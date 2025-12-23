<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Thông tin ngân hàng
$bank_info = [
    'banking' => [
        'bank_name' => 'Vietcombank',
        'account_number' => '1234567890',
        'account_name' => 'CONG TY VLXD KAT',
        'branch' => 'Chi nhánh TP.HCM'
    ],
    'momo' => [
        'phone' => '0123456789',
        'name' => 'VLXD KAT'
    ]
];

$payment_method = $order['payment_method'];
$total = $order['total_amount'];
$order_code = $order['order_code'];

// Tạo URL QR code
// Banking: https://img.vietqr.io/image/BANK_ID-ACCOUNT_NUMBER-TEMPLATE.jpg?amount=AMOUNT&addInfo=ORDER_CODE
// MoMo: Sử dụng API QR của MoMo hoặc link thanh toán
if ($payment_method === 'banking') {
    // VietQR - API tạo mã QR thanh toán ngân hàng
    $bank_id = 'VCB'; // Vietcombank
    $account_no = $bank_info['banking']['account_number'];
    $template = 'compact2';
    $qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-{$template}.jpg?amount={$total}&addInfo={$order_code}&accountName=" . urlencode($bank_info['banking']['account_name']);
} else {
    // MoMo QR - Sử dụng link thanh toán MoMo
    $momo_phone = $bank_info['momo']['phone'];
    $qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode("2|99|{$momo_phone}|{$bank_info['momo']['name']}|{$order_code}|0|0|{$total}");
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
    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-orange-600 flex items-center gap-2">
                <i class="fas fa-hammer"></i> VLXD KAT
            </a>
            <div class="flex items-center gap-4">
                <span class="text-gray-700"><i class="fas fa-credit-card"></i> Thanh toán</span>
            </div>
        </div>
    </header>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <!-- Progress Steps -->
        <div class="flex justify-center items-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center text-green-600">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="ml-2 font-semibold">Giỏ hàng</span>
                </div>
                <div class="w-24 h-1 bg-green-600 mx-4"></div>
                <div class="flex items-center text-green-600">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="ml-2 font-semibold">Đặt hàng</span>
                </div>
                <div class="w-24 h-1 bg-orange-600 mx-4"></div>
                <div class="flex items-center text-orange-600">
                    <div class="w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 font-semibold">Thanh toán</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4 pulse-slow">
                    <i class="fas fa-clock text-3xl text-orange-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Đơn hàng đang chờ thanh toán</h1>
                <p class="text-gray-600">Mã đơn hàng: <span class="font-bold text-orange-600"><?= htmlspecialchars($order_code) ?></span></p>
            </div>

            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6">
                <p class="text-orange-800">
                    <i class="fas fa-info-circle"></i> Vui lòng quét mã QR hoặc chuyển khoản theo thông tin bên dưới để hoàn tất đơn hàng.
                </p>
            </div>

            <!-- QR Code -->
            <div class="text-center mb-6">
                <div class="inline-block p-4 bg-white border-4 border-orange-500 rounded-lg">
                    <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" class="w-64 h-64 mx-auto">
                </div>
                <p class="text-sm text-gray-500 mt-2">Quét mã QR để thanh toán</p>
            </div>

            <!-- Bank Info -->
            <?php if ($payment_method === 'banking'): ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-lg mb-4 text-gray-800">Thông tin chuyển khoản</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ngân hàng:</span>
                            <span class="font-semibold"><?= $bank_info['banking']['bank_name'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Số tài khoản:</span>
                            <span class="font-semibold font-mono"><?= $bank_info['banking']['account_number'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Chủ tài khoản:</span>
                            <span class="font-semibold"><?= $bank_info['banking']['account_name'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Chi nhánh:</span>
                            <span class="font-semibold"><?= $bank_info['banking']['branch'] ?></span>
                        </div>
                        <div class="border-t pt-3 flex justify-between">
                            <span class="text-gray-600">Số tiền:</span>
                            <span class="font-bold text-orange-600 text-xl"><?= number_format($total, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nội dung:</span>
                            <span class="font-semibold text-orange-600"><?= $order_code ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="font-bold text-lg mb-4 text-gray-800">Thông tin MoMo</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Số điện thoại:</span>
                            <span class="font-semibold font-mono"><?= $bank_info['momo']['phone'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tên tài khoản:</span>
                            <span class="font-semibold"><?= $bank_info['momo']['name'] ?></span>
                        </div>
                        <div class="border-t pt-3 flex justify-between">
                            <span class="text-gray-600">Số tiền:</span>
                            <span class="font-bold text-orange-600 text-xl"><?= number_format($total, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nội dung:</span>
                            <span class="font-semibold text-orange-600"><?= $order_code ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Lưu ý quan trọng:</strong>
                </p>
                <ul class="list-disc list-inside text-sm text-yellow-800 mt-2 space-y-1">
                    <li>Vui lòng chuyển khoản <strong>đúng số tiền</strong> và <strong>đúng nội dung</strong> như trên</li>
                    <li>Đơn hàng sẽ được xử lý sau khi chúng tôi nhận được thanh toán</li>
                    <li>Thời gian xác nhận: 5-15 phút (giờ hành chính)</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <form method="POST" action="confirm_payment.php" class="flex-1">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                        <i class="fas fa-check-circle"></i> Tôi đã thanh toán
                    </button>
                </form>
                <a href="my_orders.php" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-bold hover:bg-gray-300 transition text-center">
                    <i class="fas fa-list"></i> Xem đơn hàng
                </a>
            </div>

            <p class="text-center text-gray-500 text-sm mt-4">
                Bạn có thể thanh toán sau. Đơn hàng sẽ được lưu trong phần "Đơn hàng của tôi"
            </p>
        </div>
    </div>

    <script>
        // Tự động refresh sau 30 giây để kiểm tra trạng thái thanh toán
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
