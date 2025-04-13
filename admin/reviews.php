<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Lấy danh sách đánh giá kèm thông tin người dùng và món ăn
    $stmt = $conn->query("
        SELECT r.*, a.username, f.food_name, f.image as food_image
        FROM reviews r
        LEFT JOIN account a ON r.id_account = a.account_id
        LEFT JOIN food f ON r.id_food = f.food_id
        ORDER BY r.created_at DESC
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách món ăn để lọc
    $stmt = $conn->query("SELECT food_id, food_name FROM food ORDER BY food_name");
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $reviews = [];
    $foods = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đánh Giá - TaloFood</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <style>
        .food-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .star-rating {
            color: #FFD700;
        }
        .comment-column {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Content -->
        <div id="content">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <!-- Main Content -->
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Quản Lý Đánh Giá Khách Hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                        <!-- Reviews Table -->
                        <div class="table-responsive">
                            <table id="reviewsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Món ăn</th>
                                        <th>Người dùng</th>
                                        <th>Đánh giá</th>
                                        <th>Bình luận</th>
                                        <th>Ngày đánh giá</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($reviews as $review): ?>
                                        <tr>
                                            <td><?= $review['review_id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if($review['food_image']): ?>
                                                        <img src="../uploads/foods/<?= $review['food_image'] ?>" alt="<?= $review['food_name'] ?>" class="food-image me-2">
                                                    <?php else: ?>
                                                        <div class="food-image me-2 bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?= $review['food_name'] ?>
                                                </div>
                                            </td>
                                            <td><?= $review['username'] ?></td>
                                            <td class="star-rating" data-rating="<?= $review['star_rating'] ?>">
                                                <?php for($i = 0; $i < 5; $i++): ?>
                                                    <?php if($i < $review['star_rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </td>
                                            <td class="comment-column"><?= $review['comment'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-review" data-id="<?= $review['review_id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-review" data-id="<?= $review['review_id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Review Modal -->
    <div class="modal fade" id="viewReviewModal" tabindex="-1" aria-labelledby="viewReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewReviewModalLabel">Chi tiết đánh giá</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <img src="" alt="Hình ảnh món ăn" id="modalFoodImage" class="img-fluid rounded">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h5 id="modalFoodName"></h5>
                                <div class="star-rating mb-2" id="modalStarRating"></div>
                                <p><strong>Người đánh giá:</strong> <span id="modalUsername"></span></p>
                                <p><strong>Thời gian:</strong> <span id="modalCreatedAt"></span></p>
                            </div>
                            <div class="mb-3">
                                <h6>Bình luận:</h6>
                                <p id="modalComment" class="p-3 bg-light rounded"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteReviewModal" tabindex="-1" aria-labelledby="deleteReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteReviewModalLabel">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa đánh giá này không?</p>
                    <p class="text-danger"><small>Lưu ý: Hành động này không thể hoàn tác!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteReview">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        $(document).ready(function() {
            // Xử lý toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Initialize DataTable
            const reviewsTable = $('#reviewsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[5, 'desc']], // Sắp xếp theo ngày đánh giá mới nhất
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
                columnDefs: [
                    {
                        targets: 3, // Cột đánh giá sao
                        type: 'num',
                        render: function(data, type) {
                            if (type === 'sort') {
                                // Trả về số sao để sắp xếp
                                return $(data).filter('.fas.fa-star').length;
                            }
                            // Trả về HTML cho hiển thị
                            return data;
                        }
                    },
                    {
                        targets: [0, 1, 2, 4, 5, 6], // Các cột khác
                        orderable: true
                    }
                ]
            });

            // Xem chi tiết đánh giá
            $('.view-review').on('click', function() {
                const reviewId = $(this).data('id');
                
                $.ajax({
                    url: 'ajax/get_review.php',
                    type: 'GET',
                    data: { review_id: reviewId },
                    success: function(response) {
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                const review = result.data;
                                
                                // Cập nhật nội dung modal
                                $('#modalFoodName').text(review.food_name);
                                $('#modalFoodImage').attr('src', review.food_image_url || '../assets/images/no-image.jpg');
                                $('#modalUsername').text(review.username);
                                $('#modalStarRating').html(review.stars_html);
                                $('#modalComment').text(review.comment || 'Không có bình luận');
                                $('#modalCreatedAt').text(review.created_at_formatted);

                                // Hiển thị modal
                                $('#viewReviewModal').modal('show');
                            } else {
                                Toastify({
                                    text: result.message || "Đã xảy ra lỗi khi lấy thông tin đánh giá!",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#dc3545",
                                }).showToast();
                            }
                        } catch (error) {
                            console.error('Parse Error:', error);
                            Toastify({
                                text: "Đã xảy ra lỗi khi lấy thông tin đánh giá!",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#dc3545",
                            }).showToast();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Toastify({
                            text: "Lỗi kết nối server!",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                });
            });

            // Xóa đánh giá
            let reviewIdToDelete;

            $('.delete-review').on('click', function() {
                reviewIdToDelete = $(this).data('id');
                $('#deleteReviewModal').modal('show');
            });

            $('#confirmDeleteReview').on('click', function() {
                $.ajax({
                    url: 'ajax/delete_review.php',
                    type: 'POST',
                    data: { review_id: reviewIdToDelete },
                    success: function(response) {
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                Toastify({
                                    text: "Đã xóa đánh giá thành công!",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#28a745",
                                }).showToast();

                                // Xóa dòng khỏi DataTable
                                reviewsTable.row($(`button.delete-review[data-id="${reviewIdToDelete}"]`).closest('tr')).remove().draw();

                                // Đóng modal
                                $('#deleteReviewModal').modal('hide');
                            } else {
                                Toastify({
                                    text: result.message || "Đã xảy ra lỗi khi xóa đánh giá!",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#dc3545",
                                }).showToast();
                            }
                        } catch (error) {
                            console.error('Parse Error:', error);
                            Toastify({
                                text: "Đã xảy ra lỗi khi xóa đánh giá!",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#dc3545",
                            }).showToast();
                        }
                        $('#deleteReviewModal').modal('hide');
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Toastify({
                            text: "Lỗi kết nối server!",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                        $('#deleteReviewModal').modal('hide');
                    }
                });
            });
        });
    </script>
</body>
</html>