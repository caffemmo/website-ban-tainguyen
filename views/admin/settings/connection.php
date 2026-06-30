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
        'action'        => __('Thay đổi cài đặt kết nối')
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
    $my_text = str_replace('{action}', __('Thay đổi cài đặt kết nối'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
<div class="tab-pane text-muted show active" id="ket-noi" role="tabpanel">
    <h4><?= __('Kết nối'); ?></h4>
    <form action="" method="POST">
        <div class="row g-4">
            <!-- Cột trái: SMTP + Telegram -->
            <div class="col-lg-6">
                <!-- SMTP Configuration -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <img src="<?= BASE_URL('assets/img/icon-smtp.png'); ?>" alt="SMTP" class="w-100 h-100">
                            </div>
                            <span class="fw-semibold fs-15"><?= __('Cấu hình SMTP'); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- SMTP Status -->
                        <div class="mb-4">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fa fa-toggle-on me-2 text-success"></i>
                                <?= __('Trạng thái SMTP'); ?>
                            </label>
                            <select class="form-select" name="smtp_status">
                                <option value="1" <?= $CMSNT->site('smtp_status') == 1 ? 'selected' : ''; ?>>
                                    <?= __('Bật'); ?> (ON)
                                </option>
                                <option value="0" <?= $CMSNT->site('smtp_status') == 0 ? 'selected' : ''; ?>>
                                    <?= __('Tắt'); ?> (OFF)
                                </option>
                            </select>
                        </div>

                        <!-- SMTP Host -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-server me-2 text-primary"></i>
                                <?= __('SMTP Host'); ?>
                            </label>
                            <input type="text" name="smtp_host" class="form-control"
                                placeholder="<?= __('VD: smtp.gmail.com'); ?>"
                                value="<?= $CMSNT->site('smtp_host'); ?>">
                        </div>

                        <!-- SMTP Encryption & Port -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium d-flex align-items-center">
                                        <i class="fas fa-shield-alt me-2 text-warning"></i>
                                        <?= __('Mã hóa'); ?>
                                    </label>
                                    <input type="text" name="smtp_encryption" class="form-control"
                                        placeholder="<?= __('ssl/tls'); ?>"
                                        value="<?= $CMSNT->site('smtp_encryption'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium d-flex align-items-center">
                                        <i class="fas fa-network-wired me-2 text-info"></i>
                                        <?= __('Cổng'); ?>
                                    </label>
                                    <input type="text" name="smtp_port" class="form-control"
                                        placeholder="<?= __('465, 587'); ?>"
                                        value="<?= $CMSNT->site('smtp_port'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Email -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fa fa-envelope me-2 text-danger"></i>
                                <?= __('Email SMTP'); ?>
                            </label>
                            <input type="text" name="smtp_email" class="form-control"
                                placeholder="<?= __('VD: yourmail@gmail.com'); ?>"
                                value="<?= $CMSNT->site('smtp_email'); ?>">
                        </div>

                        <!-- From Email Address -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-at me-2 text-success"></i>
                                <?= __('Email gửi đi (From)'); ?>
                            </label>
                            <input type="text" name="smtp_from_email" class="form-control"
                                placeholder="<?= __('Để trống sẽ dùng Email SMTP'); ?>"
                                value="<?= $CMSNT->site('smtp_from_email'); ?>"
                                autocomplete="off">
                            <div class="form-text text-muted">
                                <?= __('Nếu để trống, hệ thống sẽ dùng Email SMTP làm địa chỉ gửi đi.'); ?>
                            </div>
                        </div>

                        <!-- SMTP Password -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-key me-2 text-secondary"></i>
                                <?= __('Mật khẩu SMTP'); ?>
                            </label>
                            <div class="input-group">
                                <input type="password" name="smtp_password" id="smtp_password_input"
                                    class="form-control"
                                    placeholder="<?= __('Nhập mật khẩu SMTP...'); ?>"
                                    value="<?= $CMSNT->site('smtp_password'); ?>"
                                    autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="btn_toggle_smtp_password"
                                    title="<?= __('Hiện/Ẩn mật khẩu'); ?>">
                                    <i class="fas fa-eye-slash" id="smtp_password_eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Help Link & Test -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <a href="https://help.cmsnt.co/huong-dan/huong-dan-cau-hinh-smtp-vao-website-shopclone7/"
                                    target="_blank" class="text-primary fw-medium fs-13">
                                    <i class="fas fa-external-link-alt me-1"></i><?= __('Xem hướng dẫn tích hợp SMTP'); ?>
                                </a>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_test_smtp">
                                <i class="fas fa-paper-plane me-1"></i>
                                <?= __('Gửi test'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Telegram Bot Configuration -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <img src="<?= BASE_URL('assets/img/icon-bot-telegram.avif'); ?>" alt="Telegram" class="w-100 h-100">
                            </div>
                            <span class="fw-semibold fs-15"><?= __('Bot thông báo Telegram'); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Telegram Status -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-robot me-2 text-success"></i>
                                <?= __('Trạng thái Bot'); ?>
                            </label>
                            <select class="form-select" name="telegram_status">
                                <option <?= $CMSNT->site('telegram_status') == 1 ? 'selected' : ''; ?> value="1">
                                    <?= __('Bật'); ?> (ON)
                                </option>
                                <option <?= $CMSNT->site('telegram_status') == 0 ? 'selected' : ''; ?> value="0">
                                    <?= __('Tắt'); ?> (OFF)
                                </option>
                            </select>
                        </div>

                        <!-- Bot Token -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-key me-2 text-warning"></i>
                                <?= __('Bot Token'); ?>
                            </label>
                            <input type="text" name="telegram_token"
                                value="<?= $CMSNT->site('telegram_token'); ?>"
                                class="form-control"
                                placeholder="123456789:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
                            <div class="form-text">
                                <a class="text-primary fw-medium"
                                    href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-bot-telegram-vao-shopclone7/"
                                    target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i><?= __('Xem hướng dẫn tạo bot'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Chat ID -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-comment-dots me-2 text-info"></i>
                                <?= __('Chat ID'); ?>
                            </label>
                            <input type="text" name="telegram_chat_id"
                                value="<?= $CMSNT->site('telegram_chat_id'); ?>"
                                class="form-control"
                                placeholder="-100XXXXXXXXX">
                            <div class="form-text">
                                <a class="text-primary fw-medium"
                                    href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-bot-telegram-vao-shopclone7/"
                                    target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i><?= __('Xem hướng dẫn lấy Chat ID'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Telegram API URL -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-globe me-2 text-secondary"></i>
                                <?= __('API Server'); ?>
                            </label>
                            <select class="form-select" name="telegram_url">
                                <option value="https://api.telegram.org/"
                                    <?= $CMSNT->site('telegram_url') == 'https://api.telegram.org/' ? 'selected' : ''; ?>>
                                    🌐 Official Telegram API
                                </option>
                                <option value="https://bypass-telegram.cmsnt.workers.dev/"
                                    <?= $CMSNT->site('telegram_url') == 'https://bypass-telegram.cmsnt.workers.dev/' ? 'selected' : ''; ?>>
                                    🚀 CMSNT Proxy Server
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= __('Sử dụng proxy nếu Telegram bị chặn tại Việt Nam'); ?>
                            </div>
                        </div>

                        <!-- Telegram Proxy -->
                        <div class="mb-3" id="telegram_proxy_section">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2 text-purple"></i>
                                <?= __('Proxy'); ?>
                            </label>
                            <div class="input-group">
                                <input type="text" name="telegram_proxy" id="telegram_proxy_input"
                                    value="<?= $CMSNT->site('telegram_proxy'); ?>"
                                    class="form-control"
                                    placeholder="ip:port:username:password hoặc ip:port">
                                <button class="btn btn-success" type="button" id="btnGetFreeProxy" onclick="getFreeProxy()">
                                    <span id="btnText">
                                        <i class="fas fa-download me-1"></i>Lấy Proxy Free
                                    </span>
                                    <span id="btnLoading" class="d-none">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Đang lấy...
                                    </span>
                                </button>
                            </div>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1 text-info"></i>
                                <?= __('Nếu máy chủ tại Việt Nam bị chặn kết nối Telegram, sử dụng Proxy để đảm bảo Bot hoạt động ổn định.'); ?>
                            </div>
                        </div>

                        <!-- Proxy Type -->
                        <div class="mb-3" id="telegram_proxy_type_section">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-cog me-2 text-dark"></i>
                                <?= __('Loại Proxy'); ?>
                            </label>
                            <select name="telegram_proxy_type" id="telegram_proxy_type_select" class="form-select">
                                <option value="SOCKS5" <?= $CMSNT->site('telegram_proxy_type') == 'SOCKS5' ? 'selected' : ''; ?>>SOCKS5</option>
                                <option value="HTTP" <?= $CMSNT->site('telegram_proxy_type') == 'HTTP' ? 'selected' : ''; ?>>HTTP</option>
                            </select>
                        </div>

                        <!-- Test Telegram -->
                        <div class="d-flex align-items-center justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-info btn-sm" id="btn_test_telegram">
                                <i class="fas fa-paper-plane me-1"></i>
                                <?= __('Gửi test'); ?>
                            </button>
                        </div>

                        <!-- Help Link -->
                        <div class="alert alert-info border-0">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="fas fa-info-circle fs-16"></i>
                                </div>
                                <div>
                                    <strong><?= __('Cần trợ giúp?'); ?></strong><br>
                                    <a href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-bot-telegram-vao-shopclone7/"
                                        target="_blank" class="text-primary fw-medium">
                                        <i class="fas fa-external-link-alt me-1"></i><?= __('Xem hướng dẫn chi tiết tích hợp Bot Telegram'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Google Analytics, Google Ads, ChatGPT, Check Live -->
            <div class="col-lg-6">
                <!-- Google Analytics -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <img src="<?= BASE_URL('assets/img/icon-Google-Analytics.png'); ?>" alt="Analytics" class="w-100 h-100">
                            </div>
                            <span class="fw-semibold fs-15"><?= __('Google Analytics'); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Trạng thái'); ?></label>
                                    <select class="form-select" name="google_analytics_status">
                                        <option <?= $CMSNT->site('google_analytics_status') == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('Bật'); ?> (ON)
                                        </option>
                                        <option <?= $CMSNT->site('google_analytics_status') == 0 ? 'selected' : ''; ?> value="0">
                                            <?= __('Tắt'); ?> (OFF)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Measurement ID'); ?></label>
                                    <input type="text" name="google_analytics_id"
                                        placeholder="VD: G-XXXXXXXX"
                                        value="<?= $CMSNT->site('google_analytics_id'); ?>"
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Ads -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <img src="<?= BASE_URL('mod/img/icon-Google-Ads.webp'); ?>" alt="Ads" class="w-100 h-100">
                            </div>
                            <span class="fw-semibold fs-15"><?= __('Google Ads'); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Trạng thái'); ?></label>
                                    <select class="form-select" name="google_ads_status">
                                        <option <?= $CMSNT->site('google_ads_status') == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('Bật'); ?> (ON)
                                        </option>
                                        <option <?= $CMSNT->site('google_ads_status') == 0 ? 'selected' : ''; ?> value="0">
                                            <?= __('Tắt'); ?> (OFF)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Google Ads ID'); ?></label>
                                    <input type="text" name="google_ads_id"
                                        placeholder="VD: AW-XXXXXXXX"
                                        value="<?= $CMSNT->site('google_ads_id'); ?>"
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Login -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger-gradient border-bottom-0" data-bs-toggle="collapse" href="#collapseGoogleLogin" role="button" aria-expanded="false" aria-controls="collapseGoogleLogin" style="cursor: pointer;">
                        <div class="card-title text-white mb-0 d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-white-transparent me-2">
                                    <svg width="20" height="20" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" style="margin:2px">
                                        <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#fff" />
                                        <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.837.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.44 15.983 5.485 18 9.003 18z" fill="#fff" />
                                        <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#fff" />
                                        <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.485 0 2.44 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#fff" />
                                    </svg>
                                </div>
                                <span class="fw-semibold fs-15"><?= __('Đăng nhập bằng Google'); ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="collapseGoogleLogin" class="collapse">
                        <div class="card-body">
                            <?php
                            // Kiểm tra thư viện google/apiclient đã cài chưa
                            // Nếu chưa → hiển thị cảnh báo, ẩn form cấu hình
                            if (!class_exists('Google_Client')):
                            ?>
                                <div class="alert alert-warning border-0 mb-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-2">
                                            <i class="fas fa-exclamation-triangle fs-16"></i>
                                        </div>
                                        <div>
                                            <strong><?= __('Chi phí tích hợp liên hệ Admin theo thông tin liên hệ tại'); ?></strong>
                                            <a href="#" target="_blank" rel="noopener noreferrer">đây.</a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Trạng thái bật/tắt -->
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Trạng thái'); ?></label>
                                    <select class="form-select" name="status_google_login">
                                        <option <?= $CMSNT->site('status_google_login') == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('Bật'); ?> (ON)
                                        </option>
                                        <option <?= $CMSNT->site('status_google_login') == 0 ? 'selected' : ''; ?> value="0">
                                            <?= __('Tắt'); ?> (OFF)
                                        </option>
                                    </select>
                                </div>

                                <!-- Client ID & Secret -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium"><?= __('Client ID'); ?></label>
                                            <input type="text" name="google_login_client_id"
                                                value="<?= $CMSNT->site('google_login_client_id'); ?>"
                                                class="form-control"
                                                placeholder="<?= __('Google OAuth Client ID'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium"><?= __('Client Secret'); ?></label>
                                            <input type="password" name="google_login_client_secret"
                                                value="<?= $CMSNT->site('google_login_client_secret'); ?>"
                                                class="form-control"
                                                placeholder="<?= __('Google OAuth Client Secret'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Redirect URI - readonly để admin copy vào Google Console -->
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><?= __('Authorized redirect URIs'); ?></label>
                                    <div class="input-group">
                                        <input type="text" readonly
                                            value="<?= base_url('api/callback_google_login.php'); ?>"
                                            class="form-control bg-light"
                                            id="google-redirect-uri">
                                        <button class="btn btn-outline-primary" type="button"
                                            onclick="var el=document.getElementById('google-redirect-uri');el.select();document.execCommand('copy');Swal.fire('<?= __('Đã sao chép!'); ?>','','success');"
                                            title="<?= __('Sao chép URL'); ?>">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?= __('Nhập vào Authorized redirect URIs trong Google Cloud Console'); ?>
                                    </div>
                                </div>

                                <!-- Hướng dẫn -->
                                <div class="alert alert-info border-0 mb-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-2">
                                            <i class="fas fa-info-circle fs-16"></i>
                                        </div>
                                        <div>
                                            <strong><?= __('Hướng dẫn:'); ?></strong><br>
                                            <?= __('1. Vào Google Cloud Console → APIs & Services → Credentials'); ?><br>
                                            <?= __('2. Tạo OAuth 2.0 Client ID (Web application)'); ?><br>
                                            <?= __('3. Thêm Redirect URI ở trên vào mục Authorized redirect URIs'); ?><br>
                                            <?= __('4. Sao chép Client ID và Client Secret vào đây'); ?><br>
                                            <a href="https://help.cmsnt.co/huong-dan/shopkey-huong-dan-cau-hinh-tinh-nang-dang-nhap-bang-google/" target="_blank" rel="noopener noreferrer">
                                                <?= __('Xem hướng dẫn chi tiết'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ChatGPT Configuration -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2 rounded">
                                <img src="https://i.imgur.com/5iOyCNW.png" alt="ChatGPT" class="w-100 h-100">
                            </div>
                            <span class="fw-semibold fs-15"><?= __('Tích hợp ChatGPT'); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- API Key -->
                        <div class="mb-4">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-key me-2 text-warning"></i>
                                <?= __('API Key'); ?>
                            </label>
                            <input type="text" name="chatgpt_api_key"
                                placeholder="VD: sk-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                                value="<?= $CMSNT->site('chatgpt_api_key'); ?>"
                                class="form-control">
                            <div class="form-text">
                                <i class="fas fa-shield-alt me-1"></i>
                                <?= __('Lấy API Key từ'); ?> <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary fw-medium">OpenAI Platform</a>
                            </div>
                        </div>

                        <!-- Model Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-medium d-flex align-items-center">
                                <i class="fas fa-microchip me-2 text-info"></i>
                                <?= __('Chọn Model AI'); ?>
                            </label>
                            <select class="form-select" name="chatgpt_model">
                                <optgroup label="💰 <?= __('Giá rẻ nhất (Khuyến nghị)'); ?>">
                                    <option value="gpt-4.1-nano"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4.1-nano' ? 'selected' : ''; ?>>
                                        gpt-4.1-nano — $0.10/1M tokens (rẻ nhất)</option>
                                    <option value="gpt-4o-mini"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4o-mini' ? 'selected' : ''; ?>>
                                        gpt-4o-mini — $0.15/1M tokens</option>
                                    <option value="gpt-4.1-mini"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4.1-mini' ? 'selected' : ''; ?>>
                                        gpt-4.1-mini — $0.40/1M tokens</option>
                                </optgroup>
                                <optgroup label="⚡ <?= __('Hiệu năng cao'); ?>">
                                    <option value="gpt-4o"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4o' ? 'selected' : ''; ?>>
                                        gpt-4o — $2.50/1M tokens</option>
                                    <option value="gpt-4.1"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4.1' ? 'selected' : ''; ?>>
                                        gpt-4.1 — $2.00/1M tokens</option>
                                </optgroup>
                                <optgroup label="🧠 <?= __('Reasoning (suy luận nâng cao)'); ?>">
                                    <option value="o3-mini"
                                        <?= $CMSNT->site('chatgpt_model') == 'o3-mini' ? 'selected' : ''; ?>>
                                        o3-mini — $1.10/1M tokens</option>
                                    <option value="o4-mini"
                                        <?= $CMSNT->site('chatgpt_model') == 'o4-mini' ? 'selected' : ''; ?>>
                                        o4-mini — $1.10/1M tokens</option>
                                </optgroup>
                                <optgroup label="📦 <?= __('Legacy (cũ)'); ?>">
                                    <option value="gpt-3.5-turbo"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-3.5-turbo' ? 'selected' : ''; ?>>
                                        gpt-3.5-turbo</option>
                                    <option value="gpt-4"
                                        <?= $CMSNT->site('chatgpt_model') == 'gpt-4' ? 'selected' : ''; ?>>
                                        gpt-4</option>
                                </optgroup>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= __('Khuyến nghị dùng gpt-4.1-nano hoặc gpt-4o-mini để tiết kiệm chi phí.'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Check live GMAIL -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger-gradient border-bottom-0" data-bs-toggle="collapse" href="#collapseGmail" role="button" aria-expanded="false" aria-controls="collapseGmail" style="cursor: pointer;">
                        <div class="card-title text-white mb-0 d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-white-transparent me-2">
                                    <img src="<?= BASE_URL('mod/img/icon-gmail.webp'); ?>" alt="Gmail" class="w-100 h-100">
                                </div>
                                <span class="fw-semibold fs-15"><?= __('Check live GMAIL'); ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="collapseGmail" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-medium"><?= __('API Link'); ?></label>
                                <input type="text" name="api_check_live_gmail"
                                    value="<?= $CMSNT->site('api_check_live_gmail'); ?>"
                                    class="form-control">
                                <div class="form-text"><?= __('Chỉ áp dụng cho API nội bộ'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('API Key'); ?></label>
                                        <input type="text" name="api_key_check_live_gmail"
                                            value="<?= $CMSNT->site('api_key_check_live_gmail'); ?>"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('Thời gian check live cách nhau'); ?></label>
                                        <div class="input-group">
                                            <input name="time_limit_check_live_gmail" type="text" class="form-control"
                                                value="<?= $CMSNT->site('time_limit_check_live_gmail'); ?>" required>
                                            <span class="input-group-text"><?= __('Giây'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Check live Hotmail/Outlook -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary-gradient border-bottom-0" data-bs-toggle="collapse" href="#collapseHotmail" role="button" aria-expanded="false" aria-controls="collapseHotmail" style="cursor: pointer;">
                        <div class="card-title text-white mb-0 d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-white-transparent me-2 rounded">
                                    <i class="fab fa-microsoft text-white fs-18"></i>
                                </div>
                                <span class="fw-semibold fs-15"><?= __('Check live Hotmail/Outlook'); ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="collapseHotmail" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-medium"><?= __('API Link'); ?></label>
                                <input type="text" name="api_check_live_hotmail"
                                    value="<?= $CMSNT->site('api_check_live_hotmail'); ?>"
                                    class="form-control">
                                <div class="form-text"><?= __('Chỉ áp dụng cho API nội bộ'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('API Key'); ?></label>
                                        <input type="text" name="api_key_check_live_hotmail"
                                            value="<?= $CMSNT->site('api_key_check_live_hotmail'); ?>"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('Thời gian check live cách nhau'); ?></label>
                                        <div class="input-group">
                                            <input name="time_limit_check_live_hotmail" type="text" class="form-control"
                                                value="<?= $CMSNT->site('time_limit_check_live_hotmail'); ?>" required>
                                            <span class="input-group-text"><?= __('Giây'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Nút mở modal tài liệu API check live -->
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalApiDoc">
                                    <i class="fas fa-book me-1"></i><?= __('Tài liệu API'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Check live Instagram -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #833AB4, #C13584, #E1306C, #F77737); border-bottom: 0; cursor: pointer;" data-bs-toggle="collapse" href="#collapseInstagram" role="button" aria-expanded="false" aria-controls="collapseInstagram">
                        <div class="card-title text-white mb-0 d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-white-transparent me-2">
                                    <img src="<?= BASE_URL('mod/img/icon-Instagram.webp'); ?>" alt="Instagram" class="w-100 h-100">
                                </div>
                                <span class="fw-semibold fs-15"><?= __('Check live Instagram'); ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="collapseInstagram" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-medium"><?= __('API Link'); ?></label>
                                <input type="text" name="api_check_live_instagram"
                                    value="<?= $CMSNT->site('api_check_live_instagram'); ?>"
                                    class="form-control">
                                <div class="form-text"><?= __('Chỉ áp dụng cho API nội bộ'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('API Key'); ?></label>
                                        <input type="text" name="api_key_check_live_instagram"
                                            value="<?= $CMSNT->site('api_key_check_live_instagram'); ?>"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('Thời gian check live cách nhau'); ?></label>
                                        <div class="input-group">
                                            <input name="time_limit_check_live_instagram" type="text" class="form-control"
                                                value="<?= $CMSNT->site('time_limit_check_live_instagram'); ?>" required>
                                            <span class="input-group-text"><?= __('Giây'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Nút mở modal tài liệu API check live -->
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalApiDoc">
                                    <i class="fas fa-book me-1"></i><?= __('Tài liệu API'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Check live Tiktok -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: #000000 !important; border-bottom: 0; cursor: pointer;" data-bs-toggle="collapse" href="#collapseTiktok" role="button" aria-expanded="false" aria-controls="collapseTiktok">
                        <div class="card-title text-white mb-0 d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm bg-white-transparent me-2 rounded">
                                    <i class="fab fa-tiktok text-white fs-18"></i>
                                </div>
                                <span class="fw-semibold fs-15"><?= __('Check live Tiktok'); ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="collapseTiktok" class="collapse">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-medium"><?= __('API Link'); ?></label>
                                <input type="text" name="api_check_live_tiktok"
                                    value="<?= $CMSNT->site('api_check_live_tiktok'); ?>"
                                    class="form-control">
                                <div class="form-text"><?= __('Chỉ áp dụng cho API nội bộ'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('API Key'); ?></label>
                                        <input type="text" name="api_key_check_live_tiktok"
                                            value="<?= $CMSNT->site('api_key_check_live_tiktok'); ?>"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium"><?= __('Thời gian check live cách nhau'); ?></label>
                                        <div class="input-group">
                                            <input name="time_limit_check_live_tiktok" type="text" class="form-control"
                                                value="<?= $CMSNT->site('time_limit_check_live_tiktok'); ?>" required>
                                            <span class="input-group-text"><?= __('Giây'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Nút mở modal tài liệu API check live -->
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalApiDoc">
                                    <i class="fas fa-book me-1"></i><?= __('Tài liệu API'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-end">
                    <button type="submit" name="SaveSettings" class="btn btn-primary btn-lg px-5">
                        <i class="fa fa-fw fa-save me-2"></i>
                        <?= __('Lưu cấu hình'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Tài liệu API Check Live - dùng chung cho tất cả các tab check live -->
<div class="modal fade" id="modalApiDoc" tabindex="-1" aria-labelledby="modalApiDocLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-gradient text-white">
                <h5 class="modal-title" id="modalApiDocLabel">
                    <i class="fas fa-book me-2"></i><?= __('Tài liệu API Check Live'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Hướng dẫn tổng quan -->
                <div class="alert alert-primary border-0 mb-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-info-circle fs-20"></i>
                        </div>
                        <div>
                            <strong><?= __('Hướng dẫn'); ?>:</strong><br>
                            <?= __('Admin cần thuê lập trình viên xây dựng API check live với đầu vào và đầu ra theo chuẩn bên dưới.'); ?>
                        </div>
                    </div>
                </div>

                <!-- Đầu vào API -->
                <h6 class="fw-bold text-primary mb-2">
                    <i class="fas fa-sign-in-alt me-1"></i><?= __('Đầu vào (Request)'); ?>
                </h6>
                <div class="bg-light rounded p-3 mb-4">
                    <code class="text-danger fs-14 d-block" style="word-break: break-all;">
                        https://domain/checklive.php?account=nickcanup&amp;api_key=YOUR_API_KEY
                    </code>
                    <div class="mt-2">
                        <table class="table table-sm table-bordered mb-0 fs-13">
                            <thead>
                                <tr>
                                    <th><?= __('Tham số'); ?></th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>account</code></td>
                                    <td><?= __('Tên tài khoản cần kiểm tra (bắt buộc)'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>api_key</code></td>
                                    <td><?= __('Khóa API xác thực (bắt buộc)'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Đầu ra API -->
                <h6 class="fw-bold text-success mb-2">
                    <i class="fas fa-sign-out-alt me-1"></i><?= __('Đầu ra (Response)'); ?>
                </h6>
                <div class="bg-dark rounded p-3">
                    <pre class="text-success mb-0" style="font-size: 13px; white-space: pre-wrap;">{
  "status": "success",
  "data": {
      "username": "uid",
      "status": "live hoặc die"
  },
  "message": "Check live completed successfully."
}</pre>
                </div>
                <div class="mt-3">
                    <table class="table table-sm table-bordered fs-13">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('Trường'); ?></th>
                                <th><?= __('Mô tả'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>status</code></td>
                                <td><?= __('Trạng thái request: success hoặc error'); ?></td>
                            </tr>
                            <tr>
                                <td><code>data.username</code></td>
                                <td><?= __('UID của tài khoản'); ?></td>
                            </tr>
                            <tr>
                                <td><code>data.status</code></td>
                                <td><?= __('Kết quả kiểm tra: live hoặc die'); ?></td>
                            </tr>
                            <tr>
                                <td><code>message</code></td>
                                <td><?= __('Thông báo kết quả'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i><?= __('Đóng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Toggle hiện/ẩn mật khẩu SMTP
        $('#btn_toggle_smtp_password').click(function() {
            var input = $('#smtp_password_input');
            var icon = $('#smtp_password_eye');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });

        // Xử lý nút Test SMTP
        $('#btn_test_smtp').click(function() {
            var smtpHost = $('input[name="smtp_host"]').val();
            var smtpPort = $('input[name="smtp_port"]').val();
            var smtpEncryption = $('input[name="smtp_encryption"]').val();
            var smtpEmail = $('input[name="smtp_email"]').val();
            var smtpFromEmail = $('input[name="smtp_from_email"]').val();
            var smtpPassword = $('input[name="smtp_password"]').val();

            if (!smtpHost || !smtpPort || !smtpEmail || !smtpPassword) {
                Swal.fire({
                    icon: 'warning',
                    title: '<?= __("Thiếu thông tin"); ?>',
                    text: '<?= __("Vui lòng điền đầy đủ thông tin SMTP trước khi kiểm tra"); ?>',
                });
                return;
            }

            Swal.fire({
                title: '<?= __("Gửi email test SMTP"); ?>',
                html: '<div class="text-start">' +
                    '<label class="form-label fw-medium"><?= __("Email nhận thư test"); ?></label>' +
                    '<input type="email" id="swal_test_email" class="form-control" placeholder="<?= __("Nhập email nhận test..."); ?>" value="<?= htmlspecialchars($getUser['email'], ENT_QUOTES, 'UTF-8'); ?>">' +
                    '<small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i><?= __("Email test sẽ được gửi từ"); ?>: <strong>' + (smtpFromEmail || smtpEmail) + '</strong></small>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> <?= __("Gửi test"); ?>',
                cancelButtonText: '<?= __("Hủy"); ?>',
                confirmButtonColor: '#3085d6',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    var testEmail = document.getElementById('swal_test_email').value.trim();
                    if (!testEmail) {
                        Swal.showValidationMessage('<?= __("Vui lòng nhập email nhận test"); ?>');
                        return false;
                    }
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(testEmail)) {
                        Swal.showValidationMessage('<?= __("Email không đúng định dạng"); ?>');
                        return false;
                    }
                    return $.ajax({
                        url: '<?= base_url("ajaxs/admin/update.php"); ?>',
                        type: 'POST',
                        data: {
                            action: 'test_smtp',
                            smtp_host: smtpHost,
                            smtp_port: smtpPort,
                            smtp_encryption: smtpEncryption,
                            smtp_email: smtpEmail,
                            smtp_from_email: smtpFromEmail,
                            smtp_password: smtpPassword,
                            test_email: testEmail
                        },
                        dataType: 'json'
                    }).catch(function() {
                        Swal.showValidationMessage('<?= __("Lỗi kết nối máy chủ, vui lòng thử lại"); ?>');
                    });
                },
                allowOutsideClick: function() {
                    return !Swal.isLoading();
                }
            }).then(function(swalResult) {
                if (swalResult.isConfirmed && swalResult.value) {
                    var response = swalResult.value;
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<?= __("Thành công"); ?>',
                            text: response.msg,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?= __("Lỗi"); ?>',
                            text: response.msg,
                        });
                    }
                }
            });
        });

        // Xử lý nút Test Telegram
        $('#btn_test_telegram').click(function() {
            var telegramToken = $('input[name="telegram_token"]').val();
            var telegramChatId = $('input[name="telegram_chat_id"]').val();
            var telegramUrl = $('select[name="telegram_url"]').val();

            if (!telegramToken || !telegramChatId) {
                Swal.fire({
                    icon: 'warning',
                    title: '<?= __("Thiếu thông tin"); ?>',
                    text: '<?= __("Vui lòng điền Bot Token và Chat ID trước khi gửi test"); ?>',
                });
                return;
            }

            Swal.fire({
                title: '<?= __("Gửi tin nhắn test Telegram"); ?>',
                html: '<div class="text-start">' +
                    '<label class="form-label fw-medium"><?= __("Nội dung tin nhắn test"); ?></label>' +
                    '<textarea id="swal_test_telegram_msg" class="form-control" rows="3" placeholder="<?= __("Nhập nội dung..."); ?>">✅ Telegram Bot đang hoạt động tốt!\n🕐 <?= __("Thời gian"); ?>: ' + new Date().toLocaleString('vi-VN') + '</textarea>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> <?= __("Gửi test"); ?>',
                cancelButtonText: '<?= __("Hủy"); ?>',
                confirmButtonColor: '#3085d6',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    var testMsg = document.getElementById('swal_test_telegram_msg').value.trim();
                    if (!testMsg) {
                        Swal.showValidationMessage('<?= __("Vui lòng nhập nội dung tin nhắn"); ?>');
                        return false;
                    }
                    return $.ajax({
                        url: '<?= base_url("ajaxs/admin/update.php"); ?>',
                        type: 'POST',
                        data: {
                            action: 'test_telegram',
                            telegram_token: telegramToken,
                            telegram_chat_id: telegramChatId,
                            telegram_url: telegramUrl,
                            message: testMsg
                        },
                        dataType: 'json'
                    }).catch(function() {
                        Swal.showValidationMessage('<?= __("Lỗi kết nối máy chủ, vui lòng thử lại"); ?>');
                    });
                },
                allowOutsideClick: function() {
                    return !Swal.isLoading();
                }
            }).then(function(swalResult) {
                if (swalResult.isConfirmed && swalResult.value) {
                    var response = swalResult.value;
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<?= __("Thành công"); ?>',
                            text: response.msg,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?= __("Lỗi"); ?>',
                            text: response.msg,
                        });
                    }
                }
            });
        });

        // Hàm xử lý ẩn/hiện input proxy dựa trên telegram_url
        function toggleProxyInputs() {
            var selectedUrl = document.querySelector('select[name="telegram_url"]').value;
            var proxySection = document.getElementById('telegram_proxy_section');
            var proxyTypeSection = document.getElementById('telegram_proxy_type_section');

            if (selectedUrl === 'https://api.telegram.org/') {
                if (proxySection) proxySection.style.display = 'block';
                if (proxyTypeSection) proxyTypeSection.style.display = 'block';
            } else {
                if (proxySection) proxySection.style.display = 'none';
                if (proxyTypeSection) proxyTypeSection.style.display = 'none';
            }
        }

        // Thực hiện kiểm tra ban đầu
        toggleProxyInputs();

        // Theo dõi thay đổi của select telegram_url
        var telegramUrlSelect = document.querySelector('select[name="telegram_url"]');
        if (telegramUrlSelect) {
            telegramUrlSelect.addEventListener('change', toggleProxyInputs);
        }
    });

    // Hàm lấy proxy free từ API CMSNT
    function getFreeProxy() {
        var btn = $('#btnGetFreeProxy');
        var btnText = $('#btnText');
        var btnLoading = $('#btnLoading');
        var proxyInput = $('#telegram_proxy_input');
        var proxyTypeSelect = $('#telegram_proxy_type_select');

        // Hiển thị loading
        btn.prop('disabled', true);
        btnText.addClass('d-none');
        btnLoading.removeClass('d-none');

        // Gọi API
        $.ajax({
            url: 'https://api.cmsnt.co/free-proxy-socks5.php',
            type: 'GET',
            dataType: 'JSON',
            success: function(data) {
                try {
                    if (!Array.isArray(data) || data.length === 0) {
                        throw new Error('Không có proxy nào khả dụng');
                    }

                    // Lọc proxy có ping thấp (< 2000ms) để có chất lượng tốt hơn
                    var goodProxies = data.filter(function(proxy) {
                        return proxy.ping < 2000;
                    });
                    var proxyList = goodProxies.length > 0 ? goodProxies : data;

                    // Chọn random 1 proxy
                    var randomIndex = Math.floor(Math.random() * proxyList.length);
                    var selectedProxy = proxyList[randomIndex];

                    // Điền vào input với format ip:port
                    var proxyString = selectedProxy.ip + ':' + selectedProxy.port;
                    proxyInput.val(proxyString);

                    // Tự động chọn SOCKS5
                    proxyTypeSelect.val('SOCKS5');

                    // Hiển thị thông báo thành công
                    showProxyNotification('success',
                        '<strong>Lấy proxy thành công!</strong><br>' +
                        '<small>' +
                        '<i class="fas fa-server me-1"></i>' + selectedProxy.ip + ':' + selectedProxy.port + ' ' +
                        '<i class="fas fa-flag ms-2 me-1"></i>' + selectedProxy.country + ' ' +
                        '<i class="fas fa-signal ms-2 me-1"></i>' + selectedProxy.ping + 'ms' +
                        '</small>'
                    );

                } catch (error) {
                    showProxyNotification('error', 'Có lỗi xảy ra khi lấy proxy. Vui lòng thử lại!');
                }
            },
            error: function(xhr, status, error) {
                showProxyNotification('error', 'Không thể kết nối đến API. Vui lòng kiểm tra kết nối mạng và thử lại!');
            },
            complete: function() {
                // Khôi phục trạng thái nút
                btn.prop('disabled', false);
                btnText.removeClass('d-none');
                btnLoading.addClass('d-none');
            }
        });
    }

    // Hàm hiển thị thông báo proxy
    function showProxyNotification(type, message) {
        // Xóa thông báo cũ nếu có
        var oldAlert = document.querySelector('.proxy-alert');
        if (oldAlert) {
            oldAlert.remove();
        }

        // Tạo thông báo mới
        var alertClass, iconClass;
        switch (type) {
            case 'success':
                alertClass = 'alert-success';
                iconClass = 'fa-check-circle';
                break;
            case 'warning':
                alertClass = 'alert-warning';
                iconClass = 'fa-exclamation-triangle';
                break;
            case 'error':
            default:
                alertClass = 'alert-danger';
                iconClass = 'fa-times-circle';
                break;
        }

        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert ' + alertClass + ' alert-dismissible fade show proxy-alert mt-2 mb-0';
        alertDiv.innerHTML =
            '<i class="fas ' + iconClass + ' me-2"></i>' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

        // Thêm vào sau input group
        var inputGroup = document.querySelector('#telegram_proxy_input').closest('.input-group');
        inputGroup.parentNode.insertBefore(alertDiv, inputGroup.nextSibling.nextSibling);

        // Tự động ẩn sau 8 giây
        setTimeout(function() {
            if (alertDiv && alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 8000);
    }
</script>