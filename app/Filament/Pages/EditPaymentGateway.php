<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\PaymentGateway;
use App\Services\Billing\BillingService;
use App\Services\Payment\QrisService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithFileUploads;
use UnitEnum;

class EditPaymentGateway extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'payment-gateway/edit';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Billing & Keuangan';

    protected static ?string $title = 'Konfigurasi Payment Gateway';

    protected string $view = 'filament.pages.edit-payment-gateway';

    public int $record;

    public ?PaymentGateway $gateway = null;

    // Basic Info
    public string $name = '';

    public string $code = '';

    public string $category = 'digital';

    public string $description = '';

    public bool $isActive = false;

    // Fees
    public string $feeType = 'flat'; // 'flat' or 'percent'

    public float $feeFlat = 0;

    public float $feePercent = 0;

    // Instructions
    public string $instructions = '';

    // QRIS Config
    public ?string $staticQris = '';

    public bool $useUniqueCode = true;

    public $qrisImage = null;

    // Bank Transfer Config
    public ?string $bankName = '';

    public ?string $accountNumber = '';

    public ?string $accountHolder = '';

    // Gateway API Config
    public string $mode = 'production'; // 'production' or 'sandbox'

    public ?string $apiUrl = '';

    public ?string $serverKey = '';

    public ?string $clientKey = '';

    public ?string $merchantCode = '';

    public ?string $webhookSecret = '';

    // Webhook URL
    public string $webhookUrl = '';

    public static function getRoutePath(Panel $panel): string
    {
        return '/payment-gateway/{record}/edit';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'payment-gateway.edit';
    }

    public function mount(int|string $record): void
    {
        $this->record = (int) $record;
        $this->gateway = PaymentGateway::findOrFail($this->record);

        $this->code = $this->gateway->code;
        $this->name = $this->gateway->name;
        $this->category = $this->gateway->category ?? 'digital';
        $this->description = $this->gateway->description ?? '';
        $this->feeType = $this->gateway->fee_type ?? 'flat';
        $this->feeFlat = (float) $this->gateway->fee_flat;
        $this->feePercent = (float) $this->gateway->fee_percent;
        $this->isActive = (bool) $this->gateway->is_active;
        $this->instructions = $this->gateway->instructions ?? '';

        $config = $this->gateway->config ?? [];

        // QRIS
        $this->staticQris = $config['static_qris_string'] ?? IspSetting::get('static_qris_string', '');
        $this->useUniqueCode = isset($config['use_unique_code']) ? (bool) $config['use_unique_code'] : (IspSetting::get('use_unique_code', '1') === '1');

        // Bank
        $this->bankName = $config['bank_name'] ?? 'BCA';
        $this->accountNumber = $config['account_number'] ?? '';
        $this->accountHolder = $config['account_holder'] ?? '';

        // Digital APIs
        $this->mode = $config['mode'] ?? 'production';
        $this->apiUrl = $config['api_url'] ?? '';
        $this->serverKey = $config['server_key'] ?? '';
        $this->clientKey = $config['client_key'] ?? '';
        $this->merchantCode = $config['merchant_code'] ?? '';
        $this->webhookSecret = $config['webhook_secret'] ?? '';

        $this->webhookUrl = url('/api/payment/webhook');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit Gateway: '.($this->name ?: 'Payment Gateway');
    }

    public function getBreadcrumbs(): array
    {
        return [
            PaymentGatewaySettings::getUrl() => 'Payment Gateways',
            'Edit: '.($this->name ?: 'Gateway'),
        ];
    }

    public function scanUploadedQris(): void
    {
        if (! $this->qrisImage) {
            Notification::make()
                ->title('Pilih Foto QRIS')
                ->body('Silakan pilih berkas foto barcode QRIS terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        try {
            $path = $this->qrisImage->getRealPath();
            $service = app(QrisService::class);
            $result = $service->decodeFromImage($path);

            if (! empty($result['success']) && ! empty($result['payload'])) {
                $this->staticQris = $result['payload'];
                Notification::make()
                    ->title('Barcode QRIS Berhasil Dipindai!')
                    ->body("Merchant: {$result['merchant_name']} ({$result['merchant_city']})")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal Memindai QR Code')
                    ->body($result['message'] ?? 'Tidak ditemukan QR code yang valid.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error Scan QRIS')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'feeType' => 'required|in:flat,percent',
            'feeFlat' => 'numeric|min:0',
            'feePercent' => 'numeric|min:0|max:100',
        ]);

        $config = $this->gateway->config ?? [];

        if ($this->code === 'qris_manual') {
            $config['static_qris_string'] = trim($this->staticQris ?? '');
            $config['use_unique_code'] = $this->useUniqueCode;
            IspSetting::set('static_qris_string', $config['static_qris_string']);
            IspSetting::set('use_unique_code', $this->useUniqueCode ? '1' : '0');
        } elseif ($this->code === 'manual_transfer') {
            $config['bank_name'] = trim($this->bankName ?? '');
            $config['account_number'] = trim($this->accountNumber ?? '');
            $config['account_holder'] = trim($this->accountHolder ?? '');
        } else {
            $config['mode'] = $this->mode;
            $config['api_url'] = trim($this->apiUrl ?? '');
            $config['server_key'] = trim($this->serverKey ?? '');
            $config['client_key'] = trim($this->clientKey ?? '');
            $config['merchant_code'] = trim($this->merchantCode ?? '');
            $config['webhook_secret'] = trim($this->webhookSecret ?? '');

            // Synchronize with legacy settings for backward compatibility
            IspSetting::set('payment_gateway_api_url', $config['api_url']);
            IspSetting::set('payment_gateway_server_key', $config['server_key']);
            IspSetting::set('payment_gateway_client_key', $config['client_key']);
            IspSetting::set('payment_gateway_merchant_code', $config['merchant_code']);
            IspSetting::set('payment_webhook_secret', $config['webhook_secret']);
        }

        $this->gateway->update([
            'name' => $this->name,
            'description' => $this->description,
            'fee_type' => $this->feeType,
            'fee_flat' => $this->feeFlat,
            'fee_percent' => $this->feePercent,
            'is_active' => $this->isActive,
            'instructions' => $this->instructions,
            'config' => $config,
        ]);

        if ($this->isActive && $this->code !== 'manual_transfer') {
            IspSetting::set('payment_gateway_provider', $this->code);
        }

        Notification::make()
            ->title('Konfigurasi Berhasil Disimpan')
            ->body("Pengaturan gateway {$this->gateway->name} telah diperbarui.")
            ->success()
            ->send();
    }

    public function simulateWebhook(): void
    {
        $invoice = Invoice::where('status', 'unpaid')->latest()->first();

        if (! $invoice) {
            Notification::make()
                ->title('Tidak Ada Tagihan Unpaid')
                ->body('Tidak ditemukan invoice dengan status unpaid untuk disimulasikan.')
                ->warning()
                ->send();

            return;
        }

        $billingService = app(BillingService::class);
        $amount = (float) $invoice->total_amount ?: (float) $invoice->amount;
        $billingService->processPayment($invoice, $amount, $this->code, 'SIMULASI-WEBHOOK-'.now()->timestamp);

        Notification::make()
            ->title('Simulasi Webhook Berhasil!')
            ->body("Invoice {$invoice->invoice_number} senilai Rp ".number_format($amount, 0, ',', '.')." berhasil dilunasi via {$this->name}.")
            ->success()
            ->send();
    }
}
