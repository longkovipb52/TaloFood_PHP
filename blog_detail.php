<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Kiểm tra ID bài viết
    if (!isset($_GET['id'])) {
        header('Location: blog.php');
        exit();
    }

    $blog_id = (int)$_GET['id'];

    // Lấy thông tin chi tiết bài viết
    $stmt = $conn->prepare("
        SELECT b.*, a.username, a.profile_image 
        FROM blog b
        JOIN account a ON b.author_id = a.account_id
        WHERE b.blog_id = :blog_id AND b.status = 'published'
    ");
    $stmt->bindValue(':blog_id', $blog_id, PDO::PARAM_INT);
    $stmt->execute();
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$blog) {
        header('Location: blog.php');
        exit();
    }

    // Lấy các bài viết liên quan (cùng tác giả)
    $stmt = $conn->prepare("
        SELECT b.*, a.username 
        FROM blog b
        JOIN account a ON b.author_id = a.account_id
        WHERE b.author_id = :author_id 
        AND b.blog_id != :blog_id 
        AND b.status = 'published'
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->bindValue(':author_id', $blog['author_id'], PDO::PARAM_INT);
    $stmt->bindValue(':blog_id', $blog_id, PDO::PARAM_INT);
    $stmt->execute();
    $related_blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($blog['title']); ?> - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/blog.css">
    <link rel="stylesheet" href="assets/css/blog_detail.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="blog-detail">
        <div class="container">
            <div class="blog-header">
                <div class="blog-meta">
                    <span class="author">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($blog['username']); ?>
                    </span>
                    <span class="date">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                    </span>
                </div>
                <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
            </div>

            <div class="blog-image">
                <img src="uploads/blogs/<?php echo htmlspecialchars($blog['image']); ?>" 
                     alt="<?php echo htmlspecialchars($blog['title']); ?>">
            </div>

            <div class="blog-content">
                <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
            </div>

            <?php if (!empty($related_blogs)): ?>
            <div class="related-posts">
                <h2 class="section-title">Bài viết liên quan</h2>
                <div class="posts-grid">
                    <?php foreach ($related_blogs as $related): ?>
                    <div class="post-card">
                        <div class="post-image">
                            <img src="uploads/blogs/<?php echo htmlspecialchars($related['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>">
                        </div>
                        <div class="post-content">
                            <div class="meta">
                                <span class="date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($related['created_at'])); ?>
                                </span>
                            </div>
                            <h3><?php echo htmlspecialchars($related['title']); ?></h3>
                            <p><?php echo substr(strip_tags($related['content']), 0, 100); ?>...</p>
                            <a href="blog_detail.php?id=<?php echo $related['blog_id']; ?>" class="btn">Đọc tiếp</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/home.js"></script>
</body>
</html> 