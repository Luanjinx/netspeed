<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->required(),
                TextInput::make('payment_reference')
                    ->required(),
                TextInput::make('amount_paid')
                    ->required()
                    ->numeric(),
                TextInput::make('payment_method')
                    ->required()
                    ->default('qris_dynamic'),
                FileUpload::make('proof_image')
                    ->image(),
                Toggle::make('is_verified')
                    ->required(),
                TextInput::make('verified_by')
                    ->numeric(),
                DateTimePicker::make('verified_at'),
                Textarea::make('raw_response')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
