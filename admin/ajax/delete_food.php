<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

// Kiểm tra và lấy ID món ăn
$food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
if ($food_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID món ăn không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra xem món ăn có tồn tại không
    $stmt = $conn->prepare("SELECT image FROM food WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food) {
        echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
        exit;
    }

    // Xóa ảnh cũ nếu có
    if (!empty($food['image']) && file_exists('../../uploads/foods/' . $food['image'])) {
        unlink('../../uploads/foods/' . $food['image']);
    }

    // Xóa món ăn
    $stmt = $conn->prepare("DELETE FROM food WHERE food_id = ?");
    $stmt->execute([$food_id]);

    echo json_encode(['success' => true, 'message' => 'Xóa món ăn thành công']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa món ăn: ' . $e->getMessage()]);
} 