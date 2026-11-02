<?php
function page_maps(array $u): void {
    // 1. Ambil data PT Pemasok
    $pt_query = is_manager($u) 
        ? "SELECT id, legal_name AS name, city, latitude, longitude, status FROM suppliers WHERE status IN ('verified','pending')" 
        : "SELECT id, legal_name AS name, city, latitude, longitude, status FROM suppliers WHERE status='verified'";
    
    $points = [];
    foreach (rows($pt_query) as $p) {
        if (!empty($p['latitude']) && !empty($p['longitude'])) {
            $points[] = [
                'name' => $p['name'],
                'city' => $p['city'],
                'lat'  => (float)$p['latitude'],
                'lng'  => (float)$p['longitude'],
                'kind' => $p['status'] === 'pending' ? 'PT Menunggu Verifikasi' : 'PT Pemasok'
            ];
        }
    }

    // 2. Ambil data Koperasi Anak
    $coop_query = "SELECT c.id, c.name, c.city, c.latitude, c.longitude, r.name AS specialty 
                  FROM cooperatives c 
                  LEFT JOIN raw_types r ON c.specialty_raw_type_id = r.id 
                  WHERE c.status='verified'";
    
    $coop_unmapped = 0;
    foreach (rows($coop_query) as $p) {
        if (!empty($p['latitude']) && !empty($p['longitude'])) {
            $points[] = [
                'name'      => $p['name'],
                'city'      => $p['city'],
                'specialty' => $p['specialty'] ?? 'Pengolahan Limbah',
                'lat'       => (float)$p['latitude'],
                'lng'       => (float)$p['longitude'],
                'kind'      => 'Koperasi Anak'
            ];
        } else {
            $coop_unmapped++;
        }
    }

    $total_coop = (int)val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
    $total_pt   = (int)val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
?>

<style>
    .map-box-outer {
        position: relative;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #DCE6D9;
        box-shadow: 0 4px 20px rgba(22, 75, 53, 0.04);
        background: #ffffff;
    }
    #map {
        height: 520px;
        width: 100% !important;
        z-index: 1;
        border-radius: 16px;
        display: block;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        padding: 4px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        border: 1px solid #DCE6D9;
    }
    .custom-map-popup {
        font-family: inherit;
        padding: 6px 10px;
    }
    .custom-map-popup .badge-kind {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 6px;
        margin-bottom: 6px;
        display: inline-block;
    }
    .badge-pt-active { background: #164B35; color: #C7ED8D; }
    .badge-pt-pending { background: #EAB308; color: #FFFFFF; }
    .badge-coop { background: #0284C7; color: #FFFFFF; }
    .custom-map-popup h6 {
        margin: 0 0 4px 0;
        font-weight: 700;
        color: #24362E;
        font-size: 14px;
    }
    .custom-map-popup p {
        margin: 0;
        font-size: 12px;
        color: #64748B;
    }
</style>

<!-- Top KPI Summary -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <?php kpi('Titik Jaringan Aktif', count($points), 'map-pin'); ?>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <?php kpi('Total Koperasi Terverifikasi', $total_coop, 'network'); ?>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <?php kpi('PT Pemasok Terverifikasi', $total_pt, 'building'); ?>
    </div>
</div>

<!-- Container Peta Utama -->
<div class="panel panel-pad mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1" style="color: #164B35;">Peta Jaringan Presisi CoCoLink</h5>
            <p class="text-muted small mb-0">Visualisasi realtime lokasi PT Pemasok Limbah & Koperasi Anak Pengolah Per Wilayah</p>
        </div>
        <span class="badge px-3 py-2" style="background: #F6F8F4; color: #164B35; border: 1px solid #DCE6D9; font-weight: 600;">
            Leaflet JS / OpenStreetMap
        </span>
    </div>

    <?php if ($coop_unmapped > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="border-radius: 10px; font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Terdapat <strong><?= $coop_unmapped ?> Koperasi Anak</strong> yang belum memiliki koordinat Latitude/Longitude. Silakan perbarui koordinat pada menu Manajemen Akun.</span>
        </div>
    <?php endif; ?>

    <div class="map-box-outer">
        <div id="map"></div>
    </div>

    <!-- Footnote Legend -->
    <div class="d-flex flex-wrap align-items-center gap-4 mt-3 pt-2 px-2 text-muted small" style="font-size: 12px;">
        <div class="d-flex align-items-center gap-2">
            <span style="width: 12px; height: 12px; background-color: #164B35; border-radius: 50%; display: inline-block;"></span>
            <span>PT Terverifikasi</span>
        </div>
        <?php if (is_manager($u)): ?>
        <div class="d-flex align-items-center gap-2">
            <span style="width: 12px; height: 12px; background-color: #EAB308; border-radius: 50%; display: inline-block;"></span>
            <span>PT Menunggu Verifikasi</span>
        </div>
        <?php endif; ?>
        <div class="d-flex align-items-center gap-2">
            <span style="width: 12px; height: 12px; background-color: #0284C7; border-radius: 50%; display: inline-block;"></span>
            <span>Koperasi Anak</span>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzxGJ+CEV5D/3fDPn6h4uPR40=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const pts = <?= json_encode($points, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const mapEl = document.getElementById('map');
    
    if (window.L && mapEl) {
        const map = L.map('map', {
            zoomControl: true,
            scrollWheelZoom: true
        }).setView([-2.5489, 118.0149], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; CoCoLink Network &bull; OpenStreetMap'
        }).addTo(map);

        const marks = [];

        pts.forEach(p => {
            let colorHex = '#0284C7';
            let badgeClass = 'badge-coop';

            if (p.kind === 'PT Pemasok') {
                colorHex = '#164B35';
                badgeClass = 'badge-pt-active';
            } else if (p.kind === 'PT Menunggu Verifikasi') {
                colorHex = '#EAB308';
                badgeClass = 'badge-pt-pending';
            }

            const marker = L.circleMarker([p.lat, p.lng], {
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: colorHex,
                fillOpacity: 0.95
            }).addTo(map);

            const popupContent = `
                <div class="custom-map-popup">
                    <span class="badge-kind ${badgeClass}">${p.kind}</span>
                    <h6>${p.name}</h6>
                    <p><i class="bi bi-geo-alt"></i> ${p.city} ${p.specialty ? ' &bull; Spesiadlsasi: ' + p.specialty : ''}</p>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            marks.push(marker);
        });

        if (marks.length > 0) {
            const group = L.featureGroup(marks);
            map.fitBounds(group.getBounds().pad(0.3));
        }

        // Trik utama perbaikan render Leaflet agar tidak pernah kepotong
        const forceResize = () => {
            map.invalidateSize();
        };
        setTimeout(forceResize, 100);
        setTimeout(forceResize, 400);
        setTimeout(forceResize, 1000);
        window.addEventListener('resize', forceResize);
    } else {
        mapEl.innerHTML = '<div class="p-4 text-center text-danger">Gagal memuat peta Leaflet. Pastikan koneksi internet tersedia.</div>';
    }
});
</script>
<?php } ?>