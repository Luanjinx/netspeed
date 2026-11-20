<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ontDiagnostics')
                ->label('Diagnostik ONT')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->visible(fn () => ! empty($this->record->genieacs_device_id) && $this->record->genieacs_server_id)
                ->modalHeading(fn () => "Diagnostik & Status ONT — {$this->record->name}")
                ->modalDescription(fn () => "No. Pelanggan: {$this->record->customer_number} | Device ID: {$this->record->genieacs_device_id}")
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function (GenieacsApiService $genieacs) {
                    $server = $this->record->genieacsServer;
                    $data = $server
                        ? $genieacs->getDeviceDiagnosticDetails($server, $this->record->genieacs_device_id)
                        : ['success' => false, 'message' => 'Server GenieACS belum dikonfigurasi untuk pelanggan ini.'];

                    return view('filament.modals.ont-diagnostics', [
                        'customer' => $this->record,
                        'server' => $server,
                        'data' => $data,
                    ]);
                }),

            Action::make('manageWifi')
                ->label('Wi-Fi ONT')
                ->icon('heroicon-o-wifi')
                ->color('warning')
                ->visible(fn () => ! empty($this->record->genieacs_device_id) && $this->record->genieacs_server_id)
                ->modalHeading(fn () => "Kelola Wi-Fi ONT — {$this->record->name}")
                ->modalDescription('Ubah nama SSID dan password WPA2 modem pelanggan secara langsung via TR-069 tanpa membuka web GUI modem.')
                ->fillForm(function (GenieacsApiService $genieacs): array {
                    $server = $this->record->genieacsServer;
                    $ssid = '';
                    $password = '';
                    if ($server && $this->record->genieacs_device_id) {
                        $wifi = $genieacs->getWifiSettings($server, $this->record->genieacs_device_id);
                        if ($wifi['success']) {
                            $ssid = ($wifi['ssid'] !== 'N/A') ? $wifi['ssid'] : '';
                            $password = ($wifi['password'] !== 'N/A') ? $wifi['password'] : '';
                        }
                    }

                    return [
                        'ssid' => $ssid ?: 'NETSPEED - '.strtoupper(explode(' ', $this->record->name)[0]),
                        'password' => $password ?: 'wifi123456',
                        'reboot_now' => false,
                    ];
                })
                ->form([
                    TextInput::make('ssid')
                        ->label('Nama Jaringan Wi-Fi (SSID)')
                        ->required()
                        ->maxLength(32)
                        ->helperText('Nama SSID Wi-Fi yang dipancarkan oleh modem ONT pelanggan.'),
                    TextInput::make('password')
                        ->label('Password Wi-Fi Baru (WPA2-PSK)')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->helperText('Minimal 8 karakter.'),
                    Toggle::make('reboot_now')
                        ->label('Reboot ONT Setelah Update')
                        ->helperText('Sebagian modem memerlukan restart singkat (1-2 menit) agar setelan Wi-Fi segera aktif.')
                        ->default(false),
                ])
                ->action(function (array $data, GenieacsApiService $genieacs) {
                    $server = $this->record->genieacsServer;
                    if (! $server) {
                        Notification::make()->title('Server GenieACS tidak ditemukan')->danger()->send();

                        return;
                    }

                    $success = $genieacs->setWifiSettings($server, $this->record->genieacs_device_id, $data['ssid'], $data['password']);
                    if ($success) {
                        if (! empty($data['reboot_now'])) {
                            $genieacs->rebootDevice($server, $this->record->genieacs_device_id);
                        }
                        Notification::make()
                            ->title('Pengaturan Wi-Fi Berhasil Disimpan!')
                            ->body("SSID baru '{$data['ssid']}' telah dikirim ke ONT via TR-069.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Mengubah Pengaturan Wi-Fi')
                            ->body('ONT tidak merespons atau server TR-069 sedang offline.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('rebootOnt')
                ->label('Reboot ONT')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reboot Modem ONT Pelanggan?')
                ->modalDescription('Modem ONT pelanggan akan dimuat ulang via perintah TR-069. Koneksi internet akan terputus selama 1-2 menit.')
                ->visible(fn () => ! empty($this->record->genieacs_device_id) && $this->record->genieacs_server_id)
                ->action(function (GenieacsApiService $genieacs) {
                    $server = $this->record->genieacsServer;
                    if ($server && $genieacs->rebootDevice($server, $this->record->genieacs_device_id)) {
                        Notification::make()
                            ->title('Perintah Reboot Berhasil Dikirim!')
                            ->body('Modem ONT sedang melakukan proses restart.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal mengirim perintah reboot')
                            ->body('Server TR-069 sedang offline atau ONT tidak merespons.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('liveTraffic')
                ->label('Trafik & Sesi Live')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->visible(fn () => $this->record->connection_type === 'pppoe' && $this->record->router_id && ! empty($this->record->pppoe_username))
                ->modalHeading(fn () => "Monitoring Trafik Live — {$this->record->name}")
                ->modalDescription(fn () => "User PPPoE: {$this->record->pppoe_username} | Router: ".($this->record->router?->name ?? 'MikroTik'))
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function (MikrotikApiService $mikrotik) {
                    return view('filament.modals.customer-traffic', [
                        'customer' => $this->record,
                        'data' => $mikrotik->getCustomerLiveTraffic($this->record),
                    ]);
                })
                ->extraModalActions([
                    Action::make('kickSession')
                        ->label('Putuskan Sesi (Kick)')
                        ->color('danger')
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->requiresConfirmation()
                        ->modalHeading('Putuskan Sesi PPPoE?')
                        ->modalDescription('Sesi koneksi pelanggan akan diputus dari MikroTik dan pelanggan akan dialihkan untuk login ulang otomatis.')
                        ->action(function (MikrotikApiService $mikrotik) {
                            $mikrotik->kickCustomerSession($this->record);
                            Notification::make()
                                ->title('Sesi PPPoE Berhasil Diputus')
                                ->body("User {$this->record->pppoe_username} telah di-disconnect dari MikroTik.")
                                ->warning()
                                ->send();
                        }),
                ]),

            Action::make('syncToMikrotik')
                ->label('Sinkron ke MikroTik')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $service = app(MikrotikApiService::class);
                    $res = $service->syncCustomerToMikrotik($this->record);
                    if ($res['success']) {
                        Notification::make()->title('Sukses Sinkron')->body($res['message'])->success()->send();
                    } else {
                        Notification::make()->title('Gagal Sinkron')->body($res['message'])->danger()->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $customer = $this->record;
        if ($customer->connection_type === 'pppoe' && $customer->router_id && ! empty($customer->pppoe_username)) {
            $service = app(MikrotikApiService::class);
            $res = $service->syncCustomerToMikrotik($customer);
            if ($res['success']) {
                Notification::make()
                    ->title('PPPoE MikroTik Terupdate')
                    ->body($res['message'])
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Peringatan MikroTik')
                    ->body($res['message'])
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
