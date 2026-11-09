<?php
declare(strict_types=1);

function page_invoice(array $u): void {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        throw new RuntimeException('ID Transaksi Pesanan tidak ditemukan.');
    }

    $order = one("SELECT o.*, b.company_name AS buyer_name, b.nib AS buyer_nib, b.address AS buyer_address, b.city AS buyer_city,
                         u_app.name AS approver_name
                  FROM orders o 
                  JOIN buyer_profiles b ON b.id=o.buyer_id 
                  LEFT JOIN users u_app ON u_app.id=o.approved_by
                  WHERE o.id=?", [$id]);

    if (!$order) {
        throw new RuntimeException('Data pesanan industri tidak ditemukan.');
    }

    $items = rows("SELECT i.*, p.name AS product_name, p.sku, r.name AS raw_name, c.name AS coop_name, c.address AS coop_address, c.city AS coop_city
                   FROM order_items i 
                   JOIN products p ON p.id=i.product_id 
                   JOIN raw_types r ON r.id=p.raw_type_id 
                   JOIN cooperatives c ON c.id=p.coop_id 
                   WHERE i.order_id=?", [$id]);

    $firstItem = $items[0] ?? [];
    $coopName  = $firstItem['coop_name'] ?? 'Koperasi Anak Sektor Wilayah';
    $coopAddr  = $firstItem['coop_address'] ?? 'Gudang Penampungan Regional';
    $coopCity  = $firstItem['coop_city'] ?? 'Jabodetabek';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice & Surat Jalan &bull; <?= e($order['order_code']) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #F8FAFC;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0F172A;
        }
        .invoice-card {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            max-width: 850px;
            margin: 2rem auto;
            padding: 3rem;
            border: 1px solid #E2E8F0;
        }
        .coco-header-bg {
            background: linear-gradient(135deg, #0A2540 0%, #0077B6 100%);
            color: white;
            border-radius: 16px;
            padding: 1.75rem 2rem;
        }
        .qr-placeholder {
            width: 90px;
            height: 90px;
            background: #F1F5F9;
            border: 2px dashed #CBD5E1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            color: #64748B;
            text-align: center;
        }
        
        @media print {
            body { background-color: #FFFFFF; }
            .no-print { display: none !important; }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .coco-header-bg {
                background: #0A2540 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center max-w-850 mx-auto mt-4 no-print" style="max-width:850px;">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Aplikasi
        </a>
        <button onclick="window.print()" class="btn btn-primary fw-bold px-4 py-2" style="border-radius:10px; background-color:#0077B6; border:none;">
            <i class="bi bi-printer-fill me-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="invoice-card">
        <div class="coco-header-bg d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-diagram-3-fill fs-3"></i>
                    <h3 class="fw-bold mb-0 text-white" style="letter-spacing:0.5px;">CoCoLink</h3>
                </div>
                <small class="text-white-50">Jaringan Supply Chain Sirkular Kelapa Jabodetabek</small>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-primary fw-bold fs-6 px-3 py-1 mb-1" style="border-radius:20px;">
                    INVOICE & SURAT JALAN
                </span>
                <div class="small text-white-50">No: <strong><?= e($order['order_code']) ?></strong></div>
                <div class="small text-white-50">Tanggal: <?= e(date('d F Y', strtotime($order['created_at']))) ?></div>
            </div>
        </div>

        <div class="row g-4 mb-4 pb-3 border-bottom">
            <div class="col-6">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Penerbit (Koperasi Anak Penjual)</span>
                <strong class="text-dark fs-6 d-block"><?= e($coopName) ?></strong>
                <div class="small text-secondary"><?= e($coopAddr) ?></div>
                <div class="small text-secondary">Wilayah: <?= e($coopCity) ?></div>
            </div>
            <div class="col-6 text-end">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Tujuan Pengiriman (Pembeli B2B)</span>
                <strong class="text-dark fs-6 d-block"><?= e($order['buyer_name']) ?></strong>
                <div class="small text-secondary">NIB: <?= e($order['buyer_nib']) ?></div>
                <div class="small text-secondary"><?= e($order['shipping_address']) ?> (<?= e($order['buyer_city']) ?>)</div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3" style="width:50px;">No</th>
                        <th>Kode SKU & Produk Olahan</th>
                        <th>Kategori Limbah</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-center">Volume (kg)</th>
                        <th class="text-end pe-3">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($items as $it): ?>
                    <tr>
                        <td class="ps-3 text-center"><?= $no++ ?></td>
                        <td>
                            <strong class="text-dark d-block"><?= e($it['product_name']) ?></strong>
                            <small class="text-muted">SKU: <?= e($it['sku']) ?></small>
                        </td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= e($it['raw_name']) ?></span></td>
                        <td class="text-end"><?= money((float)$it['unit_price']) ?></td>
                        <td class="text-center fw-bold"><?= number((float)$it['quantity']) ?> kg</td>
                        <td class="text-end pe-3 fw-bold text-dark"><?= money((float)$it['quantity'] * (float)$it['unit_price']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-bold fs-6 py-3">GRAND TOTAL TRANSAKSI:</td>
                        <td class="text-end pe-3 fw-bold fs-5 text-success py-3"><?= money((float)$order['total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row align-items-end mt-5 pt-3 border-top">
            <div class="col-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="qr-placeholder">
                        <div>
                            <i class="bi bi-qr-code fs-3 d-block"></i>
                            VERIFIED
                        </div>
                    </div>
                    <div class="small text-muted">
                        <strong class="text-dark d-block">Verifikasi Digital Sistem CoCoLink</strong>
                        Dokumen transaksi ini sah dan dikeluarkan secara elektronik oleh sistem terintegrasi.
                        <div class="mt-1">Status: <strong class="text-success"><?= e(strtoupper(status_text($order['status']))) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="col-5 text-center">
                <small class="text-muted d-block mb-4">Pengesahan Koperasi Anak Sektor Wilayah,</small>
                <div class="border-bottom mx-auto" style="width:180px;"></div>
                <strong class="text-dark d-block mt-2 small"><?= e($order['approver_name'] ?: 'Pengelola Koperasi Anak') ?></strong>
                <small class="text-muted d-block" style="font-size:0.75rem;">Stempel Digital Resmi</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
}