-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 09:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vlxd_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `STATUS` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `NAME`, `description`, `parent_id`, `image`, `STATUS`, `created_at`) VALUES
(1, 'Xi măng', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(2, 'Gạch', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(3, 'Thép', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(4, 'Sơn', NULL, NULL, NULL, 1, '2025-11-24 08:35:14');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `TYPE` enum('import','export','adjustment','sold','return') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` enum('order','purchase','adjustment') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(15) NOT NULL,
  `customer_address` text NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `shipping_fee` decimal(15,2) DEFAULT 0.00,
  `tax` decimal(15,2) DEFAULT 0.00,
  `discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cod','banking','momo','vnpay') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cod','bank_transfer','online') NOT NULL DEFAULT 'cod',
  `amount` decimal(15,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `STATUS` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `transaction_code` varchar(100) DEFAULT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_quantity` int(11) DEFAULT 0,
  `max_quantity` int(11) DEFAULT 1000,
  `weight` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `STATUS` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `NAME`, `description`, `short_description`, `sku`, `category_id`, `supplier_id`, `price`, `sale_price`, `cost_price`, `quantity`, `min_quantity`, `max_quantity`, `weight`, `unit`, `images`, `specifications`, `STATUS`, `featured`, `view_count`, `created_at`, `updated_at`, `is_featured`) VALUES
(1, 'Gạch Ceramic 300x300', 'Gạch ceramic 300x300 được sử dụng phổ biến trong các công trình xây dựng với khả năng chống thấm, bền đẹp và dễ dàng vệ sinh.', 'Gạch Ceramic 300x300 - Bền, đẹp, dễ vệ sinh', 'GC_300X300', 1, 3, 45000.00, 42000.00, 38000.00, 5000, 500, 20000, 1.20, 'Viên', '[\"gach_ceramic_300x300.jpg\"]', '{\"kích_thước\": \"300x300mm\", \"chất_liệu\": \"Gạch Ceramic\"}', 'active', 0, 0, '2025-11-25 05:00:00', '2025-11-25 05:00:00', 0),
(2, 'Gạch Porcelain 600x600', 'Gạch Porcelain độ bền cao, lát nền nội thất và ngoại thất.', 'Gạch Porcelain cao cấp 600x600', 'GP_600X600', 1, 2, 150000.00, 135000.00, 90000.00, 3000, 100, 10000, 3.50, 'Viên', '[\"gach_porcelain_600x600.jpg\"]', '{\"kích_thước\": \"600x600mm\", \"chất_liệu\": \"Porcelain\"}', 'active', 0, 0, '2025-11-25 08:49:38', '2025-11-25 08:49:38', 0),
(3, 'Gạch Ceramic 500x500', 'Gạch Ceramic 500x500 phù hợp cho lát nền phòng khách, chống thấm tốt và bền màu.', 'Gạch Ceramic phổ thông 500x500', 'GC_500X500', 1, 3, 85000.00, 78000.00, 60000.00, 4000, 100, 15000, 2.80, 'Viên', '[\"gach_ceramic_500x500.jpg\"]', '{\"kích_thước\": \"500x500mm\", \"chất_liệu\": \"Ceramic\"}', 'active', 0, 0, '2025-11-25 08:49:57', '2025-11-25 08:49:57', 0),
(4, 'Gạch Granite 600x600 Bóng Mờ', 'Gạch Granite 600x600 bóng mờ cao cấp, chống trơn trượt và chống trầy xước tốt.', 'Gạch Granite 600x600 cao cấp', 'GG_600X600_MATT', 1, 4, 195000.00, 175000.00, 130000.00, 2500, 80, 12000, 4.00, 'Viên', '[\"gach_granite_600x600_matt.jpg\"]', '{\"kích_thước\": \"600x600mm\", \"bề_mặt\": \"Bóng mờ\", \"chất_liệu\": \"Granite\"}', 'active', 0, 0, '2025-11-25 08:50:14', '2025-11-25 08:50:14', 0),
(5, 'Xi măng Holcim PCB40', 'Xi măng chất lượng cao dùng cho xây dựng dân dụng và công nghiệp.', 'Xi măng Holcim PCB40 chất lượng cao.', 'XM-HOLCIM-40', 1, 1, 185000.00, 175000.00, 160000.00, 500, 50, 1000, 50.00, 'bao', '[\"holcim1.jpg\",\"holcim2.jpg\"]', '{\"độ mịn\":\"3500 cm2/g\",\"độ nén\":\"40 MPa\"}', 'active', 0, 0, '2025-11-24 08:36:25', '2025-11-24 08:36:25', 1),
(6, 'Gạch Tuynel 10x20', 'Gạch nung đỏ kích thước 10x20 dùng cho xây tường.', 'Gạch Tuynel 10x20 tiêu chuẩn.', 'GACH-TUYNEL-1020', 2, 2, 8500.00, 8000.00, 7000.00, 10000, 500, 20000, 1.20, 'viên', '[\"gach1.jpg\"]', '{\"kích thước\":\"10x20 cm\",\"màu sắc\":\"đỏ\"}', 'active', 0, 0, '2025-11-24 08:36:25', '2025-11-24 08:36:25', 1),
(7, 'Gạch Porcelain 800x800 Cao Cấp', 'Gạch Porcelain 800x800 có độ cứng cao, ít hút nước, bền và sang trọng.', 'Gạch Porcelain cao cấp 800x800', 'GP_800X800', 1, 2, 320000.00, 290000.00, 220000.00, 1500, 50, 8000, 7.00, 'Viên', '[\"gach_porcelain_800x800.jpg\"]', '{\"kích_thước\": \"800x800mm\", \"chất_liệu\": \"Porcelain\", \"độ_hút_nước\": \"<0.5%\"}', 'active', 0, 0, '2025-11-25 08:50:31', '2025-11-25 08:50:31', 0),
(8, 'Sơn Nội Thất Cao Cấp – Trắng', 'Sơn nội thất cao cấp, bề mặt mịn, dễ lau chùi, màu trắng tinh khiết.', 'Sơn nội thất trắng cao cấp', 'SON_NT_TRANG', 2, 1, 120000.00, 110000.00, 80000.00, 500, 10, 1000, 5.00, 'Lít', '[\"son_noi_that_trang.jpg\"]', '{\"màu_sắc\":\"Trắng\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:25', '2025-11-25 08:51:25', 0),
(9, 'Sơn Ngoại Thất Chống Thấm – Xanh', 'Sơn ngoại thất chống thấm, chịu thời tiết tốt, màu xanh mát mắt.', 'Sơn ngoại thất chống thấm xanh', 'SON_NT_XANH', 2, 2, 150000.00, 135000.00, 100000.00, 300, 10, 800, 5.00, 'Lít', '[\"son_ngoai_that_xanh.jpg\"]', '{\"màu_sắc\":\"Xanh\",\"loại\":\"Ngoại thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:39', '2025-11-25 08:51:39', 0),
(10, 'Sơn Nội Thất Cao Cấp – Kem', 'Sơn nội thất màu kem, mịn màng, dễ lau chùi và an toàn cho sức khỏe.', 'Sơn nội thất kem cao cấp', 'SON_NT_KEM', 2, 1, 120000.00, 110000.00, 80000.00, 400, 10, 900, 5.00, 'Lít', '[\"son_noi_that_kem.jpg\"]', '{\"màu_sắc\":\"Kem\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:53', '2025-11-25 08:51:53', 0),
(11, 'Sơn Ngoại Thất Chống Nắng – Vàng', 'Sơn ngoại thất màu vàng, chống tia UV, bền màu và chịu thời tiết tốt.', 'Sơn ngoại thất chống nắng vàng', 'SON_NT_VANG', 2, 2, 150000.00, 140000.00, 100000.00, 350, 10, 850, 5.00, 'Lít', '[\"son_ngoai_that_vang.jpg\"]', '{\"màu_sắc\":\"Vàng\",\"loại\":\"Ngoại thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:52:07', '2025-11-25 08:52:07', 0),
(12, 'Sơn Nội Thất Chống Ẩm – Xám', 'Sơn nội thất chống ẩm, màu xám hiện đại, bảo vệ tường khỏi nấm mốc.', 'Sơn nội thất chống ẩm xám', 'SON_NT_XAM', 2, 1, 125000.00, 115000.00, 85000.00, 450, 10, 950, 5.00, 'Lít', '[\"son_noi_that_xam.jpg\"]', '{\"màu_sắc\":\"Xám\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:52:22', '2025-11-25 08:52:22', 0),
(13, 'Thép Cây Phi 10 – CT10', 'Thép cây Phi 10 chất lượng cao, dùng cho xây dựng, chịu lực tốt.', 'Thép cây phi 10 CT10', 'THEP_CT10', 3, 1, 12000.00, 11000.00, 9000.00, 1000, 10, 5000, 0.80, 'Cây', '[\"thep_ct10.jpg\"]', '{\"đường_kinh\":\"10mm\",\"loại\":\"Thép cây\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:14', '2025-11-25 08:53:14', 0),
(14, 'Thép Hộp Vuông 50x50 – TH50', 'Thép hộp vuông 50x50, dày 2mm, dùng làm khung sườn kết cấu chịu lực.', 'Thép hộp vuông 50x50 dày 2mm', 'THEP_TH50', 3, 2, 95000.00, 90000.00, 80000.00, 500, 10, 2000, 5.00, 'Cây', '[\"thep_hop_50x50.jpg\"]', '{\"kích_thước\":\"50x50mm\",\"dày\":\"2mm\",\"loại\":\"Thép hộp\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:27', '2025-11-25 08:53:27', 0),
(15, 'Thép Ống Phi 27 – TO27', 'Thép ống phi 27 dày 1.5mm, dùng cho hệ thống lan can và kết cấu nhẹ.', 'Thép ống phi 27 TO27', 'THEP_TO27', 3, 1, 30000.00, 28000.00, 25000.00, 800, 10, 3000, 1.50, 'Cây', '[\"thep_ong_phi27.jpg\"]', '{\"đường_kinh\":\"27mm\",\"dày\":\"1.5mm\",\"loại\":\"Thép ống\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:39', '2025-11-25 08:53:39', 0),
(16, 'Thép V – 10mm – TV10', 'Thép V 10mm dùng cho khung kết cấu, chịu lực tốt và dễ lắp đặt.', 'Thép V 10mm TV10', 'THEP_TV10', 3, 2, 20000.00, 18000.00, 15000.00, 600, 10, 2000, 1.00, 'Cây', '[\"thep_v_10mm.jpg\"]', '{\"kích_thước\":\"10mm\",\"loại\":\"Thép V\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:51', '2025-11-25 08:53:51', 0),
(17, 'Thép Tấm 6mm – TT6', 'Thép tấm 6mm chất lượng cao, dùng cho khung, mái, và các kết cấu thép.', 'Thép tấm 6mm TT6', 'THEP_TT6', 3, 1, 250000.00, 230000.00, 200000.00, 300, 10, 1000, 6.00, 'Tấm', '[\"thep_tam_6mm.jpg\"]', '{\"độ_dày\":\"6mm\",\"loại\":\"Thép tấm\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:54:04', '2025-11-25 08:54:04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `min_order_amount` decimal(15,2) DEFAULT 0.00,
  `max_discount_amount` decimal(15,2) DEFAULT NULL,
  `CODE` varchar(50) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `STATUS` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `STATUS` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `NAME`, `contact_person`, `phone`, `email`, `address`, `STATUS`, `created_at`) VALUES
(1, 'Holcim', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(2, 'Tuynel', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(3, 'Hòa Phát', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(4, 'Dulux', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(5, 'Đồng Tâm', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(6, 'Việt Nhật', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(7, 'Nghi Sơn', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(8, 'Jotun', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `PASSWORD` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `CODE` (`CODE`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
