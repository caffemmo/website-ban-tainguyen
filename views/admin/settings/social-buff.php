<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/social-buff.php';

function social_buff_admin_save_setting($name, $value)
{
    global $CMSNT;

    $existing = $CMSNT->get_row_safe('SELECT `id`, `value` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($existing) {
        if (hash_equals((string) ($existing['value'] ?? ''), (string) $value)) {
            return true;
        }
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

function social_buff_admin_api_url($value)
{
    $value = rtrim(trim((string) $value), '/');
    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if ($value === '' || strlen($value) > 500 || filter_var($value, FILTER_VALIDATE_URL) === false || $scheme !== 'https') {
        return null;
    }
    return $value;
}

function social_buff_admin_markup($value)
{
    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value) || !is_finite((float) $value)) {
        return null;
    }
    $markup = (float) $value;
    if ($markup < 0 || $markup > 500) {
        return null;
    }
    return number_format($markup, 4, '.', '');
}

function social_buff_admin_timeout($value)
{
    $value = trim((string) $value);
    if (!preg_match('/^\d{1,3}$/', $value)) {
        return null;
    }
    $timeout = (int) $value;
    return $timeout >= 10 && $timeout <= 60 ? (string) $timeout : null;
}

function social_buff_admin_service_notes($value)
{
    $value = trim((string) $value);
    if (mb_strlen($value) > 16000) {
        return null;
    }

    $notes = [];
    foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        $parts = explode('|', $line, 2);
        $code = count($parts) === 2 ? social_buff_service_code($parts[0]) : '';
        $description = count($parts) === 2 ? trim((string) preg_replace('/\s+/u', ' ', $parts[1])) : '';
        if ($code === '' || $description === '' || mb_strlen($description) > 240 || isset($notes[$code])) {
            return null;
        }
        $notes[$code] = $description;
    }

    return implode("\n", array_map(function ($code) use ($notes) {
        return $code . ' | ' . $notes[$code];
    }, array_keys($notes)));
}

if (isset($_POST['SaveSocialBuffSettings'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('settings&tab=social-buff'), 1200);
    }
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(__('You do not have permission to perform this action'));
    }
    if (defined('DEMO_MODE') && DEMO_MODE) {
        admin_msg_error(__('This feature cannot be used in demo mode.'), base_url_admin('settings&tab=social-buff'));
    }

    $apiUrl = social_buff_admin_api_url($_POST['social_buff_api_url'] ?? '');
    $markup = social_buff_admin_markup($_POST['social_buff_markup_percent'] ?? '');
    $timeout = social_buff_admin_timeout($_POST['social_buff_timeout'] ?? '');
    $serviceNotes = social_buff_admin_service_notes($_POST['social_buff_service_notes'] ?? '');
    $apiKey = trim((string) ($_POST['social_buff_api_key'] ?? ''));
    if ($serviceNotes === null) {
        admin_msg_error('Mô tả gói SV không hợp lệ. Mỗi dòng phải theo dạng SV3 | Mô tả dịch vụ.', base_url_admin('settings&tab=social-buff'));
    }
    if ($apiUrl === null || $markup === null || $timeout === null || $serviceNotes === null) {
        admin_msg_error('Vui lòng kiểm tra lại địa chỉ API, phần trăm lợi nhuận và thời gian chờ.', base_url_admin('settings&tab=social-buff'));
    }
    if ($apiKey !== '' && (strlen($apiKey) > 255 || preg_match('/[\r\n]/', $apiKey))) {
        admin_msg_error('Khóa API không hợp lệ.', base_url_admin('settings&tab=social-buff'));
    }

    $maintenance = isset($_POST['social_buff_maintenance']) ? '1' : '0';
    $settings = [
        'social_buff_api_url' => $apiUrl,
        'social_buff_markup_percent' => $markup,
        'social_buff_timeout' => $timeout,
        'social_buff_service_notes' => $serviceNotes,
        'social_buff_maintenance' => $maintenance
    ];
    if ($apiKey !== '') {
        $settings['social_buff_api_key'] = $apiKey;
    }
    foreach ($settings as $name => $value) {
        if (!social_buff_admin_save_setting($name, $value)) {
            admin_msg_error(__('Unable to save settings. Please try again.'), base_url_admin('settings&tab=social-buff'));
        }
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => 'Cập nhật cấu hình Buff mạng xã hội'
    ]);
    admin_msg_success(__('Saved successfully'), base_url_admin('settings&tab=social-buff'));
}

$socialBuffMaintenance = social_buff_maintenance_enabled();
$socialBuffConfig = social_buff_config();
$socialBuffServiceNotes = social_buff_setting('social_buff_service_notes', '');
$socialBuffSavedApiKey = social_buff_setting('social_buff_api_key', '');
$socialBuffApiKeyStatus = $socialBuffSavedApiKey !== ''
    ? 'Khóa API đã được lưu.'
    : (social_buff_env('HACKLIKE17_API_KEY') !== '' ? 'Đang dùng khóa từ cấu hình máy chủ.' : 'Chưa có khóa API.');
$socialBuffConfigured = social_buff_is_configured();
?>

<style>
    .social-buff-maintenance-summary {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .social-buff-admin-header {
        align-items: flex-start;
        gap: .75rem;
    }

    .social-buff-admin-status {
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .social-buff-maintenance-switch .form-check-input {
        cursor: pointer;
        height: 1.7rem;
        margin-top: 0;
        width: 3.25rem;
    }

    .social-buff-maintenance-switch .form-check-label {
        cursor: pointer;
        font-weight: 600;
    }

    .social-buff-api-key-status {
        color: #475569;
        display: block;
        font-size: .8125rem;
        line-height: 1.5;
        margin-top: .45rem;
    }

    .social-buff-admin-save {
        min-height: 44px;
    }

    @media (max-width: 575.98px) {
        .social-buff-admin-header {
            align-items: stretch;
            flex-direction: column;
        }

        .social-buff-admin-status {
            justify-content: flex-start;
        }

        .social-buff-maintenance-summary {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<div class="tab-pane active" id="social-buff-settings" role="tabpanel">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="card custom-card">
            <div class="card-header justify-content-between social-buff-admin-header">
                <div class="card-title mb-0"><i class="fa-solid fa-bolt me-2 text-primary"></i>Buff mạng xã hội</div>
                <div class="d-flex align-items-center gap-2 social-buff-admin-status">
                    <span class="badge <?= $socialBuffConfigured ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning'; ?>">
                        <?= $socialBuffConfigured ? 'Đã sẵn sàng' : 'Cần cấu hình API'; ?>
                    </span>
                    <span class="badge <?= $socialBuffMaintenance ? 'bg-warning-transparent text-warning' : 'bg-success-transparent text-success'; ?>">
                        <?= $socialBuffMaintenance ? 'Đang bảo trì' : 'Đang nhận đơn'; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="social_buff_api_url">Địa chỉ API</label>
                        <input class="form-control" id="social_buff_api_url" name="social_buff_api_url" type="url" inputmode="url" maxlength="500" placeholder="https://..." value="<?= htmlspecialchars($socialBuffConfig['base_url'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="form-text">Chỉ chấp nhận địa chỉ HTTPS.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="social_buff_timeout">Thời gian chờ (giây)</label>
                        <input class="form-control" id="social_buff_timeout" name="social_buff_timeout" type="number" min="10" max="60" step="1" inputmode="numeric" value="<?= (int) $socialBuffConfig['timeout']; ?>" required>
                        <div class="form-text">Từ 10 đến 60 giây.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="social_buff_api_key">Khóa API</label>
                        <input class="form-control" id="social_buff_api_key" name="social_buff_api_key" type="password" maxlength="255" autocomplete="new-password" placeholder="Nhập khóa mới để thay thế">
                        <span class="social-buff-api-key-status"><i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i><?= htmlspecialchars($socialBuffApiKeyStatus, ENT_QUOTES, 'UTF-8'); ?> Để trống trường này để giữ nguyên khóa hiện tại.</span>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="social_buff_markup_percent">Lợi nhuận (%)</label>
                        <input class="form-control" id="social_buff_markup_percent" name="social_buff_markup_percent" type="number" min="0" max="500" step="0.01" inputmode="decimal" value="<?= htmlspecialchars((string) $socialBuffConfig['markup_percent'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="form-text">Từ 0% đến 500%.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="social_buff_service_notes">Mô tả gói SV hiển thị cho khách</label>
                        <textarea class="form-control" id="social_buff_service_notes" name="social_buff_service_notes" rows="5" maxlength="16000" placeholder="SV3 | Sub Việt Nam, tốc độ 15.000/ngày, bảo hành 7 ngày&#10;SV5 | Sub Việt Nam, tốc độ 10.000/ngày, bảo hành 7 ngày"><?= htmlspecialchars($socialBuffServiceNotes, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="form-text">Mỗi dòng theo dạng <code>SV3 | Mô tả khách nhìn thấy</code>. Chỉ các mã có mô tả mới hiện nội dung phụ dưới tên dịch vụ.</div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="social-buff-maintenance-summary">
                    <div>
                        <h6 class="mb-1">Bảo trì dịch vụ</h6>
                        <p class="text-muted mb-0">Khi bật, khách hàng không thể tạo đơn mới. Tài khoản quản trị vẫn sử dụng và theo dõi đơn như bình thường.</p>
                    </div>
                    <div class="form-check form-switch social-buff-maintenance-switch flex-shrink-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="social_buff_maintenance" name="social_buff_maintenance" value="1" <?= $socialBuffMaintenance ? 'checked' : ''; ?> aria-describedby="social-buff-maintenance-help">
                        <label class="form-check-label ms-2" for="social_buff_maintenance">Bật bảo trì</label>
                    </div>
                </div>
                <div id="social-buff-maintenance-help" class="alert alert-light border mt-4 mb-0 d-flex align-items-start gap-2" role="note">
                    <i class="fa-solid fa-circle-info text-primary mt-1" aria-hidden="true"></i>
                    <span>Trạng thái này được áp dụng ngay sau khi lưu. Các đơn đã tạo vẫn có thể được quản trị viên cập nhật trạng thái.</span>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" name="SaveSocialBuffSettings" class="btn btn-primary social-buff-admin-save">
                    <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Lưu cấu hình
                </button>
            </div>
        </div>
    </form>
</div>
