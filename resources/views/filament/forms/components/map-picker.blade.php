@php
    $office = \App\Models\IspSetting::getOfficeLocation();
    $officeLat = (float) ($office['lat'] ?? -6.2087634);
    $officeLng = (float) ($office['lng'] ?? 106.845599);
@endphp

<div class="w-full" x-data="ispMapPickerComponent($wire, {{ $officeLat }}, {{ $officeLng }})">
    <!-- Assets Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Trigger Bar in Form -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-blue-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                🛰️
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                    Pemilih Titik Lokasi Peta Satelit
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    <span x-show="lat && lng">
                        Koordinat aktif: <strong class="text-emerald-600 dark:text-emerald-400" x-text="lat + ', ' + lng"></strong>
                    </span>
                    <span x-show="!lat || !lng" class="text-amber-600 dark:text-amber-400">
                        Belum ada koordinat. Klik tombol di kanan untuk memilih di peta satelit.
                    </span>
                </div>
            </div>
        </div>

        <button type="button" @click="openMapModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow transition transform active:scale-95">
            <span>🗺️</span>
            <span>Buka Dialog Peta Satelit</span>
        </button>
    </div>

    <!-- Modal Dialog Popup -->
    <div x-show="openModal" 
         x-cloak
         style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);"
         class="flex items-center justify-center p-3 sm:p-6"
         @keydown.escape.window="closeMapModal()">
        
        <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200 dark:border-gray-700 max-h-[92vh]"
             @click.away="closeMapModal()">
            
            <!-- Dialog Header -->
            <div class="flex items-center justify-between px-5 py-3.5 bg-gray-900 text-white">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛰️</span>
                    <div>
                        <h3 class="font-bold text-sm sm:text-base">Dialog Peta Satelit - Titik Lokasi</h3>
                        <p class="text-[11px] text-gray-300">Geser pin merah atau klik peta tepat pada posisi atap rumah/kantor pelanggan atau ODP</p>
                    </div>
                </div>
                <button type="button" @click="closeMapModal()" class="text-gray-400 hover:text-white text-xl font-bold p-1">
                    ✕
                </button>
            </div>

            <!-- Toolbar in Dialog -->
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 text-xs">
                <div class="flex items-center gap-2">
                    <button type="button" @click="useCurrentGps()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-medium flex items-center gap-1 shadow-sm transition">
                        📍 Lokasi GPS Saya
                    </button>
                    <button type="button" @click="centerToOffice()" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md font-medium transition">
                        🏢 Ke Titik Kantor/NOC
                    </button>
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-300 font-mono">
                    Lat: <span class="font-bold text-blue-600 dark:text-blue-400" x-text="tempLat"></span> | 
                    Lng: <span class="font-bold text-blue-600 dark:text-blue-400" x-text="tempLng"></span>
                </div>
            </div>

            <!-- Leaflet Container -->
            <div class="relative w-full h-[450px] bg-gray-200">
                <div id="dialog-leaflet-container" class="w-full h-full"></div>
            </div>

            <!-- Dialog Footer -->
            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">
                    💡 <em>Tip: Gunakan tampilan satelit Google untuk melihat kontur atap dan tiang ODP di dekatnya.</em>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" @click="closeMapModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold transition">
                        Batal
                    </button>
                    <button type="button" @click="applyLocation()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-md transition flex items-center gap-1.5">
                        <span>✅</span>
                        <span>Terapkan Koordinat Ini</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
if (typeof window.ispMapPickerComponent === 'undefined') {
    window.ispMapPickerComponent = function(wire, officeLat, officeLng) {
        return {
            openModal: false,
            lat: null,
            lng: null,
            tempLat: null,
            tempLng: null,
            officeLat: officeLat || -6.2087634,
            officeLng: officeLng || 106.845599,
            map: null,
            marker: null,

            init() {
                try {
                    this.lat = wire ? wire.entangle('data.latitude') : null;
                    this.lng = wire ? wire.entangle('data.longitude') : null;
                } catch (e) {
                    // Fallback to DOM input values if entangle fails
                    const latEl = document.querySelector('[name=\"latitude\"], [id*=\"latitude\"]');
                    const lngEl = document.querySelector('[name=\"longitude\"], [id*=\"longitude\"]');
                    if (latEl) this.lat = latEl.value;
                    if (lngEl) this.lng = lngEl.value;
                }

                if (!this.lat || isNaN(parseFloat(this.lat))) {
                    this.tempLat = this.officeLat;
                } else {
                    this.tempLat = parseFloat(this.lat);
                }

                if (!this.lng || isNaN(parseFloat(this.lng))) {
                    this.tempLng = this.officeLng;
                } else {
                    this.tempLng = parseFloat(this.lng);
                }
            },

            openMapModal() {
                this.openModal = true;
                this.tempLat = (this.lat && !isNaN(parseFloat(this.lat))) ? parseFloat(this.lat) : this.officeLat;
                this.tempLng = (this.lng && !isNaN(parseFloat(this.lng))) ? parseFloat(this.lng) : this.officeLng;

                this.$nextTick(() => {
                    setTimeout(() => {
                        this.initLeaflet();
                    }, 100);
                });
            },

            closeMapModal() {
                this.openModal = false;
            },

            initLeaflet() {
                const container = document.getElementById('dialog-leaflet-container');
                if (!container || typeof L === 'undefined') return;

                if (this.map) {
                    this.map.remove();
                    this.map = null;
                }

                const centerLat = this.tempLat || this.officeLat;
                const centerLng = this.tempLng || this.officeLng;

                const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    attribution: 'Google Satellite'
                });

                const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: 'OpenStreetMap'
                });

                this.map = L.map(container, {
                    center: [centerLat, centerLng],
                    zoom: 17,
                    layers: [googleHybrid]
                });

                L.control.layers({
                    '🛰️ Satelit Hybrid (Google)': googleHybrid,
                    '🗺️ Peta Jalan (OSM)': osm
                }, null, { position: 'topright' }).addTo(this.map);

                const customPin = L.divIcon({
                    className: 'picker-pin',
                    html: '<div style="background:#ef4444; width:34px; height:34px; border-radius:50%; border:3px solid #fff; box-shadow:0 4px 12px rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">📍</div>',
                    iconSize: [34, 34],
                    iconAnchor: [17, 34]
                });

                this.marker = L.marker([centerLat, centerLng], {
                    draggable: true,
                    icon: customPin
                }).addTo(this.map);

                this.marker.bindTooltip('Geser pin ini tepat di lokasi tujuan', {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -30]
                }).openTooltip();

                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.tempLat = parseFloat(pos.lat.toFixed(7));
                    this.tempLng = parseFloat(pos.lng.toFixed(7));
                });

                this.map.on('click', (e) => {
                    this.tempLat = parseFloat(e.latlng.lat.toFixed(7));
                    this.tempLng = parseFloat(e.latlng.lng.toFixed(7));
                    this.marker.setLatLng(e.latlng);
                });

                setTimeout(() => {
                    if (this.map) this.map.invalidateSize();
                }, 200);
            },

            useCurrentGps() {
                if (!navigator.geolocation) {
                    alert('Browser atau perangkat Anda tidak mendukung GPS');
                    return;
                }

                navigator.geolocation.getCurrentPosition((pos) => {
                    const cLat = parseFloat(pos.coords.latitude.toFixed(7));
                    const cLng = parseFloat(pos.coords.longitude.toFixed(7));
                    this.tempLat = cLat;
                    this.tempLng = cLng;
                    if (this.marker) this.marker.setLatLng([cLat, cLng]);
                    if (this.map) this.map.setView([cLat, cLng], 18);
                }, (err) => {
                    alert('Gagal mendeteksi lokasi GPS: ' + err.message);
                }, { enableHighAccuracy: true });
            },

            centerToOffice() {
                this.tempLat = this.officeLat;
                this.tempLng = this.officeLng;
                if (this.marker) this.marker.setLatLng([this.officeLat, this.officeLng]);
                if (this.map) this.map.setView([this.officeLat, this.officeLng], 17);
            },

            applyLocation() {
                this.lat = this.tempLat;
                this.lng = this.tempLng;

                const latInputs = document.querySelectorAll('[name=\"latitude\"], [id*=\"latitude\"], [id=\"customer_lat_input\"]');
                const lngInputs = document.querySelectorAll('[name=\"longitude\"], [id*=\"longitude\"], [id=\"customer_lng_input\"]');
                
                latInputs.forEach(el => {
                    el.value = this.tempLat;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });

                lngInputs.forEach(el => {
                    el.value = this.tempLng;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });

                this.closeMapModal();
            }
        };
    };
}
</script>
