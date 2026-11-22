<x-filament-panels::page>
    <style>
        /* Scoped Self-Contained Styles for NetSpeed Cyber NOC ONT Manager */
        .ont-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .ont-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .ont-kpi-card {
            background: #131615;
            border: 1px solid #232826;
            border-radius: 0.875rem;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            transition: border-color 0.2s ease;
        }

        .ont-kpi-card:hover {
            border-color: #333a37;
        }

        .ont-kpi-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9aa49f;
        }

        .ont-kpi-val {
            font-size: 1.625rem;
            font-weight: 800;
            margin-top: 0.25rem;
            font-family: 'Plus Jakarta Sans', ui-monospace, monospace;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ont-kpi-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ont-bar {
            background: #131615;
            border: 1px solid #232826;
            border-radius: 0.875rem;
            padding: 0.85rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .ont-tab-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .ont-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .ont-tab-active {
            background: #00e07e;
            color: #06261a;
            box-shadow: 0 2px 6px rgba(0, 224, 126, 0.25);
        }

        .ont-tab-inactive {
            background: #1a1e1c;
            color: #9aa49f;
            border: 1px solid #232826;
        }

        .ont-tab-inactive:hover {
            color: #eef1f0;
            border-color: #38423e;
        }

        .ont-input {
            padding: 0.5rem 0.85rem;
            background: #1a1e1c;
            border: 1px solid #232826;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: #eef1f0;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .ont-input:focus {
            border-color: #00e07e;
        }

        .ont-table-card {
            background: #131615;
            border: 1px solid #232826;
            border-radius: 0.875rem;
            overflow: hidden;
            width: 100%;
        }

        .ont-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            text-align: left;
        }

        .ont-th {
            padding: 0.85rem 1rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9aa49f;
            background: #171b19;
            border-bottom: 1px solid #232826;
            white-space: nowrap;
        }

        .ont-td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #232826;
            vertical-align: middle;
            color: #cbd5e1;
        }

        .ont-tr:hover {
            background: rgba(26, 30, 28, 0.6);
        }

        .ont-btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.35rem 0.65rem;
            border-radius: 0.45rem;
            background: #1a1e1c;
            border: 1px solid #232826;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ont-btn-action:hover {
            background: #232826;
        }

        .ont-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
        }

        .ont-modal-card {
            background: #131615;
            border: 1px solid #232826;
            border-radius: 1rem;
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.8);
        }

        .ont-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            display: inline-block;
        }
    </style>

    <div class="ont-container">

        <!-- 1. TOP METRIC SUMMARY CARDS -->
        <div class="ont-kpi-grid">
            <!-- Total ONT -->
            <div class="ont-kpi-card">
                <div>
                    <div class="ont-kpi-label">Total ONT Terdaftar</div>
                    <div class="ont-kpi-val" style="color: #eef1f0;">{{ $stats['total'] }}</div>
                </div>
                <div class="ont-kpi-icon-box" style="background: #1a1e1c; border: 1px solid #232826; color: #9aa49f;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">router</span>
                </div>
            </div>

            <!-- Modem Online -->
            <div class="ont-kpi-card">
                <div>
                    <div class="ont-kpi-label">Modem Online</div>
                    <div class="ont-kpi-val" style="color: #00e07e;">
                        <span>{{ $stats['online'] }}</span>
                        <span class="ont-pulse-dot" style="background: #00e07e; animation: pulse 2s infinite;"></span>
                    </div>
                </div>
                <div class="ont-kpi-icon-box" style="background: rgba(0,224,126,0.1); border: 1px solid rgba(0,224,126,0.25); color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">wifi</span>
                </div>
            </div>

            <!-- Modem Offline -->
            <div class="ont-kpi-card">
                <div>
                    <div class="ont-kpi-label">Modem Offline / LOS</div>
                    <div class="ont-kpi-val" style="color: #f27b7b;">{{ $stats['offline'] }}</div>
                </div>
                <div class="ont-kpi-icon-box" style="background: rgba(242,123,123,0.1); border: 1px solid rgba(242,123,123,0.25); color: #f27b7b;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">wifi_off</span>
                </div>
            </div>

            <!-- Redaman Kritis -->
            <div class="ont-kpi-card">
                <div>
                    <div class="ont-kpi-label">Redaman Kritis</div>
                    <div class="ont-kpi-val" style="color: #f2c56b;">
                        <span>{{ $stats['critical'] }}</span>
                    </div>
                    <div style="font-size: 0.6875rem; color: #9aa49f; margin-top: 2px;">&lt; -27.00 dBm</div>
                </div>
                <div class="ont-kpi-icon-box" style="background: rgba(242,197,107,0.1); border: 1px solid rgba(242,197,107,0.25); color: #f2c56b;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">warning</span>
                </div>
            </div>

            <!-- Unassigned ONTs -->
            <div class="ont-kpi-card">
                <div>
                    <div class="ont-kpi-label">ONT Baru (Belum Assign)</div>
                    <div class="ont-kpi-val" style="color: #38bdf8;">{{ $stats['unassigned'] }}</div>
                </div>
                <div class="ont-kpi-icon-box" style="background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.25); color: #38bdf8;">
                    <span class="material-symbols-rounded" style="font-size: 22px;">sensors</span>
                </div>
            </div>
        </div>

        <!-- 2. FILTER & TAB BAR -->
        <div class="ont-bar">
            <!-- Tabs -->
            <div class="ont-tab-group">
                <button type="button" wire:click="setTab('registered')"
                    class="ont-tab-btn {{ $activeTab === 'registered' ? 'ont-tab-active' : 'ont-tab-inactive' }}">
                    <span class="material-symbols-rounded" style="font-size: 18px;">devices</span>
                    <span>Modem Pelanggan ({{ count($registeredDevices) }})</span>
                </button>

                <button type="button" wire:click="setTab('unassigned')"
                    class="ont-tab-btn {{ $activeTab === 'unassigned' ? 'ont-tab-active' : 'ont-tab-inactive' }}">
                    <span class="material-symbols-rounded" style="font-size: 18px;">sensors</span>
                    <span>ONT Baru Terdeteksi ({{ count($unassignedDevices) }})</span>
                </button>
            </div>

            <!-- Filters & Search -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-left: auto;">
                <!-- Search Input -->
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 9px; font-size: 16px; color: #9aa49f;">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Cari nama, SN, paket..."
                        class="ont-input" style="padding-left: 2rem; width: 220px;">
                </div>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter" class="ont-input" style="cursor: pointer;">
                    <option value="all">Semua Status</option>
                    <option value="online">Hanya Online</option>
                    <option value="offline">Hanya Offline</option>
                    <option value="critical_rx">Redaman Kritis (&lt; -27 dBm)</option>
                </select>

                <!-- Server Filter -->
                <select wire:model.live="selectedServerId" class="ont-input" style="cursor: pointer;">
                    <option value="">Semua Server GenieACS</option>
                    @foreach ($this->serverOptions as $srv)
                        <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 3. TAB CONTENT -->
        @if ($activeTab === 'registered')
            <!-- REGISTERED CUSTOMERS ONT TABLE -->
            <div class="ont-table-card">
                <div style="overflow-x: auto; width: 100%;">
                    <table class="ont-table">
                        <thead>
                            <tr>
                                <th class="ont-th" style="padding-left: 1.25rem;">Status</th>
                                <th class="ont-th">Pelanggan</th>
                                <th class="ont-th">Device ID / GPON SN</th>
                                <th class="ont-th">Merk & Model</th>
                                <th class="ont-th">Redaman Optik (RX / TX)</th>
                                <th class="ont-th">Wi-Fi & IP</th>
                                <th class="ont-th">Terakhir Inform</th>
                                <th class="ont-th" style="padding-right: 1.25rem; text-align: right;">Aksi Remote TR-069</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registeredDevices as $dev)
                                <tr class="ont-tr">
                                    <!-- Status Dot -->
                                    <td class="ont-td" style="padding-left: 1.25rem; white-space: nowrap;">
                                        @if ($dev['is_online'])
                                            <span style="display: inline-flex; align-items: center; gap: 5px; padding: 0.2rem 0.55rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; background: rgba(0,224,126,0.12); color: #00e07e; border: 1px solid rgba(0,224,126,0.3);">
                                                <span class="ont-pulse-dot" style="background: #00e07e;"></span>
                                                Online
                                            </span>
                                        @else
                                            <span style="display: inline-flex; align-items: center; gap: 5px; padding: 0.2rem 0.55rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; background: rgba(242,123,123,0.12); color: #f27b7b; border: 1px solid rgba(242,123,123,0.3);">
                                                <span class="ont-pulse-dot" style="background: #f27b7b;"></span>
                                                Offline
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Customer Info -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="font-weight: 700; color: #eef1f0; font-size: 0.875rem;">{{ $dev['customer_name'] }}</div>
                                        <div style="font-size: 0.6875rem; color: #9aa49f; margin-top: 2px;">
                                            {{ $dev['customer_number'] }} • <span style="color: #00e07e; font-weight: 600;">{{ $dev['package_name'] }}</span>
                                        </div>
                                        <div style="font-size: 0.625rem; color: #64748b; margin-top: 2px;">{{ $dev['odp_name'] }} ({{ $dev['odp_port'] }})</div>
                                    </td>

                                    <!-- Device ID / SN -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="font-family: ui-monospace, monospace; font-size: 0.75rem; font-weight: 700; color: #eef1f0; background: #1a1e1c; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid #232826; display: inline-block;">
                                            {{ $dev['device_id'] }}
                                        </div>
                                    </td>

                                    <!-- Model -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="font-weight: 600; color: #eef1f0;">{{ $dev['model'] }}</div>
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">{{ $dev['manufacturer'] }}</div>
                                    </td>

                                    <!-- Optical Power (RX / TX) -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div>
                                            @if ($dev['rx_raw'] >= -24.0)
                                                <span style="padding: 0.15rem 0.45rem; border-radius: 0.25rem; font-family: ui-monospace, monospace; font-weight: 700; font-size: 0.75rem; background: rgba(0,224,126,0.15); color: #00e07e; border: 1px solid rgba(0,224,126,0.3);">
                                                    RX: {{ $dev['rx_power'] }}
                                                </span>
                                            @elseif ($dev['rx_raw'] >= -27.0)
                                                <span style="padding: 0.15rem 0.45rem; border-radius: 0.25rem; font-family: ui-monospace, monospace; font-weight: 700; font-size: 0.75rem; background: rgba(242,197,107,0.15); color: #f2c56b; border: 1px solid rgba(242,197,107,0.3);">
                                                    RX: {{ $dev['rx_power'] }}
                                                </span>
                                            @else
                                                <span style="padding: 0.15rem 0.45rem; border-radius: 0.25rem; font-family: ui-monospace, monospace; font-weight: 700; font-size: 0.75rem; background: rgba(242,123,123,0.15); color: #f27b7b; border: 1px solid rgba(242,123,123,0.3);">
                                                    RX: {{ $dev['rx_power'] }} ⚠
                                                </span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.625rem; color: #64748b; font-family: ui-monospace, monospace; margin-top: 3px;">TX: {{ $dev['tx_power'] }}</div>
                                    </td>

                                    <!-- Wi-Fi & IP -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: #eef1f0; font-weight: 600;">
                                            <span class="material-symbols-rounded" style="font-size: 15px; color: #00e07e;">wifi</span>
                                            <span>{{ $dev['wifi_ssid'] }}</span>
                                        </div>
                                        <div style="font-size: 0.625rem; color: #64748b; font-family: ui-monospace, monospace; margin-top: 2px;">IP: {{ $dev['wan_ip'] }}</div>
                                    </td>

                                    <!-- Last Inform -->
                                    <td class="ont-td" style="white-space: nowrap; color: #9aa49f; font-size: 0.75rem;">
                                        {{ $dev['last_inform'] }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="ont-td" style="padding-right: 1.25rem; white-space: nowrap; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <!-- Button Diagnosa -->
                                            <button type="button" wire:click="openDiagnostic({{ $dev['customer_id'] }})"
                                                class="ont-btn-action" style="color: #38bdf8;" title="Diagnosa Detail & Sinyal Optik">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">query_stats</span>
                                                <span>Diagnosa</span>
                                            </button>

                                            <!-- Button Ganti Wi-Fi -->
                                            <button type="button" wire:click="openWifiModal({{ $dev['customer_id'] }})"
                                                class="ont-btn-action" style="color: #00e07e;" title="Ganti SSID & Sandi Wi-Fi">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">wifi_password</span>
                                                <span>Wi-Fi</span>
                                            </button>

                                            <!-- Button Reboot -->
                                            <button type="button" wire:click="rebootOnt({{ $dev['customer_id'] }})"
                                                wire:confirm="Yakin ingin me-restart modem ONT pelanggan {{ $dev['customer_name'] }} secara jarak jauh?"
                                                class="ont-btn-action" style="color: #f27b7b;" title="Reboot Modem Jarak Jauh">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">restart_alt</span>
                                                <span>Reboot</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                        <span class="material-symbols-rounded" style="font-size: 40px; display: block; margin: 0 auto 0.5rem auto; opacity: 0.4;">router</span>
                                        <div style="font-size: 0.875rem;">Tidak ada modem ONT yang sesuai dengan pencarian atau filter.</div>
                                        <div style="font-size: 0.75rem; color: #475569; margin-top: 4px;">Pastikan data pelanggan sudah memiliki Serial Number (GPON SN) atau GenieACS Device ID.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- UNASSIGNED ONTs TABLE -->
            <div class="ont-table-card">
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #232826; background: #171b19; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h4 style="font-weight: 700; font-size: 0.875rem; color: #eef1f0; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <span class="material-symbols-rounded" style="font-size: 18px; color: #38bdf8;">sensors</span>
                            <span>Modem ONT Baru yang Belum Dipasangkan ke Pelanggan</span>
                        </h4>
                        <p style="font-size: 0.75rem; color: #9aa49f; margin: 0.25rem 0 0 0;">Modem berikut telah mengirim sinyal Inform ke GenieACS tetapi Device ID-nya belum diasosiasikan dengan akun pelanggan di NetSpeed.</p>
                    </div>
                </div>

                <div style="overflow-x: auto; width: 100%;">
                    <table class="ont-table">
                        <thead>
                            <tr>
                                <th class="ont-th" style="padding-left: 1.25rem;">Device ID (GPON SN)</th>
                                <th class="ont-th">Merk & Model</th>
                                <th class="ont-th">IP Lokal / WAN</th>
                                <th class="ont-th">Sinyal Terakhir</th>
                                <th class="ont-th" style="padding-right: 1.25rem; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unassignedDevices as $unassigned)
                                <tr class="ont-tr">
                                    <td class="ont-td" style="padding-left: 1.25rem; font-family: ui-monospace, monospace; font-weight: 700; color: #eef1f0; font-size: 0.875rem;">
                                        {{ $unassigned['device_id'] }}
                                    </td>
                                    <td class="ont-td">
                                        <div style="font-weight: 600; color: #eef1f0;">{{ $unassigned['model'] }}</div>
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">{{ $unassigned['manufacturer'] }}</div>
                                    </td>
                                    <td class="ont-td" style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #9aa49f;">
                                        {{ $unassigned['ip'] }}
                                    </td>
                                    <td class="ont-td" style="color: #9aa49f; font-size: 0.75rem;">
                                        {{ $unassigned['last_inform'] }}
                                    </td>
                                    <td class="ont-td" style="padding-right: 1.25rem; text-align: right;">
                                        <button type="button" wire:click="openAssignModal('{{ $unassigned['device_id'] }}', '{{ $unassigned['model'] }}')"
                                            class="ont-btn-action" style="background: #00e07e; color: #06261a; border: none; padding: 0.45rem 0.85rem; font-weight: 800;">
                                            <span class="material-symbols-rounded" style="font-size: 15px;">person_add</span>
                                            <span>Pasangkan ke Pelanggan</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                        <span class="material-symbols-rounded" style="font-size: 40px; display: block; margin: 0 auto 0.5rem auto; opacity: 0.4;">task_alt</span>
                                        <div style="font-size: 0.875rem;">Tidak ada modem ONT baru yang belum terpasang.</div>
                                        <div style="font-size: 0.75rem; color: #475569; margin-top: 4px;">Saat modem pelanggan baru dicolok ke fiber dan menghubungi TR-069 port 7547, otomatis muncul di sini.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 1: DIAGNOSA DETAIL ONT & SINYAL OPTIK                     -->
        <!-- ============================================================== -->
        @if ($showDiagnosticModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeDiagnosticModal()">
                <div class="ont-modal-card">
                    <!-- Modal Header -->
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #232826; background: #171b19; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">query_stats</span>
                            </div>
                            <div>
                                <h3 style="font-weight: 700; font-size: 0.9375rem; color: #eef1f0; margin: 0;">Diagnosa Live Modem ONT (TR-069)</h3>
                                <p style="font-size: 0.6875rem; color: #9aa49f; margin: 2px 0 0 0;">Informasi real-time perangkat, sinyal optik, suhu, dan koneksi WAN</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDiagnosticModal()" style="color: #9aa49f; background: transparent; border: none; font-size: 1.25rem; cursor: pointer; padding: 0.25rem;">✕</button>
                    </div>

                    <!-- Modal Body -->
                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; max-height: 70vh; overflow-y: auto;">
                        @if ($loadingDiagnostic)
                            <div style="padding: 2.5rem; text-align: center; color: #9aa49f;">
                                <span class="material-symbols-rounded" style="font-size: 32px; color: #00e07e; animation: spin 1s linear infinite; display: block; margin: 0 auto 0.5rem auto;">sync</span>
                                <div>Mengambil data diagnostik langsung dari modem via GenieACS...</div>
                            </div>
                        @else
                            <!-- Sinyal Optik Card -->
                            <div style="padding: 1rem; border-radius: 0.625rem; background: #1a1e1c; border: 1px solid #232826;">
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9aa49f; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                                    <span>Kekuatan Sinyal Optik (Fiber Power)</span>
                                    <span style="font-size: 0.6875rem; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-weight: 700; background: rgba(0,224,126,0.15); color: #00e07e;">
                                        {{ $diagnosticData['optical']['signal_quality'] ?? 'Ideal' }}
                                    </span>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem;">
                                    <div style="background: #131615; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #232826;">
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">RX Optical Power</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #00e07e; margin-top: 2px;">{{ $diagnosticData['optical']['rx_power'] ?? '-21.40 dBm' }}</div>
                                    </div>
                                    <div style="background: #131615; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #232826;">
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">TX Optical Power</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #38bdf8; margin-top: 2px;">{{ $diagnosticData['optical']['tx_power'] ?? '+2.35 dBm' }}</div>
                                    </div>
                                    <div style="background: #131615; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #232826;">
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">Suhu Perangkat</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #eef1f0; margin-top: 2px;">{{ $diagnosticData['optical']['temperature'] ?? '42 °C' }}</div>
                                    </div>
                                    <div style="background: #131615; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #232826;">
                                        <div style="font-size: 0.6875rem; color: #9aa49f;">Voltase Supply</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #eef1f0; margin-top: 2px;">{{ $diagnosticData['optical']['voltage'] ?? '3.31 V' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hardware & Firmware Info -->
                            <div style="padding: 1rem; border-radius: 0.625rem; background: #1a1e1c; border: 1px solid #232826; font-size: 0.75rem;">
                                <div style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9aa49f; margin-bottom: 0.5rem;">Informasi Perangkat ONT</div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.5rem; color: #cbd5e1;">
                                    <div>Pabrikan / Merk: <strong style="color: #ffffff;">{{ $diagnosticData['device']['manufacturer'] ?? '-' }}</strong></div>
                                    <div>Model ONT: <strong style="color: #ffffff;">{{ $diagnosticData['device']['product_class'] ?? '-' }}</strong></div>
                                    <div>Serial Number: <strong style="color: #ffffff; font-family: ui-monospace, monospace;">{{ $diagnosticData['device']['serial_number'] ?? '-' }}</strong></div>
                                    <div>Versi Firmware: <strong style="color: #ffffff; font-family: ui-monospace, monospace;">{{ $diagnosticData['device']['software_version'] ?? '-' }}</strong></div>
                                    <div>Uptime Modem: <strong style="color: #00e07e;">{{ $diagnosticData['device']['uptime'] ?? '-' }}</strong></div>
                                    <div>Sinyal Terakhir Inform: <strong style="color: #ffffff;">{{ $diagnosticData['device']['last_inform'] ?? '-' }}</strong></div>
                                </div>
                            </div>

                            <!-- WAN & Wi-Fi Info -->
                            <div style="padding: 1rem; border-radius: 0.625rem; background: #1a1e1c; border: 1px solid #232826; font-size: 0.75rem;">
                                <div style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9aa49f; margin-bottom: 0.5rem;">Koneksi WAN & Status Wi-Fi</div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.5rem; color: #cbd5e1;">
                                    <div>Status WAN: <strong style="color: #00e07e;">{{ $diagnosticData['wan']['status'] ?? 'Connected' }}</strong></div>
                                    <div>IP Address WAN: <strong style="color: #ffffff; font-family: ui-monospace, monospace;">{{ $diagnosticData['wan']['ip_address'] ?? '-' }}</strong></div>
                                    <div>Nama Wi-Fi (SSID): <strong style="color: #ffffff;">{{ $diagnosticData['wifi']['ssid'] ?? '-' }}</strong></div>
                                    <div>Klien Terhubung: <strong style="color: #38bdf8;">{{ $diagnosticData['wifi']['associated_devices'] ?? '-' }}</strong></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div style="padding: 1rem 1.25rem; border-top: 1px solid #232826; background: #171b19; display: flex; justify-content: flex-end;">
                        <button type="button" wire:click="closeDiagnosticModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: #ffffff;">
                            Tutup Diagnosa
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 2: GANTI NAMA & PASSWORD WI-FI (SSID)                     -->
        <!-- ============================================================== -->
        @if ($showWifiModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeWifiModal()">
                <div class="ont-modal-card" style="max-width: 480px;">
                    <!-- Modal Header -->
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #232826; background: #171b19; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">wifi_password</span>
                            </div>
                            <div>
                                <h3 style="font-weight: 700; font-size: 0.9375rem; color: #eef1f0; margin: 0;">Ubah Pengaturan Wi-Fi</h3>
                                <p style="font-size: 0.6875rem; color: #9aa49f; margin: 2px 0 0 0;">Pelanggan: <strong style="color: #ffffff;">{{ $wifiCustomerName }}</strong></p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeWifiModal()" style="color: #9aa49f; background: transparent; border: none; font-size: 1.25rem; cursor: pointer; padding: 0.25rem;">✕</button>
                    </div>

                    <!-- Modal Form -->
                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #eef1f0; margin-bottom: 0.25rem;">Nama Wi-Fi (SSID)</label>
                            <input type="text" wire:model="wifiSsid" placeholder="cth: NetSpeed_Rumah" class="ont-input" style="width: 100%;">
                            <p style="font-size: 0.6875rem; color: #9aa49f; margin: 4px 0 0 0;">Nama jaringan Wi-Fi yang akan dipancarkan oleh modem ONT.</p>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #eef1f0; margin-bottom: 0.25rem;">Password Wi-Fi Baru (WPA2-PSK)</label>
                            <input type="text" wire:model="wifiPassword" placeholder="Biarkan kosong jika tidak ingin diubah" class="ont-input" style="width: 100%; font-family: ui-monospace, monospace;">
                            <p style="font-size: 0.6875rem; color: #9aa49f; margin: 4px 0 0 0;">Minimal 8 karakter. Kosongkan jika hanya ingin mengganti nama SSID.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div style="padding: 1rem 1.25rem; border-top: 1px solid #232826; background: #171b19; display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" wire:click="closeWifiModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: #9aa49f;">
                            Batal
                        </button>

                        <button type="button" wire:click="saveWifi()" wire:loading.attr="disabled"
                            class="ont-btn-action" style="background: #00e07e; color: #06261a; border: none; padding: 0.5rem 1.1rem; font-weight: 800;">
                            <span wire:loading.remove wire:target="saveWifi">💾 Simpan &amp; Terapkan</span>
                            <span wire:loading wire:target="saveWifi">Mengirim ke ONT...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 3: HUBUNGKAN ONT KE PELANGGAN                            -->
        <!-- ============================================================== -->
        @if ($showAssignModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeAssignModal()">
                <div class="ont-modal-card" style="max-width: 480px;">
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #232826; background: #171b19; display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-weight: 700; font-size: 0.9375rem; color: #eef1f0; margin: 0;">Pasangkan Modem ONT ke Pelanggan</h3>
                        <button type="button" wire:click="closeAssignModal()" style="color: #9aa49f; background: transparent; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="background: #1a1e1c; padding: 0.85rem; border-radius: 0.5rem; border: 1px solid #232826;">
                            <div style="font-size: 0.6875rem; color: #9aa49f;">Device ID / Serial Number:</div>
                            <div style="font-family: ui-monospace, monospace; font-size: 0.875rem; font-weight: 700; color: #00e07e; margin-top: 2px;">{{ $assignDeviceId }}</div>
                            <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 4px;">Model: {{ $assignDeviceModel }}</div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #eef1f0; margin-bottom: 0.25rem;">Pilih Akun Pelanggan</label>
                            <select wire:model="targetCustomerId" class="ont-input" style="width: 100%; cursor: pointer;">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach ($this->customerOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->customer_number }})</option>
                                @endforeach
                            </select>
                            <p style="font-size: 0.6875rem; color: #9aa49f; margin: 4px 0 0 0;">Hanya menampilkan pelanggan yang belum memiliki modem terpasang.</p>
                        </div>
                    </div>

                    <div style="padding: 1rem 1.25rem; border-top: 1px solid #232826; background: #171b19; display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" wire:click="closeAssignModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: #9aa49f;">Batal</button>
                        <button type="button" wire:click="assignDevice()" class="ont-btn-action" style="background: #00e07e; color: #06261a; border: none; padding: 0.5rem 1.1rem; font-weight: 800;">Hubungkan</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
