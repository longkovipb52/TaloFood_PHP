<?php
session_start();
require_once 'config/database.php';
require_once 'config/paypal.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra các tham số cần thiết
if (!isset($_GET['token']) || !isset($_GET['PayerID'])) {
    $_SESSION['error'] = "Thiếu thông tin thanh toán PayPal";
    header('Location: checkout.php');
    exit();
}

// Kiểm tra xem có bill_id trong session không
if (!isset($_SESSION['paypal_bill_id'])) {
    $_SESSION['error'] = "Không tìm thấy thông tin đơn hàng";
    header('Location: checkout.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("SELECT * FROM bill WHERE bill_id = ? AND id_account = ?");
    $stmt->execute([$_SESSION['paypal_bill_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Không tìm thấy đơn hàng");
    }

    // Lấy chi tiết đơn hàng
    $stmt = $db->prepare("SELECT bi.*, f.food_id, f.total_sold 
                         FROM bill_info bi 
                         JOIN food f ON bi.id_food = f.food_id 
                         WHERE bi.id_bill = ?");
    $stmt->execute([$_SESSION['paypal_bill_id']]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Capture payment từ PayPal
    $capture_response = capturePayPalOrder($_SESSION['paypal_order_id']);
    
    if ($capture_response && isset($capture_response['status']) && $capture_response['status'] === 'COMPLETED') {
        // Cập nhật trạng thái đơn hàng
        $stmt = $db->prepare("UPDATE bill SET status = 'Đã thanh toán' WHERE bill_id = ?");
        if (!$stmt->execute([$_SESSION['paypal_bill_id']])) {
            throw new Exception("Không thể cập nhật trạng thái đơn hàng");
        }

        // Cập nhật total_sold cho từng món ăn
        foreach ($order_items as $item) {
            $new_total_sold = ($item['total_sold'] ?? 0) + $item['count'];
            $stmt = $db->prepare("UPDATE food SET total_sold = ? WHERE food_id = ?");
            if (!$stmt->execute([$new_total_sold, $item['food_id']])) {
                throw new Exception("Không thể cập nhật số lượng đã bán");
            }
        }

        // Commit transaction
        $db->commit();

        // Lưu order_id vào session để sử dụng ở trang thank_you
        $order_id = $_SESSION['paypal_bill_id'];

        // Xóa session variables
        unset($_SESSION['paypal_bill_id']);
        unset($_SESSION['paypal_order_id']);
        unset($_SESSION['cart']);

        // Chuyển hướng đến trang cảm ơn với order_id
        header("Location: thank_you.php?order_id=" . $order_id);
        exit();
    } else {
        throw new Exception("Thanh toán PayPal không thành công");
    }

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("PayPal Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: checkout.php');
    exit();
}
?> 