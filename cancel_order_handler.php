<?php
require 'config.php';
require 'inventory_functions.php';

// Admin-only functionality
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$cancel_reason = trim($input['cancel_reason'] ?? '');

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Lấy thông tin đơn hàng
    $order_sql = "SELECT id, order_status, user_id FROM orders WHERE id = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param('i', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    $order_stmt->close();
    
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng');
    }
    
    if ($order['order_status'] === 'cancelled') {
        throw new Exception('Đơn hàng đã được hủy trước đó');
    }
    
    if ($order['order_status'] === 'delivered') {
        throw new Exception('Không thể hủy đơn hàng đã giao');
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng
    $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $order_items = [];
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
    $items_stmt->close();
    
    // Hoàn trả inventory
    if (!restoreInventoryForCancelledOrder($conn, $order_id, $_SESSION['user_id'])) {
        throw new Exception('Lỗi khi hoàn trả inventory');
    }
    
    // Cập nhật trạng thái đơn hàng
    $update_sql = "UPDATE orders SET order_status = 'cancelled', cancel_reason = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $cancel_reason, $order_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Lỗi khi cập nhật trạng thái đơn hàng');
    }
    $update_stmt->close();
    
    // Ghi log hủy đơn
    $log_sql = "INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, reason, created_at) 
                VALUES (?, ?, 'cancelled', ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_sql);
    $old_status = $order['order_status'];
    $changed_by = $_SESSION['user_id'];
    $log_stmt->bind_param('issis', $order_id, $old_status, $changed_by, $cancel_reason);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đã hủy đơn hàng và hoàn trả ' . count($order_items) . ' sản phẩm vào kho thành công'
    ]);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>