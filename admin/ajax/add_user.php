<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        exit();
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Kiểm tra username đã tồn tại
        $stmt = $conn->prepare("SELECT account_id FROM account WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại!']);
            exit();
        }

        // Kiểm tra email đã tồn tại
        $stmt = $conn->prepare("SELECT account_id FROM account WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng!']);
            exit();
        }

        // Validate password length
        if (strlen($_POST['password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            exit();
        }

        // Xử lý upload ảnh đại diện
        $profile_image = null;
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
                $profile_image = $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi upload file']);
                exit();
            }
        }

        // Thêm người dùng mới
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO account (
                username, 
                password, 
                email, 
                phone, 
                address, 
                id_role, 
                status, 
                login_attempts,
                profile_image
            ) VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?)
        ");
        
        $success = $stmt->execute([
            $_POST['username'],
            $hashed_password,
            $_POST['email'],
            $_POST['phone'] ?? '',
            $_POST['address'] ?? '',
            $_POST['role'] ?? 2, // Default to regular user if not specified
            $profile_image
        ]);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm người dùng thành công!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể thêm người dùng!'
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