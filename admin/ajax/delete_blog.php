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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['blog_id'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        $blog_id = $_POST['blog_id'];

        // Lấy thông tin ảnh của blog trước khi xóa
        $stmt = $conn->prepare("SELECT image FROM blog WHERE blog_id = :blog_id");
        $stmt->bindParam(':blog_id', $blog_id);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$blog) {
            throw new Exception('Không tìm thấy bài viết!');
        }

        // Xóa blog khỏi database
        $delete_stmt = $conn->prepare("DELETE FROM blog WHERE blog_id = :blog_id");
        $delete_stmt->bindParam(':blog_id', $blog_id);

        if ($delete_stmt->execute()) {
            // Xóa file ảnh nếu có
            if ($blog['image']) {
                $image_path = "../../uploads/blogs/" . $blog['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Xóa bài viết thành công!'
            ]);
        } else {
            throw new Exception('Không thể xóa bài viết!');
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