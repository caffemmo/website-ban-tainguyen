<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/../../../libs/youproxy.php';
require_once __DIR__ . '/../../../libs/proxyline.php';

$ipv6CountryOptions = [];
if (youproxy_is_configured()) {
    $ipv6CountryResponse = youproxy_countries('IPv6');
    if (!empty($ipv6CountryResponse['success'])) {
        $ipv6CountryOptions = youproxy_response_options(
            $ipv6CountryResponse,
            ['countries', 'items'],
            ['alpha3code', 'alpha3', 'countryCode', 'code'],
            ['name', 'countryName', 'title']
        );
    }
}

function proxy_admin_save_setting($name, $value)
{
    global $CMSNT;

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', [$name]);
    }
    return $CMSNT->insert('settings', ['name' => $name, 'value' => $value]);
}

if (isset($_POST['SyncIpv6RetailBatch'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $batchId = filter_var($_POST['ipv6_batch_id'] ?? null, FILTER_VALIDATE_INT);
    if ($batchId === false || $batchId < 1) {
        admin_msg_error('Lô IPv6 không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    $result = youproxy_ipv6_retail_sync_batch($batchId);
    if (empty($result['success'])) {
        admin_msg_error($result['message'] ?? 'Không thể đồng bộ lô IPv6.', base_url_admin('settings&tab=proxy'), 1500);
    }
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => 'Đồng bộ lô IPv6 bán lẻ #' . $batchId
    ]);
    $message = $result['status'] === 'active'
        ? 'Đã đồng bộ đủ ' . $result['received_quantity'] . '/' . $result['expected_quantity'] . ' IPv6 và mở bán lẻ.'
        : 'Đã nhận ' . $result['received_quantity'] . '/' . $result['expected_quantity'] . ' IPv6. Lô vẫn chờ đồng bộ thêm.';
    admin_msg_success($message, base_url_admin('settings&tab=proxy'), 1200);
}

if (isset($_POST['RestockIpv6Retail'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    if (!youproxy_is_configured()) {
        admin_msg_error('Hãy lưu cấu hình YouProxy hợp lệ trước khi nhập kho IPv6.', base_url_admin('settings&tab=proxy'), 1400);
    }

    $country = strtoupper(trim((string) ($_POST['ipv6_retail_country'] ?? '')));
    $rentDays = filter_var($_POST['ipv6_retail_rent_days'] ?? null, FILTER_VALIDATE_INT);
    $protocol = strtoupper(trim((string) ($_POST['ipv6_retail_protocol'] ?? 'HTTP')));
    $authType = 'LOGIN';
    $goal = trim((string) ($_POST['ipv6_retail_goal'] ?? 'Facebook'));

    if (!empty($ipv6CountryOptions)) {
        $supportedCountries = [];
        foreach ($ipv6CountryOptions as $option) {
            $supportedCountry = strtoupper(trim((string) ($option['value'] ?? '')));
            if ($supportedCountry !== '') {
                $supportedCountries[$supportedCountry] = true;
            }
        }
        if (!isset($supportedCountries[$country])) {
            admin_msg_error('Mã quốc gia IPv6 không nằm trong danh sách YouProxy đang cung cấp.', base_url_admin('settings&tab=proxy'), 1600);
        }
    }

    if (!preg_match('/^[A-Z0-9_-]{2,12}$/', $country)
        || $rentDays === false || $rentDays < 1 || $rentDays > 3650
        || !in_array($protocol, ['HTTP', 'SOCKS'], true)
        || $goal === '' || mb_strlen($goal) > 200) {
        admin_msg_error('Thông tin lô IPv6 không hợp lệ.', base_url_admin('settings&tab=proxy'), 1400);
    }

    $payload = [
        'proxyType' => 'IPv6',
        'country' => $country,
        'rentPeriodDays' => $rentDays,
        'goal' => $goal,
        'authType' => $authType,
        'protocol' => $protocol,
        'quantity' => youproxy_ipv6_retail_batch_quantity()
    ];
    $quote = youproxy_calculate_order($payload);
    if (empty($quote['success'])) {
        admin_msg_error(youproxy_error_text($quote, 'Không thể báo giá lô IPv6.'), base_url_admin('settings&tab=proxy'), 1800);
    }
    $price = youproxy_price_context($quote);
    if ((float) $price['provider_price'] <= 0) {
        admin_msg_error('YouProxy chưa trả về giá hợp lệ cho lô IPv6 này.', base_url_admin('settings&tab=proxy'), 1500);
    }
    $providerOrder = youproxy_create_order($payload);
    if (empty($providerOrder['success'])) {
        admin_msg_error(youproxy_error_text($providerOrder, 'Không thể mua lô IPv6 từ YouProxy.'), base_url_admin('settings&tab=proxy'), 1800);
    }
    $result = youproxy_ipv6_retail_store_batch($getUser['id'], $payload, $quote, $providerOrder);
    if (empty($result['success'])) {
        admin_msg_error($result['message'] ?? 'Lô YouProxy đã tạo nhưng không thể lưu vào kho. Hãy kiểm tra lại ngay.', base_url_admin('settings&tab=proxy'), 1800);
    }
    if ($result['status'] !== 'active') {
        $result = youproxy_ipv6_retail_sync_batch($result['batch_id']);
    }
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => 'Nhập kho IPv6 bán lẻ: ' . youproxy_ipv6_retail_batch_quantity() . ' IP ' . $country
    ]);
    $message = !empty($result['success']) && ($result['status'] ?? '') === 'active'
        ? 'Đã nhập kho và mở bán lẻ ' . $result['received_quantity'] . '/' . $result['expected_quantity'] . ' IPv6.'
        : 'Đã tạo lô IPv6, nhưng đang chờ YouProxy trả đủ IP để mở bán. Bạn có thể bấm Đồng bộ lô sau ít phút.';
    admin_msg_success($message, base_url_admin('settings&tab=proxy'), 1400);
}

if (isset($_POST['SaveProxySettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $apiKey = trim((string) ($_POST['youproxy_api_key'] ?? ''));
    $baseUrl = trim((string) ($_POST['youproxy_api_base_url'] ?? ''));
    $usdRate = trim((string) ($_POST['youproxy_usd_rate'] ?? ''));
    $markup = trim((string) ($_POST['youproxy_markup_percent'] ?? ''));
    $ipv6RetailUnitPrice = trim((string) ($_POST['youproxy_ipv6_retail_unit_price'] ?? ''));
    $timeout = filter_var($_POST['youproxy_timeout'] ?? null, FILTER_VALIDATE_INT);
    $proxylineApiKey = trim((string) ($_POST['proxyline_api_key'] ?? ''));
    $proxylineBaseUrl = trim((string) ($_POST['proxyline_api_base_url'] ?? ''));
    $proxylineIpv4Price = trim((string) ($_POST['proxyline_ipv4_price_per_ip_day'] ?? ''));

    if ($apiKey !== '' && mb_strlen($apiKey) > 255) {
        admin_msg_error('Khóa dịch vụ proxy không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
        admin_msg_error('URL dịch vụ proxy phải là một địa chỉ HTTPS hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if (!is_numeric($usdRate) || (float) $usdRate <= 0 || (float) $usdRate > 1000000000) {
        admin_msg_error('Tỷ giá USD không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if (!is_numeric($markup) || (float) $markup < 0 || (float) $markup > 1000) {
        admin_msg_error('Phần trăm markup không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($ipv6RetailUnitPrice !== '' && (!is_numeric($ipv6RetailUnitPrice) || (float) $ipv6RetailUnitPrice < 0 || (float) $ipv6RetailUnitPrice > 1000000000)) {
        admin_msg_error('Giá bán IPv6 lẻ không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($timeout === false || $timeout < 5 || $timeout > 120) {
        admin_msg_error('Timeout phải nằm trong khoảng 5 đến 120 giây.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($proxylineApiKey !== '' && mb_strlen($proxylineApiKey) > 255) {
        admin_msg_error('Khóa API ProxyLine không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($proxylineBaseUrl === '' || filter_var($proxylineBaseUrl, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($proxylineBaseUrl, PHP_URL_SCHEME)) !== 'https') {
        admin_msg_error('URL ProxyLine phải là một địa chỉ HTTPS hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }
    if ($proxylineIpv4Price !== '' && (!is_numeric($proxylineIpv4Price) || (float) $proxylineIpv4Price < 0 || (float) $proxylineIpv4Price > 1000000000)) {
        admin_msg_error('Giá bán IPv4 ProxyLine không hợp lệ.', base_url_admin('settings&tab=proxy'), 1200);
    }

    if ($apiKey !== '') {
        proxy_admin_save_setting('youproxy_api_key', $apiKey);
    }
    proxy_admin_save_setting('youproxy_api_base_url', rtrim($baseUrl, '/'));
    proxy_admin_save_setting('youproxy_usd_rate', number_format((float) $usdRate, 2, '.', ''));
    proxy_admin_save_setting('youproxy_markup_percent', number_format((float) $markup, 2, '.', ''));
    proxy_admin_save_setting('youproxy_ipv6_retail_unit_price', $ipv6RetailUnitPrice === '' ? '0' : number_format((float) $ipv6RetailUnitPrice, 2, '.', ''));
    proxy_admin_save_setting('youproxy_timeout', (string) $timeout);
    if ($proxylineApiKey !== '') {
        proxy_admin_save_setting('proxyline_api_key', $proxylineApiKey);
    }
    proxy_admin_save_setting('proxyline_api_base_url', rtrim($proxylineBaseUrl, '/'));
    proxy_admin_save_setting('proxyline_ipv4_price_per_ip_day', $proxylineIpv4Price === '' ? '0' : number_format((float) $proxylineIpv4Price, 2, '.', ''));
    foreach (youproxy_proxy_type_definitions() as $typeCode => $typeDefinition) {
        proxy_admin_save_setting('youproxy_type_enabled_' . $typeCode, isset($_POST['youproxy_type_enabled_' . $typeCode]) ? '1' : '0');
    }

    if ((float) $ipv6RetailUnitPrice > 0) {
        $normalizedIpv6RetailPrice = number_format((float) $ipv6RetailUnitPrice, 2, '.', '');
        $CMSNT->update('proxy_ipv6_inventory', [
            'retail_price' => $normalizedIpv6RetailPrice,
            'updated_at' => date('Y-m-d H:i:s')
        ], '`status` IN (?, ?)', ['available', 'reserved']);
        $CMSNT->update('proxy_ipv6_batches', [
            'retail_unit_price' => $normalizedIpv6RetailPrice,
            'updated_at' => date('Y-m-d H:i:s')
        ], '`status` IN (?, ?)', ['active', 'pending_sync']);
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật cấu hình dịch vụ proxy')
    ]);

    admin_msg_success('Đã lưu cấu hình proxy.', base_url_admin('settings&tab=proxy'), 900);
}

$proxyConfig = youproxy_config();
$proxylineConfig = proxyline_config();
$proxyTypeStates = youproxy_proxy_type_states();
$ipv6RetailUnitPrice = youproxy_ipv6_retail_unit_price();
$proxylineIpv4Price = proxyline_ipv4_price_per_ip_day();
$proxyConfigured = youproxy_is_configured() || proxyline_is_configured();
$hasStoredKey = youproxy_db_setting('youproxy_api_key') !== '';
$proxylineHasStoredKey = youproxy_db_setting('proxyline_api_key') !== '';
youproxy_ensure_tables();
$ipv6RetailStats = $CMSNT->get_row_safe(
    "SELECT
        SUM(CASE WHEN `status` = 'available' THEN 1 ELSE 0 END) AS available_total,
        SUM(CASE WHEN `status` = 'reserved' THEN 1 ELSE 0 END) AS reserved_total,
        SUM(CASE WHEN `status` = 'sold' THEN 1 ELSE 0 END) AS sold_total,
        SUM(CASE WHEN `status` = 'pending_sync' THEN 1 ELSE 0 END) AS pending_total
    FROM `proxy_ipv6_inventory`"
) ?: [];
$ipv6RetailBatches = $CMSNT->get_list_safe(
    'SELECT * FROM `proxy_ipv6_batches` ORDER BY `id` DESC LIMIT 12'
) ?: [];
?>

<style>
    .proxy-admin-intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 22px 24px;
        margin-bottom: 20px;
        color: #fff;
        background: linear-gradient(135deg, #173b63 0%, #2374a8 100%);
        border-radius: 14px;
    }
    .proxy-admin-intro h4 { margin: 0 0 5px; font-weight: 700; }
    .proxy-admin-intro p { margin: 0; color: rgba(255,255,255,.78); }
    .proxy-admin-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 999px;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 700;
    }
    .proxy-admin-status i { font-size: 8px; }
    .proxy-admin-status.is-ready i { color: #8bf0ba; }
    .proxy-admin-status.is-pending i { color: #ffd166; }
    .proxy-admin-card {
        height: 100%;
        border: 1px solid #e7edf5;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(31, 55, 82, .06);
    }
    .proxy-admin-card .card-header { padding: 18px 20px 0; background: transparent; border: 0; }
    .proxy-admin-card .card-body { padding: 18px 20px 20px; }
    .proxy-admin-card .card-title { font-size: 16px; font-weight: 700; }
    .proxy-admin-label { display: flex; align-items: center; gap: 8px; font-weight: 600; }
    .proxy-admin-label i { width: 18px; color: #3a82c4; text-align: center; }
    .proxy-admin-help { color: #75869a; font-size: 12px; line-height: 1.5; }
    .proxy-admin-note {
        display: flex;
        gap: 10px;
        padding: 12px 14px;
        color: #47657f;
        background: #f2f8fc;
        border: 1px solid #dbeaf4;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.55;
    }
    .proxy-admin-note i { margin-top: 2px; color: #3a82c4; }
    .proxy-admin-save { min-width: 170px; }
    .proxyline-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 999px; color: #16774a; background: #e8f8ee; font-size: 11px; font-weight: 700; }
    .proxyline-status.is-pending { color: #96640a; background: #fff4d7; }
    .proxy-availability-card { height: 100%; border: 1px solid #e1eaf3; border-radius: 12px; background: #fbfdff; transition: border-color 160ms ease, background-color 160ms ease; }
    .proxy-availability-card:focus-within { border-color: #62c8d2; background: #f5fdfe; }
    .proxy-availability-card.is-disabled { background: #fffafb; }
    .proxy-availability-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 10px; color: #087e99; background: #d7f3f4; }
    .proxy-availability-title { color: #173b63; font-size: 14px; font-weight: 700; }
    .proxy-availability-help { color: #75869a; font-size: 11px; line-height: 1.45; }
    .proxy-availability-status { display: inline-flex; align-items: center; min-height: 24px; padding: 3px 8px; border-radius: 999px; color: #16774a; background: #e8f8ee; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .proxy-availability-card.is-disabled .proxy-availability-status { color: #9b4c56; background: #fff0f2; }
    .proxy-availability-switch { min-width: 46px; min-height: 24px; margin: 0; cursor: pointer; }
    .proxy-availability-switch:focus { box-shadow: 0 0 0 3px rgba(58, 130, 196, .2); }
    .proxy-retail-kicker { color: #75869a; font-size: 12px; line-height: 1.55; }
    .proxy-retail-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
    .proxy-retail-stat { padding: 13px; border: 1px solid #e1eaf3; border-radius: 10px; background: #fbfdff; }
    .proxy-retail-stat span { display: block; color: #74869a; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .proxy-retail-stat strong { display: block; margin-top: 4px; color: #173b63; font-size: 20px; }
    .proxy-retail-table { margin: 0; font-size: 12px; }
    .proxy-retail-table th { color: #75869a; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .proxy-retail-table td { vertical-align: middle; }
    .proxy-retail-status { display: inline-flex; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .proxy-retail-status.active { color: #16774a; background: #e8f8ee; }
    .proxy-retail-status.pending_sync { color: #96640a; background: #fff4d7; }
    .proxy-retail-sync-button { white-space: nowrap; }
    @media (max-width: 575.98px) {
        .proxy-admin-intro { align-items: flex-start; flex-direction: column; padding: 18px; }
        .proxy-admin-status { align-self: flex-start; }
        .proxy-retail-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<div class="tab-pane text-muted show active" id="proxy-settings" role="tabpanel">
    <div class="proxy-admin-intro">
        <div>
            <h4><i class="fa-solid fa-server me-2" aria-hidden="true"></i><?= __('Cấu hình Proxy'); ?></h4>
            <p><?= __('Thiết lập kết nối và giá bán cho dịch vụ proxy trên website.'); ?></p>
        </div>
        <span class="proxy-admin-status <?= $proxyConfigured ? 'is-ready' : 'is-pending'; ?>">
            <i class="fa-solid fa-circle" aria-hidden="true"></i>
            <?= $proxyConfigured ? __('Đã sẵn sàng') : __('Chưa sẵn sàng'); ?>
        </span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card proxy-admin-card">
                    <div class="card-header">
                        <div class="card-title mb-0"><i class="fa-solid fa-link me-2 text-primary" aria-hidden="true"></i><?= __('Kết nối dịch vụ'); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_api_key"><i class="fa-solid fa-key" aria-hidden="true"></i><?= __('Khóa API'); ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="youproxy_api_key" name="youproxy_api_key" placeholder="<?= $hasStoredKey ? __('Đã lưu khóa máy chủ, để trống để giữ nguyên') : __('Nhập khóa API trên máy chủ'); ?>" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="proxy_api_key_toggle" data-proxy-secret-toggle data-proxy-secret-input="youproxy_api_key" title="<?= __('Hiện hoặc ẩn khóa'); ?>" aria-label="<?= __('Hiện hoặc ẩn khóa'); ?>"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
                            </div>
                            <div class="proxy-admin-help mt-2"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= __('Khóa được giữ ở server và không hiển thị cho khách hàng.'); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_api_base_url"><i class="fa-solid fa-globe" aria-hidden="true"></i><?= __('URL dịch vụ'); ?></label>
                            <input type="url" class="form-control" id="youproxy_api_base_url" name="youproxy_api_base_url" value="<?= htmlspecialchars($proxyConfig['base_url'], ENT_QUOTES, 'UTF-8'); ?>" required inputmode="url">
                            <div class="proxy-admin-help mt-2"><?= __('Dùng HTTPS và không thêm dấu / ở cuối.'); ?></div>
                        </div>

                        <div class="proxy-admin-note">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            <span><?= __('Không đưa khóa API vào JavaScript, HTML công khai hoặc tài liệu gửi cho khách.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card proxy-admin-card">
                    <div class="card-header">
                        <div class="card-title mb-0"><i class="fa-solid fa-sliders me-2 text-primary" aria-hidden="true"></i><?= __('Định giá và thời gian'); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_usd_rate"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i><?= __('Tỷ giá USD'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_usd_rate" name="youproxy_usd_rate" value="<?= htmlspecialchars((string) $proxyConfig['usd_rate'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="0.01" required>
                                <span class="input-group-text">VND / USD</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Dùng để quy đổi giá dịch vụ sang số dư ví trên website.'); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_markup_percent"><i class="fa-solid fa-percent" aria-hidden="true"></i><?= __('Markup website'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_markup_percent" name="youproxy_markup_percent" value="<?= htmlspecialchars((string) $proxyConfig['markup_percent'], ENT_QUOTES, 'UTF-8'); ?>" min="0" max="1000" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Có thể đặt 0 nếu không cộng thêm phí trên giá quy đổi.'); ?></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label proxy-admin-label" for="youproxy_ipv6_retail_unit_price"><i class="fa-solid fa-tag" aria-hidden="true"></i><?= __('Giá bán IPv6 lẻ / IP'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_ipv6_retail_unit_price" name="youproxy_ipv6_retail_unit_price" value="<?= $ipv6RetailUnitPrice > 0 ? htmlspecialchars(number_format($ipv6RetailUnitPrice, 2, '.', ''), ENT_QUOTES, 'UTF-8') : ''; ?>" min="0" max="1000000000" step="1000" placeholder="0">
                                <span class="input-group-text">VND / IP</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Nhập giá thủ công cho kho chưa bán. Để trống hoặc 0 để tự tính theo giá YouProxy và markup.'); ?></div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label proxy-admin-label" for="youproxy_timeout"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i><?= __('Timeout kết nối'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="youproxy_timeout" name="youproxy_timeout" value="<?= htmlspecialchars((string) $proxyConfig['timeout'], ENT_QUOTES, 'UTF-8'); ?>" min="5" max="120" step="1" required>
                                <span class="input-group-text">giây</span>
                            </div>
                            <div class="proxy-admin-help mt-2"><?= __('Thời gian tối đa server chờ phản hồi cho mỗi yêu cầu.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="card proxy-admin-card mt-4" aria-labelledby="proxyline-settings-title">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="card-title mb-1" id="proxyline-settings-title"><i class="fa-solid fa-globe me-2 text-primary" aria-hidden="true"></i><?= __('Cấu hình IPv4'); ?></div>
                        <p class="proxy-admin-help mb-0"><?= __('Thiết lập nguồn IPv4 riêng và giá bán thủ công. ISP, IPv6 và Mobile vẫn dùng cấu hình hiện tại.'); ?></p>
                    </div>
                    <span class="proxyline-status <?= $proxylineConfigured ? '' : 'is-pending'; ?>">
                        <i class="fa-solid fa-circle" aria-hidden="true"></i>
                        <?= $proxylineConfigured ? __('ProxyLine đã sẵn sàng') : __('Chưa cấu hình IPv4'); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label proxy-admin-label" for="proxyline_api_key"><i class="fa-solid fa-key" aria-hidden="true"></i><?= __('Khóa API IPv4'); ?></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="proxyline_api_key" name="proxyline_api_key" placeholder="<?= $proxylineHasStoredKey ? __('Đã lưu khóa máy chủ, để trống để giữ nguyên') : __('Nhập khóa API trên máy chủ'); ?>" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" data-proxy-secret-toggle data-proxy-secret-input="proxyline_api_key" title="<?= __('Hiện hoặc ẩn khóa'); ?>" aria-label="<?= __('Hiện hoặc ẩn khóa'); ?>"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
                        </div>
                        <div class="proxy-admin-help mt-2"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= __('Khóa chỉ được gửi tới server, không đưa vào frontend hoặc dữ liệu trả cho khách.'); ?></div>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label proxy-admin-label" for="proxyline_api_base_url"><i class="fa-solid fa-link" aria-hidden="true"></i><?= __('URL API IPv4'); ?></label>
                        <input type="url" class="form-control" id="proxyline_api_base_url" name="proxyline_api_base_url" value="<?= htmlspecialchars($proxylineConfig['base_url'], ENT_QUOTES, 'UTF-8'); ?>" required inputmode="url">
                        <div class="proxy-admin-help mt-2"><?= __('Dùng HTTPS và không thêm dấu / ở cuối.'); ?></div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label proxy-admin-label" for="proxyline_ipv4_price_per_ip_day"><i class="fa-solid fa-tag" aria-hidden="true"></i><?= __('Giá bán IPv4'); ?></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="proxyline_ipv4_price_per_ip_day" name="proxyline_ipv4_price_per_ip_day" value="<?= $proxylineIpv4Price > 0 ? htmlspecialchars(number_format($proxylineIpv4Price, 2, '.', ''), ENT_QUOTES, 'UTF-8') : ''; ?>" min="0" max="1000000000" step="0.01" placeholder="0">
                            <span class="input-group-text">VND / IP / ngày</span>
                        </div>
                        <div class="proxy-admin-help mt-2"><?= __('Giá × số IP × số ngày. Đặt 0 để tạm ngừng bán IPv4.'); ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card proxy-admin-card mt-4" aria-labelledby="proxy-availability-title">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <div class="card-title mb-1" id="proxy-availability-title"><i class="fa-solid fa-toggle-on me-2 text-primary" aria-hidden="true"></i><?= __('Trạng thái mở bán'); ?></div>
                        <p class="proxy-admin-help mb-0"><?= __('Tắt một loại proxy sẽ ngưng đặt mua mới. Proxy khách đã mua vẫn xem, quản lý và gia hạn bình thường.'); ?></p>
                    </div>
                    <span class="badge text-bg-light border"><i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i><?= __('Áp dụng phía máy chủ'); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($proxyTypeStates as $typeCode => $typeState): ?>
                        <?php $typeEnabled = !empty($typeState['enabled']); ?>
                        <div class="col-md-6">
                            <div class="proxy-availability-card <?= $typeEnabled ? '' : 'is-disabled'; ?> p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="proxy-availability-icon" aria-hidden="true">
                                        <i class="fa-solid <?= $typeCode === 'ISP' ? 'fa-building-shield' : ($typeCode === 'IPV4' ? 'fa-globe' : ($typeCode === 'IPV6' ? 'fa-network-wired' : 'fa-mobile-screen-button')); ?>"></i>
                                    </span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                            <div class="proxy-availability-title"><?= htmlspecialchars((string) $typeState['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="proxy-availability-status" data-proxy-admin-status><?= $typeEnabled ? __('Đang mở bán') : __('Tạm ngưng'); ?></span>
                                        </div>
                                        <div class="proxy-availability-help mt-1" id="proxy-availability-help-<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8'); ?>"><?= __('Khách hàng chỉ có thể tạo đơn mới khi công tắc này đang bật.'); ?></div>
                                        <div class="form-check form-switch d-flex align-items-center gap-2 mt-3 mb-0 p-0">
                                            <input class="form-check-input proxy-availability-switch" type="checkbox" role="switch" data-proxy-admin-toggle id="youproxy_type_enabled_<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8'); ?>" name="youproxy_type_enabled_<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8'); ?>" value="1" <?= $typeEnabled ? 'checked' : ''; ?> aria-describedby="proxy-availability-help-<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8'); ?>">
                                            <label class="form-check-label small fw-semibold" data-proxy-admin-label for="youproxy_type_enabled_<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8'); ?>"><?= $typeEnabled ? __('Đang bật') : __('Đang tắt'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" name="SaveProxySettings" class="btn btn-primary proxy-admin-save">
                <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i><?= __('Lưu cấu hình'); ?>
            </button>
        </div>
    </form>

    <section class="mt-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-1"><i class="fa-solid fa-cubes-stacked me-2 text-primary" aria-hidden="true"></i><?= __('Kho IPv6 bán lẻ'); ?></h5>
                <p class="mb-0 proxy-retail-kicker"><?= __('Mua theo lô cố định 10 IPv6 từ YouProxy, sau đó khách có thể mua từng IP từ kho này.'); ?></p>
            </div>
            <span class="badge text-bg-light border"><?= __('Lô cố định'); ?>: <?= youproxy_ipv6_retail_batch_quantity(); ?> IPv6</span>
        </div>
        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card proxy-admin-card">
                    <div class="card-header">
                        <div class="card-title mb-0"><i class="fa-solid fa-cart-plus me-2 text-primary" aria-hidden="true"></i><?= __('Nhập kho lô mới'); ?></div>
                    </div>
                    <div class="card-body">
                        <p class="proxy-admin-help mb-3"><?= __('Hệ thống mua đúng 10 IPv6 bằng số dư YouProxy. Giá bán lẻ được chốt tại thời điểm nhập kho.'); ?></p>
                        <form action="" method="POST" autocomplete="off" data-ipv6-restock-form>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label proxy-admin-label" for="ipv6_retail_country"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i><?= __('Mã quốc gia'); ?></label>
                                    <?php if (!empty($ipv6CountryOptions)): ?>
                                        <select class="form-select" id="ipv6_retail_country" name="ipv6_retail_country" required>
                                            <option value=""><?= __('Chọn quốc gia'); ?></option>
                                            <?php foreach ($ipv6CountryOptions as $option): ?>
                                                <option value="<?= htmlspecialchars((string) $option['value'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?= htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars((string) $option['value'], ENT_QUOTES, 'UTF-8'); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control" id="ipv6_retail_country" name="ipv6_retail_country" placeholder="USA" maxlength="12" required>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label proxy-admin-label" for="ipv6_retail_rent_days"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i><?= __('Thời hạn'); ?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="ipv6_retail_rent_days" name="ipv6_retail_rent_days" value="30" min="1" max="3650" required>
                                        <span class="input-group-text"><?= __('ngày'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label proxy-admin-label" for="ipv6_retail_protocol"><i class="fa-solid fa-plug-circle-bolt" aria-hidden="true"></i><?= __('Protocol'); ?></label>
                                    <select class="form-select" id="ipv6_retail_protocol" name="ipv6_retail_protocol">
                                        <option value="HTTP">HTTP</option>
                                        <option value="SOCKS">SOCKS5</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label proxy-admin-label"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><?= __('Xác thực'); ?></label>
                                    <div class="form-control bg-light text-muted"><?= __('Login / Password'); ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label proxy-admin-label" for="ipv6_retail_goal"><i class="fa-solid fa-bullseye" aria-hidden="true"></i><?= __('Mục đích sử dụng'); ?></label>
                                    <input type="text" class="form-control" id="ipv6_retail_goal" name="ipv6_retail_goal" value="Facebook" maxlength="200" required>
                                </div>
                            </div>
                            <div class="proxy-admin-note mt-3">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                <span><?= __('Lô chưa nhận đủ IP sẽ ở trạng thái chờ đồng bộ và không xuất hiện cho khách mua.'); ?></span>
                            </div>
                            <button type="submit" name="RestockIpv6Retail" class="btn btn-primary w-100 mt-3" <?= !youproxy_is_configured() ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-boxes-stacked me-2" aria-hidden="true"></i><?= __('Mua và nhập kho 10 IPv6'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card proxy-admin-card">
                    <div class="card-header d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="card-title mb-1"><i class="fa-solid fa-warehouse me-2 text-primary" aria-hidden="true"></i><?= __('Tình trạng kho'); ?></div>
                            <div class="proxy-retail-kicker"><?= __('Chỉ IPv6 còn hạn đủ với cấu hình khách chọn mới được phép bán.'); ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="proxy-retail-stat-grid mb-4">
                            <div class="proxy-retail-stat"><span><?= __('Có thể bán'); ?></span><strong><?= (int) ($ipv6RetailStats['available_total'] ?? 0); ?></strong></div>
                            <div class="proxy-retail-stat"><span><?= __('Đang giữ chỗ'); ?></span><strong><?= (int) ($ipv6RetailStats['reserved_total'] ?? 0); ?></strong></div>
                            <div class="proxy-retail-stat"><span><?= __('Đã bán'); ?></span><strong><?= (int) ($ipv6RetailStats['sold_total'] ?? 0); ?></strong></div>
                            <div class="proxy-retail-stat"><span><?= __('Chờ đồng bộ'); ?></span><strong><?= (int) ($ipv6RetailStats['pending_total'] ?? 0); ?></strong></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table proxy-retail-table">
                                <thead><tr><th><?= __('Lô'); ?></th><th><?= __('Cấu hình'); ?></th><th><?= __('Đã nhận'); ?></th><th><?= __('Giá/IP'); ?></th><th><?= __('Trạng thái'); ?></th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($ipv6RetailBatches as $batch): ?>
                                    <?php
                                    $batchStatus = (string) ($batch['status'] ?? 'pending_sync');
                                    $createdAt = strtotime((string) ($batch['created_at'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><strong>#<?= (int) $batch['id']; ?></strong><br><span class="text-muted"><?= $createdAt ? date('d/m/Y H:i', $createdAt) : '--'; ?></span></td>
                                        <td><?= htmlspecialchars((string) $batch['country'], ENT_QUOTES, 'UTF-8'); ?> · <?= (int) $batch['rent_period_days']; ?> <?= __('ngày'); ?><br><span class="text-muted"><?= htmlspecialchars((string) $batch['protocol'], ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars((string) $batch['auth_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?= (int) $batch['received_quantity']; ?>/<?= (int) $batch['expected_quantity']; ?></td>
                                        <td><?= format_currency((float) $batch['retail_unit_price']); ?></td>
                                        <td><span class="proxy-retail-status <?= $batchStatus === 'active' ? 'active' : 'pending_sync'; ?>"><?= $batchStatus === 'active' ? __('Đang bán') : __('Chờ đồng bộ'); ?></span></td>
                                        <td>
                                            <?php if ($batchStatus !== 'active'): ?>
                                                <form action="" method="POST"><input type="hidden" name="ipv6_batch_id" value="<?= (int) $batch['id']; ?>"><button type="submit" name="SyncIpv6RetailBatch" class="btn btn-sm btn-outline-primary proxy-retail-sync-button"><i class="fa-solid fa-rotate me-1" aria-hidden="true"></i><?= __('Đồng bộ'); ?></button></form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ipv6RetailBatches)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4"><?= __('Chưa có lô IPv6 nào trong kho.'); ?></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (function () {
        document.querySelectorAll('[data-proxy-secret-toggle]').forEach(function (button) {
            var input = document.getElementById(button.getAttribute('data-proxy-secret-input'));
            if (!input) return;
            button.addEventListener('click', function () {
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.querySelector('i').className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            });
        });

        document.querySelectorAll('[data-proxy-admin-status]').forEach(function (status) {
            var card = status.closest('.proxy-availability-card');
            var toggle = card ? card.querySelector('[data-proxy-admin-toggle]') : null;
            var label = card ? card.querySelector('[data-proxy-admin-label]') : null;
            if (!toggle) return;
            toggle.addEventListener('change', function () {
                var enabled = toggle.checked;
                card.classList.toggle('is-disabled', !enabled);
                status.textContent = enabled ? 'Đang mở bán' : 'Tạm ngưng';
                if (label) label.textContent = enabled ? 'Đang bật' : 'Đang tắt';
            });
        });
    }());

</script>
