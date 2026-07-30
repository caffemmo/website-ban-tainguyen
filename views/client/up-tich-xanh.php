<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once __DIR__ . '/../../models/is_user.php';

$services = [
    'get-link' => [
        'label' => 'Get Link Facebook',
        'short' => 'Lấy link xác minh nhanh',
        'description' => 'Nhập đường dẫn Facebook để bắt đầu tạo link xác minh.',
        'icon' => 'fa-link',
        'tone' => 'teal'
    ],
    'up-fb' => [
        'label' => 'Up tích Facebook',
        'short' => 'Xác minh tích xanh Facebook',
        'description' => 'Gửi thông tin xác minh Facebook theo đúng yêu cầu của dịch vụ.',
        'icon' => 'fa-facebook',
        'tone' => 'blue'
    ],
    'up-ig' => [
        'label' => 'Up tích Instagram',
        'short' => 'Xác minh tích xanh Instagram',
        'description' => 'Gửi thông tin xác minh Instagram và chọn số lượng ảnh cần dùng.',
        'icon' => 'fa-instagram',
        'tone' => 'pink'
    ]
];
$service = isset($_GET['service']) && isset($services[$_GET['service']]) ? $_GET['service'] : 'get-link';
$currentService = $services[$service];

$body = [
    'title' => __('Up tích xanh') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Dịch vụ Get Link Facebook, Up tích Facebook và Up tích Instagram.'),
    'keyword' => 'get link facebook, up tích xanh, up tích facebook, up tích instagram'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/up-tich-xanh.css?v=1') . '">';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="up-page">
    <section class="up-hero" aria-labelledby="up-page-title">
        <div class="up-hero-copy">
            <span class="up-eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Social workspace</span>
            <h1 id="up-page-title"><?= __('Up tích xanh'); ?></h1>
            <p><?= __('Chọn đúng dịch vụ, theo dõi yêu cầu và chuẩn bị thông tin xác minh trong một luồng rõ ràng.'); ?></p>
        </div>
        <div class="up-wallet-card">
            <span><?= __('Số dư ví'); ?></span>
            <strong><?= format_currency($getUser['money']); ?></strong>
            <a href="<?= base_url('recharge-bank'); ?>"><?= __('Nạp tiền'); ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>
    </section>

    <section class="up-service-tabs" aria-label="<?= __('Chọn dịch vụ Up tích xanh'); ?>">
        <?php foreach ($services as $key => $item): ?>
            <a class="up-service-tab up-service-tab--<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8'); ?> <?= $service === $key ? 'is-active' : ''; ?>" href="<?= base_url('client/up-tich-xanh/' . $key); ?>" <?= $service === $key ? 'aria-current="page"' : ''; ?>>
                <span class="up-service-tab-icon"><i class="<?= in_array($item['icon'], ['fa-facebook', 'fa-instagram'], true) ? 'fa-brands' : 'fa-solid'; ?> <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
                <span><strong><?= __($item['label']); ?></strong><small><?= __($item['short']); ?></small></span>
                <i class="fa-solid fa-arrow-up-right-from-square up-service-tab-arrow" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="up-content-grid">
        <section class="up-panel" aria-labelledby="up-service-title">
            <div class="up-panel-heading">
                <div>
                    <span class="up-step">01</span>
                    <div>
                        <span class="up-panel-kicker"><?= __('Dịch vụ đang chọn'); ?></span>
                        <h2 id="up-service-title"><?= __($currentService['label']); ?></h2>
                        <p><?= __($currentService['description']); ?></p>
                    </div>
                </div>
                <span class="up-live-state"><i aria-hidden="true"></i> <?= __('Sẵn sàng cấu hình'); ?></span>
            </div>

            <div class="up-notice" role="status">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <div><strong><?= __('API đang chờ kết nối'); ?></strong><span><?= __('Giao diện đã sẵn sàng. Chức năng gửi yêu cầu sẽ mở sau khi cấu hình API nhà cung cấp.'); ?></span></div>
            </div>

            <?php if ($service === 'get-link'): ?>
                <form class="up-form" onsubmit="return false;">
                    <label class="up-field">
                        <span><?= __('Link Facebook'); ?></span>
                        <input type="url" placeholder="https://www.facebook.com/..." autocomplete="url" disabled>
                    </label>
                    <button class="up-submit-button" type="button" disabled><i class="fa-solid fa-link" aria-hidden="true"></i> <?= __('Lấy link'); ?></button>
                </form>
            <?php else: ?>
                <form class="up-form" onsubmit="return false;">
                    <label class="up-field">
                        <span><?= $service === 'up-fb' ? __('Cookie Facebook') : __('Cookie Instagram'); ?></span>
                        <textarea rows="4" placeholder="<?= $service === 'up-fb' ? __('Dán cookie Facebook vào đây...') : __('Dán cookie Instagram vào đây...'); ?>" disabled></textarea>
                    </label>
                    <?php if ($service === 'up-ig'): ?>
                        <fieldset class="up-fieldset">
                            <legend><?= __('Số lượng ảnh'); ?></legend>
                            <div class="up-choice-grid">
                                <button type="button" class="up-choice is-selected" disabled>1 <?= __('ảnh'); ?></button>
                                <button type="button" class="up-choice" disabled>2 <?= __('ảnh'); ?></button>
                            </div>
                        </fieldset>
                    <?php endif; ?>
                    <div class="up-upload-field">
                        <span><?= __('Ảnh giấy tờ xác minh'); ?></span>
                        <label class="up-upload-box">
                            <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                            <strong><?= __('Click hoặc kéo thả ảnh'); ?></strong>
                            <small>PNG, JPG, WEBP · tối đa 10MB</small>
                            <input type="file" accept="image/png,image/jpeg,image/webp" disabled>
                        </label>
                    </div>
                    <button class="up-submit-button" type="button" disabled><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?= __('Bắt đầu xác minh'); ?></button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="up-panel up-side-panel" aria-labelledby="up-guide-title">
            <div class="up-side-heading"><span class="up-step">02</span><div><h2 id="up-guide-title"><?= __('Chuẩn bị trước khi gửi'); ?></h2><p><?= __('Giúp yêu cầu được xử lý thuận lợi hơn.'); ?></p></div></div>
            <ul class="up-check-list">
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Tài khoản phải đã đăng ký và thanh toán gói Meta Verified.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Ảnh giấy tờ rõ nét, đủ thông tin và không bị cắt góc.'); ?></span></li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><?= __('Không chia sẻ cookie cho bên khác ngoài biểu mẫu chính thức.'); ?></span></li>
            </ul>
            <div class="up-safety-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= __('Thông tin chỉ được gửi khi API được cấu hình và bật.'); ?></span></div>
        </aside>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
