<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/log_reg.css">
    <style>
        .step-container {
            display: none;
        }
        .step-container.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e1e1e1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #666;
            position: relative;
        }
        .step.active {
            background: var(--primary-color);
            color: white;
        }
        .step.completed {
            background: var(--success-color);
            color: white;
        }
        .step::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: #e1e1e1;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
        }
        .step:last-child::after {
            display: none;
        }
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .otp-inputs input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
        }
        .otp-inputs input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .timer {
            text-align: center;
            margin: 20px 0;
            color: var(--text-color);
        }
        .resend-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: underline;
            display: none;
        }
        .resend-btn.show {
            display: inline;
        }
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
        <header>Khôi Phục Mật Khẩu</header>

        <div class="step-indicator">
            <div class="step active" data-step="1">1</div>
            <div class="step" data-step="2">2</div>
            <div class="step" data-step="3">3</div>
        </div>

        <!-- Step 1: Email Input -->
        <div class="step-container active" id="step1">
            <form id="emailForm">
                <div class="field">
                    <div class="input-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                    </div>
                </div>
                <button type="submit">Gửi Mã OTP</button>
            </form>
        </div>

        <!-- Step 2: OTP Verification -->
        <div class="step-container" id="step2">
            <form id="otpForm">
                <div class="field">
                    <div class="otp-inputs">
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                        <input type="text" maxlength="1" pattern="[0-9]" required>
                    </div>
                </div>
                <div class="timer">
                    Mã OTP sẽ hết hạn sau: <span id="countdown">05:00</span>
                    <button type="button" class="resend-btn" id="resendBtn">Gửi lại mã</button>
                </div>
                <button type="submit">Xác Nhận</button>
            </form>
        </div>

        <!-- Step 3: New Password -->
        <div class="step-container" id="step3">
            <form id="passwordForm">
                <div class="field">
                    <div class="input-field">
                        <label for="newPassword">Mật khẩu mới</label>
                        <input type="password" id="newPassword" name="newPassword" placeholder="Nhập mật khẩu mới" required>
                        <i class="fas fa-eye-slash toggle-password" data-target="newPassword"></i>
                    </div>
                </div>
                <div class="field">
                    <div class="input-field">
                        <label for="confirmPassword">Xác nhận mật khẩu</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Xác nhận mật khẩu mới" required>
                        <i class="fas fa-eye-slash toggle-password" data-target="confirmPassword"></i>
                    </div>
                </div>
                <button type="submit">Đổi Mật Khẩu</button>
            </form>
        </div>

        <div class="form-link">
            <span>Quay lại trang <a href="login.php">Đăng nhập</a></span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        $(document).ready(function() {
            // OTP Input Handling
            $('.otp-inputs input').on('input', function() {
                if (this.value.length === 1) {
                    $(this).next('input').focus();
                }
            });

            $('.otp-inputs input').on('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value) {
                    $(this).prev('input').focus();
                }
            });

            // Timer Function
            function startTimer(duration, display) {
                let timer = duration, minutes, seconds;
                let countdown = setInterval(function () {
                    minutes = parseInt(timer / 60, 10);
                    seconds = parseInt(timer % 60, 10);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    display.textContent = minutes + ":" + seconds;

                    if (--timer < 0) {
                        clearInterval(countdown);
                        $('#resendBtn').addClass('show');
                    }
                }, 1000);
            }

            // Form Submissions
            $('#emailForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'ajax/send_otp.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        email: $('#email').val(),
                        action: 'forgot'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.step[data-step="1"]').removeClass('active').addClass('completed');
                            $('.step[data-step="2"]').addClass('active');
                            $('#step1').removeClass('active');
                            $('#step2').addClass('active');
                            
                            // Start Timer
                            startTimer(300, document.querySelector('#countdown'));
                            
                            Toast.success(response.message);
                        } else {
                            Toast.error(response.message || 'Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr, status, error) {
                        Toast.error('Có lỗi kết nối đến server!');
                        console.error('AJAX Error:', status, error);
                        console.log('Response:', xhr.responseText);
                    }
                });
            });

            $('#otpForm').on('submit', function(e) {
                e.preventDefault();
                let otp = '';
                $('.otp-inputs input').each(function() {
                    otp += $(this).val();
                });

                $.ajax({
                    url: 'ajax/verify_otp.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        email: $('#email').val(),
                        otp: otp,
                        action: 'forgot'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.step[data-step="2"]').removeClass('active').addClass('completed');
                            $('.step[data-step="3"]').addClass('active');
                            $('#step2').removeClass('active');
                            $('#step3').addClass('active');
                            Toast.success(response.message);
                        } else {
                            Toast.error(response.message || 'Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr, status, error) {
                        Toast.error('Có lỗi kết nối đến server!');
                        console.error('AJAX Error:', status, error);
                        console.log('Response:', xhr.responseText);
                    }
                });
            });

            $('#passwordForm').on('submit', function(e) {
                e.preventDefault();
                if ($('#newPassword').val() !== $('#confirmPassword').val()) {
                    Toast.error('Mật khẩu xác nhận không khớp!');
                    return;
                }

                if ($('#newPassword').val().length < 6) {
                    Toast.error('Mật khẩu phải có ít nhất 6 ký tự!');
                    return;
                }

                $.ajax({
                    url: 'ajax/reset_password.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        email: $('#email').val(),
                        password: $('#newPassword').val(),
                        action: 'forgot'
                    },
                    success: function(response) {
                        if (response.success) {
                            Toast.success(response.message);
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 1500);
                        } else {
                            Toast.error(response.message || 'Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr, status, error) {
                        Toast.error('Có lỗi kết nối đến server!');
                        console.error('AJAX Error:', status, error);
                        console.log('Response:', xhr.responseText);
                    }
                });
            });

            // Resend OTP
            $('#resendBtn').on('click', function() {
                $(this).removeClass('show');
                startTimer(300, document.querySelector('#countdown'));
                
                $.ajax({
                    url: 'ajax/send_otp.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { email: $('#email').val() },
                    success: function(response) {
                        if (response.success) {
                            Toast.success(response.message);
                        } else {
                            Toast.error(response.message || 'Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr, status, error) {
                        Toast.error('Có lỗi kết nối đến server!');
                        console.error('AJAX Error:', status, error);
                        console.log('Response:', xhr.responseText);
                    }
                });
            });

            // Thêm xử lý ẩn/hiện mật khẩu
            $('.toggle-password').click(function() {
                const targetId = $(this).data('target');
                const input = $(`#${targetId}`);
                
                // Chuyển đổi kiểu input
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
</body>
</html>
