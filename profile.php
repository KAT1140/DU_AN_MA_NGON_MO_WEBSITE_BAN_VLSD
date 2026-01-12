<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy thông tin user từ database
$stmt = $conn->prepare("SELECT id, email, full_name, phone, address, role, avatar_url, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User không tồn tại!";
    exit();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name)) {
        $error = 'Họ tên không được để trống!';
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param('sssi', $full_name, $phone, $address, $user_id);
        if ($stmt->execute()) {
            $success = 'Cập nhật thông tin thành công!';
            // Cập nhật session
            $_SESSION['user_name'] = $full_name;
            // Lấy lại dữ liệu mới
            $stmt2 = $conn->prepare("SELECT id, email, full_name, phone, address, role, avatar_url, created_at FROM users WHERE id = ?");
            $stmt2->bind_param('i', $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $user = $result2->fetch_assoc();
            $stmt2->close();
        } else {
            $error = 'Lỗi cập nhật: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - VLXD PRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header Navigation -->
    <header class="bg-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 hover:opacity-80 transition">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-500 rounded-lg flex items-center justify-center">
                    <span class="text-white text-lg font-black">VL</span>
                </div>
                <h1 class="text-xl font-black text-gray-800">VLXD PRO</h1>
            </a>
            
            <nav class="flex gap-2">
                <a href="index.php" class="text-gray-700 hover:text-purple-600 transition font-bold">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="cart.php" class="text-gray-700 hover:text-purple-600 transition font-bold">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                </a>
                <a href="logout.php" class="text-red-600 hover:text-red-700 transition font-bold">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto p-6">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-black text-gray-800 mb-2">
                <i class="fas fa-user-circle text-purple-600"></i> Hồ sơ cá nhân
            </h1>
            <p class="text-gray-600">Quản lý thông tin và cài đặt tài khoản của bạn</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg flex items-center gap-3">
                <i class="fas fa-check-circle text-xl"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar: Avatar & Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md overflow-hidden sticky top-24">
                    <!-- Avatar Section -->
                    <div class="bg-gradient-to-r from-purple-600 to-blue-500 p-6 text-center">
                        <div class="w-24 h-24 mx-auto mb-4 relative">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" class="w-full h-full rounded-full border-4 border-white object-cover">
                            <?php else: ?>
                                <div class="w-full h-full rounded-full border-4 border-white bg-white flex items-center justify-center">
                                    <i class="fas fa-user text-4xl text-purple-600"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-white text-xl font-bold"><?= htmlspecialchars($user['full_name'] ?? 'Chưa cập nhật') ?></h2>
                        <p class="text-purple-100 text-sm mt-1">
                            <i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($user['email']) ?>
                        </p>
                    </div>

                    <!-- User Status -->
                    <div class="p-6 space-y-4 border-t">
                        <!-- Role -->
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase mb-1">Quyền hạn</p>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="inline-block bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-700 px-4 py-2 rounded-full font-bold text-sm">
                                    <i class="fas fa-crown mr-1"></i>Quản trị viên
                                </span>
                            <?php else: ?>
                                <span class="inline-block bg-gradient-to-r from-blue-100 to-blue-50 text-blue-700 px-4 py-2 rounded-full font-bold text-sm">
                                    <i class="fas fa-user mr-1"></i>Khách hàng
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Member Since -->
                        <div class="pt-4 border-t">
                            <p class="text-xs text-gray-500 font-bold uppercase mb-2">Thành viên từ</p>
                            <p class="text-gray-800 font-semibold"><?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                        </div>

                        <!-- Admin Links -->
                        <?php if ($user['role'] === 'admin'): ?>
                            <div class="pt-4 border-t space-y-2">
                                <p class="text-xs text-gray-500 font-bold uppercase">Quản lý</p>
                                <a href="admin.php" class="flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-users"></i> Người dùng
                                </a>
                                <a href="admin_products.php" class="flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-boxes"></i> Sản phẩm
                                </a>
                                <a href="admin_orders.php" class="flex items-center gap-2 bg-green-50 hover:bg-green-100 text-green-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-shopping-cart"></i> Đơn hàng
                                </a>
                                <a href="admin_suppliers.php" class="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-truck"></i> Nhà phân phối
                                </a>
                                <a href="inventory_management.php" class="flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                    <i class="fas fa-warehouse"></i> Kho hàng
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- User Links -->
                        <div class="pt-4 border-t space-y-2">
                            <p class="text-xs text-gray-500 font-bold uppercase">Tài khoản</p>
                            <a href="my_orders.php" class="flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
                            </a>
                            <a href="addresses.php" class="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg transition font-bold text-sm">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main: Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                        <h2 class="text-white font-bold text-lg flex items-center gap-2">
                            <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                        </h2>
                    </div>

                    <form method="POST" class="p-8 space-y-6">
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-envelope text-blue-500 mr-1"></i>Email
                            </label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Email không thể thay đổi</p>
                        </div>

                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-user text-purple-500 mr-1"></i>Họ tên *
                            </label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required 
                                   placeholder="Nhập họ tên của bạn"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-phone text-green-500 mr-1"></i>Số điện thoại
                            </label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                   placeholder="Ví dụ: 0123456789"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition">
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>Địa chỉ
                            </label>
                            <textarea name="address" rows="4" 
                                      placeholder="Nhập địa chỉ của bạn"
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition resize-none"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-purple-600 text-white py-4 rounded-lg font-bold text-lg transition shadow-lg">
                                <i class="fas fa-save mr-2"></i>Cập nhật thông tin
                            </button>
                            <a href="logout.php" class="flex-1 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white py-4 rounded-lg font-bold text-lg transition shadow-lg text-center flex items-center justify-center gap-2">
                                <i class="fas fa-sign-out-alt"></i>Đăng xuất
                            </a>
                        </div>
                    </form>
                </div>
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
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="mt-4">
                <a href="admin.php" class="block w-full text-center bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                    ⚙️ Quản trị hệ thống
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
