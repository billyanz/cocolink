<?php
function page_dashboard(array $u): void {
    $roleName = match($u['role']) {
        'super_admin' => 'Super Admin Platform (Master Monitoring)',
        'induk'       => 'Admin Koperasi Induk',
        'supplier'    => 'PT Pemasok Limbah Utama',
        'coop'        => 'Koperasi Anak Olahan (Produsen)',
        'buyer'       => 'Pembeli Industri B2B',
        'driver'      => 'Driver Armada Logistik',
        default       => strtoupper($u['role'])
    };

    // Data Monitoring Real-Time untuk Dashboard
    $totSuppliers = val("SELECT COUNT(*) FROM suppliers WHERE status='verified'");
    $totCoops     = val("SELECT COUNT(*) FROM cooperatives WHERE status='verified'");
    $totBatches   = val("SELECT COALESCE(SUM(quantity),0) FROM raw_batches");
    $onGoing      = val("SELECT COUNT(*) FROM orders WHERE status IN ('approved','assigned','in_transit')");
    $completed    = val("SELECT COUNT(*) FROM orders WHERE status='completed'");
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #22694E 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-1 mb-2 fw-semibold" style="border-radius:20px;">
                    <i class="bi bi-shield-lock-fill me-1"></i> Peran Akun: <?= e($roleName) ?>
                </span>
                <h3 class="fw-bold mb-1 text-white">Selamat Datang, <?= e($u['name']) ?>! 👋</h3>
                <p class="mb-0 text-white-50 small">Pusat Monitoring Ekosistem Sirkular Kelapa CoCoLink dari Hulu ke Hilir.</p>
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
        <?php kpi('Transaksi On-Going (Berjalan)', $onGoing, 'bi-hourglass-split', 'text-warning bg-warning'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi('Transaksi Selesai (Completed)', $completed, 'bi-check-circle-fill', 'text-success bg-success'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi('PT Pemasok Terverifikasi', $totSuppliers, 'bi-building-check', 'text-info bg-info'); ?>
    </div>
    <div class="col-md-3">
        <?php kpi('Koperasi Anak Produsen', $totCoops, 'bi-shop', 'text-primary bg-primary'); ?>
    </div>
</div>

<!-- SUMMARY MONITORING DARI HULU KE HILIR -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill me-2" style="color:#164B35"></i>Alur Bisnis Sirkular Kelapa Terintegrasi</h5>
        
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-shield-check text-success me-1"></i> 1. Verifikasi Super Admin</strong>
                    <p class="small text-muted mb-0">Super Admin memverifikasi PT Pemasok yang didaftarkan Admin Induk setelah memastikan minimal 5 Koperasi Anak binaan valid.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-tools text-warning me-1"></i> 2. Produksi Koperasi Anak</strong>
                    <p class="small text-muted mb-0">Koperasi Anak mengolah pasokan limbah mentah menjadi barang olahan (contoh: Sapu Lidi) dan mempostingnya ke katalog produk.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded bg-light">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-truck text-primary me-1"></i> 3. Logistik Rute Live</strong>
                    <p class="small text-muted mb-0">Pembeli memantau pergerakan truk driver di peta *live tracking* dari gudang Koperasi Anak sampai barang tiba di industri.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>