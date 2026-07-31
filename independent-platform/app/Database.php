<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): ?PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env_value('DB_HOST', '127.0.0.1'),
                env_value('DB_PORT', '3306'),
                env_value('DB_DATABASE', 'caffemmo')
            );
            self::$connection = new PDO($dsn, (string) env_value('DB_USERNAME', 'root'), (string) env_value('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$connection;
        } catch (Throwable $exception) {
            Logger::error('Database connection failed', ['message' => $exception->getMessage()]);
            return null;
        }
    }
}

function db(): ?PDO
{
    return Database::connection();
}
