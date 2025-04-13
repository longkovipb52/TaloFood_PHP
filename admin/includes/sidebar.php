<nav id="sidebar" class="active">
    <div class="sidebar-header">
        <img src="../image/logo.jpg" alt="TaloFood Logo" class="logo">
        <h3>TaloFood Admin</h3>
    </div>

    <ul class="list-unstyled components">
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Trang Chủ</span>
            </a>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'roles.php') ? 'class="active"' : ''; ?>>
            <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                <i class="fas fa-users"></i>
                <span>Quản Lý Người Dùng</span>
            </a>
            <ul class="collapse list-unstyled <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'roles.php') ? 'show' : ''; ?>" id="userSubmenu">
                <li>
                    <a href="users.php">Danh Sách Người Dùng</a>
                </li>
                <li>
                    <a href="roles.php">Phân Quyền</a>
                </li>
            </ul>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'foods.php' || basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'class="active"' : ''; ?>>
            <a href="#productSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                <i class="fas fa-hamburger"></i>
                <span>Quản Lý Món Ăn</span>
            </a>
            <ul class="collapse list-unstyled <?php echo (basename($_SERVER['PHP_SELF']) == 'foods.php' || basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'show' : ''; ?>" id="productSubmenu">
                <li>
                    <a href="foods.php">Danh Sách Món Ăn</a>
                </li>
                <li>
                    <a href="categories.php">Danh Mục</a>
                </li>
            </ul>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'class="active"' : ''; ?>>
            <a href="orders.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Quản Lý Đơn Hàng</span>
            </a>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'contacts.php') ? 'class="active"' : ''; ?>>
            <a href="contacts.php">
                <i class="fas fa-envelope"></i>
                <span>Quản Lý Liên Hệ</span>
            </a>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'blogs.php') ? 'class="active"' : ''; ?>>
            <a href="blogs.php">
                <i class="fas fa-blog"></i>
                <span>Quản Lý Blog</span>
            </a>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'class="active"' : ''; ?>>
            <a href="reviews.php">
                <i class="fas fa-star"></i>
                <span>Quản Lý Đánh Giá</span>
            </a>
        </li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'statistics.php') ? 'class="active"' : ''; ?>>
            <a href="statistics.php">
                <i class="fas fa-chart-bar"></i>
                <span>Thống Kê & Báo Cáo</span>
            </a>
        </li>
    </ul>
</nav> 