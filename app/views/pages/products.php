<?php
function page_products(array $u): void {
    $isCoop   = $u['role'] === 'coop';
    $isInduk  = in_array($u['role'], ['induk', 'supplier'], true);
    $isSuper  = $u['role'] === 'super_admin';

    if ($isCoop) {
        $products = rows("SELECT p.*, c.name AS coop_name, r.name AS raw_name 
                         FROM products p 
                         JOIN cooperatives c ON c.id=p.coop_id 
                         JOIN raw_types r ON r.id=p.raw_type_id 
                         WHERE p.coop_id=? 
                         ORDER BY p.id DESC", [$u['coop_id']]);

        $allocations = rows("SELECT a.id, b.batch_code, r.name AS raw_name, a.quantity 
                            FROM allocations a 
                            JOIN raw_batches b ON b.id=a.batch_id 
                            JOIN raw_types r ON r.id=b.raw_type_id 
                            WHERE a.coop_id=? AND a.status='accepted' 
                            ORDER BY a.id DESC", [$u['coop_id']]);
    } elseif ($isInduk) {
        $sid = $u['supplier_id'];
        $products = rows("SELECT p.*, c.name AS coop_name, r.name AS raw_name 
                         FROM products p 
                         JOIN cooperatives c ON c.id=p.coop_id 
                         JOIN raw_types r ON r.id=p.raw_type_id 
                         WHERE c.supplier_id=? 
                         ORDER BY p.id DESC", [$sid]);
        $allocations = [];
    } else {
        $products = rows("SELECT p.*, c.name AS coop_name, r.name AS raw_name 
                         FROM products p 
                         JOIN cooperatives c ON c.id=p.coop_id 
                         JOIN raw_types r ON r.id=p.raw_type_id 
                         ORDER BY p.id DESC");
        $allocations = [];
    }

    $rawTypes = rows("SELECT id, name FROM raw_types ORDER BY name");
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-shop"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Produk Olahan & Manajemen Stok Koperasi</h5>
                <p class="mb-0 small text-white-50">
                    <?= $isCoop ? 'Kelola katalog dan konversi produksi barang siap jual dari bahan mentah.' : 'Monitoring persediaan stok produk olahan dari 5 Koperasi Anak regional Anda.' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- KOPERASI ANAK: FORM TAMBAH PRODUK & LOG KONVERSI PRODUKSI -->
<?php if ($isCoop): ?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-plus-circle-fill me-2" style="color:#164B35"></i>Tambah Katalong Produk Baru</h5>
                <p class="text-muted small mb-3">Daftarkan jenis barang hasil olahan limbah (misal: Sapu Lidi, Arang Briket).</p>

                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="create_product">
                    
                    <?php field('Nama Produk Olahan', 'name', 'text', '', 'required', 'Sapu Lidi Grade A'); ?>
                    <?php select_field('Kategori Limbah Olahan', 'raw_type_id', array_column($rawTypes, 'name', 'id')); ?>
                    <?php field('Harga per Kg / Satuan (Rp)', 'price', 'number', '', 'required step="100"', '25000'); ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small mb-1">Deskripsi Spesifikasi Produk</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Spesifikasi kualitas produk olahan..." style="border-radius:10px;"></textarea>
                    </div>

                    <button class="btn btn-coco w-100 fw-bold shadow-sm" style="border-radius:10px;"><i class="bi bi-plus-lg me-1"></i> Simpan Produk Baru</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-tools me-2" style="color:#164B35"></i>Catat Konversi Produksi Olahan</h5>
                <p class="text-muted small mb-3">Ubah bahan mentah yang diterima dari PT Induk menjadi stok produk olahan.</p>

                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="log_production">

                    <?php 
                    $allocOpts = [];
                    foreach ($allocations as $al) {
                        $allocOpts[$al['id']] = $al['batch_code'] . ' - ' . $al['raw_name'] . ' (' . number($al['quantity']) . ' kg)';
                    }
                    select_field('Pilih Bahan Mentah Diterima', 'allocation_id', $allocOpts);

                    $prodOpts = array_column($products, 'name', 'id');
                    select_field('Pilih Hasil Produk Olahan', 'product_id', $prodOpts);
                    ?>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <?php field('Input Bahan (kg)', 'input_qty', 'number', '', 'required step="0.01"', '500'); ?>
                        </div>
                        <div class="col-6">
                            <?php field('Hasil Produk (kg)', 'output_qty', 'number', '', 'required step="0.01"', '400'); ?>
                        </div>
                    </div>

                    <button class="btn btn-coco w-100 fw-bold shadow-sm" style="border-radius:10px;"><i class="bi bi-arrow-repeat me-1"></i> Tambah Stok dari Produksi</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TABEL DAFTAR PRODUK OLAHAN KOPERASI -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-shop-window me-2" style="color:#164B35"></i>Daftar Katalog Produk & Stok &bull; <?= count($products) ?></h5>
            <span class="badge bg-light text-dark border">Siap Jual ke Buyer B2B</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode SKU</th>
                        <th>Koperasi Anak Produsen</th>
                        <th>Nama Produk Olahan</th>
                        <th>Kategori Limbah</th>
                        <th>Harga Satuan</th>
                        <th>Sisa Stok Tersedia</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $pr): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><i class="bi bi-barcode me-1 text-muted"></i><?= e($pr['sku']) ?></td>
                        <td><span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i><?= e($pr['coop_name']) ?></span></td>
                        <td><strong class="text-dark"><?= e($pr['name']) ?></strong></td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?= e($pr['raw_name']) ?></span></td>
                        <td><strong class="text-success"><?= money($pr['price']) ?></strong></td>
                        <td>
                            <?php if ((float)$pr['stock'] > 0): ?>
                                <strong class="text-dark fs-6"><?= number($pr['stock']) ?></strong> <small class="text-muted">kg</small>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border">Stok Habis</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$products) table_empty('Belum ada katalog produk olahan.'); ?>
        </div>
    </div>
</div>
<?php } ?>