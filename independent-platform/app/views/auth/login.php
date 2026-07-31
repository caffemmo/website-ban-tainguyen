<section class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow"><i data-lucide="lock-keyhole"></i> CAFFEMMO WORKSPACE</span>
        <h1>Đăng nhập</h1><p>Truy cập ví, đơn hàng và các dịch vụ của bạn.</p>
        <form method="post" action="<?= e(url('/login')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>Email<input class="input" type="email" name="email" autocomplete="email" required></label>
            <label>Mật khẩu<input class="input" type="password" name="password" autocomplete="current-password" minlength="8" required></label>
            <button class="button button-primary button-full" type="submit">Đăng nhập <i data-lucide="arrow-right"></i></button>
        </form>
        <p class="form-note">Chưa có tài khoản? <a href="<?= e(url('/register')) ?>">Tạo tài khoản</a></p>
    </div>
</section>
