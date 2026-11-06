<?php
function page_accounts(array $u): void {
    must_role(['super_admin', 'induk', 'supplier']);

    $isSuper = $u['role'] === 'super_admin';

    if ($isSuper) {
        $users = rows("SELECT u.*, 
                      s.legal_name AS supplier_name, 
                      c.name AS coop_name, 
                      b.company_name AS buyer_name,
                      (SELECT COUNT(*) FROM cooperatives WHERE supplier_id = u.supplier_id AND status='verified') AS child_coop_count
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                      ORDER BY FIELD(u.role, 'super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver'), u.id DESC");
        
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE status='verified' ORDER BY city, name");
        $suppliers = rows("SELECT id, legal_name FROM suppliers WHERE status='verified' ORDER BY legal_name");
    } else {
        $sid = (int)($u['supplier_id'] ?? 0);
        $users = rows("SELECT u.*, 
                      s.legal_name AS supplier_name, 
                      c.name AS coop_name, 
                      b.company_name AS buyer_name,
                      (SELECT COUNT(*) FROM cooperatives WHERE supplier_id = ? AND status='verified') AS child_coop_count
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                      WHERE c.supplier_id=? OR (u.supplier_id=? AND u.role IN ('induk', 'supplier', 'driver', 'coop'))
                      ORDER BY FIELD(u.role, 'induk', 'supplier', 'coop', 'buyer', 'driver'), u.id DESC", [$sid, $sid, $sid]);
        
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE supplier_id=? AND status='verified' ORDER BY city, name", [$sid]);
        $suppliers = rows("SELECT id, legal_name FROM suppliers WHERE id=? ORDER BY legal_name", [$sid]);
    }

    $totalUsers      = count($users);
    $superAdminUsers = count(array_filter($users, fn($x) => $x['role'] === 'super_admin'));
    $indukUsers      = count(array_filter($users, fn($x) => $x['role'] === 'induk'));
    $supplierUsers   = count(array_filter($users, fn($x) => $x['role'] === 'supplier'));
    $coopUsers       = count(array_filter($users, fn($x) => $x['role'] === 'coop'));
    $buyerUsers      = count(array_filter($users, fn($x) => $x['role'] === 'buyer'));
    $driverUsers     = count(array_filter($users, fn($x) => $x['role'] === 'driver'));
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .accounts-page-container {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0F172A;
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
        padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700;
        letter-spacing: 0.8px; text-transform: uppercase;
    }
    .banner-title { color: #FFFFFF; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.5px; }
    .banner-subtitle { color: #E0F2FE; font-size: 0.95rem; font-weight: 500; }

    .kpi-card-modern {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1rem 1.25rem;
        transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .kpi-card-modern:hover {
        border-color: #CBD5E1; box-shadow: 0 8px 18px -4px rgba(0,0,0,0.06); transform: translateY(-2px);
    }
    .kpi-label { color: #64748B; font-weight: 600; font-size: 0.78rem; margin-bottom: 0.25rem; }
    .kpi-number { color: #0F172A; font-weight: 800; font-size: 1.5rem; line-height: 1.2; }
    .kpi-icon-box {
        width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
    }

    .form-section-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .form-section-header { background: #F8FAFC; padding: 1.25rem 1.5rem; border-bottom: 1px solid #E2E8F0; }

    .search-filter-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    .filter-tab-btn {
        background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; border-radius: 10px;
        padding: 7px 14px; font-size: 0.8rem; font-weight: 600; transition: all 0.2s ease; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-tab-btn:hover { background: #F1F5F9; color: #0F172A; border-color: #CBD5E1; }
    .filter-tab-btn.active { background: #0077B6; color: #FFFFFF; border-color: #0077B6; box-shadow: 0 4px 10px rgba(0, 119, 182, 0.25); }
    .filter-tab-btn.active-gold {
        background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: #FFFFFF; border-color: #B45309; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
    }

    .search-input-group { position: relative; }
    .search-input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1.1rem; }
    .search-input-group input { padding-left: 42px; border-radius: 10px; border: 1px solid #CBD5E1; font-weight: 500; }

    .badge-super-admin-gold {
        background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); color: #92400E; border: 1px solid #FCD34D;
        padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;
    }
    .role-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 8px;
        font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .role-chip-induk { background: #EEF2FF; border: 1px solid #C7D2FE; color: #3730A3; }
    .role-chip-supplier { background: #F0F9FF; border: 1px solid #BAE6FD; color: #0369A1; }
    .role-chip-coop { background: #F0FDF4; border: 1px solid #BBF7D0; color: #15803D; }
    .role-chip-buyer { background: #F8FAFC; border: 1px solid #CBD5E1; color: #334155; }
    .role-chip-driver { background: #FFF7ED; border: 1px solid #FFEDD5; color: #C2410C; }

    .email-chip {
        display: inline-flex; align-items: center; gap: 6px; background: #F0F9FF; border: 1px solid #BAE6FD;
        color: #0369A1; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; word-break: break-all;
    }
    .status-active-chip {
        display: inline-flex; align-items: center; gap: 6px; background: #E0F2FE; border: 1px solid #BAE6FD;
        color: #0369A1; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700;
    }
    .status-inactive-chip {
        display: inline-flex; align-items: center; gap: 6px; background: #FEE2E2; border: 1px solid #FCA5A5;
        color: #DC2626; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700;
    }

    .btn-ocean-primary {
        background-color: #0077B6; color: #FFFFFF; font-weight: 600; border-radius: 10px; border: none;
        padding: 10px 22px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
    }
    .btn-ocean-primary:hover { background-color: #023E8A; color: #FFFFFF; }
    .table-wrapper-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .pagination-custom .page-link { border-radius: 10px; margin: 0 3px; color: #0077B6; font-weight: 600; border: 1px solid #E2E8F0; }
    .pagination-custom .page-item.active .page-link { background-color: #0077B6; border-color: #0077B6; color: #FFFFFF; }

    @media (max-width: 768px) {
        .banner-title { font-size: 1.4rem; }
        .banner-subtitle { font-size: 0.85rem; }
        .filter-tab-group { overflow-x: auto; white-space: nowrap; padding-bottom: 6px; }
    }
</style>

<div class="accounts-page-container">
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
                    <div class="role-badge-crisp mb-3">
                        <i class="bi bi-people-fill"></i> USER ACCOUNT &amp; HIERARCHY MANAGEMENT
                    </div>
                    <h2 class="banner-title mb-2">Manajemen Akun 6 Peran Ekosistem CoCoLink 👥</h2>
                    <p class="banner-subtitle mb-0">
                        <?= $isSuper 
                            ? 'Kelola akun Super Admin, Koperasi Induk Sektor, PT Supplier Limbah, Koperasi Anak, Buyer, dan Driver Logistik.' 
                            : 'Terbitkan akun akses login Koperasi Anak Sektor, Pembeli B2B, dan Driver Armada di wilayah Anda.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- METRIK KPI STATISTIK -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Super Admin</div>
                    <div class="kpi-number"><?= number((float)$superAdminUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#FEF3C7; color:#D97706;"><i class="bi bi-crown"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Kop. Induk</div>
                    <div class="kpi-number"><?= number((float)$indukUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#EEF2FF; color:#3730A3;"><i class="bi bi-diagram-3-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">PT Supplier</div>
                    <div class="kpi-number"><?= number((float)$supplierUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#F0F9FF; color:#0369A1;"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Kop. Anak</div>
                    <div class="kpi-number"><?= number((float)$coopUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#DCFCE7; color:#15803D;"><i class="bi bi-shop"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Buyer B2B</div>
                    <div class="kpi-number"><?= number((float)$buyerUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#F1F5F9; color:#334155;"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label">Driver Logistik</div>
                    <div class="kpi-number"><?= number((float)$driverUsers) ?></div>
                </div>
                <div class="kpi-icon-box" style="background:#FFF7ED; color:#C2410C;"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>

    <!-- FORM TERBITKAN AKUN -->
    <div class="form-section-card mb-4">
        <div class="form-section-header">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-plus-fill me-2" style="color:#0077B6;"></i>Terbitkan Akun Baru Sesuai Peran Ekosistem
            </h5>
            <p class="text-secondary small mb-0">
                Pilih peran pengelola yang sesuai. <span class="text-primary fw-semibold">*Koperasi Induk wajib membina minimal 5 Koperasi Anak terdaftar.</span>
            </p>
        </div>

        <div class="card-body p-4">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_user">
                
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <?php field('Nama Pengelola / Penanggung Jawab', 'name', 'text', '', 'required', 'Contoh: Budi Santoso'); ?>
                    </div>
                    <div class="col-12 col-md-4">
                        <?php field('Email Akses Login', 'email', 'email', '', 'required', 'pengelola@cocolink.test'); ?>
                    </div>
                    <div class="col-12 col-md-4">
                        <?php field('Kata Sandi (Password)', 'password', 'password', '', 'required', '••••••••'); ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <?php select_field('Peran Akses Sistem', 'role', [
                            'induk'    => '1. Koperasi Induk Sektor (Wajib Min 5 Anak)',
                            'supplier' => '2. PT Supplier Utama (Penyalur Limbah)',
                            'coop'     => '3. Koperasi Anak Olahan (Sektor Wilayah)',
                            'buyer'    => '4. Pembeli Industri B2B',
                            'driver'   => '5. Driver Armada Logistik (Kurir Pengiriman)'
                        ], 'coop', 'required'); ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <?php 
                        $coopOpts = [];
                        foreach ($coops as $cp) {
                            $coopOpts[$cp['id']] = $cp['name'] . ' (' . $cp['city'] . ')';
                        }
                        select_field('Tautkan ke Koperasi Anak (Jika Role Coop / Driver)', 'coop_id', $coopOpts);
                        ?>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button class="btn btn-ocean-primary">
                            <i class="bi bi-person-check-fill me-1"></i> Simpan &amp; Terbitkan Akun
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTER TABS & SEARCH BAR -->
    <div class="search-filter-card mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-3 mb-3">
            <div class="filter-tab-group d-flex gap-1.5 flex-wrap">
                <button type="button" class="filter-tab-btn active" onclick="setRoleFilter('all', this)">
                    <i class="bi bi-grid-fill me-1"></i> Semua (<?= $totalUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('super_admin', this)">
                    <i class="bi bi-crown-fill me-1" style="color:#D97706;"></i> Super Admin (<?= $superAdminUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('induk', this)">
                    <i class="bi bi-diagram-3-fill me-1" style="color:#3730A3;"></i> Kop. Induk (<?= $indukUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('supplier', this)">
                    <i class="bi bi-building me-1"></i> PT Supplier (<?= $supplierUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('coop', this)">
                    <i class="bi bi-shop me-1"></i> Kop. Anak (<?= $coopUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('buyer', this)">
                    <i class="bi bi-cart-check me-1"></i> Buyer (<?= $buyerUsers ?>)
                </button>
                <button type="button" class="filter-tab-btn" onclick="setRoleFilter('driver', this)">
                    <i class="bi bi-truck me-1"></i> Driver (<?= $driverUsers ?>)
                </button>
            </div>
            <div class="small text-secondary fw-semibold">
                Kategori: <span id="activeFilterLabel" class="text-primary fw-bold">Semua Akun</span>
            </div>
        </div>

        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-8">
                <div class="search-input-group">
                    <i class="bi bi-search"></i>
                    <input type="text" id="accountsSearchInput" class="form-control" placeholder="Cari nama pengelola, email login, peran sistem, atau entitas terpaut..." onkeyup="applyFilterAndSearch()">
                </div>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" style="font-size:0.85rem;">
                    <i class="bi bi-people me-1" style="color:#0077B6;"></i> Menampilkan: <strong id="visibleCount"><?= count($users) ?></strong> Akun
                </span>
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR AKUN -->
    <div class="table-wrapper-card mb-3">
        <div class="p-3.5 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#FFFFFF;">
            <div class="fw-bold fs-6" style="color:#0F172A;">
                <i class="bi bi-people-fill me-2" style="color:#0077B6;"></i>Daftar Pengguna Platform CoCoLink
            </div>
            <span class="badge bg-light text-secondary border px-3 py-1.5 fw-semibold" style="font-size:0.8rem;">
                <i class="bi bi-shield-lock me-1"></i>Akses Terbuka Berdasarkan Hak Peran
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="accountsTable">
                <thead class="bg-light">
                    <tr style="font-size:0.85rem; color:#475569;">
                        <th class="ps-4 py-3">Nama Pengguna</th>
                        <th class="py-3">Email Akses Login</th>
                        <th class="py-3">Peran Hirarki</th>
                        <th class="py-3">Sektor / Entitas Terpaut</th>
                        <th class="text-center py-3">Status Akun</th>
                    </tr>
                </thead>
                <tbody id="accountsTableBody">
                <?php foreach ($users as $usr): 
                    $roleKey = strtolower($usr['role']);
                    $searchData = strtolower(e($usr['name'] . ' ' . $usr['email'] . ' ' . $usr['role'] . ' ' . ($usr['coop_name'] ?? '') . ' ' . ($usr['supplier_name'] ?? '') . ' ' . ($usr['buyer_name'] ?? '')));
                    $childCount = (int)($usr['child_coop_count'] ?? 0);
                ?>
                    <tr class="account-row-item" data-role="<?= $roleKey ?>" data-search="<?= $searchData ?>">
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-dark d-block fw-bold"><?= e($usr['name']) ?></strong>
                                <?php if ($roleKey === 'super_admin'): ?>
                                    <i class="bi bi-patch-check-fill text-warning" title="Akun Super Admin Master"></i>
                                <?php endif; ?>
                            </div>
                            <?php if ((int)$usr['id'] === (int)$u['id']): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-0.5 mt-1" style="font-size:0.7rem;">Akun Anda saat ini</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="email-chip"><i class="bi bi-envelope-fill"></i><?= e($usr['email']) ?></span>
                        </td>
                        <td>
                            <?php if ($roleKey === 'super_admin'): ?>
                                <span class="badge-super-admin-gold"><i class="bi bi-crown-fill"></i> Super Admin</span>
                            <?php elseif ($roleKey === 'induk'): ?>
                                <span class="role-chip role-chip-induk"><i class="bi bi-diagram-3-fill"></i> Koperasi Induk</span>
                                <?php if ($childCount >= 5): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0.5 ms-1" style="font-size:0.68rem;"><i class="bi bi-check-all"></i> Valid (<?= $childCount ?> Anak)</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-0.5 ms-1" style="font-size:0.68rem;"><i class="bi bi-exclamation-triangle"></i> <?= $childCount ?>/5 Anak</span>
                                <?php endif; ?>
                            <?php elseif ($roleKey === 'supplier'): ?>
                                <span class="role-chip role-chip-supplier"><i class="bi bi-building"></i> PT Supplier</span>
                            <?php elseif ($roleKey === 'coop'): ?>
                                <span class="role-chip role-chip-coop"><i class="bi bi-shop"></i> Koperasi Anak</span>
                            <?php elseif ($roleKey === 'buyer'): ?>
                                <span class="role-chip role-chip-buyer"><i class="bi bi-cart-check"></i> Buyer B2B</span>
                            <?php elseif ($roleKey === 'driver'): ?>
                                <span class="role-chip role-chip-driver"><i class="bi bi-truck"></i> Driver Armada</span>
                            <?php else: ?>
                                <span class="role-chip"><i class="bi bi-shield-check"></i><?= e(strtoupper($usr['role'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-medium text-dark small">
                                <i class="bi bi-building me-1 text-secondary"></i>
                                <?= e($usr['coop_name'] ?: ($usr['supplier_name'] ?: ($usr['buyer_name'] ?: '—'))) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$usr['is_active'] === 1): ?>
                                <span class="status-active-chip"><i class="bi bi-check-circle-fill"></i>Aktif</span>
                            <?php else: ?>
                                <span class="status-inactive-chip"><i class="bi bi-x-circle-fill"></i>Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$users): ?>
                <div class="text-center text-muted py-5 small">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    Belum ada akun pengguna terdaftar.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div class="small text-muted fw-medium" id="paginationInfo">
            Menampilkan Halaman <strong id="currentPageNum">1</strong> dari <strong id="totalPagesNum">1</strong>
        </div>
        <nav><ul class="pagination pagination-custom mb-0" id="paginationControls"></ul></nav>
    </div>
</div>

<script>
let currentRoleFilter = 'all';
let currentPage = 1;
const itemsPerPage = 6;

document.addEventListener("DOMContentLoaded", function() {
    applyFilterAndSearch();
});

function setRoleFilter(role, btnElement) {
    currentRoleFilter = role;
    document.querySelectorAll('.filter-tab-btn').forEach(btn => btn.classList.remove('active', 'active-gold'));
    if (role === 'super_admin') btnElement.classList.add('active-gold');
    else btnElement.classList.add('active');

    const labels = {
        'all': 'Semua Akun', 'super_admin': 'Super Admin Master', 'induk': 'Koperasi Induk Sektor',
        'supplier': 'PT Supplier Limbah', 'coop': 'Koperasi Anak Olahan', 'buyer': 'Pembeli Industri B2B', 'driver': 'Driver Armada Logistik'
    };
    document.getElementById('activeFilterLabel').innerText = labels[role] || 'Semua Akun';
    applyFilterAndSearch();
}

function applyFilterAndSearch() {
    const searchInput = document.getElementById('accountsSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.account-row-item');
    const matchedRows = [];

    rows.forEach(row => {
        const role = row.getAttribute('data-role');
        const searchData = row.getAttribute('data-search');
        let matchesRole = (currentRoleFilter === 'all') || (role === currentRoleFilter);
        let matchesSearch = searchData.includes(searchInput);

        if (matchesRole && matchesSearch) matchedRows.push(row);
        else row.style.display = "none";
    });

    document.getElementById('visibleCount').innerText = matchedRows.length;
    setupPagination(matchedRows);
    showPage(1, matchedRows);
}

function setupPagination(visibleRows) {
    const totalPages = Math.ceil(visibleRows.length / itemsPerPage) || 1;
    const paginationControls = document.getElementById('paginationControls');
    document.getElementById('totalPagesNum').innerText = totalPages;
    paginationControls.innerHTML = "";
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === 1 ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="showPage(${i}, null)">${i}</a>`;
        paginationControls.appendChild(li);
    }
}

function showPage(page, customMatchedRows) {
    currentPage = page;
    const searchInput = document.getElementById('accountsSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.account-row-item');
    let matchedRows = customMatchedRows;

    if (!matchedRows) {
        matchedRows = [];
        rows.forEach(row => {
            const role = row.getAttribute('data-role');
            const searchData = row.getAttribute('data-search');
            let matchesRole = (currentRoleFilter === 'all') || (role === currentRoleFilter);
            if (matchesRole && searchData.includes(searchInput)) matchedRows.push(row);
        });
    }

    rows.forEach(r => r.style.display = "none");
    const startIndex = (page - 1) * itemsPerPage;
    matchedRows.slice(startIndex, startIndex + itemsPerPage).forEach(row => row.style.display = "");

    document.getElementById('currentPageNum').innerText = page;
    document.querySelectorAll('#paginationControls .page-item').forEach((li, idx) => {
        if (idx + 1 === page) li.classList.add('active');
        else li.classList.remove('active');
    });
}
</script>
<?php } ?>