<?php
declare(strict_types=1);

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $safeContext = self::redact($context);
        $line = sprintf('[%s] %s %s%s', date('c'), $level, $message, $safeContext ? ' ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE) : '');
        error_log($line);
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0750, true);
        }
        @file_put_contents($directory . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function redact(array $context): array
    {
        $secretWords = ['password', 'pass', 'token', 'secret', 'api_key', 'cookie', 'authorization'];
        foreach ($context as $key => $value) {
            $keyLower = strtolower((string) $key);
            foreach ($secretWords as $word) {
                if (str_contains($keyLower, $word)) {
                    $context[$key] = '[redacted]';
                    continue 2;
                }
            }
            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }
        return $context;
    }
}
