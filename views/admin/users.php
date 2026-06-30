<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Users'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
if(checkPermission($getUser['admin'], 'view_user') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
    use PragmaRX\Google2FAQRCode\Google2FA;
    if(isset($_POST['AddUser'])){
        if (empty($_POST['username'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập username")){window.history.back().location.reload();}</script>');
    }
    $username = check_string($_POST['username']);
    if (validateUsername($username) != true) {
        die('<script type="text/javascript">if(!alert("Username không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    if (empty($_POST['email'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập địa chỉ Email")){window.history.back().location.reload();}</script>');
    }
    $email = check_string($_POST['email']);
    if (validateEmail($email) != true) {
        die('<script type="text/javascript">if(!alert("Định dạng Email không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->num_rows("SELECT * FROM `users` WHERE `username` = '$username' ") > 0) {
        die('<script type="text/javascript">if(!alert("Tên đăng nhập đã tồn tại trong hệ thống")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->num_rows("SELECT * FROM `users` WHERE `email` = '$email' ") > 0) {
        die('<script type="text/javascript">if(!alert("Địa chỉ email đã tồn tại trong hệ thống")){window.history.back().location.reload();}</script>');
    }
    $google2fa = new Google2FA();    
    $password_result = validate_password($_POST['password'] ?? $_POST['username'], 6, 50);
    if (!$password_result['success']) {
        die('<script type="text/javascript">if(!alert("'.__('Mật khẩu không hợp lệ: ') . $password_result['message'].'")){window.history.back().location.reload();}</script>');
    }
    $isInsert = $CMSNT->insert('users', [
        'username'  => check_string($_POST['username']),
        'password'  => TypePassword($password_result['password']),
        'email'     => check_string($_POST['email']),
        'create_date'   => gettime(),
        'update_date'   => gettime(),
        'token'         => md5(random('qwertyuiopasddfghjklzxcvbnm1234567890', 6).time()),
        'money'         => 0,
        'api_key'       => md5(time().random('QWERTYUIOPASDFGHJKL', 6)),
        'SecretKey_2fa' => $google2fa->generateSecretKey()
    ]);
    
    if($isInsert){
        admin_msg_success("Thêm user thành công!", "", 1000);
    }
}



$users = $CMSNT->get_list("SELECT * FROM `users` ORDER BY id DESC  ");

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
$where = ' `id` > 0 ';
$order_by = ' ORDER BY id DESC ';
$username = '';
$name = '';
$email = '';
$phone = '';
$status = '';
$role = '';
$money = '';
$discount = '';
$ip = '';
$id = '';
$shortByDate  = '';
$utm_source = '';
$total_money = '';

if(!empty($_GET['utm_source'])){
    $utm_source = check_string($_GET['utm_source']);
    $where .= ' AND `utm_source` LIKE "%'.$utm_source.'%" ';
}
if(!empty($_GET['id'])){
    $id = check_string($_GET['id']);
    $where .= ' AND `id` = '.$id.' ';
}
if(!empty($_GET['username'])){
    $username = check_string($_GET['username']);
    $where .= ' AND `username` LIKE "%'.$username.'%" ';
}
if(!empty($_GET['name'])){
    $name = check_string($_GET['name']);
    $where .= ' AND `fullname` LIKE "%'.$name.'%" ';
}
if(!empty($_GET['email'])){
    $email = check_string($_GET['email']);
    $where .= ' AND `email` LIKE "%'.$email.'%" ';
}
if(!empty($_GET['phone'])){
    $phone = check_string($_GET['phone']);
    $where .= ' AND `phone` LIKE "%'.$phone.'%" ';
}
if(!empty($_GET['status'])){
    $status = check_string($_GET['status']);
    if($status == 1){
        $where .= ' AND `banned` = 0 ';
    }else if($status == 2){
        $where .= ' AND `banned` = 1 ';
    }
}
if(!empty($_GET['role'])){
    $role = check_string($_GET['role']);
    if($role == 1){
        $where .= ' AND `ctv` = 1 ';
    }else if($role == 2){
        $where .= ' AND `admin` != 0 ';
    }
}
if(!empty($_GET['money'])){
    $money = check_string($_GET['money']);
    if($money == 1){
        $order_by = ' ORDER BY `money` ASC ';
    }else if($money == 2){
        $order_by = ' ORDER BY `money` DESC ';
    }
}
if(!empty($_GET['total_money'])){
    $total_money = check_string($_GET['total_money']);
    if($total_money == 1){
        $order_by = ' ORDER BY `total_money` ASC ';
    }else if($total_money == 2){
        $order_by = ' ORDER BY `total_money` DESC ';
    }
}
if(!empty($_GET['discount'])){
    $discount = check_string($_GET['discount']);
    if($discount == 1){
        $order_by = ' ORDER BY `discount` ASC ';
    }else if($discount == 2){
        $order_by = ' ORDER BY `discount` DESC ';
    }
}
if(!empty($_GET['ip'])){
    $ip = check_string($_GET['ip']);
    $where .= ' AND `ip` LIKE "%'.$ip.'%" ';
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `create_date` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(create_date) = $currentYear AND WEEK(create_date, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(create_date) = '$currentMonth' AND YEAR(create_date) = '$currentYear' ";
    }
    if($shortByDate == 4){
        $where .= " AND DATE(create_date) = '$yesterday' ";
    }
}


$listDatatable = $CMSNT->get_list("SELECT * FROM `users` WHERE $where $order_by LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `users` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("users&limit=$limit&shortByDate=$shortByDate&username=$username&name=$name&email=$email&phone=$phone&status=$status&role=$role&money=$money&ip=$ip&id=$id&utm_source=$utm_source&discount=$discount&total_money=$total_money&"), $from, $totalDatatable, $limit);



?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-users"></i> Users</h1>
        </div>
        <div class="row">
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-primary">
                                    <svg class="svg-white" xmlns="http://www.w3.org/2000/svg"
                                        enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px"
                                        fill="#000000">
                                        <rect fill="none" height="24" width="24"></rect>
                                        <g>
                                            <path
                                                d="M4,13c1.1,0,2-0.9,2-2c0-1.1-0.9-2-2-2s-2,0.9-2,2C2,12.1,2.9,13,4,13z M5.13,14.1C4.76,14.04,4.39,14,4,14 c-0.99,0-1.93,0.21-2.78,0.58C0.48,14.9,0,15.62,0,16.43V18l4.5,0v-1.61C4.5,15.56,4.73,14.78,5.13,14.1z M20,13c1.1,0,2-0.9,2-2 c0-1.1-0.9-2-2-2s-2,0.9-2,2C18,12.1,18.9,13,20,13z M24,16.43c0-0.81-0.48-1.53-1.22-1.85C21.93,14.21,20.99,14,20,14 c-0.39,0-0.76,0.04-1.13,0.1c0.4,0.68,0.63,1.46,0.63,2.29V18l4.5,0V16.43z M16.24,13.65c-1.17-0.52-2.61-0.9-4.24-0.9 c-1.63,0-3.07,0.39-4.24,0.9C6.68,14.13,6,15.21,6,16.39V18h12v-1.61C18,15.21,17.32,14.13,16.24,13.65z M8.07,16 c0.09-0.23,0.13-0.39,0.91-0.69c0.97-0.38,1.99-0.56,3.02-0.56s2.05,0.18,3.02,0.56c0.77,0.3,0.81,0.46,0.91,0.69H8.07z M12,8 c0.55,0,1,0.45,1,1s-0.45,1-1,1s-1-0.45-1-1S11.45,8,12,8 M12,6c-1.66,0-3,1.34-3,3c0,1.66,1.34,3,3,3s3-1.34,3-3 C15,7.34,13.66,6,12,6L12,6z">
                                            </path>
                                        </g>
                                    </svg>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        <?=format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM users ")['COUNT(id)']);?>
                                    </h5>
                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">TỔNG THÀNH VIÊN</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-secondary">
                                    <i class="fa-solid fa-money-bill fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        <?=format_currency($CMSNT->get_row(" SELECT SUM(money) FROM users ")['SUM(money)']);?>
                                    </h5>
                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">SỐ DƯ CÒN LẠI</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-warning">
                                    <i class="fa-solid fa-user-tie fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        <?=format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM users WHERE `admin` != 0 ")['COUNT(id)']);?>
                                    </h5>
                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">ADMIN</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-md p-2 bg-danger">
                                    <i class="fa-solid fa-lock fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex mb-1 align-items-top justify-content-between">
                                    <h5 class="fw-semibold mb-0 lh-1">
                                        <?=format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM users WHERE `banned` != 0 ")['COUNT(id)']);?>
                                    </h5>
                                </div>
                                <p class="mb-0 fs-10 op-7 text-muted fw-semibold">Banned</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="alert alert-solid-dark alert-dismissible fade show text-white">
            <p>Nếu bạn muốn tracking thành viên đăng ký, bạn có thể chèn <strong>?utm_sourc=ten_chien_dich</strong> vào
                cuối link web để thu thập dữ liệu nơi thành viên đăng ký.</p>
            <p>Ví dụ bạn muốn biết có bao nhiêu user đăng ký trong chiến dịch quảng cáo <strong>ABC</strong>, bạn chèn
                link web vào quảng cáo như sau => <strong><?=base_url();?>?utm_source=camp_abc</strong></p>
            <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <button type="button" onclick="showExportAllUsersModal()" class="btn btn-success btn-sm mb-3">
                        <i class="fa-solid fa-download"></i> XUẤT THÀNH VIÊN
                    </button>
                    <button type="button" onclick="phan_tich_utm_source_users()" class="btn btn-danger btn-sm mb-3">
                        <i class="fa-solid fa-chart-line"></i> THỐNG KÊ UTM_SOURCE
                    </button>
                    <button type="button" onclick="reset_tongnap()" id="reset_tongnap" class="btn btn-info btn-sm mb-3">
                        <i class="fa-solid fa-eraser"></i> RESET TỔNG NẠP
                    </button>
                    <button type="button" onclick="logoutALL()" id="logoutALL" class="btn btn-warning btn-sm mb-3">
                        <i class="fa-solid fa-user-xmark"></i> ĐĂNG XUẤT TẤT CẢ
                    </button>
                    <button type="button" onclick="changeAPIKey()" id="changeAPIKey" class="btn btn-primary btn-sm mb-3">
                        <i class="fa-solid fa-key"></i> THAY ĐỔI API KEY TOÀN BỘ THÀNH VIÊN
                    </button>
                </div>
            </div>
            <div class="modal fade" id="phan_tich_utm_source_users" tabindex="-1"
                aria-labelledby="phan_tich_utm_source_users" data-bs-keyboard="false" aria-hidden="true">
                <!-- Scrollable modal -->
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="phan_tich_utm_source_users"><i
                                    class="fa-solid fa-chart-line"></i>
                                THỐNG KÊ UTM SOURCE
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="hien_thi_phan_tich_utm_source_users" class="mb-3"></div>

                            <p>Nếu bạn muốn tracking thành viên đăng ký, bạn có thể chèn
                                <strong>?utm_sourc=ten_chien_dich</strong> vào
                                cuối link web để thu thập dữ liệu nơi thành viên đăng ký.
                            </p>
                            <p>Ví dụ bạn muốn biết có bao nhiêu user đăng ký trong chiến dịch quảng cáo
                                <strong>ABC</strong>, bạn chèn
                                link web vào quảng cáo như sau =>
                                <strong><?=base_url();?>?utm_source=camp_abc</strong>
                            </p>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light shadow-light btn-wave"
                                data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
            function phan_tich_utm_source_users() {
                $('#hien_thi_phan_tich_utm_source_users').html(
                    '<h5 class="mb-3 py-4 text-center"><i class="fa fa-spinner fa-spin"></i> Đang phân tích dữ liệu, vui lòng chờ...</h5>'
                );
                $('#phan_tich_utm_source_users').modal('show');
                $.ajax({
                    url: "<?=base_url('ajaxs/admin/view.php');?>",
                    method: "POST",
                    data: {
                        action: 'phan_tich_utm_source_users',
                        token: '<?=$getUser['token'];?>'
                    },
                    success: function(result) {
                        $('#hien_thi_phan_tich_utm_source_users').html(result);
                    },
                    error: function() {
                        $('#hien_thi_phan_tich_utm_source_users').html(result);
                    }
                });
            }
            </script>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH THÀNH VIÊN
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Thêm thành viên</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="users">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" type="number" value="<?=$id;?>" name="id"
                                        placeholder="ID Khách hàng">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" type="text" value="<?=$username;?>" name="username"
                                        placeholder="Username">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$name;?>" name="name" placeholder="Full name">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$email;?>" name="email" placeholder="Email">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$phone;?>" name="phone" placeholder="Phone">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$ip;?>" name="ip" placeholder="Địa chỉ IP">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$utm_source;?>" name="utm_source"
                                        placeholder="utm_source">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="status" class="form-control">
                                        <option value="">Trạng thái
                                        </option>
                                        <option <?=$status == 2 ? 'selected' : '';?> value="2">Banned
                                        </option>
                                        <option <?=$status == 1 ? 'selected' : '';?> value="1">Active
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="role" class="form-control">
                                        <option value="">Vai trò
                                        </option>
                                        <option <?=$role == 1 ? 'selected' : '';?> value="1">CTV
                                        </option>
                                        <option <?=$role == 2 ? 'selected' : '';?> value="2">Admin
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="money" class="form-control">
                                        <option value="">Sắp xếp số dư
                                        </option>
                                        <option <?=$money == 1 ? 'selected' : '';?> value="1">Tăng dần
                                        </option>
                                        <option <?=$money == 2 ? 'selected' : '';?> value="2">Giảm dần
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="total_money" class="form-control">
                                        <option value="">Sắp xếp tổng nạp
                                        </option>
                                        <option <?=$total_money == 1 ? 'selected' : '';?> value="1">Tăng dần
                                        </option>
                                        <option <?=$total_money == 2 ? 'selected' : '';?> value="2">Giảm dần
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="discount" class="form-control">
                                        <option value="">Sắp xếp chiết khấu
                                        </option>
                                        <option <?=$discount == 1 ? 'selected' : '';?> value="1">Tăng dần
                                        </option>
                                        <option <?=$discount == 2 ? 'selected' : '';?> value="2">Giảm dần
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-danger" href="<?=base_url_admin('users');?>"><i
                                            class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1.000</option>
                                        <option <?=$limit == 5000 ? 'selected' : '';?> value="5000">5.000</option>
                                        <option <?=$limit == 10000 ? 'selected' : '';?> value="10000">10.000</option>
                                        <option <?=$limit == 15000 ? 'selected' : '';?> value="15000">15.000</option>
                                        <option <?=$limit == 20000 ? 'selected' : '';?> value="20000">20.000</option>
                                        <option <?=$limit == 30000 ? 'selected' : '';?> value="30000">30.000</option>
                                        <option <?=$limit == 40000 ? 'selected' : '';?> value="40000">40.000</option>
                                        <option <?=$limit == 50000 ? 'selected' : '';?> value="50000">50.000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 4 ? 'selected' : '';?> value="4"><?=__('Hôm qua');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?>
                                        </option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
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
                                                    id="check_all_checkbox_users" value="option1">
                                            </div>
                                        </th>
                                        <th scope="col">Username</th>
                                        <th scope="col">Email</th>
                                        <th scope="col" class="text-center">Số dư khả dụng</th>
                                        <th scope="col" class="text-center">Tổng nạp</th>
                                        <th scope="col" class="text-center">Chiết khấu</th>
                                        <th scope="col" class="text-center">Admin</th>
                                        <?php if($CMSNT->site('ctv_status') == 1): ?>
                                        <th scope="col" class="text-center">CTV</th>
                                        <?php endif; ?>
                                        <th scope="col" class="text-center">Trạng thái</th>
                                        <th scope="col" class="text-center">Hoạt động</th>
                                        <th scope="col" class="text-center">utm_source</th>
                                        <th scope="col">Thời gian</th>
                                        <th scope="col" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox_users"
                                                    data-id="<?=$row['id'];?>" name="checkbox_users"
                                                    value="<?=$row['id'];?>" />
                                            </div>
                                        </td>
                                        <td><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['id']);?>"><?=$row['username'];?>
                                                [ID <?=$row['id'];?>]</a>
                                        </td>
                                        <td>
                                            <i class="fa fa-envelope" aria-hidden="true"></i> <?=$row['email'];?>
                                        </td>
                                        <td class="text-right">
                                            <b style="color:blue;"><?=format_currency($row['money']);?></b>
                                        </td>
                                        <td class="text-right">
                                            <b style="color:red;"><?=format_currency($row['total_money']);?></b>
                                        </td>
                                        <td class="text-right">
                                            <b><?=format_cash($row['discount']);?>%</b>
                                        </td>
                                        <td class="text-center"><?=display_mark($row['admin']);?></td>
                                        <?php if($CMSNT->site('ctv_status') == 1): ?>
                                        <td class="text-center">
                                            <?php if($row['ctv'] == 1): ?>
                                                <span class="badge bg-success">Có</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Không</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                        <td class="text-center">
                                            <?=display_banned($row['banned']);?>
                                        </td>
                                        <td class="text-center"><?=display_online($row['time_session']);?></td>
                                        <td class="text-center"><?=$row['utm_source'];?></td>
                                        <td><span><?=$row['create_date'];?></span></td>
                                        <td class="text-center fs-base">
                                            <a href="<?=base_url_admin('user-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-primary shadow-primary btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Edit');?>">
                                                <i class="fa fa-fw fa-edit"></i> Edit
                                            </a>
                                            <a type="button" onclick="removeAccount('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php if(checkPermission($getUser['admin'], 'login_user') != true):?>
                                            <a href="<?=base_url_admin('login-user&id='.$row['id']);?>"
                                                class="btn btn-sm btn-info shadow-info btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Login');?>">
                                                <i class="fa fa-fw fa-sign-in"></i> Login
                                            </a>
                                            <?php endif?>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="<?=$CMSNT->site('ctv_status') == 1 ? '12' : '11';?>">
                                        <div class="btn-list">
                                            <button type="button" onclick="confirmDeleteAccount()"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> XÓA THÀNH VIÊN</button>
                                            <button type="button" id="btn_edit_status_user"
                                                class="btn btn-outline-success shadow-success btn-wave btn-sm"><i
                                                    class="fa-solid fa-pen-to-square"></i> CHỈNH TRẠNG
                                                THÁI</button>
                                            <button type="button" onclick="showExportUsersModal()"
                                                class="btn btn-outline-info shadow-info btn-wave btn-sm"><i
                                                    class="fa-solid fa-download"></i> XUẤT ĐÃ CHỌN</button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?>
                                    Results</p>
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

<div class="modal fade" id="modal_edit_status_user" tabindex="-1" aria-labelledby="modal_edit_status_user"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">Cập nhật trạng thái <mark
                        class="checkboxeslength"></mark> thành viên đã chọn</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Trạng thái:');?> <span
                            class="text-danger">*</span></label>
                    <div class="col-sm-8">
                        <select class="form-control" id="status" required>
                            <option value="1">Banned</option>
                            <option value="0">Active</option>
                        </select>
                    </div>
                </div>
                <p>Khi bạn nhấn vào nút UPDATE đồng nghĩa các thành viên mà bạn đã chọn sẽ được cập nhật thành trạng
                    thái trên.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="update_status_records()" id="update_status_records"
                    class="btn btn-primary"><i class="fa fa-solid fa-save"></i> <?=__('Update');?></button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
function update_status_records() {
    $('#update_status_records').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled',
        true);
    var status = document.getElementById('status').value;
    var checkbox = document.getElementsByName('checkbox_users');
    // Sử dụng hàm đệ quy để thực hiện lần lượt từng postUpdate với thời gian chờ 100ms
    function postUpdatesSequentially(index) {
        if (index < checkbox.length) {
            if (checkbox[index].checked === true) {
                post_update_status_user(checkbox[index].value, status);
            }
            setTimeout(function() {
                postUpdatesSequentially(index + 1);
            }, 100);
        } else {
            Swal.fire({
                title: "Thành công!",
                text: "Cập nhật trạng thái thành công",
                icon: "success"
            });
            setTimeout(function() {
                location.reload();
            }, 1000);
            $('#update_status_records').html('<i class="fa fa-solid fa-save"></i> <?=__('Update');?>').prop(
                'disabled',
                false);
        }
    }
    // Bắt đầu gọi hàm đệ quy từ index 0
    postUpdatesSequentially(0);
}

$("#btn_edit_status_user").click(function() {
    var checkboxes = document.querySelectorAll('input[name="checkbox_users"]:checked');
    if (checkboxes.length === 0) {
        showMessage('Vui lòng chọn ít nhất một thành viên', 'error');
        return;
    }
    $(".checkboxeslength").html(checkboxes.length);
    $("#modal_edit_status_user").modal('show');
});

function post_update_status_user(id, status) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'update_status_user',
            id: id,
            status: status
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


function logoutALL() {
    cuteAlert({
        type: "question",
        title: "<?=__('WARNING');?>",
        message: "<?=__('Hệ thống sẽ đăng xuất tất cả thành viên, bao gồm Admin, có chắc chắn muốn tiếp tục không?');?>",
        confirmText: "<?=__('Agree');?>",
        cancelText: "<?=__('Close');?>"
    }).then((e) => {
        if (e) {
            $('#logoutALL').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: "logoutALL"
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        $('#logoutALL').html(
                            '<i class="fas fa-right-from-bracket mr-1"></i>THOÁT TẤT CẢ').prop(
                            'disabled', false);
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


function changeAPIKey() {
    cuteAlert({
        type: "question",
        title: "<?=__('WARNING');?>",
        message: "<?=__('Hệ thống sẽ thay đổi API KEY cho tất cả thành viên, bao gồm Admin, có chắc chắn muốn tiếp tục không?');?>",
        confirmText: "<?=__('Agree');?>",
        cancelText: "<?=__('Close');?>"
    }).then((e) => {
        if (e) {
            $('#changeAPIKey').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: "changeAPIKey"
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        $('#changeAPIKey').html(
                            '<i class="fas fa-key mr-1"></i> THAY ĐỔI API KEY TOÀN BỘ THÀNH VIÊN').prop(
                            'disabled', false);
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

function postRemoveAccount(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeUser',
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

function confirmDeleteAccount() {
    var checkbox = document.getElementsByName('checkbox_users');
    var isAnyCheckboxChecked = false;
    for (var i = 0; i < checkbox.length; i++) {
        if (checkbox[i].checked === true) {
            isAnyCheckboxChecked = true;
            break;
        }
    }
    if (!isAnyCheckboxChecked) {
        showMessage('Vui lòng chọn ít nhất một thành viên', 'error');
        return;
    }
    var result = confirm('Bạn có đồng ý xóa các thành viên đã chọn không?');
    if (result) {
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
    $('#check_all_checkbox_users').on('click', function() {
        $('.checkbox_users').prop('checked', this.checked);
    });
    $('.checkbox_users').on('click', function() {
        $('#check_all_checkbox_users').prop('checked', $('.checkbox_users:checked')
            .length === $('.checkbox_users').length);
    });
});
</script>


<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">Thêm thành viên mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Username');?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="username"
                                placeholder="<?=__('Please enter your username');?>" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Password');?></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="password"
                                placeholder="<?=__('Please enter your password');?>" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Email');?></label>
                        <div class="col-sm-8">
                            <input type="email" class="form-control" name="email"
                                placeholder="<?=__('Please enter your email address');?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddUser" class="btn btn-primary btn-sm"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?=__('Submit');?></button>
                </div>
            </form>
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
    #exportUsersColumnsList .cursor-move {
        cursor: grab;
    }
    #exportUsersColumnsList .cursor-move:active {
        cursor: grabbing;
    }
    #exportUsersColumnsList .list-group-item {
        user-select: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    #exportUsersColumnsList .list-group-item:hover {
        border-color: rgba(var(--primary-rgb), 0.5) !important;
        background-color: rgba(var(--primary-rgb), 0.03) !important;
    }
    #exportUsersColumnsList .list-group-item.sortable-ghost {
        opacity: 0.5;
        background-color: rgba(var(--primary-rgb), 0.1) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<!-- Sortable.js for drag-drop column ordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Modal Export Users -->
<div class="modal fade" id="exportUsersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-file-export me-2 text-primary"></i><?=__('Tùy chỉnh dữ liệu xuất');?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-primary d-flex align-items-center mb-4 border-0" role="alert">
                    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
                    <div>
                        <strong><?=__('Hướng dẫn:');?></strong> <?=__('Hãy chọn định dạng file và kéo thả để sắp xếp các cột dữ liệu cần thiết. Hệ thống sẽ trích xuất dựa trên lựa chọn của bạn.');?>
                    </div>
                </div>
                
                <div class="row g-4">
                    <!-- Phân vùng: Định dạng file -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-3">
                            <i class="fa-solid fa-file-invoice text-muted me-1"></i><?=__('1. Định dạng file');?>
                        </label>
                        <div class="card shadow-none border rounded-3 bg-white">
                            <div class="card-body p-3">
                                <div class="form-check custom-radio-box mb-3 p-3 border rounded border-primary bg-primary-transparent">
                                    <input class="form-check-input mt-2" type="radio" name="exportUsersFileType" id="fileTypeTXT" value="txt" checked style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="fileTypeTXT">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-lines fa-2x text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">TXT File</div>
                                                <div class="fs-12 text-muted">Văn bản thuần (Tab-separated)</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check custom-radio-box mb-0 p-3 border rounded">
                                    <input class="form-check-input mt-2" type="radio" name="exportUsersFileType" id="fileTypeCSV" value="csv" style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="fileTypeCSV">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-csv fa-2x text-success me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">CSV File</div>
                                                <div class="fs-12 text-muted">Dữ liệu bảng (Comma-separated)</div>
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
                                <i class="fa-solid fa-table-columns text-muted me-1"></i><?=__('2. Cột dữ liệu xuất');?>
                            </label>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-grip-vertical me-1"></i><?=__('Kéo thả để sắp xếp');?>
                            </span>
                        </div>
                        
                        <div class="card shadow-none border bg-light mb-0">
                            <div class="card-body p-2" style="max-height: 380px; overflow-y: auto;">
                                <ul class="list-group list-group-flush" id="exportUsersColumnsList">
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="id">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_id" value="id" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_id"><?=__('ID');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="username">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_username" value="username" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_username"><?=__('Username');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="email">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_email" value="email" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_email"><?=__('Email');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="name">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_name" value="fullname" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_name"><?=__('Họ tên');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="phone">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_phone" value="phone" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_phone"><?=__('Số điện thoại');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="money">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_money" value="money" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_money"><?=__('Số dư');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="total_money">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_total_money" value="total_money" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_total_money"><?=__('Tổng nạp');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="discount">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_discount" value="discount" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_discount"><?=__('Chiết khấu');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="admin">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_admin" value="admin" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_admin"><?=__('Admin');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="banned">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_banned" value="banned" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_banned"><?=__('Trạng thái');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="utm_source">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_utm_source" value="utm_source" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_utm_source"><?=__('utm_source');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="create_date">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_create_date" value="create_date" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_create_date"><?=__('Ngày tạo');?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-0 p-2 d-flex align-items-center" data-column="ip">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-user-col-checkbox" type="checkbox" role="switch" id="col_ip" value="ip" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="col_ip"><?=__('Địa chỉ IP');?></label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="toggleAllExportUsersColumns(true)">
                                    <i class="fa-solid fa-check-double me-1"></i><?=__('Chọn xuất tất cả');?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="toggleAllExportUsersColumns(false)">
                                    <i class="fa-solid fa-times me-1"></i><?=__('Bỏ chọn');?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-secondary px-4 btn-wave" data-bs-dismiss="modal"><?=__('Hủy bỏ');?></button>
                <button type="button" class="btn btn-success px-4 btn-wave fw-bold" id="confirmExportUsersBtn" onclick="confirmExportUsers()">
                    <i class="fa-solid fa-download me-2"></i><?=__('Bắt đầu trích xuất');?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // JS trang trí khi select file type radio
    $(document).ready(function() {
        $('input[name="exportUsersFileType"]').on('change', function() {
            $('.custom-radio-box').removeClass('border-primary bg-primary-transparent');
            $(this).closest('.custom-radio-box').addClass('border-primary bg-primary-transparent');
        });
        
        // Khởi tạo Sortable cho danh sách cột export
        if (typeof Sortable !== 'undefined' && document.getElementById('exportUsersColumnsList')) {
            new Sortable(document.getElementById('exportUsersColumnsList'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.fa-grip-vertical'
            });
        }
    });

    // Lấy danh sách ID đã chọn
    function getSelectedUserIds() {
        var selectedIds = [];
        $('.checkbox_users:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    // Hiển thị modal export users (từ checkbox đã chọn)
    function showExportUsersModal() {
        var selectedIds = getSelectedUserIds();
        if (selectedIds.length === 0) {
            showMessage('<?=__("Vui lòng chọn ít nhất một thành viên");?>', 'error');
            return;
        }
        window._exportAllMode = false;
        var modalEl = document.getElementById('exportUsersModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    }

    // Hiển thị modal export toàn bộ thành viên
    function showExportAllUsersModal() {
        window._exportAllMode = true;
        var modalEl = document.getElementById('exportUsersModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    }

    // Chọn/bỏ chọn tất cả cột export
    function toggleAllExportUsersColumns(checked) {
        $('.export-user-col-checkbox').prop('checked', checked);
    }

    // Xác nhận export users (hỗ trợ cả mode selected và all)
    function confirmExportUsers() {
        var isExportAll = window._exportAllMode || false;
        var selectedIds = [];

        if (!isExportAll) {
            selectedIds = getSelectedUserIds();
            if (selectedIds.length === 0) {
                showMessage('<?=__("Vui lòng chọn ít nhất một thành viên");?>', 'error');
                return;
            }
        }

        // Lấy loại file
        var fileType = $('input[name="exportUsersFileType"]:checked').val() || 'txt';

        // Lấy danh sách cột được chọn theo thứ tự
        var columns = [];
        $('#exportUsersColumnsList li').each(function() {
            var $checkbox = $(this).find('.export-user-col-checkbox');
            if ($checkbox.prop('checked')) {
                columns.push($checkbox.val());
            }
        });

        if (columns.length === 0) {
            showMessage('<?=__("Vui lòng chọn ít nhất một cột để xuất");?>', 'error');
            return;
        }

        // Gọi AJAX để export
        $('#confirmExportUsersBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?=__("Đang tải...");?>');

        // Build data cho AJAX
        var ajaxData = {
            action: 'exportSelectedUsers',
            file_type: fileType,
            columns: columns,
            token: '<?=$getUser['token'];?>'
        };
        if (isExportAll) {
            ajaxData.export_all = true;
        } else {
            ajaxData.ids = selectedIds;
        }

        $.ajax({
            url: "<?=BASE_URL('ajaxs/admin/view.php');?>",
            type: 'POST',
            dataType: 'JSON',
            data: ajaxData,
            success: function(result) {
                $('#confirmExportUsersBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?=__("Tải về");?>');

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
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getInstance(document.getElementById('exportUsersModal')).hide();
                    } else {
                        $('#exportUsersModal').modal('hide');
                    }
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#confirmExportUsersBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?=__("Tải về");?>');
                showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
            }
        });
    }

function reset_tongnap() {
    cuteAlert({
        type: "question",
        title: "Xác nhận reset tổng nạp toàn bộ thành viên",
        message: "Hệ thống sẽ reset tổng tiền đã nạp của toàn bộ users khi bạn nhấn Đồng ý.",
        confirmText: "Đồng ý",
        cancelText: "Không"
    }).then((e) => {
        if (e) {
            $('#reset_tongnap').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: "reset_total_money_users"
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        $('#reset_tongnap').html(
                            '<i class="fa-solid fa-eraser"></i> RESET TỔNG NẠP').prop(
                            'disabled', false);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
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