<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/libs/helper.php');

$CMSNT = new DB();


// for ($i=0; $i < 10000; $i++) { 
//     echo '100015'.random('0123456789', 9).'|'.random('QWERTYUIOPASDFGHJKLZXCVBNM', 15).'|'.random('qwertyuiopasdfghjklzxcvbnm0123456789QWERTYUIOPASDFGHJKLZXCVBNM', 32).'<br>';
// }



// insert_options('check_time_cron_momo', 0);
// $CMSNT->query(" CREATE TABLE `payment_momo` (
//     `id` int(11) NOT NULL,
//     `method` varchar(55) COLLATE utf8_unicode_ci DEFAULT NULL,
//     `tid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
//     `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
//     `amount` int(11) DEFAULT 0,
//     `received` int(11) DEFAULT 0,
//     `create_gettime` datetime DEFAULT NULL,
//     `create_time` int(11) DEFAULT 0,
//     `user_id` int(11) DEFAULT 0,
//     `notication` int(11) NOT NULL DEFAULT 0
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC ");

// insert_options('momo_number',  '');
// insert_options('momo_name',  '');
// insert_options('momo_token',  '');
// insert_options('momo_notice', '');
// insert_options('momo_status', 0);
// insert_options('script_footer_admin', '');

// $CMSNT->query(" ALTER TABLE `payment_momo` ADD PRIMARY KEY(`id`)    ");
// insert_options('time_cron_suppliers_api19', 0);
// insert_options('cot_so_du_ben_phai', 1);
// $CMSNT->query(" ALTER TABLE `users` ADD `utm_source` VARCHAR(55) NOT NULL DEFAULT 'web' AFTER `status_view_order` ");
// insert_options('time_cron_suppliers_api4', 0);
// $CMSNT->query(" ALTER TABLE `payment_momo` ADD UNIQUE(`tid`) ");
// $CMSNT->query(" ALTER TABLE `products` DROP INDEX `slug` ");
// $CMSNT->query(" ALTER TABLE `products` DROP INDEX `name` ");
// $CMSNT->query(" ALTER TABLE `users` DROP INDEX `token_2fa` ");

//     $CMSNT->query(" CREATE TABLE `deposit_log` (
//         `id` INT(11) NOT NULL AUTO_INCREMENT,
//         `user_id` INT(11) NOT NULL,
//         `method` VARCHAR(255) NULL DEFAULT NULL,
//         `amount` FLOAT NOT NULL DEFAULT 0,
//         `received` FLOAT NOT NULL DEFAULT 0,
//         `create_time` INT(11) NOT NULL,
//         `is_virtual` TINYINT(1) NOT NULL DEFAULT 0,
//         PRIMARY KEY (`id`)
//     )  ");
//     $CMSNT->query(" CREATE TABLE `order_log` (
//         `id` INT NOT NULL AUTO_INCREMENT,
//         `buyer` INT NOT NULL,
//         `product_name` VARCHAR(255) NULL DEFAULT NULL,
//         `pay` FLOAT NOT NULL DEFAULT 0,
//         `amount` INT NOT NULL DEFAULT 0,
//         `create_time` INT(11) NOT NULL,
//         PRIMARY KEY (`id`)
//     ); ");
//     insert_options('status_giao_dich_gan_day', 1);
//     insert_options('content_gd_mua_gan_day', '<b style="color: green;">...{username}</b> mua <b style="color: red;">{amount}</b> <b>{product_name}</b> với giá <b style="color:blue;">{price}</b>');
//     insert_options('content_gd_nap_tien_gan_day', '<b style="color: green;">...{username}</b> thực hiện nạp <b style="color:blue;">{amount}</b> bằng <b style="color:red;">{method}</b> thực nhận <b style="color:blue;">{received}</b>');
//     insert_options('status_tao_gd_ao', 0);
//     insert_options('sl_mua_toi_thieu_gd_ao', 1);
//     insert_options('sl_mua_toi_da_gd_ao', 10);
//     insert_options('toc_do_gd_mua_ao', 5);
//     insert_options('menh_gia_nap_ao_ngau_nhien', '10000
// 20000
// 40000
// 50000
// 60000
// 70000
// 100000
// 200000
// 300000
// 500000
// 400000
// 40000
// 15000
// 25000
// 35000
// 45000
// 55000
// 65000
// 45000
// 100000
// 1500000
// 200000');
//     insert_options('toc_do_gd_nap_ao', 5);
//     insert_options('method_nap_ao', 'ACB
// MB
// USDT
// PayPal');
//     insert_options('tao_gd_ao_sp_het_hang', 1);
//     $CMSNT->query(" ALTER TABLE `order_log` ADD `is_virtual` INT(11) NOT NULL DEFAULT '0' AFTER `create_time` ");
//     insert_options('check_time_cron_cron', 0);
//     insert_options('blog_status', 1);
//     $CMSNT->query(" ALTER TABLE `product_sold` ADD `time_check_live` INT(11) NOT NULL DEFAULT '0' AFTER `create_gettime` ");
//     $CMSNT->query(" ALTER TABLE `product_order` ADD `refund` INT(11) NOT NULL DEFAULT '0' AFTER `trash` ");
//     insert_options('cong_tien_nguoi_ban', 0);
//     insert_options('noti_buy_product', '[{time}] <b>{username}</b> vừa mua {amount} tài khoản {product} với giá {pay} - #{trans_id}');
//     $CMSNT->query(" ALTER TABLE `products` ADD `flag` TEXT NULL DEFAULT NULL AFTER `max` ");
//     $CMSNT->query(" CREATE TABLE `automations` ( `id` INT NOT NULL AUTO_INCREMENT , `name` TEXT NULL DEFAULT NULL , `type` VARCHAR(55) NULL DEFAULT NULL , `product_id` LONGTEXT NULL DEFAULT NULL , `schedule` INT(11) NOT NULL DEFAULT '0' , `other` TEXT NULL DEFAULT NULL , `create_gettime` DATETIME NOT NULL , `update_gettime` DATETIME NOT NULL , PRIMARY KEY (`id`)) ");

//     $CMSNT->query(" ALTER TABLE `payment_momo` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT ");
//     insert_options('check_time_cron_task', 0);
//     insert_options('thoi_gian_mua_cach_nhau', 3);
//     insert_options('max_register_ip', 5);

//     $CMSNT->query(" ALTER TABLE `suppliers` ADD `check_string_api` VARCHAR(55) NOT NULL DEFAULT 'ON' AFTER `update_gettime` ");

//     insert_options('time_cron_suppliers_api20', 0);
//     insert_options('status_menu_tools', 0);
//     insert_options('debug_auto_bank', 0);

//     $CMSNT->query(" ALTER TABLE `product_order` ADD `note` TEXT NULL DEFAULT NULL AFTER `api_transid` ");

//     insert_options('time_cron_suppliers_api9', 0);
//     insert_options('debug_api_suppliers', 1);
//     insert_options('order_by_product_home', 1);
//     insert_options('token_webhook_web2m', '');

//     insert_options('time_cron_suppliers_api21', 0);

//     $CMSNT->query(" ALTER TABLE `products` ADD `text_txt` TEXT NULL DEFAULT NULL AFTER `api_time_update` ");

//     $CMSNT->query(" ALTER TABLE product_die DROP INDEX uid ");
//     $CMSNT->query(" ALTER TABLE product_die ADD UNIQUE (uid) ");

//     insert_options('time_cron_suppliers_api17', 0);

//     $CMSNT->query(" ALTER TABLE `products` ADD `order_by` INT(11) NOT NULL DEFAULT '1' AFTER `text_txt` ");

//     insert_options('api_check_live_gmail', '');
//     insert_options('api_key_check_live_gmail', '');
//     insert_options('time_cron_checklive_gmail', 0);

//     insert_options('time_limit_check_live_gmail', 1800);

//     $CMSNT->query(" ALTER TABLE `product_sold` ADD `type` VARCHAR(55) NULL DEFAULT 'WEB' AFTER `time_check_live` ");

//     insert_options('widget_zalo1_status' , 0);
//     insert_options('widget_zalo1_sdt', '');

//     insert_options('widget_phone1_status' , 0);
//     insert_options('widget_phone1_sdt', '');


//     $CMSNT->query(" CREATE TABLE `payment_flutterwave` ( `id` INT NOT NULL AUTO_INCREMENT , `user_id` INT(11) NOT NULL DEFAULT '0' , `tx_ref` VARCHAR(55) NULL DEFAULT NULL , `amount` FLOAT NOT NULL DEFAULT '0' , `currency` TEXT NULL DEFAULT NULL , `create_gettime` DATETIME NOT NULL , `update_gettime` DATETIME NOT NULL , `status` VARCHAR(55) NOT NULL DEFAULT 'pending' , PRIMARY KEY (`id`)) ");

//     insert_options('flutterwave_status', 0);
//     insert_options('flutterwave_rate', 16);
//     insert_options('flutterwave_currency_code', 'NGN');
//     insert_options('flutterwave_publicKey', '');
//     insert_options('flutterwave_secretKey', '');
//     insert_options('flutterwave_notice', '');

//     insert_options('limit_block_ip_login', 5);
//     insert_options('limit_block_client_login', 10);

//     $CMSNT->query(" CREATE TABLE `failed_attempts` ( `id` INT NOT NULL AUTO_INCREMENT , `ip_address` VARCHAR(45) NULL DEFAULT NULL , `attempts` INT(11) NOT NULL DEFAULT '0' , `create_gettime` DATETIME NOT NULL , `type` VARCHAR(55) NULL DEFAULT NULL , PRIMARY KEY (`id`)) ");

//     insert_options('limit_block_ip_api', 20);
//     insert_options('limit_block_ip_admin_access', 5);

//     insert_options('time_cron_suppliers_api22', 0);
//     insert_options('isPurchaseIpVerified', 0);
//     insert_options('isPurchaseDeviceVerified', 0);

//     $CMSNT->query(" CREATE TABLE `payment_manual` ( `id` INT NOT NULL AUTO_INCREMENT , `icon` TEXT NULL DEFAULT NULL , `title` TEXT NULL DEFAULT NULL , `description` TEXT NULL DEFAULT NULL , `content` LONGTEXT NULL DEFAULT NULL , `display` INT(11) NOT NULL DEFAULT '0' , `create_gettime` DATETIME NOT NULL , `update_gettime` DATETIME NOT NULL , PRIMARY KEY (`id`)) ");
//     $CMSNT->query(" ALTER TABLE `payment_manual` ADD `slug` TEXT NULL DEFAULT NULL AFTER `title` ");

//     $CMSNT->query(" ALTER TABLE `log_ref` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT, add PRIMARY KEY (`id`) ");
//     insert_options('footer_card', '<a href="#"><img src="/public/client/images/payment/jpg/01.jpg" alt="payment"></a>
// <a href="#"><img src="/public/client/images/payment/jpg/02.jpg" alt="payment"></a>
// <a href="#"><img src="/public/client/images/payment/jpg/03.jpg" alt="payment"></a>
// <a href="#"><img src="/public/client/images/payment/jpg/04.jpg" alt="payment"></a>');

// insert_options('notice_orders', '');
// $CMSNT->query(" ALTER TABLE `menu` ADD `parent_id` INT(11) NOT NULL DEFAULT '0' AFTER `id` ");
// insert_options('widget_fbzalo2_status', 0);
// insert_options('widget_fbzalo2_zalo', '');
// insert_options('widget_fbzalo2_fb', '');

if (!column_exists('payment_flutterwave', 'price')) {
    $CMSNT->query(" ALTER TABLE `payment_flutterwave` ADD `price` FLOAT NOT NULL DEFAULT '0' AFTER `amount` ");
}

insert_options('time_cron_suppliers_api23', 0);

insert_options('show_btn_category_home', 0);

if (!column_exists('suppliers', 'sync_category')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD `sync_category` VARCHAR(55) NOT NULL DEFAULT 'OFF' AFTER `update_name` ");
}

if (!column_exists('categories', 'supplier_id')) {
    $CMSNT->query(" ALTER TABLE `categories` ADD `supplier_id` INT(11) NOT NULL DEFAULT '0' AFTER `id_api` ");
}

insert_options('time_cron_suppliers_api24', 0);

insert_options('status_only_ip_login_admin', 1);
insert_options('time_cron_checklive_instagram', 0);
insert_options('check_time_cron_thesieure', 0);
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_thesieure` ( `id` INT NOT NULL AUTO_INCREMENT , `method` VARCHAR(55) NULL DEFAULT NULL , `tid` VARCHAR(55) NOT NULL , `description` TEXT NULL DEFAULT NULL , `amount` INT(11) NOT NULL DEFAULT '0' , `received` INT(11) NOT NULL DEFAULT '0' , `create_gettime` DATETIME NOT NULL , `create_time` INT(11) NOT NULL DEFAULT '0' , `user_id` INT(11) NOT NULL DEFAULT '0' , `notication` INT(11) NOT NULL DEFAULT '0' , PRIMARY KEY (`id`), UNIQUE (`tid`)) ");
insert_options('thesieure_status', 0);
insert_options('thesieure_number', '');
insert_options('thesieure_email', '');
insert_options('thesieure_token', '');
insert_options('thesieure_notice', '');
insert_options('thesieure_name', '');
if (!column_exists('products', 'allow_api')) {
    $CMSNT->query(" ALTER TABLE `products` ADD `allow_api` INT(11) NOT NULL DEFAULT '1' AFTER `order_by` ");
}


if ($CMSNT->site('crypto_token') != '') {
    // đang dùng rồi thì giữ nguyên
    insert_options('crypto_type_api', 'fpayment.co');
} else {
    insert_options('crypto_type_api', 'fpayment.net');
}
insert_options('crypto_merchant_id', '');
insert_options('crypto_api_key', '');

insert_options('time_cron_suppliers_api25', 0);

insert_ip_block('27.75.226.17', 'IP PHÁ HOẠI, KHÔNG ĐƯỢC XÓA');

insert_options('api_check_live_instagram', '');
insert_options('api_key_check_live_instagram', '');
insert_options('time_limit_check_live_instagram', 10);

insert_options('api_check_live_hotmail', '');
insert_options('api_key_check_live_hotmail', '');
insert_options('time_limit_check_live_hotmail', 1800);
insert_options('time_cron_checklive_hotmail', 0);

insert_options('api_check_live_tiktok', '');
insert_options('api_key_check_live_tiktok', '');
insert_options('time_limit_check_live_tiktok', 10);
insert_options('time_cron_checklive_tiktok', 0);

if (!column_exists('users', 'remember_token')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `remember_token` VARCHAR(255) NULL DEFAULT NULL AFTER `token` ");
}

insert_options('isLoginRequiredToViewProduct', 0);

$CMSNT->query(" ALTER TABLE `product_sold` CHANGE `uid` `uid` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");

insert_options('telegram_assistant_status', 0);
insert_options('telegram_assistant_token', '');
insert_options('telegram_assistant_list_username', '');
insert_options('telegram_assistant_secret_token', generateUltraSecureToken(32));
insert_options('telegram_assistant_LicenseKey', '');
if (!column_exists('users', 'reason_banned')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `reason_banned` TEXT NULL DEFAULT NULL AFTER `banned` ");
}

$CMSNT->query(" CREATE TABLE IF NOT EXISTS `telegram_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) DEFAULT NULL,
    `command`  VARCHAR(100) DEFAULT NULL,
    `params`   TEXT DEFAULT NULL,
    `type`     VARCHAR(50) DEFAULT NULL,
    `time`     DATETIME DEFAULT NULL
) ");

if (!column_exists('product_order', 'commission_fee')) {
    $CMSNT->query(" ALTER TABLE `product_order` ADD `commission_fee` FLOAT NOT NULL DEFAULT '0' AFTER `cost` ");
}
if (!column_exists('languages', 'code')) {
    $CMSNT->query(" ALTER TABLE `languages` ADD `code` VARCHAR(55) NULL DEFAULT NULL AFTER `lang` ");
}
insert_options('status_only_device_client', 1);
insert_options('status_only_device_admin', 1);
insert_options('is_uid_visible', 1);
insert_options('list_network_topup_card', 'VIETTEL|Viettel
VINAPHONE|Vinaphone
MOBIFONE|Mobifone
VNMOBI|Vietnamobile
ZING|Zing
VCOIN|Vcoin
GARENA|Garena (chỉ nhận thẻ trên 10k)
');


$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_xipay` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `out_trade_no` VARCHAR(64) NOT NULL,
  `transaction_id` VARCHAR(64) DEFAULT NULL COMMENT 'Mã giao dịch do Xipay trả về',
  `type` VARCHAR(20) DEFAULT NULL COMMENT 'Phương thức thanh toán (alipay, wxpay...)',
  `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thực nhận',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán',
  `param` VARCHAR(255) DEFAULT NULL COMMENT 'Tham số mở rộng',
  `product_name` VARCHAR(255) DEFAULT NULL COMMENT 'Tên sản phẩm/dịch vụ',
  `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `notify_data` TEXT DEFAULT NULL COMMENT 'Lưu dữ liệu notify (nếu cần)',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` INT DEFAULT NULL COMMENT 'ID user trong hệ thống (nếu có)'
) ");
insert_options('gateway_xipay_status', 0);
insert_options('xipay_notice', '');
insert_options('xipay_min', 1);
insert_options('xipay_max', 1000000);
insert_options('gateway_xipay_md5key', '');
insert_options('gateway_xipay_pid', '');
insert_options('gateway_xipay_rate', 3508);
insert_options('gateway_xipay_license', '');
if (!column_exists('payment_xipay', 'notication')) {
    $CMSNT->query(" ALTER TABLE `payment_xipay` ADD `notication` INT(11) NOT NULL DEFAULT '0' AFTER `user_id` ");
}

$CMSNT->query(" CREATE TABLE IF NOT EXISTS admin_request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    request_url TEXT NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    request_params TEXT,
    ip VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    timestamp DATETIME NOT NULL
) ");


insert_options('korapay_status', 0);
insert_options('korapay_secretKey', '');
insert_options('korapay_min', 1);
insert_options('korapay_max', 1000000);
insert_options('korapay_notice', '');
insert_options('korapay_currency_code', 'NGN');
insert_options('korapay_rate', 17);
insert_options('korapay_proxy', '');
insert_options('korapay_license', '');
// Khuyến mãi nạp tiền Korapay - format: mốc_nạp|phần_trăm_khuyến_mãi (mỗi dòng 1 mốc)
insert_options('korapay_promotions', '');


$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_korapay` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thực nhận',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `checkout_url` VARCHAR(255) NOT NULL,
    `notication` INT(11) NOT NULL DEFAULT 0
  ) ");



/* tmweasyapi */
insert_options('tmweasyapi_status', 0);
insert_options('tmweasyapi_username', '');
insert_options('tmweasyapi_password', '');
insert_options('tmweasyapi_con_id', '');
insert_options('tmweasyapi_license', '');
insert_options('tmweasyapi_rate', 756);
insert_options('tmweasyapi_notice', '');
insert_options('tmweasyapi_min', 1);
insert_options('tmweasyapi_max', 1000000);

$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_tmweasyapi` (
`id` INT AUTO_INCREMENT PRIMARY KEY,
`trans_id` VARCHAR(64) NOT NULL,
`price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
`amount` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thanh toán',
`status` TINYINT DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`user_id` INT DEFAULT NULL,
`checkout_url` VARCHAR(255) NOT NULL,
`notication` INT(11) NOT NULL DEFAULT 0
) ");

if (!column_exists('payment_tmweasyapi', 'id_pay')) {
    $CMSNT->query(" ALTER TABLE `payment_tmweasyapi` ADD `id_pay` VARCHAR(55) NULL DEFAULT NULL AFTER `notication` ");
}

insert_options('chatgpt_api_key', '');
insert_options('chatgpt_model', 'gpt-3.5-turbo');


$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_openpix` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL,
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `checkout_url` VARCHAR(255) NOT NULL,
    `notication` INT(11) NOT NULL DEFAULT 0
    ) ");

insert_options('openpix_status', 0);
insert_options('openpix_api_key', '');
insert_options('openpix_HMAC_key', '');
insert_options('openpix_HMAC_key_completed', '');
insert_options('openpix_license', '');
insert_options('openpix_rate', 4357);
insert_options('openpix_notice', '');
insert_options('openpix_min', 1);
insert_options('openpix_max', 1000000);

if (!column_exists('suppliers', 'proxy')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD `proxy` TEXT NULL DEFAULT NULL AFTER `update_name` ");
}
insert_options('limit_block_ip_reset_password', 10);
insert_options('limit_block_ip_otp', 10);
insert_options('limit_block_ip_2fa', 10);
insert_options('task_24h', 0);
insert_options('limit_block_ip_spam', 10);
insert_options('limit_block_ip_payment', 10);


$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_bakong` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL,
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `checkout_url` VARCHAR(255) NOT NULL,
    `notication` INT(11) NOT NULL DEFAULT 0
    ) ");

insert_options('bakong_status', 0);
insert_options('bakong_profile_id', '');
insert_options('bakong_profile_key', '');
insert_options('bakong_license', '');
insert_options('bakong_rate', 25000);
insert_options('bakong_notice', '');
insert_options('bakong_min', 1);
insert_options('bakong_max', 1000000);
insert_options('bakong_proxy', '');

insert_options('icon_hotline', '<i class="fa-solid fa-phone"></i>');
insert_options('icon_address', '<i class="fa-solid fa-location-dot"></i>');
insert_options('icon_email', '<i class="fa-solid fa-envelope"></i>');


insert_options('time_cron_suppliers_api26', 0);

// Drop index only if it exists
$indexCheck = $CMSNT->get_row("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_die' AND INDEX_NAME = 'uid' LIMIT 1");
if ($indexCheck) {
    $CMSNT->query(" ALTER TABLE `product_die` DROP INDEX `uid` ");
}

insert_options('time_cron_suppliers_api27', 0);

insert_options('time_cron_suppliers_api28', 0);

insert_options('time_cron_suppliers_api29', 0);

insert_options('time_cron_suppliers_api30', 0);

insert_options('telegram_proxy', '');
insert_options('telegram_proxy_type', 'SOCKS5');
insert_options('telegram_url', 'https://bypass-telegram.cmsnt.workers.dev/');

insert_options('tax_vat', 0);

insert_options('time_cron_suppliers_api31', 0);
insert_options('google_ads_status', 0);
insert_options('google_ads_id', '');

if (!column_exists('products', 'hide_in_shop')) {
    $CMSNT->query(" ALTER TABLE `products` ADD `hide_in_shop` INT(11) NOT NULL DEFAULT '0' AFTER `allow_api` ");
}

insert_options('auto_refund_order_failed_api', 0);

insert_options('time_cron_suppliers_api32', 0);

if (!column_exists('users', 'ip_whitelist_api')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `ip_whitelist_api` TEXT NULL DEFAULT NULL AFTER `utm_source` ");
}

$CMSNT->query(" ALTER TABLE `users` CHANGE `api_key` `api_key` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");

// Insert dữ liệu cấu hình Captcha new version
if ($CMSNT->site('reCAPTCHA_status') == 0) {
    insert_options('captcha_status', 0);
} else {
    insert_options('captcha_status', 1);
}
insert_options('captcha_type', 'reCAPTCHA');
insert_options('captcha_site_key', $CMSNT->site('reCAPTCHA_site_key'));
insert_options('captcha_secret_key', $CMSNT->site('reCAPTCHA_secret_key'));
insert_options('captcha_modules', 'login,register,forgot_password,verify_2fa,verify_otp');
//
insert_options('tmweasyapi_watermark_text', '');
insert_options('tmweasyapi_watermark_color', '#ff0000');
insert_options('tmweasyapi_watermark_opacity', 0.28);
insert_options('tmweasyapi_watermark_font_size', 0.08);
//
insert_options('limit_check_live_clone', 500);
//
insert_options('isConfirmPolicyRegister', 0);
insert_options('policy_register', '');
//
// Chỉ convert một lần: nếu chưa set flag trong settings thì mới thực thi
insert_options('db_collation_migrated', 0);
if (function_exists('convertDatabaseToUtf8mb4')) {
    $flag = $CMSNT->site('db_collation_migrated');
    if (empty($flag) || (string)$flag !== '1') {
        convertDatabaseToUtf8mb4($CMSNT);
        // Lưu flag để tránh chạy lại nhiều lần
        if ($CMSNT->get_row("SELECT * FROM `settings` WHERE `name` = 'db_collation_migrated' LIMIT 1")) {
            $CMSNT->update('settings', array('value' => '1'), " `name` = 'db_collation_migrated' ");
        } else {
            $CMSNT->insert('settings', array('name' => 'db_collation_migrated', 'value' => '1'));
        }
    }
}
//
if (!column_exists('suppliers', 'rate')) {
    $CMSNT->query("ALTER TABLE `suppliers` ADD `rate` FLOAT NOT NULL DEFAULT '1' AFTER `discount`");
}


// Panel CTV
if (!column_exists('products', 'pending')) {
    $CMSNT->query(" ALTER TABLE `products` ADD `pending` INT(11) NOT NULL DEFAULT '0' AFTER `hide_in_shop` ");
}
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `ctv_withdraw` ( `id` INT NOT NULL AUTO_INCREMENT , `trans_id` VARCHAR(255) NULL DEFAULT NULL , `user_id` INT(11) NOT NULL DEFAULT '0' , `bank` TEXT NULL DEFAULT NULL , `stk` TEXT NULL DEFAULT NULL , `name` TEXT NOT NULL , `amount` FLOAT NOT NULL DEFAULT '0' , `created_at` DATETIME NOT NULL , `updated_at` DATETIME NOT NULL , `status` ENUM('pending','completed','cancel') NOT NULL , `reason` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ");
insert_options('ctv_min_withdraw', 100000); // Số tiền rút tối thiểu
insert_options('ctv_prefix_withdraw', 'CTV');
insert_options('ctv_banks_withdraw', 'USDT
VCB
MBBank
ACB');
insert_options('ctv_notice_withdraw', 'Nội dung thông báo tại trang rút tiền của CTV Panel');
insert_options('ctv_notice', 'Nội dung thông báo tại trang home của CTV Panel');
insert_options('ctv_status', 0);
insert_options('ctv_panel_license', '');
insert_options('ctv_fee_withdraw', 10);
if (!column_exists('ctv_withdraw', 'receive')) {
    $CMSNT->query(" ALTER TABLE `ctv_withdraw` ADD `receive` FLOAT NOT NULL DEFAULT '0' AFTER `amount` ");
}
if (!column_exists('ctv_withdraw', 'fee')) {
    $CMSNT->query(" ALTER TABLE `ctv_withdraw` ADD `fee` FLOAT NOT NULL DEFAULT '0' AFTER `amount` ");
}

//
insert_options('noti_refund_orders', $CMSNT->site('noti_action'));
//
if (!column_exists('suppliers', 'child')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD `child` INT(11) NOT NULL DEFAULT '0' AFTER `proxy` ");
}
if (!column_exists('suppliers', 'isAutoShow')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD `isAutoShow` INT(11) NOT NULL DEFAULT '0' AFTER `proxy` ");
}
//
insert_options('noti_api_out_of_money', '<b>⚠️ API đã hết số dư</b>

<b>Website:</b> {domain}
<b>Người dùng:</b> {username}
<b>Sản phẩm:</b> {product_name} (ID: <code>{product_id}</code>)
<b>Nhà cung cấp:</b> {supplier_name}
<b>Số lượng:</b> {amount} — <b>Tổng:</b> <code>{pay} VND</code>
<b>Thời gian:</b> {time}
<b>IP:</b> {ip}

Vui lòng nạp thêm số dư để hệ thống tiếp tục xử lý.
');

if (!column_exists('categories', 'api_time_update')) {
    $CMSNT->query(" ALTER TABLE `categories` ADD `api_time_update` INT(11) NOT NULL DEFAULT '0' AFTER `create_date` ");
}
insert_options('limit_block_ip_load_products', 30);

insert_options('key_cron_job', '');
//
if (!column_exists('payment_toyyibpay', 'notication')) {
    $CMSNT->query(" ALTER TABLE `payment_toyyibpay` ADD `notication` INT(11) NOT NULL DEFAULT '1' AFTER `reason` ");
}

//
insert_options('crypto_promotions', '');


// ============================================

// Đổi kiểu dữ liệu cho bảng users
$CMSNT->query("ALTER TABLE `users` MODIFY `money` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `users` MODIFY `total_money` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `users` MODIFY `debit` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `users` MODIFY `ref_amount` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `users` MODIFY `ref_price` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `users` MODIFY `ref_total_price` DECIMAL(20,2) NOT NULL DEFAULT 0");
// Đổi kiểu dữ liệu cho bảng dongtien
$CMSNT->query("ALTER TABLE `dongtien` MODIFY `sotientruoc` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `dongtien` MODIFY `sotienthaydoi` DECIMAL(20,2) NOT NULL DEFAULT 0");
$CMSNT->query("ALTER TABLE `dongtien` MODIFY `sotiensau` DECIMAL(20,2) NOT NULL DEFAULT 0");

//
insert_options('popup_vat', 0);
//
if (!column_exists('users', 'vat_info')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `vat_info` TEXT NULL DEFAULT NULL AFTER `ref_total_price` ");
}
//

$CMSNT->query(" ALTER TABLE `payment_bakong` CHANGE `checkout_url` `checkout_url` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");

//

insert_options('time_cron_suppliers_api33', 0);

//
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_pocketfi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã giao dịch nội bộ',
    `payment_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Mã giao dịch từ PocketFi',
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (VND)',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán (NGN)',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái: 0=pending, 1=success, 2=failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `checkout_url` VARCHAR(255) NULL DEFAULT NULL,
    `notication` INT(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('pocketfi_status', 0);
insert_options('pocketfi_api_token', '');
insert_options('pocketfi_business_id', '');
insert_options('pocketfi_min', 100);
insert_options('pocketfi_max', 1000000);
insert_options('pocketfi_notice', '');
insert_options('pocketfi_currency_code', 'NGN');
insert_options('pocketfi_rate', 17);
insert_options('pocketfi_license', '');

$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_paymentpoint` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã giao dịch nội bộ',
    `customer_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Customer ID từ PaymentPoint',
    `reserved_account_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Reserved Account ID',
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (VND)',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán (NGN)',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái: 0=pending, 1=success, 2=failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `account_number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Số tài khoản ảo',
    `account_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tên tài khoản',
    `bank_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tên ngân hàng',
    `bank_code` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Mã ngân hàng',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_trans_id` (`trans_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");
if (!column_exists('payment_paymentpoint', 'webhook_transaction_id')) {
    $CMSNT->query(" ALTER TABLE `payment_paymentpoint` ADD COLUMN `webhook_transaction_id` VARCHAR(255) DEFAULT NULL ");
}
insert_options('paymentpoint_status', 0);
insert_options('paymentpoint_api_secret', '');
insert_options('paymentpoint_api_key', '');
insert_options('paymentpoint_business_id', '');
insert_options('paymentpoint_bank_codes', '20946,20897');
insert_options('paymentpoint_currency_code', 'NGN');
insert_options('paymentpoint_rate', 1);
insert_options('paymentpoint_min', 100);
insert_options('paymentpoint_max', 1000000);
insert_options('paymentpoint_notice', '');
insert_options('paymentpoint_license', '');
insert_options('paymentpoint_name', 'PaymentPoint');
insert_options('paymentpoint_icon', 'mod/img/paymentpoint.png');

// DSocioPay Payment Gateway
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_dsociopay` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã giao dịch nội bộ',
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (VND)',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thanh toán (NGN)',
    `status` TINYINT DEFAULT 0 COMMENT 'Trạng thái: 0=pending, 1=success, 2=failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `account_number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Số tài khoản ảo',
    `account_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tên tài khoản',
    `bank_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tên ngân hàng',
    `webhook_transaction_id` VARCHAR(255) DEFAULT NULL COMMENT 'Mã giao dịch webhook',
    `notication` TINYINT DEFAULT 0 COMMENT 'Trạng thái thông báo: 0=chưa, 1=đã thông báo',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_trans_id` (`trans_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('dsociopay_status', 0);
insert_options('dsociopay_private_key', '');
insert_options('dsociopay_currency_code', 'NGN');
insert_options('dsociopay_rate', 1);
insert_options('dsociopay_notice', '');
insert_options('dsociopay_license', '');
insert_options('dsociopay_name', 'DSocioPay');
insert_options('dsociopay_icon', 'mod/img/dsociopay.png');
insert_options('dsociopay_webhook_secret', generateUltraSecureToken(8));
insert_options('dsociopay_promotions', '');

//
insert_options('isForUpdateBuy', 0);
insert_options('time_cron_suppliers_api34', 0);
insert_options('time_cron_suppliers_api35', 0);
insert_options('time_cron_suppliers_api36', 0);
insert_options('sidebar_vat_invoice_status', 1);
insert_options('time_cron_suppliers_api37', 0);
insert_options('time_cron_suppliers_api38', 0);
insert_options('time_cron_suppliers_api39', 0);
insert_options('time_cron_suppliers_api40', 0);
insert_options('time_cron_suppliers_api41', 0);
insert_options('time_cron_suppliers_api42', 0);
insert_options('time_cron_suppliers_api43', 0);
insert_options('time_cron_suppliers_api44', 0);
insert_options('time_cron_suppliers_api45', 0);
insert_options('time_cron_suppliers_api46', 0);
insert_options('time_cron_suppliers_api47', 0);
insert_options('time_cron_suppliers_api48', 0);
insert_options('time_cron_suppliers_api49', 0);
insert_options('time_cron_suppliers_api50', 0);
insert_options('time_cron_suppliers_api51', 0);
insert_options('time_cron_suppliers_api52', 0);
insert_options('time_cron_suppliers_api53', 0);
insert_options('time_cron_suppliers_api54', 0);

//
if (!column_exists('suppliers', 'notes')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD COLUMN `notes` TEXT NULL DEFAULT NULL COMMENT 'Lưu trữ JSON metadata (sync progress, settings...)' AFTER `update_gettime` ");
}
if (!column_exists('suppliers', 'list_api_id')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD COLUMN `list_api_id` LONGTEXT NULL DEFAULT NULL COMMENT 'Danh sách Product ID cần lấy từ API' AFTER `notes` ");
}

// LemPay Payment Gateway
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_lempay` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã đơn hàng nội bộ (out_trade_no)',
    `trade_no` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Mã giao dịch LemPay',
    `type` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Phương thức: alipay, wxpay, usdt',
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (VND)',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Số tiền thanh toán (CNY)',
    `status` TINYINT DEFAULT 0 COMMENT '0=pending, 1=success, 2=failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `payurl` TEXT NULL DEFAULT NULL COMMENT 'Link thanh toán',
    `notication` INT(11) NOT NULL DEFAULT 0,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_trans_id` (`trans_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('lempay_status', 0);
insert_options('lempay_pid', '');
insert_options('lempay_key', '');
insert_options('lempay_api_url', 'https://a119a.lempay.com');
insert_options('lempay_rate', 3500);
insert_options('lempay_rate_alipay', '');
insert_options('lempay_rate_wxpay', '');
insert_options('lempay_rate_usdt', '');
insert_options('lempay_min', 1);
insert_options('lempay_max', 10000);
insert_options('lempay_notice', '');
insert_options('lempay_license', '');
insert_options('lempay_name', 'LemPay (AliPay, WeChat, USDT)');
insert_options('lempay_icon', 'mod/img/logo-lempay.webp');


// ZiniPay Payment Gateway (Bangladesh - bKash, Nagad)
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_zinipay` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã đơn hàng nội bộ',
    `trade_no` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Invoice ID từ ZiniPay',
    `type` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Phương thức: bkash, nagad...',
    `price` DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (tiền tệ mặc định)',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Số tiền thanh toán (BDT)',
    `status` TINYINT DEFAULT 0 COMMENT '0=pending, 1=success, 2=failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `payurl` TEXT NULL DEFAULT NULL COMMENT 'Link thanh toán',
    `notication` INT(11) NOT NULL DEFAULT 0,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_trans_id` (`trans_id`),
    INDEX `idx_trade_no` (`trade_no`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");
// Nâng cấp cột price cho site đã cài sẵn (hỗ trợ tiền tệ lẻ như USD)
$CMSNT->query(" ALTER TABLE `payment_zinipay` MODIFY `price` DECIMAL(20,2) NOT NULL DEFAULT 0 ");

insert_options('zinipay_status', 0);
insert_options('zinipay_api_key', '');
insert_options('zinipay_api_url', 'https://api.zinipay.com');
insert_options('zinipay_rate', 300);
insert_options('zinipay_min', 10);
insert_options('zinipay_max', 100000);
insert_options('zinipay_notice', '');
insert_options('zinipay_license', '');
insert_options('zinipay_callback_secret', bin2hex(random_bytes(16)));
insert_options('zinipay_name', 'ZiniPay (bKash, Nagad)');
insert_options('zinipay_icon', 'mod/img/logo-zinipay.webp');


// TriPay Payment Gateway (Indonesia)
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `payment_tripay` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trans_id` VARCHAR(64) NOT NULL COMMENT 'Mã đơn hàng nội bộ (merchant_ref)',
    `reference` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Mã tham chiếu TriPay',
    `method` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Phương thức: BRIVA, QRIS, OVO...',
    `price` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận (VND)',
    `amount` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thanh toán (IDR)',
    `status` TINYINT DEFAULT 0 COMMENT '0=pending, 1=success, 2=failed/expired',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT DEFAULT NULL,
    `checkout_url` TEXT NULL DEFAULT NULL COMMENT 'Link thanh toán',
    `notication` INT(11) NOT NULL DEFAULT 0,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_trans_id` (`trans_id`),
    INDEX `idx_reference` (`reference`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('tripay_status', 0);
insert_options('tripay_api_key', '');
insert_options('tripay_private_key', '');
insert_options('tripay_merchant_code', '');
insert_options('tripay_sandbox', 0);
insert_options('tripay_rate', 1);
insert_options('tripay_min', 10000);
insert_options('tripay_max', 10000000);
insert_options('tripay_notice', '');
insert_options('tripay_name', 'TriPay Indonesia');
insert_options('tripay_icon', 'mod/img/logo-tripay.webp');
insert_options('tripay_license', '');

insert_options('time_cron_suppliers_shopkey', 0);

// Kiểu nạp tiền: prefix_id (Prefix + ID) hoặc fullname_transfer (Họ và tên + chuyển tiền)
insert_options('bank_recharge_type', 'prefix_id');
insert_options('bank_recharge_type_license', '');

// Loại API banking: web2m (api.web2m.com) hoặc pay2s (pay2s.vn)
insert_options('bank_api_type', 'web2m');

if (!column_exists('users', 'prefix_fullname')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `prefix_fullname` VARCHAR(255) NULL DEFAULT NULL AFTER `fullname` ");
}

// Trạng thái chờ nhập text trên Telegram Bot (VD: crypto_amount khi user nhập số tiền tùy ý)
// Fullname dùng prefix_fullname == NULL làm flag nên không cần state, nhưng crypto/search thì cần
if (!column_exists('users', 'telegram_state')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `telegram_state` VARCHAR(100) NULL DEFAULT NULL AFTER `telegram_chat_id` ");
}

// Từ khoá tìm kiếm sản phẩm đang lưu của user Telegram (dùng lại khi phân trang kết quả search)
// Không nhét vào callback_data vì giới hạn 64 bytes của Telegram sẽ vỡ khi keyword tiếng Việt dài.
if (!column_exists('users', 'telegram_search')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `telegram_search` VARCHAR(100) NULL DEFAULT NULL AFTER `telegram_state` ");
}

// 🔴 PRIORITY 1 — CRITICAL
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_product_code` ON `product_stock` (`product_code`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_seller` ON `product_stock` (`seller`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_product_code` ON `product_sold` (`product_code`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `product_sold` (`trans_id`(64))");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_buyer` ON `product_sold` (`buyer`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_product_code` ON `product_die` (`product_code`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `dongtien` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_thoigian` ON `dongtien` (`user_id`, `thoigian`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_buyer` ON `product_order` (`buyer`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_product_id` ON `product_order` (`product_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_seller` ON `product_order` (`seller`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_slug` ON `products` (`slug`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_category_status` ON `products` (`category_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_code` ON `products` (`code`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `products` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_supplier_id` ON `products` (`supplier_id`)");
$CMSNT->query("CREATE UNIQUE INDEX IF NOT EXISTS `idx_name` ON `settings` (`name`)");

// 🟡 PRIORITY 2 — HIGH
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `logs` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_status` ON `cards` (`user_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_product` ON `favorites` (`user_id`, `product_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_product_id` ON `product_discount` (`product_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_category_id` ON `posts` (`category_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `aff_log` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `log_ref` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `aff_withdraw` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_ip_type` ON `failed_attempts` (`ip_address`, `type`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_parent_status` ON `categories` (`parent_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `admin_request_logs` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_status` ON `ctv_withdraw` (`user_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_coupon_user` ON `coupon_used` (`coupon_id`, `user_id`)");

// 🟢 PRIORITY 3 — MEDIUM (Payment tables)
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_bank` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_momo` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_thesieure` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_crypto` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_crypto` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_status` ON `payment_flutterwave` (`user_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_status` ON `payment_pm` (`user_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_paypal` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_paypal` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_xipay` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_out_trade_no` ON `payment_xipay` (`out_trade_no`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_korapay` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_korapay` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_openpix` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_openpix` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_bakong` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_bakong` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_tmweasyapi` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_tmweasyapi` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_pocketfi` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_trans_id` ON `payment_pocketfi` (`trans_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_toyyibpay` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `payment_squadco` (`user_id`)");
// Chống double-credit: mỗi transaction_ref chỉ được ghi nhận 1 lần (chặn race condition ở callback_squadco.php)
$CMSNT->query("CREATE UNIQUE INDEX IF NOT EXISTS `uniq_transaction_ref` ON `payment_squadco` (`transaction_ref`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_camp_status` ON `email_sending` (`camp_id`, `status`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `deposit_log` (`user_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_buyer` ON `order_log` (`buyer`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_lang_id` ON `translate` (`lang_id`)");
$CMSNT->query("CREATE INDEX IF NOT EXISTS `idx_username` ON `telegram_logs` (`username`)");


insert_options('smtp_from_email', $CMSNT->site('smtp_email'));

// Email Queue System
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `to_email` VARCHAR(255) NOT NULL,
    `to_name` VARCHAR(255) DEFAULT '',
    `subject` VARCHAR(998) NOT NULL,
    `body` LONGTEXT NOT NULL,
    `priority` TINYINT DEFAULT 3 COMMENT '1=high, 5=low',
    `status` ENUM('pending','processing','sent','failed') DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `max_attempts` INT DEFAULT 3,
    `error_message` TEXT NULL,
    `metadata` TEXT NULL,
    `attachment_data` LONGTEXT NULL COMMENT 'Base64 encoded file content',
    `attachment_name` VARCHAR(255) NULL COMMENT 'Filename for attachment',
    `created_at` DATETIME NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `sent_at` DATETIME NULL,
    `last_attempt_at` DATETIME NULL,
    INDEX `idx_status_priority` (`status`, `priority`, `created_at`),
    INDEX `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('check_time_cron_email_queue', 0);

// Telegram Queue System
$CMSNT->query(" CREATE TABLE IF NOT EXISTS `telegram_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `chat_id` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NULL,
    `message` TEXT NOT NULL,
    `priority` TINYINT DEFAULT 3 COMMENT '1=high, 5=low',
    `status` ENUM('pending','processing','sent','failed') DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `max_attempts` INT DEFAULT 3,
    `error_message` TEXT NULL,
    `metadata` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `sent_at` DATETIME NULL,
    `last_attempt_at` DATETIME NULL,
    INDEX `idx_status_priority` (`status`, `priority`, `created_at`),
    INDEX `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

insert_options('check_time_cron_telegram_queue', 0);

// Preview UID Addon
insert_options('preview_uid_license', '');
if (!column_exists('products', 'preview_uid')) {
    $CMSNT->query(" ALTER TABLE `products` ADD `preview_uid` INT(11) NOT NULL DEFAULT '0' AFTER `hide_in_shop` ");
}


// Tên file install ẩn (dùng cho chức năng Sửa lỗi nhanh trong Dashboard)
insert_options('install_file_name', 'install_' . random('abcdefghijklmnopqrstuvwxyz0123456789', 12) . '.php', 'Tên file install ẩn');

// Thêm cột stt cho bảng languages để hỗ trợ sắp xếp drag-drop
if (!column_exists('languages', 'stt')) {
    $CMSNT->query(" ALTER TABLE `languages` ADD `stt` INT(11) NOT NULL DEFAULT '0' AFTER `code` ");
}

if (!column_exists('suppliers', 'sync_category_image')) {
    $CMSNT->query(" ALTER TABLE `suppliers` ADD `sync_category_image` VARCHAR(10) NOT NULL DEFAULT 'ON' AFTER `sync_category` ");
}

// Telegram Shop Integration
insert_options('telegram_shop_status', 0);
insert_options('telegram_shop_license', '');
insert_options('telegram_shop_bot_token', '');
insert_options('telegram_shop_webhook_code', generateUltraSecureToken(32));
insert_options('telegram_shop_bot_username', '');

// Lưu ngôn ngữ và tiền tệ ưa thích của user Telegram (Bot không có cookie nên lưu DB)
if (!column_exists('users', 'telegram_lang')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `telegram_lang` VARCHAR(10) NULL DEFAULT NULL AFTER `telegram_search` ");
}
if (!column_exists('users', 'telegram_currency')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `telegram_currency` INT NULL DEFAULT NULL AFTER `telegram_lang` ");
}

// Lưu tỷ giá USDT tại thời điểm user tạo hóa đơn nạp crypto (để biết lúc nạp dùng tỷ giá bao nhiêu)
if (!column_exists('payment_crypto', 'exchange_rate')) {
    $CMSNT->query(" ALTER TABLE `payment_crypto` ADD `exchange_rate` DECIMAL(20,2) NOT NULL DEFAULT '0' COMMENT 'Tỷ giá USDT tại thời điểm tạo hóa đơn' AFTER `received` ");
}

// Chat ID Telegram của CTV để nhận thông báo khi có đơn hàng mua sản phẩm
if (!column_exists('users', 'telegram_chat_id')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `telegram_chat_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Chat ID Telegram nhận thông báo đơn hàng CTV' AFTER `telegram_currency` ");
}

// ==========================================
// Google Login - Cột lưu thông tin liên kết Google của user
// ==========================================

// google_id: ID duy nhất từ Google để nhận diện tài khoản
if (!column_exists('users', 'google_id')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `google_id` VARCHAR(191) NULL DEFAULT NULL AFTER `email` ");
    // Unique index để đảm bảo 1 Google ID chỉ liên kết với 1 tài khoản
    $CMSNT->query(" CREATE UNIQUE INDEX IF NOT EXISTS `uniq_google_id` ON `users` (`google_id`) ");
}

// google_linked_at: Thời điểm liên kết tài khoản Google (để tracking)
if (!column_exists('users', 'google_linked_at')) {
    $CMSNT->query(" ALTER TABLE `users` ADD `google_linked_at` DATETIME NULL DEFAULT NULL AFTER `google_id` ");
}

// Settings cho tính năng Google Login (mặc định tắt - an toàn khi chưa cài thư viện)
insert_options('status_google_login', 0);
insert_options('google_login_client_id', '');
insert_options('google_login_client_secret', '');

// ==========================================
// Widget Bảng xếp hạng (Leaderboard) - hiển thị top user mua hàng nhiều nhất
// ==========================================
insert_options('leaderboard_status', 0);
// Các khoảng thời gian BXH được phép hiển thị (lưu dạng CSV: daily,weekly,monthly,all_time)
insert_options('leaderboard_periods', 'daily,weekly,monthly,all_time');
// Số lượng user hiển thị trong BXH
insert_options('leaderboard_limit', 10);

insert_options('time_cron_suppliers_api51', 0);
insert_options('time_cron_suppliers_api52', 0);
insert_options('time_cron_suppliers_api53', 0);
insert_options('time_cron_suppliers_api54', 0);
