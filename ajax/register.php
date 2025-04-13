<?php
session_start();
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy dữ liệu từ form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $address = trim($_POST['address']);
    $profile_image = null;

    // Kiểm tra mật khẩu xác nhận
    if ($password !== $confirm_password) {
        $response['message'] = 'Mật khẩu xác nhận không khớp!';
        echo json_encode($response);
        exit;
    }

    // Kiểm tra độ dài mật khẩu
    if (strlen($password) < 6) {
        $response['message'] = 'Mật khẩu phải có ít nhất 6 ký tự!';
        echo json_encode($response);
        exit;
    }

    try {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT account_id FROM Account WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Tên đăng nhập đã tồn tại!';
            echo json_encode($response);
            exit;
        }

        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT account_id FROM Account WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Email đã được sử dụng!';
            echo json_encode($response);
            exit;
        }

        // Xử lý upload hình ảnh
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/avatars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Kiểm tra định dạng file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                $response['message'] = 'Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG hoặc GIF';
                echo json_encode($response);
                exit;
            }

            // Kiểm tra kích thước file (2MB)
            if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $response['message'] = 'Kích thước file không được vượt quá 2MB';
                echo json_encode($response);
                exit;
            }

            // Tạo tên file mới
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $target_dir . $file_name;

            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image = $file_name;
            } else {
                $response['message'] = 'Không thể tải lên ảnh đại diện!';
                echo json_encode($response);
                exit;
            }
        }

        // Trong file ajax/register.php, thêm kiểm tra:
        if (empty($profile_image)) {
            $profile_image = 'user.png';
        }

        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Thêm tài khoản mới
        $query = "INSERT INTO Account (username, password, email, phone, id_role, address, status, login_attempts, profile_image) 
                 VALUES (:username, :password, :email, :phone, 2, :address, 1, 0, :profile_image)";
        
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":profile_image", $profile_image);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
        } else {
            $response['message'] = 'Có lỗi xảy ra, vui lòng thử lại!';
        }

    } catch(PDOException $e) {
        $response['message'] = 'Lỗi: ' . $e->getMessage();
    }
}

echo json_encode($response);