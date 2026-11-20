@extends('install.layout')

@section('title', 'Langkah 2: Konfigurasi Database & Environment')

@section('content')
    <form action="{{ route('install.step2.save') }}" method="POST" class="space-y-6">
        @csrf

        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="text-emerald-400">02.</span> Konfigurasi Database & Environment
            </h2>
            <p class="text-sm text-slate-400 mt-1">
                Masukkan kredensial basis data MySQL yang telah Anda buat di cPanel (MySQL Databases).
            </p>
        </div>

        <!-- Section: General Application -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">Informasi Aplikasi</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Aplikasi / Brand</label>
                    <input type="text" name="app_name" value="{{ old('app_name', 'NetSpeed Billing') }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">URL Aplikasi (Domain)</label>
                    <input type="url" name="app_url" value="{{ old('app_url', $appUrl) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                    <p class="text-[11px] text-slate-500 mt-1">Otomatis mendeteksi domain server saat ini.</p>
                </div>
            </div>
        </div>

        <!-- Section: Database Configuration -->
        <div class="space-y-4 pt-2">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Koneksi Database MySQL</h3>
                <button type="button" id="btnTestDb" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition flex items-center gap-1.5 bg-emerald-950/60 border border-emerald-500/30 px-3 py-1 rounded-lg">
                    🔍 Tes Koneksi Database
                </button>
            </div>

            <!-- Test DB Alert Box -->
            <div id="dbTestAlert" class="hidden p-3.5 rounded-xl text-xs font-medium border flex items-center gap-2"></div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="{{ old('db_host', env('DB_HOST', '127.0.0.1')) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">Gunakan <code>127.0.0.1</code> atau <code>localhost</code>.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Port</label>
                    <input type="number" id="db_port" name="db_port" value="{{ old('db_port', env('DB_PORT', 3306)) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Database (Database Name)</label>
                    <input type="text" id="db_database" name="db_database" value="{{ old('db_database', env('DB_DATABASE', '')) }}" required
                        placeholder="contoh: nexstre1_billing"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username Database</label>
                    <input type="text" id="db_username" name="db_username" value="{{ old('db_username', env('DB_USERNAME', '')) }}" required
                        placeholder="contoh: nexstre1_admin"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password Database</label>
                    <input type="password" id="db_password" name="db_password" value="{{ old('db_password', '') }}"
                        placeholder="Password user database"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
            </div>
        </div>

        <!-- Action Bottom -->
        <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('install.step1') }}" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-sm font-semibold transition">
                &larr; Kembali ke Langkah 1
            </a>
            
            <button type="submit" id="btnSubmitStep2" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                Simpan & Lanjut ke Migrasi &rarr;
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const btnTest = document.getElementById('btnTestDb');
    const alertBox = document.getElementById('dbTestAlert');

    btnTest?.addEventListener('click', async function() {
        btnTest.disabled = true;
        btnTest.innerText = 'Memeriksa koneksi...';
        alertBox.className = 'hidden';

        const payload = {
            db_host: document.getElementById('db_host').value,
            db_port: document.getElementById('db_port').value,
            db_database: document.getElementById('db_database').value,
            db_username: document.getElementById('db_username').value,
            db_password: document.getElementById('db_password').value,
        };

        try {
            const res = await fetch("{{ route('install.test_db', [], false) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            alertBox.classList.remove('hidden');

            if (data.success) {
                alertBox.className = 'p-3.5 rounded-xl text-xs font-semibold border bg-emerald-950/60 border-emerald-500/40 text-emerald-300 flex items-center gap-2';
                alertBox.innerHTML = '<span>✅</span> ' + data.message;
            } else {
                alertBox.className = 'p-3.5 rounded-xl text-xs font-semibold border bg-rose-950/60 border-rose-500/40 text-rose-300 flex items-center gap-2';
                alertBox.innerHTML = '<span>⚠️</span> ' + data.message;
            }
        } catch (e) {
            alertBox.classList.remove('hidden');
            alertBox.className = 'p-3.5 rounded-xl text-xs font-semibold border bg-rose-950/60 border-rose-500/40 text-rose-300 flex items-center gap-2';
            alertBox.innerHTML = '<span>⚠️</span> Gagal menghubungi server pengujian: ' + e.message;
        } finally {
            btnTest.disabled = false;
            btnTest.innerText = '🔍 Tes Koneksi Database';
        }
    });
</script>
@endpush

