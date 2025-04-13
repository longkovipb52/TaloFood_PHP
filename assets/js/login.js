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