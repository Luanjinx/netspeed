<?php

namespace App\Filament\Resources\Technicians\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TechnicianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil & Akses Teknisi')
                    ->description('Kelola akun login portal dan data penugasan teknisi lapangan')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])->schema([
                            TextInput::make('technician_code')
                                ->label('Kode Teknisi')
                                ->placeholder('cth: TECH-001')
                                ->helperText('Kosongkan untuk penomoran otomatis.'),
                            TextInput::make('name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->placeholder('cth: Rian Hidayat'),
                            TextInput::make('phone')
                                ->label('Nomor WhatsApp')
                                ->tel()
                                ->required()
                                ->placeholder('cth: 081234567890')
                                ->helperText('Digunakan untuk notifikasi tugas & login portal.'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('email')
                                ->label('Alamat Email')
                                ->email()
                                ->required()
                                ->placeholder('cth: teknisi1@isp.net')
                                ->unique(ignoreRecord: true),
                            TextInput::make('password')
                                ->label('Password Akun Portal')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->helperText('Minimal 6 karakter. Kosongkan saat edit jika tidak diubah.'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('work_area')
                                ->label('Wilayah / Area Tugas (Zona)')
                                ->placeholder('cth: Sektor Utara / Cluster Melati')
                                ->helperText('Zona operasional utama untuk penugasan instalasi & kendala.'),
                            Toggle::make('is_active')
                                ->label('Status Teknisi Aktif')
                                ->helperText('Teknisi non-aktif tidak dapat login ke portal lapangan.')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
