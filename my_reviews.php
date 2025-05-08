<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['toast'] = [
        'message' => 'Vui lòng đăng nhập để xem đánh giá của bạn',
        'type' => 'error'
    ];
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Phân trang
    $items_per_page = 6;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Lấy tổng số đánh giá
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE id_account = ?");
    $stmt->execute([$user_id]);
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // Lấy danh sách đánh giá của người dùng
    $stmt = $conn->prepare("
        SELECT r.*, f.food_name, f.image as food_image
        FROM reviews r
        JOIN food f ON r.id_food = f.food_id
        WHERE r.id_account = :user_id
        ORDER BY r.created_at DESC
        LIMIT :offset, :items_per_page
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['toast'] = [
        'message' => 'Có lỗi xảy ra khi lấy dữ liệu đánh giá: ' . $e->getMessage(),
        'type' => 'error'
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá của tôi - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .reviews-container {
            max-width: 1200px;
            margin: 12rem auto 3rem;
            padding: 2rem;
            background: var(--black);
            border-radius: 1rem;
            color: #fff;
        }

        .reviews-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .reviews-header h2 {
            font-size: 2.8rem;
            color: var(--main-color);
            margin-bottom: 1rem;
        }

        .reviews-header p {
            font-size: 1.6rem;
            color: #ccc;
        }

        .reviews-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .review-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(211, 173, 127, 0.3);
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .product-image {
            width: 7rem;
            height: 7rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h3 {
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .review-date {
            font-size: 1.2rem;
            color: #999;
        }

        .star-rating {
            margin: 1rem 0;
        }

        .star-rating i {
            color: var(--main-color);
            font-size: 1.8rem;
            margin-right: 0.3rem;
        }

        .review-comment {
            font-size: 1.5rem;
            line-height: 1.6;
            color: #eee;
            margin-bottom: 1.5rem;
            word-wrap: break-word;
            min-height: 7rem;
        }

        .review-actions {
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }

        .btn-edit {
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .btn-edit:hover {
            background: var(--main-color);
            color: #fff;
        }

        .btn-delete {
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: #fff;
        }

        .no-reviews {
            text-align: center;
            padding: 5rem 0;
        }

        .no-reviews i {
            font-size: 5rem;
            color: var(--main-color);
            margin-bottom: 2rem;
        }

        .no-reviews h3 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
        }

        .no-reviews p {
            font-size: 1.6rem;
            color: #aaa;
            margin-bottom: 2rem;
        }

        .no-reviews .btn {
            display: inline-block;
            background: var(--main-color);
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            transition: all 0.3s ease;
        }

        .no-reviews .btn:hover {
            background: #c19b6c;
            transform: translateY(-3px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 3rem;
        }

        .pagination a {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            margin: 0 0.3rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1.6rem;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: rgba(211, 173, 127, 0.3);
        }

        .pagination a.active {
            background: var(--main-color);
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 10rem;
            right: 2rem;
            padding: 1.5rem;
            border-radius: 0.5rem;
            background-color: rgba(46, 204, 113, 0.9);
            color: white;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
            z-index: 1100;
            transform: translateX(10rem);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.error {
            background-color: rgba(231, 76, 60, 0.9);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast .toast-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toast i {
            font-size: 2rem;
        }

        .toast span {
            font-size: 1.5rem;
        }

        @media screen and (max-width: 768px) {
            .reviews-list {
                grid-template-columns: 1fr;
            }
        }

        /* Styles cho modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1200;
            overflow: auto;
            padding-top: 5rem;
        }

        .modal-content {
            background-color: var(--black);
            margin: 0 auto;
            width: 90%;
            max-width: 600px;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-30px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            color: var(--main-color);
            font-size: 2.2rem;
            margin: 0;
        }

        .close-modal {
            color: #aaa;
            font-size: 2.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--main-color);
        }

        #edit-review-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.6rem;
            color: #ccc;
            margin-bottom: 0.8rem;
        }

        .form-group strong {
            font-size: 1.8rem;
            color: #fff;
        }

        .rating {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .rating-star {
            font-size: 2.5rem;
            color: #aaa;
            cursor: pointer;
            transition: all 0.2s;
        }

        .rating-star:hover,
        .rating-star.active {
            color: var(--main-color);
        }

        #edit-comment {
            width: 100%;
            padding: 1rem;
            font-size: 1.6rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 0.5rem;
            resize: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .form-actions button {
            padding: 1rem 2rem;
            font-size: 1.6rem;
            border-radius: 0.5rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .close-modal {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .btn-submit {
            background: var(--main-color);
            color: #fff;
        }

        .btn-submit:hover {
            background: #c19b6c;
        }

        /* Styles cho modal xác nhận xóa */
        .confirm-modal {
            background-color: var(--black);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 450px;
            text-align: center;
            animation: modalFadeIn 0.3s;
            margin: 0 auto;
        }

        .confirm-header {
            background: rgba(231, 76, 60, 0.1);
            border-bottom: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .confirm-header h3 {
            color: #e74c3c;
            font-size: 2.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .confirm-header .close-modal {
            font-size: 2.4rem;
            background: none;
            padding: 0;
            color: #999;
        }

        .confirm-body {
            padding: 2rem;
        }

        .confirm-body p {
            color: #eee;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }

        .confirm-note {
            color: #999;
            font-size: 1.4rem;
            font-style: italic;
        }

        .confirm-actions {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            padding: 0 2rem 2rem 2rem;
        }

        .btn-cancel {
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-confirm-delete {
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            background: rgba(231, 76, 60, 0.8);
            color: #fff;
            border: none;
            transition: all 0.3s;
        }

        .btn-confirm-delete:hover {
            background: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="reviews-container">
        <div class="reviews-header">
            <h2>Đánh giá của tôi</h2>
            <p>Tất cả đánh giá bạn đã viết về các sản phẩm của TaloFood</p>
        </div>

        <?php if (empty($reviews)): ?>
        <div class="no-reviews">
            <i class="fas fa-star"></i>
            <h3>Bạn chưa có đánh giá nào</h3>
            <p>Hãy mua sản phẩm và đánh giá để chia sẻ ý kiến của bạn</p>
            <a href="menu.php" class="btn">Xem thực đơn</a>
        </div>
        <?php else: ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="product-image">
                        <img src="uploads/foods/<?php echo htmlspecialchars($review['food_image']); ?>" alt="<?php echo htmlspecialchars($review['food_name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($review['food_name']); ?></h3>
                        <span class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                    </div>
                </div>
                <div class="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa<?php echo $i <= $review['star_rating'] ? 's' : 'r'; ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
                <div class="review-comment">
                    <?php echo htmlspecialchars($review['comment']); ?>
                </div>
                <div class="review-actions">
                    <button class="btn-edit" 
                            data-review-id="<?php echo $review['review_id']; ?>"
                            data-food-id="<?php echo $review['id_food']; ?>"
                            data-food-name="<?php echo htmlspecialchars($review['food_name']); ?>"
                            data-rating="<?php echo $review['star_rating']; ?>"
                            data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                        <i class="fas fa-edit"></i> Chỉnh sửa
                    </button>
                    
                    <button class="btn-delete"
                            data-review-id="<?php echo $review['review_id']; ?>"
                            data-food-name="<?php echo htmlspecialchars($review['food_name']); ?>">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $current_page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Chỉnh sửa đánh giá -->
    <div id="edit-review-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Chỉnh sửa đánh giá</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="edit-review-form">
                <input type="hidden" id="edit-review-id">
                <input type="hidden" id="edit-food-id">
                
                <div class="form-group">
                    <label>Món ăn:</label>
                    <strong id="edit-food-name"></strong>
                </div>
                
                <div class="form-group">
                    <label>Đánh giá của bạn:</label>
                    <div class="rating">
                        <i class="far fa-star rating-star" data-value="1"></i>
                        <i class="far fa-star rating-star" data-value="2"></i>
                        <i class="far fa-star rating-star" data-value="3"></i>
                        <i class="far fa-star rating-star" data-value="4"></i>
                        <i class="far fa-star rating-star" data-value="5"></i>
                        <input type="hidden" id="edit-rating" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-comment">Nhận xét:</label>
                    <textarea id="edit-comment" rows="5" placeholder="Nhập nhận xét của bạn về món ăn này..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="close-modal">Hủy</button>
                    <button type="submit" class="btn-submit">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Xác nhận xóa -->
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content confirm-modal">
            <div class="modal-header confirm-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Xác Nhận Xóa</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="confirm-body">
                <p>Bạn có chắc chắn muốn xóa đánh giá cho món "<span id="delete-food-name"></span>"?</p>
                <p class="confirm-note">Lưu ý: Hành động này không thể hoàn tác.</p>
            </div>
            <div class="confirm-actions">
                <button class="btn-cancel close-modal">Hủy</button>
                <button id="btn-confirm-delete" class="btn-confirm-delete">Xác nhận xóa</button>
            </div>
            <input type="hidden" id="delete-review-id">
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables
            const editModal = document.getElementById('edit-review-modal');
            const closeButtons = document.querySelectorAll('.close-modal');
            const stars = document.querySelectorAll('.rating-star');
            const editForm = document.getElementById('edit-review-form');
            const ratingInput = document.getElementById('edit-rating');
            
            // Edit review button
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    const foodId = this.getAttribute('data-food-id');
                    const foodName = this.getAttribute('data-food-name');
                    const rating = parseInt(this.getAttribute('data-rating'));
                    const comment = this.getAttribute('data-comment');
                    
                    // Populate modal fields
                    document.getElementById('edit-review-id').value = reviewId;
                    document.getElementById('edit-food-id').value = foodId;
                    document.getElementById('edit-food-name').textContent = foodName;
                    document.getElementById('edit-comment').value = comment;
                    
                    // Update stars
                    updateStars(rating);
                    
                    // Show modal
                    editModal.style.display = 'block';
                });
            });
            
            // Close modal buttons
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    editModal.style.display = 'none';
                    document.getElementById('delete-confirm-modal').style.display = 'none';
                });
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                } else if (event.target === document.getElementById('delete-confirm-modal')) {
                    document.getElementById('delete-confirm-modal').style.display = 'none';
                }
            });
            
            // Star rating functionality
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    updateStars(value);
                });
                
                star.addEventListener('mouseover', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    stars.forEach(s => {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        if (starValue <= value) {
                            s.className = 'fas fa-star rating-star';
                        } else {
                            s.className = 'far fa-star rating-star';
                        }
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    const currentRating = parseInt(ratingInput.value);
                    updateStars(currentRating);
                });
            });
            
            // Update stars function
            function updateStars(rating) {
                stars.forEach(star => {
                    const value = parseInt(star.getAttribute('data-value'));
                    if (value <= rating) {
                        star.className = 'fas fa-star rating-star active';
                    } else {
                        star.className = 'far fa-star rating-star';
                    }
                });
                
                ratingInput.value = rating;
            }
            
            // Edit review form submit
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const reviewId = document.getElementById('edit-review-id').value;
                const rating = parseInt(ratingInput.value);
                const comment = document.getElementById('edit-comment').value.trim();
                
                // Validate form
                if (rating === 0) {
                    showToast('Vui lòng chọn số sao đánh giá', 'error');
                    return;
                }
                
                if (comment === '') {
                    showToast('Vui lòng nhập nhận xét của bạn', 'error');
                    return;
                }
                
                // Submit form
                fetch('ajax/update_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: reviewId,
                        rating: rating,
                        comment: comment
                    })
                })
                .then(response => response.json())
                .then(data => {
                    editModal.style.display = 'none';
                    
                    if (data.success) {
                        showToast('Cập nhật đánh giá thành công', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra khi cập nhật đánh giá', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi kết nối đến server', 'error');
                });
            });
            
            // Delete review button
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    const foodName = this.getAttribute('data-food-name');
                    
                    // Populate confirm modal
                    document.getElementById('delete-food-name').textContent = foodName;
                    document.getElementById('delete-review-id').value = reviewId;
                    
                    // Show modal
                    document.getElementById('delete-confirm-modal').style.display = 'block';
                });
            });

            // Confirm delete button
            document.getElementById('btn-confirm-delete').addEventListener('click', function() {
                const reviewId = document.getElementById('delete-review-id').value;
                
                // Send delete request
                fetch('ajax/delete_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: reviewId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('delete-confirm-modal').style.display = 'none';
                    
                    if (data.success) {
                        showToast('Đã xóa đánh giá thành công', 'success');
                        
                        // Kiểm tra nếu đây là đánh giá cuối cùng trên trang hiện tại
                        const currentReviews = document.querySelectorAll('.review-card').length;
                        const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
                        
                        if (currentReviews <= 1) {
                            // Nếu đây là đánh giá cuối cùng trên trang và không phải trang 1
                            if (currentPage > 1) {
                                // Chuyển về trang trước đó
                                setTimeout(() => {
                                    window.location.href = 'my_reviews.php?page=' + (parseInt(currentPage) - 1);
                                }, 1500);
                                return;
                            }
                        }
                        
                        // Nếu không phải trường hợp đặc biệt, làm mới trang hiện tại
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra khi xóa đánh giá', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi kết nối đến server', 'error');
                });
            });
            
            // Toast function
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);
                
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
            
            <?php
            if (isset($_SESSION['toast'])) {
                echo "showToast('" . $_SESSION['toast']['message'] . "', '" . $_SESSION['toast']['type'] . "');";
                unset($_SESSION['toast']);
            }
            ?>
        });
    </script>
</body>
</html>