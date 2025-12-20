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
        .payment-method {
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .payment-method.selected {
            border-color: #f97316;
            background-color: #fff7ed;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-gradient-to-r from-orange-500 to-amber-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-16 h-16 object-cover rounded-full">
                <h1 class="text-3xl font-black">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-8">
                <nav class="flex items-center gap-6">
                    <a href="index.php" class="text-white font-bold hover:text-orange-200 transition text-lg flex items-center gap-2">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="products.php" class="text-white font-bold hover:text-orange-200 transition text-lg flex items-center gap-2">
                        <i class="fas fa-box"></i> Sản phẩm
                    </a>
                </nav>
                
                <div class="flex items-center gap-3">
                    <a href="profile.php" class="text-white font-bold hover:text-orange-200 transition text-lg">
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
                    echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-orange-600 w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-md $hiddenClass'>{$count}</span>";
                    ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Progress Steps -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex justify-center items-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center text-orange-600">
                    <div class="w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">
                        1
                    </div>
                    <span class="ml-2 font-semibold">Giỏ hàng</span>
                </div>
                <div class="w-24 h-1 bg-orange-600 mx-4"></div>
                <div class="flex items-center text-orange-600">
                    <div class="w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Shipping & Payment -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Shipping Information -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-truck text-orange-600"></i> Thông tin giao hàng
                        </h2>
                        <a href="addresses.php" class="text-orange-600 hover:text-orange-700 text-sm">
                            <i class="fas fa-map-marker-alt"></i> Quản lý địa chỉ
                        </a>
                    </div>
                    
                    <?php if ($saved_addresses->num_rows > 0): ?>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Chọn địa chỉ có sẵn</label>
                            <select id="savedAddressSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="">-- Nhập địa chỉ mới --</option>
                                <?php while ($addr = $saved_addresses->fetch_assoc()): ?>
                                    <option value='<?= json_encode($addr) ?>'>
                                        <?= htmlspecialchars($addr['address_name']) ?> - <?= htmlspecialchars($addr['recipient_name']) ?> - <?= htmlspecialchars($addr['recipient_phone']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Họ và tên *</label>
                                <input type="text" name="customer_name" required 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                <input type="email" name="customer_email" required
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại *</label>
                                <input type="tel" name="customer_phone" required
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tỉnh/Thành phố *</label>
                                <input type="text" name="province" required list="provinces-list" 
                                       placeholder="Nhập hoặc chọn tỉnh/thành phố"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <datalist id="provinces-list">
                                    <option value="An Giang">
                                    <option value="Bà Rịa - Vũng Tàu">
                                    <option value="Bắc Giang">
                                    <option value="Bắc Kạn">
                                    <option value="Bạc Liêu">
                                    <option value="Bắc Ninh">
                                    <option value="Bến Tre">
                                    <option value="Bình Định">
                                    <option value="Bình Dương">
                                    <option value="Bình Phước">
                                    <option value="Bình Thuận">
                                    <option value="Cà Mau">
                                    <option value="Cần Thơ">
                                    <option value="Cao Bằng">
                                    <option value="Đà Nẵng">
                                    <option value="Đắk Lắk">
                                    <option value="Đắk Nông">
                                    <option value="Điện Biên">
                                    <option value="Đồng Nai">
                                    <option value="Đồng Tháp">
                                    <option value="Gia Lai">
                                    <option value="Hà Giang">
                                    <option value="Hà Nam">
                                    <option value="Hà Nội">
                                    <option value="Hà Tĩnh">
                                    <option value="Hải Dương">
                                    <option value="Hải Phòng">
                                    <option value="Hậu Giang">
                                    <option value="Hòa Bình">
                                    <option value="Hưng Yên">
                                    <option value="Khánh Hòa">
                                    <option value="Kiên Giang">
                                    <option value="Kon Tum">
                                    <option value="Lai Châu">
                                    <option value="Lâm Đồng">
                                    <option value="Lạng Sơn">
                                    <option value="Lào Cai">
                                    <option value="Long An">
                                    <option value="Nam Định">
                                    <option value="Nghệ An">
                                    <option value="Ninh Bình">
                                    <option value="Ninh Thuận">
                                    <option value="Phú Thọ">
                                    <option value="Phú Yên">
                                    <option value="Quảng Bình">
                                    <option value="Quảng Nam">
                                    <option value="Quảng Ngãi">
                                    <option value="Quảng Ninh">
                                    <option value="Quảng Trị">
                                    <option value="Sóc Trăng">
                                    <option value="Sơn La">
                                    <option value="Tây Ninh">
                                    <option value="Thái Bình">
                                    <option value="Thái Nguyên">
                                    <option value="Thanh Hóa">
                                    <option value="Thừa Thiên Huế">
                                    <option value="Tiền Giang">
                                    <option value="TP. Hồ Chí Minh">
                                    <option value="Trà Vinh">
                                    <option value="Tuyên Quang">
                                    <option value="Vĩnh Long">
                                    <option value="Vĩnh Phúc">
                                    <option value="Yên Bái">
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Địa chỉ cụ thể *</label>
                            <textarea name="customer_address" required rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ghi chú đơn hàng</label>
                            <textarea name="note" rows="2" placeholder="Ghi chú về đơn hàng, ví dụ: thời gian giao hàng, yêu cầu đặc biệt..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                        </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-credit-card text-orange-600"></i> Phương thức thanh toán
                    </h2>
                    
                    <div class="space-y-3">
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer selected"
                             data-method="cod">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full border-2 border-orange-600 flex items-center justify-center">
                                        <div class="w-3 h-3 rounded-full bg-orange-600"></div>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Thanh toán khi nhận hàng (COD)</h3>
                                        <p class="text-gray-600 text-sm">Thanh toán bằng tiền mặt khi nhận hàng</p>
                                    </div>
                                </div>
                                <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                            </div>
                        </div>
                        
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer"
                             data-method="banking">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center">
                                        <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Chuyển khoản ngân hàng</h3>
                                        <p class="text-gray-600 text-sm">Chuyển khoản qua Internet Banking</p>
                                    </div>
                                </div>
                                <i class="fas fa-university text-2xl text-blue-600"></i>
                            </div>
                        </div>
                        
                        <div class="payment-method border-2 border-gray-200 rounded-lg p-4 cursor-pointer"
                             data-method="momo">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center">
                                        <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Ví MoMo</h3>
                                        <p class="text-gray-600 text-sm">Thanh toán qua ứng dụng MoMo</p>
                                    </div>
                                </div>
                                <i class="fas fa-mobile-alt text-2xl text-pink-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="payment_method" value="cod">
                    <input type="hidden" name="shipping_fee" value="<?= $shipping_fee ?>">
                    <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                    <input type="hidden" name="total" value="<?= $total ?>">
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md sticky top-24">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-receipt text-orange-600"></i> Đơn hàng của bạn
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
                                <span class="font-bold text-orange-600"><?= number_format($item_total) ?>đ</span>
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
                                    <span class="text-2xl font-black text-orange-600"><?= number_format($total) ?>đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit -->
                    <div class="p-6">
                        <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-amber-500 text-white py-4 rounded-lg font-bold text-lg hover:shadow-lg transition">
                            <i class="fas fa-lock"></i> ĐẶT HÀNG NGAY
                        </button>
                        
                        <p class="text-center text-gray-500 text-sm mt-3">
                            <i class="fas fa-shield-alt"></i> Thanh toán an toàn & bảo mật
                        </p>
                        
                        <a href="cart.php" class="block text-center text-gray-600 hover:text-orange-600 mt-4">
                            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                        </a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load saved address
        document.getElementById('savedAddressSelect')?.addEventListener('change', function() {
            if (this.value) {
                const addr = JSON.parse(this.value);
                document.querySelector('[name="customer_name"]').value = addr.recipient_name;
                document.querySelector('[name="customer_phone"]').value = addr.recipient_phone;
                document.querySelector('[name="province"]').value = addr.province;
                document.querySelector('[name="customer_address"]').value = addr.address;
            } else {
                // Clear fields
                document.querySelector('[name="customer_name"]').value = '';
                document.querySelector('[name="customer_phone"]').value = '';
                document.querySelector('[name="province"]').value = '';
                document.querySelector('[name="customer_address"]').value = '';
            }
        });

        // Payment Method Selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                    m.querySelector('.w-6').classList.remove('border-orange-600');
                    m.querySelector('.w-6').classList.add('border-gray-300');
                    m.querySelector('.w-3').classList.remove('bg-orange-600');
                    m.querySelector('.w-3').classList.add('bg-gray-300');
                });
                
                // Add selected class to clicked
                this.classList.add('selected');
                this.querySelector('.w-6').classList.remove('border-gray-300');
                this.querySelector('.w-6').classList.add('border-orange-600');
                this.querySelector('.w-3').classList.remove('bg-gray-300');
                this.querySelector('.w-3').classList.add('bg-orange-600');
                
                // Update hidden input
                const methodValue = this.getAttribute('data-method');
                document.getElementById('payment_method').value = methodValue;
            });
        });

        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '#d1d5db';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
            }
        });
    </script>
</body>
</html>