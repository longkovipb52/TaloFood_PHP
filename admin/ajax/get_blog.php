<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập!'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        $blog_id = $_GET['id'];

        // Lấy thông tin blog kèm tên tác giả
        $stmt = $conn->prepare("
            SELECT b.*, a.username as author_name 
            FROM blog b 
            LEFT JOIN account a ON b.author_id = a.account_id 
            WHERE b.blog_id = :blog_id
        ");
        
        $stmt->bindParam(':blog_id', $blog_id);
        $stmt->execute();
        
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($blog) {
            echo json_encode([
                'success' => true,
                'blog' => $blog
            ]);
        } else {
            throw new Exception('Không tìm thấy bài viết!');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin blog ID!'
    ]);
}
?> 