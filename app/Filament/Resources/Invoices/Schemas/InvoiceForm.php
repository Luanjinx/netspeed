<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Customer;
use App\Models\InternetPackage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rincian Tagihan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('invoice_number')
                                ->label('Nomor Tagihan')
                                ->default(fn () => 'INV-'.date('Ym').'-'.rand(1000, 9999))
                                ->required()
                                ->unique(ignoreRecord: true),
                            Select::make('customer_id')
                                ->label('Pelanggan')
                                ->options(Customer::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('package_id')
                                ->label('Paket Internet')
                                ->options(InternetPackage::pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('period')
                                ->label('Periode (YYYY-MM)')
                                ->default(fn () => date('Y-m'))
                                ->required(),
                            TextInput::make('amount')
                                ->label('Tagihan Pokok (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                            TextInput::make('unique_code')
                                ->label('Kode Unik')
                                ->numeric()
                                ->default(0),
                            TextInput::make('total_amount')
                                ->label('Total Pembayaran (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Status Tagihan')
                                ->options([
                                    'unpaid' => 'Belum Lunas',
                                    'paid' => 'Lunas',
                                    'cancelled' => 'Dibatalkan',
                                    'expired' => 'Kadaluarsa',
                                ])
                                ->default('unpaid')
                                ->required(),
                            DatePicker::make('due_date')
                                ->label('Tgl Jatuh Tempo')
                                ->required(),
                            DateTimePicker::make('paid_at')
                                ->label('Tgl Lunas (Jika Sudah)'),
                        ]),
                        Textarea::make('notes')
                            ->label('Catatan Invoice (AUTO: Rincian Promo / Prorata / Manual)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('qris_payload')
                            ->label('Payload String QRIS Dinamis')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
