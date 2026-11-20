@extends('layouts.technician')

@section('title', 'Dashboard Tugas Teknisi')

@section('content')
<div class="space-y-6">

    <!-- Top Profile & Quick Status Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-52 h-52 bg-accent/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="hidden sm:block shrink-0">
                    <img src="{{ asset('images/mascot-technician.webp') }}" alt="Mascot Teknisi" class="w-16 md:w-20 h-auto object-contain drop-shadow-[0_8px_20px_rgba(0,224,126,0.25)] hover:scale-105 transition-transform duration-300">
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                            {{ $technician->technician_code ?? 'TECH' }}
                        </span>
                        @if($technician->work_area)
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                                Zona: {{ $technician->work_area }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                        Semangat Bekerja, <span class="text-accent">{{ $technician->name }}</span>!
                    </h1>
                    <p class="text-xs text-muted mt-0.5">
                        Pastikan redaman OPM memenuhi standar (-15 s/d -24 dBm) sebelum menyelesaikan tugas.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('technician.create-customer') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-ink font-bold text-xs shadow-md shadow-accent/20 transition">
                    <span class="material-symbols-rounded text-base">person_add</span>
                    <span>+ Pasang Baru</span>
                </a>

                <a href="{{ route('technician.pool-tickets') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-surface2 hover:bg-border text-white border border-border text-xs font-semibold transition relative">
                    <span class="material-symbols-rounded text-base text-warn">inbox</span>
                    <span>Pool Tiket</span>
                    @if($poolTicketCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full bg-warn text-ink text-[10px] font-extrabold">{{ $poolTicketCount }}</span>
                    @endif
                </a>

                <a href="{{ route('tech.map') }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-surface2 hover:bg-border text-white border border-border text-xs font-semibold transition">
                    <span class="material-symbols-rounded text-base text-accent">near_me</span>
                    <span>Peta ODP</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Banner: Open Pool Tickets -->
    @if($poolTicketCount > 0)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-warn/10 border border-warn/30 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-warn/20 text-warn flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-lg">report_problem</span>
                </div>
                <div>
                    <span class="font-bold text-white block">Terdapat {{ $poolTicketCount }} Tiket Gangguan Terbuka di Pool Tiket!</span>
                    <span class="text-muted text-[11px]">Tiket keluhan pelanggan belum ditugaskan ke teknisi manapun. Anda dapat mengklaim tiket ini secara mandiri.</span>
                </div>
            </div>
            <a href="{{ route('technician.pool-tickets') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-warn hover:bg-warn/90 text-ink font-bold text-xs transition shrink-0">
                <span>Lihat & Klaim Tiket</span>
                <span class="material-symbols-rounded text-sm">arrow_forward</span>
            </a>
        </div>
    @endif

    <!-- Quick Stats Metric Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="rounded-xl bg-surface border border-border p-4">
            <div class="flex items-center justify-between text-muted mb-2">
                <span class="text-xs font-semibold">Tugas Aktif</span>
                <span class="material-symbols-rounded text-lg text-warn">pending_actions</span>
            </div>
            <div class="text-2xl font-extrabold text-white font-mono">{{ $activeOrders->count() }}</div>
            <p class="text-[10px] text-muted mt-1">Perlu dikerjakan</p>
        </div>

        <div class="rounded-xl bg-surface border border-border p-4">
            <div class="flex items-center justify-between text-muted mb-2">
                <span class="text-xs font-semibold">Selesai Hari Ini</span>
                <span class="material-symbols-rounded text-lg text-accent">task_alt</span>
            </div>
            <div class="text-2xl font-extrabold text-accent font-mono">{{ $completedToday }}</div>
            <p class="text-[10px] text-muted mt-1">Selesai diverifikasi</p>
        </div>

        <div class="rounded-xl bg-surface border border-border p-4">
            <div class="flex items-center justify-between text-muted mb-2">
                <span class="text-xs font-semibold">Pasang Baru (PSB)</span>
                <span class="material-symbols-rounded text-lg text-accent">add_home</span>
            </div>
            <div class="text-2xl font-extrabold text-white font-mono">{{ $psbCount }}</div>
            <p class="text-[10px] text-muted mt-1">Instalasi aktif</p>
        </div>

        <div class="rounded-xl bg-surface border border-border p-4">
            <div class="flex items-center justify-between text-muted mb-2">
                <span class="text-xs font-semibold">Tiket Gangguan</span>
                <span class="material-symbols-rounded text-lg text-danger">report_problem</span>
            </div>
            <div class="text-2xl font-extrabold text-white font-mono">{{ $repairCount }}</div>
            <p class="text-[10px] text-muted mt-1">Perbaikan aktif</p>
        </div>
    </div>

    <!-- Active Tasks (Work Orders) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-lg">format_list_bulleted</span>
                <span>Daftar Tugas Lapangan Aktif</span>
            </h2>
            <span class="text-xs text-muted">{{ $activeOrders->count() }} Tugas</span>
        </div>

        @if($activeOrders->isEmpty())
            <div class="rounded-2xl bg-surface border border-border p-8 text-center">
                <div class="w-12 h-12 rounded-full bg-accent/10 border border-accent/20 text-accent flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-rounded text-2xl">done_all</span>
                </div>
                <h3 class="text-sm font-bold text-white">Tidak Ada Tugas Berjalan</h3>
                <p class="text-xs text-muted max-w-sm mx-auto mt-1">
                    Semua penugasan instalasi atau perbaikan telah diselesaikan. Tunggu penugasan baru dari tim admin.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($activeOrders as $order)
                    @php
                        $isPsb = $order->type === 'installation';
                        $customer = $order->customer;
                        $hasCoords = $customer && $customer->latitude && $customer->longitude;
                        $mapsUrl = $hasCoords 
                            ? "https://www.google.com/maps/dir/?api=1&destination={$customer->latitude},{$customer->longitude}"
                            : ($customer ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($customer->address ?? '') : '#');
                        $waPhone = $customer ? preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $customer->phone)) : '';
                    @endphp

                    <div class="rounded-2xl bg-surface border border-border hover:border-accent/40 transition p-5 flex flex-col justify-between gap-4 relative overflow-hidden shadow-md">
                        @if($isPsb)
                            <div class="absolute top-0 left-0 bottom-0 w-1.5 bg-accent"></div>
                        @else
                            <div class="absolute top-0 left-0 bottom-0 w-1.5 bg-danger"></div>
                        @endif

                        <div class="space-y-3 pl-2">
                            <!-- Header Bar -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded {{ $isPsb ? 'bg-accent/15 text-accent border border-accent/30' : 'bg-danger/15 text-danger border border-danger/30' }}">
                                        {{ $isPsb ? 'PASANG BARU (PSB)' : 'PERBAIKAN GANGGUAN' }}
                                    </span>
                                    <span class="text-[11px] font-mono text-muted">{{ $order->order_number }}</span>
                                </div>

                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $order->status === 'in_progress' ? 'bg-warn/15 text-warn border border-warn/30' : 'bg-surface2 text-muted border border-border' }}">
                                    {{ $order->status === 'in_progress' ? 'Sedang Dikerjakan' : 'Menunggu' }}
                                </span>
                            </div>

                            <!-- Customer & Address -->
                            <div>
                                <h3 class="text-base font-bold text-white tracking-tight">
                                    {{ $customer->name ?? 'Pelanggan' }}
                                </h3>
                                <p class="text-xs text-accent font-medium">{{ $order->title }}</p>
                                <p class="text-xs text-muted line-clamp-2 mt-0.5">
                                    <span class="material-symbols-rounded text-xs align-middle text-muted">pin_drop</span>
                                    {{ $customer->address ?? 'Alamat belum diisi' }}
                                </p>
                            </div>

                            <!-- Tech Specs Badge Info -->
                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-muted pt-1">
                                @if($customer?->package)
                                    <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-white font-medium">
                                        {{ $customer->package->name }}
                                    </span>
                                @endif
                                @if($customer?->odp)
                                    <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-accent font-medium">
                                        ODP: {{ $customer->odp->name }} {{ $customer->odp_port ? "(Port {$customer->odp_port})" : '' }}
                                    </span>
                                @endif
                                @if($order->scheduled_date)
                                    <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-muted">
                                        Jadwal: {{ $order->scheduled_date->format('d M Y') }}
                                    </span>
                                @endif
                            </div>

                            @if($order->description)
                                <div class="text-xs text-muted/90 bg-surface2/60 p-2.5 rounded-lg border border-border/70 text-[11px] leading-relaxed">
                                    <strong class="text-white">Instruksi Admin:</strong> {{ $order->description }}
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 pt-2 border-t border-border/80 pl-2">
                            <!-- Navigasi Maps -->
                            <a href="{{ $mapsUrl }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-surface2 hover:bg-border text-text text-xs font-semibold transition border border-border">
                                <span class="material-symbols-rounded text-sm text-accent">navigation</span>
                                <span>Rute Maps</span>
                            </a>

                            <!-- Chat WhatsApp -->
                            @if($waPhone)
                                <a href="https://wa.me/{{ $waPhone }}" target="_blank" class="inline-flex items-center justify-center p-2 rounded-xl bg-surface2 hover:bg-border text-accent transition border border-border" title="WhatsApp Pelanggan">
                                    <span class="material-symbols-rounded text-base">chat</span>
                                </a>
                            @endif

                            <!-- Detail & Selesaikan -->
                            <a href="{{ route('technician.work-order', $order->id) }}" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-accent hover:bg-accent-hover text-ink text-xs font-bold transition shadow-sm">
                                <span>Detail Tugas</span>
                                <span class="material-symbols-rounded text-sm">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recent Completed Tasks -->
    @if($recentCompleted->isNotEmpty())
        <div class="space-y-3 pt-4">
            <h2 class="text-sm font-bold text-muted px-1 flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base text-accent">check_circle</span>
                <span>Tugas Terakhir Diselesaikan</span>
            </h2>

            <div class="rounded-2xl bg-surface border border-border divide-y divide-border overflow-hidden">
                @foreach($recentCompleted as $comp)
                    <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white">{{ $comp->customer->name ?? 'Pelanggan' }}</span>
                                <span class="text-[10px] font-mono text-muted">{{ $comp->order_number }}</span>
                            </div>
                            <p class="text-[11px] text-muted mt-0.5">
                                {{ $comp->type === 'installation' ? 'Pasang Baru' : 'Perbaikan' }} &bull; Selesai: {{ $comp->completed_at?->diffForHumans() }}
                            </p>
                        </div>

                        <div class="text-right">
                            @if($comp->optical_power_dbm)
                                <span class="text-xs font-mono font-bold text-accent px-2 py-0.5 rounded bg-accent/10 border border-accent/30">
                                    {{ $comp->optical_power_dbm }} dBm
                                </span>
                            @else
                                <span class="text-[11px] text-accent font-semibold flex items-center gap-1">
                                    <span class="material-symbols-rounded text-sm">done</span> Selesai
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
