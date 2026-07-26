<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once __DIR__ . '/../../models/is_user.php';
require_once __DIR__ . '/../../libs/youproxy.php';

$body = [
    'title' => __('Gia hạn Proxy') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Gia hạn nhiều proxy cùng lúc và bật tự động gia hạn khi cần.'),
    'keyword' => 'gia hạn proxy'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/proxy.css?v=1') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/proxy.js?v=1') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="proxy-page" data-proxy-app data-proxy-page="renew"
    data-endpoint="<?= BASE_URL('ajaxs/client/proxy.php'); ?>"
    data-token="<?= htmlspecialchars($getUser['token'], ENT_QUOTES, 'UTF-8'); ?>"
    data-configured="<?= youproxy_is_configured() ? '1' : '0'; ?>">
    <section class="proxy-page-heading">
        <div><span class="proxy-eyebrow"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Proxy workspace</span><h1><?= __('Gia hạn Proxy'); ?></h1><p><?= __('Chọn các IP cần gia hạn, xem báo giá và xử lý một lần cho cả nhóm.'); ?></p></div>
        <div class="proxy-heading-actions"><a class="proxy-secondary-button" href="<?= base_url('client/proxy-list'); ?>"><i class="fa-solid fa-server" aria-hidden="true"></i> <?= __('Proxy của tôi'); ?></a></div>
    </section>
    <div class="proxy-status" data-proxy-status role="status" aria-live="polite" hidden></div>
    <?php if (!youproxy_is_configured()): ?>
        <section class="proxy-setup-banner"><div class="proxy-setup-icon"><i class="fa-solid fa-plug-circle-xmark" aria-hidden="true"></i></div><div><strong><?= __('Dịch vụ proxy đang chờ cấu hình'); ?></strong><p><?= __('Thêm YOUPROXY_API_KEY vào .env trên cPanel để tải danh sách.'); ?></p></div></section>
    <?php endif; ?>

    <section class="proxy-renew-layout">
        <div class="proxy-panel proxy-renew-select-panel">
            <div class="proxy-panel-heading"><div><div class="proxy-step">01</div><div><h2><?= __('Chọn proxy cần gia hạn'); ?></h2><p data-renew-caption><?= __('Đang tải danh sách...'); ?></p></div></div><button type="button" class="proxy-icon-button" data-refresh-list aria-label="<?= __('Làm mới danh sách'); ?>" title="<?= __('Làm mới danh sách'); ?>"><i class="fa-solid fa-rotate" aria-hidden="true"></i></button></div>
            <div class="proxy-renew-tools"><label class="proxy-control"><span><?= __('Loại proxy'); ?></span><select data-renew-type><option value="">Tất cả loại</option><option value="IPV4">IPv4</option><option value="IPV6">IPv6</option><option value="ISP">ISP</option><option value="MOBILE">Mobile</option></select></label><button type="button" class="proxy-text-button" data-select-expiring><?= __('Chọn proxy sắp hết hạn'); ?></button></div>
            <div class="proxy-renew-list" data-renew-list><div class="proxy-loading-state"><span class="proxy-loader"></span><strong><?= __('Đang đồng bộ danh sách proxy'); ?></strong><small><?= __('Vui lòng chờ trong giây lát.'); ?></small></div></div>
        </div>
        <aside class="proxy-panel proxy-renew-summary">
            <div class="proxy-summary-top"><span class="proxy-step">02</span><div><h2><?= __('Thiết lập gia hạn'); ?></h2><p><?= __('Báo giá sẽ cập nhật theo lựa chọn.'); ?></p></div></div>
            <label class="proxy-control"><span><?= __('Gia hạn thêm'); ?></span><select data-renew-rent disabled><option value="">Chọn thời hạn</option></select></label>
            <div class="proxy-summary-visual proxy-summary-visual--compact"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span data-renew-selected>0 proxy được chọn</span></div>
            <dl class="proxy-summary-list"><div><dt><?= __('Giá nhà cung cấp'); ?></dt><dd data-provider-price>-- USD</dd></div><div><dt><?= __('Tổng thanh toán'); ?></dt><dd data-wallet-total>--</dd></div></dl>
            <button class="proxy-secondary-button proxy-full-button" type="button" data-renew-quote disabled><i class="fa-solid fa-calculator" aria-hidden="true"></i> <?= __('Tính giá'); ?></button>
            <button class="proxy-primary-button" type="button" data-renew-submit disabled><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> <?= __('Gia hạn ngay'); ?></button>
            <label class="proxy-check-row proxy-check-row--summary"><input type="checkbox" data-renew-auto><span class="proxy-check-box"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span><strong><?= __('Tự động gia hạn'); ?></strong><small><?= __('Áp dụng cho nhóm đã chọn'); ?></small></span></label>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
