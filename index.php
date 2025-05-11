<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
	// Lấy thông tin món ăn theo danh mục
	$categories = [];
	$stmt = $conn->prepare("
		SELECT c.foodcategory_id, c.foodcategory_name as name
		FROM food_category c
		ORDER BY c.foodcategory_id
	");
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($result as $category) {
		$foodStmt = $conn->prepare("
			SELECT f.food_id as id, f.food_name as name, f.price, f.new_price, 
				   f.image, f.description, f.total_sold,
				   (SELECT AVG(r.star_rating) FROM reviews r WHERE r.id_food = f.food_id) as average_rating,
				   (SELECT COUNT(*) FROM reviews r WHERE r.id_food = f.food_id) as review_count
			FROM food f
			WHERE f.id_category = ? AND f.status = 1
			LIMIT 4
		");
		$foodStmt->execute([$category['foodcategory_id']]); 
		$foods = $foodStmt->fetchAll(PDO::FETCH_ASSOC); 
		
		if (count($foods) > 0) {
			$category['foods'] = $foods;
			$categories[] = $category;
		}
	}

	// Lấy sản phẩm bán chạy
	$bestSellersStmt = $conn->prepare("
		SELECT f.food_id, f.food_name, f.price, f.new_price, 
			   f.image, f.description, f.total_sold,
			   (SELECT AVG(r.star_rating) FROM reviews r WHERE r.id_food = f.food_id) as average_rating,
			   (SELECT COUNT(*) FROM reviews r WHERE r.id_food = f.food_id) as review_count
		FROM food f
		WHERE f.status = 1
		ORDER BY f.total_sold DESC
		LIMIT 4
	");
	$bestSellersStmt->execute();
	$bestSellers = $bestSellersStmt->fetchAll(PDO::FETCH_ASSOC); // Thay thế get_result()

	// Lấy đánh giá mới nhất
	$stmt = $conn->prepare("SELECT r.*, a.username, a.profile_image, f.food_name, f.image AS food_image 
		FROM reviews r
		JOIN account a ON r.id_account = a.account_id
		JOIN food f ON r.id_food = f.food_id
		WHERE r.star_rating >= 4
		ORDER BY r.star_rating DESC, r.created_at DESC
		LIMIT 3");
	$stmt->execute();
	$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Lấy bài viết blog mới nhất
	$stmt = $conn->prepare("SELECT b.*, a.username FROM blog b
		JOIN account a ON b.author_id = a.account_id
		WHERE b.status = 'published'
		ORDER BY b.created_at DESC
		LIMIT 3");
	$stmt->execute();
	$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
	error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>TaloFood - Đồ ăn nhanh số 1</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
	<link rel="stylesheet" href="assets/css/home.css">
	<link rel="stylesheet" href="assets/css/footer.css">
	<link rel="stylesheet" href="assets/css/modal_detailfood.css">
	<link rel="stylesheet" href="assets/css/home-menu.css"> 
</head>

<body>
	<?php include 'includes/header.php'; ?>

	<section class="home" id="home">
		<div class="content">
			<h3>Đói bụng? Đừng lo, đã có TaloFood!</h3>
			<p>Đồ ăn nhanh, chuẩn vị ngon, phục vụ tốc hành — bạn chỉ việc thưởng thức!</p>
			<a href="#menu" class="btn">Đặt món ngay</a>
		</div>
	</section>

	<section class="about" id="about">
		<h1 class="heading">Về <span>chúng tôi</span></h1>
		<div class="row">
			<div class="image">
				<img src="image/about.jpg" alt="About TaloFood">
			</div>
			<div class="content">
				<h3>TaloFood – Hương Vị Nhanh, Trải Nghiệm Đậm Đà</h3>
				<p>TaloFood là thương hiệu đồ ăn nhanh tiên phong, mang đến những món ăn thơm ngon, tiện lợi và chất lượng hàng đầu.</p>
				<p class="hidden">Với công thức chế biến độc quyền cùng nguồn nguyên liệu tươi sạch, chúng tôi cam kết mang đến cho khách hàng trải nghiệm vừa nhanh – vừa ngon – vừa dinh dưỡng.</p>
				<p class="hidden">Sản phẩm nổi bật của TaloFood: Burger bò Mỹ thơm lừng, Gà rán giòn rụm, Khoai tây chiên giòn tan và nhiều Combo siêu tiết kiệm!</p>
				<button id="learn-more-btn" class="btn">Xem thêm</button>
			</div>
		</div>
	</section>

	<section class="menu" id="menu">
		<h1 class="heading">Thực đơn <span>của chúng tôi</span></h1>
		<?php foreach ($categories as $category): ?>
		<div class="category-section">
			<h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
			<div class="box-container">
				<?php foreach ($category['foods'] as $food): ?>
				<div class="menu-item" data-id="<?php echo $food['id']; ?>">
					<div class="item-image">
						<img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
						<?php if(isset($food['total_sold']) && $food['total_sold'] > 0): ?>
						<div class="sold-badge"><?php echo $food['total_sold']; ?> đã bán</div>
						<?php endif; ?>
						<div class="item-overlay">
							<button class="quick-view-btn" onclick="openQuickView(<?php echo $food['id']; ?>)">
								<i class="fas fa-eye"></i>
							</button>
							<button class="add-to-cart-btn" onclick="addToCart(<?php echo $food['id']; ?>)">
								<i class="fas fa-shopping-cart"></i>
							</button>
						</div>
					</div>
					<div class="item-info">
						<span class="item-category"><?php echo htmlspecialchars($category['name']); ?></span>
						<h3><?php echo htmlspecialchars($food['name']); ?></h3>
						<div class="item-rating">
							<?php 
							$rating = isset($food['average_rating']) ? round($food['average_rating']) : 5;
							for ($i = 1; $i <= 5; $i++): 
								if ($i <= $rating): ?>
								<i class="fas fa-star"></i>
								<?php else: ?>
								<i class="far fa-star"></i>
								<?php endif;
							endfor; ?>
						</div>
						<div class="item-price">
							<span class="new-price"><?php echo number_format($food['new_price'], 0, ',', '.'); ?>đ</span>
							<?php if(isset($food['price']) && $food['price'] > $food['new_price']): ?>
							<span class="old-price"><?php echo number_format($food['price'], 0, ',', '.'); ?>đ</span>
							<span class="discount-badge"><?php echo round((($food['price'] - $food['new_price']) / $food['price']) * 100); ?>%</span>
							<?php endif; ?>
						</div>
						<div class="item-description">
							<?php echo mb_substr(htmlspecialchars($food['description']), 0, 60, 'UTF-8') . (mb_strlen($food['description'], 'UTF-8') > 60 ? '...' : ''); ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</section>

	<section class="products" id="products">
		<h1 class="heading">Sản phẩm <span>bán chạy</span></h1>
		<div class="box-container">
			<?php foreach ($bestSellers as $product): ?>
			<div class="menu-item" data-id="<?php echo $product['food_id']; ?>">
				<div class="item-image">
					<img src="uploads/foods/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['food_name']); ?>">
					<div class="sold-badge">Bán chạy</div>
					<div class="item-overlay">
						<button class="quick-view-btn" onclick="openQuickView(<?php echo $product['food_id']; ?>)">
							<i class="fas fa-eye"></i>
						</button>
						<button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['food_id']; ?>)">
							<i class="fas fa-shopping-cart"></i>
						</button>
					</div>
				</div>
				<div class="item-info">
					<h3><?php echo htmlspecialchars($product['food_name']); ?></h3>
					<div class="item-rating">
						<?php 
						$rating = isset($product['average_rating']) ? round($product['average_rating']) : 5;
						for ($i = 1; $i <= 5; $i++): 
							if ($i <= $rating): ?>
							<i class="fas fa-star"></i>
							<?php else: ?>
							<i class="far fa-star"></i>
							<?php endif;
						endfor; ?>
						<span>(Hot)</span>
					</div>
					<div class="item-price">
						<span class="new-price"><?php echo number_format($product['new_price'], 0, ',', '.'); ?>đ</span>
						<?php if(isset($product['price']) && $product['price'] > $product['new_price']): ?>
						<span class="old-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
						<span class="discount-badge"><?php echo round((($product['price'] - $product['new_price']) / $product['price']) * 100); ?>%</span>
						<?php endif; ?>
					</div>
					<div class="item-description">
						<?php echo mb_substr(htmlspecialchars($product['description']), 0, 60, 'UTF-8') . (mb_strlen($product['description'], 'UTF-8') > 60 ? '...' : ''); ?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="review" id="review">
		<h1 class="heading">Đánh giá <span>nổi bật</span></h1>
		<div class="box-container">
			<?php foreach ($reviews as $review): ?>
			<div class="box">
				<img src="assets/images/quote-img.png" alt="" class="quote">
				<div class="review-header">
					<div class="user-info">
						<img src="<?php echo $review['profile_image'] ? 'uploads/avatars/' . $review['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
							class="user" alt="<?php echo htmlspecialchars($review['username']); ?>">
						<h3><?php echo htmlspecialchars($review['username']); ?></h3>
					</div>
					<div class="food-info">
						<img src="uploads/foods/<?php echo htmlspecialchars($review['food_image']); ?>" 
							class="food-thumbnail" alt="<?php echo htmlspecialchars($review['food_name']); ?>">
						<span class="food-name"><?php echo htmlspecialchars($review['food_name']); ?></span>
					</div>
				</div>
				<p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
				<div class="review-footer">
					<div class="stars">
						<?php
						$rating = $review['star_rating'];
						for ($i = 1; $i <= 5; $i++) {
							if ($i <= $rating) {
								echo '<i class="fas fa-star"></i>';
							} else {
								echo '<i class="far fa-star"></i>';
							}
						}
						?>
					</div>
					<div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="contact" id="contact">
		<h1 class="heading"><span>Liên hệ</span> với chúng tôi</h1>
		<div class="row">
			<iframe class="map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d582.209969736725!2d106.67416400808037!3d10.991560667331683!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174d11e65beedef%3A0x331844c85c8c91b1!2zMTc5IEh14buzbmggVsSDbiBMxal5LCBQaMO6IEzhu6NpLCBUaOG7pyBE4bqndSBN4buZdCwgQsOsbmggRMawxqFuZyA3MDAwMCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1742217209712!5m2!1svi!2s" allowfullscreen="" loading="lazy"></iframe>

			<?php if (isset($_SESSION['user_id'])): ?>
			<form action="send_contact.php" method="POST" id="contactForm">
				<h3>Gửi tin nhắn</h3>
				<div class="inputBox">
					<span class="fas fa-message"></span>
					<textarea name="message" placeholder="Nội dung tin nhắn của bạn..." required></textarea>
				</div>
				<input type="submit" value="Gửi tin nhắn" class="btn">
				<div id="messageStatus" style="margin-top: 1rem; color: #fff; font-size: 1.6rem;"></div>
			</form>
			<?php else: ?>
			<div class="login-prompt" style="flex: 1 1 45rem; padding: 5rem 2rem; text-align: center;">
				<h3 style="font-size: 2.5rem; color: #fff; margin-bottom: 2rem;">Đăng nhập để gửi tin nhắn</h3>
				<p style="font-size: 1.6rem; color: #ccc; margin-bottom: 2rem;">Bạn cần đăng nhập để có thể gửi tin nhắn cho chúng tôi</p>
				<a href="login.php" class="btn">Đăng nhập ngay</a>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="blogs" id="blogs">
		<h1 class="heading">Bài viết <span>mới</span></h1>
		<div class="box-container">
			<?php foreach ($blogs as $blog): ?>
			<div class="box">
				<div class="image">
					<img src="uploads/blogs/<?php echo htmlspecialchars($blog['image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
				</div>
				<div class="content">
					<a href="blog_detail.php?id=<?php echo $blog['blog_id']; ?>" class="title">
						<?php echo htmlspecialchars($blog['title']); ?>
					</a>
					<span>bởi <?php echo htmlspecialchars($blog['username']); ?> / <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?></span>
					<p><?php echo substr(strip_tags($blog['content']), 0, 100); ?>...</p>
					<a href="blog_detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn">Xem thêm</a>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Quick View Modal -->
	<div id="quick-view-modal" class="modal">
		<div class="modal-content">
			<span class="close">&times;</span>
			<div id="quick-view-content">

		</div>
		</div>
	</div>

	<?php include 'includes/footer.php'; ?>

	<script src="assets/js/header.js"></script>
	<script src="assets/js/menu.js"></script> 
	<script src="assets/js/home.js"></script>
	<script src="assets/js/cart.js"></script>
</body>
</html>