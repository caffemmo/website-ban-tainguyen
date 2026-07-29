<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once __DIR__ . '/../../models/is_user.php';
require_once __DIR__ . '/../../libs/youproxy.php';

$body = [
    'title' => __('Mua Proxy') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Mua proxy chính hãng với cấu hình rõ ràng và quản lý tập trung.'),
    'keyword' => 'proxy, mua proxy, proxy premium'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/proxy.css?v=9') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/proxy.js?v=6') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="proxy-page" data-proxy-app data-proxy-page="buy"
    data-endpoint="<?= BASE_URL('ajaxs/client/proxy.php'); ?>"
    data-token="<?= htmlspecialchars($getUser['token'], ENT_QUOTES, 'UTF-8'); ?>"
    data-configured="<?= youproxy_is_configured() ? '1' : '0'; ?>">
    <section class="proxy-page-heading">
        <div>
            <span class="proxy-eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Proxy workspace</span>
            <h1><?= __('Mua Proxy'); ?></h1>
            <p><?= __('Chọn cấu hình phù hợp, xem giá trước khi thanh toán và nhận thông tin proxy ngay sau khi tạo đơn.'); ?></p>
        </div>
        <div class="proxy-wallet-card">
            <span><?= __('Số dư ví'); ?></span>
            <strong data-wallet-balance><?= format_currency($getUser['money']); ?></strong>
            <a href="<?= base_url('recharge-bank'); ?>"><?= __('Nạp tiền'); ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
    </section>

    <div class="proxy-status" data-proxy-status role="status" aria-live="polite" hidden></div>

    <?php if (!youproxy_is_configured()): ?>
        <section class="proxy-setup-banner">
            <div class="proxy-setup-icon"><i class="fa-solid fa-plug-circle-xmark" aria-hidden="true"></i></div>
            <div>
                <strong><?= __('Dịch vụ proxy đang chờ cấu hình'); ?></strong>
                <p><?= __('Dịch vụ đang được chuẩn bị. Vui lòng thử lại sau ít phút hoặc liên hệ hỗ trợ.'); ?></p>
            </div>
        </section>
    <?php endif; ?>

    <section class="proxy-buy-layout">
        <form class="proxy-panel proxy-buy-form" data-buy-form>
            <div class="proxy-panel-heading">
                <div>
                    <span class="proxy-step">01</span>
                    <div>
                        <h2><?= __('Cấu hình proxy'); ?></h2>
                        <p><?= __('Tùy chỉnh theo nhu cầu sử dụng của bạn.'); ?></p>
                    </div>
                </div>
                <span class="proxy-live-dot"><i aria-hidden="true"></i> <?= __('Live pricing'); ?></span>
            </div>

            <fieldset class="proxy-fieldset">
                <legend><?= __('Loại proxy'); ?></legend>
                <div class="proxy-type-grid" data-proxy-types>
                    <button type="button" class="proxy-type-card is-selected" data-proxy-type="IPV4">
                        <span class="proxy-type-icon"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
                        <span><strong>IPv4</strong><small><?= __('Phổ biến, dễ dùng'); ?></small></span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card" data-proxy-type="IPV6">
                        <span class="proxy-type-icon"><i class="fa-solid fa-network-wired" aria-hidden="true"></i></span>
                        <span><strong>IPv6</strong><small><?= __('Tiết kiệm chi phí'); ?></small></span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card" data-proxy-type="ISP">
                        <span class="proxy-type-icon"><i class="fa-solid fa-building-shield" aria-hidden="true"></i></span>
                        <span><strong>ISP</strong><small><?= __('Ổn định cho tài khoản'); ?></small></span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card" data-proxy-type="MOBILE">
                        <span class="proxy-type-icon"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></span>
                        <span><strong>Mobile</strong><small><?= __('Có rotation linh hoạt'); ?></small></span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                </div>
                <input type="hidden" name="proxy_type" value="IPV4" data-proxy-type-input>
            </fieldset>

            <div class="proxy-form-grid">
                <label class="proxy-control">
                    <span><?= __('Quốc gia'); ?></span>
                    <div class="proxy-country-picker" data-country-options role="listbox" aria-label="Country selection">
                        <span class="proxy-country-empty" data-country-empty>Loading countries...</span>
                    </div>
                    <select class="proxy-country-select" name="country" data-country-select required disabled tabindex="-1" aria-hidden="true">
                        <option value="">Đang tải danh sách...</option>
                    </select>
                </label>
                <label class="proxy-control proxy-rent-control">
                    <span><?= __('Thời hạn'); ?></span>
                    <select name="rent_period_days" data-rent-select required disabled>
                        <option value="">Đang tải thời hạn...</option>
                    </select>
                </label>
                <label class="proxy-control proxy-mobile-field" data-mobile-field hidden>
                    <span><?= __('Nhà mạng'); ?></span>
                    <select name="mobile_operator" data-mobile-select disabled>
                        <option value="">Tùy chọn</option>
                    </select>
                </label>
                <label class="proxy-control proxy-mobile-field" data-mobile-field hidden>
                    <span><?= __('Rotation time'); ?></span>
                    <select name="rotation_time" data-rotation-select disabled>
                        <option value="">Mặc định</option>
                        <option value="5">5 phút</option>
                        <option value="15">15 phút</option>
                        <option value="30">30 phút</option>
                    </select>
                </label>
                <label class="proxy-control proxy-protocol-field" data-protocol-field hidden>
                    <span><?= __('Protocol IPv6'); ?></span>
                    <select name="protocol" data-protocol-select disabled>
                        <option value="HTTP">HTTP</option>
                        <option value="SOCKS">SOCKS</option>
                    </select>
                </label>
            </div>

            <div class="proxy-form-grid proxy-form-grid--details">
                <label class="proxy-control">
                    <span><?= __('Số lượng'); ?></span>
                    <span class="proxy-stepper">
                        <button type="button" data-quantity-minus aria-label="<?= __('Giảm số lượng'); ?>"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                        <input type="number" name="quantity" value="1" min="1" max="100" data-quantity-input aria-label="<?= __('Số lượng proxy'); ?>">
                        <button type="button" data-quantity-plus aria-label="<?= __('Tăng số lượng'); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                    </span>
                </label>
                <label class="proxy-control">
                    <span><?= __('Mục đích sử dụng'); ?></span>
                    <input type="text" name="goal" value="Marketing" maxlength="200" placeholder="Ví dụ: Quản lý quảng cáo" required>
                </label>
            </div>

            <div class="proxy-auth-row">
                <div>
                    <strong><?= __('Định dạng kết nối'); ?></strong>
                    <small><?= __('Login / Password: IP:PORT:USER:PASS · hỗ trợ cổng HTTPS và SOCKS5.'); ?></small>
                </div>
                <div class="proxy-segmented" role="radiogroup" aria-label="<?= __('Kiểu xác thực'); ?>">
                    <label><input type="radio" name="auth_type" value="LOGIN" checked><span>Login / Password</span></label>
                    <label><input type="radio" name="auth_type" value="IP"><span>IP whitelist</span></label>
                </div>
            </div>
            <label class="proxy-control proxy-ip-field" data-ip-field hidden>
                <span><?= __('IP xác thực'); ?></span>
                <input type="text" name="auth_ip" placeholder="203.0.113.10" inputmode="decimal">
            </label>

            <div class="proxy-bottom-row">
                <label class="proxy-check-row"><input type="checkbox" name="auto_extend" value="1"><span class="proxy-check-box"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span><strong><?= __('Tự động gia hạn'); ?></strong><small><?= __('Giữ proxy hoạt động liên tục'); ?></small></span></label>
                <label class="proxy-control proxy-promo-control"><span><?= __('Mã giảm giá'); ?></span><input type="text" name="promo_code" maxlength="60" placeholder="Không bắt buộc"></label>
            </div>
        </form>

        <aside class="proxy-panel proxy-order-summary" aria-label="<?= __('Tóm tắt đơn hàng'); ?>">
            <div class="proxy-summary-top">
                <span class="proxy-step">02</span>
                <div><h2><?= __('Tóm tắt đơn'); ?></h2><p><?= __('Tổng tiền được cập nhật theo cấu hình hiện tại.'); ?></p></div>
            </div>
            <div class="proxy-summary-visual proxy-summary-preview" data-buy-preview>
                <div class="proxy-summary-preview-head">
                    <span class="proxy-summary-preview-flag" data-summary-country-flag aria-hidden="true"><i class="fa-solid fa-globe"></i></span>
                    <div>
                        <span class="proxy-summary-preview-kicker"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <span data-summary-status><?= __('Đang chờ cấu hình'); ?></span></span>
                        <strong data-summary-country><?= __('Chọn quốc gia'); ?></strong>
                        <small data-summary-delivery><?= __('Cấp tự động sau thanh toán · HTTPS + SOCKS5'); ?></small>
                    </div>
                </div>
                <div class="proxy-summary-facts">
                    <div><span><?= __('Loại'); ?></span><strong data-summary-type>IPv4</strong></div>
                    <div><span><?= __('Thời hạn'); ?></span><strong data-summary-rent>--</strong></div>
                    <div><span><?= __('Số lượng'); ?></span><strong data-summary-quantity>1</strong></div>
                    <div><span><?= __('Định dạng'); ?></span><strong data-summary-auth>IP:PORT:USER:PASS</strong></div>
                </div>
            </div>
            <div class="proxy-summary-checkout">
                <dl class="proxy-summary-list">
                    <div><dt><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?= __('Loại dịch vụ'); ?></dt><dd><?= __('Proxy premium'); ?></dd></div>
                </dl>
                <div class="proxy-summary-total"><span><?= __('Tổng thanh toán'); ?></span><strong data-wallet-total>--</strong></div>
                <p class="proxy-price-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <?= __('Giá ví đã bao gồm tỷ giá và phí vận hành của website.'); ?></p>
                <button class="proxy-primary-button" type="button" data-buy-submit disabled><i class="fa-solid fa-lock" aria-hidden="true"></i> <?= __('Đang tải cấu hình'); ?></button>
                <p class="proxy-secure-note"><i class="fa-solid fa-shield-check" aria-hidden="true"></i> <?= __('Thanh toán an toàn, tự động hoàn tiền nếu giao dịch lỗi.'); ?></p>
            </div>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
