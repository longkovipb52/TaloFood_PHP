<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

if (empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID danh mục không hợp lệ']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Lấy thông tin danh mục và số lượng món ăn
    $stmt = $conn->prepare("
        SELECT fc.*, COUNT(f.food_id) as food_count 
        FROM food_category fc 
        LEFT JOIN food f ON fc.foodcategory_id = f.id_category 
        WHERE fc.foodcategory_id = :id 
        GROUP BY fc.foodcategory_id, fc.foodcategory_name, fc.image
    ");
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy danh mục'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 