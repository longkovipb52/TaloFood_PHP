<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID danh mục không hợp lệ']);
    exit();
}

try {
    $category_id = intval($_POST['id']);
    
    // Check if category exists
    $check_query = "SELECT foodcategory_id FROM foodcategory WHERE foodcategory_id = :id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Danh mục không tồn tại'
        ]);
        exit();
    }
    
    // Check if category has associated foods
    $food_check_query = "SELECT COUNT(*) FROM food WHERE foodcategory_id = :id";
    $food_check_stmt = $conn->prepare($food_check_query);
    $food_check_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    $food_check_stmt->execute();
    
    if ($food_check_stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa danh mục này vì đang có món ăn thuộc danh mục'
        ]);
        exit();
    }
    
    // Delete category
    $delete_query = "DELETE FROM foodcategory WHERE foodcategory_id = :id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa danh mục'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa danh mục: ' . $e->getMessage()
    ]);
}
?> 