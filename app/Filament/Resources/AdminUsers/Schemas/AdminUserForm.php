<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil & Kredensial Administrator')
                    ->description('Kelola akun akses admin panel, alamat email login, dan kata sandi')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('name')
                                ->label('Nama Lengkap')
                                ->required()
                                ->placeholder('cth: Administrator Utama')
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label('Nomor WhatsApp / HP')
                                ->tel()
                                ->placeholder('cth: 081234567890'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('email')
                                ->label('Alamat Email Login')
                                ->email()
                                ->required()
                                ->placeholder('cth: admin@isp.net')
                                ->unique(ignoreRecord: true)
                                ->helperText('Email ini digunakan untuk login ke Admin Panel.'),
                            TextInput::make('password')
                                ->label('Kata Sandi (Password)')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->helperText('Minimal 6 karakter. Kosongkan saat edit jika tidak ingin mengubah password.'),
                        ]),
                        Toggle::make('is_active')
                            ->label('Status Akun Aktif')
                            ->helperText('Administrator non-aktif tidak dapat masuk ke Admin Panel.')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
