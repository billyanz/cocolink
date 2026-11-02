<?php
function page_shipments(array $u): void {
    $manager = is_manager($u);
    $driver  = $u['role'] === 'driver';
    $isBuyer = $u['role'] === 'buyer';

    $query = "SELECT sh.*, o.order_code, o.status AS order_status, o.shipping_address, o.city, o.total, b.company_name, 
              us.name AS driver_name, 
              c.name AS coop_name, c.address AS coop_address, c.city AS coop_city, c.latitude AS coop_lat, c.longitude AS coop_lng,
              s_lat.latitude AS curr_lat, s_lat.longitude AS curr_lng
              FROM shipments sh 
              JOIN orders o ON o.id=sh.order_id 
              JOIN buyer_profiles b ON b.id=o.buyer_id 
              JOIN users us ON us.id=sh.driver_id
              LEFT JOIN order_items i ON i.order_id=o.id
              LEFT JOIN products p ON p.id=i.product_id
              LEFT JOIN cooperatives c ON c.id=p.coop_id
              LEFT JOIN shipment_events s_lat ON s_lat.id = (SELECT MAX(id) FROM shipment_events WHERE shipment_id=sh.id AND latitude IS NOT NULL)";

    if ($driver) {
        $ships = rows($query . ' WHERE sh.driver_id=? GROUP BY sh.id ORDER BY sh.id DESC', [$u['id']]);
    } elseif ($u['role'] === 'coop') {
        $ships = rows($query . ' WHERE p.coop_id=? GROUP BY sh.id ORDER BY sh.id DESC', [(int)($u['coop_id'] ?? 0)]);
    } elseif ($isBuyer) {
        $ships = rows($query . ' WHERE o.buyer_id=? GROUP BY sh.id ORDER BY sh.id DESC', [(int)($u['buyer_id'] ?? 0)]);
    } else {
        $ships = rows($query . ' GROUP BY sh.id ORDER BY sh.id DESC');
    }

    $assignable = $manager ? rows("SELECT id, order_code FROM orders WHERE status='approved' ORDER BY id DESC") : [];
    $drivers    = $manager ? rows("SELECT id, name FROM users WHERE role='driver' AND is_active=1 ORDER BY name") : [];
    $events     = $ships ? rows('SELECT ev.*, u.name AS actor_name FROM shipment_events ev JOIN users u ON u.id=ev.actor_id ORDER BY ev.id DESC LIMIT 300') : [];

    $by = [];
    foreach ($events as $ev) $by[$ev['shipment_id']][] = $ev;
?>

<!-- ASSETS LEAFLET MAP & LEAFLET ROUTING MACHINE (AUTO ROUTING OSRM) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-truck-front-fill"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Live Tracking Logistik & Auto-Routing OSRM</h5>
                <p class="mb-0 small text-white-50">Menarik garis rute otomatis dari Gudang Koperasi Sektor menuju Pabrik Industri Pembeli beserta estimasi jarak.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($manager): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-person-plus-fill me-2" style="color:#164B35"></i>Tugaskan Driver Armada ke Pesanan</h5>
        <form class="row g-3" method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="assign_driver">
            <div class="col-md-5">
                <?php select_field('Pilih Order Disetujui', 'id', array_column($assignable, 'order_code', 'id')); ?>
            </div>
            <div class="col-md-5">
                <?php select_field('Pilih Driver Armada', 'driver_id', array_column($drivers, 'name', 'id')); ?>
            </div>
            <div class="col-md-2 d-flex align-items-end mb-3">
                <button class="btn btn-coco w-100 fw-bold py-2 shadow-sm" style="border-radius:10px;"><i class="bi bi-send-fill me-1"></i> Assign Driver</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
<?php foreach ($ships as $s): 
    // Koordinat Asal Gudang Koperasi (Default Jakarta Pusat jika null)
    $coopLat = $s['coop_lat'] ? (float)$s['coop_lat'] : -6.1754;
    $coopLng = $s['coop_lng'] ? (float)$s['coop_lng'] : 106.8272;
    // Koordinat Tujuan Industri Pembeli (Default Cikarang jika null)
    $destLat = -6.2840; $destLng = 107.1513; 
    $currLat = $s['curr_lat'] ? (float)$s['curr_lat'] : $coopLat;
    $currLng = $s['curr_lng'] ? (float)$s['curr_lng'] : $coopLng;
?>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div>
                        <span class="badge bg-light text-dark border mb-1"><i class="bi bi-hash me-1"></i>PENGIRIMAN #<?= e($s['id']) ?></span>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($s['order_code']) ?></h4>
                        <div class="small text-muted mt-1"><i class="bi bi-building me-1"></i>Tujuan: <strong><?= e($s['company_name']) ?></strong> (<?= e($s['city']) ?>)</div>
                    </div>
                    <div><?= badge($s['status']) ?></div>
                </div>

                <!-- LIVE MAPS AUTO-ROUTING OSRM -->
                <div class="mb-3 rounded overflow-hidden border shadow-sm" style="height:250px; position:relative;">
                    <div id="shipMap<?= $s['id'] ?>" style="height:100%; width:100%;"></div>
                </div>

                <div class="bg-light p-3 rounded mb-3 small">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted d-block">Driver Armada:</span>
                            <strong class="text-dark"><i class="bi bi-person-badge me-1"></i><?= e($s['driver_name']) ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Asal Gudang Sektor:</span>
                            <strong class="text-dark"><i class="bi bi-shop me-1"></i><?= e($s['coop_name'] ?? 'Gudang Koperasi') ?></strong>
                        </div>
                    </div>
                </div>

                <!-- DRIVER UPDATE FORM -->
                <?php if ($driver && !in_array($s['status'], ['delivered', 'pickup_pending'], true)): ?>
                    <div class="card border-success border-opacity-50 mb-3" style="border-radius:12px; background:#F4F9F6;">
                        <div class="card-body p-3">
                            <strong class="text-success small d-block mb-2"><i class="bi bi-truck me-1"></i>Form Update Driver & Live GPS</strong>
                            <form method="post" enctype="multipart/form-data">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="shipment_event">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                
                                <div class="row g-2">
                                    <div class="col-12">
                                        <?php select_field('Pilih Status Rute Pengiriman', 'event_type', [
                                            'accepted'         => '1. Terima Tugas Rute Driver',
                                            'pickup_requested' => '2. Minta ACC Ambil Barang di Gudang Koperasi',
                                            'location'         => '3. Perbarui Live Posisi GPS (Truk Jalan)',
                                            'delivered'        => '4. Barang Sampai Industri (Upload Foto Bukti POD)'
                                        ]); ?>
                                    </div>
                                    <div class="col-12">
                                        <input type="text" class="form-control form-control-sm" name="note" required placeholder="Catatan posisi (misal: Tol Cikampek KM 20)">
                                    </div>
                                    <div class="col-6">
                                        <input class="form-control form-control-sm" id="lat<?= $s['id'] ?>" type="number" step="any" name="latitude" value="<?= $currLat ?>">
                                    </div>
                                    <div class="col-6">
                                        <input class="form-control form-control-sm" id="lng<?= $s['id'] ?>" type="number" step="any" name="longitude" value="<?= $currLng ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="getGPS(<?= $s['id'] ?>)"><i class="bi bi-crosshair me-1"></i> Ambil Koordinat HP Saya</button>
                                    </div>
                                    <div class="col-12">
                                        <input class="form-control form-control-sm" type="file" name="proof" accept=".jpg,.png,.pdf">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button class="btn btn-coco btn-sm w-100 fw-bold">Update Rute GPS Logistik</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ACC PICKUP GUDANG KOPERASI ANAK SEKTOR -->
                <?php if (($manager || $u['role'] === 'coop') && $s['status'] === 'pickup_pending'): ?>
                    <div class="card border-warning mb-3" style="border-radius:12px; background:#FFFDF3;">
                        <div class="card-body p-3">
                            <strong class="text-warning-emphasis d-block mb-1"><i class="bi bi-exclamation-circle me-1"></i>ACC Pickup Gudang Koperasi Sektor</strong>
                            <p class="small text-muted mb-2">Driver berada di lokasi gudang penampungan Koperasi Sektor untuk mengambil barang.</p>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="approve_pickup">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan serah terima fisik..." required></textarea>
                                <div class="d-flex gap-2">
                                    <button name="decision" value="approved" class="btn btn-success btn-sm fw-bold"><i class="bi bi-check-lg me-1"></i> ACC Pickup</button>
                                    <button name="decision" value="rejected" class="btn btn-outline-danger btn-sm fw-bold">Tolak</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <h6 class="fw-bold text-dark mb-2 small"><i class="bi bi-clock-history me-1 text-secondary"></i> Timeline Perjalanan Logistik</h6>
                <div style="max-height:160px; overflow-y:auto;" class="border rounded p-2 bg-light">
                <?php foreach ($by[$s['id']] ?? [] as $ev): ?>
                    <div class="p-2 mb-2 bg-white rounded shadow-sm border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="small text-dark"><?= e(status_text($ev['event_type'])) ?></strong>
                            <span class="small text-muted"><?= e($ev['created_at']) ?></span>
                        </div>
                        <p class="small mb-0 text-secondary"><?= e($ev['actor_name']) ?>: "<?= e($ev['note']) ?>"</p>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapId = 'shipMap<?= $s['id'] ?>';
    var map = L.map(mapId).setView([<?= $coopLat ?>, <?= $coopLng ?>], 11);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // OSRM Auto-Routing Control (Gudang Sektor -> Pabrik Buyer)
    L.Routing.control({
        waypoints: [
            L.latLng(<?= $coopLat ?>, <?= $coopLng ?>), // Asal Gudang Sektor
            L.latLng(<?= $destLat ?>, <?= $destLng ?>)  // Tujuan Pabrik Industri
        ],
        routeWhileDragging: false,
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,
        showAlternatives: false,
        lineOptions: {
            styles: [{color: '#164B35', opacity: 0.8, weight: 5}]
        }
    }).addTo(map);
});
</script>
<?php endforeach; ?>
</div>

<script>
function getGPS(id) {
    if (!navigator.geolocation) return alert('GPS tidak didukung.');
    navigator.geolocation.getCurrentPosition(p => {
        document.getElementById('lat' + id).value = p.coords.latitude.toFixed(7);
        document.getElementById('lng' + id).value = p.coords.longitude.toFixed(7);
    }, () => alert('Aktifkan GPS HP Anda.'));
}
</script>
<?php } ?>