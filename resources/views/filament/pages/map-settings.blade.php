<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Provider Peta Aktif -->
            <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">
                            Provider Satelit Peta
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                            @if ($hasGoogleApiKey)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">Google Maps Platform</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8;"></span>
                                <span style="color: #38bdf8;">OpenStreetMap &amp; Tiles (Bawaan)</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">map</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--border); font-size: 0.8125rem; color: var(--muted);">
                    @if ($hasGoogleApiKey)
                        <span>Kunci Google Maps aktif &bull; Geocoding presisi</span>
                    @else
                        <span>Mode hybrid gratis tanpa biaya kuota API</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Titik Koordinat Pusat NOC -->
            <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">
                            Titik Pusat Peta NOC
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: var(--text);">
                            <span style="color: #00e07e;">{{ $officeCoords }}</span>
                        </div>
                    </div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">near_me</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--border); font-size: 0.75rem; color: var(--muted);">
                    Level Zoom Awal: <strong>{{ $zoomLevel }}x</strong>
                </div>
            </div>

            <!-- Card 3: Peta Jaringan Live -->
            <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">
                            Akses Cepat Visual Peta
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: var(--text);">
                            <span style="color: #38bdf8;">Peta Admin &amp; Teknisi</span>
                        </div>
                    </div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">explore</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--border); font-size: 0.75rem; color: var(--muted);">
                    <a href="{{ url('/admin/network-map') }}" style="color: #00e07e; text-decoration: underline;">Peta Admin (/admin/network-map) &rarr;</a>
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
                        <h4 style="font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0;">Simpan Pengaturan Peta</h4>
                        <p style="font-size: 0.75rem; color: var(--muted); margin: 3px 0 0 0;">Terapkan Google Maps JS API key &amp; titik koordinat kantor pusat NOC</p>
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
    </div>
</x-filament-panels::page>

