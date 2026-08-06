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

function caffemmo_client_faqs_defaults()
{
    return [
        [
            'question' => 'Sau khi mua proxy, tôi nhận thông tin ở đâu?',
            'answer' => 'Sau khi đơn hàng hoàn tất, thông tin proxy sẽ được hiển thị trong mục Proxy của tôi và lịch sử đơn hàng của tài khoản.',
            'enabled' => 1,
        ],
        [
            'question' => 'Proxy hỗ trợ những định dạng kết nối nào?',
            'answer' => 'Tùy loại proxy, bạn có thể sử dụng Login / Password hoặc IP whitelist theo cấu hình hiển thị trên trang mua proxy.',
            'enabled' => 1,
        ],
        [
            'question' => 'Tôi có thể gia hạn proxy đã mua không?',
            'answer' => 'Có. Vào mục Proxy của tôi, chọn proxy cần gia hạn và thực hiện theo phần Thiết lập gia hạn.',
            'enabled' => 1,
        ],
        [
            'question' => 'Nếu gặp lỗi khi mua proxy thì phải làm gì?',
            'answer' => 'Bạn hãy kiểm tra số dư và cấu hình đã chọn. Nếu lỗi vẫn còn, vui lòng liên hệ hỗ trợ để được kiểm tra đơn hàng.',
            'enabled' => 1,
        ],
    ];
}

function caffemmo_client_faqs_normalize($faqs, $includeDisabled = false)
{
    if (!is_array($faqs)) {
        return [];
    }

    $normalized = [];
    foreach ($faqs as $faq) {
        if (!is_array($faq)) {
            continue;
        }

        $question = trim((string) ($faq['question'] ?? ''));
        $answer = trim((string) ($faq['answer'] ?? ''));
        $enabled = (int) ($faq['enabled'] ?? 1) === 1;
        if ($question === '' || $answer === '' || mb_strlen($question, 'UTF-8') > 180 || mb_strlen($answer, 'UTF-8') > 4000) {
            continue;
        }
        if (!$includeDisabled && !$enabled) {
            continue;
        }

        $normalized[] = [
            'question' => $question,
            'answer' => $answer,
            'enabled' => $enabled ? 1 : 0,
        ];
    }

    return $normalized;
}

function caffemmo_client_faqs_ensure_setting()
{
    global $CMSNT;

    if (!$CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', ['client_faqs'])) {
        $CMSNT->insert('settings', [
            'name' => 'client_faqs',
            'value' => json_encode(caffemmo_client_faqs_defaults(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}

function caffemmo_client_faqs_get($includeDisabled = false)
{
    global $CMSNT;

    $decoded = json_decode((string) $CMSNT->site('client_faqs'), true);
    if (!is_array($decoded)) {
        $decoded = caffemmo_client_faqs_defaults();
    }

    return caffemmo_client_faqs_normalize($decoded, $includeDisabled);
}

function caffemmo_client_faqs_save($faqs)
{
    global $CMSNT;

    $value = json_encode(array_values($faqs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($value === false) {
        return false;
    }

    $exists = $CMSNT->get_row_safe('SELECT `name` FROM `settings` WHERE `name` = ? LIMIT 1', ['client_faqs']);
    if ($exists) {
        return $CMSNT->update('settings', ['value' => $value], ' `name` = ? ', ['client_faqs']);
    }

    return $CMSNT->insert('settings', ['name' => 'client_faqs', 'value' => $value]);
}
