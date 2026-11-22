<?php

namespace Database\Seeders;

use App\Models\IspSetting;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingQris = IspSetting::get('static_qris_string');
        $defaultQris = $existingQris ?: '00020101021126680016ID.CO.QRIS.WWW011893600999000000000102150000000000000010303UMI51440014ID.LINKAJA.WWW02150000000000000010303UMI5204481453033605802ID5916NETSPEED BILLING6007JAKARTA61051234063047209';

        $gateways = [
            [
                'code' => 'qris_manual',
                'name' => 'QRIS Dinamis Mandiri (0% MDR)',
                'category' => 'qris',
                'logo' => 'qris',
                'description' => 'QRIS barcode resmi toko tanpa potongan biaya transaksi. Mendukung semua e-wallet & mobile banking nasional.',
                'fee_type' => 'flat',
                'fee_flat' => 0,
                'fee_percent' => 0,
                'instructions' => 'Buka aplikasi e-wallet (GoPay, OVO, Dana, LinkAja) atau m-Banking Anda, pilih Scan QRIS, lalu arahkan kamera ke barcode.',
                'is_active' => true,
                'sort_order' => 1,
                'config' => [
                    'static_qris_string' => $defaultQris,
                    'use_unique_code' => IspSetting::get('use_unique_code', '1') === '1',
                    'qris_image_upload' => IspSetting::get('qris_image_upload', null),
                ],
            ],
            [
                'code' => 'tripay',
                'name' => 'TriPay (Virtual Account, QRIS & Minimarket)',
                'category' => 'multi_channel',
                'logo' => 'tripay',
                'description' => 'Multi-channel otomatis: Virtual Account BCA, BRI, Mandiri, BNI, BSI, Permata, serta Alfamart & Indomaret.',
                'fee_type' => 'flat',
                'fee_flat' => 1500,
                'fee_percent' => 0,
                'instructions' => 'Gunakan nomor Virtual Account yang diterbitkan sistem untuk membayar lewat ATM, Mobile Banking, atau kasir minimarket.',
                'is_active' => false,
                'sort_order' => 2,
                'config' => [
                    'api_url' => IspSetting::get('payment_gateway_api_url') ?: 'https://tripay.co.id/api/',
                    'server_key' => IspSetting::get('payment_gateway_server_key') ?: '',
                    'client_key' => IspSetting::get('payment_gateway_client_key') ?: '',
                    'merchant_code' => IspSetting::get('payment_gateway_merchant_code') ?: '',
                    'webhook_secret' => IspSetting::get('payment_webhook_secret') ?: '',
                ],
            ],
            [
                'code' => 'duitku',
                'name' => 'Duitku (VA Bank, E-Wallet & Retail Mart)',
                'category' => 'multi_channel',
                'logo' => 'duitku',
                'description' => 'Mendukung VA semua bank nasional, OVO, ShopeePay, Dana, LinkAja, Alfamart & Indomaret.',
                'fee_type' => 'flat',
                'fee_flat' => 1500,
                'fee_percent' => 0,
                'instructions' => 'Bayar via Virtual Account atau e-wallet yang Anda pilih melalui gateway Duitku.',
                'is_active' => false,
                'sort_order' => 3,
                'config' => [
                    'api_url' => 'https://passport.duitku.com/webapi/api/merchant/',
                    'server_key' => '',
                    'merchant_code' => '',
                ],
            ],
            [
                'code' => 'midtrans',
                'name' => 'Midtrans (GoPay, QRIS, Virtual Account)',
                'category' => 'multi_channel',
                'logo' => 'midtrans',
                'description' => 'Payment gateway terintegrasi GoPay, ShopeePay, dan Virtual Account bank utama.',
                'fee_type' => 'percent',
                'fee_flat' => 0,
                'fee_percent' => 1.5,
                'instructions' => 'Selesaikan pembayaran menggunakan GoPay atau nomor Virtual Account Midtrans Anda.',
                'is_active' => false,
                'sort_order' => 4,
                'config' => [
                    'api_url' => 'https://api.midtrans.com/v2/',
                    'server_key' => '',
                    'client_key' => '',
                ],
            ],
            [
                'code' => 'xendit',
                'name' => 'Xendit Payment Gateway',
                'category' => 'multi_channel',
                'logo' => 'xendit',
                'description' => 'Virtual Account, e-Wallets, QRIS, dan retail outlets berlisensi Bank Indonesia.',
                'fee_type' => 'flat',
                'fee_flat' => 2000,
                'fee_percent' => 0,
                'instructions' => 'Bayar melalui saluran pembayaran resmi Xendit.',
                'is_active' => false,
                'sort_order' => 5,
                'config' => [
                    'server_key' => '',
                    'webhook_secret' => '',
                ],
            ],
            [
                'code' => 'cekmutasi',
                'name' => 'CekMutasi / Moota (Auto Mutasi Bank)',
                'category' => 'mutation',
                'logo' => 'cekmutasi',
                'description' => 'Transfer bank manual langsung ke rekening ISP dengan verifikasi otomatis via mutasi rekening.',
                'fee_type' => 'flat',
                'fee_flat' => 0,
                'fee_percent' => 0,
                'instructions' => 'Transfer tepat sesuai nominal tagihan hingga 3 digit terakhir untuk verifikasi otomatis.',
                'is_active' => false,
                'sort_order' => 6,
                'config' => [
                    'webhook_secret' => '',
                ],
            ],
            [
                'code' => 'manual_transfer',
                'name' => 'Transfer Manual Rekening Bank',
                'category' => 'manual',
                'logo' => 'bank',
                'description' => 'Transfer langsung ke rekening bank ISP dan kirim bukti pembayaran via WhatsApp CS.',
                'fee_type' => 'flat',
                'fee_flat' => 0,
                'fee_percent' => 0,
                'instructions' => "Bank: BCA\nNo Rekening: 1234567890\nAtas Nama: NetSpeed Internet\n\nSetelah transfer, kirim bukti transfer ke WhatsApp Customer Service.",
                'is_active' => true,
                'sort_order' => 7,
                'config' => [
                    'bank_name' => 'BCA',
                    'account_number' => '1234567890',
                    'account_holder' => 'NetSpeed Internet',
                ],
            ],
        ];

        foreach ($gateways as $gw) {
            PaymentGateway::updateOrCreate(
                ['code' => $gw['code']],
                $gw
            );
        }
    }
}
