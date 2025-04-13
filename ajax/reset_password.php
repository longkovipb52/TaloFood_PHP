<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra xác thực
    if (!isset($_SESSION['reset_password_verified']) || $_SESSION['reset_password_verified'] !== true) {
        echo json_encode([
            'success' => false,
            'message' => 'Phiên làm việc đã hết hạn. Vui lòng thực hiện lại quá trình khôi phục mật khẩu.'
        ]);
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ thông tin'
        ]);
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu mới
        $stmt = $conn->prepare("UPDATE account SET password = ? WHERE email = ?");
        if ($stmt->execute([$hashed_password, $email])) {
            // Xóa session xác thực
            unset($_SESSION['reset_password_verified']);
            unset($_SESSION['register_otp']);
            unset($_SESSION['register_email']);
            unset($_SESSION['otp_expiry']);

            echo json_encode([
                'success' => true,
                'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật mật khẩu. Vui lòng thử lại!'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}