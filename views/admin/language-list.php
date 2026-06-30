<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Danh sách ngôn ngữ'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
<style>
.language-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    margin-bottom: 15px;
}
.language-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.language-card.ui-sortable-helper {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    z-index: 1000;
}
.language-handle {
    cursor: move;
    color: #6c757d;
    font-size: 18px;
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.language-handle:hover {
    color: #495057;
    background-color: #f8f9fa;
}
.language-flag {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #e9ecef;
}
.language-info h5 {
    margin: 0;
    font-weight: 600;
    color: #495057;
}
.language-info p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}
.priority-badge {
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.8rem;
}
.default-badge {
    background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
    color: white;
    font-weight: 600;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.8rem;
}
.ui-state-highlight {
    height: 80px;
    background-color: #e3f2fd;
    border: 2px dashed #2196f3;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2196f3;
    font-weight: 500;
}
.ui-state-highlight::before {
    content: "Thả vào đây";
    font-size: 0.9rem;
}
.sorting-active .language-card:not(.ui-sortable-helper) {
    opacity: 0.6;
}
.no-transition * {
    transition: none !important;
}
.touch-active {
    background-color: #e3f2fd;
    color: #1976d2;
}

/* Modal Styles */
.bg-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.modal-content {
    border-radius: 15px;
    overflow: hidden;
}
.modal-header {
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}
.modal-body {
    padding: 2rem;
}
.modal-footer {
    border-radius: 0 0 15px 15px;
    padding: 1.5rem;
}
.avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.avatar-sm {
    width: 35px;
    height: 35px;
}
.bg-white-transparent {
    background-color: rgba(255,255,255,0.2);
}
.form-floating > label {
    font-weight: 500;
}
.form-control.border-2 {
    border-width: 2px !important;
}
.border-primary-subtle {
    border-color: rgba(13, 110, 253, 0.25) !important;
}
.border-info-subtle {
    border-color: rgba(13, 202, 240, 0.25) !important;
}
.border-warning-subtle {
    border-color: rgba(255, 193, 7, 0.25) !important;
}
.border-success-subtle {
    border-color: rgba(25, 135, 84, 0.25) !important;
}
.bg-primary-transparent {
    background-color: rgba(13, 110, 253, 0.1);
}
.bg-success-transparent {
    background-color: rgba(25, 135, 84, 0.1);
}
.bg-warning-transparent {
    background-color: rgba(255, 193, 7, 0.1);
}
.bg-info-transparent {
    background-color: rgba(13, 202, 240, 0.1);
}
.alert-primary-transparent {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}
.progress-stacked {
    display: flex;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    background-color: #e9ecef;
}
.progress-stacked .progress {
    background-color: transparent;
    margin: 0;
    border-radius: 0;
}
.progress-stacked .progress:first-child .progress-bar {
    border-radius: 3px 0 0 3px;
}
.progress-stacked .progress:last-child .progress-bar {
    border-radius: 0 3px 3px 0;
}
.form-control.is-valid {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}
.form-control.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.object-fit-cover {
    object-fit: cover;
}

@media (max-width: 768px) {
    .language-card .row {
        align-items: center;
    }
    .language-actions {
        justify-content: center;
        margin-top: 10px;
    }
    .modal-dialog {
        margin: 0.5rem;
    }
    .modal-body {
        padding: 1.5rem;
    }
}
</style>
';
$body['footer'] = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_lang') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddLang'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used as this is a demo site.') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    $icon = '';
    if (check_img('icon') == true) {
        $rand = check_string($_POST['lang']);
        $uploads_dir = "assets/storage/flags/flag_$rand.png";
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addIcon = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addIcon) {
            $icon = "assets/storage/flags/flag_$rand.png";
        } else {
            $icon = '';
        }
    }

    // Lấy stt cao nhất và +1 để đặt ngôn ngữ mới lên đầu
    $maxStt = $CMSNT->get_row("SELECT MAX(stt) as max_stt FROM `languages`");
    $newStt = ($maxStt['max_stt'] ?? 0) + 1;

    $isInsert = $CMSNT->insert("languages", [
        'icon'  => $icon,
        'lang'  => check_string($_POST['lang']),
        'code'  => check_string($_POST['code']),
        'stt'   => $newStt,
        'status'    => check_string($_POST['status'])
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Thêm ngôn ngữ') . " (" . $_POST['lang'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Thêm ngôn ngữ') . " (" . $_POST['lang'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm ngôn ngữ thành công!') . '")){location.href = "' . base_url_admin('language-list') . '";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm ngôn ngữ thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}
if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình ngôn ngữ')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình ngôn ngữ'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <i class="fa fa-language me-2"></i><?= __('Quản lý ngôn ngữ'); ?>
            </h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page"><?= __('Ngôn ngữ'); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (!extension_loaded('fileinfo')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fa fa-exclamation-triangle fs-20 me-3"></i>
                    <div>
                        <strong><?= __('Thiếu PHP Extension'); ?>:</strong> <?= __('Extension'); ?> <code>fileinfo</code> <?= __('chưa được cài đặt. Vui lòng kích hoạt extension này trong'); ?> <code>php.ini</code> <?= __('để tính năng upload ảnh cờ quốc gia hoạt động chính xác.'); ?>
                        <br><small class="text-muted"><?= __('Thêm hoặc bỏ comment dòng'); ?> <code>extension=fileinfo</code> <?= __('trong file'); ?> <code>php.ini</code> <?= __('và khởi động lại web server.'); ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fa fa-cog me-2"></i><?= __('Cấu hình ngôn ngữ'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label"
                                            for="example-hf-email"><?= __('Loại'); ?></label>
                                        <div class="col-sm-8">
                                            <select class="form-control" id="language_type" name="language_type">
                                                <option
                                                    <?= $CMSNT->site('language_type') == 'manual' ? 'selected' : ''; ?>
                                                    value="manual"><?= __('Dịch thủ công'); ?>
                                                </option>
                                                <option
                                                    <?= $CMSNT->site('language_type') == 'gtranslate' ? 'selected' : ''; ?>
                                                    value="gtranslate"><?= __('Gtranslate.io'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12" id="gtranslate_script" style="display:none;">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Gtranslate Script'); ?></label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" rows="5" name="gtranslate_script"><?= $CMSNT->site('gtranslate_script'); ?></textarea>
                                            <small><?= __('Truy cập vào'); ?> <a href="https://gtranslate.io/website-translator-widget" class="text-primary" target="_blank"><?= __('gtranslate.io'); ?></a> <?= __('để tạo mã sciprt theo nhu cầu của bạn, hoặc sử dụng sciprt mặc định của chúng tôi cung cấp.'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" name="SaveSettings" class="btn btn-primary btn-block"><i
                                        class="fa fa-fw fa-save me-1"></i>
                                    <?= __('Save'); ?></button>
                            </div>
                        </form>
                        <p><?= __('Hướng dẫn sử dụng tính năng đa ngôn ngữ'); ?>: <a target="_blank" class="text-primary" href="https://help.cmsnt.co/danh-muc/huong-dan-su-dung-tinh-nang-da-ngon-ngu-trong-smmpanel2/">https://help.cmsnt.co/danh-muc/huong-dan-su-dung-tinh-nang-da-ngon-ngu-trong-smmpanel2/</a></p>
                    </div>
                </div>
            </div>

            <div class="col-xl-6" id="language_stats">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fa fa-chart-bar me-2"></i><?= __('Thống kê ngôn ngữ'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $totalLangs = $CMSNT->num_rows("SELECT * FROM `languages`");
                        $activeLangs = $CMSNT->num_rows("SELECT * FROM `languages` WHERE `status` = 1");
                        $defaultLang = $CMSNT->get_row("SELECT * FROM `languages` WHERE `lang_default` = 1");
                        ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="p-3 bg-primary-transparent rounded">
                                    <h4 class="mb-1 text-primary"><?= format_cash($totalLangs); ?></h4>
                                    <p class="text-muted mb-0 fs-12"><?= __('Tổng ngôn ngữ'); ?></p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-success-transparent rounded">
                                    <h4 class="mb-1 text-success"><?= format_cash($activeLangs); ?></h4>
                                    <p class="text-muted mb-0 fs-12"><?= __('Đang hoạt động'); ?></p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-warning-transparent rounded">
                                    <h4 class="mb-1 text-warning"><?= $defaultLang['lang'] ?? 'N/A'; ?></h4>
                                    <p class="text-muted mb-0 fs-12"><?= __('Mặc định'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12" id="language_list_section">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <i class="fa fa-list me-2"></i><?= __('DANH SÁCH NGÔN NGỮ'); ?>
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#addLanguageModal"
                            class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light">
                            <i class="ri-add-line fw-semibold align-middle me-1"></i> <?= __('Thêm ngôn ngữ mới'); ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        $languages = $CMSNT->get_list("SELECT * FROM `languages` ORDER BY `stt` DESC, `id` DESC");
                        if (count($languages) > 0):
                        ?>
                            <div class="alert alert-info mb-4">
                                <i class="fa fa-info-circle me-2"></i>
                                <?= __('Bạn có thể kéo thả các ngôn ngữ để sắp xếp thứ tự ưu tiên. Nhấp vào biểu tượng'); ?>
                                <i class="fa fa-grip-vertical"></i> <?= __('và kéo thả để thay đổi vị trí.'); ?>
                            </div>

                            <div id="sortable-languages">
                                <?php foreach ($languages as $row): ?>
                                    <div class="card language-card" id="language-item-<?= $row['id']; ?>" data-id="<?= $row['id']; ?>">
                                        <div class="card-body p-3">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <div class="language-handle">
                                                        <i class="fa fa-grip-vertical"></i>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <img class="language-flag" src="<?= base_url($row['icon']); ?>" alt="<?= $row['lang']; ?>">
                                                </div>
                                                <div class="col">
                                                    <div class="language-info">
                                                        <h5><?= $row['lang']; ?></h5>
                                                        <p><?= __('Mã ISO'); ?>: <strong><?= $row['code']; ?></strong></p>
                                                        <small class="text-muted">
                                                            <i class="fa fa-link me-1"></i>
                                                            <a href="<?= base_url($row['code']); ?>" target="_blank" class="text-decoration-none">
                                                                <?= base_url($row['code']); ?>
                                                            </a>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <span class="priority-badge">
                                                            <i class="fa fa-sort-numeric-up me-1"></i><?= $row['stt']; ?>
                                                        </span>
                                                        <?php if ($row['lang_default'] == 1): ?>
                                                            <span class="default-badge">
                                                                <i class="fa fa-star me-1"></i><?= __('Mặc định'); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="status<?= $row['id']; ?>" value="1"
                                                                <?= $row['status'] == 1 ? 'checked' : ''; ?>
                                                                onchange="updateLanguage('<?= $row['id']; ?>')"
                                                                title="<?= __('Bật/tắt ngôn ngữ'); ?>">
                                                            <input type="hidden" id="stt<?= $row['id']; ?>" value="<?= $row['stt']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="language-actions d-flex gap-1 flex-wrap">
                                                        <button type="button" onclick="setDefault('<?= $row['id']; ?>')"
                                                            class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                                                            title="<?= __('Đặt làm mặc định'); ?>">
                                                            <i class="fa fa-star"></i>
                                                        </button>
                                                        <a href="<?= base_url_admin('translate-list&id=' . $row['id']); ?>"
                                                            class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                            title="<?= __('Dịch thuật'); ?>">
                                                            <i class="fa fa-language"></i>
                                                        </a>
                                                        <a href="<?= base_url_admin('language-edit&id=' . $row['id']); ?>"
                                                            class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                            title="<?= __('Chỉnh sửa'); ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <button type="button" onclick="RemoveRow('<?= $row['id']; ?>')"
                                                            class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip"
                                                            title="<?= __('Xóa'); ?>">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                <?= __('Chưa có ngôn ngữ nào trong hệ thống.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm ngôn ngữ mới -->
<div class="modal fade" id="addLanguageModal" tabindex="-1" aria-labelledby="addLanguageModalLabel" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header bg-primary-gradient border-0 text-white">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm bg-white-transparent rounded me-3">
                        <i class="ri-translate-2 fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-semibold mb-0 text-white" id="addLanguageModalLabel"><?= __('Thêm ngôn ngữ mới'); ?></h5>
                        <small class="opacity-75"><?= __('Mở rộng hỗ trợ đa ngôn ngữ cho hệ thống'); ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form action="" method="POST" enctype="multipart/form-data" id="addLanguageForm" novalidate>
                <input type="hidden" name="AddLang" value="1">
                <div class="modal-body p-4">
                    <!-- Progress Steps -->
                    <div class="progress-stacked mb-4">
                        <div class="progress" role="progressbar" style="width: 25%">
                            <div class="progress-bar bg-primary"></div>
                        </div>
                        <div class="progress" role="progressbar" style="width: 25%">
                            <div class="progress-bar bg-secondary"></div>
                        </div>
                        <div class="progress" role="progressbar" style="width: 25%">
                            <div class="progress-bar bg-secondary"></div>
                        </div>
                        <div class="progress" role="progressbar" style="width: 25%">
                            <div class="progress-bar bg-secondary"></div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Tên ngôn ngữ -->
                        <div class="col-xl-6">
                            <div class="form-floating">
                                <input type="text" class="form-control border-2 border-primary-subtle" name="lang" id="languageName"
                                    placeholder="<?= __('Nhập tên ngôn ngữ'); ?>" required>
                                <label for="languageName" class="text-primary fw-medium">
                                    <i class="ri-global-line me-2"></i><?= __('Tên ngôn ngữ'); ?> <span class="text-danger">*</span>
                                </label>
                                <div class="invalid-feedback">
                                    <?= __('Vui lòng nhập tên ngôn ngữ hợp lệ'); ?>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="ri-information-line me-1"></i><?= __('VD: Tiếng Việt, English, 中文, 日本語'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- ISO Code -->
                        <div class="col-xl-6">
                            <div class="form-floating">
                                <input type="text" class="form-control border-2 border-info-subtle text-lowercase" name="code" id="languageCode"
                                    placeholder="<?= __('Mã ISO'); ?>" maxlength="5" required style="text-transform: lowercase;">
                                <label for="languageCode" class="text-info fw-medium">
                                    <i class="ri-code-s-slash-line me-2"></i><?= __('Mã ISO Code'); ?> <span class="text-danger">*</span>
                                </label>
                                <div class="invalid-feedback">
                                    <?= __('Vui lòng nhập mã ISO hợp lệ (2-5 ký tự chữ thường)'); ?>
                                </div>
                                <div class="form-text text-muted">
                                    <i class="ri-information-line me-1"></i>
                                    <?= __('Mã ISO 639-1: VD'); ?> <code>vi</code>, <code>en</code>, <code>zh</code>, <code>ja</code>
                                    <a href="https://www.loc.gov/standards/iso639-2/php/code_list.php" target="_blank" class="text-primary ms-2">
                                        <i class="ri-external-link-line"></i> <?= __('Xem danh sách ISO'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Flag Upload -->
                        <div class="col-xl-12">
                            <div class="card border-2 border-warning-subtle bg-warning-transparent">
                                <div class="card-body p-3">
                                    <label class="form-label text-warning fw-semibold mb-3">
                                        <i class="ri-flag-line me-2"></i><?= __('Cờ quốc gia'); ?> <span class="text-danger">*</span>
                                    </label>

                                    <div class="d-flex align-items-start gap-3">
                                        <!-- Preview -->
                                        <div class="flex-shrink-0">
                                            <div id="flagPreview" class="border border-2 border-dashed border-secondary rounded d-flex align-items-center justify-content-center"
                                                style="width: 80px; height: 60px; background: #f8f9fa;">
                                                <i class="ri-image-line text-muted fs-20"></i>
                                            </div>
                                        </div>

                                        <!-- Upload -->
                                        <div class="flex-grow-1">
                                            <input type="file" class="form-control border-2 border-warning-subtle" name="icon" id="flagUpload"
                                                accept="image/*" required>
                                            <div class="invalid-feedback">
                                                <?= __('Vui lòng chọn file ảnh cho cờ quốc gia'); ?>
                                            </div>
                                            <div class="form-text text-muted">
                                                <i class="ri-information-line me-1"></i>
                                                <?= __('Kích thước khuyến nghị: 64x48px, định dạng PNG/JPG, tối đa 2MB'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trạng thái -->
                        <div class="col-xl-12">
                            <div class="card border-2 border-success-subtle bg-success-transparent">
                                <div class="card-body p-3">
                                    <label class="form-label text-success fw-semibold mb-3">
                                        <i class="ri-settings-3-line me-2"></i><?= __('Cấu hình hiển thị'); ?>
                                    </label>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select border-2 border-success-subtle" name="status" id="languageStatus" required>
                                                    <option value="1" selected><?= __('Hiển thị công khai'); ?></option>
                                                    <option value="0"><?= __('Ẩn tạm thời'); ?></option>
                                                </select>
                                                <label for="languageStatus" class="text-success">
                                                    <i class="ri-eye-line me-2"></i><?= __('Trạng thái'); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="alert alert-info border-0 mb-0">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-lightbulb-line fs-16"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <small class="fw-medium"><?= __('Gợi ý'); ?></small>
                                                        <p class="mb-0 fs-12"><?= __('Bạn có thể ẩn ngôn ngữ để hoàn thiện bản dịch trước khi công khai'); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hướng dẫn nhanh -->
                    <div class="alert alert-primary-transparent border-primary-subtle mt-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="ri-information-line fs-20 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-primary fw-semibold mb-2"><?= __('Hướng dẫn nhanh'); ?></h6>
                                <ul class="mb-0 fs-13 text-muted">
                                    <li><?= __('Tên ngôn ngữ nên sử dụng ngôn ngữ gốc để người dùng dễ nhận biết'); ?></li>
                                    <li><?= __('Mã ISO cần chính xác để hỗ trợ tính năng dịch tự động'); ?></li>
                                    <li><?= __('Sau khi thêm, bạn có thể kéo thả để sắp xếp thứ tự ưu tiên'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light border-0 p-4">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div class="text-muted fs-13">
                            <i class="ri-shield-check-line me-1"></i>
                            <?= __('Dữ liệu sẽ được lưu trữ an toàn'); ?>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1"></i><?= __('Hủy bỏ'); ?>
                            </button>
                            <button type="submit" class="btn btn-primary btn-wave fw-medium" id="submitBtn">
                                <i class="ri-add-line me-1"></i><?= __('Thêm ngôn ngữ'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Lắng nghe sự kiện thay đổi của select
        document.getElementById('language_type').addEventListener('change', function() {
            var selectedValue = this.value;
            if (selectedValue === 'gtranslate') {
                document.getElementById('language_list_section').style.display = 'none';
                document.getElementById('language_stats').style.display = 'none';
                document.getElementById('gtranslate_script').style.display = 'block';
            } else {
                document.getElementById('language_list_section').style.display = 'block';
                document.getElementById('language_stats').style.display = 'block';
                document.getElementById('gtranslate_script').style.display = 'none';
            }
        });

        // Kích hoạt sự kiện change để ẩn/hiện ban đầu
        var initialSelectedValue = document.getElementById('language_type').value;
        if (initialSelectedValue === 'gtranslate') {
            document.getElementById('language_list_section').style.display = 'none';
            document.getElementById('language_stats').style.display = 'none';
            document.getElementById('gtranslate_script').style.display = 'block';
        }

        // Khởi tạo tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<!-- jQuery UI Touch Punch cho hỗ trợ mobile -->
<script>
    /*!
     * jQuery UI Touch Punch 0.2.3
     * Hỗ trợ kéo thả trên thiết bị di động
     */
    (function($) {
        $.support.touch = 'ontouchend' in document;
        if (!$.support.touch) {
            return;
        }

        var mouseProto = $.ui.mouse.prototype,
            _mouseInit = mouseProto._mouseInit,
            _mouseDestroy = mouseProto._mouseDestroy,
            touchHandled;

        function simulateMouseEvent(event, simulatedType) {
            if (event.originalEvent.touches.length > 1) {
                return;
            }
            event.preventDefault();
            var touch = event.originalEvent.changedTouches[0],
                simulatedEvent = document.createEvent('MouseEvents');
            simulatedEvent.initMouseEvent(
                simulatedType, true, true, window, 1,
                touch.screenX, touch.screenY, touch.clientX, touch.clientY,
                false, false, false, false, 0, null
            );
            event.target.dispatchEvent(simulatedEvent);
        }

        mouseProto._touchStart = function(event) {
            var self = this;
            if (touchHandled || !self._mouseCapture(event.originalEvent.changedTouches[0])) {
                return;
            }
            touchHandled = true;
            self._touchMoved = false;
            simulateMouseEvent(event, 'mouseover');
            simulateMouseEvent(event, 'mousemove');
            simulateMouseEvent(event, 'mousedown');
        };

        mouseProto._touchMove = function(event) {
            if (!touchHandled) {
                return;
            }
            this._touchMoved = true;
            simulateMouseEvent(event, 'mousemove');
        };

        mouseProto._touchEnd = function(event) {
            if (!touchHandled) {
                return;
            }
            simulateMouseEvent(event, 'mouseup');
            simulateMouseEvent(event, 'mouseout');
            if (!this._touchMoved) {
                simulateMouseEvent(event, 'click');
            }
            touchHandled = false;
        };

        mouseProto._mouseInit = function() {
            var self = this;
            self.element.bind({
                touchstart: $.proxy(self, '_touchStart'),
                touchmove: $.proxy(self, '_touchMove'),
                touchend: $.proxy(self, '_touchEnd')
            });
            _mouseInit.call(self);
        };

        mouseProto._mouseDestroy = function() {
            var self = this;
            self.element.unbind({
                touchstart: $.proxy(self, '_touchStart'),
                touchmove: $.proxy(self, '_touchMove'),
                touchend: $.proxy(self, '_touchEnd')
            });
            _mouseDestroy.call(self);
        };
    })(jQuery);
</script>

<script>
    function updateLanguage(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateLanguage',
                id: id,
                stt: $('#stt' + id).val(),
                status: $('#status' + id + ':checked').val() || 0
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
                showMessage('<?= __("Đã xảy ra lỗi khi cập nhật"); ?>', 'error');
            }
        });
    }

    // Hàm cập nhật thứ tự ngôn ngữ với debounce
    let updateLanguageOrderTimer;

    function updateLanguageOrder(order) {
        clearTimeout(updateLanguageOrderTimer);
        updateLanguageOrderTimer = setTimeout(function() {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'updateLanguageOrder',
                    order: JSON.stringify(order)
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
                    showMessage('<?= __("Đã xảy ra lỗi khi cập nhật thứ tự"); ?>', 'error');
                }
            });
        }, 500);
    }

    function setDefault(id) {
        $('.setDefault').html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'setDefaultLanguage',
                id: id
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
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                location.reload();
            }
        });
    }

    function RemoveRow(id) {
        cuteAlert({
            type: "question",
            title: "<?= __('Xác nhận xóa ngôn ngữ'); ?>",
            message: "<?= __('Bạn có chắc chắn muốn xóa ngôn ngữ ID'); ?> " + id + " <?= __('không ?'); ?>",
            confirmText: "<?= __('Đồng Ý'); ?>",
            cancelText: "<?= __('Hủy'); ?>"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'removeLanguage',
                        id: id
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
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                        location.reload();
                    }
                });
            }
        })
    }

    // Khởi tạo drag and drop sorting
    $(document).ready(function() {
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints;

        const sortableConfig = {
            handle: '.language-handle',
            items: '.language-card',
            axis: 'y',
            cursor: 'move',
            opacity: 0.8,
            placeholder: 'ui-state-highlight',
            forcePlaceholderSize: true,
            helper: function(e, item) {
                const helper = $(item).clone().addClass('sortable-helper');
                helper.css('height', $(item).outerHeight());
                helper.css('width', $(item).outerWidth());
                return helper;
            },
            tolerance: 'pointer',
            delay: isTouchDevice ? 200 : 100,
            distance: isTouchDevice ? 10 : 5,
            scroll: true,
            scrollSpeed: 5,
            scrollSensitivity: 80,
            containment: 'parent',
            revert: false,

            start: function(event, ui) {
                $('body').addClass('sorting-active');
                $(ui.item).addClass('sorting');
                $('.language-card').addClass('no-transition');

                if (isTouchDevice) {
                    $('body').css('overflow', 'hidden');
                }
            },

            stop: function(event, ui) {
                $('body').removeClass('sorting-active');
                $(ui.item).removeClass('sorting');

                setTimeout(function() {
                    $('.language-card').removeClass('no-transition');
                    if (isTouchDevice) {
                        $('body').css('overflow', '');
                    }
                }, 50);
            },

            update: function(event, ui) {
                const languageOrder = [];
                const total = $('.language-card').length;

                $('.language-card').each(function(index) {
                    const id = $(this).data('id');
                    const position = total - index; // Đảo ngược để stt cao hơn ở trên

                    languageOrder.push({
                        id: id,
                        position: position
                    });

                    // Cập nhật UI
                    $('#stt' + id).val(position);
                    $(this).find('.priority-badge').html('<i class="fa fa-sort-numeric-up me-1"></i>' + position);
                });

                // Gửi dữ liệu lên server
                updateLanguageOrder(languageOrder);
            }
        };

        // Khởi tạo sortable
        $('#sortable-languages').sortable(sortableConfig).disableSelection();

        // Hỗ trợ touch cho mobile
        if (isTouchDevice) {
            $('.language-handle').on('touchstart', function(e) {
                $(this).addClass('touch-active');
            });

            $('.language-handle').on('touchend', function(e) {
                $(this).removeClass('touch-active');
            });
        }

        // Thêm class để tối ưu CSS
        $('body').addClass('has-sortable');
    });
</script>

<script>
    // Modal và form xử lý
    $(document).ready(function() {
        const modal = $('#addLanguageModal');
        const form = $('#addLanguageForm');
        const flagUpload = $('#flagUpload');
        const flagPreview = $('#flagPreview');
        const submitBtn = $('#submitBtn');
        const progressBars = $('.progress-bar');

        // Reset modal khi đóng
        modal.on('hidden.bs.modal', function() {
            form[0].reset();
            form.removeClass('was-validated');
            resetProgress();
            resetFlagPreview();
        });

        // Xử lý preview ảnh cờ
        flagUpload.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showMessage('<?= __("Kích thước file không được vượt quá 2MB"); ?>', 'error');
                    $(this).val('');
                    resetFlagPreview();
                    return;
                }
                if (!file.type.match('image.*')) {
                    showMessage('<?= __("Vui lòng chọn file ảnh hợp lệ"); ?>', 'error');
                    $(this).val('');
                    resetFlagPreview();
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    flagPreview.html(`<img src="${e.target.result}" class="w-100 h-100 object-fit-cover rounded" alt="Flag Preview">`);
                    updateProgress(3);
                };
                reader.readAsDataURL(file);
            } else {
                resetFlagPreview();
                updateProgress(2);
            }
        });

        // Xử lý form validation và progress
        $('#languageName').on('input', function() {
            updateProgress(1);
            validateLanguageName($(this));
        });

        $('#languageCode').on('input', function() {
            let value = $(this).val().toLowerCase();
            $(this).val(value);
            updateProgress(2);
            validateLanguageCode($(this));
        });

        $('#languageStatus').on('change', function() {
            updateProgress(4);
        });

        function validateLanguageName($input) {
            const value = $input.val().trim();
            if (value.length < 2) {
                $input.removeClass('is-valid').addClass('is-invalid');
                return false;
            } else {
                $input.removeClass('is-invalid').addClass('is-valid');
                return true;
            }
        }

        function validateLanguageCode($input) {
            const value = $input.val().trim();
            const pattern = /^[a-z]{2,5}$/;
            if (!pattern.test(value)) {
                $input.removeClass('is-valid').addClass('is-invalid');
                return false;
            } else {
                $input.removeClass('is-invalid').addClass('is-valid');
                return true;
            }
        }

        function updateProgress(step) {
            progressBars.each(function(index) {
                if (index < step) {
                    $(this).removeClass('bg-secondary').addClass('bg-primary');
                } else {
                    $(this).removeClass('bg-primary').addClass('bg-secondary');
                }
            });
        }

        function resetProgress() {
            progressBars.removeClass('bg-primary').addClass('bg-secondary');
            progressBars.first().removeClass('bg-secondary').addClass('bg-primary');
        }

        function resetFlagPreview() {
            flagPreview.html('<i class="ri-image-line text-muted fs-20"></i>');
        }

        // Form submission với loading state
        form.on('submit', function(e) {
            const isValid = form[0].checkValidity();
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                form.addClass('was-validated');
                const firstInvalidField = form.find(':invalid').first();
                if (firstInvalidField.length) {
                    firstInvalidField.focus();
                    firstInvalidField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                showMessage('<?= __("Vui lòng kiểm tra lại thông tin đã nhập"); ?>', 'error');
                return false;
            }
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span><?= __("Đang xử lý..."); ?>');
        });

        // Auto-suggest ISO codes
        const isoSuggestions = {
            'english': 'en', 'tiếng việt': 'vi', 'vietnamese': 'vi', 'chinese': 'zh', 'trung quốc': 'zh',
            '中文': 'zh', 'japanese': 'ja', 'nhật bản': 'ja', '日本語': 'ja', 'korean': 'ko', 'hàn quốc': 'ko',
            'thai': 'th', 'thái lan': 'th', 'indonesian': 'id', 'indonesia': 'id', 'malaysian': 'ms',
            'filipino': 'tl', 'hindi': 'hi', 'french': 'fr', 'pháp': 'fr', 'german': 'de', 'đức': 'de',
            'spanish': 'es', 'tây ban nha': 'es', 'italian': 'it', 'portuguese': 'pt', 'russian': 'ru',
            'nga': 'ru', 'polish': 'pl', 'dutch': 'nl', 'swedish': 'sv', 'norwegian': 'no', 'danish': 'da',
            'finnish': 'fi', 'greek': 'el', 'turkish': 'tr', 'arabic': 'ar', 'ả rập': 'ar', 'hebrew': 'he',
            'persian': 'fa', 'ukrainian': 'uk', 'czech': 'cs', 'hungarian': 'hu', 'romanian': 'ro',
            'bulgarian': 'bg', 'croatian': 'hr', 'serbian': 'sr', 'albanian': 'sq', 'esperanto': 'eo',
            'latin': 'la', 'swahili': 'sw', 'maori': 'mi', 'afrikaans': 'af', 'bengali': 'bn', 'urdu': 'ur',
            'tamil': 'ta', 'telugu': 'te', 'mongolian': 'mn', 'khmer': 'km', 'lao': 'lo', 'nepali': 'ne',
            'burmese': 'my', 'myanmar': 'my', 'sinhala': 'si'
        };

        $('#languageName').on('blur', function() {
            const langName = $(this).val().toLowerCase().trim();
            const codeInput = $('#languageCode');
            if (langName && !codeInput.val()) {
                for (let key in isoSuggestions) {
                    if (langName.includes(key)) {
                        codeInput.val(isoSuggestions[key]);
                        codeInput.trigger('input');
                        break;
                    }
                }
            }
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.altKey && e.which === 76) {
                e.preventDefault();
                modal.modal('show');
                setTimeout(() => $('#languageName').focus(), 300);
            }
        });
    });
</script>