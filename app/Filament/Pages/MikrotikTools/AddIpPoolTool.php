<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AddIpPoolTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/add-ip-pool';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Tambah IP Pool MikroTik';

    protected string $view = 'filament.pages.mikrotik-tools.add-ip-pool';

    // Form inputs
    public string $rosVersion = 'v7'; // 'v6' or 'v7'

    public string $poolName = 'pool-customers';

    public string $ranges = '192.168.10.10-192.168.10.250';

    public string $nextPool = 'none';

    public string $comment = 'NetSpeed IP Pool Pelanggan';

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
        $name = trim($this->poolName) ?: 'pool-customers';
        $range = trim($this->ranges) ?: '192.168.10.10-192.168.10.250';
        $comm = trim($this->comment) ?: 'NetSpeed IP Pool';
        $next = trim($this->nextPool);

        $nextParam = (! empty($next) && strtolower($next) !== 'none') ? " next-pool=\"{$next}\"" : '';

        $script = "# ========================================================\n";
        $script .= "# MikroTik Script: Add IP Pool ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        $script .= "/ip pool\n";
        $script .= "add name=\"{$name}\" ranges=\"{$range}\"{$nextParam} comment=\"{$comm}\"\n";

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'poolName' => 'required|string|min:2|max:64',
            'ranges' => 'required|string',
            'selectedRouterId' => 'required|exists:routers,id',
        ], [
            'poolName.required' => 'Nama IP Pool wajib diisi.',
            'ranges.required' => 'Rentang IP (Ranges) wajib diisi (contoh: 192.168.10.10-192.168.10.250).',
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
        $res = $service->addIpPool($router, [
            'name' => $this->poolName,
            'ranges' => $this->ranges,
            'next_pool' => $this->nextPool,
            'comment' => $this->comment,
        ]);

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
                ->title('IP Pool Berhasil Diterapkan ke MikroTik!')
                ->body($res['message'])
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
