<?php
ob_start();
session_start();
require 'config.php';

// Bật báo lỗi dạng JSON để dễ debug (Tắt khi deploy thật)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => 'Lỗi không xác định'];

try {
    if (empty($_POST['product_id']) || !isset($_POST['quantity'])) {
        throw new Exception('Thiếu dữ liệu gửi lên');
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    
    // 1. Kiểm tra session giỏ hàng
    if (!isset($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = session_id();
    }
    $session_val = $_SESSION['cart_id'];

    // 2. Lấy user_id nếu đã đăng nhập
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NULL';

    // 3. Lấy giá sản phẩm
    $sql_price = "SELECT price FROM products WHERE id = $product_id";
    $res = $conn->query($sql_price);

    if (!$res || $res->num_rows === 0) { 
        throw new Exception('Sản phẩm không tồn tại');
    }
    $price = (float)$res->fetch_assoc()['price'];

    // 4. Tìm giỏ hàng hiện tại (Check cả session_id lẫn user_id nếu có)
    $cart_sql = "SELECT id FROM cart WHERE session_id = '$session_val'";
    if ($user_id !== 'NULL') {
        $cart_sql .= " OR user_id = $user_id";
    }
    $cart_res = $conn->query($cart_sql);

    if ($cart_res && $cart_res->num_rows > 0) {
        $cart_row = $cart_res->fetch_assoc();
        $cart_id = $cart_row['id'];
        
        // Nếu user đã đăng nhập mà cart này chưa có user_id -> Update user_id vào
        if ($user_id !== 'NULL') {
            $conn->query("UPDATE cart SET user_id = $user_id WHERE id = $cart_id AND user_id IS NULL");
        }
    } else {
        // Tạo giỏ hàng mới
        // Lưu ý: $user_id ở đây là số hoặc chuỗi 'NULL', nên không bọc dấu nháy trong SQL
        $insert_sql = "INSERT INTO cart (session_id, user_id, created_at) VALUES ('$session_val', $user_id, NOW())";
        if (!$conn->query($insert_sql)) {
            throw new Exception('Lỗi tạo giỏ hàng: ' . $conn->error);
        }
        $cart_id = $conn->insert_id;
    }

    // 5. Thêm/Cập nhật sản phẩm vào cart_items
    $check = $conn->query("SELECT id FROM cart_items WHERE cart_id = $cart_id AND product_id = $product_id");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $conn->query("UPDATE cart_items SET quantity = quantity + $quantity, price = $price WHERE id = " . $row['id']);
    } else {
        $conn->query("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES ($cart_id, $product_id, $quantity, $price)");
    }

    // 6. Tính tổng số lượng mới để update icon
    $total_res = $conn->query("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = $cart_id");
    $new_count = $total_res->fetch_assoc()['total'] ?? 0;

    $response['success'] = true;
    $response['message'] = "✅ Đã thêm vào giỏ hàng!";
    $response['new_cart_count'] = (int)$new_count;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>