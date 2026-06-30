<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge XiPay').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('gateway_xipay_status') != 1){
    redirect(base_url());
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');




$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;
$where_conditions = ["`user_id` = ?", "`status` = ?"];
$where_params = [$getUser['id'], 1];
$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';
$billName  = '';

if(!empty($_GET['trans_id'])){
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if($trans_id !== false) {
        $where_conditions[] = '`out_trade_no` = ?';
        $where_params[] = $trans_id;
    }
}
if(!empty($_GET['amount'])){
    $amount = validate_float($_GET['amount'], 0, 999999999);
    if($amount !== false) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if($create_gettime_1[0] != $create_gettime_1[1]){
            $date_start = validate_date($create_gettime_1[0].' 00:00:00');
            $date_end = validate_date($create_gettime_1[1].' 23:59:59');
            if($date_start !== false && $date_end !== false) {
                $where_conditions[] = "`created_at` >= ? AND `created_at` <= ?";
                $where_params[] = $date_start;
                $where_params[] = $date_end;
            }
        }
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if($shortByDate == 1){
            $where_conditions[] = "`created_at` LIKE ?";
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = "YEAR(created_at) = ? AND WEEK(created_at, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = "MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_xipay` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_xipay` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-xipay&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&"), $from, $totalDatatable, $limit);

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <!-- Form nhập số tiền nạp và lựa chọn phương thức thanh toán -->
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Recharge AliPay & WeChat Pay');?></h4>
                    <div class="text-center mb-4">
                         
                    </div>
                    <?php 
if(isset($_POST['WIDtotal_fee'])){

    require_once("libs/xipay.php");
    $epay_config['apiurl'] = 'https://pay.xipay.cc/';
    $epay_config['pid'] = $CMSNT->site('gateway_xipay_pid');
    $epay_config['key'] = $CMSNT->site('gateway_xipay_md5key');

    $notify_url = base_url('api/callback_xipay.php');
    $return_url = base_url('?action=recharge-xipay');
    $out_trade_no = random('QWERTYUIOPASDFGHKZXCVBNM123456789', 16);
    // Lấy loại thanh toán từ POST, kiểm tra nếu không phải 'alipay' hoặc 'wxpay' thì mặc định 'alipay'
    $type = validate_string($_POST['type'], 10);
    if ($type !== 'alipay' && $type !== 'wxpay') {
        $type = 'alipay';
    }
    $name = __('Deposit money into').' '.$getUser['username'];
    $amount = validate_float($_POST['WIDtotal_fee'], 0.01, 999999999);
    if($amount === false) {
        redirect(base_url('?action=recharge-xipay'));
    }
    $price = $amount * $CMSNT->site('gateway_xipay_rate');
    
    $parameter = array(
        "pid" => $epay_config['pid'],
        "type" => $type,
        "notify_url" => $notify_url,
        "return_url" => $return_url,
        "out_trade_no" => $out_trade_no,
        "name" => $name,
        "money"	=> $amount,
    );

    $CMSNT->insert('payment_xipay', [
        'user_id'       => $getUser['id'],
        'out_trade_no'  => $out_trade_no,
        'type'          => $type,
        'price'         => $price,
        'amount'        => $amount,
        'product_name'  => $name,
        'status'        => 0,
        'created_at'    => gettime()
    ]);
    
    $epay = new EpayCore($epay_config);
    $html_text = $epay->pagePay($parameter);
    echo $html_text;
    die('<p>'.__('Redirecting you to the payment page, please wait...').'</p>');
}
                    ?>
                    <form action="<?=base_url('?action=recharge-xipay');?>" method="POST">
    <div class="row mb-4">
        <label class="col-sm-4 col-form-label fw-bold" for="WIDtotal_fee"><?=__('Enter the deposit amount (元):');?></label>
        <div class="col-sm-8">
            <input type="hidden" id="token" class="form-control" value="<?=$getUser['token'];?>" />
            <input type="number" 
                   class="form-control form-control-lg border-primary" 
                   id="WIDtotal_fee" 
                   name="WIDtotal_fee" 
                   placeholder="100.00"
                   min="1"
                   step="0.01"
                   required>
        </div>
    </div>

    <div class="row mb-4">
        <label class="col-sm-4 col-form-label fw-bold"><?=__('Select Payment Method:');?></label>
        <div class="col-sm-8">
            <div class="payment-methods">
                <div class="row g-3">
                    <!-- Alipay Card -->
                    <div class="col-md-6">
                        <div class="method-card active" data-value="alipay">
                            <div class="method-logo">
                                <i class="fab fa-alipay text-primary fa-3x"></i>
                            </div>
                            <div class="method-info">
                                <h5 class="mb-1">Alipay</h5>
                                <small class="text-muted"><?=__('Thanh toán qua ứng dụng Alipay');?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- WeChat Pay Card -->
                    <div class="col-md-6">
                        <div class="method-card" data-value="wxpay">
                            <div class="method-logo">
                                <i class="fab fa-weixin text-success fa-3x"></i>
                            </div>
                            <div class="method-info">
                                <h5 class="mb-1">WeChat Pay</h5>
                                <small class="text-muted"><?=__('Thanh toán qua WeChat Wallet');?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="type_value" name="type" value="alipay" required>
        </div>
    </div>

    <div class="d-grid gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-2"></i><?=__('Submit');?>
        </button>
    </div>
</form>

<style>
.method-card {
    border: 2px solid #dee2e6;
    border-radius: 15px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.method-card:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.method-card.active {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.method-logo {
    flex-shrink: 0;
}
</style>

<script>
document.querySelectorAll('.method-card').forEach(card => {
    card.addEventListener('click', function() {
        // Remove active class from all cards
        document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked card
        this.classList.add('active');
        
        // Update hidden input value
        document.getElementById('type_value').value = this.dataset.value;
    });
});
</script>
                </div>
            </div>
            <div class="col-md-5">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lưu ý');?></h4>
                    <?=$CMSNT->site('xipay_notice');?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lịch sử nạp tiền');?></h4>
                    <form action="<?=base_url();?>" method="GET">
                        <input type="hidden" name="action" value="recharge-xipay">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$trans_id;?>"
                                    name="trans_id" placeholder="<?=__('Search transaction id');?>">
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
                                <a href="<?=base_url('?action=recharge-xipay');?>" class="shop-widget-btn mb-2"><i
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
                                    <th class="text-center"><?=__('Method');?></th>
                                    <th class="text-center"><?=__('Amount');?></th>
                                    <th class="text-center"><?=__('Price');?></th>
                                    <th class="text-center"><?=__('Create date');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) {?>
                                <tr>
                                    <td class="text-center"><b><?=$row['out_trade_no'];?></b></td>
                                    <td class="text-center"><?=display_method_xipay($row['type']);?></td>
                                    <td class="text-center"><span class="text-primary">¥<b><?=$row['amount'];?></b></span></td>
                                    <td class="text-center"><b class="text-danger"><?=format_currency($row['price']);?></b></td>
                                    <td class="text-center"><?=$row['created_at'];?></td>
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
 


 <script>
function loadData() {
    $.ajax({
        url: "<?=base_url('ajaxs/client/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'notication_topup_xipay',
            token: '<?=$getUser['token'];?>'
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '<?=__('Thành công !');?>',
                    text: respone.msg,
                    showDenyButton: true,
                    confirmButtonText: '<?=__('Nạp Thêm');?>',
                    denyButtonText: `<?=__('Mua Ngay');?>`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    } else if (result.isDenied) {
                        window.location.href = '<?=base_url();?>';
                    }
                });
            }
            setTimeout(loadData, 5000);
        },
        error: function() {
            setTimeout(loadData, 5000);
        }
    });
}
loadData();
</script>


<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>