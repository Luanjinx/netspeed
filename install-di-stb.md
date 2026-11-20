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

Jalankan perintah SQL berikut untuk membuat database dan user:
```sql
CREATE DATABASE isp_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'isp_user'@'localhost' IDENTIFIED BY 'PasswordKuatISP123!';
GRANT ALL PRIVILEGES ON isp_billing.* TO 'isp_user'@'localhost';
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

Karena STB Anda biasanya berada di jaringan lokal (IP Privat seperti `192.168.1.xxx` tanpa IP Publik Statis), cara termudah dan paling aman agar aplikasi Android Flutter pelanggan dapat mengakses backend dari luar rumah adalah menggunakan **Cloudflare Tunnel (`cloudflared`)** — **100% GRATIS dan sudah HTTPS otomatis**:

1. **Install cloudflared di STB (Arsitektur ARM64):**
   ```bash
   curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb
   sudo dpkg -i cloudflared.deb
   ```
2. **Login & Hubungkan Tunnel:**
   ```bash
   cloudflared tunnel login
   cloudflared tunnel create isp-server
   ```
3. Arahkan hostname domain Anda (misal `api.ispanda.com`) ke `http://localhost:80`.
4. Jalankan sebagai system service agar otomatis nyala saat STB hidup:
   ```bash
   sudo cloudflared service install
   sudo systemctl start cloudflared
   ```

Sekarang backend di STB Anda dapat diakses secara publik dengan aman di `https://api.ispanda.com` dan siap dihubungkan ke `isp_customer_app`!

