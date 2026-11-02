<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/actions.php';
require __DIR__ . '/app/views/layout.php';
require __DIR__ . '/app/views/public.php';
require __DIR__ . '/app/views/pages.php';

$page = (string)($_GET['page'] ?? 'home');

try {
    // 1. Tangani aksi POST form (Login, Register, Add Batch, Order, dll.)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        process_action();
    }

    $u = current_user();

    // 2. Jika pengguna belum login, tampilkan halaman publik (Home, Login, Register)
    if (!$u) {
        render_public(in_array($page, ['home', 'login', 'register_supplier', 'register_buyer'], true) ? $page : 'login');
        exit;
    }

    // 3. Jika pengguna sudah login tetapi mencoba akses halaman public, arahkan ke dashboard
    if (in_array($page, ['home', 'login', 'register_supplier', 'register_buyer'], true)) {
        redirect('dashboard');
    }

    // 4. Pemetaan Hak Akses (Role-Based Access Control)
    $roles = [
        'verification' => ['super_admin', 'induk'],
        'accounts'     => ['super_admin'],
        'audit'        => ['super_admin'],
        'reports'      => ['super_admin', 'induk'],
        'batches'      => ['super_admin', 'induk', 'supplier'],
        'allocations'  => ['super_admin', 'induk', 'coop', 'supplier'],
        'products'     => ['super_admin', 'induk', 'coop'],
        'catalog'      => ['buyer'],
        'orders'       => ['super_admin', 'induk', 'buyer'],
        'shipments'    => ['super_admin', 'induk', 'supplier', 'coop', 'driver', 'buyer'],
        'dashboard'    => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver'],
        'maps'         => ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver']
    ];

    if (!isset($roles[$page])) {
        http_response_code(404);
        $page = 'dashboard';
    }

    if (!in_array($u['role'], $roles[$page], true)) {
        throw new RuntimeException('Halaman ini tidak tersedia untuk peran akun Anda (' . strtoupper($u['role']) . ').');
    }

    // 5. Render halaman aplikasi secara aman lewat pages.php & layout.php
    render_page($page, $u);

} catch (Throwable $ex) {
    try {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
    } catch (Throwable $ignored) {}

    $error = $ex instanceof PDOException
        ? 'Koneksi atau operasi database gagal. Pastikan MySQL aktif di XAMPP, database cocolink sudah terimpor, dan data tidak duplikat.'
        : $ex->getMessage();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $back = (string)($_GET['page'] ?? 'dashboard');
        if (!preg_match('/^[a-z_]+$/', $back)) {
            $back = 'dashboard';
        }
        flash($error, 'danger');
        redirect($back);
    }

    http_response_code(400);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CoCoLink &bull; Kesalahan Sistem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
</head>
<body style="background:#F6F8F4; padding:60px 20px;">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm border-danger">
            <div class="card-body p-4 text-center">
                <div class="display-4 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h4 class="fw-bold text-dark">Tidak Dapat Membuka Halaman</h4>
                <p class="text-muted mt-3 mb-4"><?= e($error) ?></p>
                <a href="<?= e(url('dashboard')) ?>" class="btn btn-success fw-bold px-4" style="background:#164B35; border:none; border-radius:10px;">
                    <i class="bi bi-house-door-fill me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}