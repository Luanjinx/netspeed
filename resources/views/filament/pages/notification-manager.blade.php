<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- 1. Live OneSignal Metric & Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Status Koneksi OneSignal -->
            <div class="isp-metric-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                            Status Koneksi OneSignal
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($isConnected)
                                <span class="noc-dot noc-dot-success"></span>
                                <span style="color: #10b981;">Terkoneksi</span>
                            @else
                                <span class="noc-dot noc-dot-danger"></span>
                                <span style="color: #f43f5e;">Belum Terhubung</span>
                            @endif
                        </div>
                    </div>
                    <div class="noc-icon-box" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">notifications</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--border); font-size: 0.75rem; color: var(--muted);">
                    @if ($isConnected)
                        App: <strong style="color: var(--text);">{{ $connectionInfo['app_name'] ?? 'OneSignal App' }}</strong>
                    @else
                        <span style="color: #f87171;">{{ $connectionInfo['error'] ?? 'Kredensial belum lengkap' }}</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Perangkat Terdaftar (Subscribers) -->
            <div class="isp-metric-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                            Perangkat Terdaftar (Subscribers)
                        </div>
                        <div class="isp-stat-number" style="color: #6366f1; margin-top: 0.35rem;">
                            {{ $connectionInfo['players_count'] ?? 0 }} <span style="font-size: 0.8125rem; font-weight: normal; color: #71717a;">Device</span>
                        </div>
                    </div>
                    <div class="noc-icon-box" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">smartphone</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255, 255, 255, 0.08); font-size: 0.75rem; color: #a1a1aa;">
                    Aktif / Messageable: <strong style="color: #10b981;">{{ $connectionInfo['messageable_players'] ?? 0 }}</strong> device
                </div>
            </div>

            <!-- Card 3: Total Notifikasi Terkirim -->
            <div class="isp-metric-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a1a1aa;">
                            Total Riwayat Notifikasi
                        </div>
                        <div class="isp-stat-number" style="color: #f59e0b; margin-top: 0.35rem;">
                            {{ \App\Models\PushNotification::count() }} <span style="font-size: 0.8125rem; font-weight: normal; color: #71717a;">Broadcast</span>
                        </div>
                    </div>
                    <div class="noc-icon-box" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">mark_email_read</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255, 255, 255, 0.08); font-size: 0.75rem; color: #a1a1aa;">
                    Berhasil: <strong style="color: #10b981;">{{ \App\Models\PushNotification::where('status', 'sent')->count() }}</strong> | Gagal: <strong style="color: #f43f5e;">{{ \App\Models\PushNotification::where('status', 'failed')->count() }}</strong>
                </div>
            </div>

        </div>

        <!-- 2. Form Panel (Tabs: Kirim Notifikasi & Pengaturan Kredensial) -->
        <div class="fi-section" style="padding: 1.5rem;">
            {{ $this->form }}

            <div style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 1rem;">
                <button type="button"
                        wire:click="saveSettings"
                        wire:loading.attr="disabled"
                        class="noc-btn noc-btn-secondary"
                        style="cursor: pointer; padding: 0.5rem 1.15rem; font-size: 0.8125rem; display: inline-flex; align-items: center; gap: 6px;">
                    <span wire:loading.remove wire:target="saveSettings" class="material-symbols-rounded" style="font-size: 16px;">save</span>
                    <span wire:loading wire:target="saveSettings" class="material-symbols-rounded" style="font-size: 16px;">hourglass_empty</span>
                    Simpan Pengaturan
                </button>

                <button type="button"
                        wire:click="sendNotification"
                        wire:loading.attr="disabled"
                        class="noc-btn noc-btn-primary"
                        style="cursor: pointer; padding: 0.65rem 1.5rem; font-size: 0.875rem; font-weight: 700; background: #16a34a; border: 1px solid #22c55e; color: #ffffff !important; border-radius: 0.5rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);">
                    <span wire:loading.remove wire:target="sendNotification" class="material-symbols-rounded" style="font-size: 18px; color: #ffffff !important;">send</span>
                    <span wire:loading wire:target="sendNotification" class="material-symbols-rounded" style="font-size: 18px; color: #ffffff !important;">hourglass_empty</span>
                    <span style="color: #ffffff !important;">Kirim Notifikasi Sekarang</span>
                </button>
            </div>
        </div>

        <!-- 3. Riwayat Pengiriman Notifikasi (Push & WhatsApp) -->
        <div class="fi-section" style="overflow: hidden;">
            <div class="noc-header-bar" style="padding: 1rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                <div class="noc-title-group">
                    <span class="material-symbols-rounded" style="font-size: 22px; color: #818cf8;">history</span>
                    <div>
                        <h3 class="noc-title">
                            Riwayat Pengiriman Notifikasi
                        </h3>
                        <p class="noc-subtitle">
                            Daftar notifikasi yang dikirimkan ke aplikasi mobile (OneSignal) dan WhatsApp pelanggan.
                        </p>
                    </div>
                </div>

                <span class="noc-pill noc-pill-neutral" style="font-size: 0.6875rem;">
                    Total: <strong>{{ \App\Models\PushNotification::count() }}</strong> Pesan
                </span>
            </div>

            <div style="overflow-x: auto;">
                <table class="isp-table-clean">
                    <thead>
                        <tr>
                            <th>Judul &amp; Pesan</th>
                            <th>Target Penerima</th>
                            <th>Penerima</th>
                            <th>Status</th>
                            <th>Ringkasan / Info</th>
                            <th>Waktu Kirim</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->notifications as $notif)
                            <tr>
                                <td style="max-width: 320px;">
                                    <div style="font-weight: 700; color: currentColor; font-size: 0.875rem;">
                                        {{ $notif->title }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: #a1a1aa; margin-top: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $notif->message }}
                                    </div>
                                    @if ($notif->action_url)
                                        <div style="font-size: 0.6875rem; color: #818cf8; margin-top: 0.15rem; display: flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-rounded" style="font-size: 13px;">link</span>
                                            <span>{{ $notif->action_url }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">
                                        {{ $notif->target_label ?: ucfirst($notif->target_type) }}
                                    </span>
                                </td>
                                <td style="font-family: ui-monospace, monospace; font-weight: 700;">
                                    {{ $notif->recipients_count }} target
                                </td>
                                <td>
                                    @if ($notif->status === 'sent')
                                        <span class="noc-pill noc-pill-success" style="font-size: 0.6875rem;">
                                            <span class="noc-dot noc-dot-success"></span> Terkirim
                                        </span>
                                    @else
                                        <span class="noc-pill noc-pill-danger" style="font-size: 0.6875rem;">
                                            <span class="noc-dot noc-dot-danger"></span> Gagal
                                        </span>
                                    @endif
                                </td>
                                <td style="font-size: 0.75rem; color: var(--muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $notif->response_summary ?: ($notif->onesignal_id ?: '-') }}
                                </td>
                                <td style="font-size: 0.75rem; color: #71717a; white-space: nowrap;">
                                    {{ $notif->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <button type="button"
                                                wire:click="resendNotification({{ $notif->id }})"
                                                wire:confirm="Kirim ulang notifikasi ini?"
                                                title="Kirim Ulang Notifikasi"
                                                class="noc-btn"
                                                style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.3); cursor: pointer; padding: 0.25rem 0.55rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 3px;">
                                            <span class="material-symbols-rounded" style="font-size: 13px;">replay</span>
                                            <span>Ulang</span>
                                        </button>

                                        <button type="button"
                                                wire:click="deleteLog({{ $notif->id }})"
                                                wire:confirm="Hapus log notifikasi ini?"
                                                title="Hapus Log"
                                                class="noc-btn"
                                                style="background: rgba(255, 255, 255, 0.05); color: #a1a1aa; border-color: rgba(255, 255, 255, 0.1); cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.75rem; display: inline-flex; align-items: center;">
                                            <span class="material-symbols-rounded" style="font-size: 13px; color: #f43f5e;">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 2.5rem; text-align: center; color: #71717a;">
                                    Belum ada riwayat pengiriman notifikasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->notifications->hasPages())
                <div style="padding: 0.875rem 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 0, 0, 0.05);">
                    {{ $this->notifications->links() }}
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>

