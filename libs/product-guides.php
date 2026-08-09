<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function caffemmo_product_guides_is_safe_url($url)
{
    $url = trim((string) $url);
    $parts = parse_url($url);

    return $url !== ''
        && mb_strlen($url, 'UTF-8') <= 2048
        && filter_var($url, FILTER_VALIDATE_URL) !== false
        && !empty($parts['host'])
        && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true);
}

function caffemmo_product_guides_get_all()
{
    global $CMSNT;

    $setting = $CMSNT->get_row_safe('SELECT `value` FROM `settings` WHERE `name` = ? LIMIT 1', ['product_guides']);
    $decoded = json_decode((string) ($setting['value'] ?? ''), true);
    if (!is_array($decoded)) {
        return [];
    }

    $guides = [];
    foreach ($decoded as $productId => $guide) {
        $productId = (int) $productId;
        $url = trim((string) ($guide['url'] ?? ''));
        if ($productId < 1 || !caffemmo_product_guides_is_safe_url($url)) {
            continue;
        }

        $guides[(string) $productId] = [
            'url' => $url,
            'enabled' => (int) ($guide['enabled'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    return $guides;
}

function caffemmo_product_guide_get($productId)
{
    $guides = caffemmo_product_guides_get_all();
    return $guides[(string) (int) $productId] ?? ['url' => '', 'enabled' => 0];
}

function caffemmo_product_guide_save($productId, $url, $enabled)
{
    global $CMSNT;

    $productId = (int) $productId;
    $url = trim((string) $url);
    if ($productId < 1 || ($url !== '' && !caffemmo_product_guides_is_safe_url($url))) {
        return false;
    }

    $guides = caffemmo_product_guides_get_all();
    $key = (string) $productId;
    if ($url === '') {
        unset($guides[$key]);
    } else {
        $guides[$key] = [
            'url' => $url,
            'enabled' => (int) $enabled === 1 ? 1 : 0,
        ];
    }

    $value = json_encode($guides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($value === false) {
        return false;
    }

    $exists = $CMSNT->get_row_safe('SELECT `value` FROM `settings` WHERE `name` = ? LIMIT 1', ['product_guides']);
    if (!$exists && $url === '') {
        return true;
    }
    if ($exists) {
        if ((string) ($exists['value'] ?? '') === $value) {
            return true;
        }
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', ['product_guides']);
    }

    return $CMSNT->insert('settings', ['name' => 'product_guides', 'value' => $value]);
}

function caffemmo_product_guide_button($productId, $guides = null)
{
    $guides = is_array($guides) ? $guides : caffemmo_product_guides_get_all();
    $guide = $guides[(string) (int) $productId] ?? null;
    if (!$guide || (int) ($guide['enabled'] ?? 0) !== 1) {
        return '';
    }

    $url = htmlspecialchars($guide['url'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars(__('Hướng dẫn sử dụng'), ENT_QUOTES, 'UTF-8');
    return '<div class="product-guide-action"><a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
        . '<i class="fa-solid fa-book-open" aria-hidden="true"></i><span>' . $label . '</span>'
        . '<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>';
}
