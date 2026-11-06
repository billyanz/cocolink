<?php
declare(strict_types=1);

// Path pemanggilan disesuaikan secara presisi ke dalam folder app/
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/actions.php';
require __DIR__ . '/app/views/layout.php';
require __DIR__ . '/app/views/public.php';
require __DIR__ . '/app/views/pages.php';

try {
    $u = current_user();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        process_action();
    }

    $page = trim((string)($_GET['page'] ?? 'dashboard'));
    if ($page === '') $page = 'dashboard';

    // Halaman Publik (Tanpa Login)
    if (in_array($page, ['login', 'register_supplier'], true)) {
        if ($u && $page === 'login') {
            redirect('dashboard');
        }
        render_public($page);
        exit;
    }

    // Wajib Login untuk Halaman Utama
    if (!$u) {
        redirect('login');
    }

    // Hak Akses Terisolasi per Peran System
    match ($page) {
        'dashboard'    => true,
        'maps'         => true,
        'verification' => must_role(['super_admin', 'induk', 'supplier']),
        'accounts'     => must_role(['super_admin', 'induk', 'supplier']),
        'batches'      => must_role(['super_admin', 'induk', 'supplier']),
        'allocations'  => must_role(['super_admin', 'induk', 'supplier', 'coop']),
        'products'     => must_role(['super_admin', 'induk', 'supplier', 'coop']),
        'catalog'      => must_role(['buyer']),
        'orders'       => must_role(['super_admin', 'induk', 'supplier', 'coop', 'buyer']),
        'shipments'    => must_role(['super_admin', 'induk', 'supplier', 'coop', 'driver', 'buyer']),
        'reports'      => must_role(['super_admin', 'induk', 'supplier']),
        'audit'        => must_role(['super_admin']),
        default        => redirect('dashboard')
    };

    render_page($page, $u);

} catch (Throwable $e) {
    flash($e->getMessage(), 'danger');
    $u = current_user();
    if ($u) {
        render_page('dashboard', $u);
    } else {
        render_public('login');
    }
}