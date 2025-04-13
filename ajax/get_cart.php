<?php
// filepath: c:\xampp\htdocs\TaloFood\ajax\get_cart.php
session_start();
require_once '../config/database.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$database = new Database();
$conn = $database->getConnection();

$total_amount = 0;

// Nếu giỏ hàng trống
if (empty($_SESSION['cart'])) {
    echo json_encode([
        'empty' => true,
        'html' => '<div class="empty-cart">
                      <i class="fas fa-shopping-basket"></i>
                      <p>Giỏ hàng trống</p>
                      <a href="menu.php" class="btn">Xem thực đơn</a>
                   </div>',
        'count' => 0,
        'total' => '0đ'
    ]);
    exit;
}

try {
    // Lấy danh sách ID của sản phẩm trong giỏ hàng
    $item_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));

    // Truy vấn tất cả sản phẩm trong một lần thay vì lặp nhiều truy vấn
    $stmt = $conn->prepare("
        SELECT food_id, food_name, image, new_price 
        FROM food 
        WHERE food_id IN ($placeholders)
    ");
    $stmt->execute($item_ids);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kiểm tra nếu sản phẩm nào trong giỏ hàng không tồn tại trong DB -> Xóa khỏi session
    $valid_food_ids = array_column($foods, 'food_id');
    foreach ($item_ids as $id) {
        if (!in_array($id, $valid_food_ids)) {
            unset($_SESSION['cart'][$id]);
        }
    }

    $items_html = '';
    $count = 0;

    foreach ($foods as $food) {
        $item_id = $food['food_id'];
        $quantity = $_SESSION['cart'][$item_id]['quantity'];
        $count += $quantity;
        $item_total = $quantity * $food['new_price'];
        $total_amount += $item_total;

        $items_html .= '
        <div class="cart-item" data-id="' . $food['food_id'] . '">
            <div class="item-image">
                <img src="uploads/foods/' . htmlspecialchars($food['image']) . '" alt="' . htmlspecialchars($food['food_name']) . '">
            </div>
            <div class="item-details">
                <h4 class="item-name">' . htmlspecialchars($food['food_name']) . '</h4>
                <div class="item-price">' . number_format($food['new_price'], 0, ',', '.') . 'đ</div>
                <div class="item-quantity">
                    <button class="decrease-quantity" onclick="updateCartItem(' . $food['food_id'] . ', ' . ($quantity - 1) . ')"><i class="fas fa-minus"></i></button>
                    <span>' . $quantity . '</span>
                    <button class="increase-quantity" onclick="updateCartItem(' . $food['food_id'] . ', ' . ($quantity + 1) . ')"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <button class="remove-item" onclick="removeCartItem(' . $food['food_id'] . ')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        ';
    }

    echo json_encode([
        'empty' => false,
        'html' => $items_html,
        'count' => $count,
        'total' => number_format($total_amount, 0, ',', '.') . 'đ'
    ]);

} catch (PDOException $e) {
    error_log("Lỗi khi lấy thông tin giỏ hàng: " . $e->getMessage()); // Ghi log lỗi thay vì hiển thị ra giao diện
    echo json_encode([
        'error' => true,
        'message' => 'Đã xảy ra lỗi khi tải giỏ hàng. Vui lòng thử lại sau!'
    ]);
}
