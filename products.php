<?php 
require 'config.php'; 
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sản Phẩm - VLXD KAT</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    .fade-out {
      animation: fadeOut 0.3s ease-out forwards;
    }
    @keyframes fadeOut {
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Header -->
  <nav class="sticky top-0 z-40 bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <a href="index.php" class="text-2xl font-bold text-blue-600 flex items-center gap-2">
        <i class="fas fa-hammer"></i> VLXD KAT
      </a>
      <div class="flex items-center gap-6">
        <a href="index.php" class="text-gray-700 hover:text-blue-600"><i class="fas fa-home"></i> Trang chủ</a>
        <a href="products.php" class="text-blue-600 font-semibold"><i class="fas fa-box"></i> Sản phẩm</a>
        <a href="cart.php" class="text-gray-700 hover:text-blue-600 relative">
          <i class="fas fa-shopping-cart"></i> Giỏ hàng
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php" class="text-gray-700 hover:text-blue-600"><i class="fas fa-user"></i> Tài khoản</a>
          <a href="logout.php" class="text-gray-700 hover:text-red-600"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        <?php else: ?>
          <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Title & Filter -->
    <div class="mb-8">
      <h1 class="text-4xl font-bold text-gray-800 mb-2"><i class="fas fa-box"></i> Danh Sách Sản Phẩm</h1>
      <p class="text-gray-600">Chọn danh mục để xem sản phẩm</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- Sidebar - Categories -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-filter"></i> Danh Mục
          </h2>
          <div class="space-y-2">
            <!-- All Products -->
            <a href="products.php" class="block p-3 rounded-lg hover:bg-blue-100 transition <?= !isset($_GET['category_id']) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' ?>">
              <i class="fas fa-th"></i> Tất cả sản phẩm
              <span class="float-right bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm">
                <?php 
                  $total = $conn->query("SELECT COUNT(*) as count FROM products WHERE STATUS='active'")->fetch_assoc();
                  echo $total['count'];
                ?>
              </span>
            </a>

            <!-- Categories -->
            <?php
              $categories = $conn->query("SELECT id, NAME FROM categories WHERE STATUS=1 ORDER BY NAME");
              while ($cat = $categories->fetch_assoc()) {
                $count = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id=" . $cat['id'] . " AND STATUS='active'")->fetch_assoc();
                $active = isset($_GET['category_id']) && $_GET['category_id'] == $cat['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700';
            ?>
              <a href="products.php?category_id=<?= $cat['id'] ?>" class="block p-3 rounded-lg hover:bg-blue-100 transition <?= $active ?>">
                <i class="fas fa-tag"></i> <?= htmlspecialchars($cat['NAME']) ?>
                <span class="float-right bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm">
                  <?= $count['count'] ?>
                </span>
              </a>
            <?php } ?>
          </div>
        </div>
      </div>

      <!-- Main Content - Products Grid -->
      <div class="lg:col-span-3">
        <?php
          $where = "WHERE STATUS='active'";
          $title = "Tất cả sản phẩm";
          
          if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
            $cat_id = intval($_GET['category_id']);
            $where .= " AND category_id=" . $cat_id;
            $cat_name = $conn->query("SELECT NAME FROM categories WHERE id=" . $cat_id)->fetch_assoc();
            if ($cat_name) {
              $title = htmlspecialchars($cat_name['NAME']);
            }
          }

          $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
          $per_page = 12;
          $offset = ($page - 1) * $per_page;

          $total_result = $conn->query("SELECT COUNT(*) as count FROM products " . $where);
          $total_products = $total_result->fetch_assoc()['count'];
          $total_pages = ceil($total_products / $per_page);

          $query = "SELECT id, NAME, description, price, sale_price, quantity, images FROM products " . $where . " ORDER BY created_at DESC LIMIT " . $offset . ", " . $per_page;
          $result = $conn->query($query);
        ?>

        <!-- Category Title & Count -->
        <div class="mb-6 flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-800"><?= $title ?></h2>
          <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-semibold">
            <i class="fas fa-cube"></i> <?= $total_products ?> sản phẩm
          </span>
        </div>

        <?php if ($total_products == 0): ?>
          <div class="text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500">Không có sản phẩm nào trong danh mục này</p>
          </div>
        <?php else: ?>
          <!-- Products Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php while ($product = $result->fetch_assoc()): ?>
              <?php
                $images = json_decode($product['images'], true);
                $image_url = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=No+Image';
                $original_price = $product['price'];
                $sale_price = $product['sale_price'] ?? $original_price;
                $discount = $original_price > $sale_price ? round(((($original_price - $sale_price) / $original_price) * 100)) : 0;
              ?>
              <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden group">
                <!-- Image -->
                <div class="relative overflow-hidden h-64 bg-gray-100">
                  <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($product['NAME']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition">
                  
                  <!-- Discount Badge -->
                  <?php if ($discount > 0): ?>
                    <div class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                      -<?= $discount ?>%
                    </div>
                  <?php endif; ?>

                  <!-- Stock Status -->
                  <div class="absolute bottom-3 left-3">
                    <?php if ($product['quantity'] > 0): ?>
                      <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">
                        <i class="fas fa-check-circle"></i> Còn hàng
                      </span>
                    <?php else: ?>
                      <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                        <i class="fas fa-times-circle"></i> Hết hàng
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Product Info -->
                <div class="p-4">
                  <h3 class="text-lg font-semibold text-gray-800 mb-2 line-clamp-2">
                    <?= htmlspecialchars($product['NAME']) ?>
                  </h3>
                  
                  <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                    <?= htmlspecialchars($product['description']) ?>
                  </p>

                  <!-- Price -->
                  <div class="mb-4">
                    <?php if ($discount > 0): ?>
                      <div class="flex items-center gap-2">
                        <span class="text-2xl font-bold text-red-600">
                          <?= number_format($sale_price, 0, ',', '.') ?>₫
                        </span>
                        <span class="text-sm text-gray-500 line-through">
                          <?= number_format($original_price, 0, ',', '.') ?>₫
                        </span>
                      </div>
                    <?php else: ?>
                      <span class="text-2xl font-bold text-gray-800">
                        <?= number_format($original_price, 0, ',', '.') ?>₫
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Actions -->
                  <div class="flex gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['NAME']) ?>')" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                      <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                    </button>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="flex-1 border-2 border-blue-600 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition flex items-center justify-center gap-2">
                      <i class="fas fa-eye"></i> Xem
                    </a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-12">
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php 
                  $query_params = isset($_GET['category_id']) ? '?category_id=' . $_GET['category_id'] . '&page=' . $i : '?page=' . $i;
                  $active = $page == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border-2 border-gray-200 hover:border-blue-600';
                ?>
                <a href="products.php<?= $query_params ?>" class="px-4 py-2 rounded-lg transition <?= $active ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function addToCart(productId, productName) {
      const quantity = 1;
      
      fetch('add_to_cart.php', {
        method: 'POST',
        body: new FormData(Object.assign(document.createElement('form'), {
          elements: [
            Object.assign(document.createElement('input'), { name: 'product_id', value: productId }),
            Object.assign(document.createElement('input'), { name: 'quantity', value: quantity })
          ]
        }))
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast('Đã thêm ' + productName + ' vào giỏ hàng!', 'success');
        } else {
          showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra', 'error');
      });
    }

    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = 'toast ' + type;
      toast.innerHTML = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>
</body>
</html>
