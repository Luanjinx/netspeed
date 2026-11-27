@extends('install.layout')

@section('title', 'Aktivasi Lisensi Aplikasi')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="text-emerald-400">🔑</span> Aktivasi Lisensi
            </h2>
            <p class="text-sm text-slate-400 mt-1">
                Masukkan <strong>Purchase Code / License Key</strong> untuk memverifikasi lisensi instalasi pada domain server <code class="text-emerald-400 font-mono">{{ $domain }}</code> sebelum mengonfigurasi database.
            </p>
        </div>

        @if($licenseData)
            <div class="p-4 rounded-xl bg-emerald-950/50 border border-emerald-500/40 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-300 uppercase tracking-wider">✅ Lisensi Terverifikasi (Ed25519)</span>
                    <span class="text-[11px] text-emerald-400 font-mono">{{ $licenseData['domain'] ?? $domain }}</span>
                </div>
                <div class="text-xs text-slate-300 flex flex-wrap gap-x-6 gap-y-1">
                    <span>Purchase Code: <code class="text-emerald-300 font-mono">{{ $licenseData['purchase_code'] }}</code></span>
                    <span>Tipe: <strong class="text-emerald-300 uppercase">{{ $licenseData['license_type'] ?? 'regular' }}</strong></span>
                </div>
            </div>
        @endif

        <form action="{{ route('install.license.activate') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="purchase_code" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Purchase Code / License Key
                </label>
                <input
                    type="text"
                    id="purchase_code"
                    name="purchase_code"
                    value="{{ old('purchase_code', $licenseData['purchase_code'] ?? '') }}"
                    placeholder="Contoh: XXXX-XXXX-XXXX-XXXX"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm font-mono focus:outline-none focus:border-emerald-500 transition"
                >
                <p class="text-[11px] text-slate-500 mt-1">
                    Sistem akan memvalidasi kunci secara online dan menyimpan sertifikat lisensi di <code>storage/app/license.json</code>.
                </p>
            </div>

            <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('install.step1') }}" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-sm font-semibold transition">
                    &larr; Kembali
                </a>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    @if($licenseData)
                        <a href="{{ route('install.step2') }}" class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition">
                            Lanjut ke Database &rarr;
                        </a>
                    @endif
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl {{ $licenseData ? 'border border-emerald-500/40 text-emerald-300 hover:bg-emerald-950/50' : 'bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 shadow-lg shadow-emerald-500/20' }} text-sm font-bold transition">
                        {{ $licenseData ? 'Aktivasi Ulang' : 'Verifikasi & Lanjut →' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

