<?php
session_start();
require_once '../config/database.php';

// Kiểm tra dữ liệu đầu vào
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['item_id']) || !is_numeric($data['item_id']) || 
    !isset($data['quantity']) || !is_numeric($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

$item_id = (int) $data['item_id'];
$quantity = (int) $data['quantity'];

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Nếu số lượng là 0 hoặc nhỏ hơn, xóa mục khỏi giỏ hàng
if ($quantity <= 0) {
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        'removed' => true
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Kiểm tra xem sản phẩm có tồn tại không
    $stmt = $conn->prepare("SELECT food_id, food_name, new_price FROM food WHERE food_id = ?");
    $stmt->execute([$item_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food) {
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại'
        ]);
        exit;
    }
    
    // Cập nhật giỏ hàng
    $_SESSION['cart'][$item_id] = [
        'quantity' => $quantity,
        'price' => $food['new_price']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng',
        'item_total' => number_format($quantity * $food['new_price'], 0, ',', '.') . 'đ'
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật giỏ hàng: ' . $e->getMessage()
    ]);
}