<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/youproxy.php';

$getUser = client_optional_user($CMSNT);
$proxyGuides = caffemmo_client_guides_get();
$proxyFaqs = caffemmo_client_faqs_get();
$proxyIsAuthenticated = is_array($getUser);
$proxyUserToken = $proxyIsAuthenticated ? (string) ($getUser['token'] ?? '') : '';

$body = [
    'title' => __('Mua Proxy') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Mua proxy chính hãng với cấu hình rõ ràng và quản lý tập trung.'),
    'keyword' => 'proxy, mua proxy, proxy premium'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/proxy.css?v=35') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/proxy.js?v=9') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="proxy-page" data-proxy-app data-proxy-page="buy"
    data-endpoint="<?= BASE_URL('ajaxs/client/proxy.php'); ?>"
    data-token="<?= htmlspecialchars($proxyUserToken, ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $proxyIsAuthenticated ? '1' : '0'; ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
    data-configured="<?= youproxy_is_configured() ? '1' : '0'; ?>">
    <?php if (!empty($proxyGuides) || !empty($proxyFaqs)): ?>
        <div class="proxy-resource-grid">
            <?php if (!empty($proxyGuides)): ?>
                <section class="proxy-guides proxy-resource-card">
                    <button class="proxy-guides-toggle" type="button" data-proxy-resource-toggle aria-expanded="false" aria-controls="proxy-guides-panel">
                        <span class="proxy-guides-toggle-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                        <span class="proxy-guides-toggle-copy"><strong><?= __('Hướng dẫn sử dụng Proxy'); ?></strong></span>
                        <i class="fa-solid fa-arrow-circle-down proxy-guides-toggle-arrow" aria-hidden="true"></i>
                    </button>
                    <div class="proxy-guides-content" id="proxy-guides-panel" data-proxy-guides-panel hidden>
                        <p class="proxy-guides-description"><?= __('Chọn tài liệu phù hợp để xem hướng dẫn cài đặt và sử dụng.'); ?></p>
                        <div class="proxy-guides-grid">
                            <?php foreach ($proxyGuides as $guide): ?>
                                <a class="proxy-guide-link" href="<?= htmlspecialchars($guide['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="proxy-guide-icon"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                                    <span class="proxy-guide-copy">
                                        <strong><?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?= __('Mở tài liệu'); ?></small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right proxy-guide-arrow" aria-hidden="true"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($proxyFaqs)): ?>
                <section class="proxy-faqs proxy-resource-card">
                    <button class="proxy-guides-toggle" type="button" data-proxy-resource-toggle aria-expanded="false" aria-controls="proxy-faqs-panel">
                        <span class="proxy-guides-toggle-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                        <span class="proxy-guides-toggle-copy"><strong><?= __('Các câu hỏi thường gặp'); ?></strong></span>
                        <i class="fa-solid fa-arrow-circle-down proxy-guides-toggle-arrow" aria-hidden="true"></i>
                    </button>
                    <div class="proxy-guides-content proxy-faqs-content" id="proxy-faqs-panel" data-proxy-faqs-panel hidden>
                        <div class="proxy-faq-list">
                            <?php foreach ($proxyFaqs as $faq): ?>
                                <article class="proxy-faq-item">
                                    <h3><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?= nl2br(htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'), false); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <script>
            (function() {
                document.querySelectorAll('[data-proxy-resource-toggle]').forEach(function(toggle) {
                    var panel = document.getElementById(toggle.getAttribute('aria-controls'));
                    var card = toggle.closest('.proxy-resource-card');
                    if (!panel || !card) return;
                    toggle.addEventListener('click', function() {
                        var isOpen = toggle.getAttribute('aria-expanded') === 'true';
                        toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                        panel.hidden = isOpen;
                        card.classList.toggle('is-open', !isOpen);
                    });
                });
            }());
        </script>
    <?php endif; ?>

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
                    <button type="button" class="proxy-type-card proxy-type-card--isp" data-proxy-type="ISP">
                        <span class="proxy-type-icon"><i class="fa-solid fa-building-shield" aria-hidden="true"></i></span>
                        <span class="proxy-type-content">
                            <span class="proxy-type-title-row"><span class="proxy-type-title"><strong><?= __('Proxy Dân Cư Tĩnh (ISP)'); ?></strong><img class="proxy-type-gif" src="<?= BASE_URL('mod/img/proxy-green-badge.gif'); ?>" alt="<?= __('Ngâm tích xanh'); ?>"></span><span class="proxy-type-badges"><em class="proxy-type-badge">Ngâm tích xanh</em><em class="proxy-type-badge proxy-type-badge--private">Private</em></span></span>
                            <small class="proxy-type-description"><?= __('Proxy Dân Cư Tĩnh (ISP) hỗ trợ tăng tiến độ ngâm tích xanh, duy trì uy tín IP và kết nối ổn định trong thời gian dài. Giảm nguy cơ bị đánh dấu và đảm bảo hiệu suất hoạt động liên tục.'); ?></small>
                            <span class="proxy-type-tags"><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('IP dân cư tĩnh (ISP)'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('ASN cao cấp'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ băng thông lớn'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Tùy chọn theo quốc gia'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ ngâm tích xanh'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Duy trì IP ổn định'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hạn chế CAPTCHA'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Phiên kết nối không giới hạn thời gian'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ HTTP(S) & SOCKS5'); ?></em></span>
                        </span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card proxy-type-card--datacenter is-selected" data-proxy-type="IPV4">
                        <span class="proxy-type-icon"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
                        <span class="proxy-type-content">
                            <span class="proxy-type-title-row"><strong><?= __('Proxy IPv4 Datacenter'); ?></strong><span class="proxy-type-badges"><em class="proxy-type-badge">Ngâm tích xanh</em><em class="proxy-type-badge proxy-type-badge--private">Private</em></span></span>
                            <small class="proxy-type-description"><?= __('Proxy IPv4 Datacenter là giải pháp phù hợp cho hầu hết nhu cầu sử dụng phổ biến. Hỗ trợ ngâm tích xanh, duy trì IP ổn định và kết nối lâu dài, hạn chế gián đoạn, tốc độ quốc tế và băng thông lớn.'); ?></small>
                            <span class="proxy-type-tags"><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('IP riêng'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i>HTTP/SOCKS5</em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ ngâm tích xanh'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Duy trì tích xanh'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Băng thông tốc độ cao'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Tốc độ quốc tế'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Nuôi tài khoản'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Ổn định'); ?></em></span>
                        </span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card" data-proxy-type="IPV6">
                        <span class="proxy-type-icon"><i class="fa-solid fa-network-wired" aria-hidden="true"></i></span>
                        <span class="proxy-type-content">
                            <span class="proxy-type-title-row"><strong><?= __('Proxy IPv6 thường'); ?></strong><span class="proxy-type-badges"><em class="proxy-type-badge">Ngâm tích xanh</em><em class="proxy-type-badge proxy-type-badge--private">Private</em></span></span>
                            <small><?= __('IP riêng, tiết kiệm chi phí, phù hợp nhu cầu cơ bản.'); ?></small>
                            <span class="proxy-type-tags"><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('IP riêng'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i>HTTP/SOCKS5</em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ ngâm tích xanh'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Tốc độ quốc tế'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Tiết kiệm chi phí'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Phù hợp nhu cầu cơ bản'); ?></em></span>
                        </span>
                        <i class="fa-solid fa-check proxy-type-check" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="proxy-type-card" data-proxy-type="MOBILE">
                        <span class="proxy-type-icon"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></span>
                        <span class="proxy-type-content">
                            <span class="proxy-type-title-row"><strong><?= __('Proxy Mobile'); ?></strong><span class="proxy-type-badges"><em class="proxy-type-badge proxy-type-badge--private">Private</em></span></span>
                            <small><?= __('Rotation linh hoạt cho tác vụ cần thay đổi IP.'); ?></small>
                            <span class="proxy-type-tags"><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Rotation linh hoạt'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Phủ sóng toàn cầu'); ?></em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i>HTTP/SOCKS5</em><em><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= __('Hỗ trợ 24/7'); ?></em></span>
                        </span>
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
                    <small class="proxy-ipv6-retail-note" data-ipv6-retail-note hidden><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i> <?= __('IPv6 được cấp lẻ từ kho với Login / Password.'); ?></small>
                </label>
                <label class="proxy-control">
                    <span><?= __('Mục đích sử dụng'); ?></span>
                    <input type="text" name="goal" value="Facebook" maxlength="200" placeholder="Ví dụ: Quản lý quảng cáo" required>
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
                <label class="proxy-control proxy-promo-control" data-promo-control><span><?= __('Mã giảm giá'); ?></span><input type="text" name="promo_code" maxlength="60" placeholder="Không bắt buộc"></label>
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
