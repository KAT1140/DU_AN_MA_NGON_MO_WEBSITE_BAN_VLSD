<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Xử lý thêm/sửa/xóa địa chỉ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $address_id = intval($_POST['address_id'] ?? 0);
        $address_name = trim($_POST['address_name'] ?? '');
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $recipient_phone = trim($_POST['recipient_phone'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($recipient_name) || empty($recipient_phone) || empty($province) || empty($address)) {
            $error = 'Vui lòng điền đầy đủ thông tin';
        } else {
            // Nếu đặt làm mặc định, bỏ mặc định của các địa chỉ khác
            if ($is_default) {
                $conn->query("UPDATE saved_addresses SET is_default = 0 WHERE user_id = $user_id");
            }
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO saved_addresses (user_id, address_name, recipient_name, recipient_phone, province, address, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('isssssi', $user_id, $address_name, $recipient_name, $recipient_phone, $province, $address, $is_default);
                if ($stmt->execute()) {
                    $message = 'Thêm địa chỉ thành công!';
                } else {
                    $error = 'Có lỗi xảy ra';
                }
            } else {
                $stmt = $conn->prepare("UPDATE saved_addresses SET address_name = ?, recipient_name = ?, recipient_phone = ?, province = ?, address = ?, is_default = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param('sssssiii', $address_name, $recipient_name, $recipient_phone, $province, $address, $is_default, $address_id, $user_id);
                if ($stmt->execute()) {
                    $message = 'Cập nhật địa chỉ thành công!';
                } else {
                    $error = 'Có lỗi xảy ra';
                }
            }
        }
    } elseif ($action === 'delete') {
        $address_id = intval($_POST['address_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM saved_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $address_id, $user_id);
        if ($stmt->execute()) {
            $message = 'Xóa địa chỉ thành công!';
        }
    } elseif ($action === 'set_default') {
        $address_id = intval($_POST['address_id'] ?? 0);
        $conn->query("UPDATE saved_addresses SET is_default = 0 WHERE user_id = $user_id");
        $stmt = $conn->prepare("UPDATE saved_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $address_id, $user_id);
        if ($stmt->execute()) {
            $message = 'Đã đặt làm địa chỉ mặc định!';
        }
    }
}

// Lấy danh sách địa chỉ
$addresses = $conn->query("SELECT * FROM saved_addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Địa Chỉ Giao Hàng - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
                <h1 class="text-3xl font-black">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-4">
                <a href="profile.php" class="text-white hover:text-purple-200"><i class="fas fa-user"></i> Tài khoản</a>
                <a href="cart.php" class="text-white hover:text-purple-200"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a>
                <a href="logout.php" class="text-white hover:text-purple-200"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><i class="fas fa-map-marker-alt"></i> Sổ Địa Chỉ</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Nút thêm địa chỉ -->
        <button onclick="showAddModal()" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 mb-6">
            <i class="fas fa-plus"></i> Thêm Địa Chỉ Mới
        </button>

        <!-- Danh sách địa chỉ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if ($addresses->num_rows > 0): ?>
                <?php while ($addr = $addresses->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 relative <?= $addr['is_default'] ? 'border-2 border-purple-500' : '' ?>">
                        <?php if ($addr['is_default']): ?>
                            <span class="absolute top-2 right-2 bg-purple-500 text-white text-xs px-2 py-1 rounded">Mặc định</span>
                        <?php endif; ?>
                        
                        <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($addr['address_name']) ?></h3>
                        <p class="text-gray-700"><i class="fas fa-user"></i> <?= htmlspecialchars($addr['recipient_name']) ?></p>
                        <p class="text-gray-700"><i class="fas fa-phone"></i> <?= htmlspecialchars($addr['recipient_phone']) ?></p>
                        <p class="text-gray-700"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($addr['address']) ?>, <?= htmlspecialchars($addr['province']) ?></p>
                        
                        <div class="mt-4 flex gap-2">
                            <?php if (!$addr['is_default']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">Đặt mặc định</button>
                                </form>
                            <?php endif; ?>
                            <button onclick='editAddress(<?= json_encode($addr) ?>)' class="text-purple-600 hover:text-purple-800 text-sm">Sửa</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Xác nhận xóa địa chỉ này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-2 text-center py-12 text-gray-500">
                    <i class="fas fa-map-marked-alt text-6xl mb-4"></i>
                    <p>Chưa có địa chỉ nào. Thêm địa chỉ giao hàng để đặt hàng nhanh hơn!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal thêm/sửa địa chỉ -->
    <div id="addressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 id="modalTitle" class="text-2xl font-bold mb-6">Thêm Địa Chỉ Mới</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="address_id" id="addressId" value="0">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tên địa chỉ</label>
                    <input type="text" name="address_name" id="addressName" placeholder="VD: Nhà riêng, Công ty..."
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Người nhận *</label>
                        <input type="text" name="recipient_name" id="recipientName" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Số điện thoại *</label>
                        <input type="tel" name="recipient_phone" id="recipientPhone" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tỉnh/Thành phố *</label>
                    <input type="text" name="province" id="province" required list="provinces-list" 
                           placeholder="Nhập hoặc chọn tỉnh/thành phố"
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="provinces-list">
                        <!-- Danh sách sẽ được tạo động bằng JavaScript -->
                    </datalist>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Địa chỉ cụ thể *</label>
                    <textarea name="address" id="address" required rows="3"
                              placeholder="Số nhà, tên đường, phường/xã, quận/huyện"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" id="isDefault" class="mr-2">
                        <span class="text-gray-700">Đặt làm địa chỉ mặc định</span>
                    </label>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                        Lưu Địa Chỉ
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Danh sách tỉnh/thành phố
        const provinces = [
            "An Giang", "Bà Rịa - Vũng Tàu", "Bắc Giang", "Bắc Kạn", "Bạc Liêu", "Bắc Ninh", 
            "Bến Tre", "Bình Định", "Bình Dương", "Bình Phước", "Bình Thuận", "Cà Mau", 
            "Cần Thơ", "Cao Bằng", "Đà Nẵng", "Đắk Lắk", "Đắk Nông", "Điện Biên", 
            "Đồng Nai", "Đồng Tháp", "Gia Lai", "Hà Giang", "Hà Nam", "Hà Nội", 
            "Hà Tĩnh", "Hải Dương", "Hải Phòng", "Hậu Giang", "Hòa Bình", "Hưng Yên", 
            "Khánh Hòa", "Kiên Giang", "Kon Tum", "Lai Châu", "Lâm Đồng", "Lạng Sơn", 
            "Lào Cai", "Long An", "Nam Định", "Nghệ An", "Ninh Bình", "Ninh Thuận", 
            "Phú Thọ", "Phú Yên", "Quảng Bình", "Quảng Nam", "Quảng Ngãi", "Quảng Ninh", 
            "Quảng Trị", "Sóc Trăng", "Sơn La", "Tây Ninh", "Thái Bình", "Thái Nguyên", 
            "Thanh Hóa", "Thừa Thiên Huế", "Tiền Giang", "TP. Hồ Chí Minh", "Trà Vinh", 
            "Tuyên Quang", "Vĩnh Long", "Vĩnh Phúc", "Yên Bái"
        ];

        // Function để cập nhật datalist với tỉnh đã chọn ở đầu
        function updateProvincesList(selectedProvince = '') {
            const datalist = document.getElementById('provinces-list');
            datalist.innerHTML = '';
            
            let sortedProvinces = [...provinces];
            
            // Nếu có tỉnh đã chọn, đưa lên đầu
            if (selectedProvince && provinces.includes(selectedProvince)) {
                sortedProvinces = sortedProvinces.filter(p => p !== selectedProvince);
                sortedProvinces.unshift(selectedProvince);
            }
            
            // Tạo options
            sortedProvinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province;
                datalist.appendChild(option);
            });
        }

        // Lắng nghe sự kiện thay đổi tỉnh/thành phố
        document.getElementById('province').addEventListener('change', function() {
            const selectedProvince = this.value.trim();
            if (selectedProvince && provinces.includes(selectedProvince)) {
                updateProvincesList(selectedProvince);
            }
        });

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm Địa Chỉ Mới';
            document.getElementById('formAction').value = 'add';
            document.getElementById('addressId').value = '0';
            document.getElementById('addressName').value = '';
            document.getElementById('recipientName').value = '';
            document.getElementById('recipientPhone').value = '';
            document.getElementById('province').value = '';
            document.getElementById('address').value = '';
            document.getElementById('isDefault').checked = false;
            document.getElementById('addressModal').classList.remove('hidden');
            
            // Reset danh sách tỉnh về thứ tự ban đầu
            updateProvincesList();
        }

        function editAddress(addr) {
            document.getElementById('modalTitle').textContent = 'Sửa Địa Chỉ';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('addressId').value = addr.id;
            document.getElementById('addressName').value = addr.address_name;
            document.getElementById('recipientName').value = addr.recipient_name;
            document.getElementById('recipientPhone').value = addr.recipient_phone;
            document.getElementById('province').value = addr.province;
            document.getElementById('address').value = addr.address;
            document.getElementById('isDefault').checked = addr.is_default == 1;
            document.getElementById('addressModal').classList.remove('hidden');
            
            // Cập nhật danh sách tỉnh với tỉnh đã chọn ở đầu
            updateProvincesList(addr.province);
        }

        function closeModal() {
            document.getElementById('addressModal').classList.add('hidden');
        }

        // Đóng modal khi click bên ngoài
        document.getElementById('addressModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Khởi tạo danh sách tỉnh ban đầu
        updateProvincesList();
    </script>
</body>
</html>
