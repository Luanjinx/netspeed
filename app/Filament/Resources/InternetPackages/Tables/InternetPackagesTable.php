<?php

namespace App\Filament\Resources\InternetPackages\Tables;

use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InternetPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->height(42)
                    ->width(75)
                    ->extraImgAttributes(['class' => 'rounded-md object-cover border border-gray-700/50 shadow-sm'])
                    ->defaultImageUrl(null)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Nama Paket')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('speed_rate')
                    ->label('Kecepatan')
                    ->badge()
                    ->color('info'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('promo_info')
                    ->label('Promo')
                    ->state(function ($record) {
                        if (! $record->promo_price || ! $record->promo_cycles) {
                            return '-';
                        }

                        return 'Rp '.number_format($record->promo_price, 0, ',', '.')." ({$record->promo_cycles} bln)";
                    })
                    ->badge()
                    ->color(fn ($record) => ($record->promo_price && $record->promo_cycles) ? 'success' : 'gray'),
                IconColumn::make('is_prorated')
                    ->label('Prorata')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mikrotik_profile')
                    ->label('Profil MikroTik')
                    ->badge(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pppoe' => 'primary',
                        'hotspot' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('shared_users')
                    ->label('Shared Users')
                    ->state(fn ($record) => $record->type === 'hotspot' ? "{$record->shared_users} Dev" : '-')
                    ->badge()
                    ->color(fn ($record) => $record->type === 'hotspot' && $record->shared_users > 1 ? 'warning' : 'gray'),
                TextColumn::make('fup_info')
                    ->label('Batas FUP')
                    ->state(function ($record) {
                        if (! $record->fup_enabled || ! $record->fup_limit_gb) {
                            return 'Unlimited';
                        }

                        return "{$record->fup_limit_gb} GB ({$record->fup_throttle_speed})";
                    })
                    ->badge()
                    ->color(fn ($record) => $record->fup_enabled ? 'warning' : 'success'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('syncProfile')
                    ->label('Kirim ke MikroTik')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => ! empty($record->mikrotik_profile) && in_array($record->type, ['pppoe', 'hotspot']))
                    ->action(function ($record, MikrotikApiService $api) {
                        $res = $api->syncInternetPackageProfile($record);
                        if ($res['success'] ?? false) {
                            Notification::make()
                                ->title('Profil Berhasil Terkirim ke MikroTik')
                                ->body($res['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengirim Profil')
                                ->body($res['message'] ?? 'Tidak dapat terhubung ke MikroTik.')
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkSyncProfiles')
                        ->label('Kirim Profil Terpilih ke MikroTik')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->action(function (Collection $records, MikrotikApiService $api) {
                            $success = 0;
                            $failed = 0;
                            foreach ($records as $record) {
                                if (! empty($record->mikrotik_profile) && in_array($record->type, ['pppoe', 'hotspot'])) {
                                    $res = $api->syncInternetPackageProfile($record);
                                    if ($res['success'] ?? false) {
                                        $success++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }
                            Notification::make()
                                ->title('Sinkronisasi Profil Massal Selesai')
                                ->body("Berhasil: {$success} paket. Gagal: {$failed} paket.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
