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
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID liên hệ!'
    ]);
    exit();
}

$contact_id = $_GET['id'];

try {
    // Tạo kết nối database
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy thông tin chi tiết liên hệ
    $stmt = $conn->prepare("
        SELECT c.*, a.username, a.email, a.phone 
        FROM contact c 
        JOIN account a ON c.id_account = a.account_id 
        WHERE c.contact_id = ?
    ");
    
    $stmt->execute([$contact_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($contact) {
        echo json_encode([
            'success' => true,
            'contact' => $contact
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
        'message' => 'Lỗi khi lấy thông tin: ' . $e->getMessage()
    ]);
}
?> 