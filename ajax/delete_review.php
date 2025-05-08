<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này'
    ]);
    exit;
}

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu
if (!isset($data['review_id']) || !is_numeric($data['review_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

$review_id = intval($data['review_id']);
$user_id = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

try {
    // Kiểm tra đánh giá có thuộc về người dùng này không
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE review_id = ? AND id_account = ?");
    $stmt->execute([$review_id, $user_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đánh giá hoặc bạn không có quyền xóa đánh giá này'
        ]);
        exit;
    }
    
    // Thực hiện xóa đánh giá
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $result = $stmt->execute([$review_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa đánh giá thành công'
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