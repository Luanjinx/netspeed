# Panduan Lengkap Instalasi & Integrasi GenieACS (TR-069)
### NetSpeed ISP Billing & Core NOC

Tutorial ini memandu Anda melakukan instalasi server **GenieACS (TR-069 Auto Configuration Server)** untuk memonitor, mengonfigurasi Wi-Fi, membaca redaman optik (RX/TX dBm), dan me-reboot modem ONT pelanggan (ZTE, Huawei, Fiberhome, VSOL, dll.) secara terpusat dari aplikasi **NetSpeed ISP Billing**.

---

## Daftar Isi
1. [Arsitektur & Port Penting](#1-arsitektur--port-penting)
2. [Kebutuhan Sistem (Hardware & OS)](#2-kebutuhan-sistem-hardware--os)
3. [Metode 1: Instalasi Otomatis (Script 1-Klik - Disarankan)](#3-metode-1-instalasi-otomatis-script-1-klik---disarankan)
4. [Metode 2: Instalasi Manual Langkah-demi-Langkah](#4-metode-2-instalasi-manual-langkah-demi-langkah)
5. [Metode 3: Instalasi menggunakan Docker Compose](#5-metode-3-instalasi-menggunakan-docker-compose)
6. [Pengaturan Modem ONT Pelanggan ke GenieACS](#6-pengaturan-modem-ont-pelanggan-ke-genieacs)
7. [Menghubungkan GenieACS ke NetSpeed ISP Billing](#7-menghubungkan-genieacs-ke-netspeed-isp-billing)
8. [Perintah Operasional & Troubleshooting](#8-perintah-operasional--troubleshooting)

---

## 1. Arsitektur & Port Penting

GenieACS terdiri dari 4 service yang bekerja bersama database MongoDB:

| Service | Port | Fungsi |
| :--- | :--- | :--- |
| **`genieacs-cwmp`** | **`7547`** | Port TR-069 tempat modem/ONT pelanggan mengirimkan sinyal Inform & perintah |
| **`genieacs-nbi`** | **`7557`** | REST API Northbound yang dihubungkan ke NetSpeed ISP Billing |
| **`genieacs-fs`** | **`7567`** | File Server untuk backup konfigurasi dan upgrade firmware ONT |
| **`genieacs-ui`** | **`3000`** | Web Dashboard Admin GUI GenieACS |
| **`mongod`** | **`27017`** | Database penyimpanan data perangkat & parameter TR-069 |

---

## 2. Kebutuhan Sistem (Hardware & OS)

* **Sistem Operasi**: 
  - **Armbian** (STB B860H, HG680P, X96 Max, Raspberry Pi, dll.)
  - **Ubuntu Server** 20.04 / 22.04 / 24.04 LTS
  - **Debian** 11 / 12
* **Arsitektur CPU**: ARM64 (`aarch64`), ARMv7 (`armhf`), atau x86_64 (`amd64`).
* **RAM Minimum**: 1 GB (Rekomendasi 2 GB untuk kelancaran hingga ribuan ONT).
* **Penyimpanan**: Minimal 8 GB free storage.

---

## 3. Metode 1: Instalasi Otomatis (Script 1-Klik - Disarankan)

Kami telah menyediakan script instalasi otomatis `install.sh` yang otomatis memasang MongoDB, Node.js v20 LTS, GenieACS v1.2.13, membuat user sistem, dan mengaktifkan seluruh service `systemd`.

### Langkah-langkah:
1. Buka terminal SSH server / STB Armbian Anda sebagai `root`.
2. Masuk ke folder `genieacs` atau salin file `install.sh` ke server Anda:
   ```bash
   cd /var/www/isp-billing/genieacs
   # Berikan izin eksekusi
   chmod +x install.sh
   # Jalankan installer
   sudo bash install.sh
   ```
3. Tunggu proses instalasi otomatis selama 2–4 menit hingga muncul tulisan hijau:
   `INSTALASI GENIEACS BERHASIL DISELESAIKAN!`
4. Server GenieACS Anda sudah langsung aktif dan berjalan otomatis saat server booting!

---

## 4. Metode 2: Instalasi Manual Langkah-demi-Langkah

Jika Anda ingin melakukan instalasi secara bertahap:

### Langkah 1: Update Sistem & Pasang Dependensi
```bash
sudo apt-get update && sudo apt-get install -y curl wget gnupg2 ca-certificates lsb-release build-essential git
```

### Langkah 2: Pasang Database MongoDB
```bash
# Tambahkan repo resmi MongoDB (Ubuntu 22.04 contoh)
curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | sudo gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor --yes
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-7.0.list

sudo apt-get update
sudo apt-get install -y mongodb-org || sudo apt-get install -y mongodb

sudo systemctl enable --now mongod || sudo systemctl enable --now mongodb
```

### Langkah 3: Pasang Node.js v20 LTS
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Langkah 4: Pasang GenieACS v1.2.13
```bash
sudo npm install -g --unsafe-perm genieacs@1.2.13
```

### Langkah 5: Buat User & File Konfigurasi
```bash
sudo useradd --system --no-create-home --shell /bin/false genieacs
sudo mkdir -p /opt/genieacs /opt/genieacs/ext /var/log/genieacs

sudo tee /opt/genieacs/genieacs.env > /dev/null << 'EOF'
GENIEACS_CWMP_ACCESS_LOG_FILE=/var/log/genieacs/genieacs-cwmp-access.log
GENIEACS_NBI_ACCESS_LOG_FILE=/var/log/genieacs/genieacs-nbi-access.log
GENIEACS_FS_ACCESS_LOG_FILE=/var/log/genieacs/genieacs-fs-access.log
GENIEACS_UI_ACCESS_LOG_FILE=/var/log/genieacs/genieacs-ui-access.log
GENIEACS_DEBUG_FILE=/var/log/genieacs/genieacs-debug.yaml
GENIEACS_MONGODB_CONNECTION_URL=mongodb://127.0.0.1/genieacs
GENIEACS_CWMP_PORT=7547
GENIEACS_NBI_PORT=7557
GENIEACS_FS_PORT=7567
GENIEACS_UI_PORT=3000
GENIEACS_UI_HOST=0.0.0.0
GENIEACS_EXT_DIR=/opt/genieacs/ext
EOF

sudo chown -R genieacs:genieacs /opt/genieacs /var/log/genieacs
sudo chmod 600 /opt/genieacs/genieacs.env
```

### Langkah 6: Pasang Systemd Services
Buat service untuk ke-4 komponen GenieACS:

```bash
for service in cwmp nbi fs ui; do
sudo tee /etc/systemd/system/genieacs-${service}.service > /dev/null << EOF
[Unit]
Description=GenieACS ${service}
After=network.target mongod.service

[Service]
Type=simple
User=genieacs
EnvironmentFile=/opt/genieacs/genieacs.env
ExecStart=/usr/bin/genieacs-${service}
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
done

# Reload dan aktifkan seluruh service
sudo systemctl daemon-reload
sudo systemctl enable --now genieacs-cwmp genieacs-nbi genieacs-fs genieacs-ui
```

---

## 5. Metode 3: Instalasi menggunakan Docker Compose

Jika server Anda telah terpasang Docker dan Docker Compose:

1. Masuk ke folder `genieacs`:
   ```bash
   cd /var/www/isp-billing/genieacs
   ```
2. Jalankan container:
   ```bash
   docker compose up -d
   ```
3. Cek container yang berjalan:
   ```bash
   docker compose ps
   ```

---

## 6. Pengaturan Modem ONT Pelanggan ke GenieACS

Agar modem ONT pelanggan dapat terbaca di GenieACS, lakukan pengaturan TR-069 pada web interface modem (IP `192.168.1.1`):

### A. Untuk Modem ZTE (F609 / F660 / F670)
1. Buka menu **Administration** atau **Network** > **TR-069**.
2. Centang **Enable TR-069**.
3. Isi parameter berikut:
   - **ACS URL**: `http://IP_SERVER_GENIEACS:7547/` *(contoh: `http://192.168.2.1:7547/`)*
   - **ACS User Name**: *(kosongkan jika tanpa auth)*
   - **ACS Password**: *(kosongkan jika tanpa auth)*
   - **Periodic Inform Enable**: Centang **Enable**
   - **Periodic Inform Interval**: `300` detik (5 menit)
   - **Connection Request User Name**: `admin`
   - **Connection Request Password**: `admin`
4. Klik **Apply / Save**.

### B. Untuk Modem Huawei (HG8245H / HG8245A / EG8145V5)
1. Buka menu **System Tools** > **TR-069**.
2. Centang **Enable Periodic Inform**.
3. **Periodic Inform Interval**: `300`
4. **ACS URL**: `http://IP_SERVER_GENIEACS:7547/`
5. Klik **Apply**.

> Dalam 1–2 menit, modem akan muncul di Web UI GenieACS (`http://IP_SERVER:3000`).

---

## 7. Menghubungkan GenieACS ke NetSpeed ISP Billing

Setelah GenieACS aktif:

1. Buka Admin Panel NetSpeed ISP Billing di browser:
   👉 Masuk ke menu **Manajemen Jaringan** > **Server GenieACS (ONT)** (`/admin/genieacs-servers`).
2. Klik tombol **Tambah Server GenieACS**:
   - **Nama Server**: `GenieACS Pusat NOC`
   - **Base URL NBI (API)**: `http://127.0.0.1:7557` *(atau gunakan IP server jika beda mesin, misal `http://192.168.2.1:7557`)*
   - **Port CWMP**: `7547`
   - **Status**: `Aktif`
3. Klik tombol **"Test Koneksi"** pada baris server:
   - Jika berhasil, akan muncul notifikasi hijau: `Terhubung sukses ke GenieACS TR-069 Server`.

### Menghubungkan Modem ke Data Pelanggan:
1. Buka menu **Data Pelanggan** > Pilih pelanggan > Klik **Edit**.
2. Gulir ke bagian **"Modem ONT & ODP Fiber"**:
   - Pilih **Server GenieACS**: `GenieACS Pusat NOC`
   - Isi **Device ID / GPON SN**: Masukkan serial number modem (contoh: `ZTEGC1234567` atau Device ID lengkap dari GenieACS).
3. Klik **Simpan**.

### Fitur yang Langsung Dapat Digunakan:
- **Cek Redaman Optik (Live)**: Klik tombol **"Diagnosa ONT"** di halaman edit pelanggan untuk melihat nilai redaman RX/TX Power (dBm) secara instan.
- **Ubah Wi-Fi Jarak Jauh**: Klik tombol **"Ganti Wi-Fi"** untuk mengganti nama SSID dan kata sandi tanpa perlu mendatangi rumah pelanggan.
- **Reboot Modem Jarak Jauh**: Klik tombol **"Reboot Modem"** untuk merestart modem pelanggan saat ada gangguan.

---

## 8. Perintah Operasional & Troubleshooting

| Kebutuhan | Perintah Terminal |
| :--- | :--- |
| **Cek status semua service** | `sudo systemctl status genieacs-cwmp genieacs-nbi genieacs-ui` |
| **Restart semua service** | `sudo systemctl restart genieacs-cwmp genieacs-nbi genieacs-ui` |
| **Melihat log koneksi modem (Live)** | `sudo journalctl -u genieacs-cwmp -f` |
| **Melihat log REST API NetSpeed** | `sudo journalctl -u genieacs-nbi -f` |
| **Buka firewall jika port terblokir** | `sudo ufw allow 7547/tcp && sudo ufw allow 7557/tcp && sudo ufw allow 3000/tcp` |
