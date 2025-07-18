-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql103.byetcluster.com
-- Waktu pembuatan: 18 Jul 2025 pada 01.15
-- Versi server: 11.4.7-MariaDB
-- Versi PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `b22_37265128_democheckerwaorder`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `description`, `details`, `created_at`) VALUES
(1, 1, 'import_sales', 'Import data penjualan dari file: Sample.xlsx', '{\n    \"file_name\": \"Sample.xlsx\",\n    \"imported_invoices\": 103,\n    \"imported_items\": 106,\n    \"skipped_rows\": 206,\n    \"completed_invoices_skipped\": 0\n}', '2025-07-02 20:09:43'),
(2, 1, 'clear_all_pending', 'Membersihkan semua (103) pending orders.', '{\n    \"cleared_count\": 103\n}', '2025-07-02 20:13:43'),
(3, 1, 'import_sales', 'Import data penjualan dari file: Sample (1).xlsx', '{\n    \"file_name\": \"Sample (1).xlsx\",\n    \"imported_invoices\": 110,\n    \"imported_items\": 311,\n    \"skipped_rows\": 1,\n    \"completed_invoices_skipped\": 0\n}', '2025-07-02 20:14:17'),
(4, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409060001-S', '{\n    \"no_penjualan\": \"JO-2409060001-S\"\n}', '2025-07-02 20:15:15'),
(5, 1, 'reset_items', 'Reset item pada order: JO-2409060001-S', '{\n    \"pending_import_id\": 2356,\n    \"no_penjualan\": \"JO-2409060001-S\"\n}', '2025-07-02 20:17:22'),
(6, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.253.194.14\",\n    \"user_agent\": \"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/137.0.0.0 Safari\\/537.36\"\n}', '2025-07-02 21:57:31'),
(7, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409070001-S', '{\n    \"no_penjualan\": \"JO-2409070001-S\"\n}', '2025-07-02 21:58:09'),
(8, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409070001-S', '{\n    \"no_penjualan\": \"JO-2409070001-S\"\n}', '2025-07-02 22:02:27'),
(9, 1, 'scan_item', 'Scan item: 11000322 (Qty: +1)', '{\n    \"pending_import_id\": 2345,\n    \"item_id\": 7944,\n    \"kode_barang\": \"11000322\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:02:54'),
(10, 1, 'scan_item', 'Scan item: 03091061 (Qty: +1)', '{\n    \"pending_import_id\": 2345,\n    \"item_id\": 7945,\n    \"kode_barang\": \"03091061\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": false\n}', '2025-07-02 22:03:01'),
(11, 1, 'scan_item', 'Scan item: 03091061 (Qty: +4)', '{\n    \"pending_import_id\": 2345,\n    \"item_id\": 7945,\n    \"kode_barang\": \"03091061\",\n    \"scanned_qty\": 4,\n    \"new_qty_ready\": 5,\n    \"is_completed\": true\n}', '2025-07-02 22:03:11'),
(12, 1, 'complete_order', 'Menyelesaikan order checker: JO-2409070001-S', '{\n    \"pending_import_id\": 2345,\n    \"new_order_id\": 543,\n    \"no_penjualan\": \"JO-2409070001-S\",\n    \"customer_data\": {\n        \"csrf_token\": \"f021126a6db3af2b9d6c7971b99f067ae736e8e73237ffa3100aabb3779f4f98\",\n        \"pending_import_id\": \"2345\",\n        \"complete_order\": \"1\",\n        \"nama_customer\": \"FAHMI\",\n        \"sumber_layanan\": \"WA1\",\n        \"layanan_pengiriman\": \"GO SEND INSTANT\\/SAMEDAY\",\n        \"alamat\": \"SUBANG\",\n        \"telepon_penerima\": \"085290119520\"\n    }\n}', '2025-07-02 22:03:30'),
(13, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.253.194.14\",\n    \"user_agent\": \"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/137.0.0.0 Safari\\/537.36\"\n}', '2025-07-02 22:07:20'),
(14, 1, 'delete_transaction', 'Menghapus transaksi No.Penjualan: JO-2409070001-S', '{\n    \"transaction_id\": 543,\n    \"no_penjualan\": \"JO-2409070001-S\"\n}', '2025-07-02 22:07:34'),
(15, 1, 'logout', 'User admin logged out', '{\n    \"username\": \"admin\"\n}', '2025-07-02 22:08:45'),
(16, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.253.194.14\",\n    \"user_agent\": \"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/137.0.0.0 Safari\\/537.36\"\n}', '2025-07-02 22:09:36'),
(17, 1, 'clear_all_pending', 'Membersihkan semua (109) pending orders.', '{\n    \"cleared_count\": 109\n}', '2025-07-02 22:10:03'),
(18, 1, 'import_sales', 'Import data penjualan dari file: Sample (2).xlsx', '{\n    \"file_name\": \"Sample (2).xlsx\",\n    \"imported_invoices\": 103,\n    \"imported_items\": 106,\n    \"skipped_rows\": 205,\n    \"completed_invoices_skipped\": 0\n}', '2025-07-02 22:11:11'),
(19, 1, 'clear_all_pending', 'Membersihkan semua (103) pending orders.', '{\n    \"cleared_count\": 103\n}', '2025-07-02 22:16:45'),
(20, 1, 'import_sales', 'Import data penjualan dari file: Sample.xlsx', '{\n    \"file_name\": \"Sample.xlsx\",\n    \"imported_invoices\": 110,\n    \"imported_items\": 311,\n    \"skipped_rows\": 1,\n    \"completed_invoices_skipped\": 0\n}', '2025-07-02 22:17:07'),
(21, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409070016-S', '{\n    \"no_penjualan\": \"JO-2409070016-S\"\n}', '2025-07-02 22:18:12'),
(22, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409060001-S', '{\n    \"no_penjualan\": \"JO-2409060001-S\"\n}', '2025-07-02 22:18:26'),
(23, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.253.194.14\",\n    \"user_agent\": \"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/137.0.0.0 Safari\\/537.36\"\n}', '2025-07-02 22:18:54'),
(24, 1, 'search_order_success', 'Pencarian sukses untuk No.Penjualan: JO-2409060001-S', '{\n    \"no_penjualan\": \"JO-2409060001-S\"\n}', '2025-07-02 22:19:42'),
(25, 1, 'scan_item', 'Scan item: 020305024 (Qty: +1)', '{\n    \"pending_import_id\": 2569,\n    \"item_id\": 8398,\n    \"kode_barang\": \"020305024\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:20:44'),
(26, 1, 'scan_item', 'Scan item: 160109384 (Qty: +1)', '{\n    \"pending_import_id\": 2569,\n    \"item_id\": 8399,\n    \"kode_barang\": \"160109384\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:20:50'),
(27, 1, 'scan_item', 'Scan item: 02012589 (Qty: +1)', '{\n    \"pending_import_id\": 2569,\n    \"item_id\": 8400,\n    \"kode_barang\": \"02012589\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:20:56'),
(28, 1, 'scan_item', 'Scan item: 020112971 (Qty: +1)', '{\n    \"pending_import_id\": 2569,\n    \"item_id\": 8401,\n    \"kode_barang\": \"020112971\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:21:02'),
(29, 1, 'scan_item', 'Scan item: 042001182 (Qty: +1)', '{\n    \"pending_import_id\": 2569,\n    \"item_id\": 8402,\n    \"kode_barang\": \"042001182\",\n    \"scanned_qty\": 1,\n    \"new_qty_ready\": 1,\n    \"is_completed\": true\n}', '2025-07-02 22:22:34'),
(30, 1, 'complete_order', 'Menyelesaikan order checker: JO-2409060001-S', '{\n    \"pending_import_id\": 2569,\n    \"new_order_id\": 544,\n    \"no_penjualan\": \"JO-2409060001-S\",\n    \"customer_data\": {\n        \"csrf_token\": \"e96831376818c812e74b5931133b74eff51acac90d91fbc90798745a023a3c3f\",\n        \"pending_import_id\": \"2569\",\n        \"complete_order\": \"1\",\n        \"nama_customer\": \"FAHMI\",\n        \"sumber_layanan\": \"WA1\",\n        \"layanan_pengiriman\": \"GO SEND INSTANT\\/SAMEDAY\",\n        \"alamat\": \"SUBANG\",\n        \"telepon_penerima\": \"085290119520\"\n    }\n}', '2025-07-02 22:22:56'),
(31, 1, 'logout', 'User admin logged out', '{\n    \"username\": \"admin\"\n}', '2025-07-03 02:06:00'),
(32, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.6.4.103\",\n    \"user_agent\": \"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/137.0.0.0 Mobile Safari\\/537.36\"\n}', '2025-07-03 03:23:41'),
(33, 1, 'login', 'User admin logged in', '{\n    \"username\": \"admin\",\n    \"ip_address\": \"182.253.124.188\",\n    \"user_agent\": \"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/138.0.0.0 Safari\\/537.36\"\n}', '2025-07-18 08:43:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan_pengiriman`
--

CREATE TABLE `layanan_pengiriman` (
  `id` int(11) NOT NULL,
  `nama_layanan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan_pengiriman`
--

INSERT INTO `layanan_pengiriman` (`id`, `nama_layanan`) VALUES
(1, 'GO SEND INSTANT/SAMEDAY'),
(2, 'GRAB EXPRESS INSTANT/SAMEDAY'),
(3, 'ID EXPRESS'),
(4, 'JNE REG/YES'),
(5, 'SI CEPAT REG/BEST/GOKIL'),
(6, 'ID TRUCKING'),
(7, 'BETTER YENS'),
(8, 'AMBIL DI TOKO'),
(9, 'TRAVEL'),
(10, 'POS'),
(11, 'J&T CARGO'),
(12, 'J&T EXPRESS');

-- --------------------------------------------------------

--
-- Struktur dari tabel `online_wa`
--

CREATE TABLE `online_wa` (
  `id` int(11) NOT NULL,
  `nama_customer` varchar(255) NOT NULL,
  `no_penjualan` varchar(50) NOT NULL,
  `sumber_layanan` varchar(50) NOT NULL,
  `layanan_pengiriman` varchar(50) NOT NULL,
  `alamat` text NOT NULL,
  `telepon_customer` varchar(20) DEFAULT NULL,
  `total_belanja` decimal(15,2) DEFAULT 0.00,
  `qty` int(11) NOT NULL,
  `checker` varchar(50) NOT NULL,
  `tanggal` datetime NOT NULL,
  `status_checked` enum('pending','partial','completed') DEFAULT 'pending',
  `source` enum('checker','manual') DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `online_wa`
--

INSERT INTO `online_wa` (`id`, `nama_customer`, `no_penjualan`, `sumber_layanan`, `layanan_pengiriman`, `alamat`, `telepon_customer`, `total_belanja`, `qty`, `checker`, `tanggal`, `status_checked`, `source`) VALUES
(544, 'FAHMI', 'JO-2409060001-S', 'WA1', 'GO SEND INSTANT/SAMEDAY', 'SUBANG', '085290119520', '213500.00', 5, 'admin', '2025-07-02 22:22:56', 'completed', 'checker');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `no_nota` varchar(50) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `qty_ready` int(11) NOT NULL DEFAULT 0,
  `harga_jual` decimal(15,2) NOT NULL,
  `sub_total` decimal(15,2) NOT NULL,
  `is_checked` tinyint(1) DEFAULT 0,
  `diskon_item` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `no_nota`, `kode_barang`, `barcode`, `nama_barang`, `qty`, `qty_ready`, `harga_jual`, `sub_total`, `is_checked`, `diskon_item`) VALUES
(1587, 544, 'JO-2409060001-S', '020305024', '020305024', 'Sample59', 1, 1, '64500.00', '64500.00', 1, 0),
(1588, 544, 'JO-2409060001-S', '160109384', '160109384', 'Sample60', 1, 1, '79500.00', '79500.00', 1, 0),
(1589, 544, 'JO-2409060001-S', '02012589', '02012589', 'Sample61', 1, 1, '21500.00', '21500.00', 1, 0),
(1590, 544, 'JO-2409060001-S', '020112971', '020112971', 'Sample62', 1, 1, '19500.00', '19500.00', 1, 0),
(1591, 544, 'JO-2409060001-S', '042001182', '042001182', 'Sample63', 1, 1, '28500.00', '28500.00', 1, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pending_imports`
--

CREATE TABLE `pending_imports` (
  `id` int(11) NOT NULL,
  `no_penjualan` varchar(50) NOT NULL,
  `checker` varchar(50) NOT NULL,
  `tanggal` datetime NOT NULL,
  `nama_customer` varchar(255) DEFAULT NULL,
  `sumber_layanan` varchar(100) DEFAULT NULL,
  `layanan_pengiriman` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pending_imports`
--

INSERT INTO `pending_imports` (`id`, `no_penjualan`, `checker`, `tanggal`, `nama_customer`, `sumber_layanan`, `layanan_pengiriman`, `alamat`) VALUES
(2551, 'JO-2409070008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2552, 'JO-2409070007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2553, 'JO-2409070006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2554, 'JO-2409070005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2555, 'JO-2409070004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2556, 'JO-2409070002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2557, 'JO-2409070003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2558, 'JO-2409070001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2559, 'JO-2409070017-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2560, 'JO-2409070016-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2561, 'JO-2409070015-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2562, 'JO-2409070014-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2563, 'JO-2409070013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2564, 'JO-2409070012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2565, 'JO-2409070011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2566, 'JO-2409070010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2567, 'JO-2409070009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2568, 'JO-2409060003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2570, 'JO-2409060002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2571, 'JO-2409060012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2572, 'JO-2409060015-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2573, 'JO-2409060014-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2574, 'JO-2409060004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2575, 'JO-2409060005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2576, 'JO-2409060006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2577, 'JO-2409060007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2578, 'JO-2409060008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2579, 'JO-2409060009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2580, 'JO-2409060010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2581, 'JO-2409060011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2582, 'JO-2409060013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2583, 'JO-2409050022-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2584, 'JO-2409050021-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2585, 'JO-2409050020-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2586, 'JO-2409050019-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2587, 'JO-2409050018-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2588, 'JO-2409050017-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2589, 'JO-2409050016-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2590, 'JO-2409050015-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2591, 'JO-2409050014-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2592, 'JO-2409050003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2593, 'JO-2409050009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2594, 'JO-2409050011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2595, 'JO-2409050012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2596, 'JO-2409050013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2597, 'JO-2409050010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2598, 'JO-2409050005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2599, 'JO-2409050004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2600, 'JO-2409050002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2601, 'JO-2409050008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2602, 'JO-2409050001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2603, 'JO-2409050006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2604, 'JO-2409050007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2605, 'JO-2409040006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2606, 'JO-2409040005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2607, 'JO-2409040004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2608, 'JO-2409040003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2609, 'JO-2409040002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2610, 'JO-2409040001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2611, 'JO-2409030011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2612, 'JO-2409030010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2613, 'JO-2409030006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2614, 'JO-2409030007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2615, 'JO-2409030005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2616, 'JO-2409030002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2617, 'JO-2409030003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2618, 'JO-2409030012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2619, 'JO-2409030004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2620, 'JO-2409030001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2621, 'JO-2409030013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2622, 'JO-2409030009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2623, 'JO-2409030008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2624, 'JO-2409020001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2625, 'JO-2409020018-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2626, 'JO-2409020020-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2627, 'JO-2409020023-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2628, 'JO-2409020019-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2629, 'JO-2409020021-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2630, 'JO-2409020022-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2631, 'JO-2409020024-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2632, 'JO-2409020017-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2633, 'JO-2409020016-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2634, 'JO-2409020015-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2635, 'JO-2409020012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2636, 'JO-2409020010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2637, 'JO-2409020014-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2638, 'JO-2409020003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2639, 'JO-2409020002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2640, 'JO-2409020004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2641, 'JO-2409020005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2642, 'JO-2409020006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2643, 'JO-2409020007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2644, 'JO-2409020008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2645, 'JO-2409020009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2646, 'JO-2409020011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2647, 'JO-2409020013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2648, 'JO-2409010008-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2649, 'JO-2409010007-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2650, 'JO-2409010006-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2651, 'JO-2409010005-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2652, 'JO-2409010004-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2653, 'JO-2409010002-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2654, 'JO-2409010001-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2655, 'JO-2409010003-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2656, 'JO-2409010013-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2657, 'JO-2409010012-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2658, 'JO-2409010011-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2659, 'JO-2409010010-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL),
(2660, 'JO-2409010009-S', 'admin', '2025-07-02 22:17:07', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pending_import_items`
--

CREATE TABLE `pending_import_items` (
  `id` int(11) NOT NULL,
  `pending_import_id` int(11) NOT NULL,
  `no_nota` varchar(50) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `qty_ready` int(11) NOT NULL DEFAULT 0,
  `harga_jual` decimal(15,2) NOT NULL,
  `sub_total` decimal(15,2) NOT NULL,
  `is_checked` tinyint(1) DEFAULT 0,
  `diskon_item` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pending_import_items`
--

INSERT INTO `pending_import_items` (`id`, `pending_import_id`, `no_nota`, `kode_barang`, `barcode`, `nama_barang`, `qty`, `qty_ready`, `harga_jual`, `sub_total`, `is_checked`, `diskon_item`) VALUES
(8340, 2551, 'JO-2409070008-S', '43000005', '43000005', 'Sample1', 50, 0, '0.00', '0.00', 0, 0),
(8341, 2551, 'JO-2409070008-S', '43000004', '43000004', 'Sample2', 100, 0, '0.00', '0.00', 0, 0),
(8342, 2551, 'JO-2409070008-S', '43000003', '43000003', 'Sample3', 100, 0, '0.00', '0.00', 0, 0),
(8343, 2552, 'JO-2409070007-S', '08015144', '189686648024', 'Sample4', 7, 0, '8900.00', '62300.00', 0, 0),
(8344, 2552, 'JO-2409070007-S', '0300754', '089686648003', 'Sample5', 1, 0, '8900.00', '8900.00', 0, 0),
(8345, 2552, 'JO-2409070007-S', '03000125', '8993531774279', 'Sample6', 4, 0, '38500.00', '154000.00', 0, 0),
(8346, 2553, 'JO-2409070006-S', '0300761', '4103040426017', 'Sample7', 1, 0, '69500.00', '69500.00', 0, 0),
(8347, 2553, 'JO-2409070006-S', '11000160', '4891228305345', 'Sample8', 1, 0, '141500.00', '65900.00', 0, 75600),
(8348, 2553, 'JO-2409070006-S', '11000159', '4891228305338', 'Sample9', 5, 0, '141500.00', '329500.00', 0, 378000),
(8349, 2554, 'JO-2409070005-S', '08030251', '8997213460627', 'Sample10', 2, 0, '36500.00', '73000.00', 0, 0),
(8350, 2554, 'JO-2409070005-S', '08020996', '8997020780635', 'Sample11', 1, 0, '120000.00', '120000.00', 0, 0),
(8351, 2554, 'JO-2409070005-S', '0300046', '8991111102917', 'Sample12', 1, 0, '54500.00', '54500.00', 0, 0),
(8352, 2554, 'JO-2409070005-S', '0300359', '8992771002333', 'Sample13', 1, 0, '29500.00', '29500.00', 0, 0),
(8353, 2554, 'JO-2409070005-S', '0100932', '4987072022306', 'Sample14', 4, 0, '5500.00', '18000.00', 0, 4000),
(8354, 2554, 'JO-2409070005-S', '0300318', '4971032989242', 'Sample15', 2, 0, '46500.00', '93000.00', 0, 0),
(8355, 2554, 'JO-2409070005-S', '0201522', '8992771002975', 'Sample16', 2, 0, '20900.00', '41800.00', 0, 0),
(8356, 2554, 'JO-2409070005-S', '0101029', '8993189320767', 'Sample17', 4, 0, '20500.00', '82000.00', 0, 0),
(8357, 2554, 'JO-2409070005-S', '0308884', '8993102697341', 'Sample18', 2, 0, '30500.00', '61000.00', 0, 0),
(8358, 2554, 'JO-2409070005-S', '0100925', '8998103002842', 'Sample19', 3, 0, '18500.00', '55500.00', 0, 0),
(8359, 2554, 'JO-2409070005-S', '8260013', '8993417227813', 'Sample20', 2, 0, '37500.00', '75000.00', 0, 0),
(8360, 2554, 'JO-2409070005-S', '0300631', '8993189700361', 'Sample21', 4, 0, '116500.00', '466000.00', 0, 0),
(8361, 2555, 'JO-2409070004-S', '16081130', '16081130', 'Sample22', 1, 0, '107000.00', '107000.00', 0, 0),
(8362, 2556, 'JO-2409070002-S', '08020996', '8997020780635', 'Sample23', 1, 0, '120000.00', '120000.00', 0, 0),
(8363, 2556, 'JO-2409070002-S', '8260013', '8993417227813', 'Sample24', 2, 0, '37500.00', '75000.00', 0, 0),
(8364, 2557, 'JO-2409070003-S', '0302110', '8992694247521', 'Sample25', 1, 0, '34500.00', '34500.00', 0, 0),
(8365, 2557, 'JO-2409070003-S', '0300202', '8999999034061', 'Sample26', 1, 0, '34500.00', '34500.00', 0, 0),
(8366, 2558, 'JO-2409070001-S', '11000322', '8999908764300', 'Sample27', 1, 0, '30500.00', '28500.00', 0, 2000),
(8367, 2558, 'JO-2409070001-S', '03091061', '6972602180562', 'Sample28', 5, 0, '8500.00', '42500.00', 0, 0),
(8368, 2559, 'JO-2409070017-S', '030901110', '4901121587707', 'Sample29', 3, 0, '264500.00', '793500.00', 0, 0),
(8369, 2560, 'JO-2409070016-S', '08031116', '5099864016475', 'Sample30', 96, 0, '12500.00', '1200000.00', 0, 0),
(8370, 2561, 'JO-2409070015-S', '041103132', '041103132', 'Sample31', 1, 0, '76500.00', '76500.00', 0, 0),
(8371, 2561, 'JO-2409070015-S', '041900877', '041900877', 'Sample32', 1, 0, '46500.00', '46500.00', 0, 0),
(8372, 2561, 'JO-2409070015-S', '16051222', '0735745021903L', 'Sample33', 1, 0, '129900.00', '129900.00', 0, 0),
(8373, 2561, 'JO-2409070015-S', '16051221', '0735745021927L', 'Sample34', 1, 0, '89900.00', '89900.00', 0, 0),
(8374, 2561, 'JO-2409070015-S', '0202641', '8993365030404', 'Sample35', 1, 0, '14500.00', '14500.00', 0, 0),
(8375, 2561, 'JO-2409070015-S', '12000539', '8801441008448', 'Sample36', 1, 0, '44500.00', '44500.00', 0, 0),
(8376, 2561, 'JO-2409070015-S', '080105372', '8997227660037', 'Sample37', 1, 0, '36500.00', '36500.00', 0, 0),
(8377, 2562, 'JO-2409070014-S', '160501243', '5391530690980-LAMA', 'Sample38', 1, 0, '84000.00', '84000.00', 0, 0),
(8378, 2563, 'JO-2409070013-S', '040202666', '040202666', 'Sample39', 1, 0, '63000.00', '62370.00', 0, 630),
(8379, 2563, 'JO-2409070013-S', '090108749', '090108749', 'Sample40', 1, 0, '106500.00', '105435.00', 0, 1065),
(8380, 2563, 'JO-2409070013-S', '040111601', '040111601', 'Sample41', 1, 0, '34000.00', '33660.00', 0, 340),
(8381, 2563, 'JO-2409070013-S', '4600585', '4600585', 'Sample42', 2, 0, '4500.00', '8910.00', 0, 90),
(8382, 2564, 'JO-2409070012-S', '16031420', '16031420', 'Sample43', 1, 0, '179900.00', '179900.00', 0, 0),
(8383, 2564, 'JO-2409070012-S', '31000005', '8993531773005', 'Sample44', 1, 0, '34500.00', '34500.00', 0, 0),
(8384, 2564, 'JO-2409070012-S', '0309046', '089686700015', 'Sample45', 1, 0, '10500.00', '10500.00', 0, 0),
(8385, 2564, 'JO-2409070012-S', '0300190', '089686700114', 'Sample46', 1, 0, '10500.00', '10500.00', 0, 0),
(8386, 2564, 'JO-2409070012-S', '100201461', '100201461', 'Sample47', 1, 0, '38500.00', '38500.00', 0, 0),
(8387, 2564, 'JO-2409070012-S', '08014826', '8997204590142', 'Sample48', 1, 0, '24900.00', '24900.00', 0, 0),
(8388, 2564, 'JO-2409070012-S', '08015144', '189686648024', 'Sample49', 1, 0, '8900.00', '8900.00', 0, 0),
(8389, 2564, 'JO-2409070012-S', '100102300', '100102300', 'Sample50', 1, 0, '59500.00', '59500.00', 0, 0),
(8390, 2564, 'JO-2409070012-S', '16081077', '16081077', 'Sample51', 1, 0, '43000.00', '43000.00', 0, 0),
(8391, 2564, 'JO-2409070012-S', '4600585', '4600585', 'Sample52', 1, 0, '4500.00', '4500.00', 0, 0),
(8392, 2565, 'JO-2409070011-S', '080105443', '3073781138436', 'Sample53', 1, 0, '49500.00', '49500.00', 0, 0),
(8393, 2565, 'JO-2409070011-S', '080105359', '3073781138450', 'Sample54', 1, 0, '49500.00', '49500.00', 0, 0),
(8394, 2565, 'JO-2409070011-S', '080201074', '8997016511588', 'Sample55', 1, 0, '322500.00', '312500.00', 0, 10000),
(8395, 2566, 'JO-2409070010-S', '20010867', '20010867', 'Sample56', 41, 0, '0.00', '0.00', 0, 0),
(8396, 2567, 'JO-2409070009-S', '20010998', '20010998', 'Sample57', 80, 0, '0.00', '0.00', 0, 0),
(8397, 2568, 'JO-2409060003-S', '0701395', '8995084900570', 'Sample58', 1, 0, '65500.00', '65500.00', 0, 0),
(8403, 2570, 'JO-2409060002-S', '08015144', '189686648024', 'Sample64', 2, 0, '8900.00', '17622.00', 0, 178),
(8404, 2570, 'JO-2409060002-S', '0300754', '089686648003', 'Sample72', 2, 0, '8900.00', '17622.00', 0, 178),
(8405, 2570, 'JO-2409060002-S', '08015177', '8993531775078', 'Sample73', 1, 0, '35500.00', '35145.00', 0, 355),
(8406, 2571, 'JO-2409060012-S', '080201074', '8997016511588', 'Sample65', 1, 0, '322500.00', '312500.00', 0, 10000),
(8407, 2572, 'JO-2409060015-S', '160901507', '160901507', 'Sample66', 1, 0, '55000.00', '55000.00', 0, 0),
(8408, 2573, 'JO-2409060014-S', '040110756', '040110756', 'Sample67', 1, 0, '92500.00', '92500.00', 0, 0),
(8409, 2573, 'JO-2409060014-S', '03300078', '8993586524287', 'Sample68', 1, 0, '21500.00', '21500.00', 0, 0),
(8410, 2573, 'JO-2409060014-S', '01030895', '8997212420806', 'Sample69', 1, 0, '49000.00', '49000.00', 0, 0),
(8411, 2573, 'JO-2409060014-S', '160801498', '160801498', 'Sample70', 1, 0, '172500.00', '172500.00', 0, 0),
(8412, 2573, 'JO-2409060014-S', '03140359', '03140359', 'Sample71', 1, 0, '13500.00', '13500.00', 0, 0),
(8413, 2574, 'JO-2409060004-S', '0308502', '8851111400430', 'Sample74', 1, 0, '124900.00', '124900.00', 0, 0),
(8414, 2574, 'JO-2409060004-S', '02010973', '02010973', 'Sample75', 3, 0, '12500.00', '37500.00', 0, 0),
(8415, 2574, 'JO-2409060004-S', '02090189', '02090189', 'Sample76', 3, 0, '12500.00', '37500.00', 0, 0),
(8416, 2574, 'JO-2409060004-S', '02011030', '02011030', 'Sample77', 1, 0, '13500.00', '13500.00', 0, 0),
(8417, 2574, 'JO-2409060004-S', '02011027', '02011027', 'Sample78', 1, 0, '13500.00', '13500.00', 0, 0),
(8418, 2574, 'JO-2409060004-S', '02012272', '02012272', 'Sample79', 1, 0, '13500.00', '13500.00', 0, 0),
(8419, 2574, 'JO-2409060004-S', '02012271', '02012271', 'Sample80', 1, 0, '13500.00', '13500.00', 0, 0),
(8420, 2575, 'JO-2409060005-S', '13141057', '13141057', 'Sample81', 1, 0, '182900.00', '181071.00', 0, 1829),
(8421, 2575, 'JO-2409060005-S', '030601139', '030601139', 'Sample82', 1, 0, '64500.00', '63855.00', 0, 645),
(8422, 2575, 'JO-2409060005-S', '040501985', '040501985', 'Sample83', 1, 0, '93500.00', '92565.00', 0, 935),
(8423, 2575, 'JO-2409060005-S', '16110125', '8994346105500', 'Sample84', 1, 0, '9900.00', '9801.00', 0, 99),
(8424, 2575, 'JO-2409060005-S', '4600657', '4600657', 'Sample85', 2, 0, '6500.00', '12870.00', 0, 130),
(8425, 2575, 'JO-2409060005-S', '4600585', '4600585', 'Sample86', 2, 0, '4500.00', '8910.00', 0, 90),
(8426, 2576, 'JO-2409060006-S', '020305005', '020305005', 'Sample87', 1, 0, '41500.00', '41500.00', 0, 0),
(8427, 2576, 'JO-2409060006-S', '0302642', '8991111102719', 'Sample88', 1, 0, '24500.00', '22050.00', 0, 2450),
(8428, 2577, 'JO-2409060007-S', '080201074', '8997016511588', 'Sample89', 1, 0, '322500.00', '312500.00', 0, 10000),
(8429, 2577, 'JO-2409060007-S', '0300614', '8993189700378', 'Sample90', 1, 0, '121500.00', '121500.00', 0, 0),
(8430, 2578, 'JO-2409060008-S', '16041446', '16041446', 'Sample91', 1, 0, '154500.00', '154500.00', 0, 0),
(8431, 2578, 'JO-2409060008-S', '100102300', '100102300', 'Sample92', 1, 0, '59500.00', '59500.00', 0, 0),
(8432, 2578, 'JO-2409060008-S', '160110482', '160110482', 'Sample93', 1, 0, '66500.00', '66500.00', 0, 0),
(8433, 2578, 'JO-2409060008-S', '030501039', '030501039', 'Sample94', 1, 0, '79000.00', '79000.00', 0, 0),
(8434, 2578, 'JO-2409060008-S', '4600657', '4600657', 'Sample95', 1, 0, '6500.00', '6500.00', 0, 0),
(8435, 2578, 'JO-2409060008-S', '08014567', '8993531774705', 'Sample96', 1, 0, '34500.00', '34500.00', 0, 0),
(8436, 2578, 'JO-2409060008-S', '03190043', '8997232648648', 'Sample97', 1, 0, '26500.00', '26500.00', 0, 0),
(8437, 2578, 'JO-2409060008-S', '03155167', '03155167', 'Sample98', 1, 0, '57500.00', '57500.00', 0, 0),
(8438, 2579, 'JO-2409060009-S', '06010722', '06010722', 'Sample99', 1, 0, '34500.00', '34500.00', 0, 0),
(8439, 2580, 'JO-2409060010-S', '030601154', '8058664153749', 'Sample100', 1, 0, '139000.00', '139000.00', 0, 0),
(8440, 2580, 'JO-2409060010-S', '042300931', '042300931', 'Sample101', 1, 0, '188000.00', '188000.00', 0, 0),
(8441, 2580, 'JO-2409060010-S', '0100295', '8997226330016', 'Sample102', 2, 0, '15900.00', '31800.00', 0, 0),
(8442, 2580, 'JO-2409060010-S', '10060645', '10060645', 'Sample103', 1, 0, '78500.00', '78500.00', 0, 0),
(8443, 2581, 'JO-2409060011-S', '040502019', '040502019', 'Sample104', 3, 0, '47500.00', '142500.00', 0, 0),
(8444, 2581, 'JO-2409060011-S', '160109683', '160109683', 'Sample105', 1, 0, '82500.00', '82500.00', 0, 0),
(8445, 2581, 'JO-2409060011-S', '160109696', '160109696', 'Sample106', 1, 0, '82500.00', '82500.00', 0, 0),
(8446, 2582, 'JO-2409060013-S', '04140395', '04140395', 'Sample107', 2, 0, '94500.00', '189000.00', 0, 0),
(8447, 2583, 'JO-2409050022-S', '020304839', '020304839', 'Sample108', 1, 0, '58500.00', '58500.00', 0, 0),
(8448, 2583, 'JO-2409050022-S', '020403289', '020403289', 'Sample109', 2, 0, '43500.00', '87000.00', 0, 0),
(8449, 2584, 'JO-2409050021-S', '0120299', '8993586524669', 'Sample110', 1, 0, '28900.00', '28900.00', 0, 0),
(8450, 2584, 'JO-2409050021-S', '160501281', '8859520905010', 'Sample111', 1, 0, '64500.00', '64500.00', 0, 0),
(8451, 2585, 'JO-2409050020-S', '03260115', '03260115', 'Sample112', 1, 0, '199500.00', '197505.00', 0, 1995),
(8452, 2586, 'JO-2409050019-S', '13091012', '13091012', 'Sample113', 1, 0, '831500.00', '831500.00', 0, 0),
(8453, 2586, 'JO-2409050019-S', '4600657', '4600657', 'Sample114', 3, 0, '6500.00', '19500.00', 0, 0),
(8454, 2587, 'JO-2409050018-S', '03091064', '6974180098826', 'Sample115', 2, 0, '156500.00', '313000.00', 0, 0),
(8455, 2588, 'JO-2409050017-S', '020304777', '020304777', 'Sample116', 3, 0, '46500.00', '139500.00', 0, 0),
(8456, 2588, 'JO-2409050017-S', '020304783', '020304783', 'Sample117', 3, 0, '49500.00', '148500.00', 0, 0),
(8457, 2589, 'JO-2409050016-S', '120403855', '120403855', 'Sample118', 2, 0, '32500.00', '65000.00', 0, 0),
(8458, 2589, 'JO-2409050016-S', '0600300', '8992771004290', 'Sample119', 2, 0, '21900.00', '43800.00', 0, 0),
(8459, 2589, 'JO-2409050016-S', '16051223', '0735745021910L', 'Sample120', 1, 0, '149900.00', '99999.00', 0, 49901),
(8460, 2589, 'JO-2409050016-S', '0101024', '8992800784001', 'Sample121', 2, 0, '24900.00', '49800.00', 0, 0),
(8461, 2589, 'JO-2409050016-S', '010700867', '0761373075025', 'Sample122', 2, 0, '29900.00', '59800.00', 0, 0),
(8462, 2589, 'JO-2409050016-S', '011600114', '0761373075018', 'Sample123', 2, 0, '29900.00', '59800.00', 0, 0),
(8463, 2589, 'JO-2409050016-S', '03091028', '6974180093067', 'Sample124', 1, 0, '106500.00', '106500.00', 0, 0),
(8464, 2590, 'JO-2409050015-S', '0200155', '7038513866304', 'Sample125', 1, 0, '26500.00', '26235.00', 0, 265),
(8465, 2590, 'JO-2409050015-S', '011500125', '9555019004552', 'Sample126', 1, 0, '31500.00', '31185.00', 0, 315),
(8466, 2590, 'JO-2409050015-S', '100102336', '100102336', 'Sample127', 1, 0, '49500.00', '49005.00', 0, 495),
(8467, 2590, 'JO-2409050015-S', '030901096', '8990052002638', 'Sample128', 1, 0, '86500.00', '69800.00', 0, 16700),
(8468, 2591, 'JO-2409050014-S', '041200774', '041200774', 'Sample129', 1, 0, '86500.00', '86500.00', 0, 0),
(8469, 2591, 'JO-2409050014-S', '041102823', '041102823', 'Sample130', 1, 0, '134500.00', '134500.00', 0, 0),
(8470, 2591, 'JO-2409050014-S', '042001144', '042001144', 'Sample131', 1, 0, '17500.00', '17500.00', 0, 0),
(8471, 2591, 'JO-2409050014-S', '042001143', '042001143', 'Sample146', 1, 0, '17500.00', '17500.00', 0, 0),
(8472, 2592, 'JO-2409050003-S', '43000004', '43000004', 'Sample132', 87, 0, '0.00', '0.00', 0, 0),
(8473, 2592, 'JO-2409050003-S', '43000005', '43000005', 'Sample133', 76, 0, '0.00', '0.00', 0, 0),
(8474, 2592, 'JO-2409050003-S', '43000003', '43000003', 'Sample147', 150, 0, '0.00', '0.00', 0, 0),
(8475, 2593, 'JO-2409050009-S', '09017474', '09017474', 'Sample134', 2, 0, '69000.00', '138000.00', 0, 0),
(8476, 2594, 'JO-2409050011-S', '110103017', '110103017', 'Sample135', 1, 0, '139500.00', '139500.00', 0, 0),
(8477, 2594, 'JO-2409050011-S', '4600585', '4600585', 'Sample136', 1, 0, '4500.00', '4500.00', 0, 0),
(8478, 2595, 'JO-2409050012-S', '120403855', '120403855', 'Sample137', 1, 0, '32500.00', '32500.00', 0, 0),
(8479, 2595, 'JO-2409050012-S', '010101175', '8994457570037', 'Sample138', 1, 0, '84900.00', '84900.00', 0, 0),
(8480, 2595, 'JO-2409050012-S', '033000162', '033000162', 'Sample139', 1, 0, '19500.00', '19500.00', 0, 0),
(8481, 2595, 'JO-2409050012-S', '13141057', '13141057', 'Sample140', 1, 0, '182500.00', '182500.00', 0, 0),
(8482, 2595, 'JO-2409050012-S', '020901214', '020901214', 'Sample141', 4, 0, '13500.00', '54000.00', 0, 0),
(8483, 2596, 'JO-2409050013-S', '041102782', '041102782', 'Sample142', 1, 0, '156500.00', '156500.00', 0, 0),
(8484, 2596, 'JO-2409050013-S', '040303122', '040303122', 'Sample143', 1, 0, '89500.00', '89500.00', 0, 0),
(8485, 2596, 'JO-2409050013-S', '041900877', '041900877', 'Sample144', 1, 0, '46500.00', '46500.00', 0, 0),
(8486, 2596, 'JO-2409050013-S', '041900875', '041900875', 'Sample145', 1, 0, '46500.00', '46500.00', 0, 0),
(8487, 2597, 'JO-2409050010-S', '08015143', '189686648017', 'Sample148', 1, 0, '8900.00', '8900.00', 0, 0),
(8488, 2597, 'JO-2409050010-S', '0300754', '089686648003', 'Sample149', 1, 0, '8900.00', '8900.00', 0, 0),
(8489, 2597, 'JO-2409050010-S', '08015144', '189686648024', 'Sample150', 1, 0, '8900.00', '8900.00', 0, 0),
(8490, 2597, 'JO-2409050010-S', '3A000009', '089686621013', 'Sample151', 1, 0, '14900.00', '14900.00', 0, 0),
(8491, 2597, 'JO-2409050010-S', '08015063', '8997211250565', 'Sample152', 1, 0, '24500.00', '24500.00', 0, 0),
(8492, 2597, 'JO-2409050010-S', '0105556', '8998103013657', 'Sample153', 1, 0, '38500.00', '38500.00', 0, 0),
(8493, 2597, 'JO-2409050010-S', '0306333', '8999908284907', 'Sample154', 1, 0, '24500.00', '24500.00', 0, 0),
(8494, 2598, 'JO-2409050005-S', '160501272', '8997205641713', 'Sample155', 1, 0, '49900.00', '49900.00', 0, 0),
(8495, 2598, 'JO-2409050005-S', '03091076', '8992959002049', 'Sample156', 1, 0, '92500.00', '83250.00', 0, 9250),
(8496, 2599, 'JO-2409050004-S', '43000004', '43000004', 'Sample157', 153, 0, '0.00', '0.00', 0, 0),
(8497, 2599, 'JO-2409050004-S', '43000003', '43000003', 'Sample158', 150, 0, '0.00', '0.00', 0, 0),
(8498, 2600, 'JO-2409050002-S', '43000005', '43000005', 'Sample159', 25, 0, '0.00', '0.00', 0, 0),
(8499, 2600, 'JO-2409050002-S', '43000004', '43000004', 'Sample160', 93, 0, '0.00', '0.00', 0, 0),
(8500, 2600, 'JO-2409050002-S', '43000003', '43000003', 'Sample161', 150, 0, '0.00', '0.00', 0, 0),
(8501, 2600, 'JO-2409050002-S', '43000001', '43000001', 'Sample162', 100, 0, '0.00', '0.00', 0, 0),
(8502, 2601, 'JO-2409050008-S', '010101175', '8994457570037', 'Sample163', 1, 0, '84900.00', '84900.00', 0, 0),
(8503, 2602, 'JO-2409050001-S', '08014988', '8997217870248', 'Sample164', 1, 0, '19900.00', '19900.00', 0, 0),
(8504, 2603, 'JO-2409050006-S', '041103157', '041103157', 'Sample165', 1, 0, '99500.00', '99500.00', 0, 0),
(8505, 2603, 'JO-2409050006-S', '0300745', '8993102681241', 'Sample166', 2, 0, '38500.00', '77000.00', 0, 0),
(8506, 2603, 'JO-2409050006-S', '0701975', '8995084901119', 'Sample167', 1, 0, '78500.00', '78500.00', 0, 0),
(8507, 2603, 'JO-2409050006-S', '01091059', '8997240890824', 'Sample168', 1, 0, '60000.00', '60000.00', 0, 0),
(8508, 2603, 'JO-2409050006-S', '01011014', '8994591090026', 'Sample169', 1, 0, '68500.00', '68500.00', 0, 0),
(8509, 2603, 'JO-2409050006-S', '010901139', '8997240891289', 'Sample170', 1, 0, '70000.00', '70000.00', 0, 0),
(8510, 2603, 'JO-2409050006-S', '0209134', '8993102697525', 'Sample171', 1, 0, '38500.00', '38500.00', 0, 0),
(8511, 2603, 'JO-2409050006-S', '0100975', '8998103007243', 'Sample172', 5, 0, '18500.00', '92500.00', 0, 0),
(8512, 2604, 'JO-2409050007-S', '03091028', '6974180093067', 'Sample173', 1, 0, '106500.00', '106500.00', 0, 0),
(8513, 2604, 'JO-2409050007-S', '03091027', '6974180090059', 'Sample174', 1, 0, '106500.00', '106500.00', 0, 0),
(8514, 2604, 'JO-2409050007-S', '01070830', '4800136111054', 'Sample175', 1, 0, '72500.00', '72500.00', 0, 0),
(8515, 2605, 'JO-2409040006-S', '05012615', '05012615', 'Sample176', 2, 0, '9500.00', '19000.00', 0, 0),
(8516, 2605, 'JO-2409040006-S', '020304471', '020304471', 'Sample177', 1, 0, '42500.00', '42500.00', 0, 0),
(8517, 2605, 'JO-2409040006-S', '01091057', '8997240890459', 'Sample178', 1, 0, '65000.00', '65000.00', 0, 0),
(8518, 2606, 'JO-2409040005-S', '080301157', '8712045019443', 'Sample179', 1, 0, '262500.00', '262500.00', 0, 0),
(8519, 2607, 'JO-2409040004-S', '033000161', '033000161', 'Sample180', 3, 0, '19500.00', '58500.00', 0, 0),
(8520, 2607, 'JO-2409040004-S', '161400232', '161400232', 'Sample181', 1, 0, '98000.00', '98000.00', 0, 0),
(8521, 2608, 'JO-2409040003-S', '0300719', '8997001680640', 'Sample182', 1, 0, '22500.00', '22500.00', 0, 0),
(8522, 2608, 'JO-2409040003-S', '080105424', '8993883950543', 'Sample183', 1, 0, '19500.00', '19500.00', 0, 0),
(8523, 2608, 'JO-2409040003-S', '08014567', '8993531774705', 'Sample184', 1, 0, '34500.00', '34500.00', 0, 0),
(8524, 2608, 'JO-2409040003-S', '042001183', '042001183', 'Sample185', 1, 0, '28500.00', '28500.00', 0, 0),
(8525, 2608, 'JO-2409040003-S', '042001182', '042001182', 'Sample186', 1, 0, '28500.00', '28500.00', 0, 0),
(8526, 2609, 'JO-2409040002-S', '16071300', '16071300', 'Sample187', 2, 0, '79000.00', '158000.00', 0, 0),
(8527, 2610, 'JO-2409040001-S', '16071259', '16071259', 'Sample188', 2, 0, '49000.00', '98000.00', 0, 0),
(8528, 2611, 'JO-2409030011-S', '0300101', '8992694242113', 'Sample189', 1, 0, '22500.00', '22500.00', 0, 0),
(8529, 2611, 'JO-2409030011-S', '03091064', '6974180098826', 'Sample190', 1, 0, '156500.00', '156500.00', 0, 0),
(8530, 2611, 'JO-2409030011-S', '0301504', '9556006060001', 'Sample217', 1, 0, '21900.00', '19710.00', 0, 2190),
(8531, 2611, 'JO-2409030011-S', '0305215', '8992694246173', 'Sample218', 1, 0, '39900.00', '39900.00', 0, 0),
(8532, 2611, 'JO-2409030011-S', '01030891', '8997212420592', 'Sample219', 1, 0, '49000.00', '49000.00', 0, 0),
(8533, 2611, 'JO-2409030011-S', '08015177', '8993531775078', 'Sample220', 1, 0, '35500.00', '35500.00', 0, 0),
(8534, 2612, 'JO-2409030010-S', '54000003', '62XH04-26', 'Sample191', 1, 0, '117500.00', '117500.00', 0, 0),
(8535, 2612, 'JO-2409030010-S', '090303086', '090303086', 'Sample192', 1, 0, '87500.00', '87500.00', 0, 0),
(8536, 2612, 'JO-2409030010-S', '12051109', '12051109', 'Sample193', 1, 0, '24500.00', '24500.00', 0, 0),
(8537, 2612, 'JO-2409030010-S', '4600657', '4600657', 'Sample194', 1, 0, '6500.00', '6500.00', 0, 0),
(8538, 2613, 'JO-2409030006-S', '08015177', '8993531775078', 'Sample195', 1, 0, '35500.00', '35500.00', 0, 0),
(8539, 2613, 'JO-2409030006-S', '0300734', '089686621020', 'Sample196', 3, 0, '14900.00', '44700.00', 0, 0),
(8540, 2613, 'JO-2409030006-S', '3A000009', '089686621013', 'Sample200', 3, 0, '14900.00', '44700.00', 0, 0),
(8541, 2613, 'JO-2409030006-S', '03091064', '6974180098826', 'Sample201', 1, 0, '156500.00', '156500.00', 0, 0),
(8542, 2614, 'JO-2409030007-S', '291500221', '6940087032669', 'Sample197', 1, 0, '46500.00', '46500.00', 0, 0),
(8543, 2614, 'JO-2409030007-S', '112800156', '112800156', 'Sample198', 1, 0, '69500.00', '69500.00', 0, 0),
(8544, 2614, 'JO-2409030007-S', '160109676', '160109676', 'Sample199', 1, 0, '89500.00', '89500.00', 0, 0),
(8545, 2615, 'JO-2409030005-S', '020304990', '020304990', 'Sample202', 1, 0, '43500.00', '43500.00', 0, 0),
(8546, 2615, 'JO-2409030005-S', '020304993', '020304993', 'Sample203', 1, 0, '33500.00', '33500.00', 0, 0),
(8547, 2615, 'JO-2409030005-S', '010700864', '8994452910012', 'Sample204', 1, 0, '46900.00', '46900.00', 0, 0),
(8548, 2615, 'JO-2409030005-S', '01091059', '8997240890824', 'Sample205', 1, 0, '60000.00', '60000.00', 0, 0),
(8549, 2615, 'JO-2409030005-S', '0301105', '8999908596901', 'Sample206', 1, 0, '16900.00', '13400.00', 0, 3500),
(8550, 2616, 'JO-2409030002-S', '0301200', '8991038772194', 'Sample207', 10, 0, '10500.00', '105000.00', 0, 0),
(8551, 2617, 'JO-2409030003-S', '0300170', '4901301508911', 'Sample208', 2, 0, '136500.00', '273000.00', 0, 0),
(8552, 2617, 'JO-2409030003-S', '112800152', '112800152', 'Sample209', 1, 0, '62500.00', '62500.00', 0, 0),
(8553, 2617, 'JO-2409030003-S', '160112127', '160112127', 'Sample210', 1, 0, '84500.00', '84500.00', 0, 0),
(8554, 2618, 'JO-2409030012-S', '11000203', '8997020781052', 'Sample211', 1, 0, '32500.00', '32500.00', 0, 0),
(8555, 2618, 'JO-2409030012-S', '01100265', '8997020781076', 'Sample212', 1, 0, '32500.00', '32500.00', 0, 0),
(8556, 2619, 'JO-2409030004-S', '36000158', '36000158', 'Sample213', 1, 0, '182500.00', '91250.00', 0, 91250),
(8557, 2620, 'JO-2409030001-S', '030103212', '030103212', 'Sample214', 2, 0, '114500.00', '229000.00', 0, 0),
(8558, 2620, 'JO-2409030001-S', '4600585', '4600585', 'Sample215', 2, 0, '4500.00', '9000.00', 0, 0),
(8559, 2621, 'JO-2409030013-S', '080105389', '8997227860697', 'Sample216', 2, 0, '36500.00', '73000.00', 0, 0),
(8560, 2622, 'JO-2409030009-S', '01011075', '8999908207500', 'Sample221', 1, 0, '14500.00', '14500.00', 0, 0),
(8561, 2622, 'JO-2409030009-S', '4600585', '4600585', 'Sample222', 1, 0, '4500.00', '4500.00', 0, 0),
(8562, 2622, 'JO-2409030009-S', '100102333', '100102333', 'Sample223', 1, 0, '63500.00', '63500.00', 0, 0),
(8563, 2623, 'JO-2409030008-S', '010700864', '8994452910012', 'Sample224', 1, 0, '46900.00', '46900.00', 0, 0),
(8564, 2623, 'JO-2409030008-S', '36000158', '36000158', 'Sample225', 1, 0, '182500.00', '91250.00', 0, 91250),
(8565, 2624, 'JO-2409020001-S', '030501032', '7237842212015', 'Sample226', 6, 0, '35900.00', '215400.00', 0, 0),
(8566, 2625, 'JO-2409020018-S', '290101197', '290101197', 'Sample227', 1, 0, '198500.00', '198500.00', 0, 0),
(8567, 2626, 'JO-2409020020-S', '010101175', '8994457570037', 'Sample228', 1, 0, '84900.00', '84900.00', 0, 0),
(8568, 2626, 'JO-2409020020-S', '01070835', '4800136111047', 'Sample229', 1, 0, '30500.00', '30500.00', 0, 0),
(8569, 2626, 'JO-2409020020-S', '03120187', '03120187', 'Sample230', 1, 0, '28500.00', '28500.00', 0, 0),
(8570, 2627, 'JO-2409020023-S', '09017369', '09017369', 'Sample231', 1, 0, '164500.00', '164500.00', 0, 0),
(8571, 2628, 'JO-2409020019-S', '13061129', '13061129', 'Sample232', 1, 0, '336900.00', '336900.00', 0, 0),
(8572, 2629, 'JO-2409020021-S', '4600657', '4600657', 'Sample233', 2, 0, '6500.00', '13000.00', 0, 0),
(8573, 2630, 'JO-2409020022-S', '12000559', '7512531271024', 'Sample234', 2, 0, '18500.00', '37000.00', 0, 0),
(8574, 2631, 'JO-2409020024-S', '13141114', '13141114', 'Sample235', 1, 0, '119900.00', '119900.00', 0, 0),
(8575, 2632, 'JO-2409020017-S', '0306333', '8999908284907', 'Sample236', 6, 0, '24500.00', '147000.00', 0, 0),
(8576, 2632, 'JO-2409020017-S', '01070841', '8999908929105', 'Sample237', 1, 0, '22500.00', '22500.00', 0, 0),
(8577, 2632, 'JO-2409020017-S', '010700903', '8999908977403', 'Sample238', 1, 0, '22500.00', '22500.00', 0, 0),
(8578, 2633, 'JO-2409020016-S', '0308943', '8992802512091', 'Sample239', 1, 0, '17900.00', '17900.00', 0, 0),
(8579, 2633, 'JO-2409020016-S', '080105346', '089686530339', 'Sample240', 1, 0, '15900.00', '15900.00', 0, 0),
(8580, 2633, 'JO-2409020016-S', '0300878', '089686530322', 'Sample241', 1, 0, '15900.00', '15900.00', 0, 0),
(8581, 2633, 'JO-2409020016-S', '0300067', '8992802016636', 'Sample242', 1, 0, '19500.00', '19500.00', 0, 0),
(8582, 2633, 'JO-2409020016-S', '080105379', '8993531775269', 'Sample243', 1, 0, '52500.00', '52500.00', 0, 0),
(8583, 2633, 'JO-2409020016-S', '08014680', '8993531772855', 'Sample244', 1, 0, '42900.00', '42900.00', 0, 0),
(8584, 2633, 'JO-2409020016-S', '290600614', '290600614', 'Sample245', 1, 0, '154000.00', '154000.00', 0, 0),
(8585, 2633, 'JO-2409020016-S', '01070771', '3504105034313', 'Sample246', 1, 0, '499500.00', '469500.00', 0, 30000),
(8586, 2634, 'JO-2409020015-S', '0200808', '9318637070510', 'Sample247', 1, 0, '74500.00', '74500.00', 0, 0),
(8587, 2635, 'JO-2409020012-S', '08015177', '8993531775078', 'Sample248', 1, 0, '35500.00', '35500.00', 0, 0),
(8588, 2635, 'JO-2409020012-S', '030901097', '8990052002645', 'Sample249', 2, 0, '86500.00', '145600.00', 0, 27400),
(8589, 2636, 'JO-2409020010-S', '041103069', '041103069', 'Sample250', 1, 0, '69500.00', '69500.00', 0, 0),
(8590, 2636, 'JO-2409020010-S', '041714982', '041714982', 'Sample251', 1, 0, '63500.00', '63500.00', 0, 0),
(8591, 2637, 'JO-2409020014-S', '100401121', '100401121', 'Sample252', 1, 0, '139500.00', '139500.00', 0, 0),
(8592, 2638, 'JO-2409020003-S', '0300573', '8993176811094', 'Sample253', 1, 0, '66500.00', '64505.00', 0, 1995),
(8593, 2638, 'JO-2409020003-S', '0200668', '8993365011007', 'Sample254', 2, 0, '21900.00', '42486.00', 0, 1314),
(8594, 2638, 'JO-2409020003-S', '0300521', '6945850500277', 'Sample255', 1, 0, '35900.00', '35900.00', 0, 0),
(8595, 2639, 'JO-2409020002-S', '11012693', '11012693', 'Sample256', 1, 0, '179500.00', '179500.00', 0, 0),
(8596, 2639, 'JO-2409020002-S', '4600657', '4600657', 'Sample257', 1, 0, '6500.00', '6500.00', 0, 0),
(8597, 2640, 'JO-2409020004-S', '4600657', '4600657', 'Sample258', 1, 0, '6500.00', '6500.00', 0, 0),
(8598, 2640, 'JO-2409020004-S', '030103250', '030103250', 'Sample259', 1, 0, '174500.00', '174500.00', 0, 0),
(8599, 2641, 'JO-2409020005-S', '02011576', '02011576', 'Sample260', 1, 0, '17500.00', '17500.00', 0, 0),
(8600, 2641, 'JO-2409020005-S', '020112637', '020112637', 'Sample261', 1, 0, '16500.00', '16500.00', 0, 0),
(8601, 2641, 'JO-2409020005-S', '020305000', '020305000', 'Sample262', 1, 0, '34500.00', '34500.00', 0, 0),
(8602, 2641, 'JO-2409020005-S', '02020852', '02020852', 'Sample263', 1, 0, '21500.00', '21500.00', 0, 0),
(8603, 2642, 'JO-2409020006-S', '03031483', '03031483', 'Sample264', 1, 0, '58500.00', '58500.00', 0, 0),
(8604, 2642, 'JO-2409020006-S', '03012919', '03012919', 'Sample265', 1, 0, '86500.00', '86500.00', 0, 0),
(8605, 2642, 'JO-2409020006-S', '03040753', '03040753', 'Sample266', 2, 0, '76500.00', '153000.00', 0, 0),
(8606, 2642, 'JO-2409020006-S', '030203097', '030203097', 'Sample267', 1, 0, '41500.00', '41500.00', 0, 0),
(8607, 2642, 'JO-2409020006-S', '03270145', '03270145', 'Sample268', 2, 0, '26500.00', '53000.00', 0, 0),
(8608, 2642, 'JO-2409020006-S', '020403269', '020403269', 'Sample269', 2, 0, '26500.00', '53000.00', 0, 0),
(8609, 2642, 'JO-2409020006-S', '020403270', '020403270', 'Sample270', 1, 0, '29500.00', '29500.00', 0, 0),
(8610, 2643, 'JO-2409020007-S', '290700121', '290700121', 'Sample271', 1, 0, '154500.00', '154500.00', 0, 0),
(8611, 2644, 'JO-2409020008-S', '08015114', '8997240291119', 'Sample272', 1, 0, '29900.00', '29900.00', 0, 0),
(8612, 2645, 'JO-2409020009-S', '10041067', '10041067', 'Sample273', 1, 0, '99500.00', '99500.00', 0, 0),
(8613, 2645, 'JO-2409020009-S', '01150106', '0299998173067', 'Sample274', 1, 0, '89000.00', '89000.00', 0, 0),
(8614, 2646, 'JO-2409020011-S', '011600117', '8991758010132', 'Sample275', 1, 0, '46500.00', '45105.00', 0, 1395),
(8615, 2646, 'JO-2409020011-S', '03120260', '03120260', 'Sample276', 1, 0, '28500.00', '28500.00', 0, 0),
(8616, 2647, 'JO-2409020013-S', '291500196', '291500196', 'Sample277', 1, 0, '152500.00', '152500.00', 0, 0),
(8617, 2648, 'JO-2409010008-S', '03101005', '8995154100909', 'Sample278', 1, 0, '3900.00', '3900.00', 0, 0),
(8618, 2648, 'JO-2409010008-S', '03091067', '03091067', 'Sample279', 1, 0, '8500.00', '8500.00', 0, 0),
(8619, 2648, 'JO-2409010008-S', '0308832', '8850007090267', 'Sample280', 1, 0, '32500.00', '32500.00', 0, 0),
(8620, 2649, 'JO-2409010007-S', '13011482', '13011482', 'Sample281', 1, 0, '437500.00', '437500.00', 0, 0),
(8621, 2650, 'JO-2409010006-S', '0300439', '8851111401567', 'Sample282', 1, 0, '126900.00', '126900.00', 0, 0),
(8622, 2651, 'JO-2409010005-S', '040502005', '040502005', 'Sample283', 1, 0, '68500.00', '68500.00', 0, 0),
(8623, 2652, 'JO-2409010004-S', '4600657', '4600657', 'Sample284', 5, 0, '6500.00', '32500.00', 0, 0),
(8624, 2653, 'JO-2409010002-S', '03091061', '6972602180562', 'Sample285', 6, 0, '8500.00', '51000.00', 0, 0),
(8625, 2653, 'JO-2409010002-S', '0300641', '8697454725232', 'Sample286', 1, 0, '82500.00', '82500.00', 0, 0),
(8626, 2653, 'JO-2409010002-S', '0300901', '8993531772527', 'Sample287', 4, 0, '28500.00', '114000.00', 0, 0),
(8627, 2653, 'JO-2409010002-S', '080105381', '8993531775306', 'Sample288', 1, 0, '49500.00', '49500.00', 0, 0),
(8628, 2654, 'JO-2409010001-S', '160109880', '160109880', 'Sample289', 1, 0, '36500.00', '36500.00', 0, 0),
(8629, 2654, 'JO-2409010001-S', '0380761', '4103040144959', 'Sample290', 1, 0, '173500.00', '173500.00', 0, 0),
(8630, 2654, 'JO-2409010001-S', '0300441', '8851111401574', 'Sample291', 1, 0, '126900.00', '126900.00', 0, 0),
(8631, 2655, 'JO-2409010003-S', '05021376', '05021376', 'Sample292', 1, 0, '15500.00', '15500.00', 0, 0),
(8632, 2655, 'JO-2409010003-S', '05021382', '05021382', 'Sample293', 3, 0, '15500.00', '46500.00', 0, 0),
(8633, 2655, 'JO-2409010003-S', '4600585', '4600585', 'Sample294', 1, 0, '4500.00', '4500.00', 0, 0),
(8634, 2655, 'JO-2409010003-S', '11041041', '9781784680886', 'Sample295', 1, 0, '152500.00', '152500.00', 0, 0),
(8635, 2656, 'JO-2409010013-S', '010500960', '8997016511878', 'Sample296', 1, 0, '84500.00', '84500.00', 0, 0),
(8636, 2656, 'JO-2409010013-S', '0300572', '8993189700354', 'Sample297', 1, 0, '107500.00', '107500.00', 0, 0),
(8637, 2656, 'JO-2409010013-S', '0300464', '8851111401161', 'Sample298', 1, 0, '92500.00', '92500.00', 0, 0),
(8638, 2656, 'JO-2409010013-S', '0300382', '4902508108430', 'Sample299', 1, 0, '17900.00', '17900.00', 0, 0),
(8639, 2656, 'JO-2409010013-S', '0300568', '4902508108423', 'Sample300', 1, 0, '29900.00', '29900.00', 0, 0),
(8640, 2656, 'JO-2409010013-S', '0301901', '4902508105835', 'Sample301', 1, 0, '33500.00', '33500.00', 0, 0),
(8641, 2657, 'JO-2409010012-S', '291300038', '6935539615323', 'Sample302', 2, 0, '32500.00', '65000.00', 0, 0),
(8642, 2657, 'JO-2409010012-S', '08021009', '8997020780765', 'Sample303', 1, 0, '70000.00', '70000.00', 0, 0),
(8643, 2657, 'JO-2409010012-S', '08030250', '8997213460634', 'Sample304', 1, 0, '36500.00', '36500.00', 0, 0),
(8644, 2658, 'JO-2409010011-S', '08014845', '8858954150041', 'Sample305', 2, 0, '29900.00', '59800.00', 0, 0),
(8645, 2658, 'JO-2409010011-S', '0308988', '8858954150058', 'Sample306', 1, 0, '29900.00', '29900.00', 0, 0),
(8646, 2658, 'JO-2409010011-S', '080105430', '8997240303218', 'Sample307', 1, 0, '49900.00', '49900.00', 0, 0),
(8647, 2658, 'JO-2409010011-S', '03091025', '6974180093050', 'Sample308', 1, 0, '97500.00', '97500.00', 0, 0),
(8648, 2659, 'JO-2409010010-S', '030901113', '8992959951088', 'Sample309', 1, 0, '178900.00', '178900.00', 0, 0),
(8649, 2660, 'JO-2409010009-S', '010901133', '0609722870610', 'Sample310', 1, 0, '134500.00', '129500.00', 0, 5000),
(8650, 2660, 'JO-2409010009-S', '0301200', '8991038772194', 'Sample311', 1, 0, '10500.00', '10500.00', 0, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','checker') DEFAULT 'checker',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `last_login`) VALUES
(1, 'admin', '$2y$10$.FK3vpeP7ISF1HQcGnYVFenDkC3k7iG/fli6kd29Huqzc6vVZjCDa', 'admin', '2025-07-18 08:43:49');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `layanan_pengiriman`
--
ALTER TABLE `layanan_pengiriman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `online_wa`
--
ALTER TABLE `online_wa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_penjualan` (`no_penjualan`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `pending_imports`
--
ALTER TABLE `pending_imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_penjualan` (`no_penjualan`);

--
-- Indeks untuk tabel `pending_import_items`
--
ALTER TABLE `pending_import_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_import_id` (`pending_import_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `layanan_pengiriman`
--
ALTER TABLE `layanan_pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `online_wa`
--
ALTER TABLE `online_wa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=545;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1592;

--
-- AUTO_INCREMENT untuk tabel `pending_imports`
--
ALTER TABLE `pending_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2661;

--
-- AUTO_INCREMENT untuk tabel `pending_import_items`
--
ALTER TABLE `pending_import_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8651;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `online_wa` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pending_import_items`
--
ALTER TABLE `pending_import_items`
  ADD CONSTRAINT `pending_import_items_ibfk_1` FOREIGN KEY (`pending_import_id`) REFERENCES `pending_imports` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
