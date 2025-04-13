<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Kiểm tra dữ liệu POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['payment_method']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    // Lấy thông tin người dùng từ session
    if (!isset($_SESSION['user'])) {
        throw new Exception('Vui lòng đăng nhập để thanh toán');
    }
    $user = $_SESSION['user'];

    // Tính tổng tiền
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Thêm phí ship (nếu có)
    $shipping_fee = 30000; // VND
    $total += $shipping_fee;

    // Kết nối database
    $database = new Database();
    $conn = $database->getConnection();
    $conn->beginTransaction();

    // Thêm vào bảng bill
    $sql = "INSERT INTO bill (ngaydat, ngaygiao, id_account, status, address, total_amount, payment_method) 
            VALUES (CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), :id_account, :status, :address, :total_amount, :payment_method)";
    
    $stmt = $conn->prepare($sql);
    $status = ($data['payment_method'] == 'paypal') ? 'Chờ xử lý' : 'Đang xử lý';
    
    $stmt->execute([
        ':id_account' => $user['account_id'],
        ':status' => $status,
        ':address' => $user['address'],
        ':total_amount' => $total,
        ':payment_method' => $data['payment_method']
    ]);

    $bill_id = $conn->lastInsertId();

    // Thêm chi tiết đơn hàng
    $sql = "INSERT INTO bill_info (id_bill, id_food, id_account, count, price) 
            VALUES (:id_bill, :id_food, :id_account, :count, :price)";
    $stmt = $conn->prepare($sql);

    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([
            ':id_bill' => $bill_id,
            ':id_food' => $item['food_id'],
            ':id_account' => $user['account_id'],
            ':count' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    // Commit transaction
    $conn->commit();

    // Xóa giỏ hàng sau khi đặt hàng thành công
    if ($data['payment_method'] != 'paypal') {
        unset($_SESSION['cart']);
    }

    // Trả về response
    $response = [
        'success' => true,
        'order_id' => $bill_id,
        'message' => 'Đặt hàng thành công!'
    ];

    // Thêm thông tin cho PayPal nếu cần
    if ($data['payment_method'] == 'paypal') {
        // Chuyển đổi VND sang USD (tạm tính 1 USD = 23,000 VND)
        $exchange_rate = 23000;
        $amount_usd = round($total / $exchange_rate, 2);
        
        $response['paypal_data'] = [
            'amount' => $amount_usd,
            'currency' => 'USD',
            'order_id' => $bill_id
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 