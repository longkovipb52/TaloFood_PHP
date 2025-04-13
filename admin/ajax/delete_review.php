<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit();
}

// Kiểm tra review_id
if (!isset($_POST['review_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin đánh giá'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra xem đánh giá có tồn tại không
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$_POST['review_id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đánh giá'
        ]);
        exit();
    }

    // Xóa đánh giá
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->execute([$_POST['review_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa đánh giá thành công'
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa đánh giá: ' . $e->getMessage()
    ]);
}
