<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function caffemmo_client_guides_default_url()
{
    return 'https://docs.google.com/document/d/1EIRDWUNvtDDFF8zPSpBXxP8fVPDDkfrAn5QP61PODhc/edit?hl=vi&tab=t.0';
}

function caffemmo_client_guides_defaults()
{
    $url = caffemmo_client_guides_default_url();

    return [
        ['title' => 'Hướng dẫn sử dụng Proxy ngâm tích xanh', 'url' => $url, 'enabled' => 1],
        ['title' => 'Hướng dẫn cài Proxy trên PC', 'url' => $url, 'enabled' => 1],
        ['title' => 'Hướng dẫn cài Proxy trên iPhone', 'url' => $url, 'enabled' => 1],
    ];
}

function caffemmo_client_guides_is_safe_url($url)
{
    $url = trim((string) $url);
    $parts = parse_url($url);

    return $url !== ''
        && filter_var($url, FILTER_VALIDATE_URL) !== false
        && !empty($parts['host'])
        && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true);
}

function caffemmo_client_guides_normalize($guides, $includeDisabled = false)
{
    if (!is_array($guides)) {
        return [];
    }

    $normalized = [];
    foreach ($guides as $guide) {
        if (!is_array($guide)) {
            continue;
        }

        $title = trim((string) ($guide['title'] ?? ''));
        $url = trim((string) ($guide['url'] ?? ''));
        $enabled = (int) ($guide['enabled'] ?? 1) === 1;
        if ($title === '' || mb_strlen($title, 'UTF-8') > 120 || !caffemmo_client_guides_is_safe_url($url)) {
            continue;
        }
        if (!$includeDisabled && !$enabled) {
            continue;
        }

        $normalized[] = [
            'title' => $title,
            'url' => $url,
            'enabled' => $enabled ? 1 : 0,
        ];
    }

    return $normalized;
}

function caffemmo_client_guides_ensure_setting()
{
    global $CMSNT;

    if (!$CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', ['client_guides'])) {
        $CMSNT->insert('settings', [
            'name' => 'client_guides',
            'value' => json_encode(caffemmo_client_guides_defaults(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}

function caffemmo_client_guides_get($includeDisabled = false)
{
    global $CMSNT;

    $decoded = json_decode((string) $CMSNT->site('client_guides'), true);
    if (!is_array($decoded)) {
        $decoded = caffemmo_client_guides_defaults();
    }

    return caffemmo_client_guides_normalize($decoded, $includeDisabled);
}

function caffemmo_client_guides_save($guides)
{
    global $CMSNT;

    $value = json_encode(array_values($guides), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($value === false) {
        return false;
    }

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', ['client_guides']);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', ['client_guides']);
    }

    return $CMSNT->insert('settings', ['name' => 'client_guides', 'value' => $value]);
}
