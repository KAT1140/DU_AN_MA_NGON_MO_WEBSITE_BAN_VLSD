<?php
require 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Lỗi cập nhật'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($cart_item_id <= 0 || $quantity < 1) {
        $response['message'] = 'Dữ liệu không hợp lệ';
        echo json_encode($response);
        exit;
    }
    
    // Lấy user_id (nếu đã đăng nhập)
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    // --- SỬA LOGIC KIỂM TRA ---
    // Kiểm tra item thuộc về cart của session hiện tại HOẶC user hiện tại
    $sql = "SELECT ci.id FROM cart_items ci
            JOIN cart c ON c.id = ci.cart_id
            WHERE ci.id = ? 
            AND (c.session_id = ? OR (c.user_id = ? AND c.user_id > 0)) 
            LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['message'] = 'Lỗi hệ thống: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Bind 3 tham số: id sản phẩm (int), session (string), user_id (int)
    $stmt->bind_param('isi', $cart_item_id, $cart_session, $user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    $stmt->close();
    
    if (!$check_result || $check_result->num_rows == 0) {
        $response['message'] = 'Không tìm thấy sản phẩm trong giỏ của bạn';
        echo json_encode($response);
        exit;
    }
    
    // Cập nhật số lượng
    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param('ii', $quantity, $cart_item_id);
    
    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật thành công';
    } else {
        $response['message'] = 'Lỗi database: ' . $update_stmt->error;
    }
    $update_stmt->close();
}

echo json_encode($response);
?>