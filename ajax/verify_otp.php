<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $email = $_POST['email'] ?? '';
    $action = $_POST['action'] ?? 'register'; // 'register' hoặc 'forgot'
    
    if (empty($otp) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        exit;
    }

    // Kiểm tra thời gian hết hạn
    if (!isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
        echo json_encode(['success' => false, 'message' => 'Mã xác thực đã hết hạn']);
        exit;
    }

    // Kiểm tra email và mã OTP
    if (!isset($_SESSION['register_otp']) || 
        !isset($_SESSION['register_email']) || 
        $_SESSION['register_otp'] !== $otp || 
        $_SESSION['register_email'] !== $email) {
        echo json_encode(['success' => false, 'message' => 'Mã xác thực không đúng']);
        exit;
    }

    // Xác thực thành công
    if ($action === 'register') {
        $_SESSION['email_verified'] = true;
    } else {
        $_SESSION['reset_password_verified'] = true;
    }
    
    echo json_encode(['success' => true, 'message' => 'Xác thực email thành công']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}