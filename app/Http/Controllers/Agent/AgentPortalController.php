<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\Agent\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AgentPortalController extends Controller
{
    /**
     * Get active agent record for currently authenticated user.
     */
    protected function getActiveAgent(): ?Agent
    {
        $user = Auth::user();
        if ($user && $user->role === 'agent') {
            return $user->agent;
        }

        // For admin inspecting the portal, return first active agent
        if ($user && $user->role === 'admin') {
            return Agent::where('is_active', true)->first();
        }

        return null;
    }

    /**
     * Agent Portal Dashboard Overview.
     */
    public function dashboard(): View
    {
        $agent = $this->getActiveAgent();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        $monthlyCommission = 0;
        $monthlyPaidCount = 0;
        $recentTransactions = collect();

        if ($agent) {
            $startOfMonth = now()->startOfMonth();

            $monthlyCommission = (float) AgentTransaction::where('agent_id', $agent->id)
                ->where('type', 'pppoe_payment')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('fee');

            $monthlyPaidCount = AgentTransaction::where('agent_id', $agent->id)
                ->where('type', 'pppoe_payment')
                ->where('created_at', '>=', $startOfMonth)
                ->count();

            $recentTransactions = AgentTransaction::where('agent_id', $agent->id)
                ->with(['invoice.customer'])
                ->latest()
                ->take(8)
                ->get();
        }

        return view('agent.dashboard', compact(
            'agent',
            'companyName',
            'monthlyCommission',
            'monthlyPaidCount',
            'recentTransactions'
        ));
    }

    /**
     * Loket Pembayaran Tagihan PPPoE Interface.
     */
    public function paymentPoint(): View
    {
        $agent = $this->getActiveAgent();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');

        return view('agent.payment-point', compact('agent', 'companyName', 'companyPhone'));
    }

    /**
     * AJAX Search for Unpaid Invoices.
     */
    public function searchInvoices(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $service = app(AgentService::class);
        $invoices = $service->searchUnpaidInvoices($query, 10);

        $data = $invoices->map(function (Invoice $inv) {
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
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * AJAX Process Payment for an Invoice.
     */
    public function processPayment(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $agent = $this->getActiveAgent();
        if (! $agent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun agen tidak ditemukan atau belum ditautkan.',
            ], 422);
        }

        if (! $agent->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun loket agen Anda sedang nonaktif.',
            ], 422);
        }

        $invoice = Invoice::with(['customer.package', 'customer.router', 'package'])->find($request->input('invoice_id'));
        if (! $invoice || $invoice->status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan atau sudah lunas.',
            ], 422);
        }

        $fee = (float) $agent->commission_per_transaction;
        $deduction = (float) $invoice->total_amount - $fee;

        if ((float) $agent->balance < $deduction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo deposit loket Anda tidak mencukupi (Sisa saldo: '.$agent->formatted_balance.', dibutuhkan: Rp '.number_format($deduction, 0, ',', '.').'). Silakan lakukan top-up ke administrator.',
            ], 422);
        }

        try {
            $service = app(AgentService::class);
            $tx = $service->payInvoice($agent, $invoice, Auth::user());

            $cust = $invoice->customer;

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran tagihan '.$invoice->invoice_number.' berhasil!',
                'data' => [
                    'reference' => $tx->payment_reference,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $cust?->name ?? 'Pelanggan',
                    'pppoe_username' => $cust?->pppoe_username ?? '-',
                    'amount_paid' => (float) $invoice->total_amount,
                    'formatted_amount' => 'Rp '.number_format((float) $invoice->total_amount, 0, ',', '.'),
                    'commission' => $fee,
                    'formatted_commission' => 'Rp '.number_format($fee, 0, ',', '.'),
                    'balance_after' => (float) $tx->balance_after,
                    'formatted_balance' => 'Rp '.number_format((float) $tx->balance_after, 0, ',', '.'),
                    'print_url' => route('print.thermal', $invoice->id),
                    'time' => now()->format('d/m/Y H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembayaran: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agent Mutasi / Transaction History.
     */
    public function history(Request $request): View
    {
        $agent = $this->getActiveAgent();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        $query = AgentTransaction::query();
        if ($agent) {
            $query->where('agent_id', $agent->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('type') && in_array($request->type, ['deposit', 'pppoe_payment'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('pppoe_username', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $transactions = $query->with(['invoice.customer', 'creator'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('agent.history', compact('agent', 'companyName', 'transactions'));
    }

    /**
     * Agent Profile Page.
     */
    public function profile(): View
    {
        $agent = $this->getActiveAgent();
        $user = Auth::user();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('agent.profile', compact('agent', 'user', 'companyName'));
    }

    /**
     * Update Password for Agent Account.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password akun agen berhasil diperbarui.');
    }
}
