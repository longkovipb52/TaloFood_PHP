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
        // Kiểm tra xem người dùng có tồn tại không và không phải admin
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

        // Bắt đầu transaction để đảm bảo toàn vẹn dữ liệu
        $conn->beginTransaction();

        // 1. Xóa chi tiết đơn hàng (bill_info)
        $stmt = $conn->prepare("DELETE FROM bill_info WHERE id_account = ?");
        $stmt->execute([$userId]);

        // 2. Xóa đơn hàng (bill)
        $stmt = $conn->prepare("DELETE FROM bill WHERE id_account = ?");
        $stmt->execute([$userId]);

        // 3. Xóa tin nhắn liên hệ (contact)
        $stmt = $conn->prepare("DELETE FROM contact WHERE id_account = ?");
        $stmt->execute([$userId]);

        // 4. Xóa đánh giá (reviews) - có ON DELETE CASCADE nhưng vẫn xử lý cho chắc
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id_account = ?");
        $stmt->execute([$userId]);

        // 5. Cuối cùng, xóa tài khoản người dùng
        $stmt = $conn->prepare("DELETE FROM account WHERE account_id = ?");
        $success = $stmt->execute([$userId]);

        if ($success) {
            // Commit transaction nếu tất cả thao tác thành công
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa người dùng và tất cả dữ liệu liên quan thành công!'
            ]);
        } else {
            // Rollback nếu xóa người dùng thất bại
            $conn->rollBack();
            
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa người dùng'
            ]);
        }
    } catch(PDOException $e) {
        // Rollback nếu có lỗi
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
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