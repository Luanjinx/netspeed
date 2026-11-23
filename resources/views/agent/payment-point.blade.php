@extends('layouts.agent')

@section('title', 'Loket Pembayaran PPPoE')

@section('content')
<div class="space-y-6" id="loket-container">

    <!-- Header Balance & Loket Info -->
    <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden shadow-lg shadow-black/30">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-accent/15 border border-accent/30 text-accent flex items-center justify-center shrink-0">
                    <span class="material-symbols-rounded text-2xl">point_of_sale</span>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-white tracking-tight">Loket Pembayaran Tagihan PPPoE</h1>
                    <p class="text-xs text-muted">Ketik nomor invoice, ID pelanggan, username PPPoE, no HP, atau scan barcode</p>
                </div>
            </div>

            <!-- Deposit Balance Indicator -->
            <div class="flex items-center gap-3">
                <div class="bg-surface2 border border-border rounded-xl px-4 py-2.5">
                    <span class="text-[10px] font-bold text-muted uppercase block">Sisa Saldo Deposit:</span>
                    <span class="text-lg font-black text-accent" id="badge-balance">
                        {{ $agent ? $agent->formatted_balance : 'Rp 0' }}
                    </span>
                </div>
                <div class="bg-surface2 border border-border rounded-xl px-4 py-2.5">
                    <span class="text-[10px] font-bold text-muted uppercase block">Komisi Bersih:</span>
                    <span class="text-sm font-extrabold text-white">
                        Rp {{ number_format((float) ($agent->commission_per_transaction ?? 0), 0, ',', '.') }} / Invoice
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-md shadow-black/20">
        <div class="max-w-2xl mx-auto space-y-3">
            <label class="block text-xs font-bold text-white uppercase tracking-wider">
                Pencarian Universal Tagihan Pelanggan:
            </label>
            <div class="relative">
                <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-muted text-2xl">search</span>
                <input type="text"
                       id="search-input"
                       placeholder="Ketik No Invoice, Username PPPoE, Nama, No WA, atau Scan Barcode..."
                       autofocus
                       class="w-full pl-12 pr-12 py-3.5 rounded-xl bg-surface2 border border-border text-white text-sm font-semibold placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition shadow-inner">
                <button type="button" id="btn-clear-search" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-white p-1">
                    <span class="material-symbols-rounded text-lg">close</span>
                </button>
            </div>
            <p class="text-[11px] text-muted flex items-center gap-1.5">
                <span class="material-symbols-rounded text-xs text-accent">info</span>
                Mendukung pencarian cepat berdasarkan: Nomor Tagihan (cth: INV-202611-0001), Username PPPoE, No. HP/WhatsApp, Nama, SN Modem, atau Scan Barcode fisik struk.
            </p>
        </div>
    </div>

    <!-- Search Results / Invoice List -->
    <div id="results-container" class="space-y-4">
        <!-- Placeholder / Loading indicator -->
        <div id="loading-state" class="hidden py-12 text-center text-muted">
            <span class="material-symbols-rounded text-3xl animate-spin text-accent mb-2 block">progress_activity</span>
            <p class="text-xs">Mencari data tagihan di sistem...</p>
        </div>

        <div id="empty-state" class="hidden py-12 text-center text-muted rounded-2xl bg-surface border border-border">
            <span class="material-symbols-rounded text-4xl block mb-2 opacity-40">search_off</span>
            <p class="text-sm font-bold text-white">Tagihan Belum Ditemukan</p>
            <p class="text-xs mt-1 text-muted">Pastikan nomor invoice, username PPPoE, atau nomor HP yang diketik sudah benar.</p>
        </div>

        <!-- Dynamic Results Cards will be rendered here -->
        <div id="invoices-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
    </div>
</div>

<!-- Modal Konfirmasi Pembayaran -->
<div id="confirm-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="rounded-2xl bg-surface border border-border max-w-md w-full p-6 shadow-2xl relative space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-border">
            <div class="flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-xl">payments</span>
                <h3 class="font-bold text-white text-base">Konfirmasi Pembayaran</h3>
            </div>
            <button type="button" onclick="closeConfirmModal()" class="text-muted hover:text-white">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="space-y-3 text-xs" id="confirm-details">
            <!-- Populated via JS -->
        </div>

        <div class="p-3 rounded-xl bg-accent/10 border border-accent/20 text-accent text-[11px] flex items-center gap-2">
            <span class="material-symbols-rounded text-base">bolt</span>
            <span>Tagihan langsung berstatus LUNAS dan isolir router MikroTik terbuka otomatis.</span>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
            <button type="button" onclick="closeConfirmModal()" class="px-4 py-2.5 rounded-xl bg-surface2 hover:bg-border text-white text-xs font-semibold transition">
                Batal
            </button>
            <button type="button" id="btn-submit-pay" onclick="executePayment()" class="px-5 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-ink text-xs font-extrabold flex items-center gap-2 shadow-lg shadow-accent/25 transition">
                <span class="material-symbols-rounded text-base">check_circle</span>
                <span>Proses Pembayaran</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Sukses & Cetak Struk -->
<div id="success-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
    <div class="rounded-2xl bg-surface border border-border max-w-sm w-full p-6 shadow-2xl relative text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-accent/20 border border-accent/40 text-accent flex items-center justify-center mx-auto shadow-lg shadow-accent/20">
            <span class="material-symbols-rounded text-4xl">check_circle</span>
        </div>

        <div>
            <h3 class="text-lg font-black text-white">Pembayaran Sukses!</h3>
            <p class="text-xs text-muted mt-1" id="success-invoice-text">Tagihan berhasil dilunasi via loket.</p>
        </div>

        <!-- Receipt preview card -->
        <div class="bg-surface2 border border-border rounded-xl p-4 text-left font-mono text-xs space-y-1.5" id="success-summary">
            <!-- Populated via JS -->
        </div>

        <div class="flex flex-col gap-2 pt-2">
            <a href="#" id="btn-print-receipt" target="_blank" class="w-full py-3 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-xs flex items-center justify-center gap-2 shadow-lg shadow-accent/25 transition">
                <span class="material-symbols-rounded text-base">print</span>
                <span>Cetak Struk POS Thermal (58mm/80mm)</span>
            </a>
            <button type="button" onclick="closeSuccessModal()" class="w-full py-2.5 rounded-xl bg-surface2 hover:bg-border text-white text-xs font-semibold transition">
                Transaksi Baru
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let searchTimer = null;
    let selectedInvoiceData = null;

    const searchInput = document.getElementById('search-input');
    const btnClearSearch = document.getElementById('btn-clear-search');
    const loadingState = document.getElementById('loading-state');
    const emptyState = document.getElementById('empty-state');
    const invoicesGrid = document.getElementById('invoices-grid');
    const confirmModal = document.getElementById('confirm-modal');
    const confirmDetails = document.getElementById('confirm-details');
    const successModal = document.getElementById('success-modal');
    const btnSubmitPay = document.getElementById('btn-submit-pay');

    searchInput.addEventListener('input', function() {
        const val = this.value.trim();
        btnClearSearch.classList.toggle('hidden', val.length === 0);

        clearTimeout(searchTimer);
        if (val.length < 2) {
            invoicesGrid.innerHTML = '';
            emptyState.classList.add('hidden');
            loadingState.classList.add('hidden');
            return;
        }

        loadingState.classList.remove('hidden');
        emptyState.classList.add('hidden');

        searchTimer = setTimeout(() => {
            fetchInvoices(val);
        }, 300);
    });

    btnClearSearch.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.focus();
        btnClearSearch.classList.add('hidden');
        invoicesGrid.innerHTML = '';
        emptyState.classList.add('hidden');
    });

    async function fetchInvoices(query) {
        try {
            const res = await fetch(`{{ route('agent.ajax.search-invoices') }}?q=${encodeURIComponent(query)}`);
            const json = await res.json();
            loadingState.classList.add('hidden');

            if (json.status === 'success' && json.data.length > 0) {
                renderInvoices(json.data);
                emptyState.classList.add('hidden');
            } else {
                invoicesGrid.innerHTML = '';
                emptyState.classList.remove('hidden');
            }
        } catch (err) {
            loadingState.classList.add('hidden');
            console.error(err);
        }
    }

    function renderInvoices(invoices) {
        invoicesGrid.innerHTML = invoices.map(inv => `
            <div class="rounded-2xl bg-surface border border-border p-5 relative overflow-hidden hover:border-accent/50 transition shadow-md shadow-black/20 flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[10px] font-mono font-extrabold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/20">
                            ${inv.invoice_number}
                        </span>
                        ${inv.is_isolated
                            ? '<span class="text-[10px] font-bold px-2 py-0.5 rounded bg-danger/15 text-danger border border-danger/30 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-danger animate-pulse"></span> Terisolir</span>'
                            : '<span class="text-[10px] font-bold px-2 py-0.5 rounded bg-accent/15 text-accent border border-accent/30">Aktif</span>'
                        }
                    </div>

                    <h3 class="text-base font-extrabold text-white">${inv.customer_name}</h3>
                    <div class="text-xs text-muted mt-1 space-y-0.5">
                        <div>User PPPoE: <strong class="text-white">${inv.pppoe_username}</strong></div>
                        <div>Paket: <span class="text-accent font-semibold">${inv.package_name}</span></div>
                        <div>Periode: <strong class="text-white">${inv.period}</strong> | Jatuh Tempo: ${inv.due_date}</div>
                        <div>WhatsApp: ${inv.phone}</div>
                    </div>
                </div>

                <div class="pt-3 border-t border-border flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10px] uppercase font-bold text-muted">Total Tagihan:</div>
                        <div class="text-lg font-black text-white">${inv.formatted_total}</div>
                    </div>
                    <button type="button"
                            onclick='openConfirmModal(${JSON.stringify(inv)})'
                            class="px-4 py-2.5 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-xs shadow-md shadow-accent/20 transition flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-base">point_of_sale</span>
                        <span>Bayar Sekarang</span>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function openConfirmModal(inv) {
        selectedInvoiceData = inv;
        confirmDetails.innerHTML = `
            <div class="flex justify-between py-1 border-b border-border/50">
                <span class="text-muted">No. Invoice:</span>
                <span class="font-bold text-white font-mono">${inv.invoice_number}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-border/50">
                <span class="text-muted">Nama Pelanggan:</span>
                <span class="font-bold text-white">${inv.customer_name}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-border/50">
                <span class="text-muted">Username PPPoE:</span>
                <span class="font-bold text-accent">${inv.pppoe_username}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-border/50">
                <span class="text-muted">Periode:</span>
                <span class="font-bold text-white">${inv.period}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-border text-sm">
                <span class="font-bold text-white">Total Bayar:</span>
                <span class="font-black text-accent">${inv.formatted_total}</span>
            </div>
        `;
        confirmModal.classList.remove('hidden');
    }

    function closeConfirmModal() {
        confirmModal.classList.add('hidden');
        selectedInvoiceData = null;
    }

    async function executePayment() {
        if (!selectedInvoiceData) return;

        btnSubmitPay.disabled = true;
        btnSubmitPay.innerHTML = '<span class="material-symbols-rounded text-base animate-spin">progress_activity</span><span>Memproses...</span>';

        try {
            const res = await fetch(`{{ route('agent.ajax.pay-invoice') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    invoice_id: selectedInvoiceData.id
                })
            });

            const json = await res.json();
            btnSubmitPay.disabled = false;
            btnSubmitPay.innerHTML = '<span class="material-symbols-rounded text-base">check_circle</span><span>Proses Pembayaran</span>';

            if (res.ok && json.status === 'success') {
                closeConfirmModal();

                // Update balance on UI
                document.getElementById('badge-balance').innerText = json.data.formatted_balance;

                // Show success modal
                document.getElementById('success-invoice-text').innerText = `Tagihan ${json.data.invoice_number} berhasil dilunasi.`;
                document.getElementById('success-summary').innerHTML = `
                    <div class="flex justify-between"><span>Referensi:</span><span class="text-white font-bold">${json.data.reference}</span></div>
                    <div class="flex justify-between"><span>Pelanggan:</span><span class="text-white font-bold">${json.data.customer_name}</span></div>
                    <div class="flex justify-between"><span>Total Tagihan:</span><span class="text-accent font-bold">${json.data.formatted_amount}</span></div>
                    <div class="flex justify-between"><span>Komisi Loket:</span><span class="text-accent font-bold">+${json.data.formatted_commission}</span></div>
                    <div class="flex justify-between"><span>Sisa Saldo:</span><span class="text-white font-bold">${json.data.formatted_balance}</span></div>
                `;
                document.getElementById('btn-print-receipt').href = json.data.print_url;
                successModal.classList.remove('hidden');

                // Trigger print automatically if supported
                window.open(json.data.print_url, '_blank');

                // Remove paid invoice from results grid
                const card = searchInput.value;
                if (card) {
                    fetchInvoices(card);
                }
            } else {
                alert(json.message || 'Gagal memproses pembayaran.');
            }
        } catch (err) {
            btnSubmitPay.disabled = false;
            btnSubmitPay.innerHTML = '<span class="material-symbols-rounded text-base">check_circle</span><span>Proses Pembayaran</span>';
            alert('Terjadi kesalahan jaringan atau server: ' + err.message);
        }
    }

    function closeSuccessModal() {
        successModal.classList.add('hidden');
        searchInput.value = '';
        btnClearSearch.classList.add('hidden');
        invoicesGrid.innerHTML = '';
        searchInput.focus();
    }
</script>
@endpush
