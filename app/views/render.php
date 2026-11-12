<?php
declare(strict_types=1);

require_once __DIR__ . '/pages/dashboard.php';
require_once __DIR__ . '/pages/verification.php';
require_once __DIR__ . '/pages/accounts.php';
require_once __DIR__ . '/pages/batches.php';
require_once __DIR__ . '/pages/allocations.php';
require_once __DIR__ . '/pages/products.php';
require_once __DIR__ . '/pages/catalog.php';
require_once __DIR__ . '/pages/orders.php';
require_once __DIR__ . '/pages/shipments.php';
require_once __DIR__ . '/pages/maps.php';
require_once __DIR__ . '/pages/reports.php';
require_once __DIR__ . '/pages/audit.php';

function render_page(string $page, array $u): void {
    render_layout($page, $u, function() use ($page, $u) {
        try {
            match ($page) {
                'dashboard'    => page_dashboard($u),
                'verification' => page_verification($u),
                'accounts'     => page_accounts($u),
                'batches'      => page_batches($u),
                'allocations'  => page_allocations($u),
                'products'     => page_products($u),
                'catalog'      => page_catalog($u),
                'orders'       => page_orders($u),
                'shipments'    => page_shipments($u),
                'maps'         => page_maps($u),
                'reports'      => page_reports($u),
                'audit'        => page_audit($u),
                default        => page_dashboard($u)
            };
        } catch (Throwable $e) {
            echo '<div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 my-3">';
            echo '  <div class="d-flex align-items-center gap-2 mb-2">';
            echo '    <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>';
            echo '    <h5 class="fw-bold mb-0 text-dark">Terjadi Kesalahan Database / Sistem</h5>';
            echo '  </div>';
            echo '  <p class="mb-0 text-secondary small" style="font-family: monospace;">' . e($e->getMessage()) . '</p>';
            echo '</div>';
        }
    });
}