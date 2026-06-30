<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Kho hàng sản phẩm').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_ctv.php');
if (isset($_GET['code'])) {
    $code = validate_alphanumeric($_GET['code'], 50);
    if ($code === false) {
        redirect(base_url_ctv('products'));
    }
} else {
    redirect(base_url_ctv('products'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

// Check if product belongs to this CTV
$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ? AND `user_id` = ? AND `pending` = 0", [$code, $getUser['id']]);
if (!$product) {
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền truy cập kho hàng này!').'")){window.history.back();}</script>');
}

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
$where = " `product_code` = ? ";
$where_params = [$code];
$create_gettime = '';
$uid = '';
$shortByDate  = '';
$user_id = '';
$username = '';
$account = '';

if(!empty($_GET['account'])){
    $account = validate_string($_GET['account'], 1000);
    if ($account !== false) {
        $where .= ' AND `account` LIKE ? ';
        $where_params[] = "%$account%";
    }
}
if(!empty($_GET['uid'])){
    $uid = validate_string($_GET['uid'], 255);
    if ($uid !== false) {
        $where .= ' AND `uid` LIKE ? ';
        $where_params[] = "%$uid%";
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
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
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
    }
}

$listDatatable = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE $where ORDER BY `id` DESC LIMIT ?, ?", array_merge($where_params, [$from, $limit]));
$totalDatatable = $CMSNT->num_rows_safe("SELECT * FROM `product_stock` WHERE $where ORDER BY id DESC", $where_params);
$urlDatatable = pagination(base_url_ctv("product-stock&limit=$limit&shortByDate=$shortByDate&code=$code&uid=$uid&create_gettime=$create_gettime&user_id=$user_id&username=$username&account=$account&"), $from, $totalDatatable, $limit);

if(isset($_POST['RemoveAccounts'])){
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Không được dùng chức năng này vì đây là trang web demo.').'")){window.history.back().location.reload();}</script>');
    }
    $value_remove = 0;
    $value_not_found = 0;
    if(empty($_POST['list_account_remove'])){
        die('<script type="text/javascript">if(!alert("'.__('Vui lòng nhập tài khoản cần xóa.').'")){window.history.back().location.reload();}</script>');
    }
    
    $list_account_remove = trim($_POST['list_account_remove']);
    
    // Xử lý nhiều loại delimiter cho textarea (Windows: \r\n, Unix: \n, Mac: \r)
    $list = preg_split('/\r\n|\r|\n/', $list_account_remove);
    
    foreach ($list as $account){
        $account = validate_string($account, 5000); // Loại bỏ khoảng trắng thừa
        
        if(empty($account)) continue; // Bỏ qua dòng trống
        
        // Kiểm tra delimiter: ưu tiên | > : > dấu cách
        if (strpos($account, '|') !== false) {
            $uid_remove = trim(explode('|', $account)[0]);
        } elseif (strpos($account, ':') !== false) {
            $uid_remove = trim(explode(':', $account)[0]);
        } else {
            $uid_remove = trim(explode(' ', $account)[0]);
        }
        
        // Kiểm tra UID có tồn tại trong database không
        $check_uid = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `uid` = ? AND `product_code` = ?", [$uid_remove, $code]);
        if(!$check_uid) {
            $value_not_found++;
            continue;
        }
        
        $isRemove = $CMSNT->remove("product_stock", " `uid` = ? AND `product_code` = ?", [$uid_remove, $code]);
        if ($isRemove) {
            $value_remove++;
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "CTV xóa tài khoản ($uid_remove) khỏi kho hàng đang bán"
            ]);
        }
    }
    
    $message = __('Xóa thành công')." [$value_remove] ".__('tài khoản').".";
    if($value_not_found > 0) {
        $message .= " ".__('Không tìm thấy')." [$value_not_found] ".__('tài khoản').".";
    }
    
    die('<script type="text/javascript">if(!alert("'.$message.'")){window.history.back().location.reload();}</script>');
}

if(isset($_POST['AddAccounts'])){
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Không được dùng chức năng này vì đây là trang web demo.').'")){window.history.back().location.reload();}</script>');
    }
    $value_add = 0;
    $value_update = 0;
    $value_skip_stock = 0;
    $value_skip_sold = 0;
    $value_total = 0;
    $value_skip_invalid = 0;
    $list = [];
    
    if(empty($_POST['accounts'])){
        die('<script type="text/javascript">if(!alert("'.__('Vui lòng nhập tài khoản cần thêm.').'")){window.history.back().location.reload();}</script>');
    }
    $accounts = trim($_POST['accounts']);
    
    // Xử lý nhiều loại delimiter cho textarea (Windows: \r\n, Unix: \n, Mac: \r)
    $list = preg_split('/\r\n|\r|\n/', $accounts);

    // Giới hạn số dòng để tránh abuse
    $max_lines = 5000;
    if (count($list) > $max_lines) {
        die('<script type="text/javascript">if(!alert("'.sprintf(__('Vượt quá số dòng cho phép (%s dòng tối đa).'), $max_lines).'")){window.history.back().location.reload();}</script>');
    }
    
    // Lọc ra những dòng rỗng
    $list = array_filter($list, function($line) {
        return trim($line) !== '';
    });
    
    foreach ($list as $account){
        $account = trim($account); // Loại bỏ khoảng trắng thừa

        // Bỏ qua các dòng quá dài để tránh tấn công DoS qua input lớn
        if (mb_strlen($account) > 5000) {
            $value_skip_invalid++;
            continue;
        }
        
        // Kiểm tra delimiter: ưu tiên | > : > dấu cách
        if (strpos($account, '|') !== false) {
            $uid = trim(explode('|', $account)[0]);
        } elseif (strpos($account, ':') !== false) {
            $uid = trim(explode(':', $account)[0]);
        } else {
            $uid = trim(explode(' ', $account)[0]);
        }
        // Validate bằng helper (chuẩn hóa và giới hạn độ dài)
        $uid = validate_string($uid, 5000, 1);
        if ($uid === false) {
            $value_skip_invalid++;
            continue;
        }
        $account_validated = validate_string($account, 5000, 1);
        if ($account_validated === false) {
            $value_skip_invalid++;
            continue;
        }
        $account = $account_validated;
        $value_total++;
        
        // Kiểm tra lọc trùng UID tài khoản đã bán
        if (isset($_POST['loc_trung_uid_sold']) && $_POST['loc_trung_uid_sold'] == 1){
            if($CMSNT->get_row_safe("SELECT * FROM `product_sold` WHERE `uid` = ?", [$uid])){
                $value_skip_sold++;
                continue;
            }
        }
        
        if (isset($_POST['loc_trung_uid']) && $_POST['loc_trung_uid'] == 1){
            if($CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `uid` = ? AND `product_code` = ?", [$uid, $code])){
                $isUpdate = $CMSNT->update("product_stock", [
                    'product_code'  => $code,
                    'seller'        => $getUser['id'],
                    'uid'           => $uid,
                    'account'       => $account,
                    'create_gettime'   => gettime()
                ], " `uid` = ? AND `product_code` = ?", [$uid, $code]);
                if ($isUpdate) {
                    $value_update++;
                }
            }else{
                $isAdd = $CMSNT->insert("product_stock", [
                    'product_code'  => $code,
                    'seller'        => $getUser['id'],
                    'uid'           => $uid,
                    'account'       => $account,
                    'create_gettime'   => gettime()
                ]);
                if ($isAdd) {
                    $value_add++;
                }
            }
        }
        else{
            $isAdd = $CMSNT->insert("product_stock", [
                'product_code'  => $code,
                'seller'        => $getUser['id'],
                'uid'           => $uid,
                'account'       => $account,
                'create_gettime'   => gettime()
            ]);
            if ($isAdd) {
                $value_add++;
            }  
        }
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => "CTV import $value_add tài khoản vào kho hàng $code"
    ]);
    
    $notification = "═══════════════════════════════════════\n";
    $notification .= "🎉 KẾT QUẢ UPLOAD TÀI KHOẢN 🎉\n";
    $notification .= "═══════════════════════════════════════\n\n";
    $notification .= "📊 THỐNG KÊ CHI TIẾT:\n";
    $notification .= "▫️ Tổng số tài khoản xử lý: " . $value_total . "\n\n";
    $notification .= "✅ Thêm mới thành công: " . $value_add . " tài khoản\n";
    $notification .= "🔄 Cập nhật thành công: " . $value_update . " tài khoản\n";
    
    if ($value_skip_stock > 0) {
        $notification .= "⚠️ Bỏ qua (trùng trong kho): " . $value_skip_stock . " tài khoản\n";
    }
    if ($value_skip_sold > 0) {
        $notification .= "🚫 Bỏ qua (đã bán): " . $value_skip_sold . " tài khoản\n";
    }
    if ($value_skip_invalid > 0) {
        $notification .= "❌ Bỏ qua (dữ liệu không hợp lệ/quá dài): " . $value_skip_invalid . " tài khoản\n";
    }
    
    $notification .= "\n═══════════════════════════════════════\n";
    $notification .= "💼 Kho hàng: " . $code . "\n";
    $notification .= "⏰ Thời gian: " . date('d/m/Y H:i:s') . "\n";
    $notification .= "═══════════════════════════════════════";
    
    die('<script type="text/javascript">if(!alert("' . str_replace("\n", "\\n", $notification) . '")){window.history.back().location.reload();}</script>');
}

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <a href="<?=base_url_ctv('products');?>" class="btn btn-dark btn-sm me-2">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <?=__('Quản lý kho hàng');?> "<b style="color:red;"><?=$code;?></b>"
            </h1>
        </div>
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('THÔNG TIN SẢN PHẨM');?>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive mb-2">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th><?=__('Sản phẩm');?></th>
                                        <th class="text-center"><?=__('Chuyên mục');?></th>
                                        <th class="text-center"><?=__('Giá bán');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?=$product['name'];?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?=getRowRealtime('categories', $product['category_id'], 'name');?></span>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge bg-danger"><?=format_currency($product['price']);?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($product['pending'] == 1): ?>
                                                <span class="badge bg-warning"><?=__('Chờ duyệt');?></span>
                                            <?php elseif($product['status'] == 1): ?>
                                                <span class="badge bg-success"><?=__('Đang bán');?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?=__('Tạm ẩn');?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('NHẬP TÀI KHOẢN VÀO KHO HÀNG');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-12 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#nhap_nhieu_tai_khoan"
                                    class="btn btn-outline-primary btn-w-lg btn-wave mb-2">
                                    <i class="fa-solid fa-folder"></i> <?=__('Nhập nhiều tài khoản');?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('XÓA TÀI KHOẢN KHỎI KHO HÀNG');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_nhieu_tai_khoan"
                                    class="btn btn-danger btn-w-lg btn-wave mb-2">
                                    <i class="fa-solid fa-trash-can"></i> <?=__('Xóa nhiều tài khoản');?>
                                </button> 
                            </div>
                            <div class="col-xl-6 d-grid gap-2">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_toan_bo_tai_khoan"
                                    class="btn btn-danger-gradient btn-wave mb-2">
                                    <i class="fa-solid fa-trash"></i> <?=__('Xóa toàn bộ tài khoản đang bán');?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('TÀI KHOẢN');?> <strong style="color:green;">LIVE</strong> <?=__('ĐANG BÁN');?>
                        </div>
                        <div class="btn-list">
                            <button type="button" onclick="viewListLIVE(`<?=$code;?>`)" id="btn_viewListLIVE"
                                class="btn btn-success btn-sm my-1 me-2">
                                <i class="fa-solid fa-copy"></i> <?=__('TÀI KHOẢN LIVE');?>
                                <span class="badge ms-2 bg-dark text-white"><?=format_cash($totalDatatable);?></span>
                            </button>
                            <button type="button" onclick="viewListDIE(`<?=$code;?>`)" id="btn_viewListDIE"
                                class="btn btn-danger btn-sm my-1 me-2">
                                <i class="fa-solid fa-copy"></i> <?=__('TÀI KHOẢN DIE');?>
                                <span class="badge ms-2 bg-dark text-white"><?=format_cash($CMSNT->get_row_safe("SELECT COUNT(id) as total FROM `product_die` WHERE `product_code` = ?", [$code])['total']);?></span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="ctv">
                                <input type="hidden" name="action" value="product-stock">
                                <input type="hidden" name="code" value="<?=$code;?>">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$uid;?>" name="uid"
                                        placeholder="<?=__('UID');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$account;?>" name="account"
                                        placeholder="<?=__('Tài khoản');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary">
                                        <i class="fa fa-search"></i> <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_ctv('product-stock&code='.$code);?>">
                                        <i class="fa fa-trash"></i> <?=__('Clear filter');?>
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
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1.000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?></option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?></option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?></option>
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
                                        <th class="text-center"><?=__('UID');?></th>
                                        <th class="text-center"><?=__('Tài khoản');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Check live gần nhất');?></th>
                                        <th class="text-center"><?=__('Type');?></th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox_product_stock"
                                                    data-id="<?=$row['id'];?>" data-checkbox="<?=$row['account'];?>"
                                                    name="checkbox_product_stock" value="<?=$row['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <strong><?=$row['uid'];?></strong>
                                        </td>
                                        <td class="text-center">
                                            <textarea rows="1" class="form-control"><?=$row['account'];?></textarea>
                                        </td>
                                        <td class="text-center">
                                            <small data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['create_gettime']));?>"><?=$row['create_gettime'];?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-dark text-white" data-toggle="tooltip" data-placement="bottom"
                                                title="<?=date("H:i:s d-m-Y", $row['time_check_live']);?>"><?=timeAgo($row['time_check_live']);?></span>
                                        </td> 
                                        <td class="text-center"><b><?=$row['type'];?></b></td>
                                        <td class="text-center">
                                            <a type="button" onclick="removeAccount('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Xóa');?>">
                                                <i class="fas fa-trash"></i> <?=__('Delete');?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="7">
                                        <div class="btn-list">
                                            <button type="button" onclick="exportDataTXT()" id="exportDataTXT"
                                                class="btn btn-outline-primary shadow-primary btn-wave btn-sm">
                                                <i class="fa-solid fa-file-export"></i> <?=__('XUẤT TỆP .TXT');?>
                                            </button>
                                            <button type="button" onclick="exportDataClipboard()" id="exportDataClipboard"
                                                class="btn btn-outline-success shadow-success btn-wave btn-sm">
                                                <i class="fa-solid fa-copy"></i> <?=__('COPY');?>
                                            </button>
                                            <button type="button" onclick="exportUIDClipboard()" id="exportUIDClipboard"
                                                class="btn btn-outline-info shadow-info btn-wave btn-sm">
                                                <i class="fa-regular fa-copy"></i> <?=__('COPY UID');?>
                                            </button>
                                            <button type="button" onclick="confirmDeleteAccount()" id="confirmDeleteAccount"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm">
                                                <i class="fa-solid fa-trash"></i> <?=__('DELETE');?>
                                            </button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?=__('Showing');?> <?=format_cash($limit);?> <?=__('of');?> <?=format_cash($totalDatatable);?> <?=__('Results');?></p>
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

<!-- Modal nhập nhiều tài khoản -->
<div class="modal fade" id="nhap_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_nhap_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="h6_nhap_nhieu_tai_khoan"><?=__('NHẬP NHIỀU TÀI KHOẢN VÀO KHO HÀNG');?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="text-area" class="form-label"><?=__('Tài khoản cần thêm: (1 dòng 1 tài khoản)');?></label>
                        <textarea class="form-control" name="accounts" id="accounts" placeholder="UID|PASS|...
UID|PASS|...
UID|PASS|...
UID|PASS|..." rows="5" required></textarea>
                        <small><?=__('Nhấn Submit để thêm');?> <strong style="color: red;" id="countAdd">0</strong> <?=__('tài khoản');?></small>
                    </div>
                    <div class="form-check form-check-md d-flex align-items-center">
                        <input class="form-check-input" type="checkbox" value="1" id="a9895w22" name="loc_trung_uid">
                        <label class="form-check-label" for="a9895w22">
                            <?=__('Lọc trùng UID tài khoản đang bán');?>
                        </label>
                    </div>
                    <div class="form-check form-check-md d-flex align-items-center">
                        <input class="form-check-input" type="checkbox" value="1" id="a9895w22_sold" name="loc_trung_uid_sold">
                        <label class="form-check-label" for="a9895w22_sold">
                            <?=__('Lọc trùng UID tài khoản đã bán');?>
                        </label>
                    </div>
                    <small><?=__('Tắt lọc trùng UID sẽ giúp tăng tốc độ tải sản phẩm lên.');?></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"><?=__('Close');?></button>
                    <button type="submit" name="AddAccounts" class="btn btn-primary btn-sm">
                        <i class="fa fa-fw fa-plus me-1"></i> <?=__('Submit');?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xóa nhiều tài khoản -->
<div class="modal fade" id="xoa_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="h6_xoa_nhieu_tai_khoan"><?=__('XÓA NHIỀU TÀI KHOẢN');?> <strong style="color:green;">LIVE</strong> <?=__('ĐANG BÁN');?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="text-area" class="form-label"><?=__('Tài khoản cần xóa: (1 dòng 1 tài khoản)');?></label>
                        <textarea class="form-control" name="list_account_remove" id="accounts_remove" placeholder="UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID" rows="5" required></textarea>
                        <small><?=__('Nhấn Submit để xóa');?> <strong style="color: red;" id="countRemove">0</strong> <?=__('tài khoản');?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"><?=__('Close');?></button>
                    <button type="submit" name="RemoveAccounts" class="btn btn-primary btn-sm">
                        <i class="fa fa-fw fa-trash me-1"></i> <?=__('Submit');?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem danh sách LIVE -->
<div class="modal fade" id="viewListLIVE" tabindex="-1" aria-labelledby="viewListLIVE" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewListLIVE"><?=__('DANH SÁCH TÀI KHOẢN');?> <strong style="color:green;">LIVE</strong>
                    <?=__('CỦA KHO HÀNG');?> <strong style="color:red;"><?=$code;?></strong>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="coypyBox_viewListLIVE" readonly rows="10"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox_viewListLIVE"
                    class="btn btn-info shadow-info btn-wave copy"><?=__('Copy');?></button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal"><?=__('Đóng');?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem danh sách DIE -->
<div class="modal fade" id="viewListDIE" tabindex="-1" aria-labelledby="viewListDIE" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewListDIE"><?=__('DANH SÁCH TÀI KHOẢN');?> <strong style="color:red;">DIE</strong> <?=__('CỦA');?>
                    <?=__('KHO HÀNG');?> <strong style="color:red;"><?=$code;?></strong>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control mb-2" id="coypyBox_viewListDIE" readonly rows="10"></textarea>
                <button type="button" id="btn_format_list_die" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash"></i> <?=__('Xóa toàn bộ');?>
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox_viewListDIE"
                    class="btn btn-info shadow-info btn-wave copy"><?=__('Copy');?></button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal"><?=__('Đóng');?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xóa toàn bộ tài khoản -->
<div class="modal fade" id="xoa_toan_bo_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_toan_bo_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="h6_xoa_toan_bo_tai_khoan"><i class="fa-solid fa-triangle-exclamation"></i> <?=__('XÓA TOÀN BỘ TÀI KHOẢN ĐANG BÁN');?>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?=__('Hệ thống sẽ thực hiện XÓA TOÀN BỘ tài khoản đang bán của kho hàng');?> <b><?=$code;?></b> <?=__('nếu bạn xác nhận vào Input dưới đây.');?></p>
                    <p><?=__('Để xác nhận XÓA TOÀN BỘ tài khoản trong kho hàng');?> <b><?=$code;?></b>, <?=__('vui lòng nhập vào ô dưới đây nội dung là');?> <b style="color:red;font-size:15px;">toi dong y</b> <?=__('để tiến hành xóa.');?></p>
                    <input class="form-control" type="text" id="confirm_empty_list_account" placeholder="<?=__('Nhập nội dung toi dong y nếu bạn chắc chắn đã hiểu nội dung trên');?>"> 
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"><?=__('Đóng');?></button>
                    <button type="button" id="btn_format_list_account" class="btn btn-primary btn-sm"><i
                            class="fa fa-fw fa-trash me-1"></i> <?=__('Xóa ngay');?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Count accounts in textarea
document.addEventListener("DOMContentLoaded", function() {
    var textarea = document.getElementById('accounts');
    var countAdd = document.getElementById("countAdd");

    if (textarea && countAdd) {
        textarea.addEventListener("input", function() {
            var lines = textarea.value.split('\n');
            var nonEmptyLinesCount = lines.filter(function(line) {
                return line.trim().length > 0;
            }).length;
            countAdd.innerText = nonEmptyLinesCount;
        });
    }
    
    var textareaRemove = document.getElementById('accounts_remove');
    var countRemove = document.getElementById("countRemove");

    if (textareaRemove && countRemove) {
        textareaRemove.addEventListener("input", function() {
            var lines = textareaRemove.value.split('\n');
            var nonEmptyLinesCount = lines.filter(function(line) {
                return line.trim().length > 0;
            }).length;
            countRemove.innerText = nonEmptyLinesCount;
        });
    }
});

// Remove account function
function postRemoveAccount(id) {
    $.ajax({
        url: "<?=base_url('ajaxs/ctv/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeAccountStock',
            id: id,
            token: '<?=$getUser['token'];?>'
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
    Swal.fire({
        title: '<?=__('Xác nhận xóa tài khoản');?>',
        text: '<?=__('Bạn có chắc chắn muốn xóa tài khoản này không?');?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?=__('Đồng ý');?>',
        cancelButtonText: '<?=__('Hủy bỏ');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            postRemoveAccount(id);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

// View list LIVE
function viewListLIVE(code) {
    var originalButtonContent = $('#btn_viewListLIVE').html();
    $('#btn_viewListLIVE').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
        .prop('disabled', true);
    $.ajax({
        url: "<?=base_url('ajaxs/ctv/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'view_product_live',
            token: '<?=$getUser['token'];?>',
            code: code
        },
        success: function(result) {
            $('#viewListLIVE').modal('show');
            $('#coypyBox_viewListLIVE').val(result.accounts);
            $('#btn_viewListLIVE').html(originalButtonContent).prop('disabled', false);
        },
        error: function() {
            alert('<?=__('Có lỗi xảy ra');?>');
            location.reload();
        }
    });
}

// View list DIE
function viewListDIE(code) {
    var originalButtonContent = $('#btn_viewListDIE').html();
    $('#btn_viewListDIE').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
        .prop('disabled', true);
    $.ajax({
        url: "<?=base_url('ajaxs/ctv/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'view_product_die',
            token: '<?=$getUser['token'];?>',
            code: code
        },
        success: function(result) {
            $('#viewListDIE').modal('show');
            $('#coypyBox_viewListDIE').val(result.accounts);
            $('#btn_viewListDIE').html(originalButtonContent).prop('disabled', false);
        },
        error: function() {
            alert('<?=__('Có lỗi xảy ra');?>');
            location.reload();
        }
    });
}

// Checkbox functions
$(function() {
    $('#check_all_checkbox_product_stock').on('click', function() {
        $('.checkbox_product_stock').prop('checked', this.checked);
    });
    $('.checkbox_product_stock').on('click', function() {
        $('#check_all_checkbox_product_stock').prop('checked', $('.checkbox_product_stock:checked')
            .length === $('.checkbox_product_stock').length);
    });
});

// Export functions
function exportDataTXT() {
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');
    if (checkboxes.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một bản ghi');?>', 'error');
        return;
    }
    $('#exportDataTXT').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
        .prop('disabled', true);
    
    var selectedData = [];
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
    
    var blob = new Blob([dataString], { type: 'text/plain' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '<?=$code;?>_' + numberOfData + '_' + timestamp + '.txt';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    $('#exportDataTXT').html('<i class="fa-solid fa-file-export"></i> <?=__('XUẤT TỆP .TXT');?>')
        .prop('disabled', false);
}

function exportDataClipboard() {
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');
    if (checkboxes.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một bản ghi');?>', 'error');
        return;
    }
    $('#exportDataClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
        .prop('disabled', true);
    
    var selectedData = [];
    checkboxes.forEach(function(checkbox) {
        selectedData.push(checkbox.getAttribute('data-checkbox').trim());
    });
    
    var dataString = selectedData.join('\n');
    
    navigator.clipboard.writeText(dataString).then(function() {
        showMessage("<?=__('Nội dung đã được sao chép vào clipboard!');?>", 'success');
        $('#exportDataClipboard').html('<i class="fa-solid fa-copy"></i> <?=__('COPY');?>')
            .prop('disabled', false);
    }).catch(function(error) {
        $('#exportDataClipboard').html('<i class="fa-solid fa-copy"></i> <?=__('COPY');?>')
            .prop('disabled', false);
        alert('<?=__('Có lỗi xảy ra trong quá trình sao chép');?>: ' + error);
    });
}

function exportUIDClipboard() {
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');
    if (checkboxes.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một bản ghi');?>', 'error');
        return;
    }
    $('#exportUIDClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
        .prop('disabled', true);
    
    var selectedData = [];
    checkboxes.forEach(function(checkbox) {
        var fullData = checkbox.getAttribute('data-checkbox').trim();
        var splitData = fullData.split('|');
        if (splitData.length > 0) {
            selectedData.push(splitData[0]);
        }
    });
    
    var dataString = selectedData.join('\n');
    
    navigator.clipboard.writeText(dataString).then(function() {
        showMessage("<?=__('Nội dung đã được sao chép vào clipboard!');?>", 'success');
        $('#exportUIDClipboard').html('<i class="fa-regular fa-copy"></i> <?=__('COPY UID');?>')
            .prop('disabled', false);
    }).catch(function(error) {
        alert('<?=__('Có lỗi xảy ra trong quá trình sao chép');?>: ' + error);
    });
}

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
        showMessage('<?=__('Vui lòng chọn ít nhất một bản ghi');?>', 'error');
        return;
    }
    
    Swal.fire({
        title: '<?=__('Xác nhận xóa');?>',
        text: '<?=__('Bạn có đồng ý xóa các bản ghi đã chọn không?');?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?=__('Đồng ý');?>',
        cancelButtonText: '<?=__('Hủy bỏ');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#confirmDeleteAccount').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?></span>')
                .prop('disabled', true);

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
    });
}

// Copy function
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}

// Xóa toàn bộ tài khoản DIE
$("#btn_format_list_die").click(function() {
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa vĩnh viễn toàn bộ dữ liệu tài khoản DIE của kho hàng');?> <?=$code;?> <?=__('khi bạn nhấn Đồng Ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=base_url('ajaxs/ctv/remove.php');?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'empty_list_die',
                    token: '<?=$getUser['token'];?>',
                    id: '<?=$code;?>'
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
                    alert('<?=__('Có lỗi xảy ra');?>');
                    location.reload();
                }
            });
        }
    });
});

$("#btn_format_list_account").click(function() {
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa vĩnh viễn toàn bộ dữ liệu tài khoản đang bán của kho hàng');?> <?=$code;?> <?=__('khi bạn nhấn Đồng Ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=base_url('ajaxs/ctv/remove.php');?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'empty_list_account_stock',
                    token: '<?=$getUser['token'];?>',
                    confirm_empty_list_account: $('#confirm_empty_list_account').val(),
                    id: '<?=$code;?>'
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
                    alert('<?=__('Có lỗi xảy ra');?>');
                    location.reload();
                }
            });
        }
    });
});
</script>
