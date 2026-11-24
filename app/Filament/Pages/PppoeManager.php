<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Router;
use App\Services\Bandwidth\FupService;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class PppoeManager extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'pppoe';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Jaringan';

    protected static ?string $navigationLabel = 'PPPoE';

    protected static ?string $title = 'Manajemen Layanan PPPoE';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.pppoe-manager';

    public ?int $selectedRouterId = null;

    public array $routerInfo = [];

    // PPPoE Properties
    public array $activeSessions = [];

    public array $secrets = [];

    public array $profiles = [];

    public array $customerMap = [];

    public int $totalFupThrottled = 0;

    public string $searchQuery = '';

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
        $this->loadData();
    }

    public function refreshData(): void
    {
        $this->loadData();
        Notification::make()->title('Data Layanan PPPoE Berhasil Diperbarui')->success()->send();
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

        // Fetch PPPoE Active Sessions
        $actives = $service->comm('/ppp/active/print');
        $this->activeSessions = $actives['!re'] ?? [];

        // Fetch PPPoE Secrets
        $secretsRes = $service->comm('/ppp/secret/print');
        $this->secrets = $secretsRes['!re'] ?? [];

        // Fetch PPPoE Profiles
        $profRes = $service->comm('/ppp/profile/print');
        $this->profiles = $profRes['!re'] ?? [];

        $service->disconnect();

        // Fetch Customers with FUP usage
        $customers = Customer::where('router_id', $this->selectedRouterId)
            ->whereNotNull('pppoe_username')
            ->with(['package', 'currentMonthFupUsage'])
            ->get();

        $this->customerMap = [];
        $fupCount = 0;
        foreach ($customers as $c) {
            $this->customerMap[$c->pppoe_username] = [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'fup_status' => $c->fup_status,
                'fup_limit_gb' => $c->fup_override_limit_gb ?: ($c->package?->fup_limit_gb ?? 0),
                'usage_gb' => $c->currentMonthFupUsage?->usage_gb ?? 0,
            ];
            if ($c->fup_status === 'fup_applied') {
                $fupCount++;
            }
        }
        $this->totalFupThrottled = $fupCount;
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Tambah Secret PPPoE Baru Langsung ke MikroTik
            Action::make('createSecret')
                ->label('Tambah Secret PPPoE')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('Tambah Akun / Secret PPPoE Baru')
                ->modalDescription('Akun akan langsung didaftarkan ke menu /ppp/secret pada router MikroTik terpilih.')
                ->form([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => $this->selectedRouterId ?: Router::where('is_active', true)->value('id'))
                        ->required(),

                    Select::make('customer_id')
                        ->label('Hubungkan ke Data Pelanggan yang Ada (Opsional)')
                        ->options(Customer::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Pilih pelanggan (atau biarkan kosong untuk input manual / buat pelanggan baru)')
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $customer = Customer::with('package')->find($state);
                                if ($customer) {
                                    $set('customer_name', $customer->name);
                                    $set('customer_phone', $customer->phone);
                                    if ($customer->pppoe_username) {
                                        $set('name', $customer->pppoe_username);
                                    }
                                    if ($customer->pppoe_password) {
                                        $set('password', $customer->pppoe_password);
                                    }
                                    if ($customer->package?->mikrotik_profile) {
                                        $set('profile', $customer->package->mikrotik_profile);
                                    }
                                    if ($customer->ip_address) {
                                        $set('remote_address', $customer->ip_address);
                                    }
                                    if ($customer->router_id) {
                                        $set('router_id', $customer->router_id);
                                    }
                                }
                            }
                        }),

                    TextInput::make('customer_name')
                        ->label('Nama Lengkap Pelanggan')
                        ->placeholder('cth: Bpk. Ahmad Subarjo')
                        ->helperText('Otomatis didaftarkan ke Data Pelanggan jika belum ada.'),

                    TextInput::make('customer_phone')
                        ->label('Nomor WhatsApp Pelanggan')
                        ->placeholder('081234567890')
                        ->tel(),

                    TextInput::make('name')
                        ->label('Username PPPoE')
                        ->placeholder('cth: ahmad_net / user123')
                        ->required()
                        ->distinct(),

                    TextInput::make('password')
                        ->label('Password PPPoE')
                        ->default('123456')
                        ->required(),

                    Select::make('profile')
                        ->label('Profil Bandwidth PPPoE MikroTik')
                        ->options(function ($get) {
                            $routerId = $get('router_id') ?: $this->selectedRouterId ?: Router::where('is_active', true)->value('id');
                            if (! $routerId) {
                                return ['default' => 'default'];
                            }
                            $router = Router::find($routerId);
                            if (! $router) {
                                return ['default' => 'default'];
                            }
                            $service = app(MikrotikApiService::class);
                            $profiles = $service->getPppProfiles($router);
                            $opts = [];
                            foreach ($profiles as $p) {
                                $name = $p['name'] ?? '';
                                if ($name) {
                                    $opts[$name] = $name.(! empty($p['rate-limit']) ? " ({$p['rate-limit']})" : '');
                                }
                            }

                            return ! empty($opts) ? $opts : ['default' => 'default'];
                        })
                        ->searchable()
                        ->default('default')
                        ->required(),

                    Select::make('service')
                        ->label('Service Type')
                        ->options([
                            'any' => 'any',
                            'pppoe' => 'pppoe',
                        ])
                        ->default('pppoe')
                        ->required(),

                    TextInput::make('local_address')
                        ->label('Local Address (Gateway PPPoE / Server IP)')
                        ->placeholder('cth: 10.10.10.1')
                        ->helperText('IP gateway interface PPPoE pada router. Kosongkan untuk default profile.'),

                    TextInput::make('remote_address')
                        ->label('Remote IP Address (Opsional / Static)')
                        ->placeholder('cth: 10.10.10.50')
                        ->helperText('Kosongkan jika IP didapat otomatis dari IP Pool MikroTik Profile.'),

                    TextInput::make('comment')
                        ->label('Komentar / Catatan')
                        ->placeholder('cth: Pelanggan Rumah Blok A'),
                ])
                ->action(function (array $data, MikrotikApiService $service) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $remoteAddress = ! empty($data['remote_address']) ? trim($data['remote_address']) : null;
                    $localAddress = ! empty($data['local_address']) ? trim($data['local_address']) : null;
                    $comment = ! empty($data['comment']) ? trim($data['comment']) : 'Panel ISP Billing - '.($data['customer_name'] ?? $data['name']);

                    $secretData = [
                        'name' => $data['name'],
                        'password' => $data['password'],
                        'profile' => $data['profile'],
                        'service' => $data['service'] ?? 'pppoe',
                        'comment' => $comment,
                    ];
                    if ($remoteAddress) {
                        $secretData['remote-address'] = $remoteAddress;
                    }
                    if ($localAddress) {
                        $secretData['local-address'] = $localAddress;
                    }

                    $synced = $service->syncPppSecret($router, $secretData);

                    if ($synced) {

                        $cust = null;
                        if (! empty($data['customer_id'])) {
                            $cust = Customer::find($data['customer_id']);
                            if ($cust) {
                                $cust->update([
                                    'pppoe_username' => $data['name'],
                                    'pppoe_password' => $data['password'],
                                    'router_id' => $router->id,
                                    'ip_address' => $remoteAddress,
                                ]);
                            }
                        } else {
                            $cust = Customer::where('pppoe_username', $data['name'])->first();
                            if ($cust) {
                                $cust->update([
                                    'pppoe_password' => $data['password'],
                                    'router_id' => $router->id,
                                    'ip_address' => $remoteAddress,
                                ]);
                            } else {
                                $package = InternetPackage::where('type', 'pppoe')
                                    ->where('mikrotik_profile', $data['profile'])
                                    ->first()
                                    ?? InternetPackage::where('type', 'pppoe')->first();

                                $custName = ! empty($data['customer_name'])
                                    ? trim($data['customer_name'])
                                    : (! empty($data['comment']) && ! str_contains($data['comment'], 'Panel ISP Billing')
                                        ? trim($data['comment'])
                                        : ucwords(str_replace(['_', '.', '-'], ' ', $data['name'])));

                                $custPhone = ! empty($data['customer_phone'])
                                    ? preg_replace('/[^0-9]/', '', $data['customer_phone'])
                                    : '08'.rand(1111111111, 9999999999);

                                $cust = Customer::create([
                                    'customer_number' => Customer::generateCustomerNumber(),
                                    'name' => $custName,
                                    'phone' => $custPhone,
                                    'router_id' => $router->id,
                                    'package_id' => $package?->id,
                                    'connection_type' => 'pppoe',
                                    'pppoe_username' => $data['name'],
                                    'pppoe_password' => $data['password'],
                                    'ip_address' => $remoteAddress,
                                    'status' => 'active',
                                    'due_date_day' => 10,
                                    'auto_isolate' => true,
                                    'notes' => $data['comment'] ?? 'Dibuat otomatis dari Manajemen PPPoE',
                                ]);
                            }
                        }

                        $custInfo = $cust ? " dan otomatis terdaftar di Data Pelanggan ({$cust->name} - {$cust->customer_number})" : '';
                        Notification::make()
                            ->title('Secret PPPoE Berhasil Didaftarkan!')
                            ->body("User [{$data['name']}] dengan profile [{$data['profile']}] aktif di MikroTik {$router->name}{$custInfo}.")
                            ->success()
                            ->send();

                        $this->selectedRouterId = $router->id;
                        $this->loadData();
                    } else {
                        Notification::make()
                            ->title('Gagal Mendaftarkan Secret PPPoE')
                            ->body("Tidak dapat menghubungkan ke router {$router->name}. Periksa IP dan API MikroTik.")
                            ->danger()
                            ->send();
                    }
                }),

            // 2. Buat Profil PPPoE Baru Langsung di MikroTik
            Action::make('createProfile')
                ->label('Buat Profil di MikroTik')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->modalHeading('Buat Profil Bandwidth PPPoE Baru')
                ->modalDescription('Profil akan langsung disimpan ke menu /ppp/profile pada MikroTik.')
                ->form([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => $this->selectedRouterId ?: Router::where('is_active', true)->value('id'))
                        ->required(),

                    TextInput::make('name')
                        ->label('Nama Profil MikroTik')
                        ->placeholder('cth: PAKET-10MBPS / VIP-50M')
                        ->required(),

                    TextInput::make('rate_limit')
                        ->label('Rate Limit (Upload/Download)')
                        ->placeholder('cth: 5M/10M (Upload 5M, Download 10M)')
                        ->helperText('Gunakan format [rx/tx] contoh: 2M/5M, 10M/20M.')
                        ->required(),

                    TextInput::make('local_address')
                        ->label('Local Address (Gateway PPPoE)')
                        ->placeholder('cth: 10.10.10.1')
                        ->default('10.10.10.1')
                        ->required(),

                    TextInput::make('remote_address')
                        ->label('Remote Address (IP Pool / IP Pelanggan)')
                        ->placeholder('cth: pool-pppoe atau 10.10.10.2')
                        ->helperText('Nama IP Pool di MikroTik atau IP address awal.')
                        ->required(),

                    TextInput::make('dns_server')
                        ->label('DNS Server')
                        ->placeholder('cth: 8.8.8.8,1.1.1.1')
                        ->default('8.8.8.8,1.1.1.1'),

                    TextInput::make('comment')
                        ->label('Komentar')
                        ->default('Dibuat via Billing System'),
                ])
                ->action(function (array $data, MikrotikApiService $service) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    if ($service->connect($router)) {
                        $params = [
                            'name' => $data['name'],
                            'rate-limit' => $data['rate_limit'],
                            'local-address' => $data['local_address'],
                            'remote-address' => $data['remote_address'],
                        ];
                        if (! empty($data['dns_server'])) {
                            $params['dns-server'] = $data['dns_server'];
                        }
                        if (! empty($data['comment'])) {
                            $params['comment'] = $data['comment'];
                        }

                        $service->comm('/ppp/profile/add', $params);
                        $service->disconnect();

                        Notification::make()
                            ->title('Profil PPPoE MikroTik Berhasil Dibuat!')
                            ->body("Profil [{$data['name']}] dengan limit [{$data['rate_limit']}] telah ditambahkan ke {$router->name}.")
                            ->success()
                            ->send();

                        $this->selectedRouterId = $router->id;
                        $this->loadData();
                    } else {
                        Notification::make()->title('Gagal terhubung ke router')->danger()->send();
                    }
                }),

            // 3. Sinkronisasi Pelanggan Database ke MikroTik (Push All Secrets)
            Action::make('pushAllSecrets')
                ->label('Push Semua Pelanggan ke Router')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Push Semua Akun PPPoE ke MikroTik')
                ->modalDescription('Seluruh pelanggan berstatus aktif di database yang memiliki pppoe_username akan didaftarkan/diperbarui di /ppp/secret router ini.')
                ->requiresConfirmation()
                ->action(function (MikrotikApiService $service) {
                    if (! $this->selectedRouterId) {
                        return;
                    }
                    $router = Router::find($this->selectedRouterId);
                    if (! $router) {
                        return;
                    }

                    $res = $service->pushAllCustomersToRouter($router);

                    Notification::make()
                        ->title('Push ke Router Selesai')
                        ->body("Total: {$res['total']} pelanggan. Berhasil: {$res['success']}. Gagal: {$res['failed']}.")
                        ->success()
                        ->send();

                    $this->loadData();
                }),

            // 4. Cek & Terapkan FUP Kuota Sekarang
            Action::make('checkFup')
                ->label('Cek & Terapkan FUP')
                ->icon('heroicon-o-funnel')
                ->color('danger')
                ->modalHeading('Pemeriksaan Kuota FUP Pelanggan PPPoE')
                ->modalDescription('Sistem akan membaca trafik terkini dari seluruh interface PPPoE aktif pada router ini. Pelanggan yang telah melampaui batas FUP paket akan otomatis disesuaikan kecepatannya dan dikirimi WhatsApp.')
                ->requiresConfirmation()
                ->action(function (FupService $fupService) {
                    if (! $this->selectedRouterId) {
                        return;
                    }
                    $router = Router::find($this->selectedRouterId);
                    if (! $router) {
                        return;
                    }

                    $res = $fupService->checkRouterCustomersFup($router);
                    if ($res['success'] ?? false) {
                        Notification::make()
                            ->title('Pemeriksaan FUP Selesai')
                            ->body("Diperiksa: {$res['checked']} pelanggan online.\nFUP Baru Diterapkan: {$res['fup_applied']}\nSedang FUP: {$res['already_fup']}\nNormal: {$res['normal']}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Memeriksa FUP')
                            ->body($res['message'] ?? 'Periksa koneksi router.')
                            ->danger()
                            ->send();
                    }

                    $this->loadData();
                }),
        ];
    }

    public function restoreCustomerFup(string $username): void
    {
        $customer = Customer::where('pppoe_username', $username)->first();
        if (! $customer) {
            Notification::make()->title('Data pelanggan tidak ditemukan')->danger()->send();

            return;
        }

        $fupService = app(FupService::class);
        $success = $fupService->restoreCustomerSpeed($customer, true);

        if ($success) {
            Notification::make()
                ->title('Kecepatan Normal Dipulihkan!')
                ->body("Kecepatan pelanggan [{$customer->name}] telah dikembalikan ke kecepatan normal paket.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Memulihkan Kecepatan')
                ->body('Tidak dapat menghubungi router MikroTik.')
                ->danger()
                ->send();
        }

        $this->loadData();
    }

    public function kickSession(string $username): void
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
            $service->kickPppActive($username);
            $service->disconnect();
            Notification::make()->title("Sesi user '{$username}' berhasil diputuskan")->warning()->send();
            $this->loadData();
        }
    }

    public function toggleIsolir(string $username, string $currentProfile): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $isolirProfile = IspSetting::get('isolir_profile', 'PROFIL-ISOLIR');
        $isCurrentlyIsolated = stripos($currentProfile, 'isolir') !== false;

        if ($isCurrentlyIsolated) {
            // Restore to original package or default
            $customer = Customer::where('pppoe_username', $username)->first();
            $targetProfile = $customer?->package?->mikrotik_profile ?? 'default';

            $restored = $service->activatePppCustomer($router, $username, $targetProfile);
            if ($restored) {
                if ($customer) {
                    $customer->update(['status' => 'active']);
                }
                Notification::make()
                    ->title('Status Isolir Dilepas')
                    ->body("User [{$username}] telah dipulihkan ke profil [{$targetProfile}].")
                    ->success()
                    ->send();
            }
        } else {
            // Isolate user
            $isolated = $service->isolatePppCustomer($router, $username, $isolirProfile);
            if ($isolated) {
                $customer = Customer::where('pppoe_username', $username)->first();
                if ($customer) {
                    $customer->update(['status' => 'isolated']);
                }
                Notification::make()
                    ->title('User Berhasil Diisolir')
                    ->body("User [{$username}] dialihkan ke profil [{$isolirProfile}] dan sesi aktifnya diputus.")
                    ->warning()
                    ->send();
            }
        }

        $this->loadData();
    }

    public function deleteSecret(string $username): void
    {
        if (! $this->selectedRouterId) {
            return;
        }
        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $removed = $service->removePppSecret($router, $username);

        if ($removed) {
            Notification::make()
                ->title('Secret PPPoE Dihapus')
                ->body("User [{$username}] telah dihapus dari /ppp/secret MikroTik.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Menghapus Secret PPPoE')
                ->body("Tidak dapat menghapus user [{$username}].")
                ->danger()
                ->send();
        }

        $this->loadData();
    }
}
