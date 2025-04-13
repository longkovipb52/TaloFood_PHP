document.addEventListener('DOMContentLoaded', function() {
    // Cart button functionality
    let cartItemsContainer = document.querySelector('.cart-items-container');
    let cartBtn = document.querySelector('#cart-btn');
    
    if (cartBtn) {
        cartBtn.onclick = () => {
            cartItemsContainer.classList.toggle('active');
            // Đảm bảo các phần tử khác được ẩn đi
            if (window.closeAllOverlays) {
                window.closeAllOverlays('cart');
            }
            
            // Load cart contents
            loadCartContents();
        }
    }
    
    // Clear cart button
    let clearCartBtn = document.querySelector('.clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }
    
    // Update cart count on page load
    updateCartCount();
});

// Function to load cart contents
function loadCartContents() {
    const cartItems = document.querySelector('.cart-items');
    if (!cartItems) return;
    
    // Show loading state
    cartItems.innerHTML = '<div class="loading-cart"><i class="fas fa-spinner fa-spin"></i><p>Đang tải giỏ hàng...</p></div>';
    
    fetch('ajax/get_cart.php')
        .then(response => response.json())
        .then(data => {
            if (data.empty) {
                cartItems.innerHTML = data.html;
                document.querySelector('.cart-count').textContent = '0';
                
                const totalAmount = document.querySelector('.total-amount');
                if (totalAmount) totalAmount.textContent = '0đ';
                
                // Hide checkout button if cart is empty
                const checkoutBtn = document.querySelector('.checkout-btn');
                if (checkoutBtn) checkoutBtn.style.display = 'none';
            } else {
                cartItems.innerHTML = data.html;
                document.querySelector('.cart-count').textContent = data.count;
                
                const totalAmount = document.querySelector('.total-amount');
                if (totalAmount) totalAmount.textContent = data.total;
                
                // Show checkout button
                const checkoutBtn = document.querySelector('.checkout-btn');
                if (checkoutBtn) checkoutBtn.style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            cartItems.innerHTML = '<div class="cart-error">Đã xảy ra lỗi khi tải giỏ hàng</div>';
        });
}

// Function to update cart count
function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (!cartCount) return;
    
    fetch('ajax/get_cart.php')
        .then(response => response.json())
        .then(data => {
            cartCount.textContent = data.count;
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Function to update cart item quantity
function updateCartItem(itemId, quantity) {
    fetch('ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload cart contents
            loadCartContents();
            
            // Show success toast
            showToast('Giỏ hàng đã được cập nhật', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        showToast('Đã xảy ra lỗi khi cập nhật giỏ hàng', 'error');
    });
}

// Function to remove item from cart
function removeCartItem(itemId) {
    fetch('ajax/remove_cart_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartItem = document.querySelector(`.cart-item[data-id="${itemId}"]`);
            if (cartItem) {
                cartItem.style.height = cartItem.offsetHeight + 'px';
                setTimeout(() => {
                    cartItem.style.height = '0';
                    cartItem.style.opacity = '0';
                    cartItem.style.padding = '0';
                    cartItem.style.margin = '0';
                    cartItem.style.overflow = 'hidden';
                }, 10);
                
                setTimeout(() => {
                    // Reload cart contents
                    loadCartContents();
                }, 300);
            }
            
            showToast('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error removing item from cart:', error);
        showToast('Đã xảy ra lỗi khi xóa sản phẩm', 'error');
    });
}

// Function to clear cart
function clearCart() {
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng không?')) {
        return;
    }
    
    fetch('ajax/clear_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload cart contents
            loadCartContents();
            
            // Show success toast
            showToast('Đã xóa toàn bộ giỏ hàng', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error clearing cart:', error);
        showToast('Đã xảy ra lỗi khi xóa giỏ hàng', 'error');
    });
}

// Function to add item to cart
function addToCart(itemId, quantity = 1) {
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
            
            // Kiểm tra nếu giỏ hàng đang mở thì tải lại nội dung giỏ hàng
            const cartItemsContainer = document.querySelector('.cart-items-container');
            if (cartItemsContainer && cartItemsContainer.classList.contains('active')) {
                loadCartContents();
            }
            
            // Show success message with product info
            const toast = document.createElement('div');
            toast.className = 'toast success';
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="toast-message">
                        <strong>Đã thêm vào giỏ hàng:</strong>
                        <div class="product-info">
                            <img src="uploads/foods/${data.product.image}" alt="${data.product.name}">
                            <div>
                                <div class="product-name">${data.product.name}</div>
                                <div class="product-price">${data.product.price}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="toast-actions">
                    <button class="view-cart-btn" onclick="document.querySelector('#cart-btn').click()">
                        Xem giỏ hàng
                    </button>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 2000);
            
            // Close modal if open
            const modal = document.getElementById('quick-view-modal');
            if (modal && modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        } else {
            // Show error message
            showToast(data.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showToast('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
    });
}

// Show toast message function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    if (type === 'success') {
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="toast-message">
                    <strong>${message}</strong>
                </div>
            </div>
        `;
    } else {
        toast.innerHTML = message;
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Expose these functions globally
window.loadCartContents = loadCartContents;
window.updateCartCount = updateCartCount;
window.updateCartItem = updateCartItem;
window.removeCartItem = removeCartItem;
window.addToCart = addToCart;
window.showToast = showToast;