<?php 
require 'config.php'; 
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VLXD KAT - Vật Liệu Xây Dựng</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      to { transform: translateX(400px); opacity: 0; }
    }
    /* Hiệu ứng nảy số khi thêm giỏ hàng */
    .bounce { animation: bounce 0.3s; }
    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.3); }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-gradient-to-r from-orange-600 to-orange-500 text-white sticky top-0 z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
      <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
        <h1 class="text-3xl font-black">VLXD KAT</h1>
      </a>
      <div class="flex items-center gap-8">
        <nav class="flex items-center gap-6">
          <a href="products.php" class="text-white font-bold hover:text-orange-200 transition text-lg flex items-center gap-2">
            <i class="fas fa-box"></i> Sản phẩm
          </a>
        </nav>
        
        <div class="flex items-center gap-3">
          <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="flex items-center gap-3">
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php" class="bg-yellow-400 text-orange-900 px-4 py-2 rounded-full font-bold hover:bg-yellow-300 transition flex items-center gap-2 shadow-lg text-sm">
                  <i class="fas fa-user-shield"></i> Quản trị
                </a>
              <?php endif; ?>

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

        <a href="cart.php" class="relative group">
          <span class="text-3xl group-hover:scale-110 transition inline-block">🛒</span>
          <?php
          $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '" . $conn->real_escape_string($cart_session) . "'");
          $row = $res ? $res->fetch_assoc() : null;
          $count = $row['total_qty'] ?? 0;

          // Logic: Luôn tạo thẻ span (có id="cart-count"), nếu count=0 thì ẩn đi (class hidden)
          $hiddenClass = ($count > 0) ? '' : 'hidden';
          echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-orange-600 w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-md $hiddenClass'>{$count}</span>";
          ?>
        </a>
        </a>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto p-10">
    <h1 class="text-5xl font-black text-center text-orange-600 mb-10">SẢN PHẨM NỔI BẬT</h1>

    <?php
    $cats = $conn->query("SELECT * FROM categories");
    echo "<form method='GET' class='mb-6 flex justify-center'>";
    echo "<div class='bg-white p-2 rounded-xl shadow-md flex gap-2'>";
    echo "<select name='cat' class='p-3 rounded-lg outline-none cursor-pointer bg-transparent'>";
    echo "<option value=''>-- Tất cả danh mục --</option>";
    while ($c = $cats->fetch_assoc()) {
        $selected = ($_GET['cat'] ?? '') == $c['id'] ? 'selected' : '';
        $catName = isset($c['name']) ? $c['name'] : $c['NAME'];
        echo "<option value='{$c['id']}' $selected>{$catName}</option>";
    }
    echo "</select>";
    echo "<button type='submit' class='px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-bold transition'>";
    echo "Lọc ngay";
    echo "</button>";
    echo "</div>";
    echo "</form>";
    ?>

    <div class="grid md:grid-cols-4 gap-8">
      <?php
      $cat_id = $_GET['cat'] ?? '';

      // --- LOGIC: Sản phẩm bán chạy nhất ---
      $sql = "SELECT p.id, p.NAME, p.price, p.sale_price, p.images, 
                     COALESCE(SUM(oi.quantity), 0) as total_sold
              FROM products p
              LEFT JOIN order_items oi ON p.id = oi.product_id
              LEFT JOIN orders o ON oi.order_id = o.id
              WHERE p.STATUS = 'active'";

      if (!empty($cat_id)) {
          $cat_id = (int)$cat_id;
          $sql .= " AND p.category_id = $cat_id";
      }

      // Chỉ tính đơn hàng chưa hủy (nếu muốn)
      $sql .= " AND (o.order_status IS NULL OR o.order_status != 'cancelled')";

      $sql .= " GROUP BY p.id";
      $sql .= " ORDER BY total_sold DESC, p.created_at DESC LIMIT 8";

      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while ($p = $result->fetch_assoc()) {
              $images = json_decode($p['images'], true);
              $image_url = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
              $display_price = $p['sale_price'] ?? $p['price'];
              
              echo "<div class='bg-white rounded-2xl shadow-2xl p-6 text-center hover:shadow-3xl transition flex flex-col h-full transform hover:-translate-y-2 duration-300'>
                      <div class='flex-grow'>
                        <div class='relative group-hover:scale-105 transition duration-300'>
                            <img src='" . htmlspecialchars($image_url) . "' alt='" . htmlspecialchars($p['NAME']) . "' class='bg-gray-200 h-48 w-full object-cover rounded-xl mb-6'>
                            " . ($p['total_sold'] > 0 ? "<span class='absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm'>Đã bán: {$p['total_sold']}</span>" : "") . "
                        </div>
                        <h3 class='font-bold text-xl mb-2 line-clamp-2 min-h-[3.5rem]'>" . htmlspecialchars($p['NAME']) . "</h3>
                        <p class='text-3xl font-black text-orange-600 mb-2'>" . number_format($display_price, 0, ',', '.') . "đ</p>
                      </div>
                      
                      <form action='add_to_cart.php' method='POST' class='mt-auto add-to-cart-form'>
                        <input type='hidden' name='product_id' value='{$p['id']}'>
                        <input type='hidden' name='quantity' value='1'>
                        <button type='submit' class='w-full bg-orange-600 text-white py-4 rounded-xl font-bold hover:bg-orange-700 text-xl shadow-lg transform active:scale-95 transition'>
                          Thêm vào giỏ
                        </button>
                      </form>
                    </div>";
          }
      } else {
          echo "<div class='col-span-4 text-center py-16'>
                  <i class='fas fa-box-open text-6xl text-gray-300 mb-4'></i>
                  <p class='text-gray-500 text-xl'>Không tìm thấy sản phẩm nào.</p>
                </div>";
      }
      ?>
    </div>
  </div>
<script src="assets/js/main.js"></script>
</body>
</html>