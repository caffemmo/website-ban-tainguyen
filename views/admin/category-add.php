<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Tạo chuyên mục mới'),
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
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_product') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}
$id = 0;
if(isset($_GET['id'])){
    $id = validate_int($_GET['id'], 1);
    if ($id === false) {
        $id = 0;
    }
}
?>
<?php
if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_product') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    // Validate input data
    $name = validate_string($_POST['name'], 255, 1);
    if ($name === false) {
        die('<script type="text/javascript">if(!alert("Tên chuyên mục không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $slug = validate_slug($_POST['slug'], 255);
    if ($slug === false) {
        die('<script type="text/javascript">if(!alert("Slug không hợp lệ! Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang, không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.")){window.history.back().location.reload();}</script>');
    }
    
    $stt = validate_int($_POST['stt'], -999999999);
    if ($stt === false) {
        die('<script type="text/javascript">if(!alert("Ưu tiên không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $parent_id = validate_int($_POST['parent_id'], 0);
    if ($parent_id === false) {
        die('<script type="text/javascript">if(!alert("Chuyên mục cha không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $status = validate_int($_POST['status'], 0, 1);
    if ($status === false) {
        die('<script type="text/javascript">if(!alert("Trạng thái không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $description = validate_string($_POST['description'], 1000);
    if ($description === false) {
        $description = '';
    }
    
    // Kiểm tra trùng lặp tên chuyên mục
    if ($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `name` = ?", [$name])) {
        die('<script type="text/javascript">if(!alert("Chuyên mục này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    
    // Kiểm tra trùng lặp slug
    if($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `slug` = ?", [$slug])) {
        die('<script type="text/javascript">if(!alert("Slug chuyên mục đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    $url_icon = null;
    if (check_img('icon') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/icon'.$rand.'.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_icon = $uploads_dir;
        }
    }
    $isInsert = $CMSNT->insert("categories", [
        'stt'           => $stt,
        'icon'          => $url_icon,
        'name'          => $name,
        'parent_id'     => $parent_id,
        'slug'          => $slug,
        'description'   => $description,
        'status'        => $status,
        'create_date'   => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Category (".$name.")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Category (".$name.").", $my_text);
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
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button"
                    class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?=base_url_admin('categories');?>"><i class="fa-solid fa-arrow-left"></i></a> Tạo chuyên mục
                con</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            TẠO CHUYÊN MỤC CON
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label" for="stt"><?=__('Ưu tiên:');?></label>
                                <input type="text" class="form-control" value="0" name="stt" required>
                                <small>Lưu ý: Ưu tiên càng cao, chuyên mục càng hiển thị trên cùng</small>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Tên chuyên mục con:');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="name"
                                        placeholder="<?=__('Nhập tên chuyên mục');?>" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Liên kết truy cập chuyên mục (slug):');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text"><?=base_url('category/');?></span>
                                        <input type="text" class="form-control" name="slug" id="slug-input"
                                            placeholder="<?=__('Nhập slug chuyên mục');?>" required>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <strong>Quy tắc slug hợp lệ:</strong><br>
                                            • Chỉ được chứa chữ cái thường (a-z), số (0-9) và dấu gạch ngang (-)<br>
                                            • Không được bắt đầu hoặc kết thúc bằng dấu gạch ngang<br>
                                            • Ví dụ hợp lệ: <code>dien-thoai</code>, <code>laptop-gaming</code>, <code>phu-kien123</code><br>
                                            • Ví dụ không hợp lệ: <code>-dien-thoai</code>, <code>dien-thoai-</code>, <code>Điện Thoại</code>
                                        </small>
                                        <div id="slug-validation-message" class="mt-1"></div>
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

                                    function validateSlug(slug) {
                                        // Kiểm tra slug hợp lệ
                                        if (!/^[a-z0-9\-]+$/.test(slug)) {
                                            return false;
                                        }
                                        
                                        // Không được bắt đầu hoặc kết thúc bằng dấu gạch ngang
                                        if (slug.startsWith('-') || slug.endsWith('-')) {
                                            return false;
                                        }
                                        
                                        return true;
                                    }

                                    function showSlugValidation(slug) {
                                        var messageDiv = document.getElementById('slug-validation-message');
                                        var slugInput = document.getElementById('slug-input');
                                        
                                        if (slug === '') {
                                            messageDiv.innerHTML = '';
                                            slugInput.classList.remove('is-invalid', 'is-valid');
                                            return;
                                        }
                                        
                                        if (validateSlug(slug)) {
                                            messageDiv.innerHTML = '<small class="text-success"><i class="fa fa-check-circle"></i> Slug hợp lệ</small>';
                                            slugInput.classList.remove('is-invalid');
                                            slugInput.classList.add('is-valid');
                                        } else {
                                            messageDiv.innerHTML = '<small class="text-danger"><i class="fa fa-exclamation-circle"></i> Slug không hợp lệ</small>';
                                            slugInput.classList.remove('is-valid');
                                            slugInput.classList.add('is-invalid');
                                        }
                                    }

                                    // Tự động tạo slug từ tên chuyên mục
                                    document.querySelector('input[name="name"]').addEventListener('input', function() {
                                        var categoryName = this.value;

                                        // Chuyển tên chuyên mục thành slug
                                        var slug = removeVietnameseTones(categoryName.toLowerCase())
                                            .replace(/ /g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                                            .replace(/[^\w-]+/g, ''); // Loại bỏ các ký tự không hợp lệ

                                        // Đặt giá trị slug vào trường input slug
                                        document.querySelector('input[name="slug"]').value = slug;
                                        
                                        // Hiển thị validation
                                        showSlugValidation(slug);
                                    });

                                    // Kiểm tra slug khi người dùng nhập trực tiếp
                                    document.getElementById('slug-input').addEventListener('input', function() {
                                        showSlugValidation(this.value);
                                    });
                                    </script>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Chuyên mục cha:');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control mb-2" name="parent_id" required>
                                        <?php foreach($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option):?>
                                        <option value="<?=$option['id'];?>"
                                            <?=$id == $option['id'] ? 'selected' : '';?>><?=$option['name'];?></option>
                                        <?php foreach($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '".$option['id']."' ") as $option1):?>
                                        <option disabled value="<?=$option1['id'];?>">__<?=$option1['name'];?></option>
                                        <?php endforeach?>
                                        <?php endforeach?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Icon:');?> <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="file" class="custom-file-input" name="icon" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Description SEO:');?></label>
                                <div class="col-sm-12">
                                    <textarea class="form-control" rows="3" name="description"></textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Trạng thái:');?> <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="status" required>
                                        <option value="1"><?=__('Hiển thị');?></option>
                                        <option value="0"><?=__('Ẩn');?></option>
                                    </select>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href="<?=base_url_admin('categories');?>"><i
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



<?php
require_once(__DIR__.'/footer.php');
?>