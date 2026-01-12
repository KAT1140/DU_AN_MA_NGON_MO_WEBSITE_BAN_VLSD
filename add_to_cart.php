<?php
require 'config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id <= 0 || $quantity <= 0) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, NAME, price, quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Sản phẩm không tồn tại');
    }

    $product = $result->fetch_assoc();

    // Kiểm tra tồn kho
    if ($product['quantity'] < $quantity) {
        throw new Exception('Số lượng sản phẩm không đủ');
    }

    // Xác định cart_id
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = $_SESSION['cart_id'] ?? session_id();

    // Tìm hoặc tạo cart
    if ($user_id) {
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM cart WHERE session_id = ? LIMIT 1");
        $stmt->bind_param("s", $session_id);
    }
    
    $stmt->execute();
    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows > 0) {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
    } else {
        // Tạo cart mới
        if ($user_id) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, session_id) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $session_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (session_id) VALUES (?)");
            $stmt->bind_param("s", $session_id);
        }
        $stmt->execute();
        $cart_id = $conn->insert_id;
    }

    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cart_id, $product_id);
    $stmt->execute();
    $item_result = $stmt->get_result();

    if ($item_result->num_rows > 0) {
        // Cập nhật số lượng
        $item = $item_result->fetch_assoc();
        $new_quantity = $item['quantity'] + $quantity;
        
        if ($new_quantity > $product['quantity']) {
            throw new Exception('Số lượng vượt quá tồn kho');
        }

        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $item['id']);
        $stmt->execute();
    } else {
        // Thêm sản phẩm mới vào giỏ
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $product['price']);
        $stmt->execute();
    }

    // Đếm tổng số sản phẩm trong giỏ
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $count = $count_result->fetch_assoc();
    $cart_count = $count['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm sản phẩm vào giỏ hàng',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
