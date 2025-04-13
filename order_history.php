<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['toast'] = [
        'message' => 'Vui lòng đăng nhập để xem lịch sử đơn hàng',
        'type' => 'error'
    ];
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Lấy thông tin điểm uy tín
try {
    $stmt = $conn->prepare("SELECT reputation_points FROM account WHERE account_id = ?");
    $stmt->execute([$user_id]);
    $reputation_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $reputation_points = $reputation_result ? $reputation_result['reputation_points'] : 100;
    
    // Xác định trạng thái điểm uy tín
    $reputation_status_class = '';
    $reputation_status_text = '';
    
    if ($reputation_points >= 70) {
        $reputation_status_class = 'reputation-normal';
        $reputation_status_text = 'Tốt';
    } elseif ($reputation_points >= 40) {
        $reputation_status_class = 'reputation-warning-status';
        $reputation_status_text = 'Trung bình';
    } elseif ($reputation_points >= 10) {
        $reputation_status_class = 'reputation-danger';
        $reputation_status_text = 'Thấp';
    } else {
        $reputation_status_class = 'reputation-danger';
        $reputation_status_text = 'Rất thấp';
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $reputation_points = 100;
    $reputation_status_class = 'reputation-normal';
    $reputation_status_text = 'Tốt';
}

try {
    // Lấy tất cả đơn hàng của người dùng
    $stmt = $conn->prepare("
        SELECT bill_id, ngaydat, ngaygiao, status, address, total_amount, payment_method, created_at, phone
        FROM bill 
        WHERE id_account = :user_id 
        ORDER BY ngaydat DESC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy thông tin chi tiết đơn hàng nếu có tham số order_id
    $orderDetails = [];
    if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
        $order_id = $_GET['order_id'];
        
        // Kiểm tra đơn hàng thuộc về người dùng hiện tại
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM bill 
            WHERE bill_id = :order_id AND id_account = :user_id
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Lấy thông tin chi tiết đơn hàng
            $stmt = $conn->prepare("
                SELECT bi.*, f.food_name, f.image, f.new_price
                FROM bill_info bi
                JOIN food f ON bi.id_food = f.food_id
                WHERE bi.id_bill = :order_id
            ");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy thông tin đơn hàng
            $stmt = $conn->prepare("
                SELECT * FROM bill WHERE bill_id = :order_id
            ");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['toast'] = [
        'message' => 'Có lỗi xảy ra khi lấy dữ liệu đơn hàng',
        'type' => 'error'
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - TaloFood</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .order-history-container {
            max-width: 1200px;
            margin: 12rem auto 3rem;
            padding: 2rem;
            background: var(--black);
            border-radius: 1rem;
            color: #fff;
        }

        .order-history-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .order-history-header h2 {
            font-size: 2.8rem;
            color: var(--main-color);
            margin-bottom: 1rem;
        }

        .order-history-tabs {
            display: flex;
            overflow-x: auto;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
        }

        .order-history-tabs::-webkit-scrollbar {
            height: 5px;
        }

        .order-history-tabs::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        .order-history-tabs::-webkit-scrollbar-thumb {
            background: var(--main-color);
            border-radius: 5px;
        }

        .tab-btn {
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.05);
            border: none;
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1.6rem;
            cursor: pointer;
            margin-right: 1rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab-btn.active {
            background: var(--main-color);
            color: #fff;
        }

        .order-list {
            margin-top: 2rem;
        }

        .order-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            border-color: rgba(211, 173, 127, 0.3);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .order-id {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--main-color);
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.4rem;
            text-transform: uppercase;
            font-weight: bold;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-processing {
            background-color: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }

        .status-shipping {
            background-color: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }

        .status-delivered {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 1.4rem;
            color: #aaa;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1.6rem;
            color: #fff;
        }

        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }

        .total-amount {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .total-amount span {
            color: var(--main-color);
        }

        .btn-view-details {
            background: var(--main-color);
            color: #fff;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-view-details:hover {
            background: #c19b6c;
            transform: translateY(-3px);
        }

        .btn-cancel-order {
            background: #dc3545;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel-order:hover {
            background: #c82333;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .reputation-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: #fff;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            border-radius: 0.5rem;
        }

        .reputation-warning i {
            font-size: 2.4rem;
            color: #ffc107;
            margin-right: 1.5rem;
        }

        .reputation-error {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: #fff;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            border-radius: 0.5rem;
        }

        .reputation-error i {
            font-size: 2.4rem;
            color: #dc3545;
            margin-right: 1.5rem;
        }

        .reputation-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .reputation-points {
            display: flex;
            align-items: center;
        }

        .reputation-points i {
            color: var(--main-color);
            font-size: 2rem;
            margin-right: 1rem;
        }

        .reputation-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--main-color);
        }

        .reputation-status {
            font-size: 1.4rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .reputation-normal {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .reputation-warning-status {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .reputation-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .reputation-guide {
            margin: 2rem 0;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .reputation-guide summary {
            padding: 1.5rem;
            font-size: 1.6rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .reputation-guide summary i {
            transition: transform 0.3s ease;
        }

        .reputation-guide details[open] summary i {
            transform: rotate(180deg);
        }

        .guide-content {
            padding: 0 1.5rem 1.5rem;
            font-size: 1.4rem;
            line-height: 1.6;
        }

        .guide-content p {
            margin-bottom: 1rem;
        }

        .guide-content ul {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }

        .guide-content li {
            margin-bottom: 0.5rem;
        }

        .reputation-badge {
            padding: 0.2rem 0.7rem;
            border-radius: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .reputation-badge.high {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .reputation-badge.medium {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .reputation-badge.low {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .reputation-badge.very-low {
            background-color: rgba(0, 0, 0, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .no-orders {
            text-align: center;
            padding: 5rem 0;
        }

        .no-orders i {
            font-size: 5rem;
            color: var(--main-color);
            margin-bottom: 2rem;
        }

        .no-orders h3 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
        }

        .no-orders p {
            font-size: 1.6rem;
            color: #aaa;
            margin-bottom: 2rem;
        }

        .no-orders .btn {
            display: inline-block;
            background: var(--main-color);
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.6rem;
            transition: all 0.3s ease;
        }

        .no-orders .btn:hover {
            background: #c19b6c;
            transform: translateY(-3px);
        }

        /* Back button styling */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1.6rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(211, 173, 127, 0.2);
            transform: translateX(-5px);
        }

        .back-btn i {
            margin-right: 1rem;
        }

        /* Order detail modal styling */
        .order-detail-modal {
            max-width: 100%;
            margin: 2rem 0;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title {
            font-size: 2.4rem;
            color: var(--main-color);
        }

        /* Order summary section */
        .order-summary {
            margin-bottom: 3rem;
        }

        .summary-header {
            font-size: 2rem;
            color: var(--main-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(211, 173, 127, 0.3);
        }

        .summary-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-label {
            font-size: 1.4rem;
            color: #aaa;
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.6rem;
            color: #fff;
        }

        /* Order items section */
        .order-items {
            margin-top: 3rem;
        }

        .item-header {
            display: grid;
            grid-template-columns: 0.8fr 2fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: bold;
            color: var(--main-color);
            font-size: 1.6rem;
        }

        .order-item {
            display: grid;
            grid-template-columns: 0.8fr 2fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            align-items: center;
            font-size: 1.6rem;
        }

        .item-image {
            width: 80px;
            height: 80px;
            overflow: hidden;
            border-radius: 0.5rem;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-name {
            font-weight: 500;
        }

        .order-total {
            margin-top: 2rem;
            text-align: right;
            font-size: 2rem;
            font-weight: bold;
        }

        .order-total span {
            color: var(--main-color);
        }

        /* Review section */
        .item-actions {
            grid-column: 1 / -1;
            padding: 1rem 0;
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .write-review-btn, .edit-review-btn {
            padding: 0.8rem 1.5rem;
            background: var(--main-color);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .write-review-btn:hover, .edit-review-btn:hover {
            background: #c19b6c;
            transform: translateY(-3px);
        }

        .review-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .user-rating {
            margin-bottom: 0.5rem;
        }

        .user-rating .fa-star {
            color: #aaa;
            margin-right: 0.2rem;
        }

        .user-rating .fa-star.active {
            color: #ffc107;
        }

        .user-comment {
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .item-header, .order-item {
                grid-template-columns: 1fr 2fr 1fr;
                grid-template-areas:
                    "image name name"
                    "price quantity subtotal";
                gap: 0.5rem;
            }
            
            .item-header div:nth-child(1) { grid-area: image; }
            .item-header div:nth-child(2) { grid-area: name; }
            .item-header div:nth-child(3) { grid-area: price; }
            .item-header div:nth-child(4) { grid-area: quantity; }
            .item-header div:nth-child(5) { grid-area: subtotal; }
            
            .item-image { grid-area: image; }
            .item-name { grid-area: name; }
            .item-price { grid-area: price; }
            .item-quantity { grid-area: quantity; }
            .item-subtotal { grid-area: subtotal; }
            
            .item-actions {
                justify-content: center;
            }
        }

        /* Styles cho modal đánh giá */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 1200;
            overflow: auto;
            padding-top: 5rem;
        }

        .modal-content {
            background-color: var(--black);
            margin: 0 auto;
            width: 90%;
            max-width: 550px;
            border-radius: 1.2rem;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(211, 173, 127, 0.2);
            animation: modalFadeIn 0.4s ease;
            overflow: hidden;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-40px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid rgba(211, 173, 127, 0.2);
            background: rgba(255, 255, 255, 0.03);
        }

        .modal-header h2 {
            color: var(--main-color);
            font-size: 2.4rem;
            margin: 0;
            font-weight: 600;
        }

        .close-modal {
            color: #aaa;
            font-size: 2.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
        }

        .close-modal:hover {
            color: var(--main-color);
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(90deg);
        }

        #review-form {
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 2.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.6rem;
            color: #ddd;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .form-group strong {
            display: block;
            font-size: 1.8rem;
            color: #fff;
            margin-top: 0.5rem;
            margin-left: 0.2rem;
        }

        .rating {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            justify-content: center;
        }

        .rating-star {
            font-size: 3rem;
            color: #555;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .rating-star:hover {
            color: #ffc107;
            transform: scale(1.2);
        }

        .rating-star.active {
            color: #ffc107;
            text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        #review-comment {
            width: 100%;
            padding: 1.5rem;
            font-size: 1.6rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 0.8rem;
            resize: none;
            height: 150px;
            transition: all 0.3s ease;
        }

        #review-comment:focus {
            border-color: var(--main-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(211, 173, 127, 0.2);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
            margin-top: 2.5rem;
        }

        .form-actions button {
            padding: 1.2rem 2.5rem;
            font-size: 1.6rem;
            border-radius: 0.8rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        button.close-modal {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 0.8rem;
            width: auto;
            height: auto;
        }

        button.close-modal:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-3px);
        }

        .btn-submit {
            background: var(--main-color);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: linear-gradient(to right, var(--main-color), #c19b6c);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(211, 173, 127, 0.3);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        /* Hiệu ứng gợn sóng khi click */
        .btn-submit::after {
            content: '';
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.3) 10%, transparent 10.01%);
            background-repeat: no-repeat;
            background-position: 50%;
            transform: scale(10, 10);
            opacity: 0;
            transition: transform .5s, opacity 1s;
        }

        .btn-submit:active::after {
            transform: scale(0, 0);
            opacity: .3;
            transition: 0s;
        }

        /* Đảm bảo CSS mobile cho modal */
        @media (max-width: 576px) {
            .modal-content {
                width: 95%;
            }
            
            .modal-header {
                padding: 1.5rem;
            }
            
            #review-form {
                padding: 1.5rem;
            }
            
            .rating {
                gap: 0.5rem;
            }
            
            .rating-star {
                font-size: 2.8rem;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="order-history-container">
        <div class="order-history-header">
            <h2>Lịch sử đơn hàng</h2>
        </div>
        
        <div class="reputation-info">
            <div class="reputation-points">
                <i class="fas fa-medal"></i>
                <span>Điểm uy tín: <span class="reputation-value"><?php echo $reputation_points; ?>/100</span></span>
            </div>
            <span class="reputation-status <?php echo $reputation_status_class; ?>">
                <?php echo $reputation_status_text; ?>
            </span>
        </div>

        <div class="reputation-guide">
            <details>
                <summary>Tìm hiểu về hệ thống điểm uy tín <i class="fas fa-chevron-down"></i></summary>
                <div class="guide-content">
                    <p><strong>Điểm uy tín là gì?</strong></p>
                    <p>Điểm uy tín phản ánh mức độ tin cậy của tài khoản của bạn. Điểm uy tín ảnh hưởng đến các quyền lợi và hạn chế khi sử dụng dịch vụ của chúng tôi.</p>
                    
                    <p><strong>Làm thế nào để tăng điểm uy tín?</strong></p>
                    <ul>
                        <li>Thanh toán trước qua PayPal: +10 điểm</li>
                        <li>Không hủy đơn hàng</li>
                        <li>Đặt hàng và thanh toán đầy đủ</li>
                    </ul>
                    
                    <p><strong>Các mức điểm uy tín:</strong></p>
                    <ul>
                        <li><span class="reputation-badge high">70-100 điểm</span>: Khách hàng bình thường, có thể đặt hàng và thanh toán sau.</li>
                        <li><span class="reputation-badge medium">40-69 điểm</span>: Có thể đặt hàng và thanh toán sau, nhưng sẽ nhận được cảnh báo.</li>
                        <li><span class="reputation-badge low">10-39 điểm</span>: Bắt buộc phải thanh toán trước khi đặt hàng.</li>
                        <li><span class="reputation-badge very-low">0-9 điểm</span>: Tạm thời khóa tính năng đặt hàng.</li>
                    </ul>
                </div>
            </details>
        </div>

        <?php if (isset($_GET['order_id']) && isset($currentOrder)): ?>
            <!-- Chi tiết đơn hàng -->
            <a href="order_history.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
            </a>
            
            <div class="order-detail-modal">
                <div class="modal-header">
                    <h2 class="modal-title">Chi tiết đơn hàng #<?php echo $currentOrder['bill_id']; ?></h2>
                </div>
                
                <div class="order-summary">
                    <h3 class="summary-header">Thông tin đơn hàng</h3>
                    <div class="summary-details">
                        <div class="summary-item">
                            <span class="summary-label">Mã đơn hàng</span>
                            <span class="summary-value">#<?php echo $currentOrder['bill_id']; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Ngày đặt</span>
                            <span class="summary-value"><?php echo date('d/m/Y', strtotime($currentOrder['ngaydat'])); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Ngày giao dự kiến</span>
                            <span class="summary-value"><?php echo date('d/m/Y', strtotime($currentOrder['ngaygiao'])); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Trạng thái</span>
                            <span class="summary-value">
                                <?php 
                                $statusClass = '';
                                switch($currentOrder['status']) {
                                    case 'Chờ xác nhận':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'Đang xử lý':
                                        $statusClass = 'status-processing';
                                        break;
                                    case 'Đang giao':
                                        $statusClass = 'status-shipping';
                                        break;
                                    case 'Đã giao':
                                        $statusClass = 'status-delivered';
                                        break;
                                    case 'Đã hủy':
                                        $statusClass = 'status-cancelled';
                                        break;
                                }
                                ?>
                                <span class="order-status <?php echo $statusClass; ?>"><?php echo $currentOrder['status']; ?></span>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Địa chỉ giao hàng</span>
                            <span class="summary-value"><?php echo htmlspecialchars($currentOrder['address']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Số điện thoại</span>
                            <span class="summary-value"><?php echo htmlspecialchars($currentOrder['phone']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Phương thức thanh toán</span>
                            <span class="summary-value"><?php echo htmlspecialchars($currentOrder['payment_method']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3 class="summary-header">Chi tiết sản phẩm</h3>
                    
                    <div class="item-header">
                        <div>Sản phẩm</div>
                        <div>Tên sản phẩm</div>
                        <div>Giá</div>
                        <div>Số lượng</div>
                        <div>Tổng tiền</div>
                    </div>
                    
                    <?php foreach ($orderDetails as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <img src="uploads/foods/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>">
                        </div>
                        <div class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                        <div class="item-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</div>
                        <div class="item-quantity"><?php echo $item['count']; ?></div>
                        <div class="item-subtotal"><?php echo number_format($item['price'] * $item['count'], 0, ',', '.'); ?>đ
                    </div>
                    </div>
                    <div class="item-actions">
                            <?php 
                            // Kiểm tra xem đơn hàng đã giao chưa và người dùng đã đánh giá sản phẩm này chưa
                            if ($currentOrder['status'] == 'Đã giao'):
                                $reviewed = false;
                                // Kiểm tra xem sản phẩm đã được đánh giá chưa
                                $reviewStmt = $conn->prepare("SELECT review_id, star_rating, comment FROM reviews 
                                                           WHERE id_account = ? AND id_food = ?");
                                $reviewStmt->execute([$_SESSION['user_id'], $item['id_food']]);
                                $review = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                                if ($review): 
                                    $reviewed = true;
                            ?>
                                <div class="review-box">
                                    <div class="user-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo ($i <= $review['star_rating']) ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="user-comment"><?php echo htmlspecialchars($review['comment']); ?></div>
                                    <button class="edit-review-btn" data-food-id="<?php echo $item['id_food']; ?>" 
                                        data-food-name="<?php echo htmlspecialchars($item['food_name']); ?>"
                                        data-rating="<?php echo $review['star_rating']; ?>"
                                        data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                        <i class="fas fa-edit"></i> Sửa đánh giá
                                    </button>
                                </div>
                            <?php else: ?>
                                <button class="write-review-btn" data-food-id="<?php echo $item['id_food']; ?>" 
                                    data-food-name="<?php echo htmlspecialchars($item['food_name']); ?>">
                                    <i class="fas fa-star"></i> Đánh giá sản phẩm
                                </button>
                            <?php 
                                endif;
                            endif; 
                            ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="order-total">
                        Tổng thanh toán: <span><?php echo number_format($currentOrder['total_amount'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Danh sách đơn hàng -->
            <div class="order-history-tabs">
                <button class="tab-btn active" data-status="all">Tất cả</button>
                <button class="tab-btn" data-status="Chờ xác nhận">Chờ xác nhận</button>
                <button class="tab-btn" data-status="Đang xử lý">Đang xử lý</button>
                <button class="tab-btn" data-status="Đang giao">Đang giao</button>
                <button class="tab-btn" data-status="Đã giao">Đã giao</button>
                <button class="tab-btn" data-status="Đã hủy">Đã hủy</button>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-basket"></i>
                <h3>Bạn chưa có đơn hàng nào</h3>
                <p>Hãy khám phá các món ăn ngon của chúng tôi và đặt ngay nhé!</p>
                <a href="menu.php" class="btn">Đặt món ngay</a>
            </div>
            <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div class="order-id">Đơn hàng #<?php echo $order['bill_id']; ?></div>
                        <?php 
                        $statusClass = '';
                        switch($order['status']) {
                            case 'Chờ xác nhận':
                                $statusClass = 'status-pending';
                                break;
                            case 'Đang xử lý':
                                $statusClass = 'status-processing';
                                break;
                            case 'Đang giao':
                                $statusClass = 'status-shipping';
                                break;
                            case 'Đã giao':
                                $statusClass = 'status-delivered';
                                break;
                            case 'Đã hủy':
                                $statusClass = 'status-cancelled';
                                break;
                        }
                        ?>
                        <div class="order-status <?php echo $statusClass; ?>"><?php echo $order['status']; ?></div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <span class="detail-label">Ngày đặt</span>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($order['ngaydat'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ngày giao dự kiến</span>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($order['ngaygiao'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phương thức thanh toán</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <div class="total-amount">
                            Tổng tiền: <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="action-buttons">
                            <a href="order_history.php?order_id=<?php echo $order['bill_id']; ?>" class="btn-view-details">
                                Xem chi tiết
                            </a>
                            <?php 
                            // Chỉ hiển thị nút hủy đơn cho đơn hàng chưa được giao và chưa bị hủy
                            if (in_array($order['status'], ['Chờ xác nhận', 'Đang xử lý', 'Đã thanh toán'])): 
                            ?>
                            <button class="btn-cancel-order" data-order-id="<?php echo $order['bill_id']; ?>" 
                                    data-reputation="<?php echo $reputation_points; ?>">
                                Hủy đơn hàng
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Đánh giá sản phẩm -->
    <div id="review-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="review-title">Đánh giá sản phẩm</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="review-form">
                <input type="hidden" name="food_id" id="food-id">
                <input type="hidden" name="action" id="review-action" value="add">
                <input type="hidden" name="star_rating" id="star-rating" value="0">
                
                <div class="form-group">
                    <label>Món ăn:</label>
                    <strong id="food-name"></strong>
                </div>
                
                <div class="form-group">
                    <label>Đánh giá của bạn:</label>
                    <div class="rating">
                        <i class="far fa-star rating-star" data-value="1"></i>
                        <i class="far fa-star rating-star" data-value="2"></i>
                        <i class="far fa-star rating-star" data-value="3"></i>
                        <i class="far fa-star rating-star" data-value="4"></i>
                        <i class="far fa-star rating-star" data-value="5"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review-comment">Nhận xét:</label>
                    <textarea id="review-comment" name="comment" rows="5" placeholder="Nhập nhận xét của bạn về món ăn này..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="close-modal">Hủy</button>
                    <button type="submit" class="btn-submit">Gửi đánh giá</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!-- Tạm bỏ file toast.js để tránh xung đột -->
    <!-- <script src="assets/js/toast.js"></script> -->
    <script>
        // Đặt ngay đầu DOM Content Loaded để đảm bảo chạy trước
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý nút hủy đơn hàng (đặt lên đầu)
            const cancelButtons = document.querySelectorAll('.btn-cancel-order');
            console.log("Số lượng nút hủy đơn:", cancelButtons.length); // Debug
            
            if (cancelButtons.length > 0) {
                cancelButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        console.log("Đã click nút hủy đơn"); // Debug
                        const orderId = this.getAttribute('data-order-id');
                        console.log("Order ID:", orderId); // Debug
                        
                        // Kiểm tra xem đơn hàng có phải là "Đã thanh toán" không
                        const orderCard = this.closest('.order-card');
                        const isPaymentCompleted = orderCard.querySelector('.order-status').textContent.trim() === 'Đã thanh toán';
                        
                        let confirmMessage = 'Bạn có chắc chắn muốn hủy đơn hàng này? Điểm uy tín của bạn sẽ bị trừ 20 điểm.';
                        
                        if (isPaymentCompleted) {
                            confirmMessage = 'Bạn có chắc chắn muốn hủy đơn hàng đã thanh toán này? Điểm uy tín của bạn sẽ bị trừ 10 điểm và tiền sẽ được hoàn lại trong 3-5 ngày làm việc.';
                        }
                        
                        if (confirm(confirmMessage)) {
                            const formData = new FormData();
                            formData.append('order_id', orderId);
                            
                            fetch('ajax/cancel_order.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                console.log("Response status:", response.status); // Debug
                                return response.json();
                            })
                            .then(data => {
                                console.log("Response data:", data); // Debug
                                if (data.success) {
                                    // Sử dụng Toastify thay vì hàm showToast tự định nghĩa
                                    Toastify({
                                        text: `${data.message}. Điểm uy tín: -${data.deduction}`,
                                        duration: 3000,
                                        close: true,
                                        gravity: "top",
                                        position: "right",
                                        backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                                    }).showToast();
                                    
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 2000);
                                } else {
                                    Toastify({
                                        text: data.message,
                                        duration: 3000,
                                        close: true,
                                        gravity: "top",
                                        position: "right",
                                        backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                                    }).showToast();
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Toastify({
                                    text: 'Đã xảy ra lỗi khi hủy đơn hàng',
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                                }).showToast();
                            });
                        }
                    });
                });
            } else {
                console.warn('Không tìm thấy nút hủy đơn hàng nào trên trang.');
            }
            
            // Các đoạn code JavaScript khác...
            const tabBtns = document.querySelectorAll('.tab-btn');
            const orderCards = document.querySelectorAll('.order-card');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const status = this.getAttribute('data-status');
                    
                    // Update active tab
                    tabBtns.forEach(tab => tab.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter orders
                    orderCards.forEach(card => {
                        if (status === 'all' || card.getAttribute('data-status') === status) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Show no orders message if no orders match the filter
                    let visibleOrders = false;
                    orderCards.forEach(card => {
                        if (card.style.display !== 'none') {
                            visibleOrders = true;
                        }
                    });
                    
                    const noOrdersDiv = document.querySelector('.no-orders');
                    if (!visibleOrders && noOrdersDiv) {
                        noOrdersDiv.style.display = 'block';
                    } else if (noOrdersDiv) {
                        noOrdersDiv.style.display = 'none';
                    }
                });
            });

            // Variables
            const modal = document.getElementById('review-modal');
            const closeBtn = document.querySelector('.close-modal');
            const stars = document.querySelectorAll('.rating-star');
            const starInput = document.getElementById('star-rating');
            const reviewForm = document.getElementById('review-form');
            
            // Mở modal khi nhấn nút đánh giá
            document.querySelectorAll('.write-review-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const foodId = this.getAttribute('data-food-id');
                    const foodName = this.getAttribute('data-food-name');
                    
                    // Reset form
                    reviewForm.reset();
                    document.getElementById('food-id').value = foodId;
                    document.getElementById('food-name').textContent = foodName;
                    document.getElementById('review-action').value = 'add';
                    document.getElementById('review-title').textContent = 'Đánh giá sản phẩm';
                    
                    // Reset stars
                    stars.forEach(star => {
                        star.className = 'far fa-star rating-star';
                    });
                    starInput.value = 0;
                    
                    // Hiện modal
                    modal.style.display = 'block';
                });
            });
            
            // Mở modal khi nhấn nút sửa đánh giá
            document.querySelectorAll('.edit-review-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const foodId = this.getAttribute('data-food-id');
                    const foodName = this.getAttribute('data-food-name');
                    const rating = parseInt(this.getAttribute('data-rating'));
                    const comment = this.getAttribute('data-comment');
                    
                    // Điền dữ liệu vào form
                    document.getElementById('food-id').value = foodId;
                    document.getElementById('food-name').textContent = foodName;
                    document.getElementById('review-comment').value = comment;
                    document.getElementById('review-action').value = 'edit';
                    document.getElementById('review-title').textContent = 'Sửa đánh giá';
                    
                    // Cập nhật stars
                    updateStars(rating);
                    
                    // Hiện modal
                    modal.style.display = 'block';
                });
            });
            
            // Đóng modal khi nhấn bất kỳ nút đóng nào
            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // Đóng modal khi nhấn bên ngoài
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Xử lý đánh giá sao
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    updateStars(value);
                });
                
                star.addEventListener('mouseover', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    stars.forEach(s => {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        if (starValue <= value) {
                            s.className = 'fas fa-star rating-star';
                        } else {
                            s.className = 'far fa-star rating-star';
                        }
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    const currentRating = parseInt(starInput.value);
                    updateStars(currentRating);
                });
            });
            
            // Hàm cập nhật hiển thị sao
            function updateStars(rating) {
                stars.forEach(s => {
                    const starValue = parseInt(s.getAttribute('data-value'));
                    if (starValue <= rating) {
                        s.className = 'fas fa-star rating-star active';
                    } else {
                        s.className = 'far fa-star rating-star';
                    }
                });
                starInput.value = rating;
            }
            
            // Xử lý submit form đánh giá
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const rating = parseInt(starInput.value);
                const comment = document.getElementById('review-comment').value.trim();
                
                // Kiểm tra đánh giá
                if (rating === 0) {
                    showToast('Vui lòng chọn số sao đánh giá', 'error');
                    return;
                }
                
                if (comment === '') {
                    showToast('Vui lòng nhập nhận xét của bạn', 'error');
                    return;
                }
                
                // Gửi AJAX request
                const formData = new FormData(this);
                
                fetch('ajax/submit_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.style.display = 'none';
                        showToast(data.message, 'success');
                        
                        // Reload trang sau 1 giây để cập nhật đánh giá
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi gửi đánh giá', 'error');
                });
            });
            
            // Hàm hiển thị thông báo toast
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);
                
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>