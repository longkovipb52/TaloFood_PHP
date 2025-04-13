<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $userId = $_POST['id'];
    
    // Không cho phép xóa tài khoản admin
    if ($userId == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của chính mình']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra xem người dùng có tồn tại không
        $stmt = $conn->prepare("SELECT id_role FROM account WHERE account_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
            exit();
        }

        // Không cho phép xóa tài khoản admin khác
        if ($user['id_role'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản admin']);
            exit();
        }

        // Xóa người dùng
        $stmt = $conn->prepare("DELETE FROM account WHERE account_id = ?");
        $success = $stmt->execute([$userId]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa người dùng thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa người dùng'
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
        'message' => 'Phương thức không được hỗ trợ hoặc thiếu thông tin'
    ]);
} 