<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        #network-map-container {
            height: calc(100vh - 240px);
            min-height: 580px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        .map-control-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            padding: 8px 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .dark .map-control-bar {
            background: rgba(30, 41, 59, 0.95);
            color: #f8fafc;
        }
        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .cable-edit-box {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }
    </style>

    <div class="space-y-3">
        <!-- Filter & Legend Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4 text-xs font-medium">
                <span class="flex items-center"><span class="legend-dot" style="background:#2563eb;"></span> ODP Tiang</span>
                <span class="flex items-center"><span class="legend-dot" style="background:#16a34a;"></span> Pelanggan Aktif</span>
                <span class="flex items-center"><span class="legend-dot" style="background:#dc2626;"></span> Diisolir</span>
                <span class="flex items-center"><span class="legend-dot" style="background:#f59e0b;"></span> Pending</span>
                <span class="flex items-center"><span class="legend-dot" style="background:#9333ea;"></span> Pusat NOC</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('tech.map') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <span class="material-symbols-rounded text-sm">smartphone</span>
                    <span>Buka Peta Teknisi (Mobile)</span>
                </a>
            </div>
        </div>

        <!-- Map Container -->
        <div class="relative">
            <div id="network-map-container"></div>

            <!-- Cable Drawing Floating Banner -->
            <div id="cable-edit-banner" class="cable-edit-box">
                <span id="cable-edit-status">Mode Gambar Kabel: Klik di peta untuk menambah titik belokan kabel FO</span>
                <button type="button" id="btn-save-cable" class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 rounded-full font-bold text-xs text-white">
                    <span class="material-symbols-rounded text-xs">save</span>
                    <span>Simpan Jalur</span>
                </button>
                <button type="button" id="btn-cancel-cable" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 rounded-full font-bold text-xs text-white">✕ Batal</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const officeLat = {{ $office['lat'] ?? -6.2087634 }};
            const officeLng = {{ $office['lng'] ?? 106.845599 }};
            const officeZoom = {{ $office['zoom'] ?? 16 }};
            const officeName = "{{ $office['name'] ?? 'Kantor Pusat ISP' }}";

            // 1. Basemaps
            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            });

            const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: '&copy; ESRI World Imagery'
            });

            const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                attribution: '&copy; Google Satellite'
            });

            // Initialize Map
            const map = L.map('network-map-container', {
                center: [officeLat, officeLng],
                zoom: officeZoom,
                layers: [googleHybrid] // Default satellite hybrid
            });

            // Layer Control
            const baseMaps = {
                "Satelit Hybrid (Google)": googleHybrid,
                "Satelit ESRI": satelliteLayer,
                "OpenStreetMap": osmLayer
            };

            const odpLayerGroup = L.layerGroup().addTo(map);
            const customerLayerGroup = L.layerGroup().addTo(map);
            const lineLayerGroup = L.layerGroup().addTo(map);

            L.control.layers(baseMaps, {
                "Tiang ODP": odpLayerGroup,
                "Pelanggan": customerLayerGroup,
                "Jalur Kabel FO": lineLayerGroup
            }, { position: 'topright' }).addTo(map);

            // 2. Custom Icon Generators
            function createIcon(color, text, size = 32) {
                return L.divIcon({
                    className: 'custom-pin',
                    html: `<div style="background:${color}; width:${size}px; height:${size}px; border-radius:50%; border:2.5px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; color:#fff; font-size:${size*0.4}px; font-weight:bold;">${text}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size/2, size/2],
                    popupAnchor: [0, -size/2]
                });
            }

            // Office Marker
            const officeIcon = createIcon('#9333ea', '<span class="material-symbols-rounded" style="font-size:18px;">domain</span>', 38);
            L.marker([officeLat, officeLng], { icon: officeIcon }).addTo(map)
                .bindPopup(`<strong>${officeName}</strong><br><small>Titik Acuan NOC / Server Pusat</small>`);

            let mapData = null;
            let currentEditingCustomer = null;
            let currentCablePoints = [];
            let editPolyline = null;

            // 3. Load Map Data via AJAX
            fetch('{{ route("network.map.data") }}')
                .then(res => res.json())
                .then(data => {
                    mapData = data;
                    renderMapElements(data);
                });

            function renderMapElements(data) {
                odpLayerGroup.clearLayers();
                customerLayerGroup.clearLayers();
                lineLayerGroup.clearLayers();

                // Render ODPs
                const odpDict = {};
                data.odps.forEach(odp => {
                    odpDict[odp.id] = odp;
                    const odpIcon = createIcon('#2563eb', '<span class="material-symbols-rounded" style="font-size:16px;">inventory_2</span>', 34);
                    const odpMarker = L.marker([odp.lat, odp.lng], { icon: odpIcon }).addTo(odpLayerGroup);
                    
                    const popupHtml = `
                        <div style="font-size:13px; line-height:1.4;">
                            <div style="font-weight:bold; color:#1d4ed8; font-size:14px;">${odp.name} (${odp.code})</div>
                            <div style="color:#64748b; font-size:11px;">${odp.address || 'Tiang Distribusi'}</div>
                            <hr style="margin:6px 0; border:none; border-top:1px solid #e2e8f0;">
                            <div>Kapasitas: <strong>${odp.occupied_ports} / ${odp.total_ports} Port Terpakai</strong></div>
                            <div>Splitter: <span style="background:#eff6ff; padding:2px 6px; border-radius:4px; color:#1e40af;">${odp.splitter_ratio}</span></div>
                        </div>
                    `;
                    odpMarker.bindPopup(popupHtml);
                });

                // Render Customers & Cable Lines
                data.customers.forEach(cust => {
                    const statusColor = cust.status === 'active' ? '#16a34a' : (cust.status === 'isolated' ? '#dc2626' : '#f59e0b');
                    const custIcon = createIcon(statusColor, '<span class="material-symbols-rounded" style="font-size:15px;">person</span>', 30);
                    const custMarker = L.marker([cust.lat, cust.lng], { icon: custIcon }).addTo(customerLayerGroup);

                    // Render Cable Route
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
                        }).addTo(lineLayerGroup);

                        line.bindTooltip(`${cust.name} ➔ ${cust.odp_name || 'ODP'}`, { sticky: true });
                    }

                    // Popup Content
                    const popupHtml = `
                        <div style="min-width:240px; font-size:13px; line-height:1.4;">
                            <div style="font-weight:bold; font-size:14px; color:#0f172a;">${cust.name}</div>
                            <div style="color:#64748b; font-size:11px;">${cust.customer_number} • ${cust.address || '-'}</div>
                            <hr style="margin:6px 0; border:none; border-top:1px solid #e2e8f0;">
                            <div>Paket: <strong>${cust.package_name} (${cust.speed})</strong></div>
                            <div>Status: <strong style="color:${statusColor}">${cust.status.toUpperCase()}</strong> (${cust.due_date})</div>
                            <div>Koneksi ODP: <strong>${cust.odp_name || '-'} (Port ${cust.odp_port || '-'})</strong></div>
                            
                            <!-- Real-time Traffic Box -->
                            <div id="traffic-box-${cust.id}" style="margin-top:8px; padding:6px; background:#f1f5f9; border-radius:6px; font-size:11px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span>Trafik PPPoE:</span>
                                    <button onclick="checkTraffic(${cust.id})" style="background:#2563eb; color:#fff; border:none; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:10px; font-weight:600; display:inline-flex; align-items:center; gap:2px;"><span class="material-symbols-rounded" style="font-size:12px;">bolt</span> Cek Live</button>
                                </div>
                                <div id="traffic-content-${cust.id}" style="margin-top:4px; font-family:monospace; color:#334155;">Belum dicek</div>
                            </div>

                            <div style="display:flex; gap:6px; margin-top:10px;">
                                <button onclick="startCableEdit(${cust.id})" style="flex:1; background:#0f172a; color:#fff; border:none; padding:5px; border-radius:4px; font-size:11px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:4px;"><span class="material-symbols-rounded" style="font-size:13px;">edit</span> Edit Jalur Kabel</button>
                                <a href="https://wa.me/${cust.phone.replace(/^0/, '62')}" target="_blank" style="background:#22c55e; color:#fff; text-decoration:none; padding:5px 8px; border-radius:4px; font-size:11px; display:inline-flex; align-items:center; gap:3px;"><span class="material-symbols-rounded" style="font-size:13px;">chat</span> WA</a>
                            </div>
                        </div>
                    `;
                    custMarker.bindPopup(popupHtml);
                });
            }

            // 4. Check Real-Time MikroTik Traffic
            window.checkTraffic = function(customerId) {
                const box = document.getElementById(`traffic-content-${customerId}`);
                box.innerHTML = '<em>Mengambil data dari MikroTik...</em>';

                fetch(`/admin/map-traffic/${customerId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.online) {
                            box.innerHTML = `
                                <div style="color:#16a34a; font-weight:bold;">ONLINE (${data.uptime})</div>
                                <div>RX (Download): <strong>${data.rx_rate}</strong></div>
                                <div>TX (Upload): <strong>${data.tx_rate}</strong></div>
                                <div>IP: ${data.ip_address}</div>
                            `;
                        } else {
                            box.innerHTML = `<span style="color:#dc2626;">OFFLINE</span> • ${data.message}`;
                        }
                    })
                    .catch(() => {
                        box.innerHTML = '<span style="color:#dc2626;">Gagal menghubungi server</span>';
                    });
            };

            // 5. Cable Polyline Drawing / Editing Mode
            const editBanner = document.getElementById('cable-edit-banner');
            const btnSave = document.getElementById('btn-save-cable');
            const btnCancel = document.getElementById('btn-cancel-cable');

            window.startCableEdit = function(customerId) {
                map.closePopup();
                const cust = mapData.customers.find(c => c.id === customerId);
                if (!cust) return;

                currentEditingCustomer = cust;
                currentCablePoints = cust.cable_path ? [...cust.cable_path] : [];

                if (currentCablePoints.length === 0 && cust.odp_lat && cust.odp_lng) {
                    currentCablePoints.push([cust.odp_lat, cust.odp_lng]);
                    currentCablePoints.push([cust.lat, cust.lng]);
                }

                if (editPolyline) map.removeLayer(editPolyline);
                editPolyline = L.polyline(currentCablePoints, { color: '#f59e0b', weight: 4 }).addTo(map);

                editBanner.style.display = 'flex';
                document.getElementById('cable-edit-status').innerText = `Menggambar Jalur Kabel: ${cust.name}. Klik peta untuk menambah belokan kabel.`;
            };

            map.on('click', function(e) {
                if (!currentEditingCustomer) return;
                currentCablePoints.push([e.latlng.lat, e.latlng.lng]);
                if (editPolyline) {
                    editPolyline.setLatLngs(currentCablePoints);
                }
            });

            btnSave.onclick = function() {
                if (!currentEditingCustomer) return;

                fetch('{{ route("network.map.save-cable") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        customer_id: currentEditingCustomer.id,
                        cable_path: currentCablePoints
                    })
                })
                .then(res => res.json())
                .then(res => {
                    alert('Jalur kabel berhasil disimpan!');
                    currentEditingCustomer.cable_path = currentCablePoints;
                    exitCableEdit();
                    renderMapElements(mapData);
                });
            };

            btnCancel.onclick = function() {
                exitCableEdit();
            };

            function exitCableEdit() {
                if (editPolyline) map.removeLayer(editPolyline);
                editPolyline = null;
                currentEditingCustomer = null;
                currentCablePoints = [];
                editBanner.style.display = 'none';
            }
        });
    </script>
</x-filament-panels::page>
