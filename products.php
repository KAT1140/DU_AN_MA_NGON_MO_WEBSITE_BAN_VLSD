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
  <nav class="sticky top-0 z-40 bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-blue-600 flex items-center gap-2">
        <img src="uploads/logo.png" alt="VLXD Logo" class="w-10 h-10 object-cover rounded-full">
        VLXD KAT
      </a>
      <div class="flex items-center gap-6">
        <a href="index.php" class="text-gray-700 hover:text-blue-600"><i class="fas fa-home"></i> Trang chủ</a>
        <a href="products.php" class="text-blue-600 font-semibold"><i class="fas fa-box"></i> Sản phẩm</a>
        
        <a href="cart.php" class="text-gray-700 hover:text-blue-600 relative group">
          <i class="fas fa-shopping-cart text-2xl"></i>
          <?php
          // Đảm bảo lấy đúng session id
          $sid = $conn->real_escape_string($_SESSION['cart_id'] ?? session_id());
          $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '$sid'");
          $count = ($res && $row = $res->fetch_assoc()) ? ($row['total_qty'] ?? 0) : 0;
          $hiddenClass = ($count > 0) ? '' : 'hidden';
          ?>
          <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold <?= $hiddenClass ?>">
            <?= $count ?>
          </span>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php" class="text-gray-700 hover:text-blue-600"><i class="fas fa-user"></i> Tài khoản</a>
          <a href="logout.php" class="text-gray-700 hover:text-red-600"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
          <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

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
          <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-sm font-semibold">
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
                $images = json_decode($product['images'], true);
                $image_url = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
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
                    <span class="text-xl font-bold text-red-600"><?= number_format($price, 0, ',', '.') ?>đ</span>
                    <?php if($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                        <span class="text-sm text-gray-400 line-through"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="p-4 pt-0 mt-auto flex gap-2">
                   <form action="add_to_cart.php" method="POST" class="flex-1 add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition flex items-center justify-center gap-2">
                          <i class="fas fa-shopping-cart"></i> Thêm
                        </button>
                   </form>
                   <a href="#" class="border border-blue-600 text-blue-600 px-4 py-2 rounded hover:bg-blue-50 transition flex items-center gap-2">
                      <i class="fas fa-eye"></i> Xem
                   </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

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