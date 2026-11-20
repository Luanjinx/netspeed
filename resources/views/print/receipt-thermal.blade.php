<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Thermal - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 5px;
        }
        .thermal-box {
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto;
            padding: 2px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .footer { font-size: 11px; text-align: center; margin-top: 10px; }
        .no-print { text-align: center; margin-bottom: 10px; }
        .btn { background: #000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; font-size: 12px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .thermal-box { width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button class="btn" onclick="window.print()">🖨️ Cetak Struk (58mm)</button>
    </div>

    <div class="thermal-box">
        <div class="text-center title">{{ strtoupper($isp['name']) }}</div>
        <div class="text-center">{{ $isp['address'] }}</div>
        <div class="text-center">Telp: {{ $isp['phone'] }}</div>
        <div class="line"></div>

        <div class="row">
            <span>No. Struk</span>
            <span class="bold">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="row">
            <span>Waktu</span>
            <span>{{ date('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>Pelanggan</span>
            <span class="bold">{{ substr($invoice->customer->name, 0, 15) }}</span>
        </div>
        <div class="row">
            <span>ID Pel.</span>
            <span>{{ $invoice->customer->customer_number }}</span>
        </div>
        <div class="line"></div>

        <div class="bold">{{ $invoice->package->name ?? 'Paket Internet' }}</div>
        <div class="row">
            <span>Periode {{ $invoice->period }}</span>
            <span>{{ number_format($invoice->amount, 0, ',', '.') }}</span>
        </div>
        @if ($invoice->unique_code > 0)
            <div class="row">
                <span>Kode Unik</span>
                <span>+{{ number_format($invoice->unique_code, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="line"></div>

        <div class="row bold" style="font-size: 13px;">
            <span>TOTAL</span>
            <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
        </div>
        <div class="row bold">
            <span>STATUS</span>
            <span>{{ $invoice->status === 'paid' ? '*** LUNAS ***' : 'BELUM DIBAYAR' }}</span>
        </div>
        <div class="line"></div>

        <div class="footer">
            Simpan struk ini sebagai<br>bukti pembayaran yang sah.<br>
            -- Terima Kasih --
        </div>
    </div>
</body>
</html>
