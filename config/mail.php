<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private $mail;

    public function __construct() {
        try {
            $this->mail = new PHPMailer(true);
            $this->mail->SMTPDebug = 0;
            
            // Cấu hình SMTP
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = '2124802010046@student.tdmu.edu.vn';
            $this->mail->Password = 'pbit xiku yver cezf';
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = 587;
            
            // Cấu hình email
            $this->mail->setFrom('2124802010046@student.tdmu.edu.vn', 'TaloFood');
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Mailer init error: " . $e->getMessage());
            throw new Exception("Không thể khởi tạo Mailer: " . $e->getMessage());
        }
    }

    public function sendOTP($to, $otp, $action = 'register') {
        try {
            // Clear all recipients and attachments
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->addAddress($to);
            $this->mail->Subject = 'Mã xác thực OTP - TaloFood';
            
            // Chọn template dựa vào action
            if ($action === 'register') {
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #E3B448;'>TaloFood - Xác thực đăng ký tài khoản</h2>
                    <p>Xin chào,</p>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại TaloFood. Để hoàn tất quá trình đăng ký, vui lòng sử dụng mã OTP sau:</p>
                    <div style='background: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                        <strong>{$otp}</strong>
                    </div>
                    <p>Mã OTP này sẽ hết hạn sau 5 phút.</p>
                    <p>Nếu bạn không thực hiện yêu cầu đăng ký này, vui lòng bỏ qua email.</p>
                    <p style='margin-top: 20px;'>Trân trọng,<br>Đội ngũ TaloFood</p>
                </div>
                ";
            } else {
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #E3B448;'>TaloFood - Khôi phục mật khẩu</h2>
                    <p>Xin chào,</p>
                    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Đây là mã OTP để xác thực:</p>
                    <div style='background: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                        <strong>{$otp}</strong>
                    </div>
                    <p>Mã OTP này sẽ hết hạn sau 5 phút.</p>
                    <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này và đảm bảo tài khoản của bạn được bảo mật.</p>
                    <p style='margin-top: 20px;'>Trân trọng,<br>Đội ngũ TaloFood</p>
                </div>
                ";
            }
            
            $this->mail->Body = $body;
            
            // Debug
            error_log("Attempting to send email to: " . $to);
            error_log("Email subject: " . $this->mail->Subject);
            
            $result = $this->mail->send();
            error_log("Email sent successfully: " . ($result ? "true" : "false"));
            return $result;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            throw new Exception("Không thể gửi email: " . $e->getMessage());
        }
    }
} 