let navbar = document.querySelector('.navbar');
let cartItem = document.querySelector('.cart-items-container');
let searchForm = document.querySelector('.search-form');

document.querySelector('#menu-btn').onclick = () => {
    navbar.classList.toggle('active');
    searchForm.classList.remove('active'); 
    cartItem.classList.remove('active'); 
};

document.querySelector('#cart-btn').onclick = () => {
    cartItem.classList.toggle('active');
    navbar.classList.remove('active');
    searchForm.classList.remove('active');
};

document.querySelector('#search-btn').onclick = () => {
    searchForm.classList.toggle('active');
    navbar.classList.remove('active');
    cartItem.classList.remove('active');
};

// Đóng các menu khi click ra ngoài
document.addEventListener('click', (e) => {
    if (!navbar.contains(e.target) && !e.target.matches('#menu-btn')) {
        navbar.classList.remove('active');
    }
    if (!cartItem.contains(e.target) && !e.target.matches('#cart-btn')) {
        cartItem.classList.remove('active');
    }
    if (!searchForm.contains(e.target) && !e.target.matches('#search-btn')) {
        searchForm.classList.remove('active');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Learn More button functionality
    const learnMoreBtn = document.querySelector('.btn');
    if (learnMoreBtn) {
        learnMoreBtn.addEventListener('click', function() {
            // Scroll to About section
            const aboutSection = document.querySelector('.about');
            if (aboutSection) {
                aboutSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // Contact form functionality
    const contactForm = document.querySelector('.contact form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageStatus = document.getElementById('messageStatus');
            const formData = new FormData(this);

            fetch('ajax/contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageStatus.textContent = 'Message sent successfully!';
                    messageStatus.style.color = '#4CAF50';
                    contactForm.reset();
                } else {
                    messageStatus.textContent = data.message || 'Error sending message. Please try again.';
                    messageStatus.style.color = '#f44336';
                }
                messageStatus.style.display = 'block';
                setTimeout(() => {
                    messageStatus.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                messageStatus.textContent = 'An error occurred. Please try again later.';
                messageStatus.style.color = '#f44336';
                messageStatus.style.display = 'block';
                setTimeout(() => {
                    messageStatus.style.display = 'none';
                }, 3000);
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Thêm sự kiện cho nút thêm giỏ hàng
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const foodId = this.getAttribute('data-id');
            addToCart(foodId, 1);
        });
    });
    
    // Modal xem nhanh
    const modal = document.getElementById('quick-view-modal');
    if (modal) {
        // Đóng modal khi nhấn nút close
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        // Đóng modal khi nhấn ra ngoài
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Code xử lý nút "Xem thêm" trong phần About
    const learnMoreBtn = document.getElementById('learn-more-btn');
    const hiddenParagraphs = document.querySelectorAll('.about .content .hidden');
    
    if (learnMoreBtn && hiddenParagraphs.length > 0) {
        console.log("Found learn more button and hidden paragraphs"); // Debug log
        
        learnMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Learn more button clicked"); // Debug log
            
            // Toggle trạng thái hiển thị cho tất cả các đoạn văn ẩn
            hiddenParagraphs.forEach(paragraph => {
                paragraph.classList.toggle('show');
            });
            
            // Thay đổi text của nút dựa trên trạng thái hiện tại
            if (hiddenParagraphs[0].classList.contains('show')) {
                learnMoreBtn.textContent = 'Thu gọn';
            } else {
                learnMoreBtn.textContent = 'Xem thêm';
            }
        });
    } else {
        console.log("Learn more button or hidden paragraphs not found"); // Debug log
    }
    
});

// Hàm xem nhanh sản phẩm
function openQuickView(foodId) {
    console.log("Opening quick view for ID:", foodId);
    const modal = document.getElementById('quick-view-modal');
    const content = document.getElementById('quick-view-content');
    
    if (!modal || !content) {
        console.error('Modal elements not found');
        return;
    }
    
    // Hiển thị trạng thái loading
    content.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Đang tải...</p>
        </div>
    `;
    modal.style.display = 'block';
    
    // Fetch thông tin sản phẩm
    fetch(`ajax/get_menu_item.php?id=${foodId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
                
                // Thêm các sự kiện cho các nút trong modal
                setupModalButtons();
            } else {
                content.innerHTML = `<div class="error"><p>${data.message || 'Không thể tải thông tin sản phẩm'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Error fetching product details:', error);
            content.innerHTML = `<div class="error"><p>Đã xảy ra lỗi: ${error.message}</p></div>`;
        });
}

// Thiết lập các nút trong modal
function setupModalButtons() {
    // Xử lý nút tăng/giảm số lượng
    const qtyInput = document.getElementById('qty');
    const decreaseBtn = document.querySelector('.quantity-btn:first-child');
    const increaseBtn = document.querySelector('.quantity-btn:last-child');
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            if (qtyInput && qtyInput.value > 1) {
                qtyInput.value = parseInt(qtyInput.value) - 1;
            }
        });
    }
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', function() {
            if (qtyInput) {
                qtyInput.value = parseInt(qtyInput.value) + 1;
            }
        });
    }
    
    // Xử lý nút thêm vào giỏ hàng trong modal
    const addToCartBtn = document.querySelector('.add-to-cart-button');
    if (addToCartBtn) {
        const foodId = addToCartBtn.getAttribute('onclick').match(/addToCart\((\d+)/)[1];
        addToCartBtn.onclick = function() {
            const quantity = qtyInput ? qtyInput.value : 1;
            addToCart(foodId, quantity);
        };
    }
}

// Tăng giảm số lượng
function decreaseQty() {
    const qty = document.getElementById('qty');
    if (qty && qty.value > 1) {
        qty.value = parseInt(qty.value) - 1;
    }
}

function increaseQty() {
    const qty = document.getElementById('qty');
    if (qty) {
        qty.value = parseInt(qty.value) + 1;
    }
}

