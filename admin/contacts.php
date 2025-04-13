<?php
require_once '../config/database.php';
require_once 'includes/contact_status.php';
session_start();

// Kiểm tra đăng nhập và role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

try {
    // Tạo kết nối database
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy danh sách liên hệ kèm thông tin người dùng
    $stmt = $conn->query("
        SELECT c.*, a.username, a.email, a.phone 
        FROM contact c 
        JOIN account a ON c.id_account = a.account_id 
        ORDER BY c.contact_id DESC
    ");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $contacts = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Liên Hệ - TaloFood</title>
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
        .message-content {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .contact-status {
            width: 100px;
            text-align: center;
        }
        .table-hover tbody tr {
            transition: all 0.3s ease;
        }
        .table-hover tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }
        .action-buttons .btn {
            margin: 0 2px;
            transition: all 0.3s ease;
        }
        .action-buttons .btn:hover {
            transform: scale(1.1);
        }
        .badge {
            transition: all 0.3s ease;
        }
        .badge:hover {
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Quản Lý Liên Hệ</h1>
                </div>

                <div class="card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Danh sách liên hệ</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="contactsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người dùng</th>
                                        <th>Email</th>
                                        <th>Số điện thoại</th>
                                        <th>Nội dung</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                    <tr>
                                        <td><?php echo $contact['contact_id']; ?></td>
                                        <td><?php echo htmlspecialchars($contact['username']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                                        <td class="message-content"><?php echo htmlspecialchars($contact['Message']); ?></td>
                                        <td class="contact-status">
                                            <?php echo get_contact_status_badge($contact['status']); ?>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="viewContact(<?php echo $contact['contact_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $contact['contact_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteContact(<?php echo $contact['contact_id']; ?>)">
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

    <!-- View Contact Modal -->
    <div class="modal fade" id="viewContactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết liên hệ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewContactContent">
                    <!-- Nội dung sẽ được thêm bằng JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật trạng thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <input type="hidden" id="contact_id" name="contact_id">
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" id="status" name="status" required>
                                <?php echo get_contact_status_options(); ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            $('#contactsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]]
            });
        });

        // Hàm tạo badge trạng thái
        function get_contact_status_badge(status) {
            let badgeClass = 'bg-warning';
            let statusText = 'Chưa xử lý';
            let icon = 'fa-clock';

            if (status === 'Đã xử lý') {
                badgeClass = 'bg-success';
                statusText = 'Đã xử lý';
                icon = 'fa-check-circle';
            } else if (status === 'Đang xử lý') {
                badgeClass = 'bg-info';
                statusText = 'Đang xử lý';
                icon = 'fa-spinner fa-spin';
            } else if (status === 'Từ chối') {
                badgeClass = 'bg-danger';
                statusText = 'Từ chối';
                icon = 'fa-times-circle';
            }

            return `<span class="badge ${badgeClass}">
                        <i class="fas ${icon}"></i> ${statusText}
                    </span>`;
        }

        // Xem chi tiết liên hệ
        function viewContact(contactId) {
            $.ajax({
                url: 'ajax/get_contact.php',
                type: 'GET',
                data: { id: contactId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const contact = data.contact;
                            let content = `
                                <div class="contact-info">
                                    <p><strong>ID:</strong> ${contact.contact_id}</p>
                                    <p><strong>Người dùng:</strong> ${contact.username}</p>
                                    <p><strong>Email:</strong> ${contact.email}</p>
                                    <p><strong>Số điện thoại:</strong> ${contact.phone}</p>
                                    <p><strong>Nội dung:</strong></p>
                                    <div class="p-3 bg-light rounded">
                                        ${contact.Message}
                                    </div>
                                    <p class="mt-3"><strong>Trạng thái:</strong> 
                                        ${get_contact_status_badge(contact.status)}
                                    </p>
                                </div>
                            `;
                            $('#viewContactContent').html(content);
                            $('#viewContactModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin liên hệ!');
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

        // Cập nhật trạng thái
        function updateStatus(contactId) {
            $.ajax({
                url: 'ajax/get_contact.php',
                type: 'GET',
                data: { id: contactId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const contact = data.contact;
                            $('#contact_id').val(contact.contact_id);
                            $('#status').val(contact.status);
                            $('#updateStatusModal').modal('show');
                        } else {
                            Toast.error(data.message || 'Không thể lấy thông tin liên hệ!');
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

        // Gửi cập nhật trạng thái
        function submitStatusUpdate() {
            const formData = new FormData($('#updateStatusForm')[0]);
            
            $.ajax({
                url: 'ajax/update_contact_status.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Toast.success('Cập nhật trạng thái thành công!');
                            $('#updateStatusModal').modal('hide');
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

        // Xóa liên hệ
        function deleteContact(contactId) {
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Bạn có chắc chắn muốn xóa liên hệ này không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax/delete_contact.php',
                        type: 'POST',
                        data: { id: contactId },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success) {
                                    Toast.success('Đã xóa liên hệ thành công!');
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