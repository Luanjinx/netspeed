<div class="settings-billing-hub-container" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                    <span class="material-symbols-rounded" style="font-size: 17px;">payments</span>
                </span>
                Pusat Billing, QRIS Dinamis &amp; Subnet Jaringan
            </h4>
            <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">
                Manajemen barcode QRIS toko, nominal 3 digit unik, alokasi subnet IP PPPoE, dan profil redirect isolir
            </p>
        </div>
        <span style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
            <span class="material-symbols-rounded" style="font-size: 14px;">credit_card</span>
            3 Modul Billing
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <!-- 1. Billing & QRIS Merchant Scanner -->
        <a href="{{ url('/admin/qris-settings') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">qr_code_2</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka QRIS &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Billing &amp; QRIS Merchant Scanner</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Auto-scan foto QRIS toko, verifikasi EMVCo Bank Indonesia &amp; aktifkan 3 digit kode unik nominal tagihan</p>
            </div>
        </a>

        <!-- 2. Subnet IP PPPoE & Pool Isolir -->
        <div wire:click="mountAction('configureNetwork')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">lan</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur Subnet &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Subnet IP PPPoE &amp; Pool Isolir</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Gateway Local Address, pool dinamis &amp; DNS terisolasi anti-konflik Hotspot MikroTik</p>
            </div>
        </div>

        <!-- 3. Generator Redirect Isolir & Captive -->
        <a href="{{ url('/admin/isolir-manager') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Wizard &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Isolir &amp; Captive Portal Wizard</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Generator script redirect MikroTik, bypass server perbankan &amp; template halaman isolir</p>
            </div>
        </a>
    </div>
</div>

