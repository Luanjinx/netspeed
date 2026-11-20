<?php

namespace App\Filament\Resources\GenieacsServers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GenieacsServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Koneksi GenieACS TR-069 NBI')
                    ->description('Konfigurasi API GenieACS Northbound Interface (Mendukung server lokal maupun external/cloud VPS)')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Server GenieACS')
                            ->placeholder('cth: GenieACS Server OLT Utama')
                            ->required(),
                        TextInput::make('base_url')
                            ->label('Base URL API NBI')
                            ->placeholder('cth: http://103.123.45.67:7557 atau http://localhost:7557')
                            ->required()
                            ->url(),
                        Grid::make(2)->schema([
                            TextInput::make('username')
                                ->label('Username NBI (Opsional)'),
                            TextInput::make('password')
                                ->label('Password NBI (Opsional)')
                                ->password()
                                ->revealable(),
                        ]),
                        TextInput::make('auth_token')
                            ->label('API Bearer Token (Opsional)')
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Server Aktif')
                            ->default(true),
                        Textarea::make('notes')
                            ->label('Catatan Keterangan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
