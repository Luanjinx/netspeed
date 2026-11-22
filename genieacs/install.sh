#!/bin/bash
# ==============================================================================
# NETSPEED BROADBAND - GENIEACS AUTO INSTALLER SCRIPT
# Mendukung: Armbian (Amlogic/Rockchip/Allwinner), Ubuntu 20.04/22.04/24.04, Debian 11/12
# Arsitektur: ARM64 (aarch64), ARMv7 (armhf), x86_64 (amd64)
# ==============================================================================

set -e

# Warna Terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}====================================================================${NC}"
echo -e "${GREEN}      NETSPEED ISP BILLING - GENIEACS TR-069 AUTO INSTALLER         ${NC}"
echo -e "${CYAN}====================================================================${NC}"

# 1. Pastikan script dijalankan sebagai root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Script ini harus dijalankan sebagai user root!${NC}"
    echo "Silakan jalankan: sudo bash $0"
    exit 1
fi

# Deteksi OS dan Arsitektur
ARCH=$(uname -m)
echo -e "${BLUE}[INFO] Mendeteksi arsitektur CPU: ${ARCH}${NC}"

# 2. Update repository & Install dependency dasar
echo -e "${YELLOW}[1/7] Memperbarui paket sistem & menginstal dependensi dasar...${NC}"
apt-get update -y
apt-get install -y curl wget gnupg2 ca-certificates lsb-release build-essential git logrotate ufw

# 3. Instalasi MongoDB
echo -e "${YELLOW}[2/7] Memeriksa dan menginstal MongoDB Database...${NC}"
if ! command -v mongod &> /dev/null; then
    echo -e "${BLUE}[INFO] Menambahkan repository resmi MongoDB...${NC}"
    
    # Ambil codename distro (jammy, bookworm, focal, bullseye)
    DISTRO_CODENAME=$(lsb_release -cs 2>/dev/null || echo "jammy")
    
    # Import MongoDB GPG Key
    curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor --yes
    
    # Deteksi fallback repo jika distro berbasis Armbian / Debian
    if [ "$DISTRO_CODENAME" = "bookworm" ]; then
        echo "deb [ signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] http://repo.mongodb.org/apt/debian bookworm/mongodb-org/7.0 main" > /etc/apt/sources.list.d/mongodb-org-7.0.list
    elif [ "$DISTRO_CODENAME" = "bullseye" ]; then
        echo "deb [ signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] http://repo.mongodb.org/apt/debian bullseye/mongodb-org/7.0 main" > /etc/apt/sources.list.d/mongodb-org-7.0.list
    else
        echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse" > /etc/apt/sources.list.d/mongodb-org-7.0.list
    fi

    apt-get update -y || true
    apt-get install -y mongodb-org || apt-get install -y mongodb || true

    systemctl daemon-reload
    systemctl enable mongod || systemctl enable mongodb
    systemctl restart mongod || systemctl restart mongodb
else
    echo -e "${GREEN}[OK] MongoDB sudah terinstal di server.${NC}"
fi

# 4. Instalasi Node.js (v20 LTS)
echo -e "${YELLOW}[3/7] Menginstal Node.js v20 LTS & NPM...${NC}"
if ! command -v node &> /dev/null || [[ $(node -v | cut -d'v' -f2 | cut -d'.' -f1) -lt 18 ]]; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi
echo -e "${GREEN}[OK] Node.js terinstal: $(node -v) | NPM: $(npm -v)${NC}"

# 5. Instalasi GenieACS via NPM
echo -e "${YELLOW}[4/7] Menginstal GenieACS TR-069 Server (v1.2.13)...${NC}"
npm install -g --unsafe-perm genieacs@1.2.13

# 6. Konfigurasi Direktori & User GenieACS
echo -e "${YELLOW}[5/7] Mengonfigurasi direktori & user genieacs...${NC}"
if ! id "genieacs" &>/dev/null; then
    useradd --system --no-create-home --shell /bin/false genieacs
fi

mkdir -p /opt/genieacs
mkdir -p /opt/genieacs/ext
mkdir -p /var/log/genieacs

# Buat file environment konfigurasi
cat << 'EOF' > /opt/genieacs/genieacs.env
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

chown -R genieacs:genieacs /opt/genieacs
chown -R genieacs:genieacs /var/log/genieacs
chmod 600 /opt/genieacs/genieacs.env

# 7. Buat Systemd Services (CWMP, NBI, FS, UI)
echo -e "${YELLOW}[6/7] Membuat Systemd Services untuk GenieACS...${NC}"

# A. CWMP Service (Port 7547 - Komunikasi ONT Modem)
cat << 'EOF' > /etc/systemd/system/genieacs-cwmp.service
[Unit]
Description=GenieACS CWMP (TR-069 Server)
After=network.target mongod.service

[Service]
Type=simple
User=genieacs
EnvironmentFile=/opt/genieacs/genieacs.env
ExecStart=/usr/bin/genieacs-cwmp
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

# B. NBI Service (Port 7557 - REST API untuk NetSpeed ISP Billing)
cat << 'EOF' > /etc/systemd/system/genieacs-nbi.service
[Unit]
Description=GenieACS NBI (Northbound REST API)
After=network.target mongod.service

[Service]
Type=simple
User=genieacs
EnvironmentFile=/opt/genieacs/genieacs.env
ExecStart=/usr/bin/genieacs-nbi
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

# C. FS Service (Port 7567 - Firmware & Config File Server)
cat << 'EOF' > /etc/systemd/system/genieacs-fs.service
[Unit]
Description=GenieACS File Server
After=network.target mongod.service

[Service]
Type=simple
User=genieacs
EnvironmentFile=/opt/genieacs/genieacs.env
ExecStart=/usr/bin/genieacs-fs
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

# D. UI Service (Port 3000 - Web GUI Admin Dashboard)
cat << 'EOF' > /etc/systemd/system/genieacs-ui.service
[Unit]
Description=GenieACS Web GUI UI
After=network.target mongod.service

[Service]
Type=simple
User=genieacs
EnvironmentFile=/opt/genieacs/genieacs.env
ExecStart=/usr/bin/genieacs-ui
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

# Buat aturan logrotate otomatis agar disk tidak penuh
cat << 'EOF' > /etc/logrotate.d/genieacs
/var/log/genieacs/*.log /var/log/genieacs/*.yaml {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 genieacs genieacs
}
EOF

# 8. Start & Enable All Services
echo -e "${YELLOW}[7/7] Mengaktifkan dan menjalankan semua service GenieACS...${NC}"
systemctl daemon-reload
systemctl enable --now genieacs-cwmp
systemctl enable --now genieacs-nbi
systemctl enable --now genieacs-fs
systemctl enable --now genieacs-ui

# Ambil IP lokal server
SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}====================================================================${NC}"
echo -e "${GREEN}        INSTALASI GENIEACS BERHASIL DISELESAIKAN!                   ${NC}"
echo -e "${GREEN}====================================================================${NC}"
echo ""
echo -e "🔹 ${CYAN}GenieACS Web Admin UI :${NC} http://${SERVER_IP}:3000"
echo -e "🔹 ${CYAN}ACS URL untuk Modem   :${NC} http://${SERVER_IP}:7547/"
echo -e "🔹 ${CYAN}NBI API (NetSpeed ISP):${NC} http://${SERVER_IP}:7557/"
echo -e "🔹 ${CYAN}File Server (FS)      :${NC} http://${SERVER_IP}:7567/"
echo ""
echo -e "${YELLOW}Perintah Berguna:${NC}"
echo -e "• Cek status service : ${CYAN}systemctl status genieacs-cwmp genieacs-nbi genieacs-ui${NC}"
echo -e "• Restart service    : ${CYAN}systemctl restart genieacs-cwmp genieacs-nbi genieacs-ui${NC}"
echo -e "• Cek log error live : ${CYAN}journalctl -u genieacs-cwmp -f${NC}"
echo ""
echo -e "${GREEN}Silakan buka http://${SERVER_IP}:3000 di browser Anda.${NC}"
echo -e "${GREEN}====================================================================${NC}"
