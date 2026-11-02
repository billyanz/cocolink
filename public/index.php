<?php
// public/index.php
require_once __DIR__ . '/../config/auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CoCoLink - Coconut Waste Supply Chain Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col font-sans">
    <nav class="border-b border-slate-800 bg-slate-950/80 backdrop-blur fixed w-full z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">🌴</span>
            <span class="text-xl font-bold tracking-tight text-emerald-400">CoCoLink</span>
        </div>
        <div>
            <?php if ($user): ?>
                <a href="dashboard.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">Go to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="text-slate-300 hover:text-white px-4 py-2 text-sm font-medium">Log In</a>
                <a href="register.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center pt-24 px-6 text-center">
        <div class="max-w-3xl">
            <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full text-xs font-semibold uppercase tracking-wider">Industrial Supply Chain Solution</span>
            <h1 class="text-4xl sm:text-6xl font-extrabold mt-6 tracking-tight text-white leading-tight">
                Empowering Indonesia's Coconut Waste Ecosystem
            </h1>
            <p class="mt-6 text-slate-400 text-lg leading-relaxed">
                Seamless multi-cooperative inventory aggregation, split order allocations, scale weighing validation, driver dispatching, and buyer delivery guarantees.
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="register.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-900/30 transition">Get Started</a>
                <a href="login.php" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-6 py-3 rounded-xl font-bold border border-slate-700 transition">Demo Login</a>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y'); ?> CoCoLink Supply Chain. All rights reserved.
    </footer>
</body>
</html>