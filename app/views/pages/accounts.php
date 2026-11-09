<?php
function page_accounts(array $u): void {
    // PT Supplier TIDAK BISA AKSES MANAJEMEN AKUN SESUAI POIN 2 STEP 2
    must_role(['super_admin', 'induk', 'coop']);

    $isSuper = $u['role'] === 'super_admin';
    $isInduk = $u['role'] === 'induk';
    $isCoop  = $u['role'] === 'coop';

    // ISOLASI QUERY BERDASARKAN OTORITAS ROLE LOGIN
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
    } elseif ($isInduk) {
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
                      WHERE u.supplier_id=? OR c.supplier_id=?
                      ORDER BY FIELD(u.role, 'induk', 'coop', 'buyer', 'driver'), u.id DESC", [$sid, $sid, $sid]);
        
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE supplier_id=? AND status='verified' ORDER BY city, name", [$sid]);
    } else {
        // PERAN COOP (Koperasi Anak) -> Hanya lihat akun buyer & coop lokalnya
        $cid = (int)($u['coop_id'] ?? 0);
        $users = rows("SELECT u.*, 
                      s.legal_name AS supplier_name, 
                      c.name AS coop_name, 
                      b.company_name AS buyer_name,
                      0 AS child_coop_count
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                      WHERE u.coop_id=? OR u.role='buyer'
                      ORDER BY u.id DESC", [$cid]);
        
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE id=? AND status='verified'", [$cid]);
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

    .search-input-group { position: relative; }
    .search-input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1.1rem; }
    .search-input-group input { padding-left: 42px; border-radius: 10px; border: 1px solid #CBD5E1; font-weight: 500; }

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

    .btn-ocean-primary {
        background-color: #0077B6; color: #FFFFFF; font-weight: 600; border-radius: 10px; border: none;
        padding: 10px 22px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
    }
    .btn-ocean-primary:hover { background-color: #023E8A; color: #FFFFFF; }
    .table-wrapper-card {
        background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
</style>

<div class="accounts-page-container">
    <div class="card border-0 mb-4 ocean-banner-card">
        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 1;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="role-badge-crisp mb-3">
                        <i class="bi bi-people-fill"></i> HIERARCHY ACCOUNT MANAGEMENT (STEP 2)
                    </div>
                    <h2 class="banner-title mb-2">Manajemen Akun Terisolasi Multi-Tier 👥</h2>
                    <p class="banner-subtitle mb-0">
                        <?= $isSuper 
                            ? 'Super Admin Master: Otoritas Penuh Kelola Akun Koperasi Induk & PT Supplier.' 
                            : ($isInduk ? 'Koperasi Induk: Mengelola Akun Koperasi Anak di Bawah Naungan Anda, Buyer B2B, & Driver Armada.' 
                                       : 'Koperasi Anak: Penerbitan & Pengelolaan Akun Pembeli B2B Wilayah.') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- METRIK KPI STATISTIK -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">Super Admin</div><div class="kpi-number"><?= number((float)$superAdminUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#FEF3C7; color:#D97706;"><i class="bi bi-crown"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">Kop. Induk</div><div class="kpi-number"><?= number((float)$indukUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#EEF2FF; color:#3730A3;"><i class="bi bi-diagram-3-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">PT Supplier</div><div class="kpi-number"><?= number((float)$supplierUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#F0F9FF; color:#0369A1;"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">Kop. Anak</div><div class="kpi-number"><?= number((float)$coopUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#DCFCE7; color:#15803D;"><i class="bi bi-shop"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">Buyer B2B</div><div class="kpi-number"><?= number((float)$buyerUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#F1F5F9; color:#334155;"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="kpi-card-modern d-flex align-items-center justify-content-between">
                <div><div class="kpi-label">Driver Logistik</div><div class="kpi-number"><?= number((float)$driverUsers) ?></div></div>
                <div class="kpi-icon-box" style="background:#FFF7ED; color:#C2410C;"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>

    <!-- FORM TERBITKAN AKUN BERDASARKAN HAK ROLE LOGIN -->
    <div class="form-section-card mb-4">
        <div class="form-section-header">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-plus-fill me-2" style="color:#0077B6;"></i>Terbitkan Akun Baru Sesuai Peran
            </h5>
            <p class="text-secondary small mb-0">
                Pilih peran pengelola yang diizinkan untuk dibuat oleh akun Anda.
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

                    <div class="col-12 col-md-4">
                        <?php 
                        // PILIHAN ROLE TERBATAS BERDASARKAN ATURAN STEP 2
                        $roleOptions = [];
                        if ($isSuper) {
                            $roleOptions = [
                                'induk'    => '1. Koperasi Induk Sektor',
                                'supplier' => '2. PT Supplier Utama',
                                'coop'     => '3. Koperasi Anak Olahan',
                                'buyer'    => '4. Pembeli Industri B2B',
                                'driver'   => '5. Driver Armada Logistik'
                            ];
                        } elseif ($isInduk) {
                            $roleOptions = [
                                'coop'   => '1. Koperasi Anak (Di Bawah Naungan Anda)',
                                'buyer'  => '2. Pembeli Industri B2B',
                                'driver' => '3. Driver Armada Logistik'
                            ];
                        } elseif ($isCoop) {
                            $roleOptions = [
                                'buyer' => '1. Pembeli Industri B2B (Lokal)'
                            ];
                        }
                        select_field('Peran Akses Sistem yang Diterbitkan', 'role', $roleOptions, array_key_first($roleOptions), 'required'); 
                        ?>
                    </div>

                    <div class="col-12 col-md-4">
                        <?php 
                        if ($isSuper || $isInduk) {
                            $coopOpts = [];
                            foreach ($coops as $cp) {
                                $coopOpts[$cp['id']] = $cp['name'] . ' (' . $cp['city'] . ')';
                            }
                            select_field('Tautkan ke Koperasi Anak (Untuk Role Coop/Driver)', 'coop_id', $coopOpts);
                        } else {
                            echo '<input type="hidden" name="coop_id" value="' . (int)$u['coop_id'] . '">';
                            echo '<label class="form-label fw-bold">Koperasi Anak Terpaut</label><input type="text" class="form-control" value="' . e($u['name']) . '" disabled>';
                        }
                        ?>
                    </div>

                    <div class="col-12 col-md-4">
                        <?php 
                        select_field('Kategori Driver (Khusus Role Driver)', 'driver_type', [
                            'pt_to_induk'   => '[Hulu] PT ➔ Induk',
                            'induk_to_coop' => '[Distribusi] Induk ➔ Anak',
                            'coop_to_buyer' => '[Hilir] Anak ➔ Buyer B2B'
                        ], 'coop_to_buyer');
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

    <!-- TABEL DAFTAR AKUN -->
    <div class="table-wrapper-card mb-3">
        <div class="p-3.5 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#FFFFFF;">
            <div class="fw-bold fs-6" style="color:#0F172A;">
                <i class="bi bi-people-fill me-2" style="color:#0077B6;"></i>Daftar Pengguna Berdasarkan Otoritas
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="accountsTable">
                <thead class="bg-light">
                    <tr style="font-size:0.85rem; color:#475569;">
                        <th class="ps-4 py-3">Nama Pengguna</th>
                        <th class="py-3">Email Akses Login</th>
                        <th class="py-3">Peran Hirarki &amp; Kategori Driver</th>
                        <th class="py-3">Sektor / Entitas Terpaut</th>
                        <th class="text-center py-3">Status Akun</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $usr): 
                    $roleKey = strtolower($usr['role']);
                    $childCount = (int)($usr['child_coop_count'] ?? 0);
                ?>
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark d-block fw-bold"><?= e($usr['name']) ?></strong>
                            <?php if ((int)$usr['id'] === (int)$u['id']): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-0.5 mt-1" style="font-size:0.7rem;">Akun Anda saat ini</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="email-chip"><i class="bi bi-envelope-fill"></i><?= e($usr['email']) ?></span>
                        </td>
                        <td>
                            <?php if ($roleKey === 'super_admin'): ?>
                                <span class="badge bg-warning bg-opacity-20 text-dark border border-warning px-2 py-1 rounded fw-bold"><i class="bi bi-crown-fill"></i> Super Admin</span>
                            <?php elseif ($roleKey === 'induk'): ?>
                                <span class="role-chip role-chip-induk"><i class="bi bi-diagram-3-fill"></i> Koperasi Induk</span>
                            <?php elseif ($roleKey === 'supplier'): ?>
                                <span class="role-chip role-chip-supplier"><i class="bi bi-building"></i> PT Supplier</span>
                            <?php elseif ($roleKey === 'coop'): ?>
                                <span class="role-chip role-chip-coop"><i class="bi bi-shop"></i> Koperasi Anak</span>
                            <?php elseif ($roleKey === 'buyer'): ?>
                                <span class="role-chip role-chip-buyer"><i class="bi bi-cart-check"></i> Buyer B2B</span>
                            <?php elseif ($roleKey === 'driver'): ?>
                                <span class="role-chip role-chip-driver"><i class="bi bi-truck"></i> Driver Logistik</span>
                                <span class="badge bg-light text-dark border ms-1"><?= e($usr['driver_type'] ?? 'coop_to_buyer') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-medium text-dark small">
                                <i class="bi bi-building me-1 text-secondary"></i>
                                <?= e($usr['coop_name'] ?: ($usr['supplier_name'] ?: ($usr['buyer_name'] ?: '—'))) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="status-active-chip"><i class="bi bi-check-circle-fill"></i>Aktif</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php } ?>