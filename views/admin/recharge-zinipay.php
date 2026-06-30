<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge ZiniPay'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '
<!-- ckeditor -->
<script src="' . BASE_URL('public/ckeditor/ckeditor.js') . '"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');

if (checkPermission($getUser['admin'], 'view_recharge') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình nạp tiền ZiniPay')
    ]);

    // Xử lý upload icon
    if (!empty($_FILES['zinipay_icon_file']['name'])) {
        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml'];
        $file_type = $_FILES['zinipay_icon_file']['type'];
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['zinipay_icon_file']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo-zinipay-' . time() . '.' . $ext;
            $upload_path = __DIR__ . '/../../mod/img/' . $new_filename;
            if (move_uploaded_file($_FILES['zinipay_icon_file']['tmp_name'], $upload_path)) {
                $_POST['zinipay_icon'] = 'mod/img/' . $new_filename;
            }
        }
    }
    unset($_POST['zinipay_icon_file']);

    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền ZiniPay'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("' . __('Save successfully!') . '")){window.history.back().location.reload();}</script>');
}

// Tạo lại mã bí mật cho URL callback
if (isset($_POST['RegenerateSecret'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }

    $newSecret = bin2hex(random_bytes(16));
    if ($CMSNT->get_row_safe("SELECT * FROM `settings` WHERE `name` = ?", ['zinipay_callback_secret'])) {
        $CMSNT->update('settings', ['value' => $newSecret], " `name` = 'zinipay_callback_secret' ");
    } else {
        $CMSNT->insert('settings', ['name' => 'zinipay_callback_secret', 'value' => $newSecret]);
    }

    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => __('Tạo lại mã bí mật callback ZiniPay')
    ]);

    die('<script type="text/javascript">if(!alert("' . __('Đã tạo lại mã bí mật! Hãy cập nhật lại URL callback trong Dashboard ZiniPay.') . '")){window.location.href="' . base_url_admin('recharge-zinipay') . '";}</script>');
}

// Kiểm tra giao dịch thủ công từ API (đối soát đơn pending khi không nhận được callback)
if (isset($_POST['CheckTransaction'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }

    $checkId = validate_int($_POST['id'] ?? 0, 1);
    if ($checkId === false) {
        die('<script type="text/javascript">if(!alert("ID đơn không hợp lệ")){window.history.back();}</script>');
    }

    require_once(__DIR__ . '/../../libs/zinipay.php');
    require_once(__DIR__ . '/../../libs/database/users.php');

    $backUrl = base_url_admin('recharge-zinipay');

    $row = $CMSNT->get_row_safe("SELECT * FROM `payment_zinipay` WHERE `id` = ? AND `status` = 0", [$checkId]);
    if (!$row) {
        die('<script type="text/javascript">if(!alert("Không tìm thấy đơn đang chờ với ID này (có thể đã xử lý xong)")){window.location.href="' . $backUrl . '";}</script>');
    }
    if (empty($row['trade_no'])) {
        die('<script type="text/javascript">if(!alert("Đơn này không có Invoice ID từ ZiniPay nên không thể kiểm tra")){window.history.back();}</script>');
    }

    $config = [
        'api_key' => $CMSNT->site('zinipay_api_key'),
        'api_url' => $CMSNT->site('zinipay_api_url') ?: 'https://api.zinipay.com'
    ];
    $verify = zinipayVerifyPayment($config, $row['trade_no']);
    $verifyStatus = isset($verify['status']) ? strtoupper($verify['status']) : '';
    $paidAmount   = isset($verify['amount']) ? (float) $verify['amount'] : 0;
    $payMethod    = $verify['payment_method'] ?? '';

    if ($verifyStatus === 'COMPLETED') {
        if ($paidAmount + 0.01 < (float) $row['amount']) {
            die('<script type="text/javascript">if(!alert("Số tiền thanh toán nhỏ hơn số tiền đơn hàng - không cộng tiền")){window.history.back();}</script>');
        }

        // Atomic claim chống cộng trùng (giống callback)
        $claimed = $CMSNT->update('payment_zinipay', [
            'status'     => 1,
            'trade_no'   => $row['trade_no'],
            'type'       => $payMethod,
            'updated_at' => gettime()
        ], " `id` = ? AND `status` = 0 ", [$row['id']]);

        if (!$claimed) {
            die('<script type="text/javascript">if(!alert("Đơn vừa được xử lý bởi tiến trình khác")){window.location.href="' . $backUrl . '";}</script>');
        }

        $user = new users();
        $isCong = $user->AddCredits(
            $row['user_id'],
            $row['price'],
            __('Recharge ZiniPay') . ' #' . $row['trans_id'],
            'TOPUP_zinipay_' . $row['trans_id']
        );

        if ($isCong) {
            $CMSNT->insert('deposit_log', [
                'user_id'     => $row['user_id'],
                'method'      => 'ZiniPay' . ($payMethod ? ' ' . $payMethod : ''),
                'amount'      => $row['price'],
                'received'    => $row['price'],
                'create_time' => time(),
                'is_virtual'  => 0
            ]);
            $CMSNT->insert("logs", [
                'user_id'    => $getUser['id'],
                'ip'         => myip(),
                'device'     => getUserAgent(),
                'createdate' => gettime(),
                'action'     => __('Đối soát thủ công ZiniPay #') . $row['trans_id']
            ]);
            die('<script type="text/javascript">if(!alert("Giao dịch THÀNH CÔNG - đã cộng tiền cho user")){window.location.href="' . $backUrl . '";}</script>');
        } else {
            // Cộng tiền thất bại => hoàn đơn về trạng thái chờ
            $CMSNT->update('payment_zinipay', [
                'status'     => 0,
                'updated_at' => gettime()
            ], " `id` = ? AND `status` = 1 ", [$row['id']]);
            die('<script type="text/javascript">if(!alert("Cộng tiền thất bại - đã hoàn đơn về trạng thái chờ")){window.history.back();}</script>');
        }
    } elseif ($verifyStatus === 'FAILED') {
        $CMSNT->update('payment_zinipay', [
            'status'     => 2,
            'trade_no'   => $row['trade_no'],
            'type'       => $payMethod,
            'updated_at' => gettime()
        ], " `id` = ? AND `status` = 0 ", [$row['id']]);
        die('<script type="text/javascript">if(!alert("Giao dịch THẤT BẠI trên ZiniPay - đã đánh dấu Failed")){window.location.href="' . $backUrl . '";}</script>');
    } elseif ($verifyStatus === 'PENDING') {
        die('<script type="text/javascript">if(!alert("Giao dịch vẫn đang CHỜ thanh toán trên ZiniPay")){window.history.back();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Không lấy được trạng thái hợp lệ từ API - kiểm tra lại API Key / kết nối")){window.history.back();}</script>');
    }
}

if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = check_string(intval($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = "  `id` > 0 ";
$shortByDate = '';
$trans_id = '';
$createdate = '';
$amount = '';
$user_id = '';
$username = '';
$status = '';

if (!empty($_GET['status'])) {
    $status = check_string($_GET['status']);
    if ($status == 1) {
        $where .= ' AND `status` = 0 ';
    } else if ($status == 2) {
        $where .= ' AND `status` = 1 ';
    } else if ($status == 3) {
        $where .= ' AND `status` = 2 ';
    }
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if ($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")) {
        $where .= ' AND `user_id` =  "' . $idUser['id'] . '" ';
    } else {
        $where .= ' AND `user_id` =  "" ';
    }
}
if (!empty($_GET['user_id'])) {
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = ' . $user_id . ' ';
}

if (!empty($_GET['trans_id'])) {
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` LIKE "%' . $trans_id . '%" ';
}
if (!empty($_GET['amount'])) {
    $amount = check_string($_GET['amount']);
    $where .= ' AND `amount` = ' . $amount . ' ';
}
if (!empty($_GET['created_at'])) {
    $created_at = check_string($_GET['created_at']);
    $createdate = $created_at;
    $created_at_1 = str_replace('-', '/', $created_at);
    $created_at_1 = explode(' to ', $created_at_1);

    if ($created_at_1[0] != $created_at_1[1]) {
        $created_at_1 = [$created_at_1[0] . ' 00:00:00', $created_at_1[1] . ' 23:59:59'];
        $where .= " AND `created_at` >= '" . $created_at_1[0] . "' AND `created_at` <= '" . $created_at_1[1] . "' ";
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = check_string($_GET['shortByDate']);
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if ($shortByDate == 1) {
        $where .= " AND `created_at` LIKE '%" . $currentDate . "%' ";
    }
    if ($shortByDate == 2) {
        $where .= " AND YEAR(created_at) = $currentYear AND WEEK(created_at, 1) = $currentWeek ";
    }
    if ($shortByDate == 3) {
        $where .= " AND MONTH(created_at) = '$currentMonth' AND YEAR(created_at) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `payment_zinipay` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `payment_zinipay` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("recharge-zinipay&limit=$limit&shortByDate=$shortByDate&created_at=$createdate&trans_id=$trans_id&amount=$amount&user_id=$user_id&username=$username&status=$status&"), $from, $totalDatatable, $limit);

$yesterday = date('Y-m-d', strtotime("-1 day"));
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");

$total_yesterday = intval($CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 AND  `created_at` LIKE '%" . $yesterday . "%' ")['SUM(price)']);
$total_today = $CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 AND `created_at` LIKE '%" . $currentDate . "%' ")['SUM(price)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 ")['SUM(price)'];

$checkKey = checkAddonLicense($CMSNT->site('zinipay_license'), 'SHOPCLONE7_GATEWAY_ZINIPAY');

// Tự sinh mã bí mật cho URL callback nếu chưa có (áp dụng cả site đã cài đặt sẵn)
$zinipayCallbackSecret = $CMSNT->site('zinipay_callback_secret');
if (empty($zinipayCallbackSecret)) {
    $zinipayCallbackSecret = bin2hex(random_bytes(16));
    if ($CMSNT->get_row_safe("SELECT * FROM `settings` WHERE `name` = ?", ['zinipay_callback_secret'])) {
        $CMSNT->update('settings', ['value' => $zinipayCallbackSecret], " `name` = 'zinipay_callback_secret' ");
    } else {
        $CMSNT->insert('settings', ['name' => 'zinipay_callback_secret', 'value' => $zinipayCallbackSecret]);
    }
}
$zinipayCallbackUrl = base_url('api/callback_zinipay.php?secret=' . $zinipayCallbackSecret);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Phương thức thanh toán ZiniPay</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <button type="button" id="open-card-config" class="btn btn-primary label-btn mb-3">
                        <i class="ri-settings-4-line label-btn-icon me-2"></i> CẤU HÌNH
                    </button>
                </div>
            </div>
            <div class="col-xl-12" id="card-config" style="display: none;">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <?php if ($checkKey['status'] != true): ?>
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-4">
                                        <label class="col-sm-7 col-form-label"><?= __('Trạng thái'); ?></label>
                                        <div class="col-sm-5">
                                            <select class="form-control" name="zinipay_status">
                                                <option <?= $CMSNT->site('zinipay_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                                <option <?= $CMSNT->site('zinipay_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-7 col-form-label">Giấy phép kích hoạt Addon</label>
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control" placeholder="921abf4dbff01xxxxxf3c562c356c769" value="<?= $CMSNT->site('zinipay_license'); ?>" name="zinipay_license">
                                        </div>
                                        <div style="margin-top: 10px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;">
                                            <strong>Chú ý:</strong> Bạn cần phải mua giấy phép kích hoạt <a target="_blank" style="color: #007bff;" href="https://client.cmsnt.co/cart.php?a=add&pid=121">Addon</a> (giá <strong>1.200.000đ</strong>) trước khi sử dụng. Truy cập Admin/Cài Đặt/Addons để mua giấy phép hoặc nhấn vào <a target="_blank" style="color: #007bff;" href="https://client.cmsnt.co/cart.php?a=add&pid=121">đây</a>.
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label"><?= __('Trạng thái'); ?></label>
                                        <div class="col-sm-7">
                                            <select class="form-control" name="zinipay_status">
                                                <option <?= $CMSNT->site('zinipay_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                                <option <?= $CMSNT->site('zinipay_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">Tên hiển thị</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_name') ?: 'ZiniPay (bKash, Nagad)'; ?>" name="zinipay_name" placeholder="ZiniPay (bKash, Nagad)">
                                            <small class="text-muted">Tên này sẽ hiển thị ở menu nạp tiền cho khách hàng</small>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">Logo/Icon</label>
                                        <div class="col-sm-7">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if ($CMSNT->site('zinipay_icon')): ?>
                                                    <img src="<?= base_url($CMSNT->site('zinipay_icon')); ?>" width="40" height="40" class="me-2 rounded" style="object-fit: contain;">
                                                <?php endif; ?>
                                                <input type="file" class="form-control form-control-sm" name="zinipay_icon_file" accept="image/*">
                                            </div>
                                            <small class="text-muted">Upload ảnh logo mới (PNG, JPG, WEBP, SVG)</small>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">API Key</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_api_key'); ?>" name="zinipay_api_key" placeholder="zini-api-key">
                                            <small class="text-muted">Lấy tại Dashboard ZiniPay (header zini-api-key)</small>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">API URL</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_api_url') ?: 'https://api.zinipay.com'; ?>" name="zinipay_api_url">
                                            <small>Mặc định: https://api.zinipay.com</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">Min (BDT)</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_min'); ?>" name="zinipay_min">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">Max (BDT)</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_max'); ?>" name="zinipay_max">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label"><?= __('Rate (1 BDT =)'); ?></label>
                                        <div class="col-sm-7">
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?= $CMSNT->site('zinipay_rate'); ?>" name="zinipay_rate">
                                                <span class="input-group-text"><?= $CMSNT->get_row("SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-5 col-form-label">Webhook URL</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" readonly value="<?= $zinipayCallbackUrl; ?>" onclick="this.select();">
                                            <small class="text-muted">Dán <strong>nguyên URL này (đã kèm mã bí mật <code>?secret=</code>)</strong> vào Dashboard ZiniPay. Callback thiếu/sai mã sẽ bị từ chối (403).</small>
                                            <button type="button" class="btn btn-sm btn-warning mt-2" onclick="regenZinipaySecret()">
                                                <i class="ri-refresh-line me-1"></i> Tạo lại mã bí mật
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <hr>
                                    <div class="row mb-4">
                                        <label class="col-sm-2 col-form-label"><?= __('Note'); ?></label>
                                        <div class="col-sm-10">
                                            <textarea id="zinipay_notice" name="zinipay_notice"><?= $CMSNT->site('zinipay_notice'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <?php endif ?>
                            </div>
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" name="SaveSettings" class="btn btn-primary btn-block"><i class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default"><?= format_currency($total_all_time); ?></p>
                                        <p class="mb-0 text-muted">Toàn thời gian</p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-danger rounded-circle fs-20"><i class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?= format_currency($CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 AND MONTH(created_at) = '$currentMonth' AND YEAR(created_at) = '$currentYear' ")['SUM(price)']); ?>
                                        </p>
                                        <p class="mb-0 text-muted">Tháng <?= date('m'); ?></p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-info rounded-circle fs-20"><i class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?= format_currency($CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 AND YEAR(created_at) = $currentYear AND WEEK(created_at, 1) = $currentWeek ")['SUM(price)']); ?>
                                        </p>
                                        <p class="mb-0 text-muted">Trong tuần</p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-warning rounded-circle fs-20"><i class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default"><?= format_currency($total_today); ?></p>
                                        <p class="mb-0 text-muted">Hôm nay
                                            <?php
                                            if ($total_yesterday != 0) {
                                                $revenueGrowth = ($total_today - $total_yesterday) / $total_yesterday * 100;
                                                if ($revenueGrowth > 0) {
                                                    echo '<span class="fs-12 text-success ms-2"><i class="ti ti-trending-up me-1 d-inline-block"></i>' . round($revenueGrowth, 2) . '% </span>';
                                                } else if ($revenueGrowth < 0) {
                                                    echo '<span class="fs-12 text-danger ms-2"><i class="ti ti-trending-down me-1 d-inline-block"></i>' . round(abs($revenueGrowth), 2) . '% </span>';
                                                }
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-primary rounded-circle fs-20"><i class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">THỐNG KÊ NẠP TIỀN THÁNG <?= date('m'); ?></div>
                    </div>
                    <div class="card-body">
                        <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                        <script>
                            (function() {
                                Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)", Chart.defaults.color = "#8c9097";
                                const labels = [
                                    <?php
                                    $month = date('m');
                                    $year = date('Y');
                                    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                    for ($day = 1; $day <= $numOfDays; $day++) {
                                        echo "\"$day/$month/$year\",";
                                    }
                                    ?>
                                ];
                                const data = {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Paid',
                                        backgroundColor: 'rgb(132, 90, 223)',
                                        borderColor: 'rgb(132, 90, 223)',
                                        data: [
                                            <?php
                                            $data = [];
                                            for ($day = 1; $day <= $numOfDays; $day++) {
                                                $date = "$year-$month-$day";
                                                $row = $CMSNT->get_row("SELECT SUM(price) FROM payment_zinipay WHERE `status` = 1 AND DATE(created_at) = '$date' ");
                                                $data[$day - 1] = $row['SUM(price)'];
                                            }
                                            for ($i = 0; $i < $numOfDays; $i++) {
                                                echo "$data[$i],";
                                            }
                                            ?>
                                        ],
                                    }]
                                };
                                const config = {
                                    type: 'bar',
                                    data: data,
                                    options: {}
                                };
                                const myChart = new Chart(document.getElementById('chartjs-line'), config);
                            })();
                        </script>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">LỊCH SỬ NẠP TIỀN ZINIPAY</div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="recharge-zinipay">
                                <div class="col-md-2 col-6">
                                    <input class="form-control form-control-sm" value="<?= $user_id; ?>" name="user_id" placeholder="<?= __('ID User'); ?>">
                                </div>
                                <div class="col-md-2 col-6">
                                    <input class="form-control form-control-sm" value="<?= $username; ?>" name="username" placeholder="<?= __('Username'); ?>">
                                </div>
                                <div class="col-md-2 col-6">
                                    <input class="form-control form-control-sm" value="<?= $trans_id; ?>" name="trans_id" placeholder="<?= __('Transaction'); ?>">
                                </div>
                                <div class="col-md-2 col-6">
                                    <input class="form-control form-control-sm" value="<?= $amount; ?>" name="amount" placeholder="<?= __('Amount'); ?>">
                                </div>
                                <div class="col-md-2 col-6">
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="">Status</option>
                                        <option <?= $status == 1 ? 'selected' : ''; ?> value="1">Waiting</option>
                                        <option <?= $status == 2 ? 'selected' : ''; ?> value="2">Completed</option>
                                        <option <?= $status == 3 ? 'selected' : ''; ?> value="3">Failed</option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="created_at" class="form-control form-control-sm" id="daterange" value="<?= $createdate; ?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i> <?= __('Search'); ?></button>
                                    <a class="btn btn-sm btn-danger" href="<?= base_url_admin('recharge-zinipay'); ?>"><i class="fa fa-trash"></i> <?= __('Clear filter'); ?></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                    <select name="shortByDate" onchange="this.form.submit()" class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr class="text-center">
                                        <th class="text-center"><?= __('Username'); ?></th>
                                        <th class="text-center"><?= __('Mã giao dịch'); ?></th>
                                        <th class="text-center"><?= __('Phương thức'); ?></th>
                                        <th class="text-center"><?= __('Số lượng'); ?></th>
                                        <th class="text-center"><?= __('Thực nhận'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th class="text-center"><?= __('Create date'); ?></th>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td class="text-center">
                                                <a class="text-primary" href="<?= base_url_admin('user-edit&id=' . $row['user_id']); ?>">
                                                    <?= getRowRealtime("users", $row['user_id'], "username"); ?> [ID <?= $row['user_id']; ?>]
                                                </a>
                                            </td>
                                            <td><b><?= $row['trans_id']; ?></b></td>
                                            <td class="text-center"><?= $row['type'] ? '<span class="badge bg-info">' . htmlspecialchars($row['type']) . '</span>' : '-'; ?></td>
                                            <td class="text-right"><b><?= format_cash($row['amount']); ?></b> BDT</td>
                                            <td class="text-right"><b><?= format_currency($row['price']); ?></b></td>
                                            <td class="text-center"><?= display_invoice($row['status']); ?></td>
                                            <td class="text-center"><?= $row['created_at']; ?></td>
                                            <td class="text-center">
                                                <?php if ($row['status'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-info" title="Kiểm tra giao dịch từ API ZiniPay" onclick="checkZinipayTx(<?= $row['id']; ?>)">
                                                        <i class="ri-search-eye-line me-1"></i> <?= __('Kiểm tra'); ?>
                                                    </button>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="8">
                                            <div class="float-right">
                                                <?= __('Paid:'); ?> <strong style="color:red;"><?= format_currency($CMSNT->get_row(" SELECT SUM(`price`) FROM `payment_zinipay` WHERE $where AND `status` = 1 ")['SUM(`price`)']); ?></strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= $limit; ?> of <?= format_cash($totalDatatable); ?> Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var button = document.getElementById('open-card-config');
        var card = document.getElementById('card-config');
        button.addEventListener('click', function() {
            if (card.style.display === 'none' || card.style.display === '') {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
<script>
    CKEDITOR.replace("zinipay_notice");
</script>
<script>
    function regenZinipaySecret() {
        if (!confirm('Tạo lại mã bí mật mới? Sau khi tạo, bạn PHẢI cập nhật lại URL callback trong Dashboard ZiniPay, nếu không webhook sẽ bị từ chối.')) {
            return;
        }
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '';
        var i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'RegenerateSecret';
        i.value = '1';
        f.appendChild(i);
        document.body.appendChild(f);
        f.submit();
    }

    function checkZinipayTx(id) {
        if (!confirm('Kiểm tra trạng thái giao dịch này từ API ZiniPay?\nNếu giao dịch đã thanh toán, tiền sẽ được cộng cho user.')) {
            return;
        }
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '';
        var i1 = document.createElement('input');
        i1.type = 'hidden';
        i1.name = 'CheckTransaction';
        i1.value = '1';
        f.appendChild(i1);
        var i2 = document.createElement('input');
        i2.type = 'hidden';
        i2.name = 'id';
        i2.value = id;
        f.appendChild(i2);
        document.body.appendChild(f);
        f.submit();
    }
</script>
<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }
</script>
