<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

// Kiểm tra và lấy dữ liệu
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit;
}

if (empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không được để trống']);
    exit;
}

// Kiểm tra trạng thái hợp lệ
$valid_statuses = ['Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Đã giao', 'Đã hủy'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra đơn hàng tồn tại
    $stmt = $conn->prepare("SELECT status FROM bill WHERE bill_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }

    // Kiểm tra logic chuyển trạng thái
    $current_status = $order['status'];
    if ($current_status === 'Đã giao' || $current_status === 'Đã hủy') {
        echo json_encode(['success' => false, 'message' => 'Không thể thay đổi trạng thái của đơn hàng đã hoàn thành hoặc đã hủy']);
        exit;
    }

    // Cập nhật trạng thái
    $stmt = $conn->prepare("UPDATE bill SET status = ? WHERE bill_id = ?");
    $stmt->execute([$status, $order_id]);

    // Nếu đơn hàng được xác nhận, cập nhật total_sold
    if ($status === 'Đã xác nhận') {
        // Lấy thông tin chi tiết đơn hàng
        $stmt = $conn->prepare("
            SELECT bi.id_food, bi.count 
            FROM bill_info bi 
            WHERE bi.id_bill = ?
        ");
        $stmt->execute([$order_id]);
        $billItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cập nhật total_sold cho từng món
        foreach ($billItems as $item) {
            $stmt = $conn->prepare("
                UPDATE food 
                SET total_sold = total_sold + ? 
                WHERE food_id = ?
            ");
            $stmt->execute([$item['count'], $item['id_food']]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
} 