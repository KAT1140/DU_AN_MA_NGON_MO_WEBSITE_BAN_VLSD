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
            // Handle multiple image uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                $max_files = 5;
                
                for ($i = 0; $i < min(count($_FILES['images']['name']), $max_files); $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_type = $_FILES['images']['type'][$i];
                        
                        if (in_array($file_type, $allowed_types)) {
                            $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                            $filename = uniqid('prod_') . '_' . $i . '.' . $ext;
                            $target = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target)) {
                                $images[] = 'uploads/' . $filename;
                            }
                        }
                    }
                }
            }
            
            $images_json = !empty($images) ? json_encode($images) : json_encode([]);
            
            $stmt = $conn->prepare("INSERT INTO products (NAME, description, short_description, sku, category_id, supplier_id, price, sale_price, cost_price, quantity, unit, images, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if ($stmt) {
                $stmt->bind_param('ssssiiiddiss', $name, $description, $short_description, $sku, $category_id, $supplier_id, $price, $sale_price, $cost_price, $quantity, $unit, $images_json);
                if ($stmt->execute()) {
                    $msg = '✅ Thêm sản phẩm thành công!';
                } else {
                    $error = '❌ Lỗi: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Handle update product
    elseif ($action === 'update_product') {
        $product_id = (int)($_POST['product_id'] ?? 0);
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
        
        if ($product_id <= 0 || !$name || !$sku || $category_id <= 0 || $price <= 0) {
            $error = '❌ Dữ liệu không hợp lệ';
        } else {
            // Handle image uploads if new images provided
            $update_images = false;
            $images = [];
            
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                $max_files = 5;
                
                for ($i = 0; $i < min(count($_FILES['images']['name']), $max_files); $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_type = $_FILES['images']['type'][$i];
                        
                        if (in_array($file_type, $allowed_types)) {
                            $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                            $filename = uniqid('prod_') . '_' . $i . '.' . $ext;
                            $target = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target)) {
                                $images[] = 'uploads/' . $filename;
                            }
                        }
                    }
                }
                
                if (!empty($images)) {
                    $update_images = true;
                }
            }
            
            if ($update_images) {
                $images_json = json_encode($images);
                $stmt = $conn->prepare("UPDATE products SET NAME = ?, description = ?, short_description = ?, sku = ?, category_id = ?, supplier_id = ?, price = ?, sale_price = ?, cost_price = ?, quantity = ?, unit = ?, images = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('ssssiiiddissi', $name, $description, $short_description, $sku, $category_id, $supplier_id, $price, $sale_price, $cost_price, $quantity, $unit, $images_json, $product_id);
                    if ($stmt->execute()) {
                        $msg = '✅ Cập nhật sản phẩm thành công!';
                    } else {
                        $error = '❌ Lỗi: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("UPDATE products SET NAME = ?, description = ?, short_description = ?, sku = ?, category_id = ?, supplier_id = ?, price = ?, sale_price = ?, cost_price = ?, quantity = ?, unit = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('ssssiiiddisi', $name, $description, $short_description, $sku, $category_id, $supplier_id, $price, $sale_price, $cost_price, $quantity, $unit, $product_id);
                    if ($stmt->execute()) {
                        $msg = '✅ Cập nhật sản phẩm thành công!';
                    } else {
                        $error = '❌ Lỗi: ' . $stmt->error;
                    }
                    $stmt->close();
                }
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

// Fetch products with filtering
$category_filter = $_GET['category'] ?? '';
$search_filter = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = (int)$category_filter;
    $param_types .= 'i';
}

if (!empty($search_filter)) {
    $where_conditions[] = "(p.NAME LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'sss';
}

if (!empty($status_filter)) {
    if ($status_filter === 'in_stock') {
        $where_conditions[] = "p.quantity > 0";
    } elseif ($status_filter === 'out_of_stock') {
        $where_conditions[] = "p.quantity = 0";
    } elseif ($status_filter === 'low_stock') {
        $where_conditions[] = "p.quantity > 0 AND p.quantity <= 10";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT p.id, p.NAME, p.sku, p.price, p.quantity, c.NAME as category, s.NAME as supplier, p.created_at
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        LEFT JOIN suppliers s ON s.id = p.supplier_id 
        {$where_clause}
        ORDER BY p.id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query($sql);
}

// Đếm đơn hàng chờ xử lý
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
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
        <a href="admin_orders.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition relative">
          <i class="fas fa-shopping-cart"></i> Đơn hàng
          <?php if ($pending_orders > 0): ?>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                  <?= $pending_orders ?>
              </span>
          <?php endif; ?>
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
          <div class="bg-gradient-to-r from-purple-600 to-blue-500 px-6 py-4">
            <h2 class="text-white font-bold text-lg"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</h2>
          </div>
          
          <form method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Tên sản phẩm *</label>
              <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Ví dụ: Xi măng Holcim">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">SKU *</label>
              <input type="text" name="sku" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="XM-001">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Danh mục *</label>
              <select name="category_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">-- Chọn danh mục --</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['NAME']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Nhà cung cấp</label>
              <select name="supplier_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
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
              <input type="number" name="price" required step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="185000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Giá khuyến mãi (VNĐ)</label>
              <input type="number" name="sale_price" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="175000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Giá vốn (VNĐ)</label>
              <input type="number" name="cost_price" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="160000">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Số lượng tồn</label>
              <input type="number" name="quantity" value="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Đơn vị</label>
              <input type="text" name="unit" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="bao, viên, ...">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả ngắn</label>
              <textarea name="short_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Mô tả ngắn..."></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả chi tiết</label>
              <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Mô tả chi tiết..."></textarea>
            </div>
            
            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">Hình ảnh sản phẩm (Tối đa 5 ảnh)</label>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition">
                <input type="file" name="images[]" id="productImages" multiple accept="image/*" class="hidden" onchange="previewImages(event)">
                <label for="productImages" class="cursor-pointer flex flex-col items-center">
                  <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                  <span class="text-gray-600">Kéo thả ảnh hoặc click để chọn</span>
                  <span class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP (Max 5 ảnh)</span>
                </label>
              </div>
              <div id="imagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
            </div>
            
            <button type="submit" class="w-full bg-purple-500 text-white py-3 rounded-lg font-bold hover:bg-purple-600 transition col-span-2">
              <i class="fas fa-plus"></i> Thêm sản phẩm
            </button>
          </form>
        </div>
      </div>

      <!-- Products List -->
      <div class="lg:col-span-2">
        <!-- Filter Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
          <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-purple-600"></i> Lọc & Tìm kiếm sản phẩm
          </h3>
          
          <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Tìm kiếm</label>
              <input type="text" name="search" value="<?= htmlspecialchars($search_filter) ?>" 
                     placeholder="Tên, SKU, mô tả..." 
                     class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Danh mục</label>
              <select name="category" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">-- Tất cả danh mục --</option>
                <?php 
                $categories_filter = $conn->query("SELECT id, NAME FROM categories WHERE STATUS = 1 ORDER BY NAME");
                while ($cat = $categories_filter->fetch_assoc()): 
                ?>
                  <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['NAME']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Trạng thái kho</label>
              <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">-- Tất cả --</option>
                <option value="in_stock" <?= $status_filter === 'in_stock' ? 'selected' : '' ?>>Còn hàng</option>
                <option value="low_stock" <?= $status_filter === 'low_stock' ? 'selected' : '' ?>>Sắp hết (≤10)</option>
                <option value="out_of_stock" <?= $status_filter === 'out_of_stock' ? 'selected' : '' ?>>Hết hàng</option>
              </select>
            </div>
            
            <div class="flex items-end gap-2">
              <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-search"></i> Lọc
              </button>
              <a href="admin_products.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                <i class="fas fa-times"></i>
              </a>
            </div>
          </form>
        </div>

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
                      <td class="px-4 py-3 font-bold text-purple-600">#<?= $p['id'] ?></td>
                      <td class="px-4 py-3">
                        <div class="font-bold"><?= htmlspecialchars($p['NAME']) ?></div>
                        <div class="text-xs text-gray-600">📦 <?= htmlspecialchars($p['category'] ?? 'N/A') ?></div>
                      </td>
                      <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['sku']) ?></td>
                      <td class="px-4 py-3 text-center font-bold text-purple-600"><?= number_format($p['price']) ?>đ</td>
                      <td class="px-4 py-3 text-center">
                        <span class="inline-block px-3 py-1 rounded-full text-white font-bold <?= $p['quantity'] > 0 ? 'bg-green-500' : 'bg-red-500' ?>">
                          <?= $p['quantity'] ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <button class="edit-product-btn bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-xs" 
                                data-product='<?= json_encode($p) ?>'>
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

  <!-- Modal Sửa sản phẩm -->
  <div id="editProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-4xl w-full m-4 max-h-[90vh] overflow-y-auto">
      <h3 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-pen-to-square"></i> Chỉnh sửa sản phẩm</h3>
      
      <form method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4">
        <input type="hidden" name="action" value="update_product">
        <input type="hidden" name="product_id" id="editProductId">
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Tên sản phẩm *</label>
          <input type="text" name="name" id="editName" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Mã SKU *</label>
          <input type="text" name="sku" id="editSku" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Danh mục *</label>
          <select name="category_id" id="editCategory" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            <?php 
            $categories = $conn->query("SELECT id, NAME FROM categories WHERE STATUS = 1 ORDER BY NAME");
            while ($cat = $categories->fetch_assoc()): 
            ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['NAME']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Nhà cung cấp</label>
          <select name="supplier_id" id="editSupplier" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
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
          <input type="number" name="price" id="editPrice" required step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Giá khuyến mãi (VNĐ)</label>
          <input type="number" name="sale_price" id="editSalePrice" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Giá vốn (VNĐ)</label>
          <input type="number" name="cost_price" id="editCostPrice" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Số lượng tồn</label>
          <input type="number" name="quantity" id="editQuantity" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Đơn vị</label>
          <input type="text" name="unit" id="editUnit" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả ngắn</label>
          <textarea name="short_description" id="editShortDesc" rows="2" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
        </div>
        
        <div class="col-span-2">
          <label class="block text-sm font-bold text-gray-700 mb-2">Mô tả chi tiết</label>
          <textarea name="description" id="editDescription" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
        </div>
        
        <div class="col-span-2">
          <label class="block text-sm font-bold text-gray-700 mb-2">Hình ảnh mới (Tối đa 5 ảnh) - Để trống nếu không thay đổi</label>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition">
            <input type="file" name="images[]" id="editProductImages" multiple accept="image/*" class="hidden" onchange="previewEditImages(event)">
            <label for="editProductImages" class="cursor-pointer flex flex-col items-center">
              <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
              <span class="text-gray-600">Kéo thả ảnh hoặc click để chọn</span>
              <span class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP (Max 5 ảnh)</span>
            </label>
          </div>
          <div id="editImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
          <div id="currentImages" class="grid grid-cols-5 gap-2 mt-3"></div>
        </div>
        
        <div class="col-span-2 flex gap-3 mt-4">
          <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-400 text-white py-3 rounded-lg hover:bg-gray-500 transition font-bold">
            Hủy
          </button>
          <button type="submit" class="flex-1 bg-purple-500 text-white py-3 rounded-lg hover:bg-purple-600 transition font-bold">
            <i class="fas fa-save"></i> Cập nhật
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function previewImages(event) {
      const files = event.target.files;
      const preview = document.getElementById('imagePreview');
      preview.innerHTML = '';
      
      if (files.length > 5) {
        alert('Chỉ được chọn tối đa 5 ảnh!');
        event.target.value = '';
        return;
      }
      
      Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.innerHTML = `
              <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border-2 border-gray-300">
              <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-lg">
                <span class="text-white text-xs font-bold">Ảnh ${index + 1}</span>
              </div>
            `;
            preview.appendChild(div);
          };
          reader.readAsDataURL(file);
        }
      });
    }
    
    function previewEditImages(event) {
      const files = event.target.files;
      const preview = document.getElementById('editImagePreview');
      preview.innerHTML = '';
      
      if (files.length > 5) {
        alert('Chỉ được chọn tối đa 5 ảnh!');
        event.target.value = '';
        return;
      }
      
      Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.innerHTML = `
              <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border-2 border-green-500">
              <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-lg">
                <span class="text-white text-xs font-bold">Ảnh mới ${index + 1}</span>
              </div>
            `;
            preview.appendChild(div);
          };
          reader.readAsDataURL(file);
        }
      });
    }
    
    function openEditModal(product) {
      document.getElementById('editProductId').value = product.id;
      document.getElementById('editName').value = product.NAME;
      document.getElementById('editSku').value = product.sku;
      document.getElementById('editCategory').value = product.category_id;
      document.getElementById('editSupplier').value = product.supplier_id || '';
      document.getElementById('editPrice').value = product.price;
      document.getElementById('editSalePrice').value = product.sale_price || '';
      document.getElementById('editCostPrice').value = product.cost_price || '';
      document.getElementById('editQuantity').value = product.quantity;
      document.getElementById('editUnit').value = product.unit || '';
      document.getElementById('editShortDesc').value = product.short_description || '';
      document.getElementById('editDescription').value = product.description || '';
      
      // Display current images
      const currentImagesDiv = document.getElementById('currentImages');
      currentImagesDiv.innerHTML = '';
      if (product.images) {
        try {
          const images = JSON.parse(product.images);
          if (Array.isArray(images) && images.length > 0) {
            images.forEach((img, index) => {
              const div = document.createElement('div');
              div.className = 'relative group';
              div.innerHTML = `
                <img src="${img}" class="w-full h-24 object-cover rounded-lg border-2 border-blue-500">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-lg">
                  <span class="text-white text-xs font-bold">Ảnh hiện tại ${index + 1}</span>
                </div>
              `;
              currentImagesDiv.appendChild(div);
            });
          }
        } catch(e) {}
      }
      
      document.getElementById('editImagePreview').innerHTML = '';
      document.getElementById('editProductImages').value = '';
      document.getElementById('editProductModal').classList.remove('hidden');
    }

    function closeEditModal() {
      document.getElementById('editProductModal').classList.add('hidden');
    }
    
    function openModal(productId, productName, currentQty) {
      document.getElementById('modalProductId').value = productId;
      document.getElementById('modalProductName').textContent = productName;
      document.getElementById('modalQuantity').value = currentQty;
      document.getElementById('editQtyModal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('editQtyModal').classList.add('hidden');
    }

    document.querySelectorAll('.edit-product-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const product = JSON.parse(btn.getAttribute('data-product'));
        openEditModal(product);
      });
    });

    document.querySelectorAll('.edit-qty-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const productId = btn.getAttribute('data-product-id');
        const productName = btn.getAttribute('data-product-name');
        const currentQty = btn.getAttribute('data-current-qty');
        openModal(productId, productName, currentQty);
      });
    });

    // Close modal when clicking outside
    document.getElementById('editProductModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'editProductModal') {
        closeEditModal();
      }
    });
    
    document.getElementById('editQtyModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'editQtyModal') {
        closeModal();
      }
    });
  </script>
</body>
</html>
