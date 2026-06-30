<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Crypto'),
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
        'action'        => __('Cấu hình nạp tiền Crypto')
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
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền Crypto'), $my_text);
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
$trans_id = '';
$createdate = '';
$amount = '';
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
if (!empty($_GET['trans_id'])) {
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` LIKE "%' . $trans_id . '%" ';
}
if (!empty($_GET['amount'])) {
    $amount = check_string($_GET['amount']);
    $where .= ' AND `amount` = ' . $amount . ' ';
}
if (!empty($_GET['create_gettime'])) {
    $create_gettime = check_string($_GET['create_gettime']);
    $createdate = $create_gettime;
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);

    if ($create_gettime_1[0] != $create_gettime_1[1]) {
        $create_gettime_1 = [$create_gettime_1[0] . ' 00:00:00', $create_gettime_1[1] . ' 23:59:59'];
        $where .= " AND `create_gettime` >= '" . $create_gettime_1[0] . "' AND `create_gettime` <= '" . $create_gettime_1[1] . "' ";
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
        $where .= " AND `create_gettime` LIKE '%" . $currentDate . "%' ";
    }
    if ($shortByDate == 2) {
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if ($shortByDate == 3) {
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `payment_crypto` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `payment_crypto` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("recharge-crypto&limit=$limit&shortByDate=$shortByDate&create_gettime=$createdate&trans_id=$trans_id&amount=$amount&status=$status&user_id=$user_id&username=$username&"), $from, $totalDatatable, $limit);


$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");


$total_yesterday = intval($CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE  `status` = 'completed' AND `create_gettime` LIKE '%" . $yesterday . "%' ")['SUM(received)']);
$total_today = $CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE  `status` = 'completed' AND `create_gettime` LIKE '%" . $currentDate . "%' ")['SUM(received)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE  `status` = 'completed' ")['SUM(received)'];

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-brands fa-bitcoin"></i> Phương thức thanh toán
                USDT</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="text-right">
                <?php if (checkPermission($getUser['admin'], 'delete_recharge') == true): ?>
                    <button class="btn btn-danger label-btn mb-3 me-2" onclick="cleanupRechargeCrypto()">
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
                                        <select class="form-control" name="crypto_status">
                                            <option <?= $CMSNT->site('crypto_status') == 1 ? 'selected' : ''; ?>
                                                value="1">ON
                                            </option>
                                            <option <?= $CMSNT->site('crypto_status') == 0 ? 'selected' : ''; ?>
                                                value="0">OFF
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label"
                                        for="example-hf-email"><?= __('Loại API'); ?></label>
                                    <div class="col-sm-8">
                                        <select class="form-control" name="crypto_type_api" id="crypto_type_api"
                                            onchange="toggleFields()">
                                            <!-- <option
                                                <?= $CMSNT->site('crypto_type_api') == 'fpayment.co' ? 'selected' : ''; ?>
                                                value="fpayment.co">FPAYMENT.CO | TRC20
                                            </option> -->
                                            <option
                                                <?= $CMSNT->site('crypto_type_api') == 'fpayment.net' ? 'selected' : ''; ?>
                                                value="fpayment.net">FPAYMENT.NET | TRC20, BEP20, POLYGON, SOLANA
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div id="fpaymentCoFields" class="crypto-fields">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Address</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('crypto_address'); ?>" name="crypto_address"
                                                placeholder="<?= __('Enter the wallet address to connect'); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email">Token API
                                            <small><a target="_blank" class="text-primary"
                                                    href="https://fpayment.co/">FPAYMENT.CO</a></small></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('crypto_token'); ?>" name="crypto_token"
                                                placeholder="<?= __('Enter verification token at API'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div id="fpaymentNetFields" class="crypto-fields" style="display: none;">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label"
                                            for="crypto_merchant_id">Merchant ID</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('crypto_merchant_id'); ?>"
                                                name="crypto_merchant_id">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="crypto_api_key">Api Key
                                            <small><a target="_blank" class="text-primary"
                                                    href="https://fpayment.net/">FPAYMENT.NET</a></small></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('crypto_api_key'); ?>" name="crypto_api_key">
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    function toggleFields() {
                                        const selectedAPI = document.getElementById('crypto_type_api').value;

                                        const fpaymentCoFields = document.getElementById('fpaymentCoFields');
                                        const fpaymentNetFields = document.getElementById('fpaymentNetFields');
                                        const hdsd_fpayment_co = document.getElementById('hdsd_fpayment_co');
                                        const hdsd_fpayment_net = document.getElementById('hdsd_fpayment_net');

                                        // Hiển thị/ẩn các trường input tương ứng
                                        if (selectedAPI === 'fpayment.co') {
                                            hdsd_fpayment_co.style.display = 'block';
                                            hdsd_fpayment_net.style.display = 'none';

                                            fpaymentCoFields.style.display = 'block';
                                            fpaymentNetFields.style.display = 'none';
                                        } else if (selectedAPI === 'fpayment.net') {
                                            fpaymentCoFields.style.display = 'none';
                                            fpaymentNetFields.style.display = 'block';

                                            hdsd_fpayment_co.style.display = 'none';
                                            hdsd_fpayment_net.style.display = 'block';
                                        }
                                    }

                                    // Gọi hàm để thiết lập trạng thái ban đầu khi tải trang
                                    window.onload = function() {
                                        toggleFields();
                                    };
                                </script>

                            </div>
                            <div class="col-lg-12 col-xl-6">
                                <div class="row mb-4">
                                    <label class="col-sm-6 col-form-label"
                                        for="example-hf-email"><?= __('Nạp tối thiểu'); ?></label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" value="<?= $CMSNT->site('crypto_min'); ?>"
                                            name="crypto_min" placeholder="VD: 10">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-6 col-form-label"
                                        for="example-hf-email"><?= __('Nạp tối đa'); ?></label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" value="<?= $CMSNT->site('crypto_max'); ?>"
                                            name="crypto_max" placeholder="VD: 100000">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-6 col-form-label"
                                        for="example-hf-email"><?= __('1 USDT ='); ?></label>
                                    <div class="col-sm-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                value="<?= $CMSNT->site('crypto_rate'); ?>" name="crypto_rate"
                                                placeholder="VD: 23000">
                                            <span class="input-group-text">
                                                <?= currencyDefault(); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-6 col-form-label"
                                        for="example-hf-email"><?= __('Khuyến mãi nạp tiền nếu có'); ?></label>
                                    <div class="col-sm-6">
                                        <textarea class="form-control" rows="4" placeholder="100|5 (tức nạp 100 USDT khuyến mãi 5%)
200|10 (tức nạp 200 USDT khuyến mãi 10%)
" name="crypto_promotions"><?= $CMSNT->site('crypto_promotions'); ?></textarea>
                                        <small>Nhập mốc nạp khuyến mãi nạp tiền nếu có, mỗi dòng là một mốc nạp.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12 col-xl-12">
                                <p style="display:none;" id="hdsd_fpayment_co">Hướng dẫn sử dụng: <a target="_blank" class="text-primary"
                                        href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-bang-usdt-trong-shopclone7/">https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-bang-usdt-trong-shopclone7/</a>
                                </p>
                                <p style="display:none;" id="hdsd_fpayment_net">Hướng dẫn sử dụng: <a target="_blank" class="text-primary"
                                        href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-usdt-thong-qua-api-fpayment-net/">https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-usdt-thong-qua-api-fpayment-net/</a>
                                </p>
                                <div class="row mb-4">
                                    <label class="col-sm-6 col-form-label"
                                        for="example-hf-email"><?= __('Crypto Note'); ?></label>
                                    <div class="col-sm-12">
                                        <textarea id="crypto_note"
                                            name="crypto_note"><?= $CMSNT->site('crypto_note'); ?></textarea>
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
                                        <?= format_currency($CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE  `status` = 'completed' AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ")['SUM(received)']); ?>
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
                                        <?= format_currency($CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE  `status` = 'completed' AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ")['SUM(received)']); ?>
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
                    <div class="card-title">THỐNG KÊ NẠP TIỀN THÁNG <?= date('m'); ?></div>
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
                                            $row = $CMSNT->get_row("SELECT SUM(received) FROM payment_crypto WHERE DATE(create_gettime) = '$date' AND  `status` = 'completed' ");
                                            $data[$day - 1] = $row['SUM(received)'];
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
                        LỊCH SỬ NẠP TIỀN CRYPTO
                    </div>
                </div>
                <div class="card-body">
                    <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                        <div class="row row-cols-lg-auto g-3 mb-3">
                            <input type="hidden" name="module" value="admin">
                            <input type="hidden" name="action" value="recharge-crypto">
                            <div class="col-md-3 col-6">
                                <input class="form-control form-control-sm" value="<?= $user_id; ?>" name="user_id"
                                    placeholder="<?= __('Search ID User'); ?>">
                            </div>
                            <div class="col-md-3 col-6">
                                <input class="form-control form-control-sm" value="<?= $username; ?>" name="username"
                                    placeholder="<?= __('Search Username'); ?>">
                            </div>
                            <div class="col-md-3 col-6">
                                <input class="form-control form-control-sm" value="<?= $trans_id; ?>" name="trans_id"
                                    placeholder="<?= __('Search trans id'); ?>">
                            </div>
                            <div class="col-md-3 col-6">
                                <input class="form-control form-control-sm" value="<?= $amount; ?>" name="amount"
                                    placeholder="<?= __('Search amount sent'); ?>">
                            </div>
                            <div class="col-md-3 col-6">
                                <select class="form-control form-control-sm mb-1" name="status">
                                    <option value=""><?= __('Status'); ?></option>
                                    <option <?= $status == 'waiting' ? 'selected' : ''; ?> value="waiting">
                                        <?= __('Waiting'); ?></option>
                                    <option <?= $status == 'expired' ? 'selected' : ''; ?> value="expired">
                                        <?= __('Expired'); ?></option>
                                    <option <?= $status == 'completed' ? 'selected' : ''; ?> value="completed">
                                        <?= __('Completed'); ?></option>
                                </select>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input type="text" name="create_gettime" class="form-control form-control-sm"
                                    id="daterange" value="<?= $createdate; ?>" placeholder="Chọn thời gian">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i>
                                    <?= __('Search'); ?>
                                </button>
                                <a class="btn btn-sm btn-danger" href="<?= base_url_admin('recharge-crypto'); ?>"><i
                                        class="fa fa-trash"></i>
                                    <?= __('Clear filter'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show">
                                <label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
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
                            <thead>
                                <tr>
                                    <th><?= __('Username'); ?></th>
                                    <th class="text-center"><?= __('Mã giao dịch'); ?></th>
                                    <th class="text-center"><?= __('Amount'); ?></th>
                                    <th class="text-center"><?= __('Received'); ?></th>
                                    <th class="text-center"><?= __('Tỷ giá'); ?></th>
                                    <th class="text-center"><?= __('Trạng thái'); ?></th>
                                    <th><?= __('Create date'); ?></th>
                                    <th><?= __('Update date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row2): ?>
                                    <tr>
                                        <td class="text-center"><a class="text-primary"
                                                href="<?= base_url_admin('user-edit&id=' . $row2['user_id']); ?>"><?= getRowRealtime("users", $row2['user_id'], "username"); ?>
                                                [ID <?= $row2['user_id']; ?>]</a>
                                        </td>
                                        <td class="text-center"><small><a class="text-primary" target="_blank"
                                                    href="<?= $row2['url_payment']; ?>"><?= $row2['trans_id']; ?></a></small>
                                        </td>
                                        <td style="text-align: right;"><b><?= $row2['amount']; ?></b>
                                            <b style="color:green;">USDT</b>
                                        </td>
                                        <td style="text-align: right;"><b
                                                style="color: red;"><?= format_currency($row2['received']); ?></b>
                                        </td>
                                        <td class="text-center">
                                            <?php // Hiển thị tỷ giá USDT tại thời điểm tạo hóa đơn 
                                            ?>
                                            <?= !empty($row2['exchange_rate']) ? format_currency($row2['exchange_rate']) : '-'; ?>
                                        </td>
                                        <td class="text-center"><?= display_invoice($row2['status']); ?></td>
                                        <td><?= $row2['create_gettime']; ?></td>
                                        <td><?= $row2['update_gettime']; ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8">
                                        <div class="float-right">
                                            <?= __('Paid:'); ?>
                                            <strong
                                                style="color:red;"><?= format_currency($CMSNT->get_row(" SELECT SUM(`received`) FROM `payment_crypto` WHERE $where AND `status` = 'completed' ")['SUM(`received`)']); ?></strong>
                                            | <?= __('Unpaid:'); ?>
                                            <strong
                                                style="color:blue;"><?= format_currency($CMSNT->get_row(" SELECT SUM(`received`) FROM `payment_crypto` WHERE $where AND `status` = 'waiting' ")['SUM(`received`)']); ?></strong>
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
<script>
    CKEDITOR.replace("crypto_note");
</script>

<?php if (checkPermission($getUser['admin'], 'delete_recharge') == true): ?>
    <script>
        // Hàm dọn dẹp lịch sử nạp tiền Crypto
        function cleanupRechargeCrypto() {
            Swal.fire({
                title: '<?= __("Dọn dẹp lịch sử nạp tiền Crypto"); ?>',
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
                        text: '<?= __("Bạn có chắc chắn muốn xóa tất cả lịch sử nạp tiền Crypto từ"); ?> ' + daysToKeep + ' <?= __("ngày trở lên? Hành động này không thể hoàn tác!"); ?>',
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
                                    action: 'cleanupRechargeCrypto',
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