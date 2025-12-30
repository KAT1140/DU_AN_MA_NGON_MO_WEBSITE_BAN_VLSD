-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 30, 2025 lúc 10:25 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `vlxd_store1`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
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
-- Cấu trúc bảng cho bảng `cart_items`
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
-- Cấu trúc bảng cho bảng `categories`
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
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `NAME`, `description`, `parent_id`, `image`, `STATUS`, `created_at`) VALUES
(1, 'Xi măng', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(2, 'Gạch', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(3, 'Thép', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(4, 'Sơn - Phụ phẩm kèm', '', NULL, NULL, 1, '2025-11-24 08:35:14'),
(5, 'Tôn - Ngói', '', NULL, NULL, 1, '2025-12-23 06:51:00'),
(6, 'Cát - Đá', '', NULL, NULL, 1, '2025-12-30 06:56:27'),
(7, 'Ống nước - Phụ kiện', '', NULL, NULL, 1, '2025-12-30 08:10:46'),
(8, 'Thiết bị vệ sinh', '', NULL, NULL, 1, '2025-12-30 08:11:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory`
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
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(15) NOT NULL,
  `customer_address` text NOT NULL,
  `province` varchar(100) DEFAULT NULL,
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

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `province`, `shipping_address`, `note`, `subtotal`, `shipping_fee`, `tax`, `discount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `shipping_method`, `tracking_number`, `completed_at`, `cancelled_at`, `created_at`, `updated_at`) VALUES
(1, 'VLXD20251223694A386BE0B76', 1, 'Nam Vo', 'namvokat@gmail.com', '0379648264', 'trà vinh', 'Trà Vinh', 'trà vinh, Trà Vinh', '', 540000.00, 30000.00, 0.00, 0.00, 570000.00, 'cod', 'paid', 'delivered', NULL, NULL, '2025-12-23 06:37:03', NULL, '2025-12-23 06:36:27', '2025-12-23 06:37:03'),
(4, 'VLXD20251223694A5680B554C', 1, 'Nam Vo', 'namvokat@gmail.com', '0379648264', 'trà vinh, Trà Vinh, An Giang', 'An Giang', 'trà vinh, Trà Vinh, An Giang, An Giang', '', 30000.00, 30000.00, 0.00, 0.00, 60000.00, 'banking', 'pending', '', NULL, NULL, NULL, NULL, '2025-12-23 08:44:48', '2025-12-23 08:44:48'),
(5, 'VLXD20251223694A585013006', 1, 'nam', 'namvokat@gmail.com', '0379648264', 'Trà Vinh', 'Việt Nam', 'Trà Vinh, Việt Nam', '', 125000.00, 30000.00, 0.00, 0.00, 155000.00, 'banking', 'pending', '', NULL, NULL, NULL, NULL, '2025-12-23 08:52:32', '2025-12-23 08:52:32'),
(6, 'VLXD202512306953787D903B8', 13, 'CoNhanQuy', '1@gmail.com', '0773998235', 'B7 ĐHTV', 'Trà Vinh', 'B7 ĐHTV, Trà Vinh', '', 20000.00, 30000.00, 0.00, 0.00, 50000.00, 'momo', 'pending', 'shipped', NULL, NULL, NULL, NULL, '2025-12-30 07:00:13', '2025-12-30 07:03:39'),
(7, 'VLXD2025123069537927DD589', 13, 'CoNhanQuy', '1@gmail.com', '0773998235', 'B7 ĐHTV, Trà Vinh', 'Trà Vinh', 'B7 ĐHTV, Trà Vinh, Trà Vinh', '', 150000.00, 30000.00, 0.00, 0.00, 180000.00, 'cod', 'pending', 'cancelled', NULL, NULL, NULL, NULL, '2025-12-30 07:03:03', '2025-12-30 08:16:53'),
(8, 'VLXD202512306953798586A43', 13, 'CoNhanQuy', '1@gmail.com', '0773998235', 'B7 ĐHTV, Trà Vinh, Trà Vinh', 'Trà Vinh', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh', '', 2500000.00, 0.00, 0.00, 0.00, 2500000.00, 'banking', 'pending', 'shipped', NULL, NULL, NULL, NULL, '2025-12-30 07:04:37', '2025-12-30 07:05:22'),
(9, 'VLXD20251230695389FDF378A', 13, 'CoNhanQuy', '1@gmail.com', '0773998235', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh', 'Trà Vinh', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh, Trà Vinh', '', 2500000.00, 0.00, 0.00, 0.00, 2500000.00, 'cod', 'pending', 'shipped', NULL, NULL, NULL, NULL, '2025-12-30 08:14:53', '2025-12-30 08:15:34'),
(10, 'VLXD2025123069538AAC707FB', 13, 'CoNhanQuy', '1@gmail.com', '0773998235', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh, Trà Vinh', 'An Giang', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh, Trà Vinh, An Giang', '', 2500000.00, 0.00, 0.00, 0.00, 2500000.00, 'cod', 'pending', 'cancelled', NULL, NULL, NULL, NULL, '2025-12-30 08:17:48', '2025-12-30 08:18:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
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

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `total_price`, `created_at`) VALUES
(1, 1, 15, 'Thép Ống Phi 27 – TO27', 30000.00, 4, 120000.00, '2025-12-23 06:36:27'),
(2, 1, 14, 'Thép Hộp Vuông 50x50 – TH50', 95000.00, 4, 380000.00, '2025-12-23 06:36:27'),
(3, 1, 16, 'Thép V – 10mm – TV10', 20000.00, 2, 40000.00, '2025-12-23 06:36:27'),
(8, 4, 15, 'Thép Ống Phi 27 – TO27', 30000.00, 1, 30000.00, '2025-12-23 08:44:48'),
(9, 5, 14, 'Thép Hộp Vuông 50x50 – TH50', 95000.00, 1, 95000.00, '2025-12-23 08:52:32'),
(10, 5, 15, 'Thép Ống Phi 27 – TO27', 30000.00, 1, 30000.00, '2025-12-23 08:52:32'),
(11, 6, 16, 'Thép V – 10mm – TV10', 20000.00, 1, 20000.00, '2025-12-30 07:00:13'),
(12, 7, 11, 'Sơn Ngoại Thất Chống Nắng – Vàng', 150000.00, 1, 150000.00, '2025-12-30 07:03:03'),
(13, 8, 17, 'Thép Tấm 6mm – TT6', 250000.00, 10, 2500000.00, '2025-12-30 07:04:37'),
(14, 9, 17, 'Thép Tấm 6mm – TT6', 250000.00, 10, 2500000.00, '2025-12-30 08:14:54'),
(15, 10, 17, 'Thép Tấm 6mm – TT6', 250000.00, 10, 2500000.00, '2025-12-30 08:17:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
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
-- Cấu trúc bảng cho bảng `products`
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
  `image` varchar(255) DEFAULT NULL,
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
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `NAME`, `description`, `short_description`, `sku`, `category_id`, `supplier_id`, `price`, `sale_price`, `cost_price`, `quantity`, `min_quantity`, `max_quantity`, `weight`, `unit`, `image`, `images`, `specifications`, `STATUS`, `featured`, `view_count`, `created_at`, `updated_at`, `is_featured`) VALUES
(1, 'Gạch Ceramic 300x300', 'Gạch ceramic 300x300 được sử dụng phổ biến trong các công trình xây dựng với khả năng chống thấm, bền đẹp và dễ dàng vệ sinh.', 'Gạch Ceramic 300x300 - Bền, đẹp, dễ vệ sinh', 'GC_300X300', 1, 3, 45000.00, 42000.00, 38000.00, 5000, 500, 20000, 1.20, 'Viên', 'product_1767077025_695374a10ffbe.jpg', '[\"gach_ceramic_300x300.jpg\"]', '{\"kích_thước\": \"300x300mm\", \"chất_liệu\": \"Gạch Ceramic\"}', 'active', 0, 0, '2025-11-25 05:00:00', '2025-12-30 06:43:45', 0),
(2, 'Gạch Porcelain 600x600', 'Gạch Porcelain độ bền cao, lát nền nội thất và ngoại thất.', 'Gạch Porcelain cao cấp 600x600', 'GP_600X600', 1, 2, 150000.00, 135000.00, 90000.00, 3000, 100, 10000, 3.50, 'Viên', 'product_1767077010_695374927f70c.jpg', '[\"gach_porcelain_600x600.jpg\"]', '{\"kích_thước\": \"600x600mm\", \"chất_liệu\": \"Porcelain\"}', 'active', 0, 0, '2025-11-25 08:49:38', '2025-12-30 06:43:30', 0),
(3, 'Gạch Ceramic 500x500', 'Gạch Ceramic 500x500 phù hợp cho lát nền phòng khách, chống thấm tốt và bền màu.', 'Gạch Ceramic phổ thông 500x500', 'GC_500X500', 1, 3, 85000.00, 78000.00, 60000.00, 4000, 100, 15000, 2.80, 'Viên', 'product_1767076998_695374868b061.jpg', '[\"gach_ceramic_500x500.jpg\"]', '{\"kích_thước\": \"500x500mm\", \"chất_liệu\": \"Ceramic\"}', 'active', 0, 0, '2025-11-25 08:49:57', '2025-12-30 06:43:18', 0),
(4, 'Gạch Granite 600x600 Bóng Mờ', 'Gạch Granite 600x600 bóng mờ cao cấp, chống trơn trượt và chống trầy xước tốt.', 'Gạch Granite 600x600 cao cấp', 'GG_600X600_MATT', 1, 4, 195000.00, 175000.00, 130000.00, 2500, 80, 12000, 4.00, 'Viên', 'product_1767076905_6953742973476.jpg', '[\"gach_granite_600x600_matt.jpg\"]', '{\"kích_thước\": \"600x600mm\", \"bề_mặt\": \"Bóng mờ\", \"chất_liệu\": \"Granite\"}', 'active', 0, 0, '2025-11-25 08:50:14', '2025-12-30 06:41:45', 0),
(5, 'Xi măng Holcim PCB40', 'Xi măng chất lượng cao dùng cho xây dựng dân dụng và công nghiệp.', 'Xi măng Holcim PCB40 chất lượng cao.', 'XM-HOLCIM-40', 1, 1, 185000.00, 175000.00, 160000.00, 500, 50, 1000, 50.00, 'bao', 'product_1767076871_69537407955c1.jpg', '[\"holcim1.jpg\",\"holcim2.jpg\"]', '{\"độ mịn\":\"3500 cm2/g\",\"độ nén\":\"40 MPa\"}', 'active', 0, 0, '2025-11-24 08:36:25', '2025-12-30 06:41:11', 1),
(6, 'Gạch Tuynel 10x20', 'Gạch nung đỏ kích thước 10x20 dùng cho xây tường.', 'Gạch Tuynel 10x20 tiêu chuẩn.', 'GACH-TUYNEL-1020', 2, 2, 8500.00, 8000.00, 7000.00, 10000, 500, 20000, 1.20, 'viên', 'product_1767076832_695373e07dbae.jpg', '[\"gach1.jpg\"]', '{\"kích thước\":\"10x20 cm\",\"màu sắc\":\"đỏ\"}', 'active', 0, 0, '2025-11-24 08:36:25', '2025-12-30 06:40:32', 1),
(7, 'Gạch Porcelain 800x800 Cao Cấp', 'Gạch Porcelain 800x800 có độ cứng cao, ít hút nước, bền và sang trọng.', 'Gạch Porcelain cao cấp 800x800', 'GP_800X800', 1, 2, 320000.00, 290000.00, 220000.00, 1500, 50, 8000, 7.00, 'Viên', 'product_1767076803_695373c304fae.jpg', '[\"gach_porcelain_800x800.jpg\"]', '{\"kích_thước\": \"800x800mm\", \"chất_liệu\": \"Porcelain\", \"độ_hút_nước\": \"<0.5%\"}', 'active', 0, 0, '2025-11-25 08:50:31', '2025-12-30 06:40:03', 0),
(8, 'Sơn Nội Thất Cao Cấp – Trắng', 'Sơn nội thất cao cấp, bề mặt mịn, dễ lau chùi, màu trắng tinh khiết.', 'Sơn nội thất trắng cao cấp', 'SON_NT_TRANG', 2, 1, 120000.00, 110000.00, 80000.00, 500, 10, 1000, 5.00, 'Lít', 'product_1767076744_695373889e98f.jpg', '[\"son_noi_that_trang.jpg\"]', '{\"màu_sắc\":\"Trắng\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:25', '2025-12-30 06:39:04', 0),
(9, 'Sơn Ngoại Thất Chống Thấm – Xanh', 'Sơn ngoại thất chống thấm, chịu thời tiết tốt, màu xanh mát mắt.', 'Sơn ngoại thất chống thấm xanh', 'SON_NT_XANH', 2, 2, 150000.00, 135000.00, 100000.00, 300, 10, 800, 5.00, 'Lít', 'product_1767076444_6953725c895f1.jpg', '[\"son_ngoai_that_xanh.jpg\"]', '{\"màu_sắc\":\"Xanh\",\"loại\":\"Ngoại thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:39', '2025-12-30 06:34:04', 0),
(10, 'Sơn Nội Thất Cao Cấp – Kem', 'Sơn nội thất màu kem, mịn màng, dễ lau chùi và an toàn cho sức khỏe.', 'Sơn nội thất kem cao cấp', 'SON_NT_KEM', 2, 1, 120000.00, 110000.00, 80000.00, 400, 10, 900, 5.00, 'Lít', 'product_1767076395_6953722bb1e40.jpg', '[\"son_noi_that_kem.jpg\"]', '{\"màu_sắc\":\"Kem\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:51:53', '2025-12-30 06:33:15', 0),
(11, 'Sơn Ngoại Thất Chống Nắng – Vàng', 'Sơn ngoại thất màu vàng, chống tia UV, bền màu và chịu thời tiết tốt.', 'Sơn ngoại thất chống nắng vàng', 'SON_NT_VANG', 2, 2, 150000.00, 140000.00, 100000.00, 349, 10, 850, 5.00, 'Lít', 'product_1767076362_6953720a6163e.jpg', '[\"son_ngoai_that_vang.jpg\"]', '{\"màu_sắc\":\"Vàng\",\"loại\":\"Ngoại thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:52:07', '2025-12-30 07:03:03', 0),
(12, 'Sơn Nội Thất Chống Ẩm – Xám', 'Sơn nội thất chống ẩm, màu xám hiện đại, bảo vệ tường khỏi nấm mốc.', 'Sơn nội thất chống ẩm xám', 'SON_NT_XAM', 4, 1, 125000.00, 115000.00, 85000.00, 449, 10, 950, 5.00, 'Lít', 'product_1767076326_695371e6b2421.jpg', '[\"son_noi_that_xam.jpg\"]', '{\"màu_sắc\":\"Xám\",\"loại\":\"Nội thất\",\"dung_tích\":\"5L\"}', 'active', 0, 0, '2025-11-25 08:52:22', '2025-12-30 06:32:06', 0),
(13, 'Thép Cây Phi 10 – CT10', 'Thép cây Phi 10 chất lượng cao, dùng cho xây dựng, chịu lực tốt.', 'Thép cây phi 10 CT10', 'THEP_CT10', 3, 1, 12000.00, 11000.00, 9000.00, 1000, 10, 5000, 0.80, 'Cây', 'product_1767076283_695371bbd990a.jpg', '[\"thep_ct10.jpg\"]', '{\"đường_kinh\":\"10mm\",\"loại\":\"Thép cây\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:14', '2025-12-30 06:31:23', 0),
(14, 'Thép Hộp Vuông 50x50 – TH50', 'Thép hộp vuông 50x50, dày 2mm, dùng làm khung sườn kết cấu chịu lực.', 'Thép hộp vuông 50x50 dày 2mm', 'THEP_TH50', 3, 2, 95000.00, 90000.00, 80000.00, 494, 10, 2000, 5.00, 'Cây', 'product_1767076243_69537193aafcc.jpg', '[\"thep_hop_50x50.jpg\"]', '{\"kích_thước\":\"50x50mm\",\"dày\":\"2mm\",\"loại\":\"Thép hộp\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:27', '2025-12-30 06:30:43', 0),
(15, 'Thép Ống Phi 27 – TO27', 'Thép ống phi 27 dày 1.5mm, dùng cho hệ thống lan can và kết cấu nhẹ.', 'Thép ống phi 27 TO27', 'THEP_TO27', 3, 1, 30000.00, 28000.00, 25000.00, 792, 10, 3000, 1.50, 'Cây', 'product_1767076074_695370eaddd16.jpg', '[\"thep_ong_phi27.jpg\"]', '{\"đường_kinh\":\"27mm\",\"dày\":\"1.5mm\",\"loại\":\"Thép ống\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:39', '2025-12-30 06:27:54', 0),
(16, 'Thép V – 10mm – TV10', 'Thép V 10mm dùng cho khung kết cấu, chịu lực tốt và dễ lắp đặt.', 'Thép V 10mm TV10', 'THEP_TV10', 3, 2, 20000.00, 18000.00, 15000.00, 596, 10, 2000, 1.00, 'Cây', 'product_1767075922_695370529f336.jpg', '[\"thep_v_10mm.jpg\"]', '{\"kích_thước\":\"10mm\",\"loại\":\"Thép V\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:53:51', '2025-12-30 07:00:13', 0),
(17, 'Thép Tấm 6mm – TT6', 'Thép tấm 6mm chất lượng cao, dùng cho khung, mái, và các kết cấu thép.', 'Thép tấm 6mm TT6', 'THEP_TT6', 3, 1, 250000.00, 230000.00, 200000.00, 270, 10, 1000, 6.00, 'Tấm', 'product_1767075816_69536fe89dc37.jpg', '[\"thep_tam_6mm.jpg\"]', '{\"độ_dày\":\"6mm\",\"loại\":\"Thép tấm\",\"xuất_xứ\":\"Việt Nam\"}', 'active', 0, 0, '2025-11-25 08:54:04', '2025-12-30 08:17:48', 0),
(18, 'Cát Xây tô - Sàng sạch', 'Cát nước ngọt hạt mịn, sạch tạp chất, đã được sàng kỹ. Độ dẻo cao giúp vữa bám dính tốt, bề mặt tường láng mịn sau khi tô, hạn chế vết nứt chân chim.', 'Cát sông hạt mịn, đã qua sàng lọc, độ dẻo cao.', 'CXT', 6, NULL, 220000.00, 0.00, 180000.00, 100, 0, 1000, NULL, 'Khối', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:07:19', '2025-12-30 09:07:19', 0),
(19, 'Cát Bê Tông (Hạt vàng)', 'Cát hạt lớn, sắc cạnh, đạt chuẩn modul độ lớn cho bê tông tươi và bê tông tay. Ít bụi bẩn, không lẫn phù sa, giúp bê tông đạt mác cao, đông kết nhanh.\r\n', 'Cát hạt to, sạch, chịu lực nén tốt cho bê tông.\r\n', 'CBT-V', 6, NULL, 350000.00, 0.00, 290000.00, 80, 0, 1000, NULL, 'Khối', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:08:30', '2025-12-30 09:08:30', 0),
(20, 'Đá Xây Dựng 1x2 (Xanh)', 'Kích thước đều 10x20mm - 20x25mm, dạng khối. Cốt liệu cứng, cường độ nén cao, chuyên dùng đổ bê tông cột, dầm, sàn cho nhà phố và công trình công nghiệp.\r\n', 'Đá xanh, chịu lực cao, ít thoi dẹt.\r\n', 'Da-XD', 6, NULL, 450000.00, 0.00, 360000.00, 75, 0, 1000, NULL, 'Khối', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:09:53', '2025-12-30 09:09:53', 0),
(21, 'Đá Mi Sàng (Lót sàn)', 'Kích thước hạt 5-10mm, được sàng tách từ đá xay. Dùng để làm gạch không nung, rải nền nhà, lót móng hoặc trộn bê tông nhựa nóng. Độ nén chặt tốt.\r\n', 'Đá hạt nhỏ, dùng lót sàn hoặc làm phụ gia.\r\n', 'Da-MS', 6, NULL, 320000.00, 0.00, 250000.00, 50, 0, 1000, NULL, 'Khối', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:10:48', '2025-12-30 09:10:48', 0),
(22, 'Tôn Lạnh Hoa Sen – 4.5 dem', 'Tôn lạnh màu AZ150, độ dày 0.45mm. Công nghệ mạ nhôm kẽm chống ăn mòn vượt trội, phản xạ nhiệt tốt giúp nhà mát mẻ. Bảo hành chính hãng Hoa Sen.\r\n', 'Tôn mạ nhôm kẽm, chống rỉ sét, độ bền cao.\r\n', 'Tol-HS-4.5', 5, NULL, 105000.00, 0.00, 88000.00, 1000, 0, 1000, NULL, 'Mét', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:12:31', '2025-12-30 09:12:31', 0),
(23, 'Tôn PU Cách Nhiệt 3 Lớp', 'Cấu tạo 3 lớp: Tôn nền + Lớp PU cách nhiệt dày 18mm + Lớp giấy bạc. Giảm tiếng ồn khi mưa, giảm nhiệt độ mái nhà đến 10 độ C. Giải pháp tối ưu cho mùa nóng.\r\n', 'Tôn kèm lớp PU và giấy bạc, chống nóng ồn.\r\n', 'Tol-PU-N3L', 5, NULL, 160000.00, 0.00, 135000.00, 600, 0, 1000, NULL, 'Mét', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:14:35', '2025-12-30 09:14:35', 0),
(24, 'Ngói Đất Nung 22 viên/m2', 'Ngói đất nung tráng men hoặc không men. Chống thấm nước, không rêu mốc. Tạo nét đẹp cổ điển, sang trọng cho mái nhà, biệt thự sân vườn.\r\n', 'Ngói đỏ truyền thống, màu bền vĩnh cửu.\r\n', 'NgoiDN', 5, NULL, 14000.00, 0.00, 11500.00, 4000, 0, 1000, NULL, 'Viên', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:15:40', '2025-12-30 09:15:40', 0),
(25, 'Tấm Polycarbonate Đặc', 'Nhựa PC đặc ruột dày 3mm, khổ 1.22m x 2.44m. Trong suốt như kính nhưng nhẹ hơn và không vỡ. Chịu lực va đập cực tốt, dùng làm mái hiên, giếng trời.\r\n', 'Tấm lấy sáng thông minh, bền như kính lực.\r\n', 'PCB', 5, NULL, 1200000.00, 0.00, 980000.00, 40, 0, 1000, NULL, 'Tấm', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:16:47', '2025-12-30 09:16:47', 0),
(26, 'Ống PVC Bình Minh Phi 27', 'Đường kính 27mm, dày 1.8mm. Nhựa uPVC không hóa dẻo, an toàn cho nước sinh hoạt. Chịu áp lực nước tốt, không đóng cặn, tuổi thọ trên 50 năm.\r\n', 'Ống nước cấp dân dụng, nhựa uPVC cao cấp.\r\n', 'PVC-BM', 7, NULL, 240000.00, 0.00, 195000.00, 400, 0, 1000, NULL, 'Cây', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:17:50', '2025-12-30 09:17:50', 0),
(27, 'Ống PVC Bình Minh Phi 90', 'Đường kính 90mm, dày 2.9mm (Class 2). Chuyên dùng cho hệ thống thoát nước thải sinh hoạt, thoát nước mưa, hệ thống tưới tiêu.\r\n', 'Ống thoát nước lớn, chịu va đập tốt.\r\n', 'PVC-BM90', 7, NULL, 240000.00, 0.00, 195000.00, 200, 0, 1000, NULL, 'Cây', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:19:11', '2025-12-30 09:19:11', 0),
(28, 'Co Vuông 90 Độ Phi 27', 'Co lơi/Co vuông 27mm chính hãng Bình Minh. Bề mặt trong láng mịn giúp dòng chảy ổn định. Mối nối kín khít tuyệt đối khi dùng keo dán ống chuyên dụng.\r\n', 'Phụ kiện nối ống góc vuông, nhựa dày.\r\n', 'Co-V-90P27', 7, NULL, 3500.00, 0.00, 2200.00, 500, 0, 1000, NULL, 'Cái', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:20:23', '2025-12-30 09:20:23', 0),
(29, 'Van Cổng Đồng Miha Phi 21', 'Van cổng (gate valve) hiệu Miha. Chất liệu đồng mạ, tay vặn màu xanh/đỏ. Ren chuẩn 21mm. Dùng để đóng mở nguồn nước tổng, chịu áp lực cao.\r\n', 'Van chặn nước bằng đồng thau, bền bỉ.\r\n', 'Van-Miha21', 7, NULL, 125000.00, 0.00, 95000.00, 100, 0, 1000, NULL, 'Cái', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:21:31', '2025-12-30 09:21:31', 0),
(30, 'Bột Trét Nội Thất Việt Mỹ', 'Bột mastic dùng cho tường trong nhà. K17Độ bám dính tốt, bề mặt trắng mịn, giúp tiết kiệm sơn phủ. Dễ xả nhám, thích hợp cho các công trình tiết kiệm chi phí.\r\n', 'Bột trét giá rẻ, trắng mịn, dễ thi công.\r\n', 'BotTret-NT-VM', 4, NULL, 95000.00, 0.00, 75000.00, 200, 0, 1000, NULL, 'bao', NULL, NULL, NULL, 'active', 0, 0, '2025-12-30 09:24:26', '2025-12-30 09:24:26', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
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
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `order_id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 15, 1, 4, '', '2025-12-23 08:54:07'),
(2, 1, 14, 1, 4, '', '2025-12-23 08:54:07'),
(3, 1, 16, 1, 5, '', '2025-12-23 08:54:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `saved_addresses`
--

CREATE TABLE `saved_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_name` varchar(100) DEFAULT 'Địa chỉ giao hàng',
  `recipient_name` varchar(255) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `province` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `saved_addresses`
--

INSERT INTO `saved_addresses` (`id`, `user_id`, `address_name`, `recipient_name`, `recipient_phone`, `province`, `address`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'nhà trọ', 'nam', '0379648264', 'Việt Nam', 'Trà Vinh', 0, '2025-12-23 08:47:44', '2025-12-23 08:47:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
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
-- Đang đổ dữ liệu cho bảng `suppliers`
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
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `PASSWORD` varchar(256) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `google_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `PASSWORD`, `email`, `full_name`, `phone`, `address`, `role`, `created_at`, `updated_at`, `google_id`, `avatar_url`) VALUES
(1, '', '$2y$12$c4BLjzUR4NRhWT3V3OT7suca2mg7mnthTtQNNWOKwsFZseurfFueu', 'namvokat@gmail.com', 'Nam Vo', '0379648264', 'Trà Vinh, Việt Nam', 'admin', '2025-12-22 09:08:33', '2025-12-23 09:07:57', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocLjawPef93lP_5luvFUEjtRO1P0ZZ8Mod4aN9NRcY9ANRnsrjw=s96-c'),
(12, 'katuv3@gmail.com', '$2y$12$c4BLjzUR4NRhWT3V3OT7suca2mg7mnthTtQNNWOKwsFZseurfFueu', 'katuv3@gmail.com', 'Katuv3', NULL, NULL, '', '2025-12-23 08:11:09', '2025-12-23 08:19:38', NULL, NULL),
(13, '1@gmail.com', '$2y$12$MzajBh3sQS8VDTkJLWRrhe1sPkzlIpvSlRjEj8hEVHHvzXyw0r/mK', '1@gmail.com', 'CoNhanQuy', '0773998235', 'B7 ĐHTV, Trà Vinh, Trà Vinh, Trà Vinh, Trà Vinh, An Giang', 'admin', '2025-12-23 09:07:32', '2025-12-30 08:17:48', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Chỉ mục cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Chỉ mục cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `CODE` (`CODE`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_product_review` (`order_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `saved_addresses`
--
ALTER TABLE `saved_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `saved_addresses`
--
ALTER TABLE `saved_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `saved_addresses`
--
ALTER TABLE `saved_addresses`
  ADD CONSTRAINT `saved_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
