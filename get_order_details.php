<?php
require 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$order_id = (int)($_GET['id'] ?? 0);

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? 'user') === 'admin';

// Lấy thông tin đơn hàng (admin xem tất cả, user chỉ xem của mình)
if ($is_admin) {
    $order_sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param('i', $order_id);
} else {
    $order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param('ii', $order_id, $user_id);
}

$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit();
}

$order = $order_result->fetch_assoc();
$stmt->close();

// Lấy các sản phẩm trong đơn hàng
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
