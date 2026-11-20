<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\Payment\QrisService;

class PrintController extends Controller
{
    public function __construct(
        protected QrisService $qrisService
    ) {}

    /**
     * Print standard A4 Invoice / Kwitansi.
     */
    public function invoice(int $id)
    {
        $invoice = Invoice::with(['customer.package', 'customer.router', 'payments'])->findOrFail($id);
        $isp = [
            'name' => IspSetting::get('company_name', 'NetSpeed Internet Service'),
            'phone' => IspSetting::get('company_phone', '0812-3456-7890'),
            'address' => IspSetting::get('company_address', 'Jl. Telekomunikasi No. 88, Jakarta'),
        ];

        $qrDataUri = null;
        if (! empty($invoice->qris_payload)) {
            try {
                $qrDataUri = $this->qrisService->renderDataUri($invoice->qris_payload);
            } catch (\Exception $e) {
                $qrDataUri = null;
            }
        }

        return view('print.invoice-a4', compact('invoice', 'isp', 'qrDataUri'));
    }

    /**
     * Print Thermal Receipt (58mm / 80mm) for Bluetooth POS printers.
     */
    public function thermal(int $id)
    {
        $invoice = Invoice::with(['customer.package', 'payments'])->findOrFail($id);
        $isp = [
            'name' => IspSetting::get('company_name', 'NETSPEED INTERNET'),
            'phone' => IspSetting::get('company_phone', '081234567890'),
            'address' => IspSetting::get('company_address', 'Jakarta'),
        ];

        return view('print.receipt-thermal', compact('invoice', 'isp'));
    }
}
