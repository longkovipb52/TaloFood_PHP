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
    // Lấy danh sách đơn hàng kèm thông tin khách hàng
    $stmt = $conn->query("
        SELECT b.*, a.username, a.phone, a.email
        FROM bill b
        JOIN account a ON b.id_account = a.account_id
        ORDER BY b.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng - TaloFood</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
        .order-id {
            font-weight: bold;
            color: #0d6efd;
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
                        <h5 class="card-title mb-0">Danh Sách Đơn Hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Mã Đơn</th>
                                        <th>Khách Hàng</th>
                                        <th>Ngày Đặt</th>
                                        <th>Ngày Giao</th>
                                        <th>Tổng Tiền</th>
                                        <th>Trạng Thái</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="order-id">#<?php echo $order['bill_id']; ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($order['username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['phone']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['ngaydat'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($order['ngaygiao'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                        <td>
                                            <?php
                                            $statusClass = match($order['status']) {
                                                'Chờ xác nhận' => 'bg-warning',
                                                'Đã xác nhận' => 'bg-info',
                                                'Đang giao' => 'bg-primary',
                                                'Đã giao' => 'bg-success',
                                                'Đã hủy' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['bill_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $order['bill_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($order['status'] !== 'Đã giao' && $order['status'] !== 'Đã hủy'): ?>
                                            <button class="btn btn-sm btn-danger" onclick="cancelOrder(<?php echo $order['bill_id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
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

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Đơn Hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetails">
                        <!-- Nội dung chi tiết đơn hàng sẽ được load bằng AJAX -->
                    </div>
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
                    <h5 class="modal-title">Cập Nhật Trạng Thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" id="order_id" name="order_id">
                        <div class="mb-3">
                            <label class="form-label">Trạng thái mới</label>
                            <select class="form-select" name="status" id="new_status" required>
                                <option value="Chờ xác nhận">Chờ xác nhận</option>
                                <option value="Đã xác nhận">Đã xác nhận</option>
                                <option value="Đang giao">Đang giao</option>
                                <option value="Đã giao">Đã giao</option>
                                <option value="Đã hủy">Đã hủy</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Toastify -->
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
            $('#ordersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[2, 'desc']], // Sắp xếp theo ngày đặt giảm dần
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>', // Layout của DataTable
                pagingType: "full_numbers" // Kiểu phân trang đầy đủ
            });
        });
        // Xem chi tiết đơn hàng
        function viewOrder(orderId) {
            // Hiển thị modal
            $('#viewOrderModal').modal('show');
            
            // Load chi tiết đơn hàng
            fetch(`ajax/get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#orderDetails').html(data.html);
                    } else {
                        Swal.fire('Lỗi!', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Lỗi!', 'Không thể tải chi tiết đơn hàng', 'error');
                });
        }

        // Cập nhật trạng thái
        function updateStatus(orderId, currentStatus) {
            $('#order_id').val(orderId);
            $('#new_status').val(currentStatus);
            $('#updateStatusModal').modal('show');
        }

        // Xử lý form cập nhật trạng thái
        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Lỗi!', 'Không thể cập nhật trạng thái', 'error');
            });
        });

        // Hủy đơn hàng
        function cancelOrder(orderId) {
            Swal.fire({
                title: 'Xác nhận hủy?',
                text: "Bạn có chắc chắn muốn hủy đơn hàng này?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Hủy đơn',
                cancelButtonText: 'Không'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('status', 'Đã hủy');

                    fetch('ajax/update_order_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã hủy!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Lỗi!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Lỗi!', 'Không thể hủy đơn hàng', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html> 