<?php
declare(strict_types=1);

function view(string $template, array $data = [], bool $layout = true): string
{
    $file = VIEW_PATH . '/' . trim($template, '/') . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    $content = (string) ob_get_clean();
    if (!$layout) {
        return $content;
    }
    return view('layout', array_merge($data, ['content' => $content]), false);
}
