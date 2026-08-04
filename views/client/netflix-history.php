<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../models/is_user.php';
require_once __DIR__ . '/../../libs/netflix.php';

$historyReady = netflix_ensure_orders_table();
$historyLimit = validate_int($_GET['limit'] ?? 10, 5, 50) ?: 10;
$historyPage = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$historyFrom = ($historyPage - 1) * $historyLimit;
$historyTotal = 0;
$historyOrders = [];

if ($historyReady) {
    $historyTotalRow = $CMSNT->get_row_safe(
        'SELECT COUNT(*) AS total FROM `netflix_orders` WHERE `user_id` = ?',
        [(int) $getUser['id']]
    );
    $historyTotal = (int) ($historyTotalRow['total'] ?? 0);
    $historyOrders = $CMSNT->get_list_safe(
        'SELECT `id`, `log_id`, `charged_amount`, `created_at`
        FROM `netflix_orders`
        WHERE `user_id` = ?
        ORDER BY `id` DESC
        LIMIT ?, ?',
        [(int) $getUser['id'], $historyFrom, $historyLimit]
    ) ?: [];
}

$historyBaseUrl = base_url('client/netflix-history');
$historyQuery = $historyBaseUrl . '?limit=' . $historyLimit . '&';
$historyPagination = pagination_client($historyQuery, $historyFrom, $historyTotal, $historyLimit);

$body = [
    'title' => __('Lịch sử Netflix') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Xem lại các lần lấy link Netflix và tạo lại link khi cần.'),
    'keyword' => 'lịch sử netflix, tạo lại link netflix'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/netflix.css?v=3') . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/netflix-history.js?v=1') . '"></script>';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main
    class="netflix-page netflix-history-page"
    data-netflix-history
    data-endpoint="<?= htmlspecialchars(BASE_URL('ajaxs/client/netflix.php'), ENT_QUOTES, 'UTF-8'); ?>"
    data-token="<?= htmlspecialchars((string) ($getUser['token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
>
    <section class="netflix-history-hero" aria-labelledby="netflix-history-title">
        <div>
            <span class="netflix-eyebrow"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Caffemmo Streaming</span>
            <h1 id="netflix-history-title"><?= __('Lịch sử Netflix'); ?></h1>
            <p><?= __('Quản lý các lần đã lấy link và tạo lại link khi link cũ hết hạn.'); ?></p>
        </div>
        <a class="netflix-history-new-link" href="<?= htmlspecialchars(base_url('client/netflix'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?= __('Lấy link mới'); ?></a>
    </section>

    <section class="netflix-panel netflix-history-panel" aria-labelledby="netflix-history-list-title">
        <div class="netflix-history-toolbar">
            <div>
                <span class="netflix-panel-kicker"><?= __('Theo dõi dịch vụ'); ?></span>
                <h2 id="netflix-history-list-title"><?= __('Các link đã mua'); ?></h2>
            </div>
            <span class="netflix-history-summary"><strong><?= format_cash($historyTotal); ?></strong> <?= __('giao dịch'); ?></span>
        </div>

        <?php if (!$historyReady): ?>
        <div class="netflix-history-empty">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <strong><?= __('Chưa thể tải lịch sử Netflix'); ?></strong>
            <span><?= __('Vui lòng thử lại sau ít phút.'); ?></span>
        </div>
        <?php elseif (empty($historyOrders)): ?>
        <div class="netflix-history-empty">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <strong><?= __('Chưa có giao dịch Netflix'); ?></strong>
            <span><?= __('Sau khi lấy link thành công, giao dịch sẽ xuất hiện tại đây để bạn tạo lại link khi cần.'); ?></span>
        </div>
        <?php else: ?>
        <div class="netflix-history-table-wrap">
            <table class="netflix-history-table">
                <thead>
                    <tr>
                        <th><?= __('Giao dịch'); ?></th>
                        <th><?= __('Chi phí'); ?></th>
                        <th><?= __('Thời gian'); ?></th>
                        <th><?= __('Link mới'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyOrders as $historyOrder): ?>
                    <tr>
                        <td><span class="netflix-history-service"><strong><?= __('Xem Netflix'); ?></strong><small>#<?= (int) $historyOrder['id']; ?></small></span></td>
                        <td><span class="netflix-history-charge"><?= format_currency((float) $historyOrder['charged_amount']); ?></span></td>
                        <td><span class="netflix-history-time"><?= htmlspecialchars((string) $historyOrder['created_at'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <div class="netflix-history-actions">
                                <button class="netflix-history-refresh" data-netflix-history-refresh data-log-id="<?= htmlspecialchars((string) $historyOrder['log_id'], ENT_QUOTES, 'UTF-8'); ?>" type="button">
                                    <i class="fa-solid fa-rotate" aria-hidden="true"></i> <?= __('Tạo lại link'); ?>
                                </button>
                                <div class="netflix-history-result-actions" data-netflix-history-result hidden>
                                    <a data-netflix-history-pc target="_blank" rel="noopener noreferrer" hidden><i class="fa-solid fa-desktop" aria-hidden="true"></i> <?= __('Máy tính'); ?></a>
                                    <a data-netflix-history-mobile target="_blank" rel="noopener noreferrer" hidden><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> <?= __('Điện thoại'); ?></a>
                                </div>
                                <span class="netflix-history-message" data-netflix-history-message role="status"></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($historyTotal > $historyLimit): ?>
        <div class="netflix-history-pagination"><?= $historyPagination; ?></div>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
