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
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="admin.php" class="text-white hover:text-purple-200">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-2xl font-bold">
                    <i class="fas fa-truck"></i> Quản Lý Nhà Phân Phối
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <a href="admin_products.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-boxes"></i> Sản Phẩm
                </a>
                <a href="admin.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    <i class="fas fa-home"></i> Quản Trị
                </a>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form thêm/sửa nhà phân phối -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-green-500"></i> Thêm Nhà Phân Phối
                    </h2>
                    
                    <form method="POST" id="supplierForm" class="space-y-4">
                        <input type="hidden" name="action" value="add_supplier" id="formAction">
                        <input type="hidden" name="id" value="" id="supplierId">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên nhà phân phối *</label>
                            <input type="text" name="name" id="supplierName" required 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Người liên hệ</label>
                            <input type="text" name="contact_person" id="contactPerson" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                            <input type="tel" name="phone" id="supplierPhone" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="supplierEmail" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ</label>
                            <textarea name="address" id="supplierAddress" rows="3" 
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                            <select name="status" id="supplierStatus" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="1">Hoạt động</option>
                                <option value="0">Tạm dừng</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" id="submitBtn" class="flex-1 bg-purple-500 text-white py-2 px-4 rounded-lg hover:bg-purple-600 transition font-semibold">
                                <i class="fas fa-save"></i> Thêm Nhà Phân Phối
                            </button>
                            <button type="button" onclick="resetForm()" class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danh sách nhà phân phối -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list text-blue-500"></i> Danh Sách Nhà Phân Phối
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhà phân phối</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Liên hệ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($suppliers && $suppliers->num_rows > 0): ?>
                                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="viewSupplierDetails(<?= htmlspecialchars(json_encode($supplier)) ?>)">
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 hover:text-purple-600"><?= htmlspecialchars($supplier['NAME']) ?></div>
                                                    <?php if ($supplier['contact_person']): ?>
                                                        <div class="text-sm text-gray-500">Liên hệ: <?= htmlspecialchars($supplier['contact_person']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php if ($supplier['phone']): ?>
                                                        <div><i class="fas fa-phone text-gray-400"></i> <?= htmlspecialchars($supplier['phone']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier['email']): ?>
                                                        <div><i class="fas fa-envelope text-gray-400"></i> <?= htmlspecialchars($supplier['email']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier['address']): ?>
                                                        <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($supplier['address']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?= $supplier['product_count'] ?> sản phẩm
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($supplier['STATUS']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Tạm dừng
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex items-center justify-center gap-2" onclick="event.stopPropagation()">
                                                    <button onclick="editSupplier(<?= htmlspecialchars(json_encode($supplier)) ?>)" 
                                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition"
                                                            title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteSupplier(<?= $supplier['id'] ?>, '<?= htmlspecialchars($supplier['NAME']) ?>', <?= $supplier['product_count'] ?>)" 
                                                            class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition"
                                                            title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Chưa có nhà phân phối nào
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewSupplierDetails(supplier) {
            const modal = document.createElement('div');
            modal.id = 'supplierModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.onclick = (e) => {
                if (e.target === modal) modal.remove();
            };
            
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="bg-gradient-to-r from-purple-500 to-blue-500 text-white p-6 rounded-t-lg">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold"><i class="fas fa-truck"></i> ${supplier.NAME}</h3>
                            <button onclick="document.getElementById('supplierModal').remove()" class="text-white hover:text-gray-200">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        ${supplier.contact_person ? `
                        <div class="flex items-start gap-3">
                            <i class="fas fa-user text-purple-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Người liên hệ</p>
                                <p class="text-lg font-semibold">${supplier.contact_person}</p>
                            </div>
                        </div>` : ''}
                        
                        ${supplier.phone ? `
                        <div class="flex items-start gap-3">
                            <i class="fas fa-phone text-green-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Số điện thoại</p>
                                <p class="text-lg font-semibold"><a href="tel:${supplier.phone}" class="text-blue-600 hover:underline">${supplier.phone}</a></p>
                            </div>
                        </div>` : ''}
                        
                        ${supplier.email ? `
                        <div class="flex items-start gap-3">
                            <i class="fas fa-envelope text-blue-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="text-lg font-semibold"><a href="mailto:${supplier.email}" class="text-blue-600 hover:underline">${supplier.email}</a></p>
                            </div>
                        </div>` : ''}
                        
                        ${supplier.address ? `
                        <div class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-red-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Địa chỉ</p>
                                <p class="text-lg">${supplier.address}</p>
                            </div>
                        </div>` : ''}
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-boxes text-orange-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Số sản phẩm</p>
                                <p class="text-lg font-semibold">${supplier.product_count} sản phẩm</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-toggle-${supplier.STATUS ? 'on' : 'off'} text-${supplier.STATUS ? 'green' : 'red'}-500 text-xl mt-1"></i>
                            <div>
                                <p class="text-sm text-gray-500">Trạng thái</p>
                                <p class="text-lg font-semibold">${supplier.STATUS ? '<span class="text-green-600">Đang hoạt động</span>' : '<span class="text-red-600">Tạm dừng</span>'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex gap-3">
                        <button onclick="editSupplier(${JSON.stringify(supplier).replace(/"/g, '&quot;')}); document.getElementById('supplierModal').remove();" 
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </button>
                        <button onclick="document.getElementById('supplierModal').remove()" 
                                class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function editSupplier(supplier) {
            document.getElementById('formAction').value = 'edit_supplier';
            document.getElementById('supplierId').value = supplier.id;
            document.getElementById('supplierName').value = supplier.NAME;
            document.getElementById('contactPerson').value = supplier.contact_person || '';
            document.getElementById('supplierPhone').value = supplier.phone || '';
            document.getElementById('supplierEmail').value = supplier.email || '';
            document.getElementById('supplierAddress').value = supplier.address || '';
            document.getElementById('supplierStatus').value = supplier.STATUS;
            
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Cập Nhật';
            document.querySelector('h2').innerHTML = '<i class="fas fa-edit text-blue-500"></i> Sửa Nhà Phân Phối';
        }

        function resetForm() {
            document.getElementById('supplierForm').reset();
            document.getElementById('formAction').value = 'add_supplier';
            document.getElementById('supplierId').value = '';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Thêm Nhà Phân Phối';
            document.querySelector('h2').innerHTML = '<i class="fas fa-plus-circle text-green-500"></i> Thêm Nhà Phân Phối';
        }

        function deleteSupplier(id, name, productCount) {
            let message = `Bạn có chắc muốn xóa nhà phân phối "${name}"?`;
            
            if (productCount > 0) {
                message += `\n\n⚠️ Cảnh báo: Nhà phân phối này có ${productCount} sản phẩm. Bạn cần xóa hoặc chuyển các sản phẩm này sang nhà phân phối khác trước.`;
            }
            
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>