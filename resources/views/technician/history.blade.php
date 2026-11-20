@extends('layouts.technician')

@section('title', 'Riwayat Penanganan')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-64 h-64 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                        COMPLETED WORK ORDERS
                    </span>
                    <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                        Total: {{ $completedOrders->total() }} Tugas Selesai
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Riwayat Penanganan Tugas Lapangan
                </h1>
                <p class="text-xs text-muted mt-1 max-w-xl">
                    Arsip seluruh pekerjaan instalasi pasang baru dan perbaikan gangguan yang telah berhasil diselesaikan dan diverifikasi.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('technician.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface2 border border-border hover:border-accent text-xs font-semibold text-white transition">
                    <span class="material-symbols-rounded text-base text-accent">assignment</span>
                    <span>Tugas Aktif</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Search & Filter Form -->
    <div class="rounded-2xl bg-surface border border-border p-4">
        <form method="GET" action="{{ route('technician.history') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="sm:col-span-2 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor SPK, nama pelanggan, atau keluhan..." class="w-full bg-bg border border-border rounded-xl pl-9 pr-3.5 py-2 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                <span class="material-symbols-rounded text-base text-muted absolute left-3 top-1/2 -translate-y-1/2">search</span>
            </div>

            <div>
                <select name="type" class="w-full bg-bg border border-border rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-accent transition">
                    <option value="">Semua Tipe Pekerjaan</option>
                    <option value="installation" {{ request('type') === 'installation' ? 'selected' : '' }}>Pasang Baru (PSB)</option>
                    <option value="repair" {{ request('type') === 'repair' ? 'selected' : '' }}>Perbaikan Gangguan</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-accent hover:bg-accent-hover text-ink font-bold text-xs transition">
                    <span class="material-symbols-rounded text-sm">filter_list</span>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['search', 'type']))
                    <a href="{{ route('technician.history') }}" class="p-2 rounded-xl bg-surface2 border border-border hover:text-white text-muted text-xs transition" title="Reset Filter">
                        <span class="material-symbols-rounded text-base">restart_alt</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- List of Completed Orders -->
    @if($completedOrders->isEmpty())
        <div class="rounded-2xl bg-surface border border-border p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-surface2 border border-border text-muted flex items-center justify-center mx-auto mb-3.5">
                <span class="material-symbols-rounded text-3xl">history</span>
            </div>
            <h3 class="text-base font-bold text-white">Belum Ada Riwayat Tugas</h3>
            <p class="text-xs text-muted max-w-md mx-auto mt-1 leading-relaxed">
                Tidak ada data riwayat pekerjaan yang cocok dengan kriteria pencarian atau belum ada tugas yang diselesaikan.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($completedOrders as $order)
                @php
                    $isPsb = $order->type === 'installation';
                    $cust = $order->customer;
                @endphp

                <div class="rounded-2xl bg-surface border border-border hover:border-accent/30 transition p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded {{ $isPsb ? 'bg-accent/15 text-accent border border-accent/30' : 'bg-danger/15 text-danger border border-danger/30' }}">
                                {{ $isPsb ? 'PASANG BARU' : 'PERBAIKAN' }}
                            </span>
                            <span class="text-xs font-mono text-muted">{{ $order->order_number }}</span>
                            <span class="text-[10px] text-muted">&bull; {{ $order->completed_at?->format('d M Y, H:i') }}</span>
                        </div>

                        <h3 class="text-sm font-bold text-white">
                            {{ $cust->name ?? 'Pelanggan' }} <span class="text-muted font-normal text-xs">— {{ $order->title }}</span>
                        </h3>

                        <p class="text-xs text-muted">
                            <span class="material-symbols-rounded text-xs align-middle">location_on</span>
                            {{ $cust->address ?? 'Alamat tidak tersedia' }}
                        </p>

                        @if($order->completion_notes)
                            <p class="text-[11px] text-muted/90 bg-surface2 px-2.5 py-1.5 rounded-lg border border-border/60 mt-1">
                                <strong class="text-white">Catatan Pengerjaan:</strong> {{ $order->completion_notes }}
                            </p>
                        @endif
                    </div>

                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2.5 pt-2 sm:pt-0 border-t sm:border-t-0 border-border">
                        @if($order->optical_power_dbm)
                            <div class="text-right">
                                <span class="text-[10px] text-muted block sm:inline">Redaman OPM:</span>
                                <span class="text-xs font-mono font-bold text-accent px-2 py-0.5 rounded bg-accent/10 border border-accent/30">
                                    {{ $order->optical_power_dbm }} dBm
                                </span>
                            </div>
                        @endif

                        <a href="{{ route('technician.work-order', $order->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface2 border border-border hover:border-accent text-accent font-semibold text-xs transition">
                            <span>Lihat SPK</span>
                            <span class="material-symbols-rounded text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pt-2">
            {{ $completedOrders->withQueryString()->links() }}
        </div>
    @endif

</div>
@endsection

