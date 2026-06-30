<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

 
$body = [
    'title' => __('Nạp tiền bằng ví THESIEURE').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


 
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];
$shortByDate = '';
$description = '';
$tid = '';
$time = '';

if(!empty($_GET['tid'])){
    $tid = validate_alphanumeric($_GET['tid'], 100);
    if($tid !== false) {
        $where_conditions[] = '`tid` = ?';
        $where_params[] = $tid;
    }
}
if(!empty($_GET['description'])){
    $description = validate_string($_GET['description'], 255);
    if($description !== false) {
        $where_conditions[] = '`description` LIKE ?';
        $where_params[] = '%'.$description.'%';
    }
}
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if($create_date_1[0] != $create_date_1[1]){
            $date_start = validate_date($create_date_1[0].' 00:00:00');
            $date_end = validate_date($create_date_1[1].' 23:59:59');
            if($date_start !== false && $date_end !== false) {
                $where_conditions[] = "`create_gettime` >= ? AND `create_gettime` <= ?";
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
            $where_conditions[] = "`create_gettime` LIKE ?";
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = "YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = "MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_thesieure` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_thesieure` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination_client(base_url("?action=recharge-thesieure&limit=$limit&shortByDate=$shortByDate&time=$time&tid=$tid&description=$description&"), $from, $totalDatatable, $limit);
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <?php if($CMSNT->num_rows(" SELECT * FROM `promotions` ") != 0):?>
            <div class="col-lg-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-percent m-2"></i> <?=mb_strtoupper(__('Khuyến mãi'));?>
                    </h3>
                </div>
                <div class="account-card p-0">
                    <table class="table fs-sm mb-0">
                        <thead>
                            <tr>
                                <th scope="col"><?=__('Số tiền nạp lớn hơn hoặc bằng');?></th>
                                <th scope="col"><?=__('Khuyến mãi thêm');?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1;foreach($CMSNT->get_list_safe("SELECT * FROM `promotions` ORDER BY `min` DESC", []) as $promotion):?>
                            <tr>
                                <td><b style="color: blue;"><?=format_currency($promotion['min']);?></b></td>
                                <td><b style="color: red;"><?=$promotion['discount'];?>%</b></td>
                            </tr>
                            <?php endforeach?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif?>
            <div class="col-lg-7">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-triangle-exclamation m-2"></i> <?=mb_strtoupper(__('Lưu ý nạp tiền'));?>
                    </h3>
                </div>
                <div class="account-card p-3">
                    <?=$CMSNT->site('thesieure_notice');?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="account-card">
                    <center class="py-3">
                        <h3><?=__('Nạp tiền bằng ví THESIEURE.COM');?></h3>
                    </center>
                    <ul class="list-group">
                        <li class="list-group-item"><?=__('Số điện thoại:');?> <b id="copySTK"
                                style="color: green;"><?=$CMSNT->site('thesieure_number');?></b> <button
                                onclick="copy()" class="copy" data-clipboard-target="#copySTK"><i
                                    class="fas fa-copy"></i></button>
                        </li>
                        <li class="list-group-item"><?=__('Địa chỉ Eamil:');?> <b id="copyEmail"
                                style="color: green;"><?=$CMSNT->site('thesieure_email');?></b> <button onclick="copy()"
                                class="copy" data-clipboard-target="#copyEmail"><i class="fas fa-copy"></i></button>
                        </li>
                        <li class="list-group-item" style="font-size:17px;"><?=__('Nội dung chuyển khoản:');?> <b
                                id="copyNoiDung"
                                style="color: red;"><?=$CMSNT->site('prefix_autobank').$getUser['id'];?></b>
                            <button onclick="copy()" class="copy" data-clipboard-target="#copyNoiDung"><i
                                    class="fas fa-copy"></i></button>

                        </li>
                        <li class="list-group-item"><?=__('Chủ tài khoản:');?>
                            <b><?=$CMSNT->site('thesieure_name');?></b>
                        </li>
                    </ul>
                    <center><small><?=__('Nhập đúng nội dung chuyển khoản để hệ thống cộng tiền tự động...');?></small></center>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-clock-rotate-left m-2"></i> <?=mb_strtoupper(__('Lịch sử nạp tiền'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="" method="GET" class="mb-3">
                        <input type="hidden" name="action" value="recharge-thesieure">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" value="<?=$tid;?>" name="tid"
                                    placeholder="<?=__('Mã giao dịch');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" value="<?=$description;?>" name="description"
                                    placeholder="<?=__('Nội dung chuyển khoản');?>">
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
                                <a href="<?=base_url('?action=recharge-thesieure');?>" class="shop-widget-btn mb-2"><i
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
                                    <th width="15%"><?=__('Thời gian');?></th>
                                    <th><?=__('Nội dung chuyển khoản');?></th>
                                    <th class="text-right"><?=__('Số tiền nạp');?></th>
                                    <th class="text-right"><?=__('Thực nhận');?></th>
                                    <th class="text-center"><?=__('Trạng thái');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row):?>
                                <tr>
                                    <td><b><?=$row['create_gettime'];?></b></td>
                                    <td>
                                        <small
                                            id="RB<?=$row['id'];?>"><?=substr($row['description'], 0, 30);?>...</small>
                                        <small class="hidden"
                                            id="hidden<?=$row['id'];?>"><?=$row['description'];?></small>
                                        <a href="javascript:void(0)" class="hidden"
                                            id="read-hide<?=$row['id'];?>"><?=__('Ẩn bớt');?></a>
                                        <a href="javascript:void(0)"
                                            id="read-more<?=$row['id'];?>"><?=__('Hiển thị thêm');?></a>
                                    </td>
                                    <td class="text-right"><b
                                            style="color: green;"><?=format_currency($row['amount']);?></b></td>
                                    <td class="text-right"><b
                                            style="color: red;"><?=format_currency($row['received']);?></b></td>
                                    <td class="fw-bold text-success text-center"><b><?=__('Đã thanh toán');?></b></td>
                                </tr>

                                <script>
                                $("#read-more<?=$row['id'];?>").click(function() {
                                    $("#hidden<?=$row['id'];?>").show(); // hiển thị nội dung đầy đủ
                                    $(this).hide(); // Ẩn nút hiển thị thêm
                                    $("#RB<?=$row['id'];?>").hide(); // Ẩn nội dung rút ngắn
                                    $("#read-hide<?=$row['id'];?>").show(); // hiển thị nút ẩn bớt
                                });
                                $("#read-hide<?=$row['id'];?>").click(function() {
                                    $("#hidden<?=$row['id'];?>").hide(); // ẩn nội dung
                                    $(this).hide(); // ẩn nút ẩn bớt
                                    $("#RB<?=$row['id'];?>").show(); // hiển thị nội dung rút ngắn
                                    $("#read-more<?=$row['id'];?>").show(); // hiện nút hiển thị thêm
                                });
                                </script>
                                <?php endforeach?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?=__('Đã thanh toán:');?>
                                            <strong
                                                style="color:red;"><?=format_currency($CMSNT->get_row_safe("SELECT SUM(`amount`) FROM `payment_thesieure` WHERE $where_clause", $where_params)['SUM(`amount`)']);?></strong>
                                            |

                                            <?=__('Thực nhận:');?>
                                            <strong
                                                style="color:blue;"><?=format_currency($CMSNT->get_row_safe("SELECT SUM(`received`) FROM `payment_thesieure` WHERE $where_clause", $where_params)['SUM(`received`)']);?></strong>
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

<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>

<script>
function loadData() {
    $.ajax({
        url: "<?=base_url('ajaxs/client/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'notication_topup_thesieure',
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