<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/social-buff.php';

$getUser = client_optional_user($CMSNT);
$isAuthenticated = is_array($getUser);
$isConfigured = social_buff_is_configured();
$socialBuffMaintenance = social_buff_maintenance_enabled();
$socialBuffAdmin = social_buff_is_admin_user($getUser);
$socialBuffAvailable = $isConfigured && social_buff_can_place_order($getUser);
$socialBuffUnavailableMessage = $socialBuffMaintenance && !$socialBuffAdmin
    ? 'Dịch vụ đang bảo trì. Vui lòng quay lại sau.'
    : 'Dịch vụ đang được cập nhật.';

$body = [
    'title' => 'Buff mạng xã hội | ' . $CMSNT->site('title'),
    'desc' => 'Đặt dịch vụ video, view, like, follow và tương tác mạng xã hội.',
    'keyword' => 'buff mạng xã hội, tăng view, tăng like, tăng follow, video',
    'legacy_client_plugins' => false
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/social-buff.css?v=1') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/social-buff.js?v=1') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main
    class="social-buff-page"
    data-social-buff
    data-endpoint="<?= htmlspecialchars(BASE_URL('ajaxs/client/social-buff.php'), ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $isAuthenticated ? '1' : '0'; ?>"
    data-configured="<?= $socialBuffAvailable ? '1' : '0'; ?>"
    data-unavailable-message="<?= htmlspecialchars($socialBuffUnavailableMessage, ENT_QUOTES, 'UTF-8'); ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
>
    <section class="social-buff-intro" aria-labelledby="social-buff-title">
        <div class="social-buff-intro-copy">
            <span class="social-buff-eyebrow"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Dịch vụ tự động</span>
            <h1 id="social-buff-title">Buff mạng xã hội</h1>
            <p>Chọn dịch vụ, dán liên kết và theo dõi trạng thái đơn ngay tại Caffemmo.</p>
        </div>
        <div class="social-buff-intro-status" role="status">
            <span class="social-buff-status-dot <?= $socialBuffAvailable ? 'is-ready' : ''; ?>" aria-hidden="true"></span>
            <span><?= $socialBuffMaintenance && !$socialBuffAdmin ? 'Đang bảo trì' : ($isConfigured ? 'Sẵn sàng nhận đơn' : 'Đang cập nhật dịch vụ'); ?></span>
        </div>
    </section>

    <?php if (!$isAuthenticated): ?>
        <section class="social-buff-login-notice" role="status">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <div><strong>Đăng nhập để đặt dịch vụ</strong><span>Danh sách dịch vụ và lịch sử đơn chỉ hiển thị cho tài khoản thành viên.</span></div>
            <a href="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>">Đăng nhập <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>
    <?php elseif ($socialBuffMaintenance && !$socialBuffAdmin): ?>
        <section class="social-buff-login-notice social-buff-login-notice--warning" role="status">
            <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
            <div><strong>Dịch vụ đang bảo trì</strong><span>Vui lòng quay lại sau khi việc cập nhật hoàn tất.</span></div>
        </section>
    <?php elseif (!$isConfigured): ?>
        <section class="social-buff-login-notice social-buff-login-notice--warning" role="status">
            <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
            <div><strong>Dịch vụ đang được cập nhật</strong><span>Vui lòng quay lại sau khi quản trị viên hoàn tất cập nhật dịch vụ.</span></div>
        </section>
    <?php elseif ($socialBuffMaintenance && $socialBuffAdmin): ?>
        <section class="social-buff-login-notice social-buff-login-notice--warning" role="status">
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            <div><strong>Chế độ bảo trì đang bật</strong><span>Khách hàng tạm thời không thể tạo đơn; tài khoản quản trị vẫn sử dụng bình thường.</span></div>
        </section>
    <?php endif; ?>

    <section class="social-buff-workspace" aria-label="Đặt dịch vụ buff mạng xã hội">
        <section class="social-buff-catalog" aria-labelledby="social-buff-catalog-title">
            <div class="social-buff-catalog-head">
                <div>
                    <span class="social-buff-kicker">Bước 1</span>
                    <h2 id="social-buff-catalog-title">Chọn dịch vụ</h2>
                </div>
                <label class="social-buff-search" for="social-buff-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="social-buff-search" type="search" placeholder="Tìm video, view, like, follow..." autocomplete="off" data-social-buff-search <?= !$isAuthenticated || !$socialBuffAvailable ? 'disabled' : ''; ?>>
                </label>
            </div>

            <div class="social-buff-filters" data-social-buff-filters aria-label="Lọc dịch vụ">
                <button type="button" class="is-active" data-social-filter="all">Tất cả</button>
                <button type="button" data-social-filter="video"><i class="fa-solid fa-play" aria-hidden="true"></i> Video &amp; lượt xem</button>
                <button type="button" data-social-filter="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i> Facebook</button>
                <button type="button" data-social-filter="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i> Instagram</button>
                <button type="button" data-social-filter="TikTok"><i class="fa-brands fa-tiktok" aria-hidden="true"></i> TikTok</button>
                <button type="button" data-social-filter="YouTube"><i class="fa-brands fa-youtube" aria-hidden="true"></i> YouTube</button>
                <button type="button" data-social-filter="Shopee">Shopee</button>
                <button type="button" data-social-filter="X / Twitter">X / Twitter</button>
            </div>

            <div class="social-buff-feedback" data-social-buff-feedback role="status" aria-live="polite" hidden></div>
            <div class="social-buff-service-grid" data-social-buff-services aria-live="polite">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="social-buff-service-skeleton" aria-hidden="true"><span></span><strong></strong><small></small><em></em></div>
                <?php endfor; ?>
            </div>
        </section>

        <aside class="social-buff-order" aria-labelledby="social-buff-order-title">
            <div class="social-buff-order-head">
                <div><span class="social-buff-kicker">Bước 2</span><h2 id="social-buff-order-title">Tạo đơn</h2></div>
                <?php if ($isAuthenticated): ?><strong class="social-buff-balance">Số dư: <?= format_currency($getUser['money']); ?></strong><?php endif; ?>
            </div>

            <div class="social-buff-selected" data-social-buff-selected>
                <span class="social-buff-selected-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
                <div><strong>Chưa chọn dịch vụ</strong><small>Chọn một dịch vụ ở danh sách bên trái.</small></div>
            </div>

            <form data-social-buff-form autocomplete="off">
                <input type="hidden" name="service_id" data-social-buff-service-id>
                <label class="social-buff-field" for="social-buff-link">
                    <span>Liên kết bài viết, video hoặc tài khoản</span>
                    <div class="social-buff-input-wrap"><i class="fa-solid fa-link" aria-hidden="true"></i><input id="social-buff-link" name="target_url" type="url" inputmode="url" placeholder="https://..." required <?= !$isAuthenticated || !$socialBuffAvailable ? 'disabled' : ''; ?>></div>
                </label>
                <label class="social-buff-field" for="social-buff-quantity">
                    <span>Số lượng <small data-social-buff-range>Chọn dịch vụ để xem giới hạn.</small></span>
                    <div class="social-buff-input-wrap"><i class="fa-solid fa-arrow-up-9-1" aria-hidden="true"></i><input id="social-buff-quantity" name="quantity" type="number" min="1" step="1" inputmode="numeric" placeholder="0" required disabled></div>
                </label>
                <div class="social-buff-summary" aria-live="polite">
                    <span>Chi phí tạm tính</span><strong data-social-buff-total>0d</strong><small data-social-buff-unit>Giá được tính theo 1.000 lượt.</small>
                </div>
                <button class="social-buff-submit" type="submit" data-social-buff-submit disabled>
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Đặt dịch vụ</span>
                </button>
            </form>
            <p class="social-buff-security"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Thông tin đơn được bảo vệ trong quá trình xử lý.</p>
        </aside>
    </section>

    <section class="social-buff-history" aria-labelledby="social-buff-history-title">
        <div class="social-buff-history-head">
            <div><span class="social-buff-kicker">Theo dõi</span><h2 id="social-buff-history-title">Đơn dịch vụ gần đây</h2></div>
            <button type="button" class="social-buff-history-refresh" data-social-buff-history-refresh <?= !$isAuthenticated ? 'disabled' : ''; ?>><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><span>Làm mới</span></button>
        </div>
        <div class="social-buff-history-list" data-social-buff-history aria-live="polite">
            <div class="social-buff-history-empty"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><?= $isAuthenticated ? 'Đang tải lịch sử đơn...' : 'Đăng nhập để xem lịch sử dịch vụ.'; ?></span></div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
