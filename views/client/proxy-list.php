<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once __DIR__ . '/../../libs/client-session.php';
require_once __DIR__ . '/../../libs/youproxy.php';

$getUser = client_optional_user($CMSNT);
$proxyIsAuthenticated = is_array($getUser);
$proxyUserToken = $proxyIsAuthenticated ? (string) ($getUser['token'] ?? '') : '';

$body = [
    'title' => __('Proxy của tôi') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Quản lý proxy đã mua, thông tin đăng nhập và thời hạn.'),
    'keyword' => 'proxy của tôi, quản lý proxy'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/proxy.css?v=13') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/proxy.js?v=7') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="proxy-page" data-proxy-app data-proxy-page="list"
    data-endpoint="<?= BASE_URL('ajaxs/client/proxy.php'); ?>"
    data-token="<?= htmlspecialchars($proxyUserToken, ENT_QUOTES, 'UTF-8'); ?>"
    data-authenticated="<?= $proxyIsAuthenticated ? '1' : '0'; ?>"
    data-login-url="<?= htmlspecialchars(base_url('client/login'), ENT_QUOTES, 'UTF-8'); ?>"
    data-configured="<?= youproxy_is_configured() ? '1' : '0'; ?>">
    <section class="proxy-page-heading">
        <div>
            <span class="proxy-eyebrow"><i class="fa-solid fa-server" aria-hidden="true"></i> Proxy workspace</span>
            <h1><?= __('Proxy của tôi'); ?></h1>
            <p><?= __('Theo dõi thời hạn, sao chép thông tin kết nối và gia hạn theo nhóm.'); ?></p>
        </div>
        <div class="proxy-heading-actions"><a class="proxy-secondary-button" href="<?= base_url('client/proxy-buy'); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?= __('Mua proxy'); ?></a><button class="proxy-icon-button" type="button" data-refresh-list aria-label="<?= __('Làm mới danh sách'); ?>" title="<?= __('Làm mới danh sách'); ?>"><i class="fa-solid fa-rotate" aria-hidden="true"></i></button></div>
    </section>
    <div class="proxy-status" data-proxy-status role="status" aria-live="polite" hidden></div>
    <?php if (!youproxy_is_configured()): ?>
        <section class="proxy-setup-banner"><div class="proxy-setup-icon"><i class="fa-solid fa-plug-circle-xmark" aria-hidden="true"></i></div><div><strong><?= __('Dịch vụ proxy đang chờ cấu hình'); ?></strong><p><?= __('Dịch vụ đang được chuẩn bị. Vui lòng thử lại sau ít phút hoặc liên hệ hỗ trợ.'); ?></p></div></section>
    <?php endif; ?>

    <section class="proxy-stats-grid" aria-label="<?= __('Tổng quan proxy'); ?>">
        <article class="proxy-stat-card"><span class="proxy-stat-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><div><small><?= __('Tổng proxy'); ?></small><strong data-stat-total>--</strong></div></article>
        <article class="proxy-stat-card"><span class="proxy-stat-icon proxy-stat-icon--green"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><div><small><?= __('Đang hoạt động'); ?></small><strong data-stat-active>--</strong></div></article>
        <article class="proxy-stat-card"><span class="proxy-stat-icon proxy-stat-icon--orange"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><div><small><?= __('Sắp hết hạn'); ?></small><strong data-stat-expiring>--</strong></div></article>
        <article class="proxy-stat-card"><span class="proxy-stat-icon proxy-stat-icon--purple"><i class="fa-solid fa-circle-nodes" aria-hidden="true"></i></span><div><small><?= __('Tình trạng'); ?></small><strong data-stat-system-status>--</strong></div></article>
    </section>

    <section class="proxy-panel proxy-list-panel">
        <div class="proxy-panel-heading proxy-list-heading">
            <div><div class="proxy-step">03</div><div><h2><?= __('Danh sách proxy'); ?></h2><p data-list-caption><?= __('Đang tải danh sách...'); ?></p></div></div>
            <div class="proxy-toolbar"><label class="proxy-control proxy-toolbar-select"><span><?= __('Lọc loại'); ?></span><select data-list-type><option value="">Tất cả</option><option value="IPV4">IPv4</option><option value="IPV6">IPv6</option><option value="ISP">ISP</option><option value="MOBILE">Mobile</option></select></label><button type="button" class="proxy-secondary-button" data-download-proxies><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> <?= __('Tải TXT'); ?></button><button type="button" class="proxy-secondary-button" data-go-renew disabled><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> <?= __('Gia hạn đã chọn'); ?></button></div>
        </div>
        <div class="proxy-format-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span><?= __('Định dạng kết nối:'); ?> <code>IP:Port:User:Pass</code>. <?= __('Bấm nút copy trong từng proxy để sao chép nhanh.'); ?></span></div>
        <div class="proxy-table-wrap" data-proxy-table>
            <div class="proxy-loading-state"><span class="proxy-loader"></span><strong><?= __('Đang đồng bộ danh sách proxy'); ?></strong><small><?= __('Vui lòng chờ trong giây lát.'); ?></small></div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
