<?php
session_start();
require_once 'config/database.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
$loggedIn = isset($_SESSION['user_id']);
$contact_messages = [];

// Nếu người dùng đã đăng nhập, lấy tin nhắn liên hệ của họ
if ($loggedIn) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT contact_id, Message, status FROM contact WHERE id_account = ? ORDER BY contact_id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $contact_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error: " . $e->getMessage());
    }
}

// Xử lý gửi tin nhắn liên hệ
$toast_message = '';
$toast_type = '';

// Kiểm tra thông báo từ redirect
if (isset($_SESSION['toast_message']) && isset($_SESSION['toast_type'])) {
    $toast_message = $_SESSION['toast_message'];
    $toast_type = $_SESSION['toast_type'];
    
    // Xóa thông báo sau khi đã lấy để không hiển thị lại
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    if (!$loggedIn) {
        $_SESSION['toast_message'] = 'Vui lòng đăng nhập để gửi tin nhắn liên hệ!';
        $_SESSION['toast_type'] = 'error';
    } elseif (empty($_POST['message'])) {
        $_SESSION['toast_message'] = 'Vui lòng nhập nội dung tin nhắn!';
        $_SESSION['toast_type'] = 'error';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Insert query - status initialized to 'Chưa xử lý'
            $stmt = $conn->prepare("INSERT INTO contact (Message, id_account, status) VALUES (?, ?, 'Chưa xử lý')");
            $stmt->execute([$_POST['message'], $_SESSION['user_id']]);
            
            $_SESSION['toast_message'] = 'Gửi tin nhắn liên hệ thành công!';
            $_SESSION['toast_type'] = 'success';
            
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['toast_message'] = 'Đã xảy ra lỗi. Vui lòng thử lại sau!';
            $_SESSION['toast_type'] = 'error';
        }
    }
    
    // Redirect sau khi xử lý form để tránh resubmission
    header('Location: contact.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liên Hệ - TaloFood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Thêm CSS cho Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/transitions.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <style>
        :root {
            --main-color: #d3ad7f;
            --black: #13131a;
            --border: .1rem solid rgba(255,255,255,.3);
        }

        .contact-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 10rem auto;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 3rem;
            opacity: 0;
            transform: translateY(-20px);
            animation: fadeInDown 1s forwards 0.3s;
        }

        .contact-header h2 {
            font-size: 3rem;
            color: #fff;
            margin-bottom: 1rem;
        }

        .contact-header p {
            font-size: 1.6rem;
            color: #ccc;
            max-width: 800px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        .contact-info {
            padding: 2rem;
            background: rgba(19, 19, 26, 0.8);
            border-radius: 1rem;
            border: var(--border);
            opacity: 0;
            transform: translateX(-20px);
            animation: fadeInLeft 1s forwards 0.6s;
        }

        .contact-info h3 {
            font-size: 2rem;
            color: var(--main-color);
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--main-color);
            padding-bottom: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .info-item i {
            font-size: 1.8rem;
            color: var(--main-color);
            margin-right: 1.5rem;
            margin-top: 0.3rem;
        }

        .info-item .content {
            flex: 1;
        }

        .info-item h4 {
            font-size: 1.6rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .info-item p {
            font-size: 1.4rem;
            color: #ccc;
            line-height: 1.5;
        }

        .social-links {
            display: flex;
            margin-top: 2rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            background: var(--main-color);
            color: #13131a;
            border-radius: 50%;
            margin-right: 1rem;
            font-size: 1.8rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(211, 173, 127, 0.4);
        }

        .contact-form {
            padding: 2rem;
            background: rgba(19, 19, 26, 0.8);
            border-radius: 1rem;
            border: var(--border);
            opacity: 0;
            transform: translateX(20px);
            animation: fadeInRight 1s forwards 0.9s;
        }

        .contact-form h3 {
            font-size: 2rem;
            color: var(--main-color);
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--main-color);
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem;
            font-size: 1.4rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 0 2px rgba(211, 173, 127, 0.25);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .btn {
            display: inline-block;
            padding: 1.2rem 2.5rem;
            font-size: 1.6rem;
            background: var(--main-color);
            color: #13131a;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #c39d6d;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(211, 173, 127, 0.4);
        }

        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(211, 173, 127, 0.4);
        }

        .btn i {
            margin-right: 0.8rem;
        }

        .contact-map {
            margin-top: 3rem;
            border-radius: 1rem;
            overflow: hidden;
            height: 400px;
            opacity: 0;
            animation: fadeIn 1s forwards 1.2s;
        }

        .contact-map iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .message-history {
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(19, 19, 26, 0.8);
            border-radius: 1rem;
            border: var(--border);
            opacity: 0;
            animation: fadeIn 1s forwards 1.5s;
        }

        .message-history h3 {
            font-size: 2rem;
            color: var(--main-color);
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--main-color);
            padding-bottom: 0.5rem;
        }

        .message-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .message-item {
            padding: 1.5rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            border-left: 3px solid var(--main-color);
        }

        .message-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .message-item .message-text {
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 1rem;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-status {
            font-size: 1.2rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }

        .status-processed {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            font-size: 1.6rem;
            color: #ccc;
        }

        .alert {
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-size: 1.4rem;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.5);
        }

        .food-decoration {
            position: absolute;
            width: 10rem;
            height: 10rem;
            opacity: 0.2;
            pointer-events: none;
            z-index: -1;
        }

        .food-decoration.burger {
            top: 10%;
            right: 5%;
            background: url('image/burger-decoration.png') no-repeat center center/contain;
            animation: floatAnimation 8s ease-in-out infinite;
        }

        .food-decoration.pizza {
            bottom: 15%;
            left: 5%;
            background: url('image/pizza-decoration.png') no-repeat center center/contain;
            animation: floatAnimation 10s ease-in-out infinite 1s;
        }

        .food-decoration.drink {
            top: 40%;
            left: 10%;
            background: url('image/drink-decoration.png') no-repeat center center/contain;
            animation: floatAnimation 7s ease-in-out infinite 0.5s;
        }

        @keyframes floatAnimation {
            0% { transform: translateY(0) rotate(0); }
            50% { transform: translateY(-15px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Thêm styles cho toast notifications */
        .toastify {
            font-size: 1.4rem;
            font-family: Arial, sans-serif;
            padding: 1.2rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .toastify.success {
            background: linear-gradient(135deg, #28a745, #218838);
        }
        
        .toastify.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .toastify.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .toastify.info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="contact-container">
        <!-- Food decoration elements -->
        <div class="food-decoration burger"></div>
        <div class="food-decoration pizza"></div>
        <div class="food-decoration drink"></div>

        <div class="contact-header">
            <h2>Liên Hệ Với Chúng Tôi</h2>
            <p>Chúng tôi luôn lắng nghe và sẵn sàng hỗ trợ bạn. Hãy chia sẻ với chúng tôi những điều bạn quan tâm.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info">
                <h3>Thông Tin Liên Hệ</h3>
                
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="content">
                        <h4>Địa Chỉ</h4>
                        <p>Số 6, đường Trần Văn Ơn, thành phố Thủ Dầu Một, tỉnh Bình Dương</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div class="content">
                        <h4>Điện Thoại</h4>
                        <p>+84 123 456 789</p>
                        <p>+84 987 654 321</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="content">
                        <h4>Email</h4>
                        <p>info@talofood.com</p>
                        <p>support@talofood.com</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div class="content">
                        <h4>Giờ Mở Cửa</h4>
                        <p>Thứ 2 - Thứ 6: 9:00 - 22:00</p>
                        <p>Thứ 7 - Chủ nhật: 10:00 - 23:00</p>
                    </div>
                </div>

                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="contact-form">
                <h3>Gửi Tin Nhắn</h3>
                
                <form method="POST" action="contact.php" id="contactForm">
                    <div class="form-group">
                        <label for="message">Nội dung tin nhắn</label>
                        <textarea name="message" id="message" class="form-control" rows="5" placeholder="Hãy chia sẻ với chúng tôi câu hỏi hoặc phản hồi của bạn..."></textarea>
                    </div>

                    <?php if($loggedIn): ?>
                        <button type="submit" name="submit_contact" class="btn">
                            <i class="fas fa-paper-plane"></i> Gửi Tin Nhắn
                        </button>
                    <?php else: ?>
                        <p style="color: #ff9800; margin-bottom: 1rem; font-size: 1.4rem;">
                            <i class="fas fa-exclamation-circle"></i> Vui lòng đăng nhập để gửi tin nhắn liên hệ!
                        </p>
                        <a href="login.php" class="btn">
                            <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="contact-map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.627583482072!2d106.67144857369895!3d10.991457355193328!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174d11e65beedef%3A0x331844c85c8c91b1!2zMTc5IEh14buzbmggVsSDbiBMxal5LCBQaMO6IEzhu6NpLCBUaOG7pyBE4bqndSBN4buZdCwgQsOsbmggRMawxqFuZyA3MDAwMCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1744359861962!5m2!1svi!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <?php if($loggedIn && count($contact_messages) > 0): ?>
        <div class="message-history">
            <h3>Lịch Sử Tin Nhắn</h3>
            
            <div class="message-list">
                <?php foreach ($contact_messages as $message): ?>
                    <div class="message-item">
                        <div class="message-text"><?php echo htmlspecialchars($message['Message']); ?></div>
                        <div class="message-meta">
                            <span class="message-status <?php echo ($message['status'] === 'Đã xử lý') ? 'status-processed' : 'status-pending'; ?>">
                                <?php echo htmlspecialchars($message['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif($loggedIn && count($contact_messages) === 0): ?>
        <div class="message-history">
            <h3>Lịch Sử Tin Nhắn</h3>
            <div class="empty-message">
                <i class="fas fa-comment-slash" style="font-size: 3rem; color: #555; margin-bottom: 1rem;"></i>
                <p>Bạn chưa có tin nhắn liên hệ nào.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Thêm JS cho Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/transitions.js"></script>
    <script>
        // Animation for message items
        document.addEventListener('DOMContentLoaded', function() {
            // Hiển thị toast notification nếu có
            <?php if(!empty($toast_message)): ?>
                Toast.<?php echo $toast_type; ?>('<?php echo addslashes($toast_message); ?>');
            <?php endif; ?>

            // Form validation và submit bằng fetch API
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    const messageInput = document.getElementById('message');
                    if (!messageInput.value.trim()) {
                        e.preventDefault();
                        Toast.error('Vui lòng nhập nội dung tin nhắn!');
                        messageInput.focus();
                    }
                });
            }

            // Hiệu ứng cho danh sách tin nhắn
            const messageItems = document.querySelectorAll('.message-item');
            
            messageItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 100 * index + 1800); // Staggered animation after page load
            });
        });
    </script>
</body>
</html>