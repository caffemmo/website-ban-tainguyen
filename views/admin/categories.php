<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Categories').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
/* CSS Variables cho Light/Dark Mode */
:root {
    /* Light Mode (Default) */
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --bg-tertiary: #e9ecef;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --text-muted: #6c757d;
    --border-color: #dee2e6;
    --hover-bg: #e9ecef;
    --accent-color: #0d6efd;
    --success-color: #198754;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #0dcaf0;
    --card-bg: #ffffff;
    --table-bg: #ffffff;
    --table-header-bg: #f5f7fb;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-hover: 0 4px 8px rgba(0,0,0,0.2);
}

/* Dark Mode */
[data-bs-theme="dark"] {
    --bg-primary: #1a1a1a;
    --bg-secondary: #2d2d2d;
    --bg-tertiary: #3a3a3a;
    --text-primary: #ffffff;
    --text-secondary: #b3b3b3;
    --text-muted: #888888;
    --border-color: #404040;
    --hover-bg: #404040;
    --card-bg: #2d2d2d;
    --table-bg: #2d2d2d;
    --table-header-bg: #3a3a3a;
    --shadow: 0 2px 4px rgba(0,0,0,0.1);
    --shadow-hover: 0 4px 8px rgba(0,0,0,0.2);
}

/* Cursor cho kéo thả - đơn giản */
.handle-parent, .handle-child {
    cursor: move;
    color: var(--text-secondary);
    font-size: 16px;
    padding: 5px;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    transition: all 0.2s ease;
}

.handle-parent:hover, .handle-child:hover {
    color: var(--accent-color);
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 3px;
}

/* Sortable effects */
.sortable-ghost {
    opacity: 0.4;
    background-color: var(--bg-tertiary) !important;
    border: 2px dashed var(--accent-color) !important;
}

.sortable-chosen {
    transform: scale(1.02);
    box-shadow: var(--shadow-hover);
    z-index: 9999;
    background-color: var(--bg-tertiary) !important;
}

.sortable-drag {
    transform: rotate(2deg);
    box-shadow: var(--shadow-hover);
    background-color: var(--bg-tertiary) !important;
}

/* Cards styling */
.sortable-parent-item {
    background-color: var(--card-bg) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    overflow: hidden;
}

.sortable-parent-item:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-1px);
}

.category-header {
    background: linear-gradient(to right, var(--bg-secondary), var(--bg-primary)) !important;
    border-bottom: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
    padding: 12px !important;
}

.category-name1 {
    color: var(--text-primary) !important;
    font-weight: 600;
    font-size: 16px;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

/* Badges styling */
.category-badge {
    font-size: 11px;
    padding: 4px 8px;
}

.badge.bg-primary {
    background-color: var(--accent-color) !important;
}

.badge.bg-info {
    background-color: var(--info-color) !important;
}

.badge.bg-success {
    background-color: var(--success-color) !important;
}

.badge.bg-danger {
    background-color: var(--danger-color) !important;
}

/* Tables styling */
.child-table {
    background-color: var(--table-bg) !important;
    color: var(--text-primary) !important;
    font-size: 13px;
}

.child-table thead th {
    background-color: var(--table-header-bg) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-color) !important;
}

.child-table tbody tr {
    background-color: var(--table-bg) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-color) !important;
    cursor: move;
    transition: all 0.2s ease;
}

.child-table tbody tr:hover {
    background-color: var(--hover-bg) !important;
}

.child-table tbody tr.sortable-ghost {
    opacity: 0.4;
    background-color: var(--bg-tertiary) !important;
    border: 2px dashed var(--accent-color) !important;
}

.child-table tbody tr.sortable-chosen {
    transform: scale(1.01);
    box-shadow: var(--shadow-hover);
    z-index: 9999;
    background-color: var(--bg-tertiary) !important;
}

/* Form controls */
.form-control, .form-select {
    background-color: var(--bg-primary) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}

.form-control:focus, .form-select:focus {
    background-color: var(--bg-primary) !important;
    border-color: var(--accent-color) !important;
    color: var(--text-primary) !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Buttons */
.btn-outline-primary {
    color: var(--accent-color) !important;
    border-color: var(--accent-color) !important;
}

.btn-outline-primary:hover {
    background-color: var(--accent-color) !important;
    border-color: var(--accent-color) !important;
    color: white !important;
}

.btn-outline-info {
    color: var(--info-color) !important;
    border-color: var(--info-color) !important;
}

.btn-outline-info:hover {
    background-color: var(--info-color) !important;
    border-color: var(--info-color) !important;
    color: white !important;
}

.btn-outline-danger {
    color: var(--danger-color) !important;
    border-color: var(--danger-color) !important;
}

.btn-outline-danger:hover {
    background-color: var(--danger-color) !important;
    border-color: var(--danger-color) !important;
    color: white !important;
}

/* Alerts */
.alert-info {
    background-color: rgba(13, 202, 240, 0.1) !important;
    border-color: var(--info-color) !important;
    color: var(--text-primary) !important;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
    border-color: var(--warning-color) !important;
    color: var(--text-primary) !important;
}

.alert-light {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}

/* Form switches */
.form-check-input:checked {
    background-color: var(--accent-color) !important;
    border-color: var(--accent-color) !important;
}

/* Modal */
.modal-content {
    background-color: var(--bg-primary) !important;
    border-color: var(--border-color) !important;
}

.modal-header {
    border-bottom-color: var(--border-color) !important;
}

.modal-footer {
    border-top-color: var(--border-color) !important;
}

/* Tối ưu cho thiết bị di động */
@media (max-width: 768px) {
    .handle-parent {
        padding: 12px !important;
        font-size: 20px !important;
        background-color: rgba(13, 110, 253, 0.1) !important;
        border-radius: 5px;
        margin-right: 10px;
        touch-action: none;
    }
    
    .handle-parent:hover {
        background-color: rgba(13, 110, 253, 0.3) !important;
        color: var(--accent-color) !important;
    }
    
    .sortable-parent-item {
        padding-top: 5px;
        padding-bottom: 5px;
        margin-bottom: 15px !important;
    }
    
    .category-header-content {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .category-header-right {
        margin-top: 8px;
        width: 100%;
        justify-content: space-between !important;
    }
}
</style>
<style>
/* Các thành phần bổ sung */
.category-actions {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.category-actions .btn {
    flex: 1;
    min-width: 100px;
    text-align: center;
    white-space: nowrap;
}

.collapse-icon {
    transition: transform 0.3s;
    color: var(--text-secondary);
}

.rotate-icon {
    transform: rotate(180deg);
}

/* CSS cho chuyên mục cha ảo */
.orphan-category {
    position: relative;
    margin-top: 20px;
    order: 9999 !important;
}

.orphan-category::before {
    content: "";
    position: absolute;
    top: -10px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(to right, var(--warning-color), #fd7e14);
}

.handle-parent-disabled {
    cursor: not-allowed !important;
    padding: 8px;
    font-size: 18px;
    color: var(--warning-color);
    display: inline-block;
    margin-right: 5px;
    background-color: rgba(255, 193, 7, 0.1);
    border-radius: 4px;
    opacity: 0.7;
}

.handle-parent-disabled:hover {
    color: #ec971f !important;
    background-color: rgba(255, 193, 7, 0.2) !important;
    cursor: not-allowed !important;
}

.orphan-category .category-header {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%) !important;
    border-left: 4px solid var(--warning-color);
}

.orphan-category .table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

/* CSS cho tính năng chọn nhiều */
.bulk-actions-container {
    border-radius: 8px;
    margin-bottom: 15px;
}

.bulk-actions-container .alert {
    margin-bottom: 0;
    border: 2px solid var(--info-color);
    background: linear-gradient(135deg, rgba(13, 202, 240, 0.1) 0%, rgba(13, 202, 240, 0.05) 100%);
    color: var(--text-primary) !important;
}

/* Styling riêng cho bulk actions của orphan categories */
#bulk-actions-orphan .alert {
    border: 2px solid var(--warning-color);
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
    color: var(--text-primary) !important;
}

.select-all-checkbox, .category-checkbox {
    transform: scale(1.1);
}

.category-checkbox:checked {
    background-color: var(--accent-color) !important;
    border-color: var(--accent-color) !important;
}

.selected-count strong {
    color: var(--accent-color);
    font-size: 16px;
}

/* Hiệu ứng hover cho hàng được chọn */
tr.selected-row {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-left: 3px solid var(--accent-color);
}

/* Animation cho bulk actions */
.bulk-actions-container {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* CSS cho loading state */
.btn.loading {
    position: relative;
    pointer-events: none;
}

.btn.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid transparent;
    border-top: 2px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn.loading .fa-solid {
    opacity: 0;
}

/* CSS cho modal cập nhật parent */
#selectedCategoriesList .badge {
    font-size: 12px;
    padding: 6px 10px;
}

#updateParentModal .modal-dialog {
    max-width: 600px;
}

#newParentSelect {
    font-size: 14px;
}

#newParentSelect option {
    padding: 8px;
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
}

/* Hiệu ứng cho danh sách được chọn */
#selectedCategoriesList {
    min-height: 60px;
    transition: all 0.3s ease;
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
}

#selectedCategoriesList:empty::before {
    content: "Chưa có chuyên mục nào được chọn";
    color: var(--text-muted);
    font-style: italic;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 60px;
}

/* Responsive cho mobile */
@media (max-width: 768px) {
    .bulk-actions-container .alert {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .bulk-actions-container .btn-group {
        margin-top: 10px;
        width: 100%;
    }
    
    .bulk-actions-container .btn {
        flex: 1;
        font-size: 12px;
    }
    
    .select-all-checkbox, .category-checkbox {
        transform: scale(1.3);
    }
    
    .category-actions {
        justify-content: center;
    }
    
    .category-collapse-btn {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    
    .table-responsive {
        border: 0;
        padding: 0;
    }
    
    .child-table {
        font-size: 12px;
    }
    
    .child-table th, .child-table td {
        padding: 8px 4px;
    }
}

/* Responsive cho modal */
@media (max-width: 576px) {
    #updateParentModal .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    #selectedCategoriesList .badge {
        font-size: 11px;
        padding: 4px 8px;
        margin-bottom: 5px;
    }
}
</style>
';
$body['footer'] = '


<!-- Page JS Plugins -->
 

';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_product') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

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
    
    $stt = validate_int($_POST['stt'], -999999999);
    if ($stt === false) {
        die('<script type="text/javascript">if(!alert("Ưu tiên không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $status = validate_int($_POST['status'], 0, 1);
    if ($status === false) {
        die('<script type="text/javascript">if(!alert("Trạng thái không hợp lệ!")){window.history.back().location.reload();}</script>');
    }
    
    $description = validate_string($_POST['description'], 1000);
    if ($description === false) {
        $description = '';
    }
    
    // Validate slug
    $slug = validate_slug($_POST['slug'], 255);
    if ($slug === false) {
        die('<script type="text/javascript">if(!alert("Slug không hợp lệ! Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang, không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.")){window.history.back().location.reload();}</script>');
    }
    
    // Kiểm tra trùng lặp slug
    if ($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `slug` = ?", [$slug])) {
        die('<script type="text/javascript">if(!alert("Chuyên mục này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
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
        'parent_id'     => 0, // Chuyên mục cha
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
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-sitemap me-1"></i> Quản lý chuyên mục sản phẩm
            </h1>
            <div class="ms-md-1 ms-0">
                <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#cleanupCategoriesModal">
                    <i class="fa-solid fa-broom me-1"></i><?= __('Dọn dẹp chuyên mục'); ?>
                </button>
                <button id="btn-add-parent" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Thêm chuyên mục cha
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Form thêm chuyên mục cha -->
            <div class="col-xl-12" id="card-add-parent" style="display: none;">
                <div class="card custom-card mb-4">
                    <div class="card-header d-flex justify-content-between border-bottom-0">
                        <div class="card-title">
                            <i class="fa-solid fa-folder-plus me-2"></i>Thêm chuyên mục cha mới
                        </div>
                        <button type="button" class="btn-close" id="btn-close-add-parent"></button>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="stt"><?=__('Ưu tiên:');?></label>
                                        <input type="text" class="form-control" value="0" name="stt" required>
                                        <div class="form-text text-muted">Ưu tiên càng cao, chuyên mục càng hiển thị
                                            trên cùng</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Tên chuyên mục cha:');?> <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            placeholder="<?=__('Nhập tên chuyên mục');?>" required>
                                    </div>
                                    <input type="hidden" name="parent_id" value="0">
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Liên kết truy cập chuyên mục (slug):');?> <span
                                                class="text-danger">*</span></label>
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
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Icon:');?> <span
                                                class="text-danger">*</span></label>
                                        <input type="file" class="form-control" name="icon" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Trạng thái:');?> <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="1">Hiển thị</option>
                                            <option value="0">Ẩn</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Description SEO:');?></label>
                                        <textarea class="form-control" rows="3" name="description"
                                            placeholder="Mô tả ngắn về chuyên mục này"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i> <?=__('Thêm chuyên mục');?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách chuyên mục -->
            <div class="col-xl-12">
                <div class="card custom-card">

                    <div class="card-body">
                        <?php
                        $parentCategories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = 0 ORDER BY `stt` DESC");
                        
                        // Lấy danh sách chuyên mục con không có cha hợp lệ
                        $orphanCategories = $CMSNT->get_list_safe("SELECT c.* FROM `categories` c LEFT JOIN `categories` p ON c.parent_id = p.id WHERE c.parent_id != 0 AND p.id IS NULL ORDER BY c.stt DESC");
                        $orphanCount = count($orphanCategories);
                        ?>
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs mb-3" id="categoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#tab-all"
                                    type="button" role="tab" aria-controls="tab-all" aria-selected="true">
                                    <i class="fa-solid fa-list me-1"></i>Tất cả
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link<?= $orphanCount > 0 ? ' text-warning' : ''; ?>" id="orphan-tab" data-bs-toggle="tab" data-bs-target="#tab-orphan"
                                    type="button" role="tab" aria-controls="tab-orphan" aria-selected="false">
                                    <i class="fa-solid fa-unlink me-1"></i>Mồ côi
                                    <?php if ($orphanCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-1"><?= $orphanCount; ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="categoryTabsContent">
                            <!-- Tab: Tất cả -->
                            <div class="tab-pane fade show active" id="tab-all" role="tabpanel" aria-labelledby="all-tab">
                                <div class="d-flex mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="collapse-all-btn">
                                        <i class="fa-solid fa-angles-up me-1"></i>Đóng tất cả
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="expand-all-btn">
                                        <i class="fa-solid fa-angles-down me-1"></i>Mở tất cả
                                    </button>
                                </div>
                        
                        <?php if(count($parentCategories) > 0): ?>

                        <div id="category-container">
                            <ul id="sortable-parent-categories" class="list-unstyled mb-0">
                                <?php foreach ($parentCategories as $index => $category): ?>
                                <li class="sortable-parent-item" id="parent-item-<?= $category['id']; ?>"
                                    data-id="<?= $category['id']; ?>">
                                    <div class="card-header p-2 bg-light category-header">
                                        <div class="d-flex align-items-center justify-content-between w-100 category-header-content"
                                            data-bs-toggle="collapse" data-bs-target="#category-<?= $category['id']; ?>"
                                            aria-expanded="false" aria-controls="category-<?= $category['id']; ?>"
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <span class="handle-parent" onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-grip-vertical"></i>
                                                </span>
                                                <img src="<?= base_url($category['icon']); ?>" class="me-2 rounded"
                                                    width="36px" height="36px">
                                                <h5 class="category-name1"><?= $category['name']; ?></h5>
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap category-header-right">
                                                <div class="category-badges">
                                                    <span class="badge bg-primary rounded-pill me-1 category-badge">
                                                        <i
                                                            class="fa-solid fa-folder me-1"></i><?= format_cash($CMSNT->num_rows_safe("SELECT * FROM `categories` WHERE `parent_id` = ?", [$category['id']])); ?>
                                                    </span>
                                                    <span class="badge bg-info rounded-pill me-1 category-badge">
                                                        <i
                                                            class="fa-solid fa-sort-numeric-up me-1"></i><?= $category['stt']; ?>
                                                    </span>
                                                    <?php if($category['status'] == 1): ?>
                                                    <span class="badge bg-success me-1 category-badge"><i
                                                            class="fa-solid fa-check me-1"></i>Hiển thị</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger me-1 category-badge"><i
                                                            class="fa-solid fa-ban me-1"></i>Ẩn</span>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn btn-sm btn-light category-collapse-btn" type="button"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-chevron-down collapse-icon"
                                                        data-category-id="<?= $category['id']; ?>"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="category-<?= $category['id']; ?>" class="collapse">
                                        <div class="card-body">
                                            <div class="category-actions">
                                                <a href="<?= base_url_admin('category-add&id=' . $category['id']); ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-plus me-1"></i> Thêm con
                                                </a>
                                                <a href="<?= base_url_admin('category-edit&id=' . $category['id']); ?>"
                                                    class="btn btn-sm btn-outline-info">
                                                    <i class="fa-solid fa-edit me-1"></i> Sửa
                                                </a>
                                                <button onclick="RemoveRow('<?= $category['id']; ?>')"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash me-1"></i> Xóa
                                                </button>
                                            </div>

                                            <?php $childCategories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = ? ORDER BY `stt` DESC", [$category['id']]); ?>

                                            <?php if(count($childCategories) > 0): ?>
                                            <!-- Nút hành động hàng loạt cho chuyên mục con -->
                                            <div class="bulk-actions-container mt-3"
                                                id="bulk-actions-<?= $category['id']; ?>" style="display: none;">
                                                <div
                                                    class="alert alert-info d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-info-circle me-2"></i>
                                                        <span class="selected-count">Đã chọn <strong>0</strong> chuyên
                                                            mục con</span>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-success bulk-update-status"
                                                            data-parent="<?= $category['id']; ?>" data-status="1">
                                                            <i class="fa-solid fa-eye me-1"></i>Hiển thị
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-warning bulk-update-status"
                                                            data-parent="<?= $category['id']; ?>" data-status="0">
                                                            <i class="fa-solid fa-eye-slash me-1"></i>Ẩn
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-info bulk-update-parent"
                                                            data-parent="<?= $category['id']; ?>">
                                                            <i class="fa-solid fa-folder-tree me-1"></i>Đổi Parent
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger bulk-remove-categories"
                                                            data-parent="<?= $category['id']; ?>">
                                                            <i class="fa-solid fa-trash me-1"></i>Xóa
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="table-responsive mt-3">
                                                <table class="table table-striped table-hover border child-table">
                                                    <thead>
                                                        <tr>
                                                            <th width="3%" class="text-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input select-all-checkbox"
                                                                        type="checkbox"
                                                                        id="selectAll<?= $category['id']; ?>"
                                                                        data-parent="<?= $category['id']; ?>">

                                                                </div>
                                                            </th>
                                                            <th width="3%" class="text-center">
                                                                <i class="fa-solid fa-grip-vertical handle-child"></i>
                                                            </th>
                                                            <th width="45%">Tên</th>
                                                            <th width="10%">Ảnh</th>
                                                            <th width="10%">Sản phẩm</th>
                                                            <th width="10%">Trạng thái</th>
                                                            <th width="10%">Thao tác</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($childCategories as $child): ?>
                                                        <tr id="child-item-<?= $child['id']; ?>"
                                                            data-parent="<?= $category['id']; ?>">
                                                            <td class="text-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input category-checkbox"
                                                                        type="checkbox" value="<?= $child['id']; ?>"
                                                                        id="checkbox<?= $child['id']; ?>"
                                                                        data-parent="<?= $category['id']; ?>"
                                                                        data-name="<?= htmlspecialchars($child['name']); ?>">
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <i class="fa-solid fa-grip-vertical handle-child"></i>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="fw-medium d-block"><?= $child['name']; ?></span>
                                                            </td>
                                                            <td>
                                                                <img src="<?= base_url($child['icon']); ?>" width="32px"
                                                                    height="32px" class="img-thumbnail">
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary rounded-pill">
                                                                    <?= format_cash($CMSNT->num_rows_safe("SELECT * FROM `products` WHERE `category_id` = ?", [$child['id']])); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="status<?= $child['id']; ?>" value="1"
                                                                        <?= $child['status'] == 1 ? 'checked' : ''; ?>
                                                                        onchange="updateForm('<?= $child['id']; ?>')">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="<?= base_url_admin('category-edit&id=' . $child['id']); ?>"
                                                                        class="btn btn-sm btn-info"
                                                                        data-bs-toggle="tooltip" title="Sửa">
                                                                        <i class="fa-solid fa-edit"></i>
                                                                    </a>
                                                                    <button onclick="RemoveRow('<?= $child['id']; ?>')"
                                                                        class="btn btn-sm btn-danger"
                                                                        data-bs-toggle="tooltip" title="Xóa">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="fa-solid fa-info-circle me-2"></i> Chưa có chuyên mục con nào
                                                trong chuyên mục này.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-circle me-2"></i> Chưa có chuyên mục nào trong hệ thống.
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info mb-2">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            Bạn có thể kéo thả các chuyên mục cha để sắp xếp thứ tự. Nhấp vào biểu tượng <i
                                class="fa-solid fa-grip-vertical"></i> và kéo thả để thay đổi vị trí.
                        </div>
                            </div><!-- end tab-all -->

                            <!-- Tab: Mồ côi -->
                            <div class="tab-pane fade" id="tab-orphan" role="tabpanel" aria-labelledby="orphan-tab">
                                <?php
                                $allParentCategories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = 0 ORDER BY `stt` DESC");
                                ?>
                                <?php if ($orphanCount > 0): ?>
                                    <div class="alert alert-warning border-warning">
                                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                        <strong>Có <?= $orphanCount; ?> chuyên mục mồ côi</strong> — Các chuyên mục này có parent_id không tồn tại trong hệ thống hoặc đã bị xóa. Hãy chọn chuyên mục cha phù hợp để gán.
                                    </div>
                                    <!-- Bulk Action Toolbar -->
                                    <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded border" id="orphan-bulk-toolbar" style="display: none !important;">
                                        <span class="text-muted">
                                            <i class="fa-solid fa-check-double me-1"></i>
                                            Đã chọn: <strong id="orphan-selected-count">0</strong>
                                        </span>
                                        <select class="form-select form-select-sm" id="orphan-bulk-parent" style="max-width: 250px;">
                                            <option value="">-- Chọn chuyên mục cha --</option>
                                            <?php foreach ($allParentCategories as $parent): ?>
                                                <option value="<?= $parent['id']; ?>"><?= $parent['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" onclick="bulkAssignOrphanParent()">
                                            <i class="fa-solid fa-check-double me-1"></i>Gán hàng loạt
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="bulkDeleteOrphans()">
                                            <i class="fa-solid fa-trash me-1"></i>Xóa đã chọn
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover border">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="3%" class="text-center">
                                                        <input class="form-check-input" type="checkbox" id="orphan-select-all"
                                                            onchange="toggleOrphanSelectAll(this)" style="transform: scale(1.2);">
                                                    </th>
                                                    <th width="5%">ID</th>
                                                    <th>Tên chuyên mục</th>
                                                    <th width="10%">Parent ID</th>
                                                    <th width="10%">Sản phẩm</th>
                                                    <th width="10%">Trạng thái</th>
                                                    <th width="22%">Gán chuyên mục cha</th>
                                                    <th width="10%">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orphanCategories as $orphan): ?>
                                                    <tr id="orphan-row-<?= $orphan['id']; ?>">
                                                        <td class="text-center">
                                                            <input class="form-check-input orphan-checkbox" type="checkbox"
                                                                value="<?= $orphan['id']; ?>" onchange="updateOrphanSelection()"
                                                                style="transform: scale(1.2);">
                                                        </td>
                                                        <td class="text-muted"><?= $orphan['id']; ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if ($orphan['icon'] != null && file_exists($orphan['icon'])): ?>
                                                                    <img src="<?= base_url($orphan['icon']); ?>" width="32px" height="32px" class="img-thumbnail me-2">
                                                                <?php else: ?>
                                                                    <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded me-2"
                                                                        style="width: 32px; height: 32px; flex-shrink: 0;">
                                                                        <i class="fa-solid fa-unlink text-warning"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <span class="fw-bold"><?= $orphan['name']; ?></span>
                                                                    <?php if (!empty($orphan['slug'])): ?>
                                                                        <br><small class="text-muted"><?= $orphan['slug']; ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-danger">
                                                                <?= $orphan['parent_id']; ?> <i class="fa-solid fa-times ms-1"></i>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary rounded-pill">
                                                                <?= format_cash($CMSNT->num_rows_safe("SELECT * FROM `products` WHERE `category_id` = ?", [$orphan['id']])); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="status<?= $orphan['id']; ?>" value="1"
                                                                    style="transform: scale(1.3);"
                                                                    <?= $orphan['status'] == 1 ? 'checked' : ''; ?>
                                                                    onchange="updateForm('<?= $orphan['id']; ?>')">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <select class="form-select form-select-sm" id="orphan-parent-<?= $orphan['id']; ?>">
                                                                    <option value="">-- Chọn chuyên mục cha --</option>
                                                                    <?php foreach ($allParentCategories as $parent): ?>
                                                                        <option value="<?= $parent['id']; ?>"><?= $parent['name']; ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button class="btn btn-sm btn-primary" onclick="assignOrphanParent('<?= $orphan['id']; ?>')" title="Gán">
                                                                    <i class="fa-solid fa-check"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="btn-list">
                                                                <a href="<?= base_url_admin('category-edit&id=' . $orphan['id']); ?>"
                                                                    class="btn btn-sm btn-info" title="Sửa">
                                                                    <i class="fa-solid fa-edit"></i>
                                                                </a>
                                                                <button onclick="RemoveRow('<?= $orphan['id']; ?>')"
                                                                    class="btn btn-sm btn-danger" title="Xóa">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fa-solid fa-check-circle me-2"></i>
                                        Không có chuyên mục mồ côi. Tất cả chuyên mục đã được gán chuyên mục cha hợp lệ.
                                    </div>
                                <?php endif; ?>
                            </div><!-- end tab-orphan -->

                        </div><!-- end tab-content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dọn dẹp chuyên mục -->
<div class="modal fade" id="cleanupCategoriesModal" tabindex="-1" aria-labelledby="cleanupCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="cleanupCategoriesModalLabel">
                    <i class="fa-solid fa-broom me-2"></i><?= __('Dọn dẹp chuyên mục'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="alert alert-danger d-flex align-items-start" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2 mt-1"></i>
                    <div>
                        <strong><?= __('Cảnh báo!'); ?></strong><br>
                        <?= __('Hành động này sẽ xóa các chuyên mục theo điều kiện bạn chọn. Dữ liệu (và biểu tượng ảnh) đã xóa không thể khôi phục!'); ?>
                    </div>
                </div>

                <form id="cleanupCategoriesForm">
                    <!-- Loại dọn dẹp -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-block mb-3">
                            <?= __('Chọn loại dọn dẹp'); ?> <span class="text-danger">*</span>
                        </label>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_orphan" value="orphan" checked>
                            <label class="form-check-label" for="cleanup_type_orphan">
                                <strong class="text-warning"><?= __('Dọn dẹp chuyên mục mồ côi'); ?></strong>
                                <br><small class="text-muted"><?= __('Xóa các chuyên mục con không liên kết với chuyên mục cha đang tồn tại.'); ?></small>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_empty" value="empty">
                            <label class="form-check-label" for="cleanup_type_empty">
                                <strong class="text-primary"><?= __('Dọn dẹp chuyên mục rỗng'); ?></strong>
                                <br><small class="text-muted"><?= __('Xóa các chuyên mục không chứa sản phẩm VÀ không chứa chuyên mục con nào.'); ?></small>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="cleanup_type" id="cleanup_type_all" value="all">
                            <label class="form-check-label" for="cleanup_type_all">
                                <strong class="text-danger"><?= __('Xóa TOÀN BỘ chuyên mục'); ?></strong>
                                <br><small class="text-muted"><?= __('Thao tác nguy hiểm: Sẽ dọn dẹp sạch sẽ toàn bộ chuyên mục hiện có trong hệ thống.'); ?></small>
                            </label>
                        </div>
                    </div>

                    <!-- Thống kê số chuyên mục sẽ bị ảnh hưởng -->
                    <div class="alert alert-info" id="cleanup_preview">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <span id="cleanup_preview_text"><?= __('Nhấn "Xem trước" để xem số chuyên mục sẽ bị ảnh hưởng'); ?></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> <?= __('Đóng'); ?>
                </button>
                <button type="button" class="btn btn-info" id="btnPreviewCleanup">
                    <i class="fa-solid fa-eye me-1"></i> <?= __('Xem trước'); ?>
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmCleanup" disabled>
                    <i class="fa-solid fa-trash me-1"></i> <?= __('Xác nhận dọn dẹp'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Reset modal khi mở
    $('#cleanupCategoriesModal').on('show.bs.modal', function () {
        $('#cleanupCategoriesForm')[0].reset();
        $('#cleanup_type_orphan').prop('checked', true);
        $('#cleanup_preview_text').html('<?= __('Nhấn "Xem trước" để xem số chuyên mục sẽ bị ảnh hưởng'); ?>');
        $('#btnConfirmCleanup').prop('disabled', true);
    });

    // Sự kiện Xem trước
    $('#btnPreviewCleanup').on('click', function() {
        const cleanupType = $('input[name="cleanup_type"]:checked').val();
        
        let cleanup_orphan = cleanupType === 'orphan' ? 1 : 0;
        let cleanup_empty = cleanupType === 'empty' ? 1 : 0;
        let cleanup_all = cleanupType === 'all' ? 1 : 0;

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> <?= __("Đang tính..."); ?>').prop('disabled', true);

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: "POST",
            dataType: "json",
            data: {
                action: 'previewCleanupCategories',
                token: '<?=$getUser['token'];?>',
                cleanup_orphan: cleanup_orphan,
                cleanup_empty: cleanup_empty,
                cleanup_all: cleanup_all
            },
            success: function(resp) {
                if (resp.status == 'success') {
                    let total_affected = 0;
                    if (cleanup_all == 1) total_affected = resp.all_count;
                    else if (cleanup_orphan == 1) total_affected = resp.orphan_count;
                    else if (cleanup_empty == 1) total_affected = resp.empty_count;

                    if (total_affected > 0) {
                        $('#cleanup_preview_text').html(
                            '<strong>' + total_affected + '</strong> <?= __("chuyên mục sẽ bị xóa vĩnh viễn"); ?>'
                        );
                        $('#btnConfirmCleanup').prop('disabled', false);
                    } else {
                        $('#cleanup_preview_text').html('<span class="text-success"><i class="fa-solid fa-check-circle me-1"></i><?= __("Không có chuyên mục rác nào theo điều kiện này!"); ?></span>');
                        $('#btnConfirmCleanup').prop('disabled', true);
                    }
                } else {
                    showMessage(resp.msg, 'error');
                }
            },
            error: function() {
                showMessage('<?= __("Lỗi kết nối máy chủ!"); ?>', 'error');
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

    // Reset nút xác nhận nếu đổi loại (cần bấm xem trước lại)
    $('input[name="cleanup_type"]').on('change', function() {
        $('#btnConfirmCleanup').prop('disabled', true);
        $('#cleanup_preview_text').html('<?= __('Nhấn "Xem trước" để cập nhật lại số liệu'); ?>');
    });

    // Xác nhận dọn dẹp
    $('#btnConfirmCleanup').on('click', function() {
        const cleanupType = $('input[name="cleanup_type"]:checked').val();
        let cleanupTypeText = '';
        
        switch(cleanupType) {
            case 'orphan': cleanupTypeText = '<?= __("Dọn dẹp chuyên mục mồ côi"); ?>'; break;
            case 'empty': cleanupTypeText = '<?= __("Dọn dẹp chuyên mục rỗng"); ?>'; break;
            case 'all': cleanupTypeText = '<?= __("Xóa TOÀN BỘ chuyên mục"); ?>'; break;
        }

        // Đóng modal Bootstrap
        $('#cleanupCategoriesModal').modal('hide');

        // Hiển thị SweetAlert
        setTimeout(function() {
            Swal.fire({
                title: '<?= __("Xác nhận dọn dẹp chuyên mục"); ?>',
                html: `
                    <div class="text-start">
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong><?= __("Cảnh báo!"); ?></strong><br>
                            <?= __("Bạn sắp dọn dẹp chuyên mục với cấu hình:"); ?>
                            <ul class="mb-0 mt-2">
                                <li><strong><?= __("Loại:"); ?></strong> ${cleanupTypeText}</li>
                            </ul>
                            <hr>
                            <small class="text-danger"><?= __("Hành động này không thể hoàn tác!"); ?></small>
                        </div>
                        <label for="confirmCleanupCategoriesText" class="form-label">
                            <?= __("Để xác nhận, vui lòng nhập"); ?> <strong class="text-danger">CLEANUP</strong>
                        </label>
                        <input type="text" id="confirmCleanupCategoriesText" class="form-control" placeholder="<?= __("Nhập: CLEANUP"); ?>" autocomplete="off">
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= __("Xóa vĩnh viễn"); ?>',
                cancelButtonText: '<?= __("Hủy"); ?>',
                preConfirm: () => {
                    const text = Swal.getPopup().querySelector('#confirmCleanupCategoriesText').value;
                    if (text !== 'CLEANUP') {
                        Swal.showValidationMessage('<?= __("Vui lòng nhập chính xác từ CLEANUP"); ?>');
                    }
                    return { text: text }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '<?= __("Đang xử lý..."); ?>',
                        text: '<?= __("Vui lòng không đóng trình duyệt"); ?>',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    let cleanup_orphan = cleanupType === 'orphan' ? 1 : 0;
                    let cleanup_empty = cleanupType === 'empty' ? 1 : 0;
                    let cleanup_all = cleanupType === 'all' ? 1 : 0;

                    $.ajax({
                        url: '<?= base_url("ajaxs/admin/remove.php"); ?>',
                        method: 'POST',
                        dataType: 'JSON',
                        data: {
                            action: 'cleanupCategories',
                            token: '<?= $getUser['token']; ?>',
                            cleanup_orphan: cleanup_orphan,
                            cleanup_empty: cleanup_empty,
                            cleanup_all: cleanup_all
                        },
                        success: function(resp) {
                            if (resp.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<?= __("Thành công!"); ?>',
                                    text: resp.msg,
                                    confirmButtonText: '<?= __("Đóng"); ?>'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('<?= __("Lỗi"); ?>', resp.msg, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('<?= __("Lỗi"); ?>', '<?= __("Có lỗi xảy ra khi thực hiện dọn dẹp"); ?>', 'error');
                        }
                    });
                }
            });
        }, 500);
    });
});
</script>

<!-- Modal cập nhật chuyên mục cha -->
<div class="modal fade" id="updateParentModal" tabindex="-1" aria-labelledby="updateParentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateParentModalLabel">
                    <i class="fa-solid fa-folder-tree me-2"></i>Cập nhật chuyên mục cha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <span id="selectedCategoriesInfo">Đã chọn <strong>0</strong> chuyên mục</span>
                </div>

                <div class="mb-3">
                    <label for="newParentSelect" class="form-label">
                        <i class="fa-solid fa-folder me-1"></i>Chọn chuyên mục cha mới:
                    </label>
                    <select class="form-select" id="newParentSelect" required>
                        <option value="">-- Chọn chuyên mục cha --</option>
                        <option value="0">🏠 Chuyên mục gốc (Không có cha)</option>
                        <?php
                        // Load danh sách chuyên mục cha
                        $allParentCategories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = 0 ORDER BY `name` ASC");
                        foreach($allParentCategories as $parentCat):
                        ?>
                        <option value="<?= $parentCat['id']; ?>">
                            📁 <?= $parentCat['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Chọn chuyên mục cha mới cho các chuyên mục đã chọn</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-list me-1"></i>Danh sách chuyên mục sẽ được cập nhật:
                    </label>
                    <div id="selectedCategoriesList" class="border rounded p-3 bg-light"
                        style="max-height: 200px; overflow-y: auto;">
                        <!-- Danh sách sẽ được load bằng JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" id="confirmUpdateParent">
                    <i class="fa-solid fa-save me-1"></i>Cập nhật Parent
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>

<!-- SortableJS được sử dụng thay thế cho jQuery UI Sortable -->

<script>
function updateForm(id) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateTableCategory',
            id: id,
            status: $('#status' + id + ':checked').val()
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

// Hàm updateParentCategoryOrder - lưu ngay lập tức không có độ trễ
function updateParentCategoryOrder(order) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateCategorySTT',
            order: order
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, result.status);
            } else {
                showMessage(result.msg, result.status);
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            showMessage('Đã xảy ra lỗi khi cập nhật thứ tự', 'error');
        }
    });
}

function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeCategory',
            id: id
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
            } else {
                showMessage(result.msg, 'error');
            }
        }
    });
}

function RemoveRow(id) {
    Swal.fire({
        icon: 'question',
        title: "<?=__('Cảnh báo');?>",
        text: "Bạn có chắc chắn muốn xóa chuyên mục ID " + id + " này không?",
        showCancelButton: true,
        confirmButtonText: "Đồng ý",
        cancelButtonText: "Hủy"
    }).then((result) => {
        if (result.isConfirmed) {
            postRemove(id);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    })
}

// Hàm debounce để giảm số lần gọi hàm
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

// Xử lý hiển thị/ẩn form thêm chuyên mục cha
document.addEventListener('DOMContentLoaded', function() {
    const btnAddParent = document.getElementById('btn-add-parent');
    const btnCloseAddParent = document.getElementById('btn-close-add-parent');
    const cardAddParent = document.getElementById('card-add-parent');

    btnAddParent.addEventListener('click', function() {
        cardAddParent.style.display = 'block';
        // Cuộn trang lên vị trí form
        cardAddParent.scrollIntoView({
            behavior: 'smooth'
        });
    });

    btnCloseAddParent.addEventListener('click', function() {
        cardAddParent.style.display = 'none';
    });

    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Tối ưu hiệu năng kéo thả với SortableJS
$(document).ready(function() {
    console.log('jQuery đã sẵn sàng, khởi tạo SortableJS');

    // Khởi tạo SortableJS cho chuyên mục cha
    var sortableParentElement = document.getElementById('sortable-parent-categories');
    if (sortableParentElement) {
        var sortableParent = new Sortable(sortableParentElement, {
            handle: '.handle-parent',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.orphan-category', // Loại trừ chuyên mục cha ảo
            onEnd: function(evt) {
                // Cập nhật thứ tự chuyên mục cha
                var parentOrder = [];
                var items = sortableParentElement.querySelectorAll('li.sortable-parent-item:not(.orphan-category)');
                var total = items.length;

                items.forEach(function(item, index) {
                    var id = item.getAttribute('data-id');
                    if (id) {
                        var reversedPosition = total - index;
                        parentOrder.push({
                            id: id,
                            position: reversedPosition
                        });
                    }
                });

                // Gửi thứ tự mới lên server
                updateParentCategoryOrder(parentOrder);
            }
        });
    }

    // Tối ưu sự kiện collapse
    const clickHandler = debounce(function() {
        const categoryId = $(this).attr('data-bs-target').replace('#category-', '');
        const icon = $(this).find('.collapse-icon[data-category-id="' + categoryId + '"]');

        setTimeout(function() {
            if ($('#category-' + categoryId).hasClass('show')) {
                icon.addClass('rotate-icon');
                localStorage.setItem('last_opened_category', categoryId);
            } else {
                icon.removeClass('rotate-icon');
                if (localStorage.getItem('last_opened_category') === categoryId) {
                    localStorage.removeItem('last_opened_category');
                }
            }
        }, 300);
    }, 50);

    // Đăng ký sự kiện với debounce để tăng hiệu suất
    $('.category-header-content').off('click').on('click', clickHandler);

    // Khôi phục trạng thái tab cuối cùng được mở
    const lastOpenedCategory = localStorage.getItem('last_opened_category');
    if (lastOpenedCategory) {
        // Đóng tất cả các tab trước
        $('.collapse').removeClass('show');

        // Mở tab đã lưu 
        $('#category-' + lastOpenedCategory).addClass('show');

        // Cập nhật biểu tượng mũi tên
        $('.collapse-icon').removeClass('rotate-icon');
        $('.collapse-icon[data-category-id="' + lastOpenedCategory + '"]').addClass('rotate-icon');
    }

    // Xử lý nút đóng tất cả chuyên mục
    $('#collapse-all-btn').on('click', function() {
        $('.collapse').removeClass('show');
        $('.collapse-icon').removeClass('rotate-icon');
        localStorage.removeItem('last_opened_category');
    });

    // Xử lý nút mở tất cả chuyên mục
    $('#expand-all-btn').on('click', function() {
        $('.collapse').addClass('show');
        $('.collapse-icon').addClass('rotate-icon');
    });

    // Khởi tạo sortable cho chuyên mục con
    console.log('Bắt đầu khởi tạo sortable cho chuyên mục con...');
    initChildCategoriesSortable();

    // ============ TÍNH NĂNG CHỌN NHIỀU CHUYÊN MỤC CON ============

    // Xử lý chọn tất cả checkbox trong một nhóm
    $('.select-all-checkbox').on('change', function() {
        const parentId = $(this).data('parent');
        const isChecked = $(this).is(':checked');
        const checkboxes = $(`.category-checkbox[data-parent="${parentId}"]`);

        checkboxes.prop('checked', isChecked);

        // Cập nhật hiển thị
        checkboxes.each(function() {
            updateRowSelection($(this));
        });

        updateBulkActionsVisibility(parentId);
    });

    // Xử lý chọn từng checkbox
    $('.category-checkbox').on('change', function() {
        const parentId = $(this).data('parent');
        updateRowSelection($(this));
        updateSelectAllCheckbox(parentId);
        updateBulkActionsVisibility(parentId);
    });

    // Hàm cập nhật trạng thái visual của hàng
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.is(':checked')) {
            row.addClass('selected-row');
        } else {
            row.removeClass('selected-row');
        }
    }

    // Hàm cập nhật trạng thái checkbox "chọn tất cả"
    function updateSelectAllCheckbox(parentId) {
        const allCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]`);
        const checkedCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]:checked`);
        const selectAllCheckbox = $(`#selectAll${parentId}`);

        if (checkedCheckboxes.length === 0) {
            selectAllCheckbox.prop('indeterminate', false);
            selectAllCheckbox.prop('checked', false);
        } else if (checkedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.prop('indeterminate', false);
            selectAllCheckbox.prop('checked', true);
        } else {
            selectAllCheckbox.prop('indeterminate', true);
            selectAllCheckbox.prop('checked', false);
        }
    }

    // Hàm cập nhật hiển thị khu vực bulk actions
    function updateBulkActionsVisibility(parentId) {
        const checkedCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]:checked`);
        const bulkActions = $(`#bulk-actions-${parentId}`);
        const selectedCount = bulkActions.find('.selected-count strong');

        if (checkedCheckboxes.length > 0) {
            bulkActions.show();
            selectedCount.text(checkedCheckboxes.length);
        } else {
            bulkActions.hide();
            selectedCount.text(0);
        }
    }

    // Xử lý nút cập nhật trạng thái hàng loạt
    $('.bulk-update-status').on('click', function() {
        const parentId = $(this).data('parent');
        const status = $(this).data('status');
        const checkedCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]:checked`);

        if (checkedCheckboxes.length === 0) {
            Swal.fire({
                icon: 'error',
                title: "Lỗi",
                text: "Vui lòng chọn ít nhất một chuyên mục",
                confirmButtonText: "Đóng"
            });
            return;
        }

        const categoryIds = [];
        checkedCheckboxes.each(function() {
            categoryIds.push($(this).val());
        });

        const statusText = status == 1 ? 'Hiển thị' : 'Ẩn';
        const btn = $(this);

        // Thông báo xác nhận khác nhau cho orphan categories
        let confirmTitle, confirmMessage;
        if (parentId === 'orphan') {
            confirmTitle = "🔧 Cập nhật trạng thái chuyên mục chưa có cha";
            confirmMessage =
                `Bạn có chắc chắn muốn ${statusText.toLowerCase()} ${categoryIds.length} chuyên mục chưa có cha hợp lệ?\n\n💡 Gợi ý: Thay vì chỉ ${statusText.toLowerCase()}, bạn có thể sửa parent_id để gán các chuyên mục này vào chuyên mục cha phù hợp.`;
        } else {
            confirmTitle = "Xác nhận";
            confirmMessage =
                `Bạn có chắc chắn muốn ${statusText.toLowerCase()} ${categoryIds.length} chuyên mục đã chọn?`;
        }

        Swal.fire({
            icon: 'question',
            title: confirmTitle,
            text: confirmMessage,
            showCancelButton: true,
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Hủy"
        }).then((result) => {
            if (result.isConfirmed) {
                // Thêm loading state
                btn.addClass('loading');
                btn.prop('disabled', true);

                $.ajax({
                    url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'bulk_update_category_status',
                        category_ids: categoryIds,
                        new_status: status
                    },
                    success: function(result) {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);

                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            // Cập nhật UI
                            checkedCheckboxes.each(function() {
                                const categoryId = $(this).val();
                                const statusSwitch = $(
                                    `#status${categoryId}`);
                                statusSwitch.prop('checked', status == 1);
                            });
                            // Reset selection
                            resetSelection(parentId);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);
                        showMessage('Đã xảy ra lỗi khi cập nhật', 'error');
                    }
                });
            }
        });
    });

    // Xử lý nút xóa hàng loạt
    $('.bulk-remove-categories').on('click', function() {
        const parentId = $(this).data('parent');
        const checkedCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]:checked`);

        if (checkedCheckboxes.length === 0) {
            Swal.fire({
                icon: 'error',
                title: "Lỗi",
                text: "Vui lòng chọn ít nhất một chuyên mục",
                confirmButtonText: "Đóng"
            });
            return;
        }

        const categoryIds = [];
        const categoryNames = [];
        checkedCheckboxes.each(function() {
            categoryIds.push($(this).val());
            categoryNames.push($(this).data('name'));
        });

        const btn = $(this);

        // Thông báo xác nhận khác nhau cho orphan categories
        let confirmTitle, confirmMessage;
        if (parentId === 'orphan') {
            confirmTitle = "⚠️ Xóa chuyên mục chưa có cha";
            confirmMessage =
                `Bạn có chắc chắn muốn xóa ${categoryIds.length} chuyên mục chưa có cha hợp lệ?\n\n🗂️ Danh sách: ${categoryNames.slice(0, 3).join(', ')}${categoryNames.length > 3 ? '...' : ''}\n\n⚠️ Lưu ý: Đây là những chuyên mục có parent_id không hợp lệ. Bạn nên cân nhắc sửa parent_id thay vì xóa.`;
        } else {
            confirmTitle = "Cảnh báo";
            confirmMessage =
                `Bạn có chắc chắn muốn xóa ${categoryIds.length} chuyên mục đã chọn?\n\nDanh sách: ${categoryNames.slice(0, 3).join(', ')}${categoryNames.length > 3 ? '...' : ''}`;
        }

        Swal.fire({
            icon: 'question',
            title: confirmTitle,
            text: confirmMessage,
            showCancelButton: true,
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Hủy"
        }).then((result) => {
            if (result.isConfirmed) {
                // Thêm loading state
                btn.addClass('loading');
                btn.prop('disabled', true);

                $.ajax({
                    url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'bulk_remove_categories',
                        category_ids: categoryIds
                    },
                    success: function(result) {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);

                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            // Xóa các hàng khỏi bảng
                            checkedCheckboxes.each(function() {
                                const categoryId = $(this).val();
                                const itemSelector = parentId === 'orphan' ?
                                    `#orphan-item-${categoryId}` :
                                    `#child-item-${categoryId}`;
                                $(itemSelector).fadeOut(300, function() {
                                    $(this).remove();
                                });
                            });
                            // Reset selection
                            setTimeout(() => {
                                resetSelection(parentId);
                                // Reload page nếu không còn chuyên mục con nào
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            }, 500);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);
                        showMessage('Đã xảy ra lỗi khi xóa', 'error');
                    }
                });
            }
        });
    });

    // Hàm reset selection
    function resetSelection(parentId) {
        $(`.category-checkbox[data-parent="${parentId}"]`).prop('checked', false);
        $(`#selectAll${parentId}`).prop('checked', false).prop('indeterminate', false);
        $(`.category-checkbox[data-parent="${parentId}"]`).closest('tr').removeClass('selected-row');
        $(`#bulk-actions-${parentId}`).hide();
    }

    // ============ TÍNH NĂNG CẬP NHẬT PARENT CATEGORY ============

    // Biến lưu trữ thông tin hiện tại
    let currentParentContext = null;
    let currentSelectedCategories = [];

    // Xử lý nút cập nhật parent
    $('.bulk-update-parent').on('click', function() {
        const parentId = $(this).data('parent');
        const checkedCheckboxes = $(`.category-checkbox[data-parent="${parentId}"]:checked`);

        if (checkedCheckboxes.length === 0) {
            Swal.fire({
                icon: 'error',
                title: "Lỗi",
                text: "Vui lòng chọn ít nhất một chuyên mục",
                confirmButtonText: "Đóng"
            });
            return;
        }

        // Lưu trữ context hiện tại
        currentParentContext = parentId;
        currentSelectedCategories = [];

        const categoryIds = [];
        const categoryNames = [];
        checkedCheckboxes.each(function() {
            const id = $(this).val();
            const name = $(this).data('name');
            categoryIds.push(id);
            categoryNames.push(name);
            currentSelectedCategories.push({
                id: id,
                name: name
            });
        });

        // Cập nhật thông tin trong modal
        $('#selectedCategoriesInfo strong').text(categoryIds.length);

        // Hiển thị danh sách chuyên mục được chọn
        let categoryListHtml = '';
        categoryNames.forEach((name, index) => {
            const badgeClass = parentId === 'orphan' ? 'bg-warning' : 'bg-info';
            categoryListHtml +=
                `<span class="badge ${badgeClass} me-1 mb-1">${index + 1}. ${name}</span>`;
        });
        $('#selectedCategoriesList').html(categoryListHtml);

        // Reset dropdown
        $('#newParentSelect').val('');

        // Cập nhật tiêu đề modal dựa trên context
        if (parentId === 'orphan') {
            $('#updateParentModalLabel').html(
                '<i class="fa-solid fa-link me-2"></i>Gán chuyên mục cha cho các chuyên mục orphan');
        } else {
            $('#updateParentModalLabel').html(
                '<i class="fa-solid fa-folder-tree me-2"></i>Cập nhật chuyên mục cha');
        }

        // Hiển thị modal
        $('#updateParentModal').modal('show');
    });

    // Xử lý nút xác nhận cập nhật parent
    $('#confirmUpdateParent').on('click', function() {
        const newParentId = $('#newParentSelect').val();

        if (newParentId === '') {
            Swal.fire({
                icon: 'error',
                title: "Lỗi",
                text: "Vui lòng chọn chuyên mục cha mới",
                confirmButtonText: "Đóng"
            });
            return;
        }

        if (currentSelectedCategories.length === 0) {
            Swal.fire({
                icon: 'error',
                title: "Lỗi",
                text: "Không có chuyên mục nào được chọn",
                confirmButtonText: "Đóng"
            });
            return;
        }

        const categoryIds = currentSelectedCategories.map(cat => cat.id);
        const categoryNames = currentSelectedCategories.map(cat => cat.name);
        const parentName = $('#newParentSelect option:selected').text();

        const btn = $(this);

        // Xác nhận trước khi cập nhật
        Swal.fire({
            icon: 'question',
            title: "🔄 Xác nhận cập nhật chuyên mục cha",
            text: `Bạn có chắc chắn muốn cập nhật ${categoryIds.length} chuyên mục sau:\n\n📋 ${categoryNames.slice(0, 3).join(', ')}${categoryNames.length > 3 ? '...' : ''}\n\n🎯 Về chuyên mục cha: "${parentName.replace(/^(🏠|📁)\s/, '')}"`,
            showCancelButton: true,
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Hủy"
        }).then((result) => {
            if (result.isConfirmed) {
                // Thêm loading state
                btn.addClass('loading');
                btn.prop('disabled', true);

                $.ajax({
                    url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'bulk_update_parent_category',
                        category_ids: categoryIds,
                        new_parent_id: newParentId
                    },
                    success: function(result) {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);

                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');

                            // Đóng modal
                            $('#updateParentModal').modal('hide');

                            // Reset selection
                            if (currentParentContext) {
                                resetSelection(currentParentContext);
                            }

                            // Reload page sau 2 giây để cập nhật UI
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        btn.removeClass('loading');
                        btn.prop('disabled', false);
                        showMessage('Đã xảy ra lỗi khi cập nhật chuyên mục cha',
                            'error');
                    }
                });
            }
        });
    });

    // Reset khi đóng modal
    $('#updateParentModal').on('hidden.bs.modal', function() {
        currentParentContext = null;
        currentSelectedCategories = [];
        $('#newParentSelect').val('');
        $('#selectedCategoriesList').html('');
    });
});

// Hàm khởi tạo SortableJS cho các bảng chuyên mục con
function initChildCategoriesSortable() {
    const tbodyElements = document.querySelectorAll('.child-table tbody');
    console.log('Tìm thấy', tbodyElements.length, 'bảng chuyên mục con');
    
    tbodyElements.forEach(function(element, index) {
        // Tìm parent ID từ data-parent của các row
        const firstRow = element.querySelector('tr[data-parent]');
        if (!firstRow) {
            console.log('Bảng', index, ': Không tìm thấy row với data-parent');
            return;
        }
        
        const parentId = firstRow.getAttribute('data-parent');
        if (!parentId) {
            console.log('Bảng', index, ': Không có parent ID');
            return;
        }
        
        console.log('Khởi tạo sortable cho parent ID:', parentId, 'với', element.querySelectorAll('tr').length, 'rows');
        
        try {
            const sortable = new Sortable(element, {
                handle: '.handle-child',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: function(evt) {
                    console.log('Bắt đầu kéo thả chuyên mục con');
                },
                onEnd: function(evt) {
                    console.log('Kéo thả chuyên mục con hoàn thành');
                    
                    // Thu thập dữ liệu vị trí mới cho các chuyên mục con
                    const childOrder = [];
                    const rows = element.querySelectorAll('tr[data-parent="' + parentId + '"]');
                    const total = rows.length;
                    
                    rows.forEach(function(row, index) {
                        let id = null;
                        if (row.id.startsWith('child-item-')) {
                            id = row.id.replace('child-item-', '');
                        } else if (row.id.startsWith('orphan-item-')) {
                            id = row.id.replace('orphan-item-', '');
                        }
                        
                        if (id) {
                            const reversedPosition = total - index;
                            childOrder.push({
                                id: id,
                                position: reversedPosition
                            });
                        }
                    });
                    
                    console.log('Dữ liệu thứ tự mới:', childOrder);
                    
                    // Gửi dữ liệu vị trí mới lên server
                    updateChildCategoryOrder(childOrder, parentId);
                }
            });
            
            console.log('SortableJS đã được khởi tạo thành công cho parent ID:', parentId);
        } catch (error) {
            console.error('Lỗi khi khởi tạo SortableJS:', error);
        }
    });
}

// Hàm cập nhật thứ tự chuyên mục con lên server - lưu ngay lập tức không có độ trễ
function updateChildCategoryOrder(order, parentId) {
    console.log('Gọi updateChildCategoryOrder với:', order, 'parentId:', parentId);
    console.log('Gửi AJAX request cập nhật thứ tự chuyên mục con...');
    
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateChildCategorySTT',
            order: order
        },
        success: function(result) {
            console.log('Kết quả AJAX:', result);
            if (result.status == 'success') {
                showMessage(result.msg, result.status);
            } else {
                showMessage(result.msg || 'Lỗi không xác định', result.status);
            }
        },
        error: function(xhr) {
            console.error('Lỗi AJAX:', xhr.responseText);
            showMessage('Đã xảy ra lỗi khi cập nhật thứ tự', 'error');
        }
    });
}

// ========== ORPHAN TAB FUNCTIONS ==========

// Gán chuyên mục cha cho chuyên mục mồ côi
function assignOrphanParent(id) {
    var parentId = $('#orphan-parent-' + id).val();
    if (!parentId) {
        showMessage('Vui lòng chọn chuyên mục cha', 'error');
        return;
    }
    $.ajax({
        url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'update_category_category',
            id: id,
            category_id: parentId
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
                // Ẩn row với animation
                $('#orphan-row-' + id).fadeOut(400, function() {
                    $(this).remove();
                    // Cập nhật badge count
                    var remaining = $('#tab-orphan tbody tr').length;
                    if (remaining === 0) {
                        location.reload();
                    } else {
                        $('#orphan-tab .badge').text(remaining);
                    }
                });
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            showMessage('Đã xảy ra lỗi', 'error');
        }
    });
}

// Toggle chọn tất cả orphan
function toggleOrphanSelectAll(el) {
    var checkboxes = document.querySelectorAll('.orphan-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = el.checked;
    });
    updateOrphanSelection();
}

// Cập nhật toolbar khi thay đổi selection
function updateOrphanSelection() {
    var checked = document.querySelectorAll('.orphan-checkbox:checked');
    var toolbar = document.getElementById('orphan-bulk-toolbar');
    var countEl = document.getElementById('orphan-selected-count');
    countEl.textContent = checked.length;
    if (checked.length > 0) {
        toolbar.style.cssText = 'display: flex !important;';
    } else {
        toolbar.style.cssText = 'display: none !important;';
    }
    // Sync select-all checkbox
    var total = document.querySelectorAll('.orphan-checkbox').length;
    document.getElementById('orphan-select-all').checked = (checked.length === total && total > 0);
}

// Gán hàng loạt chuyên mục cha
function bulkAssignOrphanParent() {
    var parentId = $('#orphan-bulk-parent').val();
    if (!parentId) {
        showMessage('Vui lòng chọn chuyên mục cha', 'error');
        return;
    }
    var checked = document.querySelectorAll('.orphan-checkbox:checked');
    if (checked.length === 0) {
        showMessage('Vui lòng chọn ít nhất một chuyên mục', 'error');
        return;
    }
    var ids = Array.from(checked).map(function(cb) {
        return cb.value;
    });
    var completed = 0;
    var failed = 0;
    ids.forEach(function(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'update_category_category',
                id: id,
                category_id: parentId
            },
            success: function(result) {
                if (result.status == 'success') {
                    $('#orphan-row-' + id).fadeOut(300, function() {
                        $(this).remove();
                    });
                    completed++;
                } else {
                    failed++;
                }
            },
            error: function() {
                failed++;
            },
            complete: function() {
                if (completed + failed === ids.length) {
                    if (completed > 0) {
                        showMessage('Đã gán thành công ' + completed + ' chuyên mục', 'success');
                    }
                    if (failed > 0) {
                        showMessage('Có ' + failed + ' chuyên mục gán thất bại', 'error');
                    }
                    // Cập nhật UI
                    setTimeout(function() {
                        var remaining = $('#tab-orphan tbody tr').length;
                        if (remaining === 0) {
                            location.reload();
                        } else {
                            $('#orphan-tab .badge').text(remaining);
                            $('#orphan-selected-count').text('0');
                            $('#orphan-bulk-toolbar').hide();
                            $('#orphan-select-all').prop('checked', false);
                        }
                    }, 500);
                }
            }
        });
    });
}

// Xóa hàng loạt chuyên mục mồ côi đã chọn
function bulkDeleteOrphans() {
    var checked = document.querySelectorAll('.orphan-checkbox:checked');
    if (checked.length === 0) {
        showMessage('Vui lòng chọn ít nhất một chuyên mục', 'error');
        return;
    }

    // Thu thập danh sách ID và tên chuyên mục đã chọn
    var ids = [];
    var names = [];
    checked.forEach(function(cb) {
        ids.push(cb.value);
        // Lấy tên chuyên mục từ cột tên trong cùng hàng
        var row = cb.closest('tr');
        var nameEl = row ? row.querySelector('.fw-bold') : null;
        if (nameEl) {
            names.push(nameEl.textContent.trim());
        }
    });

    // Hiển thị danh sách tên (tối đa 5) để admin xác nhận
    var nameList = names.slice(0, 5).join(', ');
    if (names.length > 5) nameList += '...';

    // Xác nhận bằng SweetAlert2 trước khi xóa
    Swal.fire({
        icon: 'warning',
        title: '⚠️ Xóa ' + ids.length + ' chuyên mục mồ côi',
        html: '<div class="text-start">' +
            '<p>Bạn có chắc chắn muốn xóa <strong>' + ids.length + '</strong> chuyên mục mồ côi đã chọn?</p>' +
            '<p class="text-muted mb-1"><small>📋 ' + nameList + '</small></p>' +
            '<div class="alert alert-danger py-2 mt-2 mb-0"><small><i class="fa-solid fa-triangle-exclamation me-1"></i>Hành động này không thể hoàn tác!</small></div>' +
            '</div>',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i>Xóa vĩnh viễn',
        cancelButtonText: 'Hủy'
    }).then(function(result) {
        if (result.isConfirmed) {
            // Hiển thị loading
            Swal.fire({
                title: '<?= __("Đang xử lý..."); ?>',
                text: '<?= __("Vui lòng không đóng trình duyệt"); ?>',
                allowOutsideClick: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });

            // Gọi AJAX xóa hàng loạt — tái sử dụng action bulk_remove_categories
            $.ajax({
                url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'bulk_remove_categories',
                    category_ids: ids
                },
                success: function(resp) {
                    if (resp.status == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<?= __("Thành công!"); ?>',
                            text: resp.msg,
                            confirmButtonText: '<?= __("Đóng"); ?>'
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('<?= __("Lỗi"); ?>', resp.msg, 'error');
                    }
                },
                error: function() {
                    Swal.fire('<?= __("Lỗi"); ?>', '<?= __("Có lỗi xảy ra khi xóa chuyên mục"); ?>', 'error');
                }
            });
        }
    });
}
</script>