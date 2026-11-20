<div class="settings-profile-hub-container" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                    <span class="material-symbols-rounded" style="font-size: 17px;">storefront</span>
                </span>
                Pusat Identitas Perusahaan &amp; Pemetaan NOC
            </h4>
            <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">
                Kelola nama brand yang tercetak di invoice tagihan, nomor CS WhatsApp, dan koordinat peta kantor pusat NOC
            </p>
        </div>
        <span style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
            <span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span>
            3 Modul Profil
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <!-- 1. Identitas Brand & Kontak CS -->
        <div wire:click="mountAction('configureCompany')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">badge</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur Profil &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Identitas Brand &amp; Kontak CS</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Nama brand ISP, nomor CS WhatsApp &amp; alamat kantor untuk cetak tagihan &amp; invoice</p>
            </div>
        </div>

        <!-- 2. Titik Koordinat Kantor Pusat NOC -->
        <div wire:click="mountAction('configureCoordinates')"
             class="settings-hub-card cursor-pointer"
             style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">location_on</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Atur Peta &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Titik Koordinat Kantor Pusat (NOC)</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Latitude &amp; Longitude kantor pusat untuk titik awal peta admin &amp; rute teknisi</p>
            </div>
        </div>

        <!-- 3. Halaman Lengkap Profil ISP -->
        <a href="{{ url('/admin/company-profile') }}"
           class="settings-hub-card"
           style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.15rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">domain</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #00e07e; border: 1px solid #232826;">Buka Halaman &rarr;</span>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Halaman Lengkap Profil Perusahaan</h4>
                <p style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Buka halaman mandiri untuk sinkronisasi template router MikroTik &amp; data kantor</p>
            </div>
        </a>
    </div>
</div>
