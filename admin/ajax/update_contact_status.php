<?php
require_once '../../config/database.php';
require_once '../includes/contact_status.php';
session_start();

// Kiểm tra đăng nhập và role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập!'
    ]);
    exit();
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['contact_id']) || !isset($_POST['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin!'
    ]);
    exit();
}

$contact_id = $_POST['contact_id'];
$status = $_POST['status'];

// Kiểm tra trạng thái hợp lệ
if (!is_valid_contact_status($status)) {
    echo json_encode([
        'success' => false,
        'message' => 'Trạng thái không hợp lệ!'
    ]);
    exit();
}

try {
    // Tạo kết nối database
    $database = new Database();
    $conn = $database->getConnection();

    // Cập nhật trạng thái
    $stmt = $conn->prepare("
        UPDATE contact 
        SET status = ? 
        WHERE contact_id = ?
    ");
    
    $stmt->execute([$status, $contact_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy liên hệ!'
        ]);
    }

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật: ' . $e->getMessage()
    ]);
}
?> 