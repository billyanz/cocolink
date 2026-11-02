<?php
function render_layout(string $title, string $page, array $u, callable $content): void {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> &bull; CoCoLink Circular Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --coco-primary: #164B35;
            --coco-primary-dark: #0F3525;
            --coco-accent: #C7ED8D;
            --coco-bg: #F6F8F4;
            --coco-border: #DCE6D9;
            --coco-text: #24362E;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--coco-bg);
            color: var(--coco-text);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100vw;
        }

        .app-sidebar {
            width: var(--sidebar-width);
            background-color: var(--coco-primary-dark);
            color: #ffffff;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .app-main {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .app-header {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid var(--coco-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .app-content {
            padding: 30px;
            flex-grow: 1;
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand .logo-icon {
            width: 38px;
            height: 38px;
            background: var(--coco-accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--coco-primary-dark);
            font-size: 20px;
        }

        .sidebar-menu {
            padding: 20px 12px;
            list-style: none;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-menu .menu-header {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.4);
            padding: 12px 12px 6px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 3px;
        }

        .sidebar-menu a:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.06);
        }

        .sidebar-menu a.active {
            color: #ffffff;
            background: var(--coco-primary);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .sidebar-menu a i {
            font-size: 17px;
            width: 20px;
            text-align: center;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--coco-border);
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 4px 15px rgba(22, 75, 53, 0.03);
            height: 100%;
            min-height: 100px;
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: var(--coco-bg);
            border: 1px solid var(--coco-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--coco-primary);
            flex-shrink: 0;
        }

        .kpi-content {
            flex-grow: 1;
            min-width: 0;
        }

        .kpi-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748B;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--coco-text);
            line-height: 1.1;
        }

        .panel {
            background: #ffffff;
            border: 1px solid var(--coco-border);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(22, 75, 53, 0.03);
        }

        .panel-pad {
            padding: 24px;
        }

        .btn-coco {
            background-color: var(--coco-primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-coco:hover {
            background-color: var(--coco-primary-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-lime {
            background-color: var(--coco-accent);
            color: var(--coco-primary-dark);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-lime:hover {
            background-color: #b3e370;
            color: var(--coco-primary-dark);
        }

        .chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: var(--coco-bg);
            border: 1px solid var(--coco-border);
            font-size: 11px;
            font-weight: 600;
            color: var(--coco-primary);
        }

        .notice {
            background: #F0F7EE;
            border: 1px solid #C4E3BC;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13.5px;
            color: #2D5A27;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <aside class="app-sidebar">
            <div class="sidebar-brand">
                <div class="logo-icon"><i class="bi bi-circle-styled">🥥</i></div>
                <div>
                    <div class="fw-bold" style="font-size: 16px; letter-spacing: -0.3px;">CocoLink</div>
                    <div style="font-size: 9px; color: var(--coco-accent); letter-spacing: 1px; font-weight: 700;">CIRCULAR NETWORK</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-header">Menu Utama</li>
                <li><a href="<?= e(url('dashboard')) ?>" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
                <li><a href="<?= e(url('maps')) ?>" class="<?= $page === 'maps' ? 'active' : '' ?>"><i class="bi bi-map-fill"></i> Peta Jaringan</a></li>
                
                <?php if (is_manager($u)): ?>
                    <li><a href="<?= e(url('verification')) ?>" class="<?= $page === 'verification' ? 'active' : '' ?>"><i class="bi bi-patch-check-fill"></i> Verifikasi Mitra</a></li>
                <?php endif; ?>

                <?php if (is_manager($u) || $u['role'] === 'supplier'): ?>
                    <li><a href="<?= e(url('batches')) ?>" class="<?= $page === 'batches' ? 'active' : '' ?>"><i class="bi bi-box-seam-fill"></i> Pasokan Limbah</a></li>
                <?php endif; ?>

                <?php if (is_manager($u) || $u['role'] === 'supplier' || $u['role'] === 'coop'): ?>
                    <li><a href="<?= e(url('allocations')) ?>" class="<?= $page === 'allocations' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i> Alokasi Bahan</a></li>
                <?php endif; ?>

                <?php if (is_manager($u) || $u['role'] === 'coop'): ?>
                    <li><a href="<?= e(url('products')) ?>" class="<?= $page === 'products' ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Produk Jadi</a></li>
                <?php endif; ?>

                <?php if ($u['role'] === 'buyer'): ?>
                    <li><a href="<?= e(url('catalog')) ?>" class="<?= $page === 'catalog' ? 'active' : '' ?>"><i class="bi bi-shop"></i> Katalog Produk</a></li>
                <?php endif; ?>

                <?php if (is_manager($u) || $u['role'] === 'buyer'): ?>
                    <li><a href="<?= e(url('orders')) ?>" class="<?= $page === 'orders' ? 'active' : '' ?>"><i class="bi bi-cart-check-fill"></i> Pesanan Industri</a></li>
                <?php endif; ?>

                <li><a href="<?= e(url('shipments')) ?>" class="<?= $page === 'shipments' ? 'active' : '' ?>"><i class="bi bi-truck"></i> Logistik & Driver</a></li>

                <?php if (is_manager($u)): ?>
                    <li class="menu-header">Platform Admin</li>
                    <li><a href="<?= e(url('reports')) ?>" class="<?= $page === 'reports' ? 'active' : '' ?>"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan Lanjutan</a></li>
                    <?php if ($u['role'] === 'super_admin'): ?>
                        <li><a href="<?= e(url('accounts')) ?>" class="<?= $page === 'accounts' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Manajemen Akun</a></li>
                        <li><a href="<?= e(url('audit')) ?>" class="<?= $page === 'audit' ? 'active' : '' ?>"><i class="bi bi-shield-check"></i> Audit Aktivitas</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </aside>

        <div class="app-main">
            <header class="app-header">
                <div>
                    <span class="text-muted small">Operasional / </span>
                    <span class="fw-bold text-capitalize"><?= str_replace('_', ' ', $page) ?></span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge px-3 py-2" style="background:#F6F8F4; color:#164B35; border:1px solid #DCE6D9; font-weight:700;">
                        <i class="bi bi-person-badge me-1"></i> <?= e(str_replace('_', ' ', strtoupper($u['role']))) ?>
                    </span>
                    <div class="fw-bold" style="font-size:14px;"><?= e($u['name']) ?></div>
                    <form method="post" action="<?= e(url('logout')) ?>" style="margin:0;">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="logout">
                        <button class="btn btn-sm btn-outline-danger px-3" style="border-radius:8px;"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                    </form>
                </div>
            </header>

            <main class="app-content">
                <?php $content(); ?>
            </main>
        </div>
    </div>
</body>
</html>
<?php
}

function kpi(string $label, $val, string $icon = 'box'): void {
    $iconMap = [
        'map-pin' => 'bi-geo-alt-fill',
        'network' => 'bi-diagram-3-fill',
        'building' => 'bi-building-fill',
        'building-2' => 'bi-buildings-fill',
        'shopping-bag' => 'bi-bag-check-fill',
        'truck' => 'bi-truck',
        'package' => 'bi-box-seam-fill',
        'arrow-right-left' => 'bi-arrow-left-right',
        'leaf' => 'bi-tree-fill',
        'clock' => 'bi-clock-history',
        'box' => 'bi-box-fill',
        'boxes' => 'bi-boxes',
        'factory' => 'bi-building-gear',
        'shopping-cart' => 'bi-cart-fill',
        'check-circle' => 'bi-check-circle-fill',
        'wallet' => 'bi-wallet2',
        'bell' => 'bi-bell-fill',
        'route' => 'bi-signpost-split-fill'
    ];
    $biClass = $iconMap[$icon] ?? 'bi-app-indicator';
?>
    <div class="kpi-card">
        <div class="kpi-icon">
            <i class="bi <?= $biClass ?>"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-title"><?= e($label) ?></div>
            <div class="kpi-value"><?= e((string)$val) ?></div>
        </div>
    </div>
<?php } ?>