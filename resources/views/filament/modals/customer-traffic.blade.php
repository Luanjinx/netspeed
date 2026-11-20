<div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.8125rem;">
    @if (!$data['success'])
        <div class="noc-subcard noc-subcard-alert" style="padding: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div>
                    <h4 style="font-size: 0.9375rem; font-weight: 700; color: #f43f5e; margin: 0;">Gagal Mengambil Data Trafik</h4>
                    <p style="font-size: 0.8125rem; margin-top: 0.25rem; color: #a1a1aa;">{{ $data['message'] }}</p>
                </div>
            </div>
        </div>
    @elseif (!$data['online'])
        <div class="noc-subcard" style="padding: 1.25rem; border-color: rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.08);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="noc-dot noc-dot-warning" style="width: 0.875rem; height: 0.875rem;"></span>
                <div>
                    <h4 style="font-size: 0.9375rem; font-weight: 700; color: #f59e0b; margin: 0;">Pelanggan Sedang OFFLINE</h4>
                    <p style="font-size: 0.8125rem; margin-top: 0.25rem; color: #a1a1aa;">
                        Username <strong style="color: currentColor;">{{ $data['username'] }}</strong> saat ini tidak memiliki sesi aktif di MikroTik. Modem ONT mungkin sedang mati atau kabel fiber terputus.
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Banner jika mode simulasi demo --}}
        @if (!empty($data['is_simulated']))
            <div class="noc-subcard" style="padding: 0.75rem 1rem; border-color: rgba(99, 102, 241, 0.35); background: rgba(99, 102, 241, 0.08); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #818cf8;">
                    <span>⚡</span>
                    <span><strong>Mode Preview / Simulasi:</strong> Router MikroTik (192.168.88.1) tidak terhubung langsung. Data live traffic disimulasikan dari router RB4011.</span>
                </div>
                <span class="noc-pill noc-pill-primary" style="font-size: 0.65rem; text-transform: uppercase;">Preview Data</span>
            </div>
        @endif

        {{-- Top Bar Info --}}
        <div class="fi-section" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="noc-dot noc-dot-success" style="width: 0.75rem; height: 0.75rem;"></span>
                <div>
                    <div style="font-size: 1rem; font-weight: 800; color: currentColor; display: flex; align-items: center; gap: 0.5rem;">
                        <span>User: {{ $data['username'] }}</span>
                        <span class="noc-pill noc-pill-success">
                            ONLINE (PPPoE)
                        </span>
                    </div>
                    <div style="font-size: 0.75rem; color: #71717a; margin-top: 0.15rem;">
                        Router: <strong style="color: currentColor;">{{ $data['router_name'] }}</strong> | Uptime Sesi: <strong style="color: #38bdf8;">{{ $data['uptime'] }}</strong>
                    </div>
                </div>
            </div>

            <div style="text-align: right; font-size: 0.75rem; color: #71717a;">
                <div>Paket: <strong style="color: #818cf8;">{{ $data['package_name'] }}</strong></div>
                <div style="margin-top: 0.2rem;">Profil Kecepatan: <span style="font-family: ui-monospace, monospace; font-weight: 700; color: currentColor;">{{ $data['speed_rate'] }}</span></div>
            </div>
        </div>

        {{-- Live Bandwidth Cards (RX / TX) --}}
        <div class="isp-grid-2">
            {{-- Download (RX) --}}
            <div class="isp-metric-card" style="border-color: rgba(16, 185, 129, 0.35); background: linear-gradient(145deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%);">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #10b981;">
                    <span style="display: flex; align-items: center; gap: 0.4rem;">
                        <span>⬇️</span>
                        Download Rate (Trafik Masuk)
                    </span>
                    <span class="noc-pill noc-pill-success" style="font-size: 0.65rem;">
                        Real-time
                    </span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 2.25rem; font-weight: 900; color: #10b981; margin: 0.75rem 0;">
                    {{ $data['rx_rate'] }}
                </div>
                <div style="padding-top: 0.5rem; border-top: 1px solid rgba(16, 185, 129, 0.2); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #a1a1aa;">
                    <span>Total Akumulasi Download:</span>
                    <strong style="font-family: ui-monospace, monospace; color: currentColor;">{{ $data['rx_total'] }}</strong>
                </div>
            </div>

            {{-- Upload (TX) --}}
            <div class="isp-metric-card" style="border-color: rgba(56, 189, 248, 0.35); background: linear-gradient(145deg, rgba(56, 189, 248, 0.08) 0%, rgba(56, 189, 248, 0.02) 100%);">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #38bdf8;">
                    <span style="display: flex; align-items: center; gap: 0.4rem;">
                        <span>⬆️</span>
                        Upload Rate (Trafik Keluar)
                    </span>
                    <span class="noc-pill noc-pill-primary" style="font-size: 0.65rem;">
                        Real-time
                    </span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 2.25rem; font-weight: 900; color: #38bdf8; margin: 0.75rem 0;">
                    {{ $data['tx_rate'] }}
                </div>
                <div style="padding-top: 0.5rem; border-top: 1px solid rgba(56, 189, 248, 0.2); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #a1a1aa;">
                    <span>Total Akumulasi Upload:</span>
                    <strong style="font-family: ui-monospace, monospace; color: currentColor;">{{ $data['tx_total'] }}</strong>
                </div>
            </div>
        </div>

        {{-- Connection Details Grid --}}
        <div class="noc-subcard" style="padding: 1.25rem;">
            <div class="noc-card-header" style="padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                <span class="noc-dot noc-dot-primary"></span>
                <span>Parameter Sesi Jaringan PPPoE Pelanggan</span>
            </div>

            <div class="isp-grid-4" style="margin-top: 0.75rem;">
                <div class="noc-subcard" style="padding: 0.75rem;">
                    <span style="font-size: 0.6875rem; color: #71717a; display: block;">IP Address Pelanggan</span>
                    <span style="font-family: ui-monospace, monospace; font-size: 0.875rem; font-weight: 700; color: #818cf8; user-select: all;">{{ $data['ip_address'] }}</span>
                </div>
                <div class="noc-subcard" style="padding: 0.75rem;">
                    <span style="font-size: 0.6875rem; color: #71717a; display: block;">MAC Address (Caller ID)</span>
                    <span style="font-family: ui-monospace, monospace; font-size: 0.875rem; font-weight: 700; color: currentColor; user-select: all;">{{ $data['mac_address'] }}</span>
                </div>
                <div class="noc-subcard" style="padding: 0.75rem;">
                    <span style="font-size: 0.6875rem; color: #71717a; display: block;">Layanan Service</span>
                    <span style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase;">{{ $data['service'] }}</span>
                </div>
                <div class="noc-subcard" style="padding: 0.75rem;">
                    <span style="font-size: 0.6875rem; color: #71717a; display: block;">Status Koneksi</span>
                    <span style="font-size: 0.875rem; font-weight: 800; color: #10b981;">Aktif Terhubung</span>
                </div>
            </div>
        </div>
    @endif
</div>
