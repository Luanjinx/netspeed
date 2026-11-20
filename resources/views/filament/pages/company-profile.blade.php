<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <!-- Card Aksi Simpan (Posisi Kanan) -->
        <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.15rem 1.35rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.5rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #22c55e; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">save</span>
                </div>
                <div>
                    <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0;">Simpan Pengaturan General</h4>
                    <p style="font-size: 0.75rem; color: #9aa49f; margin: 3px 0 0 0;">Terapkan nama brand ISP, nomor CS WhatsApp &amp; titik koordinat kantor pusat NOC</p>
                </div>
            </div>

            <button type="submit"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0.7rem 2rem; font-size: 0.875rem; font-weight: 700; color: #ffffff !important; background: #16a34a; border: 1px solid #22c55e; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);"
                    onmouseover="this.style.background='#15803d'; this.style.borderColor='#16a34a'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.background='#16a34a'; this.style.borderColor='#22c55e'; this.style.transform='none';">
                <span class="material-symbols-rounded" style="font-size: 20px; color: #ffffff !important; line-height: 1;">check</span>
                <span style="color: #ffffff !important; font-weight: 700; font-size: 0.95rem; letter-spacing: 0.02em;">Simpan</span>
            </button>
        </div>
    </form>
</x-filament-panels::page>

