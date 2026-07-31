<div class="page-intro">
    <div>
        <span class="eyebrow"><i data-lucide="refresh-cw"></i> PROXY WORKSPACE</span>
        <h1>Gia hạn Proxy</h1>
        <p>Chọn các proxy cần gia hạn, xem thời hạn và xác nhận bằng số dư ví.</p>
    </div>
    <a class="button button-secondary" href="<?= e(url('/app/proxy/mine')) ?>"><i data-lucide="server"></i> Proxy của tôi</a>
</div>

<form class="renew-layout" data-renew-form>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <span class="step-number">01</span>
                <div class="heading-copy">
                    <span class="eyebrow">TÀI NGUYÊN</span>
                    <h2>Chọn proxy</h2>
                    <p>Chỉ proxy thuộc tài khoản hiện tại mới hiển thị.</p>
                </div>
            </div>
            <button class="icon-button" type="button" data-renew-select-all aria-label="Chọn tất cả proxy"><i data-lucide="check-check"></i></button>
        </div>

        <?php if ($proxies !== []): ?>
            <div class="renew-list">
                <?php foreach ($proxies as $proxy): $days = days_remaining($proxy['expires_at']); ?>
                    <label class="renew-row">
                        <input type="checkbox" name="proxy_order_ids[]" value="<?= e($proxy['id']) ?>" data-renew-proxy>
                        <span class="country-mark"><?= e(strtoupper($proxy['country_code'])) ?></span>
                        <span>
                            <strong><?= e($proxy['proxy_type']) ?> · <?= e($proxy['country_code']) ?></strong>
                            <small><?= e(format_date($proxy['expires_at'])) ?> · <?= $days === null ? 'Chưa cập nhật' : ($days === 0 ? 'Đã hết hạn' : 'Còn ' . $days . ' ngày') ?></small>
                        </span>
                        <em class="status-badge status-<?= e($proxy['status']) ?>"><?= e($proxy['status']) ?></em>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-panel">
                <i data-lucide="refresh-cw"></i>
                <h2>Chưa có proxy để gia hạn</h2>
                <p>Hãy mua proxy trước, sau đó các proxy sắp hết hạn sẽ xuất hiện tại đây.</p>
                <a class="button button-primary" href="<?= e(url('/app/proxy')) ?>">Mua proxy</a>
            </div>
        <?php endif; ?>
    </section>

    <aside class="panel summary-panel">
        <div class="panel-heading">
            <div>
                <span class="step-number">02</span>
                <div class="heading-copy">
                    <span class="eyebrow">THIẾT LẬP</span>
                    <h2>Thiết lập gia hạn</h2>
                    <p>Báo giá sẽ cập nhật theo lựa chọn.</p>
                </div>
            </div>
        </div>
        <label>Gia hạn thêm
            <select class="input" name="rent_period_days" data-renew-days>
                <option value="7">7 ngày</option>
                <option value="30">30 ngày</option>
                <option value="90">90 ngày</option>
            </select>
        </label>
        <div class="summary-empty" data-renew-summary>
            <i data-lucide="refresh-cw"></i>
            <strong>Chưa chọn proxy</strong>
            <span>Chọn ít nhất một proxy để tiếp tục.</span>
        </div>
        <p class="form-result" data-renew-result role="status"></p>
        <button class="button button-primary button-full" type="submit" data-renew-submit disabled><i data-lucide="lock"></i> Gia hạn ngay</button>
    </aside>
</form>
