<?php
declare(strict_types=1);

function load_environment(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && (($value[0] ?? '') === '"' || ($value[0] ?? '') === "'")) {
            $value = trim($value, "\"'");
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function app_name(): string
{
    return (string) env_value('APP_NAME', 'Caffemmo');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = rtrim((string) env_value('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return '/' . trim($path, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function request_json(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];
    return $data;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token ??= $_POST['_csrf'] ?? request_json()['_csrf'] ?? null;
    return is_string($token) && hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token);
}

function configure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = filter_var(env_value('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOL);
    session_name((string) env_value('SESSION_COOKIE', 'caffemmo_session'));
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($message) ? $message : null;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_api_request(): bool
{
    return str_starts_with(current_path(), '/api/');
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function format_money(float|int|string $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}

function format_date(?string $date): string
{
    if (!$date) {
        return 'Chưa cập nhật';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('H:i - d.m.Y', $timestamp) : 'Chưa cập nhật';
}

function days_remaining(?string $date): ?int
{
    if (!$date) {
        return null;
    }
    $target = strtotime($date);
    if (!$target) {
        return null;
    }
    return max(0, (int) ceil(($target - time()) / 86400));
}

function proxy_payload(array $proxy): array
{
    $payload = json_decode((string) ($proxy['provider_payload'] ?? ''), true);
    return is_array($payload) ? $payload : [];
}

function proxy_connection_details(array $proxy): array
{
    $payload = proxy_payload($proxy);
    $found = [];
    $visit = static function (mixed $value) use (&$visit, &$found): void {
        if (!is_array($value)) {
            return;
        }
        $ip = (string) ($value['ip'] ?? $value['ipAddress'] ?? $value['address'] ?? $value['host'] ?? '');
        $port = (string) ($value['port'] ?? $value['httpPort'] ?? $value['proxyPort'] ?? '');
        $user = (string) ($value['username'] ?? $value['user'] ?? $value['login'] ?? '');
        $pass = (string) ($value['password'] ?? $value['pass'] ?? '');
        if ($ip !== '' && $port !== '') {
            $key = $ip . ':' . $port . ':' . $user . ':' . $pass;
            $found[$key] = ['ip' => $ip, 'port' => $port, 'user' => $user, 'pass' => $pass, 'format' => $key];
        }
        foreach ($value as $child) {
            $visit($child);
        }
    };
    $visit($payload);
    return array_values($found);
}

function nav_is_active(string $path): string
{
    return current_path() === $path || str_starts_with(current_path(), rtrim($path, '/') . '/') ? ' is-active' : '';
}
