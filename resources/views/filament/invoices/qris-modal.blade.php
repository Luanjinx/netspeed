<div class="flex flex-col items-center justify-center p-4 text-center">
    <div class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        Scan QRIS Dinamis berikut menggunakan Mobile Banking atau E-Wallet (BCA, Mandiri, BRI, BNI, Dana, GoPay, OVO, ShopeePay, LinkAja).
    </div>

    @if (!empty($svg))
        <div class="p-3 bg-white rounded-xl shadow-md border border-gray-200 inline-block">
            {!! $svg !!}
        </div>
    @else
        <div class="p-4 bg-amber-50 text-amber-700 rounded-lg text-sm">
            QRIS Statis belum dikonfigurasi di Pengaturan ISP.
        </div>
    @endif

    <div class="mt-4">
        <span class="text-xs uppercase tracking-wider text-gray-400">Total Nominal Pembayaran</span>
        <div class="text-2xl font-bold text-emerald-600">
            Rp {{ number_format($total, 0, ',', '.') }}
        </div>
        @if ($uniqueCode > 0)
            <div class="text-xs text-gray-500 mt-1">
                Termasuk kode unik verifikasi: <strong class="text-emerald-700">{{ $uniqueCode }}</strong>
            </div>
        @endif
    </div>

    @if (!empty($payload))
        <div class="mt-4 w-full text-left">
            <span class="text-xs text-gray-400">Payload QRIS:</span>
            <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs font-mono break-all select-all">
                {{ $payload }}
            </div>
        </div>
    @endif
</div>
