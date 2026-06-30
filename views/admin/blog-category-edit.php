<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa chuyên mục bài viết'),
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
    if (!$row = $CMSNT->get_row("SELECT * FROM `post_category` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('blog-category'));
    }
} else {
    redirect(base_url_admin('blog-category'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
if(checkPermission($getUser['admin'], 'edit_blog') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveCategory'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `post_category` WHERE `name` = '".check_string($_POST['name'])."' AND `id` != ".$row['id']." ")) {
        die('<script type="text/javascript">if(!alert("Tên chuyên mục đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    if (check_img('icon') == true) {
        unlink($row['icon']);
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/category'.$rand.'.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update("post_category", [
                'icon' => $uploads_dir
            ], " `id` = '".$row['id']."' ");
        }
    }
    $isInsert = $CMSNT->update("post_category", [
        'name'         => check_string($_POST['name']),
        'slug'          => create_slug(check_string($_POST['name'])),
        'content'       => isset($_POST['content']) ? base64_encode($_POST['content']) : NULL,
        'status'       => check_string($_POST['status'])
    ], " `id` = '".$row['id']."' ");
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Chỉnh sửa chuyên mục bài viết')." (".$row['name']." ID ".$row['id'].")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Chỉnh sửa chuyên mục bài viết')." (".$row['name']." ID ".$row['id'].").", $my_text);
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
            <h1 class="page-title fw-semibold fs-18 mb-0">Chỉnh sửa chuyên mục bài viết <?=__($row['name']);?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?-base_url_admin('blog-category');?>">Chuyên mục bài viết</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__($row['name']);?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA CHUYÊN MỤC BÀI VIẾT
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="col-sm-12">
                                    <div class="mb-4">
                                        <label class="form-label" for="name"><?=__('Tên chuyên mục:');?></label>
                                        <input type="text" class="form-control" value="<?=$row['name'];?>" name="name"
                                            placeholder="Nhập tên chuyên mục" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="code"><?=__('Icon:');?></label>
                                        <input type="file" class="custom-file-input mb-2" name="icon">
                                        <img src="<?=base_url($row['icon']);?>" width="50px">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_left"><?=__('Mô tả chi tiết:');?></label>
                                        <textarea id="content"
                                            name="content"><?=base64_decode($row['content']);?></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_right"><?=__('Status:');?></label>
                                        <select class="form-control" name="status" required>
                                            <option <?=$row['status'] == 1 ? 'selected' : '';?> value="1">ON</option>
                                            <option <?=$row['status'] == 0 ? 'selected' : '';?> value="0">OFF</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger"
                                href="<?=base_url_admin('blog-category');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="SaveCategory" class="btn btn-primary"><i
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