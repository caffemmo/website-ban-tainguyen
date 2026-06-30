<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Theme',
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
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_theme') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if (check_img('logo_light') == true) {
        unlink($CMSNT->site('logo_light'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['logo_light']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/logo_light_'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['logo_light']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'logo_light' ");
        }
    }
    if (check_img('logo_dark') == true) {
        unlink($CMSNT->site('logo_dark'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['logo_dark']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/logo_dark_'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['logo_dark']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'logo_dark' ");
        }
    }
    if (check_img('favicon') == true) {
        unlink($CMSNT->site('favicon'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/favicon_'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['favicon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'favicon' ");
        }
    }
    if (check_img('image') == true) {
        unlink($CMSNT->site('image'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/image_'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'image' ");
        }
    }
    if (check_img('default_product_image') == true) {
        unlink($CMSNT->site('default_product_image'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['default_product_image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/default_product_image'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['default_product_image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'default_product_image' ");
        }
    }
    if (check_img('banner_singer') == true) {
        unlink($CMSNT->site('banner_singer'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['banner_singer']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/banner_singer'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['banner_singer']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'banner_singer' ");
        }
    }
    if (check_img('avatar') == true) {
        unlink($CMSNT->site('avatar'));
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/avatar'.$rand.'.'.$file_extension;
        $tmp_name = $_FILES['avatar']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('settings', [
                'value'  => $uploads_dir
            ], " `name` = 'avatar' ");
        }
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __("Thay đổi ảnh trong giao diện"), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("Save successfully!")){window.history.back().location.reload();}</script>');
} ?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-4 mb-0"><i class="fa-solid fa-image me-2"></i>Theme</h1>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card shadow-sm">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            THAY ĐỔI GIAO DIỆN WEBSITE
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Logo Light -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="logo_light" class="form-label">Logo Light</label>
                                        <input type="file" class="form-control" name="logo_light" id="logo_light">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="bg-light rounded p-3" src="<?=BASE_URL($CMSNT->site('logo_light'));?>" alt="Logo Light">
                                    </div>
                                </div>

                                <!-- Logo Dark -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="logo_dark" class="form-label">Logo Dark</label>
                                        <input type="file" class="form-control" name="logo_dark" id="logo_dark">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="bg-light rounded p-3" src="<?=BASE_URL($CMSNT->site('logo_dark'));?>" alt="Logo Dark">
                                    </div>
                                </div>

                                <!-- Favicon -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="favicon" class="form-label">Favicon</label>
                                        <input type="file" class="form-control" name="favicon" id="favicon">
                                    </div>
                                    <div class="mt-2">
                                        <img width="50px" class="rounded-circle" src="<?=BASE_URL($CMSNT->site('favicon'));?>" alt="Favicon">
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="image" class="form-label">Image</label>
                                        <input type="file" class="form-control" name="image" id="image">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="rounded" src="<?=BASE_URL($CMSNT->site('image'));?>" alt="Image">
                                    </div>
                                </div>

                                <!-- Default Product Image -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="default_product_image" class="form-label">Ảnh sản phẩm mặc định</label>
                                        <input type="file" class="form-control" name="default_product_image" id="default_product_image">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="rounded" src="<?=BASE_URL($CMSNT->site('default_product_image'));?>" alt="Default Product Image">
                                    </div>
                                </div>

                                <!-- Banner Singer -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="banner_singer" class="form-label">Banner Singer</label>
                                        <input type="file" class="form-control" name="banner_singer" id="banner_singer">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="rounded" src="<?=BASE_URL($CMSNT->site('banner_singer'));?>" alt="Banner Singer">
                                    </div>
                                </div>

                                <!-- Avatar -->
                                <div class="col-lg-6 mb-4">
                                    <div class="form-group">
                                        <label for="avatar" class="form-label">Avatar</label>
                                        <input type="file" class="form-control" name="avatar" id="avatar">
                                    </div>
                                    <div class="mt-2">
                                        <img width="250px" class="rounded-circle" src="<?=BASE_URL($CMSNT->site('avatar'));?>" alt="Avatar">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button name="SaveSettings" class="btn btn-primary" type="submit">
                                    <i class="fas fa-save"></i> Lưu Ngay
                                </button>
                            </div>
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