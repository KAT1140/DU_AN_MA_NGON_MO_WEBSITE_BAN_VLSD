<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php?redirect=my_orders');
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'cancel_order') {
        $order_id = (int)$_POST['order_id'];
        
        // Kiểm tra đơn hàng có thuộc về user này không
        $check = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
        $check->bind_param('ii', $order_id, $user_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            // Cho phép hủy đơn đang chờ xử lý hoặc đang xử lý
            if (in_array($order['order_status'], ['pending', 'processing'])) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $order_id, $user_id);
                
                if ($stmt->execute()) {
                    $msg = "✅ Đã hủy đơn hàng thành công!";
                } else {
                    $error = "❌ Lỗi khi hủy đơn hàng: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "❌ Không thể hủy đơn hàng đã giao hoặc đang giao!";
            }
        } else {
            $error = "❌ Không tìm thấy đơn hàng!";
        }
        $check->close();
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lọc theo trạng thái
$status_filter = $_GET['status'] ?? '';
$where = "WHERE user_id = $user_id";
if (!empty($status_filter)) {
    $where .= " AND order_status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Đếm tổng số đơn
$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders $where")->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Lấy danh sách đơn hàng
$orders_sql = "SELECT * FROM orders $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$orders = $conn->query($orders_sql);

// Thống kê
$stats = [
    'all' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND order_status = 'pending'")->fetch_assoc()['count'],
    'processing' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND order_status = 'processing'")->fetch_assoc()['count'],
    'shipped' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND order_status = 'shipped'")->fetch_assoc()['count'],
    'delivered' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND order_status = 'delivered'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND order_status = 'cancelled'")->fetch_assoc()['count'],
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <nav class="bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-2xl font-bold">
                        <i class="fas fa-tools"></i> VLXD KAT
                    </a>
                    <span class="text-sm opacity-80">Đơn hàng của tôi</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="index.php" class="px-4 py-2 rounded hover:bg-orange-700 transition">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="cart.php" class="px-4 py-2 rounded hover:bg-orange-700 transition">
                        <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    </a>
                    <a href="profile.php" class="px-4 py-2 rounded hover:bg-orange-700 transition">
                        <i class="fas fa-user"></i> Tài khoản
                    </a>
                </div>
            </div>
        </div>
    </nav>

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
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
            </h1>
            <p class="text-gray-600">Theo dõi và quản lý đơn hàng của bạn</p>
        </div>

        <!-- Bộ lọc trạng thái -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <a href="my_orders.php" 
                   class="px-4 py-2 rounded-lg <?= empty($status_filter) ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-list"></i> Tất cả (<?= $stats['all'] ?>)
                </a>
                <a href="?status=pending" 
                   class="px-4 py-2 rounded-lg <?= $status_filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-clock"></i> Chờ xử lý (<?= $stats['pending'] ?>)
                </a>
                <a href="?status=processing" 
                   class="px-4 py-2 rounded-lg <?= $status_filter === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-spinner"></i> Đang xử lý (<?= $stats['processing'] ?>)
                </a>
                <a href="?status=shipped" 
                   class="px-4 py-2 rounded-lg <?= $status_filter === 'shipped' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-shipping-fast"></i> Đang giao (<?= $stats['shipped'] ?>)
                </a>
                <a href="?status=delivered" 
                   class="px-4 py-2 rounded-lg <?= $status_filter === 'delivered' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-check-circle"></i> Đã giao (<?= $stats['delivered'] ?>)
                </a>
                <a href="?status=cancelled" 
                   class="px-4 py-2 rounded-lg <?= $status_filter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                    <i class="fas fa-times-circle"></i> Đã hủy (<?= $stats['cancelled'] ?>)
                </a>
            </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <?php if ($orders->num_rows === 0): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Chưa có đơn hàng nào</h3>
                <p class="text-gray-500 mb-6">Hãy mua sắm ngay để trải nghiệm dịch vụ của chúng tôi!</p>
                <a href="products.php" class="inline-block bg-orange-600 text-white px-8 py-3 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($order = $orders->fetch_assoc()): 
                    // Màu sắc cho trạng thái
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'processing' => 'bg-blue-100 text-blue-800 border-blue-300',
                        'shipped' => 'bg-purple-100 text-purple-800 border-purple-300',
                        'delivered' => 'bg-green-100 text-green-800 border-green-300',
                        'cancelled' => 'bg-red-100 text-red-800 border-red-300'
                    ];
                    
                    $status_labels = [
                        'pending' => 'Chờ xử lý',
                        'processing' => 'Đang xử lý',
                        'shipped' => 'Đang giao hàng',
                        'delivered' => 'Đã giao hàng',
                        'cancelled' => 'Đã hủy'
                    ];
                    
                    $status_icons = [
                        'pending' => 'fa-clock',
                        'processing' => 'fa-spinner',
                        'shipped' => 'fa-shipping-fast',
                        'delivered' => 'fa-check-circle',
                        'cancelled' => 'fa-times-circle'
                    ];
                    
                    // Lấy sản phẩm trong đơn hàng
                    $items_sql = "SELECT oi.*, p.images FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = " . $order['id'];
                    $items = $conn->query($items_sql);
                ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                        <!-- Header đơn hàng -->
                        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                            <div class="flex items-center gap-6">
                                <div>
                                    <span class="text-xs text-gray-500">Mã đơn hàng</span>
                                    <p class="font-bold text-orange-600"><?= htmlspecialchars($order['order_code']) ?></p>
                                </div>
                                <div class="hidden md:block">
                                    <span class="text-xs text-gray-500">Ngày đặt</span>
                                    <p class="font-medium"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="hidden md:block">
                                    <span class="text-xs text-gray-500">Tổng tiền</span>
                                    <p class="font-bold text-orange-600"><?= number_format($order['total_amount']) ?>₫</p>
                                </div>
                            </div>
                            <div>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold border <?= $status_colors[$order['order_status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <i class="fas <?= $status_icons[$order['order_status']] ?? 'fa-question' ?>"></i>
                                    <?= $status_labels[$order['order_status']] ?? $order['order_status'] ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Sản phẩm -->
                        <div class="p-6">
                            <div class="space-y-3">
                                <?php while ($item = $items->fetch_assoc()): 
                                    $images = !empty($item['images']) ? explode(',', $item['images']) : [];
                                    $first_image = !empty($images) ? 'uploads/' . trim($images[0]) : 'https://via.placeholder.com/80';
                                ?>
                                    <div class="flex items-center gap-4 pb-3 border-b last:border-b-0">
                                        <img src="<?= $first_image ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                             class="w-20 h-20 object-cover rounded-lg">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-800"><?= htmlspecialchars($item['product_name']) ?></h4>
                                            <p class="text-sm text-gray-500">Số lượng: <?= $item['quantity'] ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-orange-600"><?= number_format($item['total_price']) ?>₫</p>
                                            <p class="text-xs text-gray-500"><?= number_format($item['product_price']) ?>₫ x <?= $item['quantity'] ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <!-- Footer: Thông tin giao hàng và actions -->
                        <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                                <span class="font-medium">Giao đến:</span> <?= htmlspecialchars($order['shipping_address']) ?>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="viewOrderDetail(<?= $order['id'] ?>)" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </button>
                                <?php if ($order['order_status'] === 'delivered'): ?>
                                    <?php
                                    // Kiểm tra xem đã đánh giá chưa
                                    $check_review = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE order_id = " . $order['id']);
                                    $has_review = $check_review->fetch_assoc()['count'] > 0;
                                    ?>
                                    <?php if (!$has_review): ?>
                                        <button onclick="openReviewModal(<?= $order['id'] ?>)" 
                                                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                                            <i class="fas fa-star"></i> Đánh giá
                                        </button>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                                            <i class="fas fa-check-circle"></i> Đã đánh giá
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (in_array($order['order_status'], ['pending', 'processing'])): ?>
                                    <button onclick="cancelOrder(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_code']) ?>')"
                                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                        <i class="fas fa-times"></i> Hủy đơn
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>" 
                           class="px-4 py-2 <?= $i === $page ? 'bg-orange-600 text-white' : 'bg-white border hover:bg-gray-50' ?> rounded-lg transition">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal chi tiết đơn hàng -->
    <div id="orderDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice"></i> Chi tiết Đơn hàng
                </h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="orderDetailContent" class="p-6">
                <!-- Nội dung sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal đánh giá -->
    <div id="reviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-star text-yellow-500"></i> Đánh giá đơn hàng
                </h3>
                <button onclick="closeReviewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="reviewContent" class="p-6">
                <!-- Nội dung sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>

    <!-- Form hủy đơn (ẩn) -->
    <form id="cancelForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="cancel_order">
        <input type="hidden" name="order_id" id="cancel_order_id">
    </form>

    <script>
        function viewOrderDetail(orderId) {
            document.getElementById('orderDetailModal').classList.remove('hidden');
            document.getElementById('orderDetailContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-orange-600"></i></div>';
            
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetail(data.order, data.items);
                    } else {
                        document.getElementById('orderDetailContent').innerHTML = '<div class="text-center text-red-600">Lỗi: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('orderDetailContent').innerHTML = '<div class="text-center text-red-600">Lỗi khi tải dữ liệu</div>';
                });
        }
        
        function displayOrderDetail(order, items) {
            const statusLabels = {
                'pending': 'Chờ xử lý',
                'processing': 'Đang xử lý',
                'shipped': 'Đang giao hàng',
                'delivered': 'Đã giao hàng',
                'cancelled': 'Đã hủy'
            };
            
            const paymentLabels = {
                'pending': 'Chờ thanh toán',
                'paid': 'Đã thanh toán',
                'failed': 'Thất bại',
                'cod': 'COD - Thanh toán khi nhận hàng'
            };
            
            let html = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Thông tin đơn hàng</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Mã đơn:</span> <span class="text-orange-600 font-bold">${order.order_code}</span></p>
                            <p><span class="font-medium">Ngày đặt:</span> ${new Date(order.created_at).toLocaleString('vi-VN')}</p>
                            <p><span class="font-medium">Trạng thái:</span> <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">${statusLabels[order.order_status] || order.order_status}</span></p>
                            <p><span class="font-medium">Thanh toán:</span> ${paymentLabels[order.payment_method] || order.payment_method}</p>
                            <p><span class="font-medium">Trạng thái TT:</span> <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">${paymentLabels[order.payment_status] || order.payment_status}</span></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3">Thông tin giao hàng</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Người nhận:</span> ${order.customer_name}</p>
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
                                <span class="text-orange-600">${parseInt(order.total_amount).toLocaleString('vi-VN')}₫</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('orderDetailContent').innerHTML = html;
        }
        
        function closeModal() {
            document.getElementById('orderDetailModal').classList.add('hidden');
        }
        
        function openReviewModal(orderId) {
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-orange-600"></i></div>';
            
            // Load sản phẩm trong đơn hàng
            fetch('get_order_items_for_review.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayReviewForm(orderId, data.items);
                    } else {
                        document.getElementById('reviewContent').innerHTML = '<div class="text-center text-red-600">Lỗi: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('reviewContent').innerHTML = '<div class="text-center text-red-600">Lỗi khi tải dữ liệu</div>';
                });
        }
        
        function displayReviewForm(orderId, items) {
            let html = '<form id="reviewForm" method="POST" action="submit_review.php">';
            html += '<input type="hidden" name="order_id" value="' + orderId + '">';
            html += '<div class="space-y-6">';
            
            items.forEach(item => {
                html += `
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex gap-4 mb-4">
                            <img src="${item.image}" alt="${item.product_name}" class="w-20 h-20 object-cover rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800">${item.product_name}</h4>
                                <p class="text-sm text-gray-500">Số lượng: ${item.quantity}</p>
                            </div>
                        </div>
                        
                        <input type="hidden" name="product_ids[]" value="${item.product_id}">
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Đánh giá sao</label>
                            <div class="flex gap-2" id="stars_${item.product_id}">
                                ${[1,2,3,4,5].map(star => `
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating_${item.product_id}" value="${star}" required class="hidden" 
                                               onchange="updateStars(${item.product_id}, ${star})">
                                        <i class="fas fa-star text-3xl text-gray-300 hover:text-yellow-400 transition star-icon"></i>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nhận xét của bạn</label>
                            <textarea name="comment_${item.product_id}" rows="3" 
                                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                                      placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            html += `
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="closeReviewModal()" 
                            class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        <i class="fas fa-paper-plane"></i> Gửi đánh giá
                    </button>
                </div>
            `;
            html += '</form>';
            
            document.getElementById('reviewContent').innerHTML = html;
        }
        
        function updateStars(productId, rating) {
            const container = document.getElementById('stars_' + productId);
            const stars = container.querySelectorAll('.star-icon');
            
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-500');
                } else {
                    star.classList.remove('text-yellow-500');
                    star.classList.add('text-gray-300');
                }
            });
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
        }
        
        function cancelOrder(orderId, orderCode) {
            if (confirm('Bạn có chắc muốn hủy đơn hàng "' + orderCode + '"?')) {
                document.getElementById('cancel_order_id').value = orderId;
                document.getElementById('cancelForm').submit();
            }
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('orderDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeReviewModal();
        });
    </script>

</body>
</html>
