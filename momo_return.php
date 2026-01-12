<?php
require_once 'config.php';
require_once 'momo_config.php';
require_once 'MoMoPaymentHandler.php';

// Lấy thông tin từ URL parameters
$requestId = $_GET['requestId'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$resultCode = $_GET['resultCode'] ?? -1;
$message = $_GET['message'] ?? '';

if (empty($requestId)) {
    $_SESSION['error'] = 'Thông tin thanh toán không hợp lệ';
    header('Location: checkout.php');
    exit();
}

try {
    // Khởi tạo MoMo handler
    $momoHandler = new MoMoPaymentHandler($conn);
    
    // Lấy thông tin giao dịch
    $transaction = $momoHandler->getTransactionByRequestId($requestId);
    
    if (!$transaction) {
        $_SESSION['error'] = 'Không tìm thấy giao dịch';
        header('Location: checkout.php');
        exit();
    }
    
    // Lấy thông tin đơn hàng
    $orderSql = "SELECT * FROM orders WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('i', $transaction['order_id']);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    
    if (!$order) {
        $_SESSION['error'] = 'Không tìm thấy đơn hàng';
        header('Location: checkout.php');
        exit();
    }
    
    // Xử lý theo kết quả
    if ($resultCode == 0) {
        // Thanh toán thành công
        $_SESSION['success'] = 'Thanh toán thành công! Đơn hàng của bạn đã được xác nhận.';
        $_SESSION['last_order'] = [
            'order_code' => $order['order_code'],
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'total' => $order['total_amount'],
            'payment_method' => 'MoMo'
        ];
        header('Location: order_success.php');
        exit();
    } else {
        // Thanh toán thất bại
        $_SESSION['error'] = 'Thanh toán thất bại: ' . $message;
        header('Location: checkout.php');
        exit();
    }
    
} catch (Exception $e) {
    error_log("MoMo Return Error: " . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra khi xử lý thanh toán';
    header('Location: checkout.php');
    exit();
}
?>