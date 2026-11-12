<?php
declare(strict_types=1);

function page_allocations(array $u): void {
    $role     = $u['role'] ?? '';
    $isCoop   = $role === 'coop';
    $isInduk  = $role === 'induk';
    $isSupp   = $role === 'supplier';
    $isSuper  = $role === 'super_admin';

    $canAllocate = $isInduk || $isSuper || $isSupp;

    if ($isCoop) {
        $userCoopId = (int)($u['coop_id'] ?? 0);

        $allocs = rows("SELECT a.*, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, c.name AS coop_name
                       FROM allocations a
                       JOIN raw_batches b ON b.id=a.batch_id
                       JOIN raw_types r ON r.id=b.raw_type_id
                       JOIN suppliers s ON s.id=b.supplier_id
                       JOIN cooperatives c ON c.id=a.coop_id
                       WHERE a.coop_id=?
                       ORDER BY a.id DESC", [$userCoopId]);
    } elseif ($isSupp) {
        $supplierId = (int)($u['supplier_id'] ?? 0);

        $allocs = rows("SELECT a.*, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, c.name AS coop_name
                       FROM allocations a
                       JOIN raw_batches b ON b.id=a.batch_id
                       JOIN raw_types r ON r.id=b.raw_type_id
                       JOIN suppliers s ON s.id=b.supplier_id
                       JOIN cooperatives c ON c.id=a.coop_id
                       WHERE b.supplier_id=? AND c.supplier_id=?
                       ORDER BY a.id DESC", [$supplierId, $supplierId]);
    } elseif ($isSuper || $isInduk) {
        $allocs = rows("SELECT a.*, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, c.name AS coop_name
                       FROM allocations a
                       JOIN raw_batches b ON b.id=a.batch_id
                       JOIN raw_types r ON r.id=b.raw_type_id
                       JOIN suppliers s ON s.id=b.supplier_id
                       JOIN cooperatives c ON c.id=a.coop_id
                       ORDER BY a.id DESC");
    } else {
        $allocs = [];
    }

    // AMBIL DATA BATCH DAN KOPERASI ANAK UNTUK DROPDOWN
    if ($canAllocate) {
        if ($isSuper || $isInduk) {
            $batches = rows("SELECT b.id, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, b.quantity,
                                   (b.quantity - COALESCE((SELECT SUM(quantity) FROM allocations WHERE batch_id = b.id AND status != 'rejected'), 0)) AS remaining_qty
                            FROM raw_batches b
                            JOIN raw_types r ON r.id = b.raw_type_id
                            JOIN suppliers s ON s.id = b.supplier_id
                            ORDER BY b.id DESC");
            $coops = rows("SELECT c.id, c.name FROM cooperatives c ORDER BY c.name");
        } else {
            $supplierId = (int)($u['supplier_id'] ?? 0);
            $batches = rows("SELECT b.id, b.batch_code, r.name AS raw_name, s.legal_name AS supplier_name, b.quantity,
                                   (b.quantity - COALESCE((SELECT SUM(quantity) FROM allocations WHERE batch_id = b.id AND status != 'rejected'), 0)) AS remaining_qty
                            FROM raw_batches b
                            JOIN raw_types r ON r.id = b.raw_type_id
                            JOIN suppliers s ON s.id = b.supplier_id
                            WHERE b.supplier_id=?
                            ORDER BY b.id DESC", [$supplierId]);
            $coops = rows("SELECT c.id, c.name FROM cooperatives c WHERE c.supplier_id=? ORDER BY c.name", [$supplierId]);
        }
    } else {
        $batches = [];
        $coops   = [];
    }
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .allocations-container {
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

<div class="allocations-container">
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
                    <div class="role-badge-crisp mb-3"><i class="bi bi-arrow-left-right"></i> RAW MATERIAL DISTRIBUTION</div>
                    <h2 class="banner-title mb-2">Alokasi Distribusi Pasokan Limbah Mentah 🔄</h2>
                    <p class="banner-subtitle mb-0">
                        <?= $isCoop ? 'Daftar pasokan limbah mentah yang dikirim dari Koperasi Induk Sektor ke Koperasi Anak Anda.' : 'Penyaluran pasokan limbah mentah dari PT Supplier via Koperasi Induk ke Koperasi Anak binaan (Min 5 Anak).' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canAllocate): ?>
    <div class="card border-0 section-card mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-send-plus-fill me-2" style="color:#0077B6"></i>Kirim Alokasi Bahan ke Koperasi Anak Sektor</h5>
            <p class="text-muted small mb-4">Pilih batch limbah mentah dari PT Supplier dan tentukan Koperasi Anak penerima di sektor Anda.</p>

            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="allocate">

                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <?php 
                        $batchOpts = [];
                        foreach ($batches as $bt) {
                            $rem = max(0, (float)$bt['remaining_qty']);
                            $batchOpts[$bt['id']] = $bt['batch_code'] . ' - ' . $bt['raw_name'] . ' (' . $bt['supplier_name'] . ') [Sisa: ' . number($rem) . ' kg]';
                        }
                        select_field('Pilih Batch Limbah Mentah PT Supplier', 'batch_id', $batchOpts);
                        ?>
                    </div>

                    <div class="col-12 col-md-4">
                        <?php select_field('Pilih Koperasi Anak Tujuan', 'coop_id', array_column($coops, 'name', 'id')); ?>
                    </div>

                    <div class="col-12 col-md-3">
                        <?php field('Jumlah Alokasi (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"', 'Contoh: 500'); ?>
                    </div>

                    <div class="col-12 text-end mt-3">
                        <button class="btn btn-ocean-primary">
                            <i class="bi bi-send-fill me-1"></i> Kirim Alokasi Ke Koperasi Anak
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 section-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-check me-2" style="color:#0077B6"></i>Daftar Alokasi Pasokan Limbah &bull; <?= count($allocs) ?></h5>
                <span class="badge bg-white text-dark border px-3 py-1.5 rounded-pill small">PT Supplier &rarr; Koperasi Induk &rarr; Koperasi Anak</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#F1F5F9; font-size:0.8rem; color:#475569; text-transform:uppercase;">
                        <tr>
                            <th class="ps-4 py-3">Kode Batch</th>
                            <th>PT Supplier Asal</th>
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
                            <td><span class="badge bg-info bg-opacity-10 text-primary border border-primary px-2.5 py-1 rounded-pill"><?= e($al['raw_name']) ?></span></td>
                            <td><strong class="fs-6 text-dark"><?= number($al['quantity']) ?></strong> <small class="text-muted">kg</small></td>
                            <td><?= badge($al['status']) ?></td>
                            <?php if ($isCoop): ?>
                                <td class="text-center">
                                    <?php if ($al['status'] === 'proposed'): ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            <?php action_form('respond_allocation', (int)$al['id'], 'Terima Pasokan', 'btn-success btn-sm font-semibold', ['status' => 'accepted']); ?>
                                            <?php action_form('respond_allocation', (int)$al['id'], 'Tolak', 'btn-outline-danger btn-sm font-semibold', ['status' => 'rejected']); ?>
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
                <?php if (!$allocs): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Data Alokasi Bahan Mentah</h6>
                        <p class="text-secondary small mb-0">Pasokan yang dialokasikan akan muncul di tabel ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>