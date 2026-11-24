<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AntiDdosTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/anti-ddos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Anti DDoS Attacks Filter';

    protected string $view = 'filament.pages.mikrotik-tools.anti-ddos';

    // Form inputs
    public string $rosVersion = 'v7'; // 'v6' or 'v7'

    public int $dstLimitRate = 50;

    public int $dstLimitBurst = 50;

    public string $dstLimitTime = '10s';

    public string $blacklistTimeout = '10m';

    public string $comment = 'Anti-DDoS-Attacks';

    public bool $optimizeRuleOrder = true; // Rekomendasi: taruh drop di atas jump agar hemat CPU

    public bool $includeInputChain = false; // Opsional proteksi routerboard itu sendiri

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
        $comm = trim($this->comment) ?: 'Anti-DDoS-Attacks';
        $rate = $this->dstLimitRate;
        $burst = $this->dstLimitBurst;
        $time = trim($this->dstLimitTime) ?: '10s';
        $timeout = trim($this->blacklistTimeout) ?: '10m';

        $script = "# ========================================================\n";
        $script .= "# Anti DDoS Attacks Configuration for MikroTik RouterOS ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        $script .= "/ip firewall filter\n";

        if ($this->optimizeRuleOrder) {
            // Rekomendasi urutan hemat CPU: Drop penyerang lama terlebih dahulu sebelum komputasi dst-limit
            $script .= "# 1. Drop paket dari IP yang sudah terdeteksi sebagai penyerang (Hemat CPU)\n";
            $script .= "add chain=forward connection-state=new src-address-list=ddoser dst-address-list=ddosed action=drop comment=\"Drop-Known-DDoS-Attackers\"\n";
            if ($this->includeInputChain) {
                $script .= "add chain=input connection-state=new src-address-list=ddoser action=drop comment=\"Drop-DDoS-to-Router\"\n";
            }
            $script .= "\n# 2. Jump koneksi baru ke chain inspeksi DDoS\n";
            $script .= "add chain=forward connection-state=new action=jump jump-target=block-ddos comment=\"{$comm}\"\n";
            if ($this->includeInputChain) {
                $script .= "add chain=input connection-state=new action=jump jump-target=block-ddos comment=\"{$comm}-Input\"\n";
            }
        } else {
            // Urutan persis seperti script yang diberikan user
            $script .= "add chain=forward connection-state=new action=jump jump-target=block-ddos comment=\"{$comm}\"\n";
            $script .= "add chain=forward connection-state=new src-address-list=ddoser dst-address-list=ddosed action=drop\n";
        }

        $script .= "\n# 3. Chain block-ddos: Izinkan traffic dalam ambang batas (dst-limit), masukkan ke blacklist jika melebihi batas\n";
        $script .= "add chain=block-ddos dst-limit={$rate},{$burst},src-and-dst-addresses/{$time} action=return\n";
        $script .= "add chain=block-ddos action=add-dst-to-address-list address-list=ddosed address-list-timeout={$timeout}\n";
        $script .= "add chain=block-ddos action=add-src-to-address-list address-list=ddoser address-list-timeout={$timeout}\n";

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'dstLimitRate' => 'required|integer|min:1|max:10000',
            'dstLimitBurst' => 'required|integer|min:1|max:10000',
            'dstLimitTime' => 'required|string',
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
                ->title('Aturan Anti DDoS Berhasil Diterapkan ke MikroTik!')
                ->body("Filter Anti DDoS telah aktif pada router {$router->name}.")
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
