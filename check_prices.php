<?php
require 'config.php';

// Kiểm tra giá sản phẩm
$sql = "SELECT id, NAME, price, sale_price FROM products LIMIT 10";
$result = $conn->query($sql);

echo "<h2>Kiểm tra giá sản phẩm:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tên</th><th>Giá</th><th>Giá sale</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['NAME']) . "</td>";
    echo "<td>" . number_format($row['price'], 0, ',', '.') . "đ</td>";
    echo "<td>" . ($row['sale_price'] ? number_format($row['sale_price'], 0, ',', '.') . "đ" : 'Không có') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
