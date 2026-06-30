<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Nạp tiền bằng Perfect Money').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('perfectmoney_status') != 1){
    redirect(base_url());
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


if ($CMSNT->get_row_safe("SELECT * FROM `payment_pm` WHERE `user_id` = ? AND `status` = 0", [$getUser['id']])) {
    $payment_id = $CMSNT->get_row_safe("SELECT * FROM `payment_pm` WHERE `user_id` = ? AND `status` = 0", [$getUser['id']])['payment_id'];
} else {
    $payment_id = random('QWERTYUIOPASDFGHJKLZXCVBNM', 4).'_'.time();
    $CMSNT->insert("payment_pm", [
        'user_id'       => $getUser['id'],
        'payment_id'    => $payment_id,
        'amount'        => 0,
        'create_date'   => gettime(),
        'create_time'   => time(),
        'update_date'   => gettime(),
        'update_time'   => time(),
        'status'        => 0
    ]);
}
$params = [
    'API_URL'               => 'https://perfectmoney.is/api/step1.asp',
    'PAYMENT_ID'            => $payment_id, // mã giao dịch không trùng lặp để lưu lên hệ thống
    'PAYEE_ACCOUNT'         => $CMSNT->site('perfectmoney_account'), // mã tài khoản Perfect Money
    'PAYMENT_UNITS'         => $CMSNT->site('perfectmoney_units'), // đơn vị tiền tệ,
    'PAYEE_NAME'            => $getUser['username'], // tên người thanh toán
    'PAYMENT_URL'           => base_url('?action=recharge-perfectmoney'), // URL của hoá đơn
    'NOPAYMENT_URL'         => base_url('?action=recharge-perfectmoney'), // URL của hoá đơn
    'STATUS_URL'            => base_url('api/callback-perfectmoney.php'), // Webhook callback
    'SUGGESTED_MEMO'        => 'Payment - '.$CMSNT->site('title')
];


$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Sử dụng prepared statements với validation
$where_conditions = ["`user_id` = ?", "`status` = 1"];
$where_params = [$getUser['id']];

$shortByDate = '';
$payment_id = '';
$time = '';
$amount = '';
$price = '';

// Validate payment_id
if(!empty($_GET['payment_id'])){
    $payment_id = validate_alphanumeric($_GET['payment_id'], 100);
    if($payment_id !== false) {
        $where_conditions[] = '`payment_id` LIKE ?';
        $where_params[] = '%'.$payment_id.'%';
    }
}

// Validate amount
if(!empty($_GET['amount'])){
    $amount = validate_float($_GET['amount'], 0.01, 999999.99);
    if($amount !== false) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}

// Validate price
if(!empty($_GET['price'])){
    $price = validate_float($_GET['price'], 0.01, 999999.99);
    if($price !== false) {
        $where_conditions[] = '`price` = ?';
        $where_params[] = $price;
    }
}

// Validate time range
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if(count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]){
            $start_date = $create_date_1[0].' 00:00:00';
            $end_date = $create_date_1[1].' 23:59:59';
            if(validate_date($create_date_1[0], 'Y/m/d') && validate_date($create_date_1[1], 'Y/m/d')) {
                $where_conditions[] = '`create_date` >= ? AND `create_date` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}

// Validate shortByDate
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        if($shortByDate == 1){
            $where_conditions[] = '`create_date` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(create_date) = ? AND WEEK(create_date, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(create_date) = ? AND YEAR(create_date) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_pm` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_pm` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-perfectmoney&limit=$limit&shortByDate=$shortByDate&time=$time&payment_id=$payment_id&amount=$amount&"), $from, $totalDatatable, $limit);

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-file-invoice m-2"></i>
                        <?=mb_strtoupper(__('Nạp tiền bằng Perfect Money'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form method="POST" action="<?=$params['API_URL']?>" target="_blank">
                        <div class="text-center mb-4">
                            <img width="300px" src="<?=base_url('assets/img/PerfectMoney.png');?>" />
                        </div>
                        <input type="hidden" name="SUGGESTED_MEMO" value="<?=$params['SUGGESTED_MEMO']?>">
                        <input type="hidden" name="PAYMENT_ID" value="<?=$params['PAYMENT_ID']?>" />
                        <input type="hidden" name="PAYEE_ACCOUNT" value="<?=$params['PAYEE_ACCOUNT']?>" />
                        <input type="hidden" name="PAYMENT_UNITS" value="<?=$params['PAYMENT_UNITS']?>" />
                        <input type="hidden" name="PAYEE_NAME" value="<?=$params['PAYEE_NAME']?>" />
                        <input type="hidden" name="PAYMENT_URL" value="<?=$params['PAYMENT_URL']?>" />
                        <input type="hidden" name="PAYMENT_URL_METHOD" value="LINK" />
                        <input type="hidden" name="NOPAYMENT_URL" value="<?=$params['NOPAYMENT_URL']?>" />
                        <input type="hidden" name="NOPAYMENT_URL_METHOD" value="LINK" />
                        <input type="hidden" name="STATUS_URL" value="<?=$params['STATUS_URL']?>" />
                        <div class="row mb-3">
                            <label class="col-sm-4 col-form-label"
                                for="example-hf-email"><?=__('Enter amount: (USD)');?></label>
                            <div class="col-sm-8">
                                <input type="hidden" class="form-control" id="token" value="<?=$getUser['token'];?>">
                                <input type="text" class="form-control" id="amount" name="PAYMENT_AMOUNT"
                                    placeholder="<?=__('Vui lòng nhập số tiền cần nạp');?>" required>
                            </div>
                        </div>
                        <center>
                            <div class="wallet-form">
                                <button type="submit" name="createPmCheckout"><?=__('Submit');?></button>
                            </div>
                        </center>
                    </form>
                </div>
            </div>
            <div class="col-md-5">
                <div class="home-heading mb-3">
                    <h3>
                        <i class="fa-solid fa-triangle-exclamation m-2"></i> 
                        <?=mb_strtoupper(__('Lưu ý'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <?=$CMSNT->site('perfectmoney_notice');?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="home-heading mb-3">
                    <h3>
                        <i class="fa-solid fa-clock-rotate-left m-2"></i>
                        <?=mb_strtoupper(__('Lịch sử nạp Perfect Money'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="<?=base_url();?>" method="GET">
                        <input type="hidden" name="action" value="recharge-perfectmoney">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$payment_id;?>" name="payment_id"
                                    placeholder="<?=__('Mã giao dịch');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$amount;?>" name="amount"
                                    placeholder="<?=__('Số tiền gửi');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$price;?>" name="price"
                                    placeholder="<?=__('Thực nhận');?>">
                            </div>
                            <div class="col-lg col-md-6 col-6">
                                <input type="text" class="js-flatpickr form-control mb-1" id="example-flatpickr-range"
                                    name="time" placeholder="<?=__('Chọn thời gian cần tìm');?>" value="<?=$time;?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?=__('Tìm kiếm');?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?=base_url('?action=recharge-perfectmoney');?>"
                                    class="shop-widget-btn mb-2"><i
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
                                    <th class="text-center"><?=__('Số tiền gửi');?></th>
                                    <th class="text-center"><?=__('Thực nhận');?></th>
                                    <th class="text-center"><?=__('Thời gian');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row2) {?>
                                <tr>
                                    <td class="text-center">
                                        <?=$row2['payment_id'];?>
                                    </td>
                                    <td class="text-center"><b><?=$row2['amount'];?> USD</b>
                                    </td>
                                    <td class="text-center"><b
                                            style="color: red;"><?=format_currency($row2['price']);?></b>
                                    </td>
                                    <td class="text-center"><?=$row2['create_date'];?></td>
                                </tr>
                                <?php }?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?=__('Đã thanh toán:');?>
                                            <strong
                                                style="color:red;"><?=format_currency($CMSNT->get_row_safe(" SELECT SUM(`price`) FROM `payment_pm` WHERE $where_clause ", $where_params)['SUM(`price`)']);?></strong>

                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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


<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>