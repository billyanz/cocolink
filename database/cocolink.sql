-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Nov 2026 pada 10.39
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
  `id` int(10) UNSIGNED NOT NULL,
  `batch_id` int(10) UNSIGNED NOT NULL,
  `coop_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `status` enum('proposed','accepted','rejected') NOT NULL DEFAULT 'proposed',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `allocations`
--

INSERT INTO `allocations` (`id`, `batch_id`, `coop_id`, `quantity`, `status`, `created_by`, `created_at`) VALUES
(1, 1, 1, 500.00, 'accepted', 2, '2026-11-02 13:05:44'),
(2, 2, 10, 10.00, 'proposed', 1, '2026-11-02 14:42:41'),
(3, 3, 9, 100.00, 'proposed', 1, '2026-11-02 14:54:39'),
(4, 4, 14, 10.00, 'accepted', 1, '2026-11-02 15:00:50'),
(5, 5, 14, 50.00, 'accepted', 8, '2026-11-02 15:34:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip`, `created_at`) VALUES
(1, 1, 'seed_demo', 'system', NULL, 'Inisialisasi Data Demo Transaksi Selesai CoCoLink', 'CLI', '2026-11-02 13:05:44'),
(2, 1, 'logout', 'user', 1, '', '::1', '2026-11-02 13:16:07'),
(3, 2, 'login', 'user', 2, '', '::1', '2026-11-02 13:16:20'),
(4, 2, 'logout', 'user', 2, '', '::1', '2026-11-02 13:58:46'),
(5, 7, 'register_supplier', 'suppliers', 2, 'Pendaftaran PT Pemasok baru dan 5 Koperasi Anak', '::1', '2026-11-02 14:16:18'),
(6, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:17:02'),
(7, 1, 'verify_supplier', 'suppliers', 2, 'Pembaruan verifikasi PT Pemasok ke verified', '::1', '2026-11-02 14:17:11'),
(8, 1, 'verify_supplier', 'suppliers', 2, 'Pembaruan verifikasi PT Pemasok ke verified', '::1', '2026-11-02 14:17:13'),
(9, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 14:17:19'),
(10, 7, 'login', 'users', 7, 'Login berhasil ke platform', '::1', '2026-11-02 14:17:25'),
(11, 7, 'create_batch', 'raw_batches', 2, 'Input batch pasokan limbah baru', '::1', '2026-11-02 14:17:52'),
(12, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-02 14:18:29'),
(13, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:18:43'),
(14, 1, 'verify_supplier', 'suppliers', 2, 'Pembaruan verifikasi PT Pemasok ke verified', '::1', '2026-11-02 14:18:50'),
(15, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 14:22:40'),
(16, 7, 'login', 'users', 7, 'Login berhasil ke platform', '::1', '2026-11-02 14:22:45'),
(17, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-02 14:28:02'),
(18, 8, 'register_supplier', 'suppliers', 3, 'Pendaftaran PT Pemasok baru dan 5 Koperasi Anak beserta titik lokasi peta', '::1', '2026-11-02 14:40:25'),
(19, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:40:39'),
(20, 1, 'verify_coop', 'cooperatives', 15, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:40:51'),
(21, 1, 'verify_supplier', 'suppliers', 3, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:40:54'),
(22, 1, 'verify_coop', 'cooperatives', 10, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:40:56'),
(23, 1, 'verify_coop', 'cooperatives', 9, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:40:59'),
(24, 1, 'verify_coop', 'cooperatives', 8, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:41:01'),
(25, 1, 'verify_coop', 'cooperatives', 7, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:41:03'),
(26, 1, 'verify_coop', 'cooperatives', 6, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:41:05'),
(27, 1, 'allocate', 'allocations', 2, 'Alokasi bahan mentah ke Koperasi Anak', '::1', '2026-11-02 14:42:41'),
(28, 1, 'verify_coop', 'cooperatives', 15, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:45:22'),
(29, 1, 'verify_coop', 'cooperatives', 14, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:45:24'),
(30, 1, 'verify_coop', 'cooperatives', 13, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:45:27'),
(31, 1, 'verify_coop', 'cooperatives', 12, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:45:30'),
(32, 1, 'verify_coop', 'cooperatives', 11, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:45:33'),
(33, 1, 'verify_supplier', 'suppliers', 3, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:46:22'),
(34, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 14:46:49'),
(35, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 14:46:57'),
(36, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 14:49:41'),
(37, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:50:13'),
(38, 1, 'verify_supplier', 'suppliers', 3, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:50:52'),
(39, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 14:53:00'),
(40, 7, 'login', 'users', 7, 'Login berhasil ke platform', '::1', '2026-11-02 14:53:03'),
(41, 7, 'create_batch', 'raw_batches', 3, 'Input batch pasokan limbah baru', '::1', '2026-11-02 14:53:23'),
(42, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-02 14:53:36'),
(43, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:53:49'),
(44, 1, 'allocate', 'allocations', 3, 'Alokasi bahan mentah ke Koperasi Anak', '::1', '2026-11-02 14:54:39'),
(45, 1, 'verify_supplier', 'suppliers', 3, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:59:03'),
(46, 1, 'verify_supplier', 'suppliers', 2, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:59:04'),
(47, 1, 'verify_supplier', 'suppliers', 1, 'Pembaruan verifikasi PT Pemasok & Koperasi Binaan ke verified', '::1', '2026-11-02 14:59:05'),
(48, 1, 'verify_coop', 'cooperatives', 15, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:59:07'),
(49, 1, 'verify_coop', 'cooperatives', 14, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:59:08'),
(50, 1, 'verify_coop', 'cooperatives', 13, 'Pembaruan verifikasi Koperasi Anak ke verified', '::1', '2026-11-02 14:59:10'),
(51, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 14:59:14'),
(52, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 14:59:39'),
(53, 1, 'create_batch', 'raw_batches', 4, 'Input batch pasokan limbah baru', '::1', '2026-11-02 14:59:58'),
(54, 1, 'allocate', 'allocations', 4, 'Alokasi bahan mentah ke Koperasi Anak', '::1', '2026-11-02 15:00:50'),
(55, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 15:01:29'),
(56, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 15:01:36'),
(57, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 15:02:04'),
(58, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 15:07:40'),
(59, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 15:13:04'),
(60, 7, 'login', 'users', 7, 'Login berhasil ke platform', '::1', '2026-11-02 15:13:09'),
(61, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-02 15:15:40'),
(62, 7, 'login', 'users', 7, 'Login berhasil ke platform', '::1', '2026-11-02 15:15:46'),
(63, 7, 'logout', 'users', 7, 'Pengguna keluar sistem', '::1', '2026-11-02 15:19:35'),
(64, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 15:19:39'),
(65, 8, 'create_user', 'users', 9, 'Pembuatan akun pengguna baru peran coop', '::1', '2026-11-02 15:24:09'),
(66, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 15:32:38'),
(67, 1, 'login', 'users', 1, 'Login berhasil ke platform', '::1', '2026-11-02 15:32:52'),
(68, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 15:33:33'),
(69, 9, 'login', 'users', 9, 'Login berhasil ke platform', '::1', '2026-11-02 15:33:36'),
(70, 9, 'logout', 'users', 9, 'Pengguna keluar sistem', '::1', '2026-11-02 15:33:50'),
(71, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 15:34:02'),
(72, 8, 'create_batch', 'raw_batches', 5, 'Input batch pasokan limbah baru', '::1', '2026-11-02 15:34:37'),
(73, 8, 'allocate', 'allocations', 5, 'Alokasi bahan mentah ke Koperasi Anak', '::1', '2026-11-02 15:34:49'),
(74, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 15:35:01'),
(75, 9, 'login', 'users', 9, 'Login berhasil ke platform', '::1', '2026-11-02 15:35:04'),
(76, 9, 'logout', 'users', 9, 'Pengguna keluar sistem', '::1', '2026-11-02 15:35:32'),
(77, 8, 'login', 'users', 8, 'Login berhasil ke platform', '::1', '2026-11-02 15:35:51'),
(78, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 15:36:53'),
(79, 2, 'login', 'users', 2, 'Login berhasil ke platform', '::1', '2026-11-02 15:40:14'),
(80, 2, 'logout', 'users', 2, 'Pengguna keluar sistem', '::1', '2026-11-02 16:06:08'),
(81, 1, 'login', 'users', 1, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:06:32'),
(82, 1, 'verify_supplier', 'suppliers', 3, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:07:18'),
(83, 1, 'verify_supplier', 'suppliers', 2, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:07:19'),
(84, 1, 'verify_supplier', 'suppliers', 1, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:07:20'),
(85, 1, 'logout', 'users', 1, 'Pengguna keluar sistem', '::1', '2026-11-02 16:08:28'),
(86, 2, 'login', 'users', 2, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:08:55'),
(87, 2, 'verify_supplier', 'suppliers', 3, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:09:01'),
(88, 2, 'verify_supplier', 'suppliers', 2, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:09:02'),
(89, 2, 'verify_supplier', 'suppliers', 1, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:09:03'),
(90, 2, 'logout', 'users', 2, 'Pengguna keluar sistem', '::1', '2026-11-02 16:09:47'),
(91, 8, 'login', 'users', 8, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:09:55'),
(92, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 16:11:08'),
(93, 9, 'login', 'users', 9, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:11:12'),
(94, 9, 'respond_allocation', 'allocations', 5, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-02 16:13:42'),
(95, 9, 'respond_allocation', 'allocations', 4, 'Koperasi Sektor merespon alokasi ke accepted', '::1', '2026-11-02 16:13:43'),
(96, 9, 'create_product', 'products', 2, 'Menambahkan katalog produk olahan baru', '::1', '2026-11-02 16:15:14'),
(97, 9, 'log_production', 'production_logs', 2, 'Catatan konversi produksi olahan sektor', '::1', '2026-11-02 16:15:55'),
(98, 9, 'logout', 'users', 9, 'Pengguna keluar sistem', '::1', '2026-11-02 16:16:09'),
(99, 5, 'login', 'users', 5, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:16:15'),
(100, 5, 'create_order', 'orders', 2, 'Pengajuan pesanan B2B industri sektor baru', '::1', '2026-11-02 16:20:08'),
(101, 5, 'logout', 'users', 5, 'Pengguna keluar sistem', '::1', '2026-11-02 16:20:14'),
(102, 9, 'login', 'users', 9, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:20:24'),
(103, 9, 'approve_order', 'orders', 2, 'Koperasi Sektor menyetujui order ke approved', '::1', '2026-11-02 16:20:29'),
(104, 9, 'logout', 'users', 9, 'Pengguna keluar sistem', '::1', '2026-11-02 16:20:47'),
(105, 8, 'login', 'users', 8, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:20:51'),
(106, 8, 'assign_driver', 'shipments', 2, 'Penugasan driver armada ke pesanan sektor', '::1', '2026-11-02 16:22:03'),
(107, 8, 'logout', 'users', 8, 'Pengguna keluar sistem', '::1', '2026-11-02 16:23:16'),
(108, 2, 'login', 'users', 2, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:23:23'),
(109, 2, 'verify_supplier', 'suppliers', 3, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:23:31'),
(110, 2, 'verify_supplier', 'suppliers', 2, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:23:32'),
(111, 2, 'verify_supplier', 'suppliers', 1, 'Verifikasi PT Pemasok & Koperasi Sektor ke verified', '::1', '2026-11-02 16:23:33'),
(112, 2, 'logout', 'users', 2, 'Pengguna keluar sistem', '::1', '2026-11-02 16:24:04'),
(113, 6, 'login', 'users', 6, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:24:15'),
(114, 6, 'shipment_event', 'shipments', 2, 'Update status driver ke accepted', '::1', '2026-11-02 16:24:42'),
(115, 6, 'shipment_event', 'shipments', 2, 'Update status driver ke pickup_requested', '::1', '2026-11-02 16:25:07'),
(116, 6, 'logout', 'users', 6, 'Pengguna keluar sistem', '::1', '2026-11-02 16:25:12'),
(117, 9, 'login', 'users', 9, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:25:15'),
(118, 9, 'approve_pickup', 'shipments', 2, 'Respon persetujuan pickup ke picked_up', '::1', '2026-11-02 16:25:30'),
(119, 9, 'logout', 'users', 9, 'Pengguna keluar sistem', '::1', '2026-11-02 16:25:33'),
(120, 6, 'login', 'users', 6, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:25:42'),
(121, 6, 'shipment_event', 'shipments', 2, 'Update status driver ke location', '::1', '2026-11-02 16:26:01'),
(122, 6, 'shipment_event', 'shipments', 2, 'Update status driver ke delivered', '::1', '2026-11-02 16:26:15'),
(123, 6, 'logout', 'users', 6, 'Pengguna keluar sistem', '::1', '2026-11-02 16:26:23'),
(124, 5, 'login', 'users', 5, 'Login berhasil ke platform Jabodetabek', '::1', '2026-11-02 16:26:28'),
(125, 5, 'logout', 'users', 5, 'Pengguna keluar sistem', '::1', '2026-11-02 16:34:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `buyer_type` enum('industry','collector','exporter') NOT NULL DEFAULT 'industry',
  `nib` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `buyer_profiles`
--

INSERT INTO `buyer_profiles` (`id`, `company_name`, `buyer_type`, `nib`, `address`, `city`, `status`, `created_at`) VALUES
(1, 'PT Industri Ekosistem Hijau', 'industry', 'DEMO-BUY-888', 'Kawasan Industri Pulogadung, Jakarta Timur', 'Jakarta', 'verified', '2026-11-02 13:05:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cooperatives`
--

CREATE TABLE `cooperatives` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `specialty_raw_type_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cooperatives`
--

INSERT INTO `cooperatives` (`id`, `supplier_id`, `name`, `province`, `city`, `address`, `latitude`, `longitude`, `specialty_raw_type_id`, `status`, `verified_by`, `created_at`) VALUES
(1, 1, 'Koperasi Hijau Daun (Bandung)', 'Jawa Barat', 'Bandung', 'Gudang Penampungan & Pickup Bandung', NULL, NULL, 1, 'verified', 2, '2026-11-02 13:05:44'),
(2, 1, 'Koperasi Arang Tempurung (Cimahi)', 'Jawa Barat', 'Cimahi', 'Gudang Penampungan & Pickup Cimahi', NULL, NULL, 2, 'verified', 2, '2026-11-02 13:05:44'),
(3, 1, 'Koperasi Sabut Sirkular (Sumedang)', 'Jawa Barat', 'Sumedang', 'Gudang Penampungan & Pickup Sumedang', NULL, NULL, 3, 'verified', 2, '2026-11-02 13:05:44'),
(4, 1, 'Koperasi Kayu Kelapa (Garut)', 'Jawa Barat', 'Garut', 'Gudang Penampungan & Pickup Garut', NULL, NULL, 4, 'verified', 2, '2026-11-02 13:05:44'),
(5, 1, 'Koperasi Olahan Air (Subang)', 'Jawa Barat', 'Subang', 'Gudang Penampungan & Pickup Subang', NULL, NULL, 5, 'verified', 2, '2026-11-02 13:05:44'),
(6, 2, 'matraman 1', 'DKI Jakarta', 'Jakarta', 'Jl. Kayu Manis 1', NULL, NULL, 1, 'verified', 2, '2026-11-02 14:16:18'),
(7, 2, 'Matraman 2', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis 2', NULL, NULL, 2, 'verified', 2, '2026-11-02 14:16:18'),
(8, 2, 'Matraman 3', 'DKI Jakarta', 'Jakarta', 'Jl.Kayu Manis 3', NULL, NULL, 3, 'verified', 2, '2026-11-02 14:16:18'),
(9, 2, 'Matraman 4', 'DKI Jakarta', 'Jakarta', 'Jl. Kayu Manis 4', NULL, NULL, 4, 'verified', 2, '2026-11-02 14:16:18'),
(10, 2, 'Matraman 5', 'DKI Jakarta', 'Jakarta', 'Jl. Kayu Manis 5', NULL, NULL, 5, 'verified', 2, '2026-11-02 14:16:18'),
(11, 3, 'cianjur 1', 'Jawa Barat', 'Cianjur', 'jl cianjur 1', NULL, NULL, 1, 'verified', 2, '2026-11-02 14:40:25'),
(12, 3, 'cianjur 2', 'Jawa Barat', 'cianjur', 'jl cianjur 2', NULL, NULL, 2, 'verified', 2, '2026-11-02 14:40:25'),
(13, 3, 'cianjur 3', 'Jawa Barat', 'cianjur', 'jl cianjur 3', NULL, NULL, 3, 'verified', 2, '2026-11-02 14:40:25'),
(14, 3, 'cianjur 4', 'Jawa Barat', 'cianjur', 'jl cianjur 4', NULL, NULL, 4, 'verified', 2, '2026-11-02 14:40:25'),
(15, 3, 'cianjur 5', 'Jawa Barat', 'cianjur', 'cianjur 5', NULL, NULL, 5, 'verified', 2, '2026-11-02 14:40:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(100) NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `shipping_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','assigned','in_transit','delivered','completed') NOT NULL DEFAULT 'requested',
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `buyer_id`, `shipping_address`, `city`, `notes`, `status`, `total`, `approved_by`, `created_at`) VALUES
(1, 'ORD-DEMO-FINISHED', 1, 'Kawasan Industri Pulogadung Block C4, Jakarta Timur', 'Jakarta', 'Pengiriman mendesak untuk ekspor', 'completed', 750000.00, 2, '2026-11-02 13:05:44'),
(2, 'ORD-261102-A026672D', 1, 'jl pabrik 1', 'Jakarta', '', 'delivered', 50000.00, 9, '2026-11-02 16:20:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 50.00, 15000.00),
(2, 2, 2, 2.00, 25000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_logs`
--

CREATE TABLE `production_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `allocation_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `input_qty` decimal(12,2) NOT NULL,
  `output_qty` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `production_logs`
--

INSERT INTO `production_logs` (`id`, `allocation_id`, `product_id`, `input_qty`, `output_qty`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 400.00, 300.00, 'Konversi 400 kg daun kelapa menjadi 300 kg sapu lidi premium', 4, '2026-11-02 13:05:44'),
(2, 5, 2, 100.00, 10.00, '', 9, '2026-11-02 16:15:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `coop_id` int(10) UNSIGNED NOT NULL,
  `raw_type_id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `price` decimal(12,2) NOT NULL,
  `stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `coop_id`, `raw_type_id`, `sku`, `name`, `description`, `unit`, `price`, `stock`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'SAPU-LIDI-01', 'Sapu Lidi Kelapa Export Grade', 'Olahan lidi daun kelapa tebal dan bersih', 'kg', 15000.00, 250.00, 1, '2026-11-02 13:05:44'),
(2, 14, 2, 'PROD-261102-FEAD3990', 'lidi kolang kaling', 'kolang kaling dari lidi', 'kg', 25000.00, 8.00, 1, '2026-11-02 16:15:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_batches`
--

CREATE TABLE `raw_batches` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `raw_type_id` int(10) UNSIGNED NOT NULL,
  `batch_code` varchar(100) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `raw_batches`
--

INSERT INTO `raw_batches` (`id`, `supplier_id`, `raw_type_id`, `batch_code`, `quantity`, `unit`, `notes`, `created_at`) VALUES
(1, 1, 1, 'RAW-DEMO-001', 1000.00, 'kg', 'Pasokan Daun Kelapa Segar dari Perkebunan', '2026-11-02 13:05:44'),
(2, 2, 5, 'RAW-261102-20A7E810', 10.00, 'kg', 'Limbah Air Kelapa Bersih', '2026-11-02 14:17:52'),
(3, 2, 4, 'RAW-261102-6C2212A2', 200.00, 'kg', 'nih batang', '2026-11-02 14:53:23'),
(4, 3, 4, 'RAW-261102-CCF1E2F7', 10.00, 'kg', 'nih ada batang 10 klo', '2026-11-02 14:59:58'),
(5, 3, 2, 'RAW-261102-E2FBAA44', 100.00, 'kg', 'tempurung test', '2026-11-02 15:34:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `raw_types`
--

CREATE TABLE `raw_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `raw_types`
--

INSERT INTO `raw_types` (`id`, `name`, `code`, `description`) VALUES
(1, 'Daun Kelapa & Lidi', 'RAW_LEAF', 'Pelepah, daun segar, dan lidi kelapa untuk kerajinan/sapu'),
(2, 'Tempurung Kelapa', 'RAW_SHELL', 'Batok kelapa kering untuk bahan arang, briket, dan karbon aktif'),
(3, 'Sabut Kelapa', 'RAW_HUSK', 'Sabut mentah untuk serat cocofiber dan media tanam cocopeat'),
(4, 'Batang & Kayu Kelapa', 'RAW_WOOD', 'Kayu pohon kelapa tua untuk bahan bangunan dan furniture'),
(5, 'Air Kelapa & Daging Ampas', 'RAW_LIQUID', 'Air kelapa dan ampas olahan untuk kebutuhan industri makanan/minuman');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipments`
--

CREATE TABLE `shipments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `driver_id` int(10) UNSIGNED NOT NULL,
  `status` enum('assigned','accepted','pickup_pending','picked_up','in_transit','delivered') NOT NULL DEFAULT 'assigned',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `shipments`
--

INSERT INTO `shipments` (`id`, `order_id`, `driver_id`, `status`, `assigned_at`, `picked_up_at`, `delivered_at`) VALUES
(1, 1, 6, 'delivered', '2026-10-31 13:05:44', '2026-11-01 13:05:44', '2026-11-02 13:05:44'),
(2, 2, 6, 'delivered', '2026-11-02 16:22:03', '2026-11-02 16:25:30', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `shipment_id` int(10) UNSIGNED NOT NULL,
  `event_type` enum('assigned','accepted','pickup_requested','pickup_rejected','picked_up','location','delivered') NOT NULL,
  `note` text DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `actor_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `shipment_events`
--

INSERT INTO `shipment_events` (`id`, `shipment_id`, `event_type`, `note`, `latitude`, `longitude`, `proof_path`, `actor_id`, `created_at`) VALUES
(1, 1, 'assigned', 'Driver ditugaskan oleh Koperasi Induk', NULL, NULL, NULL, 2, '2026-10-31 13:05:44'),
(2, 1, 'accepted', 'Driver siap mengambil barang di Gudang Koperasi', NULL, NULL, NULL, 6, '2026-10-31 19:05:44'),
(3, 1, 'picked_up', 'Persetujuan ACC Pickup oleh Koperasi Anak (Muatan 50 kg Sapu Lidi)', NULL, NULL, NULL, 4, '2026-11-01 13:05:44'),
(4, 1, 'location', 'Dalam perjalanan di Tol Cikampek KM 42', -6.3412000, 107.1523000, NULL, 6, '2026-11-02 08:05:44'),
(5, 1, 'delivered', 'Barang telah diserahterimakan ke tim Gudang Industri.', -6.1923000, 106.9123000, NULL, 6, '2026-11-02 13:05:44'),
(6, 2, 'assigned', 'Driver armada ditugaskan untuk pengiriman Sektor Jabodetabek', NULL, NULL, NULL, 8, '2026-11-02 16:22:03'),
(7, 2, 'accepted', 'tol jagorawi', 22.6222360, 88.4195060, NULL, 6, '2026-11-02 16:24:42'),
(8, 2, 'pickup_requested', 'gudang koprasi', 22.6222360, 88.4195060, 'proof_2_1793611507.pdf', 6, '2026-11-02 16:25:07'),
(9, 2, 'picked_up', 'sudah terima fisik thankyou', NULL, NULL, NULL, 9, '2026-11-02 16:25:30'),
(10, 2, 'location', 'gudang koprasi', -6.3913000, 106.7176000, NULL, 6, '2026-11-02 16:26:01'),
(11, 2, 'delivered', 'gudang koprasi', -6.3913000, 106.7176000, NULL, 6, '2026-11-02 16:26:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `legal_name` varchar(255) NOT NULL,
  `nib` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `legal_name`, `nib`, `province`, `city`, `address`, `latitude`, `longitude`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES
(1, 'PT Demo Nusantara Kelapa', 'DEMO-NIB-999', 'Jawa Barat', 'Bandung', 'Jl. Sukajadi No. 102, Bandung', -6.8915000, 107.5981000, 'verified', 2, '2026-11-02 16:23:33', '2026-11-02 13:05:44'),
(2, 'PT Rajs Hydro', '998877665544', 'DKI Jakarta', 'Jakarta', 'Jl. Kayu Manis 1 Lama', -6.2000000, 106.8166666, 'verified', 2, '2026-11-02 16:23:32', '2026-11-02 14:16:18'),
(3, 'PT. bil trans', '93123813831', 'Jawa Barat', 'Cianjur', 'Jl. Cianjur Barat daya utara selatan', -6.2000000, 106.8166666, 'verified', 2, '2026-11-02 16:23:31', '2026-11-02 14:40:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','induk','supplier','coop','buyer','driver') NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL,
  `coop_id` int(10) UNSIGNED DEFAULT NULL,
  `buyer_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `supplier_id`, `coop_id`, `buyer_id`, `is_active`, `created_at`) VALUES
(1, 'Super Admin CoCoLink', 'admin@cocolink.test', '$2y$10$fw57Ju3Yj6oGh9PF/xnJC.UHpVy4JkO6hRN8F0zZE/iBNUJUx1/0.', 'super_admin', NULL, NULL, NULL, 1, '2026-11-02 13:05:44'),
(2, 'Admin Koperasi Induk', 'induk@cocolink.test', '$2y$10$NH/BRgs5O3cgO9DVwyFTPee.88NzOaxPV7y8psgpawisxrn36DKgO', 'induk', NULL, NULL, NULL, 1, '2026-11-02 13:05:44'),
(3, 'Manajer PT Demo', 'pt@cocolink.test', '$2y$10$NH/BRgs5O3cgO9DVwyFTPee.88NzOaxPV7y8psgpawisxrn36DKgO', 'supplier', 1, NULL, NULL, 1, '2026-11-02 13:05:44'),
(4, 'Ketua Koperasi Daun', 'anak@cocolink.test', '$2y$10$NH/BRgs5O3cgO9DVwyFTPee.88NzOaxPV7y8psgpawisxrn36DKgO', 'coop', NULL, 1, NULL, 1, '2026-11-02 13:05:44'),
(5, 'Purchasing Manufaktur', 'buyer@cocolink.test', '$2y$10$NH/BRgs5O3cgO9DVwyFTPee.88NzOaxPV7y8psgpawisxrn36DKgO', 'buyer', NULL, NULL, 1, 1, '2026-11-02 13:05:44'),
(6, 'Budi Logistik Driver', 'driver@cocolink.test', '$2y$10$NH/BRgs5O3cgO9DVwyFTPee.88NzOaxPV7y8psgpawisxrn36DKgO', 'driver', NULL, NULL, NULL, 1, '2026-11-02 13:05:44'),
(7, 'Rajsfano Priariya Barisky', 'fanowp@gmail.com', '$2y$10$Xcuc4xTfyv6kfETf4eUGjejIVR9sgJU.J8QnD9xiqF/pILe5LaxN.', 'supplier', 2, NULL, NULL, 1, '2026-11-02 14:16:18'),
(8, 'Billyanz', 'billy@gmail.com', '$2y$10$jN1D4SKICgYkRlRgkhoeJOcHfSDXX2ehMCywpeXGVo189lmMaGgw6', 'supplier', 3, NULL, NULL, 1, '2026-11-02 14:40:25'),
(9, 'PT. Cipta Teknologi Cendekia', 'ctc@gmail.com', '$2y$10$jrl/O.GGpJST6m4RMnwWPeV4n/XYOt75Ze2mfaJkUmGE6F9ceDSL2', 'coop', 3, 14, NULL, 1, '2026-11-02 15:24:09');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `allocations`
--
ALTER TABLE `allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alloc_batch` (`batch_id`),
  ADD KEY `fk_alloc_coop` (`coop_id`),
  ADD KEY `fk_alloc_user` (`created_by`);

--
-- Indeks untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indeks untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nib` (`nib`);

--
-- Indeks untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_coop_supplier` (`supplier_id`),
  ADD KEY `fk_coop_rawtype` (`specialty_raw_type_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `fk_order_buyer` (`buyer_id`),
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
  ADD KEY `fk_prodlog_alloc` (`allocation_id`),
  ADD KEY `fk_prodlog_product` (`product_id`),
  ADD KEY `fk_prodlog_user` (`created_by`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_prod_coop` (`coop_id`),
  ADD KEY `fk_prod_rawtype` (`raw_type_id`);

--
-- Indeks untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_code` (`batch_code`),
  ADD KEY `fk_batch_supplier` (`supplier_id`),
  ADD KEY `fk_batch_rawtype` (`raw_type_id`);

--
-- Indeks untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

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
  ADD KEY `fk_event_ship` (`shipment_id`),
  ADD KEY `fk_event_actor` (`actor_id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nib` (`nib`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT untuk tabel `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `raw_types`
--
ALTER TABLE `raw_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `allocations`
--
ALTER TABLE `allocations`
  ADD CONSTRAINT `fk_alloc_batch` FOREIGN KEY (`batch_id`) REFERENCES `raw_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alloc_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alloc_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `cooperatives`
--
ALTER TABLE `cooperatives`
  ADD CONSTRAINT `fk_coop_rawtype` FOREIGN KEY (`specialty_raw_type_id`) REFERENCES `raw_types` (`id`),
  ADD CONSTRAINT `fk_coop_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`);

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ketidakleluasaan untuk tabel `production_logs`
--
ALTER TABLE `production_logs`
  ADD CONSTRAINT `fk_prodlog_alloc` FOREIGN KEY (`allocation_id`) REFERENCES `allocations` (`id`),
  ADD CONSTRAINT `fk_prodlog_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_prodlog_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prod_rawtype` FOREIGN KEY (`raw_type_id`) REFERENCES `raw_types` (`id`);

--
-- Ketidakleluasaan untuk tabel `raw_batches`
--
ALTER TABLE `raw_batches`
  ADD CONSTRAINT `fk_batch_rawtype` FOREIGN KEY (`raw_type_id`) REFERENCES `raw_types` (`id`),
  ADD CONSTRAINT `fk_batch_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_ship_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ship_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD CONSTRAINT `fk_event_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_event_ship` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyer_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_coop` FOREIGN KEY (`coop_id`) REFERENCES `cooperatives` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
