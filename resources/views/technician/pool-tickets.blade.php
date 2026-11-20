@extends('layouts.technician')

@section('title', 'Pool Tiket Gangguan')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-64 h-64 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                        OPEN TICKETS POOL
                    </span>
                    <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                        {{ $tickets->count() }} Tiket Tersedia
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Pool Tiket Gangguan Lapangan
                </h1>
                <p class="text-xs text-muted mt-1 max-w-xl">
                    Tiket laporan kendala internet pelanggan yang belum memiliki teknisi penanggung jawab. Anda dapat mengklaim tiket ini secara mandiri untuk segera dilakukan penanganan di lokasi.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('technician.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface2 border border-border hover:border-accent text-xs font-semibold text-white transition">
                    <span class="material-symbols-rounded text-base text-accent">assignment</span>
                    <span>Tugas Saya</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Ticket List -->
    @if($tickets->isEmpty())
        <div class="rounded-2xl bg-surface border border-border p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-accent/10 border border-accent/20 text-accent flex items-center justify-center mx-auto mb-3.5">
                <span class="material-symbols-rounded text-3xl">task_alt</span>
            </div>
            <h3 class="text-base font-bold text-white">Pool Tiket Kosong</h3>
            <p class="text-xs text-muted max-w-md mx-auto mt-1 leading-relaxed">
                Saat ini tidak ada tiket gangguan terbuka yang belum ditangani. Semua tiket pelanggan telah diambil oleh teknisi atau jaringan beroperasi normal.
            </p>
            <div class="mt-4">
                <a href="{{ route('technician.dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-accent text-ink font-bold text-xs">
                    <span class="material-symbols-rounded text-sm">home</span>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($tickets as $ticket)
                @php
                    $cust = $ticket->customer;
                    $waPhone = $cust ? preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $cust->phone)) : '';
                    $hasCoords = $cust && $cust->latitude && $cust->longitude;
                    $mapsUrl = $hasCoords 
                        ? "https://www.google.com/maps/dir/?api=1&destination={$cust->latitude},{$cust->longitude}"
                        : ($cust ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($cust->address ?? '') : '#');
                @endphp

                <div class="rounded-2xl bg-surface border border-border hover:border-accent/40 transition p-5 flex flex-col justify-between gap-4 relative overflow-hidden shadow-md">
                    <div class="space-y-3">
                        <!-- Top Bar -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                                    {{ $ticket->ticket_number }}
                                </span>
                                @if($ticket->priority === 'critical' || $ticket->priority === 'high')
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-danger/15 text-danger border border-danger/30 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-danger animate-pulse"></span>
                                        {{ strtoupper($ticket->priority) }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-surface2 text-muted border border-border">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                @endif
                            </div>

                            <span class="text-[10px] text-muted flex items-center gap-1">
                                <span class="material-symbols-rounded text-xs">schedule</span>
                                {{ $ticket->created_at?->diffForHumans() }}
                            </span>
                        </div>

                        <!-- Ticket Subject & Customer Name -->
                        <div>
                            <h3 class="text-base font-bold text-white tracking-tight">
                                {{ $ticket->subject }}
                            </h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-semibold text-accent">{{ $cust->name ?? 'Pelanggan Umum' }}</span>
                                @if($cust?->customer_number)
                                    <span class="text-[10px] font-mono text-muted">({{ $cust->customer_number }})</span>
                                @endif
                            </div>
                            <p class="text-xs text-muted mt-1 line-clamp-2">
                                <span class="material-symbols-rounded text-xs align-middle text-muted">pin_drop</span>
                                {{ $cust->address ?? 'Alamat tidak tersedia' }}
                            </p>
                        </div>

                        <!-- Problem Description -->
                        @if($ticket->description)
                            <div class="p-3 rounded-xl bg-surface2/80 border border-border text-xs text-muted leading-relaxed">
                                <strong class="text-white block mb-0.5">Keluhan Pelanggan:</strong>
                                {{ $ticket->description }}
                            </div>
                        @endif

                        <!-- Infrastructure Info -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-muted pt-1">
                            @if($cust?->odp)
                                <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-accent font-medium">
                                    ODP: {{ $cust->odp->name }} {{ $cust->odp_port ? "(Port {$cust->odp_port})" : '' }}
                                </span>
                            @endif
                            @if($cust?->package)
                                <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-white font-medium">
                                    {{ $cust->package->name }}
                                </span>
                            @endif
                            @if($cust?->router)
                                <span class="px-2 py-0.5 rounded bg-surface2 border border-border text-muted">
                                    {{ $cust->router->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 pt-3 border-t border-border/80">
                        @if($hasCoords)
                            <a href="{{ $mapsUrl }}" target="_blank" class="inline-flex items-center justify-center gap-1 py-2 px-3 rounded-xl bg-surface2 hover:bg-border text-text text-xs font-semibold transition border border-border" title="Buka Maps">
                                <span class="material-symbols-rounded text-sm text-accent">navigation</span>
                                <span class="hidden sm:inline">Peta</span>
                            </a>
                        @endif

                        @if($waPhone)
                            <a href="https://wa.me/{{ $waPhone }}" target="_blank" class="inline-flex items-center justify-center p-2 rounded-xl bg-surface2 hover:bg-border text-accent transition border border-border" title="Chat WhatsApp Pelanggan">
                                <span class="material-symbols-rounded text-base">chat</span>
                            </a>
                        @endif

                        <form action="{{ route('technician.claim-ticket', $ticket->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" onclick="return confirm('Klaim tiket gangguan ini untuk Anda tangani?')" class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-accent hover:bg-accent-hover text-ink text-xs font-extrabold transition shadow-sm">
                                <span class="material-symbols-rounded text-base">handyman</span>
                                <span>Klaim & Ambil Tugas Ini</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

