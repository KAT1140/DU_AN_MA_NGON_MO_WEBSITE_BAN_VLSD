<?php
require 'config.php';

// Admin-only page
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// Handle add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_product') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $short_description = $_POST['short_description'] ?? '';
        $sku = $_POST['sku'] ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $sale_price = (float)($_POST['sale_price'] ?? 0);
        $cost_price = (float)($_POST['cost_price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $unit = $_POST['unit'] ?? '';
        
        if (!$name || !$sku || $category_id <= 0 || $price <= 0) {
            $error = '❌ Vui lòng điền đầy đủ thông tin bắt buộc';
        } else {
            $stmt = $conn->prepare("INSERT INTO products (NAME, description, short_description, sku, category_id, supplier_id, price, sale_price, cost_price, quantity, unit, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if ($stmt) {
                $stmt->bind_param('ssssiiiddis', $name, $description, $short_description, $sku, $category_id, $supplier_id, $price, $sale_price, $cost_price, $quantity, $unit);
                if ($stmt->execute()) {
                    $msg = '✅ Thêm sản phẩm thành công!';
                } else {
                    $error = '❌ Lỗi: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Handle update quantity
    elseif ($action === 'update_quantity') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($product_id <= 0 || $quantity < 0) {
            $error = '❌ Dữ liệu không hợp lệ';
        } else {
            $stmt = $conn->prepare("UPDATE products SET quantity = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $quantity, $product_id);
                if ($stmt->execute()) {
                    $msg = '✅ Cập nhật số lượng thành công!';
                } else {
                    $error = '❌ Lỗi: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Handle delete product
    elseif ($action === 'delete_product') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            $error = '❌ ID sản phẩm không hợp lệ';
        } else {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $product_id);
                if ($stmt->execute()) {
                    $msg = '✅ Xóa sản phẩm thành công!';
                } else {
                    $error = '❌ Lỗi: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch categories
$categories = $conn->query("SELECT id, NAME FROM categories WHERE STATUS = 1 ORDER BY NAME");

// Fetch suppliers
$suppliers = $conn->query("SELECT id, NAME FROM suppliers WHERE STATUS = 1 ORDER BY NAME");

// Fetch products
$products = $conn->query("SELECT p.id, p.NAME, p.sku, p.price, p.quantity, c.NAME as category, s.NAME as supplier 
                          FROM products p 
                          LEFT JOIN categories c ON c.id = p.category_id 
                          LEFT JOIN suppliers s ON s.id = p.supplier_id 
                          ORDER BY p.id DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Quản lý sản phẩm</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-4xl font-bold text-gray-800 mb-2"><i class="fas fa-boxes"></i> Quản lý sản phẩm</h1>
        <p class="text-gray-600">Thêm, sửa và xóa sản phẩm</p>
      </div>
      <div class="space-x-2">
        <a href="profile.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition font-bold">
          <i class="fas fa-user"></i> Hồ sơ cá nhân
        </a>
        <a href="admin.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
          <i class="fas fa-users"></i> Quản lý người dùng
        </a>
        <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
          <i class="fas fa-home"></i> Trang chủ
        </a>
      </div>
    </div>

    <!-- Messages -->
    <?php if ($msg): ?>
      <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded"><?= $error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Add Product Form -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-md overflow-hidden sticky top-6">
          <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4">
            <h2 class="text-white font-bold text-lg"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</h2>
          </div>
          
          <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add_product">
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Tên sản phẩm *</label>
              <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Ví dụ: Xi măng Holcim">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">SKU *</label>
              <input type="text" name="sku" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="XM-001">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Danh mục *</label>
              <select name="category_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">-- Chọn danh mục --</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['NAME']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Nhà cung cấp</label>
              <select name="supplier_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">-- Chọn nhà cung cấp --</option>
                <?php 
                $suppliers = $conn->query("SELECT id, NAME FROM suppliers WHERE STATUS = 1 ORDER BY NAME");
                while ($sup = $suppliers->fetch_assoc()): 
                ?>
                  <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['NAME']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Giá bán (VNĐ) *</label>
              <input type="number" name="price" required step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="185000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Giá khuyến mãi (VNĐ)</label>
              <input type="number" name="sale_price" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="175000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Giá vốn (VNĐ)</label>
              <input type="number" name="cost_price" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="160000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Số lượng tồn</label>
              <input type="number" name="quantity" value="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Đơn vị</label>
              <input type="text" name="unit" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="bao, viên, ...">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả ngắn</label>
              <textarea name="short_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Mô tả ngắn..."></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả chi tiết</label>
              <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Mô tả chi tiết..."></textarea>
            </div>
            
            <button type="submit" class="w-full bg-orange-600 text-white py-3 rounded-lg font-bold hover:bg-orange-700 transition">
              <i class="fas fa-plus"></i> Thêm sản phẩm
            </button>
          </form>
        </div>
      </div>

      <!-- Products List -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
            <h2 class="text-white font-bold text-lg"><i class="fas fa-list"></i> Danh sách sản phẩm</h2>
          </div>
          
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-100 border-b">
                <tr>
                  <th class="px-4 py-3 text-left">ID</th>
                  <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                  <th class="px-4 py-3 text-left">SKU</th>
                  <th class="px-4 py-3 text-center">Giá</th>
                  <th class="px-4 py-3 text-center">Tồn kho</th>
                  <th class="px-4 py-3 text-center">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($products && $products->num_rows > 0): ?>
                  <?php while ($p = $products->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                      <td class="px-4 py-3 font-bold text-orange-600">#<?= $p['id'] ?></td>
                      <td class="px-4 py-3">
                        <div class="font-bold"><?= htmlspecialchars($p['NAME']) ?></div>
                        <div class="text-xs text-gray-600">📦 <?= htmlspecialchars($p['category'] ?? 'N/A') ?></div>
                      </td>
                      <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['sku']) ?></td>
                      <td class="px-4 py-3 text-center font-bold text-orange-600"><?= number_format($p['price']) ?>đ</td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block px-3 py-1 rounded-full text-white font-bold <?= $p['quantity'] > 0 ? 'bg-green-500' : 'bg-red-500' ?>">
                          <?= $p['quantity'] ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <button class="edit-qty-btn bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-xs" data-product-id="<?= $p['id'] ?>" data-product-name="<?= htmlspecialchars($p['NAME']) ?>" data-current-qty="<?= $p['quantity'] ?>">
                          <i class="fas fa-edit"></i> Sửa
                        </button>
                        <form method="POST" class="inline-block" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-xs">
                            <i class="fas fa-trash"></i> Xóa
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Chưa có sản phẩm nào</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Sửa số lượng -->
  <div id="editQtyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full">
      <h3 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-pen-to-square"></i> Cập nhật số lượng</h3>
      
      <form method="POST">
        <input type="hidden" name="action" value="update_quantity">
        <input type="hidden" name="product_id" id="modalProductId">
        
        <div class="mb-6">
          <p class="text-gray-600 mb-2">Sản phẩm: <span id="modalProductName" class="font-bold text-orange-600"></span></p>
          <label class="block text-sm font-bold text-gray-700 mb-2">Số lượng tồn mới</label>
          <input type="number" name="quantity" id="modalQuantity" required min="0" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-orange-500 text-lg">
        </div>
        
        <div class="flex gap-3">
          <button type="button" onclick="closeModal()" class="flex-1 bg-gray-400 text-white py-2 rounded-lg hover:bg-gray-500 transition font-bold">
            Hủy
          </button>
          <button type="submit" class="flex-1 bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700 transition font-bold">
            <i class="fas fa-save"></i> Lưu
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(productId, productName, currentQty) {
      document.getElementById('modalProductId').value = productId;
      document.getElementById('modalProductName').textContent = productName;
      document.getElementById('modalQuantity').value = currentQty;
      document.getElementById('editQtyModal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('editQtyModal').classList.add('hidden');
    }

    document.querySelectorAll('.edit-qty-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const productId = btn.getAttribute('data-product-id');
        const productName = btn.getAttribute('data-product-name');
        const currentQty = btn.getAttribute('data-current-qty');
        openModal(productId, productName, currentQty);
      });
    });

    // Close modal when clicking outside
    document.getElementById('editQtyModal').addEventListener('click', (e) => {
      if (e.target.id === 'editQtyModal') {
        closeModal();
      }
    });
  </script>
</body>
</html>
