<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID không hợp lệ'
    ]);
    exit;
}

$food_id = (int)$_GET['id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Sửa truy vấn để lấy thêm thông tin đánh giá
    $stmt = $conn->prepare("
        SELECT f.*, c.foodcategory_name,
               (SELECT AVG(r.star_rating) FROM reviews r WHERE r.id_food = f.food_id) as average_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.id_food = f.food_id) as review_count
        FROM food f
        LEFT JOIN food_category c ON f.id_category = c.foodcategory_id
        WHERE f.food_id = ?
    ");
    
    $stmt->execute([$food_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$food) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy món ăn'
        ]);
        exit;
    }
    
    // Sử dụng điểm đánh giá trung bình thực tế
    $rating = isset($food['average_rating']) && $food['average_rating'] !== null ? round($food['average_rating']) : 5;
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    
    // Hiển thị số lượng đánh giá thực tế
    $reviewCount = isset($food['review_count']) && $food['review_count'] !== null ? (int)$food['review_count'] : 0;
    
    $html = '
    <div class="modal-body">
        <div class="modal-grid">
            <div class="modal-image">
                <img src="uploads/foods/' . htmlspecialchars($food['image']) . '" alt="' . htmlspecialchars($food['food_name']) . '">
                ' . (isset($food['total_sold']) && $food['total_sold'] > 0 ? '<div class="sold-badge">' . $food['total_sold'] . ' đã bán</div>' : '') . '
            </div>
            <div class="modal-info">
                <span class="modal-category">' . htmlspecialchars($food['foodcategory_name']) . '</span>
                <h2>' . htmlspecialchars($food['food_name']) . '</h2>
                
                <div class="modal-rating">
                    <div class="stars">' . $stars . '</div>
                    <span class="count">(' . $reviewCount . ' đánh giá)</span>
                </div>
                
                <div class="modal-price">
                    <span class="new-price">' . number_format($food['new_price'], 0, ',', '.') . 'đ</span>';
                
    if (isset($food['price']) && $food['price'] > $food['new_price']) {
        $discount = round((($food['price'] - $food['new_price']) / $food['price']) * 100);
        $html .= '
                    <span class="old-price">' . number_format($food['price'], 0, ',', '.') . 'đ</span>
                    <span class="discount-badge">' . $discount . '%</span>';
    }
    
    $html .= '</div>
                
                <div class="modal-description">
                    <h3>Mô tả sản phẩm</h3>
                    <p>' . (empty($food['description']) ? 'Không có mô tả cho món ăn này.' : nl2br(htmlspecialchars($food['description']))) . '</p>
                </div>
                
                <div class="quantity-control">
                    <span>Số lượng:</span>
                    <div class="quantity-wrapper">
                        <button type="button" class="quantity-btn decrease-btn">-</button>
                        <input type="number" id="qty" value="1" min="1" max="99">
                        <button type="button" class="quantity-btn increase-btn">+</button>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="add-to-cart-btn" onclick="addToCart(' . $food_id . ', document.getElementById(\'qty\').value)">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi khi lấy thông tin món ăn. Vui lòng thử lại sau.'
    ]);
}
?>
