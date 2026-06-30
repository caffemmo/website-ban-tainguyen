<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Quản lý API nhà cung cấp',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
';
$body['footer'] = '
<!-- Datatables Cdn -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    if (!$supplier = $CMSNT->get_row("SELECT * FROM `suppliers` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('product-api'));
    }
} else {
    redirect(base_url_admin('product-api'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
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
$where = " `supplier_id` = '$id' ";
$user_id = '';
$name = '';
$create_gettime = '';
$username = '';
$shortByDate  = '';
$category_id = '';
$status = '';
$sort_stock = '';
$sort_price = '';


if (!empty($_GET['status'])) {
    $status = check_string($_GET['status']);
    if ($status == 2) {
        $where .= ' AND `status` = 0 ';
    } else if ($status == 1) {
        $where .= ' AND `status` = 1 ';
    }
}
if (!empty($_GET['category_id'])) {
    $category_id = check_string($_GET['category_id']);
    $where .= ' AND `category_id` = "' . $category_id . '" ';
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
if (!empty($_GET['sort_stock'])) {
    $sort_stock = check_string($_GET['sort_stock']);
}
if (!empty($_GET['sort_price'])) {
    $sort_price = check_string($_GET['sort_price']);
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

// Kết hợp các điều kiện sắp xếp
if (!empty($orderParts)) {
    $orderBy = " ORDER BY " . implode(", ", $orderParts) . ", `id` DESC ";
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `products` WHERE $where $orderBy LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `products` WHERE $where ");

// Tạo URL pagination với các tham số filter
$pagination_url = base_url_admin('product-api-manager');
$pagination_url .= '&id=' . $id;
$pagination_url .= '&limit=' . $limit;
if (!empty($user_id)) $pagination_url .= '&user_id=' . urlencode($user_id);
if (!empty($username)) $pagination_url .= '&username=' . urlencode($username);
if (!empty($name)) $pagination_url .= '&name=' . urlencode($name);
if (!empty($status)) $pagination_url .= '&status=' . urlencode($status);
if (!empty($category_id)) $pagination_url .= '&category_id=' . urlencode($category_id);
if (!empty($create_gettime)) $pagination_url .= '&create_gettime=' . urlencode($create_gettime);
if (!empty($shortByDate)) $pagination_url .= '&shortByDate=' . urlencode($shortByDate);
if (!empty($sort_stock)) $pagination_url .= '&sort_stock=' . urlencode($sort_stock);
if (!empty($sort_price)) $pagination_url .= '&sort_price=' . urlencode($sort_price);
$pagination_url .= '&';

$urlDatatable = pagination($pagination_url, $from, $totalDatatable, $limit);


$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button"
                    class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('product-api'); ?>"><i class="fa-solid fa-arrow-left"></i></a> Quản lý API
                <?= $supplier['domain']; ?>
            </h1>
        </div>
        <?php
        // Lặp qua danh sách nhà cung cấp
        foreach ($cron_suppliers as $type => $key) {
            if ($supplier['type'] == $type && time() - $CMSNT->site("time_cron_suppliers_$key") >= 120) {
        ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                    liên kết:
                    <a class="text-primary" href="<?= base_url('cron/suppliers/' . $key . '.php?key=' . $CMSNT->site('key_cron_job')); ?>" target="_blank">
                        <?= base_url('cron/suppliers/' . $key . '.php?key=' . $CMSNT->site('key_cron_job')); ?>
                    </a> 1 phút 1 lần để hệ thống tự động cập nhật dữ liệu từ API.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
        <?php
            }
        }
        ?>
        <ul class="nav nav-tabs tab-style-1" role="tablist">
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tab1" aria-current="page" href="#tab1"><i
                        class="fa-solid fa-chart-pie"></i> Thống kê</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_category" href="#tab_category"><i
                        class="fa-solid fa-list-ul"></i> Chuyên mục</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" href="#tab2"><i
                        class="fa-solid fa-list"></i> Sản phẩm</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url_admin('product-orders&supplier_id=' . $supplier['id']); ?>"><i
                        class="fa-solid fa-cart-shopping"></i> Đơn hàng</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url_admin('product-api-edit&id=' . $id); ?>"><i
                        class="fa-solid fa-gear"></i> Chỉnh sửa kết nối</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane show text-muted" id="tab1" role="tabpanel">
                <?php
                $doanh_thu = $CMSNT->get_row(" SELECT SUM(pay) FROM product_order WHERE `refund` = 0 AND supplier_id = '$id' ")['SUM(pay)'];
                $tien_von = $CMSNT->get_row(" SELECT SUM(cost) FROM product_order WHERE `refund` = 0 AND supplier_id = '$id' ")['SUM(cost)'];
                $loi_nhuan = $doanh_thu - $tien_von;
                ?>
                <div class="row">
                    <div class="col">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-top">
                                    <div class="me-3">
                                        <span class="avatar avatar-md p-2 bg-primary">
                                            <i class="fa-solid fa-cart-shopping fs-16"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex mb-1 align-items-top justify-content-between">
                                            <h5 class="fw-semibold mb-0 lh-1">
                                                <?= format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM product_order WHERE refund = 0 AND supplier_id = '$id' ")['COUNT(id)']); ?>
                                            </h5>
                                        </div>
                                        <p class="mb-0 fs-10 op-7 text-muted fw-semibold">ĐƠN HÀNG</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-top">
                                    <div class="me-3">
                                        <span class="avatar avatar-md p-2 bg-info">
                                            <i class="fa-solid fa-money-bill-1 fs-16"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex mb-1 align-items-top justify-content-between">
                                            <h5 class="fw-semibold mb-0 lh-1">
                                                <?= format_currency($doanh_thu); ?>
                                            </h5>
                                        </div>
                                        <p class="mb-0 fs-10 op-7 text-muted fw-semibold">DOANH THU ĐƠN HÀNG</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-top">
                                    <div class="me-3">
                                        <span class="avatar avatar-md p-2 bg-warning">
                                            <i class="fa-solid fa-money-bill-1 fs-16"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex mb-1 align-items-top justify-content-between">
                                            <h5 class="fw-semibold mb-0 lh-1">
                                                <?= format_currency($tien_von); ?>
                                            </h5>
                                        </div>
                                        <p class="mb-0 fs-10 op-7 text-muted fw-semibold">GIÁ VỐN</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-top">
                                    <div class="me-3">
                                        <span class="avatar avatar-md p-2 bg-success">
                                            <i class="fa-solid fa-money-bill-1 fs-16"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex mb-1 align-items-top justify-content-between">
                                            <h5 class="fw-semibold mb-0 lh-1">
                                                <?= format_currency($loi_nhuan); ?>
                                            </h5>
                                        </div>
                                        <p class="mb-0 fs-10 op-7 text-muted fw-semibold">LỢI NHUẬN</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <?php
                    $month = date('m');
                    $year = date('Y');
                    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

                    $labels = [];
                    $revenues = [];
                    $profits = [];

                    for ($day = 1; $day <= $numOfDays; $day++) {
                        $date = "$year-$month-$day";
                        $query = "SELECT SUM(pay), SUM(cost) FROM product_order WHERE `supplier_id` = $id AND `refund` = 0 AND DATE(create_gettime) = '$date'";
                        $result = $CMSNT->get_row($query);

                        $labels[] = "$day/$month/$year";
                        $revenues[] = $result['SUM(pay)'];
                        $profits[] = $result['SUM(pay)'] - $result['SUM(cost)'];
                    }
                    ?>
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title">
                                    <span id="chart-supplier-title">THỐNG KÊ ĐƠN HÀNG THÁNG <?= date('m'); ?></span>
                                </div>
                                <div>
                                    <select id="chart-supplier-time-range" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
                                        <option value="week">7 ngày gần đây</option>
                                        <option value="month" selected>Tháng <?= date('m'); ?></option>
                                        <option value="year">Năm <?= date('Y'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="chartjs-line" class="chartjs-chart" style="height: 300px;"></canvas>
                                <script>
                                    (function() {
                                        document.addEventListener('DOMContentLoaded', function() {
                                            let myChart;

                                            function loadChartData(timeRange) {
                                                // Cập nhật tiêu đề
                                                let titleText;
                                                switch (timeRange) {
                                                    case 'week':
                                                        titleText = 'THỐNG KÊ ĐƠN HÀNG 7 NGÀY GẦN ĐÂY';
                                                        break;
                                                    case 'month':
                                                        titleText = 'THỐNG KÊ ĐƠN HÀNG THÁNG <?= date('m'); ?>';
                                                        break;
                                                    case 'year':
                                                        titleText = 'THỐNG KÊ ĐƠN HÀNG NĂM <?= date('Y'); ?>';
                                                        break;
                                                }
                                                document.getElementById('chart-supplier-title').innerText = titleText;

                                                // Hủy biểu đồ cũ nếu tồn tại
                                                if (myChart) {
                                                    myChart.destroy();
                                                }

                                                // Gọi API lấy dữ liệu mới
                                                $.ajax({
                                                    url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                                                    method: 'POST',
                                                    dataType: 'json',
                                                    data: {
                                                        action: 'view_chart_thong_ke_don_hang_supplier',
                                                        token: '<?= $getUser['token']; ?>',
                                                        time_range: timeRange,
                                                        supplier_id: <?= $supplier['id']; ?>
                                                    },
                                                    success: function(response) {
                                                        const labels = response.labels;
                                                        const revenues = response.revenues;
                                                        const profits = response.profits;

                                                        const data = {
                                                            labels: labels,
                                                            datasets: [{
                                                                    label: 'Doanh thu',
                                                                    backgroundColor: 'rgb(132, 90, 223)',
                                                                    borderColor: 'rgb(132, 90, 223)',
                                                                    data: revenues,
                                                                },
                                                                {
                                                                    label: 'Lợi nhuận',
                                                                    backgroundColor: 'rgb(73,182,245)',
                                                                    borderColor: 'rgb(73,182,245)',
                                                                    data: profits,
                                                                }
                                                            ]
                                                        };

                                                        const config = {
                                                            type: 'bar',
                                                            data: data,
                                                            options: {
                                                                responsive: true,
                                                                maintainAspectRatio: false
                                                            }
                                                        };

                                                        myChart = new Chart(
                                                            document.getElementById('chartjs-line'),
                                                            config
                                                        );
                                                    }
                                                });
                                            }

                                            // Xử lý sự kiện khi người dùng thay đổi khoảng thời gian
                                            document.getElementById('chart-supplier-time-range').addEventListener('change', function() {
                                                loadChartData(this.value);
                                            });

                                            // Khởi tạo biểu đồ với tháng hiện tại
                                            setTimeout(function() {
                                                Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)";
                                                Chart.defaults.color = "#8c9097";
                                                loadChartData('month');
                                            }, 5);
                                        });
                                    })();
                                </script>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="tab-pane text-muted" id="tab_category" role="tabpanel">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header justify-content-between">
                                <div class="card-title">
                                    DANH SÁCH CHUYÊN MỤC API
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable-categories-api" class="table table-bordered text-nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 50px;">
                                                    <div class="form-check form-check-md d-flex align-items-center">
                                                        <input type="checkbox" class="form-check-input" name="check_all"
                                                            id="check_all_checkbox_category_api" value="option1">
                                                    </div>
                                                </th>
                                                <th>Tên chuyên mục con</th>
                                                <th>Chuyên mục cha</th>
                                                <th>Thống kê</th>
                                                <th>Ảnh</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded via Ajax -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7">
                                                    <div class="btn-list">
                                                        <button type="button" id="btn_cap_nhat_nhanh_category"
                                                            class="btn btn-outline-primary shadow-primary btn-wave btn-sm"><i
                                                                class="fa-solid fa-pen-to-square"></i> CẬP NHẬT NHANH</button>
                                                        <button type="button" id="btn_delete_category"
                                                            class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                                class="fa-solid fa-trash"></i> XÓA CHUYÊN MỤC</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="modal fade" id="modal_cap_nhat_nhanh_category" tabindex="-1" aria-labelledby="Cập nhật nhanh chuyên mục"
                                    data-bs-keyboard="false" aria-hidden="true">
                                    <!-- Scrollable modal -->
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title" id="staticBackdropLabel2">Cập nhật nhanh toàn bộ <mark
                                                        class="checkboxeslength"></mark> chuyên mục đã chọn</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-4">
                                                    <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Chuyên mục cha:'); ?></label>
                                                    <div class="col-sm-8">
                                                        <select class="form-control" id="category_category_id" required>
                                                            <option value=""><?= __('Giữ nguyên chuyên mục cha hiện tại'); ?></option>
                                                            <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                                                <option value="<?= $option['id']; ?>"><?= $option['name']; ?></option>
                                                            <?php endforeach ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mb-4">
                                                    <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Trạng thái:'); ?></label>
                                                    <div class="col-sm-8">
                                                        <select class="form-control" id="category_status" required>
                                                            <option value="">Giữ nguyên trạng thái hiện tại</option>
                                                            <option value="ON">ON</option>
                                                            <option value="OFF">OFF</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <p>Khi bạn nhấn vào nút UPDATE đồng nghĩa các chuyên mục mà bạn đã chọn sẽ được cập nhật thông tin trên.
                                                </p>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                <button type="button" onclick="cap_nhat_nhanh_category_records()" id="cap_nhat_nhanh_category_records"
                                                    class="btn btn-primary"><i class="fa fa-solid fa-save"></i> <?= __('Update'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    $(function() {
                                        $('#check_all_checkbox_category_api').on('click', function() {
                                            $('.checkbox_category_api').prop('checked', this.checked);
                                        });
                                        $('.checkbox_category_api').on('click', function() {
                                            $('#check_all_checkbox_category_api').prop('checked', $(
                                                    '.checkbox_category_api:checked')
                                                .length === $('.checkbox_category_api').length);
                                        });
                                    });

                                    $("#btn_cap_nhat_nhanh_category").click(function() {
                                        var checkboxes = document.querySelectorAll(
                                            'input[name="checkbox_category_api"]:checked');
                                        if (checkboxes.length === 0) {
                                            showMessage('Vui lòng chọn ít nhất một chuyên mục.', 'error');
                                            return;
                                        }
                                        $(".checkboxeslength").html(checkboxes.length);
                                        $("#modal_cap_nhat_nhanh_category").modal('show');
                                    });

                                    $("#btn_delete_category").click(function() {
                                        var checkboxes = document.querySelectorAll(
                                            'input[name="checkbox_category_api"]:checked');
                                        if (checkboxes.length === 0) {
                                            showMessage('<?= __('Vui lòng tích vào chuyên mục cần xóa.'); ?>',
                                                'error');
                                            return;
                                        }
                                        Swal.fire({
                                            title: "<?= __('Bạn có chắc không?'); ?>",
                                            text: "<?= __('Hệ thống sẽ XÓA'); ?> " + checkboxes.length +
                                                " <?= __('chuyên mục bạn chọn khi nhấn Đồng Ý'); ?>",
                                            icon: "warning",
                                            showCancelButton: true,
                                            confirmButtonColor: "#3085d6",
                                            cancelButtonColor: "#d33",
                                            confirmButtonText: "<?= __('Đồng ý'); ?>",
                                            cancelButtonText: "<?= __('Đóng'); ?>"
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                delete_category_records();
                                            }
                                        });
                                    });

                                    function cap_nhat_nhanh_category_records() {
                                        $('#cap_nhat_nhanh_category_records').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled',
                                            true);
                                        var category_id = document.getElementById('category_category_id').value;
                                        var status = document.getElementById('category_status').value;
                                        var checkbox = document.getElementsByName('checkbox_category_api');
                                        // Sử dụng hàm đệ quy để thực hiện lần lượt từng postUpdate với thời gian chờ 100ms
                                        function postUpdatesSequentially(index) {
                                            if (index < checkbox.length) {
                                                if (checkbox[index].checked === true) {
                                                    post_cap_nhat_nhanh_category(checkbox[index].value, category_id, status);
                                                }
                                                setTimeout(function() {
                                                    postUpdatesSequentially(index + 1);
                                                }, 100);
                                            } else {
                                                Swal.fire({
                                                    title: "Thành công!",
                                                    text: "Cập nhật thành công",
                                                    icon: "success"
                                                });
                                                setTimeout(function() {
                                                    location.reload();
                                                }, 1000);
                                                $('#cap_nhat_nhanh_category_records').html('<i class="fa fa-solid fa-save"></i> <?= __('Update'); ?>').prop(
                                                    'disabled',
                                                    false);
                                            }
                                        }
                                        // Bắt đầu gọi hàm đệ quy từ index 0
                                        postUpdatesSequentially(0);
                                    }

                                    function post_cap_nhat_nhanh_category(id, category_id, status) {
                                        $.ajax({
                                            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                                            method: "POST",
                                            dataType: "JSON",
                                            data: {
                                                action: 'cap_nhat_chuyen_muc_nhanh',
                                                id: id,
                                                category_id: category_id,
                                                status: status
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

                                    function delete_category_records() {
                                        var checkbox = document.getElementsByName('checkbox_category_api');

                                        function postUpdatesSequentially(index) {
                                            if (index < checkbox.length) {
                                                if (checkbox[index].checked === true) {
                                                    post_delete_category(checkbox[index].value);
                                                }
                                                setTimeout(function() {
                                                    postUpdatesSequentially(index + 1);
                                                }, 100);
                                            } else {
                                                Swal.fire({
                                                    title: "<?= __('Thành công!'); ?>",
                                                    text: "<?= __('Xóa thành công'); ?>",
                                                    icon: "success"
                                                });
                                                setTimeout(function() {
                                                    location.reload();
                                                }, 1000);
                                            }
                                        }
                                        postUpdatesSequentially(0);
                                    }

                                    function post_delete_category(id) {
                                        $.ajax({
                                            url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                                            method: "POST",
                                            dataType: "JSON",
                                            data: {
                                                id: id,
                                                token: '<?= $getUser['token'] ?>',
                                                action: 'removeCategory'
                                            },
                                            success: function(result) {
                                                if (result.status == 'success') {
                                                    showMessage(result.msg, result.status);
                                                } else {
                                                    showMessage(result.msg, result.status);
                                                }
                                            },
                                            error: function() {
                                                alert(html(response));
                                                location.reload();
                                            }
                                        });
                                    }

                                    function deleteCategory(id) {
                                        const originalContent = $('#btnDeleteCategory' + id)
                                            .html(); // Save the original button content
                                        $('#btnDeleteCategory' + id).html(
                                                '<span><i class="fa fa-spinner fa-spin"></i></span>')
                                            .prop('disabled', true);

                                        Swal.fire({
                                            title: "<?= __('Bạn có chắc không?'); ?>",
                                            text: "<?= __('Hệ thống sẽ xóa chuyên mục này nếu bạn nhấn Đồng ý'); ?>",
                                            icon: "warning",
                                            showCancelButton: true,
                                            confirmButtonColor: "#3085d6",
                                            cancelButtonColor: "#d33",
                                            confirmButtonText: "<?= __('Đồng ý'); ?>",
                                            cancelButtonText: "<?= __('Đóng'); ?>"
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                post_delete_category(id);
                                                setTimeout(() => {
                                                    location.reload();
                                                }, 500);
                                            }
                                        }).finally(() => {
                                            $('#btnDeleteCategory' + id).html(originalContent)
                                                .prop('disabled', false);
                                        });
                                    }
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane text-muted" id="tab2" role="tabpanel">
                <div class="row">
                    <div class="col-xl-12">
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
                                    <input type="hidden" name="id" value="<?= $id; ?>">
                                    <input type="hidden" name="action" value="product-api-manager">
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
                                            <label class="form-label"><?= __('Thời gian'); ?></label>
                                            <input type="text" name="create_gettime" class="form-control" id="daterange"
                                                value="<?= htmlspecialchars($create_gettime); ?>" placeholder="<?= __('Chọn thời gian'); ?>">
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
                                            <a href="<?= base_url_admin('product-api-manager&id=' . $id); ?>" class="btn btn-secondary">
                                                <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card custom-card">

                            <div class="card-body p-0">

                                <div class="table-responsive table-wrapper mb-3">
                                    <table class="table text-nowrap table-striped table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center">
                                                    <div class="form-check form-check-md d-flex align-items-center">
                                                        <input type="checkbox" class="form-check-input" name="check_all"
                                                            id="check_all_checkbox_product_api" value="option1">
                                                    </div>
                                                </th>
                                                <th><?= __('Sản phẩm'); ?></th>
                                                <th class="text-center"><?= __('Chuyên mục'); ?></th>
                                                <th class="text-center"><?= __('Trạng thái'); ?></th>
                                                <th class="text-center"><?= __('Giá bán'); ?></th>
                                                <th class="text-center"><?= __('Chi tiết'); ?></th>
                                                <th class="text-center"><?= __('Thời gian'); ?></th>
                                                <th class="text-center"><?= __('Thao tác'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($listDatatable as $product): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <div class="form-check form-check-md d-flex align-items-center">
                                                            <input type="checkbox"
                                                                class="form-check-input checkbox_product_api"
                                                                data-id="<?= $product['id']; ?>" name="checkbox_product_api"
                                                                value="<?= $product['id']; ?>" />
                                                        </div>
                                                    </td>
                                                    <td>
                                                        Tên sản phẩm hệ thống: <b><?= $product['name']; ?></b><br>
                                                        Tên sản phẩm API: <b><?= $product['api_name']; ?></b>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($product['category_id'] != 0): ?>
                                                            <span
                                                                class="badge bg-primary"><?= getRowRealtime('categories', $product['category_id'], 'name'); ?></span>
                                                        <?php endif ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch form-check-lg"
                                                            onchange="post_update_status_table_product(`<?= $product['id']; ?>`)">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="status<?= $product['id']; ?>" value="1"
                                                                <?= $product['status'] == 1 ? 'checked=""' : ''; ?>>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        Giá bán: <b
                                                            style="color:red;"><?= format_currency($product['price']); ?></b><br>
                                                        Giá vốn: <b
                                                            style="color:blue;"><?= format_currency($product['cost']); ?></b>
                                                    </td>
                                                    <td>
                                                        Đang bán: <b><?= format_cash($product['api_stock']); ?></b><br>
                                                        Đã bán: <b><?= format_cash($product['sold']); ?></b>
                                                    </td>
                                                    <td><small><?= $product['create_gettime']; ?></small></td>
                                                    <td>
                                                        <a type="button"
                                                            href="<?= base_url_admin('product-edit&id=' . $product['id']); ?>"
                                                            class="btn btn-sm btn-secondary shadow-secondary btn-wave"
                                                            data-bs-toggle="tooltip" title="<?= __('Chỉnh sửa'); ?>">
                                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                                        </a>
                                                        <a type="button" onclick="removeProduct('<?= $product['id']; ?>')"
                                                            id="btnDeleteProduct<?= $product['id']; ?>"
                                                            class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                            data-bs-toggle="tooltip" title="<?= __('Xóa'); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                        <tfoot>
                                            <td colspan="9">
                                                <div class="btn-list">
                                                    <button type="button" id="btn_cap_nhat_nhanh"
                                                        class="btn btn-outline-primary shadow-primary btn-wave btn-sm"><i
                                                            class="fa-solid fa-pen-to-square"></i> CẬP NHẬT NHANH</button>
                                                    <button type="button" id="btn_delete_product"
                                                        class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                            class="fa-solid fa-trash"></i> XÓA SẢN PHẨM</button>
                                                </div>
                                            </td>
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
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xác định tab được lưu trong localStorage
            let activeTab = localStorage.getItem('activeTab');

            // Nếu không có tab được lưu trong localStorage, hoặc không tồn tại tab được lưu
            if (!activeTab || !document.querySelector(activeTab)) {
                // Đặt tab "Thống kê" làm active mặc định
                let defaultTab = document.querySelector('a[data-bs-target="#tab1"]');
                if (defaultTab) {
                    defaultTab.classList.add('active');
                    defaultTab.classList.add('show');
                    let defaultTabContent = document.querySelector('#tab1');
                    if (defaultTabContent) {
                        defaultTabContent.classList.add('active');
                        defaultTabContent.classList.add('show');
                    }
                }
            } else {
                // Kích hoạt tab được lưu
                let tabLink = document.querySelector(`a[data-bs-target="${activeTab}"]`);
                if (tabLink) {
                    tabLink.classList.add('active');
                    tabLink.classList.add('show');
                    let tabContent = document.querySelector(activeTab);
                    if (tabContent) {
                        tabContent.classList.add('active');
                        tabContent.classList.add('show');
                    }
                }
            }

            // Lắng nghe sự kiện khi click vào tab
            let tabs = document.querySelectorAll('.nav-link');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    // Lưu tab được click vào localStorage
                    let targetTab = tab.getAttribute('data-bs-target');
                    localStorage.setItem('activeTab', targetTab);
                });
            });
        });
    </script>

    <script>
        $(function() {
            $('#check_all_checkbox_product_api').on('click', function() {
                $('.checkbox_product_api').prop('checked', this.checked);
            });
            $('.checkbox_product_api').on('click', function() {
                $('#check_all_checkbox_product_api').prop('checked', $('.checkbox_product_api:checked')
                    .length === $('.checkbox_product_api').length);
            });
        });
    </script>


    <script>
        function post_update_category_product(id, category_id) {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'update_category_product',
                    id: id,
                    category_id: category_id
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

        function post_update_status_product(id, status) {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'update_status_product',
                    id: id,
                    status: status
                },
                success: function(result) {
                    if (result.status == 'success') {
                        if (status == 1) {
                            document.getElementById('status' + id).checked = true;
                        } else {
                            document.getElementById('status' + id).checked = false;
                        }
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
                        showMessage(result.msg, result.status);
                    } else {
                        showMessage(result.msg, result.status);
                    }
                },
                error: function() {
                    alert(html(response));
                    location.reload();
                }
            });
        }

        function removeProduct(id) {
            const originalContent = $('#btnDeleteProduct' + id).html(); // Save the original button content
            $('#btnDeleteProduct' + id).html('<span><i class="fa fa-spinner fa-spin"></i></span>')
                .prop('disabled', true);
            Swal.fire({
                title: "<?= __('Bạn có chắc không?'); ?>",
                text: "<?= __('Hệ thống sẽ sản phẩm này nếu bạn nhấn Đồng ý'); ?>",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "<?= __('Đồng ý'); ?>",
                cancelButtonText: "<?= __('Đóng'); ?>"
            }).then((result) => {
                if (result.isConfirmed) {
                    post_remove_product(id);
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            }).finally(() => {
                // Restore the button content and enable it when Swal closes
                $('#btnDeleteProduct' + id).html(originalContent)
                    .prop('disabled', false);
            });
        }
    </script>



    <script>
        $(document).ready(function() {

            $("#btn_cap_nhat_nhanh").click(function() {
                var checkboxes = document.querySelectorAll('input[name="checkbox_product_api"]:checked');
                if (checkboxes.length === 0) {
                    showMessage('Vui lòng chọn ít nhất một sản phẩm.', 'error');
                    return;
                }
                $(".checkboxeslength").html(checkboxes.length);
                $("#modal_cap_nhat_nhanh").modal('show');
            });

            $("#btn_delete_product").click(function() {
                var checkboxes = document.querySelectorAll('input[name="checkbox_product_api"]:checked');
                if (checkboxes.length === 0) {
                    showMessage('Vui lòng chọn ít nhất một sản phẩm.', 'error');
                    return;
                }
                Swal.fire({
                    title: "Bạn có chắc không?",
                    text: "Hệ thống sẽ xóa " + checkboxes.length +
                        " sản phẩm bạn chọn khi nhấn Đồng Ý",
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

            var checkbox = document.querySelectorAll('.checkbox_product_api');

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
            var checkbox = document.getElementsByName('checkbox_product_api');

            function postUpdatesSequentially(index) {
                if (index < checkbox.length) {
                    if (checkbox[index].checked === true) {
                        post_remove_product(checkbox[index].value);
                    }
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 100);
                } else {
                    Swal.fire({
                        title: "Thành công!",
                        text: "Xóa sản phẩm thành công",
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
            localStorage.setItem('product_api_manager_filter_expanded', 'true');
        } else {
            filterBody.style.display = 'none';
            filterIcon.className = 'fa-solid fa-chevron-down';
            localStorage.setItem('product_api_manager_filter_expanded', 'false');
        }
    }

    // Khôi phục trạng thái filter form khi load trang
    $(document).ready(function() {
        var isFilterExpanded = localStorage.getItem('product_api_manager_filter_expanded');
        <?php
        // Tự động mở nếu có filter đang active
        $has_active_filter = !empty($user_id) || !empty($username) || !empty($name)
            || !empty($status) || !empty($category_id)
            || !empty($create_gettime) || !empty($shortByDate)
            || !empty($sort_stock) || !empty($sort_price);
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

    // Categories API DataTable with Ajax
    var categoriesTable = $('#datatable-categories-api').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "<?= base_url('ajaxs/admin/view.php'); ?>",
            "type": "POST",
            "data": {
                "action": "load_categories_api_datatable",
                "supplier_id": "<?= $id; ?>",
                "token": "<?= $getUser['token']; ?>"
            },
            "dataSrc": function(json) {
                if (json.status === 'error') {
                    Swal.fire('Lỗi!', json.msg, 'error');
                    return [];
                }
                return json.data;
            }
        },
        "columns": [{
                "data": null,
                "orderable": false,
                "className": "text-center",
                "render": function(data, type, row) {
                    return '<div class="form-check form-check-md d-flex align-items-center">' +
                        '<input type="checkbox" class="form-check-input checkbox_category_api" ' +
                        'data-id="' + row.id + '" name="checkbox_category_api" value="' + row.id + '" />' +
                        '</div>';
                }
            },
            {
                "data": "name",
                "render": function(data, type, row) {
                    return data;
                }
            },
            {
                "data": "parent_name",
                "render": function(data, type, row) {
                    if (row.parent_id > 1 && data) {
                        return '<a class="text-primary" href="' + row.parent_url + '">' +
                            '<i class="fa-solid fa-pen-to-square"></i> ' + data + '</a>';
                    }
                    return '';
                }
            },
            {
                "data": "total_products",
                "render": function(data, type, row) {
                    return '<span class="badge bg-outline-primary">Sản phẩm: ' + data.toLocaleString() + '</span>';
                }
            },
            {
                "data": "icon",
                "orderable": false,
                "render": function(data, type, row) {
                    return '<img src="' + data + '" width="40px">';
                }
            },
            {
                "data": "status",
                "className": "text-center",
                "render": function(data, type, row) {
                    var checked = data == 1 ? 'checked=""' : '';
                    return '<div class="form-check form-switch form-check-lg">' +
                        '<input class="form-check-input category-status-toggle" type="checkbox" ' +
                        'data-id="' + row.id + '" value="1" ' + checked + '>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return '<a type="button" href="' + row.edit_url + '" ' +
                        'class="btn btn-info shadow-info btn-wave btn-sm" ' +
                        'data-bs-toggle="tooltip" title="<?= __('Edit'); ?>">' +
                        '<i class="fa-solid fa-pen-to-square"></i> Edit</a> ' +
                        '<a type="button" onclick="deleteCategory(\'' + row.id + '\')" ' +
                        'id="btnDeleteCategory' + row.id + '" ' +
                        'class="btn btn-danger shadow-danger btn-wave btn-sm" ' +
                        'data-bs-toggle="tooltip" title="<?= __('Delete'); ?>">' +
                        '<i class="fas fa-trash"></i> Delete</a>';
                }
            }
        ],
        "language": {
            "searchPlaceholder": "Tìm kiếm...",
            "sSearch": "",
            "processing": "Đang tải dữ liệu...",
            "lengthMenu": "Hiển thị _MENU_ mục",
            "info": "Hiển thị _START_ đến _END_ trong _TOTAL_ mục",
            "infoEmpty": "Không có dữ liệu",
            "infoFiltered": "(lọc từ _MAX_ mục)",
            "zeroRecords": "Không tìm thấy dữ liệu",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Tiếp",
                "previous": "Trước"
            }
        },
        "pageLength": 10,
        "scrollX": true,
        "order": [
            [1, 'asc']
        ],
        "drawCallback": function(settings) {
            // Re-init tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Handle check all checkbox
    $('#check_all_checkbox_category_api').on('change', function() {
        $('.checkbox_category_api').prop('checked', $(this).prop('checked'));
    });

    // Handle category status toggle (giống code cũ)
    $(document).on('change', '.category-status-toggle', function() {
        var categoryId = $(this).data('id');
        var status = $(this).prop('checked') ? 1 : 0;
        var checkbox = $(this);

        $.ajax({
            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
            type: "POST",
            dataType: "JSON",
            data: {
                id: categoryId,
                status: status,
                action: 'update_status_category',
                token: "<?= $getUser['token']; ?>"
            },
            success: function(data) {
                if (data.status == 'success') {
                    showMessage(data.msg, 'success');
                } else {
                    showMessage(data.msg, 'error');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                }
            },
            error: function() {
                showMessage('Không thể cập nhật trạng thái', 'error');
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
    });
</script>