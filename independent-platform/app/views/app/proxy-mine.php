<div class="page-intro">
    <div>
        <span class="eyebrow"><i data-lucide="server"></i> PROXY WORKSPACE</span>
        <h1>Proxy của tôi</h1>
        <p>Quản lý proxy đã mua, thời hạn, cấu hình kết nối và gia hạn.</p>
    </div>
    <a class="button button-primary" href="<?= e(url('/app/proxy')) ?>"><i data-lucide="plus"></i> Mua proxy</a>
</div>

<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">TÀI NGUYÊN ĐÃ MUA</span><h2>Danh sách proxy</h2></div>
        <a class="button button-small button-secondary" href="<?= e(url('/app/proxy/renew')) ?>"><i data-lucide="refresh-cw"></i> Gia hạn</a>
    </div>
    <?php if ($proxies !== []): ?>
        <div class="proxy-list">
            <?php foreach ($proxies as $proxy): $days = days_remaining($proxy['expires_at']); $connections = proxy_connection_details($proxy); ?>
                <article class="proxy-card">
                    <div class="proxy-card-top">
                        <div><span class="country-mark"><?= e(strtoupper($proxy['country_code'])) ?></span><span><strong><?= e($proxy['proxy_type']) ?> · <?= e($proxy['country_code']) ?></strong><small><?= e($proxy['provider_order_number'] ?: ($proxy['provider_order_id'] ?: 'Đang xử lý')) ?></small></span></div>
                        <span class="status-badge status-<?= e($proxy['status']) ?>"><?= e($proxy['status']) ?></span>
                    </div>
                    <?php if ($connections !== []): ?>
                        <div class="proxy-connection-list">
                            <?php foreach ($connections as $connection): ?>
                                <div class="proxy-connection">
                                    <code><?= e($connection['format']) ?></code>
                                    <button class="button button-small button-secondary" type="button" data-copy="<?= e($connection['format']) ?>"><i data-lucide="copy"></i> Sao chép</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="proxy-pending-note"><i data-lucide="info"></i><span>Provider đã ghi nhận đơn. Thông tin kết nối sẽ xuất hiện sau khi provider cấp proxy.</span></div>
                    <?php endif; ?>
                    <div class="proxy-meta-grid">
                        <div><span>Hạn dùng</span><strong><?= e(format_date($proxy['expires_at'])) ?></strong><small><?= $days === null ? 'Chưa cập nhật' : ($days === 0 ? 'Đã hết hạn' : 'Còn ' . $days . ' ngày') ?></small></div>
                        <div><span>Số lượng</span><strong><?= e($proxy['quantity']) ?></strong><small><?= e($proxy['auth_mode']) ?></small></div>
                        <div><span>Định dạng</span><strong>IP:PORT:USER:PASS</strong><small>HTTPS · SOCKS5 tùy gói</small></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-panel"><i data-lucide="server"></i><h2>Chưa có proxy</h2><p>Proxy sau khi mua thành công sẽ được lưu tại đây cùng thời hạn và trạng thái.</p><a class="button button-primary" href="<?= e(url('/app/proxy')) ?>">Mua proxy</a></div>
    <?php endif; ?>
</section>
