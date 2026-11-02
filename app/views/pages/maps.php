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
            'info'   => 'NIB: ' . $s['nib'] . ' | Koperasi Binaan: ' . $s['verified_coops']
        ];
    }

    foreach ($coops as $c) {
        $points[] = [
            'type'   => 'coop',
            'name'   => $c['name'],
            'city'   => $c['city'] . ', Jawa Barat',
            'lat'    => $c['latitude'] ? (float)$c['latitude'] : -6.9175,
            'lng'    => $c['longitude'] ? (float)$c['longitude'] : 107.6191,
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
                <p class="mb-0 small text-white-50">Visualisasi Geospasial PT Pemasok Utama (Ikon Hijau) & Koperasi Anak Binaan (Ikon Biru).</p>
            </div>
        </div>
    </div>
</div>

<!-- KPI SUMMARY MAPS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <?php kpi('PT Pemasok Utama (Ikon Hijau)', count($suppliers), 'bi-building', 'text-success bg-success'); ?>
    </div>
    <div class="col-md-4">
        <?php kpi('Koperasi Anak Binaan (Ikon Biru)', count($coops), 'bi-shop', 'text-primary bg-primary'); ?>
    </div>
    <div class="col-md-4">
        <?php kpi('Total Titik Koordinat', count($points), 'bi-pin-map-fill', 'text-warning bg-warning'); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; overflow:hidden;">
    <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <div class="fw-bold text-dark"><i class="bi bi-map me-2" style="color:#164B35"></i>Peta Interaktif OpenStreetMap CoCoLink</div>
            <div class="d-flex gap-2">
                <span class="badge bg-success px-3 py-2"><i class="bi bi-building me-1"></i>PT Induk (Ikon Hijau)</span>
                <span class="badge bg-primary px-3 py-2"><i class="bi bi-shop me-1"></i>Koperasi Anak (Ikon Biru)</span>
            </div>
        </div>
        <div id="cocoMap" style="height: 540px; width: 100%; z-index: 1;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('cocoMap').setView([-6.2000, 106.8166], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap CoCoLink Network'
    }).addTo(map);

    var points = <?= json_encode($points) ?>;
    var bounds = [];

    points.forEach(function(pt) {
        // Marker Hijau untuk PT Induk, Marker Biru untuk Koperasi Anak
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

        var iconHeader = pt.type === 'coop' ? '<span class="badge bg-primary mb-1"><i class="bi bi-shop me-1"></i>Koperasi Anak</span>' : '<span class="badge bg-success mb-1"><i class="bi bi-building me-1"></i>PT Pemasok Utama</span>';

        var pop = '<div style="min-width:200px;">' +
                  iconHeader +
                  '<strong class="text-dark d-block fs-6">' + pt.name + '</strong>' +
                  '<small class="text-muted d-block mb-2"><i class="bi bi-geo-alt me-1"></i>' + pt.city + '</small>' +
                  '<div class="p-2 rounded bg-light border text-dark small mb-0">' + pt.info + '</div>' +
                  '</div>';

        L.marker([pt.lat, pt.lng], {icon: customIcon}).addTo(map).bindPopup(pop);
        bounds.push([pt.lat, pt.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, {padding: [50, 50]});
    }
});
</script>
<?php } ?>