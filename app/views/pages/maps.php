<?php
function page_maps(array $u): void {
    // 1. DATA KOPERASI INDUK (AUTO DETECT VIA NAMA / ROLE / ID)
    $indukData = one("SELECT s.* FROM suppliers s 
                      WHERE s.status='verified' AND (LOWER(s.legal_name) LIKE '%induk%' OR LOWER(s.legal_name) LIKE '%metropolitan%') 
                      ORDER BY s.id ASC LIMIT 1");

    if (!$indukData) {
        $indukData = one("SELECT s.* FROM suppliers s WHERE s.status='verified' ORDER BY s.id ASC LIMIT 1");
    }

    $indukId = $indukData ? (int)$indukData['id'] : 0;

    // 2. DATA PT SUPPLIER UTAMA (SELAIN KOPERASI INDUK)
    $suppliers = rows("SELECT s.*, (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops 
                      FROM suppliers s 
                      WHERE s.status='verified' AND s.id != ? 
                      ORDER BY s.id DESC", [$indukId]);

    // 3. DATA KOPERASI ANAK
    $coops = rows("SELECT c.*, s.legal_name AS induk_name, r.name AS raw_name 
                  FROM cooperatives c 
                  JOIN suppliers s ON s.id=c.supplier_id 
                  LEFT JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                  WHERE c.status='verified'");

    // 4. DATA BUYER B2B
    $buyers = rows("SELECT b.* FROM buyer_profiles b LIMIT 15");

    // 5. DATA TRUK DRIVER ARMADA BERGERAK
    $activeShipments = rows("SELECT sh.id AS shipment_id, sh.status AS ship_status, o.order_code, 
                                   us.name AS driver_name, us.driver_type,
                                   c.name AS coop_name, c.latitude AS coop_lat, c.longitude AS coop_lng,
                                   b.company_name AS buyer_name, b.city AS buyer_city,
                                   ev.latitude AS curr_lat, ev.longitude AS curr_lng, ev.notes AS last_note
                            FROM shipments sh
                            JOIN orders o ON o.id=sh.order_id
                            JOIN buyer_profiles b ON b.id=o.buyer_id
                            JOIN users us ON us.id=sh.driver_id
                            LEFT JOIN order_items i ON i.order_id=o.id
                            LEFT JOIN products p ON p.id=i.product_id
                            LEFT JOIN cooperatives c ON c.id=p.coop_id
                            LEFT JOIN shipment_events ev ON ev.id = (
                                SELECT MAX(id) FROM shipment_events WHERE shipment_id=sh.id AND latitude IS NOT NULL
                            )
                            WHERE sh.status IN ('assigned', 'accepted', 'pickup_pending', 'picked_up', 'in_transit')
                            GROUP BY sh.id");

    $points = [];
    
    // PENETAPAN KOPERASI INDUK METROPOLITAN (PLOT DI PUSAT JAKARTA AGAR TIDAK NUMPUK)
    $points[] = [
        'type'       => 'induk',
        'role_label' => 'Koperasi Induk Sektor',
        'color'      => 'violet',
        'hex'        => '#8B5CF6',
        'name'       => $indukData ? $indukData['legal_name'] : 'Koperasi Induk Metropolitan Jabodetabek',
        'city'       => 'DKI Jakarta Pusat',
        'lat'        => -6.1753924,
        'lng'        => 106.8271528,
        'info'       => 'NIB: ' . ($indukData['nib'] ?? '9120003410012') . ' | Pusat Koordinasi 5 Koperasi Anak Sektor'
    ];

    // SUPPLIER UTAMA (PT RAJS HYDRO DIBUAT DI BEKASI)
    foreach ($suppliers as $s) {
        $sLat = (!empty($s['latitude']) && (float)$s['latitude'] != 0) ? (float)$s['latitude'] : -6.2383;
        $sLng = (!empty($s['longitude']) && (float)$s['longitude'] != 0) ? (float)$s['longitude'] : 106.9756;

        $points[] = [
            'type'       => 'supplier',
            'role_label' => 'PT Supplier Utama',
            'color'      => 'green',
            'hex'        => '#10B981',
            'name'       => $s['legal_name'],
            'city'       => $s['city'] . ', ' . ($s['province'] ?? 'Jawa Barat'),
            'lat'        => $sLat,
            'lng'        => $sLng,
            'info'       => 'NIB: ' . $s['nib'] . ' | Penyalur Bahan Mentah ke Induk'
        ];
    }

    // KOPERASI ANAK (BIRU)
    foreach ($coops as $c) {
        $cLat = (!empty($c['latitude']) && (float)$c['latitude'] != 0) ? (float)$c['latitude'] : -6.2100;
        $cLng = (!empty($c['longitude']) && (float)$c['longitude'] != 0) ? (float)$c['longitude'] : 106.8450;

        $points[] = [
            'type'       => 'coop',
            'role_label' => 'Koperasi Anak Olahan',
            'color'      => 'blue',
            'hex'        => '#0284C7',
            'name'       => $c['name'],
            'city'       => $c['city'] . ', Jabodetabek',
            'lat'        => $cLat,
            'lng'        => $cLng,
            'info'       => 'Kop. Induk: ' . $c['induk_name'] . ' | Spesialis: ' . ($c['raw_name'] ?: 'Olahan Kelapa')
        ];
    }

    // BUYER B2B (ORANGE)
    foreach ($buyers as $b) {
        if (!empty($b['company_name'])) {
            $points[] = [
                'type'       => 'buyer',
                'role_label' => 'Buyer Industri B2B',
                'color'      => 'orange',
                'hex'        => '#F97316',
                'name'       => $b['company_name'],
                'city'       => $b['city'] ?? 'Jakarta',
                'lat'        => -6.2200 + (rand(-30, 30) / 1000),
                'lng'        => 106.8300 + (rand(-30, 30) / 1000),
                'info'       => 'NIB: ' . ($b['nib'] ?? '-') . ' | Tujuan Pengiriman Olahan'
            ];
        }
    }

    // TRUK DRIVER (MERAH)
    $trucks = [];
    foreach ($activeShipments as $shp) {
        $tLat = $shp['curr_lat'] ? (float)$shp['curr_lat'] : ($shp['coop_lat'] ? (float)$shp['coop_lat'] : -6.2000);
        $tLng = $shp['curr_lng'] ? (float)$shp['curr_lng'] : ($shp['coop_lng'] ? (float)$shp['coop_lng'] : 106.8166);

        $trucks[] = [
            'shipment_id' => $shp['shipment_id'],
            'order_code'  => $shp['order_code'],
            'driver_name' => $shp['driver_name'],
            'driver_type' => $shp['driver_type'] ?? 'coop_to_buyer',
            'coop_name'   => $shp['coop_name'] ?? 'Sektor Gudang',
            'buyer_name'  => $shp['buyer_name'],
            'status'      => status_text($shp['ship_status']),
            'last_note'   => $shp['last_note'] ?: 'Armada sedang melaju di rute Jabodetabek',
            'lat'         => $tLat,
            'lng'         => $tLng
        ];
    }

    $cntInduk    = count(array_filter($points, fn($p) => $p['type'] === 'induk'));
    $cntSupplier = count(array_filter($points, fn($p) => $p['type'] === 'supplier'));
    $cntCoop     = count(array_filter($points, fn($p) => $p['type'] === 'coop'));
    $cntBuyer    = count(array_filter($points, fn($p) => $p['type'] === 'buyer'));
    $cntTrucks   = count($trucks);
    $totalPoints = count($points);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .maps-page-container {
        font-family: 'Plus Jakarta Sans', sans-serif; color: #0F172A;
        animation: fadeIn 0.4s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        position: relative; overflow: hidden; border-radius: 20px;
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    .banner-silhouette-bg {
        position: absolute; top: 0; right: 0; bottom: 0; left: 0;
        pointer-events: none; z-index: 0; overflow: hidden;
    }
    .banner-silhouette-bg svg {
        position: absolute; right: -20px; bottom: -20px; height: 125%; width: auto; opacity: 0.10; fill: #FFFFFF;
    }

    .role-badge-crisp {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35); color: #FFFFFF;
        padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    }
    .banner-title { color: #FFFFFF; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.5px; }
    .banner-subtitle { color: #E0F2FE; font-size: 0.95rem; font-weight: 500; }

    .role-stat-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1.15rem 1.25rem;
        transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.02); position: relative; overflow: hidden;
    }
    .role-stat-card:hover { border-color: #CBD5E1; transform: translateY(-2px); }
    .role-stat-card::before { content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px; }
    .role-stat-card.stat-induk::before { background: #8B5CF6; }
    .role-stat-card.stat-supplier::before { background: #10B981; }
    .role-stat-card.stat-coop::before { background: #0284C7; }
    .role-stat-card.stat-buyer::before { background: #F97316; }
    .role-stat-card.stat-truck::before { background: #E11D48; }

    .role-stat-label { color: #64748B; font-weight: 600; font-size: 0.78rem; margin-bottom: 0.25rem; }
    .role-stat-val { color: #0F172A; font-weight: 800; font-size: 1.55rem; line-height: 1.1; }
    .role-stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

    .map-frame-card {
        background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 20px; overflow: hidden;
        box-shadow: 0 8px 24px -4px rgba(0,0,0,0.05);
    }
    .map-header-bar { background: #FFFFFF; padding: 1rem 1.5rem; border-bottom: 1px solid #E2E8F0; }

    .legend-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px;
        font-size: 0.78rem; font-weight: 700; border: 1px solid transparent;
    }
    .legend-induk { background: #F3E8FF; border-color: #DDD6FE; color: #6D28D9; }
    .legend-supplier { background: #ECFDF5; border-color: #A7F3D0; color: #047857; }
    .legend-coop { background: #F0F9FF; border-color: #BAE6FD; color: #0369A1; }
    .legend-buyer { background: #FFF7ED; border-color: #FFEDD5; color: #C2410C; }
    .legend-truck { background: #FFE4E6; border-color: #FECDD3; color: #BE123C; }

    .progress-bar-custom { height: 8px; border-radius: 10px; background: #E2E8F0; overflow: hidden; display: flex; }
    .progress-bar-seg { height: 100%; transition: width 0.4s ease; }
</style>

<div class="maps-page-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="banner-silhouette-bg">
            <svg viewBox="0 0 500 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,220 Q 125,180 250,220 T 500,220 L 500,300 L 0,300 Z" />
                <path d="M 0,240 Q 125,210 250,240 T 500,240 L 500,300 L 0,300 Z" opacity="0.6" />
                <g transform="translate(310, 10) scale(0.95)">
                    <path d="M 140 280 Q 125 180 145 100 Q 152 100 148 280 Z" />
                    <path d="M 145 100 Q 65 70 15 90 Q 65 60 145 100 Z" />
                    <path d="M 145 100 Q 85 30 35 20 Q 85 25 145 100 Z" />
                    <path d="M 145 100 Q 165 20 225 15 Q 170 30 145 100 Z" />
                    <path d="M 145 100 Q 215 50 265 80 Q 200 70 145 100 Z" />
                    <path d="M 145 100 Q 225 110 275 150 Q 205 120 145 100 Z" />
                    <path d="M 145 100 Q 75 120 25 160 Q 75 130 145 100 Z" />
                    <circle cx="137" cy="108" r="8" /><circle cx="150" cy="112" r="9" /><circle cx="143" cy="120" r="7.5" />
                </g>
            </svg>
        </div>

        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3"><i class="bi bi-geo-alt-fill"></i> GEOSPATIAL MONITORING &amp; LIVE TRACKING</div>
                    <h2 class="banner-title mb-2">Peta Real-Time Ekosistem &amp; Armada Truk 📍</h2>
                    <p class="banner-subtitle mb-0">Visualisasi Geospasial Entitas (Induk, Supplier, Anak, Buyer) &amp; Tracking Rute Armada Logistik.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- METRIK STATISTIK GEOSPASIAL -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="role-stat-card stat-induk d-flex align-items-center justify-content-between">
                <div>
                    <div class="role-stat-label">Koperasi Induk</div>
                    <div class="role-stat-val"><?= number((float)$cntInduk) ?></div>
                </div>
                <div class="role-stat-icon" style="background:#F3E8FF; color:#8B5CF6;"><i class="bi bi-diagram-3-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="role-stat-card stat-supplier d-flex align-items-center justify-content-between">
                <div>
                    <div class="role-stat-label">PT Supplier Utama</div>
                    <div class="role-stat-val"><?= number((float)$cntSupplier) ?></div>
                </div>
                <div class="role-stat-icon" style="background:#ECFDF5; color:#10B981;"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="role-stat-card stat-coop d-flex align-items-center justify-content-between">
                <div>
                    <div class="role-stat-label">Koperasi Anak Olahan</div>
                    <div class="role-stat-val"><?= number((float)$cntCoop) ?></div>
                </div>
                <div class="role-stat-icon" style="background:#F0F9FF; color:#0284C7;"><i class="bi bi-shop"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="role-stat-card stat-truck d-flex align-items-center justify-content-between">
                <div>
                    <div class="role-stat-label">Truk Bergerak (Live)</div>
                    <div class="role-stat-val"><?= number((float)$cntTrucks) ?></div>
                </div>
                <div class="role-stat-icon" style="background:#FFE4E6; color:#E11D48;"><i class="bi bi-truck-front-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 p-3.5 px-4" style="border-radius:16px; border: 1px solid #E2E8F0; background:#FFFFFF;">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="fw-bold small text-dark"><i class="bi bi-bar-chart-fill me-1.5" style="color:#0077B6;"></i> Proporsi Titik Geospasial Terdaftar (Total: <?= $totalPoints ?> Titik)</div>
            <div class="small text-muted">Cakupan Wilayah: <strong>Jabodetabek</strong></div>
        </div>
        <?php 
            $pctInduk    = $totalPoints > 0 ? round(($cntInduk / $totalPoints) * 100, 1) : 0;
            $pctSupplier = $totalPoints > 0 ? round(($cntSupplier / $totalPoints) * 100, 1) : 0;
            $pctCoop     = $totalPoints > 0 ? round(($cntCoop / $totalPoints) * 100, 1) : 0;
            $pctBuyer    = $totalPoints > 0 ? round(($cntBuyer / $totalPoints) * 100, 1) : 0;
        ?>
        <div class="progress-bar-custom">
            <div class="progress-bar-seg" style="width: <?= $pctInduk ?>%; background: #8B5CF6;" title="Koperasi Induk: <?= $pctInduk ?>%"></div>
            <div class="progress-bar-seg" style="width: <?= $pctSupplier ?>%; background: #10B981;" title="PT Supplier: <?= $pctSupplier ?>%"></div>
            <div class="progress-bar-seg" style="width: <?= $pctCoop ?>%; background: #0284C7;" title="Koperasi Anak: <?= $pctCoop ?>%"></div>
            <div class="progress-bar-seg" style="width: <?= $pctBuyer ?>%; background: #F97316;" title="Buyer B2B: <?= $pctBuyer ?>%"></div>
        </div>
    </div>

    <!-- PETA LEAFLET INTERAKTIF -->
    <div class="map-frame-card mb-4">
        <div class="map-header-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="fw-bold fs-6" style="color:#0F172A;"><i class="bi bi-map-fill me-2" style="color:#0077B6;"></i>Peta Live Tracking Geospasial Jabodetabek</div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="legend-chip legend-induk"><i class="bi bi-diagram-3-fill"></i> Kop. Induk (Violet)</span>
                <span class="legend-chip legend-supplier"><i class="bi bi-building"></i> PT Supplier (Hijau)</span>
                <span class="legend-chip legend-coop"><i class="bi bi-shop"></i> Kop. Anak (Biru)</span>
                <span class="legend-chip legend-buyer"><i class="bi bi-cart-check"></i> Buyer B2B (Orange)</span>
                <span class="legend-chip legend-truck"><i class="bi bi-truck-front-fill"></i> Truk Driver (Merah)</span>
            </div>
        </div>
        <div id="cocoMap" style="height: 580px; width: 100%; z-index: 1;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('cocoMap').setView([-6.2000, 106.8166], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap CoCoLink Logistics'
    }).addTo(map);

    var points = <?= json_encode($points, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?>;
    var trucks = <?= json_encode($trucks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?>;
    var bounds = [];

    function popupElement(tagName, className, text) {
        var element = document.createElement(tagName);
        element.className = className;
        element.textContent = String(text == null ? '' : text);
        return element;
    }

    // RENDER MARKER TITIK ENTITAS
    points.forEach(function(pt) {
        var iconColor = pt.color || 'blue';
        var zIndexOffset = 0;

        if (pt.type === 'induk') {
            iconColor = 'violet';
            zIndexOffset = 1000; // MENAIKKAN Z-INDEX AGAR SELALU DI TAMPILAN PALING DEPAN
        }

        var iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-' + iconColor + '.png';

        var customIcon = L.icon({
            iconUrl: iconUrl,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        var popup = document.createElement('div');
        popup.style.minWidth = '230px';
        popup.style.fontFamily = "'Plus Jakarta Sans', sans-serif";

        var badge = popupElement('span', 'badge mb-2 d-inline-block', pt.role_label);
        badge.style.backgroundColor = pt.hex;
        badge.style.color = '#FFFFFF';
        badge.style.fontWeight = '700';
        badge.style.borderRadius = '12px';
        badge.style.padding = '4px 10px';
        badge.style.fontSize = '0.75rem';
        popup.appendChild(badge);

        var name = popupElement('strong', 'text-dark d-block fs-6 mb-1', pt.name);
        name.style.fontWeight = '800';
        popup.appendChild(name);

        var city = popupElement('small', 'text-secondary d-block mb-2', '');
        var locationIcon = popupElement('i', 'bi bi-geo-alt-fill me-1', '');
        locationIcon.style.color = '#0077B6';
        city.appendChild(locationIcon);
        city.appendChild(document.createTextNode(String(pt.city == null ? '' : pt.city)));
        popup.appendChild(city);

        var info = popupElement('div', 'p-2 rounded bg-light border text-dark small mb-0', pt.info);
        info.style.fontSize = '0.8rem';
        info.style.lineHeight = '1.4';
        popup.appendChild(info);

        L.marker([pt.lat, pt.lng], {icon: customIcon, zIndexOffset: zIndexOffset}).addTo(map).bindPopup(popup);
        bounds.push([pt.lat, pt.lng]);
    });

    // RENDER MARKER TRUK DRIVER (MERAH)
    trucks.forEach(function(tr) {
        var redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        var popup = document.createElement('div');
        popup.style.minWidth = '240px';
        popup.style.fontFamily = "'Plus Jakarta Sans', sans-serif";

        var badge = popupElement('span', 'badge bg-danger text-white mb-2', '🚚 TRUK ARMADA IN-TRANSIT');
        badge.style.fontWeight = '700';
        badge.style.borderRadius = '12px';
        badge.style.padding = '4px 10px';
        popup.appendChild(badge);
        popup.appendChild(popupElement('strong', 'text-dark d-block fs-6 mb-1', tr.driver_name));

        var order = popupElement('small', 'text-muted d-block mb-1', 'Order Code: ');
        order.appendChild(popupElement('b', '', tr.order_code));
        popup.appendChild(order);
        popup.appendChild(popupElement('small', 'text-muted d-block mb-2', 'Rute: ' + String(tr.coop_name == null ? '' : tr.coop_name) + ' ➔ ' + String(tr.buyer_name == null ? '' : tr.buyer_name)));

        var details = popupElement('div', 'p-2 rounded bg-light border text-dark small mb-0', '');
        details.style.fontSize = '0.78rem';
        details.appendChild(popupElement('strong', '', 'Posisi:'));
        details.appendChild(document.createTextNode(' ' + String(tr.last_note == null ? '' : tr.last_note)));
        details.appendChild(document.createElement('br'));
        details.appendChild(popupElement('strong', '', 'Status:'));
        details.appendChild(document.createTextNode(' ' + String(tr.status == null ? '' : tr.status)));
        popup.appendChild(details);

        L.marker([tr.lat, tr.lng], {icon: redIcon, zIndexOffset: 2000}).addTo(map).bindPopup(popup);
        bounds.push([tr.lat, tr.lng]);
    });

    if (bounds.length > 0) map.fitBounds(bounds, {padding: [50, 50]});
});
</script>
<?php } ?>