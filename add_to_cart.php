<?php
require 'config.php';

header('Content-Type: application/json');

// Expect product_id and quantity
if (!empty($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $session = $_SESSION['cart_id'];
    $user_id = $_SESSION['user_id'] ?? null;

    // Verify product exists and get price
    $stmt = $conn->prepare("SELECT id, price FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => '❌ Lỗi: Sản phẩm không tồn tại!']);
        exit;
    }
    $prod = $res->fetch_assoc();
    $price = (float)$prod['price'];
    $stmt->close();

    // Find or create cart header for this session (or user)
    $cart_id = null;
    $sql = "SELECT id FROM cart WHERE session_id = '" . $conn->real_escape_string($session) . "' LIMIT 1";
    $r = $conn->query($sql);
    if ($r && $r->num_rows > 0) {
        $cart_row = $r->fetch_assoc();
        $cart_id = (int)$cart_row['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, session_id, quantity, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())");
        $uid = $user_id ?? 0;
        $stmt->bind_param('is', $uid, $session);
        if ($stmt->execute()) {
            $cart_id = (int)$conn->insert_id;
        }
        $stmt->close();
    }

    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => '❌ Lỗi: Không thể tạo giỏ hàng.']);
        exit;
    }

    // Check if cart_items already has this product
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
    $stmt->bind_param('ii', $cart_id, $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $newQty = (int)$row['quantity'] + $quantity;
        $ci_id = (int)$row['id'];
        $stmt2 = $conn->prepare("UPDATE cart_items SET quantity = ?, price = ?, updated_at = NOW() WHERE id = ?");
        $stmt2->bind_param('idi', $newQty, $price, $ci_id);
        $ok = $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt2 = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt2->bind_param('iiid', $cart_id, $product_id, $quantity, $price);
        $ok = $stmt2->execute();
        $stmt2->close();
    }

    if ($ok) {
        // Optionally update cart.quantity summary
        $conn->query("UPDATE cart SET quantity = (SELECT IFNULL(SUM(quantity),0) FROM cart_items WHERE cart_id = $cart_id), updated_at = NOW() WHERE id = $cart_id");
        echo json_encode(['success' => true, 'message' => "✅ Đã thêm $quantity sản phẩm vào giỏ hàng!"]);
    } else {
        echo json_encode(['success' => false, 'message' => '❌ Lỗi khi thêm vào giỏ hàng']);
    }

} else {
    echo json_encode(['success' => false, 'message' => '❌ Thiếu thông tin sản phẩm']);
}
?>
