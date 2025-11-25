<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($email) || empty($password) || empty($password_confirm) || empty($fullname)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $password_confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        // Kiểm tra email đã tồn tại
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
        
        if ($check_email->num_rows > 0) {
            $error = 'Email này đã được đăng ký!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
                // Insert user - lưu role = 'user' mặc định
                $sql = "INSERT INTO users (email, PASSWORD, full_name, phone, role) 
                  VALUES ('$email', '$hashed_password', '$fullname', '$phone', 'user')";
            
            if ($conn->query($sql)) {
                $success = 'Đăng kí thành công! Hãy đăng nhập để tiếp tục.';
                $_SESSION['register_success'] = true;
                // Clear form
                $email = $password = $password_confirm = $fullname = $phone = '';
            } else {
                $error = 'Lỗi khi đăng kí: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Kí - VLXD KAT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gradient-to-br from-orange-500 to-orange-700 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl p-10 max-w-md w-full">
    <!-- Header -->
    <div class="text-center mb-8">
      <a href="index.php" class="inline-block hover:opacity-90 transition mb-3">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-20 h-20 rounded-full mx-auto object-cover">
      </a>
      <h1 class="text-3xl font-black">VLXD KAT</h1>
      <p class="text-gray-600">Tạo tài khoản mới</p>
    </div>

    <!-- Error/Success Messages -->
    <?php if ($error): ?>
      <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
        ✅ <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- Form Đăng Kí -->
    <form method="POST" class="space-y-4">
      <!-- Google Sign Up Button -->
    <div class="mb-6">
      <div 
        id="g_id_onload"
        data-client_id="<?= $CLIENT_ID ?>"
        data-callback="handleCredentialResponse"
        data-auto_prompt="false">
      </div>
      <div 
        class="g_id_signin" 
        data-type="signup" 
        data-size="large"
        data-theme="outline"
        data-text="signup_with"
        data-shape="pill"
        data-logo_alignment="left">
      </div>
    </div>

    <div class="my-6 flex items-center gap-4">
      <div class="flex-1 h-px bg-gray-300"></div>
      <span class="text-gray-500 text-xs font-semibold">HOẶC</span>
      <div class="flex-1 h-px bg-gray-300"></div>
    </div>

    <!-- Họ tên -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Họ tên</label>
        <input 
          type="text" 
          name="fullname" 
          placeholder="Ngô Huy Hùng" 
          value="<?= htmlspecialchars($fullname ?? '') ?>"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        >
      </div>

      <!-- Email -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
        <input 
          type="email" 
          name="email" 
          placeholder="example@gmail.com" 
          value="<?= htmlspecialchars($email ?? '') ?>"
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        >
      </div>

      <!-- Phone -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại</label>
        <input 
          type="tel" 
          name="phone" 
          placeholder="0912345678" 
          value="<?= htmlspecialchars($phone ?? '') ?>"
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        >
      </div>

      <!-- Mật khẩu -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu</label>
        <input 
          type="password" 
          name="password" 
          placeholder="Ít nhất 6 ký tự" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        >
      </div>

      <!-- Xác nhận mật khẩu -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Xác nhận mật khẩu</label>
        <input 
          type="password" 
          name="password_confirm" 
          placeholder="Nhập lại mật khẩu" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        >
      </div>

      <!-- Nút Đăng Kí -->
      <button 
        type="submit" 
        class="w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition duration-200 mt-6"
      >
        Đăng Kí
      </button>
    </form>

    <!-- Link đến trang đăng nhập -->
    <div class="text-center mt-6">
      <p class="text-gray-600 text-sm">
        Đã có tài khoản? 
        <a href="login.php" class="text-orange-600 font-bold hover:underline">Đăng nhập tại đây</a>
      </p>
    </div>

    <!-- Quick Links -->
    <div class="space-y-2 mt-4">
      <a href="index.php" class="block w-full text-center py-2 text-gray-600 hover:text-orange-600 font-semibold transition">
        ← Quay lại trang chủ
      </a>
    </div>
  </div>

  <script>
    function handleCredentialResponse(response) {
      // Send JWT token to server
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'callback.php';
      
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'credential';
      input.value = response.credential;
      
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    }

    window.onload = function () {
      google.accounts.id.initialize({
        client_id: '<?= $CLIENT_ID ?>'
      });
    }
  </script>
</body>
</html>
