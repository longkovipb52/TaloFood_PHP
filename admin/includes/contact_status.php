<?php
// Định nghĩa các trạng thái liên hệ
define('CONTACT_STATUS_PENDING', 'Chưa xử lý');
define('CONTACT_STATUS_PROCESSING', 'Đang xử lý');
define('CONTACT_STATUS_COMPLETED', 'Đã xử lý');
define('CONTACT_STATUS_REJECTED', 'Từ chối');

// Mảng chứa tất cả các trạng thái
$contact_statuses = [
    CONTACT_STATUS_PENDING => [
        'label' => 'Chưa xử lý',
        'class' => 'bg-warning',
        'icon' => 'fa-clock',
        'description' => 'Liên hệ mới, chưa được xử lý'
    ],
    CONTACT_STATUS_PROCESSING => [
        'label' => 'Đang xử lý',
        'class' => 'bg-info',
        'icon' => 'fa-spinner fa-spin',
        'description' => 'Liên hệ đang được xử lý'
    ],
    CONTACT_STATUS_COMPLETED => [
        'label' => 'Đã xử lý',
        'class' => 'bg-success',
        'icon' => 'fa-check-circle',
        'description' => 'Liên hệ đã được xử lý xong'
    ],
    CONTACT_STATUS_REJECTED => [
        'label' => 'Từ chối',
        'class' => 'bg-danger',
        'icon' => 'fa-times-circle',
        'description' => 'Liên hệ bị từ chối'
    ]
];

// Hàm lấy thông tin trạng thái
function get_contact_status_info($status) {
    global $contact_statuses;
    return isset($contact_statuses[$status]) ? $contact_statuses[$status] : null;
}

// Hàm tạo badge trạng thái
function get_contact_status_badge($status) {
    $status_info = get_contact_status_info($status);
    if (!$status_info) return '';
    
    return sprintf(
        '<span class="badge %s">
            <i class="fas %s"></i> %s
        </span>',
        $status_info['class'],
        $status_info['icon'],
        $status_info['label']
    );
}

// Hàm lấy danh sách trạng thái cho select box
function get_contact_status_options($selected = '') {
    global $contact_statuses;
    $options = '';
    
    foreach ($contact_statuses as $value => $info) {
        $selected_attr = ($value == $selected) ? 'selected' : '';
        $options .= sprintf(
            '<option value="%s" %s>%s</option>',
            $value,
            $selected_attr,
            $info['label']
        );
    }
    
    return $options;
}

// Hàm kiểm tra trạng thái hợp lệ
function is_valid_contact_status($status) {
    global $contact_statuses;
    return isset($contact_statuses[$status]);
}

// Hàm lấy mô tả trạng thái
function get_contact_status_description($status) {
    $status_info = get_contact_status_info($status);
    return $status_info ? $status_info['description'] : '';
}

// Hàm lấy class CSS cho trạng thái
function get_contact_status_class($status) {
    $status_info = get_contact_status_info($status);
    return $status_info ? $status_info['class'] : '';
}

// Hàm lấy icon cho trạng thái
function get_contact_status_icon($status) {
    $status_info = get_contact_status_info($status);
    return $status_info ? $status_info['icon'] : '';
}
?> 