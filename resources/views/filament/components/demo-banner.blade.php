@if(\App\Providers\DemoModeServiceProvider::isDemo())
    <div x-data="{ open: false }"
         @open-demo-modal.window="open = true"
         x-on:keydown.escape.window="open = false"
         x-show="open"
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        
        <!-- Backdrop Blur Overlay -->
        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
             @click="open = false"></div>

        <!-- Popup Modal Card -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="open"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-[#131615] border border-[#232826] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md p-6"
                 @click.stop>
                
                <!-- Header Icon & Title -->
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-2xl font-bold shadow-inner flex-shrink-0">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-wide">Mode Demo Aktif</h3>
                        <p class="text-xs text-amber-400/90 font-medium">Akun Demonstrasi Read-Only</p>
                    </div>
                </div>

                <!-- Explanation Box -->
                <div class="mt-2 text-sm text-slate-300 leading-relaxed bg-[#1a1e1c] p-4 rounded-xl border border-[#232826]">
                    Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).
                </div>

                <!-- Action Button -->
                <div class="mt-5 flex justify-end">
                    <button type="button"
                            @click="open = false"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/20 transition cursor-pointer">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-demo-modal', () => {
                window.dispatchEvent(new CustomEvent('open-demo-modal'));
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
