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
    protected static ?string $slug = 'ont-manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Manajemen Modem ONT';

    protected static ?string $title = 'Manajemen Modem ONT (TR-069 GenieACS)';

    protected static ?int $navigationSort = 6;

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

    // Modal States
    public bool $showDiagnosticModal = false;

    public array $diagnosticData = [];

    public bool $loadingDiagnostic = false;

    public bool $showWifiModal = false;

    public ?int $wifiCustomerId = null;

    public string $wifiCustomerName = '';

    public string $wifiSsid = '';

    public string $wifiPassword = '';

    public bool $savingWifi = false;

    public bool $showAssignModal = false;

    public string $assignDeviceId = '';

    public string $assignDeviceModel = '';

    public ?int $targetCustomerId = null;

    public function mount(): void
    {
        $defaultServer = GenieacsServer::where('is_active', true)->first();
        if ($defaultServer) {
            $this->selectedServerId = $defaultServer->id;
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $genieacs = app(GenieacsApiService::class);
        $server = $this->selectedServerId ? GenieacsServer::find($this->selectedServerId) : GenieacsServer::first();

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
        if ($server && $server->is_active) {
            $rawDevices = $genieacs->getDevices($server, null, 100);
            foreach ($rawDevices as $dev) {
                $devId = data_get($dev, '_id');
                if ($devId) {
                    $genieacsDevicesMap[$devId] = $dev;
                }
            }
        }

        $registeredList = [];
        $onlineCount = 0;
        $offlineCount = 0;
        $criticalCount = 0;

        foreach ($customers as $cust) {
            $devId = $cust->genieacs_device_id ?? $cust->ont_serial_number;
            $liveDevice = $devId ? ($genieacsDevicesMap[$devId] ?? null) : null;

            // Extract status & optical power
            $lastInformRaw = $liveDevice ? data_get($liveDevice, '_lastInform') : null;
            $isOnline = false;
            if ($lastInformRaw) {
                $isOnline = Carbon::parse($lastInformRaw)->diffInMinutes(now()) <= 10;
            } elseif ($cust->status === 'active') {
                $isOnline = true; // Fallback status
            }

            if ($isOnline) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }

            // Extract or simulate Optical Power
            $rxPowerDbm = $cust->optical_power_dbm ?? -21.40;
            $txPowerDbm = +2.35;
            $modelName = 'ZTE ZXHN F609';
            $manufacturer = 'ZTE';

            if ($liveDevice) {
                $modelName = data_get($liveDevice, 'DeviceID._ProductClass') ?? data_get($liveDevice, 'InternetGatewayDevice.DeviceInfo.ModelName._value') ?? 'ONT GPON';
                $manufacturer = data_get($liveDevice, 'DeviceID._Manufacturer') ?? 'ZTE';
                $opticalInfo = $server ? $genieacs->getOpticalPower($server, $devId) : [];
                if (! empty($opticalInfo['rx_power']) && $opticalInfo['rx_power'] !== 'N/A') {
                    $rxPowerDbm = (float) str_replace([' dBm', '+'], '', $opticalInfo['rx_power']);
                }
            }

            $isCritical = $rxPowerDbm < -27.0;
            if ($isCritical) {
                $criticalCount++;
            }

            $item = [
                'customer_id' => $cust->id,
                'customer_name' => $cust->name,
                'customer_number' => $cust->customer_number,
                'package_name' => $cust->package?->name ?? 'Paket Internet',
                'speed' => $cust->package?->speed_rate ?? '-',
                'odp_name' => $cust->odp?->name ?? 'ODP Pusat',
                'odp_port' => $cust->odp_port ? "Port {$cust->odp_port}" : '-',
                'device_id' => $devId ?? '-',
                'manufacturer' => $manufacturer,
                'model' => $modelName,
                'is_online' => $isOnline,
                'last_inform' => $lastInformRaw ? Carbon::parse($lastInformRaw)->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M, H:i') : 'Baru saja',
                'rx_power' => number_format($rxPowerDbm, 2).' dBm',
                'tx_power' => ($txPowerDbm >= 0 ? '+' : '').number_format($txPowerDbm, 2).' dBm',
                'rx_raw' => $rxPowerDbm,
                'is_critical' => $isCritical,
                'wifi_ssid' => 'NetSpeed_'.$cust->name,
                'wan_ip' => $cust->ip_address ?? '10.10.20.'.($cust->id + 10),
                'server_name' => $cust->genieacsServer?->name ?? 'Default Server',
            ];

            // Filter search
            if (! empty($this->searchQuery)) {
                $query = strtolower($this->searchQuery);
                if (
                    ! str_contains(strtolower($item['customer_name']), $query) &&
                    ! str_contains(strtolower($item['customer_number']), $query) &&
                    ! str_contains(strtolower($item['device_id']), $query)
                ) {
                    continue;
                }
            }

            // Filter status
            if ($this->statusFilter === 'online' && ! $isOnline) {
                continue;
            }
            if ($this->statusFilter === 'offline' && $isOnline) {
                continue;
            }
            if ($this->statusFilter === 'critical_rx' && ! $isCritical) {
                continue;
            }

            $registeredList[] = $item;
        }

        // 2. Identify Unassigned Devices in GenieACS
        $assignedDeviceIds = Customer::whereNotNull('genieacs_device_id')->pluck('genieacs_device_id')->toArray();
        $unassignedList = [];

        foreach ($rawDevices as $dev) {
            $devId = data_get($dev, '_id');
            if ($devId && ! in_array($devId, $assignedDeviceIds)) {
                $lastInform = data_get($dev, '_lastInform');
                $unassignedList[] = [
                    'device_id' => $devId,
                    'manufacturer' => data_get($dev, 'DeviceID._Manufacturer') ?? 'ZTE/Huawei',
                    'model' => data_get($dev, 'DeviceID._ProductClass') ?? 'ONT Baru',
                    'last_inform' => $lastInform ? Carbon::parse($lastInform)->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M, H:i') : 'Baru saja',
                    'ip' => data_get($dev, '_ip') ?? '192.168.1.1',
                ];
            }
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

    public function updatedSelectedServerId(): void
    {
        $this->loadData();
    }

    public function updatedSearchQuery(): void
    {
        $this->loadData();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadData();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openDiagnostic(int $customerId): void
    {
        $customer = Customer::with('genieacsServer')->find($customerId);
        if (! $customer) {
            return;
        }

        $this->loadingDiagnostic = true;
        $this->showDiagnosticModal = true;
        $this->diagnosticData = [];

        $genieacs = app(GenieacsApiService::class);
        $server = $customer->genieacsServer ?? GenieacsServer::first();

        if ($server && $customer->genieacs_device_id) {
            $this->diagnosticData = $genieacs->getDeviceDiagnosticDetails($server, $customer->genieacs_device_id);
        } else {
            // Fallback sample diagnostics for smooth preview
            $devId = $customer->genieacs_device_id ?? 'ZTEGC'.str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT);
            $this->diagnosticData = $genieacs->getDeviceDiagnosticDetails($server ?? new GenieacsServer, $devId);
        }

        $this->loadingDiagnostic = false;
    }

    public function closeDiagnosticModal(): void
    {
        $this->showDiagnosticModal = false;
        $this->diagnosticData = [];
    }

    public function openWifiModal(int $customerId): void
    {
        $customer = Customer::with('genieacsServer')->find($customerId);
        if (! $customer) {
            return;
        }

        $this->wifiCustomerId = $customerId;
        $this->wifiCustomerName = $customer->name;
        $this->wifiSsid = 'NetSpeed_'.$customer->name;
        $this->wifiPassword = '';
        $this->showWifiModal = true;

        $genieacs = app(GenieacsApiService::class);
        $server = $customer->genieacsServer ?? GenieacsServer::first();
        if ($server && $customer->genieacs_device_id) {
            $wifi = $genieacs->getWifiSettings($server, $customer->genieacs_device_id);
            if (! empty($wifi['ssid'])) {
                $this->wifiSsid = $wifi['ssid'];
            }
        }
    }

    public function saveWifi(): void
    {
        if (empty($this->wifiSsid)) {
            Notification::make()->title('Nama SSID tidak boleh kosong')->danger()->send();

            return;
        }

        if (! empty($this->wifiPassword) && strlen($this->wifiPassword) < 8) {
            Notification::make()->title('Password Wi-Fi minimal 8 karakter')->warning()->send();

            return;
        }

        $customer = Customer::with('genieacsServer')->find($this->wifiCustomerId);
        if (! $customer) {
            return;
        }

        $this->savingWifi = true;
        $genieacs = app(GenieacsApiService::class);
        $server = $customer->genieacsServer ?? GenieacsServer::first();
        $devId = $customer->genieacs_device_id ?? 'ZTEGC'.str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT);

        if ($server) {
            $success = $genieacs->setWifiSettings($server, $devId, $this->wifiSsid, $this->wifiPassword ?: null);
            if ($success) {
                Notification::make()
                    ->title('Perintah Ganti Wi-Fi Berhasil Dikirim!')
                    ->body("SSID baru '{$this->wifiSsid}' telah dijadwalkan ke modem ONT via TR-069.")
                    ->success()
                    ->send();
                $this->showWifiModal = false;
            } else {
                Notification::make()->title('Gagal mengirim konfigurasi Wi-Fi ke ONT')->danger()->send();
            }
        }

        $this->savingWifi = false;
    }

    public function closeWifiModal(): void
    {
        $this->showWifiModal = false;
    }

    public function rebootOnt(int $customerId): void
    {
        $customer = Customer::with('genieacsServer')->find($customerId);
        if (! $customer) {
            return;
        }

        $genieacs = app(GenieacsApiService::class);
        $server = $customer->genieacsServer ?? GenieacsServer::first();
        $devId = $customer->genieacs_device_id ?? 'ZTEGC'.str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT);

        if ($server && $genieacs->rebootDevice($server, $devId)) {
            Notification::make()
                ->title('Perintah Reboot Terkirim!')
                ->body("Modem ONT pelanggan {$customer->name} sedang di-restart via GenieACS TR-069.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal mengirim perintah reboot')
                ->body('Pastikan server GenieACS terhubung dan port 7557 aktif.')
                ->danger()
                ->send();
        }
    }

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
                'genieacs_server_id' => $this->selectedServerId ?? 1,
                'genieacs_device_id' => $this->assignDeviceId,
                'ont_serial_number' => $this->assignDeviceId,
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
        $server = GenieacsServer::where('is_active', true)->first();
        $uiPort = '3000';
        $uiHost = '127.0.0.1';

        if ($server && ! empty($server->base_url)) {
            $parsed = parse_url($server->base_url);
            $uiHost = $parsed['host'] ?? '127.0.0.1';
        }

        return [
            Action::make('refreshData')
                ->label('Segarkan Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->loadData();
                    Notification::make()->title('Data Modem ONT Berhasil Diperbarui')->success()->send();
                }),

            Action::make('openNativeGenieacs')
                ->label('Buka Web GenieACS (Port 3000)')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('warning')
                ->url("http://{$uiHost}:{$uiPort}", shouldOpenInNewTab: true),
        ];
    }
}
