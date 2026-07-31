<div class="page-intro">
    <div>
        <span class="eyebrow"><i data-lucide="settings-2"></i> QUẢN TRỊ</span>
        <h1>Cài đặt hệ thống</h1>
        <p>Điều chỉnh vận hành và kiểm tra trạng thái kết nối mà không đưa secret provider ra giao diện.</p>
    </div>
    <a class="button button-secondary" href="<?= e(url('/admin')) ?>"><i data-lucide="arrow-left"></i> Dashboard</a>
</div>

<div class="admin-grid">
    <form class="panel form-stack" method="post" action="<?= e(url('/admin/settings')) ?>">
        <?= csrf_field() ?>
        <div class="panel-heading"><div><span class="eyebrow">VẬN HÀNH</span><h2>Cấu hình bán hàng</h2><p>Giá được dùng để báo giá và trừ ví khi mua proxy.</p></div></div>
        <label class="checkbox-field"><input type="checkbox" name="maintenance_mode" value="1" <?= $maintenance ? 'checked' : '' ?>><span><strong>Chế độ bảo trì</strong><small>Tạm dừng luồng khách hàng trong khi bạn bảo trì hệ thống.</small></span></label>
        <label>Giá proxy mặc định / ngày (VNĐ)<input class="input" type="number" min="0" step="100" name="proxy_daily_price" value="<?= e($proxyDailyPrice) ?>"></label>
        <button class="button button-primary" type="submit"><i data-lucide="save"></i> Lưu cài đặt</button>
    </form>

    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">KẾT NỐI SERVER</span><h2>Trạng thái provider</h2><p>API key chỉ được đọc từ environment của server.</p></div><i data-lucide="shield-check"></i></div>
        <div class="provider-status-list">
            <div><span>YouProxy proxy</span><strong class="status-badge <?= $providerReady ? 'status-active' : 'status-pending' ?>"><?= $providerReady ? 'Đã cấu hình' : 'Chưa cấu hình' ?></strong></div>
            <div><span>Dịch vụ social</span><strong class="status-badge <?= $socialReady ? 'status-active' : 'status-pending' ?>"><?= $socialReady ? 'Đã cấu hình' : 'Chưa cấu hình' ?></strong></div>
            <div><span>Ngân hàng / nạp tiền</span><strong class="status-badge <?= $bankReady ? 'status-active' : 'status-pending' ?>"><?= $bankReady ? 'Đã cấu hình' : 'Chưa cấu hình' ?></strong></div>
        </div>
        <div class="notice notice-info"><i data-lucide="lock-keyhole"></i><span>Không nhập API key vào form này. Dùng `.env` trên server, giới hạn quyền file và không commit file `.env`.</span></div>
    </section>
</div>
