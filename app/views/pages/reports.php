<?php
declare(strict_types=1);

function page_reports(array $u): void {
    must_role(['super_admin', 'induk', 'supplier', 'coop']);
    $canExportReports = in_array($u['role'], ['super_admin', 'induk'], true);

    // Query Tren Penjualan 30 Hari Terakhir
    if ($u['role'] === 'supplier') {
        $supplierId = (int)($u['supplier_id'] ?? 0);
        $stats = rows("SELECT DATE(o.created_at) AS day, COUNT(*) AS total_orders, COALESCE(SUM(o.total),0) AS sales
                       FROM orders o
                       WHERE o.status NOT IN ('rejected','requested')
                         AND EXISTS (
                             SELECT 1 FROM order_items i
                             JOIN products p ON p.id=i.product_id
                             JOIN cooperatives c ON c.id=p.coop_id
                             WHERE i.order_id=o.id AND c.supplier_id=?
                         )
                         AND NOT EXISTS (
                             SELECT 1 FROM order_items i
                             LEFT JOIN products p ON p.id=i.product_id
                             LEFT JOIN cooperatives c ON c.id=p.coop_id
                             WHERE i.order_id=o.id AND (p.id IS NULL OR c.id IS NULL OR c.supplier_id<>?)
                         )
                       GROUP BY DATE(o.created_at)
                       ORDER BY day DESC LIMIT 30", [$supplierId, $supplierId]);
    } elseif ($u['role'] === 'coop') {
        $coopId = (int)($u['coop_id'] ?? 0);
        $stats = rows("SELECT DATE(o.created_at) AS day, COUNT(*) AS total_orders, COALESCE(SUM(o.total),0) AS sales
                       FROM orders o
                       WHERE o.status NOT IN ('rejected','requested')
                         AND EXISTS (
                             SELECT 1 FROM order_items i
                             JOIN products p ON p.id=i.product_id
                             WHERE i.order_id=o.id AND p.coop_id=?
                         )
                         AND NOT EXISTS (
                             SELECT 1 FROM order_items i
                             LEFT JOIN products p ON p.id=i.product_id
                             WHERE i.order_id=o.id AND (p.id IS NULL OR p.coop_id<>?)
                         )
                       GROUP BY DATE(o.created_at)
                       ORDER BY day DESC LIMIT 30", [$coopId, $coopId]);
    } else {
        $stats = rows("SELECT DATE(created_at) AS day, COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS sales
                       FROM orders
                       WHERE status NOT IN ('rejected','requested')
                       GROUP BY DATE(created_at)
                       ORDER BY day DESC LIMIT 30");
    }

    $totalSales = array_sum(array_column($stats, 'sales'));
    $totalCount = array_sum(array_column($stats, 'total_orders'));
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .reports-page-container {
        font-family: 'Plus Jakarta Sans', sans-serif; color: #0F172A;
        animation: fadeIn 0.4s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        position: relative; overflow: hidden; border-radius: 20px;
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28); color: white;
    }
    .role-badge-crisp {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35); color: #FFFFFF;
        padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    }
    .banner-title { color: #FFFFFF; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.5px; }
    .banner-subtitle { color: #E0F2FE; font-size: 0.95rem; font-weight: 500; }

    .report-card-item {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px;
        transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .report-card-item:hover {
        border-color: #0077B6; transform: translateY(-3px); box-shadow: 0 8px 20px -4px rgba(0, 119, 182, 0.12);
    }

    .btn-coco-pdf {
        background-color: #DC2626; color: #FFFFFF; font-weight: 700; border-radius: 10px; border: none;
        padding: 8px 16px; font-size: 0.85rem; transition: all 0.2s ease;
    }
    .btn-coco-pdf:hover { background-color: #991B1B; color: #FFFFFF; }

    .btn-coco-excel {
        background-color: #16A34A; color: #FFFFFF; font-weight: 700; border-radius: 10px; border: none;
        padding: 8px 16px; font-size: 0.85rem; transition: all 0.2s ease;
    }
    .btn-coco-excel:hover { background-color: #15803D; color: #FFFFFF; }

    .table-wrapper-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
</style>

<div class="reports-page-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3"><i class="bi bi-file-earmark-bar-graph"></i> EXECUTIVE REPORTS</div>
                    <h2 class="banner-title mb-2">Pusat Laporan &amp; Rekapitulasi Operasional 📊</h2>
                    <p class="banner-subtitle mb-0">Cetak dokumen PDF resmi &amp; ekspor spreadsheet Excel untuk transaksi, pasokan limbah, inventaris, dan pengiriman.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- METRIK KPI SINGKAT -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="p-3.5 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                    <span class="text-muted small fw-semibold d-block">Total Nilai Transaksi Terkonfirmasi (30 Hari)</span>
                    <strong class="fs-4 text-success fw-bold"><?= money((float)$totalSales) ?></strong>
                </div>
                <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fs-3"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="p-3.5 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                    <span class="text-muted small fw-semibold d-block">Volume Pesanan Tervalidasi</span>
                    <strong class="fs-4 text-dark fw-bold"><?= number((float)$totalCount) ?> Order</strong>
                </div>
                <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary fs-3"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>

    <!-- KARTU DOWNLOAD LAPORAN MULTI-FORMAT -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 report-card-item h-100 p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3"><i class="bi bi-receipt"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1 text-dark">Rekapitulasi Transaksi Pesanan</h5>
                        <p class="text-secondary small mb-3">Laporan seluruh transaksi pesanan B2B, status persetujuan, detail buyer, dan total nominal bayar.</p>
                        <?php if ($canExportReports): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-coco-pdf text-decoration-none" target="_blank" href="download.php?type=orders&format=pdf">
                                <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                            </a>
                            <a class="btn btn-coco-excel text-decoration-none" href="download.php?type=orders&format=xlsx">
                                <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 report-card-item h-100 p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3"><i class="bi bi-box-seam"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1 text-dark">Rekapitulasi Bahan Mentah Pasokan</h5>
                        <p class="text-secondary small mb-3">Ringkasan batch limbah kelapa mentah, entitas PT Supplier penerbit, spesialisasi bahan, dan kuantitas alokasi.</p>
                        <?php if ($canExportReports): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-coco-pdf text-decoration-none" target="_blank" href="download.php?type=batches&format=pdf">
                                <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                            </a>
                            <a class="btn btn-coco-excel text-decoration-none" href="download.php?type=batches&format=xlsx">
                                <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 report-card-item h-100 p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3"><i class="bi bi-boxes"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1 text-dark">Inventaris Produk Olahan Koperasi</h5>
                        <p class="text-secondary small mb-3">Daftar stok barang hasil olahan limbah, harga per kilogram, dan Koperasi Anak Sektor pengolah.</p>
                        <?php if ($canExportReports): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-coco-pdf text-decoration-none" target="_blank" href="download.php?type=products&format=pdf">
                                <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                            </a>
                            <a class="btn btn-coco-excel text-decoration-none" href="download.php?type=products&format=xlsx">
                                <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 report-card-item h-100 p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning-emphasis rounded-3 fs-3"><i class="bi bi-truck-front-fill"></i></div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1 text-dark">Rekapitulasi Pengiriman Logistik</h5>
                        <p class="text-secondary small mb-3">Tugas pengiriman driver mitra, status transit muatan, rute antar-wilayah, dan log resi digital.</p>
                        <?php if ($canExportReports): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-coco-pdf text-decoration-none" target="_blank" href="download.php?type=shipments&format=pdf">
                                <i class="bi bi-file-pdf-fill me-1"></i> Cetak PDF
                            </a>
                            <a class="btn btn-coco-excel text-decoration-none" href="download.php?type=shipments&format=xlsx">
                                <i class="bi bi-file-excel-fill me-1"></i> Ekspor Excel (.xlsx)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL TREN PENJUALAN PER TANGGAL -->
    <div class="table-wrapper-card mb-4">
        <div class="p-3.5 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#FFFFFF;">
            <div class="fw-bold fs-6" style="color:#0F172A;">
                <i class="bi bi-graph-up-arrow me-2" style="color:#0077B6;"></i>Tren Transaksi Per Tanggal (30 Hari Terakhir)
            </div>
            <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small">Data Tervalidasi</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr style="font-size:0.8rem; color:#475569; text-transform:uppercase;">
                        <th class="ps-4 py-3">Tanggal Transaksi</th>
                        <th>Jumlah Order Tervalidasi</th>
                        <th class="text-end pe-4">Total Nilai Order (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stats as $r): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= e(date('d F Y', strtotime($r['day']))) ?></td>
                        <td><span class="badge bg-light text-dark border px-2.5 py-1"><?= e((string)$r['total_orders']) ?> Order</span></td>
                        <td class="text-end pe-4 fw-bold text-success fs-6"><?= money((float)$r['sales']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$stats): ?>
                <div class="p-5 text-center">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">Belum Ada Transaksi Tervalidasi</h6>
                    <p class="text-secondary small mb-0">Riwayat tren transaksi akan muncul secara otomatis di sini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php } ?>