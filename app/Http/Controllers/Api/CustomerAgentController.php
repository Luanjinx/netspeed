<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\AgentWithdrawal;
use App\Models\Invoice;
use App\Services\Agent\AgentService;
use App\Services\Notification\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerAgentController extends Controller
{
    public function __construct(
        protected AgentService $agentService
    ) {}

    /**
     * Get the authenticated customer's agent status, balance summary, and recent transactions.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (! $agent) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_agent' => false,
                    'agent' => null,
                    'stats' => [
                        'monthly_commission' => 0.0,
                        'monthly_paid_count' => 0,
                        'total_paid_count' => 0,
                    ],
                    'recent_transactions' => [],
                ],
            ]);
        }

        $startOfMonth = now()->startOfMonth();

        $monthlyCommission = (float) AgentTransaction::where('agent_id', $agent->id)
            ->where('type', 'pppoe_payment')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('fee');

        $monthlyPaidCount = AgentTransaction::where('agent_id', $agent->id)
            ->where('type', 'pppoe_payment')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $totalPaidCount = AgentTransaction::where('agent_id', $agent->id)
            ->where('type', 'pppoe_payment')
            ->count();

        $recentTransactions = AgentTransaction::where('agent_id', $agent->id)
            ->with(['invoice.customer'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (AgentTransaction $tx): array => $this->formatTransaction($tx))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'has_agent' => true,
                'agent' => $this->formatAgent($agent),
                'stats' => [
                    'monthly_commission' => $monthlyCommission,
                    'monthly_paid_count' => $monthlyPaidCount,
                    'total_paid_count' => $totalPaidCount,
                ],
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    /**
     * Submit or re-submit a customer application to become a payment point agent.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $existing = $user->agent;

        if ($existing) {
            if ($existing->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sudah terdaftar sebagai Mitra Agen aktif.',
                ], 422);
            }

            if ($existing->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan kemitraan agen Anda sedang menunggu persetujuan Admin.',
                ], 422);
            }

            $existing->update([
                'name' => trim($validated['name']),
                'phone' => trim($validated['phone']),
                'email' => $user->email,
                'address' => trim($validated['address']),
                'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
                'is_active' => false,
                'approval_status' => Agent::STATUS_PENDING,
                'rejection_reason' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $agent = $existing->fresh();
        } else {
            $agent = Agent::create([
                'user_id' => $user->id,
                'agent_code' => Agent::generateNextCode(),
                'name' => trim($validated['name']),
                'phone' => trim($validated['phone']),
                'email' => $user->email,
                'address' => trim($validated['address']),
                'balance' => 0,
                'commission_per_transaction' => 2000,
                'is_active' => false,
                'approval_status' => Agent::STATUS_PENDING,
                'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            ]);
        }

        try {
            $customerNumber = $user->customer?->customer_number ?? '-';
            $message = "🏪 <b>PENDAFTARAN MITRA AGEN BARU</b>\n\n"
                ."Kode: <b>{$agent->agent_code}</b>\n"
                ."Nama Loket: <b>{$agent->name}</b>\n"
                ."Pelanggan: {$user->name} ({$customerNumber})\n"
                ."WhatsApp: {$agent->phone}\n"
                ."Alamat: {$agent->address}\n\n"
                .'Silakan tinjau & setujui melalui menu <b>Billing → Mitra Agen</b> di Admin Panel.';

            app(TelegramService::class)->sendToAdmin($message);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim notifikasi Telegram pendaftaran agen: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran Mitra Agen berhasil dikirim. Menunggu persetujuan Admin.',
            'data' => [
                'has_agent' => true,
                'agent' => $this->formatAgent($agent),
            ],
        ], 201);
    }

    /**
     * Search unpaid PPPoE invoices from the native Flutter agent cashier.
     */
    public function searchInvoices(Request $request): JsonResponse
    {
        $agent = $this->resolveOperationalAgent($request);
        if (! $agent) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Mitra Agen Anda belum aktif atau belum disetujui Admin.',
            ], 403);
        }

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $invoices = $this->agentService->searchUnpaidInvoices($query, 15);

        $data = $invoices->map(function (Invoice $inv): array {
            $cust = $inv->customer;

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'period' => $inv->period,
                'amount' => (float) $inv->amount,
                'total_amount' => (float) $inv->total_amount,
                'formatted_total' => 'Rp '.number_format((float) $inv->total_amount, 0, ',', '.'),
                'due_date' => $inv->due_date ? $inv->due_date->format('d/m/Y') : '-',
                'customer_name' => $cust?->name ?? 'Pelanggan',
                'customer_number' => $cust?->customer_number ?? '-',
                'pppoe_username' => $cust?->pppoe_username ?? '-',
                'phone' => $cust?->phone ?? '-',
                'package_name' => $cust?->package?->name ?? ($inv->package?->name ?? 'Paket Internet'),
                'is_isolated' => $cust?->status === 'isolated',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Process PPPoE invoice payment via the authenticated customer's agent balance.
     */
    public function payInvoice(Request $request): JsonResponse
    {
        $agent = $this->resolveOperationalAgent($request);
        if (! $agent) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Mitra Agen Anda belum aktif atau belum disetujui Admin.',
            ], 403);
        }

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $invoice = Invoice::with(['customer.package', 'customer.router', 'package'])
            ->find($validated['invoice_id']);

        if (! $invoice || $invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan atau sudah lunas.',
            ], 422);
        }

        $fee = (float) $agent->commission_per_transaction;
        $deduction = max(0, (float) $invoice->total_amount - $fee);

        if ((float) $agent->balance < $deduction) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo deposit agen tidak mencukupi (Sisa: '.$agent->formatted_balance.', dibutuhkan: Rp '.number_format($deduction, 0, ',', '.').'). Silakan lakukan top-up ke Admin.',
            ], 422);
        }

        try {
            $tx = $this->agentService->payInvoice($agent, $invoice, $request->user());
            $cust = $invoice->customer;
            $freshAgent = $agent->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran tagihan '.$invoice->invoice_number.' berhasil diproses.',
                'data' => [
                    'receipt' => [
                        'reference' => $tx->payment_reference,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $cust?->name ?? 'Pelanggan',
                        'customer_number' => $cust?->customer_number ?? '-',
                        'pppoe_username' => $cust?->pppoe_username ?? '-',
                        'amount_paid' => (float) $invoice->total_amount,
                        'formatted_amount' => 'Rp '.number_format((float) $invoice->total_amount, 0, ',', '.'),
                        'commission' => $fee,
                        'formatted_commission' => 'Rp '.number_format($fee, 0, ',', '.'),
                        'deducted_amount' => $deduction,
                        'balance_after' => (float) $tx->balance_after,
                        'formatted_balance' => 'Rp '.number_format((float) $tx->balance_after, 0, ',', '.'),
                        'time' => $tx->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
                    ],
                    'agent' => $freshAgent ? $this->formatAgent($freshAgent) : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List agent balance mutations and payment transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $agent = $request->user()->agent;
        if (! $agent) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $query = AgentTransaction::where('agent_id', $agent->id)
            ->with(['invoice.customer'])
            ->latest();

        $type = trim((string) $request->query('type', ''));
        if ($type !== '' && in_array($type, ['deposit', 'pppoe_payment', 'commission', 'refund', 'withdrawal'], true)) {
            $query->where('type', $type);
        }

        $transactions = $query->take(50)
            ->get()
            ->map(fn (AgentTransaction $tx): array => $this->formatTransaction($tx))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * List withdrawal requests submitted by the authenticated customer's agent.
     */
    public function withdrawalsIndex(Request $request): JsonResponse
    {
        $agent = $request->user()?->agent;
        if (! $agent) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $withdrawals = AgentWithdrawal::where('agent_id', $agent->id)
            ->latest()
            ->take(30)
            ->get()
            ->map(fn (AgentWithdrawal $w): array => $this->formatWithdrawal($w))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Submit a balance withdrawal request (minimum Rp 10.000, balance held until Admin processes).
     */
    public function withdrawalsStore(Request $request): JsonResponse
    {
        $agent = $this->resolveOperationalAgent($request);
        if (! $agent) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Mitra Agen Anda belum aktif atau belum disetujui Admin.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:'.AgentWithdrawal::MIN_AMOUNT,
            'method' => 'required|string|in:bank_transfer,ewallet,cash',
            'bank_or_provider' => 'required_unless:method,cash|nullable|string|max:100',
            'account_number' => 'required_unless:method,cash|nullable|string|max:100',
            'account_name' => 'required_unless:method,cash|nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.min' => 'Nominal penarikan minimal Rp '.number_format(AgentWithdrawal::MIN_AMOUNT, 0, ',', '.').'.',
            'bank_or_provider.required_unless' => 'Nama Bank / E-Wallet wajib diisi.',
            'account_number.required_unless' => 'Nomor Rekening / No. HP wajib diisi.',
            'account_name.required_unless' => 'Nama pemilik rekening / e-wallet wajib diisi.',
        ]);

        try {
            $withdrawal = $this->agentService->requestWithdrawal($agent, $validated, $request->user());
            $freshAgent = $agent->fresh();

            try {
                $targetInfo = $withdrawal->method === AgentWithdrawal::METHOD_CASH
                    ? 'Tunai di Kantor'
                    : trim(($withdrawal->bank_or_provider ?? '').' • '.($withdrawal->account_number ?? '').' ('.($withdrawal->account_name ?? '-').')');

                $message = "💸 <b>PENGAJUAN TARIK SALDO AGEN</b>\n\n"
                    ."Kode: <b>{$withdrawal->withdrawal_code}</b>\n"
                    ."Agen: <b>{$agent->name}</b> ({$agent->agent_code})\n"
                    ."Nominal: <b>{$withdrawal->formatted_amount}</b>\n"
                    ."Tujuan: {$targetInfo}\n\n"
                    .'Silakan tinjau & cairkan melalui menu <b>Billing → Tarik Saldo Agen</b> atau Aplikasi Admin.';

                app(TelegramService::class)->sendToAdmin($message);
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim notifikasi Telegram tarik saldo agen: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Pengajuan tarik saldo {$withdrawal->withdrawal_code} berhasil dikirim. Menunggu pencairan Admin.",
                'data' => [
                    'withdrawal' => $this->formatWithdrawal($withdrawal),
                    'agent' => $freshAgent ? $this->formatAgent($freshAgent) : null,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatWithdrawal(AgentWithdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->id,
            'withdrawal_code' => $withdrawal->withdrawal_code,
            'amount' => (float) $withdrawal->amount,
            'formatted_amount' => $withdrawal->formatted_amount,
            'method' => $withdrawal->method,
            'method_label' => $withdrawal->method_label,
            'bank_or_provider' => $withdrawal->bank_or_provider,
            'account_number' => $withdrawal->account_number,
            'account_name' => $withdrawal->account_name,
            'notes' => $withdrawal->notes,
            'status' => $withdrawal->status,
            'rejection_reason' => $withdrawal->rejection_reason,
            'transfer_reference' => $withdrawal->transfer_reference,
            'processed_at' => $withdrawal->processed_at?->format('d/m/Y H:i'),
            'created_at' => $withdrawal->created_at?->format('d/m/Y H:i') ?? '',
        ];
    }

    protected function resolveOperationalAgent(Request $request): ?Agent
    {
        $agent = $request->user()?->agent;
        if (! $agent || ! $agent->canOperate()) {
            return null;
        }

        return $agent;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'agent_code' => $agent->agent_code,
            'name' => $agent->name,
            'phone' => $agent->phone,
            'email' => $agent->email,
            'address' => $agent->address,
            'balance' => (float) $agent->balance,
            'formatted_balance' => $agent->formatted_balance,
            'commission_per_transaction' => (float) $agent->commission_per_transaction,
            'is_active' => (bool) $agent->is_active,
            'approval_status' => $agent->approval_status ?? Agent::STATUS_APPROVED,
            'rejection_reason' => $agent->rejection_reason,
            'notes' => $agent->notes,
            'approved_at' => $agent->approved_at?->toIso8601String(),
            'created_at' => $agent->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatTransaction(AgentTransaction $tx): array
    {
        return [
            'id' => $tx->id,
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'fee' => (float) $tx->fee,
            'balance_before' => (float) $tx->balance_before,
            'balance_after' => (float) $tx->balance_after,
            'description' => $tx->description,
            'payment_reference' => $tx->payment_reference,
            'invoice_number' => $tx->invoice?->invoice_number,
            'customer_name' => $tx->invoice?->customer?->name,
            'created_at' => $tx->created_at?->format('d/m/Y H:i') ?? '',
        ];
    }
}
