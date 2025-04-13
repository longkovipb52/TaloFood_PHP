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
if (!isset($_GET['review_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin đánh giá'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy thông tin chi tiết đánh giá
    $stmt = $conn->prepare("
        SELECT r.*, a.username, f.food_name, f.image as food_image
        FROM reviews r
        LEFT JOIN account a ON r.id_account = a.account_id
        LEFT JOIN food f ON r.id_food = f.food_id
        WHERE r.review_id = ?
    ");
    
    $stmt->execute([$_GET['review_id']]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($review) {
        // Tạo HTML cho stars
        $stars_html = '';
        for ($i = 0; $i < 5; $i++) {
            if ($i < $review['star_rating']) {
                $stars_html .= '<i class="fas fa-star"></i>';
            } else {
                $stars_html .= '<i class="far fa-star"></i>';
            }
        }

        // Format created_at
        $created_at_formatted = date('d/m/Y H:i', strtotime($review['created_at']));

        // Chuẩn bị URL hình ảnh
        $food_image_url = $review['food_image'] ? '../uploads/foods/' . $review['food_image'] : '';

        echo json_encode([
            'success' => true,
            'data' => [
                'review_id' => $review['review_id'],
                'food_name' => $review['food_name'],
                'food_image_url' => $food_image_url,
                'username' => $review['username'],
                'star_rating' => $review['star_rating'],
                'stars_html' => $stars_html,
                'comment' => $review['comment'],
                'created_at_formatted' => $created_at_formatted
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đánh giá'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy thông tin đánh giá: ' . $e->getMessage()
    ]);
}