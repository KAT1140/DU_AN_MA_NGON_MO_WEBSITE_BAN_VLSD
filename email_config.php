<?php
// Cấu hình email cho tính năng quên mật khẩu sử dụng PHPMailer

// Import PHPMailer classes
require_once 'vendor/phpmailer/Exception.php';
require_once 'vendor/phpmailer/PHPMailer.php';
require_once 'vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cấu hình SMTP Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // ⚠️ THAY ĐỔI EMAIL CỦA BẠN
define('SMTP_PASSWORD', 'your-app-password');    // ⚠️ THAY ĐỔI APP PASSWORD
define('SMTP_ENCRYPTION', 'tls');

// Thông tin người gửi
define('FROM_EMAIL', 'your-email@gmail.com');    // ⚠️ THAY ĐỔI EMAIL CỦA BẠN
define('FROM_NAME', 'VLXD KAT');

// Cấu hình chung
define('SITE_URL', 'http://localhost/vlxd'); // ⚠️ THAY ĐỔI URL WEBSITE
define('RESET_TOKEN_EXPIRY', 3600); // Token hết hạn sau 1 giờ

/**
 * Hàm gửi email reset password sử dụng PHPMailer
 */
function sendResetEmail($to_email, $to_name, $reset_token) {
    $mail = new PHPMailer(true);
    
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Người gửi
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        
        // Người nhận
        $mail->addAddress($to_email, $to_name);
        
        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Đặt lại mật khẩu - VLXD KAT';
        
        $reset_link = SITE_URL . "/reset_password.php?token=" . $reset_token;
        
        $mail->Body = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #f97316, #f59e0b); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                    border-radius: 8px 8px 0 0; 
                }
                .content { 
                    background: #ffffff; 
                    padding: 30px; 
                    border: 1px solid #e5e7eb;
                    border-top: none;
                    border-radius: 0 0 8px 8px; 
                }
                .button { 
                    display: inline-block; 
                    background: #f97316; 
                    color: white !important; 
                    padding: 15px 30px; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    font-weight: bold;
                    font-size: 16px;
                }
                .button:hover { background: #ea580c; }
                .footer { 
                    text-align: center; 
                    margin-top: 20px; 
                    color: #666; 
                    font-size: 12px; 
                    padding: 20px;
                    background: #f9fafb;
                    border-radius: 8px;
                }
                .link-box {
                    background: #f3f4f6;
                    padding: 15px;
                    border-radius: 6px;
                    word-break: break-all;
                    font-family: monospace;
                    font-size: 14px;
                    margin: 15px 0;
                }
                .warning {
                    background: #fef3c7;
                    border: 1px solid #f59e0b;
                    padding: 15px;
                    border-radius: 6px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 28px;'>🏗️ VLXD KAT</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Đặt lại mật khẩu tài khoản</p>
                </div>
                <div class='content'>
                    <p style='font-size: 16px;'>Xin chào <strong>" . htmlspecialchars($to_name ?: $to_email) . "</strong>,</p>
                    
                    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>VLXD KAT</strong>.</p>
                    
                    <p>Để đặt lại mật khẩu, vui lòng nhấp vào nút bên dưới:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $reset_link . "' class='button'>🔐 Đặt lại mật khẩu</a>
                    </div>
                    
                    <p>Hoặc copy và dán link sau vào trình duyệt của bạn:</p>
                    <div class='link-box'>" . $reset_link . "</div>
                    
                    <div class='warning'>
                        <p style='margin: 0;'><strong>⚠️ Lưu ý quan trọng:</strong></p>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>Link này sẽ <strong>hết hạn sau 1 giờ</strong></li>
                            <li>Link chỉ có thể sử dụng <strong>một lần duy nhất</strong></li>
                            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng <strong>bỏ qua email này</strong></li>
                        </ul>
                    </div>
                    
                    <p style='margin-top: 30px;'>Nếu bạn gặp khó khăn, vui lòng liên hệ với chúng tôi qua email hoặc hotline hỗ trợ.</p>
                    
                    <p>Trân trọng,<br><strong>Đội ngũ VLXD KAT</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>&copy; 2025 VLXD KAT - Vật Liệu Xây Dựng Chất Lượng Cao</p>
                    <p style='margin: 5px 0 0 0;'>Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Text version cho email client không hỗ trợ HTML
        $mail->AltBody = "
        VLXD KAT - Đặt lại mật khẩu
        
        Xin chào " . ($to_name ?: $to_email) . ",
        
        Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
        
        Vui lòng truy cập link sau để đặt lại mật khẩu:
        " . $reset_link . "
        
        Lưu ý: Link này sẽ hết hạn sau 1 giờ và chỉ sử dụng được một lần.
        
        Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
        
        Trân trọng,
        Đội ngũ VLXD KAT
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log lỗi để debug
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Kiểm tra cấu hình email
 */
function checkEmailConfig() {
    $errors = [];
    
    if (SMTP_USERNAME === 'your-email@gmail.com') {
        $errors[] = 'Chưa cấu hình SMTP_USERNAME';
    }
    
    if (SMTP_PASSWORD === 'your-app-password') {
        $errors[] = 'Chưa cấu hình SMTP_PASSWORD';
    }
    
    if (FROM_EMAIL === 'your-email@gmail.com') {
        $errors[] = 'Chưa cấu hình FROM_EMAIL';
    }
    
    return $errors;
}
?>