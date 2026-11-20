<x-filament-panels::page>
    <!-- Quick Integration & Tools Hub -->
    <div class="settings-hub-wrapper" style="margin-bottom: 1.5rem; padding: 1.25rem 1.35rem; background: #131615; border: 1px solid #232826; border-radius: 1rem;">
        <div class="settings-hub-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #eef1f0; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.25);">
                        <span class="material-symbols-rounded" style="font-size: 17px;">dashboard_customize</span>
                    </span>
                    Pusat Alat &amp; Konsol Terpadu Sistem
                </h3>
                <p style="font-size: 0.775rem; color: #9aa49f; margin: 4px 0 0 0;">Akses cepat konsol teknis bot, push notifikasi, isolir, dan tabel database tanpa berpindah-pindah menu</p>
            </div>
            <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: #1a1e1c; color: #00e07e; border: 1px solid #232826; display: inline-flex; align-items: center; gap: 5px;">
                <span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span>
                4 Modul Aktif
            </span>
        </div>

        <div class="settings-hub-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.875rem;">
            <!-- Bot Telegram -->
            <a href="{{ url('/admin/telegram-bot') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none; transition: all 0.2s ease;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">smart_toy</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Konsol &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Bot Telegram NOC</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Uji webhook &amp; alert operasional live</p>
                </div>
            </a>

            <!-- OneSignal Broadcast -->
            <a href="{{ url('/admin/onesignal') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none; transition: all 0.2s ease;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">campaign</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Broadcast &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Push Notifikasi</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Kirim pesan push OneSignal ke user</p>
                </div>
            </a>

            <!-- Isolir & Captive Wizard -->
            <a href="{{ url('/admin/isolir-manager') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none; transition: all 0.2s ease;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">shield_lock</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Wizard &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Isolir &amp; Captive</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Generator script redirect MikroTik</p>
                </div>
            </a>

            <!-- Raw DB Settings Table -->
            <a href="{{ url('/admin/isp-settings') }}" class="settings-hub-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.1rem; background: #1a1e1c; border: 1px solid #232826; border-radius: 0.75rem; text-decoration: none; transition: all 0.2s ease;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="settings-hub-icon-box" style="width: 2.6rem; height: 2.6rem; border-radius: 0.6rem; background: #131615; border: 1px solid #232826; display: flex; align-items: center; justify-content: center; color: #00e07e;">
                        <span class="material-symbols-rounded" style="font-size: 22px;">database</span>
                    </div>
                    <span class="settings-hub-badge" style="font-size: 0.7rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #131615; color: #9aa49f; border: 1px solid #232826;">Tabel DB &rarr;</span>
                </div>
                <div>
                    <h4 class="settings-hub-title" style="font-size: 0.875rem; font-weight: 700; color: #eef1f0; margin: 0.75rem 0 0.25rem 0;">Database Settings</h4>
                    <p class="settings-hub-desc" style="font-size: 0.75rem; color: #9aa49f; margin: 0; line-height: 1.35;">Tabel mentah key-value konfigurasi</p>
                </div>
            </a>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end pt-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check" color="primary">
                Simpan Seluruh Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>