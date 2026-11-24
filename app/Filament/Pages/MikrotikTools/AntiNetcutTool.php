<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AntiNetcutTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/anti-netcut';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Anti Netcut Protection';

    protected string $view = 'filament.pages.mikrotik-tools.anti-netcut';

    // Form inputs
    public string $rosVersion = 'v7'; // 'v6' or 'v7'

    public string $localListName = 'Local';

    public bool $autoDefineLocalSubnets = true; // Otomatis buat list Local (RFC1918) agar rule tidak error

    public string $blacklistTimeout = '00:10:00';

    public string $targetDomain = 'www.arcai.com';

    public string $contentPattern = '.arcai.com';

    public bool $dropInput = true; // Blokir juga akses ke gateway router

    public string $comment = 'Anti-Netcut';

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
        $local = trim($this->localListName) ?: 'Local';
        $timeout = trim($this->blacklistTimeout) ?: '00:10:00';
        $domain = trim($this->targetDomain) ?: 'www.arcai.com';
        $pattern = trim($this->contentPattern) ?: '.arcai.com';
        $comm = trim($this->comment) ?: 'Anti-Netcut';

        $script = "# ========================================================\n";
        $script .= "# Anti Netcut Protection Configuration ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        if ($this->autoDefineLocalSubnets) {
            $script .= "# 1. Definisi Address-List IP Lokal ({$local}) agar pencocokan rule berhasil\n";
            $script .= "/ip firewall address-list\n";
            $script .= "add list={$local} address=192.168.0.0/16 comment=\"IP-Private-LAN\"\n";
            $script .= "add list={$local} address=10.0.0.0/8 comment=\"IP-Private-LAN\"\n";
            $script .= "add list={$local} address=172.16.0.0/12 comment=\"IP-Private-LAN\"\n\n";
        }

        $script .= "# 2. Daftarkan domain server Netcut ke address-list\n";
        $script .= "/ip firewall address-list\n";
        $script .= "add list=netcut address={$domain} comment=\"Netcut-Official-Domain\"\n\n";

        $script .= "# 3. Deteksi traffic Netcut di RAW Prerouting (Sangat hemat CPU sebelum conntrack)\n";
        $script .= "/ip firewall raw\n";
        $script .= "add action=add-src-to-address-list address-list=IP_NETCUT address-list-timeout={$timeout} chain=prerouting content=\"{$pattern}\" dst-address-list=!{$local} src-address-list={$local} comment=\"Detect-Netcut-User\"\n\n";

        $script .= "# 4. Blokir koneksi internet pengguna Netcut di Filter Forward\n";
        $script .= "/ip firewall filter\n";
        $script .= "add chain=forward action=drop src-address-list=IP_NETCUT comment=\"{$comm}-Drop-Forward\"\n";

        if ($this->dropInput) {
            $script .= "add chain=input action=drop src-address-list=IP_NETCUT comment=\"{$comm}-Drop-Input-Router\"\n";
        }

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'localListName' => 'required|string',
            'blacklistTimeout' => 'required|string',
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
                ->title('Aturan Anti Netcut Berhasil Diterapkan!')
                ->body("Proteksi Netcut telah aktif pada router {$router->name}.")
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
}
