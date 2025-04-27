<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để đánh giá sản phẩm'
    ]);
    exit();
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['food_id']) || !isset($_POST['star_rating']) || !isset($_POST['comment'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

$food_id = (int)$_POST['food_id'];
$rating = (int)$_POST['star_rating']; 
$comment = trim($_POST['comment']);
$action = $_POST['action'] ?? 'add';

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
    
    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $conn->prepare("SELECT food_id FROM food WHERE food_id = ?");
    $stmt->execute([$food_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại'
        ]);
        exit();
    }
    
    // Kiểm tra xem người dùng đã mua và đã nhận sản phẩm này chưa
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM bill_info bi
        JOIN bill b ON bi.id_bill = b.bill_id
        WHERE bi.id_food = ? AND bi.id_account = ? AND b.status = 'Đã giao'
    ");
    $stmt->execute([$food_id, $_SESSION['user_id']]);
    $hasPurchased = $stmt->fetchColumn() > 0;
    
    if (!$hasPurchased) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn chỉ có thể đánh giá sản phẩm đã mua và đã nhận hàng'
        ]);
        exit();
    }
    
    // Kiểm tra xem người dùng đã đánh giá sản phẩm này chưa
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE id_account = ? AND id_food = ?");
    $stmt->execute([$_SESSION['user_id'], $food_id]);
    $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'add' && $existingReview) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đã đánh giá sản phẩm này rồi'
        ]);
        exit();
    }
    
    if ($action === 'edit' && !$existingReview) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đánh giá để chỉnh sửa'
        ]);
        exit();
    }
    
    // Xử lý thêm hoặc cập nhật đánh giá
    if ($action === 'add') {
        $stmt = $conn->prepare("
            INSERT INTO reviews (id_account, id_food, star_rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$_SESSION['user_id'], $food_id, $rating, $comment]);
        
        $message = 'Cảm ơn bạn đã đánh giá sản phẩm!';
    } else {
        $stmt = $conn->prepare("
            UPDATE reviews
            SET star_rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP
            WHERE id_account = ? AND id_food = ?
        ");
        $result = $stmt->execute([$rating, $comment, $_SESSION['user_id'], $food_id]);
        
        $message = 'Đánh giá của bạn đã được cập nhật!';
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi lưu đánh giá'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}