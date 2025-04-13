<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện hành động này'
    ]);
    exit();
}

if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

$order_id = (int)$_POST['order_id'];
$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Kiểm tra đơn hàng thuộc về người dùng hiện tại và có trạng thái phù hợp để hủy
    $stmt = $conn->prepare("
        SELECT status, payment_method, total_amount FROM bill 
        WHERE bill_id = ? AND id_account = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng'
        ]);
        exit();
    }
    
    // Kiểm tra trạng thái đơn hàng
    $allowed_statuses = ['Chờ xác nhận', 'Đang xử lý', 'Đã thanh toán'];
    if (!in_array($order['status'], $allowed_statuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng không thể hủy ở trạng thái này'
        ]);
        exit();
    }
    
    // Trừ điểm uy tín tùy thuộc vào phương thức thanh toán
    $deduction = 20; // Mặc định trừ 20 điểm
    $refund_message = "";
    
    // Nếu đã thanh toán trước qua PayPal, giảm mức phạt điểm
    if ($order['status'] == 'Đã thanh toán' || $order['payment_method'] == 'PayPal') {
        $deduction = 10; // Trừ ít điểm hơn nếu đã thanh toán trước
        $refund_message = " Tiền thanh toán sẽ được hoàn lại trong 3-5 ngày làm việc.";
    }
    
    // Cập nhật trạng thái đơn hàng
    $stmt = $conn->prepare("UPDATE bill SET status = 'Đã hủy' WHERE bill_id = ?");
    $stmt->execute([$order_id]);
    
    // Lấy điểm uy tín hiện tại
    $stmt = $conn->prepare("SELECT reputation_points FROM account WHERE account_id = ?");
    $stmt->execute([$user_id]);
    $current_points = $stmt->fetchColumn();
    
    // Tính toán điểm uy tín mới (không cho phép nhỏ hơn 0)
    $new_points = max(0, $current_points - $deduction);
    
    // Cập nhật điểm uy tín
    $stmt = $conn->prepare("UPDATE account SET reputation_points = ? WHERE account_id = ?");
    $stmt->execute([$new_points, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng đã được hủy thành công.' . $refund_message,
        'deduction' => $deduction,
        'new_points' => $new_points
    ]);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại sau'
    ]);
}
?>