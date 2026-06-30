<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Chỉnh sửa menu',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="'.BASE_URL('public/AdminLTE3/').'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `menu` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url('admin/menu-list'));
    }
} else {
    redirect(base_url('admin/menu-list'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_menu') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveMenu'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    $isUpdate = $CMSNT->update("menu", [
        'name'              => check_string($_POST['name']),
        'slug'              => create_slug(check_string($_POST['name'])),
        'href'              => !empty($_POST['href']) ? check_string($_POST['href']) : '',
        'icon'              => $_POST['icon'],
        'position'          => !empty($_POST['position']) ? check_string($_POST['position']) : 3,
        'target'            => !empty($_POST['target']) ? check_string($_POST['target']) : '',
        'content'           => !empty($_POST['content']) ? $_POST['content'] : '',
        'status'            => check_string($_POST['status'])
    ], " `id` = '".$row['id']."' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit menu (ID ".$row['id'].")"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit menu (ID ".$row['id'].")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công!")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại!")){window.history.back().location.reload();}</script>');
    }
}
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-sitemap"></i> Chỉnh sửa menu '<b
                    style="color:red;"><?=$row['name'];?></b>'</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('menu-list');?>">Menu</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=$row['name'];?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA MENU
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Tên menu (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="<?=$row['name'];?>" name="name"
                                        placeholder="Nhập tên menu cần tạo" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Liên kết</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="<?=$row['href'];?>"
                                        placeholder="Nhập địa chỉ liên kết cần tới khi click vào menu này" name="href">
                                    <small>Chỉ áp dụng khi nội dung hiển thị trống</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-12 col-form-label" for="example-hf-email">Nội dung hiển thị (nếu
                                    có)</label>
                                <div class="col-sm-12">
                                    <textarea id="content" name="content"
                                        placeholder="Để trống nếu muốn sử dụng liên kết"><?=$row['content'];?></textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Vị trí hiển thị</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="position" required>
                                        <option <?=$row['position'] == 1 ? 'selected' : '';?> value="1">Trong menu SỐ DƯ
                                        </option>
                                        <option <?=$row['position'] == 2 ? 'selected' : '';?> value="2">Trong menu NẠP
                                            TIỀN</option>
                                        <option <?=$row['position'] == 3 ? 'selected' : '';?> value="3">Trong menu KHÁC
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Icon menu (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control"
                                        placeholder='Ví dụ: <i class="fas fa-home"></i>' name="icon"
                                        value='<?=$row['icon'];?>' required>
                                    <small>Tìm thêm icon tại <a target="_blank"
                                            href="https://fontawesome.com/v5.15/icons?d=gallery&p=2">đây</a></small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Trạng thái</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="status" required>
                                        <option <?=$row['status'] == 1 ? 'selected' : '';?> value="1">Hiển thị</option>
                                        <option <?=$row['status'] == 0 ? 'selected' : '';?> value="0">Ẩn</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-sm-4"></div>
                                <div class="col-sm-8">
                                    <div class="form-check form-check-md d-flex align-items-center mb-2">
                                        <input class="form-check-input" type="checkbox" name="target"
                                            <?=$row['target'] == '_blank' ? 'checked' : '';?> value="_blank"
                                            id="customCheckbox2" checked>
                                        <label class="form-check-label" for="customCheckbox2">
                                            Mở tab mới khi
                                            click
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?=base_url_admin('menu-list');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="SaveMenu" class="btn btn-primary shadow-primary btn-wave"><i
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

<script>
CKEDITOR.replace("content");
</script>