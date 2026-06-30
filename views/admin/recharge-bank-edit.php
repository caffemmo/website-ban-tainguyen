<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Bank Edit'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

 
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $CMSNT = new DB();
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `banks` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('bank-list'));
    }
} else {
    redirect(base_url_admin('bank-list'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_recharge') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['LuuNganHang'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if($checkKey['status'] != true){
        die('<script type="text/javascript">if(!alert("'.$checkKey['msg'].'")){window.history.back().location.reload();}</script>');
    }
    if (check_img('image') == true) {
        unlink($row['image']);
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $uploads_dir = 'assets/storage/images/bank/'.$rand.'.png';
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('banks', [
                'image'  => 'assets/storage/images/bank/'.$rand.'.png'
            ], " `id` = '$id' ");
        }
    }
    $isUpdate = $CMSNT->update("banks", [
        'short_name' => check_string($_POST['short_name']),
        'accountNumber' => check_string($_POST['accountNumber']),
        'status' => check_string($_POST['status']),
        'token' => check_string(removeSpaces($_POST['token'])),
        'password' => check_string($_POST['password']),
        'accountName' => check_string($_POST['accountName'])
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật thông tin ngân hàng')." (".$_POST['short_name']." - ".$_POST['accountNumber'].")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Cập nhật thông tin ngân hàng')." (".$_POST['short_name']." - ".$_POST['accountNumber'].").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công !")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Chỉnh sửa ngân hàng <?=$row['short_name'];?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Nạp tiền</a></li>
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('recharge-bank-config');?>">Ngân hàng</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa ngân hàng
                            <?=$row['short_name'];?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if(time() - $CMSNT->site('check_time_cron_bank') >= 120):?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            Vui lòng thực hiện <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> liên kết: <a class="text-primary" href="<?=base_url('cron/bank.php?key='.$CMSNT->site('key_cron_job'));?>"
                target="_blank"><?=base_url('cron/bank.php?key='.$CMSNT->site('key_cron_job'));?></a> 1 phút 1 lần để hệ thống xử lý nạp tiền tự động.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA NGÂN HÀNG
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Thêm ngân hàng</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?=__('Ngân hàng');?> <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="<?=$row['short_name'];?>" list="options"
                                    name="short_name" placeholder="<?=__('Nhập tên ngân hàng');?>" required>
                                <datalist id="options">
                                    <?php foreach ($config_listbank as $key => $value):?>
                                    <option value="<?=$key;?>"><?=$value;?></option>
                                    <?php endforeach?>
                                    <?php $data = json_decode(curl_get('https://api.vietqr.io/v2/banks'), true);
                                    foreach($data['data'] as $bank):?>
                                    <option value="<?=$bank['code'];?>"><?=$bank['name'];?></option>
                                    <?php endforeach?>
                                </datalist>
                            </div>
                            <div class="row">
                                <div class="col-8">
                                    <div class="mb-4">
                                        <label for="exampleInputFile">Image</label>
                                        <input type="file" class="form-control" name="image">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group">
                                        <img width="200px" src="<?=BASE_URL($row['image']);?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?=__('Account number');?></label>
                                <input type="text" class="form-control" name="accountNumber"
                                    value="<?=$row['accountNumber'];?>" placeholder="Nhập số tài khoản" required>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?=__('Account name');?></label>
                                <input type="text" class="form-control" name="accountName"
                                    value="<?=$row['accountName'];?>" placeholder="Nhập tên chủ tài khoản" required>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1">Trạng thái</label>
                                <select class="form-control" name="status">
                                    <option <?=$row['status'] == 1 ? 'selected' : '';?> value="1">ON</option>
                                    <option <?=$row['status'] == 0 ? 'selected' : '';?> value="0">OFF</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?=__('Password Internet Banking');?></label>
                                <input type="text" class="form-control" name="password" value="<?=$row['password'];?>"
                                    placeholder="Áp dụng khi cấu hình nạp tiền tự động.">
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?=__('Token');?></label>
                                <input type="text" class="form-control" name="token" value="<?=$row['token'];?>"
                                    placeholder="Áp dụng khi cấu hình nạp tiền tự động.">
                            </div>


                            <a type="button" class="btn btn-hero btn-danger"
                                href="<?=base_url_admin('recharge-bank-config');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="LuuNganHang" class="btn btn-hero btn-success"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Save');?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?php
require_once(__DIR__.'/footer.php');
?>