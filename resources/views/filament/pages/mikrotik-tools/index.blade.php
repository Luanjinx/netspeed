<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Banner & Header --}}
        <div style="background: linear-gradient(135deg, rgba(0, 224, 126, 0.08) 0%, rgba(2, 132, 199, 0.05) 100%); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem 1.75rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.25rem;">
            <div style="max-width: 650px;">
                <div style="display: flex; align-items: center; gap: 0.625rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; background: rgba(0, 224, 126, 0.15); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3); padding: 0.2rem 0.65rem; border-radius: 9999px;">
                        MikroTik Core NOC Tools
                    </span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--muted);">RouterOS v6 & v7 Ready</span>
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text); margin: 0; letter-spacing: -0.02em;">
                    Koleksi Script Generator & Otomasi RouterOS
                </h2>
                <p style="font-size: 0.875rem; color: var(--muted); margin-top: 0.4rem; line-height: 1.5;">
                    Generate script konfigurasi siap pakai atau langsung <strong>push otomatis ke router MikroTik</strong> Anda tanpa perlu membuka terminal WinBox secara manual.
                </p>
            </div>

            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('filament.admin.resources.routers.index') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); font-size: 0.8125rem; font-weight: 700; text-decoration: none;">
                    <span class="material-symbols-rounded" style="font-size: 18px; color: #00e07e;">router</span>
                    <span>Kelola Router MikroTik</span>
                </a>
            </div>
        </div>

        {{-- Filter & Search Bar --}}
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 0.875rem 1.25rem;">
            {{-- Category Pills --}}
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @php
                    $categories = [
                        'all' => 'Semua Tools',
                        'security' => '🛡️ Keamanan & Hardening',
                        'ip' => '🌐 IP & Pool',
                        'network' => '⚡ Optimasi Jaringan',
                        'maintenance' => '🧹 Reset & Maintenance',
                    ];
                @endphp
                @foreach ($categories as $catKey => $catLabel)
                    <button type="button" wire:click="setCategory('{{ $catKey }}')"
                        style="padding: 0.35rem 0.85rem; font-size: 0.8125rem; font-weight: 700; border-radius: 0.5rem; border: 1px solid {{ $activeCategory === $catKey ? '#00e07e' : 'var(--border)' }}; background: {{ $activeCategory === $catKey ? 'rgba(0, 224, 126, 0.12)' : 'var(--surface2)' }}; color: {{ $activeCategory === $catKey ? '#00e07e' : 'var(--text)' }}; cursor: pointer; transition: all 0.2s ease;">
                        {{ $catLabel }}
                    </button>
                @endforeach
            </div>

            {{-- Search Bar --}}
            <div style="position: relative; min-width: 240px; flex: 1; max-width: 320px;">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama tool..."
                    style="width: 100%; font-size: 0.8125rem; border-radius: 0.5rem; padding: 0.45rem 0.75rem 0.45rem 2.25rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                <span class="material-symbols-rounded" style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--muted); pointer-events: none;">search</span>
            </div>
        </div>

        {{-- Tools Grid --}}
        @php
            $tools = [
                [
                    'title' => 'Add Admin User',
                    'slug' => 'add-admin-user',
                    'category' => 'security',
                    'category_label' => 'Keamanan',
                    'description' => 'Tambah user admin baru dengan hak akses spesifik, amankan dari brute-force, dan opsi otomatis matikan default user admin.',
                    'icon' => 'person_add',
                    'color' => '#00e07e',
                    'status' => 'ready',
                    'v6' => true,
                    'v7' => true,
                    'url' => route('filament.admin.pages.mikrotik-tools.add-admin-user'),
                ],
                [
                    'title' => 'Add IP Address',
                    'slug' => 'add-ip-address',
                    'category' => 'ip',
                    'category_label' => 'IP & Pool',
                    'description' => 'Tetapkan alamat IP beserta subnet CIDR ke interface router (ethernet, bridge, atau vlan) dengan opsi network custom.',
                    'icon' => 'lan',
                    'color' => '#38bdf8',
                    'status' => 'ready',
                    'v6' => true,
                    'v7' => true,
                    'url' => route('filament.admin.pages.mikrotik-tools.add-ip-address'),
                ],
                [
                    'title' => 'Add IP Pool',
                    'slug' => 'add-ip-pool',
                    'category' => 'ip',
                    'category_label' => 'IP & Pool',
                    'description' => 'Buat alokasi rentang IP dinamis (IP Pool) untuk layanan DHCP Server, PPPoE Profile, atau Hotspot Voucher.',
                    'icon' => 'view_stream',
                    'color' => '#a855f7',
                    'status' => 'ready',
                    'v6' => true,
                    'v7' => true,
                    'url' => route('filament.admin.pages.mikrotik-tools.add-ip-pool'),
                ],
                [
                    'title' => 'Anti DDoS Attacks',
                    'slug' => 'anti-ddos',
                    'category' => 'security',
                    'category_label' => 'Keamanan',
                    'description' => 'Filter proteksi dari serangan TCP SYN Flood, UDP Flood limiter, dan jump-target block dynamic DDoS list.',
                    'icon' => 'security',
                    'color' => '#f27b7b',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Anti Netcut & ARP Spoofing',
                    'slug' => 'anti-netcut',
                    'category' => 'security',
                    'category_label' => 'Keamanan',
                    'description' => 'Proteksi pemotongan bandwidth lokal antar klien dan manipulasi gateway melalui ARP reply-only & drop invalid ARP.',
                    'icon' => 'shield',
                    'color' => '#38bdf8',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Anti Tethering (Hotspot TTL Lock)',
                    'slug' => 'anti-tethering',
                    'category' => 'security',
                    'category_label' => 'Keamanan',
                    'description' => 'Kunci nilai TTL paket via Mangle agar koneksi hotspot voucher tidak dapat dibagikan kembali via WiFi tethering hp.',
                    'icon' => 'wifi_tethering_off',
                    'color' => '#f59e0b',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Enable Fasttrack Connection',
                    'slug' => 'enable-fasttrack',
                    'category' => 'network',
                    'category_label' => 'Optimasi Jaringan',
                    'description' => 'Bypass connection tracking untuk paket data yang sudah terkonfirmasi agar throughput CPU MikroTik bekerja maksimal.',
                    'icon' => 'bolt',
                    'color' => '#00e07e',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Auto Reboot Scheduler',
                    'slug' => 'auto-reboot',
                    'category' => 'network',
                    'category_label' => 'Optimasi Jaringan',
                    'description' => 'Jadwalkan restart router secara periodik otomatis (misal setiap hari Minggu jam 03:00 pagi) untuk menjaga stabilitas RAM.',
                    'icon' => 'restart_alt',
                    'color' => '#a855f7',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'MikroTik Auto Backup to E-Mail',
                    'slug' => 'auto-backup-email',
                    'category' => 'network',
                    'category_label' => 'Backup & Otomasi',
                    'description' => 'Script berkala ekspor konfigurasi (.backup dan .rsc) terenkripsi dan otomatis kirim lampiran ke email admin/NOC.',
                    'icon' => 'mail',
                    'color' => '#ec4899',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Block Access Modem ONT',
                    'slug' => 'block-access-modem',
                    'category' => 'security',
                    'category_label' => 'Keamanan',
                    'description' => 'Blokir akses web GUI modem ONT dari sisi pelanggan untuk mencegah pelanggan mengubah pengaturan bridge atau WAN.',
                    'icon' => 'block',
                    'color' => '#ef4444',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
                [
                    'title' => 'Reset All Counters',
                    'slug' => 'reset-counters',
                    'category' => 'maintenance',
                    'category_label' => 'Maintenance',
                    'description' => 'Bersihkan seluruh statistik byte dan counter paket pada interface, firewall filter, mangle, dan NAT MikroTik.',
                    'icon' => 'cleaning_services',
                    'color' => '#06b6d4',
                    'status' => 'upcoming',
                    'v6' => true,
                    'v7' => true,
                    'url' => null,
                ],
            ];

            // Filter tools
            $filteredTools = array_filter($tools, function ($t) use ($activeCategory, $search) {
                if ($activeCategory !== 'all' && $t['category'] !== $activeCategory) {
                    return false;
                }
                if (!empty($search)) {
                    $q = strtolower($search);
                    return str_contains(strtolower($t['title']), $q) || str_contains(strtolower($t['description']), $q);
                }
                return true;
            });
        @endphp

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
            @forelse ($filteredTools as $tool)
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; gap: 1.15rem; transition: transform 0.2s ease, border-color 0.2s ease; position: relative;"
                    onmouseover="this.style.borderColor='rgba(0, 224, 126, 0.4)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div>
                        {{-- Card Header: Icon & Badges --}}
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.875rem;">
                            <div style="width: 44px; height: 44px; border-radius: 0.75rem; background: rgba(0, 224, 126, 0.1); border: 1px solid rgba(0, 224, 126, 0.2); display: flex; align-items: center; justify-content: center; color: {{ $tool['color'] }};">
                                <span class="material-symbols-rounded" style="font-size: 24px;">{{ $tool['icon'] }}</span>
                            </div>

                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                @if ($tool['status'] === 'ready')
                                    <span style="font-size: 0.6875rem; font-weight: 800; padding: 0.2rem 0.55rem; border-radius: 9999px; background: rgba(0, 224, 126, 0.15); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3);">
                                        SIAP DIGUNAKAN
                                    </span>
                                @else
                                    <span style="font-size: 0.6875rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 9999px; background: rgba(148, 163, 184, 0.1); color: var(--muted); border: 1px solid var(--border);">
                                        SEGERA
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Tool Title --}}
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0; letter-spacing: -0.01em;">
                            {{ $tool['title'] }}
                        </h3>

                        {{-- Compatibility Pills --}}
                        <div style="display: flex; gap: 0.35rem; margin-top: 0.4rem;">
                            <span style="font-size: 0.625rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 4px; background: var(--surface2); color: #38bdf8; border: 1px solid var(--border);">
                                ROS 6
                            </span>
                            <span style="font-size: 0.625rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 4px; background: var(--surface2); color: #00e07e; border: 1px solid var(--border);">
                                ROS 7
                            </span>
                            <span style="font-size: 0.625rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 4px; background: var(--surface2); color: var(--muted); border: 1px solid var(--border);">
                                Direct Push
                            </span>
                        </div>

                        {{-- Description --}}
                        <p style="font-size: 0.8125rem; color: var(--muted); margin-top: 0.65rem; line-height: 1.45;">
                            {{ $tool['description'] }}
                        </p>
                    </div>

                    {{-- Action Button --}}
                    <div>
                        @if ($tool['url'])
                            <a href="{{ $tool['url'] }}" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.55rem 1rem; border-radius: 0.5rem; background: #00e07e; color: #0f172a; font-size: 0.8125rem; font-weight: 800; text-decoration: none; transition: opacity 0.2s ease;">
                                <span>Buka Tool Halaman Penuh</span>
                                <span class="material-symbols-rounded" style="font-size: 16px;">arrow_forward</span>
                            </a>
                        @else
                            <button type="button" disabled style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.55rem 1rem; border-radius: 0.5rem; background: var(--surface2); color: var(--muted); border: 1px solid var(--border); font-size: 0.8125rem; font-weight: 700; cursor: not-allowed;">
                                <span>Sedang Dikembangkan</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; padding: 3rem 1rem; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
                    <span class="material-symbols-rounded" style="font-size: 36px; color: var(--muted);">search_off</span>
                    <p style="font-size: 0.9375rem; color: var(--muted); margin-top: 0.5rem;">Tidak ada tools yang sesuai dengan pencarian Anda.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

