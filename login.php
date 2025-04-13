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

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                username: $('#username').val(),
                password: $('#password').val()
            };
            
            $.ajax({
                url: 'ajax/login.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            Toast.error(data.message || 'Tài khoản hoặc mật khẩu không chính xác!');
                        }
                    } catch (error) {
                        Toast.error('Tài khoản hoặc mật khẩu không chính xác!');
                    }
                },
                error: function() {
                    Toast.error('Có lỗi xảy ra khi đăng nhập!');
                }
            });
        });
    });
    </script>
</body>
</html>