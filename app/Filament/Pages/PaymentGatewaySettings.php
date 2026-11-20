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

    protected static ?string $navigationLabel = 'API Payment Gateway & QRIS Dinamis';

    protected static ?string $title = 'Manajemen & Integrasi Payment Gateway & QRIS Dinamis';

    protected ?string $subheading = 'Koneksi barcode QRIS Dinamis 0% MDR dengan auto-scanner, gateway pihak ketiga (Tripay, Midtrans, CekMutasi, Moota, Xendit), dan Webhook pelunasan otomatis';

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
                    // Section 1: Provider & Endpoint Utama
                    Section::make('Metode & Provider Utama Payment Gateway')
                        ->description('Pilih metode payment gateway bawaan QRIS Dinamis atau gateway pihak ketiga (Tripay, Midtrans, CekMutasi, Moota, Xendit).')
                        ->icon('heroicon-o-building-library')
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

                            TextInput::make('payment_gateway_api_url')
                                ->label('Link URL Endpoint API Gateway Pihak Ketiga')
                                ->placeholder('cth: https://tripay.co.id/api/ atau https://api.midtrans.com/v2/')
                                ->helperText('Hanya diperlukan untuk gateway pihak ketiga. Kosongkan jika menggunakan QRIS manual/mutasi.'),

                            TextInput::make('payment_gateway_merchant_code')
                                ->label('Kode Toko / Merchant Code / Merchant ID')
                                ->placeholder('cth: T12345 / Merchant ID')
                                ->helperText('Dapatkan kode merchant dari dashboard penyedia payment gateway Anda.'),
                        ]),

                    // Section 2: QRIS Dinamis Otomatis & Auto Scanner (Bawaan 0% MDR)
                    Section::make('QRIS Dinamis Otomatis & Auto Scanner (Bawaan 0% MDR)')
                        ->description('Unggah gambar atau foto screenshot barcode QRIS toko Anda (BCA, Mandiri, BRI, ShopeePay, GoPay, Nobu, dll). Sistem akan otomatis membaca string EMVCo dan info merchant.')
                        ->icon('heroicon-o-qr-code')
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
                                ->content(fn ($get) => AppSettings::renderQrisPreviewHtml($get('static_qris_string'))),

                            Textarea::make('static_qris_string')
                                ->label('Raw String QRIS Statis EMVCo Merchant')
                                ->placeholder('00020101021126680016ID.CO.QRIS.WWW0118936009990000000001...')
                                ->rows(3)
                                ->live(debounce: 500)
                                ->helperText('Payload EMVCo QRIS resmi toko Anda. Nilai ini otomatis terisi saat foto QRIS diunggah di atas.'),

                            Toggle::make('use_unique_code')
                                ->label('Gunakan 3 Digit Kode Unik Tagihan (cth: Rp 150.245)')
                                ->helperText('Menambahkan 3 digit angka acak unik untuk mempermudah deteksi pembayaran mutasi rekening.')
                                ->default(true),
                        ]),

                    // Section 3: Kredensial & Kunci Rahasia API Pihak Ketiga
                    Section::make('Kredensial & Kunci Rahasia API Gateway Pihak Ketiga')
                        ->description('Kunci autentikasi server untuk gateway Tripay, Midtrans, CekMutasi, Moota, atau Xendit.')
                        ->icon('heroicon-o-key')
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

                    // Section 4: Webhook Callback URL
                    Section::make('Webhook Callback URL (Notifikasi Pembayaran Real-Time)')
                        ->description('Salin alamat URL webhook ini ke pengaturan Callback URL / Notification URL di dashboard payment gateway Anda.')
                        ->icon('heroicon-o-arrow-path-rounded-square')
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

                    // Section 5: Otomasi Pelunasan
                    Section::make('Otomatisasi Sistem Saat Tagihan Lunas')
                        ->description('Aksi yang otomatis dijalankan sistem tepat setelah webhook pembayaran berhasil diverifikasi.')
                        ->icon('heroicon-o-bolt')
                        ->columnSpan(['default' => 1, 'lg' => 2])
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
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanQrisImage')
                ->label('Pindai Foto QRIS')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->modalHeading('Pindai & Ekstrak Data QRIS Merchant')
                ->modalDescription('Unggah file foto atau screenshot barcode QRIS toko Anda. Sistem akan memindai QR code dan menerapkan hasilnya langsung.')
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

                        $this->refreshStatus();

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

            Action::make('testWebhookSimulation')
                ->label('Simulasi Webhook Lunas')
                ->icon('heroicon-o-bolt')
                ->color('gray')
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
                    $invNum = trim($data['test_invoice_number']);
                    $amount = (float) $data['test_amount'];

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
                }),

            Action::make('backToSettings')
                ->label('Kembali ke Pengaturan')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url('/admin/settings')),

            Action::make('save')
                ->label('Simpan Pengaturan Gateway & QRIS')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
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
        IspSetting::set('static_qris_string', trim($state['static_qris_string'] ?? ''));
        IspSetting::set('use_unique_code', ! empty($state['use_unique_code']) ? '1' : '0');

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan Payment Gateway & QRIS Dinamis Berhasil Disimpan!')
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
            'API Payment Gateway & QRIS Dinamis',
        ];
    }
}
