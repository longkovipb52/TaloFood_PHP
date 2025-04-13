<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    $email = $_POST['email'] ?? '';
    $action = $_POST['action'] ?? 'register'; // 'register' hoặc 'forgot'
    
    if (empty($email)) {
        $response['message'] = 'Vui lòng nhập email!';
        echo json_encode($response);
        exit;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Kiểm tra email trong database
        $stmt = $conn->prepare("SELECT account_id FROM account WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->rowCount() > 0;

        // Xử lý theo action
        if ($action === 'register' && $emailExists) {
            $response['message'] = 'Email đã được sử dụng';
            echo json_encode($response);
            exit;
        } elseif ($action === 'forgot' && !$emailExists) {
            $response['message'] = 'Email không tồn tại trong hệ thống';
            echo json_encode($response);
            exit;
        }

        // Tạo mã OTP 6 chữ số
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Lưu mã OTP vào session
        $_SESSION['register_otp'] = $otp;
        $_SESSION['register_email'] = $email;
        $_SESSION['otp_expiry'] = time() + 300; // 5 phút

        // Gửi email sử dụng Mailer class
        try {
            $mailer = new Mailer();
            if ($mailer->sendOTP($email, $otp, $action)) {
                $response['success'] = true;
                $response['message'] = 'Mã xác thực đã được gửi đến email của bạn';
            } else {
                throw new Exception("Không thể gửi email");
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            $response['message'] = 'Không thể gửi email: ' . $e->getMessage();
        }

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}