<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../models/is_user.php';
require_once __DIR__ . '/../../libs/locket-gold.php';

$historyReady = locket_gold_ensure_orders_table();
$historyLimit = validate_int($_GET['limit'] ?? 10, 5, 50) ?: 10;
$historyPage = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$historyFrom = ($historyPage - 1) * $historyLimit;
$historyTotal = 0;
$historyOrders = [];

if ($historyReady) {
    $historyTotalRow = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS total FROM `locket_gold_orders` WHERE `user_id` = ?',
        [(int) $getUser['id']]
    );
    $historyTotal = (int) ($historyTotalRow['total'] ?? 0);
    $historyOrders = $CMSNT->get_list_safe(
        'SELECT `id`, `order_code`, `package_name`, `account_count`, `usernames`, `charged_amount`, `status`, `admin_note`, `created_at`, `updated_at`
        FROM `locket_gold_orders`
        WHERE `user_id` = ?
        ORDER BY `id` DESC
        LIMIT ?, ?',
        [(int) $getUser['id'], $historyFrom, $historyLimit]
    ) ?: [];
}

$historyBaseUrl = base_url('client/locket-gold-history');
$historyQuery = $historyBaseUrl . '?limit=' . $historyLimit . '&';
$historyPagination = pagination_client($historyQuery, $historyFrom, $historyTotal, $historyLimit);

$body = [
    'title' => __('Lịch sử Locket Gold') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Theo dõi các đơn Locket Gold đã gửi.'),
    'keyword' => 'lịch sử locket gold, đơn locket'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/locket-gold.css?v=1') . '">';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="locket-page locket-history-page">
    <section class="locket-history-hero" aria-labelledby="locket-history-title">
        <div>
            <span class="locket-eyebrow"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Caffemmo Service</span>
            <h1 id="locket-history-title"><?= __('Lịch sử Locket Gold'); ?></h1>
            <p><?= __('Theo dõi trạng thái xử lý và thông tin các đơn đã gửi.'); ?></p>
        </div>
        <a class="locket-history-new-link" href="<?= htmlspecialchars(base_url('client/locket-gold'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?= __('Tạo đơn mới'); ?></a>
    </section>

    <section class="locket-panel locket-history-panel" aria-labelledby="locket-history-list-title">
        <div class="locket-history-toolbar">
            <div>
                <span class="locket-panel-kicker"><?= __('Theo dõi dịch vụ'); ?></span>
                <h2 id="locket-history-list-title"><?= __('Các đơn đã gửi'); ?></h2>
            </div>
            <span class="locket-history-summary"><strong><?= format_cash($historyTotal); ?></strong> <?= __('đơn'); ?></span>
        </div>

        <?php if (!$historyReady): ?>
        <div class="locket-history-empty">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <strong><?= __('Chưa thể tải lịch sử'); ?></strong>
            <span><?= __('Vui lòng thử lại sau ít phút.'); ?></span>
        </div>
        <?php elseif (empty($historyOrders)): ?>
        <div class="locket-history-empty">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <strong><?= __('Chưa có đơn Locket Gold'); ?></strong>
            <span><?= __('Đơn mới sẽ xuất hiện tại đây để bạn theo dõi trạng thái xử lý.'); ?></span>
        </div>
        <?php else: ?>
        <div class="locket-history-table-wrap">
            <table class="locket-history-table">
                <thead>
                    <tr>
                        <th><?= __('Đơn hàng'); ?></th>
                        <th><?= __('Username'); ?></th>
                        <th><?= __('Chi phí'); ?></th>
                        <th><?= __('Trạng thái'); ?></th>
                        <th><?= __('Thời gian'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyOrders as $historyOrder): ?>
                    <tr>
                        <td>
                            <span class="locket-history-service">
                                <strong><?= htmlspecialchars((string) $historyOrder['package_name'], ENT_QUOTES, 'UTF-8'); ?> <small>(<?= (int) $historyOrder['account_count']; ?> <?= __('tài khoản'); ?>)</small></strong>
                                <small>#<?= htmlspecialchars((string) $historyOrder['order_code'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </span>
                        </td>
                        <td><span class="locket-history-usernames"><?= htmlspecialchars((string) $historyOrder['usernames'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="locket-history-charge"><?= format_currency((float) $historyOrder['charged_amount']); ?></span></td>
                        <td>
                            <span class="locket-history-status <?= locket_gold_status_class($historyOrder['status']); ?>"><?= __(locket_gold_status_label($historyOrder['status'])); ?></span>
                            <?php if (trim((string) ($historyOrder['admin_note'] ?? '')) !== ''): ?>
                            <small class="locket-history-note"><?= htmlspecialchars((string) $historyOrder['admin_note'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="locket-history-time"><?= htmlspecialchars((string) $historyOrder['created_at'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="locket-history-pagination"><?= $historyPagination; ?></div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
