<?php
/**
 * Cấu hình MoMo Payment Gateway
 * Tài liệu API: https://developers.momo.vn/v3/
 */

// MoMo API Configuration - Updated credentials
define('MOMO_PARTNER_CODE', 'MOMOBKUN20180529'); // Partner Code từ MoMo
define('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j'); // Access Key từ MoMo  
define('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'); // Secret Key từ MoMo

// MoMo API URLs
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'); // Sandbox URL
define('MOMO_QUERY_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/query'); // Query transaction status

// Callback URLs (cần thay đổi theo domain thực tế)
define('MOMO_RETURN_URL', 'http://localhost:8000/momo_return.php'); // URL khách hàng quay lại sau thanh toán
define('MOMO_NOTIFY_URL', 'http://localhost:8000/momo_callback.php'); // URL nhận callback từ MoMo

// Payment Configuration
define('MOMO_REQUEST_TYPE', 'captureWallet'); // Loại thanh toán: captureWallet để lấy QR code
define('MOMO_ORDER_INFO', 'Thanh toán đơn hàng VLXD Store'); // Thông tin đơn hàng mặc định
define('MOMO_EXTRA_DATA', ''); // Dữ liệu bổ sung (có thể để trống)

// Timeout Configuration
define('MOMO_TIMEOUT_MINUTES', 15); // Thời gian timeout cho giao dịch (phút)

// Transaction Status
define('MOMO_STATUS_PENDING', 'pending');
define('MOMO_STATUS_SUCCESS', 'success');
define('MOMO_STATUS_FAILED', 'failed');
define('MOMO_STATUS_TIMEOUT', 'timeout');
define('MOMO_STATUS_CANCELLED', 'cancelled');

/**
 * Lấy URL callback đầy đủ
 */
function getMoMoCallbackUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    return $protocol . '://' . $host . '/momo_callback.php';
}

/**
 * Lấy URL return đầy đủ
 */
function getMoMoReturnUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    return $protocol . '://' . $host . '/momo_return.php';
}

/**
 * Tạo request ID duy nhất
 */
function generateMoMoRequestId() {
    return 'VLXD_' . date('YmdHis') . '_' . uniqid();
}

/**
 * Tạo order ID duy nhất cho MoMo
 */
function generateMoMoOrderId() {
    return 'ORDER_' . date('YmdHis') . '_' . uniqid();
}

/**
 * Validate MoMo configuration
 */
function validateMoMoConfig() {
    $required_constants = [
        'MOMO_PARTNER_CODE',
        'MOMO_ACCESS_KEY', 
        'MOMO_SECRET_KEY',
        'MOMO_ENDPOINT'
    ];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant) || empty(constant($constant))) {
            throw new Exception("MoMo configuration missing: {$constant}");
        }
    }
    
    return true;
}
?>