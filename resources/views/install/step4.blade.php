@extends('install.layout')

@section('title', 'Langkah 4: Akun Super Admin & Profil ISP')

@section('content')
    <form action="{{ route('install.step4.save') }}" method="POST" class="space-y-6">
        @csrf

        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="text-emerald-400">04.</span> Setup Akun Super Admin & Profil ISP
            </h2>
            <p class="text-sm text-slate-400 mt-1">
                Buat akun administrator utama untuk mengelola pelanggan, tagihan, dan konfigurasi jaringan.
            </p>
        </div>

        <!-- Super Admin Section -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">Akun Administrator (Login Panel)</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap Admin</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name', 'Administrator ISP') }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Login Admin</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@isp.net') }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password Admin</label>
                    <input type="password" name="admin_password" required minlength="8"
                        placeholder="Minimal 8 karakter"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Konfirmasi Password</label>
                    <input type="password" name="admin_password_confirmation" required minlength="8"
                        placeholder="Ulangi password"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">No. WhatsApp Admin</label>
                    <input type="text" name="admin_phone" value="{{ old('admin_phone', '081234567890') }}"
                        placeholder="08xxxxxxxxxx"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
            </div>
        </div>

        <!-- ISP Company Section -->
        <div class="space-y-4 pt-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">Profil & Identitas Bisnis ISP</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Perusahaan / Brand ISP</label>
                    <input type="text" name="company_name" value="{{ old('company_name', 'NetSpeed Internet Service') }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor CS / Helpdesk WhatsApp</label>
                    <input type="text" name="company_phone" value="{{ old('company_phone', '0812-3456-7890') }}"
                        placeholder="Nomor WA yang tertera di invoice"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Kantor Operasional</label>
                <input type="text" name="company_address" value="{{ old('company_address', 'Jl. Telekomunikasi No. 88') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-obsidian-800 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-emerald-500 transition">
            </div>

            <!-- Seed Option Checkbox -->
            <div class="p-3.5 rounded-xl bg-obsidian-800/60 border border-slate-800 flex items-start gap-3">
                <input type="checkbox" id="seed_sample_data" name="seed_sample_data" value="1" checked
                    class="mt-1 h-4 w-4 rounded border-slate-700 bg-obsidian-950 text-emerald-500 focus:ring-emerald-500">
                <label for="seed_sample_data" class="text-xs text-slate-300 cursor-pointer">
                    <strong class="text-white">Muat Data Sampel ISP:</strong> Otomatis membuatkan paket internet standar (Home Basic 10M, Home Pro 30M, Business 100M) dan profil Router MikroTik dasar untuk mempermudah awal setup.
                </label>
            </div>
        </div>

        <!-- Action Bottom -->
        <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('install.step3') }}" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-sm font-semibold transition">
                &larr; Kembali ke Migrasi
            </a>
            
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-obsidian-950 text-sm font-bold shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                Simpan & Lanjut ke Selesai &rarr;
            </button>
        </div>
    </form>
@endsection

