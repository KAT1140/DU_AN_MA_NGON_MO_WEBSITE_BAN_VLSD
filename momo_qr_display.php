<?php
session_start();
require_once 'config.php';
require_once 'momo_personal_config.php';

// Kiểm tra user đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lấy thông tin đơn hàng từ URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$requestId = isset($_GET['request_id']) ? $_GET['request_id'] : '';

if (!$orderId) {
    $_SESSION['error'] = "Không tìm thấy đơn hàng";
    header('Location: cart.php');
    exit();
}

// Lấy thông tin đơn hàng từ database
$order_sql = "SELECT o.*, u.full_name 
              FROM orders o 
              LEFT JOIN users u ON u.id = o.user_id 
              WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param('ii', $orderId, $_SESSION['user_id']);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['error'] = "Không tìm thấy đơn hàng";
    header('Location: cart.php');
    exit();
}

$order = $order_result->fetch_assoc();
$amount = $order['total_amount'];
$orderCode = $order['order_code'];

// Tạo QR code MoMo cá nhân
$qrCodeImage = generateMoMoPersonalQR($amount, $orderCode);
$momoPhone = MOMO_PERSONAL_PHONE;
$momoName = MOMO_PERSONAL_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo - VLXD Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .momo-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        h2 {
            color: #a50064;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .order-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .order-info p {
            margin: 10px 0;
            font-size: 16px;
            color: #333;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #a50064;
            margin: 10px 0;
        }
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 3px solid #a50064;
        }
        .qr-code {
            max-width: 250px;
            width: 100%;
            height: auto;
            margin: 0 auto;
        }
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }
        .instructions li {
            margin: 8px 0;
        }
        .deeplink-button {
            background: #a50064;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
            transition: background 0.3s;
        }
        .deeplink-button:hover {
            background: #7d004b;
        }
        .cancel-button {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: background 0.3s;
        }
        .cancel-button:hover {
            background: #5a6268;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #a50064;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .timer {
            font-size: 18px;
            color: #dc3545;
            font-weight: bold;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <svg class="momo-logo" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="#a50064"/>
            <text x="50" y="65" font-size="40" fill="white" text-anchor="middle" font-weight="bold">M</text>
        </svg>
        
        <h2>Thanh toán MoMo</h2>
        <p style="color: #666; margin-bottom: 20px;">Quét mã QR để thanh toán</p>
        
        <div class="order-info">
            <p><strong>Mã đơn hàng:</strong> #<?php echo htmlspecialchars($orderCode); ?></p>
            <div class="amount"><?php echo number_format($amount); ?> VNĐ</div>
            <div class="timer" id="timer">Thời gian: <span id="time">15:00</span></div>
        </div>
        
        <div class="qr-container">
            <img src="<?php echo htmlspecialchars($qrCodeImage); ?>" 
                 alt="QR Code MoMo" 
                 class="qr-code"
                 onerror="this.style.display='none'; document.getElementById('qr-error').style.display='block';">
            <div id="qr-error" style="display: none; color: #dc3545;">
                Không thể tải mã QR. Vui lòng chuyển khoản thủ công theo thông tin bên dưới.
            </div>
        </div>
        
        <div class="instructions">
            <h4>📱 Hướng dẫn thanh toán:</h4>
            <ol>
                <li>Mở ứng dụng MoMo trên điện thoại</li>
                <li>Chọn "Quét mã QR" và quét mã QR phía trên</li>
                <li>Hoặc chọn "Chuyển tiền" và nhập thông tin bên dưới</li>
                <li>Xác nhận thanh toán</li>
            </ol>
        </div>
        
        <div style="background: #fff9e6; border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin: 20px 0; text-align: left;">
            <h4 style="color: #856404; margin: 0 0 15px 0; text-align: center;">💳 Thông tin chuyển khoản MoMo</h4>
            
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin: 8px 0; color: #666; font-size: 14px;">Số điện thoại:</p>
                <p style="margin: 8px 0; font-size: 20px; color: #a50064; font-weight: bold;"><?php echo htmlspecialchars($momoPhone); ?></p>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin: 8px 0; color: #666; font-size: 14px;">Tên tài khoản:</p>
                <p style="margin: 8px 0; font-size: 18px; color: #333; font-weight: bold;"><?php echo htmlspecialchars($momoName); ?></p>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin: 8px 0; color: #666; font-size: 14px;">Số tiền:</p>
                <p style="margin: 8px 0; font-size: 24px; color: #a50064; font-weight: bold;"><?php echo number_format($amount); ?> VNĐ</p>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px;">
                <p style="margin: 8px 0; color: #666; font-size: 14px;">Nội dung chuyển khoản:</p>
                <p style="margin: 8px 0; font-size: 18px; color: #a50064; font-weight: bold; word-break: break-all;"><?php echo htmlspecialchars($orderCode); ?></p>
            </div>
            
            <p style="margin: 15px 0 5px 0; color: #856404; font-size: 14px; text-align: center;">
                ⚠️ Vui lòng nhập chính xác nội dung để đơn hàng được xử lý tự động
            </p>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="payment_status.php?order_id=<?php echo $orderId; ?>" class="cancel-button">
                Kiểm tra trạng thái
            </a>
            <a href="checkout.php" class="cancel-button">
                ← Quay lại
            </a>
        </div>
        
        <div class="loading" id="checking" style="display: none;"></div>
        <p id="status-message" style="margin-top: 15px; color: #666;"></p>
    </div>

    <script>
        // Đếm ngược thời gian
        let timeLeft = 900; // 15 phút
        const timerElement = document.getElementById('time');
        
        const countdown = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                clearInterval(statusCheck);
                document.getElementById('status-message').innerHTML = 
                    '<span style="color: #dc3545;">⏱️ Hết thời gian thanh toán</span>';
            }
        }, 1000);

        // Tự động kiểm tra trạng thái thanh toán
        const statusCheck = setInterval(() => {
            fetch('payment_status.php?order_id=<?php echo $orderId; ?>&ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(statusCheck);
                        clearInterval(countdown);
                        document.getElementById('status-message').innerHTML = 
                            '<span style="color: #28a745;">✅ Thanh toán thành công!</span>';
                        setTimeout(() => {
                            window.location.href = 'order_success.php?order_id=<?php echo $orderId; ?>';
                        }, 2000);
                    } else if (data.status === 'cancelled' || data.status === 'failed') {
                        clearInterval(statusCheck);
                        clearInterval(countdown);
                        document.getElementById('status-message').innerHTML = 
                            '<span style="color: #dc3545;">❌ Thanh toán thất bại</span>';
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
        }, 5000); // Kiểm tra mỗi 5 giây
    </script>
</body>
</html>
