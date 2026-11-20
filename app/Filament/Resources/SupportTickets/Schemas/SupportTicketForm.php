<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\Technician;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tiket Gangguan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('ticket_number')
                                ->label('Nomor Tiket')
                                ->default(fn () => 'TCK-'.date('Ym').'-'.rand(1000, 9999))
                                ->required(),
                            Select::make('customer_id')
                                ->label('Pelanggan')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('technician_id')
                                ->label('Teknisi yang Ditugaskan')
                                ->options(Technician::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih teknisi lapangan'),
                        ]),
                        TextInput::make('subject')
                            ->label('Kendala / Judul Tiket')
                            ->placeholder('cth: Lampu LOS Merah / Koneksi Lambat')
                            ->required(),
                        Textarea::make('description')
                            ->label('Detail Keluhan Pelanggan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            Select::make('priority')
                                ->label('Prioritas')
                                ->options([
                                    'low' => 'Rendah',
                                    'medium' => 'Sedang',
                                    'high' => 'Tinggi / Urgent',
                                ])
                                ->default('medium')
                                ->required(),
                            Select::make('status')
                                ->label('Status Tiket')
                                ->options([
                                    'open' => 'Menunggu Teknisi',
                                    'in_progress' => 'Sedang Diperiksa',
                                    'resolved' => 'Selesai / Normal',
                                    'closed' => 'Ditutup',
                                ])
                                ->default('open')
                                ->required(),
                            DateTimePicker::make('resolved_at')
                                ->label('Waktu Selesai'),
                        ]),
                    ]),

                Section::make('Laporan Lapangan Teknisi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('optical_power_dbm')
                                ->label('Redaman Optik OPM (dBm)')
                                ->suffix('dBm')
                                ->placeholder('cth: -19.5'),
                            Textarea::make('resolution_notes')
                                ->label('Tindakan / Solusi Teknisi')
                                ->placeholder('cth: Penyambungan ulang patchcord yang tertekuk di roset user...'),
                        ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
