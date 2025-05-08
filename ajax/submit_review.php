<?php
// filepath: c:\xampp\htdocs\TaloFood\ajax\submit_review.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để đánh giá sản phẩm'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
    $bill_info_id = isset($_POST['bill_info_id']) ? intval($_POST['bill_info_id']) : 0;
    $rating = isset($_POST['star_rating']) ? intval($_POST['star_rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    
    if ($food_id <= 0 || $bill_info_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ'
        ]);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng chọn số sao đánh giá từ 1-5'
        ]);
        exit;
    }
    
    if (empty($comment)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập nhận xét của bạn'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    
    try {
        if ($action == 'add') {
            // Kiểm tra xem đơn hàng này đã được đánh giá chưa
            $stmt = $conn->prepare("SELECT * FROM reviews WHERE id_account = ? AND id_food = ? AND id_bill_info = ?");
            $stmt->execute([$user_id, $food_id, $bill_info_id]);
            
            if ($stmt->rowCount() > 0) {
                // Nếu đã đánh giá, cập nhật đánh giá
                $stmt = $conn->prepare("UPDATE reviews SET star_rating = ?, comment = ?, created_at = NOW() 
                                     WHERE id_account = ? AND id_food = ? AND id_bill_info = ?");
                $result = $stmt->execute([$rating, $comment, $user_id, $food_id, $bill_info_id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cập nhật đánh giá thành công!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra khi cập nhật đánh giá'
                    ]);
                }
            } else {
                // Nếu chưa đánh giá, thêm mới
                $stmt = $conn->prepare("INSERT INTO reviews (id_account, id_food, id_bill_info, star_rating, comment) 
                                     VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$user_id, $food_id, $bill_info_id, $rating, $comment]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Gửi đánh giá thành công!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra khi gửi đánh giá'
                    ]);
                }
            }
        } else if ($action == 'edit') {
            // Cập nhật đánh giá hiện có
            $stmt = $conn->prepare("UPDATE reviews SET star_rating = ?, comment = ? 
                                 WHERE id_account = ? AND id_food = ? AND id_bill_info = ?");
            $result = $stmt->execute([$rating, $comment, $user_id, $food_id, $bill_info_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cập nhật đánh giá thành công!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật đánh giá'
                ]);
            }
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}