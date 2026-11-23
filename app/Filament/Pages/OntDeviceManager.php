<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\GenieacsServer;
use App\Services\Genieacs\GenieacsApiService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class OntDeviceManager extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'ont-manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|UnitEnum|null $navigationGroup = 'Jaringan';

    protected static ?string $navigationLabel = 'Modem ONT';

    protected static ?string $title = 'Modem ONT Pelanggan (TR-069)';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.ont-device-manager';

    public ?int $selectedServerId = null;

    public string $searchQuery = '';

    public string $statusFilter = 'all'; // 'all', 'online', 'offline', 'critical_rx'

    public string $activeTab = 'registered'; // 'registered', 'unassigned'

    public array $stats = [
        'total' => 0,
        'online' => 0,
        'offline' => 0,
        'critical' => 0,
        'unassigned' => 0,
    ];

    public array $registeredDevices = [];

    public array $unassignedDevices = [];

    // GenieACS Connection State
    public bool $isGenieacsConnected = false;

    public ?string $activeServerUrl = null;

    // Server Config Modal States
    public bool $showServerModal = false;

    public string $serverName = 'GenieACS STB Armbian';

    public string $serverUrl = 'http://127.0.0.1:7557';

    public ?string $serverUsername = null;

    public ?string $serverPassword = null;

    public ?string $serverToken = null;

    public bool $serverIsActive = true;

    public ?string $serverTestStatus = null; // 'success', 'error', null

    public ?string $serverTestMessage = null;

    public bool $isTestingConnection = false;

    // Diagnostic Modal States
    public bool $showDiagnosticModal = false;

    public array $diagnosticData = [];

    public bool $loadingDiagnostic = false;

    // Wi-Fi Modal States
    public bool $showWifiModal = false;

    public ?int $wifiCustomerId = null;

    public string $wifiCustomerName = '';

    public string $wifiSsid = '';

    public string $wifiPassword = '';

    public bool $savingWifi = false;

    // Assign Device Modal States
    public bool $showAssignModal = false;

    public string $assignDeviceId = '';

    public string $assignDeviceModel = '';

    public ?int $targetCustomerId = null;

    public function mount(): void
    {
        $defaultServer = GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first();
        if ($defaultServer) {
            $this->selectedServerId = $defaultServer->id;
            $this->activeServerUrl = $defaultServer->base_url;
            $this->serverName = $defaultServer->name;
            $this->serverUrl = $defaultServer->base_url;
            $this->serverUsername = $defaultServer->username;
            $this->serverPassword = $defaultServer->password;
            $this->serverToken = $defaultServer->auth_token;
            $this->serverIsActive = (bool) $defaultServer->is_active;
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $genieacs = app(GenieacsApiService::class);
        $server = $this->selectedServerId ? GenieacsServer::find($this->selectedServerId) : (GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first());

        // 1. Load Registered Customers with ONTs
        $customerQuery = Customer::query()
            ->with(['package', 'odp', 'genieacsServer'])
            ->where(function ($q) {
                $q->whereNotNull('genieacs_device_id')
                    ->orWhereNotNull('ont_serial_number');
            });

        if ($this->selectedServerId) {
            $customerQuery->where('genieacs_server_id', $this->selectedServerId);
        }

        $customers = $customerQuery->get();

        // Query devices from GenieACS if server is available
        $genieacsDevicesMap = [];
        $rawDevices = [];

        if ($server && $server->is_active && ! empty($server->base_url)) {
            $this->activeServerUrl = $server->base_url;
            $testRes = $genieacs->testConnection($server);
            $this->isGenieacsConnected = ! empty($testRes['success']);

            if ($this->isGenieacsConnected) {
                $rawDevices = $genieacs->getDevices($server, null, 100);
                foreach ($rawDevices as $dev) {
                    $devId = data_get($dev, '_id');
                    if ($devId) {
                        $genieacsDevicesMap[$devId] = $dev;
                    }
                }
            }
        } else {
            $this->isGenieacsConnected = false;
        }

        $registeredList = [];
        $onlineCount = 0;
        $offlineCount = 0;
        $criticalCount = 0;

        foreach ($customers as $cust) {
            $devId = $cust->genieacs_device_id ?? $cust->ont_serial_number;
            $liveDevice = $devId ? ($genieacsDevicesMap[$devId] ?? null) : null;

            // Extract status & optical power
            $lastInform = data_get($liveDevice, '_lastInform');
            $isOnline = false;
            $lastInformHuman = 'Belum pernah inform';

            if ($lastInform) {
                $diffSeconds = Carbon::parse($lastInform)->diffInSeconds(now());
                $isOnline = $diffSeconds <= 600; // 10 minutes threshold
                $lastInformHuman = Carbon::parse($lastInform)->diffForHumans();
            }

            // Optical RX power parsing
            $rxPower = '-';
            $rxValue = null;
            $txPower = '-';

            if ($liveDevice && $server) {
                $optical = $genieacs->getOpticalPower($server, $devId);
                $rxPower = $optical['rx_power'] ?? '-';
                $txPower = $optical['tx_power'] ?? '-';
                $rxValue = $optical['rx_value'] ?? null;
            }

            // Redaman kritis flag (< -27.00 dBm)
            $isCriticalRx = false;
            if ($rxValue !== null && $rxValue < -27.0) {
                $isCriticalRx = true;
                $criticalCount++;
            }

            if ($isOnline) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }

            // Extract Model & Wi-Fi
            $manufacturer = data_get($liveDevice, 'DeviceID.Manufacturer._value')
                ?? data_get($liveDevice, 'InternetGatewayDevice.DeviceInfo.Manufacturer._value', 'ZTE / Huawei');

            $productClass = data_get($liveDevice, 'DeviceID.ProductClass._value')
                ?? data_get($liveDevice, 'InternetGatewayDevice.DeviceInfo.ModelName._value', 'ONT GPON');

            $ssid = data_get($liveDevice, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID._value')
                ?? $cust->wifi_ssid
                ?? '-';

            $wanIp = data_get($liveDevice, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress._value')
                ?? data_get($liveDevice, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress._value')
                ?? $cust->ip_address
                ?? '-';

            $item = [
                'customer_id' => $cust->id,
                'customer_name' => $cust->name,
                'customer_number' => $cust->customer_number,
                'package_name' => $cust->package?->name ?? 'Tanpa Paket',
                'odp_name' => $cust->odp?->name ?? 'ODP Tidak Terdata',
                'odp_port' => $cust->odp_port ? 'Port '.$cust->odp_port : '',
                'device_id' => $devId,
                'model' => $productClass,
                'manufacturer' => $manufacturer,
                'is_online' => $isOnline,
                'last_inform' => $lastInformHuman,
                'rx_power' => $rxPower,
                'rx_raw' => $rxValue,
                'tx_power' => $txPower,
                'is_critical_rx' => $isCriticalRx,
                'wifi_ssid' => $ssid,
                'wan_ip' => $wanIp,
            ];

            // Filter logic
            if ($this->statusFilter === 'online' && ! $isOnline) {
                continue;
            }
            if ($this->statusFilter === 'offline' && $isOnline) {
                continue;
            }
            if ($this->statusFilter === 'critical_rx' && ! $isCriticalRx) {
                continue;
            }

            if (! empty($this->searchQuery)) {
                $q = strtolower($this->searchQuery);
                $match = str_contains(strtolower($cust->name), $q)
                    || str_contains(strtolower($cust->customer_number), $q)
                    || str_contains(strtolower($devId), $q)
                    || str_contains(strtolower($ssid), $q);
                if (! $match) {
                    continue;
                }
            }

            $registeredList[] = $item;
        }

        // 2. Identify Unassigned ONTs
        $unassignedList = [];
        $registeredDevIds = Customer::whereNotNull('genieacs_device_id')
            ->pluck('genieacs_device_id')
            ->merge(Customer::whereNotNull('ont_serial_number')->pluck('ont_serial_number'))
            ->filter()
            ->all();

        foreach ($rawDevices as $raw) {
            $id = data_get($raw, '_id');
            if (! $id || in_array($id, $registeredDevIds)) {
                continue;
            }

            $mfg = data_get($raw, 'DeviceID.Manufacturer._value')
                ?? data_get($raw, 'InternetGatewayDevice.DeviceInfo.Manufacturer._value', 'Vendor');
            $model = data_get($raw, 'DeviceID.ProductClass._value')
                ?? data_get($raw, 'InternetGatewayDevice.DeviceInfo.ModelName._value', 'Modem ONT');
            $ip = data_get($raw, '_ip', '-');
            $inform = data_get($raw, '_lastInform');

            $unassignedList[] = [
                'device_id' => $id,
                'model' => $model,
                'manufacturer' => $mfg,
                'ip' => $ip,
                'last_inform' => $inform ? Carbon::parse($inform)->diffForHumans() : 'Baru saja',
            ];
        }

        $this->registeredDevices = $registeredList;
        $this->unassignedDevices = $unassignedList;

        $this->stats = [
            'total' => count($customers),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'critical' => $criticalCount,
            'unassigned' => count($unassignedList),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // =========================================================================
    // SERVER CONFIG MODAL ACTIONS
    // =========================================================================

    public function openServerConfigModal(): void
    {
        $server = GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first();
        if ($server) {
            $this->serverName = $server->name;
            $this->serverUrl = $server->base_url;
            $this->serverUsername = $server->username;
            $this->serverPassword = $server->password;
            $this->serverToken = $server->auth_token;
            $this->serverIsActive = (bool) $server->is_active;
        } else {
            $this->serverName = 'GenieACS STB Armbian';
            $this->serverUrl = 'http://127.0.0.1:7557';
        }

        $this->serverTestStatus = null;
        $this->serverTestMessage = null;
        $this->showServerModal = true;
    }

    public function closeServerConfigModal(): void
    {
        $this->showServerModal = false;
    }

    public function testServerConnection(): void
    {
        $this->isTestingConnection = true;
        $genieacs = app(GenieacsApiService::class);

        $dummy = new GenieacsServer([
            'name' => $this->serverName,
            'base_url' => rtrim($this->serverUrl, '/'),
            'username' => $this->serverUsername,
            'password' => $this->serverPassword,
            'auth_token' => $this->serverToken,
        ]);

        $res = $genieacs->testConnection($dummy);
        $this->isTestingConnection = false;

        if ($res['success']) {
            $this->serverTestStatus = 'success';
            $this->serverTestMessage = 'Koneksi Berhasil! GenieACS NBI port 7557 terhubung dengan baik.';
        } else {
            $this->serverTestStatus = 'error';
            $this->serverTestMessage = $res['message'] ?? 'Gagal terhubung. Pastikan IP dan port 7557 aktif di server STB Anda.';
        }
    }

    public function saveServerConfig(): void
    {
        $server = GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first();
        if (! $server) {
            $server = new GenieacsServer;
        }

        $server->name = $this->serverName ?: 'GenieACS STB Armbian';
        $server->base_url = rtrim($this->serverUrl, '/');
        $server->username = $this->serverUsername;
        $server->password = $this->serverPassword;
        $server->auth_token = $this->serverToken;
        $server->is_active = $this->serverIsActive;
        $server->save();

        $this->selectedServerId = $server->id;
        $this->activeServerUrl = $server->base_url;
        $this->showServerModal = false;

        Notification::make()
            ->title('Pengaturan GenieACS Disimpan')
            ->body("Koneksi ke {$server->base_url} berhasil disimpan.")
            ->success()
            ->send();

        $this->loadData();
    }

    // =========================================================================
    // REMOTE ONT DIAGNOSTIC MODAL
    // =========================================================================

    public function openDiagnostic(int $customerId): void
    {
        $this->loadingDiagnostic = true;
        $this->showDiagnosticModal = true;

        $customer = Customer::find($customerId);
        if (! $customer) {
            $this->loadingDiagnostic = false;

            return;
        }

        $devId = $customer->genieacs_device_id ?? $customer->ont_serial_number;
        if (! $devId) {
            $this->loadingDiagnostic = false;

            return;
        }

        $server = $customer->genieacsServer ?? (GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first());
        $genieacs = app(GenieacsApiService::class);

        try {
            $this->diagnosticData = $genieacs->getDeviceDiagnosticDetails($server ?? new GenieacsServer, $devId);
        } catch (\Exception $e) {
            $this->diagnosticData = [];
        }

        $this->loadingDiagnostic = false;
    }

    public function closeDiagnosticModal(): void
    {
        $this->showDiagnosticModal = false;
    }

    // =========================================================================
    // REMOTE WI-FI MODAL
    // =========================================================================

    public function openWifiModal(int $customerId): void
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return;
        }

        $this->wifiCustomerId = $customer->id;
        $this->wifiCustomerName = $customer->name;

        $devId = $customer->genieacs_device_id ?? $customer->ont_serial_number;
        $server = $customer->genieacsServer ?? (GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first());
        $genieacs = app(GenieacsApiService::class);

        $wifi = $genieacs->getWifiSettings($server ?? new GenieacsServer, $devId);
        $this->wifiSsid = $wifi['ssid'] ?? $customer->wifi_ssid ?? '';
        $this->wifiPassword = '';

        $this->showWifiModal = true;
    }

    public function closeWifiModal(): void
    {
        $this->showWifiModal = false;
    }

    public function saveWifi(): void
    {
        if (empty($this->wifiSsid)) {
            Notification::make()->title('Nama SSID tidak boleh kosong')->danger()->send();

            return;
        }

        $this->savingWifi = true;
        $customer = Customer::find($this->wifiCustomerId);
        if ($customer) {
            $devId = $customer->genieacs_device_id ?? $customer->ont_serial_number;
            $server = $customer->genieacsServer ?? (GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first());
            $genieacs = app(GenieacsApiService::class);

            $success = $genieacs->setWifiSettings(
                $server ?? new GenieacsServer,
                $devId,
                $this->wifiSsid,
                $this->wifiPassword ?: null
            );

            if ($success) {
                $customer->update(['wifi_ssid' => $this->wifiSsid]);

                Notification::make()
                    ->title('Pengaturan Wi-Fi Berhasil Dikirim!')
                    ->body("Parameter Wi-Fi modem {$customer->name} telah dikirim via TR-069.")
                    ->success()
                    ->send();

                $this->showWifiModal = false;
                $this->loadData();
            } else {
                Notification::make()
                    ->title('Gagal Mengubah Wi-Fi')
                    ->body('GenieACS tidak dapat menjangkau modem. Pastikan modem online.')
                    ->danger()
                    ->send();
            }
        }
        $this->savingWifi = false;
    }

    // =========================================================================
    // REMOTE REBOOT
    // =========================================================================

    public function rebootOnt(int $customerId): void
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return;
        }

        $devId = $customer->genieacs_device_id ?? $customer->ont_serial_number;
        $server = $customer->genieacsServer ?? (GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first());
        $genieacs = app(GenieacsApiService::class);

        $ok = $genieacs->rebootDevice($server ?? new GenieacsServer, $devId);
        if ($ok) {
            Notification::make()
                ->title('Perintah Reboot Dikirim!')
                ->body("Modem pelanggan {$customer->name} sedang melakukan restart.")
                ->success()
                ->send();
            $this->loadData();
        } else {
            Notification::make()
                ->title('Gagal Reboot Modem')
                ->body('Perintah reboot gagal dieksekusi. Pastikan modem sedang online.')
                ->danger()
                ->send();
        }
    }

    // =========================================================================
    // PAIR UNASSIGNED ONT TO CUSTOMER
    // =========================================================================

    public function openAssignModal(string $deviceId, string $model): void
    {
        $this->assignDeviceId = $deviceId;
        $this->assignDeviceModel = $model;
        $this->targetCustomerId = null;
        $this->showAssignModal = true;
    }

    public function assignDevice(): void
    {
        if (! $this->targetCustomerId) {
            Notification::make()->title('Pilih pelanggan terlebih dahulu')->warning()->send();

            return;
        }

        $customer = Customer::find($this->targetCustomerId);
        if ($customer) {
            $customer->update([
                'genieacs_device_id' => $this->assignDeviceId,
                'ont_serial_number' => $this->assignDeviceId,
                'genieacs_server_id' => $this->selectedServerId,
            ]);

            Notification::make()
                ->title('Modem Berhasil Dipasangkan!')
                ->body("Modem ONT {$this->assignDeviceId} telah dihubungkan ke pelanggan {$customer->name}.")
                ->success()
                ->send();

            $this->showAssignModal = false;
            $this->loadData();
        }
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
    }

    public function getCustomerOptionsProperty(): Collection
    {
        return Customer::whereNull('genieacs_device_id')
            ->orderBy('name')
            ->get(['id', 'name', 'customer_number']);
    }

    public function getServerOptionsProperty(): Collection
    {
        return GenieacsServer::where('is_active', true)->get(['id', 'name', 'base_url']);
    }

    protected function getHeaderActions(): array
    {
        $server = GenieacsServer::where('is_active', true)->first() ?? GenieacsServer::first();
        $uiPort = '3000';
        $uiHost = '127.0.0.1';

        if ($server && ! empty($server->base_url)) {
            $parsed = parse_url($server->base_url);
            $uiHost = $parsed['host'] ?? '127.0.0.1';
        }

        return [
            Action::make('configureServer')
                ->label('Hubungkan Server GenieACS')
                ->icon('heroicon-o-cpu-chip')
                ->color('primary')
                ->action(fn () => $this->openServerConfigModal()),

            Action::make('refreshData')
                ->label('Segarkan')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->loadData();
                    Notification::make()->title('Data Diperbarui')->success()->send();
                }),

            Action::make('openNativeGenieacs')
                ->label('Port 3000')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url("http://{$uiHost}:{$uiPort}", shouldOpenInNewTab: true),
        ];
    }
}
