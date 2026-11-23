<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Router Selection & Status Bar -->
        <div class="fi-section noc-router-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.875rem; flex-wrap: wrap; flex: 1; min-width: 250px;">
                <div class="noc-icon-box" style="width: 36px; height: 36px; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: #f2c56b;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">wifi</span>
                </div>
                <div style="flex: 1; min-width: 180px;">
                    <label style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: block;">
                        Pilih Router Hotspot MikroTik
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
                        Terhubung ke Hotspot RouterOS
                    </span>
                @else
                    <span class="noc-pill noc-pill-danger" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; background: rgba(242, 123, 123, 0.12); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.3); border-radius: 9999px; font-weight: 700;">
                        <span class="noc-dot noc-dot-danger" style="width: 7px; height: 7px; border-radius: 9999px; background: #f27b7b; display: inline-block;"></span>
                        Terputus
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
                        <strong style="font-size: 0.9375rem; color: #f27b7b;">Gagal Menghubungkan ke MikroTik:</strong>
                        <div style="margin-top: 0.25rem; font-size: 0.8125rem; color: var(--muted);">
                            {{ $errorMessage ?: 'Pastikan IP, port API (8728), username, dan password router sudah benar serta firewall MikroTik mengizinkan koneksi API.' }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Hotspot Overview Stat Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; width: 100%;">
                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Total Voucher Hotspot</div>
                            <div class="isp-stat-number" style="color: #f2c56b; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ $this->voucherStats['total'] }} <span style="font-size: 0.75rem; font-weight: normal; color: var(--muted);">Tiket</span>
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(242, 197, 107, 0.15); color: #f2c56b;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">confirmation_number</span>
                        </div>
                    </div>
                </div>

                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Belum Terpakai</div>
                            <div class="isp-stat-number" style="color: #00e07e; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ $this->voucherStats['unused'] }} <span style="font-size: 0.75rem; font-weight: normal; color: var(--muted);">Siap Jual</span>
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(0, 224, 126, 0.15); color: #00e07e;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">check_circle</span>
                        </div>
                    </div>
                </div>

                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Sudah Terpakai</div>
                            <div class="isp-stat-number" style="color: var(--muted); margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ $this->voucherStats['used'] }} <span style="font-size: 0.75rem; font-weight: normal; color: #71717a;">Voucher</span>
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--surface2); color: var(--muted);">
                            <span class="material-symbols-rounded" style="font-size: 18px;">receipt_long</span>
                        </div>
                    </div>
                </div>

                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Sesi Hotspot Online</div>
                            <div class="isp-stat-number" style="color: #38bdf8; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ count($activeSessions) }} <span style="font-size: 0.75rem; font-weight: normal; color: var(--muted);">Device</span>
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">wifi_tethering</span>
                        </div>
                    </div>
                </div>

                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Profil Hotspot Router</div>
                            <div class="isp-stat-number" style="color: #4deda4; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ count($hotspotProfiles) }} <span style="font-size: 0.75rem; font-weight: normal; color: var(--muted);">Profil</span>
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(77, 237, 164, 0.15); color: #4deda4;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                        </div>
                    </div>
                </div>

                <div class="isp-metric-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.15rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted);">Beban CPU Router</div>
                            <div class="isp-stat-number" style="color: #00e07e; margin-top: 0.25rem; font-size: 1.65rem; font-weight: 800;">
                                {{ $routerInfo['cpu-load'] ?? 0 }}%
                            </div>
                            <div style="font-size: 0.6875rem; color: #71717a; margin-top: 0.2rem;">
                                {{ $routerInfo['board-name'] ?? ($routerInfo['platform'] ?? 'RouterOS') }}
                            </div>
                        </div>
                        <div class="noc-icon-box" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(0, 224, 126, 0.15); color: #00e07e;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">memory</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. Database & Riwayat Voucher Hotspot -->
            <div class="fi-section" style="overflow: hidden;">
                <div class="noc-header-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div class="noc-title-group">
                        <span class="material-symbols-rounded" style="font-size: 22px; color: #f59e0b;">confirmation_number</span>
                        <div>
                            <h3 class="noc-title">
                                Database &amp; Riwayat Voucher Hotspot
                            </h3>
                            <p class="noc-subtitle">
                                Daftar voucher cetak tersimpan di sistem yang langsung tersinkronisasi ke /ip/hotspot/user MikroTik.
                            </p>
                        </div>
                    </div>

                    <!-- Search and Filter Toolbar -->
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <input type="text"
                               wire:model.live.debounce.300ms="voucherSearch"
                               placeholder="Cari kode / batch..."
                               style="font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.4rem 0.75rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: currentColor; min-width: 170px;">

                        <!-- Profil Filter -->
                        <select wire:model.live="voucherProfileFilter" style="font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.4rem 0.75rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: currentColor;">
                            <option value="" style="background: var(--surface2); color: var(--text);">Semua Profil</option>
                            @foreach (\App\Models\HotspotVoucher::distinct()->whereNotNull('profile')->pluck('profile') as $prof)
                                <option value="{{ $prof }}" style="background: var(--surface2); color: var(--text);">{{ $prof }}</option>
                            @endforeach
                        </select>

                        <!-- Status Filter -->
                        <select wire:model.live="voucherStatusFilter" style="font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.4rem 0.75rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: currentColor;">
                            <option value="" style="background: var(--surface2); color: var(--text);">Semua Status</option>
                            <option value="unused" style="background: var(--surface2); color: var(--text);">Belum Terpakai</option>
                            <option value="used" style="background: var(--surface2); color: var(--text);">Sudah Dipakai</option>
                            <option value="expired" style="background: var(--surface2); color: var(--text);">Kadaluarsa</option>
                        </select>

                        <!-- Per Page -->
                        <select wire:model.live="voucherPerPage" style="font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.4rem 0.75rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); color: currentColor;">
                            <option value="15" style="background: var(--surface2); color: var(--text);">15 / hal</option>
                            <option value="30" style="background: var(--surface2); color: var(--text);">30 / hal</option>
                            <option value="50" style="background: var(--surface2); color: var(--text);">50 / hal</option>
                            <option value="100" style="background: var(--surface2); color: var(--text);">100 / hal</option>
                        </select>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="isp-table-clean">
                        <thead>
                            <tr>
                                <th>Kode Voucher</th>
                                <th>Password</th>
                                <th>Profil MikroTik</th>
                                <th>Harga Jual</th>
                                <th>Masa Aktif</th>
                                <th>Shared Users</th>
                                <th>Status</th>
                                <th>Batch ID</th>
                                <th>Waktu Dibuat</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->vouchers as $v)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                                            <span style="font-family: ui-monospace, monospace; font-weight: 800; font-size: 0.9375rem; color: #f59e0b;">
                                                {{ $v->code }}
                                            </span>
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $v->code }}'); this.querySelector('.material-symbols-rounded').innerText = 'check'; setTimeout(() => this.querySelector('.material-symbols-rounded').innerText = 'content_copy', 1200);"
                                                    title="Salin Kode Voucher"
                                                    style="background: transparent; border: none; cursor: pointer; color: #71717a; padding: 0 0.15rem; display: inline-flex; align-items: center;">
                                                <span class="material-symbols-rounded" style="font-size: 14px;">content_copy</span>
                                            </button>
                                        </div>
                                    </td>
                                    <td style="font-family: ui-monospace, monospace; color: #71717a; font-size: 0.75rem;">
                                        {{ $v->password ?: 'Sama dg Kode' }}
                                    </td>
                                    <td>
                                        <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">
                                            {{ $v->profile }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 700; color: currentColor;">
                                        Rp {{ number_format($v->price, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                                            {{ $v->validity }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="noc-pill {{ $v->shared_users > 1 ? 'noc-pill-warning' : 'noc-pill-neutral' }}" style="font-size: 0.6875rem;">
                                            {{ $v->shared_users }} Device{{ $v->shared_users > 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($v->status === 'unused')
                                            <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                                                <span class="noc-dot noc-dot-success"></span> Belum Terpakai
                                            </span>
                                        @elseif ($v->status === 'used')
                                            <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                                                Sudah Dipakai
                                            </span>
                                        @else
                                            <span class="noc-pill noc-pill-danger" style="font-size: 0.6875rem;">
                                                Kadaluarsa
                                            </span>
                                        @endif
                                    </td>
                                    <td style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #a1a1aa;">
                                        {{ $v->generated_batch ?: '-' }}
                                    </td>
                                    <td style="font-size: 0.75rem; color: #71717a; white-space: nowrap;">
                                        {{ $v->created_at?->format('d M Y, H:i') ?: '-' }}
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                            {{-- Tombol Cetak Satuan --}}
                                            <a href="{{ route('print.voucher.single', ['id' => $v->id]) }}"
                                               target="_blank"
                                               class="noc-btn"
                                               title="Cetak Struk Thermal Satuan"
                                               style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.25rem 0.6rem; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-rounded" style="font-size: 14px;">print</span>
                                                <span>Cetak</span>
                                            </a>

                                            {{-- Tombol Cetak Batch --}}
                                            @if (!empty($v->generated_batch))
                                                <a href="{{ route('print.vouchers', ['batch' => $v->generated_batch]) }}"
                                                   target="_blank"
                                                   class="noc-btn"
                                                   title="Cetak Lembar Batch Lengkap"
                                                   style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); padding: 0.25rem 0.6rem; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                    <span class="material-symbols-rounded" style="font-size: 14px;">description</span>
                                                    <span>Batch</span>
                                                </a>
                                            @endif

                                            {{-- Tombol Hapus --}}
                                            <button type="button"
                                                    wire:click="deleteVoucher({{ $v->id }})"
                                                    wire:confirm="Hapus voucher '{{ $v->code }}' dari database dan router MikroTik?"
                                                    class="noc-btn"
                                                    title="Hapus Voucher"
                                                    style="background: rgba(255, 255, 255, 0.05); color: #a1a1aa; border: 1px solid rgba(255, 255, 255, 0.1); cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.75rem; display: inline-flex; align-items: center;">
                                                <span class="material-symbols-rounded" style="font-size: 14px; color: #f43f5e;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                        Tidak ada data voucher hotspot yang sesuai dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($this->vouchers->hasPages())
                    <div style="padding: 0.875rem 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 0, 0, 0.05);">
                        {{ $this->vouchers->links() }}
                    </div>
                @endif
            </div>

            <!-- 2. Sesi Hotspot Aktif Saat Ini (/ip/hotspot/active) -->
            <div class="fi-section" style="overflow: hidden;">
                <div class="noc-header-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div class="noc-title-group">
                        <span class="material-symbols-rounded" style="font-size: 22px; color: #00e07e;">wifi</span>
                        <div>
                            <h3 class="noc-title">
                                Sesi Hotspot Aktif Saat Ini (/ip/hotspot/active)
                            </h3>
                            <p class="noc-subtitle">
                                Pengguna / voucher hotspot yang sedang login dan terhubung ke internet saat ini.
                            </p>
                        </div>
                    </div>

                    <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                        Total Online: <strong>{{ count($activeSessions) }}</strong> Device
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
                            @forelse ($activeSessions as $sess)
                                <tr>
                                    <td style="font-family: ui-monospace, monospace; font-weight: 800; color: #06b6d4;">
                                        {{ $sess['user'] ?? '-' }}
                                    </td>
                                    <td style="font-family: ui-monospace, monospace;">
                                        {{ $sess['address'] ?? '-' }}
                                    </td>
                                    <td style="font-family: ui-monospace, monospace; color: #a1a1aa;">
                                        {{ $sess['mac_address'] ?? '-' }}
                                    </td>
                                    <td style="font-weight: 600; color: #10b981;">
                                        {{ $sess['uptime'] ?? '-' }}
                                    </td>
                                    <td style="font-weight: 600; color: #818cf8;">
                                        {{ $sess['session_time_left'] ?? '-' }}
                                    </td>
                                    <td style="color: #a1a1aa;">
                                        ↓ {{ number_format(($sess['bytes_in'] ?? 0) / (1024 * 1024), 2) }} MB / 
                                        ↑ {{ number_format(($sess['bytes_out'] ?? 0) / (1024 * 1024), 2) }} MB
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button"
                                                wire:click="kickSession('{{ $sess['user'] ?? $sess['mac_address'] }}')"
                                                wire:confirm="Putuskan koneksi pengguna hotspot {{ $sess['user'] ?? $sess['mac_address'] }}?"
                                                class="noc-btn"
                                                style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border-color: rgba(244, 63, 94, 0.3); cursor: pointer; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                            Putuskan (Kick)
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

            <!-- 3. Profil Hotspot MikroTik (/ip/hotspot/user/profile) -->
            <div class="fi-section" style="overflow: hidden;">
                <div class="noc-header-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div class="noc-title-group">
                        <span class="material-symbols-rounded" style="font-size: 22px; color: #38bdf8;">tune</span>
                        <div>
                            <h3 class="noc-title">
                                Daftar Profil Hotspot MikroTik (/ip/hotspot/user/profile)
                            </h3>
                            <p class="noc-subtitle">
                                Konfigurasi paket kecepatan, shared users, dan timeout yang terpasang di router MikroTik.
                            </p>
                        </div>
                    </div>

                    <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                        Total: <strong>{{ count($hotspotProfiles) }}</strong> Profil
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
                                    <td>
                                        <span class="noc-pill noc-pill-primary" style="font-weight: 700;">
                                            {{ $prof['name'] }}
                                        </span>
                                    </td>
                                    <td style="font-family: ui-monospace, monospace; font-weight: 600;">
                                        {{ $prof['rate_limit'] ?: 'Unlimited / Default' }}
                                    </td>
                                    <td>
                                        <span class="noc-pill {{ ($prof['shared_users'] ?? 1) > 1 ? 'noc-pill-warning' : 'noc-pill-neutral' }}" style="font-size: 0.6875rem;">
                                            {{ $prof['shared_users'] }} User
                                        </span>
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
                                        Tidak ada profil hotspot yang ditemukan di router ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. User Hotspot Terdaftar di MikroTik (/ip/hotspot/user) -->
            <div class="fi-section" style="overflow: hidden;">
                <div class="noc-header-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div class="noc-title-group">
                        <span class="material-symbols-rounded" style="font-size: 22px; color: #4deda4;">group</span>
                        <div>
                            <h3 class="noc-title">
                                Raw User Hotspot di MikroTik (/ip/hotspot/user)
                            </h3>
                            <p class="noc-subtitle">
                                Daftar akun voucher hotspot yang saat ini terpasang langsung di memori router MikroTik.
                            </p>
                        </div>
                    </div>

                    <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                        Total di Router: <strong>{{ count($hotspotUsers) }}</strong> User
                    </span>
                </div>

                <div style="overflow-x: auto; max-height: 380px;">
                    <table class="isp-table-clean">
                        <thead>
                            <tr>
                                <th>Username / Kode</th>
                                <th>Profil</th>
                                <th>Limit Uptime</th>
                                <th>Uptime Terpakai</th>
                                <th>Trafik (In / Out)</th>
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
                                        <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">
                                            {{ $u['profile'] }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; color: #818cf8;">
                                        {{ $u['limit_uptime'] ?: 'Unlimited' }}
                                    </td>
                                    <td style="color: #a1a1aa;">
                                        {{ $u['uptime'] ?: '0s' }}
                                    </td>
                                    <td style="color: #a1a1aa;">
                                        ↓ {{ number_format(($u['bytes_in'] ?? 0) / 1024, 1) }} KB / 
                                        ↑ {{ number_format(($u['bytes_out'] ?? 0) / 1024, 1) }} KB
                                    </td>
                                    <td style="color: #71717a; font-size: 0.75rem;">
                                        {{ $u['comment'] ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                        Belum ada user hotspot di router MikroTik.
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
