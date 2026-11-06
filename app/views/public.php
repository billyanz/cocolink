<?php
declare(strict_types=1);

function render_public(string $page): void {
    $flash = take_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoCoLink &bull; Circular Supply Chain Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body { background: #F6F8F4; font-family: system-ui, -apple-system, sans-serif; color: #2D3748; }
        .auth-card { background: #FFFFFF; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-coco { background: #164B35; color: #FFF; border: none; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 10px; }
        .btn-coco:hover { background: #1D5C43; color: #FFF; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 950px;">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['kind']) ?> alert-dismissible fade show mb-4" style="border-radius:12px;">
            <i class="bi bi-info-circle-fill me-2"></i><?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($page === 'login'): ?>
        <div class="auth-card p-5 mx-auto" style="max-width: 450px;">
            <div class="text-center mb-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; background:#164B35 !important;">
                    <i class="bi bi-recycle fs-3"></i>
                </div>
                <h4 class="fw-bold text-dark">Masuk ke CoCoLink</h4>
                <p class="text-muted small">Platform Manajemen Ekosistem Sirkular Kelapa Jabodetabek</p>
            </div>

            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="login">
                <?php field('Alamat Email', 'email', 'email', '', 'required', 'nama@perusahaan.com'); ?>
                <?php field('Kata Sandi', 'password', 'password', '', 'required', '••••••••'); ?>
                <button class="btn btn-coco w-100 mt-2 mb-3 shadow-sm">Masuk Sistem</button>
            </form>

            <div class="border-top pt-3 text-center small text-muted">
                Belum punya akun mitra PT Pemasok? <br>
                <a href="<?= url('register_supplier') ?>" class="text-success fw-bold text-decoration-none">Daftar PT Pemasok Utama</a>
            </div>
        </div>

    <?php elseif ($page === 'register_supplier'): ?>
        <div class="auth-card p-5">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Pendaftaran PT Pemasok Limbah Utama</h4>
                    <p class="text-muted small mb-0">Ajukan pendaftaran PT Pemasok Regional dan daftarkan minimal 5 Koperasi Anak Sektor Wilayah (Jabodetabek).</p>
                </div>
                <a href="<?= url('login') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali Login</a>
            </div>

            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="register_supplier">

                <!-- 01. AKUN PENANGGUNG JAWAB ADMIN PT -->
                <h6 class="fw-bold text-success mb-3"><i class="bi bi-person-circle me-2"></i>01 &bull; Akun Pengelola / Admin PT Pemasok</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><?php field('Nama Lengkap Manajer', 'name', 'text', '', 'required', 'Rajsfano Priariya'); ?></div>
                    <div class="col-md-4"><?php field('Email Perusahaan', 'email', 'email', '', 'required', 'pt@perusahaan.com'); ?></div>
                    <div class="col-md-4"><?php field('Kata Sandi Login', 'password', 'password', '', 'required', '••••••••'); ?></div>
                </div>

                <!-- 02. LEGALITAS PT PEMASOK UTAMA -->
                <h6 class="fw-bold text-success mb-3"><i class="bi bi-building me-2"></i>02 &bull; Legalitas Perusahaan PT Pemasok Utama</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><?php field('Nama Resmi PT', 'legal_name', 'text', '', 'required', 'PT Kelapa Mandiri Abadi'); ?></div>
                    <div class="col-md-6"><?php field('NIB Perusahaan', 'nib', 'text', '', 'required', '998877665544'); ?></div>
                    <div class="col-md-6"><?php field('Provinsi', 'province', 'text', '', 'required', 'DKI Jakarta'); ?></div>
                    <div class="col-md-6"><?php field('Kota / Wilayah Operasi', 'city', 'text', '', 'required', 'Jakarta'); ?></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark small mb-1">Alamat Kantor / Gudang Utama PT</label>
                        <textarea class="form-control" name="address" rows="2" required placeholder="Jl. Raya Perkebunan Kelapa No. 102" style="border-radius:10px;"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark small mb-1">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Link Google Maps Kantor PT Utama
                        </label>
                        <input type="url" class="form-control" name="gmaps_url" required placeholder="https://maps.app.goo.gl/..." style="border-radius:10px; padding:0.6rem 0.9rem;">
                    </div>
                </div>

                <!-- 03. 5 KOPERASI ANAK SEKTOR WILAYAH -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-success mb-0"><i class="bi bi-shop me-2"></i>03 &bull; Data 5 Koperasi Anak Sektor Wilayah (Jabodetabek)</h6>
                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Wajib 5 Sektor</span>
                </div>

                <?php 
                $sectors = [
                    1 => ['name' => 'Koperasi Sektor Jakarta Timur', 'city' => 'Jakarta Timur', 'type' => 1],
                    2 => ['name' => 'Koperasi Sektor Jakarta Selatan', 'city' => 'Jakarta Selatan', 'type' => 2],
                    3 => ['name' => 'Koperasi Sektor Jakarta Barat', 'city' => 'Jakarta Barat', 'type' => 3],
                    4 => ['name' => 'Koperasi Sektor Jakarta Pusat', 'city' => 'Jakarta Pusat', 'type' => 4],
                    5 => ['name' => 'Koperasi Sektor Jakarta Utara', 'city' => 'Jakarta Utara', 'type' => 5],
                ];
                $types = [
                    1 => 'Daun Kelapa & Lidi',
                    2 => 'Tempurung Kelapa',
                    3 => 'Sabut Kelapa',
                    4 => 'Batang & Kayu Kelapa',
                    5 => 'Air Kelapa & Daging Ampas'
                ];
                for ($i = 1; $i <= 5; $i++): 
                    $sec = $sectors[$i];
                ?>
                    <div class="p-3 border rounded bg-light mb-3" style="border-radius:12px;">
                        <div class="fw-bold text-dark small mb-2"><i class="bi bi-pin-map-fill me-1 text-success"></i>Koperasi Sektor Wilayah #<?= $i ?></div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm" name="coop_name[]" value="<?= e($sec['name']) ?>" required placeholder="Nama Koperasi Sektor">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" name="coop_city[]" value="<?= e($sec['city']) ?>" required placeholder="Wilayah Sektor">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" name="coop_raw_type_id[]" required>
                                    <?php foreach ($types as $tid => $tn): ?>
                                        <option value="<?= $tid ?>" <?= $tid === $sec['type'] ? 'selected' : '' ?>><?= e($tn) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control form-control-sm" name="coop_address[]" required placeholder="Alamat Gudang Sektor">
                            </div>
                            <div class="col-12 mt-2">
                                <input type="url" class="form-control form-control-sm" name="coop_gmaps_url[]" required placeholder="Link Google Maps Gudang Sektor #<?= $i ?> (https://maps.app.goo.gl/...)">
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>

                <div class="text-end mt-4">
                    <button class="btn btn-coco fw-bold px-5 py-2 shadow-sm" style="border-radius:10px;">
                        <i class="bi bi-send-check-fill me-1"></i> Ajukan Pendaftaran PT Pemasok
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php
}