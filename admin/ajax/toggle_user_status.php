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
        $stmt = $conn->prepare("UPDATE account SET status = ?, login_attempts = 0, locked_until = NULL WHERE account_id = ?");
        $success = $stmt->execute([$_POST['status'], $_POST['id']]);
        
        echo json_encode(['success' => $success]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 