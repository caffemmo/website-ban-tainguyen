<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}


$body = [
    'title' => __('Nạp tiền bằng thẻ cào').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">


';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('card_status') != 1){
    redirect(base_url('client/home'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');



if(isset($_GET['limit'])){
    $limit = validate_int($_GET['limit'], 5, 20000) ?: 10;
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = validate_int($_GET['page'], 1, 10000) ?: 1;
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];
$shortByDate = '';
$pin = '';
$time = '';
$serial = '';
$status = '';

if(!empty($_GET['status'])){
    $status = validate_string($_GET['status'], 20);
    if($status !== false) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}
if(!empty($_GET['pin'])){
    $pin = validate_string($_GET['pin'], 50);
    if($pin !== false) {
        $where_conditions[] = '`pin` LIKE ?';
        $where_params[] = '%'.$pin.'%';
    }
}
if(!empty($_GET['serial'])){
    $serial = validate_string($_GET['serial'], 50);
    if($serial !== false) {
        $where_conditions[] = '`serial` LIKE ?';
        $where_params[] = '%'.$serial.'%';
    }
}
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
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if($shortByDate == 1){
            $where_conditions[] = "`create_date` LIKE ?";
            $where_params[] = "%$currentDate%";
        }
        if($shortByDate == 2){
            $where_conditions[] = "YEAR(create_date) = ? AND WEEK(create_date, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = "MONTH(create_date) = ? AND YEAR(create_date) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `cards` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT COUNT(*) AS total FROM `cards` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($count_sql, $where_params)['total'] ?? 0;
$urlDatatable = pagination_client(base_url("?action=recharge-card&limit=$limit&shortByDate=$shortByDate&time=$time&pin=$pin&serial=$serial&status=$status&"), $from, $totalDatatable, $limit);
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-paper-plane m-2"></i>
                        <?=mb_strtoupper(__('Nạp tiền bằng thẻ cào tự động'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Loại thẻ');?></label>
                        <div class="col-lg-8 fv-row">
                        <select class="form-control" id="telco">
                            <option value="">-- <?= __('Chọn loại thẻ'); ?> --</option>
                            <?php
                            // Lấy dữ liệu từ cấu hình
                            $list_network_topup_card = $CMSNT->site('list_network_topup_card');
                            // Tách các dòng dữ liệu
                            $cards = explode("\n", $list_network_topup_card);
                            foreach ($cards as $card) {
                                $card = trim($card);
                                if(!$card) {
                                    continue;
                                }
                                // Tách thành mảng theo dấu |
                                $arr = explode('|', $card);
                                if(count($arr) == 2) {
                                    echo '<option value="'.$arr[0].'">'.$arr[1].'</option>';
                                }
                            }
                            ?>
                        </select>

                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Mệnh giá');?></label>
                        <div class="col-lg-8 fv-row">
                            <select class="form-control" onchange="totalPrice()" id="amount">
                                <option value="">-- <?=__('Chọn mệnh giá');?> --</option>
                                <option value="10000">10.000đ</option>
                                <option value="20000">20.000đ</option>
                                <option value="30000">30.000đ</option>
                                <option value="50000">50.000đ</option>
                                <option value="100000">100.000đ</option>
                                <option value="200000">200.000đ</option>
                                <!--<option value="300000">300.000đ</option>-->
                                <option value="500000">500.000đ</option>
                                <option value="1000000">1.000.000đ</option>
                                <option value="2000000">2.000.000đ</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Serial');?></label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" id="serial" class="form-control"
                                placeholder="<?=__('Nhập serial thẻ');?>" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Pin');?></label>
                        <div class="col-lg-8 fv-row">
                            <input type="text" id="pin" class="form-control" placeholder="<?=__('Nhập mã thẻ');?>" />
                            <input type="hidden" id="token" class="form-control" value="<?=$getUser['token'];?>" />
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <div class="alert bg-white alert-info" role="alert">
                            <div class="iq-alert-icon">
                                <i class="ri-alert-line"></i>
                            </div>
                            <div class="iq-alert-text"><?=__('Số tiền thực nhận');?>: <b id="ketqua"
                                    style="color: red;">0</b></div>
                        </div>
                    </div>
                    <center>
                        <div class="wallet-form">
                            <button type="button" id="submit"><?=__('NẠP NGAY');?></button>
                        </div>
                    </center>
                </div>
            </div>
            <div class="col-md-5">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-triangle-exclamation m-2"></i> <?=mb_strtoupper(__('Lưu ý'));?>
                    </h3>
                </div>
                <div class="account-card p-3">
                    <?=$CMSNT->site('card_notice');?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-clock-rotate-left m-2"></i> <?=mb_strtoupper(__('Lịch sử nạp thẻ'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="<?=base_url();?>" method="GET" class="mb-3">
                        <input type="hidden" name="action" value="recharge-card">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-2" value="<?=$pin;?>" name="pin"
                                    placeholder="<?=__('Pin');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-2" value="<?=$serial;?>" name="serial"
                                    placeholder="<?=__('Serial');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <select class="form-select mb-2" name="status">
                                    <option value=""><?=__('Trạng thái');?></option>
                                    <option <?=$status == 'pending' ? 'selected' : '';?> value="pending">
                                        <?=__('Đang chờ xử lý');?></option>
                                    <option <?=$status == 'error' ? 'selected' : '';?> value="error">
                                        <?=__('Thẻ lỗi');?></option>
                                    <option <?=$status == 'completed' ? 'selected' : '';?> value="completed">
                                        <?=__('Thành công');?></option>
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
                                <a href="<?=base_url('?action=recharge-card');?>" class="shop-widget-btn mb-2"><i
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
                                    <th class="text-center"><?=__('Nhà mạng');?></th>
                                    <th class="text-center"><?=__('Serial');?></th>
                                    <th class="text-center"><?=__('Pin');?></th>
                                    <th class="text-center"><?=__('Mệnh giá');?></th>
                                    <th class="text-center"><?=__('Thực nhận');?></th>
                                    <th class="text-center"><?=__('Trạng thái');?></th>
                                    <th class="text-center"><?=__('Create date');?></th>
                                    <th class="text-center"><?=__('Update date');?></th>
                                    <th class="text-center"><?=__('Lý do');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row2) {?>
                                <tr>
                                    <td class="text-center"><?=$row2['telco'];?></td>
                                    <td class="text-center"><?=$row2['serial'];?></td>
                                    <td class="text-center"><?=$row2['pin'];?></td>
                                    <td class="text-right"><b
                                            style="color: red;"><?=format_currency($row2['amount']);?></b></td>
                                    <td class="text-right"><b
                                            style="color: green;"><?=format_currency($row2['price']);?></b></td>
                                    <td class="text-center"><?=display_card($row2['status']);?></td>
                                    <td><?=$row2['create_date'];?></td>
                                    <td><?=$row2['update_date'];?></td>
                                    <td><?=$row2['reason'];?></td>
                                </tr>
                                <?php }?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?=__('Đã thanh toán:');?>
                                            <strong style="color:red;"><?php
$sum_sql = "SELECT SUM(`received`) AS total FROM `payment_crypto` WHERE $where_clause AND `status` = ?";
$sum = $CMSNT->get_row_safe($sum_sql, array_merge($where_params, ['completed']))['total'] ?? 0;
echo format_currency($sum);
?></strong>
                                            | <?=__('Chưa thanh toán:');?>
                                            <strong style="color:blue;"><?php
$sum_waiting_sql = "SELECT SUM(`received`) AS total FROM `payment_crypto` WHERE $where_clause AND `status` = ?";
$sum_waiting = $CMSNT->get_row_safe($sum_waiting_sql, array_merge($where_params, ['waiting']))['total'] ?? 0;
echo format_currency($sum_waiting);
?></strong>
                                        </div>
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
function totalPrice() {
    var total = 0;
    var amount = $("#amount").val();
    total = amount - amount * <?=$CMSNT->site('card_ck');?> / 100;
    $('#ketqua').html(total.toString().replace(/(.)(?=(\d{3})+$)/g, '$1.'));
}
</script>
<script type="text/javascript">
$("#submit").on("click", function() {
    $('#submit').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?>').prop(
        'disabled',
        true);
    $.ajax({
        url: "<?=base_url('ajaxs/client/create.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'nap_the',
            token: $("#token").val(),
            serial: $('#serial').val(),
            pin: $('#pin').val(),
            telco: $('#telco').val(),
            amount: $('#amount').val()
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire({
                    title: '<?=__('Successful !');?>',
                    text: respone.msg,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#submit').html(
                    '<?=__('NẠP NGAY');?>')
                .prop('disabled', false);
        },
        error: function() {
            Swal.fire('<?=__('Failure!');?>', 'Không thể xử lý', 'error');
            $('#submit').html(
                    '<?=__('NẠP NGAY');?>')
                .prop('disabled', false);
        }

    });
});
</script>