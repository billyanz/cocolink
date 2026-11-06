<?php
function page_verification(): void {
    $u       = user();
    $isSuper = $u['role'] === 'super_admin';
    $isInduk = in_array($u['role'], ['induk', 'supplier'], true);

    if ($isSuper || $u['role'] === 'induk') {
        $suppliers = rows("SELECT s.*, 
                          (SELECT email FROM users WHERE supplier_id=s.id LIMIT 1) AS supplier_email,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS total_coops
                          FROM suppliers s 
                          ORDER BY FIELD(s.status,'pending','rejected','verified'), s.id DESC");

        $coops = rows("SELECT c.*, s.legal_name, r.name AS raw_name,
                       (SELECT email FROM users WHERE coop_id=c.id LIMIT 1) AS coop_email
                       FROM cooperatives c 
                       JOIN suppliers s ON s.id=c.supplier_id 
                       JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                       ORDER BY FIELD(c.status,'pending','rejected','verified'), c.id DESC");
    } else {
        $sid = $u['supplier_id'];
        $suppliers = rows("SELECT s.*, 
                          (SELECT email FROM users WHERE supplier_id=s.id LIMIT 1) AS supplier_email,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS total_coops
                          FROM suppliers s WHERE s.id=?", [$sid]);

        $coops = rows("SELECT c.*, s.legal_name, r.name AS raw_name,
                       (SELECT email FROM users WHERE coop_id=c.id LIMIT 1) AS coop_email
                       FROM cooperatives c 
                       JOIN suppliers s ON s.id=c.supplier_id 
                       JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                       WHERE c.supplier_id=?
                       ORDER BY c.id DESC", [$sid]);
    }

    $rawTypes = rows("SELECT id, name FROM raw_types ORDER BY name");

    // Kelompokkan Koperasi Anak berdasarkan supplier_id
    $coopsBySupplier = [];
    foreach ($coops as $c) {
        $coopsBySupplier[$c['supplier_id']][] = $c;
    }
?>

<!-- ASSETS GOOGLE FONT (PLUS JAKARTA SANS) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .verification-container {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0F172A;
    }

    /* BANNER OCEAN THEME WITH SILHOUETTE */
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
        opacity: 0.12;
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
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

    /* SEARCH FILTER STYLING */
    .search-filter-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .search-input-group {
        position: relative;
    }
    .search-input-group i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.1rem;
    }
    .search-input-group input {
        padding-left: 42px;
        border-radius: 10px;
        border: 1px solid #CBD5E1;
        font-weight: 500;
    }
    .search-input-group input:focus {
        border-color: #0077B6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.15);
    }

    /* COLLAPSIBLE ACCORDION SUPPLIER CARD */
    .supplier-group-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        transition: all 0.25s ease;
    }
    .supplier-group-card:hover {
        border-color: #CBD5E1;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .supplier-header {
        background: #F8FAFC;
        padding: 1.25rem 1.5rem;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s ease;
    }
    .supplier-header:hover {
        background: #F1F5F9;
    }

    /* ACCORDION COLLAPSE ANIMATION */
    .supplier-collapse-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0, 1, 0, 1);
        background: #FFFFFF;
    }
    .supplier-collapse-content.expanded {
        max-height: 2000px;
        transition: max-height 0.5s ease-in-out;
    }

    .toggle-icon-chevron {
        transition: transform 0.3s ease;
        display: inline-block;
    }
    .toggle-icon-chevron.rotate {
        transform: rotate(180deg);
    }

    /* EMAIL SECTION CHIP */
    .email-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        color: #1E40AF;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* PAGINATION STYLING */
    .pagination-custom .page-link {
        border-radius: 10px;
        margin: 0 3px;
        color: #0077B6;
        font-weight: 600;
        border: 1px solid #E2E8F0;
    }
    .pagination-custom .page-item.active .page-link {
        background-color: #0077B6;
        border-color: #0077B6;
        color: #FFFFFF;
    }

    /* ANIMATED TOAST NOTIFICATION */
    .toast-verify-success {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1100;
        background: #0F172A;
        color: #FFFFFF;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: none;
        align-items: center;
        gap: 12px;
        animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes slideUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<div class="verification-container">
    <!-- BANNER OCEAN THEME WITH SILHOUETTE -->
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
                        <i class="bi bi-shield-check"></i> ACCREDITATION &amp; LEGALITY VERIFICATION
                    </div>
                    <h2 class="banner-title mb-2">Verifikasi Legalitas &amp; Kepatuhan PT Pemasok 🛡️</h2>
                    <p class="banner-subtitle mb-0">Pengesahan PT Pemasok Utama &amp; 5 Koperasi Anak Sektor Wilayah oleh Koperasi Induk Metropolitan.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CARI DAN FILTER LIVE SEARCH BAR -->
    <div class="search-filter-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-8">
                <div class="search-input-group">
                    <i class="bi bi-search"></i>
                    <input type="text" id="verificationSearchInput" class="form-control" placeholder="Cari PT Pemasok, Email, NIB, Koperasi Anak, atau Kota..." onkeyup="filterSupplierCards()">
                </div>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" style="font-size:0.85rem;">
                    <i class="bi bi-building me-1 text-primary"></i> Total PT Pemasok: <strong id="visibleCount"><?= count($suppliers) ?></strong> Perusahaan
                </span>
            </div>
        </div>
    </div>

    <!-- LIST PT PEMASOK COLLAPSIBLE (ACCORDION PER SECTION) -->
    <div id="supplierContainer">
        <?php foreach ($suppliers as $index => $s): 
            $childCoops = $coopsBySupplier[$s['id']] ?? [];
            $pageIndex = floor($index / 3) + 1; // 3 PT per halaman
            
            // Susun string data pencarian lengkap
            $searchString = strtolower(e($s['legal_name'] . ' ' . $s['nib'] . ' ' . ($s['supplier_email'] ?? '') . ' ' . $s['city'] . ' ' . implode(' ', array_map(fn($x) => $x['name'] . ' ' . ($x['coop_email'] ?? '') . ' ' . $x['city'], $childCoops))));
        ?>
            <div class="supplier-group-card supplier-item-card" data-page="<?= $pageIndex ?>" data-search="<?= $searchString ?>">
                
                <!-- HEADER AKORDION PT PEMASOK -->
                <div class="supplier-header d-flex justify-content-between align-items-center flex-wrap gap-3" onclick="toggleSupplierAccordion(<?= $s['id'] ?>)">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light btn-sm rounded-circle p-2 border" type="button" style="width:36px; height:36px;">
                            <i class="bi bi-chevron-down toggle-icon-chevron" id="chevronIcon<?= $s['id'] ?>"></i>
                        </button>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <strong class="text-dark fs-5 fw-bold" style="letter-spacing:-0.3px;"><?= e($s['legal_name']) ?></strong>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-card-text me-1"></i>NIB: <?= e($s['nib']) ?></span>
                                
                                <!-- EMAIL PENGGUNA PT -->
                                <span class="email-chip"><i class="bi bi-envelope-fill"></i><?= e($s['supplier_email'] ?: 'Email Belum Didaftarkan') ?></span>
                            </div>
                            <div class="small text-secondary">
                                <i class="bi bi-geo-alt-fill me-1 text-primary"></i><?= e($s['city'] . ', ' . $s['province']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap" onclick="event.stopPropagation()">
                        <!-- INDIKATOR SYARAT 5 SEKTOR -->
                        <div>
                            <?php if ($s['verified_coops'] >= 5): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>5 / 5 Sektor Valid</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= $s['verified_coops'] ?> / 5 (Kurang Sektor)</span>
                            <?php endif; ?>
                        </div>

                        <!-- BADGE STATUS VERIFIKASI PT -->
                        <div><?= badge($s['status']) ?></div>

                        <!-- AKSI VERIFIKASI PT (MENCEGAH VERIFIKASI 2X) -->
                        <div>
                            <?php if ($isSuper || $u['role'] === 'induk'): ?>
                                <?php if ($s['status'] === 'verified'): ?>
                                    <button class="btn btn-outline-success btn-sm fw-bold disabled" style="border-radius:8px;" disabled>
                                        <i class="bi bi-check-all me-1"></i> Terverifikasi (Terkunci)
                                    </button>
                                <?php else: ?>
                                    <div class="d-flex gap-2">
                                        <form method="post" onsubmit="return handleVerifySubmit(this, 'ACC PT <?= e($s['legal_name']) ?> oleh Koperasi Induk?')">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="verify_supplier">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="status" value="verified">
                                            <button class="btn btn-success btn-sm fw-bold px-3" style="border-radius:8px;"><i class="bi bi-check-lg me-1"></i> ACC Induk</button>
                                        </form>

                                        <form method="post" onsubmit="return handleVerifySubmit(this, 'Tolak pendaftaran PT ini?')">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="verify_supplier">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn btn-outline-danger btn-sm fw-bold" style="border-radius:8px;">Tolak</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="small text-muted"><i class="bi bi-lock me-1"></i>Otoritas Induk</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ISI PANEL COLLAPSIBLE (DETAIL SECTION & 5 KOPERASI ANAK) -->
                <div class="supplier-collapse-content" id="collapseContent<?= $s['id'] ?>">
                    <div class="p-4 border-top">
                        
                        <!-- RINGKASAN DETAIL AKUN PT INTI -->
                        <div class="p-3 mb-4 rounded-3 border bg-light">
                            <div class="row g-3 small">
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block mb-1">Email Pengguna PT:</span>
                                    <strong class="text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-envelope text-primary"></i> <?= e($s['supplier_email'] ?: 'Belum Ada Akun') ?>
                                    </strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block mb-1">NIB Legalitas:</span>
                                    <strong class="text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-file-earmark-text text-primary"></i> <?= e($s['nib']) ?>
                                    </strong>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="text-muted d-block mb-1">Wilayah Operasional:</span>
                                    <strong class="text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-geo-alt text-primary"></i> <?= e($s['address'] . ', ' . $s['city']) ?>
                                    </strong>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="text-muted d-block mb-1">Status Sektor Binaan:</span>
                                    <strong class="text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-shop text-primary"></i> <?= $s['verified_coops'] ?> Dari 5 Sektor Terverifikasi
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- FORM TAMBAH KOPERASI SEKTOR PENGGANTI JIKA Sektor < 5 -->
                        <?php if ($s['verified_coops'] < 5 && ($isSuper || $isInduk)): ?>
                            <div class="p-3 mb-4 rounded-3 border border-warning" style="background:#FFFDF5;">
                                <strong class="text-warning-emphasis small d-block mb-1"><i class="bi bi-plus-circle-fill me-1"></i>Tambah Koperasi Anak Sektor Pengganti</strong>
                                <p class="small text-muted mb-2" style="font-size:0.8rem;">Syarat pengesahan membutuhkan minimal 5 Koperasi Sektor aktif. Lengkapi sektor yang kurang di bawah ini:</p>
                                <form method="post">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="add_replacement_coop">
                                    <input type="hidden" name="supplier_id" value="<?= $s['id'] ?>">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" name="name" required placeholder="Nama Koperasi Sektor">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="city" required placeholder="Sektor (misal: JakTim)">
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-sm" name="specialty_raw_type_id" required>
                                                <?php foreach ($rawTypes as $rt): ?>
                                                    <option value="<?= $rt['id'] ?>"><?= e($rt['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="address" required placeholder="Alamat Gudang">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-warning btn-sm w-100 fw-bold" style="border-radius:8px;"><i class="bi bi-plus-lg me-1"></i> Tambah</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- DAFTAR 5 KOPERASI ANAK SEKTOR WILAYAH BINAAN -->
                        <h6 class="fw-bold text-dark mb-3 small text-uppercase" style="letter-spacing:0.5px;">
                            <i class="bi bi-shop me-1 text-primary"></i> Daftar Koperasi Anak Sektor Binaan (<?= count($childCoops) ?> / 5)
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 border rounded">
                                <thead class="bg-light">
                                    <tr style="font-size:0.85rem;">
                                        <th class="ps-3">Nama Koperasi Anak</th>
                                        <th>Email Akun Login</th>
                                        <th>Spesialis Olahan</th>
                                        <th>Sektor Wilayah</th>
                                        <th>Status Verifikasi</th>
                                        <th class="text-center">Aksi Keputusan Sektor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($childCoops as $c): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= e($c['name']) ?></td>
                                        <td>
                                            <span class="email-chip" style="font-size:0.75rem;">
                                                <i class="bi bi-envelope"></i><?= e($c['coop_email'] ?: 'Belum Ada Akun') ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?= e($c['raw_name']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($c['city']) ?></span></td>
                                        <td><?= badge($c['status']) ?></td>
                                        <td class="text-center">
                                            <?php if ($c['status'] === 'verified'): ?>
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <span class="badge bg-success bg-opacity-10 text-success border px-3 py-1.5 fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Terverifikasi</span>
                                                    <?php if ($isSuper || $isInduk): ?>
                                                        <form method="post" onsubmit="return confirm('Hapus Koperasi Anak ini dari ekosistem?')" class="d-inline">
                                                            <?= csrf_input() ?>
                                                            <input type="hidden" name="action" value="delete_coop">
                                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                            <button class="btn btn-link text-danger p-0 ms-1" title="Hapus Sektor"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <form method="post" onsubmit="return handleVerifySubmit(this, 'ACC Koperasi Sektor <?= e($c['name']) ?>?')">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="verify_coop">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <input type="hidden" name="status" value="verified">
                                                        <button class="btn btn-success btn-sm fw-bold px-3" style="border-radius:8px;"><i class="bi bi-check-lg me-1"></i> ACC Sektor</button>
                                                    </form>

                                                    <form method="post" onsubmit="return confirm('Tolak Koperasi Anak ini?')">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="verify_coop">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button class="btn btn-outline-danger btn-sm fw-bold" style="border-radius:8px;">Tolak</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$childCoops): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3 small">Belum ada Koperasi Anak Sektor terdaftar di bawah PT ini.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

        <?php if (!$suppliers): ?>
            <div class="card border-0 shadow-sm p-5 text-center" style="border-radius:18px;">
                <div class="display-5 text-muted mb-3"><i class="bi bi-inbox"></i></div>
                <h5 class="fw-bold text-dark">Belum Ada Data PT Pemasok</h5>
                <p class="text-muted small mb-0">Belum ada pendaftaran PT Pemasok Utama yang terdaftar dalam sistem.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PAGINATION SECTIONS (1, 2, 3...) -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="small text-muted fw-medium" id="paginationInfo">
            Menampilkan Halaman <strong id="currentPageNum">1</strong> dari <strong id="totalPagesNum">1</strong>
        </div>
        <nav>
            <ul class="pagination pagination-custom mb-0" id="paginationControls">
                <!-- Disuntikkan via JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- ANIMATED TOAST FEEDBACK ACC VERIFIKASI -->
<div class="toast-verify-success" id="verifyToast">
    <i class="bi bi-check-circle-fill text-success fs-4"></i>
    <div>
        <strong class="d-block" style="font-size:0.9rem;">Verifikasi Berhasil!</strong>
        <small class="text-white-50" style="font-size:0.75rem;">Status legalitas telah resmi disahkan dan dikunci.</small>
    </div>
</div>

<!-- JAVASCRIPT ACCORDION TOGGLE, SEARCH BAR, PAGINATION & TOAST -->
<script>
let currentPage = 1;
const itemsPerPage = 3;

document.addEventListener("DOMContentLoaded", function() {
    setupPagination();
    showPage(1);
    
    // Buka akordion pertama secara otomatis
    const firstCard = document.querySelector('.supplier-item-card');
    if (firstCard) {
        const firstId = firstCard.querySelector('[id^="collapseContent"]').id.replace('collapseContent', '');
        toggleSupplierAccordion(parseInt(firstId));
    }
});

// Toggle Accordion Slide Down/Up
function toggleSupplierAccordion(id) {
    const content = document.getElementById('collapseContent' + id);
    const chevron = document.getElementById('chevronIcon' + id);

    if (content.classList.contains('expanded')) {
        content.classList.remove('expanded');
        chevron.classList.remove('rotate');
    } else {
        // Tutup akordion lain terlebih dahulu
        document.querySelectorAll('.supplier-collapse-content').forEach(el => el.classList.remove('expanded'));
        document.querySelectorAll('.toggle-icon-chevron').forEach(el => el.classList.remove('rotate'));

        content.classList.add('expanded');
        chevron.classList.add('rotate');
    }
}

// Fitur Filter Search Bar Live (Saring Nama, Email, NIB, Sektor)
function filterSupplierCards() {
    const input = document.getElementById('verificationSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.supplier-item-card');
    let visible = 0;

    cards.forEach(card => {
        const searchData = card.getAttribute('data-search');
        if (searchData.includes(input)) {
            card.style.display = "";
            visible++;
            
            // Buka akordion otomatis jika sedang dicari
            if (input !== "") {
                const id = card.querySelector('[id^="collapseContent"]').id.replace('collapseContent', '');
                document.getElementById('collapseContent' + id).classList.add('expanded');
                document.getElementById('chevronIcon' + id).classList.add('rotate');
            }
        } else {
            card.style.display = "none";
        }
    });

    document.getElementById('visibleCount').innerText = visible;
    if (input !== "") {
        document.getElementById('paginationControls').style.display = "none";
        document.getElementById('paginationInfo').style.display = "none";
    } else {
        document.getElementById('paginationControls').style.display = "flex";
        document.getElementById('paginationInfo').style.display = "block";
        showPage(1);
    }
}

// Fitur Pagination (1, 2, 3)
function setupPagination() {
    const cards = document.querySelectorAll('.supplier-item-card');
    const totalPages = Math.ceil(cards.length / itemsPerPage) || 1;
    const paginationControls = document.getElementById('paginationControls');
    
    document.getElementById('totalPagesNum').innerText = totalPages;
    paginationControls.innerHTML = "";

    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === 1 ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="showPage(${i})">${i}</a>`;
        paginationControls.appendChild(li);
    }
}

function showPage(page) {
    currentPage = page;
    const cards = document.querySelectorAll('.supplier-item-card');
    
    cards.forEach((card, index) => {
        const itemPage = Math.floor(index / itemsPerPage) + 1;
        if (itemPage === page) {
            card.style.display = "";
        } else {
            card.style.display = "none";
        }
    });

    document.getElementById('currentPageNum').innerText = page;
    const lis = document.querySelectorAll('#paginationControls .page-item');
    lis.forEach((li, idx) => {
        if (idx + 1 === page) li.classList.add('active');
        else li.classList.remove('active');
    });
}

// Form Handler dengan Toast Animation
function handleVerifySubmit(form, message) {
    if (confirm(message)) {
        const toast = document.getElementById('verifyToast');
        toast.style.display = 'flex';
        setTimeout(() => {
            form.submit();
        }, 500);
    }
    return false;
}
</script>
<?php } ?>