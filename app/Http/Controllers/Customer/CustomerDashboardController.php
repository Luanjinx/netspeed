<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Payment\QrisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    /**
     * Customer Main Dashboard Overview.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer()->with(['package', 'router', 'odp'])->firstOrFail();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');

        // 1. Current unpaid invoice & QRIS
        $unpaidInvoice = Invoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->latest()
            ->first();

        $qrisDataUri = null;
        if ($unpaidInvoice) {
            $staticQris = IspSetting::get('qris_raw_payload', '');
            if (! empty($staticQris)) {
                try {
                    $qrisService = app(QrisService::class);
                    $dynamicQris = $qrisService->convertStaticToDynamic(
                        $staticQris,
                        (float) $unpaidInvoice->amount,
                        $unpaidInvoice->invoice_number
                    );
                    $qrisDataUri = $qrisService->renderDataUri($dynamicQris);
                } catch (\Throwable) {
                    $qrisDataUri = null;
                }
            }
        }

        // WhatsApp Confirmation Link
        $cleanPhone = preg_replace('/[^0-9]/', '', $companyPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }
        $waText = urlencode("Halo Admin {$companyName}, saya {$customer->name} (ID: {$customer->customer_number}). Saya ingin konfirmasi pembayaran tagihan internet invoice ".($unpaidInvoice?->invoice_number ?? '-'));
        $waConfirmUrl = "https://wa.me/{$cleanPhone}?text={$waText}";

        // 2. Paid Invoices History
        $paidInvoices = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->latest()
            ->take(5)
            ->get();

        // 3. Current Month FUP Usage
        $fupUsage = $customer->currentMonthFupUsage;

        // 4. TR-069 GenieACS Modem Wi-Fi & Signal Details
        $wifiSettings = ['ssid' => null, 'password' => null];
        $opticalPower = null;
        $genieacsConnected = false;

        if ($customer->genieacs_server_id && $customer->genieacs_device_id && $customer->genieacsServer) {
            $genieacsConnected = true;
            try {
                $genieacs = app(GenieacsApiService::class);
                $wifiRes = $genieacs->getWifiSettings($customer->genieacsServer, $customer->genieacs_device_id);
                if ($wifiRes['success']) {
                    $wifiSettings = $wifiRes;
                }

                $optRes = $genieacs->getOpticalPower($customer->genieacsServer, $customer->genieacs_device_id);
                if ($optRes['success']) {
                    $opticalPower = $optRes;
                }
            } catch (\Throwable) {
                // Silently fallback if server temporarily unreachable
            }
        }

        // 5. Recent Trouble Tickets
        $recentTickets = SupportTicket::where('customer_id', $customer->id)
            ->latest()
            ->take(5)
            ->get();

        return view('customer.dashboard', compact(
            'customer',
            'companyName',
            'companyPhone',
            'unpaidInvoice',
            'qrisDataUri',
            'waConfirmUrl',
            'paidInvoices',
            'fupUsage',
            'wifiSettings',
            'opticalPower',
            'genieacsConnected',
            'recentTickets',
        ));
    }

    /**
     * Change ONT Wi-Fi SSID and Password via TR-069 GenieACS.
     */
    public function changeWifi(Request $request): RedirectResponse
    {
        $request->validate([
            'ssid' => 'required|string|min:3|max:32',
            'password' => 'required|string|min:8|max:64',
            'reboot_now' => 'nullable|boolean',
        ], [
            'ssid.required' => 'Nama Wi-Fi (SSID) wajib diisi.',
            'password.required' => 'Password Wi-Fi baru wajib diisi minimal 8 karakter.',
            'password.min' => 'Password Wi-Fi minimal 8 karakter.',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || ! $customer->genieacs_server_id || ! $customer->genieacs_device_id || ! $customer->genieacsServer) {
            return back()->with('error', 'Modem ONT Anda belum terhubung dengan sistem TR-069 GenieACS. Silakan hubungi admin.');
        }

        $genieacs = app(GenieacsApiService::class);
        $server = $customer->genieacsServer;
        $deviceId = $customer->genieacs_device_id;

        $success = $genieacs->setWifiSettings($server, $deviceId, $request->input('ssid'), $request->input('password'));

        if ($success) {
            if ($request->boolean('reboot_now')) {
                $genieacs->rebootDevice($server, $deviceId);
            }

            return back()->with('success', "Pengaturan Wi-Fi baru '{$request->input('ssid')}' berhasil dikirim ke modem Anda!".($request->boolean('reboot_now') ? ' Modem sedang melakukan restart otomatis.' : ''));
        }

        return back()->with('error', 'Gagal memperbarui Wi-Fi modem. Modem sedang offline atau tidak merespons.');
    }

    /**
     * Reboot ONT modem remotely via TR-069 GenieACS.
     */
    public function rebootOnt(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || ! $customer->genieacs_server_id || ! $customer->genieacs_device_id || ! $customer->genieacsServer) {
            return back()->with('error', 'Modem ONT belum terhubung ke sistem TR-069.');
        }

        $genieacs = app(GenieacsApiService::class);
        $success = $genieacs->rebootDevice($customer->genieacsServer, $customer->genieacs_device_id);

        if ($success) {
            return back()->with('success', 'Perintah restart berhasil dikirim ke modem ONT. Mohon tunggu 1-2 menit hingga koneksi menyala kembali.');
        }

        return back()->with('error', 'Gagal mengirim perintah restart. Server TR-069 sedang sibuk atau modem tidak merespons.');
    }

    /**
     * Upload Payment Proof for an Unpaid Invoice.
     */
    public function uploadProof(Request $request, int $invoiceId): RedirectResponse
    {
        $request->validate([
            'proof_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'proof_image.required' => 'File bukti transfer wajib dipilih.',
            'proof_image.image' => 'File harus berupa gambar foto (JPG, PNG, WEBP).',
            'proof_image.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $path = $request->file('proof_image')->store('payment-proofs', 'public');

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-WEB-'.time(),
            'amount_paid' => $invoice->total_amount,
            'payment_method' => 'manual_transfer',
            'proof_image' => $path,
            'is_verified' => false,
            'notes' => 'Bukti pembayaran diunggah oleh pelanggan via Portal Web Pelanggan',
        ]);

        return back()->with('success', 'Bukti pembayaran Anda berhasil diunggah! Admin kami akan memverifikasi pembayaran Anda segera.');
    }

    /**
     * Trouble Tickets List & History.
     */
    public function tickets(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer()->with(['package', 'router'])->firstOrFail();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');

        $tickets = SupportTicket::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.tickets', compact('customer', 'tickets', 'companyName', 'companyPhone'));
    }

    /**
     * Store New Trouble Ticket from Customer.
     */
    public function storeTicket(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ], [
            'subject.required' => 'Judul kendala wajib diisi.',
            'description.required' => 'Deskripsi keluhan wajib diisi dengan jelas.',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-'.date('Ym').'-'.rand(100, 999),
            'customer_id' => $customer->id,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'medium'),
            'status' => 'open',
        ]);

        return back()->with('success', "Tiket gangguan #{$ticket->ticket_number} berhasil dikirim! Tim teknisi kami akan segera menangani laporan Anda.");
    }

    /**
     * Customer Profile & Password Settings.
     */
    public function profile(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer()->with(['package', 'router'])->firstOrFail();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');

        return view('customer.profile', compact('customer', 'companyName', 'companyPhone'));
    }

    /**
     * Update Customer Account Password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        // Update User & Customer password
        $user->update(['password' => Hash::make($request->input('password'))]);
        if ($customer) {
            $customer->update(['password' => $request->input('password')]);
        }

        return back()->with('success', 'Password akun Anda berhasil diperbarui! Silakan gunakan password baru ini untuk login berikutnya.');
    }
}
