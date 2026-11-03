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

<!-- ASSETS GOOGLE FONT (PLUS JAKARTA SANS) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .batches-page-container {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0F172A;
    }

    /* BANNER OCEAN CORPORATE THEME */
    .ocean-banner-card {
        background: linear-gradient(135deg, #0A2540 0%, #005691 50%, #0077B6 100%);
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        box-shadow: 0 12px 28px -6px rgba(0, 86, 145, 0.28);
    }
    .banner-silhouette-bg {
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }
    .banner-silhouette-bg svg {
        position: absolute;
        right: -20px;
        bottom: -20px;
        height: 125%;
        width: auto;
        opacity: 0.10;
        fill: #FFFFFF;
    }

    .role-badge-crisp {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #FFFFFF;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }
    .banner-title {
        color: #FFFFFF;
        font-weight: 800;
        font-size: 1.85rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .banner-subtitle {
        color: #E0F2FE;
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* CARD STYLING */
    .section-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
    }
    .section-card:hover {
        border-color: #CBD5E1;
        box-shadow: 0 8px 20px -4px rgba(0,0,0,0.05);
    }

    /* BUTTON OCEAN PRIMARY */
    .btn-ocean-primary {
        background-color: #0077B6;
        color: #FFFFFF;
        font-weight: 700;
        border-radius: 12px;
        border: none;
        padding: 10px 24px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
    }
    .btn-ocean-primary:hover {
        background-color: #023E8A;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(2, 62, 138, 0.35);
    }

    /* BADGE CUSTOM */
    .badge-raw-type {
        background-color: #F0F9FF;
        border: 1px solid #BAE6FD;
        color: #0284C7;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
    }

    @media (max-width: 768px) {
        .banner-title { font-size: 1.4rem; }
        .banner-subtitle { font-size: 0.85rem; }
    }
</style>

<div class="batches-page-container">
    <!-- BANNER OCEAN CORPORATE THEME WITH SILHOUETTE -->
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
                    <circle cx="137" cy="108" r="8" />
                    <circle cx="150" cy="112" r="9" />
                    <circle cx="143" cy="120" r="7.5" />
                </g>
            </svg>
        </div>

        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3">
                        <i class="bi bi-box-seam-fill"></i> HULU MANAGEMENT &bull; RAW MATERIALS
                    </div>
                    <h2 class="banner-title mb-2">Pasokan Limbah Mentah PT Pemasok 📦</h2>
                    <p class="banner-subtitle mb-0">Pencatatan volume dan akumulasi pasokan limbah mentah segar dari PT Pemasok Utama sebelum dialokasikan ke Koperasi Sektor.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM TAMBAH BATCH PASOKAN (KHUSUS SUPPLIER / MANAGER) -->
    <?php if ($supplier || $manager): ?>
    <div class="card border-0 section-card mb-4">
        <div class="card-body p-4 p-md-4.5">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="p-2 rounded-circle" style="background:#E0F2FE; color:#0077B6;">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                </div>
                <h5 class="fw-bold text-dark mb-0">Input Batch Pasokan Limbah Baru</h5>
            </div>
            <p class="text-secondary small mb-4 ms-4 ps-2">Masukkan detail volume dan jenis limbah mentah kelapa yang siap diproses.</p>

            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_batch">

                <div class="row g-3">
                    <?php if ($manager && $suppliers): ?>
                        <div class="col-12 col-md-4">
                            <?php select_field('Pilih PT Pemasok Utama', 'supplier_id', array_column($suppliers, 'legal_name', 'id')); ?>
                        </div>
                    <?php endif; ?>

                    <div class="<?= ($manager && $suppliers) ? 'col-12 col-md-4' : 'col-12 col-md-6' ?>">
                        <?php select_field('Kategori Limbah Kelapa', 'raw_type_id', array_column($rawTypes, 'name', 'id')); ?>
                    </div>

                    <div class="<?= ($manager && $suppliers) ? 'col-12 col-md-4' : 'col-12 col-md-6' ?>">
                        <?php field('Jumlah Volume (kg)', 'quantity', 'number', '', 'required step="0.01" min="0.01"', 'Contoh: 1000'); ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark small mb-1">Catatan Tambahan / Kondisi Fisik Limbah</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Contoh: Limbah tempurung kelapa kering kadar air rendah." style="border-radius:10px; border-color:#CBD5E1;"></textarea>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button class="btn btn-ocean-primary">
                            <i class="bi bi-box-arrow-in-down me-1.5"></i> Simpan Batch Limbah
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABEL DAFTAR PASOKAN BATCH -->
    <div class="card border-0 section-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text me-1 fs-5" style="color:#0077B6;"></i>
                    <h5 class="fw-bold mb-0 text-dark">Daftar Batch Pasokan Limbah</h5>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1 ms-1"><?= count($batches) ?> Record</span>
                </div>
                <span class="badge bg-white text-secondary border px-3 py-1.5 rounded-pill font-monospace small"><i class="bi bi-building me-1"></i> Gudang Mentah PT</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#F1F5F9; font-size:0.8rem; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                        <tr>
                            <th class="ps-4 py-3">Kode Batch</th>
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
                            <td class="ps-4 fw-bold text-dark"><i class="bi bi-qr-code me-1.5 text-muted"></i><?= e($b['batch_code']) ?></td>
                            <td><strong class="text-dark"><?= e($b['legal_name']) ?></strong></td>
                            <td><span class="badge-raw_type"><?= e($b['raw_name']) ?></span></td>
                            <td><strong class="fs-6 text-dark"><?= number($b['quantity']) ?></strong> <small class="text-muted">kg</small></td>
                            <td><small class="text-secondary"><?= e($b['notes'] ?: '—') ?></small></td>
                            <td><small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= e($b['created_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!$batches): ?>
                    <div class="p-5 text-center">
                        <div class="mb-3 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Data Pasokan Batch Limbah Mentah</h6>
                        <p class="text-secondary small mb-0">Gunakan formulir di atas untuk mencatat volume pasokan limbah baru.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>