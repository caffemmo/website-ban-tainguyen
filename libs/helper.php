<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$CMSNT = new DB;
$timezone = $CMSNT->site('timezone');
if (empty($timezone)) {
    $timezone = 'Asia/Ho_Chi_Minh'; // Default timezone
}
date_default_timezone_set($timezone);
$session_login = $CMSNT->site('session_login');

// Cấu hình session bảo mật
ini_set('session.gc_maxlifetime', $session_login);
ini_set('session.cookie_lifetime', $session_login);

ini_set('session.cookie_secure', '1'); // Chỉ gửi cookie qua HTTPS
ini_set('session.cookie_httponly', '1'); // Chặn truy cập cookie từ JavaScript
ini_set('session.cookie_samesite', 'Lax'); // Lax cho phép redirect từ bên thứ 3 (Google OAuth) nhưng vẫn chống CSRF (chặn POST cross-site)

// Cấu hình session ID bảo mật
ini_set('session.use_strict_mode', '1'); // Chỉ chấp nhận session ID do server tạo
ini_set('session.use_only_cookies', '1'); // Chỉ sử dụng cookie, không qua URL
ini_set('session.use_trans_sid', '0'); // Tắt session ID trong URL

session_start();


$_SERVER['SERVER_NAME'] = check_string($_SERVER['SERVER_NAME'] ?? '');
$_SERVER['HTTP_USER_AGENT'] = check_string($_SERVER['HTTP_USER_AGENT'] ?? '');
$_SERVER['REMOTE_ADDR'] = check_string($_SERVER['REMOTE_ADDR'] ?? '');
$_SERVER['REQUEST_URI'] = check_string($_SERVER['REQUEST_URI'] ?? '');
$_SERVER['REQUEST_METHOD'] = check_string($_SERVER['REQUEST_METHOD'] ?? '');
$_SERVER['HTTP_HOST'] = check_string($_SERVER['HTTP_HOST'] ?? '');


if ($CMSNT->get_row_safe("SELECT * FROM `block_ip` WHERE `ip` = ? AND `banned` = 1", [myip()])) {
    require_once(__DIR__ . '/../views/common/block-ip.php');
    exit();
}



/**
 * ⚡ CACHE SYSTEM - Tối ưu hiệu suất bằng cách cache dữ liệu tĩnh
 */

// Biến global để lưu cache
global $CACHE_DATA;
$CACHE_DATA = [];

/**
 * Lấy tất cả categories với cache
 * @return array Danh sách categories
 */
function get_categories_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['categories_all'])) {
        $CACHE_DATA['categories_all'] = $CMSNT->get_list_safe(
            "SELECT * FROM `categories` WHERE `status` = ? ORDER BY `stt` DESC",
            [1]
        );
    }

    return $CACHE_DATA['categories_all'];
}

/**
 * Lấy categories parent (level 0) với cache
 * @return array Danh sách categories parent
 */
function get_categories_parent_cached()
{
    global $CACHE_DATA;

    if (!isset($CACHE_DATA['categories_parent'])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA['categories_parent'] = array_filter($all_categories, function ($cat) {
            return $cat['parent_id'] == 0;
        });
    }

    return $CACHE_DATA['categories_parent'];
}

/**
 * Lấy categories con theo parent_id với cache
 * @param int $parent_id ID của category cha
 * @return array Danh sách categories con
 */
function get_categories_by_parent_cached($parent_id)
{
    global $CACHE_DATA;

    $cache_key = 'categories_parent_' . $parent_id;

    if (!isset($CACHE_DATA[$cache_key])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA[$cache_key] = array_filter($all_categories, function ($cat) use ($parent_id) {
            return $cat['parent_id'] == $parent_id;
        });
    }

    return $CACHE_DATA[$cache_key];
}

/**
 * Lấy categories NOT parent (parent_id != 0) với cache
 * @return array Danh sách categories không phải parent
 */
function get_categories_not_parent_cached()
{
    global $CACHE_DATA;

    if (!isset($CACHE_DATA['categories_not_parent'])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA['categories_not_parent'] = array_filter($all_categories, function ($cat) {
            return $cat['parent_id'] != 0;
        });
    }

    return $CACHE_DATA['categories_not_parent'];
}

/**
 * Lấy danh sách languages với cache
 * @return array Danh sách languages
 */
function get_languages_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['languages'])) {
        $CACHE_DATA['languages'] = $CMSNT->get_list_safe(
            "SELECT * FROM `languages` WHERE `status` = ? ORDER BY `stt` DESC, `id` DESC",
            [1]
        );
    }

    return $CACHE_DATA['languages'];
}

/**
 * Lấy danh sách currencies với cache
 * @return array Danh sách currencies
 */
function get_currencies_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['currencies'])) {
        $CACHE_DATA['currencies'] = $CMSNT->get_list_safe(
            "SELECT * FROM `currencies` WHERE `display` = ?",
            [1]
        );
    }

    return $CACHE_DATA['currencies'];
}

/**
 * Lấy danh sách payment manual với cache
 * @return array Danh sách payment manual
 */
function get_payment_manual_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['payment_manual'])) {
        $CACHE_DATA['payment_manual'] = $CMSNT->get_list_safe(
            "SELECT * FROM `payment_manual` WHERE `display` = ?",
            [1]
        );
    }

    return $CACHE_DATA['payment_manual'];
}

/**
 * Hàm kiểm tra xem một cột có tồn tại trong bảng hay không
 * @param string $table Tên bảng cần kiểm tra
 * @param string $column Tên cột cần kiểm tra
 * @return bool True nếu cột tồn tại, False nếu không
 */
function column_exists($table, $column)
{
    global $CMSNT;
    // Validate table and column names to prevent SQL injection
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if (empty($table) || empty($column)) {
        return false;
    }

    $result = $CMSNT->get_row("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($result != false);
}

function getCurrencyNameDefault()
{
    return currencyDefault();
}
function getUserAgent(): string
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Làm sạch User-Agent để tránh XSS hoặc injection
    return htmlspecialchars(strip_tags($userAgent), ENT_QUOTES, 'UTF-8');
}
function deleteFolder($folderPath)
{
    if (!is_dir($folderPath)) {
        return false; // Thư mục không tồn tại
    }

    $files = array_diff(scandir($folderPath), ['.', '..']);

    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
    }

    return rmdir($folderPath);
}
function checkBlockIP($type, $time = 15)
{
    global $CMSNT;
    $ip_address = myip();
    if ($type == 'API') {
        $reason = 'Request API sai API KEY quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_api') ? $CMSNT->site('limit_block_ip_api') : 10;  // Số lần thử tối đa
    } elseif ($type == 'LOGIN') {
        $reason = 'Đăng nhập thất bại quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_login') ? $CMSNT->site('limit_block_ip_login') : 10;  // Số lần thử tối đa
    } elseif ($type == 'ADMIN') {
        $reason = 'Đăng nhập Admin thất bại quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_admin_access') ? $CMSNT->site('limit_block_ip_admin_access') : 10;  // Số lần thử tối đa
    } elseif ($type == 'CTV') {
        $reason = 'Đăng nhập CTV Panel thất bại quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_admin_access') ? $CMSNT->site('limit_block_ip_admin_access') : 10;  // Số lần thử tối đa
    } elseif ($type == 'RESET_PASSWORD') {
        $reason = 'Spam khôi phục mật khẩu';
        $max_attempts = $CMSNT->site('limit_block_ip_reset_password') ? $CMSNT->site('limit_block_ip_reset_password') : 10;  // Số lần thử tối đa
    } elseif ($type == 'OTP') {
        $reason = 'Spam OTP';
        $max_attempts = $CMSNT->site('limit_block_ip_otp') ? $CMSNT->site('limit_block_ip_otp') : 10;  // Số lần thử tối đa
    } elseif ($type == '2FA') {
        $reason = 'Spam 2FA';
        $max_attempts = $CMSNT->site('limit_block_ip_2fa') ? $CMSNT->site('limit_block_ip_2fa') : 10;  // Số lần thử tối đa
    } elseif ($type == 'PAYMENT') {
        $reason = 'Spam Tạo hóa đơn nạp tiền quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_payment') ? $CMSNT->site('limit_block_ip_payment') : 10;  // Số lần thử tối đa
    } elseif ($type == 'SCAN') {
        $reason = 'SCAN mò Token quá nhiều lần';
        $max_attempts = 10;  // Số lần thử tối đa
    } elseif ($type == 'IP_NOT_WHITELIST_API') {
        $reason = 'IP không nằm trong danh sách IP Whitelist';
        $max_attempts = 10;  // Số lần thử tối đa
    } elseif ($type == 'LOAD_PRODUCTS') {
        $reason = 'Spam Load Ajax Products';
        $max_attempts = $CMSNT->site('limit_block_ip_load_products') ? $CMSNT->site('limit_block_ip_load_products') : 30;  // Số lần thử tối đa
    } else {
        $reason = 'Spam Request quá nhiều lần';
        $max_attempts = $CMSNT->site('limit_block_ip_spam') ? $CMSNT->site('limit_block_ip_spam') : 10;  // Số lần thử tối đa
    }
    // Thêm log thất bại vào bảng failed_attempts
    $CMSNT->insert("failed_attempts", [
        'ip_address'        => $ip_address,
        'attempts'          => 1,
        'type'              => $type,
        'create_gettime'    => gettime()
    ]);
    // Đếm số lần thất bại trong 15 phút gần nhất
    $attempts = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `failed_attempts` 
        WHERE `ip_address` = ? 
        AND `type` = ?
        AND `create_gettime` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$ip_address, $type, $time]);

    // Nếu số lần thất bại vượt quá giới hạn
    if ($attempts['total'] >= $max_attempts) {
        // Thêm vào danh sách block
        $CMSNT->insert('block_ip', [
            'ip' => $ip_address,
            'attempts' => $attempts['total'],
            'create_gettime' => gettime(),
            'banned' => 1,
            'reason' => __($reason)
        ]);
        // Xóa tất cả log thất bại của IP này
        $CMSNT->remove('failed_attempts', " `ip_address` = '$ip_address' AND `type` = '$type'");
        return json_encode(['status' => 'error', 'msg' => __('IP của bạn đã bị khóa. Vui lòng thử lại sau.')]);
    }
}

function checkDomainAPI($domain, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cmsnt.co/checkdomain.php?domain={$domain}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);


    $data = curl_exec($ch);
    curl_close($ch);
    $checkdomain = json_decode($data, true);
    if ($checkdomain['status'] == false) {
        return [
            'msg' => $checkdomain['msg'],
            'status' => false
        ];
    }
    return [
        'msg' => '',
        'status' => true
    ];
}



function display_method_xipay($method)
{
    $method = htmlspecialchars($method);
    $output = '';

    switch (strtolower($method)) {
        case 'alipay':
            $output = '<span class="d-inline-flex align-items-center border rounded p-2">';
            $output .= '<i class="fab fa-alipay text-primary fa-2x me-2"></i>';
            $output .= '<span class="fs-7 text-primary">' . __('Alipay') . '</span>';
            $output .= '</span>';
            break;

        case 'wxpay':
            $output = '<span class="d-inline-flex align-items-center border rounded p-2">';
            $output .= '<i class="fab fa-weixin text-success fa-2x me-2"></i>';
            $output .= '<span class="fs-7 text-success">' . __('WeChat Pay') . '</span>';
            $output .= '</span>';
            break;

        default:
            break;
    }

    return $output;
}

function generateUltraSecureToken($length = 32)
{
    $randomBytes = random_bytes($length);
    return bin2hex($randomBytes);
}

function generateRememberToken($currentToken, $storedIp)
{
    // Tạo token mới nếu token trống
    if (empty($currentToken)) {
        return bin2hex(random_bytes(64));
    }
    return $currentToken;
}

function isSecureCookie($name)
{
    if (isset($_COOKIE[$name])) {
        return true;
    } else {
        false;
    }
}

function setSecureCookie($name, $value)
{
    global $CMSNT;

    // Kiểm tra xem có đang chạy trên HTTPS không
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // Cấu hình cookie bảo mật
    $options = [
        'expires' => time() + $CMSNT->site('session_login'),
        'path' => '/',
        'domain' => '', // Có thể set domain cụ thể nếu cần
        'secure' => $is_https, // Chỉ gửi qua HTTPS
        'httponly' => true, // Chặn JavaScript access
        'samesite' => 'Lax' // Cho phép redirect từ bên thứ 3 nhưng vẫn bảo mật
    ];

    return setcookie($name, $value, $options);
}

function insert_options($name, $value)
{
    global $CMSNT;
    if (!$CMSNT->get_row_safe("SELECT * FROM `settings` WHERE `name` = ?", [$name])) {
        $CMSNT->insert("settings", [
            'name'  => $name,
            'value' => $value
        ]);
    }
}

//
$host = $_SERVER['HTTP_HOST'] ?? '';
$host = check_string($host);
$domains = $host . ',' . 'www.' . $host;
insert_options('domains', $domains);
insert_options('telegram_proxy_type', 'HTTP');
insert_options('home_page', 'home');
insert_options('maintenance_title', 'Hệ thống đang nâng cấp');
insert_options('maintenance_content', 'Chúng tôi đang thực hiện bảo trì và nâng cấp hệ thống để mang đến trải nghiệm tốt hơn. Vui lòng quay lại sau ít phút.');
//

function insert_ip_block($ip, $reason)
{
    global $CMSNT;
    if (!$CMSNT->get_row_safe("SELECT * FROM `block_ip` WHERE `ip` = ?", [$ip])) {
        $CMSNT->insert('block_ip', [
            'ip'        => check_string($ip),
            'attempts'  => 5,
            'banned'    => 1,
            'reason'    => check_string($reason),
            'create_gettime'    => gettime()
        ]);
    }
    return true;
}
function checkAccessAttempts($max_attempts = 5)
{
    global $CMSNT;
    $ip_address = myip();
    $attempt = $CMSNT->get_row_safe("SELECT * FROM `failed_attempts` WHERE `ip_address` = ? AND `type` = 'Spam Request' ", [$ip_address]);
    // Kiểm tra xem IP đã vượt quá số lần thử và trong khoảng thời gian lockout chưa
    if ($attempt && $attempt['attempts'] >= $max_attempts) {
        // Khóa IP vào bảng banned_ips
        $CMSNT->insert('block_ip', [
            'ip'                => $ip_address,
            'attempts'          => $attempt['attempts'],
            'create_gettime'    => gettime(),
            'banned'            => 1,
            'reason'            => __('Spam Request')
        ]);
        // Xóa IP ra khỏi bảng failed_attempts sau khi đã block
        $CMSNT->remove('failed_attempts', " `ip_address` = ? ", [$ip_address]);
        return true;
    }
    // Nếu chưa đến mức lockout, tăng số lần thử
    if ($attempt) {
        // Cập nhật số lần thất bại
        $CMSNT->cong('failed_attempts', 'attempts', 1, " `ip_address` = ? ", [$ip_address]);
    } else {
        // Thêm bản ghi mới cho IP này
        $CMSNT->insert("failed_attempts", [
            'ip_address'    => $ip_address,
            'attempts'      => 1,
            'type'          => 'Spam Request',
            'create_gettime' => gettime()
        ]);
    }
    return true;
}

function removeSpaces($string)
{
    return str_replace(' ', '', $string);
}

/**
 * Bỏ dấu tiếng Việt
 * @param string $str Chuỗi cần bỏ dấu
 * @return string Chuỗi đã bỏ dấu
 */
function removeVietnameseAccents($str)
{
    if (function_exists('transliterator_transliterate')) {
        $str = transliterator_transliterate('Any-Latin; Latin-ASCII', $str);
    } else {
        $search = [
            'à',
            'á',
            'ạ',
            'ả',
            'ã',
            'â',
            'ầ',
            'ấ',
            'ậ',
            'ẩ',
            'ẫ',
            'ă',
            'ằ',
            'ắ',
            'ặ',
            'ẳ',
            'ẵ',
            'è',
            'é',
            'ẹ',
            'ẻ',
            'ẽ',
            'ê',
            'ề',
            'ế',
            'ệ',
            'ể',
            'ễ',
            'ì',
            'í',
            'ị',
            'ỉ',
            'ĩ',
            'ò',
            'ó',
            'ọ',
            'ỏ',
            'õ',
            'ô',
            'ồ',
            'ố',
            'ộ',
            'ổ',
            'ỗ',
            'ơ',
            'ờ',
            'ớ',
            'ợ',
            'ở',
            'ỡ',
            'ù',
            'ú',
            'ụ',
            'ủ',
            'ũ',
            'ư',
            'ừ',
            'ứ',
            'ự',
            'ử',
            'ữ',
            'ỳ',
            'ý',
            'ỵ',
            'ỷ',
            'ỹ',
            'đ',
            'À',
            'Á',
            'Ạ',
            'Ả',
            'Ã',
            'Â',
            'Ầ',
            'Ấ',
            'Ậ',
            'Ẩ',
            'Ẫ',
            'Ă',
            'Ằ',
            'Ắ',
            'Ặ',
            'Ẳ',
            'Ẵ',
            'È',
            'É',
            'Ẹ',
            'Ẻ',
            'Ẽ',
            'Ê',
            'Ề',
            'Ế',
            'Ệ',
            'Ể',
            'Ễ',
            'Ì',
            'Í',
            'Ị',
            'Ỉ',
            'Ĩ',
            'Ò',
            'Ó',
            'Ọ',
            'Ỏ',
            'Õ',
            'Ô',
            'Ồ',
            'Ố',
            'Ộ',
            'Ổ',
            'Ỗ',
            'Ơ',
            'Ờ',
            'Ớ',
            'Ợ',
            'Ở',
            'Ỡ',
            'Ù',
            'Ú',
            'Ụ',
            'Ủ',
            'Ũ',
            'Ư',
            'Ừ',
            'Ứ',
            'Ự',
            'Ử',
            'Ữ',
            'Ỳ',
            'Ý',
            'Ỵ',
            'Ỷ',
            'Ỹ',
            'Đ'
        ];
        $replace = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'y',
            'y',
            'y',
            'd',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'Y',
            'Y',
            'Y',
            'Y',
            'D'
        ];
        $str = str_replace($search, $replace, $str);
    }
    return $str;
}
function curl_get_contents($url, $timeout = 10)
{
    // Initialize a cURL session
    $ch = curl_init();
    // Set the URL to fetch
    curl_setopt($ch, CURLOPT_URL, $url);
    // Set the timeout for the request
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Return the transfer as a string instead of outputting it directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // Optional: Set a user-agent to mimic a browser request
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
    // Optional: Follow redirects (HTTP 3xx responses)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Execute the request and store the result
    $result = curl_exec($ch);
    // Check for errors
    if (curl_errno($ch)) {
        // If there's an error, return false
        $result = false;
    }
    // Close the cURL session
    curl_close($ch);
    return $result;
}



function remove_html_tags($string)
{
    // Loại bỏ các thẻ ul và li
    $string = preg_replace('/<ul[^>]*>/', '', $string);
    $string = preg_replace('/<\/ul>/', '', $string);
    $string = preg_replace('/<li[^>]*>/', '', $string);
    $string = preg_replace('/<\/li>/', '', $string);

    // Loại bỏ các thẻ b và i
    $string = preg_replace('/<b[^>]*>/', '', $string);
    $string = preg_replace('/<\/b>/', '', $string);
    $string = preg_replace('/<i[^>]*>/', '', $string);
    $string = preg_replace('/<\/i>/', '', $string);

    // Trả về chuỗi đã loại bỏ các thẻ HTML
    return $string;
}
function getDiscount($amount, $product_id)
{
    $CMSNT = new DB;
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_discount` WHERE `min` <= ? AND `product_id` = ? ORDER BY `min` DESC", [$amount, $product_id]) as $discount) {
        return $discount['discount'];
    }
    return 0;
}
function checkPromotion($amount)
{
    global $CMSNT;
    foreach ($CMSNT->get_list_safe("SELECT * FROM `promotions` WHERE `min` <= ? ORDER by `min` DESC", [$amount]) as $promotion) {
        $received = $amount + $amount * $promotion['discount'] / 100;
        return $received;
    }
    return $amount;
}

function parseCryptoPromotionsConfig($config)
{
    $promotions = [];
    $config = trim((string)$config);
    if ($config === '') {
        return $promotions;
    }
    $lines = preg_split('/\r\n|\r|\n/', $config);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode('|', $line);
        if (count($parts) < 2) {
            continue;
        }
        $minAmount = (float)preg_replace('/[^0-9.]/', '', $parts[0]);
        $discount = (float)preg_replace('/[^0-9.]/', '', $parts[1]);
        if ($minAmount > 0 && $discount > 0) {
            $promotions[] = [
                'min' => $minAmount,
                'discount' => $discount
            ];
        }
    }
    if (!empty($promotions)) {
        usort($promotions, function ($a, $b) {
            if ($a['min'] == $b['min']) {
                return ($b['discount'] <=> $a['discount']);
            }
            return ($a['min'] <=> $b['min']);
        });
    }
    return $promotions;
}

// Tính tổng thực nhận khi qua mốc nạp
function calculateCryptoReceivedAmount($amount, $type)
{
    $promotions = parseCryptoPromotionsConfig($type);
    if (!empty($promotions)) {
        usort($promotions, function ($a, $b) {
            if ($a['min'] == $b['min']) {
                return ($b['discount'] <=> $a['discount']);
            }
            return ($b['min'] <=> $a['min']);
        });
    }
    foreach ($promotions as $item) {
        if ($amount >= $item['min']) {
            return $amount + $amount * $item['discount'] / 100;
        }
    }
    return $amount;
}
function admin_msg_success($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire({
        title: "Thành công!",
        text: "' . $text . '",
        icon: "success"
    });
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_error($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thất Bại", "' . $text . '","error");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_warning($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thông Báo", "' . $text . '","warning");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function debit_processing($user_id)
{
    $CMSNT = new DB();
    $User = new users();

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$user_id]);
    if ($getUser && $getUser['debit'] > 0) {
        if ($getUser['money'] >= $getUser['debit']) {
            // ĐỦ TIỀN TRẢ NỢ
            $isTru = $CMSNT->tru('users', 'debit', $getUser['debit'], " `id` = ? ", [$user_id]);
            if ($isTru) {
                $User->RemoveCredits($getUser['id'], $getUser['debit'], __('Thanh toán số tiền ghi nợ'));
                return true;
            }
        } else {
            // KHÔNG ĐỦ TIỀN
            $isTru = $CMSNT->tru('users', 'debit', $getUser['money'], " `id` = ? ", [$user_id]);
            if ($isTru) {
                $User->RemoveCredits($getUser['id'], $getUser['money'], __('Thanh toán số tiền ghi nợ'));
                return true;
            }
        }
    }
    return false;
}
function checkCoupon($product_id, $coupon, $user_id, $money)
{
    global $CMSNT;

    // Validate input
    $coupon_code = trim($coupon);
    $product_id = intval($product_id);
    $user_id = intval($user_id);
    $money = floatval($money);

    if (empty($coupon_code) || $product_id <= 0 || $user_id <= 0 || $money <= 0) {
        return 0;
    }

    // Sử dụng prepared statements - giữ nguyên tên hàm
    $coupon = $CMSNT->get_row_safe(
        "SELECT * FROM `coupons` WHERE `code` = ? AND `min` <= ? AND `max` >= ? AND `used` < `amount`",
        [$coupon_code, $money, $money]
    );

    if ($coupon) {
        $used_count = $CMSNT->num_rows_safe(
            "SELECT * FROM coupon_used WHERE `coupon_id` = ?",
            [$coupon['id']]
        );

        if ($used_count < $coupon['amount']) {
            $already_used = $CMSNT->get_row_safe(
                "SELECT * FROM `coupon_used` WHERE `coupon_id` = ? AND `user_id` = ?",
                [$coupon['id'], $user_id]
            );

            if (!$already_used) {
                if ($coupon['product_id'] == '') {
                    return $money * $coupon['discount'] / 100;
                }
                if (in_array($product_id, json_decode($coupon['product_id'])) == true) {
                    return $money * $coupon['discount'] / 100;
                }
                return 0;
            }
            return 0;
        }
        return 0;
    }
    return 0;
}
function checkPermission($admin_id, $role)
{
    global $CMSNT;

    // Validate input
    $admin_id = intval($admin_id);
    $role = trim($role);

    if ($admin_id <= 0 || empty($role)) {
        return false;
    }
    // cấp độ cao nhất
    if ($admin_id == 99999) {
        return true;
    }
    $row = $CMSNT->get_row("SELECT * FROM `admin_role` WHERE `id` = '" . $admin_id . "' ");
    if ($row && in_array($role, json_decode($row['role'])) == true) {
        return true;
    }
    return false;
}
function getStock($code)
{
    $CMSNT = new DB;
    // Validate input
    $code = validate_string($code);
    if ($code === false) {
        return 0;
    }
    // Sử dụng prepared statements
    $result = $CMSNT->get_row("SELECT COUNT(id) as count FROM `product_stock` WHERE `product_code` = '" . $code . "'");
    return $result ? $result['count'] : 0;
}
function currencyDefault()
{
    $CMSNT = new DB;
    return $CMSNT->get_row_safe(" SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code'];
}
function dirImageProduct($image)
{
    // Kiểm tra xem $image đã có đường dẫn đầy đủ hay chưa
    if (strpos($image, 'http') === 0 || strpos($image, 'assets/') === 0) {
        return $image; // Trả về nguyên đường dẫn nếu là URL hoặc đường dẫn đầy đủ
    }
    // Nếu chỉ là tên file, thêm đường dẫn vào
    $path = 'assets/storage/images/products/' . $image;
    return $path;
}

function generate_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function display_camp($status)
{
    if ($status == 0) {
        return '<span class="badge bg-info">Processing</span>';
    } elseif ($status == 1) {
        return '<span class="badge bg-success">Completed</span>';
    } elseif ($status == 2) {
        return '<span class="badge bg-danger">Cancel</span>';
    } else {
        return '<span class="badge bg-warning">Khác</span>';
    }
}
function display_withdraw($data)
{
    if ($data == 'pending') {
        $show = '<span class="badge bg-warning">Pending</span>';
    } elseif ($data == 'cancel') {
        $show = '<span class="badge bg-danger">Cancel</span>';
    } else if ($data == 'completed') {
        $show = '<span class="badge bg-success">Completed</span>';
    }
    return $show;
}

/**
 * Hiển thị trạng thái rút tiền CTV
 * @param string $status Trạng thái rút tiền (pending, completed, cancel)
 * @return string HTML badge hiển thị trạng thái
 */
function display_ctv_withdraw_status($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning-transparent text-warning">Chờ xử lý</span>';
        case 'completed':
            return '<span class="badge bg-success-transparent text-success">Hoàn thành</span>';
        case 'cancel':
            return '<span class="badge bg-danger-transparent text-danger">Hủy bỏ</span>';
        default:
            return '<span class="badge bg-secondary-transparent text-secondary">Không xác định</span>';
    }
}
if (!function_exists('cal_days_in_month')) {
    function cal_days_in_month($calendar, $month, $year)
    {
        return date('t', mktime(0, 0, 0, $month, 1, $year));
    }
}
function setCurrency($id)
{
    global $CMSNT;

    // Validate input
    $id = validate_int($id, 1);
    if ($id === false) {
        return false;
    }

    if ($row = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? AND `display` = 1", [$id])) {
        $isSet = setcookie('currency', $row['id'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}
function getCurrency()
{
    global $CMSNT;
    if (isset($_COOKIE['currency'])) {
        $currency = validate_int($_COOKIE['currency'], 1);
        if ($currency !== false) {
            $rowcurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? AND `display` = 1", [$currency]);
            if ($rowcurrency) {
                return $rowcurrency['id'];
            }
        }
    }
    $rowcurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `default_currency` = 1", []);
    if ($rowcurrency) {
        return $rowcurrency['id'];
    }
    return false;
}
function display_invoice($data)
{
    if ($data == 'waiting') {
        $show = '<span class="badge bg-warning">Waiting</span>';
    } elseif ($data == 'expired') {
        $show = '<span class="badge bg-danger">Expired</span>';
    } else if ($data == 'completed') {
        $show = '<span class="badge bg-success">Completed</span>';
    } else if ($data == 0) {
        $show = '<span class="badge bg-warning">Waiting</span>';
    } else if ($data == 2) {
        $show = '<span class="badge bg-danger">Expired</span>';
    } else if ($data == 1) {
        $show = '<span class="badge bg-success">Completed</span>';
    } else {
        $show = '<span class="badge bg-danger">Không xác định</span>';
    }
    return $show;
}

function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) && preg_match("/^.{1,253}$/", $domain_name) && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name));
}
function display_domains($data)
{
    if ($data == 1) {
        $show = '<span class="badge bg-success">' . __('Hoạt Động') . '</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-warning">' . __('Đang Xây Dựng') . '</span>';
    } elseif ($data == 2) {
        $show = '<span class="badge bg-danger">' . __('Huỷ') . '</span>';
    } else {
        $show = '<span class="badge bg-danger">Không xác định</span>';
    }
    return $show;
}

function addRef($user_id, $price, $note = '')
{
    $CMSNT = new DB;
    if ($CMSNT->site('status_ref') != 1) {
        return false;
    }
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$user_id]);
    if ($getUser && $getUser['ref_id'] != 0) {
        $price = $price * $CMSNT->site('ck_ref') / 100;
        $CMSNT->cong('users', 'ref_money', $price, " `id` = ? ", [$getUser['ref_id']]);
        $CMSNT->cong('users', 'ref_total_money', $price, " `id` = ? ", [$getUser['ref_id']]);
        $CMSNT->cong('users', 'ref_amount', $price, " `id` = ? ", [$getUser['id']]);
        $CMSNT->insert('log_ref', [
            'user_id'       => $getUser['ref_id'],
            'reason'        => $note,
            'sotientruoc'   => getRowRealtime('users', $getUser['ref_id'], 'ref_money') - $price,
            'sotienthaydoi' => $price,
            'sotienhientai' => getRowRealtime('users', $getUser['ref_id'], 'ref_money'),
            'create_gettime'    => gettime()
        ]);
        return true;
    }
    return false;
}
function sendMessAdmin($my_text)
{
    if ($my_text != '') {
        return sendMessTelegram($my_text);
    }
    return false;
}
function sendMessTelegram($my_text, $token = '', $chat_id = '', $proxy = '', $proxy_type = '')
{
    $CMSNT = new DB;
    if ($my_text == '') {
        return false;
    }
    if ($CMSNT->site('telegram_status') != 1) {
        return false;
    }

    // Sử dụng TelegramQueue để gửi bất đồng bộ
    if (!class_exists('TelegramQueue')) {
        require_once(__DIR__ . '/TelegramQueue.php');
    }

    $telegramQueue = new TelegramQueue();

    // Nếu chat_id rỗng thì dùng mặc định (null)
    $queueChatId = !empty($chat_id) ? $chat_id : null;
    $queueToken = !empty($token) ? $token : null;

    return $telegramQueue->queueMessage($my_text, $queueChatId, $queueToken, 3, [
        'type' => 'legacy_sendMessTelegram',
        'source' => 'helper'
    ]);
}
function getFlag($flag)
{

    if (empty($flag)) {
        return '';
    }
    return '<img width="30px;" src="https://flagicons.lipis.dev/flags/4x3/' . $flag . '.svg">';
}
function claimSpin($user_id, $trans_id, $total_money)
{
    $CMSNT = new DB();
    $USER = new users();
    if ($CMSNT->site('status_spin') == 1) {
        if ($total_money >= $CMSNT->site('condition_spin')) {
            $USER->AddSpin($user_id, 1, 'Nhập 1 SPIN từ đơn hàng #' . $trans_id);
        }
    }
}
function getRandomWeightedElement(array $weightedValues)
{
    $Rand = mt_Rand(1, (int) array_sum($weightedValues));
    foreach ($weightedValues as $key => $value) {
        $Rand -= $value;
        if ($Rand <= 0) {
            return $key;
        }
    }
}
function checkFormatCard($type, $seri, $pin)
{
    $seri = strlen($seri);
    $pin = strlen($pin);
    $data = [];
    if ($type == 'Viettel' || $type == "viettel" || $type == "VT" || $type == "VIETTEL") {
        if ($seri != 11 && $seri != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 13 && $pin != 15) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Mobifone' || $type == "mobifone" || $type == "Mobi" || $type == "MOBIFONE") {
        if ($seri != 15) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'VNMB' || $type == "Vnmb" || $type == "VNM" || $type == "VNMOBI") {
        if ($seri != 16) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Vinaphone' || $type == "vinaphone" || $type == "Vina" || $type == "VINAPHONE") {
        if ($seri != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Garena' || $type == "garena") {
        if ($seri != 9) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 16) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Zing' || $type == "zing" || $type == "ZING") {
        if ($seri != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 9) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Vcoin' || $type == "VTC") {
        if ($seri != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    $data = [
        'status'    => true,
        'msg'       => 'Success'
    ];
    return $data;
}
function active_sidebar_client($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'mobile-menu-active';
        }
    }
    return '';
}
function show_sidebar_client($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active open';
        }
    }
    return '';
}
function show_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active open';
        }
    }
    return '';
}

function parse_order_id($des, $MEMO_PREFIX)
{
    $re = '/' . $MEMO_PREFIX . '\d+/im';
    preg_match_all($re, $des, $matches, PREG_SET_ORDER, 0);
    if (count($matches) == 0) {
        return null;
    }
    // Print the entire match result
    $orderCode = $matches[0][0];
    $prefixLength = strlen($MEMO_PREFIX);
    $orderId = intval(substr($orderCode, $prefixLength));
    return $orderId;
}
function display_status_toyyibpay($status)
{
    if ($status == 0) {
        return '<b style="color:#db7e06;">' . __('Waiting') . '</b>';
    } elseif ($status == 'confirming') {
        return '<b style="color:blue;">' . __('Confirming') . '</b>';
    } elseif ($status == 'confirmed') {
        return '<b style="color:green;">' . __('Confirmed') . '</b>';
    } elseif ($status == 'refunded') {
        return '<b style="color:pink;">' . __('Refunded') . '</b>';
    } elseif ($status == 'expired') {
        return '<b style="color:red;">' . __('Expired') . '</b>';
    } elseif ($status == 2) {
        return '<b style="color:red;">' . __('Failed') . '</b>';
    } elseif ($status == 'partially_paid') {
        return '<b style="color:green;">' . __('Partially Paid') . '</b>';
    } elseif ($status == 1) {
        return '<b style="color:green;">' . __('Finished') . '</b>';
    }
}
// function display_status_crypto($status)
// {
//     if ($status == 'waiting') {
//         return '<b style="color:#db7e06;">'.__('Waiting').'</b>';
//     } elseif ($status == 'confirming') {
//         return '<b style="color:blue;">'.__('Confirming').'</b>';
//     } elseif ($status == 'confirmed') {
//         return '<b style="color:green;">'.__('Confirmed').'</b>';
//     } elseif ($status == 'refunded') {
//         return '<b style="color:pink;">'.__('Refunded').'</b>';
//     } elseif ($status == 'expired') {
//         return '<b style="color:red;">'.__('Expired').'</b>';
//     } elseif ($status == 'failed') {
//         return '<b style="color:red;">'.__('Failed').'</b>';
//     } elseif ($status == 'partially_paid') {
//         return '<b style="color:green;">'.__('Partially Paid').'</b>';
//     } elseif ($status == 'finished') {
//         return '<b style="color:green;">'.__('Finished').'</b>';
//     }
// }
function display_service($status)
{
    if ($status == 0) {
        return '<b style="color:blue;">Đang chờ xử lý</b>';
    } elseif ($status == 1) {
        return '<b style="color:green;">Hoàn tất</b>';
    } elseif ($status == 2) {
        return '<b style="color:red;">Huỷ</b>';
    } else {
        return '<b style="color:yellow;">Khác</b>';
    }
}
function display_card($status)
{
    if ($status == 'pending') {
        return '<span class="badge bg-info">' . __('Đang chờ xử lý') . '</span>';
    } elseif ($status == 'completed') {
        return '<span class="badge bg-success">' . __('Thành công') . '</span>';
    } elseif ($status == 'error') {
        return '<span class="badge bg-danger">' . __('Thất bại') . '</span>';
    } else {
        return '<span class="badge bg-warning">Khác</span>';
    }
}
function display_invoice_text($status)
{
    if ($status == 0) {
        return __('Đang chờ thanh toán');
    } elseif ($status == 1) {
        return __('Đã thanh toán');
    } elseif ($status == 2) {
        return __('Huỷ bỏ');
    } else {
        return __('Khác');
    }
}
// lấy dữ liệu theo thời gian thực
function getRowRealtime($table, $id, $row)
{
    global $CMSNT;
    if ($data = $CMSNT->get_row_safe("SELECT `" . $row . "` FROM `$table` WHERE `id` = ?", [$id])) {
        return $data[$row];
    }
    return false;
}

function get_url()
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $url = "https://";
    } else {
        $url = "http://";
    }
    $url .= $_SERVER['HTTP_HOST'];
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
}
function url()
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị SERVER_NAME hoặc HTTP_HOST
    $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của host
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Sử dụng domain mặc định nếu không hợp lệ
    }

    // Nếu host không nằm trong danh sách domains, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0];
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';

    // Làm sạch REQUEST_URI để tránh lỗi XSS
    $uri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');

    // Trả về URL đầy đủ
    return sprintf("%s://%s%s", $protocol, $host, $uri);
}

function base_url($url = '')
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của HTTP_HOST
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Domain mặc định nếu HTTP_HOST không hợp lệ
    }

    // Nếu HTTP_HOST không nằm trong danh sách, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0]; // Domain mặc định
    }

    // Giữ HTTPS khi website chạy sau reverse proxy hoặc Cloudflare.
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''), 2)[0]));
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || $forwardedProto === 'https';
    $protocol = $isHttps ? 'https' : 'http';

    // Xử lý localhost riêng (nếu cần)
    if ($host === 'localhost') {
        $base = 'http://localhost/CMSNT.CO/SHOPCLONE7';
    } else {
        $base = $protocol . '://' . $host;
    }

    // Trả về URL đầy đủ
    return check_string($base) . '/' . ltrim($url, '/');
}

function base_url_admin($url = '')
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của HTTP_HOST
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Domain mặc định nếu HTTP_HOST không hợp lệ
    }

    // Nếu HTTP_HOST không nằm trong danh sách, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0]; // Domain mặc định
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // Xử lý localhost riêng (nếu cần)
    if ($host === 'localhost') {
        $base = 'http://localhost/CMSNT.CO/SHOPCLONE7';
    } else {
        $base = $protocol . '://' . $host;
    }

    // Kiểm tra và bảo toàn giá trị URL
    $final_url = rtrim(check_string($base), '/') . '/?module=admin&action=' . $url;

    // Trả về URL đầy đủ
    return $final_url;
}

function base_url_ctv($url = '')
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của HTTP_HOST
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Domain mặc định nếu HTTP_HOST không hợp lệ
    }

    // Nếu HTTP_HOST không nằm trong danh sách, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0]; // Domain mặc định
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // Xử lý localhost riêng (nếu cần)
    if ($host === 'localhost') {
        $base = 'http://localhost/CMSNT.CO/SHOPCLONE7';
    } else {
        $base = $protocol . '://' . $host;
    }

    // Kiểm tra và bảo toàn giá trị URL
    $final_url = rtrim(check_string($base), '/') . '/?module=ctv&action=' . $url;

    // Trả về URL đầy đủ
    return $final_url;
}



// mã hoá password
function TypePassword($password)
{
    $CMSNT = new DB();
    if ($CMSNT->site('type_password') == 'md5') {
        return md5($password);
    }
    if ($CMSNT->site('type_password') == 'bcrypt') {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    if ($CMSNT->site('type_password') == 'sha1') {
        return sha1($password);
    }
    return $password;
}
// lấy thông tin user theo id
function getUser($id, $row)
{
    $CMSNT = new DB();
    $result = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$id]);
    return $result ? $result[$row] : null;
}
function validateUsername($username)
{
    // Loại bỏ khoảng trắng đầu/cuối
    $username = trim($username);
    // Kiểm tra username chỉ chứa chữ cái, số, và có độ dài từ 3-20 ký tự
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9]{2,19}$/', $username)) {
        return htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); // Bảo vệ chống XSS
    }
    return false; // Không hợp lệ
}
function validateEmail($email)
{
    // Loại bỏ khoảng trắng đầu/cuối
    $email = trim($email);

    // Kiểm tra email bằng filter_var
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Bảo vệ chống XSS
    }
    return false; // Không hợp lệ
}
// check định dạng số điện thoại
function validatePhone($data)
{
    if (preg_match('/^\+?(\d.*){3,}$/', $data, $matches)) {
        return true;
    } else {
        return false;
    }
}
// get datatime
function gettime()
{
    return date('Y/m/d H:i:s', time());
}

function format_currency2($amount)
{
    $CMSNT = new DB();
    $currency = $CMSNT->site('currency');
    if ($currency == 'USD') {
        return '$' . number_format($amount / $CMSNT->site('usd_rate'), 2, '.', '');
    } elseif ($currency == 'VND') {
        return format_cash($amount) . 'đ';
    } elseif ($currency == 'THB') {
        return format_cash($amount / 645.36) . ' THB';
    }
}

function format_currency($amount)
{
    $amount = validate_float($amount);
    $CMSNT = new DB();
    if (isset($_COOKIE['currency'])) {
        $currency = validate_int($_COOKIE['currency'], 1);
        if ($currency !== false) {
            $rowCurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? AND `display` = 1", [$currency]);
            if ($rowCurrency) {
                if ($rowCurrency['seperator'] == 'comma') {
                    $seperator = ',';
                }
                if ($rowCurrency['seperator'] == 'space') {
                    $seperator = '';
                }
                if ($rowCurrency['seperator'] == 'dot') {
                    $seperator = '.';
                }
                return $rowCurrency['symbol_left'] . number_format($amount / $rowCurrency['rate'], $rowCurrency['decimal_currency'], '.', $seperator) . $rowCurrency['symbol_right'];
            }
        }
    }
    $rowCurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `default_currency` = 1", []);
    if ($rowCurrency) {
        if ($rowCurrency['seperator'] == 'comma') {
            $seperator = ',';
        }
        if ($rowCurrency['seperator'] == 'space') {
            $seperator = '';
        }
        if ($rowCurrency['seperator'] == 'dot') {
            $seperator = '.';
        }
        return $rowCurrency['symbol_left'] . number_format($amount / $rowCurrency['rate'], $rowCurrency['decimal_currency'], '.', $seperator) . $rowCurrency['symbol_right'];
    }
    return format_cash($amount) . 'đ';
}
//show ip
// function myip(){
//     if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
//         $ip_address = $_SERVER['HTTP_CLIENT_IP'];
//     } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
//         $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
//     } else {
//         $ip_address = $_SERVER['REMOTE_ADDR'];
//     }
//     if(isset(explode(',', $ip_address)[1])){
//         return explode(',', $ip_address)[0];
//     }
//     return check_string($ip_address);
// }

function myip()
{
    // Địa chỉ IP mặc định (REMOTE_ADDR)
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    // Kiểm tra các header khác (nếu có)
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Lấy danh sách IP từ X-Forwarded-For
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip_list = array_map('trim', $ip_list); // Loại bỏ khoảng trắng thừa

        // Lấy địa chỉ IP đầu tiên hợp lệ
        foreach ($ip_list as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ip_address = $ip;
                break;
            }
        }
    }
    // Kiểm tra và trả về địa chỉ IP đã xác thực
    return filter_var($ip_address, FILTER_VALIDATE_IP) ? $ip_address : '0.0.0.0';
}

// lọc input
// function check_string($data){
//     // Loại bỏ các ký tự nguy hiểm
//     $data = trim($data);
//     $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

//     // Escape SQL injection
//     $data = addslashes($data);

//     $dangerous_keywords = [
//         'UNION', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
//         'EXEC', 'EXECUTE', 'SCRIPT', '--', '/*', '*/', 'xp_', 'sp_'
//     ];

//     foreach($dangerous_keywords as $keyword) {
//         $data = str_ireplace($keyword, '', $data);
//     }

//     return $data;
// }
function check_string($input)
{
    $data = trim($input);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
// định dạng tiền tệ
function format_cash($number, $suffix = '')
{
    return number_format($number, 0, ',', '.') . "{$suffix}";
}
function create_slug($str)
{
    $unicode = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd' => 'đ',
        'D' => 'Đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    );

    foreach ($unicode as $nonUnicode => $uni) {
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }

    // Loại bỏ các ký tự không hợp lệ (chỉ giữ lại chữ cái, số và dấu gạch ngang)
    $str = preg_replace('/[^\w\s-]/', '', $str);

    // Thay khoảng trắng bằng dấu gạch ngang
    $str = preg_replace('/\s+/', '-', $str);

    return strtolower($str);
}

function checkAddon($id_addon)
{
    $CMSNT = new DB();

    // Validate input
    $id_addon = validate_string($id_addon, 50);
    if ($id_addon === false) {
        return false;
    }

    $domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);
    $addon = $CMSNT->get_row_safe("SELECT * FROM `addons` WHERE `id` = ?", [$id_addon]);
    if ($addon && $addon['purchase_key'] == md5($domain . '|' . $id_addon)) {
        return true;
    }
    return false;
}
function curl_get2($url)
{
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    return file_get_contents($url, false, stream_context_create($arrContextOptions));
}
// curl get
function curl_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function curl_dataPost($url, $dataPost)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $dataPost,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function curl_post($url, $method, $postinfo, $cookie_file_path)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_file_path);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7"
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
    }
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}
function convertTokenToCookie($token)
{
    $html = json_decode(file_get_contents("https://api.facebook.com/method/auth.getSessionforApp?access_token=$token&format=json&new_app_id=350685531728&generate_session_cookies=1"), true);
    $cookie = $html['session_cookies'][0]['name'] . "=" . $html['session_cookies'][0]['value'] . ";" . $html['session_cookies'][1]['name'] . "=" . $html['session_cookies'][1]['value'] . ";" . $html['session_cookies'][2]['name'] . "=" . $html['session_cookies'][2]['value'] . ";" . $html['session_cookies'][3]['name'] . "=" . $html['session_cookies'][3]['value'];
    return $cookie;
}
function senInboxCSM($cookie, $noiDungTinNhan, $idAnh, $idNguoiNhan)
{
    //lấy id người gửi
    preg_match("/c_user=([0-9]+);/", $cookie, $idNguoiGui);
    $idNguoiGui = $idNguoiGui[1];
    //lấy dtsg
    $html =  curl_post('https://m.facebook.com', 'GET', "", $cookie);
    $re = "/<input type=\"hidden\" name=\"fb_dtsg\" value=\"(.*?)\" autocomplete=\"off\" \\/>/";
    preg_match($re, $html, $dtsg);
    $dtsg = $dtsg[1];
    //tách chuỗi thành vòng lặp, lấy từng người nhận ra
    $ex = explode("|", $idNguoiNhan);
    foreach ($ex as $idNguoiNhan) {
        // echo ".$idNguoiNhan.";
        //lấy tids
        $html1 = curl_post("https://m.facebook.com/messages/read/?fbid=$idNguoiNhan&_rdr", 'GET', '', $cookie);
        $re = "/tids=(.*?)\&/";
        preg_match($re, $html1, $tid);
        if (isset($tid[1])) {
            $tid = urldecode($tid[1]);  //encode mã tids lại
            $data = array(
                "fb_dtsg" => "$dtsg",
                "body" => "$noiDungTinNhan",
                "send" => "Gá»­i",
                "photo_ids[0]" => "$idAnh", // Sửa lỗi biến không được khai báo
                "tids" => "$tid",
                "referrer" => "",
                "ctype" => "",
                "cver" => "legacy"
            );
        } else {
            $data = array(
                "fb_dtsg" => "$dtsg",
                "body" => "$noiDungTinNhan",
                "Send" => "Gá»­i",
                "ids[0]" => "$idNguoiNhan",
                "photo" => "",
                "waterfall_source" => "message"
            );
        }
        //Gửi tin nhắn
        $html = curl_post('https://m.facebook.com/messages/send/?icm=1&refid=12', 'POST', http_build_query($data), $cookie);
        $re = preg_match("/send_success/", $html, $rep); //bắt kết quả trả về
        if (isset($rep[0])) {
            ob_flush();
            flush();
            return true;
        } else {
            ob_flush();
            flush();
            return false;
        }
    }
}

// hàm tạo string random
function random($string, $int)
{
    return substr(str_shuffle($string), 0, $int);
}
// Hàm redirect
function redirect($url)
{
    header("Location: {$url}");
    exit();
}

// show active sidebar AdminLTE3
function active_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active';
        }
    }
    return '';
}
function menuopen_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'menu-open';
        }
    }
    return '';
}

// Hàm lấy value từ $_POST
function input_post($key)
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : false;
}

// Hàm lấy value từ $_GET
function input_get($key)
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : false;
}

// Hàm kiểm tra submit
function is_submit($key)
{
    return (isset($_POST['request_name']) && $_POST['request_name'] == $key);
}

function display_mark($data)
{
    if ($data >= 1) {
        $show = '<span class="badge bg-success">Có</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-danger">Không</span>';
    }
    return $show;
}
// display banned
function display_banned($banned)
{
    if ($banned != 1) {
        return '<span class="badge bg-success">Active</span>';
    } else {
        return '<span class="badge bg-danger">Banned</span>';
    }
}
// display online
function display_online($time)
{
    if (time() - $time <= 300) {
        return '<span class="badge bg-success">Online</span>';
    } else {
        return '<span class="badge bg-danger">Offline</span>';
    }
}
// hiển thị cờ quốc gia
function display_flag($data)
{
    return '<img src="https://flagcdn.com/40x30/' . $data . '.png" >';
}
function display_live($data)
{
    if ($data == 'LIVE') {
        $show = '<span class="badge bg-success">LIVE</span>';
    } elseif ($data == 'DIE') {
        $show = '<span class="badge bg-danger">DIE</span>';
    }
    return $show;
}
function display_checklive($data)
{
    if ($data == 1) {
        $show = '<span class="badge bg-success">Có</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-danger">Không</span>';
    }
    return $show;
}
function card24h($telco, $amount, $serial, $pin, $trans_id)
{
    global $CMSNT;
    $partner_id = $CMSNT->site('card_partner_id');
    $partner_key = $CMSNT->site('card_partner_key');
    $url = base64_decode('aHR0cHM6Ly9jYXJkMjRoLmNvbS9jaGFyZ2luZ3dzL3YyP3NpZ249') . md5($partner_key . $pin . $serial) . '&telco=' . $telco . '&code=' . $pin . '&serial=' . $serial . '&amount=' . $amount . '&request_id=' . $trans_id . '&partner_id=' . $partner_id . '&command=charging';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}
// hiển thị trạng thái hiển thị
function display_status_product($data)
{
    if ($data == 1) {
        $show = '<span class="badge bg-success">Hiển thị</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-danger">Ẩn</span>';
    }
    return $show;
}
//display rank admin
function display_role($data)
{
    if ($data == 1) {
        $show = '<span class="badge badge-danger">Admin</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge badge-info">Member</span>';
    }
    return $show;
}
// Hàm show msg
function msg_success($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thành Công", "' . $text . '","success");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_error($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thất Bại", "' . $text . '","error");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_warning($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thông Báo", "' . $text . '","warning");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
//paginationBoostrap
function paginationBoostrap($url, $start, $total, $kmess)
{
    $out[] = '<ul class="pagination">';
    $neighbors = 2;
    if ($start >= $total) {
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    } else {
        $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    }
    $base_link = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="far fa-hand-point-left"></i>');
    if ($start > $kmess * $neighbors) {
        $out[] = sprintf($base_link, 1, '1');
    }
    if ($start > $kmess * ($neighbors + 1)) {
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    }
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
        if ($start >= $kmess * $nCont) {
            $tmpStart = $start - $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    }
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
        if ($start + $kmess * $nCont <= $tmpMaxPages) {
            $tmpStart = $start + $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) {
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    }
    if ($start + $kmess * $neighbors < $tmpMaxPages) {
        $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    }
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="far fa-hand-point-right"></i>
        ');
    }
    $out[] = '</ul>';
    return implode('', $out);
}
function check_img($img)
{
    $filename = $_FILES[$img]['name'];
    $ext = explode(".", $filename);
    $ext = end($ext);
    $valid_ext = array("png", "jpeg", "jpg", "PNG", "JPEG", "JPG", "gif", "GIF", "svg", "SVG", "webp", "WEBP");
    if (in_array($ext, $valid_ext)) {
        return true;
    }
}
function timeAgo($time_ago)
{
    $time_ago = $time_ago;
    $cur_time   = time();
    $time_elapsed   = $cur_time - $time_ago;
    $seconds    = $time_elapsed;
    $minutes    = round($time_elapsed / 60);
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400);
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640);
    $years      = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        if ($seconds < 0) {
            return __('Vừa xong');
        }
        return "$seconds " . __('giây trước');
    }
    //Minutes
    elseif ($minutes <= 60) {
        return "$minutes " . __('phút trước');
    }
    //Hours
    elseif ($hours <= 24) {
        return "$hours " . __('tiếng trước');
    }
    //Days
    elseif ($days <= 7) {
        if ($days == 1) {
            return __('Hôm qua');
        } else {
            return "$days " . __('ngày trước');
        }
    }
    //Weeks
    elseif ($weeks <= 4.3) {
        return "$weeks " . __('tuần trước');
    }
    //Months
    elseif ($months <= 12) {
        return "$months " . __('tháng trước');
    }
    //Years
    else {
        return "$years " . __('năm trước');
    }
}

function timeAgo2($time_ago)
{
    $time_ago   = date("Y-m-d H:i:s", $time_ago);
    $time_ago   = strtotime($time_ago);
    $time_elapsed   = $time_ago;
    $seconds    = $time_elapsed;
    $minutes    = round($time_elapsed / 60);
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400);
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640);
    $years      = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return "$seconds giây";
    }
    //Minutes
    elseif ($minutes <= 60) {
        return "$minutes phút";
    }
    //Hours
    elseif ($hours <= 24) {
        return "$hours tiếng";
    }
    //Days
    elseif ($days <= 7) {
        if ($days == 1) {
            return "$days ngày";
        } else {
            return "$days ngày";
        }
    }
    //Weeks
    elseif ($weeks <= 4.3) {
        return "$weeks tuần";
    }
    //Months
    elseif ($months <= 12) {
        return "$months tháng";
    }
    //Years
    else {
        return "$years năm";
    }
}
function CheckLiveClone($uid)
{
    //$json = json_decode(curl_get("https://graph.facebook.com/".$uid."/picture?redirect=false"), true);
    $json = json_decode(curl_get("https://graph2.facebook.com/v3.3/" . $uid . "/picture?redirect=0"), true);
    if ($json['data']) {
        if (empty($json['data']['height']) && empty($json['data']['width'])) {
            return 'DIE';
        } else {
            return 'LIVE';
        }
    }
    // else if($json['error']){
    //     return 'DIE';
    // }
    else {
        return 'LIVE';
    }
}
function dirToArray($dir)
{
    $result = array();

    $cdir = scandir($dir);
    foreach ($cdir as $key => $value) {
        if (!in_array($value, array(".", ".."))) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
            } else {
                $result[] = $value;
            }
        }
    }

    return $result;
}

function realFileSize($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $size = filesize($path);

    if (!($file = fopen($path, 'rb'))) {
        return false;
    }

    if ($size >= 0) { //Check if it really is a small file (< 2 GB)
        if (fseek($file, 0, SEEK_END) === 0) { //It really is a small file
            fclose($file);
            return $size;
        }
    }

    //Quickly jump the first 2 GB with fseek. After that fseek is not working on 32 bit php (it uses int internally)
    $size = PHP_INT_MAX - 1;
    if (fseek($file, PHP_INT_MAX - 1) !== 0) {
        fclose($file);
        return false;
    }

    $length = 1024 * 1024;
    while (!feof($file)) { //Read the file until end
        $read = fread($file, $length);
        $size = bcadd($size, $length);
    }
    $size = bcsub($size, $length);
    $size = bcadd($size, strlen($read));

    fclose($file);
    return $size;
}
function FileSizeConvert($bytes)
{
    $result = NULL;
    $bytes = floatval($bytes);
    $arBytes = array(
        0 => array(
            "UNIT" => "TB",
            "VALUE" => pow(1024, 4)
        ),
        1 => array(
            "UNIT" => "GB",
            "VALUE" => pow(1024, 3)
        ),
        2 => array(
            "UNIT" => "MB",
            "VALUE" => pow(1024, 2)
        ),
        3 => array(
            "UNIT" => "KB",
            "VALUE" => 1024
        ),
        4 => array(
            "UNIT" => "B",
            "VALUE" => 1
        ),
    );

    foreach ($arBytes as $arItem) {
        if ($bytes >= $arItem["VALUE"]) {
            $result = $bytes / $arItem["VALUE"];
            $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
            break;
        }
    }
    return $result;
}
function GetCorrectMTime($filePath)
{
    $time = filemtime($filePath);

    $isDST = (date('I', $time) == 1);
    $systemDST = (date('I') == 1);

    $adjustment = 0;

    if ($isDST == false && $systemDST == true) {
        $adjustment = 3600;
    } elseif ($isDST == true && $systemDST == false) {
        $adjustment = -3600;
    } else {
        $adjustment = 0;
    }

    return ($time + $adjustment);
}



function DownloadFile($file)
{ // $file = include path
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
}
function getFileType(string $url): string
{
    $filename = explode('.', $url);
    $extension = end($filename);

    switch ($extension) {
        case 'pdf':
            $type = $extension;
            break;
        case 'docx':
        case 'doc':
            $type = 'word';
            break;
        case 'xls':
        case 'xlsx':
            $type = 'excel';
            break;
        case 'mp3':
        case 'ogg':
        case 'wav':
            $type = 'audio';
            break;
        case 'mp4':
        case 'mov':
            $type = 'video';
            break;
        case 'zip':
        case '7z':
        case 'rar':
            $type = 'archive';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
            $type = 'image';
            break;
        default:
            $type = 'alt';
    }

    return $type;
}

function getLocation($ip)
{
    if ($ip = '::1') {
        $data = [
            'country' => 'VN'
        ];
        return $data;
    }
    $url = "http://ipinfo.io/" . $ip;
    $location = json_decode(file_get_contents($url), true);
    return $location;
}
function pagination($url, $start, $total, $kmess)
{
    $out[] = ' <div class="pagination-style-1"><ul class="pagination mb-0">';
    $neighbors = 2;
    if ($start >= $total) $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    else $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    $base_link = '<li class="page-item  "><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="ri-arrow-left-s-line align-middle"></i>');
    if ($start > $kmess * $neighbors) $out[] = sprintf($base_link, 1, '1');
    if ($start > $kmess * ($neighbors + 1)) $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) if ($start >= $kmess * $nCont) {
        $tmpStart = $start - $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) if ($start + $kmess * $nCont <= $tmpMaxPages) {
        $tmpStart = $start + $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    if ($start + $kmess * $neighbors < $tmpMaxPages) $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="ri-arrow-right-s-line align-middle"></i>');
    }
    $out[] = '</ul></div>';
    return implode('', $out);
}

function pagination_client($url, $start, $total, $kmess)
{
    $out[] = ' <div class="paging_simple_numbers"><ul class="pagination">';
    $neighbors = 2;
    if ($start >= $total) $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    else $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    $base_link = '<li class="paginate_button page-item previous "><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="fas fa-long-arrow-alt-left"></i>');
    if ($start > $kmess * $neighbors) $out[] = sprintf($base_link, 1, '1');
    if ($start > $kmess * ($neighbors + 1)) $out[] = '<li class="paginate_button page-item previous disabled"><a class="page-link">...</a></li>';
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) if ($start >= $kmess * $nCont) {
        $tmpStart = $start - $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    $out[] = '<li class="paginate_button page-item previous"><a class="page-link active">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) if ($start + $kmess * $nCont <= $tmpMaxPages) {
        $tmpStart = $start + $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) $out[] = '<li class="paginate_button page-item previous disabled"><a class="page-link">...</a></li>';
    if ($start + $kmess * $neighbors < $tmpMaxPages) $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="fas fa-long-arrow-alt-right"></i>');
    }
    $out[] = '</ul></div>';
    return implode('', $out);
}
function roundMoney($amount)
{
    // Chỉ làm tròn nếu amount > 1000
    if ($amount <= 1000) {
        return $amount;
    }
    // Làm tròn số lên đến hàng chục gần nhất
    $roundedAmount = round($amount, -2);
    // Lấy phần dư của số sau khi làm tròn đến hàng chục gần nhất
    $remainder = $amount - $roundedAmount;
    // Nếu phần dư lớn hơn hoặc bằng 50, làm tròn lên, ngược lại làm tròn xuống
    // Nếu phần dư lớn hơn hoặc bằng 25 và nhỏ hơn 50, làm tròn xuống đến 250
    // Nếu phần dư lớn hơn hoặc bằng 5 và nhỏ hơn 25, làm tròn xuống đến 600
    if ($remainder >= 50) {
        $roundedAmount += 100;
    } elseif ($remainder >= 25) {
        $roundedAmount += 0; // không làm gì cả
    } elseif ($remainder >= 5) {
        $roundedAmount += 0; // không làm gì cả
    }
    return $roundedAmount;
}
function check_path($path)
{
    return preg_replace("/[^A-Za-z0-9_-]/", '', check_string(basename($path)));
}

function checkAddonLicense($licensekey, $project)
{
    // 1. Kiểm tra domain whitelist
    $domain_white = [
        // Thêm các domain được whitelist tại đây
        // "localhost",
        // "127.0.0.1",
        // "shopcuaban.com"
    ];
    $domain = $_SERVER['HTTP_HOST'] ?? 'unknown';

    if (in_array($domain, $domain_white)) {
        // Nếu domain nằm trong whitelist => trả về "kích hoạt" luôn
        return [
            'msg'    => '',
            'status' => true
        ];
    }

    // -----------------------------------------------------------
    // 2. Thực hiện logic kiểm tra giấy phép (tích hợp từ check_license_addon)
    // -----------------------------------------------------------
    $whmcsurl            = 'https://client.cmsnt.co/';
    $licensing_secret_key = $project; // Project
    $localkeydays        = 15;
    $allowcheckfaildays  = 5;
    $check_token         = time() . md5(mt_rand(100000000, mt_getrandmax()) . $licensekey);
    $checkdate           = date("Ymd");
    $domain              = $_SERVER['SERVER_NAME'] ?? 'unknown-domain';
    $usersip             = $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? '127.0.0.1');
    $dirpath             = dirname(__FILE__);
    $verifyfilepath      = 'modules/servers/licensing/verify.php';

    $localkey    = ''; // Trong ví dụ này, ta bỏ localkey = '' vì chưa thấy lưu localkey cũ

    // ===========================================
    // Hàm con: parseLocalKey (nếu cần parse local key cũ)
    // Ở đây, ta tạm bỏ qua parse localkey do code gốc tạm cài localkey=''
    // ===========================================

    // -------------------------------------------
    // Gửi request đến server license
    // -------------------------------------------
    $responseCode = 0;
    $postfields = [
        'licensekey' => $licensekey,
        'domain'     => $domain,
        'ip'         => $usersip,
        'dir'        => $dirpath
    ];
    if ($check_token) {
        $postfields['check_token'] = $check_token;
    }
    $query_string = http_build_query($postfields);

    // Thử gửi cURL
    if (function_exists('curl_exec')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // Nếu không có cURL, fallback fsockopen (ít dùng)
        $responseCodePattern = '/^HTTP\/\d+\.\d+\s+(\d+)/';
        $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
        if ($fp) {
            $newlinefeed = "\r\n";
            $header = "POST " . $whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
            $header .= "Host: " . $whmcsurl . $newlinefeed;
            $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
            $header .= "Content-length: " . @strlen($query_string) . $newlinefeed;
            $header .= "Connection: close" . $newlinefeed . $newlinefeed;
            $header .= $query_string;
            $data = '';
            @stream_set_timeout($fp, 20);
            @fputs($fp, $header);
            $status = @socket_get_status($fp);
            while (!@feof($fp) && $status) {
                $line = @fgets($fp, 1024);
                if (
                    !$responseCode &&
                    preg_match($responseCodePattern, trim($line), $m)
                ) {
                    $responseCode = empty($m[1]) ? 0 : $m[1];
                }
                $data .= $line;
                $status = @socket_get_status($fp);
            }
            @fclose($fp);
        }
    }

    // -------------------------------------------
    // Xử lý kết quả
    // -------------------------------------------
    // Nếu server license không trả về 200 => check local key cũ => tạm bỏ qua, coi như invalid
    if ($responseCode != 200) {
        return [
            'status' => false,
            'msg'    => 'Remote Check Failed (HTTP ' . $responseCode . ')'
        ];
    }

    // Trích xuất kết quả
    preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
    $results = [];
    foreach ($matches[1] as $k => $v) {
        $results[$v] = $matches[2][$k];
    }

    // MD5 check
    if (isset($results['md5hash'])) {
        if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
            return [
                'status' => false,
                'msg'    => 'MD5 Checksum Verification Failed'
            ];
        }
    }

    // -----------------------------------------------------------
    // 3. So khớp status => trả về msg & status (true/false)
    // -----------------------------------------------------------
    $status = $results['status'] ?? 'Invalid'; // Mặc định invalid nếu ko có

    // Mảng thông báo
    $status_messages = [
        'Active'    => ['Kích hoạt giấy phép thành công!',  true],
        'Invalid'   => ['Giấy phép kích hoạt không hợp lệ',  false],
        'Expired'   => ['Giấy phép mã nguồn đã hết hạn, vui lòng gia hạn ngay', false],
        'Suspended' => ['Giấy phép của bạn đã bị tạm ngưng', false],
        'timeout'   => ['Yêu cầu kiểm tra giấy phép đã hết thời gian chờ',  true]
    ];

    if (isset($status_messages[$status])) {
        list($msg, $stt) = $status_messages[$status];
        return [
            'msg'    => $msg,
            'status' => $stt
        ];
    } else {
        // Không match => default
        return [
            'msg'    => '',
            'status' => true
        ];
    }
}



function CMSNT_check_license($licensekey, $localkey = '')
{
    global $config;
    $whmcsurl = 'https://client.cmsnt.co/';
    $licensing_secret_key = $config['project'];
    $localkeydays = 15;
    $allowcheckfaildays = 5;
    $check_token = time() . md5(mt_rand(100000000, mt_getrandmax()) . $licensekey);
    $checkdate = date("Ymd");
    $domain = $_SERVER['SERVER_NAME'];
    $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
    $dirpath = dirname(__FILE__);
    $verifyfilepath = 'modules/servers/licensing/verify.php';
    $localkeyvalid = false;
    if ($localkey) {
        $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
        $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
        $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
        if ($md5hash == md5($localdata . $licensing_secret_key)) {
            $localdata = strrev($localdata); # Reverse the string
            $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
            $localdata = substr($localdata, 32); # Extract License Data
            $localdata = base64_decode($localdata);
            $localkeyresults = json_decode($localdata, true);
            $originalcheckdate = $localkeyresults['checkdate'];
            if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $localkeyvalid = true;
                    $results = $localkeyresults;
                    $validdomains = explode(',', $results['validdomain']);
                    if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validips = explode(',', $results['validip']);
                    if (!in_array($usersip, $validips)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validdirs = explode(',', $results['validdirectory']);
                    if (!in_array($dirpath, $validdirs)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                }
            }
        }
    }
    if (!$localkeyvalid) {
        $responseCode = 0;
        $postfields = array(
            'licensekey' => $licensekey,
            'domain' => $domain,
            'ip' => $usersip,
            'dir' => $dirpath,
        );
        if ($check_token) $postfields['check_token'] = $check_token;
        $query_string = '';
        foreach ($postfields as $k => $v) {
            $query_string .= $k . '=' . urlencode($v) . '&';
        }
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            // $curl_errno = curl_errno($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            // if ($curl_errno == CURLE_OPERATION_TIMEDOUT) {
            //     $results = array();
            //     $results['status'] = 'timeout';
            //     return $results;
            // }
        } else {
            $responseCodePattern = '/^HTTP\/\d+\.\d+\s+(\d+)/';
            $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
            if ($fp) {
                $newlinefeed = "\r\n";
                $header = "POST " . $whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                $header .= "Host: " . $whmcsurl . $newlinefeed;
                $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                $header .= "Content-length: " . @strlen($query_string) . $newlinefeed;
                $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                $header .= $query_string;
                $data = $line = '';
                @stream_set_timeout($fp, 20);
                @fputs($fp, $header);
                $status = @socket_get_status($fp);
                while (!@feof($fp) && $status) {
                    $line = @fgets($fp, 1024);
                    $patternMatches = array();
                    if (
                        !$responseCode
                        && preg_match($responseCodePattern, trim($line), $patternMatches)
                    ) {
                        $responseCode = (empty($patternMatches[1])) ? 0 : $patternMatches[1];
                    }
                    $data .= $line;
                    $status = @socket_get_status($fp);
                }
                @fclose($fp);
            }
        }
        if ($responseCode != 200) {
            $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
            if ($originalcheckdate > $localexpiry) {
                $results = $localkeyresults;
            } else {
                $results = array();
                $results['status'] = "Invalid";
                $results['description'] = "Remote Check Failed";
                return $results;
            }
        } else {
            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
            $results = array();
            foreach ($matches[1] as $k => $v) {
                $results[$v] = $matches[2][$k];
            }
        }
        if (!is_array($results)) {
            die("Invalid License Server Response");
        }
        if (isset($results['md5hash'])) {
            if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                $results['status'] = "Invalid";
                $results['description'] = "MD5 Checksum Verification Failed";
                return $results;
            }
        }
        if ($results['status'] == "Active") {
            $results['checkdate'] = $checkdate;
            $data_encoded = json_encode($results);
            $data_encoded = base64_encode($data_encoded);
            $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
            $data_encoded = strrev($data_encoded);
            $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
            $data_encoded = wordwrap($data_encoded, 80, "\n", true);
            $results['localkey'] = $data_encoded;
        }
        $results['remotecheck'] = true;
    }
    unset($postfields, $data, $matches, $whmcsurl, $licensing_secret_key, $checkdate, $usersip, $localkeydays, $allowcheckfaildays, $md5hash);
    return $results;
}

function checkLicenseKey($licensekey)
{
    $domain_white = [];
    $domain = $_SERVER['HTTP_HOST'];
    if (in_array($domain, $domain_white)) {
        return [
            'msg' => '',
            'status' => true
        ];
    }
    // Gọi hàm kiểm tra giấy phép
    $results = CMSNT_check_license($licensekey, '');
    // Mảng các trạng thái và thông báo tương ứng
    $status_messages = [
        'Active' => ['Kích hoạt giấy phép thành công!', true],
        'Invalid' => ['Giấy phép kích hoạt không hợp lệ', false],
        'Expired' => ['Giấy phép mã nguồn đã hết hạn, vui lòng gia hạn ngay', false],
        'Suspended' => ['Giấy phép của bạn đã bị tạm ngưng', false],
        'timeout' => ['Yêu cầu kiểm tra giấy phép đã hết thời gian chờ', true]
    ];
    // Kiểm tra trạng thái và gán thông báo tương ứng
    if (isset($status_messages[$results['status']])) {
        list($results['msg'], $results['status']) = $status_messages[$results['status']];
    } else {
        $results['msg'] = '';
        $results['status'] = true;
    }
    return $results;
}

function getStatusLicenseKey()
{
    global $CMSNT;
    $results = CMSNT_check_license($CMSNT->site('license_key'), '');
    return $results['status'];
}

/**
 * Kiểm tra IP có trong whitelist của user hay không
 * @param string $user_ip_whitelist - Danh sách IP whitelist của user (cách nhau bởi \n)
 * @param string $client_ip - IP của client cần kiểm tra
 * @return bool - true nếu IP hợp lệ hoặc whitelist trống, false nếu bị chặn
 */
function checkIPWhitelist($user_ip_whitelist, $client_ip)
{
    // Nếu không có whitelist thì cho phép tất cả IP
    if (empty($user_ip_whitelist)) {
        return true;
    }

    // Tách danh sách IP
    $allowed_ips = array_filter(explode("\n", $user_ip_whitelist), function ($ip) {
        return trim($ip) !== '';
    });

    // Chuẩn hóa IP client
    $client_ip = trim($client_ip);

    // Kiểm tra IP có trong danh sách không
    foreach ($allowed_ips as $allowed_ip) {
        $allowed_ip = trim($allowed_ip);
        if ($allowed_ip === $client_ip) {
            return true;
        }
    }

    return false;
}




/**
 * Kiểm tra trạng thái captcha
 * 
 * @return bool True nếu captcha được bật, false nếu tắt
 */
function isCaptchaEnabled()
{
    global $CMSNT;
    return $CMSNT->site('captcha_status') == 1;
}

/**
 * Kiểm tra xem captcha có được bật cho module cụ thể không
 * 
 * @param string $module Tên module (login, register, forgot_password, verify_2fa, verify_otp)
 * @return bool True nếu captcha được bật cho module này
 */
function isCaptchaEnabledForModule($module)
{
    global $CMSNT;

    // Kiểm tra captcha có được bật chung không
    if (!isCaptchaEnabled()) {
        return false;
    }
    // Nếu chưa cấu hình SiteKey thì bỏ qua
    if (getCaptchaSiteKey() == '') {
        return false;
    }

    // Lấy danh sách modules được áp dụng captcha
    $captchaModules = $CMSNT->site('captcha_modules') ?? '';

    // Nếu không có cấu hình modules, mặc định áp dụng cho tất cả (backward compatibility)
    if (empty($captchaModules)) {
        return true;
    }

    // Tách danh sách modules
    $enabledModules = explode(',', $captchaModules);
    $enabledModules = array_map('trim', $enabledModules);

    return in_array($module, $enabledModules);
}

/**
 * Lấy loại captcha được cấu hình
 * 
 * @return string Loại captcha (reCAPTCHA hoặc Cloudflare)
 */
function getCaptchaType()
{
    global $CMSNT;
    return $CMSNT->site('captcha_type') ?: 'reCAPTCHA';
}

/**
 * Lấy Site Key của captcha
 * 
 * @return string Site key cho captcha
 */
function getCaptchaSiteKey()
{
    global $CMSNT;
    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return $CMSNT->site('captcha_site_key');
    } else {
        // Fallback cho reCAPTCHA cũ
        return $CMSNT->site('captcha_site_key') ?: $CMSNT->site('reCAPTCHA_site_key');
    }
}

/**
 * Lấy Secret Key của captcha
 * 
 * @return string Secret key cho captcha
 */
function getCaptchaSecretKey()
{
    global $CMSNT;
    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return $CMSNT->site('captcha_secret_key');
    } else {
        // Fallback cho reCAPTCHA cũ
        return $CMSNT->site('captcha_secret_key') ?: $CMSNT->site('reCAPTCHA_secret_key');
    }
}

/**
 * Xác thực captcha response
 * 
 * @param string $response Response từ captcha
 * @param string $remoteip IP của client (optional)
 * @param string $module Tên module để kiểm tra (optional)
 * @return array Kết quả xác thực với keys: success, error_message
 */
function verifyCaptchaResponse($response, $remoteip = '', $module = '')
{
    global $CMSNT;

    // Kiểm tra captcha có được bật cho module cụ thể không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return ['success' => true, 'error_message' => ''];
    }

    if (!isCaptchaEnabled()) {
        return ['success' => true, 'error_message' => ''];
    }

    if (empty($response)) {
        return ['success' => false, 'error_message' => __('Vui lòng xác minh Captcha')];
    }

    $type = getCaptchaType();
    $secretKey = getCaptchaSecretKey();

    if (empty($secretKey)) {
        return ['success' => false, 'error_message' => __('Captcha chưa được cấu hình đúng cách')];
    }

    try {
        if ($type === 'Cloudflare') {
            // Cloudflare Turnstile verification
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $data = [
                'secret' => $secretKey,
                'response' => $response,
                'remoteip' => $remoteip ?: myip()
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 10
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                return ['success' => false, 'error_message' => __('Không thể xác thực Captcha')];
            }

            $responseData = json_decode($result, true);

            if ($responseData && isset($responseData['success'])) {
                if ($responseData['success'] === true) {
                    return ['success' => true, 'error_message' => ''];
                } else {
                    $errorCodes = $responseData['error-codes'] ?? [];
                    $errorMessage = __('Xác thực Captcha thất bại');

                    // Tùy chỉnh thông báo lỗi dựa trên mã lỗi
                    if (in_array('timeout-or-duplicate', $errorCodes)) {
                        $errorMessage = __('Captcha đã hết hạn hoặc đã được sử dụng');
                    } elseif (in_array('invalid-input-response', $errorCodes)) {
                        $errorMessage = __('Captcha không hợp lệ');
                    }

                    return ['success' => false, 'error_message' => $errorMessage];
                }
            }
        } else {
            // Google reCAPTCHA verification
            $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($secretKey) . "&response=" . urlencode($response);
            if (!empty($remoteip)) {
                $url .= "&remoteip=" . urlencode($remoteip);
            }

            $verify = file_get_contents($url);

            if ($verify === false) {
                return ['success' => false, 'error_message' => __('Không thể xác thực reCAPTCHA')];
            }

            $captcha_success = json_decode($verify, true);

            if ($captcha_success && isset($captcha_success['success'])) {
                if ($captcha_success['success'] === true) {
                    return ['success' => true, 'error_message' => ''];
                } else {
                    $errorCodes = $captcha_success['error-codes'] ?? [];
                    $errorMessage = __('Xác thực reCAPTCHA thất bại');

                    // Tùy chỉnh thông báo lỗi dựa trên mã lỗi
                    if (in_array('timeout-or-duplicate', $errorCodes)) {
                        $errorMessage = __('reCAPTCHA đã hết hạn hoặc đã được sử dụng');
                    } elseif (in_array('invalid-input-response', $errorCodes)) {
                        $errorMessage = __('reCAPTCHA không hợp lệ');
                    }

                    return ['success' => false, 'error_message' => $errorMessage];
                }
            }
        }

        return ['success' => false, 'error_message' => __('Phản hồi Captcha không hợp lệ')];
    } catch (Exception $e) {
        return ['success' => false, 'error_message' => __('Lỗi hệ thống khi xác thực Captcha')];
    }
}

/**
 * Tạo HTML cho captcha widget
 * 
 * @param string $containerId ID của container chứa captcha
 * @param string $module Tên module để kiểm tra (optional)
 * @return string HTML code cho captcha widget
 */
function renderCaptchaWidget($containerId = 'captcha-container', $module = '')
{

    // Kiểm tra captcha có được bật cho module cụ thể không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return '';
    }

    if (!isCaptchaEnabled()) {
        return '';
    }

    $type = getCaptchaType();
    $siteKey = getCaptchaSiteKey();

    if (empty($siteKey)) {
        return '<div class="alert alert-warning">Captcha chưa được cấu hình</div>';
    }

    if ($type === 'Cloudflare') {
        return '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($siteKey) . '" data-callback="onTurnstileSuccess"></div>';
    } else {
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';
    }
}

/**
 * Tạo HTML script tags cho captcha
 * 
 * @param string $module Tên module để kiểm tra (optional)
 * @return string HTML script tags
 */
function renderCaptchaScripts($module = '')
{
    global $CMSNT;

    if (!isCaptchaEnabled()) {
        return '';
    }

    // Nếu có module cụ thể, kiểm tra xem module đó có được bật captcha không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return '';
    }

    // Nếu không có module cụ thể, kiểm tra xem có ít nhất 1 module nào được bật captcha không
    if (empty($module)) {
        $captchaModules = $CMSNT->site('captcha_modules') ?? '';

        // Nếu không có modules nào được cấu hình, mặc định là có (backward compatibility)
        if (empty($captchaModules)) {
            // Backward compatibility - nếu chưa cấu hình modules thì load script
        } else {
            // Nếu có cấu hình nhưng danh sách rỗng, không load script
            $enabledModules = explode(',', $captchaModules);
            $enabledModules = array_filter(array_map('trim', $enabledModules));

            if (empty($enabledModules)) {
                return '';  // Không có module nào được chọn, không load script
            }
        }
    }

    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    } else {
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }
}

/**
 * Chuyển toàn bộ database và tất cả bảng sang utf8mb4_unicode_ci
 * - Đổi charset/collation của database
 * - Convert tất cả bảng hiện có sang utf8mb4 + utf8mb4_unicode_ci
 *
 * @param DB|null $db
 * @return int Số bảng đã cố gắng chuyển đổi
 */
function convertDatabaseToUtf8mb4($db = null)
{
    if ($db === null) {
        global $CMSNT;
        $db = $CMSNT;
    }

    if (!($db instanceof DB)) {
        return 0;
    }

    // Đổi charset/collation cho database
    $databaseName = isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : '';
    if (!empty($databaseName)) {
        $db->query("ALTER DATABASE `" . $databaseName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Lấy toàn bộ danh sách bảng
    $tables = $db->get_list("SHOW TABLES");
    if (empty($tables) || !is_array($tables)) {
        return 0;
    }

    // Cột trả về từ SHOW TABLES có tên dạng: Tables_in_{DBNAME}
    $firstRow = isset($tables[0]) ? $tables[0] : array();
    $tableNameKey = !empty($firstRow) ? array_keys($firstRow)[0] : '';
    if (empty($tableNameKey)) {
        return 0;
    }

    $converted = 0;
    foreach ($tables as $row) {
        if (!isset($row[$tableNameKey])) {
            continue;
        }
        $table = $row[$tableNameKey];

        // Bỏ qua nếu đã đúng collation
        if (!empty($databaseName)) {
            $collationRow = $db->get_row("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $databaseName . "' AND TABLE_NAME = '" . $table . "' LIMIT 1");
            $currentCollation = ($collationRow && isset($collationRow['TABLE_COLLATION'])) ? strtolower($collationRow['TABLE_COLLATION']) : '';
            if ($currentCollation === 'utf8mb4_unicode_ci') {
                continue;
            }
        }

        // Chuyển toàn bộ bảng (bao gồm các cột) sang utf8mb4_unicode_ci
        $db->query("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        // Đặt default charset/collation cho bảng
        $db->query("ALTER TABLE `{$table}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $converted++;
    }

    return $converted;
}

/**
 * ===================================================================
 * CÁC HÀM XỬ LÝ ENCODING AN TOÀN
 * ===================================================================
 * Các hàm này được thiết kế để xử lý encoding dữ liệu một cách an toàn
 * đặc biệt cho các ký tự Unicode như tiếng Trung, tiếng Nhật, v.v.
 */

/**
 * Xử lý encoding an toàn cho dữ liệu từ database
 * @param string $data Dữ liệu cần xử lý
 * @param bool $decode_html Có decode HTML entities không (mặc định true)
 * @return string Dữ liệu đã được xử lý encoding
 */
function safe_encoding($data, $decode_html = true)
{
    if (empty($data)) {
        return '';
    }

    // Đảm bảo dữ liệu là string
    $data = (string)$data;

    // Chuyển đổi encoding về UTF-8
    $data = mb_convert_encoding($data, 'UTF-8', 'auto');

    // Decode HTML entities nếu cần
    if ($decode_html) {
        $data = htmlspecialchars_decode($data, ENT_QUOTES | ENT_HTML5);
        // Đảm bảo kết quả vẫn là UTF-8 sau khi decode
        $data = mb_convert_encoding($data, 'UTF-8', 'auto');
    }

    // Loại bỏ các ký tự không hợp lệ
    $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

    return $data;
}

/**
 * Kiểm tra và sửa lỗi encoding cho dữ liệu đơn hàng
 * @param string $data Dữ liệu cần kiểm tra
 * @return string Dữ liệu đã được sửa lỗi encoding
 */
function fix_order_encoding($data)
{
    if (empty($data)) {
        return '';
    }

    // Kiểm tra xem có phải là dữ liệu bị lỗi encoding không
    if (mb_check_encoding($data, 'UTF-8') === false) {
        // Thử các encoding phổ biến
        $encodings = ['GB2312', 'GBK', 'BIG5', 'ISO-8859-1', 'Windows-1252'];
        foreach ($encodings as $encoding) {
            $converted = mb_convert_encoding($data, 'UTF-8', $encoding);
            if (mb_check_encoding($converted, 'UTF-8') && $converted !== $data) {
                return $converted;
            }
        }
    }

    return safe_encoding($data);
}

/**
 * ===================================================================
 * CÁC HÀM VALIDATION AN TOÀN CHO PREPARED STATEMENTS
 * ===================================================================
 * Các hàm này được thiết kế để validate dữ liệu đầu vào một cách an toàn
 * trước khi sử dụng trong prepared statements
 */

/**
 * Validate chuỗi văn bản với độ dài tối đa
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa (mặc định 255)
 * @param int $min_length Độ dài tối thiểu (mặc định 0)
 * @return string|false Chuỗi đã validate hoặc false nếu không hợp lệ
 */
function validate_string($input, $max_length = 255, $min_length = 0)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);
    $length = mb_strlen($input, 'UTF-8');

    if ($length < $min_length || $length > $max_length) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate mật khẩu với quy tắc bảo mật
 * @param mixed $input Dữ liệu đầu vào
 * @param int $min_length Độ dài tối thiểu (mặc định 6)
 * @param int $max_length Độ dài tối đa (mặc định 50)
 * @return array Kết quả validation với 'success' và 'message'
 */
function validate_password($input, $min_length = 6, $max_length = 50)
{
    if (!is_string($input) && !is_numeric($input)) {
        return [
            'success' => false,
            'message' => __('Mật khẩu phải là chuỗi ký tự')
        ];
    }

    $input = trim((string)$input);
    $length = mb_strlen($input, 'UTF-8');

    // Kiểm tra độ dài
    if ($length < $min_length) {
        return [
            'success' => false,
            'message' => sprintf(__('Mật khẩu phải có ít nhất %d ký tự'), $min_length)
        ];
    }

    if ($length > $max_length) {
        return [
            'success' => false,
            'message' => sprintf(__('Mật khẩu không được vượt quá %d ký tự'), $max_length)
        ];
    }

    // Kiểm tra ký tự được phép: chữ cái, số, và các ký tự đặc biệt an toàn (loại trừ các ký tự có thể gây XSS)
    if (!preg_match('/^[a-zA-Z0-9@$*!&#%^+=_\-\[\]{}|\\:";\'<>?,.\/`~()]+$/', $input)) {
        return [
            'success' => false,
            'message' => __('Mật khẩu chỉ được phép sử dụng chữ cái (a-z, A-Z), số (0-9) và các ký tự đặc biệt an toàn')
        ];
    }

    return [
        'success' => true,
        'message' => 'OK',
        'password' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')
    ];
}

/**
 * Validate chuỗi chỉ chứa ký tự chữ và số, dấu gạch dưới, gạch ngang
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa (mặc định 50)
 * @return string|false Chuỗi đã validate hoặc false nếu không hợp lệ
 */
function validate_alphanumeric($input, $max_length = 255)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > $max_length) {
        return false;
    }

    // Chỉ cho phép chữ cái, số, dấu gạch dưới và gạch ngang
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate địa chỉ email
 * @param mixed $input Dữ liệu đầu vào
 * @return string|false Email đã validate hoặc false nếu không hợp lệ
 */
function validate_email($input)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 320) { // RFC 5321 limit
        return false;
    }

    $email = filter_var($input, FILTER_VALIDATE_EMAIL);
    return $email !== false ? $email : false;
}

/**
 * Validate số nguyên trong khoảng cho phép
 * @param mixed $input Dữ liệu đầu vào
 * @param int $min Giá trị tối thiểu
 * @param int $max Giá trị tối đa
 * @return int|false Số nguyên đã validate hoặc false nếu không hợp lệ
 */
function validate_int($input, $min = PHP_INT_MIN, $max = PHP_INT_MAX)
{
    if (!is_numeric($input)) {
        return false;
    }

    $value = intval($input);

    if ($value < $min || $value > $max) {
        return false;
    }

    return $value;
}

/**
 * Validate số thực trong khoảng cho phép
 * @param mixed $input Dữ liệu đầu vào
 * @param float $min Giá trị tối thiểu
 * @param float $max Giá trị tối đa
 * @return float|false Số thực đã validate hoặc false nếu không hợp lệ
 */
function validate_float($input, $min = -PHP_FLOAT_MAX, $max = PHP_FLOAT_MAX)
{
    if (!is_numeric($input)) {
        return false;
    }

    $value = floatval($input);

    if ($value < $min || $value > $max) {
        return false;
    }

    return $value;
}

/**
 * Validate ngày tháng theo định dạng
 * @param mixed $input Dữ liệu đầu vào
 * @param string $format Định dạng ngày tháng (mặc định Y-m-d)
 * @return string|false Ngày đã validate hoặc false nếu không hợp lệ
 */
function validate_date($input, $format = 'Y-m-d')
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input)) {
        return false;
    }

    $date = DateTime::createFromFormat($format, $input);

    if (!$date || $date->format($format) !== $input) {
        return false;
    }

    return $input;
}

/**
 * Validate URL
 * @param mixed $input Dữ liệu đầu vào
 * @param array $allowed_schemes Các scheme được phép (mặc định http, https)
 * @return string|false URL đã validate hoặc false nếu không hợp lệ
 */
function validate_url($input, $allowed_schemes = ['http', 'https'])
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 2048) {
        return false;
    }

    $url = filter_var($input, FILTER_VALIDATE_URL);

    if ($url === false) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    if (!in_array($scheme, $allowed_schemes)) {
        return false;
    }

    return $url;
}

/**
 * Validate số điện thoại (định dạng cơ bản)
 * @param mixed $input Dữ liệu đầu vào
 * @return string|false Số điện thoại đã validate hoặc false nếu không hợp lệ
 */
function validate_phone($input)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);

    // Loại bỏ các ký tự không phải số, dấu +, -, (, ), space
    $cleaned = preg_replace('/[^0-9+\-() ]/', '', $input);

    if (empty($cleaned) || mb_strlen($cleaned, 'UTF-8') < 8 || mb_strlen($cleaned, 'UTF-8') > 20) {
        return false;
    }

    // Kiểm tra pattern cơ bản cho số điện thoại
    if (!preg_match('/^[+]?[0-9\-() ]+$/', $cleaned)) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate JSON string
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_depth Độ sâu tối đa khi decode JSON
 * @return string|false JSON string đã validate hoặc false nếu không hợp lệ
 */
function validate_json($input, $max_depth = 10)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 65535) {
        return false;
    }

    json_decode($input, true, $max_depth);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $input;
}

/**
 * Validate IP address
 * @param mixed $input Dữ liệu đầu vào
 * @param int $flags Flags cho filter_var (mặc định FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
 * @return string|false IP address đã validate hoặc false nếu không hợp lệ
 */
function validate_ip($input, $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input)) {
        return false;
    }

    $ip = filter_var($input, FILTER_VALIDATE_IP, $flags);

    return $ip !== false ? $ip : false;
}

/**
 * Validate boolean value
 * @param mixed $input Dữ liệu đầu vào
 * @return bool|false Boolean value hoặc false nếu không hợp lệ
 */
function validate_boolean($input)
{
    if (is_bool($input)) {
        return $input;
    }

    if (is_string($input)) {
        $input = strtolower(trim($input));
        if (in_array($input, ['true', '1', 'yes', 'on'])) {
            return true;
        }
        if (in_array($input, ['false', '0', 'no', 'off', ''])) {
            return false;
        }
    }

    if (is_numeric($input)) {
        return (bool)$input;
    }

    return false;
}

/**
 * Validate slug (URL-friendly string)
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa
 * @return string|false Slug đã validate hoặc false nếu không hợp lệ
 */
function validate_slug($input, $max_length = 100)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > $max_length) {
        return false;
    }

    // Chỉ cho phép chữ cái thường, số và dấu gạch ngang
    if (!preg_match('/^[a-z0-9\-]+$/', $input)) {
        return false;
    }

    // Không được bắt đầu hoặc kết thúc bằng dấu gạch ngang
    if (strpos($input, '-') === 0 || strrpos($input, '-') === mb_strlen($input, 'UTF-8') - 1) {
        return false;
    }

    return $input;
}

/**
 * Validate mảng với các phần tử phải thỏa mãn điều kiện
 * @param mixed $input Dữ liệu đầu vào
 * @param callable $validator Hàm validate từng phần tử
 * @param int $max_items Số phần tử tối đa
 * @return array|false Mảng đã validate hoặc false nếu không hợp lệ
 */
function validate_array($input, $validator = null, $max_items = 1000)
{
    if (!is_array($input)) {
        return false;
    }

    if (count($input) > $max_items) {
        return false;
    }

    if ($validator && is_callable($validator)) {
        $validated = [];
        foreach ($input as $key => $value) {
            $validated_value = $validator($value);
            if ($validated_value === false) {
                return false;
            }
            $validated[$key] = $validated_value;
        }
        return $validated;
    }

    return $input;
}
function validate_path($path)
{
    if (!is_string($path)) {
        return false;
    }

    // Lấy basename để tránh path traversal
    $path = basename($path);

    // Chỉ cho phép chữ cái, số, gạch ngang và gạch dưới
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $path)) {
        return false;
    }

    // Giới hạn độ dài
    if (strlen($path) > 50) {
        return false;
    }

    return $path;
}

function deleteSecureCookie($name)
{

    // Xóa cookie với các options tương tự như khi set
    $options = [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '', // Phải giống với khi set cookie
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ];

    // Xóa cookie với domain rỗng (giống như khi set cookie)
    setcookie($name, '', $options);
}

function logout_user()
{
    // Xóa tất cả các cookie liên quan đến user
    deleteSecureCookie('user_login');
    deleteSecureCookie('user_agent');
    deleteSecureCookie('remember_token');

    // Xóa session nếu đã được start
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }

    redirect(base_url('client/login'));
}

function logout_admin()
{
    // Xóa tất cả các cookie liên quan đến admin
    deleteSecureCookie('admin_login');
    deleteSecureCookie('user_login'); // Admin cũng có user_login
    deleteSecureCookie('user_agent');
    deleteSecureCookie('remember_token');

    // Xóa session nếu đã được start
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }

    redirect(base_url('client/login'));
}

/**
 * Kiểm tra chế độ bảo trì
 * Nếu site đang bảo trì và user không phải admin thì hiển thị trang bảo trì
 */
function checkMaintenance(): void
{
    global $CMSNT, $getUser;
    if ($CMSNT->site('status') != 1 && (!isset($getUser) || empty($getUser['admin']) || $getUser['admin'] <= 0)) {
        require_once(__DIR__ . '/../views/common/maintenance.php');
        exit();
    }
}

/**
 * Tìm user dựa trên nội dung chuyển khoản
 * - prefix_id: tìm theo prefix + user ID (cách cũ)
 * - fullname_transfer: tìm theo prefix_fullname + prefix trong nội dung CK (xóa space cả 2 bên)
 */
function findUserByDescription($description, $bankRechargeType)
{
    global $CMSNT;
    if ($bankRechargeType == 'fullname_transfer') {
        // Chuẩn hóa description: bỏ dấu chấm, bỏ dấu tiếng Việt, ghi hoa, XÓA SPACE
        $descNormalized = str_replace(' ', '', mb_strtoupper(removeVietnameseAccents(str_replace('.', ' ', $description)), 'UTF-8'));
        $prefix = str_replace(' ', '', mb_strtoupper($CMSNT->site('prefix_autobank'), 'UTF-8'));
        // Ghép prefix_fullname + prefix (xóa space), check trùng LIỀN NHAU trong description (đã xóa space)
        return $CMSNT->get_row_safe(
            " SELECT * FROM `users` WHERE `prefix_fullname` IS NOT NULL AND `prefix_fullname` != '' AND ? LIKE CONCAT('%', REPLACE(`prefix_fullname`, ' ', ''), ?, '%') ORDER BY LENGTH(`prefix_fullname`) DESC LIMIT 1 ",
            [$descNormalized, $prefix]
        );
    } else {
        // Chuẩn hóa: bỏ dấu chấm thành space, ghi hoa
        $descClean = mb_strtoupper(str_replace('.', ' ', $description), 'UTF-8');
        $prefix = mb_strtoupper($CMSNT->site('prefix_autobank'), 'UTF-8');
        // Tìm prefix + (space tùy chọn) + số ID (lấy nhóm số đầu tiên sau prefix)
        if (!empty($prefix) && preg_match('/' . preg_quote($prefix, '/') . '\s*(\d+)/i', $descClean, $m)) {
            $user_id = intval($m[1]);
            if ($user_id > 0) {
                return $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$user_id]);
            }
        }
        return null;
    }
}
