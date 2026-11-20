<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\InstallationService;
use App\Services\Payment\QrisService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! app()->environment('testing') && ! InstallationService::isInstalled()) {
            return redirect()->route('install.step1');
        }

        $pppoePackages = InternetPackage::where('is_active', true)
            ->where('type', 'pppoe')
            ->orderBy('price')
            ->get();

        $hotspotPackages = InternetPackage::where('is_active', true)
            ->where('type', 'hotspot')
            ->orderBy('price')
            ->get();

        $dedicatedPackages = InternetPackage::where('is_active', true)
            ->where('type', 'static')
            ->orderBy('price')
            ->get();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');
        $companyEmail = IspSetting::get('company_email', 'cs@netspeed.net.id');
        $companyAddress = IspSetting::get('company_address', 'Jl. Telekomunikasi No. 1, Cyber Park');
        $office = IspSetting::getOfficeLocation();

        return view('landing', compact(
            'pppoePackages',
            'hotspotPackages',
            'dedicatedPackages',
            'companyName',
            'companyPhone',
            'companyEmail',
            'companyAddress',
            'office'
        ));
    }

    /**
     * Check customer bill by email or phone (WhatsApp).
     */
    public function checkBill(Request $request, QrisService $qrisService): JsonResponse
    {
        $search = trim((string) $request->input('search'));

        if (empty($search)) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masukkan Email atau Nomor WhatsApp Anda.',
            ], 422);
        }

        $phoneDigits = preg_replace('/[^0-9]/', '', $search);

        $customer = Customer::where('email', $search)
            ->orWhere('phone', $search)
            ->when(strlen($phoneDigits) >= 8, function ($q) use ($phoneDigits) {
                $q->orWhere('phone', 'like', "%{$phoneDigits}%");
                if (str_starts_with($phoneDigits, '62') && strlen($phoneDigits) > 9) {
                    $local = '0'.substr($phoneDigits, 2);
                    $q->orWhere('phone', $local);
                } elseif (str_starts_with($phoneDigits, '0') && strlen($phoneDigits) > 9) {
                    $intl = '62'.substr($phoneDigits, 1);
                    $q->orWhere('phone', $intl);
                }
            })
            ->orWhere('customer_number', $search)
            ->with(['package'])
            ->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => "Data pelanggan dengan kata kunci \"{$search}\" tidak ditemukan. Pastikan Email atau Nomor WhatsApp Anda sudah terdaftar.",
            ], 404);
        }

        // Find active unpaid invoice
        $unpaidInvoice = Invoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->first();

        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $cleanPhone = preg_replace('/[^0-9]/', '', $companyPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $staticQris = IspSetting::get('static_qris', '');
        $qrisQr = null;

        if ($unpaidInvoice && ! empty($staticQris)) {
            try {
                $qrisString = $qrisService->convertStaticToDynamic(
                    $staticQris,
                    (float) $unpaidInvoice->amount,
                    $unpaidInvoice->invoice_number
                );
                $qrisQr = $qrisService->renderDataUri($qrisString);
            } catch (Exception) {
                $qrisQr = null;
            }
        }

        $waText = urlencode("Halo Admin {$companyName}, saya {$customer->name} (ID: {$customer->customer_number}). Saya ingin konfirmasi tagihan internet untuk invoice ".($unpaidInvoice?->invoice_number ?? '-'));
        $waUrl = "https://wa.me/{$cleanPhone}?text={$waText}";

        return response()->json([
            'success' => true,
            'customer' => [
                'name' => $customer->name,
                'customer_number' => $customer->customer_number,
                'pppoe_username' => $customer->pppoe_username,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'status' => $customer->status,
                'package_name' => $customer->package?->name ?? 'Paket Kustom',
                'speed' => $customer->package?->speed_rate ?? '-',
            ],
            'has_unpaid' => (bool) $unpaidInvoice,
            'invoice' => $unpaidInvoice ? [
                'invoice_number' => $unpaidInvoice->invoice_number,
                'period' => $unpaidInvoice->period,
                'amount' => (float) $unpaidInvoice->amount,
                'total_amount' => (float) $unpaidInvoice->amount,
                'amount_formatted' => 'Rp '.number_format($unpaidInvoice->amount, 0, ',', '.'),
                'due_date' => $unpaidInvoice->due_date ? date('d F Y', strtotime($unpaidInvoice->due_date)) : 'Segera',
                'status' => $unpaidInvoice->status,
                'is_unpaid' => true,
            ] : null,
            'qris_qr' => $qrisQr,
            'qris_data_uri' => $qrisQr,
            'wa_url' => $waUrl,
            'company_name' => $companyName,
            'company_phone' => $companyPhone,
        ]);
    }

    /**
     * Submit new customer installation lead.
     */
    public function registerLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'package_id' => 'nullable|exists:internet_packages,id',
            'address' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $package = ! empty($validated['package_id']) ? InternetPackage::find($validated['package_id']) : null;
        $packageName = $package ? "{$package->name} ({$package->speed_rate} - Rp ".number_format($package->price, 0, ',', '.').')' : 'Pilihan Paket Konsultasi';

        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');
        $cleanPhone = preg_replace('/[^0-9]/', '', $companyPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $waMessage = "*PENDAFTARAN PASANG BARU INTERNET*\n\n"
            ."Nama Calon Pelanggan: *{$validated['name']}*\n"
            ."Nomor WhatsApp: *{$validated['phone']}*\n"
            ."Paket Diminati: *{$packageName}*\n"
            ."Alamat Lengkap: *{$validated['address']}*\n"
            .(! empty($validated['notes']) ? "Catatan: {$validated['notes']}\n\n" : "\n")
            .'Mohon info jadwal survey dan ketersediaan port ODP di lokasi saya. Terima kasih!';

        $waUrl = "https://wa.me/{$cleanPhone}?text=".urlencode($waMessage);

        return response()->json([
            'success' => true,
            'message' => "Terima kasih, Bpk/Ibu {$validated['name']}! Pengajuan pasang baru berhasil dicatat. Tim kami akan segera menghubungi Anda.",
            'wa_url' => $waUrl,
        ]);
    }
}
