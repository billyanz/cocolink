<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/actions.php';
require __DIR__.'/app/views/layout.php';
require __DIR__.'/app/views/public.php';
$page=(string)($_GET['page']??'home');
try {
 if($_SERVER['REQUEST_METHOD']==='POST')process_action();
 $u=current_user();
 if(!$u){render_public(in_array($page,['home','login','register_supplier','register_buyer'],true)?$page:'login');exit;}
 if(in_array($page,['home','login','register_supplier','register_buyer'],true))redirect('dashboard');
 $roles=[
  'verification'=>['super_admin','induk'],'accounts'=>['super_admin'],
  'audit'=>['super_admin'],'reports'=>['super_admin','induk'],
  'batches'=>['super_admin','induk','supplier'],
  'allocations'=>['super_admin','induk','coop','supplier'],
  'products'=>['super_admin','induk','coop'],'catalog'=>['buyer'],
  'orders'=>['super_admin','induk','buyer'],
  'shipments'=>['super_admin','induk','coop','driver','buyer'],
  'dashboard'=>['super_admin','induk','supplier','coop','buyer','driver'],
  'maps'=>['super_admin','induk','supplier','coop','buyer','driver']
 ];
 if(!isset($roles[$page])){http_response_code(404);$page='dashboard';}
 if(!in_array($u['role'],$roles[$page],true))throw new RuntimeException('Halaman tidak tersedia untuk peran akun Anda.');
 render_header($page,$u);
 require __DIR__.'/app/views/pages.php';
 render_page($page,$u);
 render_footer();
} catch(Throwable $ex){
 try { if(db()->inTransaction())db()->rollBack(); } catch(Throwable $ignored) {}
 $error=$ex instanceof PDOException?'Koneksi/operasi database gagal. Pastikan MySQL aktif, SQL sudah diimpor, dan email/NIB/SKU tidak duplikat.':$ex->getMessage();
 if($_SERVER['REQUEST_METHOD']==='POST'){$back=(string)($_GET['page']??'dashboard');if(!preg_match('/^[a-z_]+$/',$back))$back='dashboard';flash($error,'danger');redirect($back);}
 http_response_code(400);
 ?><!doctype html><html lang="id"><meta charset="utf-8"><title>CocoLink · Kesalahan</title><body style="font:16px Arial;background:#f4f7f2;padding:60px"><h2>⚠️ Tidak dapat membuka halaman</h2><p><?=e($error)?></p><a href="<?=e(url('dashboard'))?>">Kembali ke dashboard</a></body></html><?php
}
