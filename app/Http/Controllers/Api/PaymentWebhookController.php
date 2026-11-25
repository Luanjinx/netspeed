<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotspotVoucher;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Router;
use App\Models\VoucherOrder;
use App\Services\Billing\BillingService;
use App\Services\Hotspot\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        if (! $this->verifyWebhookAuthorization($request)) {
            Log::warning('Unauthorized payment webhook attempt rejected.', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook signature atau token tidak valid.',
            ], 401);
        }

        $payload = $request->all();
        Log::info('Payment webhook received:', ['ip' => $request->ip(), 'keys' => array_keys($payload)]);

        // 1. Check for CekMutasi array format
        if (isset($payload['data']) && is_array($payload['data'])) {
            $settledCount = 0;
            foreach ($payload['data'] as $entry) {
                if (($entry['type'] ?? '') === 'credit') {
                    $amount = (float) ($entry['amount'] ?? 0);
                    $desc = (string) ($entry['description'] ?? '');
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
                    $desc = (string) ($item['description'] ?? '');
                    if ($this->settleByAmountOrDesc($amount, $desc, 'Moota')) {
                        $settledCount++;
                    }
                }
            }

            return response()->json(['success' => true, 'settled' => $settledCount]);
        }

        // 3. Standard single payload (Tripay, Midtrans, or Custom Callback)
        $orderRef = $request->input('invoice_number')
            ?: $request->input('bill_no')
            ?: $request->input('merchant_ref')
            ?: $request->input('order_id');

        $amount = (float) ($request->input('amount') ?: $request->input('total_amount'));

        // Check if this webhook is for a Hotspot Voucher Order
        $voucherOrder = null;
        if ($orderRef && str_starts_with((string) $orderRef, 'VCH-')) {
            $voucherOrder = VoucherOrder::where('order_number', $orderRef)->with(['package', 'router'])->first();
        } elseif (! $orderRef && $amount > 0) {
            $voucherOrder = VoucherOrder::where('status', 'pending')
                ->where('total_amount', $amount)
                ->with(['package', 'router'])
                ->latest()
                ->first();
        }

        if ($voucherOrder) {
            if ($voucherOrder->status === 'paid' && ! empty($voucherOrder->voucher_code)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan voucher sudah lunas sebelumnya.',
                ]);
            }

            $this->issueVoucherForOrder($voucherOrder);

            return response()->json([
                'success' => true,
                'message' => "Pembayaran pesanan voucher {$voucherOrder->order_number} berhasil diverifikasi dan kode hotspot diterbitkan.",
            ]);
        }

        $invoice = null;

        if ($orderRef) {
            $invoice = Invoice::where('invoice_number', $orderRef)->first();
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

        $method = (string) $request->input('payment_method', 'qris_dynamic');
        $reference = $request->input('trx_id') ?: $request->input('reference') ?: ('WEBHOOK-'.time());

        $this->billingService->markInvoicePaid($invoice, $method, "Webhook callback: {$reference}");

        return response()->json([
            'success' => true,
            'message' => "Pembayaran tagihan {$invoice->invoice_number} berhasil diverifikasi dan internet pelanggan telah aktif.",
        ]);
    }

    /**
     * Verify incoming webhook signature or shared secret token.
     */
    protected function verifyWebhookAuthorization(Request $request): bool
    {
        $configuredSecret = trim((string) IspSetting::get('payment_webhook_secret', ''));

        if ($configuredSecret === '') {
            return ! app()->environment('production');
        }

        $providedToken = (string) (
            $request->header('X-Webhook-Secret')
            ?: $request->header('X-Callback-Token')
            ?: ''
        );

        if ($providedToken !== '' && hash_equals($configuredSecret, $providedToken)) {
            return true;
        }

        $providedSignature = (string) (
            $request->header('X-Callback-Signature')
            ?: $request->header('X-Signature')
            ?: ''
        );

        if ($providedSignature !== '') {
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $configuredSecret);

            return hash_equals($expectedSignature, $providedSignature);
        }

        return false;
    }

    /**
     * Helper to match mutation by exact total_amount or invoice/order number in description.
     */
    protected function settleByAmountOrDesc(float $amount, string $description, string $provider): bool
    {
        if (preg_match('/(VCH-\d{14}-\d{3})/', $description, $vchMatches)) {
            $voucherOrder = VoucherOrder::where('order_number', $vchMatches[1])->with(['package', 'router'])->first();
            if ($voucherOrder && $voucherOrder->status === 'pending') {
                $this->issueVoucherForOrder($voucherOrder);

                return true;
            }
        }

        $invoice = null;

        // Try finding invoice number in description text (e.g. INV-202610-0001)
        if (preg_match('/((?:INV|UPG)-\d{6}-\d{4}(?:-\d{2})?)/', $description, $matches)) {
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

        if ($amount > 0) {
            $voucherOrder = VoucherOrder::where('status', 'pending')
                ->where('total_amount', $amount)
                ->with(['package', 'router'])
                ->latest()
                ->first();
            if ($voucherOrder) {
                $this->issueVoucherForOrder($voucherOrder);

                return true;
            }
        }

        return false;
    }

    /**
     * Generate and activate a hotspot voucher for a verified paid order.
     */
    public static function issueVoucherForOrder(VoucherOrder $order): VoucherOrder
    {
        return DB::transaction(function () use ($order): VoucherOrder {
            $locked = VoucherOrder::where('id', $order->id)->lockForUpdate()->with(['package', 'router'])->firstOrFail();
            if ($locked->status === 'paid' && ! empty($locked->voucher_code)) {
                return $locked;
            }

            $package = $locked->package;
            $router = $locked->router ?: Router::where('is_active', true)->firstOrFail();

            $voucherCode = strtoupper(Str::random(6));
            $voucherPassword = $voucherCode;

            $voucher = HotspotVoucher::create([
                'router_id' => $router->id,
                'code' => $voucherCode,
                'password' => $voucherPassword,
                'profile' => $package->mikrotik_profile ?: 'default',
                'shared_users' => $package->shared_users ?? 1,
                'price' => $locked->amount,
                'validity' => $package->validity_days.' Hari',
                'status' => 'unused',
                'generated_batch' => 'AUTO-'.date('Ymd'),
            ]);

            try {
                app(VoucherService::class)->syncSingleVoucherToRouter($voucher);
            } catch (\Throwable $e) {
                Log::warning('MikroTik sync voucher error: '.$e->getMessage());
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'hotspot_voucher_id' => $voucher->id,
                'voucher_code' => $voucherCode,
                'voucher_password' => $voucherPassword,
            ]);

            return $locked->fresh(['package', 'router']);
        });
    }
}
