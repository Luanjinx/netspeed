<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Models\AdminUser;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Administrator')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                TextColumn::make('email')
                    ->label('Email Login')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                TextColumn::make('phone')
                    ->label('WhatsApp / HP')
                    ->placeholder('-')
                    ->url(fn (AdminUser $record) => $record->phone ? 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->phone)) : null, true)
                    ->color('success')
                    ->icon('heroicon-o-phone'),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (AdminUser $record) => $record->id === auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
