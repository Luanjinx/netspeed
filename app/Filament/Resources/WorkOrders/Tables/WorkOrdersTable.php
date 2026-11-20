<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['technician', 'customer']))
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. SPK')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'installation' => 'Pasang Baru',
                        'repair' => 'Perbaikan',
                        'maintenance' => 'Maintenance',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'installation' => 'success',
                        'repair' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'in_progress' => 'Proses',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_date')
                    ->label('Jadwal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('optical_power_dbm')
                    ->label('Redaman')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} dBm" : '-')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status SPK')
                    ->options([
                        'pending' => 'Menunggu Teknisi',
                        'in_progress' => 'Sedang Dikerjakan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
                SelectFilter::make('type')
                    ->label('Tipe Pekerjaan')
                    ->options([
                        'installation' => 'Pasang Baru',
                        'repair' => 'Perbaikan Gangguan',
                        'maintenance' => 'Pemeliharaan',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
