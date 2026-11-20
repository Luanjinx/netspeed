@if(config('app.demo'))
    <div class="w-full bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 px-4 py-2 text-xs font-semibold shadow-md flex items-center justify-between z-40 relative border-b border-amber-400/40">
        <div class="max-w-7xl mx-auto w-full flex items-center justify-center gap-2 text-center">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-950 text-amber-400 text-xs font-bold shadow-inner">
                ⚡
            </span>
            <span>
                <strong class="uppercase tracking-wider font-extrabold text-slate-950">Mode Demo Aktif:</strong>
                Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).
            </span>
        </div>
    </div>
@endif

