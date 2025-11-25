<?php
// Xử lý callback Google Sign-In (ID token)
session_start();
include 'config.php';

$token = $_POST['credential'] ?? '';

if (empty($token)) {
    echo "Lỗi: Không tìm thấy credential.";
    exit;
}

// Xác thực ID token bằng endpoint của Google
$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
$resp = @file_get_contents($url);
if ($resp === false) {
    echo "Lỗi khi xác thực token với Google.";
    exit;
}

$data = json_decode($resp, true);
if (!$data || !isset($data['aud'])) {
    echo "Token không hợp lệ.";
    exit;
}

// Kiểm tra audience (client_id)
if ($data['aud'] !== $CLIENT_ID) {
    echo "ID token không dành cho ứng dụng này (aud mismatch).";
    exit;
}

// Kiểm tra thời hạn
if (isset($data['exp']) && $data['exp'] < time()) {
    echo "ID token đã hết hạn.";
    exit;
}

// Lấy thông tin user
$google_id = $data['sub'] ?? null;
$email = $data['email'] ?? null;
$name = $data['name'] ?? ($data['given_name'] ?? '');
$picture = $data['picture'] ?? null;

if (!$google_id || !$email) {
    echo "Thông tin người dùng không đầy đủ từ Google.";
    exit;
}

// Tìm user theo google_id hoặc email (lấy cả role nếu có)
$stmt = $conn->prepare("SELECT id, role FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->bind_param('ss', $google_id, $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();
    $user_id = $user['id'];
    $user_role = $user['role'] ?? 'user';
    // Cập nhật avatar/ tên nếu cần
    $up = $conn->prepare("UPDATE users SET full_name = ?, avatar_url = ? WHERE id = ?");
    $up->bind_param('ssi', $name, $picture, $user_id);
    $up->execute();
} else {
    // Tạo user mới với role = 'user'
    $ins = $conn->prepare("INSERT INTO users (email, full_name, phone, google_id, avatar_url, role, created_at) VALUES (?, ?, '', ?, ?, 'user', NOW())");
    $ins->bind_param('ssss', $email, $name, $google_id, $picture);
    if ($ins->execute()) {
        $user_id = $ins->insert_id;
        $user_role = 'user';
    } else {
        echo "Lỗi khi tạo user: " . $conn->error;
        exit;
    }
}

// Thiết lập session
$_SESSION['user_id'] = $user_id;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;
$_SESSION['logged_in'] = true;
$_SESSION['user_role'] = $user_role ?? 'user';

// Điều hướng về trang chủ
header('Location: index.php');
exit;
?>