<?php

namespace App\Filament\Resources\Technicians\Tables;

use App\Models\Technician;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TechniciansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'workOrders as active_orders_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress']),
            ]))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('technician_code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Teknisi')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->url(fn (Technician $record) => 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->phone)), true)
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('work_area')
                    ->label('Wilayah Operasional')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                TextColumn::make('active_orders_count')
                    ->label('Tugas Berjalan')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
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
