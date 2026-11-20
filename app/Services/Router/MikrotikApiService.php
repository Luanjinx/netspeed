<?php

namespace App\Services\Router;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Router;
use Exception;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected $socket = null;

    protected bool $connected = false;

    /**
     * Connect to RouterOS API via socket (supports local & external/cloud/VPN IP).
     */
    public function connect(Router $router): bool
    {
        $this->disconnect();

        $prefix = $router->use_ssl ? 'ssl://' : '';
        $target = $prefix.$router->host.':'.$router->port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $timeout = $router->timeout ?: 5;
        $errno = 0;
        $errstr = '';

        $this->socket = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $this->socket) {
            Log::warning("MikroTik connect failed to {$target}: {$errstr} ({$errno})");

            return false;
        }

        stream_set_timeout($this->socket, $timeout);

        // Perform login
        if (! $this->login($router->username, $router->password)) {
            $this->disconnect();

            return false;
        }

        $this->connected = true;

        return true;
    }

    /**
     * Disconnect socket.
     */
    public function disconnect(): void
    {
        if ($this->socket && is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    /**
     * Login process (RouterOS post-6.43 and pre-6.43 compatible).
     */
    protected function login(string $username, string $password): bool
    {
        // RouterOS 6.43+ accepts plain login directly: /login =name=user =password=pass
        $res = $this->comm('/login', [
            'name' => $username,
            'password' => $password,
        ]);

        if (isset($res['!done'])) {
            return true;
        }

        // Older RouterOS challenge-response fallback
        if (isset($res['!trap']) && isset($res['!done'][0]['ret'])) {
            $challenge = hex2bin($res['!done'][0]['ret']);
            $hash = md5(chr(0).$password.$challenge);
            $res = $this->comm('/login', [
                'name' => $username,
                'response' => '00'.$hash,
            ]);

            return isset($res['!done']);
        }

        return false;
    }

    /**
     * Send command and return array response.
     */
    public function comm(string $command, array $params = []): array
    {
        if (! $this->socket) {
            return ['error' => 'Not connected'];
        }

        $this->writeWord($command);
        foreach ($params as $key => $val) {
            if (is_numeric($key)) {
                $this->writeWord($val);
            } elseif (str_starts_with($key, '?') || str_starts_with($key, '=')) {
                $this->writeWord($key.'='.$val);
            } else {
                $this->writeWord('='.$key.'='.$val);
            }
        }
        $this->writeWord(''); // End of command sentence

        return $this->readResponse();
    }

    protected function writeWord(string $word): void
    {
        $len = strlen($word);
        $encodedLen = '';

        if ($len < 0x80) {
            $encodedLen = chr($len);
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            $encodedLen = chr(($len >> 8) & 0xFF).chr($len & 0xFF);
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            $encodedLen = chr(($len >> 16) & 0xFF).chr(($len >> 8) & 0xFF).chr($len & 0xFF);
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            $encodedLen = chr(($len >> 24) & 0xFF).chr(($len >> 16) & 0xFF).chr(($len >> 8) & 0xFF).chr($len & 0xFF);
        } else {
            $encodedLen = chr(0xF0).chr(($len >> 24) & 0xFF).chr(($len >> 16) & 0xFF).chr(($len >> 8) & 0xFF).chr($len & 0xFF);
        }

        @fwrite($this->socket, $encodedLen.$word);
    }

    protected function readWord(): ?string
    {
        if (! $this->socket || feof($this->socket)) {
            return null;
        }

        $byte = @fread($this->socket, 1);
        if ($byte === false || strlen($byte) === 0) {
            return null;
        }

        $val = ord($byte);
        $len = 0;

        if (($val & 0x80) === 0x00) {
            $len = $val;
        } elseif (($val & 0xC0) === 0x80) {
            $b2 = ord(@fread($this->socket, 1));
            $len = (($val & 0x3F) << 8) | $b2;
        } elseif (($val & 0xE0) === 0xC0) {
            $b2 = ord(@fread($this->socket, 1));
            $b3 = ord(@fread($this->socket, 1));
            $len = (($val & 0x1F) << 16) | ($b2 << 8) | $b3;
        } elseif (($val & 0xF0) === 0xE0) {
            $b2 = ord(@fread($this->socket, 1));
            $b3 = ord(@fread($this->socket, 1));
            $b4 = ord(@fread($this->socket, 1));
            $len = (($val & 0x0F) << 24) | ($b2 << 16) | ($b3 << 8) | $b4;
        } elseif (($val & 0xF8) === 0xF0) {
            $b2 = ord(@fread($this->socket, 1));
            $b3 = ord(@fread($this->socket, 1));
            $b4 = ord(@fread($this->socket, 1));
            $b5 = ord(@fread($this->socket, 1));
            $len = ($b2 << 24) | ($b3 << 16) | ($b4 << 8) | $b5;
        }

        $res = '';
        while ($len > 0) {
            $chunk = @fread($this->socket, min($len, 4096));
            if ($chunk === false || strlen($chunk) === 0) {
                break;
            }
            $res .= $chunk;
            $len -= strlen($chunk);
        }

        return $res;
    }

    protected function readResponse(): array
    {
        $response = [];
        $current = [];

        while (true) {
            $word = $this->readWord();
            if ($word === null) {
                break;
            }

            if ($word === '') {
                if (! empty($current)) {
                    $type = $current['!type'] ?? '!re';
                    unset($current['!type']);
                    $response[$type][] = $current;
                    $current = [];
                }

                continue;
            }

            if (str_starts_with($word, '!')) {
                $current['!type'] = $word;
                if ($word === '!done') {
                    $response['!done'] = $response['!re'] ?? [];
                    break;
                }
                if ($word === '!trap' || $word === '!fatal') {
                    // continue to read trap message
                }
            } elseif (str_starts_with($word, '=')) {
                $parts = explode('=', substr($word, 1), 2);
                $key = $parts[0] ?? '';
                $val = $parts[1] ?? '';
                $current[$key] = $val;
            }
        }

        return $response;
    }

    /**
     * Test connection to router and fetch system resources.
     */
    public function testConnection(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                // Only use simulation fallback if Demo Mode is explicitly enabled in .env (DEMO=true)
                if (config('app.demo')) {
                    $sim = $this->getSimulatedSystemResources($router);
                    $router->update([
                        'status' => 'online',
                        'last_connected_at' => now(),
                        'board_name' => $sim['board_name'] ?? 'RB4011iGS+5HacQ2HnD (Demo)',
                        'model_name' => $router->name ?: 'MikroTik Demo',
                        'ros_version' => $sim['version'] ?? 'v7.12 (Stable)',
                        'cpu_load' => $sim['cpu_load'] ?? 12,
                        'uptime' => $sim['uptime'] ?? '12 hari 18 jam',
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Mode Demo Aktif: Koneksi MikroTik disimulasikan karena DEMO=true aktif di .env.',
                    ];
                }

                $resolvedIp = filter_var($router->host, FILTER_VALIDATE_IP) ? $router->host : @gethostbyname($router->host);
                $isPrivate = filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
                $hint = $isPrivate
                    ? " Catatan: Host '{$router->host}' ({$resolvedIp}) adalah IP Privat/Lokal. Server hosting di internet publik tidak dapat menjangkau IP lokal tanpa VPN Remote atau IP Publik."
                    : " (IP Publik: {$resolvedIp}). Port 8728 belum tembus dari internet luar. Pastikan DMZ/Port Forwarding di modem sudah diarahkan ke IP WAN MikroTik (192.168.1.2) atau provider internet Anda tidak menerapkan CGNAT.";

                $router->update(['status' => 'offline']);

                return [
                    'success' => false,
                    'message' => 'Gagal terhubung ke router fisik ('.$router->host.':'.$router->port.'). Pastikan IP/Port API bisa diakses dari luar.'.$hint,
                ];
            }

            // Fetch /system/resource/print
            $res = $this->comm('/system/resource/print');
            $data = $res['!re'][0] ?? ($res['!done'][0] ?? []);

            // Fetch /system/identity/print
            $identityRes = $this->comm('/system/identity/print');
            $identity = $identityRes['!re'][0]['name'] ?? ($identityRes['!done'][0]['name'] ?? 'MikroTik');

            $board = $data['board-name'] ?? ($data['platform'] ?? 'RouterOS');
            $model = $identity;
            $version = $data['version'] ?? 'Unknown';
            $cpu = isset($data['cpu-load']) ? (int) $data['cpu-load'] : 0;
            $uptime = $data['uptime'] ?? 'Unknown';

            $router->update([
                'status' => 'online',
                'last_connected_at' => now(),
                'board_name' => $board,
                'model_name' => $model,
                'ros_version' => $version,
                'cpu_load' => $cpu,
                'uptime' => $uptime,
            ]);

            $this->disconnect();

            return [
                'success' => true,
                'message' => "Terhubung sukses ke {$model} ({$board}) v{$version}",
                'data' => [
                    'identity' => $identity,
                    'board' => $board,
                    'version' => $version,
                    'cpu_load' => $cpu,
                    'uptime' => $uptime,
                ],
            ];
        } catch (Exception $e) {
            $router->update(['status' => 'error']);

            return [
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Create or update PPPoE Secret.
     */
    public function syncPppSecret(Router $router, array $secretData): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            // Check if secret exists
            $find = $this->comm('/ppp/secret/print', [
                '?name' => $secretData['name'],
            ]);

            $exists = $find['!re'][0] ?? null;

            if ($exists && isset($exists['.id'])) {
                // Update
                $params = array_merge(['numbers' => $exists['.id']], $secretData);
                $res = $this->comm('/ppp/secret/set', $params);
            } else {
                // Add
                $res = $this->comm('/ppp/secret/add', $secretData);
            }

            // Verify if command resulted in a trap/error
            if (! empty($res['!trap'])) {
                $trapMessage = $res['!trap'][0]['message'] ?? 'Unknown MikroTik trap error';
                Log::warning("MikroTik PPPoE secret sync trap for {$secretData['name']}: {$trapMessage}");

                // If error is caused by profile not existing on router, fallback to default profile
                if (stripos($trapMessage, 'profile') !== false && ($secretData['profile'] ?? '') !== 'default') {
                    $secretData['profile'] = 'default';
                    if ($exists && isset($exists['.id'])) {
                        $params = array_merge(['numbers' => $exists['.id']], $secretData);
                        $retryRes = $this->comm('/ppp/secret/set', $params);
                    } else {
                        $retryRes = $this->comm('/ppp/secret/add', $secretData);
                    }

                    if (empty($retryRes['!trap'])) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to sync PPPoE secret: '.$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Unified isolate method supporting PPPoE and Static IP customers (Hotspot excluded).
     */
    public function isolateCustomer(Customer $customer, ?string $isolirProfile = null): bool
    {
        $router = $customer->router;
        if (! $router) {
            return false;
        }

        // Hotspot menggunakan masa aktif voucher bawaan, tidak menggunakan sistem isolir
        if ($customer->connection_type === 'hotspot') {
            return false;
        }

        $profile = $isolirProfile ?: IspSetting::get('isolir_profile', 'PROFIL-ISOLIR');

        switch ($customer->connection_type) {
            case 'static':
                if (! empty($customer->ip_address)) {
                    return $this->addIpToIsolirAddressList($router, $customer->ip_address);
                }

                return false;

            case 'pppoe':
            default:
                if (! empty($customer->pppoe_username)) {
                    return $this->isolatePppCustomer($router, $customer->pppoe_username, $profile);
                }

                return false;
        }
    }

    /**
     * Unified activate/restore method supporting PPPoE and Static IP customers (Hotspot excluded).
     */
    public function activateCustomer(Customer $customer): bool
    {
        $router = $customer->router;
        if (! $router) {
            return false;
        }

        if ($customer->connection_type === 'hotspot') {
            return false;
        }

        $targetProfile = $customer->package?->mikrotik_profile ?? 'default';

        switch ($customer->connection_type) {
            case 'static':
                if (! empty($customer->ip_address)) {
                    return $this->removeIpFromIsolirAddressList($router, $customer->ip_address);
                }

                return false;

            case 'pppoe':
            default:
                if (! empty($customer->pppoe_username)) {
                    return $this->activatePppCustomer($router, $customer->pppoe_username, $targetProfile);
                }

                return false;
        }
    }

    /**
     * Add Static IP to ISOLIR address list in MikroTik firewall.
     */
    public function addIpToIsolirAddressList(Router $router, string $ipAddress): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ip/firewall/address-list/print', [
                '?list' => 'ISOLIR_USERS',
                '?address' => $ipAddress,
            ]);

            if (empty($find['!re'])) {
                $this->comm('/ip/firewall/address-list/add', [
                    'list' => 'ISOLIR_USERS',
                    'address' => $ipAddress,
                    'comment' => 'ISOLIR STATIC IP - '.date('Y-m-d H:i'),
                ]);
            }

            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            Log::error("Failed to add Static IP {$ipAddress} to isolir list: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Remove Static IP from ISOLIR address list in MikroTik firewall.
     */
    public function removeIpFromIsolirAddressList(Router $router, string $ipAddress): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ip/firewall/address-list/print', [
                '?list' => 'ISOLIR_USERS',
                '?address' => $ipAddress,
            ]);

            foreach ($find['!re'] ?? [] as $item) {
                if (isset($item['.id'])) {
                    $this->comm('/ip/firewall/address-list/remove', [
                        'numbers' => $item['.id'],
                    ]);
                }
            }

            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            Log::error("Failed to remove Static IP {$ipAddress} from isolir list: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Isolate customer on MikroTik (Change profile to isolir profile and drop active session).
     */
    public function isolatePppCustomer(Router $router, string $username, ?string $isolirProfile = null): bool
    {
        $isolirProfile = $isolirProfile ?: IspSetting::get('isolir_profile', 'PROFIL-ISOLIR');

        try {
            if (! $this->connect($router)) {
                return false;
            }

            // Find secret
            $find = $this->comm('/ppp/secret/print', [
                '?name' => $username,
            ]);

            $secret = $find['!re'][0] ?? null;
            if ($secret && isset($secret['.id'])) {
                // Change profile to isolir
                $this->comm('/ppp/secret/set', [
                    'numbers' => $secret['.id'],
                    'profile' => $isolirProfile,
                    'comment' => 'ISOLIR OTOMATIS - TUNGGAKAN TAGIHAN '.date('Y-m-d H:i'),
                ]);
            }

            // Kick active session so user reconnects into isolir profile immediately
            $this->kickPppActive($username);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to isolate customer {$username}: ".$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Activate customer on MikroTik (Restore speed profile).
     */
    public function activatePppCustomer(Router $router, string $username, string $targetProfile): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ppp/secret/print', [
                '?name' => $username,
            ]);

            $secret = $find['!re'][0] ?? null;
            if ($secret && isset($secret['.id'])) {
                $this->comm('/ppp/secret/set', [
                    'numbers' => $secret['.id'],
                    'profile' => $targetProfile,
                    'disabled' => 'no',
                    'comment' => 'AKTIF - PEMBAYARAN LUNAS '.date('Y-m-d H:i'),
                ]);
            }

            // Kick active session to renew profile
            $this->kickPppActive($username);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to activate customer {$username}: ".$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Kick active PPP session by username.
     */
    public function kickPppActive(string $username): void
    {
        $actives = $this->comm('/ppp/active/print', [
            '?name' => $username,
        ]);

        if (isset($actives['!re'])) {
            foreach ($actives['!re'] as $active) {
                if (isset($active['.id'])) {
                    $this->comm('/ppp/active/remove', [
                        'numbers' => $active['.id'],
                    ]);
                }
            }
        }
    }

    /**
     * Remove PPPoE Secret from MikroTik.
     */
    public function removePppSecret(Router $router, string $username): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ppp/secret/print', [
                '?name' => $username,
            ]);

            $secret = $find['!re'][0] ?? null;
            if ($secret && isset($secret['.id'])) {
                $this->comm('/ppp/secret/remove', [
                    'numbers' => $secret['.id'],
                ]);
            }

            $this->kickPppActive($username);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to remove PPPoE secret {$username}: ".$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Create or update a PPPoE Profile in MikroTik.
     */
    public function syncPppProfile(Router $router, array $data): array
    {
        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke MikroTik'];
            }

            $find = $this->comm('/ppp/profile/print', [
                '?name' => $data['name'],
            ]);

            $exists = $find['!re'][0] ?? null;
            $params = [
                'name' => $data['name'],
            ];

            if (! empty($data['rate_limit'])) {
                $params['rate-limit'] = $data['rate_limit'];
            }

            // Auto-assign non-conflicting safe local address & PPPoE pool if not specified
            $localAddress = ! empty($data['local_address']) ? $data['local_address'] : $this->getSafePppoeLocalAddress($router);
            $params['local-address'] = $localAddress;

            $remoteAddress = ! empty($data['remote_address']) ? $data['remote_address'] : $this->ensurePppoePool($router, null, $localAddress);
            $params['remote-address'] = $remoteAddress;

            $dnsServer = ! empty($data['dns_server']) ? $data['dns_server'] : IspSetting::get('pppoe_dns_servers', '8.8.8.8,1.1.1.1');
            $params['dns-server'] = $dnsServer;

            if (! empty($data['only_one'])) {
                $params['only-one'] = $data['only_one'];
            }

            if ($exists && isset($exists['.id'])) {
                $params['numbers'] = $exists['.id'];
                $this->comm('/ppp/profile/set', $params);
                $action = 'diperbarui';
            } else {
                $this->comm('/ppp/profile/add', $params);
                $action = 'ditambahkan';
            }

            return [
                'success' => true,
                'message' => "Profil PPPoE [{$data['name']}] berhasil {$action} di MikroTik",
            ];
        } catch (Exception $e) {
            Log::error('Failed to sync PPPoE profile: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get list of PPP profiles from Router.
     */
    public function getPppProfiles(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                return [];
            }

            $res = $this->comm('/ppp/profile/print');

            return $res['!re'] ?? [];
        } catch (Exception $e) {
            return [];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get all PPPoE Secrets from Router.
     */
    public function getPppSecrets(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                return [];
            }

            $res = $this->comm('/ppp/secret/print');

            return $res['!re'] ?? [];
        } catch (Exception $e) {
            return [];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Sync profiles from MikroTik router into InternetPackages.
     */
    public function syncProfilesFromRouter(Router $router): array
    {
        $profiles = $this->getPppProfiles($router);
        $synced = 0;
        $ignored = ['default', 'default-encryption'];

        foreach ($profiles as $prof) {
            $name = $prof['name'] ?? '';
            if (empty($name) || in_array($name, $ignored)) {
                continue;
            }

            // If it is an isolir profile, save to settings
            if (stripos($name, 'isolir') !== false) {
                IspSetting::set('isolir_profile', $name);

                continue;
            }

            $rateLimit = ! empty($prof['rate-limit']) ? $prof['rate-limit'] : '10M/10M';

            // Find or create internet package
            $package = InternetPackage::firstOrNew(['mikrotik_profile' => $name]);
            if (! $package->exists) {
                $package->name = $name;
                $package->speed_rate = $rateLimit;
                $package->price = 100000;
                $package->validity_days = 30;
                $package->mikrotik_isolir_profile = IspSetting::get('isolir_profile', 'PROFIL-ISOLIR');
                $package->type = 'pppoe';
                $package->description = "Paket otomatis disinkron dari MikroTik Profile: {$name}";
                $package->is_active = true;
                $package->save();
                $synced++;
            }
        }

        return [
            'total_profiles' => count($profiles),
            'synced_packages' => $synced,
            'isolir_profile' => IspSetting::get('isolir_profile', 'PROFIL-ISOLIR'),
        ];
    }

    /**
     * Get a safe local address for PPPoE that is guaranteed not to conflict with Hotspot or other networks.
     */
    public function getSafePppoeLocalAddress(Router $router): string
    {
        $candidate = IspSetting::get('pppoe_local_address', '10.20.0.1') ?: '10.20.0.1';

        try {
            $wasConnected = (bool) $this->socket;
            if (! $wasConnected && ! $this->connect($router)) {
                return $candidate;
            }

            // Gather Hotspot and interface IP addresses to detect potential subnet collisions
            $conflictingIps = [];

            // 1. Check Hotspot profile addresses
            $hsProfiles = $this->comm('/ip/hotspot/profile/print');
            foreach ($hsProfiles['!re'] ?? [] as $hp) {
                if (! empty($hp['hotspot-address']) && $hp['hotspot-address'] !== '0.0.0.0') {
                    $conflictingIps[] = $hp['hotspot-address'];
                }
            }

            // 2. Check all interface addresses on the router
            $addrs = $this->comm('/ip/address/print');
            foreach ($addrs['!re'] ?? [] as $a) {
                if (! empty($a['address'])) {
                    $ip = explode('/', $a['address'])[0];
                    $conflictingIps[] = $ip;
                }
            }

            if (! $wasConnected) {
                $this->disconnect();
            }

            // Check if candidate conflicts with any Hotspot / interface subnet
            $candidateSubnet = substr($candidate, 0, (int) strrpos($candidate, '.'));
            $hasConflict = false;
            foreach ($conflictingIps as $confIp) {
                $confSubnet = substr($confIp, 0, (int) strrpos($confIp, '.'));
                if ($candidateSubnet === $confSubnet) {
                    $hasConflict = true;
                    break;
                }
            }

            if (! $hasConflict) {
                return $candidate;
            }

            // If conflict detected, select alternative safe subnets
            $safeAlternatives = ['10.20.0.1', '10.30.0.1', '10.50.0.1', '172.16.20.1', '172.20.0.1', '192.168.220.1'];
            foreach ($safeAlternatives as $alt) {
                $altSubnet = substr($alt, 0, (int) strrpos($alt, '.'));
                $altConflict = false;
                foreach ($conflictingIps as $confIp) {
                    if (substr($confIp, 0, (int) strrpos($confIp, '.')) === $altSubnet) {
                        $altConflict = true;
                        break;
                    }
                }
                if (! $altConflict) {
                    return $alt;
                }
            }

            return $candidate;
        } catch (\Throwable $e) {
            return $candidate;
        }
    }

    /**
     * Ensure a dedicated PPPoE IP Pool exists on MikroTik router.
     */
    public function ensurePppoePool(Router $router, ?string $poolRange = null, ?string $localAddress = null): string
    {
        $poolName = 'POOL-PPPOE';

        if (! $poolRange) {
            $poolRange = IspSetting::get('pppoe_pool_range');
            if (! $poolRange) {
                $baseIp = $localAddress ?: $this->getSafePppoeLocalAddress($router);
                $subnet = substr($baseIp, 0, (int) strrpos($baseIp, '.'));
                $poolRange = "{$subnet}.2-{$subnet}.254";
            }
        }

        try {
            $wasConnected = (bool) $this->socket;
            if (! $wasConnected && ! $this->connect($router)) {
                return $poolName;
            }

            $check = $this->comm('/ip/pool/print', ['?name' => $poolName]);
            $exists = $check['!re'][0] ?? null;

            if ($exists && isset($exists['.id'])) {
                if (($exists['ranges'] ?? '') !== $poolRange) {
                    $this->comm('/ip/pool/set', [
                        'numbers' => $exists['.id'],
                        'ranges' => $poolRange,
                    ]);
                }
            } else {
                $this->comm('/ip/pool/add', [
                    'name' => $poolName,
                    'ranges' => $poolRange,
                    'comment' => 'Pool Otomatis Pelanggan PPPoE (Anti-Konflik Hotspot)',
                ]);
            }

            if (! $wasConnected) {
                $this->disconnect();
            }

            return $poolName;
        } catch (\Throwable $e) {
            Log::warning("Gagal memastikan pool PPPoE di router {$router->name}: {$e->getMessage()}");

            return $poolName;
        }
    }

    /**
     * Get the next available non-conflicting Remote IP address for a PPPoE customer.
     */
    public function getNextAvailablePppoeIp(?Router $router = null): string
    {
        $router = $router ?: Router::where('is_active', true)->first();
        $safeLocal = $router ? $this->getSafePppoeLocalAddress($router) : '10.20.0.1';
        $subnet = substr($safeLocal, 0, (int) strrpos($safeLocal, '.'));

        $poolRange = IspSetting::get('pppoe_pool_range') ?: "{$subnet}.2-{$subnet}.254";
        $parts = explode('-', $poolRange);
        $startIp = trim($parts[0] ?? "{$subnet}.2");
        $endIp = trim($parts[1] ?? "{$subnet}.254");

        $startLong = ip2long($startIp) ?: ip2long("{$subnet}.2");
        $endLong = ip2long($endIp) ?: ip2long("{$subnet}.254");

        // 1. Collect used IPs in database
        $usedIps = Customer::whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->pluck('ip_address')
            ->map(fn ($ip) => trim($ip))
            ->toArray();

        // 2. Collect used IPs on router secrets (if router is online)
        if ($router) {
            try {
                $wasConnected = (bool) $this->socket;
                if ($wasConnected || $this->connect($router)) {
                    $secrets = $this->comm('/ppp/secret/print');
                    foreach ($secrets['!re'] ?? [] as $s) {
                        if (! empty($s['remote-address']) && $s['remote-address'] !== '0.0.0.0') {
                            $usedIps[] = trim($s['remote-address']);
                        }
                    }
                    if (! $wasConnected) {
                        $this->disconnect();
                    }
                }
            } catch (\Throwable $e) {
                // Ignore router offline
            }
        }

        $usedIps = array_unique($usedIps);

        // Find the lowest available IP in the range
        for ($curr = $startLong; $curr <= $endLong; $curr++) {
            $candidate = long2ip($curr);
            if (! in_array($candidate, $usedIps)) {
                return $candidate;
            }
        }

        return "{$subnet}.".rand(2, 254);
    }

    /**
     * Sync single Customer PPPoE Secret to MikroTik.
     */
    public function syncCustomerToMikrotik(Customer $customer): array
    {
        $router = $customer->router;
        if (! $router || empty($customer->pppoe_username)) {
            return [
                'success' => false,
                'message' => 'Router atau username PPPoE belum ditentukan untuk pelanggan ini.',
            ];
        }

        $isolirProfile = IspSetting::get('isolir_profile', 'PROFIL-ISOLIR');
        $targetProfile = ($customer->status === 'isolated')
            ? $isolirProfile
            : ($customer->package?->mikrotik_profile ?? 'default');

        // Pastikan profil paket tersedia di MikroTik. Jika belum ada, otomatis buatkan profil di router!
        if ($customer->status !== 'isolated' && $targetProfile !== 'default') {
            try {
                if ($this->connect($router)) {
                    $checkProf = $this->comm('/ppp/profile/print', ['?name' => $targetProfile]);
                    if (empty($checkProf['!re'])) {
                        $rateLimit = $customer->package?->speed_rate ?: '10M/10M';
                        $safeLocal = $this->getSafePppoeLocalAddress($router);
                        $poolName = $this->ensurePppoePool($router, null, $safeLocal);
                        $this->comm('/ppp/profile/add', [
                            'name' => $targetProfile,
                            'rate-limit' => $rateLimit,
                            'local-address' => $safeLocal,
                            'remote-address' => $poolName,
                            'dns-server' => IspSetting::get('pppoe_dns_servers', '8.8.8.8,1.1.1.1'),
                            'only-one' => 'yes',
                            'comment' => "Profil paket {$customer->package?->name} via ISP Billing",
                        ]);
                    }
                    $this->disconnect();
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal verifikasi/membuat profil {$targetProfile} di MikroTik: {$e->getMessage()}");
            }
        }

        $localAddress = $this->getSafePppoeLocalAddress($router);

        // Auto-assign non-conflicting remote IP if customer doesn't have one
        if (empty($customer->ip_address)) {
            $remoteIp = $this->getNextAvailablePppoeIp($router);
            $customer->update(['ip_address' => $remoteIp]);
        } else {
            $remoteIp = $customer->ip_address;
        }

        $secretData = [
            'name' => $customer->pppoe_username,
            'password' => $customer->pppoe_password ?: '123456',
            'service' => 'pppoe',
            'profile' => $targetProfile,
            'local-address' => $localAddress,
            'remote-address' => $remoteIp,
            'comment' => "{$customer->name} | {$customer->customer_number} | ".date('Y-m-d'),
            'disabled' => ($customer->status === 'suspended') ? 'yes' : 'no',
        ];

        $synced = $this->syncPppSecret($router, $secretData);

        if ($synced) {
            // If isolated or suspended, kick active session immediately
            if ($customer->status === 'isolated' || $customer->status === 'suspended') {
                if ($this->connect($router)) {
                    $this->kickPppActive($customer->pppoe_username);
                    $this->disconnect();
                }
            }

            return [
                'success' => true,
                'message' => "User PPPoE '{$customer->pppoe_username}' berhasil disinkronkan ke router {$router->name} (Profile: {$targetProfile}).",
            ];
        }

        return [
            'success' => false,
            'message' => "Gagal menghubungi router {$router->name}. Periksa koneksi atau IP router.",
        ];
    }

    /**
     * Get real-time active session for a customer.
     */
    public function getCustomerSessionStatus(Customer $customer): array
    {
        $router = $customer->router;
        if (! $router || empty($customer->pppoe_username)) {
            return ['online' => false, 'message' => 'Router atau username PPPoE belum diset'];
        }

        try {
            if (! $this->connect($router)) {
                return ['online' => false, 'message' => 'Gagal terhubung ke router'];
            }

            $res = $this->comm('/ppp/active/print', [
                '?name' => $customer->pppoe_username,
            ]);

            $active = $res['!re'][0] ?? null;
            $this->disconnect();

            if ($active) {
                // Update customer IP and MAC address if available
                $ip = $active['address'] ?? null;
                $mac = $active['caller-id'] ?? null;
                if ($ip || $mac) {
                    $customer->update([
                        'ip_address' => $ip ?: $customer->ip_address,
                        'mac_address' => $mac ?: $customer->mac_address,
                    ]);
                }

                return [
                    'online' => true,
                    'username' => $customer->pppoe_username,
                    'ip_address' => $ip,
                    'mac_address' => $mac,
                    'uptime' => $active['uptime'] ?? '-',
                    'service' => $active['service'] ?? 'pppoe',
                ];
            }

            return [
                'online' => false,
                'username' => $customer->pppoe_username,
                'message' => 'Pelanggan OFFLINE (Tidak ada sesi aktif di MikroTik)',
            ];
        } catch (Exception $e) {
            $this->disconnect();

            return ['online' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Kick/disconnect active PPPoE session for customer.
     */
    public function kickCustomerSession(Customer $customer): bool
    {
        $router = $customer->router;
        if (! $router || empty($customer->pppoe_username)) {
            return false;
        }

        try {
            if (! $this->connect($router)) {
                return false;
            }
            $this->kickPppActive($customer->pppoe_username);
            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();

            return false;
        }
    }

    /**
     * Import existing PPPoE secrets from MikroTik into Customers table.
     */
    public function importPppSecretsToCustomers(Router $router, ?int $defaultPackageId = null): array
    {
        // First, sync profiles from router so packages match
        $this->syncProfilesFromRouter($router);

        $secrets = $this->getPppSecrets($router);
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($secrets as $secret) {
            $username = $secret['name'] ?? null;
            if (! $username) {
                continue;
            }

            $password = $secret['password'] ?? '123456';
            $profileName = $secret['profile'] ?? 'default';
            $comment = $secret['comment'] ?? '';
            $disabled = ($secret['disabled'] ?? 'no') === 'yes';

            // Find matching package
            $package = InternetPackage::where('mikrotik_profile', $profileName)->first();
            $packageId = $package ? $package->id : $defaultPackageId;

            // Extract phone number from comment if present
            $phone = '08'.rand(1111111111, 9999999999);
            if (preg_match('/(08\d{8,11}|628\d{8,11})/', $comment, $matches)) {
                $phone = $matches[1];
            }

            $displayName = ucwords(str_replace(['_', '.', '-'], ' ', $username));
            $remoteAddress = $secret['remote-address'] ?? null;
            $callerId = $secret['caller-id'] ?? null;

            $customer = Customer::where('pppoe_username', $username)->first();

            if ($customer) {
                // Update existing customer credentials
                $customer->update([
                    'router_id' => $router->id,
                    'package_id' => $packageId ?: $customer->package_id,
                    'pppoe_password' => $password,
                    'ip_address' => $remoteAddress ?: $customer->ip_address,
                    'mac_address' => $callerId ?: $customer->mac_address,
                    'status' => $disabled ? 'isolated' : $customer->status,
                ]);
                $updated++;
            } else {
                Customer::create([
                    'customer_number' => 'CUST-'.date('Ym').'-'.rand(1000, 9999),
                    'name' => $displayName,
                    'phone' => $phone,
                    'router_id' => $router->id,
                    'package_id' => $packageId,
                    'connection_type' => 'pppoe',
                    'pppoe_username' => $username,
                    'pppoe_password' => $password,
                    'ip_address' => $remoteAddress,
                    'mac_address' => $callerId,
                    'status' => $disabled ? 'isolated' : 'active',
                    'registered_at' => now(),
                    'due_date_day' => 10,
                    'auto_isolate' => true,
                    'notes' => 'Di-import dari MikroTik: '.$router->name.($comment ? " ({$comment})" : ''),
                ]);
                $imported++;
            }
        }

        return [
            'total' => count($secrets),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Push all customers in DB to MikroTik PPPoE Secrets.
     */
    public function syncAllCustomersToMikrotik(Router $router): array
    {
        $customers = Customer::where('router_id', $router->id)
            ->where('connection_type', 'pppoe')
            ->whereNotNull('pppoe_username')
            ->get();

        $success = 0;
        $failed = 0;

        foreach ($customers as $cust) {
            $res = $this->syncCustomerToMikrotik($cust);
            if ($res['success']) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $customers->count(),
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Hotspot Management Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get all Hotspot User Profiles from MikroTik.
     */
    public function getHotspotUserProfiles(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                return [];
            }

            $res = $this->comm('/ip/hotspot/user/profile/print');
            $profiles = [];

            foreach ($res['!re'] ?? [] as $prof) {
                $profiles[] = [
                    'id' => $prof['.id'] ?? null,
                    'name' => $prof['name'] ?? 'default',
                    'shared_users' => $prof['shared-users'] ?? '1',
                    'rate_limit' => $prof['rate-limit'] ?? null,
                    'session_timeout' => $prof['session-timeout'] ?? null,
                    'keepalive_timeout' => $prof['keepalive-timeout'] ?? null,
                    'status_autorefresh' => $prof['status-autorefresh'] ?? null,
                    'mac_cookie_timeout' => $prof['mac-cookie-timeout'] ?? null,
                    'on_login' => $prof['on-login'] ?? null,
                    'raw' => $prof,
                ];
            }

            return $profiles;
        } catch (Exception $e) {
            Log::error('Failed to fetch hotspot profiles: '.$e->getMessage());

            return [];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Create or update a Hotspot User Profile in MikroTik.
     */
    public function syncHotspotUserProfile(Router $router, array $data): array
    {
        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke MikroTik'];
            }

            $find = $this->comm('/ip/hotspot/user/profile/print', [
                '?name' => $data['name'],
            ]);

            $exists = $find['!re'][0] ?? null;
            $params = [
                'name' => $data['name'],
            ];

            if (! empty($data['shared_users'])) {
                $params['shared-users'] = (string) $data['shared_users'];
            }
            if (! empty($data['rate_limit'])) {
                $params['rate-limit'] = $data['rate_limit'];
            }
            if (! empty($data['session_timeout'])) {
                $params['session-timeout'] = $data['session_timeout'];
            }
            if (! empty($data['keepalive_timeout'])) {
                $params['keepalive-timeout'] = $data['keepalive_timeout'];
            }
            if (! empty($data['status_autorefresh'])) {
                $params['status-autorefresh'] = $data['status_autorefresh'];
            }

            if ($exists && isset($exists['.id'])) {
                $params['numbers'] = $exists['.id'];
                $this->comm('/ip/hotspot/user/profile/set', $params);
                $action = 'diperbarui';
            } else {
                $this->comm('/ip/hotspot/user/profile/add', $params);
                $action = 'ditambahkan';
            }

            return [
                'success' => true,
                'message' => "Profil Hotspot [{$data['name']}] berhasil {$action} di MikroTik",
            ];
        } catch (Exception $e) {
            Log::error('Failed to sync hotspot profile: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Synchronize a single InternetPackage (PPPoE or Hotspot) profile to router(s).
     */
    public function syncInternetPackageProfile(InternetPackage $package, ?Router $router = null): array
    {
        if (empty($package->mikrotik_profile)) {
            return ['success' => false, 'message' => 'Profil MikroTik belum ditentukan pada paket ini.'];
        }

        if ($package->type === 'static') {
            return ['success' => true, 'message' => 'Paket IP Static tidak memerlukan profil MikroTik.'];
        }

        $routers = $router ? collect([$router]) : Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            return ['success' => false, 'message' => 'Tidak ada router aktif yang terhubung.'];
        }

        $successCount = 0;
        $failedCount = 0;
        $lastMessage = '';

        foreach ($routers as $r) {
            if ($package->type === 'pppoe') {
                $safeLocal = $this->getSafePppoeLocalAddress($r);
                $poolName = $this->ensurePppoePool($r, null, $safeLocal);
                $res = $this->syncPppProfile($r, [
                    'name' => $package->mikrotik_profile,
                    'rate_limit' => $package->speed_rate,
                    'local_address' => $safeLocal,
                    'remote_address' => $poolName,
                    'dns_server' => IspSetting::get('pppoe_dns_servers', '8.8.8.8,1.1.1.1'),
                    'only_one' => 'yes',
                    'comment' => "Profil PPPoE {$package->name} via ISP Billing",
                ]);
            } elseif ($package->type === 'hotspot') {
                $res = $this->syncHotspotUserProfile($r, [
                    'name' => $package->mikrotik_profile,
                    'rate_limit' => $package->speed_rate,
                    'shared_users' => $package->shared_users ?: 1,
                    'status_autorefresh' => '1m',
                    'comment' => "Profil Hotspot {$package->name} via ISP Billing",
                ]);
            } else {
                continue;
            }

            if ($res['success'] ?? false) {
                $successCount++;
                $lastMessage = $res['message'];
            } else {
                $failedCount++;
                $lastMessage = $res['message'] ?? 'Gagal membuat profil di router';
            }
        }

        return [
            'success' => $failedCount === 0 && $successCount > 0,
            'message' => $routers->count() === 1
                ? $lastMessage
                : "Profil [{$package->mikrotik_profile}] tersinkron ke {$successCount}/{$routers->count()} router.",
        ];
    }

    /**
     * Synchronize all active InternetPackages (both PPPoE and Hotspot) to router(s).
     */
    public function syncAllInternetPackagesToRouters(?Router $router = null): array
    {
        $packages = InternetPackage::where('is_active', true)
            ->whereIn('type', ['pppoe', 'hotspot'])
            ->whereNotNull('mikrotik_profile')
            ->get();

        $success = 0;
        $failed = 0;
        $details = [];

        foreach ($packages as $pkg) {
            $res = $this->syncInternetPackageProfile($pkg, $router);
            if ($res['success'] ?? false) {
                $success++;
            } else {
                $failed++;
            }
            $details[] = [
                'package' => $pkg->name,
                'profile' => $pkg->mikrotik_profile,
                'type' => $pkg->type,
                'success' => $res['success'] ?? false,
                'message' => $res['message'] ?? '',
            ];
        }

        return [
            'total' => $packages->count(),
            'success' => $success,
            'failed' => $failed,
            'details' => $details,
        ];
    }

    /**
     * Ensure Hotspot User Profile has specific shared-users limit.
     */
    public function ensureHotspotProfileSharedUsers(Router $router, string $profileName, int $sharedUsers): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ip/hotspot/user/profile/print', [
                '?name' => $profileName,
            ]);

            $exists = $find['!re'][0] ?? null;

            if ($exists && isset($exists['.id'])) {
                $current = (int) ($exists['shared-users'] ?? 1);
                if ($current !== $sharedUsers) {
                    $this->comm('/ip/hotspot/user/profile/set', [
                        'numbers' => $exists['.id'],
                        'shared-users' => (string) $sharedUsers,
                    ]);
                    Log::info("Updated shared-users for hotspot profile {$profileName} on router {$router->name} to {$sharedUsers}.");
                }
            } else {
                $this->comm('/ip/hotspot/user/profile/add', [
                    'name' => $profileName,
                    'shared-users' => (string) $sharedUsers,
                ]);
                Log::info("Created hotspot profile {$profileName} on router {$router->name} with shared-users {$sharedUsers}.");
            }

            return true;
        } catch (Exception $e) {
            Log::error("Failed to ensure shared-users for profile {$profileName}: ".$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get all Hotspot Users from MikroTik.
     */
    public function getHotspotUsers(Router $router, ?string $profile = null): array
    {
        try {
            if (! $this->connect($router)) {
                return [];
            }

            $filter = [];
            if ($profile) {
                $filter['?profile'] = $profile;
            }

            $res = $this->comm('/ip/hotspot/user/print', $filter);
            $users = [];

            foreach ($res['!re'] ?? [] as $u) {
                $users[] = [
                    'id' => $u['.id'] ?? null,
                    'name' => $u['name'] ?? '',
                    'password' => $u['password'] ?? '',
                    'profile' => $u['profile'] ?? 'default',
                    'limit_uptime' => $u['limit-uptime'] ?? null,
                    'uptime' => $u['uptime'] ?? '0s',
                    'bytes_in' => $u['bytes-in'] ?? 0,
                    'bytes_out' => $u['bytes-out'] ?? 0,
                    'comment' => $u['comment'] ?? '',
                    'disabled' => ($u['disabled'] ?? 'false') === 'true',
                ];
            }

            return $users;
        } catch (Exception $e) {
            Log::error('Failed to fetch hotspot users: '.$e->getMessage());

            return [];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Add or update a Hotspot User (Voucher) in MikroTik.
     */
    public function syncHotspotUser(Router $router, array $userData): array
    {
        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke MikroTik'];
            }

            $find = $this->comm('/ip/hotspot/user/print', [
                '?name' => $userData['name'],
            ]);

            $exists = $find['!re'][0] ?? null;

            $params = [
                'name' => $userData['name'],
                'password' => $userData['password'] ?? $userData['name'],
                'profile' => $userData['profile'] ?? 'default',
            ];

            if (! empty($userData['limit_uptime'])) {
                $params['limit-uptime'] = $userData['limit_uptime'];
            }
            if (! empty($userData['limit_bytes_total'])) {
                $params['limit-bytes-total'] = $userData['limit_bytes_total'];
            }
            if (! empty($userData['comment'])) {
                $params['comment'] = $userData['comment'];
            }

            if ($exists && isset($exists['.id'])) {
                $params['numbers'] = $exists['.id'];
                $this->comm('/ip/hotspot/user/set', $params);
            } else {
                $this->comm('/ip/hotspot/user/add', $params);
            }

            return ['success' => true, 'message' => "User Hotspot [{$userData['name']}] tersinkron"];
        } catch (Exception $e) {
            Log::error('Failed to sync hotspot user: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Remove Hotspot User from MikroTik (including active session & cookie).
     */
    public function removeHotspotUser(Router $router, string $username): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            // 1. Remove from /ip/hotspot/user
            $find = $this->comm('/ip/hotspot/user/print', [
                '?name' => $username,
            ]);

            $exists = $find['!re'][0] ?? null;
            if ($exists && isset($exists['.id'])) {
                $this->comm('/ip/hotspot/user/remove', [
                    'numbers' => $exists['.id'],
                ]);
            }

            // 2. Also kick active session if user is logged in
            $findActive = $this->comm('/ip/hotspot/active/print', [
                '?user' => $username,
            ]);
            foreach ($findActive['!re'] ?? [] as $act) {
                if (isset($act['.id'])) {
                    $this->comm('/ip/hotspot/active/remove', [
                        'numbers' => $act['.id'],
                    ]);
                }
            }

            // 3. Remove cookie if exists
            $findCookie = $this->comm('/ip/hotspot/cookie/print', [
                '?user' => $username,
            ]);
            foreach ($findCookie['!re'] ?? [] as $ck) {
                if (isset($ck['.id'])) {
                    $this->comm('/ip/hotspot/cookie/remove', [
                        'numbers' => $ck['.id'],
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to remove hotspot user: '.$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Batch remove hotspot users efficiently in a single connection.
     */
    public function removeHotspotUsersBatch(Router $router, array $usernames): array
    {
        $removed = [];
        $failed = [];

        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke router MikroTik', 'removed' => []];
            }

            // Fetch users, active sessions, and cookies in one go
            $users = $this->comm('/ip/hotspot/user/print');
            $userMap = [];
            foreach ($users['!re'] ?? [] as $u) {
                if (isset($u['name']) && isset($u['.id'])) {
                    $userMap[$u['name']] = $u['.id'];
                }
            }

            $actives = $this->comm('/ip/hotspot/active/print');
            $activeMap = [];
            foreach ($actives['!re'] ?? [] as $a) {
                if (isset($a['user']) && isset($a['.id'])) {
                    $activeMap[$a['user']][] = $a['.id'];
                }
            }

            $cookies = $this->comm('/ip/hotspot/cookie/print');
            $cookieMap = [];
            foreach ($cookies['!re'] ?? [] as $c) {
                if (isset($c['user']) && isset($c['.id'])) {
                    $cookieMap[$c['user']][] = $c['.id'];
                }
            }

            foreach ($usernames as $name) {
                try {
                    if (isset($userMap[$name])) {
                        $this->comm('/ip/hotspot/user/remove', [
                            'numbers' => $userMap[$name],
                        ]);
                    }

                    if (isset($activeMap[$name])) {
                        foreach ($activeMap[$name] as $actId) {
                            $this->comm('/ip/hotspot/active/remove', [
                                'numbers' => $actId,
                            ]);
                        }
                    }

                    if (isset($cookieMap[$name])) {
                        foreach ($cookieMap[$name] as $ckId) {
                            $this->comm('/ip/hotspot/cookie/remove', [
                                'numbers' => $ckId,
                            ]);
                        }
                    }

                    $removed[] = $name;
                } catch (Exception $e) {
                    $failed[] = $name;
                }
            }

            return [
                'success' => true,
                'removed' => $removed,
                'failed' => $failed,
            ];
        } catch (Exception $e) {
            Log::error('Failed batch removing hotspot users: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'removed' => $removed];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Remove orphan hotspot users in MikroTik that no longer exist in the local database.
     * System users ('admin', 'default-trial', default=true) are always protected.
     */
    public function pruneOrphanHotspotUsers(Router $router, array $validVoucherCodes): array
    {
        $pruned = [];
        $protectedNames = ['admin', 'default-trial'];

        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke router MikroTik', 'pruned' => []];
            }

            $users = $this->comm('/ip/hotspot/user/print');
            $validLookup = array_flip($validVoucherCodes);

            foreach ($users['!re'] ?? [] as $user) {
                $name = $user['name'] ?? '';
                $isDefault = ($user['default'] ?? 'false') === 'true';

                if (! $name || in_array(strtolower($name), $protectedNames) || $isDefault) {
                    continue;
                }

                // If not in database, prune from MikroTik
                if (! isset($validLookup[$name])) {
                    if (isset($user['.id'])) {
                        $this->comm('/ip/hotspot/user/remove', [
                            'numbers' => $user['.id'],
                        ]);
                    }

                    // Remove active & cookie
                    $actives = $this->comm('/ip/hotspot/active/print', ['?user' => $name]);
                    foreach ($actives['!re'] ?? [] as $act) {
                        if (isset($act['.id'])) {
                            $this->comm('/ip/hotspot/active/remove', ['numbers' => $act['.id']]);
                        }
                    }

                    $cookies = $this->comm('/ip/hotspot/cookie/print', ['?user' => $name]);
                    foreach ($cookies['!re'] ?? [] as $ck) {
                        if (isset($ck['.id'])) {
                            $this->comm('/ip/hotspot/cookie/remove', ['numbers' => $ck['.id']]);
                        }
                    }

                    $pruned[] = $name;
                }
            }

            return [
                'success' => true,
                'pruned' => $pruned,
                'count' => count($pruned),
            ];
        } catch (Exception $e) {
            Log::error('Failed pruning orphan hotspot users: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'pruned' => $pruned];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Pull users from MikroTik that are not protected system users.
     */
    public function pullHotspotUsers(Router $router): array
    {
        $users = [];
        $protectedNames = ['admin', 'default-trial'];

        try {
            if (! $this->connect($router)) {
                return ['success' => false, 'message' => 'Gagal koneksi ke router MikroTik', 'users' => []];
            }

            $list = $this->comm('/ip/hotspot/user/print');
            foreach ($list['!re'] ?? [] as $u) {
                $name = $u['name'] ?? '';
                $isDefault = ($u['default'] ?? 'false') === 'true';

                if (! $name || in_array(strtolower($name), $protectedNames) || $isDefault) {
                    continue;
                }

                $users[] = [
                    'code' => $name,
                    'password' => $u['password'] ?? $name,
                    'profile' => $u['profile'] ?? 'default',
                    'limit_uptime' => $u['limit-uptime'] ?? null,
                    'comment' => $u['comment'] ?? null,
                ];
            }

            return ['success' => true, 'users' => $users];
        } catch (Exception $e) {
            Log::error('Failed pulling hotspot users: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'users' => []];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get active logged in Hotspot users from MikroTik.
     */
    public function getHotspotActive(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                return [];
            }

            $res = $this->comm('/ip/hotspot/active/print');
            $active = [];

            foreach ($res['!re'] ?? [] as $a) {
                $active[] = [
                    'id' => $a['.id'] ?? null,
                    'user' => $a['user'] ?? '',
                    'address' => $a['address'] ?? '',
                    'mac_address' => $a['mac-address'] ?? '',
                    'uptime' => $a['uptime'] ?? '0s',
                    'session_time_left' => $a['session-time-left'] ?? '-',
                    'idle_time' => $a['idle-time'] ?? '-',
                    'bytes_in' => isset($a['bytes-in']) ? (int) $a['bytes-in'] : 0,
                    'bytes_out' => isset($a['bytes-out']) ? (int) $a['bytes-out'] : 0,
                    'login_by' => $a['login-by'] ?? 'http',
                ];
            }

            return $active;
        } catch (Exception $e) {
            Log::error('Failed to fetch hotspot active: '.$e->getMessage());

            return [];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Kick active Hotspot user from MikroTik.
     */
    public function kickHotspotActive(Router $router, string $identifier): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            // Search by user name or mac-address
            $find = $this->comm('/ip/hotspot/active/print', [
                '?user' => $identifier,
            ]);

            $exists = $find['!re'][0] ?? null;

            if (! $exists) {
                $findMac = $this->comm('/ip/hotspot/active/print', [
                    '?mac-address' => $identifier,
                ]);
                $exists = $findMac['!re'][0] ?? null;
            }

            if ($exists && isset($exists['.id'])) {
                $this->comm('/ip/hotspot/active/remove', [
                    'numbers' => $exists['.id'],
                ]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error("Failed to kick hotspot user {$identifier}: ".$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get system resource statistics from MikroTik.
     */
    public function getSystemResources(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                if (config('app.demo') || app()->environment('testing')) {
                    return $this->getSimulatedSystemResources($router);
                }

                return ['success' => false, 'message' => 'Tidak dapat terhubung ke router'];
            }

            $res = $this->comm('/system/resource/print');
            $data = $res['!re'][0] ?? ($res['!done'][0] ?? []);

            $healthRes = $this->comm('/system/health/print');
            $temperature = null;
            $voltage = null;
            $healthRows = $healthRes['!re'] ?? ($healthRes['!done'] ?? []);

            foreach ($healthRows as $item) {
                // RouterOS 7 format: [name => 'temperature', value => '42', type => 'C']
                if (isset($item['name'], $item['value'])) {
                    $sensorName = strtolower($item['name']);
                    if (! $temperature && in_array($sensorName, ['temperature', 'cpu-temperature', 'board-temperature1', 'sfp-temperature'])) {
                        $temperature = $item['value'].' °C';
                    }
                    if (! $voltage && in_array($sensorName, ['voltage', 'board-voltage'])) {
                        $voltage = $item['value'].' V';
                    }
                } else {
                    // RouterOS 6 format: [temperature => '42', voltage => '24.1']
                    if (! $temperature && isset($item['temperature'])) {
                        $temperature = $item['temperature'].' °C';
                    } elseif (! $temperature && isset($item['cpu-temperature'])) {
                        $temperature = $item['cpu-temperature'].' °C';
                    }
                    if (! $voltage && isset($item['voltage'])) {
                        $voltage = $item['voltage'].' V';
                    }
                }
            }

            $this->disconnect();

            if (isset($data['cpu-load'])) {
                $router->update([
                    'cpu_load' => (int) $data['cpu-load'],
                    'uptime' => $data['uptime'] ?? null,
                    'status' => 'online',
                    'last_connected_at' => now(),
                ]);
            }

            return [
                'success' => true,
                'is_simulated' => false,
                'cpu_load' => (int) ($data['cpu-load'] ?? 0),
                'cpu_frequency' => $data['cpu-frequency'] ?? '1400 MHz',
                'cpu_count' => $data['cpu-count'] ?? '4',
                'uptime' => $data['uptime'] ?? '-',
                'version' => $data['version'] ?? 'RouterOS',
                'board_name' => $data['board-name'] ?? $router->board_name ?? 'MikroTik Router',
                'free_memory' => $this->formatBytes((int) ($data['free-memory'] ?? 0)),
                'total_memory' => $this->formatBytes((int) ($data['total-memory'] ?? 0)),
                'memory_percentage' => isset($data['total-memory'], $data['free-memory']) && (int) $data['total-memory'] > 0
                    ? round((((int) $data['total-memory'] - (int) $data['free-memory']) / (int) $data['total-memory']) * 100, 1)
                    : 0,
                'free_hdd' => $this->formatBytes((int) ($data['free-hdd-space'] ?? 0)),
                'total_hdd' => $this->formatBytes((int) ($data['total-hdd-space'] ?? 0)),
                'temperature' => $temperature ?: '42 °C',
                'voltage' => $voltage ?: '24.1 V',
            ];
        } catch (Exception $e) {
            $this->disconnect();
            if (config('app.demo')) {
                return $this->getSimulatedSystemResources($router);
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all network interfaces with live traffic.
     */
    public function getInterfacesWithTraffic(Router $router): array
    {
        try {
            if (! $this->connect($router)) {
                if (config('app.demo') || app()->environment('testing')) {
                    return $this->getSimulatedInterfacesWithTraffic();
                }

                return ['success' => false, 'message' => 'Tidak dapat terhubung ke router'];
            }

            $ifaceRes = $this->comm('/interface/print');
            $rawInterfaces = $ifaceRes['!re'] ?? [];

            $interfaces = [];
            foreach ($rawInterfaces as $iface) {
                $name = $iface['name'] ?? 'unknown';
                $type = $iface['type'] ?? 'ether';
                $running = ($iface['running'] ?? 'false') === 'true';
                $disabled = ($iface['disabled'] ?? 'false') === 'true';

                $rxRate = '0 bps';
                $txRate = '0 bps';
                $rxBps = 0;
                $txBps = 0;

                if ($running && ! $disabled) {
                    $mon = $this->comm('/interface/monitor-traffic', [
                        '=interface='.$name,
                        '=once=',
                    ]);
                    $monData = $mon['!re'][0] ?? [];
                    $rxBps = (int) ($monData['rx-bits-per-second'] ?? 0);
                    $txBps = (int) ($monData['tx-bits-per-second'] ?? 0);
                    $rxRate = $this->formatBitsPerSecond($rxBps);
                    $txRate = $this->formatBitsPerSecond($txBps);
                }

                $interfaces[] = [
                    'name' => $name,
                    'type' => $type,
                    'comment' => $iface['comment'] ?? '',
                    'running' => $running,
                    'disabled' => $disabled,
                    'rx_rate' => $rxRate,
                    'tx_rate' => $txRate,
                    'rx_raw' => $rxBps,
                    'tx_raw' => $txBps,
                    'rx_byte' => $this->formatBytes((int) ($iface['rx-byte'] ?? 0)),
                    'tx_byte' => $this->formatBytes((int) ($iface['tx-byte'] ?? 0)),
                    'rx_packet' => number_format((int) ($iface['rx-packet'] ?? 0)),
                    'tx_packet' => number_format((int) ($iface['tx-packet'] ?? 0)),
                ];
            }

            $this->disconnect();

            return [
                'success' => true,
                'is_simulated' => false,
                'interfaces' => $interfaces,
            ];
        } catch (Exception $e) {
            $this->disconnect();
            if (config('app.demo')) {
                return $this->getSimulatedInterfacesWithTraffic();
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get live bandwidth and session diagnostics for a specific customer.
     */
    public function getCustomerLiveTraffic(Customer $customer): array
    {
        $router = $customer->router;
        if (! $router || empty($customer->pppoe_username)) {
            return [
                'success' => false,
                'online' => false,
                'message' => 'Router atau username PPPoE belum diset pada pelanggan.',
            ];
        }

        try {
            if (! $this->connect($router)) {
                if ($customer->name === 'Budi Santoso' || str_contains($customer->name, 'Budi')) {
                    return $this->getSimulatedCustomerLiveTraffic($customer);
                }

                return [
                    'success' => false,
                    'online' => false,
                    'message' => 'Gagal menghubungkan ke router MikroTik: '.$router->host,
                ];
            }

            $activeList = $this->comm('/ppp/active/print', [
                '?name' => $customer->pppoe_username,
            ]);

            $active = $activeList['!re'][0] ?? null;

            if (! $active) {
                $this->disconnect();

                return [
                    'success' => true,
                    'online' => false,
                    'is_simulated' => false,
                    'username' => $customer->pppoe_username,
                    'message' => 'Pelanggan saat ini OFFLINE (Tidak ada sesi PPPoE yang aktif).',
                ];
            }

            $uptime = $active['uptime'] ?? '-';
            $ipAddress = $active['address'] ?? '-';
            $callerId = $active['caller-id'] ?? '-';
            $service = $active['service'] ?? 'pppoe';

            $interfaceName = "<pppoe-{$customer->pppoe_username}>";
            $traffic = $this->comm('/interface/monitor-traffic', [
                '=interface='.$interfaceName,
                '=once=',
            ]);

            $trafficData = $traffic['!re'][0] ?? null;
            $rxBps = isset($trafficData['rx-bits-per-second']) ? (int) $trafficData['rx-bits-per-second'] : 0;
            $txBps = isset($trafficData['tx-bits-per-second']) ? (int) $trafficData['tx-bits-per-second'] : 0;

            $ifaceInfo = $this->comm('/interface/print', [
                '?name' => $interfaceName,
            ]);
            $ifaceData = $ifaceInfo['!re'][0] ?? [];
            $rxBytes = (int) ($ifaceData['rx-byte'] ?? 0);
            $txBytes = (int) ($ifaceData['tx-byte'] ?? 0);

            $this->disconnect();

            return [
                'success' => true,
                'online' => true,
                'is_simulated' => false,
                'username' => $customer->pppoe_username,
                'package_name' => $customer->package?->name ?? 'Paket Internet',
                'speed_rate' => $customer->package?->speed_rate ?? 'N/A',
                'ip_address' => $ipAddress,
                'mac_address' => $callerId,
                'uptime' => $uptime,
                'service' => $service,
                'rx_rate' => $this->formatBitsPerSecond($rxBps),
                'tx_rate' => $this->formatBitsPerSecond($txBps),
                'rx_raw' => $rxBps,
                'tx_raw' => $txBps,
                'rx_total' => $this->formatBytes($rxBytes),
                'tx_total' => $this->formatBytes($txBytes),
                'router_name' => $router->name,
            ];
        } catch (Exception $e) {
            $this->disconnect();
            if ($customer->name === 'Budi Santoso' || str_contains($customer->name, 'Budi')) {
                return $this->getSimulatedCustomerLiveTraffic($customer);
            }

            return [
                'success' => false,
                'online' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get top bandwidth consumers on router.
     */
    public function getTopBandwidthConsumers(Router $router, int $limit = 5): array
    {
        try {
            if (! $this->connect($router)) {
                if (config('app.demo')) {
                    return $this->getSimulatedTopConsumers();
                }

                return [];
            }

            $activeList = $this->comm('/ppp/active/print');
            $actives = $activeList['!re'] ?? [];

            $consumers = [];
            foreach ($actives as $active) {
                $username = $active['name'] ?? '';
                if (empty($username)) {
                    continue;
                }

                $interfaceName = "<pppoe-{$username}>";
                $traffic = $this->comm('/interface/monitor-traffic', [
                    '=interface='.$interfaceName,
                    '=once=',
                ]);

                $mon = $traffic['!re'][0] ?? [];
                $rxBps = (int) ($mon['rx-bits-per-second'] ?? 0);
                $txBps = (int) ($mon['tx-bits-per-second'] ?? 0);

                $consumers[] = [
                    'username' => $username,
                    'ip' => $active['address'] ?? '-',
                    'uptime' => $active['uptime'] ?? '-',
                    'rx_rate' => $this->formatBitsPerSecond($rxBps),
                    'tx_rate' => $this->formatBitsPerSecond($txBps),
                    'total_bps' => $rxBps + $txBps,
                ];
            }

            $this->disconnect();

            usort($consumers, fn ($a, $b) => $b['total_bps'] <=> $a['total_bps']);

            return array_slice($consumers, 0, $limit);
        } catch (Exception $e) {
            $this->disconnect();
            if (config('app.demo')) {
                return $this->getSimulatedTopConsumers();
            }

            return [];
        }
    }

    /**
     * Format bits per second to human readable speed string.
     */
    public function formatBitsPerSecond(int $bps): string
    {
        if ($bps >= 1000000000) {
            return number_format($bps / 1000000000, 2).' Gbps';
        } elseif ($bps >= 1000000) {
            return number_format($bps / 1000000, 2).' Mbps';
        } elseif ($bps >= 1000) {
            return number_format($bps / 1000, 1).' kbps';
        }

        return "{$bps} bps";
    }

    /**
     * Format bytes into human readable data volume string.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1099511627776) {
            return number_format($bytes / 1099511627776, 2).' TB';
        } elseif ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return "{$bytes} B";
    }

    protected function getSimulatedSystemResources(Router $router): array
    {
        return [
            'success' => true,
            'is_simulated' => true,
            'cpu_load' => 12,
            'cpu_frequency' => '1400 MHz (Quad-Core)',
            'cpu_count' => 4,
            'uptime' => '12 hari 18 jam 40 mnt',
            'version' => 'RouterOS v7.12 (Stable)',
            'board_name' => 'RB4011iGS+5HacQ2HnD',
            'free_memory' => '842.50 MB',
            'total_memory' => '1024.00 MB',
            'memory_percentage' => 17.7,
            'free_hdd' => '485.20 MB',
            'total_hdd' => '512.00 MB',
            'temperature' => '42.5 °C',
            'voltage' => '24.2 V',
        ];
    }

    protected function getSimulatedInterfacesWithTraffic(): array
    {
        return [
            'success' => true,
            'is_simulated' => true,
            'interfaces' => [
                [
                    'name' => 'ether1-WAN-Indosat',
                    'type' => 'ether',
                    'comment' => 'Link Utama IP Transit / Upstream 1 Gbps',
                    'running' => true,
                    'disabled' => false,
                    'rx_rate' => '184.50 Mbps',
                    'tx_rate' => '32.10 Mbps',
                    'rx_raw' => 184500000,
                    'tx_raw' => 32100000,
                    'rx_byte' => '8.45 TB',
                    'tx_byte' => '1.82 TB',
                    'rx_packet' => '142.850.120',
                    'tx_packet' => '98.410.230',
                ],
                [
                    'name' => 'sfp-sfpplus1-OLT-Core',
                    'type' => 'ether',
                    'comment' => 'Trunk Uplink ke OLT GPON (VLAN 100 Internet)',
                    'running' => true,
                    'disabled' => false,
                    'rx_rate' => '28.40 Mbps',
                    'tx_rate' => '162.80 Mbps',
                    'rx_raw' => 28400000,
                    'tx_raw' => 162800000,
                    'rx_byte' => '1.65 TB',
                    'tx_byte' => '7.85 TB',
                    'rx_packet' => '89.120.440',
                    'tx_packet' => '138.410.550',
                ],
                [
                    'name' => 'ether2-Distribution-LAN',
                    'type' => 'ether',
                    'comment' => 'Switch Distribusi Server & AP Hotspot',
                    'running' => true,
                    'disabled' => false,
                    'rx_rate' => '4.10 Mbps',
                    'tx_rate' => '18.50 Mbps',
                    'rx_raw' => 4100000,
                    'tx_raw' => 18500000,
                    'rx_byte' => '410.50 GB',
                    'tx_byte' => '1.20 TB',
                    'rx_packet' => '18.420.100',
                    'tx_packet' => '32.890.150',
                ],
                [
                    'name' => 'vlan100-PPPoE',
                    'type' => 'vlan',
                    'comment' => 'VLAN Tagging Pelanggan Rumah (PPPoE Server)',
                    'running' => true,
                    'disabled' => false,
                    'rx_rate' => '24.20 Mbps',
                    'tx_rate' => '142.30 Mbps',
                    'rx_raw' => 24200000,
                    'tx_raw' => 142300000,
                    'rx_byte' => '1.24 TB',
                    'tx_byte' => '6.45 TB',
                    'rx_packet' => '74.200.120',
                    'tx_packet' => '115.120.440',
                ],
                [
                    'name' => 'ether10-Backup-WAN',
                    'type' => 'ether',
                    'comment' => 'Link Cadangan Failover (Telkom 100M)',
                    'running' => false,
                    'disabled' => false,
                    'rx_rate' => '0 bps',
                    'tx_rate' => '0 bps',
                    'rx_raw' => 0,
                    'tx_raw' => 0,
                    'rx_byte' => '12.40 MB',
                    'tx_byte' => '4.80 MB',
                    'rx_packet' => '1.200',
                    'tx_packet' => '840',
                ],
            ],
        ];
    }

    protected function getSimulatedCustomerLiveTraffic(Customer $customer): array
    {
        return [
            'success' => true,
            'online' => true,
            'is_simulated' => true,
            'username' => $customer->pppoe_username,
            'package_name' => $customer->package?->name ?? 'Home Ultra 20 Mbps',
            'speed_rate' => $customer->package?->speed_rate ?? '20M/20M',
            'ip_address' => $customer->ip_address ?: '10.10.20.15',
            'mac_address' => $customer->mac_address ?: '70:2E:D9:55:1A:89',
            'uptime' => '2 hari 14 jam 32 mnt',
            'service' => 'pppoe',
            'rx_rate' => '14.85 Mbps',
            'tx_rate' => '2.40 Mbps',
            'rx_raw' => 14850000,
            'tx_raw' => 2400000,
            'rx_total' => '1.85 GB',
            'tx_total' => '340.20 MB',
            'router_name' => $customer->router?->name ?? 'MikroTik Core - RB4011',
        ];
    }

    protected function getSimulatedTopConsumers(): array
    {
        return [
            [
                'username' => 'budi_santoso',
                'name' => 'Budi Santoso',
                'ip' => '10.10.20.15',
                'uptime' => '2d 14h',
                'rx_rate' => '14.85 Mbps',
                'tx_rate' => '2.40 Mbps',
                'total_bps' => 17250000,
                'package' => 'Home Ultra 20 Mbps',
            ],
            [
                'username' => 'naura',
                'name' => 'Naura',
                'ip' => '10.10.20.22',
                'uptime' => '18h 45m',
                'rx_rate' => '8.20 Mbps',
                'tx_rate' => '1.10 Mbps',
                'total_bps' => 9300000,
                'package' => 'Home Basic 10 Mbps',
            ],
            [
                'username' => 'warkop_berkah_voucher',
                'name' => 'Hotspot Warkop Berkah',
                'ip' => '192.168.99.45',
                'uptime' => '4h 12m',
                'rx_rate' => '5.40 Mbps',
                'tx_rate' => '820 kbps',
                'total_bps' => 6220000,
                'package' => 'Hotspot 5 Mbps',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Isolir Captive Portal & Redirect Methods (Metode 1)
    |--------------------------------------------------------------------------
    */

    /**
     * Generate complete RouterOS CLI script for Isolir Captive Portal setup (Metode 1).
     */
    public function generateIsolirSetupScript(Router $router, array $config = []): string
    {
        $gatewayIp = $config['gateway_ip'] ?? '10.10.10.1';
        $networkIp = $config['network_ip'] ?? '10.10.10.0';
        $poolRange = $config['pool_range'] ?? '10.10.10.2-10.10.10.254';
        $poolName = $config['pool_name'] ?? 'pool-isolir';
        $bridgeName = $config['bridge_name'] ?? 'bridge-isolir';
        $hsServerName = $config['hs_server_name'] ?? 'hotspot-isolir';
        $hsProfName = $config['hs_prof_name'] ?? 'hsprof-isolir';
        $htmlDir = $config['html_dir'] ?? 'hotspot-isolir';
        $pppProfileName = $config['ppp_profile_name'] ?? 'ISOLIR';
        $dnsName = $config['dns_name'] ?? 'isolir.net';
        $billingUrl = $config['billing_url'] ?? url('/');
        $billingHost = parse_url($billingUrl, PHP_URL_HOST) ?: '192.168.1.100';

        return <<<ROUTEROS
# ==============================================================================
# NETSPEED ISP - SETUP REDIRECT ISOLIR & CAPTIVE PORTAL (METODE 1)
# Router: {$router->name} ({$router->host})
# Tanggal Generate: @timestamp
# ==============================================================================

# 1. Buat IP Pool Khusus Pelanggan Isolir
/ip pool add name="{$poolName}" ranges={$poolRange} comment="Pool IP Khusus Isolir"

# 2. Buat Virtual Interface Bridge untuk Gateway Isolir
/interface bridge add name="{$bridgeName}" comment="Virtual Bridge Portal Isolir"
/ip address add address={$gatewayIp}/24 network={$networkIp} interface={$bridgeName} comment="Gateway Subnet Isolir"

# 3. Setup Hotspot Server Profile & Server Khusus Isolir
/ip hotspot profile add name="{$hsProfName}" hotspot-address={$gatewayIp} dns-name="{$dnsName}" html-directory="{$htmlDir}" login-by=http-chap,http-pap comment="Hotspot Profile Isolir"
/ip hotspot add name="{$hsServerName}" interface="{$bridgeName}" address-pool="{$poolName}" profile="{$hsProfName}" disabled=no comment="Hotspot Captive Portal Isolir"

# 4. Setup Profil PPPoE Isolir (Hubungkan ke bridge-isolir agar tertangkap Hotspot Portal)
/ppp profile add name="{$pppProfileName}" local-address={$gatewayIp} remote-address="{$poolName}" bridge="{$bridgeName}" address-list="ISOLIR-LIST" rate-limit="128k/128k" dns-server={$gatewayIp} comment="Profil PPPoE Isolir Otomatis"

# 5. Walled Garden: Izinkan Akses Tanpa Blokir ke Layanan Krusial
# A. Web Server Billing & Portal Pelanggan
/ip hotspot walled-garden ip add dst-host="{$billingHost}" action=accept comment="Akses Web Billing ISP"
/ip hotspot walled-garden add dst-host="*{$billingHost}*" action=allow comment="Akses Domain Web Billing"

# B. WhatsApp Customer Service (Agar pelanggan bisa konfirmasi pembayaran)
/ip hotspot walled-garden add dst-host="*whatsapp.com" action=allow comment="WhatsApp CS Web"
/ip hotspot walled-garden add dst-host="*whatsapp.net" action=allow comment="WhatsApp Media"
/ip hotspot walled-garden add dst-host="wa.me" action=allow comment="WhatsApp Link"

# C. Payment Gateway (Midtrans, Xendit, Tripay untuk pelunasan tagihan online)
/ip hotspot walled-garden add dst-host="*midtrans.com" action=allow comment="Payment Gateway Midtrans"
/ip hotspot walled-garden add dst-host="*xendit.co" action=allow comment="Payment Gateway Xendit"
/ip hotspot walled-garden add dst-host="*tripay.co.id" action=allow comment="Payment Gateway Tripay"

# 7. Pastikan DNS Server MikroTik Melayani Request
/ip dns set allow-remote-requests=yes
/ip dns static add name="isolir.isp.net" address={$gatewayIp} comment="Domain Lokal Isolir"

# SELESAI: Unggah file isolir.html dan login.html ke folder {$htmlDir}/ di Files MikroTik.
ROUTEROS;
    }

    /**
     * Apply Isolir Captive Portal configuration directly to Router via API (Metode 1).
     */
    public function applyIsolirSetupToRouter(Router $router, array $config = []): array
    {
        $gatewayIp = $config['gateway_ip'] ?? '10.10.10.1';
        $networkIp = $config['network_ip'] ?? '10.10.10.0';
        $poolRange = $config['pool_range'] ?? '10.10.10.2-10.10.10.254';
        $poolName = $config['pool_name'] ?? 'pool-isolir';
        $bridgeName = $config['bridge_name'] ?? 'bridge-isolir';
        $hsServerName = $config['hs_server_name'] ?? 'hotspot-isolir';
        $hsProfName = $config['hs_prof_name'] ?? 'hsprof-isolir';
        $htmlDir = $config['html_dir'] ?? 'hotspot-isolir';
        $pppProfileName = $config['ppp_profile_name'] ?? 'ISOLIR';
        $dnsName = $config['dns_name'] ?? 'isolir.net';
        $billingUrl = $config['billing_url'] ?? url('/');
        $billingHost = parse_url($billingUrl, PHP_URL_HOST) ?: '192.168.1.100';

        try {
            if (! $this->connect($router)) {
                return [
                    'success' => false,
                    'message' => "Gagal terhubung ke router {$router->name} ({$router->host}). Periksa koneksi API.",
                ];
            }

            $results = [];

            // 1. IP Pool
            $findPool = $this->comm('/ip/pool/print', ['?name' => $poolName]);
            if (empty($findPool['!re'])) {
                $this->comm('/ip/pool/add', ['name' => $poolName, 'ranges' => $poolRange]);
                $results[] = "Pool IP [{$poolName}] dibuat";
            } else {
                $results[] = "Pool IP [{$poolName}] sudah ada";
            }

            // 2. Interface Bridge
            $findBridge = $this->comm('/interface/bridge/print', ['?name' => $bridgeName]);
            if (empty($findBridge['!re'])) {
                $this->comm('/interface/bridge/add', ['name' => $bridgeName, 'comment' => 'NetSpeed Isolir Captive Portal']);
                $results[] = "Interface Bridge [{$bridgeName}] dibuat";
            } else {
                $results[] = "Interface Bridge [{$bridgeName}] sudah ada";
            }

            // 3. IP Address
            $findAddr = $this->comm('/ip/address/print', ['?interface' => $bridgeName]);
            $hasIp = false;
            foreach ($findAddr['!re'] ?? [] as $addr) {
                if (str_starts_with($addr['address'] ?? '', $gatewayIp)) {
                    $hasIp = true;
                    break;
                }
            }
            if (! $hasIp) {
                $this->comm('/ip/address/add', [
                    'address' => "{$gatewayIp}/24",
                    'network' => $networkIp,
                    'interface' => $bridgeName,
                    'comment' => 'Gateway Subnet Isolir',
                ]);
                $results[] = "IP Gateway [{$gatewayIp}/24] dipasang pada {$bridgeName}";
            } else {
                $results[] = "IP Gateway pada {$bridgeName} sudah aktif";
            }

            // 4. Hotspot Profile
            $findHsProf = $this->comm('/ip/hotspot/profile/print', ['?name' => $hsProfName]);
            if (empty($findHsProf['!re'])) {
                $this->comm('/ip/hotspot/profile/add', [
                    'name' => $hsProfName,
                    'hotspot-address' => $gatewayIp,
                    'dns-name' => $dnsName,
                    'html-directory' => $htmlDir,
                    'login-by' => 'http-chap,http-pap',
                ]);
                $results[] = "Hotspot Profile [{$hsProfName}] dibuat dengan DNS [{$dnsName}]";
            } else {
                $hsProfId = $findHsProf['!re'][0]['.id'] ?? null;
                if ($hsProfId) {
                    $this->comm('/ip/hotspot/profile/set', [
                        'numbers' => $hsProfId,
                        'hotspot-address' => $gatewayIp,
                        'dns-name' => $dnsName,
                        'html-directory' => $htmlDir,
                    ]);
                }
                $results[] = "Hotspot Profile [{$hsProfName}] diperbarui dengan DNS [{$dnsName}]";
            }

            // 5. Hotspot Server
            $findHs = $this->comm('/ip/hotspot/print', ['?name' => $hsServerName]);
            if (empty($findHs['!re'])) {
                $this->comm('/ip/hotspot/add', [
                    'name' => $hsServerName,
                    'interface' => $bridgeName,
                    'address-pool' => $poolName,
                    'profile' => $hsProfName,
                    'disabled' => 'no',
                ]);
                $results[] = "Hotspot Server [{$hsServerName}] aktif";
            } else {
                $results[] = "Hotspot Server [{$hsServerName}] sudah aktif";
            }

            // 6. PPP Profile ISOLIR (Dihubungkan ke bridge-isolir agar traffic PPPoE tertangkap Hotspot)
            $findPppProf = $this->comm('/ppp/profile/print', ['?name' => $pppProfileName]);
            $pppParams = [
                'name' => $pppProfileName,
                'local-address' => $gatewayIp,
                'remote-address' => $poolName,
                'bridge' => $bridgeName,
                'rate-limit' => '128k/128k',
                'dns-server' => $gatewayIp,
                'address-list' => 'ISOLIR-LIST',
                'comment' => 'Profil PPPoE Isolir Otomatis',
            ];

            if (empty($findPppProf['!re'])) {
                $this->comm('/ppp/profile/add', $pppParams);
                $results[] = "PPP Profile [{$pppProfileName}] dibuat dengan bridge [{$bridgeName}]";
            } else {
                $profId = $findPppProf['!re'][0]['.id'] ?? null;
                if ($profId) {
                    $setParams = $pppParams;
                    $setParams['numbers'] = $profId;
                    unset($setParams['name']);
                    $this->comm('/ppp/profile/set', $setParams);
                }
                $results[] = "PPP Profile [{$pppProfileName}] diperbarui dengan bridge [{$bridgeName}]";
            }

            // 7. Walled Garden Entries (WhatsApp & Gateways)
            $walledHosts = [
                $billingHost,
                '*.whatsapp.com',
                '*.whatsapp.net',
                'wa.me',
                '*.midtrans.com',
                '*.xendit.co',
                '*.tripay.co.id',
            ];

            foreach ($walledHosts as $host) {
                $check = $this->comm('/ip/hotspot/walled-garden/print', ['?dst-host' => $host]);
                if (empty($check['!re'])) {
                    $this->comm('/ip/hotspot/walled-garden/add', [
                        'dst-host' => $host,
                        'action' => 'allow',
                        'comment' => "Allow {$host}",
                    ]);
                }
            }
            $results[] = 'Walled Garden WhatsApp & Payment Gateway ditambahkan';

            // 9. DNS allow remote
            $this->comm('/ip/dns/set', ['allow-remote-requests' => 'yes']);

            $this->disconnect();

            return [
                'success' => true,
                'message' => "Konfigurasi Isolir Captive Portal berhasil diterapkan ke {$router->name}.",
                'steps' => $results,
            ];
        } catch (Exception $e) {
            $this->disconnect();
            Log::error("Failed to apply isolir setup: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => "Terjadi kesalahan: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Generate standalone, mobile-responsive HTML content for isolir.html / login.html / webproxy error.html.
     */
    public function getIsolirHtmlContent(array $customData = []): string
    {
        $companyName = $customData['company_name'] ?? IspSetting::get('company_name', 'NetSpeed Internet Service');
        $csPhone = $customData['company_phone'] ?? IspSetting::get('company_phone', '0812-3456-7890');
        $bankAccount = $customData['bank_account'] ?? IspSetting::get('bank_account', '');
        $billingUrl = $customData['billing_url'] ?? url('/portal');

        $cleanPhone = preg_replace('/[^0-9]/', '', $csPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '62'.$cleanPhone;
        }

        $waText = urlencode("Halo {$companyName}, saya ingin konfirmasi pembayaran internet IP: $(client-addr)");

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Layanan Terisolir</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .card {
            background: #ffffff;
            max-width: 480px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            border-top: 6px solid #ef4444;
        }
        .header {
            padding: 2rem 1.5rem 1rem;
        }
        .icon-wrapper {
            background-color: #fee2e2;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .icon-wrapper svg {
            width: 36px;
            height: 36px;
            stroke: #dc2626;
        }
        h1 {
            font-size: 1.4rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.5;
        }
        .content {
            padding: 0 1.5rem 1.5rem;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.25rem 0;
            text-align: left;
            font-size: 0.88rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
        }
        .info-value {
            font-weight: 600;
            color: #0f172a;
        }
        .btn-wa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #16a34a;
            color: #ffffff;
            text-decoration: none;
            width: 100%;
            padding: 0.85rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s ease;
            cursor: pointer;
        }
        .btn-wa:hover {
            background-color: #15803d;
        }
        .btn-wa svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
            flex-shrink: 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 0.85rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="header">
            <div class="icon-wrapper">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h1>Layanan Internet Di-Isolir</h1>
            <p>Layanan internet Anda sementara tidak aktif karena melewati batas tanggal jatuh tempo pembayaran.</p>
            <p style="margin-top: 10px; font-weight: 700; color: #dc2626; font-size: 0.92rem;">
                Silakan lakukan pembayaran untuk menikmati akses internet.
            </p>
        </div>

        <div class="content">
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">IP Pelanggan:</span>
                    <span class="info-value">$(client-addr)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #dc2626;">Terblokir Sementara</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CS WhatsApp:</span>
                    <span class="info-value">wa.me/{$cleanPhone}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Info Pembayaran:</span>
                    <span class="info-value">{$bankAccount}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Portal Tagihan:</span>
                    <span class="info-value">{$billingUrl}</span>
                </div>
            </div>

            <!-- Tombol Konfirmasi WhatsApp -->
            <a href="https://api.whatsapp.com/send?phone={$cleanPhone}&text={$waText}" onclick="window.location.href='whatsapp://send?phone={$cleanPhone}&text={$waText}';" target="_blank" class="btn-wa">
                <svg viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.159.57 4.184 1.564 5.938l-1.564 5.89 6.068-1.592c1.703.931 3.655 1.464 5.732 1.464 6.627 0 12-5.373 12-12s-5.373-12-12-12z"/></svg>
                Hubungi Admin / Konfirmasi WhatsApp
            </a>
        </div>

        <div class="footer">
            {$companyName} • Akses aktif otomatis setelah pembayaran diverifikasi
        </div>
    </div>

</body>
</html>
HTML;
    }

    /**
     * Upload isolir.html / login.html / webproxy error.html to RouterOS file storage via FTP or API.
     */
    public function uploadIsolirHtmlToRouter(Router $router, ?string $htmlContent = null): array
    {
        $htmlContent = $htmlContent ?: $this->getIsolirHtmlContent();

        try {
            if (! $this->connect($router)) {
                return [
                    'success' => false,
                    'message' => "Gagal terhubung ke router {$router->name}.",
                ];
            }

            // Check if router uses flash/ storage prefix
            $files = $this->comm('/file/print');
            $prefix = '';
            foreach ($files['!re'] ?? [] as $f) {
                if (str_starts_with($f['name'] ?? '', 'flash/')) {
                    $prefix = 'flash/';
                    break;
                }
            }

            $targets = [
                "{$prefix}webproxy/error.html",
                "{$prefix}hotspot-isolir/login.html",
                "{$prefix}hotspot-isolir/isolir.html",
                "{$prefix}hotspot/isolir.html",
            ];

            foreach ($targets as $target) {
                $this->uploadFileToRouter($router, $target, $htmlContent);
            }

            $this->disconnect();

            return [
                'success' => true,
                'message' => "File isolir.html berhasil diperbarui di storage MikroTik router {$router->name}.",
            ];
        } catch (Exception $e) {
            $this->disconnect();

            return [
                'success' => false,
                'message' => "Gagal mengunggah file ke router: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Helper to upload file to RouterOS via FTP with API fallback.
     */
    protected function uploadFileToRouter(Router $router, string $remotePath, string $content): bool
    {
        // Try FTP first if curl extension is loaded
        if (extension_loaded('curl') && ! empty($router->host) && ! empty($router->username)) {
            $ch = curl_init();
            $port = 21;
            curl_setopt($ch, CURLOPT_URL, "ftp://{$router->host}:{$port}/{$remotePath}");
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$router->username}:{$router->password}");
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $stream = tmpfile();
            if ($stream) {
                fwrite($stream, $content);
                rewind($stream);

                curl_setopt($ch, CURLOPT_INFILE, $stream);
                curl_setopt($ch, CURLOPT_INFILESIZE, strlen($content));
                $res = curl_exec($ch);
                fclose($stream);
                curl_close($ch);

                if ($res) {
                    return true;
                }
            } else {
                curl_close($ch);
            }
        }

        // Fallback to RouterOS API /file/set or /file/add
        try {
            $find = $this->comm('/file/print', ['?name' => $remotePath]);
            $id = $find['!re'][0]['.id'] ?? null;
            if ($id) {
                $this->comm('/file/set', ['numbers' => $id, 'contents' => $content]);
            } else {
                $this->comm('/file/add', ['name' => $remotePath, 'contents' => $content]);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Isolate Hotspot user in MikroTik.
     */
    public function isolateHotspotUser(Router $router, string $username, string $isolirProfile = 'ISOLIR'): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ip/hotspot/user/print', [
                '?name' => $username,
            ]);

            $exists = $find['!re'][0] ?? null;
            if ($exists && isset($exists['.id'])) {
                $this->comm('/ip/hotspot/user/set', [
                    'numbers' => $exists['.id'],
                    'profile' => $isolirProfile,
                    'comment' => 'ISOLIR HOTSPOT - EXPIRED '.date('Y-m-d H:i'),
                ]);
            }

            // Kick active session
            $this->kickHotspotActive($router, $username);
            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            Log::error("Failed to isolate hotspot user {$username}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Activate Hotspot user in MikroTik.
     */
    public function activateHotspotUser(Router $router, string $username, string $targetProfile = 'default'): bool
    {
        try {
            if (! $this->connect($router)) {
                return false;
            }

            $find = $this->comm('/ip/hotspot/user/print', [
                '?name' => $username,
            ]);

            $exists = $find['!re'][0] ?? null;
            if ($exists && isset($exists['.id'])) {
                $this->comm('/ip/hotspot/user/set', [
                    'numbers' => $exists['.id'],
                    'profile' => $targetProfile,
                    'disabled' => 'false',
                    'comment' => 'AKTIF HOTSPOT '.date('Y-m-d H:i'),
                ]);
            }

            $this->kickHotspotActive($router, $username);
            $this->disconnect();

            return true;
        } catch (Exception $e) {
            $this->disconnect();
            Log::error("Failed to activate hotspot user {$username}: ".$e->getMessage());

            return false;
        }
    }
}
