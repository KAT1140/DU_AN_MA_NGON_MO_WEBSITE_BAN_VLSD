<?php
require 'config.php';
header('Content-Type: text/html; charset=utf-8');

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: admin_products.php');
    exit;
}

// Lấy thông tin sản phẩm
$product_query = "
    SELECT p.*, c.name as category_name, s.name as supplier_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    WHERE p.id = ?
";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: admin_products.php');
    exit;
}

// Lấy lịch sử inventory của sản phẩm
$inventory_history = $conn->query("
    SELECT i.*, u.full_name as created_by_name 
    FROM inventory i 
    LEFT JOIN users u ON i.created_by = u.id 
    WHERE i.product_id = $product_id 
    ORDER BY i.created_at DESC 
    LIMIT 50
");

// Thống kê inventory của sản phẩm
$stats_query = "
    SELECT 
        SUM(CASE WHEN type IN ('import', 'return') THEN quantity_change ELSE 0 END) as total_import,
        SUM(CASE WHEN type IN ('export', 'sold') THEN quantity_change ELSE 0 END) as total_export,
        COUNT(*) as total_transactions
    FROM inventory 
    WHERE product_id = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $product_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Inventory - <?= htmlspecialchars($product['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="admin_products.php" class="text-white hover:text-purple-200">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold">
                    <i class="fas fa-box"></i> Chi Tiết Inventory
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <a href="inventory_management.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-warehouse"></i> Quản Lý Kho
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Thông tin sản phẩm -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($product['name']) ?></h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">SKU:</span>
                            <span class="font-semibold"><?= htmlspecialchars($product['sku']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Danh mục:</span>
                            <span class="font-semibold"><?= htmlspecialchars($product['category_name']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nhà cung cấp:</span>
                            <span class="font-semibold"><?= htmlspecialchars($product['supplier_name'] ?? 'Chưa có') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Đơn vị:</span>
                            <span class="font-semibold"><?= htmlspecialchars($product['unit']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Thông Tin Tồn Kho</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Tồn kho hiện tại</p>
                            <p class="text-2xl font-bold text-blue-600"><?= number_format($product['quantity']) ?></p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Tồn kho tối thiểu</p>
                            <p class="text-2xl font-bold text-yellow-600"><?= number_format($product['min_quantity']) ?></p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Giá vốn</p>
                            <p class="text-lg font-bold text-green-600"><?= number_format($product['cost_price'], 0, ',', '.') ?>đ</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Giá trị tồn kho</p>
                            <p class="text-lg font-bold text-purple-600"><?= number_format($product['quantity'] * $product['cost_price'], 0, ',', '.') ?>đ</p>
                        </div>
                    </div>
                    
                    <!-- Trạng thái -->
                    <div class="mt-4">
                        <?php
                        if ($product['quantity'] == 0) {
                            echo '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                                    <i class="fas fa-times-circle"></i> Hết hàng
                                  </span>';
                        } elseif ($product['quantity'] <= $product['min_quantity'] && $product['min_quantity'] > 0) {
                            echo '<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                                    <i class="fas fa-exclamation-triangle"></i> Sắp hết hàng
                                  </span>';
                        } else {
                            echo '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    <i class="fas fa-check-circle"></i> Còn hàng
                                  </span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Tổng nhập kho</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($stats['total_import'] ?? 0) ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-arrow-down text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Tổng xuất kho</p>
                        <p class="text-2xl font-bold text-red-600"><?= number_format($stats['total_export'] ?? 0) ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-arrow-up text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Tổng giao dịch</p>
                        <p class="text-2xl font-bold text-blue-600"><?= number_format($stats['total_transactions'] ?? 0) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lịch sử inventory -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-history text-blue-500"></i> Lịch Sử Giao Dịch
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thay đổi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho sau</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người thực hiện</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($inventory_history && $inventory_history->num_rows > 0): ?>
                            <?php while ($record = $inventory_history->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
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
                                        $color = $type_colors[$record['type']] ?? 'bg-gray-100 text-gray-800';
                                        $name = $type_names[$record['type']] ?? $record['type'];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $color ?>">
                                            <?= $name ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if (in_array($record['type'], ['import', 'return'])): ?>
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
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= htmlspecialchars($record['note'] ?? '') ?>
                                    </td>
                                </tr>
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
        </div>
    </div>
</body>
</html>
