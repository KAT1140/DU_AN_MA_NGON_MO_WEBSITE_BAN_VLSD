<?php
session_start();
require_once 'config.php';

// Xử lý Google OAuth callback
if (isset($_POST['credential'])) {
    // Verify Google token
    $credential = $_POST['credential'];
    
    // Decode JWT token
    $parts = explode('.', $credential);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        
        if ($payload) {
            $email = $payload['email'] ?? '';
            $name = $payload['name'] ?? '';
            $picture = $payload['picture'] ?? '';
            $google_id = $payload['sub'] ?? '';
            
            if ($email) {
                // Kiểm tra user đã tồn tại chưa
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // User đã tồn tại - đăng nhập
                    $user = $result->fetch_assoc();
                    
                    // Cập nhật thông tin từ Google
                    $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, avatar_url = ?, google_id = ? WHERE id = ?");
                    $update_stmt->bind_param('sssi', $name, $picture, $google_id, $user['id']);
                    $update_stmt->execute();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                } else {
                    // User mới - tạo tài khoản
                    $role = 'user';
                    $insert_stmt = $conn->prepare("INSERT INTO users (email, full_name, avatar_url, google_id, role) VALUES (?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param('sssss', $email, $name, $picture, $google_id, $role);
                    
                    if ($insert_stmt->execute()) {
                        $new_user_id = $conn->insert_id;
                        
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['logged_in'] = true;
                    }
                }
                
                // Redirect về trang chủ hoặc trang được yêu cầu
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit();
            }
        }
    }
}

// Nếu không có credential, redirect về login
$_SESSION['error'] = 'Đăng nhập thất bại. Vui lòng thử lại.';
header('Location: login.php');
exit();
?>
