<?php
// public/register.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF verification failed.";
    } else {
        $name     = sanitizeInput($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'buyer';
        $phone    = sanitizeInput($_POST['phone'] ?? '');

        if (!$name || !$email || strlen($password) < 6) {
            $errors[] = "Please enter a valid name, email, and minimum 6-character password.";
        }

        if (!in_array($role, ['buyer', 'driver'])) {
            $errors[] = "Self-registration is restricted to Buyers and Drivers.";
        }

        if (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "This email address is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hash, $role, $phone])) {
                    logAudit($pdo, $pdo->lastInsertId(), 'REGISTER', "Registered new {$role}: {$email}");
                    $success = "Registration completed! You may now <a href='login.php' class='underline text-emerald-600 font-bold'>log in</a>.";
                } else {
                    $errors[] = "Failed to create account.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CoCoLink - Register Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-200">
        <div class="flex items-center space-x-3 justify-center mb-6">
            <span class="text-3xl">🌴</span>
            <h1 class="text-2xl font-bold text-slate-800">CoCoLink Registration</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm border border-red-200">
                <?php foreach ($errors as $e) echo "<div>• {$e}</div>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-50 text-emerald-700 p-3 rounded-lg mb-4 text-sm border border-emerald-200">
                <?= $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Company / User Name</label>
                <input type="text" name="name" required class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Phone Number</label>
                <input type="text" name="phone" class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Account Role</label>
                <select name="role" class="w-full border border-slate-300 p-3 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="buyer">Industrial Buyer (Offtaker)</option>
                    <option value="driver">Logistics Driver</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-lg text-sm transition shadow-md shadow-emerald-900/20">
                Create Account
            </button>
        </form>
        <p class="text-xs text-center text-slate-500 mt-4">
            Already have an account? <a href="login.php" class="text-emerald-600 font-semibold">Sign In</a>
        </p>
    </div>
</body>
</html>