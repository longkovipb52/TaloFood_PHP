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
    // Thống kê tổng quan
    $stats = [
        'users' => 0,
        'products' => 0,
        'orders' => 0,
        'revenue' => 0
    ];

    // Đếm số người dùng
    $stmt = $conn->query("SELECT COUNT(*) as total FROM account WHERE id_role = 2");
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Đếm số món ăn
    $stmt = $conn->query("SELECT COUNT(*) as total FROM food");
    $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Đếm số đơn hàng
    $stmt = $conn->query("SELECT COUNT(*) as total FROM bill");
    $stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Tính tổng doanh thu
    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM bill WHERE status = 'Đã giao'");
    $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Lấy dữ liệu biểu đồ doanh thu theo tháng
    $revenueData = array_fill(1, 12, 0); // Khởi tạo mảng 12 tháng với giá trị 0
    $stmt = $conn->query("
        SELECT 
            MONTH(ngaydat) as month,
            SUM(total_amount) as revenue,
            COUNT(*) as order_count
        FROM bill 
        WHERE YEAR(ngaydat) = YEAR(CURRENT_DATE)
        AND status = 'Đã giao'
        GROUP BY MONTH(ngaydat)
        ORDER BY month
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $revenueData[$row['month']] = (float)$row['revenue'];
    }

    // Lấy dữ liệu so sánh với tháng trước
    $currentMonth = date('n');
    $stmt = $conn->query("
        SELECT 
            SUM(CASE WHEN MONTH(ngaydat) = $currentMonth THEN total_amount ELSE 0 END) as current_month_revenue,
            SUM(CASE WHEN MONTH(ngaydat) = $currentMonth - 1 THEN total_amount ELSE 0 END) as last_month_revenue,
            COUNT(CASE WHEN MONTH(ngaydat) = $currentMonth THEN 1 END) as current_month_orders,
            COUNT(CASE WHEN MONTH(ngaydat) = $currentMonth - 1 THEN 1 END) as last_month_orders
        FROM bill 
        WHERE YEAR(ngaydat) = YEAR(CURRENT_DATE)
        AND status = 'Đã giao'
        AND MONTH(ngaydat) IN ($currentMonth, $currentMonth - 1)
    ");
    $comparison = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tính phần trăm tăng/giảm
    $revenueGrowth = 0;
    $orderGrowth = 0;

    if ($comparison['last_month_revenue'] > 0) {
        $revenueGrowth = round((($comparison['current_month_revenue'] - $comparison['last_month_revenue']) / $comparison['last_month_revenue']) * 100);
    }

    if ($comparison['last_month_orders'] > 0) {
        $orderGrowth = round((($comparison['current_month_orders'] - $comparison['last_month_orders']) / $comparison['last_month_orders']) * 100);
    }

    // Lấy thống kê trạng thái đơn hàng
    $orderStats = [];
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count
        FROM bill
        GROUP BY status
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orderStats[$row['status']] = $row['count'];
    }

    // Lấy danh sách đơn hàng gần đây
    $recentOrders = [];
    $stmt = $conn->query("
        SELECT b.*, a.username, GROUP_CONCAT(f.food_name SEPARATOR ', ') as food_names
        FROM bill b
        JOIN account a ON b.id_account = a.account_id
        JOIN bill_info bi ON b.bill_id = bi.id_bill
        JOIN food f ON bi.id_food = f.food_id
        GROUP BY b.bill_id
        ORDER BY b.ngaydat DESC
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['toast'] = [
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage(),
        'type' => 'error'
    ];
}

// Lấy thông tin admin
$stmt = $conn->prepare("SELECT * FROM account WHERE account_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị - TaloFood</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="active">
            <div class="sidebar-header">
                <img src="../image/logo.jpg" alt="TaloFood Logo" class="logo">
                <h3>TaloFood Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Trang Chủ</span>
                    </a>
                </li>
                <li>
                    <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-users"></i>
                        <span>Quản Lý Người Dùng</span>
                    </a>
                    <ul class="collapse list-unstyled" id="userSubmenu">
                        <li>
                            <a href="users.php">Danh Sách Người Dùng</a>
                        </li>
                        <li>
                            <a href="roles.php">Phân Quyền</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#productSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-hamburger"></i>
                        <span>Quản Lý Món Ăn</span>
                    </a>
                    <ul class="collapse list-unstyled" id="productSubmenu">
                        <li>
                            <a href="foods.php">Danh Sách Món Ăn</a>
                        </li>
                        <li>
                            <a href="categories.php">Danh Mục</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Quản Lý Đơn Hàng</span>
                    </a>
                </li>
                <li>
                    <a href="contacts.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Quản Lý Liên hệ</span>
                    </a>
                </li>
                <li>
                    <a href="blogs.php">
                        <i class="fas fa-blog"></i>
                        <span>Quản Lý Blog</span>
                    </a>
                </li>
                <li>
                    <a href="reviews.php">
                        <i class="fas fa-star"></i>
                        <span>Quản Lý Đánh Giá</span>
                    </a>
                </li>
                <li>
                    <a href="statistics.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Thống Kê & Báo Cáo</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="dropdownMenuButton" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span><?php echo htmlspecialchars($admin['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Hồ Sơ</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Cài Đặt</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng Xuất</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card bg-primary text-white">
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-info">
                                <h5>Người Dùng</h5>
                                <h3><?php echo $stats['users']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-warning text-white">
                            <div class="stats-icon">
                                <i class="fas fa-hamburger"></i>
                            </div>
                            <div class="stats-info">
                                <h5>Món Ăn</h5>
                                <h3><?php echo $stats['products']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-success text-white">
                            <div class="stats-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stats-info">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5>Đơn Hàng</h5>
                                    <select class="form-select form-select-sm" id="orderPeriod" style="width: auto;">
                                        <option value="all">Tất cả</option>
                                        <option value="current" selected>Tháng hiện tại</option>
                                        <option value="previous">Tháng trước</option>
                                    </select>
                                </div>
                                <h3 id="orderAmount"><?php
                                $currentMonth = date('m');
                                $currentYear = date('Y');
                                
                                // Get all time orders
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bill");
                                $stmt->execute();
                                $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                
                                // Get current month orders
                                $stmt = $conn->prepare("SELECT COUNT(*) as total 
                                        FROM bill 
                                        WHERE MONTH(ngaydat) = :month 
                                        AND YEAR(ngaydat) = :year");
                                $stmt->execute(['month' => $currentMonth, 'year' => $currentYear]);
                                $currentOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                                // Get previous month orders
                                $stmt = $conn->prepare("SELECT COUNT(*) as total 
                                        FROM bill 
                                        WHERE MONTH(ngaydat) = :month 
                                        AND YEAR(ngaydat) = :year");
                                $stmt->execute(['month' => ($currentMonth - 1), 'year' => $currentYear]);
                                $previousOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                                // Calculate percentage change
                                $orderPercentChange = 0;
                                if ($previousOrders > 0) {
                                    $orderPercentChange = (($currentOrders - $previousOrders) / $previousOrders) * 100;
                                }

                                echo $currentOrders;
                                ?>
                                </h3>
                                <p id="orderChange">
                                    <i class="fas <?php echo $orderPercentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                                    <?php echo abs(round($orderPercentChange)); ?>% so với tháng trước
                                </p>
                                <script>
                                    const orderData = {
                                        all: '<?php echo $totalOrders; ?>',
                                        current: '<?php echo $currentOrders; ?>',
                                        previous: '<?php echo $previousOrders; ?>'
                                    };
                                    
                                    document.getElementById('orderPeriod').addEventListener('change', function() {
                                        const amount = document.getElementById('orderAmount');
                                        const change = document.getElementById('orderChange');
                                        
                                        amount.textContent = orderData[this.value];
                                        
                                        // Hiển thị hoặc ẩn phần trăm thay đổi
                                        if (this.value === 'all') {
                                            change.style.display = 'none';
                                        } else {
                                            change.style.display = 'block';
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-info text-white">
                            <div class="stats-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stats-info">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5>Doanh Thu</h5>
                                    <select class="form-select form-select-sm" id="revenuePeriod" style="width: auto;">
                                        <option value="all">Tất cả</option>
                                        <option value="current" selected>Tháng hiện tại</option>
                                        <option value="previous">Tháng trước</option>
                                    </select>
                                </div>
                                <h3 id="revenueAmount"><?php
                                $currentMonth = date('m');
                                $currentYear = date('Y');
                                
                                // Get all time revenue
                                $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue 
                                        FROM bill 
                                        WHERE status = 'Đã giao'");
                                $stmt->execute();
                                $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
                                
                                // Get current month revenue
                                $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue 
                                        FROM bill 
                                        WHERE MONTH(ngaydat) = :month 
                                        AND YEAR(ngaydat) = :year 
                                        AND status = 'Đã giao'");
                                $stmt->execute(['month' => $currentMonth, 'year' => $currentYear]);
                                $currentRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

                                // Get previous month revenue
                                $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue 
                                        FROM bill 
                                        WHERE MONTH(ngaydat) = :month 
                                        AND YEAR(ngaydat) = :year 
                                        AND status = 'Đã giao'");
                                $stmt->execute(['month' => ($currentMonth - 1), 'year' => $currentYear]);
                                $previousRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

                                // Calculate percentage change
                                $percentChange = 0;
                                if ($previousRevenue > 0) {
                                    $percentChange = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
                                }

                                // Store values for JavaScript
                                echo number_format($currentRevenue, 0, ',', '.') . 'đ';
                                ?>
                                </h3>
                                <p id="revenueChange">
                                    <i class="fas <?php echo $percentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                                    <?php echo abs(round($percentChange)); ?>% so với tháng trước
                                </p>
                                <script>
                                    const revenueData = {
                                        all: '<?php echo number_format($totalRevenue, 0, ',', '.'); ?>đ',
                                        current: '<?php echo number_format($currentRevenue, 0, ',', '.'); ?>đ',
                                        previous: '<?php echo number_format($previousRevenue, 0, ',', '.'); ?>đ'
                                    };
                                    
                                    document.getElementById('revenuePeriod').addEventListener('change', function() {
                                        const amount = document.getElementById('revenueAmount');
                                        const change = document.getElementById('revenueChange');
                                        
                                        amount.textContent = revenueData[this.value];
                                        
                                        // Hiển thị hoặc ẩn phần trăm thay đổi
                                        if (this.value === 'all') {
                                            change.style.display = 'none';
                                        } else {
                                            change.style.display = 'block';
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Thống Kê Doanh Thu Năm <?php echo date('Y'); ?></h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Phân Bố Đơn Hàng</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ordersPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Đơn Hàng Gần Đây</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="recentOrdersTable">
                                <thead>
                                    <tr>
                                        <th>Mã Đơn</th>
                                        <th>Khách Hàng</th>
                                        <th>Món Ăn</th>
                                        <th>Ngày Đặt</th>
                                        <th>Tổng Tiền</th>
                                        <th>Trạng Thái</th>
                                        <th>Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['bill_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo htmlspecialchars($order['food_names']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($order['status']) {
                                                case 'Đã giao':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'Đang giao':
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case 'Đã xác nhận':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case 'Chờ xác nhận':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'Đã hủy':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="orders.php?id=<?php echo $order['bill_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Khởi tạo DataTable
        $(document).ready(function() {
            $('#recentOrdersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[3, 'desc']], // Sắp xếp theo ngày đặt giảm dần
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]]
            });

            // Xử lý toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Biểu đồ doanh thu
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                                     'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: monthNames.slice(1),
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: <?php echo json_encode(array_values($revenueData)); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Doanh Thu Theo Tháng'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Biểu đồ phân bố đơn hàng
            const orderCtx = document.getElementById('ordersPieChart').getContext('2d');
            new Chart(orderCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($orderStats)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($orderStats)); ?>,
                        backgroundColor: [
                            'rgb(40, 167, 69)',  // Đã giao - success
                            'rgb(0, 123, 255)',  // Đang giao - primary
                            'rgb(23, 162, 184)', // Đã xác nhận - info
                            'rgb(255, 193, 7)',  // Chờ xác nhận - warning
                            'rgb(220, 53, 69)'   // Đã hủy - danger
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>