<?php
/**
 * Các hàm xử lý inventory cho hệ thống VLXD
 */

/**
 * Cập nhật inventory khi có đơn hàng mới
 * @param mysqli $conn - Kết nối database
 * @param int $order_id - ID đơn hàng
 * @param int $user_id - ID người tạo (có thể null cho hệ thống)
 * @return bool - True nếu thành công
 */
function updateInventoryForOrder($conn, $order_id, $user_id = null) {
    try {
        // Lấy danh sách sản phẩm trong đơn hàng
        $order_items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($order_items_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity_sold = $item['quantity'];
            
            // Lấy thông tin sản phẩm hiện tại
            $product_query = "SELECT quantity, NAME FROM products WHERE id = ?";
            $product_stmt = $conn->prepare($product_query);
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            
            if ($product_data = $product_result->fetch_assoc()) {
                $current_quantity = $product_data['quantity'];
                $new_quantity = $current_quantity - $quantity_sold;
                
                // Đảm bảo không âm
                if ($new_quantity < 0) {
                    $new_quantity = 0;
                }
                
                // Cập nhật số lượng trong bảng products
                $update_product = "UPDATE products SET quantity = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_product);
                $update_stmt->bind_param("ii", $new_quantity, $product_id);
                $update_stmt->execute();
                
                // Thêm record vào bảng inventory
                $inventory_note = "Bán hàng - Đơn hàng #" . $order_id;
                $insert_inventory = "INSERT INTO inventory (product_id, quantity_change, current_quantity, TYPE, reference_id, reference_type, note, created_by) VALUES (?, ?, ?, 'sold', ?, 'order', ?, ?)";
                $inventory_stmt = $conn->prepare($insert_inventory);
                $inventory_stmt->bind_param("iiissi", $product_id, $quantity_sold, $new_quantity, $order_id, $inventory_note, $user_id);
                $inventory_stmt->execute();
                
                $inventory_stmt->close();
                $update_stmt->close();
            }
            $product_stmt->close();
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Lỗi cập nhật inventory: " . $e->getMessage());
        return false;
    }
}

/**
 * Hoàn trả inventory khi hủy đơn hàng
 * @param mysqli $conn - Kết nối database
 * @param int $order_id - ID đơn hàng
 * @param int $user_id - ID người thực hiện
 * @return bool - True nếu thành công
 */
function restoreInventoryForCancelledOrder($conn, $order_id, $user_id = null) {
    try {
        // Lấy danh sách sản phẩm trong đơn hàng đã hủy
        $order_items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($order_items_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity_return = $item['quantity'];
            
            // Lấy số lượng hiện tại
            $product_query = "SELECT quantity FROM products WHERE id = ?";
            $product_stmt = $conn->prepare($product_query);
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            
            if ($product_data = $product_result->fetch_assoc()) {
                $current_quantity = $product_data['quantity'];
                $new_quantity = $current_quantity + $quantity_return;
                
                // Cập nhật số lượng trong bảng products
                $update_product = "UPDATE products SET quantity = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_product);
                $update_stmt->bind_param("ii", $new_quantity, $product_id);
                $update_stmt->execute();
                
                // Thêm record vào bảng inventory
                $inventory_note = "Hoàn trả - Hủy đơn hàng #" . $order_id;
                $insert_inventory = "INSERT INTO inventory (product_id, quantity_change, current_quantity, TYPE, reference_id, reference_type, note, created_by) VALUES (?, ?, ?, 'return', ?, 'order', ?, ?)";
                $inventory_stmt = $conn->prepare($insert_inventory);
                $inventory_stmt->bind_param("iiissi", $product_id, $quantity_return, $new_quantity, $order_id, $inventory_note, $user_id);
                $inventory_stmt->execute();
                
                $inventory_stmt->close();
                $update_stmt->close();
            }
            $product_stmt->close();
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Lỗi hoàn trả inventory: " . $e->getMessage());
        return false;
    }
}

/**
 * Kiểm tra tồn kho trước khi đặt hàng
 * @param mysqli $conn - Kết nối database
 * @param array $cart_items - Mảng sản phẩm [product_id => quantity]
 * @return array - Mảng kết quả ['success' => bool, 'message' => string, 'out_of_stock' => array]
 */
function checkInventoryAvailability($conn, $cart_items) {
    $out_of_stock = [];
    $insufficient_stock = [];
    
    foreach ($cart_items as $product_id => $requested_quantity) {
        $query = "SELECT NAME, quantity FROM products WHERE id = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($product = $result->fetch_assoc()) {
            if ($product['quantity'] == 0) {
                $out_of_stock[] = $product['NAME'];
            } elseif ($product['quantity'] < $requested_quantity) {
                $insufficient_stock[] = [
                    'name' => $product['NAME'],
                    'available' => $product['quantity'],
                    'requested' => $requested_quantity
                ];
            }
        }
        $stmt->close();
    }
    
    if (!empty($out_of_stock) || !empty($insufficient_stock)) {
        $message = "";
        if (!empty($out_of_stock)) {
            $message .= "Sản phẩm hết hàng: " . implode(", ", $out_of_stock) . ". ";
        }
        if (!empty($insufficient_stock)) {
            $insufficient_messages = [];
            foreach ($insufficient_stock as $item) {
                $insufficient_messages[] = $item['name'] . " (còn " . $item['available'] . ", yêu cầu " . $item['requested'] . ")";
            }
            $message .= "Không đủ hàng: " . implode(", ", $insufficient_messages) . ".";
        }
        
        return [
            'success' => false,
            'message' => $message,
            'out_of_stock' => $out_of_stock,
            'insufficient_stock' => $insufficient_stock
        ];
    }
    
    return ['success' => true, 'message' => 'Tất cả sản phẩm đều có sẵn'];
}

/**
 * Lấy thống kê inventory nhanh
 * @param mysqli $conn - Kết nối database
 * @return array - Thống kê
 */
function getInventoryStats($conn) {
    $stats = [];
    
    // Tổng sản phẩm
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $stats['total_products'] = $result->fetch_assoc()['total'];
    
    // Sản phẩm sắp hết hàng
    $result = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE status = 'active' AND quantity <= min_quantity AND min_quantity > 0");
    $stats['low_stock'] = $result->fetch_assoc()['low_stock'];
    
    // Sản phẩm hết hàng
    $result = $conn->query("SELECT COUNT(*) as out_of_stock FROM products WHERE status = 'active' AND quantity = 0");
    $stats['out_of_stock'] = $result->fetch_assoc()['out_of_stock'];
    
    // Tổng giá trị tồn kho
    $result = $conn->query("SELECT SUM(quantity * cost_price) as total_value FROM products WHERE status = 'active' AND cost_price > 0");
    $stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;
    
    return $stats;
}

/**
 * Lấy danh sách sản phẩm sắp hết hàng
 * @param mysqli $conn - Kết nối database
 * @param int $limit - Giới hạn số lượng
 * @return array - Danh sách sản phẩm
 */
function getLowStockProducts($conn, $limit = 10) {
    $query = "
        SELECT p.id, p.NAME, p.sku, p.quantity, p.min_quantity, c.NAME as category_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        AND p.quantity <= p.min_quantity 
        AND p.min_quantity > 0
        ORDER BY (p.quantity / p.min_quantity) ASC, p.quantity ASC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

/**
 * Tạo cảnh báo inventory cho admin
 * @param mysqli $conn - Kết nối database
 * @return array - Danh sách cảnh báo
 */
function getInventoryAlerts($conn) {
    $alerts = [];
    
    // Sản phẩm hết hàng
    $out_of_stock = $conn->query("
        SELECT NAME, sku FROM products 
        WHERE status = 'active' AND quantity = 0
        ORDER BY NAME
    ");
    
    if ($out_of_stock->num_rows > 0) {
        $products = [];
        while ($product = $out_of_stock->fetch_assoc()) {
            $products[] = $product['NAME'] . " (" . $product['sku'] . ")";
        }
        $alerts[] = [
            'type' => 'danger',
            'title' => 'Sản phẩm hết hàng',
            'message' => 'Có ' . count($products) . ' sản phẩm đã hết hàng: ' . implode(', ', array_slice($products, 0, 3)) . (count($products) > 3 ? '...' : ''),
            'count' => count($products)
        ];
    }
    
    // Sản phẩm sắp hết hàng
    $low_stock = $conn->query("
        SELECT NAME, sku, quantity, min_quantity FROM products 
        WHERE status = 'active' 
        AND quantity <= min_quantity 
        AND min_quantity > 0 
        AND quantity > 0
        ORDER BY (quantity / min_quantity) ASC
    ");
    
    if ($low_stock->num_rows > 0) {
        $products = [];
        while ($product = $low_stock->fetch_assoc()) {
            $products[] = $product['NAME'] . " (còn " . $product['quantity'] . "/" . $product['min_quantity'] . ")";
        }
        $alerts[] = [
            'type' => 'warning',
            'title' => 'Sản phẩm sắp hết hàng',
            'message' => 'Có ' . count($products) . ' sản phẩm sắp hết hàng: ' . implode(', ', array_slice($products, 0, 3)) . (count($products) > 3 ? '...' : ''),
            'count' => count($products)
        ];
    }
    
    return $alerts;
}
?>