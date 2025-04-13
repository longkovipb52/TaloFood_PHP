<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bill_id'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Lấy thông tin chi tiết đơn hàng
        $stmt = $conn->prepare("
            SELECT bi.id_food, bi.count 
            FROM bill_info bi 
            WHERE bi.id_bill = ?
        ");
        $stmt->execute([$_POST['bill_id']]);
        $billItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cập nhật total_sold cho từng món
        foreach ($billItems as $item) {
            $stmt = $conn->prepare("
                UPDATE food 
                SET total_sold = total_sold + ? 
                WHERE food_id = ?
            ");
            $stmt->execute([$item['count'], $item['id_food']]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật số lượng bán thành công!'
        ]);
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]);
}
?> 