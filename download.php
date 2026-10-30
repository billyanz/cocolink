<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
try {
 $u=user();$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);if(!$id)throw new RuntimeException('ID bukti tidak valid.');
 $ev=one('SELECT ev.*,s.driver_id,o.buyer_id FROM shipment_events ev JOIN shipments s ON s.id=ev.shipment_id JOIN orders o ON o.id=s.order_id WHERE ev.id=?',[$id]);
 if(!$ev||!$ev['proof_path'])throw new RuntimeException('Berkas tidak ditemukan.');
 if(!is_manager($u)&&!($u['role']==='driver'&&(int)$u['id']===(int)$ev['driver_id'])&&!($u['role']==='buyer'&&(int)$u['buyer_id']===(int)$ev['buyer_id']))throw new RuntimeException('Anda tidak memiliki akses bukti pengiriman ini.');
 $p=__DIR__.'/storage/uploads/'.basename($ev['proof_path']);if(!is_file($p))throw new RuntimeException('Berkas tidak tersedia.');
 header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($p));header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline; filename="'.basename($ev['proof_path']).'"');readfile($p);exit;
} catch(Throwable $e){http_response_code(403);echo e($e->getMessage());}
