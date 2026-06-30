<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Automations',
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
if (checkPermission($getUser['admin'], 'view_automations') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddTask'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used as this is a demo site.') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_automations') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if (empty($_POST['type'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng chọn loại công việc")){window.history.back().location.reload();}</script>');
    }
    $type = check_string($_POST['type']);

    if (empty($_POST['product_id'])) {
        $product_id = NULL;
    } else {
        // product_id is sent as a JSON string from the form to avoid PHP max_input_vars limit
        $decoded = json_decode($_POST['product_id'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $product_id = json_encode($decoded);
        } else {
            $product_id = NULL;
        }
    }

    if (empty($_POST['schedule']) && $_POST['type'] !== 'cleanup_orphan_images') {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập thời gian")){window.history.back().location.reload();}</script>');
    }
    $schedule = !empty($_POST['schedule']) ? check_string($_POST['schedule']) : '0';

    if ($type == 'change_warehouse') {
        if (empty($_POST['other'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập mã kho hàng cần chuyển đến")){window.history.back().location.reload();}</script>');
        }
    }

    $isInsert = $CMSNT->insert("automations", [
        'name'              => !empty($_POST['name']) ? check_string($_POST['name']) : NULL,
        'type'              => $type,
        'product_id'        => $product_id,
        'schedule'          => $schedule,
        'other'             => !empty($_POST['other']) ? check_string($_POST['other']) : NULL,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Task Automation'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', 'Add Task Automation', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công!")){location.href = "' . base_url_admin('automations') . '";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại!")){window.history.back().location.reload();}</script>');
    }
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
$create_gettime = '';
$shortByDate  = '';


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
$listDatatable = $CMSNT->get_list(" SELECT * FROM `automations` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `automations` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("automations&limit=$limit&shortByDate=$shortByDate&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="bx bxs-calendar"></i> Automations</h1>
        </div>
        <?php if (time() - $CMSNT->site('check_time_cron_task') >= 300): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                Vui lòng thực hiện <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> liên kết: <a class="text-primary" href="<?= base_url('cron/task.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank"><?= base_url('cron/task.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 - 5 phút 1 lần để sử dụng được chức năng này.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH CÔNG VIỆC TỰ ĐỘNG
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> THÊM TASK</button>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="automations">
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?= $create_gettime; ?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?= __('Search'); ?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?= base_url_admin('automations'); ?>"><i class="fa fa-trash"></i>
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
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
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
                                                    id="check_all_checkbox" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center">Tên công việc</th>
                                        <th class="text-center">Loại công việc</th>
                                        <th class="text-center">Chi tiết công việc</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="form-check form-check-md d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input checkbox"
                                                        data-id="<?= $row['id']; ?>" name="checkbox"
                                                        value="<?= $row['id']; ?>" />
                                                </div>
                                            </td>
                                            <td class="text-center"><?= $row['name']; ?></td>
                                            <td class="text-center">
                                                <?php if ($row['type'] == 'delete_order') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-danger">Xóa tài khoản đã bán</span>';
                                                } elseif ($row['type'] == 'delete_order_revenue') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-danger">Xóa đơn hàng & tài khoản đã bán</span>';
                                                } elseif ($row['type'] == 'change_warehouse') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-primary">Thay đổi kho hàng</span>';
                                                } elseif ($row['type'] == 'delete_order_not_uid') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-primary">Xóa tài khoản đã bán, không xóa UID</span>';
                                                } elseif ($row['type'] == 'delete_history_topup') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-primary">Xóa lịch sử nạp tiền</span>';
                                                } elseif ($row['type'] == 'delete_history_dongtien') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-info">Xóa biến động số dư</span>';
                                                } elseif ($row['type'] == 'delete_user_no_topup') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-warning">Xóa User không nạp tiền</span>';
                                                } elseif ($row['type'] == 'cleanup_orphan_images') {
                                                    echo '<span style="font-size: 13px;" class="badge bg-info">Dọn dẹp ảnh rác</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($row['type'] == 'delete_order'): ?>
                                                    Hệ thống sẽ thực hiện xóa tài khoản đã bán sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b>, chỉ áp dụng các
                                                    sản phẩm bạn chọn.
                                                <?php elseif ($row['type'] == 'delete_order_not_uid'): ?>
                                                    Hệ thống sẽ thực hiện xóa tài khoản đã bán, không xóa UID sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b>, chỉ áp dụng các
                                                    sản phẩm bạn chọn.
                                                <?php elseif ($row['type'] == 'change_warehouse'): ?>
                                                    Hệ thống sẽ thực hiện chuyển những tài khoản trong sản phẩm bạn chọn vào kho
                                                    hàng <b style="color:blue;"><?= $row['other']; ?></b> nếu quá <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b> chưa được bán.
                                                <?php elseif ($row['type'] == 'delete_order_revenue'): ?>
                                                    Hệ thống sẽ thực hiện xóa đơn hàng & tài khoản đã bán sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b>, chỉ áp dụng các
                                                    sản phẩm bạn chọn.
                                                <?php elseif ($row['type'] == 'delete_history_topup'): ?>
                                                    Hệ thống sẽ thực hiện xóa lịch sử nạp tiền sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b>.
                                                <?php elseif ($row['type'] == 'delete_history_dongtien'): ?>
                                                    Hệ thống sẽ thực hiện xóa biến động số dư sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b>.
                                                <?php elseif ($row['type'] == 'delete_user_no_topup'): ?>
                                                    Hệ thống sẽ thực hiện xóa User chưa nạp tiền (total_money = 0 và money = 0) sau <b
                                                        style="color:red;"><?= timeAgo2($row['schedule']); ?></b> kể từ ngày đăng ký.
                                                <?php elseif ($row['type'] == 'cleanup_orphan_images'): ?>
                                                    Hệ thống sẽ tự động quét và xóa ảnh không còn được sử dụng trong thư mục storage.
                                                <?php endif ?>

                                            </td>
                                            <td class="text-center">
                                                <a type="button"
                                                    href="<?= base_url_admin('automation-edit&id=' . $row['id']); ?>"
                                                    class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                    title="<?= __('Edit'); ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a type="button" onclick="remove('<?= $row['id']; ?>')"
                                                    class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                    title="<?= __('Delete'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <td colspan="8">
                                        <div class="btn-list">
                                            <button type="button" id="btn_delete_row"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> XÓA TASK ĐÃ CHỌN</button>
                                        </div>
                                    </td>
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
                        <p>Hướng dẫn sử dụng chức năng xóa đơn hàng đã bán: <a class="text-primary" target="_blank"
                                href="https://help.cmsnt.co/huong-dan/cau-hinh-tu-dong-xoa-don-hang-da-ban-trong-shopclone7/">https://help.cmsnt.co/huong-dan/cau-hinh-tu-dong-xoa-don-hang-da-ban-trong-shopclone7/</a>
                        </p>
                        <p>Hướng dẫn sử dụng chức đổi kho hàng: <a class="text-primary" target="_blank"
                                href="https://help.cmsnt.co/huong-dan/cau-hinh-chuc-nang-tu-doi-kho-hang-tai-khoan-dang-ban/">https://help.cmsnt.co/huong-dan/cau-hinh-chuc-nang-tu-doi-kho-hang-tai-khoan-dang-ban/</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> THÊM CÔNG VIỆC CẦN TỰ
                    ĐỘNG
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body" onchange="loadform()">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Tên công việc</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <textarea class="form-control" name="name"
                                    placeholder="Nhập tên mô tả task nếu có"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Loại công việc (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <select class="form-control" name="type" id="type" required>
                                    <option value=""> -- Chọn loại công việc --</option>
                                    <option value="delete_order">Xóa tài khoản đã bán</option>
                                    <option value="delete_order_not_uid">Xóa tài khoản đã bán, không xóa UID</option>
                                    <option value="delete_order_revenue">Xóa đơn hàng & tài khoản đã bán</option>
                                    <option value="change_warehouse">Thay đổi kho hàng</option>
                                    <option value="delete_history_topup">Xóa lịch sử nạp tiền</option>
                                    <option value="delete_history_dongtien">Xóa biến động số dư</option>
                                    <option value="delete_user_no_topup">Xóa User không nạp tiền</option>
                                    <option value="cleanup_orphan_images">Dọn dẹp ảnh rác</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4" id="product_id_input" style="display: none;">
                        <label class="col-sm-4 col-form-label">Áp dụng cho sản phẩm:</label>
                        <div class="col-sm-8">
                            <input type="text" id="productSearch" class="form-control form-control-sm mb-2" placeholder="🔍 Tìm kiếm sản phẩm hoặc chuyên mục..." oninput="searchProducts(this.value)" autocomplete="off">
                            <div id="productCheckboxList" style="border: 1px solid #e0e0e0; border-radius: 6px; max-height: 300px; overflow-y: auto; background: #fff;">
                                <div class="product-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0;" onclick="toggleAllProducts(this)">
                                    <span style="cursor: pointer; margin: 0; font-weight: 500; color: #555;">
                                        <input type="checkbox" id="checkAllProducts" style="margin-right: 8px;" checked> ~ Tất cả sản phẩm ~
                                    </span>
                                </div>
                                <?php foreach ($CMSNT->get_list(" SELECT * FROM `categories` ") as $category): ?>
                                    <?php $catProducts = $CMSNT->get_list(" SELECT * FROM `products` WHERE `category_id` = '" . $category['id'] . "' "); ?>
                                    <?php if (!empty($catProducts)): ?>
                                        <div class="category-group" data-cat-id="<?= $category['id']; ?>">
                                            <div class="category-header" data-cat-name="<?= htmlspecialchars($category['name']); ?>" style="padding: 6px 12px; background: #f8f9fa; font-weight: 600; font-size: 12px; color: #555; letter-spacing: 0.3px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" onclick="handleCategoryClick(event, '<?= $category['id']; ?>', this)">
                                                <span style="cursor: pointer; margin: 0; display: flex; align-items: center;">
                                                    <input type="checkbox" class="category-checkbox" data-cat-id="<?= $category['id']; ?>" style="margin-right: 6px;" onclick="event.stopPropagation(); toggleCategoryCheck(event, '<?= $category['id']; ?>', this.closest('.category-header'))"> <?= $category['name']; ?>
                                                    <span style="margin-left: 6px; font-weight: normal; color: #999; font-size: 11px;">(<?= count($catProducts); ?>)</span>
                                                </span>
                                                <span class="cat-chevron" style="font-size: 14px; color: #999; transition: transform 0.2s;">▸</span>
                                            </div>
                                            <div class="category-products" data-cat-id="<?= $category['id']; ?>" style="display: none;">
                                                <?php foreach ($catProducts as $product): ?>
                                                    <div class="product-item" data-product-name="<?= htmlspecialchars($product['name']); ?>" data-cat-id="<?= $category['id']; ?>" style="padding: 7px 12px 7px 28px; cursor: pointer; border-bottom: 1px solid #f8f8f8; transition: background 0.15s;" onmouseover="this.style.background='#f5f3ff'" onmouseout="if(!this.querySelector('input').checked) this.style.background='#fff'; else this.style.background='#ede9fe';" onclick="toggleProduct(event, this)">
                                                        <span style="cursor: pointer; margin: 0; display: block;">
                                                            <input type="checkbox" class="product-checkbox" data-category="<?= $category['id']; ?>" value="<?= $product['id']; ?>" style="margin-right: 8px;" onchange="updateProductSelection()"> <?= $product['name']; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach ?>
                                            </div>
                                        </div>
                                    <?php endif ?>
                                <?php endforeach ?>
                            </div>
                            <!-- Single hidden input for form submission (avoids PHP max_input_vars limit) -->
                            <input type="hidden" name="product_id" id="productHiddenInput" value="[]">
                            <small class="text-muted mt-1 d-block">Click vào chuyên mục để mở rộng danh sách. Tích checkbox chuyên mục để chọn tất cả sản phẩm.</small>
                        </div>
                        <script>
                            function toggleAllProducts(el) {
                                var cb = document.getElementById('checkAllProducts');
                                if (event.target !== cb) cb.checked = !cb.checked;
                                if (cb.checked) {
                                    document.querySelectorAll('.product-checkbox').forEach(function(c) {
                                        c.checked = false;
                                        c.closest('.product-item').style.background = '#fff';
                                    });
                                    document.querySelectorAll('.category-checkbox').forEach(function(c) {
                                        c.checked = false;
                                    });
                                }
                                updateProductSelection();
                            }

                            function toggleCategoryDropdown(catId, headerEl) {
                                var container = document.querySelector('.category-products[data-cat-id="' + catId + '"]');
                                var chevron = headerEl.querySelector('.cat-chevron');
                                if (container.style.display === 'none') {
                                    container.style.display = '';
                                    chevron.textContent = '▾';
                                } else {
                                    container.style.display = 'none';
                                    chevron.textContent = '▸';
                                }
                            }

                            function handleCategoryClick(e, catId, headerEl) {
                                // If clicking the checkbox, let toggleCategoryCheck handle it
                                if (e.target.classList.contains('category-checkbox')) return;
                                // Otherwise toggle the dropdown
                                toggleCategoryDropdown(catId, headerEl);
                            }

                            function toggleCategoryCheck(e, catId, headerEl) {
                                var catCb = headerEl.querySelector('.category-checkbox');
                                var isChecked = catCb.checked;
                                // Auto-expand when checking
                                var container = document.querySelector('.category-products[data-cat-id="' + catId + '"]');
                                var chevron = headerEl.querySelector('.cat-chevron');
                                if (isChecked && container.style.display === 'none') {
                                    container.style.display = '';
                                    chevron.textContent = '▾';
                                }
                                document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]').forEach(function(c) {
                                    c.checked = isChecked;
                                    c.closest('.product-item').style.background = isChecked ? '#ede9fe' : '#fff';
                                });
                                document.getElementById('checkAllProducts').checked = false;
                                var anyChecked = document.querySelectorAll('.product-checkbox:checked').length > 0;
                                if (!anyChecked) document.getElementById('checkAllProducts').checked = true;
                                updateProductSelection();
                                e.stopPropagation();
                            }

                            function toggleProduct(e, el) {
                                var cb = el.querySelector('.product-checkbox');
                                if (e.target !== cb) cb.checked = !cb.checked;
                                document.getElementById('checkAllProducts').checked = false;
                                el.style.background = cb.checked ? '#ede9fe' : '#fff';
                                var catId = cb.getAttribute('data-category');
                                var allInCat = document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]');
                                var checkedInCat = document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]:checked');
                                var catCb = document.querySelector('.category-checkbox[data-cat-id="' + catId + '"]');
                                if (catCb) catCb.checked = (allInCat.length === checkedInCat.length);
                                var anyChecked = document.querySelectorAll('.product-checkbox:checked').length > 0;
                                if (!anyChecked) document.getElementById('checkAllProducts').checked = true;
                                updateProductSelection();
                            }

                            function updateProductSelection() {
                                var ids = [];
                                document.querySelectorAll('.product-checkbox:checked').forEach(function(c) {
                                    ids.push(c.value);
                                });
                                document.getElementById('productHiddenInput').value = JSON.stringify(ids);
                            }

                            function searchProducts(query) {
                                var q = query.toLowerCase().trim();
                                var catGroups = document.querySelectorAll('#productCheckboxList .category-group');
                                if (!q) {
                                    // Reset: show all groups, collapse all dropdowns
                                    catGroups.forEach(function(g) {
                                        g.style.display = '';
                                        var header = g.querySelector('.category-header');
                                        var container = g.querySelector('.category-products');
                                        container.style.display = 'none';
                                        header.querySelector('.cat-chevron').textContent = '▸';
                                        container.querySelectorAll('.product-item').forEach(function(p) {
                                            p.style.display = '';
                                        });
                                    });
                                    return;
                                }
                                catGroups.forEach(function(g) {
                                    var header = g.querySelector('.category-header');
                                    var container = g.querySelector('.category-products');
                                    var catName = (header.getAttribute('data-cat-name') || '').toLowerCase();
                                    var products = container.querySelectorAll('.product-item');
                                    var hasMatch = false;
                                    if (catName.indexOf(q) !== -1) {
                                        // Category name matches - show all products, expand
                                        g.style.display = '';
                                        container.style.display = '';
                                        header.querySelector('.cat-chevron').textContent = '▾';
                                        products.forEach(function(p) {
                                            p.style.display = '';
                                        });
                                    } else {
                                        // Check individual products
                                        products.forEach(function(p) {
                                            var name = (p.getAttribute('data-product-name') || '').toLowerCase();
                                            if (name.indexOf(q) !== -1) {
                                                p.style.display = '';
                                                hasMatch = true;
                                            } else {
                                                p.style.display = 'none';
                                            }
                                        });
                                        if (hasMatch) {
                                            g.style.display = '';
                                            container.style.display = '';
                                            header.querySelector('.cat-chevron').textContent = '▾';
                                        } else {
                                            g.style.display = 'none';
                                        }
                                    }
                                });
                            }
                        </script>
                    </div>
                    <div class="row mb-4" id="schedule_row">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Thời gian (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <input class="form-control" name="schedule" id="schedule" onkeyup="loadform()"
                                    value="604800" placeholder="Nhập giây, ví dụ 1 ngày = 86400" required>
                                <span class="input-group-text">
                                    Giây
                                </span>
                            </div>
                            <div class="btn-group" role="group" aria-label="Time buttons">
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(1)">1 ngày</button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(3)">3 ngày</button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(7)" active>7 ngày</button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(30)">30 ngày</button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(90)">3 tháng</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        function setTime(days) {
                            const seconds = days * 86400; // 1 ngày = 86400 giây
                            document.getElementById('schedule').value = seconds;
                            loadform(); // Gọi hàm loadform nếu cần cập nhật gì thêm
                        }
                    </script>
                    <div class="row mb-4" id="warehouse_input" style="display: none;">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Kho hàng nhận (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <input class="form-control" name="other" id="other" onkeyup="loadform()"
                                    placeholder="Mã kho hàng">
                            </div>
                        </div>
                    </div>

                    <p id="mota">Vui lòng chọn loại công việc</p>

                    <script>
                        function formatTime(seconds) {
                            var days = Math.floor(seconds / (60 * 60 * 24));
                            var hours = Math.floor((seconds % (60 * 60 * 24)) / (60 * 60));
                            var minutes = Math.floor((seconds % (60 * 60)) / 60);
                            var remainingSeconds = seconds % 60;

                            var result = '';
                            if (days > 0) {
                                result += days + ' ngày ';
                            }
                            if (hours > 0) {
                                result += hours + ' giờ ';
                            }
                            if (minutes > 0) {
                                result += minutes + ' phút ';
                            }
                            if (remainingSeconds > 0) {
                                result += remainingSeconds + ' giây';
                            }

                            return result.trim();
                        }

                        function loadform() {
                            var type = $('#type').val();
                            var schedule = $('#schedule').val();
                            var formattedTime = formatTime(schedule);

                            $('#warehouse_input').hide();
                            $('#product_id_input').hide();

                            if (type == 'change_warehouse') {
                                $('#warehouse_input').show();
                                $('#product_id_input').show();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện chuyển những tài khoản trong sản phẩm bạn chọn vào kho hàng <b style="color:blue;">' +
                                    $('#other').val() + '</b> nếu quá <b style="color:red;">' + formattedTime +
                                    '</b> chưa được bán.');
                            } else if (type == 'delete_order') {
                                $('#product_id_input').show();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa tài khoản đã bán sau <b style="color:red;">' +
                                    formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                            } else if (type == 'delete_order_not_uid') {
                                $('#product_id_input').show();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa tài khoản đã bán, không xóa UID sau <b style="color:red;">' +
                                    formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                            } else if (type == 'delete_order_revenue') {
                                $('#product_id_input').show();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa đơn hàng và tài khoản đã bán sau <b style="color:red;">' +
                                    formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                            } else if (type == 'delete_history_topup') {
                                $('#product_id_input').hide();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa lịch sử nạp tiền sau <b style="color:red;">' +
                                    formattedTime + '</b>.');
                            } else if (type == 'delete_history_dongtien') {
                                $('#product_id_input').hide();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa biến động số dư sau <b style="color:red;">' +
                                    formattedTime + '</b>.');
                            } else if (type == 'delete_user_no_topup') {
                                $('#product_id_input').hide();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa User chưa nạp tiền (total_money = 0 và money = 0) sau <b style="color:red;">' +
                                    formattedTime + '</b> kể từ ngày đăng ký.');
                            } else if (type == 'cleanup_orphan_images') {
                                $('#product_id_input').hide();
                                $('#mota').html(
                                    'Nếu bạn tạo Task này => Hệ thống sẽ tự động quét và xóa ảnh không còn được sử dụng trong thư mục storage.');
                            } else {
                                $('#mota').html('Vui lòng chọn loại công việc');
                            }

                            // Ẩn/hiện ô thời gian
                            if (type == 'cleanup_orphan_images') {
                                $('#schedule_row').hide();
                                $('#schedule').removeAttr('required');
                            } else {
                                $('#schedule_row').show();
                                $('#schedule').attr('required', true);
                            }
                        }
                    </script>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddTask" class="btn btn-primary shadow-primary btn-wave"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?= __('Submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php
require_once(__DIR__ . '/footer.php');
?>


<script>
    $(function() {
        $('#check_all_checkbox').on('click', function() {
            $('.checkbox').prop('checked', this.checked);
        });
        $('.checkbox').on('click', function() {
            $('#check_all_checkbox').prop('checked', $('.checkbox:checked')
                .length === $('.checkbox').length);
        });
    });
</script>


<script>
    $("#btn_delete_row").click(function() {
        var checkboxes = document.querySelectorAll('input[name="checkbox"]:checked');
        if (checkboxes.length === 0) {
            return showMessage('Lỗi: Vui lòng chọn ít nhất một dữ liệu.', 'error');
        }
        Swal.fire({
            title: "Bạn có chắc không?",
            text: "Hệ thống sẽ xóa " + checkboxes.length +
                " Task bạn đã chọn khi nhấn Đồng Ý",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Đóng"
        }).then((result) => {
            if (result.isConfirmed) {
                delete_records();
            }
        });
    });

    function delete_records() {
        var checkbox = document.getElementsByName('checkbox');

        function postUpdatesSequentially(index) {
            if (index < checkbox.length) {
                if (checkbox[index].checked === true) {
                    postRemove(checkbox[index].value);
                }
                setTimeout(function() {
                    postUpdatesSequentially(index + 1);
                }, 100);
            } else {
                Swal.fire({
                    title: "Thành công!",
                    text: "Xóa dữ liệu thành công",
                    icon: "success"
                });
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        }
        postUpdatesSequentially(0);
    }
</script>

<script>
    function postRemove(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'removeTaskAutomation',
                id: id
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                }
            }
        });
    }

    function remove(id) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa Task",
            message: "Bạn có chắc chắn muốn xóa Task này không ?",
            confirmText: "Đồng ý",
            cancelText: "Không"
        }).then((e) => {
            if (e) {
                postRemove(id);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        })
    }
</script>