<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\PaymentGateway;
use App\Services\Billing\BillingService;
use BackedEnum;
use Database\Seeders\PaymentGatewaySeeder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class PaymentGatewaySettings extends Page
{
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
