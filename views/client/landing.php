<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$site_title = $CMSNT->site('title');
$site_desc = $CMSNT->site('description');
$support_url = $CMSNT->site('facebook') ?: base_url('client/contact');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site_title); ?></title>
    <meta name="description" content="<?= htmlspecialchars($site_desc); ?>">
    <link rel="icon" type="image/png" href="<?= BASE_URL($CMSNT->site('favicon')); ?>">
    <link rel="stylesheet" href="<?= BASE_URL('public/client/css/landing-gateway.css?v=1'); ?>">
</head>
<body>
    <main class="gateway-shell">
        <canvas class="tech-canvas" id="techCanvas" aria-hidden="true"></canvas>
        <div class="tech-grid"></div>
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>

        <section class="gateway-hero">
            <div class="hero-copy">
                <p class="eyebrow">Premium digital resources</p>
                <h1><?= htmlspecialchars($site_title); ?></h1>
                <p class="hero-lead">
                    Chọn nhanh khu vực bạn cần: mua tài nguyên, săn deal, nạp tiền, xem API hoặc liên hệ hỗ trợ.
                </p>

                <div class="hero-actions">
                    <a class="primary-action" href="<?= base_url('client/home'); ?>">Vào shop</a>
                    <a class="secondary-action" href="<?= base_url('document-api'); ?>">Tài liệu API</a>
                </div>
            </div>

            <div class="signal-panel">
                <div class="panel-header">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="signal-ring">
                    <div class="ring-core">LIVE</div>
                </div>
                <div class="panel-stats">
                    <div>
                        <strong>24/7</strong>
                        <span>Auto delivery</span>
                    </div>
                    <div>
                        <strong>API</strong>
                        <span>Ready</span>
                    </div>
                    <div>
                        <strong>Deal</strong>
                        <span>Daily</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="gateway-routes" aria-label="Gateway routes">
            <a class="route-card" href="<?= base_url('client/home'); ?>">
                <span class="route-icon">01</span>
                <strong>Kho tài nguyên</strong>
                <small>Duyệt sản phẩm, xem tồn kho và tạo đơn nhanh.</small>
            </a>
            <a class="route-card" href="<?= base_url('client/home'); ?>">
                <span class="route-icon">02</span>
                <strong>Săn deal</strong>
                <small>Khu vực dành cho các logic sale, giờ vàng và ưu đãi sắp mở.</small>
            </a>
            <a class="route-card" href="<?= base_url('client/recharge-bank'); ?>">
                <span class="route-icon">03</span>
                <strong>Nạp tiền</strong>
                <small>Đi tới khu vực nạp số dư để mua hàng nhanh hơn.</small>
            </a>
            <a class="route-card" href="<?= base_url('document-api'); ?>">
                <span class="route-icon">04</span>
                <strong>Kết nối API</strong>
                <small>Tài liệu tích hợp dành cho khách mua số lượng lớn.</small>
            </a>
            <a class="route-card" href="<?= htmlspecialchars($support_url); ?>">
                <span class="route-icon">05</span>
                <strong>Hỗ trợ</strong>
                <small>Liên hệ khi cần tư vấn sản phẩm hoặc xử lý đơn hàng.</small>
            </a>
        </section>
    </main>
    <script src="<?= BASE_URL('public/client/js/landing-gateway.js?v=1'); ?>"></script>
</body>
</html>
