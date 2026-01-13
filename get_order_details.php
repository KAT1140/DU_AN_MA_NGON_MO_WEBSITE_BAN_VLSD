<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit;
}

// Lấy thông tin đơn hàng (chỉ của user hiện tại)
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $conn->prepare("SELECT 
    oi.id,
    oi.product_id,
    oi.product_name,
    oi.product_price,
    oi.quantity,
    oi.total_price
FROM order_items oi
WHERE oi.order_id = ?
ORDER BY oi.id");

$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
?>
