<?php

namespace App\Filament\Resources\GenieacsServers\Tables;

use App\Models\GenieacsServer;
use App\Services\Genieacs\GenieacsApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GenieacsServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Server')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('base_url')
                    ->label('NBI URL')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'error' => 'warning',
                        default => 'danger',
                    }),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_checked_at')
                    ->label('Pengecekan Terakhir')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum pernah'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('testGenieacs')
                    ->label('Test Koneksi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (GenieacsServer $record, GenieacsApiService $service) {
                        $result = $service->testConnection($record);
                        if ($result['success']) {
                            Notification::make()
                                ->title('Koneksi GenieACS Berhasil')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Koneksi GenieACS Gagal')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
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
