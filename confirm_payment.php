<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Cập nhật trạng thái đơn hàng
// Admin sẽ xác nhận sau khi kiểm tra thanh toán
$update_stmt = $conn->prepare("UPDATE orders SET order_status = 'pending', payment_status = 'pending', updated_at = NOW() WHERE id = ?");
$update_stmt->bind_param('i', $order_id);
$update_stmt->execute();
$update_stmt->close();

// Lưu thông tin vào session
$_SESSION['last_order'] = [
    'order_code' => $order['order_code'],
    'order_id' => $order_id,
    'customer_name' => $order['customer_name'],
    'total' => $order['total_amount'],
    'payment_method' => $order['payment_method']
];

header('Location: order_success.php');
exit();
?>
