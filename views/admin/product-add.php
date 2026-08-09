<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Product Add'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
<!-- Select2 Cdn -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Internal Select-2.js -->
<script src="' . base_url('public/theme/') . 'assets/js/select2.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/../../libs/product-guides.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_product') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
$checkPreviewUid = checkAddonLicense($CMSNT->site('preview_uid_license'), 'SHOPCLONE7_PREVIEW_UID');
?>
<?php
if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `products` WHERE `name` = '" . check_string($_POST['name']) . "' ")) {
        die('<script type="text/javascript">if(!alert("sản phẩm này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `products` WHERE `slug` = '" . check_string($_POST['slug']) . "' ")) {
        die('<script type="text/javascript">if(!alert("Slug này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    $guideUrl = trim((string) ($_POST['guide_url'] ?? ''));
    $guideEnabled = (int) ($_POST['guide_enabled'] ?? 0) === 1 ? 1 : 0;
    if ($guideUrl !== '' && !caffemmo_product_guides_is_safe_url($guideUrl)) {
        die('<script type="text/javascript">if(!alert("URL hướng dẫn sản phẩm không hợp lệ.")){window.history.back().location.reload();}</script>');
    }

    $images = '';
    if (isset($_FILES['images']['name']) && !empty($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $name => $value) {
            if ($value == '') {
                break;
            }
            $rand = random("QWERTYUIOPASDFGHJKLZXCVBNM0123456789", 8);
            $uploads_dir = 'assets/storage/images/products/';
            $tmp_name = $_FILES['images']['tmp_name'][$name];
            $name_image = $rand . ".png";
            move_uploaded_file($tmp_name, $uploads_dir . $name_image);
            $images = $images . PHP_EOL . $name_image;
        }
    }

    $isInsert = $CMSNT->insert('products', [
        'stt'               => !empty($_POST['stt']) ? check_string($_POST['stt']) : 0,
        'code'              => !empty($_POST['code']) ? check_string($_POST['code']) : uniqid(),
        'user_id'           => $getUser['id'],
        'name'              => !empty($_POST['name']) ? check_string($_POST['name']) : NULL,
        'slug'              => !empty($_POST['slug']) ? check_string($_POST['slug']) : NULL,
        'images'            => trim($images),
        'short_desc'        => !empty($_POST['short_desc']) ? check_string($_POST['short_desc']) : NULL,
        'description'       => !empty($_POST['description']) ? base64_encode($_POST['description']) : NULL,
        'flag'              => !empty($_POST['flag']) ? check_string($_POST['flag']) : NULL,
        'note'              => !empty($_POST['note']) ? base64_encode($_POST['note']) : NULL,
        'text_txt'          => !empty($_POST['text_txt']) ? check_string($_POST['text_txt']) : NULL,
        'price'             => !empty($_POST['price']) ? check_string($_POST['price']) : 0,
        'min'               => !empty($_POST['min']) ? check_string($_POST['min']) : 1,
        'max'               => !empty($_POST['max']) ? check_string($_POST['max']) : 1000000,
        'cost'              => !empty($_POST['cost']) ? check_string($_POST['cost']) : 0,
        'discount'          => !empty($_POST['discount']) ? check_string($_POST['discount']) : 0,
        'check_live'        => !empty($_POST['check_live']) ? check_string($_POST['check_live']) : 'None',
        'category_id'       => check_string($_POST['category_id']),
        'status'            => check_string($_POST['status']),
        'order_by'          => !empty($_POST['order_by']) ? check_string($_POST['order_by']) : 1,
        'allow_api'         => !empty($_POST['allow_api']) ? check_string($_POST['allow_api']) : 0,
        'hide_in_shop'      => !empty($_POST['hide_in_shop']) ? check_string($_POST['hide_in_shop']) : 0,
        'preview_uid'       => isset($_POST['preview_uid']) ? check_string($_POST['preview_uid']) : 0,
        'sold'              => !empty($_POST['sold']) ? check_string($_POST['sold']) : 0,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        caffemmo_product_guide_save($isInsert, $guideUrl, $guideEnabled);
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Product (" . check_string($_POST['name']) . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Product (" . check_string($_POST['name']) . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "' . base_url_admin('products') . '";}</script>');
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
                    href="<?= base_url_admin('products'); ?>"><i class="fa-solid fa-arrow-left"></i></a> Thêm sản phẩm mới
            </h1>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                THÔNG TIN SẢN PHẨM
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5">
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Số thứ tự:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="stt" value="0" required>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Tên sản phẩm:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name"
                                        placeholder="<?= __('Nhập tên sản phẩm'); ?>" required>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Slug:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= base_url('product/'); ?></span>
                                        <input type="text" class="form-control" name="slug" required>
                                    </div>
                                    <small>Để mặc định nếu không hiểu cách sử dụng.</small>
                                </div>
                                <script>
                                    function removeVietnameseTones(str) {
                                        return str.normalize('NFD') // Tách tổ hợp ký tự và dấu
                                            .replace(/[\u0300-\u036f]/g, '') // Loại bỏ dấu
                                            .replace(/đ/g, 'd') // Chuyển đổi chữ "đ" thành "d"
                                            .replace(/Đ/g, 'D'); // Chuyển đổi chữ "Đ" thành "D"
                                    }

                                    document.querySelector('input[name="name"]').addEventListener('input', function() {
                                        var productName = this.value;

                                        // Chuyển tên sản phẩm thành slug
                                        var slug = removeVietnameseTones(productName.toLowerCase())
                                            .replace(/ /g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                                            .replace(/[^\w-]+/g, ''); // Loại bỏ các ký tự không hợp lệ

                                        // Đặt giá trị slug vào trường input slug
                                        document.querySelector('input[name="slug"]').value = slug;
                                    });
                                </script>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giá bán mặc định:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" id="example-group1-input3"
                                            name="price" required>
                                        <span class="input-group-text"><?= currencyDefault(); ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giảm giá:'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" id="example-group1-input3"
                                            name="discount" value="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giá vốn:'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" id="example-group1-input3"
                                            name="cost" value="0">
                                        <span class="input-group-text"><?= currencyDefault(); ?></span>
                                    </div>
                                    <small>Giá vốn nhập hàng để tính toán lợi nhuận nếu có</small>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mua tối thiểu:'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" id="example-group1-input3"
                                            name="min" value="1">
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mua tối đa:'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" id="example-group1-input3"
                                            name="max" value="1000000">
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Số lượng đã bán: (nếu có)'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" name="sold"
                                            value="0">

                                    </div>
                                    <small>Số lượng đã bán = số lượng bán thật + số lượng bán ảo (nhập thêm ở đây)</small>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Check live tài khoản:'); ?></label>
                                    <select class="form-control" name="check_live">
                                        <option value="None">None</option>
                                        <option value="Clone">Clone Facebook</option>
                                        <option value="Hotmail">Hotmail & Outlook (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option value="Gmail">Gmail (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option value="Instagram">Instagram (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option value="Tiktok">Tiktok (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Quốc gia:'); ?> (nếu
                                        có)</label>
                                    <input type="text" class="form-control" name="flag"
                                        placeholder="Country Codes VD: Việt Nam = vn, Mỹ = us, Thái Lan = th">
                                    <small>Truy cập vào <a class="text-primary"
                                            href="https://www.nationsonline.org/oneworld/country_code_list.htm"
                                            target="_blank">đây</a> để sao chép Code Alpha 2</small>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mô tả ngắn:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="3" name="short_desc"
                                        placeholder="Nhập mô tả ngắn cho sản phẩm" required></textarea>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label d-flex justify-content-between align-items-center"
                                        for="description">
                                        <?= __('Mô tả chi tiết:'); ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="tooltip"
                                            data-placement="bottom"
                                            title="Hệ thống sẽ tạo nội dung mô tả chi tiết bằng AI dựa vào tiêu đề và mô tả ngắn của sản phẩm."
                                            id="aiGenerateBtn">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i><?= __('Tạo bằng AI') ?>
                                        </button>
                                    </label>
                                    <textarea class="form-control" id="description" name="description"
                                        rows="5"></textarea>
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const aiGenerateBtn = document.getElementById('aiGenerateBtn');
                                        if (!aiGenerateBtn) return;

                                        aiGenerateBtn.addEventListener('click', function() {
                                            const keyword = document.querySelector('[name="name"]').value
                                                .trim();
                                            const short_desc = document.querySelector('[name="short_desc"]')
                                                .value.trim();

                                            // Kiểm tra input tên sản phẩm
                                            if (keyword === "") {
                                                showMessage('Vui lòng nhập tên sản phẩm', 'error');
                                                return;
                                            }

                                            // Kiểm tra input mô tả ngắn
                                            if (short_desc === "") {
                                                showMessage('Vui lòng nhập mô tả ngắn', 'error');
                                                return;
                                            }

                                            // Hiển thị hộp xác nhận (vẫn sử dụng Swal.fire)
                                            Swal.fire({
                                                title: 'Xác nhận',
                                                text: 'Khi bạn nhấn Đồng ý, hệ thống sẽ tự động tạo nội dung mô tả chi tiết sử dụng AI, dựa trên tên sản phẩm và mô tả ngắn bạn đã nhập.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonText: 'Đồng ý',
                                                cancelButtonText: 'Hủy bỏ'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    const originalBtnHTML = aiGenerateBtn.innerHTML;
                                                    aiGenerateBtn.disabled = true;
                                                    aiGenerateBtn.innerHTML =
                                                        '<i class="fa fa-spinner fa-spin me-1"></i>Đang xử lý...';

                                                    $.ajax({
                                                        url: "<?= base_url('ajaxs/admin/create.php'); ?>",
                                                        method: "POST",
                                                        dataType: "JSON",
                                                        data: {
                                                            action: 'generated_description_by_ai',
                                                            keyword: keyword,
                                                            short_desc: short_desc
                                                        },
                                                        success: function(data) {
                                                            console.log(
                                                                "Response từ API:",
                                                                data);
                                                            if (data.success) {
                                                                // Nếu dùng CKEditor, cập nhật qua API của CKEditor
                                                                if (typeof CKEDITOR !==
                                                                    'undefined' &&
                                                                    CKEDITOR
                                                                    .instances &&
                                                                    CKEDITOR.instances
                                                                    .description) {
                                                                    CKEDITOR.instances
                                                                        .description
                                                                        .setData(data
                                                                            .description
                                                                        );
                                                                } else {
                                                                    const descElem =
                                                                        document
                                                                        .getElementById(
                                                                            'description'
                                                                        );
                                                                    if (descElem) {
                                                                        descElem.value =
                                                                            data
                                                                            .description;
                                                                    }
                                                                }
                                                                Swal.fire({
                                                                    title: 'Thành công!',
                                                                    text: 'Nội dung đã được tạo',
                                                                    icon: 'success',
                                                                    confirmButtonText: 'Đóng'
                                                                });
                                                            } else {
                                                                showMessage(data
                                                                    .message ||
                                                                    'Có lỗi xảy ra',
                                                                    'error');
                                                            }
                                                        },
                                                        error: function(xhr, status,
                                                            error) {
                                                            console.error('Error:',
                                                                error);
                                                            showMessage(
                                                                'Đã xảy ra lỗi: ' +
                                                                (error ||
                                                                    'Không rõ nguyên nhân'
                                                                ), 'error');
                                                        },
                                                        complete: function() {
                                                            aiGenerateBtn.disabled =
                                                                false;
                                                            aiGenerateBtn.innerHTML =
                                                                originalBtnHTML;
                                                        }
                                                    });
                                                }
                                            });
                                        });
                                    });
                                </script>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Lưu ý xuất hiện khi xem đơn hàng:'); ?></label>
                                    <textarea class="note" id="note" name="note"></textarea>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Nội dung đầu tiên trong tệp .txt:'); ?></label>
                                    <textarea class="form-control" name="text_txt"></textarea>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="example-file-input-multiple"><?= __('Ảnh sản phẩm:'); ?></label>
                                        <input class="form-control" type="file" name="images[]" multiple>
                                        <small>Có thể chọn 1 hoặc nhiều ảnh</small>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Trạng thái:'); ?> <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" name="status" required>
                                        <option value="1">ON</option>
                                        <option value="0">OFF</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Chuyên mục:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <select class="form-control js-example-basic-single" name="category_id" required>
                                        <option value="0"><?= __('-- Chuyên mục --'); ?></option>
                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                            <option disabled value="<?= $option['id']; ?>"><?= $option['name']; ?></option>
                                            <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $option['id'] . "' ") as $option1): ?>
                                                <option value="<?= $option1['id']; ?>">__<?= $option1['name']; ?></option>
                                            <?php endforeach ?>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mã sản phẩm:'); ?> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="code"
                                        value="<?= isset($_GET['code']) ? check_string($_GET['code']) : uniqid(); ?>">
                                    <small><?= __('Mã sản phẩm dùng để phân loại kho hàng, 2 sản phẩm giống mã sản phẩm sẽ dùng chung 1 kho hàng.'); ?></small>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Tài khoản nào trong kho hàng được ưu tiên bán trước?'); ?></label>
                                    <select class="form-control" name="order_by" required>
                                        <option value="1"><?= __('Check live gần nhất'); ?></option>
                                        <option value="2"><?= __('Import lâu nhất'); ?></option>
                                        <option value="3"><?= __('Import gần nhất'); ?></option>
                                        <option value="4"><?= __('Ngẫu nhiên'); ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Cho phép kết nối API'); ?></label>
                                    <select class="form-control" name="allow_api" required>
                                        <option value="1"><?= __('ON'); ?></option>
                                        <option value="2"><?= __('OFF'); ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Tạm ẩn sản phẩm khỏi cửa hàng'); ?></label>
                                    <select class="form-control" name="hide_in_shop" required>
                                        <option value="0"><?= __('OFF'); ?></option>
                                        <option value="1"><?= __('ON'); ?></option>
                                    </select>
                                    <div class="form-text">
                                        <?= __('Khi chọn ON sản phẩm sẽ không hiển thị trên cửa hàng và API danh sách sản phẩm, nhưng vẫn có thể mua được thông qua API.'); ?>
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="guide_url"><?= __('Link hướng dẫn sử dụng'); ?></label>
                                    <input type="url" class="form-control" name="guide_url" id="guide_url" placeholder="https://...">
                                    <select class="form-control mt-2" name="guide_enabled">
                                        <option value="0"><?= __('Tắt nút hướng dẫn'); ?></option>
                                        <option value="1"><?= __('Bật nút hướng dẫn'); ?></option>
                                    </select>
                                    <div class="form-text"><?= __('Để trống URL để xóa nút hướng dẫn.'); ?></div>
                                </div>
                                <?php if ($checkPreviewUid['status'] == true): ?>
                                    <div class="col-sm-12 mb-2">
                                        <label class="form-label"
                                            for="preview_uid"><?= __('Cho phép xem trước UID'); ?></label>
                                        <select class="form-control" name="preview_uid" id="preview_uid">
                                            <option value="0"><?= __('OFF'); ?></option>
                                            <option value="1"><?= __('ON'); ?></option>
                                        </select>
                                        <div class="form-text">
                                            <?= __('Khi chọn ON, khách hàng có thể xem trước UID trước khi mua hàng.'); ?>
                                        </div>
                                    </div>
                                <?php endif ?>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?= base_url_admin('products'); ?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?= __('Back'); ?></a>
                            <button type="submit" name="submit" class="btn btn-primary shadow-primary btn-wave"><i
                                    class="fa fa-fw fa-plus me-1"></i> Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    CKEDITOR.replace("description");
    CKEDITOR.replace("note");
</script>
