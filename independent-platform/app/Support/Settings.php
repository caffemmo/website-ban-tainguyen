<?php
declare(strict_types=1);

final class Settings
{
    public static function get(string $name, mixed $default = null): mixed
    {
        $connection = db();
        if (!$connection) {
            return $default;
        }
        try {
            $statement = $connection->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
            $statement->execute([$name]);
            $value = $statement->fetchColumn();
            return $value === false ? $default : $value;
        } catch (Throwable $exception) {
            Logger::error('Setting read failed', ['name' => $name, 'message' => $exception->getMessage()]);
            return $default;
        }
    }

    public static function put(string $name, string $value, bool $secret = false): bool
    {
        if (trim($name) === '') {
            return false;
        }
        $connection = db();
        if (!$connection) {
            return false;
        }
        try {
            $statement = $connection->prepare(
                'INSERT INTO settings (name, value, is_secret) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), is_secret = VALUES(is_secret)'
            );
            $statement->execute([$name, $value, $secret ? 1 : 0]);
            return true;
        } catch (Throwable $exception) {
            Logger::error('Setting write failed', ['name' => $name, 'message' => $exception->getMessage()]);
            return false;
        }
    }
}
