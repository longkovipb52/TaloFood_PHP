<?php
session_start();
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giới thiệu - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/about.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="about-hero-content">
            <h1>Về Chúng Tôi</h1>
            <p>Khám phá câu chuyện và giá trị của TaloFood</p>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story">
        <div class="container">
            <h2 class="section-title">Câu Chuyện Của Chúng Tôi</h2>
            <div class="story-content">
                <div class="story-image">
                    <img src="image/story.jpg" alt="Câu chuyện của chúng tôi">
                </div>
                <div class="story-text">
                    <h3>Khởi đầu từ đam mê</h3>
                    <p>TaloFood được thành lập với mong muốn mang đến những món ăn nhanh ngon miệng và chất lượng nhất.</p>
                    <p>Chúng tôi cam kết sử dụng nguyên liệu tươi ngon và phục vụ khách hàng với tất cả tâm huyết.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values Section -->
    <section class="core-values">
        <div class="container">
            <h2 class="section-title">Giá Trị Cốt Lõi</h2>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Tươi ngon mỗi ngày</h3>
                    <p>Chúng tôi lựa chọn những nguyên liệu tươi sạch nhất để đảm bảo chất lượng món ăn.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-heart"></i>
                    <h3>Khách hàng là trung tâm</h3>
                    <p>Luôn lắng nghe và phục vụ tận tâm để mang đến trải nghiệm tốt nhất.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Giao hàng nhanh chóng</h3>
                    <p>Đảm bảo thức ăn đến tay khách hàng nóng hổi và đúng giờ.</p>
                </div>
            </div>
        </div>
    </section>
    

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/home.js"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</body>
</html> 