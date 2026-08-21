<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/social-buff.php';

function social_buff_admin_save_setting($name, $value)
{
    global $CMSNT;

    $existing = $CMSNT->get_row_safe('SELECT `id` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($existing) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
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

    $maintenance = isset($_POST['social_buff_maintenance']) ? '1' : '0';
    if (!social_buff_admin_save_setting('social_buff_maintenance', $maintenance)) {
        admin_msg_error(__('Unable to save settings. Please try again.'), base_url_admin('settings&tab=social-buff'));
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $maintenance === '1'
            ? 'Bật bảo trì Buff mạng xã hội'
            : 'Tắt bảo trì Buff mạng xã hội'
    ]);
    admin_msg_success(__('Saved successfully'), base_url_admin('settings&tab=social-buff'));
}

$socialBuffMaintenance = social_buff_maintenance_enabled();
?>

<style>
    .social-buff-maintenance-summary {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
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

    @media (max-width: 575.98px) {
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
            <div class="card-header justify-content-between">
                <div class="card-title mb-0"><i class="fa-solid fa-bolt me-2 text-primary"></i>Buff mạng xã hội</div>
                <span class="badge <?= $socialBuffMaintenance ? 'bg-warning-transparent text-warning' : 'bg-success-transparent text-success'; ?>">
                    <?= $socialBuffMaintenance ? 'Đang bảo trì' : 'Đang nhận đơn'; ?>
                </span>
            </div>
            <div class="card-body">
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
                <button type="submit" name="SaveSocialBuffSettings" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Lưu và áp dụng
                </button>
            </div>
        </div>
    </form>
</div>
