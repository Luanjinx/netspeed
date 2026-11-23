<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Status Koneksi Gateway -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Status Gateway WhatsApp
                        </div>
                        <div style="font-size: 1.2rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($isConfigured)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">Terkonfigurasi &amp; Siap</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 10px #ef4444;"></span>
                                <span style="color: #ef4444;">Belum Ada Token API</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">chat</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.8125rem; color: #9aa49f;">
                    @if ($isConfigured)
                        <span>Kredensial API telah tersimpan dan siap mengirim pesan otomatis</span>
                    @else
                        <span style="color: #f87171;">Silakan masukkan API Key / Token pada formulir di bawah</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Server Host Endpoint -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Server Host Gateway
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0;">
                            <span style="color: #38bdf8;">{{ $endpointHost }}</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">dns</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    Target API: <code style="color: #a7f3d0;">{{ $data['whatsapp_gateway_url'] ?? 'https://api.fonnte.com/send' }}</code>
                </div>
            </div>

            <!-- Card 3: Nomor Pengirim Aktif -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Nomor Pengirim / Device
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0;">
                            <span style="color: #00e07e;">{{ $activeSender }}</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">smartphone</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    Format Pengiriman: <strong style="color: #eef1f0;">Indonesian E.164 (628xxx)</strong>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan -->
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <!-- Card Aksi Simpan (Posisi Kanan) -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.15rem 1.35rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; box-shadow: var(--shadow);">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">save</span>
                    </div>
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0;">Simpan Pengaturan WhatsApp</h4>
                        <p style="font-size: 0.75rem; color: var(--muted); margin: 3px 0 0 0;">Terapkan endpoint gateway, token Fonnte/Wablas &amp; template notifikasi</p>
                    </div>
                </div>

                <button type="submit"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0.7rem 2rem; font-size: 0.875rem; font-weight: 800; color: #06261a !important; background: #00e07e; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(0, 224, 126, 0.25);"
                        onmouseover="this.style.background='#4deda4'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.background='#00e07e'; this.style.transform='none';">
                    <span class="material-symbols-rounded" style="font-size: 20px; color: #06261a !important; line-height: 1;">check</span>
                    <span style="color: #06261a !important; font-weight: 800; font-size: 0.95rem; letter-spacing: 0.02em;">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</x-filament-panels::page>

