<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BasicConfigTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/basic-config';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'MikroTik Basic Configuration Script Generator';

    protected string $view = 'filament.pages.mikrotik-tools.basic-config';

    // RouterOS version & system identity
    public string $rosVersion = 'v7';

    public string $routerIdentity = 'NetSpeed-Router';

    public string $timezone = 'Asia/Jakarta';

    public bool $enableNtp = true;

    public string $ntpServerPrimary = 'id.pool.ntp.org';

    public string $ntpServerSecondary = 'time.google.com';

    // WAN Uplink
    public string $wanInterface = 'ether1';

    public string $wanMode = 'dhcp'; // dhcp, static, pppoe

    public string $wanIpCidr = '192.168.1.2/24';

    public string $wanGateway = '192.168.1.1';

    public string $wanPppoeUser = 'isp-uplink@provider';

    public string $wanPppoePass = 'password123';

    // LAN Bridge & DHCP
    public string $bridgeName = 'bridge-lan';

    public string $lanInterfaces = 'ether2,ether3,ether4,ether5';

    public string $lanIpCidr = '192.168.88.1/24';

    public bool $enableDhcpServer = true;

    public string $dhcpRange = '192.168.88.10-192.168.88.254';

    public string $dnsServers = '8.8.8.8,1.1.1.1';

    public bool $allowRemoteDns = true;

    // NAT, Firewall & Hardening
    public bool $enableNat = true;

    public bool $enableBasicFirewall = true;

    public bool $enableFasttrack = false;

    public bool $disableUnusedServices = true;

    // Router selection & push
    public ?int $selectedRouterId = null;

    public bool $isPushing = false;

    public ?array $pushResult = null;

    public function mount(): void
    {
        $defaultRouter = Router::where('is_active', true)->first();
        if ($defaultRouter) {
            $this->selectedRouterId = $defaultRouter->id;
        }
    }

    public function getGeneratedScriptProperty(): string
    {
        $versionLabel = $this->rosVersion === 'v7' ? 'RouterOS v7' : 'RouterOS v6';
        $identity = $this->sanitizeValue($this->routerIdentity, 'NetSpeed-Router');
        $tz = $this->sanitizeValue($this->timezone, 'Asia/Jakarta');
        $wanIf = $this->sanitizeValue($this->wanInterface, 'ether1');
        $bridge = $this->sanitizeValue($this->bridgeName, 'bridge-lan');
        $lanCidr = $this->sanitizeValue($this->lanIpCidr, '192.168.88.1/24');
        $lanNetwork = $this->extractNetworkCidr($lanCidr);
        $lanGateway = $this->extractIpOnly($lanCidr);
        $dns = $this->sanitizeValue($this->dnsServers, '8.8.8.8,1.1.1.1');
        $dhcpPoolRange = $this->sanitizeValue($this->dhcpRange, '192.168.88.10-192.168.88.254');

        $wanOutListTarget = $this->wanMode === 'pppoe' ? 'pppoe-wan' : $wanIf;

        $script = "# ========================================================\n";
        $script .= "# MikroTik Basic Configuration Script ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        // 1. System Identity & Clock / NTP
        $script .= "# 1. Identitas Router & Zona Waktu\n";
        $script .= "/system identity set name=\"{$identity}\"\n";
        $script .= "/system clock set time-zone-name=\"{$tz}\"\n";

        if ($this->enableNtp) {
            $ntp1 = $this->sanitizeValue($this->ntpServerPrimary, 'id.pool.ntp.org');
            $ntp2 = $this->sanitizeValue($this->ntpServerSecondary, 'time.google.com');
            if ($this->rosVersion === 'v7') {
                $script .= "/system ntp client set enabled=yes\n";
                $script .= "/system ntp client servers add address=\"{$ntp1}\"\n";
                if ($ntp2 !== '') {
                    $script .= "/system ntp client servers add address=\"{$ntp2}\"\n";
                }
            } else {
                $script .= "/system ntp client set enabled=yes primary-ntp=\"{$ntp1}\"".($ntp2 !== '' ? " secondary-ntp=\"{$ntp2}\"" : '')."\n";
            }
        }

        // 2. Interface Lists & LAN Bridge
        $script .= "\n# 2. Bridge LAN & Interface List (WAN / LAN)\n";
        $script .= "/interface bridge add name=\"{$bridge}\" comment=\"Local-LAN-Bridge\"\n";

        $lanPorts = array_values(array_filter(array_map('trim', explode(',', $this->lanInterfaces))));
        foreach ($lanPorts as $port) {
            $safePort = $this->sanitizeValue($port, '');
            if ($safePort !== '') {
                $script .= "/interface bridge port add bridge=\"{$bridge}\" interface=\"{$safePort}\"\n";
            }
        }

        $script .= "/interface list add name=WAN\n";
        $script .= "/interface list add name=LAN\n";
        $script .= "/interface list member add list=WAN interface=\"{$wanOutListTarget}\"\n";
        $script .= "/interface list member add list=LAN interface=\"{$bridge}\"\n";

        // 3. WAN Uplink Configuration
        $script .= "\n# 3. Konfigurasi Uplink Internet (WAN)\n";
        if ($this->wanMode === 'static') {
            $wanIp = $this->sanitizeValue($this->wanIpCidr, '192.168.1.2/24');
            $wanGw = $this->sanitizeValue($this->wanGateway, '192.168.1.1');
            $script .= "/ip address add address=\"{$wanIp}\" interface=\"{$wanIf}\" comment=\"WAN-Static\"\n";
            $script .= "/ip route add dst-address=0.0.0.0/0 gateway=\"{$wanGw}\" comment=\"Default-Gateway-WAN\"\n";
        } elseif ($this->wanMode === 'pppoe') {
            $pppoeUser = $this->sanitizeValue($this->wanPppoeUser, 'user@isp');
            $pppoePass = $this->sanitizeValue($this->wanPppoePass, 'password');
            $script .= "/interface pppoe-client add name=pppoe-wan interface=\"{$wanIf}\" user=\"{$pppoeUser}\" password=\"{$pppoePass}\" add-default-route=yes use-peer-dns=yes disabled=no comment=\"WAN-PPPoE\"\n";
        } else {
            $script .= "/ip dhcp-client add interface=\"{$wanIf}\" add-default-route=yes use-peer-dns=no disabled=no comment=\"WAN-DHCP\"\n";
        }

        // 4. LAN IP, DNS & DHCP Server
        $script .= "\n# 4. IP Gateway LAN, DNS & DHCP Server\n";
        $script .= "/ip address add address=\"{$lanCidr}\" interface=\"{$bridge}\" comment=\"LAN-Gateway\"\n";
        $remoteReq = $this->allowRemoteDns ? 'yes' : 'no';
        $script .= "/ip dns set servers=\"{$dns}\" allow-remote-requests={$remoteReq}\n";

        if ($this->enableDhcpServer) {
            $script .= "/ip pool add name=dhcp_pool_lan ranges=\"{$dhcpPoolRange}\"\n";
            $script .= "/ip dhcp-server add name=dhcp-lan interface=\"{$bridge}\" address-pool=dhcp_pool_lan lease-time=1d disabled=no\n";
            $script .= "/ip dhcp-server network add address=\"{$lanNetwork}\" gateway=\"{$lanGateway}\" dns-server=\"{$lanGateway},{$dns}\"\n";
        }

        // 5. NAT Masquerade
        if ($this->enableNat) {
            $script .= "\n# 5. Firewall NAT Masquerade\n";
            $script .= "/ip firewall nat add chain=srcnat out-interface-list=WAN action=masquerade comment=\"Default-Masquerade\"\n";
        }

        // 6. Basic Firewall & FastTrack
        if ($this->enableBasicFirewall || $this->enableFasttrack) {
            $script .= "\n# 6. Firewall Dasar & Keamanan Router\n";
            $script .= "/ip firewall filter\n";
            if ($this->enableFasttrack) {
                $script .= "add chain=forward action=fasttrack-connection connection-state=established,related hw-offload=yes comment=\"FastTrack-Established-Related\"\n";
            }
            if ($this->enableBasicFirewall) {
                $script .= "add chain=input action=accept connection-state=established,related,untracked comment=\"Accept-Established-Related-Input\"\n";
                $script .= "add chain=input action=drop connection-state=invalid comment=\"Drop-Invalid-Input\"\n";
                $script .= "add chain=input action=accept protocol=icmp comment=\"Accept-ICMP-Ping\"\n";
                $script .= "add chain=input action=drop in-interface-list=!LAN comment=\"Drop-All-Not-From-LAN\"\n";
                $script .= "add chain=forward action=accept connection-state=established,related,untracked comment=\"Accept-Established-Related-Forward\"\n";
                $script .= "add chain=forward action=drop connection-state=invalid comment=\"Drop-Invalid-Forward\"\n";
                $script .= "add chain=forward action=drop connection-state=new connection-nat-state=!dstnat in-interface-list=WAN comment=\"Drop-All-From-WAN-Not-DSTNATed\"\n";
            }
        }

        // 7. Service Hardening (keep WinBox & API active for ISP Billing)
        if ($this->disableUnusedServices) {
            $script .= "\n# 7. Nonaktifkan Service Tidak Terpakai (Pertahankan WinBox & API)\n";
            $script .= "/ip service set telnet disabled=yes\n";
            $script .= "/ip service set ftp disabled=yes\n";
            $script .= "/ip service set www disabled=yes\n";
            $script .= "/ip service set api-ssl disabled=yes\n";
            $script .= "/ip service set api disabled=no\n";
            $script .= "/ip service set winbox disabled=no\n";
        }

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'routerIdentity' => 'required|string|max:64',
            'wanInterface' => 'required|string|max:32',
            'bridgeName' => 'required|string|max:32',
            'lanIpCidr' => 'required|string|max:32',
            'selectedRouterId' => 'required|exists:routers,id',
        ], [
            'selectedRouterId.required' => 'Pilih router tujuan terlebih dahulu.',
        ]);

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            Notification::make()->title('Router tidak ditemukan.')->danger()->send();

            return;
        }

        $this->isPushing = true;
        $this->pushResult = null;

        $service = app(MikrotikApiService::class);
        $res = $service->executeScript($router, $this->generatedScript);

        $this->isPushing = false;
        $this->pushResult = [
            'success' => $res['success'],
            'message' => $res['message'],
            'timestamp' => now()->format('H:i:s'),
            'router_name' => $router->name,
            'router_host' => $router->host,
        ];

        if ($res['success']) {
            Notification::make()
                ->title('Basic Configuration Berhasil Diterapkan!')
                ->body("Script konfigurasi dasar telah dijalankan pada router {$router->name}.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Menerapkan ke MikroTik')
                ->body($res['message'])
                ->danger()
                ->send();
        }
    }

    protected function sanitizeValue(?string $value, string $fallback): string
    {
        $clean = str_replace(['"', "\r", "\n", ';'], '', trim((string) $value));

        return $clean !== '' ? $clean : $fallback;
    }

    protected function extractIpOnly(string $cidr): string
    {
        return explode('/', $cidr)[0] ?: '192.168.88.1';
    }

    protected function extractNetworkCidr(string $cidr): string
    {
        $parts = explode('/', $cidr);
        $ip = $parts[0] ?? '192.168.88.1';
        $prefix = isset($parts[1]) ? (int) $parts[1] : 24;
        $ipLong = ip2long($ip);

        if ($ipLong === false || $prefix < 0 || $prefix > 32) {
            return '192.168.88.0/24';
        }

        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        $network = long2ip($ipLong & $mask);

        return "{$network}/{$prefix}";
    }
}
