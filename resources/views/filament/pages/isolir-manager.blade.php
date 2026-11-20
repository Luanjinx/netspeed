<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Top Info Header & Router Selector -->
        <div class="fi-section" style="padding: 1.25rem 1.5rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div class="noc-icon-box" style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.3); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 24px; color: #f43f5e;">shield</span>
                </div>
                <div>
                    <h2 class="noc-title" style="font-size: 1.1rem;">Redirect Isolir &amp; Captive Portal (Metode 1)</h2>
                    <p class="noc-subtitle">
                        Alihkan pelanggan PPPoE (dan IP Static) yang menunggak secara otomatis ke halaman isolir.html via Captive Portal tanpa error SSL.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #a1a1aa;">Router:</label>
                <select wire:model.live="selectedRouterId" style="font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; padding: 0.4rem 0.85rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: currentColor;">
                    @foreach (\App\Models\Router::where('is_active', true)->get() as $r)
                        <option value="{{ $r->id }}" style="background: #18181c; color: #ffffff;">{{ $r->name }} ({{ $r->host }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 0.5rem;">
            <button type="button" wire:click="$set('activeTab', 'setup')" class="noc-btn {{ $activeTab === 'setup' ? 'noc-btn-primary' : 'noc-btn-secondary' }}" style="padding: 0.5rem 1rem; font-size: 0.8125rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 16px;">settings</span> Setup Otomatis (1-Click API)
            </button>
            <button type="button" wire:click="$set('activeTab', 'script')" class="noc-btn {{ $activeTab === 'script' ? 'noc-btn-primary' : 'noc-btn-secondary' }}" style="padding: 0.5rem 1rem; font-size: 0.8125rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 16px;">terminal</span> Skrip Terminal RouterOS
            </button>
            <button type="button" wire:click="$set('activeTab', 'preview')" class="noc-btn {{ $activeTab === 'preview' ? 'noc-btn-primary' : 'noc-btn-secondary' }}" style="padding: 0.5rem 1rem; font-size: 0.8125rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="font-size: 16px;">preview</span> Pratinjau isolir.html
            </button>
        </div>

        @if ($activeTab === 'setup')
            <!-- Tab 1: Setup Otomatis -->
            <div class="isp-grid-2">
                {{-- Form Konfigurasi Parameter Subnet & Profil --}}
                <div class="noc-subcard" style="padding: 1.25rem;">
                    <div class="noc-card-header" style="padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                        <span class="noc-dot noc-dot-primary"></span>
                        <span>Parameter Jaringan Isolir MikroTik</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.875rem; margin-top: 0.75rem;">
                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">IP Gateway Isolir & Subnet:</label>
                            <input type="text" wire:model.live="gatewayIp" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-family: ui-monospace, monospace; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: currentColor;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Standar: 10.10.10.1 (Subnet /24)</span>
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">Rentang IP Pool Isolir:</label>
                            <input type="text" wire:model.live="poolRange" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-family: ui-monospace, monospace; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: currentColor;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Standar: 10.10.10.2-10.10.10.254</span>
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">Nama Profil PPPoE & Hotspot:</label>
                            <input type="text" wire:model.live="pppProfileName" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-size: 0.875rem; font-weight: 700; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: #818cf8;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Standar: ISOLIR</span>
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">Folder HTML Hotspot di MikroTik:</label>
                            <input type="text" wire:model.live="htmlDir" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-family: ui-monospace, monospace; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: currentColor;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Standar: hotspot-isolir</span>
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">DNS Name Portal Isolir (Domain Lokal):</label>
                            <input type="text" wire:model.live="dnsName" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-family: ui-monospace, monospace; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: #38bdf8;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Standar: isolir.net (otomatis di-resolve ke IP gateway isolir)</span>
                        </div>
                    </div>
                </div>

                {{-- Form Info Kontak & Pembayaran isolir.html --}}
                <div class="noc-subcard" style="padding: 1.25rem;">
                    <div class="noc-card-header" style="padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                        <span class="noc-dot noc-dot-warning"></span>
                        <span>Informasi Kontak & Pembayaran pada isolir.html</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.875rem; margin-top: 0.75rem;">
                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">Nama Brand ISP:</label>
                            <input type="text" wire:model.live="companyName" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: currentColor;">
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">No WhatsApp Customer Service:</label>
                            <input type="text" wire:model.live="companyPhone" placeholder="081234567890" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: #10b981;">
                            <span style="font-size: 0.6875rem; color: #71717a;">Tombol chat WhatsApp otomatis mengarah ke nomor ini.</span>
                        </div>

                        <div>
                            <label style="font-size: 0.75rem; color: #a1a1aa; font-weight: 600; display: block; margin-bottom: 0.25rem;">Metode & Rekening Bank:</label>
                            <input type="text" wire:model.live="bankAccount" placeholder="BCA: 123-456-7890 a/n NetSpeed" style="width: 100%; border-radius: 0.5rem; padding: 0.45rem 0.75rem; font-size: 0.875rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); color: currentColor;">
                        </div>

                        <!-- Eksekusi Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <button type="button" wire:click="applyToRouter" wire:loading.attr="disabled" class="noc-btn noc-btn-primary" style="padding: 0.75rem; font-size: 0.875rem; justify-content: center; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <span wire:loading.remove wire:target="applyToRouter" class="material-symbols-rounded" style="font-size: 16px;">bolt</span>
                                <span wire:loading wire:target="applyToRouter" class="material-symbols-rounded" style="font-size: 16px;">hourglass_empty</span>
                                Terapkan Konfigurasi ke MikroTik via API
                            </button>

                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" wire:click="uploadHtmlToRouter" wire:loading.attr="disabled" class="noc-btn noc-btn-secondary" style="flex: 1; padding: 0.6rem; font-size: 0.75rem; justify-content: center; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">cloud_upload</span>
                                    <span>Upload isolir.html via API</span>
                                </button>
                                <button type="button" wire:click="downloadHtml" class="noc-btn noc-btn-secondary" style="flex: 1; padding: 0.6rem; font-size: 0.75rem; justify-content: center; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">download</span>
                                    <span>Unduh isolir.html</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($setupSteps))
                <div class="noc-subcard noc-subcard-success" style="padding: 1.25rem;">
                    <div class="noc-card-header" style="color: #059669; font-size: 0.8125rem;">
                        <span class="noc-dot noc-dot-success"></span>
                        <span>Laporan Penerapan Konfigurasi ke Router Berhasil</span>
                    </div>
                    <ul style="margin-top: 0.5rem; padding-left: 1.25rem; font-size: 0.75rem; color: #047857; display: flex; flex-direction: column; gap: 0.25rem;">
                        @foreach ($setupSteps as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

        @elseif ($activeTab === 'script')
            <!-- Tab 2: Skrip Terminal MikroTik -->
            <div class="fi-section" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 class="noc-title">Skrip Perintah RouterOS (MikroTik Terminal)</h3>
                        <p class="noc-subtitle">Gunakan skrip ini jika ingin menerapkan konfigurasi secara manual melalui Winbox > New Terminal.</p>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('mikrotikScriptText').innerText); alert('Skrip berhasil disalin ke clipboard!');" class="noc-btn noc-btn-primary" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">content_copy</span>
                        <span>Salin Skrip Terminal</span>
                    </button>
                </div>

                <pre id="mikrotikScriptText" style="padding: 1rem; border-radius: 0.75rem; background: #0c0c0e; border: 1px solid rgba(255, 255, 255, 0.08); font-family: ui-monospace, monospace; font-size: 0.8125rem; color: #a5b4fc; overflow-x: auto; line-height: 1.5; white-space: pre-wrap;">{{ $generatedScript }}</pre>

                <div class="noc-subcard" style="margin-top: 1rem; font-size: 0.75rem; color: #a1a1aa; line-height: 1.5;">
                    <strong style="color: currentColor;">Panduan Penggunaan Manual di Winbox:</strong>
                    <ol style="padding-left: 1.25rem; margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.2rem;">
                        <li>Buka aplikasi <strong>Winbox</strong> dan masuk ke router MikroTik Anda.</li>
                        <li>Buka menu <strong>New Terminal</strong>, lalu paste (klik kanan > Paste) skrip di atas dan tekan <strong>Enter</strong>.</li>
                        <li>Unduh file <code>isolir.html</code> dari tab <strong>Pratinjau isolir.html</strong>.</li>
                        <li>Di Winbox, buka menu <strong>Files</strong>, lalu seret (drag & drop) file <code>isolir.html</code> ke dalam folder <code>hotspot-isolir/</code> dan ganti namanya menjadi <code>login.html</code>.</li>
                    </ol>
                </div>
            </div>

        @elseif ($activeTab === 'preview')
            <!-- Tab 3: Pratinjau isolir.html -->
            <div class="fi-section" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 class="noc-title">Pratinjau Tampilan Layar Pelanggan (isolir.html)</h3>
                        <p class="noc-subtitle">Tampilan responsif yang otomatis muncul di layar HP atau laptop pelanggan saat terhubung ke Wi-Fi.</p>
                    </div>
                    <button type="button" wire:click="downloadHtml" class="noc-btn noc-btn-primary" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">download</span>
                        <span>Unduh File isolir.html</span>
                    </button>
                </div>

                <div style="max-width: 480px; margin: 0 auto; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <iframe srcdoc="{{ app(\App\Services\Router\MikrotikApiService::class)->getIsolirHtmlContent(['company_name' => $companyName, 'company_phone' => $companyPhone, 'bank_account' => $bankAccount, 'billing_url' => url('/')]) }}" style="width: 100%; height: 600px; border: none; background: #09090b;"></iframe>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

