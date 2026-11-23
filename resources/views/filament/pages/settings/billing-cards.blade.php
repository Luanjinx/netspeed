<div class="settings-billing-hub-container" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                    <span class="material-symbols-rounded" style="font-size: 17px;">payments</span>
                </span>
                Pusat Billing, QRIS Dinamis &amp; Subnet Jaringan
            </h4>
            <p style="font-size: 0.775rem; color: var(--muted); margin: 4px 0 0 0;">
                Manajemen barcode QRIS toko, nominal 3 digit unik, alokasi subnet IP PPPoE, dan profil redirect isolir
            </p>
        </div>
        <span style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: var(--surface2); color: #00e07e; border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 5px;">
            <span class="material-symbols-rounded" style="font-size: 14px;">credit_card</span>
            3 Modul Billing
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <!-- 1. Payment Gateway & QRIS Dinamis -->
        <a href="{{ url('/admin/payment-gateway') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">qr_code_2</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: var(--surface); color: #00e07e; border: 1px solid var(--border);">Buka Gateway &amp; QRIS &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0.75rem 0 0.25rem 0;">Payment Gateway &amp; QRIS Dinamis</h4>
                <p style="font-size: 0.75rem; color: var(--muted); margin: 0; line-height: 1.35;">Auto-scan foto QRIS toko 0% MDR, Tripay, Midtrans, CekMutasi, 3 digit kode unik &amp; webhook</p>
            </div>
        </a>

        <!-- 2. Subnet IP PPPoE & Pool Isolir -->
        <div wire:click="mountAction('configureNetwork')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">lan</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: var(--surface); color: #00e07e; border: 1px solid var(--border);">Atur Subnet &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0.75rem 0 0.25rem 0;">Subnet IP PPPoE &amp; Pool Isolir</h4>
                <p style="font-size: 0.75rem; color: var(--muted); margin: 0; line-height: 1.35;">Gateway Local Address, pool dinamis &amp; DNS terisolasi anti-konflik Hotspot MikroTik</p>
            </div>
        </div>

        <!-- 3. Integrasi Captive Portal & Isolir MikroTik -->
        <a href="{{ url('/admin/isolir-manager') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: var(--surface); color: #00e07e; border: 1px solid var(--border);">Buka Wizard &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0.75rem 0 0.25rem 0;">Captive Portal &amp; Isolir MikroTik</h4>
                <p style="font-size: 0.75rem; color: var(--muted); margin: 0; line-height: 1.35;">Halaman peringatan jatuh tempo interaktif dengan tombol bayar QRIS langsung</p>
            </div>
        </a>
    </div>
</div>
