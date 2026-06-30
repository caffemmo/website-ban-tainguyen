<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Products') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
if (checkPermission($getUser['admin'], 'view_product') != true) {
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
$user_id = '';
$name = '';
$create_gettime = '';
$username = '';
$shortByDate  = '';
$category_id = '';
$supplier_id = '';
$status = '';
$code = '';
$sort_stock = '';
$sort_price = '';
$sort_stt = '';
$filter_stock = '';


if (!empty($_GET['code'])) {
    $code = check_string($_GET['code']);
    $where .= ' AND `code` = "' . $code . '" ';
}
if (!empty($_GET['status'])) {
    $status = check_string($_GET['status']);
    if ($status == 2) {
        $where .= ' AND `status` = 0 ';
    } else if ($status == 1) {
        $where .= ' AND `status` = 1 ';
    }
}
if (!empty($_GET['supplier_id'])) {
    $supplier_id = check_string($_GET['supplier_id']);
    if ($supplier_id == 'deleted') {
        // Lọc sản phẩm có supplier_id > 0 nhưng supplier đã bị xóa
        $where .= ' AND `supplier_id` > 0 AND `supplier_id` NOT IN (SELECT `id` FROM `suppliers`) ';
    } else {
        $supplier_id_value = $supplier_id == 'none' ? 0 : $supplier_id;
        $where .= ' AND `supplier_id` = "' . $supplier_id_value . '" ';
    }
}
if (!empty($_GET['category_id'])) {
    $category_id = check_string($_GET['category_id']);
    if ($category_id == 'none') {
        $where .= ' AND `category_id` <= 0 ';
    } else {
        $where .= ' AND `category_id` = "' . $category_id . '" ';
    }
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
if (!empty($_GET['name'])) {
    $name = check_string($_GET['name']);
    $where .= ' AND (`name` LIKE "%' . $name . '%" OR `api_name` LIKE "%' . $name . '%") ';
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
// Lọc sản phẩm hết hàng (tồn kho = 0)
if (!empty($_GET['filter_stock'])) {
    $filter_stock = check_string($_GET['filter_stock']);
    if ($filter_stock == 'out_of_stock') {
        // Sản phẩm hệ thống: đếm từ product_stock = 0, sản phẩm API: api_stock = 0
        $where .= " AND (
            (`supplier_id` = 0 AND (SELECT COUNT(*) FROM `product_stock` WHERE `product_stock`.`product_code` = `products`.`code`) = 0)
            OR (`supplier_id` > 0 AND `api_stock` = 0)
        ) ";
    } elseif ($filter_stock == 'in_stock') {
        // Sản phẩm còn hàng (tồn kho > 0)
        $where .= " AND (
            (`supplier_id` = 0 AND (SELECT COUNT(*) FROM `product_stock` WHERE `product_stock`.`product_code` = `products`.`code`) > 0)
            OR (`supplier_id` > 0 AND `api_stock` > 0)
        ) ";
    }
}
if (!empty($_GET['sort_stock'])) {
    $sort_stock = check_string($_GET['sort_stock']);
}
if (!empty($_GET['sort_price'])) {
    $sort_price = check_string($_GET['sort_price']);
}
if (!empty($_GET['sort_stt'])) {
    $sort_stt = check_string($_GET['sort_stt']);
}

// Xây dựng câu SQL với sắp xếp theo số lượng tồn kho Live và giá bán
$orderBy = " ORDER BY `id` DESC ";
$orderParts = [];

// Sắp xếp theo tồn kho
if ($sort_stock == 'asc' || $sort_stock == 'desc') {
    // Tính số lượng tồn kho Live: nếu supplier_id = 0 thì đếm từ product_stock, không thì dùng api_stock
    $orderParts[] = "CASE 
        WHEN `supplier_id` = 0 THEN (
            SELECT COUNT(*) FROM `product_stock` WHERE `product_stock`.`product_code` = `products`.`code`
        )
        ELSE `api_stock`
    END " . ($sort_stock == 'asc' ? 'ASC' : 'DESC');
}

// Sắp xếp theo giá bán
if ($sort_price == 'asc' || $sort_price == 'desc') {
    $orderParts[] = "`price` " . ($sort_price == 'asc' ? 'ASC' : 'DESC');
}

// Sắp xếp theo STT (ưu tiên)
if ($sort_stt == 'asc' || $sort_stt == 'desc') {
    $orderParts[] = "`stt` " . ($sort_stt == 'asc' ? 'ASC' : 'DESC');
}

// Kết hợp các điều kiện sắp xếp
if (!empty($orderParts)) {
    $orderBy = " ORDER BY " . implode(", ", $orderParts) . ", `id` DESC ";
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `products` WHERE $where $orderBy LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `products` WHERE $where ");

// Tạo URL pagination với các tham số filter
$pagination_url = base_url_admin('products');
$pagination_url .= '&limit=' . $limit;
if (!empty($user_id)) $pagination_url .= '&user_id=' . urlencode($user_id);
if (!empty($username)) $pagination_url .= '&username=' . urlencode($username);
if (!empty($name)) $pagination_url .= '&name=' . urlencode($name);
if (!empty($code)) $pagination_url .= '&code=' . urlencode($code);
if (!empty($status)) $pagination_url .= '&status=' . urlencode($status);
if (!empty($category_id)) $pagination_url .= '&category_id=' . urlencode($category_id);
if (!empty($supplier_id)) $pagination_url .= '&supplier_id=' . urlencode($supplier_id);
if (!empty($create_gettime)) $pagination_url .= '&create_gettime=' . urlencode($create_gettime);
if (!empty($shortByDate)) $pagination_url .= '&shortByDate=' . urlencode($shortByDate);
if (!empty($sort_stock)) $pagination_url .= '&sort_stock=' . urlencode($sort_stock);
if (!empty($sort_price)) $pagination_url .= '&sort_price=' . urlencode($sort_price);
if (!empty($sort_stt)) $pagination_url .= '&sort_stt=' . urlencode($sort_stt);
if (!empty($filter_stock)) $pagination_url .= '&filter_stock=' . urlencode($filter_stock);
$pagination_url .= '&';

$urlDatatable = pagination($pagination_url, $from, $totalDatatable, $limit);

?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-cart-shopping"></i> Products</h1>
            <div class="d-flex">
                <a type="button" href="<?= base_url_admin('product-add'); ?>"
                    class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                        class="ri-add-line fw-semibold align-middle"></i> Thêm sản phẩm mới</a>
            </div>
        </div>

        <!-- Form tìm kiếm -->
        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleFilterForm()">
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter me-2"></i><?= __('Bộ lọc tìm kiếm'); ?>
                </h6>
                <button type="button" class="btn btn-sm btn-light" id="toggleFilterBtn">
                    <i class="fa-solid fa-chevron-down" id="filterIcon"></i>
                </button>
            </div>
            <div class="card-body" id="filterFormBody" style="display: none;">
                <form method="GET" action="<?= base_url_admin(); ?>">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="products">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label"><?= __('ID User'); ?></label>
                            <input type="number" class="form-control" name="user_id"
                                value="<?= $user_id; ?>"
                                placeholder="<?= __('ID người dùng'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Username'); ?></label>
                            <input type="text" class="form-control" name="username"
                                value="<?= htmlspecialchars($username); ?>"
                                placeholder="<?= __('Username...'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('Tên sản phẩm'); ?></label>
                            <input type="text" class="form-control" name="name"
                                value="<?= htmlspecialchars($name); ?>"
                                placeholder="<?= __('Tên sản phẩm...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Mã kho hàng'); ?></label>
                            <input type="text" class="form-control" name="code"
                                value="<?= htmlspecialchars($code); ?>"
                                placeholder="<?= __('Mã kho hàng...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Trạng thái'); ?></label>
                            <select class="form-select" name="status">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option <?= $status == 1 ? 'selected' : ''; ?> value="1"><?= __('Hiển Thị'); ?></option>
                                <option <?= $status == 2 ? 'selected' : ''; ?> value="2"><?= __('Ẩn'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('Chuyên mục'); ?></label>
                            <select class="form-select js-example-basic-single" name="category_id">
                                <option value=""><?= __('Tất cả chuyên mục'); ?></option>
                                <option value="none" <?= $category_id == 'none' ? 'selected' : ''; ?>><?= __('Không có chuyên mục'); ?></option>
                                <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                    <option disabled value="<?= $option['id']; ?>"><?= $option['name']; ?></option>
                                    <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $option['id'] . "' ") as $option1): ?>
                                        <option <?= $category_id == $option1['id'] ? 'selected' : ''; ?>
                                            value="<?= $option1['id']; ?>">-- <?= $option1['name']; ?></option>
                                    <?php endforeach ?>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('API Supplier'); ?></label>
                            <select class="form-select js-example-basic-single" name="supplier_id">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="none" <?= $supplier_id == 'none' ? 'selected' : ''; ?>>
                                    <?= __('Sản phẩm hệ thống'); ?></option>
                                <option value="deleted" <?= $supplier_id == 'deleted' ? 'selected' : ''; ?>>
                                    <?= __('Supplier đã xóa'); ?></option>
                                <?php foreach ($CMSNT->get_list("SELECT * FROM `suppliers` ") as $supplier): ?>
                                    <option <?= $supplier_id == $supplier['id'] ? 'selected' : ''; ?>
                                        value="<?= $supplier['id']; ?>"><?= $supplier['domain']; ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('Thời gian'); ?></label>
                            <input type="text" name="create_gettime" class="form-control" id="daterange"
                                value="<?= htmlspecialchars($create_gettime); ?>" placeholder="<?= __('Chọn thời gian'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Lọc theo tồn kho'); ?></label>
                            <select name="filter_stock" class="form-select">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option <?= $filter_stock == 'out_of_stock' ? 'selected' : ''; ?> value="out_of_stock"><?= __('Hết hàng (= 0)'); ?></option>
                                <option <?= $filter_stock == 'in_stock' ? 'selected' : ''; ?> value="in_stock"><?= __('Còn hàng (> 0)'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Sắp xếp theo tồn kho'); ?></label>
                            <select name="sort_stock" class="form-select">
                                <option value=""><?= __('Mặc định'); ?></option>
                                <option <?= $sort_stock == 'asc' ? 'selected' : ''; ?> value="asc"><?= __('Tồn kho tăng dần'); ?></option>
                                <option <?= $sort_stock == 'desc' ? 'selected' : ''; ?> value="desc"><?= __('Tồn kho giảm dần'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Sắp xếp theo giá bán'); ?></label>
                            <select name="sort_price" class="form-select">
                                <option value=""><?= __('Mặc định'); ?></option>
                                <option <?= $sort_price == 'asc' ? 'selected' : ''; ?> value="asc"><?= __('Giá bán tăng dần'); ?></option>
                                <option <?= $sort_price == 'desc' ? 'selected' : ''; ?> value="desc"><?= __('Giá bán giảm dần'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Sắp xếp theo ưu tiên'); ?></label>
                            <select name="sort_stt" class="form-select">
                                <option value=""><?= __('Mặc định'); ?></option>
                                <option <?= $sort_stt == 'asc' ? 'selected' : ''; ?> value="asc"><?= __('Ưu tiên tăng dần'); ?></option>
                                <option <?= $sort_stt == 'desc' ? 'selected' : ''; ?> value="desc"><?= __('Ưu tiên giảm dần'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Lọc theo ngày'); ?></label>
                            <select name="shortByDate" class="form-select">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
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
                            <a href="<?= base_url_admin('products'); ?>" class="btn btn-secondary">
                                <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if (count($listDatatable) > 0): ?>
                            <!-- Thanh công cụ hàng loạt -->
                            <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">
                                            <span id="selectedCount">0</span> <?= __('sản phẩm đã chọn'); ?>
                                        </span>
                                    </div>
                                    <div class="btn-list">
                                        <button type="button" id="btn_cap_nhat_nhanh_main" class="btn btn-sm btn-info">
                                            <i class="fa-solid fa-pen-to-square me-1"></i><?= __('Cập nhật nhanh'); ?>
                                        </button>
                                        <button type="button" id="btn_delete_product_main" class="btn btn-sm btn-danger">
                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>
                                        </button>
                                        <button type="button" onclick="showExportProductsModal()" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-download me-1"></i><?= __('Xuất sản phẩm'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover border text-nowrap">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <div class="form-check form-check-md d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input" name="check_all"
                                                        id="check_all_checkbox_product" value="option1" style="width: 20px; height: 20px; cursor: pointer;">
                                                </div>
                                            </th>
                                            <th>Ưu tiên</th>
                                            <th class="text-center"><?= __('Thao tác'); ?></th>
                                            <th><?= __('Sản phẩm'); ?></th>
                                            <th class="text-center"><?= __('Chuyên mục'); ?></th>
                                            <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            <!-- <th class="text-center"><?= __('Ảnh'); ?></th> -->
                                            <th class="text-center"><?= __('Giá bán'); ?></th>
                                            <th class="text-center"><?= __('Tồn kho'); ?></th>
                                            <th class="text-center"><?= __('Chi tiết'); ?></th>
                                            <th class="text-center"><?= __('Seller'); ?></th>
                                            <th class="text-center"><?= __('Thời gian'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listDatatable as $product): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check form-check-md d-flex align-items-center">
                                                        <input type="checkbox" class="form-check-input checkbox_product"
                                                            data-id="<?= $product['id']; ?>" name="checkbox_product"
                                                            value="<?= $product['id']; ?>" style="width: 20px; height: 20px; cursor: pointer;" />
                                                    </div>
                                                </td>
                                                <td width="100px"><input onchange="post_update_stt_table_product(`<?= $product['id']; ?>`)"
                                                        id="stt<?= $product['id']; ?>" class="form-control" type="number"
                                                        value="<?= $product['stt']; ?>"></td>
                                                <td>
                                                    <?php if ($product['supplier_id'] == 0): ?>
                                                        <a type="button"
                                                            href="<?= base_url_admin('product-stock&code=' . $product['code']); ?>"
                                                            class="btn btn-warning-gradient btn-wave btn-sm"
                                                            data-bs-toggle="tooltip" title="<?= __('Kho hàng'); ?>">
                                                            <i class="fa-solid fa-cart-shopping"></i> Kho hàng
                                                        </a>
                                                    <?php else: ?>
                                                        <a type="button"
                                                            href="<?= base_url_admin('product-api-manager&id=' . $product['supplier_id']); ?>"
                                                            class="btn btn-primary-gradient btn-wave btn-sm"
                                                            data-bs-toggle="tooltip" title="<?= getRowRealtime('suppliers', $product['supplier_id'], 'domain'); ?>">
                                                            <i class="fa-solid fa-bars-progress"></i> Quản lý API
                                                        </a>
                                                    <?php endif ?>
                                                    <a type="button"
                                                        href="<?= base_url_admin('product-edit&id=' . $product['id']); ?>"
                                                        class="btn btn-sm btn-secondary shadow-secondary btn-wave"
                                                        data-bs-toggle="tooltip" title="<?= __('Chỉnh sửa'); ?>">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a type="button" onclick="remove('<?= $product['id']; ?>')"
                                                        class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                        data-bs-toggle="tooltip" title="<?= __('Xóa'); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    <small><a
                                                            href="<?= base_url_admin('product-edit&id=' . $product['id']); ?>"><?= $product['name']; ?></a>
                                                </td>
                                                <td class="text-center"><span
                                                        class="badge bg-primary"><?= $product['category_id'] != 0 ? getRowRealtime('categories', $product['category_id'], 'name') : ''; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch form-check-lg d-flex justify-content-center"
                                                        onchange="post_update_status_table_product(`<?= $product['id']; ?>`)">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="status<?= $product['id']; ?>" value="1"
                                                            <?= $product['status'] == 1 ? 'checked=""' : ''; ?>>
                                                    </div>
                                                </td>
                                                <!-- <td class="text-center">
                                            <div class="js-gallery img-fluid-100">
                                                <?php $i = 0;
                                                foreach (explode(PHP_EOL, $product['images']) as $image): ?>
                                                <?php $i++; ?>
                                                <div class="animated fadeIn"
                                                    <?= $i > 1 ? 'style="display:none;"' : ''; ?>>
                                                    <a class="glightbox" data-gallery="gallery1"
                                                        href="<?= base_url(dirImageProduct($image)); ?>">
                                                        <img style="width:40px;"
                                                            src="<?= base_url(dirImageProduct($image)); ?>" alt="">
                                                    </a>
                                                </div>
                                                <?php endforeach ?>
                                            </div>
                                        </td> -->
                                                <td>
                                                    Giá bán: <b
                                                        style="color:red;"><?= format_currency($product['price']); ?></b><br>
                                                    Giá vốn: <b style="color:blue;"><?= format_currency($product['cost']); ?></b>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success-transparent">
                                                        <?= format_cash($product['supplier_id'] == 0 ? getStock($product['code']) : $product['api_stock']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="color:green;">Live</span>:
                                                    <b><?= format_cash($product['supplier_id'] == 0 ? getStock($product['code']) : $product['api_stock']); ?></b><br>
                                                    <span style="color:red;">Die</span>:
                                                    <b><?= format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM product_die WHERE `product_code` = '" . $product['code'] . "' ")['COUNT(id)']); ?></b><br>
                                                    Đã bán: <b><?= format_cash($product['sold']); ?></b>
                                                </td>
                                                <td class="text-center"><a class="text-primary"
                                                        href="<?= base_url_admin('user-edit&id=' . $product['user_id']); ?>"><?= getRowRealtime("users", $product['user_id'], "username"); ?>
                                                        [ID <?= $product['user_id']; ?>]</a>
                                                </td>
                                                <td><small><?= $product['create_gettime']; ?></small></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="11">
                                                <p class="mb-0">Lưu ý: Ưu tiên càng cao, sản phẩm càng hiển thị trên cùng</p>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Phân trang -->
                            <?php if ($totalDatatable > $limit): ?>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-5">
                                            <p class="dataTables_info"><?= __('Showing'); ?> <?= $limit; ?> <?= __('of'); ?> <?= number_format($totalDatatable); ?> <?= __('Results'); ?></p>
                                        </div>
                                        <div class="col-sm-12 col-md-7">
                                            <?= $urlDatatable; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning m-3">
                                <i class="fa-solid fa-exclamation-circle me-2"></i><?= __('Chưa có sản phẩm nào.'); ?>
                            </div>
                        <?php endif; ?>
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
    // Toggle filter form
    function toggleFilterForm() {
        var filterBody = document.getElementById('filterFormBody');
        var filterIcon = document.getElementById('filterIcon');

        if (filterBody.style.display === 'none') {
            filterBody.style.display = 'block';
            filterIcon.className = 'fa-solid fa-chevron-up';
            localStorage.setItem('products_filter_expanded', 'true');
        } else {
            filterBody.style.display = 'none';
            filterIcon.className = 'fa-solid fa-chevron-down';
            localStorage.setItem('products_filter_expanded', 'false');
        }
    }

    // Khôi phục trạng thái filter form khi load trang
    $(document).ready(function() {
        var isFilterExpanded = localStorage.getItem('products_filter_expanded');
        <?php
        // Tự động mở nếu có filter đang active
        $has_active_filter = !empty($user_id) || !empty($username) || !empty($name) || !empty($code)
            || !empty($status) || !empty($category_id) || !empty($supplier_id)
            || !empty($create_gettime) || !empty($shortByDate) || !empty($sort_stock) || !empty($sort_price) || !empty($sort_stt) || !empty($filter_stock);
        ?>
        <?php if ($has_active_filter): ?>
            // Có filter đang active, tự động mở
            document.getElementById('filterFormBody').style.display = 'block';
            document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
        <?php else: ?>
            // Không có filter, kiểm tra localStorage
            if (isFilterExpanded === 'true') {
                document.getElementById('filterFormBody').style.display = 'block';
                document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
            }
        <?php endif; ?>
    });

    // Toggle select all
    function toggleSelectAll(checkbox) {
        var checkboxes = document.querySelectorAll('.checkbox_product');
        checkboxes.forEach(function(cb) {
            cb.checked = checkbox.checked;
        });
        updateBulkButtons();
    }

    // Update bulk action buttons
    function updateBulkButtons() {
        var count = document.querySelectorAll('.checkbox_product:checked').length;
        var bulkToolbar = document.getElementById('bulkActionsToolbar');
        var selectedCount = document.getElementById('selectedCount');

        if (count > 0) {
            bulkToolbar.classList.remove('d-none');
            selectedCount.textContent = count;
        } else {
            bulkToolbar.classList.add('d-none');
        }
    }

    $(function() {
        // Hàm cập nhật trạng thái nút và counter
        function updateSelectedRows() {
            var count = $('.checkbox_product:checked').length;
            var total = $('.checkbox_product').length;

            // Cập nhật checkbox "check all"
            $('#check_all_checkbox_product').prop('checked', count === total && count > 0);

            // Hiển thị/ẩn các nút hành động hàng loạt
            var bulkToolbar = document.getElementById('bulkActionsToolbar');
            var selectedCount = document.getElementById('selectedCount');
            if (count > 0) {
                if (bulkToolbar) {
                    bulkToolbar.classList.remove('d-none');
                    if (selectedCount) selectedCount.textContent = count;
                }
            } else {
                if (bulkToolbar) bulkToolbar.classList.add('d-none');
            }
        }

        // Checkbox "check all"
        $('#check_all_checkbox_product').on('click', function() {
            $('.checkbox_product').prop('checked', this.checked);
            updateSelectedRows();
        });

        // Checkbox từng sản phẩm
        $(document).on('click', '.checkbox_product', function() {
            updateSelectedRows();
        });
    });
</script>
<script>
    function post_update_stt_table_product(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'update_stt_table_product',
                id: id,
                stt: $('#stt' + id).val()
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
</script>
<script>
    function post_cap_nhat_nhanh(id, category_id, status, discount, short_desc, price, pricePercent, priceAction, sold_set, soldAdjust, soldAction) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'cap_nhat_san_pham_nhanh',
                id: id,
                category_id: category_id,
                status: status,
                discount: discount,
                short_desc: short_desc,
                price: price,
                pricePercent: pricePercent,
                priceAction: priceAction,
                sold_set: sold_set,
                soldAdjust: soldAdjust,
                soldAction: soldAction
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
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


    function post_update_status_table_product(id) {
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
                    showMessage(result.msg, 'success');
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

    function post_remove_product(id) {
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
                    showMessage(result.msg, 'success');
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

    function remove(id) {
        cuteAlert({
            type: "question",
            title: "Xác Nhận Xóa sản phẩm",
            message: "Bạn có chắc chắn muốn xóa sản phẩm ID " + id + " không ?",
            confirmText: "Đồng Ý",
            cancelText: "Hủy"
        }).then((e) => {
            if (e) {
                post_remove_product(id);
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        })
    }
</script>



<script>
    $(document).ready(function() {

        $("#btn_cap_nhat_nhanh_main").click(function() {
            var checkboxes = document.querySelectorAll('.checkbox_product:checked');
            if (checkboxes.length === 0) {
                showMessage('Vui lòng chọn ít nhất một sản phẩm.', 'error');
                return;
            }
            $(".checkboxeslength").html(checkboxes.length);
            $("#modal_cap_nhat_nhanh").modal('show');
        });

        $("#btn_delete_product_main").click(function() {
            var checkboxes = document.querySelectorAll('.checkbox_product:checked');
            if (checkboxes.length === 0) {
                showMessage('Vui lòng chọn ít nhất một sản phẩm.', 'error');
                return;
            }
            Swal.fire({
                title: "<?= __('Xác nhận xóa sản phẩm'); ?>",
                html: `
                <div class="text-start">
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong><?= __('Cảnh báo!'); ?></strong><br>
                        <?= __('Bạn sắp xóa'); ?> <strong>${checkboxes.length}</strong> <?= __('sản phẩm'); ?><br>
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
                confirmButtonText: "<?= __('Xóa sản phẩm'); ?>",
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
        });

    });
</script>

<script>
    function cap_nhat_nhanh_records() {
        var category_id = document.getElementById('category_id').value;
        var status = document.getElementById('status').value;
        var allow_api = document.getElementById('allow_api').value;
        var discount = document.getElementById('discount').value;
        var short_desc = document.getElementById('short_desc').value;
        var price = document.getElementById('price').value;
        var pricePercent = document.getElementById('pricePercent').value;
        var priceAction = document.getElementById('priceAction').value;
        var sold_set = document.getElementById('sold_set').value;
        var soldAdjust = document.getElementById('soldAdjust').value;
        var soldAction = document.getElementById('soldAction').value;

        // Kiểm tra xem có trường nào được điền không
        var hasChanges = false;
        if (category_id !== '' ||
            status !== '' ||
            allow_api !== '' ||
            discount !== '' ||
            short_desc !== '' ||
            price !== '' ||
            pricePercent !== '' ||
            sold_set !== '' ||
            soldAdjust !== '') {
            hasChanges = true;
        }

        // Nếu không có thay đổi nào, báo lỗi
        if (!hasChanges) {
            Swal.fire({
                title: "<?= __('Cảnh báo!'); ?>",
                html: '<p><?= __('Vui lòng nhập ít nhất một trường để cập nhật!'); ?></p>',
                icon: "warning",
                confirmButtonText: "<?= __('Đồng ý'); ?>"
            });
            return false;
        }

        $('#cap_nhat_nhanh_records').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        var checkbox = document.querySelectorAll('.checkbox_product');

        var totalSelected = 0;
        var updatedCount = 0;

        // Đếm số lượng sản phẩm được chọn
        for (var i = 0; i < checkbox.length; i++) {
            if (checkbox[i].checked === true) {
                totalSelected++;
            }
        }

        // Đóng modal và hiển thị Swal progress
        $('#modal_cap_nhat_nhanh').modal('hide');

        Swal.fire({
            title: '<?= __('Đang cập nhật sản phẩm'); ?>',
            html: `
            <div class="text-start">
                <div class="mb-3">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                             role="progressbar" 
                             id="update-progress-bar"
                             style="width: 0%"
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="update-progress-text">0%</span>
                        </div>
                    </div>
                </div>
                <p class="mb-0 text-center">
                    <strong><?= __('Đã cập nhật'); ?>:</strong> <span id="updated-count">0</span>/<span id="total-update-count">${totalSelected}</span> <?= __('sản phẩm'); ?>
                </p>
                <small class="text-muted d-block text-center mt-2"><?= __('Vui lòng không tắt trang này...'); ?></small>
            </div>
        `,
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Sử dụng hàm đệ quy để thực hiện lần lượt từng postUpdate với thời gian chờ 100ms
        function postUpdatesSequentially(index) {
            if (index < checkbox.length) {
                if (checkbox[index].checked === true) {
                    // Gọi ajax cập nhật sản phẩm
                    $.ajax({
                        url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                        method: "POST",
                        dataType: "JSON",
                        data: {
                            action: 'cap_nhat_san_pham_nhanh',
                            id: checkbox[index].value,
                            category_id: category_id,
                            status: status,
                            allow_api: allow_api,
                            discount: discount,
                            short_desc: short_desc,
                            price: price,
                            pricePercent: pricePercent,
                            priceAction: priceAction,
                            sold_set: sold_set,
                            soldAdjust: soldAdjust,
                            soldAction: soldAction
                        },
                        success: function(result) {
                            updatedCount++;

                            // Cập nhật progress bar
                            var percentage = Math.round((updatedCount / totalSelected) * 100);
                            $('#update-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
                            $('#update-progress-text').text(percentage + '%');
                            $('#updated-count').text(updatedCount);

                            // Tiếp tục cập nhật sản phẩm tiếp theo
                            setTimeout(function() {
                                postUpdatesSequentially(index + 1);
                            }, 100);
                        },
                        error: function() {
                            // Nếu có lỗi vẫn tiếp tục cập nhật các sản phẩm còn lại
                            updatedCount++;
                            var percentage = Math.round((updatedCount / totalSelected) * 100);
                            $('#update-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
                            $('#update-progress-text').text(percentage + '%');
                            $('#updated-count').text(updatedCount);

                            setTimeout(function() {
                                postUpdatesSequentially(index + 1);
                            }, 100);
                        }
                    });
                } else {
                    // Nếu checkbox không được chọn, bỏ qua và tiếp tục
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 10);
                }
            } else {
                // Hoàn thành cập nhật tất cả
                Swal.fire({
                    title: "<?= __('Thành công!'); ?>",
                    html: `
                    <p class="mb-0"><?= __('Đã cập nhật thành công'); ?> <strong>${updatedCount}</strong> <?= __('sản phẩm'); ?></p>
                `,
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 2000);
                $('#cap_nhat_nhanh_records').html('<i class="fa fa-solid fa-save"></i> <?= __('Cập nhật'); ?>').prop('disabled', false);
            }
        }
        // Bắt đầu gọi hàm đệ quy từ index 0
        postUpdatesSequentially(0);
    }

    function delete_records() {
        var checkbox = document.querySelectorAll('.checkbox_product');
        var totalSelected = 0;
        var deletedCount = 0;

        // Đếm số lượng sản phẩm được chọn
        for (var i = 0; i < checkbox.length; i++) {
            if (checkbox[i].checked === true) {
                totalSelected++;
            }
        }

        // Hiển thị Swal loading với progress
        Swal.fire({
            title: '<?= __('Đang xóa sản phẩm'); ?>',
            html: `
            <div class="text-start">
                <div class="mb-3">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" 
                             role="progressbar" 
                             id="delete-progress-bar"
                             style="width: 0%"
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="delete-progress-text">0%</span>
                        </div>
                    </div>
                </div>
                <p class="mb-0 text-center">
                    <strong><?= __('Đã xóa'); ?>:</strong> <span id="deleted-count">0</span>/<span id="total-count">${totalSelected}</span> <?= __('sản phẩm'); ?>
                </p>
                <small class="text-muted d-block text-center mt-2"><?= __('Vui lòng không tắt trang này...'); ?></small>
            </div>
        `,
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        function postUpdatesSequentially(index) {
            if (index < checkbox.length) {
                if (checkbox[index].checked === true) {
                    // Gọi ajax xóa sản phẩm
                    $.ajax({
                        url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                        method: "POST",
                        dataType: "JSON",
                        data: {
                            id: checkbox[index].value,
                            action: 'removeProduct'
                        },
                        success: function(result) {
                            deletedCount++;

                            // Cập nhật progress bar
                            var percentage = Math.round((deletedCount / totalSelected) * 100);
                            $('#delete-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
                            $('#delete-progress-text').text(percentage + '%');
                            $('#deleted-count').text(deletedCount);

                            // Tiếp tục xóa sản phẩm tiếp theo
                            setTimeout(function() {
                                postUpdatesSequentially(index + 1);
                            }, 100);
                        },
                        error: function() {
                            // Nếu có lỗi vẫn tiếp tục xóa các sản phẩm còn lại
                            deletedCount++;
                            var percentage = Math.round((deletedCount / totalSelected) * 100);
                            $('#delete-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
                            $('#delete-progress-text').text(percentage + '%');
                            $('#deleted-count').text(deletedCount);

                            setTimeout(function() {
                                postUpdatesSequentially(index + 1);
                            }, 100);
                        }
                    });
                } else {
                    // Nếu checkbox không được chọn, bỏ qua và tiếp tục
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 10);
                }
            } else {
                // Hoàn thành xóa tất cả
                Swal.fire({
                    title: "<?= __('Thành công!'); ?>",
                    html: `
                    <p class="mb-0"><?= __('Đã xóa thành công'); ?> <strong>${deletedCount}</strong> <?= __('sản phẩm'); ?></p>
                `,
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }

        // Bắt đầu xóa từ index 0
        postUpdatesSequentially(0);
    }
</script>
<div class="modal fade" id="modal_cap_nhat_nhanh" tabindex="-1" aria-labelledby="Cập nhật nhanh"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel2">Cập nhật nhanh <mark class="checkboxeslength"></mark> sản phẩm đã chọn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="alert alert-info">
                    <strong>Hướng dẫn:</strong> Chỉ nhập vào các trường bạn muốn thay đổi. Để trống nếu muốn giữ nguyên giá trị hiện tại.
                </div>

                <div class="row">
                    <!-- Cột bên trái -->
                    <div class="col-md-6">

                        <!-- Thông tin cơ bản -->
                        <div class="card mb-3">
                            <div class="card-header" style="background-color: #5a6c7d !important; color: white !important;">
                                <h6 class="mb-0 card-title">Thông tin cơ bản</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= __('Chuyên mục'); ?></label>
                                    <select class="form-select" id="category_id">
                                        <option value=""><?= __('Giữ nguyên chuyên mục hiện tại'); ?></option>
                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                            <option disabled value="<?= $option['id']; ?>" class="fw-bold"><?= $option['name']; ?></option>
                                            <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $option['id'] . "' ") as $option1): ?>
                                                <option value="<?= $option1['id']; ?>">-- <?= $option1['name']; ?></option>
                                            <?php endforeach ?>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= __('Trạng thái'); ?></label>
                                    <select class="form-select" id="status">
                                        <option value="">Giữ nguyên trạng thái hiện tại</option>
                                        <option value="ON">Hiển thị (ON)</option>
                                        <option value="OFF">Ẩn (OFF)</option>
                                    </select>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold"><?= __('Cho phép kết nối API'); ?></label>
                                    <select class="form-select" id="allow_api">
                                        <option value="">Giữ nguyên trạng thái hiện tại</option>
                                        <option value="1">ON (Cho phép)</option>
                                        <option value="0">OFF (Không cho phép)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Giá cả -->
                        <div class="card mb-3">
                            <div class="card-header" style="background-color: #5a6c7d !important; color: white !important;">
                                <h6 class="mb-0 card-title">Quản lý giá</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= __('Giá bán lẻ'); ?></label>
                                    <input type="number" class="form-control" id="price" min="0"
                                        placeholder="Nhập giá bán lẻ mới">
                                    <div class="form-text">Nhập giá cố định mới hoặc để trống nếu muốn giữ nguyên</div>
                                </div>

                                <div class="mb-3 pt-2 border-top">
                                    <label class="form-label fw-bold"><?= __('Điều chỉnh giá theo % (dựa trên giá vốn)'); ?></label>
                                    <div class="input-group">
                                        <select class="form-select" id="priceAction" style="max-width: 120px;">
                                            <option value="increase"><?= __('Tăng'); ?></option>
                                            <option value="decrease"><?= __('Giảm'); ?></option>
                                        </select>
                                        <input type="number" class="form-control" id="pricePercent" min="0" max="100" placeholder="Nhập %">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Ví dụ: Giá vốn 100.000đ, tăng 20% = Giá bán 120.000đ</div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold"><?= __('Giảm giá (%)'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="discount" min="0" max="100"
                                            placeholder="Nhập % giảm giá">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Cột bên phải -->
                    <div class="col-md-6">

                        <!-- Số lượng đã bán -->
                        <div class="card mb-3">
                            <div class="card-header" style="background-color: #5a6c7d !important; color: white !important;">
                                <h6 class="mb-0 card-title">Số lượng đã bán</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= __('Đặt số lượng cụ thể'); ?></label>
                                    <input type="number" class="form-control" id="sold_set" min="0"
                                        placeholder="Nhập số lượng đã bán mới">
                                    <div class="form-text">Nhập con số cụ thể để đặt lại số lượng đã bán</div>
                                </div>

                                <div class="mb-0 pt-2 border-top">
                                    <label class="form-label fw-bold"><?= __('Điều chỉnh số lượng'); ?></label>
                                    <div class="input-group">
                                        <select class="form-select" id="soldAction" style="max-width: 130px;">
                                            <option value="add"><?= __('Cộng thêm'); ?></option>
                                            <option value="subtract"><?= __('Trừ đi'); ?></option>
                                        </select>
                                        <input type="number" class="form-control" id="soldAdjust" min="0" placeholder="Nhập số lượng">
                                    </div>
                                    <div class="form-text">Ví dụ: Đã bán 50, cộng thêm 25 = Tổng đã bán 75</div>
                                </div>
                            </div>
                        </div>

                        <!-- Mô tả -->
                        <div class="card mb-3">
                            <div class="card-header" style="background-color: #5a6c7d !important; color: white !important;">
                                <h6 class="mb-0 card-title">Mô tả</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-0">
                                    <label class="form-label fw-bold"><?= __('Mô tả ngắn'); ?></label>
                                    <textarea class="form-control" id="short_desc" rows="5"
                                        placeholder="Nhập mô tả ngắn mới cho sản phẩm..."></textarea>
                                    <div class="form-text">Mô tả ngắn sẽ hiển thị trên danh sách sản phẩm</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    <strong>Lưu ý:</strong> Khi bạn nhấn nút Cập nhật, tất cả các sản phẩm đã chọn sẽ được cập nhật theo thông tin trên!
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" onclick="cap_nhat_nhanh_records()" id="cap_nhat_nhanh_records"
                    class="btn btn-primary"><i class="fa fa-solid fa-save"></i> <?= __('Cập nhật'); ?></button>
            </div>
        </div>
    </div>
</div>



<!-- Modal Xuất sản phẩm -->
<div class="modal fade" id="exportProductsModal" tabindex="-1" aria-hidden="true">
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
                                <div class="form-check custom-radio-box-product mb-3 p-3 border rounded border-primary bg-primary-transparent">
                                    <input class="form-check-input mt-2" type="radio" name="exportProductFileType" id="productFileTypeTXT" value="txt" checked style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="productFileTypeTXT">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-lines fa-2x text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">TXT File</div>
                                                <div class="fs-12 text-muted"><?= __('Văn bản thuần (Tab-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check custom-radio-box-product mb-0 p-3 border rounded">
                                    <input class="form-check-input mt-2" type="radio" name="exportProductFileType" id="productFileTypeCSV" value="csv" style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="productFileTypeCSV">
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
                                <ul class="list-group list-group-flush" id="exportProductColumnsList">
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="name">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_name" value="name" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_name"><?= __('Tên sản phẩm'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="code">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_code" value="code" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_code"><?= __('Mã kho hàng'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="price">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_price" value="price" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_price"><?= __('Giá bán'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="cost">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_cost" value="cost" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_cost"><?= __('Giá vốn'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="category">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_category" value="category" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_category"><?= __('Chuyên mục'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="stock_live">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_stock_live" value="stock_live" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_stock_live"><?= __('Tồn kho Live'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="sold">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_sold" value="sold" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_sold"><?= __('Đã bán'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="status">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_status" value="status" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_status"><?= __('Trạng thái'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="seller">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_seller" value="seller" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_seller"><?= __('Seller'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="create_gettime">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_create_gettime" value="create_gettime" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_create_gettime"><?= __('Ngày tạo'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-0 p-2 d-flex align-items-center" data-column="stock_data">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-product-col-checkbox" type="checkbox" role="switch" id="pcol_stock_data" value="stock_data" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="pcol_stock_data"><?= __('Dữ liệu kho'); ?></label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="toggleAllExportProductColumns(true)">
                                    <i class="fa-solid fa-check-double me-1"></i><?= __('Chọn xuất tất cả'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="toggleAllExportProductColumns(false)">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Bỏ chọn'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-secondary px-4 btn-wave" data-bs-dismiss="modal"><?= __('Hủy bỏ'); ?></button>
                <button type="button" class="btn btn-success px-4 btn-wave fw-bold" id="confirmExportProductsBtn" onclick="confirmExportProducts()">
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

    .custom-radio-box-product {
        transition: all 0.2s;
    }

    #exportProductColumnsList .cursor-move {
        cursor: grab;
    }

    #exportProductColumnsList .cursor-move:active {
        cursor: grabbing;
    }

    #exportProductColumnsList .list-group-item {
        user-select: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    #exportProductColumnsList .list-group-item:hover {
        border-color: rgba(var(--primary-rgb), 0.5) !important;
        background-color: rgba(var(--primary-rgb), 0.03) !important;
    }

    #exportProductColumnsList .list-group-item.sortable-ghost {
        opacity: 0.5;
        background-color: rgba(var(--primary-rgb), 0.1) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<script>
    // JS trang trí khi select file type radio cho products
    $(document).ready(function() {
        $('input[name="exportProductFileType"]').on('change', function() {
            $('.custom-radio-box-product').removeClass('border-primary bg-primary-transparent');
            $(this).closest('.custom-radio-box-product').addClass('border-primary bg-primary-transparent');
        });
    });
</script>

<script>
    // Khởi tạo Sortable cho danh sách cột export sản phẩm
    if (typeof Sortable !== 'undefined' && document.getElementById('exportProductColumnsList')) {
        new Sortable(document.getElementById('exportProductColumnsList'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.fa-grip-vertical'
        });
    }

    // Hiển thị modal export sản phẩm
    function showExportProductsModal() {
        var selectedIds = getSelectedProductIds();
        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một sản phẩm"); ?>', 'error');
            return;
        }
        var modalEl = document.getElementById('exportProductsModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).addClass('show').css('display', 'block');
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
        }
    }

    // Chọn/bỏ chọn tất cả cột
    function toggleAllExportProductColumns(checked) {
        $('.export-product-col-checkbox').prop('checked', checked);
    }

    // Lấy danh sách ID sản phẩm đã chọn
    function getSelectedProductIds() {
        var selectedIds = [];
        $('.checkbox_product:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    // Xác nhận export sản phẩm
    function confirmExportProducts() {
        var selectedIds = getSelectedProductIds();
        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một sản phẩm"); ?>', 'error');
            return;
        }

        // Lấy loại file
        var fileType = $('input[name="exportProductFileType"]:checked').val() || 'txt';

        // Lấy danh sách cột được chọn theo thứ tự
        var columns = [];
        $('#exportProductColumnsList li').each(function() {
            var $checkbox = $(this).find('.export-product-col-checkbox');
            if ($checkbox.prop('checked')) {
                columns.push($checkbox.val());
            }
        });

        if (columns.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một cột để xuất"); ?>', 'error');
            return;
        }

        // Gọi AJAX để export
        $('#confirmExportProductsBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang tải..."); ?>');

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'exportProducts',
                token: '<?= $getUser['token']; ?>',
                ids: selectedIds,
                file_type: fileType,
                columns: columns
            },
            success: function(result) {
                $('#confirmExportProductsBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');

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
                    bootstrap.Modal.getInstance(document.getElementById('exportProductsModal')).hide();
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#confirmExportProductsBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }
</script>

<script>
    var lightboxVideo = GLightbox({
        selector: '.glightbox'
    });
</script>