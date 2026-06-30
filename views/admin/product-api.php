<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý kết nối API') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
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
$where = " `id` > 0 ";
$shortByDate  = '';


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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `suppliers` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `suppliers` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("product-api&limit=$limit&shortByDate=$shortByDate&"), $from, $totalDatatable, $limit);

?>
<?php
$doanh_thu = $CMSNT->get_row(" SELECT SUM(pay) FROM product_order WHERE `refund` = 0 AND supplier_id != 0 ")['SUM(pay)'];
$tien_von = $CMSNT->get_row(" SELECT SUM(cost) FROM product_order WHERE `refund` = 0 AND supplier_id != 0 ")['SUM(cost)'];
$loi_nhuan = $doanh_thu - $tien_von;
$don_hang = $CMSNT->get_row(" SELECT COUNT(id) FROM product_order WHERE `refund` = 0 AND supplier_id != 0 ")['COUNT(id)'];

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-code"></i> Kết nối API</h1>
        </div>
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
                                        <?= format_cash($don_hang); ?>
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
        <!-- <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title">
                            <span id="chart-api-title">THỐNG KÊ DOANH THU API 7 NGÀY GẦN ĐÂY <?= date('m'); ?></span>
                        </div>
                        <div>
                            <select id="chart-api-time-range" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
                                <option value="week" selected>7 ngày gần đây</option>
                                <option value="month">Tháng <?= date('m'); ?></option>
                                <option value="year">Năm <?= date('Y'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="chart-loading" class="text-center" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); z-index:100; width:100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2">Đang tải dữ liệu...</p>
                        </div>
                        <canvas id="chartjs-line" class="chartjs-chart" style="height: 300px;"></canvas>
                        <script>
                        (function() {
                            document.addEventListener('DOMContentLoaded', function() {
                                let myChart;
                                const chartContainer = document.getElementById('chartjs-line').parentNode;
                                const loadingElement = document.getElementById('chart-loading');
                                
                                function showLoading() {
                                    loadingElement.style.display = 'block';
                                }
                                
                                function hideLoading() {
                                    loadingElement.style.display = 'none';
                                }
                                
                                function loadChartData(timeRange) {
                                    // Hiển thị loading
                                    showLoading();
                                    
                                    // Cập nhật tiêu đề
                                    let titleText;
                                    switch(timeRange) {
                                        case 'week':
                                            titleText = 'THỐNG KÊ DOANH THU API 7 NGÀY GẦN ĐÂY';
                                            break;
                                        case 'month':
                                            titleText = 'THỐNG KÊ DOANH API THÁNG <?= date('m'); ?>';
                                            break;
                                        case 'year':
                                            titleText = 'THỐNG KÊ DOANH THU API NĂM <?= date('Y'); ?>';
                                            break;
                                    }
                                    document.getElementById('chart-api-title').innerText = titleText;
                                    
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
                                            action: 'view_chart_doanh_thu_api_suppliers',
                                            token: '<?= $getUser['token']; ?>',
                                            time_range: timeRange
                                        },
                                        success: function(response) {
                                            const labels = response.labels;
                                            const suppliers = response.suppliers || [];
                                            
                                            // Ẩn loading sau khi dữ liệu đã được tải xong
                                            hideLoading();
                                            
                                            // Kiểm tra nếu không có dữ liệu
                                            if (!suppliers || suppliers.length === 0) {
                                                // Hiển thị thông báo không có dữ liệu
                                                chartContainer.innerHTML = `
                                                    <div class="text-center py-5">
                                                        <i class="fa-solid fa-chart-line text-muted mb-3" style="font-size: 4rem;"></i>
                                                        <h5 class="text-muted">Không có dữ liệu để hiển thị</h5>
                                                        <p class="text-muted">Hiện chưa có doanh thu nào từ các nhà cung cấp API</p>
                                                    </div>
                                                `;
                                                return;
                                            }
                                            
                                            // Tạo màu ngẫu nhiên cho mỗi API supplier
                                            function getRandomColor() {
                                                const letters = '0123456789ABCDEF';
                                                let color = '#';
                                                for (let i = 0; i < 6; i++) {
                                                    color += letters[Math.floor(Math.random() * 16)];
                                                }
                                                return color;
                                            }
                                            
                                            // Tạo datasets cho từng nhà cung cấp API
                                            const datasets = suppliers.map(supplier => {
                                                const color = getRandomColor();
                                                return {
                                                    label: supplier.name || supplier.domain || 'API ' + supplier.domain,
                                                    backgroundColor: color,
                                                    borderColor: color,
                                                    data: supplier.revenues || [],
                                                };
                                            });

                                            const data = {
                                                labels: labels,
                                                datasets: datasets
                                            };

                                            const config = {
                                                type: 'bar',
                                                data: data,
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: {
                                                            position: 'top',
                                                        }
                                                    }
                                                }
                                            };

                                            myChart = new Chart(
                                                document.getElementById('chartjs-line'),
                                                config
                                            );
                                        },
                                        error: function(xhr, status, error) {
                                            // Nếu có lỗi, ẩn loading và hiển thị thông báo lỗi
                                            hideLoading();
                                            console.error("Lỗi khi tải dữ liệu biểu đồ:", error);
                                            // Tùy chọn: thêm thông báo lỗi cho người dùng
                                            chartContainer.innerHTML = '<div class="alert alert-danger">Không thể tải dữ liệu biểu đồ. Vui lòng thử lại sau.</div>';
                                        }
                                    });
                                }
                                
                                // Xử lý sự kiện khi người dùng thay đổi khoảng thời gian
                                document.getElementById('chart-api-time-range').addEventListener('change', function() {
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
        </div> -->
        <?php
        // Kiểm tra cron job cho từng supplier (chỉ báo 1 lần cho mỗi type)
        $cron_warned = [];
        foreach ($listDatatable as $sup) {
            foreach ($cron_suppliers as $type => $key) {
                if ($sup['type'] == $type && !in_array($type, $cron_warned) && time() - $CMSNT->site("time_cron_suppliers_$key") >= 120) {
                    $cron_warned[] = $type;
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
        }
        ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH API ĐANG KẾT NỐI
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?= base_url_admin('product-api-add'); ?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> THÊM WEBSITE API</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url_admin(); ?>" class="align-items-center mb-3" name="formSearch"
                            method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="product-api">
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
                                        <th><?= __('Website'); ?></th>
                                        <th class="text-center"><?= __('Type'); ?></th>
                                        <th class="text-center"><?= __('Số dư'); ?></th>
                                        <th class="text-center"><?= __('Thống kê'); ?></th>
                                        <th class="text-center"><?= __('Chi tiết'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $supplier): ?>
                                        <?php
                                        $query = "SELECT 
                                    SUM(pay) AS total_pay, 
                                    SUM(cost) AS total_cost, 
                                    COUNT(id) AS total_orders 
                                    FROM product_order 
                                    WHERE supplier_id = '" . $supplier['id'] . "'";
                                        $result = $CMSNT->get_row($query);
                                        $doanh_thu = $result['total_pay'];
                                        $loi_nhuan = $doanh_thu - $result['total_cost'];
                                        $don_hang = $result['total_orders'];

                                        ?>
                                        <tr onchange="updateForm(`<?= $supplier['id']; ?>`)">
                                            <td>
                                                <?php if (!empty($supplier['domain'])): ?>
                                                    <i class="fa-solid fa-link"></i> Domain: <a class="text-primary"
                                                        href="<?= $supplier['domain']; ?>"
                                                        target="_blank"><?= $supplier['domain']; ?></a><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['username'])): ?>
                                                    <i class="fa-solid fa-user"></i> Username:
                                                    <strong><?= substr($supplier['username'], 0, 4); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['password'])): ?>
                                                    <i class="fa-solid fa-lock"></i> Password:
                                                    <strong><?= substr($supplier['password'], 0, 4); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['api_key'])): ?>
                                                    <i class="fa-solid fa-key"></i> API Key:
                                                    <strong><?= substr($supplier['api_key'], 0, 12); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['token'])): ?>
                                                    <i class="fa-solid fa-key"></i> Token:
                                                    <strong><?= substr($supplier['token'], 0, 12); ?>...</strong>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $supplier['type']; ?></span>
                                            </td>
                                            <td class="text-right">
                                                <?= check_string($supplier['price']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $categoryCount = format_cash($CMSNT->get_row("SELECT COUNT(id) FROM `categories` WHERE `supplier_id` = '" . $supplier['id'] . "' ")['COUNT(id)']);
                                                $productCount = format_cash($CMSNT->get_row("SELECT COUNT(id) FROM `products` WHERE `supplier_id` = '" . $supplier['id'] . "' ")['COUNT(id)']);
                                                ?>
                                                <span class="badge bg-outline-warning">Chuyên mục: <?= $categoryCount; ?></span><br>
                                                <span class="badge bg-outline-primary">Sản phẩm: <?= $productCount; ?></span><br>
                                                <span class="badge bg-outline-info">Đơn hàng: <?= format_cash($don_hang); ?></span><br>
                                                <span class="badge bg-outline-danger">Doanh thu: <?= format_currency($doanh_thu); ?></span><br>
                                                <span class="badge bg-outline-success">Lợi nhuận: <?= format_currency($loi_nhuan); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $updateNameBadge = $supplier['update_name'] == 'ON' ? 'success' : 'danger';
                                                $updatePriceBadge = $supplier['update_price'] == 'ON' ? 'success' : 'danger';
                                                $roundMoneyBadge = $supplier['roundMoney'] == 'ON' ? 'success' : 'danger';
                                                $syncCategoryBadge = $supplier['sync_category'] == 'ON' ? 'success' : 'danger';
                                                ?>
                                                Tăng giá: <span class="badge bg-outline-primary"><?= $supplier['discount']; ?>%</span><br>
                                                Cập nhật tên sản phẩm: <span class="badge bg-<?= $updateNameBadge; ?>"><?= $supplier['update_name']; ?></span><br>
                                                Cập nhật giá bán: <span class="badge bg-<?= $updatePriceBadge; ?>"><?= $supplier['update_price']; ?></span><br>
                                                Làm tròn giá bán: <span class="badge bg-<?= $roundMoneyBadge; ?>"><?= $supplier['roundMoney']; ?></span><br>
                                                Đồng bộ chuyên mục API: <span class="badge bg-<?= $syncCategoryBadge; ?>"><?= $supplier['sync_category']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch form-check-lg">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="status<?= $supplier['id']; ?>" value="1"
                                                        <?= $supplier['status'] == 1 ? 'checked=""' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-gear"></i> <?= __('Thao tác'); ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('product-api-manager&id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-bars-progress text-primary me-2"></i> <?= __('Thống kê'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('product-api-manager&id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-folder text-secondary me-2"></i> <?= __('Chuyên mục'); ?> (<?= $categoryCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('products&supplier_id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-box text-warning me-2"></i> <?= __('Sản phẩm'); ?> (<?= $productCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('product-orders&supplier_id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-cart-shopping text-success me-2"></i> <?= __('Đơn hàng'); ?> (<?= format_cash($don_hang); ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= base_url_admin('product-api-edit&id=' . $supplier['id']); ?>">
                                                                <i class="fas fa-edit text-info me-2"></i> <?= __('Chỉnh sửa'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removeCategoriesOnly('<?= $supplier['id']; ?>', '<?= $categoryCount; ?>')"
                                                                data-categories="<?= $categoryCount; ?>">
                                                                <i class="fas fa-folder-minus text-danger me-2"></i> <?= __('Xóa chuyên mục'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removeProductsOnly('<?= $supplier['id']; ?>', '<?= $productCount; ?>')"
                                                                data-services="<?= $productCount; ?>">
                                                                <i class="fas fa-box-open text-danger me-2"></i> <?= __('Xóa sản phẩm'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removeCategoriesServices('<?= $supplier['id']; ?>', '<?= $categoryCount; ?>', '<?= $productCount; ?>')"
                                                                data-categories="<?= $categoryCount; ?>"
                                                                data-services="<?= $productCount; ?>">
                                                                <i class="fas fa-trash text-danger me-2"></i> <?= __('Xóa chuyên mục và sản phẩm'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="removeItem('<?= $supplier['id']; ?>')">
                                                                <i class="fas fa-trash text-danger me-2"></i> <?= __('Xóa'); ?>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
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


        <style>
            .brand-carousel {
                width: 100%;
                overflow: hidden;
                animation: moveCards 25s linear infinite;
                white-space: nowrap;
            }

            .brand-carousel-container {
                width: 100%;
                overflow-x: auto;
            }

            .brand-carousel {
                white-space: nowrap;
                font-size: 0;
                width: max-content;
            }

            .brand-card {
                font-size: 16px;
                display: inline-block;
                vertical-align: top;
                margin-right: 20px;
                transition: all 0.3s ease;
            }

            .brand-carousel:hover {
                animation-play-state: paused;
            }

            @keyframes moveCards {
                0% {
                    transform: translateX(0%);
                }

                100% {
                    transform: translateX(-100%);
                }
            }

            .brand-card {
                position: relative;
                display: inline-block;
                margin: 10px;
                vertical-align: middle;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 20px;
                transition: all 0.3s ease;
            }

            .brand-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .brand-card img {
                width: 100px;
                height: 100px;
                object-fit: contain;
                margin-bottom: 20px;
            }

            .connect-button,
            .website-button {
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                color: #fff;
                padding: 5px 10px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }

            .brand-card:hover .connect-button,
            .brand-card:hover .website-button {
                opacity: 1;
            }

            .website-button {
                bottom: 45px;
            }

            .api-section {
                border-left: 4px solid #3498db;
                transition: all 0.3s ease;
            }

            .api-section:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        </style>
        <div class="row justify-content-center py-4">
            <div class="col-12 text-center mb-3">
                <h4 class="fw-bold"><i class="fa-solid fa-boxes-packing text-primary me-2"></i>Nhà cung cấp API gợi ý</h4>
                <p class="text-muted">Kết nối nhanh với các nhà cung cấp API đáng tin cậy</p>
            </div>
            <div class="brand-carousel-container">
                <div class="brand-carousel animated-carousel">

                </div>
            </div>
            <div class="mt-3 text-center" id="notitcation_suppliers"></div>
        </div>
        <script>
            $(document).ready(function() {
                $('.brand-carousel').html('');
                $.ajax({
                    url: 'https://api.cmsnt.co/suppliers.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Xử lý dữ liệu trả về từ server
                        if (response && response.suppliers.length > 0) {
                            var html = '';
                            $.each(response.suppliers, function(index, brand) {
                                html += '<div class="brand-card">';
                                html += '<img src="' + brand.logo + '" alt="Logo" class="mb-2">';
                                html +=
                                    '<a href="<?= base_url_admin("product-api-add"); ?>&domain=' +
                                    brand.domain + '&type=' + brand.type +
                                    '" class="connect-button btn btn-sm btn-danger">Kết nối</a>';
                                html += '<a href="' + brand.domain +
                                    '?utm_source=ads_cmsnt" target="_blank" class="website-button btn btn-sm btn-primary">Xem</a>';
                                html += '</div>';
                            });
                            $('.brand-carousel').html(html);
                            $('#notitcation_suppliers').html(response.notication);
                            calculateAndSetAnimationDuration();
                        } else {
                            $('.brand-carousel').html('');
                        }
                    },
                    error: function() {
                        $('.brand-carousel').html('');
                    }
                });
            });
            // Function to calculate carousel width and set animation duration
            function calculateAndSetAnimationDuration() {
                var carousel = $('.animated-carousel');
                var carouselWidth = carousel[0].scrollWidth;
                var cardWidth = carousel.children().first().outerWidth(true); // Including margin
                var numberOfCards = carouselWidth / cardWidth;
                var animationDuration = numberOfCards * 2; // Adjust this multiplier as needed
                carousel.css('animation-duration', animationDuration + 's');
            }
        </script>

    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    function updateForm(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateTableProductAPI',
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




    var lightboxVideo = GLightbox({
        selector: '.glightbox'
    });
</script>



<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteModalLabel">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <?= __('Xác nhận xóa nhà cung cấp API'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-warning">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-exclamation-triangle text-warning me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa API này khỏi hệ thống'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả sản phẩm của API này'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả chuyên mục của API này'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa nhà cung cấp này'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa nhà cung cấp'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteCategoriesModal" tabindex="-1" aria-labelledby="confirmDeleteCategoriesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteCategoriesModalLabel">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <?= __('Xác nhận xóa chuyên mục và sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-exclamation-triangle text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả chuyên mục của nhà cung cấp này'); ?> (<span id="categoriesCount">0</span> <?= __('chuyên mục'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả sản phẩm của nhà cung cấp này'); ?> (<span id="productsCount">0</span> <?= __('sản phẩm'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-info-circle text-info me-2"></i>
                            <?= __('Giữ nguyên thông tin nhà cung cấp và đơn hàng'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmCategoriesCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmCategoriesCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa chuyên mục và sản phẩm'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteCategoriesButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục và sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteOnlyCategoriesModal" tabindex="-1" aria-labelledby="confirmDeleteOnlyCategoriesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteOnlyCategoriesModalLabel">
                    <i class="fa-solid fa-folder-minus me-2"></i>
                    <?= __('Xác nhận xóa chuyên mục'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả chuyên mục của nhà cung cấp này'); ?> (<span id="categoriesOnlyCount">0</span> <?= __('chuyên mục'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-info-circle text-info me-2"></i>
                            <?= __('Giữ nguyên sản phẩm hiện có nhưng sẽ không còn liên kết chuyên mục'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmCategoriesOnlyCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmCategoriesOnlyCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ chuyên mục'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteCategoriesOnlyButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteOnlyProductsModal" tabindex="-1" aria-labelledby="confirmDeleteOnlyProductsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteOnlyProductsModalLabel">
                    <i class="fa-solid fa-box-open me-2"></i>
                    <?= __('Xác nhận xóa sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả sản phẩm của nhà cung cấp này'); ?> (<span id="productsOnlyCount">0</span> <?= __('sản phẩm'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-info-circle text-info me-2"></i>
                            <?= __('Giữ nguyên cấu trúc chuyên mục để tái đồng bộ nếu cần'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmProductsOnlyCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmProductsOnlyCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ sản phẩm'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteProductsOnlyButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function removeCategoriesOnly(supplierId, categoriesCount) {
        $('#categoriesOnlyCount').text(categoriesCount);
        $('#confirmDeleteOnlyCategoriesModal').modal('show');
        $('#confirmCategoriesOnlyCheckbox').prop('checked', false);
        $('#confirmDeleteCategoriesOnlyButton').prop('disabled', true);

        $('#confirmDeleteCategoriesOnlyButton').off('click').on('click', function() {
            if ($('#confirmCategoriesOnlyCheckbox').prop('checked')) {
                $('#confirmDeleteCategoriesOnlyButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removeCategoriesOnly'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteCategoriesOnlyButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeleteCategoriesOnlyButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmCategoriesOnlyCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteCategoriesOnlyButton').prop('disabled', false);
            } else {
                $('#confirmDeleteCategoriesOnlyButton').prop('disabled', true);
            }
        });
    }

    function removeProductsOnly(supplierId, productsCount) {
        $('#productsOnlyCount').text(productsCount);
        $('#confirmDeleteOnlyProductsModal').modal('show');
        $('#confirmProductsOnlyCheckbox').prop('checked', false);
        $('#confirmDeleteProductsOnlyButton').prop('disabled', true);

        $('#confirmDeleteProductsOnlyButton').off('click').on('click', function() {
            if ($('#confirmProductsOnlyCheckbox').prop('checked')) {
                $('#confirmDeleteProductsOnlyButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removeProductsOnly'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteProductsOnlyButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeleteProductsOnlyButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmProductsOnlyCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteProductsOnlyButton').prop('disabled', false);
            } else {
                $('#confirmDeleteProductsOnlyButton').prop('disabled', true);
            }
        });
    }

    function removeCategoriesServices(supplierId, categoriesCount, productsCount) {
        // Cập nhật số lượng trong modal
        $('#categoriesCount').text(categoriesCount);
        $('#productsCount').text(productsCount);

        // Hiển thị modal
        $('#confirmDeleteCategoriesModal').modal('show');

        // Reset checkbox
        $('#confirmCategoriesCheckbox').prop('checked', false);
        $('#confirmDeleteCategoriesButton').prop('disabled', true);

        $('#confirmDeleteCategoriesButton').off('click').on('click', function() {
            if ($('#confirmCategoriesCheckbox').prop('checked')) {
                $('#confirmDeleteCategoriesButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removeCategoriesAndProducts'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteCategoriesButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục và sản phẩm'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeleteCategoriesButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục và sản phẩm'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmCategoriesCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteCategoriesButton').prop('disabled', false);
            } else {
                $('#confirmDeleteCategoriesButton').prop('disabled', true);
            }
        });
    }

    function removeItem(id) {
        $('#confirmDeleteModal').modal('show');

        $('#confirmDeleteButton').off('click').on('click', function() {
            if ($('#confirmCheckbox').prop('checked')) {
                $('#confirmDeleteButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: id,
                        action: 'removeSupplier'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa nhà cung cấp'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });

        $('#confirmCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteButton').prop('disabled', false);
            } else {
                $('#confirmDeleteButton').prop('disabled', true);
            }
        });
    }
</script>