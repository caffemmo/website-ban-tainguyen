<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Edit Role',
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
    if (!$row = $CMSNT->get_row("SELECT * FROM `admin_role` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('roles'));
    }
} else {
    redirect(base_url_admin('roles'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_role') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['Save'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Không được dùng chức năng này vì đây là trang web demo.').'")){window.history.back().location.reload();}</script>');
    }
    if(empty($_POST['name'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập tên vai trò")){window.history.back().location.reload();}</script>');
    }
    $name = check_string($_POST['name']);
    if(empty($_POST['role'])){
        die('<script type="text/javascript">if(!alert("Vui lòng chọn quyền cho role")){window.history.back().location.reload();}</script>');
    }
    $role = json_encode($_POST['role']);
    $isInsert = $CMSNT->update("admin_role", [
        'name'              => $name,
        'role'              => $role,
        'update_gettime'    => gettime()
    ], " `id` = '".$row['id']."' ");
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Role ($name)."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Role ($name).", $my_text);
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
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-shield-halved"></i> Chỉnh sửa vai trò '<b style="color:red;"><?=$row['name'];?></b>'</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('roles');?>">Roles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA ROLE
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Tên vai trò (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="name" value="<?=$row['name'];?>" placeholder="VD: Super Admin"
                                        required>
                                </div>
                            </div>
                            <div class="form-check form-check-md d-flex align-items-center mb-2">
                                <input class="form-check-input" type="checkbox" value="" id="selectAll"
                                    onclick="toggleAllCheckboxes()">
                                <label class="form-check-label" for="selectAll">
                                    Chọn tất cả các quyền
                                </label>
                            </div>
                            <div class="row mb-4">
                                <?php foreach ($admin_roles as $category => $roles): ?>
                                <hr>
                                <div class="col-4">
                                    <div class="form-check form-check-md d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" value=""
                                            id="<?= strtolower(str_replace(' ', '_', $category)) ?>"
                                            onclick="toggleCategory('<?= strtolower(str_replace(' ', '_', $category)) ?>')">
                                        <label class="form-check-label"
                                            for="<?= strtolower(str_replace(' ', '_', $category)) ?>">
                                            <?= $category ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <?php foreach ($roles as $key => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?= $key ?>"
                                            name="role[]" id="<?= $key ?>" <?=in_array($key, json_decode($row['role']), true) ? 'checked' : '';?>
                                            data-category="<?= strtolower(str_replace(' ', '_', $category)) ?>">
                                        <label class="form-check-label" for="<?= $key ?>">
                                            <?= $label ?> <span class="badge bg-primary-transparent"><?=$key;?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <script>
                            function toggleAllCheckboxes() {
                                var checkboxes = document.querySelectorAll('[name="role[]"]');
                                var selectAllCheckbox = document.getElementById('selectAll');

                                checkboxes.forEach(function(checkbox) {
                                    checkbox.checked = selectAllCheckbox.checked;
                                });
                            }

                            function toggleCategory(categoryId) {
                                var checkboxes = document.querySelectorAll('[data-category="' + categoryId + '"]');
                                var categoryCheckbox = document.getElementById(categoryId);
                                var selectAllCheckbox = document.getElementById('selectAll');

                                checkboxes.forEach(function(checkbox) {
                                    checkbox.checked = categoryCheckbox.checked;
                                });

                                // Kiểm tra xem tất cả ô checkbox trong danh mục đã được chọn hay không
                                selectAllCheckbox.checked = checkboxes.length === document.querySelectorAll(
                                    '[data-category="' +
                                    categoryId + '"]:checked').length;
                            }
                            </script>
                            <a type="button" class="btn btn-danger shadow-danger" href="<?=base_url_admin('roles');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?=__('Back');?></a>
                            <button type="submit" name="Save" class="btn btn-primary shadow-primary"><i
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
 