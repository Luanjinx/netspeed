<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\GenieacsServer;
use App\Models\InternetPackage;
use App\Models\Odp;
use App\Models\Router;
use App\Models\Technician;
use App\Services\Router\MikrotikApiService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pelanggan')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])->schema([
                            TextInput::make('customer_number')
                                ->label('Nomor Pelanggan')
                                ->default(fn () => Customer::generateCustomerNumber())
                                ->required()
                                ->numeric()
                                ->unique(ignoreRecord: true)
                                ->helperText('Nomor pelanggan acak (hanya angka) dan unik otomatis.'),
                            TextInput::make('name')
                                ->label('Nama Lengkap')
                                ->placeholder('cth: Budi Santoso')
                                ->required(),
                            TextInput::make('phone')
                                ->label('Nomor WhatsApp')
                                ->tel()
                                ->placeholder('cth: 081234567890')
                                ->helperText('Dapat digunakan pelanggan sebagai ID login')
                                ->required(),
                        ]),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('email')
                                ->label('Email Pelanggan (Login & Notifikasi)')
                                ->email()
                                ->placeholder('cth: budi@gmail.com')
                                ->helperText('Dapat digunakan pelanggan sebagai ID login'),
                            TextInput::make('password')
                                ->label('Password Akun Pelanggan')
                                ->password()
                                ->revealable()
                                ->placeholder('Minimal 6 karakter')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn (string $operation): bool => false)
                                ->helperText('Password login pelanggan via Email atau No WA. Kosongkan jika tidak diubah.'),
                        ]),
                        Textarea::make('address')
                            ->label('Alamat Pemasangan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Titik Geografis & Lokasi Peta (Satelit)')
                    ->description('Tentukan koordinat letak rumah/kantor pelanggan via dialog peta satelit atau GPS')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            TextInput::make('latitude')
                                ->label('Latitude')
                                ->placeholder('cth: -6.2087634')
                                ->extraInputAttributes(['id' => 'customer_lat_input']),
                            TextInput::make('longitude')
                                ->label('Longitude')
                                ->placeholder('cth: 106.845599')
                                ->extraInputAttributes(['id' => 'customer_lng_input']),
                        ]),
                        View::make('filament.forms.components.map-picker')
                            ->columnSpanFull(),
                    ]),

                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        // Kolom Kiri: Jaringan & Billing
                        Group::make([
                            Section::make('Pengaturan Jaringan & Kredensial MikroTik')
                                ->description('Konfigurasi router gateway, paket internet, dan akun akses PPPoE / Hotspot')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 3])->schema([
                                        Select::make('router_id')
                                            ->label('Router Gateway')
                                            ->options(Router::where('is_active', true)->pluck('name', 'id'))
                                            ->default(fn () => Router::where('is_active', true)->value('id'))
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('package_id')
                                            ->label('Paket Internet')
                                            ->options(InternetPackage::where('is_active', true)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('connection_type')
                                            ->label('Tipe Koneksi')
                                            ->options([
                                                'pppoe' => 'PPPoE',
                                                'hotspot' => 'Hotspot',
                                                'static' => 'IP Static',
                                            ])
                                            ->default('pppoe')
                                            ->required(),
                                    ]),
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('pppoe_username')
                                            ->label('Username PPPoE / Hotspot')
                                            ->placeholder('cth: budi_santoso')
                                            ->required(),
                                        TextInput::make('pppoe_password')
                                            ->label('Password PPPoE / Hotspot')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                    ]),
                                    TextInput::make('ip_address')
                                        ->label('IP Address Pelanggan / Remote IP (Otomatis)')
                                        ->default(fn () => app(MikrotikApiService::class)->getNextAvailablePppoeIp())
                                        ->placeholder('cth: 10.20.0.2')
                                        ->helperText('Otomatis dialokasikan dari pool PPPoE anti-konflik Hotspot. Anda tetap dapat menyesuaikannya bila perlu.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Billing & Isolir Otomatis')
                                ->description('Status langganan, siklus jatuh tempo bulanan, dan auto isolir tunggakan')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 3])->schema([
                                        Select::make('status')
                                            ->label('Status Berlangganan')
                                            ->options([
                                                'active' => 'Aktif',
                                                'isolated' => 'Diisolir',
                                                'suspended' => 'Suspended (Berhenti Sementara)',
                                                'pending' => 'Pending (Menunggu Pemasangan)',
                                            ])
                                            ->default('active')
                                            ->required(),
                                        TextInput::make('due_date_day')
                                            ->label('Tanggal Jatuh Tempo Bulanan (1 - 28)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(28)
                                            ->default(10)
                                            ->required(),
                                        Toggle::make('auto_isolate')
                                            ->label('Auto Isolir Jika Telat Bayar')
                                            ->default(true)
                                            ->inline(false),
                                    ]),
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        DatePicker::make('install_date')
                                            ->label('Tanggal Pasang (Instalasi)')
                                            ->placeholder('Pilih tanggal pemasangan')
                                            ->helperText('Digunakan untuk perhitungan tagihan prorata pada bulan pemasangan.'),
                                        TextInput::make('promo_cycles_used')
                                            ->label('Siklus Promo Terpakai')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('Jumlah siklus tagihan harga promo yang telah dipakai. Otomatis reset saat ganti paket.'),
                                    ]),
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        Select::make('technician_id')
                                            ->label('Teknisi Penanggung Jawab / Pasang')
                                            ->options(Technician::where('is_active', true)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Pilih teknisi'),
                                        TextInput::make('optical_power_dbm')
                                            ->label('Redaman Optik OPM (dBm)')
                                            ->placeholder('cth: -19.5')
                                            ->suffix('dBm'),
                                    ]),
                                    Textarea::make('notes')
                                        ->label('Catatan Teknisi')
                                        ->rows(3)
                                        ->placeholder('Catatan teknis pemasangan, kendala, atau informasi pelanggan...')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                        // Kolom Kanan: Perangkat ONT, ODP & Kuota FUP
                        Group::make([
                            Section::make('Perangkat ONT (Modem), TR-069 & Jalur ODP')
                                ->description('Data modem ONT pelanggan, integrasi GenieACS TR-069, dan pemetaan port ODP')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        Select::make('genieacs_server_id')
                                            ->label('Server GenieACS')
                                            ->options(GenieacsServer::where('is_active', true)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('mac_address')
                                            ->label('MAC Address ONT (Opsional)')
                                            ->placeholder('cth: 70:2E:D9:XX:XX:XX'),
                                    ]),
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        TextInput::make('ont_serial_number')
                                            ->label('Serial Number ONT (SN / GPON SN)')
                                            ->placeholder('cth: ZTEGC1234567 / HWTC...'),
                                        TextInput::make('genieacs_device_id')
                                            ->label('GenieACS Device ID')
                                            ->placeholder('cth: 202BC1-HG680P-...'),
                                    ]),
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        Select::make('odp_id')
                                            ->label('Titik ODP (Tiang Fiber)')
                                            ->options(Odp::pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('odp_port')
                                            ->label('Nomor Port ODP (cth: Port 1..16)')
                                            ->numeric(),
                                    ]),
                                ]),

                            Section::make('Status Kuota & FUP (Fair Usage Policy)')
                                ->description('Pengaturan dan pemantauan batas pemakaian kuota bulanan serta OneSignal')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                                        Select::make('fup_status')
                                            ->label('Status FUP Saat Ini')
                                            ->options([
                                                'normal' => 'Normal (Kecepatan Penuh)',
                                                'fup_applied' => 'FUP Terpakai (Kecepatan Disesuaikan)',
                                            ])
                                            ->default('normal')
                                            ->required(),
                                        TextInput::make('fup_override_limit_gb')
                                            ->label('Dispensasi Kuota Khusus (GB, Opsional)')
                                            ->numeric()
                                            ->suffix('GB')
                                            ->placeholder('Kosongkan untuk mengikuti batas paket')
                                            ->helperText('Jika diisi, nilai ini mengabaikan batas FUP paket standar'),
                                    ]),
                                    TextInput::make('onesignal_id')
                                        ->label('OneSignal Player ID (Notifikasi HP Pelanggan)')
                                        ->placeholder('cth: 00000000-0000-0000-0000-000000000000')
                                        ->helperText('ID perangkat OneSignal untuk pengiriman broadcast tagihan & pemeliharaan')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
