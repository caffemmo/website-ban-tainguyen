<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../models/is_user.php';
require_once __DIR__ . '/../../libs/uptichxanh.php';

uptichxanh_ensure_tables();

$historyServices = [
    '' => 'Tất cả dịch vụ',
    'get-link' => uptichxanh_service_label('get-link'),
    'up-fb' => uptichxanh_service_label('up-fb'),
    'up-ig' => uptichxanh_service_label('up-ig')
];
$historyService = isset($_GET['service']) && is_scalar($_GET['service']) ? trim((string) $_GET['service']) : '';
if (!array_key_exists($historyService, $historyServices)) {
    $historyService = '';
}
$historyLimit = validate_int($_GET['limit'] ?? 10, 5, 50) ?: 10;
$historyPage = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$historyFrom = ($historyPage - 1) * $historyLimit;

$historyWhere = ['`user_id` = ?'];
$historyParams = [(int) $getUser['id']];
if ($historyService !== '') {
    $historyWhere[] = '`service` = ?';
    $historyParams[] = $historyService;
}
$historyWhereSql = implode(' AND ', $historyWhere);
$historyTotalRow = $CMSNT->get_row_safe("SELECT COUNT(*) AS total FROM `up_tich_xanh_orders` WHERE $historyWhereSql", $historyParams);
$historyTotal = (int) ($historyTotalRow['total'] ?? 0);
$historyOrders = $CMSNT->get_list_safe(
    "SELECT `id`, `service`, `provider_uid`, `result_link`, `charged_amount`, `provider_status`, `created_at`
    FROM `up_tich_xanh_orders`
    WHERE $historyWhereSql
    ORDER BY `id` DESC
    LIMIT ?, ?",
    array_merge($historyParams, [$historyFrom, $historyLimit])
) ?: [];

$historyBaseUrl = base_url('client/up-tich-xanh-history');
$historyQuery = $historyBaseUrl . '?service=' . rawurlencode($historyService) . '&limit=' . $historyLimit . '&';
$historyPagination = pagination_client($historyQuery, $historyFrom, $historyTotal, $historyLimit);

$body = [
    'title' => __('Lịch sử Up tích xanh') . ' | ' . $CMSNT->site('title'),
    'desc' => __('Theo dõi các yêu cầu Up tích xanh đã gửi.'),
    'keyword' => 'lịch sử up tích xanh, get link facebook, up tích facebook, up tích instagram'
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/up-tich-xanh.css?v=14') . '">';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/nav.php';
?>

<main class="up-page up-history-page">
    <section class="up-panel up-history-panel" aria-labelledby="up-history-title">
        <div class="up-history-overview">
            <div class="up-history-overview-copy">
                <span class="up-eyebrow"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Caffemmo Social</span>
                <h1 id="up-history-title"><?= __('Lịch sử yêu cầu Up tích xanh'); ?></h1>
                <p><?= __('Chỉ hiển thị thông tin kết quả và thanh toán của tài khoản hiện tại. Cookie, ảnh giấy tờ và phản hồi API không được hiển thị.'); ?></p>
            </div>
            <a class="up-history-back-link" href="<?= htmlspecialchars(base_url('client/up-tich-xanh/get-link'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?= __('Tạo yêu cầu mới'); ?></a>
        </div>

        <form class="up-history-filter" action="<?= htmlspecialchars($historyBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" method="get">
            <label for="up_history_service">
                <?= __('Lọc theo dịch vụ'); ?>
                <select id="up_history_service" name="service" onchange="this.form.submit()">
                    <?php foreach ($historyServices as $historyServiceKey => $historyServiceLabel): ?>
                    <option value="<?= htmlspecialchars($historyServiceKey, ENT_QUOTES, 'UTF-8'); ?>"<?= $historyService === $historyServiceKey ? ' selected' : ''; ?>><?= htmlspecialchars(__($historyServiceLabel), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="up_history_limit">
                <?= __('Hiển thị'); ?>
                <select id="up_history_limit" name="limit" onchange="this.form.submit()">
                    <?php foreach ([10, 20, 50] as $limitOption): ?>
                    <option value="<?= $limitOption; ?>"<?= $historyLimit === $limitOption ? ' selected' : ''; ?>><?= $limitOption; ?> <?= __('dòng'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <span class="up-history-summary"><strong><?= format_cash($historyTotal); ?></strong> <?= __('yêu cầu'); ?></span>
        </form>

        <?php if (empty($historyOrders)): ?>
        <div class="up-history-empty">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <strong><?= __('Chưa có yêu cầu nào'); ?></strong>
            <span><?= __('Kết quả của các yêu cầu thành công sẽ xuất hiện tại đây.'); ?></span>
        </div>
        <?php else: ?>
        <div class="up-history-table-wrap">
            <table class="up-history-table">
                <thead>
                    <tr>
                        <th><?= __('Dịch vụ'); ?></th>
                        <th><?= __('Mã yêu cầu'); ?></th>
                        <th><?= __('Kết quả'); ?></th>
                        <th><?= __('Chi phí'); ?></th>
                        <th><?= __('Trạng thái'); ?></th>
                        <th><?= __('Thời gian'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyOrders as $historyOrder): ?>
                    <?php
                    $historyStatus = uptichxanh_order_status($historyOrder['provider_status'] ?? '');
                    $historyLink = trim((string) ($historyOrder['result_link'] ?? ''));
                    $historyHasLink = $historyLink !== ''
                        && filter_var($historyLink, FILTER_VALIDATE_URL) !== false
                        && strtolower((string) parse_url($historyLink, PHP_URL_SCHEME)) === 'https';
                    ?>
                    <tr>
                        <td><span class="up-history-service"><strong><?= htmlspecialchars(__($historyServices[$historyOrder['service']] ?? uptichxanh_service_label($historyOrder['service'])), ENT_QUOTES, 'UTF-8'); ?></strong><small>#<?= (int) $historyOrder['id']; ?></small></span></td>
                        <td><span class="up-history-id"><?= !empty($historyOrder['provider_uid']) ? htmlspecialchars($historyOrder['provider_uid'], ENT_QUOTES, 'UTF-8') : __('Chưa có mã'); ?></span></td>
                        <td>
                            <?php if ($historyHasLink): ?>
                            <a class="up-history-result" href="<?= htmlspecialchars($historyLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?= __('Mở link') ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                            <?php else: ?>
                            <span class="up-history-result--empty"><?= __('Không có liên kết'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="up-history-charge"><?= format_currency((float) $historyOrder['charged_amount']); ?></span></td>
                        <td><span class="up-history-status up-history-status--<?= htmlspecialchars($historyStatus['class'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__($historyStatus['label']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="up-history-time"><?= htmlspecialchars((string) $historyOrder['created_at'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($historyTotal > $historyLimit): ?>
        <div class="up-history-pagination"><?= $historyPagination; ?></div>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
