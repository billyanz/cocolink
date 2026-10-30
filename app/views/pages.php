<?php
require_once __DIR__.'/layout.php';
function render_page(string $page,array $u):void {switch($page){
 case 'dashboard':page_dashboard($u);break;
 case 'verification':page_verification();break;
 case 'accounts':page_accounts();break;
 case 'batches':page_batches($u);break;
 case 'allocations':page_allocations($u);break;
 case 'products':page_products($u);break;
 case 'catalog':page_catalog();break;
 case 'orders':page_orders($u);break;
 case 'shipments':page_shipments($u);break;
 case 'maps':page_maps($u);break;
 case 'reports':page_reports();break;
 case 'audit':page_audit();break;
 }}
function kpi(string $label,mixed $value,string $icon='circle-dot'):void { ?><div class="col-sm-6 col-xl-3"><div class="panel kpi"><div class="kpi-label"><?=e($label)?></div><div class="kpi-value"><?=e($value)?></div><div class="kpi-icon"><i class="icon icon-<?=e($icon)?>"></i></div></div></div><?php }

require_once __DIR__.'/pages/dashboard.php';
require_once __DIR__.'/pages/verification.php';
require_once __DIR__.'/pages/accounts.php';
require_once __DIR__.'/pages/batches.php';
require_once __DIR__.'/pages/allocations.php';
require_once __DIR__.'/pages/products.php';
require_once __DIR__.'/pages/catalog.php';
require_once __DIR__.'/pages/orders.php';
require_once __DIR__.'/pages/shipments.php';
require_once __DIR__.'/pages/maps.php';
require_once __DIR__.'/pages/reports.php';
require_once __DIR__.'/pages/audit.php';
