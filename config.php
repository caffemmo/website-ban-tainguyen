<?php
$config = [
    'project'       => 'SHOPCLONE7_ENCRYPTION',
    'version'       => '6.4.0',
    'max_time_load' => 4,
    'limit_block_ip_login_client'   => 5,
    'limit_block_login_client'      => 10,
    'tamper_report_url'             => ''
];



$config_listbank = [
    'THESIEURE'      => 'Ví THESIEURE.COM',
    'MOMO'          => 'Ví điện tử MOMO',
    'Zalo Pay'      => 'Ví điện tử Zalo Pay'

];

$admin_roles = [
    'Dashboard'  => [
        'view_license' => 'Xem giấy phép kích hoạt website & số phiên bản website tại Dashboard',
        'view_statistical' => 'Xem thống kê tại Dashboard',
        'view_recent_transactions' => 'Xem đơn hàng & nạp tiền gần đây tại Dashboard'
    ],
    'Logs'  => [
        'view_logs' => 'Xem nhật ký hoạt động',
        'view_transactions' => 'Xem biến động số dư',
        'view_telegram_logs'    => 'Xem nhật ký sử dụng trợ lý Telegram',
        'delete_transactions' => 'Xóa biến động số dư',
        'delete_logs' => 'Xóa nhật ký hoạt động',
        'delete_telegram_logs' => 'Xóa nhật ký sử dụng trợ lý Telegram',
        'delete_bank_logs' => 'Xóa lịch sử ngân hàng'
    ],
    'Security'  => [
        'view_block_ip' => 'Xem danh sách IP bị chặn',
        'edit_block_ip' => 'Thêm, Xóa, Sửa IP bị chặn'
    ],
    'Automation'  => [
        'view_automations' => 'Xem các công việc tự động hóa',
        'edit_automations' => 'Thêm, Xóa, Sửa công việc tự động hóa'
    ],
    'User'  => [
        'view_user' => 'Xem danh sách thành viên',
        'edit_user' => 'Thêm xóa sửa thành viên',
        'login_user' => 'Đăng nhập vào tài khoản thành viên'
    ],
    'Role' => [
        'view_role'     => 'Xem danh sách role',
        'edit_role'     => 'Thêm xóa sửa role'
    ],
    'Payment'   => [
        'view_recharge'    => 'Xem lịch sử nạp tiền, nhận tiền',
        'edit_recharge'    => 'Thêm xóa sửa thông tin nạp tiền'
    ],
    'Affiliate Program'   => [
        'view_affiliate'            => 'Xem lịch sử hoa hồng',
        'view_withdraw_affiliate'   => 'Xem lịch sử rút tiền',
        'edit_withdraw_affiliate'   => 'Chỉnh sửa lịch sử rút tiền',
        'edit_affiliate'            => 'Cấu hình Affiliate'
    ],
    'Email Campaigns'   => [
        'view_email_campaigns'  => 'Xem chiến dịch Email Marketing',
        'edit_email_campaigns'  => 'Thêm xóa sửa chiến dịch Email Marketing'
    ],
    'Coupon'   => [
        'view_coupon'  => 'Xem danh sách và lịch sử mã giảm giá',
        'edit_coupon'  => 'Thêm xóa sửa mã giảm giá'
    ],
    'Promotion'   => [
        'view_promotion'  => 'Xem danh sách mốc nạp khuyến mãi',
        'edit_promotion'  => 'Thêm xóa sửa mốc nạp khuyến mãi'
    ],
    'Blog'   => [
        'view_blog'  => 'Xem danh sách và chuyên mục bài viết',
        'edit_blog'  => 'Thêm xóa sửa chuyên mục và bài viết'
    ],
    'Product' => [
        'view_product'          => 'Xem danh sách chuyên mục, sản phẩm',
        'edit_product'          => 'Thêm xóa sửa chuyên mục, sản phẩm',
        'edit_stock_product'    => 'Quản lý kho hàng sản phẩm',
        'view_orders_product'   => 'Xem lịch sử đơn hàng',
        'refund_orders_product' => 'Hoàn tiền đơn hàng',
        'view_order_product'    => 'Xem chi tiết đơn hàng',
        'delete_order_product'  => 'Xóa đơn hàng',
        'manager_suppliers'     => 'Thêm, xóa, sửa các nhà cung cấp API',
        'view_suppliers'        => 'Xem nhà cung cấp API trong phần đơn hàng',
        'view_sold_product'     => 'Xem toàn bộ tài khoản đã bán'
    ],
    'CTV' => [
        'view_ctv'  => 'Xem danh sách CTV',
        'view_withdraw_ctv' => 'Xem lịch sử rút tiền CTV',
        'edit_withdraw_ctv' => 'Chỉnh sửa lịch sử rút tiền CTV',
        'edit_product_ctv'  => 'Duyệt sản phẩm CTV',
        'delete_product_ctv' => 'Xóa sản phẩm CTV',
        'view_statistics_ctv' => 'Xem thống kê CTV',
        'edit_config_ctv'      => 'Cấu hình CTV Panel'
    ],
    'Menu'  => [
        'view_menu' => 'Xem danh sách menu',
        'edit_menu' => 'Thêm xóa sửa menu'
    ],
    'Language' => [
        'view_lang' => 'Xem danh sách ngôn ngữ',
        'edit_lang' => 'Thêm xóa sửa ngôn ngữ'
    ],
    'Currency'  => [
        'view_currency' => 'Xem danh sách tiền tệ',
        'edit_currency' => 'Thêm xóa sửa tiền tệ'
    ],
    'Setting' => [
        'edit_theme'    => 'Chỉnh sửa giao diện website',
        'edit_setting'  => 'Chỉnh sửa thông tin cài đặt website'
    ]
];

// Danh sách cấu hình các nhà cung cấp
$cron_suppliers = [
    'SHOPCLONE6' => 'shopclone6',
    'SHOPCLONE7' => 'shopclone7',
    'SHOPKEY' => 'shopkey',
    'API_1' => 'api1',
    'API_6' => 'api6',
    'API_14' => 'api14',
    'API_17' => 'api17',
    'API_18' => 'api18',
    'API_19' => 'api19',
    'API_4' => 'api4',
    'API_20' => 'api20',
    'API_9' => 'api9',
    'API_21' => 'api21',
    'API_22' => 'api22',
    'API_23' => 'api23',
    'API_24' => 'api24',
    'API_25' => 'api25',
    'API_26' => 'api26',
    'API_27' => 'api27',
    'API_28' => 'api28',
    'API_29' => 'api29',
    'API_30' => 'api30',
    'API_31' => 'api31',
    'API_32' => 'api32',
    'API_33' => 'api33',
    'API_34' => 'api34',
    'API_35' => 'api35',
    'API_36' => 'api36',
    'API_37' => 'api37',
    'API_38' => 'api38',
    'API_39' => 'api39',
    'API_40' => 'api40',
    'API_41' => 'api41',
    'API_42' => 'api42',
    'API_43' => 'api43',
    'API_44' => 'api44', // Buyer API (X-Buyer-Key header, USDT balance)
    'API_45' => 'api45', // Telegram Buyer API (key dạng tgb_xxx, qua query param)
    'API_46' => 'api46', // Shop Bot API (header X-API-Key, VNĐ balance)
    'API_47' => 'api47', // API Shared mới sử dụng MD5 Signature và cơ chế App ID/App Key
    'API_48' => 'api48', // API tương thích APIv7 - giữ kiểu yêu cầu cũ với api_key, .php endpoint
    'API_49' => 'api49', // API - mua proxy tự động
    'API_50' => 'api50', // Buyer API (Authorization Bearer, walletCurrency, deliveredAccounts)
    'API_51' => 'api51', // Nas Nabi API (X-API-Key header, ok status, balance & orders endpoints)
];



$domain_blacklist = [];
