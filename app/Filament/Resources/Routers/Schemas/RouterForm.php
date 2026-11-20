<?php

namespace App\Filament\Resources\Routers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RouterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Router')
                    ->description('Koneksi API RouterOS MikroTik (Mendukung IP Lokal, Cloud, DDNS, atau VPN WireGuard/OpenVPN)')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Router')
                            ->placeholder('cth: MikroTik Core RB4011')
                            ->required(),
                        Grid::make(3)->schema([
                            TextInput::make('host')
                                ->label('Host / IP Address')
                                ->placeholder('cth: 192.168.88.1 atau vpn.ispnet.id')
                                ->helperText('Di hosting cPanel, gunakan IP Publik Statis, Domain DDNS, atau VPN Remote (tunnel.id). IP lokal (192.168.x.x) hanya bisa diakses via Laragon/localhost.')
                                ->required(),
                            TextInput::make('port')
                                ->label('Port API')
                                ->numeric()
                                ->default(8728)
                                ->required(),
                            Toggle::make('use_ssl')
                                ->label('Gunakan SSL (Port 8729)')
                                ->inline(false),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('username')
                                ->label('Username API')
                                ->default('admin')
                                ->required(),
                            TextInput::make('password')
                                ->label('Password API')
                                ->password()
                                ->revealable()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('timeout')
                                ->label('Timeout (detik)')
                                ->numeric()
                                ->default(5),
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->default(true)
                                ->inline(false),
                        ]),
                        Textarea::make('notes')
                            ->label('Catatan Lokasi / Keterangan')
                            ->placeholder('cth: Router Distribusi OLT Tower 1')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
