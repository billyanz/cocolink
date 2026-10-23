<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth(): array {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function checkRole(array $allowedRoles): void {
    $user = checkAuth();
    if (!in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        die('Akses ditolak. Peran Anda tidak memiliki izin untuk halaman ini.');
    }
}