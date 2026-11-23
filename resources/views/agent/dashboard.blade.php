@extends('layouts.agent')

@section('title', 'Dashboard Loket Agen')

@section('content')
<div class="space-y-6">

    <!-- Top Profile & Quick Status Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-60 h-60 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-accent/15 border border-accent/30 flex items-center justify-center text-accent shrink-0 shadow-lg shadow-accent/10">
                    <span class="material-symbols-rounded text-3xl sm:text-4xl">storefront</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="text-[11px] font-mono font-extrabold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                            {{ $agent->agent_code ?? 'AGEN' }}
                        </span>
                        @if($agent && $agent->is_active)
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-accent/10 text-accent border border-accent/20 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-accent inline-block"></span>
                                Loket Aktif
                            </span>
                        @else
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-danger/10 text-danger border border-danger/20">
                                Non-Aktif
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                        Selamat Datang, <span class="text-accent">{{ $agent->name ?? Auth::user()->name }}</span>!
                    </h1>
                    <p class="text-xs text-muted mt-1 max-w-xl">
                        {{ $agent->address ?: 'Terima pembayaran tagihan internet pelanggan secara instan, otomatis unisolir pada MikroTik, dan cetak struk kasir thermal.' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('agent.payment-point') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-xs sm:text-sm shadow-lg shadow-accent/25 transition">
                    <span class="material-symbols-rounded text-lg">point_of_sale</span>
                    <span>Buka Loket Pembayaran</span>
                </a>

                <a href="{{ route('agent.history') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-3 rounded-xl bg-surface2 hover:bg-border text-white border border-border text-xs font-semibold transition">
                    <span class="material-symbols-rounded text-base text-accent">history</span>
                    <span>Riwayat Transaksi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Saldo Deposit -->
        <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">Saldo Deposit</span>
                <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">
                    <span class="material-symbols-rounded text-lg">wallet</span>
                </div>
            </div>
            <div class="text-2xl font-black text-white mb-1">
                {{ $agent ? $agent->formatted_balance : 'Rp 0' }}
            </div>
            <p class="text-[11px] {{ ($agent && (float)$agent->balance > 50000) ? 'text-accent' : 'text-warn' }} flex items-center gap-1">
                <span class="material-symbols-rounded text-sm">{{ ($agent && (float)$agent->balance > 50000) ? 'check_circle' : 'warning' }}</span>
                {{ ($agent && (float)$agent->balance > 0) ? 'Sisa deposit aktif melayani' : 'Segera setor isi ulang ke Admin' }}
            </p>
        </div>

        <!-- 2. Total Komisi Bulan Ini -->
        <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">Komisi Bulan Ini</span>
                <div class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">
                    <span class="material-symbols-rounded text-lg">savings</span>
                </div>
            </div>
            <div class="text-2xl font-black text-accent mb-1">
                Rp {{ number_format((float) $monthlyCommission, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-muted">
                Periode: <strong class="text-white">{{ now()->translatedFormat('F Y') }}</strong>
            </p>
        </div>

        <!-- 3. Transaksi Berhasil -->
        <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">PPPoE Terbayar</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                    <span class="material-symbols-rounded text-lg">check_circle</span>
                </div>
            </div>
            <div class="text-2xl font-black text-white mb-1">
                {{ $monthlyPaidCount }} <span class="text-sm font-semibold text-muted">Tagihan</span>
            </div>
            <p class="text-[11px] text-muted">
                Invoice pelanggan lunas via loket
            </p>
        </div>

        <!-- 4. Komisi per Tagihan -->
        <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">Reward / Tagihan</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center">
                    <span class="material-symbols-rounded text-lg">featured_seasonal_and_gifts</span>
                </div>
            </div>
            <div class="text-2xl font-black text-white mb-1">
                Rp {{ number_format((float) ($agent->commission_per_transaction ?? 0), 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-accent">
                Potongan komisi tunai instan
            </p>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-accent text-xl">history</span>
                    Transaksi & Mutasi Terakhir
                </h2>
                <p class="text-xs text-muted mt-0.5">Riwayat pengisian deposit dan pembayaran tagihan yang baru diproses</p>
            </div>
            <a href="{{ route('agent.history') }}" class="text-xs font-bold text-accent hover:underline flex items-center gap-1">
                Lihat Semua
                <span class="material-symbols-rounded text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-border text-muted uppercase font-bold text-[10px] tracking-wider">
                        <th class="pb-3 px-3">Waktu</th>
                        <th class="pb-3 px-3">No. Referensi</th>
                        <th class="pb-3 px-3">Jenis</th>
                        <th class="pb-3 px-3">Keterangan / Pelanggan</th>
                        <th class="pb-3 px-3 text-right">Komisi</th>
                        <th class="pb-3 px-3 text-right">Nominal</th>
                        <th class="pb-3 px-3 text-right">Sisa Saldo</th>
                        <th class="pb-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-surface2/60 transition">
                            <td class="py-3 px-3 text-muted whitespace-nowrap">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-white">
                                {{ $tx->payment_reference }}
                            </td>
                            <td class="py-3 px-3">
                                @if($tx->type === 'deposit')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-accent/15 text-accent border border-accent/30">
                                        Top-Up Deposit
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">
                                        Bayar PPPoE
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if($tx->invoice)
                                    <div class="font-bold text-white">{{ $tx->invoice->customer?->name ?? 'Pelanggan' }}</div>
                                    <div class="text-[10px] text-muted">{{ $tx->invoice->invoice_number }} (Periode: {{ $tx->invoice->period }})</div>
                                @else
                                    <div class="text-muted">{{ $tx->notes ?: 'Deposit Saldo Agen' }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-accent">
                                {{ (float)$tx->fee > 0 ? '+Rp ' . number_format((float)$tx->fee, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 px-3 text-right font-extrabold {{ $tx->type === 'deposit' ? 'text-accent' : 'text-danger' }}">
                                {{ $tx->type === 'deposit' ? '+' : '-' }}Rp {{ number_format((float)$tx->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-white">
                                Rp {{ number_format((float)$tx->balance_after, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($tx->invoice_id)
                                    <a href="{{ route('print.thermal', $tx->invoice_id) }}" target="_blank" title="Cetak Ulang Struk" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-surface2 hover:bg-border text-accent transition">
                                        <span class="material-symbols-rounded text-base">print</span>
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-muted">
                                <span class="material-symbols-rounded text-3xl block mb-2 opacity-40">receipt_long</span>
                                Belum ada transaksi pembayaran yang dilakukan oleh akun loket ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

