<?php
require_once 'config.php';
require_once 'momo_config.php';
require_once 'MoMoPaymentHandler.php';

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Đọc JSON input
$input = json_decode(file_get_contents('php://input'), true);
$requestId = $input['request_id'] ?? '';
$orderId = $input['order_id'] ?? '';

if (empty($requestId) || empty($orderId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Khởi tạo MoMo handler
    $momoHandler = new MoMoPaymentHandler($conn);
    
    // Lấy thông tin giao dịch
    $transaction = $momoHandler->getTransactionByRequestId($requestId);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit();
    }
    
    // Kiểm tra timeout
    if ($momoHandler->checkTimeout($requestId)) {
        echo json_encode([
            'status' => 'timeout',
            'message' => 'Giao dịch đã hết hạn'
        ]);
        exit();
    }
    
    // Lấy thông tin đơn hàng
    $orderSql = "SELECT payment_status, order_status FROM orders WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }
    
    // Tính thời gian còn lại
    $expiresAt = new DateTime($transaction['expires_at']);
    $now = new DateTime();
    $timeLeft = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
    
    // Trả về trạng thái
    $response = [
        'status' => $transaction['status'],
        'order_status' => $order['order_status'],
        'payment_status' => $order['payment_status'],
        'time_left' => $timeLeft,
        'expires_at' => $transaction['expires_at']
    ];
    
    // Thêm thông tin bổ sung tùy theo trạng thái
    switch ($transaction['status']) {
        case MOMO_STATUS_SUCCESS:
            $response['message'] = 'Thanh toán thành công';
            $response['momo_trans_id'] = $transaction['momo_trans_id'];
            break;
            
        case MOMO_STATUS_FAILED:
            $response['message'] = $transaction['local_message'] ?: 'Thanh toán thất bại';
            $response['result_code'] = $transaction['result_code'];
            break;
            
        case MOMO_STATUS_TIMEOUT:
            $response['message'] = 'Giao dịch đã hết hạn';
            break;
            
        case MOMO_STATUS_CANCELLED:
            $response['message'] = 'Giao dịch đã bị hủy';
            break;
            
        default:
            $response['message'] = 'Đang chờ thanh toán';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Payment Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái thanh toán'
    ]);
}
?>
