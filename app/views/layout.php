<?php
declare(strict_types=1);

function render_layout(string $page, array $u, callable $contentRenderer): void {
    // PEMISAHAN MENU SIDEBAR BERDASARKAN KATEGORI ALUR BISNIS
    $menuGroups = [
        'Ringkasan & Peta' => [
            'dashboard'    => ['label' => 'Dashboard Utama', 'icon' => 'bi-speedometer2', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver']],
            'maps'         => ['label' => 'Peta Jaringan Mitra', 'icon' => 'bi-map', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver']],
        ],
        'Master Kemitraan' => [
            'verification' => ['label' => 'Verifikasi Koperasi', 'icon' => 'bi-shield-check', 'roles' => ['super_admin', 'induk', 'supplier']],
            'accounts'     => ['label' => 'Manajemen Akun', 'icon' => 'bi-people', 'roles' => ['super_admin', 'induk', 'supplier']],
        ],
        'Hulu (Bahan Mentah)' => [
            'batches'      => ['label' => 'Pasokan Limbah PT', 'icon' => 'bi-box-seam', 'roles' => ['super_admin', 'induk', 'supplier']],
            'allocations'  => ['label' => 'Alokasi Distribusi', 'icon' => 'bi-arrow-left-right', 'roles' => ['super_admin', 'induk', 'supplier', 'coop']],
        ],
        'Hilir (Olahan B2B)' => [
            'products'     => ['label' => 'Produk Sektor', 'icon' => 'bi-shop', 'roles' => ['super_admin', 'induk', 'supplier', 'coop']],
            'catalog'      => ['label' => 'Katalog Industri', 'icon' => 'bi-cart-check', 'roles' => ['buyer']],
            'orders'       => ['label' => 'Pesanan Pembeli', 'icon' => 'bi-receipt', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'buyer']],
        ],
        'Logistik' => [
            'shipments'    => ['label' => 'Tracking Armada', 'icon' => 'bi-truck', 'roles' => ['super_admin', 'induk', 'supplier', 'coop', 'driver', 'buyer']],
        ],
        'Sistem Utama' => [
            'reports'      => ['label' => 'Laporan Lanjutan', 'icon' => 'bi-file-earmark-bar-graph', 'roles' => ['super_admin']],
            'audit'        => ['label' => 'Audit Log Aktivitas', 'icon' => 'bi-journal-text', 'roles' => ['super_admin']],
        ]
    ];

    $flash = take_flash();

    // AMBIL NOTIFIKASI REAL-TIME DENGAN PENANGANAN AMAN
    $unreadCount = 0;
    $recentNotifs = [];
    
    try {
        if (($u['role'] ?? '') === 'coop' && !empty($u['coop_id'])) {
            $unreadCount = (int)val("SELECT COUNT(*) FROM allocations WHERE coop_id=? AND status='proposed'", [(int)$u['coop_id']]);
            if ($unreadCount > 0) {
                $recentNotifs = rows("SELECT a.id, b.batch_code, a.quantity, a.created_at FROM allocations a JOIN raw_batches b ON b.id=a.batch_id WHERE a.coop_id=? AND a.status='proposed' ORDER BY a.id DESC LIMIT 5", [(int)$u['coop_id']]);
            }
        } elseif (in_array(($u['role'] ?? ''), ['induk', 'supplier'], true) && !empty($u['supplier_id'])) {
            $unreadCount = (int)val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='pending'", [(int)$u['supplier_id']]);
            if ($unreadCount > 0) {
                $recentNotifs = rows("SELECT id, name AS batch_code, 0 AS quantity, created_at FROM cooperatives WHERE supplier_id=? AND status='pending' ORDER BY id DESC LIMIT 5", [(int)$u['supplier_id']]);
            }
        }
    } catch (Throwable $e) {
        $unreadCount = 0;
        $recentNotifs = [];
    }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>CoCoLink &bull; Circular Supply Chain Network</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            /* OCEAN BLUE CORPORATE PALETTE */
            --ocean-dark: #0A192F;       /* Deep Ocean Navy */
            --ocean-surface: #112240;    /* Dark Card Accent */
            --ocean-primary: #0077B6;    /* Corporate Blue */
            --ocean-accent: #00B4D8;     /* Cyan Waves */
            --ocean-light: #E0F2FE;      /* Soft Light Blue */
            --ocean-bg: #F8FAFC;         /* Light Canvas */
            --ocean-text-soft: #94A3B8;
            --sidebar-width: 275px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--ocean-bg);
            color: #0F172A;
            overflow-x: hidden;
        }

        /* LAYOUT MAIN WRAPPER */
        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* -------------------------------------------
           SIDEBAR WITH MODERN ANIMATIONS & SCROLL
        ---------------------------------------------*/
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--ocean-dark);
            color: var(--ocean-text-soft);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            box-shadow: 6px 0 24px rgba(0, 0, 0, 0.12);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1040;
            position: relative;
        }

        /* HEADER BRANDING WITH LOGO CONTAINER */
        .sidebar-brand {
            position: relative;
            background: linear-gradient(135deg, #0A2540 0%, #005691 100%);
            padding: 1.35rem 1.25rem;
            flex-shrink: 0;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-brand::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 24px;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 1440 320' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='%230A192F' fill-opacity='1' d='M0,160L60,176C120,192,240,224,360,213.3C480,203,600,149,720,144C840,139,960,181,1080,186.7C1200,192,1320,160,1380,144L1440,128L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-position: bottom;
            pointer-events: none;
        }

        /* LOGO BOX MODULAR (GANTI GAMBAR LOGO DI SINI ATAU PAKAI FALLBACK) */
        .logo-box {
            width: 44px;
            height: 44px;
            background: #FFFFFF;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .logo-box:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .logo-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .logo-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ocean-primary);
            font-size: 1.35rem;
        }

        /* SCROLLBAR SIDEBAR AREA (INDEPENDENT SMOOTH SCROLL) */
        .sidebar-scroll {
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px 0 20px;
            scroll-behavior: smooth;
        }

        /* CUSTOM SLIM SCROLLBAR */
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 180, 216, 0.4);
        }

        .sidebar-heading {
            font-size: 0.68rem;
            letter-spacing: 1.2px;
            color: #64748B;
            margin-top: 1.2rem;
            margin-bottom: 0.4rem;
            padding: 0 1.5rem;
            font-weight: 800;
        }

        /* NAVIGATION LINK ANIMATIONS */
        .nav-link-coco {
            color: #94A3B8;
            padding: 0.72rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
            position: relative;
        }

        .nav-link-coco .nav-icon {
            transition: transform 0.25s ease, color 0.25s ease;
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
        }

        .nav-link-coco:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.05);
            padding-left: 1.75rem;
        }

        .nav-link-coco:hover .nav-icon {
            transform: scale(1.18);
            color: var(--ocean-accent);
        }

        /* ACTIVE MENU ITEM WITH GLOW EFFECT */
        .nav-link-coco.active {
            color: #FFFFFF;
            background: linear-gradient(90deg, rgba(0, 119, 182, 0.22) 0%, rgba(0, 180, 216, 0.04) 100%);
            border-left: 4px solid var(--ocean-accent);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .nav-link-coco.active .nav-icon {
            color: var(--ocean-accent);
        }

        /* LOGOUT FOOTER BUTTON */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 1rem 1.25rem;
            background-color: rgba(0, 0, 0, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .btn-logout-coco {
            background: rgba(255, 255, 255, 0.06);
            color: #E2E8F0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-weight: 700;
            font-size: 0.85rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s ease;
        }

        .btn-logout-coco:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #F87171;
            border-color: rgba(239, 68, 68, 0.3);
            transform: translateY(-1px);
        }

        /* MAIN CONTENT & TOP NAVBAR */
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.75rem 2rem;
            background-color: var(--ocean-bg);
            width: 100%;
        }

        .top-navbar {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 0.85rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        /* OVERLAY MOBILE */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(10, 25, 47, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1030;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                height: 100%;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
            .main-content {
                padding: 1.25rem 1rem;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- MOBILE OVERLAY BACKDROP -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR UTAMA -->
    <aside class="sidebar" id="sidebar">
        <!-- HEADER BRAND & LOGO BOX CONTAINER -->
        <div class="sidebar-brand d-flex align-items-center gap-3">
            <!-- CONTAINER LOGO (MODULAR LOGO SLOT) -->
            <div class="logo-box">
                <!-- Ubah src di bawah ke file logo Anda (misal: assets/img/logo.png) -->
                <img src="assets/img/logo.png" alt="CoCoLink Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
            </div>

            <div style="position:relative; z-index:2;">
                <h5 class="fw-extrabold text-white mb-0" style="letter-spacing: -0.3px; font-weight:800;">CoCoLink</h5>
                <small style="color: var(--ocean-accent); font-size: 0.65rem; letter-spacing:1px; font-weight:700;">
                    <?= ($u['role'] ?? '') === 'super_admin' ? 'MASTER SYSTEM' : 'WORKSPACE B2B' ?>
                </small>
            </div>
        </div>

        <!-- MENU NAVIGASI SCROLLABLE TERISOLASI (RETAIN SCROLL POSITION) -->
        <nav class="sidebar-scroll" id="sidebarNav">
            <?php foreach ($menuGroups as $groupName => $items): ?>
                <?php 
                    $hasAccess = false;
                    foreach ($items as $item) {
                        if (in_array(($u['role'] ?? ''), $item['roles'], true)) {
                            $hasAccess = true;
                            break;
                        }
                    }
                ?>
                
                <?php if ($hasAccess): ?>
                    <div class="sidebar-heading text-uppercase"><?= e($groupName) ?></div>
                    <?php foreach ($items as $key => $item): ?>
                        <?php if (in_array(($u['role'] ?? ''), $item['roles'], true)): ?>
                            <a href="<?= url($key) ?>" class="nav-link-coco <?= $page === $key ? 'active' : '' ?>">
                                <i class="bi <?= $item['icon'] ?> nav-icon"></i>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- FOOTER LOGOUT BUTTON -->
        <div class="sidebar-footer">
            <form method="post" action="<?= url('login') ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout-coco">
                    <i class="bi bi-power text-danger"></i>
                    <span>Keluar Sistem</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- KONTEN UTAMA KANAN -->
    <main class="main-content">
        <!-- TOPBAR HEADER -->
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <!-- Hamburger Menu Mobile -->
                <button class="btn btn-light d-lg-none rounded-3 p-2 border" type="button" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-4 text-dark"></i>
                </button>
                
                <div class="d-none d-sm-block">
                    <div class="text-muted" style="font-size:0.75rem; font-weight:700; letter-spacing:0.5px;">MODUL OPERASIONAL</div>
                    <strong class="text-dark fs-5 capitalize" style="letter-spacing:-0.3px;"><?= e(ucwords(str_replace('_', ' ', $page))) ?></strong>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- DROPDOWN NOTIFIKASI REAL-TIME -->
                <div class="dropdown">
                    <button class="btn btn-light position-relative rounded-circle p-2 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 42px; height: 42px; background: #F8FAFC;">
                        <i class="bi bi-bell-fill text-secondary fs-6"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem;">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 mt-2" style="width: 310px; border-radius: 14px; z-index: 1050;">
                        <li class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <strong class="small text-dark"><i class="bi bi-bell me-1" style="color:var(--ocean-primary);"></i> Pemberitahuan</strong>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary"><?= $unreadCount ?> Baru</span>
                        </li>
                        <?php if ($recentNotifs): ?>
                            <?php foreach ($recentNotifs as $notif): ?>
                                <li>
                                    <a class="dropdown-item py-2 px-3 rounded-3 small text-wrap mt-1" href="<?= url(($u['role'] ?? '') === 'coop' ? 'allocations' : 'verification') ?>">
                                        <div class="fw-bold text-dark"><?= e($notif['batch_code']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= ($u['role'] ?? '') === 'coop' ? 'Alokasi bahan mentah masuk (' . number($notif['quantity']) . ' kg)' : 'Pendaftaran Koperasi Sektor baru.' ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center small fw-bold" href="<?= url(($u['role'] ?? '') === 'coop' ? 'allocations' : 'verification') ?>" style="color:var(--ocean-primary);">Lihat Semua Notifikasi</a></li>
                        <?php else: ?>
                            <li class="py-4 text-center text-muted small">Tidak ada notifikasi baru saat ini.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="d-none d-md-block text-end">
                    <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= e($u['name'] ?? 'User') ?></div>
                    <span class="fw-semibold" style="font-size:0.75rem; color:var(--ocean-primary);"><i class="bi bi-shield-check me-1"></i><?= e(strtoupper($u['role'] ?? 'GUEST')) ?></span>
                </div>
                <div class="text-white rounded-circle d-flex justify-content-center align-items-center fw-bold shadow-sm" style="width:42px; height:42px; font-size:1.1rem; background: var(--ocean-primary);">
                    <?= strtoupper(substr(($u['name'] ?? 'U'), 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- FLASH NOTIFICATION ALERT -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['kind']) ?> alert-dismissible fade show shadow-sm border-0 mb-4" style="border-radius:14px;">
                <i class="bi bi-info-circle-fill me-2"></i><?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- KONTEN HALAMAN DINAMIS -->
        <?php $contentRenderer(); ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SCRIPT UNTUK PRESERVE SCROLLBAR SIDEBAR & TOGGLE MOBILE -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarNav = document.getElementById('sidebarNav');
        
        // Restore Posisi Scroll Sidebar dari sessionStorage
        if (sidebarNav) {
            const savedScroll = sessionStorage.getItem('cocolink_sidebar_scroll');
            if (savedScroll !== null) {
                sidebarNav.scrollTop = parseInt(savedScroll, 10);
            }
            
            // Simpan Posisi Scroll Setiap Kali User Menggulung Sidebar
            sidebarNav.addEventListener('scroll', function() {
                sessionStorage.setItem('cocolink_sidebar_scroll', sidebarNav.scrollTop);
            });
        }
    });

    // Toggle Sidebar Mobile Drawer
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
</script>

</body>
</html>
<?php
}