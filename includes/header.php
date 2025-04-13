<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Fetch user data if logged in
$user_image = null;
$username = '';
if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT profile_image, username FROM account WHERE account_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $header_user = $stmt->fetch(PDO::FETCH_ASSOC); // Đổi tên biến
        $user_image = $header_user['profile_image'];
        $username = $header_user['username'];
    } catch(PDOException $e) {
        error_log("Error: " . $e->getMessage());
    }
}
?>
<link rel="stylesheet" href="assets/css/transitions.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/cart.css">

<header class="header">
    <a href="index.php" class="logo">
        <img src="image/logo.jpg" alt="TaloFood Logo">
    </a>

    <nav class="navbar">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="menu.php">Menu</a>
        <a href="my_reviews.php">Review</a>
        <a href="contact.php">Contact</a>
        <a href="blog.php">Blog</a>
    </nav>

    <div class="icons">
        <div class="fas fa-search" id="search-btn"></div>
        <div class="fas fa-shopping-cart" id="cart-btn"></div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-profile-dropdown">
                <div class="user-profile">
                    <img src="<?php echo $user_image ? 'uploads/avatars/' . htmlspecialchars($user_image) : 'assets/images/default-avatar.png'; ?>" 
                         alt="User Profile">
                </div>
                <div class="dropdown-menu">
                    <div class="dropdown-header">
                        <div class="user-avatar">
                            <img src="<?php echo $user_image ? 'uploads/avatars/' . htmlspecialchars($user_image) : 'assets/images/default-avatar.png'; ?>" alt="User Avatar">
                        </div>
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Thông tin cá nhân</span>
                    </a>
                    <a href="order_history.php" class="dropdown-item">
                        <i class="fas fa-history"></i>
                        <span>Lịch sử đơn hàng</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="fas fa-sign-in-alt" id="login-btn" onclick="window.location.href='login.php'" title="Đăng nhập"></div>
        <?php endif; ?>
        <div class="fas fa-bars" id="menu-btn"></div>
    </div>

    <div class="search-form">
        <form action="menu.php" method="GET">
            <input type="search" name="search" id="search-box" placeholder="Tìm kiếm món ăn..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" autocomplete="off">
            <button type="submit" class="fas fa-search"></button>
        </form>
        <div class="search-suggestions" id="search-suggestions"></div>
    </div>

    <div class="cart-items-container">
        <div class="cart-header">
            <h3>Giỏ hàng của bạn</h3>
            <span class="cart-count">0</span>
        </div>
        
        <div class="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>Giỏ hàng trống</p>
                <a href="menu.php" class="btn">Xem thực đơn</a>
            </div>
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span>Tổng tiền:</span>
                <span class="total-amount">0đ</span>
            </div>
            <div class="cart-actions">
                <a href="checkout.php" class="btn checkout-btn">Thanh toán</a>
                <button class="btn clear-cart-btn">Xóa giỏ hàng</button>
            </div>
        </div>
    </div>
</header>
<script src="assets/js/transitions.js"></script>
<script src="assets/js/header.js" defer></script>
<script src="assets/js/cart.js" defer></script>
<script src="assets/js/search-suggestions.js" defer></script>