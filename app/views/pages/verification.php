<?php
function page_verification(): void {
    $u       = user();
    $isSuper = $u['role'] === 'super_admin';

    $suppliers = rows("SELECT s.*, 
                      (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS total_coops,
                      (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops 
                      FROM suppliers s 
                      ORDER BY FIELD(s.status,'pending','rejected','verified'), s.id DESC");

    $coops = rows('SELECT c.*, s.legal_name, r.name AS raw_name 
                   FROM cooperatives c 
                   JOIN suppliers s ON s.id=c.supplier_id 
                   JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                   ORDER BY FIELD(c.status,\'pending\',\'rejected\',\'verified\'), c.id DESC');

    $buyers = rows('SELECT * FROM buyer_profiles ORDER BY FIELD(status,\'pending\',\'rejected\',\'verified\'), id DESC');
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius:16px; background: linear-gradient(135deg, #164B35 0%, #1D5C43 100%); color:white;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-white bg-opacity-20 fs-3 text-white"><i class="bi bi-shield-check"></i></div>
            <div>
                <h5 class="fw-bold mb-1 text-white">Verifikasi Legalitas & Kepatuhan Mitra</h5>
                <p class="mb-0 small text-white-50">
                    <?= $isSuper ? 'Pemeriksaan tingkat akhir Super Admin untuk PT Pemasok & Koperasi Anak.' : 'Pengajuan verifikasi dari Koperasi Induk ke Super Admin Platform.' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center gap-3" style="border-radius:12px; background:#FFFBEB; color:#78350F;">
    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
    <div>
        <strong>Aturan Verifikasi PT Pemasok Utama:</strong> PT Pemasok besar hanya dapat di-ACC oleh <strong>Super Admin</strong> apabila Admin Koperasi Induk telah mendaftarkan <strong>minimal 5 Koperasi Anak valid</strong> dalam jaringannya.
    </div>
</div>

<!-- TABEL VERIFIKASI PT PEMASOK UTAMA -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2" style="color:#164B35"></i>Pemeriksaan PT Pemasok Besar &bull; <?= count($suppliers) ?></h5>
            <span class="badge bg-light text-dark border">Syarat: Min. 5 Koperasi Anak</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama PT / NIB</th>
                        <th>Wilayah Operasional</th>
                        <th>Koperasi Anak Terdaftar</th>
                        <th>Status Verifikasi</th>
                        <th class="text-center">Aksi Keputusan (Super Admin)</th>
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
                            <?php if ($s['total_coops'] >= 5): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-check-circle me-1"></i><?= $s['total_coops'] ?> / 5 Koperasi Anak</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i><?= $s['total_coops'] ?> / 5 (Belum Cukup)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= badge($s['status']) ?></td>
                        <td class="text-center">
                            <?php if ($isSuper): ?>
                                <?php if ($s['total_coops'] >= 5 || $s['status'] === 'verified'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php action_form('verify_supplier', (int)$s['id'], 'ACC Super Admin', 'btn-success btn-sm', ['status' => 'verified']); ?>
                                        <?php action_form('verify_supplier', (int)$s['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border"><i class="bi bi-lock-fill me-1"></i>Butuh 5 Koperasi Anak</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="small text-muted">Menunggu ACC Super Admin</span>
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

<!-- TABEL KOPERASI ANAK -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="p-4 border-bottom">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shop me-2" style="color:#164B35"></i>Koperasi Anak Produsen &bull; <?= count($coops) ?></h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Koperasi Anak</th>
                        <th>PT Induk Pemasok</th>
                        <th>Spesialis Olahan</th>
                        <th>Kota Penampungan</th>
                        <th>Status</th>
                        <th class="text-center">Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($coops as $c): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= e($c['name']) ?></td>
                        <td><?= e($c['legal_name']) ?></td>
                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><?= e($c['raw_name']) ?></span></td>
                        <td><?= e($c['city']) ?></td>
                        <td><?= badge($c['status']) ?></td>
                        <td class="text-center">
                            <?php if ($isSuper): ?>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php action_form('verify_coop', (int)$c['id'], 'ACC Anak', 'btn-success btn-sm', ['status' => 'verified']); ?>
                                    <?php action_form('verify_coop', (int)$c['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                                </div>
                            <?php else: ?>
                                <span class="small text-muted">Diajukan ke Super Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$coops) table_empty('Belum ada Koperasi Anak terdaftar.'); ?>
        </div>
    </div>
</div>
<?php } ?>