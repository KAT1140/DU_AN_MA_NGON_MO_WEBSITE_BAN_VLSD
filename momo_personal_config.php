<?php
/**
 * Cấu hình MoMo cá nhân
 * Dùng cho thanh toán thủ công qua MoMo
 */

// Thông tin MoMo cá nhân
define('MOMO_PERSONAL_PHONE', '0379648264');
define('MOMO_PERSONAL_NAME', 'Võ Nhật Duy Nam');

// Cấu hình hiển thị
define('MOMO_PERSONAL_ENABLED', true); // Bật/tắt thanh toán MoMo cá nhân
define('MOMO_PERSONAL_INSTRUCTIONS', 'Vui lòng chuyển khoản đúng số tiền và nội dung để đơn hàng được xử lý nhanh chóng.');

/**
 * Tạo QR code chuyển tiền MoMo
 */
function generateMoMoPersonalQR($amount, $content) {
    $phone = MOMO_PERSONAL_PHONE;
    $name = MOMO_PERSONAL_NAME;
    
    // Sử dụng VietQR API để tạo QR MoMo
    $qrUrl = "https://img.vietqr.io/image/momo-" . $phone . "-compact2.png" .
             "?amount=" . $amount .
             "&addInfo=" . urlencode($content) .
             "&accountName=" . urlencode($name);
    
    return $qrUrl;
}

/**
 * Tạo deeplink mở MoMo app để chuyển tiền
 */
function generateMoMoPersonalDeeplink($amount, $content) {
    // Deeplink mở app MoMo với thông tin đã điền sẵn
    // Format: momo://transfer (không hỗ trợ prefill thông tin)
    return "momo://";
}

/**
 * Lấy link web MoMo
 */
function getMoMoPersonalWebUrl() {
    return "https://momo.vn";
}
?>
