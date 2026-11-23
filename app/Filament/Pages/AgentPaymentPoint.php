<?php

namespace App\Filament\Pages;

use App\Models\Agent;
use App\Models\Invoice;
use App\Services\Agent\AgentService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class AgentPaymentPoint extends Page
{
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'agent']);
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->role === 'agent' ? null : 'Billing & Keuangan';
    }

    protected static ?string $slug = 'agent-payment-point';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Billing & Keuangan';

    protected static ?string $navigationLabel = 'Loket Pembayaran Agen';

    protected static ?string $title = 'Loket Pembayaran Tagihan PPPoE (Agen & Kasir)';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.agent-payment-point';

    public ?int $selectedAgentId = null;

    public string $searchQuery = '';

    public array $searchResults = [];

    public ?int $selectedInvoiceId = null;

    public ?array $selectedInvoice = null;

    public ?array $lastSuccessTransaction = null;

    public bool $isProcessing = false;

    public function mount(): void
    {
        $user = auth()->user();

        // If logged-in user is an agent, lock to their agent account
        if ($user && $user->role === 'agent' && $user->agent) {
            $this->selectedAgentId = $user->agent->id;
        } else {
            // Default to first active agent or null
            $firstAgent = Agent::where('is_active', true)->first();
            if ($firstAgent) {
                $this->selectedAgentId = $firstAgent->id;
            }
        }
    }

    public function updatedSearchQuery(): void
    {
        $this->executeSearch();
    }

    public function executeSearch(): void
    {
        $query = trim($this->searchQuery);
        if (strlen($query) < 2) {
            $this->searchResults = [];

            return;
        }

        $service = app(AgentService::class);
        $invoices = $service->searchUnpaidInvoices($query, 8);

        $this->searchResults = $invoices->map(function (Invoice $inv) {
            $cust = $inv->customer;

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'period' => $inv->period,
                'amount' => (float) $inv->amount,
                'total_amount' => (float) $inv->total_amount,
                'due_date' => $inv->due_date ? $inv->due_date->format('d/m/Y') : '-',
                'customer_name' => $cust?->name ?? 'Pelanggan',
                'customer_number' => $cust?->customer_number ?? '-',
                'pppoe_username' => $cust?->pppoe_username ?? '-',
                'phone' => $cust?->phone ?? '-',
                'package_name' => $cust?->package?->name ?? ($inv->package?->name ?? 'Paket Internet'),
                'is_isolated' => $cust?->status === 'isolated',
            ];
        })->toArray();
    }

    public function selectInvoice(int $invoiceId): void
    {
        $this->selectedInvoiceId = $invoiceId;
        $found = collect($this->searchResults)->firstWhere('id', $invoiceId);

        if ($found) {
            $this->selectedInvoice = $found;
        } else {
            $inv = Invoice::with(['customer.package', 'package'])->find($invoiceId);
            if ($inv && $inv->status === 'unpaid') {
                $cust = $inv->customer;
                $this->selectedInvoice = [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'period' => $inv->period,
                    'amount' => (float) $inv->amount,
                    'total_amount' => (float) $inv->total_amount,
                    'due_date' => $inv->due_date ? $inv->due_date->format('d/m/Y') : '-',
                    'customer_name' => $cust?->name ?? 'Pelanggan',
                    'customer_number' => $cust?->customer_number ?? '-',
                    'pppoe_username' => $cust?->pppoe_username ?? '-',
                    'phone' => $cust?->phone ?? '-',
                    'package_name' => $cust?->package?->name ?? ($inv->package?->name ?? 'Paket Internet'),
                    'is_isolated' => $cust?->status === 'isolated',
                ];
            }
        }
    }

    public function cancelSelectedInvoice(): void
    {
        $this->selectedInvoiceId = null;
        $this->selectedInvoice = null;
    }

    public function processPayment(): void
    {
        if (! $this->selectedAgentId) {
            Notification::make()->title('Pilih Agen Terlebih Dahulu')->danger()->send();

            return;
        }

        if (! $this->selectedInvoiceId) {
            Notification::make()->title('Pilih Tagihan yang Akan Dibayar')->warning()->send();

            return;
        }

        $agent = Agent::find($this->selectedAgentId);
        if (! $agent) {
            Notification::make()->title('Data Agen Tidak Ditemukan')->danger()->send();

            return;
        }

        $invoice = Invoice::with(['customer.package', 'customer.router', 'package'])->find($this->selectedInvoiceId);
        if (! $invoice || $invoice->status === 'paid') {
            Notification::make()->title('Tagihan Tidak Ditemukan atau Sudah Lunas')->danger()->send();
            $this->cancelSelectedInvoice();
            $this->executeSearch();

            return;
        }

        $this->isProcessing = true;

        try {
            $agentService = app(AgentService::class);
            $tx = $agentService->payInvoice($agent, $invoice, auth()->user());

            $cust = $invoice->customer;

            $this->lastSuccessTransaction = [
                'reference' => $tx->payment_reference,
                'time' => now()->format('d/m/Y H:i:s'),
                'agent_name' => $agent->name,
                'agent_code' => $agent->agent_code,
                'invoice_number' => $invoice->invoice_number,
                'period' => $invoice->period,
                'customer_name' => $cust?->name ?? 'Pelanggan',
                'customer_number' => $cust?->customer_number ?? '-',
                'pppoe_username' => $cust?->pppoe_username ?? '-',
                'package_name' => $cust?->package?->name ?? 'Paket Internet',
                'amount_paid' => (float) $invoice->total_amount,
                'fee' => (float) $tx->fee,
                'net_deducted' => (float) ($invoice->total_amount - $tx->fee),
                'balance_after' => (float) $tx->balance_after,
                'was_isolated' => $this->selectedInvoice['is_isolated'] ?? false,
            ];

            Notification::make()
                ->title('Pembayaran Berhasil & Lunas!')
                ->body("Tagihan {$invoice->invoice_number} ({$cust?->name}) berhasil dibayar. Internet aktif otomatis di MikroTik.")
                ->success()
                ->send();

            $this->cancelSelectedInvoice();
            $this->searchQuery = '';
            $this->searchResults = [];
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memproses Pembayaran')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function closeReceiptModal(): void
    {
        $this->lastSuccessTransaction = null;
    }

    public function getActiveAgentProperty(): ?Agent
    {
        return $this->selectedAgentId ? Agent::find($this->selectedAgentId) : null;
    }

    public function getRecentTransactionsProperty()
    {
        if (! $this->selectedAgentId) {
            return collect();
        }

        return $this->activeAgent?->transactions()->take(5)->get() ?? collect();
    }
}
