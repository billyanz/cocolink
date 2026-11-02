<?php
// config/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function sanitizeInput(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function requireAuth(?string $allowedRole = null): array {
    $user = getCurrentUser();
    if (!$user) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }

    if ($allowedRole !== null && $user['role'] !== $allowedRole) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: You lack permission for this operation.']);
            exit;
        }
        http_response_code(403);
        die('Access Denied: Role privileges insufficient.');
    }

    return $user;
}

function logAudit(PDO $pdo, ?int $userId, string $action, string $details): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $ip]);
}