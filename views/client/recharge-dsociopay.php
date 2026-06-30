<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge DSocioPay') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<style>
    .bank-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
    .bank-card-header { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .bank-card-body { background: rgba(255,255,255,0.15); border-radius: 12px; padding: 16px; }
    .bank-info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2); }
    .bank-info-row:last-child { border-bottom: none; }
    .bank-info-label { font-size: 13px; opacity: 0.85; }
    .bank-info-value { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .bank-info-value.highlight { color: #ffd700; font-size: 18px; letter-spacing: 2px; }
    .copy-btn { background: rgba(255,255,255,0.25); border: none; color: #fff; padding: 4px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.2s; }
    .copy-btn:hover { background: rgba(255,255,255,0.4); }
    .pp-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 14px; }
    .pp-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .pp-alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .loading-spinner { display: none; }
    #btnCreateAccount:disabled .btn-text { display: none; }
    #btnCreateAccount:disabled .loading-spinner { display: inline; }
    /* Modal styles */
    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
    .bank-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; display: none; width: 90%; max-width: 480px; }
    .bank-modal .bank-card { margin: 0; }
    .bank-modal-close { position: absolute; top: 10px; right: 16px; background: rgba(255,255,255,0.3); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
</style>
';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_user.php');
if ($CMSNT->site('dsociopay_status') != 1) {
    redirect(base_url());
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../libs/dsociopay.php');

// Lấy thông tin virtual account đã tạo (nếu có)
$existingAccount = dsociopayGetUserVirtualAccount($getUser['id']);

$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';

if (!empty($_GET['trans_id'])) {
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if ($trans_id !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $trans_id;
    }
}
if (!empty($_GET['amount'])) {
    $amount = validate_int($_GET['amount'], 1);
    if ($amount !== false) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}
if (!empty($_GET['time'])) {
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if (count($create_gettime_1) == 2 && $create_gettime_1[0] != $create_gettime_1[1]) {
            $start_date = $create_gettime_1[0] . ' 00:00:00';
            $end_date = $create_gettime_1[1] . ' 23:59:59';
            if (validate_date($create_gettime_1[0], 'Y/m/d') && validate_date($create_gettime_1[1], 'Y/m/d')) {
                $where_conditions[] = '`created_at` >= ? AND `created_at` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if ($shortByDate !== false) {
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        if ($shortByDate == 1) {
            $where_conditions[] = '`created_at` LIKE ?';
            $where_params[] = '%' . $currentDate . '%';
        }
        if ($shortByDate == 2) {
            $where_conditions[] = 'YEAR(created_at) = ? AND WEEK(created_at, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if ($shortByDate == 3) {
            $where_conditions[] = 'MONTH(created_at) = ? AND YEAR(created_at) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_dsociopay` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);
$count_sql = "SELECT * FROM `payment_dsociopay` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);
$urlDatatable = pagination(base_url("?action=recharge-dsociopay&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&"), $from, $totalDatatable, $limit);

$gatewayName = $CMSNT->site('dsociopay_name') ?: 'DSocioPay';
$gatewayIcon = $CMSNT->site('dsociopay_icon') ?: 'mod/img/dsociopay.png';
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?= $gatewayName; ?></h4>
                    <div class="text-center mb-4">
                        <div class="d-flex justify-content-center">
                            <img width="150px" src="<?= base_url($gatewayIcon); ?>" alt="<?= $gatewayName; ?>" />
                        </div>
                    </div>

                    <?php if ($existingAccount && !empty($existingAccount['account_number'])): ?>
                        <!-- Hiển thị thông tin tài khoản đã có -->
                        <div class="pp-alert pp-alert-success">
                            <i class="fas fa-check-circle"></i> <?= __('Chuyển tiền vào tài khoản bên dưới để nạp tiền tự động.'); ?>
                        </div>

                        <div class="bank-card">
                            <div class="bank-card-header">
                                <i class="fas fa-university"></i> <?= __('Thông tin chuyển khoản'); ?>
                            </div>
                            <div class="bank-card-body">
                                <div class="bank-info-row">
                                    <span class="bank-info-label"><?= __('Ngân hàng'); ?></span>
                                    <span class="bank-info-value"><?= $existingAccount['bank_name']; ?></span>
                                </div>
                                <div class="bank-info-row">
                                    <span class="bank-info-label"><?= __('Số tài khoản'); ?></span>
                                    <span class="bank-info-value highlight">
                                        <?= $existingAccount['account_number']; ?>
                                        <button class="copy-btn" onclick="copyToClipboard('<?= $existingAccount['account_number']; ?>')">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </span>
                                </div>
                                <div class="bank-info-row">
                                    <span class="bank-info-label"><?= __('Chủ tài khoản'); ?></span>
                                    <span class="bank-info-value"><?= $existingAccount['account_name']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="pp-alert pp-alert-info">
                            <i class="fas fa-info-circle"></i> <?= __('Chuyển khoản bất kỳ số tiền nào vào tài khoản trên, hệ thống sẽ tự động cộng tiền.'); ?>
                        </div>
                    <?php else: ?>
                        <!-- Nút tạo tài khoản mới -->
                        <div class="text-center">
                            <p class="mb-3"><?= __('Nhấn nút bên dưới để tạo thông tin nạp tiền'); ?></p>
                            <input type="hidden" id="token" value="<?= $getUser['token']; ?>">
                            <div class="wallet-form">
                                <button type="button" id="btnCreateAccount">
                                    <span class="btn-text"><?= __('Tạo tài khoản nạp tiền'); ?></span>
                                    <span class="loading-spinner">
                                        <i class="fas fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Lưu ý'); ?></h4>
                    <?= $CMSNT->site('dsociopay_notice'); ?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Lịch sử nạp tiền'); ?> <?= $gatewayName; ?></h4>
                    <form action="<?= base_url(); ?>" method="GET">
                        <input type="hidden" name="action" value="recharge-dsociopay">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $trans_id; ?>" name="trans_id" placeholder="<?= __('Mã giao dịch'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $amount; ?>" name="amount" placeholder="<?= __('Số tiền'); ?>">
                            </div>
                            <div class="col-lg col-md-6 col-6">
                                <input type="text" class="js-flatpickr form-control mb-1" name="time" placeholder="<?= __('Chọn thời gian'); ?>" value="<?= $time; ?>" data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?= base_url('?action=recharge-dsociopay'); ?>" class="shop-widget-btn mb-2"><i class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
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
                    <div class="table-scroll">
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center"><?= __('TransID'); ?></th>
                                    <th class="text-center"><?= __('Ngân hàng'); ?></th>
                                    <th class="text-center"><?= __('Số TK'); ?></th>
                                    <th class="text-center"><?= __('Số tiền'); ?></th>
                                    <th class="text-center"><?= __('Thực nhận'); ?></th>
                                    <th class="text-center"><?= __('Status'); ?></th>
                                    <th class="text-center"><?= __('Ngày tạo'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) { ?>
                                    <tr>
                                        <td class="text-center"><b><?= $row['trans_id']; ?></b></td>
                                        <td class="text-center"><?= $row['bank_name']; ?></td>
                                        <td class="text-center"><b><?= $row['account_number']; ?></b></td>
                                        <td class="text-center"><b><?= number_format($row['amount'], 2); ?></b> <?= $CMSNT->site('dsociopay_currency_code') ?: 'NGN'; ?></td>
                                        <td class="text-center"><b style="color: red;"><?= format_currency($row['price']); ?></b></td>
                                        <td class="text-center"><?= display_invoice($row['status']); ?></td>
                                        <td class="text-center"><i class="far fa-calendar-alt mr-2 text-secondary"></i> <?= $row['created_at']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?= __('Paid:'); ?>
                                            <strong style="color:red;"><?= format_currency($CMSNT->get_row_safe("SELECT SUM(`price`) FROM `payment_dsociopay` WHERE $where_clause AND `status` = 1", $where_params)['SUM(`price`)']); ?></strong>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bottom-paginate">
                        <p class="page-info">Showing <?= $limit; ?> of <?= $totalDatatable; ?> Results</p>
                        <div class="pagination">
                            <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal hiển thị thông tin bank account -->
<div class="modal-backdrop" id="bankModalBackdrop"></div>
<div class="bank-modal" id="bankAccountModal">
    <div class="bank-card">
        <button class="bank-modal-close" id="closeBankModal">&times;</button>
        <div class="bank-card-header">
            <i class="fas fa-check-circle"></i> <?= __('Tài khoản đã tạo thành công!'); ?>
        </div>
        <div class="bank-card-body">
            <div class="bank-info-row">
                <span class="bank-info-label"><?= __('Ngân hàng'); ?></span>
                <span class="bank-info-value" id="modal-bank-name"></span>
            </div>
            <div class="bank-info-row">
                <span class="bank-info-label"><?= __('Số tài khoản'); ?></span>
                <span class="bank-info-value highlight" id="modal-account-number"></span>
            </div>
            <div class="bank-info-row">
                <span class="bank-info-label"><?= __('Chủ tài khoản'); ?></span>
                <span class="bank-info-value" id="modal-account-name"></span>
            </div>
        </div>
        <div class="text-center mt-3">
            <button class="copy-btn" onclick="copyToClipboard(document.getElementById('modal-account-number').textContent.trim())" style="padding: 8px 24px; font-size: 14px;">
                <i class="fas fa-copy"></i> <?= __('Copy Số Tài Khoản'); ?>
            </button>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script type="text/javascript">
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: '<?= __("Đã sao chép!"); ?>',
                    text: text,
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        } else {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            Swal.fire({
                icon: 'success',
                title: '<?= __("Đã sao chép!"); ?>',
                text: text,
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    // Modal controls
    document.getElementById('closeBankModal')?.addEventListener('click', function() {
        document.getElementById('bankAccountModal').style.display = 'none';
        document.getElementById('bankModalBackdrop').style.display = 'none';
        location.reload();
    });
    document.getElementById('bankModalBackdrop')?.addEventListener('click', function() {
        document.getElementById('bankAccountModal').style.display = 'none';
        document.getElementById('bankModalBackdrop').style.display = 'none';
        location.reload();
    });

    // Create account button
    document.getElementById('btnCreateAccount')?.addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;

        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/create.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'CreateDsociopayAccount',
                token: document.getElementById('token').value
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Hiển thị modal với thông tin account
                    document.getElementById('modal-bank-name').textContent = response.bank_name || '';
                    document.getElementById('modal-account-number').textContent = response.account_number || '';
                    document.getElementById('modal-account-name').textContent = response.account_name || '';
                    document.getElementById('bankAccountModal').style.display = 'block';
                    document.getElementById('bankModalBackdrop').style.display = 'block';
                } else {
                    Swal.fire('<?= __("Lỗi"); ?>', response.msg, 'error');
                    btn.disabled = false;
                }
            },
            error: function() {
                Swal.fire('<?= __("Lỗi"); ?>', '<?= __("Lỗi kết nối"); ?>', 'error');
                btn.disabled = false;
            }
        });
    });
</script>

<script>
    // Polling check for payment status
    function loadData() {
        $.ajax({
            url: "<?= base_url('ajaxs/client/view.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'notication_topup_dsociopay',
                token: '<?= $getUser['token']; ?>'
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '<?= __('Thành công !'); ?>',
                        text: respone.msg,
                        showDenyButton: true,
                        confirmButtonText: '<?= __('Nạp Thêm'); ?>',
                        denyButtonText: `<?= __('Mua Ngay'); ?>`,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        } else if (result.isDenied) {
                            window.location.href = '<?= base_url(); ?>';
                        } else {
                            setTimeout(loadData, 5000);
                        }
                    });
                } else {
                    setTimeout(loadData, 5000);
                }
            },
            error: function() {
                setTimeout(loadData, 5000);
            }
        });
    }
    loadData();
</script>

<script>
    Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider', 'jq-masked-inputs', 'jq-pw-strength']);
</script>