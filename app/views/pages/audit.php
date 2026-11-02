<?php
function page_audit(): void {
    $logs = rows('SELECT l.*, u.name AS user_name FROM audit_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 250');
?>
<div class="notice mb-3">
    Audit trail menyimpan jenis tindakan, pelaku, entitas terkait, IP address, dan timestamp aktivitas. Menampilkan 250 aktivitas terakhir.
</div>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pelaku</th>
                    <th>Tindakan</th>
                    <th>Entitas</th>
                    <th>Keterangan</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="small text-muted"><?= e($l['created_at']) ?></td>
                    <td><?= e($l['user_name'] ?? 'Sistem') ?></td>
                    <td class="fw-bold"><?= e($l['action']) ?></td>
                    <td><?= e($l['entity_type'] . ' #' . $l['entity_id']) ?></td>
                    <td><?= e($l['details']) ?></td>
                    <td class="small text-muted"><?= e($l['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$logs) table_empty('Belum ada aktivitas tercatat.'); ?>
    </div>
</div>
<?php } ?>