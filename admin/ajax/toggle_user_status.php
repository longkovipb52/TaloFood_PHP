<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/mail.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Lấy thông tin người dùng trước khi cập nhật
        $stmt = $conn->prepare("SELECT * FROM account WHERE account_id = ?");
        $stmt->execute([$_POST['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
            exit();
        }
        
        // Cập nhật trạng thái tài khoản
        $stmt = $conn->prepare("UPDATE account SET status = ?, login_attempts = 0, locked_until = NULL WHERE account_id = ?");
        $success = $stmt->execute([$_POST['status'], $_POST['id']]);
        
        // Nếu thành công và status = 1 (mở khóa), gửi email thông báo cho người dùng
        if ($success && $_POST['status'] == 1 && !empty($user['email'])) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
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
                $mail->setFrom('2124802010046@student.tdmu.edu.vn', 'TaloFood System');
                $mail->addAddress($user['email']);
                
                // Nội dung email
                $mail->isHTML(true);
                $mail->Subject = 'Tài khoản của bạn đã được mở khóa - TaloFood';
                
                $body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <h2 style="color: #E3B448;">TaloFood - Tài khoản đã mở khóa</h2>
                    <p>Xin chào ' . htmlspecialchars($user['username']) . ',</p>
                    <p>Chúng tôi vui mừng thông báo rằng tài khoản của bạn trên hệ thống TaloFood đã được mở khóa.</p>
                    
                    <div style="background: #f0f7ff; padding: 15px; margin: 20px 0; border-left: 4px solid #2196F3;">
                        <p>Bạn đã có thể đăng nhập vào hệ thống bình thường và tiếp tục sử dụng các dịch vụ của TaloFood.</p>
                    </div>
                    
                    <div style="margin: 20px 0; text-align: center;">
                        <a href="http://localhost:8080/TaloFood/login.php" 
                           style="background-color: #4CAF50; color: white; padding: 10px 15px; 
                                  text-decoration: none; border-radius: 4px; display: inline-block;">
                            Đăng nhập ngay
                        </a>
                    </div>
                    
                    <p>Nếu bạn gặp bất kỳ vấn đề nào, vui lòng liên hệ với chúng tôi qua email hỗ trợ.</p>
                    
                    <p style="margin-top: 20px;">Trân trọng,<br>Đội ngũ TaloFood</p>
                </div>';
                
                $mail->Body = $body;
                $mail->send();
                
                // Thêm thông tin gửi email thành công vào kết quả trả về
                echo json_encode([
                    'success' => true, 
                    'emailSent' => true,
                    'message' => 'Đã mở khóa tài khoản và thông báo cho người dùng'
                ]);
                exit();
                
            } catch (Exception $e) {
                // Vẫn trả về thành công nếu cập nhật DB thành công, chỉ gửi email thất bại
                echo json_encode([
                    'success' => true, 
                    'emailSent' => false,
                    'message' => 'Đã mở khóa tài khoản nhưng không thể gửi email thông báo'
                ]);
                exit();
            }
        }
        
        echo json_encode(['success' => $success]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}