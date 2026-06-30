<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge PaymentPoint') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
<style>
/* Alert Styles */
.pp-alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}
.pp-alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.pp-alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
.pp-alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.pp-alert i {
    margin-right: 8px;
}

/* Bank Account Card */
.bank-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 0;
    margin-bottom: 15px;
    overflow: hidden;
}
.bank-card-header {
    background: #17a2b8;
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 14px;
}
.bank-card-body {
    padding: 15px;
}
.bank-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px dashed #e0e0e0;
}
.bank-info-row:last-child {
    border-bottom: none;
}
.bank-info-label {
    color: #666;
    font-size: 13px;
    font-weight: 500;
}
.bank-info-value {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}
.bank-info-value.highlight {
    color: #d9534f;
    font-size: 18px;
}
.copy-btn {
    background: #17a2b8;
    border: none;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
}
.copy-btn:hover {
    background: #138496;
}

/* Loading Spinner */
.loading-spinner {
    display: none;
}
.loading-spinner.show {
    display: inline-block;
}

/* Modal Buttons */
.pp-btn {
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    margin-left: 10px;
}
.pp-btn-secondary {
    background: #6c757d;
    color: white;
}
.pp-btn-secondary:hover {
    background: #5a6268;
}
.pp-btn-primary {
    background: #007bff;
    color: white;
}
.pp-btn-primary:hover {
    background: #0069d9;
}
</style>
';
$body['footer'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_user.php');
if ($CMSNT->site('paymentpoint_status') != 1) {
    redirect(base_url());
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');


$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Sử dụng prepared statements với validation
$where_conditions = ["`user_id` = ?", "`status` = 1"];
$where_params = [$getUser['id']];

$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';

// Validate trans_id
if (!empty($_GET['trans_id'])) {
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if ($trans_id !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $trans_id;
    }
}

// Validate amount
if (!empty($_GET['amount'])) {
    $amount = validate_float($_GET['amount'], 0.01, 999999.99);
    if ($amount !== false) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}

// Validate time range
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

// Validate shortByDate
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
$sql = "SELECT * FROM `payment_paymentpoint` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_paymentpoint` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-paymentpoint&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&"), $from, $totalDatatable, $limit);

// Kiểm tra xem user đã có virtual account chưa thanh toán (status = 0) không
// Nếu status = 1 (đã thanh toán) thì bỏ qua, cho phép tạo tài khoản mới
$existingAccount = $CMSNT->get_row_safe(
    "SELECT * FROM `payment_paymentpoint` WHERE `user_id` = ? AND `account_number` IS NOT NULL AND `account_number` != '' AND `status` = 0 ORDER BY `id` DESC LIMIT 1",
    [$getUser['id']]
);

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Recharge PaymentPoint'); ?></h4>
                    <div class="text-center mb-4">
                        <img width="200px" src="<?= base_url('mod/img/bg-paymentpoint.png'); ?>" />
                    </div>

                    <input type="hidden" class="form-control" id="token" value="<?= $getUser['token']; ?>">

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

                    <?php else: ?>
                        <!-- Nút tạo tài khoản mới -->
                        <div class="text-center">
                            <p class="mb-3"><?= __('Nhấn nút bên dưới để tạo thông tin nạp tiền'); ?></p>
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
                    <?= $CMSNT->site('paymentpoint_notice'); ?>

                    <div class="mt-3">
                        <h6><?= __('Tỷ giá quy đổi:'); ?></h6>
                        <p><strong>1 <?= $CMSNT->site('paymentpoint_currency_code'); ?> = <?= format_currency($CMSNT->site('paymentpoint_rate')); ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Lịch sử nạp PaymentPoint'); ?></h4>
                    <form action="<?= base_url(); ?>" method="GET">
                        <input type="hidden" name="action" value="recharge-paymentpoint">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $trans_id; ?>" name="trans_id"
                                    placeholder="<?= __('Search transaction id'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $amount; ?>" name="amount"
                                    placeholder="<?= __('Search amount'); ?>">
                            </div>
                            <div class="col-lg col-md-6 col-6">
                                <input type="text" class="js-flatpickr form-control mb-1" id="example-flatpickr-range"
                                    name="time" placeholder="<?= __('Chọn thời gian cần tìm'); ?>" value="<?= $time; ?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?= base_url('?action=recharge-paymentpoint'); ?>" class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show"><label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                    <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                    <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                    <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                    <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                    <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                    <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
                                </select>
                            </div>
                            <div class="filter-short">
                                <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                <select name="shortByDate" onchange="this.form.submit()"
                                    class="form-select filter-select">
                                    <option value=""><?= __('Tất cả'); ?></option>
                                    <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?>
                                    </option>
                                    <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?>
                                    </option>
                                    <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll">
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center"><?= __('TransID'); ?></th>
                                    <th class="text-center"><?= __('Amount'); ?></th>
                                    <th class="text-center"><?= __('Price'); ?></th>
                                    <th class="text-center"><?= __('Bank'); ?></th>
                                    <th class="text-center"><?= __('Status'); ?></th>
                                    <th class="text-center"><?= __('Create date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) { ?>
                                    <tr>
                                        <td class="text-center"><b><?= $row['trans_id']; ?></b></td>
                                        <td class="text-center"><b><?= format_cash($row['amount']); ?></b> <?= $CMSNT->site('paymentpoint_currency_code'); ?></td>
                                        <td class="text-center"><b
                                                style="color: red;"><?= format_currency($row['price']); ?></b></td>
                                        <td class="text-center"><?= $row['bank_name']; ?></td>
                                        <td class="text-center"><?= display_invoice($row['status']); ?></td>
                                        <td class="text-center"><i class="far fa-calendar-alt mr-2 text-secondary"></i>
                                            <?= $row['created_at']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <div class="float-right">
                                            <?= __('Paid:'); ?>
                                            <strong
                                                style="color:red;"><?= format_currency($CMSNT->get_row_safe(" SELECT SUM(`price`) FROM `payment_paymentpoint` WHERE $where_clause AND `status` = 1 ", $where_params)['SUM(`price`)']); ?></strong>

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

<!-- Modal hiển thị thông tin tài khoản -->
<div class="modal fade" id="bankAccountModal" tabindex="-1" aria-labelledby="bankAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankAccountModalLabel">
                    <i class="fas fa-university"></i> <?= __('Thông tin tài khoản nạp tiền'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="pp-alert pp-alert-success">
                    <i class="fas fa-check-circle"></i> <?= __('Chuyển tiền vào tài khoản bên dưới để nạp tiền tự động.'); ?>
                </div>

                <div id="bankAccountsContainer">
                    <!-- Bank cards will be inserted here by JavaScript -->
                </div>

                <p class="mt-3 mb-0"><strong><?= __('Tỷ giá:'); ?></strong> 1 <?= $CMSNT->site('paymentpoint_currency_code'); ?> = <?= format_currency($CMSNT->site('paymentpoint_rate')); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="pp-btn pp-btn-secondary" data-bs-dismiss="modal"><?= __('Đóng'); ?></button>
                <button type="button" class="pp-btn pp-btn-primary" onclick="location.reload();"><?= __('Làm mới trang'); ?></button>
            </div>
        </div>
    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>


<script type="text/javascript">
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: '<?= __("Đã sao chép!"); ?>',
                text: text,
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(function(err) {
            // Fallback for older browsers
            var tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            Swal.fire({
                icon: 'success',
                title: '<?= __("Đã sao chép!"); ?>',
                text: text,
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    <?php if (!$existingAccount || empty($existingAccount['account_number'])): ?>
        $("#btnCreateAccount").on("click", function() {
            var btn = $(this);
            btn.find('.btn-text').hide();
            btn.find('.loading-spinner').addClass('show');
            btn.prop('disabled', true);

            $.ajax({
                url: "<?= BASE_URL('ajaxs/client/create.php'); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'CreatePaymentpointAccount',
                    token: $("#token").val()
                },
                success: function(response) {
                    if (response.status == 'success') {
                        // Hiển thị thông tin tài khoản trong modal
                        var accountsHtml = '';
                        if (response.bank_accounts && response.bank_accounts.length > 0) {
                            response.bank_accounts.forEach(function(account) {
                                accountsHtml += '<div class="bank-card">';
                                accountsHtml += '<div class="bank-card-header"><i class="fas fa-university"></i> <?= __("Thông tin chuyển khoản"); ?></div>';
                                accountsHtml += '<div class="bank-card-body">';
                                accountsHtml += '<div class="bank-info-row"><span class="bank-info-label"><?= __("Ngân hàng"); ?></span><span class="bank-info-value">' + account.bankName + '</span></div>';
                                accountsHtml += '<div class="bank-info-row"><span class="bank-info-label"><?= __("Số tài khoản"); ?></span><span class="bank-info-value highlight">' + account.accountNumber + ' <button class="copy-btn" onclick="copyToClipboard(\'' + account.accountNumber + '\')"><i class="fas fa-copy"></i> Copy</button></span></div>';
                                accountsHtml += '<div class="bank-info-row"><span class="bank-info-label"><?= __("Chủ tài khoản"); ?></span><span class="bank-info-value">' + account.accountName + '</span></div>';
                                accountsHtml += '</div></div>';
                            });
                        }

                        $('#bankAccountsContainer').html(accountsHtml);
                        $('#bankAccountModal').modal('show');

                    } else {
                        Swal.fire(
                            '<?= __('Error'); ?>',
                            response.msg,
                            'error'
                        );
                    }

                    btn.find('.btn-text').show();
                    btn.find('.loading-spinner').removeClass('show');
                    btn.prop('disabled', false);
                },
                error: function() {
                    Swal.fire(
                        '<?= __('Error'); ?>',
                        '<?= __('Có lỗi xảy ra, vui lòng thử lại'); ?>',
                        'error'
                    );

                    btn.find('.btn-text').show();
                    btn.find('.loading-spinner').removeClass('show');
                    btn.prop('disabled', false);
                }
            });
        });
    <?php endif; ?>
</script>



<script>
    Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
        'jq-masked-inputs', 'jq-pw-strength'
    ]);
</script>