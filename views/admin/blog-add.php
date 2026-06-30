<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Viết bài mới'),
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
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');

if(checkPermission($getUser['admin'], 'edit_blog') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}

if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `posts` WHERE `title` = '".check_string($_POST['title'])."' ")) {
        die('<script type="text/javascript">if(!alert("Tiêu đề bài viết đã tồn tại.")){window.history.back().location.reload();}</script>');
    }
    $url_icon = null;
    if (check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/image'.$rand.'.png';
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_icon = $uploads_dir;
        }
    }
    $isInsert = $CMSNT->insert("posts", [
        'user_id'       => $getUser['id'],
        'image'          => $url_icon,
        'title'          => check_string($_POST['title']),
        'slug'          => check_string($_POST['slug']),
        'category_id'   => check_string($_POST['category_id']),
        'content'       => isset($_POST['content']) ? base64_encode($_POST['content']) : NULL,
        'status'        => check_string($_POST['status']),
        'create_gettime'   => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo bài viết mới')." (".check_string($_POST['title']).")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Tạo bài viết mới')." (".check_string($_POST['title']).").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Viết bài mới</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('blogs');?>">Blogs</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Viết bài mới</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            THÊM BÀI VIẾT MỚI
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label" for="example-hf-email">Tiêu đề bài viết:
                                        <span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input name="title" type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label" for="example-hf-email">Slug:
                                        <span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <span class="input-group-text"><?=base_url('blog/');?></span>
                                            <input type="text" class="form-control" name="slug" required>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                function removeVietnameseTones(str) {
                                    return str.normalize('NFD') // Tách tổ hợp ký tự và dấu
                                        .replace(/[\u0300-\u036f]/g, '') // Loại bỏ dấu
                                        .replace(/đ/g, 'd') // Chuyển đổi chữ "đ" thành "d"
                                        .replace(/Đ/g, 'D'); // Chuyển đổi chữ "Đ" thành "D"
                                }

                                document.querySelector('input[name="title"]').addEventListener('input', function() {
                                    var productName = this.value;

                                    // Chuyển tên sản phẩm thành slug
                                    var slug = removeVietnameseTones(productName.toLowerCase())
                                        .replace(/ /g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                                        .replace(/[^\w-]+/g, ''); // Loại bỏ các ký tự không hợp lệ

                                    // Đặt giá trị slug vào trường input slug
                                    document.querySelector('input[name="slug"]').value = slug;
                                });
                                </script>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label" for="example-hf-email">Ảnh nổi bật:
                                        <span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="file" class="custom-file-input" name="image">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Chuyên mục');?>
                                        <span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <select class="form-select" name="category_id" required>
                                            <option value="">-- <?=__('Chọn chuyên mục');?> --</option>
                                            <?php foreach($CMSNT->get_list(" SELECT * FROM `post_category` ") as $category):?>
                                            <option value="<?=$category['id'];?>"><?=$category['name'];?></option>
                                            <?php endforeach?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label"
                                        for="example-hf-email"><?=__('Nội dung chi tiết:');?></label>
                                    <div class="col-sm-12">
                                        <textarea class="content" id="content" name="content"></textarea>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label"
                                        for="example-hf-email"><?=__('Trạng thái:');?> <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <select class="form-control" name="status" required>
                                            <option value="1">ON</option>
                                            <option value="0">OFF</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                            <a type="button" class="btn btn-danger" href="<?=base_url_admin('blogs');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?=__('Back');?></a>
                            <button type="submit" name="submit" class="btn btn-primary"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Submit');?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>



<!-- Main Container -->
<main id="main-container">

    <!-- Page Content -->
    <div class="content">
        <div
            class="d-md-flex justify-content-md-between align-items-md-center py-3 pt-md-3 pb-md-0 text-center text-md-start mb-3">
            <div>
                <h1 class="h3 mb-1">
                    <?=__('Thêm bài viết');?>
                </h1>
                <p class="fw-medium mb-0 text-muted">

                </p>
            </div>
            <div class="mt-4 mt-md-0">

            </div>
        </div>

        <div class="block block-rounded">
            <div class="block-content block-content-full">

            </div>
        </div>
        <!-- END Dynamic Table Full -->
    </div>
    <!-- END Page Content -->
</main>
<!-- END Main Container -->


<?php
require_once(__DIR__.'/footer.php');
?>

<script>
CKEDITOR.replace("content");
</script>