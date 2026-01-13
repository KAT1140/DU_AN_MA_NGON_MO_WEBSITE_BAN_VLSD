<?php 
require 'config.php'; 
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sản Phẩm - VLXD KAT</title>
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
    .toast.success { border-left: 4px solid #10b981; color: #10b981; }
    .toast.error { border-left: 4px solid #ef4444; color: #ef4444; }
    @keyframes slideIn {
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      to { transform: translateX(400px); opacity: 0; }
    }
    .bounce { animation: bounce 0.3s; }
    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.3); }
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
          <a href="index.php" class="text-white font-bold hover:text-purple-200 transition text-lg flex items-center gap-2">
            <i class="fas fa-home"></i> Trang chủ
          </a>
          <a href="products.php" class="text-white font-bold hover:text-purple-200 transition text-lg flex items-center gap-2 underline">
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
          $sid = $conn->real_escape_string($_SESSION['cart_id'] ?? session_id());
          $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '$sid'");
          $count = ($res && $row = $res->fetch_assoc()) ? ($row['total_qty'] ?? 0) : 0;
          $hiddenClass = ($count > 0) ? '' : 'hidden';
          echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-purple-600 w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-md $hiddenClass'>{$count}</span>";
          ?>
        </a>
      </div>
    </div>
  </header>

 <div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800 mb-2"><i class="fas fa-box"></i> Danh Sách Sản Phẩm</h1>
      <p class="text-gray-600">Chọn danh mục để xem sản phẩm</p>
    </div>

   <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-filter"></i> Danh Mục
          </h2>
         <div class="space-y-2">
            <a href="products.php" class="block p-3 rounded-lg hover:bg-blue-100 transition <?= !isset($_GET['category_id']) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
              <i class="fas fa-th"></i> Tất cả sản phẩm
            </a>
            <?php
              $categories = $conn->query("SELECT id, NAME FROM categories WHERE STATUS=1 ORDER BY NAME");
             while ($cat = $categories->fetch_assoc()) {
                $active = isset($_GET['category_id']) && $_GET['category_id'] == $cat['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700';
            ?>
              <a href="products.php?category_id=<?= $cat['id'] ?>" class="block p-3 rounded-lg hover:bg-blue-100 transition <?= $active ?>">
               <i class="fas fa-tag"></i> <?= htmlspecialchars($cat['NAME']) ?>
              </a>
            <?php } ?>
          </div>
        </div>
      </div>

     <div class="lg:col-span-3">
        <?php
          $where = "WHERE STATUS='active'";
          $title = "Tất cả sản phẩm";
          
          if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
            $cat_id = intval($_GET['category_id']);
            $where .= " AND category_id=" . $cat_id;
            $cat_name = $conn->query("SELECT NAME FROM categories WHERE id=" . $cat_id)->fetch_assoc();
            if ($cat_name) $title = htmlspecialchars($cat_name['NAME']);
          }

          $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
          $per_page = 12;
          $offset = ($page - 1) * $per_page;

          $total_result = $conn->query("SELECT COUNT(*) as count FROM products " . $where);
          $total_products = $total_result->fetch_assoc()['count'];
          $total_pages = ceil($total_products / $per_page);

          $query = "SELECT * FROM products " . $where . " ORDER BY created_at DESC LIMIT " . $offset . ", " . $per_page;
          $result = $conn->query($query);
        ?>

       <div class="mb-6 flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-800"><?= $title ?></h2>
          <span class="bg-purple-50 text-purple-600 px-3 py-1 rounded-lg text-sm font-semibold">
            <i class="fas fa-cube"></i> <?= $total_products ?> sản phẩm
          </span>
        </div>

        <?php if ($total_products == 0): ?>
          <div class="text-center py-12">
            <p class="text-xl text-gray-500">Không có sản phẩm nào.</p>
          </div>
        <?php else: ?>
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($product = $result->fetch_assoc()): 
                $images = json_decode($product['images'], true) ?: [];
                $image_url = 'https://via.placeholder.com/300x300?text=No+Image';
                if (!empty($images)) {
                    // Kiểm tra xem đường dẫn đã có 'uploads/' chưa
                    $first_image = $images[0];
                    if (strpos($first_image, 'uploads/') === 0) {
                        $image_url = $first_image;
                    } else {
                        $image_url = 'uploads/' . $first_image;
                    }
                }
                $price = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
            ?>
              <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden group flex flex-col h-full">
                <div class="relative h-48 bg-gray-100">
                  <img src="<?= htmlspecialchars($image_url) ?>" class="w-full h-full object-cover">
                  <?php if ($product['quantity'] > 0): ?>
                    <span class="absolute bottom-2 left-2 bg-green-500 text-white px-2 py-1 rounded text-xs"><i class="fas fa-check"></i> Còn hàng</span>
                  <?php else: ?>
                    <span class="absolute bottom-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-xs">Hết hàng</span>
                  <?php endif; ?>
                </div>

                <div class="p-4 flex-grow">
                  <h3 class="font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($product['NAME']) ?></h3>
                  <div class="flex items-center gap-2 mb-3">
                    <span class="text-xl font-bold text-purple-500"><?= number_format($price, 0, ',', '.') ?>đ</span>
                    <?php if($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                        <span class="text-sm text-gray-400 line-through"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="p-4 pt-0 mt-auto flex gap-2">
                   <form action="add_to_cart.php" method="POST" class="flex-1 add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="w-full bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition flex items-center justify-center gap-2">
                          <i class="fas fa-shopping-cart"></i> Thêm
                        </button>
                   </form>
                   <a href="#" class="border border-purple-500 text-purple-500 px-4 py-2 rounded hover:bg-purple-50 transition flex items-center gap-2">
                      <i class="fas fa-eye"></i> Xem
                   </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <?php
          // Hiển thị sản phẩm gợi ý liên quan
          $related_products = [];
          $related_title = '';
          
          if (isset($_GET['category_id'])) {
              $current_cat_id = intval($_GET['category_id']);
              
              // Định nghĩa các danh mục liên quan
              $related_categories = [
                  2 => [4, 'Sơn - Phụ phẩm kèm'], // Gạch -> Sơn
                  1 => [2, 'Gạch'],               // Xi măng -> Gạch
                  4 => [2, 'Gạch'],               // Sơn -> Gạch
                  3 => [1, 'Xi măng'],             // Thép -> Xi măng
                  5 => [3, 'Thép'],               // Tôn-Ngói -> Thép
              ];
              
              if (isset($related_categories[$current_cat_id])) {
                  $related_cat_id = $related_categories[$current_cat_id][0];
                  $related_title = $related_categories[$current_cat_id][1];
                  
                  $related_query = "SELECT * FROM products WHERE STATUS='active' AND category_id = $related_cat_id ORDER BY RAND() LIMIT 6";
                  $related_result = $conn->query($related_query);
                  
                  if ($related_result && $related_result->num_rows > 0) {
                      while ($related_product = $related_result->fetch_assoc()) {
                          $related_products[] = $related_product;
                      }
                  }
              }
          }
          ?>

         <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-8">
              <?php for ($i = 1; $i <= $total_pages; $i++): 
                  $active = $page == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border hover:border-blue-600';
                  $params = $_GET; $params['page'] = $i;
                  $query_str = http_build_query($params);
              ?>
                <a href="?<?= $query_str ?>" class="px-3 py-2 rounded <?= $active ?>"><?= $i ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<script src="assets/js/main.js"></script>
</body>
</html>
