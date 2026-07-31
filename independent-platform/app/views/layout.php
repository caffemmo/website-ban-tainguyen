<?php $user = $user ?? current_user(); ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Nền tảng vận hành tài nguyên số, proxy, ví và dịch vụ của Caffemmo.">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? app_name()) . ' | ' . app_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="sidebar-brand">
            <a href="<?= e(url('/')) ?>" class="brand-lockup">
                <span class="brand-symbol" aria-hidden="true">CM</span>
                <span><strong>CAFFEMMO</strong><small>WORKSPACE</small></span>
            </a>
            <button class="icon-button sidebar-close" type="button" aria-label="Đóng menu" data-sidebar-toggle><i data-lucide="x"></i></button>
        </div>
        <?php if ($user): ?>
            <div class="sidebar-user">
                <span class="avatar"><?= e(strtoupper(substr((string) $user['name'], 0, 1))) ?></span>
                <span><strong><?= e($user['name']) ?></strong><small><?= e(format_money($user['balance'])) ?></small></span>
            </div>
        <?php endif; ?>
        <nav class="sidebar-nav" aria-label="Điều hướng workspace">
            <p class="nav-label">Tổng quan</p>
            <a class="nav-link<?= nav_is_active('/app') ?>" href="<?= e(url('/app')) ?>"><i data-lucide="layout-dashboard"></i><span>Bảng điều khiển</span></a>
            <a class="nav-link<?= nav_is_active('/app/catalog') ?>" href="<?= e(url('/app/catalog')) ?>"><i data-lucide="store"></i><span>Tất cả sản phẩm</span></a>
            <p class="nav-label">Dịch vụ</p>
            <a class="nav-link<?= nav_is_active('/app/proxy') ?>" href="<?= e(url('/app/proxy')) ?>"><i data-lucide="network"></i><span>Mua Proxy</span><em>Mới</em></a>
            <a class="nav-link<?= nav_is_active('/app/proxy/mine') ?>" href="<?= e(url('/app/proxy/mine')) ?>"><i data-lucide="server"></i><span>Proxy của tôi</span></a>
            <a class="nav-link<?= nav_is_active('/app/proxy/renew') ?>" href="<?= e(url('/app/proxy/renew')) ?>"><i data-lucide="refresh-cw"></i><span>Gia hạn Proxy</span></a>
            <a class="nav-link<?= nav_is_active('/app/social') ?>" href="<?= e(url('/app/social')) ?>"><i data-lucide="badge-check"></i><span>Up tích xanh</span></a>
            <p class="nav-label">Tài chính</p>
            <a class="nav-link<?= nav_is_active('/app/wallet') ?>" href="<?= e(url('/app/wallet')) ?>"><i data-lucide="wallet"></i><span>Ví & nạp tiền</span></a>
            <a class="nav-link<?= nav_is_active('/app/orders') ?>" href="<?= e(url('/app/orders')) ?>"><i data-lucide="receipt-text"></i><span>Đơn hàng</span></a>
            <?php if ($user && in_array($user['role'], ['admin', 'staff'], true)): ?><p class="nav-label">Quản trị</p><a class="nav-link<?= nav_is_active('/admin') ?>" href="<?= e(url('/admin')) ?>"><i data-lucide="shield-check"></i><span>Admin workspace</span></a><?php endif; ?>
            <p class="nav-label">Hỗ trợ</p>
            <a class="nav-link" href="#support"><i data-lucide="headphones"></i><span>Liên hệ hỗ trợ</span></a>
        </nav>
        <?php if ($user): ?>
            <a class="sidebar-logout" href="<?= e(url('/logout')) ?>"><i data-lucide="log-out"></i> Đăng xuất</a>
        <?php else: ?>
            <a class="sidebar-logout" href="<?= e(url('/login')) ?>"><i data-lucide="log-in"></i> Đăng nhập</a>
        <?php endif; ?>
    </aside>

    <div class="page-area">
        <header class="topbar">
            <button class="icon-button menu-toggle" type="button" aria-label="Mở menu" data-sidebar-toggle><i data-lucide="menu"></i></button>
            <a class="mobile-brand" href="<?= e(url('/')) ?>"><strong>CAFFEMMO</strong><span>WORKSPACE</span></a>
            <div class="topbar-actions">
                <a class="topbar-link" href="<?= e(url('/app/wallet')) ?>"><i data-lucide="wallet"></i><span><?= e($user ? format_money($user['balance']) : 'Đăng nhập') ?></span></a>
                <?php if ($user): ?><a class="icon-button" href="<?= e(url('/logout')) ?>" aria-label="Đăng xuất"><i data-lucide="log-out"></i></a><?php endif; ?>
            </div>
        </header>
        <main class="main-content">
            <?php if ($message = flash('success')): ?><div class="toast toast-success" role="status"><i data-lucide="check-circle-2"></i><?= e($message) ?></div><?php endif; ?>
            <?php if ($message = flash('error')): ?><div class="toast toast-error" role="alert"><i data-lucide="alert-circle"></i><?= e($message) ?></div><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<script>window.CAFFEMMO_APP_URL = <?= json_encode(rtrim(url('/'), '/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= e(url('/assets/app.js')) ?>" defer></script>
</body>
</html>
