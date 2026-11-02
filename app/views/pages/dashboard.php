<?php
function page_dashboard(array $u): void {
    $roleName = match($u['role']) {
        'super_admin' => 'Super Admin Platform (Master Monitoring)',
        'induk'       => 'Admin Koperasi Induk Metropolitan',
        'supplier'    => 'PT Pemasok Limbah Utama',
        'coop'        => 'Koperasi Anak Olahan Sektor Wilayah',
        'buyer'       => 'Pembeli Industri B2B',
        'driver'      => 'Driver Armada Logistik',
        default       => strtoupper($u['role'])
    };

    $isSuper  = $u['role'] === 'super_admin';
    $isInduk  = $u['role'] === 'induk';
    $isSupplier = in_array($u['role'], ['supplier', 'induk'], true);
    $isCoop   = $u['role'] === 'coop';
    $isBuyer  = $u['role'] === 'buyer';
    $isDriver = $u['role'] === 'driver';

    // Perhitungan KPI Dinamis Terisolasi Per Role
    if ($isSuper || $isInduk) {
        $totSuppliers = val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM raw_batches");
        $onGoing      = val("SELECT COUNT(*) FROM orders WHERE status IN ('approved','assigned','in_transit','pickup_pending')");
        $completed    = val("SELECT COUNT(*) FROM orders WHERE status='completed'");
    } elseif ($u['supplier_id']) {
        $sid = (int)$u['supplier_id'];
        $totSuppliers = 1;
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='verified'", [$sid]);
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM raw_batches WHERE supplier_id=?", [$sid]);
        $onGoing      = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE c.supplier_id=? AND o.status IN ('approved','assigned','in_transit','pickup_pending')", [$sid]);
        $completed    = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE c.supplier_id=? AND o.status='completed'", [$sid]);
    } elseif ($isCoop) {
        $cid = (int)$u['coop_id'];
        $totSuppliers = 1;
        $totCoops     = 1;
        $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM allocations WHERE coop_id=? AND status='accepted'", [$cid]);
        $onGoing      = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id WHERE p.coop_id=? AND o.status IN ('approved','assigned','in_transit','pickup_pending')", [$cid]);
        $completed    = val("SELECT COUNT(*) FROM orders o JOIN order_items i ON i.order_id=o.id JOIN products p ON p.id=i.product_id WHERE p.coop_id=? AND o.status='completed'", [$cid]);
    } else {
        $totSuppliers = val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
        $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
        $totBatches   = 0;
        $onGoing      = val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status IN ('approved','assigned','in_transit')", [(int)($u['buyer_id'] ?? 0)]);
        $completed    = val("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='completed'", [(int)($u['buyer_id'] ?? 0)]);
    }
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #22694E 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-1 mb-2 fw-semibold" style="border-radius:20px;">
                    <i class="bi bi-shield-lock-fill me-1"></i> Peran Akun: <?= e($roleName) ?>
                </span>
                <h3 class="fw-bold mb-1 text-white">Selamat Datang, <?= e($u['name']) ?>! 👋</h3>
                <p class="mb-0 text-white-50 small">Pusat Monitoring Ekosistem Sirkular Kelapa CoCoLink Wilayah Jabodetabek.</p>
            </div>
            <a href="<?= url('maps') ?>" class="btn btn-light fw-bold px-4 py-2" style="border-radius:10px; color:#164B35;">
                <i class="bi bi-map-fill me-1"></i> Buka Peta Live Logistik
            </a>
        </div>
    </div>
</div>

<!-- DASHBOARD MONITORING KPI UTAMA -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <?php kpi('Transaksi On-Going', $onGoing, 'bi-hourglass-split', 'text-warning bg-warning'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi('Transaksi Selesai', $completed, 'bi-check-circle-fill', 'text-success bg-success'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi($isCoop ? 'Volume Pasokan Diterima (kg)' : 'Total Pasokan Limbah (kg)', $totBatches, 'bi-box-seam-fill', 'text-info bg-info'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi('Koperasi Sektor Aktif', $totCoops, 'bi-shop', 'text-primary bg-primary'); ?>
    </div>
</div>

<!-- SUMMARY MONITORING DARI HULU KE HILIR -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill me-2" style="color:#164B35"></i>Alur Operasional Sirkular Ekosistem Jabodetabek</h5>
        
        <div class="row g-3">
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-building text-success me-1"></i> 1. Koperasi Induk & PT Utama</strong>
                    <p class="small text-muted mb-0">PT Pemasok Utama mengumpulkan limbah mentah dan didampingi Koperasi Induk Metropolitan untuk legalitas.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-geo-alt text-primary me-1"></i> 2. Distro 5 Anak Sektor</strong>
                    <p class="small text-muted mb-0">Pasokan limbah dialokasikan ke 5 Koperasi Anak per sektor wilayah (JakTim, JakSel, JakBar, JakPus, JakUt).</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-tools text-warning me-1"></i> 3. Olahan Barang B2B</strong>
                    <p class="small text-muted mb-0">Koperasi Sektor mengolah limbah menjadi produk siap pakai (Sapu Lidi, Briket) dan diposting ke katalog B2B.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-truck text-danger me-1"></i> 4. Live Tracking Driver</strong>
                    <p class="small text-muted mb-0">Driver armada mengambil barang di gudang sektor dan mengantarkan ke Industri Pembeli dengan peta live GPS.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>