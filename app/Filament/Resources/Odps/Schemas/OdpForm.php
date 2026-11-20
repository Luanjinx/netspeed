<?php

namespace App\Filament\Resources\Odps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class OdpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi ODP (Optical Distribution Point)')
                    ->description('Pencatatan titik tiang ODP dan alokasi port kabel fiber optik pelanggan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama ODP')
                                ->placeholder('cth: ODP-MAWAR-01')
                                ->required(),
                            TextInput::make('code')
                                ->label('Kode ODP')
                                ->placeholder('cth: ODP/MWR/01')
                                ->default(fn () => 'ODP-'.rand(100, 999))
                                ->required()
                                ->unique(ignoreRecord: true),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('total_ports')
                                ->label('Kapasitas Port')
                                ->options([
                                    8 => '8 Port',
                                    16 => '16 Port',
                                    24 => '24 Port',
                                ])
                                ->default(8)
                                ->required(),
                            Select::make('splitter_ratio')
                                ->label('Rasio Splitter')
                                ->options([
                                    '1:8' => '1:8 Mini Box / PLC',
                                    '1:16' => '1:16 PLC',
                                    '1:4' => '1:4 Drop Core',
                                ])
                                ->default('1:8')
                                ->required(),
                            Select::make('status')
                                ->label('Status ODP')
                                ->options([
                                    'active' => 'Aktif (Tersedia Port)',
                                    'full' => 'Penuh (Port Habis)',
                                    'maintenance' => 'Perbaikan / Gangguan Tiang',
                                ])
                                ->default('active')
                                ->required(),
                        ]),
                        Textarea::make('address')
                            ->label('Lokasi / Alamat Tiang ODP')
                            ->placeholder('cth: Depan Rumah No. 12, Jl. Mawar RT 03 / RW 05')
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('latitude')
                                ->label('Latitude GPS (Opsional)')
                                ->placeholder('cth: -6.200000'),
                            TextInput::make('longitude')
                                ->label('Longitude GPS (Opsional)')
                                ->placeholder('cth: 106.816666'),
                        ]),
                        View::make('filament.forms.components.map-picker')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Catatan Redaman Awal / Keterangan')
                            ->placeholder('cth: Redaman in dari OLT -17.5 dBm')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
