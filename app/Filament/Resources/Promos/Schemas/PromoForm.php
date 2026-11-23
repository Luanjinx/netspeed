<?php

namespace App\Filament\Resources\Promos\Schemas;

use App\Models\InternetPackage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        // Kolom Kiri / Utama (2 Kolom): Banner, Judul, Artikel & Penawaran
                        Group::make([
                            Section::make('Visual Banner & Artikel Promo')
                                ->description('Pilih metode input banner (upload file langsung atau direct URL gambar) dan isi artikel penjelasan promo')
                                ->icon('heroicon-o-photo')
                                ->schema([
                                    Radio::make('image_type')
                                        ->label('Pilihan Metode Input Banner')
                                        ->options([
                                            'upload' => 'Upload Langsung (File dari Perangkat)',
                                            'url' => 'Direct URL (Tautan / Link Gambar Langsung)',
                                        ])
                                        ->default('upload')
                                        ->inline()
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record && ! empty($record->image) && (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                                $component->state('url');
                                            } else {
                                                $component->state('upload');
                                            }
                                        })
                                        ->columnSpanFull(),

                                    FileUpload::make('image_upload')
                                        ->label('Upload Banner Iklan Landscape')
                                        ->image()
                                        ->imageEditor()
                                        ->directory('promos')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->maxSize(5120)
                                        ->columnSpanFull()
                                        ->visible(fn ($get) => $get('image_type') !== 'url')
                                        ->required(fn ($get, $operation, $record) => $get('image_type') !== 'url' && ($operation === 'create' || empty($record?->image)))
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record && ! empty($record->image) && ! (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                                $component->state($record->image);
                                            }
                                        })
                                        ->helperText('Format: JPG, PNG, WEBP. Rekomendasi rasio landscape 16:9 atau 2:1 (cth: 1200 x 600 px), maks. 5 MB.'),

                                    TextInput::make('image_url')
                                        ->label('Direct URL Banner Iklan Landscape')
                                        ->placeholder('https://example.com/banner-promo.jpg')
                                        ->url()
                                        ->columnSpanFull()
                                        ->visible(fn ($get) => $get('image_type') === 'url')
                                        ->required(fn ($get) => $get('image_type') === 'url')
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record && ! empty($record->image) && (str_starts_with($record->image, 'http://') || str_starts_with($record->image, 'https://'))) {
                                                $component->state($record->image);
                                            }
                                        })
                                        ->live(debounce: 500)
                                        ->helperText('Masukkan tautan langsung gambar banner (diawali http:// atau https://).'),

                                    Placeholder::make('url_preview')
                                        ->label('Preview Gambar Banner')
                                        ->columnSpanFull()
                                        ->visible(fn ($get) => $get('image_type') === 'url' && filled($get('image_url')))
                                        ->content(function ($get) {
                                            $url = e($get('image_url'));

                                            return new HtmlString(
                                                "<div style='max-width: 440px; border-radius: 8px; overflow: hidden; border: 1px solid #232826; background: #1a1e1c; padding: 4px;'>
                                                    <img src='{$url}' alt='Banner Preview' style='width: 100%; height: auto; max-height: 180px; object-fit: cover; border-radius: 6px;' onerror=\"this.style.display='none'\" />
                                                </div>"
                                            );
                                        }),

                                    TextInput::make('title')
                                        ->label('Judul Promo / Judul Artikel Blog')
                                        ->placeholder('cth: Nikmati Hiburan Tanpa Batas dengan WeTV')
                                        ->required()
                                        ->columnSpanFull(),

                                    Textarea::make('content')
                                        ->label('Artikel Lengkap / Penjelasan Promo')
                                        ->placeholder("Tuliskan deskripsi lengkap promo di sini seperti artikel blog...\nContoh:\nSekarang kamu bisa menikmati hiburan lengkap tanpa batas dengan NetSpeed kapanpun dan dimanapun melalui paket add-on WeTV VIP.\n\nKeunggulan:\n- Akses konten VIP eksklusif tanpa jeda iklan\n- Nonton bareng keluarga kualitas Full HD\n- Aktivasi instan ke akun pelanggan Anda")
                                        ->rows(8)
                                        ->columnSpanFull()
                                        ->helperText('Konten ini akan dibaca oleh pelanggan saat membuka halaman artikel promo.'),
                                ]),

                            Section::make('Penawaran Harga & Tombol Langganan (Opsional)')
                                ->description('Tampilkan kartu penawaran harga spesial dengan diskon di halaman promo')
                                ->icon('heroicon-o-tag')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('offer_title')
                                            ->label('Nama Penawaran / Paket Promo')
                                            ->placeholder('cth: WeTV VIP 30 Days atau Home Ultra 20 Mbps'),
                                        TextInput::make('offer_duration')
                                            ->label('Durasi / Periode Penawaran')
                                            ->placeholder('cth: 1 Bulan, 3 Bulan, atau 1 Tahun')
                                            ->default('1 Bulan'),
                                    ]),

                                    Grid::make(['default' => 1, 'sm' => 3])->schema([
                                        TextInput::make('original_price')
                                            ->label('Harga Normal (Coret)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('39000'),
                                        TextInput::make('promo_price')
                                            ->label('Harga Promo Spesial')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('30000'),
                                        TextInput::make('discount_label')
                                            ->label('Label Diskon (Opsional)')
                                            ->placeholder('cth: -24% (otomatis jika kosong)'),
                                    ]),

                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('price_unit')
                                            ->label('Satuan Harga')
                                            ->placeholder('cth: / bulan')
                                            ->default('/ bulan'),
                                        TextInput::make('cta_text')
                                            ->label('Teks Tombol Aksi Utama')
                                            ->placeholder('cth: Langganan Sekarang')
                                            ->default('Langganan Sekarang'),
                                    ]),

                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        Select::make('action_type')
                                            ->label('Jenis Tindakan Saat Tombol Ditekan')
                                            ->options([
                                                'register' => 'Buka Formulir Pendaftaran / Pasang Baru',
                                                'package' => 'Arahkan ke Paket Internet Tertentu',
                                                'whatsapp' => 'Hubungi Customer Care WhatsApp',
                                                'url' => 'Buka Tautan / Website Luar',
                                                'none' => 'Hanya Tampilan Informasi (Tanpa Tindakan)',
                                            ])
                                            ->default('register')
                                            ->live()
                                            ->required(),
                                        Select::make('package_id')
                                            ->label('Pilihan Paket Internet Terkait')
                                            ->options(InternetPackage::where('is_active', true)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn ($get) => $get('action_type') === 'package')
                                            ->required(fn ($get) => $get('action_type') === 'package'),
                                        TextInput::make('link_url')
                                            ->label('Tautan Website Luar (URL)')
                                            ->url()
                                            ->placeholder('https://contoh-promo.com')
                                            ->visible(fn ($get) => $get('action_type') === 'url')
                                            ->required(fn ($get) => $get('action_type') === 'url'),
                                    ]),
                                ]),
                        ])
                            ->columnSpan(['lg' => 2]),

                        // Kolom Kanan / Sidebar (1 Kolom): Pengaturan Tayang, Urutan & Status
                        Group::make([
                            Section::make('Pengaturan Tayang & Status')
                                ->description('Atur status publikasi, urutan tampil, dan jadwal tayang promo')
                                ->icon('heroicon-o-calendar')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Status Aktif')
                                        ->helperText('Jika nonaktif, promo tidak akan muncul di aplikasi mobile.')
                                        ->default(true),

                                    TextInput::make('order')
                                        ->label('Urutan Tampil (Prioritas)')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Angka terkecil (0, 1, ...) tampil di urutan awal.'),

                                    DateTimePicker::make('starts_at')
                                        ->label('Waktu Mulai Tayang (Opsional)')
                                        ->helperText('Kosongkan untuk langsung tayang sekarang.'),

                                    DateTimePicker::make('ends_at')
                                        ->label('Waktu Selesai Tayang (Opsional)')
                                        ->helperText('Kosongkan jika promo berlaku selamanya.'),
                                ]),
                        ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
