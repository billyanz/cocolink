<?php
function page_shipments(array $u): void {
    $manager = is_manager($u);
    $driver  = $u['role'] === 'driver';

    $query = "SELECT sh.*, o.order_code, o.status AS order_status, o.shipping_address, o.city, o.total, b.company_name, 
              us.name AS driver_name, us.email AS driver_email, 
              (SELECT CONCAT(c.name, ' — ', c.address, ', ', c.city) 
               FROM order_items i 
               JOIN products p ON p.id=i.product_id 
               JOIN cooperatives c ON c.id=p.coop_id 
               WHERE i.order_id=o.id LIMIT 1) AS pickup_address 
              FROM shipments sh 
              JOIN orders o ON o.id=sh.order_id 
              JOIN buyer_profiles b ON b.id=o.buyer_id 
              JOIN users us ON us.id=sh.driver_id";

    if ($driver) {
        $ships = rows($query . ' WHERE sh.driver_id=? ORDER BY sh.id DESC', [$u['id']]);
    } elseif ($u['role'] === 'coop') {
        $ships = rows($query . ' WHERE EXISTS(SELECT 1 FROM order_items i JOIN products p ON p.id=i.product_id WHERE i.order_id=o.id AND p.coop_id=?) ORDER BY sh.id DESC', [$u['coop_id']]);
    } elseif ($u['role'] === 'supplier') {
        $ships = rows($query . ' WHERE EXISTS(SELECT 1 FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=o.id AND c.supplier_id=?) ORDER BY sh.id DESC', [$u['supplier_id']]);
    } elseif ($u['role'] === 'buyer') {
        $ships = rows($query . ' WHERE o.buyer_id=? ORDER BY sh.id DESC', [$u['buyer_id']]);
    } else {
        $ships = rows($query . ' ORDER BY sh.id DESC');
    }

    $assignable = $manager ? rows("SELECT id, order_code FROM orders WHERE status='approved' ORDER BY id DESC") : [];
    $drivers    = $manager ? rows("SELECT id, name FROM users WHERE role='driver' AND is_active=1 ORDER BY name") : [];
    $events     = $ships ? rows('SELECT ev.*, u.name AS actor_name FROM shipment_events ev JOIN users u ON u.id=ev.actor_id ORDER BY ev.id DESC LIMIT 300') : [];

    $by = [];
    foreach ($events as $ev) $by[$ev['shipment_id']][] = $ev;

    if ($manager):
?>
<div class="panel p-4 bg-white border mb-4" style="border-radius:16px;">
    <h5 class="fw-bold mb-1"><i class="bi bi-person-plus-fill me-2" style="color:var(--coco-primary)"></i>Tugaskan Driver Mitra ke Pesanan</h5>
    <p class="helper text-muted small mb-3">Driver hanya dapat ditugaskan untuk pesanan yang telah divalidasi oleh Koperasi Induk.</p>
    <form class="row g-3" method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="assign_driver">
        <div class="col-md-5">
            <?php select_field('Pesanan Siap Kirim', 'id', array_column($assignable, 'order_code', 'id')); ?>
        </div>
        <div class="col-md-5">
            <?php select_field('Driver Logistik Aktif', 'driver_id', array_column($drivers, 'name', 'id')); ?>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-coco w-100 fw-bold py-2"><i class="bi bi-send-fill me-1"></i> Assign Driver</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($ships as $s): ?>
    <div class="col-xl-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="chip"><i class="bi bi-hash me-1"></i>PENGIRIMAN #<?= e($s['id']) ?></span>
                    <h5 class="fw-bold mt-2 mb-1" style="color:var(--coco-primary)"><?= e($s['order_code']) ?></h5>
                    <div class="helper text-muted small"><i class="bi bi-building me-1"></i><?= e($s['company_name']) ?> &rarr; <?= e($s['city']) ?></div>
                </div>
                <div><?= badge($s['status']) ?></div>
            </div>
            
            <hr>

            <div class="row g-2 mb-3 small">
                <div class="col-6">
                    <div class="text-muted">Driver Mitra:</div>
                    <strong><i class="bi bi-person-badge me-1"></i><?= e($s['driver_name']) ?></strong>
                </div>
                <div class="col-6">
                    <div class="text-muted">Alamat Tujuan:</div>
                    <strong><i class="bi bi-geo-alt me-1"></i><?= e($s['shipping_address']) ?></strong>
                </div>
                <div class="col-12 mt-2">
                    <div class="text-muted">Titik Pickup Koperasi Anak:</div>
                    <strong class="text-success"><i class="bi bi-shop me-1"></i><?= e($s['pickup_address'] ?? 'Belum tersedia') ?></strong>
                </div>
            </div>

            <!-- FORM DRIVER UPDATE STATUS & GPS -->
            <?php if ($driver && !in_array($s['status'], ['delivered', 'pickup_pending'], true)): 
                $next = match($s['status']) {
                    'assigned' => 'accepted',
                    'accepted' => 'pickup_requested',
                    'picked_up' => 'delivered',
                    default => ''
                };
            ?>
                <form method="post" enctype="multipart/form-data" class="p-3 mb-3 border" style="background:#F6F8F4; border-radius:12px;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="shipment_event">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    
                    <div class="row g-2">
                        <div class="col-12">
                            <?php select_field('Aksi Tugas Driver', 'event_type', array_filter([
                                'accepted' => 'Terima Tugas Pengiriman',
                                'pickup_requested' => 'Ajukan Pickup Barang (Minta ACC Koperasi)',
                                'location' => 'Update Posisi GPS / Lokasi Live',
                                'delivered' => 'Barang Sampai Tujuan (Upload Proof)'
                            ], fn($k) => $k === $next || ($k === 'location' && in_array($s['status'], ['accepted', 'picked_up'], true)), ARRAY_FILTER_USE_KEY)); ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Keterangan / Kondisi Muatan</label>
                            <textarea class="form-control form-control-sm" name="note" rows="2" required placeholder="Contoh: Barang lengkap, kondisi segel aman"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Latitude</label>
                            <input class="form-control form-control-sm" id="lat<?= $s['id'] ?>" type="number" step="any" name="latitude">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Longitude</label>
                            <input class="form-control form-control-sm" id="lng<?= $s['id'] ?>" type="number" step="any" name="longitude">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="getGPS(<?= $s['id'] ?>)">
                                <i class="bi bi-crosshair me-1"></i> Ambil Koordinat GPS HP Saya
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Bukti Foto / Lampiran PDF</label>
                            <input class="form-control form-control-sm" type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <div class="col-12 mt-2">
                            <button class="btn btn-coco btn-sm w-100 fw-bold">Kirim Update Pengiriman</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- FORM KOPERASI ACC PICKUP DRIVER -->
            <?php if (($manager || $u['role'] === 'coop') && $s['status'] === 'pickup_pending'): ?>
                <div class="p-3 mb-3 border border-warning" style="background:#FFFDF0; border-radius:12px;">
                    <strong class="text-dark"><i class="bi bi-exclamation-square-fill text-warning me-1"></i>Verifikasi Penyerahan Barang (ACC Pickup)</strong>
                    <p class="helper small text-muted mt-1 mb-2">Driver berada di lokasi pickup. Periksa fisik dan jumlah limbah/produk sebelum menyetujui.</p>
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="approve_pickup">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan serah terima / alasan..." required></textarea>
                        <div class="d-flex gap-2">
                            <button name="decision" value="approved" class="btn btn-coco btn-sm fw-bold"><i class="bi bi-check-lg me-1"></i> ACC Pickup</button>
                            <button name="decision" value="rejected" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-x-lg me-1"></i> Tolak Pickup</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="helper fw-bold mb-2 small text-uppercase" style="letter-spacing:0.5px; color:#64748B"><i class="bi bi-clock-history me-1"></i>Timeline Perjalanan Logistik</div>
            <div style="max-height:240px; overflow-y:auto;" class="border rounded p-2 bg-light">
            <?php foreach ($by[$s['id']] ?? [] as $ev): ?>
                <div class="pb-2 mb-2 border-bottom bg-white p-2 rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="small text-dark"><?= e(status_text($ev['event_type'])) ?></strong>
                        <span class="helper small text-muted"><?= e($ev['created_at']) ?></span>
                    </div>
                    <p class="helper small mb-1 text-secondary"><?= e($ev['actor_name']) ?>: <?= e($ev['note']) ?></p>
                    <div class="d-flex gap-2">
                        <?php if ($ev['latitude'] !== null): ?>
                            <a target="_blank" class="small text-decoration-none" rel="noopener" href="https://www.openstreetmap.org/?mlat=<?= e($ev['latitude']) ?>&mlon=<?= e($ev['longitude']) ?>#map=15/<?= e($ev['latitude']) ?>/<?= e($ev['longitude']) ?>">
                                <i class="bi bi-geo-alt-fill me-1"></i>Peta GPS
                            </a>
                        <?php endif; ?>
                        <?php if ($ev['proof_path']): ?>
                            <a class="small text-decoration-none text-success fw-bold" href="<?= e(app_base() . '/download.php?id=' . $ev['id']) ?>" target="_blank">
                                <i class="bi bi-file-earmark-arrow-down-fill me-1"></i>Bukti Foto / PDF ↗
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$ships): ?>
    <div class="panel p-4 bg-white border" style="border-radius:16px;">
        <?php table_empty('Belum ada tugas pengiriman logistik.'); ?>
    </div>
<?php endif; ?>

<script>
function getGPS(id) {
    if (!navigator.geolocation) return alert('GPS tidak didukung pada browser Anda.');
    navigator.geolocation.getCurrentPosition(p => {
        document.getElementById('lat' + id).value = p.coords.latitude.toFixed(7);
        document.getElementById('lng' + id).value = p.coords.longitude.toFixed(7);
    }, () => alert('Gagal mengambil koordinat lokasi. Pastikan GPS aktif.'));
}
</script>
<?php } ?>