<?php
require 'config.php';
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

$token = $_GET['token'] ?? '';
$message = '';
$error = '';
$valid_token = false;
$user_email = '';

// Kiểm tra token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_email = $result->fetch_assoc()['email'];
    } else {
        $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    }
}

// Xử lý form reset mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $error = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Cập nhật mật khẩu
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $user_email);
        
        if ($stmt->execute()) {
            // Đánh dấu token đã sử dụng
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.';
        } else {
            $error = 'Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-500 to-blue-500 text-white p-8 text-center">
            <div class="mb-4">
                <i class="fas fa-lock text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold">Đặt lại mật khẩu</h1>
            <p class="text-purple-100 mt-2">Tạo mật khẩu mới cho tài khoản</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2 text-green-700 mb-3">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <a href="login.php" class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-sign-in-alt"></i>
                        Đăng nhập ngay
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 text-red-700 mb-3">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php if (!$valid_token): ?>
                        <a href="forgot_password.php" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-redo"></i>
                            Yêu cầu link mới
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token && !$message): ?>
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-700">
                        <i class="fas fa-info-circle"></i>
                        <span>Đặt lại mật khẩu cho: <strong><?= htmlspecialchars($user_email) ?></strong></span>
                    </div>
                </div>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Mật khẩu mới
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                               placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)"
                               minlength="6">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Xác nhận mật khẩu
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                               placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-lg font-bold hover:from-purple-600 hover:to-blue-600 transition transform hover:scale-105 shadow-lg">
                        <i class="fas fa-save mr-2"></i>Đặt lại mật khẩu
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

    <script>
        // Kiểm tra mật khẩu khớp
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Mật khẩu xác nhận không khớp');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
