<?php

namespace App\Services\Hotspot;

use App\Models\HotspotVoucher;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Exception;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    public function __construct(
        protected MikrotikApiService $mikrotikService
    ) {}

    /**
     * Get Hotspot User Profiles for a router.
     * Tries live query to MikroTik first, then falls back to local database packages.
     */
    public function getProfiles(Router $router): array
    {
        $profiles = [];

        // 1. Try pulling live profiles from MikroTik
        try {
            $liveProfiles = $this->mikrotikService->getHotspotUserProfiles($router);
            foreach ($liveProfiles as $p) {
                $name = $p['name'];
                $rate = ! empty($p['rate_limit']) ? " (Speed: {$p['rate_limit']})" : '';
                $shared = ! empty($p['shared_users']) ? " [Shared: {$p['shared_users']}]" : '';
                $profiles[$name] = "{$name}{$rate}{$shared}";
            }
        } catch (Exception $e) {
            Log::warning('Could not fetch live profiles from MikroTik: '.$e->getMessage());
        }

        // 2. Supplement with Hotspot packages saved in InternetPackage
        $localHotspotPackages = InternetPackage::where('type', 'hotspot')
            ->where('is_active', true)
            ->pluck('name', 'mikrotik_profile')
            ->toArray();

        foreach ($localHotspotPackages as $mikrotikProf => $pkgName) {
            if (! empty($mikrotikProf) && ! isset($profiles[$mikrotikProf])) {
                $profiles[$mikrotikProf] = "{$mikrotikProf} ({$pkgName})";
            }
        }

        // 3. Supplement with distinct profiles already present in HotspotVoucher
        $existingVoucherProfiles = HotspotVoucher::where('router_id', $router->id)
            ->distinct()
            ->pluck('profile', 'profile')
            ->toArray();

        foreach ($existingVoucherProfiles as $profName) {
            if (! isset($profiles[$profName])) {
                $profiles[$profName] = $profName;
            }
        }

        if (empty($profiles)) {
            $profiles['default'] = 'default (Bawaan MikroTik)';
        }

        return $profiles;
    }

    /**
     * Pull all Hotspot User Profiles from MikroTik and synchronize to InternetPackage database.
     */
    public function pullProfilesFromRouter(Router $router): array
    {
        $liveProfiles = $this->mikrotikService->getHotspotUserProfiles($router);
        $imported = 0;
        $updated = 0;

        foreach ($liveProfiles as $prof) {
            $name = $prof['name'];
            $rateLimit = $prof['rate_limit'] ?? null;
            $meta = $this->extractProfileMetadata($name, $router);

            $price = $meta['price'];
            $validity = $meta['validity'];
            $sharedUsers = $meta['shared_users'];

            $validityDays = 1;
            if (preg_match('/(\d+)\s*hari/i', $validity, $m)) {
                $validityDays = (int) $m[1];
            }

            $pkg = InternetPackage::where('mikrotik_profile', $name)
                ->where('type', 'hotspot')
                ->first();

            if ($pkg) {
                $pkg->update([
                    'speed_rate' => $rateLimit ?: $pkg->speed_rate,
                    'price' => $price,
                    'validity_days' => $validityDays,
                    'shared_users' => $sharedUsers,
                    'is_active' => true,
                ]);
                $updated++;
            } else {
                InternetPackage::create([
                    'name' => 'Hotspot '.ucfirst($name),
                    'speed_rate' => $rateLimit ?: 'Up to 10 Mbps',
                    'price' => $price,
                    'validity_days' => $validityDays,
                    'shared_users' => $sharedUsers,
                    'mikrotik_profile' => $name,
                    'type' => 'hotspot',
                    'description' => "Paket Voucher Hotspot tersinkron dari MikroTik {$router->name} (Profile: {$name})",
                    'is_active' => true,
                ]);
                $imported++;
            }
        }

        return [
            'total' => count($liveProfiles),
            'imported' => $imported,
            'updated' => $updated,
            'profiles' => $liveProfiles,
        ];
    }

    /**
     * Generate a batch of hotspot vouchers with the matching MikroTik profile.
     */
    public function generateBatch(
        Router $router,
        string $profile,
        float $price,
        string $validity,
        int $qty = 20,
        string $prefix = '',
        string $charType = 'alphanumeric_upper',
        int $codeLength = 5,
        bool $userEqualsPass = true,
        ?string $limitUptime = null,
        int $sharedUsers = 1
    ): array {
        $batchId = 'BATCH-'.date('Ymd-His');
        $generated = [];

        // Determine RouterOS limit-uptime string (e.g. 1h, 3h, 1d, 7d, 30d)
        if (! $limitUptime) {
            $limitUptime = $this->parseUptimeLimit($validity);
        }

        // Connect to router for batch push
        $routerConnected = $this->mikrotikService->connect($router);

        if ($routerConnected) {
            // Ensure profile in MikroTik has matching shared-users
            try {
                $profFind = $this->mikrotikService->comm('/ip/hotspot/user/profile/print', [
                    '?name' => $profile,
                ]);
                $profExists = $profFind['!re'][0] ?? null;
                if ($profExists && isset($profExists['.id'])) {
                    $currentShared = (int) ($profExists['shared-users'] ?? 1);
                    if ($currentShared !== $sharedUsers) {
                        $this->mikrotikService->comm('/ip/hotspot/user/profile/set', [
                            'numbers' => $profExists['.id'],
                            'shared-users' => (string) $sharedUsers,
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::warning("Could not set shared-users on profile {$profile}: ".$e->getMessage());
            }
        }

        // Update local InternetPackage if exists
        InternetPackage::where('mikrotik_profile', $profile)
            ->where('type', 'hotspot')
            ->update(['shared_users' => $sharedUsers]);

        for ($i = 0; $i < $qty; $i++) {
            $code = $this->generateVoucherCode($prefix, $charType, $codeLength);
            $password = $userEqualsPass ? $code : $this->generateVoucherCode('', 'numeric', 4);

            // Sync to MikroTik if router is connected
            if ($routerConnected) {
                try {
                    $userData = [
                        'name' => $code,
                        'password' => $password,
                        'profile' => $profile,
                        'comment' => "VOUCHER:{$batchId} | Rp".number_format($price, 0, '', '.')." | {$validity}".($sharedUsers > 1 ? " | Shared:{$sharedUsers}U" : ''),
                    ];
                    if (! empty($limitUptime)) {
                        $userData['limit-uptime'] = $limitUptime;
                    }
                    $this->mikrotikService->comm('/ip/hotspot/user/add', $userData);
                } catch (Exception $e) {
                    Log::warning("Failed to add hotspot user to MikroTik {$code}: ".$e->getMessage());
                }
            }

            $voucher = HotspotVoucher::create([
                'router_id' => $router->id,
                'code' => $code,
                'password' => $password,
                'profile' => $profile,
                'shared_users' => $sharedUsers,
                'price' => $price,
                'validity' => $validity,
                'status' => 'unused',
                'generated_batch' => $batchId,
            ]);

            $generated[] = $voucher;
        }

        if ($routerConnected) {
            $this->mikrotikService->disconnect();
        }

        return [
            'batch_id' => $batchId,
            'count' => count($generated),
            'vouchers' => $generated,
            'limit_uptime' => $limitUptime,
            'shared_users' => $sharedUsers,
        ];
    }

    /**
     * Push a single HotspotVoucher to MikroTik.
     */
    public function syncSingleVoucherToRouter(HotspotVoucher $voucher): array
    {
        $router = $voucher->router;
        if (! $router) {
            return ['success' => false, 'message' => 'Router tidak terasosiasi'];
        }

        if (! empty($voucher->shared_users) && ! empty($voucher->profile)) {
            $this->mikrotikService->ensureHotspotProfileSharedUsers($router, $voucher->profile, (int) $voucher->shared_users);
        }

        $limitUptime = $this->parseUptimeLimit($voucher->validity);
        $userData = [
            'name' => $voucher->code,
            'password' => $voucher->password,
            'profile' => $voucher->profile,
            'comment' => 'VOUCHER:'.($voucher->generated_batch ?: 'MANUAL').' | Rp'.number_format($voucher->price, 0, '', '.')." | {$voucher->validity}".($voucher->shared_users > 1 ? " | Shared:{$voucher->shared_users}U" : ''),
        ];
        if (! empty($limitUptime)) {
            $userData['limit_uptime'] = $limitUptime;
        }

        return $this->mikrotikService->syncHotspotUser($router, $userData);
    }

    /**
     * Remove single HotspotVoucher from MikroTik.
     */
    public function removeSingleVoucherFromRouter(HotspotVoucher $voucher): bool
    {
        $router = $voucher->router;
        if (! $router) {
            return false;
        }

        return $this->mikrotikService->removeHotspotUser($router, $voucher->code);
    }

    /**
     * Remove multiple vouchers from MikroTik in a single optimized connection.
     */
    public function removeBatchVouchersFromRouter(Router $router, array $voucherCodes): array
    {
        return $this->mikrotikService->removeHotspotUsersBatch($router, $voucherCodes);
    }

    /**
     * Prune orphan hotspot users on MikroTik that no longer exist in the local database.
     */
    public function pruneOrphanVouchers(Router $router): array
    {
        $validCodes = HotspotVoucher::where('router_id', $router->id)->pluck('code')->all();

        return $this->mikrotikService->pruneOrphanHotspotUsers($router, $validCodes);
    }

    /**
     * Import users from MikroTik that are not in the local database.
     */
    public function importVouchersFromRouter(Router $router): array
    {
        $pull = $this->mikrotikService->pullHotspotUsers($router);
        if (! ($pull['success'] ?? false)) {
            return ['success' => false, 'message' => $pull['message'] ?? 'Gagal membaca router', 'imported' => 0];
        }

        $imported = 0;
        foreach ($pull['users'] ?? [] as $userData) {
            $code = $userData['code'];
            $exists = HotspotVoucher::where('router_id', $router->id)->where('code', $code)->exists();
            if ($exists) {
                continue;
            }

            $profile = $userData['profile'] ?? 'default';
            $pkg = InternetPackage::where('mikrotik_profile', $profile)->where('type', 'hotspot')->first();

            HotspotVoucher::create([
                'router_id' => $router->id,
                'code' => $code,
                'password' => $userData['password'] ?? $code,
                'profile' => $profile,
                'price' => $pkg?->price ?? 5000,
                'validity' => $pkg ? ($pkg->validity_days.' Hari') : ($userData['limit_uptime'] ?? '1 Hari'),
                'status' => 'unused',
                'generated_batch' => 'IMPORT-'.date('Ymd'),
            ]);

            $imported++;
        }

        return [
            'success' => true,
            'imported' => $imported,
            'total_found' => count($pull['users'] ?? []),
        ];
    }

    /**
     * Sync voucher status with active MikroTik hotspot sessions.
     */
    public function syncVoucherStatus(Router $router): array
    {
        $activeSessions = $this->mikrotikService->getHotspotActive($router);
        $updatedCount = 0;

        foreach ($activeSessions as $session) {
            $username = $session['user'] ?? '';
            $mac = $session['mac_address'] ?? null;

            if ($username) {
                $voucher = HotspotVoucher::where('router_id', $router->id)
                    ->where('code', $username)
                    ->where('status', 'unused')
                    ->first();

                if ($voucher) {
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now(),
                        'used_by_mac' => $mac,
                    ]);
                    $updatedCount++;
                }
            }
        }

        return [
            'active_sessions_count' => count($activeSessions),
            'vouchers_marked_used' => $updatedCount,
            'active_sessions' => $activeSessions,
        ];
    }

    /**
     * Helper to generate unique random code based on options.
     */
    protected function generateVoucherCode(string $prefix, string $charType, int $length): string
    {
        switch ($charType) {
            case 'numeric':
                $characters = '0123456789';
                break;
            case 'alpha_upper':
                $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // exclude ambiguous I, O
                break;
            case 'alphanumeric_lower':
                $characters = '23456789abcdefghjkmnpqrstuvwxyz';
                break;
            case 'alphanumeric_upper':
            default:
                $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;
        }

        $code = '';
        $maxIndex = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, $maxIndex)];
        }

        return strtoupper($prefix).$code;
    }

    /**
     * Extract validity, price, shared-users, and uptime limit from a Hotspot Profile name or MikroTik profile data.
     */
    public function extractProfileMetadata(string $profileName, ?Router $router = null): array
    {
        $validity = '1 Hari';
        $price = 5000;
        $sharedUsers = 1;

        // 1. Check local InternetPackage first
        $pkg = InternetPackage::where('mikrotik_profile', $profileName)
            ->where('type', 'hotspot')
            ->first();

        if ($pkg) {
            if ($pkg->price > 0) {
                $price = (float) $pkg->price;
            }
            if ($pkg->shared_users > 0) {
                $sharedUsers = (int) $pkg->shared_users;
            }
            if ($pkg->validity_days > 0) {
                $validity = $pkg->validity_days.' Hari';
            }
        }

        // 2. Check live MikroTik profile if router is provided
        if ($router) {
            try {
                $liveProfiles = $this->mikrotikService->getHotspotUserProfiles($router);
                foreach ($liveProfiles as $lp) {
                    if (($lp['name'] ?? '') === $profileName) {
                        if (! empty($lp['shared_users'])) {
                            $sharedUsers = (int) $lp['shared_users'];
                        }
                        if (! empty($lp['session_timeout'])) {
                            $parsedSession = $this->convertRouterOsTimeToHuman($lp['session_timeout']);
                            if ($parsedSession) {
                                $validity = $parsedSession;
                            }
                        }
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore router query errors
            }
        }

        // 3. Intelligent Profile Name Parsing (Universal Mikhmon/MikroTik standards)
        $normalized = strtolower(str_replace(['_', '-', '.', '/'], ' ', $profileName));

        // Detect Days (Hari) e.g. 30hari, 30 hari, 30d
        if (preg_match('/(\d+)\s*(?:hari|days|day|d\b)/i', $normalized, $matches)) {
            $validity = $matches[1].' Hari';
        }
        // Detect Hours (Jam) e.g. 3jam, 3 jam, 3h
        elseif (preg_match('/(\d+)\s*(?:jam|hours|hour|h\b)/i', $normalized, $matches)) {
            $validity = $matches[1].' Jam';
        }
        // Detect Weeks (Minggu) e.g. 1minggu, 1week, 1w
        elseif (preg_match('/(\d+)\s*(?:minggu|weeks|week|w\b)/i', $normalized, $matches)) {
            $days = (int) $matches[1] * 7;
            $validity = $days.' Hari';
        }
        // Detect Months (Bulan) e.g. 1bulan, 1month, 1m
        elseif (preg_match('/(\d+)\s*(?:bulan|months|month|m\b)/i', $normalized, $matches)) {
            $days = (int) $matches[1] * 30;
            $validity = $days.' Hari';
        }

        // Detect Price from name: e.g. 60K -> 60000, 5K -> 5000, 3K -> 3000, 20K -> 20000
        if (preg_match('/(\d+)\s*k\b/i', $normalized, $pMatches)) {
            $price = (float) $pMatches[1] * 1000;
        } elseif (preg_match('/(?:\D|^)(\d{4,6})(?:\D|$)/', $profileName, $pMatches)) {
            $val = (float) $pMatches[1];
            if ($val >= 1000) {
                $price = $val;
            }
        }

        // Detect Shared Users in name if specified (e.g. 3user, 2u, shared3)
        if (preg_match('/(\d+)\s*(?:user|usr|u)\b/i', $normalized, $uMatches)) {
            $sharedUsers = (int) $uMatches[1];
        }

        return [
            'profile' => $profileName,
            'validity' => $validity,
            'limit_uptime' => $this->parseUptimeLimit($validity) ?: '1d',
            'price' => $price,
            'shared_users' => max(1, $sharedUsers),
        ];
    }

    /**
     * Convert RouterOS time notation (e.g. 03:00:00, 1d 00:00:00, 30d, 1h) to human string.
     */
    public function convertRouterOsTimeToHuman(string $timeStr): ?string
    {
        $timeStr = trim($timeStr);
        if (empty($timeStr)) {
            return null;
        }

        if (preg_match('/(\d+)d/i', $timeStr, $m)) {
            return $m[1].' Hari';
        }
        if (preg_match('/(\d+)w/i', $timeStr, $m)) {
            return ($m[1] * 7).' Hari';
        }
        if (preg_match('/(\d+)h/i', $timeStr, $m)) {
            return $m[1].' Jam';
        }

        // HH:MM:SS format e.g. 03:00:00
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $timeStr, $m)) {
            $hours = (int) $m[1];
            if ($hours >= 24 && $hours % 24 === 0) {
                return ($hours / 24).' Hari';
            }
            if ($hours > 0) {
                return $hours.' Jam';
            }
        }

        return null;
    }

    /**
     * Helper to translate human validity string to RouterOS uptime limit.
     */
    public function parseUptimeLimit(string $validity): ?string
    {
        $val = strtolower($validity);

        // Check days first
        if (preg_match('/(\d+)\s*(?:hari|days|day|d\b)/i', $val, $matches)) {
            return $matches[1].'d';
        }
        // Check hours with word boundary so it does not match "hari"
        if (preg_match('/(\d+)\s*(?:jam|hours|hour|h\b)/i', $val, $matches)) {
            return $matches[1].'h';
        }
        // Check weeks
        if (preg_match('/(\d+)\s*(?:minggu|weeks|week|w\b)/i', $val, $matches)) {
            return ((int) $matches[1] * 7).'d';
        }
        // Check months
        if (preg_match('/(\d+)\s*(?:bulan|months|month|m\b)/i', $val, $matches)) {
            return ((int) $matches[1] * 30).'d';
        }

        return match ($val) {
            '1 jam' => '1h',
            '2 jam' => '2h',
            '3 jam' => '3h',
            '4 jam' => '4h',
            '5 jam' => '5h',
            '6 jam' => '6h',
            '12 jam' => '12h',
            '1 hari', '1 hari (24 jam)' => '1d',
            '2 hari' => '2d',
            '3 hari' => '3d',
            '7 hari', '7 hari (1 minggu)' => '7d',
            '14 hari' => '14d',
            '30 hari', '30 hari (1 bulan)' => '30d',
            default => null,
        };
    }
}
