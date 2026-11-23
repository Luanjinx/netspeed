<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Router Selection & Status Bar -->
        <div class="fi-section noc-router-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.875rem; flex-wrap: wrap; flex: 1; min-width: 250px;">
                <div class="noc-icon-box" style="width: 36px; height: 36px; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">router</span>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: block;">
                        Router MikroTik Core PPPoE
                    </label>
                    <select wire:model.live="selectedRouterId" style="width: 100%; margin-top: 0.25rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; padding: 0.4rem 0.75rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                        @foreach (\App\Models\Router::where('is_active', true)->get() as $r)
                            <option value="{{ $r->id }}" style="background: var(--surface2); color: var(--text);">{{ $r->name }} ({{ $r->host }}:{{ $r->port }})</option>
                        @endforeach
                    </select>
                </div>

                @if ($isConnected)
                    <span class="noc-pill noc-pill-success" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); border-radius: 9999px; font-weight: 700;">
                        <span class="noc-dot noc-dot-success" style="width: 7px; height: 7px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                        Terhubung ke PPPoE Server
                    </span>
                @else
                    <span class="noc-pill noc-pill-danger" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3); border-radius: 9999px; font-weight: 700;">
                        <span class="noc-dot noc-dot-danger" style="width: 7px; height: 7px; border-radius: 9999px; background: #f27b7b; display: inline-block;"></span>
                        Koneksi Terputus
                    </span>
                @endif
            </div>

            <!-- Quick Action Buttons -->
            <div class="noc-card-actions" style="display: flex; align-items: center; gap: 0.5rem;">
                <button type="button" wire:click="refreshData" wire:loading.attr="disabled" class="noc-btn noc-btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.45rem 0.85rem; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); font-size: 0.8125rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                    <span wire:loading.remove wire:target="refreshData" class="material-symbols-rounded" style="font-size: 16px; color: #00e07e;">refresh</span>
                    <span wire:loading wire:target="refreshData" class="material-symbols-rounded" style="font-size: 16px; color: #00e07e; animation: spin 1s linear infinite;">sync</span>
                    <span>Segarkan Data</span>
                </button>
            </div>
        </div>

        @if (!$isConnected)
            <div class="noc-subcard noc-subcard-alert" style="padding: 1.25rem; background: #1a1414; border: 1px solid rgba(242, 123, 123, 0.3); border-radius: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.875rem;">
                    <span class="material-symbols-rounded" style="font-size: 28px; color: #f27b7b;">warning</span>
                    <div>
                        <strong style="font-size: 0.9375rem; color: #f27b7b;">Gagal Menghubungkan ke Router MikroTik:</strong>
                        <div style="margin-top: 0.25rem; font-size: 0.8125rem; color: var(--muted);">
                            {{ $errorMessage ?: 'Pastikan IP, port API (8728), username, dan password router sudah benar serta firewall MikroTik mengizinkan koneksi API.' }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- PPPoE Stat Cards -->
            <div class="isp-grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                {{-- Card 1: Sesi Online Aktif --}}
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                    <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                            <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                            <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Sesi PPPoE Online</span>
                        </div>
                        <div class="noc-metric-icon" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">wifi_tethering</span>
                        </div>
                    </div>
                    <div class="noc-card-body">
                        <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: #00e07e; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                            {{ count($activeSessions) }} <span style="font-size: 0.8rem; font-weight: 600; color: var(--muted);">Pelanggan</span>
                        </div>
                    </div>
                    <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem;">
                        <span>Tunnel aktif real-time</span>
                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: rgba(0, 224, 126, 0.1); border: 1px solid rgba(0, 224, 126, 0.3); color: #00e07e;">Online</span>
                    </div>
                </div>

                {{-- Card 2: Total PPPoE Secrets --}}
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                    <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                            <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; background: #38bdf8; display: inline-block;"></span>
                            <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Total Akun PPPoE</span>
                        </div>
                        <div class="noc-metric-icon" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">vpn_key</span>
                        </div>
                    </div>
                    <div class="noc-card-body">
                        <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: #38bdf8; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                            {{ count($secrets) }} <span style="font-size: 0.8rem; font-weight: 600; color: var(--muted);">Akun</span>
                        </div>
                    </div>
                    <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem;">
                        <span>Kredensial di MikroTik</span>
                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: #38bdf8;">/ppp/secret</span>
                    </div>
                </div>

                {{-- Card 3: Pelanggan Ter-FUP --}}
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                    <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                            <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; background: {{ $totalFupThrottled > 0 ? '#f27b7b' : '#00e07e' }}; display: inline-block;"></span>
                            <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Pelanggan Limit FUP</span>
                        </div>
                        <div class="noc-metric-icon" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: {{ $totalFupThrottled > 0 ? '#f27b7b' : '#00e07e' }};">
                            <span class="material-symbols-rounded" style="font-size: 18px;">{{ $totalFupThrottled > 0 ? 'speed' : 'check_circle' }}</span>
                        </div>
                    </div>
                    <div class="noc-card-body">
                        <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: {{ $totalFupThrottled > 0 ? '#f27b7b' : '#00e07e' }}; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                            {{ $totalFupThrottled }} <span style="font-size: 0.8rem; font-weight: 600; color: var(--muted);">Pelanggan</span>
                        </div>
                    </div>
                    <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem;">
                        <span>{{ $totalFupThrottled > 0 ? 'Batas kuota tercapai' : 'Semua kuota normal' }}</span>
                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: {{ $totalFupThrottled > 0 ? '#f27b7b' : '#00e07e' }};">FUP Status</span>
                    </div>
                </div>

                {{-- Card 4: Profil Paket PPPoE --}}
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                    <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                            <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; background: #f2c56b; display: inline-block;"></span>
                            <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Profil Kecepatan</span>
                        </div>
                        <div class="noc-metric-icon" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #f2c56b;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                        </div>
                    </div>
                    <div class="noc-card-body">
                        <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: #f2c56b; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                            {{ count($profiles) }} <span style="font-size: 0.8rem; font-weight: 600; color: var(--muted);">Paket</span>
                        </div>
                    </div>
                    <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem;">
                        <span>Profil bandwidth router</span>
                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: #f2c56b;">/ppp/profile</span>
                    </div>
                </div>

                {{-- Card 5: Beban CPU Router --}}
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.75rem;">
                    <div class="noc-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="noc-card-label-wrap" style="display: flex; align-items: center; gap: 0.45rem;">
                            <span class="noc-pulse-dot" style="width: 7px; height: 7px; border-radius: 9999px; background: #4deda4; display: inline-block;"></span>
                            <span class="noc-card-label" style="font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Beban CPU Router</span>
                        </div>
                        <div class="noc-metric-icon" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #4deda4;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">memory</span>
                        </div>
                    </div>
                    <div class="noc-card-body">
                        <div class="noc-metric-val" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: #4deda4; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">
                            {{ $routerInfo['cpu-load'] ?? 0 }}%
                        </div>
                    </div>
                    <div class="noc-card-footer" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.725rem; color: var(--muted); border-top: 1px solid var(--border); padding-top: 0.55rem;">
                        <span style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $routerInfo['board-name'] ?? ($routerInfo['platform'] ?? 'RouterOS') }}</span>
                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text);">v{{ $routerInfo['version'] ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- PPPoE Secrets Management (/ppp/secret) -->
            <div class="fi-section" style="overflow: hidden; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
                <div class="noc-header-bar" style="padding: 1.15rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border);">
                    <div class="noc-title-group" style="display: flex; align-items: center; gap: 0.875rem;">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                            <span class="material-symbols-rounded" style="font-size: 20px;">shield_person</span>
                        </div>
                        <div>
                            <h3 class="noc-title" style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0;">
                                Manajemen PPPoE Secrets di MikroTik (/ppp/secret)
                            </h3>
                            <p class="noc-subtitle" style="font-size: 0.775rem; color: var(--muted); margin: 3px 0 0 0;">
                                Kelola kredensial akun pelanggan PPPoE, profil bandwidth, status isolir, IP tunnel, dan kuota FUP.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <div style="position: relative;">
                            <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 16px; color: var(--muted);">search</span>
                            <input type="text"
                                   wire:model.live.debounce.300ms="searchQuery"
                                   placeholder="Cari username, profile, atau pelanggan..."
                                   style="font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.45rem 0.75rem 0.45rem 2.1rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); min-width: 250px; outline: none;">
                        </div>

                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.7rem; font-weight: 700; padding: 0.4rem 0.75rem; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); color: #00e07e;">
                            Total: <strong>{{ count($secrets) }}</strong> Akun
                        </span>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="isp-table-clean" style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; text-align: left;">
                        <thead>
                            <tr style="background: var(--surface2);">
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Pelanggan / Username</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Password</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Profil Paket</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">IP Tunnel (Remote)</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Kuota &amp; FUP</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Status Akun</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Keterangan Pelanggan</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); text-align: right; white-space: nowrap;">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $filteredSecrets = $secrets;
                                if (!empty($searchQuery)) {
                                    $filteredSecrets = array_filter($secrets, function ($s) use ($searchQuery) {
                                        return stripos($s['name'] ?? '', $searchQuery) !== false ||
                                               stripos($s['profile'] ?? '', $searchQuery) !== false ||
                                               stripos($s['comment'] ?? '', $searchQuery) !== false;
                                    });
                                }
                            @endphp

                            @forelse ($filteredSecrets as $sec)
                                @php
                                    $username = $sec['name'] ?? '-';
                                    $isDisabled = ($sec['disabled'] ?? 'no') === 'yes';
                                    $isIsolir = stripos($sec['profile'] ?? '', 'isolir') !== false;
                                    $isActiveOnline = in_array($username, array_column($activeSessions, 'name'));
                                    $custData = $customerMap[$username] ?? null;
                                @endphp
                                <tr style="background: var(--surface); border-bottom: 1px solid var(--border);">
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle;">
                                        <div style="font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; color: var(--text); font-size: 0.875rem;">
                                            {{ $username }}
                                        </div>
                                        @if ($isActiveOnline)
                                            <span style="font-size: 0.6875rem; color: #00e07e; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 3px;">
                                                <span class="noc-dot noc-dot-success" style="width: 6px; height: 6px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                                                Online Sekarang
                                            </span>
                                        @else
                                            <span style="font-size: 0.6875rem; color: #71717a; display: inline-block; margin-top: 3px;">
                                                Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, monospace; color: var(--muted); font-size: 0.8125rem;">
                                        {{ $sec['password'] ?? '••••••' }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle;">
                                        @if ($isIsolir)
                                            <span style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3);">
                                                {{ $sec['profile'] ?? '-' }}
                                            </span>
                                        @else
                                            <span style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3);">
                                                {{ $sec['profile'] ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, monospace; font-size: 0.8125rem; color: var(--muted);">
                                        {{ $sec['remote-address'] ?? '-' }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle;">
                                        @if ($custData)
                                            @if (($custData['fup_limit_gb'] ?? 0) > 0)
                                                <div style="font-size: 0.75rem;">
                                                    <span style="font-weight: 700; color: {{ $custData['fup_status'] === 'fup_applied' ? '#f27b7b' : 'var(--text)' }};">
                                                        {{ $custData['usage_gb'] }} GB
                                                    </span>
                                                    <span style="color: #71717a;">/ {{ $custData['fup_limit_gb'] }} GB</span>
                                                    @if ($custData['fup_status'] === 'fup_applied')
                                                        <div style="margin-top: 3px;">
                                                            <span style="font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: rgba(242, 123, 123, 0.15); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3);">
                                                                Limit FUP
                                                            </span>
                                                        </div>
                                                    @else
                                                        <div style="margin-top: 3px;">
                                                            <span style="font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: rgba(0, 224, 126, 0.1); color: #00e07e;">
                                                                Normal
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span style="font-size: 0.75rem; color: var(--muted); font-weight: 600;">Unlimited</span>
                                            @endif
                                        @else
                                            <span style="font-size: 0.75rem; color: #71717a;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle;">
                                        @if ($isDisabled)
                                            <span style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: var(--surface2); color: var(--muted); border: 1px solid var(--border);">
                                                Nonaktif
                                            </span>
                                        @elseif ($isIsolir)
                                            <span style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: rgba(242, 123, 123, 0.15); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3);">
                                                Terisolir
                                            </span>
                                        @else
                                            <span style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="noc-dot noc-dot-success" style="width: 5px; height: 5px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                                                Aktif Normal
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-size: 0.775rem; color: var(--muted); max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $sec['comment'] ?? ($custData['name'] ?? '-') }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                            {{-- Tombol Pulihkan FUP --}}
                                            @if ($custData && $custData['fup_status'] === 'fup_applied')
                                                <button type="button"
                                                        wire:click="restoreCustomerFup('{{ $username }}')"
                                                        wire:confirm="Reset kuota FUP dan kembalikan kecepatan normal untuk user {{ $username }}?"
                                                        title="Pulihkan Kecepatan Normal"
                                                        style="display: inline-flex; align-items: center; gap: 4px; background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); cursor: pointer; padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.375rem; transition: all 0.15s ease;">
                                                    <span class="material-symbols-rounded" style="font-size: 14px;">restart_alt</span>
                                                    Reset FUP
                                                </button>
                                            @endif

                                            {{-- Tombol Toggle Isolir --}}
                                            <button type="button"
                                                    wire:click="toggleIsolir('{{ $username }}', '{{ $sec['profile'] ?? '' }}')"
                                                    style="display: inline-flex; align-items: center; gap: 4px; @if ($isIsolir) background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); @else background: rgba(242, 197, 107, 0.12); color: #f2c56b; border: 1px solid rgba(242, 197, 107, 0.3); @endif cursor: pointer; padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.375rem; transition: all 0.15s ease;">
                                                <span class="material-symbols-rounded" style="font-size: 14px;">{{ $isIsolir ? 'lock_open' : 'lock' }}</span>
                                                {{ $isIsolir ? 'Buka Isolir' : 'Isolir' }}
                                            </button>

                                            {{-- Tombol Kick jika Online --}}
                                            @if ($isActiveOnline)
                                                <button type="button"
                                                        wire:click="kickSession('{{ $username }}')"
                                                        wire:confirm="Putuskan sesi koneksi user {{ $username }}?"
                                                        title="Putuskan sesi koneksi"
                                                        style="display: inline-flex; align-items: center; gap: 4px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3); cursor: pointer; padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.375rem; transition: all 0.15s ease;">
                                                    <span class="material-symbols-rounded" style="font-size: 14px;">power_settings_new</span>
                                                    Kick
                                                </button>
                                            @endif

                                            {{-- Tombol Hapus --}}
                                            <button type="button"
                                                    wire:click="deleteSecret('{{ $username }}')"
                                                    wire:confirm="Hapus akun PPPoE {{ $username }} dari MikroTik?"
                                                    title="Hapus Secret PPPoE"
                                                    style="display: inline-flex; align-items: center; justify-content: center; background: var(--surface2); color: var(--muted); border: 1px solid var(--border); cursor: pointer; width: 28px; height: 28px; border-radius: 0.375rem; transition: all 0.15s ease;"
                                                    onmouseover="this.style.color='#f27b7b'; this.style.borderColor='rgba(242, 123, 123, 0.4)';"
                                                    onmouseout="this.style.color='var(--muted)'; this.style.borderColor='var(--border)';">
                                                <span class="material-symbols-rounded" style="font-size: 15px;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="padding: 2.5rem; text-align: center; color: var(--muted); background: var(--surface);">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                            <span class="material-symbols-rounded" style="font-size: 32px; color: var(--muted);">vpn_key_off</span>
                                            <span>Belum ada PPPoE Secret di router ini.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PPPoE Active Sessions Table (/ppp/active) -->
            <div class="fi-section" style="overflow: hidden; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
                <div class="noc-header-bar" style="padding: 1.15rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border);">
                    <div class="noc-title-group" style="display: flex; align-items: center; gap: 0.875rem;">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #00e07e;">
                            <span class="material-symbols-rounded" style="font-size: 20px;">online_prediction</span>
                        </div>
                        <div>
                            <h3 class="noc-title" style="font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0;">
                                Sesi PPPoE Aktif Saat Ini (Online Real-time)
                            </h3>
                            <p class="noc-subtitle" style="font-size: 0.775rem; color: var(--muted); margin: 3px 0 0 0;">
                                Daftar pelanggan PPPoE yang sedang terhubung dan aktif mentransmisikan data di router.
                            </p>
                        </div>
                    </div>
                    <span class="noc-pill noc-pill-success" style="font-size: 0.7rem; font-weight: 700; padding: 0.4rem 0.75rem; border-radius: 0.5rem; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3);">
                        Total Online: <strong>{{ count($activeSessions) }}</strong> Pelanggan
                    </span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="isp-table-clean" style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; text-align: left;">
                        <thead>
                            <tr style="background: var(--surface2);">
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Username PPPoE</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">IP Dial-in Pelanggan</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">MAC / Caller-ID</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Uptime Koneksi</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap;">Service Protocol</th>
                                <th style="padding: 0.85rem 1rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); text-align: right; white-space: nowrap;">Aksi Putuskan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeSessions as $sess)
                                <tr style="background: var(--surface); border-bottom: 1px solid var(--border);">
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 800; color: #00e07e; font-size: 0.875rem;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="noc-dot noc-dot-success" style="width: 6px; height: 6px; border-radius: 9999px; background: #00e07e; display: inline-block;"></span>
                                            {{ $sess['name'] ?? '-' }}
                                        </div>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, monospace; color: var(--text);">
                                        {{ $sess['address'] ?? '-' }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, monospace; color: var(--muted);">
                                        {{ $sess['caller-id'] ?? '-' }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; font-family: ui-monospace, monospace; color: var(--text);">
                                        {{ $sess['uptime'] ?? '-' }}
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle;">
                                        <span class="noc-badge-sub" style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background: var(--surface2); border: 1px solid var(--border); color: var(--muted); text-transform: uppercase;">
                                            {{ $sess['service'] ?? 'pppoe' }}
                                        </span>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; vertical-align: middle; text-align: right;">
                                        <button type="button"
                                                wire:click="kickSession('{{ $sess['name'] }}')"
                                                wire:confirm="Putuskan sesi koneksi user {{ $sess['name'] }}?"
                                                style="display: inline-flex; align-items: center; gap: 4px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3); cursor: pointer; padding: 0.3rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.375rem; transition: all 0.15s ease;">
                                            <span class="material-symbols-rounded" style="font-size: 14px;">power_settings_new</span>
                                            Putuskan (Kick)
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 2.5rem; text-align: center; color: var(--muted); background: var(--surface);">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                            <span class="material-symbols-rounded" style="font-size: 32px; color: var(--muted);">signal_wifi_off</span>
                                            <span>Tidak ada sesi PPPoE yang sedang aktif (Semua pelanggan sedang offline).</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
