<section class="public-hero">
    <div class="hero-copy">
        <span class="eyebrow"><i data-lucide="shield-check"></i> WORKSPACE ĐỘC LẬP</span>
        <h1>Vận hành tài nguyên số gọn, rõ và ổn định.</h1>
        <p>Một không gian duy nhất để quản lý sản phẩm, proxy, ví, đơn hàng và dịch vụ cộng đồng. Dữ liệu và khóa tích hợp nằm phía server của bạn.</p>
        <div class="button-row">
            <?php if ($user): ?><a class="button button-primary" href="<?= e(url('/app')) ?>">Vào workspace <i data-lucide="arrow-right"></i></a><?php else: ?><a class="button button-primary" href="<?= e(url('/register')) ?>">Tạo tài khoản <i data-lucide="arrow-right"></i></a><a class="button button-ghost" href="<?= e(url('/login')) ?>">Đăng nhập</a><?php endif; ?>
        </div>
    </div>
    <div class="hero-console" aria-label="Tổng quan hệ thống">
        <div class="console-top"><span><i data-lucide="activity"></i> Hệ thống vận hành</span><b><i></i> Ổn định</b></div>
        <div class="console-metric"><strong>24/7</strong><span>theo dõi đơn hàng, ví và dịch vụ</span></div>
        <div class="console-grid"><div><span>Kho tài nguyên</span><strong>Đang sẵn sàng</strong></div><div><span>Proxy workspace</span><strong>Live pricing</strong></div><div><span>Audit log</span><strong>Đã bật</strong></div><div><span>API provider</span><strong>Server-side</strong></div></div>
    </div>
</section>
<section class="section-block">
    <div class="section-heading"><span class="eyebrow">Bám sát nghiệp vụ cũ</span><h2>Những phần quan trọng nằm trong cùng một luồng.</h2><p>Thiết kế lại để khách hàng không phải đi qua nhiều màn hình rời rạc, còn admin có thể kiểm soát dữ liệu và lỗi ở một nơi.</p></div>
    <div class="feature-grid">
        <article class="feature-card"><span class="feature-icon tone-blue"><i data-lucide="boxes"></i></span><h3>Kho & sản phẩm</h3><p>Quản lý danh mục, tồn kho, giá, đơn và dữ liệu giao ngay.</p></article>
        <article class="feature-card"><span class="feature-icon tone-teal"><i data-lucide="network"></i></span><h3>Proxy workspace</h3><p>Mua, xem proxy, sao chép định dạng và gia hạn theo vòng đời.</p></article>
        <article class="feature-card"><span class="feature-icon tone-violet"><i data-lucide="wallet-cards"></i></span><h3>Ví & nạp tiền</h3><p>Giao dịch idempotent, đối soát provider và lịch sử số dư rõ ràng.</p></article>
        <article class="feature-card"><span class="feature-icon tone-amber"><i data-lucide="badge-check"></i></span><h3>Dịch vụ cộng đồng</h3><p>Adapter server-side cho các dịch vụ hợp lệ, không lộ API key.</p></article>
    </div>
</section>
