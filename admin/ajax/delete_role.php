<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $roleId = $_POST['id'];
    
    // Không cho phép xóa vai trò Admin (id = 1) và User (id = 2)
    if ($roleId <= 2) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa vai trò hệ thống']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra xem có người dùng nào đang sử dụng vai trò này không
        $stmt = $conn->prepare("SELECT COUNT(*) FROM account WHERE id_role = ?");
        $stmt->execute([$roleId]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa vai trò đang được sử dụng']);
            exit();
        }

        // Xóa vai trò
        $stmt = $conn->prepare("DELETE FROM role WHERE role_id = ?");
        $success = $stmt->execute([$roleId]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa vai trò']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 