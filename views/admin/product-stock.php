<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Kho hàng sản phẩm | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['code'])) {
    $code = check_string($_GET['code']);
} else {
    redirect(base_url_admin('products'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
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
$where = " `product_code` = '$code' ";
$create_gettime = '';
$uid = '';
$shortByDate  = '';
$user_id = '';
$username = '';
$account = '';

if (!empty($_GET['account'])) {
    $account = check_string($_GET['account']);
    $where .= ' AND `account` LIKE "%' . $account . '%" ';
}
if (!empty($_GET['uid'])) {
    $uid = check_string($_GET['uid']);
    $where .= ' AND `uid` LIKE "%' . $uid . '%" ';
}
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
    $where .= ' AND `user_id` = "' . $user_id . '" ';
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `product_stock` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `product_stock` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("product-stock&limit=$limit&shortByDate=$shortByDate&code=$code&uid=$uid&create_gettime=$create_gettime&user_id=$user_id&username=$username&account=$account&"), $from, $totalDatatable, $limit);



?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button"
                    class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('products'); ?>"><i class="fa-solid fa-arrow-left"></i></a> Quản lý kho hàng
                "<b style="color:red;"><?= $code; ?></b>"</h1>
        </div>
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            SẢN PHẨM ĐANG SỬ DỤNG KHO HÀNG NÀY
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive mb-2">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th><?= __('Sản phẩm'); ?></th>
                                        <th class="text-center"><?= __('Chuyên mục'); ?></th>
                                        <th class="text-center"><?= __('Giá bán'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($CMSNT->get_list(" SELECT * FROM `products` WHERE `code` = '$code' ") as $product): ?>
                                        <tr onchange="updateFormProduct(`<?= $product['id']; ?>`)">
                                            <td>
                                                <a type="button"
                                                    href="<?= base_url_admin('product-edit&id=' . $product['id']); ?>"
                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                    title="<?= __('Chỉnh sửa'); ?>">
                                                    <i class="fa fa-pencil-alt"></i>
                                                </a>
                                                <a type="button" onclick="removeProduct('<?= $product['id']; ?>')"
                                                    class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                    title="<?= __('Xóa'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch form-check-lg">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="status<?= $product['id']; ?>" value="1"
                                                        <?= $product['status'] == 1 ? 'checked=""' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $product['name']; ?>
                                            </td>
                                            <td class="text-center"><span
                                                    class="badge bg-primary"><?= getRowRealtime('categories', $product['category_id'], 'name'); ?></span>
                                            </td>
                                            <td class="text-right"><span
                                                    class="badge bg-danger"><?= format_currency($product['price']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-right">
                            <a type="button" target="_blank" href="<?= base_url_admin('product-add&code=' . $code); ?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Tạo sản phẩm sử dụng kho hàng
                                <?= $code; ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            NHẬP TÀI KHOẢN VÀO KHO HÀNG
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#nhap_tung_tai_khoan"
                                    class="btn btn-outline-danger btn-w-lg btn-wave mb-2"><i
                                        class="fa-solid fa-file"></i> Nhập từng tài
                                    khoản</button>
                            </div>
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#nhap_nhieu_tai_khoan"
                                    class="btn btn-outline-primary btn-w-lg btn-wave mb-2"><i
                                        class="fa-solid fa-folder"></i> Nhập nhiều tài
                                    khoản</button>
                            </div>
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#nhap_tai_khoan_bang_txt"
                                    class="btn btn-outline-info btn-w-lg btn-wave mb-2"><i class='bx bxs-file-txt'
                                        style="font-size: 16px;"></i> Nhập bằng tệp
                                    .txt</button>
                            </div>
                            <!-- <div class="col-xl-6 d-grid gap-2">
                                <button type="button" class="btn btn-outline-success btn-w-lg btn-wave mb-2"><i
                                        class="fa-solid fa-file-csv"></i> Nhập bằng tệp
                                    .csv</button>
                            </div> -->
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#nhap_bang_api"
                                    class="btn btn-outline-dark btn-w-lg btn-wave mb-2"><i class="fa-solid fa-code"></i>
                                    Nhập bằng API</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            XÓA TÀI KHOẢN KHỎI KHO HÀNG
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_nhieu_tai_khoan"
                                    class="btn btn-danger btn-w-lg btn-wave mb-2"><i class="fa-solid fa-trash-can"></i>
                                    Xóa nhiều tài khoản</button>
                            </div>
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_toan_bo_tai_khoan"
                                    class="btn btn-danger-gradient btn-wave mb-2"><i class="fa-solid fa-trash"></i>
                                    Xóa toàn bộ tài khoản đang bán</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            TÀI KHOẢN <strong style="color:green;">LIVE</strong> ĐANG BÁN
                        </div>
                        <div class="btn-list">

                            <button type="button" onclick="viewListLIVE(`<?= $code; ?>`)" id="btn_viewListLIVE"
                                class="btn btn-success btn-sm my-1 me-2"><i class="fa-solid fa-copy"></i> TÀI KHOẢN LIVE
                                <span
                                    class="badge ms-2 bg-dark text-white"><?= format_cash($totalDatatable); ?></span></button>
                            <button type="button" onclick="viewListDIE(`<?= $code; ?>`)" id="btn_viewListDIE"
                                class="btn btn-danger btn-sm my-1 me-2"><i class="fa-solid fa-copy"></i> TÀI KHOẢN DIE
                                <span
                                    class="badge ms-2 bg-dark text-white"><?= format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM `product_die` WHERE `product_code` = '$code' ")['COUNT(id)']); ?></span>

                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="product-stock">
                                <input type="hidden" name="code" value="<?= $code; ?>">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $uid; ?>" name="uid"
                                        placeholder="UID">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $account; ?>" name="account"
                                        placeholder="Tài khoản">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $user_id; ?>" name="user_id"
                                        placeholder="ID Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $username; ?>" name="username"
                                        placeholder="Username Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?= $create_gettime; ?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?= __('Search'); ?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?= base_url_admin('product-stock&code=' . $code); ?>"><i
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
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1.000</option>
                                        <option <?= $limit == 2000 ? 'selected' : ''; ?> value="2000">2.000</option>
                                        <option <?= $limit == 5000 ? 'selected' : ''; ?> value="5000">5.000</option>
                                        <option <?= $limit == 10000 ? 'selected' : ''; ?> value="10000">10.000</option>
                                        <option <?= $limit == 20000 ? 'selected' : ''; ?> value="20000">20.000</option>
                                        <option <?= $limit == 50000 ? 'selected' : ''; ?> value="50000">50.000</option>
                                        <option <?= $limit == 100000 ? 'selected' : ''; ?> value="100000">100.000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?>
                                        </option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?>
                                        </option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3">
                                            <?= __('Tháng này'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox_product_stock" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center">UID</th>
                                        <th class="text-center">Tài khoản</th>
                                        <th class="text-center">Seller</th>
                                        <th class="text-center">Thời gian</th>
                                        <th class="text-center">Check live gần nhất</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="form-check form-check-md d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input checkbox_product_stock"
                                                        data-id="<?= $row['id']; ?>" data-checkbox="<?= htmlspecialchars($row['account'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        name="checkbox_product_stock" value="<?= $row['id']; ?>" />
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= $row['uid']; ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <textarea rows="1" readonly class="form-control"><?= $row['account']; ?></textarea>
                                            </td>
                                            <td class="text-center"><a class="text-primary"
                                                    href="<?= base_url_admin('user-edit&id=' . $row['seller']); ?>"><?= getRowRealtime("users", $row['seller'], "username"); ?>
                                                    [ID <?= $row['seller']; ?>]</a>
                                            </td>
                                            <td class="text-center">
                                                <small data-toggle="tooltip" data-placement="bottom"
                                                    title="<?= timeAgo(strtotime($row['create_gettime'])); ?>"><?= $row['create_gettime']; ?></small>
                                            </td>
                                            <td class="text-center"><span
                                                    class="badge rounded-pill bg-dark text-white" data-toggle="tooltip" data-placement="bottom"
                                                    title="<?= date("H:i:s d-m-Y", $row['time_check_live']); ?>"><?= timeAgo($row['time_check_live']); ?></span>
                                            </td>
                                            <td class="text-center"><b><?= $row['type']; ?></b></td>
                                            <td class="text-center">
                                                <a type="button" onclick="removeAccount('<?= $row['id']; ?>')"
                                                    class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                    title="<?= __('Xóa'); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <td colspan="9">
                                        <div class="btn-list">
                                            <button type="button" onclick="exportDataTXT()" id="exportDataTXT"
                                                class="btn btn-outline-primary shadow-primary btn-wave btn-sm"><i
                                                    class="fa-solid fa-file-export"></i> XUẤT TỆP .TXT</button>
                                            <button type="button" onclick="exportDataClipboard()"
                                                id="exportDataClipboard"
                                                class="btn btn-outline-success shadow-success btn-wave btn-sm"><i
                                                    class="fa-solid fa-copy"></i> COPY</button>
                                            <button type="button" onclick="exportUIDClipboard()" id="exportUIDClipboard"
                                                class="btn btn-outline-info shadow-info btn-wave btn-sm"><i
                                                    class="fa-regular fa-copy"></i> COPY UID</button>
                                            <button type="button" onclick="confirmDeleteAccount()"
                                                id="confirmDeleteAccount"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> DELETE</button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= format_cash($limit); ?> of
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

<div class="modal fade" id="nhap_tung_tai_khoan" tabindex="-1" aria-labelledby="h6_nhap_tung_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-import">
                <h6 class="modal-title" id="h6_nhap_tung_tai_khoan"><span class="header-icon"><i class="fa-solid fa-file"></i></span> NHẬP TỪNG TÀI KHOẢN VÀO KHO HÀNG</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="importSingleLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2">Đang nhập dữ liệu...</h5>
                        <p class="text-muted mb-0" id="importSingleProgress">Vui lòng chờ trong giây lát</p>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="text-area" class="form-label">Tài khoản cần thêm:</label>
                    <textarea class="form-control" id="single_accounts" placeholder="Định dạng UID|PASS|..." rows="2"
                        required></textarea>
                </div>
                <div class="form-check form-check-md d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" value="1" id="dsg9898w" name="loc_trung_uid">
                    <label class="form-check-label" for="dsg9898w">
                        Lọc trùng UID tài khoản đang bán
                    </label>
                </div>
                <div class="form-check form-check-md d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" value="1" id="dsg9898w_sold" name="loc_trung_uid_sold">
                    <label class="form-check-label" for="dsg9898w_sold">
                        Lọc trùng UID tài khoản đã bán
                    </label>
                </div>
                <div class="stock-tip-box"><i class="fa-solid fa-lightbulb me-1"></i> Tắt lọc trùng UID sẽ giúp tăng tốc độ tải sản phẩm lên.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" id="btnCancelSingle">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitSingle" onclick="submitImportSingle()"><i
                        class="fa fa-fw fa-plus me-1"></i> Submit</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="nhap_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_nhap_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-import">
                <h6 class="modal-title" id="h6_nhap_nhieu_tai_khoan"><span class="header-icon"><i class="fa-solid fa-folder"></i></span> NHẬP NHIỀU TÀI KHOẢN VÀO KHO HÀNG</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="importMultiLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2">Đang nhập dữ liệu...</h5>
                        <p class="text-muted mb-0" id="importMultiProgress">Vui lòng chờ trong giây lát</p>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="text-area" class="form-label">Tài khoản cần thêm: (1 dòng 1 tài khoản)</label>
                    <textarea class="form-control" id="accounts" placeholder="UID|PASS|...
UID|PASS|...
UID|PASS|...
UID|PASS|..." rows="5" required></textarea>
                    <small>Nhấn Submit để thêm <strong style="color: red;" id="countAdd">0</strong> tài
                        khoản</small>
                </div>
                <div class="form-check form-check-md d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" value="1" id="a9895w22" name="loc_trung_uid_multi">
                    <label class="form-check-label" for="a9895w22">
                        Lọc trùng UID tài khoản đang bán
                    </label>
                </div>
                <div class="form-check form-check-md d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" value="1" id="a9895w22_sold" name="loc_trung_uid_sold_multi">
                    <label class="form-check-label" for="a9895w22_sold">
                        Lọc trùng UID tài khoản đã bán
                    </label>
                </div>
                <div class="stock-tip-box"><i class="fa-solid fa-lightbulb me-1"></i> Tắt lọc trùng UID sẽ giúp tăng tốc độ tải sản phẩm lên.</div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var textarea = document.getElementById('accounts');
                        var countAdd = document.getElementById("countAdd");

                        if (textarea && countAdd) {
                            textarea.addEventListener("input", function() {
                                var lines = textarea.value.split('\n');
                                var nonEmptyLinesCount = lines.filter(function(line) {
                                    return line.trim().length >
                                        0;
                                }).length;
                                countAdd.innerText = nonEmptyLinesCount;
                            });
                        }
                    });
                </script>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" id="btnCancelMulti">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitMulti" onclick="submitImportMulti()"><i
                        class="fa fa-fw fa-plus me-1"></i> Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="nhap_tai_khoan_bang_txt" tabindex="-1" aria-labelledby="h6_nhap_tai_khoan_bang_txt"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-import">
                <h6 class="modal-title" id="h6_nhap_tai_khoan_bang_txt"><span class="header-icon"><i class="bx bxs-file-txt"></i></span> NHẬP TÀI KHOẢN BẰNG TỆP .TXT</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="importTXTLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2">Đang nhập dữ liệu...</h5>
                        <p class="text-muted mb-0" id="importTXTProgress">Vui lòng chờ trong giây lát</p>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <label for="formFile" class="form-label">Tải lên tệp tài khoản .txt</label>
                    <input class="form-control" type="file" id="txt_file_input" accept=".txt" required>
                </div>
                <div class="mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-1">Xem trước dữ liệu:</h6>
                            <div id="txt_preview" class="text-muted">
                                <i class="fa-solid fa-info-circle me-1"></i>Chọn file để xem trước...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-check form-check-md d-flex align-items-center mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="txt_loc_trung_uid" name="loc_trung_uid_txt">
                    <label class="form-check-label" for="txt_loc_trung_uid">
                        Lọc trùng UID tài khoản đang bán
                    </label>
                </div>
                <div class="form-check form-check-md d-flex align-items-center mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="txt_loc_trung_uid_sold" name="loc_trung_uid_sold_txt">
                    <label class="form-check-label" for="txt_loc_trung_uid_sold">
                        Lọc trùng UID tài khoản đã bán
                    </label>
                </div>
                <ul>
                    <li>Chỉ nhập tệp định dạng .TXT</li>
                    <li>1 dòng 1 tài khoản</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" id="btnCancelTXT">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitTXT" onclick="submitImportTXT()" disabled><i
                        class="fa fa-fw fa-plus me-1"></i> Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Modal Modernized -->
<style>
    /* === Modal Header Gradient === */
    .modal-header-import,
    .modal-header-delete,
    .modal-header-api,
    .modal-header-view {
        color: #fff;
        border-bottom: none;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .modal-header-import .modal-title,
    .modal-header-delete .modal-title,
    .modal-header-api .modal-title,
    .modal-header-view .modal-title {
        color: #fff !important;
    }

    .modal-header-import {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .modal-header-import .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: 0.3px;
    }

    .modal-header-import .modal-title .header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .modal-header-import .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-header-import .btn-close:hover {
        opacity: 1;
    }

    .modal-header-delete {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    }

    .modal-header-delete .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
    }

    .modal-header-delete .modal-title .header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .modal-header-delete .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-header-api {
        background: linear-gradient(135deg, #0abde3 0%, #10ac84 100%);
    }

    .modal-header-api .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
    }

    .modal-header-api .modal-title .header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .modal-header-api .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    .modal-header-view {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .modal-header-view .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
    }

    .modal-header-view .modal-title .header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .modal-header-view .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    /* === Modal Content === */
    .modal-stock .modal-content {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .modal-stock .modal-body {
        padding: 1.5rem;
    }

    .modal-stock .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
        gap: 8px;
    }

    /* === Loading Overlay === */
    .import-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.96);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: inherit;
        backdrop-filter: blur(4px);
    }

    .import-loading-content {
        text-align: center;
        padding: 30px;
    }

    .import-loading-content h5 {
        color: #1e293b;
        font-weight: 600;
    }

    .import-loading-content p {
        font-size: 14px;
    }

    .import-loading-overlay .spinner-border {
        animation: spinner-border .75s linear infinite, pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    /* === Result Box === */
    .import-result-box {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        background: #f8f9fa;
        margin-top: 10px;
    }

    .import-result-box .result-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 5px;
        border-bottom: 1px solid #e9ecef;
    }

    .import-result-box .result-item:last-child {
        border-bottom: none;
    }

    /* === Tip Box === */
    .stock-tip-box {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 10px;
        padding: 12px 16px;
        margin-top: 12px;
        font-size: 13px;
        color: #475569;
    }

    .stock-tip-box i {
        color: #667eea;
    }

    /* === Dark Mode === */
    [data-theme="dark"] .import-loading-overlay {
        background: rgba(30, 41, 59, 0.96);
    }

    [data-theme="dark"] .import-loading-content h5 {
        color: #f8fafc;
    }

    [data-theme="dark"] .import-loading-content p {
        color: #94a3b8 !important;
    }

    [data-theme="dark"] .stock-tip-box {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #94a3b8;
    }

    [data-theme="dark"] .import-result-box {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .import-result-box .result-item {
        border-color: #334155;
    }
</style>

<!-- JS Import AJAX -->
<script>
    var txtFileData = [];

    // Xử lý khi chọn file TXT
    document.addEventListener('DOMContentLoaded', function() {
        var txtInput = document.getElementById('txt_file_input');
        if (txtInput) {
            txtInput.addEventListener('change', function(e) {
                handleTXTFile(e.target.files[0]);
            });
        }
    });

    function handleTXTFile(file) {
        if (!file) return;
        if (!file.name.endsWith('.txt')) {
            showMessage('Vui lòng chọn file .TXT', 'error');
            document.getElementById('txt_file_input').value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            var content = e.target.result;
            var lines = content.split(/\r?\n/);
            txtFileData = [];
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();
                if (line !== '') txtFileData.push(line);
            }
            if (txtFileData.length === 0) {
                document.getElementById('txt_preview').innerHTML = '<span class="text-warning"><i class="fa-solid fa-exclamation-circle me-1"></i>Không tìm thấy dữ liệu hợp lệ</span>';
                document.getElementById('btnSubmitTXT').disabled = true;
                return;
            }
            var html = '<div class="alert alert-success mb-0 py-2">';
            html += '<i class="fa-solid fa-check-circle me-1"></i>';
            html += '<strong>Tìm thấy: ' + txtFileData.length + ' tài khoản</strong><br>';
            html += '<small>Xem trước 5 dòng đầu:</small><br>';
            html += '<ul class="mb-0 mt-1" style="font-size:12px;">';
            for (var i = 0; i < Math.min(5, txtFileData.length); i++) {
                var display = txtFileData[i].length > 80 ? txtFileData[i].substring(0, 80) + '...' : txtFileData[i];
                html += '<li><code>' + $('<span>').text(display).html() + '</code></li>';
            }
            html += '</ul></div>';
            document.getElementById('txt_preview').innerHTML = html;
            document.getElementById('btnSubmitTXT').disabled = false;
        };
        reader.readAsText(file, 'UTF-8');
    }

    // Hiển thị kết quả import
    function showImportResult(data) {
        var html = '<div class="import-result-box">';
        html += '<div class="result-item"><span>📊 Tổng xử lý:</span><strong>' + data.total + ' tài khoản</strong></div>';
        html += '<div class="result-item"><span>✅ Thêm mới:</span><strong class="text-success">' + data.added + '</strong></div>';
        html += '<div class="result-item"><span>🔄 Cập nhật:</span><strong class="text-info">' + data.updated + '</strong></div>';
        if (data.skip_sold > 0) {
            html += '<div class="result-item"><span>🚫 Bỏ qua (đã bán):</span><strong class="text-danger">' + data.skip_sold + '</strong></div>';
        }
        html += '</div>';
        return html;
    }

    // Import từng tài khoản
    function submitImportSingle() {
        var accounts = $('#single_accounts').val().trim();
        if (!accounts) {
            showMessage('Vui lòng nhập tài khoản cần thêm', 'error');
            return;
        }
        $.ajax({
            url: "<?= base_url('ajaxs/admin/create.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'importAccountStock',
                code: '<?= $code; ?>',
                accounts: accounts,
                import_type: 'an',
                loc_trung_uid: $('#dsg9898w').is(':checked') ? 1 : 0,
                loc_trung_uid_sold: $('#dsg9898w_sold').is(':checked') ? 1 : 0
            },
            beforeSend: function() {
                $('#importSingleLoading').removeClass('d-none');
                $('#importSingleProgress').text('Đang nhập 1 tài khoản...');
                $('#btnSubmitSingle').prop('disabled', true);
                $('#btnCancelSingle').prop('disabled', true);
            },
            success: function(result) {
                $('#importSingleLoading').addClass('d-none');
                if (result.status == 'success') {
                    Swal.fire({
                        title: 'Import thành công!',
                        html: showImportResult(result.data),
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    showMessage(result.msg, 'error');
                    $('#btnSubmitSingle').prop('disabled', false);
                    $('#btnCancelSingle').prop('disabled', false);
                }
            },
            error: function() {
                $('#importSingleLoading').addClass('d-none');
                showMessage('Đã xảy ra lỗi', 'error');
                $('#btnSubmitSingle').prop('disabled', false);
                $('#btnCancelSingle').prop('disabled', false);
            }
        });
    }

    // Import nhiều tài khoản
    function submitImportMulti() {
        var accounts = $('#accounts').val().trim();
        if (!accounts) {
            showMessage('Vui lòng nhập tài khoản cần thêm', 'error');
            return;
        }
        var lines = accounts.split(/\r?\n/).filter(function(l) {
            return l.trim() !== '';
        });
        $.ajax({
            url: "<?= base_url('ajaxs/admin/create.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'importAccountStock',
                code: '<?= $code; ?>',
                accounts: accounts,
                import_type: 'multi',
                loc_trung_uid: $('#a9895w22').is(':checked') ? 1 : 0,
                loc_trung_uid_sold: $('#a9895w22_sold').is(':checked') ? 1 : 0
            },
            beforeSend: function() {
                $('#importMultiLoading').removeClass('d-none');
                $('#importMultiProgress').text('Đang nhập ' + lines.length + ' tài khoản...');
                $('#btnSubmitMulti').prop('disabled', true);
                $('#btnCancelMulti').prop('disabled', true);
                $('#accounts').prop('disabled', true);
            },
            success: function(result) {
                $('#importMultiLoading').addClass('d-none');
                if (result.status == 'success') {
                    Swal.fire({
                        title: 'Import thành công!',
                        html: showImportResult(result.data),
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    showMessage(result.msg, 'error');
                    $('#btnSubmitMulti').prop('disabled', false);
                    $('#btnCancelMulti').prop('disabled', false);
                    $('#accounts').prop('disabled', false);
                }
            },
            error: function() {
                $('#importMultiLoading').addClass('d-none');
                showMessage('Đã xảy ra lỗi', 'error');
                $('#btnSubmitMulti').prop('disabled', false);
                $('#btnCancelMulti').prop('disabled', false);
                $('#accounts').prop('disabled', false);
            }
        });
    }

    // Import từ TXT
    function submitImportTXT() {
        if (txtFileData.length === 0) {
            showMessage('Không có dữ liệu để nhập', 'error');
            return;
        }
        var stockDataText = txtFileData.join('\n');
        $.ajax({
            url: "<?= base_url('ajaxs/admin/create.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'importAccountStock',
                code: '<?= $code; ?>',
                accounts: stockDataText,
                import_type: 'multi',
                loc_trung_uid: $('#txt_loc_trung_uid').is(':checked') ? 1 : 0,
                loc_trung_uid_sold: $('#txt_loc_trung_uid_sold').is(':checked') ? 1 : 0
            },
            beforeSend: function() {
                $('#importTXTLoading').removeClass('d-none');
                $('#importTXTProgress').text('Đang nhập ' + txtFileData.length + ' tài khoản...');
                $('#btnSubmitTXT').prop('disabled', true);
                $('#btnCancelTXT').prop('disabled', true);
            },
            success: function(result) {
                $('#importTXTLoading').addClass('d-none');
                if (result.status == 'success') {
                    Swal.fire({
                        title: 'Import thành công!',
                        html: showImportResult(result.data),
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    showMessage(result.msg, 'error');
                    $('#btnSubmitTXT').prop('disabled', false);
                    $('#btnCancelTXT').prop('disabled', false);
                }
            },
            error: function() {
                $('#importTXTLoading').addClass('d-none');
                showMessage('Đã xảy ra lỗi', 'error');
                $('#btnSubmitTXT').prop('disabled', false);
                $('#btnCancelTXT').prop('disabled', false);
            }
        });
    }
</script>
<div class="modal fade" id="xoa_toan_bo_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_toan_bo_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-stock">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header modal-header-delete">
                    <h6 class="modal-title" id="h6_xoa_toan_bo_tai_khoan"><span class="header-icon"><i class="fa-solid fa-triangle-exclamation"></i></span> XÓA TOÀN BỘ TÀI KHOẢN ĐANG BÁN</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Hệ thống sẽ thực hiện XÓA TOÀN BỘ tài khoản đang bán của kho hàng <b><?= $code; ?></b> nếu bạn xác nhận vào Input dưới đây.</p>
                    <p>Để xác nhận XÓA TOÀN BỘ tài khoản trong kho hàng <b><?= $code; ?></b>, vui lòng nhập vào ô dưới đây nội dung là <b style="color:red;font-size:15px;">toi dong y</b> để tiến hành xóa.</p>
                    <input class="form-control" type="text" id="confirm_empty_list_account" placeholder="Nhập nội dung toi dong y nếu bạn chắc chắn đã hiểu nội dung trên">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="btn_format_list_account" class="btn btn-primary btn-sm"><i
                            class="fa fa-fw fa-trash me-1"></i> Xóa ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $("#btn_format_list_account").click(function() {
        Swal.fire({
            title: "Bạn có chắc không?",
            text: "Hệ thống sẽ xóa vĩnh viễn toàn bộ dữ liệu tài khoản đang bán của kho hàng <?= $code; ?> khi bạn nhấn Đồng Ý",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Đóng"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/admin/remove.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'empty_list_account_stock',
                        token: '<?= $getUser['token']; ?>',
                        confirm_empty_list_account: $('#confirm_empty_list_account').val(),
                        id: '<?= $code; ?>'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout("location.href = '';", 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });
    });
</script>
<div class="modal fade" id="xoa_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-delete">
                <h6 class="modal-title" id="h6_xoa_nhieu_tai_khoan"><span class="header-icon"><i class="fa-solid fa-trash-can"></i></span> XÓA NHIỀU TÀI KHOẢN <strong>LIVE</strong> ĐANG BÁN</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="removeMultiLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2">Đang xóa tài khoản...</h5>
                        <p class="text-muted mb-0" id="removeMultiProgress">Vui lòng chờ trong giây lát</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="text-area" class="form-label">Tài khoản cần xóa: (1 dòng 1 tài khoản)</label>
                    <textarea class="form-control" id="accounts_remove" placeholder="UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID" rows="5" required></textarea>
                    <small>Nhấn Submit để xóa <strong style="color: red;" id="countRemove">0</strong> tài khoản</small>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var textarea = document.getElementById('accounts_remove');
                            var countRemove = document.getElementById("countRemove");
                            if (textarea && countRemove) {
                                textarea.addEventListener("input", function() {
                                    var lines = textarea.value.split('\n');
                                    var nonEmptyLinesCount = lines.filter(function(line) {
                                        return line.trim().length > 0;
                                    }).length;
                                    countRemove.innerText = nonEmptyLinesCount;
                                });
                            }
                        });
                    </script>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" id="btnCancelRemove">Close</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnSubmitRemove" onclick="submitRemoveMulti()"><i
                        class="fa fa-fw fa-trash me-1"></i> Submit</button>
            </div>
        </div>
    </div>
</div>
<script>
    function showRemoveResult(data) {
        var html = '<div class="import-result-box">';
        html += '<div class="result-item"><span>📊 Tổng xử lý:</span><strong>' + data.total + ' tài khoản</strong></div>';
        html += '<div class="result-item"><span>🗑️ Đã xóa:</span><strong class="text-danger">' + data.removed + '</strong></div>';
        if (data.not_found > 0) {
            html += '<div class="result-item"><span>⚠️ Không tìm thấy:</span><strong class="text-warning">' + data.not_found + '</strong></div>';
        }
        html += '</div>';
        return html;
    }

    function submitRemoveMulti() {
        var accounts = $('#accounts_remove').val().trim();
        if (!accounts) {
            showMessage('Vui lòng nhập tài khoản cần xóa', 'error');
            return;
        }
        var lines = accounts.split(/\r?\n/).filter(function(l) {
            return l.trim() !== '';
        });
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Bạn có chắc muốn xóa ' + lines.length + ' tài khoản khỏi kho hàng?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/admin/create.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'removeAccountStock',
                        code: '<?= $code; ?>',
                        accounts: accounts
                    },
                    beforeSend: function() {
                        $('#removeMultiLoading').removeClass('d-none');
                        $('#removeMultiProgress').text('Đang xóa ' + lines.length + ' tài khoản...');
                        $('#btnSubmitRemove').prop('disabled', true);
                        $('#btnCancelRemove').prop('disabled', true);
                        $('#accounts_remove').prop('disabled', true);
                    },
                    success: function(result) {
                        $('#removeMultiLoading').addClass('d-none');
                        if (result.status == 'success') {
                            Swal.fire({
                                title: 'Xóa thành công!',
                                html: showRemoveResult(result.data),
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            showMessage(result.msg, 'error');
                            $('#btnSubmitRemove').prop('disabled', false);
                            $('#btnCancelRemove').prop('disabled', false);
                            $('#accounts_remove').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $('#removeMultiLoading').addClass('d-none');
                        showMessage('Đã xảy ra lỗi', 'error');
                        $('#btnSubmitRemove').prop('disabled', false);
                        $('#btnCancelRemove').prop('disabled', false);
                        $('#accounts_remove').prop('disabled', false);
                    }
                });
            }
        });
    }
</script>
<div class="modal fade" id="nhap_bang_api" tabindex="-1" aria-labelledby="h6_nhap_bang_api" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-api">
                <h6 class="modal-title" id="h6_nhap_bang_api"><span class="header-icon"><i class="fa-solid fa-code"></i></span> NHẬP TÀI KHOẢN BẰNG API</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="text-area" class="form-label">API nhập tài khoản vào kho hàng này</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="url_api"
                            value="<?= base_url('api/importAccount.php?code=' . $code . '&api_key=' . $getUser['api_key'] . '&account=&filter=1'); ?>">
                        <button class="btn btn-info copy" data-clipboard-target="#url_api" type="button"
                            onclick="copy()">Copy</button>
                    </div>
                </div>
                <p>API Key của bạn là: <strong class="copy" style="color: blue;" id="api_key"
                        data-clipboard-target="#api_key" onclick="copy()" data-toggle="tooltip" data-placement="bottom"
                        title="Nhấn vào để Copy"><?= $getUser['api_key']; ?></strong> <button
                        onclick="changeAPIKey(`<?= $getUser['token']; ?>`)" data-toggle="tooltip" data-placement="bottom"
                        title="Thay đổi API KEY khác nếu API KEY cũ của bạn bị lộ ra ngoài"
                        class="btn btn-danger btn-sm"><i class="fa-solid fa-rotate"></i></button></p>
                <p>Trong đó:</p>
                <ul>
                    <li><strong>code</strong>: mã của kho hàng, ví dụ kho hàng hiện tại của bạn là <strong
                            style="color:red;"><?= $code; ?></strong></li>
                    <li><strong>api_key</strong>: API Key của tài khoản admin có role <strong>Quản lý kho hàng sản phẩm</strong>
                        <span class="badge bg-primary-transparent">edit_stock_product</span>
                    </li>
                    <li><strong>filter</strong>: 1 để bật lọc trùng UID, 0 để tắt lọc trùng UID.</span>
                    </li>
                    <li><strong>account</strong>: tài khoản cần thêm vào kho hàng, có thể thêm nhiều tài khoản bằng cách nhập mỗi tài khoản trên một dòng (xuống dòng để phân tách).</li>
                </ul>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    function updateFormProduct(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'update_status_product',
                id: id,
                status: $('#status' + id + ':checked').val()
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
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

    function removeProduct(id) {
        cuteAlert({
            type: "question",
            title: "Xác Nhận Xóa sản phẩm",
            message: "Bạn có chắc chắn muốn xóa sản phẩm ID " + id + " không ?",
            confirmText: "Đồng Ý",
            cancelText: "Hủy"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: id,
                        action: 'removeProduct'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
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
        })
    }
</script>



<script>
    function postRemoveAccount(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'removeAccountStock',
                id: id
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
                } else {
                    showMessage(result.msg, 'error');
                }
            }
        });
    }

    function removeAccount(id) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa tài khoản",
            message: "Bạn có chắc chắn muốn xóa tài khoản này không ?",
            confirmText: "Đồng ý",
            cancelText: "Không"
        }).then((e) => {
            if (e) {
                postRemoveAccount(id);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        })
    }
</script>
<script>
    function confirmDeleteAccount() {
        var checkbox = document.getElementsByName('checkbox_product_stock');
        var isAnyCheckboxChecked = false;
        for (var i = 0; i < checkbox.length; i++) {
            if (checkbox[i].checked === true) {
                isAnyCheckboxChecked = true;
                break;
            }
        }
        if (!isAnyCheckboxChecked) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        var result = confirm('Bạn có đồng ý xóa các bản ghi đã chọn không?');
        if (result) {
            $('#confirmDeleteAccount').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
                .prop('disabled',
                    true);

            function postUpdatesSequentially(index) {
                if (index < checkbox.length) {
                    if (checkbox[index].checked === true) {
                        postRemoveAccount(checkbox[index].value);
                    }
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 100);
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            }
            postUpdatesSequentially(0);
        }
    }

    $(function() {
        $('#check_all_checkbox_product_stock').on('click', function() {
            $('.checkbox_product_stock').prop('checked', this.checked);
        });
        $('.checkbox_product_stock').on('click', function() {
            $('#check_all_checkbox_product_stock').prop('checked', $('.checkbox_product_stock:checked')
                .length === $('.checkbox_product_stock').length);
        });
    });
</script>

<script>
    function exportDataTXT() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        $('#exportDataTXT').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            var accountValue = checkbox.getAttribute('data-checkbox');
            if (accountValue) {
                // Trim giá trị để loại bỏ khoảng trắng thừa
                accountValue = accountValue.trim();
                // Nếu tài khoản có nhiều dòng, tách ra thành các dòng riêng
                var lines = accountValue.split(/\r?\n/);
                lines.forEach(function(line) {
                    line = line.trim();
                    // Chỉ thêm dòng không rỗng
                    if (line.length > 0) {
                        selectedData.push(line);
                    }
                });
            }
        });

        // Lấy số lượng dữ liệu được xuất
        var numberOfData = selectedData.length;

        // Chuyển đổi mảng thành chuỗi với mỗi giá trị trên một dòng
        var dataString = selectedData.join('\n');

        // Tạo timestamp cho tên file
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var seconds = String(now.getSeconds()).padStart(2, '0');
        var timestamp = year + '-' + month + '-' + day + '_' + hours + '-' + minutes + '-' + seconds;

        // Tạo một đối tượng Blob chứa dữ liệu
        var blob = new Blob([dataString], {
            type: 'text/plain'
        });

        // Tạo một đường link để tải xuống tệp tin TXT
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = '<?= $code; ?>_' + numberOfData + '_' + timestamp + '.txt';

        // Thêm đường link vào trang và kích hoạt sự kiện click để tải xuống
        document.body.appendChild(link);
        link.click();

        // Xóa đường link sau khi đã tải xuống
        document.body.removeChild(link);
        $('#exportDataTXT').html(
            '<i class="fa-solid fa-file-export"></i> XUẤT TỆP .TXT'
        ).prop('disabled',
            false);


    }
</script>

<script>
    function exportDataClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        $('#exportDataClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            // Đảm bảo rằng có một dòng mới sau mỗi giá trị
            selectedData.push(checkbox.getAttribute('data-checkbox').trim());
        });

        // Chuyển đổi mảng thành chuỗi, với mỗi giá trị trên một dòng
        var dataString = selectedData.join('\n');

        // Sao chép chuỗi vào clipboard
        navigator.clipboard.writeText(dataString).then(function() {
            showMessage("Nội dung đã được sao chép vào clipboard!", 'success');
            $('#exportDataClipboard').html(
                '<i class="fa-solid fa-copy"></i> COPY'
            ).prop('disabled',
                false);
        }).catch(function(error) {
            $('#exportDataClipboard').html(
                '<i class="fa-solid fa-copy"></i> COPY'
            ).prop('disabled',
                false);
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>

<script>
    function exportUIDClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        $('#exportUIDClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            // Lấy dữ liệu và chia nó dựa trên dấu '|'
            var fullData = checkbox.getAttribute('data-checkbox').trim();
            var splitData = fullData.split('|');
            // Kiểm tra để chắc chắn rằng dữ liệu tồn tại trước khi thêm vào mảng
            if (splitData.length > 0) {
                selectedData.push(splitData[0]); // Chỉ lấy phần trước dấu '|'
            }
        });

        // Chuyển đổi mảng thành chuỗi, với mỗi giá trị trên một dòng
        var dataString = selectedData.join('\n');

        // Sao chép chuỗi vào clipboard
        navigator.clipboard.writeText(dataString).then(function() {
            showMessage("Nội dung đã được sao chép vào clipboard!", 'success');
            $('#exportUIDClipboard').html(
                '<i class="fa-regular fa-copy"></i> COPY UID'
            ).prop('disabled',
                false);
        }).catch(function(error) {
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>

<script>
    function changeAPIKey(token) {
        cuteAlert({
            type: "question",
            title: "Bạn có chắc không?",
            message: "Hệ thống sẽ thay đổi API KEY nếu bạn nhấn Đồng Ý",
            confirmText: "Đồng ý",
            cancelText: "Không"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/client/auth.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        token: '<?= $getUser['token']; ?>',
                        action: 'changeAPIKey'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            document.getElementById("url_api").value =
                                '<?= base_url(); ?>api/importAccount.php?code=<?= $code; ?>&api_key=' +
                                result.api_key + '&account=';
                            document.getElementById("api_key").innerHTML = result.api_key;
                        } else {
                            Swal.fire({
                                title: "<?= __('Thất bại!'); ?>",
                                text: result.msg,
                                icon: "error"
                            });
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        })
    }
</script>
<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }
</script>

<div class="modal fade" id="viewListDIE" tabindex="-1" aria-labelledby="viewListDIE" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-delete">
                <h6 class="modal-title" id="viewListDIE"><span class="header-icon"><i class="fa-solid fa-skull-crossbones"></i></span> DANH SÁCH TÀI KHOẢN <strong>DIE</strong> CỦA KHO HÀNG <strong><?= $code; ?></strong></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control mb-2" id="coypyBox_viewListDIE" readonly rows="10"></textarea>
                <button type="button" id="btn_format_list_die" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Xóa toàn bộ</button>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox_viewListDIE"
                    class="btn btn-info shadow-info btn-wave copy">Copy</button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function viewListDIE(code) {
        var originalButtonContent = $('#btn_viewListDIE').html();
        $('#btn_viewListDIE').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        $.ajax({
            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'view_product_die',
                token: '<?= $getUser['token']; ?>',
                code: code
            },
            success: function(result) {
                $('#viewListDIE').modal('show');
                $('#coypyBox_viewListDIE').val(result.accounts);
                $('#btn_viewListDIE').html(originalButtonContent).prop('disabled', false);
            },
            error: function() {
                alert(html(result));
                location.reload();
            }
        });
    }
</script>

<div class="modal fade" id="viewListLIVE" tabindex="-1" aria-labelledby="viewListLIVE" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-stock">
        <div class="modal-content">
            <div class="modal-header modal-header-view">
                <h6 class="modal-title" id="viewListLIVE"><span class="header-icon"><i class="fa-solid fa-heart-pulse"></i></span> DANH SÁCH TÀI KHOẢN <strong>LIVE</strong> CỦA KHO HÀNG <strong><?= $code; ?></strong></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="coypyBox_viewListLIVE" readonly rows="10"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox_viewListLIVE"
                    class="btn btn-info shadow-info btn-wave copy">Copy</button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function viewListLIVE(code) {
        var originalButtonContent = $('#btn_viewListLIVE').html();
        $('#btn_viewListLIVE').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        $.ajax({
            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'view_product_live',
                token: '<?= $getUser['token']; ?>',
                code: code
            },
            success: function(result) {
                $('#viewListLIVE').modal('show');
                $('#coypyBox_viewListLIVE').val(result.accounts);
                $('#btn_viewListLIVE').html(originalButtonContent).prop('disabled', false);
            },
            error: function() {
                alert(html(result));
                location.reload();
            }
        });
    }
</script>

<script>
    $("#btn_format_list_die").click(function() {
        Swal.fire({
            title: "Bạn có chắc không?",
            text: "Hệ thống sẽ xóa vĩnh viễn toàn bộ dữ liệu tài khoản DIE của kho hàng <?= $code; ?> khi bạn nhấn Đồng Ý",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Đóng"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/admin/remove.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'empty_list_die',
                        token: '<?= $getUser['token']; ?>',
                        id: '<?= $code; ?>'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout("location.href = '';", 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });
    });
</script>