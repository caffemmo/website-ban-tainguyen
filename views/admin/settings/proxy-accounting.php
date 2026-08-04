<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/proxy-accounting.php';

$accountingMonth = isset($_GET['accounting_month']) ? (string) $_GET['accounting_month'] : date('Y-m');
$proxyAccounting = proxy_accounting_report($accountingMonth);
if (empty($proxyAccounting['success'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars((string) ($proxyAccounting['message'] ?? 'Không thể tải báo cáo kế toán proxy.'), ENT_QUOTES, 'UTF-8') . '</div>';
    return;
}

$selected = $proxyAccounting['selected'];
$all = $proxyAccounting['all'];
$stock = $proxyAccounting['stock'];
$selectedMonthLabel = date('m/Y', strtotime($proxyAccounting['period']['start']));

function proxy_accounting_admin_money($amount)
{
    return format_currency((float) $amount);
}

function proxy_accounting_admin_class($amount)
{
    return (float) $amount >= 0 ? 'is-positive' : 'is-negative';
}

function proxy_accounting_admin_month($month)
{
    return date('m/Y', strtotime($month . '-01'));
}
?>

<style>
    .proxy-accounting-head { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; margin-bottom:22px; }
    .proxy-accounting-head h4 { margin:0 0 5px; font-weight:700; color:#173b63; }
    .proxy-accounting-head p { margin:0; color:#75869a; font-size:12px; }
    .proxy-accounting-filter { display:flex; align-items:flex-end; gap:8px; }
    .proxy-accounting-filter label { margin:0; color:#5e7186; font-size:11px; font-weight:700; }
    .proxy-accounting-filter input { min-width:150px; }
    .proxy-accounting-kpis { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:18px; }
    .proxy-accounting-kpi { min-height:110px; padding:16px; border:1px solid #e1eaf3; border-radius:10px; background:#fbfdff; }
    .proxy-accounting-kpi span { display:block; color:#718399; font-size:11px; font-weight:700; text-transform:uppercase; }
    .proxy-accounting-kpi strong { display:block; margin-top:8px; color:#173b63; font-size:21px; line-height:1.15; }
    .proxy-accounting-kpi small { display:block; margin-top:7px; color:#8393a5; font-size:11px; }
    .proxy-accounting-kpi.is-positive strong { color:#147a4d; }
    .proxy-accounting-kpi.is-negative strong { color:#b64242; }
    .proxy-accounting-section { border:1px solid #e1eaf3; border-radius:10px; background:#fff; margin-bottom:18px; overflow:hidden; }
    .proxy-accounting-section-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #edf2f7; }
    .proxy-accounting-section-head h5 { margin:0; color:#173b63; font-size:15px; font-weight:700; }
    .proxy-accounting-section-head small { color:#7a8b9e; }
    .proxy-accounting-table { margin:0; font-size:12px; }
    .proxy-accounting-table th { color:#718399; font-size:10px; text-transform:uppercase; white-space:nowrap; }
    .proxy-accounting-table td { vertical-align:middle; }
    .proxy-accounting-table .money { text-align:right; white-space:nowrap; }
    .proxy-accounting-positive { color:#147a4d; font-weight:700; }
    .proxy-accounting-negative { color:#b64242; font-weight:700; }
    .proxy-accounting-note { display:flex; gap:8px; padding:12px 14px; color:#5f7890; background:#f4f9fd; border:1px solid #dcecf8; border-radius:8px; font-size:12px; line-height:1.5; }
    .proxy-accounting-note i { margin-top:2px; color:#3a82c4; }
    @media (max-width: 767.98px) {
        .proxy-accounting-head { align-items:stretch; flex-direction:column; }
        .proxy-accounting-filter { align-items:stretch; flex-direction:column; }
        .proxy-accounting-filter input, .proxy-accounting-filter button { width:100%; }
        .proxy-accounting-kpis { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 480px) {
        .proxy-accounting-kpis { grid-template-columns:1fr; }
    }
</style>

<div class="tab-pane text-muted show active" id="proxy-accounting-settings" role="tabpanel">
    <div class="proxy-accounting-head">
        <div>
            <h4><i class="fa-solid fa-calculator me-2" aria-hidden="true"></i><?= __('Kế toán Proxy'); ?></h4>
            <p><?= __('Tự động tổng hợp tiền nhập, tiền bán, giá vốn, lãi/lỗ và vốn đang nằm trong kho.'); ?></p>
        </div>
        <form class="proxy-accounting-filter" method="GET">
            <input type="hidden" name="module" value="settings">
            <input type="hidden" name="tab" value="proxy-accounting">
            <label for="accounting_month"><?= __('Tháng báo cáo'); ?></label>
            <input class="form-control form-control-sm" type="month" id="accounting_month" name="accounting_month" value="<?= htmlspecialchars((string) $proxyAccounting['period']['month'], ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1" aria-hidden="true"></i><?= __('Xem báo cáo'); ?></button>
        </form>
    </div>

    <div class="proxy-accounting-kpis">
        <div class="proxy-accounting-kpi"><span><?= __('Tiền nhập tháng'); ?></span><strong><?= proxy_accounting_admin_money($selected['input_cost']); ?></strong><small><?= (int) $selected['batch_count']; ?> lô + đơn mua trực tiếp</small></div>
        <div class="proxy-accounting-kpi"><span><?= __('Tiền bán ra tháng'); ?></span><strong><?= proxy_accounting_admin_money($selected['revenue']); ?></strong><small><?= (int) $selected['orders']; ?> đơn / <?= (int) $selected['quantity']; ?> proxy</small></div>
        <div class="proxy-accounting-kpi <?= proxy_accounting_admin_class($selected['profit']); ?>"><span><?= __('Lãi/lỗ đã thực hiện'); ?></span><strong><?= proxy_accounting_admin_money($selected['profit']); ?></strong><small>Doanh thu trừ giá vốn hàng đã bán</small></div>
        <div class="proxy-accounting-kpi <?= proxy_accounting_admin_class($selected['cash_difference']); ?>"><span><?= __('Tiền chênh lệch'); ?></span><strong><?= proxy_accounting_admin_money($selected['cash_difference']); ?></strong><small>Tiền bán ra trừ tiền nhập trong tháng</small></div>
        <div class="proxy-accounting-kpi"><span><?= __('Vốn còn trong kho'); ?></span><strong><?= proxy_accounting_admin_money($stock['cost']); ?></strong><small><?= (int) $stock['quantity']; ?> IPv6 available/reserved</small></div>
        <div class="proxy-accounting-kpi <?= proxy_accounting_admin_class($stock['profit']); ?>"><span><?= __('Lãi dự kiến tồn kho'); ?></span><strong><?= proxy_accounting_admin_money($stock['profit']); ?></strong><small>Nếu bán hết theo giá hiện tại</small></div>
    </div>

    <div class="proxy-accounting-note mb-4">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span><?= __('Báo cáo quy đổi giá nhà cung cấp theo tỷ giá USD hiện tại. Do dữ liệu lô cũ chưa lưu tỷ giá tại thời điểm mua, phần lô cũ là số liệu ước tính; lô mới vẫn được theo dõi theo giá nhập và giá bán đã lưu.'); ?></span>
    </div>

    <section class="proxy-accounting-section">
        <div class="proxy-accounting-section-head">
            <div><h5><?= __('Báo cáo tháng ' . $selectedMonthLabel); ?></h5><small><?= __('Lãi gộp chỉ tính proxy đã bán, không tính toàn bộ lô vừa nhập.'); ?></small></div>
            <span class="badge text-bg-light border"><?= __('Tỷ giá'); ?>: <?= proxy_accounting_admin_money($proxyAccounting['usd_rate']); ?>/USD</span>
        </div>
        <div class="table-responsive">
            <table class="table proxy-accounting-table">
                <thead><tr><th><?= __('Chỉ tiêu'); ?></th><th class="money"><?= __('Số tiền'); ?></th><th><?= __('Ý nghĩa'); ?></th></tr></thead>
                <tbody>
                    <tr><td><?= __('Tiền nhập'); ?></td><td class="money"><?= proxy_accounting_admin_money($selected['input_cost']); ?></td><td><?= __('Chi phí mua lô và mua trực tiếp trong tháng'); ?></td></tr>
                    <tr><td><?= __('Tiền bán ra'); ?></td><td class="money"><?= proxy_accounting_admin_money($selected['revenue']); ?></td><td><?= __('Doanh thu đơn proxy không hoàn tiền'); ?></td></tr>
                    <tr><td><?= __('Giá vốn đã bán'); ?></td><td class="money"><?= proxy_accounting_admin_money($selected['cost_of_goods_sold']); ?></td><td><?= __('Chi phí của proxy thực sự đã cấp cho khách'); ?></td></tr>
                    <tr><td><strong><?= __('Lãi/lỗ'); ?></strong></td><td class="money <?= $selected['profit'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><strong><?= proxy_accounting_admin_money($selected['profit']); ?></strong></td><td><?= __('Lãi gộp, chưa trừ chi phí vận hành khác'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proxy-accounting-section">
        <div class="proxy-accounting-section-head"><div><h5><?= __('12 tháng gần nhất'); ?></h5><small><?= __('Tự động cập nhật theo đơn và lô đã ghi nhận.'); ?></small></div></div>
        <div class="table-responsive">
            <table class="table proxy-accounting-table">
                <thead><tr><th><?= __('Tháng'); ?></th><th class="money"><?= __('Tiền nhập'); ?></th><th class="money"><?= __('Bán ra'); ?></th><th class="money"><?= __('Giá vốn'); ?></th><th class="money"><?= __('Lãi/lỗ'); ?></th><th class="money"><?= __('Chênh lệch tiền'); ?></th><th><?= __('Đơn'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($proxyAccounting['monthly'] as $row): ?>
                    <tr>
                        <td><strong><?= proxy_accounting_admin_month($row['month']); ?></strong></td>
                        <td class="money"><?= proxy_accounting_admin_money($row['input_cost']); ?></td>
                        <td class="money"><?= proxy_accounting_admin_money($row['revenue']); ?></td>
                        <td class="money"><?= proxy_accounting_admin_money($row['cost_of_goods_sold']); ?></td>
                        <td class="money <?= $row['profit'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><?= proxy_accounting_admin_money($row['profit']); ?></td>
                        <td class="money <?= $row['cash_difference'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><?= proxy_accounting_admin_money($row['cash_difference']); ?></td>
                        <td><?= (int) $row['orders']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proxy-accounting-section">
        <div class="proxy-accounting-section-head"><div><h5><?= __('Tồn kho IPv6 theo quốc gia'); ?></h5><small><?= __('Giá trị bán ra và lãi dự kiến nếu bán hết số còn lại.'); ?></small></div></div>
        <div class="table-responsive">
            <table class="table proxy-accounting-table">
                <thead><tr><th><?= __('Quốc gia'); ?></th><th><?= __('Số lượng'); ?></th><th class="money"><?= __('Vốn nhập'); ?></th><th class="money"><?= __('Có thể bán'); ?></th><th class="money"><?= __('Lãi dự kiến'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($stock['by_country'] as $country => $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) $country, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?= (int) $row['quantity']; ?></td>
                        <td class="money"><?= proxy_accounting_admin_money($row['cost']); ?></td>
                        <td class="money"><?= proxy_accounting_admin_money($row['revenue']); ?></td>
                        <td class="money <?= $row['profit'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><?= proxy_accounting_admin_money($row['profit']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($stock['by_country'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><?= __('Hiện chưa có IPv6 còn hàng trong kho.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proxy-accounting-section">
        <div class="proxy-accounting-section-head"><div><h5><?= __('Lũy kế toàn bộ'); ?></h5><small><?= __('Tính từ dữ liệu proxy đã ghi nhận trong hệ thống.'); ?></small></div></div>
        <div class="table-responsive">
            <table class="table proxy-accounting-table">
                <thead><tr><th><?= __('Chỉ tiêu'); ?></th><th class="money"><?= __('Lũy kế'); ?></th></tr></thead>
                <tbody>
                    <tr><td><?= __('Tổng tiền nhập'); ?></td><td class="money"><?= proxy_accounting_admin_money($all['input_cost']); ?></td></tr>
                    <tr><td><?= __('Tổng tiền bán ra'); ?></td><td class="money"><?= proxy_accounting_admin_money($all['revenue']); ?></td></tr>
                    <tr><td><?= __('Tổng lãi/lỗ đã thực hiện'); ?></td><td class="money <?= $all['profit'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><?= proxy_accounting_admin_money($all['profit']); ?></td></tr>
                    <tr><td><?= __('Tổng chênh lệch tiền'); ?></td><td class="money <?= $all['cash_difference'] >= 0 ? 'proxy-accounting-positive' : 'proxy-accounting-negative'; ?>"><?= proxy_accounting_admin_money($all['cash_difference']); ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
