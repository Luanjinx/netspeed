@extends('layouts.customer')

@section('title', 'Tiket Gangguan & Bantuan')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#232826] pb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('customer.dashboard') }}" class="text-xs text-[#9aa49f] hover:text-white transition flex items-center gap-1">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Pusat Bantuan & Tiket Gangguan
            </h1>
            <p class="text-xs sm:text-sm text-[#9aa49f] mt-1">
                Laporkan segala kendala koneksi, kerusakan fiber optic, atau kebutuhan teknis Anda langsung ke tim NOC
            </p>
        </div>

        <div>
            <button type="button" onclick="document.getElementById('newTicketModal').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] font-extrabold text-xs shadow-lg shadow-[#00e07e]/20 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                <span>Buat Tiket Baru</span>
            </button>
        </div>
    </div>

    <!-- Active Tickets & History -->
    <div class="glass-card p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                <span>🎫</span>
                <span>Daftar Riwayat Tiket Gangguan</span>
            </h2>
            <span class="text-xs text-[#9aa49f]">Total: {{ $tickets->total() }} Tiket</span>
        </div>

        @if($tickets->isEmpty())
            <div class="py-12 text-center text-[#9aa49f] space-y-4">
                <div class="w-24 h-auto mx-auto filter drop-shadow-[0_8px_20px_rgba(0,224,126,0.25)]">
                    <img src="{{ asset('images/mascot-technician.webp') }}" alt="Mascot NOC" class="w-full h-auto object-contain">
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-[#eef1f0]">Tidak Ada Tiket Gangguan</h3>
                    <p class="text-xs text-[#9aa49f] max-w-sm mx-auto">
                        Koneksi jaringan Anda terpantau normal &amp; optimal. Tim teknisi siaga 24/7 jika Anda membutuhkan bantuan!
                    </p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($tickets as $ticket)
                    <div class="p-5 rounded-xl bg-[#1a1e1c] border border-[#232826] hover:border-[#00e07e]/40 transition space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#232826] pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-[#131615] text-[#eef1f0] border border-[#232826]">
                                    #{{ $ticket->ticket_number }}
                                </span>

                                @if($ticket->status === 'open')
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#00e07e]/15 text-[#00e07e] border border-[#00e07e]/30">
                                        Terbuka (Menunggu Review)
                                    </span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#4deda4]/15 text-[#4deda4] border border-[#4deda4]/30">
                                        Sedang Ditangani Teknisi
                                    </span>
                                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed')
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#131615] text-[#9aa49f] border border-[#232826]">
                                        Selesai / Ditutup
                                    </span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#f2c56b]/15 text-[#f2c56b] border border-[#f2c56b]/30">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                @endif

                                @php
                                    $prioBadge = match($ticket->priority) {
                                        'urgent' => 'bg-[#f27b7b]/15 text-[#f27b7b] border-[#f27b7b]/30',
                                        'high' => 'bg-[#f2c56b]/15 text-[#f2c56b] border-[#f2c56b]/30',
                                        'low' => 'bg-[#131615] text-[#9aa49f] border-[#232826]',
                                        default => 'bg-[#00e07e]/15 text-[#00e07e] border-[#00e07e]/30',
                                    };
                                @endphp
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase {{ $prioBadge }}">
                                    Prioritas: {{ $ticket->priority ?? 'Sedang' }}
                                </span>
                            </div>

                            <span class="text-xs text-[#9aa49f]">
                                Dilaporkan: {{ $ticket->created_at->format('d M Y, H:i') }} ({{ $ticket->created_at->diffForHumans() }})
                            </span>
                        </div>

                        <div>
                            <h3 class="text-sm font-extrabold text-white">{{ $ticket->subject }}</h3>
                            <p class="text-xs text-[#9aa49f] mt-1 whitespace-pre-line leading-relaxed">
                                {{ $ticket->description }}
                            </p>
                        </div>

                        @if($ticket->resolved_at)
                            <div class="pt-2 border-t border-[#232826] text-[11px] text-[#00e07e] flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <span>Diselesaikan pada: {{ $ticket->resolved_at->format('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="pt-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Create New Ticket -->
<div id="newTicketModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full p-6 sm:p-8 bg-[#131615] border border-[#232826] shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-[#232826]">
            <div>
                <h3 class="text-lg font-extrabold text-white">Buat Laporan / Tiket Gangguan</h3>
                <p class="text-xs text-[#9aa49f]">Jelaskan kendala Anda agar teknisi dapat menyiapkan perbaikan</p>
            </div>
            <button type="button" onclick="document.getElementById('newTicketModal').classList.add('hidden')" class="text-[#9aa49f] hover:text-white text-xl">
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('customer.tickets.store') }}" class="space-y-4 mt-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Judul / Subjek Kendala</label>
                <input type="text" name="subject" required placeholder="Contoh: Internet LOS Lampu Merah berkedip" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Tingkat Prioritas</label>
                <select name="priority" class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition">
                    <option value="medium">Sedang (Kendala wajar / berkala)</option>
                    <option value="low">Rendah (Pertanyaan umum / konsultasi)</option>
                    <option value="high">Tinggi (Koneksi putus total)</option>
                    <option value="urgent">Urgent / Sangat Mendesak (Kabel FO putus fisik)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#eef1f0] mb-1">Deskripsi Lengkap Kendala</label>
                <textarea name="description" rows="4" required placeholder="Jelaskan detail kendala, sejak jam berapa terjadi, indikator lampu router, dll..." class="w-full px-3.5 py-2.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-sm text-[#eef1f0] focus:outline-none focus:border-[#00e07e] transition"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#232826]">
                <button type="button" onclick="document.getElementById('newTicketModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#9aa49f] text-xs font-bold transition border border-[#232826]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] text-xs font-extrabold shadow-lg shadow-[#00e07e]/20 transition">
                    Kirim Tiket Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
