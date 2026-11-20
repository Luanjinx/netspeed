<?php

namespace App\Filament\Resources\HotspotVouchers\Schemas;

use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class HotspotVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rincian Voucher Hotspot Sesuai Profil MikroTik')
                    ->description('Voucher yang dibuat akan otomatis terdaftar di menu /ip/hotspot/user MikroTik')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('router_id')
                                ->label('Router Hotspot MikroTik')
                                ->options(Router::where('is_active', true)->pluck('name', 'id'))
                                ->default(fn () => Router::where('is_active', true)->value('id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('profile', null))
                                ->required(),

                            Select::make('profile')
                                ->label('Profil Hotspot MikroTik')
                                ->placeholder('Pilih profil dari MikroTik...')
                                ->options(function ($get) {
                                    $routerId = $get('router_id') ?: Router::where('is_active', true)->value('id');
                                    if (! $routerId) {
                                        return ['default' => 'default (Default Profile)'];
                                    }
                                    $router = Router::find($routerId);
                                    if (! $router) {
                                        return ['default' => 'default (Default Profile)'];
                                    }

                                    return app(VoucherService::class)->getProfiles($router);
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state) {
                                        $router = Router::find($get('router_id'));
                                        $meta = app(VoucherService::class)->extractProfileMetadata($state, $router);

                                        $set('validity', $meta['validity']);
                                        $set('price', $meta['price']);
                                        $set('shared_users', $meta['shared_users']);
                                    }
                                })
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('code')
                                ->label('Kode Voucher / Username')
                                ->default(fn () => strtoupper(Str::random(5)))
                                ->placeholder('cth: VCR782')
                                ->required()
                                ->unique(ignoreRecord: true),

                            TextInput::make('password')
                                ->label('Password Voucher')
                                ->default(fn ($get) => $get('code'))
                                ->placeholder('Kosongkan atau samakan dengan kode')
                                ->required(),
                        ]),

                        Grid::make(4)->schema([
                            TextInput::make('price')
                                ->label('Harga Jual (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->helperText('Otomatis mengikuti profil MikroTik')
                                ->default(5000)
                                ->required(),

                            Select::make('validity')
                                ->label('Masa Aktif / Limit Uptime')
                                ->options([
                                    '1 Jam' => '1 Jam (1h)',
                                    '2 Jam' => '2 Jam (2h)',
                                    '3 Jam' => '3 Jam (3h)',
                                    '4 Jam' => '4 Jam (4h)',
                                    '5 Jam' => '5 Jam (5h)',
                                    '6 Jam' => '6 Jam (6h)',
                                    '12 Jam' => '12 Jam (12h)',
                                    '1 Hari' => '1 Hari / 24 Jam (1d)',
                                    '2 Hari' => '2 Hari (2d)',
                                    '3 Hari' => '3 Hari (3d)',
                                    '7 Hari' => '7 Hari / 1 Minggu (7d)',
                                    '14 Hari' => '14 Hari / 2 Minggu (14d)',
                                    '30 Hari' => '30 Hari / 1 Bulan (30d)',
                                ])
                                ->helperText('Otomatis mengikuti profil MikroTik')
                                ->default('1 Hari')
                                ->required(),

                            TextInput::make('shared_users')
                                ->label('Shared Users')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(20)
                                ->helperText('Maksimal perangkat login bersamaan')
                                ->required(),

                            Select::make('status')
                                ->label('Status Voucher')
                                ->options([
                                    'unused' => 'Belum Terpakai (Ready)',
                                    'used' => 'Sedang / Sudah Dipakai',
                                    'expired' => 'Kadaluarsa / Habis',
                                ])
                                ->default('unused')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('generated_batch')
                                ->label('Batch ID')
                                ->placeholder('Otomatis dibuat saat generate batch')
                                ->disabled(),

                            TextInput::make('used_by_mac')
                                ->label('MAC Address Pemakai (Login)')
                                ->placeholder('Terisi otomatis saat user hotspot login')
                                ->disabled(),
                        ]),
                    ]),
            ]);
    }
}
