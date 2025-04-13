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

if (!isset($data['review_id']) || !isset($data['rating']) || !isset($data['comment'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

$review_id = (int)$data['review_id'];
$rating = (int)$data['rating'];
$comment = trim($data['comment']);

// Kiểm tra giá trị đánh giá
if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Đánh giá phải từ 1 đến 5 sao'
    ]);
    exit();
}

// Kiểm tra nội dung nhận xét
if (empty($comment)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập nhận xét của bạn'
    ]);
    exit();
}

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
            'message' => 'Bạn không có quyền sửa đánh giá này'
        ]);
        exit();
    }
    
    // Cập nhật đánh giá
    $stmt = $conn->prepare("UPDATE reviews SET star_rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP WHERE review_id = ?");
    $result = $stmt->execute([$rating, $comment, $review_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật đánh giá thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi cập nhật đánh giá'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}