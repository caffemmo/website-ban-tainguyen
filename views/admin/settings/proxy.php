<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/youproxy.php';

function proxy_admin_save_setting($name, $value)
{
    global $CMSNT;

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

if (isset($_POST['SaveProxySettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $apiKey = trim((string) ($_POST['youproxy_api_key'] ?? ''));
    $baseUrl = trim((string) ($_POST['youproxy_api_base_url'] ?? ''));
    $usdRate = trim((string) ($_POST['youproxy_usd_rate'] ?? ''));
    $markup = trim((string) ($_POST['youproxy_markup_percent'] ?? ''));
    $timeout = filter_var($_POST['youproxy_timeout'] ?? null, FILTER_VALIDATE_INT);

    if ($apiKey !== '' && mb_strlen($apiKey) > 255) {
        admin_msg_error('Khóa dịch vụ proxy không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
        admin_msg_error('URL dịch vụ proxy phải là một địa chỉ HTTPS hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if (!is_numeric($usdRate) || (float) $usdRate <= 0 || (float) $usdRate > 1000000000) {
        admin_msg_error('Tỷ giá USD không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if (!is_numeric($markup) || (float) $markup < 0 || (float) $markup > 1000) {
        admin_msg_error('Phần trăm markup không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($timeout === false || $timeout < 5 || $timeout > 120) {
        admin_msg_error('Timeout phải nằm trong khoảng 5 đến 120 giây.', base_url_admin('settings&tab=proxy'), 1200);
    }

    if ($apiKey !== '') {
        proxy_admin_save_setting('youproxy_api_key', $apiKey);
    }
    proxy_admin_save_setting('youproxy_api_base_url', rtrim($baseUrl, '/'));
    proxy_admin_save_setting('youproxy_usd_rate', number_format((float) $usdRate, 2, '.', ''));
    proxy_admin_save_setting('youproxy_markup_percent', number_format((float) $markup, 2, '.', ''));
    proxy_admin_save_setting('youproxy_timeout', (string) $timeout);

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật cấu hình dịch vụ proxy')
    ]);

    admin_msg_success('Đã lưu cấu hình proxy.', base_url_admin('settings&tab=proxy'), 900);
}

$proxyConfig = youproxy_config();
$proxyConfigured = youproxy_is_configured();
$hasStoredKey = youproxy_db_setting('youproxy_api_key') !== '';
?>

<style>
    .proxy-admin-intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 22px 24px;
        margin-bottom: 20px;
        color: #fff;
        background: linear-gradient(135deg, #173b63 0%, #2374a8 100%);
        border-radius: 14px;
    }
    .proxy-admin-intro h4 { margin: 0 0 5px; font-weight: 700; }
    .proxy-admin-intro p { margin: 0; color: rgba(255,255,255,.78); }
    .proxy-admin-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 999px;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 700;
    }
    .proxy-admin-status i { font-size: 8px; }
    .proxy-admin-status.is-ready i { color: #8bf0ba; }
    .proxy-admin-status.is-pending i { color: #ffd166; }
    .proxy-admin-card {
        height: 100%;
        border: 1px solid #e7edf5;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(31, 55, 82, .06);
    }
    .proxy-admin-card .card-header { padding: 18px 20px 0; background: transparent; border: 0; }
    .proxy-admin-card .card-body { padding: 18px 20px 20px; }
    .proxy-admin-card .card-title { font-size: 16px; font-weight: 700; }
    .proxy-admin-label { display: flex; align-items: center; gap: 8px; font-weight: 600; }
    .proxy-admin-label i { width: 18px; color: #3a82c4; text-align: center; }
    .proxy-admin-help { color: #75869a; font-size: 12px; line-height: 1.5; }
    .proxy-admin-note {
        display: flex;
        gap: 10px;
        padding: 12px 14px;
        color: #47657f;
        background: #f2f8fc;
        border: 1px solid #dbeaf4;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.55;
    }
    .proxy-admin-note i { margin-top: 2px; color: #3a82c4; }
    .proxy-admin-save { min-width: 170px; }
    @media (max-width: 575.98px) {
        .proxy-admin-intro { align-items: flex-start; flex-direction: column; padding: 18px; }
        .proxy-admin-status { align-self: flex-start; }
    }
</style>

<div class="tab-pane text-muted show active" id="proxy-settings" role="tabpanel">
    <div class="proxy-admin-intro">
        <div>
            <h4><i class="fa-solid fa-server me-2" aria-hidden="true"></i><?= __('Cấu hình Proxy'); ?></h4>
            <p><?= __('Thiết lập kết nối và giá bán cho dịch vụ proxy trên website.'); ?></p>
        </div>
        <span class="proxy-admin-status <?= $proxyConfigured ? 'is-ready' : 'is-pending'; ?>">
            <i class="fa-solid fa-circle" aria-hidden="true"></i>
            <?= $proxyConfigured ? __('Đã sẵn sàng') : __('Chưa sẵn sàng'); ?>
        </span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card proxy-admin-card">
                    <div class="card-header">
                        <div class="card-title mb-0"><i class="fa-solid fa-link me-2 text-primary" aria-hidden="true"></i><?= __('Kết nối dịch vụ'); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_api_key"><i class="fa-solid fa-key" aria-hidden="true"></i><?= __('Khóa API'); ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="youproxy_api_key" name="youproxy_api_key" placeholder="<?= $hasStoredKey ? __('Đã lưu khóa máy chủ, để trống để giữ nguyên') : __('Nhập khóa API trên máy chủ'); ?>" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="proxy_api_key_toggle" title="<?= __('Hiện hoặc ẩn khóa'); ?>" aria-label="<?= __('Hiện hoặc ẩn khóa'); ?>"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
                            </div>
                            <div class="proxy-admin-help mt-2"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= __('Khóa được giữ ở server và không hiển thị cho khách hàng.'); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_api_base_url"><i class="fa-solid fa-globe" aria-hidden="true"></i><?= __('URL dịch vụ'); ?></label>
                            <input type="url" class="form-control" id="youproxy_api_base_url" name="youproxy_api_base_url" value="<?= htmlspecialchars($proxyConfig['base_url'], ENT_QUOTES, 'UTF-8'); ?>" required inputmode="url">
                            <div class="proxy-admin-help mt-2"><?= __('Dùng HTTPS và không thêm dấu / ở cuối.'); ?></div>
                        </div>

                        <div class="proxy-admin-note">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            <span><?= __('Không đưa khóa API vào JavaScript, HTML công khai hoặc tài liệu gửi cho khách.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card proxy-admin-card">
                    <div class="card-header">
                        <div class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-primary" aria-hidden="true"></i><?= __('Định giá và thời gian'); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_usd_rate"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i><?= __('Tỷ giá USD'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_usd_rate" name="youproxy_usd_rate" value="<?= htmlspecialchars((string) $proxyConfig['usd_rate'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="0.01" required>
                                <span class="input-group-text">VND / USD</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Dùng để quy đổi giá dịch vụ sang số dư ví trên website.'); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_markup_percent"><i class="fa-solid fa-percent" aria-hidden="true"></i><?= __('Markup website'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_markup_percent" name="youproxy_markup_percent" value="<?= htmlspecialchars((string) $proxyConfig['markup_percent'], ENT_QUOTES, 'UTF-8'); ?>" min="0" max="1000" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Có thể đặt 0 nếu không cộng thêm phí trên giá quy đổi.'); ?></div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label proxy-admin-label" for="youproxy_timeout"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i><?= __('Timeout kết nối'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_timeout" name="youproxy_timeout" value="<?= htmlspecialchars((string) $proxyConfig['timeout'], ENT_QUOTES, 'UTF-8'); ?>" min="5" max="120" step="1" required>
                                <span class="input-group-text">giây</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Thời gian tối đa server chờ phản hồi cho mỗi yêu cầu.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" name="SaveProxySettings" class="btn btn-primary proxy-admin-save">
                <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i><?= __('Lưu cấu hình'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        var input = document.getElementById('youproxy_api_key');
        var button = document.getElementById('proxy_api_key_toggle');
        if (!input || !button) return;
        button.addEventListener('click', function () {
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('i').className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }());
</script>
