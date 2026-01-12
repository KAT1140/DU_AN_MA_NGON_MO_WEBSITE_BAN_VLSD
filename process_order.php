<?php
require 'config.php';
require 'inventory_functions.php';

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
$cart_session = session_id(); // Thêm dòng này

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

// Kiểm tra tồn kho bằng hệ thống inventory
$cart_items = [];
$cart_products_for_check = [];
while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
    $cart_products_for_check[$item['product_id']] = $item['quantity'];
}

// Sử dụng hàm kiểm tra inventory
$inventory_check = checkInventoryAvailability($conn, $cart_products_for_check);
if (!$inventory_check['success']) {
    $errors[] = $inventory_check['message'];
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
                    customer_address, province, shipping_address, note, subtotal, shipping_fee, 
                    total_amount, payment_method, payment_status, order_status
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
    
    $order_stmt = $conn->prepare($order_sql);
    $shipping_address = $customer_address . ', ' . $province;
    $order_stmt->bind_param('sisssssssddss', 
        $order_code, $user_id, $customer_name, $customer_email, $customer_phone,
        $customer_address, $province, $shipping_address, $note, $subtotal, $shipping_fee,
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
    }
    
    $order_item_stmt->close();
    
    // Cập nhật inventory cho đơn hàng
    if (!updateInventoryForOrder($conn, $order_id, $user_id)) {
        throw new Exception('Lỗi cập nhật inventory');
    }
    
    // Xử lý theo phương thức thanh toán
    if ($payment_method === 'momo' || $payment_method === 'momo_qr') {
        // Debug log
        error_log("Processing MoMo payment for order_id: " . $order_id . ", amount: " . $total);
        
        // Thanh toán MoMo - không xóa giỏ hàng và không xác nhận đơn hàng ngay
        // Đơn hàng sẽ được xác nhận sau khi nhận callback từ MoMo
        
        // Commit transaction để lưu đơn hàng
        $conn->commit();
        
        // Tạo thanh toán MoMo
        require_once 'MoMoPaymentHandler.php';
        $momoHandler = new MoMoPaymentHandler($conn);
        
        $orderInfo = "Thanh toán đơn hàng " . $order_code . " - VLXD Store";
        $momoResult = $momoHandler->createPayment($order_id, $total, $orderInfo);
        
        error_log("MoMo payment result: " . json_encode($momoResult));
        
        if ($momoResult['success']) {
            // Chuyển đến trang hiển thị QR
            $redirectUrl = 'momo_qr_display.php?request_id=' . urlencode($momoResult['request_id']) . 
                          '&order_id=' . urlencode($order_id);
            error_log("Redirecting to: " . $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit();
        } else {
            // Lỗi tạo thanh toán MoMo - rollback và thông báo lỗi
            error_log("MoMo payment creation failed: " . $momoResult['error']);
            
            $conn->begin_transaction();
            
            // Hoàn lại inventory
            if (!restoreInventoryForCancelledOrder($conn, $order_id, $user_id)) {
                error_log("Failed to restore inventory for cancelled order: " . $order_id);
            }
            
            // Xóa đơn hàng đã tạo
            $delete_order_sql = "DELETE FROM orders WHERE id = ?";
            $delete_order_stmt = $conn->prepare($delete_order_sql);
            $delete_order_stmt->bind_param('i', $order_id);
            $delete_order_stmt->execute();
            $delete_order_stmt->close();
            
            $conn->commit();
            
            $_SESSION['checkout_errors'] = ['Không thể tạo thanh toán MoMo: ' . $momoResult['error']];
            header('Location: checkout.php');
            exit();
        }
    } elseif ($payment_method === 'banking') {
        // Thanh toán chuyển khoản ngân hàng
        
        // Commit transaction để lưu đơn hàng
        $conn->commit();
        
        // Lưu thông tin đơn hàng vào session
        $_SESSION['last_order'] = [
            'order_code' => $order_code,
            'order_id' => $order_id,
            'customer_name' => $customer_name,
            'total' => $total,
            'payment_method' => 'banking'
        ];
        
        // Chuyển đến trang hiển thị thông tin chuyển khoản
        header('Location: banking_payment.php?order_id=' . $order_id);
        exit();
    } else {
        // Thanh toán COD hoặc Banking - xử lý như cũ
        
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
    }
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['checkout_errors'] = [$e->getMessage()];
    header('Location: checkout.php');
    exit();
}
?>
