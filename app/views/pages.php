<?php
declare(strict_types=1);

require __DIR__ . '/pages/dashboard.php';
require __DIR__ . '/pages/verification.php';
require __DIR__ . '/pages/accounts.php';
require __DIR__ . '/pages/batches.php';
require __DIR__ . '/pages/allocations.php';
require __DIR__ . '/pages/products.php';
require __DIR__ . '/pages/catalog.php';
require __DIR__ . '/pages/orders.php';
require __DIR__ . '/pages/shipments.php';
require __DIR__ . '/pages/maps.php';
require __DIR__ . '/pages/reports.php';
require __DIR__ . '/pages/audit.php';

function render_page(string $page, array $u): void {
    render_layout($page, $u, function() use ($page, $u) {
        match ($page) {
            'dashboard'    => page_dashboard($u),
            'verification' => page_verification(),
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
    });
}