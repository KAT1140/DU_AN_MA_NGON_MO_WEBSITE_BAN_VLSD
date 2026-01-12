<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ email và mật khẩu!';
        header('Location: login.php');
        exit();
    }

    // Prepared statement để tránh SQL injection
    $stmt = $conn->prepare("SELECT id, email, PASSWORD, full_name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['PASSWORD'])) {
            // Đăng nhập thành công
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect về trang trước đó hoặc trang chủ
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit();
        } else {
            $_SESSION['error'] = 'Mật khẩu không đúng!';
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Email không tồn tại hoặc tài khoản đã bị khóa!';
        header('Location: login.php');
        exit();
    }

    $stmt->close();
} else {
    header('Location: login.php');
    exit();
}
?>
