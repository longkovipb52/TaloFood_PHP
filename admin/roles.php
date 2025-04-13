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
    // Lấy danh sách vai trò
    $stmt = $conn->query("SELECT * FROM role ORDER BY role_id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $roles = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Phân Quyền - TaloFood</title>
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
                        <h5 class="card-title mb-0">Quản Lý Phân Quyền</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                            <i class="fas fa-plus"></i> Thêm Vai Trò Mới
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="rolesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên vai trò</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?php echo $role['role_id']; ?></td>
                                                <td><?php echo htmlspecialchars($role['rolename']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editRole('<?php echo $role['role_id']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($role['role_id'] > 2): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteRole('<?php echo $role['role_id']; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
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

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Vai Trò Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addRoleForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên vai trò</label>
                            <input type="text" class="form-control" name="role_name" required>
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

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh Sửa Vai Trò</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRoleForm" method="post">
                    <input type="hidden" id="edit_id_role" name="id_role">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_role_name" class="form-label">Tên vai trò</label>
                            <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
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
            $('#rolesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'desc']] // Sắp xếp theo cột ID giảm dần
            });

            // Xử lý thêm vai trò
            $('#addRoleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                console.log('Add Role Form Data:', formData);

                $.ajax({
                    url: 'ajax/add_role.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log('Add Role Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Thêm vai trò thành công!');
                                $('#addRoleModal').modal('hide');
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

            // Xử lý sửa vai trò
            $('#editRoleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = {
                    id_role: $('#edit_id_role').val(),
                    role_name: $('#edit_role_name').val()
                };
                console.log('Edit Role Form Data:', formData);

                $.ajax({
                    url: 'ajax/edit_role.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log('Edit Role Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success(data.message || 'Cập nhật vai trò thành công!');
                                $('#editRoleModal').modal('hide');
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

        // Sửa vai trò
        function editRole(roleId) {
            $.ajax({
                url: 'ajax/get_role.php',
                type: 'GET',
                data: { id: roleId },
                success: function(response) {
                    console.log('Get Role Response:', response);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const role = data.role;
                            $('#edit_id_role').val(role.role_id);
                            $('#edit_role_name').val(role.rolename);
                            $('#editRoleModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin vai trò!');
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

        // Xóa vai trò
        function deleteRole(roleId) {
            if (confirm('Bạn có chắc chắn muốn xóa vai trò này?')) {
                $.ajax({
                    url: 'ajax/delete_role.php',
                    type: 'POST',
                    data: { id: roleId },
                    success: function(response) {
                        console.log('Delete Role Response:', response);
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Toast.success('Xóa vai trò thành công!');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Toast.error(data.message || 'Không thể xóa vai trò!');
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