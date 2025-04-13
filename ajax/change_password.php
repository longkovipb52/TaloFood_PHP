<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit();
}

$current_password = $_POST['current-password'] ?? '';
$new_password = $_POST['new-password'] ?? '';
$confirm_password = $_POST['confirm-password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin'
    ]);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu xác nhận không khớp'
    ]);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra mật khẩu hiện tại
    $stmt = $conn->prepare("SELECT password FROM account WHERE account_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_hash = $stmt->fetchColumn();

    if (!password_verify($current_password, $current_hash)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu hiện tại không đúng'
        ]);
        exit();
    }

    // Thêm kiểm tra mật khẩu mới không được trùng với mật khẩu hiện tại
    if (password_verify($new_password, $current_hash)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại'
        ]);
        exit();
    }

    // Cập nhật mật khẩu mới
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE account SET password = ? WHERE account_id = ?");
    
    if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
        echo json_encode([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật mật khẩu'
        ]);
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}