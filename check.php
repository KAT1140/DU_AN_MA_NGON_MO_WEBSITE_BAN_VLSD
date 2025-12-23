<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ email và mật khẩu!';
        header('Location: login.php');
        exit();
    }
    
    // Kiểm tra user trong database - Dùng prepared statement để tránh SQL injection
    $sql = "SELECT id, email, PASSWORD, full_name, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu
        if (password_verify($password, $user['PASSWORD'])) {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['logged_in'] = true;
            
            // Redirect về trang chủ
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['error'] = 'Mật khẩu không đúng!';
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Email không tồn tại!';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>
