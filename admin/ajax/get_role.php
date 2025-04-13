<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin ID vai trò']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $stmt = $conn->prepare("SELECT * FROM role WHERE role_id = ?");
    $stmt->execute([$_GET['id']]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($role) {
        echo json_encode([
            'success' => true, 
            'role' => [
                'role_id' => $role['role_id'],
                'rolename' => $role['rolename']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy vai trò']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 