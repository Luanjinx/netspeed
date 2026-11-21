<?php

namespace App\Filament\Resources\SitePages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SitePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        // Kolom Kiri: Judul & Konten Halaman Lengkap
                        Group::make([
                            Section::make('Konten Halaman')
                                ->description('Kelola isi kebijakan privasi, syarat & ketentuan, atau informasi halaman statis lainnya')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('title')
                                            ->label('Judul Halaman')
                                            ->placeholder('cth: Kebijakan Privasi')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                                if ($operation === 'create') {
                                                    $set('slug', Str::slug($state));
                                                }
                                            })
                                            ->required(),

                                        TextInput::make('slug')
                                            ->label('Slug URL')
                                            ->placeholder('cth: privacy-policy')
                                            ->helperText('Digunakan untuk akses web: /page/{slug}')
                                            ->required()
                                            ->unique(ignoreRecord: true),
                                    ]),

                                    Textarea::make('content')
                                        ->label('Isi Konten Halaman Lengkap')
                                        ->placeholder("Tuliskan isi halaman di sini...\nContoh:\n1. Ketentuan Berlangganan\nLayanan ini disediakan oleh NetSpeed...")
                                        ->rows(14)
                                        ->columnSpanFull()
                                        ->required()
                                        ->helperText('Format teks multi-paragraf dan poin akan otomatis ditampilkan rapi pada aplikasi mobile dan browser web.'),
                                ]),
                        ])
                            ->columnSpan(['lg' => 2]),

                        // Kolom Kanan: Pengaturan Status & Tautan
                        Group::make([
                            Section::make('Pengaturan & Tautan')
                                ->description('Status publikasi dan link akses web publik')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Status Aktif')
                                        ->helperText('Jika nonaktif, halaman tidak dapat diakses publik.')
                                        ->default(true),

                                    TextInput::make('order')
                                        ->label('Urutan Tampil')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Angka terkecil (0, 1, ...) tampil di urutan atas.'),

                                    TextInput::make('public_url')
                                        ->label('Tautan Web Publik')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) => $record ? url('/page/'.$record->slug) : 'Tersedia setelah disimpan')
                                        ->helperText('Gunakan tautan ini jika dibutuhkan untuk Google Play Store / App Store.'),
                                ]),
                        ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
