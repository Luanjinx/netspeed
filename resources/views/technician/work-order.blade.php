@extends('layouts.technician')

@section('title', 'Detail Tugas - ' . $workOrder->order_number)

@section('content')
@php
    $customer = $workOrder->customer;
    $isPsb = $workOrder->type === 'installation';
    $hasCoords = $customer && $customer->latitude && $customer->longitude;
    $mapsUrl = $hasCoords 
        ? "https://www.google.com/maps/dir/?api=1&destination={$customer->latitude},{$customer->longitude}"
        : ($customer ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($customer->address ?? '') : '#');
    $waPhone = $customer ? preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $customer->phone)) : '';
@endphp

<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Top Navigation & Heading -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('technician.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface border border-border text-xs font-semibold text-muted hover:text-white hover:border-accent transition">
            <span class="material-symbols-rounded text-sm">arrow_back</span>
            <span>Kembali ke Dashboard</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-md {{ $isPsb ? 'bg-accent/15 text-accent border border-accent/30' : 'bg-danger/15 text-danger border border-danger/30' }}">
                {{ $isPsb ? 'PASANG BARU (PSB)' : 'PERBAIKAN' }}
            </span>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $workOrder->status === 'completed' ? 'bg-accent/15 text-accent' : ($workOrder->status === 'in_progress' ? 'bg-warn/15 text-warn' : 'bg-surface2 text-muted') }} border border-border">
                {{ ucfirst($workOrder->status) }}
            </span>
        </div>
    </div>

    <!-- SPK Title Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="text-[11px] font-mono text-muted">{{ $workOrder->order_number }}</span>
                <h1 class="text-xl font-extrabold text-white tracking-tight mt-0.5">
                    {{ $workOrder->title }}
                </h1>
                @if($workOrder->scheduled_date)
                    <p class="text-xs text-muted mt-1 flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-xs text-accent">event</span>
                        <span>Jadwal Pengerjaan: <strong class="text-white">{{ $workOrder->scheduled_date->format('l, d F Y') }}</strong></span>
                    </p>
                @endif
            </div>

            @if($workOrder->status === 'pending')
                <form action="{{ route('technician.work-order.start', $workOrder->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-warn hover:bg-warn/90 text-ink font-bold text-xs shadow-md transition">
                        <span class="material-symbols-rounded text-base">play_arrow</span>
                        <span>Mulai Kerjakan Tugas</span>
                    </button>
                </form>
            @endif
        </div>

        @if($workOrder->description)
            <div class="mt-4 p-3 rounded-xl bg-surface2/80 border border-border text-xs text-muted leading-relaxed">
                <span class="font-bold text-white block mb-0.5">Instruksi Tim Admin:</span>
                {{ $workOrder->description }}
            </div>
        @endif
    </div>

    <!-- Customer Details Card -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <h2 class="text-sm font-bold text-white flex items-center gap-2">
            <span class="material-symbols-rounded text-accent text-lg">person_pin</span>
            <span>Data Pelanggan & Lokasi Pemasangan</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="space-y-1">
                <span class="text-muted block text-[11px]">Nama Pelanggan</span>
                <strong class="text-white text-sm block">{{ $customer->name ?? '-' }}</strong>
                <span class="font-mono text-muted text-[11px]">ID: {{ $customer->customer_number ?? '-' }}</span>
            </div>

            <div class="space-y-1">
                <span class="text-muted block text-[11px]">Nomor WhatsApp</span>
                <div class="flex items-center gap-2">
                    <strong class="text-white text-sm">{{ $customer->phone ?? '-' }}</strong>
                    @if($waPhone)
                        <a href="https://wa.me/{{ $waPhone }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-accent/15 text-accent border border-accent/30 font-semibold text-[11px] hover:bg-accent/25">
                            <span class="material-symbols-rounded text-xs">chat</span> Chat
                        </a>
                    @endif
                </div>
            </div>

            <div class="sm:col-span-2 space-y-1 pt-2 border-t border-border">
                <span class="text-muted block text-[11px]">Alamat Lengkap</span>
                <p class="text-white text-xs leading-relaxed">{{ $customer->address ?? 'Alamat belum diisi' }}</p>

                <div class="pt-2">
                    <a href="{{ $mapsUrl }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface2 hover:bg-border text-accent font-bold text-xs border border-border transition">
                        <span class="material-symbols-rounded text-base">directions</span>
                        <span>Buka Rute Navigasi Google Maps</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Infrastructure & Credentials -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <h2 class="text-sm font-bold text-white flex items-center gap-2">
            <span class="material-symbols-rounded text-accent text-lg">settings_ethernet</span>
            <span>Konfigurasi Jaringan & Kredensial ONT</span>
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 text-xs">
            <div class="p-3 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px]">Titik Tiang ODP</span>
                <strong class="text-white block mt-0.5">{{ $customer->odp?->name ?? 'Belum ditentukan' }}</strong>
                @if($customer->odp_port)
                    <span class="text-[11px] text-accent font-semibold">Port {{ $customer->odp_port }}</span>
                @endif
            </div>

            <div class="p-3 rounded-xl bg-surface2 border border-border">
                <span class="text-muted block text-[10px]">Paket Layanan</span>
                <strong class="text-white block mt-0.5">{{ $customer->package?->name ?? 'Kustom' }}</strong>
                <span class="text-[11px] text-muted">{{ $customer->package?->speed_rate ?? '-' }}</span>
            </div>

            <div class="p-3 rounded-xl bg-surface2 border border-border col-span-2 sm:col-span-1">
                <span class="text-muted block text-[10px]">Router Gateway</span>
                <strong class="text-white block mt-0.5">{{ $customer->router?->name ?? 'MikroTik Core' }}</strong>
                <span class="text-[11px] text-muted">{{ strtoupper($customer->connection_type ?? 'PPPoE') }}</span>
            </div>

            <!-- PPPoE Credentials -->
            @if($customer->connection_type === 'pppoe')
                <div class="p-3 rounded-xl bg-surface2 border border-accent/30 col-span-2 sm:col-span-3 space-y-2">
                    <span class="text-accent text-[11px] font-bold block flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-sm">key</span>
                        <span>Kredensial Login PPPoE (Input di Modem ONT Pelanggan)</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <span class="text-muted text-[10px]">Username PPPoE:</span>
                            <div class="font-mono text-white text-xs font-bold bg-bg p-2 rounded border border-border select-all">
                                {{ $customer->pppoe_username ?? 'user belum dibuat' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-[10px]">Password PPPoE:</span>
                            <div class="font-mono text-white text-xs font-bold bg-bg p-2 rounded border border-border select-all">
                                {{ $customer->pppoe_password ?? 'password belum dibuat' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Live Connection Check Widget -->
        <div class="p-4 rounded-xl bg-surface2 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-white block">Uji Koneksi MikroTik Live</span>
                <p id="live-check-text" class="text-xs text-muted mt-0.5">
                    Klik tombol untuk memastikan modem ONT pelanggan sudah berhasil *dial-in* ke MikroTik.
                </p>
            </div>

            <button type="button" id="btn-check-live" onclick="checkLiveConnection()" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface border border-border hover:border-accent text-accent text-xs font-bold transition">
                <span class="material-symbols-rounded text-sm" id="icon-check-spin">sync</span>
                <span id="label-check-live">Cek Status Online</span>
            </button>
        </div>
    </div>

    <!-- Akses Ringkas ke Perangkat GenieACS TR-069 -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-lg">settings_remote</span>
                <span>Akses Ringkas ONT (GenieACS TR-069)</span>
            </h2>

            @if($ontData && isset($ontData['success']) && $ontData['success'])
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/30 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                    ONT Terkoneksi TR-069
                </span>
            @elseif($customer && $customer->genieacs_device_id)
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-danger/15 text-danger border border-danger/30">
                    Offline / Belum Sync
                </span>
            @else
                <span class="text-[11px] text-muted px-2 py-0.5 rounded bg-surface2 border border-border">
                    Belum Terdaftar TR-069
                </span>
            @endif
        </div>

        @if($ontData && isset($ontData['success']) && $ontData['success'])
            @php
                $devInfo = $ontData['device_info'] ?? [];
                $optInfo = $ontData['optical_info'] ?? [];
                $wifiInfo = $ontData['wifi_info'] ?? [];
                $wanInfo = $ontData['wan_info'] ?? [];
            @endphp

            <!-- ONT Info Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Model & Brand</span>
                    <strong class="text-white block mt-0.5 truncate">{{ $devInfo['model'] ?? ($devInfo['manufacturer'] ?? 'XPON/GPON ONT') }}</strong>
                    <span class="text-[10px] text-muted font-mono truncate block">{{ $devInfo['serial_number'] ?? $customer->genieacs_device_id }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Redaman RX (ONT)</span>
                    <strong class="text-accent text-sm block mt-0.5 font-mono">
                        {{ $optInfo['rx_power'] ?? '-' }}
                    </strong>
                    <span class="text-[10px] text-muted">TX: {{ $optInfo['tx_power'] ?? '-' }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Suhu & Tegangan</span>
                    <strong class="text-white block mt-0.5 font-mono">{{ $optInfo['temperature'] ?? 'Normal' }}</strong>
                    <span class="text-[10px] text-muted">Volt: {{ $optInfo['voltage'] ?? '-' }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Uptime ONT</span>
                    <strong class="text-white block mt-0.5 font-mono">{{ $devInfo['uptime'] ?? '-' }}</strong>
                    <span class="text-[10px] text-muted">WAN: {{ $wanInfo['status'] ?? 'Connected' }}</span>
                </div>
            </div>

            <!-- Wi-Fi SSID & Password Management Form -->
            <div class="p-4 rounded-xl bg-surface2 border border-border space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-white flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-sm text-accent">wifi</span>
                        <span>Konfigurasi Wi-Fi Pelanggan (TR-069)</span>
                    </span>
                    <span class="text-[10px] text-muted">Push langsung ke modem tanpa perlu login web GUI ONT</span>
                </div>

                <form action="{{ route('technician.ont.wifi') }}" method="POST" class="space-y-3">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-muted text-[11px] mb-1">Nama Wi-Fi (SSID)</label>
                            <input type="text" name="ssid" value="{{ old('ssid', $wifiInfo['ssid'] ?? '') }}" required placeholder="cth: NETSPEED-BUDI" class="w-full bg-bg border border-border rounded-xl px-3 py-2 text-xs text-white placeholder-muted focus:outline-none focus:border-accent font-semibold transition">
                        </div>

                        <div>
                            <label class="block text-muted text-[11px] mb-1">Password Wi-Fi WPA2 (Min. 8 karakter)</label>
                            <input type="text" name="password" value="{{ old('password', ($wifiInfo['password'] ?? '') !== 'N/A' ? ($wifiInfo['password'] ?? '') : '') }}" placeholder="Kosongkan jika tidak diubah" minlength="8" class="w-full bg-bg border border-border rounded-xl px-3 py-2 text-xs text-white placeholder-muted focus:outline-none focus:border-accent font-mono transition">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1">
                        <label class="flex items-center gap-2 text-xs text-muted cursor-pointer">
                            <input type="checkbox" name="reboot" value="1" class="rounded border-border text-accent focus:ring-accent bg-bg">
                            <span>Reboot ONT otomatis setelah konfigurasi diterapkan</span>
                        </label>

                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-accent hover:bg-accent-hover text-ink font-bold text-xs transition shadow-sm">
                            <span class="material-symbols-rounded text-sm">send</span>
                            <span>Terapkan Pengaturan Wi-Fi</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reboot Button -->
            <div class="flex items-center justify-between p-3.5 rounded-xl bg-surface2/60 border border-border text-xs">
                <div>
                    <span class="font-bold text-white block">Reboot Perangkat ONT</span>
                    <span class="text-muted text-[11px]">Restart modem ONT dari jauh jika ada kendala koneksi atau lambat.</span>
                </div>

                <form action="{{ route('technician.ont.reboot') }}" method="POST">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <button type="submit" onclick="return confirm('Kirim perintah REBOOT ke modem ONT pelanggan sekarang?')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-danger/15 border border-danger/30 text-danger hover:bg-danger hover:text-white font-bold text-xs transition">
                        <span class="material-symbols-rounded text-sm">restart_alt</span>
                        <span>Reboot ONT</span>
                    </button>
                </form>
            </div>
        @else
            <div class="p-4 rounded-xl bg-surface2 border border-border text-xs text-muted space-y-2">
                <div class="flex items-center gap-2 text-warn font-semibold">
                    <span class="material-symbols-rounded text-base">info</span>
                    <span>Modem ONT Belum Terhubung ke GenieACS TR-069</span>
                </div>
                <p class="text-[11px] leading-relaxed">
                    Pelanggan ini belum memiliki Device ID GenieACS yang valid atau modem belum mengirimkan ACS Inform. Pengaturan SSID & reboot via TR-069 dapat digunakan setelah ONT terhubung ke server TR-069 ISP.
                </p>
            </div>
        @endif
    </div>

    <!-- Complete Task Form -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <h2 class="text-sm font-bold text-white flex items-center gap-2">
            <span class="material-symbols-rounded text-accent text-lg">verified</span>
            <span>Laporan Hasil Lapangan & Penyelesaian</span>
        </h2>

        @if($workOrder->status === 'completed')
            <div class="p-4 rounded-xl bg-accent/10 border border-accent/30 space-y-2 text-xs">
                <div class="flex items-center gap-2 text-accent font-bold">
                    <span class="material-symbols-rounded">check_circle</span>
                    <span>Tugas Ini Telah Diselesaikan pada {{ $workOrder->completed_at?->format('d M Y, H:i') }}</span>
                </div>
                @if($workOrder->optical_power_dbm)
                    <p class="text-white">Redaman Optik (OPM): <strong class="text-accent">{{ $workOrder->optical_power_dbm }} dBm</strong></p>
                @endif
                @if($workOrder->completion_notes)
                    <p class="text-muted">Catatan: {{ $workOrder->completion_notes }}</p>
                @endif
            </div>
        @else
            <form action="{{ route('technician.work-order.complete', $workOrder->id) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-white mb-1.5">
                            Hasil Ukur Redaman Optik (OPM) <span class="text-accent">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="optical_power_dbm" value="{{ old('optical_power_dbm', $workOrder->optical_power_dbm) }}" placeholder="cth: -19.5" required class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition font-mono">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-muted font-bold">dBm</span>
                        </div>
                        <p class="text-[10px] text-muted mt-1">Standar redaman ISP: -15.00 dBm s/d -24.00 dBm</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-white mb-1.5">
                            Catatan Teknisi / Tindakan Lapangan
                        </label>
                        <input type="text" name="completion_notes" value="{{ old('completion_notes', $workOrder->completion_notes) }}" placeholder="cth: Kabel dropcore 65m, splice rapi di roset" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan tugas ini? Pelanggan akan otomatis diaktifkan dan sistem mengirimkan notifikasi.')" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-sm shadow-lg shadow-accent/20 transition">
                        <span class="material-symbols-rounded text-lg">check_circle</span>
                        <span>Selesaikan Tugas & Aktifkan Layanan</span>
                    </button>
                </div>
            </form>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function checkLiveConnection() {
        const btn = document.getElementById('btn-check-live');
        const icon = document.getElementById('icon-check-spin');
        const text = document.getElementById('live-check-text');
        const label = document.getElementById('label-check-live');

        icon.classList.add('animate-spin');
        label.innerText = 'Memeriksa...';

        fetch("{{ route('technician.check-status', $customer->id ?? 0) }}")
            .then(res => res.json())
            .then(data => {
                icon.classList.remove('animate-spin');
                label.innerText = 'Cek Ulang';

                if (data.online) {
                    text.innerHTML = `<span class="text-accent font-bold">✔ ONLINE DI MIKROTIK:</span> IP: <strong>${data.ip}</strong> &bull; Uptime: <strong>${data.uptime}</strong>`;
                } else {
                    text.innerHTML = `<span class="text-danger font-bold">✖ BELUM ONLINE:</span> ${data.message}`;
                }
            })
            .catch(err => {
                icon.classList.remove('animate-spin');
                label.innerText = 'Coba Lagi';
                text.innerHTML = `<span class="text-danger">Gagal memeriksa: ${err.message}</span>`;
            });
    }
</script>
@endpush
@endsection

