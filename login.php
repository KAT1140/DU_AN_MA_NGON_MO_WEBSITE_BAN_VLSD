<?php
require 'config.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập - VLXD KAT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen flex items-center justify-center p-4">
  <!-- Container chính -->
  <div class="w-full max-w-md">
    <!-- Card chính -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
      <!-- Header gradient -->
      <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-8 py-12 text-center">
        <a href="index.php" class="inline-block hover:opacity-90 transition">
          <img src="uploads/logo.png" alt="VLXD Logo" class="w-20 h-20 rounded-full mx-auto mb-4 shadow-lg object-cover">
        </a>
        <h1 class="text-4xl font-black text-white">VLXD KAT</h1>
        <p class="text-orange-100 mt-2 font-semibold">Đăng nhập để tiếp tục mua hàng</p>
      </div>

      <!-- Content -->
      <div class="p-8">
        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
          <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
            <p class="font-semibold">⚠️ <?= htmlspecialchars($_SESSION['error']) ?></p>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Google Sign In -->
        <div class="mb-6">
          <div 
            id="g_id_onload"
            data-client_id="<?= $CLIENT_ID ?>"
            data-login_uri="<?= $REDIRECT_URI ?>"
            data-auto_prompt="false">
          </div>
          <div class="g_id_signin"
               data-type="standard"
               data-size="large"
               data-theme="outline"
               data-text="signin_with"
               data-shape="pill"
               data-logo_alignment="left"
               style="display: flex; justify-content: center;">
          </div>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 my-8">
          <div class="flex-1 h-px bg-gray-300"></div>
          <span class="text-gray-500 font-semibold text-sm">HOẶC</span>
          <div class="flex-1 h-px bg-gray-300"></div>
        </div>

        <!-- Form Đăng Nhập -->
        <form action="check.php" method="POST" class="space-y-4">
          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">📧 Email</label>
            <input 
              type="email" 
              name="email" 
              placeholder="example@gmail.com" 
              required 
              class="w-full px-5 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
            >
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">🔐 Mật khẩu</label>
            <input 
              type="password" 
              name="pass" 
              placeholder="Nhập mật khẩu của bạn" 
              required 
              class="w-full px-5 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
            >
          </div>

          <!-- Remember & Forgot Password -->
          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="remember" class="w-4 h-4 rounded">
              <span class="text-gray-600">Ghi nhớ tôi</span>
            </label>
            <a href="#" class="text-orange-600 hover:text-orange-700 font-semibold">Quên mật khẩu?</a>
          </div>

          <!-- Submit Button -->
          <button 
            type="submit" 
            class="w-full bg-gradient-to-r from-orange-600 to-orange-500 text-white py-3 rounded-xl font-bold hover:shadow-lg transition mt-6 text-lg"
          >
            🔓 Đăng Nhập
          </button>
        </form>

        <!-- Sign Up Link -->
        <div class="text-center mt-6">
          <p class="text-gray-600">
            Chưa có tài khoản? 
            <a href="dangki.php" class="text-orange-600 font-bold hover:text-orange-700 transition">Đăng ký ngay</a>
          </p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-6">
      <a href="index.php" class="text-gray-600 hover:text-orange-600 font-semibold transition">
        ← Quay lại trang chủ
      </a>
    </div>
  </div>
</body>
</html>