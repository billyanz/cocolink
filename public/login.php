<?php
// public/login.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT u.*, c.name as coop_name FROM users u LEFT JOIN cooperatives c ON u.cooperative_id = c.id WHERE u.email = ? AND u.status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                session_regenerate_id(true);
                $_SESSION['user'] = $user;
                logAudit($pdo, $user['id'], 'LOGIN', "User logged in: {$user['email']}");
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid credentials provided.";
            }
        } else {
            $error = "Please fill in all input fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CoCoLink - Account Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-200">
        <div class="flex items-center space-x-3 justify-center mb-6">
            <span class="text-3xl">🌴</span>
            <h1 class="text-2xl font-bold text-slate-800">CoCoLink Portal</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm border border-red-200">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email Address</label>
                <input type="email" name="email" required value="induk@cocolink.id" class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required value="password123" class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-lg text-sm transition shadow-md shadow-emerald-900/20">
                Sign In to Platform
            </button>
        </form>

        <div class="mt-6 border-t pt-4 text-xs text-slate-500">
            <p class="font-bold text-slate-700 mb-2">Demo Accounts (Password: <code class="bg-slate-100 px-1 py-0.5 rounded text-emerald-700">password123</code>):</p>
            <div class="grid grid-cols-2 gap-1 text-slate-600">
                <div>• Central Induk:</div><div class="font-semibold text-slate-800">induk@cocolink.id</div>
                <div>• Coop Bogor A1:</div><div class="font-semibold text-slate-800">bogor@cocolink.id</div>
                <div>• Coop Ciamis A2:</div><div class="font-semibold text-slate-800">ciamis@cocolink.id</div>
                <div>• Industrial Buyer:</div><div class="font-semibold text-slate-800">buyer@industri.com</div>
                <div>• Logistics Driver:</div><div class="font-semibold text-slate-800">driver@cocolink.id</div>
            </div>
        </div>
    </div>
</body>
</html>