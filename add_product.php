<?php
$require_admin_note = '';
require 'config.php';

// Only allow admin to add products
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
  $require_admin_note = 'Bạn cần phải là admin để thêm sản phẩm. <a href="login.php">Đăng nhập</a> bằng tài khoản admin.';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $price = $_POST['price'] ?? '';
  $cat = $_POST['category_id'] ?? '';
  $description = trim($_POST['description'] ?? '');
  $quantity = (int)($_POST['quantity'] ?? 0);

  if ($name === '' || $price === '' || $cat === '') {
    $error = 'Vui lòng điền đầy đủ tên, giá và danh mục.';
  } else {
    // Handle image upload
    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
      $file = $_FILES['image'];
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $allowed = ['jpg','jpeg','png','webp','gif'];
      if (!in_array(strtolower($ext), $allowed)) {
        $error = 'Chỉ chấp nhận ảnh (jpg, png, webp, gif).';
      } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Lỗi khi upload ảnh.';
      } else {
        $targetName = uniqid('prod_') . '.' . $ext;
        $targetPath = __DIR__ . '/uploads/' . $targetName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
          $image_url = 'uploads/' . $targetName;
        } else {
          $error = 'Không thể lưu file ảnh.';
        }
      }
    }

    if ($error === '') {
      $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, category_id, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param('ssdiss', $name, $description, $price, $quantity, $cat, $image_url);
      if ($stmt->execute()) {
        $success = '✅ Đã thêm sản phẩm: ' . htmlspecialchars($name);
      } else {
        $error = 'Lỗi khi thêm sản phẩm: ' . $stmt->error;
      }
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thêm sản phẩm</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-xl">
    <h1 class="text-3xl font-black text-purple-600 mb-6">➕ Thêm sản phẩm mới</h1>

    <?php if ($require_admin_note): ?>
      <div class="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700"><?= $require_admin_note ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-700"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      <input type="text" name="name" placeholder="Tên sản phẩm" required class="p-3 border rounded w-full" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      <textarea name="description" placeholder="Mô tả ngắn" class="p-3 border rounded w-full" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      <div class="grid grid-cols-2 gap-4">
        <input type="number" step="0.01" name="price" placeholder="Giá (VND)" required class="p-3 border rounded w-full" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        <input type="number" name="quantity" placeholder="Số lượng" class="p-3 border rounded w-full" value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>">
      </div>

      <select name="category_id" required class="p-3 border rounded w-full">
        <option value="">-- Chọn danh mục --</option>
        <?php
        $cats = $conn->query("SELECT * FROM categories");
        while ($c = $cats->fetch_assoc()) {
            $sel = (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : '';
            echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
        }
        ?>
      </select>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Ảnh sản phẩm</label>
        <input type="file" name="image" accept="image/*" class="w-full">
      </div>

      <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded-xl font-bold hover:bg-purple-700">Thêm sản phẩm</button>
    </form>
  </div>
</body>
</html>
