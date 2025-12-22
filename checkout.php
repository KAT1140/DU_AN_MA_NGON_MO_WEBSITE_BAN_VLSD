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
    <title>Thanh Toán - VLXD PRO</title>
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
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-orange-600 flex items-center gap-2">
                <i class="fas fa-hammer"></i> VLXD PRO
            </a>
            <div class="flex items-center gap-4">
                <span class="text-gray-700"><i class="fas fa-shopping-bag"></i> Thanh toán</span>
                <a href="cart.php" class="text-gray-600 hover:text-orange-600">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
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
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-truck text-orange-600"></i> Thông tin giao hàng
                    </h2>
                    
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
                                <select name="province" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    <option value="">Chọn tỉnh/thành phố</option>
                                    <option value="An Giang">An Giang</option>
                                    <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                                    <option value="Bắc Giang">Bắc Giang</option>
                                    <option value="Bắc Kạn">Bắc Kạn</option>
                                    <option value="Bạc Liêu">Bạc Liêu</option>
                                    <option value="Bắc Ninh">Bắc Ninh</option>
                                    <option value="Bến Tre">Bến Tre</option>
                                    <option value="Bình Định">Bình Định</option>
                                    <option value="Bình Dương">Bình Dương</option>
                                    <option value="Bình Phước">Bình Phước</option>
                                    <option value="Bình Thuận">Bình Thuận</option>
                                    <option value="Cà Mau">Cà Mau</option>
                                    <option value="Cần Thơ">Cần Thơ</option>
                                    <option value="Cao Bằng">Cao Bằng</option>
                                    <option value="Đà Nẵng">Đà Nẵng</option>
                                    <option value="Đắk Lắk">Đắk Lắk</option>
                                    <option value="Đắk Nông">Đắk Nông</option>
                                    <option value="Điện Biên">Điện Biên</option>
                                    <option value="Đồng Nai">Đồng Nai</option>
                                    <option value="Đồng Tháp">Đồng Tháp</option>
                                    <option value="Gia Lai">Gia Lai</option>
                                    <option value="Hà Giang">Hà Giang</option>
                                    <option value="Hà Nam">Hà Nam</option>
                                    <option value="Hà Nội">Hà Nội</option>
                                    <option value="Hà Tĩnh">Hà Tĩnh</option>
                                    <option value="Hải Dương">Hải Dương</option>
                                    <option value="Hải Phòng">Hải Phòng</option>
                                    <option value="Hậu Giang">Hậu Giang</option>
                                    <option value="Hòa Bình">Hòa Bình</option>
                                    <option value="Hưng Yên">Hưng Yên</option>
                                    <option value="Khánh Hòa">Khánh Hòa</option>
                                    <option value="Kiên Giang">Kiên Giang</option>
                                    <option value="Kon Tum">Kon Tum</option>
                                    <option value="Lai Châu">Lai Châu</option>
                                    <option value="Lâm Đồng">Lâm Đồng</option>
                                    <option value="Lạng Sơn">Lạng Sơn</option>
                                    <option value="Lào Cai">Lào Cai</option>
                                    <option value="Long An">Long An</option>
                                    <option value="Nam Định">Nam Định</option>
                                    <option value="Nghệ An">Nghệ An</option>
                                    <option value="Ninh Bình">Ninh Bình</option>
                                    <option value="Ninh Thuận">Ninh Thuận</option>
                                    <option value="Phú Thọ">Phú Thọ</option>
                                    <option value="Phú Yên">Phú Yên</option>
                                    <option value="Quảng Bình">Quảng Bình</option>
                                    <option value="Quảng Nam">Quảng Nam</option>
                                    <option value="Quảng Ngãi">Quảng Ngãi</option>
                                    <option value="Quảng Ninh">Quảng Ninh</option>
                                    <option value="Quảng Trị">Quảng Trị</option>
                                    <option value="Sóc Trăng">Sóc Trăng</option>
                                    <option value="Sơn La">Sơn La</option>
                                    <option value="Tây Ninh">Tây Ninh</option>
                                    <option value="Thái Bình">Thái Bình</option>
                                    <option value="Thái Nguyên">Thái Nguyên</option>
                                    <option value="Thanh Hóa">Thanh Hóa</option>
                                    <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                                    <option value="Tiền Giang">Tiền Giang</option>
                                    <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                                    <option value="Trà Vinh">Trà Vinh</option>
                                    <option value="Tuyên Quang">Tuyên Quang</option>
                                    <option value="Vĩnh Long">Vĩnh Long</option>
                                    <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                                    <option value="Yên Bái">Yên Bái</option>
                                </select>
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
                    
                    <!-- Terms & Submit -->
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" required class="mt-1">
                                <span class="text-sm text-gray-600">
                                    Tôi đồng ý với <a href="#" class="text-orange-600 hover:underline">điều khoản và điều kiện</a> của VLXD PRO
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-orange-600 to-orange-500 text-white py-4 rounded-lg font-bold text-lg hover:shadow-lg transition">
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