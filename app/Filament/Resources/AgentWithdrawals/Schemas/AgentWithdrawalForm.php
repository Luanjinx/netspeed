<?php

namespace App\Filament\Resources\AgentWithdrawals\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgentWithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('withdrawal_code')->label('Kode')->disabled(),
                TextInput::make('amount')->label('Nominal')->prefix('Rp')->disabled(),
                TextInput::make('bank_or_provider')->label('Bank / E-Wallet')->disabled(),
                TextInput::make('account_number')->label('No. Rekening / HP')->disabled(),
                TextInput::make('account_name')->label('Atas Nama')->disabled(),
                Textarea::make('notes')->label('Catatan')->disabled(),
            ]);
    }
}
