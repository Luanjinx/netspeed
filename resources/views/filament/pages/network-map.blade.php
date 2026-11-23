<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #network-map-container {
            height: calc(100vh - 230px);
            min-height: 580px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .map-legend-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .legend-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
        }
        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .custom-pin {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-popup .leaflet-popup-content-wrapper {
            background: var(--surface) !important;
            color: var(--text) !important;
            border-radius: 12px;
            padding: 6px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .custom-popup .leaflet-popup-tip {
            background: var(--surface) !important;
        }
        .cable-edit-box {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface);
            border: 1px solid #00e07e;
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.6);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            backdrop-filter: blur(8px);
        }
    </style>

    <div x-data="networkMapApp({
            officeLat: {{ (float) ($office['lat'] ?? -6.2087634) }},
            officeLng: {{ (float) ($office['lng'] ?? 106.845599) }},
            officeZoom: {{ (int) ($office['zoom'] ?? 16) }},
            officeName: @js($office['name'] ?? 'Kantor Pusat ISP'),
            mapDataUrl: @js(route('network.map.data')),
            saveCableUrl: @js(route('network.map.save-cable')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="initMap()"
        class="space-y-3"
    >
        <!-- Filter & Legend Bar -->
        <div class="map-legend-bar">
            <div class="flex flex-wrap items-center gap-4 text-xs font-medium">
                <span class="legend-pill"><span class="legend-dot" style="background:#2563eb;"></span> ODP Tiang</span>
                <span class="legend-pill"><span class="legend-dot" style="background:#16a34a;"></span> Pelanggan Aktif</span>
                <span class="legend-pill"><span class="legend-dot" style="background:#dc2626;"></span> Diisolir</span>
                <span class="legend-pill"><span class="legend-dot" style="background:#f59e0b;"></span> Pending</span>
                <span class="legend-pill"><span class="legend-dot" style="background:#9333ea;"></span> Pusat NOC</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('tech.map') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#00e07e] hover:bg-[#4deda4] text-black text-xs font-bold rounded-lg shadow-sm transition">
                    <span class="material-symbols-rounded text-sm">smartphone</span>
                    <span>Buka Peta Teknisi (Mobile)</span>
                </a>
            </div>
        </div>

        <!-- Map Container -->
        <div class="relative">
            <div id="network-map-container">
                <!-- Loading State Overlay -->
                <div x-show="loading" class="absolute inset-0 z-[500] flex flex-col items-center justify-center bg-[var(--surface)] text-[var(--muted)] gap-3">
                    <svg class="animate-spin h-8 w-8 text-[#00e07e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-xs font-semibold tracking-wide">Memuat Peta Satelit & Topologi Jaringan...</span>
                </div>
            </div>

            <!-- Cable Drawing Floating Banner -->
            <div x-show="isDrawingCable" x-cloak class="cable-edit-box">
                <span x-text="drawingStatus"></span>
                <button type="button" @click="saveCable()" class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 rounded-full font-bold text-xs text-white shadow">
                    <span class="material-symbols-rounded text-xs">save</span>
                    <span>Simpan Jalur</span>
                </button>
                <button type="button" @click="cancelCable()" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 rounded-full font-bold text-xs text-white shadow">✕ Batal</button>
            </div>
        </div>
    </div>

    <script>
        function networkMapApp(config) {
            return {
                officeLat: config.officeLat,
                officeLng: config.officeLng,
                officeZoom: config.officeZoom,
                officeName: config.officeName,
                mapDataUrl: config.mapDataUrl,
                saveCableUrl: config.saveCableUrl,
                csrfToken: config.csrfToken,

                loading: true,
                map: null,
                odpLayerGroup: null,
                customerLayerGroup: null,
                lineLayerGroup: null,
                mapData: null,

                isDrawingCable: false,
                drawingStatus: '',
                currentEditingCustomer: null,
                currentCablePoints: [],
                editPolyline: null,

                initMap() {
                    window.__networkMapApp = this;
                    this.ensureLeaflet(() => {
                        this.setupMap();
                    });
                },

                ensureLeaflet(callback) {
                    if (window.L && typeof window.L.map === 'function') {
                        callback();
                        return;
                    }

                    // Dynamically load Leaflet script if not present
                    let script = document.getElementById('leaflet-js-cdn');
                    if (!script) {
                        script = document.createElement('script');
                        script.id = 'leaflet-js-cdn';
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        document.head.appendChild(script);
                    }

                    let loaded = false;
                    script.onload = () => {
                        if (!loaded) {
                            loaded = true;
                            callback();
                        }
                    };

                    // Fallback polling for fast cache
                    const timer = setInterval(() => {
                        if (window.L && typeof window.L.map === 'function') {
                            clearInterval(timer);
                            if (!loaded) {
                                loaded = true;
                                callback();
                            }
                        }
                    }, 100);
                },

                setupMap() {
                    const container = document.getElementById('network-map-container');
                    if (!container) return;

                    // Clean up prior Leaflet instance if present
                    if (this.map) {
                        try { this.map.remove(); } catch(e) {}
                        this.map = null;
                    }
                    if (container._leaflet_id) {
                        container._leaflet_id = null;
                    }

                    // 1. Basemap tile layers
                    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        attribution: '&copy; Google Satellite'
                    });

                    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    });

                    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 19,
                        attribution: '&copy; ESRI World Imagery'
                    });

                    // 2. Initialize Map instance
                    this.map = L.map(container, {
                        center: [this.officeLat, this.officeLng],
                        zoom: this.officeZoom,
                        layers: [googleHybrid]
                    });

                    // 3. Layer controls
                    this.odpLayerGroup = L.layerGroup().addTo(this.map);
                    this.customerLayerGroup = L.layerGroup().addTo(this.map);
                    this.lineLayerGroup = L.layerGroup().addTo(this.map);

                    L.control.layers({
                        "Satelit Hybrid (Google)": googleHybrid,
                        "Satelit ESRI": satelliteLayer,
                        "OpenStreetMap": osmLayer
                    }, {
                        "Tiang ODP": this.odpLayerGroup,
                        "Pelanggan": this.customerLayerGroup,
                        "Jalur Kabel FO": this.lineLayerGroup
                    }, { position: 'topright' }).addTo(this.map);

                    // 4. Add Office Marker (NOC / Server Pusat)
                    const officeIcon = this.createIcon('#9333ea', '<span class="material-symbols-rounded" style="font-size:18px;">domain</span>', 38);
                    L.marker([this.officeLat, this.officeLng], { icon: officeIcon })
                        .addTo(this.map)
                        .bindPopup(`<strong>${this.officeName}</strong><br><small>Titik Acuan NOC / Server Pusat</small>`, {
                            className: 'custom-popup'
                        });

                    // 5. Cable click handler on map
                    this.map.on('click', (e) => {
                        if (!this.isDrawingCable || !this.currentEditingCustomer) return;
                        this.currentCablePoints.push([e.latlng.lat, e.latlng.lng]);
                        if (this.editPolyline) {
                            this.editPolyline.setLatLngs(this.currentCablePoints);
                        }
                    });

                    // 6. Invalidate size after layout stabilization
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 250);

                    // 7. Fetch data from backend
                    this.loadMapData();

                    // 8. Alpine cleanup hook on SPA unmount
                    this.$cleanup(() => {
                        if (this.map) {
                            try { this.map.remove(); } catch(e) {}
                            this.map = null;
                        }
                    });
                },

                createIcon(color, text, size = 32) {
                    return L.divIcon({
                        className: 'custom-pin',
                        html: `<div style="background:${color}; width:${size}px; height:${size}px; border-radius:50%; border:2.5px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; color:#fff; font-size:${size*0.4}px; font-weight:bold;">${text}</div>`,
                        iconSize: [size, size],
                        iconAnchor: [size/2, size/2],
                        popupAnchor: [0, -size/2]
                    });
                },

                loadMapData() {
                    this.loading = true;
                    fetch(this.mapDataUrl)
                        .then(res => res.json())
                        .then(data => {
                            this.mapData = data;
                            this.renderMapElements(data);
                            this.loading = false;
                            setTimeout(() => {
                                if (this.map) this.map.invalidateSize();
                            }, 100);
                        })
                        .catch(err => {
                            console.error('Failed to load map data:', err);
                            this.loading = false;
                        });
                },

                renderMapElements(data) {
                    if (!this.map) return;

                    this.odpLayerGroup.clearLayers();
                    this.customerLayerGroup.clearLayers();
                    this.lineLayerGroup.clearLayers();

                    // Render ODPs
                    const odpDict = {};
                    (data.odps || []).forEach(odp => {
                        odpDict[odp.id] = odp;
                        const odpIcon = this.createIcon('#2563eb', '<span class="material-symbols-rounded" style="font-size:16px;">inventory_2</span>', 34);
                        const odpMarker = L.marker([odp.lat, odp.lng], { icon: odpIcon }).addTo(this.odpLayerGroup);

                        const popupHtml = `
                            <div style="font-size:13px; line-height:1.4;">
                                <div style="font-weight:bold; color:#3b82f6; font-size:14px;">${odp.name} (${odp.code})</div>
                                <div style="color:#94a3b8; font-size:11px;">${odp.address || 'Tiang Distribusi'}</div>
                                <hr style="margin:6px 0; border:none; border-top:1px solid #334155;">
                                <div>Kapasitas: <strong>${odp.occupied_ports} / ${odp.total_ports} Port Terpakai</strong></div>
                                <div>Splitter: <span style="background:#1e293b; padding:2px 6px; border-radius:4px; color:#60a5fa;">${odp.splitter_ratio}</span></div>
                            </div>
                        `;
                        odpMarker.bindPopup(popupHtml, { className: 'custom-popup' });
                    });

                    // Render Customers & Cable Lines
                    (data.customers || []).forEach(cust => {
                        const statusColor = cust.status === 'active' ? '#16a34a' : (cust.status === 'isolated' ? '#dc2626' : '#f59e0b');
                        const custIcon = this.createIcon(statusColor, '<span class="material-symbols-rounded" style="font-size:15px;">person</span>', 30);
                        const custMarker = L.marker([cust.lat, cust.lng], { icon: custIcon }).addTo(this.customerLayerGroup);

                        // Cable Route
                        let cableCoords = [];
                        if (cust.cable_path && cust.cable_path.length > 0) {
                            cableCoords = cust.cable_path;
                        } else if (cust.odp_lat && cust.odp_lng) {
                            cableCoords = [[cust.odp_lat, cust.odp_lng], [cust.lat, cust.lng]];
                        }

                        if (cableCoords.length > 0) {
                            const line = L.polyline(cableCoords, {
                                color: cust.status === 'active' ? '#10b981' : '#f87171',
                                weight: 3.5,
                                opacity: 0.85,
                                dashArray: cust.cable_path ? null : '5, 5'
                            }).addTo(this.lineLayerGroup);

                            line.bindTooltip(`${cust.name} ➔ ${cust.odp_name || 'ODP'}`, { sticky: true });
                        }

                        // Popup Content
                        const popupHtml = `
                            <div style="min-width:240px; font-size:13px; line-height:1.4;">
                                <div style="font-weight:bold; font-size:14px; color:#f8fafc;">${cust.name}</div>
                                <div style="color:#94a3b8; font-size:11px;">${cust.customer_number} • ${cust.address || '-'}</div>
                                <hr style="margin:6px 0; border:none; border-top:1px solid #334155;">
                                <div>Paket: <strong>${cust.package_name} (${cust.speed})</strong></div>
                                <div>Status: <strong style="color:${statusColor}">${cust.status.toUpperCase()}</strong> (${cust.due_date})</div>
                                <div>Koneksi ODP: <strong>${cust.odp_name || '-'} (Port ${cust.odp_port || '-'})</strong></div>
                                
                                <!-- Real-time Traffic Box -->
                                <div id="traffic-box-${cust.id}" style="margin-top:8px; padding:6px; background:#1e293b; border-radius:6px; font-size:11px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="color:#cbd5e1;">Trafik PPPoE:</span>
                                        <button onclick="window.checkMapTraffic(${cust.id})" style="background:#2563eb; color:#fff; border:none; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:600; display:inline-flex; align-items:center; gap:2px;">
                                            <span class="material-symbols-rounded" style="font-size:12px;">bolt</span> Cek Live
                                        </button>
                                    </div>
                                    <div id="traffic-content-${cust.id}" style="margin-top:4px; font-family:monospace; color:#94a3b8;">Belum dicek</div>
                                </div>

                                <div style="display:flex; gap:6px; margin-top:10px;">
                                    <button onclick="window.startMapCableEdit(${cust.id})" style="flex:1; background:#0f172a; border:1px solid #334155; color:#fff; padding:5px; border-radius:4px; font-size:11px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:13px;">edit</span> Edit Jalur Kabel
                                    </button>
                                    <a href="https://wa.me/${(cust.phone || '').replace(/^0/, '62')}" target="_blank" style="background:#22c55e; color:#fff; text-decoration:none; padding:5px 8px; border-radius:4px; font-size:11px; display:inline-flex; align-items:center; gap:3px;">
                                        <span class="material-symbols-rounded" style="font-size:13px;">chat</span> WA
                                    </a>
                                </div>
                            </div>
                        `;
                        custMarker.bindPopup(popupHtml, { className: 'custom-popup' });
                    });
                },

                startCableEdit(customerId) {
                    if (!this.mapData || !this.map) return;
                    this.map.closePopup();
                    const cust = (this.mapData.customers || []).find(c => c.id === customerId);
                    if (!cust) return;

                    this.currentEditingCustomer = cust;
                    this.currentCablePoints = cust.cable_path ? [...cust.cable_path] : [];

                    if (this.currentCablePoints.length === 0 && cust.odp_lat && cust.odp_lng) {
                        this.currentCablePoints.push([cust.odp_lat, cust.odp_lng]);
                        this.currentCablePoints.push([cust.lat, cust.lng]);
                    }

                    if (this.editPolyline) {
                        this.map.removeLayer(this.editPolyline);
                    }
                    this.editPolyline = L.polyline(this.currentCablePoints, { color: '#f59e0b', weight: 4 }).addTo(this.map);

                    this.drawingStatus = `Menggambar Jalur Kabel: ${cust.name}. Klik peta untuk menambah titik belokan.`;
                    this.isDrawingCable = true;
                },

                saveCable() {
                    if (!this.currentEditingCustomer) return;

                    fetch(this.saveCableUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            customer_id: this.currentEditingCustomer.id,
                            cable_path: this.currentCablePoints
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        alert('Jalur kabel fiber optik berhasil disimpan!');
                        this.currentEditingCustomer.cable_path = this.currentCablePoints;
                        this.cancelCable();
                        this.renderMapElements(this.mapData);
                    })
                    .catch(err => {
                        alert('Gagal menyimpan jalur kabel: ' + err.message);
                    });
                },

                cancelCable() {
                    if (this.editPolyline && this.map) {
                        this.map.removeLayer(this.editPolyline);
                    }
                    this.editPolyline = null;
                    this.currentEditingCustomer = null;
                    this.currentCablePoints = [];
                    this.isDrawingCable = false;
                }
            };
        }

        // Global functions for Leaflet popup HTML actions
        window.checkMapTraffic = function(customerId) {
            const box = document.getElementById(`traffic-content-${customerId}`);
            if (!box) return;
            box.innerHTML = '<em>Mengambil data dari MikroTik...</em>';

            fetch(`/admin/map-traffic/${customerId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.online) {
                        box.innerHTML = `
                            <div style="color:#10b981; font-weight:bold;">ONLINE (${data.uptime})</div>
                            <div>RX (Download): <strong>${data.rx_rate}</strong></div>
                            <div>TX (Upload): <strong>${data.tx_rate}</strong></div>
                            <div>IP: ${data.ip_address}</div>
                        `;
                    } else {
                        box.innerHTML = `<span style="color:#ef4444;">OFFLINE</span> • ${data.message || 'Tidak aktif'}`;
                    }
                })
                .catch(() => {
                    box.innerHTML = '<span style="color:#ef4444;">Gagal menghubungi server</span>';
                });
        };

        window.startMapCableEdit = function(customerId) {
            if (window.__networkMapApp) {
                window.__networkMapApp.startCableEdit(customerId);
            }
        };
    </script>
</x-filament-panels::page>