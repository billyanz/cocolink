<?php
require_once __DIR__ . '/../bootstrap.php';

function public_header(string $title): void {
    $f = take_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> &bull; CocoLink Circular Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --g: #164B35;
            --lime: #C7ED8D;
            --cream: #F6F8F4;
            --border: #DCE6D9;
        }

        body {
            background: var(--cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #24362E;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
            color: var(--g);
            text-decoration: none;
        }

        .btn-darkgreen {
            background: var(--g);
            border: 1px solid var(--g);
            color: #fff;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-darkgreen:hover {
            background: #0F3525;
            color: white;
        }

        .btn-lime {
            background: var(--lime);
            border: 0;
            color: #164B35;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
        }

        .hero {
            background: linear-gradient(135deg, #164B35 0%, #0F3525 100%);
            color: #fff;
            border-radius: 20px;
            padding: 60px;
        }

        .login-card {
            max-width: 480px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 34px;
            margin: 40px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .register-card {
            max-width: 900px;
        }

        .form-control, .form-select {
            padding: 11px;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .pill {
            background: #EAF3E7;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 11px;
            color: var(--g);
            font-weight: 700;
        }

        .coop-card {
            background: #F8FAF7;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg border-bottom py-3">
        <div class="container">
            <a class="navbar-brand brand d-flex align-items-center gap-2" href="<?= e(url('home')) ?>">
                <span class="p-1 px-2 rounded" style="background:var(--lime); color:var(--g)">🥥</span> CocoLink
            </a>
            <div class="ms-auto d-flex gap-2">
                <a href="<?= e(url('home')) ?>" class="btn btn-link text-dark text-decoration-none fw-semibold">Beranda</a>
                <a href="<?= e(url('login')) ?>" class="btn btn-darkgreen">Masuk Dashboard</a>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        <?php if ($f): ?>
            <div class="alert alert-<?= e($f['kind']) ?> mt-3" style="border-radius:12px;"><?= e($f['message']) ?></div>
        <?php endif; ?>
<?php
}

function public_footer(): void {
?>
    </main>
    <footer class="container py-4 border-top d-flex justify-content-between text-muted small mt-5">
        <span>© <?= date('Y') ?> CocoLink Circular Network</span>
        <span>Transformasi Limbah Kelapa Menjadi Nilai Ekonomi</span>
    </footer>
</body>
</html>
<?php
}

function render_public(string $page): void {
    public_header(match($page) {
        'login'             => 'Masuk Dashboard',
        'register_supplier' => 'Daftar PT Pemasok',
        'register_buyer'    => 'Daftar Pembeli Industri',
        'home'              => 'Ekosistem Limbah Kelapa',
        default             => 'CocoLink'
    });

    if ($page === 'home'):
?>
<div class="hero mt-3">
    <span class="pill" style="background:rgba(255,255,255,0.15); color:#C7ED8D">CIRCULAR ECONOMY &bull; SUPPLY CHAIN</span>
    <h1 class="display-5 fw-bold mt-3">Dari Limbah Menjadi <span style="color:#C7ED8D">Nilai Ekonomi.</span></h1>
    <p class="mt-3 mb-4 text-white-50 fs-5" style="max-width:700px;">
        Platform terintegrasi untuk PT Pemasok Limbah, Koperasi Anak Pengolah Per Wilayah, Pembeli Industri B2B, dan Driver Logistik.
    </p>
    <div class="d-flex flex-wrap gap-3">
        <a href="<?= e(url('register_supplier')) ?>" class="btn btn-lime">Daftarkan PT Pemasok ↗</a>
        <a href="<?= e(url('register_buyer')) ?>" class="btn btn-outline-light px-4" style="border-radius:10px;">Daftar Pembeli Industri</a>
    </div>
</div>

<div class="row mt-4 g-3">
    <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 h-100">
            <div class="fs-1 text-success mb-2"><i class="bi bi-shield-check"></i></div>
            <h5 class="fw-bold">01 &bull; Pasokan Terverifikasi</h5>
            <p class="text-muted small mt-2 mb-0">Setiap PT wajib memiliki minimal 5 Koperasi Anak terverifikasi sebelum dapat memasok bahan mentah.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 h-100">
            <div class="fs-1 text-success mb-2"><i class="bi bi-diagram-3"></i></div>
            <h5 class="fw-bold">02 &bull; Produksi Per Wilayah</h5>
            <p class="text-muted small mt-2 mb-0">Alokasi limbah daun, tempurung, sabut, dan batang dikelola secara spesifik oleh Koperasi Anak.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 h-100">
            <div class="fs-1 text-success mb-2"><i class="bi bi-truck"></i></div>
            <h5 class="fw-bold">03 &bull; Logistik & Tracking</h5>
            <p class="text-muted small mt-2 mb-0">Driver mitra memperbarui status pickup, mengunggah bukti kirim, dan membagikan koordinat lokasi.</p>
        </div>
    </div>
</div>

<?php elseif ($page === 'login'): ?>
<div class="login-card">
    <span class="pill">AKSES SISTEM</span>
    <h3 class="fw-bold mt-2">Masuk Akun</h3>
    <p class="text-muted small mb-4">Akses dashboard sesuai hak akses dan peran Anda.</p>
    
    <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="login">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input class="form-control" type="email" name="email" placeholder="nama@perusahaan.com" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-darkgreen w-100 fw-bold py-2 mt-2">Masuk Dashboard</button>
    </form>
    
    <p class="text-muted small mt-4 mb-0 text-center">
        Belum terdaftar? <a href="<?= e(url('register_supplier')) ?>">Daftar PT Pemasok</a> &bull; <a href="<?= e(url('register_buyer')) ?>">Daftar Pembeli</a>
    </p>
</div>

<?php elseif ($page === 'register_supplier'): $types = rows('SELECT id, name FROM raw_types ORDER BY id'); ?>
<div class="login-card register-card">
    <span class="pill">REGISTRASI PT PEMASOK</span>
    <h3 class="fw-bold mt-2">Daftarkan PT & 5 Koperasi Anak</h3>
    <p class="text-muted small">PT Pemasok wajib mengajukan sekurangnya 5 calon Koperasi Anak yang akan menampung limbah kelapa spesifik per wilayah.</p>
    
    <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="register_supplier">
        
        <h6 class="fw-bold text-success border-bottom pb-2 mt-4"><i class="bi bi-person-circle me-1"></i>01 &bull; Informasi Akun Pengelola PT</h6>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Nama Penanggung Jawab</label><input class="form-control" name="name" required></div>
            <div class="col-md-4"><label class="form-label">Email Operasional</label><input type="email" class="form-control" name="email" required></div>
            <div class="col-md-4"><label class="form-label">Password (min. 8 karakter)</label><input type="password" minlength="8" class="form-control" name="password" required></div>
        </div>

        <h6 class="fw-bold text-success border-bottom pb-2 mt-4"><i class="bi bi-building me-1"></i>02 &bull; Legalitas Perusahaan (PT)</h6>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama Resmi PT</label><input class="form-control" name="legal_name" required></div>
            <div class="col-md-6"><label class="form-label">NIB Perusahaan</label><input class="form-control" name="nib" required></div>
            <div class="col-md-6"><label class="form-label">Provinsi</label><input class="form-control" name="province" required></div>
            <div class="col-md-6"><label class="form-label">Kota / Kabupaten</label><input class="form-control" name="city" required></div>
            <div class="col-12"><label class="form-label">Alamat Kantor / Gudang Utama</label><textarea class="form-control" name="address" rows="2" required></textarea></div>
            <div class="col-md-6"><label class="form-label">Latitude GPS</label><input class="form-control" type="number" step="any" name="latitude" placeholder="-6.200000" required></div>
            <div class="col-md-6"><label class="form-label">Longitude GPS</label><input class="form-control" type="number" step="any" name="longitude" placeholder="106.816666" required></div>
        </div>

        <h6 class="fw-bold text-success border-bottom pb-2 mt-4"><i class="bi bi-diagram-3 me-1"></i>03 &bull; Minimal 5 Calon Koperasi Anak</h6>
        <div id="coops" class="d-grid gap-3">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="coop-card">
                    <div class="fw-bold mb-2 text-dark">Koperasi Anak #<?= $i ?></div>
                    <div class="row g-2">
                        <div class="col-md-5"><input class="form-control form-control-sm" name="coop_name[]" placeholder="Nama Koperasi Anak" required></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" name="coop_city[]" placeholder="Kota / Wilayah" required></div>
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" name="coop_type[]" required>
                                <option value="">Spesialisasi Limbah...</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12"><input class="form-control form-control-sm" name="coop_address[]" placeholder="Alamat lengkap titik pickup..." required></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm mt-3" onclick="addCoop()">+ Tambah Koperasi (Maks. 20)</button>
        <button class="btn btn-darkgreen w-100 fw-bold py-2 mt-4">Kirim Permohonan Pendaftaran</button>
    </form>
</div>
<script>
function addCoop() {
    let c = document.querySelectorAll('#coops .coop-card');
    if (c.length >= 20) return alert('Maksimal 20 Koperasi Anak.');
    let d = c[0].cloneNode(true);
    d.querySelector('.fw-bold').textContent = 'Koperasi Anak #' + (c.length + 1);
    d.querySelectorAll('input,select').forEach(x => x.value = '');
    document.getElementById('coops').appendChild(d);
}
</script>

<?php elseif ($page === 'register_buyer'): ?>
<div class="login-card register-card" style="max-width:650px">
    <span class="pill">PENDAFTARAN PEMBELI INDUSTRI</span>
    <h3 class="fw-bold mt-2">Akses Stok Hasil Olahan Limbah</h3>
    <p class="text-muted small mb-4">Akun Pembeli Industri diverifikasi oleh Koperasi Induk sebelum dapat mengajukan pesanan B2B.</p>
    
    <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="register_buyer">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama Pengguna / Purchasing</label><input class="form-control" name="name" required></div>
            <div class="col-md-6"><label class="form-label">Email Perusahaan</label><input type="email" class="form-control" name="email" required></div>
            <div class="col-md-6"><label class="form-label">Password (min. 8)</label><input type="password" class="form-control" name="password" minlength="8" required></div>
            <div class="col-md-6"><label class="form-label">Nama Perusahaan Pembeli</label><input class="form-control" name="company_name" required></div>
            <div class="col-md-6">
                <label class="form-label">Kategori Pembeli</label>
                <select class="form-select" name="buyer_type" required>
                    <option value="industry">PT / Manufaktur Industri</option>
                    <option value="collector">Pengepul / Distributor Besar</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">NIB (Opsional)</label><input class="form-control" name="nib"></div>
            <div class="col-md-6"><label class="form-label">Kota</label><input class="form-control" name="city" required></div>
            <div class="col-12"><label class="form-label">Alamat Pabrik / Gudang</label><textarea class="form-control" name="address" required></textarea></div>
        </div>
        <button class="btn btn-darkgreen w-100 fw-bold py-2 mt-4">Daftar Sebagai Pembeli Industri</button>
    </form>
</div>
<?php 
    endif;
    public_footer();
}