<?php
require 'config.php';

// Chỉ admin mới được vào
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_supplier') {
        $name = trim($_POST['name']);
        $contact_person = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $status = intval($_POST['status']);

        if (empty($name)) {
            $error = "❌ Tên nhà phân phối không được để trống!";
        } else {
            $stmt = $conn->prepare("INSERT INTO suppliers (NAME, contact_person, phone, email, address, STATUS) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssi', $name, $contact_person, $phone, $email, $address, $status);
            
            if ($stmt->execute()) {
                $msg = "✅ Thêm nhà phân phối thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'edit_supplier') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $contact_person = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $status = intval($_POST['status']);

        if (empty($name)) {
            $error = "❌ Tên nhà phân phối không được để trống!";
        } else {
            $stmt = $conn->prepare("UPDATE suppliers SET NAME = ?, contact_person = ?, phone = ?, email = ?, address = ?, STATUS = ? WHERE id = ?");
            $stmt->bind_param('sssssii', $name, $contact_person, $phone, $email, $address, $status, $id);
            
            if ($stmt->execute()) {
                $msg = "✅ Cập nhật nhà phân phối thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_supplier') {
        $id = intval($_POST['id']);
        
        // Kiểm tra xem có sản phẩm nào đang sử dụng nhà phân phối này không
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE supplier_id = $id");
        $product_count = $check->fetch_assoc()['count'];
        
        if ($product_count > 0) {
            $error = "❌ Không thể xóa! Có $product_count sản phẩm đang sử dụng nhà phân phối này.";
        } else {
            $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $msg = "✅ Xóa nhà phân phối thành công!";
            } else {
                $error = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Lấy danh sách nhà phân phối
$suppliers = $conn->query("
    SELECT s.*, 
           COUNT(p.id) as product_count 
    FROM suppliers s 
    LEFT JOIN products p ON s.id = p.supplier_id 
    GROUP BY s.id 
    ORDER BY s.NAME ASC
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Nhà Phân Phối - VLXD KAT</title>
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
                        <i class="fas fa-truck text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold">Quản Lý Nhà Phân Phối</h1>
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
                    <a href="admin_suppliers.php" class="bg-white bg-opacity-20 px-3 py-2 rounded-lg font-semibold">
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
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Danh sách nhà phân phối (bên trái, rộng hơn) -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list text-blue-500"></i> Danh Sách Nhà Phân Phối
                            <span class="text-sm font-normal text-gray-500 ml-2">(<?= $suppliers ? $suppliers->num_rows : 0 ?> nhà phân phối)</span>
                        </h2>
                        <button onclick="resetForm()" class="lg:hidden bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition">
                            <i class="fas fa-plus"></i> Thêm mới
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nhà phân phối</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Liên hệ</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Sản phẩm</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($suppliers && $suppliers->num_rows > 0): ?>
                                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                        <tr class="hover:bg-purple-50 cursor-pointer transition-colors duration-150" onclick="editSupplier(<?= htmlspecialchars(json_encode($supplier)) ?>)">
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900 hover:text-purple-600 flex items-center gap-2">
                                                        <i class="fas fa-truck text-purple-500"></i>
                                                        <?= htmlspecialchars($supplier['NAME']) ?>
                                                    </div>
                                                    <?php if ($supplier['contact_person']): ?>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <i class="fas fa-user text-gray-400"></i> <?= htmlspecialchars($supplier['contact_person']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm space-y-1">
                                                    <?php if ($supplier['phone']): ?>
                                                        <div class="text-gray-700">
                                                            <i class="fas fa-phone text-green-500 w-4"></i> 
                                                            <span class="font-medium"><?= htmlspecialchars($supplier['phone']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier['email']): ?>
                                                        <div class="text-gray-600">
                                                            <i class="fas fa-envelope text-blue-500 w-4"></i> 
                                                            <?= htmlspecialchars($supplier['email']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier['address']): ?>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <i class="fas fa-map-marker-alt text-red-500 w-4"></i> 
                                                            <?= htmlspecialchars(mb_substr($supplier['address'], 0, 30)) ?><?= mb_strlen($supplier['address']) > 30 ? '...' : '' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-3 py-1 text-sm font-bold rounded-full <?= $supplier['product_count'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' ?>">
                                                    <i class="fas fa-boxes"></i> <?= $supplier['product_count'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php if ($supplier['STATUS']): ?>
                                                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle"></i> Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                                        <i class="fas fa-pause-circle"></i> Tạm dừng
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-gray-500">Chưa có nhà phân phối nào</p>
                                            <button onclick="resetForm()" class="mt-3 text-purple-600 hover:text-purple-800 font-medium">
                                                <i class="fas fa-plus-circle"></i> Thêm nhà phân phối đầu tiên
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Form thêm/sửa nhà phân phối (bên phải, cố định) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 lg:sticky lg:top-4">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-green-500"></i> 
                        <span>Thêm Nhà Phân Phối</span>
                    </h2>
                    
                    <form method="POST" id="supplierForm" class="space-y-3">
                        <input type="hidden" name="action" value="add_supplier">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Tên nhà phân phối <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="supplierName" required 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   placeholder="VD: Công ty VLXD ABC">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Người liên hệ</label>
                            <input type="text" name="contact_person" id="contactPerson" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   placeholder="Nguyễn Văn A">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Số điện thoại</label>
                            <input type="tel" name="phone" id="supplierPhone" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   placeholder="0901234567">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" id="supplierEmail" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   placeholder="email@example.com">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Địa chỉ</label>
                            <textarea name="address" id="supplierAddress" rows="2" 
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                      placeholder="Địa chỉ đầy đủ"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Trạng thái</label>
                            <select name="status" id="supplierStatus" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="1">✓ Hoạt động</option>
                                <option value="0">✗ Tạm dừng</option>
                            </select>
                        </div>
                        
                        <div class="pt-4 border-t space-y-2">
                            <button type="submit" class="w-full bg-purple-500 text-white py-2.5 px-4 rounded-lg hover:bg-purple-600 transition font-bold text-sm">
                                <i class="fas fa-save"></i> Thêm Nhà Phân Phối
                            </button>
                            <button type="button" onclick="resetForm()" class="w-full bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition text-sm font-semibold">
                                <i class="fas fa-redo"></i> Làm mới
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 pt-4 border-t space-y-2">
                        <a href="admin_products.php" class="w-full bg-white border-2 border-purple-500 text-purple-600 py-2.5 px-4 rounded-lg font-bold text-sm hover:bg-purple-50 transition flex items-center justify-center gap-2">
                            <i class="fas fa-boxes"></i> Sản Phẩm
                        </a>
                        <a href="admin.php" class="w-full bg-blue-500 text-white py-2.5 px-4 rounded-lg font-bold text-sm hover:bg-blue-600 transition flex items-center justify-center gap-2">
                            <i class="fas fa-cogs"></i> Quản trị hệ thống
                        </a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal sửa nhà phân phối -->
    <div id="editSupplierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-blue-500 to-purple-500 text-white p-6 rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-bold">
                        <i class="fas fa-edit"></i> Sửa Nhà Phân Phối
                    </h3>
                    <button onclick="closeEditModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" id="editSupplierForm" class="p-6">
                <input type="hidden" name="action" value="edit_supplier">
                <input type="hidden" name="id" id="editSupplierId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Tên nhà phân phối <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="editSupplierName" required 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Người liên hệ</label>
                        <input type="text" name="contact_person" id="editContactPerson" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Số điện thoại</label>
                        <input type="tel" name="phone" id="editSupplierPhone" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="editSupplierEmail" 
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Địa chỉ</label>
                        <textarea name="address" id="editSupplierAddress" rows="3" 
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" id="editSupplierStatus" 
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1">✓ Hoạt động</option>
                            <option value="0">✗ Tạm dừng</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-3 px-6 rounded-lg hover:bg-blue-600 transition font-bold">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition font-bold">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="button" onclick="deleteSupplierFromModal()" class="bg-red-500 text-white py-3 px-6 rounded-lg hover:bg-red-600 transition font-bold">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editSupplier(supplier) {
            // Show modal and fill data
            document.getElementById('editSupplierModal').classList.remove('hidden');
            document.getElementById('editSupplierId').value = supplier.id;
            document.getElementById('editSupplierName').value = supplier.NAME;
            document.getElementById('editContactPerson').value = supplier.contact_person || '';
            document.getElementById('editSupplierPhone').value = supplier.phone || '';
            document.getElementById('editSupplierEmail').value = supplier.email || '';
            document.getElementById('editSupplierAddress').value = supplier.address || '';
            document.getElementById('editSupplierStatus').value = supplier.STATUS;
        }
        
        function closeEditModal() {
            document.getElementById('editSupplierModal').classList.add('hidden');
        }
        
        function deleteSupplierFromModal() {
            const supplierId = document.getElementById('editSupplierId').value;
            const supplierName = document.getElementById('editSupplierName').value;
            
            if (confirm('⚠️ Bạn có chắc muốn xóa nhà phân phối "' + supplierName + '"?\n\nLưu ý: Không thể xóa nếu đang có sản phẩm sử dụng nhà phân phối này.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" value="${supplierId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when click outside
        document.getElementById('editSupplierModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        
        function resetForm() {
            // Reset form in sidebar (for adding)
            document.getElementById('supplierForm').reset();
        }
        
        function deleteCurrentSupplier() {
            alert('Vui lòng chọn nhà phân phối từ danh sách để xóa!');
        }
        
        function deleteCurrentSupplier() {
            const supplierId = document.getElementById('supplierId').value;
            const supplierName = document.getElementById('supplierName').value;
            
            if (!supplierId) {
                alert('Vui lòng chọn nhà phân phối cần xóa!');
                return;
            }
            
            if (confirm('⚠️ Bạn có chắc muốn xóa nhà phân phối "' + supplierName + '"?\n\nLưu ý: Không thể xóa nếu đang có sản phẩm sử dụng nhà phân phối này.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" value="${supplierId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>