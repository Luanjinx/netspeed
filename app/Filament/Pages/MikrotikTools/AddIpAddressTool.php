<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AddIpAddressTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/add-ip-address';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Tambah IP Address MikroTik';

    protected string $view = 'filament.pages.mikrotik-tools.add-ip-address';

    // Form inputs
    public string $rosVersion = 'v7'; // 'v6' or 'v7'

    public string $ipAddress = '192.168.10.1/24';

    public string $interface = 'ether2';

    public string $network = '';

    public string $comment = 'LAN Office';

    public bool $disabled = false;

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
        $ip = trim($this->ipAddress) ?: '192.168.10.1/24';
        $iface = trim($this->interface) ?: 'ether2';
        $comm = trim($this->comment) ?: 'NetSpeed IP Address';
        $net = trim($this->network);
        $dis = $this->disabled ? 'yes' : 'no';

        $netParam = ! empty($net) ? " network=\"{$net}\"" : '';

        $script = "# ========================================================\n";
        $script .= "# MikroTik Script: Add IP Address ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        $script .= "/ip address\n";
        $script .= "add address=\"{$ip}\" interface=\"{$iface}\"{$netParam} comment=\"{$comm}\" disabled={$dis}\n";

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'ipAddress' => 'required|string|regex:/^[0-9\.\/]+$/',
            'interface' => 'required|string',
            'selectedRouterId' => 'required|exists:routers,id',
        ], [
            'ipAddress.required' => 'IP Address beserta subnet CIDR wajib diisi (contoh: 192.168.10.1/24).',
            'ipAddress.regex' => 'Format IP Address tidak valid. Gunakan format IP/CIDR misal 192.168.10.1/24.',
            'interface.required' => 'Nama interface wajib diisi.',
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
        $res = $service->addIpAddress($router, [
            'address' => $this->ipAddress,
            'interface' => $this->interface,
            'network' => $this->network,
            'comment' => $this->comment,
            'disabled' => $this->disabled,
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
                ->title('IP Address Berhasil Diterapkan ke MikroTik!')
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
