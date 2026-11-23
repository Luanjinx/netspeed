<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <!-- Card Aksi Simpan (Posisi Kanan) -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.15rem 1.35rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">save</span>
                </div>
                <div>
                    <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0;">Simpan Pengaturan General</h4>
                    <p style="font-size: 0.75rem; color: var(--muted); margin: 3px 0 0 0;">Terapkan nama brand ISP, nomor CS WhatsApp &amp; titik koordinat kantor pusat NOC</p>
                </div>
            </div>

            <button type="submit"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0.7rem 2rem; font-size: 0.875rem; font-weight: 800; color: #ffffff !important; background: #00e07e; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(0, 224, 126, 0.25);"
                    onmouseover="this.style.background='#4deda4'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.background='#00e07e'; this.style.transform='none';">
                <span class="material-symbols-rounded" style="font-size: 20px; color: #ffffff !important; line-height: 1;">check</span>
                <span style="color: #ffffff !important; font-weight: 800; font-size: 0.95rem; letter-spacing: 0.02em;">Simpan</span>
            </button>
        </div>
    </form>
</x-filament-panels::page>

