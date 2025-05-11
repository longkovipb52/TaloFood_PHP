<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra order_id
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("SELECT b.*, a.username, a.phone, a.address 
                         FROM bill b 
                         JOIN account a ON b.id_account = a.account_id 
                         WHERE b.bill_id = ? AND b.id_account = ?");
    $stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Không tìm thấy đơn hàng");
    }

    // Lấy chi tiết đơn hàng
    $stmt = $db->prepare("SELECT bi.*, f.food_name, f.image 
                         FROM bill_info bi 
                         JOIN food f ON bi.id_food = f.food_id 
                         WHERE bi.id_bill = ?");
    $stmt->execute([$_GET['order_id']]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/thank_you.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="thank-you-container">
        <div class="thank-you-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Cảm ơn bạn đã đặt hàng!</h1>
            <p>Đơn hàng của bạn đã được xác nhận và đang được xử lý.</p>

            <div class="order-details">
                <h2>Thông tin đơn hàng #<?php echo $order['bill_id']; ?></h2>
                
                <div class="order-info">
                    <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y', strtotime($order['ngaydat'])); ?></p>
                    <p><strong>Ngày giao dự kiến:</strong> <?php echo date('d/m/Y', strtotime($order['ngaygiao'])); ?></p>
                    <p><strong>Trạng thái:</strong> <?php echo $order['status']; ?></p>
                    <p><strong>Phương thức thanh toán:</strong> <?php echo $order['payment_method']; ?></p>
                </div>

                <div class="delivery-info">
                    <h3>Thông tin giao hàng</h3>
                    <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                    <p><strong>Người đặt:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                </div>

                <div class="order-items">
                    <h3>Chi tiết đơn hàng</h3>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <img src="uploads/foods/<?php echo $item['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($item['food_name']); ?>">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['food_name']); ?></h4>
                                <p>Số lượng: <?php echo $item['count']; ?></p>
                                <p>Giá: <?php echo number_format($item['price']); ?> VND</p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="order-total">
                        <span>Tổng tiền:</span>
                        <span><?php echo number_format($order['total_amount']); ?> VND</span>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
                <a href="order_history.php" class="btn btn-secondary">Xem lịch sử đơn hàng</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
