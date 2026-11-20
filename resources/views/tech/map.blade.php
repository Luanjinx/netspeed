<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Peta Teknisi Lapangan - NetSpeed</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { width: 100%; height: 100%; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        #map { width: 100%; height: 100%; }
        
        /* Floating Top Bar */
        .top-bar {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            color: #fff;
            padding: 10px 14px;
            border-radius: 12px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .top-title { font-size: 14px; font-weight: bold; }
        .top-sub { font-size: 10px; color: #94a3b8; }

        /* Floating GPS Button */
        .gps-btn {
            position: absolute;
            bottom: 25px;
            right: 15px;
            background: #2563eb;
            color: #fff;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 15px rgba(37,99,235,0.5);
            border: 2px solid #fff;
            cursor: pointer;
            z-index: 1000;
            transition: transform 0.2s;
        }
        .gps-btn:active { transform: scale(0.92); }

        /* Popup Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 8px;
            font-size: 13px;
        }
        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            text-decoration: none;
            color: #fff;
            text-align: center;
            margin-top: 5px;
        }
        .btn-gmaps { background: #ea4335; }
        .btn-wa { background: #22c55e; }
        .btn-tel { background: #0284c7; }

        /* My Location pulsing dot */
        .my-loc-dot {
            width: 16px;
            height: 16px;
            background: #2563eb;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(37,99,235,0.8);
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div>
            <div class="top-title">📱 Peta Teknisi Lapangan</div>
            <div class="top-sub">Jalur Kabel ODP & Navigasi Pelanggan</div>
        </div>
        <div>
            <a href="/admin" style="color:#38bdf8; text-decoration:none; font-size:12px; font-weight:bold;">Dashboard ➔</a>
        </div>
    </div>

    <div id="map"></div>

    <div id="btn-gps" class="gps-btn" title="Cari Lokasi Saya">📍</div>

    <script>
        const officeLat = {{ $office['lat'] ?? -6.2087634 }};
        const officeLng = {{ $office['lng'] ?? 106.845599 }};
        const officeZoom = {{ $office['zoom'] ?? 16 }};

        // Layers
        const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Google Satellite'
        });
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'OpenStreetMap'
        });

        const map = L.map('map', {
            center: [officeLat, officeLng],
            zoom: officeZoom,
            layers: [googleHybrid],
            zoomControl: false
        });

        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        L.control.layers({
            "Satelit": googleHybrid,
            "Peta Jalan": osm
        }, null, { position: 'topright' }).addTo(map);

        function createPin(color, icon, size = 30) {
            return L.divIcon({
                className: 'tech-pin',
                html: `<div style="background:${color}; width:${size}px; height:${size}px; border-radius:50%; border:2px solid #fff; box-shadow:0 3px 6px rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; color:#fff; font-size:${size*0.42}px;">${icon}</div>`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2],
                popupAnchor: [0, -size/2]
            });
        }

        // Office Marker
        L.marker([officeLat, officeLng], { icon: createPin('#9333ea', '🏢', 34) }).addTo(map)
            .bindPopup("<strong>Kantor Pusat / NOC</strong>");

        // Load Network Data
        fetch('/admin/map-data')
            .then(res => res.json())
            .then(data => {
                // Render ODPs
                data.odps.forEach(odp => {
                    const odpMarker = L.marker([odp.lat, odp.lng], {
                        icon: createPin('#2563eb', '📦', 32)
                    }).addTo(map);

                    const odpPopup = `
                        <div style="line-height:1.4;">
                            <strong style="color:#1d4ed8; font-size:14px;">${odp.name}</strong><br>
                            <small>${odp.code} • ${odp.address || 'Tiang'}</small><br>
                            <div style="margin:5px 0; background:#f1f5f9; padding:4px 8px; border-radius:4px;">
                                Kapasitas: <strong>${odp.occupied_ports} / ${odp.total_ports} Port Terpakai</strong><br>
                                Sisa Kosong: <strong style="color:#16a34a;">${odp.total_ports - odp.occupied_ports} Port</strong>
                            </div>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${odp.lat},${odp.lng}" target="_blank" class="btn-action btn-gmaps">
                                🚗 Rute Google Maps ke ODP
                            </a>
                        </div>
                    `;
                    odpMarker.bindPopup(odpPopup);
                });

                // Render Customers
                data.customers.forEach(cust => {
                    const statusColor = cust.status === 'active' ? '#16a34a' : (cust.status === 'isolated' ? '#dc2626' : '#f59e0b');
                    const custMarker = L.marker([cust.lat, cust.lng], {
                        icon: createPin(statusColor, '👤', 28)
                    }).addTo(map);

                    // Cable line to ODP
                    let cableCoords = [];
                    if (cust.cable_path && cust.cable_path.length > 0) {
                        cableCoords = cust.cable_path;
                    } else if (cust.odp_lat && cust.odp_lng) {
                        cableCoords = [[cust.odp_lat, cust.odp_lng], [cust.lat, cust.lng]];
                    }

                    if (cableCoords.length > 0) {
                        L.polyline(cableCoords, {
                            color: cust.status === 'active' ? '#10b981' : '#f87171',
                            weight: 3,
                            opacity: 0.8,
                            dashArray: cust.cable_path ? null : '4, 4'
                        }).addTo(map);
                    }

                    const cleanPhone = cust.phone ? cust.phone.replace(/^0/, '62') : '';

                    const custPopup = `
                        <div style="line-height:1.4; min-width:210px;">
                            <strong style="font-size:14px;">${cust.name}</strong><br>
                            <small style="color:#64748b;">${cust.customer_number}</small><br>
                            <div style="margin:4px 0;">Alamat: ${cust.address || '-'}</div>
                            <div>ODP: <strong>${cust.odp_name || '-'} (Port ${cust.odp_port || '-'})</strong></div>
                            <div>Status: <strong style="color:${statusColor}">${cust.status.toUpperCase()}</strong></div>
                            <hr style="margin:6px 0; border:none; border-top:1px solid #e2e8f0;">

                            <a href="https://www.google.com/maps/dir/?api=1&destination=${cust.lat},${cust.lng}" target="_blank" class="btn-action btn-gmaps">
                                🚗 Buka Rute Google Maps
                            </a>

                            ${cleanPhone ? `
                                <a href="https://wa.me/${cleanPhone}?text=Halo%20Bpk/Ibu%20${encodeURIComponent(cust.name)},%20kami%20dari%20tim%20teknisi%20internet." target="_blank" class="btn-action btn-wa">
                                    💬 Chat WhatsApp
                                </a>
                                <a href="tel:${cust.phone}" class="btn-action btn-tel">
                                    📞 Telepon Pelanggan
                                </a>
                            ` : ''}
                        </div>
                    `;
                    custMarker.bindPopup(custPopup);
                });
            });

        // GPS Device Tracker for Field Technician
        let userMarker = null;
        let accuracyCircle = null;

        document.getElementById('btn-gps').onclick = function() {
            if (!navigator.geolocation) {
                alert('Browser atau perangkat Anda tidak mendukung GPS Geolocation.');
                return;
            }

            this.style.background = '#e11d48';

            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = pos.coords.accuracy;

                if (userMarker) map.removeLayer(userMarker);
                if (accuracyCircle) map.removeLayer(accuracyCircle);

                const myIcon = L.divIcon({
                    className: 'my-loc',
                    html: '<div class="my-loc-dot"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });

                userMarker = L.marker([lat, lng], { icon: myIcon }).addTo(map)
                    .bindPopup("<strong>Posisi Anda Sekarang</strong><br><small>Akurasi: ±" + Math.round(acc) + " meter</small>")
                    .openPopup();

                accuracyCircle = L.circle([lat, lng], { radius: acc, color: '#3b82f6', fillOpacity: 0.15 }).addTo(map);

                map.setView([lat, lng], 18);
                document.getElementById('btn-gps').style.background = '#2563eb';
            }, function(err) {
                alert('Gagal mengambil lokasi GPS: ' + err.message);
                document.getElementById('btn-gps').style.background = '#2563eb';
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        };
    </script>
</body>
</html>
