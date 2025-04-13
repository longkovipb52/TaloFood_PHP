<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id']) || 
    !isset($_POST['status']) || !in_array($_POST['status'], ['0', '1'])) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
    exit();
}

try {
    $category_id = intval($_POST['id']);
    $new_status = intval($_POST['status']);
    
    // Check if category exists
    $check_query = "SELECT foodcategory_id FROM foodcategory WHERE foodcategory_id = :id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Danh mục không tồn tại'
        ]);
        exit();
    }
    
    // Update category status
    $update_query = "UPDATE foodcategory SET status = :status WHERE foodcategory_id = :id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status, PDO::PARAM_INT);
    $update_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    
    if ($update_stmt->execute()) {
        $status_text = $new_status == 1 ? 'kích hoạt' : 'vô hiệu hóa';
        echo json_encode([
            'success' => true,
            'message' => "Đã $status_text danh mục thành công"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thay đổi trạng thái danh mục'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi thay đổi trạng thái danh mục: ' . $e->getMessage()
    ]);
}
?> 