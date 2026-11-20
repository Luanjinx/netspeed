<div class="settings-api-hub-container" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                    <span class="material-symbols-rounded" style="font-size: 17px;">hub</span>
                </span>
                Pusat Integrasi API &amp; Layanan Eksternal
            </h4>
            <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">
                Klik kartu integrasi untuk membuka popup dialog konfigurasi API secara cepat dan praktis
            </p>
        </div>
        <span style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
            <span class="material-symbols-rounded" style="font-size: 14px;">bolt</span>
            6 API Terpadu
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <!-- 1. API WhatsApp Gateway -->
        <a href="{{ url('/admin/whatsapp-gateway') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">chat</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API WhatsApp Gateway</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Notifikasi tagihan, invoice PDF &amp; voucher via WhatsApp Fonnte/Wablas</p>
            </div>
        </a>

        <!-- 2. API Bot Telegram NOC -->
        <div wire:click="mountAction('configureTelegram')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">smart_toy</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Bot Telegram NOC</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Token bot HTTP API &amp; Chat ID alert router offline, tiket &amp; broadcast NOC</p>
            </div>
        </div>

        <!-- 3. API Payment Gateway -->
        <div wire:click="mountAction('configurePayment')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">credit_card</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Payment Gateway</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tripay, Midtrans, CekMutasi, QRIS Otomatis &amp; webhook pelunasan realtime</p>
            </div>
        </div>

        <!-- 4. API Mobile Flutter & STB -->
        <div wire:click="mountAction('configureMobile')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">phone_android</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Mobile Flutter &amp; STB</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Endpoint backend, API key rahasia, Firebase FCM &amp; OneSignal broadcast</p>
            </div>
        </div>

        <!-- 5. API TR-069 GenieACS -->
        <div wire:click="mountAction('configureGenieacs')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">router</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API TR-069 GenieACS</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">REST API NBI port 7557, token auth, monitoring redaman &amp; remote ONT</p>
            </div>
        </div>

        <!-- 6. API Peta & Geocoding -->
        <div wire:click="mountAction('configureMaps')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">map</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur API &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">API Peta &amp; Geocoding</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Google Maps JS API key &amp; koordinat kantor untuk routing teknisi di peta</p>
            </div>
        </div>
    </div>
</div>

