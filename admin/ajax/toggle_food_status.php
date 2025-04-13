<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra món ăn có tồn tại không
        $stmt = $conn->prepare("SELECT food_id FROM food WHERE food_id = ?");
        $stmt->execute([$_POST['id']]);
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy món ăn!']);
            exit();
        }

        // Cập nhật trạng thái
        $stmt = $conn->prepare("UPDATE food SET status = ? WHERE food_id = ?");
        $success = $stmt->execute([
            $_POST['status'],
            $_POST['id']
        ]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái!'
            ]);
        }
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