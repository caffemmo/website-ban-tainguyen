<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../libs/database/users.php");
$CMSNT = new DB();

if (empty($_REQUEST['api_key'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập API KEY')]));
}
if (empty($_REQUEST['code'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập Mã Kho Hàng')]));
}
if (empty($_REQUEST['account'])) {
    die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập Tài khoản cần thêm')]));
}
// Validate input parameters
$api_key = validate_alphanumeric($_REQUEST['api_key']);
if ($api_key === false) {
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}

$code = validate_string($_REQUEST['code']);
if ($code === false) {
    die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
}

$account = $_REQUEST['account'];
if ($account === false) {
    die(json_encode(['status' => 'error', 'msg' => __('Tài khoản không hợp lệ')]));
}
$value_add = 0;
$value_update = 0;
$accountLines = preg_split("/\r\n|\r|\n/", $account);
$filter = isset($_REQUEST['filter']) ? validate_int($_REQUEST['filter'], 0, 1) : 1;
if ($filter === false) {
    $filter = 1; // Default value if invalid
}

if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `api_key` = ? AND `banned` = 0 AND `admin` != 0", [$api_key])) {
    // Rate limit
    checkBlockIP('API', 15);
    die(json_encode(['status' => 'error', 'msg' => __('API Key không hợp lệ')]));
}
// Kiểm tra IP Whitelist
$client_ip = myip();
if (!checkIPWhitelist($getUser['ip_whitelist_api'], $client_ip)) {
    // Rate limit cho IP không hợp lệ
    checkBlockIP('IP_NOT_WHITELIST_API', 5);
    die(json_encode([
        'status' => 'error',
        'msg' => __('IP của bạn không nằm trong Whitelist API của User này'),
        'client_ip' => $client_ip
    ]));
}
if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
    die(json_encode(['status' => 'error', 'msg' => __('API KEY không có quyền sử dụng tính năng này')]));
}

$seller_id = validate_int($getUser['id'], 1);
if ($seller_id === false) {
    die(json_encode(['status' => 'error', 'msg' => __('ID người bán không hợp lệ')]));
}
$hasValidAccount = false;
foreach ($accountLines as $accountLine) {
    $accountLine = trim(validate_string($accountLine, 5000, 1));
    if ($accountLine === '') {
        continue;
    }
    $hasValidAccount = true;
    // Kiểm tra delimiter: ưu tiên | > : > dấu cách
    if (strpos($accountLine, '|') !== false) {
        $uid = explode('|', $accountLine)[0];
    } elseif (strpos($accountLine, ':') !== false) {
        $uid = explode(':', $accountLine)[0];
    } else {
        $uid = explode(' ', $accountLine)[0];
    }

    // Kích hoạt lọc trùng UID
    if ($filter == 1) {
        // Validate UID
        $uid_validated = validate_string($uid, 1000);
        if ($uid_validated === false) {
            die(json_encode(['status' => 'error', 'msg' => __('UID không hợp lệ')]));
        }

        // Kiểm tra acc có tồn tại trong kho hàng chưa, nếu có rồi thì cập nhật tài khoản
        if ($CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `uid` = ?", [$uid_validated])) {

            $isUpdate = $CMSNT->update("product_stock", [
                'product_code'  => $code,
                'seller'        => $seller_id,
                'uid'           => $uid_validated,
                'account'       => $accountLine,
                'type'          => 'API',
                'create_gettime'   => gettime()
            ], " `uid` = ?", [$uid_validated]);
            if ($isUpdate) {
                $value_update++;
            }
        } else {
            // Kiểm tra acc đã bán chưa, nếu acc đã bán rồi thì không cho upload lên
            $sold_exists = $CMSNT->get_row_safe("SELECT `id` FROM `product_sold` WHERE `uid` = ? LIMIT 1", [$uid_validated]);
            if (!$sold_exists) {
                $isAdd = $CMSNT->insert("product_stock", [
                    'product_code'  => $code,
                    'seller'        => $seller_id,
                    'uid'           => $uid_validated,
                    'account'       => $accountLine,
                    'type'          => 'API',
                    'create_gettime'   => gettime()
                ]);
                if ($isAdd) {
                    $value_add++;
                }
            } else {
                die(json_encode(['status' => 'error', 'msg' => __('Tài khoản đã bán, không thể thêm vào kho hàng')]));
            }
        }
    } else {
        // Thêm tài khoản vào kho hàng
        $uid_validated = validate_string($uid, 1000);
        if ($uid_validated === false) {
            die(json_encode(['status' => 'error', 'msg' => __('UID không hợp lệ')]));
        }

        $isAdd = $CMSNT->insert("product_stock", [
            'product_code'  => $code,
            'seller'        => $seller_id,
            'uid'           => $uid_validated,
            'account'       => $accountLine,
            'type'          => 'API',
            'create_gettime'   => gettime()
        ]);
        if ($isAdd) {
            $value_add++;
        }
    }
}

if ($hasValidAccount === false) {
    die(json_encode(['status' => 'error', 'msg' => __('Không có tài khoản hợp lệ để xử lý')]));
}


die(json_encode(['status' => 'success', 'msg' => 'Đã thêm ' . $value_add . ' tài khoản | Cập nhật ' . $value_update . ' tài khoản']));
