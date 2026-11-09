<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/lib/exports.php';

try {
    $u = current_user();
    if (!$u) {
        throw new RuntimeException('Akses ditolak. Silakan login terlebih dahulu.');
    }

    // 1. UNDUH BERKAS BUKTI EVENT SHIPMENT (JIKA PARAMETER ID DIPANGGIL)
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id) {
        $ev = one('SELECT ev.*, s.driver_id, o.buyer_id 
                  FROM shipment_events ev 
                  JOIN shipments s ON s.id=ev.shipment_id 
                  JOIN orders o ON o.id=s.order_id 
                  WHERE ev.id=?', [$id]);

        if (!$ev || !$ev['proof_path']) {
            throw new RuntimeException('Berkas bukti pengiriman tidak ditemukan.');
        }

        if (!is_manager($u) && !($u['role'] === 'driver' && (int)$u['id'] === (int)$ev['driver_id']) && !($u['role'] === 'buyer' && (int)$u['buyer_id'] === (int)$ev['buyer_id'])) {
            throw new RuntimeException('Anda tidak memiliki izin untuk melihat bukti pengiriman ini.');
        }

        $filePath = __DIR__ . '/storage/uploads/' . basename($ev['proof_path']);
        if (!is_file($filePath)) {
            throw new RuntimeException('File fisik bukti tidak tersedia di server.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename($ev['proof_path']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    // 2. GENERATOR EKSPOR LAPORAN MULTI-FORMAT (PDF & XLSX)
    $type   = required_str('type');
    $format = strtolower((string)($_GET['format'] ?? 'pdf'));

    if (!in_array($format, ['pdf', 'xlsx'], true)) {
        throw new RuntimeException('Format ekspor yang diminta tidak didukung.');
    }

    $title   = 'Laporan Operasional CoCoLink';
    $headers = [];
    $data    = [];

    switch ($type) {
        case 'orders':
            $title   = 'Laporan Rekapitulasi Transaksi Pesanan B2B';
            $headers = ['Kode Order', 'Pembeli Industri', 'Kota Tujuan', 'Total Bayar', 'Status Order', 'Tanggal'];
            $rows    = rows("SELECT o.order_code, b.company_name, b.city, o.total, o.status, o.created_at 
                             FROM orders o 
                             JOIN buyer_profiles b ON b.id=o.buyer_id 
                             ORDER BY o.id DESC");
            foreach ($rows as $r) {
                $data[] = [
                    $r['order_code'],
                    $r['company_name'],
                    $r['city'],
                    money((float)$r['total']),
                    status_text($r['status']),
                    date('d/m/Y', strtotime($r['created_at']))
                ];
            }
            break;

        case 'batches':
            $title   = 'Laporan Rekapitulasi Pasokan Limbah Mentah';
            $headers = ['Kode Batch', 'PT Supplier', 'Jenis Limbah', 'Kuantitas (kg)', 'Catatan', 'Tanggal'];
            $rows    = rows("SELECT rb.batch_code, s.legal_name, rt.name AS raw_name, rb.quantity, rb.notes, rb.created_at 
                             FROM raw_batches rb 
                             JOIN suppliers s ON s.id=rb.supplier_id 
                             JOIN raw_types rt ON rt.id=rb.raw_type_id 
                             ORDER BY rb.id DESC");
            foreach ($rows as $r) {
                $data[] = [
                    $r['batch_code'],
                    $r['legal_name'],
                    $r['raw_name'],
                    number((float)$r['quantity']) . ' kg',
                    $r['notes'] ?: '-',
                    date('d/m/Y', strtotime($r['created_at']))
                ];
            }
            break;

        case 'products':
            $title   = 'Laporan Inventaris Produk Olahan Koperasi';
            $headers = ['SKU Produk', 'Nama Produk', 'Jenis Limbah', 'Koperasi Pengolah', 'Harga (Rp/kg)', 'Stok (kg)'];
            $rows    = rows("SELECT p.sku, p.name, rt.name AS raw_name, c.name AS coop_name, p.price, p.stock 
                             FROM products p 
                             JOIN raw_types rt ON rt.id=p.raw_type_id 
                             JOIN cooperatives c ON c.id=p.coop_id 
                             ORDER BY p.id DESC");
            foreach ($rows as $r) {
                $data[] = [
                    $r['sku'],
                    $r['name'],
                    $r['raw_name'],
                    $r['coop_name'],
                    money((float)$r['price']),
                    number((float)$r['stock']) . ' kg'
                ];
            }
            break;

        case 'shipments':
            $title   = 'Laporan Rekapitulasi Pengiriman & Logistik';
            $headers = ['No Resi', 'Kode Order', 'Driver Petugas', 'Kategori Rute', 'Status Pengiriman', 'Tujuan'];
            $rows    = rows("SELECT sh.id, o.order_code, u.name AS driver_name, u.driver_type, sh.status, b.company_name 
                             FROM shipments sh 
                             JOIN orders o ON o.id=sh.order_id 
                             JOIN buyer_profiles b ON b.id=o.buyer_id 
                             LEFT JOIN users u ON u.id=sh.driver_id 
                             ORDER BY sh.id DESC");
            foreach ($rows as $r) {
                $data[] = [
                    'RESI #' . $r['id'],
                    $r['order_code'],
                    $r['driver_name'] ?: 'Belum Ditugaskan',
                    strtoupper(str_replace('_', ' ', $r['driver_type'] ?? 'COOP TO BUYER')),
                    status_text($r['status']),
                    $r['company_name']
                ];
            }
            break;

        default:
            throw new RuntimeException('Kategori laporan tidak valid.');
    }

    $filename = $type . '_report_' . date('Ymd_His') . '.' . $format;

    if ($format === 'pdf') {
        export_pdf_table($title, $headers, $data, $filename);
    } else {
        export_xlsx($filename, $headers, $data);
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo '<div style="font-family:sans-serif; padding:2rem; color:#991B1B; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:10px; max-width:600px; margin:3rem auto;">';
    echo '<h3 style="margin-top:0;">Gagal Mengunduh Laporan</h3>';
    echo '<p>' . e($e->getMessage()) . '</p>';
    echo '<a href="javascript:history.back()" style="display:inline-block; padding:8px 16px; background:#DC2626; color:#FFF; text-decoration:none; border-radius:6px; font-weight:bold;">Kembali</a>';
    echo '</div>';
}