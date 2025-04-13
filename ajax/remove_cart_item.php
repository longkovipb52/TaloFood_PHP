<?php
session_start();

// Kiểm tra dữ liệu đầu vào
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['item_id']) || !is_numeric($data['item_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

$item_id = (int) $data['item_id'];

// Kiểm tra xem giỏ hàng có tồn tại không
if (!isset($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Giỏ hàng không tồn tại'
    ]);
    exit;
}

// Xóa sản phẩm khỏi giỏ hàng
if (isset($_SESSION['cart'][$item_id])) {
    unset($_SESSION['cart'][$item_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sản phẩm không tồn tại trong giỏ hàng'
    ]);
}