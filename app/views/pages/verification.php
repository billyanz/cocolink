<?php
function page_verification(): void {
    $suppliers = rows("SELECT s.*, 
                      (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id AND c.status='verified') AS verified_coops, 
                      (SELECT COUNT(*) FROM cooperatives c WHERE c.supplier_id=s.id) AS all_coops 
                      FROM suppliers s 
                      ORDER BY FIELD(s.status,'pending','rejected','verified'), s.id DESC");
                      
    $coops = rows('SELECT c.*, s.legal_name, r.name AS raw_name 
                   FROM cooperatives c 
                   JOIN suppliers s ON s.id=c.supplier_id 
                   JOIN raw_types r ON r.id=c.specialty_raw_type_id 
                   ORDER BY FIELD(c.status,\'pending\',\'rejected\',\'verified\'), c.id DESC');
                   
    $buyers = rows('SELECT * FROM buyer_profiles ORDER BY FIELD(status,\'pending\',\'rejected\',\'verified\'), id DESC');
?>

<div class="notice mb-4">
    <i class="bi bi-shield-lock-fill me-2"></i>
    <strong>Aturan Syarat Pendaftaran PT Inti:</strong> PT Pemasok hanya dapat diverifikasi jika telah memiliki <strong>minimal 5 Koperasi Anak</strong> yang terverifikasi dalam jaringannya.
</div>

<!-- PT SUPPLIERS TABLE -->
<div class="panel mb-4">
    <div class="panel-pad section-title mb-0 d-flex justify-content-between align-items-center border-bottom pb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-building-check me-2" style="color:var(--coco-primary)"></i>PT Pemasok Utama &bull; <?= count($suppliers) ?></h5>
        <span class="chip">Syarat: Minimal 5 Koperasi Valid</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>PT / NIB</th>
                    <th>Wilayah Operasional</th>
                    <th>Jaringan Koperasi Valid</th>
                    <th>Status System</th>
                    <th>Keputusan Verifikasi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td>
                        <strong><?= e($s['legal_name']) ?></strong>
                        <div class="helper small text-muted"><i class="bi bi-card-text me-1"></i>NIB: <?= e($s['nib']) ?></div>
                    </td>
                    <td><i class="bi bi-geo-alt me-1 text-muted"></i><?= e($s['city'] . ', ' . $s['province']) ?></td>
                    <td>
                        <span class="badge <?= $s['verified_coops'] >= 5 ? 'bg-success' : 'bg-warning text-dark' ?> px-2 py-1">
                            <?= e($s['verified_coops']) ?> / 5 Min Valid
                        </span>
                        <div class="helper small text-muted mt-1">Total diajukan: <?= e($s['all_coops']) ?></div>
                    </td>
                    <td><?= badge($s['status']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($s['verified_coops'] >= 5 || $s['status'] === 'verified'): ?>
                                <?php action_form('verify_supplier', (int)$s['id'], 'Setujui', 'btn-outline-success btn-sm', ['status' => 'verified']); ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled title="Butuh min 5 Koperasi Valid"><i class="bi bi-lock-fill me-1"></i>Koperasi < 5</button>
                            <?php endif; ?>
                            <?php action_form('verify_supplier', (int)$s['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$suppliers) table_empty('Belum ada PT Pemasok terdaftar.'); ?>
    </div>
</div>

<!-- KOPERASI ANAK TABLE -->
<div class="panel mb-4">
    <div class="panel-pad section-title mb-0 border-bottom pb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2" style="color:var(--coco-primary)"></i>Koperasi Anak Per Wilayah &bull; <?= count($coops) ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama Koperasi Anak</th>
                    <th>Induk PT Pemasok</th>
                    <th>Fokus Pengolahan</th>
                    <th>Kota / Wilayah</th>
                    <th>Status</th>
                    <th>Tindakan Verifikasi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($coops as $c): ?>
                <tr>
                    <td class="fw-bold"><?= e($c['name']) ?></td>
                    <td><?= e($c['legal_name']) ?></td>
                    <td><span class="chip"><?= e($c['raw_name']) ?></span></td>
                    <td><?= e($c['city']) ?></td>
                    <td><?= badge($c['status']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php action_form('verify_coop', (int)$c['id'], 'Setujui', 'btn-outline-success btn-sm', ['status' => 'verified']); ?>
                            <?php action_form('verify_coop', (int)$c['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$coops) table_empty('Belum ada Koperasi Anak terdaftar.'); ?>
    </div>
</div>

<!-- PEMBELI INDUSTRI TABLE -->
<div class="panel">
    <div class="panel-pad section-title mb-0 border-bottom pb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-buildings me-2" style="color:var(--coco-primary)"></i>Pembeli Industri / Pengepul &bull; <?= count($buyers) ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama Perusahaan Pembeli</th>
                    <th>Tipe / Kategori</th>
                    <th>Kota</th>
                    <th>Status</th>
                    <th>Tindakan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($buyers as $b): ?>
                <tr>
                    <td class="fw-bold"><?= e($b['company_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e(strtoupper($b['buyer_type'])) ?></span></td>
                    <td><?= e($b['city']) ?></td>
                    <td><?= badge($b['status']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php action_form('verify_buyer', (int)$b['id'], 'Setujui', 'btn-outline-success btn-sm', ['status' => 'verified']); ?>
                            <?php action_form('verify_buyer', (int)$b['id'], 'Tolak', 'btn-outline-danger btn-sm', ['status' => 'rejected']); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$buyers) table_empty('Belum ada calon pembeli.'); ?>
    </div>
</div>
<?php } ?>