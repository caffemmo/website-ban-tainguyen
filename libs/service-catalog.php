<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!function_exists('caffemmo_service_catalog')) {
    function caffemmo_service_catalog()
    {
        return [
            'proxy' => [
                'title' => 'Dịch vụ Proxy',
                'items' => [
                    [
                        'key' => 'proxy-buy',
                        'label' => 'Mua Proxy',
                        'short' => 'Chọn loại proxy phù hợp',
                        'description' => 'Mua proxy chính hãng, xem giá trước khi thanh toán và nhận thông tin kết nối tự động.',
                        'url' => base_url('client/proxy-buy'),
                        'action' => 'proxy-buy',
                        'icon' => 'fa-solid fa-cart-shopping',
                        'tone' => 'teal',
                        'requires_login' => true
                    ],
                    [
                        'key' => 'proxy-list',
                        'label' => 'Proxy của tôi',
                        'short' => 'Quản lý proxy đã mua',
                        'description' => 'Theo dõi IP, quốc gia, cổng HTTPS/SOCKS5, hạn dùng và trạng thái tự động gia hạn.',
                        'url' => base_url('client/proxy-list'),
                        'action' => 'proxy-list',
                        'icon' => 'fa-solid fa-server',
                        'tone' => 'blue',
                        'requires_login' => true
                    ],
                    [
                        'key' => 'proxy-renew',
                        'label' => 'Gia hạn Proxy',
                        'short' => 'Gia hạn nhanh, không gián đoạn',
                        'description' => 'Chọn nhiều proxy, xem báo giá và bật tự động gia hạn khi cần.',
                        'url' => base_url('client/proxy-renew'),
                        'action' => 'proxy-renew',
                        'icon' => 'fa-solid fa-arrows-rotate',
                        'tone' => 'green',
                        'requires_login' => true
                    ]
                ]
            ],
            'up-tich-xanh' => [
                'title' => 'Up tích xanh',
                'items' => [
                    [
                        'key' => 'get-link',
                        'label' => 'Get Link Facebook',
                        'short' => 'Lấy link xác minh nhanh',
                        'description' => 'Gửi thông tin qua server để lấy link xác minh Facebook, không lộ API nhà cung cấp.',
                        'url' => base_url('client/up-tich-xanh/get-link'),
                        'action' => 'up-tich-xanh',
                        'service' => 'get-link',
                        'icon' => 'fa-solid fa-link',
                        'tone' => 'teal',
                        'requires_login' => true
                    ],
                    [
                        'key' => 'up-fb',
                        'label' => 'Up tích Facebook',
                        'short' => 'Xác minh tích xanh Facebook',
                        'description' => 'Gửi yêu cầu xác minh Facebook qua server-side proxy và quản lý chi phí bằng ví Caffemmo.',
                        'url' => base_url('client/up-tich-xanh/up-fb'),
                        'action' => 'up-tich-xanh',
                        'service' => 'up-fb',
                        'icon' => 'fa-brands fa-facebook',
                        'tone' => 'blue',
                        'requires_login' => true
                    ],
                    [
                        'key' => 'up-ig',
                        'label' => 'Up tích Instagram',
                        'short' => 'Xác minh tích xanh Instagram',
                        'description' => 'Gửi yêu cầu xác minh Instagram qua server, có trạng thái rõ ràng khi thành công hoặc lỗi.',
                        'url' => base_url('client/up-tich-xanh/up-ig'),
                        'action' => 'up-tich-xanh',
                        'service' => 'up-ig',
                        'icon' => 'fa-brands fa-instagram',
                        'tone' => 'pink',
                        'requires_login' => true
                    ]
                ]
            ]
        ];
    }
}

if (!function_exists('caffemmo_service_catalog_flat')) {
    function caffemmo_service_catalog_flat()
    {
        $items = [];
        foreach (caffemmo_service_catalog() as $groupKey => $group) {
            foreach ($group['items'] as $item) {
                $item['group'] = $groupKey;
                $item['group_title'] = $group['title'];
                $items[] = $item;
            }
        }
        return $items;
    }
}

if (!function_exists('caffemmo_is_service_active')) {
    function caffemmo_is_service_active($item, $currentAction = '', $currentService = '')
    {
        if (($item['action'] ?? '') !== $currentAction) {
            return false;
        }
        if (($item['action'] ?? '') !== 'up-tich-xanh') {
            return true;
        }
        return ($item['service'] ?? 'get-link') === ($currentService ?: 'get-link');
    }
}
