<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Debug information
$debug = [];

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit();
}

// Kiểm tra FILE được gửi lên
if (!isset($_FILES['profile_image'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy dữ liệu ảnh'
    ]);
    exit();
}

$file = $_FILES['profile_image'];
$debug['file_info'] = $file;

// Kiểm tra lỗi upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Lỗi khi tải lên file: ';
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_message .= 'File vượt quá kích thước cho phép trong php.ini';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_message .= 'File vượt quá kích thước cho phép trong form';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message .= 'File chỉ được tải lên một phần';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message .= 'Không có file nào được tải lên';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message .= 'Thư mục tạm không tồn tại';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message .= 'Không thể ghi file vào ổ đĩa';
            break;
        default:
            $error_message .= 'Lỗi không xác định';
    }
    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'debug' => $debug
    ]);
    exit();
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 2 * 1024 * 1024; // 2MB

// Kiểm tra định dạng file
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc GIF'
    ]);
    exit();
}

// Kiểm tra kích thước file
if ($file['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'Kích thước file không được vượt quá 2MB'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Tạo thư mục uploads nếu chưa tồn tại
    $upload_dir = '../uploads/avatars/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo thư mục uploads'
            ]);
            exit();
        }
    }

    // Tạo tên file mới
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $extension;
    $target_path = $upload_dir . $new_filename;

    // Lấy ảnh cũ để xóa
    $stmt = $conn->prepare("SELECT profile_image FROM account WHERE account_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $old_image = $stmt->fetchColumn();

    // Upload file mới
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Cập nhật database
        $stmt = $conn->prepare("UPDATE account SET profile_image = ? WHERE account_id = ?");
        if ($stmt->execute([$new_filename, $_SESSION['user_id']])) {
            // Xóa ảnh cũ nếu tồn tại
            if ($old_image && $old_image != '' && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }

            // URL đầy đủ để cập nhật trong giao diện
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật ảnh đại diện thành công',
                'image_url' => 'uploads/avatars/' . $new_filename
            ]);
        } else {
            // Xóa file mới nếu không thể cập nhật database
            unlink($target_path);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật ảnh đại diện trong cơ sở dữ liệu'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tải lên ảnh: Lỗi khi di chuyển file'
        ]);
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}