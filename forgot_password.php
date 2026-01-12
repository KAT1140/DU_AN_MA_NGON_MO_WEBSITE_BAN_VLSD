<?php
require 'config.php';
require 'email_config.php';
header('Content-Type: text/html; charset=utf-8');

// Tự động tạo bảng password_resets nếu chưa có
$create_table_sql = "CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($create_table_sql);

$message = '';
$error = '';

// Kiểm tra cấu hình email
$config_errors = checkEmailConfig();
if (!empty($config_errors)) {
    $error = 'Cấu hình email chưa đầy đủ: ' . implode(', ', $config_errors) . '. Vui lòng cập nhật file email_config.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email của bạn.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Kiểm tra email có tồn tại trong hệ thống không
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Tạo token reset
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token hết hạn sau 1 giờ
            
            // Lưu token vào database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires_at);
            
            if ($stmt->execute()) {
                // Gửi email sử dụng PHPMailer
                if (sendResetEmail($email, $user['full_name'], $token)) {
                    $message = 'Email đặt lại mật khẩu đã được gửi thành công đến <strong>' . htmlspecialchars($email) . '</strong>. 
                    <br><br>Vui lòng kiểm tra hộp thư (bao gồm cả thư mục spam) và làm theo hướng dẫn trong email.
                    <br><br><small class="text-gray-600">Link reset sẽ hết hạn sau 1 giờ.</small>';
                } else {
                    $error = 'Có lỗi xảy ra khi gửi email. Vui lòng kiểm tra cấu hình email hoặc thử lại sau.';
                }
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        } else {
            // Không tìm thấy email, nhưng vẫn hiển thị thông báo thành công để bảo mật
            $message = 'Nếu email này tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu đến email của bạn.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-500 to-blue-500 text-white p-8 text-center">
            <div class="mb-4">
                <i class="fas fa-key text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold">Quên mật khẩu</h1>
            <p class="text-purple-100 mt-2">Nhập email để đặt lại mật khẩu</p>
        </div>

        <!-- Form -->
        <div class="p-8">
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2 text-green-700">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 text-red-700">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                               placeholder="Nhập email của bạn"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-lg font-bold hover:from-purple-600 hover:to-blue-600 transition transform hover:scale-105 shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Gửi link đặt lại mật khẩu
                    </button>
                </form>
            <?php endif; ?>

            <!-- Back to login -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-purple-600 hover:text-purple-700 font-semibold flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</body>
</html>
