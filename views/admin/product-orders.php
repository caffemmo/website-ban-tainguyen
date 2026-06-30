<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Đơn hàng') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    /* CSS cho hàng được chọn */
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

    .table tr.selected:hover {
        background-color: rgba(13, 110, 253, 0.12) !important;
    }

    .table tr.selected td:first-child {
        border-left: 2px solid #0d6efd !important;
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
    
    /* Loading overlay */
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
    
    /* Hỗ trợ cho các nút bulk action */
    #bulk-action-buttons {
        transition: all 0.3s ease;
    }
    
    #selected-counter {
        font-size: 13px;
        padding: 3px 8px;
        background-color: rgba(13, 110, 253, 0.1);
        border-radius: 4px;
    }
    
    /* CSS cho dropdown menu có submenu */
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
    
    .dropdown-item span {
        flex: 1;
        text-align: left;
        margin-left: 4px;
    }
    
    .dropdown-item:hover {
        background-color: rgba(13, 110, 253, 0.1) !important;
        transform: translateX(2px);
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
    
    /* Submenu styles */
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
    
    /* Icon spacing cho tất cả dropdown items */
    .dropdown-item i {
        width: 18px;
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
    
    /* Dark mode support */
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
    
    [data-theme-mode="dark"] .dropdown-item.text-danger {
        color: #ff6b6b !important;
    }
    
    [data-theme-mode="dark"] .dropdown-item.text-danger:hover {
        color: #ff5252 !important;
        background-color: rgba(255, 107, 107, 0.1) !important;
    }
</style>
';
$body['footer'] = '
<!-- Select2 Cdn -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Internal Select-2.js -->
<script src="' . base_url('public/theme/') . 'assets/js/select2.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
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
$where = " `id` > 0 ";
$buyer = '';
$username = '';
$seller = '';
$seller_username = '';
$create_gettime = '';
$trans_id = '';
$shortByDate  = '';
$supplier_id = '';
$api_transid = '';
$product_id = '';
$uid = '';
$account = '';

if (!empty($_GET['account'])) {
    $account = check_string($_GET['account']);
    $product_sold_rows = $CMSNT->get_list('SELECT * FROM `product_sold` WHERE `account` LIKE "%' . $account . '%" ');
    if (!empty($product_sold_rows)) {
        $trans_ids = array_map(function ($row) {
            return $row['trans_id'];
        }, $product_sold_rows);
        $trans_ids_str = implode('","', $trans_ids);
        $where .= ' AND `trans_id` IN ("' . $trans_ids_str . '") ';
    }
}
if (!empty($_GET['uid'])) {
    $uid = check_string($_GET['uid']);
    $product_sold_rows = $CMSNT->get_list('SELECT * FROM `product_sold` WHERE `uid` = "' . $uid . '" ');
    if (!empty($product_sold_rows)) {
        $trans_ids = array_map(function ($row) {
            return $row['trans_id'];
        }, $product_sold_rows);
        $trans_ids_str = implode('","', $trans_ids);
        $where .= ' AND `trans_id` IN ("' . $trans_ids_str . '") ';
    }
}
if (!empty($_GET['product_id'])) {
    $product_id = check_string($_GET['product_id']);
    $where .= ' AND `product_id` = "' . $product_id . '" ';
}
if (!empty($_GET['api_transid'])) {
    $api_transid = check_string($_GET['api_transid']);
    // Kiểm tra xem có phải nhiều mã đơn hàng không (phân tách bằng dấu phẩy)
    if (strpos($api_transid, ',') !== false) {
        // Tách các mã đơn hàng bằng dấu phẩy và loại bỏ khoảng trắng
        $api_transid = array_map('trim', explode(',', $api_transid));
        $api_transid = array_filter($api_transid); // Loại bỏ phần tử rỗng
        if (!empty($api_transid)) {
            $api_transid_str = implode('","', $api_transid);
            $where .= ' AND `api_transid` IN ("' . $api_transid_str . '") ';
        }
    } else {
        // Tìm kiếm một mã đơn hàng như trước
        $where .= ' AND `api_transid` LIKE "%' . $api_transid . '%" ';
    }
}
if (!empty($_GET['supplier_id'])) {
    $supplier_id = check_string($_GET['supplier_id']);
    $where .= ' AND `supplier_id` = "' . $supplier_id . '" ';
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if ($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")) {
        $where .= ' AND `buyer` =  "' . $idUser['id'] . '" ';
    } else {
        $where .= ' AND `buyer` =  "" ';
    }
}
if (!empty($_GET['buyer'])) {
    $buyer = check_string($_GET['buyer']);
    $where .= ' AND `buyer` = "' . $buyer . '" ';
}
// Xử lý tìm kiếm theo người bán (seller)
if (!empty($_GET['seller'])) {
    $seller = check_string($_GET['seller']);
    $where .= ' AND `seller` = "' . $seller . '" ';
}
// Xử lý tìm kiếm theo tên người bán
if (!empty($_GET['seller_username'])) {
    $seller_username = check_string($_GET['seller_username']);
    if ($idSeller = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$seller_username' ")) {
        $where .= ' AND `seller` =  "' . $idSeller['id'] . '" ';
    } else {
        $where .= ' AND `seller` =  "" ';
    }
}
if (!empty($_GET['trans_id'])) {
    $trans_id = check_string($_GET['trans_id']);
    // Kiểm tra xem có phải nhiều mã đơn hàng không (phân tách bằng dấu phẩy)
    if (strpos($trans_id, ',') !== false) {
        // Tách các mã đơn hàng bằng dấu phẩy và loại bỏ khoảng trắng
        $trans_ids = array_map('trim', explode(',', $trans_id));
        $trans_ids = array_filter($trans_ids); // Loại bỏ phần tử rỗng
        if (!empty($trans_ids)) {
            $trans_ids_str = implode('","', $trans_ids);
            $where .= ' AND `trans_id` IN ("' . $trans_ids_str . '") ';
        }
    } else {
        // Tìm kiếm một mã đơn hàng như trước
        $where .= ' AND `trans_id` LIKE "%' . $trans_id . '%" ';
    }
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
    if ($shortByDate == 4) {
        $where .= " AND DATE(create_gettime) = '$yesterday' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `product_order` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE $where ORDER BY id DESC ");

$urlDatatable = pagination(base_url_admin("product-orders&limit=$limit&shortByDate=$shortByDate&buyer=$buyer&trans_id=$trans_id&create_gettime=$create_gettime&username=$username&supplier_id=$supplier_id&api_transid=$api_transid&product_id=$product_id&uid=$uid&account=$account&seller=$seller&seller_username=$seller_username&"), $from, $totalDatatable, $limit);

?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-cart-shopping"></i> Danh sách đơn hàng
            </h1>
        </div>
        <?php if (!$CMSNT->get_row(" SELECT * FROM `automations` WHERE `type` IN ('delete_order', 'delete_order_not_uid', 'delete_order_revenue') ")): ?>
            <div class="alert alert-warning alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-warning" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                </svg>
                Quý khách nên cài <a class="text-primary"
                    href="https://help.cmsnt.co/huong-dan/cau-hinh-tu-dong-xoa-don-hang-da-ban-trong-shopclone7/"
                    target="_blank">tự động xóa đơn hàng</a> đã bán sau khoảng thời gian nhất định để bảo mật dữ liệu khách
                hàng và giảm tải tài nguyên máy chủ.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <?php if (checkPermission($getUser['admin'], 'delete_orders_product') == true): ?>
                        <button type="button" onclick="openCleanupOrdersModal()" class="btn btn-warning btn-sm mb-3">
                            <i class="ri-delete-bin-line"></i> <?= __('Dọn dẹp đơn hàng'); ?>
                        </button>
                    <?php endif; ?>

                    <button type="button" onclick="top_san_pham_ban_chay()" class="btn btn-danger btn-sm mb-3">
                        <i class="fa-solid fa-chart-line"></i> TOP SẢN PHẨM BÁN CHẠY
                    </button>

                </div>
            </div>
            <div class="col-xl-12">
                <!-- Bộ lọc tìm kiếm -->
                <div class="card custom-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleOrderFilterForm()">
                        <h6 class="mb-0">
                            <i class="fa-solid fa-filter me-2"></i><?= __('Bộ lọc tìm kiếm'); ?>
                        </h6>
                        <button type="button" class="btn btn-sm btn-light" id="toggleOrderFilterBtn">
                            <i class="fa-solid fa-chevron-down" id="orderFilterIcon"></i>
                        </button>
                    </div>
                    <div class="card-body" id="orderFilterFormBody" style="display: none;">
                        <form method="GET" action="<?= base_url_admin(); ?>">
                            <input type="hidden" name="module" value="admin">
                            <input type="hidden" name="action" value="product-orders">
                            <input type="hidden" value="<?= $getUser['token']; ?>" id="token">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('ID User mua'); ?></label>
                                    <input type="number" class="form-control" name="buyer"
                                        value="<?= $buyer; ?>"
                                        placeholder="<?= __('ID người mua'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('Username mua'); ?></label>
                                    <input type="text" class="form-control" name="username"
                                        value="<?= htmlspecialchars($username); ?>"
                                        placeholder="<?= __('Username mua hàng'); ?>">
                                </div>
                                <?php if ($CMSNT->site('ctv_status') == 1): ?>
                                    <div class="col-md-2">
                                        <label class="form-label"><?= __('ID User bán'); ?></label>
                                        <input type="number" class="form-control" name="seller"
                                            value="<?= isset($_GET['seller']) ? check_string($_GET['seller']) : ''; ?>"
                                            placeholder="<?= __('ID người bán'); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><?= __('Username bán'); ?></label>
                                        <input type="text" class="form-control" name="seller_username"
                                            value="<?= isset($_GET['seller_username']) ? check_string($_GET['seller_username']) : ''; ?>"
                                            placeholder="<?= __('Username bán hàng'); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('Mã đơn hàng'); ?></label>
                                    <input type="text" class="form-control" name="trans_id"
                                        value="<?= htmlspecialchars($trans_id); ?>"
                                        placeholder="<?= __('Nhập mã đơn (phân tách bằng dấu phẩy)'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('UID'); ?></label>
                                    <input type="text" class="form-control" name="uid"
                                        value="<?= htmlspecialchars($uid); ?>"
                                        placeholder="<?= __('UID...'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('Account'); ?></label>
                                    <input type="text" class="form-control" name="account"
                                        value="<?= htmlspecialchars($account); ?>"
                                        placeholder="<?= __('Account...'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('Mã đơn hàng API'); ?></label>
                                    <input type="text" class="form-control" name="api_transid"
                                        value="<?= htmlspecialchars($api_transid); ?>"
                                        placeholder="<?= __('Nhập mã API (phân tách bằng dấu phẩy)'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('API Supplier'); ?></label>
                                    <select class="form-select js-example-basic-single" name="supplier_id">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `suppliers` ") as $supplier): ?>
                                            <option <?= $supplier_id == $supplier['id'] ? 'selected' : ''; ?>
                                                value="<?= $supplier['id']; ?>"><?= $supplier['domain']; ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('Sản phẩm'); ?></label>
                                    <select class="form-select js-example-basic-single" name="product_id">
                                        <option value=""><?= __('Tất cả sản phẩm'); ?></option>
                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `products` ") as $product): ?>
                                            <option <?= $product_id == $product['id'] ? 'selected' : ''; ?>
                                                value="<?= $product['id']; ?>">
                                                <?= $product['name']; ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('Thời gian'); ?></label>
                                    <input type="text" name="create_gettime" class="form-control" id="daterange"
                                        value="<?= htmlspecialchars($create_gettime); ?>" placeholder="<?= __('Chọn thời gian'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('Lọc theo ngày'); ?></label>
                                    <select name="shortByDate" class="form-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 4 ? 'selected' : ''; ?> value="4"><?= __('Hôm qua'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('Số lượng/trang'); ?></label>
                                    <select class="form-select" name="limit">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="col-md-12 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fa-solid fa-filter me-1"></i><?= __('Lọc'); ?>
                                    </button>
                                    <a href="<?= base_url_admin('product-orders'); ?>" class="btn btn-secondary">
                                        <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card custom-card">
                    <div class="card-body">

                        <!-- Nút hành động hàng loạt -->
                        <div class="d-flex mb-3">
                            <div class="btn-list" id="bulk-action-buttons" style="display: none;">
                                <div class="dropdown">
                                    <button type="button"
                                        class="btn btn-outline-primary shadow-primary btn-wave btn-sm dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false" id="btn_thao_tac_nhanh">
                                        <i class="fa-solid fa-cog"></i> <?= __('Thao tác nhanh'); ?>
                                    </button>
                                    <ul class="dropdown-menu shadow-lg border-0">
                                        <!-- Sao chép -->
                                        <li class="dropdown-submenu">
                                            <a class="dropdown-item has-submenu" href="javascript:void(0);">
                                                <i class="fa-solid fa-copy text-info me-2"></i>
                                                <span><?= __('Sao chép'); ?></span>
                                                <i class="fa-solid fa-chevron-right ms-auto"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="copyOrderData('trans_id')">
                                                        <i class="fa-solid fa-hashtag text-primary me-2"></i>
                                                        <?= __('Mã đơn hàng'); ?>
                                                    </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="copyOrderData('api_transid')">
                                                        <i class="fa-solid fa-code text-success me-2"></i>
                                                        <?= __('Mã đơn hàng API'); ?>
                                                    </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="copyOrderData('product_name')">
                                                        <i class="fa-solid fa-box text-warning me-2"></i>
                                                        <?= __('Tên sản phẩm'); ?>
                                                    </a></li>
                                            </ul>
                                        </li>

                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <!-- Xuất đơn hàng -->
                                        <li><a class="dropdown-item text-success" href="javascript:void(0);"
                                                onclick="showExportModal()">
                                                <i class="fa-solid fa-download text-success me-2"></i>
                                                <?= __('Xuất đơn hàng'); ?>
                                            </a></li>

                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <!-- Xóa đơn hàng -->
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);"
                                                onclick="deleteSelectedOrders()">
                                                <i class="fa-solid fa-trash text-danger me-2"></i>
                                                <?= __('Xóa đơn hàng'); ?>
                                            </a></li>

                                    </ul>
                                </div>
                                <span id="selected-counter" class="ms-2 align-self-center text-primary"></span>
                            </div>
                            <div class="ms-auto">
                                <button id="select-all-btn" type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-check-double"></i> <?= __('Chọn tất cả'); ?>
                                </button>
                                <button id="deselect-all-btn" type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-xmark"></i> <?= __('Bỏ chọn tất cả'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" style="width: 20px; height: 20px; cursor: pointer;" name="check_all"
                                                    id="check_all_checkbox_product" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                        <th class="text-center"><?= __('Bên mua'); ?></th>
                                        <?php if ($CMSNT->site('ctv_status') == 1): ?>
                                            <th class="text-center"><?= __('Bên bán'); ?></th>
                                        <?php endif ?>
                                        <th class="text-center"><?= __('Đơn hàng'); ?></th>
                                        <th class="text-center"><?= __('Thanh toán'); ?></th>
                                        <th class="text-center"><?= __('Sản phẩm'); ?></th>
                                        <th class="text-center"><?= __('Thời gian'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $order): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="form-check form-check-md d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input checkbox_product" style="width: 20px; height: 20px; cursor: pointer;"
                                                        data-id="<?= $order['id']; ?>"
                                                        data-trans-id="<?= $order['trans_id']; ?>"
                                                        data-api-transid="<?= $order['api_transid'] ? $order['api_transid'] : ''; ?>"
                                                        data-product-name="<?= htmlspecialchars($order['product_name'], ENT_QUOTES); ?>"
                                                        name="checkbox_product" value="<?= $order['id']; ?>" />
                                                </div>
                                            </td>
                                            <td class="text-center">

                                                <button class="btn btn-info btn-sm shadow-info btn-wave" id="btnViewOrder"
                                                    onclick="viewOrder(`<?= $order['trans_id']; ?>`)" data-toggle="tooltip"
                                                    type="button"><i class="fa-solid fa-eye"></i></button>
                                                <button class="btn btn-primary btn-sm shadow-primary btn-wave"
                                                    onclick="downloadOrder(`<?= $order['trans_id']; ?>`)"><i
                                                        class="fa-solid fa-cloud-arrow-down"></i></button>
                                                <button type="button" onclick="deleteOrder(`<?= $order['id']; ?>`)"
                                                    id="btnDeleteOrder<?= $order['id']; ?>"
                                                    class="btn btn-danger btn-sm shadow-danger btn-wave">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                <button class="btn btn-success btn-sm shadow-success btn-wave refund-button"
                                                    data-id="<?= $order['id']; ?>" data-amount="<?= $order['amount']; ?>"
                                                    data-pay="<?= $order['pay']; ?>" data-transid="<?= $order['trans_id']; ?>"
                                                    <?= $order['amount'] == 0 ? 'disabled' : ''; ?> type="button">
                                                    <i class="fa-solid fa-rotate-left"></i> Hoàn tiền
                                                </button>
                                            </td>
                                            <td>
                                                <?php if ($order['buyer'] > 0): ?>
                                                    <?php $user = $CMSNT->get_row(" SELECT * FROM users WHERE id = " . $order['buyer']); ?>
                                                    <i class="fa-solid fa-user"></i> <?= $user['username']; ?> [ID
                                                    <?= $order['buyer']; ?>] <a class="text-primary"
                                                        href="<?= base_url_admin('user-edit&id=' . $order['buyer']); ?>"><i
                                                            class="fa-solid fa-edit"></i></a><br>
                                                    <i class="fa-solid fa-wallet"></i> <?= __('Số dư hiện tại:'); ?>
                                                    <strong
                                                        style="color:red;"><?= format_currency($user['money']); ?></strong><br>
                                                    <i class="fa-solid fa-money-bill-trend-up"></i> <?= __('Tổng nạp:'); ?>
                                                    <strong
                                                        style="color:green;"><?= format_currency($user['total_money']); ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted"><?= __('Hệ thống'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($CMSNT->site('ctv_status') == 1): ?>
                                                <td>
                                                    <?php if ($order['seller'] > 0): ?>
                                                        <?php $seller = $CMSNT->get_row(" SELECT * FROM users WHERE id = " . $order['seller']); ?>
                                                        <?php if ($seller): ?>
                                                            <i class="fa-solid fa-user-tie"></i> <?= $seller['username']; ?> [ID
                                                            <?= $order['seller']; ?>] <a class="text-primary"
                                                                href="<?= base_url_admin('user-edit&id=' . $order['seller']); ?>"><i
                                                                    class="fa-solid fa-edit"></i></a><br>
                                                            <i class="fa-solid fa-wallet"></i> <?= __('Số dư hiện tại:'); ?>
                                                            <strong
                                                                style="color:red;"><?= format_currency($seller['money']); ?></strong><br>
                                                            <i class="fa-solid fa-money-bill-trend-up"></i> <?= __('Tổng nạp:'); ?>
                                                            <strong
                                                                style="color:green;"><?= format_currency($seller['total_money']); ?></strong>
                                                        <?php endif ?>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?= __('Hệ thống'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif ?>
                                            <td>
                                                Mã đơn hàng: #<strong><?= $order['trans_id']; ?></strong><br>
                                                Mã đơn hàng API (nếu có): #<strong><?= $order['api_transid']; ?></strong><br>
                                                <?php if (checkPermission($getUser['admin'], 'view_suppliers') == true): ?>
                                                    Server API (nếu có):
                                                    <?php if ($order['supplier_id'] != 0): ?>
                                                        <?= getRowRealtime('suppliers', $order['supplier_id'], 'domain'); ?> <a
                                                            class="text-primary"
                                                            href="<?= base_url_admin('product-api-manager&id=' . $order['supplier_id']); ?>"><i
                                                                class="fa-solid fa-edit"></i></a><br>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            </td>
                                            <td>
                                                Số lượng: <strong><?= format_cash($order['amount']); ?></strong><br>
                                                Thanh toán: <strong
                                                    style="color:red;"><?= format_currency($order['pay']); ?></strong><br>
                                                Giá vốn: <strong
                                                    style="color:blue;"><?= format_currency($order['cost']); ?></strong> -
                                                Lãi: <strong
                                                    style="color:green;"><?= format_currency($order['pay'] - $order['cost']); ?></strong><br>
                                            </td>
                                            <td>
                                                <?= $order['product_name']; ?> <a class="text-primary"
                                                    href="<?= base_url_admin('product-edit&id=' . $order['product_id']); ?>"><i
                                                        class="fa-solid fa-edit"></i></a>
                                                <?php if ($order['supplier_id'] != 0): ?>
                                                    <br><small>Tên sản phẩm API:
                                                        <?= getRowRealtime('products', $order['product_id'], 'api_name'); ?></small>
                                                <?php endif ?>
                                            </td>
                                            <td class="text-center">
                                                <strong data-toggle="tooltip" data-placement="bottom"
                                                    title="<?= timeAgo(strtotime($order['create_gettime'])); ?>"><?= $order['create_gettime']; ?></strong>
                                            </td>

                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="<?= $CMSNT->site('ctv_status') == 1 ? '8' : '7' ?>">
                                            <?php
                                            $stats = $CMSNT->get_row(" SELECT 
                                                SUM(`amount`) as total_amount,
                                                SUM(`pay`) as total_revenue,
                                                SUM(`cost`) as total_cost
                                                FROM `product_order` 
                                                WHERE `refund` = 0 AND $where 
                                            ");
                                            $total_amount = $stats['total_amount'] ?? 0;
                                            $total_revenue = $stats['total_revenue'] ?? 0;
                                            $total_cost = $stats['total_cost'] ?? 0;
                                            $total_profit = $total_revenue - $total_cost;
                                            ?>
                                            <div class="text-right">
                                                Tài khoản đã bán:
                                                <strong><?= format_cash($total_amount); ?></strong>
                                                |
                                                Đơn hàng: <strong
                                                    style="color: green;"><?= format_cash($totalDatatable); ?></strong> |
                                                Doanh thu: <strong
                                                    style="color:red;"><?= format_currency($total_revenue); ?></strong>
                                                |
                                                Giá vốn: <strong
                                                    style="color:orange;"><?= format_currency($total_cost); ?></strong>
                                                |
                                                Lợi nhuận: <strong
                                                    style="color:blue;"><?= format_currency($total_profit); ?></strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= $limit; ?> of <?= format_cash($totalDatatable); ?>
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


<script>
    // Toggle filter form
    function toggleOrderFilterForm() {
        var filterBody = document.getElementById('orderFilterFormBody');
        var filterIcon = document.getElementById('orderFilterIcon');

        if (filterBody.style.display === 'none') {
            filterBody.style.display = 'block';
            filterIcon.className = 'fa-solid fa-chevron-up';
            localStorage.setItem('product_orders_filter_expanded', 'true');
        } else {
            filterBody.style.display = 'none';
            filterIcon.className = 'fa-solid fa-chevron-down';
            localStorage.setItem('product_orders_filter_expanded', 'false');
        }
    }

    // Khôi phục trạng thái filter form khi load trang (vanilla JS - không cần jQuery)
    (function() {
        var isFilterExpanded = localStorage.getItem('product_orders_filter_expanded');
        <?php
        // Tự động mở nếu có filter đang active (dùng $_GET vì biến $seller bị ghi đè trong vòng lặp bảng)
        $has_active_filter = !empty($_GET['buyer']) || !empty($_GET['username']) || !empty($_GET['trans_id']) || !empty($_GET['uid'])
            || !empty($_GET['account']) || !empty($_GET['api_transid']) || !empty($_GET['supplier_id']) || !empty($_GET['product_id'])
            || !empty($_GET['create_gettime']) || !empty($_GET['shortByDate']) || !empty($_GET['seller']) || !empty($_GET['seller_username']);
        ?>
        <?php if ($has_active_filter): ?>
            // Có filter đang active, tự động mở
            document.getElementById('orderFilterFormBody').style.display = 'block';
            document.getElementById('orderFilterIcon').className = 'fa-solid fa-chevron-up';
        <?php else: ?>
            // Không có filter, kiểm tra localStorage
            if (isFilterExpanded === 'true') {
                document.getElementById('orderFilterFormBody').style.display = 'block';
                document.getElementById('orderFilterIcon').className = 'fa-solid fa-chevron-up';
            }
        <?php endif; ?>
    })();
</script>

<?php
require_once(__DIR__ . '/footer.php');
?>

<!-- Modal Export đơn hàng -->
<div class="modal fade" id="exportOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-file-export me-2 text-primary"></i><?= __('Tùy chỉnh dữ liệu xuất'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-primary d-flex align-items-center mb-4 border-0" role="alert">
                    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
                    <div>
                        <strong><?= __('Hướng dẫn:'); ?></strong> <?= __('Hãy chọn định dạng file và kéo thả để sắp xếp các cột dữ liệu cần thiết. Hệ thống sẽ trích xuất dựa trên lựa chọn của bạn.'); ?>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Phân vùng: Định dạng file -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-3">
                            <i class="fa-solid fa-file-invoice text-muted me-1"></i><?= __('1. Định dạng file'); ?>
                        </label>
                        <div class="card shadow-none border rounded-3 bg-white">
                            <div class="card-body p-3">
                                <div class="form-check custom-radio-box mb-3 p-3 border rounded border-primary bg-primary-transparent">
                                    <input class="form-check-input mt-2" type="radio" name="exportFileType" id="fileTypeTXT" value="txt" checked style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="fileTypeTXT">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-lines fa-2x text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">TXT File</div>
                                                <div class="fs-12 text-muted"><?= __('Văn bản thuần (Tab-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check custom-radio-box mb-0 p-3 border rounded">
                                    <input class="form-check-input mt-2" type="radio" name="exportFileType" id="fileTypeCSV" value="csv" style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="fileTypeCSV">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-csv fa-2x text-success me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">CSV File</div>
                                                <div class="fs-12 text-muted"><?= __('Dữ liệu bảng (Comma-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phân vùng: Chọn và sắp xếp cột -->
                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-bold mb-0">
                                <i class="fa-solid fa-table-columns text-muted me-1"></i><?= __('2. Cột dữ liệu xuất'); ?>
                            </label>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-grip-vertical me-1"></i><?= __('Kéo thả để sắp xếp'); ?>
                            </span>
                        </div>

                        <div class="card shadow-none border bg-light mb-0">
                            <div class="card-body p-2" style="max-height: 380px; overflow-y: auto;">
                                <ul class="list-group list-group-flush" id="exportColumnsList">
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="trans_id">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_trans_id" value="trans_id" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_trans_id"><?= __('Mã đơn hàng'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="api_transid">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_api_transid" value="api_transid" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_api_transid"><?= __('Mã đơn API'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="username">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_username" value="username" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_username"><?= __('Username'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="product_name">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_product_name" value="product_name" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_product_name"><?= __('Sản phẩm'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="amount">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_amount" value="amount" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_amount"><?= __('Số lượng'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="pay">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_pay" value="pay" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_pay"><?= __('Thanh toán'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="cost">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_cost" value="cost" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_cost"><?= __('Giá vốn'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="create_gettime">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_create_gettime" value="create_gettime" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_create_gettime"><?= __('Ngày tạo'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-0 p-2 d-flex align-items-center" data-column="delivery_content">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-col-checkbox" type="checkbox" role="switch" id="col_delivery_content" value="delivery_content" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_delivery_content"><?= __('Nội dung giao'); ?></label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="toggleAllExportColumns(true)">
                                    <i class="fa-solid fa-check-double me-1"></i><?= __('Chọn xuất tất cả'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="toggleAllExportColumns(false)">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Bỏ chọn'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-secondary px-4 btn-wave" data-bs-dismiss="modal"><?= __('Hủy bỏ'); ?></button>
                <button type="button" class="btn btn-success px-4 btn-wave fw-bold" id="confirmExportBtn" onclick="confirmExportOrders()">
                    <i class="fa-solid fa-download me-2"></i><?= __('Bắt đầu trích xuất'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .custom-radio-box {
        transition: all 0.2s;
    }

    #exportColumnsList .cursor-move {
        cursor: grab;
    }

    #exportColumnsList .cursor-move:active {
        cursor: grabbing;
    }

    #exportColumnsList .list-group-item {
        user-select: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    #exportColumnsList .list-group-item:hover {
        border-color: rgba(var(--primary-rgb), 0.5) !important;
        background-color: rgba(var(--primary-rgb), 0.03) !important;
    }

    #exportColumnsList .list-group-item.sortable-ghost {
        opacity: 0.5;
        background-color: rgba(var(--primary-rgb), 0.1) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<script>
    // JS trang trí khi select file type radio
    $(document).ready(function() {
        $('input[name="exportFileType"]').on('change', function() {
            $('.custom-radio-box').removeClass('border-primary bg-primary-transparent');
            $(this).closest('.custom-radio-box').addClass('border-primary bg-primary-transparent');
        });
    });
</script>

<!-- Sortable.js for drag-drop column ordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Loading overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>


<!-- Modal Hoàn tiền -->
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
                        <?= __('Vui lòng chọn'); ?> <strong><?= __('Hoàn toàn bộ'); ?></strong>,
                        <strong><?= __('Hoàn một phần'); ?></strong> <?= __('hoặc'); ?>
                        <strong><?= __('Hoàn theo %'); ?></strong> <?= __('và nhập giá trị tương ứng.'); ?>
                    </div>
                </div>

                <!-- Form chính -->
                <form class="px-2">

                    <!-- Chọn kiểu hoàn tiền -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-2"><?= __('Chọn cách hoàn tiền'); ?> <span
                                class="text-danger">*</span></label>
                        <div class="form-check form-check-inline form-check-md">
                            <input class="form-check-input" type="radio" name="refundType" id="refundFull" value="full"
                                checked>
                            <label class="form-check-label" for="refundFull">
                                <?= __('Hoàn toàn bộ'); ?>
                            </label>
                        </div>
                        <div class="form-check form-check-inline form-check-md">
                            <input class="form-check-input" type="radio" name="refundType" id="refundPartial"
                                value="partial">
                            <label class="form-check-label" for="refundPartial">
                                <?= __('Hoàn một phần'); ?>
                            </label>
                        </div>
                        <div class="form-check form-check-inline form-check-md">
                            <input class="form-check-input" type="radio" name="refundType" id="refundPercent"
                                value="percent">
                            <label class="form-check-label" for="refundPercent">
                                <?= __('Hoàn theo %'); ?>
                                <small class="text-muted">(<?= __('chỉ áp dụng đơn 1 tài khoản'); ?>)</small>
                            </label>
                        </div>
                    </div>

                    <!-- Nhập số lượng khi hoàn một phần -->
                    <div id="partialGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="partialQuantity" class="form-label fw-semibold"><?= __('Số lượng cần hoàn'); ?>
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="basic-addon1">
                                    <i class="fa-solid fa-hashtag"></i>
                                </span>
                                <input type="number" class="form-control" id="partialQuantity" name="partialQuantity"
                                    placeholder="<?= __('Nhập số lượng cần hoàn'); ?>" min="1" aria-describedby="basic-addon1">
                            </div>
                            <small class="text-muted"><?= __('Không vượt quá tổng số lượng còn lại'); ?></small>
                        </div>
                    </div>

                    <!-- Nhập % khi hoàn theo phần trăm -->
                    <div id="percentGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="percentValue" class="form-label fw-semibold"><?= __('Phần trăm cần hoàn'); ?>
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fa-solid fa-percent"></i>
                                </span>
                                <input type="number" class="form-control" id="percentValue" name="percentValue"
                                    placeholder="<?= __('Nhập phần trăm cần hoàn (0.01 - 100)'); ?>"
                                    min="0.01" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">
                                <?= __('Áp dụng cho đơn hàng có 1 tài khoản. Hệ thống sẽ thu hồi hoa hồng và tiền người bán theo cùng tỷ lệ %.'); ?>
                            </small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold"><?= __('Lý do hoàn tiền'); ?> <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" placeholder="Nhập nội dung hoàn tiền"></textarea>
                    </div>

                    <!-- Hiển thị tổng số tiền hoàn -->
                    <div class="mb-3">
                        <label for="refundAmount" class="form-label fw-semibold"><?= __('Tổng số tiền hoàn'); ?></label>
                        <input type="text" class="form-control" id="refundAmount" name="refundAmount" placeholder="0"
                            disabled>
                    </div>

                    <!-- Hiển thị số lượng tài khoản hoàn -->
                    <div class="mb-3">
                        <label for="refundCount"
                            class="form-label fw-semibold"><?= __('Số lượng tài khoản hoàn'); ?></label>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="refundCount" name="refundCount" placeholder="0"
                                disabled>
                            <label for="refundCount">Tài khoản</label>
                        </div>
                    </div>

                </form>

                <!-- Lịch sử hoàn tiền từ bảng biến động số dư (dongtien) -->
                <div id="refundHistorySection" style="display: none;">
                    <hr class="my-3">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> <?= __('Lịch sử hoàn tiền'); ?>
                        <span id="refundHistoryCount" class="badge bg-light text-dark ms-2">0</span>
                    </h6>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 35%;"><?= __('Nội dung'); ?></th>
                                    <th class="text-center" style="width: 15%;"><?= __('Số tiền'); ?></th>
                                    <th class="text-center" style="width: 15%;"><?= __('Người nhận/bị thu'); ?></th>
                                    <th class="text-center" style="width: 15%;"><?= __('Số dư sau'); ?></th>
                                    <th class="text-center" style="width: 20%;"><?= __('Thời gian'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="refundHistoryBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
                    <i class="fa-solid fa-warning me-2"></i>
                    <div>
                        <?= __('Hệ thống sẽ thu hồi hoa hồng nếu đơn hàng có phát sinh hoa hồng cho người giới thiệu.'); ?>
                    </div>
                </div>
            </div>

            <!-- Footer của Modal -->
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Đóng
                </button>
                <button type="button" class="btn btn-primary" id="confirmRefund">
                    <i class="fa-solid fa-check me-1"></i> Xác nhận hoàn tiền
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(() => {
        // ======== 1. Khai báo biến dùng chung ========
        let orderId, orderAmount, orderPay, transId;

        // Cache các selector thường dùng để tránh truy vấn DOM nhiều lần
        const $refundModal = $('#refundModal');
        const $refundModalLabel = $('#refundModalLabel');
        const $refundFull = $('#refundFull');
        const $refundPartial = $('#refundPartial');
        const $refundPercent = $('#refundPercent');
        const $partialGroup = $('#partialGroup');
        const $percentGroup = $('#percentGroup');
        const $partialQuantity = $('#partialQuantity');
        const $percentValue = $('#percentValue');
        const $refundAmount = $('#refundAmount');
        const $refundCount = $('#refundCount');
        const $reason = $('#reason');
        const $confirmRefundBtn = $('#confirmRefund');
        const $tokenInput = $('#token'); // Token bảo mật CSRF
        const originalBtnContent = $confirmRefundBtn.html(); // Lưu html gốc của nút để khôi phục sau loading
        const $refundHistorySection = $('#refundHistorySection');
        const $refundHistoryBody = $('#refundHistoryBody');
        const $refundHistoryCount = $('#refundHistoryCount');

        // ======== 2. Hàm phụ ========
        // (A) Đặt lý do hoàn tiền tương ứng theo từng kiểu hoàn
        const setReason = (type, extra) => {
            if (type === 'partial') {
                $reason.val(`Hoàn tiền một phần đơn hàng #${transId}`);
            } else if (type === 'percent') {
                // extra ở đây là phần trăm hoàn (nếu có) để ghi rõ vào lý do
                const percent = extra || $percentValue.val() || '';
                $reason.val(percent ?
                    `Hoàn ${percent}% đơn hàng #${transId}` :
                    `Hoàn tiền theo % đơn hàng #${transId}`);
            } else {
                $reason.val(`Hoàn tiền đơn hàng #${transId}`);
            }
        };

        // (B) Hiển thị duy nhất khối input phù hợp với kiểu hoàn đang chọn
        const showRefundGroup = (group) => {
            $partialGroup.hide();
            $percentGroup.hide();
            if (group === 'partial') {
                $partialGroup.show();
            } else if (group === 'percent') {
                $percentGroup.show();
            }
        };

        // (C) Lấy & render lịch sử hoàn tiền từ biến động số dư (bảng dongtien)
        const loadRefundHistory = (orderIdParam) => {
            $refundHistoryBody.empty();
            $refundHistorySection.hide();
            $refundHistoryCount.text('0');

            $.ajax({
                    url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_refund_history',
                        id: orderIdParam
                    }
                })
                .done((res) => {
                    if (res.status !== 'success' || !res.history || res.history.length === 0) {
                        return;
                    }
                    $refundHistoryCount.text(res.total);

                    let html = '';
                    res.history.forEach((item) => {
                        // Badge loại giao dịch: hoàn tiền cho buyer = xanh, thu hồi từ seller = đỏ
                        const typeBadge = item.type === 'refund' ?
                            '<span class="badge bg-success-transparent"><i class="fa-solid fa-arrow-up me-1"></i><?= __("Hoàn tiền"); ?></span>' :
                            '<span class="badge bg-danger-transparent"><i class="fa-solid fa-arrow-down me-1"></i><?= __("Thu hồi"); ?></span>';

                        // Badge số tiền: cộng = xanh, trừ = đỏ
                        const amountBadge = item.is_increase ?
                            `<span class="badge bg-success-transparent">+${item.amount_fmt}</span>` :
                            `<span class="badge bg-danger-transparent">-${item.amount_fmt}</span>`;

                        html += `<tr>
                        <td>
                            ${typeBadge}
                            <small class="d-block text-muted mt-1" title="${item.transid}">${item.content}</small>
                        </td>
                        <td class="text-center">${amountBadge}</td>
                        <td class="text-center">
                            <small><i class="fa-solid fa-user me-1"></i>${item.username} <span class="text-muted">[ID ${item.user_id}]</span></small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-transparent">${item.after_fmt}</span>
                        </td>
                        <td class="text-center">
                            <small>${item.time}</small>
                        </td>
                    </tr>`;
                    });

                    $refundHistoryBody.html(html);
                    $refundHistorySection.show();
                })
                .fail((xhr, status, error) => {
                    console.error('Lỗi khi tải lịch sử hoàn tiền:', error);
                });
        };

        // ======== 3. Hàm tính tiền hoàn (AJAX) ========
        // value: số lượng tài khoản (partial) hoặc phần trăm (percent), bỏ qua với full
        const calculateRefund = (refundType, value) => {
            $.ajax({
                    url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'tinh_tien_refund',
                        id: orderId,
                        refundType: refundType,
                        partialQuantity: refundType === 'partial' ? value : 0,
                        percentValue: refundType === 'percent' ? value : 0
                    }
                })
                .done((response) => {
                    if (response.status === 'error') {
                        showMessage(response.msg, 'error');
                        $refundAmount.val('');
                        $refundCount.val('');
                    } else {
                        $refundAmount.val(response.totalRefund);
                        // Số lượng tài khoản hoàn: full = toàn bộ, partial = số nhập, percent = 0 (vì không trả lại tài khoản)
                        if (refundType === 'full') {
                            $refundCount.val(orderAmount);
                        } else if (refundType === 'partial') {
                            $refundCount.val(value);
                        } else {
                            $refundCount.val(0);
                        }
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Lỗi khi tính toán số tiền hoàn:', error);
                });
        };

        // ======== 4. Sự kiện click nút "Hoàn tiền" ========
        $('.refund-button').on('click', function() {
            // Lấy dữ liệu từ nút bấm hành động
            orderId = $(this).data('id');
            orderAmount = parseFloat($(this).data('amount'));
            orderPay = parseFloat($(this).data('pay'));
            transId = $(this).data('transid');

            $refundModalLabel.html(
                `<i class="fa-solid fa-rotate-left"></i> Hoàn tiền đơn hàng #<b>${transId}</b>`
            );

            // Reset form về trạng thái "Hoàn toàn bộ"
            $refundFull.prop('checked', true);
            $refundPartial.prop('checked', false);
            $refundPercent.prop('checked', false);
            showRefundGroup(null);
            $partialQuantity.val('').attr('max', orderAmount);
            $percentValue.val('');

            // Chỉ cho phép hoàn theo % khi đơn có duy nhất 1 tài khoản (theo nghiệp vụ user yêu cầu)
            const allowPercent = orderAmount === 1;
            $refundPercent.prop('disabled', !allowPercent);
            // Cập nhật tooltip giải thích cho admin biết lý do bị disable
            const $percentLabel = $('label[for="refundPercent"]');
            if (!allowPercent) {
                $percentLabel.addClass('text-muted')
                    .attr('title', 'Chỉ áp dụng cho đơn hàng có 1 tài khoản');
            } else {
                $percentLabel.removeClass('text-muted').removeAttr('title');
            }

            $refundAmount.val(orderPay.toFixed(2));
            $refundCount.val(orderAmount);
            setReason('full');

            calculateRefund('full', orderAmount);

            // Tải lịch sử hoàn tiền từ biến động số dư để admin tham khảo trước khi thao tác
            loadRefundHistory(orderId);

            $refundModal.modal('show');
        });

        // ======== 5. Đổi kiểu hoàn tiền (full / partial / percent) ========
        $('input[name="refundType"]').change(function() {
            const refundType = $(this).val();
            if (refundType === 'partial') {
                showRefundGroup('partial');
                // Reset trường liên quan để admin nhập lại
                $refundAmount.val('');
                $partialQuantity.val('');
                $refundCount.val('');
                setReason('partial');
            } else if (refundType === 'percent') {
                showRefundGroup('percent');
                $refundAmount.val('');
                $percentValue.val('');
                $refundCount.val(0);
                setReason('percent');
            } else {
                showRefundGroup(null);
                calculateRefund('full', orderAmount);
                setReason('full');
            }
        });

        // ======== 6. Nhập số lượng hoàn một phần ========
        $partialQuantity.on('input', function() {
            let quantity = parseInt($(this).val(), 10) || 0;
            if (quantity > orderAmount) {
                quantity = orderAmount;
                $(this).val(quantity);
            }
            $refundCount.val(quantity);
            calculateRefund('partial', quantity);
        });

        // ======== 7. Nhập % hoàn theo phần trăm ========
        $percentValue.on('input', function() {
            let percent = parseFloat($(this).val());
            // Giới hạn min/max ngay tại UI để tránh gửi giá trị vô lý lên server
            if (isNaN(percent) || percent <= 0) {
                $refundAmount.val('');
                $refundCount.val(0);
                return;
            }
            if (percent > 100) {
                percent = 100;
                $(this).val(percent);
            }
            // Cập nhật lý do để khớp % vừa nhập
            setReason('percent', percent);
            calculateRefund('percent', percent);
        });

        // ======== 8. Xác nhận hoàn tiền ========
        $confirmRefundBtn.on('click', () => {
            // 1. Lấy dữ liệu form
            const refundType = $('input[name="refundType"]:checked').val();
            const partialQuantity = parseInt($partialQuantity.val()) || 0;
            const percentValue = parseFloat($percentValue.val()) || 0;
            const currentReason = $reason.val().trim();

            // 2. Validate theo từng kiểu hoàn
            if (refundType === 'partial' && partialQuantity < 1) {
                showMessage('Vui lòng nhập số lượng tài khoản cần hoàn!', 'error');
                return;
            }
            if (refundType === 'percent') {
                // Chặn hoàn % với đơn nhiều tài khoản (frontend guard, backend cũng check)
                if (orderAmount !== 1) {
                    showMessage('Hoàn theo % chỉ áp dụng cho đơn hàng có 1 tài khoản!', 'error');
                    return;
                }
                if (percentValue <= 0 || percentValue > 100) {
                    showMessage('Phần trăm hoàn phải lớn hơn 0 và nhỏ hơn hoặc bằng 100!', 'error');
                    return;
                }
            }

            if (!currentReason) {
                showMessage('Vui lòng nhập lý do hoàn tiền!', 'error');
                return;
            }

            if (!confirm('Bạn có chắc chắn muốn hoàn tiền đơn hàng này?')) {
                return;
            }

            // 3. Vô hiệu hóa nút & hiển thị loading khi đang gửi yêu cầu
            $confirmRefundBtn.prop('disabled', true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xử lý...');

            $.ajax({
                    url: '<?= BASE_URL('ajaxs/admin/update.php'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        token: $tokenInput.val(),
                        action: 'refundOrder',
                        id: orderId,
                        refundType: refundType,
                        partialQuantity: partialQuantity,
                        percentValue: percentValue,
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
                    alert('Đã có lỗi xảy ra khi hoàn tiền!');
                })
                .always(() => {
                    $confirmRefundBtn.prop('disabled', false).html(originalBtnContent);
                });
        });

    });
</script>






<script>
    // JavaScript xử lý chức năng cập nhật nhanh
    $(function() {
        // Checkbox "check all"
        $('#check_all_checkbox_product').on('click', function() {
            $('.checkbox_product').prop('checked', this.checked);
            updateSelectedRows();
        });

        // Chọn/bỏ chọn hàng khi click vào checkbox
        $(document).on('change', '.checkbox_product', function() {
            updateSelectedRows();
        });

        // Nút chọn tất cả
        $('#select-all-btn').on('click', function() {
            $('.checkbox_product').prop('checked', true);
            updateSelectedRows();
        });

        // Nút bỏ chọn tất cả
        $('#deselect-all-btn').on('click', function() {
            $('.checkbox_product').prop('checked', false);
            updateSelectedRows();
        });

        function updateSelectedRows() {
            // Highlight các hàng được chọn
            $('.checkbox_product').each(function() {
                if ($(this).prop('checked')) {
                    $(this).closest('tr').addClass('selected');
                } else {
                    $(this).closest('tr').removeClass('selected');
                }
            });

            // Cập nhật số lượng đã chọn
            var count = $('.checkbox_product:checked').length;

            // Hiển thị/ẩn các nút hành động hàng loạt
            if (count > 0) {
                $('#bulk-action-buttons').fadeIn(10);
                $('#selected-counter').text(count + ' ' + (count == 1 ? '<?= __('đơn hàng'); ?>' :
                    '<?= __('đơn hàng'); ?>') + ' <?= __('đã chọn'); ?>');
            } else {
                $('#bulk-action-buttons').fadeOut(10);
                $('#selected-counter').text('');
            }
        }

        // Xử lý checkbox chọn tất cả trong bảng
        $('#check_all_checkbox_product').on('change', function() {
            $('.checkbox_product').prop('checked', this.checked);
            updateSelectedRows();
        });
    });

    function post_remove(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                id: id,
                token: $("#token").val(),
                action: 'removeOrder'
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('Có lỗi xảy ra khi xóa đơn hàng: ' + error, 'error');
            }
        });
    }

    // Hàm xử lý xóa nhiều đơn hàng một lúc
    function delete_records() {
        // Hiển thị loading
        $('#loading-overlay').addClass('active');

        // Thu thập ID của các đơn hàng được chọn
        var ids = [];
        $('.checkbox_product:checked').each(function() {
            ids.push($(this).val());
        });

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'bulkRemoveOrders',
                ids: JSON.stringify(ids),
                token: $("#token").val()
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        title: "<?= __('Thành công!'); ?>",
                        text: respone.msg,
                        icon: "success"
                    }).then((result) => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "<?= __('Thất bại!'); ?>",
                        text: respone.msg,
                        icon: "error"
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: "<?= __('Thất bại!'); ?>",
                    text: "<?= __('Đã xảy ra lỗi khi kết nối đến máy chủ'); ?>",
                    icon: "error"
                });
            },
            complete: function() {
                // Ẩn loading
                $('#loading-overlay').removeClass('active');
            }
        });
    }

    // Hàm sao chép dữ liệu đơn hàng theo loại
    function copyOrderData(dataType) {
        var selectedOrders = $('.checkbox_product:checked');

        if (selectedOrders.length == 0) {
            showMessage('<?= __('Vui lòng chọn ít nhất một đơn hàng'); ?>', 'error');
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
                    labelText = '<?= __('mã đơn hàng'); ?>';
                    break;
                case 'api_transid':
                    data = element.attr('data-api-transid');
                    labelText = '<?= __('mã đơn hàng API'); ?>';
                    break;
                case 'product_name':
                    data = element.attr('data-product-name');
                    labelText = '<?= __('tên sản phẩm'); ?>';
                    break;
                default:
                    data = element.attr('data-id');
                    labelText = '<?= __('ID đơn hàng'); ?>';
            }

            // Chỉ thêm dữ liệu không rỗng
            if (data && data.trim() !== '') {
                dataList.push(data.trim());
            }
        });

        if (dataList.length === 0) {
            showMessage('<?= __('Không có dữ liệu'); ?> ' + labelText + ' <?= __('để sao chép'); ?>', 'warning');
            return;
        }

        // Tạo text để sao chép - mỗi dữ liệu một dòng
        var textToCopy = dataList.join('\n');

        // Sao chép vào clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showMessage('<?= __('Đã sao chép'); ?> ' + dataList.length + ' ' + labelText, 'success');
            }).catch(function(err) {
                fallbackCopyTextToClipboard(textToCopy, dataList.length, labelText);
            });
        } else {
            // Fallback cho các trình duyệt cũ
            fallbackCopyTextToClipboard(textToCopy, dataList.length, labelText);
        }
    }

    // Hàm fallback để sao chép text
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
                showMessage('<?= __('Đã sao chép'); ?> ' + count + ' ' + label, 'success');
            } else {
                showMessage('<?= __('Không thể sao chép'); ?>', 'error');
            }
        } catch (err) {
            console.error('<?= __('Lỗi fallback sao chép'); ?>: ', err);
            showMessage('<?= __('Không thể sao chép'); ?>', 'error');
        }

        document.body.removeChild(textArea);
    }

    // Hàm xóa đơn hàng đã chọn
    function deleteSelectedOrders() {
        var checkboxes = document.querySelectorAll('input[name="checkbox_product"]:checked');
        if (checkboxes.length === 0) {
            showMessage('<?= __('Vui lòng chọn ít nhất một đơn hàng'); ?>', 'error');
            return;
        }

        Swal.fire({
            title: "<?= __('Xác nhận xóa đơn hàng'); ?>",
            html: `
            <div class="text-start">
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong><?= __('Cảnh báo!'); ?></strong><br>
                    <?= __('Bạn sắp xóa'); ?> <strong>${checkboxes.length}</strong> <?= __('đơn hàng'); ?><br>
                    <small><?= __('Hành động này không thể hoàn tác!'); ?></small>
                </div>
                <label for="confirmText" class="form-label">
                    <?= __('Để xác nhận, vui lòng nhập'); ?> <strong class="text-danger">DELETE</strong>
                </label>
                <input type="text" id="confirmText" class="form-control" placeholder="<?= __('Nhập: DELETE'); ?>" autocomplete="off">
                <small class="text-muted mt-1 d-block"><?= __('Nhập chính xác từ "DELETE" để tiếp tục'); ?></small>
            </div>
        `,
            icon: "warning",
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<?= __('Xóa đơn hàng'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            preConfirm: () => {
                const confirmText = document.getElementById('confirmText').value.trim();
                if (confirmText !== 'DELETE') {
                    Swal.showValidationMessage('<?= __('Vui lòng nhập chính xác "DELETE" để xác nhận'); ?>');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                delete_records();
            }
        });
    }
</script>

<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }
</script>

<script>
    function downloadOrder(trans_id) {
        Swal.fire({
            title: "<?= __('Xác nhận tải đơn hàng'); ?>",
            text: "<?= __('Hệ thống sẽ tải về đơn hàng khi bạn nhấn đồng ý'); ?>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Đóng'); ?>",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/view.php"); ?>",
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
                        alert(html(result));
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
</script>


<script>
    function deleteOrder(id) {
        const originalContent = $('#btnDeleteOrder' + id)
            .html(); // Save the original button content
        $('#btnDeleteOrder' + id).html(
                '<span><i class="fa fa-spinner fa-spin"></i></span>')
            .prop('disabled', true);
        Swal.fire({
            title: "<?= __('Xác nhận xóa đơn hàng'); ?>",
            text: "<?= __('Hệ thống sẽ xóa đơn hàng khỏi hệ thống khi bạn nhấn đồng ý'); ?>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Đóng'); ?>",
        }).then((result) => {
            if (result.isConfirmed) {
                post_remove(id);
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        }).finally(() => {
            $('#btnDeleteOrder' + id).html(originalContent)
                .prop('disabled', false);
        });
    }
</script>


<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" data-bs-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h6 class="modal-title fw-bold" id="viewOrderModalLabel">
                    <i class="fa-solid fa-receipt me-2 text-primary"></i><?= __('Chi tiết đơn hàng'); ?>
                    <span class="badge bg-primary-transparent ms-2" id="orderModalTransId"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="orderDetailBody">
                <!-- Loading state -->
                <div id="orderDetailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3"><?= __('Đang tải dữ liệu...'); ?></p>
                </div>

                <!-- Content (hidden until loaded) -->
                <div id="orderDetailContent" style="display: none;">
                    <!-- Row 1: Order Info (with IP/Device) + Supplier -->
                    <div class="row g-3 mb-3">
                        <!-- Thông tin đơn hàng -->
                        <div class="col-md-7">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header od-header-order py-2 px-3">
                                    <h6 class="mb-0 fw-bold fs-13">
                                        <i class="fa-solid fa-clipboard-list me-2"></i><?= __('Thông tin đơn hàng'); ?>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted fw-medium" style="width:140px;"><i class="fa-solid fa-hashtag me-1 opacity-50"></i><?= __('Mã đơn hàng'); ?></td>
                                                <td class="fw-bold" id="od_trans_id"></td>
                                            </tr>
                                            <tr id="od_api_transid_row">
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-code me-1 opacity-50"></i><?= __('Mã đơn API'); ?></td>
                                                <td id="od_api_transid"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-box me-1 opacity-50"></i><?= __('Sản phẩm'); ?></td>
                                                <td id="od_product_name"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-layer-group me-1 opacity-50"></i><?= __('Số lượng'); ?></td>
                                                <td id="od_amount"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-clock me-1 opacity-50"></i><?= __('Thời gian'); ?></td>
                                                <td id="od_create_gettime"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-globe me-1 opacity-50"></i>IP</td>
                                                <td id="od_ip" class="font-monospace"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-mobile-screen me-1 opacity-50"></i><?= __('Thiết bị'); ?></td>
                                                <td id="od_device" class="text-wrap fs-12" style="word-break: break-all;"></td>
                                            </tr>
                                            <tr id="od_refund_row" style="display:none;">
                                                <td class="text-muted fw-medium"><i class="fa-solid fa-rotate-left me-1 text-danger opacity-50"></i><?= __('Trạng thái'); ?></td>
                                                <td><span class="badge bg-danger-transparent" id="od_refund_badge"><?= __('Đã hoàn tiền'); ?></span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier API -->
                        <div class="col-md-5" id="od_supplier_section">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header od-header-supplier py-2 px-3">
                                    <h6 class="mb-0 fw-bold fs-13">
                                        <i class="fa-solid fa-server me-2"></i><?= __('API Supplier'); ?>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div id="od_supplier_content"></div>
                                    <div id="od_supplier_empty" class="text-center text-muted py-3" style="display:none;">
                                        <i class="fa-solid fa-cloud-slash fs-3 mb-2 d-block opacity-50"></i>
                                        <span class="fs-13"><?= __('Không qua API'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Accounts Data -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header od-accounts-header py-2 px-3 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold fs-13">
                                        <i class="fa-solid fa-database me-2"></i><?= __('Dữ liệu tài khoản'); ?>
                                        <span class="badge bg-light text-dark ms-2" id="od_accounts_count">0</span>
                                    </h6>
                                    <button type="button" onclick="copyOrderAccounts()" class="btn btn-sm btn-outline-light">
                                        <i class="fa-solid fa-copy me-1"></i><?= __('Sao chép'); ?>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <textarea class="form-control border-0 rounded-0" id="orderAccountsBox" readonly rows="8" style="font-family: 'Courier New', monospace; font-size: 13px; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Biến động số dư đơn hàng (mua, doanh thu, hoàn tiền, thu hồi, hoa hồng) -->
                    <div class="row g-3 mt-0" id="od_balance_history_section" style="display: none;">
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header od-header-balance py-2 px-3">
                                    <h6 class="mb-0 fw-bold fs-13">
                                        <i class="fa-solid fa-arrow-right-arrow-left me-2"></i><?= __('Biến động số dư đơn hàng'); ?>
                                        <span class="badge bg-white bg-opacity-25 ms-2" id="od_balance_history_count">0</span>
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="sticky-top">
                                                <tr>
                                                    <th class="ps-3" style="width: 14%;"><?= __('Loại'); ?></th>
                                                    <th style="width: 28%;"><?= __('Nội dung'); ?></th>
                                                    <th class="text-center" style="width: 13%;"><?= __('Số tiền'); ?></th>
                                                    <th class="text-center" style="width: 16%;"><?= __('Tài khoản'); ?></th>
                                                    <th class="text-center" style="width: 13%;"><?= __('Số dư sau'); ?></th>
                                                    <th class="text-center pe-3" style="width: 16%;"><?= __('Thời gian'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="od_balance_history_body">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary btn-wave px-4" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i><?= __('Đóng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #viewOrderModal .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    #viewOrderModal .table td {
        padding: 6px 8px;
        vertical-align: middle;
    }

    #viewOrderModal .fs-11 {
        font-size: 11px;
    }

    #viewOrderModal .fs-12 {
        font-size: 12px;
    }

    #viewOrderModal .fs-13 {
        font-size: 13px;
    }

    #viewOrderModal .fs-14 {
        font-size: 14px;
    }

    #viewOrderModal .bg-teal-transparent {
        background-color: rgba(32, 201, 151, 0.1);
    }

    #viewOrderModal .text-teal {
        color: #20c997;
    }

    #viewOrderModal .font-monospace {
        font-family: 'Courier New', monospace;
    }

    /* === Card Header Solid Colors === */
    #viewOrderModal .od-header-order {
        background-color: #4361ee !important;
    }

    #viewOrderModal .od-header-supplier {
        background-color: #f59e0b !important;
    }

    #viewOrderModal .od-accounts-header {
        background-color: #2d3238 !important;
    }

    #viewOrderModal .od-header-refund {
        background-color: #dc3545 !important;
    }

    #viewOrderModal .od-header-balance {
        background-color: #6f42c1 !important;
    }

    /*
     * Badge trong bảng "Biến động số dư": tăng tương phản so với bg-*-transparent mặc định
     * (nền quá nhạt + chữ cùng tông → khó đọc trên một số màn hình).
     */
    #od_balance_history_section .od-balance-type {
        font-weight: 600;
        border: 1px solid transparent;
    }

    #od_balance_history_section .od-balance-type-purchase {
        background-color: rgba(67, 97, 238, 0.22) !important;
        color: #1e3a8a !important;
        border-color: rgba(67, 97, 238, 0.35);
    }

    #od_balance_history_section .od-balance-type-revenue {
        background-color: rgba(25, 135, 84, 0.22) !important;
        color: #0f5132 !important;
        border-color: rgba(25, 135, 84, 0.35);
    }

    #od_balance_history_section .od-balance-type-refund {
        background-color: rgba(217, 119, 6, 0.22) !important;
        color: #92400e !important;
        border-color: rgba(217, 119, 6, 0.4);
    }

    #od_balance_history_section .od-balance-type-take_refund {
        background-color: rgba(220, 53, 69, 0.22) !important;
        color: #842029 !important;
        border-color: rgba(220, 53, 69, 0.35);
    }

    #od_balance_history_section .od-balance-type-commission {
        background-color: rgba(13, 202, 240, 0.25) !important;
        color: #055160 !important;
        border-color: rgba(13, 202, 240, 0.45);
    }

    #od_balance_history_section .od-balance-type-other {
        background-color: rgba(108, 117, 125, 0.22) !important;
        color: #343a40 !important;
        border-color: rgba(108, 117, 125, 0.35);
    }

    #od_balance_history_section .od-balance-amt {
        font-weight: 600;
        border: 1px solid transparent;
    }

    #od_balance_history_section .od-balance-amt-plus {
        background-color: rgba(25, 135, 84, 0.22) !important;
        color: #0f5132 !important;
        border-color: rgba(25, 135, 84, 0.35);
    }

    #od_balance_history_section .od-balance-amt-minus {
        background-color: rgba(220, 53, 69, 0.22) !important;
        color: #842029 !important;
        border-color: rgba(220, 53, 69, 0.35);
    }

    #od_balance_history_section .od-balance-after {
        font-weight: 600;
        background-color: rgba(67, 97, 238, 0.18) !important;
        color: #1e3a8a !important;
        border: 1px solid rgba(67, 97, 238, 0.35);
    }

    /* White text for all solid headers */
    #viewOrderModal [class*="od-header-"] h6,
    #viewOrderModal [class*="od-header-"] h6 i,
    #viewOrderModal [class*="od-header-"] h6 .badge,
    #viewOrderModal .od-accounts-header h6,
    #viewOrderModal .od-accounts-header h6 i,
    #viewOrderModal .od-accounts-header .btn {
        color: #fff !important;
    }

    #viewOrderModal [class*="od-header-"],
    #viewOrderModal .od-accounts-header {
        color: #fff !important;
    }

    /* Badge đếm trên header tím: override chữ trắng chung để nền sáng + chữ tối, đọc rõ */
    #viewOrderModal #od_balance_history_count {
        color: #4c1d95 !important;
        background-color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
    }

    /* Textarea */
    #orderAccountsBox {
        background: var(--custom-white, #f8f9fa);
        color: var(--default-text-color, #333);
    }

    #orderAccountsBox:focus {
        box-shadow: none;
    }

    /* Dark mode overrides */
    [data-theme-mode="dark"] #viewOrderModal .od-accounts-header {
        background-color: #1a1d21 !important;
    }

    [data-theme-mode="dark"] #orderAccountsBox {
        background: var(--custom-white, #1a1d21);
        color: var(--default-text-color, #e0e0e0);
    }

    [data-theme-mode="dark"] #viewOrderModal .fw-bold,
    [data-theme-mode="dark"] #viewOrderModal .card-body .fw-bold {
        color: var(--default-text-color, #e0e0e0);
    }

    [data-theme-mode="dark"] #viewOrderModal .modal-header,
    [data-theme-mode="dark"] #viewOrderModal .modal-footer {
        background-color: var(--custom-white, #1e2126) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* Dark mode: badge biến động số dư — nền đậm hơn + chữ sáng để không “chìm” */
    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-purchase {
        background-color: rgba(129, 161, 248, 0.28) !important;
        color: #e0e7ff !important;
        border-color: rgba(129, 161, 248, 0.5);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-revenue {
        background-color: rgba(52, 211, 153, 0.28) !important;
        color: #d1fae5 !important;
        border-color: rgba(52, 211, 153, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-refund {
        background-color: rgba(251, 191, 36, 0.28) !important;
        color: #fef3c7 !important;
        border-color: rgba(251, 191, 36, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-take_refund {
        background-color: rgba(248, 113, 113, 0.28) !important;
        color: #fee2e2 !important;
        border-color: rgba(248, 113, 113, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-commission {
        background-color: rgba(34, 211, 238, 0.28) !important;
        color: #cffafe !important;
        border-color: rgba(34, 211, 238, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-type-other {
        background-color: rgba(156, 163, 175, 0.3) !important;
        color: #f3f4f6 !important;
        border-color: rgba(156, 163, 175, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-amt-plus {
        background-color: rgba(52, 211, 153, 0.28) !important;
        color: #d1fae5 !important;
        border-color: rgba(52, 211, 153, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-amt-minus {
        background-color: rgba(248, 113, 113, 0.28) !important;
        color: #fee2e2 !important;
        border-color: rgba(248, 113, 113, 0.45);
    }

    [data-theme-mode="dark"] #od_balance_history_section .od-balance-after {
        background-color: rgba(129, 161, 248, 0.28) !important;
        color: #e0e7ff !important;
        border-color: rgba(129, 161, 248, 0.5);
    }
</style>

<script type="text/javascript">
    function viewOrder(trans_id) {
        // Show modal with loading state
        var modalEl = document.getElementById('viewOrderModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $('#orderDetailLoading').show();
        $('#orderDetailContent').hide();
        $('#orderModalTransId').text('#' + trans_id);

        $.ajax({
            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'view_order',
                token: '<?= $getUser['token']; ?>',
                trans_id: trans_id
            },
            success: function(result) {
                if (result.status === 'error') {
                    showMessage(result.msg, 'error');
                    modal.hide();
                    return;
                }

                var order = result.order;
                var buyer = result.buyer;
                var seller = result.seller;
                var supplier = result.supplier;

                // === Thông tin đơn hàng ===
                $('#od_trans_id').html('<span class="font-monospace">' + order.trans_id + '</span>');

                if (order.api_transid) {
                    $('#od_api_transid').html('<span class="font-monospace">' + order.api_transid + '</span>');
                    $('#od_api_transid_row').show();
                } else {
                    $('#od_api_transid_row').hide();
                }

                $('#od_product_name').html(
                    '<a href="<?= base_url_admin("product-edit&id="); ?>' + order.product_id + '" class="text-primary fw-medium">' +
                    order.product_name + ' <i class="fa-solid fa-external-link fs-10"></i></a>'
                );

                $('#od_amount').html('<span class="badge bg-primary-transparent fs-13">' + order.amount + ' <?= __("tài khoản"); ?></span>');

                $('#od_create_gettime').html(
                    '<span>' + order.create_gettime + '</span>' +
                    '<br><small class="text-muted">' + order.time_ago + '</small>'
                );

                // Refund
                if (order.refund == 1) {
                    $('#od_refund_row').show();
                } else {
                    $('#od_refund_row').hide();
                }

                // === IP & Device ===
                $('#od_ip').html(order.ip ? '<span class="badge bg-light text-dark border">' + order.ip + '</span>' : '<span class="text-muted fs-12"><?= __("Không có dữ liệu"); ?></span>');
                $('#od_device').html(order.device ? order.device : '<span class="text-muted"><?= __("Không có dữ liệu"); ?></span>');

                // === Supplier ===
                if (supplier) {
                    var supHtml = '<table class="table table-sm table-borderless mb-0">';
                    supHtml += '<tr><td class="text-muted fw-medium" style="width:60px;"><?= __("Domain"); ?></td>';
                    supHtml += '<td><a href="' + supplier.domain + '" target="_blank" class="text-primary">' + supplier.domain + ' <i class="fa-solid fa-external-link fs-10"></i></a></td></tr>';
                    supHtml += '<tr><td class="text-muted fw-medium"><?= __("Loại"); ?></td>';
                    supHtml += '<td><span class="badge bg-info-transparent">' + (supplier.type || 'N/A') + '</span></td></tr>';
                    if (order.api_transid) {
                        supHtml += '<tr><td class="text-muted fw-medium"><?= __("Mã API"); ?></td>';
                        supHtml += '<td class="font-monospace fs-12">' + order.api_transid + '</td></tr>';
                    }
                    supHtml += '</table>';
                    supHtml += '<a href="<?= base_url_admin("product-api-manager&id="); ?>' + supplier.id + '" class="btn btn-sm btn-outline-warning mt-2 w-100"><i class="fa-solid fa-gear me-1"></i><?= __("Quản lý API"); ?></a>';

                    $('#od_supplier_content').html(supHtml).show();
                    $('#od_supplier_empty').hide();
                } else {
                    $('#od_supplier_content').hide();
                    $('#od_supplier_empty').show();
                }

                // === Tài khoản ===
                $('#orderAccountsBox').val(result.accounts);
                $('#od_accounts_count').text(result.accounts_count);

                // === Biến động số dư đơn hàng: load tất cả giao dịch liên quan ===
                loadOrderBalanceHistory(order.id);

                // Show content, hide loading
                $('#orderDetailLoading').hide();
                $('#orderDetailContent').show();
            },
            error: function() {
                showMessage('<?= __("Đã xảy ra lỗi khi tải dữ liệu"); ?>', 'error');
                modal.hide();
            }
        });
    }

    function copyOrderAccounts() {
        var text = $('#orderAccountsBox').val();
        if (!text || text.trim() === '') {
            showMessage('<?= __("Không có dữ liệu để sao chép"); ?>', 'warning');
            return;
        }
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showMessage('<?= __("Đã sao chép dữ liệu tài khoản vào bộ nhớ tạm"); ?>', 'success');
            }).catch(function() {
                // Fallback
                $('#orderAccountsBox').select();
                document.execCommand('copy');
                showMessage('<?= __("Đã sao chép dữ liệu tài khoản vào bộ nhớ tạm"); ?>', 'success');
            });
        } else {
            $('#orderAccountsBox').select();
            document.execCommand('copy');
            showMessage('<?= __("Đã sao chép dữ liệu tài khoản vào bộ nhớ tạm"); ?>', 'success');
        }
    }

    // Tải và hiển thị biến động số dư đơn hàng (mua, doanh thu, hoàn tiền, thu hồi, hoa hồng)
    function loadOrderBalanceHistory(orderId) {
        var $section = $('#od_balance_history_section');
        var $body = $('#od_balance_history_body');
        var $count = $('#od_balance_history_count');

        $section.hide();
        $body.empty();
        $count.text('0');

        // Map loại giao dịch → class od-balance-type-* (CSS riêng, tương phản cao hơn theme transparent)
        var typeMap = {
            'purchase':    { icon: 'fa-cart-shopping',    label: '<?= __("Mua hàng"); ?>',    css: 'od-balance-type od-balance-type-purchase' },
            'revenue':     { icon: 'fa-coins',            label: '<?= __("Doanh thu"); ?>',   css: 'od-balance-type od-balance-type-revenue' },
            'refund':      { icon: 'fa-rotate-left',      label: '<?= __("Hoàn tiền"); ?>',   css: 'od-balance-type od-balance-type-refund' },
            'take_refund': { icon: 'fa-arrow-turn-down',  label: '<?= __("Thu hồi"); ?>',     css: 'od-balance-type od-balance-type-take_refund' },
            'commission':  { icon: 'fa-handshake',        label: '<?= __("Hoa hồng"); ?>',    css: 'od-balance-type od-balance-type-commission' },
            'other':       { icon: 'fa-circle-question',  label: '<?= __("Khác"); ?>',        css: 'od-balance-type od-balance-type-other' }
        };

        $.ajax({
                url: '<?= base_url("ajaxs/admin/view.php"); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_order_balance_history',
                    id: orderId
                }
            })
            .done(function(res) {
                if (res.status !== 'success' || !res.history || res.history.length === 0) {
                    $body.html('<tr><td colspan="6" class="text-center text-muted py-3"><i class="fa-solid fa-inbox me-1"></i><?= __("Không có dữ liệu"); ?></td></tr>');
                    $section.show();
                    return;
                }
                $count.text(res.total);

                var html = '';
                for (var i = 0; i < res.history.length; i++) {
                    var item = res.history[i];
                    var info = typeMap[item.type] || typeMap['other'];

                    // Badge loại giao dịch
                    var typeBadge = '<span class="badge ' + info.css + ' fs-11"><i class="fa-solid ' + info.icon + ' me-1"></i>' + info.label + '</span>';

                    // Badge số tiền (class riêng để tương phản rõ)
                    var amountBadge = item.is_increase
                        ? '<span class="badge od-balance-amt od-balance-amt-plus">+' + item.amount_fmt + '</span>'
                        : '<span class="badge od-balance-amt od-balance-amt-minus">-' + item.amount_fmt + '</span>';

                    html += '<tr>';
                    html += '<td class="ps-3">' + typeBadge + '</td>';
                    html += '<td><small class="text-muted" title="' + item.transid + '">' + item.content + '</small></td>';
                    html += '<td class="text-center">' + amountBadge + '</td>';
                    html += '<td class="text-center"><small><i class="fa-solid fa-user me-1 opacity-50"></i>' + item.username + ' <span class="text-muted">[ID ' + item.user_id + ']</span></small></td>';
                    html += '<td class="text-center"><span class="badge od-balance-after">' + item.after_fmt + '</span></td>';
                    html += '<td class="text-center pe-3"><small>' + item.time + '</small></td>';
                    html += '</tr>';
                }

                $body.html(html);
                $section.show();
            })
            .fail(function(xhr, status, error) {
                console.error('Lỗi khi tải biến động số dư đơn hàng:', error);
            });
    }
</script>



<div class="modal fade" id="top_san_pham_ban_chay" tabindex="-1" aria-labelledby="top_san_pham_ban_chay"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="top_san_pham_ban_chay"><i class="fa-solid fa-chart-line"></i> TOP SẢN PHẨM
                    BÁN CHẠY
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="hien_thi_top_san_pham_ban_chay"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function top_san_pham_ban_chay() {
        $('#hien_thi_top_san_pham_ban_chay').html(
            '<h5 class="mb-3 py-4 text-center"><i class="fa fa-spinner fa-spin"></i> Đang phân tích dữ liệu, vui lòng chờ...</h5>'
        );
        $('#top_san_pham_ban_chay').modal('show');
        $.ajax({
            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
            method: "POST",
            data: {
                action: 'top_san_pham_ban_chay',
                token: '<?= $getUser['token']; ?>'
            },
            success: function(result) {
                $('#hien_thi_top_san_pham_ban_chay').html(result);
            },
            error: function() {
                $('#hien_thi_top_san_pham_ban_chay').html(result);
            }
        });
    }
</script>

<script>
    // Xử lý tự động chuyển đổi định dạng mã đơn hàng khi paste
    $(document).ready(function() {
        // Xử lý sự kiện paste cho input trans_id
        $('input[name="trans_id"]').on('paste', function(e) {
            const input = this;

            // Ngăn chặn hành vi paste mặc định
            e.preventDefault();

            // Lấy dữ liệu từ clipboard
            let clipboardData = e.originalEvent.clipboardData || window.clipboardData;
            let pastedData = clipboardData.getData('text').trim();

            // Lấy nội dung hiện tại của input
            let currentValue = $(input).val().trim();

            // Xử lý dữ liệu paste
            let newData = '';

            // Kiểm tra xem có phải dữ liệu nhiều dòng không
            if (pastedData.includes('\n') || pastedData.includes('\r')) {
                // Tách các dòng và loại bỏ khoảng trắng
                let lines = pastedData.split(/[\r\n]+/)
                    .map(line => line.trim())
                    .filter(line => line.length > 0);

                if (lines.length > 1) {
                    // Nối các dòng bằng dấu phẩy
                    newData = lines.join(',');

                    // Hiển thị thông báo cho việc chuyển đổi
                    showMessage('<?= __('Đã chuyển đổi'); ?> ' + lines.length +
                        ' <?= __('mã đơn hàng thành định dạng phân tách bằng dấu phẩy'); ?>', 'success');
                } else {
                    // Nếu chỉ có 1 dòng
                    newData = lines[0] || '';
                }
            } else {
                // Nếu không có xuống dòng
                newData = pastedData;
            }

            // Kết hợp với nội dung hiện có
            let finalValue = '';
            if (currentValue && newData) {
                // Nếu cả hai đều có nội dung
                // Kiểm tra xem currentValue có kết thúc bằng dấu phẩy không
                if (currentValue.endsWith(',')) {
                    finalValue = currentValue + newData;
                } else {
                    finalValue = currentValue + ',' + newData;
                }
            } else if (newData) {
                // Chỉ có dữ liệu mới
                finalValue = newData;
            } else {
                // Không có dữ liệu mới, giữ nguyên
                finalValue = currentValue;
            }

            // Loại bỏ dấu phẩy trùng lặp
            finalValue = finalValue.replace(/,+/g, ',').replace(/^,|,$/g, '');

            // Cập nhật giá trị input
            $(input).val(finalValue);
        });

        // Thêm tooltip hướng dẫn
        $('input[name="trans_id"]').attr('title',
            '<?= __('Có thể paste nhiều mã đơn hàng (mỗi mã một dòng), hệ thống sẽ tự động thêm vào danh sách hiện có'); ?>'
        );

        // Xử lý sự kiện paste cho input api_transid
        $('input[name="api_transid"]').on('paste', function(e) {
            const input = this;

            // Ngăn chặn hành vi paste mặc định
            e.preventDefault();

            // Lấy dữ liệu từ clipboard
            let clipboardData = e.originalEvent.clipboardData || window.clipboardData;
            let pastedData = clipboardData.getData('text').trim();

            // Lấy nội dung hiện tại của input
            let currentValue = $(input).val().trim();

            // Xử lý dữ liệu paste
            let newData = '';

            // Kiểm tra xem có phải dữ liệu nhiều dòng không
            if (pastedData.includes('\n') || pastedData.includes('\r')) {
                // Tách các dòng và loại bỏ khoảng trắng
                let lines = pastedData.split(/[\r\n]+/)
                    .map(line => line.trim())
                    .filter(line => line.length > 0);

                if (lines.length > 1) {
                    // Nối các dòng bằng dấu phẩy
                    newData = lines.join(',');

                    // Hiển thị thông báo cho việc chuyển đổi
                    showMessage('<?= __('Đã chuyển đổi'); ?> ' + lines.length +
                        ' <?= __('mã đơn hàng API thành định dạng phân tách bằng dấu phẩy'); ?>',
                        'success');
                } else {
                    // Nếu chỉ có 1 dòng
                    newData = lines[0] || '';
                }
            } else {
                // Nếu không có xuống dòng
                newData = pastedData;
            }

            // Kết hợp với nội dung hiện có
            let finalValue = '';
            if (currentValue && newData) {
                // Nếu cả hai đều có nội dung
                // Kiểm tra xem currentValue có kết thúc bằng dấu phẩy không
                if (currentValue.endsWith(',')) {
                    finalValue = currentValue + newData;
                } else {
                    finalValue = currentValue + ',' + newData;
                }
            } else if (newData) {
                // Chỉ có dữ liệu mới
                finalValue = newData;
            } else {
                // Không có dữ liệu mới, giữ nguyên
                finalValue = currentValue;
            }

            // Loại bỏ dấu phẩy trùng lặp
            finalValue = finalValue.replace(/,+/g, ',').replace(/^,|,$/g, '');

            // Cập nhật giá trị input
            $(input).val(finalValue);
        });

        // Thêm tooltip hướng dẫn cho api_transid
        $('input[name="api_transid"]').attr('title',
            '<?= __('Có thể paste nhiều mã đơn hàng API (mỗi mã một dòng), hệ thống sẽ tự động thêm vào danh sách hiện có'); ?>'
        );
    });
</script>

<!-- Modal Dọn dẹp đơn hàng -->
<?php if (checkPermission($getUser['admin'], 'delete_orders_product') == true): ?>
    <div class="modal fade" id="cleanupOrdersModal" tabindex="-1" aria-labelledby="cleanupOrdersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="cleanupOrdersModalLabel">
                        <i class="ri-delete-bin-line me-2"></i> <?= __('Dọn dẹp đơn hàng'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="alert alert-danger d-flex align-items-start" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2 mt-1"></i>
                        <div>
                            <strong><?= __('Cảnh báo!'); ?></strong><br>
                            <?= __('Hành động này sẽ xóa các đơn hàng cũ theo điều kiện bạn chọn. Dữ liệu đã xóa không thể khôi phục!'); ?>
                        </div>
                    </div>

                    <form id="cleanupOrdersForm">
                        <!-- Loại dọn dẹp -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-3">
                                <?= __('Chọn loại dọn dẹp'); ?> <span class="text-danger">*</span>
                            </label>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_1" value="delete_order_revenue" checked>
                                <label class="form-check-label" for="cleanup_type_1">
                                    <strong class="text-danger"><?= __('Xóa toàn bộ đơn hàng và tài khoản'); ?></strong>
                                    <br><small class="text-muted"><?= __('Xóa hoàn toàn đơn hàng và tất cả tài khoản đã bán (bao gồm UID, Account...)'); ?></small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_2" value="delete_order_only">
                                <label class="form-check-label" for="cleanup_type_2">
                                    <strong class="text-primary"><?= __('Xóa đơn hàng, không xóa tài khoản'); ?></strong>
                                    <br><small class="text-muted"><?= __('Ẩn đơn hàng nhưng giữ nguyên toàn bộ tài khoản đã bán'); ?></small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_3" value="delete_order_not_uid">
                                <label class="form-check-label" for="cleanup_type_3">
                                    <strong class="text-success"><?= __('Xóa đơn hàng và tài khoản, giữ lại UID'); ?></strong>
                                    <br><small class="text-muted"><?= __('Ẩn đơn hàng, xóa thông tin tài khoản nhưng giữ lại UID để tra cứu'); ?></small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_4" value="delete_order">
                                <label class="form-check-label" for="cleanup_type_4">
                                    <strong class="text-warning"><?= __('Xóa đơn hàng, xóa toàn bộ tài khoản'); ?></strong>
                                    <br><small class="text-muted"><?= __('Ẩn đơn hàng và xóa tất cả tài khoản đã bán (bao gồm UID, Account...)'); ?></small>
                                </label>
                            </div>
                        </div>

                        <!-- Số ngày giữ lại -->
                        <div class="mb-4">
                            <label for="cleanup_days" class="form-label fw-semibold">
                                <?= __('Số ngày giữ lại'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-calendar-days"></i></span>
                                <input type="number" class="form-control" id="cleanup_days" name="days_to_keep"
                                    placeholder="<?= __('Nhập số ngày'); ?>" min="1" value="30" required>
                                <span class="input-group-text"><?= __('ngày'); ?></span>
                            </div>
                            <small class="text-muted">
                                <?= __('Ví dụ: Nhập 30 sẽ xóa tất cả đơn hàng từ 30 ngày trở lên, giữ lại đơn hàng trong 30 ngày gần đây'); ?>
                            </small>
                        </div>

                        <!-- Thống kê số đơn hàng sẽ bị ảnh hưởng -->
                        <div class="alert alert-info" id="cleanup_preview">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <span id="cleanup_preview_text"><?= __('Nhấn "Xem trước" để xem số đơn hàng sẽ bị ảnh hưởng'); ?></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> <?= __('Đóng'); ?>
                    </button>
                    <button type="button" class="btn btn-info" id="btnPreviewCleanup">
                        <i class="fa-solid fa-eye me-1"></i> <?= __('Xem trước'); ?>
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmCleanup" disabled>
                        <i class="fa-solid fa-trash me-1"></i> <?= __('Xác nhận dọn dẹp'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hàm mở modal dọn dẹp đơn hàng
        function openCleanupOrdersModal() {
            // Reset form
            $('#cleanupOrdersForm')[0].reset();
            $('#cleanup_type_1').prop('checked', true);
            $('#cleanup_days').val(30);
            $('#cleanup_preview_text').text('<?= __("Nhấn \"Xem trước\" để xem số đơn hàng sẽ bị ảnh hưởng"); ?>');
            $('#btnConfirmCleanup').prop('disabled', true);

            // Hiển thị modal
            $('#cleanupOrdersModal').modal('show');
        }

        // Xem trước số đơn hàng sẽ bị ảnh hưởng
        $('#btnPreviewCleanup').on('click', function() {
            const days = parseInt($('#cleanup_days').val()) || 0;
            const cleanupType = $('input[name="cleanup_type"]:checked').val();

            if (days < 1) {
                showMessage('<?= __("Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)"); ?>', 'error');
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> <?= __("Đang tính..."); ?>').prop('disabled', true);

            $.ajax({
                url: '<?= base_url("ajaxs/admin/view.php"); ?>',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'previewCleanupOrders',
                    token: '<?= $getUser['token']; ?>',
                    days_to_keep: days,
                    cleanup_type: cleanupType
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#cleanup_preview_text').html(
                            '<strong>' + response.count + '</strong> <?= __("đơn hàng sẽ bị ảnh hưởng"); ?>' +
                            (response.accounts_count ? ' (<strong>' + response.accounts_count + '</strong> <?= __("tài khoản"); ?>)' : '')
                        );
                        $('#btnConfirmCleanup').prop('disabled', false);
                    } else {
                        showMessage(response.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?= __("Có lỗi xảy ra khi tính toán"); ?>', 'error');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        });

        // Xác nhận dọn dẹp
        $('#btnConfirmCleanup').on('click', function() {
            const days = parseInt($('#cleanup_days').val()) || 0;
            const cleanupType = $('input[name="cleanup_type"]:checked').val();

            if (days < 1) {
                showMessage('<?= __("Vui lòng nhập số ngày hợp lệ"); ?>', 'error');
                return;
            }

            // Lấy text mô tả loại dọn dẹp
            let cleanupTypeText = '';
            switch (cleanupType) {
                case 'delete_order_revenue':
                    cleanupTypeText = '<?= __("Xóa toàn bộ đơn hàng và tài khoản"); ?>';
                    break;
                case 'delete_order_only':
                    cleanupTypeText = '<?= __("Xóa đơn hàng, không xóa tài khoản"); ?>';
                    break;
                case 'delete_order_not_uid':
                    cleanupTypeText = '<?= __("Xóa đơn hàng và tài khoản, giữ lại UID"); ?>';
                    break;
                case 'delete_order':
                    cleanupTypeText = '<?= __("Xóa đơn hàng, xóa toàn bộ tài khoản"); ?>';
                    break;
            }

            // Đóng modal Bootstrap trước khi hiển thị SweetAlert2 để tránh xung đột
            $('#cleanupOrdersModal').modal('hide');

            // Đợi modal đóng xong rồi mới hiển thị SweetAlert
            setTimeout(function() {
                Swal.fire({
                    title: '<?= __("Xác nhận dọn dẹp đơn hàng"); ?>',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-danger mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong><?= __("Cảnh báo!"); ?></strong><br>
                                <?= __("Bạn sắp dọn dẹp đơn hàng với cấu hình:"); ?>
                                <ul class="mb-0 mt-2">
                                    <li><strong><?= __("Loại:"); ?></strong> ${cleanupTypeText}</li>
                                    <li><strong><?= __("Số ngày giữ lại:"); ?></strong> ${days} <?= __("ngày"); ?></li>
                                </ul>
                                <hr>
                                <small class="text-danger"><?= __("Hành động này không thể hoàn tác!"); ?></small>
                            </div>
                            <label for="confirmCleanupText" class="form-label">
                                <?= __("Để xác nhận, vui lòng nhập"); ?> <strong class="text-danger">CLEANUP</strong>
                            </label>
                            <input type="text" id="confirmCleanupText" class="form-control" placeholder="<?= __("Nhập: CLEANUP"); ?>" autocomplete="off">
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<?= __("Xác nhận dọn dẹp"); ?>',
                    cancelButtonText: '<?= __("Hủy"); ?>',
                    didOpen: () => {
                        document.getElementById('confirmCleanupText').focus();
                    },
                    preConfirm: () => {
                        const confirmText = document.getElementById('confirmCleanupText').value.trim();
                        if (confirmText !== 'CLEANUP') {
                            Swal.showValidationMessage('<?= __("Vui lòng nhập chính xác \"CLEANUP\" để xác nhận"); ?>');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Hiển thị loading
                        Swal.fire({
                            title: '<?= __("Đang xử lý..."); ?>',
                            text: '<?= __("Vui lòng đợi, quá trình này có thể mất vài phút"); ?>',
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
                                action: 'cleanupOrders',
                                token: '<?= $getUser['token']; ?>',
                                days_to_keep: days,
                                cleanup_type: cleanupType
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
                                    text: '<?= __("Có lỗi xảy ra khi dọn dẹp đơn hàng"); ?>',
                                    icon: 'error',
                                    confirmButtonText: '<?= __("OK"); ?>'
                                });
                            }
                        });
                    }
                });
            }, 300);
        });

        // Khi thay đổi loại dọn dẹp hoặc số ngày, reset nút xác nhận
        $('input[name="cleanup_type"], #cleanup_days').on('change input', function() {
            $('#btnConfirmCleanup').prop('disabled', true);
            $('#cleanup_preview_text').text('<?= __("Nhấn \"Xem trước\" để xem số đơn hàng sẽ bị ảnh hưởng"); ?>');
        });

        // ======== Export Orders Functions ========
        // Khởi tạo Sortable cho danh sách cột export
        if (typeof Sortable !== 'undefined' && document.getElementById('exportColumnsList')) {
            new Sortable(document.getElementById('exportColumnsList'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.fa-grip-vertical'
            });
        }
    </script>

    <script>
        // Hiển thị modal export
        function showExportModal() {
            var selectedIds = getSelectedOrderIds();
            if (selectedIds.length === 0) {
                showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
                return;
            }
            var modalEl = document.getElementById('exportOrdersModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                $(modalEl).addClass('show').css('display', 'block');
                $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
            }
        }

        // Chọn/bỏ chọn tất cả cột
        function toggleAllExportColumns(checked) {
            $('.export-col-checkbox').prop('checked', checked);
        }

        // Lấy danh sách ID đã chọn cho export
        function getSelectedOrderIds() {
            var selectedIds = [];
            $('.checkbox_product:checked').each(function() {
                selectedIds.push($(this).val());
            });
            return selectedIds;
        }

        // Xác nhận export đơn hàng
        function confirmExportOrders() {
            var selectedIds = getSelectedOrderIds();
            if (selectedIds.length === 0) {
                showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
                return;
            }

            // Lấy loại file
            var fileType = $('input[name="exportFileType"]:checked').val() || 'txt';

            // Lấy danh sách cột được chọn theo thứ tự
            var columns = [];
            $('#exportColumnsList li').each(function() {
                var $checkbox = $(this).find('.export-col-checkbox');
                if ($checkbox.prop('checked')) {
                    columns.push($checkbox.val());
                }
            });

            if (columns.length === 0) {
                showMessage('<?= __("Vui lòng chọn ít nhất một cột để xuất"); ?>', 'error');
                return;
            }

            // Gọi AJAX để export
            $('#confirmExportBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang tải..."); ?>');

            $.ajax({
                url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'exportProductOrders',
                    token: '<?= $getUser['token']; ?>',
                    ids: selectedIds,
                    file_type: fileType,
                    columns: columns
                },
                success: function(result) {
                    $('#confirmExportBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');

                    if (result.status == 'success') {
                        // Tạo file và download
                        var content = result.data.content;
                        var filename = result.data.filename;
                        var mimeType = fileType === 'csv' ? 'text/csv;charset=utf-8;' : 'text/plain;charset=utf-8;';

                        // Thêm BOM cho UTF-8
                        var bom = '\uFEFF';
                        var blob = new Blob([bom + content], {
                            type: mimeType
                        });
                        var link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);

                        showMessage(result.msg, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('exportOrdersModal')).hide();
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    $('#confirmExportBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');
                    showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                }
            });
        }
    </script>
<?php endif; ?>