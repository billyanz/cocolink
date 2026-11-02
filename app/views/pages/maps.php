<?php
function page_maps(array $u): void {
    $manager   = is_manager($u);
    $suppliers = $manager 
        ? rows("SELECT s.*, (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops FROM suppliers s")
        : rows("SELECT s.*, (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops FROM suppliers s WHERE s.status='verified'");

    $coops = rows("SELECT c.*, s.legal_name, r.name AS raw_name FROM cooperatives c JOIN suppliers s ON s.id=c.supplier_id JOIN raw_types r ON r.id=c.specialty_raw_type_id WHERE c.status='verified'");

    $points = [];
    foreach ($suppliers as $s) {
        $points[] = [
            'type'   => 'supplier',
            'name'   => $s['legal_name'],
            'city'   => $s['city'] . ', ' . $s['province'],
            'lat'    => (float)$s['latitude'],
            'lng'    => (float)$s['longitude'],
            'status' => $s['status'],
            'info'   => 'NIB: ' . $s['nib'] . ' | Koperasi Valid: ' . $s['verified_coops']
        ];
    }

    $baseLat = -6.9175;
    $baseLng = 107.6191;
    foreach ($coops as $idx => $c) {
        $lat = $baseLat + (($idx + 1) * 0.025);
        $lng = $baseLng + (($idx + 1) * 0.035);
        $points[] = [
            'type'   => 'coop',
            'name'   => $c['name'],
            'city'   => $c['city'] . ', Jawa Barat',
            'lat'    => $lat,
            'lng'    => $lng,
            'status' => 'verified',
            'info'   => 'PT Utama: ' . $c['legal_name'] . ' | Spesialis: ' . $c['raw_name']
        ];
    }
?>

<!-- ASSETS LEAFLET MAPS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Peta Sebaran Jaringan Ekosistem Mitra</h5>
                <p class="mb-0 small text-white-50">Visualisasi geospasial lokasi PT Pemasok Limbah Utama dan Koperasi Anak Olahan di Indonesia.</p>
            </div>
        </div>
    </div>
</div>

<!-- KPI SUMMARY MAPS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <?php kpi('PT Pemasok Terpeta', count($suppliers), 'bi-building', 'text-success bg-success'); ?>
    </div>
    <div class="col-md-4">
        <?php kpi('Koperasi Anak Terpeta', count($coops), 'bi-shop', 'text-info bg-info'); ?>
    </div>
    <div class="col-md-4">
        <?php kpi('Total Titik Koordinat', count($points), 'bi-pin-map-fill', 'text-warning bg-warning'); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; overflow:hidden;">
    <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <div class="fw-bold text-dark"><i class="bi bi-map me-2" style="color:#164B35"></i>Peta Interaktif OpenStreetMap</div>
            <div class="d-flex gap-2">
                <span class="badge bg-success"><i class="bi bi-geo-alt-fill me-1"></i>PT Verified</span>
                <span class="badge bg-warning text-dark"><i class="bi bi-geo-alt-fill me-1"></i>PT Pending</span>
                <span class="badge bg-info"><i class="bi bi-geo-alt-fill me-1"></i>Koperasi Anak</span>
            </div>
        </div>
        <div id="cocoMap" style="height: 520px; width: 100%; z-index: 1;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('cocoMap').setView([-6.9175, 107.6191], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> CoCoLink'
    }).addTo(map);

    var points = <?= json_encode($points) ?>;
    var bounds = [];

    points.forEach(function(pt) {
        var color = pt.type === 'coop' ? 'blue' : (pt.status === 'verified' ? 'green' : 'gold');
        var iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-' + color + '.png';

        var customIcon = L.icon({
            iconUrl: iconUrl,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var pop = '<strong>' + pt.name + '</strong><br>' +
                  '<small class="text-muted">' + pt.city + '</small><br>' +
                  '<span class="badge bg-light text-dark border mt-1">' + pt.info + '</span>';

        var m = L.marker([pt.lat, pt.lng], {icon: customIcon}).addTo(map).bindPopup(pop);
        bounds.push([pt.lat, pt.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, {padding: [40, 40]});
    }
});
</script>
<?php } ?>