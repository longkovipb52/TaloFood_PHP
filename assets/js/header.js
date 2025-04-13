// Navbar Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Menu button functionality
    let navbar = document.querySelector('.navbar');
    let menuBtn = document.querySelector('#menu-btn');
    
    if (menuBtn) {
        menuBtn.onclick = () => {
            menuBtn.classList.toggle('fa-times');
            navbar.classList.toggle('active');
            if (searchForm) searchForm.classList.remove('active');
            if (cartItemsContainer) cartItemsContainer.classList.remove('active');
            if (profileDropdown) profileDropdown.classList.remove('active');
        }
    }
    
    // Search button functionality
    let searchForm = document.querySelector('.search-form');
    let searchBtn = document.querySelector('#search-btn');
    
    if (searchBtn) {
        searchBtn.onclick = () => {
            searchForm.classList.toggle('active');
            if (navbar) navbar.classList.remove('active');
            if (menuBtn) menuBtn.classList.remove('fa-times');
            if (cartItemsContainer) cartItemsContainer.classList.remove('active');
            if (profileDropdown) profileDropdown.classList.remove('active');
        }
    }
    
    // Cart button functionality
    let cartItemsContainer = document.querySelector('.cart-items-container');
    let cartBtn = document.querySelector('#cart-btn');
    
    if (cartBtn) {
        cartBtn.onclick = () => {
            cartItemsContainer.classList.toggle('active');
        };
    }
    
    // User Profile Dropdown
    const profileBtn = document.querySelector('.user-profile-dropdown');
    const profileDropdown = document.querySelector('.dropdown-menu');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
        
        // Close dropdown when clicking on dropdown items
        const dropdownItems = profileDropdown.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', () => {
                profileDropdown.classList.remove('active');
            });
        });
    }
    
    // Close dropdowns when scrolling
    window.onscroll = () => {
        if (navbar) navbar.classList.remove('active');
        if (menuBtn) menuBtn.classList.remove('fa-times');
        if (searchForm) searchForm.classList.remove('active');
        if (cartItemsContainer) cartItemsContainer.classList.remove('active');
        if (profileDropdown) profileDropdown.classList.remove('active');
    }

    // Helper function to close all overlays except one
    window.closeAllOverlays = function(except) {
        if (except !== 'navbar' && navbar) navbar.classList.remove('active');
        if (except !== 'menu-btn' && menuBtn) menuBtn.classList.remove('fa-times');
        if (except !== 'search' && searchForm) searchForm.classList.remove('active');
        if (except !== 'cart' && cartItemsContainer) cartItemsContainer.classList.remove('active');
        if (except !== 'profile' && profileDropdown) profileDropdown.classList.remove('active');
    };
    
    // Lấy và hiển thị số lượng sản phẩm trong giỏ hàng khi trang tải
    updateCartCount();
});

// Function to load cart contents
function loadCartContents() {
    const cartItems = document.querySelector('.cart-items');
    const cartCount = document.querySelector('.cart-count');
    const totalAmount = document.querySelector('.total-amount');
    
    // Show loading state
    cartItems.innerHTML = '<div class="loading-cart"><i class="fas fa-spinner fa-spin"></i><p>Đang tải giỏ hàng...</p></div>';
    
    fetch('ajax/get_cart.php')
        .then(response => response.json())
        .then(data => {
            if (data.empty) {
                cartItems.innerHTML = data.html;
                cartCount.textContent = '0';
                totalAmount.textContent = '0đ';
                
                // Hide checkout button if cart is empty
                document.querySelector('.checkout-btn').style.display = 'none';
            } else {
                cartItems.innerHTML = data.html;
                cartCount.textContent = data.count;
                totalAmount.textContent = data.total;
                
                // Show checkout button
                document.querySelector('.checkout-btn').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            cartItems.innerHTML = '<div class="cart-error">Đã xảy ra lỗi khi tải giỏ hàng</div>';
        });
}

// Function to update cart count
function updateCartCount() {
    fetch('ajax/get_cart.php')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.querySelector('.cart-count');
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
            // Remove item element with animation
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
            
            // Show success toast
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

// Function to show toast message
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Search functionality
const searchBox = document.querySelector('.search-box input');
const searchForm = document.querySelector('.search-box form');
let searchTimeout;

// Create suggestions container
const suggestionsContainer = document.createElement('div');
suggestionsContainer.className = 'search-suggestions';
searchBox.parentNode.appendChild(suggestionsContainer);

// Handle search input
searchBox.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const term = this.value.trim();
    
    if (term.length < 2) {
        suggestionsContainer.classList.remove('active');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`get_suggestions.php?term=${encodeURIComponent(term)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                
                let html = '';
                
                // Add suggestions section
                if (data.suggestions.length > 0) {
                    html += `
                        <div class="suggestion-section">
                            <h4>Gợi ý tìm kiếm</h4>
                            ${data.suggestions.map(item => `
                                <div class="suggestion-item" data-value="${item.food_name}">
                                    <i class="fas fa-search"></i>
                                    <span class="item-name">${item.food_name}</span>
                                    <span class="item-category">${item.foodcategory_name}</span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                // Add popular items section
                if (data.popularItems.length > 0) {
                    html += `
                        <div class="suggestion-section">
                            <h4>Món ăn bán chạy</h4>
                            ${data.popularItems.map(item => `
                                <div class="suggestion-item" data-value="${item.food_name}">
                                    <i class="fas fa-fire"></i>
                                    <span class="item-name">${item.food_name}</span>
                                    <span class="item-category">${item.foodcategory_name}</span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                suggestionsContainer.innerHTML = html;
                suggestionsContainer.classList.add('active');
            })
            .catch(error => console.error('Error:', error));
    }, 300);
});

// Handle suggestion click
suggestionsContainer.addEventListener('click', function(e) {
    const suggestionItem = e.target.closest('.suggestion-item');
    if (suggestionItem) {
        const value = suggestionItem.dataset.value;
        searchBox.value = value;
        suggestionsContainer.classList.remove('active');
        searchForm.submit();
    }
});

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!searchBox.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        suggestionsContainer.classList.remove('active');
    }
});