<?php
function page_dashboard(array $u): void {
    $role = $u['role'];
    if (is_manager($u)) {
        $a = (int)val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
        $b = (int)val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
        $c = (int)val("SELECT COUNT(*) FROM orders WHERE status IN ('requested','approved','assigned','in_transit','delivered')");
        $d = (int)val("SELECT COUNT(*) FROM shipments WHERE status IN ('assigned','accepted','picked_up')");
        $recent = rows('SELECT o.*, bp.company_name FROM orders o JOIN buyer_profiles bp ON bp.id=o.buyer_id ORDER BY o.id DESC LIMIT 7');
        $message = 'Validasi mitra, pengolahan, pemesanan, dan pengiriman dalam satu pusat koordinasi.';
    } elseif ($role === 'supplier') {
        $a = (int)val('SELECT COUNT(*) FROM raw_batches WHERE supplier_id=?', [$u['supplier_id']]);
        $b = (int)val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='verified'", [$u['supplier_id']]);
        $c = (int)val('SELECT COUNT(*) FROM allocations a JOIN raw_batches r ON r.id=a.batch_id WHERE r.supplier_id=?', [$u['supplier_id']]);
        $d = (float)val('SELECT COALESCE(SUM(quantity),0) FROM raw_batches WHERE supplier_id=?', [$u['supplier_id']]);
        $recent = rows('SELECT b.batch_code code, r.name type, b.quantity, b.created_at FROM raw_batches b JOIN raw_types r ON r.id=b.raw_type_id WHERE b.supplier_id=? ORDER BY b.id DESC LIMIT 7', [$u['supplier_id']]);
        $message = 'Pantau input limbah dan koperasi pengolah yang menerima alokasi dari pusat.';
    } elseif ($role === 'coop') {
        $a = (int)val("SELECT COUNT(*) FROM allocations WHERE coop_id=? AND status='proposed'", [$u['coop_id']]);
        $b = (int)val('SELECT COUNT(*) FROM products WHERE coop_id=?', [$u['coop_id']]);
        $c = (float)val('SELECT COALESCE(SUM(stock),0) FROM products WHERE coop_id=?', [$u['coop_id']]);
        $d = (int)val('SELECT COUNT(*) FROM production_logs l JOIN products p ON p.id=l.product_id WHERE p.coop_id=?', [$u['coop_id']]);
        $recent = rows('SELECT p.name code, r.name type, p.stock quantity, p.created_at FROM products p JOIN raw_types r ON r.id=p.raw_type_id WHERE p.coop_id=? ORDER BY p.id DESC LIMIT 7', [$u['coop_id']]);
        $message = 'Terima pasokan, catat produksi, dan kelola stok produk jadi.';
    } elseif ($role === 'buyer') {
        $a = (int)val('SELECT COUNT(*) FROM orders WHERE buyer_id=?', [$u['buyer_id']]);
        $b = (int)val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='requested'", [$u['buyer_id']]);
        $c = (int)val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='completed'", [$u['buyer_id']]);
        $d = (float)val('SELECT COALESCE(SUM(total),0) FROM orders WHERE buyer_id=?', [$u['buyer_id']]);
        $recent = rows('SELECT order_code code, status type, total quantity, created_at FROM orders WHERE buyer_id=? ORDER BY id DESC LIMIT 7', [$u['buyer_id']]);
        $message = 'Temukan produk terverifikasi dan pantau seluruh pesanan Anda.';
    } else {
        $a = (int)val('SELECT COUNT(*) FROM shipments WHERE driver_id=?', [$u['id']]);
        $b = (int)val("SELECT COUNT(*) FROM shipments WHERE driver_id=? AND status='assigned'", [$u['id']]);
        $c = (int)val("SELECT COUNT(*) FROM shipments WHERE driver_id=? AND status='picked_up'", [$u['id']]);
        $d = (int)val("SELECT COUNT(*) FROM shipments WHERE driver_id=? AND status='delivered'", [$u['id']]);
        $recent = rows('SELECT o.order_code code, s.status type, o.total quantity, s.assigned_at created_at FROM shipments s JOIN orders o ON o.id=s.order_id WHERE s.driver_id=? ORDER BY s.id DESC LIMIT 7', [$u['id']]);
        $message = 'Terima tugas dan laporkan pengambilan, lokasi, serta bukti serah terima.';
    }
?>

<div class="hero-panel mb-4 p-4" style="background: linear-gradient(135deg, #164B35 0%, #24362E 100%); border-radius:16px; color:white;">
    <span class="text-uppercase fw-bold" style="letter-spacing:2px; font-size:11px; color:#C7ED8D">CIRCULAR IMPACT OPERATIONS</span>
    <h2 style="font-weight:800; margin-top:8px">Satu jaringan. Banyak nilai baru.</h2>
    <p class="mt-2 mb-3 text-white-50"><?= e($message) ?></p>
    <a class="btn btn-lime text-dark fw-bold" href="<?= e(url('maps')) ?>">Lihat peta jaringan <span class="ms-1">↗</span></a>
</div>

<div class="row g-3 mb-4">
<?php
    if (is_manager($u)) {
        kpi('PT Terverifikasi', $a, 'building-2');
        kpi('Koperasi Anak Aktif', $b, 'network');
        kpi('Pesanan Berjalan', $c, 'shopping-bag');
        kpi('Kiriman Aktif', $d, 'truck');
    } elseif ($role === 'supplier') {
        kpi('Batch Bahan Mentah', $a, 'package');
        kpi('Koperasi Terverifikasi', $b, 'network');
        kpi('Alokasi Tercatat', $c, 'arrow-right-left');
        kpi('Total Limbah (kg)', number($d), 'leaf');
    } elseif ($role === 'coop') {
        kpi('Alokasi Menunggu', $a, 'clock');
        kpi('Produk Terdaftar', $b, 'box');
        kpi('Stok Produk (kg)', number($c), 'boxes');
        kpi('Catatan Produksi', $d, 'factory');
    } elseif ($role === 'buyer') {
        kpi('Total Pesanan', $a, 'shopping-cart');
        kpi('Menunggu Verifikasi', $b, 'clock');
        kpi('Pesanan Selesai', $c, 'check-circle');
        kpi('Nilai Pesanan', money($d), 'wallet');
    } else {
        kpi('Total Tugas', $a, 'truck');
        kpi('Tugas Baru', $b, 'bell');
        kpi('Dalam Perjalanan', $c, 'route');
        kpi('Selesai', $d, 'check-circle');
    }
?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel p-3 bg-white border" style="border-radius:16px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Aktivitas Terkini</h5>
                <span class="chip">Pembaruan otomatis</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Referensi</th>
                            <th>Keterangan</th>
                            <th>Nilai / Kuantitas</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['order_code'] ?? $r['code'] ?? '—') ?></td>
                            <td><?= isset($r['status']) ? badge($r['status']) : e($r['company_name'] ?? $r['type'] ?? '—') ?></td>
                            <td><?= isset($r['total']) ? money($r['total']) : (isset($r['quantity']) ? number($r['quantity']) : '—') ?></td>
                            <td class="text-muted small"><?= e($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$recent) table_empty('Belum ada aktivitas.'); ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="panel p-3 bg-white border h-100" style="border-radius:16px;">
            <h5 class="fw-bold mb-3">Jejak Rantai Pasok</h5>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>01 &bull; PT Pemasok</span>
                <span class="badge bg-light text-dark">Bahan Mentah</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>02 &bull; Koperasi Induk</span>
                <span class="badge bg-light text-dark">Validasi</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>03 &bull; Koperasi Anak</span>
                <span class="badge bg-light text-dark">Produksi</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>04 &bull; Pembeli Industri</span>
                <span class="badge bg-light text-dark">Pesanan</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span>05 &bull; Driver Mitra</span>
                <span class="badge bg-light text-dark">Pengiriman</span>
            </div>
            <p class="helper mt-3 mb-0 small text-muted">Data terhubung langsung dengan sistem database terkini CoCoLink.</p>
        </div>
    </div>
</div>
<?php } ?>