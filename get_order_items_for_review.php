<?php
require 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$order_id = (int)($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

// Kiểm tra đơn hàng có thuộc về user này không và đã được giao chưa
$check = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
$check->bind_param('ii', $order_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit();
}

$order = $result->fetch_assoc();
if ($order['order_status'] !== 'delivered') {
    echo json_encode(['success' => false, 'message' => 'Chỉ có thể đánh giá đơn hàng đã giao']);
    exit();
}

// Lấy danh sách sản phẩm trong đơn hàng
$items_sql = "SELECT oi.product_id, oi.product_name, oi.quantity, p.images 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $images = !empty($item['images']) ? explode(',', $item['images']) : [];
    $first_image = !empty($images) ? 'uploads/' . trim($images[0]) : 'https://via.placeholder.com/80';
    
    $items[] = [
        'product_id' => $item['product_id'],
        'product_name' => $item['product_name'],
        'quantity' => $item['quantity'],
        'image' => $first_image
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items
]);
