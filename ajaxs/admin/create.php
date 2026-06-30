<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . '/../../models/is_admin.php');

if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);
}

if ($_POST['action'] == 'generated_description_by_ai') {
    // Kiểm tra quyền sử dụng tính năng
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    // Lấy dữ liệu từ POST
    $keyword    = isset($_POST['keyword']) ? check_string($_POST['keyword']) : '';
    $short_desc = isset($_POST['short_desc']) ? check_string($_POST['short_desc']) : '';

    // Kiểm tra tham số (loại bỏ biến $license vì không được định nghĩa)
    if (empty($keyword) || empty($short_desc)) {
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu dữ liệu tên sản phẩm và mô tả ngắn'
        ]);
        exit;
    }

    if ($CMSNT->site('chatgpt_api_key') == '') {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng cấu hình Api Key ChatGPT tại Admin -> Cài Đặt -> Kết nối'
        ]);
        exit;
    }
    // Tạo prompt cho ChatGPT sử dụng cả keyword và short_desc
    $prompt = "Hãy tạo một bài giới thiệu chi tiết về loại tài khoản hoặc sản phẩm có tên \"$keyword\". " .
        "Mô tả ngắn: \"$short_desc\". " .
        "Nội dung cần bao gồm:\n" .
        "- Các tính năng và ưu điểm nổi bật,\n" .
        "- Lợi ích khi sử dụng tài khoản/sản phẩm \"$keyword\",\n" .
        "- Một số hướng dẫn hoặc thông tin bổ ích dành cho người dùng.\n\n" .
        "Yêu cầu:\n" .
        "- Sử dụng các thẻ như <h1>, <h2>, <p>, <ul>, <li> phù hợp trong CKEDITOR để định dạng nội dung giống như bài viết chuẩn SEO.\n" .
        "    - Font chữ (font-family) và màu sắc (color) cho tiêu đề và đoạn văn,\n" .
        "    - Khoảng cách padding và margin hợp lý,\n" .
        "    - Hiệu ứng nhẹ cho tiêu đề, ví dụ như underline hoặc shadow.\n\n";

    // Thiết lập các thông số cho API ChatGPT
    $api_key     = $CMSNT->site('chatgpt_api_key');
    $model       = $CMSNT->site('chatgpt_model'); // Hoặc "gpt-4" nếu bạn có quyền truy cập
    $max_tokens  = 1000;
    $temperature = 0.7;

    $url     = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens'   => $max_tokens,
        'temperature'  => $temperature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    // Tắt xác minh SSL nếu môi trường của bạn cần
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'message' => "Curl Error: " . curl_error($ch)
        ]);
        exit;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        echo json_encode([
            'success' => false,
            'message' => "HTTP Error: $http_code => $response"
        ]);
        exit;
    }
    curl_close($ch);

    $response_data = json_decode($response, true);
    if (!$response_data) {
        echo json_encode([
            'success' => false,
            'message' => "AI đang gián đoạn, đang cố gắng thử lại sau: " . $response
        ]);
        exit;
    }

    // Lấy kết quả từ API nếu có
    if (isset($response_data['choices'][0]['message']['content'])) {
        $generatedContent = $response_data['choices'][0]['message']['content'];
    } else {
        $generatedContent = 'No response generated';
    }

    // Trả về kết quả dưới dạng JSON
    echo json_encode([
        'success'     => true,
        'description' => $generatedContent
    ]);
    exit;
}


// Import tài khoản vào kho hàng qua AJAX
if ($_POST['action'] == 'importAccountStock') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input với validate functions
    $code = isset($_POST['code']) ? validate_string($_POST['code'], 255) : false;
    $accounts_raw = isset($_POST['accounts']) ? trim($_POST['accounts']) : '';
    $import_type = isset($_POST['import_type']) ? validate_string($_POST['import_type'], 10, 1) : 'multi';
    $loc_trung_uid = isset($_POST['loc_trung_uid']) ? intval($_POST['loc_trung_uid']) : 0;
    $loc_trung_uid_sold = isset($_POST['loc_trung_uid_sold']) ? intval($_POST['loc_trung_uid_sold']) : 0;

    // Whitelist import_type
    if ($import_type === false || !in_array($import_type, ['an', 'multi'])) {
        $import_type = 'multi';
    }

    if ($code === false || empty($code)) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (empty($accounts_raw)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tài khoản cần thêm')]));
    }

    // Tách danh sách tài khoản
    $list = [];
    if ($import_type == 'an') {
        $list[] = $accounts_raw;
    } else {
        $list = preg_split('/\r\n|\r|\n/', $accounts_raw);
        $list = array_filter($list, function ($line) {
            return trim($line) !== '';
        });
    }

    $value_add = 0;
    $value_update = 0;
    $value_skip_sold = 0;
    $value_total = 0;

    foreach ($list as $account) {
        $account = trim($account);
        if ($account === '') continue;

        // Xác định UID
        if (strpos($account, '|') !== false) {
            $uid = explode('|', $account)[0];
        } elseif (strpos($account, ':') !== false) {
            $uid = explode(':', $account)[0];
        } else {
            $uid = explode(' ', $account)[0];
        }
        $uid = trim($uid);
        if ($uid === '') continue;
        $value_total++;

        // Lọc trùng UID đã bán - Prepared Statement
        if ($loc_trung_uid_sold == 1) {
            if ($CMSNT->get_row_safe("SELECT `id` FROM `product_sold` WHERE `uid` = ? LIMIT 1", [$uid])) {
                $value_skip_sold++;
                continue;
            }
        }

        // Lọc trùng UID đang bán - Prepared Statement
        if ($loc_trung_uid == 1) {
            if ($CMSNT->get_row_safe("SELECT `id` FROM `product_stock` WHERE `uid` = ? LIMIT 1", [$uid])) {
                $isUpdate = $CMSNT->update("product_stock", [
                    'product_code'     => $code,
                    'seller'           => $getUser['id'],
                    'uid'              => $uid,
                    'account'          => $account,
                    'create_gettime'   => gettime()
                ], "`uid` = ?", [$uid]);
                if ($isUpdate) {
                    $value_update++;
                }
            } else {
                $isAdd = $CMSNT->insert("product_stock", [
                    'product_code'     => $code,
                    'seller'           => $getUser['id'],
                    'uid'              => $uid,
                    'account'          => $account,
                    'create_gettime'   => gettime()
                ]);
                if ($isAdd) {
                    $value_add++;
                }
            }
        } else {
            $isAdd = $CMSNT->insert("product_stock", [
                'product_code'     => $code,
                'seller'           => $getUser['id'],
                'uid'              => $uid,
                'account'          => $account,
                'create_gettime'   => gettime()
            ]);
            if ($isAdd) {
                $value_add++;
            }
        }
    }

    // Log
    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => "Import $value_add tài khoản vào kho hàng $code"
    ]);

    // Gửi thông báo Telegram
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', "Import $value_add tài khoản vào kho hàng $code", $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status' => 'success',
        'msg'    => __('Import thành công'),
        'data'   => [
            'total'       => $value_total,
            'added'       => $value_add,
            'updated'     => $value_update,
            'skip_sold'   => $value_skip_sold
        ]
    ]));
}

// Xóa nhiều tài khoản khỏi kho hàng qua AJAX
if ($_POST['action'] == 'removeAccountStock') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $code = isset($_POST['code']) ? validate_string($_POST['code'], 255) : false;
    $accounts_raw = isset($_POST['accounts']) ? trim($_POST['accounts']) : '';

    if ($code === false || empty($code)) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (empty($accounts_raw)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tài khoản cần xóa')]));
    }

    $list = preg_split('/\r\n|\r|\n/', $accounts_raw);
    $list = array_filter($list, function ($line) {
        return trim($line) !== '';
    });

    $value_remove = 0;
    $value_not_found = 0;
    $value_total = 0;

    foreach ($list as $account) {
        $account = trim($account);
        if ($account === '') continue;

        // Xác định UID
        if (strpos($account, '|') !== false) {
            $uid_remove = trim(explode('|', $account)[0]);
        } elseif (strpos($account, ':') !== false) {
            $uid_remove = trim(explode(':', $account)[0]);
        } else {
            $uid_remove = trim(explode(' ', $account)[0]);
        }

        if ($uid_remove === '') continue;
        $value_total++;

        // Xóa bằng Prepared Statements
        $isRemove = $CMSNT->remove("product_stock", "`uid` = ?", [$uid_remove]);
        if ($isRemove) {
            $value_remove++;
        } else {
            $value_not_found++;
        }
    }

    // Log
    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => "Xóa $value_remove tài khoản khỏi kho hàng $code"
    ]);

    // Gửi thông báo Telegram
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', "Xóa $value_remove tài khoản khỏi kho hàng $code", $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status' => 'success',
        'msg'    => __('Xóa thành công'),
        'data'   => [
            'total'     => $value_total,
            'removed'   => $value_remove,
            'not_found' => $value_not_found
        ]
    ]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
