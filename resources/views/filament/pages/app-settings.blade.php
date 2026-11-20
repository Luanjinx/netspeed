<x-filament-panels::page>
    <style>
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

        .settings-grid-6 {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.875rem !important;
        }
        @media (min-width: 640px) {
            .settings-grid-6 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 768px) {
            .settings-grid-6 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1200px) {
            .settings-grid-6 {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
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

    <!-- 1. PUSAT ALAT & KONSOL TERPADU SISTEM (4 MODUL SISTEM) -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.25rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">dashboard_customize</span>
                    </span>
                    Pusat Alat &amp; Konsol Terpadu Sistem
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Akses cepat konsol push notifikasi, generator isolir, inspeksi database, dan pembaruan sistem</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span>
                4 Modul Sistem
            </span>
        </div>

        <div class="settings-grid-4">
            <!-- 1. OneSignal Broadcast -->
            <a href="{{ url('/admin/onesignal') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">campaign</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Broadcast &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Push Notifikasi</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Kirim pesan push OneSignal ke user</p>
                </div>
            </a>

            <!-- 2. Isolir & Captive Wizard -->
            <a href="{{ url('/admin/isolir-manager') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Wizard &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Isolir &amp; Captive</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Generator script redirect MikroTik</p>
                </div>
            </a>

            <!-- 3. Raw DB Settings Table -->
            <a href="{{ url('/admin/isp-settings') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">database</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Tabel DB &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Database Settings</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tabel mentah key-value konfigurasi</p>
                </div>
            </a>

            <!-- 4. Pembaruan Sistem (GitHub) -->
            <a href="{{ url('/admin/system-update') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">system_update</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Update &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Pembaruan Sistem</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tarik pembaruan fitur langsung dari GitHub</p>
                </div>
            </a>
        </div>
    </div>

    <!-- 2. PUSAT INTEGRASI API & LAYANAN EKSTERNAL (6 MODUL API - STYLE SAMA DENGAN ATAS) -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.5rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">hub</span>
                    </span>
                    Pusat Integrasi API &amp; Layanan Eksternal
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Konfigurasi gateway pesan WhatsApp, Bot Telegram NOC, pembayaran online, aplikasi pelanggan, TR-069 &amp; peta</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">bolt</span>
                6 Modul API
            </span>
        </div>

        <div class="settings-grid-6">
            <!-- 1. API WhatsApp Gateway (Buka Halaman / Tanpa Popup) -->
            <a href="{{ url('/admin/whatsapp-gateway') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">chat</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API WhatsApp Gateway</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Notifikasi tagihan, invoice &amp; voucher live via Fonnte/Wablas</p>
                </div>
            </a>

            <!-- 2. Bot Telegram NOC (Buka Halaman / Tanpa Popup) -->
            <a href="{{ url('/admin/telegram-bot') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">smart_toy</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Bot Telegram NOC</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Token HTTP API, chat ID, webhook live &amp; bot operasional</p>
                </div>
            </a>

            <!-- 3. API Payment Gateway (Buka Halaman / Tanpa Popup) -->
            <a href="{{ url('/admin/payment-gateway') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">credit_card</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Payment Gateway</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tripay, Midtrans, CekMutasi, QRIS Otomatis &amp; webhook</p>
                </div>
            </a>

            <!-- 4. API Mobile Flutter & STB (Buka Halaman / Tanpa Popup) -->
            <a href="{{ url('/admin/mobile-api') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">phone_android</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Mobile Flutter &amp; STB</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Endpoint backend, API key rahasia, Firebase FCM &amp; OneSignal</p>
                </div>
            </a>

            <!-- 5. API TR-069 GenieACS -->
            <div wire:click="mountAction('configureGenieacs')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">router</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API TR-069 GenieACS</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">REST API NBI port 7557, token auth &amp; remote ONT</p>
                </div>
            </div>

            <!-- 6. API Peta & Geocoding (Buka Halaman / Tanpa Popup) -->
            <a href="{{ url('/admin/map-settings') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">map</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Peta &amp; Geocoding</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Google Maps JS API key, koordinat NOC &amp; rute teknisi</p>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. TABS PENGATURAN LAINNYA (PROFIL ISP & BILLING/ISOLIR) -->
    <div>
        {{ $this->form }}
    </div>
</x-filament-panels::page>
