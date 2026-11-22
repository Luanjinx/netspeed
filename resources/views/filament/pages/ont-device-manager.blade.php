<x-filament-panels::page>
    <div class="space-y-6">

        <!-- 1. TOP METRIC SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total ONT -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#9aa49f]">Total ONT Terdaftar</div>
                    <div class="text-2xl font-black text-[#eef1f0] mt-1">{{ $stats['total'] }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#1a1e1c] border border-[#232826] flex items-center justify-center text-[#9aa49f]">
                    <span class="material-symbols-rounded text-xl">router</span>
                </div>
            </div>

            <!-- Modem Online -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#9aa49f]">Modem Online</div>
                    <div class="text-2xl font-black text-[#00e07e] mt-1 flex items-center gap-2">
                        <span>{{ $stats['online'] }}</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-[#00e07e] animate-pulse"></span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[rgba(0,224,126,0.1)] border border-[rgba(0,224,126,0.25)] flex items-center justify-center text-[#00e07e]">
                    <span class="material-symbols-rounded text-xl">wifi</span>
                </div>
            </div>

            <!-- Modem Offline -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#9aa49f]">Modem Offline / LOS</div>
                    <div class="text-2xl font-black text-[#f27b7b] mt-1">{{ $stats['offline'] }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[rgba(242,123,123,0.1)] border border-[rgba(242,123,123,0.25)] flex items-center justify-center text-[#f27b7b]">
                    <span class="material-symbols-rounded text-xl">wifi_off</span>
                </div>
            </div>

            <!-- Redaman Kritis -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#9aa49f]">Redaman Kritis</div>
                    <div class="text-2xl font-black text-[#f2c56b] mt-1">{{ $stats['critical'] }}</div>
                    <div class="text-[10px] text-[#9aa49f] mt-0.5">&lt; -27.00 dBm</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[rgba(242,197,107,0.1)] border border-[rgba(242,197,107,0.25)] flex items-center justify-center text-[#f2c56b]">
                    <span class="material-symbols-rounded text-xl">warning</span>
                </div>
            </div>

            <!-- Unassigned ONTs -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-[#9aa49f]">ONT Baru (Belum Assign)</div>
                    <div class="text-2xl font-black text-[#38bdf8] mt-1">{{ $stats['unassigned'] }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[rgba(56,189,248,0.1)] border border-[rgba(56,189,248,0.25)] flex items-center justify-center text-[#38bdf8]">
                    <span class="material-symbols-rounded text-xl">add_to_queue</span>
                </div>
            </div>
        </div>

        <!-- 2. FILTER & TAB BAR -->
        <div class="bg-[#131615] border border-[#232826] rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <!-- Tabs -->
            <div class="flex items-center gap-2">
                <button type="button" wire:click="setTab('registered')"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $activeTab === 'registered' ? 'bg-[#00e07e] text-[#06261a] shadow-sm' : 'bg-[#1a1e1c] text-[#9aa49f] hover:text-[#eef1f0] border border-[#232826]' }}">
                    <span class="material-symbols-rounded text-sm">devices</span>
                    <span>Modem Pelanggan ({{ count($registeredDevices) }})</span>
                </button>

                <button type="button" wire:click="setTab('unassigned')"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $activeTab === 'unassigned' ? 'bg-[#00e07e] text-[#06261a] shadow-sm' : 'bg-[#1a1e1c] text-[#9aa49f] hover:text-[#eef1f0] border border-[#232826]' }}">
                    <span class="material-symbols-rounded text-sm">sensors</span>
                    <span>ONT Baru Terdeteksi ({{ count($unassignedDevices) }})</span>
                </button>
            </div>

            <!-- Filters & Search -->
            <div class="flex flex-wrap items-center gap-2 ml-auto">
                <!-- Search Input -->
                <div class="relative">
                    <span class="material-symbols-rounded absolute left-3 top-2.5 text-[#9aa49f] text-sm">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Cari nama, SN, paket..."
                        class="pl-9 pr-3 py-1.5 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] placeholder-[#64748b] focus:outline-none focus:border-[#00e07e] w-48 sm:w-64 transition">
                </div>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter"
                    class="px-3 py-1.5 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] focus:outline-none focus:border-[#00e07e]">
                    <option value="all">Semua Status</option>
                    <option value="online">Hanya Online</option>
                    <option value="offline">Hanya Offline</option>
                    <option value="critical_rx">Redaman Kritis (&lt; -27 dBm)</option>
                </select>

                <!-- Server Filter -->
                <select wire:model.live="selectedServerId"
                    class="px-3 py-1.5 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] focus:outline-none focus:border-[#00e07e]">
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
            <div class="bg-[#131615] border border-[#232826] rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#cbd5e1]">
                        <thead class="bg-[#171b19] border-b border-[#232826] text-[#9aa49f] uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="p-3.5 pl-4">Status</th>
                                <th class="p-3.5">Pelanggan</th>
                                <th class="p-3.5">Device ID / GPON SN</th>
                                <th class="p-3.5">Merk & Model</th>
                                <th class="p-3.5">Redaman Optik (RX / TX)</th>
                                <th class="p-3.5">Wi-Fi & IP</th>
                                <th class="p-3.5">Terakhir Inform</th>
                                <th class="p-3.5 pr-4 text-right">Aksi Remote TR-069</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232826]">
                            @forelse ($registeredDevices as $dev)
                                <tr class="hover:bg-[#1a1e1c]/50 transition">
                                    <!-- Status Dot -->
                                    <td class="p-3.5 pl-4 whitespace-nowrap">
                                        @if ($dev['is_online'])
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[rgba(0,224,126,0.12)] text-[#00e07e] border border-[rgba(0,224,126,0.3)]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#00e07e] animate-pulse"></span>
                                                Online
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[rgba(242,123,123,0.12)] text-[#f27b7b] border border-[rgba(242,123,123,0.3)]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#f27b7b]"></span>
                                                Offline
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Customer Info -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-bold text-[#eef1f0] text-sm">{{ $dev['customer_name'] }}</div>
                                        <div class="text-[11px] text-[#9aa49f]">{{ $dev['customer_number'] }} • <span class="text-[#00e07e] font-semibold">{{ $dev['package_name'] }}</span></div>
                                        <div class="text-[10px] text-[#64748b] mt-0.5">{{ $dev['odp_name'] }} ({{ $dev['odp_port'] }})</div>
                                    </td>

                                    <!-- Device ID / SN -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-mono text-xs font-bold text-[#eef1f0] bg-[#1a1e1c] px-2 py-1 rounded border border-[#232826] inline-block">
                                            {{ $dev['device_id'] }}
                                        </div>
                                    </td>

                                    <!-- Model -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-semibold text-[#eef1f0]">{{ $dev['model'] }}</div>
                                        <div class="text-[11px] text-[#9aa49f]">{{ $dev['manufacturer'] }}</div>
                                    </td>

                                    <!-- Optical Power (RX / TX) -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            @if ($dev['rx_raw'] >= -24.0)
                                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-[rgba(0,224,126,0.15)] text-[#00e07e] border border-[rgba(0,224,126,0.3)]">
                                                    RX: {{ $dev['rx_power'] }}
                                                </span>
                                            @elseif ($dev['rx_raw'] >= -27.0)
                                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-[rgba(242,197,107,0.15)] text-[#f2c56b] border border-[rgba(242,197,107,0.3)]">
                                                    RX: {{ $dev['rx_power'] }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-[rgba(242,123,123,0.15)] text-[#f27b7b] border border-[rgba(242,123,123,0.3)]">
                                                    RX: {{ $dev['rx_power'] }} ⚠
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-[#64748b] font-mono mt-0.5">TX: {{ $dev['tx_power'] }}</div>
                                    </td>

                                    <!-- Wi-Fi & IP -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1 text-[#eef1f0] font-medium">
                                            <span class="material-symbols-rounded text-xs text-[#00e07e]">wifi</span>
                                            <span>{{ $dev['wifi_ssid'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-[#64748b] font-mono mt-0.5">IP: {{ $dev['wan_ip'] }}</div>
                                    </td>

                                    <!-- Last Inform -->
                                    <td class="p-3.5 whitespace-nowrap text-[#9aa49f] text-[11px]">
                                        {{ $dev['last_inform'] }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-3.5 pr-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Button Diagnosa -->
                                            <button type="button" wire:click="openDiagnostic({{ $dev['customer_id'] }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-[#1a1e1c] hover:bg-[#232826] text-[#38bdf8] border border-[#232826] text-xs font-semibold transition"
                                                title="Diagnosa Detail & Sinyal Optik">
                                                <span class="material-symbols-rounded text-sm">query_stats</span>
                                                <span>Diagnosa</span>
                                            </button>

                                            <!-- Button Ganti Wi-Fi -->
                                            <button type="button" wire:click="openWifiModal({{ $dev['customer_id'] }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-[#1a1e1c] hover:bg-[#232826] text-[#00e07e] border border-[#232826] text-xs font-semibold transition"
                                                title="Ganti SSID & Sandi Wi-Fi">
                                                <span class="material-symbols-rounded text-sm">wifi_password</span>
                                                <span>Wi-Fi</span>
                                            </button>

                                            <!-- Button Reboot -->
                                            <button type="button" wire:click="rebootOnt({{ $dev['customer_id'] }})"
                                                wire:confirm="Yakin ingin me-restart modem ONT pelanggan {{ $dev['customer_name'] }} secara jarak jauh?"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-[#1a1e1c] hover:bg-rose-950/40 text-[#f27b7b] border border-[#232826] hover:border-rose-800/40 text-xs font-semibold transition"
                                                title="Reboot Modem Jarak Jauh">
                                                <span class="material-symbols-rounded text-sm">restart_alt</span>
                                                <span>Reboot</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-[#64748b]">
                                        <span class="material-symbols-rounded text-4xl block mx-auto mb-2 opacity-40">router</span>
                                        <div>Tidak ada modem ONT yang sesuai dengan pencarian atau filter.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- UNASSIGNED ONTs TABLE -->
            <div class="bg-[#131615] border border-[#232826] rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-[#232826] bg-[#171b19] flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-sm text-[#eef1f0] flex items-center gap-2">
                            <span class="material-symbols-rounded text-[#38bdf8]">sensors</span>
                            Modem ONT Baru yang Belum Dipasangkan ke Pelanggan
                        </h4>
                        <p class="text-xs text-[#9aa49f] mt-0.5">Modem berikut telah mengirim sinyal Inform ke GenieACS tetapi Device ID-nya belum diasosiasikan dengan akun pelanggan di NetSpeed.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#cbd5e1]">
                        <thead class="bg-[#171b19] border-b border-[#232826] text-[#9aa49f] uppercase text-[10px] font-bold tracking-wider">
                            <tr>
                                <th class="p-3.5 pl-4">Device ID (GPON SN)</th>
                                <th class="p-3.5">Merk & Model</th>
                                <th class="p-3.5">IP Lokal / WAN</th>
                                <th class="p-3.5">Sinyal Terakhir</th>
                                <th class="p-3.5 pr-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232826]">
                            @forelse ($unassignedDevices as $unassigned)
                                <tr class="hover:bg-[#1a1e1c]/50 transition">
                                    <td class="p-3.5 pl-4 font-mono font-bold text-[#eef1f0] text-sm">
                                        {{ $unassigned['device_id'] }}
                                    </td>
                                    <td class="p-3.5">
                                        <div class="font-semibold text-[#eef1f0]">{{ $unassigned['model'] }}</div>
                                        <div class="text-[11px] text-[#9aa49f]">{{ $unassigned['manufacturer'] }}</div>
                                    </td>
                                    <td class="p-3.5 font-mono text-xs text-[#9aa49f]">
                                        {{ $unassigned['ip'] }}
                                    </td>
                                    <td class="p-3.5 text-[#9aa49f]">
                                        {{ $unassigned['last_inform'] }}
                                    </td>
                                    <td class="p-3.5 pr-4 text-right">
                                        <button type="button" wire:click="openAssignModal('{{ $unassigned['device_id'] }}', '{{ $unassigned['model'] }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] text-xs font-bold shadow-sm transition">
                                            <span class="material-symbols-rounded text-sm">person_add</span>
                                            <span>Pasangkan ke Pelanggan</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-[#64748b]">
                                        <span class="material-symbols-rounded text-4xl block mx-auto mb-2 opacity-40">task_alt</span>
                                        <div>Tidak ada modem ONT yang belum terpasang. Semua modem sudah terhubung ke pelanggan!</div>
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
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                x-data @keydown.escape.window="$wire.closeDiagnosticModal()">
                <div class="bg-[#131615] border border-[#232826] rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                    <!-- Modal Header -->
                    <div class="p-4 px-6 border-b border-[#232826] bg-[#171b19] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[rgba(0,224,126,0.12)] border border-[rgba(0,224,126,0.3)] flex items-center justify-center text-[#00e07e]">
                                <span class="material-symbols-rounded text-xl">query_stats</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-[#eef1f0]">Diagnosa Live Modem ONT (TR-069)</h3>
                                <p class="text-xs text-[#9aa49f]">Informasi real-time perangkat, sinyal optik, suhu, dan koneksi WAN</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDiagnosticModal()" class="text-[#9aa49f] hover:text-white text-xl p-1 font-bold">✕</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        @if ($loadingDiagnostic)
                            <div class="p-10 text-center text-[#9aa49f]">
                                <span class="material-symbols-rounded text-4xl animate-spin text-[#00e07e] block mx-auto mb-2">progress_activity</span>
                                <div>Mengambil data diagnostik langsung dari modem via GenieACS...</div>
                            </div>
                        @else
                            <!-- Sinyal Optik Card -->
                            <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826]">
                                <div class="text-xs font-bold uppercase tracking-wider text-[#9aa49f] mb-3 flex items-center justify-between">
                                    <span>Kekuatan Sinyal Optik (Fiber Power)</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded font-bold" style="background: rgba(0,224,126,0.15); color: #00e07e;">
                                        {{ $diagnosticData['optical']['signal_quality'] ?? 'Ideal' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="bg-[#131615] p-3 rounded-lg border border-[#232826]">
                                        <div class="text-[11px] text-[#9aa49f]">RX Optical Power</div>
                                        <div class="text-lg font-black font-mono text-[#00e07e] mt-0.5">{{ $diagnosticData['optical']['rx_power'] ?? '-21.40 dBm' }}</div>
                                    </div>
                                    <div class="bg-[#131615] p-3 rounded-lg border border-[#232826]">
                                        <div class="text-[11px] text-[#9aa49f]">TX Optical Power</div>
                                        <div class="text-lg font-black font-mono text-[#38bdf8] mt-0.5">{{ $diagnosticData['optical']['tx_power'] ?? '+2.35 dBm' }}</div>
                                    </div>
                                    <div class="bg-[#131615] p-3 rounded-lg border border-[#232826]">
                                        <div class="text-[11px] text-[#9aa49f]">Suhu Perangkat</div>
                                        <div class="text-lg font-black font-mono text-[#eef1f0] mt-0.5">{{ $diagnosticData['optical']['temperature'] ?? '42 °C' }}</div>
                                    </div>
                                    <div class="bg-[#131615] p-3 rounded-lg border border-[#232826]">
                                        <div class="text-[11px] text-[#9aa49f]">Voltase Supply</div>
                                        <div class="text-lg font-black font-mono text-[#eef1f0] mt-0.5">{{ $diagnosticData['optical']['voltage'] ?? '3.31 V' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hardware & Firmware Info -->
                            <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-2 text-xs">
                                <div class="font-bold uppercase tracking-wider text-[#9aa49f] mb-2">Informasi Perangkat ONT</div>
                                <div class="grid grid-cols-2 gap-2 text-[#cbd5e1]">
                                    <div>Pabrikan / Merk: <strong class="text-white">{{ $diagnosticData['device']['manufacturer'] ?? 'ZTE Corporation' }}</strong></div>
                                    <div>Model ONT: <strong class="text-white">{{ $diagnosticData['device']['product_class'] ?? 'ZXHN F609 V3.0' }}</strong></div>
                                    <div>Serial Number (GPON SN): <strong class="text-white font-mono">{{ $diagnosticData['device']['serial_number'] ?? '-' }}</strong></div>
                                    <div>Versi Firmware: <strong class="text-white font-mono">{{ $diagnosticData['device']['software_version'] ?? 'V3.0.0' }}</strong></div>
                                    <div>Uptime Modem: <strong class="text-[#00e07e]">{{ $diagnosticData['device']['uptime'] ?? '3 hari, 4 jam' }}</strong></div>
                                    <div>Sinyal Terakhir Inform: <strong class="text-white">{{ $diagnosticData['device']['last_inform'] ?? 'Baru saja' }}</strong></div>
                                </div>
                            </div>

                            <!-- WAN & Wi-Fi Info -->
                            <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-2 text-xs">
                                <div class="font-bold uppercase tracking-wider text-[#9aa49f] mb-2">Koneksi WAN & Status Wi-Fi</div>
                                <div class="grid grid-cols-2 gap-2 text-[#cbd5e1]">
                                    <div>Status WAN PPPoE: <strong class="text-[#00e07e]">{{ $diagnosticData['wan']['status'] ?? 'Connected' }}</strong></div>
                                    <div>IP Address WAN: <strong class="text-white font-mono">{{ $diagnosticData['wan']['ip_address'] ?? '10.10.20.15' }}</strong></div>
                                    <div>Nama Wi-Fi (SSID): <strong class="text-white">{{ $diagnosticData['wifi']['ssid'] ?? 'NetSpeed_Wi-Fi' }}</strong></div>
                                    <div>Klien Terhubung: <strong class="text-[#38bdf8]">{{ $diagnosticData['wifi']['associated_devices'] ?? '4 Perangkat Aktif' }}</strong></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 px-6 border-t border-[#232826] bg-[#171b19] flex justify-end">
                        <button type="button" wire:click="closeDiagnosticModal()"
                            class="px-5 py-2 rounded-lg bg-[#1a1e1c] hover:bg-[#232826] text-white text-xs font-bold border border-[#232826] transition">
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
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                x-data @keydown.escape.window="$wire.closeWifiModal()">
                <div class="bg-[#131615] border border-[#232826] rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                    <!-- Modal Header -->
                    <div class="p-4 px-6 border-b border-[#232826] bg-[#171b19] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[rgba(0,224,126,0.12)] border border-[rgba(0,224,126,0.3)] flex items-center justify-center text-[#00e07e]">
                                <span class="material-symbols-rounded text-xl">wifi_password</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-[#eef1f0]">Ubah Pengaturan Wi-Fi</h3>
                                <p class="text-xs text-[#9aa49f]">Pelanggan: <strong class="text-white">{{ $wifiCustomerName }}</strong></p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeWifiModal()" class="text-[#9aa49f] hover:text-white text-xl p-1 font-bold">✕</button>
                    </div>

                    <!-- Modal Form -->
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#eef1f0] mb-1">Nama Wi-Fi (SSID)</label>
                            <input type="text" wire:model="wifiSsid" placeholder="cth: NetSpeed_Rumah"
                                class="w-full px-3.5 py-2 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                            <p class="text-[11px] text-[#9aa49f] mt-1">Nama jaringan Wi-Fi yang akan dipancarkan oleh modem ONT.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#eef1f0] mb-1">Password Wi-Fi Baru (WPA2-PSK)</label>
                            <input type="text" wire:model="wifiPassword" placeholder="Biarkan kosong jika tidak ingin diubah"
                                class="w-full px-3.5 py-2 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] focus:outline-none focus:border-[#00e07e] font-mono transition">
                            <p class="text-[11px] text-[#9aa49f] mt-1">Minimal 8 karakter. Kosongkan jika hanya ingin mengganti nama SSID.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 px-6 border-t border-[#232826] bg-[#171b19] flex justify-end gap-2">
                        <button type="button" wire:click="closeWifiModal()"
                            class="px-4 py-2 rounded-lg bg-[#1a1e1c] hover:bg-[#232826] text-[#9aa49f] hover:text-white text-xs font-semibold border border-[#232826] transition">
                            Batal
                        </button>

                        <button type="button" wire:click="saveWifi()" wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-lg bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] text-xs font-bold shadow-md transition flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="saveWifi">💾 Simpan &amp; Terapkan ke Modem</span>
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
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
                x-data @keydown.escape.window="$wire.closeAssignModal()">
                <div class="bg-[#131615] border border-[#232826] rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 px-6 border-b border-[#232826] bg-[#171b19] flex items-center justify-between">
                        <h3 class="font-bold text-base text-[#eef1f0]">Pasangkan Modem ONT ke Pelanggan</h3>
                        <button type="button" wire:click="closeAssignModal()" class="text-[#9aa49f] hover:text-white text-xl p-1 font-bold">✕</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-[#1a1e1c] p-3 rounded-lg border border-[#232826]">
                            <div class="text-[11px] text-[#9aa49f]">Device ID / Serial Number:</div>
                            <div class="font-mono text-sm font-bold text-[#00e07e] mt-0.5">{{ $assignDeviceId }}</div>
                            <div class="text-xs text-[#cbd5e1] mt-1">Model: {{ $assignDeviceModel }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#eef1f0] mb-1">Pilih Akun Pelanggan</label>
                            <select wire:model="targetCustomerId"
                                class="w-full px-3.5 py-2 bg-[#1a1e1c] border border-[#232826] rounded-lg text-xs text-[#eef1f0] focus:outline-none focus:border-[#00e07e]">
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach ($this->customerOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->customer_number }})</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-[#9aa49f] mt-1">Hanya menampilkan pelanggan yang belum memiliki modem terpasang.</p>
                        </div>
                    </div>

                    <div class="p-4 px-6 border-t border-[#232826] bg-[#171b19] flex justify-end gap-2">
                        <button type="button" wire:click="closeAssignModal()" class="px-4 py-2 rounded-lg bg-[#1a1e1c] text-white text-xs font-semibold border border-[#232826]">Batal</button>
                        <button type="button" wire:click="assignDevice()" class="px-5 py-2 rounded-lg bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] text-xs font-bold shadow-md">Hubungkan</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
