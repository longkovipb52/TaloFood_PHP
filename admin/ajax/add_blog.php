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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Lấy dữ liệu từ form
        $title = $_POST['title'];
        $content = $_POST['content'];
        $author_id = $_POST['author_id'];
        $status = $_POST['status'];

        // Xử lý upload hình ảnh
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../../uploads/blogs/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Kiểm tra định dạng file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG hoặc GIF!');
            }

            // Kiểm tra kích thước file (giới hạn 5MB)
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Kích thước file không được vượt quá 5MB!');
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $new_filename;
            } else {
                throw new Exception('Có lỗi xảy ra khi upload file!');
            }
        }

        // Thêm bài viết vào database
        $stmt = $conn->prepare("
            INSERT INTO blog (title, content, image, author_id, status, created_at) 
            VALUES (:title, :content, :image, :author_id, :status, NOW())
        ");

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':author_id', $author_id);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm bài viết thành công!'
            ]);
        } else {
            throw new Exception('Không thể thêm bài viết!');
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
        'message' => 'Phương thức không được hỗ trợ!'
    ]);
}
?> 