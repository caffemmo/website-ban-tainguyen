<?php
/**
 * AJAX endpoint: Lấy dữ liệu Bảng xếp hạng (Leaderboard)
 * 
 * Truy vấn top users mua hàng nhiều nhất theo khoảng thời gian được cấu hình.
 * Dữ liệu được tổng hợp từ bảng product_order (đơn hàng đã hoàn thành).
 */
define("IN_SITE", true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . '/../../libs/lang.php');
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/session.php');

header('Content-Type: application/json; charset=utf-8');

$CMSNT = new DB();

// Lấy thông tin user nếu đã đăng nhập để biết đang là user nào (is_me)
if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}

// Kiểm tra tính năng BXH có được bật không
if ($CMSNT->site('leaderboard_status') != 1) {
    echo json_encode(['status' => 'error', 'msg' => __('Bảng xếp hạng đang tắt')]);
    exit;
}

// Validate period parameter - chỉ cho phép giá trị trong whitelist
$allowed_periods = ['daily', 'weekly', 'monthly', 'all_time'];
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
if (!in_array($period, $allowed_periods)) {
    $period = 'daily';
}

// Kiểm tra period có nằm trong danh sách được admin cấu hình không
$configured_periods = explode(',', $CMSNT->site('leaderboard_periods') ?: 'daily,weekly,monthly,all_time');
if (!in_array($period, $configured_periods)) {
    // Nếu period không được cấu hình, lấy period đầu tiên trong danh sách
    $period = !empty($configured_periods[0]) ? $configured_periods[0] : 'daily';
}

// Giới hạn số lượng user hiển thị
$limit = validate_int($CMSNT->site('leaderboard_limit'), 5, 50);
if ($limit === false) $limit = 10;

// Xác định điều kiện thời gian dựa trên period
$time_condition = '';
$time_params = [];

switch ($period) {
    case 'daily':
        // Trong ngày hôm nay (từ 00:00:00 đến hiện tại)
        $time_condition = " AND o.`create_gettime` >= ?";
        $time_params[] = date('Y-m-d') . ' 00:00:00';
        break;
    case 'weekly':
        // Trong tuần này (từ thứ 2 đầu tuần đến hiện tại)
        $monday = date('Y-m-d', strtotime('monday this week'));
        $time_condition = " AND o.`create_gettime` >= ?";
        $time_params[] = $monday . ' 00:00:00';
        break;
    case 'monthly':
        // Trong tháng này (từ ngày 1 đến hiện tại)
        $time_condition = " AND o.`create_gettime` >= ?";
        $time_params[] = date('Y-m-01') . ' 00:00:00';
        break;
    case 'all_time':
        // Toàn thời gian - không cần điều kiện thời gian
        $time_condition = '';
        $time_params = [];
        break;
}

// Truy vấn BXH: tổng hợp tổng chi tiêu + số đơn hàng từ bảng product_order
// Chỉ lấy đơn hàng chưa hoàn tiền (refund = 0) và chưa xóa (trash = 0)
$sql = "SELECT 
            u.`username`,
            COUNT(o.`id`) AS total_orders,
            SUM(o.`pay`) AS total_spent
        FROM `product_order` o
        INNER JOIN `users` u ON o.`buyer` = u.`id`
        WHERE o.`refund` = 0 
          AND o.`trash` = 0
          {$time_condition}
        GROUP BY o.`buyer`
        ORDER BY total_spent DESC
        LIMIT ?";

$params = array_merge($time_params, [$limit]);
$leaderboard = $CMSNT->get_list_safe($sql, $params);

// Format dữ liệu trả về - ẩn bớt username cho bảo mật
$data = [];
$rank = 1;
foreach ($leaderboard as $entry) {
    $username = $entry['username'];
    $is_me = (isset($getUser['username']) && strtolower($getUser['username']) == strtolower($username)) ? true : false;
    
    // Che giấu một phần username nếu không phải tài khoản của bản thân
    if ($is_me) {
        $display_username = $username;
    } else {
        if (mb_strlen($username) > 3) {
            $display_username = mb_substr($username, 0, 3) . '***';
        } else {
            $display_username = $username[0] . '**';
        }
    }
    
    $data[] = [
        'rank'         => $rank,
        'username'     => $display_username,
        'total_orders' => (int)$entry['total_orders'],
        'total_spent'  => format_currency($entry['total_spent']),
        'is_me'        => $is_me
    ];
    $rank++;
}

echo json_encode([
    'status'  => 'success',
    'period'  => $period,
    'data'    => $data
]);
