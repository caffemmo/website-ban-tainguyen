<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/locket-gold.php';

function locket_gold_admin_save_setting($name, $value)
{
    global $CMSNT;

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

function locket_gold_admin_price($value)
{
    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value) || (float) $value <= 0 || (float) $value > 1000000000) {
        return false;
    }
    return number_format((float) $value, 2, '.', '');
}

if (isset($_POST['SaveLocketGoldSettings'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('settings&tab=locket-gold'), 1200);
    }
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("This function cannot be used because this is a demo site")){window.history.back().location.reload();}</script>');
    }

    $prices = [];
    foreach (locket_gold_packages() as $package) {
        $price = locket_gold_admin_price($_POST[$package['setting']] ?? '');
        if ($price === false) {
            admin_msg_error('Giá ' . $package['label'] . ' phải lớn hơn 0 và hợp lệ.', base_url_admin('settings&tab=locket-gold'), 1200);
        }
        $prices[$package['setting']] = $price;
    }

    $warrantyDays = validate_int($_POST['locket_gold_warranty_days'] ?? '', 1, 3650);
    if ($warrantyDays === false) {
        admin_msg_error('Thời hạn bảo hành phải từ 1 đến 3650 ngày.', base_url_admin('settings&tab=locket-gold'), 1200);
    }

    locket_gold_admin_save_setting('locket_gold_enabled', isset($_POST['locket_gold_enabled']) ? '1' : '0');
    locket_gold_admin_save_setting('locket_gold_warranty_days', (string) $warrantyDays);
    foreach ($prices as $setting => $price) {
        locket_gold_admin_save_setting($setting, $price);
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật cấu hình Locket Gold')
    ]);
    admin_msg_success('Đã lưu cấu hình Locket Gold.', base_url_admin('settings&tab=locket-gold'), 900);
}

$locketGoldEnabled = locket_gold_enabled();
$locketGoldWarrantyDays = locket_gold_warranty_days();
$locketGoldPackages = locket_gold_packages();
?>

<style>
    .locket-admin-intro { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:22px 24px; margin-bottom:20px; color:#172033; background:#fffdf4; border:1px solid #f4df8f; border-left:4px solid #eab308; border-radius:10px; }
    .locket-admin-intro h4 { margin:0 0 5px; font-weight:700; }
    .locket-admin-intro p { margin:0; color:#6b7280; }
    .locket-admin-status { display:inline-flex; min-height:30px; align-items:center; gap:7px; padding:6px 10px; border:1px solid #d1d5db; border-radius:999px; color:#6b7280; background:#fff; white-space:nowrap; font-size:12px; font-weight:700; }
    .locket-admin-status.is-ready { border-color:#b8e2ca; color:#16734a; background:#f0fbf4; }
    .locket-admin-status i { font-size:8px; }
    .locket-admin-card { height:100%; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 7px 20px rgba(31,55,82,.05); }
    .locket-admin-card .card-header { padding:18px 20px 0; border:0; background:transparent; }
    .locket-admin-card .card-body { padding:18px 20px 20px; }
    .locket-admin-card .card-title { font-size:16px; font-weight:700; }
    .locket-admin-label { display:flex; align-items:center; gap:8px; font-weight:600; }
    .locket-admin-label i { width:18px; color:#b77900; text-align:center; }
    .locket-admin-help { color:#718096; font-size:12px; line-height:1.5; }
    .locket-admin-note { display:flex; gap:10px; padding:12px 14px; border:1px solid #dce8ef; border-radius:8px; color:#36566b; background:#f6fbfd; font-size:12px; line-height:1.55; }
    .locket-admin-note i { margin-top:2px; color:#0b95a0; }
    .locket-admin-save { min-width:180px; color:#fff; background:#111827; border-color:#111827; }
    .locket-admin-save:hover { color:#fff; background:#263244; border-color:#263244; }
    @media (max-width:575.98px) { .locket-admin-intro { align-items:flex-start; flex-direction:column; padding:18px; } }
</style>

<div class="tab-pane text-muted show active" id="locket-gold-settings" role="tabpanel">
    <div class="locket-admin-intro">
        <div>
            <h4><i class="fa-solid fa-crown me-2" aria-hidden="true"></i><?= __('Cấu hình Locket Gold'); ?></h4>
            <p><?= __('Quản lý giá bán, thời hạn bảo hành và trạng thái nhận đơn thủ công.'); ?></p>
        </div>
        <span class="locket-admin-status <?= $locketGoldEnabled ? 'is-ready' : ''; ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= $locketGoldEnabled ? __('Đang mở bán') : __('Đang tắt'); ?></span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card locket-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-tags me-2 text-warning" aria-hidden="true"></i><?= __('Giá các gói'); ?></div></div>
                    <div class="card-body">
                        <?php foreach ($locketGoldPackages as $package): ?>
                        <div class="mb-3">
                            <label class="form-label locket-admin-label" for="<?= htmlspecialchars($package['setting'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-crown" aria-hidden="true"></i><?= htmlspecialchars($package['label'], ENT_QUOTES, 'UTF-8'); ?> <small class="text-muted">(<?= (int) $package['max_accounts']; ?> tài khoản)</small></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="<?= htmlspecialchars($package['setting'], ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($package['setting'], ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars(number_format((float) $package['price'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" min="1" max="1000000000" step="1000" required>
                                <span class="input-group-text">VND</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="locket-admin-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span><?= __('Giá hiển thị cho khách hàng là giá bán của Caffemmo. Đơn được tạo để admin xử lý thủ công sau khi ví bị trừ.'); ?></span></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card locket-admin-card">
                    <div class="card-header"><div class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-warning" aria-hidden="true"></i><?= __('Trạng thái dịch vụ'); ?></div></div>
                    <div class="card-body">
                        <div class="form-check form-switch form-check-lg mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="locket_gold_enabled" name="locket_gold_enabled" value="1" <?= $locketGoldEnabled ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="locket_gold_enabled"><?= __('Cho phép khách tạo đơn Locket Gold'); ?></label>
                        </div>
                        <div class="mb-4">
                            <label class="form-label locket-admin-label" for="locket_gold_warranty_days"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><?= __('Thời hạn bảo hành'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="locket_gold_warranty_days" name="locket_gold_warranty_days" value="<?= (int) $locketGoldWarrantyDays; ?>" min="1" max="3650" step="1" required>
                                <span class="input-group-text">ngày</span>
                            </div>
                        </div>
                        <div class="locket-admin-help mb-3"><?= __('Khách chỉ cần nhập username, không nhập mật khẩu hoặc cookie. Khi đơn lỗi, admin có thể hoàn tiền ngay trong màn hình xử lý.'); ?></div>
                        <div class="locket-admin-help"><strong><?= __('Trạng thái đơn:'); ?></strong><br><?= __('Chờ xử lý, Đang xử lý, Đã hoàn tất, Thất bại hoặc Đã hoàn tiền.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button type="submit" name="SaveLocketGoldSettings" class="btn locket-admin-save"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i><?= __('Lưu cấu hình'); ?></button></div>
    </form>
</div>
