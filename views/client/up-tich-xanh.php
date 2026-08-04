<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/uptichxanh.php';
require_once __DIR__ . '/../../libs/service-catalog.php';

$getUser = client_optional_user($CMSNT);
$upIsAuthenticated = is_array($getUser);
$upUserToken = $upIsAuthenticated ? (string) ($getUser['token'] ?? '') : '';
$upWalletBalance = $upIsAuthenticated ? format_currency($getUser['money']) : __('Đăng nhập để sử dụng');
$upWalletUrl = $upIsAuthenticated ? base_url('recharge-bank') : base_url('client/login');
$upWalletAction = $upIsAuthenticated ? __('Nạp tiền') : __('Đăng nhập');

$serviceCatalog = caffemmo_service_catalog();
$services = [];
foreach ($serviceCatalog['up-tich-xanh']['items'] as $catalogItem) {
    $services[$catalogItem['key']] = $catalogItem;
}

$service = isset($_GET['service']) && isset($services[$_GET['service']]) ? $_GET['service'] : 'get-link';
$currentService = $services[$service];
$upConfigured = uptichxanh_is_configured();
$servicePrice = uptichxanh_service_price($service);

$body = [
    'title' => __('Up tích xanh') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Dịch vụ Get Link Facebook, Up tích Facebook và Up tích Instagram.'),
    'keyword' => 'get link facebook, up tích xanh, up tích facebook, up tích instagram'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/up-tich-xanh.css?v=16') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/up-tich-xanh.js?v=5') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main
    class="up-page"
    data-up-app
    data-endpoint="<?= htmlspecialchars(BASE_URL('ajaxs/client/up-tich-xanh.php'), ENT_QUOTES, 'UTF-8'); ?>"
    data-token="<?= htmlspecialchars($upUserToken, ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $upIsAuthenticated ? '1' : '0'; ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
    data-service="<?= htmlspecialchars($service, ENT_QUOTES, 'UTF-8'); ?>"
    data-configured="<?= $upConfigured ? '1' : '0'; ?>"
    data-history-url="<?= htmlspecialchars(base_url('client/up-tich-xanh-history'), ENT_QUOTES, 'UTF-8'); ?>"
>
    <section class="up-hero" aria-labelledby="up-page-title">
        <div class="up-hero-copy">
            <span class="up-eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Caffemmo Social</span>
            <h1 id="up-page-title"><?= __('Up tích xanh'); ?></h1>
            <p><?= __('Chọn dịch vụ phù hợp và chuẩn bị thông tin xác minh theo từng bước rõ ràng.'); ?></p>
        </div>
        <div class="up-wallet-card">
            <span><?= __('Số dư ví'); ?></span>
            <strong><?= $upWalletBalance; ?></strong>
            <a href="<?= htmlspecialchars($upWalletUrl, ENT_QUOTES, 'UTF-8'); ?>"><?= $upWalletAction; ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
    </section>

    <section class="up-service-tabs" aria-label="<?= __('Chọn dịch vụ nhanh'); ?>">
        <?php foreach ($services as $key => $item): ?>
            <a class="up-service-tab up-service-tab--<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8'); ?> <?= $service === $key ? 'is-active' : ''; ?>" href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" <?= $service === $key ? 'aria-current="page"' : ''; ?>>
                <span class="up-service-tab-icon"><i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                <span><strong><?= __($item['label']); ?></strong><small><?= __($item['short']); ?></small></span>
                <i class="fa-solid fa-arrow-up-right-from-square up-service-tab-arrow" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="up-history-link-row">
        <span><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <?= __('Các yêu cầu đã gửi được lưu trong lịch sử riêng của bạn.'); ?></span>
        <a href="<?= htmlspecialchars(base_url('client/up-tich-xanh-history'), ENT_QUOTES, 'UTF-8'); ?>"><?= __('Lịch sử yêu cầu'); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>

    <div class="up-content-grid">
        <section class="up-panel" aria-labelledby="up-service-title">
            <div class="up-panel-heading">
                <div class="up-heading-main">
                    <span class="up-step">01</span>
                    <div class="up-heading-copy">
                        <span class="up-panel-kicker"><?= __('Dịch vụ đang chọn'); ?></span>
                        <div class="up-heading-title-row"><h2 id="up-service-title"><?= __($currentService['label']); ?></h2></div>
                        <p><?= __($currentService['description']); ?></p>
                    </div>
                </div>
                <span class="up-service-state <?= $upConfigured ? 'up-service-state--ready' : 'up-service-state--soon'; ?>" role="status"><i aria-hidden="true"></i> <?= $upConfigured ? __('Sẵn sàng') : __('Tạm đóng'); ?></span>
            </div>

            <div class="up-notice up-notice--info" data-up-notice role="status" aria-live="polite">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <div>
                    <strong><?= $upConfigured ? __('Thông tin được gửi bảo mật') : __('Dịch vụ đang được chuẩn bị'); ?></strong>
                    <span><?= $upConfigured ? __('Cookie và ảnh chỉ dùng để xử lý yêu cầu, không hiển thị công khai.') : __('Vui lòng quay lại sau khi quản trị viên hoàn tất cấu hình dịch vụ.'); ?></span>
                </div>
            </div>

            <?php if (!$upConfigured): ?>
                <div class="up-form-lock" id="up-form-state" role="status">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <div><strong><?= __('Chưa mở tiếp nhận'); ?></strong><span><?= __('Dịch vụ sẽ được mở ngay khi hoàn tất cấu hình.'); ?></span></div>
                </div>
            <?php endif; ?>

            <form class="up-form <?= !$upConfigured ? 'up-form--locked' : ''; ?>" data-up-form enctype="multipart/form-data" autocomplete="off"<?= !$upConfigured ? ' aria-describedby="up-form-state"' : ''; ?>>
                <?php if ($service === 'get-link'): ?>
                    <label class="up-field" for="up_cookie">
                        <span><?= __('Cookie Facebook'); ?></span>
                        <textarea id="up_cookie" name="cookie" rows="5" placeholder="<?= __('Dán cookie Facebook vào đây...'); ?>"<?= !$upConfigured ? ' disabled' : ''; ?> required></textarea>
                        <small><?= __('Chi phí mỗi lượt:'); ?> <?= format_currency($servicePrice); ?></small>
                    </label>
                    <button class="up-submit-button" data-up-submit type="submit"<?= !$upConfigured ? ' disabled' : ''; ?>><i class="fa-solid fa-link" aria-hidden="true"></i> <?= __('Lấy link xác minh'); ?></button>
                <?php else: ?>
                    <label class="up-field" for="up_cookie">
                        <span><?= $service === 'up-fb' ? __('Cookie Facebook') : __('Cookie Instagram'); ?></span>
                        <textarea id="up_cookie" name="cookie" rows="5" placeholder="<?= $service === 'up-fb' ? __('Dán cookie Facebook vào đây...') : __('Dán cookie Instagram vào đây...'); ?>"<?= !$upConfigured ? ' disabled' : ''; ?> required></textarea>
                    </label>
                    <div class="up-upload-field">
                        <span><?= __('Ảnh giấy tờ xác minh'); ?></span>
                        <label class="up-upload-box<?= $upConfigured ? ' is-enabled' : ''; ?>" for="up_image">
                            <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                            <strong data-up-image-title><?= __('Chọn ảnh giấy tờ'); ?></strong>
                            <small>PNG, JPG, WEBP · tối thiểu 1500×1000px · <?= __('tối đa 10MB'); ?></small>
                            <small class="up-upload-status" data-up-image-meta data-state="empty" aria-live="polite">Chưa chọn ảnh</small>
                            <input id="up_image" type="file" name="image" accept="image/png,image/jpeg,image/webp"<?= !$upConfigured ? ' disabled' : ''; ?> required>
                        </label>
                        <div class="up-image-preview" data-up-image-preview hidden>
                            <img data-up-image-preview-image alt="Ảnh giấy tờ đã chọn">
                            <div class="up-image-preview-copy">
                                <strong data-up-image-preview-name></strong>
                                <small data-up-image-preview-info></small>
                            </div>
                            <button type="button" class="up-image-preview-remove" data-up-image-remove aria-label="Xóa ảnh đã chọn"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Xóa</button>
                        </div>
                    </div>
                    <div class="up-price-row"><span><?= __('Chi phí mỗi lượt'); ?></span><strong><?= format_currency($servicePrice); ?></strong></div>
                    <button class="up-submit-button" data-up-submit type="submit"<?= !$upConfigured ? ' disabled' : ''; ?>><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?= __('Gửi yêu cầu xác minh'); ?></button>
                <?php endif; ?>
            </form>

            <div class="up-result" data-up-result hidden aria-live="polite"></div>
        </section>

        <aside class="up-panel up-side-panel" aria-labelledby="up-guide-title">
            <div class="up-side-heading">
                <span class="up-step">02</span>
                <div>
                    <span class="up-panel-kicker"><?= __('Hướng dẫn'); ?></span>
                    <h2 id="up-guide-title"><?= __('Chuẩn bị trước khi gửi'); ?></h2>
                    <p><?= __('Giúp yêu cầu được xử lý thuận lợi hơn.'); ?></p>
                </div>
            </div>
            <ul class="up-check-list">
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Tài khoản phải đã đăng ký và thanh toán gói Meta Verified.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Ảnh giấy tờ rõ nét, đủ thông tin và không bị cắt góc.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Không chia sẻ cookie cho bên khác ngoài biểu mẫu chính thức.'); ?></span></li>
            </ul>
            <div class="up-safety-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= __('Thông tin của bạn được bảo vệ trong suốt quá trình tiếp nhận và xử lý.'); ?></span></div>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
