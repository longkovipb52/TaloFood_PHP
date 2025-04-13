<?php
require_once '../config/database.php';
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

    // Lấy năm hiện tại
    $currentYear = date('Y');
    $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;

    // Lấy doanh thu theo tháng trong năm
    $stmt = $conn->prepare("
        SELECT MONTH(ngaydat) as month,
               COUNT(*) as total_orders,
               SUM(CASE WHEN status = 'Đã giao' THEN total_amount ELSE 0 END) as revenue,
               COUNT(CASE WHEN status = 'Đã hủy' THEN 1 END) as cancelled_orders,
               COUNT(CASE WHEN status = 'Đã giao' THEN 1 END) as completed_orders
        FROM bill
        WHERE YEAR(ngaydat) = :year
        GROUP BY MONTH(ngaydat)
        ORDER BY month
    ");
    $stmt->execute(['year' => $selectedYear]);
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy top 5 món ăn bán chạy
    $stmt = $conn->prepare("
        SELECT f.food_name, 
               COUNT(*) as order_count,
               SUM(bi.count) as total_quantity,
               SUM(bi.count * bi.price) as total_revenue
        FROM bill_info bi
        JOIN food f ON bi.id_food = f.food_id
        JOIN bill b ON bi.id_bill = b.bill_id
        WHERE b.status = 'Đã giao'
        AND YEAR(b.ngaydat) = :year
        GROUP BY f.food_id, f.food_name
        ORDER BY total_quantity DESC
        LIMIT 5
    ");
    $stmt->execute(['year' => $selectedYear]);
    $topFoods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy thống kê theo danh mục
    $stmt = $conn->prepare("
        SELECT fc.foodcategory_name,
               COUNT(DISTINCT b.bill_id) as order_count,
               SUM(bi.count) as total_quantity,
               SUM(bi.count * bi.price) as total_revenue
        FROM bill_info bi
        JOIN food f ON bi.id_food = f.food_id
        JOIN food_category fc ON f.id_category = fc.foodcategory_id
        JOIN bill b ON bi.id_bill = b.bill_id
        WHERE b.status = 'Đã giao'
        AND YEAR(b.ngaydat) = :year
        GROUP BY fc.foodcategory_id, fc.foodcategory_name
        ORDER BY total_revenue DESC
    ");
    $stmt->execute(['year' => $selectedYear]);
    $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê & Báo Cáo - TaloFood</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <style>
        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fc;
        }
        .table td {
            transition: background-color 0.2s ease;
        }
        .table tr:hover td {
            background-color: #f8f9fc;
        }
        .revenue-stats {
            font-size: 1.1rem;
        }
        .stats-value {
            font-weight: bold;
            color: #4e73df;
            transition: color 0.3s ease;
        }
        .stats-value:hover {
            color: #2e59d9;
        }
        .table-hover tbody tr {
            transition: transform 0.2s ease;
        }
        .table-hover tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
            transition: transform 0.3s ease;
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.2);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
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
                    <h1 class="h3 mb-0 text-gray-800" data-aos="fade-right">Thống Kê & Báo Cáo</h1>
                </div>

                <!-- Tổng quan thống kê -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng đơn hàng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-value">
                                    <?php 
                                    $totalOrders = array_sum(array_column($monthlyStats, 'total_orders')); 
                                    echo number_format($totalOrders);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đơn thành công</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-value">
                                    <?php 
                                    $completedOrders = array_sum(array_column($monthlyStats, 'completed_orders'));
                                    echo number_format($completedOrders);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tổng doanh thu</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-value">
                                    <?php 
                                    $totalRevenue = array_sum(array_column($monthlyStats, 'revenue'));
                                    echo number_format($totalRevenue, 0, ',', '.') . 'đ';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Tỷ lệ thành công</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-value">
                                    <?php 
                                    $successRate = ($totalOrders > 0) ? round(($completedOrders / $totalOrders) * 100, 1) : 0;
                                    echo $successRate . '%';
                                    ?>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $successRate; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Năm thống kê -->
                <div class="card mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Thống kê năm <?php echo $selectedYear; ?></h6>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='statistics.php?year='+this.value">
                            <?php
                            for($year = $currentYear; $year >= 2020; $year--) {
                                $selected = $year == $selectedYear ? 'selected' : '';
                                echo "<option value='$year' $selected>Năm $year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Biểu đồ doanh thu -->
                <div class="card mb-4" data-aos="fade-up">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Biểu đồ doanh thu theo tháng</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top 5 món ăn và Thống kê danh mục -->
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card mb-4" data-aos="fade-right">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Top 5 món ăn bán chạy</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Tên món</th>
                                                <th>Số lượng</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($topFoods as $index => $food): ?>
                                            <tr data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                                <td><?php echo $food['food_name']; ?></td>
                                                <td class="stats-value"><?php echo $food['total_quantity']; ?></td>
                                                <td class="stats-value"><?php echo number_format($food['total_revenue'], 0, ',', '.'); ?>đ</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card mb-4" data-aos="fade-left">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Thống kê theo danh mục</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Danh mục</th>
                                                <th>Số đơn</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($categoryStats as $index => $category): ?>
                                            <tr data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                                <td><?php echo $category['foodcategory_name']; ?></td>
                                                <td class="stats-value"><?php echo $category['order_count']; ?></td>
                                                <td class="stats-value"><?php echo number_format($category['total_revenue'], 0, ',', '.'); ?>đ</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê chi tiết -->
                <div class="card mb-4" data-aos="fade-up">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Thống kê chi tiết theo tháng</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="monthlyStatsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Tháng</th>
                                        <th>Tổng đơn</th>
                                        <th>Đơn thành công</th>
                                        <th>Đơn hủy</th>
                                        <th>Tỷ lệ thành công</th>
                                        <th>Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($monthlyStats as $stat): ?>
                                    <tr>
                                        <td>Tháng <?php echo $stat['month']; ?></td>
                                        <td class="stats-value"><?php echo $stat['total_orders']; ?></td>
                                        <td class="stats-value"><?php echo $stat['completed_orders']; ?></td>
                                        <td class="stats-value"><?php echo $stat['cancelled_orders']; ?></td>
                                        <td class="stats-value"><?php echo round(($stat['completed_orders'] / $stat['total_orders']) * 100, 1); ?>%</td>
                                        <td class="stats-value"><?php echo number_format($stat['revenue'], 0, ',', '.'); ?>đ</td>
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
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../assets/js/toast.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo AOS
            AOS.init({
                duration: 800,
                once: true
            });

            // Xử lý toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            // Initialize DataTable với animation
            $('#monthlyStatsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'asc']],
                pageLength: 12,
                searching: false,
                info: false,
                paging: false,
                drawCallback: function() {
                    $('.table tbody tr').each(function(index) {
                        $(this).css('animation-delay', (index * 0.1) + 's');
                        $(this).addClass('animate-fade-in');
                    });
                }
            });
        });

        // Biểu đồ doanh thu với animation
        const monthlyData = <?php echo json_encode($monthlyStats); ?>;
        const months = monthlyData.map(item => 'Tháng ' + item.month);
        const revenues = monthlyData.map(item => item.revenue);
        const orderCounts = monthlyData.map(item => item.total_orders);

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Số đơn hàng',
                    data: orderCounts,
                    type: 'line',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.type === 'line') {
                                    return `Số đơn hàng: ${context.raw}`;
                                }
                                return `Doanh thu: ${context.raw.toLocaleString('vi-VN')}đ`;
                            }
                        },
                        animation: {
                            duration: 400
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>
</html>

<?php
} catch(PDOException $e) {
    echo "Lỗi kết nối: " . $e->getMessage();
}
?>
