<?php
require 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// Xử lý duyệt/từ chối đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);
    
    if ($action === 'approve' && $review_id > 0) {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $review_id);
        if ($stmt->execute()) {
            $msg = '✅ Đã duyệt đánh giá thành công!';
        } else {
            $error = '❌ Có lỗi xảy ra khi duyệt đánh giá.';
        }
    }
    elseif ($action === 'reject' && $review_id > 0) {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $review_id);
        if ($stmt->execute()) {
            $msg = '✅ Đã từ chối đánh giá thành công!';
        } else {
            $error = '❌ Có lỗi xảy ra khi từ chối đánh giá.';
        }
    }
    elseif ($action === 'delete' && $review_id > 0) {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param('i', $review_id);
        if ($stmt->execute()) {
            $msg = '✅ Đã xóa đánh giá thành công!';
        } else {
            $error = '❌ Có lỗi xảy ra khi xóa đánh giá.';
        }
    }
}

// Lấy filter từ URL
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Xây dựng câu truy vấn
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR r.comment LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Đếm tổng số đánh giá theo trạng thái
$pending_count = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'approved'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'rejected'")->fetch_assoc()['count'];
$total_count = $pending_count + $approved_count + $rejected_count;

// Lấy danh sách đánh giá
$sql = "SELECT r.*, p.name as product_name, u.full_name as user_name, u.email as user_email 
        FROM reviews r 
        LEFT JOIN products p ON r.product_id = p.id 
        LEFT JOIN users u ON r.user_id = u.id 
        {$where_clause} 
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đánh giá - VLXD Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-blue-500 text-white shadow-xl sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold">Quản Lý Đánh Giá</h1>
                </div>
                <nav class="flex items-center gap-3">
                    <a href="admin.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="admin_products.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-boxes"></i> Sản phẩm
                    </a>
                    <a href="admin_orders.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-shopping-cart"></i> Đơn hàng
                    </a>
                    <a href="admin_suppliers.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-truck"></i> Nhà phân phối
                    </a>
                    <a href="index.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Thông báo -->
        <?php if ($msg): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-2 text-green-700">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-2 text-red-700">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Tổng đánh giá</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $total_count ?></p>
                    </div>
                    <i class="fas fa-star text-4xl text-blue-100"></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-yellow-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Chờ duyệt</p>
                        <p class="text-3xl font-bold text-yellow-600"><?= $pending_count ?></p>
                    </div>
                    <i class="fas fa-clock text-4xl text-yellow-100"></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Đã duyệt</p>
                        <p class="text-3xl font-bold text-green-600"><?= $approved_count ?></p>
                    </div>
                    <i class="fas fa-check text-4xl text-green-100"></i>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-red-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Từ chối</p>
                        <p class="text-3xl font-bold text-red-600"><?= $rejected_count ?></p>
                    </div>
                    <i class="fas fa-times text-4xl text-red-100"></i>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tìm kiếm</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Tìm theo tên sản phẩm, nội dung đánh giá, tên khách hàng..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    Lọc
                </button>
                <?php if ($search || $status_filter !== 'all'): ?>
                    <a href="admin_reviews.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Danh sách đánh giá -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-list mr-2"></i>Danh sách đánh giá
                </h2>
            </div>

            <?php if ($reviews->num_rows > 0): ?>
                <div class="divide-y divide-gray-200">
                    <?php while ($review = $reviews->fetch_assoc()): ?>
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($review['product_name']) ?></h3>
                                        <div class="flex items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ml-2 text-sm text-gray-600">(<?= $review['rating'] ?>/5)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-gray-700"><?= htmlspecialchars($review['comment']) ?></p>
                                    </div>
                                    
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($review['user_name']) ?></span>
                                        <span><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($review['user_email']) ?></span>
                                        <span><i class="fas fa-calendar mr-1"></i><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3 ml-6">
                                    <!-- Trạng thái -->
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                                            <i class="fas fa-clock mr-1"></i>Chờ duyệt
                                        </span>
                                    <?php elseif ($review['status'] === 'approved'): ?>
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                            <i class="fas fa-check mr-1"></i>Đã duyệt
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                            <i class="fas fa-times mr-1"></i>Từ chối
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Nút hành động -->
                                    <div class="flex gap-2">
                                        <?php if ($review['status'] !== 'approved'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white w-8 h-8 rounded shadow transition" title="Duyệt">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($review['status'] !== 'rejected'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded shadow transition" title="Từ chối">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                            <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white w-8 h-8 rounded shadow transition" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-star text-6xl mb-4 text-gray-300"></i>
                    <p class="text-xl mb-2">Không tìm thấy đánh giá nào</p>
                    <?php if ($search || $status_filter !== 'all'): ?>
                        <p class="text-sm">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
