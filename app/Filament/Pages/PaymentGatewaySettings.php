<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\Billing\BillingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PaymentGatewaySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'payment-gateway';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'API Payment Gateway & Webhook';

    protected static ?string $title = 'Manajemen & Integrasi Payment Gateway';

    protected ?string $subheading = 'Koneksi gateway pembayaran otomatis (Tripay, Midtrans, CekMutasi, Moota, Xendit, QRIS) & Webhook notifikasi pelunasan real-time';

    protected string $view = 'filament.pages.payment-gateway-settings';

    public ?array $data = [];

    public string $activeProviderName = '';

    public string $webhookUrl = '';

    public bool $isConfigured = false;

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
        ]);

        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $provider = $this->data['payment_gateway_provider'] ?? IspSetting::get('payment_gateway_provider', 'qris_manual');
        $serverKey = $this->data['payment_gateway_server_key'] ?? IspSetting::get('payment_gateway_server_key', '');

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
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    // Section 1: Provider & Endpoint
                    Section::make('Metode & Provider Payment Gateway')
                        ->description('Pilih metode gateway pihak ketiga atau gunakan sistem barcode QRIS Dinamis bawaan sistem.')
                        ->icon('heroicon-o-building-library')
                        ->schema([
                            Select::make('payment_gateway_provider')
                                ->label('Penyedia / Metode Payment Gateway')
                                ->options([
                                    'qris_manual' => 'QRIS Statis to Dinamis (Bawaan - Tanpa Potongan MDR)',
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
                                ->label('Link URL Endpoint API Gateway')
                                ->placeholder('cth: https://tripay.co.id/api/ atau https://api.midtrans.com/v2/')
                                ->helperText('Kosongkan jika menggunakan mode bawaan QRIS manual atau mutasi.'),

                            TextInput::make('payment_gateway_merchant_code')
                                ->label('Kode Toko / Merchant Code / Merchant ID')
                                ->placeholder('cth: T12345 / Merchant ID')
                                ->helperText('Dapatkan kode merchant dari dashboard penyedia payment gateway Anda.'),
                        ]),

                    // Section 2: Kunci Rahasia API
                    Section::make('Kredensial & Kunci Rahasia API')
                        ->description('Kunci autentikasi server untuk mengotorisasi request transaksi dan webhook callback.')
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

                    // Section 3: Webhook Callback URL
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

                    // Section 4: Otomasi Pelunasan
                    Section::make('Otomatisasi Sistem Saat Tagihan Lunas')
                        ->description('Aksi yang otomatis dijalankan sistem tepat setelah webhook pembayaran berhasil diverifikasi.')
                        ->icon('heroicon-o-bolt')
                        ->schema([
                            Toggle::make('payment_auto_activate')
                                ->label('Otomatis Buka Isolir & Aktifkan Paket PPPoE')
                                ->helperText('Sistem langsung memindahkan profil secret MikroTik dari isolir ke paket langganan aktif.')
                                ->default(true),

                            Toggle::make('payment_auto_receipt')
                                ->label('Otomatis Kirim Kwitansi Lunas via WhatsApp')
                                ->helperText('Kirim pesan tanda terima pembayaran otomatis ke nomor WhatsApp pelanggan.')
                                ->default(true),

                            Toggle::make('payment_auto_telegram')
                                ->label('Kirim Notifikasi Lunas ke Bot Telegram NOC')
                                ->helperText('Kirim alert pembayaran instan ke grup / admin NOC di Telegram.')
                                ->default(true),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWebhookSimulation')
                ->label('Simulasi Webhook Lunas')
                ->icon('heroicon-o-bolt')
                ->color('warning')
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
                ->label('Simpan Pengaturan Gateway')
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

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan Payment Gateway Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'API Payment Gateway & Webhook',
        ];
    }
}
