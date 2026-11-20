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

            <div class="flex items-center justify-between pt-4 border-t border-[#232826]">
                <a href="{{ url('/admin/settings') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-[#1a1e1c] text-[#9aa49f] border border-[#232826] hover:text-[#eef1f0] hover:border-[#00e07e] transition-colors">
                    <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                    Kembali ke Pengaturan Sistem
                </a>

                <x-filament::button type="submit" size="lg" icon="heroicon-o-check" color="primary">
                    Simpan Pengaturan WhatsApp
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
