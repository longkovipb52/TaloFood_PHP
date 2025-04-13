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
    
    // Lấy danh sách người dùng
    $stmt = $conn->query("SELECT * FROM account where id_role != 1 ORDER BY account_id DESC");
    

    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng - TaloFood</title>
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
        .user-info {
            padding: 15px;
        }

        .user-info p {
            margin-bottom: 10px;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .user-info p:last-child {
            border-bottom: none;
        }

        .user-info strong {
            display: inline-block;
            width: 120px;
            color: #666;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .modal-footer {
            background-color: #f8f9fa;
            border-radius: 0 0 10px 10px;
        }

        /* CSS cho ảnh đại diện */
        .avatar-column {
            width: 70px;
            text-align: center;
        }

        .avatar-preview {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .avatar-preview:hover {
            transform: scale(1.1);
        }

        #current_profile_image img {
            max-width: 100px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Thêm CSS cho phần nút thao tác */
        td .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        td .btn i {
            font-size: 0.875rem;
        }

        /* Đảm bảo cột thao tác đủ rộng */
        #usersTable th:last-child,
        #usersTable td:last-child {
            min-width: 160px;
            text-align: center;
            white-space: nowrap;
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
                        <h5 class="card-title mb-0">Danh Sách Người Dùng</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Thêm Người Dùng
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh đại diện</th>
                                        <th>Tên đăng nhập</th>
                                        <th>Email</th>
                                        <th>Số điện thoại</th>
                                        <th>Vai trò</th>
                                        <th>Trạng thái</th>
                                        <th>Địa chỉ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['account_id']; ?></td>
                                                <td>
                                                    <?php if ($user['profile_image']): ?>
                                                        <img src="../uploads/avatars/<?php echo $user['profile_image']; ?>" 
                                                            class="rounded-circle" 
                                                            alt="Avatar" 
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <img src="../assets/images/default-avatar.png" 
                                                            class="rounded-circle" 
                                                            alt="Default Avatar" 
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                <td>
                                                    <?php 
                                                        echo ($user['id_role'] == 1) ? 'Admin' : 'User';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['status'] == 1): ?>
                                                        <span class="badge bg-success">Hoạt động</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Đã khóa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['account_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['account_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['status'] == 1): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="toggleUserStatus(<?php echo $user['account_id']; ?>, 0)">
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success" onclick="toggleUserStatus(<?php echo $user['account_id']; ?>, 1)">
                                                            <i class="fas fa-lock-open"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['account_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Người Dùng Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <small class="text-muted">Cho phép: JPG, JPEG, PNG, GIF (Tối đa 2MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vai trò</label>
                            <select class="form-select" name="role">
                                <option value="2">Người dùng</option>
                                <option value="1">Admin</option>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Người Dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="account_id" id="edit_account_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="address" id="edit_address" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <div id="current_profile_image" class="mt-2"></div>
                            <small class="text-muted">Để trống nếu không muốn thay đổi ảnh</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vai trò</label>
                            <select class="form-select" name="role" id="edit_role">
                                <option value="2">Người dùng</option>
                                <option value="1">Admin</option>
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

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thông tin người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewUserContent">
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
    <script>
        $(document).ready(function() {
            // Xử lý toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Initialize DataTable
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'desc']] // Sắp xếp theo cột ID giảm dần
            });

            // Xử lý thêm người dùng
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $.ajax({
                    url: 'ajax/add_user.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Add User Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Thêm người dùng thành công!');
                                $('#addUserModal').modal('hide');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
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

            // Xử lý sửa người dùng
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $.ajax({
                    url: 'ajax/edit_user.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Edit User Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Cập nhật thông tin thành công!');
                                $('#editUserModal').modal('hide');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
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

        // Xem thông tin người dùng
        function viewUser(userId) {
            $.ajax({
                url: 'ajax/get_user.php',
                type: 'GET',
                data: { id: userId },
                success: function(response) {
                    console.log('Get User Response:', response);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const user = data.user;
                            let content = `
                                <div class="user-info">
                                    <p><strong>ID:</strong> ${user.account_id}</p>
                                    <p><strong>Tên đăng nhập:</strong> ${user.username}</p>
                                    <p><strong>Email:</strong> ${user.email}</p>
                                    <p><strong>Số điện thoại:</strong> ${user.phone}</p>
                                    <p><strong>Địa chỉ:</strong> ${user.address}</p>
                                    <p><strong>Ảnh đại diện:</strong><br>
                                        ${user.profile_image ? 
                                            `<img src="../uploads/avatars/${user.profile_image}" class="img-thumbnail mt-2" style="max-width: 150px;">` : 
                                            'Chưa có ảnh đại diện'}
                                    </p>
                                    <p><strong>Vai trò:</strong> ${user.id_role == 1 ? 'Admin' : 'User'}</p>
                                    <p><strong>Trạng thái:</strong> ${user.status == 1 ? 
                                        '<span class="badge bg-success">Hoạt động</span>' : 
                                        '<span class="badge bg-danger">Đã khóa</span>'}</p>
                                </div>
                            `;
                            $('#viewUserContent').html(content);
                            $('#viewUserModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin người dùng!');
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

        // Sửa thông tin người dùng
        function editUser(userId) {
            $.ajax({
                url: 'ajax/get_user.php',
                type: 'GET',
                data: { id: userId },
                success: function(response) {
                    console.log('Get User Response:', response);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const user = data.user;
                            $('#edit_account_id').val(user.account_id);
                            $('#edit_username').val(user.username);
                            $('#edit_email').val(user.email);
                            $('#edit_phone').val(user.phone);
                            $('#edit_address').val(user.address);
                            $('#edit_role').val(user.id_role);
                            
                            // Hiển thị ảnh đại diện hiện tại
                            if (user.profile_image) {
                                $('#current_profile_image').html(`
                                    <img src="../uploads/avatars/${user.profile_image}" 
                                         alt="Current Avatar" 
                                         class="img-thumbnail mt-2">
                                    <p class="text-muted mt-1">Ảnh đại diện hiện tại</p>
                                `);
                            } else {
                                $('#current_profile_image').html(`
                                    <img src="../assets/images/default-avatar.png" 
                                         alt="Default Avatar" 
                                         class="img-thumbnail mt-2">
                                    <p class="text-muted mt-1">Chưa có ảnh đại diện</p>
                                `);
                            }
                            
                            $('#editUserModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin người dùng!');
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

        // Khóa/mở khóa người dùng
        function toggleUserStatus(userId, newStatus) {
            if (confirm('Bạn có chắc chắn muốn thay đổi trạng thái người dùng này?')) {
                $.ajax({
                    url: 'ajax/toggle_user_status.php',
                    type: 'POST',
                    data: { 
                        id: userId,
                        status: newStatus
                    },
                    success: function(response) {
                        console.log('Toggle User Status Response:', response);
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

        // Xóa người dùng
        function deleteUser(userId) {
            if (confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác!')) {
                $.ajax({
                    url: 'ajax/delete_user.php',
                    type: 'POST',
                    data: { id: userId },
                    success: function(response) {
                        console.log('Delete User Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Xóa người dùng thành công!');
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
    </script>
</body>
</html> 