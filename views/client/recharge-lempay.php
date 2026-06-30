<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge LemPay') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_user.php');
if ($CMSNT->site('lempay_status') != 1) {
    redirect(base_url());
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Sử dụng prepared statements
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';
$type = '';

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

// Validate type
if (!empty($_GET['type'])) {
    $type = validate_string($_GET['type'], 20);
    if ($type !== false && in_array($type, ['alipay', 'wxpay', 'usdt'])) {
        $where_conditions[] = '`type` = ?';
        $where_params[] = $type;
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
$sql = "SELECT * FROM `payment_lempay` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_lempay` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-lempay&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&type=$type&"), $from, $totalDatatable, $limit);

// Payment type icons
function getLempayTypeIcon($type)
{
    $icons = [
        'alipay' => '<span class="badge bg-primary"><i class="fab fa-alipay"></i> Alipay</span>',
        'wxpay' => '<span class="badge bg-success"><i class="fab fa-weixin"></i> WeChat</span>',
        'usdt' => '<span class="badge bg-warning"><i class="fab fa-bitcoin"></i> USDT</span>'
    ];
    return $icons[$type] ?? $type;
}
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Recharge LemPay'); ?></h4>
                    <div class="text-center mb-4">
                        <div class="d-flex justify-content-center">
                            <img width="120px" src="<?= base_url($CMSNT->site('lempay_icon') ?: ''); ?>" alt="<?= $CMSNT->site('lempay_name') ?: 'LemPay'; ?>" />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"><?= __('Phương thức thanh toán'); ?>:</label>
                        <div class="col-sm-8">
                            <select class="form-control" id="payment_type">
                                <option value="alipay">Alipay (支付宝)</option>
                                <option value="wxpay">WeChat Pay (微信支付)</option>
                                <option value="usdt">USDT (Tether)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"><?= __('Số tiền nạp (CNY)'); ?>:</label>
                        <div class="col-sm-8">
                            <input type="hidden" class="form-control" id="token" value="<?= $getUser['token']; ?>">
                            <input type="number" step="0.01" class="form-control" id="amount" placeholder="<?= __('Nhập số tiền cần nạp'); ?>" required>
                            <small class="text-muted">Min: <?= $CMSNT->site('lempay_min'); ?> CNY - Max: <?= $CMSNT->site('lempay_max'); ?> CNY</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"><?= __('Bạn sẽ nhận được'); ?>:</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" id="receive_amount" readonly placeholder="0">
                                <span class="input-group-text"><?= $CMSNT->get_row("SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code']; ?></span>
                            </div>
                        </div>
                    </div>
                    <center>
                        <div class="wallet-form">
                            <button type="button" id="btnSubmit"><?= __('Tạo hóa đơn'); ?></button>
                        </div>
                    </center>
                </div>
            </div>
            <div class="col-md-5">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Lưu ý'); ?></h4>
                    <?= $CMSNT->site('lempay_notice'); ?>
                    <?php if (empty($CMSNT->site('lempay_notice'))): ?>
                        <ul>
                            <li>Hỗ trợ thanh toán qua <strong>Alipay</strong>, <strong>WeChat Pay</strong> và <strong>USDT</strong></li>
                            <li>Số tiền sẽ được cộng tự động sau khi thanh toán thành công</li>
                            <li>Tỉ giá có thể thay đổi theo từng phương thức thanh toán</li>
                            <li>Nếu gặp vấn đề, vui lòng liên hệ hỗ trợ</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?= __('Lịch sử nạp tiền LemPay'); ?></h4>
                    <form action="<?= base_url(); ?>" method="GET">
                        <input type="hidden" name="action" value="recharge-lempay">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $trans_id; ?>" name="trans_id" placeholder="<?= __('Mã giao dịch'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?= $amount; ?>" name="amount" placeholder="<?= __('Số tiền'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <select class="form-control mb-1" name="type">
                                    <option value=""><?= __('Phương thức'); ?></option>
                                    <option <?= $type == 'alipay' ? 'selected' : ''; ?> value="alipay">Alipay</option>
                                    <option <?= $type == 'wxpay' ? 'selected' : ''; ?> value="wxpay">WeChat Pay</option>
                                    <option <?= $type == 'usdt' ? 'selected' : ''; ?> value="usdt">USDT</option>
                                </select>
                            </div>
                            <div class="col-lg col-md-6 col-6">
                                <input type="text" class="js-flatpickr form-control mb-1" name="time" placeholder="<?= __('Chọn thời gian'); ?>" value="<?= $time; ?>" data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?= base_url('?action=recharge-lempay'); ?>" class="shop-widget-btn mb-2"><i class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
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
                                    <th class="text-center"><?= __('Phương thức'); ?></th>
                                    <th class="text-center"><?= __('Số tiền'); ?></th>
                                    <th class="text-center"><?= __('Thực nhận'); ?></th>
                                    <th class="text-center"><?= __('Status'); ?></th>
                                    <th class="text-center"><?= __('Ngày tạo'); ?></th>
                                    <th class="text-center"><?= __('Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) { ?>
                                    <tr>
                                        <td class="text-center"><b><?= $row['trans_id']; ?></b></td>
                                        <td class="text-center"><?= getLempayTypeIcon($row['type']); ?></td>
                                        <td class="text-center"><b><?= $row['amount']; ?></b> CNY</td>
                                        <td class="text-center"><b style="color: red;"><?= format_currency($row['price']); ?></b></td>
                                        <td class="text-center"><?= display_invoice($row['status']); ?></td>
                                        <td class="text-center"><i class="far fa-calendar-alt mr-2 text-secondary"></i> <?= $row['created_at']; ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 0 && !empty($row['payurl'])): ?>
                                                <a class="btn btn-primary btn-sm" target="_blank" href="<?= $row['payurl']; ?>">
                                                    <i class="fas fa-credit-card mr-2"></i> <?= __('Pay Now'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?= __('Paid:'); ?>
                                            <strong style="color:red;"><?= format_currency($CMSNT->get_row_safe("SELECT SUM(`price`) FROM `payment_lempay` WHERE $where_clause AND `status` = 1", $where_params)['SUM(`price`)']); ?></strong>
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

<?php require_once(__DIR__ . '/footer.php'); ?>

<script type="text/javascript">
    // Rate configuration
    var rates = {
        'alipay': <?= $CMSNT->site('lempay_rate_alipay') ?: $CMSNT->site('lempay_rate'); ?>,
        'wxpay': <?= $CMSNT->site('lempay_rate_wxpay') ?: $CMSNT->site('lempay_rate'); ?>,
        'usdt': <?= $CMSNT->site('lempay_rate_usdt') ?: $CMSNT->site('lempay_rate'); ?>
    };

    // Calculate receive amount
    function calculateReceive() {
        var amount = parseFloat($("#amount").val()) || 0;
        var paymentType = $("#payment_type").val();
        var rate = rates[paymentType] || <?= $CMSNT->site('lempay_rate'); ?>;
        var receive = amount * rate;
        $("#receive_amount").val(receive.toLocaleString());
    }

    $("#amount, #payment_type").on("input change", calculateReceive);

    // Submit payment
    $("#btnSubmit").on("click", function() {
        var amount = parseFloat($("#amount").val()) || 0;
        if (amount <= 0) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Vui lòng nhập số tiền'); ?>', 'error');
            return;
        }

        $('#btnSubmit').html('<?= __("Đang xử lý..."); ?>').prop('disabled', true);

        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/create.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'RechargeLempay',
                token: $("#token").val(),
                amount: $("#amount").val(),
                payment_type: $("#payment_type").val()
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    window.open(respone.invoice_url, "_self");
                } else {
                    Swal.fire('<?= __('Error'); ?>', respone.msg, 'error');
                }
                $('#btnSubmit').html('<?= __('Tạo hóa đơn'); ?>').prop('disabled', false);
            },
            error: function() {
                Swal.fire('<?= __('Error'); ?>', '<?= __('Lỗi kết nối'); ?>', 'error');
                $('#btnSubmit').html('<?= __('Tạo hóa đơn'); ?>').prop('disabled', false);
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
                action: 'notication_topup_lempay',
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