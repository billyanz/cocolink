<?php
function page_audit():void {
 $logs=rows('SELECT l.*,u.name user_name FROM audit_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 250');
 ?><div class="notice mb-3">Audit trail menyimpan jenis tindakan, pelaku, entitas terkait, IP, dan waktu. 250 aktivitas terakhir ditampilkan.</div><div class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Pelaku</th><th>Tindakan</th><th>Entitas</th><th>Keterangan</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $l):?><tr><td><?=e($l['created_at'])?></td><td><?=e($l['user_name']??'Sistem')?></td><td class="fw-bold"><?=e($l['action'])?></td><td><?=e($l['entity_type'].' #'.$l['entity_id'])?></td><td><?=e($l['details'])?></td><td><?=e($l['ip'])?></td></tr><?php endforeach;?></tbody></table><?php if(!$logs)table_empty('Belum ada aktivitas.');?></div></div>
<?php }
