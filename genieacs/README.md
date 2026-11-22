# GenieACS TR-069 Server Package
### Untuk NetSpeed ISP Billing & Core NOC

Folder ini berisi paket instalasi dan template provisioning untuk **GenieACS TR-069 Server**, yang digunakan untuk me-manage modem/ONT pelanggan (ZTE, Huawei, Fiberhome, VSOL) secara terpusat.

---

## 📁 Struktur Berkas

```
genieacs/
├── install.sh             # Script instalasi otomatis 1-klik (Armbian / Ubuntu / Debian)
├── docker-compose.yml     # Konfigurasi instalasi berbasis Docker & MongoDB 6.0
├── TUTORIAL-INSTALL.md    # Panduan lengkap instalasi, setting modem ONT, & integrasi ke NetSpeed
├── README.md              # Ringkasan berkas
└── provisions/            # Skrip TR-069 Provisioning bawaan
    ├── inform.js          # Skrip handler inform berkala, pembaca redaman RX/TX, & uptime
    ├── wifi-config.js     # Template pengaturan SSID & password Wi-Fi WPA2-PSK
    └── wan-pppoe.js       # Template pengaturan WAN PPPoE & VLAN ID otomatis
```

---

## 🚀 Cara Cepat Instalasi di Armbian / Ubuntu Server

Jalankan perintah ini di terminal server Anda:

```bash
cd /var/www/isp-billing/genieacs
chmod +x install.sh
sudo bash install.sh
```

Setelah instalasi selesai:
- **Web UI GenieACS**: `http://IP_SERVER:3000`
- **Port CWMP (ONT)**: `http://IP_SERVER:7547/`
- **Port NBI (API NetSpeed)**: `http://IP_SERVER:7557/`

Baca panduan lengkap langkah-demi-langkah, cara setting modem ZTE/Huawei, dan integrasi ke NetSpeed ISP Billing pada berkas **[TUTORIAL-INSTALL.md](TUTORIAL-INSTALL.md)**.
