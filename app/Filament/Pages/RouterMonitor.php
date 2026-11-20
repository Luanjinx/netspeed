<?php

namespace App\Filament\Pages;

use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class RouterMonitor extends Page
{
    protected static ?string $slug = 'router-monitor';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Monitoring Jaringan & MikroTik';

    protected static ?string $title = 'Monitoring Real-time Jaringan & MikroTik';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.router-monitor';

    public string $activeTab = 'router'; // 'router', 'pppoe', 'hotspot'

    protected $queryString = ['activeTab'];

    public ?int $selectedRouterId = null;

    public bool $isConnected = false;

    public bool $isSimulated = false;

    public string $errorMessage = '';

    public bool $autoRefresh = false;

    // --- Tab 1: Router & Interface Traffic ---
    public array $systemResources = [];

    public array $interfaces = [];

    public array $topConsumers = [];

    // --- Tab 2: PPPoE Monitoring ---
    public array $pppoeActiveSessions = [];

    public array $pppoeSecrets = [];

    public array $pppoeProfiles = [];

    // --- Tab 3: Hotspot Monitoring ---
    public array $hotspotActiveSessions = [];

    public array $hotspotUsers = [];

    public array $hotspotProfiles = [];

    public array $hotspotServers = [];

    public function mount(): void
    {
        $defaultRouter = Router::where('is_active', true)->first();
        if ($defaultRouter) {
            $this->selectedRouterId = $defaultRouter->id;
            $this->loadData();
        }
    }

    public function updatedSelectedRouterId(): void
    {
        $this->loadData();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadData();
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = ! $this->autoRefresh;
    }

    public function refreshData(): void
    {
        $this->loadData();
        Notification::make()
            ->title('Data Monitoring Berhasil Diperbarui')
            ->success()
            ->send();
    }

    public function loadData(): void
    {
        if (! $this->selectedRouterId) {
            $this->isConnected = false;

            return;
        }

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            $this->isConnected = false;

            return;
        }

        $service = app(MikrotikApiService::class);

        // System Resources is lightweight and always kept updated
        $sysRes = $service->getSystemResources($router);
        if ($sysRes['success']) {
            $this->isConnected = true;
            $this->isSimulated = ! empty($sysRes['is_simulated']);
            $this->systemResources = $sysRes;
            $this->errorMessage = '';
        } else {
            $this->isConnected = false;
            $this->isSimulated = false;
            $this->errorMessage = $sysRes['message'] ?? "Gagal terhubung ke router {$router->name} ({$router->host}:{$router->port}).";

            return;
        }

        // Fetch data based on active tab
        if ($this->activeTab === 'router') {
            $ifaceRes = $service->getInterfacesWithTraffic($router);
            $this->interfaces = $ifaceRes['interfaces'] ?? [];
            $this->topConsumers = $service->getTopBandwidthConsumers($router, 5);
        } elseif ($this->activeTab === 'pppoe') {
            if ($service->connect($router)) {
                $actives = $service->comm('/ppp/active/print');
                $this->pppoeActiveSessions = $actives['!re'] ?? [];

                $secretsRes = $service->comm('/ppp/secret/print');
                $this->pppoeSecrets = $secretsRes['!re'] ?? [];

                $profRes = $service->comm('/ppp/profile/print');
                $this->pppoeProfiles = $profRes['!re'] ?? [];
                $service->disconnect();
            }
        } elseif ($this->activeTab === 'hotspot') {
            $this->hotspotActiveSessions = $service->getHotspotActive($router);
            $this->hotspotUsers = $service->getHotspotUsers($router);
            $this->hotspotProfiles = $service->getHotspotUserProfiles($router);
            if ($service->connect($router)) {
                $srvRes = $service->comm('/ip/hotspot/print');
                $this->hotspotServers = $srvRes['!re'] ?? [];
                $service->disconnect();
            }
        }
    }

    // --- Router Actions ---
    public function rebootRouter(): void
    {
        if (! $this->selectedRouterId) {
            return;
        }

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        if ($service->connect($router)) {
            $service->comm('/system/reboot');
            $service->disconnect();
            Notification::make()
                ->title('Perintah Reboot Berhasil Dikirim ke Router')
                ->body("Router {$router->name} sedang dalam proses reboot.")
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal mengirim perintah reboot')
                ->body('Tidak dapat terhubung ke router.')
                ->danger()
                ->send();
        }
    }

    // --- PPPoE Actions ---
    public function kickPppoeSession(string $username): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        if ($service->connect($router)) {
            $actives = $service->comm('/ppp/active/print', ['?name' => $username]);
            if (isset($actives['!re'])) {
                foreach ($actives['!re'] as $active) {
                    if (isset($active['.id'])) {
                        $service->comm('/ppp/active/remove', ['.id' => $active['.id']]);
                    }
                }
            }
            $service->disconnect();
            Notification::make()->title("Sesi user '{$username}' berhasil diputuskan")->warning()->send();
            $this->loadData();
        }
    }

    public function importAllPppoeSecrets(): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $res = $service->importPppSecretsToCustomers($router);
        Notification::make()
            ->title('Sinkronisasi Sukses')
            ->body("Ditemukan: {$res['total']} user.\nImport Baru: {$res['imported']} pelanggan.\nUpdate: {$res['updated']} pelanggan.")
            ->success()
            ->send();

        $this->loadData();
    }

    public function pushAllPppoeCustomers(): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $res = $service->syncAllCustomersToMikrotik($router);
        Notification::make()
            ->title('Sinkronisasi ke MikroTik Selesai')
            ->body("Pelanggan dikirim: {$res['total']}. Sukses: {$res['synced']}. Gagal: {$res['failed']}.")
            ->success()
            ->send();

        $this->loadData();
    }

    // --- Hotspot Actions ---
    public function kickHotspotSession(string $identifier): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $kicked = $service->kickHotspotActive($router, $identifier);

        if ($kicked) {
            Notification::make()->title("Sesi hotspot '{$identifier}' berhasil diputuskan")->warning()->send();
        } else {
            Notification::make()->title("Gagal memutuskan sesi '{$identifier}'")->danger()->send();
        }

        $this->loadData();
    }

    public function pullHotspotProfiles(): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $voucherService = app(VoucherService::class);
        $res = $voucherService->pullProfilesFromRouter($router);

        Notification::make()
            ->title('Profil Hotspot Disinkronkan')
            ->body("Ditemukan {$res['total']} profil di MikroTik. {$res['imported']} paket baru tersimpan di database.")
            ->success()
            ->send();

        $this->loadData();
    }

    public function syncHotspotVoucherStatus(): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $voucherService = app(VoucherService::class);
        $res = $voucherService->syncVoucherStatus($router);

        Notification::make()
            ->title('Sinkronisasi Sesi Hotspot Selesai')
            ->body("Terdeteksi {$res['active_sessions_count']} pengguna online. {$res['vouchers_marked_used']} voucher ditandai 'used'.")
            ->success()
            ->send();

        $this->loadData();
    }
}
