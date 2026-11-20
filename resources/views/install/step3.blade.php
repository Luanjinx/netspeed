@extends('install.layout')

@section('title', 'Langkah 3: Migrasi Database & Struktur Tabel')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="text-emerald-400">03.</span> Migrasi Database & Struktur Tabel
            </h2>
            <p class="text-sm text-slate-400 mt-1">
                Sistem akan membuat seluruh tabel yang dibutuhkan: Pelanggan, Tagihan & Invoice, Router MikroTik, Paket Internet, dan Pengaturan Billing.
            </p>
        </div>

        <!-- Migration Info Box -->
        <div class="p-4 rounded-xl bg-obsidian-800 border border-slate-800 space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-lg">
                    ⚡
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Eksekusi Skema Database</h3>
                    <p class="text-xs text-slate-400">Menjalankan <code>php artisan migrate --force</code> langsung melalui browser.</p>
                </div>
            </div>

            <!-- Progress Bar (Hidden initially) -->
            <div id="progressContainer" class="hidden space-y-2 pt-2">
                <div class="flex justify-between text-xs text-slate-400">
                    <span id="progressStatus">Memproses migrasi tabel...</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                    <div id="progressBar" class="h-full bg-emerald-500 transition-all duration-300 w-0"></div>
                </div>
            </div>
        </div>

        <!-- Terminal Output Window -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Console Log Migrasi</label>
                <span id="liveBadge" class="hidden text-[11px] text-emerald-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Berjalan...
                </span>
            </div>
            <div id="consoleOutput" class="w-full h-48 rounded-xl bg-obsidian-950 border border-slate-800/80 p-4 font-mono text-xs text-emerald-400/90 overflow-y-auto leading-relaxed whitespace-pre-wrap">
Klik tombol "Jalankan Migrasi Database" di bawah untuk memulai proses pembentukan tabel...
            </div>
        </div>

        <!-- Action Bottom -->
        <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('install.step2') }}" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-sm font-semibold transition">
                &larr; Kembali ke Database
            </a>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="button" id="btnStartMigrate" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <span>⚡</span> Jalankan Migrasi Database
                </button>

                <a href="{{ route('install.step4') }}" id="btnNextStep4" class="hidden w-full sm:w-auto text-center px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition">
                    Lanjut ke Setup Admin &rarr;
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const btnStart = document.getElementById('btnStartMigrate');
    const btnNext = document.getElementById('btnNextStep4');
    const consoleOutput = document.getElementById('consoleOutput');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');
    const liveBadge = document.getElementById('liveBadge');

    btnStart?.addEventListener('click', async function() {
        btnStart.disabled = true;
        btnStart.classList.add('opacity-50', 'cursor-not-allowed');
        btnStart.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-obsidian-950 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Sedang Migrasi...';

        progressContainer.classList.remove('hidden');
        liveBadge.classList.remove('hidden');
        progressBar.style.width = '35%';
        progressPercent.innerText = '35%';
        progressStatus.innerText = 'Mempersiapkan koneksi dan mengeksekusi tabel...';
        consoleOutput.innerText = '>> Memulai Artisan Migrate --force ...\n';

        try {
            const res = await fetch("{{ route('install.migrate', [], false) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await res.json();
            liveBadge.classList.add('hidden');

            if (data.success) {
                progressBar.style.width = '100%';
                progressPercent.innerText = '100%';
                progressStatus.innerText = 'Migrasi database berhasil diselesaikan!';
                consoleOutput.innerText += (data.output || 'Semua tabel berhasil dimigrasikan ke MySQL.') + '\n\n>> SUKSES! Struktur tabel telah siap digunakan.';
                
                btnStart.classList.add('hidden');
                btnNext.classList.remove('hidden');
            } else {
                progressBar.style.width = '100%';
                progressBar.classList.remove('bg-emerald-500');
                progressBar.classList.add('bg-rose-500');
                progressPercent.innerText = 'Gagal';
                progressStatus.innerText = 'Terjadi kesalahan saat proses migrasi:';
                consoleOutput.innerText += '\n[ERROR]\n' + (data.output || 'Terjadi kesalahan sistem saat migrasi.');
                btnStart.disabled = false;
                btnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                btnStart.innerHTML = '<span>⚡</span> Coba Migrasi Lagi';
            }
        } catch (e) {
            liveBadge.classList.add('hidden');
            progressBar.style.width = '100%';
            progressBar.classList.remove('bg-emerald-500');
            progressBar.classList.add('bg-rose-500');
            progressStatus.innerText = 'Koneksi terputus:';
            consoleOutput.innerText += '\n[FATAL ERROR] ' + e.message;
            btnStart.disabled = false;
            btnStart.classList.remove('opacity-50', 'cursor-not-allowed');
            btnStart.innerHTML = '<span>⚡</span> Coba Migrasi Lagi';
        }
    });
</script>
@endpush

