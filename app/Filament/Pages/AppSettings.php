<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Models\Router;
use App\Services\Notification\WhatsAppService;
use App\Services\Payment\QrisService;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static ?string $title = 'Pengaturan Sistem & Integrasi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.app-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $office = IspSetting::getOfficeLocation();
        $apiKey = IspSetting::get('app_api_key');
        if (empty($apiKey)) {
            $apiKey = 'isp_'.Str::random(32);
            IspSetting::set('app_api_key', $apiKey);
        }

        $this->form->fill([
            // 1. WhatsApp Gateway API
            'whatsapp_gateway_url' => IspSetting::get('whatsapp_gateway_url', 'https://api.fonnte.com/send'),
            'whatsapp_api_key' => IspSetting::get('whatsapp_api_key', ''),
            'whatsapp_sender_number' => IspSetting::get('whatsapp_sender_number', ''),

            // 1b. Telegram Bot API
            'telegram_bot_token' => IspSetting::get('telegram_bot_token', ''),
            'telegram_admin_chat_id' => IspSetting::get('telegram_admin_chat_id', ''),
            'telegram_bot_enabled' => IspSetting::get('telegram_bot_enabled', '1') === '1',

            // 2. Payment Gateway API
            'payment_gateway_provider' => IspSetting::get('payment_gateway_provider', 'qris_manual'),
            'payment_gateway_api_url' => IspSetting::get('payment_gateway_api_url', ''),
            'payment_gateway_server_key' => IspSetting::get('payment_gateway_server_key', ''),
            'payment_gateway_client_key' => IspSetting::get('payment_gateway_client_key', ''),
            'payment_gateway_merchant_code' => IspSetting::get('payment_gateway_merchant_code', ''),
            'payment_webhook_url' => url('/api/payment/webhook'),

            // 3. Mobile Flutter & STB API
            'app_api_base_url' => IspSetting::get('app_api_base_url', url('/api')),
            'app_api_key' => $apiKey,
            'fcm_server_key' => IspSetting::get('fcm_server_key', ''),
            'onesignal_app_id' => IspSetting::get('onesignal_app_id', ''),
            'onesignal_rest_api_key' => IspSetting::get('onesignal_rest_api_key', ''),

            // 4. GenieACS TR-069 API
            'genieacs_api_url' => IspSetting::get('genieacs_api_url', 'http://192.168.2.1:7557'),
            'genieacs_auth_token' => IspSetting::get('genieacs_auth_token', ''),
            'genieacs_username' => IspSetting::get('genieacs_username', ''),
            'genieacs_password' => IspSetting::get('genieacs_password', ''),

            // 5. Maps API
            'google_maps_api_key' => IspSetting::get('google_maps_api_key', ''),

            // 6. Company Profile
            'company_name' => IspSetting::get('company_name', 'NETSPEED WI-FI BROADBAND'),
            'company_phone' => IspSetting::get('company_phone', '0812-3456-7890'),
            'company_address' => IspSetting::get('company_address', 'Jl. Telekomunikasi No. 88, Jakarta'),
            'office_lat' => (string) ($office['lat'] ?? '-6.2087634'),
            'office_lng' => (string) ($office['lng'] ?? '106.845599'),

            // 7. Billing & QRIS
            'qris_image_upload' => IspSetting::get('qris_image_upload', null),
            'static_qris_string' => IspSetting::get('static_qris_string', ''),
            'use_unique_code' => IspSetting::get('use_unique_code', '1') === '1',
            'default_isolir_profile' => IspSetting::get('default_isolir_profile', 'PROFIL-ISOLIR'),

            // 8. PPPoE Network Subnet & Local Address (Anti-Konflik Hotspot)
            'pppoe_local_address' => IspSetting::get('pppoe_local_address', '10.20.0.1'),
            'pppoe_pool_range' => IspSetting::get('pppoe_pool_range', '10.20.0.2-10.20.0.254'),
            'pppoe_dns_servers' => IspSetting::get('pppoe_dns_servers', '8.8.8.8,1.1.1.1'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Pengaturan Sistem')
                    ->tabs([
                        // TAB 1: INTEGRASI API (LINK & API KEY)
                        Tab::make('Integrasi API (Link & Key)')
                            ->icon('heroicon-o-key')
                            ->schema([
                                // 1. WhatsApp Gateway
                                Section::make('API WhatsApp Gateway (Notifikasi Tagihan & Voucher)')
                                    ->description('Konfigurasi link gateway dan API key pengiriman notifikasi WhatsApp ke pelanggan')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('whatsapp_gateway_url')
                                                ->label('Link URL Gateway WhatsApp (API Endpoint)')
                                                ->placeholder('cth: https://api.fonnte.com/send')
                                                ->helperText('Contoh Fonnte: https://api.fonnte.com/send | Wablas: https://solo.wablas.com/api/send-message')
                                                ->required(),

                                            TextInput::make('whatsapp_api_key')
                                                ->label('API Key / Token WhatsApp')
                                                ->placeholder('Masukkan token autentikasi API')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Token API rahasia dari akun penyedia WhatsApp Gateway Anda'),
                                        ]),

                                        TextInput::make('whatsapp_sender_number')
                                            ->label('Nomor WhatsApp Pengirim (Opsional)')
                                            ->placeholder('cth: 081234567890'),
                                    ]),

                                // 1b. Telegram Bot API
                                Section::make('API Bot Telegram (Notifikasi NOC & Bot Interaktif)')
                                    ->description('Pengaturan token bot Telegram untuk monitoring operasional, alert router, dan notifikasi tiket/pembayaran. Kelola lebih lanjut di menu Sistem > Bot Telegram.')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('telegram_bot_token')
                                                ->label('Telegram Bot Token (HTTP API)')
                                                ->placeholder('123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Dapatkan dari @BotFather di Telegram'),

                                            TextInput::make('telegram_admin_chat_id')
                                                ->label('Chat ID Admin / Grup NOC')
                                                ->placeholder('cth: 123456789 atau -100123456789')
                                                ->helperText('ID penerima alert notifikasi sistem (Ketik /id di bot)'),
                                        ]),

                                        Toggle::make('telegram_bot_enabled')
                                            ->label('Aktifkan Notifikasi Bot Telegram')
                                            ->default(true),

                                        Placeholder::make('telegram_console_link')
                                            ->label('Alat Manajemen Bot Telegram')
                                            ->content(new HtmlString('<div class="pt-1"><a href="'.url('/admin/telegram-bot').'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#1a1e1c] text-[#00e07e] border border-[#232826] hover:bg-[#00e07e] hover:text-[#06261a] transition-all"><span>Buka Konsol & Log Interaktif Bot Telegram &rarr;</span></a></div>')),
                                    ]),

                                // 2. Payment Gateway & Webhook
                                Section::make('API Payment Gateway & Webhook Pembayaran Otomatis')
                                    ->description('Pengaturan koneksi API ke Tripay, Midtrans, CekMutasi, atau Moota')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('payment_gateway_provider')
                                                ->label('Penyedia / Metode Payment Gateway')
                                                ->options([
                                                    'qris_manual' => 'QRIS Statis to Dinamis (Bawaan - Tanpa Potongan MDR)',
                                                    'tripay' => 'Tripay (QRIS Dinamis, Virtual Account BCA/Mandiri/BRI, Alfamart)',
                                                    'midtrans' => 'Midtrans (GoPay, QRIS, Virtual Account)',
                                                    'xendit' => 'Xendit',
                                                    'cekmutasi' => 'CekMutasi (Deteksi Mutasi Rekening Otomatis)',
                                                    'moota' => 'Moota (Auto Cek Mutasi Bank)',
                                                ])
                                                ->default('qris_manual')
                                                ->required(),

                                            TextInput::make('payment_gateway_api_url')
                                                ->label('Link URL Endpoint API Payment Gateway')
                                                ->placeholder('cth: https://tripay.co.id/api/ atau https://api.midtrans.com/'),
                                        ]),

                                        Grid::make(3)->schema([
                                            TextInput::make('payment_gateway_server_key')
                                                ->label('API Server Key / Private Key')
                                                ->placeholder('Server Key dari dashboard gateway')
                                                ->password()
                                                ->revealable(),

                                            TextInput::make('payment_gateway_client_key')
                                                ->label('API Client Key / Public Key (Opsional)')
                                                ->placeholder('Client Key publik'),

                                            TextInput::make('payment_gateway_merchant_code')
                                                ->label('Kode Toko / Merchant Code')
                                                ->placeholder('cth: T12345 / Merchant ID'),
                                        ]),

                                        TextInput::make('payment_webhook_url')
                                            ->label('Webhook Callback URL (Salin ini ke Dashboard Payment Gateway)')
                                            ->helperText('Saat pelanggan bayar via QRIS/VA, payment gateway akan mengirim sinyal HTTP POST ke tautan ini untuk otomatis melunasi tagihan & mengaktifkan PPPoE.')
                                            ->readOnly()
                                            ->copyable(),
                                    ]),

                                // 3. Mobile Flutter & STB Client API
                                Section::make('API Aplikasi Mobile Flutter & STB Android')
                                    ->description('Pengaturan base URL dan kunci otorisasi backend untuk aplikasi mobile pelanggan / teknisi')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('app_api_base_url')
                                                ->label('Link Base API Backend (Untuk Flutter / STB)')
                                                ->helperText('Tautan ini diisikan ke variabel baseUrl di aplikasi Flutter / STB Anda')
                                                ->readOnly()
                                                ->copyable(),

                                            TextInput::make('app_api_key')
                                                ->label('App API Secret Key')
                                                ->helperText('Kunci rahasia untuk otorisasi header request aplikasi mobile ke backend')
                                                ->readOnly()
                                                ->copyable(),
                                        ]),

                                        TextInput::make('fcm_server_key')
                                            ->label('Firebase Cloud Messaging (FCM) Server Key')
                                            ->placeholder('Server Key untuk Push Notification Android / iOS')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Dapatkan dari Firebase Console > Project Settings > Cloud Messaging'),

                                        Grid::make(2)->schema([
                                            TextInput::make('onesignal_app_id')
                                                ->label('OneSignal App ID (Push Notification Flutter)')
                                                ->placeholder('cth: 3fa85f64-5717-4562-b3fc-2c963f66afa6')
                                                ->helperText('Dapatkan dari OneSignal Dashboard > App Settings > Keys & IDs'),

                                            TextInput::make('onesignal_rest_api_key')
                                                ->label('OneSignal REST API Key')
                                                ->placeholder('os_v2_app_... atau REST API Key')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Kunci autentikasi server OneSignal'),
                                        ]),

                                        Placeholder::make('onesignal_console_link')
                                            ->label('Alat Push Notifikasi')
                                            ->content(new HtmlString('<div class="pt-1"><a href="'.url('/admin/onesignal').'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#1a1e1c] text-[#00e07e] border border-[#232826] hover:bg-[#00e07e] hover:text-[#06261a] transition-all"><span>Buka Hub Broadcast Push Notifikasi OneSignal &rarr;</span></a></div>')),
                                    ]),

                                // 4. TR-069 GenieACS API
                                Section::make('API TR-069 GenieACS (Modem ONT Pelanggan)')
                                    ->description('Koneksi ke GenieACS NBI untuk cek redaman optik, status ONT, dan reboot modem')
                                    ->icon('heroicon-o-signal')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('genieacs_api_url')
                                                ->label('Link URL Server GenieACS NBI')
                                                ->placeholder('cth: http://192.168.2.1:7557')
                                                ->helperText('Default port REST API NBI GenieACS adalah 7557'),

                                            TextInput::make('genieacs_auth_token')
                                                ->label('API Auth Token GenieACS')
                                                ->placeholder('Bearer Token (Opsional)')
                                                ->password()
                                                ->revealable(),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('genieacs_username')
                                                ->label('Username Basic Auth (Opsional)')
                                                ->placeholder('cth: admin'),

                                            TextInput::make('genieacs_password')
                                                ->label('Password Basic Auth (Opsional)')
                                                ->password()
                                                ->revealable(),
                                        ]),
                                    ]),

                                // 5. Google Maps API
                                Section::make('API Peta & Geocoding')
                                    ->description('Google Maps Javascript API Key untuk fitur satelit hybrid dan geocoding')
                                    ->icon('heroicon-o-map')
                                    ->schema([
                                        TextInput::make('google_maps_api_key')
                                            ->label('Google Maps API Key (Opsional)')
                                            ->placeholder('AIzaSy...')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Jika kosong, sistem secara otomatis menggunakan OpenStreetMap & Google Tile hybrid gratis bawaan.'),
                                    ]),
                            ]),

                        // TAB 2: PROFIL PERUSAHAAN & TITIK PETA
                        Tab::make('Profil ISP & Titik Peta')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make('Identitas Perusahaan / Penyedia Layanan')
                                    ->description('Informasi nama dan kontak ISP yang tercetak di invoice tagihan, struk kasir thermal, dan pesan WhatsApp')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('company_name')
                                                ->label('Nama Brand / Perusahaan ISP')
                                                ->placeholder('cth: NETSPEED WI-FI BROADBAND')
                                                ->required(),

                                            TextInput::make('company_phone')
                                                ->label('Nomor WhatsApp Layanan CS / Bantuan')
                                                ->placeholder('cth: 0812-3456-7890')
                                                ->required(),
                                        ]),

                                        Textarea::make('company_address')
                                            ->label('Alamat Kantor Pusat / NOC')
                                            ->placeholder('Alamat lengkap kantor penyedia internet')
                                            ->rows(3),
                                    ]),

                                Section::make('Koordinat Titik Kantor Pusat / NOC (Pusat Peta)')
                                    ->description('Titik koordinat awal saat teknisi membuka peta lapangan (/tech/map) dan peta admin (/admin/map)')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('office_lat')
                                                ->label('Latitude Kantor Pusat')
                                                ->placeholder('cth: -6.2087634')
                                                ->required(),

                                            TextInput::make('office_lng')
                                                ->label('Longitude Kantor Pusat')
                                                ->placeholder('cth: 106.845599')
                                                ->required(),
                                        ]),
                                    ]),
                            ]),

                        // TAB 3: BILLING, QRIS & ISOLIR
                        Tab::make('Billing, QRIS & Isolir')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('QRIS Dinamis Otomatis & Auto Scanner')
                                    ->description('Unggah gambar/foto barcode QRIS statis toko Anda untuk otomatis membaca string EMVCo dan info merchant, atau masukkan manual.')
                                    ->schema([
                                        FileUpload::make('qris_image_upload')
                                            ->label('Upload Foto / Barcode QRIS Merchant (Scan Otomatis)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('qris-uploads')
                                            ->visibility('public')
                                            ->maxSize(5120)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                            ->live()
                                            ->helperText('Pilih atau drag & drop file foto/screenshot QRIS toko Anda (BCA, Mandiri, BRI, ShopeePay, GoPay, Nobu, dll). Sistem akan langsung membaca QR code dan mengisi string EMVCo di bawah.')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $filePath = self::resolveUploadedFilePath($state);
                                                if (! $filePath || ! file_exists($filePath)) {
                                                    return;
                                                }

                                                $service = app(QrisService::class);
                                                $result = $service->decodeFromImage($filePath);

                                                if ($result['success'] ?? false) {
                                                    $set('static_qris_string', $result['payload']);

                                                    Notification::make()
                                                        ->title('✅ Barcode QRIS Berhasil Dipindai!')
                                                        ->body("Merchant: {$result['merchant_name']}\nKota: {$result['merchant_city']}\nFormat EMVCo valid telah diterapkan ke sistem.")
                                                        ->success()
                                                        ->duration(8000)
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('Gagal Memindai QR Code')
                                                        ->body($result['message'] ?? 'Tidak ditemukan QR code yang valid pada gambar.')
                                                        ->danger()
                                                        ->duration(9000)
                                                        ->send();
                                                }
                                            }),

                                        Placeholder::make('qris_preview_display')
                                            ->label('Status & Preview Barcode QRIS Merchant')
                                            ->content(fn ($get) => self::renderQrisPreviewHtml($get('static_qris_string'))),

                                        Textarea::make('static_qris_string')
                                            ->label('Raw String QRIS Statis EMVCo Merchant')
                                            ->placeholder('00020101021126680016ID.CO.QRIS.WWW0118936009990000000001...')
                                            ->rows(3)
                                            ->live(debounce: 500)
                                            ->helperText('Payload EMVCo QRIS resmi toko Anda. Nilai ini otomatis terisi saat foto QRIS diunggah di atas. Anda juga dapat mengubahnya secara manual jika diperlukan.'),

                                        Toggle::make('use_unique_code')
                                            ->label('Gunakan 3 Digit Kode Unik Tagihan')
                                            ->helperText('Menambahkan 3 digit angka acak (cth: Rp 150.245) untuk mempermudah deteksi pembayaran mutasi rekening.')
                                            ->default(true),
                                    ]),

                                Section::make('Profil Isolir Otomatis MikroTik')
                                    ->description('Profil kecepatan yang diterapkan ke pelanggan PPPoE saat menunggak pembayaran')
                                    ->schema([
                                        TextInput::make('default_isolir_profile')
                                            ->label('Nama Profil Isolir MikroTik (/ppp/profile)')
                                            ->placeholder('cth: PROFIL-ISOLIR atau ISOLIR')
                                            ->required(),

                                        Placeholder::make('isolir_wizard_link')
                                            ->label('Alat Redirect Isolir')
                                            ->content(new HtmlString('<div class="pt-1"><a href="'.url('/admin/isolir-manager').'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#1a1e1c] text-[#00e07e] border border-[#232826] hover:bg-[#00e07e] hover:text-[#06261a] transition-all"><span>Buka Wizard Script Isolir & Captive MikroTik &rarr;</span></a></div>')),
                                    ]),

                                Section::make('Subnet Jaringan PPPoE & Anti-Konflik Hotspot')
                                    ->description('Konfigurasi IP Gateway (Local Address) dan Pool IP dinamis yang otomatis disematkan pada secret & profil PPPoE agar terisolasi penuh dari subnet Hotspot')
                                    ->icon('heroicon-o-shield-check')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('pppoe_local_address')
                                                ->label('Local Address (Gateway PPPoE)')
                                                ->placeholder('cth: 10.20.0.1')
                                                ->helperText('IP gateway lokal tunnel PPPoE di MikroTik')
                                                ->required(),
                                            TextInput::make('pppoe_pool_range')
                                                ->label('Range IP Pool Dinamis (POOL-PPPOE)')
                                                ->placeholder('cth: 10.20.0.2-10.20.0.254')
                                                ->helperText('Dibuat otomatis di /ip/pool MikroTik')
                                                ->required(),
                                            TextInput::make('pppoe_dns_servers')
                                                ->label('DNS Server PPPoE')
                                                ->placeholder('cth: 8.8.8.8,1.1.1.1')
                                                ->required(),
                                        ]),
                                    ]),
                            ]),

                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWhatsApp')
                ->label('Test Kirim WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->form([
                    TextInput::make('test_phone')
                        ->label('Nomor WhatsApp Tujuan')
                        ->placeholder('cth: 081234567890')
                        ->tel()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $phone = $data['test_phone'];
                    $service = app(WhatsAppService::class);
                    $company = $this->data['company_name'] ?? 'ISP Billing';
                    $msg = "Halo! Ini adalah pesan uji coba integrasi API WhatsApp Gateway dari *{$company}*.\n\nWaktu: ".now()->format('d M Y, H:i:s')."\nStatus: ✅ Sukses Terhubung!";

                    $res = $service->sendMessage($phone, $msg);
                    if ($res['success'] ?? false) {
                        Notification::make()->title('WhatsApp Berhasil Terkirim!')->success()->send();
                    } else {
                        Notification::make()->title('Gagal Kirim WhatsApp')->body($res['message'] ?? 'Error')->danger()->send();
                    }
                }),

            Action::make('testGenieacs')
                ->label('Test GenieACS')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $url = $this->data['genieacs_api_url'] ?? '';
                    if (empty($url)) {
                        Notification::make()->title('URL GenieACS kosong')->warning()->send();

                        return;
                    }
                    try {
                        $req = Http::timeout(5)->baseUrl(rtrim($url, '/'));
                        if (! empty($this->data['genieacs_auth_token'])) {
                            $req->withToken($this->data['genieacs_auth_token']);
                        } elseif (! empty($this->data['genieacs_username']) && ! empty($this->data['genieacs_password'])) {
                            $req->withBasicAuth($this->data['genieacs_username'], $this->data['genieacs_password']);
                        }
                        $res = $req->get('/devices', ['limit' => 1]);
                        if ($res->successful()) {
                            Notification::make()->title('Koneksi GenieACS Sukses (HTTP '.$res->status().')')->success()->send();
                        } else {
                            Notification::make()->title('GenieACS Error: HTTP '.$res->status())->warning()->send();
                        }
                    } catch (Exception $e) {
                        Notification::make()->title('Gagal terhubung ke GenieACS')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('generateNewApiKey')
                ->label('Buat API Key Mobile Baru')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $newKey = 'isp_'.Str::random(32);
                    $this->data['app_api_key'] = $newKey;
                    IspSetting::set('app_api_key', $newKey);
                    Notification::make()->title('API Key Baru Dibuat')->body($newKey)->success()->send();
                }),

            Action::make('scanQrisImage')
                ->label('Scan Foto QRIS')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->modalHeading('Pindai & Ekstrak Data QRIS Merchant')
                ->modalDescription('Unggah file foto atau screenshot barcode QRIS toko Anda. Sistem akan memindai QR code dan menerapkan hasilnya langsung ke pengaturan.')
                ->modalSubmitActionLabel('Pindai & Terapkan Otomatis')
                ->form([
                    FileUpload::make('modal_qris_image')
                        ->label('File Foto / Screenshot QRIS')
                        ->image()
                        ->disk('public')
                        ->directory('qris-uploads')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $state = $data['modal_qris_image'] ?? null;
                    $filePath = self::resolveUploadedFilePath($state);

                    if (! $filePath || ! file_exists($filePath)) {
                        Notification::make()->title('File gambar tidak ditemukan')->danger()->send();

                        return;
                    }

                    $service = app(QrisService::class);
                    $res = $service->decodeFromImage($filePath);

                    if ($res['success']) {

                        $this->data['static_qris_string'] = $res['payload'];
                        $this->data['qris_image_upload'] = $state;
                        IspSetting::set('static_qris_string', $res['payload']);
                        if (is_string($state)) {
                            IspSetting::set('qris_image_upload', $state);
                        }

                        Notification::make()
                            ->title('✅ Barcode QRIS Berhasil Diterapkan!')
                            ->body("Merchant: {$res['merchant_name']}\nKota: {$res['merchant_city']}\nPengaturan QRIS telah otomatis diperbarui.")
                            ->success()
                            ->duration(8000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Memindai QRIS')
                            ->body($res['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // 1. WhatsApp Gateway
        IspSetting::set('whatsapp_gateway_url', trim($state['whatsapp_gateway_url'] ?? ''));
        IspSetting::set('whatsapp_api_key', trim($state['whatsapp_api_key'] ?? ''));
        IspSetting::set('whatsapp_sender_number', trim($state['whatsapp_sender_number'] ?? ''));

        // 1b. Telegram Bot
        IspSetting::set('telegram_bot_token', trim($state['telegram_bot_token'] ?? ''));
        IspSetting::set('telegram_admin_chat_id', trim($state['telegram_admin_chat_id'] ?? ''));
        IspSetting::set('telegram_bot_enabled', ! empty($state['telegram_bot_enabled']) ? '1' : '0');

        // 2. Payment Gateway
        IspSetting::set('payment_gateway_provider', $state['payment_gateway_provider'] ?? 'qris_manual');
        IspSetting::set('payment_gateway_api_url', trim($state['payment_gateway_api_url'] ?? ''));
        IspSetting::set('payment_gateway_server_key', trim($state['payment_gateway_server_key'] ?? ''));
        IspSetting::set('payment_gateway_client_key', trim($state['payment_gateway_client_key'] ?? ''));
        IspSetting::set('payment_gateway_merchant_code', trim($state['payment_gateway_merchant_code'] ?? ''));

        // 3. Mobile Flutter API
        IspSetting::set('app_api_base_url', trim($state['app_api_base_url'] ?? ''));
        IspSetting::set('app_api_key', trim($state['app_api_key'] ?? ''));
        IspSetting::set('fcm_server_key', trim($state['fcm_server_key'] ?? ''));
        IspSetting::set('onesignal_app_id', trim($state['onesignal_app_id'] ?? ''));
        IspSetting::set('onesignal_rest_api_key', trim($state['onesignal_rest_api_key'] ?? ''));

        // 4. GenieACS API
        IspSetting::set('genieacs_api_url', trim($state['genieacs_api_url'] ?? ''));
        IspSetting::set('genieacs_auth_token', trim($state['genieacs_auth_token'] ?? ''));
        IspSetting::set('genieacs_username', trim($state['genieacs_username'] ?? ''));
        IspSetting::set('genieacs_password', trim($state['genieacs_password'] ?? ''));

        // 5. Maps API
        IspSetting::set('google_maps_api_key', trim($state['google_maps_api_key'] ?? ''));

        // 6. Company Profile
        IspSetting::set('company_name', trim($state['company_name'] ?? ''));
        IspSetting::set('company_phone', trim($state['company_phone'] ?? ''));
        IspSetting::set('company_address', trim($state['company_address'] ?? ''));
        IspSetting::set('office_lat', trim($state['office_lat'] ?? ''));
        IspSetting::set('office_lng', trim($state['office_lng'] ?? ''));

        // 7. Billing & QRIS
        if (isset($state['qris_image_upload']) && is_string($state['qris_image_upload'])) {
            IspSetting::set('qris_image_upload', $state['qris_image_upload']);
        }
        IspSetting::set('static_qris_string', trim($state['static_qris_string'] ?? ''));
        IspSetting::set('use_unique_code', ! empty($state['use_unique_code']) ? '1' : '0');
        IspSetting::set('default_isolir_profile', trim($state['default_isolir_profile'] ?? 'PROFIL-ISOLIR'));
        IspSetting::set('isolir_profile', trim($state['default_isolir_profile'] ?? 'PROFIL-ISOLIR'));

        // 8. PPPoE Subnet (Anti-Konflik Hotspot)
        IspSetting::set('pppoe_local_address', trim($state['pppoe_local_address'] ?? '10.20.0.1'));
        IspSetting::set('pppoe_pool_range', trim($state['pppoe_pool_range'] ?? '10.20.0.2-10.20.0.254'));
        IspSetting::set('pppoe_dns_servers', trim($state['pppoe_dns_servers'] ?? '8.8.8.8,1.1.1.1'));

        // Update settings.json
        $jsonPath = config_path('settings.json');
        $json = [
            'office_name' => trim($state['company_name'] ?? 'Kantor Pusat ISP'),
            'office_lat' => (float) ($state['office_lat'] ?? -6.2087634),
            'office_lng' => (float) ($state['office_lng'] ?? 106.845599),
            'default_zoom' => 16,
        ];
        @file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Auto-sync isolir template to active routers with latest company_phone & company_name
        try {
            $activeRouters = Router::where('is_active', true)->get();
            if ($activeRouters->isNotEmpty()) {
                $service = app(MikrotikApiService::class);
                $html = $service->getIsolirHtmlContent([
                    'company_name' => trim($state['company_name'] ?? ''),
                    'company_phone' => trim($state['company_phone'] ?? ''),
                ]);
                foreach ($activeRouters as $r) {
                    $service->uploadIsolirHtmlToRouter($r, $html);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Auto-sync isolir page failed: {$e->getMessage()}");
        }

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan!')
            ->body('Seluruh link API, API key, dan preferensi sistem telah diperbarui dengan aman.')
            ->success()
            ->send();
    }

    /**
     * Resolve the local filesystem path from a FileUpload component state.
     */
    public static function resolveUploadedFilePath(mixed $state): ?string
    {
        if (empty($state)) {
            return null;
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        if (is_string($state)) {
            $storagePath = Storage::disk('public')->path($state);
            if (file_exists($storagePath)) {
                return $storagePath;
            }
            if (file_exists($state)) {
                return $state;
            }
        }

        if (is_array($state) && count($state) > 0) {
            $first = reset($state);

            return self::resolveUploadedFilePath($first);
        }

        return null;
    }

    /**
     * Render rich preview HTML for the configured QRIS string.
     */
    public static function renderQrisPreviewHtml(?string $qris): HtmlString
    {
        $cleanQris = trim($qris ?? '');

        if (empty($cleanQris)) {
            return new HtmlString('
                <div class="flex items-center gap-3 p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 text-gray-500 dark:text-gray-400">
                    <div class="p-2.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <div class="text-xs">
                        <span class="font-semibold text-gray-700 dark:text-gray-200 block text-sm">Belum Ada Barcode QRIS Terdaftar</span>
                        Silakan unggah foto barcode QRIS toko Anda pada kotak upload di atas. Sistem akan membaca barcode secara otomatis, memverifikasi EMVCo Bank Indonesia, dan menampilkan preview kode QR di sini.
                    </div>
                </div>
            ');
        }

        try {
            $service = app(QrisService::class);
            $details = $service->getQrisDetails($cleanQris);
            $svgUri = $service->renderDataUri($cleanQris);

            $merchant = e($details['merchant_name'] ?? 'Merchant QRIS');
            $city = e($details['merchant_city'] ?? '-');
            $method = e($details['initiation_method'] ?? 'Statis');
            $crc = e($details['crc'] ?? '');

            $crcBadge = $details['crc_valid']
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">CRC Valid ('.$crc.')</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">CRC: '.$crc.'</span>';

            return new HtmlString('
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-800/80 shadow-sm flex flex-col sm:flex-row items-center gap-5">
                    <div class="bg-white p-2 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm shrink-0">
                        <img src="'.$svgUri.'" alt="QRIS Merchant Barcode" class="w-32 h-32 object-contain" />
                    </div>
                    <div class="space-y-2 text-left flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                Standar EMVCo QRIS Terverifikasi
                            </span>
                            '.$crcBadge.'
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white truncate">'.$merchant.'</h4>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                            <p><span class="font-medium text-gray-700 dark:text-gray-300">Kota Merchant:</span> '.$city.'</p>
                            <p><span class="font-medium text-gray-700 dark:text-gray-300">Mode Sistem:</span> QRIS '.$method.' &rarr; Otomatis disuntikkan nominal tagihan unik saat pelanggan checkout</p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 pt-0.5">Mendukung pembayaran dari BCA Mobile, Livin by Mandiri, BRImo, BNI Mobile, GoPay, OVO, Dana, ShopeePay, LinkAja, dll.</p>
                        </div>
                    </div>
                </div>
            ');
        } catch (\Throwable $e) {
            return new HtmlString('<div class="p-3 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800">Format string QRIS belum lengkap atau tidak valid: '.e($e->getMessage()).'</div>');
        }
    }
}
