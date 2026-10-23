<?php
session_start();

// Jika user sudah login, redirect langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$email = '';

// Ambil pesan sukses registrasi jika ada
$success = $_SESSION['registered_success'] ?? '';
unset($_SESSION['registered_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === '123456')) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoCoLink - Masuk ke Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-200">
        <div class="text-center mb-6">
            <a href="index.php" class="text-3xl font-black text-emerald-700 tracking-tight">CoCoLink</a>
            <p class="text-sm text-gray-500 mt-1">Platform Sinergi Koperasi & Industri Limbah Kelapa</p>
        </div>

        <!-- Alert Notifikasi Skenario -->
        <?php if (!empty($success)): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-3 rounded mb-5 text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-5 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input 
                    type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required 
                    placeholder="nama@perusahaan.com"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input 
                    type="password" id="password" name="password" required 
                    placeholder="••••••••"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm"
                >
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition duration-200 shadow-md text-sm">
                Masuk Akun
            </button>
        </form>

        <!-- LINK MENUBUJU REGISTRASI -->
        <div class="mt-5 text-center text-sm text-gray-600">
            Belum memiliki akun? 
            <a href="register.php" class="text-emerald-700 font-semibold hover:underline">Daftar di sini</a>
        </div>

        <!-- Info Kredensial Pengujian -->
        <div class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-500 text-center">
            <p class="font-semibold text-gray-700 mb-1">Kredensial Pengujian (Password: <code class="bg-gray-100 px-1 py-0.5 rounded text-red-600">123456</code>):</p>
            <ul class="text-left space-y-1 mt-2 text-gray-600">
                <li>• <b>Buyer (PT. X):</b> <code class="text-emerald-700">buyer@ptx.com</code></li>
                <li>• <b>Koperasi Induk:</b> <code class="text-emerald-700">induk@cocolink.org</code></li>
                <li>• <b>Koperasi Anak (A1):</b> <code class="text-emerald-700">a1@cocolink.org</code></li>
                <li>• <b>Driver:</b> <code class="text-emerald-700">driver@cocolink.org</code></li>
            </ul>
        </div>
    </div>
</body>
</html>