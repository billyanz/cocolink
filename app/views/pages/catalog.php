<?php
function page_catalog(array $u): void {
    must_role(['buyer']);

    $products = rows("SELECT p.*, c.name AS coop_name, c.city AS coop_city, r.name AS raw_name 
                      FROM products p 
                      JOIN cooperatives c ON c.id=p.coop_id 
                      JOIN raw_types r ON r.id=p.raw_type_id 
                      WHERE p.is_active=1 AND p.stock > 0 
                      ORDER BY p.id DESC");
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .catalog-container {
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

    .product-card-item {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px;
        transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .product-card-item:hover {
        border-color: #0077B6; transform: translateY(-4px); box-shadow: 0 10px 24px -4px rgba(0, 119, 182, 0.15);
    }

    .btn-ocean-primary {
        background-color: #0077B6; color: #FFFFFF; font-weight: 700; border-radius: 10px;
        border: none; padding: 10px 22px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
    }
    .btn-ocean-primary:hover { background-color: #023E8A; color: #FFFFFF; }
</style>

<div class="catalog-container">
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
                    <div class="role-badge-crisp mb-3"><i class="bi bi-cart-check"></i> B2B INDUSTRY CATALOG</div>
                    <h2 class="banner-title mb-2">Katalog Olahan Industri B2B 🛍️</h2>
                    <p class="banner-subtitle mb-0">Pasokan barang olahan berkualitas murni dari 5 Koperasi Anak Sektor Wilayah Jabodetabek.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($products as $pr): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 product-card-item h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2.5 py-1 rounded-pill"><?= e($pr['raw_name']) ?></span>
                            <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill"><i class="bi bi-geo-alt me-1"></i><?= e($pr['coop_city']) ?></span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1"><?= e($pr['name']) ?></h5>
                        <div class="small text-muted mb-3"><i class="bi bi-shop me-1"></i><?= e($pr['coop_name']) ?></div>

                        <p class="small text-secondary mb-3 flex-grow-1" style="line-height:1.5;"><?= e($pr['description'] ?: 'Produk olahan limbah kelapa murni siap pasok industri.') ?></p>

                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block font-semibold">Harga per Satuan</small>
                                    <strong class="text-success fs-5"><?= money($pr['price']) ?></strong>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block font-semibold">Stok Tersedia</small>
                                    <strong class="text-dark fs-6"><?= number($pr['stock']) ?> kg</strong>
                                </div>
                            </div>
                        </div>

                        <form method="post">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="create_order">
                            <input type="hidden" name="product_id" value="<?= $pr['id'] ?>">

                            <div class="mb-2">
                                <input type="number" step="0.01" min="1" max="<?= $pr['stock'] ?>" class="form-control form-control-sm" name="quantity" required placeholder="Jumlah beli (kg)" style="border-radius:8px;">
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="shipping_address" required placeholder="Alamat Pabrik Industri Tujuan" style="border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-sm" name="city" value="Jakarta" required placeholder="Kota Pengiriman" style="border-radius:8px;">
                            </div>

                            <button class="btn btn-ocean-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-cart-plus me-1"></i> Pesan Sekarang (B2B)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$products): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm p-5 text-center" style="border-radius:18px; border:1px solid #E2E8F0;">
                    <div class="display-5 text-muted mb-3"><i class="bi bi-inbox"></i></div>
                    <h5 class="fw-bold text-dark">Katalog Olahan Masih Kosong</h5>
                    <p class="text-muted small mb-0">Belum ada Koperasi Anak Sektor yang memposting stok produk olahan siap jual.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php } ?>