<x-filament-panels::page>
    <style>
        .settings-grid-3 {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.875rem !important;
        }
        @media (min-width: 640px) {
            .settings-grid-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1024px) {
            .settings-grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        .settings-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.875rem !important;
        }
        @media (min-width: 640px) {
            .settings-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1024px) {
            .settings-grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }

        .settings-grid-5 {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.875rem !important;
        }
        @media (min-width: 640px) {
            .settings-grid-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 768px) {
            .settings-grid-5 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1200px) {
            .settings-grid-5 {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }
        }

        .settings-hub-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-decoration: none !important;
        }
        .settings-hub-card:hover {
            border-color: rgba(0, 224, 126, 0.45) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.6), 0 0 14px rgba(0, 224, 126, 0.12);
        }
    </style>

    <!-- 1. PUSAT ALAT & KONSOL TERPADU SISTEM (3 MODUL SISTEM) -->
    <div class="settings-hub-wrapper">
        <div class="settings-hub-header">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">dashboard_customize</span>
                    </span>
                    Pusat Alat &amp; Konsol Terpadu Sistem
                </h3>
                <p style="font-size: 0.775rem; color: var(--muted); margin: 4px 0 0 0;">Akses cepat identitas ISP, generator isolir, dan pembaruan sistem</p>
            </div>
            <span class="settings-hub-badge" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span>
                3 Modul Sistem
            </span>
        </div>

        <div class="settings-grid-3">
            <!-- 1. General & Profil ISP -->
            <a href="{{ url('/admin/company-profile') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">domain</span>
                    </div>
                    <span class="settings-hub-badge">Atur Profil &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">General &amp; Profil ISP</h4>
                    <p class="settings-hub-desc">Nama brand ISP, kontak layanan CS &amp; titik koordinat kantor pusat NOC</p>
                </div>
            </a>

            <!-- 2. Isolir & Captive Wizard -->
            <a href="{{ url('/admin/isolir-manager') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                    </div>
                    <span class="settings-hub-badge">Wizard &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">Isolir &amp; Captive</h4>
                    <p class="settings-hub-desc">Generator script redirect MikroTik</p>
                </div>
            </a>

            <!-- 3. Pembaruan Sistem (GitHub) -->
            <a href="{{ url('/admin/system-update') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">system_update</span>
                    </div>
                    <span class="settings-hub-badge">Update &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">Pembaruan Sistem</h4>
                    <p class="settings-hub-desc">Tarik pembaruan fitur langsung dari GitHub</p>
                </div>
            </a>
        </div>
    </div>

    <!-- 2. PUSAT INTEGRASI API & LAYANAN EKSTERNAL (5 MODUL API) -->
    <div class="settings-hub-wrapper">
        <div class="settings-hub-header">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">hub</span>
                    </span>
                    Pusat Integrasi API &amp; Layanan Eksternal
                </h3>
                <p style="font-size: 0.775rem; color: var(--muted); margin: 4px 0 0 0;">Konfigurasi gateway pesan WhatsApp, Bot Telegram NOC, pembayaran online, aplikasi pelanggan &amp; peta</p>
            </div>
            <span class="settings-hub-badge" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">bolt</span>
                5 Modul API
            </span>
        </div>

        <div class="settings-grid-5">
            <!-- 1. API WhatsApp Gateway -->
            <a href="{{ url('/admin/whatsapp-gateway') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">chat</span>
                    </div>
                    <span class="settings-hub-badge">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">API WhatsApp Gateway</h4>
                    <p class="settings-hub-desc">Notifikasi tagihan, invoice &amp; voucher live via Fonnte/Wablas</p>
                </div>
            </a>

            <!-- 2. Bot Telegram NOC -->
            <a href="{{ url('/admin/telegram-bot') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">smart_toy</span>
                    </div>
                    <span class="settings-hub-badge">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">Bot Telegram NOC</h4>
                    <p class="settings-hub-desc">Token HTTP API, chat ID, webhook live &amp; bot operasional</p>
                </div>
            </a>

            <!-- 3. API Payment Gateway & QRIS Dinamis -->
            <a href="{{ url('/admin/payment-gateway') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">credit_card</span>
                    </div>
                    <span class="settings-hub-badge">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">API Payment Gateway &amp; QRIS</h4>
                    <p class="settings-hub-desc">QRIS Dinamis 0% MDR, auto-scanner, Tripay, Midtrans &amp; webhook</p>
                </div>
            </a>

            <!-- 4. API Mobile Flutter & STB -->
            <a href="{{ url('/admin/mobile-api') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">phone_android</span>
                    </div>
                    <span class="settings-hub-badge">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">API Mobile Flutter &amp; STB</h4>
                    <p class="settings-hub-desc">Endpoint backend, API key rahasia, Firebase FCM &amp; OneSignal</p>
                </div>
            </a>

            <!-- 5. API Peta & Geocoding -->
            <a href="{{ url('/admin/map-settings') }}" class="settings-hub-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box">
                        <span class="material-symbols-rounded" style="font-size: 22px;">map</span>
                    </div>
                    <span class="settings-hub-badge">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title">API Peta &amp; Geocoding</h4>
                    <p class="settings-hub-desc">Google Maps JS API key, koordinat NOC &amp; rute teknisi</p>
                </div>
            </a>
        </div>
    </div>

</x-filament-panels::page>
