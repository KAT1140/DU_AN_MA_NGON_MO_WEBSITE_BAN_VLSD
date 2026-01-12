<?php
require 'config.php';
header('Content-Type: text/html; charset=utf-8');

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Xử lý thêm/sửa inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_inventory':
                $product_id = intval($_POST['product_id']);
                $quantity_change = intval($_POST['quantity_change']);
                $type = $_POST['type'];
                $note = trim($_POST['note']);
                $created_by = $_SESSION['user_id'];
                
                // Lấy số lượng hiện tại của sản phẩm
                $current_qty_result = $conn->query("SELECT quantity FROM products WHERE id = $product_id");
                if ($current_qty_result && $row = $current_qty_result->fetch_assoc()) {
                    $current_quantity = $row['quantity'];
                    
                    // Tính số lượng mới
                    if ($type === 'import' || $type === 'return') {
                        $new_quantity = $current_quantity + $quantity_change;
                    } else {
                        $new_quantity = $current_quantity - $quantity_change;
                        if ($new_quantity < 0) {
                            $error = "Không thể xuất nhiều hơn số lượng hiện có!";
                            break;
                        }
                    }
                    
                    // Thêm vào bảng inventory
                    $stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity_change, current_quantity, TYPE, note, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiissi", $product_id, $quantity_change, $new_quantity, $type, $note, $created_by);
                    
                    if ($stmt->execute()) {
                        // Cập nhật số lượng trong bảng products
                        $conn->query("UPDATE products SET quantity = $new_quantity WHERE id = $product_id");
                        $success = "Cập nhật inventory thành công!";
                    } else {
                        $error = "Lỗi: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Không tìm thấy sản phẩm!";
                }
                break;
        }
    }
}

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT p.*, c.NAME as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.NAME");

// Lấy lịch sử inventory với phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total_result = $conn->query("SELECT COUNT(*) as count FROM inventory");
$total_records = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $per_page);

$inventory_history = $conn->query("
    SELECT i.*, p.NAME as product_name, p.sku, u.full_name as created_by_name 
    FROM inventory i 
    LEFT JOIN products p ON i.product_id = p.id 
    LEFT JOIN users u ON i.created_by = u.id 
    ORDER BY i.created_at DESC 
    LIMIT $offset, $per_page
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Kho Hàng - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="admin.php" class="text-white hover:text-purple-200">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold">
                    <i class="fas fa-warehouse"></i> Quản Lý Kho Hàng
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <a href="inventory_report.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-chart-bar"></i> Báo Cáo
                </a>
                <a href="admin.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    <i class="fas fa-home"></i> Quản Trị
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Thông báo -->
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form thêm inventory -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-green-500"></i> Cập Nhật Kho Hàng
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_inventory">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sản phẩm</label>
                            <select name="product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Chọn sản phẩm</option>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <option value="<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['NAME']) ?> (<?= htmlspecialchars($product['sku']) ?>) - Tồn: <?= $product['quantity'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loại giao dịch</label>
                            <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="import">Nhập kho</option>
                                <option value="export">Xuất kho</option>
                                <option value="adjustment">Điều chỉnh</option>
                                <option value="return">Trả hàng</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng</label>
                            <input type="number" name="quantity_change" required min="1" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                            <textarea name="note" rows="3" 
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                      placeholder="Ghi chú về giao dịch này..."></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-purple-500 text-white py-2 px-4 rounded-lg hover:bg-purple-600 transition font-semibold">
                            <i class="fas fa-save"></i> Cập Nhật Kho
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lịch sử inventory -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-history text-blue-500"></i> Lịch Sử Kho Hàng
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thay đổi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người tạo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($inventory_history && $inventory_history->num_rows > 0): ?>
                                    <?php while ($record = $inventory_history->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($record['product_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($record['sku']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $type_colors = [
                                                    'import' => 'bg-green-100 text-green-800',
                                                    'export' => 'bg-red-100 text-red-800',
                                                    'adjustment' => 'bg-yellow-100 text-yellow-800',
                                                    'sold' => 'bg-blue-100 text-blue-800',
                                                    'return' => 'bg-purple-100 text-purple-800'
                                                ];
                                                $type_names = [
                                                    'import' => 'Nhập kho',
                                                    'export' => 'Xuất kho',
                                                    'adjustment' => 'Điều chỉnh',
                                                    'sold' => 'Đã bán',
                                                    'return' => 'Trả hàng'
                                                ];
                                                $record_type = $record['TYPE'] ?? '';
                                                $color = $type_colors[$record_type] ?? 'bg-gray-100 text-gray-800';
                                                $name = $type_names[$record_type] ?? $record_type;
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $color ?>">
                                                    <?= $name ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if (in_array($record_type, ['import', 'return'])): ?>
                                                    <span class="text-green-600 font-semibold">+<?= number_format($record['quantity_change']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-red-600 font-semibold">-<?= number_format($record['quantity_change']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= number_format($record['current_quantity']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($record['created_by_name'] ?? 'Hệ thống') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('d/m/Y H:i', strtotime($record['created_at'])) ?>
                                            </td>
                                        </tr>
                                        <?php if ($record['note']): ?>
                                            <tr class="bg-gray-50">
                                                <td colspan="6" class="px-6 py-2 text-sm text-gray-600">
                                                    <i class="fas fa-sticky-note text-yellow-500"></i> 
                                                    <strong>Ghi chú:</strong> <?= htmlspecialchars($record['note']) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Chưa có giao dịch nào
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex justify-center gap-2">
                                <?php for ($i = 1; $i <= $total_pages; $i++): 
                                    $active = $page == $i ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 border hover:border-purple-500';
                                ?>
                                    <a href="?page=<?= $i ?>" class="px-3 py-2 rounded <?= $active ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>