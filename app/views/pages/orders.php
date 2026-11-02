<?php
function page_orders(array $u): void {
    $isCoop  = $u['role'] === 'coop';
    $isBuyer = $u['role'] === 'buyer';
    $isSuper = $u['role'] === 'super_admin';
    $isInduk = in_array($u['role'], ['induk', 'supplier'], true);

    $query = "SELECT o.*, b.company_name, b.city AS buyer_city, 
              (SELECT c.name FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=o.id LIMIT 1) AS coop_name, 
              (SELECT c.id FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=o.id LIMIT 1) AS coop_id 
              FROM orders o 
              JOIN buyer_profiles b ON b.id=o.buyer_id";

    if ($isBuyer) {
        $orders = rows($query . " WHERE o.buyer_id=? ORDER BY o.id DESC", [(int)($u['buyer_id'] ?? 0)]);
    } elseif ($isCoop) {
        $orders = rows($query . " HAVING coop_id=? ORDER BY o.id DESC", [(int)($u['coop_id'] ?? 0)]);
    } else {
        $orders = rows($query . " ORDER BY o.id DESC");
    }
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1F6348 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Manajemen Transaksi Pesanan Industri B2B</h5>
                <p class="mb-0 small text-white-50">Koperasi Anak Sektor Wilayah menyetujui permintaan dari Pembeli Industri dan memuat penerbitan Invoice resmi.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-cart-check me-2" style="color:#164B35"></i>Daftar Pesanan Industri &bull; <?= count($orders) ?></h5>
            <span class="badge bg-light text-dark border">Lingkup Jabodetabek</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode Transaksi</th>
                        <th>Pembeli Industri</th>
                        <th>Koperasi Anak Sektor Penjual</th>
                        <th>Tujuan Pengiriman</th>
                        <th>Total Bayar</th>
                        <th>Status Order</th>
                        <th class="text-center">Aksi Pesanan</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $ord): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><i class="bi bi-hash text-muted"></i><?= e($ord['order_code']) ?></td>
                        <td><strong class="text-dark"><?= e($ord['company_name']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i><?= e($ord['coop_name'] ?? 'Koperasi Sektor') ?></span></td>
                        <td><?= e($ord['shipping_address']) ?> (<?= e($ord['city']) ?>)</td>
                        <td><strong class="text-success fs-6"><?= money($ord['total']) ?></strong></td>
                        <td><?= badge($ord['status']) ?></td>
                        <td class="text-center">
                            <!-- ACC PESANAN OLEH KOPERASI ANAK SEKTOR -->
                            <?php if ($isCoop && $ord['status'] === 'requested'): ?>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php action_form('approve_order', (int)$ord['id'], 'ACC Pesanan', 'btn-success btn-sm', ['status' => 'approved']); ?>
                                    <?php action_form('approve_order', (int)$ord['id'], 'Tolak Order', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- PEMBELI KONFIRMASI TERIMA BARANG -->
                            <?php if ($isBuyer && $ord['status'] === 'delivered'): ?>
                                <?php action_form('confirm_order_received', (int)$ord['id'], 'Konfirmasi Barang Diterima', 'btn-coco btn-sm'); ?>
                            <?php endif; ?>

                            <!-- INVOICE SIMULASI -->
                            <?php if (!in_array($ord['status'], ['requested', 'rejected'], true)): ?>
                                <button onclick="alert('Fungsi invoice terlampir pada dokumen laporan transaksi.');" class="btn btn-outline-secondary btn-sm fw-bold px-3">
                                    <i class="bi bi-file-earmark-pdf-fill me-1 text-danger"></i> Invoice PDF
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$orders) table_empty('Belum ada pesanan transaksi industri.'); ?>
        </div>
    </div>
</div>
<?php } ?>