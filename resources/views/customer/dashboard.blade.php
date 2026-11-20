@extends('layouts.customer')

@section('title', 'Dashboard Pelanggan')

@section('content')
<div class="space-y-8">

    <!-- Hero / Account Summary Header -->
    <div class="relative overflow-hidden rounded-2xl bg-[#131615] border border-[#232826] p-6 sm:p-8 backdrop-blur-xl shadow-xl shadow-black/40">
        <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-[#00e07e]/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-6 top-1/2 -translate-y-1/2 hidden lg:block opacity-90 hover:opacity-100 transition-opacity">
            <img src="{{ asset('images/mascot-wifi-phone.webp') }}" alt="Mascot NetSpeed" class="w-36 h-auto float-anim drop-shadow-[0_10px_25px_rgba(0,224,126,0.25)]">
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-md bg-[#1a1e1c] text-[#eef1f0] border border-[#232826]">
                        ID: {{ $customer->customer_number }}
                    </span>
                    @if($customer->status === 'active')
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-[#00e07e]/15 text-[#00e07e] border border-[#00e07e]/30">
                            <span class="w-2 h-2 rounded-full bg-[#00e07e] animate-pulse"></span>
                            Layanan Aktif
                        </span>
                    @elseif($customer->status === 'isolated')
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-[#f27b7b]/15 text-[#f27b7b] border border-[#f27b7b]/30">
                            <span class="w-2 h-2 rounded-full bg-[#f27b7b]"></span>
                            Layanan Diisolir (Menunggu Pembayaran)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full bg-[#f2c56b]/15 text-[#f2c56b] border border-[#f2c56b]/30">
                            Status: {{ ucfirst($customer->status ?? 'Non-Aktif') }}
                        </span>
                    @endif

                    <span class="text-xs px-2.5 py-1 rounded-md bg-[#1a1e1c] text-[#9aa49f] border border-[#232826] font-medium">
                        {{ strtoupper($customer->connection_type ?? 'PPPoE') }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Halo, <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-[#eef1f0] to-[#00e07e]">{{ $customer->name }}</span> 👋
                </h1>
                <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                    Paket Internet: <strong class="text-[#eef1f0]">{{ $customer->package?->name ?? 'Paket Kustom' }}</strong> 
                    ({{ $customer->package?->speed_rate ?? 'Speed Maksimal' }}) &bull; Jatuh tempo tiap tanggal 
                    <strong class="text-[#00e07e]">{{ $customer->due_date ?? 10 }}</strong>
                </p>
            </div>

            <!-- Quick Meta Badge -->
            <div class="flex items-center gap-3">
                <a href="#tagihan-section" class="px-4 py-2.5 rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] font-extrabold text-xs shadow-lg shadow-[#00e07e]/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Cek Tagihan & QRIS</span>
                </a>
                <a href="{{ route('customer.tickets') }}" class="px-4 py-2.5 rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] font-bold text-xs border border-[#232826] transition flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#f2c56b]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Lapor Gangguan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN GRID SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- LEFT COLUMN: BILLING & QRIS (Spans 2 cols on lg) -->
        <div class="lg:col-span-2 space-y-8" id="tagihan-section">
            
            <!-- 1. Bill Card -->
            @if($unpaidInvoice)
                <div class="glass-card glass-card-accent p-6 sm:p-8 relative overflow-hidden bg-[#131615]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#232826] pb-5">
                        <div>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded bg-[#f27b7b]/15 text-[#f27b7b] border border-[#f27b7b]/30">
                                🔔 Tagihan Belum Dibayar
                            </span>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-white mt-2">
                                Tagihan Internet #{{ $unpaidInvoice->invoice_number }}
                            </h2>
                            <p class="text-xs text-[#9aa49f] mt-1">
                                Periode: <strong class="text-[#eef1f0]">{{ $unpaidInvoice->period ?? date('F Y') }}</strong> &bull; Jatuh Tempo: <span class="text-[#f27b7b] font-semibold">{{ $unpaidInvoice->due_date ? $unpaidInvoice->due_date->format('d M Y') : 'Segera' }}</span>
                            </p>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-[#9aa49f] block font-medium">Total Pembayaran</span>
                            <span class="text-2xl sm:text-3xl font-black text-[#00e07e] tracking-tight">
                                Rp {{ number_format($unpaidInvoice->total_amount ?? $unpaidInvoice->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Payment Action Area -->
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
                        <!-- QRIS Code Box -->
                        <div class="flex flex-col items-center justify-center p-5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-center">
                            @if($qrisDataUri)
                                <div class="bg-white p-3 rounded-xl shadow-lg shadow-black/50 mb-3 max-w-[210px] w-full aspect-square flex items-center justify-center">
                                    <img src="{{ $qrisDataUri }}" alt="QRIS NetSpeed" class="w-full h-full object-contain">
                                </div>
                                <div class="flex items-center gap-1.5 text-xs font-bold text-[#eef1f0]">
                                    <span class="w-2 h-2 rounded-full bg-[#00e07e] animate-ping"></span>
                                    <span>Scan QRIS Instan</span>
                                </div>
                                <p class="text-[11px] text-[#9aa49f] mt-1">BCA, Mandiri, BRI, BSI, GoPay, OVO, Dana, ShopeePay</p>
                                <a href="{{ $qrisDataUri }}" download="QRIS-{{ $unpaidInvoice->invoice_number }}.svg" class="mt-2 text-[11px] font-semibold text-[#00e07e] hover:underline">
                                    Unduh Gambar QRIS
                                </a>
                            @else
                                <div class="w-16 h-16 rounded-full bg-[#131615] border border-[#232826] flex items-center justify-center mb-3 text-2xl">
                                    💳
                                </div>
                                <div class="text-xs font-bold text-[#eef1f0]">Transfer Rekening Bank</div>
                                <p class="text-xs text-[#9aa49f] mt-1">Silakan transfer dan unggah bukti pembayaran di formulir samping.</p>
                            @endif
                        </div>

                        <!-- Manual Upload & WhatsApp Action -->
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-[#1a1e1c] border border-[#232826]">
                                <h4 class="text-xs font-bold text-[#eef1f0] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#00e07e]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    Unggah Bukti Transfer
                                </h4>
                                <form method="POST" action="{{ route('customer.invoice.proof', $unpaidInvoice->id) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div>
                                        <input type="file" name="proof_image" accept="image/*" required class="block w-full text-xs text-[#9aa49f] file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#00e07e]/15 file:text-[#00e07e] hover:file:bg-[#00e07e]/25 cursor-pointer">
                                        <p class="text-[10px] text-[#9aa49f] mt-1">Format: JPG, PNG, WEBP (Maksimal 5MB)</p>
                                    </div>
                                    <button type="submit" class="w-full py-2.5 px-3 rounded-lg bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] font-extrabold text-xs shadow-md shadow-[#00e07e]/20 transition flex items-center justify-center gap-2">
                                        <span>Kirim Bukti Pembayaran</span>
                                    </button>
                                </form>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ $waConfirmUrl }}" target="_blank" class="flex-1 py-2 px-3 rounded-lg bg-[#0f3a25] hover:bg-[#0f3a25]/80 text-[#4deda4] border border-[#00e07e]/30 font-bold text-xs text-center transition flex items-center justify-center gap-1.5">
                                    <span>💬 Konfirmasi via WA</span>
                                </a>
                                <a href="{{ route('print.invoice', $unpaidInvoice->id) }}" target="_blank" class="py-2 px-3 rounded-lg bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] font-bold text-xs border border-[#232826] transition" title="Lihat Faktur">
                                    <span>📄 Faktur</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Paid State Alert -->
                <div class="glass-card p-6 sm:p-8 border-[#00e07e]/30 bg-[#00e07e]/5 flex flex-col sm:flex-row items-center gap-5 relative overflow-hidden">
                    <div class="shrink-0">
                        <img src="{{ asset('images/mascot-streaming.webp') }}" alt="Mascot Streaming" class="w-16 sm:w-20 h-auto object-contain drop-shadow-[0_4px_16px_rgba(0,224,126,0.3)]">
                    </div>
                    <div class="space-y-1 text-center sm:text-left flex-1">
                        <h3 class="text-lg font-bold text-white">Semua Tagihan Sudah Lunas!</h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f]">
                            Terima kasih atas kedisiplinan Anda. Layanan internet Anda berjalan optimal tanpa hambatan isolir &mdash; selamat streaming &amp; berselancar lancar!
                        </p>
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg bg-[#00e07e]/20 text-[#00e07e] border border-[#00e07e]/30">
                            Aktif Sampai Bulan Depan
                        </span>
                    </div>
                </div>
            @endif

            <!-- 2. Modem ONT & Wi-Fi Management (TR-069 GenieACS) -->
            <div class="glass-card p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#232826] pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 flex items-center justify-center text-[#00e07e]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" /></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-white">Pengaturan Wi-Fi & Status Modem ONT</h3>
                            <p class="text-xs text-[#9aa49f]">Kelola nama SSID, kata sandi Wi-Fi, dan pantau kualitas sinyal optik fiber</p>
                        </div>
                    </div>

                    @if($genieacsConnected)
                        <form method="POST" action="{{ route('customer.reboot-ont') }}" onsubmit="return confirm('Apakah Anda yakin ingin me-restart modem ONT Anda? Koneksi internet akan terputus selama 1-2 menit.')">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#f27b7b]/15 hover:bg-[#f27b7b]/25 text-[#f27b7b] border border-[#f27b7b]/30 font-bold text-xs flex items-center gap-1.5 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>Restart Modem Mandiri</span>
                            </button>
                        </form>
                    @endif
                </div>

                @if($genieacsConnected)
                    <!-- Optical Power Signal Indicator -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-[#1a1e1c] border border-[#232826] text-xs">
                        <div>
                            <span class="text-[#9aa49f] block text-[11px]">Sinyal Optik (RX)</span>
                            <span class="font-extrabold text-base {{ isset($opticalPower['rx_power']) && (float)$opticalPower['rx_power'] < -27 ? 'text-[#f27b7b]' : 'text-[#00e07e]' }}">
                                {{ $opticalPower['rx_power'] ?? '-21.4 dBm' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[#9aa49f] block text-[11px]">Kualitas Redaman</span>
                            <span class="font-bold text-[#eef1f0]">
                                {{ $opticalPower['signal_quality'] ?? 'Prima / Optimal' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[#9aa49f] block text-[11px]">Suhu Perangkat</span>
                            <span class="font-bold text-[#eef1f0]">
                                {{ $opticalPower['temperature'] ?? '42 °C' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[#9aa49f] block text-[11px]">Status TR-069</span>
                            <span class="inline-flex items-center gap-1 font-bold text-[#00e07e]">
                                <span class="w-2 h-2 rounded-full bg-[#00e07e]"></span>
                                Terhubung
                            </span>
                        </div>
                    </div>

                    <!-- Change Wi-Fi Form -->
                    <form method="POST" action="{{ route('customer.change-wifi') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Nama Wi-Fi (SSID)</label>
                                <input type="text" name="ssid" value="{{ old('ssid', $wifiSettings['ssid'] ?? '') }}" required placeholder="Contoh: NetSpeed-Rumah" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Password Wi-Fi Baru</label>
                                <div class="relative">
                                    <input type="password" id="wifiPassInput" name="password" required minlength="8" placeholder="Minimal 8 karakter" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition pr-10">
                                    <button type="button" onclick="toggleWifiPass()" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9aa49f] hover:text-white">
                                        👁️
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-[#9aa49f]">
                                <input type="checkbox" name="reboot_now" value="1" checked class="rounded bg-[#1a1e1c] border-[#232826] text-[#00e07e] focus:ring-0">
                                <span>Restart modem otomatis setelah simpan agar perubahan langsung aktif</span>
                            </label>

                            <button type="submit" class="py-2.5 px-5 rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] font-extrabold text-xs shadow-lg shadow-[#00e07e]/20 transition flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <span>Simpan Pengaturan Wi-Fi</span>
                            </button>
                        </div>
                    </form>
                @else
                    <!-- Fallback if not connected to GenieACS -->
                    <div class="p-5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-xs text-[#9aa49f] space-y-2">
                        <div class="flex items-center gap-2 font-bold text-[#eef1f0]">
                            <span>ℹ️</span>
                            <span>Informasi Modem ONT</span>
                        </div>
                        <p>
                            Modem ONT Anda saat ini dikonfigurasi melalui router manajemen atau teknisi lapangan. Untuk perubahan kata sandi Wi-Fi atau konfigurasi lanjutan, silakan hubungi Customer Service kami via WhatsApp.
                        </p>
                        @if($customer->ont_mac || $customer->ont_sn)
                            <div class="pt-2 font-mono text-[11px] text-[#9aa49f]">
                                GPON SN: {{ $customer->ont_sn ?? '-' }} &bull; MAC: {{ $customer->ont_mac ?? '-' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- 3. Paid Invoices History -->
            <div class="glass-card p-6 sm:p-8 space-y-4">
                <div class="flex items-center justify-between border-b border-[#232826] pb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-white">Riwayat Pembayaran Terakhir</h3>
                        <p class="text-xs text-[#9aa49f]">Daftar tagihan yang telah berhasil diverifikasi dan lunas</p>
                    </div>
                </div>

                @if($paidInvoices->isEmpty())
                    <div class="py-8 text-center text-xs text-[#9aa49f]">
                        Belum ada riwayat pembayaran faktur sebelumnya.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-[#eef1f0]">
                            <thead class="text-[11px] uppercase tracking-wider text-[#9aa49f] border-b border-[#232826]">
                                <tr>
                                    <th class="py-3 px-3">No. Faktur</th>
                                    <th class="py-3 px-3">Periode</th>
                                    <th class="py-3 px-3">Nominal</th>
                                    <th class="py-3 px-3">Tanggal Bayar</th>
                                    <th class="py-3 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#232826] font-medium">
                                @foreach($paidInvoices as $inv)
                                    <tr class="hover:bg-[#1a1e1c] transition">
                                        <td class="py-3 px-3 font-mono font-bold text-white">
                                             #{{ $inv->invoice_number }}
                                        </td>
                                        <td class="py-3 px-3 text-[#9aa49f]">
                                            {{ $inv->period ?? '-' }}
                                        </td>
                                        <td class="py-3 px-3 font-bold text-[#00e07e]">
                                            Rp {{ number_format($inv->total_amount ?? $inv->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-[#9aa49f]">
                                            {{ $inv->paid_at ? $inv->paid_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="py-3 px-3 text-right space-x-1">
                                            <a href="{{ route('print.invoice', $inv->id) }}" target="_blank" class="px-2 py-1 rounded bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] font-semibold text-[11px] border border-[#232826] transition">
                                                Cetak PDF
                                            </a>
                                            <a href="{{ route('print.thermal', $inv->id) }}" target="_blank" class="px-2 py-1 rounded bg-[#1a1e1c] hover:bg-[#232826] text-[#9aa49f] font-semibold text-[11px] border border-[#232826] transition">
                                                Struk
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        <!-- RIGHT COLUMN: FUP USAGE & SUPPORT TICKETS -->
        <div class="space-y-8">

            <!-- FUP / Data Usage Card -->
            <div class="glass-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-[#232826] pb-3">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>📊</span>
                        <span>Pemakaian Kuota & FUP</span>
                    </h3>
                    <span class="text-[11px] font-mono text-[#9aa49f]">{{ date('M Y') }}</span>
                </div>

                @php
                    $package = $customer->package;
                    $fupLimit = $customer->custom_fup_quota_gb ?? ($package?->fup_limit_gb ?? 0);
                    $usedBytes = $fupUsage?->total_bytes ?? 0;
                    $usedGb = round($usedBytes / (1024 * 1024 * 1024), 2);
                    $percentage = ($fupLimit > 0) ? min(100, round(($usedGb / $fupLimit) * 100)) : 0;
                @endphp

                <div class="space-y-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-2xl font-black text-white">
                            {{ $fupUsage?->usage_formatted ?? '0.00 GB' }}
                        </span>
                        <span class="text-xs text-[#9aa49f] font-medium">
                            @if($fupLimit > 0)
                                dari batas {{ $fupLimit }} GB
                            @else
                                Unlimited / Tanpa Batas
                            @endif
                        </span>
                    </div>

                    @if($fupLimit > 0)
                        <!-- Progress bar -->
                        <div class="w-full bg-[#1a1e1c] rounded-full h-2.5 overflow-hidden border border-[#232826]">
                            <div class="h-full rounded-full transition-all duration-500 {{ $percentage >= 90 ? 'bg-[#f27b7b]' : ($percentage >= 70 ? 'bg-[#f2c56b]' : 'bg-[#00e07e]') }}" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-[#9aa49f]">
                            <span>Status: <strong class="text-[#eef1f0]">{{ ucfirst($customer->fup_status ?? 'Normal') }}</strong></span>
                            <span>{{ $percentage }}% terpakai</span>
                        </div>
                    @else
                        <div class="p-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[11px] text-[#9aa49f]">
                            🚀 Paket Anda memiliki akses internet <strong class="text-[#00e07e]">Unlimited FUP</strong> tanpa penurunan kecepatan.
                        </div>
                    @endif
                </div>

                <div class="pt-2 text-[10px] text-[#9aa49f] leading-relaxed border-t border-[#232826]">
                    *Akumulasi penggunaan kuota di-reset otomatis setiap awal bulan kalender (tanggal 1).
                </div>
            </div>

            <!-- Trouble Tickets Card -->
            <div class="glass-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-[#232826] pb-3">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>🎫</span>
                        <span>Tiket Kendala Jaringan</span>
                    </h3>
                    <a href="{{ route('customer.tickets') }}" class="text-[11px] font-bold text-[#00e07e] hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>

                @if($recentTickets->isEmpty())
                    <div class="py-6 text-center text-xs text-[#9aa49f] space-y-3">
                        <p>Tidak ada laporan gangguan yang aktif.</p>
                        <a href="{{ route('customer.tickets') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#00e07e]/15 text-[#00e07e] hover:bg-[#00e07e]/25 border border-[#00e07e]/30 text-xs font-bold transition">
                            <span>+ Buat Tiket Bantuan</span>
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentTickets as $ticket)
                            <div class="p-3 rounded-xl bg-[#1a1e1c] border border-[#232826] space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-[10px] font-bold text-[#9aa49f]">#{{ $ticket->ticket_number }}</span>
                                    @if($ticket->status === 'open')
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#00e07e]/15 text-[#00e07e] border border-[#00e07e]/30">Terbuka</span>
                                    @elseif($ticket->status === 'in_progress')
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#4deda4]/15 text-[#4deda4] border border-[#4deda4]/30">Ditangani</span>
                                    @else
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#131615] text-[#9aa49f] border border-[#232826]">Selesai</span>
                                    @endif
                                </div>
                                <h4 class="text-xs font-bold text-[#eef1f0] line-clamp-1">{{ $ticket->subject }}</h4>
                                <p class="text-[11px] text-[#9aa49f] line-clamp-1">{{ $ticket->description }}</p>
                                <span class="text-[10px] text-[#9aa49f] block">{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach

                        <div class="pt-2">
                            <a href="{{ route('customer.tickets') }}" class="block w-full py-2 px-3 rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] text-center text-xs font-bold border border-[#232826] transition">
                                + Buat Laporan Kendala Baru
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Customer Service Quick Contacts -->
            <div class="glass-card p-6 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#9aa49f]">Bantuan & Dukungan Teknis</h3>
                <p class="text-xs text-[#9aa49f] leading-relaxed">
                    Mengalami kendala koneksi atau lampu router LOS berkedip merah? Hubungi tim NOC kami secara langsung:
                </p>
                <div class="pt-1 space-y-2">
                    <a href="{{ $waConfirmUrl }}" target="_blank" class="w-full py-2.5 px-4 rounded-xl bg-[#0f3a25] hover:bg-[#0f3a25]/80 text-[#4deda4] border border-[#00e07e]/30 text-xs font-bold flex items-center justify-center gap-2 transition">
                        <span>💬 WhatsApp Support 24/7</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function toggleWifiPass() {
        const input = document.getElementById('wifiPassInput');
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>
@endpush
