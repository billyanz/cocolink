<?php
function page_batches(array $u): void {
    $manager  = is_manager($u);
    $supplier = $u['role'] === 'supplier';

    if ($supplier) {
        check_supplier_active($u);
        $batches = rows("SELECT b.*, r.name AS raw_name, s.legal_name 
                        FROM raw_batches b 
                        JOIN raw_types r ON r.id=b.raw_type_id 
                        JOIN suppliers s ON s.id=b.supplier_id 
                        WHERE b.supplier_id=? 
                        ORDER BY b.id DESC", [$u['supplier_id']]);
    } else {
        $batches = rows("SELECT b.*, r.name AS raw_name, s.legal_name 
                        FROM raw_batches b 
                        JOIN raw_types r ON r.id=b.raw_type_id 
                        JOIN suppliers s ON s.id=b.supplier_id 
                        ORDER BY b.id DESC");
    }

    $rawTypes = rows("SELECT id, name FROM raw_types ORDER BY name");
    $suppliers = $manager ? rows("SELECT id, legal_name FROM suppliers WHERE status='verified' ORDER BY legal_name") : [];
?>

<!-- BANNER PASOKAN LIMBAH -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-box-seam"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Pasokan Limbah Mentah PT Pemasok</h5>
                <p class="mb-0 small text-white-50">Pencatatan volume dan kategori pasokan limbah mentah segar dari PT Pemasok Utama sebelum dialokasikan.</p>
            </div>
        </div>
    </div>
</div>

<!-- FORM TAMBAH BATCH PASOKAN (KHUSUS SUPPLIER / MANAGER) -->
<?php if ($supplier || $manager): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-plus-circle-fill me-2" style="color:#164B35"></i>Input Batch Pasokan Limbah Baru</h5>
        <p class="text-muted small mb-4">Masukkan detail volume dan jenis limbah mentah kelapa yang siap diproses.</p>

        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_batch">

            <div class="row g-3">
                <?php if ($manager && $suppliers): ?>
                    <div class="col-md-4">
                        <?php select_field('Pilih PT Pemasok Utama', 'supplier_id', array_column($suppliers, 'legal_name', 'id')); ?>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <?php select_field('Kategori Limbah Kelapa', 'raw_type_id', array_column($rawTypes, 'name', 'id')); ?>
                </div>

                <div class="col-md-4">
                    <?php field('Jumlah Volume (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"', 'Contoh: 1000'); ?>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold text-dark small mb-1">Catatan Tambahan / Kondisi Limbah</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Contoh: Limbah tempurung kelapa kering kadar air rendah." style="border-radius:10px;"></textarea>
                </div>

                <div class="col-12 text-end mt-3">
                    <button class="btn btn-coco fw-bold px-4 py-2 shadow-sm" style="border-radius:10px;">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Simpan Batch Limbah
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABEL DAFTAR PASOKAN BATCH -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-2" style="color:#164B35"></i>Daftar Batch Pasokan Limbah &bull; <?= count($batches) ?></h5>
            <span class="badge bg-light text-dark border">Gudang Mentah PT</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode Batch</th>
                        <th>PT Pemasok Utama</th>
                        <th>Kategori Limbah</th>
                        <th>Volume Masuk</th>
                        <th>Catatan Fisik</th>
                        <th>Tanggal Input</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($batches as $b): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><i class="bi bi-qr-code me-1 text-muted"></i><?= e($b['batch_code']) ?></td>
                        <td><strong class="text-dark"><?= e($b['legal_name']) ?></strong></td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><?= e($b['raw_name']) ?></span></td>
                        <td><strong class="fs-6 text-dark"><?= number($b['quantity']) ?></strong> <small class="text-muted">kg</small></td>
                        <td><small class="text-secondary"><?= e($b['notes'] ?: '—') ?></small></td>
                        <td><small class="text-muted"><?= e($b['created_at']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$batches) table_empty('Belum ada data pasokan batch limbah mentah.'); ?>
        </div>
    </div>
</div>
<?php } ?>