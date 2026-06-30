<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Settings',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<!-- ckeditor -->
<script src="' . BASE_URL('public/ckeditor/ckeditor.js') . '"></script>
<!-- Thêm CSS của CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">

<!-- Thêm JavaScript của CodeMirror -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/monokai.min.css">
<!-- Mode HTML mixed (hỗ trợ HTML, CSS và JS) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/htmlmixed/htmlmixed.min.js"></script>
<!-- Mode cho CSS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/css/css.min.js"></script>
<!-- Mode cho JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/javascript/javascript.min.js"></script>
<!-- Mode cho XML (cần cho HTML) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/xml/xml.min.js"></script>

';
$body['footer'] = '
 
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_setting') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

// Xác định tab hiện tại
$current_tab = isset($_GET['tab']) ? check_string($_GET['tab']) : 'general';
$base_settings_url = base_url_admin('settings');

// Mapping các tab với file và thông tin hiển thị
$tab_config = [
    'general'              => ['file' => 'general.php',              'icon' => 'bx bx-cog',                                            'label' => __('Cài đặt chung')],
    'connection'           => ['file' => 'connection.php',           'icon' => 'bx bx-plug',                                           'label' => __('Kết nối')],
    'telegram-template'    => ['file' => 'telegram-template.php',    'icon' => 'fa-brands fa-telegram',                                'label' => __('Telegram Template')],
    'mail-template'        => ['file' => 'mail-template.php',        'icon' => 'fa-solid fa-envelope',                                 'label' => __('Mail Template')],
    'security'             => ['file' => 'security.php',             'icon' => 'fa-solid fa-shield-halved',                            'label' => __('Bảo mật')],
    'product-display'      => ['file' => 'product-display.php',      'icon' => 'bx bx-desktop',                                       'label' => __('Hiển thị sản phẩm')],
    'recent-transactions'  => ['file' => 'recent-transactions.php',  'icon' => 'fa-solid fa-clock-rotate-left',                        'label' => __('Giao dịch gần đây')],
    'widget'               => ['file' => 'widget.php',               'icon' => 'fa-brands fa-themeco',                                 'label' => __('Widget')],
    'addons'               => ['file' => 'addons.php',               'icon' => 'fa-solid fa-puzzle-piece',                             'label' => __('Addons')],
    'cron-jobs'            => ['file' => 'cron-jobs.php',            'icon' => 'fas fa-clock',                                         'label' => __('Cron Jobs')],
    'telegram-shop'        => ['file' => 'telegram-shop.php',        'icon' => 'fa-brands fa-telegram',                                'label' => __('Telegram Shop')],
    'homepage'             => ['file' => 'homepage.php',             'icon' => 'bx bx-home-alt',                                       'label' => __('Landing Page')],
    'leaderboard'          => ['file' => 'leaderboard.php',          'icon' => 'fa-solid fa-ranking-star',                              'label' => __('Bảng xếp hạng')],
];

// Kiểm tra tab hợp lệ, nếu không thì chuyển về tab đầu tiên
if (!array_key_exists($current_tab, $tab_config)) {
    $current_tab = array_key_first($tab_config);
}
?>

<style>
    /* Ẩn pseudo-element :before của card title */
    .card.custom-card .card-header .card-title:before {
        display: none !important;
    }

    /* Hoặc có thể dùng cách này */
    .card.custom-card .card-header .card-title:before {
        content: none !important;
    }
</style>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-gear"></i> Cài đặt</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Sidebar Navigation -->
                            <div class="col-xl-2">
                                <nav class="nav nav-tabs flex-column nav-style-5 mb-3" role="tablist">
                                    <?php foreach ($tab_config as $tab_key => $config): ?>
                                        <a class="nav-link <?= $current_tab == $tab_key ? 'active' : ''; ?>" href="<?= $base_settings_url; ?>&tab=<?= $tab_key; ?>">
                                            <i class="<?= $config['icon']; ?> me-2 align-middle d-inline-block"></i><?= $config['label']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>

                            <!-- Tab Content -->
                            <div class="col-xl-10">
                                <div class="tab-content">
                                    <?php
                                    // Include file tab tương ứng
                                    $tab_file = __DIR__ . '/settings/' . $tab_config[$current_tab]['file'];
                                    if (file_exists($tab_file)) {
                                        require_once($tab_file);
                                    } else {
                                        // Fallback về tab đầu tiên nếu file không tồn tại
                                        $first_tab = array_key_first($tab_config);
                                        require_once(__DIR__ . '/settings/' . $tab_config[$first_tab]['file']);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>