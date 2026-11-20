<x-filament-panels::page>
    <style>
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
        @media (min-width: 1024px) {
            .settings-grid-5 {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
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

        .settings-grid-2 {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 0.875rem !important;
        }
        @media (min-width: 640px) {
            .settings-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
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

    <!-- 1. PUSAT INTEGRASI GATEWAY & API EKSTERNAL -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.25rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">hub</span>
                    </span>
                    Pusat Integrasi Gateway &amp; API Eksternal
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Konfigurasi gateway WhatsApp, payment gateway, aplikasi mobile pelanggan, TR-069 GenieACS, dan Google Maps</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">bolt</span>
                5 Modul API
            </span>
        </div>

        <div class="settings-grid-5">
            <!-- 1. API WhatsApp Gateway -->
            <div wire:click="mountAction('configureWhatsApp')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">chat</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API WhatsApp Gateway</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Notifikasi tagihan, invoice PDF &amp; voucher via Fonnte/Wablas</p>
                </div>
            </div>

            <!-- 2. API Payment Gateway -->
            <div wire:click="mountAction('configurePayment')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">credit_card</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Payment Gateway</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tripay, Midtrans, CekMutasi, QRIS Otomatis &amp; Webhook pelunasan</p>
                </div>
            </div>

            <!-- 3. API Mobile Flutter & STB -->
            <div wire:click="mountAction('configureMobile')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">phone_android</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Mobile Flutter &amp; STB</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Endpoint backend, API key rahasia, Firebase FCM &amp; OneSignal push alert</p>
                </div>
            </div>

            <!-- 4. API TR-069 GenieACS -->
            <div wire:click="mountAction('configureGenieacs')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">router</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API TR-069 GenieACS</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">REST API NBI port 7557, token auth, cek redaman optik &amp; remote ONT</p>
                </div>
            </div>

            <!-- 5. API Peta & Geocoding -->
            <div wire:click="mountAction('configureMaps')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">map</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Peta &amp; Geocoding</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Google Maps JS API key &amp; koordinat kantor pusat untuk rute teknisi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BILLING, QRIS & IDENTITAS USAHA -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.25rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">payments</span>
                    </span>
                    Billing, QRIS &amp; Identitas Usaha ISP
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Manajemen barcode QRIS statis &amp; dinamis, kode unik tagihan, profil nama perusahaan &amp; koordinat NOC</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">verified</span>
                2 Halaman Khusus
            </span>
        </div>

        <div class="settings-grid-2">
            <!-- 1. Billing & QRIS Merchant -->
            <a href="{{ url('/admin/qris-settings') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">qr_code_2</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Billing &amp; QRIS Merchant</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Upload barcode QRIS, auto-decode EMVCo Bank Indonesia, nominal 3 digit unik &amp; preview QR</p>
                </div>
            </a>

            <!-- 2. Identitas ISP & Titik Peta -->
            <a href="{{ url('/admin/company-profile') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">business</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Identitas ISP &amp; Titik Peta</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Nama brand perusahaan, nomor CS, alamat kantor &amp; titik koordinat NOC untuk pusat peta</p>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. MANAJEMEN JARINGAN, BOT & OTOMASI ROUTER -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.25rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">router</span>
                    </span>
                    Manajemen Jaringan, Bot &amp; Otomasi Router
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Konsol bot teknis, push broadcast notifikasi, wizard generator isolir, dan isolasi subnet PPPoE</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">wifi</span>
                4 Modul Jaringan
            </span>
        </div>

        <div class="settings-grid-4">
            <!-- 1. Bot Telegram NOC -->
            <a href="{{ url('/admin/telegram-bot') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">smart_toy</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Konsol &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Bot Telegram NOC</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Uji webhook live, alert router offline, notifikasi tiket &amp; broadcast operasional</p>
                </div>
            </a>

            <!-- 2. Push Notifikasi Massal -->
            <a href="{{ url('/admin/onesignal') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">campaign</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Broadcast &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Push Notifikasi Massal</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Kirim pesan push broadcast massal ke aplikasi Android &amp; STB pelanggan</p>
                </div>
            </a>

            <!-- 3. Redirect Isolir & Captive Portal -->
            <a href="{{ url('/admin/isolir-manager') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Wizard &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Isolir &amp; Captive Portal</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Generator script redirect isolir MikroTik, bypass server bank &amp; profil isolir</p>
                </div>
            </a>

            <!-- 4. Subnet & IP Pool PPPoE -->
            <div wire:click="mountAction('configureNetwork')" class="settings-hub-card cursor-pointer" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; user-select: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">lan</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur Subnet &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Subnet &amp; IP Pool PPPoE</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Gateway Local Address, pool dinamis &amp; DNS terisolasi anti-konflik Hotspot</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. DATABASE & PEMELIHARAAN SISTEM -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.25rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">terminal</span>
                    </span>
                    Database &amp; Pemeliharaan Sistem
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Tabel mentah key-value konfigurasi dan sinkronisasi pembaruan kode dari GitHub</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">build</span>
                2 Modul Sistem
            </span>
        </div>

        <div class="settings-grid-2">
            <!-- 1. Raw DB Settings Table -->
            <a href="{{ url('/admin/isp-settings') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">database</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Tabel DB &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Database Settings</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tabel mentah key-value konfigurasi sistem untuk audit &amp; inspeksi database</p>
                </div>
            </a>

            <!-- 2. Pembaruan Sistem (GitHub) -->
            <a href="{{ url('/admin/system-update') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">system_update</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Update &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Pembaruan Sistem</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Cek riwayat commit git log &amp; tarik pembaruan fitur langsung dari GitHub</p>
                </div>
            </a>
        </div>
    </div>
</x-filament-panels::page>
