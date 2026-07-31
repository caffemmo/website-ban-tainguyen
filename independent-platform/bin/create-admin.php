<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

[$script, $name, $email, $password] = array_pad($argv, 4, null);
if (!$name || !$email || !$password || strlen($password) < 12 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php bin/create-admin.php \"Name\" email@example.com StrongPassword\n");
    exit(1);
}

$connection = db();
if (!$connection) {
    fwrite(STDERR, "Database unavailable\n");
    exit(1);
}
$statement = $connection->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "admin")');
$statement->execute([$name, normalize_email($email), password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Admin created: {$email}\n");
