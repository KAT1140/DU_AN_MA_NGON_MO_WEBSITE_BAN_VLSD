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
    
    /* CSS cho grid layout đồng đều */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      align-items: stretch;
    }
    
    .product-card {
      display: flex;
      flex-direction: column;
      height: 100%;
      min-height: 450px;
    }
    
    .product-card .card-content {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    
    .product-card .card-actions {
      margin-top: auto;
      padding-top: 1rem;
    }
    
    @media (min-width: 768px) {
      .product-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
      <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
        <h1 class="text-3xl font-black">VLXD KAT</h1>
      </a>
      <div class="flex items-center gap-8">
        <nav class="flex items-center gap-6">
          <a href="products.php" class="text-white font-bold hover:text-purple-200 transition text-lg flex items-center gap-2">
            <i class="fas fa-box"></i> Sản phẩm
          </a>
        </nav>
        
        <div class="flex items-center gap-3">
          <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="flex items-center gap-3">
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php" class="bg-yellow-400 text-purple-900 px-4 py-2 rounded-full font-bold hover:bg-yellow-300 transition flex items-center gap-2 shadow-lg text-sm">
                  <i class="fas fa-user-shield"></i> Quản trị
                </a>
              <?php endif; ?>

              <a href="profile.php" class="text-white font-bold hover:text-purple-200 transition text-lg">
                👤 <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?>
              </a>
              <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-full font-bold hover:bg-red-700 transition">
                Đăng xuất
              </a>
            </div>
          <?php else: ?>
            <a href="login.php" class="bg-white text-purple-600 px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition">
              Đăng nhập
            </a>
            <a href="dangki.php" class="border-2 border-white text-white px-6 py-3 rounded-full font-bold hover:bg-purple-400 transition">
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
          echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-purple-600 w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-md $hiddenClass'>{$count}</span>";
          ?>
        </a>
      </div>
    </div>
  </header>

  <!-- Hero Banner -->
  <div class="bg-gradient-to-r from-purple-400 to-blue-400 text-white py-20">
    <div class="max-w-7xl mx-auto px-10 text-center">
      <h1 class="text-6xl font-black mb-4">🏗️ VLXD KAT</h1>
      <p class="text-2xl mb-8">Vật Liệu Xây Dựng Chất Lượng Cao - Giá Cả Cạnh Tranh</p>
      <div class="flex justify-center gap-4">
        <a href="products.php" class="bg-white text-purple-500 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition shadow-xl">
          <i class="fas fa-shopping-bag"></i> Xem tất cả sản phẩm
        </a>
        <a href="#featured" class="bg-purple-500 text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-purple-600 transition shadow-xl">
          <i class="fas fa-star"></i> Sản phẩm nổi bật
        </a>
      </div>
    </div>
  </div>

  <!-- Danh mục nhanh -->
  <div class="max-w-7xl mx-auto px-10 py-12">
    <h2 class="text-3xl font-black text-center text-gray-800 mb-8">
      <i class="fas fa-th-large"></i> DANH MỤC SẢN PHẨM
    </h2>
    <div class="grid md:grid-cols-4 gap-6">
      <?php
      $cats = $conn->query("SELECT * FROM categories WHERE STATUS=1 ORDER BY NAME");
      while ($c = $cats->fetch_assoc()) {
          $catName = isset($c['name']) ? $c['name'] : $c['NAME'];
          echo "<a href='products.php?category_id={$c['id']}' class='bg-white rounded-xl p-6 text-center shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2'>
                  <div class='text-5xl mb-4'>📦</div>
                  <h3 class='font-bold text-lg text-gray-800'>" . htmlspecialchars($catName) . "</h3>
                </a>";
      }
      ?>
    </div>
  </div>

  <!-- Hàng mới về -->
  <div class="bg-blue-50 py-16" id="new-products">
    <div class="max-w-7xl mx-auto px-10">
      <h2 class="text-5xl font-black text-center text-blue-600 mb-10">
        <i class="fas fa-gift"></i> HÀNG MỚI VỀ
      </h2>
      <div class="grid md:grid-cols-4 gap-8">
        <?php
        // Query lấy sản phẩm mới nhất
        $sql_new = "SELECT id, NAME, price, sale_price, images 
                    FROM products 
                    WHERE STATUS = 'active' 
                    ORDER BY created_at DESC 
                    LIMIT 8";
        
        $result_new = $conn->query($sql_new);

        if ($result_new && $result_new->num_rows > 0) {
            while ($p = $result_new->fetch_assoc()) {
                $images = json_decode($p['images'], true);
                $image_url = 'https://via.placeholder.com/300x300?text=No+Image';
                if (!empty($images)) {
                    $first_image = $images[0];
                    if (strpos($first_image, 'uploads/') === 0) {
                        $image_url = $first_image;
                    } else {
                        $image_url = 'uploads/' . $first_image;
                    }
                }
                $display_price = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
                
                echo "<div class='bg-white rounded-2xl shadow-2xl p-6 text-center hover:shadow-3xl transition flex flex-col h-full transform hover:-translate-y-2 duration-300'>
                        <div class='flex-grow'>
                          <div class='relative group-hover:scale-105 transition duration-300'>
                              <img src='" . htmlspecialchars($image_url) . "' alt='" . htmlspecialchars($p['NAME']) . "' class='bg-gray-200 h-48 w-full object-cover rounded-xl mb-6'>
                              <span class='absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm flex items-center gap-1'>
                                  <i class='fas fa-sparkles'></i> MỚI
                              </span>
                          </div>
                          <h3 class='font-bold text-xl mb-2 line-clamp-2 min-h-[3.5rem]'>" . htmlspecialchars($p['NAME']) . "</h3>
                          <p class='text-3xl font-black text-blue-600 mb-2'>" . number_format($display_price, 0, ',', '.') . "đ</p>
                        </div>
                        
                        <form action='add_to_cart.php' method='POST' class='mt-auto add-to-cart-form'>
                          <input type='hidden' name='product_id' value='{$p['id']}'>
                          <input type='hidden' name='quantity' value='1'>
                          <button type='submit' class='w-full bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 text-xl shadow-lg transform active:scale-95 transition'>
                            <i class='fas fa-cart-plus'></i> Thêm vào giỏ
                          </button>
                        </form>
                      </div>";
            }
        } else {
            echo "<div class='col-span-4 text-center py-16'>
                    <i class='fas fa-box-open text-6xl text-gray-300 mb-4'></i>
                    <p class='text-gray-500 text-xl'>Chưa có sản phẩm mới.</p>
                  </div>";
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Sản phẩm nổi bật -->
  <div class="max-w-7xl mx-auto px-10 py-16" id="featured">
    <h2 class="text-5xl font-black text-center text-purple-500 mb-6">
      <i class="fas fa-star"></i> SẢN PHẨM NỔI BẬT
    </h2>
    <p class="text-center text-gray-600 mb-10 text-lg">Được đánh giá cao và bán chạy nhất</p>

    <div class="product-grid">
      <?php
      $cat_id = '';

      // --- LOGIC: Sản phẩm đề xuất (Bán nhiều + Đánh giá tốt) ---
      $sql = "SELECT p.id, p.NAME, p.price, p.sale_price, p.images, 
                     COALESCE(SUM(oi.quantity), 0) as total_sold,
                     COALESCE(COUNT(DISTINCT oi.id), 0) as total_sold
              FROM products p
              LEFT JOIN order_items oi ON p.id = oi.product_id
              LEFT JOIN orders o ON oi.order_id = o.id AND (o.order_status IS NULL OR o.order_status != 'cancelled')
              WHERE p.STATUS = 'active'";

      $sql .= " GROUP BY p.id";
      // Sắp xếp theo: số lượng bán nhiều
      $sql .= " ORDER BY total_sold DESC, p.created_at DESC LIMIT 8";

      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while ($p = $result->fetch_assoc()) {
              $images = json_decode($p['images'], true);
              $image_url = 'https://via.placeholder.com/300x300?text=No+Image';
              if (!empty($images)) {
                  $first_image = $images[0];
                  if (strpos($first_image, 'uploads/') === 0) {
                      $image_url = $first_image;
                  } else {
                      $image_url = 'uploads/' . $first_image;
                  }
              }
              $display_price = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
              $avg_rating = round($p['avg_rating'], 1);
              $review_count = $p['review_count'];
              
              echo "<div class='product-card bg-white rounded-2xl shadow-2xl p-6 text-center hover:shadow-3xl transition duration-300 transform hover:-translate-y-2'>
                      <div class='card-content'>
                        <div class='relative mb-4'>
                            <a href='product_detail.php?id={$p['id']}' class='block'>
                                <img src='" . htmlspecialchars($image_url) . "' alt='" . htmlspecialchars($p['NAME']) . "' class='bg-gray-200 h-48 w-full object-cover rounded-xl hover:opacity-90 transition'>
                            </a>";
              
              if ($avg_rating > 0) {
                  echo "<span class='absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm flex items-center gap-1'>
                          <i class='fas fa-star'></i> {$avg_rating} ({$review_count})
                        </span>";
              }
              
              echo "        </div>
                        <div class='flex-grow flex flex-col justify-center'>
                            <a href='product_detail.php?id={$p['id']}' class='block hover:text-purple-600 transition'>
                                <h3 class='font-bold text-xl mb-3 line-clamp-2 min-h-[3rem] flex items-center justify-center'>" . htmlspecialchars($p['NAME']) . "</h3>
                            </a>
                            <p class='text-3xl font-black text-purple-500 mb-4'>" . number_format($display_price, 0, ',', '.') . "đ</p>
                        </div>
                      </div>
                      
                      <div class='card-actions space-y-2'>
                        <a href='product_detail.php?id={$p['id']}' class='block w-full bg-blue-500 text-white py-2 rounded-lg font-semibold hover:bg-blue-600 transition'>
                            <i class='fas fa-eye'></i> Xem chi tiết
                        </a>
                        <form action='add_to_cart.php' method='POST' class='add-to-cart-form' onclick='event.stopPropagation();'>
                            <input type='hidden' name='product_id' value='{$p['id']}'>
                            <input type='hidden' name='quantity' value='1'>
                            <button type='submit' class='w-full bg-purple-500 text-white py-2 rounded-lg font-semibold hover:bg-purple-600 transition'>
                                <i class='fas fa-cart-plus'></i> Thêm vào giỏ
                            </button>
                        </form>
                      </div>
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

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-12 mt-16">
    <div class="max-w-7xl mx-auto px-10 grid md:grid-cols-3 gap-8">
      <div>
        <h3 class="text-2xl font-bold mb-4">VLXD KAT</h3>
        <p class="text-gray-400">Vật liệu xây dựng chất lượng cao, uy tín hàng đầu Việt Nam.</p>
      </div>
      <div>
        <h4 class="text-xl font-bold mb-4">Liên kết nhanh</h4>
        <ul class="space-y-2 text-gray-400">
          <li><a href="products.php" class="hover:text-white transition"><i class="fas fa-angle-right"></i> Sản phẩm</a></li>
          <li><a href="#featured" class="hover:text-white transition"><i class="fas fa-angle-right"></i> Sản phẩm nổi bật</a></li>
          <li><a href="#new-products" class="hover:text-white transition"><i class="fas fa-angle-right"></i> Hàng mới về</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-xl font-bold mb-4">Liên hệ</h4>
        <ul class="space-y-2 text-gray-400">
          <li><i class="fas fa-phone"></i> Hotline: 1900 xxxx</li>
          <li><i class="fas fa-envelope"></i> Email: vlxdkat@gmail.com</li>
          <li><i class="fas fa-map-marker-alt"></i> Địa chỉ: 126 Nguyễn Thiện Thành, Phường 5, Trà Vinh, Việt Nam</li>
        </ul>
      </div>
    </div>
    <div class="text-center text-gray-500 mt-8 pt-8 border-t border-gray-700">
      <p>&copy; 2025 VLXD KAT. All rights reserved.</p>
    </div>
  </footer>

<script src="assets/js/main.js"></script>
</body>
</html>
