<?php
require_once 'config.php';
require_once 'momo_config.php';
require_once 'MoMoPaymentHandler.php';

// Log callback để debug
error_log("MoMo Callback received: " . file_get_contents('php://input'));

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

try {
    // Đọc dữ liệu callback
    $input = file_get_contents('php://input');
    $callbackData = json_decode($input, true);
    
    if (!$callbackData) {
        error_log("Invalid JSON in MoMo callback");
        http_response_code(400);
        exit();
    }
    
    // Log callback data
    error_log("MoMo Callback Data: " . json_encode($callbackData));
    
    // Khởi tạo MoMo handler
    $momoHandler = new MoMoPaymentHandler($conn);
    
    // Xác thực chữ ký
    if (!$momoHandler->verifySignature($callbackData)) {
        error_log("Invalid signature in MoMo callback");
        http_response_code(400);
        exit();
    }
    
    // Lấy thông tin từ callback
    $requestId = $callbackData['requestId'] ?? '';
    $orderId = $callbackData['orderId'] ?? '';
    $resultCode = $callbackData['resultCode'] ?? -1;
    $message = $callbackData['message'] ?? '';
    $localMessage = $callbackData['localMessage'] ?? '';
    $transId = $callbackData['transId'] ?? '';
    $signature = $callbackData['signature'] ?? '';
    
    if (empty($requestId)) {
        error_log("Missing requestId in MoMo callback");
        http_response_code(400);
        exit();
    }
    
    // Lấy thông tin giao dịch
    $transaction = $momoHandler->getTransactionByRequestId($requestId);
    if (!$transaction) {
        error_log("Transaction not found for requestId: " . $requestId);
        http_response_code(404);
        exit();
    }
    
    // Kiểm tra giao dịch đã được xử lý chưa (tránh duplicate callback)
    if ($transaction['status'] !== MOMO_STATUS_PENDING) {
        error_log("Transaction already processed: " . $requestId);
        http_response_code(200);
        echo json_encode(['message' => 'Already processed']);
        exit();
    }
    
    // Bắt đầu transaction database
    $conn->begin_transaction();
    
    try {
        // Xác định trạng thái mới
        $newStatus = ($resultCode == 0) ? MOMO_STATUS_SUCCESS : MOMO_STATUS_FAILED;
        
        // Cập nhật trạng thái giao dịch MoMo
        $updateData = [
            'resultCode' => $resultCode,
            'localMessage' => $localMessage,
            'signature' => $signature
        ];
        
        $momoHandler->updateTransactionStatus($requestId, $newStatus, $transId, $updateData);
        
        // Cập nhật callback data đầy đủ
        $updateCallbackSql = "UPDATE momo_transactions SET callback_data = ? WHERE momo_request_id = ?";
        $updateCallbackStmt = $conn->prepare($updateCallbackSql);
        $fullCallbackData = json_encode($callbackData);
        $updateCallbackStmt->bind_param('ss', $fullCallbackData, $requestId);
        $updateCallbackStmt->execute();
        $updateCallbackStmt->close();
        
        // Cập nhật đơn hàng
        if ($resultCode == 0) {
            // Thanh toán thành công - xác nhận đơn hàng
            $updateOrderSql = "UPDATE orders SET 
                              payment_status = 'paid', 
                              order_status = 'confirmed',
                              momo_trans_id = ?,
                              momo_order_id = ?,
                              payment_gateway_response = ?
                              WHERE id = ?";
            $updateOrderStmt = $conn->prepare($updateOrderSql);
            $updateOrderStmt->bind_param('sssi', $transId, $orderId, $fullCallbackData, $transaction['order_id']);
            $updateOrderStmt->execute();
            $updateOrderStmt->close();
            
            error_log("Order confirmed successfully: " . $transaction['order_id']);
            
            // TODO: Gửi email xác nhận cho khách hàng
            // sendOrderConfirmationEmail($transaction['order_id']);
            
        } else {
            // Thanh toán thất bại - cập nhật trạng thái
            $updateOrderSql = "UPDATE orders SET 
                              payment_status = 'failed',
                              payment_gateway_response = ?
                              WHERE id = ?";
            $updateOrderStmt = $conn->prepare($updateOrderSql);
            $updateOrderStmt->bind_param('si', $fullCallbackData, $transaction['order_id']);
            $updateOrderStmt->execute();
            $updateOrderStmt->close();
            
            // Hoàn lại số lượng sản phẩm trong kho
            $restoreStockSql = "UPDATE products p 
                               JOIN order_items oi ON p.id = oi.product_id 
                               SET p.quantity = p.quantity + oi.quantity 
                               WHERE oi.order_id = ?";
            $restoreStockStmt = $conn->prepare($restoreStockSql);
            $restoreStockStmt->bind_param('i', $transaction['order_id']);
            $restoreStockStmt->execute();
            $restoreStockStmt->close();
            
            error_log("Payment failed, stock restored for order: " . $transaction['order_id']);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Trả về response cho MoMo
        http_response_code(200);
        echo json_encode([
            'message' => 'Callback processed successfully',
            'requestId' => $requestId,
            'orderId' => $orderId,
            'resultCode' => $resultCode
        ]);
        
        error_log("MoMo callback processed successfully for requestId: " . $requestId);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        error_log("Error processing MoMo callback: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("MoMo Callback Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Gửi email xác nhận đơn hàng (TODO: implement)
 */
function sendOrderConfirmationEmail($orderId) {
    // TODO: Implement email sending
    error_log("TODO: Send confirmation email for order: " . $orderId);
}
?>