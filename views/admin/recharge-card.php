<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Card'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

';
$body['footer'] = '
 
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_recharge') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình nạp thẻ cào')
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
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền thẻ cào'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("' . __('Save successfully!') . '")){window.history.back().location.reload();}</script>');
}

if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = check_string(intval($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0  ";
$shortByDate = '';
$pin = '';
$createdate = '';
$serial = '';
$status = '';
$user_id = '';
$username = '';

if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if ($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")) {
        $where .= ' AND `user_id` =  "' . $idUser['id'] . '" ';
    } else {
        $where .= ' AND `user_id` =  "" ';
    }
}
if (!empty($_GET['user_id'])) {
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = ' . $user_id . ' ';
}
if (!empty($_GET['status'])) {
    $status = check_string($_GET['status']);
    $where .= ' AND `status` = "' . $status . '" ';
}
if (!empty($_GET['pin'])) {
    $pin = check_string($_GET['pin']);
    $where .= ' AND `pin` LIKE "%' . $pin . '%" ';
}
if (!empty($_GET['serial'])) {
    $serial = check_string($_GET['serial']);
    $where .= ' AND `serial` LIKE "%' . $serial . '%" ';
}
if (!empty($_GET['create_date'])) {
    $create_date = check_string($_GET['create_date']);
    $createdate = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);

    if ($create_date_1[0] != $create_date_1[1]) {
        $create_date_1 = [$create_date_1[0] . ' 00:00:00', $create_date_1[1] . ' 23:59:59'];
        $where .= " AND `create_date` >= '" . $create_date_1[0] . "' AND `create_date` <= '" . $create_date_1[1] . "' ";
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if ($shortByDate == 1) {
        $where .= " AND `create_date` LIKE '%" . $currentDate . "%' ";
    }
    if ($shortByDate == 2) {
        $where .= " AND YEAR(create_date) = $currentYear AND WEEK(create_date, 1) = $currentWeek ";
    }
    if ($shortByDate == 3) {
        $where .= " AND MONTH(create_date) = '$currentMonth' AND YEAR(create_date) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `cards` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `cards` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("recharge-card&limit=$limit&shortByDate=$shortByDate&create_date=$createdate&pin=$pin&serial=$serial&status=$status&user_id=$user_id&username=$username&"), $from, $totalDatatable, $limit);


$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");


$total_yesterday = intval($CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE  `status` = 'completed' AND `create_date` LIKE '%" . $yesterday . "%' ")['SUM(amount)']);
$total_today = $CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE  `status` = 'completed' AND `create_date` LIKE '%" . $currentDate . "%' ")['SUM(amount)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE  `status` = 'completed' ")['SUM(amount)'];

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Nạp tiền bằng thẻ Điện Thoại, thẻ Game</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Nạp tiền</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Card</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <?php if (checkPermission($getUser['admin'], 'delete_recharge') == true): ?>
                        <button class="btn btn-danger label-btn mb-3 me-2" onclick="cleanupRechargeCard()">
                            <i class="ri-delete-bin-line label-btn-icon me-2"></i> <?= __('Dọn dẹp lịch sử'); ?>
                        </button>
                    <?php endif; ?>
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
                                            for="example-hf-email"><?= __('Trạng thái'); ?></label>
                                        <div class="col-sm-8">
                                            <select class="form-control" name="card_status">
                                                <option <?= $CMSNT->site('card_status') == 1 ? 'selected' : ''; ?>
                                                    value="1">ON
                                                </option>
                                                <option <?= $CMSNT->site('card_status') == 0 ? 'selected' : ''; ?>
                                                    value="0">OFF
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Partner ID
                                            <small><a target="_blank" class="text-primary"
                                                    href="https://card24h.com/">CARD24H.COM</a></small></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('card_partner_id'); ?>" name="card_partner_id">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Partner Key
                                            <small><a target="_blank" class="text-primary"
                                                    href="https://card24h.com/">CARD24H.COM</a></small></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('card_partner_key'); ?>" name="card_partner_key">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="example-hf-email">Phí nạp
                                            thẻ</label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="<?= $CMSNT->site('card_ck'); ?>" name="card_ck"
                                                    placeholder="VD: 10 = 10%">
                                                <span class="input-group-text">
                                                    %
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="example-hf-email">Loại thẻ</label>
                                        <div class="col-sm-6">
                                            <textarea class="form-control" placeholder="VIETTEL|Viettel
VINAPHONE|Vinaphone
MOBIFONE|Mobifone
VNMOBI|Vietnamobile
ZING|Zing
VCOIN|Vcoin
GARENA|Garena (chỉ nhận thẻ trên 10k)
" rows="5" name="list_network_topup_card"><?= $CMSNT->site('list_network_topup_card'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12">
                                    <p>Hướng dẫn sử dụng: <a target="_blank" class="text-primary"
                                            href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-the-cao-trong-shopclone7/">https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-the-cao-trong-shopclone7/</a>
                                    </p>
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?= __('Note'); ?></label>
                                        <div class="col-sm-12">
                                            <textarea id="card_notice"
                                                name="card_notice"><?= $CMSNT->site('card_notice'); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" name="SaveSettings" class="btn btn-primary btn-block"><i
                                        class="fa fa-fw fa-save me-1"></i>
                                    <?= __('Save'); ?></button>
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
                                            <?= format_currency($total_all_time); ?></p>
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
                                            <?= format_currency($CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE  `status` = 'completed' AND MONTH(create_date) = '$currentMonth' AND YEAR(create_date) = '$currentYear' ")['SUM(amount)']); ?>
                                        </p>
                                        <p class="mb-0 text-muted">Tháng <?= date('m'); ?></p>
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
                                            <?= format_currency($CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE  `status` = 'completed' AND YEAR(create_date) = $currentYear AND WEEK(create_date, 1) = $currentWeek ")['SUM(amount)']); ?>
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
                                            <?= format_currency($total_today); ?></p>
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
                        <div class="card-title">THỐNG KÊ NẠP THẺ THÁNG <?= date('m'); ?></div>
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
                                        label: 'Paid',
                                        backgroundColor: 'rgb(132, 90, 223)',
                                        borderColor: 'rgb(132, 90, 223)',
                                        data: [
                                            <?php
                                            $data = [];
                                            for ($day = 1; $day <= $numOfDays; $day++) {
                                                $date = "$year-$month-$day";
                                                $row = $CMSNT->get_row("SELECT SUM(amount) FROM cards WHERE DATE(create_date) = '$date' AND  `status` = 'completed' ");
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
                            LỊCH SỬ NẠP THẺ CÀO
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="recharge-card">
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?= $user_id; ?>" name="user_id"
                                        placeholder="<?= __('Search ID User'); ?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?= $username; ?>" name="username"
                                        placeholder="<?= __('Search Username'); ?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?= $pin; ?>" name="pin"
                                        placeholder="<?= __('Search Pin'); ?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?= $serial; ?>" name="serial"
                                        placeholder="<?= __('Search Serial'); ?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control form-control-sm mb-1" name="status">
                                        <option value=""><?= __('Status'); ?></option>
                                        <option <?= $status == 'pending' ? 'selected' : ''; ?> value="pending">
                                            <?= __('Đang chờ xử lý'); ?></option>
                                        <option <?= $status == 'error' ? 'selected' : ''; ?> value="error">
                                            <?= __('Thẻ lỗi'); ?></option>
                                        <option <?= $status == 'completed' ? 'selected' : ''; ?> value="completed">
                                            <?= __('Thành công'); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_date" class="form-control form-control-sm"
                                        id="daterange" value="<?= $createdate; ?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?= __('Search'); ?>
                                    </button>
                                    <a class="btn btn-sm btn-danger" href="<?= base_url_admin('recharge-card'); ?>"><i
                                            class="fa fa-trash"></i>
                                        <?= __('Clear filter'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('Hôm nay'); ?>
                                        </option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2">
                                            <?= __('Tuần này'); ?>
                                        </option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3">
                                            <?= __('Tháng này'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead class="table">
                                    <tr>
                                        <th><?= __('Username'); ?></th>
                                        <th class="text-center"><?= __('Telco'); ?></th>
                                        <th class="text-center"><?= __('Serial'); ?></th>
                                        <th class="text-center"><?= __('Pin'); ?></th>
                                        <th class="text-center"><?= __('Mệnh giá'); ?></th>
                                        <th class="text-center"><?= __('Thực nhận'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th class="text-center"><?= __('Create date'); ?></th>
                                        <th class="text-center"><?= __('Update date'); ?></th>
                                        <th class="text-center"><?= __('Lý do'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row2): ?>
                                        <tr>
                                            <td class="text-center"><a class="text-primary"
                                                    href="<?= base_url_admin('user-edit&id=' . $row2['user_id']); ?>"><?= getRowRealtime("users", $row2['user_id'], "username"); ?>
                                                    [ID <?= $row2['user_id']; ?>]</a>
                                            </td>
                                            <td class="text-center"><?= $row2['telco']; ?></td>
                                            <td class="text-center"><?= $row2['serial']; ?></td>
                                            <td class="text-center"><?= $row2['pin']; ?></td>
                                            <td class="text-right"><b
                                                    style="color: red;"><?= format_currency($row2['amount']); ?></b></td>
                                            <td class="text-right"><b
                                                    style="color: green;"><?= format_currency($row2['price']); ?></b></td>
                                            <td class="text-center"><?= display_card($row2['status']); ?></td>
                                            <td><?= $row2['create_date']; ?></td>
                                            <td><?= $row2['update_date']; ?></td>
                                            <td><?= $row2['reason']; ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="float-right">
                                                <?= __('Tổng nạp:'); ?>
                                                <strong
                                                    style="color:red;"><?= format_currency($CMSNT->get_row(" SELECT SUM(`amount`) FROM `cards` WHERE $where AND `status` = 'completed' ")['SUM(`amount`)']); ?></strong>
                                                | <?= __('Thực nhận:'); ?>
                                                <strong
                                                    style="color:blue;"><?= format_currency($CMSNT->get_row(" SELECT SUM(`price`) FROM `cards` WHERE $where AND `status` = 'completed' ")['SUM(`price`)']); ?></strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= $limit; ?> of
                                    <?= format_cash($totalDatatable); ?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?php
require_once(__DIR__ . '/footer.php');
?>


<script>
    CKEDITOR.replace("card_notice");
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

<?php if (checkPermission($getUser['admin'], 'delete_recharge') == true): ?>
    <script>
        // Hàm dọn dẹp lịch sử nạp tiền Card
        function cleanupRechargeCard() {
            Swal.fire({
                title: '<?= __("Dọn dẹp lịch sử nạp tiền Card"); ?>',
                html: '<div class="text-start">' +
                    '<p class="mb-3"><?= __("Nhập số ngày cần giữ lại lịch sử. Tất cả lịch sử từ số ngày trở lên sẽ bị xóa."); ?></p>' +
                    '<p class="text-muted small mb-3"><?= __("Ví dụ: Nhập 30 sẽ xóa tất cả lịch sử từ 30 ngày trở lên"); ?></p>' +
                    '<input type="number" id="daysToKeep" class="form-control" min="1" placeholder="<?= __("Số ngày cần giữ lại"); ?>" value="30">' +
                    '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= __("Xóa"); ?>',
                cancelButtonText: '<?= __("Hủy"); ?>',
                preConfirm: () => {
                    const days = document.getElementById('daysToKeep').value;
                    if (!days || days < 1) {
                        Swal.showValidationMessage('<?= __("Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)"); ?>');
                        return false;
                    }
                    return days;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const daysToKeep = result.value;

                    Swal.fire({
                        title: '<?= __("Xác nhận xóa"); ?>',
                        text: '<?= __("Bạn có chắc chắn muốn xóa tất cả lịch sử nạp tiền Card từ"); ?> ' + daysToKeep + ' <?= __("ngày trở lên? Hành động này không thể hoàn tác!"); ?>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<?= __("Xác nhận xóa"); ?>',
                        cancelButtonText: '<?= __("Hủy"); ?>'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            Swal.fire({
                                title: '<?= __("Đang xử lý"); ?>...',
                                text: '<?= __("Vui lòng đợi"); ?>...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: '<?= base_url("ajaxs/admin/remove.php"); ?>',
                                method: 'POST',
                                dataType: 'JSON',
                                data: {
                                    action: 'cleanupRechargeCard',
                                    token: '<?= $getUser["token"]; ?>',
                                    days_to_keep: daysToKeep
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        Swal.fire({
                                            title: '<?= __("Thành công"); ?>',
                                            text: response.msg,
                                            icon: 'success',
                                            confirmButtonText: '<?= __("OK"); ?>'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: '<?= __("Lỗi"); ?>',
                                            text: response.msg,
                                            icon: 'error',
                                            confirmButtonText: '<?= __("OK"); ?>'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: '<?= __("Lỗi"); ?>',
                                        text: '<?= __("Có lỗi xảy ra khi xóa lịch sử"); ?>',
                                        icon: 'error',
                                        confirmButtonText: '<?= __("OK"); ?>'
                                    });
                                }
                            });
                        }
                    });
                }
            });
        }
    </script>
<?php endif; ?>