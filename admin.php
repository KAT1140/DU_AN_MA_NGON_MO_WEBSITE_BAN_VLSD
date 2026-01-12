<?php
require 'config.php';

// Chỉ admin mới được vào
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// --- XỬ LÝ POST REQUEST (Thêm, Sửa, Xóa, Nâng/Hạ quyền) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. XÓA NGƯỜI DÙNG
    if ($action === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        // Không cho phép tự xóa chính mình
        if ($user_id == $_SESSION['user_id']) {
            $error = "❌ Bạn không thể tự xóa tài khoản của mình!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $msg = "✅ Đã xóa người dùng thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // 2. THÊM NGƯỜI DÙNG MỚI
    elseif ($action === 'add_user') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];

        // Kiểm tra email trùng
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error = "❌ Email này đã tồn tại!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // --- SỬA ĐOẠN NÀY ---
            // Thêm cột username và gán giá trị email vào đó
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, PASSWORD, phone, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            // Bind params: username (lấy bằng email), full_name, email, password, phone, role
            $stmt->bind_param('ssssss', $email, $full_name, $email, $hashed_password, $phone, $role);
            // --------------------
            
            if ($stmt->execute()) {
                $msg = "✅ Thêm người dùng mới thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // 3. SỬA THÔNG TIN NGƯỜI DÙNG
    elseif ($action === 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $new_password = $_POST['password']; // Nếu rỗng thì không đổi pass

        // Câu lệnh SQL cơ bản
        $sql = "UPDATE users SET full_name = ?, phone = ?, role = ? WHERE id = ?";
        
        // Nếu có nhập mật khẩu mới thì cập nhật thêm
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, role = ?, PASSWORD = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $full_name, $phone, $role, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssi', $full_name, $phone, $role, $user_id);
        }

        if ($stmt->execute()) {
            $msg = "✅ Cập nhật thông tin thành công!";
        } else {
            $error = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }

    // 4. LOGIC CŨ: PROMOTE / DEMOTE (Nhanh)
    elseif (isset($_POST['user_id']) && ($action === 'promote' || $action === 'demote')) {
        $user_id = (int)$_POST['user_id'];
        $new_role = ($action === 'promote') ? 'admin' : 'customer';
        $conn->query("UPDATE users SET role = '$new_role' WHERE id = $user_id");
        $msg = "✅ Cập nhật quyền thành công!";
    }
}

// Lấy danh sách user
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$total_users = $users->num_rows;
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];

// Đếm đơn hàng chờ xử lý
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];

// Thống kê inventory
require_once 'inventory_functions.php';
$inventory_stats = getInventoryStats($conn);
$inventory_alerts = getInventoryAlerts($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Quản lý người dùng</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @keyframes pulse-slow {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    .animate-pulse-slow {
      animation: pulse-slow 2s infinite;
    }
    .quick-action-card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .quick-action-card:hover {
      transform: translateY(-4px) scale(1.02);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

  <header class="bg-gradient-to-r from-purple-600 to-blue-500 text-white sticky top-0 z-40 shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
          <i class="fas fa-users-cog text-2xl"></i>
        </div>
        <h1 class="text-2xl font-black">Quản lý người dùng</h1>
      </div>
      <nav class="flex gap-2">
        <a href="index.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-bold text-sm">
           <i class="fas fa-home"></i> Trang chủ
        </a>
        <a href="admin_products.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-bold text-sm">
           <i class="fas fa-boxes"></i> Sản phẩm
        </a>
        <a href="admin_orders.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-bold text-sm relative">
           <i class="fas fa-shopping-cart"></i> Đơn hàng
           <?php if ($pending_orders > 0): ?>
               <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                   <?= $pending_orders ?>
               </span>
           <?php endif; ?>
        </a>
        <a href="inventory_management.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-bold text-sm">
           <i class="fas fa-warehouse"></i> Kho hàng
        </a>
        <a href="admin_suppliers.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-bold text-sm">
           <i class="fas fa-truck"></i> Nhà phân phối
        </a>
      </nav>
    </div>
  </header>

  <div class="max-w-7xl mx-auto p-6">
    <!-- Cảnh báo inventory -->
    <?php if (!empty($inventory_alerts)): ?>
        <div class="mb-6 space-y-3">
            <?php foreach ($inventory_alerts as $alert): ?>
                <div class="bg-<?= $alert['type'] === 'danger' ? 'red' : 'yellow' ?>-100 border border-<?= $alert['type'] === 'danger' ? 'red' : 'yellow' ?>-400 text-<?= $alert['type'] === 'danger' ? 'red' : 'yellow' ?>-700 px-4 py-3 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-<?= $alert['type'] === 'danger' ? 'exclamation-circle' : 'exclamation-triangle' ?> text-xl"></i>
                        <div>
                            <strong><?= htmlspecialchars($alert['title']) ?></strong>
                            <p class="text-sm"><?= htmlspecialchars($alert['message']) ?></p>
                        </div>
                    </div>
                    <a href="inventory_report.php?filter=<?= $alert['type'] === 'danger' ? 'out_of_stock' : 'low_stock' ?>" 
                       class="bg-<?= $alert['type'] === 'danger' ? 'red' : 'yellow' ?>-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-<?= $alert['type'] === 'danger' ? 'red' : 'yellow' ?>-700 transition">
                        Xem chi tiết
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500 flex justify-between items-center">
         <div><p class="text-gray-500">Tổng người dùng</p><p class="text-3xl font-bold text-blue-600"><?= $total_users ?></p></div>
         <i class="fas fa-users text-4xl text-blue-100"></i>
      </div>
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500 flex justify-between items-center">
         <div><p class="text-gray-500">Quản trị viên</p><p class="text-3xl font-bold text-green-600"><?= $admin_count ?></p></div>
         <i class="fas fa-crown text-4xl text-green-100"></i>
      </div>
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500 flex justify-between items-center">
         <div><p class="text-gray-500">Khách hàng</p><p class="text-3xl font-bold text-purple-600"><?= $total_users - $admin_count ?></p></div>
         <i class="fas fa-shopping-bag text-4xl text-purple-100"></i>
      </div>
      <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500 flex justify-between items-center">
         <div><p class="text-gray-500">Tổng sản phẩm</p><p class="text-3xl font-bold text-purple-600"><?= number_format($inventory_stats['total_products']) ?></p></div>
         <i class="fas fa-boxes text-4xl text-purple-100"></i>
      </div>
    </div>

    <!-- Thống Kê Theo Danh Mục -->
    <div class="mb-8">
      <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold flex items-center gap-3">
            <i class="fas fa-chart-pie"></i> Thống Kê Theo Danh Mục
          </h2>
          <a href="inventory_report.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-semibold transition">
            <i class="fas fa-external-link-alt"></i> Xem Chi Tiết
          </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white bg-opacity-10 p-4 rounded-lg backdrop-blur-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-100 text-sm">Hết hàng</p>
                <p class="text-3xl font-bold"><?= number_format($inventory_stats['out_of_stock']) ?></p>
                <p class="text-purple-200 text-xs mt-1">sản phẩm</p>
              </div>
              <div class="bg-red-500 bg-opacity-80 p-3 rounded-full">
                <i class="fas fa-times-circle text-2xl"></i>
              </div>
            </div>
          </div>
          
          <div class="bg-white bg-opacity-10 p-4 rounded-lg backdrop-blur-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-100 text-sm">Sắp hết hàng</p>
                <p class="text-3xl font-bold"><?= number_format($inventory_stats['low_stock']) ?></p>
                <p class="text-purple-200 text-xs mt-1">sản phẩm</p>
              </div>
              <div class="bg-yellow-500 bg-opacity-80 p-3 rounded-full">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
              </div>
            </div>
          </div>
          
          <div class="bg-white bg-opacity-10 p-4 rounded-lg backdrop-blur-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-purple-100 text-sm">Giá trị tồn kho</p>
                <p class="text-2xl font-bold"><?= number_format($inventory_stats['total_value'], 0, ',', '.') ?>đ</p>
                <p class="text-purple-200 text-xs mt-1">tổng giá trị</p>
              </div>
              <div class="bg-green-500 bg-opacity-80 p-3 rounded-full">
                <i class="fas fa-dollar-sign text-2xl"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section Quản Lý Nhanh -->
    <div class="mb-8">
      <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-tachometer-alt text-blue-500"></i> Quản Lý Nhanh
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Xem Hàng Trong Kho -->
        <a href="inventory_report.php" class="quick-action-card bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white p-6 rounded-xl shadow-lg hover:shadow-xl group">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-lg mb-2">Hàng Trong Kho</h3>
              <p class="text-blue-100 text-sm">Xem tất cả sản phẩm và tồn kho</p>
              <div class="mt-3 flex items-center gap-2">
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">
                  <?= number_format($inventory_stats['total_products']) ?> sản phẩm
                </span>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-full group-hover:bg-opacity-30 transition-all">
              <i class="fas fa-boxes text-2xl"></i>
            </div>
          </div>
        </a>

        <!-- Quản Lý Kho -->
        <a href="inventory_management.php" class="quick-action-card bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white p-6 rounded-xl shadow-lg hover:shadow-xl group">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-lg mb-2">Quản Lý Kho</h3>
              <p class="text-green-100 text-sm">Nhập/xuất hàng và lịch sử</p>
              <div class="mt-3 flex items-center gap-2">
                <?php if ($inventory_stats['low_stock'] > 0): ?>
                  <span class="text-xs bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full animate-pulse-slow">
                    <?= $inventory_stats['low_stock'] ?> sắp hết
                  </span>
                <?php endif; ?>
                <?php if ($inventory_stats['out_of_stock'] > 0): ?>
                  <span class="text-xs bg-red-400 text-red-900 px-2 py-1 rounded-full animate-pulse-slow">
                    <?= $inventory_stats['out_of_stock'] ?> hết hàng
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-full group-hover:bg-opacity-30 transition-all">
              <i class="fas fa-warehouse text-2xl"></i>
            </div>
          </div>
        </a>

        <!-- Sản Phẩm -->
        <a href="admin_products.php" class="quick-action-card bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white p-6 rounded-xl shadow-lg hover:shadow-xl group">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-lg mb-2">Sản Phẩm</h3>
              <p class="text-purple-100 text-sm">Quản lý danh mục sản phẩm</p>
              <div class="mt-3">
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">
                  Quản lý catalog
                </span>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-full group-hover:bg-opacity-30 transition-all">
              <i class="fas fa-cube text-2xl"></i>
            </div>
          </div>
        </a>

        <!-- Nhà Phân Phối -->
        <a href="admin_suppliers.php" class="quick-action-card bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white p-6 rounded-xl shadow-lg hover:shadow-xl group">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-lg mb-2">Nhà Phân Phối</h3>
              <p class="text-indigo-100 text-sm">Quản lý nhà cung cấp</p>
              <div class="mt-3">
                <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">
                  Suppliers
                </span>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-full group-hover:bg-opacity-30 transition-all">
              <i class="fas fa-truck text-2xl"></i>
            </div>
          </div>
        </a>

        <!-- Đơn Hàng -->
        <a href="admin_orders.php" class="quick-action-card bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white p-6 rounded-xl shadow-lg hover:shadow-xl group relative">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-lg mb-2">Đơn Hàng</h3>
              <p class="text-purple-100 text-sm">Xử lý đơn hàng khách hàng</p>
              <div class="mt-3">
                <?php if ($pending_orders > 0): ?>
                  <span class="text-xs bg-red-400 text-red-900 px-2 py-1 rounded-full animate-pulse-slow">
                    <?= $pending_orders ?> chờ xử lý
                  </span>
                <?php else: ?>
                  <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">
                    Tất cả đã xử lý
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-full group-hover:bg-opacity-30 transition-all">
              <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
          </div>
          <?php if ($pending_orders > 0): ?>
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold animate-pulse">
              <?= $pending_orders ?>
            </span>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <?php if ($msg): ?><div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg border border-green-400"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg border border-red-400"><?= $error ?></div><?php endif; ?>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-gray-100 px-6 py-4 flex justify-between items-center border-b">
        <h2 class="font-bold text-lg text-gray-700"><i class="fas fa-list"></i> Danh sách tài khoản</h2>
        <button onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow transition">
          <i class="fas fa-plus"></i> Thêm người dùng
        </button>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-50 text-gray-700 uppercase font-bold">
            <tr>
              <th class="px-6 py-3">ID</th>
              <th class="px-6 py-3">Thông tin</th>
              <th class="px-6 py-3">Liên hệ</th>
              <th class="px-6 py-3 text-center">Vai trò</th>
              <th class="px-6 py-3 text-center">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php while ($u = $users->fetch_assoc()): 
                $is_me = ($u['id'] == $_SESSION['user_id']);
            ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 font-bold text-gray-500">#<?= $u['id'] ?></td>
              <td class="px-6 py-4">
                <p class="font-bold text-gray-800"><?= htmlspecialchars($u['full_name']) ?></p>
                <p class="text-xs text-gray-500">Đăng ký: <?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
              </td>
              <td class="px-6 py-4">
                <p><i class="fas fa-envelope text-gray-400 w-4"></i> <?= htmlspecialchars($u['email']) ?></p>
                <p><i class="fas fa-phone text-gray-400 w-4"></i> <?= htmlspecialchars($u['phone']) ?></p>
              </td>
              <td class="px-6 py-4 text-center">
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold text-xs border border-yellow-200">Admin</span>
                <?php else: ?>
                  <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-bold text-xs border border-gray-200">Khách</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 text-center space-x-2">
                <button onclick='openEditModal(<?= json_encode($u) ?>)' class="bg-blue-500 hover:bg-blue-600 text-white w-8 h-8 rounded shadow transition" title="Sửa">
                    <i class="fas fa-edit"></i>
                </button>
                
                <?php if (!$is_me): ?>
                <form method="POST" class="inline-block" onsubmit="return confirm('Bạn chắc chắn muốn xóa user này? Hành động này không thể hoàn tác!');">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded shadow transition" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <?php else: ?>
                    <span class="text-gray-400 text-xs italic">(Bạn)</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
        <div class="bg-green-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg">Thêm người dùng mới</h3>
            <button onclick="closeModal('addModal')" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add_user">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Họ tên</label>
                <input type="text" name="full_name" required class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" required minlength="6" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Vai trò</label>
                <select name="role" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <option value="customer">Khách hàng</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg shadow mt-2">Thêm ngay</button>
        </form>
    </div>
  </div>

  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg">Sửa thông tin</h3>
            <button onclick="closeModal('editModal')" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Email (Không thể sửa)</label>
                <input type="email" id="edit_email" disabled class="w-full border bg-gray-100 rounded-lg p-2 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Họ tên</label>
                <input type="text" name="full_name" id="edit_fullname" required class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" id="edit_phone" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Mật khẩu mới (Để trống nếu không đổi)</label>
                <input type="password" name="password" minlength="6" placeholder="Nhập để đổi mật khẩu..." class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Vai trò</label>
                <select name="role" id="edit_role" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="customer">Khách hàng</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg shadow mt-2">Cập nhật</button>
        </form>
    </div>
  </div>

  <script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_fullname').value = user.full_name;
        document.getElementById('edit_phone').value = user.phone;
        document.getElementById('edit_role').value = user.role;
        
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
  </script>
</body>
</html>