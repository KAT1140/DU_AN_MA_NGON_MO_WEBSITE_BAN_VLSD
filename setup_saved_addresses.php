<?php
require 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS `saved_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_name` varchar(100) DEFAULT NULL COMMENT 'Tên địa chỉ: Nhà riêng, Công ty...',
  `recipient_name` varchar(100) NOT NULL COMMENT 'Tên người nhận',
  `recipient_phone` varchar(20) NOT NULL COMMENT 'Số điện thoại người nhận',
  `province` varchar(100) NOT NULL COMMENT 'Tỉnh/Thành phố',
  `address` text NOT NULL COMMENT 'Địa chỉ chi tiết',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '1 = Địa chỉ mặc định',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `saved_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sổ địa chỉ giao hàng'";

if ($conn->query($sql)) {
    echo "✅ Tạo bảng saved_addresses thành công!";
} else {
    echo "❌ Lỗi: " . $conn->error;
}

$conn->close();
?>
