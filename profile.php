<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    $_SESSION['toast'] = [
        'message' => 'Phiên đăng nhập không hợp lệ',
        'type' => 'error'
    ];
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$database = new Database();
$conn = $database->getConnection();

try {
    $stmt = $conn->prepare("SELECT username, email, phone, address, profile_image FROM account WHERE account_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result === false) {
        $_SESSION['toast'] = [
            'message' => 'Không tìm thấy thông tin người dùng',
            'type' => 'error'
        ];
        header("Location: index.php");
        exit();
    }

    $user = [
        'username' => $result['username'] ?? '',
        'email' => $result['email'] ?? '',
        'phone' => $result['phone'] ?? '',
        'address' => $result['address'] ?? '',
        'profile_image' => $result['profile_image'] ?? ''
    ];

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['toast'] = [
        'message' => 'Có lỗi xảy ra khi lấy thông tin người dùng',
        'type' => 'error'
    ];
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 12rem auto 3rem;
            padding: 2rem;
            background: var(--black);
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.2);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
            color: #fff;
        }

        .profile-header h2 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
            color: var(--main-color);
        }

        .profile-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 3rem;
        }

        .profile-sidebar {
            text-align: center;
        }

        .profile-image-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
            position: relative;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--main-color);
        }

        .image-upload-label {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--main-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-label:hover {
            background: var(--black);
            transform: scale(1.1);
        }

        .image-upload-label i {
            color: #fff;
            font-size: 1.6rem;
        }

        #profile-image-upload {
            display: none;
        }

        .profile-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .profile-tab {
            padding: 1rem 2rem;
            font-size: 1.6rem;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .profile-tab.active {
            color: var(--main-color);
            border-bottom-color: var(--main-color);
        }

        .profile-tab:hover {
            color: var(--main-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            font-size: 1.4rem;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            background: rgba(255,255,255,0.05);
            color: #fff;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 0 2px rgba(211, 173, 127, 0.2);
        }

        .btn-save {
            background: var(--main-color);
            color: #fff;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #c19b6c;
            transform: translateY(-2px);
        }

        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #fff;
        }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-image-container {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h2>Thông tin cá nhân</h2>
        </div>

        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <img src="<?php echo $user['profile_image'] ? 'uploads/avatars/' . htmlspecialchars($user['profile_image']) : 'image/avatar.png'; ?>" 
                         alt="Profile Image" 
                         class="profile-image" 
                         id="preview-image">
                    <label for="profile-image-upload" class="image-upload-label">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="profile-image-upload" accept="image/*">
                </div>
            </div>

            <div class="profile-main">
                <div class="profile-tabs">
                    <button class="profile-tab active" data-tab="info">Thông tin cơ bản</button>
                    <button class="profile-tab" data-tab="password">Đổi mật khẩu</button>
                </div>

                <!-- Tab thông tin cơ bản -->
                <div class="tab-content active" id="info-tab">
                    <form id="profile-form">
                        <div class="form-group">
                            <label for="username">Tên đăng nhập</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn-save">Lưu thay đổi</button>
                    </form>
                </div>

                <!-- Tab đổi mật khẩu -->
                <div class="tab-content" id="password-tab">
                    <form id="password-form">
                        <div class="form-group">
                            <label for="current-password">Mật khẩu hiện tại</label>
                            <div class="password-field">
                                <input type="password" id="current-password" name="current-password" required>
                                <i class="fas fa-eye-slash toggle-password"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new-password">Mật khẩu mới</label>
                            <div class="password-field">
                                <input type="password" id="new-password" name="new-password" required>
                                <i class="fas fa-eye-slash toggle-password"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm-password">Xác nhận mật khẩu mới</label>
                            <div class="password-field">
                                <input type="password" id="confirm-password" name="confirm-password" required>
                                <i class="fas fa-eye-slash toggle-password"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Đổi mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        $(document).ready(function() {
            // Xử lý chuyển tab
            $('.profile-tab').click(function() {
                $('.profile-tab').removeClass('active');
                $(this).addClass('active');
                
                const tabId = $(this).data('tab');
                $('.tab-content').removeClass('active');
                $(`#${tabId}-tab`).addClass('active');
            });

            // Xử lý preview ảnh đại diện và upload
            $('#profile-image-upload').change(function() {
                const file = this.files[0];
                if (file) {
                    // Kiểm tra kích thước file (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Toast.error('Kích thước file không được vượt quá 2MB');
                        return;
                    }
                    
                    // Kiểm tra loại file
                    const fileType = file.type;
                    if (!['image/jpeg', 'image/png', 'image/gif'].includes(fileType)) {
                        Toast.error('Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc GIF');
                        return;
                    }
                    
                    // Hiển thị ảnh xem trước
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview-image').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                    
                    
                    // Upload ảnh
                    const formData = new FormData();
                    formData.append('profile_image', file);
                    
                    $.ajax({
                        url: 'ajax/update_profile_image.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                // Cập nhật ảnh đại diện trong thanh header
                                const headerProfileImage = $('.user-profile img, .dropdown-header .user-avatar img');
                                if (headerProfileImage.length > 0) {
                                    headerProfileImage.attr('src', data.image_url);
                                }
                                Toast.success('Cập nhật ảnh đại diện thành công');
                            } else {
                                Toast.error(data.message || 'Có lỗi xảy ra khi cập nhật ảnh');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', xhr.responseText);
                            Toast.error('Có lỗi khi kết nối đến server: ' + error);
                        }
                    });
                }
            });

            // Xử lý cập nhật thông tin cá nhân
            $('#profile-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'ajax/update_profile.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Toast.success('Cập nhật thông tin thành công');
                        } else {
                            Toast.error(data.message || 'Có lỗi xảy ra');
                        }
                    },
                    error: function() {
                        Toast.error('Có lỗi kết nối đến server');
                    }
                });
            });

            // Xử lý đổi mật khẩu
            $('#password-form').on('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = $('#current-password').val();
                const newPassword = $('#new-password').val();
                const confirmPassword = $('#confirm-password').val();
                
                if (newPassword.length < 6) {
                    Toast.error('Mật khẩu mới phải có ít nhất 6 ký tự');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    Toast.error('Mật khẩu xác nhận không khớp');
                    return;
                }
                
                // Kiểm tra mật khẩu mới không được trùng với mật khẩu hiện tại
                if (newPassword === currentPassword) {
                    Toast.error('Mật khẩu mới không được trùng với mật khẩu hiện tại');
                    return;
                }
                
                $.ajax({
                    url: 'ajax/change_password.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Toast.success('Đổi mật khẩu thành công');
                            $('#password-form')[0].reset();
                        } else {
                            Toast.error(data.message || 'Có lỗi xảy ra');
                        }
                    },
                    error: function(xhr) {
                        console.error('Ajax error:', xhr.responseText);
                        Toast.error('Có lỗi kết nối đến server');
                    }
                });
            });

            // Xử lý ẩn/hiện mật khẩu
            $('.toggle-password').click(function() {
                const input = $(this).siblings('input');
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