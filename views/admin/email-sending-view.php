<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'View Sending Report',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>

';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');

if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `email_campaigns` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('email-campaigns'));
    }
} else {
    redirect(base_url_admin('email-campaigns'));
}


require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
if(checkPermission($getUser['admin'], 'view_email_campaigns') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
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
$shortByDate  = '';
$where = " `camp_id` = '$id'  ";
$status  = '';
$username = '';
$email = '';
$update_gettime = '';
$user_id = '';

if(!empty($_GET['status'])){
    $status = intval(check_string($_GET['status']));
    if($status == 1){
        $where .= ' AND `status` = 0 ';
    }else if($status == 2){
        $where .= ' AND `status` = 1 ';
    }else if($status == 3){
        $where .= ' AND `status` = 2 ';
    }else if($status == 4){
        $where .= ' AND `status` = 3 ';
    }
}
if(!empty($_GET['username'])){
    $username = check_string($_GET['username']);
    $user_id = $CMSNT->get_row(" SELECT `id` FROM `users` WHERE `username` = '$username' ")['id'];
    $where .= ' AND `user_id` = "'.$user_id.'" ';
}
if(!empty($_GET['email'])){
    $email = check_string($_GET['email']);
    $user_id = $CMSNT->get_row(" SELECT `id` FROM `users` WHERE `email` = '$email' ")['id'];
    $where .= ' AND `user_id` = "'.$user_id.'" ';
}
if(!empty($_GET['update_gettime'])){
    $create_date = check_string($_GET['update_gettime']);
    $update_gettime = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);

    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `update_gettime` >= '".$create_date_1[0]."' AND `update_gettime` <= '".$create_date_1[1]."' ";
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
        $where .= " AND `update_gettime` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(update_gettime) = $currentYear AND WEEK(update_gettime, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(update_gettime) = '$currentMonth' AND YEAR(update_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `email_sending` WHERE $where ORDER BY `id` ASC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `email_sending` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("email-sending-view&id=$id&limit=$limit&shortByDate=$shortByDate&update_gettime=$update_gettime&status=$status&email=$email&username=$username&"), $from, $totalDatatable, $limit);



?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">View Sending Report</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('email-campaigns');?>">Email
                                Campaigns</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Sending Report</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            VIEW SENDING REPORT
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?=base_url_admin('email-campaigns');?>"
                                class="btn btn-sm btn-danger btn-wave waves-light waves-effect waves-light"><i class="fa-solid fa-rotate-left"></i> Quay lại</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="email-sending-view">
                                <input type="hidden" name="id" value="<?=$id;?>">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$email;?>" name="email"
                                        placeholder="Email">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="update_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$update_gettime;?>"
                                        placeholder="Chọn thời gian hoàn thành">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin("email-sending-view&id=$id");?>"><i
                                            class="fa fa-trash"></i>
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
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th>Thời gian hoàn thành</th>
                                        <th>Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><i class="ace-icon fa fa-user bigger-130 mr-1"></i>
                                            <strong><?=getRowRealtime('users', $row['user_id'], 'username');?></strong>
                                        </td>
                                        <td><i class="ace-icon fa fa-envelope bigger-130 mr-1"></i>
                                            <strong><?=getRowRealtime('users', $row['user_id'], 'email');?></strong>
                                        </td>
                                        <td class="text-center"><?=display_camp($row['status']);?></td>
                                        <td><i class="fa-solid fa-clock mr-1"></i> <?=$row['update_gettime'];?></td>
                                        <td>
                                            <textarea class="form-control" rows="1"
                                                readonly><?=$row['response'];?></textarea>
                                        </td>
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