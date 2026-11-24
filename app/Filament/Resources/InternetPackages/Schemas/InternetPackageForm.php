<?php

namespace App\Filament\Resources\InternetPackages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class InternetPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Paket Internet')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Paket')
                                ->placeholder('cth: Home Ultra 20 Mbps')
                                ->required(),
                            TextInput::make('speed_rate')
                                ->label('Kecepatan (Bandwidth)')
                                ->placeholder('cth: 20M/20M')
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('price')
                                ->label('Harga Bulanan (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                            TextInput::make('validity_days')
                                ->label('Masa Aktif (Hari)')
                                ->numeric()
                                ->default(30)
                                ->required(),
                            Select::make('type')
                                ->label('Tipe Layanan')
                                ->options([
                                    'pppoe' => 'PPPoE (Router Wi-Fi / LAN)',
                                    'hotspot' => 'Hotspot (Voucher / Wi-Fi)',
                                    'static' => 'IP Static / Dedicated',
                                ])
                                ->default('pppoe')
                                ->live()
                                ->required(),
                            TextInput::make('shared_users')
                                ->label('Shared Users (Maks. Perangkat)')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(20)
                                ->helperText('Batas perangkat yang bisa login bersamaan untuk voucher hotspot paket ini di MikroTik.')
                                ->visible(fn ($get) => $get('type') === 'hotspot')
                                ->required(fn ($get) => $get('type') === 'hotspot'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('mikrotik_profile')
                                ->label('Nama Profil MikroTik')
                                ->placeholder(fn ($get) => $get('type') === 'hotspot' ? 'cth: 1HARI-5K atau DEFAULT' : 'cth: PROFILE-20M')
                                ->helperText(fn ($get) => $get('type') === 'hotspot' ? 'Nama user profile di menu /ip/hotspot/user/profile MikroTik' : 'Nama profile yang ada di menu PPP -> Profiles di MikroTik')
                                ->required(),
                            TextInput::make('mikrotik_isolir_profile')
                                ->label('Profil Isolir MikroTik (Opsional)')
                                ->placeholder('cth: ISOLIR')
                                ->helperText('Jika kosong, akan memakai profil isolir global'),
                        ]),
                        Textarea::make('description')
                            ->label('Deskripsi Singkat Paket (Opsional)')
                            ->rows(2)
                            ->placeholder('cth: Paket internet hemat untuk kebutuhan browsing dan media sosial keluarga.')
                            ->columnSpanFull(),

                        Repeater::make('features')
                            ->label('Fitur dan Benefit')
                            ->helperText('Tambahkan poin fitur & benefit paket satu per satu. Tombol "+ Tambah Fitur / Benefit" untuk menambah poin baru.')
                            ->schema([
                                Grid::make(12)->schema([
                                    TextInput::make('text')
                                        ->label('Fitur / Benefit')
                                        ->placeholder('cth: Internet UNLIMITED Tanpa FUP')
                                        ->required()
                                        ->columnSpan(8),
                                    Select::make('icon')
                                        ->label('Ikon')
                                        ->options([
                                            'wifi' => '📶 Sinyal / Unlimited',
                                            'router' => '📟 Modem / ONT',
                                            'wrench' => '🔧 Gratis Instalasi',
                                            'devices' => '📱 Multi Device',
                                            'bolt' => '⚡ Kecepatan Tinggi',
                                            'shield' => '🛡️ Keamanan / FUP',
                                            'star' => '⭐ Spesial / Premium',
                                            'check' => '✓ Ceklis (Default)',
                                        ])
                                        ->default('check')
                                        ->columnSpan(4),
                                ]),
                            ])
                            ->addActionLabel('+ Tambah Fitur / Benefit')
                            ->itemLabel(fn (array $state): ?string => $state['text'] ?? null)
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                        Radio::make('image_type')
                            ->label('Pilihan Metode Input Gambar / Brosur')
                            ->options([
                                'upload' => 'Upload Langsung (File dari Perangkat)',
                                'url' => 'Direct URL (Tautan / Link Gambar Langsung)',
                            ])
                            ->default('upload')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && ! empty($record->image) && (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                    $component->state('url');
                                } else {
                                    $component->state('upload');
                                }
                            })
                            ->columnSpanFull(),

                        FileUpload::make('image_upload')
                            ->label('Upload Gambar / Brosur Paket')
                            ->image()
                            ->imageEditor()
                            ->directory('packages')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('image_type') !== 'url')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && ! empty($record->image) && ! (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                    $component->state($record->image);
                                }
                            })
                            ->helperText('Format: JPG, PNG, WEBP, maks 5 MB.'),

                        TextInput::make('image_url')
                            ->label('Direct URL Gambar / Brosur Paket')
                            ->placeholder('https://example.com/brosur-paket.jpg')
                            ->url()
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('image_type') === 'url')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && ! empty($record->image) && (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                    $component->state($record->image);
                                }
                            })
                            ->live(debounce: 500)
                            ->helperText('Masukkan tautan langsung gambar brosur (diawali http:// atau https://).'),

                        Placeholder::make('url_preview')
                            ->label('Preview Gambar Paket')
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('image_type') === 'url' && filled($get('image_url')))
                            ->content(function ($get) {
                                $url = e($get('image_url'));

                                return new HtmlString(
                                    "<div style='max-width: 320px; border-radius: 8px; overflow: hidden; border: 1px solid #232826; background: #1a1e1c; padding: 4px;'>
                                        <img src='{$url}' alt='Package Preview' style='width: 100%; height: auto; max-height: 160px; object-fit: cover; border-radius: 6px;' onerror=\"this.style.display='none'\" />
                                    </div>"
                                );
                            }),
                        Toggle::make('is_active')
                            ->label('Paket Ditampilkan & Aktif')
                            ->default(true),
                    ]),

                Section::make('Skema Promo & Prorata Tagihan')
                    ->description('Pengaturan harga promo untuk pelanggan baru dan opsi perhitungan prorata bulan pemasangan.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('promo_price')
                                ->label('Harga Promo Bulanan (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('cth: 150000')
                                ->helperText('Tarif khusus selama siklus promo. Kosongkan jika tidak ada promo.'),
                            TextInput::make('promo_cycles')
                                ->label('Jumlah Siklus Promo (Bulan)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->helperText('Berapa siklus tagihan awal yang berhak mendapatkan harga promo.'),
                            Toggle::make('is_prorated')
                                ->label('Opsi Prorata Bulan Pasang')
                                ->helperText('Hitung tagihan pertama proporsional sisa hari bulan pemasangan.')
                                ->default(false),
                        ]),
                    ])
                    ->collapsible(),

                Section::make('Kebijakan Batas Pemakaian Wajar (FUP PPPoE)')
                    ->description('Otomatis turunkan kecepatan jika pemakaian data bulanan pelanggan mencapai batas kuota.')
                    ->schema([
                        Toggle::make('fup_enabled')
                            ->label('Aktifkan Batas Kuota FUP untuk Paket Ini')
                            ->helperText('Jika aktif, kecepatan internet pelanggan akan disesuaikan setelah kuota habis')
                            ->live(),
                        Grid::make(3)->schema([
                            TextInput::make('fup_limit_gb')
                                ->label('Batas Kuota Bulanan (GB)')
                                ->numeric()
                                ->suffix('GB')
                                ->placeholder('cth: 300')
                                ->required(fn ($get) => (bool) $get('fup_enabled'))
                                ->visible(fn ($get) => (bool) $get('fup_enabled')),
                            TextInput::make('fup_throttle_speed')
                                ->label('Kecepatan Setelah FUP (Upload/Download)')
                                ->placeholder('cth: 2M/5M atau 5M/5M')
                                ->default('2M/5M')
                                ->required(fn ($get) => (bool) $get('fup_enabled'))
                                ->visible(fn ($get) => (bool) $get('fup_enabled')),
                            TextInput::make('fup_mikrotik_profile')
                                ->label('Profil FUP MikroTik (Opsional)')
                                ->placeholder('cth: PROFIL-FUP-5M')
                                ->helperText('Jika diisi, secret pelanggan akan dialihkan ke profil ini')
                                ->visible(fn ($get) => (bool) $get('fup_enabled')),
                        ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
