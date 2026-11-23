<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- 1. Hero Action Banner -->
    <div style="background: linear-gradient(135deg, rgba(6, 38, 26, 0.95) 0%, rgba(13, 21, 18, 0.98) 100%); border: 1px solid rgba(0, 224, 126, 0.25); border-radius: 1.25rem; padding: 1.75rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden;">
        <!-- Background decorative ambient circles -->
        <div style="position: absolute; right: -50px; top: -50px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(0, 224, 126, 0.15) 0%, transparent 70%); pointer-events: none;"></div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; align-items: center; position: relative; z-index: 1;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 9999px; background: rgba(0, 224, 126, 0.12); border: 1px solid rgba(0, 224, 126, 0.3); color: #00e07e; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                    <span class="material-symbols-rounded" style="font-size: 16px;">verified</span>
                    Loket Mitra Resmi ISP
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0 0 0.5rem 0; line-height: 1.25;">
                    Loket Pembayaran PPPoE Mandiri
                </h1>
                <p style="font-size: 0.875rem; color: #a1a1aa; margin: 0 0 1.25rem 0; line-height: 1.5; max-width: 540px;">
                    Cari tagihan pelanggan menggunakan nomor invoice, username PPPoE, nomor HP, atau scan barcode struk. Bayar instan menggunakan saldo deposit dengan unisolir otomatis di router MikroTik.
                </p>
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="{{ route('filament.admin.pages.agent-payment-point') }}"
                       style="display: inline-flex; align-items: center; gap: 0.5rem; background: #00e07e; color: #06261a; font-weight: 800; font-size: 0.875rem; padding: 0.75rem 1.5rem; border-radius: 0.75rem; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(0, 224, 126, 0.35);">
                        <span class="material-symbols-rounded" style="font-size: 20px;">point_of_sale</span>
                        Buka Loket Pembayaran PPPoE
                    </a>
                </div>
            </div>

            <!-- Agent Mini Identity & Workflow Card -->
            <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1rem; padding: 1.25rem;">
                <div style="font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; color: #71717a; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                    Informasi Loket Aktif
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 0.4rem;">
                        <span style="font-size: 0.8125rem; color: #a1a1aa;">Nama Loket:</span>
                        <span style="font-size: 0.875rem; font-weight: 700; color: #fff;">{{ $agent?->name ?? '-' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 0.4rem;">
                        <span style="font-size: 0.8125rem; color: #a1a1aa;">Kode Agen:</span>
                        <span style="font-size: 0.8125rem; font-weight: 800; color: #00e07e; background: rgba(0, 224, 126, 0.1); padding: 0.15rem 0.5rem; border-radius: 0.4rem;">
                            {{ $agent?->agent_code ?? '-' }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 0.4rem;">
                        <span style="font-size: 0.8125rem; color: #a1a1aa;">Status Layanan:</span>
                        @if ($agent?->is_active)
                            <span style="font-size: 0.75rem; font-weight: 700; color: #00e07e; display: flex; align-items: center; gap: 4px;">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #00e07e; display: inline-block;"></span>
                                Siap Melayani Transaksi
                            </span>
                        @else
                            <span style="font-size: 0.75rem; font-weight: 700; color: #f27b7b;">Non-Aktif</span>
                        @endif
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.8125rem; color: #a1a1aa;">Nomor Kontak / WA:</span>
                        <span style="font-size: 0.8125rem; font-weight: 600; color: #d4d4d8;">{{ $agent?->phone ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Recent Transactions Table -->
    <div style="background: var(--surface, #111614); border: 1px solid var(--border, rgba(255, 255, 255, 0.08)); border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h2 style="font-size: 1.1rem; font-weight: 800; color: var(--text, #fff); margin: 0;">
                    Riwayat Transaksi & Mutasi Saldo Terakhir
                </h2>
                <p style="font-size: 0.75rem; color: var(--muted, #a1a1aa); margin-top: 0.2rem;">
                    Daftar pembayaran tagihan internet dan top-up saldo deposit loket Anda
                </p>
            </div>
            <a href="{{ route('filament.admin.pages.agent-payment-point') }}"
               style="font-size: 0.8125rem; font-weight: 700; color: #00e07e; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                Buka Loket Pembayaran
                <span class="material-symbols-rounded" style="font-size: 16px;">arrow_forward</span>
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.08)); color: var(--muted, #71717a); font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;">
                        <th style="padding: 0.75rem 1rem;">Waktu</th>
                        <th style="padding: 0.75rem 1rem;">No. Referensi</th>
                        <th style="padding: 0.75rem 1rem;">Jenis Mutasi</th>
                        <th style="padding: 0.75rem 1rem;">Keterangan / Pelanggan</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Komisi</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Nominal</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Sisa Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $tx)
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04); transition: background 0.15s ease;"
                            onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 0.85rem 1rem; color: var(--muted, #a1a1aa); white-space: nowrap;">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="padding: 0.85rem 1rem; font-family: monospace; font-size: 0.75rem; color: var(--text, #fff); font-weight: 700;">
                                {{ $tx->payment_reference }}
                            </td>
                            <td style="padding: 0.85rem 1rem;">
                                @if ($tx->type === 'topup')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 0.2rem 0.55rem; border-radius: 0.375rem; font-size: 0.6875rem; font-weight: 700; background: rgba(0, 224, 126, 0.12); color: #00e07e; border: 1px solid rgba(0, 224, 126, 0.3);">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">add_circle</span>
                                        Top-Up Deposit
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 0.2rem 0.55rem; border-radius: 0.375rem; font-size: 0.6875rem; font-weight: 700; background: rgba(59, 130, 246, 0.12); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">receipt_long</span>
                                        Bayar PPPoE
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 0.85rem 1rem;">
                                @if ($tx->invoice)
                                    <div style="font-weight: 700; color: var(--text, #fff);">
                                        {{ $tx->invoice->customer?->name ?? 'Pelanggan' }}
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--muted, #a1a1aa);">
                                        {{ $tx->invoice->invoice_number }} (Periode: {{ $tx->invoice->period }})
                                    </div>
                                @else
                                    <div style="color: var(--muted, #a1a1aa);">{{ $tx->notes ?: 'Deposit Saldo Agen' }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.85rem 1rem; text-align: right; color: #00e07e; font-weight: 700;">
                                @if ((float) $tx->fee > 0)
                                    +Rp {{ number_format((float) $tx->fee, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 800; color: {{ $tx->type === 'topup' ? '#00e07e' : '#f27b7b' }};">
                                {{ $tx->type === 'topup' ? '+' : '-' }}Rp {{ number_format((float) $tx->amount, 0, ',', '.') }}
                            </td>
                            <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 700; color: var(--text, #fff);">
                                Rp {{ number_format((float) $tx->balance_after, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 2.5rem 1rem; text-align: center; color: var(--muted, #71717a);">
                                <span class="material-symbols-rounded" style="font-size: 36px; display: block; margin-bottom: 0.5rem; opacity: 0.5;">history</span>
                                Belum ada transaksi yang diproses oleh akun loket ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

