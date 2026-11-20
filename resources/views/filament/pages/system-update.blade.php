<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- ROW 1: 3 METRIC STATUS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            
            <!-- CARD 1: VERSI SISTEM SAAT INI -->
            <div style="background: #131615; border: 1px solid #232826; border-radius: 0.875rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Versi Sistem Lokal
                        </div>
                        <div style="font-size: 1.25rem; font-weight: 800; margin-top: 0.35rem; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-family: monospace; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); padding: 2px 8px; border-radius: 6px; font-size: 1.05rem;">
                                {{ $versionInfo['commit_hash'] ?? '351b5d1' }}
                            </span>
                            <span style="font-size: 0.75rem; color: #9aa49f; font-weight: 600; padding: 2px 6px; background: #1a1e1c; border-radius: 4px; border: 1px solid #232826;">
                                Cabang: {{ $versionInfo['branch'] ?? 'main' }}
                            </span>
                        </div>
                    </div>
                    <div style="background: rgba(0, 224, 126, 0.12); border-radius: 0.6rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: #00e07e;">memory</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.775rem; color: #9aa49f;">
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #eef1f0; font-weight: 600;" title="{{ $versionInfo['commit_message'] ?? '' }}">
                        {{ $versionInfo['commit_message'] ?? '-' }}
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.725rem;">
                        <span>Oleh: <strong style="color: #cbd5e1;">{{ $versionInfo['commit_author'] ?? '-' }}</strong></span>
                        @if ($versionInfo['has_uncommitted_changes'] ?? false)
                            <span style="color: #f2c56b; font-weight: 700;">⚠ Ada file lokal termodifikasi</span>
                        @else
                            <span style="color: #00e07e; font-weight: 600;">✓ Direktori kerja bersih</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- CARD 2: REPOSITORY GITHUB -->
            <div style="background: #131615; border: 1px solid #232826; border-radius: 0.875rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Repository GitHub
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
                            <a href="https://github.com/{{ $versionInfo['repo_owner'] ?? 'Luanjinx' }}/{{ $versionInfo['repo_name'] ?? 'netspeed' }}" target="_blank" style="color: #eef1f0; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <span>{{ $versionInfo['repo_owner'] ?? 'Luanjinx' }}/{{ $versionInfo['repo_name'] ?? 'netspeed' }}</span>
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #9aa49f;">open_in_new</span>
                            </a>
                        </div>
                    </div>
                    <div style="background: rgba(56, 189, 248, 0.12); border-radius: 0.6rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(56, 189, 248, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: #38bdf8;">public</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Remote: <code style="color: #38bdf8;">origin/{{ $versionInfo['branch'] ?? 'main' }}</code></span>
                        <span>Cek: <strong style="color: #cbd5e1;">{{ $updateStatus['checked_at'] ?? 'Baru saja' }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- CARD 3: STATUS PEMBARUAN -->
            <div style="background: #131615; border: 1px solid #232826; border-radius: 0.875rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9aa49f;">
                            Status Pembaruan
                        </div>
                        <div style="font-size: 1.15rem; font-weight: 800; margin-top: 0.35rem;">
                            @if (!empty($updateStatus['is_up_to_date']))
                                <span style="color: #00e07e; display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #00e07e; box-shadow: 0 0 10px #00e07e;"></span>
                                    Sistem Terkini (Up to Date)
                                </span>
                            @elseif (isset($updateStatus['commits_behind']) && $updateStatus['commits_behind'] > 0)
                                <span style="color: #f2c56b; display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #f2c56b; box-shadow: 0 0 10px #f2c56b;"></span>
                                    Ada {{ $updateStatus['commits_behind'] }} Pembaruan Tersedia!
                                </span>
                            @else
                                <span style="color: #9aa49f;">Belum Dicek</span>
                            @endif
                        </div>
                    </div>
                    <div style="background: {{ !empty($updateStatus['is_up_to_date']) ? 'rgba(0, 224, 126, 0.12)' : 'rgba(242, 197, 107, 0.15)' }}; border-radius: 0.6rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: 1px solid {{ !empty($updateStatus['is_up_to_date']) ? 'rgba(0, 224, 126, 0.25)' : 'rgba(242, 197, 107, 0.3)' }};">
                        <span class="material-symbols-rounded" style="font-size: 24px; color: {{ !empty($updateStatus['is_up_to_date']) ? '#00e07e' : '#f2c56b' }};">
                            {{ !empty($updateStatus['is_up_to_date']) ? 'verified' : 'system_update' }}
                        </span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #232826; font-size: 0.75rem; color: #9aa49f; display: flex; justify-content: space-between; align-items: center;">
                    @if (!empty($updateStatus['is_up_to_date']))
                        <span>Tidak ada antrean commit baru</span>
                        <span style="color: #00e07e; font-weight: 700;">v.{{ $versionInfo['commit_hash'] ?? 'latest' }}</span>
                    @else
                        <span>Target: <code style="color: #f2c56b;">{{ $updateStatus['latest_remote_hash'] ?? '-' }}</code></span>
                        <span style="color: #f2c56b; font-weight: 700;">{{ $updateStatus['commits_behind'] ?? 0 }} commit baru</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- NOTIFICATION ALERTS IF ANY -->
        @if ($lastSuccessMessage)
            <div style="background: rgba(0, 224, 126, 0.1); border: 1px solid rgba(0, 224, 126, 0.3); border-radius: 0.75rem; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; color: #00e07e;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">check_circle</span>
                    <div>
                        <div style="font-weight: 700; font-size: 0.875rem;">Pembaruan Berhasil Dijalankan!</div>
                        <div style="font-size: 0.8rem; color: #a7f3d0; margin-top: 2px;">{{ $lastSuccessMessage }}</div>
                    </div>
                </div>
                <button type="button" wire:click="$set('lastSuccessMessage', null)" style="background: none; border: none; color: #a7f3d0; cursor: pointer;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
            </div>
        @endif

        @if ($lastErrorMessage)
            <div style="background: rgba(242, 123, 123, 0.1); border: 1px solid rgba(242, 123, 123, 0.3); border-radius: 0.75rem; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; color: #f27b7b;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">error</span>
                    <div>
                        <div style="font-weight: 700; font-size: 0.875rem;">Pemberitahuan Sistem</div>
                        <div style="font-size: 0.8rem; color: #fca5a5; margin-top: 2px;">{{ $lastErrorMessage }}</div>
                    </div>
                </div>
                <button type="button" wire:click="$set('lastErrorMessage', null)" style="background: none; border: none; color: #fca5a5; cursor: pointer;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
            </div>
        @endif

        <!-- CHANGELOG LIST IF COMMITS ARE AVAILABLE -->
        @if (!empty($updateStatus['incoming_commits']))
            <div style="background: #131615; border: 1px solid #232826; border-radius: 0.875rem; padding: 1.35rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: #f8fafc; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="color: #f2c56b; font-size: 20px;">list_alt</span>
                            Daftar Pembaruan yang Akan Diunduh (Changelog)
                        </h3>
                        <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">
                            Commit berikut akan otomatis ditarik dari GitHub saat Anda menekan tombol "Perbarui Sekarang".
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: rgba(242, 197, 107, 0.15); color: #f2c56b; border: 1px solid rgba(242, 197, 107, 0.3);">
                        {{ count($updateStatus['incoming_commits']) }} Commit Baru
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                    @foreach ($updateStatus['incoming_commits'] as $commit)
                        <div style="background: #1a1e1c; border: 1px solid #232826; border-radius: 0.6rem; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <span style="font-family: monospace; font-size: 0.75rem; font-weight: 700; background: #131615; border: 1px solid #232826; padding: 3px 8px; border-radius: 5px; color: #00e07e; align-self: flex-start;">
                                    <a href="{{ $commit['url'] }}" target="_blank" style="color: #00e07e; text-decoration: none;">
                                        {{ $commit['hash'] }}
                                    </a>
                                </span>
                                <div>
                                    <div style="font-size: 0.85rem; font-weight: 700; color: #eef1f0;">
                                        {{ $commit['message'] }}
                                    </div>
                                    <div style="font-size: 0.725rem; color: #9aa49f; margin-top: 3px; display: flex; gap: 10px;">
                                        <span>Penulis: <strong style="color: #cbd5e1;">{{ $commit['author'] }}</strong></span>
                                        <span>•</span>
                                        <span>Waktu: {{ $commit['date'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ $commit['url'] }}" target="_blank" style="font-size: 0.75rem; color: #38bdf8; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #131615; border: 1px solid #232826; border-radius: 6px;">
                                <span>Lihat di GitHub</span>
                                <span class="material-symbols-rounded" style="font-size: 14px;">launch</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- TERMINAL CONSOLE / LIVE LOG VIEWER -->
        <div style="background: #0d0f0e; border: 1px solid #232826; border-radius: 0.875rem; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);">
            <!-- Terminal Title Bar -->
            <div style="background: #171b19; padding: 0.75rem 1rem; border-bottom: 1px solid #232826; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="display: flex; gap: 6px;">
                        <span style="width: 11px; height: 11px; border-radius: 50%; background: #ff5f56; display: inline-block;"></span>
                        <span style="width: 11px; height: 11px; border-radius: 50%; background: #ffbd2e; display: inline-block;"></span>
                        <span style="width: 11px; height: 11px; border-radius: 50%; background: #27c93f; display: inline-block;"></span>
                    </div>
                    <span style="font-family: monospace; font-size: 0.75rem; color: #9aa49f; margin-left: 8px; font-weight: 600;">
                        Terminal Eksekusi &amp; Log Pembaruan (Git &amp; Artisan Engine)
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    @if ($lastHistory)
                        <span style="font-size: 0.725rem; color: #9aa49f;">
                            Update Terakhir: <strong style="color: #cbd5e1;">{{ $lastHistory['updated_at'] }}</strong> ({{ $lastHistory['from_hash'] }} &rarr; {{ $lastHistory['to_hash'] }})
                        </span>
                    @endif
                </div>
            </div>

            <!-- Terminal Screen Body -->
            <div style="padding: 1.25rem; font-family: 'JetBrains Mono', 'Fira Code', Menlo, Monaco, Consolas, monospace; font-size: 0.775rem; line-height: 1.55; color: #e2e8f0; max-height: 480px; overflow-y: auto;">
                @if (empty($executionLogs))
                    <div style="color: #64748b; padding: 1.5rem 0; text-align: center;">
                        <span class="material-symbols-rounded" style="font-size: 32px; display: block; margin: 0 auto 8px auto; opacity: 0.5;">terminal</span>
                        <div>Belum ada aktivitas pembaruan yang dijalankan pada sesi ini.</div>
                        <div style="font-size: 0.725rem; margin-top: 4px; color: #475569;">
                            Klik tombol "Periksa GitHub" untuk mengecek commit baru, atau "Perbarui Sekarang" untuk menarik update langsung dari repository.
                        </div>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        @foreach ($executionLogs as $idx => $log)
                            <div style="background: rgba(255, 255, 255, 0.02); border-left: 3px solid {{ $log['success'] ? '#00e07e' : '#f27b7b' }}; padding: 0.65rem 0.85rem; border-radius: 0 0.4rem 0.4rem 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                                    <span style="color: {{ $log['success'] ? '#00e07e' : '#f27b7b' }}; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                                        @if ($log['success'])
                                            <span>✓</span>
                                        @else
                                            <span>✗</span>
                                        @endif
                                        <span>{{ $log['step'] }}</span>
                                    </span>
                                    <code style="color: #64748b; font-size: 0.7rem;">{{ $log['command'] }}</code>
                                </div>
                                <pre style="margin: 0; padding: 0.5rem; background: #070908; border-radius: 4px; border: 1px solid #1a1e1c; color: #94a3b8; font-size: 0.725rem; white-space: pre-wrap; word-break: break-all;">{{ $log['output'] }}</pre>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- QUICK GUIDELINE / HOW IT WORKS CARD -->
        <div style="background: #131615; border: 1px solid #232826; border-radius: 0.875rem; padding: 1.35rem;">
            <h4 style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: #00e07e; font-size: 20px;">info</span>
                Bagaimana Mekanisme Pembaruan Sistem Ini Bekerja?
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; font-size: 0.775rem; color: #9aa49f; line-height: 1.45;">
                <div style="background: #1a1e1c; padding: 1rem; border-radius: 0.6rem; border: 1px solid #232826;">
                    <div style="color: #00e07e; font-weight: 700; margin-bottom: 4px;">1. Git Pull dari GitHub</div>
                    Sistem menarik kode terbaru dari cabang <code>main</code> repository <code>Luanjinx/netspeed</code> secara otomatis tanpa perlu login SSH manual.
                </div>
                <div style="background: #1a1e1c; padding: 1rem; border-radius: 0.6rem; border: 1px solid #232826;">
                    <div style="color: #00e07e; font-weight: 700; margin-bottom: 4px;">2. Auto Migrate Database</div>
                    Jika versi terbaru menyertakan tabel atau kolom baru, sistem secara aman mengeksekusi <code>migrate --force</code> agar struktur database langsung sinkron.
                </div>
                <div style="background: #1a1e1c; padding: 1rem; border-radius: 0.6rem; border: 1px solid #232826;">
                    <div style="color: #00e07e; font-weight: 700; margin-bottom: 4px;">3. Optimasi Cache &amp; Aset</div>
                    Cache konfigurasi, rute, dan Blade dibersihkan lalu dikompilasi ulang dengan <code>optimize</code> sehingga performa tetap maksimal dan bebas lag.
                </div>
                <div style="background: #1a1e1c; padding: 1rem; border-radius: 0.6rem; border: 1px solid #232826;">
                    <div style="color: #00e07e; font-weight: 700; margin-bottom: 4px;">4. Zero-Downtime Safe</div>
                    Mode pemeliharaan sementara diaktifkan selama proses penarikan file dan langsung dipulihkan kembali saat proses selesai.
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>

