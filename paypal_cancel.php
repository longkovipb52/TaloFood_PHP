<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra bill_id
$bill_id = isset($_GET['bill_id']) ? intval($_GET['bill_id']) : 0;
if ($bill_id <= 0 || !isset($_SESSION['current_bill_id']) || $_SESSION['current_bill_id'] != $bill_id) {
    header('Location: checkout.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("SELECT * FROM bill WHERE bill_id = ? AND id_account = ?");
    $stmt->execute([$bill_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Cập nhật trạng thái đơn hàng
        $stmt = $db->prepare("UPDATE bill SET status = 'Đã hủy' WHERE bill_id = ?");
        $stmt->execute([$bill_id]);
    }

    // Xóa session PayPal
    unset($_SESSION['paypal_order_id']);
    unset($_SESSION['current_bill_id']);

    $_SESSION['error'] = 'Bạn đã hủy thanh toán. Vui lòng thử lại nếu muốn tiếp tục.';
    header('Location: checkout.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: checkout.php');
    exit();
} 