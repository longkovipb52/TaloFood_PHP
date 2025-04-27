<?php
session_start();
require_once '../config/database.php';

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ!'
    ]);
    exit;
}

// Lấy dữ liệu từ request
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Kiểm tra dữ liệu
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin!'
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra tài khoản
    $stmt = $conn->prepare("SELECT * FROM Account WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Kiểm tra trạng thái tài khoản
        if ($user['status'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa!',
                'locked' => true, // Thêm cờ xác định tài khoản bị khóa
                'username' => $user['username'] // Trả về username để sử dụng cho form
            ]);
            exit;
        }

        // Kiểm tra số lần đăng nhập sai
        if ($user['login_attempts'] >= 5 && $user['locked_until'] > date('Y-m-d H:i:s')) {
            echo json_encode([
                'success' => false,
                'message' => 'Tài khoản tạm thời bị khóa. Vui lòng thử lại sau!'
            ]);
            exit;
        }

        // Kiểm tra mật khẩu
        if (password_verify($password, $user['password'])) {
            // Reset số lần đăng nhập sai
            $stmt = $conn->prepare("UPDATE Account SET login_attempts = 0, locked_until = NULL WHERE account_id = ?");
            $stmt->execute([$user['account_id']]);

            // Lưu thông tin vào session
            $_SESSION['user_id'] = $user['account_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['id_role'];

            echo json_encode([
                'success' => true,
                'redirect' => $user['id_role'] == 1 ? 'admin/index.php' : 'index.php'
            ]);
        } else {
            // Tăng số lần đăng nhập sai
            $login_attempts = $user['login_attempts'] + 1;
            $locked_until = ($login_attempts >= 5) ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;

            // Nếu đăng nhập sai quá 5 lần, cập nhật cả status thành 0 (khóa)
            if ($login_attempts >= 5) {
                $stmt = $conn->prepare("UPDATE Account SET login_attempts = ?, locked_until = ?, status = 0 WHERE account_id = ?");
                $stmt->execute([$login_attempts, $locked_until, $user['account_id']]);
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Tài khoản đã bị khóa do đăng nhập sai nhiều lần!',
                    'locked' => true,
                    'username' => $user['username']
                ]);
                exit;
            } else {
                $stmt = $conn->prepare("UPDATE Account SET login_attempts = ?, locked_until = ? WHERE account_id = ?");
                $stmt->execute([$login_attempts, $locked_until, $user['account_id']]);
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Tài khoản hoặc mật khẩu không chính xác! Còn ' . (5 - $login_attempts) . ' lần thử.'
                ]);
            }
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản hoặc mật khẩu không chính xác!'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}