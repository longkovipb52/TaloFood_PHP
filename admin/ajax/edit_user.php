<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['account_id']) || empty($_POST['username']) || empty($_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra tài khoản tồn tại và lấy thông tin ảnh cũ
        $stmt = $conn->prepare("SELECT account_id, profile_image FROM account WHERE account_id = ?");
        $stmt->execute([$_POST['account_id']]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_user) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
            exit();
        }

        // Kiểm tra username đã tồn tại (trừ user hiện tại)
        $stmt = $conn->prepare("
            SELECT account_id FROM account 
            WHERE username = ? AND account_id != ?
        ");
        $stmt->execute([$_POST['username'], $_POST['account_id']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại!']);
            exit();
        }

        // Kiểm tra email đã tồn tại (trừ user hiện tại)
        $stmt = $conn->prepare("
            SELECT account_id FROM account 
            WHERE email = ? AND account_id != ?
        ");
        $stmt->execute([$_POST['email'], $_POST['account_id']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng!']);
            exit();
        }

        // Xử lý upload ảnh đại diện mới nếu có
        $profile_image = $current_user['profile_image']; // Giữ nguyên ảnh cũ nếu không upload ảnh mới
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "../../uploads/avatars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Kiểm tra định dạng file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG hoặc GIF']);
                exit();
            }

            // Kiểm tra kích thước file (2MB)
            if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 2MB']);
                exit();
            }

            // Tạo tên file mới
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Xóa ảnh cũ nếu có
                if ($current_user['profile_image'] && file_exists($target_dir . $current_user['profile_image'])) {
                    unlink($target_dir . $current_user['profile_image']);
                }
                $profile_image = $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi upload file']);
                exit();
            }
        }

        // Cập nhật thông tin người dùng
        $stmt = $conn->prepare("
            UPDATE account 
            SET username = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                id_role = ?,
                profile_image = ?
            WHERE account_id = ?
        ");
        
        $success = $stmt->execute([
            $_POST['username'],
            $_POST['email'],
            $_POST['phone'] ?? '',
            $_POST['address'] ?? '',
            $_POST['role'],
            $profile_image,
            $_POST['account_id']
        ]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật thông tin người dùng'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
} 