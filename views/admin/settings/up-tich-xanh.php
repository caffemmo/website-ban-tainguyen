<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/uptichxanh.php';

function uptichxanh_admin_save_setting($name, $value)
{
    global $CMSNT;

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

function uptichxanh_admin_price($input, $name)
{
    $value = trim((string) ($input[$name] ?? ''));
    if (!is_numeric($value) || (float) $value <= 0 || (float) $value > 1000000000) {
        return false;
    }
    return number_format((float) $value, 2, '.', '');
}

if (isset($_POST['SaveUpTichXanhSettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $apiKey = trim((string) ($_POST['uptichxanh_api_key'] ?? ''));
    $baseUrl = rtrim(trim((string) ($_POST['uptichxanh_api_base_url'] ?? '')), '/');
    $timeout = filter_var($_POST['uptichxanh_timeout'] ?? null, FILTER_VALIDATE_INT);
    $getLinkPrice = uptichxanh_admin_price($_POST, 'uptichxanh_price_get_link');
    $upFbPrice = uptichxanh_admin_price($_POST, 'uptichxanh_price_up_fb');
    $upIgPrice = uptichxanh_admin_price($_POST, 'uptichxanh_price_up_ig');

    if ($apiKey !== '' && mb_strlen($apiKey) > 255) {
        admin_msg_error('Khóa API không hợp lệ.', base_url_admin('settings&tab=up-tich-xanh'), 1200);
    }
    if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
        admin_msg_error('URL dịch vụ phải là một địa chỉ HTTPS hợp lệ.', base_url_admin('settings&tab=up-tich-xanh'), 1200);
    }
    if ($timeout === false || $timeout < 5 || $timeout > 120) {
        admin_msg_error('Timeout phải nằm trong khoảng 5 đến 120 giây.', base_url_admin('settings&tab=up-tich-xanh'), 1200);
    }
    if ($getLinkPrice === false || $upFbPrice === false || $upIgPrice === false) {
        admin_msg_error('Giá bán mỗi lượt phải lớn hơn 0 và hợp lệ.', base_url_admin('settings&tab=up-tich-xanh'), 1200);
    }

    if ($apiKey !== '') {
        uptichxanh_admin_save_setting('uptichxanh_api_key', $apiKey);
    }
    uptichxanh_admin_save_setting('uptichxanh_api_base_url', $baseUrl);
    uptichxanh_admin_save_setting('uptichxanh_timeout', (string) $timeout);
    uptichxanh_admin_save_setting('uptichxanh_price_get_link', $getLinkPrice);
    uptichxanh_admin_save_setting('uptichxanh_price_up_fb', $upFbPrice);
    uptichxanh_admin_save_setting('uptichxanh_price_up_ig', $upIgPrice);

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật cấu hình dịch vụ Up tích xanh')
    ]);
    admin_msg_success('Đã lưu cấu hình Up tích xanh.', base_url_admin('settings&tab=up-tich-xanh'), 900);
}

$upConfig = uptichxanh_config();
$upConfigured = uptichxanh_is_configured();
$hasStoredKey = uptichxanh_db_setting('uptichxanh_api_key') !== '' || $upConfig['api_key'] !== '';
?>

<style>
    .upx-admin-intro { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:22px 24px; margin-bottom:20px; color:#fff; background:#153a5a; border-radius:14px; }
    .upx-admin-intro h4 { margin:0 0 5px; font-weight:700; }
    .upx-admin-intro p { margin:0; color:rgba(255,255,255,.78); }
    .upx-admin-status { display:inline-flex; align-items:center; gap:7px; padding:8px 12px; border:1px solid rgba(255,255,255,.28); border-radius:999px; white-space:nowrap; font-size:13px; font-weight:700; }
    .upx-admin-status i { font-size:8px; color:#ffd166; }
    .upx-admin-status.is-ready i { color:#8bf0ba; }
    .upx-admin-card { height:100%; border:1px solid #e7edf5; border-radius:14px; box-shadow:0 8px 24px rgba(31,55,82,.06); }
    .upx-admin-card .card-header { padding:18px 20px 0; border:0; background:transparent; }
    .upx-admin-card .card-body { padding:18px 20px 20px; }
    .upx-admin-card .card-title { font-size:16px; font-weight:700; }
    .upx-admin-label { display:flex; align-items:center; gap:8px; font-weight:600; }
    .upx-admin-label i { width:18px; color:#0b9ba8; text-align:center; }
    .upx-admin-help { color:#75869a; font-size:12px; line-height:1.5; }
    .upx-admin-note { display:flex; gap:10px; padding:12px 14px; border:1px solid #dbeaf4; border-radius:10px; color:#47657f; background:#f2f8fc; font-size:12px; line-height:1.55; }
    .upx-admin-note i { margin-top:2px; color:#0b9ba8; }
    .upx-admin-save { min-width:180px; }
    @media (max-width:575.98px) { .upx-admin-intro { align-items:flex-start; flex-direction:column; padding:18px; } }
</style>

<div class="tab-pane text-muted show active" id="up-tich-xanh-settings" role="tabpanel">
    <div class="upx-admin-intro">
        <div>
            <h4><i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i><?= __('Cấu hình Up tích xanh'); ?></h4>
            <p><?= __('Thiết lập kết nối nội bộ và giá bán hiển thị cho khách hàng.'); ?></p>
        </div>
        <span class="upx-admin-status <?= $upConfigured ? 'is-ready' : ''; ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= $upConfigured ? __('Đã sẵn sàng') : __('Chưa sẵn sàng'); ?></span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card upx-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-link me-2 text-primary" aria-hidden="true"></i><?= __('Kết nối dịch vụ'); ?></div></div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label upx-admin-label" for="uptichxanh_api_key"><i class="fa-solid fa-key" aria-hidden="true"></i><?= __('Khóa API'); ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="uptichxanh_api_key" name="uptichxanh_api_key" placeholder="<?= $hasStoredKey ? __('Đã lưu khóa máy chủ, để trống để giữ nguyên') : __('Nhập khóa API trên máy chủ'); ?>" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="uptichxanh_api_key_toggle" title="<?= __('Hiện hoặc ẩn khóa'); ?>" aria-label="<?= __('Hiện hoặc ẩn khóa'); ?>"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
                            </div>
                            <div class="upx-admin-help mt-2"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= __('Khóa chỉ lưu ở server và không bao giờ trả về giao diện khách hàng.'); ?></div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label upx-admin-label" for="uptichxanh_api_base_url"><i class="fa-solid fa-globe" aria-hidden="true"></i><?= __('URL dịch vụ'); ?></label>
                            <input type="url" class="form-control" id="uptichxanh_api_base_url" name="uptichxanh_api_base_url" value="<?= htmlspecialchars($upConfig['base_url'], ENT_QUOTES, 'UTF-8'); ?>" required inputmode="url">
                            <div class="upx-admin-help mt-2"><?= __('Dùng HTTPS và không thêm dấu / ở cuối.'); ?></div>
                        </div>
                        <div class="upx-admin-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= __('Không đưa khóa API vào JavaScript, HTML công khai hoặc tài liệu gửi cho khách.'); ?></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card upx-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-tags me-2 text-primary" aria-hidden="true"></i><?= __('Giá bán và timeout'); ?></div></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label upx-admin-label" for="uptichxanh_price_get_link"><i class="fa-solid fa-link" aria-hidden="true"></i>Get Link Facebook</label><div class="input-group"><input type="number" class="form-control" id="uptichxanh_price_get_link" name="uptichxanh_price_get_link" value="<?= htmlspecialchars((string) $upConfig['prices']['get-link'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1" required><span class="input-group-text">VND</span></div></div>
                            <div class="col-md-6"><label class="form-label upx-admin-label" for="uptichxanh_price_up_fb"><i class="fa-brands fa-facebook" aria-hidden="true"></i>Up tích Facebook</label><div class="input-group"><input type="number" class="form-control" id="uptichxanh_price_up_fb" name="uptichxanh_price_up_fb" value="<?= htmlspecialchars((string) $upConfig['prices']['up-fb'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1" required><span class="input-group-text">VND</span></div></div>
                            <div class="col-md-6"><label class="form-label upx-admin-label" for="uptichxanh_price_up_ig"><i class="fa-brands fa-instagram" aria-hidden="true"></i>Up tích Instagram</label><div class="input-group"><input type="number" class="form-control" id="uptichxanh_price_up_ig" name="uptichxanh_price_up_ig" value="<?= htmlspecialchars((string) $upConfig['prices']['up-ig'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1" required><span class="input-group-text">VND</span></div></div>
                            <div class="col-md-6"><label class="form-label upx-admin-label" for="uptichxanh_timeout"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i><?= __('Timeout kết nối'); ?></label><div class="input-group"><input type="number" class="form-control" id="uptichxanh_timeout" name="uptichxanh_timeout" value="<?= htmlspecialchars((string) $upConfig['timeout'], ENT_QUOTES, 'UTF-8'); ?>" min="5" max="120" step="1" required><span class="input-group-text"><?= __('giây'); ?></span></div></div>
                        </div>
                        <div class="upx-admin-help mt-3"><?= __('Giá bán được kiểm tra trước khi trừ tiền; nếu yêu cầu không thành công, hệ thống tự hoàn tiền cho khách.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button type="submit" name="SaveUpTichXanhSettings" class="btn btn-primary upx-admin-save"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i><?= __('Lưu cấu hình'); ?></button></div>
    </form>
</div>

<script>
    (function () {
        var input = document.getElementById('uptichxanh_api_key');
        var button = document.getElementById('uptichxanh_api_key_toggle');
        if (!input || !button) return;
        button.addEventListener('click', function () {
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('i').className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }());
</script>
