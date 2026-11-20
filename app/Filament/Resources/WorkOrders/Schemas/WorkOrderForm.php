<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\Technician;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Penugasan & SPK')
                    ->description('Penerbitan surat perintah kerja lapangan untuk teknisi')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])->schema([
                            TextInput::make('order_number')
                                ->label('Nomor SPK')
                                ->placeholder('Otomatis (cth: SPK-202611-0001)')
                                ->disabled()
                                ->dehydrated(false),
                            Select::make('type')
                                ->label('Tipe Pekerjaan')
                                ->options([
                                    'installation' => 'Pemasangan Baru (PSB)',
                                    'repair' => 'Perbaikan Gangguan',
                                    'maintenance' => 'Pemeliharaan / Cek Jalur',
                                ])
                                ->default('installation')
                                ->live()
                                ->required(),
                            Select::make('technician_id')
                                ->label('Teknisi Pelaksana')
                                ->options(Technician::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Select::make('customer_id')
                                ->label('Pelanggan Tujuan')
                                ->options(Customer::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('support_ticket_id')
                                ->label('Tiket Gangguan Terkait (Opsional)')
                                ->options(SupportTicket::pluck('ticket_number', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih jika menindaklanjuti tiket'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 3])->schema([
                            TextInput::make('title')
                                ->label('Judul Tugas')
                                ->placeholder('cth: Pasang Baru Paket 20M Bpk Budi')
                                ->required(),
                            DatePicker::make('scheduled_date')
                                ->label('Tanggal Jadwal')
                                ->default(now())
                                ->required(),
                            Select::make('status')
                                ->label('Status SPK')
                                ->options([
                                    'pending' => 'Menunggu Teknisi',
                                    'in_progress' => 'Sedang Dikerjakan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                ])
                                ->default('pending')
                                ->required(),
                        ]),
                        Textarea::make('description')
                            ->label('Instruksi / Catatan Tambahan Admin')
                            ->placeholder('Instruksi khusus jalur kabel, tiang ODP, kontak pic di lokasi...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Hasil Pengerjaan Lapangan')
                    ->description('Diisi oleh teknisi atau admin setelah pengerjaan selesai')
                    ->icon('heroicon-o-check-circle')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('optical_power_dbm')
                                ->label('Redaman Optik OPM (dBm)')
                                ->placeholder('cth: -19.5')
                                ->suffix('dBm')
                                ->helperText('Standar ISP sehat: -15 dBm s/d -24 dBm'),
                            Textarea::make('completion_notes')
                                ->label('Laporan Akhir Teknisi')
                                ->placeholder('Catatan hasil instalasi/perbaikan...'),
                        ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
