<?php
function page_orders(array $u): void {
    $manager = is_manager($u);
    $data    = $manager 
        ? rows('SELECT o.*, bp.company_name, us.name AS approver FROM orders o JOIN buyer_profiles bp ON bp.id=o.buyer_id LEFT JOIN users us ON us.id=o.approved_by ORDER BY o.id DESC')
        : rows('SELECT o.*, bp.company_name, us.name AS approver FROM orders o JOIN buyer_profiles bp ON bp.id=o.buyer_id LEFT JOIN users us ON us.id=o.approved_by WHERE o.buyer_id=? ORDER BY o.id DESC', [$u['buyer_id']]);
        
    $items   = rows('SELECT i.order_id, i.quantity, i.unit_price, p.name AS product_name, p.sku, c.name AS coop_name FROM order_items i JOIN products p ON p.id=i.product_id JOIN cooperatives c ON c.id=p.coop_id ORDER BY i.order_id DESC');
    $itemsBy = [];
    foreach ($items as $it) $itemsBy[$it['order_id']][] = $it;
?>
<div class="notice mb-4">
    Alur pesanan: Diajukan Pembeli &rarr; Divalidasi Koperasi Induk &rarr; Driver ditugaskan &rarr; Pengiriman &rarr; Konfirmasi Penerimaan. PDF Invoice tersedia saat pesanan disetujui.
</div>

<div class="panel bg-white border" style="border-radius:16px; overflow:hidden;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Pesanan Industri &bull; <?= count($data) ?></h5>
        <?php if (!$manager): ?>
            <a class="btn btn-sm btn-coco fw-bold" href="<?= e(url('catalog')) ?>">+ Pesan dari Katalog</a>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Kode / Pembeli</th>
                    <th>Produk & Koperasi</th>
                    <th>Tujuan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Dokumen / Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $o): ?>
                <tr>
                    <td style="min-width:175px">
                        <b><?= e($o['order_code']) ?></b>
                        <div class="helper small text-muted"><?= e($o['company_name']) ?> &bull; <?= e(substr($o['created_at'], 0, 10)) ?></div>
                    </td>
                    <td>
                    <?php foreach ($itemsBy[$o['id']] ?? [] as $i): ?>
                        <div><strong><?= e($i['product_name']) ?></strong></div>
                        <div class="helper small text-muted"><?= e($i['coop_name']) ?> / <?= number($i['quantity']) ?> kg &times; <?= money($i['unit_price']) ?></div>
                    <?php endforeach; ?>
                    </td>
                    <td>
                        <?= e($o['city']) ?>
                        <div class="helper small text-muted" style="max-width:220px"><?= e($o['shipping_address']) ?></div>
                    </td>
                    <td class="fw-bold"><?= money($o['total']) ?></td>
                    <td><?= badge($o['status']) ?></td>
                    <td style="min-width:180px">
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (!in_array($o['status'], ['requested', 'rejected'], true)): ?>
                                <a class="btn btn-sm btn-outline-coco" href="<?= e(app_base() . '/report.php?type=invoice&id=' . $o['id'] . '&format=pdf') ?>" target="_blank">PDF Invoice</a>
                            <?php endif; ?>
                            
                            <?php if ($manager && $o['status'] === 'requested'): ?>
                                <?php action_form('approve_order', (int)$o['id'], 'Setujui', 'btn-outline-coco btn-sm', ['decision' => 'approved']); ?>
                                <?php action_form('approve_order', (int)$o['id'], 'Tolak', 'btn-outline-danger btn-sm', ['decision' => 'rejected']); ?>
                            <?php endif; ?>
                            
                            <?php if (!$manager && $o['status'] === 'delivered'): ?>
                                <?php action_form('confirm_order', (int)$o['id'], 'Konfirmasi Terima', 'btn-coco btn-sm'); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$data) table_empty('Belum ada pesanan.'); ?>
    </div>
</div>
<?php } ?>