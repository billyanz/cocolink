<?php
function page_allocations(array $u): void {
    $isCoop   = $u['role'] === 'coop';
    $isInduk  = in_array($u['role'], ['induk', 'supplier'], true);
    $isSuper  = $u['role'] === 'super_admin';

    if ($isCoop) {
        // AMBIL COOP ID DARI USER SEKALIGUS FALLBACK KE SUPPLIER TERIKAT DENGAN PRESISI
        $userCoopId = (int)($u['coop_id'] ?? 0);
        $userSuppId = (int)($u['supplier_id'] ?? 0);

        $allocs = rows("SELECT a.*, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, c.name AS coop_name
                       FROM allocations a
                       JOIN raw_batches b ON b.id=a.batch_id
                       JOIN raw_types r ON r.id=b.raw_type_id
                       JOIN suppliers s ON s.id=b.supplier_id
                       JOIN cooperatives c ON c.id=a.coop_id
                       WHERE a.coop_id = ? 
                          OR (c.supplier_id = ? AND ? > 0)
                          OR a.coop_id IN (SELECT id FROM cooperatives WHERE supplier_id = ? AND ? > 0)
                       ORDER BY a.id DESC", [$userCoopId, $userSuppId, $userSuppId, $userSuppId, $userSuppId]);
    } else {
        $sid = (int)($u['supplier_id'] ?? 0);
        $allocs = rows("SELECT a.*, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, c.name AS coop_name
                       FROM allocations a
                       JOIN raw_batches b ON b.id=a.batch_id
                       JOIN raw_types r ON r.id=b.raw_type_id
                       JOIN suppliers s ON s.id=b.supplier_id
                       JOIN cooperatives c ON c.id=a.coop_id
                       " . ($sid && !$isSuper ? "WHERE b.supplier_id = {$sid}" : "") . "
                       ORDER BY a.id DESC");
    }

    $batches = $isInduk ? rows("SELECT b.id, b.batch_code, r.name AS raw_name, b.quantity 
                                FROM raw_batches b 
                                JOIN raw_types r ON r.id=b.raw_type_id 
                                " . ($u['supplier_id'] && !$isSuper ? "WHERE b.supplier_id = {$u['supplier_id']}" : "") . "
                                ORDER BY b.id DESC") : [];

    $coops = $isInduk ? rows("SELECT c.id, c.name 
                              FROM cooperatives c 
                              WHERE c.status='verified' " . ($u['supplier_id'] && !$isSuper ? "AND c.supplier_id = {$u['supplier_id']}" : "") . "
                              ORDER BY c.name") : [];
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-arrow-left-right"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Alokasi Distribusi Bahan Mentah Limbah</h5>
                <p class="mb-0 small text-white-50">
                    <?= $isCoop ? 'Daftar pasokan limbah mentah yang dikirim dari PT Induk ke Koperasi Anak Anda.' : 'Penyaluran pasokan limbah mentah dari PT Utama ke Koperasi Anak regional.' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FORM ALOKASI KHUSUS PT INDUK -->
<?php if ($isInduk): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-send-plus-fill me-2" style="color:#164B35"></i>Kirim Alokasi Bahan ke Koperasi Anak</h5>
        <p class="text-muted small mb-4">Pilih batch limbah mentah segar dan tentukan Koperasi Anak penerima di wilayah regional Anda.</p>

        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="allocate">

            <div class="row g-3">
                <div class="col-md-5">
                    <?php 
                    $batchOpts = [];
                    foreach ($batches as $bt) {
                        $batchOpts[$bt['id']] = $bt['batch_code'] . ' - ' . $bt['raw_name'] . ' (' . number($bt['quantity']) . ' kg)';
                    }
                    select_field('Pilih Batch Limbah Mentah', 'batch_id', $batchOpts);
                    ?>
                </div>

                <div class="col-md-4">
                    <?php select_field('Pilih Koperasi Anak Tujuan', 'coop_id', array_column($coops, 'name', 'id')); ?>
                </div>

                <div class="col-md-3">
                    <?php field('Jumlah Alokasi (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"', 'Contoh: 500'); ?>
                </div>

                <div class="col-12 text-end mt-3">
                    <button class="btn btn-coco fw-bold px-4 py-2 shadow-sm" style="border-radius:10px;">
                        <i class="bi bi-send-fill me-1"></i> Kirim Alokasi Bahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- TABEL ALOKASI DITERIMA KOPERASI ANAK -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-check me-2" style="color:#164B35"></i>Daftar Alokasi Pasokan Limbah &bull; <?= count($allocs) ?></h5>
            <span class="badge bg-light text-dark border">Distribusi Hulu &rarr; Olahan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode Batch</th>
                        <th>PT Induk Pengirim</th>
                        <th>Koperasi Anak Penerima</th>
                        <th>Kategori Limbah</th>
                        <th>Volume Alokasi</th>
                        <th>Status Penerimaan</th>
                        <?php if ($isCoop): ?><th class="text-center">Aksi Konfirmasi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allocs as $al): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><i class="bi bi-hash text-muted"></i><?= e($al['batch_code']) ?></td>
                        <td><strong class="text-dark"><?= e($al['supplier_name']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i><?= e($al['coop_name']) ?></span></td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><?= e($al['raw_name']) ?></span></td>
                        <td><strong class="fs-6 text-dark"><?= number($al['quantity']) ?></strong> <small class="text-muted">kg</small></td>
                        <td><?= badge($al['status']) ?></td>
                        <?php if ($isCoop): ?>
                            <td class="text-center">
                                <?php if ($al['status'] === 'proposed'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php action_form('respond_allocation', (int)$al['id'], 'Terima Pasokan', 'btn-success btn-sm', ['status' => 'accepted']); ?>
                                        <?php action_form('respond_allocation', (int)$al['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-muted"><i class="bi bi-check-all text-success me-1"></i>Selesai</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$allocs) table_empty('Belum ada data alokasi bahan mentah yang dikirim.'); ?>
        </div>
    </div>
</div>
<?php } ?>