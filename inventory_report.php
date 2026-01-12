<?php
require 'config.php';
header('Content-Type: text/html; charset=utf-8');

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Lấy filter từ URL
$filter = $_GET['filter'] ?? 'all';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Xây dựng WHERE clause
$where_conditions = ["p.STATUS = 'active'"];
$params = [];
$param_types = '';

if ($filter === 'low_stock') {
    $where_conditions[] = "p.quantity <= p.min_quantity AND p.min_quantity > 0 AND p.quantity > 0";
} elseif ($filter === 'out_of_stock') {
    $where_conditions[] = "p.quantity = 0";
} elseif ($filter === 'in_stock') {
    $where_conditions[] = "p.quantity > p.min_quantity";
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

if ($search) {
    $where_conditions[] = "(p.NAME LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy danh sách sản phẩm
$query = "
    SELECT p.*, c.NAME as category_name, s.NAME as supplier_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    WHERE $where_clause
    ORDER BY 
        CASE 
            WHEN p.quantity = 0 THEN 1
            WHEN p.quantity <= p.min_quantity AND p.min_quantity > 0 THEN 2
            ELSE 3
        END,
        p.quantity ASC,
        p.NAME ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh sách categories
$categories = $conn->query("SELECT * FROM categories ORDER BY NAME");

// Thống kê tổng quan
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN quantity <= min_quantity AND min_quantity > 0 AND quantity > 0 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN quantity > min_quantity OR min_quantity = 0 THEN 1 ELSE 0 END) as in_stock,
        SUM(quantity * cost_price) as total_value,
        SUM(quantity) as total_quantity
    FROM products 
    WHERE STATUS = 'active'
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Kho Hàng - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="admin.php" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-3">
                            <i class="fas fa-chart-bar"></i> Báo Cáo Kho Hàng
                        </h1>
                        <p class="text-purple-100 text-sm">Thống kê và quản lý tồn kho</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="inventory_management.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-warehouse"></i> Quản Lý Kho
                    </a>
                    <a href="admin.php" class="bg-purple-500 hover:bg-purple-600 px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-home"></i> Quản Trị
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Thống Kê Tổng Quan -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Tổng Sản Phẩm -->
                <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Tổng Sản Phẩm</p>
                            <p class="text-3xl font-bold text-blue-600 mt-1"><?= number_format($stats['total_products']) ?></p>
                            <p class="text-gray-400 text-xs mt-1">sản phẩm</p>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-full">
                            <i class="fas fa-boxes text-2xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Sắp Hết Hàng -->
                <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Sắp Hết Hàng</p>
                            <p class="text-3xl font-bold text-yellow-600 mt-1"><?= number_format($stats['low_stock']) ?></p>
                            <p class="text-gray-400 text-xs mt-1">sản phẩm</p>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded-full">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-600 status-badge"></i>
                        </div>
                    </div>
                </div>

                <!-- Hết Hàng -->
                <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Hết Hàng</p>
                            <p class="text-3xl font-bold text-red-600 mt-1"><?= number_format($stats['out_of_stock']) ?></p>
                            <p class="text-gray-400 text-xs mt-1">sản phẩm</p>
                        </div>
                        <div class="bg-red-100 p-4 rounded-full">
                            <i class="fas fa-times-circle text-2xl text-red-600 status-badge"></i>
                        </div>
                    </div>
                </div>

                <!-- Giá Trị Tồn Kho -->
                <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Giá Trị Tồn Kho</p>
                            <p class="text-2xl font-bold text-green-600 mt-1"><?= number_format($stats['total_value'], 0, ',', '.') ?>đ</p>
                            <p class="text-gray-400 text-xs mt-1">tổng giá trị</p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ Lọc và Tìm Kiếm -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                <div class="flex flex-wrap gap-3">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-filter text-purple-600"></i> Bộ Lọc
                    </h2>
                </div>
                
                <form method="GET" class="flex flex-wrap gap-3 items-center">
                    <!-- Filter buttons -->
                    <div class="flex gap-2">
                        <a href="?filter=all&category=<?= $category_filter ?>&search=<?= $search ?>" 
                           class="px-4 py-2 rounded-lg font-medium transition <?= $filter === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <i class="fas fa-list"></i> Tất cả
                        </a>
                        <a href="?filter=in_stock&category=<?= $category_filter ?>&search=<?= $search ?>" 
                           class="px-4 py-2 rounded-lg font-medium transition <?= $filter === 'in_stock' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <i class="fas fa-check-circle"></i> Còn hàng
                        </a>
                        <a href="?filter=low_stock&category=<?= $category_filter ?>&search=<?= $search ?>" 
                           class="px-4 py-2 rounded-lg font-medium transition <?= $filter === 'low_stock' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <i class="fas fa-exclamation-triangle"></i> Sắp hết
                        </a>
                        <a href="?filter=out_of_stock&category=<?= $category_filter ?>&search=<?= $search ?>" 
                           class="px-4 py-2 rounded-lg font-medium transition <?= $filter === 'out_of_stock' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <i class="fas fa-times-circle"></i> Hết hàng
                        </a>
                    </div>

                    <!-- Category filter -->
                    <select name="category" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tất cả danh mục</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['NAME']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Search -->
                    <div class="flex">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Tìm kiếm sản phẩm..." 
                               class="border border-gray-300 rounded-l-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 w-64">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-r-lg hover:bg-purple-700 transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <input type="hidden" name="filter" value="<?= $filter ?>">
                </form>
            </div>
        </div>

        <!-- Danh Sách Sản Phẩm -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-warehouse text-purple-600"></i> Chi Tiết Tồn Kho
                    <span class="text-sm font-normal text-gray-500 ml-2">
                        (<?= $products->num_rows ?> sản phẩm)
                    </span>
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                            <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                            <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Min</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vốn</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                $status_icon = '';
                                
                                if ($product['quantity'] == 0) {
                                    $status_class = 'bg-red-100 text-red-800 border-red-200';
                                    $status_text = 'Hết hàng';
                                    $status_icon = 'fas fa-times-circle';
                                } elseif ($product['quantity'] <= $product['min_quantity'] && $product['min_quantity'] > 0) {
                                    $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                    $status_text = 'Sắp hết';
                                    $status_icon = 'fas fa-exclamation-triangle';
                                } else {
                                    $status_class = 'bg-green-100 text-green-800 border-green-200';
                                    $status_text = 'Còn hàng';
                                    $status_icon = 'fas fa-check-circle';
                                }
                                
                                $total_value = $product['quantity'] * $product['cost_price'];
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border <?= $status_class ?>">
                                                    <i class="<?= $status_icon ?> mr-1"></i>
                                                    <?= $status_text ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['NAME']) ?></div>
                                                <div class="text-sm text-gray-500">SKU: <?= htmlspecialchars($product['sku']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-lg font-bold <?= $product['quantity'] == 0 ? 'text-red-600' : ($product['quantity'] <= $product['min_quantity'] && $product['min_quantity'] > 0 ? 'text-yellow-600' : 'text-green-600') ?>">
                                            <?= number_format($product['quantity']) ?>
                                        </span>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($product['unit'] ?? 'Cái') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        <?= number_format($product['min_quantity']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                        <?= number_format($product['cost_price'], 0, ',', '.') ?>đ
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-green-600">
                                        <?= number_format($total_value, 0, ',', '.') ?>đ
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500 text-lg">Không tìm thấy sản phẩm nào</p>
                                        <p class="text-gray-400 text-sm">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when category changes
        document.querySelector('select[name="category"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>