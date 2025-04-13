<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập!'
    ]);
    exit();
}

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['food_name']) || !isset($_POST['category_id']) || !isset($_POST['price'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bắt buộc!'
    ]);
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
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode([
                'success' => false,
                'message' => 'File không hợp lệ! Chỉ chấp nhận ảnh jpg, jpeg, png, gif'
            ]);
            exit();
        }

        $image_name = uniqid() . '.' . $ext;
        $upload_path = '../../uploads/foods/' . $image_name;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể upload hình ảnh!'
            ]);
            exit();
        }
    }

    // Chuẩn bị câu lệnh SQL
    $sql = "INSERT INTO food (food_name, id_category, price, new_price, description, image, status) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);

    // Xử lý giá mới, nếu không có thì set NULL
    $new_price = !empty($_POST['new_price']) ? $_POST['new_price'] : null;

    // Thực thi câu lệnh
    $stmt->execute([
        $_POST['food_name'],
        $_POST['category_id'],
        $_POST['price'],
        $new_price,
        $_POST['description'] ?? '',
        $image_name
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thêm món ăn thành công!'
    ]);

} catch(PDOException $e) {
    // Xóa ảnh đã upload nếu có lỗi
    if ($image_name && file_exists('../../uploads/foods/' . $image_name)) {
        unlink('../../uploads/foods/' . $image_name);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 