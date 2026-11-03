<?php
function page_shipments(array $u): void {
    $isSuper = $u['role'] === 'super_admin';
    $isInduk = $u['role'] === 'induk';
    $manager = $isSuper || $isInduk;
    $driver  = $u['role'] === 'driver';
    $isBuyer = $u['role'] === 'buyer';

    // FIX: Mengubah o.city menjadi b.city AS city (kolom city berasal dari buyer_profiles)
    $query = "SELECT sh.*, o.order_code, o.status AS order_status, o.shipping_address, b.city AS city, o.total, b.company_name, 
              us.name AS driver_name, 
              c.name AS coop_name, c.address AS coop_address, c.city AS coop_city, c.latitude AS coop_lat, c.longitude AS coop_lng,
              s_lat.latitude AS curr_lat, s_lat.longitude AS curr_lng
              FROM shipments sh 
              JOIN orders o ON o.id=sh.order_id 
              JOIN buyer_profiles b ON b.id=o.buyer_id 
              LEFT JOIN users us ON us.id=sh.driver_id
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<style>
    .shipments-container { font-family: 'Plus Jakarta Sans', sans-serif; color: #0F172A; }
    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        border-radius: 20px; color: white; box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    .btn-ocean-primary {
        background-color: #0077B6; color: #FFFFFF; font-weight: 600; border-radius: 10px; border: none; padding: 10px 22px;
    }
    .btn-ocean-primary:hover { background-color: #023E8A; }
</style>

<div class="shipments-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-truck-front-fill"></i></div>
                <div>
                    <h4 class="fw-bold mb-1 text-white">Live Tracking Logistik &amp; Auto-Routing OSRM</h4>
                    <p class="mb-0 small text-white-50">Tracing rute pengiriman armada dari Gudang Koperasi Anak ke Pabrik Industri Pembeli B2B.</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($manager): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:18px; border: 1px solid #E2E8F0;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-person-plus-fill me-2" style="color:#0077B6"></i>Tugaskan Driver Armada ke Pesanan</h5>
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
                    <button class="btn btn-ocean-primary w-100 fw-bold py-2 shadow-sm"><i class="bi bi-send-fill me-1"></i> Tugaskan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
    <?php foreach ($ships as $s): 
        $coopLat = $s['coop_lat'] ? (float)$s['coop_lat'] : -6.5971;
        $coopLng = $s['coop_lng'] ? (float)$s['coop_lng'] : 106.7996;
        $destLat = -6.2000; $destLng = 106.8166; 
        $currLat = $s['curr_lat'] ? (float)$s['curr_lat'] : $coopLat;
        $currLng = $s['curr_lng'] ? (float)$s['curr_lng'] : $coopLng;
    ?>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:18px; border:1px solid #E2E8F0;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                        <div>
                            <span class="badge bg-light text-dark border mb-1"><i class="bi bi-hash me-1"></i>PENGIRIMAN #<?= e($s['id']) ?></span>
                            <h4 class="fw-bold mb-0 text-dark"><?= e($s['order_code']) ?></h4>
                            <div class="small text-muted mt-1"><i class="bi bi-building me-1"></i>Tujuan: <strong><?= e($s['company_name']) ?></strong> (<?= e($s['city']) ?>)</div>
                        </div>
                        <div><?= badge($s['status']) ?></div>
                    </div>

                    <div class="mb-3 rounded overflow-hidden border shadow-sm" style="height:250px; position:relative;">
                        <div id="shipMap<?= $s['id'] ?>" style="height:100%; width:100%;"></div>
                    </div>

                    <div class="bg-light p-3 rounded mb-3 small">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted d-block">Driver Armada:</span>
                                <strong class="text-dark"><i class="bi bi-person-badge me-1"></i><?= e($s['driver_name'] ?: 'Belum Ditugaskan') ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Asal Gudang Sektor:</span>
                                <strong class="text-dark"><i class="bi bi-shop me-1"></i><?= e($s['coop_name'] ?? 'Koperasi Anak') ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php if ($driver && !in_array($s['status'], ['delivered', 'pickup_pending'], true)): ?>
                        <div class="card border-primary border-opacity-50 mb-3" style="border-radius:12px; background:#F0F9FF;">
                            <div class="card-body p-3">
                                <strong class="text-primary small d-block mb-2"><i class="bi bi-truck me-1"></i>Update Posisi Rute Driver &amp; GPS Live</strong>
                                <form method="post" enctype="multipart/form-data">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="shipment_event">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <?php select_field('Pilih Status Pengiriman', 'event_type', [
                                                'accepted'         => '1. Terima Rute Pengiriman',
                                                'pickup_requested' => '2. Minta ACC Pickup di Gudang Koperasi Anak',
                                                'location'         => '3. Perbarui Lokasi Truk (Sedang Perjalanan)',
                                                'delivered'        => '4. Barang Sampai Industri Pembeli (Upload POD)'
                                            ]); ?>
                                        </div>
                                        <div class="col-12">
                                            <input type="text" class="form-control form-control-sm" name="note" required placeholder="Catatan posisi (misal: Tol Jagorawi KM 15)">
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
                                            <button class="btn btn-ocean-primary btn-sm w-100 fw-bold">Update Rute Logistik</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h6 class="fw-bold text-dark mb-2 small"><i class="bi bi-clock-history me-1 text-secondary"></i> Timeline Perjalanan Logistik</h6>
                    <div style="max-height:160px; overflow-y:auto;" class="border rounded p-2 bg-light">
                    <?php foreach ($by[$s['id']] ?? [] as $ev): ?>
                        <div class="p-2 mb-2 bg-white rounded shadow-sm border-start border-4 border-primary">
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
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        L.Routing.control({
            waypoints: [
                L.latLng(<?= $coopLat ?>, <?= $coopLng ?>),
                L.latLng(<?= $destLat ?>, <?= $destLng ?>)
            ],
            routeWhileDragging: false, addWaypoints: false, draggableWaypoints: false,
            fitSelectedRoutes: true, showAlternatives: false,
            lineOptions: { styles: [{color: '#0077B6', opacity: 0.8, weight: 5}] }
        }).addTo(map);
    });
    </script>
    <?php endforeach; ?>

    <?php if (!$ships): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center" style="border-radius:18px; border:1px solid #E2E8F0;">
                <i class="bi bi-truck fs-1 text-muted mb-2"></i>
                <h5 class="fw-bold text-dark">Belum Ada Data Pengiriman Logistik</h5>
                <p class="text-secondary small mb-0">Pesanan yang disetujui akan muncul di sini untuk ditugaskan ke driver.</p>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
function getGPS(id) {
    if (!navigator.geolocation) return alert('GPS tidak didukung.');
    navigator.geolocation.getCurrentPosition(p => {
        document.getElementById('lat' + id).value = p.coords.latitude.toFixed(7);
        document.getElementById('lng' + id).value = p.coords.longitude.toFixed(7);
    }, () => alert('Aktifkan akses GPS HP Anda.'));
}
</script>
<?php } ?>