<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Profil & Keamanan Akun';

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Lengkap')
            ->required()
            ->maxLength(255)
            ->placeholder('cth: Administrator ISP')
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->placeholder('cth: admin@isp.net')
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Nomor WhatsApp / HP')
            ->tel()
            ->placeholder('cth: 081234567890');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun Admin')
                    ->description('Kelola data profil, nama admin, dan alamat email login Anda.')
                    ->icon('heroicon-o-user-circle')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                        ]),
                        $this->getPhoneFormComponent(),
                    ]),

                Section::make('Ganti Kata Sandi (Password)')
                    ->description('Kosongkan kolom kata sandi jika Anda hanya ingin memperbarui email atau nama.')
                    ->icon('heroicon-o-key')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                        ]),
                        $this->getCurrentPasswordFormComponent(),
                    ]),
            ]);
    }
}
