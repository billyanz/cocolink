<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error   = '';$name    = '';
$email   = '';$role    = 'buyer';
$phone   = '';$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password =$_POST['password'] ?? '';
    $role     =$_POST['role'] ?? 'buyer';
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Koperasi Induk dihapus dari pilihan registrasi publik
    $allowedRoles = ['buyer', 'child_coop', 'driver'];

    if (empty($name) \vert{}\vert{} empty($email) || empty($password) \vert{}\vert{} !in_array($role, $allowedRoles)) {$error = 'Semua kolom wajib diisi dengan benar.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {$error = 'Format email tidak valid.';
    } else {
        $db = (new Database())->getConnection();

        $stmtCheck =$db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);

        if ($stmtCheck->fetch()) {$error = 'Email sudah terdaftar.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmtInsert =$db->prepare("
                INSERT INTO users (name, email, password, role, phone, address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($stmtInsert->execute([$name, $email,$hashedPassword, $role,$phone, $address])) {$_SESSION['registered_success'] = 'Pendaftaran berhasil! Silakan masuk.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Gagal mendaftar. Terjadi kesalahan sistem.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoCoLink - Registrasi Akun</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4 my-8">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg border border-gray-200">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-black text-emerald-700 tracking-tight">CoCoLink</h1>
            <p class="text-sm text-gray-500 mt-1">Pendaftaran Pengguna Baru</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-5 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap / Instansi</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" placeholder="PT. X Industri / Koperasi A1">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" placeholder="nama@perusahaan.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" placeholder="••••••••">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Peran Pengguna (Aktor)</label>
                <select id="role" name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                    <option value="buyer" <?= $role === 'buyer' ? 'selected' : '' ?>>Pengepul Industri / Buyer (PT. X, Y, Z)</option>
                    <option value="child_coop" <?= $role === 'child_coop' ? 'selected' : '' ?>>Koperasi Anak (Supplier Pasokan)</option>
                    <option value="driver" <?= $role === 'driver' ? 'selected' : '' ?>>Driver Logistik / Transportasi</option>
                </select>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP/WhatsApp</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" placeholder="081234567890">
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                <textarea id="address" name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" placeholder="Alamat lengkap lokasi/pabrik..."><?= htmlspecialchars($address) ?></textarea>
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition duration-200 shadow-md text-sm">
                Daftar Akun
            </button>
        </form>

        <div class="mt-5 text-center text-sm text-gray-600">
            Sudah memiliki akun? <a href="login.php" class="text-emerald-700 font-semibold hover:underline">Masuk di sini</a>
        </div>
    </div>
</body>
</html>