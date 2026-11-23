<x-filament-panels::page>
    <style>
        .ont-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }

        /* Server Connection Banner */
        .ont-banner-alert {
            background: rgba(242, 197, 107, 0.08);
            border: 1px solid rgba(242, 197, 107, 0.28);
            border-radius: 0.875rem;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .ont-banner-connected {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* 3 Essential KPI Cards */
        .ont-kpi-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .ont-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            transition: border-color 0.2s ease;
        }

        .ont-card:hover {
            border-color: #38423e;
        }

        .ont-card-title {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .ont-card-number {
            font-size: 1.75rem;
            font-weight: 800;
            margin-top: 0.25rem;
            font-family: 'Plus Jakarta Sans', ui-monospace, monospace;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ont-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Tabs & Filter Bar */
        .ont-toolbar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 0.75rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.875rem;
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
            padding: 0.45rem 0.95rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .ont-tab-active {
            background: #00e07e;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 224, 126, 0.25);
        }

        .ont-tab-inactive {
            background: var(--surface2);
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .ont-tab-inactive:hover {
            color: var(--text);
            border-color: #38423e;
        }

        .ont-input {
            padding: 0.45rem 0.85rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease;
        }

        .ont-input:focus {
            border-color: #00e07e;
        }

        /* Simplified 5-Column Table */
        .ont-table-card {
            background: var(--surface);
            border: 1px solid var(--border);
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
            color: var(--muted);
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .ont-td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border);
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
            background: var(--surface2);
            border: 1px solid var(--border);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ont-btn-action:hover {
            background: var(--border);
        }

        /* Modals */
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
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            width: 100%;
            max-width: 580px;
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

    <div class="ont-wrapper">

        <!-- 1. GENIEACS CONNECTION STATUS BANNER -->
        @if (!$isGenieacsConnected)
            <div class="ont-banner-alert">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #f2c56b; font-size: 26px;">cable</span>
                    <div>
                        <strong style="color: #f2c56b; font-size: 0.875rem;">Server GenieACS Belum Terhubung</strong>
                        <div style="color: var(--muted); font-size: 0.75rem; margin-top: 2px;">
                            Masukkan IP STB Armbian Anda (port 7557) agar status modem, sinyal optik (dBm), dan ganti sandi Wi-Fi aktif.
                        </div>
                    </div>
                </div>
                <button type="button" wire:click="openServerConfigModal()" class="ont-btn-action" style="background: #f2c56b; color: var(--surface); border: none; padding: 0.45rem 0.95rem; font-weight: 800;">
                    <span class="material-symbols-rounded" style="font-size: 16px;">settings_ethernet</span>
                    <span>Hubungkan Server Sekarang</span>
                </button>
            </div>
        @else
            <div class="ont-banner-connected">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="ont-pulse-dot" style="background: #00e07e; animation: pulse 2s infinite;"></span>
                    <span style="font-size: 0.8125rem; color: var(--text); font-weight: 600;">
                        GenieACS Terhubung: <code style="color: #00e07e; font-family: ui-monospace, monospace; background: var(--surface2); padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid var(--border);">{{ $activeServerUrl }}</code>
                    </span>
                    <span style="font-size: 0.75rem; color: var(--muted);">(NBI Port 7557 Aktif)</span>
                </div>
                <button type="button" wire:click="openServerConfigModal()" class="ont-btn-action" style="color: var(--muted); font-size: 0.75rem;">
                    <span class="material-symbols-rounded" style="font-size: 15px;">settings</span>
                    <span>Ubah IP Server</span>
                </button>
            </div>
        @endif

        <!-- 2. HANYA 3 KARTU UTAMA (RINGKAS & JELAS) -->
        <div class="ont-kpi-3">
            <!-- Modem Online -->
            <div class="ont-card">
                <div>
                    <div class="ont-card-title">Modem Online</div>
                    <div class="ont-card-number" style="color: #00e07e;">
                        <span>{{ $stats['online'] }}</span>
                        <span class="ont-pulse-dot" style="background: #00e07e;"></span>
                    </div>
                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Dari total {{ $stats['total'] }} modem terdaftar</div>
                </div>
                <div class="ont-card-icon" style="background: rgba(0,224,126,0.1); border: 1px solid rgba(0,224,126,0.25); color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">wifi</span>
                </div>
            </div>

            <!-- Modem Offline / LOS -->
            <div class="ont-card">
                <div>
                    <div class="ont-card-title">Modem Offline / LOS</div>
                    <div class="ont-card-number" style="color: #f27b7b;">{{ $stats['offline'] }}</div>
                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Mati lampu / kabel optik terputus</div>
                </div>
                <div class="ont-card-icon" style="background: rgba(242,123,123,0.1); border: 1px solid rgba(242,123,123,0.25); color: #f27b7b;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">wifi_off</span>
                </div>
            </div>

            <!-- Redaman Kritis -->
            <div class="ont-card">
                <div>
                    <div class="ont-card-title">Redaman Kritis</div>
                    <div class="ont-card-number" style="color: #f2c56b;">
                        <span>{{ $stats['critical'] }}</span>
                    </div>
                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Sinyal drop &lt; -27.00 dBm (Perlu perbaikan)</div>
                </div>
                <div class="ont-card-icon" style="background: rgba(242,197,107,0.1); border: 1px solid rgba(242,197,107,0.25); color: #f2c56b;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">warning</span>
                </div>
            </div>
        </div>

        <!-- 3. TABS & FILTER BAR -->
        <div class="ont-toolbar">
            <!-- Tabs -->
            <div class="ont-tab-group">
                <button type="button" wire:click="setTab('registered')"
                    class="ont-tab-btn {{ $activeTab === 'registered' ? 'ont-tab-active' : 'ont-tab-inactive' }}">
                    <span class="material-symbols-rounded" style="font-size: 17px;">devices</span>
                    <span>Modem Pelanggan ({{ count($registeredDevices) }})</span>
                </button>

                <button type="button" wire:click="setTab('unassigned')"
                    class="ont-tab-btn {{ $activeTab === 'unassigned' ? 'ont-tab-active' : 'ont-tab-inactive' }}">
                    <span class="material-symbols-rounded" style="font-size: 17px;">sensors</span>
                    <span>ONT Baru Terdeteksi ({{ count($unassignedDevices) }})</span>
                    @if (count($unassignedDevices) > 0)
                        <span style="font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 9999px; background: #38bdf8; color: #082f49;">NEW</span>
                    @endif
                </button>
            </div>

            <!-- Search & Status Filter -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 9px; font-size: 16px; color: var(--muted);">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Cari nama, SN, paket..."
                        class="ont-input" style="padding-left: 2rem; width: 220px;">
                </div>

                <select wire:model.live="statusFilter" class="ont-input" style="cursor: pointer;">
                    <option value="all">Semua Status</option>
                    <option value="online">Hanya Online</option>
                    <option value="offline">Hanya Offline</option>
                    <option value="critical_rx">Redaman Kritis</option>
                </select>
            </div>
        </div>

        <!-- 4. TABEL RINGKAS 5 KOLOM (TIDAK LAGI KEPANJANGAN) -->
        @if ($activeTab === 'registered')
            <div class="ont-table-card">
                <div style="overflow-x: auto; width: 100%;">
                    <table class="ont-table">
                        <thead>
                            <tr>
                                <th class="ont-th" style="padding-left: 1.25rem;">Pelanggan &amp; Status</th>
                                <th class="ont-th">Modem &amp; Serial Number</th>
                                <th class="ont-th">Sinyal Optik (RX)</th>
                                <th class="ont-th">Wi-Fi &amp; IP</th>
                                <th class="ont-th" style="padding-right: 1.25rem; text-align: right;">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registeredDevices as $dev)
                                <tr class="ont-tr">
                                    <!-- 1. Pelanggan & Status -->
                                    <td class="ont-td" style="padding-left: 1.25rem; white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            @if ($dev['is_online'])
                                                <span class="ont-pulse-dot" style="background: #00e07e;" title="Online"></span>
                                            @else
                                                <span class="ont-pulse-dot" style="background: #f27b7b;" title="Offline"></span>
                                            @endif
                                            <div>
                                                <div style="font-weight: 700; color: var(--text); font-size: 0.875rem;">{{ $dev['customer_name'] }}</div>
                                                <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 1px;">
                                                    {{ $dev['customer_number'] }} • <span style="color: #00e07e; font-weight: 600;">{{ $dev['package_name'] }}</span>
                                                </div>
                                                <div style="font-size: 0.625rem; color: #64748b; margin-top: 1px;">{{ $dev['odp_name'] }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Modem & SN -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="font-weight: 600; color: var(--text);">{{ $dev['model'] }}</div>
                                        <div style="font-family: ui-monospace, monospace; font-size: 0.725rem; color: #00e07e; background: var(--surface2); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid var(--border); display: inline-block; margin-top: 2px;">
                                            SN: {{ $dev['device_id'] }}
                                        </div>
                                    </td>

                                    <!-- 3. Sinyal Optik (RX Power) -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        @if ($dev['rx_raw'] !== null)
                                            @if ($dev['rx_raw'] >= -24.0)
                                                <span style="padding: 0.2rem 0.55rem; border-radius: 0.35rem; font-family: ui-monospace, monospace; font-weight: 800; font-size: 0.8125rem; background: rgba(0,224,126,0.15); color: #00e07e; border: 1px solid rgba(0,224,126,0.3);">
                                                    {{ $dev['rx_power'] }}
                                                </span>
                                                <span style="font-size: 0.6875rem; color: #00e07e; font-weight: 600; margin-left: 4px;">(Bagus)</span>
                                            @elseif ($dev['rx_raw'] >= -27.0)
                                                <span style="padding: 0.2rem 0.55rem; border-radius: 0.35rem; font-family: ui-monospace, monospace; font-weight: 800; font-size: 0.8125rem; background: rgba(242,197,107,0.15); color: #f2c56b; border: 1px solid rgba(242,197,107,0.3);">
                                                    {{ $dev['rx_power'] }}
                                                </span>
                                                <span style="font-size: 0.6875rem; color: #f2c56b; font-weight: 600; margin-left: 4px;">(Cukup)</span>
                                            @else
                                                <span style="padding: 0.2rem 0.55rem; border-radius: 0.35rem; font-family: ui-monospace, monospace; font-weight: 800; font-size: 0.8125rem; background: rgba(242,123,123,0.15); color: #f27b7b; border: 1px solid rgba(242,123,123,0.3);">
                                                    {{ $dev['rx_power'] }} ⚠
                                                </span>
                                                <span style="font-size: 0.6875rem; color: #f27b7b; font-weight: 600; margin-left: 4px;">(Kritis)</span>
                                            @endif
                                        @else
                                            <span style="color: #64748b; font-size: 0.75rem;">- Belum terbaca -</span>
                                        @endif
                                    </td>

                                    <!-- 4. Wi-Fi & IP -->
                                    <td class="ont-td" style="white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: var(--text); font-weight: 600;">
                                            <span class="material-symbols-rounded" style="font-size: 15px; color: #00e07e;">wifi</span>
                                            <span>{{ $dev['wifi_ssid'] }}</span>
                                        </div>
                                        <div style="font-size: 0.6875rem; color: var(--muted); font-family: ui-monospace, monospace; margin-top: 2px;">IP: {{ $dev['wan_ip'] }}</div>
                                    </td>

                                    <!-- 5. Aksi Cepat -->
                                    <td class="ont-td" style="padding-right: 1.25rem; white-space: nowrap; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <button type="button" wire:click="openDiagnostic({{ $dev['customer_id'] }})"
                                                class="ont-btn-action" style="color: #38bdf8;" title="Cek Suhu, Voltase & Detail">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">query_stats</span>
                                                <span>Diagnosa</span>
                                            </button>

                                            <button type="button" wire:click="openWifiModal({{ $dev['customer_id'] }})"
                                                class="ont-btn-action" style="color: #00e07e;" title="Ganti Nama/Password Wi-Fi">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">wifi_password</span>
                                                <span>Wi-Fi</span>
                                            </button>

                                            <button type="button" wire:click="rebootOnt({{ $dev['customer_id'] }})"
                                                wire:confirm="Restart modem pelanggan {{ $dev['customer_name'] }}?"
                                                class="ont-btn-action" style="color: #f27b7b;" title="Restart Modem">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">restart_alt</span>
                                                <span>Reboot</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                        <span class="material-symbols-rounded" style="font-size: 40px; display: block; margin: 0 auto 0.5rem auto; opacity: 0.4;">router</span>
                                        <div style="font-size: 0.875rem; color: var(--text); font-weight: 600;">Belum ada modem ONT yang terhubung</div>
                                        <div style="font-size: 0.75rem; color: var(--muted); margin-top: 4px;">
                                            Modem pelanggan baru yang dicolokkan akan otomatis masuk ke tab <strong>"ONT Baru Terdeteksi"</strong>.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- TAB UNASSIGNED ONTS -->
            <div class="ont-table-card">
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2);">
                    <h4 style="font-weight: 700; font-size: 0.875rem; color: var(--text); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 18px; color: #38bdf8;">sensors</span>
                        <span>Daftar Modem Baru yang Belum Dipasangkan ke Pelanggan</span>
                    </h4>
                    <p style="font-size: 0.75rem; color: var(--muted); margin: 0.25rem 0 0 0;">
                        Modem ini baru dicolok kabel optik dan otomatis terdeteksi oleh GenieACS. Klik tombol untuk mengikatnya ke pelanggan.
                    </p>
                </div>

                <div style="overflow-x: auto; width: 100%;">
                    <table class="ont-table">
                        <thead>
                            <tr>
                                <th class="ont-th" style="padding-left: 1.25rem;">Serial Number (GPON SN)</th>
                                <th class="ont-th">Merk &amp; Model</th>
                                <th class="ont-th">IP Address</th>
                                <th class="ont-th">Terdeteksi Pada</th>
                                <th class="ont-th" style="padding-right: 1.25rem; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unassignedDevices as $unassigned)
                                <tr class="ont-tr">
                                    <td class="ont-td" style="padding-left: 1.25rem; font-family: ui-monospace, monospace; font-weight: 700; color: #00e07e; font-size: 0.875rem;">
                                        {{ $unassigned['device_id'] }}
                                    </td>
                                    <td class="ont-td">
                                        <div style="font-weight: 600; color: var(--text);">{{ $unassigned['model'] }}</div>
                                        <div style="font-size: 0.6875rem; color: var(--muted);">{{ $unassigned['manufacturer'] }}</div>
                                    </td>
                                    <td class="ont-td" style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: var(--muted);">
                                        {{ $unassigned['ip'] }}
                                    </td>
                                    <td class="ont-td" style="color: var(--muted); font-size: 0.75rem;">
                                        {{ $unassigned['last_inform'] }}
                                    </td>
                                    <td class="ont-td" style="padding-right: 1.25rem; text-align: right;">
                                        <button type="button" wire:click="openAssignModal('{{ $unassigned['device_id'] }}', '{{ $unassigned['model'] }}')"
                                            class="ont-btn-action" style="background: #00e07e; color: #ffffff; border: none; padding: 0.45rem 0.85rem; font-weight: 800;">
                                            <span class="material-symbols-rounded" style="font-size: 15px;">person_add</span>
                                            <span>Pasangkan ke Pelanggan</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                        <span class="material-symbols-rounded" style="font-size: 40px; display: block; margin: 0 auto 0.5rem auto; opacity: 0.4;">task_alt</span>
                                        <div style="font-size: 0.875rem; color: var(--text); font-weight: 600;">Tidak ada modem baru yang belum terpasang.</div>
                                        <div style="font-size: 0.75rem; color: var(--muted); margin-top: 4px;">
                                            Saat teknisi memasang modem baru di rumah pelanggan, modem akan otomatis muncul di sini.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 1: PENGATURAN KONEKSI SERVER GENIEACS                     -->
        <!-- ============================================================== -->
        @if ($showServerModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeServerConfigModal()">
                <div class="ont-modal-card">
                    <!-- Header -->
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">settings_ethernet</span>
                            </div>
                            <div>
                                <h3 style="font-weight: 700; font-size: 0.9375rem; color: var(--text); margin: 0;">Hubungkan Server GenieACS (STB / VPS)</h3>
                                <p style="font-size: 0.6875rem; color: var(--muted); margin: 2px 0 0 0;">Konfigurasi API NBI agar NetSpeed terhubung ke GenieACS</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeServerConfigModal()" style="color: var(--muted); background: transparent; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <!-- Form -->
                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem;">Nama Server</label>
                            <input type="text" wire:model="serverName" placeholder="cth: GenieACS STB Armbian" class="ont-input" style="width: 100%;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem;">
                                Base URL API NBI GenieACS <span style="color: #f27b7b;">*</span>
                            </label>
                            <input type="text" wire:model="serverUrl" placeholder="http://192.168.3.1:7557" class="ont-input" style="width: 100%; font-family: ui-monospace, monospace;">
                            <p style="font-size: 0.6875rem; color: #00e07e; margin: 4px 0 0 0;">
                                💡 <strong>Penting:</strong> Gunakan port <strong>7557</strong> (Port API NBI GenieACS). Contoh: <code>http://192.168.3.1:7557</code> atau <code>http://127.0.0.1:7557</code>.
                            </p>
                        </div>

                        <!-- Live Test Status Box -->
                        @if ($serverTestStatus === 'success')
                            <div style="padding: 0.75rem; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); color: #00e07e; font-size: 0.75rem; font-weight: 600;">
                                ✓ {{ $serverTestMessage }}
                            </div>
                        @elseif ($serverTestStatus === 'error')
                            <div style="padding: 0.75rem; border-radius: 0.5rem; background: rgba(242,123,123,0.12); border: 1px solid rgba(242,123,123,0.3); color: #f27b7b; font-size: 0.75rem; font-weight: 600;">
                                ✕ {{ $serverTestMessage }}
                            </div>
                        @endif
                    </div>

                    <!-- Footer with Test & Save Buttons -->
                    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <button type="button" wire:click="testServerConnection()" wire:loading.attr="disabled"
                            class="ont-btn-action" style="padding: 0.5rem 0.9rem; color: #38bdf8;">
                            <span wire:loading.remove wire:target="testServerConnection" class="material-symbols-rounded" style="font-size: 16px;">wifi_tethering</span>
                            <span wire:loading wire:target="testServerConnection" class="material-symbols-rounded" style="font-size: 16px; animation: spin 1s linear infinite;">sync</span>
                            <span>Tes Koneksi</span>
                        </button>

                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" wire:click="closeServerConfigModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: var(--muted);">Batal</button>
                            <button type="button" wire:click="saveServerConfig()"
                                class="ont-btn-action" style="background: #00e07e; color: #ffffff; border: none; padding: 0.5rem 1.2rem; font-weight: 800;">
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 2: DIAGNOSA DETAIL ONT                                   -->
        <!-- ============================================================== -->
        @if ($showDiagnosticModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeDiagnosticModal()">
                <div class="ont-modal-card" style="max-width: 620px;">
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">query_stats</span>
                            </div>
                            <div>
                                <h3 style="font-weight: 700; font-size: 0.9375rem; color: var(--text); margin: 0;">Diagnosa Detail Modem ONT</h3>
                                <p style="font-size: 0.6875rem; color: var(--muted); margin: 2px 0 0 0;">Sinyal optik, suhu, voltase &amp; status PPPoE</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDiagnosticModal()" style="color: var(--muted); background: transparent; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; max-height: 70vh; overflow-y: auto;">
                        @if ($loadingDiagnostic)
                            <div style="padding: 2.5rem; text-align: center; color: var(--muted);">
                                <span class="material-symbols-rounded" style="font-size: 32px; color: #00e07e; animation: spin 1s linear infinite; display: block; margin: 0 auto 0.5rem auto;">sync</span>
                                <div>Mengambil data sinyal langsung dari modem via GenieACS...</div>
                            </div>
                        @else
                            <!-- Sinyal Optik -->
                            <div style="padding: 1rem; border-radius: 0.625rem; background: var(--surface2); border: 1px solid var(--border);">
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 0.75rem;">
                                    Kekuatan Sinyal Optik (Optical Fiber)
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem;">
                                    <div style="background: var(--surface); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                                        <div style="font-size: 0.6875rem; color: var(--muted);">RX Power</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #00e07e; margin-top: 2px;">{{ $diagnosticData['optical']['rx_power'] ?? '-' }}</div>
                                    </div>
                                    <div style="background: var(--surface); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                                        <div style="font-size: 0.6875rem; color: var(--muted);">TX Power</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: #38bdf8; margin-top: 2px;">{{ $diagnosticData['optical']['tx_power'] ?? '-' }}</div>
                                    </div>
                                    <div style="background: var(--surface); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                                        <div style="font-size: 0.6875rem; color: var(--muted);">Suhu Chip</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: var(--text); margin-top: 2px;">{{ $diagnosticData['optical']['temperature'] ?? '-' }}</div>
                                    </div>
                                    <div style="background: var(--surface); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                                        <div style="font-size: 0.6875rem; color: var(--muted);">Voltase</div>
                                        <div style="font-size: 1.125rem; font-weight: 800; font-family: ui-monospace, monospace; color: var(--text); margin-top: 2px;">{{ $diagnosticData['optical']['voltage'] ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hardware & Firmware -->
                            <div style="padding: 1rem; border-radius: 0.625rem; background: var(--surface2); border: 1px solid var(--border); font-size: 0.75rem;">
                                <div style="font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem;">Informasi Hardware &amp; Uptime</div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.5rem; color: var(--muted);">
                                    <div>Pabrikan / Merk: <strong style="color: var(--text);">{{ $diagnosticData['device']['manufacturer'] ?? '-' }}</strong></div>
                                    <div>Model: <strong style="color: var(--text);">{{ $diagnosticData['device']['product_class'] ?? '-' }}</strong></div>
                                    <div>Serial Number: <strong style="color: var(--text); font-family: ui-monospace, monospace;">{{ $diagnosticData['device']['serial_number'] ?? '-' }}</strong></div>
                                    <div>Firmware: <strong style="color: var(--text); font-family: ui-monospace, monospace;">{{ $diagnosticData['device']['software_version'] ?? '-' }}</strong></div>
                                    <div>Uptime Modem: <strong style="color: #00e07e;">{{ $diagnosticData['device']['uptime'] ?? '-' }}</strong></div>
                                    <div>Terakhir Inform: <strong style="color: var(--text);">{{ $diagnosticData['device']['last_inform'] ?? '-' }}</strong></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--surface2); display: flex; justify-content: flex-end;">
                        <button type="button" wire:click="closeDiagnosticModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: var(--text);">Tutup</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 3: GANTI NAMA & PASSWORD WI-FI (SSID)                     -->
        <!-- ============================================================== -->
        @if ($showWifiModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeWifiModal()">
                <div class="ont-modal-card" style="max-width: 480px;">
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0,224,126,0.12); border: 1px solid rgba(0,224,126,0.3); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">wifi_password</span>
                            </div>
                            <div>
                                <h3 style="font-weight: 700; font-size: 0.9375rem; color: var(--text); margin: 0;">Ganti Nama &amp; Password Wi-Fi</h3>
                                <p style="font-size: 0.6875rem; color: var(--muted); margin: 2px 0 0 0;">Pelanggan: <strong style="color: #ffffff;">{{ $wifiCustomerName }}</strong></p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeWifiModal()" style="color: var(--muted); background: transparent; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem;">Nama Wi-Fi (SSID)</label>
                            <input type="text" wire:model="wifiSsid" placeholder="cth: NetSpeed_Rumah" class="ont-input" style="width: 100%;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem;">Password Wi-Fi Baru</label>
                            <input type="text" wire:model="wifiPassword" placeholder="Biarkan kosong jika tidak ingin ganti password" class="ont-input" style="width: 100%; font-family: ui-monospace, monospace;">
                            <p style="font-size: 0.6875rem; color: var(--muted); margin: 4px 0 0 0;">Minimal 8 karakter.</p>
                        </div>
                    </div>

                    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--surface2); display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" wire:click="closeWifiModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: var(--muted);">Batal</button>
                        <button type="button" wire:click="saveWifi()" wire:loading.attr="disabled"
                            class="ont-btn-action" style="background: #00e07e; color: #ffffff; border: none; padding: 0.5rem 1.1rem; font-weight: 800;">
                            <span wire:loading.remove wire:target="saveWifi">💾 Terapkan ke Modem</span>
                            <span wire:loading wire:target="saveWifi">Mengirim via TR-069...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- MODAL 4: HUBUNGKAN ONT KE PELANGGAN                            -->
        <!-- ============================================================== -->
        @if ($showAssignModal)
            <div class="ont-modal-backdrop" x-data @keydown.escape.window="$wire.closeAssignModal()">
                <div class="ont-modal-card" style="max-width: 480px;">
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-weight: 700; font-size: 0.9375rem; color: var(--text); margin: 0;">Pasangkan Modem ke Akun Pelanggan</h3>
                        <button type="button" wire:click="closeAssignModal()" style="color: var(--muted); background: transparent; border: none; font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="background: var(--surface2); padding: 0.85rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                            <div style="font-size: 0.6875rem; color: var(--muted);">Serial Number (GPON SN):</div>
                            <div style="font-family: ui-monospace, monospace; font-size: 0.875rem; font-weight: 700; color: #00e07e; margin-top: 2px;">{{ $assignDeviceId }}</div>
                            <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 4px;">Model: {{ $assignDeviceModel }}</div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.25rem;">Pilih Akun Pelanggan</label>
                            <select wire:model="targetCustomerId" class="ont-input" style="width: 100%; cursor: pointer;">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach ($this->customerOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->customer_number }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--surface2); display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" wire:click="closeAssignModal()" class="ont-btn-action" style="padding: 0.5rem 1rem; color: var(--muted);">Batal</button>
                        <button type="button" wire:click="assignDevice()" class="ont-btn-action" style="background: #00e07e; color: #ffffff; border: none; padding: 0.5rem 1.1rem; font-weight: 800;">Pasangkan</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
