<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/netflix.php';

$getUser = client_optional_user($CMSNT);
$netflixAuthenticated = is_array($getUser);
$netflixPrice = netflix_service_price();
$body = [
    'title' => __('Xem Netflix') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Lấy link xem Netflix nhanh chóng.'),
    'keyword' => 'xem netflix, netflix'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/netflix.css?v=4') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/netflix.js?v=2') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main
    class="netflix-page"
    data-netflix-app
    data-endpoint="<?= htmlspecialchars(BASE_URL('ajaxs/client/netflix.php'), ENT_QUOTES, 'UTF-8'); ?>"
    data-token="<?= htmlspecialchars($netflixAuthenticated ? (string) ($getUser['token'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $netflixAuthenticated ? '1' : '0'; ?>"
    data-configured="<?= netflix_api_is_configured() ? '1' : '0'; ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
>
    <section class="netflix-hero" aria-labelledby="netflix-page-title">
        <div>
            <span class="netflix-eyebrow"><i class="fa-solid fa-play" aria-hidden="true"></i> Caffemmo Streaming</span>
            <h1 id="netflix-page-title"><?= __('Xem Netflix'); ?></h1>
            <p><?= __('Tạo link xem Netflix trên máy tính và điện thoại từ hệ thống Caffemmo.'); ?></p>
        </div>
        <span class="netflix-status <?= netflix_api_is_configured() ? 'netflix-status--ready' : 'netflix-status--pending'; ?>" role="status">
            <i aria-hidden="true"></i> <?= netflix_api_is_configured() ? __('Sẵn sàng') : __('Đang cấu hình'); ?>
        </span>
    </section>

    <div class="netflix-content-grid">
        <section class="netflix-panel" aria-labelledby="netflix-service-title">
            <div class="netflix-panel-heading">
                <span class="netflix-step">01</span>
                <div>
                    <span class="netflix-panel-kicker"><?= __('Dịch vụ đang chọn'); ?></span>
                    <h2 id="netflix-service-title"><?= __('Link xem Netflix'); ?></h2>
                    <p><?= __('Link được tạo trực tiếp từ nhà cung cấp và có thời hạn sử dụng.'); ?></p>
                </div>
            </div>

            <ul class="netflix-feature-list" aria-label="<?= __('Quyền lợi dịch vụ Netflix'); ?>">
                <li><i class="fa-solid fa-display" aria-hidden="true"></i><span><strong><?= __('PC và Mobile'); ?></strong><small><?= __('Xem trên máy tính và điện thoại'); ?></small></span></li>
                <li><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><span><strong><?= __('Bảo hành 30 ngày'); ?></strong><small><?= __('Hỗ trợ tạo lại link trong thời hạn'); ?></small></span></li>
                <li><i class="fa-solid fa-rotate" aria-hidden="true"></i><span><strong><?= __('Tạo lại miễn phí'); ?></strong><small><?= __('Không tính tiền khi link hết hạn'); ?></small></span></li>
            </ul>

            <div class="netflix-notice" role="note">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span><?= __('Hệ thống chỉ hiển thị link xem, không lưu hoặc hiển thị cookie Netflix.'); ?></span>
            </div>

            <div class="netflix-price-row">
                <span><i class="fa-solid fa-tags" aria-hidden="true"></i><?= __('Giá mỗi lần lấy link mới'); ?></span>
                <strong><?= $netflixPrice > 0 ? format_currency($netflixPrice) : __('Chưa thiết lập'); ?></strong>
            </div>
            <div class="netflix-charge-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><?= __('Chỉ tính tiền khi lấy link mới. Tạo lại link khi hết hạn miễn phí trong 30 ngày.'); ?></div>

            <button class="netflix-submit-button" data-netflix-submit type="button"<?= !$netflixAuthenticated || !netflix_api_is_configured() ? ' disabled' : ''; ?>>
                <i class="fa-solid fa-link" aria-hidden="true"></i> <?= __('Lấy link xem Netflix'); ?>
            </button>
            <a class="netflix-history-link" href="<?= htmlspecialchars(base_url('client/netflix-history'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <?= __('Xem lịch sử và tạo lại link miễn phí'); ?></a>
            <a class="netflix-login-link" data-netflix-login href="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"<?= $netflixAuthenticated ? ' hidden' : ''; ?>><?= __('Đăng nhập để sử dụng'); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>

            <div class="netflix-result" data-netflix-result hidden aria-live="polite">
                <strong data-netflix-result-title></strong>
                <span data-netflix-result-meta></span>
                <div class="netflix-result-actions">
                    <a data-netflix-pc target="_blank" rel="noopener noreferrer" hidden><i class="fa-solid fa-desktop" aria-hidden="true"></i> <?= __('Mở trên máy tính'); ?></a>
                    <a data-netflix-mobile target="_blank" rel="noopener noreferrer" hidden><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> <?= __('Mở trên điện thoại'); ?></a>
                    <button class="netflix-refresh-button" data-netflix-refresh type="button" hidden><i class="fa-solid fa-rotate" aria-hidden="true"></i> <?= __('Tạo lại link miễn phí'); ?></button>
                </div>
            </div>
        </section>

        <aside class="netflix-panel netflix-side-panel" aria-labelledby="netflix-guide-title">
            <div class="netflix-panel-heading">
                <span class="netflix-step">02</span>
                <div>
                    <span class="netflix-panel-kicker"><?= __('Lưu ý'); ?></span>
                    <h2 id="netflix-guide-title"><?= __('Sử dụng link an toàn'); ?></h2>
                    <p><?= __('Link có thể hết hạn theo thời gian trả về từ API.'); ?></p>
                </div>
            </div>
            <ul class="netflix-check-list">
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Không chia sẻ link đăng nhập cho người khác.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Mở link ngay sau khi tạo để có trải nghiệm ổn định.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Nếu link hết hạn, vào lịch sử để tạo lại miễn phí trong 30 ngày bảo hành.'); ?></span></li>
            </ul>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
