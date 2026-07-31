<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->get('/', static function (): string {
    $user = current_user();
    return view('home', ['title' => 'Nền tảng vận hành Caffemmo', 'user' => $user]);
});

$router->get('/login', static function (): string {
    if (current_user()) {
        redirect_to('/app');
    }
    return view('auth/login', ['title' => 'Đăng nhập']);
});

$router->post('/login', static function (): never {
    if (!verify_csrf()) {
        flash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
        redirect_to('/login');
    }
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !Auth::attempt($email, $password)) {
        flash('error', 'Email hoặc mật khẩu không đúng.');
        redirect_to('/login');
    }
    Logger::info('User logged in', ['email' => $email]);
    redirect_to('/app');
});

$router->get('/register', static function (): string {
    if (current_user()) {
        redirect_to('/app');
    }
    return view('auth/register', ['title' => 'Tạo tài khoản']);
});

$router->post('/register', static function (): never {
    if (!verify_csrf()) {
        flash('error', 'Phiên biểu mẫu đã hết hạn.');
        redirect_to('/register');
    }
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        flash('error', 'Vui lòng kiểm tra họ tên, email và mật khẩu tối thiểu 8 ký tự.');
        redirect_to('/register');
    }
    $connection = db();
    if (!$connection) {
        flash('error', 'Hệ thống dữ liệu chưa sẵn sàng.');
        redirect_to('/register');
    }
    try {
        $statement = $connection->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Tài khoản đã được tạo. Bạn có thể đăng nhập.');
    } catch (Throwable $exception) {
        Logger::error('Registration failed', ['message' => $exception->getMessage()]);
        flash('error', 'Email đã tồn tại hoặc dữ liệu chưa hợp lệ.');
    }
    redirect_to('/login');
});

$router->get('/logout', static function (): never {
    Auth::logout();
    redirect_to('/');
});

$router->get('/app', static function (): string {
    $user = require_auth();
    return view('app/dashboard', ['title' => 'Bảng điều khiển', 'user' => $user, 'stats' => CatalogService::dashboard((int) $user['id'])]);
});

$router->get('/app/catalog', static function (): string {
    $user = require_auth();
    $products = CatalogService::products();
    return view('app/catalog', ['title' => 'Tất cả sản phẩm', 'user' => $user, 'products' => $products !== [] ? $products : demo_products()]);
});

$router->get('/app/proxy', static function (): string {
    $user = require_auth();
    $provider = new YouProxyProvider();
    return view('app/proxy', [
        'title' => 'Mua Proxy',
        'user' => $user,
        'providerReady' => $provider->isConfigured(),
    ]);
});

$router->get('/app/proxy/mine', static function (): string {
    $user = require_auth();
    return view('app/proxy-mine', ['title' => 'Proxy của tôi', 'user' => $user, 'proxies' => CatalogService::proxies((int) $user['id'])]);
});

$router->get('/app/proxy/renew', static function (): string {
    $user = require_auth();
    return view('app/proxy-renew', ['title' => 'Gia hạn Proxy', 'user' => $user, 'proxies' => CatalogService::proxies((int) $user['id'])]);
});

$router->get('/app/social', static function (): string {
    $user = require_auth();
    return view('app/social', [
        'title' => 'Dịch vụ mạng xã hội',
        'user' => $user,
        'providerReady' => (new SocialProvider())->isConfigured(),
    ]);
});

$router->get('/app/wallet', static function (): string {
    $user = require_auth();
    return view('app/wallet', ['title' => 'Ví & nạp tiền', 'user' => $user, 'transactions' => CatalogService::wallet((int) $user['id'])]);
});

$router->get('/app/orders', static function (): string {
    $user = require_auth();
    return view('app/orders', ['title' => 'Đơn hàng', 'user' => $user, 'orders' => CatalogService::orders((int) $user['id'])]);
});

$router->get('/admin', static function (): string {
    $user = require_admin();
    return view('admin/dashboard', ['title' => 'Admin dashboard', 'user' => $user, 'stats' => CatalogService::adminStats()]);
});

$router->get('/admin/settings', static function (): string {
    $user = require_admin();
    return view('admin/settings', [
        'title' => 'Cài đặt hệ thống',
        'user' => $user,
        'maintenance' => (string) Settings::get('maintenance_mode', '0') === '1',
        'proxyDailyPrice' => (float) Settings::get('proxy_daily_price', env_value('YOUPROXY_DEFAULT_DAILY_PRICE', '33800')),
        'providerReady' => (new YouProxyProvider())->isConfigured(),
        'socialReady' => (new SocialProvider())->isConfigured(),
        'bankReady' => (new BankProvider())->isConfigured(),
    ]);
});

$router->post('/admin/settings', static function (): never {
    require_admin();
    if (!verify_csrf()) {
        flash('error', 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.');
        redirect_to('/admin/settings');
    }
    $maintenance = !empty($_POST['maintenance_mode']) ? '1' : '0';
    $price = max(0, (float) ($_POST['proxy_daily_price'] ?? 0));
    Settings::put('maintenance_mode', $maintenance);
    Settings::put('proxy_daily_price', (string) $price);
    flash('success', 'Đã lưu cài đặt platform. Secret provider vẫn đọc từ .env server.');
    redirect_to('/admin/settings');
});

$router->get('/api/health', static function (): never {
    json_response(['ok' => true, 'service' => 'caffemmo-independent-platform', 'time' => date(DATE_ATOM)]);
});

$router->get('/api/catalog', static function (): never {
    require_auth();
    $products = CatalogService::products();
    json_response(['ok' => true, 'data' => $products !== [] ? $products : demo_products()]);
});

$router->get('/api/proxy/options', static function (): never {
    require_auth();
    $provider = new YouProxyProvider();
    if (!$provider->isConfigured()) {
        json_response(['ok' => false, 'state' => 'not_configured', 'message' => 'Dịch vụ đang được cấu hình.'], 503);
    }
    $result = $provider->options();
    json_response($result, $result['ok'] ? 200 : 502);
});

$router->get('/api/me', static function (): never {
    json_response(['ok' => true, 'data' => require_auth()]);
});

$router->post('/api/wallet/deposit-intent', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $payload = request_json();
    $result = WalletService::createPendingDeposit((int) $user['id'], (float) ($payload['amount'] ?? 0), (string) ($payload['provider'] ?? 'manual'), (string) ($payload['description'] ?? ''));
    json_response($result, $result['ok'] ? 201 : 422);
});

$router->post('/api/proxy/intent', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $result = ProxyService::createPending((int) $user['id'], request_json());
    json_response($result, $result['ok'] ? 201 : 422);
});

$router->post('/api/order/create', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $payload = request_json();
    $result = OrderService::create((int) $user['id'], is_array($payload['items'] ?? null) ? $payload['items'] : []);
    json_response($result, $result['ok'] ? 201 : 422);
});

$router->post('/api/proxy/purchase', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $result = ProxyService::purchase((int) $user['id'], request_json());
    json_response($result, $result['ok'] ? 201 : 422);
});

$router->post('/api/proxy/renew', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $payload = request_json();
    $proxyId = (int) ($payload['proxy_order_id'] ?? 0);
    $days = max(1, (int) ($payload['rent_period_days'] ?? 0));
    $connection = db();
    if (!$connection || $proxyId < 1) {
        json_response(['ok' => false, 'status' => 'invalid'], 422);
    }
    try {
        $statement = $connection->prepare("SELECT * FROM proxy_orders WHERE id = ? AND user_id = ? AND status IN ('active','expired') LIMIT 1");
        $statement->execute([$proxyId, $user['id']]);
        $proxy = $statement->fetch();
        if (!$proxy || trim((string) $proxy['provider_order_id']) === '') {
            json_response(['ok' => false, 'status' => 'proxy_not_found'], 404);
        }
        $provider = new YouProxyProvider();
        $providerResult = $provider->renew((string) $proxy['provider_order_id'], $days);
        if (!$providerResult['ok']) {
            json_response(['ok' => false, 'status' => 'provider_failed'], 502);
        }
        $update = $connection->prepare("UPDATE proxy_orders SET status = 'active', expires_at = DATE_ADD(GREATEST(COALESCE(expires_at, UTC_TIMESTAMP()), UTC_TIMESTAMP()), INTERVAL ? DAY) WHERE id = ?");
        $update->execute([$days, $proxyId]);
        AuditService::record((int) $user['id'], 'proxy.renewed', 'proxy_order', (string) $proxyId, ['days' => $days]);
        json_response(['ok' => true, 'status' => 'active', 'provider' => $providerResult['data'] ?? []]);
    } catch (Throwable $exception) {
        Logger::error('Proxy renewal failed', ['user_id' => $user['id'], 'proxy_id' => $proxyId, 'message' => $exception->getMessage()]);
        json_response(['ok' => false, 'status' => 'failed'], 500);
    }
});

$router->post('/api/social/request', static function (): never {
    $user = require_auth();
    if (!verify_csrf()) {
        json_response(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
    $payload = request_json();
    $service = (string) ($payload['service'] ?? '');
    // Chi nhan metadata an toan. Khong nhan UID, mat khau, 2FA hay cookie.
    $safePayload = ['link' => (string) ($payload['link'] ?? ''), 'image_url' => (string) ($payload['image_url'] ?? '')];
    $result = SocialService::processRequest((int) $user['id'], $service, $safePayload);
    json_response($result, $result['ok'] ? 201 : 422);
});

try {
    $maintenanceAllowed = in_array(current_path(), ['/login', '/register', '/logout', '/api/health'], true);
    $maintenanceUser = current_user();
    if ((string) Settings::get('maintenance_mode', '0') === '1' && !$maintenanceAllowed && !($maintenanceUser && in_array($maintenanceUser['role'], ['admin', 'staff'], true))) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'Platform đang bảo trì'], 503);
        }
        http_response_code(503);
        echo view('errors/maintenance', ['title' => 'Đang bảo trì']);
        exit;
    }
    $result = $router->dispatch(request_method(), current_path());
    if (is_string($result)) {
        echo $result;
    }
} catch (Throwable $exception) {
    Logger::error('Unhandled application error', ['message' => $exception->getMessage(), 'path' => current_path()]);
    if (is_api_request()) {
        json_response(['ok' => false, 'error' => 'Internal server error'], 500);
    }
    http_response_code(500);
    echo view('errors/500', ['title' => 'Có lỗi xảy ra']);
}

function demo_products(): array
{
    return [
        ['name' => 'ACC thuê tích xanh Facebook', 'type' => 'Tài nguyên', 'price' => 120000, 'tone' => 'blue', 'stock' => 'Còn hàng'],
        ['name' => 'Proxy IPv4 premium', 'type' => 'Proxy', 'price' => 33800, 'tone' => 'teal', 'stock' => 'Live pricing'],
        ['name' => 'ChatGPT Plus 20K', 'type' => 'Tài nguyên', 'price' => 20000, 'tone' => 'violet', 'stock' => 'Còn hàng'],
        ['name' => 'Up tích xanh Facebook', 'type' => 'Dịch vụ', 'price' => 15000, 'tone' => 'amber', 'stock' => 'Sẵn sàng'],
    ];
}
