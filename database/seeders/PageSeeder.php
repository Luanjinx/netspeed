<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Kebijakan Privasi',
                'order' => 1,
                'is_active' => true,
                'content' => "Kebijakan Privasi ini menjelaskan bagaimana NetSpeed Broadband mengumpulkan, menggunakan, mengelola, dan melindungi informasi pribadi Anda saat menggunakan layanan internet kami serta aplikasi mobile NetSpeed Customer App.\n\n1. Informasi yang Kami Kumpulkan\nKami mengumpulkan informasi yang Anda berikan secara langsung saat mendaftar atau menggunakan layanan, termasuk:\n• Nama lengkap, alamat pemasangan, dan nomor kontak (WhatsApp/telepon)\n• Alamat email dan identitas akun pelanggan\n• Informasi perangkat jaringan seperti router dan status koneksi ONT\n\n2. Penggunaan Informasi\nInformasi yang dikumpulkan digunakan untuk:\n• Menyediakan, mengoperasikan, dan memelihara kualitas koneksi internet Anda\n• Memproses tagihan bulanan dan konfirmasi pembayaran\n• Memberikan notifikasi tagihan, pemeliharaan jaringan, dan penanganan gangguan teknis\n• Mempermudah konsultasi layanan pelanggan melalui Customer Care\n\n3. Keamanan Data Pelanggan\nKami berkomitmen menjaga keamanan data pribadi Anda dengan menerapkan protokol enkripsi standar industri dan pengamanan server yang ketat. Kami tidak pernah menjual atau membagikan data pribadi Anda kepada pihak ketiga untuk tujuan komersial.\n\n4. Hak Pelanggan\nAnda berhak untuk memperbarui data kontak Anda, memeriksa riwayat tagihan, serta mengajukan permohonan penghapusan atau penyesuaian akun melalui kontak resmi Customer Service kami.\n\n5. Kontak Kami\nJika Anda memiliki pertanyaan mengenai kebijakan privasi ini, silakan hubungi Customer Service NetSpeed melalui WhatsApp resmi atau email cs@netspeed.net.id.",
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'terms-of-service'],
            [
                'title' => 'Syarat & Ketentuan',
                'order' => 2,
                'is_active' => true,
                'content' => "Selamat datang di NetSpeed Broadband. Dengan mendaftar, mengakses, atau menggunakan layanan internet dan aplikasi mobile kami, Anda setuju untuk terikat oleh Syarat dan Ketentuan berikut:\n\n1. Ketentuan Berlangganan\n• Layanan internet disediakan untuk penggunaan rumah tangga atau bisnis sesuai paket yang dipilih.\n• Pelanggan bertanggung jawab menjaga kerahasiaan kata sandi akun Wi-Fi dan kredensial portal pelanggan.\n\n2. Pembayaran dan Penagihan\n• Tagihan diterbitkan setiap bulan dan jatuh tempo sesuai tanggal yang tertera pada faktur.\n• Pembayaran dapat dilakukan secara instan melalui QRIS atau transfer bank resmi yang tertera di aplikasi.\n• Keterlambatan pembayaran dapat mengakibatkan isolir sementara pada akses internet hingga pembayaran diselesaikan.\n\n3. Perangkat dan Instalasi\n• Perangkat modem/ONT yang dipinjamkan selama masa berlangganan tetap merupakan aset NetSpeed dan harus dirawat dengan baik oleh pelanggan.\n• Kerusakan atau kehilangan akibat kelalaian pelanggan dapat dikenakan biaya penggantian.\n\n4. Kebijakan Penggunaan Wajar (Fair Usage Policy)\n• Pelanggan dilarang menggunakan koneksi untuk aktivitas ilegal, penyebaran malware, peretasan, atau penjualan ulang bandwidth tanpa izin resmi tertulis.\n\n5. Perubahan Syarat & Ketentuan\nNetSpeed berhak mengubah atau memperbarui syarat dan ketentuan ini sewaktu-waktu dengan pemberitahuan melalui aplikasi atau broadcast resmi.",
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'Tentang Kami',
                'order' => 3,
                'is_active' => true,
                'content' => "NetSpeed Broadband adalah penyedia layanan internet pita lebar (broadband) berbasis serat optik (Fiber Optic) yang berkomitmen memberikan koneksi super cepat, stabil, dan tanpa batas kuota (unlimited) untuk keluarga, pelaku usaha, dan komunitas digital.\n\nVisi Kami:\nMenghubungkan setiap rumah dan bisnis dengan akses internet berkualitas tinggi yang terjangkau dan handal untuk mendukung kemajuan ekonomi digital Indonesia.\n\nMisi Kami:\n• Menyediakan infrastruktur jaringan fiber optik 100% dengan uptime maksimal.\n• Memberikan pelayanan pelanggan dan penanganan gangguan yang responsif serta profesional.\n• Menghadirkan paket internet fleksibel dengan harga transparan tanpa biaya tersembunyi.\n\nDidukung oleh tim teknisi bersertifikasi dan NOC (Network Operations Center) 24/7, NetSpeed siap menjadi mitra konektivitas digital terpercaya Anda.",
            ]
        );
    }
}
