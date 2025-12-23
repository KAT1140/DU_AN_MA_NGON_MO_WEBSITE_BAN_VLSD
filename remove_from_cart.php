<?php
require 'config.php';
header('Content-Type: application/json');

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
$session = $_SESSION['cart_id'];

// Lấy user_id nếu đã đăng nhập [FIX: Thêm đoạn này]
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($cart_item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID mục không hợp lệ']);
    exit;
}

// [FIX: Sửa câu SQL để tìm theo cả Session ID HOẶC User ID]
$sql = "SELECT ci.cart_id, ci.quantity 
        FROM cart_items ci 
        JOIN cart c ON ci.cart_id = c.id 
        WHERE ci.id = ? 
        AND (c.session_id = ? OR (c.user_id = ? AND c.user_id > 0)) 
        LIMIT 1";

$stmt = $conn->prepare($sql);
// Bind 3 tham số: id item (int), session (string), user_id (int)
$stmt->bind_param('isi', $cart_item_id, $session, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mục không tìm thấy hoặc bạn không có quyền xóa']);
    exit;
}

$row = $res->fetch_assoc();
$cart_id = (int)$row['cart_id'];
$stmt->close();

// Thực hiện xóa
$stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
$stmt->bind_param('ii', $cart_item_id, $cart_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Tính lại tổng sau khi xóa để cập nhật UI
    $res2 = $conn->query("SELECT IFNULL(SUM(quantity),0) AS qty, IFNULL(SUM(quantity * price),0) AS total FROM cart_items WHERE cart_id = $cart_id");
    $s = $res2 ? $res2->fetch_assoc() : ['qty' => 0, 'total' => 0];
    $new_qty = (int)$s['qty'];
    $new_total = (float)$s['total'];
    
    // Cập nhật lại tổng số lượng vào bảng cart cha
    $conn->query("UPDATE cart SET quantity = $new_qty, updated_at = NOW() WHERE id = $cart_id");

    echo json_encode([
        'success' => true, 
        'message' => '✅ Đã xóa mục khỏi giỏ hàng', 
        'new_qty' => $new_qty, 
        'new_total' => $new_total
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '❌ Lỗi khi xóa mục']);
}
?>