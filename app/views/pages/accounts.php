<?php
function page_accounts(?array $u = null): void {
    $u ??= user();
    must_role(['super_admin', 'induk', 'supplier']);

    $isSuper = $u['role'] === 'super_admin';

    if ($isSuper) {
        $users = rows("SELECT u.*, s.legal_name AS supplier_name, c.name AS coop_name, b.company_name AS buyer_name 
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      LEFT JOIN buyer_profiles b ON b.id=u.buyer_id 
                      ORDER BY u.id DESC");
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE status='verified' ORDER BY city, name");
    } else {
        $sid = (int)$u['supplier_id'];
        $users = rows("SELECT u.*, s.legal_name AS supplier_name, c.name AS coop_name 
                      FROM users u 
                      LEFT JOIN suppliers s ON s.id=u.supplier_id 
                      LEFT JOIN cooperatives c ON c.id=u.coop_id 
                      WHERE c.supplier_id=? OR (u.supplier_id=? AND u.role='supplier')
                      ORDER BY u.id DESC", [$sid, $sid]);
        $coops = rows("SELECT id, name, city FROM cooperatives WHERE supplier_id=? AND status='verified' ORDER BY city, name", [$sid]);
    }
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1F6348 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-people-fill"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Manajemen Akun Sektor Wilayah Regional</h5>
                <p class="mb-0 small text-white-50">
                    <?= $isSuper ? 'Kelola seluruh pengguna ekosistem platform CoCoLink nasional.' : 'Terbitkan akun akses login untuk Koperasi Anak Sektor & Pembeli Industri di wilayah kota Anda.' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FORM TERBITKAN AKUN ELEGAN PER SEKTOR WILAYAH -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-person-plus-fill me-2" style="color:#164B35"></i>Terbitkan Akun Sektor Wilayah / Pembeli Industri</h5>
        <p class="text-muted small mb-4">Buatkan akses email dan password untuk pengelola Koperasi Anak sektor (misal: JakTim, JakSel) atau akun Pembeli Industri.</p>

        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_user">
            
            <div class="row g-3">
                <div class="col-md-4">
                    <?php field('Nama Pengelola / Penanggung Jawab', 'name', 'text', '', 'required', 'Contoh: Budi Santoso (Sektor JakTim)'); ?>
                </div>
                <div class="col-md-4">
                    <?php field('Email Akses Login', 'email', 'email', '', 'required', 'koperasi.jaktim@cocolink.test'); ?>
                </div>
                <div class="col-md-4">
                    <?php field('Kata Sandi (Password)', 'password', 'password', '', 'required', '••••••••'); ?>
                </div>

                <div class="col-md-6">
                    <?php select_field('Peran Akses System', 'role', [
                        'coop'   => 'Koperasi Anak Olahan Sektor Wilayah',
                        'buyer'  => 'Pembeli Industri B2B (Lingkup Kota)',
                        'driver' => 'Driver Armada Logistik'
                    ], 'coop', 'required'); ?>
                </div>

                <div class="col-md-6">
                    <?php 
                    $coopOpts = [];
                    foreach ($coops as $cp) {
                        $coopOpts[$cp['id']] = $cp['name'] . ' (' . $cp['city'] . ')';
                    }
                    select_field('Pilih Koperasi Anak Sektor Wilayah', 'coop_id', $coopOpts);
                    ?>
                </div>

                <div class="col-12 text-end mt-3">
                    <button class="btn btn-coco fw-bold px-4 py-2 shadow-sm" style="border-radius:10px;">
                        <i class="bi bi-person-check-fill me-1"></i> Simpan & Terbitkan Akun
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TABEL DAFTAR AKUN TERDAFTAR -->
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2" style="color:#164B35"></i>Daftar Akun Pengelola Sektor Wilayah &bull; <?= count($users) ?></h5>
            <span class="badge bg-light text-dark border">Lingkup Wilayah Kota</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Pengguna</th>
                        <th>Email Login</th>
                        <th>Peran System</th>
                        <th>Sektor Wilayah / PT Terpaut</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $usr): ?>
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark"><?= e($usr['name']) ?></strong>
                            <?php if ((int)$usr['id'] === (int)$u['id']): ?>
                                <span class="badge bg-secondary ms-1">Akun Utama PT</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-muted small"><i class="bi bi-envelope me-1"></i><?= e($usr['email']) ?></span></td>
                        <td><span class="badge bg-light text-dark border fw-semibold"><?= e(strtoupper($usr['role'])) ?></span></td>
                        <td><small class="fw-medium text-dark"><?= e($usr['coop_name'] ?: ($usr['supplier_name'] ?: '—')) ?></small></td>
                        <td>
                            <?php if ((int)$usr['is_active'] === 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$users) table_empty('Belum ada akun pengelola sektor terdaftar.'); ?>
        </div>
    </div>
</div>
<?php } ?>