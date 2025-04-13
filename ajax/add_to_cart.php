<?php
session_start();
require_once '../config/database.php';

// Kiểm tra dữ liệu đầu vào
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['item_id']) || !is_numeric($data['item_id']) || 
    !isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $item_id = (int) $data['item_id'];
    $quantity = (int) $data['quantity'];
    
    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $conn->prepare("SELECT food_id, food_name, new_price, image FROM food WHERE food_id = ?");
    $stmt->execute([$item_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food) {
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại'
        ]);
        exit;
    }
    
    // Thêm hoặc cập nhật số lượng trong giỏ hàng
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = [
            'quantity' => $quantity,
            'price' => $food['new_price']
        ];
    }
    
    // Đếm tổng số lượng sản phẩm trong giỏ hàng
    $total_items = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_items += $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vào giỏ hàng',
        'product' => [
            'name' => $food['food_name'],
            'price' => number_format($food['new_price'], 0, ',', '.') . 'đ',
            'image' => $food['image']
        ],
        'cart_count' => $total_items
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi thêm vào giỏ hàng: ' . $e->getMessage()
    ]);
}