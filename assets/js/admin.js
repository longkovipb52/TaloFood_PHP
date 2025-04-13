document.addEventListener('DOMContentLoaded', function() {
    // Toggle Sidebar
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('content').classList.toggle('active');
    });

    // Revenue Chart
    const revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            datasets: [{
                label: 'Doanh Thu',
                data: [12, 19, 3, 5, 2, 3, 8, 14, 10, 15, 9, 11],
                borderColor: '#E3B448',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(227, 180, 72, 0.1)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Orders Pie Chart
    const ordersPieChart = new Chart(document.getElementById('ordersPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Đã Giao', 'Đang Giao', 'Chờ Xử Lý', 'Đã Hủy'],
            datasets: [{
                data: [45, 25, 20, 10],
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Initialize DataTables
    $('#recentOrdersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]]
    });
});

// Toast Notification System
class AdminToast {
    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    show(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div class="toast-content">
                <p>${message}</p>
            </div>
            <button type="button" class="toast-close">&times;</button>
        `;

        this.container.appendChild(toast);

        const close = toast.querySelector('.toast-close');
        close.addEventListener('click', () => this.hide(toast));

        setTimeout(() => this.hide(toast), 5000);
    }

    hide(toast) {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 500);
    }
}

// Initialize Toast
const adminToast = new AdminToast();

// Sidebar Toggle
$(document).ready(function() {
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $(this).toggleClass('active');
    });

    // Close sidebar on small screens when clicking outside
    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#sidebar, #sidebarCollapse').length) {
                $('#sidebar').removeClass('active');
                $('#sidebarCollapse').removeClass('active');
            }
        }
    });

    // Active menu item
    const currentPath = window.location.pathname;
    const filename = currentPath.split('/').pop();

    $('nav#sidebar .components a').each(function() {
        const href = $(this).attr('href');
        if (href === filename) {
            $(this).addClass('active');
            $(this).closest('.collapse').addClass('show');
            $(this).closest('li').find('a[data-bs-toggle="collapse"]').removeClass('collapsed');
        }
    });

    // Toast notifications
    if (typeof showToast === 'function') {
        const toast = $('.toast');
        if (toast.length) {
            showToast();
        }
    }
}); 