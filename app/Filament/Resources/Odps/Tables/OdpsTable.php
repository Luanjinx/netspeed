<?php

namespace App\Filament\Resources\Odps\Tables;

use App\Models\Odp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OdpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('customers'))
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('code')
                    ->label('Kode ODP')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Nama ODP')
                    ->searchable(),
                TextColumn::make('splitter_ratio')
                    ->label('Splitter')
                    ->badge(),
                TextColumn::make('port_usage')
                    ->label('Kapasitas Port')
                    ->state(function (Odp $record): string {
                        $used = $record->customers_count ?? $record->customers()->count();

                        return "{$used} / {$record->total_ports} Port";
                    })
                    ->badge()
                    ->color(function (Odp $record): string {
                        $used = $record->customers_count ?? $record->customers()->count();
                        if ($used >= $record->total_ports) {
                            return 'danger';
                        } elseif ($used >= $record->total_ports * 0.75) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'full' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('address')
                    ->label('Lokasi Tiang')
                    ->limit(35)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
