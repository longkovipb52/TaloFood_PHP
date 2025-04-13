<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

try {
    // Lấy danh sách danh mục kèm số lượng món ăn
    $stmt = $conn->query("
        SELECT fc.foodcategory_id, fc.foodcategory_name, fc.image,
               COUNT(f.food_id) as food_count 
        FROM food_category fc 
        LEFT JOIN food f ON fc.foodcategory_id = f.id_category 
        GROUP BY fc.foodcategory_id, fc.foodcategory_name, fc.image
        ORDER BY fc.foodcategory_id DESC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = 'Lỗi khi lấy dữ liệu: ' . $e->getMessage();
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục - TaloFood</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .category-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .table-hover tbody tr {
            transition: all 0.3s ease;
        }
        .table-hover tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }
        .btn-group .btn {
            margin: 0 2px;
            transition: all 0.3s ease;
        }
        .btn-group .btn:hover {
            transform: scale(1.1);
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
                        <h5 class="card-title mb-0">Danh Sách Danh Mục</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus"></i> Thêm Danh Mục
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="categoryTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên danh mục</th>
                                        <th>Số lượng món</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['foodcategory_id']; ?></td>
                                            <td>
                                                <?php if ($category['image']): ?>
                                                    <img src="../uploads/categories/<?php echo $category['image']; ?>" class="category-image" alt="<?php echo htmlspecialchars($category['foodcategory_name']); ?>">
                                                <?php else: ?>
                                                    <img src="../assets/images/no-image.jpg" class="category-image" alt="No Image">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['foodcategory_name']); ?></td>
                                            <td><?php echo $category['food_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-info btn-sm" onclick="viewCategory(<?php echo $category['foodcategory_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo $category['foodcategory_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['foodcategory_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm Danh Mục -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Danh Mục Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_image" class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" id="category_image" name="image" accept="image/*">
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

    <!-- Modal Sửa Danh Mục -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa Danh Mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_category_id" name="category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_image" class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" id="edit_category_image" name="image" accept="image/*">
                            <div id="current_image" class="mt-2"></div>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Xử lý toggle sidebar
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar').toggleClass('active');
            $('#content').toggleClass('active');
        });

        // Initialize DataTable
        $('#categoryTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]]
        });

        // Xử lý thêm danh mục
        $('#addCategoryForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: 'ajax/add_category.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: data.message
                            }).then(() => {
                                location.reload();
                            });
                            $('#addCategoryModal').modal('hide');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: data.message
                            });
                        }
                    } catch (e) {
                        console.error(e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Có lỗi xảy ra khi xử lý dữ liệu'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server'
                    });
                }
            });
        });

        // Xử lý sửa danh mục
        $('#editCategoryForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: 'ajax/edit_category.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: data.message
                            }).then(() => {
                                location.reload();
                            });
                            $('#editCategoryModal').modal('hide');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: data.message
                            });
                        }
                    } catch (e) {
                        console.error(e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Có lỗi xảy ra khi xử lý dữ liệu'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể kết nối đến server'
                    });
                }
            });
        });
    });

    // Hàm xem chi tiết danh mục
    function viewCategory(id) {
        $.ajax({
            url: 'ajax/get_category.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        let imageHtml = data.category.image 
                            ? `<img src="../uploads/categories/${data.category.image}" class="img-fluid mb-3" alt="${data.category.foodcategory_name}">`
                            : `<img src="../assets/images/no-image.jpg" class="img-fluid mb-3" alt="No Image">`;

                        Swal.fire({
                            title: 'Chi tiết danh mục',
                            html: `
                                <div class="text-center mb-3">
                                    ${imageHtml}
                                </div>
                                <div class="text-start">
                                    <p><strong>ID:</strong> ${data.category.foodcategory_id}</p>
                                    <p><strong>Tên danh mục:</strong> ${data.category.foodcategory_name}</p>
                                    <p><strong>Số lượng món:</strong> ${data.category.food_count}</p>
                                </div>
                            `,
                            confirmButtonText: 'Đóng'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: data.message
                        });
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Có lỗi xảy ra khi xử lý dữ liệu'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể kết nối đến server'
                });
            }
        });
    }

    // Hàm sửa danh mục
    function editCategory(id) {
        $.ajax({
            url: 'ajax/get_category.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#edit_category_id').val(data.category.foodcategory_id);
                        $('#edit_category_name').val(data.category.foodcategory_name);
                        
                        if (data.category.image) {
                            $('#current_image').html(`
                                <img src="../uploads/categories/${data.category.image}" class="img-thumbnail" style="height: 100px;" alt="${data.category.foodcategory_name}">
                            `);
                        } else {
                            $('#current_image').html('Chưa có hình ảnh');
                        }
                        
                        $('#editCategoryModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: data.message
                        });
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Có lỗi xảy ra khi xử lý dữ liệu'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể kết nối đến server'
                });
            }
        });
    }

    // Hàm xóa danh mục
    function deleteCategory(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: "Bạn có chắc chắn muốn xóa danh mục này?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete_category.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công',
                                    text: data.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: data.message
                                });
                            }
                        } catch (e) {
                            console.error(e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Có lỗi xảy ra khi xử lý dữ liệu'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Không thể kết nối đến server'
                        });
                    }
                });
            }
        });
    }
    </script>
</body>
</html> 