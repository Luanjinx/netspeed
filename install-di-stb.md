# 🚀 Panduan Instalasi NetSpeed ISP Billing di STB (Set-Top Box)

Panduan lengkap instalasi dan optimasi **NetSpeed ISP Billing & Core NOC** pada perangkat **STB (Set-Top Box)** berbasis **Armbian Linux** (Ubuntu 22.04 / 24.04 LTS atau Debian 12 Bookworm).

---

## 📌 1. Kebutuhan Perangkat & Spesifikasi

- **Tipe STB yang Kompatibel:**
  - ZTE B860H (v1, v2, v5)
  - Fiberhome HG680P
  - X96 Max / X96 Mini / Tanix TX3 / HK1 Box (Amlogic S905X / S905W / S905X2 / S905X3)
- **Sistem Operasi:** Armbian Server (CLI tanpa Desktop GUI agar sangat hemat RAM).
- **RAM Minimum:** 1 GB (Wajib aktifkan Swap 2 GB). *Disarankan STB RAM 2 GB*.
- **Penyimpanan:** Internal eMMC atau USB Flashdisk 3.0 / SSD eksternal (Minimal 8 GB - 16 GB).
- **Akses:** Jaringan LAN/WiFi ke STB, remote via SSH (PuTTY, Terminal, atau Termius).

---

## 🛠️ Langkah 1: Persiapan Sistem & Pembuatan Swap File (Wajib)

Hubungkan ke STB Anda via SSH (contoh: `ssh root@192.168.1.100`), kemudian jalankan pembaruan sistem dan buat **Swap Memory 2 GB** agar STB tidak kehabisan RAM saat proses instalasi dependensi:

```bash
# Update repository & upgrade paket sistem
sudo apt update && sudo apt upgrade -y

# Buat Swap File 2 GB
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Pastikan swap otomatis aktif saat STB reboot
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Cek apakah swap sudah aktif
free -h
```

---

## 📦 Langkah 2: Instalasi Nginx, PHP 8.3 & MariaDB Server

### 1. Tambahkan Repository PHP 8.3 (Ondřej Surý PPA)
```bash
sudo apt install -y software-properties-common ca-certificates apt-transport-https lsb-release
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```
*(Catatan: Jika Anda menggunakan Debian 12, ikuti panduan repositori `deb.sury.org`).*

### 2. Pasang Nginx, MariaDB, dan PHP 8.3 beserta Ekstensi
```bash
sudo apt install -y nginx mariadb-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring \
  php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl php8.3-sqlite3 \
  php8.3-gd php8.3-readline git curl unzip
```

### 3. Pasang Composer (PHP Package Manager)
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 🗄️ Langkah 3: Konfigurasi Database MariaDB

Amankan instalasi MySQL / MariaDB dan buat database untuk ISP Billing:

```bash
sudo mysql_secure_installation
```
*(Ikuti petunjuk di layar: atur root password, hapus anonymous user, dan hapus test database).*

Masuk ke MariaDB Console:
```bash
sudo mysql -u root -p
```

Jalankan perintah SQL berikut untuk membuat database dan user (mendukung koneksi via socket `localhost` maupun TCP `127.0.0.1`):
```sql
CREATE DATABASE IF NOT EXISTS isp_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'isp_user'@'localhost' IDENTIFIED BY 'PasswordKuatISP123!';
CREATE USER IF NOT EXISTS 'isp_user'@'127.0.0.1' IDENTIFIED BY 'PasswordKuatISP123!';
GRANT ALL PRIVILEGES ON isp_billing.* TO 'isp_user'@'localhost';
GRANT ALL PRIVILEGES ON isp_billing.* TO 'isp_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

---

## 📁 Langkah 4: Deploy Source Code ISP Billing

### 1. Clone Repositori NetSpeed ke `/var/www/isp-billing`
Jalankan perintah clone langsung dari repositori GitHub:

```bash
# Clone source code langsung ke direktori web server
sudo git clone https://github.com/Luanjinx/netspeed.git /var/www/isp-billing
cd /var/www/isp-billing
```

### 2. Atur File Konfigurasi `.env`
Salin template konfigurasi:
```bash
cp .env.example .env
nano .env
```

Sesuaikan baris konfigurasi penting berikut:
```ini
APP_NAME="NetSpeed ISP"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP_STB_ANDA    # Contoh: http://192.168.1.100 atau domain/tunnel

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=isp_billing
DB_USERNAME=isp_user
DB_PASSWORD=PasswordKuatISP123!
```

### 3. Install Dependensi PHP & Eksekusi Migration
```bash
# Install paket composer untuk production
composer install --no-dev --optimize-autoloader

# Generate kunci enkripsi aplikasi
php artisan key:generate

# Jalankan migration database beserta data seeder default
php artisan migrate --seed --force

# Buat symbolic link untuk storage upload / file statis
php artisan storage:link

# Cache konfigurasi untuk performa maksimal di STB
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Atur Hak Akses Folder (Permissions)
```bash
sudo chown -R www-data:www-data /var/www/isp-billing
sudo chmod -R 775 /var/www/isp-billing/storage
sudo chmod -R 775 /var/www/isp-billing/bootstrap/cache
```

---

## 🌐 Langkah 5: Konfigurasi Virtual Host Nginx

Buat file konfigurasi Nginx untuk aplikasi:

```bash
sudo nano /etc/nginx/sites-available/isp-billing
```

Tempelkan (*paste*) konfigurasi berikut:

```nginx
server {
    listen 80;
    server_name _; # Atau isi dengan domain / subdomain Anda
    root /var/www/isp-billing/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -sf /etc/nginx/sites-available/isp-billing /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

---

## 🧙 Langkah 5B: Menjalankan Web Setup Wizard (Instalasi Praktis via Browser)

Setelah Nginx dan PHP-FPM aktif, Anda dapat menyelesaikan konfigurasi awal melalui browser secara visual tanpa perlu banyak mengetik perintah manual:

Buka browser Anda dan akses:
👉 **`http://IP_STB_ANDA/install`** atau **`https://domainanda/install`**

Ikuti 5 langkah mudah berikut:

1. **Langkah 01 — Server & Izin Folder:**
   - Sistem akan memeriksa versi PHP, ekstensi (PDO, cURL, OpenSSL), dan izin tulis folder `storage` & `bootstrap/cache`.
   - Jika semua bertanda centang hijau, klik **Lanjut ke Konfigurasi Database →**.

2. **Langkah 02 — Konfigurasi Database & Environment:**
   - **Nama Aplikasi / Brand**: `NetSpeed Billing`
   - **URL Aplikasi (Domain)**:
     - Jika menggunakan Cloudflare Tunnel: **Wajib gunakan awalan `https://`**, contoh: `https://ryzprjkt.cloud`
     - Jika akses lokal: `http://192.168.1.xxx`
   - **Database Host**: `127.0.0.1` *(Port: `3306`)*
   - **Nama Database**: `isp_billing`
   - **Username Database**: `isp_user`
   - **Password Database**: `PasswordKuatISP123!`
   - Klik tombol **Simpan & Lanjut ke Migrasi →**.

3. **Langkah 03 — Migrasi Database & Struktur Tabel:**
   - Klik tombol hijau **⚡ Jalankan Migrasi Database** (atau tombol alternatif **🚀 Migrasi Langsung (Form)**).
   - Tunggu hingga proses migrasi selesai 100%, lalu klik **Lanjut ke Setup Admin →**.
   - > 💡 *Tips: Jika koneksi browser terputus atau muncul "Failed to fetch", cukup jalankan perintah di terminal STB:*
     > ```bash
     > cd /var/www/isp-billing
     > php artisan migrate --force
     > ```
     > *Setelah itu langsung buka `https://domainanda/install/step4` di browser.*

4. **Langkah 04 — Akun Pengelola & Profil Perusahaan ISP:**
   - Masukkan **Nama Lengkap**, **Email Login**, dan **Password Super Admin**.
   - Masukkan **Nama Brand ISP** dan **Nomor WhatsApp Dukungan Teknis**.
   - Centang opsi *Isi data contoh (paket internet, router demo, teknisi)* jika ingin mencoba data awal.
   - Klik **Selesaikan Instalasi & Kunci Sistem →**.

5. **Langkah 05 — Selesai!**
   - Instalasi selesai dan sistem terkunci aman.
   - Klik tombol **Buka Admin Panel (NetSpeed)** untuk langsung login ke dashboard pengelola di `/admin`!

---

## ⏰ Langkah 6: Pasang Otomatisasi Scheduler (Cron Job)

Laravel membutuhkan *Cron Job* setiap menit agar fitur otomatisasi berjalan di STB (cek jatuh tempo, isolir pelanggan otomatis, cek kuota FUP, sinkronisasi secret MikroTik):

```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut di paling bawah:
```cron
* * * * * php /var/www/isp-billing/artisan schedule:run >> /dev/null 2>&1
```

---

## ⚡ Langkah 7: Trik Optimasi Ringan STB (Hemat RAM)

Agar STB Anda berjalan dingin, stabil, dan tidak kehabisan memori meskipun dipakai berbulan-bulan:

### 1. Batasi Alokasi Memori MariaDB
Buka konfigurasi server MariaDB:
```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```
Cari blok `[mysqld]` dan tambahkan/sesuaikan:
```ini
[mysqld]
innodb_buffer_pool_size = 64M
max_connections = 50
key_buffer_size = 16M
```
Simpan, lalu restart:
```bash
sudo systemctl restart mariadb
```

### 2. Atur PHP-FPM ke Mode `ondemand`
Buka konfigurasi pool PHP-FPM:
```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```
Ubah pengaturan proses:
```ini
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
pm.max_requests = 200
```
Simpan, lalu restart:
```bash
sudo systemctl restart php8.3-fpm
```

---

## 🌍 Langkah 8: Mengakses Backend dari Internet (Aplikasi Flutter / Pelanggan)

Karena STB Anda berada di jaringan lokal rumah/kantor (IP Privat seperti `192.168.1.xxx` tanpa IP Publik Statis), cara termudah, paling aman, dan stabil agar aplikasi Android Flutter pelanggan dapat mengakses backend dari jaringan internet luar adalah menggunakan **Cloudflare Tunnel (`cloudflared`)** — **100% GRATIS, tanpa port forwarding router, dan otomatis HTTPS / SSL aktif**.

> [!IMPORTANT]
> **Perbedaan Utama: Web Dashboard (Opsi A) vs CLI Manual (Opsi B):**
> - **Opsi A (Cloudflare Web Dashboard — Sangat Direkomendasikan):** Tunnel dibuat langsung dari website Cloudflare. Anda bisa mengatur domain, subdomain (`api.domainanda.com`), dan port lokal (`localhost:80`) langsung dari web Cloudflare tanpa perlu edit file config apapun di STB.
> - **Opsi B (CLI Manual):** Jika tunnel dibuat lewat perintah terminal `cloudflared tunnel create`, Cloudflare menandainya sebagai *"Locally managed"*. Pada mode ini, Anda **tidak bisa** mengatur rute dari web Cloudflare, melainkan **wajib** membuat file konfigurasi `config.yml` dan memasukkan **Tunnel ID** secara manual di STB.

---

### Opsi A: Menggunakan Cloudflare Zero Trust Dashboard (Metode Token — Sangat Mudah & Praktis)

Metode ini tidak memerlukan pembuatan file `config.yml` secara manual karena seluruh konfigurasi Tunnel ID dan hostname dikelola otomatis dari dashboard Cloudflare:

1. **Install cloudflared di STB (ARM64):**
   ```bash
   curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb
   sudo dpkg -i cloudflared.deb
   ```

2. **Buat Tunnel di Dashboard Cloudflare:**
   - Buka dashboard [Cloudflare Zero Trust](https://one.dash.cloudflare.com/) > menu **Networks** > **Tunnels**.
   - Klik **Create a Tunnel** > Pilih **Cloudflared** > Beri nama tunnel (misal: `isp-stb-server`).
   - Pada bagian **Choose your environment**, pilih **Debian** dan arsitektur **arm64**.

3. **Salin & Jalankan Perintah Service Install:**
   Cloudflare akan menyediakan perintah 1 baris yang sudah berisi token Tunnel unik Anda:
   ```bash
   sudo cloudflared service install <TOKEN_DARI_CLOUDFLARE>
   ```
   *Perintah ini otomatis mengonfigurasi Tunnel ID dan mengaktifkan service systemd `cloudflared`.*

   > [!TIP]
   > **Jika Muncul Error "cloudflared service is already installed":**
   > Jika Anda sebelumnya pernah menginstall service cloudflared lama di STB dan muncul error:
   > `cloudflared service is already installed at /etc/systemd/system/cloudflared.service`
   > Cukup hapus service lama terlebih dahulu dengan perintah:
   > ```bash
   > sudo cloudflared service uninstall
   > ```
   > Lalu jalankan kembali perintah `sudo cloudflared service install <TOKEN_DARI_CLOUDFLARE>`.

4. **Tambahkan Public Hostname (Routing Domain):**
   - Pada tab **Public Hostname** di Cloudflare Dashboard, klik **Add a public hostname**.
   - **Subdomain**: `api` (atau `billing`)
   - **Domain**: pilih domain Anda yang sudah aktif di Cloudflare (misal: `ispanda.com`)
   - **Service Type**: `HTTP`
   - **URL**: `localhost:80` (atau `127.0.0.1:80`)
   - Klik **Save Tunnel**. Status tunnel akan langsung berubah menjadi **Healthy** hijau!

---

### Opsi B: Menggunakan CLI Manual (Konfigurasi File `config.yml` & Tunnel ID)

Jika Anda ingin mengelola file konfigurasi secara lokal di STB menggunakan CLI:

1. **Install cloudflared di STB (ARM64):**
   ```bash
   curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb
   sudo dpkg -i cloudflared.deb
   ```

2. **Login ke Akun Cloudflare:**
   ```bash
   cloudflared tunnel login
   ```
   *Buka link URL yang muncul di terminal pada browser untuk mengotorisasi domain Anda.*

3. **Buat Tunnel Baru:**
   ```bash
   cloudflared tunnel create isp-server
   ```
   Terminal akan menampilkan output seperti ini:
   ```text
   Tunnel credentials written to /root/.cloudflared/9b4a1c23-5678-4abc-9def-1234567890ab.json.
   Created tunnel isp-server with id 9b4a1c23-5678-4abc-9def-1234567890ab
   ```
   > 📌 **Catat Tunnel ID Anda:** contoh di atas adalah `9b4a1c23-5678-4abc-9def-1234567890ab`.

4. **Hubungkan Hostname Domain ke Tunnel (Routing DNS):**
   ```bash
   cloudflared tunnel route dns isp-server api.ispanda.com
   ```
   *(Ganti `api.ispanda.com` dengan subdomain & domain Anda).*

5. **Buat File Konfigurasi `config.yml`:**
   Buat dan edit file konfigurasi:
   ```bash
   nano ~/.cloudflared/config.yml
   ```
   Isi dengan script berikut (**ganti `<TUNNEL_ID_ANDA>` dengan ID yang didapat pada Langkah 3**):
   ```yaml
   tunnel: <TUNNEL_ID_ANDA>
   credentials-file: /root/.cloudflared/<TUNNEL_ID_ANDA>.json

   ingress:
     - hostname: api.ispanda.com
       service: http://localhost:80
     - service: http_status:404
   ```
   *Simpan dengan menekan `Ctrl+O`, lalu `Enter`, dan keluar dengan `Ctrl+X`.*

6. **Tes Koneksi Tunnel:**
   ```bash
   cloudflared tunnel run isp-server
   ```
   *Jika muncul log `Connection established`, tekan `Ctrl+C` untuk berhenti dan lanjut pasang service.*

7. **Pasang sebagai System Service (Otomatis Nyala Saat STB Hidup):**
   ```bash
   # Salin konfigurasi dan file credential ke folder system
   sudo mkdir -p /etc/cloudflared
   sudo cp ~/.cloudflared/config.yml /etc/cloudflared/
   sudo cp ~/.cloudflared/<TUNNEL_ID_ANDA>.json /etc/cloudflared/

   # Install dan aktifkan service systemd
   sudo cloudflared service install
   sudo systemctl daemon-reload
   sudo systemctl enable --now cloudflared
   ```

---

### 🔍 Verifikasi & Sambungkan ke Aplikasi Flutter

1. **Cek Status Service di STB:**
   ```bash
   sudo systemctl status cloudflared
   ```
   *Pastikan statusnya `active (running)`.*

2. **Uji di Browser / Smartphone:**
   Buka `https://api.ispanda.com` di browser HP Anda menggunakan jaringan kuota seluler (bukan Wi-Fi lokal). Halaman login NetSpeed harus terbuka dengan gembok hijau HTTPS yang aman!

3. **Sambungkan ke Aplikasi Mobile Flutter (`isp_customer_app`):**
   Di proyek Flutter Anda, cukup sesuaikan `baseUrl` API ke domain Cloudflare Tunnel ini:
   ```dart
   // File: lib/services/api_service.dart
   static const String baseUrl = 'https://api.ispanda.com/api';
   ```
   Sekarang seluruh pelanggan dan teknisi dapat mengakses tagihan, scan QRIS, dan cek status router dari mana saja melalui internet!

