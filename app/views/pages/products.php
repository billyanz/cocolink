<?php
function page_products(array $u): void {
    $manager  = is_manager($u);
    $myCoop   = $u['role'] === 'coop';
    $coops    = $myCoop ? ' WHERE p.coop_id=?' : '';
    $params   = $myCoop ? [$u['coop_id']] : [];
    
    $products = rows('SELECT p.*, c.name AS coop_name, r.name AS raw_name 
                      FROM products p 
                      JOIN cooperatives c ON c.id=p.coop_id 
                      JOIN raw_types r ON r.id=p.raw_type_id' . $coops . ' 
                      ORDER BY p.id DESC', $params);

    if ($myCoop) {
        $coop     = one('SELECT * FROM cooperatives WHERE id=?', [$u['coop_id']]);
        $types    = rows('SELECT id, name FROM raw_types WHERE id=?', [$coop['specialty_raw_type_id']]);
        $accepted = rows("SELECT a.*, b.batch_code, 
                         COALESCE((SELECT SUM(l.input_qty) FROM production_logs l WHERE l.allocation_id=a.id),0) AS used 
                         FROM allocations a 
                         JOIN raw_batches b ON b.id=a.batch_id 
                         WHERE a.coop_id=? AND a.status IN ('accepted','completed') 
                         ORDER BY a.id DESC", [$u['coop_id']]);
    }

    if ($myCoop):
?>
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <h5 class="fw-bold mb-1">Daftarkan Jenis Produk</h5>
            <p class="helper mb-3">Produk jadi dimulai dengan stok 0. Stok bertambah dari catatan konversi produksi.</p>
            <form method="post" class="row g-3">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="new_product">
                <div class="col-md-6"><?php field('Kode SKU Unik', 'sku'); ?></div>
                <div class="col-md-6"><?php field('Nama Produk', 'name'); ?></div>
                <div class="col-12"><?php select_field('Asal Jenis Limbah', 'raw_type_id', array_column($types, 'name', 'id')); ?></div>
                <div class="col-md-6"><?php field('Harga Jual per kg (Rp)', 'price', 'number', '', 'required min="1" step="0.01"'); ?></div>
                <div class="col-md-6"><?php field('Deskripsi Singkat', 'description', 'text', '', ''); ?></div>
                <div class="col-12"><button class="btn btn-coco w-100 fw-bold">Simpan Katalog Produk</button></div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="panel p-4 bg-white border h-100" style="border-radius:16px;">
            <h5 class="fw-bold mb-1">Catat Konversi Limbah &rarr; Produk</h5>
            <p class="helper mb-3">Sistem akan memotong kuantitas limbah dan otomatis menambah stok produk olahan.</p>
            <form method="post" class="row g-3">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="production">
                <div class="col-12">
                    <label class="form-label fw-bold">Alokasi Diterima / Sisa Input</label>
                    <select class="form-select" name="allocation_id" required>
                        <option value="">Pilih Alokasi Limbah</option>
                        <?php foreach ($accepted as $a): 
                            $remain = (float)$a['quantity'] - (float)$a['used'];
                            if ($remain > 0): ?>
                                <option value="<?= $a['id'] ?>"><?= e($a['batch_code']) ?> &bull; <?= number($remain) ?> kg tersedia</option>
                            <?php endif; 
                        endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Produk Hasil Olahan</label>
                    <select class="form-select" name="product_id" required>
                        <option value="">Pilih Produk Tujuan</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['sku'] . ' &bull; ' . $p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><?php field('Input Limbah (kg)', 'input_qty', 'number', '', 'required step="0.01" min="0.01"'); ?></div>
                <div class="col-md-6"><?php field('Output Produk (kg)', 'output_qty', 'number', '', 'required step="0.01" min="0.01"'); ?></div>
                <div class="col-12"><?php field('Catatan Produksi', 'notes', 'text', '', ''); ?></div>
                <div class="col-12"><button class="btn btn-coco w-100 fw-bold">Catat Produksi Limbah</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Inventaris Produk Olahan &bull; <?= count($products) ?></h5>
        <span class="chip">Stok Siap Jual</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>SKU / Produk</th>
                    <th>Koperasi Anak</th>
                    <th>Asal Limbah</th>
                    <th>Harga</th>
                    <th>Stok Tersedia</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <strong><?= e($p['name']) ?></strong>
                        <div class="helper small text-muted"><?= e($p['sku']) ?></div>
                    </td>
                    <td><?= e($p['coop_name']) ?></td>
                    <td><?= e($p['raw_name']) ?></td>
                    <td><?= money($p['price']) ?> / kg</td>
                    <td><strong><?= number($p['stock']) ?></strong> kg</td>
                    <td>
                        <?= ((int)$p['is_active']) 
                            ? '<span class="badge text-bg-success">Aktif</span>' 
                            : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$products) table_empty('Belum ada produk olahan.'); ?>
    </div>
</div>
<?php } ?>