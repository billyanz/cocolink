-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Nov 2026 pada 08.03
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cocolink`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `allocations`
--

CREATE TABLE `allocations` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `coop_id` int(11) NOT NULL,
  `quantity` decimal(12,2) DEFAULT 0.00,
  `status` enum('proposed','accepted','rejected') DEFAULT 'proposed',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `allocations`
--

INSERT INTO `allocations` (`id`, `batch_id`, `coop_id`, `quantity`, `status`, `created_at`) VALUES
(1, 1, 1, 50.00, 'accepted', '2026-11-06 15:58:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 15:31:22'),
(2, 1, 'create_batch', 'raw_batches', 1, 'Input batch pasokan limbah baru', '::1', '2026-11-06 15:58:13'),
(3, 1, 'allocate', 'allocations', 1, 'Alokasi bahan mentah ke Koperasi Anak Sektor Wilayah', '::1', '2026-11-06 15:58:24'),
(4, 1, 'add_replacement_coop', 'cooperatives', 6, 'Penambahan Koperasi Anak Sektor pengganti', '::1', '2026-11-06 16:08:44'),
(5, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-06 16:58:31'),
(6, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 16:58:38'),
(7, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-06 17:05:52'),
(8, 2, 'login', 'users', 2, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 17:05:57'),
(9, 2, 'logout', 'users', 2, 'Pengguna keluar sistem', '::1', '2026-11-06 17:08:58'),
(10, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 17:09:08'),
(11, 1, 'create_user', 'users', 7, 'Pembuatan akun baru peran coop', '::1', '2026-11-06 17:10:41'),
(12, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-06 17:11:03'),
(13, 7, 'login', 'users', 7, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 17:11:06'),
(14, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:17'),
(15, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:22'),
(16, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:22'),
(17, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:23'),
(18, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:23'),
(19, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:23'),
(20, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:23'),
(21, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:23'),
(22, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:24'),
(23, 7, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:11:24'),
(24, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-06 17:12:37'),
(25, 4, 'login', 'users', 4, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-06 17:12:46'),
(26, 4, 'respond_allocation', 'allocations', 1, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-06 17:13:06'),
(27, 1, 'create_batch', 'raw_batches', 2, 'Input batch pasokan limbah baru', '::1', '2026-11-06 17:14:50'),
(28, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-09 10:50:59'),
(29, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-09 13:14:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `nib` varchar(100) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `buyer_profiles`
--

INSERT INTO `buyer_profiles` (`id`, `company_name`, `nib`, `email`, `phone`, `address`, `city`, `created_at`) VALUES
(1, 'PT Eco Briket Indonesia', '8120001122334', 'buyer.briket@cocolink.test', '081122334455', 'Kawasan Industri Pulogadung', 'Jakarta', '2026-11-03 14:44:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cooperatives`
--

CREATE TABLE `cooperatives` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'verified',
  `specialty_raw_type_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cooperatives`
--

INSERT INTO `cooperatives` (`id`, `supplier_id`, `name`, `city`, `address`, `status`, `specialty_raw_type_id`, `latitude`, `longitude`, `created_at`) VALUES
(1, 1, 'Koperasi Anak Sektor Bogor', 'Bogor', 'Jl. Pajajaran No. 12', 'verified', 1, -6.59710000, 106.79960000, '2026-11-03 14:44:13'),
(2, 1, 'Koperasi Anak Sektor Depok', 'Depok', 'Jl. Margonda Raya No. 88', 'verified', 2, -6.40250000, 106.79420000, '2026-11-03 14:44:13'),
(3, 1, 'Koperasi Anak Sektor Tangerang', 'Tangerang', 'Jl. Daan Mogot No. 34', 'verified', 1, -6.17830000, 106.63190000, '2026-11-03 14:44:13'),
(4, 1, 'Koperasi Anak Sektor Bekasi', 'Bekasi', 'Jl. Ahmad Yani No. 101', 'verified', 3, -6.23830000, 106.97560000, '2026-11-03 14:44:13'),
(5, 1, 'Koperasi Anak Sektor Jakarta Timur', 'Jakarta Timur', 'Jl. Pemuda No. 55', 'verified', 2, -6.22500000, 106.90000000, '2026-11-03 14:44:13'),
(6, 2, 'tes1', 'tes1', 'tes1', 'verified', 2, -6.20000000, 106.81666660, '2026-11-06 16:08:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(100) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total` decimal(15,2) DEFAULT 0.00,
  `status` enum('requested','approved','assigned','in_transit','delivered','completed','rejected') DEFAULT 'requested',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_logs`
--

CREATE TABLE `production_logs` (
  `id` int(11) NOT NULL,
  `allocation_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `input_qty` decimal(12,2) DEFAULT 0.00,
  `output_qty` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `coop_id` int(11) NOT NULL,
  `raw_type_id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) DEFAULT 0.00,
  `stock` decimal(12,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_batches`
--

CREATE TABLE `raw_batches` (
  `id` int(11) NOT NULL,
  `batch_code` varchar(100) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `raw_type_id` int(11) NOT NULL,
  `quantity` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `raw_batches`
--

INSERT INTO `raw_batches` (`id`, `batch_code`, `supplier_id`, `raw_type_id`, `quantity`, `notes`, `created_at`) VALUES
(1, 'RAW-261106-7BBB7AD1', 1, 3, 100.00, 'limbah tipis', '2026-11-06 15:58:13'),
(2, 'RAW-261106-9DAA3D72', 1, 3, 400.00, 'tes1', '2026-11-06 17:14:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_types`
--

CREATE TABLE `raw_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `raw_types`
--

INSERT INTO `raw_types` (`id`, `name`, `code`) VALUES
(1, 'Sabut Kelapa (Coco Coir)', 'RAW-01'),
(2, 'Tempurung Kelapa (Shell)', 'RAW-02'),
(3, 'Air & Ampas Kelapa', 'RAW-03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'assigned',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `legal_name` varchar(255) NOT NULL,
  `nib` varchar(100) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'verified',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `legal_name`, `nib`, `email`, `phone`, `address`, `city`, `province`, `status`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'Koperasi Induk Metropolitan Jabodetabek', '9120003410012', 'induk.jabodetabek@cocolink.test', '081299887766', 'Jl. Industri Raya No. 45', 'Jakarta', 'DKI Jakarta', 'verified', -6.20000000, 106.81666700, '2026-11-03 14:44:13'),
(2, 'PT Rajs Hydro Limbah Utama', '9120005540099', 'supplier.rajs@cocolink.test', '081377665544', 'Kawasan Industri Jababeka V', 'Bekasi', 'Jawa Barat', 'verified', -6.28500000, 107.17000000, '2026-11-03 14:44:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','induk','supplier','coop','buyer','driver') NOT NULL,
  `driver_type` enum('pt_to_induk','induk_to_coop','coop_to_buyer') DEFAULT 'coop_to_buyer',
  `supplier_id` int(11) DEFAULT NULL,
  `coop_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `driver_type`, `supplier_id`, `coop_id`, `buyer_id`, `is_active`, `created_at`) VALUES
(1, 'Super Admin Master', 'admin@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'super_admin', 'coop_to_buyer', NULL, NULL, NULL, 1, '2026-11-03 14:44:13'),
(2, 'Pengelola Koperasi Induk Metropolitan', 'induk.jabodetabek@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'induk', 'coop_to_buyer', 1, NULL, NULL, 1, '2026-11-03 14:44:13'),
(3, 'Direktur PT Rajs Hydro', 'pt.rajs@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'supplier', 'coop_to_buyer', 2, NULL, NULL, 1, '2026-11-03 14:44:13'),
(4, 'Admin Kop. Anak Bogor', 'coop.bogor@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'coop', 'coop_to_buyer', 1, 1, NULL, 1, '2026-11-03 14:44:13'),
(5, 'Procurement Manager PT Eco Briket', 'buyer.briket@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'buyer', 'coop_to_buyer', NULL, NULL, 1, 1, '2026-11-03 14:44:13'),
(6, 'Driver Express Logistik', 'driver.budi@cocolink.test', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1zFhN0hL4a2J1mG.wE6N2P6G7K1zHCS', 'driver', 'coop_to_buyer', 1, 1, NULL, 1, '2026-11-03 14:44:13'),
(7, 'tes1', 'test1@gmail.com', '$2y$10$o8VLxdKMGHvBJTX0191XLeOAQGzIgG8CXhNiXqkTOUjHmG7Yp3egy', 'coop', 'coop_to_buyer', NULL, 6, NULL, 1, '2026-11-06 17:10:41');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `allocations`
--
ALTER TABLE `allocations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prodlog_product` (`product_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `allocations`
--
ALTER TABLE `allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  ADD CONSTRAINT `fk_prodlog_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
