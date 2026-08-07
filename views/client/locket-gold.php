<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/locket-gold.php';

$getUser = client_optional_user($CMSNT);
$locketGoldPackages = locket_gold_packages();
$locketGoldEnabled = locket_gold_enabled();
$locketGoldWarrantyDays = locket_gold_warranty_days();
$locketGoldPriceMap = [];
foreach ($locketGoldPackages as $package) {
    $locketGoldPriceMap[$package['key']] = [
        'label' => $package['label'],
        'max_accounts' => (int) $package['max_accounts'],
        'price' => (float) $package['price']
    ];
}
$clientResourceService = 'locket-gold';

$body = [
    'title' => __('Locket Gold Vĩnh Viễn') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Nâng cấp tài khoản Locket Gold Vĩnh Viễn nhanh chóng từ Caffemmo.'),
    'keyword' => 'locket gold vĩnh viễn, nâng cấp locket'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/locket-gold.css?v=2') . '"><link rel="stylesheet" href="' . BASE_URL('mod/css/client-resources.css?v=1') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/client-resources.js?v=1') . '"></script><script src="' . BASE_URL('mod/js/locket-gold.js?v=1') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main
    class="locket-page"
    data-locket-app
    data-endpoint="<?= htmlspecialchars(BASE_URL('ajaxs/client/locket-gold.php'), ENT_QUOTES, 'UTF-8'); ?>"
    data-token="<?= htmlspecialchars($getUser ? (string) ($getUser['token'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $getUser ? '1' : '0'; ?>"
    data-enabled="<?= $locketGoldEnabled ? '1' : '0'; ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
    data-packages="<?= htmlspecialchars(json_encode($locketGoldPriceMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
>
    <section class="locket-hero" aria-labelledby="locket-page-title">
        <div>
            <span class="locket-eyebrow"><i class="fa-solid fa-crown" aria-hidden="true"></i> Caffemmo Service</span>
            <h1 id="locket-page-title"><?= __('Locket Gold Vĩnh Viễn'); ?></h1>
            <p><?= __('Chọn gói phù hợp, nhập username và theo dõi đơn ngay trên Caffemmo.'); ?></p>
        </div>
        <span class="locket-status <?= $locketGoldEnabled ? 'is-ready' : 'is-closed'; ?>" role="status"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= $locketGoldEnabled ? __('Đang nhận đơn') : __('Tạm đóng'); ?></span>
    </section>

    <?php require __DIR__ . '/client-resources.php'; ?>

    <div class="locket-content-grid">
        <section class="locket-panel" aria-labelledby="locket-package-title">
            <div class="locket-panel-heading">
                <span class="locket-step">01</span>
                <div>
                    <span class="locket-panel-kicker"><?= __('Chọn gói'); ?></span>
                    <h2 id="locket-package-title"><?= __('Gói Locket Gold Vĩnh Viễn'); ?></h2>
                    <p><?= __('Mỗi gói hỗ trợ số lượng tài khoản khác nhau.'); ?></p>
                </div>
            </div>

            <div class="locket-package-grid" role="radiogroup" aria-label="<?= __('Chọn gói Locket Gold Vĩnh Viễn'); ?>">
                <?php foreach ($locketGoldPackages as $package): ?>
                <button class="locket-package-card<?= $package['key'] === 'vip-1' ? ' is-selected' : ''; ?>" data-locket-package="<?= htmlspecialchars($package['key'], ENT_QUOTES, 'UTF-8'); ?>" type="button" role="radio" aria-checked="<?= $package['key'] === 'vip-1' ? 'true' : 'false'; ?>">
                    <span class="locket-package-card-top"><strong><?= htmlspecialchars($package['label'], ENT_QUOTES, 'UTF-8'); ?></strong><i class="fa-solid fa-crown" aria-hidden="true"></i></span>
                    <span class="locket-package-price"><?= format_currency($package['price']); ?></span>
                    <span class="locket-package-limit"><?= __('Tối đa'); ?> <?= (int) $package['max_accounts']; ?> <?= __('tài khoản'); ?></span>
                    <span class="locket-package-features">
                        <?php foreach ($package['features'] as $feature): ?>
                        <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __($feature); ?></span>
                        <?php endforeach; ?>
                        <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><?= __('Bảo hành'); ?> <?= (int) $locketGoldWarrantyDays; ?> <?= __('ngày'); ?></span>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="locket-panel locket-order-panel" aria-labelledby="locket-order-title">
            <div class="locket-panel-heading">
                <span class="locket-step">02</span>
                <div>
                    <span class="locket-panel-kicker"><?= __('Gửi yêu cầu'); ?></span>
                    <h2 id="locket-order-title"><?= __('Kích hoạt nhanh'); ?></h2>
                    <p><?= __('Chỉ cần username Locket, không cần mật khẩu hoặc cookie.'); ?></p>
                </div>
            </div>

            <div class="locket-selected-summary" data-locket-summary>
                <span><i class="fa-solid fa-crown" aria-hidden="true"></i><strong data-locket-selected-label>VIP 1</strong></span>
                <strong data-locket-selected-price><?= format_currency($locketGoldPackages[0]['price']); ?></strong>
            </div>

            <label class="locket-field-label" for="locket_usernames"><?= __('Username Locket'); ?></label>
            <textarea id="locket_usernames" class="locket-usernames-input" data-locket-usernames rows="4" maxlength="800" placeholder="<?= __('Ví dụ: username_locket'); ?>"<?= !$getUser || !$locketGoldEnabled ? ' disabled' : ''; ?>></textarea>
            <div class="locket-field-help"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span data-locket-limit-help><?= __('Mỗi dòng một username, tối đa 1 tài khoản với gói đang chọn.'); ?></span></div>

            <div class="locket-safe-note" role="note">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span><?= __('Caffemmo chỉ tiếp nhận username để xử lý đơn. Không nhập mật khẩu, mã OTP hoặc thông tin iCloud.'); ?></span>
            </div>

            <div class="locket-wallet-row">
                <span><i class="fa-solid fa-wallet" aria-hidden="true"></i><?= __('Số dư hiện tại'); ?></span>
                <strong><?= $getUser ? format_currency($getUser['money']) : __('Đăng nhập để xem'); ?></strong>
            </div>

            <button class="locket-submit-button" data-locket-submit type="button"<?= !$getUser || !$locketGoldEnabled ? ' disabled' : ''; ?>><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?= __('Gửi yêu cầu'); ?></button>
            <a class="locket-history-link" href="<?= htmlspecialchars(base_url('client/locket-gold-history'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <?= __('Xem lịch sử đơn'); ?></a>
            <a class="locket-login-link" data-locket-login href="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"<?= $getUser ? ' hidden' : ''; ?>><?= __('Đăng nhập để sử dụng'); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>

            <div class="locket-result" data-locket-result hidden aria-live="polite">
                <strong data-locket-result-title></strong>
                <span data-locket-result-meta></span>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
