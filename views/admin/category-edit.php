<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa chuyên mục') . ' | ' . $CMSNT->site('title')
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="' . BASE_URL('public/AdminLTE3/') . 'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = validate_int($_GET['id'], 1);
    if ($id === false) {
        redirect(base_url_admin('categories'));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id])) {
        redirect(base_url_admin('categories'));
    }
} else {
    redirect(base_url_admin('categories'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_product') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveCategory'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }
    // Validate input data
    $name = validate_string($_POST['name'], 255, 1);
    if ($name === false) {
        die('<script type="text/javascript">if(!alert("' . __('Tên chuyên mục không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
    }

    $slug = validate_slug($_POST['slug'], 255);
    if ($slug === false) {
        die('<script type="text/javascript">if(!alert("' . __('Slug không hợp lệ! Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang, không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.') . '")){window.history.back().location.reload();}</script>');
    }

    $stt = validate_int($_POST['stt'], -999999999);
    if ($stt === false) {
        die('<script type="text/javascript">if(!alert("' . __('Ưu tiên không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
    }

    $parent_id = validate_int($_POST['parent_id'], 0);
    if ($parent_id === false) {
        die('<script type="text/javascript">if(!alert("' . __('Chuyên mục cha không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
    }

    // Kiểm tra: nếu chuyên mục cha (parent_id=0) đang có con thì không được phép đổi thành chuyên mục con
    if ($row['parent_id'] == 0 && $parent_id != 0) {
        $childCount = $CMSNT->num_rows_safe("SELECT * FROM `categories` WHERE `parent_id` = ?", [$row['id']]);
        if ($childCount > 0) {
            die('<script type="text/javascript">if(!alert("' . sprintf(__('Không thể chuyển chuyên mục này thành chuyên mục con vì nó đang có %d chuyên mục con. Vui lòng di chuyển hoặc xóa các chuyên mục con trước.'), $childCount) . '")){window.history.back().location.reload();}</script>');
        }
    }

    $status = validate_int($_POST['status'], 0, 1);
    if ($status === false) {
        die('<script type="text/javascript">if(!alert("' . __('Trạng thái không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
    }

    $description = validate_string($_POST['description'], 1000);
    if ($description === false) {
        $description = '';
    }

    // Kiểm tra trùng lặp tên chuyên mục
    if ($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `name` = ? AND `id` != ?", [$name, $row['id']])) {
        die('<script type="text/javascript">if(!alert("' . __('Tên chuyên mục đã tồn tại trong hệ thống.') . '")){window.history.back().location.reload();}</script>');
    }

    // Kiểm tra trùng lặp slug
    if ($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `slug` = ? AND `id` != ?", [$slug, $row['id']])) {
        die('<script type="text/javascript">if(!alert("' . __('Slug chuyên mục đã tồn tại trong hệ thống.') . '")){window.history.back().location.reload();}</script>');
    }
    if (check_img('icon') == true) {
        unlink($row['icon']);
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/' . $rand . '.' . $ext;
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update("categories", [
                'icon' => $uploads_dir
            ], " `id` = ?", [$row['id']]);
        }
    }
    $isInsert = $CMSNT->update("categories", [
        'stt'               => $stt,
        'parent_id'         => $parent_id,
        'name'              => $name,
        'slug'              => $slug,
        'description'       => $description,
        'status'            => $status
    ], " `id` = ?", [$row['id']]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Category (" . $row['name'] . " ID " . $row['id'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Category (" . $row['name'] . " ID " . $row['id'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
    }
}
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button" class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('categories'); ?>"><i class="fa-solid fa-arrow-left"></i></a> <?= __('Chỉnh sửa chuyên mục'); ?> <?= $row['name']; ?></h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?= __('CHỈNH SỬA CHUYÊN MỤC SẢN PHẨM'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="col-sm-12">
                                    <div class="mb-4">
                                        <label class="form-label" for="stt"><?= __('Ưu tiên:'); ?></label>
                                        <input type="text" class="form-control" value="<?= $row['stt']; ?>" name="stt" required>
                                        <small class="text-muted"><?= __('Ưu tiên càng cao, chuyên mục càng hiển thị trên cùng'); ?></small>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="name"><?= __('Tên chuyên mục:'); ?></label>
                                        <input type="text" class="form-control" value="<?= $row['name']; ?>" name="name"
                                            placeholder="<?= __('Nhập tên chuyên mục'); ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="slug"><?= __('Liên kết truy cập chuyên mục (slug):'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?= base_url('category/'); ?></span>
                                            <input type="text" class="form-control" value="<?= $row['slug']; ?>" name="slug" id="slug-input"
                                                placeholder="<?= __('Nhập slug chuyên mục'); ?>" required>
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

                                        // Validate slug hiện tại khi trang load
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var currentSlug = document.getElementById('slug-input').value;
                                            if (currentSlug) {
                                                showSlugValidation(currentSlug);
                                            }
                                        });
                                    </script>
                                    <?php
                                    $hasChildren = $CMSNT->num_rows_safe("SELECT * FROM `categories` WHERE `parent_id` = ?", [$row['id']]);
                                    $isParentWithChildren = ($row['parent_id'] == 0 && $hasChildren > 0);
                                    ?>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label"
                                            for="example-hf-email"><?= __('Chuyên mục cha:'); ?>
                                            <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <?php if ($isParentWithChildren): ?>
                                                <input type="hidden" name="parent_id" value="0">
                                                <select class="form-control mb-2" disabled>
                                                    <option selected value="0"><?= __('-- Chuyên mục cha (gốc) --'); ?></option>
                                                </select>
                                                <div class="alert alert-warning py-2 mt-2 mb-0">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                    <strong><?= __('Không thể thay đổi:'); ?></strong>
                                                    <?= __('Chuyên mục này đang có'); ?> <strong><?= $hasChildren; ?></strong> <?= __('chuyên mục con. Hãy di chuyển hoặc xóa các chuyên mục con trước khi thay đổi cấp bậc.'); ?>
                                                </div>
                                            <?php else: ?>
                                                <select class="form-control mb-2" name="parent_id" required>
                                                    <option value="0"><?= __('-- Chuyên mục cha (gốc) --'); ?></option>
                                                    <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                                        <?php if ($option['id'] == $row['id']) continue; ?>
                                                        <option <?= $option['id'] == $row['parent_id'] ? 'selected' : ''; ?>
                                                            value="<?= $option['id']; ?>"><?= $option['name']; ?></option>
                                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $option['id'] . "' ") as $option1): ?>
                                                            <option disabled value="<?= $option1['id']; ?>">__<?= $option1['name']; ?>
                                                            </option>
                                                        <?php endforeach ?>
                                                    <?php endforeach ?>
                                                </select>
                                                <i><?= __('Chỉ định một chuyên mục cha để tạo đa cấp. Chẳng hạn, chuyên mục Facebook sẽ là chuyên mục cha của Clone Facebook và VIA Facebook.'); ?></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="code"><?= __('Icon:'); ?></label>
                                        <input type="file" class="custom-file-input mb-2" name="icon">
                                        <img src="<?= base_url($row['icon']); ?>" width="50px">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_left"><?= __('Description SEO:'); ?></label>
                                        <textarea class="form-control" rows="3"
                                            name="description"><?= $row['description']; ?></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="status"><?= __('Trạng thái:'); ?></label>
                                        <select class="form-control" name="status" required>
                                            <option <?= $row['status'] == 1 ? 'selected' : ''; ?> value="1"><?= __('Hiển thị'); ?></option>
                                            <option <?= $row['status'] == 0 ? 'selected' : ''; ?> value="0"><?= __('Ẩn'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href="<?= base_url_admin('categories'); ?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?= __('Back'); ?></a>
                            <button type="submit" name="SaveCategory" class="btn btn-primary"><i
                                    class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<?php
require_once(__DIR__ . '/footer.php');
?>