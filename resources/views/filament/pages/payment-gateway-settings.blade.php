<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Provider Aktif -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Provider Payment Gateway
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($isConfigured)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">{{ $activeProviderName }}</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #eab308; box-shadow: 0 0 10px #eab308;"></span>
                                <span style="color: #eab308;">{{ $activeProviderName }} (Kunci Kosong)</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">credit_card</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.8125rem; color: #9aa49f;">
                    Status: <strong style="color: #eef1f0;">Pelunasan Otomatis Aktif</strong>
                </div>
            </div>

            <!-- Card 2: QRIS Merchant & Auto-Scanner -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            QRIS Dinamis 0% MDR
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($hasQrisConfigured)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                <span style="color: #00e07e;">{{ $qrisMerchantName }}</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #eab308; box-shadow: 0 0 10px #eab308;"></span>
                                <span style="color: #eab308;">Belum Upload QRIS</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">qr_code_2</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    @if ($hasQrisConfigured)
                        <span style="color: #00e07e;">Standar EMVCo Valid &bull; Auto Scanner Aktif</span>
                    @else
                        <span>Unggah foto barcode QRIS di form bawah untuk auto-scan</span>
                    @endif
                </div>
            </div>

            <!-- Card 3: Webhook & Otomasi Pelunasan -->
            <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Webhook &amp; Otomasi Sistem
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #eef1f0;">
                            <span style="color: #38bdf8;">Auto-Unblock PPPoE</span>
                        </div>
                    </div>
                    <div style="background: #131615; border: 1px solid #232826; border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">bolt</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    Kwitansi WhatsApp + Alert Telegram NOC otomatis saat tagihan lunas
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
                    Simpan Pengaturan Gateway &amp; QRIS
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
