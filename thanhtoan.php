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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-3 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-12 h-12 object-cover rounded-full">
                <h1 class="text-2xl font-bold">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-6">
                <nav class="flex items-center gap-4">
                    <a href="index.php" class="text-white hover:text-purple-200 transition">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="cart.php" class="text-white hover:text-purple-200 transition">
                        <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-credit-card"></i> Xác Nhận Thanh Toán
            </h1>
            <p class="text-gray-600 mt-2">Vui lòng kiểm tra thông tin và hoàn tất đơn hàng</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <?php foreach($errors as $err) echo "<p class='flex items-center gap-2'><i class='fas fa-exclamation-circle'></i> $err</p>"; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold mb-6 pb-3 border-b flex items-center gap-2">
                        <i class="fas fa-shipping-fast text-purple-600"></i>
                        1. Thông tin Giao hàng
                    </h2>
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
                        
                        <h2 class="text-xl font-bold mb-6 pb-3 border-b flex items-center gap-2">
                            <i class="fas fa-wallet text-purple-600"></i>
                            2. Phương thức Thanh toán
                        </h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition border-purple-500 bg-purple-50">
                                <input type="radio" name="payment_method" value="COD" checked class="w-5 h-5 text-purple-600">
                                <span class="ml-3 font-semibold text-gray-800">
                                    <i class="fas fa-money-bill-wave text-green-600"></i>
                                    Thanh toán khi nhận hàng (COD)
                                </span>
                            </label>
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="radio" name="payment_method" value="BANK" class="w-5 h-5 text-purple-600">
                                <span class="ml-3 font-semibold text-gray-800">
                                    <i class="fas fa-university text-blue-600"></i>
                                    Chuyển khoản Ngân hàng
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="mt-8 w-full bg-gradient-to-r from-purple-600 to-blue-500 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            HOÀN TẤT ĐẶT HÀNG (<?= number_format($grand_total) ?>₫)
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                    <h2 class="text-xl font-bold mb-6 pb-3 border-b flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-purple-600"></i>
                        3. Tóm tắt Đơn hàng
                    </h2>
                    
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
