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

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-shop-window"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Katalog Industri B2B &bull; Produk Olahan Kelapa</h5>
                <p class="mb-0 small text-white-50">Pesan pasokan olahan berkualitas dari 5 Koperasi Anak Sektor Wilayah Jabodetabek.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($products as $pr): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><?= e($pr['raw_name']) ?></span>
                        <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i><?= e($pr['coop_city']) ?></span>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= e($pr['name']) ?></h5>
                    <div class="small text-muted mb-3"><i class="bi bi-shop me-1"></i><?= e($pr['coop_name']) ?></div>

                    <p class="small text-secondary mb-3 flex-grow-1"><?= e($pr['description'] ?: 'Produk olahan limbah kelapa murni siap pasok industri.') ?></p>

                    <div class="bg-light p-3 rounded mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Harga per Satuan</small>
                                <strong class="text-success fs-5"><?= money($pr['price']) ?></strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Stok Tersedia</small>
                                <strong class="text-dark fs-6"><?= number($pr['stock']) ?> kg</strong>
                            </div>
                        </div>
                    </div>

                    <!-- FORM BUAT PESANAN B2B -->
                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="create_order">
                        <input type="hidden" name="product_id" value="<?= $pr['id'] ?>">

                        <div class="mb-2">
                            <input type="number" step="0.01" min="1" max="<?= $pr['stock'] ?>" class="form-control form-control-sm" name="quantity" required placeholder="Jumlah beli (kg)">
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm" name="shipping_address" required placeholder="Alamat Pabrik / Tujuan">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" name="city" value="Jakarta" required placeholder="Kota Pengiriman">
                        </div>

                        <button class="btn btn-coco btn-sm w-100 fw-bold py-2" style="border-radius:10px;">
                            <i class="bi bi-cart-plus me-1"></i> Beli Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!$products): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center" style="border-radius:16px;">
                <div class="display-5 text-muted mb-3"><i class="bi bi-inbox"></i></div>
                <h5 class="fw-bold text-dark">Katalog Olahan Masih Kosong</h5>
                <p class="text-muted small mb-0">Belum ada Koperasi Anak Sektor yang memposting stok produk olahan siap jual.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php } ?>