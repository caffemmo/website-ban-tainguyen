<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge MOMO'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_recharge') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_recharge') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if($checkKey['status'] != true){
        die('<script type="text/javascript">if(!alert("'.$checkKey['msg'].'")){window.history.back().location.reload();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình nạp MOMO')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình nạp MOMO'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("'.__('Save successfully!').'")){window.history.back().location.reload();}</script>');
} 

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
$where = " `id` > 0 ";
$shortByDate = '';
$description = '';
$tid = '';
$create_gettime = '';
$user_id = '';
$username = '';
$method = '';


if(!empty($_GET['method'])){
    $method = check_string($_GET['method']);
    $where .= ' AND `method` LIKE "%'.$method.'%" ';
}
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
    $where .= ' AND `user_id` = '.$user_id.' ';
}
if(!empty($_GET['tid'])){
    $tid = check_string($_GET['tid']);
    $where .= ' AND `tid` = "'.$tid.'" ';
}
if(!empty($_GET['description'])){
    $description = check_string($_GET['description']);
    $where .= ' AND `description` LIKE "%'.$description.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $create_date_1 = str_replace('-', '/', $create_gettime);
    $create_date_1 = explode(' to ', $create_date_1);
    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_date_1[0]."' AND `create_gettime` <= '".$create_date_1[1]."' ";
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `payment_momo` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `payment_momo` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("recharge-momo&limit=$limit&shortByDate=$shortByDate&create_gettime=$create_gettime&tid=$tid&description=$description&method=$method&"), $from, $totalDatatable, $limit);


 
$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");


$total_yesterday = intval($CMSNT->get_row("SELECT SUM(amount) FROM payment_momo WHERE  `create_gettime` LIKE '%".$yesterday."%' ")['SUM(amount)']);
$total_today = $CMSNT->get_row("SELECT SUM(amount) FROM payment_momo WHERE  `create_gettime` LIKE '%".$currentDate."%' ")['SUM(amount)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(amount) FROM payment_momo ")['SUM(amount)'];
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Nạp tiền bằng ví MOMO</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Nạp tiền</a></li>
                        <li class="breadcrumb-item active" aria-current="page">MOMO</li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if(time() - $CMSNT->site('check_time_cron_momo') >= 120):?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            Vui lòng thực hiện <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> liên kết: <a class="text-primary" href="<?=base_url('cron/momo.php?key='.$CMSNT->site('key_cron_job'));?>"
                target="_blank"><?=base_url('cron/momo.php?key='.$CMSNT->site('key_cron_job'));?></a> 1 phút 1 lần để hệ thống xử lý nạp tiền tự động.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <button type="button" id="open-card-config" class="btn btn-primary label-btn mb-3">
                        <i class="ri-settings-4-line label-btn-icon me-2"></i> CẤU HÌNH
                    </button>
                </div>
            </div>
            <div class="col-xl-12" id="card-config" style="display: none;">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">

                            <div class="row">
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label"
                                            for="example-hf-email"><?=__('Trạng thái');?></label>
                                        <div class="col-sm-8">
                                            <select class="form-control" name="momo_status">
                                                <option <?=$CMSNT->site('momo_status') == 1 ? 'selected' : '';?>
                                                    value="1">ON
                                                </option>
                                                <option <?=$CMSNT->site('momo_status') == 0 ? 'selected' : '';?>
                                                    value="0">OFF
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Số điện thoại
                                            MOMO</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?=$CMSNT->site('momo_number');?>" name="momo_number">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Chủ tài khoản
                                            MOMO</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?=$CMSNT->site('momo_name');?>" name="momo_name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="example-hf-email">Token API
                                            Auto</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control"
                                                value="<?=$CMSNT->site('momo_token');?>" name="momo_token">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Prefix');?></label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm"
                                                    value="<?=$CMSNT->site('prefix_autobank');?>" name="prefix_autobank"
                                                    placeholder="VD: NAPTIEN">
                                                <span class="input-group-text">
                                                    <?=$getUser['id'];?>
                                                </span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Note');?></label>
                                        <div class="col-sm-12">
                                            <textarea id="momo_notice"
                                                name="momo_notice"><?=$CMSNT->site('momo_notice');?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" name="SaveSettings" class="btn btn-primary btn-block"><i
                                        class="fa fa-fw fa-save me-1"></i>
                                    <?=__('Save');?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($total_all_time);?></p>
                                        <p class="mb-0 text-muted">Toàn thời gian</p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-danger rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM payment_momo WHERE  MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ")['SUM(amount)']);?>
                                        </p>
                                        <p class="mb-0 text-muted">Tháng <?=date('m');?></p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-info rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM payment_momo WHERE  YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ")['SUM(amount)']);?>
                                        </p>
                                        <p class="mb-0 text-muted">Trong tuần</p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-warning rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($total_today);?></p>
                                        <p class="mb-0 text-muted">Hôm nay
                                            <?php 
if ($total_yesterday != 0) {
    $revenueGrowth = ($total_today - $total_yesterday) / $total_yesterday * 100;
    if ($revenueGrowth > 0) {
        // tăng
      echo '<span class="fs-12 text-success ms-2"><i class="ti ti-trending-up me-1 d-inline-block"></i>' . round($revenueGrowth, 2) . '% </span>';
    } else if ($revenueGrowth < 0) {
        // giảm
      echo '<span class="fs-12 text-danger ms-2"><i class="ti ti-trending-down me-1 d-inline-block"></i>' . round(abs($revenueGrowth), 2) . '% </span>';
    }
  }
                            ?>

                                        </p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-primary rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">THỐNG KÊ NẠP TIỀN THÁNG <?=date('m');?></div>
                    </div>
                    <div class="card-body">
                        <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                        <script>
                        (function() {
                            /* line chart  */
                            Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)", Chart.defaults.color =
                                "#8c9097";
                            const labels = [
                                <?php
              $month = date('m');
              $year = date('Y');
              $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

              for ($day = 1; $day <= $numOfDays; $day++) {
                  echo "\"$day/$month/$year\",";
              }
              ?>
                            ];
                            const data = {
                                labels: labels,
                                datasets: [{
                                    label: 'Nạp tiền tự động',
                                    backgroundColor: 'rgb(132, 90, 223)',
                                    borderColor: 'rgb(132, 90, 223)',
                                    data: [
                                        <?php
                  $data = [];
                  for ($day = 1; $day <= $numOfDays; $day++) {
                      $date = "$year-$month-$day";
                      $row = $CMSNT->get_row("SELECT SUM(amount) FROM payment_momo WHERE DATE(create_gettime) = '$date' ");
                      $data[$day - 1] = $row['SUM(amount)'];
                  }
                  for ($i = 0; $i < $numOfDays; $i++) {
                      echo "$data[$i],";
                  }
                  ?>
                                    ],
                                }]
                            };
                            const config = {
                                type: 'bar',
                                data: data,
                                options: {}
                            };
                            const myChart = new Chart(
                                document.getElementById('chartjs-line'),
                                config
                            );



                        })();
                        </script>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            LỊCH SỬ NẠP TIỀN TỰ ĐỘNG
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="recharge-momo">
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('Tìm ID thành viên');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Tìm Username');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$tid;?>" name="tid"
                                        placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$method;?>" name="method"
                                        placeholder="<?=__('Ngân hàng');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$description;?>"
                                        name="description" placeholder="<?=__('Nội dung chuyển khoản');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-sm btn-danger" href="<?=base_url_admin('recharge-momo');?>"><i
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
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1">
                                            <?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2">
                                            <?=__('Tuần này');?>
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
                                        <th><?=__('Thời gian');?></th>
                                        <th class="text-right"><?=__('Số tiền nạp');?></th>
                                        <th class="text-right"><?=__('Thực nhận');?></th>
                                        <th class="text-center"><?=__('Ngân hàng');?></th>
                                        <th class="text-center"><?=__('Mã giao dịch');?></th>
                                        <th><?=__('Nội dung chuyển khoản');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td class="text-center"><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                        </td>
                                        <td><?=$row['create_gettime'];?></td>
                                        <td class="text-right"><b
                                                style="color: green;"><?=format_currency($row['amount']);?></b>
                                        </td>
                                        <td class="text-right"><b
                                                style="color: red;"><?=format_currency($row['received']);?></b>
                                        </td>
                                        <td class="text-center"><b><?=$row['method'];?></b></td>
                                        <td class="text-center"><b><?=$row['tid'];?></b></td>
                                        <td><small><?=$row['description'];?></small></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="float-right">
                                                <?=__('Đã thanh toán:');?>
                                                <strong
                                                    style="color:red;"><?=format_currency($CMSNT->get_row(" SELECT SUM(`amount`) FROM `payment_momo` WHERE $where ")['SUM(`amount`)']);?></strong>
                                                |

                                                <?=__('Thực nhận:');?>
                                                <strong
                                                    style="color:blue;"><?=format_currency($CMSNT->get_row(" SELECT SUM(`received`) FROM `payment_momo` WHERE $where ")['SUM(`received`)']);?></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of
                                    <?=format_cash($totalDatatable);?>
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




    <?php
require_once(__DIR__.'/footer.php');
?>


    <script>
    CKEDITOR.replace("momo_notice");
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var button = document.getElementById('open-card-config');
        var card = document.getElementById('card-config');

        // Thêm sự kiện click cho nút button
        button.addEventListener('click', function() {
            // Kiểm tra nếu card đang hiển thị thì ẩn đi, ngược lại hiển thị
            if (card.style.display === 'none' || card.style.display === '') {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
    </script>