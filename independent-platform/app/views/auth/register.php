<section class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow"><i data-lucide="user-plus"></i> BẮT ĐẦU NHANH</span>
        <h1>Tạo tài khoản</h1><p>Tài khoản mới được tạo trong database của platform độc lập.</p>
        <form method="post" action="<?= e(url('/register')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <label>Họ tên<input class="input" type="text" name="name" autocomplete="name" required></label>
            <label>Email<input class="input" type="email" name="email" autocomplete="email" required></label>
            <label>Mật khẩu<input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required></label>
            <button class="button button-primary button-full" type="submit">Tạo tài khoản <i data-lucide="arrow-right"></i></button>
        </form>
        <p class="form-note">Đã có tài khoản? <a href="<?= e(url('/login')) ?>">Đăng nhập</a></p>
    </div>
</section>
