<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\PaymentGateway;
use App\Services\Billing\BillingService;
use App\Services\Payment\QrisService;
use BackedEnum;
use Database\Seeders\PaymentGatewaySeeder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;
use UnitEnum;

class PaymentGatewaySettings extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'payment-gateway';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Billing & Keuangan';

    protected static ?string $navigationLabel = 'Payment Gateways';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Payment Gateways';

    protected ?string $subheading = 'Kelola berbagai saluran pembayaran online (Multi-Payment) yang aktif untuk pelanggan';

    protected string $view = 'filament.pages.payment-gateway-settings';

    // Filters
    public string $searchQuery = '';

    public string $statusFilter = 'all'; // 'all', 'active', 'inactive'

    public string $webhookUrl = '';

    // Global Automation Settings
    public bool $autoActivate = true;

    public bool $autoReceipt = true;

    public bool $autoTelegram = true;

    // Edit Modal State
    public bool $showEditModal = false;

    public ?int $editingGatewayId = null;

    public string $editCode = '';

    public string $editName = '';

    public string $editDescription = '';

    public string $editCategory = 'digital';

    public string $editFeeType = 'flat'; // 'flat' or 'percent'

    public float $editFeeFlat = 0;

    public float $editFeePercent = 0;

    public bool $editIsActive = false;

    public string $editInstructions = '';

    // Specific Config State
    public ?string $editStaticQris = '';

    public bool $editUseUniqueCode = true;

    public $editQrisImage = null;

    public ?string $editApiUrl = '';

    public ?string $editServerKey = '';

    public ?string $editClientKey = '';

    public ?string $editMerchantCode = '';

    public ?string $editWebhookSecret = '';

    public ?string $editBankName = '';

    public ?string $editAccountNumber = '';

    public ?string $editAccountHolder = '';

    public function mount(): void
    {
        $this->webhookUrl = url('/api/payment/webhook');

        $this->autoActivate = IspSetting::get('payment_auto_activate', '1') === '1';
        $this->autoReceipt = IspSetting::get('payment_auto_receipt', '1') === '1';
        $this->autoTelegram = IspSetting::get('payment_auto_telegram', '1') === '1';

        // Auto-seed if empty
        if (PaymentGateway::count() === 0) {
            $seeder = new PaymentGatewaySeeder;
            $seeder->run();
        }
    }

    public function getGatewaysProperty(): Collection
    {
        $query = PaymentGateway::query()->orderBy('sort_order', 'asc');

        if (! empty($this->searchQuery)) {
            $q = strtolower($this->searchQuery);
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$q}%"]);
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->get();
    }

    public function resetFilters(): void
    {
        $this->searchQuery = '';
        $this->statusFilter = 'all';
    }

    public function toggleGateway(int $id): void
    {
        $gw = PaymentGateway::find($id);
        if ($gw) {
            $gw->is_active = ! $gw->is_active;
            $gw->save();

            // Sync with IspSetting if it's the primary provider
            if ($gw->is_active && $gw->code !== 'manual_transfer') {
                IspSetting::set('payment_gateway_provider', $gw->code);
            }

            $statusText = $gw->is_active ? 'Diaktifkan' : 'Dinonaktifkan';
            Notification::make()
                ->title("{$gw->name} {$statusText}")
                ->success()
                ->send();
        }
    }

    public function editGateway(int $id): void
    {
        $gw = PaymentGateway::find($id);
        if (! $gw) {
            return;
        }

        $this->editingGatewayId = $gw->id;
        $this->editCode = $gw->code;
        $this->editName = $gw->name;
        $this->editDescription = $gw->description ?? '';
        $this->editCategory = $gw->category ?? 'digital';
        $this->editFeeType = $gw->fee_type ?? 'flat';
        $this->editFeeFlat = (float) $gw->fee_flat;
        $this->editFeePercent = (float) $gw->fee_percent;
        $this->editIsActive = (bool) $gw->is_active;
        $this->editInstructions = $gw->instructions ?? '';

        $config = $gw->config ?? [];
        $this->editStaticQris = $config['static_qris_string'] ?? IspSetting::get('static_qris_string', '');
        $this->editUseUniqueCode = isset($config['use_unique_code']) ? (bool) $config['use_unique_code'] : (IspSetting::get('use_unique_code', '1') === '1');
        $this->editApiUrl = $config['api_url'] ?? '';
        $this->editServerKey = $config['server_key'] ?? '';
        $this->editClientKey = $config['client_key'] ?? '';
        $this->editMerchantCode = $config['merchant_code'] ?? '';
        $this->editWebhookSecret = $config['webhook_secret'] ?? '';
        $this->editBankName = $config['bank_name'] ?? 'BCA';
        $this->editAccountNumber = $config['account_number'] ?? '';
        $this->editAccountHolder = $config['account_holder'] ?? '';

        $this->editQrisImage = null;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingGatewayId = null;
    }

    public function scanUploadedQris(): void
    {
        if (! $this->editQrisImage) {
            return;
        }

        try {
            $path = $this->editQrisImage->getRealPath();
            $service = app(QrisService::class);
            $result = $service->decodeFromImage($path);

            if (! empty($result['success']) && ! empty($result['payload'])) {
                $this->editStaticQris = $result['payload'];
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

    public function saveGateway(): void
    {
        if (! $this->editingGatewayId) {
            return;
        }

        $gw = PaymentGateway::find($this->editingGatewayId);
        if (! $gw) {
            return;
        }

        $config = $gw->config ?? [];

        if ($gw->code === 'qris_manual') {
            $config['static_qris_string'] = trim($this->editStaticQris ?? '');
            $config['use_unique_code'] = $this->editUseUniqueCode;
            IspSetting::set('static_qris_string', $config['static_qris_string']);
            IspSetting::set('use_unique_code', $this->editUseUniqueCode ? '1' : '0');
        } elseif ($gw->code === 'manual_transfer') {
            $config['bank_name'] = trim($this->editBankName ?? '');
            $config['account_number'] = trim($this->editAccountNumber ?? '');
            $config['account_holder'] = trim($this->editAccountHolder ?? '');
        } else {
            $config['api_url'] = trim($this->editApiUrl ?? '');
            $config['server_key'] = trim($this->editServerKey ?? '');
            $config['client_key'] = trim($this->editClientKey ?? '');
            $config['merchant_code'] = trim($this->editMerchantCode ?? '');
            $config['webhook_secret'] = trim($this->editWebhookSecret ?? '');

            // Also sync to legacy IspSetting for compatibility
            IspSetting::set('payment_gateway_api_url', $config['api_url']);
            IspSetting::set('payment_gateway_server_key', $config['server_key']);
            IspSetting::set('payment_gateway_client_key', $config['client_key']);
            IspSetting::set('payment_gateway_merchant_code', $config['merchant_code']);
            IspSetting::set('payment_webhook_secret', $config['webhook_secret']);
        }

        $gw->update([
            'name' => $this->editName,
            'description' => $this->editDescription,
            'fee_type' => $this->editFeeType,
            'fee_flat' => $this->editFeeFlat,
            'fee_percent' => $this->editFeePercent,
            'is_active' => $this->editIsActive,
            'instructions' => $this->editInstructions,
            'config' => $config,
        ]);

        $this->showEditModal = false;

        Notification::make()
            ->title('Konfigurasi Gateway Disimpan')
            ->body("Pengaturan untuk {$gw->name} berhasil diperbarui.")
            ->success()
            ->send();
    }

    public function saveGlobalAutomation(): void
    {
        IspSetting::set('payment_auto_activate', $this->autoActivate ? '1' : '0');
        IspSetting::set('payment_auto_receipt', $this->autoReceipt ? '1' : '0');
        IspSetting::set('payment_auto_telegram', $this->autoTelegram ? '1' : '0');

        Notification::make()
            ->title('Otomasi Pembayaran Disimpan')
            ->body('Pengaturan auto-aktivasi dan notifikasi berhasil disimpan.')
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
        $billingService->processPayment($invoice, $amount, 'payment_gateway', 'SIMULASI-WEBHOOK-'.now()->timestamp);

        Notification::make()
            ->title('Simulasi Webhook Berhasil!')
            ->body("Invoice {$invoice->invoice_number} senilai Rp ".number_format($amount, 0, ',', '.').' berhasil ditandai Lunas.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWebhook')
                ->label('Simulasi Webhook Lunas')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->action(fn () => $this->simulateWebhook()),
        ];
    }
}
