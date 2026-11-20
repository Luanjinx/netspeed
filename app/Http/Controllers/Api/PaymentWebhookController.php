<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Webhook callback for QRIS, Bank Mutation (CekMutasi / Moota), or Payment Gateway.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Payment webhook received:', $payload);

        // 1. Check for CekMutasi array format
        if (isset($payload['data']) && is_array($payload['data'])) {
            $settledCount = 0;
            foreach ($payload['data'] as $entry) {
                if (($entry['type'] ?? '') === 'credit') {
                    $amount = (float) ($entry['amount'] ?? 0);
                    $desc = $entry['description'] ?? '';
                    if ($this->settleByAmountOrDesc($amount, $desc, 'CekMutasi')) {
                        $settledCount++;
                    }
                }
            }

            return response()->json(['success' => true, 'settled' => $settledCount]);
        }

        // 2. Check for Moota array format
        if (is_array($payload) && isset($payload[0]['mutation_id'])) {
            $settledCount = 0;
            foreach ($payload as $item) {
                if (($item['type'] ?? '') === 'CR') {
                    $amount = (float) ($item['amount'] ?? 0);
                    $desc = $item['description'] ?? '';
                    if ($this->settleByAmountOrDesc($amount, $desc, 'Moota')) {
                        $settledCount++;
                    }
                }
            }

            return response()->json(['success' => true, 'settled' => $settledCount]);
        }

        // 3. Standard single payload (Tripay, Midtrans, or Custom Callback)
        $invoiceNumber = $request->input('invoice_number')
            ?: $request->input('bill_no')
            ?: $request->input('merchant_ref')
            ?: $request->input('order_id');

        $amount = (float) ($request->input('amount') ?: $request->input('total_amount'));

        $invoice = null;

        if ($invoiceNumber) {
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();
        }

        if (! $invoice && $amount > 0) {
            // Find unpaid invoice matching exact amount (which includes the unique code)
            $invoice = Invoice::where('status', 'unpaid')
                ->where('total_amount', $amount)
                ->latest()
                ->first();
        }

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan yang sesuai tidak ditemukan.',
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Tagihan sudah lunas sebelumnya.',
            ]);
        }

        $method = $request->input('payment_method', 'qris_dynamic');
        $reference = $request->input('trx_id') ?: $request->input('reference') ?: ('WEBHOOK-'.time());

        $this->billingService->markInvoicePaid($invoice, $method, "Webhook callback: {$reference}");

        return response()->json([
            'success' => true,
            'message' => "Pembayaran tagihan {$invoice->invoice_number} berhasil diverifikasi dan internet pelanggan telah aktif.",
        ]);
    }

    /**
     * Helper to match mutation by exact total_amount or invoice number in description.
     */
    protected function settleByAmountOrDesc(float $amount, string $description, string $provider): bool
    {
        $invoice = null;

        // Try finding invoice number in description text (e.g. INV-202610-0001)
        if (preg_match('/(INV-\d{6}-\d{4})/', $description, $matches)) {
            $invoice = Invoice::where('invoice_number', $matches[1])->first();
        }

        // Otherwise find by exact amount (amount + unique code)
        if (! $invoice && $amount > 0) {
            $invoice = Invoice::where('status', 'unpaid')
                ->where('total_amount', $amount)
                ->latest()
                ->first();
        }

        if ($invoice && $invoice->status === 'unpaid') {
            $this->billingService->markInvoicePaid($invoice, 'bank_mutation', "Mutasi {$provider}: {$description}");

            return true;
        }

        return false;
    }
}
