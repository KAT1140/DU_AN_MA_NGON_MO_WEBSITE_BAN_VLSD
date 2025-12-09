<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ form
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? '');
$province = trim($_POST['province'] ?? '');
$note = trim($_POST['note'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'cod');
$shipping_fee = floatval($_POST['shipping_fee'] ?? 0);
$subtotal = floatval($_POST['subtotal'] ?? 0);
$total = floatval($_POST['total'] ?? 0);

// Validation
$errors = [];
if (empty($customer_name)) $errors[] = 'Họ tên không được để trống';
if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
if (empty($customer_phone)) $errors[] = 'Số điện thoại không được để trống';
if (empty($customer_address)) $errors[] = 'Địa chỉ không được để trống';
if (empty($province)) $errors[] = 'Vui lòng chọn tỉnh/thành phố';

// Kiểm tra giỏ hàng
$sql = "SELECT ci.id, ci.quantity, ci.price, p.id as product_id, p.NAME as product_name, p.quantity as stock 
        FROM cart c
        JOIN cart_items ci ON ci.cart_id = c.id
        JOIN products p ON p.id = ci.product_id
        WHERE c.session_id = ? OR (c.user_id = ? AND c.user_id != 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $cart_session, $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    $errors[] = 'Giỏ hàng trống';
}

// Kiểm tra tồn kho
$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    if ($item['quantity'] > $item['stock']) {
        $errors[] = "Sản phẩm '{$item['product_name']}' chỉ còn {$item['stock']} sản phẩm trong kho";
    }
    $cart_items[] = $item;
}

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: checkout.php');
    exit();
}

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Tạo mã đơn hàng
    $order_code = 'VLXD' . date('Ymd') . strtoupper(uniqid());
    
    // Insert vào bảng orders
    $order_sql = "INSERT INTO orders (
                    order_code, user_id, customer_name, customer_email, customer_phone, 
                    customer_address, shipping_address, note, subtotal, shipping_fee, 
                    total_amount, payment_method, payment_status, order_status
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
    
    $order_stmt = $conn->prepare($order_sql);
    $shipping_address = $customer_address . ', ' . $province;
    $order_stmt->bind_param('sissssssddds', 
        $order_code, $user_id, $customer_name, $customer_email, $customer_phone,
        $customer_address, $shipping_address, $note, $subtotal, $shipping_fee,
        $total, $payment_method
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception('Lỗi khi tạo đơn hàng: ' . $order_stmt->error);
    }
    
    $order_id = $order_stmt->insert_id;
    $order_stmt->close();
    
    // Insert order items
    $order_item_sql = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) 
                       VALUES (?, ?, ?, ?, ?, ?)";
    $order_item_stmt = $conn->prepare($order_item_sql);
    
    foreach ($cart_items as $item) {
        $item_total = $item['quantity'] * $item['price'];
        $order_item_stmt->bind_param('iisdid', 
            $order_id, $item['product_id'], $item['product_name'], 
            $item['price'], $item['quantity'], $item_total
        );
        
        if (!$order_item_stmt->execute()) {
            throw new Exception('Lỗi khi thêm sản phẩm vào đơn hàng: ' . $order_item_stmt->error);
        }
        
        // Cập nhật số lượng tồn kho
        $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    $order_item_stmt->close();
    
    // Xóa giỏ hàng
    $delete_cart_sql = "DELETE c FROM cart c 
                        LEFT JOIN cart_items ci ON ci.cart_id = c.id 
                        WHERE c.session_id = ? OR (c.user_id = ? AND c.user_id != 0)";
    $delete_stmt = $conn->prepare($delete_cart_sql);
    $delete_stmt->bind_param('si', $cart_session, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Lưu thông tin đơn hàng vào session để hiển thị ở trang thành công
    $_SESSION['last_order'] = [
        'order_code' => $order_code,
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'total' => $total
    ];
    
    // Chuyển đến trang thành công
    header('Location: order_success.php');
    exit();
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['checkout_errors'] = [$e->getMessage()];
    header('Location: checkout.php');
    exit();
}
?>