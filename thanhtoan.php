<?php
// BẮT ĐẦU SESSION VÀ KẾT NỐI DATABASE
session_start();
include 'config.php'; 

// KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// KIỂM TRA GIỎ HÀNG
if (empty($_SESSION['cart'])) {
    header("Location: cart.php"); // Chuyển về giỏ hàng nếu trống
    exit();
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'];
$total_amount = 0;
$shipping_fee = 30000; // Phí ship cố định (Bạn có thể thay đổi)
$errors = [];

// TÍNH TỔNG TIỀN
foreach ($cart as $product_id => $item) {
    // Lưu ý: Đảm bảo item['price'] và item['quantity'] đã được thiết lập đúng
    $total_amount += $item['price'] * $item['quantity'];
}
$grand_total = $total_amount + $shipping_fee;

// LẤY THÔNG TIN NGƯỜI DÙNG MẶC ĐỊNH từ bảng users
$user_info = [];
if ($conn && $stmt = $conn->prepare("SELECT full_name, phone, address FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_info = $result->fetch_assoc();
    }
    $stmt->close();
}


// XỬ LÝ THANH TOÁN (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy thông tin từ form
    $recipient_name = trim($_POST['recipient_name'] ?? $user_info['full_name']);
    $recipient_phone = trim($_POST['recipient_phone'] ?? $user_info['phone']);
    $shipping_address = trim($_POST['shipping_address'] ?? $user_info['address']);
    $payment_method = $_POST['payment_method'] ?? 'COD'; 

    // Kiểm tra dữ liệu bắt buộc
    if (empty($recipient_name) || empty($recipient_phone) || empty($shipping_address)) {
        $errors[] = "Vui lòng điền đầy đủ thông tin giao hàng.";
    }

    if (empty($errors)) {
        // BẮT ĐẦU GIAO DỊCH (TRANSACTION)
        $conn->begin_transaction();
        
        try {
            // 1. TẠO ĐƠN HÀNG CHÍNH (orders)
            $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, recipient_name, recipient_phone, payment_method, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt = $conn->prepare($order_sql);
            $stmt->bind_param("idssss", $user_id, $grand_total, $shipping_address, $recipient_name, $recipient_phone, $payment_method);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi tạo đơn hàng chính.");
            }
            
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // 2. TẠO CHI TIẾT ĐƠN HÀNG (order_items)
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($item_sql);
            
            foreach ($cart as $product_id => $item) {
                $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                if (!$stmt->execute()) {
                    throw new Exception("Lỗi tạo chi tiết đơn hàng cho sản phẩm ID: $product_id");
                }
                // (Tùy chọn: Thêm logic giảm số lượng trong bảng inventory/products ở đây)
            }
            $stmt->close();
            
            // 3. HOÀN TẤT GIAO DỊCH VÀ CHUYỂN HƯỚNG
            $conn->commit();
            unset($_SESSION['cart']); // Xóa giỏ hàng
            $_SESSION['order_success'] = "Đặt hàng thành công! Mã đơn hàng: $order_id";
            header("Location: order_success.php?id=$order_id");
            exit();

        } catch (Exception $e) {
            // Nếu có lỗi, hoàn tác database
            $conn->rollback();
            $errors[] = "Quá trình thanh toán thất bại. Vui lòng thử lại. Chi tiết lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh Toán - VLXD KAT</title>
    </head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8 max-w-7xl">
        <h1 class="text-3xl font-bold mb-8 text-gray-800">Xác Nhận Thanh Toán Đơn Hàng</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php foreach($errors as $err) echo "<p>⚠️ $err</p>"; ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap -mx-4">
            <div class="w-full lg:w-2/3 px-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-6 border-b pb-2">1. Thông tin Giao hàng</h2>
                    <form method="POST">
                        
                        <div class="mb-4">
                            <label for="recipient_name" class="block text-sm font-medium text-gray-700">Tên người nhận</label>
                            <input type="text" name="recipient_name" required 
                                value="<?= htmlspecialchars($_POST['recipient_name'] ?? $user_info['full_name'] ?? '') ?>" 
                                class="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm">
                        </div>
                        
                        <div class="mb-4">
                            <label for="recipient_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input type="tel" name="recipient_phone" required 
                                value="<?= htmlspecialchars($_POST['recipient_phone'] ?? $user_info['phone'] ?? '') ?>" 
                                class="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm">
                        </div>
                        
                        <div class="mb-6">
                            <label for="shipping_address" class="block text-sm font-medium text-gray-700">Địa chỉ giao hàng</label>
                            <textarea name="shipping_address" rows="3" required 
                                class="mt-1 block w-full border border-gray-300 p-2 rounded-md shadow-sm"><?= htmlspecialchars($_POST['shipping_address'] ?? $user_info['address'] ?? '') ?></textarea>
                        </div>
                        
                        <h2 class="text-xl font-semibold mb-6 border-b pb-2">2. Phương thức Thanh toán</h2>
                        
                        <div class="space-y-4">
                            <label class="flex items-center p-3 border rounded-lg bg-indigo-50">
                                <input type="radio" name="payment_method" value="COD" checked class="form-radio text-indigo-600">
                                <span class="ml-3 font-medium">Thanh toán khi nhận hàng (COD)</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg">
                                <input type="radio" name="payment_method" value="BANK" class="form-radio text-indigo-600">
                                <span class="ml-3 font-medium">Chuyển khoản Ngân hàng (Thanh toán trước)</span>
                            </label>
                        </div>

                        <button type="submit" class="mt-10 w-full bg-purple-600 text-white py-3 rounded-lg font-bold text-lg hover:bg-purple-700 transition duration-150">
                            HOÀN TẤT ĐẶT HÀNG (<?= number_format($grand_total) ?> VNĐ)
                        </button>
                    </form>
                </div>
            </div>

            <div class="w-full lg:w-1/3 px-4">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-6">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">3. Tóm tắt Đơn hàng</h2>
                    
                    <div class="mb-4 max-h-60 overflow-y-auto border-b pb-4">
                        <?php foreach ($cart as $item): ?>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-600 truncate pr-2"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="font-medium text-right whitespace-nowrap">
                                <?= $item['quantity'] ?> x <?= number_format($item['price']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="space-y-2 pt-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span><?= number_format($total_amount) ?> VNĐ</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span><?= number_format($shipping_fee) ?> VNĐ</span>
                        </div>
                        <div class="flex justify-between font-bold text-xl pt-4 border-t mt-4">
                            <span>TỔNG CỘNG:</span>
                            <span class="text-purple-600"><?= number_format($grand_total) ?> VNĐ</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
