<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use App\Services\Bandwidth\FupService;
use App\Services\Billing\BillingService;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['package', 'router', 'technician']))
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('customer_number')
                    ->label('No. Pelanggan')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->url(fn ($record) => 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->phone)), true)
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                TextColumn::make('package.name')
                    ->label('Paket')
                    ->badge()
                    ->color('info'),
                TextColumn::make('pppoe_username')
                    ->label('User PPPoE')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('router.name')
                    ->label('Router')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'isolated' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('fup_status')
                    ->label('Status FUP')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'fup_applied' ? 'FUP Throttled' : 'Normal')
                    ->color(fn (string $state): string => match ($state) {
                        'fup_applied' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('due_date_day')
                    ->label('Tgl Jatuh Tempo')
                    ->formatStateUsing(fn ($state) => "Tgl {$state}"),
                TextColumn::make('install_date')
                    ->label('Tgl Pasang')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('promo_cycles_used')
                    ->label('Siklus Promo')
                    ->formatStateUsing(function (Customer $record) {
                        if (! $record->package || ! $record->package->promo_cycles) {
                            return '-';
                        }

                        return "{$record->promo_cycles_used} / {$record->package->promo_cycles}";
                    })
                    ->badge()
                    ->color(fn (Customer $record) => ($record->package?->promo_cycles && $record->promo_cycles_used < $record->package->promo_cycles) ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('liveTraffic')
                    ->label('Trafik')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->visible(fn (Customer $record) => $record->connection_type === 'pppoe' && $record->router_id && ! empty($record->pppoe_username))
                    ->modalHeading(fn (Customer $record) => "Monitoring Trafik Live — {$record->name}")
                    ->modalDescription(fn (Customer $record) => "User PPPoE: {$record->pppoe_username} | Router: ".($record->router?->name ?? 'MikroTik'))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Customer $record, MikrotikApiService $mikrotik) {
                        return view('filament.modals.customer-traffic', [
                            'customer' => $record,
                            'data' => $mikrotik->getCustomerLiveTraffic($record),
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
                            ->action(function (Customer $record, MikrotikApiService $mikrotik) {
                                $mikrotik->kickCustomerSession($record);
                                Notification::make()
                                    ->title('Sesi PPPoE Berhasil Diputus')
                                    ->body("User {$record->pppoe_username} telah di-disconnect dari MikroTik.")
                                    ->warning()
                                    ->send();
                            }),
                    ]),

                Action::make('ontDiagnostics')
                    ->label('Diagnostik')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->visible(fn (Customer $record) => ! empty($record->genieacs_device_id) && $record->genieacs_server_id)
                    ->modalHeading(fn (Customer $record) => "Diagnostik & Status ONT — {$record->name}")
                    ->modalDescription(fn (Customer $record) => "No. Pelanggan: {$record->customer_number} | Device ID: {$record->genieacs_device_id}")
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Customer $record, GenieacsApiService $genieacs) {
                        $server = $record->genieacsServer;
                        $data = $server
                            ? $genieacs->getDeviceDiagnosticDetails($server, $record->genieacs_device_id)
                            : ['success' => false, 'message' => 'Server GenieACS belum dikonfigurasi untuk pelanggan ini.'];

                        return view('filament.modals.ont-diagnostics', [
                            'customer' => $record,
                            'server' => $server,
                            'data' => $data,
                        ]);
                    }),

                EditAction::make(),

                ActionGroup::make([
                    Action::make('manageWifi')
                        ->label('Kelola Wi-Fi')
                        ->icon('heroicon-o-wifi')
                        ->color('warning')
                        ->visible(fn (Customer $record) => ! empty($record->genieacs_device_id) && $record->genieacs_server_id)
                        ->modalHeading(fn (Customer $record) => "Kelola Wi-Fi ONT — {$record->name}")
                        ->modalDescription('Ubah nama SSID dan password WPA2 modem pelanggan secara langsung via TR-069 tanpa membuka web GUI modem.')
                        ->fillForm(function (Customer $record, GenieacsApiService $genieacs): array {
                            $server = $record->genieacsServer;
                            $ssid = '';
                            $password = '';
                            if ($server && $record->genieacs_device_id) {
                                $wifi = $genieacs->getWifiSettings($server, $record->genieacs_device_id);
                                if ($wifi['success']) {
                                    $ssid = ($wifi['ssid'] !== 'N/A') ? $wifi['ssid'] : '';
                                    $password = ($wifi['password'] !== 'N/A') ? $wifi['password'] : '';
                                }
                            }

                            return [
                                'ssid' => $ssid ?: 'NETSPEED - '.strtoupper(explode(' ', $record->name)[0]),
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
                        ->action(function (array $data, Customer $record, GenieacsApiService $genieacs) {
                            $server = $record->genieacsServer;
                            if (! $server) {
                                Notification::make()->title('Server GenieACS tidak ditemukan')->danger()->send();

                                return;
                            }

                            $success = $genieacs->setWifiSettings($server, $record->genieacs_device_id, $data['ssid'], $data['password']);
                            if ($success) {
                                if (! empty($data['reboot_now'])) {
                                    $genieacs->rebootDevice($server, $record->genieacs_device_id);
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
                        ->visible(fn (Customer $record) => ! empty($record->genieacs_device_id) && $record->genieacs_server_id)
                        ->action(function (Customer $record, GenieacsApiService $genieacs) {
                            $server = $record->genieacsServer;
                            if ($server && $genieacs->rebootDevice($server, $record->genieacs_device_id)) {
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

                    Action::make('isolateManual')
                        ->label('Isolir Pelanggan')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Customer $record) => $record->status === 'active' && $record->connection_type !== 'hotspot')
                        ->action(function (Customer $record, MikrotikApiService $mikrotik) {
                            $mikrotik->isolateCustomer($record);
                            $record->update(['status' => 'isolated']);
                            Notification::make()->title("Pelanggan {$record->name} ({$record->connection_type}) berhasil di-isolir")->warning()->send();
                        }),

                    Action::make('activateManual')
                        ->label('Buka Isolir / Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Customer $record) => $record->status === 'isolated' && $record->connection_type !== 'hotspot')
                        ->action(function (Customer $record, MikrotikApiService $mikrotik) {
                            $mikrotik->activateCustomer($record);
                            $record->update(['status' => 'active']);
                            Notification::make()->title("Pelanggan {$record->name} ({$record->connection_type}) berhasil diaktifkan kembali")->success()->send();
                        }),

                    Action::make('restoreFup')
                        ->label('Pulihkan Kecepatan FUP')
                        ->icon('heroicon-o-bolt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Pulihkan Kecepatan Pelanggan?')
                        ->modalDescription('Kecepatan internet pelanggan akan dikembalikan ke kecepatan normal paket tanpa menunggu siklus bulan depan.')
                        ->visible(fn (Customer $record) => $record->fup_status === 'fup_applied')
                        ->action(function (Customer $record, FupService $fupService) {
                            $success = $fupService->restoreCustomerSpeed($record, true);
                            if ($success) {
                                Notification::make()->title("Kecepatan normal untuk {$record->name} berhasil dipulihkan")->success()->send();
                            } else {
                                Notification::make()->title('Gagal memulihkan kecepatan di router')->danger()->send();
                            }
                        }),

                    Action::make('syncPppoe')
                        ->label('Sinkron ke MikroTik')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn (Customer $record) => $record->connection_type === 'pppoe' && $record->router_id && ! empty($record->pppoe_username))
                        ->action(function (Customer $record, MikrotikApiService $mikrotik) {
                            $res = $mikrotik->syncCustomerToMikrotik($record);
                            if ($res['success']) {
                                Notification::make()->title('Sinkron Sukses')->body($res['message'])->success()->send();
                            } else {
                                Notification::make()->title('Sinkron Gagal')->body($res['message'])->danger()->send();
                            }
                        }),

                    Action::make('resetPromoCycles')
                        ->label('Reset Siklus Promo')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn () => auth()->user()?->role === 'admin')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Siklus Promo Pelanggan?')
                        ->modalDescription('Counter promo_cycles_used akan dikembalikan ke 0 agar pelanggan dapat kembali menikmati harga promo.')
                        ->action(function (Customer $record) {
                            $record->update(['promo_cycles_used' => 0]);
                            Notification::make()
                                ->title('Siklus Promo Direset')
                                ->body("Counter promo untuk {$record->name} berhasil dikembalikan ke 0.")
                                ->success()
                                ->send();
                        }),

                    Action::make('susulanProrata')
                        ->label('Susulan Prorata Bulan Pasang')
                        ->icon('heroicon-o-calculator')
                        ->color('primary')
                        ->visible(fn () => auth()->user()?->role === 'admin')
                        ->requiresConfirmation()
                        ->modalHeading('Buat Tagihan Susulan Prorata Bulan Pasang?')
                        ->modalDescription(fn (Customer $record) => $record->install_date
                            ? "Sistem akan membuat tagihan prorata untuk bulan pasang ({$record->install_date->format('F Y')}) menggunakan harga reguler paket."
                            : 'Pelanggan belum memiliki tanggal pasang (install_date). Mohon isi tanggal pasang terlebih dahulu.')
                        ->action(function (Customer $record, BillingService $billing) {
                            try {
                                $invoice = $billing->generateRetroactiveProrataInvoice($record);
                                Notification::make()
                                    ->title('Tagihan Susulan Prorata Diterbitkan')
                                    ->body("Invoice {$invoice->invoice_number} senilai Rp ".number_format($invoice->total_amount, 0, ',', '.').' berhasil dibuat.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Gagal Membuat Tagihan Prorata')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Lainnya'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkSyncPppoe')
                        ->label('Sinkronkan PPPoE Terpilih ke MikroTik')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Collection $records, MikrotikApiService $mikrotik) {
                            $success = 0;
                            $failed = 0;
                            foreach ($records as $record) {
                                if ($record->connection_type === 'pppoe' && $record->router_id && ! empty($record->pppoe_username)) {
                                    $res = $mikrotik->syncCustomerToMikrotik($record);
                                    if ($res['success']) {
                                        $success++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }
                            Notification::make()
                                ->title('Sinkronisasi Massal Selesai')
                                ->body("Berhasil: {$success} pelanggan. Gagal: {$failed} pelanggan.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
