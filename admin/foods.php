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
    // Lấy danh sách món ăn kèm thông tin danh mục
    $stmt = $conn->query("
        SELECT f.*, fc.foodcategory_name 
        FROM food f 
        LEFT JOIN food_category fc ON f.id_category = fc.foodcategory_id 
        ORDER BY f.food_id DESC
    ");
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách danh mục để dùng trong form thêm/sửa
    $stmt = $conn->query("SELECT * FROM food_category ORDER BY foodcategory_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $foods = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Món Ăn - TaloFood</title>
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
        .price-column {
            min-width: 100px;
        }
        .description-column {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-column {
            white-space: nowrap;
            min-width: 140px;
        }
        .action-column .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0 1px;
        }
        .action-column .btn i {
            font-size: 0.875rem;
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
                        <h5 class="card-title mb-0">Danh Sách Món Ăn</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">
                            <i class="fas fa-plus"></i> Thêm Món Ăn
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="foodsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên món</th>
                                        <th>Danh mục</th>
                                        <th>Giá cũ</th>
                                        <th>Giá mới</th>
                                        <th>Đã bán</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($foods as $food): ?>
                                        <tr>
                                            <td><?php echo $food['food_id']; ?></td>
                                            <td>
                                                <?php if ($food['image']): ?>
                                                    <img src="../uploads/foods/<?php echo $food['image']; ?>" class="food-image" alt="<?php echo $food['food_name']; ?>">
                                                <?php else: ?>
                                                    <img src="../assets/images/no-image.jpg" class="food-image" alt="No Image">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($food['food_name']); ?></td>
                                            <td><?php echo htmlspecialchars($food['foodcategory_name']); ?></td>
                                            <td class="price-column"><?php echo number_format($food['price'], 0, ',', '.') . 'đ'; ?></td>
                                            <td class="new-price-column"><?php echo number_format($food['new_price'], 0, ',', '.') . 'đ'; ?></td>
                                            <td class="text-center"><?php echo $food['total_sold']; ?></td>
                                            <td class="description-column"><?php echo htmlspecialchars($food['description']); ?></td>
                                            <td>
                                                <?php if ($food['status'] == 1): ?>
                                                    <span class="badge bg-success status-badge">Đang bán</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger status-badge">Ngừng bán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-column">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info" onclick="viewFood(<?php echo $food['food_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="editFood(<?php echo $food['food_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($food['status'] == 1): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="toggleFoodStatus(<?php echo $food['food_id']; ?>, 0)">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success" onclick="toggleFoodStatus(<?php echo $food['food_id']; ?>, 1)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteFood(<?php echo $food['food_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- Add Food Modal -->
    <div class="modal fade" id="addFoodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Món Ăn Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addFoodForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên món ăn</label>
                            <input type="text" class="form-control" name="food_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['foodcategory_id']; ?>">
                                        <?php echo htmlspecialchars($category['foodcategory_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá cũ</label>
                            <input type="number" class="form-control" name="price" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá mới (khuyến mãi)</label>
                            <input type="number" class="form-control" name="new_price" min="0" placeholder="Để trống nếu không có khuyến mãi">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Food Modal -->
    <div class="modal fade" id="editFoodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Món Ăn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editFoodForm" enctype="multipart/form-data">
                    <input type="hidden" name="food_id" id="edit_food_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên món ăn</label>
                            <input type="text" class="form-control" name="food_name" id="edit_food_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['foodcategory_id']; ?>">
                                        <?php echo htmlspecialchars($category['foodcategory_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá cũ</label>
                            <input type="number" class="form-control" name="price" id="edit_price" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá mới (khuyến mãi)</label>
                            <input type="number" class="form-control" name="new_price" id="edit_new_price" min="0" placeholder="Để trống nếu không có khuyến mãi">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="current_image" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Food Modal -->
    <div class="modal fade" id="viewFoodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết món ăn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewFoodContent">
                    <!-- Nội dung sẽ được thêm bằng JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../assets/js/toast.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Xử lý toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Initialize DataTable
            $('#foodsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'desc']], // Sắp xếp theo ID giảm dần
                pageLength: 5, // Số dòng mỗi trang
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]], // Tùy chọn số dòng hiển thị
                responsive: true, // Responsive
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>', // Layout của DataTable
                pagingType: "full_numbers" // Kiểu phân trang đầy đủ
            });

            // Xử lý thêm món ăn
            $('#addFoodForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $.ajax({
                    url: 'ajax/add_food.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Thêm món ăn thành công!');
                                $('#addFoodModal').modal('hide');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Toast.error(data.message || 'Có lỗi xảy ra!');
                            }
                        } catch (error) {
                            console.error('Parse Error:', error);
                            Toast.error('Có lỗi xảy ra khi xử lý dữ liệu!');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Toast.error('Lỗi kết nối server!');
                    }
                });
            });

            // Xử lý sửa món ăn
            $('#editFoodForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $.ajax({
                    url: 'ajax/edit_food.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Cập nhật món ăn thành công!');
                                $('#editFoodModal').modal('hide');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Toast.error(data.message || 'Có lỗi xảy ra!');
                            }
                        } catch (error) {
                            console.error('Parse Error:', error);
                            Toast.error('Có lỗi xảy ra khi xử lý dữ liệu!');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Toast.error('Lỗi kết nối server!');
                    }
                });
            });
        });

        // Xem chi tiết món ăn
        function viewFood(foodId) {
            $.ajax({
                url: 'ajax/get_food.php',
                type: 'GET',
                data: { id: foodId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const food = data.food;
                            let imageHtml = food.image 
                                ? `<img src="../uploads/foods/${food.image}" class="img-fluid mb-3 rounded" alt="${food.food_name}">`
                                : `<img src="../assets/images/no-image.jpg" class="img-fluid mb-3 rounded" alt="No Image">`;
                            
                            // Xử lý hiển thị giá
                            let priceHtml = `<p><strong>Giá gốc:</strong> ${food.price_formatted}</p>`;
                            if (food.new_price) {
                                priceHtml += `
                                    <p><strong>Giá khuyến mãi:</strong> 
                                        <span class="text-danger">${food.new_price_formatted}</span>
                                        <span class="badge bg-danger ms-2">
                                            -${Math.round((1 - food.new_price/food.price) * 100)}%
                                        </span>
                                    </p>`;
                            }

                            let content = `
                                <div class="text-center mb-3">
                                    ${imageHtml}
                                </div>
                                <div class="food-info">
                                    <p><strong>ID:</strong> ${food.food_id}</p>
                                    <p><strong>Tên món:</strong> ${food.food_name}</p>
                                    <p><strong>Danh mục:</strong> ${food.foodcategory_name}</p>
                                    ${priceHtml}
                                    <p><strong>Đã bán:</strong> ${food.total_sold} đơn vị</p>
                                    <p><strong>Mô tả:</strong> ${food.description || 'Không có mô tả'}</p>
                                    <p><strong>Trạng thái:</strong> ${food.status == 1 ? 
                                        '<span class="badge bg-success">Đang bán</span>' : 
                                        '<span class="badge bg-danger">Ngừng bán</span>'}</p>
                                </div>
                            `;
                            $('#viewFoodContent').html(content);
                            $('#viewFoodModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin món ăn!');
                        }
                    } catch (error) {
                        console.error('Parse Error:', error);
                        Toast.error('Có lỗi xảy ra!');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    Toast.error('Lỗi kết nối server!');
                }
            });
        }

        // Sửa thông tin món ăn
        function editFood(foodId) {
            $.ajax({
                url: 'ajax/get_food.php',
                type: 'GET',
                data: { id: foodId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const food = data.food;
                            $('#edit_food_id').val(food.food_id);
                            $('#edit_food_name').val(food.food_name);
                            $('#edit_category_id').val(food.id_category);
                            $('#edit_price').val(food.price);
                            $('#edit_new_price').val(food.new_price);
                            $('#edit_description').val(food.description);
                            
                            if (food.image) {
                                $('#current_image').html(`
                                    <img src="../uploads/foods/${food.image}" class="img-thumbnail" style="height: 100px;" alt="${food.food_name}">
                                `);
                            } else {
                                $('#current_image').html('Chưa có hình ảnh');
                            }
                            
                            $('#editFoodModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin món ăn!');
                        }
                    } catch (error) {
                        console.error('Parse Error:', error);
                        Toast.error('Có lỗi xảy ra!');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    Toast.error('Lỗi kết nối server!');
                }
            });
        }

        // Thay đổi trạng thái món ăn
        function toggleFoodStatus(foodId, newStatus) {
            if (confirm('Bạn có chắc chắn muốn thay đổi trạng thái món ăn này?')) {
                $.ajax({
                    url: 'ajax/toggle_food_status.php',
                    type: 'POST',
                    data: { 
                        id: foodId,
                        status: newStatus
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success('Cập nhật trạng thái thành công!');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Toast.error(data.message || 'Có lỗi xảy ra!');
                            }
                        } catch (error) {
                            console.error('Parse Error:', error);
                            Toast.error('Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Toast.error('Lỗi kết nối server!');
                    }
                });
            }
        }

        function deleteFood(foodId) {
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Bạn có chắc chắn muốn xóa món ăn này không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Gửi request xóa
                    const formData = new FormData();
                    formData.append('food_id', foodId);

                    fetch('ajax/delete_food.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Đã xóa!',
                                data.message,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Lỗi!',
                                data.message,
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'Lỗi!',
                            'Đã xảy ra lỗi khi xóa món ăn',
                            'error'
                        );
                    });
                }
            });
        }
    </script>
</body>
</html> 