document.addEventListener('DOMContentLoaded', function() {
    // Các code khác giữ nguyên...
    
    // Xử lý chuyển danh mục với hiệu ứng
    const filterBtns = document.querySelectorAll('.filter-btn');
    const menuGrid = document.querySelector('.menu-grid');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Nếu nút đã active thì không làm gì cả
            if (this.classList.contains('active')) {
                return;
            }
            
            // Xóa trạng thái active của các nút khác
            filterBtns.forEach(btn => btn.classList.remove('active'));
            
            // Đánh dấu nút hiện tại là active
            this.classList.add('active');
            
            // Lấy thông tin danh mục
            const categoryId = this.getAttribute('data-category');
            const categoryUrl = buildCategoryUrl(categoryId);
            
            // Hiệu ứng fade out
            menuGrid.classList.add('fading-out');
            
            // Đợi kết thúc animation fade out
            setTimeout(() => {
                // Tạo loading animation
                const loadingHtml = `
                    <div class="category-loading">
                        <div class="loader"></div>
                    </div>
                `;
                menuGrid.innerHTML = loadingHtml;
                menuGrid.classList.remove('fading-out');
                
                // Cập nhật URL mà không reload trang
                history.pushState({category: categoryId}, '', categoryUrl);
                
                // Tải dữ liệu danh mục mới bằng AJAX
                loadCategoryItems(categoryId);
            }, 300);
        });
    });
    
    // Xử lý khi người dùng nhấn nút Back/Forward của trình duyệt
    window.addEventListener('popstate', function(event) {
        const categoryId = event.state?.category || '';
        loadCategoryItems(categoryId, false);
    });
    
    // Hàm tạo URL danh mục
    function buildCategoryUrl(categoryId) {
        const url = new URL(window.location.href);
        
        if (categoryId) {
            url.searchParams.set('category', categoryId);
        } else {
            url.searchParams.delete('category');
        }
        
        // Giữ nguyên tham số search nếu có
        const searchTerm = url.searchParams.get('search');
        if (!searchTerm) {
            url.searchParams.delete('search');
        }
        
        return url.toString();
    }
    
    // Hàm tải dữ liệu danh mục
    function loadCategoryItems(categoryId, updateHistory = true) {
        const url = buildCategoryUrl(categoryId);
        
        // Thêm tham số Ajax để server biết đây là request AJAX
        const fetchUrl = new URL(url);
        fetchUrl.searchParams.set('ajax', '1');
        
        fetch(fetchUrl.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Trích xuất phần menu-grid từ HTML trả về
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newMenuGrid = doc.querySelector('.menu-grid');
                
                if (newMenuGrid) {
                    // Chuẩn bị hiệu ứng fade in
                    menuGrid.classList.add('fading-in');
                    
                    // Cập nhật nội dung grid
                    menuGrid.innerHTML = newMenuGrid.innerHTML;
                    
                    // Kích hoạt hiệu ứng fade in
                    setTimeout(() => {
                        menuGrid.classList.remove('fading-in');
                    }, 50);
                    
                    // Update URL nếu cần
                    if (updateHistory) {
                        history.pushState({category: categoryId}, '', url);
                    }
                    
                    // Cập nhật trạng thái active cho nút
                    updateActiveButton(categoryId);
                }
            })
            .catch(error => {
                console.error('Error loading category items:', error);
                menuGrid.innerHTML = `<div class="error">Đã xảy ra lỗi khi tải danh mục. Vui lòng thử lại!</div>`;
            });
    }
    
    // Cập nhật trạng thái active cho nút danh mục
    function updateActiveButton(categoryId) {
        filterBtns.forEach(btn => {
            const btnCategoryId = btn.getAttribute('data-category');
            if ((btnCategoryId === '' && categoryId === '') || (btnCategoryId === categoryId)) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }
    
    // Chỉ giữ lại các phần tử chắc chắn tồn tại
    const modal = document.getElementById('quick-view-modal');
    const closeBtn = document.querySelector('.close');
    const addToCartBtns = document.querySelectorAll('.add-to-cart');

    console.log("Debug - Số nút thêm giỏ hàng:", addToCartBtns.length);
    
    // Đóng modal với hiệu ứng mượt mà
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModalWithAnimation);
    }
    
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModalWithAnimation();
        }
    });

    // Hàm đóng modal với hiệu ứng
    function closeModalWithAnimation() {
        if (modal) {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show');
                modal.classList.remove('closing');
                modal.style.display = 'none';
            }, 400); // Thời gian khớp với transition trong CSS
        }
    }

    // Gắn sự kiện cho nút thêm vào giỏ hàng
    if (addToCartBtns.length > 0) {
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.closest('.food-card').getAttribute('data-id');
                if (itemId) {
                    console.log("Thêm vào giỏ: ID =", itemId);
                    addToCart(itemId, 1);
                }
            });
        });
    }
    
    // Highlight danh mục đang chọn
    const currentCategory = new URLSearchParams(window.location.search).get('category');
    if (currentCategory) {
        const activeCategory = document.querySelector(`.categories a[href*="category=${currentCategory}"]`);
        if (activeCategory) {
            activeCategory.classList.add('active');
        }
    } else {
        const allCategoryLink = document.querySelector('.categories a[href="menu.php"]');
        if (allCategoryLink) {
            allCategoryLink.classList.add('active');
        }
    }

    // Tìm kiếm
    const searchInput = document.getElementById('menu-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const foodName = item.querySelector('h3').textContent.toLowerCase();
                const description = item.querySelector('.item-description')?.textContent.toLowerCase() || '';
                
                if (foodName.includes(searchTerm) || description.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Sắp xếp
    const sortSelect = document.getElementById('sort-menu');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const menuItems = Array.from(document.querySelectorAll('.menu-item'));
            const menuGrid = document.querySelector('.menu-grid');
            
            menuItems.sort((a, b) => {
                const aPrice = parseFloat(a.querySelector('.new-price').textContent.replace(/[^\d]/g, ''));
                const bPrice = parseFloat(b.querySelector('.new-price').textContent.replace(/[^\d]/g, ''));
                const aName = a.querySelector('h3').textContent;
                const bName = b.querySelector('h3').textContent;
                
                switch(this.value) {
                    case 'price-asc':
                        return aPrice - bPrice;
                    case 'price-desc':
                        return bPrice - aPrice;
                    case 'name-asc':
                        return aName.localeCompare(bName);
                    case 'name-desc':
                        return bName.localeCompare(aName);
                    default:
                        return 0;
                }
            });
            
            // Xóa tất cả các item hiện tại
            while (menuGrid.firstChild) {
                menuGrid.removeChild(menuGrid.firstChild);
            }
            
            // Thêm lại các item theo thứ tự mới
            menuItems.forEach(item => {
                menuGrid.appendChild(item);
            });
        });
    }
});

// Format price with thousands separator
function formatPrice(price) {
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Quantity controls for quick view modal
function decreaseQty() {
    const qty = document.getElementById('qty');
    if (qty && qty.value > 1) qty.value = parseInt(qty.value) - 1;
}

function increaseQty() {
    const qty = document.getElementById('qty');
    if (qty) qty.value = parseInt(qty.value) + 1;
}

// Open quick view modal với hiệu ứng mượt mà
function openQuickView(foodId) {
    console.log("Opening quick view for food ID:", foodId);
    const modal = document.getElementById('quick-view-modal');
    const content = document.getElementById('quick-view-content');
    
    if (!modal || !content) {
        console.error('Modal elements not found:', { modal, content });
        return;
    }
    
    // Show loading state
    content.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Đang tải...</p>
        </div>
    `;
    
    // Hiển thị modal với hiệu ứng
    modal.style.display = 'flex';  // Thay 'block' bằng 'flex' để căn giữa
    
    // Đảm bảo browser render trước khi thêm class 'show'
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Fetch food details
    fetch(`ajax/get_menu_item.php?id=${foodId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
                
                // Thêm nút đóng vào modal nếu chưa có
                if (!modal.querySelector('.close')) {
                    const closeButton = document.createElement('button');
                    closeButton.className = 'close';
                    closeButton.innerHTML = '<i class="fas fa-times"></i>';
                    closeButton.addEventListener('click', closeModalWithAnimation);
                    content.prepend(closeButton);
                }
                
                // Add event listeners for quantity controls
                const qtyInput = document.getElementById('qty');
                const decreaseBtn = document.querySelector('.quantity-btn:first-child');
                const increaseBtn = document.querySelector('.quantity-btn:last-child');
                
                if (decreaseBtn) {
                    decreaseBtn.addEventListener('click', decreaseQty);
                }
                
                if (increaseBtn) {
                    increaseBtn.addEventListener('click', increaseQty);
                }
            } else {
                content.innerHTML = `<div class="error"><p>Không thể tải thông tin món ăn này</p><p>${data.message || ''}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Error fetching food details:', error);
            content.innerHTML = `
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Đã xảy ra lỗi khi lấy thông tin món ăn. Vui lòng thử lại sau.</p>
                    <small>Chi tiết lỗi: ${error.message}</small>
                </div>
            `;
        });
}

// Thêm hàm đóng modal ở phạm vi global
function closeModalWithAnimation() {
    const modal = document.getElementById('quick-view-modal');
    if (modal) {
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('show');
            modal.classList.remove('closing');
            modal.style.display = 'none';
        }, 400); // Thời gian khớp với transition trong CSS
    }
}

// Add to cart functionality (giữ nguyên)
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
            }, 5000);
            
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

// Show toast message (giữ nguyên)
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

// Thêm hiệu ứng ripple khi click nút đóng
document.addEventListener('click', function(e) {
    if (e.target.closest('.close')) {
        const button = e.target.closest('.close');
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        button.appendChild(ripple);
        
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 2;
        
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${e.clientX - rect.left - (size/2)}px`;
        ripple.style.top = `${e.clientY - rect.top - (size/2)}px`;
        
        setTimeout(() => {
            ripple.remove();
        }, 600); // Thời gian khớp với animation
    }
});