<?php
function page_verification(): void {
    $u       = user();
    $isSuper = $u['role'] === 'super_admin';
    $isInduk = in_array($u['role'], ['induk', 'supplier'], true);

    if ($isSuper || $u['role'] === 'induk') {
        $suppliers = rows("SELECT s.*, 
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS total_coops
                          FROM suppliers s 
                          ORDER BY FIELD(s.status,'pending','rejected','verified'), s.id DESC");

        $coops = rows("SELECT c.*, s.legal_name, r.name AS raw_name 
                       FROM cooperatives c 
                       JOIN suppliers s ON s.id=c.supplier_id 
                       JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                       ORDER BY FIELD(c.status,'pending','rejected','verified'), c.id DESC");
    } else {
        $sid = $u['supplier_id'];
        $suppliers = rows("SELECT s.*, 
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops,
                          (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS total_coops
                          FROM suppliers s WHERE s.id=?", [$sid]);

        $coops = rows("SELECT c.*, s.legal_name, r.name AS raw_name 
                       FROM cooperatives c 
                       JOIN suppliers s ON s.id=c.supplier_id 
                       JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                       WHERE c.supplier_id=?
                       ORDER BY c.id DESC", [$sid]);
    }

    $rawTypes = rows("SELECT id, name FROM raw_types ORDER BY name");
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-shield-check"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Verifikasi Legalitas & Kepatuhan PT Pemasok Jabodetabek</h5>
                <p class="mb-0 small text-white-50">
                    Pengesahan PT Pemasok Utama & 5 Koperasi Anak Sektor Wilayah oleh Koperasi Induk Metropolitan.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- TABEL PT PEMASOK REGIONAL -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2" style="color:#164B35"></i>Pemeriksaan PT Pemasok Utama (Jabodetabek)</h5>
            <span class="badge bg-light text-dark border">Syarat: Wajib 5 Anak Sektor Valid</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama PT / NIB</th>
                        <th>Wilayah Operasional</th>
                        <th>Koperasi Anak Sektor</th>
                        <th>Status Verifikasi</th>
                        <th class="text-center">Aksi Keputusan Koperasi Induk</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td class="ps-4">
                            <strong class="text-dark d-block"><?= e($s['legal_name']) ?></strong>
                            <small class="text-muted"><i class="bi bi-card-text me-1"></i>NIB: <?= e($s['nib']) ?></small>
                        </td>
                        <td><i class="bi bi-geo-alt me-1 text-secondary"></i><?= e($s['city'] . ', ' . $s['province']) ?></td>
                        <td>
                            <?php if ($s['verified_coops'] >= 5): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><i class="bi bi-check-circle me-1"></i><?= $s['verified_coops'] ?> / 5 Sektor Valid</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i><?= $s['verified_coops'] ?> / 5 (Kurang Sektor)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= badge($s['status']) ?></td>
                        <td class="text-center">
                            <?php if ($isSuper || $u['role'] === 'induk'): ?>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php action_form('verify_supplier', (int)$s['id'], 'ACC Koperasi Induk', 'btn-success btn-sm', ['status' => 'verified']); ?>
                                    <?php action_form('verify_supplier', (int)$s['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                                </div>
                            <?php else: ?>
                                <span class="small text-muted">Di-ACC Koperasi Induk</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$suppliers) table_empty('Belum ada pendaftaran PT Pemasok.'); ?>
        </div>
    </div>
</div>

<!-- FORM TAMBAH KOPERASI SEKTOR PENGGANTI -->
<?php foreach ($suppliers as $s): ?>
    <?php if ($s['verified_coops'] < 5 && ($isSuper || $isInduk)): ?>
        <div class="card border-warning mb-4 shadow-sm" style="border-radius:16px; background:#FFFDF3;">
            <div class="card-body p-4">
                <h5 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-plus-circle me-2"></i>Tambah Koperasi Anak Sektor Pengganti untuk <?= e($s['legal_name']) ?></h5>
                <p class="small text-muted mb-3">Jumlah Koperasi Anak Sektor aktif saat ini adalah <strong><?= $s['verified_coops'] ?> dari syarat minimal 5</strong>. Daftarkan Koperasi Anak Sektor pengganti agar kuota genap.</p>

                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="add_replacement_coop">
                    <input type="hidden" name="supplier_id" value="<?= $s['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="name" required placeholder="Nama Koperasi Sektor">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="city" required placeholder="Wilayah Sektor (Misal: JakTim)">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="specialty_raw_type_id" required>
                                <?php foreach ($rawTypes as $rt): ?>
                                    <option value="<?= $rt['id'] ?>"><?= e($rt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="address" required placeholder="Alamat Gudang Sektor">
                        </div>
                        <div class="col-md-10">
                            <input type="url" class="form-control form-control-sm" name="gmaps_url" required placeholder="Link Google Maps Gudang Sektor (https://maps.app.goo.gl/...)">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-warning btn-sm w-100 fw-bold"><i class="bi bi-plus-lg me-1"></i> Tambah Sektor</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- TABEL DAFTAR KOPERASI SEKTOR -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shop me-2" style="color:#164B35"></i>Daftar Koperasi Anak Sektor Wilayah &bull; <?= count($coops) ?></h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Koperasi Anak</th>
                        <th>PT Induk Pemasok</th>
                        <th>Spesialis Olahan</th>
                        <th>Sektor Wilayah</th>
                        <th>Status Verifikasi</th>
                        <th class="text-center">Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($coops as $c): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= e($c['name']) ?></td>
                        <td><?= e($c['legal_name']) ?></td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?= e($c['raw_name']) ?></span></td>
                        <td><?= e($c['city']) ?></td>
                        <td><?= badge($c['status']) ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <?php if ($c['status'] !== 'verified'): ?>
                                    <?php action_form('verify_coop', (int)$c['id'], 'ACC Sektor', 'btn-success btn-sm', ['status' => 'verified']); ?>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border px-2 py-1"><i class="bi bi-check-circle me-1"></i>Terverifikasi</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$coops) table_empty('Belum ada Koperasi Anak Sektor terdaftar.'); ?>
        </div>
    </div>
</div>
<?php } ?>