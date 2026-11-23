<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Consolas', 'Courier New', Monaco, 'Lucida Console', monospace;
            font-size: 11.5px;
            line-height: 1.35;
            color: #000;
            background: #e4e7eb;
            margin: 0;
            padding: 20px 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Preview Toolbar */
        .toolbar {
            max-width: 420px;
            margin: 0 auto 15px auto;
            background: #111614;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            font-family: system-ui, -apple-system, sans-serif;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .toolbar-btn {
            background: #1e2422;
            color: #d1d5db;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .toolbar-btn:hover {
            background: #28302d;
            color: #fff;
        }

        .toolbar-btn.active {
            background: #00e07e;
            color: #06261a;
            border-color: #00e07e;
            font-weight: 800;
        }

        .toolbar-btn-print {
            background: #00e07e;
            color: #06261a;
            border: 1px solid #00e07e;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 224, 126, 0.3);
        }

        .toolbar-btn-print:hover {
            background: #4deda4;
        }

        /* Receipt Box Canvas */
        .thermal-wrapper {
            display: flex;
            justify-content: center;
        }

        .receipt-card {
            background: #fff;
            width: 58mm;
            max-width: 58mm;
            padding: 14px 10px 18px 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            border-radius: 4px;
            transition: width 0.2s ease, max-width 0.2s ease;
        }

        .receipt-card.size-80 {
            width: 80mm;
            max-width: 80mm;
            font-size: 12.5px;
            padding: 18px 14px 22px 14px;
        }

        /* Typography & Layout Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }

        .logo-wrap {
            text-align: center;
            margin-bottom: 6px;
        }

        .logo-img {
            max-height: 38px;
            width: auto;
            object-fit: contain;
            filter: grayscale(100%) contrast(150%);
        }

        .isp-title {
            font-size: 13.5px;
            font-weight: 900;
            letter-spacing: 0.04em;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .receipt-card.size-80 .isp-title {
            font-size: 15px;
        }

        .isp-sub {
            font-size: 9.5px;
            color: #222;
            line-height: 1.25;
            margin-bottom: 4px;
        }

        .receipt-card.size-80 .isp-sub {
            font-size: 10.5px;
        }

        .divider-dashed {
            border-top: 1px dashed #000;
            margin: 6px 0;
            width: 100%;
        }

        .divider-double {
            border-top: 2px dashed #000;
            margin: 7px 0;
            width: 100%;
        }

        .badge-title {
            font-size: 11px;
            font-weight: 900;
            text-align: center;
            letter-spacing: 0.05em;
            margin: 3px 0 1px 0;
        }

        .receipt-card.size-80 .badge-title {
            font-size: 12px;
        }

        /* Key-Value Pair Grid */
        .kv-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 2px 0;
            gap: 6px;
            word-break: break-word;
        }

        .kv-label {
            color: #333;
            flex-shrink: 0;
        }

        .kv-val {
            text-align: right;
            font-weight: 600;
            color: #000;
        }

        /* Items Table */
        .item-head {
            font-weight: bold;
            margin-top: 4px;
            color: #000;
        }

        .item-sub {
            font-size: 9.5px;
            color: #444;
            margin-bottom: 3px;
        }

        .receipt-card.size-80 .item-sub {
            font-size: 10.5px;
        }

        /* Grand Total Box */
        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            font-weight: 900;
            margin: 5px 0;
        }

        .receipt-card.size-80 .total-box {
            font-size: 15px;
        }

        .status-paid-banner {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-weight: 900;
            font-size: 11.5px;
            letter-spacing: 0.08em;
            margin: 6px 0;
        }

        /* QR Code & Barcode */
        .qr-wrap {
            text-align: center;
            margin: 8px 0 4px 0;
        }

        .qr-svg {
            display: inline-block;
            width: 72px;
            height: 72px;
            margin: 0 auto;
        }

        .barcode-visual {
            letter-spacing: 3px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }

        .footer-note {
            text-align: center;
            font-size: 9.5px;
            line-height: 1.35;
            color: #222;
            margin-top: 6px;
        }

        .receipt-card.size-80 .footer-note {
            font-size: 10.5px;
        }

        /* PRINT STYLES */
        @media print {
            .toolbar {
                display: none !important;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .thermal-wrapper {
                margin: 0;
                padding: 0;
            }

            .receipt-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 2mm 3mm !important;
                margin: 0 !important;
            }

            .receipt-card.size-58 {
                width: 58mm !important;
                max-width: 58mm !important;
            }

            .receipt-card.size-80 {
                width: 80mm !important;
                max-width: 80mm !important;
            }

            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="checkAutoPrint()">

    <!-- Control Toolbar (Screen Only) -->
    <div class="toolbar">
        <div class="toolbar-group">
            <span style="color: #9aa49f; font-size: 11px; font-weight: 600;">Ukuran:</span>
            <button type="button" id="btn-58" class="toolbar-btn active" onclick="setSize(58)">
                58 mm
            </button>
            <button type="button" id="btn-80" class="toolbar-btn" onclick="setSize(80)">
                80 mm
            </button>
        </div>

        <div class="toolbar-group">
            <button type="button" class="toolbar-btn-print" onclick="window.print()">
                🖨️ Cetak Struk
            </button>
            <button type="button" class="toolbar-btn" onclick="window.close()">
                Tutup
            </button>
        </div>
    </div>

    <!-- Thermal Receipt Body -->
    <div class="thermal-wrapper">
        <div class="receipt-card size-58" id="receipt-card">

            <!-- 1. Header ISP -->
            <div class="logo-wrap">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            </div>
            <div class="text-center isp-title">{{ strtoupper($isp['name']) }}</div>
            <div class="text-center isp-sub">
                INTERNET SERVICE PROVIDER<br>
                {{ $isp['address'] }}<br>
                CS / WhatsApp: {{ $isp['phone'] }}
            </div>

            <div class="divider-double"></div>

            <!-- 2. Receipt Title -->
            <div class="badge-title uppercase">BUKTI PEMBAYARAN RESMI</div>
            <div class="text-center" style="font-size: 9px; color: #444; margin-bottom: 3px;">
                OFFICIAL PAYMENT RECEIPT
            </div>

            <div class="divider-dashed"></div>

            <!-- 3. Transaction Metadata -->
            <div class="kv-row">
                <span class="kv-label">No. Tagihan</span>
                <span class="kv-val">{{ $invoice->invoice_number }}</span>
            </div>

            @if($agentTx)
                <div class="kv-row">
                    <span class="kv-label">No. Referensi</span>
                    <span class="kv-val">{{ $agentTx->payment_reference }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Loket / Agen</span>
                    <span class="kv-val">{{ strtoupper($agentTx->agent?->name ?? 'LOKET RESMI') }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Kode Loket</span>
                    <span class="kv-val">{{ $agentTx->agent?->agent_code ?? '-' }}</span>
                </div>
            @else
                <div class="kv-row">
                    <span class="kv-label">Loket / Kasir</span>
                    <span class="kv-val">KASIR PUSAT NOC</span>
                </div>
            @endif

            <div class="kv-row">
                <span class="kv-label">Waktu Bayar</span>
                <span class="kv-val">
                    {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}
                </span>
            </div>

            <div class="divider-dashed"></div>

            <!-- 4. Customer Info -->
            <div class="kv-row">
                <span class="kv-label">ID Pelanggan</span>
                <span class="kv-val">{{ $invoice->customer->customer_number }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Nama Pel.</span>
                <span class="kv-val font-bold">{{ strtoupper($invoice->customer->name) }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">User PPPoE</span>
                <span class="kv-val font-bold">{{ $invoice->customer->pppoe_username }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">No. WhatsApp</span>
                <span class="kv-val">{{ $invoice->customer->phone ?? '-' }}</span>
            </div>

            <div class="divider-dashed"></div>

            <!-- 5. Package & Breakdown -->
            <div class="item-head">{{ $invoice->package->name ?? 'Layanan Paket Internet' }}</div>
            <div class="item-sub">
                Kecepatan: {{ $invoice->package->speed_rate ?? 'Broadband' }} | Periode: {{ $invoice->period }}
            </div>

            <div class="kv-row">
                <span class="kv-label">Biaya Langganan</span>
                <span class="kv-val">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</span>
            </div>

            @if((int) $invoice->unique_code > 0)
                <div class="kv-row">
                    <span class="kv-label">Biaya Admin / Kode</span>
                    <span class="kv-val">Rp {{ number_format((float) $invoice->unique_code, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="divider-dashed"></div>

            <!-- 6. Total Bayar -->
            <div class="total-box">
                <span>TOTAL BAYAR</span>
                <span>Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</span>
            </div>

            <div class="kv-row" style="font-size: 10.5px;">
                <span class="kv-label">Metode Pembayaran</span>
                <span class="kv-val">
                    @if($agentTx)
                        LOKET AGEN (TUNAI)
                    @elseif($invoice->payments->first())
                        {{ strtoupper($invoice->payments->first()->payment_channel ?? 'TUNAI') }}
                    @else
                        KASIR TUNAI
                    @endif
                </span>
            </div>

            <!-- 7. Status Lunas -->
            <div class="status-paid-banner">
                @if($invoice->status === 'paid')
                    *** LUNAS / TERBAYAR ***
                @else
                    *** BELUM LUNAS ***
                @endif
            </div>

            <div class="divider-double"></div>

            <!-- 8. QR Code Verification -->
            @if(!empty($qrDataUri))
                <div class="qr-wrap">
                    <img src="{{ $qrDataUri }}" alt="QR Verifikasi" class="qr-svg">
                    <div style="font-size: 8.5px; color: #444; margin-top: 3px;">
                        Pindai QR untuk verifikasi struk asli
                    </div>
                </div>
            @endif

            <div class="text-center barcode-visual">
                * {{ $invoice->invoice_number }} *
            </div>

            <!-- 9. Footer Terms & Thank You -->
            <div class="footer-note">
                Struk ini merupakan bukti pembayaran yang sah diterbitkan secara elektronik.<br>
                Koneksi internet Anda aktif otomatis setelah transaksi berhasil.<br>
                Simpan bukti pembayaran ini baik-baik.<br>
                <strong>Bantuan Layanan: {{ $isp['phone'] }}</strong><br>
                -- TERIMA KASIH ATAS PEMBAYARAN ANDA --
            </div>

        </div>
    </div>

    <script>
        function setSize(size) {
            const card = document.getElementById('receipt-card');
            const btn58 = document.getElementById('btn-58');
            const btn80 = document.getElementById('btn-80');

            if (size === 80) {
                card.classList.remove('size-58');
                card.classList.add('size-80');
                btn80.classList.add('active');
                btn58.classList.remove('active');
            } else {
                card.classList.remove('size-80');
                card.classList.add('size-58');
                btn58.classList.add('active');
                btn80.classList.remove('active');
            }
        }

        function checkAutoPrint() {
            // Auto open print dialog if opened in popup window
            if (window.opener) {
                setTimeout(() => {
                    window.print();
                }, 350);
            }
        }

        // Keyboard shortcut: Press Enter to print, Esc to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                window.print();
            } else if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>
