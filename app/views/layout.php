<?php
declare(strict_types=1);

function render_layout(string $page, array $u, callable $contentRenderer): void {
    // PEMISAHAN MENU SIDEBAR PRESISI SESUAI PERAN AKUN
    $menuItems = [
        'dashboard'    => ['label' => 'Dashboard Utama', 'icon' => 'bi-speedometer2', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver']],
        'maps'         => ['label' => 'Peta Jaringan Mitra', 'icon' => 'bi-map', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver']],
        'verification' => ['label' => 'Verifikasi Koperasi Binaan', 'icon' => 'bi-shield-check', 'roles' => ['super_admin', 'induk', 'supplier']],
        'accounts'     => ['label' => 'Manajemen Akun Koperasi', 'icon' => 'bi-people', 'roles' => ['super_admin', 'induk', 'supplier']],
        'batches'      => ['label' => 'Pasokan Limbah PT', 'icon' => 'bi-box-seam', 'roles' => ['super_admin', 'induk', 'supplier']],
        'allocations'  => ['label' => 'Alokasi Bahan Mentah', 'icon' => 'bi-arrow-left-right', 'roles' => ['super_admin', 'induk', 'supplier', 'coop']],
        'products'     => ['label' => 'Produk Olahan Koperasi', 'icon' => 'bi-shop', 'roles' => ['super_admin', 'induk', 'supplier', 'coop']],
        'catalog'      => ['label' => 'Katalog Industri B2B', 'icon' => 'bi-cart-check', 'roles' => ['buyer']],
        'orders'       => ['label' => 'Pesanan Industri', 'icon' => 'bi-receipt', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer']],
        'shipments'    => ['label' => 'Logistik & Driver Armada', 'icon' => 'bi-truck', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'driver', 'buyer']],
        'reports'      => ['label' => 'Laporan Lanjutan', 'icon' => 'bi-file-earmark-bar-graph', 'roles' => ['super_admin']],
        'audit'        => ['label' => 'Audit Log Aktivitas', 'icon' => 'bi-journal-text', 'roles' => ['super_admin']],
    ];

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
        :root {
            --coco-primary: #164B35;
            --coco-primary-hover: #1D5C43;
            --coco-bg: #F6F8F4;
            --coco-sidebar-bg: #113828;
        }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: var(--coco-bg);
            color: #2D3748;
        }
        .sidebar {
            width: 260px;
            background-color: var(--coco-sidebar-bg);
            min-height: 100vh;
            color: #E2E8F0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        .nav-link-coco {
            color: #A0AEC0;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        .nav-link-coco:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.08);
        }
        .nav-link-coco.active {
            color: #FFFFFF;
            background-color: var(--coco-primary);
            box-shadow: 0 4px 12px rgba(22, 75, 53, 0.3);
        }
        .btn-coco {
            background-color: var(--coco-primary);
            color: #FFFFFF;
            border: none;
        }
        .btn-coco:hover {
            background-color: var(--coco-primary-hover);
            color: #FFFFFF;
        }
        .top-navbar {
            background: #FFFFFF;
            border-radius: 14px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <aside class="sidebar p-3 d-flex flex-column">
        <div class="d-flex align-items-center gap-2 px-2 py-3 mb-3 border-bottom border-white border-opacity-10">
            <div class="bg-success bg-gradient text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-recycle fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-white mb-0" style="letter-spacing: 0.5px;">CoCoLink</h6>
                <small class="text-white-50" style="font-size: 0.72rem;">
                    <?= $u['role'] === 'super_admin' ? 'MASTER PLATFORM' : 'REGIONAL PT INDUK' ?>
                </small>
            </div>
        </div>

        <nav class="flex-grow-1">
            <div class="text-white-50 small fw-bold px-2 mb-2 text-uppercase" style="font-size:0.7rem; letter-spacing:1px;">
                <?= $u['role'] === 'super_admin' ? 'Menu Master System' : 'Menu Operasional PT' ?>
            </div>
            <?php foreach ($menuItems as $key => $item): ?>
                <?php if (in_array($u['role'], $item['roles'], true)): ?>
                    <a href="<?= url($key) ?>" class="nav-link-coco <?= $page === $key ? 'active' : '' ?>">
                        <i class="bi <?= $item['icon'] ?> fs-5"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="border-top border-white border-opacity-10 pt-3 mt-auto">
            <form method="post" action="<?= url('login') ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-outline-light btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-2" style="border-radius:10px;">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar Sistem</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main-content flex-grow-1">
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Operasional /</span>
                <strong class="text-dark capitalize"><?= e(ucwords(str_replace('_', ' ', $page))) ?></strong>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fw-semibold">
                    <i class="bi bi-person-badge me-1"></i><?= e(strtoupper($u['role'])) ?>
                </span>
                <div class="fw-bold text-dark small"><?= e($u['name']) ?></div>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['kind']) ?> alert-dismissible fade show shadow-sm border-0 mb-4" style="border-radius:12px;">
                <i class="bi bi-info-circle-fill me-2"></i><?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php $contentRenderer(); ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}