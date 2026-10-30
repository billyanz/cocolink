<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/lib/exports.php';
try {
 $u=user();$type=(string)($_GET['type']??'');$format=(string)($_GET['format']??'pdf');
 if(!in_array($format,['pdf','xlsx'],true))throw new RuntimeException('Format laporan tidak valid.');
 if($type==='invoice'){
  $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);if(!$id)throw new RuntimeException('ID invoice tidak valid.');
  $o=one('SELECT o.*,b.company_name,b.address buyer_address FROM orders o JOIN buyer_profiles b ON b.id=o.buyer_id WHERE o.id=?',[$id]);
  if(!$o||!is_manager($u)&&!($u['role']==='buyer'&&(int)$u['buyer_id']===(int)$o['buyer_id']))throw new RuntimeException('Invoice tidak dapat diakses.');
  if(in_array($o['status'],['requested','rejected'],true))throw new RuntimeException('Invoice belum diterbitkan.');
  $items=rows('SELECT i.*,p.name product_name,p.sku,c.name coop_name FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id WHERE i.order_id=?',[$id]);
  $label='INV'.substr($o['order_code'],3);
  $pdf=new CocoPdf();$pdf->newPage();$pdf->text(42,758,'INVOICE / RINCIAN TRANSAKSI',18,true);$pdf->text(42,735,'No. '.$label,12,true);$pdf->text(42,715,'Tanggal transaksi: '.substr($o['created_at'],0,10));$pdf->text(42,694,'Pembeli: '.$o['company_name'],11,true);$pdf->text(42,678,'Tujuan: '.$o['city']);$pdf->text(42,662,'Alamat: '.uni_slice($o['shipping_address'],0,78));
  $pdf->rect(40,609,515,26,[0.89,0.95,0.87]);$pdf->text(48,618,'Produk',10,true);$pdf->text(303,618,'Qty (kg)',10,true);$pdf->text(384,618,'Harga',10,true);$pdf->text(477,618,'Subtotal',10,true);$y=587;
  foreach($items as $item){$pdf->text(48,$y,uni_slice($item['product_name'],0,35),9);$pdf->text(303,$y,(string)$item['quantity'],9);$pdf->text(384,$y,money($item['unit_price']),8);$pdf->text(475,$y,money((float)$item['unit_price']*(float)$item['quantity']),8);$pdf->line(40,$y-9,555,$y-9);$y-=29;}
  $pdf->text(320,$y-19,'TOTAL',12,true);$pdf->text(413,$y-19,money($o['total']),13,true);
  $pdf->text(42,$y-78,'Status: '.status_text($o['status']),10,true);$pdf->text(42,$y-100,'Dokumen transaksi platform (bukan faktur pajak).',9);$pdf->text(42,$y-115,'Pajak, ongkos kirim, pembayaran dan retur dikelola terpisah.',9);
  $pdf->output($label.'.pdf');
 }
 must_role(['super_admin','induk']);
 if($type==='orders'){
  $data=rows("SELECT o.order_code,b.company_name,o.city,o.status,o.total,o.created_at FROM orders o JOIN buyer_profiles b ON b.id=o.buyer_id ORDER BY o.id DESC");
  $headers=['Kode order','Pembeli','Kota','Status','Total (Rp)','Tanggal'];
  $clean=array_map(fn($x)=>[$x['order_code'],$x['company_name'],$x['city'],status_text($x['status']),(float)$x['total'],$x['created_at']],$data);
  if($format==='xlsx')export_xlsx('CocoLink_Pesanan.xlsx',$headers,$clean);
  export_pdf_table('Laporan Pesanan',$headers,$clean,'CocoLink_Pesanan.pdf');
 }
 if($type==='batches'){
  $data=rows("SELECT b.batch_code,s.legal_name,r.name raw_name,b.quantity,COALESCE((SELECT SUM(a.quantity) FROM allocations a WHERE a.batch_id=b.id AND a.status<>'rejected'),0) reserved,b.created_at FROM raw_batches b JOIN suppliers s ON s.id=b.supplier_id JOIN raw_types r ON r.id=b.raw_type_id ORDER BY b.id DESC");
  $headers=['Batch','PT pemasok','Limbah','Awal (kg)','Alokasi (kg)','Sisa (kg)','Tanggal'];
  $clean=array_map(fn($x)=>[$x['batch_code'],$x['legal_name'],$x['raw_name'],(float)$x['quantity'],(float)$x['reserved'],(float)$x['quantity']-(float)$x['reserved'],$x['created_at']],$data);
  if($format==='xlsx')export_xlsx('CocoLink_Pasokan.xlsx',$headers,$clean);
  export_pdf_table('Laporan Pasokan Limbah',$headers,$clean,'CocoLink_Pasokan.pdf');
 }
 if($type==='products'){
  $data=rows("SELECT p.sku,p.name,c.name coop_name,r.name raw_name,p.stock,p.unit,p.price FROM products p JOIN cooperatives c ON c.id=p.coop_id JOIN raw_types r ON r.id=p.raw_type_id ORDER BY c.name,p.name");
  $headers=['SKU','Produk','Koperasi','Asal bahan','Stok','Satuan','Harga (Rp)'];
  $clean=array_map(fn($x)=>[$x['sku'],$x['name'],$x['coop_name'],$x['raw_name'],(float)$x['stock'],$x['unit'],(float)$x['price']],$data);
  if($format==='xlsx')export_xlsx('CocoLink_Produk.xlsx',$headers,$clean);
  export_pdf_table('Inventaris Produk Jadi',$headers,$clean,'CocoLink_Produk.pdf');
 }
 if($type==='shipments'){
  $data=rows('SELECT o.order_code,b.company_name,u.name driver_name,s.status,o.city,s.assigned_at,s.picked_up_at,s.delivered_at FROM shipments s JOIN orders o ON o.id=s.order_id JOIN buyer_profiles b ON b.id=o.buyer_id JOIN users u ON u.id=s.driver_id ORDER BY s.id DESC');
  $headers=['Kode order','Pembeli','Driver','Status','Tujuan','Ditugaskan','Pickup','Selesai'];
  $clean=array_map(fn($x)=>[$x['order_code'],$x['company_name'],$x['driver_name'],status_text($x['status']),$x['city'],$x['assigned_at'],$x['picked_up_at']??'—',$x['delivered_at']??'—'],$data);
  if($format==='xlsx')export_xlsx('CocoLink_Logistik.xlsx',$headers,$clean);
  export_pdf_table('Laporan Pengiriman',$headers,$clean,'CocoLink_Logistik.pdf');
 }
 throw new RuntimeException('Jenis laporan tidak dikenali.');
} catch(Throwable $ex){http_response_code(400);header('Content-Type: text/plain; charset=UTF-8');echo 'Laporan gagal: '.$ex->getMessage();}
