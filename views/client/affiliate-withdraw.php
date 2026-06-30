<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Affiliate Withdraw').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '

';

if($CMSNT->site('affiliate_status') != 1){
    redirect(base_url());
}


require_once(__DIR__.'/../../models/is_user.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');

// ✅ Sử dụng validation functions an toàn
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;

$from = ($page - 1) * $limit;

// ✅ Sử dụng prepared statements pattern  
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$transid = '';
$time = '';
$status = '';

// ✅ An toàn cho status filtering với whitelist
if(!empty($_GET['status'])){
    $status = validate_string($_GET['status'], 20);
    if($status !== false && in_array($status, ['pending', 'cancel', 'completed'])) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}

// ✅ An toàn cho transid filtering
if(!empty($_GET['transid'])){
    $transid = validate_alphanumeric($_GET['transid'], 50);
    if($transid !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $transid;
    }
}

// ✅ An toàn cho date range filtering
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if(count($create_gettime_1) == 2 && $create_gettime_1[0] != $create_gettime_1[1]){
            $start_date = $create_gettime_1[0].' 00:00:00';
            $end_date = $create_gettime_1[1].' 23:59:59';
            // Validate date format
            if(validate_date($create_gettime_1[0], 'Y/m/d') !== false && validate_date($create_gettime_1[1], 'Y/m/d') !== false) {
                $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}

// ✅ An toàn cho shortByDate filtering
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        
        if($shortByDate == 1){
            $where_conditions[] = '`create_gettime` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// ✅ Sử dụng prepared statements an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `aff_withdraw` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `aff_withdraw` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=affiliate-withdraw&limit=$limit&shortByDate=$shortByDate&time=$time&transid=$transid&"), $from, $totalDatatable, $limit);
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Rút số dư hoa hồng');?></h4>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Ngân hàng');?></label>
                        <div class="col-sm-8">
                            <input type="hidden" class="form-control" id="token" value="<?=$getUser['token'];?>">
                            <select class="form-control" id="bank">
                                <option value="">-- <?=__('Select the bank to withdraw');?> --</option>
                                <?php $listbank = explode(PHP_EOL, $CMSNT->site('affiliate_banks')); ?>
                                <?php foreach($listbank as $value):?>
                                <option value="<?=$value;?>"><?=$value;?></option>
                                <?php endforeach?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Số tài khoản');?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="stk"
                                placeholder="<?=__('Vui lòng nhập số tài khoản');?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Tên chủ tài khoản');?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="name"
                                placeholder="<?=__('Vui lòng nhập tên chủ tài khoản');?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Số tiền cần rút');?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="amount"
                                placeholder="<?=__('Vui lòng nhập số tiền cần rút');?>">
                        </div>
                    </div>
                    <center>
                        <div class="wallet-form">
                            <button type="button" id="btnWithdraw"><?=__('Submit');?></button>
                        </div>
                    </center>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Thông kê của bạn');?></h4>
                    <div class="my-wallet">
                        <p><?=__('Số tiền hoa hồng khả dụng');?></p>
                        <h3><?=format_currency($getUser['ref_price']);?></h3>
                    </div>
                    <div class="wallet-card-group">
                        <div class="wallet-card">
                            <p><?=__('Tổng số tiền hoa hồng đã nhận');?></p>
                            <h3><?=format_currency($getUser['ref_total_price']);?></h3>
                        </div>
                        <div class="wallet-card">
                            <p><?=__('Số lần nhấp vào liên kết');?></p>
                            <h3><?=format_cash($getUser['ref_click']);?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lịch sử rút tiền');?></h4>
                    <form action="" method="GET">
                        <input type="hidden" name="action" value="affiliate-withdraw">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" value="<?=$transid;?>" name="transid"
                                    placeholder="<?=__('Mã giao dịch');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <select class="form-select mb-2" name="status">
                                    <option value=""><?=__('Trạng thái');?></option>
                                    <option <?=$status == 'pending' ? 'selected' : '';?> value="pending">
                                        <?=__('Pending');?></option>
                                    <option <?=$status == 'cancel' ? 'selected' : '';?> value="cancel">
                                        <?=__('Cancel');?></option>
                                    <option <?=$status == 'completed' ? 'selected' : '';?> value="completed">
                                        <?=__('Completed');?></option>
                                </select>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input type="text" class="js-flatpickr form-control mb-2" id="example-flatpickr-range"
                                    name="time" placeholder="<?=__('Chọn thời gian cần tìm');?>" value="<?=$time;?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?=__('Tìm kiếm');?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?=base_url('?action=affiliate-withdraw');?>" class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?=__('Bỏ lọc');?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show"><label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                    <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                    <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                    <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                    <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                    <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                    <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                    <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
                                </select>
                            </div>
                            <div class="filter-short">
                                <label class="filter-label"><?=__('Short by Date:');?></label>
                                <select name="shortByDate" onchange="this.form.submit()"
                                    class="form-select filter-select">
                                    <option value=""><?=__('Tất cả');?></option>
                                    <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                    </option>
                                    <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?>
                                    </option>
                                    <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll">
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center"><?=__('Mã giao dịch');?></th>
                                    <th class="text-center"><?=__('Thời gian');?></th>
                                    <th class="text-center"><?=__('Số tiền rút');?></th>
                                    <th class="text-center"><?=__('Ngân hàng');?></th>
                                    <th class="text-center"><?=__('Trạng thái');?></th>
                                    <th class="text-center"><?=__('Lý do');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) {?>
                                <tr>
                                    <td class="text-center"><?=$row['trans_id'];?></td>
                                    <td class="text-center"><?=$row['create_gettime'];?></td>
                                    <td class="text-right"><b><?=format_currency($row['amount']);?></b></td>
                                    <td class="text-center"><?=$row['bank'];?> - <?=$row['stk'];?></td>
                                    <td class="text-center"><?=display_withdraw($row['status']);?></td>
                                    <td class="text-center"><small><?=$row['reason'];?></small></td>
                                </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($totalDatatable == 0):?>
                    <div class="empty-state">
                        <svg width="184" height="152" viewBox="0 0 184 152" xmlns="http://www.w3.org/2000/svg">
                            <g fill="none" fill-rule="evenodd">
                                <g transform="translate(24 31.67)">
                                    <ellipse fill-opacity=".8" fill="#F5F5F7" cx="67.797" cy="106.89" rx="67.797"
                                        ry="12.668"></ellipse>
                                    <path
                                        d="M122.034 69.674L98.109 40.229c-1.148-1.386-2.826-2.225-4.593-2.225h-51.44c-1.766 0-3.444.839-4.592 2.225L13.56 69.674v15.383h108.475V69.674z"
                                        fill="#AEB8C2"></path>
                                    <path
                                        d="M101.537 86.214L80.63 61.102c-1.001-1.207-2.507-1.867-4.048-1.867H31.724c-1.54 0-3.047.66-4.048 1.867L6.769 86.214v13.792h94.768V86.214z"
                                        fill="url(#linearGradient-1)" transform="translate(13.56)"></path>
                                    <path
                                        d="M33.83 0h67.933a4 4 0 0 1 4 4v93.344a4 4 0 0 1-4 4H33.83a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
                                        fill="#F5F5F7"></path>
                                    <path
                                        d="M42.678 9.953h50.237a2 2 0 0 1 2 2V36.91a2 2 0 0 1-2 2H42.678a2 2 0 0 1-2-2V11.953a2 2 0 0 1 2-2zM42.94 49.767h49.713a2.262 2.262 0 1 1 0 4.524H42.94a2.262 2.262 0 0 1 0-4.524zM42.94 61.53h49.713a2.262 2.262 0 1 1 0 4.525H42.94a2.262 2.262 0 0 1 0-4.525zM121.813 105.032c-.775 3.071-3.497 5.36-6.735 5.36H20.515c-3.238 0-5.96-2.29-6.734-5.36a7.309 7.309 0 0 1-.222-1.79V69.675h26.318c2.907 0 5.25 2.448 5.25 5.42v.04c0 2.971 2.37 5.37 5.277 5.37h34.785c2.907 0 5.277-2.421 5.277-5.393V75.1c0-2.972 2.343-5.426 5.25-5.426h26.318v33.569c0 .617-.077 1.216-.221 1.789z"
                                        fill="#DCE0E6"></path>
                                </g>
                                <path
                                    d="M149.121 33.292l-6.83 2.65a1 1 0 0 1-1.317-1.23l1.937-6.207c-2.589-2.944-4.109-6.534-4.109-10.408C138.802 8.102 148.92 0 161.402 0 173.881 0 184 8.102 184 18.097c0 9.995-10.118 18.097-22.599 18.097-4.528 0-8.744-1.066-12.28-2.902z"
                                    fill="#DCE0E6"></path>
                                <g transform="translate(149.65 15.383)" fill="#FFF">
                                    <ellipse cx="20.654" cy="3.167" rx="2.849" ry="2.815"></ellipse>
                                    <path d="M5.698 5.63H0L2.898.704zM9.259.704h4.985V5.63H9.259z"></path>
                                </g>
                            </g>
                        </svg>
                        <p><?=__('Không có dữ liệu');?></p>
                    </div>
                    <?php endif?>
                    <div class="bottom-paginate">
                        <p class="page-info">Showing <?=$limit;?> of <?=$totalDatatable;?> Results</p>
                        <div class="pagination">
                            <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php
require_once(__DIR__.'/footer.php');
?>

<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>
<script type="text/javascript">
$("#btnWithdraw").on("click", function() {
    $('#btnWithdraw').html('<i class="fa fa-spinner fa-spin"></i> <?=__("Processing...");?>').prop('disabled',
        true);
    $.ajax({
        url: "<?=BASE_URL('ajaxs/client/create.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'WithdrawCommission',
            token: $('#token').val(),
            bank: $('#bank').val(),
            stk: $('#stk').val(),
            name: $('#name').val(),
            amount: $('#amount').val()
        },
        success: function(result) {
            if (result.status == 'success') {
                Swal.fire({
                    title: '<?=__('Success');?>',
                    icon: 'success',
                    text: result.msg,
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire(
                    '<?=__('Failure');?>',
                    result.msg,
                    'error'
                );
            }
            $('#btnWithdraw').html('<?=__('SUBMIT');?>')
                .prop('disabled', false);
        }
    })
});
</script>
<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>