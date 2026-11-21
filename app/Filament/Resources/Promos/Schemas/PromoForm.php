<?php

namespace App\Filament\Resources\Promos\Schemas;

use App\Models\InternetPackage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner Iklan Landscape')
                    ->description('Upload visual banner landscape yang akan ditampilkan pada slider aplikasi mobile')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Upload Banner Iklan Landscape')
                            ->image()
                            ->imageEditor()
                            ->directory('promos')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Format: JPG, PNG, WEBP. Rekomendasi rasio landscape 16:9 atau 2:1 (cth: 1200 x 600 px), maks. 5 MB.'),
                    ]),

                Section::make('Informasi & Teks Promo')
                    ->description('Teks judul dan keterangan yang tampil di atas banner')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('title')
                                ->label('Judul Promo / Iklan')
                                ->placeholder('cth: Promo Gaspol 11.11')
                                ->required(),
                            TextInput::make('badge_text')
                                ->label('Teks Badge Pojok (Opsional)')
                                ->placeholder('cth: PROMO, BEST SELLER, 50 MBPS'),
                        ]),
                        TextInput::make('subtitle')
                            ->label('Keterangan Singkat / Tagline')
                            ->placeholder('cth: Nikmati Internet Cepat 100 Mbps Bebas FUP')
                            ->columnSpanFull(),
                    ]),

                Section::make('Aksi Saat Banner Diklik di Aplikasi')
                    ->description('Tentukan apa yang terjadi saat pengguna mengetuk banner iklan ini di aplikasi mobile')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Select::make('action_type')
                                ->label('Jenis Tindakan / Aksi')
                                ->options([
                                    'none' => 'Hanya Tampilan Iklan (Tanpa Aksi)',
                                    'package' => 'Buka Paket Internet Tertentu',
                                    'register' => 'Buka Formulir Pasang Baru',
                                    'whatsapp' => 'Buka Chat Customer Service WhatsApp',
                                    'url' => 'Buka Tautan / Website Luar',
                                ])
                                ->default('none')
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

                Section::make('Pengaturan Tayang & Urutan')
                    ->description('Jadwalkan masa aktif promo dan urutan tampil di slider')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])->schema([
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->helperText('Jika nonaktif, banner tidak akan muncul di aplikasi.')
                                ->default(true),
                            TextInput::make('order')
                                ->label('Urutan Tampil (Prioritas)')
                                ->numeric()
                                ->default(0)
                                ->helperText('Urutan dari angka terkecil (0, 1, 2, ...)'),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            DateTimePicker::make('starts_at')
                                ->label('Waktu Mulai Tayang (Opsional)')
                                ->helperText('Kosongkan jika ingin langsung tayang sekarang.'),
                            DateTimePicker::make('ends_at')
                                ->label('Waktu Selesai Tayang (Opsional)')
                                ->helperText('Kosongkan jika promo berlaku selamanya.'),
                        ]),
                    ]),
            ]);
    }
}
