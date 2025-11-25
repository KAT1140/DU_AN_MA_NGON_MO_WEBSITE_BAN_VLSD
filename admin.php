<?php
require 'config.php';

// Admin-only page
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// Handle promote/demote actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];

    if ($user_id > 0) {
        if ($action === 'promote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $msg = "✅ Nâng cấp người dùng #$user_id thành admin thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'demote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'customer' WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $msg = "✅ Hạ cấp người dùng #$user_id thành khách hàng thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch users
$users = $conn->query("SELECT id, email, full_name, phone, role, created_at FROM users ORDER BY id DESC");
$total_users = $users ? $users->num_rows : 0;
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Quản lý người dùng</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header Navigation -->
  <header class="bg-gradient-to-r from-blue-600 to-blue-500 text-white sticky top-0 z-40 shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
            <i class="fas fa-users text-2xl"></i>
          </div>
          <h1 class="text-2xl font-black">Quản lý người dùng</h1>
        </div>
        <nav class="flex gap-2">
          <a href="profile.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition font-bold text-sm">
            <i class="fas fa-user"></i> Hồ sơ
          </a>
          <a href="admin_products.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition font-bold text-sm">
            <i class="fas fa-boxes"></i> Sản phẩm
          </a>
          <a href="index.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition font-bold text-sm">
            <i class="fas fa-home"></i> Trang chủ
          </a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Content -->
  <div class="max-w-7xl mx-auto p-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 font-semibold">Tổng người dùng</p>
            <p class="text-4xl font-black text-blue-600"><?= $total_users ?></p>
          </div>
          <i class="fas fa-users text-5xl text-blue-100"></i>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 font-semibold">Admin</p>
            <p class="text-4xl font-black text-green-600"><?= $admin_count ?></p>
          </div>
          <i class="fas fa-crown text-5xl text-green-100"></i>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 font-semibold">Khách hàng</p>
            <p class="text-4xl font-black text-orange-600"><?= $total_users - $admin_count ?></p>
          </div>
          <i class="fas fa-shopping-bag text-5xl text-orange-100"></i>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <?php if ($msg): ?>
      <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg flex items-center gap-3">
        <i class="fas fa-check-circle text-xl"></i>
        <span><?= $msg ?></span>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-xl"></i>
        <span><?= $error ?></span>
      </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
        <h2 class="text-white font-bold text-lg flex items-center gap-2">
          <i class="fas fa-table"></i> Danh sách người dùng
        </h2>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-100 border-b-2 border-gray-200">
            <tr>
              <th class="px-6 py-4 text-left font-bold text-gray-700">ID</th>
              <th class="px-6 py-4 text-left font-bold text-gray-700">Email</th>
              <th class="px-6 py-4 text-left font-bold text-gray-700">Họ tên</th>
              <th class="px-6 py-4 text-left font-bold text-gray-700">Số điện thoại</th>
              <th class="px-6 py-4 text-center font-bold text-gray-700">Quyền hạn</th>
              <th class="px-6 py-4 text-left font-bold text-gray-700">Ngày tạo</th>
              <th class="px-6 py-4 text-center font-bold text-gray-700">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total_users > 0): ?>
              <?php while ($u = $users->fetch_assoc()): 
                $is_current_user = ($u['email'] === ($_SESSION['user_email'] ?? ''));
              ?>
              <tr class="border-b hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                  <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold text-sm">#<?= $u['id'] ?></span>
                </td>
                <td class="px-6 py-4">
                  <div class="font-semibold text-gray-800"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-gray-700"><?= htmlspecialchars($u['full_name'] ?? '(Chưa cập nhật)') ?></div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-gray-700"><?= htmlspecialchars($u['phone'] ?? '(Chưa cập nhật)') ?></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="inline-block bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-700 px-4 py-2 rounded-full font-bold text-sm">
                      <i class="fas fa-crown mr-1"></i>Admin
                    </span>
                  <?php else: ?>
                    <span class="inline-block bg-gradient-to-r from-gray-100 to-gray-50 text-gray-700 px-4 py-2 rounded-full font-bold text-sm">
                      <i class="fas fa-user mr-1"></i>Khách hàng
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <div class="text-gray-600 text-sm"><?= date('d/m/Y', strtotime($u['created_at'])) ?></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <?php if ($is_current_user): ?>
                    <span class="text-gray-500 text-sm font-bold">(Bạn)</span>
                  <?php else: ?>
                    <?php if ($u['role'] !== 'admin'): ?>
                      <form method="POST" class="inline-block">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action" value="promote">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-bold text-sm transition" onclick="return confirm('Nâng cấp người dùng này lên admin?');">
                          <i class="fas fa-arrow-up"></i> Nâng cấp
                        </button>
                      </form>
                    <?php else: ?>
                      <form method="POST" class="inline-block">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action" value="demote">
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-bold text-sm transition" onclick="return confirm('Hạ cấp người dùng này?');">
                          <i class="fas fa-arrow-down"></i> Hạ cấp
                        </button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                  <p>Chưa có người dùng nào</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
  </style>
</body>
</html>
  </div>
</body>
</html>
