<?php
require_once '../../config/database.php';
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin ID liên hệ'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Xóa liên hệ
    $stmt = $conn->prepare("
        DELETE FROM contact 
        WHERE contact_id = :id
    ");
    
    if ($stmt->execute(['id' => $_POST['id']])) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa liên hệ thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa liên hệ'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
} 