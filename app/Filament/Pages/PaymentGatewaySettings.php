<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\Billing\BillingService;
use App\Services\Payment\QrisService;
use BackedEnum;
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
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class PaymentGatewaySettings extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $slug = 'payment-gateway';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'API Payment Gateway & Webhook';

    protected static ?string $title = 'Manajemen & Integrasi Payment Gateway';

    protected ?string $subheading = 'Konfigurasi pembayaran QRIS Dinamis 0% MDR bawaan, gateway pihak ketiga (Tripay, Midtrans, CekMutasi, Moota, Xendit), dan webhook pelunasan real-time';

    protected string $view = 'filament.pages.payment-gateway-settings';

    public ?array $data = [];

    public string $activeProviderName = '';

    public string $webhookUrl = '';

    public bool $isConfigured = false;

    public bool $hasQrisConfigured = false;

    public string $qrisMerchantName = '';

    public function mount(): void
    {
        $this->webhookUrl = url('/api/payment/webhook');

        $provider = IspSetting::get('payment_gateway_provider', 'qris_manual');
        $serverKey = IspSetting::get('payment_gateway_server_key', '');

        $this->form->fill([
            'payment_gateway_provider' => $provider,
            'payment_gateway_api_url' => IspSetting::get('payment_gateway_api_url', ''),
            'payment_gateway_server_key' => $serverKey,
            'payment_gateway_client_key' => IspSetting::get('payment_gateway_client_key', ''),
            'payment_gateway_merchant_code' => IspSetting::get('payment_gateway_merchant_code', ''),
            'payment_webhook_secret' => IspSetting::get('payment_webhook_secret', ''),
            'payment_auto_activate' => IspSetting::get('payment_auto_activate', '1') === '1',
            'payment_auto_receipt' => IspSetting::get('payment_auto_receipt', '1') === '1',
            'payment_auto_telegram' => IspSetting::get('payment_auto_telegram', '1') === '1',

            // QRIS Dinamis & Auto Scanner Fields
            'qris_image_upload' => IspSetting::get('qris_image_upload', null),
            'static_qris_string' => IspSetting::get('static_qris_string', ''),
            'use_unique_code' => IspSetting::get('use_unique_code', '1') === '1',
        ]);

        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $provider = $this->data['payment_gateway_provider'] ?? IspSetting::get('payment_gateway_provider', 'qris_manual');
        $serverKey = $this->data['payment_gateway_server_key'] ?? IspSetting::get('payment_gateway_server_key', '');
        $qris = $this->data['static_qris_string'] ?? IspSetting::get('static_qris_string', '');

        $providerLabels = [
            'qris_manual' => 'QRIS Statis to Dinamis (Bawaan - 0% MDR)',
            'tripay' => 'Tripay Payment Gateway (QRIS, VA, Retail)',
            'midtrans' => 'Midtrans (GoPay, QRIS, Virtual Account)',
            'xendit' => 'Xendit Gateway',
            'cekmutasi' => 'CekMutasi (Auto Deteksi Rekening Bank)',
            'moota' => 'Moota (Auto Cek Mutasi Bank)',
        ];

        $this->activeProviderName = $providerLabels[$provider] ?? strtoupper($provider);
        $this->isConfigured = ($provider === 'qris_manual') || ! empty(trim($serverKey));

        $cleanQris = trim($qris ?? '');
        $this->hasQrisConfigured = ! empty($cleanQris);
        if ($this->hasQrisConfigured) {
            try {
                $service = app(QrisService::class);
                $this->qrisMerchantName = $service->getMerchantName($cleanQris);
            } catch (\Throwable $e) {
                $this->qrisMerchantName = 'QRIS Terdaftar';
            }
        } else {
            $this->qrisMerchantName = 'Belum Ada Barcode QRIS';
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    // Card 1: Metode & Provider Utama Payment Gateway (Termasuk QRIS jika dipilih)
                    Section::make('Metode & Provider Utama Payment Gateway')
                        ->description('Pilih metode payment gateway bawaan QRIS Dinamis atau gateway pihak ketiga (Tripay, Midtrans, CekMutasi, Moota, Xendit).')
                        ->icon('heroicon-o-building-library')
                        ->columnSpan(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') === 'qris_manual' ? ['default' => 1, 'lg' => 2] : 1)
                        ->schema([
                            Select::make('payment_gateway_provider')
                                ->label('Penyedia / Metode Pembayaran Aktif')
                                ->options([
                                    'qris_manual' => 'QRIS Statis to Dinamis (Bawaan - 0% Potongan MDR)',
                                    'tripay' => 'Tripay (QRIS Dinamis, Virtual Account BCA/Mandiri/BRI, Alfamart)',
                                    'midtrans' => 'Midtrans (GoPay, QRIS, Virtual Account)',
                                    'xendit' => 'Xendit Payment Gateway',
                                    'cekmutasi' => 'CekMutasi (Deteksi Mutasi Rekening Otomatis)',
                                    'moota' => 'Moota (Auto Cek Mutasi Bank)',
                                ])
                                ->default('qris_manual')
                                ->required()
                                ->live(),

                            // --- TAMPIL HANYA JIKA MEMILIH QRIS STATIS TO DINAMIS (DI DALAM CARD INI) ---
                            FileUpload::make('qris_image_upload')
                                ->label('Upload Foto / Barcode QRIS Merchant (Scan Otomatis)')
                                ->image()
                                ->disk('public')
                                ->directory('qris-uploads')
                                ->visibility('public')
                                ->maxSize(5120)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                ->live()
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') === 'qris_manual')
                                ->helperText('Pilih atau drag & drop file foto/screenshot barcode QRIS toko Anda. Sistem langsung memindai QR code dan mengisi string EMVCo secara instan.')
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
                                        $this->refreshStatus();

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
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') === 'qris_manual')
                                ->content(fn ($get) => AppSettings::renderQrisPreviewHtml($get('static_qris_string'))),

                            Textarea::make('static_qris_string')
                                ->label('Raw String QRIS Statis EMVCo Merchant')
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') === 'qris_manual')
                                ->placeholder('00020101021126680016ID.CO.QRIS.WWW0118936009990000000001...')
                                ->rows(3)
                                ->live(debounce: 500)
                                ->helperText('Payload EMVCo QRIS resmi toko Anda. Nilai ini otomatis terisi saat foto QRIS diunggah di atas.'),

                            Toggle::make('use_unique_code')
                                ->label('Gunakan 3 Digit Kode Unik Tagihan (cth: Rp 150.245)')
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') === 'qris_manual')
                                ->helperText('Menambahkan 3 digit angka acak unik untuk mempermudah deteksi pembayaran mutasi rekening.')
                                ->default(true),

                            // --- TAMPIL HANYA JIKA MEMILIH GATEWAY PIHAK KETIGA ---
                            TextInput::make('payment_gateway_api_url')
                                ->label('Link URL Endpoint API Gateway Pihak Ketiga')
                                ->placeholder('cth: https://tripay.co.id/api/ atau https://api.midtrans.com/v2/')
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') !== 'qris_manual')
                                ->helperText('Link endpoint API resmi dari dashboard gateway pihak ketiga Anda.'),

                            TextInput::make('payment_gateway_merchant_code')
                                ->label('Kode Toko / Merchant Code / Merchant ID')
                                ->placeholder('cth: T12345 / Merchant ID')
                                ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') !== 'qris_manual')
                                ->helperText('Dapatkan kode merchant dari dashboard penyedia payment gateway Anda.'),
                        ]),

                    // Card 2: Kredensial & Kunci Rahasia API Gateway Pihak Ketiga (Hanya untuk 3rd party)
                    Section::make('Kredensial & Kunci Rahasia API Gateway Pihak Ketiga')
                        ->description('Kunci autentikasi server untuk gateway Tripay, Midtrans, CekMutasi, Moota, atau Xendit.')
                        ->icon('heroicon-o-key')
                        ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') !== 'qris_manual')
                        ->schema([
                            TextInput::make('payment_gateway_server_key')
                                ->label('API Server Key / Private Key')
                                ->placeholder('Masukkan Server Key / Private Key rahasia')
                                ->password()
                                ->revealable()
                                ->helperText('Kunci rahasia dari dashboard gateway untuk pembuatan transaksi.'),

                            TextInput::make('payment_gateway_client_key')
                                ->label('API Client Key / Public Key (Opsional)')
                                ->placeholder('Masukkan Client Key publik')
                                ->helperText('Diperlukan untuk integrasi frontend snap / checkout popup tertentu.'),

                            TextInput::make('payment_webhook_secret')
                                ->label('Webhook Secret / Callback Signature (Opsional)')
                                ->placeholder('Secret key untuk verifikasi signature payload webhook')
                                ->password()
                                ->revealable()
                                ->helperText('Digunakan untuk memvalidasi keaslian data notifikasi callback dari gateway.'),
                        ]),

                    // Card 3: Webhook Callback URL (Hanya untuk 3rd party)
                    Section::make('Webhook Callback URL (Notifikasi Pembayaran Real-Time)')
                        ->description('Salin alamat URL webhook ini ke pengaturan Callback URL / Notification URL di dashboard payment gateway Anda.')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->visible(fn ($get) => ($get('payment_gateway_provider') ?? 'qris_manual') !== 'qris_manual')
                        ->columnSpan(['default' => 1, 'lg' => 2])
                        ->schema([
                            Placeholder::make('payment_webhook_display')
                                ->label('Alamat Webhook Endpoint Aplikasi')
                                ->content(fn () => new HtmlString('
                                    <div style="display: flex; align-items: center; gap: 0.75rem; background: #131615; border: 1px solid #232826; border-radius: 0.5rem; padding: 0.75rem 1rem;">
                                        <code style="font-size: 0.85rem; color: #00e07e; font-family: monospace; word-break: break-all; flex: 1;">'.url('/api/payment/webhook').'</code>
                                    </div>
                                    <p style="font-size: 0.75rem; color: #9aa49f; margin-top: 0.5rem;">
                                        Method: <strong>POST</strong> &bull; Format Payload: JSON &bull; Mendukung format Tripay, Midtrans, CekMutasi, dan Moota.
                                    </p>
                                ')),
                        ]),

                    // Card 4: Otomasi Pelunasan & Simulasi Webhook Lunas
                    Section::make('Otomatisasi Sistem Saat Tagihan Lunas')
                        ->description('Aksi yang otomatis dijalankan sistem tepat setelah pembayaran tagihan berhasil diverifikasi.')
                        ->icon('heroicon-o-bolt')
                        ->columnSpan(['default' => 1, 'lg' => 2])
                        ->headerActions([
                            Action::make('testWebhookSimulation')
                                ->label('Simulasi Webhook Lunas')
                                ->icon('heroicon-o-bolt')
                                ->color('warning')
                                ->modalHeading('Simulasi Webhook Pelunasan Tagihan')
                                ->modalDescription('Masukkan nomor tagihan (invoice) dan nominal untuk menguji alur otomatisasi sistem saat menerima notifikasi webhook pembayaran lunas.')
                                ->modalSubmitActionLabel('Jalankan Simulasi Sekarang')
                                ->form([
                                    TextInput::make('test_invoice_number')
                                        ->label('Nomor Tagihan (Invoice)')
                                        ->placeholder('cth: INV-2026-0001')
                                        ->required(),
                                    TextInput::make('test_amount')
                                        ->label('Nominal Pembayaran (Rp)')
                                        ->numeric()
                                        ->placeholder('cth: 150000')
                                        ->required(),
                                ])
                                ->action(function (array $data) {
                                    $this->executeWebhookSimulation($data);
                                }),
                        ])
                        ->schema([
                            Grid::make(3)->schema([
                                Toggle::make('payment_auto_activate')
                                    ->label('Otomatis Buka Isolir & Aktifkan Paket PPPoE')
                                    ->helperText('Sistem langsung memindahkan profil secret MikroTik dari isolir ke paket aktif.')
                                    ->default(true),

                                Toggle::make('payment_auto_receipt')
                                    ->label('Otomatis Kirim Kwitansi via WhatsApp')
                                    ->helperText('Kirim pesan bukti lunas otomatis ke WhatsApp pelanggan.')
                                    ->default(true),

                                Toggle::make('payment_auto_telegram')
                                    ->label('Kirim Notifikasi Lunas ke Bot Telegram NOC')
                                    ->helperText('Kirim alert pembayaran instan ke grup / admin NOC Telegram.')
                                    ->default(true),
                            ]),

                            Placeholder::make('webhook_simulation_panel')
                                ->label('Uji Coba & Simulasi Notifikasi Webhook')
                                ->content(fn () => new HtmlString('
                                    <div style="background: #131615; border: 1px dashed #2e3532; border-radius: 0.75rem; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                                        <div style="flex: 1; min-width: 260px;">
                                            <div style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; display: flex; align-items: center; gap: 6px;">
                                                <span class="material-symbols-rounded" style="font-size: 20px; color: #f59e0b;">bolt</span>
                                                Simulasi Webhook Pelunasan Real-Time
                                            </div>
                                            <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0; line-height: 1.4;">
                                                Uji alur buka isolir MikroTik, pengiriman kwitansi WhatsApp, dan notifikasi Bot Telegram secara instan tanpa menunggu transfer pembayaran asli.
                                            </p>
                                        </div>
                                    </div>
                                ')),

                            Actions::make([
                                Action::make('runWebhookSimulation')
                                    ->label('⚡ Jalankan Simulasi Webhook Lunas')
                                    ->icon('heroicon-o-bolt')
                                    ->color('warning')
                                    ->modalHeading('Simulasi Webhook Pelunasan Tagihan')
                                    ->modalDescription('Masukkan nomor tagihan (invoice) dan nominal untuk menguji alur otomatisasi sistem saat menerima notifikasi webhook pembayaran lunas.')
                                    ->modalSubmitActionLabel('Jalankan Simulasi Sekarang')
                                    ->form([
                                        TextInput::make('test_invoice_number')
                                            ->label('Nomor Tagihan (Invoice)')
                                            ->placeholder('cth: INV-2026-0001')
                                            ->required(),
                                        TextInput::make('test_amount')
                                            ->label('Nominal Pembayaran (Rp)')
                                            ->numeric()
                                            ->placeholder('cth: 150000')
                                            ->required(),
                                    ])
                                    ->action(function (array $data) {
                                        $this->executeWebhookSimulation($data);
                                    }),
                            ]),
                        ]),
                ]),
            ]);
    }

    /**
     * Header actions dikosongkan agar header halaman bersih, rapi, dan tidak ada tombol bertumpuk.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function executeWebhookSimulation(array $data): void
    {
        $invNum = trim($data['test_invoice_number'] ?? '');
        $amount = (float) ($data['test_amount'] ?? 0);

        $invoice = Invoice::where('invoice_number', $invNum)->first();
        if (! $invoice) {
            Notification::make()
                ->title('Invoice Tidak Ditemukan')
                ->body("Tagihan dengan nomor {$invNum} tidak ada di database.")
                ->danger()
                ->send();

            return;
        }

        if ($invoice->status === 'paid') {
            Notification::make()
                ->title('Invoice Sudah Lunas')
                ->body("Tagihan {$invNum} statusnya sudah Lunas.")
                ->info()
                ->send();

            return;
        }

        try {
            $billingService = app(BillingService::class);
            $billingService->processPayment($invoice, $amount, 'payment_gateway', 'SIMULASI-WEBHOOK-'.now()->timestamp);

            Notification::make()
                ->title('✅ Simulasi Webhook Berhasil!')
                ->body("Invoice {$invNum} berhasil dilunasi & PPPoE pelanggan telah diaktifkan.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Memproses Pelunasan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        $state = $this->form->getState();

        IspSetting::set('payment_gateway_provider', $state['payment_gateway_provider'] ?? 'qris_manual');
        IspSetting::set('payment_gateway_api_url', trim($state['payment_gateway_api_url'] ?? ''));
        IspSetting::set('payment_gateway_server_key', trim($state['payment_gateway_server_key'] ?? ''));
        IspSetting::set('payment_gateway_client_key', trim($state['payment_gateway_client_key'] ?? ''));
        IspSetting::set('payment_gateway_merchant_code', trim($state['payment_gateway_merchant_code'] ?? ''));
        IspSetting::set('payment_webhook_secret', trim($state['payment_webhook_secret'] ?? ''));

        IspSetting::set('payment_auto_activate', ! empty($state['payment_auto_activate']) ? '1' : '0');
        IspSetting::set('payment_auto_receipt', ! empty($state['payment_auto_receipt']) ? '1' : '0');
        IspSetting::set('payment_auto_telegram', ! empty($state['payment_auto_telegram']) ? '1' : '0');

        // Save QRIS Settings
        if (isset($state['qris_image_upload']) && is_string($state['qris_image_upload'])) {
            IspSetting::set('qris_image_upload', $state['qris_image_upload']);
        }
        if (array_key_exists('static_qris_string', $state)) {
            IspSetting::set('static_qris_string', trim($state['static_qris_string'] ?? ''));
        }
        if (array_key_exists('use_unique_code', $state)) {
            IspSetting::set('use_unique_code', ! empty($state['use_unique_code']) ? '1' : '0');
        }

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan Payment Gateway Berhasil Disimpan!')
            ->success()
            ->send();
    }

    protected static function resolveUploadedFilePath(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        if (is_string($state)) {
            $storage = Storage::disk('public');
            if ($storage->exists($state)) {
                return $storage->path($state);
            }
            $fullPath = storage_path('app/public/'.$state);
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'API Payment Gateway & Webhook',
        ];
    }
}
