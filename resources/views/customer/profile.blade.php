@extends('layouts.customer')

@section('title', 'Profil & Pengaturan Akun')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="border-b border-[#232826] pb-6">
        <a href="{{ route('customer.dashboard') }}" class="text-xs text-[#9aa49f] hover:text-white transition flex items-center gap-1 mb-2">
            &larr; Kembali ke Dashboard
        </a>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Pengaturan Akun & Profil Pelanggan
        </h1>
        <p class="text-xs sm:text-sm text-[#9aa49f] mt-1">
            Lihat detail data langganan internet Anda dan perbarui kata sandi untuk keamanan akses login mandiri
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- LEFT COLUMN: Identitas & Langganan (Spans 2 cols) -->
        <div class="lg:col-span-2 space-y-8">

            <div class="glass-card p-6 sm:p-8 space-y-6">
                <div class="flex items-center gap-3 border-b border-[#232826] pb-4">
                    <div class="w-10 h-10 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 flex items-center justify-center text-[#00e07e] font-bold">
                        👤
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-white">Identitas Pelanggan</h3>
                        <p class="text-xs text-[#9aa49f]">Data registrasi resmi akun Anda di sistem ISP</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] font-medium block">Nomor / ID Pelanggan</span>
                        <span class="font-mono text-base font-extrabold text-[#00e07e]">{{ $customer->customer_number }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] font-medium block">Nama Lengkap</span>
                        <span class="text-base font-extrabold text-white">{{ $customer->name }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] font-medium block">Nomor WhatsApp Aktif</span>
                        <span class="font-semibold text-[#eef1f0]">{{ $customer->phone }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] font-medium block">Email Akun</span>
                        <span class="font-semibold text-[#eef1f0]">{{ $customer->email ?? auth()->user()->email ?? '-' }}</span>
                    </div>

                    <div class="sm:col-span-2 p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] font-medium block">Alamat Pemasangan</span>
                        <span class="font-medium text-[#eef1f0] leading-relaxed">{{ $customer->address ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Detail Jaringan & Paket Internet -->
            <div class="glass-card p-6 sm:p-8 space-y-6">
                <div class="flex items-center gap-3 border-b border-[#232826] pb-4">
                    <div class="w-10 h-10 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 flex items-center justify-center text-[#00e07e] font-bold">
                        🌐
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-white">Detail Paket & Jalur Infrastruktur</h3>
                        <p class="text-xs text-[#9aa49f]">Konfigurasi bandwidth dan jalur kabel optik ODP Anda</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Paket Internet</span>
                        <span class="font-extrabold text-white text-sm">{{ $customer->package?->name ?? 'Paket Kustom' }}</span>
                        <span class="text-[11px] text-[#00e07e] font-semibold block">{{ $customer->package?->speed_rate ?? '-' }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Biaya Langganan</span>
                        <span class="font-extrabold text-white text-sm">Rp {{ number_format($customer->package?->price ?? 0, 0, ',', '.') }}</span>
                        <span class="text-[11px] text-[#9aa49f] block">Jatuh tempo tgl {{ $customer->due_date ?? 10 }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Metode Koneksi</span>
                        <span class="font-extrabold text-white text-sm">{{ strtoupper($customer->connection_type ?? 'PPPoE') }}</span>
                        <span class="text-[11px] text-[#9aa49f] font-mono block">IP: {{ $customer->ip_address ?? 'Auto Pool' }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Router Gateway</span>
                        <span class="font-bold text-[#eef1f0]">{{ $customer->router?->name ?? 'MikroTik Core' }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Titik Tiang ODP</span>
                        <span class="font-bold text-[#eef1f0]">{{ $customer->odp?->name ?? '-' }}</span>
                        <span class="text-[11px] text-[#9aa49f] block">Nomor Port: {{ $customer->odp_port ?? '-' }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1">
                        <span class="text-[#9aa49f] text-[11px] block">Status Isolir Otomatis</span>
                        <span class="font-bold {{ $customer->auto_isolate ? 'text-[#00e07e]' : 'text-[#9aa49f]' }}">
                            {{ $customer->auto_isolate ? 'Aktif (Auto Isolir)' : 'Non-Aktif' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Security & Change Password -->
        <div class="space-y-8">

            <div class="glass-card p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3 border-b border-[#232826] pb-4">
                    <div class="w-10 h-10 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 flex items-center justify-center text-[#00e07e] font-bold">
                        🔒
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Ganti Kata Sandi</h3>
                        <p class="text-xs text-[#9aa49f]">Gunakan kata sandi kuat minimal 6 karakter</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('customer.profile.password') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password" required placeholder="Masukkan kata sandi lama" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Kata Sandi Baru</label>
                        <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" required minlength="6" placeholder="Ketik ulang kata sandi baru" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] font-extrabold text-xs shadow-lg shadow-[#00e07e]/20 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        <span>Perbarui Kata Sandi</span>
                    </button>
                </form>
            </div>

            <!-- Request Data Change Card -->
            <div class="glass-card p-6 space-y-3">
                <h4 class="text-xs font-bold text-[#eef1f0] uppercase tracking-wider flex items-center gap-1.5">
                    <span>💡</span>
                    <span>Perubahan Data Pelanggan</span>
                </h4>
                <p class="text-xs text-[#9aa49f] leading-relaxed">
                    Bila Anda ingin mengganti alamat pemasangan, pindah paket internet, atau memperbarui nomor WhatsApp resmi, silakan hubungi tim administrasi kami.
                </p>
                <div class="pt-2">
                    @php
                        $cleanPhone = preg_replace('/[^0-9]/', '', $companyPhone ?? '081234567890');
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $waEditUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode("Halo Admin {$companyName}, saya {$customer->name} (ID: {$customer->customer_number}). Saya ingin mengajukan perubahan data langganan/pindah paket.");
                    @endphp
                    <a href="{{ $waEditUrl }}" target="_blank" class="w-full py-2.5 px-4 rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] font-bold text-xs flex items-center justify-center gap-2 border border-[#232826] transition">
                        <span>💬 Hubungi Admin via WhatsApp</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
