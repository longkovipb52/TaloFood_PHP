<?php
require_once 'config/database.php';

// Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$database = new Database();
$conn = $database->getConnection();

// Xử lý AJAX request
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] == '1';

try {
    // Lấy danh sách danh mục
    $stmt = $conn->prepare("SELECT * FROM food_category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Xây dựng câu truy vấn SQL
    $sql = "SELECT f.*, c.foodcategory_name, 
                   (SELECT AVG(star_rating) FROM reviews WHERE id_food = f.food_id) as average_rating,
                   (SELECT COUNT(*) FROM reviews WHERE id_food = f.food_id) as review_count
            FROM food f
            JOIN food_category c ON f.id_category = c.foodcategory_id 
            WHERE 1=1";
    
    $params = [];
    
    if ($category_filter > 0) {
        $sql .= " AND f.id_category = ?";
        $params[] = $category_filter;
    }
    
    if ($search_term) {
        $sql .= " AND (f.food_name LIKE ?  OR c.foodcategory_name LIKE ?)";
        $params[] = "%$search_term%";
        $params[] = "%$search_term%";
    }
    
    $sql .= " ORDER BY f.food_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Nếu là AJAX request, trả về chỉ phần menu-grid
if ($isAjaxRequest) {
    ob_start();
    if(empty($foods)): ?>
        <div class="no-products">
            <i class="fas fa-search"></i>
            <p>Không tìm thấy sản phẩm nào</p>
        </div>
    <?php else: ?>
        <div class="menu-grid">
            <?php foreach($foods as $food): ?>
            <div class="menu-item" data-id="<?php echo $food['food_id']; ?>">
                <div class="item-image">
                    <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>">
                    <?php if(isset($food['total_sold']) && $food['total_sold'] > 0): ?>
                    <div class="sold-badge"><?php echo $food['total_sold']; ?> đã bán</div>
                    <?php endif; ?>
                    <div class="item-overlay">
                        <button class="quick-view-btn" onclick="openQuickView(<?php echo $food['food_id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $food['food_id']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                <div class="item-info">
                    <span class="item-category"><?php echo htmlspecialchars($food['foodcategory_name']); ?></span>
                    <h3><?php echo htmlspecialchars($food['food_name']); ?></h3>
                    <div class="item-rating">
                        <?php 
                        $rating = isset($food['average_rating']) ? round($food['average_rating']) : 0;
                        for ($i = 1; $i <= 5; $i++): 
                            if ($i <= $rating): ?>
                            <i class="fas fa-star"></i>
                            <?php else: ?>
                            <i class="far fa-star"></i>
                            <?php endif;
                        endfor; ?>
                        <span>(<?php echo isset($food['review_count']) ? $food['review_count'] : 0; ?>)</span>
                    </div>
                    <div class="item-price">
                        <?php if(isset($food['price']) && $food['price'] > $food['new_price']): ?>
                        <span class="old-price"><?php echo number_format($food['price'], 0, ',', '.'); ?>đ</span>
                        <span class="discount-badge"><?php echo round((($food['price'] - $food['new_price']) / $food['price']) * 100); ?>%</span>
                        <?php endif; ?>
                        <span class="new-price"><?php echo number_format($food['new_price'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="item-description">
                        <?php echo mb_substr(htmlspecialchars($food['description']), 0, 60, 'UTF-8') . (mb_strlen($food['description'], 'UTF-8') > 60 ? '...' : ''); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
    
    echo ob_get_clean();
    exit;
}

// Nếu không phải AJAX request, render toàn bộ trang
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thực đơn - TaloFood</title>
    
    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/modal_detailfood.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="menu-hero">
        <div class="hero-content">
            <h1>Thực đơn của chúng tôi</h1>
            <p>Khám phá thế giới ẩm thực đa dạng và hấp dẫn</p>
        </div>
    </section>

    <section class="menu-categories">
        <div class="category-filters">
            <button class="filter-btn <?php if(!isset($_GET['category'])) echo 'active'; ?>" 
                    data-category="" 
                    onclick="return false;">Tất cả</button>
            <?php foreach($categories as $category): ?>
            <button class="filter-btn <?php if(isset($_GET['category']) && $_GET['category'] == $category['foodcategory_id']) echo 'active'; ?>" 
                   data-category="<?php echo $category['foodcategory_id']; ?>" 
                   onclick="return false;">
                <?php echo htmlspecialchars($category['foodcategory_name']); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php if($search_term): ?>
            <div class="search-results-info">
                <h3>Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_term); ?>"</h3>
                <p>Tìm thấy <?php echo count($foods); ?> món ăn</p>
            </div>
        <?php endif; ?>

        <div class="search-sort-container">
            <div class="custom-sort-box">
                <div class="sort-selected">
                    <i class="fas fa-sort"></i>
                    <span>Sắp xếp mặc định</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sort-options">
                    <div class="sort-option active" data-value="default">
                        <i class="fas fa-sort"></i>
                        <span>Sắp xếp mặc định</span>
                    </div>
                    <!-- Thêm hai tùy chọn sắp xếp theo đánh giá -->
                    <div class="sort-option" data-value="rating-desc">
                        <i class="fas fa-star"></i>
                        <span>Đánh giá: Cao nhất</span>
                    </div>
                    <div class="sort-option" data-value="rating-asc">
                        <i class="far fa-star"></i>
                        <span>Đánh giá: Thấp nhất</span>
                    </div>
                    <div class="sort-option" data-value="price-asc">
                        <i class="fas fa-sort-amount-up-alt"></i>
                        <span>Giá: Thấp đến cao</span>
                    </div>
                    <div class="sort-option" data-value="price-desc">
                        <i class="fas fa-sort-amount-down"></i>
                        <span>Giá: Cao đến thấp</span>
                    </div>
                    <div class="sort-option" data-value="name-asc">
                        <i class="fas fa-sort-alpha-down"></i>
                        <span>Tên: A-Z</span>
                    </div>
                    <div class="sort-option" data-value="name-desc">
                        <i class="fas fa-sort-alpha-up"></i>
                        <span>Tên: Z-A</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(empty($foods)): ?>
            <div class="no-products">
                <i class="fas fa-search"></i>
                <p>Không tìm thấy sản phẩm nào</p>
            </div>
        <?php else: ?>
            <div class="menu-grid">
                <?php foreach($foods as $food): ?>
                <div class="menu-item" data-id="<?php echo $food['food_id']; ?>">
                    <div class="item-image">
                        <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>">
                        <?php if(isset($food['total_sold']) && $food['total_sold'] > 0): ?>
                        <div class="sold-badge"><?php echo $food['total_sold']; ?> đã bán</div>
                        <?php endif; ?>
                        <div class="item-overlay">
                            <button class="quick-view-btn" onclick="openQuickView(<?php echo $food['food_id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $food['food_id']; ?>)">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="item-info">
                        <span class="item-category"><?php echo htmlspecialchars($food['foodcategory_name']); ?></span>
                        <h3><?php echo htmlspecialchars($food['food_name']); ?></h3>
                        <div class="item-rating">
                            <?php 
                            $rating = isset($food['average_rating']) ? round($food['average_rating']) : 0;
                            for ($i = 1; $i <= 5; $i++): 
                                if ($i <= $rating): ?>
                                <i class="fas fa-star"></i>
                                <?php else: ?>
                                <i class="far fa-star"></i>
                                <?php endif;
                            endfor; ?>
                            <span>(<?php echo isset($food['review_count']) ? $food['review_count'] : 0; ?>)</span>
                        </div>
                        <div class="item-price">
                            <?php if(isset($food['price']) && $food['price'] > $food['new_price']): ?>
                            <span class="old-price"><?php echo number_format($food['price'], 0, ',', '.'); ?>đ</span>
                            <span class="discount-badge"><?php echo round((($food['price'] - $food['new_price']) / $food['price']) * 100); ?>%</span>
                            <?php endif; ?>
                            <span class="new-price"><?php echo number_format($food['new_price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="item-description">
                            <?php echo mb_substr(htmlspecialchars($food['description']), 0, 60, 'UTF-8') . (mb_strlen($food['description'], 'UTF-8') > 60 ? '...' : ''); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Quick View Modal -->
    <div id="quick-view-modal" class="modal">
        <div class="modal-content">
            <button class="close"><i class="fas fa-times"></i></button>
            <div id="quick-view-content">
                <!-- Nội dung chi tiết sản phẩm sẽ được load bằng AJAX -->
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Đang tải...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/menu.js"></script>
    <script src="assets/js/header.js"></script>
</body>
</html>