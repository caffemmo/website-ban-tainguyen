<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Xử lý lưu cài đặt trang chủ
if (isset($_POST['SaveHomepage'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    // Ghi log thay đổi cài đặt
    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => __('Thay đổi cài đặt trang chủ')
    ]);

    // Lưu giá trị home_page vào bảng settings
    $home_page_value = isset($_POST['home_page']) ? check_string($_POST['home_page']) : 'home';
    $CMSNT->update("settings", [
        'value' => $home_page_value
    ], " `name` = 'home_page' ");

    // Gửi thông báo Telegram cho admin
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Thay đổi cài đặt trang chủ') . ': ' . $home_page_value, $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}

// Quét danh sách file homepage hợp lệ trong views/client/
// Chỉ lấy các file bắt đầu bằng "home"
$client_views = glob(VIEWS_PATH . '/client/home*.php');
$home_options = [];
foreach ($client_views as $file) {
    $basename = basename($file, '.php');
    $home_options[] = $basename;
}
// Đảm bảo 'home' (Mặc định) luôn đứng đầu danh sách
usort($home_options, function ($a, $b) {
    if ($a === 'home') return -1;
    if ($b === 'home') return 1;
    return strcmp($a, $b);
});

// Giá trị hiện tại đang chọn, mặc định là 'home'
$current_home_page = $CMSNT->site('home_page') ?: 'home';

// Thư mục chứa ảnh preview cho từng homepage
$preview_base = 'mod/img/homepage/';
?>

<div class="tab-pane text-muted show active" id="homepage-setting" role="tabpanel">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="flex-shrink-0">
            <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                <i class="bx bx-home fs-18 text-primary"></i>
            </div>
        </div>
        <div class="flex-grow-1 ms-3">
            <h4 class="mb-1"><?= __('Chọn Home Page'); ?></h4>
            <p class="text-muted mb-0"><?= __('Tùy chọn giao diện trang chủ hiển thị cho khách truy cập website'); ?></p>
        </div>
    </div>

    <form action="" method="POST">
        <!-- Input hidden để lưu giá trị homepage đang chọn -->
        <input type="hidden" name="home_page" id="home_page_value" value="<?= htmlspecialchars($current_home_page, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="row g-3">
            <?php
            // Card đặc biệt: Không sử dụng homepage (trở về mặc định = 'home')
            // Hiển thị từng option dạng card có ảnh preview
            foreach ($home_options as $opt):
                $is_selected = ($current_home_page === $opt);
                // Ảnh preview: ưu tiên file png, fallback sang jpg, nếu không có thì dùng placeholder
                $preview_img = '';
                if (file_exists(__DIR__ . '/../../../' . $preview_base . $opt . '.png')) {
                    $preview_img = base_url($preview_base . $opt . '.png');
                } elseif (file_exists(__DIR__ . '/../../../' . $preview_base . $opt . '.jpg')) {
                    $preview_img = base_url($preview_base . $opt . '.jpg');
                } elseif (file_exists(__DIR__ . '/../../../' . $preview_base . $opt . '.webp')) {
                    $preview_img = base_url($preview_base . $opt . '.webp');
                }

                // Tên hiển thị thân thiện: chuyển "home-demo" → "Home Demo"
                $display_name = ucwords(str_replace('-', ' ', $opt));
                if ($opt === 'home') {
                    $display_name = __('Mặc định');
                }
            ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card homepage-card mb-0 <?= $is_selected ? 'homepage-card-selected' : ''; ?>"
                        onclick="selectHomepage('<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>', this)"
                        style="cursor: pointer; transition: all 0.2s ease;">
                        <div class="card-body p-2">
                            <!-- Ảnh preview -->
                            <div class="homepage-preview-img rounded overflow-hidden mb-2 position-relative"
                                style="height: 160px; background: #f0f0f5;">
                                <?php if ($opt === 'home'): ?>
                                    <!-- Mặc định: hiển thị icon empty, không cần ảnh preview -->
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                        <div class="text-center">
                                            <i class="bx bx-border-none fs-50 opacity-40"></i>
                                            <div class="small mt-1 opacity-60"><?= __('Không sử dụng homepage'); ?></div>
                                        </div>
                                    </div>
                                <?php elseif ($preview_img): ?>
                                    <img src="<?= $preview_img; ?>" alt="<?= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <!-- Fallback nếu không có ảnh preview -->
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                        <div class="text-center">
                                            <i class="bx bx-image fs-30 opacity-50"></i>
                                            <div class="small mt-1 opacity-50"><?= __('Chưa có ảnh'); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Badge đánh dấu đang chọn -->
                                <?php if ($is_selected): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-primary rounded-pill px-2 py-1 shadow-sm">
                                            <i class="fas fa-check me-1"></i><?= __('Đang dùng'); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Tên homepage -->
                            <div class="text-center py-1">
                                <span class="fw-semibold fs-14"><?= $display_name; ?></span>
                                <div class="text-muted small"><?= $opt; ?>.php</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Save Button -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <div class="text-muted">
                <i class="fas fa-info-circle me-1 text-primary"></i>
                <?= __('Click vào giao diện muốn chọn, sau đó nhấn Lưu để áp dụng'); ?>
            </div>
            <button type="submit" name="SaveHomepage" class="btn btn-primary btn-lg px-5">
                <i class="fa fa-fw fa-save me-2"></i>
                <?= __('Lưu cài đặt'); ?>
            </button>
        </div>
    </form>
</div>

<style>
    /* Card homepage có viền mặc định */
    .homepage-card {
        border: 2px solid transparent;
        border-radius: 10px;
        background: var(--custom-white, #fff);
        box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
    }

    /* Hover hiệu ứng nhẹ */
    .homepage-card:hover {
        border-color: rgba(var(--primary-rgb, 105, 108, 255), .45);
        box-shadow: 0 4px 15px rgba(var(--primary-rgb, 105, 108, 255), .15);
        transform: translateY(-2px);
    }

    /* Card đang được chọn (selected) */
    .homepage-card-selected {
        border-color: rgb(var(--primary-rgb, 105, 108, 255)) !important;
        box-shadow: 0 4px 18px rgba(var(--primary-rgb, 105, 108, 255), .25) !important;
    }
</style>

<script>
    /**
     * Chọn homepage: cập nhật input hidden và highlight card đang chọn
     * @param {string} value - tên file homepage (vd: 'home', 'home-demo')
     * @param {HTMLElement} cardEl - phần tử card được click
     */
    function selectHomepage(value, cardEl) {
        // Cập nhật giá trị hidden input
        document.getElementById('home_page_value').value = value;

        // Xóa trạng thái selected ở tất cả card
        document.querySelectorAll('.homepage-card').forEach(function(card) {
            card.classList.remove('homepage-card-selected');
            // Xóa badge "Đang dùng" cũ
            var badge = card.querySelector('.badge.bg-primary');
            if (badge) badge.closest('.position-absolute').remove();
        });

        // Thêm trạng thái selected cho card vừa click
        cardEl.classList.add('homepage-card-selected');

        // Thêm badge "Đang dùng" vào card mới
        var previewDiv = cardEl.querySelector('.homepage-preview-img');
        var badgeHtml = '<div class="position-absolute top-0 end-0 m-2">' +
            '<span class="badge bg-primary rounded-pill px-2 py-1 shadow-sm">' +
            '<i class="fas fa-check me-1"></i><?= __("Đang dùng"); ?>' +
            '</span></div>';
        previewDiv.insertAdjacentHTML('beforeend', badgeHtml);
    }
</script>