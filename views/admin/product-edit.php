<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Chỉnh sửa sản phẩm',
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
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    if (!$product = $CMSNT->get_row("SELECT * FROM `products` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('products'));
    }
} else {
    redirect(base_url_admin('products'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
if (checkPermission($getUser['admin'], 'edit_product') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
$checkPreviewUid = checkAddonLicense($CMSNT->site('preview_uid_license'), 'SHOPCLONE7_PREVIEW_UID');
?>
<?php
if (isset($_POST['save'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if ($product['name'] != check_string($_POST['name'])) {
        if ($CMSNT->get_row("SELECT * FROM `products` WHERE `name` = '" . check_string($_POST['name']) . "' ")) {
            die('<script type="text/javascript">if(!alert("sản phẩm này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
        }
    }
    if ($product['slug'] != check_string($_POST['slug'])) {
        if ($CMSNT->get_row("SELECT * FROM `products` WHERE `slug` = '" . check_string($_POST['slug']) . "' ")) {
            die('<script type="text/javascript">if(!alert("Slug này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
        }
    }

    $images = $product['images'];

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

    $isInsert = $CMSNT->update("products", [
        'stt'               => !empty($_POST['stt']) ? check_string($_POST['stt']) : 0,
        'code'              => !empty($_POST['code']) ? check_string($_POST['code']) : NULL,
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
        'max'               => !empty($_POST['max']) ? check_string($_POST['max']) : 100000,
        'sold'              => !empty($_POST['sold']) ? check_string($_POST['sold']) : 0,
        'cost'              => !empty($_POST['cost']) ? check_string($_POST['cost']) : 0,
        'discount'          => !empty($_POST['discount']) ? check_string($_POST['discount']) : 0,
        'check_live'        => !empty($_POST['check_live']) ? check_string($_POST['check_live']) : 'None',
        'category_id'       => check_string($_POST['category_id']),
        'status'            => check_string($_POST['status']),
        'order_by'          => !empty($_POST['order_by']) ? check_string($_POST['order_by']) : 1,
        'update_gettime'    => gettime(),
        'allow_api'         => !empty($_POST['allow_api']) ? check_string($_POST['allow_api']) : 0,
        'hide_in_shop'      => !empty($_POST['hide_in_shop']) ? check_string($_POST['hide_in_shop']) : 0,
        'preview_uid'       => isset($_POST['preview_uid']) ? check_string($_POST['preview_uid']) : 0
    ], " `id` = '" . $product['id'] . "' ");

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Product (" . $product['name'] . " ID " . $product['id'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Product (" . $product['name'] . " ID " . $product['id'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công!")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại!")){window.history.back().location.reload();}</script>');
    }
}

if (isset($_POST['AddDiscount'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    $isInsert = $CMSNT->insert("product_discount", [
        'product_id'        => $product['id'],
        'min'               => check_string($_POST['min']),
        'discount'          => check_string($_POST['discount']),
        'create_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Thêm điều kiện giảm giá cho sản phẩm (" . $product['name'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Thêm điều kiện giảm giá cho sản phẩm (" . $product['name'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công!")){window.history.back().location.reload();}</script>');
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
                    href="<?= base_url_admin('products'); ?>"><i class="fa-solid fa-arrow-left"></i></a> Chỉnh sửa sản
                phẩm <?= $product['name']; ?>
            </h1>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <?php if ($product['check_live'] == 'Clone' && time() - $CMSNT->site('time_cron_checklive_clone') >= 120): ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON
                            JOB</a></b> liên kết: <a class="text-primary" href="<?= base_url('cron/checklive/clone.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                        target="_blank"><?= base_url('cron/checklive/clone.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ thống
                    check live tài khoản.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            <?php endif ?>
            <?php if ($product['check_live'] == 'Hotmail' && time() - $CMSNT->site('time_cron_checklive_hotmail') >= 120): ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON
                            JOB</a></b> liên kết: <a class="text-primary"
                        href="<?= base_url('cron/checklive/hotmail.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                        target="_blank"><?= base_url('cron/checklive/hotmail.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ
                    thống
                    check live tài khoản.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            <?php endif ?>
            <?php if ($product['check_live'] == 'Gmail' && time() - $CMSNT->site('time_cron_checklive_gmail') >= 120): ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON
                            JOB</a></b> liên kết: <a class="text-primary" href="<?= base_url('cron/checklive/gmail.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                        target="_blank"><?= base_url('cron/checklive/gmail.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ
                    thống
                    check live tài khoản.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            <?php endif ?>
            <?php if ($product['check_live'] == 'Instagram' && time() - $CMSNT->site('time_cron_checklive_instagram') >= 120): ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON
                            JOB</a></b> liên kết: <a class="text-primary"
                        href="<?= base_url('cron/checklive/instagram.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                        target="_blank"><?= base_url('cron/checklive/instagram.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ
                    thống
                    check live tài khoản.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            <?php endif ?>
            <?php if ($product['check_live'] == 'Tiktok' && time() - $CMSNT->site('time_cron_checklive_tiktok') >= 120): ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON
                            JOB</a></b> liên kết: <a class="text-primary"
                        href="<?= base_url('cron/checklive/tiktok.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                        target="_blank"><?= base_url('cron/checklive/tiktok.php?key=' . $CMSNT->site('key_cron_job')); ?></a> 1 phút 1 lần để hệ
                    thống
                    check live tài khoản.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            <?php endif ?>
            <div class="row">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                THÔNG TIN SẢN PHẨM
                            </div>
                            <div class="d-flex">
                                <div class="btn-list">
                                    <button type="button" data-bs-toggle="modal"
                                        data-bs-target="#exampleModalScrollable2"
                                        class="btn btn-sm btn-danger-gradient btn-wave shadow-danger"><i
                                            class="fa-solid fa-tag"></i>
                                        THÊM KHUYẾN MÃI</button>
                                    <?php if ($product['supplier_id'] == 0): ?>
                                        <a type="button" href="<?= base_url_admin('product-stock&code=' . $product['code']); ?>"
                                            class="btn btn-warning-gradient btn-wave btn-sm" data-bs-toggle="tooltip"
                                            title="<?= __('Kho hàng'); ?>">
                                            <i class="fa-solid fa-cart-shopping"></i> KHO HÀNG
                                        </a>
                                    <?php else: ?>
                                        <a type="button"
                                            href="<?= base_url_admin('product-api-manager&id=' . $product['supplier_id']); ?>"
                                            class="btn btn-primary-gradient btn-wave btn-sm" data-bs-toggle="tooltip"
                                            title="<?= __('Quản lý API'); ?>">
                                            <i class="fa-solid fa-bars-progress"></i> QUẢN LÝ API
                                        </a>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5">
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Số thứ tự:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="stt" value="<?= $product['stt']; ?>"
                                        required>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Tên sản phẩm:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="<?= $product['name']; ?>"
                                        placeholder="<?= __('Nhập tên sản phẩm'); ?>" required>
                                    <?php if ($product['supplier_id'] != 0 && !empty($product['api_name'])): ?>
                                        <div class="form-text"><i class="fa-solid fa-cloud text-primary"></i> <?= __('Tên gốc API:'); ?> <strong><?= $product['api_name']; ?></strong></div>
                                    <?php endif ?>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Slug:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= base_url('product/'); ?></span>
                                        <input type="text" class="form-control" value="<?= $product['slug']; ?>"
                                            name="slug" required>
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giá bán mặc định:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" value="<?= $product['price']; ?>"
                                            class="form-control text-center" id="example-group1-input3" name="price"
                                            required>
                                        <span class="input-group-text"><?= currencyDefault(); ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giảm giá:'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" id="example-group1-input3"
                                            name="discount" value="<?= $product['discount']; ?>">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Giá vốn:'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" id="example-group1-input3"
                                            name="cost" value="<?= $product['cost']; ?>">
                                        <span class="input-group-text"><?= currencyDefault(); ?></span>
                                    </div>
                                    <small>Giá vốn nhập hàng để tính toán lợi nhuận nếu có</small>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mua tối thiểu:'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" id="example-group1-input3"
                                            name="min" value="<?= $product['min']; ?>">
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Mua tối đa:'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" id="example-group1-input3"
                                            name="max" value="<?= $product['max']; ?>">
                                    </div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Số lượng đã bán: (nếu có)'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control text-center" name="sold"
                                            value="<?= $product['sold']; ?>">
                                    </div>
                                    <small>Số lượng đã bán = số lượng bán thật + số lượng bán ảo (nhập thêm ở
                                        đây)</small>
                                </div>
                                <div class="col-sm-4 mb-2"
                                    style="<?= $product['supplier_id'] != 0 ? 'display:none;' : ''; ?>">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Check live tài khoản:'); ?></label>
                                    <select class="form-control" name="check_live">
                                        <option <?= $product['check_live'] == 'None' ? 'selected' : ''; ?> value="None">
                                            None
                                        </option>
                                        <option <?= $product['check_live'] == 'Clone' ? 'selected' : ''; ?> value="Clone">
                                            Clone Facebook</option>
                                        <option <?= $product['check_live'] == 'Hotmail' ? 'selected' : ''; ?>
                                            value="Hotmail">Hotmail & Outlook (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option <?= $product['check_live'] == 'Gmail' ? 'selected' : ''; ?> value="Gmail">
                                            Gmail (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option <?= $product['check_live'] == 'Instagram' ? 'selected' : ''; ?>
                                            value="Instagram">Instagram (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                        <option <?= $product['check_live'] == 'Tiktok' ? 'selected' : ''; ?>
                                            value="Tiktok">Tiktok (Cấu hình API Key tại Cài đặt -> Kết nối)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <label class="form-label" for="example-hf-email"><?= __('Quốc gia:'); ?> (nếu
                                        có)</label>
                                    <input type="text" class="form-control" name="flag" value="<?= $product['flag']; ?>"
                                        placeholder="Country Codes VD: Việt Nam = vn, Mỹ = us, Thái Lan = th">
                                    <small>Truy cập vào <a class="text-primary"
                                            href="https://www.nationsonline.org/oneworld/country_code_list.htm"
                                            target="_blank">đây</a> để sao chép Code Alpha 2</small>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <label class="form-label" for="example-hf-email"><?= __('Mô tả ngắn:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="3" name="short_desc"
                                        placeholder="Nhập mô tả ngắn cho sản phẩm"
                                        required><?= $product['short_desc']; ?></textarea>
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
                                        rows="5"><?= base64_decode($product['description']); ?></textarea>
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



                                <div class="col-sm-12 mb-3">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Lưu ý xuất hiện khi xem đơn hàng:'); ?></label>
                                    <textarea class="note" id="note"
                                        name="note"><?= base64_decode($product['note']); ?></textarea>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Nội dung đầu tiên trong tệp .txt:'); ?></label>
                                    <textarea class="form-control" name="text_txt"><?= $product['text_txt']; ?></textarea>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <div class="mb-3">
                                        <label class="form-label" for="example-file-input-multiple">Tải lên ảnh sản
                                            phẩm:</label>
                                        <input class="form-control" type="file" name="images[]" multiple>
                                        <small>Có thể chọn 1 hoặc nhiều ảnh</small>
                                    </div>
                                    <div class="row">
                                        <?php foreach (explode(PHP_EOL, $product['images']) as $image): ?>
                                            <?php if (empty($image)) {
                                                continue;
                                            }
                                            ?>
                                            <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-sm-12">
                                                <div class="card">
                                                    <span class="badge bg-dark text-white"><?= $image; ?></span>
                                                    <a href="<?= dirImageProduct($image); ?>" class="glightbox"
                                                        data-gallery="gallery1">
                                                        <img src="<?= dirImageProduct($image); ?>" width="100%" alt="image">
                                                    </a>
                                                    <button type="button"
                                                        onclick="removeImageProduct(`<?= $product['id']; ?>`,`<?= $image; ?>`)"
                                                        class="btn btn-danger btn-sm">Delete</button>
                                                </div>
                                            </div>
                                        <?php endforeach ?>
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
                                        <option <?= $product['status'] == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $product['status'] == 0 ? 'selected' : ''; ?> value="0">OFF</option>
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
                                                <option <?= $product['category_id'] == $option1['id'] ? 'selected' : ''; ?>
                                                    value="<?= $option1['id']; ?>">__<?= $option1['name']; ?></option>
                                            <?php endforeach ?>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-3"
                                    style="<?= $product['supplier_id'] != 0 ? 'display:none;' : ''; ?>">
                                    <label class="form-label" for="example-hf-email"><?= __('Mã sản phẩm:'); ?> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="code" value="<?= $product['code']; ?>">
                                    <small><?= __('Mã sản phẩm dùng để phân loại kho hàng, 2 sản phẩm giống Mã sản phẩm sẽ dùng chung 1 kho hàng.'); ?></small>
                                </div>
                                <div class="col-sm-12 mb-2"
                                    style="<?= $product['supplier_id'] != 0 ? 'display:none;' : ''; ?>">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Tài khoản nào trong kho hàng được ưu tiên bán trước?'); ?></label>
                                    <select class="form-control" name="order_by" required>
                                        <option value="1" <?= $product['order_by'] == 1 ? 'selected' : ''; ?>>
                                            <?= __('Check live gần nhất'); ?></option>
                                        <option value="2" <?= $product['order_by'] == 2 ? 'selected' : ''; ?>>
                                            <?= __('Import lâu nhất'); ?></option>
                                        <option value="3" <?= $product['order_by'] == 3 ? 'selected' : ''; ?>>
                                            <?= __('Import gần nhất'); ?></option>
                                        <option value="4" <?= $product['order_by'] == 4 ? 'selected' : ''; ?>>
                                            <?= __('Ngẫu nhiên'); ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Cho phép kết nối API'); ?></label>
                                    <select class="form-control" name="allow_api" required>
                                        <option <?= $product['allow_api'] == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('ON'); ?></option>
                                        <option <?= $product['allow_api'] == 0 ? 'selected' : ''; ?> value="0">
                                            <?= __('OFF'); ?></option>
                                    </select>
                                </div>
                                <div class="col-sm-12 mb-2">
                                    <label class="form-label"
                                        for="example-hf-email"><?= __('Tạm ẩn sản phẩm khỏi cửa hàng'); ?></label>
                                    <select class="form-control" name="hide_in_shop" required>
                                        <option <?= $product['hide_in_shop'] == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('ON'); ?></option>
                                        <option <?= $product['hide_in_shop'] == 0 ? 'selected' : ''; ?> value="0">
                                            <?= __('OFF'); ?></option>
                                    </select>
                                    <div class="form-text">
                                        <?= __('Khi chọn ON sản phẩm sẽ không hiển thị trên cửa hàng và API danh sách sản phẩm, nhưng vẫn có thể mua được thông qua API.'); ?>
                                    </div>
                                </div>
                                <?php if ($checkPreviewUid['status'] == true && $product['supplier_id'] == 0): ?>
                                    <div class="col-sm-12 mb-2">
                                        <label class="form-label"
                                            for="preview_uid"><?= __('Cho phép xem trước UID'); ?></label>
                                        <select class="form-control" name="preview_uid" id="preview_uid">
                                            <option <?= ($product['preview_uid'] ?? 0) == 0 ? 'selected' : ''; ?> value="0">
                                                <?= __('OFF'); ?></option>
                                            <option <?= ($product['preview_uid'] ?? 0) == 1 ? 'selected' : ''; ?> value="1">
                                                <?= __('ON'); ?></option>
                                        </select>
                                        <div class="form-text">
                                            <?= __('Khi chọn ON, khách hàng có thể xem trước UID trước khi mua hàng.'); ?>
                                        </div>
                                    </div>
                                <?php endif ?>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger"
                                href="<?= base_url_admin('products'); ?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?= __('Back'); ?></a>
                            <button type="submit" name="save" class="btn btn-primary shadow-primary"><i
                                    class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?></button>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
</div>



<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">THÊM ĐIỀU KIỆN KHUYẾN MÃI</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-solid-primary alert-dismissible fade show">
                        Điều kiện này không áp dụng cho các thành viên đã được set chiết khấu giảm giá.<br>
                        Nếu bạn set chiết khấu giảm cho thành viên A, thành viên A sẽ không được áp dụng mức giảm này
                        nữa.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                                class="bi bi-x"></i></button>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6 mb-2">
                            <label class="form-label" for="example-hf-email"><?= __('Số lượng mua tối thiểu:'); ?>
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="min"
                                placeholder="<?= __('Số lượng mua tối thiểu để áp dụng giảm giá'); ?>" required>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <label class="form-label" for="example-hf-email"><?= __('Giảm giá'); ?>
                                <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="discount"
                                    placeholder="VD: nhập 10 sẽ giảm 10%">
                                <span class="input-group-text">
                                    %
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm text-nowrap table-striped table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center">Số lượng mua tối thiểu</th>
                                    <th class="text-center">Giảm giá</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($CMSNT->get_list(" SELECT * FROM product_discount WHERE product_id = '" . $product['id'] . "' ORDER BY min ASC ") as $product_discount): ?>
                                    <tr>
                                        <td class="text-center"><span
                                                class="badge bg-outline-info"><?= format_cash($product_discount['min']); ?></span>
                                        </td>
                                        <td class="text-center"><span
                                                class="badge bg-outline-danger"><?= $product_discount['discount']; ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <a type="button" onclick="remove('<?= $product_discount['id']; ?>')"
                                                class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                data-bs-toggle="tooltip" title="<?= __('Xóa'); ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddDiscount" class="btn btn-primary"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?= __('Submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    var lightboxVideo = GLightbox({
        selector: '.glightbox'
    });

    CKEDITOR.replace("description");
    CKEDITOR.replace("note");

    function removeImageProduct(id, image) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa ảnh",
            message: "Bạn có chắc chắn muốn xóa ảnh " + id + " không ?",
            confirmText: "Đồng Ý",
            cancelText: "Hủy"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: id,
                        image: image,
                        action: 'removeImageProduct'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        })
    }
</script>

<script>
    function remove(id) {
        cuteAlert({
            type: "question",
            title: "Xác Nhận Xóa Điều Kiện Giảm Giá",
            message: "Bạn có chắc chắn muốn xóa item này không ?",
            confirmText: "Đồng Ý",
            cancelText: "Hủy"
        }).then((e) => {
            if (e) {
                post_remove_discount(id);
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        })
    }

    function post_remove_discount(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                id: id,
                action: 'removeProductDiscount'
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);

                } else {
                    showMessage(result.msg, result.status);
                }
            },
            error: function() {
                alert(html(result));
                location.reload();
            }
        });
    }
</script>