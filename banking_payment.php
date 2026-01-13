<?php
require 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

// Lấy thông tin đơn hàng
$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin đơn hàng từ database
$order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Thông tin ngân hàng
// TODO: Cập nhật số tài khoản ngân hàng thực tế
$bank_info = [
    'bank_name' => 'Ngân hàng TMCP Quân Đội (MB Bank)',
    'account_number' => '1234567890',  // Cần thay bằng số tài khoản thực tế
    'account_name' => 'VO NHAT DUY NAM',
    'branch' => 'MB Bank'
];

// Tạo nội dung chuyển khoản
$transfer_content = "VLXD " . $order['order_code'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Chuyển Khoản - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
                <h1 class="text-3xl font-black">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-3">
                <a href="profile.php" class="text-white font-bold hover:text-purple-200 transition text-lg">
                    👤 <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?>
                </a>
                <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-full font-bold hover:bg-red-700 transition">
                    Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-12">
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
                    <span class="ml-2 font-semibold">Thanh toán</span>
                </div>
                <div class="w-24 h-1 bg-purple-600 mx-4"></div>
                <div class="flex items-center text-purple-600">
                    <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 font-semibold">Chuyển khoản</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-university text-3xl text-purple-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Thanh Toán Chuyển Khoản</h1>
                <p class="text-gray-600">Vui lòng chuyển khoản theo thông tin bên dưới</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Thông tin chuyển khoản -->
                <div class="space-y-6">
                    <div class="bg-purple-50 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-purple-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> Thông tin chuyển khoản
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-purple-200">
                                <span class="font-semibold text-gray-700">Ngân hàng:</span>
                                <span class="text-gray-800"><?= htmlspecialchars($bank_info['bank_name']) ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b border-purple-200">
                                <span class="font-semibold text-gray-700">Số tài khoản:</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-mono text-lg" id="account-number"><?= htmlspecialchars($bank_info['account_number']) ?></span>
                                    <button onclick="copyToClipboard('account-number')" class="text-purple-600 hover:text-purple-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b border-purple-200">
                                <span class="font-semibold text-gray-700">Tên tài khoản:</span>
                                <span class="text-gray-800"><?= htmlspecialchars($bank_info['account_name']) ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b border-purple-200">
                                <span class="font-semibold text-gray-700">Chi nhánh:</span>
                                <span class="text-gray-800"><?= htmlspecialchars($bank_info['branch']) ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b border-purple-200">
                                <span class="font-semibold text-gray-700">Số tiền:</span>
                                <span class="text-2xl font-bold text-purple-600"><?= number_format($order['total_amount']) ?>đ</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2">
                                <span class="font-semibold text-gray-700">Nội dung:</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-mono" id="transfer-content"><?= htmlspecialchars($transfer_content) ?></span>
                                    <button onclick="copyToClipboard('transfer-content')" class="text-purple-600 hover:text-purple-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Lưu ý quan trọng:</strong><br>
                                    • Vui lòng chuyển khoản đúng số tiền và nội dung<br>
                                    • Đơn hàng sẽ được xử lý sau khi nhận được thanh toán<br>
                                    • Liên hệ hotline nếu cần hỗ trợ: 1900 xxxx
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="text-center">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Quét mã QR để chuyển khoản</h3>
                        <div class="flex justify-center mb-4">
                            <?php
                            // Tạo QR code theo chuẩn VietQR
                            // Format: 00020101021238570010A00000072701270006970415011{STK}0208QRIBFTTA53037045802VN62{length}{content}6304{checksum}
                            
                            $bank_code = "970415"; // Mã ngân hàng VietinBank
                            $account_number = $bank_info['account_number'];
                            $amount = $order['total_amount'];
                            $content = $transfer_content;
                            
                            // Tạo VietQR URL với thông tin chuẩn
                            $vietqr_url = "https://img.vietqr.io/image/" . $bank_code . "-" . $account_number . "-compact2.png";
                            $vietqr_url .= "?amount=" . $amount;
                            $vietqr_url .= "&addInfo=" . urlencode($content);
                            $vietqr_url .= "&accountName=" . urlencode($bank_info['account_name']);
                            ?>
                            <img src="<?= $vietqr_url ?>" 
                                 alt="VietQR Code" 
                                 class="border-2 border-gray-200 rounded-lg shadow-sm"
                                 style="width: 200px; height: 200px; object-fit: contain;"
                                 onload="console.log('VietQR loaded successfully')"
                                 onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('STK: ' . $bank_info['account_number'] . ' - ' . $bank_info['account_name'] . ' - VietinBank - ' . number_format($order['total_amount']) . 'đ - ' . $transfer_content) ?>&bgcolor=FFFFFF&color=000000'; console.log('VietQR failed, using fallback');"
                            />
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Quét bằng app ngân hàng hoặc ví điện tử</p>
                        <div class="flex justify-center gap-2 mb-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-mobile-alt mr-1"></i> Banking App
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-qrcode mr-1"></i> VietQR
                            </span>
                        </div>
                        
                        <!-- Alternative: Manual QR -->
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <i class="fas fa-info-circle"></i> 
                                Nếu không quét được QR, vui lòng chuyển khoản thủ công theo thông tin bên trái
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Thông tin đơn hàng</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-semibold">Mã đơn hàng:</span> <?= htmlspecialchars($order['order_code']) ?>
                    </div>
                    <div>
                        <span class="font-semibold">Khách hàng:</span> <?= htmlspecialchars($order['customer_name']) ?>
                    </div>
                    <div>
                        <span class="font-semibold">Email:</span> <?= htmlspecialchars($order['customer_email']) ?>
                    </div>
                    <div>
                        <span class="font-semibold">Điện thoại:</span> <?= htmlspecialchars($order['customer_phone']) ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex justify-center gap-4">
                <a href="my_orders.php" class="bg-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-purple-700 transition">
                    <i class="fas fa-list"></i> Xem đơn hàng của tôi
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-700 transition">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        // Copy to clipboard function - simplified
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess(element);
                }).catch(function(err) {
                    console.log('Clipboard API failed, trying fallback');
                    fallbackCopyTextToClipboard(text, element);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(text, element);
            }
        }
        
        function fallbackCopyTextToClipboard(text, element) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(element);
                } else {
                    console.log('Copy command failed');
                }
            } catch (err) {
                console.error('Fallback copy failed: ', err);
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopySuccess(element) {
            const originalHTML = element.innerHTML;
            const originalColor = element.style.color;
            
            element.innerHTML = '<i class="fas fa-check text-green-600"></i> Đã copy!';
            element.style.color = '#10b981';
            
            setTimeout(() => {
                element.innerHTML = originalHTML;
                element.style.color = originalColor;
            }, 2000);
        }

        // Simple page load handler
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Banking payment page loaded successfully');
        });
    </script>
</body>
</html>