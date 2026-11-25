<?php

namespace App\Filament\Resources\Agents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil & Informasi Kemitraan Agen')
                    ->description('Kelola data mitra loket pembayaran, nomor kontak, serta komisi transaksi')
                    ->icon('heroicon-o-building-storefront')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])->schema([
                            TextInput::make('agent_code')
                                ->label('Kode Agen')
                                ->placeholder('cth: AGT-001')
                                ->helperText('Kosongkan untuk penomoran otomatis.')
                                ->maxLength(20),
                            TextInput::make('name')
                                ->label('Nama Agen / Nama Loket')
                                ->required()
                                ->placeholder('cth: Loket Warung Berkah / Toko Anto')
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label('Nomor WhatsApp Agen')
                                ->tel()
                                ->required()
                                ->placeholder('cth: 081234567890')
                                ->helperText('Digunakan untuk konfirmasi setoran & notifikasi transaksi.'),
                        ]),

                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('commission_per_transaction')
                                ->label('Komisi per Transaksi PPPoE')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(2000)
                                ->required()
                                ->helperText('Nominal keuntungan langsung yang didapat agen per pembayaran.'),
                            TextInput::make('balance')
                                ->label('Saldo Deposit Saat Ini')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->helperText('Gunakan tombol Top-Up di tabel untuk menambah saldo.'),
                        ]),

                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Textarea::make('address')
                                ->label('Alamat / Lokasi Loket')
                                ->placeholder('cth: Jl. Mawar No. 12, RT 02 RW 05 (Depan Pos Ronda)')
                                ->rows(3),
                            Textarea::make('notes')
                                ->label('Catatan Kemitraan')
                                ->placeholder('Catatan khusus perjanjian kemitraan atau jam buka loket...')
                                ->rows(3),
                        ]),

                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Select::make('approval_status')
                                ->label('Status Pengajuan')
                                ->options([
                                    'pending' => 'Menunggu ACC',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                ])
                                ->default('approved')
                                ->required(),
                            Toggle::make('is_active')
                                ->label('Status Agen Aktif')
                                ->helperText('Jika nonaktif, agen tidak dapat melakukan pembayaran atau transaksi loket.')
                                ->default(true),
                        ]),
                    ]),

                Section::make('Akun Login Portal Agen')
                    ->description('Buat akun login agar mitra agen dapat mengakses Dashboard & Loket Pembayaran secara mandiri')
                    ->icon('heroicon-o-key')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('email')
                                ->label('Email Login Portal Agen')
                                ->email()
                                ->required()
                                ->placeholder('cth: agen.berkah@gmail.com')
                                ->helperText('Email ini digunakan agen untuk masuk ke Admin Panel.'),
                            TextInput::make('password')
                                ->label('Kata Sandi (Password)')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(false)
                                ->helperText('Minimal 6 karakter. Kosongkan saat edit jika tidak ingin mengubah password.'),
                        ]),
                    ]),
            ]);
    }
}
