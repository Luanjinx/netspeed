<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 25px; color: #333; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #1e40af; margin-bottom: 4px; }
        .company-desc { font-size: 13px; color: #64748b; line-height: 1.4; }
        .inv-title { text-align: right; }
        .inv-title h2 { margin: 0; color: #1e293b; font-size: 26px; }
        .inv-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-top: 5px; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .meta-grid { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .meta-col { width: 48%; }
        .meta-col h4 { margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; color: #64748b; }
        .meta-col p { margin: 3px 0; font-size: 14px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        table.items th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px; text-align: left; font-size: 13px; color: #475569; }
        table.items td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .text-right { text-align: right; }
        .total-box { margin-left: auto; width: 300px; margin-bottom: 25px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .total-grand { font-size: 18px; font-weight: bold; color: #1e40af; border-top: 2px solid #cbd5e1; padding-top: 8px; margin-top: 4px; }
        .qr-section { display: flex; align-items: center; gap: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .qr-img { width: 130px; height: 130px; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 35px; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        .print-btn { background: #2563eb; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        @media print { .no-print { display: none; } .invoice-box { border: none; box-shadow: none; padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: auto; text-align: right;">
        <button class="print-btn" onclick="window.print()">🖨️ Cetak Invoice (A4)</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 50px; width: auto; object-fit: contain;">
                <div>
                    <div class="company-name">{{ $isp['name'] }}</div>
                    <div class="company-desc">
                        {{ $isp['address'] }}<br>
                        Telp / WhatsApp: {{ $isp['phone'] }}
                    </div>
                </div>
            </div>
            <div class="inv-title">
                <h2>INVOICE</h2>
                <div class="inv-badge {{ $invoice->status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                    {{ strtoupper($invoice->status === 'paid' ? 'LUNAS' : 'BELUM DIBAYAR') }}
                </div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-col">
                <h4>Ditagihkan Kepada:</h4>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                <p>No. Pelanggan: {{ $invoice->customer->customer_number }}</p>
                <p>Telepon: {{ $invoice->customer->phone }}</p>
                <p>Alamat: {{ $invoice->customer->address ?: '-' }}</p>
            </div>
            <div class="meta-col text-right">
                <h4>Rincian Tagihan:</h4>
                <p><strong>No. Tagihan:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Periode:</strong> {{ $invoice->period }}</p>
                <p><strong>Tgl Jatuh Tempo:</strong> {{ date('d F Y', strtotime($invoice->due_date)) }}</p>
                @if ($invoice->paid_at)
                    <p><strong>Tgl Lunas:</strong> {{ date('d F Y H:i', strtotime($invoice->paid_at)) }}</p>
                @endif
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Deskripsi Layanan</th>
                    <th>Kecepatan</th>
                    <th>Tipe</th>
                    <th class="text-right">Biaya (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $invoice->package->name ?? 'Layanan Internet' }}</strong><br>
                        <span style="font-size: 12px; color: #64748b;">Langganan internet periode {{ $invoice->period }}</span>
                    </td>
                    <td>{{ $invoice->package->speed_rate ?? '-' }}</td>
                    <td>{{ strtoupper($invoice->package->type ?? 'PPPoE') }}</td>
                    <td class="text-right">{{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
            </div>
            @if ($invoice->unique_code > 0)
                <div class="total-row" style="color: #059669;">
                    <span>Kode Unik Verifikasi:</span>
                    <span>+ Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="total-row total-grand">
                <span>TOTAL BAYAR:</span>
                <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if ($invoice->status === 'unpaid' && !empty($qrDataUri))
            <div class="qr-section">
                <img src="{{ $qrDataUri }}" class="qr-img" alt="QRIS Dinamis">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #1e40af;">Pembayaran Cepat via QRIS</h4>
                    <p style="margin: 0; font-size: 13px; color: #475569;">
                        Scan QRIS di samping menggunakan BCA, Mandiri, BRI, BNI, Dana, GoPay, OVO, atau ShopeePay.
                        Nominal akan otomatis terisi sebesar <strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>.
                    </p>
                </div>
            </div>
        @endif

        <div class="footer">
            Kuitansi ini dibuat secara otomatis oleh sistem komputer dan sah tanpa tanda tangan basah.<br>
            Terima kasih telah menggunakan layanan {{ $isp['name'] }}.
        </div>
    </div>
</body>
</html>
