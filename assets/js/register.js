document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    registerForm.addEventListener('submit', function(e) {
        // Ngăn form submit mặc định để kiểm tra
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const address = document.getElementById('address').value.trim();
        
        let isValid = true;
        
        // Xóa tất cả thông báo lỗi cũ
        document.querySelectorAll('.error-message').forEach(error => error.remove());
        document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
        
        // Kiểm tra các trường
        if (username === '') {
            showError('username', 'Vui lòng nhập tên đăng nhập');
            isValid = false;
        }
        
        if (email === '') {
            showError('email', 'Vui lòng nhập email');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showError('email', 'Email không hợp lệ');
            isValid = false;
        }
        
        if (phone === '') {
            showError('phone', 'Vui lòng nhập số điện thoại');
            isValid = false;
        } else if (!isValidPhone(phone)) {
            showError('phone', 'Số điện thoại không hợp lệ');
            isValid = false;
        }
        
        if (password === '') {
            showError('password', 'Vui lòng nhập mật khẩu');
            isValid = false;
        }
        
        if (confirmPassword === '') {
            showError('confirmPassword', 'Vui lòng nhập lại mật khẩu');
            isValid = false;
        }
        
        if (password !== confirmPassword) {
            showError('confirmPassword', 'Mật khẩu xác nhận không khớp');
            isValid = false;
        }
        
        if (address === '') {
            showError('address', 'Vui lòng nhập địa chỉ');
            isValid = false;
        }
        
        if (isValid) {
            // If all validations pass, submit the form
            registerForm.submit();
        }
    });

    function showError(field, message) {
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        const currentError = document.querySelector(`.${field}-error`);
        if (currentError) {
            currentError.textContent = message;
        } else {
            const fieldElement = document.getElementById(field);
            const errorContainer = document.createElement('div');
            errorContainer.className = 'error';
            errorContainer.appendChild(errorElement);
            fieldElement.parentNode.appendChild(errorContainer);
        }
        fieldElement.classList.add('error');
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        const phoneRegex = /^[0-9]{10}$/;
        return phoneRegex.test(phone);
    }
}); 