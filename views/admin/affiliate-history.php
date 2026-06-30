<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Affiliate History',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>

';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_affiliate') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}

if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}

$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$user_id = '';
$reason = '';
$create_gettime = '';
$username = '';
$shortByDate  = '';

if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `user_id` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `user_id` =  "" ';
    }
}
if(!empty($_GET['user_id'])){
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = "'.$user_id.'" ';
}
if(!empty($_GET['reason'])){
    $reason = check_string($_GET['reason']);
    $where .= ' AND `reason` LIKE "%'.$reason.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);
    if($create_gettime_1[0] != $create_gettime_1[1]){
        $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_gettime_1[0]."' AND `create_gettime` <= '".$create_gettime_1[1]."' ";
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `create_gettime` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}



$listDatatable = $CMSNT->get_list(" SELECT * FROM `aff_log` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `aff_log` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("affiliate-history&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&reason=$reason&create_gettime=$create_gettime&username=$username&"), $from, $totalDatatable, $limit);

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Nhật ký hoa hồng</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Affiliate Program</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Nhật ký hoa hồng</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            NHẬT KÝ HOA HỒNG
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="affiliate-history">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$reason;?>" name="reason"
                                        placeholder="<?=__('Hành động');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('affiliate-history');?>"><i class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
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
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th><?=__('Username');?></th>
                                        <th class="text-center"><?=__('Hoa hồng ban đầu');?></th>
                                        <th class="text-center"><?=__('Hoa hồng thay đổi');?></th>
                                        <th class="text-center"><?=__('Hoa hồng hiện tại');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Lý do');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                        </td>
                                        <td class="text-right"><span
                                                class="badge bg-success-gradient"><?=format_currency($row['sotientruoc']);?></span>
                                        </td>
                                        <td class="text-right"><span
                                                class="badge bg-danger-gradient"><?=format_currency($row['sotienthaydoi']);?></span>
                                        </td>
                                        <td class="text-right"><span
                                                class="badge bg-primary-gradient"><?=format_currency($row['sotienhientai']);?></span>
                                        </td>
                                        <td class="text-center"><span
                                                class="badge bg-light text-dark"><?=$row['create_gettime'];?></span>
                                        </td>
                                        <td><i><?=$row['reason'];?></i></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?>
                                    Results</p>
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



<?php
require_once(__DIR__.'/footer.php');
?>