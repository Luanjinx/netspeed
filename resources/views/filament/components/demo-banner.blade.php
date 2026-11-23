@if(\App\Providers\DemoModeServiceProvider::isDemo())
    <div x-data="{ 
            open: {{ session('open_demo_modal') ? 'true' : 'false' }},
            redirectUrl: '{{ session('demo_redirect_url') }}'
         }"
         @open-demo-modal.window="open = true; if ($event.detail && $event.detail.url) { redirectUrl = $event.detail.url; }"
         x-on:keydown.escape.window="open = false"
         x-show="open"
         x-cloak
         style="display: none;"
         role="dialog"
         aria-modal="true">
        
        <!-- Backdrop Blur Overlay -->
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.78); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); z-index: 999998;"
             @click="open = false"></div>

        <!-- Centered Popup Modal Container -->
        <div x-show="open"
             style="position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 1.25rem; pointer-events: none;">
            
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 style="pointer-events: auto; width: 100%; max-width: 440px; background: var(--surface); border: 1px solid var(--border); border-radius: 1.25rem; padding: 1.75rem; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4), 0 0 25px rgba(0, 224, 126, 0.12); color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif;"
                 @click.stop>
                
                <!-- Header Icon & Title -->
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.1rem;">
                    <div style="width: 3rem; height: 3rem; border-radius: 0.875rem; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); display: flex; align-items: center; justify-content: center; color: #fbbf24; font-size: 1.6rem; flex-shrink: 0;">
                        ⚡
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text); margin: 0; line-height: 1.3;">Mode Demo Aktif</h3>
                        <p style="font-size: 0.75rem; color: #fbbf24; margin: 3px 0 0 0; font-weight: 600;">Akun Demonstrasi Read-Only</p>
                    </div>
                </div>

                <!-- Explanation Box -->
                <div style="font-size: 0.85rem; color: var(--muted); line-height: 1.55; background: var(--surface2); padding: 1rem 1.1rem; border-radius: 0.875rem; border: 1px solid var(--border); margin-bottom: 1.5rem;">
                    Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).
                </div>

                <!-- Action Button -->
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button"
                            @click="open = false; if (redirectUrl && (window.location.pathname.includes('/edit') || window.location.pathname.includes('/create'))) { window.location.href = redirectUrl; }"
                            style="padding: 0.65rem 1.4rem; border-radius: 0.75rem; background: #00e07e; color: #ffffff; font-weight: 800; font-size: 0.8125rem; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 224, 126, 0.35); transition: all 0.2s ease;">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-demo-modal', (event) => {
                window.dispatchEvent(new CustomEvent('open-demo-modal', { detail: event || {} }));
            });

            if (typeof Livewire.hook === 'function') {
                Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    succeed(({ snapshot, effect }) => {
                        const effectStr = JSON.stringify(effect || {});
                        if (effectStr.includes('Mode Demo') || effectStr.includes('open-demo-modal')) {
                            window.dispatchEvent(new CustomEvent('open-demo-modal'));
                        }
                    });
                });
            }
        });
    </script>
@endif
