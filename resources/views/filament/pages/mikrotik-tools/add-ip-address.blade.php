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
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0;">
                            Tambah IP Address MikroTik
                        </h2>
                        <span style="font-size: 0.6875rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 9999px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
                            IP MANAGEMENT
                        </span>
                    </div>
                    <p style="font-size: 0.8125rem; color: var(--muted); margin-top: 0.2rem;">
                        Tetapkan IP Address pada interface router, tentukan subnet CIDR, dan push langsung ke MikroTik.
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; align-items: start;">
            {{-- Column 1: Configuration Form --}}
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.625rem; padding-bottom: 0.875rem; border-bottom: 1px solid var(--border);">
                    <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: rgba(56, 189, 248, 0.12); color: #38bdf8; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">lan</span>
                    </div>
                    <div>
                        <h3 style="font-size: 0.9375rem; font-weight: 800; color: var(--text); margin: 0;">Parameter IP Address</h3>
                        <p style="font-size: 0.75rem; color: var(--muted); margin: 0;">Isi alamat IP beserta subnet mask CIDR dan interface</p>
                    </div>
                </div>

                {{-- IP Address Field --}}
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                        IP Address & Subnet (CIDR) <span style="color: #f27b7b;">*</span>
                    </label>
                    <input type="text" wire:model.live="ipAddress" placeholder="cth: 192.168.10.1/24 atau 10.10.10.1/30"
                        style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                    <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">
                        Wajib sertakan prefix panjang subnet mask (contoh: <code>/24</code>, <code>/28</code>, <code>/30</code>).
                    </span>
                    @error('ipAddress') <span style="font-size: 0.6875rem; color: #f27b7b; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                </div>

                {{-- Interface Field --}}
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                        Nama Interface <span style="color: #f27b7b;">*</span>
                    </label>
                    <input type="text" wire:model.live="interface" placeholder="cth: ether2, bridge-lan, vlan100"
                        style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                    <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">
                        Tuliskan nama interface target pada router (contoh: <code>ether1</code>, <code>ether2</code>, <code>bridge-lan</code>).
                    </span>
                    @error('interface') <span style="font-size: 0.6875rem; color: #f27b7b; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                </div>

                {{-- Network Field (Optional) --}}
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                        Network Address (Opsional)
                    </label>
                    <input type="text" wire:model.live="network" placeholder="cth: 192.168.10.0 (kosongkan jika otomatis)"
                        style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                    <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">
                        Dapat dikosongkan, RouterOS akan menghitung network otomatis berdasarkan subnet CIDR.
                    </span>
                </div>

                {{-- Comment Field --}}
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                        Catatan / Komentar
                    </label>
                    <input type="text" wire:model.live="comment" placeholder="Keterangan interface IP di MikroTik"
                        style="width: 100%; font-size: 0.875rem; border-radius: 0.5rem; padding: 0.55rem 0.85rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); outline: none;">
                </div>

                {{-- Status Toggle --}}
                <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.875rem 1rem;">
                    <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                        <div>
                            <strong style="font-size: 0.8125rem; color: var(--text); display: block;">
                                Nonaktifkan IP (Disabled)
                            </strong>
                            <span style="font-size: 0.75rem; color: var(--muted);">
                                Centang jika ingin menambahkan IP dalam keadaan nonaktif (disabled=yes).
                            </span>
                        </div>
                        <input type="checkbox" wire:model.live="disabled" style="accent-color: #f27b7b; width: 18px; height: 18px;">
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
                            <span class="material-symbols-rounded" style="font-size: 18px; color: #38bdf8;">terminal</span>
                            <span style="font-size: 0.8125rem; font-weight: 800; color: var(--text);">
                                RouterOS Script Output ({{ strtoupper($rosVersion) }})
                            </span>
                        </div>

                        <button type="button" @click="copyScript()"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.85rem; border-radius: 0.5rem; background: #38bdf8; border: none; color: #0f172a; font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                            <span class="material-symbols-rounded" style="font-size: 15px;" x-text="copied ? 'check' : 'content_copy'"></span>
                            <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Salin Script (Copy)'"></span>
                        </button>
                    </div>

                    <div style="background: #090d16; padding: 1.25rem; overflow-x: auto; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; line-height: 1.6; color: #38bdf8; min-height: 150px;">
                        <pre style="margin: 0; white-space: pre-wrap; word-break: break-all;" x-ref="scriptCode">{{ $this->generatedScript }}</pre>
                    </div>

                    <div style="padding: 0.65rem 1.25rem; background: var(--surface2); border-top: 1px solid var(--border); font-size: 0.6875rem; color: var(--muted); display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 14px; color: #38bdf8;">info</span>
                        <span>Anda dapat mem-paste script ke Terminal WinBox, atau push langsung ke router aktif di bawah.</span>
                    </div>
                </div>

                {{-- Card B: Push Directly to MikroTik Router --}}
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.875rem; border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 0.625rem;">
                            <div style="width: 32px; height: 32px; border-radius: 0.5rem; background: rgba(56, 189, 248, 0.12); color: #38bdf8; display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">cloud_upload</span>
                            </div>
                            <div>
                                <h3 style="font-size: 0.9375rem; font-weight: 800; color: var(--text); margin: 0;">Push Langsung ke Router</h3>
                                <p style="font-size: 0.75rem; color: var(--muted); margin: 0;">Pasang IP address otomatis ke MikroTik target</p>
                            </div>
                        </div>

                        <span style="font-size: 0.6875rem; font-weight: 700; color: #38bdf8; background: rgba(56, 189, 248, 0.1); padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid rgba(56, 189, 248, 0.25);">
                            API Port 8728
                        </span>
                    </div>

                    {{-- Router Selector --}}
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text); margin-bottom: 0.35rem;">
                            Pilih Router MikroTik Tujuan <span style="color: #f27b7b;">*</span>
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
                            style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem 1.25rem; border-radius: 0.5rem; background: #38bdf8; border: none; color: #0f172a; font-size: 0.875rem; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                            <span wire:loading.remove wire:target="pushToMikrotik" class="material-symbols-rounded" style="font-size: 20px;">send</span>
                            <span wire:loading wire:target="pushToMikrotik" class="material-symbols-rounded" style="font-size: 20px; animation: spin 1s linear infinite;">sync</span>
                            <span wire:loading.remove wire:target="pushToMikrotik">Push IP Address ke MikroTik</span>
                            <span wire:loading wire:target="pushToMikrotik">Menerapkan IP ke Interface...</span>
                        </button>
                    </div>

                    {{-- Push Result Status Alert --}}
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
