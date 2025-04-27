document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    
    loginForm.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        let isValid = true;
        
        // Kiểm tra username
        if (username === '') {
            showError('username', 'Vui lòng nhập tên đăng nhập');
            isValid = false;
        } else {
            removeError('username');
        }
        
        // Kiểm tra password
        if (password === '') {
            showError('password', 'Vui lòng nhập mật khẩu');
            isValid = false;
        } else if (password.length < 6) {
            showError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
            isValid = false;
        } else {
            removeError('password');
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Hàm hiển thị lỗi
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        let errorDiv = field.parentElement.querySelector('.error-message');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            field.parentElement.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        field.classList.add('error');
    }
    
    // Hàm xóa lỗi
    function removeError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorDiv = field.parentElement.querySelector('.error-message');
        
        if (errorDiv) {
            errorDiv.remove();
        }
        field.classList.remove('error');
    }
});

// Thêm vào cuối file
function showPopupMessage(message, type = 'success') {
    // Tạo element cho popup
    const popup = document.createElement('div');
    popup.className = `popup-message popup-${type}`;
    popup.textContent = message;
    
    // Thêm vào body
    document.body.appendChild(popup);
    
    // Hiển thị popup
    setTimeout(() => {
        popup.style.display = 'block';
    }, 100);
    
    // Ẩn và xóa popup sau 5 giây
    setTimeout(() => {
        popup.style.animation = 'fadeOut 0.5s ease-out';
        setTimeout(() => {
            popup.remove();
        }, 500);
    }, 5000);
}

// Kiểm tra nếu có thông báo thành công từ URL
const urlParams = new URLSearchParams(window.location.search);
const successMessage = urlParams.get('success_message');
if (successMessage) {
    showPopupMessage(decodeURIComponent(successMessage));
}

// Xử lý hiển thị form yêu cầu mở khóa khi tài khoản bị khóa
function showUnlockRequestButton(username) {
    // Tạo nút yêu cầu mở khóa nếu chưa có
    if (!$('#requestUnlockBtn').length) {
        const unlockBtn = $('<button id="requestUnlockBtn" class="unlock-request-btn">Yêu cầu mở khóa tài khoản</button>');
        $('#loginForm').after(unlockBtn);
        
        // Gắn sự kiện click cho nút
        unlockBtn.click(function() {
            $('#unlockUsername').val(username);
            $('#unlockRequestModal').css('display', 'block');
        });
    }
}

// Xử lý đóng modal
$(document).on('click', '.close-modal', function() {
    $('#unlockRequestModal').css('display', 'none');
});

// Khi click bên ngoài modal thì đóng modal
$(window).click(function(event) {
    if (event.target == document.getElementById('unlockRequestModal')) {
        $('#unlockRequestModal').css('display', 'none');
    }
});

// Xử lý form yêu cầu mở khóa
$('#unlockRequestForm').submit(function(e) {
    e.preventDefault();
    
    const formData = {
        username: $('#unlockUsername').val(),
        email: $('#unlockEmail').val(),
        reason: $('#unlockReason').val() || 'Không có lý do cụ thể'
    };
    
    // Hiển thị loading
    const submitBtn = $('.unlock-submit-btn');
    const originalText = submitBtn.text();
    submitBtn.text('Đang gửi...').prop('disabled', true);
    
    $.ajax({
        url: 'ajax/request_unlock.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    Toast.success(data.message);
                    $('#unlockRequestModal').css('display', 'none');
                    
                    // Thay đổi nút yêu cầu mở khóa thành thông báo đã gửi
                    $('#requestUnlockBtn').text('Đã gửi yêu cầu mở khóa! Vui lòng kiểm tra email của bạn').prop('disabled', true)
                        .css('background-color', '#28a745');
                } else {
                    Toast.error(data.message);
                }
            } catch (error) {
                Toast.error('Có lỗi xảy ra khi gửi yêu cầu!');
            }
            submitBtn.text(originalText).prop('disabled', false);
        },
        error: function() {
            Toast.error('Không thể kết nối đến máy chủ!');
            submitBtn.text(originalText).prop('disabled', false);
        }
    });
});

// Sửa đổi phần xử lý đăng nhập để hiển thị nút yêu cầu mở khóa khi tài khoản bị khóa
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
                    
                    // Nếu tài khoản bị khóa, hiển thị nút yêu cầu mở khóa
                    if (data.locked) {
                        showUnlockRequestButton(data.username);
                    }
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