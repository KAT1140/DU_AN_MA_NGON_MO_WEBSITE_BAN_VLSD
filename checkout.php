<?php
require 'config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php?redirect=checkout');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Lấy thông tin user
$user_stmt = $conn->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Lấy địa chỉ đã lưu
$saved_addresses = $conn->query("SELECT * FROM saved_addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");

// Lấy giỏ hàng
$sql = "SELECT ci.id, ci.quantity, ci.price, p.id as product_id, p.NAME as product_name, p.images 
        FROM cart c
        JOIN cart_items ci ON ci.cart_id = c.id
        JOIN products p ON p.id = ci.product_id
        WHERE c.session_id = ? OR (c.user_id = ? AND c.user_id != 0)
        ORDER BY ci.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $cart_session, $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;
$shipping_fee = 0;

if ($cart_result->num_rows > 0) {
    while ($item = $cart_result->fetch_assoc()) {
        $item_total = $item['quantity'] * $item['price'];
        $subtotal += $item_total;
        $cart_items[] = $item;
    }
    
    // Tính phí vận chuyển (miễn phí trên 1 triệu)
    if ($subtotal < 1000000) {
        $shipping_fee = 30000;
    }
    
    $total = $subtotal + $shipping_fee;
} else {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset và base styles */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        /* Payment method styles */
        .payment-method {
            transition: all 0.3s ease;
            min-height: 100px;
        }
        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .payment-method.selected {
            border-color: #8b5cf6 !important;
            background-color: #f3f4f6 !important;
        }
        .payment-method h3 {
            font-size: 1.125rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .payment-method p {
            font-size: 1rem;
            line-height: 1.5;
            color: #6b7280;
        }
        .payment-method i {
            font-size: 2.5rem !important;
        }
        
        /* MAIN CHECKOUT LAYOUT - ABSOLUTE POSITIONING */
        .checkout-main {
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 1rem !important;
            width: 100% !important;
        }
        
        .checkout-content {
            position: relative !important;
            width: 100% !important;
            min-height: 600px !important;
        }
        
        .checkout-form-section {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 65% !important;
            padding-right: 2rem !important;
        }
        
        .checkout-summary-section {
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            width: 33% !important;
            min-width: 300px !important;
        }
        
        /* Override any conflicting styles */
        .checkout-content > * {
            display: block !important;
        }
        
        /* Force desktop layout on screens > 1024px */
        @media (min-width: 1025px) {
            .checkout-form-section {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 65% !important;
            }
            
            .checkout-summary-section {
                position: absolute !important;
                right: 0 !important;
                top: 0 !important;
                width: 33% !important;
            }
        }
        
        /* Mobile responsive */
        @media (max-width: 1024px) {
            .checkout-content {
                position: static !important;
            }
            
            .checkout-form-section {
                position: static !important;
                width: 100% !important;
                padding-right: 0 !important;
                margin-bottom: 2rem !important;
            }
            
            .checkout-summary-section {
                position: static !important;
                width: 100% !important;
                min-width: 0 !important;
            }
        }
        
        @media (max-width: 768px) {
            .checkout-main {
                padding: 0.5rem !important;
            }
            
            .checkout-content {
                gap: 1rem !important;
            }
        }
        
        /* Form elements */
        input, textarea, select {
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        /* Grid utilities */
        .grid {
            width: 100% !important;
        }
        
        .grid > div {
            width: 100% !important;
            min-width: 0 !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
                <h1 class="text-3xl font-black">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-8">
                <nav class="flex items-center gap-6">
                    <a href="index.php" class="text-white font-bold hover:text-purple-200 transition text-lg flex items-center gap-2">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="products.php" class="text-white font-bold hover:text-purple-200 transition text-lg flex items-center gap-2">
                        <i class="fas fa-box"></i> Sản phẩm
                    </a>
                </nav>
                
                <div class="flex items-center gap-3">
                    <a href="profile.php" class="text-white font-bold hover:text-purple-200 transition text-lg">
                        👤 <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?>
                    </a>
                    <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-full font-bold hover:bg-red-700 transition">
                        Đăng xuất
                    </a>
                </div>

                <a href="cart.php" class="relative group">
                    <span class="text-3xl group-hover:scale-110 transition inline-block">🛒</span>
                    <?php
                    $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '" . $conn->real_escape_string($cart_session) . "'");
                    $row = $res ? $res->fetch_assoc() : null;
                    $count = $row['total_qty'] ?? 0;
                    $hiddenClass = ($count > 0) ? '' : 'hidden';
                    echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-purple-600 w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-md $hiddenClass'>{$count}</span>";
                    ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Progress Steps -->
    <div class="checkout-main py-6">
        <div class="flex justify-center items-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center text-purple-600">
                    <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">
                        1
                    </div>
                    <span class="ml-2 font-semibold">Giỏ hàng</span>
                </div>
                <div class="w-24 h-1 bg-purple-600 mx-4"></div>
                <div class="flex items-center text-purple-600">
                    <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 font-semibold">Thanh toán</span>
                </div>
                <div class="w-24 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center text-gray-400">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2">Hoàn tất</span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content">
            <!-- Left Column - Shipping & Payment -->
            <div class="checkout-form-section">
                <form id="checkout-form" method="POST" action="process_order.php">
                <div class="space-y-6">
                <!-- Shipping Information -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-truck text-purple-600"></i> Thông tin giao hàng
                        </h2>
                        <a href="addresses.php" class="text-purple-600 hover:text-purple-700 text-sm">
                            <i class="fas fa-map-marker-alt"></i> Quản lý địa chỉ
                        </a>
                    </div>
                    
                    <?php if ($saved_addresses->num_rows > 0): ?>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Chọn địa chỉ có sẵn</label>
                            <select id="savedAddressSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">-- Nhập địa chỉ mới --</option>
                                <?php while ($addr = $saved_addresses->fetch_assoc()): ?>
                                    <option value='<?= json_encode($addr) ?>'>
                                        <?= htmlspecialchars($addr['address_name']) ?> - <?= htmlspecialchars($addr['recipient_name']) ?> - <?= htmlspecialchars($addr['recipient_phone']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Họ và tên *</label>
                                <input type="text" name="customer_name" required 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                <input type="email" name="customer_email" required
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại *</label>
                                <input type="tel" name="customer_phone" required
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tỉnh/Thành phố *</label>
                                <input type="text" name="province" required list="provinces-list" 
                                       placeholder="Nhập hoặc chọn tỉnh/thành phố"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <datalist id="provinces-list">
                                    <!-- Danh sách sẽ được tạo động bằng JavaScript -->
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ cụ thể *</label>
                            <textarea name="customer_address" required rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ghi chú đơn hàng</label>
                            <textarea name="note" rows="2" placeholder="Ghi chú về đơn hàng, ví dụ: thời gian giao hàng, yêu cầu đặc biệt..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-credit-card text-purple-600"></i> Phương thức thanh toán
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-6 cursor-pointer selected"
                             data-method="cod">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="w-7 h-7 rounded-full border-2 border-purple-600 flex items-center justify-center flex-shrink-0">
                                        <div class="w-4 h-4 rounded-full bg-purple-600"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-800 text-lg">Thanh toán khi nhận hàng (COD)</h3>
                                        <p class="text-gray-600 text-base">Thanh toán bằng tiền mặt khi nhận hàng</p>
                                    </div>
                                </div>
                                <i class="fas fa-money-bill-wave text-3xl text-green-600 flex-shrink-0 ml-4"></i>
                            </div>
                        </div>
                        
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-6 cursor-pointer"
                             data-method="banking">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="w-7 h-7 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0">
                                        <div class="w-4 h-4 rounded-full bg-gray-300"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-800 text-lg">Chuyển khoản ngân hàng</h3>
                                        <p class="text-gray-600 text-base">Chuyển khoản qua Internet Banking</p>
                                    </div>
                                </div>
                                <i class="fas fa-university text-3xl text-blue-600 flex-shrink-0 ml-4"></i>
                            </div>
                        </div>
                        
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-6 cursor-pointer"
                             data-method="momo">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="w-7 h-7 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0">
                                        <div class="w-4 h-4 rounded-full bg-gray-300"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-800 text-lg">Ví MoMo</h3>
                                        <p class="text-gray-600 text-base">Thanh toán qua ứng dụng MoMo</p>
                                    </div>
                                </div>
                                <i class="fas fa-mobile-alt text-3xl text-pink-600 flex-shrink-0 ml-4"></i>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="payment_method" value="cod">
                    <input type="hidden" name="shipping_fee" value="<?= $shipping_fee ?>">
                    <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                    <input type="hidden" name="total" value="<?= $total ?>">
                </div>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="checkout-summary-section">
                <div class="bg-white rounded-xl shadow-md sticky top-24">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-receipt text-purple-600"></i> Đơn hàng của bạn
                        </h2>
                        
                        <!-- Cart Items -->
                        <div class="space-y-3 max-h-64 overflow-y-auto mb-4">
                            <?php foreach ($cart_items as $item): 
                                $item_total = $item['quantity'] * $item['price'];
                            ?>
                            <div class="flex items-center justify-between py-2 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <span class="text-gray-600 font-bold"><?= $item['quantity'] ?>x</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($item['product_name']) ?></h4>
                                        <p class="text-gray-600 text-xs"><?= number_format($item['price']) ?>đ</p>
                                    </div>
                                </div>
                                <span class="font-bold text-purple-600"><?= number_format($item_total) ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Tạm tính:</span>
                                <span class="font-bold"><?= number_format($subtotal) ?>đ</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Phí vận chuyển:</span>
                                <span class="font-bold <?= $shipping_fee == 0 ? 'text-green-600' : '' ?>">
                                    <?= $shipping_fee == 0 ? 'MIỄN PHÍ' : number_format($shipping_fee).'đ' ?>
                                </span>
                            </div>
                            
                            <?php if ($shipping_fee > 0 && $subtotal < 1000000): ?>
                            <div class="text-sm text-gray-500 bg-yellow-50 p-2 rounded">
                                <i class="fas fa-info-circle text-yellow-500"></i>
                                Miễn phí vận chuyển cho đơn hàng từ 1.000.000đ
                            </div>
                            <?php endif; ?>
                            
                            <div class="pt-4 border-t">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-800">Tổng cộng:</span>
                                    <span class="text-2xl font-black text-purple-600"><?= number_format($total) ?>đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit -->
                    <div class="p-6">
                        <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-4 rounded-lg font-bold text-lg hover:shadow-lg transition">
                            <i class="fas fa-lock"></i> ĐẶT HÀNG NGAY
                        </button>
                        
                        <p class="text-center text-gray-500 text-sm mt-3">
                            <i class="fas fa-shield-alt"></i> Thanh toán an toàn & bảo mật
                        </p>
                        
                        <a href="cart.php" class="block text-center text-gray-600 hover:text-purple-600 mt-4">
                            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                        </a>
                    </div>
                </div>
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

        // Load saved address
        document.getElementById('savedAddressSelect')?.addEventListener('change', function() {
            if (this.value) {
                const addr = JSON.parse(this.value);
                document.querySelector('[name="customer_name"]').value = addr.recipient_name;
                document.querySelector('[name="customer_phone"]').value = addr.recipient_phone;
                document.querySelector('[name="province"]').value = addr.province;
                document.querySelector('[name="customer_address"]').value = addr.address;
                
                // Cập nhật danh sách tỉnh với tỉnh đã chọn ở đầu
                updateProvincesList(addr.province);
            } else {
                // Clear fields
                document.querySelector('[name="customer_name"]').value = '';
                document.querySelector('[name="customer_phone"]').value = '';
                document.querySelector('[name="province"]').value = '';
                document.querySelector('[name="customer_address"]').value = '';
                
                // Reset danh sách tỉnh về thứ tự ban đầu
                updateProvincesList();
            }
        });

        // Lắng nghe sự kiện thay đổi tỉnh/thành phố
        document.querySelector('[name="province"]').addEventListener('change', function() {
            const selectedProvince = this.value.trim();
            if (selectedProvince && provinces.includes(selectedProvince)) {
                updateProvincesList(selectedProvince);
            }
        });

        // Khởi tạo danh sách tỉnh ban đầu
        updateProvincesList();

        // Payment Method Selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                    m.querySelector('.w-7').classList.remove('border-purple-600');
                    m.querySelector('.w-7').classList.add('border-gray-300');
                    m.querySelector('.w-4').classList.remove('bg-purple-600');
                    m.querySelector('.w-4').classList.add('bg-gray-300');
                });
                
                // Add selected class to clicked
                this.classList.add('selected');
                this.querySelector('.w-7').classList.remove('border-gray-300');
                this.querySelector('.w-7').classList.add('border-purple-600');
                this.querySelector('.w-4').classList.remove('bg-gray-300');
                this.querySelector('.w-4').classList.add('bg-purple-600');
                
                // Update hidden input
                const methodValue = this.getAttribute('data-method');
                document.getElementById('payment_method').value = methodValue;
            });
        });

        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            console.log('Form submit triggered');
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            let emptyFields = [];
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ef4444';
                    emptyFields.push(field.name || field.id || 'unknown field');
                } else {
                    field.style.borderColor = '#d1d5db';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                console.log('Form validation failed. Empty fields:', emptyFields);
                alert('Vui lòng điền đầy đủ thông tin bắt buộc: ' + emptyFields.join(', '));
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            return true;
        });
    </script>
</body>
</html>
