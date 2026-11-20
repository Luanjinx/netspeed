@extends('layouts.technician')

@section('title', 'Monitoring Sistem Ringkas')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-64 h-64 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                        NOC MONITORING
                    </span>
                    <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                        Status Real-time
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Monitoring Sistem & NOC Ringkas
                </h1>
                <p class="text-xs text-muted mt-1 max-w-xl">
                    Informasi kondisi perangkat MikroTik Core, kapasitas port ODP di lapangan, dan ringkasan status konektivitas pelanggan.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('tech.map') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-ink font-bold text-xs shadow-md shadow-accent/20 transition">
                    <span class="material-symbols-rounded text-base">map</span>
                    <span>Buka Peta Jaringan Lapangan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 1. Router MikroTik Core Status -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-lg">router</span>
                <span>Router MikroTik Core Gateway</span>
            </h2>

            @if($router)
                @if(isset($routerStats['success']) && $routerStats['success'])
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-accent/15 text-accent border border-accent/30 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                        Terhubung (RouterOS)
                    </span>
                @else
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-danger/15 text-danger border border-danger/30 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-danger"></span>
                        Offline / Timeout
                    </span>
                @endif
            @else
                <span class="text-[11px] text-muted">Belum ada router aktif</span>
            @endif
        </div>

        @if($router)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Router Identity</span>
                    <strong class="text-white text-sm block mt-0.5 truncate">{{ $router->name }}</strong>
                    <span class="text-[11px] font-mono text-muted">{{ $router->host }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Beban CPU</span>
                    <strong class="text-white text-sm block mt-0.5 font-mono">
                        {{ $routerStats['cpu_load'] ?? '0' }}%
                    </strong>
                    <span class="text-[11px] text-muted">{{ $routerStats['cpu_count'] ?? 1 }} Core CPU</span>
                </div>

                <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">RAM Bebas / Total</span>
                    <strong class="text-white text-sm block mt-0.5 font-mono">
                        {{ isset($routerStats['free_memory']) ? round($routerStats['free_memory'] / 1024 / 1024, 1) : 0 }} MB
                    </strong>
                    <span class="text-[11px] text-muted">Total: {{ isset($routerStats['total_memory']) ? round($routerStats['total_memory'] / 1024 / 1024, 0) : 0 }} MB</span>
                </div>

                <div class="p-3.5 rounded-xl bg-surface2 border border-border">
                    <span class="text-muted text-[10px] block">Uptime Router</span>
                    <strong class="text-accent text-sm block mt-0.5 font-mono">
                        {{ $routerStats['uptime'] ?? '-' }}
                    </strong>
                    <span class="text-[11px] text-muted">v{{ $routerStats['version'] ?? 'RouterOS' }}</span>
                </div>
            </div>
        @else
            <div class="p-4 rounded-xl bg-surface2 text-muted text-xs text-center">
                Belum ada router MikroTik yang ditandai aktif di sistem.
            </div>
        @endif
    </div>

    <!-- 2. Ringkasan Status Pelanggan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="rounded-xl bg-surface border border-border p-4 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-muted block">Pelanggan Aktif</span>
                <span class="text-2xl font-extrabold text-accent font-mono">{{ $activeCustomers }}</span>
                <p class="text-[10px] text-muted mt-0.5">Layanan internet menyala</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-accent/10 border border-accent/20 text-accent flex items-center justify-center">
                <span class="material-symbols-rounded text-xl">wifi</span>
            </div>
        </div>

        <div class="rounded-xl bg-surface border border-border p-4 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-muted block">Pelanggan Terisolir</span>
                <span class="text-2xl font-extrabold text-danger font-mono">{{ $isolatedCustomers }}</span>
                <p class="text-[10px] text-muted mt-0.5">Jatuh tempo / blokir</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-danger/10 border border-danger/20 text-danger flex items-center justify-center">
                <span class="material-symbols-rounded text-xl">wifi_off</span>
            </div>
        </div>

        <div class="rounded-xl bg-surface border border-border p-4 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-muted block">Menunggu Aktivasi</span>
                <span class="text-2xl font-extrabold text-warn font-mono">{{ $pendingCustomers }}</span>
                <p class="text-[10px] text-muted mt-0.5">Menunggu penarikan kabel</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-warn/10 border border-warn/20 text-warn flex items-center justify-center">
                <span class="material-symbols-rounded text-xl">schedule</span>
            </div>
        </div>
    </div>

    <!-- 3. Kapasitas & Utilisasi Kotak ODP -->
    <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-accent text-lg">hub</span>
                    <span>Kapasitas & Utilisasi Titik ODP Fiber</span>
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Total {{ $totalOdps }} Titik ODP &bull; {{ $totalOdpUsed }} dari {{ $totalOdpCapacity }} Port Terpakai ({{ $odpUtilization }}% Terisi)
                </p>
            </div>

            <!-- Progress bar -->
            <div class="w-full sm:w-48 space-y-1">
                <div class="h-2.5 rounded-full bg-surface2 overflow-hidden border border-border">
                    <div class="h-full bg-accent rounded-full transition-all duration-500" style="width: {{ min(100, $odpUtilization) }}%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-muted font-mono">
                    <span>Sisa: {{ max(0, $totalOdpCapacity - $totalOdpUsed) }} Port</span>
                    <span class="text-accent font-bold">{{ $odpUtilization }}%</span>
                </div>
            </div>
        </div>

        <!-- ODP Grid Breakdown -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-2">
            @forelse($odps as $odp)
                @php
                    $total = $odp->total_ports ?: 8;
                    $used = $odp->customers_count ?: 0;
                    $percent = $total > 0 ? round(($used / $total) * 100) : 0;
                    $isFull = $used >= $total;
                @endphp

                <div class="p-3.5 rounded-xl bg-surface2 border {{ $isFull ? 'border-danger/40' : 'border-border' }} space-y-2">
                    <div class="flex items-center justify-between">
                        <strong class="text-xs text-white">{{ $odp->name }}</strong>
                        @if($isFull)
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-danger/20 text-danger border border-danger/30">PENUH</span>
                        @else
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-accent/15 text-accent">Tersedia {{ $total - $used }} Port</span>
                        @endif
                    </div>

                    <div class="space-y-1">
                        <div class="h-1.5 rounded-full bg-bg overflow-hidden">
                            <div class="h-full {{ $isFull ? 'bg-danger' : 'bg-accent' }}" style="width: {{ min(100, $percent) }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-muted">
                            <span>{{ $used }}/{{ $total }} Port</span>
                            <span>{{ $percent }}%</span>
                        </div>
                    </div>

                    <p class="text-[10px] text-muted truncate">
                        <span class="material-symbols-rounded text-[11px] align-middle">place</span>
                        {{ $odp->address ?? ($odp->code ?? 'Lokasi tidak ada catatan') }}
                    </p>
                </div>
            @empty
                <div class="col-span-full text-center py-6 text-xs text-muted">
                    Belum ada data ODP yang tercatat di sistem.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
