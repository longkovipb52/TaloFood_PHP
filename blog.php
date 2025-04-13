<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Phân trang
    $items_per_page = 6;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Lấy tổng số bài viết
    $stmt = $conn->prepare("SELECT COUNT(*) FROM blog WHERE status = 'published'");
    $stmt->execute();
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // Lấy danh sách bài viết
    $stmt = $conn->prepare("
        SELECT b.*, a.username, a.profile_image 
        FROM blog b
        JOIN account a ON b.author_id = a.account_id
        WHERE b.status = 'published'
        ORDER BY b.created_at DESC
        LIMIT :offset, :items_per_page
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy các bài viết nổi bật (có nhiều lượt xem nhất)
    $stmt = $conn->prepare("
        SELECT b.*, a.username 
        FROM blog b
        JOIN account a ON b.author_id = a.account_id
        WHERE b.status = 'published'
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $featured_blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/blog.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="blog-hero">
        <div class="blog-hero-content">
            <h1>Blog TaloFood</h1>
            <p>Khám phá thế giới ẩm thực qua những câu chuyện của chúng tôi</p>
        </div>
    </section>

    <section class="featured-posts">
        <div class="container">
            <h2 class="section-title">Bài viết nổi bật</h2>
            <div class="featured-grid">
                <?php foreach ($featured_blogs as $blog): ?>
                <div class="featured-card">
                    <div class="featured-image">
                        <img src="uploads/blogs/<?php echo htmlspecialchars($blog['image']); ?>" 
                             alt="<?php echo htmlspecialchars($blog['title']); ?>">
                    </div>
                    <div class="featured-content">
                        <div class="meta">
                            <span class="author">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($blog['username']); ?>
                            </span>
                            <span class="date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                            </span>
                        </div>
                        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                        <p><?php echo substr(strip_tags($blog['content']), 0, 150); ?>...</p>
                        <a href="blog_detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn">Đọc tiếp</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="all-posts">
        <div class="container">
            <h2 class="section-title">Tất cả bài viết</h2>
            <div class="posts-grid">
                <?php foreach ($blogs as $blog): ?>
                <div class="post-card">
                    <div class="post-image">
                        <img src="uploads/blogs/<?php echo htmlspecialchars($blog['image']); ?>" 
                             alt="<?php echo htmlspecialchars($blog['title']); ?>">
                    </div>
                    <div class="post-content">
                        <div class="meta">
                            <span class="author">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($blog['username']); ?>
                            </span>
                            <span class="date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                            </span>
                        </div>
                        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                        <p><?php echo substr(strip_tags($blog['content']), 0, 100); ?>...</p>
                        <a href="blog_detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn">Đọc tiếp</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   class="<?php echo $current_page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/home.js"></script>
</body>
</html> 