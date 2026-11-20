<div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.8125rem;">
    @if (!$data['success'])
        <div class="noc-subcard noc-subcard-alert" style="padding: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div>
                    <h4 style="font-size: 0.9375rem; font-weight: 700; color: #f43f5e; margin: 0;">Gagal Membaca Data ONT</h4>
                    <p style="font-size: 0.8125rem; margin-top: 0.25rem; color: #a1a1aa;">{{ $data['message'] }}</p>
                </div>
            </div>
            <div style="margin-top: 0.75rem; font-size: 0.75rem; color: #71717a; padding-left: 2.25rem;">
                Tips: Pastikan server GenieACS berjalan (port 7557), modem ONT menyala, dan Device ID/Serial Number pelanggan telah sesuai.
            </div>
        </div>
    @else
        {{-- Banner jika mode simulasi demo --}}
        @if (!empty($data['is_simulated']))
            <div class="noc-subcard" style="padding: 0.75rem 1rem; border-color: rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.08); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #f59e0b;">
                    <span>⚡</span>
                    <span><strong>Mode Preview / Simulasi:</strong> Server GenieACS (port 7557) sedang offline. Data diagnostik berikut disimulasikan dari spesifikasi standar TR-069 GPON ONT.</span>
                </div>
                <span class="noc-pill noc-pill-warning" style="font-size: 0.65rem; text-transform: uppercase;">Preview Data</span>
            </div>
        @endif

        {{-- Top Bar Info --}}
        <div class="fi-section" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="noc-dot @if ($data['is_online']) noc-dot-success @else noc-dot-danger @endif" style="width: 0.75rem; height: 0.75rem;"></span>
                <div>
                    <div style="font-size: 1rem; font-weight: 800; color: currentColor; display: flex; align-items: center; gap: 0.5rem;">
                        <span>{{ $data['device_info']['model'] ?? 'ONT GPON' }}</span>
                        <span class="noc-pill {{ $data['is_online'] ? 'noc-pill-success' : 'noc-pill-danger' }}">
                            {{ $data['is_online'] ? 'ONLINE (TR-069)' : 'OFFLINE' }}
                        </span>
                    </div>
                    <div style="font-size: 0.75rem; font-family: ui-monospace, monospace; color: #71717a; margin-top: 0.15rem;">
                        Device ID: {{ $data['device_id'] }} | SN: {{ $data['device_info']['serial_number'] }}
                    </div>
                </div>
            </div>

            <div style="text-align: right; font-size: 0.75rem; color: #71717a;">
                <div>Inform Terakhir: <strong style="color: currentColor;">{{ $data['last_inform'] }}</strong></div>
                <div style="margin-top: 0.2rem;">Uptime: <strong style="color: currentColor;">{{ $data['device_info']['uptime'] }}</strong></div>
            </div>
        </div>

        {{-- Metric Cards (Redaman, TX, Suhu, WAN) --}}
        <div class="isp-grid-4">
            {{-- Optical RX (Redaman) --}}
            <div class="isp-metric-card" style="@if ($data['optical']['quality_color'] === 'success') border-color: rgba(16, 185, 129, 0.4); @elseif ($data['optical']['quality_color'] === 'warning') border-color: rgba(245, 158, 11, 0.4); @else border-color: rgba(244, 63, 94, 0.4); @endif">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                    <span>Redaman Masuk (RX)</span>
                    <span>📡</span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 1.75rem; font-weight: 900; color: currentColor; margin-top: 0.25rem;">
                    {{ $data['optical']['rx_power'] }}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.35rem;">
                    <span class="noc-pill @if ($data['optical']['quality_color'] === 'success') noc-pill-success @elseif ($data['optical']['quality_color'] === 'warning') noc-pill-warning @else noc-pill-danger @endif">
                        {{ $data['optical']['quality'] }}
                    </span>
                    <span style="font-size: 0.6875rem; color: #71717a;">Normal -15 s/d -25 dBm</span>
                </div>
            </div>

            {{-- Optical TX --}}
            <div class="isp-metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                    <span>Sinyal Keluar (TX)</span>
                    <span style="color: #38bdf8;">📤</span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 1.75rem; font-weight: 900; color: #38bdf8; margin-top: 0.25rem;">
                    {{ $data['optical']['tx_power'] }}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; color: #71717a; margin-top: 0.35rem;">
                    <span>Transmisi Laser</span>
                    <span>+1.5 ~ +5 dBm</span>
                </div>
            </div>

            {{-- Suhu & Voltase --}}
            <div class="isp-metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                    <span>Suhu & Daya</span>
                    <span style="color: #f59e0b;">🌡️</span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 1.75rem; font-weight: 900; color: currentColor; margin-top: 0.25rem;">
                    {{ $data['optical']['temperature'] }}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; color: #71717a; margin-top: 0.35rem;">
                    <span>Tegangan: <strong style="color: currentColor;">{{ $data['optical']['voltage'] }}</strong></span>
                    <span style="color: #10b981;">Aman (&lt;65°C)</span>
                </div>
            </div>

            {{-- WAN PPPoE IP --}}
            <div class="isp-metric-card">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                    <span>IP WAN / PPPoE</span>
                    <span style="color: #818cf8;">🌐</span>
                </div>
                <div style="font-family: ui-monospace, monospace; font-size: 1.35rem; font-weight: 900; color: #818cf8; margin-top: 0.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $data['wan']['ip'] }}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.6875rem; margin-top: 0.35rem;">
                    <span style="color: #71717a;">{{ $data['wan']['type'] }}</span>
                    <span class="noc-pill noc-pill-success">{{ $data['wan']['status'] }}</span>
                </div>
            </div>
        </div>

        {{-- Detail Grid: Device Hardware vs Wi-Fi --}}
        <div class="isp-grid-2">
            {{-- Spesifikasi Hardware ONT --}}
            <div class="noc-subcard" style="padding: 1.25rem;">
                <div class="noc-card-header" style="padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                    <span class="noc-dot noc-dot-primary"></span>
                    <span>Informasi Hardware & Firmware ONT</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.75rem; margin-top: 0.5rem;">
                    <div class="noc-row">
                        <span class="noc-label">Pabrikan / Vendor:</span>
                        <strong style="color: currentColor;">{{ $data['device_info']['manufacturer'] }}</strong>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Model / Tipe:</span>
                        <strong style="color: currentColor;">{{ $data['device_info']['model'] }}</strong>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Serial Number (PON SN):</span>
                        <span style="font-family: ui-monospace, monospace; font-weight: 700; color: #818cf8;">{{ $data['device_info']['serial_number'] }}</span>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Versi Firmware:</span>
                        <span style="font-family: ui-monospace, monospace;">{{ $data['device_info']['software_version'] }}</span>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Versi Hardware:</span>
                        <span style="font-family: ui-monospace, monospace;">{{ $data['device_info']['hardware_version'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Konfigurasi Wi-Fi Pelanggan --}}
            <div class="noc-subcard" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                    <div class="noc-card-header">
                        <span class="noc-dot noc-dot-warning"></span>
                        <span>Konfigurasi Wi-Fi Pelanggan (WLAN)</span>
                    </div>
                    <span class="noc-pill {{ $data['wifi']['enabled'] ? 'noc-pill-success' : 'noc-pill-danger' }}">
                        {{ $data['wifi']['enabled'] ? 'Wi-Fi Aktif' : 'Wi-Fi Mati' }}
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.75rem; margin-top: 0.5rem;">
                    <div class="noc-row">
                        <span class="noc-label">Nama Wi-Fi (SSID):</span>
                        <strong style="font-size: 0.875rem; color: #38bdf8;">{{ $data['wifi']['ssid'] }}</strong>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Password Wi-Fi:</span>
                        <span style="font-family: ui-monospace, monospace; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.35rem; background: rgba(99, 102, 241, 0.15); color: #c7d2fe; user-select: all;">
                            {{ $data['wifi']['password'] }}
                        </span>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Keamanan / Enkripsi:</span>
                        <span>{{ $data['wifi']['security'] }}</span>
                    </div>
                    <div class="noc-row">
                        <span class="noc-label">Frekuensi Kanal:</span>
                        <span>2.4 GHz b/g/n (MIMO 2x2)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daftar Perangkat Terhubung (Connected Hosts / Clients) --}}
        <div class="fi-section" style="overflow: hidden;">
            <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                <div class="noc-title-group">
                    <span style="font-size: 1.25rem;">📱</span>
                    <div>
                        <h4 class="noc-title">Perangkat Terhubung ke Modem (Connected Clients)</h4>
                        <p class="noc-subtitle">Daftar HP, laptop, dan TV pintar yang aktif menggunakan jaringan Wi-Fi pelanggan.</p>
                    </div>
                </div>
                <span class="noc-pill noc-pill-neutral">
                    Total: <strong style="color: currentColor;">{{ count($data['connected_hosts']) }} perangkat</strong>
                </span>
            </div>

            @if (empty($data['connected_hosts']))
                <div style="padding: 2rem; text-align: center; font-size: 0.75rem; color: #71717a;">
                    Belum ada perangkat klien yang terdeteksi aktif terhubung ke modem pelanggan.
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="isp-table-clean">
                        <thead>
                            <tr>
                                <th>Nama Perangkat</th>
                                <th>IP Address Lokal</th>
                                <th>MAC Address</th>
                                <th>Tipe Koneksi</th>
                                <th style="text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['connected_hosts'] as $host)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: currentColor; display: flex; align-items: center; gap: 0.4rem;">
                                            @if (str_contains(strtolower($host['interface']), 'wi-fi') || str_contains(strtolower($host['interface']), 'wireless'))
                                                <span style="color: #f59e0b;">📶</span>
                                            @else
                                                <span style="color: #38bdf8;">🔌</span>
                                            @endif
                                            <span>{{ $host['name'] }}</span>
                                        </div>
                                    </td>
                                    <td style="font-family: ui-monospace, monospace; font-size: 0.8125rem;">{{ $host['ip'] }}</td>
                                    <td style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #71717a;">{{ $host['mac'] }}</td>
                                    <td style="color: #a1a1aa;">{{ $host['interface'] }}</td>
                                    <td style="text-align: right;">
                                        <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                                            Aktif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
