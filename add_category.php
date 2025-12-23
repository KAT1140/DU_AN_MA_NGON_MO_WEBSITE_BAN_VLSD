<?php
require 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Xử lý thêm/sửa/xóa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Tên danh mục không được để trống';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $description);
            if ($stmt->execute()) {
                $message = 'Thêm danh mục thành công!';
            } else {
                $error = 'Có lỗi xảy ra: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Tên danh mục không được để trống';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $description, $id);
            if ($stmt->execute()) {
                $message = 'Cập nhật danh mục thành công!';
            } else {
                $error = 'Có lỗi xảy ra: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Kiểm tra xem có sản phẩm nào thuộc danh mục này không
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] > 0) {
            $error = 'Không thể xóa danh mục này vì còn ' . $result['count'] . ' sản phẩm!';
        } else {
            if ($conn->query("DELETE FROM categories WHERE id = $id")) {
                $message = 'Xóa danh mục thành công!';
            } else {
                $error = 'Có lỗi xảy ra: ' . $conn->error;
            }
        }
    }
}

// Lấy danh sách tất cả danh mục
$categories = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                            FROM categories c 
                            LEFT JOIN products p ON p.category_id = c.id 
                            GROUP BY c.id 
                            ORDER BY c.name ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-3">
                        <img src="uploads/logo.png" alt="Logo" class="w-14 h-14 object-cover rounded-full">
                        <h1 class="text-3xl font-black">VLXD KAT</h1>
                    </a>
                </div>
                <nav class="flex items-center gap-6">
                    <a href="admin.php" class="hover:text-orange-200"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="admin_products.php" class="hover:text-orange-200"><i class="fas fa-box"></i> Sản phẩm</a>
                    <a href="admin_orders.php" class="hover:text-orange-200"><i class="fas fa-shopping-bag"></i> Đơn hàng</a>
                    <a href="logout.php" class="hover:text-orange-200"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-folder-open text-orange-600"></i> Quản Lý Danh Mục
            </h2>
            <button onclick="showAddModal()" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 font-semibold">
                <i class="fas fa-plus"></i> Thêm Danh Mục
            </button>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">ID</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Tên Danh Mục</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Mô Tả</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Số Sản Phẩm</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-600">#<?= $cat['id'] ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?= htmlspecialchars($cat['NAME'] ?? $cat['name'] ?? '') ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                        <?= $cat['product_count'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['NAME'] ?? $cat['name'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>')" 
                                            class="text-blue-600 hover:text-blue-800 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['NAME'] ?? $cat['name'] ?? '', ENT_QUOTES) ?>')" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-folder-open text-4xl mb-2"></i>
                                <p>Chưa có danh mục nào</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Thêm/Sửa Danh Mục -->
    <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
            <div class="p-6 border-b">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Thêm Danh Mục</h3>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId" value="0">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tên Danh Mục *</label>
                    <input type="text" name="name" id="categoryName" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Mô Tả</label>
                    <textarea name="description" id="categoryDescription" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 font-semibold">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 font-semibold">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form xóa ẩn -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm Danh Mục';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryId').value = '0';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryModal').classList.remove('hidden');
            document.getElementById('categoryModal').classList.add('flex');
        }

        function editCategory(id, name, description) {
            document.getElementById('modalTitle').textContent = 'Sửa Danh Mục';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryDescription').value = description;
            document.getElementById('categoryModal').classList.remove('hidden');
            document.getElementById('categoryModal').classList.add('flex');
        }

        function deleteCategory(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục "' + name + '"?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.add('hidden');
            document.getElementById('categoryModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('categoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
