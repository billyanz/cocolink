<?php
function page_batches(array $u): void {
    $manager = is_manager($u);
    $types   = rows('SELECT id, name, examples FROM raw_types');
    
    $batches = $manager 
        ? rows("SELECT b.*, s.legal_name, r.name AS raw_name, 
                 (SELECT COALESCE(SUM(a.quantity),0) FROM allocations a WHERE a.batch_id=b.id AND a.status<>'rejected') AS reserved 
                 FROM raw_batches b 
                 JOIN suppliers s ON s.id=b.supplier_id 
                 JOIN raw_types r ON r.id=b.raw_type_id 
                 ORDER BY b.id DESC")
        : rows("SELECT b.*, s.legal_name, r.name AS raw_name, 
                 (SELECT COALESCE(SUM(a.quantity),0) FROM allocations a WHERE a.batch_id=b.id AND a.status<>'rejected') AS reserved 
                 FROM raw_batches b 
                 JOIN suppliers s ON s.id=b.supplier_id 
                 JOIN raw_types r ON r.id=b.raw_type_id 
                 WHERE b.supplier_id=? 
                 ORDER BY b.id DESC", [$u['supplier_id']]);

    if (!$manager): 
?>
<div class="panel p-4 bg-white border mb-4" style="border-radius:16px;">
    <h5 class="fw-bold mb-1">Catat Batch Limbah Kelapa</h5>
    <p class="helper mb-3">Bahan mentah dicatat terlebih dahulu, alokasi ke Koperasi Anak diatur oleh Induk.</p>
    <form method="post" class="row g-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="new_batch">
        <div class="col-md-5">
            <?php select_field('Jenis Limbah', 'raw_type_id', array_column($types, 'name', 'id')); ?>
        </div>
        <div class="col-md-3">
            <?php field('Berat (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"'); ?>
        </div>
        <div class="col-md-4">
            <?php field('Catatan / Keterangan', 'notes', 'text', '', ''); ?>
        </div>
        <div class="col-12">
            <button class="btn btn-coco fw-bold px-4 py-2">+ Simpan Batch Pasokan</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Daftar Batch Limbah &bull; <?= count($batches) ?></h5>
        <span class="chip">Satuan Standar: kg</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Kode Batch</th>
                    <th>PT Sumber</th>
                    <th>Jenis</th>
                    <th>Qty Awal</th>
                    <th>Dialokasikan</th>
                    <th>Sisa Stok</th>
                    <th>Tanggal Input</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
                <tr>
                    <td class="fw-bold"><?= e($b['batch_code']) ?></td>
                    <td><?= e($b['legal_name']) ?></td>
                    <td><?= e($b['raw_name']) ?></td>
                    <td><?= number($b['quantity']) ?> kg</td>
                    <td><?= number($b['reserved']) ?> kg</td>
                    <td><strong><?= number((float)$b['quantity'] - (float)$b['reserved']) ?> kg</strong></td>
                    <td class="text-muted small"><?= e(substr($b['created_at'], 0, 10)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$batches) table_empty('Batch limbah belum dicatat.'); ?>
    </div>
</div>
<?php } ?>