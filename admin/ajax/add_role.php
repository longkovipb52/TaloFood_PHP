<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['role_name'])) {
        echo json_encode(['success' => false, 'message' => 'Tên vai trò không được để trống']);
        exit();
    }

    // Validate role name length
    if (strlen($_POST['role_name']) < 3 || strlen($_POST['role_name']) > 50) {
        echo json_encode(['success' => false, 'message' => 'Tên vai trò phải từ 3 đến 50 ký tự']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra xem vai trò đã tồn tại chưa
        $stmt = $conn->prepare("SELECT role_id FROM role WHERE rolename = ?");
        $stmt->execute([$_POST['role_name']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Vai trò này đã tồn tại']);
            exit();
        }

        // Thêm vai trò mới
        $stmt = $conn->prepare("INSERT INTO role (rolename) VALUES (?)");
        $success = $stmt->execute([$_POST['role_name']]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm vai trò thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể thêm vai trò'
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