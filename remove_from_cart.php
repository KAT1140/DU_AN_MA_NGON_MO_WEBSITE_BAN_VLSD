<?php
require 'config.php';
header('Content-Type: application/json');

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
$session = $_SESSION['cart_id'];

if ($cart_item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID mục không hợp lệ']);
    exit;
}

// Verify that the cart_item belongs to a cart owned by this session (safety)
$stmt = $conn->prepare("SELECT ci.cart_id, ci.quantity FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE ci.id = ? AND c.session_id = ? LIMIT 1");
$stmt->bind_param('is', $cart_item_id, $session);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mục không tìm thấy hoặc không thuộc phiên này']);
    exit;
}
$row = $res->fetch_assoc();
$cart_id = (int)$row['cart_id'];
$stmt->close();

// Delete the cart_item
$stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
$stmt->bind_param('ii', $cart_item_id, $cart_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Recalculate total qty for cart
    $res2 = $conn->query("SELECT IFNULL(SUM(quantity),0) AS qty, IFNULL(SUM(quantity * price),0) AS total FROM cart_items WHERE cart_id = $cart_id");
    $s = $res2 ? $res2->fetch_assoc() : ['qty' => 0, 'total' => 0];
    $new_qty = (int)$s['qty'];
    $new_total = (float)$s['total'];
    // update cart.summary quantity
    $conn->query("UPDATE cart SET quantity = $new_qty, updated_at = NOW() WHERE id = $cart_id");

    echo json_encode(['success' => true, 'message' => '✅ Đã xóa mục khỏi giỏ hàng', 'new_qty' => $new_qty, 'new_total' => $new_total]);
} else {
    echo json_encode(['success' => false, 'message' => '❌ Lỗi khi xóa mục']);
}
