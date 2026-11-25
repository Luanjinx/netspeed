<?php

namespace App\Filament\Resources\PackageRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackageRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Pengajuan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('request_number')
                                ->label('No. Pengajuan')
                                ->disabled(),
                            Select::make('type')
                                ->label('Jenis Pengajuan')
                                ->options([
                                    'registration' => 'Daftar Baru',
                                    'upgrade' => 'Upgrade Paket',
                                ])
                                ->disabled(),
                            Select::make('customer_id')
                                ->label('Pelanggan')
                                ->relationship('customer', 'name')
                                ->disabled(),
                            Select::make('requested_package_id')
                                ->label('Paket Diminta')
                                ->relationship('requestedPackage', 'name')
                                ->disabled(),
                            Select::make('status')
                                ->label('Status Persetujuan')
                                ->options([
                                    'pending' => 'Menunggu ACC',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                ])
                                ->disabled(),
                            Select::make('payment_status')
                                ->label('Status Pembayaran')
                                ->options([
                                    'not_required' => 'Tidak Perlu Bayar',
                                    'unpaid' => 'Menunggu Pembayaran',
                                    'paid' => 'Lunas',
                                ])
                                ->disabled(),
                        ]),
                        Textarea::make('customer_notes')
                            ->label('Catatan Pelanggan')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('admin_notes')
                            ->label('Catatan Admin / Teknisi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
