@extends('layouts.agent')

@section('title', 'Riwayat Transaksi Agen')

@section('content')
<div class="space-y-6">

    <!-- Header & Filter Bar -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-accent">history</span>
                    Riwayat Transaksi & Mutasi Deposit
                </h1>
                <p class="text-xs text-muted mt-0.5">Daftar lengkap mutasi saldo, top-up deposit, dan pembayaran invoice PPPoE</p>
            </div>

            <!-- Filter Controls -->
            <form action="{{ route('agent.history') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Cari referensi, invoice, nama..."
                       class="px-3.5 py-2 rounded-xl bg-surface2 border border-border text-white text-xs placeholder:text-muted focus:outline-none focus:border-accent">

                <select name="type" class="px-3 py-2 rounded-xl bg-surface2 border border-border text-white text-xs focus:outline-none focus:border-accent">
                    <option value="">Semua Jenis</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Top-Up Deposit</option>
                    <option value="pppoe_payment" {{ request('type') === 'pppoe_payment' ? 'selected' : '' }}>Bayar PPPoE</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-accent text-ink font-bold text-xs hover:bg-accent-hover transition flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">filter_list</span>
                    <span>Filter</span>
                </button>

                @if(request()->hasAny(['q', 'type']))
                    <a href="{{ route('agent.history') }}" class="px-3 py-2 rounded-xl bg-surface2 text-muted hover:text-white border border-border text-xs transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Table of Transactions -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-border text-muted uppercase font-bold text-[10px] tracking-wider">
                        <th class="pb-3 px-3">Tanggal & Waktu</th>
                        <th class="pb-3 px-3">No. Referensi</th>
                        <th class="pb-3 px-3">Jenis Mutasi</th>
                        <th class="pb-3 px-3">Keterangan / Pelanggan</th>
                        <th class="pb-3 px-3 text-right">Komisi Bersih</th>
                        <th class="pb-3 px-3 text-right">Nominal</th>
                        <th class="pb-3 px-3 text-right">Sisa Saldo</th>
                        <th class="pb-3 px-3 text-center">Struk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-surface2/60 transition">
                            <td class="py-3 px-3 text-muted whitespace-nowrap">
                                {{ $tx->created_at->format('d/m/Y H:i:s') }}
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
                                    <div class="text-[10px] text-muted">{{ $tx->invoice->invoice_number }} (PPPoE: {{ $tx->invoice->customer?->pppoe_username ?? '-' }})</div>
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
                                    <a href="{{ route('print.thermal', $tx->invoice_id) }}" target="_blank" title="Cetak Struk Thermal" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-surface2 hover:bg-border text-accent transition">
                                        <span class="material-symbols-rounded text-base">print</span>
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-muted">
                                <span class="material-symbols-rounded text-4xl block mb-2 opacity-40">receipt_long</span>
                                Tidak ada transaksi yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-t border-border">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

