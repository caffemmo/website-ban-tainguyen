<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<?php
// Xử lý lưu cài đặt
if (isset($_POST['SaveSettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Thay đổi cài đặt bảo mật')
    ]);

    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }

    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Thay đổi cài đặt bảo mật'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
<div class="tab-pane text-muted show active" id="security" role="tabpanel">
    <div class="d-flex align-items-center mb-4">
        <div class="flex-shrink-0">
            <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                <i class="ri-shield-check-line fs-18 text-primary"></i>
            </div>
        </div>
        <div class="flex-grow-1 ms-3">
            <h4 class="mb-1"><?= __('Cài đặt bảo mật hệ thống'); ?></h4>
            <p class="text-muted mb-0">
                <?= __('Cấu hình các tính năng bảo mật để bảo vệ hệ thống khỏi các cuộc tấn công'); ?>
            </p>
        </div>
    </div>
    <form action="" method="POST">
        <div class="row">
            <!-- Card 1: Bảo vệ chống Brute Force -->
            <div class="col-xl-6">
                <div class="card border border-danger-subtle h-100">
                    <div class="card-header bg-danger-subtle">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i
                                    class="ri-shield-cross-line fs-18 text-danger"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h6 class="card-title mb-0 fw-semibold text-danger">
                                    <?= __('Bảo vệ chống Brute Force'); ?>
                                </h6>
                                <small class="text-muted">
                                    <?= __('Ngăn chặn tấn công scan tài khoản'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Block IP Login -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-lock-line me-1 text-primary"></i>
                                <?= __('Khóa IP nếu đăng nhập sai mật khẩu quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_login'); ?>"
                                    name="limit_block_ip_login" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Khuyến nghị: ≤ 5 lần để bảo mật tốt nhất'); ?>
                            </div>
                        </div>

                        <!-- Block Client Account -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i
                                    class="ri-user-forbid-line me-1 text-warning"></i>
                                <?= __('Khóa tài khoản nếu đăng nhập sai mật khẩu quá nhiều lần'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_client_login'); ?>"
                                    name="limit_block_client_login" min="1"
                                    max="50">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Khuyến nghị: ≤ 10 lần để cân bằng bảo mật và trải nghiệm'); ?>
                            </div>
                        </div>

                        <!-- Block API -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-key-line me-1 text-info"></i>
                                <?= __('Khóa IP nếu API KEY sai quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_api'); ?>"
                                    name="limit_block_ip_api" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Khuyến nghị: ≤ 20 lần để bảo vệ API'); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i
                                    class=" ri-shield-keyhole-fill me-1 text-info"></i>
                                <?= __('Khóa IP nếu nhập sai 2FA quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_2fa'); ?>"
                                    name="limit_block_ip_2fa" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Khuyến nghị: ≤ 10 lần'); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-shield-flash-line me-1 text-info"></i>
                                <?= __('Khóa IP nếu nhập sai OTP quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_otp'); ?>"
                                    name="limit_block_ip_otp" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Khuyến nghị: ≤ 10 lần'); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-shield-user-line me-1 text-info"></i>
                                <?= __('Khóa IP nếu tạo hóa đơn nạp tiền quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_payment'); ?>"
                                    name="limit_block_ip_payment" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-shield-user-line me-1 text-info"></i>
                                <?= __('Khóa IP nếu yêu cầu khôi phục mật khẩu quá nhiều lần trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_reset_password'); ?>"
                                    name="limit_block_ip_reset_password" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="ri-shield-user-line me-1 text-info"></i>
                                <?= __('Khóa IP nếu load tất cả sản phẩm quá nhiều lần trong 15 phút (Spam Load Ajax Products)'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_load_products'); ?>"
                                    name="limit_block_ip_load_products" min="1">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Kiểm soát truy cập -->
            <div class="col-xl-6">
                <div class="card border border-primary-subtle h-100">
                    <div class="card-header bg-primary-subtle">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i
                                    class="ri-shield-check-line fs-18 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <h6
                                    class="card-title mb-0 fw-semibold text-primary">
                                    <?= __('Kiểm soát truy cập'); ?>
                                </h6>
                                <small class="text-muted">
                                    <?= __('Giới hạn số thiết bị và IP đăng nhập'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Admin Access Attempts -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                <i class="ri-admin-line me-1 text-danger"></i>
                                <?= __('Khóa IP truy cập trái phép Admin Panel trong 15 phút'); ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('limit_block_ip_admin_access'); ?>"
                                    name="limit_block_ip_admin_access" min="1"
                                    max="20">
                                <span
                                    class="input-group-text"><?= __('lần'); ?></span>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Số lần truy cập sai URL admin trước khi block IP'); ?>
                            </div>
                        </div>

                        <!-- Single IP Admin -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                <i class="ri-global-line me-1 text-success"></i>
                                <?= __('Chỉ cho phép Admin đăng nhập từ 1 IP'); ?>
                            </label>
                            <select class="form-select"
                                name="status_only_ip_login_admin" required>
                                <option value="0"
                                    <?= $CMSNT->site('status_only_ip_login_admin') == 0 ? 'selected' : ''; ?>>
                                    <i class="ri-close-line"></i> <?= __('Tắt'); ?>
                                    (<?= __('Cho phép nhiều IP'); ?>)
                                </option>
                                <option value="1"
                                    <?= $CMSNT->site('status_only_ip_login_admin') == 1 ? 'selected' : ''; ?>>
                                    <i class="ri-check-line"></i> <?= __('Bật'); ?>
                                    (<?= __('Chỉ 1 IP duy nhất'); ?>)
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Tự động đăng xuất phiên đăng nhập Admin cũ nếu có lần đăng nhập mới'); ?>
                            </div>
                        </div>

                        <!-- Single Device Admin -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                <i class="ri-computer-line me-1 text-warning"></i>
                                <?= __('Chỉ cho phép Admin đăng nhập từ 1 thiết bị'); ?>
                            </label>
                            <select class="form-select"
                                name="status_only_device_admin" required>
                                <option value="0"
                                    <?= $CMSNT->site('status_only_device_admin') == 0 ? 'selected' : ''; ?>>
                                    <?= __('Tắt'); ?>
                                    (<?= __('Cho phép nhiều thiết bị'); ?>)
                                </option>
                                <option value="1"
                                    <?= $CMSNT->site('status_only_device_admin') == 1 ? 'selected' : ''; ?>>
                                    <?= __('Bật'); ?>
                                    (<?= __('Chỉ 1 thiết bị duy nhất'); ?>)
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Tự động đăng xuất phiên đăng nhập Admin cũ nếu có lần đăng nhập mới'); ?>
                            </div>
                        </div>

                        <!-- Single Device Client -->
                        <div class="mb-0">
                            <label class="form-label fw-medium">
                                <i class="ri-smartphone-line me-1 text-info"></i>
                                <?= __('Chỉ cho phép Client đăng nhập từ 1 thiết bị'); ?>
                            </label>
                            <select class="form-select"
                                name="status_only_device_client" required>
                                <option value="0"
                                    <?= $CMSNT->site('status_only_device_client') == 0 ? 'selected' : ''; ?>>
                                    <?= __('Tắt'); ?>
                                    (<?= __('Cho phép nhiều thiết bị'); ?>)
                                </option>
                                <option value="1"
                                    <?= $CMSNT->site('status_only_device_client') == 1 ? 'selected' : ''; ?>>
                                    <?= __('Bật'); ?>
                                    (<?= __('Chỉ 1 thiết bị duy nhất'); ?>)
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="ri-information-line me-1"></i>
                                <?= __('Tự động đăng xuất phiên đăng nhập Client cũ nếu có lần đăng nhập mới'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Card 3: Admin Panel URL Security -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border border-warning-subtle">
                        <div class="card-header bg-warning-subtle">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i
                                        class="ri-shield-check-line fs-18 text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6
                                        class="card-title mb-0 fw-semibold text-warning">
                                        <?= __('Bảo mật khác'); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= __('Một số cấu hình bảo mật khác'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i
                                                class="ri-admin-line me-1 text-primary"></i>
                                            <?= __('Số lượng tài khoản có thể đăng ký tối đa của 1 IP'); ?>
                                        </label>
                                        <input name="max_register_ip" type="text"
                                            class="form-control"
                                            value="<?= $CMSNT->site('max_register_ip'); ?>"
                                            required>
                                        <div class="form-text">
                                            <i class="ri-information-line me-1"></i>
                                            <?= __('1 IP chỉ được phép đăng ký tối đa'); ?>
                                            <strong><?= $CMSNT->site('max_register_ip'); ?></strong>
                                            <?= __('tài khoản'); ?>.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i
                                                class="ri-time-line me-1 text-primary"></i>
                                            <?= __('Thời gian lưu đăng nhập'); ?>
                                        </label>
                                        <div class="input-group">
                                            <input name="session_login"
                                                type="number" class="form-control"
                                                value="<?= $CMSNT->site('session_login'); ?>"
                                                placeholder="<?= __('Nhập thời gian...'); ?>"
                                                required>
                                            <span class="input-group-text">
                                                <i class="ri-time-line me-1"></i>
                                                <?= __('giây'); ?>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            <i class="ri-information-line me-1"></i>
                                            <?= __('VD: 86400 = 24 giờ, 3600 = 1 giờ'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i
                                                class="ri-computer-line me-1 text-primary"></i>
                                            <?= __('Xác minh thiết bị người mua'); ?>
                                        </label>
                                        <select class="form-select"
                                            name="isPurchaseDeviceVerified" required>
                                            <option value="0"
                                                <?= $CMSNT->site('isPurchaseDeviceVerified') == 0 ? 'selected' : ''; ?>>
                                                <?= __('Tắt'); ?>
                                            </option>
                                            <option value="1"
                                                <?= $CMSNT->site('isPurchaseDeviceVerified') == 1 ? 'selected' : ''; ?>>
                                                <?= __('Bật'); ?>
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            <i class="ri-information-line me-1"></i>
                                            <?= __('Khi bật, chỉ có thiết bị đã mua đơn hàng mới có thể xem chi tiết đơn hàng (Sử dụng User-Agent để kiểm tra).'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i
                                                class="ri-global-line me-1 text-primary"></i>
                                            <?= __('Xác minh IP người mua'); ?>
                                        </label>
                                        <select class="form-select"
                                            name="isPurchaseIpVerified" required>
                                            <option value="0"
                                                <?= $CMSNT->site('isPurchaseIpVerified') == 0 ? 'selected' : ''; ?>>
                                                <?= __('Tắt'); ?>
                                            </option>
                                            <option value="1"
                                                <?= $CMSNT->site('isPurchaseIpVerified') == 1 ? 'selected' : ''; ?>>
                                                <?= __('Bật'); ?>
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            <i class="ri-information-line me-1"></i>
                                            <?= __('Khi bật, chỉ IP đã thực hiện mua đơn hàng mới có thể xem được chi tiết đơn hàng đó.'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i
                                                class="ri-key-line me-1 text-warning"></i>
                                            <?= __('Mã bí mật Cron Job'); ?>
                                        </label>
                                        <div class="input-group">
                                            <input name="key_cron_job" type="text"
                                                class="form-control"
                                                value="<?= $CMSNT->site('key_cron_job'); ?>"
                                                required readonly>
                                            <button type="button"
                                                class="btn btn-primary"
                                                onclick="generateKeyCronJob()">
                                                <i
                                                    class="ri-refresh-line me-1"></i><?= __('Tạo mới'); ?>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?= __('Mã bí mật Cron Job sẽ được sử dụng để xác thực yêu cầu từ Cron Job, tránh spam cron job từ bên ngoài.'); ?>
                                        </div>
                                    </div>
                                    <script>
                                        /**
                                         * Hàm tạo random key 16 ký tự cho Cron Job
                                         * Sử dụng các ký tự alphanumeric (a-z, A-Z, 0-9) để đảm bảo tính bảo mật
                                         */
                                        function generateKeyCronJob() {
                                            // Xác nhận từ user trước khi thay đổi
                                            cuteAlert({
                                                type: "question",
                                                title: "<?= __('Xác nhận thay đổi'); ?>",
                                                message: "<?= __('Bạn có chắc chắn muốn tạo mã bí mật Cron Job mới?'); ?>",
                                                confirmText: "<?= __('Đồng ý'); ?>",
                                                cancelText: "<?= __('Hủy'); ?>"
                                            }).then((confirmed) => {
                                                if (confirmed) {
                                                    // Tạo random key 16 ký tự
                                                    const characters =
                                                        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                                    let randomKey = '';

                                                    // Tạo 16 ký tự random
                                                    for (let i = 0; i < 16; i++) {
                                                        randomKey += characters
                                                            .charAt(Math.floor(Math
                                                                .random() *
                                                                characters
                                                                .length));
                                                    }

                                                    // Cập nhật giá trị vào input field
                                                    const keyInput = document
                                                        .querySelector(
                                                            'input[name="key_cron_job"]'
                                                        );
                                                    if (keyInput) {
                                                        keyInput.value = randomKey;

                                                        // Hiệu ứng highlight để user biết đã thay đổi
                                                        keyInput.style
                                                            .backgroundColor =
                                                            '#fff3cd';
                                                        keyInput.style.borderColor =
                                                            '#ffc107';

                                                        // Reset highlight sau 2 giây
                                                        setTimeout(() => {
                                                            keyInput.style
                                                                .backgroundColor =
                                                                '';
                                                            keyInput.style
                                                                .borderColor =
                                                                '';
                                                        }, 2000);

                                                        // Hiển thị thông báo thành công
                                                        cuteAlert({
                                                            type: "success",
                                                            title: "<?= __('Thành công!'); ?>",
                                                            message: "<?= __('Đã tạo mã bí mật Cron Job mới. Vui lòng lưu cài đặt để áp dụng thay đổi.'); ?>",
                                                            confirmText: "<?= __('Đóng'); ?>"
                                                        });
                                                    } else {
                                                        // Lỗi không tìm thấy input field
                                                        cuteAlert({
                                                            type: "error",
                                                            title: "<?= __('Lỗi!'); ?>",
                                                            message: "<?= __('Không tìm thấy trường nhập mã bí mật.'); ?>",
                                                            confirmText: "<?= __('Đóng'); ?>"
                                                        });
                                                    }
                                                }
                                            });
                                        }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card border border-info-subtle h-100">
                        <div class="card-header bg-info-subtle">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt fs-18 text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="card-title mb-0 fw-semibold text-info">
                                        <?= __('Captcha'); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= __('Cấu hình Captcha ngăn chặn SPAM'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-power-off me-1 text-info"></i>
                                    <?= __('Trạng thái'); ?>
                                </label>
                                <select class="form-select" name="captcha_status"
                                    required>
                                    <option value="0"
                                        <?= $CMSNT->site('captcha_status') == 0 ? 'selected' : ''; ?>>
                                        <?= __('Tắt'); ?>
                                    </option>
                                    <option value="1"
                                        <?= $CMSNT->site('captcha_status') == 1 ? 'selected' : ''; ?>>
                                        <?= __('Bật'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-puzzle-piece me-1 text-info"></i>
                                    <?= __('Loại Captcha'); ?>
                                </label>
                                <select class="form-select" name="captcha_type"
                                    required>
                                    <option value="reCAPTCHA"
                                        <?= $CMSNT->site('captcha_type') == 'reCAPTCHA' ? 'selected' : ''; ?>>
                                        <?= __('reCAPTCHA (Google)'); ?>
                                    </option>
                                    <option value="Cloudflare"
                                        <?= $CMSNT->site('captcha_type') == 'Cloudflare' ? 'selected' : ''; ?>>
                                        <?= __('Cloudflare Captcha'); ?>
                                    </option>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?= __('Chọn loại Captcha sau đó cấu hình Site Key, Secret Key để sử dụng.'); ?>
                                </div>
                                <!-- Link hướng dẫn động theo loại Captcha -->
                                <div id="captcha-help-link" class="alert alert-success-transparent border-0 mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas fa-book-open fs-16 text-success"></i>
                                        </div>
                                        <div>
                                            <strong><?= __('Cần trợ giúp?'); ?></strong><br>
                                            <a id="help-link" href="https://help.cmsnt.co/huong-dan/smmpanel2-huong-dan-cau-hinh-recaptcha/" target="_blank" class="text-success fw-medium">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                <span id="help-text"><?= __('Xem hướng dẫn chi tiết cấu hình reCAPTCHA'); ?></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-key me-1 text-info"></i>
                                    <?= __('Site Key'); ?>
                                </label>
                                <input class="form-control" type="text"
                                    name="captcha_site_key"
                                    value="<?= $CMSNT->site('captcha_site_key'); ?>"
                                    placeholder="<?= __('Nhập Site Key...'); ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-lock me-1 text-info"></i>
                                    <?= __('Secret Key'); ?>
                                </label>
                                <input class="form-control" type="text"
                                    name="captcha_secret_key"
                                    value="<?= $CMSNT->site('captcha_secret_key'); ?>"
                                    placeholder="<?= __('Nhập Secret Key...'); ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-list-check me-1 text-info"></i>
                                    <?= __('Module áp dụng Captcha'); ?>
                                </label>
                                <select class="form-control"
                                    name="captcha_modules[]"
                                    id="captcha-modules" multiple>
                                    <?php
                                    $selectedModules = explode(',', $CMSNT->site('captcha_modules') ?? '');
                                    $modules = [
                                        'login'             => __('Đăng nhập'),
                                        'register'          => __('Đăng ký'),
                                        'forgot_password'   => __('Quên mật khẩu'),
                                        'verify_2fa'        => __('Xác minh 2FA'),
                                        'verify_otp'        => __('Xác minh OTP')
                                    ];
                                    foreach ($modules as $value => $label):
                                        $selected = in_array($value, $selectedModules) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $value; ?>" <?= $selected; ?>><?= $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?= __('Nhưng module mà bạn chọn sẽ áp dụng tính năng xác thực Captcha khi submit.'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <i class="ri-information-line me-1"></i>
                            <?= __('Lưu ý: Những thay đổi này sẽ ảnh hưởng đến bảo mật toàn hệ thống'); ?>
                        </div>
                        <button type="submit" name="SaveSettings"
                            class="btn btn-danger btn-label">
                            <i
                                class="ri-save-line label-icon align-middle fs-16 me-2"></i>
                            <?= __('Lưu cài đặt bảo mật'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        // Function để cập nhật link hướng dẫn Captcha
        function updateCaptchaHelpLink() {
            const captchaType = $('select[name="captcha_type"]').val();
            const helpLink = $('#help-link');
            const helpText = $('#help-text');

            if (captchaType === 'reCAPTCHA') {
                helpLink.attr('href', 'https://help.cmsnt.co/huong-dan/huong-dan-cau-hinh-recaptcha-trong-shopclone7/');
                helpText.text('<?= __('Xem hướng dẫn chi tiết cấu hình reCAPTCHA'); ?>');
            } else if (captchaType === 'Cloudflare') {
                helpLink.attr('href', 'https://help.cmsnt.co/huong-dan/shopclone7-huong-dan-cau-hinh-captcha-su-dung-cloudflare/');
                helpText.text('<?= __('Xem hướng dẫn chi tiết cấu hình Cloudflare Captcha'); ?>');
            }
        }

        // Cập nhật link khi trang load
        updateCaptchaHelpLink();

        // Cập nhật link khi thay đổi loại Captcha
        $('select[name="captcha_type"]').on('change', function() {
            updateCaptchaHelpLink();
        });

        // Khởi tạo Choices.js cho select module captcha
        const captchaModulesChoice = new Choices('#captcha-modules', {
            removeItemButton: true,
            searchEnabled: false,
            noChoicesText: '<?= __('Không có lựa chọn nào'); ?>',
            itemSelectText: '<?= __('Nhấn để chọn'); ?>',
            // Preserve selected values from database
            silent: false
        });
    });
</script>