<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'CTV Statistics',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
// Kiểm tra giấy phép addon
$checkKey = checkAddonLicense($CMSNT->site('ctv_panel_license'), 'SHOPCLONE7_CTVPANEL');

if(checkPermission($getUser['admin'], 'view_statistics_ctv') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}

if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;

$where = " `trash` = 0 ";
$user_id = '';
$username = '';
$trans_id = '';
$time = '';
$shortByDate = '';

// Lọc theo mã GD
if(!empty($_GET['trans_id'])){
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` = "'.$trans_id.'" ';
}
// Lọc theo ID user (CTV seller)
if(!empty($_GET['user_id'])){
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `seller` = "'.$user_id.'" ';
}
// Lọc theo username (CTV seller)
if(!empty($_GET['username'])){
    $username = check_string($_GET['username']);
    if($u = $CMSNT->get_row("SELECT id FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `seller` = "'.$u['id'].'" ';
    }else{
        // không có user -> trả về rỗng
        $where .= ' AND `seller` = "0" ';
    }
}
// Lọc theo thời gian
if(!empty($_GET['time'])){
    $time = check_string($_GET['time']);
    $create_gettime_1 = str_replace('-', '/', $time);
    $create_gettime_1 = explode(' to ', $create_gettime_1);
    if($create_gettime_1[0] != $create_gettime_1[1]){
        $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_gettime_1[0]."' AND `create_gettime` <= '".$create_gettime_1[1]."' ";
    }
}
// Lọc nhanh theo ngày
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
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

// Lấy danh sách đơn hàng thuộc CTV (có seller > 0)
$listDatatable = $CMSNT->get_list(" SELECT * FROM `product_order` WHERE $where AND `seller` > 0 ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE $where AND `seller` > 0 ");
$urlDatatable = pagination(base_url_admin("ctv-statistics&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&time=$time&username=$username&trans_id=$trans_id&"), $from, $totalDatatable, $limit);

// KPI
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");

$kpi_total_revenue = $CMSNT->get_row("SELECT SUM(pay) FROM `product_order` WHERE `trash` = 0 AND `seller` > 0 ")["SUM(pay)"];
$kpi_total_orders = $CMSNT->get_row("SELECT COUNT(*) FROM `product_order` WHERE `trash` = 0 AND `seller` > 0 ")["COUNT(*)"];
$kpi_month_revenue = $CMSNT->get_row("SELECT SUM(pay) FROM `product_order` WHERE `trash` = 0 AND `seller` > 0 AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ")["SUM(pay)"];
$kpi_today_revenue = $CMSNT->get_row("SELECT SUM(pay) FROM `product_order` WHERE `trash` = 0 AND `seller` > 0 AND `create_gettime` LIKE '%$currentDate%' ")["SUM(pay)"];

?>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">CTV Statistics</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">CTV Panel</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Statistics</li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if($CMSNT->site('cong_tien_nguoi_ban') != 1):?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3"
                    role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Bạn cần bật tính năng "Cộng tiền người bán" trong cài đặt hệ thống trước khi có thể sử dụng CTV
                    Panel.
                    <br><small class="text-muted">Vui lòng vào <strong>Cài đặt → Thiết lập chung</strong> và bật tùy
                        chọn "Cộng tiền người bán" để kích hoạt CTV Panel.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            </div>
        </div>
        <?php endif?>
        
        <?php if(!column_exists('products', 'pending')):?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3"
                    role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Cột <strong>pending</strong> không tồn tại trong bảng <strong>products</strong>.
                    <br><small class="text-muted">Có thể bạn đang sử dụng phiên bản cũ của SHOPCLONE7. Vui lòng cập nhật lên phiên bản mới nhất hoặc thêm cột pending vào bảng products để sử dụng đầy đủ tính năng.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            </div>
        </div>
        <?php endif?>
        
        <?php if($checkKey['status'] != true):?>
        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Giấy phép không hợp lệ!</h4>
                            <p>Bạn cần phải mua giấy phép kích hoạt Addon CTV Panel để sử dụng tính năng này.</p>
                            <hr>
                            <p class="mb-0">
                                Vui lòng truy cập <a href="<?=base_url_admin('ctv-config');?>" class="alert-link">Cấu hình CTV Panel</a> để nhập giấy phép hoặc 
                                <a href="https://client.cmsnt.co/store/license-source-code/addon-ctv-panel-shopclone-v7" target="_blank" class="alert-link">mua giấy phép</a> nếu bạn chưa có.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif($CMSNT->site('ctv_status') != 1):?>
            
        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Tính năng đang tắt!</h4>
                            <p>CTV Panel hiện đang bị tắt trong cấu hình hệ thống.</p>
                            <hr>
                            <p class="mb-0">
                                Vui lòng truy cập <a href="<?=base_url_admin('ctv-config');?>" class="alert-link">Cấu hình CTV Panel</a> để bật tính năng này.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else:?>
        <div class="row">
            <div class="col-xl-3">
                <div class="card custom-card"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-fill">
                    <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($kpi_total_revenue);?></p>
                    <p class="mb-0 text-muted">Tổng doanh thu CTV</p>
                </div><div class="ms-2"><span class="avatar text-bg-primary rounded-circle fs-20"><i class='bx bx-dollar'></i></span></div></div></div></div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-fill">
                    <p class="mb-1 fs-5 fw-semibold text-default"><?=format_cash($kpi_total_orders);?></p>
                    <p class="mb-0 text-muted">Tổng số đơn hàng</p>
                </div><div class="ms-2"><span class="avatar text-bg-info rounded-circle fs-20"><i class='bx bx-cart'></i></span></div></div></div></div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-fill">
                    <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($kpi_month_revenue);?></p>
                    <p class="mb-0 text-muted">Doanh thu tháng <?=date('m');?></p>
                </div><div class="ms-2"><span class="avatar text-bg-warning rounded-circle fs-20"><i class='bx bx-trending-up'></i></span></div></div></div></div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-fill">
                    <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($kpi_today_revenue);?></p>
                    <p class="mb-0 text-muted">Doanh thu hôm nay</p>
                </div><div class="ms-2"><span class="avatar text-bg-danger rounded-circle fs-20"><i class='bx bx-time-five'></i></span></div></div></div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">ĐƠN HÀNG BÁN BỞI CTV</div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="ctv-statistics">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id" placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username" placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$trans_id;?>" name="trans_id" placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="time" class="form-control form-control-sm" id="daterange" value="<?=$time;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i> <?=__('Search');?></button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('ctv-statistics');?>"><i class="fa fa-trash"></i> <?=__('Clear filter');?></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
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
                                    <select name="shortByDate" onchange="this.form.submit()" class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?></option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?></option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?></option>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead class="table">
                                    <tr>
                                        <th><?=__('Mã giao dịch');?></th>
                                        <th><?=__('Bên bán');?></th>
                                        <th><?=__('Bên mua');?></th>
                                        <th class="text-end"><?=__('Số lượng');?></th>
                                        <th class="text-end"><?=__('Giá trị đơn (pay)');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th><?=__('IP');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['trans_id'];?></td>
                                        <td><a class="text-primary" href="<?=base_url_admin('user-edit&id='.$row['seller']);?>"><?=getRowRealtime('users', $row['seller'], 'username');?> [ID <?=$row['seller'];?>]</a></td>
                                        <td><a class="text-primary" href="<?=base_url_admin('user-edit&id='.$row['buyer']);?>"><?=getRowRealtime('users', $row['buyer'], 'username');?> [ID <?=$row['buyer'];?>]</a></td>
                                        <td class="text-end"><?=format_cash($row['amount']);?></td>
                                        <td class="text-end"><span class="badge bg-primary-gradient"><?=format_currency($row['pay']);?></span></td>
                                        <td class="text-center">
                                            <strong data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['create_gettime']));?>"><?=$row['create_gettime'];?></strong>
                                        </td>
                                        <td><?=$row['ip'];?></td>
                                    </tr>
                                    <?php endforeach?>
                                    <?php if($totalDatatable == 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="empty-state">
                                                <i class="ri-inbox-line fs-48 text-muted"></i>
                                                <p class="text-muted mt-2"><?=__('Không có dữ liệu');?></p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?> Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>
