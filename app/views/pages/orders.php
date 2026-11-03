<?php
function page_orders(array $u): void {
    $isCoop   = $u['role'] === 'coop';
    $isBuyer  = $u['role'] === 'buyer';
    $isDriver = $u['role'] === 'driver';
    $isInduk  = $u['role'] === 'induk';
    $isSuper  = $u['role'] === 'super_admin';

    $query = "SELECT o.*, b.company_name, b.city AS buyer_city, 
              (SELECT c.name FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=o.id LIMIT 1) AS coop_name, 
              (SELECT c.id FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=o.id LIMIT 1) AS coop_id 
              FROM orders o 
              JOIN buyer_profiles b ON b.id=o.buyer_id";

    if ($isBuyer) {
        $orders = rows($query . " WHERE o.buyer_id=? ORDER BY o.id DESC", [(int)($u['buyer_id'] ?? 0)]);
    } elseif ($isCoop) {
        $orders = rows($query . " WHERE o.id IN (SELECT i2.order_id FROM order_items i2 JOIN products p2 ON p2.id=i2.product_id WHERE p2.coop_id=?) ORDER BY o.id DESC", [(int)($u['coop_id'] ?? 0)]);
    } elseif ($isDriver) {
        $orders = rows($query . " WHERE o.status IN ('approved','assigned','in_transit','delivered') ORDER BY o.id DESC");
    } else {
        $orders = rows($query . " ORDER BY o.id DESC");
    }
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .orders-container {
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
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    .banner-silhouette-bg {
        position: absolute; top: 0; right: 0; bottom: 0; left: 0;
        pointer-events: none; z-index: 0; overflow: hidden;
    }
    .banner-silhouette-bg svg {
        position: absolute; right: -20px; bottom: -20px; height: 125%; width: auto; opacity: 0.10; fill: #FFFFFF;
    }

    .role-badge-crisp {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35); color: #FFFFFF;
        padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    }
    .banner-title { color: #FFFFFF; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.5px; }
    .banner-subtitle { color: #E0F2FE; font-size: 0.95rem; font-weight: 500; }

    .section-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: all 0.25s ease;
    }
</style>

<div class="orders-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="banner-silhouette-bg">
            <svg viewBox="0 0 500 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,220 Q 125,180 250,220 T 500,220 L 500,300 L 0,300 Z" />
                <path d="M 0,240 Q 125,210 250,240 T 500,240 L 500,300 L 0,300 Z" opacity="0.6" />
                <g transform="translate(310, 10) scale(0.95)">
                    <path d="M 140 280 Q 125 180 145 100 Q 152 100 148 280 Z" />
                    <path d="M 145 100 Q 65 70 15 90 Q 65 60 145 100 Z" />
                    <path d="M 145 100 Q 85 30 35 20 Q 85 25 145 100 Z" />
                    <path d="M 145 100 Q 165 20 225 15 Q 170 30 145 100 Z" />
                    <path d="M 145 100 Q 215 50 265 80 Q 200 70 145 100 Z" />
                    <path d="M 145 100 Q 225 110 275 150 Q 205 120 145 100 Z" />
                    <path d="M 145 100 Q 75 120 25 160 Q 75 130 145 100 Z" />
                    <circle cx="137" cy="108" r="8" /><circle cx="150" cy="112" r="9" /><circle cx="143" cy="120" r="7.5" />
                </g>
            </svg>
        </div>

        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3"><i class="bi bi-receipt"></i> B2B TRANSACTION ORDERS</div>
                    <h2 class="banner-title mb-2">Manajemen Pesanan Industri B2B 📑</h2>
                    <p class="banner-subtitle mb-0">Koperasi Anak menyetujui pesanan Buyer, lalu Driver Logistik mengantarkan barang ke pabrik tujuan.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 section-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-cart-check me-2" style="color:#0077B6"></i>Daftar Pesanan Industri &bull; <?= count($orders) ?></h5>
                <span class="badge bg-white text-secondary border px-3 py-1.5 rounded-pill small">Ekosistem Jabodetabek</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#F1F5F9; font-size:0.8rem; color:#475569; text-transform:uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Kode Transaksi</th>
                            <th>Pembeli Industri</th>
                            <th>Koperasi Anak Penjual</th>
                            <th>Tujuan Pengiriman</th>
                            <th>Total Bayar</th>
                            <th>Status Order</th>
                            <th class="text-center">Aksi Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><i class="bi bi-hash text-muted"></i><?= e($ord['order_code']) ?></td>
                            <td><strong class="text-dark"><?= e($ord['company_name']) ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i><?= e($ord['coop_name'] ?? 'Koperasi Sektor') ?></span></td>
                            <td><?= e($ord['shipping_address']) ?> (<?= e($ord['buyer_city']) ?>)</td>
                            <td><strong class="text-success fs-6"><?= money($ord['total']) ?></strong></td>
                            <td><?= badge($ord['status']) ?></td>
                            <td class="text-center">
                                <?php if ($isCoop && $ord['status'] === 'requested'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php action_form('approve_order', (int)$ord['id'], 'ACC Pesanan', 'btn-success btn-sm font-semibold', ['status' => 'approved']); ?>
                                        <?php action_form('approve_order', (int)$ord['id'], 'Tolak Order', 'btn-outline-danger btn-sm font-semibold', ['status' => 'rejected']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isDriver && in_array($ord['status'], ['approved', 'assigned'], true)): ?>
                                    <?php action_form('update_order_delivery', (int)$ord['id'], 'Ambil & Antar Paket', 'btn-primary btn-sm font-semibold', ['status' => 'in_transit']); ?>
                                <?php elseif ($isDriver && $ord['status'] === 'in_transit'): ?>
                                    <?php action_form('update_order_delivery', (int)$ord['id'], 'Tandai Sampai Tujuan', 'btn-warning btn-sm font-semibold', ['status' => 'delivered']); ?>
                                <?php endif; ?>

                                <?php if ($isBuyer && $ord['status'] === 'delivered'): ?>
                                    <?php action_form('confirm_order_received', (int)$ord['id'], 'Konfirmasi Barang Diterima', 'btn-success btn-sm font-semibold'); ?>
                                <?php endif; ?>

                                <?php if (!in_array($ord['status'], ['requested', 'rejected'], true)): ?>
                                    <a href="<?= url('invoice', ['id' => $ord['id']]) ?>" class="btn btn-outline-secondary btn-sm fw-bold px-2.5 py-1 ms-1 rounded-3" target="_blank">
                                        <i class="bi bi-printer me-1"></i> Invoice
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$orders): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Pesanan Transaksi Industri</h6>
                        <p class="text-secondary small mb-0">Pesanan B2B dari Buyer akan dicatat di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>