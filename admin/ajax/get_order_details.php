<?php
session_start();
require_once '../../config/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Kiểm tra và lấy ID đơn hàng
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("
        SELECT b.*, a.username, a.phone, a.email
        FROM bill b
        JOIN account a ON b.id_account = a.account_id
        WHERE b.bill_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }

    // Lấy chi tiết các món ăn trong đơn hàng
    $stmt = $conn->prepare("
        SELECT bi.*, f.food_name, f.image
        FROM bill_info bi
        JOIN food f ON bi.id_food = f.food_id
        WHERE bi.id_bill = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tạo HTML cho chi tiết đơn hàng
    $html = '
    <div class="order-details">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="mb-2">Thông tin khách hàng</h6>
                <p class="mb-1"><strong>Tên:</strong> ' . htmlspecialchars($order['username']) . '</p>
                <p class="mb-1"><strong>SĐT:</strong> ' . htmlspecialchars($order['phone']) . '</p>
                <p class="mb-1"><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
                <p class="mb-1"><strong>Địa chỉ:</strong> ' . htmlspecialchars($order['address']) . '</p>
            </div>
            <div class="col-md-6">
                <h6 class="mb-2">Thông tin đơn hàng</h6>
                <p class="mb-1"><strong>Mã đơn:</strong> #' . $order['bill_id'] . '</p>
                <p class="mb-1"><strong>Ngày đặt:</strong> ' . date('d/m/Y', strtotime($order['ngaydat'])) . '</p>
                <p class="mb-1"><strong>Ngày giao:</strong> ' . date('d/m/Y', strtotime($order['ngaygiao'])) . '</p>
                <p class="mb-1"><strong>Trạng thái:</strong> <span class="badge ' . getStatusClass($order['status']) . '">' . htmlspecialchars($order['status']) . '</span></p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Món ăn</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($items as $item) {
        $html .= '
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="' . getItemImage($item['image']) . '" class="me-2" style="width: 40px; height: 40px; object-fit: cover;" alt="' . htmlspecialchars($item['food_name']) . '">
                                ' . htmlspecialchars($item['food_name']) . '
                            </div>
                        </td>
                        <td>' . $item['count'] . '</td>
                        <td>' . number_format($item['price'], 0, ',', '.') . 'đ</td>
                        <td>' . number_format($item['price'] * $item['count'], 0, ',', '.') . 'đ</td>
                    </tr>';
    }

    $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                        <td><strong>' . number_format($order['total_amount'], 0, ',', '.') . 'đ</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-3">
            <p class="mb-1"><strong>Phương thức thanh toán:</strong> ' . htmlspecialchars($order['payment_method']) . '</p>
            <p class="mb-1"><strong>Thời gian tạo:</strong> ' . date('d/m/Y H:i:s', strtotime($order['created_at'])) . '</p>
        </div>
    </div>';

    echo json_encode(['success' => true, 'html' => $html]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết đơn hàng: ' . $e->getMessage()]);
}

function getStatusClass($status) {
    return match($status) {
        'Chờ xác nhận' => 'bg-warning',
        'Đã xác nhận' => 'bg-info',
        'Đang giao' => 'bg-primary',
        'Đã giao' => 'bg-success',
        'Đã hủy' => 'bg-danger',
        default => 'bg-secondary'
    };
}

function getItemImage($image) {
    if ($image && file_exists('../../uploads/foods/' . $image)) {
        return '../uploads/foods/' . $image;
    }
    return '../assets/images/no-image.jpg';
} 