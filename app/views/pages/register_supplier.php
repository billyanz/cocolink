<?php
// Handler Pendaftaran PT Induk / Supplier Utama
function handle_register_supplier(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $db = get_db_connection(); // Fungsi koneksi PDO database Anda

    $legal_name  = trim($_POST['legal_name'] ?? '');
    $nib         = trim($_POST['nib'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $province    = trim($_POST['province'] ?? 'DKI Jakarta');
    $password    = $_POST['password'] ?? '';

    // Validasi specialty_raw_type_id: Jika dikosongkan/invalid, diset NULL agar FK tidak error
    $raw_type_id = (!empty($_POST['specialty_raw_type_id']) && intval($_POST['specialty_raw_type_id']) > 0) 
                   ? intval($_POST['specialty_raw_type_id']) 
                   : null;

    if (empty($legal_name) || empty($email) || empty($password)) {
        $_SESSION['flash_error'] = "Nama Perusahaan, Email, dan Password wajib diisi!";
        header("Location: index.php?page=register_supplier");
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Insert Data Supplier (PT Induk)
        $stmtSupplier = $db->prepare("INSERT INTO suppliers (legal_name, nib, email, phone, address, city, province, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 'verified')");
        $stmtSupplier->execute([$legal_name, $nib, $email, $phone, $address, $city, $province]);
        $supplier_id = $db->lastInsertId();

        // 2. Buat Koperasi Utama bawaan dari PT Induk (Tabel cooperatives TIDAK ADA kolom province)
        $stmtCoop = $db->prepare("INSERT INTO cooperatives (supplier_id, name, city, address, specialty_raw_type_id, status) 
                                  VALUES (?, ?, ?, ?, ?, 'verified')");
        $stmtCoop->execute([$supplier_id, $legal_name . ' (Pusat)', $city, $address, $raw_type_id]);
        $coop_id = $db->lastInsertId();

        // 3. Buat Akun User Login (Role: induk)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmtUser = $db->prepare("INSERT INTO users (name, email, password, role, supplier_id, coop_id, is_active) 
                                  VALUES (?, ?, ?, 'induk', ?, ?, 1)");
        $stmtUser->execute([$legal_name, $email, $hashed_password, $supplier_id, $coop_id]);

        $db->commit();
        $_SESSION['flash_success'] = "Pendaftaran PT Induk Berhasil! Silakan Login.";
        header("Location: index.php?page=login");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['flash_error'] = "Gagal mendaftar: " . $e->getMessage();
        header("Location: index.php?page=register_supplier");
        exit;
    }
}
?>