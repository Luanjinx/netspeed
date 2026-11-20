<?php

namespace App\Filament\Resources\HotspotVouchers\Tables;

use App\Models\HotspotVoucher;
use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HotspotVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['router']))
            ->defaultPaginationPageOption(15)
            ->paginationPageOptions([15, 25, 50])
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Voucher')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Kode voucher berhasil disalin!'),

                TextColumn::make('password')
                    ->label('Password')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('router.name')
                    ->label('Router Hotspot')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('profile')
                    ->label('Profil MikroTik')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('validity')
                    ->label('Masa Aktif')
                    ->badge()
                    ->color('info'),

                TextColumn::make('shared_users')
                    ->label('Shared Users')
                    ->badge()
                    ->color(fn (int $state): string => $state > 1 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (int $state): string => "{$state} Device".($state > 1 ? 's' : ''))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unused' => 'success',
                        'used' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unused' => 'Belum Terpakai',
                        'used' => 'Sudah Dipakai',
                        default => 'Kadaluarsa',
                    }),

                TextColumn::make('used_by_mac')
                    ->label('MAC Login')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('used_at')
                    ->label('Waktu Dipakai')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('generated_batch')
                    ->label('Batch ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('router_id')
                    ->label('Filter Router')
                    ->relationship('router', 'name'),

                SelectFilter::make('profile')
                    ->label('Filter Profil Hotspot')
                    ->options(fn () => HotspotVoucher::distinct()->pluck('profile', 'profile')->toArray()),

                SelectFilter::make('shared_users')
                    ->label('Filter Shared Users')
                    ->options([
                        1 => '1 Perangkat (Single)',
                        2 => '2 Perangkat',
                        3 => '3 Perangkat',
                        4 => '4 Perangkat',
                        5 => '5 Perangkat',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'unused' => 'Belum Terpakai',
                        'used' => 'Sudah Dipakai',
                        'expired' => 'Kadaluarsa',
                    ]),
            ])
            ->recordActions([
                // Cetak Satuan (Struk Thermal / Mini Card)
                Action::make('printSingle')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (HotspotVoucher $record) => route('print.voucher.single', $record->id), true),

                // Cetak Lembar Batch (jika bagian dari batch)
                Action::make('printBatch')
                    ->label('Cetak Batch')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('primary')
                    ->visible(fn (HotspotVoucher $record) => ! empty($record->generated_batch))
                    ->url(fn (HotspotVoucher $record) => route('print.vouchers', $record->generated_batch), true),

                // Kick / Disconnect sesi aktif voucher jika user sedang login
                Action::make('kickSession')
                    ->label('Kick Sesi')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (HotspotVoucher $record, MikrotikApiService $api) {
                        $router = $record->router;
                        if (! $router) {
                            return;
                        }

                        $kicked = $api->kickHotspotActive($router, $record->code);
                        if ($kicked) {
                            Notification::make()
                                ->title('Sesi Voucher Di-Disconnect')
                                ->body("User [{$record->code}] telah diputus dari Hotspot MikroTik.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('User Tidak Sedang Aktif')
                                ->body("User [{$record->code}] tidak ditemukan pada daftar sesi aktif hotspot MikroTik.")
                                ->info()
                                ->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->before(function (HotspotVoucher $record) {
                        $record->skip_router_deletion = true;
                        if ($record->router) {
                            $removed = app(VoucherService::class)->removeSingleVoucherFromRouter($record);
                            if (! $removed) {
                                Notification::make()
                                    ->title('Peringatan: MikroTik Tidak Merespons')
                                    ->body("Voucher [{$record->code}] dihapus dari web, namun router tidak merespons atau user sudah tidak ada di MikroTik.")
                                    ->warning()
                                    ->send();
                            }
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records) {
                            $voucherService = app(VoucherService::class);
                            $grouped = $records->groupBy('router_id');

                            foreach ($grouped as $routerId => $groupRecords) {
                                if (! $routerId) {
                                    continue;
                                }

                                $router = Router::find($routerId);
                                if ($router) {
                                    foreach ($groupRecords as $r) {
                                        $r->skip_router_deletion = true;
                                    }
                                    $codes = $groupRecords->pluck('code')->all();
                                    $result = $voucherService->removeBatchVouchersFromRouter($router, $codes);

                                    if (! ($result['success'] ?? false)) {
                                        Notification::make()
                                            ->title('Peringatan Hapus Batch MikroTik')
                                            ->body("Sebagian atau seluruh user voucher gagal dihapus di router [{$router->name}].")
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }
                        }),
                ]),
            ]);
    }
}
