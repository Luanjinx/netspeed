<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Navigation & Header Bar --}}
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1rem 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('filament.admin.pages.mikrotik-tools') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 0.5rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); text-decoration: none; transition: all 0.2s ease;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">arrow_back</span>
                </a>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0;">
                            MikroTik Basic Configuration Script Generator
                        </h2>
                        <span style="font-size: 0.6875rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 9999px; background: rgba(0, 224, 126, 0.15); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3);">
                            CORE SETUP
                        </span>
                    </div>
                    <p style="font-size: 0.8125rem; color: var(--muted); margin-top: 0.2rem;">
                        Generate konfigurasi dasar lengkap MikroTik (Identity, NTP, WAN, Bridge LAN, DHCP, NAT, Firewall &amp; Service Hardening).
                    </p>
                </div>
            </div>

            {{-- RouterOS Version Selector Pills --}}
            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--surface2); border: 1px solid var(--border); padding: 0.25rem; border-radius: 0.65rem;">
                <button type="button" wire:click="$set('rosVersion', 'v6')"
                    style="padding: 0.35rem 0.85rem; font-size: 0.75rem; font-weight: 800; border-radius: 0.45rem; border: none; cursor: pointer; transition: all 0.2s ease; {{ $rosVersion === 'v6' ? 'background: #38bdf8; color: #0f172a;' : 'background: transparent; color: var(--muted);' }}">
                    RouterOS v6
                </button>
                <button type="button" wire:click="$set('rosVersion', 'v7')"
                    style="padding: 0.35rem 0.85rem; font-size: 0.75rem; font-weight: 800; border-radius: 0.45rem; border: none; cursor: pointer; transition: all 0.2s ease; {{ $rosVersion === 'v7' ? 'background: #00e07e; color: #0f172a;' : 'background: transparent; color: var(--muted);' }}">
                    RouterOS v7
                </button>
            </div>
        </div>

        {{-- Main Two-Column Layout --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; align-items: start;">
            {{-- Column 1: Configuration Form --}}
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.625rem; padding-bottom: 0.875rem; border-bottom: 1px solid var(--border);">
                    <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: rgba(0, 224, 126, 0.12); color: #00e07e; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                    </div>
                    <div>
                        <h3 style="font-size: 0.9375rem; font-weight: 800; color: var(--text); margin: 0;">Parameter Konfigurasi Dasar</h3>
                        <p style="font-size: 0.75rem; color: var(--muted); margin: 0;">Sesuaikan identitas, uplink WAN, jaringan LAN, dan firewall</p>
                    </div>
                </div>

                {{-- 1. System Identity & Timezone --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                            Identity Router <span style="color: #00e07e;">*</span>
                        </label>
                        <input type="text" wire:model.live="routerIdentity" placeholder="NetSpeed-Router"
                            style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                            Zona Waktu (Timezone)
                        </label>
                        <select wire:model.live="timezone"
                            style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                            <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                        </select>
                    </div>
                </div>

                {{-- 2. WAN Configuration --}}
                <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; gap: 0.875rem;">
                    <strong style="font-size: 0.8125rem; color: #38bdf8; display: block;">Uplink Internet (WAN)</strong>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                                Interface WAN <span style="color: #00e07e;">*</span>
                            </label>
                            <input type="text" wire:model.live="wanInterface" placeholder="ether1"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                                Metode Koneksi WAN
                            </label>
                            <select wire:model.live="wanMode"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                                <option value="dhcp">DHCP Client (Otomatis)</option>
                                <option value="static">Static IP Address</option>
                                <option value="pppoe">PPPoE Client</option>
                            </select>
                        </div>
                    </div>

                    @if ($wanMode === 'static')
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">IP WAN (CIDR)</label>
                                <input type="text" wire:model.live="wanIpCidr" placeholder="192.168.1.2/24"
                                    style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Gateway ISP</label>
                                <input type="text" wire:model.live="wanGateway" placeholder="192.168.1.1"
                                    style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                            </div>
                        </div>
                    @elseif ($wanMode === 'pppoe')
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Username PPPoE</label>
                                <input type="text" wire:model.live="wanPppoeUser"
                                    style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Password PPPoE</label>
                                <input type="text" wire:model.live="wanPppoePass"
                                    style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 3. LAN & DHCP Server --}}
                <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; gap: 0.875rem;">
                    <strong style="font-size: 0.8125rem; color: #00e07e; display: block;">Jaringan Lokal (Bridge LAN &amp; DHCP)</strong>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Nama Bridge</label>
                            <input type="text" wire:model.live="bridgeName" placeholder="bridge-lan"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">IP Gateway LAN (CIDR)</label>
                            <input type="text" wire:model.live="lanIpCidr" placeholder="192.168.88.1/24"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">Port Anggota Bridge (pisahkan koma)</label>
                        <input type="text" wire:model.live="lanInterfaces" placeholder="ether2,ether3,ether4,ether5"
                            style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">DNS Servers</label>
                            <input type="text" wire:model.live="dnsServers" placeholder="8.8.8.8,1.1.1.1"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">DHCP Pool Range</label>
                            <input type="text" wire:model.live="dhcpRange" placeholder="192.168.88.10-192.168.88.254"
                                style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.5rem 0.75rem; background: var(--surface); border: 1px solid var(--border); color: var(--text); outline: none;">
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--text); cursor: pointer;">
                            <input type="checkbox" wire:model.live="enableDhcpServer" style="accent-color: #00e07e; width: 15px; height: 15px;">
                            <span>Aktifkan DHCP Server</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--text); cursor: pointer;">
                            <input type="checkbox" wire:model.live="allowRemoteDns" style="accent-color: #00e07e; width: 15px; height: 15px;">
                            <span>Allow Remote DNS Requests</span>
                        </label>
                    </div>
                </div>

                {{-- 4. NTP & Firewall Hardening Toggles --}}
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; background: var(--surface2); border: 1px solid var(--border); cursor: pointer;">
                        <input type="checkbox" wire:model.live="enableNtp" style="margin-top: 0.15rem; accent-color: #00e07e; width: 16px; height: 16px;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: var(--text); display: block;">Sinkronisasi Jam Otomatis (NTP Client)</strong>
                            <span style="font-size: 0.725rem; color: var(--muted);">Menyesuaikan sintaks NTP otomatis untuk RouterOS {{ strtoupper($rosVersion) }}.</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; background: var(--surface2); border: 1px solid var(--border); cursor: pointer;">
                        <input type="checkbox" wire:model.live="enableNat" style="margin-top: 0.15rem; accent-color: #00e07e; width: 16px; height: 16px;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: var(--text); display: block;">NAT Masquerade (Akses Internet Klien)</strong>
                            <span style="font-size: 0.725rem; color: var(--muted);">Menambahkan srcnat masquerade ke interface list WAN.</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; background: rgba(0, 224, 126, 0.08); border: 1px solid rgba(0, 224, 126, 0.25); cursor: pointer;">
                        <input type="checkbox" wire:model.live="enableBasicFirewall" style="margin-top: 0.15rem; accent-color: #00e07e; width: 16px; height: 16px;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: #00e07e; display: block;">Firewall Dasar Stateful (Rekomendasi)</strong>
                            <span style="font-size: 0.725rem; color: var(--muted);">Blokir koneksi invalid dan akses langsung dari WAN ke router.</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; background: var(--surface2); border: 1px solid var(--border); cursor: pointer;">
                        <input type="checkbox" wire:model.live="enableFasttrack" style="margin-top: 0.15rem; accent-color: #38bdf8; width: 16px; height: 16px;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: var(--text); display: block;">Aktifkan FastTrack Connection</strong>
                            <span style="font-size: 0.725rem; color: var(--muted);">Mempercepat throughput CPU (nonaktifkan jika memakai Simple Queue / PCQ).</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; background: var(--surface2); border: 1px solid var(--border); cursor: pointer;">
                        <input type="checkbox" wire:model.live="disableUnusedServices" style="margin-top: 0.15rem; accent-color: #00e07e; width: 16px; height: 16px;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: var(--text); display: block;">Matikan Service Tidak Aman (Telnet, FTP, WWW)</strong>
                            <span style="font-size: 0.725rem; color: var(--muted);">Mempertahankan WinBox dan API (8728) agar tetap terhubung ke billing.</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Column 2: Generated Script & Direct Push --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                {{-- Card A: Script Output with Copy Button --}}
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; overflow: hidden; display: flex; flex-direction: column;"
                    x-data="{ copied: false, copyScript() { navigator.clipboard.writeText($refs.scriptCode.innerText); this.copied = true; setTimeout(() => this.copied = false, 2500); } }">
                    <div style="padding: 0.875rem 1.25rem; background: var(--surface2); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="material-symbols-rounded" style="font-size: 18px; color: #00e07e;">terminal</span>
                            <span style="font-size: 0.8125rem; font-weight: 800; color: var(--text);">
                                RouterOS Basic Config Output ({{ strtoupper($rosVersion) }})
                            </span>
                        </div>

                        <button type="button" @click="copyScript()"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.85rem; border-radius: 0.5rem; background: #00e07e; border: none; color: #0f172a; font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                            <span class="material-symbols-rounded" style="font-size: 15px;" x-text="copied ? 'check' : 'content_copy'"></span>
                            <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Salin Script (Copy)'"></span>
                        </button>
                    </div>

                    <div style="background: #090d16; padding: 1.25rem; overflow-x: auto; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; line-height: 1.6; color: #6ee7b7; min-height: 240px;">
                        <pre style="margin: 0; white-space: pre-wrap; word-break: break-word;" x-ref="scriptCode">{{ $this->generatedScript }}</pre>
                    </div>

                    <div style="padding: 0.65rem 1.25rem; background: var(--surface2); border-top: 1px solid var(--border); font-size: 0.6875rem; color: var(--muted); display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 14px; color: #00e07e;">verified</span>
                        <span>Sintaks otomatis menyesuaikan perbedaan RouterOS v6 dan RouterOS v7 (seperti format NTP Client).</span>
                    </div>
                </div>

                {{-- Card B: Push Directly to MikroTik Router --}}
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.875rem; border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 0.625rem;">
                            <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: rgba(0, 224, 126, 0.12); color: #00e07e; display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">cloud_upload</span>
                            </div>
                            <div>
                                <h3 style="font-size: 0.9375rem; font-weight: 800; color: var(--text); margin: 0;">Push Langsung ke Router</h3>
                                <p style="font-size: 0.75rem; color: var(--muted); margin: 0;">Jalankan konfigurasi dasar langsung ke router target</p>
                            </div>
                        </div>

                        <span style="font-size: 0.6875rem; font-weight: 700; color: #00e07e; background: rgba(0, 224, 126, 0.1); padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid rgba(0, 224, 126, 0.25);">
                            API Port 8728
                        </span>
                    </div>

                    {{-- Router Selector --}}
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                            Pilih Router MikroTik Tujuan <span style="color: #00e07e;">*</span>
                        </label>
                        <select wire:model.live="selectedRouterId"
                            style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                            <option value="">-- Pilih Router Aktif --</option>
                            @foreach (\App\Models\Router::where('is_active', true)->get() as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->host }}:{{ $r->port }})</option>
                            @endforeach
                        </select>
                        @error('selectedRouterId') <span style="font-size: 0.6875rem; color: #f27b7b; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>

                    {{-- Push Button --}}
                    <div>
                        <button type="button" wire:click="pushToMikrotik" wire:loading.attr="disabled"
                            style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem 1.25rem; border-radius: 0.5rem; background: #00e07e; border: none; color: #0f172a; font-size: 0.875rem; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                            <span wire:loading.remove wire:target="pushToMikrotik" class="material-symbols-rounded" style="font-size: 20px;">send</span>
                            <span wire:loading wire:target="pushToMikrotik" class="material-symbols-rounded" style="font-size: 20px; animation: spin 1s linear infinite;">sync</span>
                            <span wire:loading.remove wire:target="pushToMikrotik">Push Basic Config ke MikroTik</span>
                            <span wire:loading wire:target="pushToMikrotik">Menerapkan Konfigurasi...</span>
                        </button>
                    </div>

                    @if ($pushResult)
                        <div style="padding: 1rem; border-radius: 0.75rem; background: {{ $pushResult['success'] ? 'rgba(0, 224, 126, 0.08)' : 'rgba(242, 123, 123, 0.08)' }}; border: 1px solid {{ $pushResult['success'] ? 'rgba(0, 224, 126, 0.3)' : 'rgba(242, 123, 123, 0.3)' }}; display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 22px; color: {{ $pushResult['success'] ? '#00e07e' : '#f27b7b' }}; margin-top: -2px;">
                                {{ $pushResult['success'] ? 'check_circle' : 'error' }}
                            </span>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="font-size: 0.8125rem; color: {{ $pushResult['success'] ? '#00e07e' : '#f27b7b' }};">
                                        {{ $pushResult['success'] ? 'Berhasil Diterapkan ke Router' : 'Gagal Menerapkan ke Router' }}
                                    </strong>
                                    <span style="font-size: 0.6875rem; color: var(--muted);">{{ $pushResult['timestamp'] }}</span>
                                </div>
                                <div style="font-size: 0.8125rem; color: var(--text); margin-top: 0.35rem; line-height: 1.45;">
                                    {{ $pushResult['message'] }}
                                </div>
                                <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.35rem;">
                                    Target Router: <strong>{{ $pushResult['router_name'] }}</strong> ({{ $pushResult['router_host'] }})
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

