<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Flutterwave').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('flutterwave_status') != 1){
    redirect(base_url());
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


if ($CMSNT->get_row_safe("SELECT * FROM `payment_flutterwave` WHERE `user_id` = ? AND `status` = 'pending'", [$getUser['id']])) {
    $tx_ref = $CMSNT->get_row_safe("SELECT * FROM `payment_flutterwave` WHERE `user_id` = ? AND `status` = 'pending'", [$getUser['id']])['tx_ref'];
} else {
    $tx_ref = md5(random('QWERTYUIOPASDFGHJKLZXCVBNM', 4).'_'.time());
    $CMSNT->insert("payment_flutterwave", [
        'user_id'       => $getUser['id'],
        'tx_ref'        => $tx_ref,
        'amount'        => 0,
        'currency'      => $CMSNT->site('flutterwave_currency_code'),
        'create_gettime'   => gettime(),
        'update_gettime'   => gettime(),
        'status'        => 'pending'
    ]);
}

$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Sử dụng prepared statements với validation
$where_conditions = ["`user_id` = ?", "`status` = 'success'"];
$where_params = [$getUser['id']];

$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';

// Validate trans_id
if(!empty($_GET['trans_id'])){
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if($trans_id !== false) {
        $where_conditions[] = '`tx_ref` = ?';
        $where_params[] = $trans_id;
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

// Validate time range
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if(count($create_gettime_1) == 2 && $create_gettime_1[0] != $create_gettime_1[1]){
            $start_date = $create_gettime_1[0].' 00:00:00';
            $end_date = $create_gettime_1[1].' 23:59:59';
            if(validate_date($create_gettime_1[0], 'Y/m/d') && validate_date($create_gettime_1[1], 'Y/m/d')) {
                $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
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

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_flutterwave` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_flutterwave` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-flutterwave&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&"), $from, $totalDatatable, $limit);

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Recharge Flutterwave');?></h4>
                    <div class="text-center mb-4">
                        <img width="300px" src="<?=base_url('mod/img/logo-flutterwave.webp');?>" />
                    </div>
                    <form method="POST" action="https://checkout.flutterwave.com/v3/hosted/pay">
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Enter the deposit amount: ('.$CMSNT->site('flutterwave_currency_code').')');?></label>
                        <div class="col-sm-8">
                            <input type="hidden" name="public_key" value="<?=$CMSNT->site('flutterwave_publicKey');?>" />
                            <input type="hidden" name="customer[email]" value="<?=$getUser['email'];?>" />
                            <input type="hidden" name="customer[name]" value="<?=$getUser['username'];?>" />
                            <input type="hidden" name="tx_ref" value="<?=$tx_ref;?>" />
                            <input type="text" class="form-control" name="amount"
                                placeholder="<?=__('Please enter the amount to deposit');?>" required>
                            
                            <input type="hidden" name="currency" value="<?=$CMSNT->site('flutterwave_currency_code');?>" />
                            <input type="hidden" name="meta[token]" value="<?=$tx_ref;?>" />
                            <input type="hidden" name="redirect_url" value="<?=base_url('?action=recharge-flutterwave');?>" />
                        </div>
                    </div>
                    <center>
                        <div class="wallet-form">
                            <button type="submit" id="start-payment-button"><?=__('Submit');?></button>
                        </div>
                    </center>
                    </form>
                </div>
            </div>
            <div class="col-md-5">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lưu ý');?></h4>
                    <?=$CMSNT->site('flutterwave_notice');?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lịch sử nạp Flutterwave');?></h4>
                    <form action="<?=base_url();?>" method="GET">
                        <input type="hidden" name="action" value="recharge-flutterwave">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$trans_id;?>"
                                    name="trans_id" placeholder="<?=__('Search transaction ref');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$amount;?>" name="amount"
                                    placeholder="<?=__('Search amount');?>">
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
                                <a href="<?=base_url('?action=recharge-flutterwave');?>" class="shop-widget-btn mb-2"><i
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
                                    <th class="text-center"><?=__('TransID');?></th>
                                    <th class="text-center"><?=__('Amount');?></th>
                                    <th class="text-center"><?=__('Price');?></th>
                                    <th class="text-center"><?=__('Create date');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) {?>
                                <tr>
                                    <td class="text-center"><b><?=$row['tx_ref'];?></b></td>
                                    <td class="text-center"><b><?=$row['amount'];?></b></td>
                                    <td class="text-center"><b><?=format_currency($row['price']);?></b></td>
                                    <td class="text-center"><?=$row['create_gettime'];?></td>
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


<script src="https://checkout.squadco.com/widget/squad.min.js"></script>

<script>
function SquadPay() {

    const squadInstance = new squad({
        onClose: () => console.log("Widget closed"),
        onLoad: () => console.log("Widget loaded successfully"),
        onSuccess: () => console.log(`Linked successfully`),
        key: "<?=$CMSNT->site('squadco_Public_Key');?>",
        //Change key (test_pk_sample-public-key-1) to the key on your Squad Dashboard
        email: document.getElementById("email").value,
        transaction_ref: document.getElementById("transaction_ref").value,
        currency_code: document.getElementById("currency_code").value,
        amount: document.getElementById("amount").value * 100
    });
    squadInstance.setup();
    squadInstance.open();

}
</script>

<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>