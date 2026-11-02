<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

function db(): PDO {
    global $config;
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
            $config['db_user'],
            $config['db_password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]
        );
    }
    return $pdo;
}

function sql(string $query, array $params = []): PDOStatement {
    $st = db()->prepare($query);
    $st->execute($params);
    return $st;
}

function rows(string $query, array $params = []): array {
    return sql($query, $params)->fetchAll();
}

function one(string $query, array $params = []): ?array {
    $r = sql($query, $params)->fetch();
    return $r ?: null;
}

function val(string $query, array $params = []): mixed {
    return sql($query, $params)->fetchColumn();
}

function uni_len(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function uni_lower(string $s): string {
    return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
}

function uni_slice(string $s, int $start, int $length): string {
    return function_exists('mb_substr') ? mb_substr($s, $start, $length) : substr($s, $start, $length);
}

function e(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_base(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
}

function url(string $page = 'dashboard', array $args = []): string {
    return app_base() . '/index.php?' . http_build_query(array_merge(['page' => $page], $args));
}

function redirect(string $page = 'dashboard', array $args = []): never {
    header('Location: ' . url($page, $args));
    exit;
}

function money(mixed $number): string {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function number(mixed $n, int $dec = 2): string {
    return number_format((float)$n, $dec, ',', '.');
}

function csrf(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}

function require_csrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {
        throw new RuntimeException('Sesi formulir telah berakhir demi keamanan. Silakan muat ulang halaman.');
    }
}

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $u = one('SELECT * FROM users WHERE id=?', [(int)$_SESSION['uid']]);
    if (!$u || !(int)$u['is_active']) {
        unset($_SESSION['uid']);
        return null;
    }
    return $u;
}

function user(): array {
    $u = current_user();
    if (!$u) {
        redirect('login');
    }
    return $u;
}

function must_role(array $roles): array {
    $u = user();
    if (!in_array($u['role'], $roles, true)) {
        throw new RuntimeException('Anda tidak memiliki hak akses untuk membuka halaman ini.');
    }
    return $u;
}

function is_manager(?array $u = null): bool {
    $u ??= current_user();
    return $u && in_array($u['role'], ['super_admin', 'induk'], true);
}

function flash(string $message, string $kind = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'kind' => $kind];
}

function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function required_str(string $key, int $max = 255): string {
    $v = trim((string)($_POST[$key] ?? ''));
    if ($v === '' || uni_len($v) > $max) {
        throw new RuntimeException('Kolom ' . str_replace('_', ' ', $key) . ' wajib diisi (maksimal ' . $max . ' karakter).');
    }
    return $v;
}

function post_id(string $key): int {
    $id = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new RuntimeException('Data ID ' . e($key) . ' tidak valid.');
    }
    return $id;
}

function positive(string $key): float {
    $str = str_replace(',', '.', trim((string)($_POST[$key] ?? '')));
    if (!is_numeric($str) || (float)$str <= 0) {
        throw new RuntimeException('Jumlah ' . str_replace('_', ' ', $key) . ' harus berupa angka positif.');
    }
    return round((float)$str, 2);
}

function coord(string $key, float $min, float $max, bool $nullable = false): ?float {
    $s = trim((string)($_POST[$key] ?? ''));
    if ($s === '' && $nullable) return null;
    if ($s === '' || !is_numeric($s) || (float)$s < $min || (float)$s > $max) {
        throw new RuntimeException('Koordinat lokasi tidak valid.');
    }
    return (float)$s;
}

function audit(?int $u, string $action, string $entity, ?int $id = null, string $details = ''): void {
    sql('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,details,ip) VALUES (?,?,?,?,?,?)', [
        $u, $action, $entity, $id, $details, substr((string)($_SERVER['REMOTE_ADDR'] ?? 'CLI'), 0, 45)
    ]);
}

function code(string $prefix): string {
    return $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function status_text(string $status): string {
    return [
        'pending'          => 'Menunggu Pengesahan',
        'verified'         => 'Mitra Resmi / Terverifikasi',
        'rejected'         => 'Ditolak / Perlu Perbaikan',
        'proposed'         => 'Menunggu Persetujuan Koperasi',
        'accepted'         => 'Telah Diterima Koperasi',
        'completed'        => 'Transaksi Selesai & Berhasil',
        'requested'        => 'Menunggu Persetujuan Order',
        'approved'         => 'Pesanan Disetujui',
        'assigned'         => 'Driver Telah Ditugaskan',
        'in_transit'       => 'Dalam Perjalanan Pengiriman',
        'pickup_pending'   => 'Pengajuan Pickup (Butuh ACC)',
        'pickup_requested' => 'Driver Meminta Pickup',
        'pickup_rejected'  => 'Pickup Ditolak Gudang',
        'picked_up'        => 'Sudah Diambil dari Gudang',
        'delivered'        => 'Barang Tiba di Tujuan',
        'location'         => 'Pembaruan Posisi GPS'
    ][$status] ?? ucwords(str_replace('_', ' ', $status));
}

function badge(string $status): string {
    $color = match ($status) {
        'completed', 'verified', 'delivered' => 'success',
        'rejected', 'pickup_rejected'        => 'danger',
        'accepted', 'approved'               => 'info',
        'in_transit', 'picked_up'            => 'primary',
        default                              => 'warning'
    };
    return '<span class="badge rounded-pill text-bg-' . $color . ' px-3 py-2 fw-semibold" style="font-size:0.82rem;"><i class="bi bi-dot me-1"></i>' . e(status_text($status)) . '</span>';
}

function initials(string $name): string {
    $words = preg_split('/\s+/u', trim($name));
    return strtoupper(substr(($words[0] ?? 'C'), 0, 1) . substr(($words[1] ?? 'L'), 0, 1));
}

/* ==========================================================================
   HELPER INPUT FORM & TAMPILAN ELEGAN (KPI, Field, Select, Action)
   ========================================================================== */

/**
 * HELPER KPI CARD (Fix undefined function kpi)
 */
function kpi(string $label, mixed $val, string $icon = 'bi-activity', string $colorClass = 'text-success bg-success'): void {
?>
    <div class="card border-0 shadow-sm p-3 h-100" style="border-radius:14px;">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle <?= e($colorClass) ?> bg-opacity-10 fs-4">
                <i class="bi <?= e($icon) ?>"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium"><?= e($label) ?></div>
                <h4 class="fw-bold mb-0 text-dark"><?= is_numeric($val) ? number_format((float)$val) : e((string)$val) ?></h4>
            </div>
        </div>
    </div>
<?php
}

function field(string $label, string $name, string $type = 'text', string $value = '', string $attributes = 'required', string $placeholder = ''): void {
?>
    <div class="mb-3">
        <label class="form-label fw-semibold text-dark small mb-1" for="<?= e($name) ?>"><?= e($label) ?></label>
        <input type="<?= e($type) ?>" class="form-control" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="<?= e($placeholder) ?>" <?= $attributes ?> style="border-radius:10px; padding:0.6rem 0.9rem;">
    </div>
<?php
}

function select_field(string $label, string $name, array $options, string $selected = '', string $attributes = 'required'): void {
?>
    <div class="mb-3">
        <label class="form-label fw-semibold text-dark small mb-1" for="<?= e($name) ?>"><?= e($label) ?></label>
        <select class="form-select" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $attributes ?> style="border-radius:10px; padding:0.6rem 0.9rem;">
            <option value="">-- Pilih <?= e($label) ?> --</option>
            <?php foreach ($options as $val => $txt): ?>
                <option value="<?= e((string)$val) ?>" <?= (string)$val === (string)$selected ? 'selected' : '' ?>>
                    <?= e((string)$txt) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
<?php
}

function action_form(string $action, int $id, string $label, string $btnClass = 'btn-coco btn-sm', array $extraParams = []): void {
?>
    <form method="post" class="d-inline-block m-0">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="<?= e($action) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php foreach ($extraParams as $k => $v): ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
        <?php endforeach; ?>
        <button class="btn <?= e($btnClass) ?> fw-bold px-3 shadow-sm" style="border-radius:8px;"><?= e($label) ?></button>
    </form>
<?php
}

function table_empty(string $msg = 'Belum ada catatan data transaksi yang tersedia.'): void {
?>
    <div class="py-5 text-center text-muted">
        <div class="display-6 mb-2 text-secondary"><i class="bi bi-inbox-fill"></i></div>
        <div class="fw-semibold text-secondary"><?= e($msg) ?></div>
        <small class="text-muted">Gunakan tombol atau formulir di atas untuk menambahkan data baru.</small>
    </div>
<?php
}

function check_supplier_active(array $u): void {
    if ($u['role'] !== 'supplier' || !$u['supplier_id'] || val('SELECT status FROM suppliers WHERE id=?', [$u['supplier_id']]) !== 'verified') {
        throw new RuntimeException('PT Pemasok Anda belum aktif atau masih dalam tahap verifikasi.');
    }
}

function check_coop_active(array $u): void {
    if ($u['role'] !== 'coop' || !$u['coop_id'] || val('SELECT status FROM cooperatives WHERE id=?', [$u['coop_id']]) !== 'verified') {
        throw new RuntimeException('Koperasi Anak Anda belum diaktifkan oleh Admin Koperasi Induk.');
    }
}

function check_buyer_active(array $u): void {
    if ($u['role'] !== 'buyer' || !$u['buyer_id'] || val('SELECT status FROM buyer_profiles WHERE id=?', [$u['buyer_id']]) !== 'verified') {
        throw new RuntimeException('Akun Pembeli Industri Anda masih menunggu persetujuan legalitas.');
    }
}