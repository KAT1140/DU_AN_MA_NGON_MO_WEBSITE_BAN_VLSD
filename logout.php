<?php
session_start();

// Xóa tất cả session variables
$_SESSION = array();

// Xóa session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hủy session
session_destroy();

// Redirect về trang chủ
header('Location: index.php');
exit();
?>
