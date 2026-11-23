<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Live Status Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            
            <!-- Card 1: Status Koneksi Bot -->
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">
                            Status Bot Telegram
                        </div>
                        <div style="font-size: 1.25rem; font-weight: 800; margin-top: 0.35rem; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
                            @if ($isBotConnected)
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981;"></span>
                                <span>{{ $botInfo['first_name'] ?? 'Bot Online' }}</span>
                            @else
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 10px #ef4444;"></span>
                                <span style="color: #ef4444;">Belum Terhubung</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: rgba(59, 130, 246, 0.15); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: #38bdf8;">smart_toy</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255, 255, 255, 0.08); font-size: 0.8125rem; color: #94a3b8;">
                    @if ($isBotConnected && !empty($botInfo['username']))
                        Username: <a href="https://t.me/{{ $botInfo['username'] }}" target="_blank" style="color: #38bdf8; font-weight: bold; text-decoration: underline;">@ {{ $botInfo['username'] }}</a>
                        <span style="display: block; font-size: 0.75rem; color: #64748b; margin-top: 2px;">ID Bot: {{ $botInfo['id'] ?? '-' }}</span>
                    @else
                        <span style="color: #f87171;">{{ $botInfo['error'] ?? 'Token belum diisi atau salah' }}</span>
                    @endif
                </div>
            </div>

            <!-- Card 2: Webhook Endpoint -->
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">
                            Mode Penerimaan Pesan
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #f8fafc;">
                            @if (!empty($webhookInfo['url']))
                                <span style="color: #38bdf8;">Webhook Aktif</span>
                            @else
                                <span style="color: #f59e0b;">Mode Polling / Standby</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.15); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: #10b981;">link</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255, 255, 255, 0.08); font-size: 0.75rem; color: #94a3b8;">
                    @if (!empty($webhookInfo['url']))
                        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $webhookInfo['url'] }}">
                            URL: <code style="color: #a7f3d0;">{{ $webhookInfo['url'] }}</code>
                        </div>
                        <div style="color: #64748b; margin-top: 2px;">Pending Updates: <strong>{{ $webhookInfo['pending_update_count'] ?? 0 }}</strong></div>
                    @else
                        <span>Webhook belum diset. Anda dapat menjalankan konsol: <code style="color: #fde047;">php artisan telegram:bot --poll</code></span>
                    @endif
                </div>
            </div>

            <!-- Card 3: Target Notifikasi NOC -->
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">
                            Target Notifikasi NOC
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #f8fafc;">
                            @if (!empty($data['telegram_admin_chat_id']))
                                <code style="color: #cbd5e1; font-size: 0.95rem;">{{ $data['telegram_admin_chat_id'] }}</code>
                            @else
                                <span style="color: #94a3b8; font-size: 0.875rem;">Belum Dikonfigurasi</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.15); border-radius: 0.5rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: #f59e0b;">campaign</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed rgba(255, 255, 255, 0.08); font-size: 0.75rem; color: #94a3b8;">
                    Notifikasi Otomatis: 
                    @if (!empty($data['telegram_bot_enabled']))
                        <span style="color: #10b981; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span> AKTIF</span>
                    @else
                        <span style="color: #f87171; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;"><span class="material-symbols-rounded" style="font-size: 14px;">cancel</span> NONAKTIF</span>
                    @endif
                </div>
            </div>

        </div>

        <!-- Form Pengaturan -->
        <form wire:submit="saveSettings" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-end pt-4">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-check" color="primary">
                    Simpan
                </x-filament::button>
            </div>
        </form>

        <!-- Command Reference & Setup Tutorial -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
            
            <!-- Panduan 3 Langkah -->
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.75rem; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 20px; color: #38bdf8;">menu_book</span> Panduan Singkat Setup Bot Telegram
                </h3>
                <ol style="font-size: 0.8125rem; color: #cbd5e1; line-height: 1.6; padding-left: 1.25rem; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li>
                        <strong>Buat Akun Bot di @BotFather:</strong><br>
                        Buka Telegram, cari akun resmi <a href="https://t.me/BotFather" target="_blank" style="color: #38bdf8; text-decoration: underline;">@BotFather</a>, ketik <code>/newbot</code>, lalu ikuti instruksi hingga mendapatkan <strong>Bot API Token</strong>.
                    </li>
                    <li>
                        <strong>Dapatkan Chat ID Anda atau Grup NOC:</strong><br>
                        • Untuk akun pribadi: Kirim pesan apapun ke bot buatan Anda, lalu ketik perintah <code>/id</code>.<br>
                        • Untuk Grup/Channel: Masukkan bot ke grup Anda, angkat sebagai Admin, lalu ketik <code>/id</code> di grup untuk mendapatkan Chat ID (biasanya berawalan minus seperti <code>-100...</code>).
                    </li>
                    <li>
                        <strong>Aktifkan Webhook atau Jalankan Polling:</strong><br>
                        • Jika aplikasi memiliki IP/Domain publik (HTTPS), klik tombol <strong>"Set Webhook Otomatis"</strong> di pojok kanan atas.<br>
                        • Jika berjalan di localhost (Laragon/offline), gunakan perintah konsol: <code>php artisan telegram:bot --poll</code>
                    </li>
                </ol>
            </div>

            <!-- Daftar Perintah Interaktif Bot -->
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 0.75rem; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 20px; color: #00e07e;">terminal</span> Perintah Interaktif yang Didukung Bot
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/start</code>
                        <span style="color: #94a3b8;">Menampilkan menu tombol cepat & ID chat</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/status</code>
                        <span style="color: #94a3b8;">Ringkasan total pelanggan, router, dan tagihan</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/cek [nama/username]</code>
                        <span style="color: #94a3b8;">Cek info pelanggan, paket, status PPPoE & tagihan</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/tagihan</code>
                        <span style="color: #94a3b8;">Daftar tagihan tertunggak (unpaid invoices)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/router</code>
                        <span style="color: #94a3b8;">Cek status online/offline seluruh router MikroTik</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/fup</code>
                        <span style="color: #94a3b8;">Monitoring pemakaian kuota FUP pelanggan bulan ini</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/id</code>
                        <span style="color: #94a3b8;">Melihat Telegram Chat ID pengguna / grup</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0.5rem; background: rgba(255, 255, 255, 0.03); border-radius: 0.35rem;">
                        <code style="color: #38bdf8; font-weight: bold;">/help</code>
                        <span style="color: #94a3b8;">Daftar panduan lengkap semua perintah</span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>

