<?php
// paypal_redirect.php
session_start();
require_once 'config/database.php';
require_once 'config/paypal.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra bill_id
$bill_id = isset($_GET['bill_id']) ? intval($_GET['bill_id']) : 0;
if ($bill_id <= 0) {
    header('Location: checkout.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("SELECT b.*, a.username, a.phone, a.address 
                         FROM bill b 
                         JOIN account a ON b.id_account = a.account_id 
                         WHERE b.bill_id = ? AND b.id_account = ?");
    $stmt->execute([$bill_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Đơn hàng không tồn tại');
    }

    // Lấy chi tiết các món ăn trong đơn hàng
    $stmt = $db->prepare("SELECT bi.*, f.food_name, f.new_price 
                         FROM bill_info bi 
                         JOIN food f ON bi.id_food = f.food_id 
                         WHERE bi.id_bill = ?");
    $stmt->execute([$bill_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn bị thông tin chi tiết cho PayPal
    $items = array();
    $total_amount = 0;

    foreach ($order_items as $item) {
        $item_price_usd = round($item['new_price'] / VND_TO_USD, 2);
        $item_total = $item_price_usd * $item['count'];
        $total_amount += $item_total;

        $items[] = array(
            'name' => $item['food_name'],
            'unit_amount' => array(
                'currency_code' => 'USD',
                'value' => number_format($item_price_usd, 2, '.', '')
            ),
            'quantity' => $item['count'],
            'description' => 'Món ăn từ TaloFood'
        );
    }

    // Tạo payload cho PayPal
    $payload = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'reference_id' => (string)$bill_id,
                'description' => 'Đơn hàng từ TaloFood #' . $bill_id,
                'items' => $items,
                'amount' => array(
                    'currency_code' => 'USD',
                    'value' => number_format($total_amount, 2, '.', ''),
                    'breakdown' => array(
                        'item_total' => array(
                            'currency_code' => 'USD',
                            'value' => number_format($total_amount, 2, '.', '')
                        )
                    )
                ),
                'shipping' => array(
                    'name' => array(
                        'full_name' => $order['username']
                    ),
                    'address' => array(
                        'address_line_1' => $order['address'],
                        'admin_area_2' => 'Ho Chi Minh City',
                        'country_code' => 'VN'
                    )
                )
            )
        ),
        'application_context' => array(
            'brand_name' => 'TaloFood Restaurant',
            'shipping_preference' => 'SET_PROVIDED_ADDRESS',
            'user_action' => 'PAY_NOW',
            'return_url' => PAYPAL_SUCCESS_URL,
            'cancel_url' => PAYPAL_CANCEL_URL
        )
    );

    // Tạo order trên PayPal
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        throw new Exception('Không thể kết nối với PayPal');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 201) {
        error_log("PayPal API Error: " . $response);
        throw new Exception('Không thể tạo đơn hàng PayPal');
    }

    $order_data = json_decode($response, true);

    if (isset($order_data['id'])) {
        // Lưu PayPal order ID và bill_id vào session
        $_SESSION['paypal_order_id'] = $order_data['id'];
        $_SESSION['paypal_bill_id'] = $bill_id;

        // Chuyển hướng đến PayPal
        foreach ($order_data['links'] as $link) {
            if ($link['rel'] === 'approve') {
                header('Location: ' . $link['href']);
                exit();
            }
        }
    } else {
        throw new Exception('Không thể tạo đơn hàng PayPal');
    }

} catch (Exception $e) {
    error_log("PayPal Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: checkout.php');
    exit();
}
?>
