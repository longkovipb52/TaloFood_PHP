<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
    ]);
    exit();
}

// Lấy dữ liệu từ POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['review_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

$review_id = (int)$data['review_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Kiểm tra quyền sở hữu đánh giá
    $stmt = $conn->prepare("SELECT id_account FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || $result['id_account'] != $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền xóa đánh giá này'
        ]);
        exit();
    }
    
    // Xóa đánh giá
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $result = $stmt->execute([$review_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa đánh giá thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi xóa đánh giá'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}