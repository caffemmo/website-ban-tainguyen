<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/netflix.php';

function netflix_admin_save_setting($name, $value)
{
    global $CMSNT;

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

function netflix_admin_price($value)
{
    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value) || (float) $value <= 0 || (float) $value > 1000000000) {
        return false;
    }
    return number_format((float) $value, 2, '.', '');
}

if (isset($_POST['SaveNetflixSettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $apiKey = trim((string) ($_POST['netflix_api_key'] ?? ''));
    $enabled = isset($_POST['netflix_enabled']) ? '1' : '0';
    $price = netflix_admin_price($_POST['netflix_price'] ?? '');
    if ($apiKey !== '' && mb_strlen($apiKey) > 255) {
        admin_msg_error('Khóa API Netflix không hợp lệ.', base_url_admin('settings&tab=netflix'), 1200);
    }
    if ($price === false) {
        admin_msg_error('Giá Netflix phải lớn hơn 0 và hợp lệ.', base_url_admin('settings&tab=netflix'), 1200);
    }

    if ($apiKey !== '') {
        netflix_admin_save_setting('netflix_api_key', $apiKey);
    }
    netflix_admin_save_setting('netflix_enabled', $enabled);
    netflix_admin_save_setting('netflix_price', $price);

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật cấu hình Netflix')
    ]);
    admin_msg_success('Đã lưu cấu hình Netflix.', base_url_admin('settings&tab=netflix'), 900);
}

$netflixConfigured = netflix_api_is_configured();
$netflixEnabled = netflix_is_enabled();
$netflixPrice = netflix_service_price();
$hasStoredKey = netflix_db_setting('netflix_api_key') !== '' || netflix_env('CAFFEMMO_NETFLIX_API_KEY') !== '';
?>

<style>
    .netflix-admin-intro { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:22px 24px; margin-bottom:20px; color:#fff; background:#1b1b1d; border-radius:12px; }
    .netflix-admin-intro h4 { margin:0 0 5px; font-weight:700; }
    .netflix-admin-intro p { margin:0; color:rgba(255,255,255,.72); }
    .netflix-admin-status { display:inline-flex; min-height:30px; align-items:center; gap:7px; padding:6px 10px; border:1px solid rgba(255,255,255,.25); border-radius:999px; white-space:nowrap; font-size:12px; font-weight:700; }
    .netflix-admin-status i { color:#f0b44c; font-size:8px; }
    .netflix-admin-status.is-ready i { color:#5ee0a2; }
    .netflix-admin-card { height:100%; border:1px solid #e7edf5; border-radius:12px; box-shadow:0 8px 24px rgba(31,55,82,.06); }
    .netflix-admin-card .card-header { padding:18px 20px 0; border:0; background:transparent; }
    .netflix-admin-card .card-body { padding:18px 20px 20px; }
    .netflix-admin-card .card-title { font-size:16px; font-weight:700; }
    .netflix-admin-label { display:flex; align-items:center; gap:8px; font-weight:600; }
    .netflix-admin-label i { width:18px; color:#c51f2a; text-align:center; }
    .netflix-admin-help { color:#75869a; font-size:12px; line-height:1.5; }
    .netflix-admin-note { display:flex; gap:10px; padding:12px 14px; border:1px solid #f0c6ca; border-radius:9px; color:#744348; background:#fff5f5; font-size:12px; line-height:1.55; }
    .netflix-admin-note i { margin-top:2px; color:#c51f2a; }
    .netflix-admin-save { min-width:180px; background:#c51f2a; border-color:#c51f2a; }
    .netflix-admin-save:hover { background:#ad1721; border-color:#ad1721; }
    @media (max-width:575.98px) { .netflix-admin-intro { align-items:flex-start; flex-direction:column; padding:18px; } }
</style>

<div class="tab-pane text-muted show active" id="netflix-settings" role="tabpanel">
    <div class="netflix-admin-intro">
        <div>
            <h4><i class="fa-solid fa-play me-2" aria-hidden="true"></i><?= __('Cấu hình Netflix'); ?></h4>
            <p><?= __('Thiết lập API CTV để tạo link xem Netflix cho khách hàng.'); ?></p>
        </div>
        <span class="netflix-admin-status <?= $netflixConfigured ? 'is-ready' : ''; ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= $netflixConfigured ? __('Đã sẵn sàng') : ($netflixEnabled ? __('Chưa có API key') : __('Đang tắt')); ?></span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card netflix-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-link me-2 text-danger" aria-hidden="true"></i><?= __('Kết nối API'); ?></div></div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label netflix-admin-label" for="netflix_api_key"><i class="fa-solid fa-key" aria-hidden="true"></i><?= __('CTV API Key'); ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="netflix_api_key" name="netflix_api_key" placeholder="<?= $hasStoredKey ? __('Đã lưu khóa máy chủ, để trống để giữ nguyên') : __('Nhập CTV API Key'); ?>" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="netflix_api_key_toggle" title="<?= __('Hiện hoặc ẩn khóa'); ?>" aria-label="<?= __('Hiện hoặc ẩn khóa'); ?>"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
                            </div>
                            <div class="netflix-admin-help mt-2"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= __('Khóa chỉ lưu trong server, không trả về giao diện khách hàng.'); ?></div>
                        </div>
                        <div class="netflix-admin-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= __('API key được gửi trực tiếp từ máy chủ đến nhà cung cấp. Không đặt key trong JavaScript, HTML công khai hoặc URL.'); ?></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card netflix-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-danger" aria-hidden="true"></i><?= __('Trạng thái dịch vụ'); ?></div></div>
                    <div class="card-body">
                        <div class="form-check form-switch form-check-lg mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="netflix_enabled" name="netflix_enabled" value="1" <?= $netflixEnabled ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="netflix_enabled"><?= __('Cho phép khách hàng dùng Netflix'); ?></label>
                        </div>
                        <div class="mb-4">
                            <label class="form-label netflix-admin-label" for="netflix_price"><i class="fa-solid fa-tags" aria-hidden="true"></i><?= __('Giá mỗi lần lấy link'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="netflix_price" name="netflix_price" value="<?= htmlspecialchars(number_format($netflixPrice, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" min="1" max="1000000000" step="1000" required>
                                <span class="input-group-text">VND</span>
                            </div>
                            <div class="netflix-admin-help mt-2"><?= __('Giá được trừ một lần khi khách hàng lấy link mới. Tạo lại link không trừ thêm tiền.'); ?></div>
                        </div>
                        <div class="netflix-admin-help mb-3"><?= __('Khi tắt, nút lấy link sẽ tạm khóa nhưng API key và giá vẫn được giữ lại.'); ?></div>
                        <div class="netflix-admin-help"><strong><?= __('Endpoint lấy cookie:'); ?></strong><br><code>api.tiembanh4k.com/api/ctv-api/get-cookie</code></div>
                        <div class="netflix-admin-help mt-2"><strong><?= __('Endpoint làm mới link:'); ?></strong><br><code>backend-c0r3-7xpq9zn2025.onrender.com/api/ctv-api/regenerate-token</code></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button type="submit" name="SaveNetflixSettings" class="btn btn-primary netflix-admin-save"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i><?= __('Lưu cấu hình'); ?></button></div>
    </form>
</div>

<script>
    (function () {
        var input = document.getElementById('netflix_api_key');
        var button = document.getElementById('netflix_api_key_toggle');
        if (!input || !button) return;
        button.addEventListener('click', function () {
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('i').className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }());
</script>
