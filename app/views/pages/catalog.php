<?php
function page_catalog(): void {
    $list = rows("SELECT p.*, c.name AS coop_name, r.name AS raw_name, s.legal_name 
                  FROM products p 
                  JOIN cooperatives c ON c.id=p.coop_id 
                  JOIN suppliers s ON s.id=c.supplier_id 
                  JOIN raw_types r ON r.id=p.raw_type_id 
                  WHERE p.is_active=1 AND p.stock>0 AND c.status='verified' AND s.status='verified' 
                  ORDER BY p.name");
?>
<div class="row g-3">
<?php foreach ($list as $p): ?>
    <div class="col-md-6 col-xl-4">
        <div class="panel p-4 bg-white border h-100 d-flex flex-column" style="border-radius:16px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="chip"><?= e($p['raw_name']) ?></span>
                <span class="helper small text-muted">SKU <?= e($p['sku']) ?></span>
            </div>
            <div style="background:#eaf0e3; border-radius:12px; height:120px; display:grid; place-items:center; font-size:50px">
                🥥
            </div>
            <h5 class="mt-3 fw-bold text-dark"><?= e($p['name']) ?></h5>
            <p class="helper mb-2"><?= e($p['coop_name']) ?> &bull; <?= e($p['legal_name']) ?></p>
            
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong style="font-size:20px; color:#164B35;"><?= money($p['price']) ?></strong>
                    <span class="helper">per kg</span>
                </div>
                <div class="helper mb-3 text-muted">Stok tersedia: <?= number($p['stock']) ?> kg</div>
                
                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="new_order">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <div class="mb-2">
                        <?php field('Jumlah Pesan (kg)', 'quantity', 'number', '', 'required min="0.01" step="0.01" max="' . e($p['stock']) . '"'); ?>
                    </div>
                    <div class="mb-2"><?php field('Kota Tujuan', 'city'); ?></div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Alamat Pengiriman</label>
                        <textarea class="form-control" name="shipping_address" required rows="2" placeholder="Masukkan alamat lengkap..."></textarea>
                    </div>
                    <div class="mb-3"><?php field('Catatan Pesanan', 'notes', 'text', '', ''); ?></div>
                    <button class="btn btn-coco w-100 fw-bold py-2">Ajukan Pesanan Industri</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$list): ?>
    <div class="panel p-4 bg-white border" style="border-radius:16px;">
        <?php table_empty('Belum tersedia produk yang memiliki stok siap jual.'); ?>
    </div>
<?php endif; ?>
<?php } ?>