<?php
function page_allocations(array $u): void {
    $manager  = is_manager($u);
    $supplier = $u['role'] === 'supplier';
    
    if ($manager) {
        $batches = rows("SELECT b.id, b.supplier_id, b.raw_type_id, b.batch_code, r.name AS raw_name, s.legal_name, 
                        ROUND(b.quantity - COALESCE((SELECT SUM(a.quantity) FROM allocations a WHERE a.batch_id=b.id AND a.status<>'rejected'),0), 2) AS remaining 
                        FROM raw_batches b 
                        JOIN raw_types r ON r.id=b.raw_type_id 
                        JOIN suppliers s ON s.id=b.supplier_id 
                        WHERE s.status='verified' 
                        HAVING remaining > 0 
                        ORDER BY b.id DESC");
        $coops  = rows("SELECT c.id, c.supplier_id, c.specialty_raw_type_id, c.name, c.city FROM cooperatives c WHERE c.status='verified' ORDER BY c.name");
        $allocs = rows('SELECT a.*, b.batch_code, b.supplier_id, b.raw_type_id, r.name AS raw_name, c.name AS coop_name, s.legal_name 
                       FROM allocations a 
                       JOIN raw_batches b ON b.id=a.batch_id 
                       JOIN raw_types r ON r.id=b.raw_type_id 
                       JOIN cooperatives c ON c.id=a.coop_id 
                       JOIN suppliers s ON s.id=b.supplier_id 
                       ORDER BY a.id DESC');
    } elseif ($supplier) {
        $allocs = rows('SELECT a.*, b.batch_code, r.name AS raw_name, c.name AS coop_name, s.legal_name 
                       FROM allocations a 
                       JOIN raw_batches b ON b.id=a.batch_id 
                       JOIN raw_types r ON r.id=b.raw_type_id 
                       JOIN cooperatives c ON c.id=a.coop_id 
                       JOIN suppliers s ON s.id=b.supplier_id 
                       WHERE b.supplier_id=? 
                       ORDER BY a.id DESC', [$u['supplier_id']]);
    } else {
        $allocs = rows('SELECT a.*, b.batch_code, r.name AS raw_name, c.name AS coop_name, s.legal_name 
                       FROM allocations a 
                       JOIN raw_batches b ON b.id=a.batch_id 
                       JOIN raw_types r ON r.id=b.raw_type_id 
                       JOIN cooperatives c ON c.id=a.coop_id 
                       JOIN suppliers s ON s.id=b.supplier_id 
                       WHERE a.coop_id=? 
                       ORDER BY a.id DESC', [$u['coop_id']]);
    }

    if ($manager):
?>
<div class="panel p-4 bg-white border mb-4" style="border-radius:16px;">
    <h5 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2" style="color:var(--coco-primary)"></i>Alokasikan Bahan Mentah</h5>
    <p class="helper text-muted small mb-3">Hanya dapat mengalokasikan ke Koperasi Terverifikasi dari PT yang sama dengan spesialisasi limbah yang sesuai.</p>
    <form method="post" class="row g-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="allocate">
        <div class="col-md-5">
            <label class="form-label fw-bold small text-secondary">Batch Tersedia</label>
            <select name="batch_id" id="batch" class="form-select" required onchange="filterCoops()">
                <option value="">Pilih Batch</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>" data-supplier="<?= $b['supplier_id'] ?>" data-type="<?= $b['raw_type_id'] ?>">
                        <?= e($b['batch_code'] . ' &bull; ' . $b['raw_name'] . ' &bull; ' . $b['legal_name'] . ' (sisa ' . number($b['remaining']) . ' kg)') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold small text-secondary">Koperasi Tujuan</label>
            <select name="coop_id" id="coop" class="form-select" required>
                <option value="">Pilih Koperasi</option>
                <?php foreach ($coops as $c): ?>
                    <option value="<?= $c['id'] ?>" data-supplier="<?= $c['supplier_id'] ?>" data-type="<?= $c['specialty_raw_type_id'] ?>">
                        <?= e($c['name'] . ' &bull; ' . $c['city']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <?php field('Alokasi (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"'); ?>
        </div>
        <div class="col-12 mt-3">
            <button class="btn btn-coco fw-bold px-4 py-2"><i class="bi bi-send-check me-1"></i> Buat Alokasi Bahan</button>
        </div>
    </form>
</div>
<script>
function filterCoops() {
    let selected = document.querySelector('#batch option:checked');
    let opts = document.querySelectorAll('#coop option');
    document.getElementById('coop').value = '';
    if (!selected || !selected.value) return;
    opts.forEach(o => {
        if (!o.value) return;
        o.hidden = (o.dataset.supplier !== selected.dataset.supplier || o.dataset.type !== selected.dataset.type);
    });
}
filterCoops();
</script>
<?php endif; ?>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-journal-text me-2" style="color:var(--coco-primary)"></i>Riwayat Alokasi Pasokan Limbah &bull; <?= count($allocs) ?></h5>
        <span class="chip">Satuan Standar: kg</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>PT Pemasok</th>
                    <th>Koperasi Anak</th>
                    <th>Jenis Limbah</th>
                    <th>Kuantitas</th>
                    <th>Status</th>
                    <?php if ($u['role'] === 'coop'): ?><th>Respons</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allocs as $a): ?>
                <tr>
                    <td class="fw-bold"><?= e($a['batch_code']) ?></td>
                    <td><?= e($a['legal_name']) ?></td>
                    <td><?= e($a['coop_name']) ?></td>
                    <td><span class="chip"><?= e($a['raw_name']) ?></span></td>
                    <td><strong><?= number($a['quantity']) ?></strong> kg</td>
                    <td><?= badge($a['status']) ?></td>
                    <?php if ($u['role'] === 'coop'): ?>
                        <td>
                        <?php if ($a['status'] === 'proposed'): ?>
                            <div class="d-flex gap-1">
                                <?php action_form('respond_allocation', (int)$a['id'], 'Terima', 'btn-outline-success btn-sm', ['status' => 'accepted']); ?>
                                <?php action_form('respond_allocation', (int)$a['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                            </div>
                        <?php else: ?>
                            <span class="helper small text-muted">Diproses</span>
                        <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$allocs) table_empty('Belum ada alokasi limbah tercatat.'); ?>
    </div>
</div>
<?php } ?>