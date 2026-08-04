<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../models/is_admin.php';
require_once __DIR__ . '/../../libs/locket-gold.php';
$body = [
    'title' => 'Đơn Locket Gold',
    'desc' => 'Quản lý đơn Locket Gold',
    'keyword' => 'locket gold orders'
];
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';
require_once __DIR__ . '/nav.php';

if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if (!locket_gold_ensure_orders_table()) {
    die('<script type="text/javascript">if(!alert("Chưa thể chuẩn bị bảng đơn Locket Gold")){window.history.back();}</script>');
}

$allowedStatuses = ['pending', 'processing', 'completed', 'failed'];

if (isset($_POST['SaveLocketGoldOrder'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('locket-gold-orders'), 1200);
    }
    $orderId = validate_int($_POST['order_id'] ?? '', 1, 2147483647);
    $status = isset($_POST['status']) ? trim((string) $_POST['status']) : '';
    $referenceCode = trim((string) ($_POST['reference_code'] ?? ''));
    $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

    if ($orderId === false || !in_array($status, $allowedStatuses, true)) {
        admin_msg_error('Thông tin cập nhật đơn không hợp lệ.', base_url_admin('locket-gold-orders'), 1200);
    }
    if (mb_strlen($referenceCode) > 100 || mb_strlen($adminNote) > 2000) {
        admin_msg_error('Mã tham chiếu hoặc ghi chú quá dài.', base_url_admin('locket-gold-orders'), 1200);
    }

    $order = $CMSNT->get_row_safe('SELECT * FROM `locket_gold_orders` WHERE `id` = ? LIMIT 1', [(int) $orderId]);
    if (!$order) {
        admin_msg_error('Không tìm thấy đơn Locket Gold.', base_url_admin('locket-gold-orders'), 1200);
    }

    $completedAt = $status === 'completed' ? ($order['completed_at'] ?: date('Y-m-d H:i:s')) : null;
    $updated = $CMSNT->update('locket_gold_orders', [
        'status' => $status,
        'reference_code' => $referenceCode !== '' ? $referenceCode : null,
        'admin_note' => $adminNote !== '' ? $adminNote : null,
        'admin_id' => (int) $getUser['id'],
        'updated_at' => date('Y-m-d H:i:s'),
        'completed_at' => $completedAt
    ], ' `id` = ? ', [(int) $orderId]);

    if (!$updated) {
        admin_msg_error('Không thể cập nhật đơn Locket Gold.', base_url_admin('locket-gold-orders'), 1200);
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => 'Cập nhật đơn Locket Gold #' . $order['order_code'] . ' thành ' . locket_gold_status_label($status)
    ]);
    admin_msg_success('Đã cập nhật đơn Locket Gold.', base_url_admin('locket-gold-orders'), 900);
}

if (isset($_POST['RefundLocketGoldOrder'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('locket-gold-orders'), 1200);
    }
    if (checkPermission($getUser['admin'], 'refund_orders_product') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền hoàn tiền đơn hàng")){window.history.back();}</script>');
    }

    $orderId = validate_int($_POST['order_id'] ?? '', 1, 2147483647);
    $refundReason = trim((string) ($_POST['refund_reason'] ?? ''));
    if ($orderId === false) {
        admin_msg_error('Mã đơn không hợp lệ.', base_url_admin('locket-gold-orders'), 1200);
    }

    $order = $CMSNT->get_row_safe('SELECT * FROM `locket_gold_orders` WHERE `id` = ? LIMIT 1', [(int) $orderId]);
    if (!$order) {
        admin_msg_error('Không tìm thấy đơn Locket Gold.', base_url_admin('locket-gold-orders'), 1200);
    }
    if ((float) $order['refund_amount'] > 0 || $order['status'] === 'refunded') {
        admin_msg_error('Đơn này đã được hoàn tiền trước đó.', base_url_admin('locket-gold-orders'), 1200);
    }

    $refundAmount = (float) $order['charged_amount'];
    $userModel = new users();
    $refundTransaction = 'REFUND_LOCKET_GOLD_' . $order['order_code'];
    if (!$userModel->RefundCredits($order['user_id'], $refundAmount, 'Hoàn tiền đơn Locket Gold ' . $order['order_code'], $refundTransaction)) {
        admin_msg_error('Không thể hoàn tiền cho tài khoản khách hàng.', base_url_admin('locket-gold-orders'), 1200);
    }

    $note = $refundReason !== '' ? $refundReason : 'Đơn được hoàn tiền bởi admin.';
    $CMSNT->update('locket_gold_orders', [
        'status' => 'refunded',
        'refund_amount' => $refundAmount,
        'admin_note' => $note,
        'admin_id' => (int) $getUser['id'],
        'updated_at' => date('Y-m-d H:i:s'),
        'refunded_at' => date('Y-m-d H:i:s')
    ], ' `id` = ? ', [(int) $orderId]);

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => 'Hoàn tiền đơn Locket Gold #' . $order['order_code'] . ': ' . $note
    ]);
    admin_msg_success('Đã hoàn tiền đơn Locket Gold.', base_url_admin('locket-gold-orders'), 900);
}

$limit = validate_int($_GET['limit'] ?? 20, 10, 100) ?: 20;
$page = validate_int($_GET['page'] ?? 1, 1, 100000) ?: 1;
$from = ($page - 1) * $limit;
$statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : '';
$where = ['o.`id` > 0'];
$params = [];

if (in_array($statusFilter, array_merge($allowedStatuses, ['refunded']), true)) {
    $where[] = 'o.`status` = ?';
    $params[] = $statusFilter;
}
if ($keyword !== '') {
    $where[] = '(o.`order_code` LIKE ? OR o.`usernames` LIKE ? OR u.`username` LIKE ?)';
    $likeKeyword = '%' . $keyword . '%';
    $params[] = $likeKeyword;
    $params[] = $likeKeyword;
    $params[] = $likeKeyword;
}

$whereSql = implode(' AND ', $where);
$countRow = $CMSNT->get_row_safe('SELECT COUNT(*) AS total FROM `locket_gold_orders` o LEFT JOIN `users` u ON u.`id` = o.`user_id` WHERE ' . $whereSql, $params);
$total = (int) ($countRow['total'] ?? 0);
$orders = $CMSNT->get_list_safe(
    'SELECT o.*, u.`username` AS customer_username
    FROM `locket_gold_orders` o
    LEFT JOIN `users` u ON u.`id` = o.`user_id`
    WHERE ' . $whereSql . '
    ORDER BY o.`id` DESC
    LIMIT ?, ?',
    array_merge($params, [$from, $limit])
) ?: [];

$baseOrdersUrl = base_url_admin('locket-gold-orders&limit=' . $limit . '&status=' . urlencode($statusFilter) . '&keyword=' . urlencode($keyword) . '&');
$pagination = pagination($baseOrdersUrl, $from, $total, $limit);
?>

<style>
    .locket-orders-intro { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:20px 22px; margin-bottom:20px; border:1px solid #f0df9d; border-left:4px solid #eab308; border-radius:10px; background:#fffdf4; }
    .locket-orders-intro h1 { margin:0 0 5px; color:#172033; font-size:20px; font-weight:800; }
    .locket-orders-intro p { margin:0; color:#718096; font-size:12px; }
    .locket-orders-card { border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 7px 20px rgba(31,55,82,.05); }
    .locket-orders-filter { display:grid; grid-template-columns:minmax(180px, 220px) minmax(180px, 1fr) auto; align-items:end; gap:12px; padding:14px; border:1px solid #e5edf1; border-radius:8px; background:#f8fbfc; }
    .locket-orders-filter label { display:grid; gap:6px; color:#3f5d70; font-size:11px; font-weight:700; }
    .locket-orders-table-wrap { overflow-x:auto; border:1px solid #e1eaf0; border-radius:8px; }
    .locket-orders-table { width:100%; min-width:1120px; border-collapse:collapse; color:#45657b; font-size:12px; }
    .locket-orders-table th { padding:12px 13px; border-bottom:1px solid #d9e6ed; color:#607b8e; background:#f7fafc; font-size:10px; font-weight:800; text-align:left; text-transform:uppercase; white-space:nowrap; }
    .locket-orders-table td { padding:13px; border-top:1px solid #e7eef2; vertical-align:top; }
    .locket-orders-table tbody tr:first-child td { border-top:0; }
    .locket-orders-code { display:grid; gap:4px; min-width:150px; }
    .locket-orders-code strong { color:#173b56; font-size:12px; }
    .locket-orders-code small,
    .locket-orders-meta small { color:#79909f; font-size:10px; }
    .locket-orders-meta { display:grid; gap:4px; min-width:130px; }
    .locket-orders-usernames { max-width:210px; color:#375b71; line-height:1.5; white-space:pre-line; overflow-wrap:anywhere; }
    .locket-orders-price { color:#8d5f08; font-weight:800; white-space:nowrap; }
    .locket-orders-status { display:inline-flex; min-height:25px; align-items:center; padding:4px 8px; border:1px solid; border-radius:999px; font-size:10px; font-weight:800; white-space:nowrap; }
    .locket-orders-status.is-pending { border-color:#ecd498; color:#896117; background:#fff9eb; }
    .locket-orders-status.is-processing { border-color:#bbd9ef; color:#25618c; background:#f2f8fd; }
    .locket-orders-status.is-completed { border-color:#a9dfc8; color:#087655; background:#effbf5; }
    .locket-orders-status.is-failed,
    .locket-orders-status.is-refunded { border-color:#efb4b4; color:#a13d3d; background:#fff5f5; }
    .locket-orders-action { display:grid; min-width:250px; gap:7px; }
    .locket-orders-action .form-control,
    .locket-orders-action .form-select { min-height:35px; font-size:11px; }
    .locket-orders-action textarea { min-height:58px; resize:vertical; }
    .locket-orders-action-buttons { display:flex; flex-wrap:wrap; gap:7px; }
    .locket-orders-action-buttons .btn { font-size:11px; }
    .locket-orders-refund { padding-top:7px; border-top:1px solid #edf1f3; }
    .locket-orders-pagination { display:flex; justify-content:flex-end; margin-top:16px; }
    @media (max-width:767.98px) {
        .locket-orders-intro { align-items:flex-start; flex-direction:column; }
        .locket-orders-filter { grid-template-columns:1fr; }
        .locket-orders-pagination { justify-content:center; }
    }
</style>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="locket-orders-intro">
            <div>
                <h1><i class="fa-solid fa-crown me-2" aria-hidden="true"></i><?= __('Đơn Locket Gold'); ?></h1>
                <p><?= __('Xử lý thủ công, cập nhật trạng thái và hoàn tiền trực tiếp từ Admin Panel.'); ?></p>
            </div>
            <a class="btn btn-dark btn-sm" href="<?= base_url_admin('settings&tab=locket-gold'); ?>"><i class="fa-solid fa-sliders me-1" aria-hidden="true"></i><?= __('Cấu hình giá'); ?></a>
        </div>

        <div class="card custom-card locket-orders-card">
            <div class="card-body">
                <form class="locket-orders-filter mb-4" method="GET">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="locket-gold-orders">
                    <label><?= __('Trạng thái'); ?>
                        <select class="form-select form-select-sm" name="status">
                            <option value=""><?= __('Tất cả trạng thái'); ?></option>
                            <?php foreach (array_merge($allowedStatuses, ['refunded']) as $status): ?>
                            <option value="<?= $status; ?>" <?= $statusFilter === $status ? 'selected' : ''; ?>><?= __(locket_gold_status_label($status)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= __('Tìm đơn hoặc username'); ?>
                        <input class="form-control form-control-sm" type="search" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?= __('Mã đơn, username khách...'); ?>">
                    </label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-search me-1" aria-hidden="true"></i><?= __('Lọc'); ?></button>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= base_url_admin('locket-gold-orders'); ?>"><?= __('Xóa lọc'); ?></a>
                    </div>
                </form>

                <?php if (empty($orders)): ?>
                <div class="text-center text-muted py-5"><i class="fa-solid fa-inbox d-block mb-2 fs-3"></i><?= __('Chưa có đơn Locket Gold phù hợp.'); ?></div>
                <?php else: ?>
                <div class="locket-orders-table-wrap">
                    <table class="locket-orders-table">
                        <thead>
                            <tr>
                                <th><?= __('Đơn'); ?></th>
                                <th><?= __('Khách hàng'); ?></th>
                                <th><?= __('Username'); ?></th>
                                <th><?= __('Gói'); ?></th>
                                <th><?= __('Chi phí'); ?></th>
                                <th><?= __('Trạng thái'); ?></th>
                                <th><?= __('Cập nhật'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><span class="locket-orders-code"><strong>#<?= htmlspecialchars((string) $order['order_code'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8'); ?></small></span></td>
                                <td><span class="locket-orders-meta"><strong><?= htmlspecialchars((string) ($order['customer_username'] ?: 'User #' . $order['user_id']), ENT_QUOTES, 'UTF-8'); ?></strong><small>ID <?= (int) $order['user_id']; ?></small></span></td>
                                <td><span class="locket-orders-usernames"><?= htmlspecialchars((string) $order['usernames'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><span class="locket-orders-meta"><strong><?= htmlspecialchars((string) $order['package_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?= (int) $order['account_count']; ?>/<?= (int) $order['account_limit']; ?> <?= __('tài khoản'); ?></small></span></td>
                                <td><span class="locket-orders-price"><?= format_currency((float) $order['charged_amount']); ?></span><?php if ((float) $order['refund_amount'] > 0): ?><small class="d-block text-danger mt-1"><?= __('Đã hoàn'); ?></small><?php endif; ?></td>
                                <td><span class="locket-orders-status <?= locket_gold_status_class($order['status']); ?>"><?= __(locket_gold_status_label($order['status'])); ?></span></td>
                                <td>
                                    <?php if ($order['status'] === 'refunded'): ?>
                                    <span class="text-muted small"><?= __('Đơn đã hoàn tiền, không thể cập nhật thêm.'); ?></span>
                                    <?php else: ?>
                                    <form class="locket-orders-action" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                        <select class="form-select form-select-sm" name="status" aria-label="<?= __('Trạng thái đơn'); ?>">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                            <option value="<?= $status; ?>" <?= $order['status'] === $status ? 'selected' : ''; ?>><?= __(locket_gold_status_label($status)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input class="form-control form-control-sm" type="text" name="reference_code" maxlength="100" value="<?= htmlspecialchars((string) ($order['reference_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?= __('Mã tham chiếu nội bộ'); ?>">
                                        <textarea class="form-control form-control-sm" name="admin_note" maxlength="2000" placeholder="<?= __('Ghi chú hiển thị cho khách'); ?>"><?= htmlspecialchars((string) ($order['admin_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        <div class="locket-orders-action-buttons"><button class="btn btn-dark btn-sm" type="submit" name="SaveLocketGoldOrder"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i><?= __('Lưu'); ?></button></div>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ((float) $order['refund_amount'] <= 0 && $order['status'] !== 'refunded' && checkPermission($getUser['admin'], 'refund_orders_product') == true): ?>
                                    <form class="locket-orders-action locket-orders-refund" method="POST" onsubmit="return confirm('Xác nhận hoàn tiền đơn này?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">
                                        <input class="form-control form-control-sm" type="text" name="refund_reason" maxlength="500" placeholder="<?= __('Lý do hoàn tiền'); ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit" name="RefundLocketGoldOrder"><i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i><?= __('Hoàn tiền'); ?></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="locket-orders-pagination"><?= $pagination; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
