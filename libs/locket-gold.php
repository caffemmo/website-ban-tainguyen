<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!function_exists('locket_gold_setting')) {
    function locket_gold_setting($name, $fallback = '')
    {
        global $CMSNT;
        static $cache = [];

        if (array_key_exists($name, $cache)) {
            return $cache[$name];
        }
        if (!isset($CMSNT) || !is_object($CMSNT)) {
            return $fallback;
        }

        $row = $CMSNT->get_row_safe('SELECT `value` FROM `settings` WHERE `name` = ? LIMIT 1', [$name]);
        $cache[$name] = $row && isset($row['value']) ? trim((string) $row['value']) : $fallback;
        return $cache[$name];
    }
}

if (!function_exists('locket_gold_enabled')) {
    function locket_gold_enabled()
    {
        $setting = locket_gold_setting('locket_gold_enabled', '1');
        return $setting === '' || $setting === '1';
    }
}

if (!function_exists('locket_gold_warranty_days')) {
    function locket_gold_warranty_days()
    {
        $days = locket_gold_setting('locket_gold_warranty_days', '30');
        return is_numeric($days) ? max(1, min(3650, (int) $days)) : 30;
    }
}

if (!function_exists('locket_gold_packages')) {
    function locket_gold_packages()
    {
        $packages = [
            [
                'key' => 'vip-1',
                'label' => 'VIP 1',
                'max_accounts' => 1,
                'setting' => 'locket_gold_price_vip_1',
                'default_price' => 39000,
                'features' => ['Kích hoạt Gold cho 1 tài khoản Locket.', 'Không cần cung cấp mật khẩu tài khoản.', 'Phù hợp cho nhu cầu cá nhân.']
            ],
            [
                'key' => 'vip-2',
                'label' => 'VIP 2',
                'max_accounts' => 2,
                'setting' => 'locket_gold_price_vip_2',
                'default_price' => 49000,
                'features' => ['Kích hoạt tối đa 2 tài khoản.', 'Dễ dàng thay đổi username khi cần.', 'Phù hợp cho bạn bè và gia đình.']
            ],
            [
                'key' => 'lifetime',
                'label' => 'Locket Gold Vĩnh Viễn',
                'max_accounts' => 3,
                'setting' => 'locket_gold_price_lifetime',
                'default_price' => 79000,
                'features' => ['Miễn phí kích hoạt tối đa 3 tài khoản.', 'Duy trì kết nối ổn định trên nhiều thiết bị.', 'Phù hợp cho nhóm nhỏ.']
            ],
            [
                'key' => 'vip-4',
                'label' => 'VIP 4 - Cao Cấp',
                'max_accounts' => 10,
                'setting' => 'locket_gold_price_vip_4',
                'default_price' => 229000,
                'features' => ['Kích hoạt tối đa 10 tài khoản.', 'Phù hợp cho nhóm bạn bè hoặc gia đình.', 'Ưu tiên hỗ trợ khi cần xử lý đơn.']
            ]
        ];

        foreach ($packages as &$package) {
            $storedPrice = locket_gold_setting($package['setting'], '');
            $package['price'] = $storedPrice !== '' && is_numeric($storedPrice)
                ? max(0, (float) $storedPrice)
                : (float) $package['default_price'];
        }
        unset($package);

        return $packages;
    }
}

if (!function_exists('locket_gold_package')) {
    function locket_gold_package($key)
    {
        foreach (locket_gold_packages() as $package) {
            if ($package['key'] === trim((string) $key)) {
                return $package;
            }
        }
        return null;
    }
}

if (!function_exists('locket_gold_normalize_usernames')) {
    function locket_gold_normalize_usernames($input, $maxAccounts)
    {
        $rawItems = is_array($input) ? $input : preg_split('/\R/u', (string) $input);
        $usernames = [];

        foreach ($rawItems as $rawItem) {
            $username = trim((string) $rawItem);
            $username = ltrim($username, '@');
            if ($username === '') {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{1,63}$/', $username)) {
                return ['success' => false, 'message' => 'Username Locket chỉ được chứa chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.'];
            }
            if (!in_array($username, $usernames, true)) {
                $usernames[] = $username;
            }
        }

        if (empty($usernames)) {
            return ['success' => false, 'message' => 'Vui lòng nhập ít nhất một username Locket.'];
        }
        if (count($usernames) > (int) $maxAccounts) {
            return ['success' => false, 'message' => 'Gói này chỉ hỗ trợ tối đa ' . (int) $maxAccounts . ' tài khoản.'];
        }

        return ['success' => true, 'usernames' => $usernames];
    }
}

if (!function_exists('locket_gold_ensure_orders_table')) {
    function locket_gold_ensure_orders_table()
    {
        global $CMSNT;
        if (!isset($CMSNT) || !is_object($CMSNT)) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `locket_gold_orders` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_code` VARCHAR(32) NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `package_key` VARCHAR(40) NOT NULL,
            `package_name` VARCHAR(100) NOT NULL,
            `account_limit` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            `account_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            `usernames` TEXT NOT NULL,
            `charged_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `reference_code` VARCHAR(100) NULL,
            `admin_note` TEXT NULL,
            `admin_id` INT UNSIGNED NULL,
            `refund_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            `completed_at` DATETIME NULL,
            `refunded_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `locket_gold_orders_code` (`order_code`),
            KEY `locket_gold_orders_user_id` (`user_id`),
            KEY `locket_gold_orders_status` (`status`),
            KEY `locket_gold_orders_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $CMSNT->query($sql) !== false;
    }
}

if (!function_exists('locket_gold_order_code')) {
    function locket_gold_order_code()
    {
        return 'LG' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (!function_exists('locket_gold_status_label')) {
    function locket_gold_status_label($status)
    {
        $labels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'completed' => 'Đã hoàn tất',
            'failed' => 'Thất bại',
            'refunded' => 'Đã hoàn tiền'
        ];
        return $labels[(string) $status] ?? 'Không xác định';
    }
}

if (!function_exists('locket_gold_status_class')) {
    function locket_gold_status_class($status)
    {
        $classes = [
            'pending' => 'is-pending',
            'processing' => 'is-processing',
            'completed' => 'is-completed',
            'failed' => 'is-failed',
            'refunded' => 'is-refunded'
        ];
        return $classes[(string) $status] ?? 'is-pending';
    }
}

if (!function_exists('locket_gold_order_warranty_active')) {
    function locket_gold_order_warranty_active($createdAt)
    {
        $createdTimestamp = strtotime((string) $createdAt);
        return $createdTimestamp !== false && time() <= ($createdTimestamp + (locket_gold_warranty_days() * 86400));
    }
}
