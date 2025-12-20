<?php
require 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);
$product_ids = $_POST['product_ids'] ?? [];

if ($order_id === 0 || empty($product_ids)) {
    $_SESSION['error'] = 'Dữ liệu không hợp lệ';
    header('Location: my_orders.php');
    exit();
}

// Kiểm tra đơn hàng có thuộc về user này không và đã được giao chưa
$check = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
$check->bind_param('ii', $order_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng';
    header('Location: my_orders.php');
    exit();
}

$order = $result->fetch_assoc();
if ($order['order_status'] !== 'delivered') {
    $_SESSION['error'] = 'Chỉ có thể đánh giá đơn hàng đã giao';
    header('Location: my_orders.php');
    exit();
}

// Kiểm tra xem đã đánh giá chưa
$check_review = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE order_id = $order_id");
if ($check_review->fetch_assoc()['count'] > 0) {
    $_SESSION['error'] = 'Đơn hàng này đã được đánh giá';
    header('Location: my_orders.php');
    exit();
}

// Lưu đánh giá
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO reviews (order_id, product_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($product_ids as $product_id) {
        $product_id = (int)$product_id;
        $rating = (int)($_POST["rating_$product_id"] ?? 0);
        $comment = trim($_POST["comment_$product_id"] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Đánh giá sao không hợp lệ');
        }
        
        $stmt->bind_param('iiiis', $order_id, $product_id, $user_id, $rating, $comment);
        $stmt->execute();
    }
    
    $conn->commit();
    $_SESSION['success'] = '✅ Cảm ơn bạn đã đánh giá! Ý kiến của bạn rất quan trọng với chúng tôi.';
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = '❌ Lỗi khi lưu đánh giá: ' . $e->getMessage();
}

header('Location: my_orders.php');
exit();
