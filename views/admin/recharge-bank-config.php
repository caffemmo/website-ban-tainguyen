<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Bank Config'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

';
$body['footer'] = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');

if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }
    // Kiểm tra giấy phép khi chọn kiểu nạp tiền "Họ và tên + chuyển tiền"
    if (isset($_POST['bank_recharge_type']) && $_POST['bank_recharge_type'] == 'fullname_transfer') {
        $checkAddon = checkAddonLicense($_POST['bank_recharge_type_license'] ?? '', 'SHOPCLONE7_BANK_FULLNAME_TRANSFER');
        if ($checkAddon['status'] != true) {
            die('<script type="text/javascript">if(!alert("' . __('Giấy phép kích hoạt không hợp lệ hoặc đã hết hạn. Vui lòng kiểm tra lại.') . '")){window.history.back().location.reload();}</script>');
        }
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình nạp tiền Ngân Hàng')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền Ngân Hàng'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
}
?>
<?php
if (isset($_POST['ThemNganHang'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không thể sử dụng chức năng này vì đây là trang web demo') . '")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }

    $url_image = '';
    if (check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $uploads_dir = 'assets/storage/images/bank/' . $rand . '.png';
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_image = 'assets/storage/images/bank/' . $rand . '.png';
        }
    }
    $isInsert = $CMSNT->insert("banks", [
        'image'         => $url_image,
        'short_name'    => check_string($_POST['short_name']),
        'accountNumber' => check_string($_POST['accountNumber']),
        'token' => check_string(removeSpaces($_POST['token'])),
        'password' => check_string($_POST['password']),
        'accountName'   => check_string($_POST['accountName'])
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Thêm ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Thêm ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại !")){window.history.back().location.reload();}</script>');
    }
}



?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Cấu hình ngân hàng</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Nạp tiền</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url_admin('recharge-bank'); ?>">Ngân hàng</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cấu hình</li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if ($CMSNT->site('bank_api_type') != 'pay2s' && time() - $CMSNT->site('check_time_cron_bank') >= 120): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                Vui lòng thực hiện <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> liên kết: <a class="text-primary" href="<?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank"><?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ thống xử lý nạp tiền tự động.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <a class="btn btn-danger label-btn mb-3" href="<?= base_url_admin('recharge-bank'); ?>">
                        <i class="ri-arrow-go-back-line label-btn-icon me-2"></i> QUAY LẠI
                    </a>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH NGÂN HÀNG
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Thêm ngân hàng</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="datatable-basic" class="table text-nowrap table-striped table-hover table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?= __('Ngân hàng'); ?></th>
                                    <th><?= __('Số tài khoản'); ?></th>
                                    <th><?= __('Chủ tài khoản'); ?></th>
                                    <th>Trạng thái</th>
                                    <th><?= __('Thao tác'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0;
                                foreach ($CMSNT->get_list("SELECT * FROM `banks`  ") as $bank) { ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= $bank['short_name']; ?></td>
                                        <td><?= $bank['accountNumber']; ?></td>
                                        <td><?= $bank['accountName']; ?></td>
                                        <td><?= display_status_product($bank['status']); ?></td>
                                        <td><a aria-label=""
                                                href="<?= base_url_admin('recharge-bank-edit&id=' . $bank['id']); ?>"
                                                style="color:white;" class="btn btn-info btn-sm btn-icon-left m-b-10"
                                                type="button">
                                                <i class="fas fa-edit mr-1"></i><span class=""> Edit</span>
                                            </a>
                                            <button style="color:white;" onclick="RemoveRow('<?= $bank['id']; ?>')"
                                                class="btn btn-danger btn-sm btn-icon-left m-b-10" type="button">
                                                <i class="fas fa-trash mr-1"></i><span class=""> Delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CẤU HÌNH
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Trạng
                                            thái</label>
                                        <div class="col-sm-8">
                                            <select class="form-control form-control-sm" name="bank_status">
                                                <option <?= $CMSNT->site('bank_status') == 0 ? 'selected' : ''; ?>
                                                    value="0">OFF
                                                </option>
                                                <option <?= $CMSNT->site('bank_status') == 1 ? 'selected' : ''; ?>
                                                    value="1">ON
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label"
                                            for="bank_recharge_type"><?= __('Kiểu nạp tiền'); ?></label>
                                        <div class="col-sm-8">
                                            <select class="form-control form-control-sm" name="bank_recharge_type" id="bank_recharge_type" onchange="togglePrefixRow()">
                                                <option <?= $CMSNT->site('bank_recharge_type') == 'prefix_id' || $CMSNT->site('bank_recharge_type') == '' ? 'selected' : ''; ?>
                                                    value="prefix_id">Prefix + ID
                                                </option>
                                                <option <?= $CMSNT->site('bank_recharge_type') == 'fullname_transfer' ? 'selected' : ''; ?>
                                                    value="fullname_transfer">Họ và tên + chuyển tiền
                                                </option>
                                            </select>
                                            <small>Chọn kiểu nội dung chuyển khoản mà khách hàng sử dụng khi nạp tiền.</small>
                                        </div>
                                    </div>
                                    <div class="row mb-4" id="license_row" style="<?= $CMSNT->site('bank_recharge_type') == 'fullname_transfer' ? '' : 'display:none;'; ?>">
                                        <label class="col-sm-4 col-form-label" for="bank_recharge_type_license"><?= __('Giấy phép kích hoạt'); ?></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control form-control-sm"
                                                value="<?= $CMSNT->site('bank_recharge_type_license'); ?>" name="bank_recharge_type_license"
                                                placeholder="921abf4dbff01xxxxxf3c562c356c769">
                                            <div style="margin-top: 10px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;">
                                                <strong>Chú ý:</strong> Bạn cần phải mua giấy phép kích hoạt <a target="_blank" style="color: #007bff;" href="https://client.cmsnt.co/cart.php?a=add&pid=110">Addon</a> trước khi sử dụng.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-4" id="prefix_row">
                                        <label class="col-sm-4 col-form-label"
                                            for="example-hf-email"><?= __('Prefix'); ?></label>
                                        <div class="col-sm-8">
                                            <div class="input-group" id="prefix_input_group">
                                                <span class="input-group-text" id="prefix_prepend" style="<?= $CMSNT->site('bank_recharge_type') == 'fullname_transfer' ? '' : 'display:none;'; ?>">HỌ VÀ TÊN +</span>
                                                <input type="text" class="form-control form-control-sm"
                                                    value="<?= $CMSNT->site('prefix_autobank'); ?>" name="prefix_autobank"
                                                    placeholder="VD: NAPTIEN">
                                                <span class="input-group-text" id="prefix_append" style="<?= $CMSNT->site('bank_recharge_type') == 'fullname_transfer' ? 'display:none;' : ''; ?>">
                                                    <?= $getUser['id']; ?>
                                                </span>
                                            </div>
                                            <small id="prefix_help"><?= $CMSNT->site('bank_recharge_type') == 'fullname_transfer' ? 'Nội dung CK = HỌ VÀ TÊN + Prefix. VD: NGUYEN VAN A chuyen tien' : 'Không được để trống Prefix, Prefix là nội dung nạp tiền vào hệ thống.'; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="example-hf-email">Số tiền
                                            nạp tối thiểu</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm"
                                                value="<?= $CMSNT->site('bank_min'); ?>" name="bank_min">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="example-hf-email">Số tiền
                                            nạp tối đa</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm"
                                                value="<?= $CMSNT->site('bank_max'); ?>" name="bank_max">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="bank_api_type">Loại API</label>
                                        <div class="col-sm-6">
                                            <select class="form-control form-control-sm" name="bank_api_type" id="bank_api_type" onchange="toggleApiType()">
                                                <option <?= $CMSNT->site('bank_api_type') == 'web2m' || $CMSNT->site('bank_api_type') == '' ? 'selected' : ''; ?> value="web2m">web2m</option>
                                                <option <?= $CMSNT->site('bank_api_type') == 'pay2s' ? 'selected' : ''; ?> value="pay2s">pay2s</option>
                                            </select>
                                            <small><span id="api_guide_web2m" style="<?= $CMSNT->site('bank_api_type') == 'pay2s' ? 'display:none;' : ''; ?>">Hướng dẫn tích hợp <a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-bang-ngan-hang-vn-tu-dong/">tại đây</a>.</span></small>
                                            <small><span id="api_guide_pay2s" style="<?= $CMSNT->site('bank_api_type') == 'pay2s' ? '' : 'display:none;'; ?>">pay2s chỉ hỗ trợ Webhook, không sử dụng Cron Job. Đăng ký tài khoản <a target="_blank" class="text-primary" href="https://ntlink.co/pay2s">tại đây</a>.</span></small>
                                        </div>
                                    </div>
                                    <div class="row mb-4" id="row_token_webhook">
                                        <label class="col-sm-6 col-form-label"><?= __('Token Webhook API'); ?></label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm"
                                                value="<?= $CMSNT->site('token_webhook_web2m'); ?>" name="token_webhook_web2m"
                                                placeholder="Token Webhook API nếu có">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label">Webhook URL</label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm" id="webhook_url_display"
                                                    value="<?= $CMSNT->site('bank_api_type') == 'pay2s' ? base_url('api/webhook_pay2s.php') : base_url('api/webhook_web2m.php'); ?>"
                                                    readonly>
                                                <button class="btn btn-sm btn-outline-primary" type="button" onclick="copyWebhookUrl()">
                                                    <i class="ri-file-copy-line"></i> Sao chép
                                                </button>
                                            </div>
                                            <small>Sao chép link này và dán vào cấu hình Webhook tại nhà cung cấp API.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?= __('Lưu ý nạp tiền'); ?></label>
                                        <div class="col-sm-12">
                                            <textarea id="bank_notice"
                                                name="bank_notice"><?= $CMSNT->site('bank_notice'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href=""><i class="fa fa-fw fa-undo me-1"></i>
                                <?= __('Reload'); ?></a>
                            <button type="submit" name="SaveSettings" class="btn btn-success">
                                <i class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel2">Thêm ngân hàng mới</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Ngân hàng'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" list="options" name="short_name"
                                    placeholder="<?= __('Nhập tên ngân hàng'); ?>" required>
                                <datalist id="options">
                                    <?php foreach ($config_listbank as $key => $value): ?>
                                        <option value="<?= $key; ?>"><?= $value; ?></option>
                                    <?php endforeach ?>
                                    <?php $data = json_decode(curl_get('https://api.vietqr.io/v2/banks'), true);
                                    foreach ($data['data'] as $bank): ?>
                                        <option value="<?= $bank['code']; ?>"><?= $bank['name']; ?></option>
                                    <?php endforeach ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label">Image <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="image" required>
                                <small><?= __('Khi VietQR không hoạt động, hệ thống sẽ hiện ảnh này thay cho mã QR'); ?></small>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Số tài khoản'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="accountNumber"
                                    placeholder="Nhập số tài khoản" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Chủ tài khoản'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="accountName"
                                    placeholder="Nhập tên chủ tài khoản" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"
                                for="example-hf-email"><?= __('Password Internet Banking'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="password"
                                    placeholder="Áp dụng khi cấu hình nạp tiền tự động.">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Token'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="token"
                                    placeholder="Áp dụng khi cấu hình nạp tiền tự động.">
                            </div>
                        </div>
                        <small>Hướng dẫn tích hợp tự động nạp tiền bằng Ngân Hàng tại <a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-bang-ngan-hang-vn-tu-dong/">đây</a></small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="ThemNganHang" class="btn btn-primary btn-sm"><i
                                class="fa fa-fw fa-plus me-1"></i>
                            <?= __('Submit'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>
<script>
    CKEDITOR.replace("bank_notice");

    function togglePrefixRow() {
        var type = document.getElementById('bank_recharge_type').value;
        var licenseRow = document.getElementById('license_row');
        var prefixPrepend = document.getElementById('prefix_prepend');
        var prefixAppend = document.getElementById('prefix_append');
        var prefixHelp = document.getElementById('prefix_help');
        if (type === 'fullname_transfer') {
            licenseRow.style.display = '';
            prefixPrepend.style.display = '';
            prefixAppend.style.display = 'none';
            prefixHelp.textContent = 'Nội dung CK = HỌ VÀ TÊN + Prefix. VD: NGUYEN VAN A NAPTIEN';
        } else {
            licenseRow.style.display = 'none';
            prefixPrepend.style.display = 'none';
            prefixAppend.style.display = '';
            prefixHelp.textContent = 'Không được để trống Prefix, Prefix là nội dung nạp tiền vào hệ thống.';
        }
    }

    function toggleApiType() {
        var apiType = document.getElementById('bank_api_type').value;
        var isPay2s = (apiType === 'pay2s');
        // Ẩn/hiện hướng dẫn theo loại API
        document.getElementById('api_guide_web2m').style.display = isPay2s ? 'none' : '';
        document.getElementById('api_guide_pay2s').style.display = isPay2s ? '' : 'none';
        // Cập nhật Webhook URL
        var webhookInput = document.getElementById('webhook_url_display');
        var baseUrl = '<?= base_url(); ?>';
        webhookInput.value = isPay2s ? baseUrl + 'api/webhook_pay2s.php' : baseUrl + 'api/webhook_web2m.php';
    }

    function copyWebhookUrl() {
        var webhookInput = document.getElementById('webhook_url_display');
        webhookInput.select();
        webhookInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(webhookInput.value).then(function() {
            showMessage('Đã sao chép Webhook URL!', 'success');
        }).catch(function() {
            document.execCommand('copy');
            showMessage('Đã sao chép Webhook URL!', 'success');
        });
    }
</script>


<script type="text/javascript">
    function RemoveRow(id) {
        cuteAlert({
            type: "question",
            title: "<?= __('Confirm Remove'); ?>",
            message: "<?= __('Are you sure you want to delete the ID'); ?> " + id + " ?",
            confirmText: "Okey",
            cancelText: "Close"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'removeBank',
                        id: id
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        })
    }
</script>

<script>
    $('#datatable-basic').DataTable({
        language: {
            searchPlaceholder: 'Search...',
            sSearch: '',
        },
        "pageLength": 10,
        scrollX: true
    });
</script>