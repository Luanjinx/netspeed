# NetSpeed ISP Billing & Core NOC System

Sistem manajemen billing ISP, otomasi router MikroTik, manajemen perangkat ONT (GenieACS), portal teknisi, dan API portal pelanggan berbasis **Laravel 13** dan **Filament 5**.

## Teknologi Utama

- **PHP**: `^8.3`
- **Backend Framework**: Laravel `^13.17`, Laravel Sanctum `^4.0`
- **Admin Panel**: Filament `^5.10`
- **Frontend Assets**: Tailwind CSS `^4.0`, Vite `^8.0`
- **Testing & Quality**: PHPUnit `^12.5`, Laravel Pint `^1.27`

## Fitur Utama

### Manajemen Pelanggan, Paket & Keuangan
- **Pelanggan & Layanan** (`Customers`, `InternetPackages`, `HotspotVouchers`): manajemen pelanggan PPPoE/Hotspot, FUP, kuota, dan isolir otomatis.
- **Alur Persetujuan Daftar & Upgrade Paket** (`PackageRequests`, Portal Teknisi `/teknisi/pengajuan`): pengajuan pendaftaran baru atau upgrade paket harus disetujui admin atau teknisi sebelum aktif.
- **Penagihan & Pembayaran** (`Invoices`, `Payments`, `PaymentGatewaySettings`, `QrisSettings`, `AgentPaymentPoint`): penerbitan tagihan, konfirmasi bukti bayar, webhook payment gateway, serta poin pembayaran agen (`Agents`).

### Otomasi Jaringan (MikroTik & GenieACS)
- **Router & Layanan** (`Routers`, `RouterMonitor`, `PppoeManager`, `HotspotManager`, `IsolirManager`): monitoring router dan sinkronisasi PPPoE/Hotspot/Isolir melalui MikroTik API.
- **MikroTik Tools** (`MikrotikTools`):
  - **Basic Configuration Script Generator** (`BasicConfigTool`) untuk RouterOS v6 & v7 (Identity, NTP, WAN DHCP/Static/PPPoE, Bridge LAN, DHCP Server, NAT, Firewall dasar, Service Hardening) + push langsung ke router.
  - `AddAdminUserTool`, `AddIpAddressTool`, `AddIpPoolTool`, `AntiDdosTool`, `AntiHackExploitTool`, dan `AntiNetcutTool`.
- **GenieACS TR-069 & Peta Jaringan** (`GenieacsServers`, `OntDeviceManager`, `NetworkMap`, `Odps`): manajemen modem ONT pelanggan (termasuk perubahan SSID/password Wi-Fi dari aplikasi), pemetaan ODP, dan jalur kabel.

### Operasional Teknisi, Notifikasi & Pembaruan Sistem
- **Teknisi & Helpdesk** (`Technicians`, `WorkOrders`, `SupportTickets`): pengelolaan SPK, status pekerjaan lapangan, dan tiket gangguan pelanggan.
- **Notifikasi & Integrasi** (`WhatsAppSettings`, `TelegramBotManager`, `NotificationManager`, `MobileAppSettings`): broadcast push notification OneSignal (dengan sinkronisasi `onesignal_app_id` publik ke aplikasi mobile), notifikasi WhatsApp, dan bot Telegram.
- **Updater Otomatis** (`SystemUpdate`): mendukung pembaruan langsung dari panel admin via Git (`-c safe.directory` otomatis untuk VPS/STB Linux) maupun fallback otomatis unduh & ekstrak arsip GitHub ZIP untuk **shared hosting / environment tanpa Git atau `proc_open`**.

## Instalasi Lokal

1. **Install dependency & siapkan environment:**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
2. **Konfigurasi database** pada file `.env`, lalu jalankan migrasi:
   ```bash
   php artisan migrate --force
   ```
3. **Build aset frontend:**
   ```bash
   npm install
   npm run build
   ```
4. **Jalankan server pengembangan:**
   ```bash
   composer run dev
   ```

## Panduan Update Server (VPS / STB / Shared Hosting)

### Melalui Panel Admin (**Pengaturan -> Update Sistem**)
- **VPS / STB Linux (dengan Git)**: pastikan folder proyek dimiliki oleh user PHP-FPM (misalnya `www-data`):
  ```bash
  sudo chown -R www-data:www-data /var/www/isp-billing
  sudo chmod -R 775 /var/www/isp-billing/storage /var/www/isp-billing/bootstrap/cache /var/www/isp-billing/.git
  ```
- **Shared Hosting (cPanel / DirectAdmin / tanpa Git atau `proc_open`)**: cukup pastikan ekstensi PHP `ext-zip` (`ZipArchive`) aktif; sistem otomatis menggunakan mode unduh arsip GitHub ZIP sambil melindungi `.env`, `storage/`, `bootstrap/cache/`, `vendor/`, dan `*.jks`.

### Secara Manual via SSH (VPS / STB)
```bash
cd /var/www/isp-billing
git pull origin main
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

## Pengujian & Formatting Kode

```bash
php artisan test
vendor/bin/pint --dirty --format agent
```

## Lisensi

Proyek ini berlisensi [MIT](https://opensource.org/licenses/MIT).
