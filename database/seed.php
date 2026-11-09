<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya dapat dijalankan melalui CLI Terminal.\n");
}

require __DIR__ . '/../app/bootstrap.php';

try {
    $pdo = db();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // TRUNCATE SELURUH TABEL TRANSAKSI & MASTER
    $tables = ['shipment_events', 'shipments', 'order_items', 'orders', 'production_logs', 'products', 'allocations', 'raw_batches', 'cooperatives', 'buyer_profiles', 'suppliers', 'users', 'audit_logs'];
    foreach ($tables as $tbl) {
        $pdo->exec("TRUNCATE TABLE `$tbl`;");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $passHash = password_hash('123456', PASSWORD_DEFAULT);
    $adminHash = password_hash('CocoLink!2026', PASSWORD_DEFAULT);

    // 1. KOPERASI INDUK METROPOLITAN (SUPERVISI JABODETABEK)
    sql("INSERT INTO users (id, name, email, password_hash, role, is_active) VALUES 
         (1, 'Super Admin CoCoLink', 'admin@cocolink.test', ?, 'super_admin', 1),
         (2, 'Koperasi Induk Jabodetabek', 'induk@cocolink.test', ?, 'induk', 1)", [$adminHash, $passHash]);

    // 2. PT PEMASOK UTAMA REGIONAL (JAKARTA)
    sql("INSERT INTO suppliers (id, legal_name, nib, province, city, address, latitude, longitude, status, verified_at) VALUES 
         (1, 'PT Kelapa Mandiri Jabodetabek', 'NIB-JABODE-999', 'DKI Jakarta', 'Jakarta', 'Kawasan Industri Pulo Gadung No. 88, Jakarta Timur', -6.1923000, 106.9123000, 'verified', NOW())");

    sql("INSERT INTO users (id, name, email, password_hash, role, supplier_id, is_active) VALUES 
         (3, 'Rajsfano (PT Pemasok Utama)', 'pt@cocolink.test', ?, 'supplier', 1, 1)", [$passHash]);

    // 3. 5 KOPERASI ANAK SEKTOR WILAYAH (JABODETABEK)
    $coops = [
        [1, 1, 'Koperasi Sektor Jakarta Timur', 'DKI Jakarta', 'Jakarta Timur', 'Jl. Raya Industri Pulogadung No. 12', -6.1923000, 106.9123000, 1, 'verified'],
        [2, 1, 'Koperasi Sektor Jakarta Selatan', 'DKI Jakarta', 'Jakarta Selatan', 'Jl. TB Simatupang Gudang 4', -6.2915000, 106.8200000, 2, 'verified'],
        [3, 1, 'Koperasi Sektor Jakarta Barat', 'DKI Jakarta', 'Jakarta Barat', 'Jl. Daan Mogot KM 10', -6.1600000, 106.7400000, 3, 'verified'],
        [4, 1, 'Koperasi Sektor Jakarta Pusat', 'DKI Jakarta', 'Jakarta Pusat', 'Jl. Kemayoran Gempol No. 5', -6.1700000, 106.8400000, 4, 'verified'],
        [5, 1, 'Koperasi Sektor Jakarta Utara', 'DKI Jakarta', 'Jakarta Utara', 'Jl. Pelabuhan Tanjung Priok Gudang B', -6.1100000, 106.8800000, 5, 'verified'],
    ];

    foreach ($coops as $c) {
        sql("INSERT INTO cooperatives (id, supplier_id, name, province, city, address, latitude, longitude, specialty_raw_type_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $c);
    }

    // UNTUK KOPERASI SEKTOR JAKTIM (CTCLINK / ANAK)
    sql("INSERT INTO users (id, name, email, password_hash, role, supplier_id, coop_id, is_active) VALUES 
         (4, 'Ketua Koperasi Sektor JakTim', 'anak@cocolink.test', ?, 'coop', 1, 1, 1),
         (5, 'PT. Cipta Teknologi Cendekia', 'ctc@gmail.com', ?, 'coop', 1, 1, 1)", [$passHash, $passHash]);

    // 4. BUYER INDUSTRI & DRIVER ARMADA
    sql("INSERT INTO buyer_profiles (id, company_name, buyer_type, nib, address, city, status) VALUES 
         (1, 'PT Manufaktur Briket & Serat Indonesia', 'industry', 'BUYER-NIB-888', 'Kawasan Industri Jababeka 2, Cikarang', 'Bekasi', 'verified')");

    sql("INSERT INTO users (id, name, email, password_hash, role, buyer_id, is_active) VALUES 
         (6, 'Purchasing Manufaktur B2B', 'buyer@cocolink.test', ?, 'buyer', 1, 1)", [$passHash]);

    sql("INSERT INTO users (id, name, email, password_hash, role, is_active) VALUES 
         (7, 'Budi Armada Logistik', 'driver@cocolink.test', ?, 'driver', 1)", [$passHash]);

    // 5. SEED BATCH PASOKAN LIMBAH & PRODUCT OLAHAN INITIAL
    sql("INSERT INTO raw_batches (id, supplier_id, raw_type_id, batch_code, quantity, notes) VALUES 
         (1, 1, 1, 'RAW-JABODETABEK-001', 1000.00, 'Pasokan Daun Kelapa & Lidi Segar Hulu Jabodetabek')");

    sql("INSERT INTO allocations (id, batch_id, coop_id, quantity, status, created_by) VALUES 
         (1, 1, 1, 500.00, 'accepted', 3)");

    sql("INSERT INTO products (id, coop_id, raw_type_id, sku, name, description, unit, price, stock, is_active) VALUES 
         (1, 1, 1, 'SAPU-LIDI-JAKTIM', 'Sapu Lidi Kelapa Export Grade A', 'Lidi kelapa murni olahan Koperasi Sektor JakTim', 'kg', 15000.00, 300.00, 1)");

    echo "<h2 style='color:green; font-family:sans-serif;'>SUCCESS! Seed Data Ekosistem Jabodetabek Berhasil Dipasang.</h2>";
    echo "<hr><pre style='font-size:1rem; font-family:monospace;'>";
    echo "=========================================================\n";
    echo " KREDENSIAL LOGIN AKUN DEMO JABODETABEK:\n";
    echo "=========================================================\n";
    echo "1. Super Admin Platform      : admin@cocolink.test    [ Pass: CocoLink!2026 ]\n";
    echo "2. Admin Koperasi Induk      : induk@cocolink.test    [ Pass: 123456 ]\n";
    echo "3. PT Pemasok Utama          : pt@cocolink.test       [ Pass: 123456 ]\n";
    echo "4. Koperasi Anak (Sektor)    : anak@cocolink.test    [ Pass: 123456 ]\n";
    echo "5. Koperasi Anak (CTC)       : ctc@gmail.com          [ Pass: 123456 ]\n";
    echo "6. Pembeli Industri B2B      : buyer@cocolink.test    [ Pass: 123456 ]\n";
    echo "7. Driver Armada Logistik    : driver@cocolink.test   [ Pass: 123456 ]\n";
    echo "=========================================================\n";
    echo "</pre>";

} catch (Throwable $e) {
    echo "<h2 style='color:red;'>ERROR SEEDING: " . e($e->getMessage()) . "</h2>";
}