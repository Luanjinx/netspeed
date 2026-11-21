<?php

namespace App\Filament\Resources\Promos\Tables;

use App\Models\Promo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order', 'asc')
            ->columns([
                ImageColumn::make('image')
                    ->label('Banner')
                    ->disk('public')
                    ->width(110)
                    ->height(55)
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 8px;']),

                TextColumn::make('title')
                    ->label('Judul Promo')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Promo $record) => $record->subtitle),

                TextColumn::make('badge_text')
                    ->label('Badge')
                    ->badge()
                    ->color('warning')
                    ->placeholder('-'),

                TextColumn::make('action_type')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'none' => 'Hanya Iklan',
                        'package' => 'Pilih Paket',
                        'register' => 'Pasang Baru',
                        'whatsapp' => 'Chat CS WA',
                        'url' => 'Buka Web',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'package', 'register' => 'success',
                        'whatsapp' => 'info',
                        'url' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('starts_at')
                    ->label('Jadwal Tayang')
                    ->formatStateUsing(function (Promo $record) {
                        if (! $record->starts_at && ! $record->ends_at) {
                            return 'Selamanya';
                        }
                        $start = $record->starts_at ? $record->starts_at->format('d/m/y') : 'Sekarang';
                        $end = $record->ends_at ? $record->ends_at->format('d/m/y') : 'Selamanya';

                        return "{$start} - {$end}";
                    })
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('action_type')
                    ->label('Filter Aksi')
                    ->options([
                        'none' => 'Hanya Iklan',
                        'package' => 'Pilih Paket',
                        'register' => 'Pasang Baru',
                        'whatsapp' => 'Chat CS WA',
                        'url' => 'Buka Web',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
