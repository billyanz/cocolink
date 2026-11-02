-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Nov 2026 pada 04.57
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `coop_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(13,2) NOT NULL,
  `status` enum('proposed','accepted','rejected','completed') NOT NULL DEFAULT 'proposed',
  `note` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `allocations`
--

INSERT INTO `allocations` (`id`, `batch_id`, `coop_id`, `quantity`, `status`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 250.00, 'accepted', NULL, 2, '2026-10-28 09:12:23', '2026-10-28 09:12:23'),
(2, 1, 1, 1.00, 'accepted', NULL, 1, '2026-10-28 10:06:29', '2026-10-28 10:07:44'),
(3, 2, 2, 50.00, 'proposed', NULL, 2, '2026-11-02 03:44:33', '2026-11-02 03:44:33'),
(4, 2, 2, 50.00, 'proposed', NULL, 2, '2026-11-02 03:44:45', '2026-11-02 03:44:45'),
(5, 1, 1, 50.00, 'completed', NULL, 1, '2026-11-02 03:48:39', '2026-11-02 03:50:48'),
(6, 1, 1, 99.00, 'proposed', NULL, 2, '2026-11-02 03:53:35', '2026-11-02 03:53:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(90) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip`, `created_at`) VALUES
(1, 1, 'seed_demo', 'system', NULL, 'Data pengujian, bukan entitas nyata', 'CLI', '2026-10-28 09:12:23'),
(2, 1, 'login', 'user', 1, '', '::1', '2026-10-28 09:14:20'),
(3, 1, 'logout', 'user', 1, '', '::1', '2026-10-28 09:14:52'),
(4, 2, 'login', 'user', 2, '', '::1', '2026-10-28 09:15:32'),
(5, 2, 'logout', 'user', 2, '', '::1', '2026-10-28 09:16:03'),
(6, 2, 'login', 'user', 2, '', '::1', '2026-10-28 09:16:15'),
(7, 2, 'logout', 'user', 2, '', '::1', '2026-10-28 09:16:48'),
(8, 6, 'login', 'user', 6, '', '::1', '2026-10-28 09:17:07'),
(9, 6, 'logout', 'user', 6, '', '::1', '2026-10-28 09:17:49'),
(10, 4, 'login', 'user', 4, '', '::1', '2026-10-28 09:18:06'),
(11, 4, 'logout', 'user', 4, '', '::1', '2026-10-28 09:18:39'),
(12, 3, 'login', 'user', 3, '', '::1', '2026-10-28 09:19:08'),
(13, 3, 'logout', 'user', 3, '', '::1', '2026-10-28 09:19:23'),
(14, 1, 'login', 'user', 1, '', '::1', '2026-10-28 09:21:22'),
(15, 1, 'logout', 'user', 1, '', '::1', '2026-10-28 09:54:01'),
(16, 2, 'login', 'user', 2, '', '::1', '2026-10-28 09:54:32'),
(17, 2, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-10-28 09:54:54'),
(18, 2, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-10-28 09:54:56'),
(19, 2, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-10-28 09:54:57'),
(20, 2, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-10-28 09:54:58'),
(21, 2, 'verify_buyer', 'buyer', 1, 'verified', '::1', '2026-10-28 09:55:24'),
(22, 2, 'logout', 'user', 2, '', '::1', '2026-10-28 09:56:10'),
(23, 7, 'register_supplier', 'supplier', 2, 'Mencalonkan 5 koperasi anak', '::1', '2026-10-28 10:00:43'),
(24, 7, 'login', 'user', 7, '', '::1', '2026-10-28 10:00:54'),
(25, 7, 'logout', 'user', 7, '', '::1', '2026-10-28 10:03:12'),
(26, 1, 'login', 'user', 1, '', '::1', '2026-10-28 10:03:28'),
(27, 1, 'verify_coop', 'cooperative', 10, 'verified', '::1', '2026-10-28 10:05:03'),
(28, 1, 'verify_coop', 'cooperative', 9, 'verified', '::1', '2026-10-28 10:05:06'),
(29, 1, 'verify_coop', 'cooperative', 8, 'verified', '::1', '2026-10-28 10:05:08'),
(30, 1, 'verify_coop', 'cooperative', 7, 'verified', '::1', '2026-10-28 10:05:09'),
(31, 1, 'verify_coop', 'cooperative', 6, 'verified', '::1', '2026-10-28 10:05:12'),
(32, 1, 'verify_supplier', 'supplier', 2, 'verified; koperasi valid=5', '::1', '2026-10-28 10:05:39'),
(33, 1, 'allocate', 'allocation', 2, '', '::1', '2026-10-28 10:06:29'),
(34, 1, 'logout', 'user', 1, '', '::1', '2026-10-28 10:06:41'),
(35, 4, 'login', 'user', 4, '', '::1', '2026-10-28 10:07:33'),
(36, 4, 'respond_allocation', 'allocation', 2, 'accepted', '::1', '2026-10-28 10:07:44'),
(37, 4, 'logout', 'user', 4, '', '::1', '2026-10-28 11:05:37'),
(38, 2, 'login', 'user', 2, '', '::1', '2026-11-02 02:22:31'),
(39, 2, 'logout', 'user', 2, '', '::1', '2026-11-02 02:23:14'),
(40, 1, 'login', 'user', 1, '', '::1', '2026-11-02 02:23:29'),
(41, 1, 'logout', 'user', 1, '', '::1', '2026-11-02 03:21:30'),
(42, 2, 'login', 'user', 2, '', '::1', '2026-11-02 03:22:18'),
(43, 2, 'logout', 'user', 2, '', '::1', '2026-11-02 03:37:03'),
(44, 3, 'login', 'user', 3, '', '::1', '2026-11-02 03:37:45'),
(45, 3, 'new_batch', 'raw_batch', 2, '', '::1', '2026-11-02 03:42:03'),
(46, 3, 'logout', 'user', 3, '', '::1', '2026-11-02 03:43:44'),
(47, 2, 'login', 'user', 2, '', '::1', '2026-11-02 03:43:57'),
(48, 2, 'allocate', 'allocation', 3, '', '::1', '2026-11-02 03:44:33'),
(49, 2, 'allocate', 'allocation', 4, '', '::1', '2026-11-02 03:44:45'),
(50, 2, 'logout', 'user', 2, '', '::1', '2026-11-02 03:45:19'),
(51, 3, 'login', 'user', 3, '', '::1', '2026-11-02 03:46:10'),
(52, 3, 'logout', 'user', 3, '', '::1', '2026-11-02 03:46:23'),
(53, 4, 'login', 'user', 4, '', '::1', '2026-11-02 03:46:50'),
(54, 4, 'logout', 'user', 4, '', '::1', '2026-11-02 03:47:22'),
(55, 1, 'login', 'user', 1, '', '::1', '2026-11-02 03:47:37'),
(56, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:47:53'),
(57, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:47:56'),
(58, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:47:57'),
(59, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:48:20'),
(60, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:48:20'),
(61, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:48:20'),
(62, 1, 'verify_supplier', 'supplier', 2, 'verified; koperasi valid=5', '::1', '2026-11-02 03:48:21'),
(63, 1, 'allocate', 'allocation', 5, '', '::1', '2026-11-02 03:48:39'),
(64, 1, 'verify_supplier', 'supplier', 1, 'verified; koperasi valid=5', '::1', '2026-11-02 03:49:18'),
(65, 1, 'logout', 'user', 1, '', '::1', '2026-11-02 03:49:22'),
(66, 4, 'login', 'user', 4, '', '::1', '2026-11-02 03:49:33'),
(67, 4, 'respond_allocation', 'allocation', 5, 'accepted', '::1', '2026-11-02 03:49:41'),
(68, 4, 'new_product', 'product', 2, '', '::1', '2026-11-02 03:50:18'),
(69, 4, 'production', 'product', 2, '50 kg input -> 50 kg produk', '::1', '2026-11-02 03:50:48'),
(70, 4, 'logout', 'user', 4, '', '::1', '2026-11-02 03:51:20'),
(71, 3, 'login', 'user', 3, '', '::1', '2026-11-02 03:51:32'),
(72, 3, 'logout', 'user', 3, '', '::1', '2026-11-02 03:51:59'),
(73, 5, 'login', 'user', 5, '', '::1', '2026-11-02 03:52:12'),
(74, 5, 'logout', 'user', 5, '', '::1', '2026-11-02 03:52:35'),
(75, 2, 'login', 'user', 2, '', '::1', '2026-11-02 03:53:03'),
(76, 2, 'allocate', 'allocation', 6, '', '::1', '2026-11-02 03:53:35'),
(77, 2, 'verify_coop', 'cooperative', 10, 'verified', '::1', '2026-11-02 03:53:46'),
(78, 2, 'verify_coop', 'cooperative', 9, 'verified', '::1', '2026-11-02 03:53:48'),
(79, 2, 'verify_coop', 'cooperative', 8, 'verified', '::1', '2026-11-02 03:53:49'),
(80, 2, 'verify_coop', 'cooperative', 7, 'verified', '::1', '2026-11-02 03:53:50'),
(81, 2, 'verify_coop', 'cooperative', 6, 'verified', '::1', '2026-11-02 03:53:51'),
(82, 2, 'logout', 'user', 2, '', '::1', '2026-11-02 03:54:25'),
(83, 6, 'login', 'user', 6, '', '::1', '2026-11-02 03:55:23'),
(84, 6, 'logout', 'user', 6, '', '::1', '2026-11-02 03:55:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(160) NOT NULL,
  `buyer_type` enum('industry','collector') NOT NULL DEFAULT 'industry',
  `nib` varchar(30) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(90) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `buyer_profiles`
--

INSERT INTO `buyer_profiles` (`id`, `company_name`, `buyer_type`, `nib`, `address`, `city`, `status`, `created_at`) VALUES
(1, 'PT Contoh Industri Hijau', 'industry', 'DEMO-BUY-01', 'Alamat pengiriman demo, Jakarta', 'Jakarta', 'verified', '2026-10-28 09:12:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cooperatives`
--

CREATE TABLE `cooperatives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `province` varchar(90) NOT NULL,
  `city` varchar(90) NOT NULL,
  `address` text NOT NULL,
  `specialty_raw_type_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cooperatives`
--

INSERT INTO `cooperatives` (`id`, `supplier_id`, `name`, `province`, `city`, `address`, `specialty_raw_type_id`, `latitude`, `longitude`, `status`, `verified_by`, `created_at`) VALUES
(1, 1, 'Koperasi Hijau Daun', 'Jawa Barat', 'Bandung', 'Alamat pickup demo, Bandung', 1, NULL, NULL, 'verified', 2, '2026-10-28 09:12:23'),
(2, 1, 'Koperasi Arang Tempurung', 'Jawa Barat', 'Cimahi', 'Alamat pickup demo, Cimahi', 2, NULL, NULL, 'verified', 2, '2026-10-28 09:12:23'),
(3, 1, 'Koperasi Sabut Sirkular', 'Jawa Barat', 'Sumedang', 'Alamat pickup demo, Sumedang', 3, NULL, NULL, 'verified', 2, '2026-10-28 09:12:23'),
(4, 1, 'Koperasi Kayu Kelapa', 'Jawa Barat', 'Garut', 'Alamat pickup demo, Garut', 4, NULL, NULL, 'verified', 2, '2026-10-28 09:12:23'),
(5, 1, 'Koperasi Olahan Air', 'Jawa Barat', 'Subang', 'Alamat pickup demo, Subang', 5, NULL, NULL, 'verified', 2, '2026-10-28 09:12:23'),
(6, 2, 'sekdes 1', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis I lama', 1, NULL, NULL, 'verified', 2, '2026-10-28 10:00:43'),
(7, 2, 'Sekdes 2', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis 2', 2, NULL, NULL, 'verified', 2, '2026-10-28 10:00:43'),
(8, 2, 'sekdes 3', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis 4', 3, NULL, NULL, 'verified', 2, '2026-10-28 10:00:43'),
(9, 2, 'sekdes 4', 'DKI Jakarta', 'jakarta', 'Jl.Kayu Manis 5', 4, NULL, NULL, 'verified', 2, '2026-10-28 10:00:43'),
(10, 2, 'sekdes 6', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis 7', 6, NULL, NULL, 'verified', 2, '2026-10-28 10:00:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(40) NOT NULL,
  `buyer_id` bigint(20) UNSIGNED NOT NULL,
  `shipping_address` text NOT NULL,
  `city` varchar(90) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','assigned','in_transit','delivered','completed') NOT NULL DEFAULT 'requested',
  `total` decimal(17,2) NOT NULL DEFAULT 0.00,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `buyer_id`, `shipping_address`, `city`, `notes`, `status`, `total`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'ORD-DEMO-001', 1, 'Alamat pengiriman demo, Jakarta', 'Jakarta', NULL, 'assigned', 240000.00, 2, '2026-10-28 09:12:23', '2026-10-28 09:12:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(13,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 20.00, 12000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_logs`
--

CREATE TABLE `production_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `allocation_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `input_qty` decimal(13,2) NOT NULL,
  `output_qty` decimal(13,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `production_logs`
--

INSERT INTO `production_logs` (`id`, `allocation_id`, `product_id`, `input_qty`, `output_qty`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 200.00, 150.00, 'Simulasi konversi: 200 kg daun menjadi 150 kg sapu lidi', 4, '2026-10-28 09:12:23'),
(2, 5, 2, 50.00, 50.00, 'daun custom', 4, '2026-11-02 03:50:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coop_id` bigint(20) UNSIGNED NOT NULL,
  `raw_type_id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(15) NOT NULL DEFAULT 'kg',
  `price` decimal(15,2) NOT NULL,
  `stock` decimal(13,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `coop_id`, `raw_type_id`, `sku`, `name`, `description`, `unit`, `price`, `stock`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'DAUN-DEMO-001', 'Sapu Lidi Kelapa Premium', 'Produk hasil olahan daun kelapa', 'kg', 12000.00, 130.00, 1, '2026-10-28 09:12:23'),
(2, 1, 1, '32133232313123', 'daun kelapa custom', 'daun custom', 'kg', 10000.00, 50.00, 1, '2026-11-02 03:50:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_batches`
--

CREATE TABLE `raw_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `raw_type_id` int(10) UNSIGNED NOT NULL,
  `batch_code` varchar(30) NOT NULL,
  `quantity` decimal(13,2) NOT NULL,
  `unit` varchar(12) NOT NULL DEFAULT 'kg',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `raw_batches`
--

INSERT INTO `raw_batches` (`id`, `supplier_id`, `raw_type_id`, `batch_code`, `quantity`, `unit`, `notes`, `created_at`) VALUES
(1, 1, 1, 'RAW-DEMO-001', 500.00, 'kg', 'Bahan mentah simulasi', '2026-10-28 09:12:23'),
(2, 1, 2, 'RAW-261102-10447196', 100.00, 'kg', 'tolong bisa dikirim segera tidak, saya sedang butuh banyak ada pesanan', '2026-11-02 03:42:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_types`
--

CREATE TABLE `raw_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(90) NOT NULL,
  `examples` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `raw_types`
--

INSERT INTO `raw_types` (`id`, `name`, `examples`, `created_at`) VALUES
(1, 'Daun kelapa', 'Sapu lidi, anyaman, bahan kompos', '2026-10-28 09:12:03'),
(2, 'Tempurung kelapa', 'Arang, briket, karbon aktif', '2026-10-28 09:12:03'),
(3, 'Sabut kelapa', 'Cocopeat, cocofiber, keset', '2026-10-28 09:12:03'),
(4, 'Batang kelapa', 'Papan, furnitur, kerajinan', '2026-10-28 09:12:03'),
(5, 'Air kelapa', 'Nata de coco, pupuk cair, fermentasi', '2026-10-28 09:12:03'),
(6, 'Daging / ampas kelapa', 'Minyak, pakan, tepung ampas', '2026-10-28 09:12:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('assigned','accepted','pickup_pending','picked_up','delivered') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shipments`
--

INSERT INTO `shipments` (`id`, `order_id`, `driver_id`, `status`, `assigned_at`, `picked_up_at`, `delivered_at`) VALUES
(1, 1, 6, 'assigned', '2026-10-28 09:12:23', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` enum('assigned','accepted','pickup_requested','pickup_rejected','picked_up','location','delivered') NOT NULL,
  `note` text NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `actor_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shipment_events`
--

INSERT INTO `shipment_events` (`id`, `shipment_id`, `event_type`, `note`, `latitude`, `longitude`, `proof_path`, `actor_id`, `created_at`) VALUES
(1, 1, 'assigned', 'Order demo telah ditugaskan', NULL, NULL, NULL, 2, '2026-10-28 09:12:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `legal_name` varchar(160) NOT NULL,
  `nib` varchar(30) NOT NULL,
  `province` varchar(90) NOT NULL,
  `city` varchar(90) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `legal_name`, `nib`, `province`, `city`, `address`, `latitude`, `longitude`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES
(1, 'PT Demo Nusantara Kelapa', 'DEMO-001', 'Jawa Barat', 'Bandung', 'Alamat simulasi untuk pengujian CocoLink', -6.9175000, 107.6191000, 'verified', 1, '2026-11-02 10:49:18', '2026-10-28 09:12:23'),
(2, 'PT Hostel Company', '2141241212412412', 'DKI Jakarta', 'Jakarta', 'Jl. Keel Keel Keel', 6.2019512, 106.7883937, 'verified', 1, '2026-11-02 10:48:21', '2026-10-28 10:00:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','induk','supplier','coop','buyer','driver') NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `coop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `buyer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `supplier_id`, `coop_id`, `buyer_id`, `is_active`, `created_at`) VALUES
(1, 'Admin Platform', 'admin@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'super_admin', NULL, NULL, NULL, 1, '2026-10-28 09:12:23'),
(2, 'Admin Koperasi Induk', 'induk@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'induk', NULL, NULL, NULL, 1, '2026-10-28 09:12:23'),
(3, 'Manajer PT Demo', 'pt@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'supplier', 1, NULL, NULL, 1, '2026-10-28 09:12:23'),
(4, 'Ketua Koperasi Daun', 'anak@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'coop', NULL, 1, NULL, 1, '2026-10-28 09:12:23'),
(5, 'Purchasing Industri', 'buyer@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'buyer', NULL, NULL, 1, 1, '2026-10-28 09:12:23'),
(6, 'Driver CocoLink', 'driver@cocolink.test', '$2y$10$84JTwyjjMWf9L3jKmXrCpesHfgfiEeQalqliYerLiRx3bpoM0kZZu', 'driver', NULL, NULL, NULL, 1, '2026-10-28 09:12:23'),
(7, 'Rajsfano Priariya Barisky', 'fanowp@gmail.com', '$2y$10$3HgClU78dZMzqHJu5CpLBuySGNa3gage2BpJc/3I.kZz/8beUXrfi', 'supplier', 2, NULL, NULL, 1, '2026-10-28 10:00:43');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `allocations`
--
ALTER TABLE `allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_allocation_status` (`status`),
  ADD KEY `fk_all_batch` (`batch_id`),
  ADD KEY `fk_all_coop` (`coop_id`),
  ADD KEY `fk_all_creator` (`created_by`);

--
-- Indeks untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_time` (`created_at`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indeks untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coop_supplier_name` (`supplier_id`,`name`),
  ADD KEY `idx_coop_supplier_status` (`supplier_id`,`status`),
  ADD KEY `fk_coop_raw` (`specialty_raw_type_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_order_status` (`status`),
  ADD KEY `idx_order_buyer` (`buyer_id`),
  ADD KEY `fk_order_approver` (`approved_by`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_order` (`order_id`),
  ADD KEY `fk_item_product` (`product_id`);

--
-- Indeks untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prodlog_all` (`allocation_id`),
  ADD KEY `fk_prodlog_product` (`product_id`),
  ADD KEY `fk_prodlog_user` (`created_by`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_product_coop` (`coop_id`),
  ADD KEY `fk_product_raw` (`raw_type_id`);

--
-- Indeks untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_code` (`batch_code`),
  ADD KEY `fk_batch_supplier` (`supplier_id`),
  ADD KEY `fk_batch_type` (`raw_type_id`);

--
-- Indeks untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `fk_ship_driver` (`driver_id`);

--
-- Indeks untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_ship_time` (`shipment_id`,`created_at`),
  ADD KEY `fk_event_actor` (`actor_id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nib` (`nib`),
  ADD KEY `idx_supplier_status` (`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `fk_user_supplier` (`supplier_id`),
  ADD KEY `fk_user_coop` (`coop_id`),
  ADD KEY `fk_user_buyer` (`buyer_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `allocations`
--
ALTER TABLE `allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `allocations`
--
ALTER TABLE `allocations`
  ADD CONSTRAINT `fk_all_batch` FOREIGN KEY (`batch_id`) REFERENCES `raw_batches` (`id`),
  ADD CONSTRAINT `fk_all_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`),
  ADD CONSTRAINT `fk_all_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  ADD CONSTRAINT `fk_coop_raw` FOREIGN KEY (`specialty_raw_type_id`) REFERENCES `raw_types` (`id`),
  ADD CONSTRAINT `fk_coop_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`);

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ketidakleluasaan untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  ADD CONSTRAINT `fk_prodlog_all` FOREIGN KEY (`allocation_id`) REFERENCES `allocations` (`id`),
  ADD CONSTRAINT `fk_prodlog_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_prodlog_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`),
  ADD CONSTRAINT `fk_product_raw` FOREIGN KEY (`raw_type_id`) REFERENCES `raw_types` (`id`);

--
-- Ketidakleluasaan untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  ADD CONSTRAINT `fk_batch_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_batch_type` FOREIGN KEY (`raw_type_id`) REFERENCES `raw_types` (`id`);

--
-- Ketidakleluasaan untuk tabel `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_ship_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ship_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Ketidakleluasaan untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD CONSTRAINT `fk_event_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_event_ship` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`);

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`),
  ADD CONSTRAINT `fk_user_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`),
  ADD CONSTRAINT `fk_user_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
