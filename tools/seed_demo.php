<?php
/**
 * Seeder Data Demo CoCoLink
 * Jalankan via CLI: C:\xampp\php\php.exe tools/seed_demo.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya dapat dijalankan melalui CLI Terminal.\n");
}

require __DIR__ . '/../app/bootstrap.php';

// Cek apakah database sudah berisi data pengguna
if ((int)val('SELECT COUNT(*) FROM users') > 0) {
    echo "⚠️ Database cocolink sudah memiliki data pengguna!\n";
    echo "Gunakan database kosong jika ingin mengatur ulang data demo.\n";
    exit(1);
}

$pwdStrong = 'CocoLink!2026'; // Khusus Super Admin
$pwdSimple = '123456';        // Untuk semua akun demo lainnya

$hashStrong = password_hash($pwdStrong, PASSWORD_DEFAULT);
$hashSimple = password_hash($pwdSimple, PASSWORD_DEFAULT);

db()->beginTransaction();

try {
    // Helper function untuk membuat akun user
    $makeUser = function (string $name, string $email, string $role, string $hash, ?int $sid = null, ?int $cid = null, ?int $bid = null): int {
        sql('INSERT INTO users(name,email,password_hash,role,supplier_id,coop_id,buyer_id) VALUES (?,?,?,?,?,?,?)', [
            $name, $email, $hash, $role, $sid, $cid, $bid
        ]);
        return (int)db()->lastInsertId();
    };

    echo "📦 1. Membuat akun pengguna dan entitas mitra...\n";

    // 1. Akun Super Admin & Koperasi Induk
    $admin = $makeUser('Super Admin CoCoLink', 'admin@cocolink.test', 'super_admin', $hashStrong);
    $induk = $makeUser('Admin Koperasi Induk', 'induk@cocolink.test', 'induk', $hashSimple);

    // 2. PT Pemasok Limbah Utama (Verified)
    sql("INSERT INTO suppliers(legal_name, nib, province, city, address, latitude, longitude, status, verified_by, verified_at) 
         VALUES ('PT Demo Nusantara Kelapa', 'DEMO-NIB-999', 'Jawa Barat', 'Bandung', 'Jl. Sukajadi No. 102, Bandung', -6.8915000, 107.5981000, 'verified', ?, NOW())", [$induk]);
    $sid = (int)db()->lastInsertId();
    $supplierUser = $makeUser('Manajer PT Demo', 'pt@cocolink.test', 'supplier', $hashSimple, $sid);

    // 3. 5 Koperasi Anak Wilayah
    $coopNames = [
        'Koperasi Hijau Daun (Bandung)',
        'Koperasi Arang Tempurung (Cimahi)',
        'Koperasi Sabut Sirkular (Sumedang)',
        'Koperasi Kayu Kelapa (Garut)',
        'Koperasi Olahan Air (Subang)'
    ];
    $cities = ['Bandung', 'Cimahi', 'Sumedang', 'Garut', 'Subang'];
    $coopIds = [];

    foreach ($coopNames as $i => $name) {
        $rawTypeId = $i + 1; // 1: Daun, 2: Tempurung, 3: Sabut, 4: Batang, 5: Air/Lainnya
        sql("INSERT INTO cooperatives(supplier_id, name, province, city, address, specialty_raw_type_id, status, verified_by) 
             VALUES (?, ?, 'Jawa Barat', ?, ?, ?, 'verified', ?)", [
            $sid, $name, $cities[$i], 'Gudang Penampungan & Pickup ' . $cities[$i], $rawTypeId, $induk
        ]);
        $coopIds[] = (int)db()->lastInsertId();
    }

    // Akun Pengelola Koperasi Anak #1 (Koperasi Hijau Daun - Spesialis Daun)
    $coopUser = $makeUser('Ketua Koperasi Daun', 'anak@cocolink.test', 'coop', $hashSimple, null, $coopIds[0]);

    // 4. Pembeli Industri B2B (Verified)
    sql("INSERT INTO buyer_profiles(company_name, buyer_type, nib, address, city, status) 
         VALUES ('PT Industri Ekosistem Hijau', 'industry', 'DEMO-BUY-888', 'Kawasan Industri Pulogadung, Jakarta Timur', 'Jakarta', 'verified')");
    $bid = (int)db()->lastInsertId();
    $buyerUser = $makeUser('Purchasing Manufaktur', 'buyer@cocolink.test', 'buyer', $hashSimple, null, null, $bid);

    // 5. Driver Logistik
    $driverUser = $makeUser('Budi Logistik Driver', 'driver@cocolink.test', 'driver', $hashSimple);

    echo "⚙️ 2. Mensimulasikan alur bisnis pasokan limbah hingga produksi...\n";

    // 6. PT Pemasok mencatat Pasokan Limbah Mentah (Batch)
    sql("INSERT INTO raw_batches(supplier_id, raw_type_id, batch_code, quantity, unit, notes) 
         VALUES (?, 1, 'RAW-DEMO-001', 1000.00, 'kg', 'Pasokan Daun Kelapa Segar dari Perkebunan')", [$sid]);
    $rawId = (int)db()->lastInsertId();

    // 7. Koperasi Induk Mengalokasikan Limbah ke Koperasi Anak (Koperasi Hijau Daun)
    sql("INSERT INTO allocations(batch_id, coop_id, quantity, status, created_by) 
         VALUES (?, ?, 500.00, 'accepted', ?)", [$rawId, $coopIds[0], $induk]);
    $allocId = (int)db()->lastInsertId();

    // 8. Koperasi Anak Mendaftarkan Katalogue Produk Olahan
    sql("INSERT INTO products(coop_id, raw_type_id, sku, name, description, unit, price, stock, is_active) 
         VALUES (?, 1, 'SAPU-LIDI-01', 'Sapu Lidi Kelapa Export Grade', 'Olahan lidi daun kelapa tebal dan bersih', 'kg', 15000.00, 0, 1)", [$coopIds[0]]);
    $productId = (int)db()->lastInsertId();

    // 9. Koperasi Anak Mencatat Konversi Produksi (Input Limbah -> Output Produk Jadi)
    sql("INSERT INTO production_logs(allocation_id, product_id, input_qty, output_qty, notes, created_by) 
         VALUES (?, ?, 400.00, 300.00, 'Konversi 400 kg daun kelapa menjadi 300 kg sapu lidi premium', ?)", [
        $allocId, $productId, $coopUser
    ]);
    sql("UPDATE products SET stock=300.00 WHERE id=?", [$productId]);

    echo "🚚 3. Mensimulasikan Transaksi Pesanan & Logistik Pengiriman SAMPAI SELESAI...\n";

    // 10. Pembeli Industri Memesan Produk (50 kg @ Rp 15.000 = Rp 750.000)
    $orderCode = 'ORD-DEMO-FINISHED';
    $totalAmount = 750000.00;

    sql("INSERT INTO orders(order_code, buyer_id, shipping_address, city, notes, status, total, approved_by) 
         VALUES (?, ?, 'Kawasan Industri Pulogadung Block C4, Jakarta Timur', 'Jakarta', 'Pengiriman mendesak untuk ekspor', 'completed', ?, ?)", [
        $orderCode, $bid, $totalAmount, $induk
    ]);
    $orderId = (int)db()->lastInsertId();

    // Detail Item Pesanan & Pengurangan Stok
    sql("INSERT INTO order_items(order_id, product_id, quantity, unit_price) VALUES (?, ?, 50.00, 15000.00)", [$orderId, $productId]);
    sql("UPDATE products SET stock=stock-50.00 WHERE id=?", [$productId]);

    // 11. Pengiriman Logistik (Shipment) Berstatus Delivered
    sql("INSERT INTO shipments(order_id, driver_id, status, assigned_at, picked_up_at, delivered_at) 
         VALUES (?, ?, 'delivered', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, NOW())", [
        $orderId, $driverUser
    ]);
    $shipmentId = (int)db()->lastInsertId();

    // 12. Rekam Peristiwa Timeline Logistik (Events)
    sql("INSERT INTO shipment_events(shipment_id, event_type, note, actor_id, created_at) 
         VALUES (?, 'assigned', 'Driver ditugaskan oleh Koperasi Induk', ?, NOW() - INTERVAL 2 DAY)", [$shipmentId, $induk]);

    sql("INSERT INTO shipment_events(shipment_id, event_type, note, actor_id, created_at) 
         VALUES (?, 'accepted', 'Driver siap mengambil barang di Gudang Koperasi', ?, NOW() - INTERVAL 42 HOUR)", [$shipmentId, $driverUser]);

    sql("INSERT INTO shipment_events(shipment_id, event_type, note, actor_id, created_at) 
         VALUES (?, 'picked_up', 'Persetujuan ACC Pickup oleh Koperasi Anak (Muatan 50 kg Sapu Lidi)', ?, NOW() - INTERVAL 24 HOUR)", [$shipmentId, $coopUser]);

    sql("INSERT INTO shipment_events(shipment_id, event_type, note, latitude, longitude, actor_id, created_at) 
         VALUES (?, 'location', 'Dalam perjalanan di Tol Cikampek KM 42', -6.3412000, 107.1523000, ?, NOW() - INTERVAL 5 HOUR)", [$shipmentId, $driverUser]);

    sql("INSERT INTO shipment_events(shipment_id, event_type, note, latitude, longitude, actor_id, created_at) 
         VALUES (?, 'delivered', 'Barang telah diserahterimakan ke tim Gudang Industri.', -6.1923000, 106.9123000, ?, NOW())", [$shipmentId, $driverUser]);

    // Audit Log
    audit($admin, 'seed_demo', 'system', null, 'Inisialisasi Data Demo Transaksi Selesai CoCoLink');

    db()->commit();

    echo " SUCCESS! Seed Data Demo Berhasil Dipasang.\n";
    echo "=================================================================\n";
    echo " KREDENSIAL LOGIN AKUN DEMO:\n";
    echo "=================================================================\n";
    echo " 1. Super Admin Platform : admin@cocolink.test  [ Password: {$pwdStrong} ]\n";
    echo " 2. Admin Koperasi Induk: induk@cocolink.test  [ Password: {$pwdSimple} ]\n";
    echo " 3. PT Pemasok Limbah    : pt@cocolink.test     [ Password: {$pwdSimple} ]\n";
    echo " 4. Koperasi Anak        : anak@cocolink.test   [ Password: {$pwdSimple} ]\n";
    echo " 5. Pembeli Industri     : buyer@cocolink.test  [ Password: {$pwdSimple} ]\n";
    echo " 6. Driver Logistik      : driver@cocolink.test [ Password: {$pwdSimple} ]\n";
    echo "=================================================================\n";

} catch (Throwable $ex) {
    db()->rollBack();
    fwrite(STDERR, "❌ Error Seed Data: " . $ex->getMessage() . "\n");
    exit(1);
}