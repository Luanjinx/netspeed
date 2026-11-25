@extends('layouts.technician')

@section('title', 'Persetujuan Daftar & Upgrade Paket')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-surface border border-border p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-warn/20 text-warn border border-warn/30">
                    {{ $pendingCount }} Menunggu ACC
                </span>
            </div>
            <h1 class="text-xl font-bold text-white">Persetujuan Daftar Baru & Upgrade Paket</h1>
            <p class="text-xs text-muted mt-0.5">Verifikasi pemasangan baru atau pengajuan upgrade paket pelanggan sebelum layanan diaktifkan.</p>
        </div>

        <div class="flex flex-wrap gap-2 text-xs font-semibold">
            @foreach(['pending' => 'Menunggu ACC', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
                <a href="{{ route('technician.package-requests', ['status' => $key]) }}"
                   class="px-3 py-2 rounded-xl border transition {{ $statusFilter === $key ? 'bg-accent text-ink border-accent font-bold' : 'bg-surface2 text-muted border-border hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="rounded-2xl bg-surface border border-border p-10 text-center">
            <span class="material-symbols-rounded text-4xl text-muted mb-2 block">fact_check</span>
            <p class="text-sm font-bold text-white">Tidak ada pengajuan pada kategori ini</p>
            <p class="text-xs text-muted mt-1">Pengajuan pasang baru atau upgrade paket dari aplikasi pelanggan akan muncul di sini.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach($requests as $req)
                <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-mono font-bold text-accent">{{ $req->request_number }}</span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-surface2 border border-border text-white">
                                    {{ $req->type === 'registration' ? 'Daftar Baru' : 'Upgrade Paket' }}
                                </span>
                                @if($req->status === 'pending')
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-warn/20 text-warn border border-warn/30">Menunggu ACC</span>
                                @elseif($req->status === 'approved')
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-accent/20 text-accent border border-accent/30">Disetujui</span>
                                @else
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-danger/20 text-danger border border-danger/30">Ditolak</span>
                                @endif

                                @if($req->type === 'upgrade')
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded {{ $req->payment_status === 'unpaid' ? 'bg-warn/15 text-warn' : 'bg-accent/15 text-accent' }}">
                                        {{ $req->payment_status === 'unpaid' ? 'Menunggu Pembayaran' : ($req->payment_status === 'paid' ? 'Prorata Lunas' : 'Tanpa Biaya') }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-white">{{ $req->customer?->name ?? '-' }}</h3>
                            <p class="text-xs text-muted">
                                ID: {{ $req->customer?->customer_number ?? '-' }} &bull; WA: {{ $req->customer?->phone ?? '-' }}
                                @if($req->customer?->address)
                                    &bull; {{ $req->customer->address }}
                                @endif
                            </p>
                        </div>

                        <div class="text-left sm:text-right text-xs space-y-1">
                            @if($req->type === 'upgrade')
                                <div class="text-muted">Paket Lama: <span class="text-white font-semibold">{{ $req->currentPackage?->name ?? '-' }}</span></div>
                            @endif
                            <div class="text-muted">Paket Diminta: <span class="text-accent font-bold">{{ $req->requestedPackage?->name ?? '-' }} ({{ $req->requestedPackage?->speed_rate ?? '-' }})</span></div>
                            <div class="text-[11px] text-muted">Diajukan: {{ $req->created_at?->format('d M Y H:i') }}</div>
                        </div>
                    </div>

                    @if($req->customer_notes || $req->admin_notes)
                        <div class="rounded-xl bg-surface2 border border-border p-3 text-xs space-y-1">
                            @if($req->customer_notes)
                                <div class="text-muted"><span class="font-semibold text-white">Catatan Pelanggan:</span> {{ $req->customer_notes }}</div>
                            @endif
                            @if($req->admin_notes)
                                <div class="text-muted"><span class="font-semibold text-accent">Catatan Petugas:</span> {{ $req->admin_notes }}</div>
                            @endif
                        </div>
                    @endif

                    @if($req->status === 'pending')
                        <div class="flex flex-col sm:flex-row gap-3 pt-2 border-t border-border">
                            <form action="{{ route('technician.package-requests.approve', $req->id) }}" method="POST" class="flex-1 flex flex-col sm:flex-row gap-2">
                                @csrf
                                <input type="text" name="notes" placeholder="Catatan ACC (opsional)..."
                                       class="flex-1 px-3 py-2 rounded-xl bg-surface2 border border-border text-xs text-white placeholder-muted focus:outline-none focus:border-accent">
                                <button type="submit"
                                        @disabled($req->type === 'upgrade' && $req->payment_status === 'unpaid')
                                        class="px-4 py-2 rounded-xl bg-accent hover:bg-accent-hover disabled:opacity-40 text-ink font-bold text-xs transition shrink-0">
                                    ACC &amp; Aktifkan
                                </button>
                            </form>

                            <form action="{{ route('technician.package-requests.reject', $req->id) }}" method="POST" class="flex-1 flex flex-col sm:flex-row gap-2">
                                @csrf
                                <input type="text" name="reason" required placeholder="Alasan penolakan..."
                                       class="flex-1 px-3 py-2 rounded-xl bg-surface2 border border-border text-xs text-white placeholder-muted focus:outline-none focus:border-danger">
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-danger/20 hover:bg-danger/30 text-danger border border-danger/30 font-bold text-xs transition shrink-0">
                                    Tolak
                                </button>
                            </form>
                        </div>
                    @elseif($req->reviewer)
                        <div class="text-[11px] text-muted pt-2 border-t border-border">
                            Diproses oleh <span class="text-white font-semibold">{{ $req->reviewer->name }}</span> pada {{ $req->reviewed_at?->format('d M Y H:i') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

