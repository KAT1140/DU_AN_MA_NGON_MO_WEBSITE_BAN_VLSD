<?php 
require 'config.php'; 
header('Content-Type: text/html; charset=utf-8');

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin sản phẩm
$sql = "SELECT p.*, c.NAME as category_name, s.NAME as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        WHERE p.id = ? AND p.STATUS = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$product = $result->fetch_assoc();
// Ưu tiên sử dụng trường image trước, nếu không có thì dùng images JSON
if (!empty($product['image'])) {
    $images = ['uploads/' . $product['image']];
} else {
    $images_json = json_decode($product['images'], true) ?: [];
    $images = array_map(function($img) { return 'uploads/' . $img; }, $images_json);
}
$specifications = json_decode($product['specifications'], true) ?: [];

// Sản phẩm liên quan (cùng danh mục)
$related_sql = "SELECT id, NAME, price, sale_price, image, images 
                FROM products 
                WHERE category_id = ? AND id != ? AND STATUS = 'active' 
                ORDER BY RAND() 
                LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['category_id'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['NAME']) ?> - VLXD KAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-500 to-blue-500 text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-3 hover:opacity-90 transition">
                <img src="uploads/logo.png" alt="VLXD Logo" class="w-12 h-12 object-cover rounded-full">
                <h1 class="text-2xl font-bold">VLXD KAT</h1>
            </a>
            <div class="flex items-center gap-6">
                <nav class="flex items-center gap-4">
                    <a href="index.php" class="text-white hover:text-purple-200 transition">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                    <a href="products.php" class="text-white hover:text-purple-200 transition">
                        <i class="fas fa-box"></i> Sản phẩm
                    </a>
                </nav>
                
                <a href="cart.php" class="relative group">
                    <span class="text-2xl group-hover:scale-110 transition inline-block">🛒</span>
                    <?php
                    $res = $conn->query("SELECT SUM(ci.quantity) AS total_qty FROM cart c JOIN cart_items ci ON ci.cart_id = c.id WHERE c.session_id = '" . $conn->real_escape_string($cart_session) . "'");
                    $row = $res ? $res->fetch_assoc() : null;
                    $count = $row['total_qty'] ?? 0;
                    $hiddenClass = ($count > 0) ? '' : 'hidden';
                    echo "<span id='cart-count' class='absolute -top-2 -right-2 bg-white text-purple-600 w-6 h-6 rounded-full flex items-center justify-center font-bold text-sm shadow-md $hiddenClass'>{$count}</span>";
                    ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <nav class="text-sm text-gray-600">
                <a href="index.php" class="hover:text-purple-500">Trang chủ</a>
                <span class="mx-2">/</span>
                <a href="products.php" class="hover:text-purple-500">Sản phẩm</a>
                <?php if ($product['category_name']): ?>
                    <span class="mx-2">/</span>
                    <a href="products.php?category_id=<?= $product['category_id'] ?>" class="hover:text-purple-500">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                <?php endif; ?>
                <span class="mx-2">/</span>
                <span class="text-gray-800"><?= htmlspecialchars($product['NAME']) ?></span>
            </nav>
        </div>
    </div>

    <!-- Chi tiết sản phẩm -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="grid lg:grid-cols-2 gap-12">
            <!-- Hình ảnh sản phẩm -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <?php if (!empty($images)): ?>
                        <img id="main-image" src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($product['NAME']) ?>" 
                             class="w-full h-96 object-cover rounded-lg mb-4">
                        
                        <?php if (count($images) > 1): ?>
                            <div class="flex gap-2 overflow-x-auto">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['NAME']) ?>" 
                                         class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-purple-500 transition"
                                         onclick="changeMainImage('<?= htmlspecialchars($image) ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-6xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thông tin sản phẩm -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($product['NAME']) ?></h1>

                    <!-- Giá -->
                    <div class="mb-6">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <div class="flex items-center gap-3">
                                <span class="text-4xl font-bold text-red-600"><?= number_format($product['sale_price'], 0, ',', '.') ?>đ</span>
                                <span class="text-xl text-gray-500 line-through"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                                <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-bold">
                                    -<?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?>%
                                </span>
                            </div>
                        <?php else: ?>
                            <span class="text-4xl font-bold text-purple-600"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                        <?php endif; ?>
                    </div>

                    <!-- Mô tả ngắn -->
                    <?php if ($product['short_description']): ?>
                        <div class="mb-6">
                            <h3 class="font-bold text-lg mb-2">Mô tả ngắn:</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Thông tin cơ bản -->
                    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                        <div class="bg-gray-50 p-3 rounded">
                            <span class="font-semibold">SKU:</span> <?= htmlspecialchars($product['sku']) ?>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <span class="font-semibold">Tồn kho:</span> <?= $product['quantity'] ?> <?= htmlspecialchars($product['unit'] ?? 'sản phẩm') ?>
                        </div>
                        <?php if ($product['category_name']): ?>
                            <div class="bg-gray-50 p-3 rounded">
                                <span class="font-semibold">Danh mục:</span> <?= htmlspecialchars($product['category_name']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($product['supplier_name']): ?>
                            <div class="bg-gray-50 p-3 rounded">
                                <span class="font-semibold">Nhà cung cấp:</span> <?= htmlspecialchars($product['supplier_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thêm vào giỏ hàng -->
                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="flex gap-4 mb-6">
                            <div class="flex items-center border rounded-lg">
                                <button type="button" onclick="decreaseQuantity()" class="px-3 py-2 text-gray-600 hover:bg-gray-100">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" 
                                       class="w-16 text-center border-0 focus:ring-0">
                                <button type="button" onclick="increaseQuantity()" class="px-3 py-2 text-gray-600 hover:bg-gray-100">+</button>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-purple-500 text-white py-4 rounded-xl font-bold text-xl hover:bg-purple-600 transition shadow-lg">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mô tả chi tiết và thông số kỹ thuật -->
        <div class="mt-12 bg-white rounded-xl shadow-lg p-8">
            <div class="border-b mb-6">
                <nav class="flex gap-8">
                    <button onclick="showTab('description')" id="desc-tab" class="pb-4 border-b-2 border-purple-500 text-purple-500 font-bold">
                        Mô tả chi tiết
                    </button>
                    <?php if (!empty($specifications)): ?>
                        <button onclick="showTab('specifications')" id="spec-tab" class="pb-4 border-b-2 border-transparent text-gray-600 hover:text-purple-500">
                            Thông số kỹ thuật
                        </button>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Mô tả chi tiết -->
            <div id="description-content" class="tab-content">
                <?php if ($product['description']): ?>
                    <div class="prose max-w-none">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Chưa có mô tả chi tiết cho sản phẩm này.</p>
                <?php endif; ?>
            </div>

            <!-- Thông số kỹ thuật -->
            <?php if (!empty($specifications)): ?>
                <div id="specifications-content" class="tab-content hidden">
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($specifications as $key => $value): ?>
                            <div class="flex justify-between py-2 border-b">
                                <span class="font-semibold"><?= htmlspecialchars($key) ?>:</span>
                                <span><?= htmlspecialchars($value) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Đánh giá -->
            <div id="reviews-content" class="tab-content hidden">
                <?php if ($reviews->num_rows > 0): ?>
                    <div class="space-y-6">
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="border-b pb-6">
                                <div class="flex items-center gap-4 mb-2">
                                    <div class="w-10 h-10 bg-purple-500 text-white rounded-full flex items-center justify-center font-bold">
                                        <?= strtoupper(substr($review['full_name'] ?: $review['email'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold"><?= htmlspecialchars($review['full_name'] ?: $review['email']) ?></h4>
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <span class="text-gray-500 text-sm ml-auto"><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="text-gray-700 ml-14"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">Chưa có đánh giá nào cho sản phẩm này.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sản phẩm liên quan -->
        <?php if ($related_products->num_rows > 0): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-center mb-8">Sản phẩm liên quan</h2>
                <div class="grid md:grid-cols-4 gap-6">
                    <?php while ($related = $related_products->fetch_assoc()): ?>
                        <?php
                        $related_images_json = json_decode($related['images'], true);
                        // Ưu tiên sử dụng trường image nếu có
                        if (!empty($related['image'])) {
                            $related_image = 'uploads/' . $related['image'];
                        } elseif (!empty($related_images_json)) {
                            $related_image = 'uploads/' . $related_images_json[0];
                        } else {
                            $related_image = 'https://via.placeholder.com/300x300?text=No+Image';
                        }
                        $related_price = $related['sale_price'] ?? $related['price'];
                        ?>
                        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition">
                            <a href="product_detail.php?id=<?= $related['id'] ?>">
                                <img src="<?= htmlspecialchars($related_image) ?>" alt="<?= htmlspecialchars($related['NAME']) ?>" 
                                     class="w-full h-48 object-cover rounded-lg mb-4">
                                <h3 class="font-bold mb-2 line-clamp-2"><?= htmlspecialchars($related['NAME']) ?></h3>
                                <p class="text-xl font-bold text-purple-500"><?= number_format($related_price, 0, ',', '.') ?>đ</p>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function changeMainImage(src) {
            document.getElementById('main-image').src = src;
        }

        function showTab(tabName) {
            // Ẩn tất cả tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Bỏ active cho tất cả tab
            document.querySelectorAll('[id$="-tab"]').forEach(tab => {
                tab.classList.remove('border-purple-500', 'text-purple-500');
                tab.classList.add('border-transparent', 'text-gray-600');
            });
            
            // Hiện tab được chọn
            document.getElementById(tabName + '-content').classList.remove('hidden');
            document.getElementById(tabName.substring(0, 4) + '-tab').classList.add('border-purple-500', 'text-purple-500');
            document.getElementById(tabName.substring(0, 4) + '-tab').classList.remove('border-transparent', 'text-gray-600');
        }

        function increaseQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value);
            if (current < max) {
                input.value = current + 1;
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            const current = parseInt(input.value);
            if (current > 1) {
                input.value = current - 1;
            }
        }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
