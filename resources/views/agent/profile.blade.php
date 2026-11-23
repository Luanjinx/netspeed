@extends('layouts.agent')

@section('title', 'Profil & Pengaturan Akun Agen')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20">
        <h1 class="text-xl font-extrabold text-white flex items-center gap-2 mb-1">
            <span class="material-symbols-rounded text-accent">account_circle</span>
            Profil Kemitraan & Loket Agen
        </h1>
        <p class="text-xs text-muted">Rincian data pendaftaran loket pembayaran dan nomor kontak terdaftar</p>
    </div>

    <!-- Agent Identity Card -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-muted">Informasi Akun Loket</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Nama Agen / Loket:</span>
                <span class="text-sm font-extrabold text-white mt-0.5 block">{{ $agent?->name ?? $user->name }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Kode Agen Resmi:</span>
                <span class="text-sm font-mono font-extrabold text-accent mt-0.5 block">{{ $agent?->agent_code ?? 'AGT-000' }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Nomor WhatsApp / HP:</span>
                <span class="text-sm font-semibold text-white mt-0.5 block">{{ $agent?->phone ?? $user->phone }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Email Login:</span>
                <span class="text-sm font-semibold text-white mt-0.5 block">{{ $user->email }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Komisi per Tagihan:</span>
                <span class="text-sm font-extrabold text-accent mt-0.5 block">Rp {{ number_format((float) ($agent?->commission_per_transaction ?? 0), 0, ',', '.') }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px] uppercase font-bold">Saldo Deposit Saat Ini:</span>
                <span class="text-sm font-black text-white mt-0.5 block">{{ $agent ? $agent->formatted_balance : 'Rp 0' }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-surface2 border border-border sm:col-span-2">
                <span class="text-muted block text-[10px] uppercase font-bold">Alamat Loket:</span>
                <span class="text-xs text-white mt-0.5 block">{{ $agent?->address ?: 'Belum diatur' }}</span>
            </div>
        </div>
    </div>

    <!-- Password Change Form -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-muted flex items-center gap-1.5">
            <span class="material-symbols-rounded text-base text-accent">key</span>
            Ganti Kata Sandi (Password)
        </h2>

        <form action="{{ route('agent.profile.password') }}" method="POST" class="space-y-4 max-w-md">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-muted mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-surface2 border border-border text-white text-xs focus:outline-none focus:border-accent">
                @error('current_password')
                    <span class="text-danger text-[11px] mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-muted mb-1">Password Baru (Min. 6 Karakter)</label>
                <input type="password" name="password" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-surface2 border border-border text-white text-xs focus:outline-none focus:border-accent">
                @error('password')
                    <span class="text-danger text-[11px] mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-muted mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-surface2 border border-border text-white text-xs focus:outline-none focus:border-accent">
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-xs shadow-md shadow-accent/20 transition flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">save</span>
                <span>Simpan Password Baru</span>
            </button>
        </form>
    </div>
</div>
@endsection

