<?php
require 'config.php';

// Chỉ admin mới được vào
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'confirm_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Xác nhận đơn hàng: chuyển từ pending sang processing
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'processing' WHERE id = ? AND order_status = 'pending'");
        $stmt->bind_param('i', $order_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $msg = "✅ Đã xác nhận đơn hàng thành công!";
            } else {
                $error = "❌ Không thể xác nhận đơn hàng này!";
            }
        } else {
            $error = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if ($action === 'ship_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Chuyển đơn sang đang giao: processing → shipped
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'shipped' WHERE id = ? AND order_status = 'processing'");
        $stmt->bind_param('i', $order_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $msg = "✅ Đơn hàng đã được chuyển cho đơn vị vận chuyển!";
            } else {
                $error = "❌ Không thể cập nhật trạng thái đơn hàng!";
            }
        } else {
            $error = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if ($action === 'deliver_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Xác nhận đã giao: shipped → delivered và cập nhật thanh toán thành 'paid'
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered', payment_status = 'paid', completed_at = NOW() WHERE id = ? AND order_status = 'shipped'");
        $stmt->bind_param('i', $order_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $msg = "✅ Đơn hàng đã được giao thành công!";
            } else {
                $error = "❌ Không thể cập nhật trạng thái đơn hàng!";
            }
        } else {
            $error = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if ($action === 'update_status') {
        $order_id = (int)$_POST['order_id'];
        $order_status = $_POST['order_status'];
        $payment_status = $_POST['payment_status'];
        
        $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        $stmt->bind_param('ssi', $order_status, $payment_status, $order_id);
        
        if ($stmt->execute()) {
            $msg = "✅ Đã cập nhật trạng thái đơn hàng thành công!";
        } else {
            $error = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if ($action === 'delete_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        $conn->begin_transaction();
        
        try {
            // Hoàn lại số lượng tồn kho trước khi xóa
            $restore_sql = "UPDATE products p 
                           JOIN order_items oi ON p.id = oi.product_id 
                           SET p.quantity = p.quantity + oi.quantity 
                           WHERE oi.order_id = ?";
            $restore_stmt = $conn->prepare($restore_sql);
            $restore_stmt->bind_param('i', $order_id);
            $restore_stmt->execute();
            $restore_stmt->close();
            
            // Xóa order_items
            $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $delete_items_stmt->bind_param('i', $order_id);
            $delete_items_stmt->execute();
            $delete_items_stmt->close();
            
            // Xóa order
            $delete_order_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $delete_order_stmt->bind_param('i', $order_id);
            $delete_order_stmt->execute();
            $delete_order_stmt->close();
            
            // Commit transaction
            $conn->commit();
            $msg = "✅ Đã xóa đơn hàng và hoàn trả số lượng vào kho thành công!";
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            $error = "❌ Lỗi khi xóa đơn hàng: " . $e->getMessage();
        }
    }
}

// Lấy thống kê
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
$processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;

// Lọc và tìm kiếm
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = &$search_term;
    $params[] = &$search_term;
    $params[] = &$search_term;
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where[] = "order_status = ?";
    $params[] = &$status_filter;
    $types .= 's';
}

if (!empty($payment_filter)) {
    $where[] = "payment_status = ?";
    $params[] = &$payment_filter;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn
$count_sql = "SELECT COUNT(*) as total FROM orders $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($types)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Lấy danh sách đơn hàng
$sql = "SELECT o.*, u.full_name as user_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where_clause 
        ORDER BY o.created_at DESC 
        LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();
} else {
    $orders = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .clickable-row {
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background-color: #f8fafc !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .clickable-row:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-blue-500 text-white shadow-xl sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold">Quản Lý Đơn Hàng</h1>
                </div>
                <nav class="flex items-center gap-3">
                    <a href="admin.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="admin_products.php" class="text-white hover:text-purple-200 transition px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">
                        <i class="fas fa-boxes"></i> Sản phẩm
                    </a>
                    <a href="admin_orders.php" class="bg-white bg-opacity-20 px-3 py-2 rounded-lg font-semibold">
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

    <div class="container mx-auto px-4 py-8">
        
        <!-- Thông báo -->
        <?php if ($msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $msg ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Tổng đơn hàng</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_orders) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Chờ xử lý</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= number_format($pending_orders) ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Đang xử lý</p>
                        <p class="text-2xl font-bold text-blue-600"><?= number_format($processing_orders) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-spinner text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Hoàn thành</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($completed_orders) ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Doanh thu</p>
                        <p class="text-xl font-bold text-purple-600"><?= number_format($total_revenue) ?>₫</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Mã đơn, tên, SĐT..."
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái đơn</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tất cả</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Đang giao</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Đã giao</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thanh toán</label>
                    <select name="payment" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tất cả</option>
                        <option value="pending" <?= $payment_filter === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                        <option value="paid" <?= $payment_filter === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                        <option value="failed" <?= $payment_filter === 'failed' ? 'selected' : '' ?>>Thất bại</option>
                    </select>
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                    <a href="admin_orders.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Danh sách đơn hàng -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list"></i> Danh sách Đơn hàng (<?= number_format($total_records) ?>)
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thanh toán</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($orders->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Chưa có đơn hàng nào</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <?php
                                // Màu cho trạng thái đơn hàng
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                
                                $status_labels = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'shipped' => 'Đang giao',
                                    'delivered' => 'Đã giao',
                                    'cancelled' => 'Đã hủy'
                                ];
                                
                                $payment_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'paid' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800'
                                ];
                                
                                $payment_labels = [
                                    'pending' => 'Chờ thanh toán',
                                    'paid' => 'Đã thanh toán',
                                    'failed' => 'Thất bại'
                                ];
                                ?>
                                <tr class="clickable-row cursor-pointer" onclick="viewOrder(<?= $order['id'] ?>)">
                                    <td class="px-6 py-4">
                                        <span class="text-purple-600 font-semibold">
                                            <?= htmlspecialchars($order['order_code']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <div class="text-gray-500"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-purple-600">
                                        <?= number_format($order['total_amount']) ?>₫
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full <?= $payment_colors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $payment_labels[$order['payment_status']] ?? $order['payment_status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full <?= $status_colors[$order['order_status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $status_labels[$order['order_status']] ?? $order['order_status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Hiển thị <?= min($offset + 1, $total_records) ?> - <?= min($offset + $limit, $total_records) ?> của <?= $total_records ?> đơn hàng
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&payment=<?= urlencode($payment_filter) ?>" 
                                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                                    <i class="fas fa-chevron-left"></i> Trước
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&payment=<?= urlencode($payment_filter) ?>" 
                                   class="px-4 py-2 <?= $i === $page ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> rounded transition">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&payment=<?= urlencode($payment_filter) ?>" 
                                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                                    Sau <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal xem chi tiết đơn hàng -->
    <div id="viewOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice"></i> Chi tiết Đơn hàng
                </h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="orderDetails" class="p-6">
                <!-- Nội dung chi tiết đơn hàng sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal cập nhật trạng thái -->
    <div id="editOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit"></i> Cập nhật Trạng thái
                </h3>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="edit_order_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái đơn hàng</label>
                    <select name="order_status" id="edit_order_status" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="pending">Chờ xử lý</option>
                        <option value="processing">Đang xử lý</option>
                        <option value="shipped">Đang giao hàng</option>
                        <option value="delivered">Đã giao hàng</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái thanh toán</label>
                    <select name="payment_status" id="edit_payment_status" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="pending">Chờ thanh toán</option>
                        <option value="paid">Đã thanh toán</option>
                        <option value="failed">Thất bại</option>
                    </select>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form xóa (ẩn) -->
    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete_order">
        <input type="hidden" name="order_id" id="delete_order_id">
    </form>

    <!-- Form xác nhận đơn hàng (ẩn) -->
    <form id="confirmForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="confirm_order">
        <input type="hidden" name="order_id" id="confirm_order_id">
    </form>

    <!-- Form chuyển sang đang giao (ẩn) -->
    <form id="shipForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="ship_order">
        <input type="hidden" name="order_id" id="ship_order_id">
    </form>

    <!-- Form xác nhận đã giao (ẩn) -->
    <form id="deliverForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="deliver_order">
        <input type="hidden" name="order_id" id="deliver_order_id">
    </form>

    <script>
        function viewOrder(orderId) {
            document.getElementById('viewOrderModal').classList.remove('hidden');
            document.getElementById('orderDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i></div>';
            
            // Load chi tiết đơn hàng qua AJAX
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order, data.items);
                    } else {
                        document.getElementById('orderDetails').innerHTML = '<div class="text-center text-red-600">Lỗi: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('orderDetails').innerHTML = '<div class="text-center text-red-600">Lỗi khi tải dữ liệu</div>';
                });
        }
        
        function displayOrderDetails(order, items) {
            const statusLabels = {
                'pending': 'Chờ xử lý',
                'processing': 'Đang xử lý',
                'shipped': 'Đang giao',
                'delivered': 'Đã giao',
                'cancelled': 'Đã hủy'
            };
            
            const paymentLabels = {
                'pending': 'Chờ thanh toán',
                'paid': 'Đã thanh toán',
                'failed': 'Thất bại',
                'cod': 'COD - Thanh toán khi nhận hàng'
            };
            
            let html = `
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Thông tin đơn hàng</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Mã đơn:</span> <span class="text-purple-600 font-bold">${order.order_code}</span></p>
                            <p><span class="font-medium">Ngày đặt:</span> ${new Date(order.created_at).toLocaleString('vi-VN')}</p>
                            <p><span class="font-medium">Trạng thái:</span> <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">${statusLabels[order.order_status] || order.order_status}</span></p>
                            <p><span class="font-medium">Thanh toán:</span> ${paymentLabels[order.payment_method] || order.payment_method}</p>
                            <p><span class="font-medium">Trạng thái TT:</span> <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">${paymentLabels[order.payment_status] || order.payment_status}</span></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Thông tin khách hàng</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Họ tên:</span> ${order.customer_name}</p>
                            <p><span class="font-medium">Email:</span> ${order.customer_email}</p>
                            <p><span class="font-medium">Số điện thoại:</span> ${order.customer_phone}</p>
                            <p><span class="font-medium">Địa chỉ:</span> ${order.shipping_address}</p>
                            ${order.note ? '<p><span class="font-medium">Ghi chú:</span> ' + order.note + '</p>' : ''}
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-bold text-gray-800 mb-3">Sản phẩm đã đặt</h4>
                    <table class="w-full border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm">Sản phẩm</th>
                                <th class="px-4 py-2 text-center text-sm">Số lượng</th>
                                <th class="px-4 py-2 text-right text-sm">Đơn giá</th>
                                <th class="px-4 py-2 text-right text-sm">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
            `;
            
            items.forEach(item => {
                html += `
                    <tr>
                        <td class="px-4 py-2 text-sm">${item.product_name}</td>
                        <td class="px-4 py-2 text-center text-sm">${item.quantity}</td>
                        <td class="px-4 py-2 text-right text-sm">${parseInt(item.product_price).toLocaleString('vi-VN')}₫</td>
                        <td class="px-4 py-2 text-right text-sm font-semibold">${parseInt(item.total_price).toLocaleString('vi-VN')}₫</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Tạm tính:</span>
                                <span>${parseInt(order.subtotal).toLocaleString('vi-VN')}₫</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>Phí vận chuyển:</span>
                                <span>${parseInt(order.shipping_fee).toLocaleString('vi-VN')}₫</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg border-t pt-2">
                                <span>Tổng cộng:</span>
                                <span class="text-purple-600">${parseInt(order.total_amount).toLocaleString('vi-VN')}₫</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${order.order_status === 'pending' ? `
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Đơn hàng chờ xác nhận</p>
                                    <p class="text-sm text-gray-600">Click nút bên cạnh để xác nhận và bắt đầu xử lý đơn hàng này</p>
                                </div>
                            </div>
                            <button onclick="closeViewModal(); confirmOrder(${order.id}, '${order.order_code}');" 
                                    class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-bold">
                                <i class="fas fa-check-circle"></i> Xác nhận đơn hàng
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                ${order.order_status === 'processing' ? `
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-box text-blue-600 text-2xl"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Đơn hàng đang xử lý</p>
                                    <p class="text-sm text-gray-600">Đơn hàng đã được xác nhận. Click nút bên cạnh khi đã giao cho đơn vị vận chuyển</p>
                                </div>
                            </div>
                            <button onclick="closeViewModal(); shipOrder(${order.id}, '${order.order_code}');" 
                                    class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-bold">
                                <i class="fas fa-shipping-fast"></i> Đã giao vận chuyển
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                ${order.order_status === 'shipped' ? `
                    <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-shipping-fast text-purple-600 text-2xl"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Đơn hàng đang giao</p>
                                    <p class="text-sm text-gray-600">Đơn hàng đang trên đường giao. Click nút bên cạnh khi khách hàng đã nhận được hàng</p>
                                </div>
                            </div>
                            <button onclick="closeViewModal(); deliverOrder(${order.id}, '${order.order_code}');" 
                                    class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-bold">
                                <i class="fas fa-check-circle"></i> Xác nhận đã giao
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                ${order.order_status === 'delivered' ? `
                    <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-center gap-3 py-2">
                            <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                            <div>
                                <p class="font-bold text-gray-800 text-lg">Đơn hàng đã hoàn thành</p>
                                <p class="text-sm text-gray-600">Đơn hàng đã được giao thành công đến khách hàng</p>
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                ${order.order_status === 'cancelled' ? `
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center justify-center gap-3 py-2">
                            <i class="fas fa-times-circle text-red-600 text-3xl"></i>
                            <div>
                                <p class="font-bold text-gray-800 text-lg">Đơn hàng đã bị hủy</p>
                                <p class="text-sm text-gray-600">Đơn hàng này đã được hủy bởi khách hàng hoặc admin</p>
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                <div class="mt-4 flex justify-end gap-2">
                    <button onclick="closeViewModal(); deleteOrder(${order.id}, '${order.order_code}');" 
                            class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition font-bold">
                        <i class="fas fa-trash"></i> Xóa đơn hàng
                    </button>
                </div>
            `;
            
            document.getElementById('orderDetails').innerHTML = html;
        }
        
        function closeViewModal() {
            document.getElementById('viewOrderModal').classList.add('hidden');
        }
        
        function editOrder(orderId, orderStatus, paymentStatus) {
            document.getElementById('edit_order_id').value = orderId;
            document.getElementById('edit_order_status').value = orderStatus;
            document.getElementById('edit_payment_status').value = paymentStatus;
            document.getElementById('editOrderModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editOrderModal').classList.add('hidden');
        }
        
        function deleteOrder(orderId, orderCode) {
            const confirmMessage = `Bạn có chắc muốn xóa đơn hàng "${orderCode}"?\n\n` +
                                 `⚠️ Lưu ý:\n` +
                                 `• Thao tác này không thể hoàn tác!\n\n` +
                                 `Nhấn OK để xác nhận xóa đơn hàng.`;
            
            if (confirm(confirmMessage)) {
                document.getElementById('delete_order_id').value = orderId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        function confirmOrder(orderId, orderCode) {
            if (confirm('Xác nhận đơn hàng "' + orderCode + '"?\n\nĐơn hàng sẽ chuyển sang trạng thái "Đang xử lý".')) {
                document.getElementById('confirm_order_id').value = orderId;
                document.getElementById('confirmForm').submit();
            }
        }
        
        function shipOrder(orderId, orderCode) {
            if (confirm('Xác nhận đơn hàng "' + orderCode + '" đã giao cho đơn vị vận chuyển?\n\nĐơn hàng sẽ chuyển sang trạng thái "Đang giao".')) {
                document.getElementById('ship_order_id').value = orderId;
                document.getElementById('shipForm').submit();
            }
        }
        
        function deliverOrder(orderId, orderCode) {
            if (confirm('Xác nhận đơn hàng "' + orderCode + '" đã giao thành công?\n\nĐơn hàng sẽ chuyển sang trạng thái "Đã giao".')) {
                document.getElementById('deliver_order_id').value = orderId;
                document.getElementById('deliverForm').submit();
            }
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('viewOrderModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });
        
        document.getElementById('editOrderModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>

    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-red-600">
                        <i class="fas fa-exclamation-triangle"></i> Hủy đơn hàng
                    </h3>
                    <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-700 mb-3">Bạn có chắc chắn muốn hủy đơn hàng này?</p>
                    <p class="text-sm text-purple-600 mb-3">
                        <i class="fas fa-info-circle"></i> 
                        Số lượng sản phẩm sẽ được hoàn trả vào kho tự động.
                    </p>
                    
                    <label class="block text-sm font-bold text-gray-700 mb-2">Lý do hủy đơn:</label>
                    <textarea id="cancelReason" rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                              placeholder="Nhập lý do hủy đơn hàng..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button onclick="confirmCancelOrder()" 
                            class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-times-circle"></i> Xác nhận hủy
                    </button>
                    <button onclick="closeCancelModal()" 
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cancelOrderId = null;

        function showCancelModal(orderId) {
            cancelOrderId = orderId;
            document.getElementById('cancelModal').classList.remove('hidden');
            document.getElementById('cancelReason').value = '';
            document.getElementById('cancelReason').focus();
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
            cancelOrderId = null;
        }

        function confirmCancelOrder() {
            if (!cancelOrderId) return;
            
            const reason = document.getElementById('cancelReason').value.trim();
            if (!reason) {
                alert('Vui lòng nhập lý do hủy đơn hàng');
                return;
            }
            
            // Disable button to prevent double click
            const confirmBtn = event.target;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            
            fetch('cancel_order_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: cancelOrderId,
                    cancel_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload(); // Reload page to show updated status
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Có lỗi xảy ra khi hủy đơn hàng');
            })
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-times-circle"></i> Xác nhận hủy';
                closeCancelModal();
            });
        }

        // Close modal when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) closeCancelModal();
        });
    </script>

</body>
</html>
