<?php
require 'config.php'; // Đảm bảo config.php đã được include để lấy kết nối DB và CLIENT_ID

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation cơ bản
    if (empty($email) || empty($password) || empty($password_confirm) || empty($fullname)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $password_confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        // [QUAN TRỌNG] Logic xử lý đăng ký an toàn
        
        // 1. Kiểm tra email đã tồn tại chưa (Dùng Prepared Statement)
        $check_sql = "SELECT id FROM users WHERE email = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = 'Email này đã được đăng ký!';
            } else {
                // 2. Mã hóa mật khẩu (Khắc phục lỗi đăng nhập báo sai pass)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 3. Insert vào database
                // Lưu ý: Cột username được gán bằng email để tránh lỗi duplicate entry
                $insert_sql = "INSERT INTO users (username, email, PASSWORD, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())";
                
                if ($insert_stmt = $conn->prepare($insert_sql)) {
                    $insert_stmt->bind_param("sssss", $email, $email, $hashed_password, $fullname, $phone);
                    
                    if ($insert_stmt->execute()) {
                        $success = 'Đăng ký thành công! Đang chuyển hướng đến trang đăng nhập...';
                        // Tự động chuyển hướng sau 2 giây
                        echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                    } else {
                        $error = 'Lỗi hệ thống: ' . $conn->error;
                    }
                    $insert_stmt->close();
                } else {
                    $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $error = 'Lỗi kết nối cơ sở dữ liệu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Ký - VLXD KAT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gradient-to-br from-orange-500 to-orange-700 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl p-10 max-w-md w-full">
    
    <div class="text-center mb-8"> 
      <a href="index.php" class="inline-block hover:opacity-90 transition mb-3">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-20 h-20 rounded-full mx-auto object-cover border-4 border-orange-100">
      </a>
      <h1 class="text-3xl font-black text-gray-800">VLXD KAT</h1>
      <p class="text-gray-500 mt-1">Tạo tài khoản thành viên mới</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg text-sm flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-lg text-sm flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <div class="mb-6">
        <div id="g_id_onload"
             data-client_id="<?= $CLIENT_ID ?>"
             data-context="signup"
             data-ux_mode="popup"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
             data-type="standard"
             data-shape="pill"
             data-theme="outline"
             data-text="signup_with"
             data-size="large"
             data-logo_alignment="left"
             data-width="100%">
        </div>
    </div>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Hoặc đăng ký bằng email</span>
        </div>
    </div>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên</label>
        <input 
          type="text" 
          name="fullname" 
          placeholder="Ví dụ: Nguyễn Văn A" 
          value="<?= htmlspecialchars($fullname ?? '') ?>"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
        >
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
        <input 
          type="email" 
          name="email" 
          placeholder="email@example.com" 
          value="<?= htmlspecialchars($email ?? '') ?>"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
        >
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
        <input 
          type="tel" 
          name="phone" 
          placeholder="09xx xxx xxx" 
          value="<?= htmlspecialchars($phone ?? '') ?>"
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
        >
      </div>

      <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu</label>
            <input 
              type="password" 
              name="password" 
              placeholder="Min 6 ký tự" 
              required 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
            >
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nhập lại</label>
            <input 
              type="password" 
              name="password_confirm" 
              placeholder="Xác nhận" 
              required 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
            >
          </div>
      </div>

      <button 
        type="submit" 
        class="w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 hover:shadow-lg transition duration-200 mt-6"
      >
        ĐĂNG KÝ NGAY
      </button>
    </form>

    <div class="text-center mt-6 space-y-3">
      <p class="text-gray-600 text-sm">
        Đã có tài khoản? 
        <a href="login.php" class="text-orange-600 font-bold hover:underline">Đăng nhập ngay</a>
      </p>
      
      <a href="index.php" class="inline-flex items-center text-sm text-gray-500 hover:text-orange-600 font-medium transition">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Quay lại trang chủ
      </a>
    </div>
  </div>

  <script>
    function handleCredentialResponse(response) {
      // Tạo form ẩn để gửi token về server (callback.php)
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'callback.php'; // Đảm bảo file callback.php tồn tại và xử lý đúng
      
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'credential';
      input.value = response.credential;
      
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    }
  </script>
</body>
</html>