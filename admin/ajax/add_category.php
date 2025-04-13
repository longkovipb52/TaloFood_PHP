<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

// Kiểm tra tên danh mục
if (empty($_POST['category_name'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên danh mục']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Xử lý upload hình ảnh nếu có
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng file
        if (!in_array($file_extension, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Định dạng file không được hỗ trợ']);
            exit();
        }
        
        // Tạo tên file mới
        $image_name = uniqid() . '.' . $file_extension;
        $upload_path = '../../uploads/categories/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Upload file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $image_name)) {
            echo json_encode(['success' => false, 'message' => 'Không thể upload hình ảnh']);
            exit();
        }
    }
    
    // Thêm danh mục vào database
    $stmt = $conn->prepare("INSERT INTO food_category (foodcategory_name, image) VALUES (:name, :image)");
    $stmt->bindParam(':name', $_POST['category_name']);
    $stmt->bindParam(':image', $image_name);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thêm danh mục thành công'
        ]);
    } else {
        // Xóa file ảnh nếu thêm thất bại
        if ($image_name && file_exists($upload_path . $image_name)) {
            unlink($upload_path . $image_name);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thêm danh mục'
        ]);
    }
} catch(PDOException $e) {
    // Xóa file ảnh nếu có lỗi
    if ($image_name && file_exists($upload_path . $image_name)) {
        unlink($upload_path . $image_name);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 