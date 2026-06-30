<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa thành viên'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
if (checkPermission($getUser['admin'], 'edit_user') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_GET['id'])) {
    $CMSNT = new DB();
    $id = check_string($_GET['id']);
    $user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$id]);
    if (!$user) {
        die('<script type="text/javascript">if(!alert("' . __('User không tồn tại trong hệ thống') . '")){window.history.back();}</script>');
    }
    if ($getUser['admin'] != 99999 && $user['admin'] == 99999) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    // submit form edit
    if (isset($_POST['btnSaveUser'])) {

        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if (check_string($_POST['discount']) > 100) {
            die('<script type="text/javascript">if(!alert("' . __('Chiết khấu giảm giá không được lớn hơn 100') . '")){window.history.back().location.reload();}</script>');
        }
        if (check_string($_POST['admin']) != $user['admin']) {
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Thay đổi quyền Admin cho thành viên %s[%s] từ %s -> %s.'), $user['username'], $user['id'], $user['admin'], check_string($_POST['admin']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => sprintf(__("Bạn được Admin %s thay đổi quyền Admin."), $getUser['username'])
            ]);
        }
        if (check_string($_POST['ctv']) != $user['ctv']) {
            $ctvStatusOld = $user['ctv'] == 1 ? __('CTV') : __('Không phải CTV');
            $ctvStatusNew = check_string($_POST['ctv']) == 1 ? __('CTV') : __('Không phải CTV');
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Thay đổi trạng thái CTV cho thành viên %s[%s] từ %s -> %s.'), $user['username'], $user['id'], $ctvStatusOld, $ctvStatusNew)
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => sprintf(__("Bạn được Admin %s thay đổi trạng thái CTV."), $getUser['username'])
            ]);
        }
        if ($_POST['discount'] != $user['discount']) {
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Thay đổi chiết khấu thành viên %s từ %s%% sang %s%%.'), $user['username'], $user['discount'], check_string($_POST['discount']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => 'Bạn được Admin ' . $getUser['username'] . ' thay đổi chiết khấu.'
            ]);
        }
        if ($_POST['username'] != $user['username']) {
            if (validate_alphanumeric($_POST['username']) === false) {
                die('<script type="text/javascript">if(!alert("' . __('Tên đăng nhập không hợp lệ') . '")){window.history.back().location.reload();}</script>');
            }
            if ($CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `username` = ? AND `id` != ? ", [check_string($_POST['username']), $user['id']])) {
                die('<script type="text/javascript">if(!alert("' . __('Tên đăng nhập này đã có người sử dụng') . '")){window.history.back().location.reload();}</script>');
            }
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Change Username %s[%s] | %s -> %s.'), $user['username'], $user['id'], $user['username'], check_string($_POST['username']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('Bạn được Admin thay đổi Username.')
            ]);
        }
        if ($_POST['email'] != $user['email']) {
            if (validate_email($_POST['email']) === false) {
                die('<script type="text/javascript">if(!alert("' . __('Địa chỉ Email không hợp lệ') . '")){window.history.back().location.reload();}</script>');
            }
            if ($CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `email` = ? AND `id` != ? ", [check_string($_POST['email']), $user['id']])) {
                die('<script type="text/javascript">if(!alert("' . __('Địa chỉ Email này đã có người sử dụng') . '")){window.history.back().location.reload();}</script>');
            }
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Change Email %s[%s] | %s -> %s.'), $user['username'], $user['id'], $user['email'], check_string($_POST['email']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('Bạn được Admin thay đổi Email.')
            ]);
        }
        if ($_POST['admin'] == 99999 && $user['admin'] != 99999) {
            die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back().location.reload();}</script>');
        }
        if (!empty($_POST['password'])) {
            $password_result = validate_password($_POST['password'], 6, 50);
            if (!$password_result['success']) {
                die('<script type="text/javascript">if(!alert("' . __('Mật khẩu không hợp lệ: ') . $password_result['message'] . '")){window.history.back().location.reload();}</script>');
            }
            $CMSNT->update('users', [
                'password' => TypePassword($password_result['password'])
            ], "`id` = ?", [$user['id']]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('Bạn được Admin thay đổi mật khẩu.')
            ]);
        }
        // Xử lý lưu thông tin VAT nếu có
        $vat_info_json = null;
        if (isset($_POST['vat_type']) && !empty($_POST['vat_type']) && isset($_POST['vat_name']) && !empty($_POST['vat_name'])) {
            $vat_data = [
                'vat_type' => check_string($_POST['vat_type']),
                'vat_name' => check_string($_POST['vat_name']),
                'vat_address' => check_string($_POST['vat_address'] ?? ''),
                'vat_tax_code' => check_string($_POST['vat_tax_code'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            // Thêm CCCD nếu là personal và có giá trị
            if (check_string($_POST['vat_type']) == 'personal' && isset($_POST['vat_cccd']) && !empty($_POST['vat_cccd'])) {
                $vat_data['vat_cccd'] = check_string($_POST['vat_cccd']);
            }
            $vat_info_json = json_encode($vat_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $updateData = [
            'username'  => check_string($_POST['username']),
            'email'     => check_string($_POST['email']),
            'status_2fa'    => check_string($_POST['status_2fa']),
            'status_otp_mail'   => check_string($_POST['status_otp_mail']),
            'token'     => check_string($_POST['token']),
            'api_key'   => check_string($_POST['api_key']),
            'phone'     => check_string($_POST['phone']),
            'gender'    => check_string($_POST['gender']),
            'admin'     => check_string($_POST['admin']),
            'ctv'       => check_string($_POST['ctv']),
            'discount'  => check_string($_POST['discount']),
            'ref_ck'    => check_string($_POST['ref_ck']),
            'banned'    => check_string($_POST['banned']),
            'ref_id'    => check_string($_POST['ref_id'])
        ];

        // Cập nhật prefix_fullname nếu đang dùng chế độ fullname_transfer
        if ($CMSNT->site('bank_recharge_type') == 'fullname_transfer' && isset($_POST['prefix_fullname'])) {
            $prefix_fullname = trim(check_string($_POST['prefix_fullname']));
            // Kiểm tra trùng prefix_fullname với user khác (chỉ khi không rỗng)
            if (!empty($prefix_fullname) && $prefix_fullname !== ($user['prefix_fullname'] ?? '')) {
                $existingUser = $CMSNT->get_row_safe("SELECT `id`, `username` FROM `users` WHERE `prefix_fullname` = ? AND `id` != ?", [$prefix_fullname, $user['id']]);
                if ($existingUser) {
                    die('<script type="text/javascript">if(!alert("' . sprintf(__('Họ và tên chuyển khoản "%s" đã được sử dụng bởi thành viên %s. Vui lòng nhập tên khác.'), htmlspecialchars($prefix_fullname, ENT_QUOTES, 'UTF-8'), $existingUser['username']) . '")){window.history.back().location.reload();}</script>');
                }
            }
            $updateData['prefix_fullname'] = $prefix_fullname;
        }

        if ($vat_info_json !== null) {
            $updateData['vat_info'] = $vat_info_json;
        }

        $CMSNT->update('users', $updateData, "`id` = ?", [$user['id']]);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Cập nhật thông tin thành viên %s[%s].'), $user['username'], $user['id'])
        ]);
        $CMSNT->insert("logs", [
            'user_id'       => $user['id'],
            'createdate'    => gettime(),
            'action'        => __('You are changed information by Admin.')
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Cập nhật thông tin thành viên %s[%s].'), $user['username'], $user['id']), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Cập nhật thông tin thành công!') . '")){window.history.back().location.reload();}</script>');
    }


    if (isset($_POST['cong_tien'])) {
        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if ($_POST['amount'] <= 0) {
            die('<script type="text/javascript">if(!alert("' . __('Amount không hợp lệ !') . '")){window.history.back().location.reload();}</script>');
        }

        $amount = check_string($_POST['amount']);
        $DBUser = new users();


        // CỘNG TIỀN VÀO VÍ CHÍNH
        if ($_POST['wallet'] == 1) {
            $reason = '[' . __('CỘNG TIỀN VÀO VÍ CHÍNH') . '] ' . check_string($_POST['reason']);
            $DBUser->AddCredits($id, $amount, $reason, 'admin_add_' . uniqid());
        }
        // HOÀN TIỀN VÀO VÍ CHÍNH
        if ($_POST['wallet'] == 2) {
            $reason = '[' . __('HOÀN TIỀN') . '] ' . check_string($_POST['reason']);
            $DBUser->RefundCredits($id, $amount, $reason, 'admin_refund_' . uniqid());
        }
        // CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI
        if ($_POST['wallet'] == 3) {
            $reason = '[' . __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI') . '] ' . check_string($_POST['reason']);
            $isDebit = $CMSNT->cong('users', 'debit', $amount, " `id` = ? ", [$id]);
            if ($isDebit) {
                $DBUser->AddCredits($id, $amount, $reason, 'admin_add_' . uniqid());
            }
        }
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Cộng %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason)
        ]);


        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('[Admin] Cộng %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        admin_msg_success(__('Cộng số dư thành công!'), "", 1000);
    }

    if (isset($_POST['tru_tien'])) {
        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if ($_POST['amount'] <= 0) {
            die('<script type="text/javascript">if(!alert("' . __('Amount không hợp lệ !') . '")){window.history.back().location.reload();}</script>');
        }

        $amount = check_string($_POST['amount']);

        if ($_POST['wallet'] == 2) {
            $CMSNT->tru('users', 'debit', $amount, " `id` = ? ", [$id]);
            $reason = '[VÍ GHI NỢ] ' . check_string($_POST['reason']);
        } else {
            if (getRowRealtime('users', $id, 'money') < $amount) {
                die('<script type="text/javascript">if(!alert("' . __('Số dư bạn trừ vượt quá số dư khả dụng của thành viên') . '")){window.history.back().location.reload();}</script>');
            }
            $reason = '[VÍ CHÍNH] ' . check_string($_POST['reason']);
            /* Xử lý trừ tiền */
            $DBUser = new users();
            $DBUser->RemoveCredits($id, $amount, $reason, 'admin_remove_' . uniqid());
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Trừ %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason)
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('[Admin] Trừ %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        admin_msg_success(__("Trừ số dư thành công!"), "", 1000);
    }
}

// Lấy thông tin VAT hiện tại của user
$vat_info = null;
if (!empty($user['vat_info'])) {
    $vat_info = json_decode($user['vat_info'], true);
    if (!$vat_info || empty($vat_info['vat_type']) || empty($vat_info['vat_name'])) {
        $vat_info = null;
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h1 class="page-title fw-semibold fs-20 mb-0">
                    <a class="btn btn-outline-dark btn-wave btn-sm me-2" href="<?= base_url_admin('users'); ?>">
                        <i class="ri-arrow-left-line"></i>
                    </a>
                    <?= __('Chỉnh sửa thành viên'); ?>
                </h1>
                <p class="mb-0 text-muted fs-12 ms-5">
                    <i class="ri-user-line me-1"></i><?= $user['username']; ?>
                    <span class="badge bg-<?= $user['banned'] == 0 ? 'success' : 'danger'; ?>-transparent ms-2">
                        <?= $user['banned'] == 0 ? __('Active') : __('Banned'); ?>
                    </span>
                </p>
            </div>
            <div class="btn-group" role="group">
                <button type="button" data-bs-toggle="modal" data-bs-target="#modal-addCredit"
                    class="btn btn-success btn-wave">
                    <i class="ri-add-line me-1"></i><?= __('Cộng số dư'); ?>
                </button>
                <button type="button" data-bs-toggle="modal" data-bs-target="#modal-removeCredit"
                    class="btn btn-danger btn-wave">
                    <i class="ri-subtract-line me-1"></i><?= __('Trừ số dư'); ?>
                </button>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-wave dropdown-toggle"
                        data-bs-toggle="dropdown">
                        <i class="ri-more-line"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= base_url_admin('logs&user_id=' . $user['id']); ?>"
                                target="_blank">
                                <i class="ri-history-line me-2"></i><?= __('Nhật ký hoạt động'); ?>
                            </a></li>
                        <li><a class="dropdown-item" href="<?= base_url_admin('transactions&user_id=' . $user['id']); ?>"
                                target="_blank">
                                <i class="ri-exchange-line me-2"></i><?= __('Biến động số dư'); ?>
                            </a></li>
                        <li><a class="dropdown-item" href="<?= base_url_admin('product-orders&buyer=' . $user['id']); ?>"
                                target="_blank">
                                <i class="ri-shopping-cart-line me-2"></i><?= __('Đơn hàng'); ?>
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- User Statistics Card -->
            <div class="col-12 mb-4">
                <div class="card custom-card border">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-primary-transparent me-3">
                                        <i class="ri-wallet-3-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Ví chính'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-primary"><?= format_currency($user['money']); ?>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-success-transparent me-3">
                                        <i class="ri-money-dollar-circle-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Tổng tiền nạp'); ?>
                                            <!-- Nút reset tổng nạp cho user hiện tại -->
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-wave ms-1 py-0 px-1" onclick="confirmResetTotalMoney()" title="<?= __('Reset tổng nạp'); ?>">
                                                <i class="ri-refresh-line fs-11"></i>
                                            </button>
                                        </p>
                                        <h6 class="mb-0 fw-semibold text-success" id="total-money-display">
                                            <?= format_currency($user['total_money']); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-warning-transparent me-3">
                                        <i class="ri-shopping-cart-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Đã sử dụng'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-warning">
                                            <?= format_currency($user['total_money'] - $user['money']); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-danger-transparent me-3">
                                        <i class="fa-solid fa-credit-card fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Số tiền nợ'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-danger"><?= format_currency($user['debit']); ?>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Form -->
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <!-- Navigation Tabs -->
                            <ul class="nav nav-tabs nav-tabs-header mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#basic-info"
                                        aria-selected="true">
                                        <i class="ri-user-line me-2"></i><?= __('Thông tin cơ bản'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#security-info"
                                        aria-selected="false">
                                        <i class="ri-shield-user-line me-2"></i><?= __('Bảo mật'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#permissions"
                                        aria-selected="false">
                                        <i class="ri-admin-line me-2"></i><?= __('Quyền hạn'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#system-info"
                                        aria-selected="false">
                                        <i class="ri-computer-line me-2"></i><?= __('Thông tin hệ thống'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#vat-info"
                                        aria-selected="false">
                                        <i class="ri-receipt-line me-2"></i><?= __('Thông tin xuất hóa đơn VAT'); ?>
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content">
                                <!-- Basic Information Tab -->
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Username'); ?> <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-user-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['username']; ?>" name="username" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Email <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-mail-line text-muted"></i>
                                                    </span>
                                                    <input type="email" class="form-control"
                                                        value="<?= $user['email']; ?>" name="email" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Phone'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-phone-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="<?= $user['phone']; ?>"
                                                        name="phone">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Giới tính'); ?></label>
                                                <select class="form-control" name="gender">
                                                    <option <?= $user['gender'] == 'Male' ? 'selected' : ''; ?>
                                                        value="Male">
                                                        <i class="ri-men-line"></i> <?= __('Nam'); ?> (Male)
                                                    </option>
                                                    <option <?= $user['gender'] == 'Female' ? 'selected' : ''; ?>
                                                        value="Female">
                                                        <i class="ri-women-line"></i> <?= __('Nữ'); ?> (Female)
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Người giới thiệu'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-user-shared-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" name="ref_id"
                                                        value="<?= $user['ref_id']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Chiết khấu giảm giá'); ?>
                                                    (%)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-percent-line text-muted"></i>
                                                    </span>
                                                    <input type="number" class="form-control"
                                                        value="<?= $user['discount']; ?>" name="discount" min="0"
                                                        max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Tùy chỉnh Phần trăm hoa hồng tiếp thị liên kết'); ?>
                                                    (%)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-percent-line text-muted"></i>
                                                    </span>
                                                    <input type="number" class="form-control"
                                                        value="<?= $user['ref_ck']; ?>" name="ref_ck" min="0"
                                                        max="100" step="0.01">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <span class="text-muted">
                                                    <?= __('Mặc định sẽ sử dụng phần trăm hoa hồng mà bạn đã cấu hình trong phần Affiliate Program.'); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($CMSNT->site('bank_recharge_type') == 'fullname_transfer'): ?>
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold"><?= __('Họ và tên chuyển khoản (Prefix Fullname)'); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i class="ri-bank-card-line text-muted"></i>
                                                        </span>
                                                        <input type="text" class="form-control" name="prefix_fullname"
                                                            value="<?= htmlspecialchars($user['prefix_fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            placeholder="<?= __('VD: NGUYEN VAN A'); ?>">
                                                    </div>
                                                    <div class="form-text">
                                                        <i class="ri-information-line me-1"></i><?= __('Họ và tên dùng làm nội dung chuyển khoản khi nạp tiền qua ngân hàng. Bỏ trống nếu muốn user tự nhập.'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Security Information Tab -->
                                <div class="tab-pane fade" id="security-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Token <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-key-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control" id="token_input"
                                                        value="<?= $user['token']; ?>" name="token" required>
                                                    <button type="button" id="show_token"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleTokenVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text text-warning">
                                                    <i
                                                        class="ri-alert-line me-1"></i><?= __('Bảo mật thông tin này vì kẻ xấu có thể thực hiện đăng nhập tài khoản bằng Token'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">API Key <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-key-2-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control"
                                                        value="<?= $user['api_key']; ?>" name="api_key" id="api_key_input"
                                                        required>
                                                    <button type="button" id="show_api_key"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleApiKeyVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text text-warning">
                                                    <i
                                                        class="ri-alert-line me-1"></i><?= __('Bảo mật thông tin này vì kẻ xấu có thể mua hàng thông qua API KEY'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Mật khẩu mới'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-lock-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control"
                                                        placeholder="<?= __('Nhập mật khẩu mới'); ?>" name="password">
                                                </div>
                                                <div class="form-text">
                                                    <i
                                                        class="ri-information-line me-1"></i><?= __('Bỏ trống nếu không muốn thay đổi mật khẩu'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Secret Key Google 2FA</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-shield-keyhole-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control" id="key_2fa_input"
                                                        value="<?= $user['SecretKey_2fa']; ?>" disabled>
                                                    <button type="button" id="show_key_2fa"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleKey2FAVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between mt-2">
                                                    <div class="form-text text-danger mb-0">
                                                        <i class="ri-error-warning-line me-1"></i><?= __('Lộ thông tin này có thể khiến kẻ xấu bỏ qua bước xác minh 2FA.'); ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-wave ms-2" onclick="confirmReset2FA()" title="<?= __('Reset Secret Key'); ?>">
                                                        <i class="ri-refresh-line me-1"></i><?= __('Reset'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Trạng thái Google 2FA</label>
                                                <select class="form-select" name="status_2fa">
                                                    <option <?= $user['status_2fa'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-checkbox-circle-line"></i> Bật (ON)
                                                    </option>
                                                    <option <?= $user['status_2fa'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-close-circle-line"></i> Tắt (OFF)
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Xác minh OTP'); ?></label>
                                                <select class="form-select" name="status_otp_mail">
                                                    <option <?= $user['status_otp_mail'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-checkbox-circle-line"></i> <?= __('Bật'); ?>
                                                    </option>
                                                    <option <?= $user['status_otp_mail'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-close-circle-line"></i> <?= __('Tắt'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Permissions Tab -->
                                <div class="tab-pane fade" id="permissions" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Admin Role'); ?> <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" name="admin">
                                                    <option value="0" <?= $user['admin'] == 0 ? 'selected' : ''; ?>>
                                                        <i class="ri-user-line"></i> User (Khách hàng)
                                                    </option>
                                                    <?php foreach ($CMSNT->get_list(" SELECT * FROM `admin_role` ") as $role): ?>
                                                        <option value="<?= $role['id']; ?>"
                                                            <?= $user['admin'] == $role['id'] ? 'selected' : ''; ?>>
                                                            <i class="ri-admin-line"></i> <?= $role['name']; ?> (Admin Role)
                                                        </option>
                                                    <?php endforeach ?>
                                                    <?php if ($user['admin'] == 99999): ?>
                                                        <option value="99999"
                                                            <?= $user['admin'] == 99999 ? 'selected' : ''; ?>>
                                                            <i class="ri-vip-crown-line"></i> Administrator (Admin Root)
                                                        </option>
                                                    <?php endif ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('CTV Status'); ?></label>
                                                <select class="form-select" name="ctv">
                                                    <option value="0" <?= $user['ctv'] == 0 ? 'selected' : ''; ?>>
                                                        <i class="ri-user-line"></i> <?= __('Không phải CTV'); ?> (Normal User)
                                                    </option>
                                                    <option value="1" <?= $user['ctv'] == 1 ? 'selected' : ''; ?>>
                                                        <i class="ri-team-line"></i> <?= __('CTV'); ?> (Collaborator)
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Trạng thái tài khoản</label>
                                                <select class="form-select" name="banned">
                                                    <option <?= $user['banned'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-checkbox-circle-line"></i> <span
                                                            class="text-success">Hoạt động (Active)</span>
                                                    </option>
                                                    <option <?= $user['banned'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-forbid-line"></i> <span class="text-danger">Bị cấm
                                                            (Banned)</span>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- VAT Information Tab -->
                                <div class="tab-pane fade" id="vat-info" role="tabpanel">
                                    <div class="alert alert-info border-info mb-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-information-line me-2 fs-16"></i>
                                                <div>
                                                    <strong><?= __('Thông tin xuất hóa đơn VAT'); ?></strong><br>
                                                    <small><?= __('Nếu bạn muốn bắt buộc khách hàng phải nhập thông tin xuất hóa đơn trước khi sử dụng website, cấu hình trong Cài đặt -> Cài đặt chung -> ON/OFF bắt buộc nhập thông tin để xuất VAT.'); ?></small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="copyVatInfoBtn" title="<?= __('Sao chép thông tin xuất hóa đơn'); ?>">
                                                <i class="ri-file-copy-line me-1"></i><?= __('Sao chép thông tin'); ?>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabs Navigation -->
                                    <ul class="nav nav-tabs nav-tabs-header mb-4" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link <?= !$vat_info || (isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal') ? 'active' : ''; ?>" data-bs-toggle="tab" role="tab" href="#vat-personal" aria-selected="true">
                                                <i class="fa-solid fa-user me-2"></i><?= __('Cá nhân'); ?>
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link <?= $vat_info && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'business' ? 'active' : ''; ?>" data-bs-toggle="tab" role="tab" href="#vat-business" aria-selected="false">
                                                <i class="fa-solid fa-building me-2"></i><?= __('Doanh nghiệp'); ?>
                                            </a>
                                        </li>
                                    </ul>

                                    <!-- VAT Tabs Content -->
                                    <div class="tab-content" id="vatTabsContent">
                                        <!-- Tab Cá nhân -->
                                        <div class="tab-pane fade <?= !$vat_info || (isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal') ? 'show active' : ''; ?>" id="vat-personal" role="tabpanel" aria-labelledby="personal-tab">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Họ và tên'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="ri-user-line text-muted"></i>
                                                            </span>
                                                            <input type="text" class="form-control" name="vat_name"
                                                                value="<?= htmlspecialchars(isset($vat_info['vat_name']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_name'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="<?= __('Nhập họ và tên'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Địa chỉ'); ?></label>
                                                        <textarea class="form-control" name="vat_address" rows="3"
                                                            placeholder="<?= __('Nhập địa chỉ'); ?>"><?= htmlspecialchars(isset($vat_info['vat_address']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_address'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Mã số thuế cá nhân'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="ri-file-text-line text-muted"></i>
                                                            </span>
                                                            <input type="text" class="form-control" name="vat_tax_code"
                                                                value="<?= htmlspecialchars(isset($vat_info['vat_tax_code']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_tax_code'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="<?= __('Nhập mã số thuế cá nhân'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('CCCD (Căn cước công dân)'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="ri-id-card-line text-muted"></i>
                                                            </span>
                                                            <input type="text" class="form-control" name="vat_cccd"
                                                                value="<?= htmlspecialchars(isset($vat_info['vat_cccd']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_cccd'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="<?= __('Nhập số CCCD'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="vat_type" value="personal" id="vat_type_personal">
                                        </div>

                                        <!-- Tab Doanh nghiệp -->
                                        <div class="tab-pane fade <?= $vat_info && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'business' ? 'show active' : ''; ?>" id="vat-business" role="tabpanel" aria-labelledby="business-tab">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Tên công ty'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="ri-building-line text-muted"></i>
                                                            </span>
                                                            <input type="text" class="form-control" name="vat_name"
                                                                value="<?= htmlspecialchars(isset($vat_info['vat_name']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_name'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="<?= __('Nhập tên công ty'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Mã số thuế'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="ri-file-text-line text-muted"></i>
                                                            </span>
                                                            <input type="text" class="form-control" name="vat_tax_code"
                                                                value="<?= htmlspecialchars(isset($vat_info['vat_tax_code']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_tax_code'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="<?= __('Nhập mã số thuế'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold"><?= __('Địa chỉ công ty'); ?></label>
                                                        <textarea class="form-control" name="vat_address" rows="3"
                                                            placeholder="<?= __('Nhập địa chỉ công ty'); ?>"><?= htmlspecialchars(isset($vat_info['vat_address']) && isset($vat_info['vat_type']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_address'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="vat_type" value="business" id="vat_type_business">
                                        </div>
                                    </div>
                                </div>

                                <!-- System Information Tab -->
                                <div class="tab-pane fade" id="system-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Địa chỉ IP đăng nhập'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-wifi-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="<?= $user['ip']; ?>"
                                                        disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Thiết bị đăng nhập'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-computer-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['device']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Ngày đăng ký'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-calendar-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['create_date']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Đăng nhập gần nhất'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-time-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['update_date']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <a class="btn btn-outline-secondary btn-wave" href="<?= base_url_admin('users'); ?>">
                                    <i class="ri-arrow-left-line me-2"></i><?= __('Quay lại'); ?>
                                </a>
                                <button type="submit" name="btnSaveUser" class="btn btn-primary btn-wave">
                                    <i class="ri-save-line me-2"></i><?= __('Lưu thay đổi'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Logs Card -->
            <div class="col-xl-6 mb-4">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title text-uppercase">
                            <i class="ri-history-line me-2"></i><?= __('Nhật ký hoạt động gần nhất'); ?>
                        </div>
                        <a href="<?= base_url_admin('logs&user_id=' . $user['id']); ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i><?= __('Xem tất cả'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th class="fw-semibold"><?= __('Hành động'); ?></th>
                                        <th class="fw-semibold"><?= __('Thời gian'); ?></th>
                                        <th class="fw-semibold">IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentLogs = $CMSNT->get_list_safe("SELECT * FROM `logs` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 20", [$user['id']]);
                                    if (empty($recentLogs)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="ri-folder-open-line fs-24 d-block mb-2"></i>
                                                <?= __('Chưa có hoạt động nào'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <div class="avatar avatar-xs bg-primary-transparent me-2 mt-1">
                                                            <i class="ri-file-text-line fs-10"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <small class="text-muted"><?= $log['action']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark fs-10" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= timeAgo(strtotime($log['createdate'])); ?>">
                                                        <?= date('d/m H:i', strtotime($log['createdate'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger-transparent fs-10"><?= $log['ip']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Balance Transactions Card -->
            <div class="col-xl-6 mb-4">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title text-uppercase">
                            <i class="ri-exchange-line me-2"></i><?= __('Biến động số dư gần nhất'); ?>
                        </div>
                        <a href="<?= base_url_admin('transactions&user_id=' . $user['id']); ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i><?= __('Xem tất cả'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th class="fw-semibold"><?= __('Thay đổi'); ?></th>
                                        <th class="fw-semibold"><?= __('Số dư sau'); ?></th>
                                        <th class="fw-semibold"><?= __('Thời gian'); ?></th>
                                        <th class="fw-semibold"><?= __('Lý do'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentTransactions = $CMSNT->get_list_safe("SELECT * FROM `dongtien` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 20", [$user['id']]);
                                    if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="ri-coins-line fs-24 d-block mb-2"></i>
                                                <?= __('Chưa có giao dịch nào'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <?php if (($transaction['sotiensau'] - $transaction['sotientruoc']) > 0): ?>
                                                        <span class="badge bg-success-transparent">
                                                            <i
                                                                class="ri-add-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php elseif (($transaction['sotientruoc'] - $transaction['sotiensau']) > 0): ?>
                                                        <span class="badge bg-danger-transparent">
                                                            <i
                                                                class="ri-subtract-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-transparent">
                                                            <i
                                                                class="ri-equal-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-primary-transparent"><?= format_currency($transaction['sotiensau']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark fs-10" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= timeAgo(strtotime($transaction['thoigian'])); ?>">
                                                        <?= date('d/m H:i', strtotime($transaction['thoigian'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted" title="<?= $transaction['noidung']; ?>">
                                                        <?= mb_strlen($transaction['noidung']) > 30 ? mb_substr($transaction['noidung'], 0, 30) . '...' : $transaction['noidung']; ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Enhanced JavaScript for improved UI -->
<script>
    // Toggle password visibility functions with improved styling
    function toggleTokenVisibility() {
        var input = document.getElementById('token_input');
        var button = document.getElementById('show_token');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleApiKeyVisibility() {
        var input = document.getElementById('api_key_input');
        var button = document.getElementById('show_api_key');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleKey2FAVisibility() {
        var input = document.getElementById('key_2fa_input');
        var button = document.getElementById('show_key_2fa');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    // Tab navigation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade animation to tabs
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function() {
                const target = document.querySelector(this.getAttribute('href'));
                target.style.opacity = '0';
                setTimeout(function() {
                    target.style.transition = 'opacity 0.3s ease-in-out';
                    target.style.opacity = '1';
                }, 50);
            });
        });

        // Xử lý chuyển tab VAT để cập nhật hidden input vat_type
        const vatTabLinks = document.querySelectorAll('#vat-info [data-bs-toggle="tab"]');
        if (vatTabLinks.length > 0) {
            // Khởi tạo hidden input ban đầu
            const activeVatTab = document.querySelector('#vat-info .nav-link.active');
            if (activeVatTab) {
                const targetId = activeVatTab.getAttribute('href');
                if (targetId === '#vat-personal') {
                    const personalInput = document.getElementById('vat_type_personal');
                    if (personalInput) personalInput.disabled = false;
                } else if (targetId === '#vat-business') {
                    const businessInput = document.getElementById('vat_type_business');
                    if (businessInput) businessInput.disabled = false;
                }
            }

            vatTabLinks.forEach(function(tabLink) {
                tabLink.addEventListener('shown.bs.tab', function() {
                    const targetId = this.getAttribute('href');
                    // Ẩn tất cả hidden input
                    const personalInput = document.getElementById('vat_type_personal');
                    const businessInput = document.getElementById('vat_type_business');
                    if (personalInput) personalInput.disabled = true;
                    if (businessInput) businessInput.disabled = true;

                    // Kích hoạt hidden input tương ứng với tab đang active
                    if (targetId === '#vat-personal' && personalInput) {
                        personalInput.disabled = false;
                    } else if (targetId === '#vat-business' && businessInput) {
                        businessInput.disabled = false;
                    }
                });
            });
        }

        // Xử lý sao chép thông tin xuất hóa đơn VAT
        const copyVatInfoBtn = document.getElementById('copyVatInfoBtn');
        if (copyVatInfoBtn) {
            copyVatInfoBtn.addEventListener('click', function() {
                // Xác định tab nào đang active
                const activeVatTab = document.querySelector('#vat-info .nav-link.active');
                if (!activeVatTab) return;

                const targetId = activeVatTab.getAttribute('href');
                let vatText = '';

                if (targetId === '#vat-personal') {
                    // Tab Cá nhân
                    const vatName = document.querySelector('#vat-personal input[name="vat_name"]')?.value || '';
                    const vatAddress = document.querySelector('#vat-personal textarea[name="vat_address"]')?.value || '';
                    const vatTaxCode = document.querySelector('#vat-personal input[name="vat_tax_code"]')?.value || '';
                    const vatCccd = document.querySelector('#vat-personal input[name="vat_cccd"]')?.value || '';

                    vatText = 'Cá Nhân\n';
                    vatText += '- Họ và Tên: ' + vatName + '\n';
                    vatText += '- Địa chỉ: ' + vatAddress + '\n';
                    vatText += '- Mã số thuế cá nhân: ' + vatTaxCode + '\n';
                    vatText += '- CCCD (Căn cước công dân): ' + vatCccd;

                } else if (targetId === '#vat-business') {
                    // Tab Doanh nghiệp
                    const vatName = document.querySelector('#vat-business input[name="vat_name"]')?.value || '';
                    const vatTaxCode = document.querySelector('#vat-business input[name="vat_tax_code"]')?.value || '';
                    const vatAddress = document.querySelector('#vat-business textarea[name="vat_address"]')?.value || '';

                    vatText = 'Doanh nghiệp\n';
                    vatText += '- Tên công ty: ' + vatName + '\n';
                    vatText += '- Mã số thuế: ' + vatTaxCode + '\n';
                    vatText += '- Địa chỉ công ty: ' + vatAddress;
                }

                // Sao chép vào clipboard
                if (vatText.trim()) {
                    navigator.clipboard.writeText(vatText).then(function() {
                        // Hiển thị thông báo thành công
                        const originalText = copyVatInfoBtn.innerHTML;
                        copyVatInfoBtn.innerHTML = '<i class="ri-check-line me-1"></i><?= __('Đã sao chép'); ?>';
                        copyVatInfoBtn.classList.remove('btn-outline-primary');
                        copyVatInfoBtn.classList.add('btn-success');

                        setTimeout(function() {
                            copyVatInfoBtn.innerHTML = originalText;
                            copyVatInfoBtn.classList.remove('btn-success');
                            copyVatInfoBtn.classList.add('btn-outline-primary');
                        }, 2000);
                    }).catch(function(err) {
                        // Fallback cho trình duyệt không hỗ trợ clipboard API
                        const textArea = document.createElement('textarea');
                        textArea.value = vatText;
                        textArea.style.position = 'fixed';
                        textArea.style.opacity = '0';
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            const originalText = copyVatInfoBtn.innerHTML;
                            copyVatInfoBtn.innerHTML = '<i class="ri-check-line me-1"></i><?= __('Đã sao chép'); ?>';
                            copyVatInfoBtn.classList.remove('btn-outline-primary');
                            copyVatInfoBtn.classList.add('btn-success');

                            setTimeout(function() {
                                copyVatInfoBtn.innerHTML = originalText;
                                copyVatInfoBtn.classList.remove('btn-success');
                                copyVatInfoBtn.classList.add('btn-outline-primary');
                            }, 2000);
                        } catch (err) {
                            alert('<?= __('Không thể sao chép. Vui lòng sao chép thủ công.'); ?>');
                        }
                        document.body.removeChild(textArea);
                    });
                } else {
                    alert('<?= __('Không có thông tin để sao chép.'); ?>');
                }
            });
        }
    });
</script>


<script>
    function confirmReset2FA() {
        Swal.fire({
            title: '<?= __('Xác nhận Reset 2FA'); ?>',
            text: '<?= __('Bạn có chắc chắn muốn reset Secret Key Google 2FA cho thành viên này? Thao tác này sẽ xóa Secret Key và tắt 2FA.'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= __('Đồng ý Reset'); ?>',
            cancelButtonText: '<?= __('Hủy'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'reset_2fa_key',
                        id: '<?= $user['id']; ?>'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: '<?= __('Đang xử lý...'); ?>',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                title: '<?= __('Thành công!'); ?>',
                                text: response.msg,
                                icon: 'success'
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: '<?= __('Lỗi!'); ?>',
                                text: response.msg,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: '<?= __('Lỗi!'); ?>',
                            text: '<?= __('Có lỗi xảy ra, vui lòng thử lại.'); ?>',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }
</script>

<!-- Xử lý reset tổng nạp cho user cụ thể -->
<script>
    function confirmResetTotalMoney() {
        Swal.fire({
            title: '<?= __('Xác nhận Reset tổng nạp'); ?>',
            text: '<?= sprintf(__('Bạn có chắc chắn muốn reset tổng tiền nạp (total_money) của thành viên %s về 0?'), $user['username']); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= __('Đồng ý Reset'); ?>',
            cancelButtonText: '<?= __('Hủy'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'reset_total_money_user',
                        id: '<?= $user['id']; ?>'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: '<?= __('Đang xử lý...'); ?>',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                title: '<?= __('Thành công!'); ?>',
                                text: response.msg,
                                icon: 'success'
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: '<?= __('Lỗi!'); ?>',
                                text: response.msg,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: '<?= __('Lỗi!'); ?>',
                            text: '<?= __('Có lỗi xảy ra, vui lòng thử lại.'); ?>',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }
</script>

<?php
require_once(__DIR__ . '/footer.php');
?>

<div class="modal fade" id="modal-addCredit" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary-transparent">
                    <h6 class="modal-title fw-semibold" id="staticBackdropLabel2">
                        <i class="ri-add-circle-line me-2"></i><?= __('Cộng số dư'); ?>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="notice_add_money" class="alert alert-success alert-dismissible fade show"
                        style="display: none;" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-checkbox-circle-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('CỘNG TIỀN VÀO VÍ CHÍNH'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được cộng vào ví chính và cộng vào tổng nạp của thành viên.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="notice_refund" class="alert alert-info alert-dismissible fade show" style="display: none;"
                        role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-information-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('HOÀN TIỀN VÀO VÍ CHÍNH'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được hoàn vào ví chính nhưng KHÔNG cộng vào tổng nạp của thành viên.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="notice_credit_debt" class="alert alert-warning alert-dismissible fade show"
                        style="display: none;" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-error-warning-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được cộng vào ví chính nhưng KHÔNG cộng vào tổng nạp, đồng thời ghi nợ lại số tiền này. Số tiền nợ sẽ được trừ tự động khi thành viên nạp tiền.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Loại ví'); ?></label>
                            <select class="form-select" name="wallet" id="walletSelect">
                                <option value="1"><?= __('CỘNG TIỀN VÀO VÍ CHÍNH'); ?></option>
                                <option value="2"><?= __('HOÀN TIỀN VÀO VÍ CHÍNH'); ?></option>
                                <option value="3"><?= __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI'); ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Số tiền'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                                <input type="number" step="any" min="0" class="form-control" name="amount" id="amountInput"
                                    placeholder="<?= __('Nhập số tiền cần cộng'); ?>" required>
                                <span class="input-group-text"><?= getCurrencyNameDefault(); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Lý do'); ?></label>
                            <textarea class="form-control" name="reason" rows="3"
                                placeholder="<?= __('Nhập lý do cộng tiền (tùy chọn)'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 text-center">
                        <i class="ri-information-line me-1"></i>
                        <?= __('Sẽ thực hiện cộng'); ?> <span id="amountDisplay" class="fw-bold text-primary">0</span> <?= getCurrencyNameDefault(); ?>
                        <?= __('vào'); ?> <span id="walletDisplay"
                            class="fw-bold text-success"><?= __('VÍ CHÍNH'); ?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-hero btn-danger" data-bs-dismiss="modal"><i
                            class="fa fa-fw fa-times me-1"></i> <?= __('Close'); ?></button>
                    <button type="submit" name="cong_tien" class="btn btn-hero btn-success"><i
                            class="fa fa-fw fa-plus me-1"></i> <?= __('Submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Enhanced Modal JavaScript for Cộng số dư
    document.addEventListener("DOMContentLoaded", function() {
        // Modal Cộng tiền elements
        var selectWallet = document.getElementById('walletSelect');
        var amountInput = document.getElementById('amountInput');
        var amountDisplay = document.getElementById('amountDisplay');
        var walletDisplay = document.getElementById('walletDisplay');
        var noticeAddMoney = document.getElementById('notice_add_money');
        var noticeRefund = document.getElementById('notice_refund');
        var noticeCreditDebt = document.getElementById('notice_credit_debt');

        // Initialize displays if elements exist
        if (selectWallet && amountInput && amountDisplay && walletDisplay) {
            updateAmountDisplay();
            updateWalletDisplay();
            updateNoticeDisplay();

            // Event listeners
            amountInput.addEventListener('input', updateAmountDisplay);
            selectWallet.addEventListener('change', function() {
                updateWalletDisplay();
                updateNoticeDisplay();
            });

            function updateAmountDisplay() {
                var inputValue = amountInput.value;
                if (!inputValue || isNaN(inputValue)) {
                    amountDisplay.textContent = '0';
                    return;
                }
                var formattedAmount = formatNumber(inputValue);
                amountDisplay.textContent = formattedAmount;
            }

            function updateWalletDisplay() {
                walletDisplay.textContent = selectWallet.options[selectWallet.selectedIndex].text;
            }

            function updateNoticeDisplay() {
                if (!noticeAddMoney || !noticeRefund || !noticeCreditDebt) return;

                // Ẩn tất cả thông báo
                noticeAddMoney.style.display = 'none';
                noticeRefund.style.display = 'none';
                noticeCreditDebt.style.display = 'none';

                // Hiển thị thông báo tương ứng
                if (selectWallet.value === "1") {
                    noticeAddMoney.style.display = 'block';
                } else if (selectWallet.value === "2") {
                    noticeRefund.style.display = 'block';
                } else if (selectWallet.value === "3") {
                    noticeCreditDebt.style.display = 'block';
                }
            }

            function formatNumber(value) {
                return parseFloat(value).toLocaleString('vi-VN');
            }
        }

        // Modal Trừ tiền elements
        var selectWallet2 = document.getElementById('walletSelect2');
        var amountInput2 = document.getElementById('amountInput2');
        var amountDisplay2 = document.getElementById('amountDisplay2');
        var walletDisplay2 = document.getElementById('walletDisplay2');

        // Initialize displays for modal trừ tiền
        if (selectWallet2 && amountInput2 && amountDisplay2 && walletDisplay2) {
            updateAmountDisplay2();
            updateWalletDisplay2();

            // Event listeners for modal trừ tiền
            amountInput2.addEventListener('input', updateAmountDisplay2);
            selectWallet2.addEventListener('change', updateWalletDisplay2);

            function updateAmountDisplay2() {
                var inputValue = amountInput2.value;
                if (!inputValue || isNaN(inputValue)) {
                    amountDisplay2.textContent = '0';
                    return;
                }
                var formattedAmount = formatNumber(inputValue);
                amountDisplay2.textContent = formattedAmount;
            }

            function updateWalletDisplay2() {
                walletDisplay2.textContent = selectWallet2.options[selectWallet2.selectedIndex].text;
            }

            function formatNumber(value) {
                return parseFloat(value).toLocaleString('vi-VN');
            }
        }
    });
</script>

<div class="modal fade" id="modal-removeCredit" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-danger-transparent">
                    <h6 class="modal-title fw-semibold" id="staticBackdropLabel2">
                        <i class="ri-subtract-line me-2"></i><?= __('Trừ số dư'); ?>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-error-warning-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('Chú ý khi trừ tiền'); ?></strong><br>
                                <small><?= __('Hãy kiểm tra kỹ số dư và lý do trước khi thực hiện. Thao tác này không thể hoàn tác.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Loại ví'); ?></label>
                            <select class="form-select" name="wallet" id="walletSelect2">
                                <option value="1"><?= __('VÍ CHÍNH'); ?></option>
                                <option value="2"><?= __('VÍ GHI NỢ'); ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Số tiền'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                                <input type="number" step="any" min="0" class="form-control" name="amount" id="amountInput2"
                                    placeholder="<?= __('Nhập số tiền cần trừ'); ?>" required>
                                <span class="input-group-text"><?= getCurrencyNameDefault(); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Lý do'); ?></label>
                            <textarea class="form-control" name="reason" id="reasonInput" rows="3"
                                placeholder="<?= __('Nhập lý do trừ tiền'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 text-center">
                        <i class="ri-information-line me-1"></i>
                        <?= __('Sẽ thực hiện trừ'); ?> <span id="amountDisplay2" class="fw-bold text-danger">0</span>
                        <?= getCurrencyNameDefault(); ?>
                        <?= __('từ'); ?> <span id="walletDisplay2"
                            class="fw-bold text-warning"><?= __('VÍ CHÍNH'); ?></span>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i><?= __('Đóng'); ?>
                    </button>
                    <button type="submit" name="tru_tien" class="btn btn-danger btn-wave">
                        <i class="ri-subtract-line me-1"></i><?= __('Trừ tiền'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for improved UI -->
<script>
    // Toggle password visibility functions with improved styling
    function toggleTokenVisibility() {
        var input = document.getElementById('token_input');
        var button = document.getElementById('show_token');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleApiKeyVisibility() {
        var input = document.getElementById('api_key_input');
        var button = document.getElementById('show_api_key');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleKey2FAVisibility() {
        var input = document.getElementById('key_2fa_input');
        var button = document.getElementById('show_key_2fa');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    // Tab navigation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade animation to tabs
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function() {
                const target = document.querySelector(this.getAttribute('href'));
                target.style.opacity = '0';
                setTimeout(function() {
                    target.style.transition = 'opacity 0.3s ease-in-out';
                    target.style.opacity = '1';
                }, 50);
            });
        });

        // Xử lý chuyển tab VAT để cập nhật hidden input vat_type
        const vatTabLinks = document.querySelectorAll('#vat-info [data-bs-toggle="tab"]');
        if (vatTabLinks.length > 0) {
            // Khởi tạo hidden input ban đầu
            const activeVatTab = document.querySelector('#vat-info .nav-link.active');
            if (activeVatTab) {
                const targetId = activeVatTab.getAttribute('href');
                if (targetId === '#vat-personal') {
                    const personalInput = document.getElementById('vat_type_personal');
                    if (personalInput) personalInput.disabled = false;
                } else if (targetId === '#vat-business') {
                    const businessInput = document.getElementById('vat_type_business');
                    if (businessInput) businessInput.disabled = false;
                }
            }

            vatTabLinks.forEach(function(tabLink) {
                tabLink.addEventListener('shown.bs.tab', function() {
                    const targetId = this.getAttribute('href');
                    // Ẩn tất cả hidden input
                    const personalInput = document.getElementById('vat_type_personal');
                    const businessInput = document.getElementById('vat_type_business');
                    if (personalInput) personalInput.disabled = true;
                    if (businessInput) businessInput.disabled = true;

                    // Kích hoạt hidden input tương ứng với tab đang active
                    if (targetId === '#vat-personal' && personalInput) {
                        personalInput.disabled = false;
                    } else if (targetId === '#vat-business' && businessInput) {
                        businessInput.disabled = false;
                    }
                });
            });
        }

        // Xử lý sao chép thông tin xuất hóa đơn VAT
        const copyVatInfoBtn = document.getElementById('copyVatInfoBtn');
        if (copyVatInfoBtn) {
            copyVatInfoBtn.addEventListener('click', function() {
                // Xác định tab nào đang active
                const activeVatTab = document.querySelector('#vat-info .nav-link.active');
                if (!activeVatTab) return;

                const targetId = activeVatTab.getAttribute('href');
                let vatText = '';

                if (targetId === '#vat-personal') {
                    // Tab Cá nhân
                    const vatName = document.querySelector('#vat-personal input[name="vat_name"]')?.value || '';
                    const vatAddress = document.querySelector('#vat-personal textarea[name="vat_address"]')?.value || '';
                    const vatTaxCode = document.querySelector('#vat-personal input[name="vat_tax_code"]')?.value || '';
                    const vatCccd = document.querySelector('#vat-personal input[name="vat_cccd"]')?.value || '';

                    vatText = 'Cá Nhân\n';
                    vatText += '- Họ và Tên: ' + vatName + '\n';
                    vatText += '- Địa chỉ: ' + vatAddress + '\n';
                    vatText += '- Mã số thuế cá nhân: ' + vatTaxCode + '\n';
                    vatText += '- CCCD (Căn cước công dân): ' + vatCccd;

                } else if (targetId === '#vat-business') {
                    // Tab Doanh nghiệp
                    const vatName = document.querySelector('#vat-business input[name="vat_name"]')?.value || '';
                    const vatTaxCode = document.querySelector('#vat-business input[name="vat_tax_code"]')?.value || '';
                    const vatAddress = document.querySelector('#vat-business textarea[name="vat_address"]')?.value || '';

                    vatText = 'Doanh nghiệp\n';
                    vatText += '- Tên công ty: ' + vatName + '\n';
                    vatText += '- Mã số thuế: ' + vatTaxCode + '\n';
                    vatText += '- Địa chỉ công ty: ' + vatAddress;
                }

                // Sao chép vào clipboard
                if (vatText.trim()) {
                    navigator.clipboard.writeText(vatText).then(function() {
                        // Hiển thị thông báo thành công
                        const originalText = copyVatInfoBtn.innerHTML;
                        copyVatInfoBtn.innerHTML = '<i class="ri-check-line me-1"></i><?= __('Đã sao chép'); ?>';
                        copyVatInfoBtn.classList.remove('btn-outline-primary');
                        copyVatInfoBtn.classList.add('btn-success');

                        setTimeout(function() {
                            copyVatInfoBtn.innerHTML = originalText;
                            copyVatInfoBtn.classList.remove('btn-success');
                            copyVatInfoBtn.classList.add('btn-outline-primary');
                        }, 2000);
                    }).catch(function(err) {
                        // Fallback cho trình duyệt không hỗ trợ clipboard API
                        const textArea = document.createElement('textarea');
                        textArea.value = vatText;
                        textArea.style.position = 'fixed';
                        textArea.style.opacity = '0';
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            const originalText = copyVatInfoBtn.innerHTML;
                            copyVatInfoBtn.innerHTML = '<i class="ri-check-line me-1"></i><?= __('Đã sao chép'); ?>';
                            copyVatInfoBtn.classList.remove('btn-outline-primary');
                            copyVatInfoBtn.classList.add('btn-success');

                            setTimeout(function() {
                                copyVatInfoBtn.innerHTML = originalText;
                                copyVatInfoBtn.classList.remove('btn-success');
                                copyVatInfoBtn.classList.add('btn-outline-primary');
                            }, 2000);
                        } catch (err) {
                            alert('<?= __('Không thể sao chép. Vui lòng sao chép thủ công.'); ?>');
                        }
                        document.body.removeChild(textArea);
                    });
                } else {
                    alert('<?= __('Không có thông tin để sao chép.'); ?>');
                }
            });
        }
    });
</script>