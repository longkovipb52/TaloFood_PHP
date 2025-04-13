<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['id_role']) || empty($_POST['role_name'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
        exit();
    }

    // Không cho phép sửa vai trò Admin và User
    if ($_POST['id_role'] <= 2) {
        echo json_encode(['success' => false, 'message' => 'Không thể sửa vai trò hệ thống']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra xem vai trò có tồn tại không
        $stmt = $conn->prepare("SELECT role_id FROM role WHERE role_id = ?");
        $stmt->execute([$_POST['id_role']]);
        if ($stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Vai trò không tồn tại']);
            exit();
        }

        // Kiểm tra xem tên mới có trùng với vai trò khác không
        $stmt = $conn->prepare("SELECT role_id FROM role WHERE rolename = ? AND role_id != ?");
        $stmt->execute([$_POST['role_name'], $_POST['id_role']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên vai trò đã tồn tại']);
            exit();
        }

        // Cập nhật thông tin vai trò
        $stmt = $conn->prepare("UPDATE role SET rolename = ? WHERE role_id = ?");
        $success = $stmt->execute([
            $_POST['role_name'],
            $_POST['id_role']
        ]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật vai trò thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật vai trò'
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
        'message' => 'Phương thức không được hỗ trợ'
    ]);
} 