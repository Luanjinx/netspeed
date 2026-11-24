<?php

namespace App\Services\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Services\Notification\TelegramService;
use App\Services\Notification\WhatsAppService;
use App\Services\Payment\QrisService;
use App\Services\Router\MikrotikApiService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        protected QrisService $qrisService,
        protected MikrotikApiService $mikrotikService,
        protected WhatsAppService $whatsAppService,
        protected ?TelegramService $telegramService = null
    ) {
        $this->telegramService = $telegramService ?: app(TelegramService::class);
    }

    /**
     * Generate an invoice for a customer for a given billing period.
     */
    public function generateInvoice(Customer $customer, ?string $period = null): Invoice
    {
        $period = $period ?: Carbon::now()->format('Y-m');
        $package = $customer->package;

        if (! $package) {
            throw new Exception("Pelanggan {$customer->name} belum memiliki paket internet terpilih.");
        }

        // Check if invoice already exists for this customer and period
        $existing = Invoice::where('customer_id', $customer->id)
            ->where('period', $period)
            ->first();

        if ($existing) {
            return $existing;
        }

        $regularPrice = (float) $package->price;
        $monthlyRate = $regularPrice;
        $notesParts = [];
        $isPromoApplied = false;

        // Check promo eligibility:
        if (
            $package->promo_price !== null &&
            $package->promo_price > 0 &&
            (int) $package->promo_cycles > 0 &&
            (int) $customer->promo_cycles_used < (int) $package->promo_cycles
        ) {
            $isPromoApplied = true;
            $monthlyRate = (float) $package->promo_price;
            $currentCycle = (int) $customer->promo_cycles_used + 1;
            $totalCycles = (int) $package->promo_cycles;
            $discount = $regularPrice - $monthlyRate;
            $notesParts[] = "Promo siklus {$currentCycle}/{$totalCycles} (Diskon Rp ".number_format(max(0, $discount), 0, ',', '.').')';
        }

        // Check prorata eligibility:
        $amount = $monthlyRate;
        if (
            $package->is_prorated &&
            $customer->install_date
        ) {
            $installCarbon = Carbon::parse($customer->install_date);
            if ($installCarbon->format('Y-m') === $period) {
                $daysInMonth = $installCarbon->daysInMonth;
                $remainingDays = max(1, $daysInMonth - $installCarbon->day + 1);
                $ratio = $remainingDays / $daysInMonth;
                $amount = round($monthlyRate * $ratio);
                $notesParts[] = "Prorata bulan pasang ({$remainingDays}/{$daysInMonth} hari, tgl pasang: {$installCarbon->format('d/m/Y')})";
            }
        }

        $notes = ! empty($notesParts) ? 'AUTO: '.implode(' | ', $notesParts) : null;

        $useUniqueCode = IspSetting::get('use_unique_code', '1') === '1';
        $uniqueCode = $useUniqueCode ? rand(101, 999) : 0;
        $totalAmount = $amount + $uniqueCode;

        // Due date: usually due_date_day of the current period
        $dueDateDay = $customer->due_date_day ?: 10;
        $periodParts = explode('-', $period);
        $dueDate = Carbon::createFromDate((int) $periodParts[0], (int) $periodParts[1], min($dueDateDay, 28));

        $invoiceNumber = 'INV-'.str_replace('-', '', $period).'-'.sprintf('%04d', $customer->id);

        // Generate Dynamic QRIS if static QRIS is configured
        $staticQris = IspSetting::get('static_qris_string');
        $qrisPayload = null;

        if (! empty($staticQris)) {
            try {
                $qrisPayload = $this->qrisService->convertStaticToDynamic($staticQris, $totalAmount, $invoiceNumber);
            } catch (Exception $e) {
                Log::warning("Could not generate Dynamic QRIS for invoice {$invoiceNumber}: ".$e->getMessage());
            }
        }

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'period' => $period,
            'amount' => $amount,
            'unique_code' => $uniqueCode,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => $dueDate,
            'payment_method' => 'qris_dynamic',
            'qris_payload' => $qrisPayload,
            'notes' => $notes,
        ]);

        if ($isPromoApplied) {
            $customer->increment('promo_cycles_used');
        }

        return $invoice;
    }

    /**
     * Generate retroactive prorata invoice for the customer's installation month if it doesn't exist yet.
     * Uses regular package price (not promo) to close missed installation month billing.
     */
    public function generateRetroactiveProrataInvoice(Customer $customer): Invoice
    {
        if (! $customer->install_date) {
            throw new Exception("Pelanggan {$customer->name} belum memiliki tanggal pasang (install_date).");
        }

        $package = $customer->package;
        if (! $package) {
            throw new Exception("Pelanggan {$customer->name} belum memiliki paket internet terpilih.");
        }

        $installCarbon = Carbon::parse($customer->install_date);
        $period = $installCarbon->format('Y-m');

        // Check if invoice already exists for install month
        $existing = Invoice::where('customer_id', $customer->id)
            ->where('period', $period)
            ->first();

        if ($existing) {
            throw new Exception("Tagihan bulan pasang ({$period}) untuk pelanggan {$customer->name} sudah ada ({$existing->invoice_number}).");
        }

        $daysInMonth = $installCarbon->daysInMonth;
        $remainingDays = max(1, $daysInMonth - $installCarbon->day + 1);
        $regularPrice = (float) $package->price;
        $amount = round(($remainingDays / $daysInMonth) * $regularPrice);

        $notes = "AUTO: Susulan tagihan prorata bulan pasang ({$remainingDays}/{$daysInMonth} hari, tgl pasang: {$installCarbon->format('d/m/Y')}) dari harga reguler Rp ".number_format($regularPrice, 0, ',', '.');

        $useUniqueCode = IspSetting::get('use_unique_code', '1') === '1';
        $uniqueCode = $useUniqueCode ? rand(101, 999) : 0;
        $totalAmount = $amount + $uniqueCode;

        $dueDateDay = $customer->due_date_day ?: 10;
        $dueDate = Carbon::createFromDate((int) $installCarbon->year, (int) $installCarbon->month, min($dueDateDay, 28));

        $invoiceNumber = 'INV-'.str_replace('-', '', $period).'-'.sprintf('%04d', $customer->id);

        $staticQris = IspSetting::get('static_qris_string');
        $qrisPayload = null;

        if (! empty($staticQris)) {
            try {
                $qrisPayload = $this->qrisService->convertStaticToDynamic($staticQris, $totalAmount, $invoiceNumber);
            } catch (Exception $e) {
                Log::warning("Could not generate Dynamic QRIS for invoice {$invoiceNumber}: ".$e->getMessage());
            }
        }

        return Invoice::create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'period' => $period,
            'amount' => $amount,
            'unique_code' => $uniqueCode,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => $dueDate,
            'payment_method' => 'qris_dynamic',
            'qris_payload' => $qrisPayload,
            'notes' => $notes,
        ]);
    }

    /**
     * Generate invoices in batch for all active customers.
     */
    public function generateMonthlyInvoices(?string $period = null): int
    {
        $period = $period ?: Carbon::now()->format('Y-m');
        $customers = Customer::whereIn('status', ['active', 'isolated'])
            ->whereNotNull('package_id')
            ->get();

        $count = 0;
        foreach ($customers as $customer) {
            try {
                $this->generateInvoice($customer, $period);
                $count++;
            } catch (Exception $e) {
                Log::error("Failed to generate invoice for customer {$customer->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Mark an invoice as paid and automatically activate the customer in MikroTik.
     */
    public function markInvoicePaid(Invoice $invoice, string $method = 'qris_dynamic', ?string $notes = null): bool
    {
        if ($invoice->status === 'paid') {
            return true;
        }

        return DB::transaction(function () use ($invoice, $method, $notes) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $method,
                'notes' => $notes ?: $invoice->notes,
            ]);

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_reference' => 'PAY-'.date('YmdHis').'-'.rand(100, 999),
                'amount_paid' => $invoice->total_amount,
                'payment_method' => $method,
                'is_verified' => true,
                'verified_at' => now(),
                'notes' => 'Pembayaran otomatis lunas',
            ]);

            $customer = $invoice->customer;
            if ($customer) {
                // If invoice is for package upgrade or different package, update package_id
                if ($invoice->package_id && (int) $invoice->package_id !== (int) $customer->package_id) {
                    $customer->package_id = $invoice->package_id;
                    $customer->promo_cycles_used = 0;
                    $customer->save();
                    $customer->refresh();
                }

                $customer->update(['status' => 'active']);

                // Auto-activate on MikroTik router
                if ($customer->router && $customer->pppoe_username && $customer->package) {
                    $this->mikrotikService->activatePppCustomer(
                        $customer->router,
                        $customer->pppoe_username,
                        $customer->package->mikrotik_profile
                    );
                }

                // Send WhatsApp Payment Receipt
                $this->whatsAppService->sendPaymentReceipt($payment);

                // Send Telegram NOC notification
                try {
                    $this->telegramService?->notifyPaymentReceived($payment);
                } catch (\Throwable $e) {
                    Log::warning('Telegram payment notification error: '.$e->getMessage());
                }
            }

            return true;
        });
    }

    /**
     * Auto isolate customers with overdue invoices.
     */
    public function processAutoIsolir(): int
    {
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', Carbon::today())
            ->with(['customer.router', 'customer.package'])
            ->get();

        $isolatedCount = 0;
        $globalIsolirProfile = IspSetting::get('default_isolir_profile', 'ISOLIR');

        foreach ($overdueInvoices as $inv) {
            $customer = $inv->customer;
            if (! $customer || ! $customer->auto_isolate || $customer->status === 'isolated') {
                continue;
            }

            $router = $customer->router;
            if ($router && $customer->pppoe_username) {
                $isolirProfile = $customer->package?->mikrotik_isolir_profile ?: $globalIsolirProfile;
                $success = $this->mikrotikService->isolatePppCustomer($router, $customer->pppoe_username, $isolirProfile);
                if ($success) {
                    $customer->update(['status' => 'isolated']);
                    $this->whatsAppService->sendIsolirAlert($customer);
                    $isolatedCount++;
                }
            } else {
                $customer->update(['status' => 'isolated']);
                $this->whatsAppService->sendIsolirAlert($customer);
                $isolatedCount++;
            }
        }

        return $isolatedCount;
    }
}
