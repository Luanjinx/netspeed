@extends('layouts.technician')

@section('title', 'Pasang Baru Lapangan (PSB)')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Top Banner -->
    <div class="rounded-2xl bg-surface border border-border p-5 sm:p-6 relative overflow-hidden shadow-lg shadow-black/40">
        <div class="absolute right-0 top-0 w-64 h-64 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-surface2 text-accent border border-accent/30">
                        INSTANT FIELD PSB
                    </span>
                    <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-surface2 text-muted border border-border">
                        Auto-Sync MikroTik & WA
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                    Input Pelanggan Baru (PSB)
                </h1>
                <p class="text-xs text-muted mt-1 max-w-xl">
                    Daftarkan pelanggan langsung di lokasi instalasi. Sistem akan otomatis membuat akun PPPoE di MikroTik Core, mengalokasikan port ODP, dan mengirim pesan aktivasi WhatsApp ke pelanggan.
                </p>
            </div>

            <a href="{{ route('technician.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface2 border border-border hover:border-accent text-xs font-semibold text-muted hover:text-white transition self-start sm:self-auto">
                <span class="material-symbols-rounded text-sm">arrow_back</span>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-danger/10 border border-danger/30 text-xs text-danger space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">error</span>
                <span>Terdapat kesalahan pengisian data:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px] space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('technician.store-customer') }}" method="POST" class="space-y-6" id="form-psb">
        @csrf

        <!-- 1. Data Pelanggan -->
        <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-lg">person</span>
                <span>1. Data Pribadi & Kontak Pelanggan</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Nama Lengkap <span class="text-accent">*</span>
                    </label>
                    <input type="text" name="name" id="cust_name" value="{{ old('name') }}" required placeholder="cth: Budi Setiawan" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Nomor WhatsApp / HP <span class="text-accent">*</span>
                    </label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="cth: 08123456789" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                    <p class="text-[10px] text-muted mt-1">Notifikasi selamat datang & tagihan dikirim via WhatsApp.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-white mb-1.5">
                        Alamat Email (Opsional)
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="cth: budi@gmail.com" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-white mb-1.5">
                        Alamat Pemasangan Rumah / Kantor <span class="text-accent">*</span>
                    </label>
                    <textarea name="address" required rows="2" placeholder="cth: Jl. Merpati No. 12, RT 02 / RW 04, Kelurahan Sukamaju" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted focus:outline-none focus:border-accent transition">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 2. Lokasi Koordinat GPS -->
        <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-accent text-lg">location_on</span>
                    <span>2. Titik Koordinat GPS Rumah</span>
                </h2>

                <button type="button" onclick="getCurrentGpsLocation()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-accent/15 border border-accent/40 text-accent font-bold text-xs hover:bg-accent/25 transition">
                    <span class="material-symbols-rounded text-base" id="gps-icon">my_location</span>
                    <span id="gps-btn-label">Ambil GPS Saya Sekarang</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-muted mb-1.5">Latitude</label>
                    <input type="text" name="latitude" id="input_latitude" value="{{ old('latitude') }}" placeholder="-6.200000" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted font-mono focus:outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block font-semibold text-muted mb-1.5">Longitude</label>
                    <input type="text" name="longitude" id="input_longitude" value="{{ old('longitude') }}" placeholder="106.816666" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted font-mono focus:outline-none focus:border-accent transition">
                </div>
            </div>

            <p id="gps-status" class="text-[11px] text-muted flex items-center gap-1.5">
                <span class="material-symbols-rounded text-xs text-accent">info</span>
                <span>Klik tombol di atas untuk mengisi koordinat akurat dari GPS smartphone Anda.</span>
            </p>
        </div>

        <!-- 3. Paket & ODP Lapangan -->
        <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="material-symbols-rounded text-accent text-lg">lan</span>
                <span>3. Paket & Penempatan Tiang ODP</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Router MikroTik Gateway <span class="text-accent">*</span>
                    </label>
                    <select name="router_id" required class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-accent transition">
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ old('router_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->host }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Paket Kecepatan Internet <span class="text-accent">*</span>
                    </label>
                    <select name="package_id" required class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-accent transition">
                        <option value="">-- Pilih Paket --</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name }} &bull; {{ $pkg->speed_rate ?? 'Uncapped' }} &bull; Rp {{ number_format($pkg->price, 0, ',', '.') }}/bln
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ODP Dropdown -->
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-white mb-1.5">
                        Titik Kotak ODP Fiber Optik
                    </label>
                    <select name="odp_id" id="odp_select" onchange="loadOdpPorts(this.value)" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-accent transition">
                        <option value="">-- Pilih ODP Terdekat --</option>
                        @foreach($odps as $odp)
                            <option value="{{ $odp->id }}" {{ old('odp_id') == $odp->id ? 'selected' : '' }}>
                                {{ $odp->name }} ({{ $odp->code }}) &bull; {{ $odp->address ?? 'Lokasi ODP' }} (Kapasitas: {{ $odp->total_ports ?? 8 }} Port)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Visual Port Selector Grid -->
                <div class="sm:col-span-2 space-y-2" id="odp-port-section" style="display: none;">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-white flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-sm text-accent">hub</span>
                            <span>Pilih Port ODP:</span>
                        </span>
                        <span id="odp-port-summary" class="text-muted text-[11px]">Memuat data port...</span>
                    </div>

                    <!-- Port visual pills -->
                    <div id="odp-ports-grid" class="grid grid-cols-4 sm:grid-cols-8 gap-2"></div>
                    <input type="hidden" name="odp_port" id="input_odp_port" value="{{ old('odp_port') }}">
                </div>
            </div>
        </div>

        <!-- 4. Kredensial PPPoE & Pengukuran OPM -->
        <div class="rounded-2xl bg-surface border border-border p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-accent text-lg">vpn_key</span>
                    <span>4. Kredensial PPPoE & Redaman OPM</span>
                </h2>

                <button type="button" onclick="generateCredentials()" class="text-xs font-semibold text-accent hover:underline flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">autorenew</span>
                    <span>Generate Otomatis</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Username PPPoE MikroTik <span class="text-accent">*</span>
                    </label>
                    <input type="text" name="pppoe_username" id="input_pppoe_user" value="{{ old('pppoe_username') }}" required placeholder="cth: budi_01" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted font-mono focus:outline-none focus:border-accent transition">
                </div>

                <div>
                    <label class="block font-semibold text-white mb-1.5">
                        Password PPPoE MikroTik <span class="text-accent">*</span>
                    </label>
                    <input type="text" name="pppoe_password" id="input_pppoe_pass" value="{{ old('pppoe_password') }}" required placeholder="cth: pass123" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted font-mono focus:outline-none focus:border-accent transition">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-white mb-1.5">
                        Hasil Ukur Redaman Fiber Optik (OPM)
                    </label>
                    <div class="relative max-w-xs">
                        <input type="text" name="optical_power_dbm" value="{{ old('optical_power_dbm') }}" placeholder="cth: -19.5" class="w-full bg-bg border border-border rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-muted font-mono focus:outline-none focus:border-accent transition">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-muted font-bold">dBm</span>
                    </div>
                    <p class="text-[10px] text-muted mt-1">Standar redaman layak: -15 s/d -24 dBm.</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" onclick="return confirm('Proses pasang baru ini sekarang? Akun PPPoE akan langsung disinkronkan ke router MikroTik.')" class="w-full inline-flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-sm shadow-xl shadow-accent/25 transition">
                <span class="material-symbols-rounded text-xl">rocket_launch</span>
                <span>Simpan, Aktifkan Layanan & Sync MikroTik</span>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    // GPS Geolocation Handler
    function getCurrentGpsLocation() {
        const icon = document.getElementById('gps-icon');
        const label = document.getElementById('gps-btn-label');
        const status = document.getElementById('gps-status');

        if (!navigator.geolocation) {
            alert('Browser atau perangkat Anda tidak mendukung geolokasi GPS.');
            return;
        }

        icon.classList.add('animate-spin');
        label.innerText = 'Mengunci Posisi...';
        status.innerHTML = `<span class="text-warn">Sedang mendeteksi satelit GPS...</span>`;

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                icon.classList.remove('animate-spin');
                label.innerText = 'GPS Terkunci ✔';

                const lat = pos.coords.latitude.toFixed(6);
                const lng = pos.coords.longitude.toFixed(6);
                const acc = Math.round(pos.coords.accuracy);

                document.getElementById('input_latitude').value = lat;
                document.getElementById('input_longitude').value = lng;

                status.innerHTML = `<span class="text-accent font-semibold">✔ Posisi GPS Terkunci: ${lat}, ${lng} (Akurasi: ±${acc}m)</span>`;
            },
            (err) => {
                icon.classList.remove('animate-spin');
                label.innerText = 'Ambil GPS Saya';
                status.innerHTML = `<span class="text-danger">Gagal mengambil GPS: ${err.message}. Pastikan izin lokasi aktif.</span>`;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Auto-generate Username & Password
    function generateCredentials() {
        const nameInput = document.getElementById('cust_name').value.trim();
        let base = 'user';
        if (nameInput) {
            base = nameInput.toLowerCase().replace(/[^a-z0-9]/g, '').substring(0, 8);
            if (base.length < 3) base = 'user';
        }
        const randNum = Math.floor(100 + Math.random() * 900);
        const pass = Math.random().toString(36).substring(2, 8);

        document.getElementById('input_pppoe_user').value = `${base}_${randNum}`;
        document.getElementById('input_pppoe_pass').value = pass;
    }

    // Dynamic ODP Ports Fetcher
    function loadOdpPorts(odpId) {
        const section = document.getElementById('odp-port-section');
        const summary = document.getElementById('odp-port-summary');
        const grid = document.getElementById('odp-ports-grid');
        const inputPort = document.getElementById('input_odp_port');

        if (!odpId) {
            section.style.display = 'none';
            inputPort.value = '';
            return;
        }

        section.style.display = 'block';
        summary.innerText = 'Memuat ketersediaan port...';
        grid.innerHTML = '<div class="col-span-full text-center py-2 text-muted text-xs">Mengambil data port...</div>';

        fetch(`/teknisi/ajax/odp-ports/${odpId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    summary.innerText = 'Gagal memuat port ODP.';
                    return;
                }

                summary.innerHTML = `<strong class="text-accent">${data.available_count}</strong> dari ${data.total_ports} port kosong (${data.used_count} terisi)`;
                grid.innerHTML = '';

                data.ports.forEach(item => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `p-2.5 rounded-xl text-center border font-mono text-xs font-bold transition flex flex-col items-center justify-center gap-0.5 ${
                        item.is_used 
                            ? 'bg-surface2/50 border-danger/30 text-danger/50 cursor-not-allowed opacity-60' 
                            : 'bg-surface2 border-border text-white hover:border-accent hover:bg-surface2'
                    }`;

                    btn.innerHTML = `
                        <span>P-${item.port}</span>
                        <span class="text-[9px] ${item.is_used ? 'text-danger' : 'text-accent font-normal'}">
                            ${item.is_used ? 'Terisi' : 'Kosong'}
                        </span>
                    `;

                    if (!item.is_used) {
                        btn.onclick = () => selectOdpPort(item.port, btn);
                        // Auto-select if matches old value
                        if (inputPort.value == item.port) {
                            selectOdpPort(item.port, btn);
                        }
                    } else {
                        btn.disabled = true;
                    }

                    grid.appendChild(btn);
                });
            })
            .catch(err => {
                summary.innerHTML = `<span class="text-danger">Error: ${err.message}</span>`;
            });
    }

    function selectOdpPort(portNumber, activeBtn) {
        document.getElementById('input_odp_port').value = portNumber;
        const grid = document.getElementById('odp-ports-grid');
        const buttons = grid.querySelectorAll('button:not(:disabled)');

        buttons.forEach(btn => {
            btn.classList.remove('bg-accent', 'text-ink', 'border-accent');
            btn.classList.add('bg-surface2', 'border-border', 'text-white');
        });

        activeBtn.classList.remove('bg-surface2', 'border-border', 'text-white');
        activeBtn.classList.add('bg-accent', 'text-ink', 'border-accent');
    }

    // Initialize port selector if old odp_id is set
    document.addEventListener('DOMContentLoaded', () => {
        const selectedOdp = document.getElementById('odp_select').value;
        if (selectedOdp) {
            loadOdpPorts(selectedOdp);
        }
    });
</script>
@endpush
