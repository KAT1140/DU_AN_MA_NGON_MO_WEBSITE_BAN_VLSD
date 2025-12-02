-- Import data from vlxd_storemoi.sql

-- Insert categories
INSERT INTO `categories` (`id`, `NAME`, `description`, `parent_id`, `image`, `STATUS`, `created_at`) VALUES
(1, 'Xi măng', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(2, 'Gạch', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(3, 'Thép', NULL, NULL, NULL, 1, '2025-11-24 08:35:14'),
(4, 'Sơn', NULL, NULL, NULL, 1, '2025-11-24 08:35:14');

-- Insert suppliers
INSERT INTO `suppliers` (`id`, `NAME`, `contact_person`, `phone`, `email`, `address`, `STATUS`, `created_at`) VALUES
(1, 'Holcim', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(2, 'Tuynel', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(3, 'Hòa Phát', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(4, 'Dulux', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(5, 'Đồng Tâm', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(6, 'Việt Nhật', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(7, 'Nghi Sơn', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09'),
(8, 'Jotun', NULL, NULL, NULL, NULL, 1, '2025-11-24 08:36:09');

-- Insert products
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

-- Update AUTO_INCREMENT
ALTER TABLE `categories` AUTO_INCREMENT=5;
ALTER TABLE `suppliers` AUTO_INCREMENT=9;
ALTER TABLE `products` AUTO_INCREMENT=18;
