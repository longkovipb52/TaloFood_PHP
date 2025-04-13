<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

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

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($username) || empty($email) || empty($phone) || empty($address)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra username đã tồn tại chưa (trừ username hiện tại)
    $stmt = $conn->prepare("SELECT account_id FROM account WHERE username = ? AND account_id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập đã tồn tại'
        ]);
        exit();
    }

    // Kiểm tra email đã tồn tại chưa (trừ email hiện tại)
    $stmt = $conn->prepare("SELECT account_id FROM account WHERE email = ? AND account_id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email đã tồn tại'
        ]);
        exit();
    }

    // Cập nhật thông tin
    $stmt = $conn->prepare("UPDATE account SET username = ?, email = ?, phone = ?, address = ? WHERE account_id = ?");
    if ($stmt->execute([$username, $email, $phone, $address, $_SESSION['user_id']])) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật thông tin'
        ]);
    }
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
} 