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
    // Lấy danh sách blog kèm thông tin tác giả
    $stmt = $conn->query("
        SELECT b.*, a.username as author_name 
        FROM blog b 
        LEFT JOIN account a ON b.author_id = a.account_id 
        ORDER BY b.created_at DESC
    ");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $blogs = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Blog - TaloFood</title>
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
        .blog-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .title-column {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .content-column {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-column {
            white-space: nowrap;
            min-width: 120px;
        }
        .action-column .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0 1px;
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
                        <h5 class="card-title mb-0">Quản Lý Blog</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBlogModal">
                            <i class="fas fa-plus"></i> Thêm Bài Viết
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="blogsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hình ảnh</th>
                                        <th>Tiêu đề</th>
                                        <th>Nội dung</th>
                                        <th>Tác giả</th>
                                        <th>Ngày tạo</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blogs as $blog): ?>
                                        <tr>
                                            <td><?php echo $blog['blog_id']; ?></td>
                                            <td>
                                                <?php if ($blog['image']): ?>
                                                    <img src="../uploads/blogs/<?php echo $blog['image']; ?>" class="blog-image" alt="<?php echo $blog['title']; ?>">
                                                <?php else: ?>
                                                    <img src="../assets/images/no-image.jpg" class="blog-image" alt="No Image">
                                                <?php endif; ?>
                                            </td>
                                            <td class="title-column"><?php echo htmlspecialchars($blog['title']); ?></td>
                                            <td class="content-column"><?php echo htmlspecialchars($blog['content']); ?></td>
                                            <td><?php echo htmlspecialchars($blog['author_name']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($blog['created_at'])); ?></td>
                                            <td>
                                                <?php if ($blog['status'] == 'published'): ?>
                                                    <span class="badge bg-success">Đã đăng</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Nháp</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-column">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info" onclick="viewBlog(<?php echo $blog['blog_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="editBlog(<?php echo $blog['blog_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteBlog(<?php echo $blog['blog_id']; ?>)">
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

    <!-- Add Blog Modal -->
    <div class="modal fade" id="addBlogModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Bài Viết Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addBlogForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung</label>
                            <textarea class="form-control" name="content" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status" required>
                                <option value="draft">Nháp</option>
                                <option value="published">Đăng ngay</option>
                            </select>
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

    <!-- Edit Blog Modal -->
    <div class="modal fade" id="editBlogModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Bài Viết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editBlogForm" enctype="multipart/form-data">
                    <input type="hidden" name="blog_id" id="edit_blog_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung</label>
                            <textarea class="form-control" name="content" id="edit_content" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="current_image" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="draft">Nháp</option>
                                <option value="published">Đăng</option>
                            </select>
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

    <!-- View Blog Modal -->
    <div class="modal fade" id="viewBlogModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết bài viết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewBlogContent">
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
            $('#blogsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[5, 'desc']], // Sắp xếp theo ngày tạo giảm dần
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
                responsive: true
            });

            // Xử lý thêm blog
            $('#addBlogForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('author_id', <?php echo $_SESSION['user_id']; ?>);
                
                $.ajax({
                    url: 'ajax/add_blog.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Thêm bài viết thành công!');
                                $('#addBlogModal').modal('hide');
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

            // Xử lý sửa blog
            $('#editBlogForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $.ajax({
                    url: 'ajax/edit_blog.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Cập nhật bài viết thành công!');
                                $('#editBlogModal').modal('hide');
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

        // Xem chi tiết blog
        function viewBlog(blogId) {
            $.ajax({
                url: 'ajax/get_blog.php',
                type: 'GET',
                data: { id: blogId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const blog = data.blog;
                            let imageHtml = blog.image 
                                ? `<img src="../uploads/blogs/${blog.image}" class="img-fluid mb-3 rounded" alt="${blog.title}">`
                                : `<img src="../assets/images/no-image.jpg" class="img-fluid mb-3 rounded" alt="No Image">`;

                            let content = `
                                <div class="text-center mb-3">
                                    ${imageHtml}
                                </div>
                                <div class="blog-info">
                                    <h4>${blog.title}</h4>
                                    <p class="text-muted">
                                        <small>
                                            Tác giả: ${blog.author_name} | 
                                            Đăng ngày: ${new Date(blog.created_at).toLocaleDateString('vi-VN')} |
                                            Trạng thái: <span class="badge ${blog.status === 'published' ? 'bg-success' : 'bg-warning'}">
                                                ${blog.status === 'published' ? 'Đã đăng' : 'Nháp'}
                                            </span>
                                        </small>
                                    </p>
                                    <div class="content mt-3">
                                        ${blog.content}
                                    </div>
                                </div>
                            `;
                            $('#viewBlogContent').html(content);
                            $('#viewBlogModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin bài viết!');
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

        // Sửa blog
        function editBlog(blogId) {
            $.ajax({
                url: 'ajax/get_blog.php',
                type: 'GET',
                data: { id: blogId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const blog = data.blog;
                            $('#edit_blog_id').val(blog.blog_id);
                            $('#edit_title').val(blog.title);
                            $('#edit_content').val(blog.content);
                            $('#edit_status').val(blog.status);
                            
                            if (blog.image) {
                                $('#current_image').html(`
                                    <img src="../uploads/blogs/${blog.image}" class="img-thumbnail" style="height: 100px;" alt="${blog.title}">
                                `);
                            } else {
                                $('#current_image').html('Chưa có hình ảnh');
                            }
                            
                            $('#editBlogModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin bài viết!');
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

        // Xóa blog
        function deleteBlog(blogId) {
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Bạn có chắc chắn muốn xóa bài viết này không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/delete_blog.php',
                        type: 'POST',
                        data: { blog_id: blogId },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    Toast.success('Xóa bài viết thành công!');
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
            });
        }
    </script>
</body>
</html> 