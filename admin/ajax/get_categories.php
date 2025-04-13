<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Prepare the query to get categories with food count
    $query = "SELECT c.foodcategory_id, c.foodcategory_name, c.status, 
              COUNT(f.food_id) as food_count 
              FROM foodcategory c 
              LEFT JOIN food f ON c.foodcategory_id = f.foodcategory_id 
              GROUP BY c.foodcategory_id 
              ORDER BY c.foodcategory_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'foodcategory_id' => $row['foodcategory_id'],
            'foodcategory_name' => $row['foodcategory_name'],
            'status' => $row['status'],
            'food_count' => $row['food_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách danh mục: ' . $e->getMessage()
    ]);
}
?> 