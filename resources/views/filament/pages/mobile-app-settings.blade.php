<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Base URL API Backend -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Base URL API Backend
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; word-break: break-all;">
                            <span style="color: #38bdf8;">{{ $data['app_api_base_url'] ?? url('/api') }}</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #38bdf8; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">lan</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.8125rem; color: #9aa49f;">
                    Gunakan URL ini di variabel <code style="color: #a7f3d0;">baseUrl</code> Flutter &amp; STB
                </div>
            </div>

            <!-- Card 2: App API Secret Key -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Header Otorisasi API
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                            <span style="color: #00e07e;">Kunci Aktif</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">key</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f; word-break: break-all;">
                    Header: <code style="color: #00e07e;">x-api-key: {{ Str::limit($currentApiKey, 20) }}</code>
                </div>
            </div>

            <!-- Card 3: OneSignal Push Engine -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            OneSignal Push Engine
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($isOneSignalActive)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">Terkoneksi</span>
                                @if ($oneSignalAppName)
                                    <span style="font-size: 0.75rem; font-weight: 600; color: #9aa49f;">({{ $oneSignalAppName }})</span>
                                @endif
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #eab308; box-shadow: 0 0 10px #eab308;"></span>
                                <span style="color: #eab308;">Belum Terhubung</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #eab308; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">notifications_active</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    <a href="{{ url('/admin/onesignal') }}" style="color: #00e07e; text-decoration: underline;">Buka Hub Broadcast Pesan Massal &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan -->
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <!-- Card Aksi Simpan (Posisi Kanan) -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.15rem 1.35rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div style="width: 2.6rem; height: 2.6rem; border-radius: 0.5rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #22c55e; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">save</span>
                    </div>
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0;">Simpan Pengaturan Mobile</h4>
                        <p style="font-size: 0.75rem; color: #9aa49f; margin: 3px 0 0 0;">Terapkan konfigurasi base URL, API secret key &amp; OneSignal broadcast</p>
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
    </div>
</x-filament-panels::page>

