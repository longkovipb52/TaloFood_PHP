<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để gửi tin nhắn']);
    exit;
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Lấy dữ liệu từ form
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'];

// Validate dữ liệu
if (empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập nội dung tin nhắn']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Chuẩn bị và thực thi câu lệnh SQL
    $stmt = $conn->prepare("INSERT INTO contact (Message, id_account, status) VALUES (:message, :user_id, 'Chưa xử lý')");
    
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Tin nhắn đã được gửi thành công']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra khi gửi tin nhắn']);
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra, vui lòng thử lại sau']);
} 