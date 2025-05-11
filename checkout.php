<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lấy thông tin điểm uy tín
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT reputation_points FROM account WHERE account_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $reputation_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $reputation_points = $reputation_result ? $reputation_result['reputation_points'] : 100;
} catch (Exception $e) {
    $reputation_points = 100; // Giá trị mặc định nếu có lỗi
}

// Xác định loại cảnh báo dựa trên điểm uy tín
$reputation_status = '';
$forced_payment = false;
$account_locked = false;

if ($reputation_points >= 70) {
    $reputation_status = 'normal';
} elseif ($reputation_points >= 40) {
    $reputation_status = 'warning';
} elseif ($reputation_points >= 10) {
    $reputation_status = 'danger';
    $forced_payment = true;
} else {
    $reputation_status = 'locked';
    $account_locked = true;
}

// Nếu tài khoản bị khóa, chuyển hướng
if ($account_locked) {
    $_SESSION['error'] = 'Tài khoản của bạn đã bị khóa tính năng đặt hàng do điểm uy tín quá thấp. Vui lòng liên hệ với nhân viên hỗ trợ.';
    header('Location: index.php');
    exit();
}

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

// Xử lý form thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $db->beginTransaction();

        // Kiểm tra nếu bị buộc thanh toán trước mà chọn COD
        $payment_method = $_POST['payment_method'] ?? 'cod';
        if ($forced_payment && $payment_method === 'cod') {
            throw new Exception("Với điểm uy tín hiện tại, bạn cần thanh toán trước khi đặt hàng.");
        }

        // Bổ sung phần lấy dữ liệu từ form
        $fullname = $_POST['fullname'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;

        // Validate dữ liệu
        if (empty($fullname) || empty($phone) || empty($address)) {
            throw new Exception("Vui lòng điền đầy đủ thông tin");
        }

        // Kiểm tra định dạng số điện thoại
        if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            throw new Exception("Số điện thoại không hợp lệ");
        }

        // Tính ngày giao hàng (3 ngày sau ngày đặt)
        $order_date = date('Y-m-d');
        $delivery_date = date('Y-m-d', strtotime('+0 days'));

        // Thêm đơn hàng vào bảng bill
        $stmt = $db->prepare("INSERT INTO bill (ngaydat, ngaygiao, id_account, status, address, total_amount, payment_method, phone, name) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $order_date,
            $delivery_date,
            $_SESSION['user_id'],
            'Chờ xác nhận',
            $address,
            $total_amount,
            $payment_method,
            $phone,
            $fullname // Thêm tên người nhận
        ]);

        if (!$result) {
            throw new Exception("Có lỗi xảy ra khi tạo đơn hàng");
        }

        $bill_id = $db->lastInsertId();

        // Thêm chi tiết đơn hàng vào bảng bill_info
        foreach ($_SESSION['cart'] as $food_id => $cart_item) {
            // Lấy giá sản phẩm
            $stmt = $db->prepare("SELECT new_price FROM food WHERE food_id = ?");
            $stmt->execute([$food_id]);
            $food = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($food) {
                $quantity = (int)$cart_item['quantity'];
                if ($quantity <= 0) continue;

                // Thêm chi tiết đơn hàng
                $stmt = $db->prepare("INSERT INTO bill_info (id_bill, id_food, id_account, count, price) 
                                      VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $bill_id,
                    $food_id,
                    $_SESSION['user_id'],
                    $quantity,
                    $food['new_price']
                ]);

                if (!$result) {
                    throw new Exception("Có lỗi xảy ra khi thêm chi tiết đơn hàng");
                }
            }
        }

        $db->commit();

        // Xử lý thanh toán
        if ($payment_method === 'paypal') {
            // Cộng điểm cho người dùng khi thanh toán PayPal
            $stmt = $db->prepare("UPDATE account SET reputation_points = LEAST(100, reputation_points + 10) WHERE account_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Chuyển hướng đến PayPal
            header("Location: paypal_redirect.php?bill_id=" . $bill_id);
            exit();
        } else {
            // Xóa giỏ hàng cho COD
            unset($_SESSION['cart']);
            // Chuyển hướng đến trang cảm ơn
            header("Location: thank_you.php?order_id=" . $bill_id);
            exit();
        }

    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header('Location: checkout.php');
        exit();
    }
}

// Lấy thông tin người dùng
try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM account WHERE account_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}

// Tính tổng tiền giỏ hàng
$total_amount = 0;
foreach ($_SESSION['cart'] as $food_id => $cart_item) {
    $stmt = $db->prepare("SELECT new_price FROM food WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($food) {
        $total_amount += $food['new_price'] * $cart_item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body class="checkout-page">
    <?php include 'includes/header.php'; ?>

    <main class="checkout-container">
        <div class="checkout-grid">
            <!-- Form Section -->
            <div class="checkout-form">
                <h1>Thanh toán</h1>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message show">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($reputation_status === 'warning'): ?>
                <div class="reputation-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p><strong>Cảnh báo:</strong> Tài khoản của bạn có điểm uy tín thấp (<?php echo $reputation_points; ?> điểm).</p>
                        <p>Vui lòng chú ý tránh hủy đơn hàng để không bị giảm thêm điểm uy tín.</p>
                    </div>
                </div>
                <?php elseif ($reputation_status === 'danger'): ?>
                <div class="reputation-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <p><strong>Cảnh báo:</strong> Tài khoản của bạn có điểm uy tín rất thấp (<?php echo $reputation_points; ?> điểm).</p>
                        <p>Bạn chỉ có thể đặt hàng khi thanh toán trước.</p>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <h3>Thông tin giao hàng</h3>
                        <div class="form-row">
                            <label for="fullname">Họ và tên người nhận</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            <small class="form-text text-muted">Có thể thay đổi nếu bạn gửi đến người khác</small>
                        </div>
                        <div class="form-row">
                            <label for="phone">Số điện thoại</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="address">Địa chỉ giao hàng</label>
                            <input type="text" id="address" name="address" class="form-control"
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <?php if ($forced_payment): ?>
                    <div class="form-group">
                        <h3>Phương thức thanh toán</h3>
                        <div class="payment-methods">
                            <label class="payment-method disabled">
                                <input type="radio" name="payment_method" value="cod" disabled>
                                <img src="image/cod.png" alt="COD">
                                <span>Thanh toán khi nhận hàng (COD) <i class="fas fa-lock"></i></span>
                                <div class="payment-disabled-msg">Không khả dụng do điểm uy tín thấp</div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal" checked>
                                <img src="image/paypal.png" alt="PayPal">
                                <span>PayPal <i class="fas fa-plus-circle reputation-plus"></i> +10 điểm</span>
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <h3>Phương thức thanh toán</h3>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <img src="image/cod.png" alt="COD">
                                <span>Thanh toán khi nhận hàng (COD)</span>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal">
                                <img src="image/paypal.png" alt="PayPal">
                                <span>PayPal</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                    <button type="submit" class="checkout-btn">Xác nhận đặt hàng</button>
                </form>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary">
                <h3>Đơn hàng của bạn</h3>
                <div class="order-items">
                    <?php foreach ($_SESSION['cart'] as $food_id => $cart_item): ?>
                        <?php
                        $stmt = $db->prepare("SELECT * FROM food WHERE food_id = ?");
                        $stmt->execute([$food_id]);
                        $food = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <div class="order-item">
                            <div class="item-details">
                                <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($food['food_name']); ?>" 
                                     class="item-image">
                                <div class="item-info">
                                    <span class="item-name"><?php echo htmlspecialchars($food['food_name']); ?></span>
                                    <span>x<?php echo $cart_item['quantity']; ?></span>
                                </div>
                            </div>
                            <span class="item-price"><?php echo number_format($food['new_price']); ?> VND</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-total">
                    <div class="total-row">
                        <span>Tạm tính:</span>
                        <span><?php echo number_format($total_amount); ?> VND</span>
                    </div>
                    <div class="total-row final">
                        <span>Tổng cộng:</span>
                        <span><?php echo number_format($total_amount); ?> VND</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div class="loading">
            <div class="loading-spinner"></div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Add loading animation when form is submitted
        document.querySelector('form').addEventListener('submit', function() {
            document.querySelector('.loading').classList.add('active');
        });

        // Add selected class to payment method when selected
        document.querySelectorAll('.payment-method input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-method').forEach(method => {
                    method.classList.remove('selected');
                });
                this.closest('.payment-method').classList.add('selected');
            });
        });
    </script>
</body>
</html>
