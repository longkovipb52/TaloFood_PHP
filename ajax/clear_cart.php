<?php
session_start();

// Xóa giỏ hàng
$_SESSION['cart'] = [];

echo json_encode([
    'success' => true,
    'message' => 'Giỏ hàng đã được xóa'
]);