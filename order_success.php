<?php
require 'config.php';

// Kiểm tra nếu có đơn hàng vừa tạo
if (!isset($_SESSION['last_order'])) {
    header('Location: index.php');
    exit();
}

$order = $_SESSION['last_order'];
unset($_SESSION['last_order']); // Xóa sau khi hiển thị

// Lấy chi tiết đơn hàng từ database
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$order_stmt->bind_param('i', $order['order_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order_details = $order_result->fetch_assoc();
$order_stmt->close();

// Lấy sản phẩm trong đơn hàng
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param('i', $order['order_id']);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}
$items_stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Hàng Thành Công - VLXD PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f97316;
            border-radius: 50%;
            animation: fall 5s linear infinite;
        }
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <!-- Confetti Effect -->
    <div id="confetti-container"></div>

    <div class="max-w-4xl mx-auto p-6">
        <!-- Success Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-500 px-8 py-12 text-center relative">
                <div class="absolute top-4 left-8">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-3xl text-white"></i>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="w-24 h-24 mx-auto bg-white rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-check-circle text-6xl text-green-600"></i>
                    </div>
                    <h1 class="text-4xl font-black text-white mb-2">ĐẶT HÀNG THÀNH CÔNG!</h1>
                    <p class="text-green-100 text-lg">Cảm ơn bạn đã mua hàng tại VLXD PRO</p>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Order Summary -->
                <div class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 p-6 rounded-xl">
                            <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fas fa-info-circle text-blue-600"></i> Thông tin đơn hàng
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Mã đơn hàng:</span>
                                    <span class="font-bold text-orange-600"><?= htmlspecialchars($order_details['order_code']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Ngày đặt:</span>
                                    <span class="font-bold"><?= date('d/m/Y H:i', strtotime($order_details['created_at'])) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Trạng thái:</span>
                                    <span class="font-bold text-green-600">
                                        <?php 
                                        if ($order_details['order_status'] == 'awaiting_payment') {
                                            echo '<span class="text-yellow-600">CHỜ THANH TOÁN</span>';
                                        } else {
                                            echo 'ĐÃ XÁC NHẬN';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Thanh toán:</span>
                                    <span class="font-bold">
                                        <?php 
                                        if ($order_details['payment_method'] == 'cod') {
                                            echo 'COD (Tiền mặt)';
                                        } elseif ($order_details['payment_method'] == 'banking') {
                                            echo 'Chuyển khoản';
                                        } else {
                                            echo 'MoMo';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-xl">
                            <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fas fa-user text-purple-600"></i> Thông tin khách hàng
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Họ tên:</span>
                                    <span class="font-bold"><?= htmlspecialchars($order_details['customer_name']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-bold"><?= htmlspecialchars($order_details['customer_email']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Điện thoại:</span>
                                    <span class="font-bold"><?= htmlspecialchars($order_details['customer_phone']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Địa chỉ:</span>
                                    <span class="font-bold text-right"><?= htmlspecialchars($order_details['customer_address']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-gray-50 p-6 rounded-xl mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-box text-orange-600"></i> Chi tiết đơn hàng
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-3 text-left">Sản phẩm</th>
                                        <th class="px-4 py-3 text-center">Đơn giá</th>
                                        <th class="px-4 py-3 text-center">Số lượng</th>
                                        <th class="px-4 py-3 text-right">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-3"><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td class="px-4 py-3 text-center"><?= number_format($item['product_price']) ?>đ</td>
                                        <td class="px-4 py-3 text-center"><?= $item['quantity'] ?></td>
                                        <td class="px-4 py-3 text-right font-bold"><?= number_format($item['total_price']) ?>đ</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-bold">Tạm tính:</td>
                                        <td class="px-4 py-3 text-right"><?= number_format($order_details['subtotal']) ?>đ</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-bold">Phí vận chuyển:</td>
                                        <td class="px-4 py-3 text-right"><?= number_format($order_details['shipping_fee']) ?>đ</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-bold text-lg">Tổng cộng:</td>
                                        <td class="px-4 py-3 text-right text-2xl font-black text-orange-600">
                                            <?= number_format($order_details['total_amount']) ?>đ
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <?php if ($order_details['payment_method'] == 'banking' || $order_details['payment_method'] == 'momo'): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-r-lg mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i> Xác nhận thanh toán
                    </h3>
                    <p class="text-gray-700 mb-2">
                        <i class="fas fa-info-circle text-blue-500"></i> 
                        Cảm ơn bạn đã xác nhận thanh toán! Đơn hàng của bạn đang được xử lý.
                    </p>
                    <p class="text-gray-600 text-sm">
                        Chúng tôi sẽ kiểm tra và xác nhận thanh toán của bạn trong thời gian sớm nhất (5-15 phút trong giờ hành chính).
                        Bạn sẽ nhận được thông báo qua email khi đơn hàng được xác nhận.
                    </p>
                </div>
                <?php elseif ($order_details['payment_method'] == 'cod'): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-green-600"></i> Thanh toán khi nhận hàng (COD)
                    </h3>
                    <p class="text-gray-700">
                        Bạn sẽ thanh toán bằng tiền mặt khi nhận hàng. Vui lòng chuẩn bị số tiền 
                        <span class="font-bold text-orange-600"><?= number_format($order_details['total_amount']) ?>đ</span>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Next Steps -->
                <div class="text-center mb-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Bước tiếp theo</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-xl shadow border">
                            <div class="w-12 h-12 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-sms text-green-600 text-xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Xác nhận đơn hàng</h4>
                            <p class="text-gray-600 text-sm">Chúng tôi sẽ gọi điện xác nhận đơn hàng trong vòng 30 phút</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow border">
                            <div class="w-12 h-12 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-truck text-blue-600 text-xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Giao hàng</h4>
                            <p class="text-gray-600 text-sm">Đơn hàng sẽ được giao trong 2-3 ngày làm việc</p>
                        </div>
                        <div class="bg-white p-4 rounded-xl shadow border">
                            <div class="w-12 h-12 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-headset text-orange-600 text-xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Hỗ trợ 24/7</h4>
                            <p class="text-gray-600 text-sm">Liên hệ hotline: 1900 1234 nếu cần hỗ trợ</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col md:flex-row gap-4 justify-center">
                    <a href="index.php" class="bg-gradient-to-r from-orange-600 to-orange-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-lg transition text-center">
                        <i class="fas fa-home"></i> TIẾP TỤC MUA SẮM
                    </a>
                    <a href="profile.php" class="bg-gradient-to-r from-blue-600 to-blue-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-lg transition text-center">
                        <i class="fas fa-history"></i> XEM LỊCH SỬ ĐƠN HÀNG
                    </a>
                    <button onclick="window.print()" class="bg-gradient-to-r from-gray-600 to-gray-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-lg transition">
                        <i class="fas fa-print"></i> IN ĐƠN HÀNG
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confetti effect
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.backgroundColor = [
                    '#f97316', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'
                ][Math.floor(Math.random() * 5)];
                container.appendChild(confetti);
                
                // Remove after animation
                setTimeout(() => confetti.remove(), 5000);
            }
        }
        
        // Create confetti on page load
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            setTimeout(createConfetti, 1000);
            setTimeout(createConfetti, 2000);
        });
    </script>
</body>
</html>