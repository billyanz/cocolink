<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/lib/exports.php';

try {
    $u      = user();
    $type   = (string)($_GET['type'] ?? '');
    $format = (string)($_GET['format'] ?? 'pdf');

    if (!in_array($format, ['pdf', 'xlsx'], true)) {
        throw new RuntimeException('Format laporan tidak valid.');
    }

    // 1. Ekspor Invoice Pesanan Pembeli (PDF)
    if ($type === 'invoice') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new RuntimeException('ID invoice tidak valid.');
        }

        $o = one('SELECT o.*, b.company_name, b.address AS buyer_address FROM orders o JOIN buyer_profiles b ON b.id=o.buyer_id WHERE o.id=?', [$id]);
        if (!$o || (!is_manager($u) && !($u['role'] === 'buyer' && (int)$u['buyer_id'] === (int)$o['buyer_id']))) {
            throw new RuntimeException('Invoice ini tidak dapat Anda akses.');
        }

        if (in_array($o['status'], ['requested', 'rejected'], true)) {
            throw new RuntimeException('Invoice belum dapat diterbitkan karena pesanan belum disetujui.');
        }

        $items = rows('SELECT i.*, p.name AS product_name, p.sku, c.name AS coop_name 
                      FROM order_items i 
                      JOIN products p ON p.id=i.product_id 
                      JOIN cooperatives c ON c.id=p.coop_id 
                      WHERE i.order_id=?', [$id]);

        $label = 'INV-' . substr($o['order_code'], 4);
        
        $pdf = new CocoPdf();
        $pdf->newPage();
        $pdf->text(40, 758, 'INVOICE / DOKUMEN TRANSAKSI SAH', 16, true, [0.086, 0.294, 0.208]);
        $pdf->text(40, 735, 'No. Referensi: ' . $label, 11, true, [0.2, 0.2, 0.2]);
        $pdf->text(40, 715, 'Tanggal Transaksi: ' . substr($o['created_at'], 0, 10));
        $pdf->text(40, 694, 'Pembeli Industri: ' . $o['company_name'], 11, true);
        $pdf->text(40, 678, 'Kota Tujuan: ' . $o['city']);
        $pdf->text(40, 662, 'Alamat Pengiriman: ' . uni_slice($o['shipping_address'], 0, 75));

        // Header Tabel Produk Invoice
        $pdf->rect(40, 609, 515, 26, [0.863, 0.929, 0.851]);
        $pdf->text(48, 618, 'Produk Olahan', 10, true, [0.086, 0.294, 0.208]);
        $pdf->text(300, 618, 'Qty (kg)', 10, true, [0.086, 0.294, 0.208]);
        $pdf->text(380, 618, 'Harga / kg', 10, true, [0.086, 0.294, 0.208]);
        $pdf->text(470, 618, 'Subtotal', 10, true, [0.086, 0.294, 0.208]);

        $y = 585;
        foreach ($items as $item) {
            $pdf->text(48, $y, uni_slice($item['product_name'], 0, 35), 9);
            $pdf->text(300, $y, number((float)$item['quantity']), 9);
            $pdf->text(380, $y, money($item['unit_price']), 8);
            $pdf->text(470, $y, money((float)$item['unit_price'] * (float)$item['quantity']), 8);
            $pdf->line(40, $y - 9, 555, $y - 9);
            $y -= 28;
        }

        $pdf->text(320, $y - 18, 'TOTAL BAYAR', 11, true, [0.086, 0.294, 0.208]);
        $pdf->text(440, $y - 18, money($o['total']), 12, true, [0.086, 0.294, 0.208]);

        $pdf->text(40, $y - 75, 'Status Pesanan: ' . status_text($o['status']), 10, true);
        $pdf->text(40, $y - 95, '* Dokumen transaksi ini diterbitkan otomatis oleh CoCoLink Circular Network.', 8, false, [0.4, 0.4, 0.4]);

        $pdf->output($label . '.pdf');
    }

    // Hanya Admin Induk / Super Admin yang dapat mengakses laporan rekapitulasi
    must_role(['super_admin', 'induk']);

    // 2. Laporan Rekap Pesanan Industri
    if ($type === 'orders') {
        $data = rows("SELECT o.order_code, b.company_name, o.city, o.status, o.total, o.created_at 
                      FROM orders o 
                      JOIN buyer_profiles b ON b.id=o.buyer_id 
                      ORDER BY o.id DESC");
        $headers = ['Kode Order', 'Pembeli Industri', 'Kota Tujuan', 'Status', 'Total (Rp)', 'Tanggal'];
        $clean = array_map(fn($x) => [
            $x['order_code'], $x['company_name'], $x['city'], status_text($x['status']), (float)$x['total'], $x['created_at']
        ], $data);

        if ($format === 'xlsx') {
            export_xlsx('CocoLink_Pesanan.xlsx', $headers, $clean);
        }
        export_pdf_table('Laporan Transaksi Pesanan Industri', $headers, $clean, 'CocoLink_Pesanan.pdf');
    }

    // 3. Laporan Pasokan Limbah Kelapa
    if ($type === 'batches') {
        $data = rows("SELECT b.batch_code, s.legal_name, r.name AS raw_name, b.quantity, 
                      COALESCE((SELECT SUM(a.quantity) FROM allocations a WHERE a.batch_id=b.id AND a.status<>'rejected'),0) AS reserved, 
                      b.created_at 
                      FROM raw_batches b 
                      JOIN suppliers s ON s.id=b.supplier_id 
                      JOIN raw_types r ON r.id=b.raw_type_id 
                      ORDER BY b.id DESC");
        $headers = ['Batch', 'PT Pemasok', 'Jenis Limbah', 'Awal (kg)', 'Alokasi (kg)', 'Sisa (kg)', 'Tanggal'];
        $clean = array_map(fn($x) => [
            $x['batch_code'], $x['legal_name'], $x['raw_name'], (float)$x['quantity'], (float)$x['reserved'], (float)$x['quantity'] - (float)$x['reserved'], $x['created_at']
        ], $data);

        if ($format === 'xlsx') {
            export_xlsx('CocoLink_Pasokan.xlsx', $headers, $clean);
        }
        export_pdf_table('Laporan Pasokan Limbah Kelapa', $headers, $clean, 'CocoLink_Pasokan.pdf');
    }

    // 4. Laporan Inventaris Produk Olahan
    if ($type === 'products') {
        $data = rows("SELECT p.sku, p.name, c.name AS coop_name, r.name AS raw_name, p.stock, p.unit, p.price 
                      FROM products p 
                      JOIN cooperatives c ON c.id=p.coop_id 
                      JOIN raw_types r ON r.id=p.raw_type_id 
                      ORDER BY c.name, p.name");
        $headers = ['SKU', 'Nama Produk', 'Koperasi Anak', 'Asal Limbah', 'Stok', 'Satuan', 'Harga (Rp)'];
        $clean = array_map(fn($x) => [
            $x['sku'], $x['name'], $x['coop_name'], $x['raw_name'], (float)$x['stock'], $x['unit'], (float)$x['price']
        ], $data);

        if ($format === 'xlsx') {
            export_xlsx('CocoLink_Produk.xlsx', $headers, $clean);
        }
        export_pdf_table('Inventaris Produk Olahan Koperasi', $headers, $clean, 'CocoLink_Produk.pdf');
    }

    // 5. Laporan Pengiriman Logistik
    if ($type === 'shipments') {
        $data = rows('SELECT o.order_code, b.company_name, u.name AS driver_name, s.status, o.city, s.assigned_at, s.picked_up_at, s.delivered_at 
                      FROM shipments s 
                      JOIN orders o ON o.id=s.order_id 
                      JOIN buyer_profiles b ON b.id=o.buyer_id 
                      JOIN users u ON u.id=s.driver_id 
                      ORDER BY s.id DESC');
        $headers = ['Kode Order', 'Pembeli', 'Driver', 'Status', 'Tujuan', 'Ditugaskan', 'Pickup', 'Selesai'];
        $clean = array_map(fn($x) => [
            $x['order_code'], $x['company_name'], $x['driver_name'], status_text($x['status']), $x['city'], $x['assigned_at'], $x['picked_up_at'] ?? '—', $x['delivered_at'] ?? '—'
        ], $data);

        if ($format === 'xlsx') {
            export_xlsx('CocoLink_Logistik.xlsx', $headers, $clean);
        }
        export_pdf_table('Laporan Pengiriman Logistik Driver', $headers, $clean, 'CocoLink_Logistik.pdf');
    }

    throw new RuntimeException('Jenis laporan tidak dikenali.');

} catch (Throwable $ex) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Gagal mengunduh laporan: ' . $ex->getMessage();
}