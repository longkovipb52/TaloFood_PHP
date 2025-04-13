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

// Kiểm tra dữ liệu đầu vào
if (empty($_POST['category_id']) || empty($_POST['category_name'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Lấy thông tin danh mục hiện tại
    $stmt = $conn->prepare("SELECT image FROM food_category WHERE foodcategory_id = :id");
    $stmt->bindParam(':id', $_POST['category_id']);
    $stmt->execute();
    $current_category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Xử lý upload hình ảnh mới nếu có
    $image_name = $current_category['image']; // Giữ nguyên ảnh cũ nếu không upload ảnh mới
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
        
        // Upload file mới
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $image_name)) {
            echo json_encode(['success' => false, 'message' => 'Không thể upload hình ảnh']);
            exit();
        }
        
        // Xóa file ảnh cũ nếu tồn tại
        if ($current_category['image'] && file_exists($upload_path . $current_category['image'])) {
            unlink($upload_path . $current_category['image']);
        }
    }
    
    // Cập nhật thông tin danh mục
    $stmt = $conn->prepare("UPDATE food_category SET foodcategory_name = :name, image = :image WHERE foodcategory_id = :id");
    $stmt->bindParam(':name', $_POST['category_name']);
    $stmt->bindParam(':image', $image_name);
    $stmt->bindParam(':id', $_POST['category_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công'
        ]);
    } else {
        // Xóa file ảnh mới nếu cập nhật thất bại
        if ($image_name != $current_category['image'] && file_exists($upload_path . $image_name)) {
            unlink($upload_path . $image_name);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật danh mục'
        ]);
    }
} catch(PDOException $e) {
    // Xóa file ảnh mới nếu có lỗi
    if (isset($image_name) && $image_name != $current_category['image'] && file_exists($upload_path . $image_name)) {
        unlink($upload_path . $image_name);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 