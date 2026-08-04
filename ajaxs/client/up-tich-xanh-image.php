<?php

// Public, short-lived image response used by the verification provider.
$filename = isset($_GET['file']) && is_scalar($_GET['file']) ? (string) $_GET['file'] : '';
if (!preg_match('/^verify_[a-f0-9]{20}\.(jpg|png|webp)$/i', $filename)) {
    http_response_code(404);
    exit;
}

$path = dirname(__DIR__, 2) . '/assets/storage/up-tich-xanh/' . $filename;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

$mimeMap = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp'
];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mime = $mimeMap[$extension] ?? '';
if ($mime === '' || @getimagesize($path) === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: no-store, max-age=0');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
readfile($path);
