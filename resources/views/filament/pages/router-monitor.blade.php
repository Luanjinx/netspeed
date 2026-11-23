<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;" @if ($autoRefresh) wire:poll.5s="loadData" @endif>
        <!-- Top Router Selector & Actions Bar -->
        <div class="fi-section noc-router-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.875rem; flex-wrap: wrap;">
                <div class="noc-icon-box" style="width: 38px; height: 38px; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">router</span>
                </div>
                <div>
                    <label style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: block; margin-bottom: 2px;">
                        Pilih Router MikroTik Core
                    </label>
                    <select wire:model.live="selectedRouterId" style="font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; padding: 0.4rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none; cursor: pointer;">
                        @foreach (\App\Models\Router::where('is_active', true)->get() as $r)
                            <option value="{{ $r->id }}" style="background: var(--surface2); color: var(--text);">{{ $r->name }} ({{ $r->host }}:{{ $r->port }})</option>
                        @endforeach
                    </select>
                </div>

                @if ($isConnected)
                    <span class="noc-pill noc-pill-success" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 6px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); border-radius: 9999px; font-weight: 700; white-space: nowrap;">
                        <span class="noc-dot noc-dot-success" style="width: 7px; height: 7px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                        Terhubung ke RouterOS
                    </span>
                    @if ($isSimulated)
                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.35rem 0.65rem; border-radius: 9999px; background: var(--surface2); border: 1px solid var(--border); color: var(--muted); white-space: nowrap;">
                            Preview Data
                        </span>
                    @endif
                @else
                    <span class="noc-pill noc-pill-danger" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; display: inline-flex; align-items: center; gap: 6px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3); border-radius: 9999px; font-weight: 700; white-space: nowrap;">
                        <span class="noc-dot noc-dot-danger" style="width: 7px; height: 7px; border-radius: 9999px; background: #f27b7b; display: inline-block;"></span>
                        Terputus
                    </span>
                @endif
            </div>

            <!-- Dynamic Action Buttons by Active Tab -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                <!-- Auto-Refresh Toggle Button -->
                <button type="button"
                        wire:click="toggleAutoRefresh"
                        style="height: 38px; padding: 0 0.95rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; @if ($autoRefresh) background: rgba(0, 224, 126, 0.15); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.4); @else background: var(--surface2); color: var(--text); border: 1px solid var(--border); @endif">
                    <span class="material-symbols-rounded" style="font-size: 17px; @if ($autoRefresh) color: #00e07e; @else color: var(--muted); @endif">bolt</span>
                    <span>{{ $autoRefresh ? 'Auto-Refresh Aktif (5s)' : 'Aktifkan Auto-Refresh' }}</span>
                    @if ($autoRefresh)
                        <span style="width: 6px; height: 6px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                    @endif
                </button>

                <!-- Perbarui Data Button (Sync Icon - Not Letter C) -->
                <button type="button"
                        wire:click="refreshData"
                        wire:loading.attr="disabled"
                        style="height: 38px; padding: 0 1rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: #00e07e; color: #06261a; border: 1px solid #00e07e; box-shadow: 0 2px 8px rgba(0, 224, 126, 0.25);">
                    <span wire:loading.remove wire:target="refreshData" class="material-symbols-rounded" style="font-size: 18px; color: #06261a; font-weight: 700;">sync</span>
                    <span wire:loading wire:target="refreshData" class="material-symbols-rounded" style="font-size: 18px; color: #06261a; animation: spin 1s linear infinite;">sync</span>
                    <span>Perbarui Data</span>
                </button>

                @if ($activeTab === 'router')
                    <!-- Reboot Router Button -->
                    <button type="button"
                            wire:click="rebootRouter"
                            wire:confirm="Reboot router MikroTik ini? Semua koneksi internet pelanggan akan terputus sementara."
                            style="height: 38px; padding: 0 0.95rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: rgba(244, 63, 94, 0.12); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.3);">
                        <span class="material-symbols-rounded" style="font-size: 17px; color: #f43f5e;">restart_alt</span>
                        <span>Reboot Router</span>
                    </button>
                @elseif ($activeTab === 'pppoe')
                    <button type="button"
                            wire:click="importAllPppoeSecrets"
                            wire:loading.attr="disabled"
                            style="height: 38px; padding: 0 0.95rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: var(--surface2); color: var(--text); border: 1px solid var(--border);">
                        <span class="material-symbols-rounded" style="font-size: 17px; color: #38bdf8;">cloud_download</span>
                        <span>Tarik PPPoE ke Pelanggan</span>
                    </button>
                    <button type="button"
                            wire:click="pushAllPppoeCustomers"
                            wire:loading.attr="disabled"
                            style="height: 38px; padding: 0 1rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: #00e07e; color: #06261a; border: 1px solid #00e07e; box-shadow: 0 2px 8px rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 18px; color: #06261a; font-weight: 700;">cloud_upload</span>
                        <span>Kirim Pelanggan ke MikroTik</span>
                    </button>
                @elseif ($activeTab === 'hotspot')
                    <button type="button"
                            wire:click="pullHotspotProfiles"
                            wire:loading.attr="disabled"
                            style="height: 38px; padding: 0 0.95rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: var(--surface2); color: var(--text); border: 1px solid var(--border);">
                        <span class="material-symbols-rounded" style="font-size: 17px; color: #38bdf8;">cloud_download</span>
                        <span>Tarik Profil ke Database</span>
                    </button>
                    <button type="button"
                            wire:click="syncHotspotVoucherStatus"
                            wire:loading.attr="disabled"
                            style="height: 38px; padding: 0 1rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; background: #00e07e; color: #06261a; border: 1px solid #00e07e; box-shadow: 0 2px 8px rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 18px; color: #06261a; font-weight: 700;">sync</span>
                        <span>Sinkron Status Voucher</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Unified Navigation Button Tabs -->
        <div class="noc-tabs-bar">
            <button type="button"
                    wire:click="setTab('router')"
                    class="noc-tab-btn {{ $activeTab === 'router' ? 'active' : '' }}"
                    style="display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="font-size: 19px;">monitoring</span>
                <span>Sumber Daya &amp; Trafik Interface</span>
            </button>

            <button type="button"
                    wire:click="setTab('pppoe')"
                    class="noc-tab-btn {{ $activeTab === 'pppoe' ? 'active' : '' }}"
                    style="display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="font-size: 19px;">wifi_tethering</span>
                <span>Monitoring Real-time PPPoE</span>
                @if (count($pppoeActiveSessions) > 0)
                    <span class="noc-tab-badge">{{ count($pppoeActiveSessions) }} Online</span>
                @endif
            </button>

            <button type="button"
                    wire:click="setTab('hotspot')"
                    class="noc-tab-btn {{ $activeTab === 'hotspot' ? 'active' : '' }}"
                    style="display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="font-size: 19px;">wifi</span>
                <span>Monitoring Real-time Hotspot</span>
                @if (count($hotspotActiveSessions) > 0)
                    <span class="noc-tab-badge">{{ count($hotspotActiveSessions) }} Online</span>
                @endif
            </button>
        </div>

        @if (!$isConnected)
            <div class="noc-subcard noc-subcard-alert" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="font-size: 26px; color: #f43f5e;">warning</span>
                    <div>
                        <strong style="font-size: 0.9375rem; color: #f43f5e;">Gagal Menghubungkan ke MikroTik:</strong>
                        <div style="margin-top: 0.25rem; font-size: 0.8125rem; color: #a1a1aa;">
                            {{ $errorMessage ?: 'Pastikan IP, port API (8728), username, dan password router sudah benar serta firewall MikroTik mengizinkan koneksi API.' }}
                        </div>
                    </div>
                </div>
            </div>
        @else

            {{-- =========================================================================
                 TAB 1: SUMBER DAYA & TRAFIK INTERFACE MIKROTIK
                 ========================================================================= --}}
            @if ($activeTab === 'router')
                <!-- Overview Stat Cards -->
                <div class="isp-grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    {{-- 1. CPU Load --}}
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                        <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                                <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; display: inline-block; @if (($systemResources['cpu_load'] ?? 0) > 80) background: #f27b7b; @elseif (($systemResources['cpu_load'] ?? 0) > 50) background: #f2c56b; @else background: #00e07e; @endif"></span>
                                <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Beban CPU (Load)</span>
                            </div>
                            <div class="noc-metric-icon" style="width: 30px; height: 30px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                            </div>
                        </div>

                        <div class="noc-card-body" style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; font-family: 'Plus Jakarta Sans', ui-monospace, monospace; @if (($systemResources['cpu_load'] ?? 0) > 80) color: #f27b7b; @elseif (($systemResources['cpu_load'] ?? 0) > 50) color: #f2c56b; @else color: #00e07e; @endif">
                                {{ $systemResources['cpu_load'] ?? 0 }}<span style="font-size: 1rem; font-weight: 700; margin-left: 2px;">%</span>
                            </div>
                            <div class="noc-track" style="width: 100%; height: 6px; border-radius: 9999px; background: var(--surface2); border: 1px solid var(--border); overflow: hidden; margin-top: 0.25rem;">
                                <div class="noc-bar" style="height: 100%; border-radius: 9999px; width: {{ min(100, max(5, $systemResources['cpu_load'] ?? 0)) }}%; @if (($systemResources['cpu_load'] ?? 0) > 80) background: linear-gradient(90deg, #f27b7b, #ef4444); @elseif (($systemResources['cpu_load'] ?? 0) > 50) background: linear-gradient(90deg, #f2c56b, #eab308); @else background: linear-gradient(90deg, #00e07e, #4deda4); @endif"></div>
                            </div>
                        </div>

                        <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem; margin-top: 0.2rem;">
                            <span class="noc-footer-item" style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="color: var(--muted);">Core:</span> <strong style="color: var(--text);">{{ $systemResources['cpu_count'] ?? 1 }} Core</strong>
                            </span>
                            <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: var(--muted);">
                                {{ !empty($systemResources['cpu_frequency']) ? (is_numeric($systemResources['cpu_frequency']) ? $systemResources['cpu_frequency'] . ' MHz' : $systemResources['cpu_frequency']) : '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- 2. RAM / Memory --}}
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                        <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                                <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; display: inline-block; background: #38bdf8;"></span>
                                <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">RAM / Memory Free</span>
                            </div>
                            <div class="noc-metric-icon" style="width: 30px; height: 30px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                                <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>

                        <div class="noc-card-body" style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; font-family: 'Plus Jakarta Sans', ui-monospace, monospace; color: #38bdf8;">
                                {{ $systemResources['free_memory'] ?? '-' }}
                            </div>
                            <div class="noc-track" style="width: 100%; height: 6px; border-radius: 9999px; background: var(--surface2); border: 1px solid var(--border); overflow: hidden; margin-top: 0.25rem;">
                                <div class="noc-bar" style="height: 100%; border-radius: 9999px; width: {{ min(100, max(5, $systemResources['memory_percentage'] ?? 20)) }}%; background: linear-gradient(90deg, #38bdf8, #00e07e);"></div>
                            </div>
                        </div>

                        <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem; margin-top: 0.2rem;">
                            <span class="noc-footer-item" style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="color: var(--muted);">Total:</span> <strong style="color: var(--text);">{{ $systemResources['total_memory'] ?? '-' }}</strong>
                            </span>
                            <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8;">
                                Terpakai: {{ $systemResources['memory_percentage'] ?? 0 }}%
                            </span>
                        </div>
                    </div>

                    {{-- 3. Uptime Sistem --}}
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                        <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                                <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; display: inline-block; background: #00e07e;"></span>
                                <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Uptime Sistem</span>
                            </div>
                            <div class="noc-metric-icon" style="width: 30px; height: 30px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                                <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>

                        <div class="noc-card-body" style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <div class="noc-metric-val" style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em; line-height: 1.15; font-family: ui-monospace, SFMono-Regular, monospace; color: var(--text);">
                                {{ $systemResources['uptime'] ?? '-' }}
                            </div>
                            <div style="font-size: 0.725rem; color: #00e07e; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                <svg style="width: 13px; height: 13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Router Aktif &amp; Normal</span>
                            </div>
                        </div>

                        <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem; margin-top: 0.2rem;">
                            <span class="noc-footer-item" style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="color: var(--muted);">RouterOS:</span>
                            </span>
                            <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: rgba(0, 224, 126, 0.1); border: 1px solid rgba(0, 224, 126, 0.3); color: #00e07e;">
                                v{{ $systemResources['version'] ?? 'RouterOS' }}
                            </span>
                        </div>
                    </div>

                    {{-- 4. Suhu & Daya Board --}}
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                        <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                                <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; display: inline-block; background: #f2c56b;"></span>
                                <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Suhu &amp; Daya Board</span>
                            </div>
                            <div class="noc-metric-icon" style="width: 30px; height: 30px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #f2c56b;">
                                <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>

                        <div class="noc-card-body" style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <div class="noc-metric-val" style="display: flex; align-items: baseline; gap: 8px; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                                <span style="color: #f2c56b;">{{ $systemResources['temperature'] ?? 'Normal' }}</span>
                                @if(!empty($systemResources['voltage']) && $systemResources['voltage'] !== '-')
                                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--muted);">({{ $systemResources['voltage'] }})</span>
                                @endif
                            </div>
                            <div style="font-size: 0.725rem; color: var(--muted); margin-top: 2px;">
                                Termal perangkat beroperasi aman
                            </div>
                        </div>

                        <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem; margin-top: 0.2rem;">
                            <span class="noc-footer-item" style="color: var(--muted);">
                                Hardware:
                            </span>
                            <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $systemResources['board_name'] ?? 'MikroTik Router' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Table 1: Live Interface Traffic Monitoring -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #00e07e;">swap_vert</span>
                            <div>
                                <h3 class="noc-title">
                                    Monitoring Trafik Interface MikroTik (Real-Time Bandwidth)
                                </h3>
                                <p class="noc-subtitle">
                                    Pantau laju unduh (RX) dan unggah (TX) pada masing-masing port fisik, SFP, dan VLAN.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                            Total Interface: <strong>{{ count($interfaces) }}</strong>
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Nama Interface</th>
                                    <th>Tipe</th>
                                    <th>Status Link</th>
                                    <th style="color: #10b981;">Live Download (RX Rate)</th>
                                    <th style="color: #38bdf8;">Live Upload (TX Rate)</th>
                                    <th>Total Akumulasi (RX / TX)</th>
                                    <th>Total Paket</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($interfaces as $iface)
                                    <tr>
                                        <td>
                                            <div style="font-family: ui-monospace, monospace; font-weight: 700; color: currentColor;">
                                                {{ $iface['name'] }}
                                            </div>
                                            @if (!empty($iface['comment']))
                                                <div style="font-size: 0.6875rem; color: #71717a; margin-top: 0.15rem;">
                                                    {{ $iface['comment'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="noc-pill noc-pill-neutral" style="text-transform: uppercase;">
                                                {{ $iface['type'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($iface['disabled'])
                                                <span class="noc-pill noc-pill-neutral">Disabled</span>
                                            @elseif ($iface['running'])
                                                <span class="noc-pill noc-pill-success">
                                                    <span class="noc-dot noc-dot-success"></span>
                                                    Link Up
                                                </span>
                                            @else
                                                <span class="noc-pill noc-pill-danger">
                                                    <span class="noc-dot noc-dot-danger"></span>
                                                    Down / No Link
                                                </span>
                                            @endif
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.95rem; font-weight: 800; color: #10b981;">
                                            {{ $iface['rx_rate'] }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.95rem; font-weight: 800; color: #38bdf8;">
                                            {{ $iface['tx_rate'] }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #a1a1aa;">
                                            <div>RX: <strong style="color: currentColor;">{{ $iface['rx_byte'] }}</strong></div>
                                            <div style="color: #71717a;">TX: <strong style="color: currentColor;">{{ $iface['tx_byte'] }}</strong></div>
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #71717a;">
                                            <div>{{ $iface['rx_packet'] }} pkts</div>
                                            <div style="font-size: 0.6875rem;">{{ $iface['tx_packet'] }} pkts</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="padding: 2rem; text-align: center; color: #71717a;">
                                            Tidak ada data interface yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Table 2: Top Bandwidth Consumers -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #f2c56b;">trending_up</span>
                            <div>
                                <h3 class="noc-title">
                                    Top 5 Pelanggan Pemakaian Bandwidth Terbesar Saat Ini
                                </h3>
                                <p class="noc-subtitle">
                                    Deteksi dini pengguna dengan pemakaian trafik tinggi atau potensi pemakaian berlebih.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Nama / User</th>
                                    <th>IP Address</th>
                                    <th>Paket</th>
                                    <th>Uptime Sesi</th>
                                    <th style="color: #10b981;">Download Rate</th>
                                    <th style="color: #38bdf8;">Upload Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topConsumers as $user)
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: currentColor;">
                                                {{ $user['name'] ?? $user['username'] }}
                                            </div>
                                            <div style="font-size: 0.6875rem; font-family: ui-monospace, monospace; color: #71717a;">
                                                {{ $user['username'] }}
                                            </div>
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.8125rem;">{{ $user['ip'] }}</td>
                                        <td>
                                            <span class="noc-pill noc-pill-primary">
                                                {{ $user['package'] ?? 'PPPoE Client' }}
                                            </span>
                                        </td>
                                        <td style="font-size: 0.8125rem;">{{ $user['uptime'] }}</td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.95rem; font-weight: 800; color: #10b981;">
                                            {{ $user['rx_rate'] }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 0.95rem; font-weight: 800; color: #38bdf8;">
                                            {{ $user['tx_rate'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2rem; text-align: center; color: #71717a;">
                                            Belum ada data pemakaian trafik aktif saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            {{-- =========================================================================
                 TAB 2: MONITORING REAL-TIME PPPOE MIKROTIK
                 ========================================================================= --}}
            @elseif ($activeTab === 'pppoe')
                <!-- Overview Stat Cards -->
                <div class="isp-grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Sesi Online Aktif</div>
                                <div class="isp-stat-number" style="color: #00e07e; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($pppoeActiveSessions) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">User</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(0, 224, 126, 0.15); color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">wifi_tethering</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Total PPPoE Secrets</div>
                                <div class="isp-stat-number" style="color: #38bdf8; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($pppoeSecrets) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Akun</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">vpn_key</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Profil Paket PPPoE</div>
                                <div class="isp-stat-number" style="color: #f2c56b; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($pppoeProfiles) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Profil</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(242, 197, 107, 0.15); color: #f2c56b;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Beban CPU Router</div>
                                <div class="isp-stat-number" style="color: #00e07e; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ $systemResources['cpu_load'] ?? 0 }}%
                                </div>
                                <div style="font-size: 0.6875rem; color: #71717a; margin-top: 0.2rem;">
                                    Uptime: {{ $systemResources['uptime'] ?? '-' }}
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(0, 224, 126, 0.15); color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">memory</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table 1: PPPoE Active Sessions -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #00e07e;">online_prediction</span>
                            <div>
                                <h3 class="noc-title">
                                    Sesi PPPoE Aktif Saat Ini (Online)
                                </h3>
                                <p class="noc-subtitle">
                                    Daftar koneksi tunnel PPPoE yang sedang terhubung dari pelanggan.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                            Total Online: <strong>{{ count($pppoeActiveSessions) }}</strong>
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Username PPPoE</th>
                                    <th>IP Address</th>
                                    <th>MAC / Caller-ID</th>
                                    <th>Uptime Koneksi</th>
                                    <th>Service</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pppoeActiveSessions as $sess)
                                    <tr>
                                        <td style="font-family: ui-monospace, monospace; font-weight: 800; color: #10b981;">
                                            {{ $sess['name'] ?? '-' }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace;">
                                            {{ $sess['address'] ?? '-' }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; color: #a1a1aa;">
                                            {{ $sess['caller-id'] ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $sess['uptime'] ?? '-' }}
                                        </td>
                                        <td>
                                            <span class="noc-pill noc-pill-neutral">
                                                {{ $sess['service'] ?? 'pppoe' }}
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button"
                                                    wire:click="kickPppoeSession('{{ $sess['name'] }}')"
                                                    wire:confirm="Putuskan sesi user {{ $sess['name'] }}?"
                                                    class="noc-btn"
                                                    style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border-color: rgba(244, 63, 94, 0.3); cursor: pointer; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                                Putuskan (Kick)
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                            Tidak ada sesi PPPoE yang sedang aktif (Semua pelanggan sedang offline).
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Table 2: PPPoE Secrets List -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #38bdf8;">vpn_key</span>
                            <div>
                                <h3 class="noc-title">
                                    Daftar PPPoE Secrets di MikroTik (/ppp/secret)
                                </h3>
                                <p class="noc-subtitle">
                                    Kredensial akun pelanggan PPPoE yang terdaftar pada MikroTik.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                            Total Akun: <strong>{{ count($pppoeSecrets) }}</strong>
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>Profile Paket</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Keterangan / Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pppoeSecrets as $sec)
                                    @php
                                        $isDisabled = ($sec['disabled'] ?? 'no') === 'yes';
                                        $isIsolir = stripos($sec['profile'] ?? '', 'isolir') !== false;
                                    @endphp
                                    <tr>
                                        <td style="font-weight: 700; color: currentColor;">
                                            {{ $sec['name'] ?? '-' }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; color: #71717a;">
                                            ••••••
                                        </td>
                                        <td>
                                            <span class="noc-pill {{ $isIsolir ? 'noc-pill-danger' : 'noc-pill-primary' }}">
                                                {{ $sec['profile'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.75rem; text-transform: uppercase; color: #a1a1aa;">
                                                {{ $sec['service'] ?? 'any' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($isDisabled)
                                                <span class="noc-pill noc-pill-neutral">Disabled</span>
                                            @elseif ($isIsolir)
                                                <span class="noc-pill noc-pill-danger">Diisolir</span>
                                            @else
                                                <span class="noc-pill noc-pill-success">
                                                    <span class="noc-dot noc-dot-success"></span>
                                                    Aktif
                                                </span>
                                            @endif
                                        </td>
                                        <td style="font-size: 0.75rem; color: #71717a;">
                                            {{ $sec['comment'] ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                            Belum ada PPPoE Secret di router ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            {{-- =========================================================================
                 TAB 3: MONITORING REAL-TIME HOTSPOT & PROFIL MIKROTIK
                 ========================================================================= --}}
            @elseif ($activeTab === 'hotspot')
                <!-- Overview Stat Cards -->
                <div class="isp-grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Pengguna Hotspot Aktif</div>
                                <div class="isp-stat-number" style="color: #00e07e; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($hotspotActiveSessions) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Device</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(0, 224, 126, 0.15); color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">wifi</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Profil Hotspot MikroTik</div>
                                <div class="isp-stat-number" style="color: #38bdf8; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($hotspotProfiles) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Profil</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Total User di MikroTik</div>
                                <div class="isp-stat-number" style="color: #4deda4; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($hotspotUsers) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">User</span>
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(77, 237, 164, 0.15); color: #4deda4;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">group</span>
                            </div>
                        </div>
                    </div>

                    <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">Server Hotspot</div>
                                <div class="isp-stat-number" style="color: #f2c56b; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                    {{ count($hotspotServers) }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Server</span>
                                </div>
                                <div style="font-size: 0.6875rem; color: #71717a; margin-top: 0.2rem;">
                                    CPU: {{ $systemResources['cpu_load'] ?? 0 }}%
                                </div>
                            </div>
                            <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">settings</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. Real-time Active Sessions Table -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #00e07e;">wifi</span>
                            <div>
                                <h3 class="noc-title">
                                    Sesi Hotspot Aktif Saat Ini (/ip/hotspot/active)
                                </h3>
                                <p class="noc-subtitle">
                                    Perangkat voucher/tamu yang sedang online dan terhubung ke hotspot.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                            {{ count($hotspotActiveSessions) }} Online
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>User (Voucher)</th>
                                    <th>IP Address</th>
                                    <th>MAC Address</th>
                                    <th>Uptime</th>
                                    <th>Sisa Waktu</th>
                                    <th>Trafik (In / Out)</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hotspotActiveSessions as $session)
                                    <tr>
                                        <td style="font-family: ui-monospace, monospace; font-weight: 800; color: currentColor;">
                                            {{ $session['user'] ?? '-' }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace;">
                                            {{ $session['address'] ?? '-' }}
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; color: #a1a1aa;">
                                            {{ $session['mac_address'] ?? '-' }}
                                        </td>
                                        <td style="color: #10b981; font-weight: 600;">
                                            {{ $session['uptime'] ?? '-' }}
                                        </td>
                                        <td style="color: #818cf8; font-weight: 600;">
                                            {{ $session['session_time_left'] ?? '-' }}
                                        </td>
                                        <td style="font-size: 0.75rem; color: #a1a1aa;">
                                            ↓ {{ number_format(($session['bytes_in'] ?? 0) / (1024 * 1024), 2) }} MB /
                                            ↑ {{ number_format(($session['bytes_out'] ?? 0) / (1024 * 1024), 2) }} MB
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button"
                                                    wire:click="kickHotspotSession('{{ $session['user'] ?? $session['mac_address'] }}')"
                                                    wire:confirm="Yakin ingin memutuskan koneksi pengguna ini?"
                                                    class="noc-btn"
                                                    style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border-color: rgba(244, 63, 94, 0.3); cursor: pointer; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                                Kick
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                            Tidak ada pengguna hotspot yang sedang online saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Hotspot User Profiles Table -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #38bdf8;">tune</span>
                            <div>
                                <h3 class="noc-title">
                                    Daftar Profil Hotspot MikroTik (/ip/hotspot/user/profile)
                                </h3>
                                <p class="noc-subtitle">
                                    Daftar profil paket bandwidth dan batas waktu hotspot di MikroTik.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                            {{ count($hotspotProfiles) }} Profil
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Nama Profil</th>
                                    <th>Kecepatan (Rate-Limit)</th>
                                    <th>Shared Users</th>
                                    <th>Session Timeout</th>
                                    <th>Keepalive Timeout</th>
                                    <th>Status Autorefresh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hotspotProfiles as $prof)
                                    <tr>
                                        <td style="font-weight: 700;">
                                            <span class="noc-pill noc-pill-primary">
                                                {{ $prof['name'] }}
                                            </span>
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; color: currentColor;">
                                            {{ $prof['rate_limit'] ?: 'Unlimited / Default' }}
                                        </td>
                                        <td>
                                            {{ $prof['shared_users'] }} User
                                        </td>
                                        <td style="color: #a1a1aa;">
                                            {{ $prof['session_timeout'] ?: '-' }}
                                        </td>
                                        <td style="color: #a1a1aa;">
                                            {{ $prof['keepalive_timeout'] ?: '-' }}
                                        </td>
                                        <td style="color: #a1a1aa;">
                                            {{ $prof['status_autorefresh'] ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                            Tidak ada profil hotspot yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Hotspot Users in MikroTik -->
                <div class="fi-section" style="overflow: hidden;">
                    <div class="noc-header-bar" style="padding: 1rem 1.25rem;">
                        <div class="noc-title-group">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: #4deda4;">group</span>
                            <div>
                                <h3 class="noc-title">
                                    User Hotspot Terdaftar di MikroTik (/ip/hotspot/user)
                                </h3>
                                <p class="noc-subtitle">
                                    Semua akun voucher dan user hotspot yang tersimpan di MikroTik.
                                </p>
                            </div>
                        </div>
                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                            {{ count($hotspotUsers) }} Total User
                        </span>
                    </div>

                    <div style="overflow-x: auto; max-height: 24rem;">
                        <table class="isp-table-clean">
                            <thead>
                                <tr>
                                    <th>Username / Kode</th>
                                    <th>Profil</th>
                                    <th>Limit Uptime</th>
                                    <th>Uptime Terpakai</th>
                                    <th>Bytes In / Out</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hotspotUsers as $u)
                                    <tr>
                                        <td style="font-family: ui-monospace, monospace; font-weight: 700; color: currentColor;">
                                            {{ $u['name'] }}
                                        </td>
                                        <td>
                                            <span class="noc-pill noc-pill-neutral">
                                                {{ $u['profile'] }}
                                            </span>
                                        </td>
                                        <td style="color: #818cf8; font-weight: 600;">
                                            {{ $u['limit_uptime'] ?: 'Unlimited' }}
                                        </td>
                                        <td style="color: #a1a1aa;">
                                            {{ $u['uptime'] ?: '0s' }}
                                        </td>
                                        <td style="font-size: 0.75rem; color: #a1a1aa;">
                                            ↓ {{ number_format(($u['bytes_in'] ?? 0) / 1024, 1) }} KB /
                                            ↑ {{ number_format(($u['bytes_out'] ?? 0) / 1024, 1) }} KB
                                        </td>
                                        <td style="font-size: 0.75rem; color: #71717a;">
                                            {{ $u['comment'] ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                            Belum ada user hotspot di MikroTik.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        @endif
    </div>
</x-filament-panels::page>
