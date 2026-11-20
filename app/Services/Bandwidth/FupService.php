<?php

namespace App\Services\Bandwidth;

use App\Models\Customer;
use App\Models\CustomerFupUsage;
use App\Models\Router;
use App\Services\Notification\TelegramService;
use App\Services\Notification\WhatsAppService;
use App\Services\Router\MikrotikApiService;
use Exception;
use Illuminate\Support\Facades\Log;

class FupService
{
    public function __construct(
        public MikrotikApiService $mikrotikService,
        public WhatsAppService $whatsappService,
        public ?TelegramService $telegramService = null
    ) {
        $this->telegramService = $telegramService ?: app(TelegramService::class);
    }

    /**
     * Record bandwidth usage and check if FUP quota is exceeded.
     */
    public function recordAndCheckUsage(Customer $customer, int $currentRx, int $currentTx): array
    {
        $package = $customer->package;
        $period = date('Y-m');

        $usage = CustomerFupUsage::firstOrCreate(
            ['customer_id' => $customer->id, 'period' => $period],
            [
                'bytes_download' => 0,
                'bytes_upload' => 0,
                'total_bytes' => 0,
                'last_seen_rx' => $currentRx,
                'last_seen_tx' => $currentTx,
            ]
        );

        // Calculate delta (handle user disconnect/reconnect counter reset)
        $deltaRx = ($currentRx >= $usage->last_seen_rx) ? ($currentRx - $usage->last_seen_rx) : $currentRx;
        $deltaTx = ($currentTx >= $usage->last_seen_tx) ? ($currentTx - $usage->last_seen_tx) : $currentTx;

        // In MikroTik PPPoE interface:
        // tx-byte is router transmitting to client (Customer Download)
        // rx-byte is router receiving from client (Customer Upload)
        $newDownload = $usage->bytes_download + $deltaTx;
        $newUpload = $usage->bytes_upload + $deltaRx;
        $newTotal = $newDownload + $newUpload;

        $usage->update([
            'bytes_download' => $newDownload,
            'bytes_upload' => $newUpload,
            'total_bytes' => $newTotal,
            'last_seen_rx' => $currentRx,
            'last_seen_tx' => $currentTx,
        ]);

        $usageGb = round($newTotal / (1024 * 1024 * 1024), 2);

        // Check FUP rule
        $fupEnabled = $package?->fup_enabled || ! empty($customer->fup_override_limit_gb);
        $limitGb = $customer->fup_override_limit_gb ?: ($package?->fup_limit_gb ?? 0);

        $fupTriggered = false;
        if ($fupEnabled && $limitGb > 0) {
            $limitBytes = $limitGb * 1024 * 1024 * 1024;

            if ($newTotal >= $limitBytes && $customer->fup_status !== 'fup_applied') {
                $applied = $this->applyFup($customer, $usageGb, $limitGb);
                if ($applied) {
                    $usage->update(['fup_applied_at' => now()]);
                    $fupTriggered = true;
                }
            }
        }

        return [
            'customer' => $customer->name,
            'usage_gb' => $usageGb,
            'limit_gb' => $limitGb,
            'fup_enabled' => $fupEnabled,
            'fup_triggered' => $fupTriggered,
            'fup_status' => $customer->fup_status,
        ];
    }

    /**
     * Apply FUP speed throttle to customer.
     */
    public function applyFup(Customer $customer, ?float $usageGb = null, ?float $limitGb = null): bool
    {
        $package = $customer->package;
        $router = $customer->router;

        if (! $router || empty($customer->pppoe_username)) {
            return false;
        }

        $throttleSpeed = $package?->fup_throttle_speed ?: '2M/5M';
        $fupProfile = $package?->fup_mikrotik_profile;

        try {
            if (! $this->mikrotikService->connect($router)) {
                return false;
            }

            // 1. If explicit FUP profile configured in MikroTik, assign to secret
            if (! empty($fupProfile)) {
                $find = $this->mikrotikService->comm('/ppp/secret/print', [
                    '?name' => $customer->pppoe_username,
                ]);
                $secret = $find['!re'][0] ?? null;
                if ($secret && isset($secret['.id'])) {
                    $this->mikrotikService->comm('/ppp/secret/set', [
                        'numbers' => $secret['.id'],
                        'profile' => $fupProfile,
                        'comment' => "FUP TERCAPAI - {$customer->name} | {$throttleSpeed} | ".date('Y-m-d H:i'),
                    ]);
                }
            } else {
                // Otherwise dynamically update simple queue or create FUP profile
                $queueFind = $this->mikrotikService->comm('/queue/simple/print', [
                    '?name' => "<pppoe-{$customer->pppoe_username}>",
                ]);
                $queue = $queueFind['!re'][0] ?? null;
                if ($queue && isset($queue['.id'])) {
                    $this->mikrotikService->comm('/queue/simple/set', [
                        'numbers' => $queue['.id'],
                        'max-limit' => $throttleSpeed,
                        'comment' => "FUP APPLIED ({$throttleSpeed})",
                    ]);
                }
            }

            // 2. Kick active session to immediately enforce the speed restriction
            $this->mikrotikService->kickPppActive($customer->pppoe_username);
            $this->mikrotikService->disconnect();

            // 3. Update customer record
            $customer->update([
                'fup_status' => 'fup_applied',
                'fup_notified_at' => now(),
            ]);

            // 4. Send WhatsApp Notification
            if ($usageGb === null) {
                $curUsage = $customer->currentMonthFupUsage;
                $usageGb = $curUsage ? $curUsage->usage_gb : 0;
            }
            if ($limitGb === null) {
                $limitGb = $customer->fup_override_limit_gb ?: ($package?->fup_limit_gb ?? 0);
            }

            $this->whatsappService->sendFupAlert($customer, $usageGb, $limitGb, $throttleSpeed);

            try {
                $this->telegramService?->notifyFupTriggered($customer, (float) $usageGb, (float) $limitGb, $throttleSpeed);
            } catch (\Throwable $e) {
                Log::warning('Telegram FUP notification error: '.$e->getMessage());
            }

            Log::info("FUP applied to customer {$customer->name} ({$customer->pppoe_username}) on router {$router->name}. Speed throttled to {$throttleSpeed}.");

            return true;
        } catch (Exception $e) {
            $this->mikrotikService->disconnect();
            Log::error("Failed to apply FUP to {$customer->pppoe_username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Restore customer to normal package speed.
     */
    public function restoreCustomerSpeed(Customer $customer, bool $sendNotification = false): bool
    {
        $package = $customer->package;
        $router = $customer->router;

        if (! $router || empty($customer->pppoe_username)) {
            return false;
        }

        $targetProfile = $package?->mikrotik_profile ?? 'default';

        try {
            if (! $this->mikrotikService->connect($router)) {
                return false;
            }

            $find = $this->mikrotikService->comm('/ppp/secret/print', [
                '?name' => $customer->pppoe_username,
            ]);
            $secret = $find['!re'][0] ?? null;
            if ($secret && isset($secret['.id'])) {
                $this->mikrotikService->comm('/ppp/secret/set', [
                    'numbers' => $secret['.id'],
                    'profile' => $targetProfile,
                    'comment' => "{$customer->name} | Normal Speed | ".date('Y-m-d H:i'),
                ]);
            }

            $this->mikrotikService->kickPppActive($customer->pppoe_username);
            $this->mikrotikService->disconnect();

            $customer->update([
                'fup_status' => 'normal',
            ]);

            if ($sendNotification) {
                $this->whatsappService->sendFupRestored($customer);
            }

            Log::info("FUP restored to normal for customer {$customer->name} ({$customer->pppoe_username}). Profile: {$targetProfile}.");

            return true;
        } catch (Exception $e) {
            $this->mikrotikService->disconnect();
            Log::error("Failed to restore FUP speed for {$customer->pppoe_username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Check FUP across all active PPPoE customers on a router.
     */
    public function checkRouterCustomersFup(Router $router): array
    {
        $checked = 0;
        $fupApplied = 0;
        $alreadyFup = 0;
        $normal = 0;

        try {
            if (! $this->mikrotikService->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke router'];
            }

            // Fetch active sessions
            $activeRes = $this->mikrotikService->comm('/ppp/active/print');
            $actives = $activeRes['!re'] ?? [];

            // Fetch all interfaces for bytes
            $ifaceRes = $this->mikrotikService->comm('/interface/print');
            $ifaceMap = [];
            foreach ($ifaceRes['!re'] ?? [] as $iface) {
                if (isset($iface['name'])) {
                    $ifaceMap[$iface['name']] = $iface;
                }
            }

            $this->mikrotikService->disconnect();

            foreach ($actives as $active) {
                $username = $active['name'] ?? '';
                if (! $username) {
                    continue;
                }

                $customer = Customer::where('pppoe_username', $username)
                    ->where('router_id', $router->id)
                    ->with('package')
                    ->first();

                if (! $customer) {
                    continue;
                }

                $checked++;
                $ifaceName = "<pppoe-{$username}>";
                $ifaceData = $ifaceMap[$ifaceName] ?? null;

                $rxBytes = (int) ($ifaceData['rx-byte'] ?? 0);
                $txBytes = (int) ($ifaceData['tx-byte'] ?? 0);

                $res = $this->recordAndCheckUsage($customer, $rxBytes, $txBytes);

                if ($res['fup_triggered']) {
                    $fupApplied++;
                } elseif ($res['fup_status'] === 'fup_applied') {
                    $alreadyFup++;
                } else {
                    $normal++;
                }
            }

            return [
                'success' => true,
                'checked' => $checked,
                'fup_applied' => $fupApplied,
                'already_fup' => $alreadyFup,
                'normal' => $normal,
            ];
        } catch (Exception $e) {
            Log::error("Failed checking FUP for router {$router->name}: ".$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reset monthly FUP cycle for all customers (run at start of each month).
     */
    public function resetMonthlyCycle(): array
    {
        $customers = Customer::where('fup_status', 'fup_applied')->with(['package', 'router'])->get();
        $restored = 0;

        foreach ($customers as $customer) {
            $success = $this->restoreCustomerSpeed($customer, true);
            if ($success) {
                $restored++;
            }
        }

        return [
            'total' => $customers->count(),
            'restored' => $restored,
        ];
    }
}
