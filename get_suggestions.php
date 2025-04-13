<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    echo json_encode(['suggestions' => [], 'popularItems' => []]);
    exit;
}

$term = '%' . $_GET['term'] . '%';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Tìm kiếm món ăn và danh mục phù hợp với từ khóa
    $stmt = $conn->prepare("
        SELECT DISTINCT f.food_name, c.foodcategory_name 
        FROM food f 
        JOIN food_category c ON f.id_category = c.foodcategory_id 
        WHERE f.food_name LIKE ? OR c.foodcategory_name LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$term, $term]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy 5 món ăn phổ biến nhất (bán chạy)
    $stmt = $conn->prepare("
        SELECT f.food_name, c.foodcategory_name
        FROM food f
        JOIN food_category c ON f.id_category = c.foodcategory_id
        JOIN bill_info bi ON f.food_id = bi.id_food
        GROUP BY f.food_id
        ORDER BY SUM(bi.count) DESC
        LIMIT 5
    ");
    $stmt->execute();
    $popularItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'suggestions' => $suggestions,
        'popularItems' => $popularItems
    ]);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error', 'suggestions' => [], 'popularItems' => []]);
}
?>