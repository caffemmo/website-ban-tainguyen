<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('CTV Withdraw').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
';
$body['footer'] = '

';

require_once(__DIR__.'/../../models/is_ctv.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

// Phân trang cho lịch sử rút tiền
if(isset($_GET['limit'])){
    $limit = validate_int($_GET['limit'], 1, 1000);
    if ($limit === false) {
        $limit = 10;
    }
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = validate_int($_GET['page'], 1);
    if ($page === false) {
        $page = 1;
    }
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;

// Điều kiện lọc dữ liệu
$where = " `user_id` = ? ";
$where_params = [$getUser['id']];
$shortByDate = '';
$transid = '';
$time = '';
$status = '';
$stk = '';
$reason = '';

// Lọc theo mã giao dịch
if(!empty($_GET['transid'])){
    $transid = validate_alphanumeric($_GET['transid'], 50);
    if ($transid !== false) {
        $where .= ' AND `trans_id` = ? ';
        $where_params[] = $transid;
    }
}

// Lọc theo số tài khoản
if(!empty($_GET['stk'])){
    $stk = validate_string($_GET['stk'], 50);
    if ($stk !== false) {
        $where .= ' AND `stk` = ? ';
        $where_params[] = $stk;
    }
}

// Lọc theo lý do
if(!empty($_GET['reason'])){
    $reason = validate_string($_GET['reason'], 255);
    if ($reason !== false) {
        $where .= ' AND `reason` LIKE ? ';
        $where_params[] = "%$reason%";
    }
}

// Lọc theo trạng thái
if(!empty($_GET['status'])){
    $status = validate_string($_GET['status'], 20);
    if ($status !== false) {
        $where .= ' AND `status` = ? ';
        $where_params[] = $status;
    }
}

// Lọc theo thời gian
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if($create_gettime_1[0] != $create_gettime_1[1]){
            $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
            $where .= " AND `created_at` >= ? AND `created_at` <= ? ";
            $where_params[] = $create_gettime_1[0];
            $where_params[] = $create_gettime_1[1];
        }
    }
}

// Lọc theo ngày (hôm nay, tuần này, tháng này)
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if ($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if($shortByDate == 1){
            $where .= " AND `created_at` LIKE ? ";
            $where_params[] = "%$currentDate%";
        }
        if($shortByDate == 2){
            $where .= " AND YEAR(created_at) = ? AND WEEK(created_at, 1) = ? ";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where .= " AND MONTH(created_at) = ? AND YEAR(created_at) = ? ";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Lấy danh sách lịch sử rút tiền
$listDatatable = $CMSNT->get_list_safe("SELECT * FROM `ctv_withdraw` WHERE $where ORDER BY `id` DESC LIMIT ?, ?", array_merge($where_params, [$from, $limit]));
$totalDatatable = $CMSNT->num_rows_safe("SELECT * FROM `ctv_withdraw` WHERE $where ORDER BY id DESC", $where_params);

// Tính toán số dư CTV (tổng doanh thu - tổng rút tiền)
$totalRevenue = $CMSNT->get_row_safe("SELECT SUM(pay) as total FROM `product_order` WHERE `seller` = ? AND `trash` = 0", [$getUser['id']])['total'] ?? 0;
$totalWithdrawn = $CMSNT->get_row_safe("SELECT SUM(amount) as total FROM `ctv_withdraw` WHERE `user_id` = ? AND `status` = 'completed'", [$getUser['id']])['total'] ?? 0;
$totalFeePaid = $CMSNT->get_row_safe("SELECT SUM(fee) as total FROM `ctv_withdraw` WHERE `user_id` = ? AND `status` = 'completed'", [$getUser['id']])['total'] ?? 0;
$availableBalance = $getUser['money'];

// Pagination URL
$urlDatatable = pagination_client(base_url_ctv("ctv-withdraw?limit=$limit&shortByDate=$shortByDate&time=$time&transid=$transid&stk=$stk&reason=$reason&status=$status&"), $from, $totalDatatable, $limit);
?>

<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Start::page-header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <p class="fw-semibold fs-18 mb-0"><?=__('Rút tiền CTV');?></p>
            </div>
            <div class="btn-list mt-md-0 mt-2">
                <a href="<?=base_url_ctv('home');?>" class="btn btn-primary btn-wave">
                    <i class="ri-home-4-line me-2 d-inline-block"></i><?=__('Dashboard');?>
                </a>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Start::row-1 -->
        <div class="row">
            <!-- Form rút tiền -->
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <form id="withdrawForm">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label" for="bank"><?=__('Ngân hàng');?></label>
                                <div class="col-sm-8">
                                    <input type="hidden" class="form-control" id="token" value="<?=$getUser['token'];?>">
                                    <select class="form-control" id="bank" required>
                                        <option value="">-- <?=__('Chọn ngân hàng để rút tiền');?> --</option>
                                        <?php 
                                        // Lấy danh sách ngân hàng từ cấu hình site (có thể thay đổi theo yêu cầu)
                                        $listbank = explode(PHP_EOL, $CMSNT->site('ctv_banks_withdraw')); 
                                        ?>
                                        <?php foreach($listbank as $value):?>
                                        <option value="<?=$value;?>"><?=$value;?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label" for="stk"><?=__('Số tài khoản');?></label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="stk" 
                                        placeholder="<?=__('Vui lòng nhập số tài khoản');?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label" for="name"><?=__('Tên chủ tài khoản');?></label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="name" 
                                        placeholder="<?=__('Vui lòng nhập tên chủ tài khoản');?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label" for="amount"><?=__('Số tiền cần rút');?></label>
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" id="amount" 
                                        placeholder="<?=__('Vui lòng nhập số tiền cần rút');?>" 
                                        min="10000" max="<?=$availableBalance;?>" required>
                                    <small class="text-muted"><?=__('Số tiền tối thiểu:');?> <?=format_currency($CMSNT->site('ctv_min_withdraw'));?> - <?=__('Phí rút');?> <?=$CMSNT->site('ctv_fee_withdraw');?>%</small>
                                </div>
                            </div>
                            
                            <!-- Hiển thị phí rút và số tiền thực nhận -->
                            <div id="feeCalculation" style="display: none;">
                                <div class="row mb-2">
                                    <div class="col-sm-4">
                                        <label class="form-label text-muted"><?=__('Phí rút tiền');?>:</label>
                                    </div>
                                    <div class="col-sm-8">
                                        <span id="feeAmount" class="text-warning fw-bold">0 VND</span>
                                        <small class="text-muted d-block">(<?=$CMSNT->site('ctv_fee_withdraw');?>%)</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <label class="form-label text-muted"><?=__('Số tiền thực nhận');?>:</label>
                                    </div>
                                    <div class="col-sm-8">
                                        <span id="actualAmount" class="text-success fw-bold fs-5">0 VND</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="button" id="btnWithdraw" class="btn btn-primary btn-wave">
                                        <i class="ri-money-dollar-circle-line me-2"></i><?=__('Gửi yêu cầu rút tiền');?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Thống kê CTV -->
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="mb-3">
                        <?=$CMSNT->site('ctv_notice_withdraw');?>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                                    <div>
                                        <p class="mb-1 text-muted"><?=__('Số dư khả dụng');?></p>
                                        <h4 class="mb-0 text-primary"><?=format_currency($availableBalance);?></h4>
                                    </div>
                                    <div class="avatar avatar-md bg-primary-transparent">
                                        <i class="ri-wallet-3-line fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                                    <div>
                                        <p class="mb-1 text-muted"><?=__('Tổng doanh thu');?></p>
                                        <h5 class="mb-0"><?=format_currency($totalRevenue);?></h5>
                                    </div>
                                    <div class="avatar avatar-sm bg-success-transparent">
                                        <i class="ri-money-dollar-circle-line fs-16"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                                    <div>
                                        <p class="mb-1 text-muted"><?=__('Đã rút');?></p>
                                        <h5 class="mb-0"><?=format_currency($totalWithdrawn);?></h5>
                                    </div>
                                    <div class="avatar avatar-sm bg-warning-transparent">
                                        <i class="ri-exchange-line fs-16"></i>
                                    </div>
                                </div>
                            </div>
                            <?php if($CMSNT->site('ctv_fee_withdraw') > 0): ?>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                                    <div>
                                        <p class="mb-1 text-muted"><?=__('Tổng phí đã trả');?></p>
                                        <h5 class="mb-0 text-danger"><?=format_currency($totalFeePaid);?></h5>
                                    </div>
                                    <div class="avatar avatar-sm bg-danger-transparent">
                                        <i class="ri-money-dollar-box-line fs-16"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->

        <!-- Lịch sử rút tiền -->
        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title text-uppercase">
                            <?=__('Lịch sử rút tiền');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Form tìm kiếm và lọc -->
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="ctv">
                                <input type="hidden" name="action" value="ctv-withdraw">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$transid;?>" name="transid"
                                        placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$stk;?>" name="stk"
                                        placeholder="<?=__('Số tài khoản');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$reason;?>" name="reason"
                                        placeholder="<?=__('Lý do');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control form-control-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 'pending' ? 'selected' : '';?> value="pending">
                                            <?=__('Chờ xử lý');?></option>
                                        <option <?=$status == 'completed' ? 'selected' : '';?> value="completed">
                                            <?=__('Hoàn thành');?></option>
                                        <option <?=$status == 'cancel' ? 'selected' : '';?> value="cancel">
                                            <?=__('Hủy bỏ');?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="time" class="form-control form-control-sm"
                                        id="daterange" value="<?=$time;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_ctv('ctv-withdraw');?>"><i class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?=__('Show');?> :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
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
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <!-- Bảng lịch sử rút tiền -->
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead class="table">
                                    <tr>
                                        <th><?=__('Mã giao dịch');?></th>
                                        <th class="text-center"><?=__('Số tiền rút');?></th>
                                        <?php if($CMSNT->site('ctv_fee_withdraw') > 0): ?>
                                        <th class="text-center"><?=__('Phí rút');?></th>
                                        <th class="text-center"><?=__('Thực nhận');?></th>
                                        <?php endif; ?>
                                        <th><?=__('Tài khoản nhận tiền');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th><?=__('Thời gian');?></th>
                                        <th><?=__('Lý do');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($listDatatable)): ?>
                                        <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td><?=$row['trans_id'];?></td>
                                            <td class="text-right">
                                                <span class="badge bg-primary-gradient"><?=format_currency($row['amount']);?></span>
                                            </td>
                                            <?php if($CMSNT->site('ctv_fee_withdraw') > 0): ?>
                                            <td class="text-right">
                                                <span class="badge bg-warning-gradient"><?=format_currency($row['fee'] ?? 0);?></span>
                                            </td>
                                            <td class="text-right">
                                                <span class="badge bg-success-gradient"><?=format_currency($row['receive'] ?? $row['amount']);?></span>
                                            </td>
                                            <?php endif; ?>
                                            <td><?=$row['bank'];?> - <?=$row['stk'];?> - <?=$row['name'];?></td>
                                            <td class="text-center"><?=display_ctv_withdraw_status($row['status']);?></td>
                                            <td><span class="badge bg-light text-dark"><?=$row['created_at'];?></span></td>
                                            <td><?=$row['reason'];?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?=$CMSNT->site('ctv_fee_withdraw') > 0 ? '8' : '6';?>" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="ri-inbox-line fs-48 text-muted"></i>
                                                    <p class="text-muted mt-2"><?=__('Không có dữ liệu');?></p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?=__('Showing');?> <?=$limit;?> <?=__('of');?> <?=format_cash($totalDatatable);?>
                                    <?=__('Results');?></p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->

<?php require_once(__DIR__.'/footer.php'); ?>

<!-- Script tính toán phí rút tiền -->
<script type="text/javascript">
// Tính toán phí rút tiền khi user nhập số tiền
$("#amount").on("input", function() {
    var amount = parseFloat($(this).val()) || 0;
    
    if (amount > 0) {
        // Gọi AJAX để tính toán chính xác từ server
        $.ajax({
            url: "<?=BASE_URL('ajaxs/ctv/view.php');?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'calculateWithdrawFee',
                amount: amount,
                token: $('#token').val()
            },
            success: function(response) {
                if (response.status === 'success') {
                    $("#feeAmount").text(response.fee);
                    $("#actualAmount").text(response.receive);
                    $("#feeCalculation").show();
                } else {
                    $("#feeCalculation").hide();
                }
            },
            error: function() {
                $("#feeCalculation").hide();
            }
        });
    } else {
        $("#feeCalculation").hide();
    }
});


$("#btnWithdraw").on("click", function() {
    // Validate form
    var bank = $('#bank').val();
    var stk = $('#stk').val();
    var name = $('#name').val();
    var amount = $('#amount').val();
    
    if (!bank || !stk || !name || !amount) {
        Swal.fire({
            title: '<?=__('Lỗi');?>',
            text: '<?=__('Vui lòng điền đầy đủ thông tin');?>',
            icon: 'error'
        });
        return;
    }
    
    
    if (parseFloat(amount) < <?=$CMSNT->site('ctv_min_withdraw');?>) {
        Swal.fire({
            title: '<?=__('Lỗi');?>',
            text: '<?=__('Số tiền rút tối thiểu là').' '.format_currency($CMSNT->site('ctv_min_withdraw'));?>',
            icon: 'error'
        });
        return;
    }
    
    if (parseFloat(amount) > <?=getRowRealtime('users', $getUser['id'], 'money');?>) {
        Swal.fire({
            title: '<?=__('Lỗi');?>',
            text: '<?=__('Số tiền rút vượt quá số dư khả dụng');?>',
            icon: 'error'
        });
        return;
    }
    
    // Disable button và hiển thị loading
    $('#btnWithdraw').html('<i class="fa fa-spinner fa-spin"></i> <?=__("Đang xử lý...");?>').prop('disabled', true);
    
    // Tính toán phí rút để hiển thị trong thông báo
    var feePercent = <?=$CMSNT->site('ctv_fee_withdraw');?>;
    var fee = (parseFloat(amount) * feePercent) / 100;
    var receive = parseFloat(amount) - fee;
    
    $.ajax({
        url: "<?=BASE_URL('ajaxs/ctv/create.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'CtvWithdraw',
            token: $('#token').val(),
            bank: bank,
            stk: stk,
            name: name,
            amount: amount
        },
        success: function(result) {
            if (result.status == 'success') {
                var successMessage = result.msg;
                if (feePercent > 0) {
                    successMessage += '<br><br><strong><?=__('Chi tiết giao dịch:');?></strong><br>' +
                        '<?=__('Số tiền yêu cầu:');?> ' + new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(parseFloat(amount)) + '<br>' +
                        '<?=__('Phí rút tiền (');?>' + feePercent + '%): ' + new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(fee) + '<br>' +
                        '<?=__('Số tiền thực nhận:');?> ' + new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(receive);
                }
                
                Swal.fire({
                    title: '<?=__('Thành công');?>',
                    icon: 'success',
                    html: successMessage,
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire(
                    '<?=__('Thất bại');?>',
                    result.msg,
                    'error'
                );
            }
            // Reset button
            $('#btnWithdraw').html('<i class="ri-money-dollar-circle-line me-2"></i><?=__('Gửi yêu cầu rút tiền');?>')
                .prop('disabled', false);
        },
        error: function() {
            Swal.fire(
                '<?=__('Lỗi');?>',
                '<?=__('Có lỗi xảy ra, vui lòng thử lại');?>',
                'error'
            );
            // Reset button
            $('#btnWithdraw').html('<i class="ri-money-dollar-circle-line me-2"></i><?=__('Gửi yêu cầu rút tiền');?>')
                .prop('disabled', false);
        }
    });
});
</script>

<!-- Script cho date range picker -->
<script>
$(document).ready(function() {
    // Date range picker
    $('#daterange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });

    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});
</script>

