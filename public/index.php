<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika pengguna sudah dalam keadaan login, alihkan langsung ke Dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoCoLink - Platform Sinergi Koperasi & Industri Limbah Kelapa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <!-- Header & Navigasi -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <a href="index.php" class="text-2xl font-black text-emerald-700 tracking-wider flex items-center gap-2">
                    <svg class="w-8 h-8 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    CoCoLink
                </a>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="login.php" class="text-sm font-semibold text-gray-700 hover:text-emerald-700 px-4 py-2 rounded-lg transition">
                    Masuk
                </a>
                <a href="register.php" class="text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg shadow-md transition duration-200">
                    Daftar Akun
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-emerald-900 to-emerald-800 text-white py-20 px-6">
        <div class="max-w-5xl mx-auto text-center space-y-6">
            <span class="bg-emerald-700 text-emerald-100 text-xs uppercase font-bold px-3 py-1.5 rounded-full tracking-wide">
                Ekosistem Digital Limbah Kelapa
            </span>
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
                Sinergi Transparan Antara Industri & Koperasi Limbah Kelapa
            </h1>
            <p class="text-emerald-100 text-lg md:text-xl max-w-3xl mx-auto font-light">
                Menghubungkan Pengepul Industri, Koperasi Induk, Koperasi Anak, dan Logistik Armada secara terintegrasi dan real-time.
            </p>
            
            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold px-8 py-3.5 rounded-xl shadow-lg transition duration-200 text-base">
                    Bergabung Sekarang
                </a>
                <a href="login.php" class="bg-emerald-700 hover:bg-emerald-600 text-white font-semibold px-8 py-3.5 rounded-xl border border-emerald-500 transition duration-200 text-base">
                    Masuk ke Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Alur Kerja Ekosistem (4 Aktor Utama) -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Empat Pilar Utama CoCoLink</h2>
            <p class="text-gray-500 mt-2">Mekanisme kerja terintegrasi yang adil, terbuka, dan efisien.</p>
        </div>

        <div class="grid md:grid-cols-4 gap-8">
            <!-- Card 1: Pengepul -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                    1
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Pengepul Industri</h3>
                <p class="text-sm text-gray-600">
                    Perusahaan (PT. X, Y, Z) mendaftar dan memesan kebutuhan pasokan limbah kelapa (arang batok, sabut, minyak).
                </p>
            </div>

            <!-- Card 2: Koperasi Induk -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                    2
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Koperasi Induk</h3>
                <p class="text-sm text-gray-600">
                    Memverifikasi pesanan masuk dan membagi alokasi kuota target pasokan ke beberapa Koperasi Anak.
                </p>
            </div>

            <!-- Card 3: Koperasi Anak -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                    3
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Koperasi Anak</h3>
                <p class="text-sm text-gray-600">
                    Menerima pembagian kuota (misal: A1 30 Ton & A2 20 Ton) dan menyiapkan produk sesuai spesifikasi.
                </p>
            </div>

            <!-- Card 4: Driver Logistik -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                    4
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Driver Logistik</h3>
                <p class="text-sm text-gray-600">
                    Mengambil muatan dari Koperasi Anak dan mengantarkan ke lokasi pabrik pengepul secara terlacaks.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-6 text-center text-sm">
            <p>&copy; <?= date('Y') ?> CoCoLink. Platform Sinergi Koperasi & Industri Limbah Kelapa.</p>
        </div>
    </footer>

</body>
</html>