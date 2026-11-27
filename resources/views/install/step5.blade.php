@extends('install.layout')

@section('title', 'Langkah 5: Instalasi Selesai & Kunci Sistem')

@section('content')
    <div class="text-center space-y-6 max-w-xl mx-auto py-4">
        <!-- Success Icon -->
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-xl shadow-emerald-500/10">
            🎉
        </div>

        <div>
            <h2 class="text-2xl font-extrabold text-white">Selamat! Instalasi Sukses</h2>
            <p class="text-sm text-slate-400 mt-2">
                Aplikasi NetSpeed Billing telah berhasil dikonfigurasi dan siap digunakan sepenuhnya.
            </p>
        </div>

        <!-- Summary Card -->
        <div class="p-5 rounded-2xl bg-obsidian-800 border border-slate-800 text-left space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-700/60 pb-2">Ringkasan Konfigurasi</h3>
            
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 block">Alamat Aplikasi:</span>
                    <strong class="text-slate-200 font-mono">{{ url('/') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Panel Admin (Filament):</span>
                    <strong class="text-emerald-400 font-mono">{{ url('/admin') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Koneksi Database:</span>
                    <strong class="text-slate-200 font-mono">{{ env('DB_DATABASE') }} (MySQL)</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Status Keamanan:</span>
                    <strong class="text-emerald-400">Lock File Diaktifkan</strong>
                </div>
                @if(!empty($licenseData['purchase_code']))
                    <div class="col-span-2">
                        <span class="text-slate-500 block">Lisensi Aktif:</span>
                        <strong class="text-emerald-400 font-mono">{{ $licenseData['purchase_code'] }} ({{ $licenseData['domain'] ?? '-' }})</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Security Warning Alert -->
        <div class="p-4 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-200 text-xs text-left flex items-start gap-3">
            <span class="text-base">🔒</span>
            <div>
                <strong>Pengamanan Sistem:</strong>
                Saat Anda mengklik tombol di bawah, sistem akan membuat file pengunci <code>storage/installed</code> sehingga halaman instalasi ini tidak dapat dibuka kembali oleh siapapun.
            </div>
        </div>

        <!-- Finish Form -->
        <form action="{{ route('install.finish') }}" method="POST" class="pt-2">
            @csrf
            <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-base font-extrabold shadow-xl shadow-emerald-500/25 transition transform hover:-translate-y-0.5">
                🚀 Kunci Installer & Masuk ke Panel Admin &rarr;
            </button>
        </form>
    </div>
@endsection

