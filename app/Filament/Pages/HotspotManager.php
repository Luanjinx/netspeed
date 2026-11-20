<?php

namespace App\Filament\Pages;

use App\Models\HotspotVoucher;
use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithPagination;
use UnitEnum;

class HotspotManager extends Page
{
    use WithPagination;

    protected static ?string $slug = 'hotspot';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wifi';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Voucher Hotspot';

    protected static ?string $title = 'Manajemen Voucher & Layanan Hotspot';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.hotspot-manager';

    public ?int $selectedRouterId = null;

    public array $routerInfo = [];

    public array $activeSessions = [];

    public array $hotspotUsers = [];

    public array $hotspotProfiles = [];

    public array $hotspotServers = [];

    public string $voucherSearch = '';

    public string $voucherProfileFilter = '';

    public string $voucherStatusFilter = '';

    public int $voucherPerPage = 15;

    public bool $isConnected = false;

    public string $errorMessage = '';

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
        $this->resetPage('voucherPage');
        $this->loadData();
    }

    public function updatedVoucherSearch(): void
    {
        $this->resetPage('voucherPage');
    }

    public function updatedVoucherProfileFilter(): void
    {
        $this->resetPage('voucherPage');
    }

    public function updatedVoucherStatusFilter(): void
    {
        $this->resetPage('voucherPage');
    }

    public function updatedVoucherPerPage(): void
    {
        $this->resetPage('voucherPage');
    }

    public function refreshData(): void
    {
        $this->loadData();
        Notification::make()->title('Data Hotspot Berhasil Diperbarui')->success()->send();
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

        if (! $service->connect($router)) {
            $this->isConnected = false;
            $this->errorMessage = "Tidak dapat terhubung ke router {$router->name} ({$router->host}:{$router->port}).";

            return;
        }

        $this->isConnected = true;
        $this->errorMessage = '';

        // Fetch System Resources
        $res = $service->comm('/system/resource/print');
        $this->routerInfo = $res['!re'][0] ?? ($res['!done'][0] ?? []);

        // Fetch Hotspot Active Sessions
        $this->activeSessions = $service->getHotspotActive($router);

        // Fetch Hotspot Users
        $this->hotspotUsers = $service->getHotspotUsers($router);

        // Fetch Hotspot User Profiles
        $this->hotspotProfiles = $service->getHotspotUserProfiles($router);

        // Fetch Hotspot Servers
        $srvRes = $service->comm('/ip/hotspot/print');
        $this->hotspotServers = $srvRes['!re'] ?? [];

        $service->disconnect();
    }

    public function getVouchersProperty()
    {
        $query = HotspotVoucher::query()->with('router');
        if ($this->selectedRouterId) {
            $query->where('router_id', $this->selectedRouterId);
        }
        if (! empty($this->voucherSearch)) {
            $search = trim($this->voucherSearch);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('generated_batch', 'like', "%{$search}%");
            });
        }
        if (! empty($this->voucherProfileFilter)) {
            $query->where('profile', $this->voucherProfileFilter);
        }
        if (! empty($this->voucherStatusFilter)) {
            $query->where('status', $this->voucherStatusFilter);
        }

        return $query->latest()->paginate($this->voucherPerPage, ['*'], 'voucherPage');
    }

    public function getVoucherStatsProperty(): array
    {
        $query = HotspotVoucher::query();
        if ($this->selectedRouterId) {
            $query->where('router_id', $this->selectedRouterId);
        }

        return [
            'total' => (clone $query)->count(),
            'unused' => (clone $query)->where('status', 'unused')->count(),
            'used' => (clone $query)->where('status', 'used')->count(),
            'active_online' => count($this->activeSessions),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Generate Batch Voucher Hotspot
            Action::make('generateBatchVoucher')
                ->label('Generate Batch Voucher')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->modalHeading('Generate Batch Voucher Hotspot Sesuai Profil MikroTik')
                ->modalDescription('Voucher yang dibuat akan langsung otomatis terdaftar di /ip/hotspot/user MikroTik.')
                ->form([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => $this->selectedRouterId ?: Router::where('is_active', true)->value('id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('profile', null))
                        ->required(),

                    Select::make('profile')
                        ->label('Profil Hotspot MikroTik')
                        ->placeholder('Pilih profil hotspot...')
                        ->options(function ($get) {
                            $routerId = $get('router_id') ?: $this->selectedRouterId ?: Router::where('is_active', true)->value('id');
                            if (! $routerId) {
                                return ['default' => 'default'];
                            }
                            $router = Router::find($routerId);
                            if (! $router) {
                                return ['default' => 'default'];
                            }

                            return app(VoucherService::class)->getProfiles($router);
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state) {
                                $router = Router::find($get('router_id') ?: $this->selectedRouterId);
                                if ($router) {
                                    $meta = app(VoucherService::class)->extractProfileMetadata($state, $router);
                                    $set('validity', $meta['validity']);
                                    $set('price', $meta['price']);
                                    $set('shared_users', $meta['shared_users']);
                                }
                            }
                        })
                        ->required(),

                    TextInput::make('price')
                        ->label('Harga Jual Per Voucher (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(5000)
                        ->required(),

                    Select::make('validity')
                        ->label('Masa Aktif / Batas Waktu')
                        ->options([
                            '1 Jam' => '1 Jam (1h)',
                            '2 Jam' => '2 Jam (2h)',
                            '3 Jam' => '3 Jam (3h)',
                            '4 Jam' => '4 Jam (4h)',
                            '5 Jam' => '5 Jam (5h)',
                            '6 Jam' => '6 Jam (6h)',
                            '12 Jam' => '12 Jam (12h)',
                            '1 Hari' => '1 Hari / 24 Jam (1d)',
                            '2 Hari' => '2 Hari (2d)',
                            '3 Hari' => '3 Hari (3d)',
                            '7 Hari' => '7 Hari / 1 Minggu (7d)',
                            '14 Hari' => '14 Hari / 2 Minggu (14d)',
                            '30 Hari' => '30 Hari / 1 Bulan (30d)',
                        ])
                        ->default('1 Hari')
                        ->required(),

                    TextInput::make('qty')
                        ->label('Jumlah Voucher yang Dibuat')
                        ->numeric()
                        ->default(20)
                        ->minValue(1)
                        ->maxValue(500)
                        ->required(),

                    TextInput::make('shared_users')
                        ->label('Shared Users (Maks. Perangkat)')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(20)
                        ->required(),

                    Select::make('char_type')
                        ->label('Format Karakter Kode')
                        ->options([
                            'alphanumeric_upper' => 'Huruf Kapital + Angka (cth: A8K9M)',
                            'alpha_upper' => 'Hanya Huruf Kapital (cth: BDGKM)',
                            'numeric' => 'Hanya Angka (cth: 89432)',
                            'alphanumeric_lower' => 'Huruf Kecil + Angka (cth: x9m2p)',
                        ])
                        ->default('alphanumeric_upper')
                        ->required(),

                    Select::make('code_length')
                        ->label('Panjang Karakter Kode')
                        ->options([
                            4 => '4 Karakter',
                            5 => '5 Karakter',
                            6 => '6 Karakter',
                            8 => '8 Karakter',
                        ])
                        ->default(5)
                        ->required(),

                    TextInput::make('prefix')
                        ->label('Prefix Kode (Opsional)')
                        ->placeholder('cth: NET- atau HS-'),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $res = $voucherService->generateBatch(
                        $router,
                        $data['profile'],
                        (float) $data['price'],
                        $data['validity'],
                        (int) $data['qty'],
                        $data['prefix'] ?? '',
                        $data['char_type'] ?? 'alphanumeric_upper',
                        (int) $data['code_length'],
                        true,
                        null,
                        (int) ($data['shared_users'] ?? 1)
                    );

                    Notification::make()
                        ->title('Batch Voucher Berhasil Dibuat!')
                        ->body("{$res['count']} voucher berhasil digenerate dan didaftarkan ke MikroTik {$router->name}.")
                        ->success()
                        ->send();

                    $this->selectedRouterId = $router->id;
                    $this->loadData();
                }),

            // 2. Tarik Profil Hotspot MikroTik
            Action::make('pullProfilesAction')
                ->label('Tarik Profil MikroTik')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(fn () => $this->pullProfiles()),

            // 3. Sinkron Status Voucher
            Action::make('syncVouchersAction')
                ->label('Sinkron Status Voucher')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(fn () => $this->syncVoucherStatus()),
        ];
    }

    public function kickSession(string $identifier): void
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

    public function pullProfiles(): void
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

    public function syncVoucherStatus(): void
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
            ->title('Sinkronisasi Status Selesai')
            ->body("Terdeteksi {$res['active_sessions_count']} pengguna aktif. {$res['vouchers_marked_used']} voucher ditandai 'used'.")
            ->success()
            ->send();

        $this->loadData();
    }

    public function deleteVoucher(int $id): void
    {
        $voucher = HotspotVoucher::find($id);
        if (! $voucher) {
            return;
        }

        $code = $voucher->code;
        $voucher->delete();

        Notification::make()->title("Voucher '{$code}' berhasil dihapus")->success()->send();
    }
}
