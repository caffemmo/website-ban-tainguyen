<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>
<?php
require_once __DIR__ . '/../../libs/service-catalog.php';

$upService = isset($_GET['service']) && in_array($_GET['service'], ['get-link', 'up-fb', 'up-ig'], true)
    ? $_GET['service']
    : '';
$upMenuActive = isset($action) && $action === 'up-tich-xanh';
$upHistoryActive = isset($action) && $action === 'up-tich-xanh-history';
if ($upMenuActive && $upService === '') {
    $upService = 'get-link';
}
$serviceCatalog = caffemmo_service_catalog();
?>

<body class="has-desktop-sidebar">
    <div class="backdrop"></div><a class="backtop" href="#"><i class="fa-sharp fa-solid fa-chevron-up"></i></a>
    <aside class="desktop-app-sidebar" aria-label="<?= __('Điều hướng chính'); ?>">
        <div class="desktop-sidebar-scroll">
            <nav class="desktop-sidebar-nav">
                <p class="desktop-sidebar-section-title"><?= __('Tổng quan'); ?></p>
                <a class="desktop-sidebar-link <?= isset($action) && $action == 'home' ? 'active' : ''; ?>" href="<?= base_url('client/home'); ?>">
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span><?= __('Trang chủ'); ?></span>
                </a>
                <a class="desktop-sidebar-link <?= isset($action) && $action == 'products' ? 'active' : ''; ?>" href="<?= base_url('client/products'); ?>">
                    <i class="fa-solid fa-store" aria-hidden="true"></i><span><?= __('Mua tài nguyên'); ?></span>
                </a>

                <?php foreach ($serviceCatalog as $serviceGroupKey => $serviceGroup): ?>
                <p class="desktop-sidebar-section-title"><?= __($serviceGroup['title']); ?></p>
                    <?php foreach ($serviceGroup['items'] as $serviceItem): ?>
                    <?php $serviceActive = caffemmo_is_service_active($serviceItem, $action ?? '', $upService); ?>
                    <a class="desktop-sidebar-link desktop-sidebar-service-link <?= $serviceActive ? 'active' : ''; ?>" href="<?= htmlspecialchars($serviceItem['url'], ENT_QUOTES, 'UTF-8'); ?>" <?= $serviceActive ? 'aria-current="page"' : ''; ?>>
                        <i class="<?= htmlspecialchars($serviceItem['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        <span class="desktop-sidebar-link-label">
                            <strong><?= __($serviceItem['label']); ?></strong>
                            <small><?= __($serviceItem['short']); ?></small>
                        </span>
                    </a>
                    <?php endforeach; ?>
                    <?php if ($serviceGroupKey === 'up-tich-xanh'): ?>
                    <a class="desktop-sidebar-link desktop-sidebar-service-link <?= $upHistoryActive ? 'active' : ''; ?>" href="<?= base_url('client/up-tich-xanh-history'); ?>" <?= $upHistoryActive ? 'aria-current="page"' : ''; ?>>
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                        <span class="desktop-sidebar-link-label"><strong><?= __('Lịch sử yêu cầu'); ?></strong><small><?= __('Theo dõi dịch vụ đã gửi'); ?></small></span>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if($CMSNT->site('status_menu_tools') == 1): ?>
                <p class="desktop-sidebar-section-title"><?= __('Công cụ miễn phí'); ?></p>
                <a class="desktop-sidebar-link" href="<?= base_url('tool/check-live-facebook'); ?>">
                    <i class="fa-brands fa-facebook" aria-hidden="true"></i><span><?= __('Check live Facebook'); ?></span>
                </a>
                <a class="desktop-sidebar-link" href="<?= base_url('tool/get-2fa'); ?>">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= __('Lấy mã 2FA'); ?></span>
                </a>
                <a class="desktop-sidebar-link" href="<?= base_url('tool/icon-facebook'); ?>">
                    <i class="fa-solid fa-icons" aria-hidden="true"></i><span><?= __('Icon Facebook'); ?></span>
                </a>
                <a class="desktop-sidebar-link" href="<?= base_url('tool/random-face'); ?>">
                    <i class="fa-solid fa-face-smile" aria-hidden="true"></i><span><?= __('Random Face'); ?></span>
                </a>
                <?php endif; ?>

                <p class="desktop-sidebar-section-title"><?= __('Hỗ trợ'); ?></p>
                <a class="desktop-sidebar-link" href="<?= base_url('client/contact'); ?>">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i><span><?= __('Liên hệ hỗ trợ'); ?></span>
                </a>
                <a class="desktop-sidebar-link" href="<?= base_url('client/faq'); ?>">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i><span><?= __('Câu hỏi thường gặp'); ?></span>
                </a>
                <?php if($CMSNT->site('api_status') == 1): ?>
                <a class="desktop-sidebar-link" href="<?= base_url('document-api'); ?>">
                    <i class="fa-regular fa-file-code" aria-hidden="true"></i><span><?= __('Tài liệu API'); ?></span>
                </a>
                <?php endif; ?>
                <?php if(isset($getUser)): ?>
                <a class="desktop-sidebar-link desktop-sidebar-logout" href="<?= base_url('client/logout'); ?>">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span><?= __('Đăng xuất'); ?></span>
                </a>
                <?php else: ?>
                <a class="desktop-sidebar-link" href="<?= base_url('client/login'); ?>">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span><?= __('Đăng nhập'); ?></span>
                </a>
                <?php endif; ?>
            </nav>
            <div class="desktop-sidebar-support-card">
                <span class="desktop-sidebar-support-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
                <span class="desktop-sidebar-support-copy">
                    <strong><?= __('Cần hỗ trợ?'); ?></strong>
                    <small><?= __('Đội ngũ luôn sẵn sàng'); ?></small>
                </span>
                <a href="<?= base_url('client/contact'); ?>" aria-label="<?= __('Liên hệ hỗ trợ'); ?>" title="<?= __('Liên hệ hỗ trợ'); ?>">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </aside>
    <div class="header-top">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-5">
                    <div class="header-top-welcome">
                        <p><?= $CMSNT->site('notice_top_left'); ?></p>
                    </div>
                </div>
                <div class="col-md-5 col-lg-3">
                    <div class="header-top-select">
                        <div class="header-select"><i class="icofont-world"></i>
                            <?php if ($CMSNT->site('language_type') == 'manual'): ?>
                                <select class="select" id="changeLanguage" onchange="changeLanguage()">
                                    <?php foreach (get_languages_cached() as $lang): ?>
                                        <option value="<?= $lang['id']; ?>"
                                            <?= getLanguage() == $lang['lang'] ? 'selected' : ''; ?>><?= $lang['lang']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="header-select-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                            <?php elseif ($CMSNT->site('language_type') == 'gtranslate'): ?>
                                <?= $CMSNT->site('gtranslate_script'); ?>
                            <?php endif ?>
                        </div>
                        <div class="header-select"><i class="icofont-money"></i>
                            <select class="select" id="changeCurrency" onchange="changeCurrency()">
                                <?php foreach (get_currencies_cached() as $currency): ?>
                                    <option value="<?= $currency['id']; ?>"
                                        <?= getCurrency() == $currency['id'] ? 'selected' : ''; ?>><?= $currency['code']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="header-select-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 col-lg-4">
                    <ul class="header-top-list">
                        <li><a href="<?= base_url('client/policy'); ?>"><?= __('Chính sách'); ?></a></li>
                        <li><a href="<?= base_url('client/faq'); ?>"><?= __('FAQ'); ?></a></li>
                        <li><a href="<?= base_url('client/contact'); ?>"><?= __('Liên Hệ'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
    $homeNoticeRaw = trim((string)$CMSNT->site('notice_home'));
    $homeNoticeText = html_entity_decode(strip_tags($homeNoticeRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $homeNoticeText = preg_replace('/\s+/u', ' ', trim($homeNoticeText));
    if ($homeNoticeText === null) {
        $homeNoticeText = '';
    }
    ?>
    <?php if ($homeNoticeText !== ''): ?>
    <section class="site-notice-marquee" aria-label="<?= __('Thông báo'); ?>">
        <div class="container">
            <span class="site-notice-marquee-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
            <div class="site-notice-marquee-viewport">
                <div class="site-notice-marquee-track">
                    <span class="site-notice-marquee-message"><?= htmlspecialchars($homeNoticeText, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php for ($noticeCopy = 0; $noticeCopy < 3; $noticeCopy++): ?>
                    <span class="site-notice-marquee-message" aria-hidden="true"><?= htmlspecialchars($homeNoticeText, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <header class="header-part">
        <div class="container">
            <div class="header-content">
                <div class="header-media-group">
                    <button class="header-user" type="button" aria-label="<?= __('Mở menu'); ?>"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
                    <a href="<?= base_url(); ?>">
                        <img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>" alt="logo"></a>
                    <button class="header-src"><i class="fas fa-search"></i></button>
                </div>
                <a href="<?= base_url(); ?>" class="header-logo"><img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>"
                        alt="logo"></a>
                <form class="header-form" method="GET" action="<?= base_url('client/products'); ?>">
                    <input type="text" name="keyword" value="<?= isset($keyword) ? $keyword : ''; ?>"
                        placeholder="<?= __('Tìm kiếm sản phẩm...'); ?>"><button><i class="fas fa-search"></i></button>
                </form>
                <div class="header-widget-group">
                    <a href="<?= base_url('product-orders/'); ?>" class="header-widget" title="<?= __('Đơn hàng'); ?>"><i
                            class="fa-solid fa-cart-arrow-down"></i></a>
                    <a href="<?= base_url('client/favorites'); ?>" class="header-widget"
                        title="<?= __('Sản phẩm yêu thích'); ?>">
                        <i class="fas fa-heart"></i>
                        <sup
                            id="numFavorites"><?= isset($getUser) ? $CMSNT->get_row_safe(" SELECT COUNT(id) FROM `favorites` WHERE `user_id` = ? ", [$getUser['id']])["COUNT(id)"] : 0; ?></sup>
                    </a>
                    <button class="header-widget header-cart" title="<?= __('Nạp tiền'); ?>"><i
                            class="fa-solid fa-building-columns"></i>

                    </button>
                    <?php if (isset($getUser)): ?>
                        <a href="<?= base_url('client/profile'); ?>" class="header-widget" title="Profile">
                            <img src="<?= BASE_URL($CMSNT->site('avatar')); ?>" alt="user"><span>
                                <p class="text-uppercase"><?= $getUser['username']; ?></p>
                                <p style="color:blue;"><?= format_currency($getUser['money']); ?></p>
                            </span>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('client/login'); ?>" class="header-widget" title="Login">
                            <img src="<?= BASE_URL($CMSNT->site('avatar')); ?>" alt="user"><span>Login</span>
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </header>
    <nav class="navbar-part">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navbar-content">
                        <ul class="navbar-list">
                            <li class="navbar-item"><a class="navbar-link"
                                    href="<?= base_url(''); ?>"><?= __('Trang chủ'); ?></a>
                            </li>
                            <li class="navbar-item dropdown-megamenu"><a class="navbar-link dropdown-arrow"
                                    href="#"><?= __('Sản phẩm'); ?></a>
                                <div class="megamenu">
                                    <div class="container">
                                        <div id="menu-categories-container">
                                            <!-- Skeleton loading cho menu -->
                                            <div class="menu-skeleton">
                                                <div class="row row-cols-5">
                                                    <div class="col-4">
                                                        <div class="megamenu-wrap">
                                                            <div class="skeleton-menu-title"></div>
                                                            <ul class="megamenu-list">
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="megamenu-wrap">
                                                            <div class="skeleton-menu-title"></div>
                                                            <ul class="megamenu-list">
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="megamenu-wrap">
                                                            <div class="skeleton-menu-title"></div>
                                                            <ul class="megamenu-list">
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                                <li>
                                                                    <div class="skeleton-menu-item"></div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="navbar-item dropdown">
                                <a class="navbar-link dropdown-arrow" href="#"><?= __('Nạp tiền'); ?></a>
                                <ul class="dropdown-position-list">
                                    <?php if ($CMSNT->site('bank_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-bank'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-bank.svg'); ?>">
                                                <?= __('Ngân hàng'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('momo_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-momo'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-momo.png'); ?>">
                                                <?= __('Ví MOMO'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('thesieure_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-thesieure'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-thesieure.webp'); ?>">
                                                <?= __('Ví THESIEURE'); ?></a>
                                        </li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('card_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-card'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-cards.png'); ?>">
                                                <?= __('Thẻ cào'); ?></a>
                                        </li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('crypto_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-crypto'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-usdt.svg'); ?>"> <?= __('Crypto'); ?></a>
                                        </li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('paypal_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-paypal'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-paypal.svg'); ?>">
                                                <?= __('Paypal'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('perfectmoney_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-perfectmoney'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-perfectmoney.svg'); ?>">
                                                <?= __('Perfect Money'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('toyyibpay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-toyyibpay'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-toyyibpay.jpeg'); ?>">
                                                <?= __('Toyyibpay Malaysia'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('squadco_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-squadco'); ?>"><img width="20px"
                                                    src="<?= base_url('assets/img/icon-squadco.png'); ?>">
                                                <?= __('Squadco Nigeria'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('flutterwave_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-flutterwave'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-flutterwave.png'); ?>">
                                                <?= __('Flutterwave'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-xipay'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/logo-xipay.webp'); ?>">
                                                <?= __('AliPay & WeChat Pay'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('lempay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-lempay'); ?>"><img width="20px"
                                                    src="<?= base_url($CMSNT->site('lempay_icon') ?: 'mod/img/logo-lempay.webp'); ?>">
                                                <?= __($CMSNT->site('lempay_name') ?: 'LemPay'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('tripay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-tripay'); ?>"><img width="20px"
                                                    src="<?= base_url($CMSNT->site('tripay_icon') ?: 'mod/img/logo-tripay.webp'); ?>">
                                                <?= __($CMSNT->site('tripay_name') ?: 'TriPay Indonesia'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('zinipay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-zinipay'); ?>"><img width="20px"
                                                    src="<?= base_url($CMSNT->site('zinipay_icon') ?: 'mod/img/logo-zinipay.webp'); ?>">
                                                <?= __($CMSNT->site('zinipay_name') ?: 'ZiniPay'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('korapay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-korapay'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/logo-korapay.webp'); ?>">
                                                <?= __('Korapay Africa'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('pocketfi_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-pocketfi'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-pocketfi.webp'); ?>">
                                                <?= __('PocketFi Nigeria'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('paymentpoint_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-paymentpoint'); ?>"><img width="20px"
                                                    src="<?= base_url($CMSNT->site('paymentpoint_icon') ?: 'mod/img/paymentpoint.png'); ?>">
                                                <?= __($CMSNT->site('paymentpoint_name') ?: 'PaymentPoint'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('dsociopay_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-dsociopay'); ?>"><img width="20px"
                                                    src="<?= base_url($CMSNT->site('dsociopay_icon') ?: 'mod/img/dsociopay.png'); ?>">
                                                <?= __($CMSNT->site('dsociopay_name') ?: 'DSocioPay'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-tmweasyapi'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>">
                                                <?= __('Tmweasyapi Thailand'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('openpix_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-openpix'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-openpix.webp'); ?>">
                                                <?= __('OpenPix'); ?></a></li>
                                    <?php endif ?>
                                    <?php if ($CMSNT->site('bakong_status') == 1): ?>
                                        <li><a href="<?= base_url('?action=recharge-bakong'); ?>"><img width="20px"
                                                    src="<?= base_url('mod/img/icon-bakong.webp'); ?>">
                                                <?= __('Bakong Wallet Cambodia'); ?></a></li>
                                    <?php endif ?>
                                    <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                                        <li><a href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>"><img
                                                    width="20px" src="<?= base_url($payment_manual['icon']); ?>">
                                                <?= __($payment_manual['title']); ?></a></li>
                                    <?php endforeach ?>
                                </ul>
                            </li>
                            <li class="navbar-item dropdown">
                                <a class="navbar-link dropdown-arrow" href="#"><?= __('Lịch sử'); ?></a>
                                <ul class="dropdown-position-list">
                                    <li><a href="<?= base_url('product-orders/'); ?>"><?= __('Lịch sử đơn hàng'); ?></a>
                                    </li>
                                    <li><a href="<?= base_url('client/logs'); ?>"><?= __('Nhật ký hoạt động'); ?></a></li>
                                    <li><a href="<?= base_url('client/transactions'); ?>"><?= __('Biến động số dư'); ?></a>
                                    </li>
                                </ul>
                            </li>
                            <?php if ($CMSNT->site('affiliate_status') == 1): ?>
                                <li class="navbar-item dropdown">
                                    <a class="navbar-link dropdown-arrow" href="#"><?= __('Affiliate Program'); ?></a>
                                    <ul class="dropdown-position-list">
                                        <li><a href="<?= base_url('?action=affiliates'); ?>"><?= __('Thống kê'); ?></a></li>
                                        <li><a href="<?= base_url('?action=affiliate-history'); ?>"><?= __('Lịch sử'); ?></a>
                                        </li>
                                        <li><a href="<?= base_url('?action=affiliate-withdraw'); ?>"><?= __('Rút tiền'); ?></a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif ?>
                            <?php if ($CMSNT->site('blog_status') == 1): ?>
                                <li class="navbar-item"><a class="navbar-link"
                                        href="<?= base_url('blogs'); ?>"><?= __('Blogs'); ?></a></li>
                            <?php endif ?>
                            <?php if ($CMSNT->site('api_status') == 1): ?>
                                <li class="navbar-item"><a class="navbar-link"
                                        href="<?= base_url('document-api'); ?>"><?= __('Tài liệu API'); ?></a></li>
                            <?php endif ?>
                            <?php if (isset($getUser) && $getUser['ctv'] != 0 && $CMSNT->site('ctv_status') == 1): ?>
                                <li class="navbar-item"><a class="navbar-link"
                                        href="<?= base_url_ctv(); ?>"><?= __('CTV Panel'); ?></a></li>
                            <?php endif ?>
                            <?php if (isset($getUser) && $getUser['admin'] != 0): ?>
                                <li class="navbar-item"><a class="navbar-link"
                                        href="<?= base_url_admin(); ?>"><?= __('Admin Panel'); ?></a></li>
                            <?php endif ?>
                        </ul>
                        <div class="navbar-info-group">
                            <div class="navbar-info"><?= $CMSNT->site('icon_hotline'); ?>
                                <p><small><?= __('Hotline'); ?></small><span><?= $CMSNT->site('hotline'); ?></span></p>
                            </div>
                            <div class="navbar-info"><?= $CMSNT->site('icon_email'); ?>
                                <p><small><?= __('Email'); ?></small><span><?= $CMSNT->site('email'); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <aside class="category-sidebar">
        <div class="category-header">
            <h4 class="category-title"><i class="fas fa-align-left"></i><span><?= __('Sản phẩm'); ?></span></h4><button
                class="category-close"><i class="icofont-close"></i></button>
        </div>
        <!--menu mobile-->
        <ul class="category-list">
            <?php foreach (get_categories_parent_cached() as $category): ?>
                <li class="category-item">
                    <a class="category-link dropdown-link" href="#">
                        <img src="<?= base_url($category['icon']); ?>" style="margin-right: 10px;" width="30px">
                        <?= __($category['name']); ?> </a>
                    <ul class="dropdown-list">
                        <?php foreach (get_categories_by_parent_cached($category['id']) as $category1): ?>
                            <li><a href="<?= base_url('category/' . $category1['slug']); ?>"><?= __($category1['name']); ?></a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </li>
            <?php endforeach ?>
        </ul>
    </aside>
    <aside class="cart-sidebar">
        <div class="cart-header">
            <div class="cart-total"><i
                    class="fa-solid fa-building-columns"></i><span><?= __('Chọn phương thức nạp tiền'); ?></span></div>
            <button class="cart-close"><i class="icofont-close"></i></button>
        </div>
        <ul class="category-list">
            <?php if ($CMSNT->site('bank_status') == 1): ?>
                <li class="category-item">
                    <a class="category-link" href="<?= base_url('?action=recharge-bank'); ?>"><img style="margin-right: 10px;"
                            width="30px" src="<?= base_url('assets/img/icon-bank.svg'); ?>">
                        <?= __('Ngân hàng'); ?></a>
                </li>
            <?php endif ?>
            <?php if ($CMSNT->site('momo_status') == 1): ?>
                <li class="category-item"><a href="<?= base_url('?action=recharge-momo'); ?>" class="category-link"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('assets/img/icon-momo.png'); ?>">
                        <?= __('Ví MOMO'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('thesieure_status') == 1): ?>
                <li class="category-item"><a href="<?= base_url('?action=recharge-thesieure'); ?>" class="category-link"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/icon-thesieure.webp'); ?>">
                        <?= __('Ví THESIEURE'); ?></a>
                </li>
            <?php endif ?>
            <?php if ($CMSNT->site('card_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-card'); ?>">
                        <img width="30px" style="margin-right: 10px;" src="<?= base_url('assets/img/icon-cards.png'); ?>">
                        <?= __('Thẻ cào'); ?></a>
                </li>
            <?php endif ?>
            <?php if ($CMSNT->site('crypto_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-crypto'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('assets/img/icon-usdt.svg'); ?>">
                        <?= __('Crypto'); ?></a>
                </li>
            <?php endif ?>
            <?php if ($CMSNT->site('paypal_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-paypal'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('assets/img/icon-paypal.svg'); ?>">
                        <?= __('Paypal'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('perfectmoney_status') == 1): ?>
                <li class="category-item"><a class="category-link"
                        href="<?= base_url('?action=recharge-perfectmoney'); ?>"><img style="margin-right: 10px;" width="30px"
                            src="<?= base_url('assets/img/icon-perfectmoney.svg'); ?>">
                        <?= __('Perfect Money'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('toyyibpay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-toyyibpay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('assets/img/icon-toyyibpay.jpeg'); ?>">
                        <?= __('Toyyibpay Malaysia'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('squadco_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-squadco'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('assets/img/icon-squadco.png'); ?>">
                        <?= __('Squadco Nigeria'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('flutterwave_status') == 1): ?>
                <li class="category-item"><a class="category-link"
                        href="<?= base_url('?action=recharge-flutterwave'); ?>"><img style="margin-right: 10px;" width="30px"
                            src="<?= base_url('mod/img/icon-flutterwave.png'); ?>">
                        <?= __('Flutterwave'); ?></a></li>
            <?php endif ?>

            <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-xipay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/logo-xipay.webp'); ?>">
                        <?= __('AliPay & WeChat Pay'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('lempay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-lempay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url($CMSNT->site('lempay_icon') ?: 'mod/img/logo-lempay.webp'); ?>">
                        <?= __($CMSNT->site('lempay_name') ?: 'LemPay'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('tripay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-tripay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url($CMSNT->site('tripay_icon') ?: 'mod/img/logo-tripay.webp'); ?>">
                        <?= __($CMSNT->site('tripay_name') ?: 'TriPay Indonesia'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('zinipay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-zinipay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url($CMSNT->site('zinipay_icon') ?: 'mod/img/logo-zinipay.webp'); ?>">
                        <?= __($CMSNT->site('zinipay_name') ?: 'ZiniPay'); ?></a></li>
            <?php endif ?>

            <?php if ($CMSNT->site('korapay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-korapay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/logo-korapay.webp'); ?>">
                        <?= __('Korapay Africa'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('pocketfi_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-pocketfi'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/icon-pocketfi.webp'); ?>">
                        <?= __('PocketFi Nigeria'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('paymentpoint_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-paymentpoint'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url($CMSNT->site('paymentpoint_icon') ?: 'mod/img/paymentpoint.png'); ?>">
                        <?= __($CMSNT->site('paymentpoint_name') ?: 'PaymentPoint'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('dsociopay_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-dsociopay'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url($CMSNT->site('dsociopay_icon') ?: 'mod/img/dsociopay.png'); ?>">
                        <?= __($CMSNT->site('dsociopay_name') ?: 'DSocioPay'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-tmweasyapi'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>">
                        <?= __('Tmweasyapi Thailand'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('openpix_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-openpix'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/icon-openpix.webp'); ?>">
                        <?= __('OpenPix'); ?></a></li>
            <?php endif ?>
            <?php if ($CMSNT->site('bakong_status') == 1): ?>
                <li class="category-item"><a class="category-link" href="<?= base_url('?action=recharge-bakong'); ?>"><img
                            style="margin-right: 10px;" width="30px" src="<?= base_url('mod/img/icon-bakong.webp'); ?>">
                        <?= __('Bakong Wallet Cambodia'); ?></a></li>
            <?php endif ?>


            <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                <li class="category-item"><a class="category-link"
                        href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>"><img style="margin-right: 10px;"
                            width="30px" src="<?= base_url($payment_manual['icon']); ?>">
                        <?= __($payment_manual['title']); ?></a></li>
            <?php endforeach ?>
        </ul>
    </aside>
    <aside class="nav-sidebar nav-sidebar-mobile" aria-label="<?= __('Menu chính'); ?>" aria-hidden="true">
        <div class="nav-header">
            <button class="nav-close" type="button" aria-label="<?= __('Đóng menu'); ?>">
                <i class="icofont-close" aria-hidden="true"></i>
            </button>
        </div>
        <div class="nav-content">
            <div class="nav-account-card">
                <span class="nav-account-avatar"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                <span class="nav-account-copy">
                    <strong><?= isset($getUser) ? htmlspecialchars($getUser['username'], ENT_QUOTES, 'UTF-8') : __('Khách truy cập'); ?></strong>
                    <small><?= isset($getUser) ? __('Tài khoản thành viên') : __('Đăng nhập để mua hàng'); ?></small>
                </span>
                <?php if (isset($getUser)): ?>
                    <span class="nav-account-balance"><?= format_currency($getUser['money']); ?></span>
                <?php endif; ?>
            </div>

            <nav class="nav-list" aria-label="<?= __('Điều hướng mobile'); ?>">
                <p class="nav-section-title"><?= __('Tổng quan'); ?></p>
                <a class="nav-link <?= active_sidebar_client(['home', '']); ?>" href="<?= base_url('client/home'); ?>">
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span><?= __('Trang chủ'); ?></span>
                </a>
                <a class="nav-link <?= active_sidebar_client(['products']); ?>" href="<?= base_url('client/products'); ?>">
                    <i class="fa-solid fa-store" aria-hidden="true"></i><span><?= __('Mua tài nguyên'); ?></span>
                </a>

                <p class="nav-section-title"><?= __('Tài chính'); ?></p>
                <a class="nav-link <?= active_sidebar_client(['recharge-bank', 'recharge-momo', 'recharge-card', 'recharge-crypto', 'recharge-paypal', 'recharge-manual']); ?>" href="<?= base_url('?action=recharge-bank'); ?>">
                    <i class="fa-solid fa-building-columns" aria-hidden="true"></i><span><?= __('Nạp tiền'); ?></span>
                </a>
                <a class="nav-link <?= active_sidebar_client(['product-orders', 'product-order']); ?>" href="<?= base_url('product-orders'); ?>">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><?= __('Lịch sử đơn hàng'); ?></span>
                </a>

                <?php foreach ($serviceCatalog as $serviceGroupKey => $serviceGroup): ?>
                <p class="nav-section-title"><?= __($serviceGroup['title']); ?></p>
                    <?php foreach ($serviceGroup['items'] as $serviceItem): ?>
                    <?php $serviceActive = caffemmo_is_service_active($serviceItem, $action ?? '', $upService); ?>
                    <a class="nav-link <?= $serviceActive ? 'active' : ''; ?>" href="<?= htmlspecialchars($serviceItem['url'], ENT_QUOTES, 'UTF-8'); ?>" <?= $serviceActive ? 'aria-current="page"' : ''; ?>>
                        <i class="<?= htmlspecialchars($serviceItem['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        <span class="nav-link-label"><strong><?= __($serviceItem['label']); ?></strong><small><?= __($serviceItem['short']); ?></small></span>
                    </a>
                    <?php endforeach; ?>
                    <?php if ($serviceGroupKey === 'up-tich-xanh'): ?>
                    <a class="nav-link <?= $upHistoryActive ? 'active' : ''; ?>" href="<?= base_url('client/up-tich-xanh-history'); ?>" <?= $upHistoryActive ? 'aria-current="page"' : ''; ?>>
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><?= __('Lịch sử yêu cầu'); ?></span>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <p class="nav-section-title"><?= __('Hỗ trợ'); ?></p>
                <a class="nav-link" href="<?= base_url('client/contact'); ?>">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i><span><?= __('Liên hệ hỗ trợ'); ?></span>
                </a>
                <a class="nav-link" href="<?= base_url('client/faq'); ?>">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i><span><?= __('Câu hỏi thường gặp'); ?></span>
                </a>
                <?php if ($CMSNT->site('affiliate_status') == 1): ?>
                    <a class="nav-link" href="<?= base_url('?action=affiliates'); ?>">
                        <i class="fa-solid fa-money-bill-trend-up" aria-hidden="true"></i><span><?= __('Affiliate Program'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($CMSNT->site('api_status') == 1): ?>
                    <a class="nav-link" href="<?= base_url('document-api'); ?>">
                        <i class="fa-regular fa-file-code" aria-hidden="true"></i><span><?= __('Tài liệu API'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if (isset($getUser) && $getUser['admin'] != 0): ?>
                    <a class="nav-link" href="<?= base_url_admin(); ?>">
                        <i class="fa-solid fa-gear" aria-hidden="true"></i><span><?= __('Admin Panel'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if (isset($getUser)): ?>
                    <a class="nav-link nav-link--logout" href="<?= base_url('client/logout'); ?>">
                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span><?= __('Đăng xuất'); ?></span>
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="<?= base_url('client/login'); ?>">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span><?= __('Đăng nhập'); ?></span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="nav-support-card">
                <span class="nav-support-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
                <span><strong><?= __('Cần hỗ trợ?'); ?></strong><small><?= __('Đội ngũ luôn sẵn sàng'); ?></small></span>
                <a href="<?= base_url('client/contact'); ?>" aria-label="<?= __('Liên hệ hỗ trợ'); ?>" title="<?= __('Liên hệ hỗ trợ'); ?>">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </aside>
    <aside class="nav-sidebar nav-sidebar-legacy" aria-hidden="true">
        <div class="nav-header"><a href="<?= base_url(); ?>"><img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>"
                    alt="logo"></a><button class="nav-close"><i class="icofont-close"></i></button></div>
        <div class="nav-content">
            <div class="nav-btn">
                <?php if (isset($getUser)): ?>
                    <a href="<?= base_url('client/profile'); ?>" class="btn btn-inline">
                        <i class="fa fa-user"></i> <span><?= $getUser['username']; ?></span></a>
                <?php else: ?>
                    <a href="<?= base_url('client/login'); ?>" class="btn btn-inline">
                        <i class="fa fa-unlock-alt"></i> <span><?= __('Đăng Nhập'); ?></span></a>
                <?php endif ?>

            </div>
            <div class="nav-select-group">
                <p><?= __('Số dư của tôi:'); ?> <strong
                        class="text-wallet"><?= isset($getUser) ? format_currency($getUser['money']) : 0; ?></strong></p>
            </div>
            <ul class="nav-list">
                <li><a class="nav-link" href="<?= base_url('client/home'); ?>"><i
                            class="icofont-home"></i><?= __('Trang chủ'); ?></a></li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-cart-shopping"></i><?= __('Sản phẩm'); ?></a>
                    <ul class="dropdown-list">
                        <?php foreach (get_categories_not_parent_cached() as $category1): ?>
                            <li><a href="<?= base_url('category/' . $category1['slug']); ?>"><img width="25px"
                                        class="me-2 active" src="<?= base_url($category1['icon']); ?>">
                                    <?= __($category1['name']); ?></a></li>
                        <?php endforeach ?>
                    </ul>
                </li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-building-columns"></i><?= __('Nạp tiền'); ?></a>
                    <ul class="dropdown-list">
                        <?php if ($CMSNT->site('bank_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-bank'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-bank.svg'); ?>">
                                    <?= __('Ngân hàng'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('momo_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-momo'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-momo.png'); ?>">
                                    <?= __('Ví MOMO'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('thesieure_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-thesieure'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-thesieure.webp'); ?>">
                                    <?= __('Ví THESIEURE'); ?></a>
                            </li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('card_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-card'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-cards.png'); ?>">
                                    <?= __('Thẻ cào'); ?></a>
                            </li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('crypto_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-crypto'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-usdt.svg'); ?>"> <?= __('Crypto'); ?></a>
                            </li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('paypal_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-paypal'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-paypal.svg'); ?>">
                                    <?= __('Paypal'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('perfectmoney_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-perfectmoney'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-perfectmoney.svg'); ?>">
                                    <?= __('Perfect Money'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('toyyibpay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-toyyibpay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-toyyibpay.jpeg'); ?>">
                                    <?= __('Toyyibpay Malaysia'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('squadco_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-squadco'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-squadco.png'); ?>">
                                    <?= __('Squadco Nigeria'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('flutterwave_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-flutterwave'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-flutterwave.png'); ?>">
                                    <?= __('Flutterwave'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-xipay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/logo-xipay.webp'); ?>">
                                    <?= __('AliPay & WeChat Pay'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('lempay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-lempay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url($CMSNT->site('lempay_icon') ?: 'mod/img/logo-lempay.webp'); ?>">
                                    <?= __($CMSNT->site('lempay_name') ?: 'LemPay'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('tripay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-tripay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url($CMSNT->site('tripay_icon') ?: 'mod/img/logo-tripay.webp'); ?>">
                                    <?= __($CMSNT->site('tripay_name') ?: 'TriPay Indonesia'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('zinipay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-zinipay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url($CMSNT->site('zinipay_icon') ?: 'mod/img/logo-zinipay.webp'); ?>">
                                    <?= __($CMSNT->site('zinipay_name') ?: 'ZiniPay'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('korapay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-korapay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/logo-korapay.webp'); ?>">
                                    <?= __('Korapay Africa'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('pocketfi_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-pocketfi'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-pocketfi.webp'); ?>">
                                    <?= __('PocketFi Nigeria'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('paymentpoint_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-paymentpoint'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url($CMSNT->site('paymentpoint_icon') ?: 'mod/img/paymentpoint.png'); ?>">
                                    <?= __($CMSNT->site('paymentpoint_name') ?: 'PaymentPoint'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('dsociopay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-dsociopay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url($CMSNT->site('dsociopay_icon') ?: 'mod/img/dsociopay.png'); ?>">
                                    <?= __($CMSNT->site('dsociopay_name') ?: 'DSocioPay'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-tmweasyapi'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>">
                                    <?= __('Tmweasyapi Thailand'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('openpix_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-openpix'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-openpix.webp'); ?>">
                                    <?= __('OpenPix'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('bakong_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-bakong'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-bakong.webp'); ?>">
                                    <?= __('Bakong Wallet Cambodia'); ?></a></li>
                        <?php endif ?>
                        <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                            <li><a href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>"><img width="20px"
                                        class="me-2" src="<?= base_url($payment_manual['icon']); ?>">
                                    <?= __($payment_manual['title']); ?></a></li>
                        <?php endforeach ?>
                    </ul>
                </li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-clock-rotate-left"></i><?= __('Lịch sử'); ?></a>
                    <ul class="dropdown-list">
                        <li><a href="<?= base_url('product-orders/'); ?>"><?= __('Lịch sử đơn hàng'); ?></a>
                        </li>
                        <li><a href="<?= base_url('client/logs'); ?>"><?= __('Nhật ký hoạt động'); ?></a></li>
                        <li><a href="<?= base_url('client/transactions'); ?>"><?= __('Biến động số dư'); ?></a>
                        </li>
                    </ul>
                </li>
                <?php if ($CMSNT->site('affiliate_status') == 1): ?>
                    <li><a class="nav-link dropdown-link" href="#"><i
                                class="fa-solid fa-money-bill-trend-up"></i><?= __('Affiliate Program'); ?></a>
                        <ul class="dropdown-list">
                            <li><a href="<?= base_url('?action=affiliates'); ?>"><?= __('Thống kê'); ?></a></li>
                            <li><a href="<?= base_url('?action=affiliate-history'); ?>"><?= __('Lịch sử'); ?></a>
                            </li>
                            <li><a href="<?= base_url('?action=affiliate-withdraw'); ?>"><?= __('Rút tiền'); ?></a>
                            </li>
                        </ul>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('blog_status') == 1): ?>
                    <li><a class="nav-link" href="<?= base_url('blogs'); ?>"><i
                                class="fa-solid fa-newspaper"></i><?= __('Blogs'); ?></a></li>
                <?php endif ?>
                <?php if ($CMSNT->site('api_status') == 1): ?>
                    <li><a class="nav-link" href="<?= base_url('document-api'); ?>"><i
                                class="fa-regular fa-file-code"></i><?= __('Tài liệu API'); ?></a></li>
                <?php endif ?>
                <?php if (isset($getUser) && $getUser['admin'] != 0): ?>
                    <li><a class="nav-link" href="<?= base_url_admin(); ?>"><i
                                class="fa-solid fa-gear"></i><?= __('Admin Panel'); ?></a></li>
                <?php endif ?>
                <?php if (isset($getUser) && $getUser['ctv'] != 0 && $CMSNT->site('ctv_status') == 1): ?>
                    <li><a class="nav-link"
                            href="<?= base_url_ctv(); ?>"><i class="fa-solid fa-gear"></i><?= __('CTV Panel'); ?></a></li>
                <?php endif ?>
                <li><a class="nav-link" href="<?= base_url('client/logout'); ?>"><i
                            class="icofont-logout"></i><?= __('Đăng xuất'); ?></a></li>
            </ul>
            <div class="nav-info-group">
                <div class="nav-info"><?= $CMSNT->site('icon_hotline'); ?>
                    <p><span><?= $CMSNT->site('hotline'); ?></span></p>
                </div>
                <div class="nav-info"><?= $CMSNT->site('icon_email'); ?>
                    <p><span><?= $CMSNT->site('email'); ?></span></p>
                </div>
            </div>
        </div>
    </aside>





    <script>
        // Change Language Function
        function changeLanguage() {
            var id = document.getElementById("changeLanguage").value;
            $.ajax({
                url: baseUrl + "ajaxs/client/update.php",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'changeLanguage',
                    id: id
                },
                success: function(respone) {
                    if (respone.status == 'success') {
                        location.reload();
                    } else {
                        cuteAlert({
                            type: "error",
                            title: "Error",
                            message: respone.msg,
                            buttonText: "Okay"
                        });
                    }
                },
                error: function() {
                    alert(html(response));
                    history.back();
                }
            });
        }

        // Change Currency Function
        function changeCurrency() {
            var id = document.getElementById("changeCurrency").value;
            $.ajax({
                url: baseUrl + "ajaxs/client/update.php",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'changeCurrency',
                    id: id
                },
                success: function(respone) {
                    if (respone.status == 'success') {
                        location.reload();
                    } else {
                        cuteAlert({
                            type: "error",
                            title: "Error",
                            message: respone.msg,
                            buttonText: "Okay"
                        });
                    }
                },
                error: function() {
                    alert(html(response));
                    history.back();
                }
            });
        }

        // Load menu categories bằng AJAX
        const menuCategoryCacheKey = 'caffemmo-menu-categories-v1';
        const menuCategoryCacheLifetime = 5 * 60 * 1000;

        function getCachedMenuCategories() {
            try {
                const cached = JSON.parse(sessionStorage.getItem(menuCategoryCacheKey));
                if (!cached || Date.now() - cached.savedAt > menuCategoryCacheLifetime) {
                    return null;
                }
                return cached.payload;
            } catch (error) {
                return null;
            }
        }

        function cacheMenuCategories(response) {
            try {
                sessionStorage.setItem(menuCategoryCacheKey, JSON.stringify({
                    savedAt: Date.now(),
                    payload: response
                }));
            } catch (error) {
                // Menu loading continues when browser storage is unavailable.
            }
        }

        $(document).ready(function() {
            // Load menu categories ngay khi trang load
            const cachedMenu = getCachedMenuCategories();
            if (cachedMenu && cachedMenu.menu_html) {
                $('#menu-categories-container').html(cachedMenu.menu_html).addClass('menu-loaded');
                if ($('#home-categories-container').length) {
                    $('.home-categories-skeleton').remove();
                    $('#home-categories-container').append(cachedMenu.home_buttons_html || '');
                }
                return;
            }
            loadMenuCategories();
        });

        function loadMenuCategories() {
            $.ajax({
                url: baseUrl + 'ajaxs/client/load_menu.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    cacheMenuCategories(response);
                    // Load menu dropdown (nav.php)
                    $('#menu-categories-container').html(response.menu_html);
                    $('#menu-categories-container').addClass('menu-loaded');

                    // Load category buttons (home.php) - nếu container tồn tại
                    if ($('#home-categories-container').length) {
                        // Xóa skeleton loading
                        $('.home-categories-skeleton').remove();
                        // Giữ lại nút "Tất cả sản phẩm", chỉ append categories mới
                        $('#home-categories-container').append(response.home_buttons_html);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading menu categories:', error);
                    // Giữ skeleton loading nếu có lỗi
                }
            });
        }
    </script>
