<?php

namespace App\Filament\Resources\HotspotVouchers\Pages;

use App\Filament\Resources\HotspotVouchers\HotspotVoucherResource;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListHotspotVouchers extends ListRecords
{
    protected static string $resource = HotspotVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Generate Batch Voucher sesuai profil MikroTik
            Action::make('generateBatch')
                ->label('Generate Batch Voucher')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('Generate Batch Voucher Hotspot Sesuai Profil MikroTik')
                ->modalDescription('Voucher yang dibuat akan langsung otomatis terdaftar di /ip/hotspot/user MikroTik')
                ->form([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('profile', null))
                        ->required(),

                    Select::make('profile')
                        ->label('Profil Hotspot MikroTik')
                        ->placeholder('Pilih profil hotspot...')
                        ->options(function ($get) {
                            $routerId = $get('router_id') ?: Router::where('is_active', true)->value('id');
                            if (! $routerId) {
                                return ['default' => 'default'];
                            }
                            $router = Router::find($routerId);
                            if (! $router) {
                                return ['default' => 'default'];
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

                    TextInput::make('price')
                        ->label('Harga Jual Per Voucher (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->helperText('Otomatis mengikuti profil MikroTik')
                        ->default(5000)
                        ->required(),

                    Select::make('validity')
                        ->label('Masa Aktif / Batas Waktu (Limit Uptime)')
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

                    TextInput::make('qty')
                        ->label('Jumlah Voucher yang Dibuat')
                        ->numeric()
                        ->default(20)
                        ->minValue(1)
                        ->maxValue(500)
                        ->required(),

                    TextInput::make('shared_users')
                        ->label('Shared Users (Maks. Perangkat)')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(20)
                        ->helperText('Batas jumlah perangkat yang bisa login bersamaan dengan 1 voucher (contoh: 1 = hanya 1 perangkat, 3 = bisa untuk 3 perangkat).')
                        ->required(),

                    Select::make('char_type')
                        ->label('Format Karakter Kode')
                        ->options([
                            'alphanumeric_upper' => 'Huruf Kapital + Angka (cth: A8K9M)',
                            'alpha_upper' => 'Hanya Huruf Kapital (cth: BDGKM)',
                            'numeric' => 'Hanya Angka (cth: 89432)',
                            'alphanumeric_lower' => 'Huruf Kecil + Angka (cth: x9m2p)',
                        ])
                        ->default('alphanumeric_upper')
                        ->required(),

                    Select::make('code_length')
                        ->label('Panjang Karakter Kode')
                        ->options([
                            4 => '4 Karakter',
                            5 => '5 Karakter',
                            6 => '6 Karakter',
                            8 => '8 Karakter',
                        ])
                        ->default(5)
                        ->required(),

                    TextInput::make('prefix')
                        ->label('Prefix Kode (Opsional)')
                        ->placeholder('cth: NET- atau HS-'),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $meta = $voucherService->extractProfileMetadata($data['profile'], $router);
                    $validity = ($data['validity'] === '1 Hari' && $meta['validity'] !== '1 Hari')
                        ? $meta['validity']
                        : $data['validity'];

                    $result = $voucherService->generateBatch(
                        router: $router,
                        profile: $data['profile'],
                        price: (float) $data['price'],
                        validity: $validity,
                        qty: (int) $data['qty'],
                        prefix: $data['prefix'] ?? '',
                        charType: $data['char_type'] ?? 'alphanumeric_upper',
                        codeLength: (int) ($data['code_length'] ?? 5),
                        userEqualsPass: true,
                        limitUptime: null,
                        sharedUsers: (int) ($data['shared_users'] ?? 1)
                    );

                    Notification::make()
                        ->title("Berhasil generate {$result['count']} voucher!")
                        ->body("Profil: {$data['profile']} (Limit: {$result['limit_uptime']}, Shared: {$result['shared_users']} Device). Batch ID: {$result['batch_id']}. Voucher siap dicetak dan langsung aktif di MikroTik.")
                        ->success()
                        ->send();
                }),

            // 2. Tarik Profil Hotspot dari MikroTik ke Database
            Action::make('pullProfiles')
                ->label('Tarik Profil MikroTik')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalHeading('Tarik Profil Hotspot dari Router MikroTik')
                ->modalDescription('Membaca seluruh /ip/hotspot/user/profile dari MikroTik dan menyinkronkannya sebagai paket voucher')
                ->form([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->required(),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        return;
                    }

                    $result = $voucherService->pullProfilesFromRouter($router);

                    Notification::make()
                        ->title('Sinkronisasi Profil Hotspot Sukses!')
                        ->body("Total profil ditemukan di MikroTik: {$result['total']} ({$result['imported']} paket baru, {$result['updated']} paket diperbarui).")
                        ->success()
                        ->send();
                }),

            // 3. Buat Profil Hotspot Baru Langsung ke MikroTik
            Action::make('createProfile')
                ->label('Buat Profil Hotspot di MikroTik')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->modalHeading('Buat Hotspot User Profile Baru di MikroTik')
                ->form([
                    Select::make('router_id')
                        ->label('Router MikroTik Tujuan')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->required(),
                    TextInput::make('name')
                        ->label('Nama Profil Hotspot')
                        ->placeholder('cth: 1JAM-2K, 3JAM-5K, 1HARI-10K')
                        ->required(),
                    TextInput::make('rate_limit')
                        ->label('Limit Kecepatan (Upload/Download)')
                        ->placeholder('cth: 2M/5M atau 5M/10M')
                        ->default('2M/5M')
                        ->required(),
                    TextInput::make('shared_users')
                        ->label('Shared Users (Maks Perangkat Per Voucher)')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    TextInput::make('session_timeout')
                        ->label('Session Timeout (Opsional)')
                        ->placeholder('cth: 01:00:00 atau 1d'),
                ])
                ->action(function (array $data, MikrotikApiService $api) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        return;
                    }

                    $res = $api->syncHotspotUserProfile($router, [
                        'name' => $data['name'],
                        'rate_limit' => $data['rate_limit'],
                        'shared_users' => $data['shared_users'],
                        'session_timeout' => $data['session_timeout'] ?? null,
                    ]);

                    if ($res['success']) {
                        // Also record into InternetPackage
                        InternetPackage::updateOrCreate(
                            ['mikrotik_profile' => $data['name'], 'type' => 'hotspot'],
                            [
                                'name' => 'Hotspot '.$data['name'],
                                'speed_rate' => $data['rate_limit'],
                                'price' => 5000,
                                'validity_days' => 1,
                                'shared_users' => (int) ($data['shared_users'] ?? 1),
                                'is_active' => true,
                            ]
                        );

                        Notification::make()->title($res['message'])->success()->send();
                    } else {
                        Notification::make()->title($res['message'])->danger()->send();
                    }
                }),

            // 4. Sinkron Status Voucher dengan MikroTik Active
            Action::make('syncVoucherStatus')
                ->label('Sinkron Sesi Aktif')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    Select::make('router_id')
                        ->label('Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->required(),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        return;
                    }

                    $res = $voucherService->syncVoucherStatus($router);

                    Notification::make()
                        ->title('Sinkronisasi Sesi Aktif Selesai')
                        ->body("Terdeteksi {$res['active_sessions_count']} perangkat aktif di Hotspot MikroTik. {$res['vouchers_marked_used']} voucher ditandai 'used'.")
                        ->success()
                        ->send();
                }),

            // 5. Bersihkan User Orphan di MikroTik (User yang sudah dihapus di web tapi masih nyangkut di MikroTik)
            Action::make('pruneOrphanUsers')
                ->label('Bersihkan User Nyangkut')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Bersihkan User Hotspot Nyangkut di MikroTik')
                ->modalDescription('Fitur ini akan menyapu MikroTik dan menghapus semua user hotspot yang sudah tidak ada di web panel ini. User sistem ("admin" & "default-trial") tetap aman dan tidak akan dihapus.')
                ->requiresConfirmation()
                ->form([
                    Select::make('router_id')
                        ->label('Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->required(),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        return;
                    }

                    $res = $voucherService->pruneOrphanVouchers($router);

                    if ($res['success'] ?? false) {
                        $count = $res['count'] ?? 0;
                        if ($count > 0) {
                            $list = implode(', ', array_slice($res['pruned'], 0, 5));
                            if ($count > 5) {
                                $list .= '... dan '.($count - 5).' lainnya';
                            }
                            Notification::make()
                                ->title("Berhasil Membersihkan {$count} User Nyangkut!")
                                ->body("User dihapus dari MikroTik: {$list}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('MikroTik Bersih')
                                ->body('Tidak ada user nyangkut / orphan di MikroTik. Semua data telah sinkron.')
                                ->info()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Gagal Membersihkan User MikroTik')
                            ->body($res['message'] ?? 'Periksa koneksi router.')
                            ->danger()
                            ->send();
                    }
                }),

            // 6. Import Voucher dari MikroTik ke Web Panel
            Action::make('importFromRouter')
                ->label('Tarik Voucher MikroTik')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Import Voucher dari MikroTik ke Sistem Web')
                ->modalDescription('Membaca semua user hotspot yang ada di MikroTik (misal dibuat lewat Winbox/Mikhmon) dan mendaftarkannya ke sistem billing ini.')
                ->form([
                    Select::make('router_id')
                        ->label('Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->value('id'))
                        ->required(),
                ])
                ->action(function (array $data, VoucherService $voucherService) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        return;
                    }

                    $res = $voucherService->importVouchersFromRouter($router);

                    if ($res['success'] ?? false) {
                        $imported = $res['imported'] ?? 0;
                        Notification::make()
                            ->title('Proses Import Selesai')
                            ->body("Berhasil mengimpor {$imported} voucher baru dari MikroTik (total user ditemukan: {$res['total_found']}).")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Mengimpor dari MikroTik')
                            ->body($res['message'] ?? 'Periksa koneksi router.')
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make()->label('Tambah 1 Voucher Manual'),
        ];
    }
}
