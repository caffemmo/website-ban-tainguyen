<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Language editing'),
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
    $row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url('admin/language-list'));
    }
} else {
    redirect(base_url('admin/language-list'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_lang') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveLang'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if (check_img('icon') == true) {
        $rand = check_string($_POST['lang']);
        $uploads_dir = "assets/storage/flags/flag_$rand.png";
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addIcon = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addIcon) {
            $icon = "assets/storage/flags/flag_$rand.png";
            $CMSNT->update("languages", [
                'icon'      => $icon
            ], " `id` = '".$row['id']."' ");
        }
    }
    $isInsert = $CMSNT->update("languages", [
        'lang'      => check_string($_POST['lang']),
        'code'  => check_string($_POST['code']),
        'status'    => check_string($_POST['status'])
    ], " `id` = '".$row['id']."' ");
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Chỉnh sửa ngôn ngữ')." (".$row['id'].")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Chỉnh sửa ngôn ngữ')." (".$row['id'].").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("'.__('Save successfully!').'")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("'.__('Save failure!').'")){window.history.back().location.reload();}</script>');
    }
}
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Edit Language</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('language-list');?>">Languages</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=$row['lang'];?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA NGÔN NGỮ
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Tên ngôn ngữ</label>
                                <div class="col-sm-8">
                                    <input type="text" value="<?=$row['lang'];?>" class="form-control" name="lang"
                                        placeholder="Nhập tên ngôn ngữ VD: English" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">ISO Code</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="code" value="<?=$row['code'];?>"
                                        placeholder="VD: vi, en, th, fr, zh" required>
                                    <small>Để sử dụng tính năng dịch tự động, Code phải được thêm vào và phải là ISO 639-1 <a class="text-primary" href="https://www.loc.gov/standards/iso639-2/php/code_list.php" target="_blank">Xem ISO</a> .</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Flag</label>
                                <div class="col-sm-8">
                                    <input class="form-control mb-2" type="file" name="icon" id="example-file-input">
                                    <img src="<?=base_url($row['icon']);?>" width="100px">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Trạng thái</label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="status" required>
                                        <option <?=$row['status'] == 1 ? 'selected' : '';?> value="1"><?=__('Show');?>
                                        </option>
                                        <option <?=$row['status'] == 0 ? 'selected' : '';?> value="0"><?=__('Hide');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave" href="<?=base_url_admin('language-list');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="SaveLang" class="btn btn-primary shadow-primary btn-wave"><i
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