<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Đơn hàng').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .table tr.selected {
        background-color: rgba(0, 94, 234, 0.08) !important;
        position: relative;
        outline: 1px solid #0d6efd !important;
        outline-offset: -1px;
    }
    .table tr.selected td {
        border-color: rgba(13, 110, 253, 0.2) !important;
        color: rgba(0, 0, 0, 0.7);
    }
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .loading-overlay.active {
        display: flex;
    }
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    #bulk-action-buttons {
        transition: all 0.3s ease;
    }
    #selected-counter {
        font-size: 13px;
        padding: 3px 8px;
        background-color: rgba(13, 110, 253, 0.1);
        border-radius: 4px;
    }
    .dropdown-menu {
        animation: fadeInDown 0.3s ease-in-out;
        border: none !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
        min-width: 200px;
    }
    .dropdown-item {
        padding: 8px 16px;
        transition: all 0.2s ease;
        border-radius: 4px;
        margin: 2px 4px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        text-align: left;
    }
    .dropdown-item:hover {
        background-color: rgba(13, 110, 253, 0.1) !important;
        transform: translateX(2px);
    }
    .dropdown-item i {
        width: 18px;
        text-align: center;
        margin-right: 8px;
    }
    .dropdown-item.has-submenu .fa-chevron-right {
        font-size: 10px;
        color: #666;
        transition: transform 0.2s ease;
        margin-left: auto;
        margin-right: 0;
    }
    .dropdown-submenu:hover .fa-chevron-right {
        transform: rotate(90deg);
    }
    .dropdown-submenu {
        position: relative;
    }
    .dropdown-submenu .dropdown-menu {
        position: absolute !important;
        top: 0;
        left: 100%;
        margin-top: 0;
        margin-left: 2px;
        border-radius: 6px;
        display: none;
        animation: fadeInRight 0.2s ease-in-out;
    }
    .dropdown-submenu:hover > .dropdown-menu {
        display: block;
    }
    .dropdown-submenu .dropdown-item {
        padding: 6px 12px;
        font-size: 13px;
        text-align: left;
        justify-content: flex-start;
    }
    .dropdown-submenu .dropdown-item i {
        width: 16px;
        text-align: center;
        margin-right: 8px;
    }
    @keyframes fadeInDown {
        0% {
            opacity: 0;
            transform: translateY(-10px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes fadeInRight {
        0% {
            opacity: 0;
            transform: translateX(-10px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }
    [data-theme-mode="dark"] .table tr.selected {
        background-color: rgba(13, 110, 253, 0.15) !important;
        outline: 1px solid #3384ff !important;
        color: rgba(255, 255, 255, 0.7);
    }
    [data-theme-mode="dark"] .table tr.selected td {
        border-color: rgba(13, 110, 253, 0.3) !important;
        color: rgba(255, 255, 255, 0.7);
    }
    [data-theme-mode="dark"] .dropdown-menu {
        background-color: #1a1d29 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-item {
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-item:focus {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-item.has-submenu .fa-chevron-right {
        color: #ccc !important;
    }
    [data-theme-mode="dark"] .dropdown-submenu .dropdown-menu {
        background-color: #1a1d29 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    [data-theme-mode="dark"] .dropdown-submenu .dropdown-item {
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-submenu .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
    }
    [data-theme-mode="dark"] .dropdown-divider {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-theme-mode="dark"] .dropdown-item i {
        color: inherit !important;
    }
</style>
';
$body['footer'] = '
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="'.base_url('public/theme/').'assets/js/select2.js"></script>
';
require_once(__DIR__.'/../../models/is_ctv.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

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
$where = " `id` > 0 AND `seller` = ? ";
$where_params = [$getUser['id']];
$buyer = '';
$username = '';
$create_gettime = '';
$trans_id = '';
$shortByDate  = '';
$api_transid = '';
$product_id = '';
$uid = '';
$account = '';

if (!empty($_GET['account'])) {
    $account = validate_string($_GET['account'], 255);
    if ($account !== false) {
        $product_sold_rows = $CMSNT->get_list_safe('SELECT * FROM `product_sold` WHERE `account` LIKE ?', ["%$account%"]);
        if (!empty($product_sold_rows)) {
            $trans_ids = array_map(function($row) {
                return $row['trans_id'];
            }, $product_sold_rows);
            $placeholders = str_repeat('?,', count($trans_ids) - 1) . '?';
            $where .= ' AND `trans_id` IN ('.$placeholders.') ';
            $where_params = array_merge($where_params, $trans_ids);
        }
    }
}
if (!empty($_GET['uid'])) {
    $uid = validate_string($_GET['uid'], 255);
    if ($uid !== false) {
        $product_sold_rows = $CMSNT->get_list_safe('SELECT * FROM `product_sold` WHERE `uid` = ?', [$uid]);
        if (!empty($product_sold_rows)) {
            $trans_ids = array_map(function($row) {
                return $row['trans_id'];
            }, $product_sold_rows);
            $placeholders = str_repeat('?,', count($trans_ids) - 1) . '?';
            $where .= ' AND `trans_id` IN ('.$placeholders.') ';
            $where_params = array_merge($where_params, $trans_ids);
        }
    }
}
if(!empty($_GET['product_id'])){
    $product_id = validate_int($_GET['product_id'], 1);
    if ($product_id !== false) {
        $where .= ' AND `product_id` = ? ';
        $where_params[] = $product_id;
    }
}
if (!empty($_GET['username'])) {
    $username = validate_string($_GET['username'], 255);
    if ($username !== false) {
        if($idUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `email` LIKE ?", ["%$username%"])){
            $where .= ' AND `buyer` = ? ';
            $where_params[] = $idUser['id'];
        }else{
            $where .= ' AND `buyer` = "" ';
        }
    }
}
if(!empty($_GET['buyer'])){
    $buyer = validate_int($_GET['buyer'], 1);
    if ($buyer !== false) {
        $where .= ' AND `buyer` = ? ';
        $where_params[] = $buyer;
    }
}
if(!empty($_GET['trans_id'])){
    $trans_id = validate_string($_GET['trans_id'], 255);
    if ($trans_id !== false) {
        if(strpos($trans_id, ',') !== false) {
            $trans_ids = array_map('trim', explode(',', $trans_id));
            $trans_ids = array_filter($trans_ids);
            if(!empty($trans_ids)) {
                $placeholders = str_repeat('?,', count($trans_ids) - 1) . '?';
                $where .= ' AND `trans_id` IN ('.$placeholders.') ';
                $where_params = array_merge($where_params, $trans_ids);
            }
        } else {
            $where .= ' AND `trans_id` LIKE ? ';
            $where_params[] = "%$trans_id%";
        }
    }
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = validate_string($_GET['create_gettime'], 50);
    if ($create_gettime !== false) {
        $createdate = $create_gettime;
        $create_gettime_1 = str_replace('-', '/', $create_gettime);
        $create_gettime_1 = explode(' to ', $create_gettime_1);

        if($create_gettime_1[0] != $create_gettime_1[1]){
            $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
            $where .= " AND `create_gettime` >= ? AND `create_gettime` <= ? ";
            $where_params[] = $create_gettime_1[0];
            $where_params[] = $create_gettime_1[1];
        }
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 4);
    if ($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if($shortByDate == 1){
            $where .= " AND `create_gettime` LIKE ? ";
            $where_params[] = "%$currentDate%";
        }
        if($shortByDate == 2){
            $where .= " AND YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ? ";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where .= " AND MONTH(create_gettime) = ? AND YEAR(create_gettime) = ? ";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
        if($shortByDate == 4){
            $where .= " AND DATE(create_gettime) = ? ";
            $where_params[] = $yesterday;
        }
    }
}

$listDatatable = $CMSNT->get_list_safe("SELECT * FROM `product_order` WHERE $where ORDER BY `id` DESC LIMIT ?, ?", array_merge($where_params, [$from, $limit]));
$totalDatatable = $CMSNT->num_rows_safe("SELECT * FROM `product_order` WHERE $where ORDER BY id DESC", $where_params);
$urlDatatable = pagination(base_url_ctv("product-orders&limit=$limit&shortByDate=$shortByDate&buyer=$buyer&trans_id=$trans_id&create_gettime=$create_gettime&username=$username&product_id=$product_id&uid=$uid&account=$account&"), $from, $totalDatatable, $limit);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-cart-shopping"></i> <?=__('Danh sách đơn hàng đã bán');?>
            </h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="<?=base_url();?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row g-2 mb-3">
                                <input type="hidden" name="module" value="ctv">
                                <input type="hidden" name="action" value="product-orders">
                                <input type="hidden" value="<?=$getUser['token'];?>" id="token">
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$buyer;?>" name="buyer" placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Email');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$trans_id;?>" name="trans_id"
                                        placeholder="<?=__('Mã đơn hàng (có thể nhập nhiều mã, phân tách bằng dấu phẩy)');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$uid;?>" name="uid" placeholder="<?=__('UID');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$account;?>" name="account"
                                        placeholder="<?=__('Account');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control js-example-basic-single" name="product_id">
                                        <option value=""><?=__('-- Sản phẩm của tôi --');?></option>
                                        <?php foreach($CMSNT->get_list_safe("SELECT * FROM `products` WHERE `user_id` = ?", [$getUser['id']]) as $product):?>
                                        <option <?=$product_id == $product['id'] ? 'selected' : '';?>
                                            value="<?=$product['id'];?>">
                                            <?=$product['name'];?>
                                        </option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-6">
                                    <input type="text" name="create_gettime" class="form-control" id="daterange"
                                        value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <button class="btn btn-hero btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-danger" href="<?=base_url_ctv('product-orders');?>"><i
                                            class="fa fa-trash"></i>
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
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?></option>
                                        <option <?=$shortByDate == 4 ? 'selected' : '';?> value="4"><?=__('Hôm qua');?></option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?></option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?></option>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <!-- Nút hành động hàng loạt -->
                        <div class="d-flex mb-3">
                            <div class="btn-list" id="bulk-action-buttons" style="display: none;">
                                <div class="dropdown">
                                    <button type="button"
                                        class="btn btn-outline-primary shadow-primary btn-wave btn-sm dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false" id="btn_thao_tac_nhanh">
                                        <i class="fa-solid fa-cog"></i> <?=__('Thao tác nhanh');?>
                                    </button>
                                    <ul class="dropdown-menu shadow-lg border-0">
                                        <!-- <?=__('Sao chép');?> -->
                                        <li class="dropdown-submenu">
                                            <a class="dropdown-item has-submenu" href="javascript:void(0);">
                                                <i class="fa-solid fa-copy text-info me-2"></i>
                                                <span><?=__('Sao chép');?></span>
                                                <i class="fa-solid fa-chevron-right ms-auto"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="copyOrderData('trans_id')">
                                                        <i class="fa-solid fa-hashtag text-primary me-2"></i>
                                                        <?=__('Mã đơn hàng');?>
                                                    </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="copyOrderData('product_name')">
                                                        <i class="fa-solid fa-box text-warning me-2"></i>
                                                        <?=__('Tên sản phẩm');?>
                                                    </a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                                <span id="selected-counter" class="ms-2 align-self-center text-primary"></span>
                            </div>
                            <div class="ms-auto">
                                <button id="select-all-btn" type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-check-double"></i> <?=__('Chọn tất cả');?>
                                </button>
                                <button id="deselect-all-btn" type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-xmark"></i> <?=__('Bỏ chọn tất cả');?>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox_product" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                        <th class="text-center"><?=__('Bên mua');?></th>
                                        <th class="text-center"><?=__('Đơn hàng');?></th>
                                        <th class="text-center"><?=__('Sản phẩm');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $order): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox_product"
                                                    data-id="<?=$order['id'];?>"
                                                    data-trans-id="<?=$order['trans_id'];?>"
                                                    data-product-name="<?=htmlspecialchars($order['product_name'], ENT_QUOTES);?>"
                                                    name="checkbox_product" value="<?=$order['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm shadow-info btn-wave" id="btnViewOrder"
                                                onclick="viewOrder(`<?=$order['trans_id'];?>`)" data-toggle="tooltip"
                                                type="button"><i class="fa-solid fa-eye"></i></button>
                                            <button class="btn btn-primary btn-sm shadow-primary btn-wave"
                                                onclick="downloadOrder(`<?=$order['trans_id'];?>`)"><i
                                                    class="fa-solid fa-cloud-arrow-down"></i></button>
                                                    <button class="btn btn-success btn-sm shadow-success btn-wave refund-button"
                                                data-id="<?=$order['id'];?>" data-amount="<?=$order['amount'];?>"
                                                data-pay="<?=$order['pay'];?>" data-transid="<?=$order['trans_id'];?>"
                                                <?= $order['amount'] == 0 ? 'disabled' : ''; ?> type="button">
                                                <i class="fa-solid fa-rotate-left"></i> <?=__('Hoàn tiền');?>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if($order['buyer'] > 0): ?>
                                            <?php $user = $CMSNT->get_row_safe("SELECT * FROM users WHERE id = ?", [$order['buyer']]);?>
                                            <i class="fa-solid fa-envelope"></i> <?=substr($user['email'], 0, 5);?>***<br>
                                            <i class="fa-solid fa-wallet"></i> <?=__('Số dư hiện tại:');?>
                                            <strong
                                                style="color:red;"><?=format_currency($user['money']);?></strong><br>
                                            <i class="fa-solid fa-money-bill-trend-up"></i> <?=__('Tổng nạp:');?>
                                            <strong
                                                style="color:green;"><?=format_currency($user['total_money']);?></strong>
                                            <?php else: ?>
                                            <span class="text-muted"><?=__('Hệ thống');?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?=__('Mã đơn hàng');?>: #<strong><?=$order['trans_id'];?></strong><br>
                                            <?=__('Số lượng');?>: <strong><?=format_cash($order['amount']);?></strong><br>
                                            <?=__('Thanh toán');?>: <strong
                                                style="color:red;"><?=format_currency($order['pay']);?></strong>
                                        </td>
                                        <td>
                                            <?=$order['product_name'];?> <a class="text-primary"
                                                href="<?=base_url_ctv('product-edit&id='.$order['product_id']);?>"><i
                                                    class="fa-solid fa-edit"></i></a>
                                        </td>
                                        <td class="text-center">
                                            <strong data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($order['create_gettime']));?>"><?=$order['create_gettime'];?></strong>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="text-right">
                                                <?=__('Tài khoản đã bán');?>:
                                                <strong><?=format_cash($CMSNT->get_row_safe("SELECT SUM(`amount`) as total FROM `product_order` WHERE `refund` = 0 AND $where", $where_params)['total']);?></strong>
                                                |
                                                <?=__('Đơn hàng');?>: <strong
                                                    style="color: green;"><?=format_cash($totalDatatable);?></strong> |
                                                <?=__('Doanh thu');?>: <strong
                                                    style="color:red;"><?=format_currency($CMSNT->get_row_safe("SELECT SUM(`pay`) as total FROM `product_order` WHERE `refund` = 0 AND $where", $where_params)['total']);?></strong>
                                                |
                                                <?=__('Lợi nhuận');?>: <strong
                                                    style="color:blue;"><?=format_currency($CMSNT->get_row_safe("SELECT SUM(`pay`) as total FROM `product_order` WHERE `refund` = 0 AND $where", $where_params)['total']-$CMSNT->get_row_safe("SELECT SUM(`cost`) as total FROM `product_order` WHERE `refund` = 0 AND $where", $where_params)['total']);?></strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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

<?php
require_once(__DIR__.'/footer.php');
?>

<!-- Loading overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>



<!-- Modal <?=__('Hoàn tiền');?> -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <!-- modal-lg để rộng hơn, tùy ý -->
        <div class="modal-content rounded-3 shadow">

            <!-- Tiêu đề Modal (header) -->
            <div class="modal-header text-white">
                <!-- Tiêu đề sẽ được thiết lập động bằng JS -->
                <h5 class="modal-title fw-bold" id="refundModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Phần nội dung (body) -->
            <div class="modal-body py-4">

                <!-- Thông tin hoặc hướng dẫn (tùy chọn) -->
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <div>
                        <?=__('Vui lòng chọn');?> <strong><?=__('Hoàn toàn bộ');?></strong> <?=__('hoặc');?> <strong><?=__('Hoàn một phần');?></strong> <?=__('và nhập số lượng');?>
                        <?=__('cần hoàn.');?>
                    </div>
                </div>

                <!-- Form chính -->
                <form class="px-2">

                    <!-- Chọn kiểu hoàn tiền -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-2"><?=__('Chọn cách hoàn tiền');?> <span
                                class="text-danger">*</span></label>
                        <div class="form-check form-check-inline form-check-md">
                            <input class="form-check-input" type="radio" name="refundType" id="refundFull" value="full"
                                checked>
                            <label class="form-check-label" for="refundFull">
                                <?=__('Hoàn toàn bộ');?>
                            </label>
                        </div>
                        <div class="form-check form-check-inline form-check-md">
                            <input class="form-check-input" type="radio" name="refundType" id="refundPartial"
                                value="partial">
                            <label class="form-check-label" for="refundPartial">
                                <?=__('Hoàn một phần');?>
                            </label>
                        </div>
                    </div>

                    <!-- Nhập số lượng khi hoàn một phần -->
                    <div id="partialGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="partialQuantity" class="form-label fw-semibold"><?=__('Số lượng cần hoàn');?>
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="basic-addon1">
                                    <i class="fa-solid fa-hashtag"></i>
                                </span>
                                <input type="number" class="form-control" id="partialQuantity" name="partialQuantity"
                                    placeholder="<?=__('Nhập số lượng cần hoàn');?>" min="1" aria-describedby="basic-addon1">
                            </div>
                            <small class="text-muted"><?=__('Không vượt quá tổng số lượng còn lại');?></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold"><?=__('Lý do hoàn tiền');?></label>
                        <textarea class="form-control" id="reason" placeholder="<?=__('Nhập nội dung hoàn tiền');?>"></textarea>
                    </div>

                    <!-- Hiển thị tổng số tiền hoàn -->
                    <div class="mb-3">
                        <label for="refundAmount" class="form-label fw-semibold"><?=__('Tổng số tiền hoàn');?></label>
                        <input type="text" class="form-control" id="refundAmount" name="refundAmount" placeholder="<?=__('0');?>"
                            disabled>
                    </div>

                    <!-- Hiển thị số lượng tài khoản hoàn -->
                    <div class="mb-3">
                        <label for="refundCount"
                            class="form-label fw-semibold"><?=__('Số lượng tài khoản hoàn');?></label>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="refundCount" name="refundCount" placeholder="0"
                                disabled>
                            <label for="refundCount"><?=__('Tài khoản');?></label>
                        </div>
                    </div>

                </form>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-warning me-2"></i>
                    <div>
                        <?=__('Hệ thống sẽ thu hồi hoa hồng nếu đơn hàng có phát sinh hoa hồng cho người giới thiệu.');?>
                    </div>
                </div>
            </div>

            <!-- Footer của Modal -->
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> <?=__('Đóng');?>
                </button>
                <button type="button" class="btn btn-primary" id="confirmRefund">
                    <i class="fa-solid fa-check me-1"></i> <?=__('Xác nhận hoàn tiền');?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal xem đơn hàng -->
<div class="modal fade" id="viewOrder" tabindex="-1" aria-labelledby="viewOrder" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewOrder"><i class="fa-solid fa-eye"></i> <?=__('CHI TIẾT ĐƠN HÀNG');?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="coypyBox" readonly rows="10"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox"
                    class="btn btn-danger shadow-danger btn-wave copy"><?=__('Sao chép');?></button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal"><?=__('Đóng');?></button>
            </div>
        </div>
    </div>
</div>





<script>
$(document).ready(() => {
    // ======== 1. Khai báo biến dùng chung ========
    let orderId, orderAmount, orderPay, transId;

    // Cache các selector thường dùng
    const $refundModal = $('#refundModal');
    const $refundModalLabel = $('#refundModalLabel');
    const $refundFull = $('#refundFull');
    const $refundPartial = $('#refundPartial');
    const $partialGroup = $('#partialGroup');
    const $partialQuantity = $('#partialQuantity');
    const $refundAmount = $('#refundAmount');
    const $refundCount = $('#refundCount');
    const $reason = $('#reason');
    const $confirmRefundBtn = $('#confirmRefund');
    const $tokenInput = $('#token'); // Token bảo mật
    const originalBtnContent = $confirmRefundBtn.html(); // Lưu html gốc của nút

    // ======== 2. Hàm phụ ========
    // (A) Đặt lý do hoàn tiền tương ứng
    // const setReason = (type) => {
    //     if (type === 'partial') {
    //         $reason.val(`setReason${transId}`);
    //     } else {
    //         $reason.val(`<?=__('Hoàn tiền đơn hàng');?> #${transId}`);
    //     }
    // };

    // (B) Ẩn/Hiện khối nhập số lượng partial
    const togglePartialGroup = (show) => {
        if (show) {
            $partialGroup.show();
        } else {
            $partialGroup.hide();
        }
    };

    // ======== 3. Hàm tính tiền hoàn (AJAX) ========
    const calculateRefund = (refundType, partialQuantity) => {
        $.ajax({
                url: '<?= base_url('ajaxs/ctv/view.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'tinh_tien_refund',
                    id: orderId,
                    refundType: refundType,
                    partialQuantity: partialQuantity
                }
            })
            .done((response) => {
                if (response.status === 'error') {
                    showMessage(response.msg, 'error');
                } else {
                    $refundAmount.val(response.totalRefund);
                    $refundCount.val(refundType === 'full' ? orderAmount : partialQuantity);
                }
            })
            .fail((xhr, status, error) => {
                console.error('Lỗi khi tính toán số tiền hoàn:', error);
            });
    };

    // ======== 4. Sự kiện click nút "Hoàn tiền" ========
    $('.refund-button').on('click', function() {
        // Lấy dữ liệu từ nút
        orderId = $(this).data('id');
        orderAmount = parseFloat($(this).data('amount'));
        orderPay = parseFloat($(this).data('pay'));
        transId = $(this).data('transid');

        // Thiết lập Modal
        $refundModalLabel.html(
            `<i class="fa-solid fa-rotate-left"></i> <?=__('Hoàn tiền đơn hàng');?> #<b>${transId}</b>`
        );

        // Reset form về trạng thái "Hoàn toàn bộ"
        $refundFull.prop('checked', true);
        $refundPartial.prop('checked', false);
        togglePartialGroup(false);
        $partialQuantity.val('').attr('max', orderAmount);

        $refundAmount.val(orderPay.toFixed(2));
        $refundCount.val(orderAmount);
        // setReason('full');

        // Tính toán tiền hoàn kiểu full
        calculateRefund('full', orderAmount);

        // Hiển thị Modal
        $refundModal.modal('show');
    });

    // ======== 5. Chọn "Hoàn toàn bộ" / "Hoàn một phần" ========
    $('input[name="refundType"]').change(() => {
        const isPartial = $refundPartial.is(':checked');
        if (isPartial) {
            togglePartialGroup(true);
            // Reset trường liên quan
            $refundAmount.val('');
            $partialQuantity.val('');
            $refundCount.val('');
            // setReason('partial');
        } else {
            togglePartialGroup(false);
            calculateRefund('full', orderAmount);
            // setReason('full');
        }
    });

    // ======== 6. Nhập số lượng hoàn một phần ========
    $partialQuantity.on('input', function() {
        let quantity = parseInt($(this).val(), 10) || 0;
        if (quantity > orderAmount) {
            quantity = orderAmount;
            $(this).val(quantity);
        }
        // Hiển thị số lượng
        $refundCount.val(quantity);

        // Tính tiền hoàn
        calculateRefund('partial', quantity);
    });

    // ======== 7. Xác nhận hoàn tiền ========
    $confirmRefundBtn.on('click', () => {
        // 1. Kiểm tra bắt buộc
        const refundType = $('input[name="refundType"]:checked').val();
        const partialQuantity = parseInt($partialQuantity.val()) || 0;
        const currentReason = $reason.val().trim();

        // Nếu chọn "<?=__('Hoàn một phần');?>" mà chưa nhập hoặc nhập 0 => báo lỗi
        if (refundType === 'partial' && partialQuantity < 1) {
            showMessage('Vui lòng nhập số lượng tài khoản cần hoàn!', 'error');
            return; // Dừng, không thực hiện tiếp
        }


        // 2. Thông báo confirm
        if (!confirm('Bạn có chắc chắn muốn hoàn tiền đơn hàng này?')) {
            return; // Nếu người dùng bấm "Hủy", cũng dừng luôn
        }

        // 3. Bắt đầu xử lý hoàn tiền
        // Vô hiệu hóa nút & hiển thị loading
        $confirmRefundBtn.prop('disabled', true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xử lý...');

        // Gửi AJAX hoàn tiền
        $.ajax({
                url: '<?= BASE_URL('ajaxs/ctv/update.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    token: $tokenInput.val(),
                    action: 'refundOrder',
                    id: orderId,
                    refundType: refundType,
                    partialQuantity: partialQuantity,
                    reason: currentReason
                }
            })
            .done((result) => {
                showMessage(result.msg, result.status);
                $refundModal.modal('hide');
                if (result.status === 'success') {
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .fail(() => {
                alert('<?=__('Đã có lỗi xảy ra khi hoàn tiền!');?>');
            })
            .always(() => {
                // Khôi phục trạng thái nút
                $confirmRefundBtn.prop('disabled', false).html(originalBtnContent);
            });
    });

});
</script>



<script>
$(document).ready(() => {
    // Checkbox functions
    $('#check_all_checkbox_product').on('click', function() {
        $('.checkbox_product').prop('checked', this.checked);
        updateSelectedRows();
    });

    $(document).on('change', '.checkbox_product', function() {
        updateSelectedRows();
    });

    $('#select-all-btn').on('click', function() {
        $('.checkbox_product').prop('checked', true);
        updateSelectedRows();
    });

    $('#deselect-all-btn').on('click', function() {
        $('.checkbox_product').prop('checked', false);
        updateSelectedRows();
    });

    function updateSelectedRows() {
        $('.checkbox_product').each(function() {
            if ($(this).prop('checked')) {
                $(this).closest('tr').addClass('selected');
            } else {
                $(this).closest('tr').removeClass('selected');
            }
        });

        var count = $('.checkbox_product:checked').length;

        if (count > 0) {
            $('#bulk-action-buttons').fadeIn(200);
            $('#selected-counter').text(count + ' ' + (count == 1 ? '<?=__('đơn hàng');?>' : '<?=__('đơn hàng');?>') + ' <?=__('đã chọn');?>');
        } else {
            $('#bulk-action-buttons').fadeOut(200);
            $('#selected-counter').text('');
        }
    }
});



function copyOrderData(dataType) {
    var selectedOrders = $('.checkbox_product:checked');

    if (selectedOrders.length == 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một đơn hàng');?>', 'error');
        return;
    }

    var dataList = [];
    var labelText = '';

    selectedOrders.each(function() {
        var element = $(this);
        var data = '';

        switch (dataType) {
            case 'trans_id':
                data = element.attr('data-trans-id');
                labelText = '<?=__('mã đơn hàng');?>';
                break;
            case 'product_name':
                data = element.attr('data-product-name');
                labelText = '<?=__('tên sản phẩm');?>';
                break;
            default:
                data = element.attr('data-id');
                labelText = '<?=__('ID đơn hàng');?>';
        }

        if (data && data.trim() !== '') {
            dataList.push(data.trim());
        }
    });

    if (dataList.length === 0) {
        showMessage('<?=__('Không có dữ liệu');?> ' + labelText + ' <?=__('để sao chép');?>', 'warning');
        return;
    }

    var textToCopy = dataList.join('\n');

    if (navigator.clipboard) {
        navigator.clipboard.writeText(textToCopy).then(function() {
            showMessage('<?=__('Đã sao chép');?> ' + dataList.length + ' ' + labelText, 'success');
        }).catch(function(err) {
            fallbackCopyTextToClipboard(textToCopy, dataList.length, labelText);
        });
    } else {
        fallbackCopyTextToClipboard(textToCopy, dataList.length, labelText);
    }
}

function fallbackCopyTextToClipboard(text, count, label) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "-1000px";
    textArea.style.left = "-1000px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showMessage('<?=__('Đã sao chép');?> ' + count + ' ' + label, 'success');
        } else {
            showMessage('<?=__('Không thể sao chép');?>', 'error');
        }
    } catch (err) {
        console.error('<?=__('Lỗi fallback sao chép');?>: ', err);
        showMessage('<?=__('Không thể sao chép');?>', 'error');
    }

    document.body.removeChild(textArea);
}


function viewOrder(trans_id) {
    $.ajax({
        url: "<?=base_url('ajaxs/ctv/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'view_order',
            token: '<?=$getUser['token'];?>',
            trans_id: trans_id
        },
        success: function(result) {
            $('#viewOrder').modal('show');
            $('#coypyBox').val(result.accounts);
        },
        error: function() {
            alert('<?=__('Có lỗi xảy ra');?>');
            location.reload();
        }
    });
}

function downloadOrder(trans_id) {
    Swal.fire({
        title: "<?=__('Xác nhận tải đơn hàng');?>",
        text: "<?=__('Hệ thống sẽ tải về đơn hàng khi bạn nhấn đồng ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=base_url('ajaxs/ctv/view.php');?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'download_order',
                    trans_id: trans_id,
                    token: $("#token").val(),
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        downloadTXT(result.filename, result.accounts);
                    } else {
                        showMessage(result.msg, result.status);
                    }
                },
                error: function() {
                    alert('<?=__('Có lỗi xảy ra');?>');
                    location.reload();
                }
            });
        }
    });
}

function downloadTXT(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}


new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>
