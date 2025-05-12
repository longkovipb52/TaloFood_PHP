<?php
session_start();
require_once '../config/database.php';
require_once '../config/mail.php';

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ!'
    ]);
    exit;
}

// Lấy dữ liệu từ request
$username = trim($_POST['username'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$email = trim($_POST['email'] ?? '');

// Kiểm tra dữ liệu cơ bản
if (empty($username) || empty($email) || empty($reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin!'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email không hợp lệ!'
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra tài khoản có tồn tại không
    $stmt = $conn->prepare("SELECT * FROM Account WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản!'
        ]);
        exit;
    }
    
    // Kiểm tra email có khớp với email đăng ký không
    if ($user['email'] !== $email) {
        echo json_encode([
            'success' => false,
            'message' => 'Email không khớp với email đã đăng ký cho tài khoản này!'
        ]);
        exit;
    }

    // Kiểm tra tài khoản có bị khóa không
    if ($user['locked_until'] === null && $user['status'] == 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản này không bị khóa!'
        ]);
        exit;
    }
    
    // Lấy email của admin từ database
    $stmt = $conn->prepare("SELECT email FROM Account WHERE id_role = 1 LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy admin để gửi yêu cầu!'
        ]);
        exit;
    }
    
    // Chuẩn bị gửi email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '2124802010046@student.tdmu.edu.vn'; 
        $mail->Password = 'pbit xiku yver cezf'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Người gửi và người nhận
        $mail->setFrom('your-email@gmail.com', 'TaloFood System');
        $mail->addAddress($admin['email']);
        
        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Yêu cầu mở khóa tài khoản - TaloFood';
        
        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #E3B448;">TaloFood - Yêu cầu mở khóa tài khoản</h2>
            <p>Xin chào Quản trị viên,</p>
            <p>Một người dùng đã gửi yêu cầu mở khóa tài khoản trên hệ thống TaloFood.</p>
            
            <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                <p><strong>Thông tin tài khoản:</strong></p>
                <ul>
                    <li>Tên đăng nhập: '.$user['username'].'</li>
                    <li>ID tài khoản: '.$user['account_id'].'</li>
                    <li>Email đăng ký: '.$user['email'].'</li>
                </ul>
                
                <p><strong>Thông tin liên hệ:</strong></p>
                <ul>
                    <li>Email liên hệ: '.$email.'</li>
                </ul>
                
                <p><strong>Lý do yêu cầu mở khóa:</strong></p>
                <p style="font-style: italic;">'.nl2br(htmlspecialchars($reason)).'</p>
            </div>
            
            <div style="margin: 20px 0; text-align: center;">
                <a href="http://localhost:8080/TaloFood/admin/users.php" 
                   style="background-color: #4CAF50; color: white; padding: 10px 15px; 
                          text-decoration: none; border-radius: 4px; display: inline-block;">
                    Truy cập trang quản lý người dùng
                </a>
            </div>
            
            <p style="margin-top: 20px;">Trân trọng,<br>Hệ thống TaloFood</p>
        </div>';
        
        $mail->Body = $body;
        
        // Gửi email
        $email_sent = $mail->send();
        
        if ($email_sent) {
            // Lưu thông tin yêu cầu vào session để tránh gửi nhiều lần
            if (!isset($_SESSION['unlock_requests'])) {
                $_SESSION['unlock_requests'] = [];
            }
            $_SESSION['unlock_requests'][$user['account_id']] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Yêu cầu mở khóa đã được gửi đến quản trị viên! Vui lòng chờ phản hồi từ email.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gửi email yêu cầu. Vui lòng thử lại sau!'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi gửi email: ' . $mail->ErrorInfo
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}