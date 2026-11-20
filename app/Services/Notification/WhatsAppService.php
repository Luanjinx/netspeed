<?php

namespace App\Services\Notification;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Models\WorkOrder;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send generic WhatsApp text message.
     */
    public function sendMessage(string $phone, string $message): array
    {
        $gatewayUrl = IspSetting::get('whatsapp_gateway_url', 'https://api.fonnte.com/send');
        $apiKey = IspSetting::get('whatsapp_api_key', '');

        if (empty($gatewayUrl) || empty($apiKey)) {
            Log::info("WhatsApp Gateway belum dikonfigurasi. Pesan ke {$phone} dilewati.");

            return [
                'success' => false,
                'message' => 'API Key atau Gateway WhatsApp belum dikonfigurasi di Pengaturan ISP.',
            ];
        }

        // Clean Indonesian phone number into 628xxx format
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        try {
            // Standard Fonnte / Wablas compatibility
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $apiKey,
                ])
                ->post($gatewayUrl, [
                    'target' => $cleanPhone,
                    'phone' => $cleanPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim.',
                    'response' => $response->json(),
                ];
            }

            Log::warning("WhatsApp send failed to {$cleanPhone}: HTTP ".$response->status().' - '.$response->body());

            return [
                'success' => false,
                'message' => 'Gagal mengirim WhatsApp: HTTP '.$response->status(),
            ];
        } catch (Exception $e) {
            Log::error('WhatsApp exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Notification: Tagihan Baru Diterbitkan.
     */
    public function sendInvoiceCreated(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        if (! $customer || empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP pelanggan tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');
        $dueDate = $invoice->due_date ? date('d-m-Y', strtotime($invoice->due_date)) : '-';
        $totalFormatted = number_format($invoice->total_amount, 0, ',', '.');

        $msg = "*TAGIHAN INTERNET BULANAN*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Tagihan internet Anda untuk periode *{$invoice->period}* telah terbit:\n"
            ."• No. Tagihan: *{$invoice->invoice_number}*\n"
            ."• Paket: *{$customer->package?->name}*\n"
            ."• Total Bayar: *Rp {$totalFormatted}*\n"
            ."• Jatuh Tempo: *{$dueDate}*\n\n"
            ."Pembayaran dapat dilakukan melalui scan *QRIS Dinamis* di aplikasi mobile pelanggan atau transfer sesuai nominal tepat di atas.\n\n"
            ."Terima kasih atas kepercayaannya.\n_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Pengingat Jatuh Tempo (Reminder H-3 / H-0).
     */
    public function sendDueReminder(Invoice $invoice, int $daysLeft = 0): array
    {
        $customer = $invoice->customer;
        if (! $customer || empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');
        $dueDate = $invoice->due_date ? date('d-m-Y', strtotime($invoice->due_date)) : '-';
        $totalFormatted = number_format($invoice->total_amount, 0, ',', '.');

        $statusText = $daysLeft > 0
            ? "akan jatuh tempo dalam *{$daysLeft} hari lagi* ({$dueDate})"
            : "jatuh tempo *HARI INI* ({$dueDate})";

        $msg = "*PENGINGAT JATUH TEMPO TAGIHAN*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Tagihan internet Anda {$statusText}:\n"
            ."• No. Tagihan: *{$invoice->invoice_number}*\n"
            ."• Nominal: *Rp {$totalFormatted}*\n\n"
            ."Mohon segera lakukan pembayaran agar koneksi internet Anda tetap lancar dan tidak terisolir otomatis oleh sistem.\n\n"
            ."Terima kasih.\n_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Kwitansi / Bukti Pembayaran Lunas.
     */
    public function sendPaymentReceipt(Payment $payment): array
    {
        $invoice = $payment->invoice;
        $customer = $invoice?->customer;

        if (! $customer || empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');
        $amountFormatted = number_format($payment->amount_paid, 0, ',', '.');
        $paidTime = $payment->verified_at ? date('d-m-Y H:i', strtotime($payment->verified_at)) : date('d-m-Y H:i');

        $msg = "*KWITANSI PEMBAYARAN LUNAS*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Pembayaran tagihan internet Anda telah kami terima:\n"
            ."• No. Kwitansi: *{$payment->payment_reference}*\n"
            ."• No. Tagihan: *{$invoice->invoice_number}*\n"
            ."• Periode: *{$invoice->period}*\n"
            ."• Jumlah: *Rp {$amountFormatted}*\n"
            ."• Waktu Bayar: *{$paidTime}*\n"
            ."• Status: *LUNAS & AKTIF*\n\n"
            ."Koneksi internet Anda aktif normal. Terima kasih atas pembayaran tepat waktunya.\n\n"
            ."_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Pemberitahuan Layanan Terisolir.
     */
    public function sendIsolirAlert(Customer $customer): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');

        $msg = "*PEMBERITAHUAN ISOLIR SEMENTARA*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Layanan internet Anda sementara waktu *TERISOLIR* dikarenakan tagihan bulanan telah melewati batas waktu jatuh tempo.\n\n"
            ."Silakan segera melakukan pelunasan tagihan melalui scan QRIS pada aplikasi pelanggan atau hubungi customer service kami agar sistem otomatis mengaktifkan kembali koneksi internet Anda.\n\n"
            .'Bantuan & Konfirmasi: '.IspSetting::get('company_phone', 'CS')."\n"
            ."_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Batas Pemakaian Wajar (FUP) Tercapai.
     */
    public function sendFupAlert(Customer $customer, float $usageGb, float $limitGb, string $throttleSpeed): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');
        $usageFormatted = number_format($usageGb, 1, ',', '.');
        $limitFormatted = number_format($limitGb, 0, ',', '.');

        $msg = "*INFORMASI BATAS PEMAKAIAN WAJAR (FUP)*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Kami informasikan bahwa pemakaian internet Anda bulan ini telah mencapai *{$usageFormatted} GB* dari batas FUP paket Anda (*{$limitFormatted} GB*).\n\n"
            ."Sesuai ketentuan, kecepatan akses internet Anda disesuaikan menjadi *{$throttleSpeed}* hingga akhir periode bulan ini, dan akan kembali normal secara otomatis pada awal siklus berikutnya.\n\n"
            ."Internet tetap dapat digunakan secara aktif tanpa biaya tambahan.\n"
            ."Terima kasih atas pengertiannya.\n\n"
            ."_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Kecepatan Normal / Reset FUP Dipulihkan.
     */
    public function sendFupRestored(Customer $customer): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'ISP Internet');
        $normalSpeed = $customer->package?->speed_rate ?? 'Normal';

        $msg = "*KECEPATAN INTERNET NORMAL KEMBALI*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Kabar baik! Siklus baru pemakaian internet Anda telah dimulai. Kecepatan internet Anda telah *DIPULIHKAN PENUH* ke *{$normalSpeed}*.\n\n"
            ."Selamat menikmati kembali akses internet berkecepatan maksimal.\n\n"
            ."_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }

    /**
     * Notification: Pendaftaran Pasang Baru (Surat Tugas / SPK Terbit).
     */
    public function sendNewRegistration(Customer $customer, WorkOrder $workOrder): array
    {
        if (empty($customer->phone)) {
            return ['success' => false, 'message' => 'Nomor HP tidak valid'];
        }

        $ispName = IspSetting::get('company_name', 'NetSpeed ISP');
        $packageName = $customer->package?->name ?? 'Paket Internet';
        $price = $customer->package ? 'Rp '.number_format($customer->package->price, 0, ',', '.') : '-';

        $msg = "*PENDAFTARAN PASANG BARU BERHASIL*\n"
            ."Halo Bpk/Ibu *{$customer->name}*,\n\n"
            ."Terima kasih telah memilih *{$ispName}*. Permohonan pemasangan baru Anda telah kami terima:\n\n"
            ."• No. Pelanggan: *{$customer->customer_number}*\n"
            ."• No. SPK/Tugas: *{$workOrder->order_number}*\n"
            ."• Pilihan Paket: *{$packageName}* ({$price}/bln)\n"
            ."• Alamat: *{$customer->address}*\n"
            ."• Status: *Menunggu Penjadwalan Teknisi*\n\n"
            ."Tim teknisi kami akan segera menghubungi Anda melalui nomor WhatsApp ini untuk konfirmasi jadwal survei & instalasi ke lokasi.\n\n"
            ."Anda juga dapat memantau status pemasangan melalui aplikasi mobile *{$ispName}* menggunakan nomor WhatsApp atau ID Pelanggan Anda.\n\n"
            ."Salam Hangat,\n_{$ispName}_";

        return $this->sendMessage($customer->phone, $msg);
    }
}
