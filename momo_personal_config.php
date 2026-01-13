<?php
/**
 * Cấu hình MoMo cá nhân
 * Dùng cho thanh toán thủ công qua MoMo
 */

// Thông tin MoMo cá nhân - Chuyển vào tài khoản ngân hàng
// Dùng VietQR để tạo QR code chuyển khoản ngân hàng qua MoMo
define('MOMO_PERSONAL_PHONE', '0379648264');  // Số điện thoại (dự phòng)
define('MOMO_PERSONAL_NAME', 'Võ Nhật Duy Nam');

// Thông tin ngân hàng để tạo QR code
define('MOMO_BANK_CODE', 'MB');  // Mã ngân hàng MB Bank
define('MOMO_ACCOUNT_NUMBER', '0379648264');  // Số tài khoản ngân hàng
define('MOMO_ACCOUNT_NAME', 'VO NHAT DUY NAM');  // Tên chủ tài khoản

// Cấu hình hiển thị
define('MOMO_PERSONAL_ENABLED', true); // Bật/tắt thanh toán MoMo cá nhân
define('MOMO_PERSONAL_INSTRUCTIONS', 'Vui lòng chuyển khoản đúng số tiền và nội dung để đơn hàng được xử lý nhanh chóng.');

/**
 * Tạo QR code chuyển tiền MoMo vào tài khoản ngân hàng
 */
function generateMoMoPersonalQR($amount, $content) {
    $bankCode = MOMO_BANK_CODE;
    $accountNumber = MOMO_ACCOUNT_NUMBER;
    $accountName = MOMO_ACCOUNT_NAME;
    
    // Sử dụng VietQR API để tạo QR MoMo chuyển vào ngân hàng
    // Format: https://img.vietqr.io/image/{BANKCODE}-{ACCOUNTNO}-compact2.png
    $qrUrl = "https://img.vietqr.io/image/" .
             strtoupper($bankCode) . "-" . $accountNumber . "-compact2.png?" .
             "amount=" . intval($amount) .
             "&addInfo=" . urlencode($content) .
             "&accountName=" . urlencode($accountName);
    
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
