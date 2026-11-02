<?php
function page_reports(): void {
    $stats = rows("SELECT DATE(created_at) AS day, COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS sales 
                   FROM orders 
                   WHERE status NOT IN ('rejected','requested') 
                   GROUP BY DATE(created_at) 
                   ORDER BY day DESC LIMIT 30");
?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <div class="icon-chip mb-3 bg-light p-2 rounded-circle d-inline-block text-success fs-4"><i class="bi bi-receipt"></i></div>
            <h5 class="fw-bold mb-1">Rekapitulasi Transaksi Pesanan</h5>
            <p class="helper text-muted small mb-3">Laporan seluruh pesanan industri, nilai transaksi, status operasional, dan invoice.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-coco btn-sm fw-bold" target="_blank" href="<?= e(app_base() . '/report.php?type=orders&format=pdf') ?>">
                    <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                </a>
                <a class="btn btn-outline-coco btn-sm fw-bold" href="<?= e(app_base() . '/report.php?type=orders&format=xlsx') ?>">
                    <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <div class="icon-chip mb-3 bg-light p-2 rounded-circle d-inline-block text-success fs-4"><i class="bi bi-box-seam"></i></div>
            <h5 class="fw-bold mb-1">Rekapitulasi Bahan Mentah Pasokan</h5>
            <p class="helper text-muted small mb-3">Ringkasan limbah per batch, sumber PT Pemasok, jenis limbah, kuantitas awal, dan alokasi.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-coco btn-sm fw-bold" target="_blank" href="<?= e(app_base() . '/report.php?type=batches&format=pdf') ?>">
                    <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                </a>
                <a class="btn btn-outline-coco btn-sm fw-bold" href="<?= e(app_base() . '/report.php?type=batches&format=xlsx') ?>">
                    <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <div class="icon-chip mb-3 bg-light p-2 rounded-circle d-inline-block text-success fs-4"><i class="bi bi-boxes"></i></div>
            <h5 class="fw-bold mb-1">Inventaris Produk Olahan Koperasi</h5>
            <p class="helper text-muted small mb-3">Stok produk jadi, harga jual per kg, identitas Koperasi Anak pengolah, dan grade.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-coco btn-sm fw-bold" target="_blank" href="<?= e(app_base() . '/report.php?type=products&format=pdf') ?>">
                    <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                </a>
                <a class="btn btn-outline-coco btn-sm fw-bold" href="<?= e(app_base() . '/report.php?type=products&format=xlsx') ?>">
                    <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <div class="icon-chip mb-3 bg-light p-2 rounded-circle d-inline-block text-success fs-4"><i class="bi bi-truck-front-fill"></i></div>
            <h5 class="fw-bold mb-1">Rekapitulasi Pengiriman Logistik</h5>
            <p class="helper text-muted small mb-3">Tugas pengiriman driver mitra, status transit, lokasi koordinat GPS, dan bukti foto.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-coco btn-sm fw-bold" target="_blank" href="<?= e(app_base() . '/report.php?type=shipments&format=pdf') ?>">
                    <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                </a>
                <a class="btn btn-outline-coco btn-sm fw-bold" href="<?= e(app_base() . '/report.php?type=shipments&format=xlsx') ?>">
                    <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                </a>
            </div>
        </div>
    </div>
</div>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Tren Penjualan Per Tanggal (30 Hari Terakhir)</h5>
        <span class="chip">Data Tervalidasi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal Transaksi</th>
                    <th>Jumlah Order Tervalidasi</th>
                    <th>Total Nilai Order (Rp)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $r): ?>
                <tr>
                    <td class="fw-bold"><?= e($r['day']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($r['total_orders']) ?> Order</span></td>
                    <td class="fw-bold text-success"><?= money($r['sales']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$stats) table_empty('Belum ada transaksi yang tervalidasi.'); ?>
    </div>
</div>
<?php } ?>