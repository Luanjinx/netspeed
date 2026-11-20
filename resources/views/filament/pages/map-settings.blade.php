<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Provider Peta Aktif -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Provider Satelit Peta
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($hasGoogleApiKey)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">Google Maps Platform</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8;"></span>
                                <span style="color: #38bdf8;">OpenStreetMap &amp; Tiles (Bawaan)</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">map</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.8125rem; color: #9aa49f;">
                    @if ($hasGoogleApiKey)
                        <span>Kunci Google Maps aktif &bull; Geocoding presisi</span>
                    @else
                        <span>Mode hybrid gratis tanpa biaya kuota API</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Titik Koordinat Pusat NOC -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Titik Pusat Peta NOC
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0;">
                            <span style="color: #00e07e;">{{ $officeCoords }}</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">near_me</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    Level Zoom Awal: <strong>{{ $zoomLevel }}x</strong>
                </div>
            </div>

            <!-- Card 3: Peta Jaringan Live -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Akses Cepat Visual Peta
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0;">
                            <span style="color: #38bdf8;">Peta Admin &amp; Teknisi</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">explore</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    <a href="{{ url('/admin/network-map') }}" style="color: #00e07e; text-decoration: underline;">Peta Admin (/admin/network-map) &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan -->
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-between pt-4 border-t border-[#232826]">
                <a href="{{ url('/admin/settings') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-[#1a1e1c] text-[#9aa49f] border border-[#232826] hover:text-[#eef1f0] hover:border-[#00e07e] transition-colors">
                    <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                    Kembali ke Pengaturan Sistem
                </a>

                <x-filament::button type="submit" size="lg" icon="heroicon-o-check" color="primary">
                    Simpan Pengaturan Peta
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>

