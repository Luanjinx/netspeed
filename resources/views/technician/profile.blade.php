@extends('layouts.technician')

@section('title', 'Profil Teknisi')

@section('content')
<div class="space-y-6 max-w-xl mx-auto">

    <!-- Profile Header Card -->
    <div class="rounded-2xl bg-surface border border-border p-6 text-center space-y-3">
        <div class="w-16 h-16 rounded-2xl bg-accent/15 border border-accent/30 text-accent flex items-center justify-center mx-auto">
            <span class="material-symbols-rounded text-3xl">badge</span>
        </div>

        <div>
            <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                {{ $technician->technician_code ?? 'TECH' }}
            </span>
            <h1 class="text-xl font-bold text-white mt-1">{{ $technician->name }}</h1>
            <p class="text-xs text-muted">{{ $technician->email }} &bull; {{ $technician->phone }}</p>
        </div>

        <div class="pt-2 flex items-center justify-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-accent/10 border border-accent/30 text-accent text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                Teknisi Aktif
            </span>
            @if($technician->work_area)
                <span class="px-3 py-1 rounded-full bg-surface2 border border-border text-muted text-xs font-medium">
                    Zona: {{ $technician->work_area }}
                </span>
            @endif
        </div>
    </div>

    <!-- Change Password Form -->
    <div class="rounded-2xl bg-surface border border-border p-6 space-y-4">
        <h2 class="text-sm font-bold text-white flex items-center gap-2">
            <span class="material-symbols-rounded text-accent text-lg">lock_reset</span>
            <span>Ubah Password Akun</span>
        </h2>

        <form action="{{ route('technician.profile.password') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-white mb-1.5">Password Lama</label>
                <input type="password" name="current_password" required placeholder="Masukkan password lama" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                @error('current_password')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-white mb-1.5">Password Baru</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                @error('password')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-white mb-1.5">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required placeholder="Ulangi password baru" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-accent hover:bg-accent-hover text-ink font-bold text-xs shadow-md transition">
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>

    <!-- Logout Card -->
    <div class="rounded-2xl bg-surface border border-border p-4 flex items-center justify-between">
        <div>
            <strong class="text-white text-xs block">Keluar dari Akun</strong>
            <span class="text-muted text-[11px]">Akhiri sesi kerja di perangkat ini</span>
        </div>

        <form action="{{ route('customer.logout') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-xl bg-danger/10 hover:bg-danger/20 text-danger border border-danger/30 text-xs font-bold transition">
                Keluar
            </button>
        </form>
    </div>

</div>
@endsection

