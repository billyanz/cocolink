<?php
function page_products(array $u): void {
    $isCoop   = $u['role'] === 'coop';
    $isInduk  = $u['role'] === 'induk';
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
        $sid = (int)($u['supplier_id'] ?? 0);
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .products-container {
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
    .btn-ocean-primary {
        background-color: #0077B6; color: #FFFFFF; font-weight: 700; border-radius: 12px;
        border: none; padding: 10px 24px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
    }
    .btn-ocean-primary:hover { background-color: #023E8A; color: #FFFFFF; transform: translateY(-1px); }
</style>

<div class="products-container">
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
                    <div class="role-badge-crisp mb-3"><i class="bi bi-shop"></i> B2B PRODUCT CATALOG</div>
                    <h2 class="banner-title mb-2">Katalog Produk Olahan Koperasi Sektor 🏷️</h2>
                    <p class="banner-subtitle mb-0">
                        <?= $isCoop ? 'Kelola katalog dan konversi produksi barang siap jual dari bahan mentah.' : 'Monitoring persediaan stok produk olahan dari Koperasi Anak Sektor binaan.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isCoop): ?>
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 section-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-plus-circle-fill me-2" style="color:#0077B6"></i>Tambah Katalog Produk Baru</h5>
                    <p class="text-muted small mb-3">Daftarkan jenis barang hasil olahan (Sapu Lidi, Arang Briket, Karbon Aktif).</p>

                    <form method="post">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="create_product">
                        
                        <?php field('Nama Produk Olahan', 'name', 'text', '', 'required', 'Sapu Lidi Grade A'); ?>
                        <?php select_field('Kategori Limbah Olahan', 'raw_type_id', array_column($rawTypes, 'name', 'id')); ?>
                        <?php field('Harga per Kg / Satuan (Rp)', 'price', 'number', '', 'required step="100"', '25000'); ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark small mb-1">Deskripsi Spesifikasi Produk</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Spesifikasi kualitas produk olahan..." style="border-radius:10px; border-color:#CBD5E1;"></textarea>
                        </div>

                        <button class="btn btn-ocean-primary w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Simpan Produk Baru</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 section-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-tools me-2" style="color:#0077B6"></i>Catat Konversi Produksi Olahan</h5>
                    <p class="text-muted small mb-3">Ubah bahan mentah yang diterima dari Koperasi Induk menjadi stok olahan.</p>

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

                        <button class="btn btn-ocean-primary w-100 fw-bold"><i class="bi bi-arrow-repeat me-1"></i> Tambah Stok dari Produksi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 section-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-shop-window me-2" style="color:#0077B6"></i>Daftar Katalog Produk &amp; Stok &bull; <?= count($products) ?></h5>
                <span class="badge bg-white text-secondary border px-3 py-1.5 rounded-pill small">Siap Jual ke Buyer B2B</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#F1F5F9; font-size:0.8rem; color:#475569; text-transform:uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Kode SKU</th>
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
                            <td class="ps-4 fw-bold text-dark"><i class="bi bi-barcode me-1.5 text-muted"></i><?= e($pr['sku']) ?></td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i><?= e($pr['coop_name']) ?></span></td>
                            <td><strong class="text-dark"><?= e($pr['name']) ?></strong></td>
                            <td><span class="badge bg-info bg-opacity-10 text-primary border border-primary px-2.5 py-1 rounded-pill"><?= e($pr['raw_name']) ?></span></td>
                            <td><strong class="text-success"><?= money($pr['price']) ?></strong></td>
                            <td>
                                <?php if ((float)$pr['stock'] > 0): ?>
                                    <strong class="text-dark fs-6"><?= number($pr['stock']) ?></strong> <small class="text-muted">kg</small>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border px-2.5 py-1 rounded-pill">Stok Habis</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$products): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Katalog Produk Olahan</h6>
                        <p class="text-secondary small mb-0">Produk yang didaftarkan Koperasi Sektor akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>