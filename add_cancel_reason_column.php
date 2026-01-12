<?php
require 'config.php';

echo "<h2>Thêm cột cancel_reason vào bảng orders</h2>";
echo "<hr>";

// Kiểm tra xem cột đã tồn tại chưa
$check = $conn->query("SHOW COLUMNS FROM orders LIKE 'cancel_reason'");

if ($check->num_rows > 0) {
    echo "<p style='color: blue;'>✓ Cột cancel_reason đã tồn tại</p>";
} else {
    // Thêm cột
    $sql = "ALTER TABLE orders ADD COLUMN cancel_reason TEXT NULL AFTER order_status";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Đã thêm cột cancel_reason thành công!</p>";
    } else {
        echo "<p style='color: red;'>✗ Lỗi: " . $conn->error . "</p>";
    }
}

echo "<br>";
echo "<a href='my_orders.php' style='background: #8b5cf6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Về đơn hàng của tôi</a>";
?>
