<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/log_reg.css">
    <style>
        /* Style cho nút ẩn/hiện mật khẩu */
        .input-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }
        .input-field input[type="password"] {
            padding-right: 45px;
        }

        /* CSS cho modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }

        .close-modal {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: var(--primary-color);
        }

        .alert-message {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
        }

        #unlockReason {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .unlock-submit-btn {
            width: 100%;
            margin-top: 15px;
        }

        /* Thêm button để hiển thị khi tài khoản bị khóa */
        .unlock-request-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            width: 100%;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .unlock-request-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="./image/logo.jpg" alt="TaloFood Logo">
        </div>
        <header>Đăng Nhập TaloFood</header>
        
        <form id="loginForm" method="POST" action="">
            <div class="field">
                <div class="input-field">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
            </div>

            <div class="field">
                <div class="input-field">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                    <i class="fas fa-eye-slash toggle-password" data-target="password"></i>
                </div>
            </div>

            <div class="form-link" style="text-align: right; margin: 10px 0;">
                <a href="forgot_password.php">Quên mật khẩu?</a>
            </div>

            <button type="submit">Đăng Nhập</button>
        </form>

        <div class="form-link">
            <span>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></span>
        </div>
    </div>

    <!-- Modal Yêu Cầu Mở Khóa -->
    <div class="modal" id="unlockRequestModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Yêu cầu mở khóa tài khoản</h3>
            <p class="alert-message">Tài khoản của bạn đã bị khóa do đăng nhập sai nhiều lần. Vui lòng gửi yêu cầu mở khóa đến quản trị viên.</p>
            
            <form id="unlockRequestForm">
                <input type="hidden" id="unlockUsername" name="username">
                
                <div class="field">
                    <div class="input-field">
                        <label for="unlockEmail">Email liên hệ</label>
                        <input type="email" id="unlockEmail" name="email" placeholder="Nhập email để nhận phản hồi" required>
                    </div>
                </div>
                
                <div class="field">
                    <div class="input-field">
                        <label for="unlockReason">Lý do yêu cầu mở khóa</label>
                        <textarea id="unlockReason" name="reason" rows="3" placeholder="Giải thích lý do tài khoản bị khóa và tại sao bạn muốn mở khóa"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="unlock-submit-btn">Gửi yêu cầu mở khóa</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
    <?php
    if (isset($_SESSION['toast'])) {
        if ($_SESSION['toast']['type'] === 'success') {
            echo "Toast.success('" . $_SESSION['toast']['message'] . "');";
        } else {
            echo "Toast.error('" . $_SESSION['toast']['message'] . "');";
        }
        unset($_SESSION['toast']);
    }
    ?>

    $(document).ready(function() {
        // Xử lý ẩn/hiện mật khẩu
        $('.toggle-password').click(function() {
            const targetId = $(this).data('target');
            const input = $(`#${targetId}`);
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                $(this).removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                input.attr('type', 'password');
                $(this).removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });
    });
    </script>
    <script src="assets/js/login.js"></script>
</body>
</html>