document.addEventListener("DOMContentLoaded", () => {
    // Kiểm tra xem các phần tử đã tồn tại chưa để tránh tạo trùng lặp
    if (!document.querySelector('.swipe-overlay')) {
        const swipeOverlay = document.createElement('div');
        swipeOverlay.className = 'swipe-overlay';
        document.body.appendChild(swipeOverlay);
    }

    if (!document.querySelector('.page-loader')) {
        const pageLoader = document.createElement('div');
        pageLoader.className = 'page-loader';
        pageLoader.innerHTML = `
            <div class="food-loader">
                <div class="food-item"></div>
                <div class="food-item"></div>
                <div class="food-item"></div>
                <div class="food-item"></div>
            </div>
        `;
        document.body.appendChild(pageLoader);
    }

    const swipeOverlay = document.querySelector('.swipe-overlay');
    const pageLoader = document.querySelector('.page-loader');

    // Hiệu ứng khi trang vừa tải
    window.addEventListener('load', () => {
        // Ẩn loader nếu đang hiển thị
        if (pageLoader.classList.contains('active')) {
            pageLoader.classList.remove('active');
        }

        // Xử lý animation vào trang
        document.body.classList.add('page-transition-enter');
        
        setTimeout(() => {
            document.body.classList.remove('page-transition-enter');
        }, 800);
    });

    // Xử lý chuyển trang khi click vào links
    const links = document.querySelectorAll("a[href]:not([target='_blank']):not([href^='#']):not([href^='javascript:']):not(.dropdown-item)");

    links.forEach(link => {
        // Đảm bảo mỗi link chỉ được gắn sự kiện một lần
        if (link.getAttribute('data-transition-initialized') === 'true') {
            return;
        }
        
        link.setAttribute('data-transition-initialized', 'true');
        
        link.addEventListener("click", function(event) {
            const target = this;
            const isSameDomain = target.hostname === window.location.hostname;
            const isDifferentPage = target.pathname !== window.location.pathname;
            const hasNoPreventTransition = !target.hasAttribute('data-no-transition');

            // Chỉ áp dụng cho liên kết nội bộ và trang khác
            if (isSameDomain && isDifferentPage && hasNoPreventTransition) {
                event.preventDefault();
                
                // Chỉ sử dụng một hiệu ứng để tránh khựng
                pageLoader.classList.add('active');
                
                // Chuyển hướng sau thời gian ngắn hơn
                setTimeout(() => {
                    window.location.href = target.href;
                }, 400); // Giảm thời gian xuống để tránh cảm giác chờ đợi
            }
        });
    });

    // Thêm hiệu ứng khi scroll
    function revealOnScroll() {
        const revealElements = document.querySelectorAll('.menu-item, .food-card, .product-item');
        
        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('revealed');
            }
        });
    }

    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll(); // Kiểm tra các phần tử khi trang tải
});