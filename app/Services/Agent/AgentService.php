<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\AgentWithdrawal;
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

    /**
     * Request a balance withdrawal by an operational agent, holding the requested balance immediately.
     *
     * @param  array<string, mixed>  $data
     */
    public function requestWithdrawal(Agent $agent, array $data, ?User $creator = null): AgentWithdrawal
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount < AgentWithdrawal::MIN_AMOUNT) {
            throw new Exception('Nominal penarikan minimal Rp '.number_format(AgentWithdrawal::MIN_AMOUNT, 0, ',', '.').'.');
        }

        return DB::transaction(function () use ($agent, $amount, $data, $creator): AgentWithdrawal {
            $lockedAgent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();

            if (! $lockedAgent->canOperate()) {
                throw new Exception('Akun Mitra Agen belum aktif atau belum disetujui Admin.');
            }

            if ((float) $lockedAgent->balance < $amount) {
                throw new Exception('Saldo deposit agen tidak mencukupi (Saldo: '.$lockedAgent->formatted_balance.').');
            }

            $balanceBefore = (float) $lockedAgent->balance;
            $lockedAgent->decrement('balance', $amount);
            $balanceAfter = (float) $lockedAgent->fresh()->balance;

            $code = AgentWithdrawal::generateNextCode();

            $withdrawal = AgentWithdrawal::create([
                'agent_id' => $lockedAgent->id,
                'withdrawal_code' => $code,
                'amount' => $amount,
                'method' => (string) ($data['method'] ?? AgentWithdrawal::METHOD_BANK),
                'bank_or_provider' => $data['bank_or_provider'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => AgentWithdrawal::STATUS_PENDING,
            ]);

            $targetInfo = trim(implode(' ', array_filter([
                $withdrawal->method_label,
                $withdrawal->bank_or_provider,
                $withdrawal->account_number,
            ])));

            AgentTransaction::create([
                'agent_id' => $lockedAgent->id,
                'invoice_id' => null,
                'type' => 'withdrawal',
                'amount' => $amount,
                'fee' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Tarik Saldo #{$code} ({$targetInfo})",
                'payment_reference' => $code,
                'created_by' => $creator?->id,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Approve a pending agent withdrawal and record transfer reference.
     */
    public function approveWithdrawal(AgentWithdrawal $withdrawal, User $admin, ?string $transferReference = null): AgentWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $transferReference): AgentWithdrawal {
            $locked = AgentWithdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new Exception('Pengajuan penarikan saldo ini sudah diproses sebelumnya.');
            }

            $locked->update([
                'status' => AgentWithdrawal::STATUS_APPROVED,
                'transfer_reference' => $transferReference ? trim($transferReference) : null,
                'rejection_reason' => null,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $locked->fresh(['agent', 'processor']);
        });
    }

    /**
     * Reject a pending agent withdrawal and automatically refund held balance.
     */
    public function rejectWithdrawal(AgentWithdrawal $withdrawal, User $admin, string $reason): AgentWithdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason): AgentWithdrawal {
            $locked = AgentWithdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new Exception('Pengajuan penarikan saldo ini sudah diproses sebelumnya.');
            }

            $lockedAgent = Agent::whereKey($locked->agent_id)->lockForUpdate()->firstOrFail();
            $amount = (float) $locked->amount;

            $locked->update([
                'status' => AgentWithdrawal::STATUS_REJECTED,
                'rejection_reason' => trim($reason),
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $balanceBefore = (float) $lockedAgent->balance;
            $lockedAgent->increment('balance', $amount);
            $balanceAfter = (float) $lockedAgent->fresh()->balance;

            AgentTransaction::create([
                'agent_id' => $lockedAgent->id,
                'invoice_id' => null,
                'type' => 'refund',
                'amount' => $amount,
                'fee' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Pengembalian Tarik Saldo #{$locked->withdrawal_code} (Ditolak: ".trim($reason).')',
                'payment_reference' => $locked->withdrawal_code,
                'created_by' => $admin->id,
            ]);

            return $locked->fresh(['agent', 'processor']);
        });
    }
}
