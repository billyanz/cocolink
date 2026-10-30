<?php
/** Jalankan setelah import SQL: php tools/seed_demo.php (hanya CLI). */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require __DIR__.'/../app/bootstrap.php';
if(val('SELECT COUNT(*) FROM users')>0){echo "Database sudah punya akun. Demi keamanan data, seed demo dibatalkan.\n";exit(1);}
$pwd='CocoLink!2026';$h=password_hash($pwd,PASSWORD_DEFAULT);
db()->beginTransaction();
try {
 $makeUser=function(string $name,string $email,string $role,?int $sid=null,?int $cid=null,?int $bid=null)use($h):int{
  sql('INSERT INTO users(name,email,password_hash,role,supplier_id,coop_id,buyer_id) VALUES (?,?,?,?,?,?,?)',[$name,$email,$h,$role,$sid,$cid,$bid]);return(int)db()->lastInsertId();
 };
 $admin=$makeUser('Admin Platform','admin@cocolink.test','super_admin');
 $induk=$makeUser('Admin Koperasi Induk','induk@cocolink.test','induk');
 sql("INSERT INTO suppliers(legal_name,nib,province,city,address,latitude,longitude,status,verified_by,verified_at) VALUES (?,?,?,?,?,?,?,'verified',?,NOW())",['PT Demo Nusantara Kelapa','DEMO-001','Jawa Barat','Bandung','Alamat simulasi untuk pengujian CocoLink',-6.9175000,107.6191000,$induk]);$sid=(int)db()->lastInsertId();
 $su=$makeUser('Manajer PT Demo','pt@cocolink.test','supplier',$sid);
 $names=['Koperasi Hijau Daun','Koperasi Arang Tempurung','Koperasi Sabut Sirkular','Koperasi Kayu Kelapa','Koperasi Olahan Air'];
 $cities=['Bandung','Cimahi','Sumedang','Garut','Subang'];$coopIds=[];
 foreach($names as $i=>$name){$t=$i+1;
  sql("INSERT INTO cooperatives(supplier_id,name,province,city,address,specialty_raw_type_id,status,verified_by) VALUES(?,?,?,?,?,?,'verified',?)",[$sid,$name,'Jawa Barat',$cities[$i],'Alamat pickup demo, '.$cities[$i],$t,$induk]);$coopIds[]=(int)db()->lastInsertId();
 }
 $co=$makeUser('Ketua Koperasi Daun','anak@cocolink.test','coop',null,$coopIds[0]);
 sql("INSERT INTO buyer_profiles(company_name,nib,address,city,status) VALUES (?,?,?,?,'verified')",['PT Contoh Industri Hijau','DEMO-BUY-01','Alamat pengiriman demo, Jakarta','Jakarta']);$bid=(int)db()->lastInsertId();
 $buyer=$makeUser('Purchasing Industri','buyer@cocolink.test','buyer',null,null,$bid);
 $driver=$makeUser('Driver CocoLink','driver@cocolink.test','driver');
 sql('INSERT INTO raw_batches(supplier_id,raw_type_id,batch_code,quantity,unit,notes) VALUES(?,?,?,?,?,?)',[$sid,1,'RAW-DEMO-001',500,'kg','Bahan mentah simulasi']);$rawid=(int)db()->lastInsertId();
 sql("INSERT INTO allocations(batch_id,coop_id,quantity,status,created_by) VALUES(?,?,?,'accepted',?)",[$rawid,$coopIds[0],250,$induk]);$aid=(int)db()->lastInsertId();
 sql("INSERT INTO products(coop_id,raw_type_id,sku,name,description,unit,price,stock) VALUES(?,?,?,?,?,?,?,?)",[$coopIds[0],1,'DAUN-DEMO-001','Sapu Lidi Kelapa Premium','Produk hasil olahan daun kelapa','kg',12000,0]);$pid=(int)db()->lastInsertId();
 sql('INSERT INTO production_logs(allocation_id,product_id,input_qty,output_qty,notes,created_by) VALUES(?,?,?,?,?,?)',[$aid,$pid,200,150,'Simulasi konversi: 200 kg daun menjadi 150 kg sapu lidi',$co]);
 sql('UPDATE products SET stock=150 WHERE id=?',[$pid]);
 sql("INSERT INTO orders(order_code,buyer_id,shipping_address,city,status,total,approved_by) VALUES(?,?,?,?,'assigned',?,?)",['ORD-DEMO-001',$bid,'Alamat pengiriman demo, Jakarta','Jakarta',240000,$induk]);$oid=(int)db()->lastInsertId();
 sql('INSERT INTO order_items(order_id,product_id,quantity,unit_price) VALUES(?,?,?,?)',[$oid,$pid,20,12000]);
 sql('UPDATE products SET stock=stock-20 WHERE id=?',[$pid]);
 sql('INSERT INTO shipments(order_id,driver_id) VALUES(?,?)',[$oid,$driver]);$sh=(int)db()->lastInsertId();
 sql("INSERT INTO shipment_events(shipment_id,event_type,note,actor_id) VALUES(?,'assigned','Order demo telah ditugaskan',?)",[$sh,$induk]);
 audit($admin,'seed_demo','system',null,'Data pengujian, bukan entitas nyata');db()->commit();
 echo "CocoLink data DEMO terpasang.\nPassword untuk seluruh akun: {$pwd}\n";
 echo "admin@cocolink.test (super admin)\ninduk@cocolink.test (koperasi induk)\npt@cocolink.test (PT pemasok)\nanak@cocolink.test (koperasi anak)\nbuyer@cocolink.test (pembeli)\ndriver@cocolink.test (driver)\n";
} catch(Throwable $ex){db()->rollBack();fwrite(STDERR,"Seed gagal: ".$ex->getMessage()."\n");exit(1);}
