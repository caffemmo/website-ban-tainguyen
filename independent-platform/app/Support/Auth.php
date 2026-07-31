<?php
declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id < 1 || !db()) {
            return null;
        }
        $statement = db()->prepare('SELECT id, name, email, role, balance, status FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $user = $statement->fetch();
        return is_array($user) && $user['status'] === 'active' ? $user : null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $connection = db();
        if (!$connection) {
            return false;
        }
        $statement = $connection->prepare('SELECT id, password_hash, status FROM users WHERE email = ? LIMIT 1');
        $statement->execute([normalize_email($email)]);
        $record = $statement->fetch();
        if (!is_array($record) || $record['status'] !== 'active' || !password_verify($password, (string) $record['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $record['id'];
        csrf_token();
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}

function current_user(): ?array
{
    return Auth::user();
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'Unauthenticated'], 401);
        }
        redirect_to('/login');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (!in_array($user['role'], ['admin', 'staff'], true)) {
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}
