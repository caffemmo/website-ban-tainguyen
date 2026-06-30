<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Biến động số dư').' | '.$CMSNT->site('title'),
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


 
if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$shortByDate = '';
$reason = '';
$time = '';

// Xây dựng WHERE clause an toàn với prepared statements
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if(count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]){
            $start_date = $create_date_1[0].' 00:00:00';
            $end_date = $create_date_1[1].' 23:59:59';
            if(validate_date($create_date_1[0], 'Y/m/d') && validate_date($create_date_1[1], 'Y/m/d')) {
                $where_conditions[] = '`thoigian` >= ? AND `thoigian` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}
if(!empty($_GET['content'])){
    $reason = validate_string($_GET['content'], 255, 1);
    if($reason !== false) {
        $where_conditions[] = '`noidung` LIKE ?';
        $where_params[] = '%'.$reason.'%';
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        if($shortByDate == 1){
            $where_conditions[] = '`thoigian` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(thoigian) = ? AND WEEK(thoigian, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(thoigian) = ? AND YEAR(thoigian) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Xây dựng câu SQL an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `dongtien` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM `dongtien` WHERE $where_clause";
$totalResult = $CMSNT->get_row_safe($count_sql, $where_params);
$totalDatatable = $totalResult ? $totalResult['total'] : 0;
$urlDatatable = pagination_client(base_url("?action=transactions&limit=$limit&shortByDate=$shortByDate&content=$reason&time=$time&"), $from, $totalDatatable, $limit);

?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3">
                <?php require_once(__DIR__.'/sidebar.php');?>
            </div>
            <div class="col-lg-9">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Biến động số dư');?></h4>
                    <form action="" method="GET">
                        <input type="hidden" name="action" value="transactions">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" type="text" value="<?=$reason;?>" name="content"
                                    placeholder="<?=__('Lý do');?>">
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
                                <a href="<?=base_url('?action=transactions');?>" class="shop-widget-btn mb-2"><i
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
                    <div class="table-scroll table-wrapper">
                        <table class="table text-nowrap fs-sm mb-0">
                            <thead>
                                <th width="20%"><?=__('Thời gian');?></th>
                                <th class="text-center"><?=__('Số dư ban đầu');?></th>
                                <th class="text-center"><?=__('Số dư thay đổi');?></th>
                                <th class="text-center"><?=__('Số dư hiện tại');?></th>
                                <th><?=__('Lý do');?></th>
                            </thead>
                            <tbody></tbody>
                                <?php foreach ($listDatatable as $row) {?>
                                <tr>
                                    <td><?=$row['thoigian'];?></td>
                                    <td class="text-right"><b><?=format_currency($row['sotientruoc']);?></b>
                                    </td>
                                    <?php if(($row['sotiensau'] - $row['sotientruoc']) > 0):?>
                                        <td class="text-right"><b
                                                style="color:green;">+<?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php elseif(($row['sotientruoc'] - $row['sotiensau']) > 0):?>
                                        <td class="text-right"><b
                                                style="color:red;">-<?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php else:?>
                                        <td class="text-right"><b><?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php endif?>
                                    <td class="text-right"><b
                                            style="color: blue;"><?=format_currency($row['sotiensau']);?></b>
                                    </td>
                                    <td><small><?=$row['noidung'];?></small></td>
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
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>
<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>