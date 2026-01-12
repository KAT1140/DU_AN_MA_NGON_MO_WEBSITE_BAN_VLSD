<?php
/**
 * Widget hiển thị thông tin tồn kho cho sản phẩm
 * Sử dụng: include 'inventory_widget.php'; echo renderInventoryWidget($product_data);
 */

function renderInventoryWidget($product) {
    $quantity = $product['quantity'];
    $min_quantity = $product['min_quantity'] ?? 0;
    $unit = $product['unit'] ?? 'sản phẩm';
    
    // Xác định trạng thái
    if ($quantity == 0) {
        $status = 'out_of_stock';
        $status_text = 'Hết hàng';
        $status_color = 'red';
        $icon = 'times-circle';
    } elseif ($min_quantity > 0 && $quantity <= $min_quantity) {
        $status = 'low_stock';
        $status_text = 'Sắp hết hàng';
        $status_color = 'yellow';
        $icon = 'exclamation-triangle';
    } else {
        $status = 'in_stock';
        $status_text = 'Còn hàng';
        $status_color = 'green';
        $icon = 'check-circle';
    }
    
    // Tính phần trăm tồn kho (nếu có min_quantity)
    $percentage = 100;
    if ($min_quantity > 0) {
        $percentage = min(100, ($quantity / ($min_quantity * 2)) * 100);
    }
    
    ob_start();
    ?>
    <div class="inventory-widget bg-gray-50 rounded-lg p-4 border-l-4 border-<?= $status_color ?>-500">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-<?= $icon ?> text-<?= $status_color ?>-500"></i>
                <span class="font-semibold text-<?= $status_color ?>-700"><?= $status_text ?></span>
            </div>
            <span class="text-sm text-gray-600">
                <?= number_format($quantity) ?> <?= htmlspecialchars($unit) ?>
            </span>
        </div>
        
        <?php if ($min_quantity > 0 && $status !== 'out_of_stock'): ?>
            <div class="mb-2">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Tồn kho</span>
                    <span><?= number_format($quantity) ?>/<?= number_format($min_quantity * 2) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-<?= $status_color ?>-500 h-2 rounded-full transition-all duration-300" 
                         style="width: <?= $percentage ?>%"></div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($status === 'low_stock'): ?>
            <p class="text-xs text-yellow-600 mt-2">
                <i class="fas fa-info-circle"></i> 
                Chỉ còn <?= number_format($quantity) ?> <?= htmlspecialchars($unit) ?>. Đặt hàng sớm để tránh hết hàng!
            </p>
        <?php elseif ($status === 'out_of_stock'): ?>
            <p class="text-xs text-red-600 mt-2">
                <i class="fas fa-exclamation-circle"></i> 
                Sản phẩm tạm hết hàng. Vui lòng liên hệ để được tư vấn sản phẩm thay thế.
            </p>
        <?php endif; ?>
    </div>
    
    <style>
    .inventory-widget {
        transition: all 0.3s ease;
    }
    .inventory-widget:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Widget đơn giản chỉ hiển thị badge trạng thái
 */
function renderInventoryBadge($product) {
    $quantity = $product['quantity'];
    $min_quantity = $product['min_quantity'] ?? 0;
    
    if ($quantity == 0) {
        return '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                    <i class="fas fa-times-circle"></i> Hết hàng
                </span>';
    } elseif ($min_quantity > 0 && $quantity <= $min_quantity) {
        return '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                    <i class="fas fa-exclamation-triangle"></i> Sắp hết (' . number_format($quantity) . ')
                </span>';
    } else {
        return '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                    <i class="fas fa-check-circle"></i> Còn hàng (' . number_format($quantity) . ')
                </span>';
    }
}

/**
 * Kiểm tra có thể thêm vào giỏ hàng không
 */
function canAddToCart($product, $requested_quantity = 1) {
    return $product['quantity'] >= $requested_quantity;
}

/**
 * Lấy số lượng tối đa có thể đặt
 */
function getMaxOrderQuantity($product) {
    return min($product['quantity'], $product['max_quantity'] ?? 1000);
}
?>
