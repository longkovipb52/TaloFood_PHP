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
        $blog_id = $_POST['blog_id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $status = $_POST['status'];

        // Kiểm tra xem blog có tồn tại không
        $check_stmt = $conn->prepare("SELECT image FROM blog WHERE blog_id = :blog_id");
        $check_stmt->bindParam(':blog_id', $blog_id);
        $check_stmt->execute();
        $current_blog = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_blog) {
            throw new Exception('Không tìm thấy bài viết!');
        }

        $image = $current_blog['image']; // Giữ lại ảnh cũ nếu không upload ảnh mới

        // Xử lý upload hình ảnh mới nếu có
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
                // Xóa ảnh cũ nếu có
                if ($current_blog['image'] && file_exists($target_dir . $current_blog['image'])) {
                    unlink($target_dir . $current_blog['image']);
                }
                $image = $new_filename;
            } else {
                throw new Exception('Có lỗi xảy ra khi upload file!');
            }
        }

        // Cập nhật thông tin blog
        $stmt = $conn->prepare("
            UPDATE blog 
            SET title = :title,
                content = :content,
                image = :image,
                status = :status,
                updated_at = NOW()
            WHERE blog_id = :blog_id
        ");

        $stmt->bindParam(':blog_id', $blog_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật bài viết thành công!'
            ]);
        } else {
            throw new Exception('Không thể cập nhật bài viết!');
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