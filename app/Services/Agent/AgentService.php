<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\BillingService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AgentService
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Top-up deposit balance for an agent.
     */
    public function topUpBalance(Agent $agent, float $amount, ?string $notes = null, ?User $creator = null): AgentTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Nominal top-up harus lebih dari 0.');
        }

        return DB::transaction(function () use ($agent, $amount, $notes, $creator) {
            $balanceBefore = (float) $agent->balance;
            $agent->increment('balance', $amount);
            $balanceAfter = (float) $agent->fresh()->balance;

            $reference = 'AGT-TOP-'.date('YmdHis').'-'.rand(100, 999);

            return AgentTransaction::create([
                'agent_id' => $agent->id,
                'invoice_id' => null,
                'type' => 'deposit',
                'amount' => $amount,
                'fee' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $notes ?: ('Top-up saldo deposit via Admin (Ref: '.$reference.')'),
                'payment_reference' => $reference,
                'created_by' => $creator?->id,
            ]);
        });
    }

    /**
     * Smart Universal Search for Unpaid Invoices.
     * Supports: Invoice Number, PPPoE Username, Customer Number, Phone, Name, ONT SN, IP Address.
     */
    public function searchUnpaidInvoices(string $query, int $limit = 10): Collection
    {
        $term = trim($query);
        if (empty($term)) {
            return new Collection;
        }

        // Clean barcode/QR string if scanned with leading/trailing symbols
        $cleanTerm = preg_replace('/[^a-zA-Z0-9_\-\.\@]/', '', $term);

        return Invoice::query()
            ->where('status', 'unpaid')
            ->where(function ($q) use ($term, $cleanTerm) {
                // 1. Direct Invoice Number Match
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhere('invoice_number', 'like', "%{$cleanTerm}%")
                    // 2. Customer Relations: PPPoE Username, Customer Number, Name, Phone, ONT SN
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('pppoe_username', 'like', "%{$term}%")
                            ->orWhere('customer_number', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhere('ont_serial_number', 'like', "%{$term}%")
                            ->orWhere('ip_address', 'like', "%{$term}%");
                    });
            })
            ->with(['customer.package', 'customer.router', 'package'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Process PPPoE invoice payment by an Agent.
     */
    public function payInvoice(Agent $agent, Invoice $invoice, ?User $creator = null): AgentTransaction
    {
        if ($invoice->status === 'paid') {
            throw new Exception("Tagihan {$invoice->invoice_number} sudah berstatus LUNAS.");
        }

        if (! $agent->canOperate()) {
            throw new Exception("Akun Agen {$agent->name} belum disetujui atau sedang nonaktif. Silakan hubungi admin.");
        }

        $fee = (float) $agent->commission_per_transaction;
        $invoiceTotal = (float) $invoice->total_amount;
        $deductAmount = max(0, $invoiceTotal - $fee);

        if ((float) $agent->balance < $deductAmount) {
            throw new Exception(
                "Saldo deposit Agen tidak mencukupi!\n".
                "Sisa Saldo: {$agent->formatted_balance}\n".
                'Dibutuhkan: Rp '.number_format($deductAmount, 0, ',', '.')."\n".
                '(Tagihan: Rp '.number_format($invoiceTotal, 0, ',', '.').' - Komisi: Rp '.number_format($fee, 0, ',', '.').')'
            );
        }

        return DB::transaction(function () use ($agent, $invoice, $fee, $invoiceTotal, $deductAmount, $creator) {
            $balanceBefore = (float) $agent->balance;
            $agent->decrement('balance', $deductAmount);
            $balanceAfter = (float) $agent->fresh()->balance;

            $reference = 'AGT-PAY-'.date('YmdHis').'-'.rand(100, 999);

            $custName = $invoice->customer?->name ?? 'Pelanggan';
            $pppoeUser = $invoice->customer?->pppoe_username ?? '-';

            // 1. Record agent transaction
            $transaction = AgentTransaction::create([
                'agent_id' => $agent->id,
                'invoice_id' => $invoice->id,
                'type' => 'pppoe_payment',
                'amount' => $invoiceTotal,
                'fee' => $fee,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Bayar Tagihan {$invoice->invoice_number} [{$pppoeUser} - {$custName}] (Fee: Rp ".number_format($fee, 0, ',', '.').')',
                'payment_reference' => $reference,
                'created_by' => $creator?->id,
            ]);

            // 2. Mark invoice as paid in BillingService (handles payment record, auto-activation in MikroTik, and WhatsApp receipt)
            $this->billingService->markInvoicePaid(
                $invoice,
                'agent',
                "Lunas via Agen {$agent->name} ({$agent->agent_code}) - Ref: {$reference}"
            );

            return $transaction;
        });
    }
}
