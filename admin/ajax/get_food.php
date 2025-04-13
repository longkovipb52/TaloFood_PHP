<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập!'
    ]);
    exit();
}

// Kiểm tra ID món ăn
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID món ăn!'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy thông tin món ăn kèm tên danh mục
    $stmt = $conn->prepare("
        SELECT f.*, fc.foodcategory_name 
        FROM food f 
        LEFT JOIN food_category fc ON f.id_category = fc.foodcategory_id 
        WHERE f.food_id = ?
    ");
    
    $stmt->execute([$_GET['id']]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($food) {
        // Format giá tiền
        $food['price_formatted'] = number_format($food['price'], 0, ',', '.') . 'đ';
        $food['new_price_formatted'] = $food['new_price'] ? number_format($food['new_price'], 0, ',', '.') . 'đ' : null;

        echo json_encode([
            'success' => true,
            'food' => $food
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy món ăn!'
        ]);
    }

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 