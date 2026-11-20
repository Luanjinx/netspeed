<?php

namespace App\Filament\Resources\Routers\Tables;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoutersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Router')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('host')
                    ->label('Host / IP')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('port')
                    ->label('Port')
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'error' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('board_name')
                    ->label('Tipe Board')
                    ->placeholder('-'),
                TextColumn::make('ros_version')
                    ->label('Versi ROS')
                    ->placeholder('-'),
                TextColumn::make('cpu_load')
                    ->label('CPU Load')
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state}%" : '-')
                    ->badge()
                    ->color(fn ($state) => $state > 75 ? 'danger' : ($state > 40 ? 'warning' : 'success')),
                TextColumn::make('uptime')
                    ->label('Uptime')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_connected_at')
                    ->label('Terakhir Terhubung')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('testConnection')
                    ->label('Test Koneksi')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Router $record, MikrotikApiService $service) {
                        $result = $service->testConnection($record);
                        if ($result['success']) {
                            Notification::make()
                                ->title('Koneksi Sukses!')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Koneksi Gagal')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('importPppoe')
                    ->label('Tarik PPPoE')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Tarik User PPPoE dari MikroTik?')
                    ->modalDescription('Sistem akan menarik seluruh user PPPoE Secret di router ini, menyinkronkan profil paket, lalu mendaftarkannya sebagai pelanggan baru atau memperbarui data akun.')
                    ->action(function (Router $record, MikrotikApiService $service) {
                        $res = $service->importPppSecretsToCustomers($record);
                        Notification::make()
                            ->title('Sinkronisasi Selesai')
                            ->body("Total dari MikroTik: {$res['total']} user.\nImport Baru: {$res['imported']} pelanggan.\nUpdate: {$res['updated']} pelanggan.")
                            ->success()
                            ->send();
                    }),

                Action::make('syncProfiles')
                    ->label('Tarik Paket/Profil')
                    ->icon('heroicon-o-tag')
                    ->color('info')
                    ->action(function (Router $record, MikrotikApiService $service) {
                        $res = $service->syncProfilesFromRouter($record);
                        Notification::make()
                            ->title('Profil Paket Disinkron')
                            ->body("Ditemukan: {$res['total_profiles']} profil.\nPaket Baru Ditambahkan: {$res['synced_packages']}.\nProfil Isolir Aktif: {$res['isolir_profile']}")
                            ->success()
                            ->send();
                    }),

                Action::make('pushAllCustomers')
                    ->label('Kirim Semua ke Router')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Seluruh Pelanggan ke MikroTik?')
                    ->modalDescription('Seluruh data pelanggan PPPoE di database pada router ini akan dikirim atau disesuaikan ke /ppp/secret MikroTik.')
                    ->action(function (Router $record, MikrotikApiService $service) {
                        $res = $service->syncAllCustomersToMikrotik($record);
                        Notification::make()
                            ->title('Push ke Router Selesai')
                            ->body("Total: {$res['total']} pelanggan.\nBerhasil: {$res['success']}\nGagal: {$res['failed']}")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
