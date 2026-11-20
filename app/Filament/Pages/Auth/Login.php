<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Portal Administrator';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Masuk untuk mengelola billing, pelanggan & jaringan ISP';
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::Medium;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email Administrator')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->placeholder('admin@netspeed.id');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->placeholder('Masukkan password Anda');
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya di perangkat ini');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk ke Panel NOC →')
            ->submit('authenticate');
    }
}
