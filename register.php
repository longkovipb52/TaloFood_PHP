<?php
session_start();
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra xác thực email
    if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true) {
        $_SESSION['toast'] = [
            'message' => 'Vui lòng xác thực email trước khi đăng ký',
            'type' => 'error'
        ];
        header("Location: register.php");
        exit();
    }

    // Lấy dữ liệu từ form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $address = trim($_POST['address']);
    $profile_image = 'user.png';

    // Xử lý upload hình ảnh
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/avatars/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Kiểm tra định dạng file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['toast'] = [
                'message' => 'Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG hoặc GIF',
                'type' => 'error'
            ];
        } 
        // Kiểm tra kích thước file (2MB)
        elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
            $_SESSION['toast'] = [
                'message' => 'Kích thước file không được vượt quá 2MB',
                'type' => 'error'
            ];
        } 
        else {
            // Tạo tên file mới
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $target_dir . $file_name;

            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image = $file_name;
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Không thể tải lên ảnh đại diện!',
                    'type' => 'error'
                ];
            }
        }
    }

    // Kiểm tra mật khẩu xác nhận
    if ($password !== $confirm_password) {
        $_SESSION['toast'] = [
            'message' => 'Mật khẩu xác nhận không khớp!',
            'type' => 'error'
        ];
    }
    // Kiểm tra độ dài mật khẩu
    else if (strlen($password) < 6) {
        $_SESSION['toast'] = [
            'message' => 'Mật khẩu phải có ít nhất 6 ký tự!',
            'type' => 'error'
        ];
    }
    else {
        try {
            // Kiểm tra username đã tồn tại chưa
            $stmt = $conn->prepare("SELECT account_id FROM Account WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['toast'] = [
                    'message' => 'Tên đăng nhập đã tồn tại!',
                    'type' => 'error'
                ];
            }
            else {
                // Mã hóa mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Thêm tài khoản mới
                $query = "INSERT INTO Account (username, password, email, phone, id_role, address, status, login_attempts, profile_image) 
                         VALUES (:username, :password, :email, :phone, 2, :address, 1, 0, :profile_image)";
                
                $stmt = $conn->prepare($query);
                
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":phone", $phone);
                $stmt->bindParam(":address", $address);
                $stmt->bindParam(":profile_image", $profile_image);
                
                if($stmt->execute()) {
                    // Xóa session xác thực email
                    unset($_SESSION['email_verified']);
                    unset($_SESSION['register_otp']);
                    unset($_SESSION['register_email']);
                    unset($_SESSION['otp_expiry']);

                    $_SESSION['toast'] = [
                        'message' => 'Đăng ký thành công! Vui lòng đăng nhập.',
                        'type' => 'success'
                    ];
                    header("Location: login.php");
                    exit();
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Có lỗi xảy ra, vui lòng thử lại!',
                        'type' => 'error'
                    ];
                }
            }
        } catch(PDOException $e) {
            $_SESSION['toast'] = [
                'message' => 'Lỗi: ' . $e->getMessage(),
                'type' => 'error'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/log_reg.css">
    <style>
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .form-full-width {
            grid-column: 1 / -1;
        }

        .field {
            margin-bottom: 1.5rem;
        }

        .input-field {
            position: relative;
        }

        .input-field label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
            color: #333;
        }

        .input-field input {
            width: 100%;
            padding: 0 15px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            height: 55px;
        }

        .input-field input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.1);
        }

        /* Style cho nút ẩn/hiện mật khẩu */
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

        .image-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 auto 2rem;
            width: 100%;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            background: #fff;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-preview:hover img {
            transform: scale(1.05);
        }

        .image-upload input[type="file"] {
            display: none;
        }

        .image-upload label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            background: var(--primary-color);
            color: #fff;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.4rem;
            border: 2px solid var(--primary-color);
        }

        .image-upload label i {
            margin-right: 8px;
        }

        .image-upload label:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .upload-text {
            font-size: 1.2rem;
            color: var(--text-color);
            margin-top: 0.5rem;
            text-align: center;
        }

        button[type="submit"] {
            height: 55px;
            width: 100%;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            background-color: var(--primary-color);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(227, 180, 72, 0.3);
        }

        .form-link {
            text-align: center;
            margin-top: 2rem;
            font-size: 1.4rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }
        }

        /* Step Indicator Styles */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e1e1e1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            position: relative;
            font-weight: 600;
            font-size: 1.6rem;
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

        /* OTP Input Styles */
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
            font-size: 1.4rem;
        }

        .resend-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: underline;
            display: none;
            font-size: 1.4rem;
            margin-left: 10px;
        }

        .resend-btn.show {
            display: inline;
        }

        .resend-btn:hover {
            color: var(--primary-dark);
        }

        /* Success Step Styles */
        .success-container {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        .success-container h2 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .success-container p {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.4rem;
        }

        .success-container button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .success-container button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .button-group button {
            flex: 1;
        }

        .back-btn {
            background-color: #6c757d !important;
        }

        .back-btn:hover {
            background-color: #5a6268 !important;
        }

        .step-container {
            display: none;
        }

        .step-container.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="./image/logo.jpg" alt="TaloFood Logo">
        </div>
        <header>Đăng Ký Tài Khoản TaloFood</header>

        <div class="step-indicator">
            <div class="step active" data-step="1">1</div>
            <div class="step" data-step="2">2</div>
            <div class="step" data-step="3">3</div>
        </div>

        <!-- Step 1: Registration Form -->
        <div class="step-container active" id="step1">
            <form id="registerForm">
                <div class="form-grid">
                    <div class="field">
                        <div class="input-field">
                            <label for="username">Tên đăng nhập</label>
                            <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                        </div>
                    </div>

                    <div class="field">
                        <div class="input-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Nhập email" required>
                        </div>
                    </div>

                    <div class="field">
                        <div class="input-field">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" required>
                        </div>
                    </div>

                    <div class="field">
                        <div class="input-field">
                            <label for="address">Địa chỉ</label>
                            <input type="text" id="address" name="address" placeholder="Nhập địa chỉ" required>
                        </div>
                    </div>

                    <div class="field">
                        <div class="input-field">
                            <label for="password">Mật khẩu</label>
                            <input type="password" id="password" name="password" placeholder="Tạo mật khẩu" required>
                            <i class="fas fa-eye-slash toggle-password" data-target="password"></i>
                        </div>
                    </div>

                    <div class="field">
                        <div class="input-field">
                            <label for="confirmPassword">Xác nhận mật khẩu</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Xác nhận mật khẩu" required>
                            <i class="fas fa-eye-slash toggle-password" data-target="confirmPassword"></i>
                        </div>
                    </div>
                </div>
                <button type="submit">Tiếp tục</button>
            </form>
        </div>

        <!-- Step 2: Profile Image -->
        <div class="step-container" id="step2">
            <form id="imageForm">
                <div class="image-upload">
                    <div class="image-preview">
                        <img src="image/avatar.png" alt="Profile Image" id="preview-image">
                    </div>
                    <input type="file" name="profile_image" id="profile-image" accept="image/*">
                    <label for="profile-image">
                        <i class="fas fa-camera"></i>
                        Chọn ảnh đại diện
                    </label>
                    <div class="upload-text">Cho phép JPG, PNG hoặc GIF. Tối đa 2MB</div>
                </div>
                <div class="button-group">
                    <button type="button" class="back-btn" onclick="prevStep(2)">Quay lại</button>
                    <button type="submit">Tiếp tục</button>
                </div>
            </form>
        </div>

        <!-- Step 3: OTP Verification -->
        <div class="step-container" id="step3">
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
                    Mã xác thực sẽ hết hạn sau: <span id="countdown">05:00</span>
                    <button type="button" class="resend-btn" id="resendBtn">Gửi lại mã</button>
                </div>
                <div class="button-group">
                    <button type="button" class="back-btn" onclick="prevStep(3)">Quay lại</button>
                    <button type="submit">Xác nhận</button>
                </div>
            </form>
        </div>

        <div class="form-link">
            <span>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
    $(document).ready(function() {
        let formData = new FormData();
        let timer;

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

        // Preview image before upload
        $('#profile-image').change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview-image').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            }
        });

        // Form Submissions
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate password match
            if ($('#password').val() !== $('#confirmPassword').val()) {
                Toast.error('Mật khẩu xác nhận không khớp!');
                return;
            }

            // Validate password length
            if ($('#password').val().length < 6) {
                Toast.error('Mật khẩu phải có ít nhất 6 ký tự!');
                return;
            }

            // Store form data
            formData = new FormData(this);
            
            // Move to next step
            nextStep(1);
        });

        $('#imageForm').on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#profile-image')[0];
            if (fileInput.files.length > 0) {
                formData.append('profile_image', fileInput.files[0]);
            }

            // Send OTP
            $.ajax({
                url: 'ajax/send_otp.php',
                type: 'POST',
                dataType: 'json',
                data: { 
                    email: $('#email').val(),
                    action: 'register'
                },
                success: function(response) {
                    if (response.success) {
                        nextStep(2);
                        timer = startTimer(300, document.querySelector('#countdown'));
                        Toast.success(response.message);
                    } else {
                        Toast.error(response.message || 'Có lỗi xảy ra!');
                    }
                },
                error: function(xhr, status, error) {
                    Toast.error('Có lỗi kết nối đến server!');
                    console.error('AJAX Error:', status, error);
                }
            });
        });

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
                    otp: otp 
                },
                success: function(response) {
                    if (response.success) {
                        // Submit registration data
                        $.ajax({
                            url: 'ajax/register.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(registerResponse) {
                                try {
                                    const data = JSON.parse(registerResponse);
                                    if (data.success) {
                                        Toast.success(data.message);
                                        setTimeout(() => {
                                            window.location.href = 'login.php';
                                        }, 1500);
                                    } else {
                                        Toast.error(data.message);
                                    }
                                } catch (error) {
                                    Toast.error('Có lỗi xảy ra!');
                                }
                            },
                            error: function() {
                                Toast.error('Có lỗi khi kết nối với server!');
                            }
                        });
                    } else {
                        Toast.error(response.message || 'Mã OTP không hợp lệ!');
                    }
                },
                error: function(xhr, status, error) {
                    Toast.error('Có lỗi kết nối đến server!');
                    console.error('AJAX Error:', status, error);
                }
            });
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
            return countdown;
        }

        // Resend OTP
        $('#resendBtn').on('click', function() {
            $(this).removeClass('show');
            clearInterval(timer);
            timer = startTimer(300, document.querySelector('#countdown'));
            
            $.ajax({
                url: 'ajax/send_otp.php',
                type: 'POST',
                dataType: 'json',
                data: { 
                    email: $('#email').val(),
                    action: 'register'
                },
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
                }
            });
        });
    });

    function nextStep(currentStep) {
        $('.step[data-step="' + currentStep + '"]').removeClass('active').addClass('completed');
        $('.step[data-step="' + (currentStep + 1) + '"]').addClass('active');
        $('#step' + currentStep).removeClass('active');
        $('#step' + (currentStep + 1)).addClass('active');
    }

    function prevStep(currentStep) {
        $('.step[data-step="' + currentStep + '"]').removeClass('active');
        $('.step[data-step="' + (currentStep - 1) + '"]').removeClass('completed').addClass('active');
        $('#step' + currentStep).removeClass('active');
        $('#step' + (currentStep - 1)).addClass('active');
    }
    </script>
</body>
</html>