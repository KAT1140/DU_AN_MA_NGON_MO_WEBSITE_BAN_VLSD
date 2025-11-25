<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VLXD KAT - Vật Liệu Xây Dựng</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .toast {
      position: fixed;
      top: 100px;
      right: 20px;
      padding: 16px 24px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      animation: slideIn 0.3s ease-out;
    }
    .toast.success {
      border-left: 4px solid #10b981;
      color: #10b981;
    }
    .toast.error {
      border-left: 4px solid #ef4444;
      color: #ef4444;
    }
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header -->
  <header class="bg-gradient-to-r from-orange-600 to-orange-500 text-white sticky top-0 z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
      <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
        <h1 class="text-3xl font-black">VLXD</h1>
      </a>
      <div class="flex items-center gap-8">
        <div class="flex items-center gap-3">
          <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="flex items-center gap-3">
              <a href="profile.php" class="text-white font-bold hover:text-orange-200 transition text-lg">
                👤 <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?>
              </a>
              <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-full font-bold hover:bg-red-700 transition">
                Đăng xuất
              </a>
            </div>
          <?php else: ?>
            <a href="login.php" class="bg-white text-orange-600 px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition">
              Đăng nhập
            </a>
            <a href="dangki.php" class="border-2 border-white text-white px-6 py-3 rounded-full font-bold hover:bg-orange-400 transition">
              Đăng ký
            </a>
          <?php endif; ?>
        </div>
        <a href="cart.php" class="relative">
          <span class="text-3xl">🛒</span>
          <?php
          $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '" . $conn->real_escape_string($cart_session) . "'");
          $row = $res ? $res->fetch_assoc() : null;
          $count = $row['total_qty'] ?? 0;

          if ($count > 0) {
            echo "<span class='absolute -top-2 -right-2 bg-white text-orange-600 w-8 h-8 rounded-full flex items-center justify-center font-bold'>{$count}</span>";
          }
          ?>
        </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Nội dung -->
  <div class="max-w-7xl mx-auto p-10">
    <h1 class="text-5xl font-black text-center text-orange-600 mb-10">SẢN PHẨM NỔI BẬT</h1>

    <?php
    // Lấy danh sách danh mục
    $cats = $conn->query("SELECT * FROM categories");

    echo "<form method='GET' class='mb-6'>";
    echo "<select name='cat' class='p-3 rounded-xl'>";
    echo "<option value=''>-- Tất cả danh mục --</option>";
    while ($c = $cats->fetch_assoc()) {
        $selected = ($_GET['cat'] ?? '') == $c['id'] ? 'selected' : '';
        $catName = isset($c['name']) ? $c['name'] : $c['NAME'];
        echo "<option value='{$c['id']}' $selected>{$catName}</option>";
    }
    echo "</select> 
          <button type='submit' class='ml-2 px-4 py-2 bg-orange-600 text-white rounded-xl'>
            Lọc
          </button>";
    echo "</form>";
    ?>

    <div class="grid md:grid-cols-4 gap-8">
      <?php
      $cat_id = $_GET['cat'] ?? '';
      $sql = "SELECT * FROM products WHERE is_featured = 1";
      if (!empty($cat_id)) {
          $cat_id = (int)$cat_id;
          $sql .= " AND category_id = $cat_id";
      }
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while ($p = $result->fetch_assoc()) {
              echo "<div class='bg-white rounded-2xl shadow-2xl p-6 text-center hover:shadow-3xl transition'>
                      <div class='bg-gray-200 h-48 rounded-xl mb-6'></div>
                      <h3 class='font-bold text-xl mb-2'>{$p['NAME']}</h3>
                      <p class='text-3xl font-black text-orange-600 mb-6'>" . number_format($p['price']) . "đ</p>
                      <form action='add_to_cart.php' method='POST'>
                        <input type='hidden' name='product_id' value='{$p['id']}'>
                        <input type='hidden' name='quantity' value='1'>
                        <button type='submit' class='w-full bg-orange-600 text-white py-4 rounded-xl font-bold hover:bg-orange-700 text-xl'>
                          Thêm vào giỏ
                        </button>
                      </form>
                    </div>";
          }
      } else {
          echo "<p class='col-span-4 text-center text-gray-600 text-xl'>Không có sản phẩm nổi bật trong danh mục này.</p>";
      }
      ?>
    </div>
  </div>

  <script>
    function showToast(message, isSuccess) {
      const toast = document.createElement('div');
      toast.className = `toast ${isSuccess ? 'success' : 'error'}`;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    document.querySelectorAll('form[action="add_to_cart.php"]').forEach(form => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        try {
          const response = await fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          showToast(data.message, data.success);
        } catch (error) {
          showToast('❌ Lỗi: ' + error.message, false);
        }
      });
    });
  </script>

  <style>
    @keyframes slideOut {
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
  </style>
</body>
</html>
