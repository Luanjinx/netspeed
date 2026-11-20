@extends('install.layout')

@section('title', 'Langkah 1: Persyaratan Server & Izin Folder')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="text-emerald-400">01.</span> Persyaratan Server & Izin Folder
            </h2>
            <p class="text-sm text-slate-400 mt-1">
                Sistem memeriksa kesiapan modul PHP dan izin tulis direktori hosting agar aplikasi dapat berjalan optimal.
            </p>
        </div>

        <!-- PHP Version Card -->
        <div class="p-4 rounded-xl bg-obsidian-800 border border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">
                    PHP
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Versi PHP</h3>
                    <p class="text-xs text-slate-400">Minimal: v{{ $requirements['php_version']['required'] }} &bull; Terdeteksi: <strong class="text-slate-200">v{{ $requirements['php_version']['current'] }}</strong></p>
                </div>
            </div>
            @if($requirements['php_version']['passed'])
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-950 border border-emerald-500/30 text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Memenuhi Syarat
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-950 border border-rose-500/30 text-rose-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Tidak Memenuhi
                </span>
            @endif
        </div>

        <!-- PHP Extensions Grid -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Ekstensi PHP Wajib</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                @foreach($requirements['extensions'] as $ext => $info)
                    <div class="p-3 rounded-lg bg-obsidian-800/80 border border-slate-800/80 flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-300">{{ $info['name'] }}</span>
                        @if($info['passed'])
                            <span class="text-emerald-400 text-xs font-bold flex items-center gap-1">
                                ✓ <span class="hidden sm:inline text-[11px] font-normal text-emerald-500">Aktif</span>
                            </span>
                        @else
                            <span class="text-rose-400 text-xs font-bold flex items-center gap-1">
                                ✕ <span class="hidden sm:inline text-[11px] font-normal text-rose-500">Nonaktif</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Directory Permissions -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Izin Tulis Folder (Permissions 775 / 644)</h3>
                <button type="button" id="btnAutoFix" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition flex items-center gap-1.5 underline">
                    ⚡ Perbaiki Izin Otomatis
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @foreach($requirements['directories'] as $name => $info)
                    <div class="p-3 rounded-lg bg-obsidian-800/80 border border-slate-800/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-mono">📁</span>
                            <span class="text-xs font-medium text-slate-300 font-mono">{{ $info['path'] }}</span>
                        </div>
                        @if($info['passed'])
                            <span class="text-emerald-400 text-xs font-semibold">Writable (0775)</span>
                        @else
                            <span class="text-rose-400 text-xs font-semibold">Permission Denied</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Bottom -->
        <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-500 text-center sm:text-left">
                @if($requirements['all_passed'])
                    <span class="text-emerald-400 font-medium">✓ Semua modul & perizinan server telah siap.</span>
                @else
                    <span class="text-amber-400 font-medium">⚠️ Terdapat modul atau folder yang belum sesuai. Klik "Perbaiki Izin Otomatis" atau aktifkan modul di cPanel.</span>
                @endif
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('install.step1') }}" class="w-1/2 sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-sm font-semibold transition">
                    Cek Ulang
                </a>
                @if($requirements['all_passed'])
                    <a href="{{ route('install.step2') }}" class="w-1/2 sm:w-auto text-center px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition">
                        Lanjut ke Database &rarr;
                    </a>
                @else
                    <button disabled class="w-1/2 sm:w-auto text-center px-6 py-2.5 rounded-xl bg-slate-800 text-slate-500 text-sm font-bold cursor-not-allowed">
                        Lanjut ke Database &rarr;
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('btnAutoFix')?.addEventListener('click', async function() {
        this.disabled = true;
        this.innerText = 'Sedang memperbaiki...';
        
        try {
            const res = await fetch("{{ route('install.fix_permissions') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            alert(data.message);
            window.location.reload();
        } catch (e) {
            alert('Gagal menjalankan auto-fix: ' + e.message);
            this.disabled = false;
            this.innerText = '⚡ Perbaiki Izin Otomatis';
        }
    });
</script>
@endpush

