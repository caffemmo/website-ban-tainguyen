<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

function getInvoiceAPI_33($domain, $token, $trans_id, $proxy = '')
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/partner/code?invoice_code={$trans_id}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'X-Partner-Token: ' . $token
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function buy_API_33($domain, $token, $id_api, $amount, $proxy = '')
{
    $curl = curl_init();

    $postData = json_encode([
        'price_id' => intval($id_api),
        'quantity' => intval($amount)
    ]);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/partner/invoice",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Partner-Token: ' . $token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}


function listProduct_API_33($domain, $proxy = '')
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/plan",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
function getToken_API_33($domain, $username, $password, $proxy = '')
{
    // Bước 1: Đăng nhập để lấy access_token
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/user/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "username={$username}&password={$password}",
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);

    $loginData = json_decode($response, true);
    if (!isset($loginData['access_token'])) {
        return json_encode([
            'code' => '400000',
            'message' => 'Đăng nhập thất bại. Vui lòng kiểm tra lại username và password.',
            'data' => null
        ]);
    }

    $access_token = $loginData['access_token'];

    // Bước 2: Lấy Partner Token
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/partner/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $access_token
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);

    $partnerData = json_decode($response, true);
    if (!isset($partnerData['code']) || $partnerData['code'] != '200000') {
        return json_encode([
            'code' => isset($partnerData['code']) ? $partnerData['code'] : '400000',
            'message' => isset($partnerData['message']) ? $partnerData['message'] : 'Không thể lấy partner token',
            'data' => null
        ]);
    }

    $partner_token = $partnerData['data'];

    // Bước 3: Lấy số dư tài khoản
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/user/balance",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $access_token
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);

    $balanceData = json_decode($response, true);
    if (!isset($balanceData['code']) || $balanceData['code'] != '200000') {
        return json_encode([
            'code' => isset($balanceData['code']) ? $balanceData['code'] : '400000',
            'message' => isset($balanceData['message']) ? $balanceData['message'] : 'Không thể lấy số dư tài khoản',
            'data' => null
        ]);
    }

    // Trả về partner token từ data (theo format mà product-api-add.php đang sử dụng)
    // Lưu ý: api/user/balance trả về data trực tiếp là số dư (integer), không phải object
    return json_encode([
        'code' => '200000',
        'message' => 'Success',
        'data' => $partner_token,
        'balance' => isset($balanceData['data']) ? $balanceData['data'] : 0
    ]);
}
function buy_API_32($domain, $api_key, $id_api, $amount, $proxy = '')
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/BuyGmail/BuyProduct?apikey={$api_key}&product_id={$id_api}&quantity={$amount}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function getStock_API_32($domain, $api_key, $id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/BuyGmail/GetstockGmail?apikey={$api_key}&id={$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);

    // Parse response và chỉ return stock
    $response = json_decode($data, true);

    // Return stock number hoặc 0 nếu không có
    if (isset($response['success']) && $response['success'] == true) {
        return intval($response['data']['stock']);
    } else {
        return 0;
    }
}
function listProduct_API_32($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/BuyGmail/GetListGmailProduct?apikey={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function balance_API_32($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/ApiV2/GetUserInfo?apikey={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function balance_API_31($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}/api/profile.php?api_key={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function listProduct_API_31($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}/api/products.php?api_key={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function buy_API_31($domain, $coupon, $api_key, $id_api, $amount, $proxy = '')
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}/api/buy_product",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('action' => 'buyProduct', 'id' => $id_api, 'amount' => $amount, 'coupon' => $coupon, 'api_key' => $api_key),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function buy_API_30($domain, $api_key, $id_api, $amount)
{
    return curl_get($domain . "huoqu?shuliang={$amount}&leixing={$id_api}&card={$api_key}");
}
function listProduct_API_30($domain)
{
    return curl_get2($domain . 'kucun');
}
function balance_API_30($domain, $apikey)
{
    return curl_get($domain . "yue?card=$apikey");
}
//
function buy_API_29($domain, $api_key, $id_api, $amount)
{
    return curl_get($domain . "api/mail/getMail?clientKey={$api_key}&mailType={$id_api}&quantity={$amount}");
}
function listProduct_API_29($domain, $type)
{
    return curl_get2($domain . 'api/mail/getStock?mailType=' . $type);
}
function balance_API_29($domain, $apikey)
{
    return curl_get($domain . "api/user/balance?clientKey=$apikey");
}
//
function buy_API_28($domain, $token, $api_id, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api/order',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'id' => $api_id,
            'quantity' => $amount,
            'user_token' => $token
        ),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_28($domain)
{
    return curl_get2($domain . 'api/get-account');
}
function balance_API_28($domain, $username, $password)
{
    return curl_get("{$domain}api/get-info?username=$username&password=$password");
}
function getOrder_API_26($domain, $api_key, $token, $invoice)
{
    global $CMSNT;

    $allowed_domains = explode(',', $CMSNT->site('domains'));
    $api_key = explode('|', $api_key);
    $token = explode('|', $token);

    $public_key = $token[0];
    $private_key = $token[1];
    $email = $api_key[0];
    $token_pay = $api_key[1];
    $key = $api_key[2];
    $key_createorder = $api_key[3];
    $host = $token[2];

    $curl = curl_init("{$domain}api/downloadtxt/{$invoice}");
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            "LEQUE-KEY-API-PUB: $public_key",
            "LEQUE-KEY-API-PRIV: $private_key",
            "HOST: $host"
        ),
        CURLOPT_SSL_VERIFYPEER => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_SSL_VERIFYHOST => false  // Lưu ý: Tắt xác minh SSL có thể không an toàn
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    // Hiển thị phản hồi dưới dạng văn bản thuần túy, giữ nguyên định dạng
    if ($response && strlen($response) > 0) {
        // Sử dụng htmlspecialchars để tránh XSS nếu nội dung có thể chứa HTML/JS
        // Sử dụng nl2br để chuyển đổi dấu xuống dòng thành thẻ <br> khi hiển thị trên web
        return htmlspecialchars($response);
    } else {
        return __('Please contact Admin to get order');
    }
}
function buy_API_26($domain, $api_key, $token, $api_id, $amount)
{
    global $CMSNT;

    $allowed_domains = explode(',', $CMSNT->site('domains'));
    $api_key = explode('|', $api_key);
    $token = explode('|', $token);

    $public_key = $token[0];
    $private_key = $token[1];
    $email = $api_key[0];
    $token_pay = $api_key[1];
    $key = $api_key[2];
    $key_createorder = $api_key[3];
    $host = $token[2];

    $curl = curl_init();
    $data = array(
        "email" => $email,
        "key" => $key_createorder,
        "count" => $amount,
        "type" => $api_id,
        "fund" => "13",
        "success_url" => basename(''),
        "token_pay" => $token_pay
    );

    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/createorder",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "webApi",
        CURLOPT_SSL_VERIFYPEER => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_SSL_VERIFYHOST => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array(
            "LEQUE-KEY-API-PUB: $public_key",
            "LEQUE-KEY-API-PRIV: $private_key",
            "HOST: $host"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, true);
    if (isset($response['ok']) && $response['ok'] == 'TRUE') {
        $invoice = $response['invoice'];
        $curl = curl_init();
        $data = array(
            "pay" => "yes",
            "email_pay" => $email,
            "token_pay" => $token_pay
        );
        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$domain}api/paybalance/{$invoice}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "webApi",
            CURLOPT_SSL_VERIFYPEER => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
            CURLOPT_SSL_VERIFYHOST => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array(
                "LEQUE-KEY-API-PUB: $public_key",
                "LEQUE-KEY-API-PRIV: $private_key",
                "HOST: $host"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    } else {
        return json_encode($response);
    }
}
function listProduct_API_26($domain, $api_key, $token)
{
    global $CMSNT;

    $allowed_domains = explode(',', $CMSNT->site('domains'));
    $api_key = explode('|', $api_key);
    $token = explode('|', $token);

    $public_key = $token[0];
    $private_key = $token[1];
    $email = $api_key[0];
    $token_pay = $api_key[1];
    $key = $api_key[2];
    $host = $token[2];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/goods?key={$key}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "webApi",
        CURLOPT_SSL_VERIFYPEER => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_SSL_VERIFYHOST => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_HTTPHEADER => array(
            "LEQUE-KEY-API-PUB: " . $public_key,
            "LEQUE-KEY-API-PRIV: " . $private_key,
            "HOST: $host"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}
function balance_API_26($domain, $api_key, $token)
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = explode(',', $CMSNT->site('domains'));
    $api_key = explode('|', $api_key);
    $token = explode('|', $token);
    //
    $public_key = $token[0];
    $private_key = $token[1];
    $email = $api_key[0];
    $token_pay = $api_key[1];
    $host = $token[2];

    $curl = curl_init();
    $data = array(
        "email" => $email,
        "token_pay" => $token_pay,
    );
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}api/balanceuser",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "webApi",
        CURLOPT_SSL_VERIFYPEER => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_SSL_VERIFYHOST => false, // Lưu ý: Tắt xác minh SSL có thể không an toàn
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array(
            "LEQUE-KEY-API-PUB: " . $public_key,
            "LEQUE-KEY-API-PRIV: " . $private_key,
            "HOST: $host"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}
//
function buy_API_25($domain, $api_key, $id_api, $amount)
{
    return curl_get($domain . "purchase?apikey=$api_key&accountcode=$id_api&quantity=$amount");
}
function listProduct_API_25($domain)
{
    return curl_get($domain . "instock");
}
function balance_API_25($domain, $apikey)
{
    return curl_get($domain . "balance?apikey=$apikey");
}

function balance_API_24($domain, $api_key)
{
    return curl_get2($domain . "api/checkapikey=$api_key");
}
function buy_API_24($domain, $api_key, $api_id, $amount)
{
    return curl_get($domain . "api/byproduct/apikey=$api_key&product_id=$api_id&quality=$amount");
}
function listProduct_API_24($domain, $api_key)
{
    return curl_get($domain . "api/checkprice=$api_key");
}

function buy_API_23($domain, $api_key, $api_id, $amount)
{
    return curl_get($domain . "purchase?api_key=$api_key&accountcode=$api_id&quantity=$amount");
}
function listProduct_API_23($domain)
{
    return curl_get($domain . "instock");
}
function balance_API_23($domain, $api_key)
{
    return curl_get2($domain . "balance?api_key=$api_key");
}
function buy_API_22($domain, $token, $product_id, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api/buyHotMailUd',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'quantity' => $amount,
            'token' => $token,
            'product_id' => $product_id
        ),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_22($domain, $token)
{
    return curl_get($domain . 'api/quantity?token=' . $token);
}
function buy_API_17($domain, $username, $password, $api_id, $amount)
{
    return curl_get2("$domain/api/BResource.php?username=$username&password=$password&id=$api_id&amount=$amount");
}
function listProduct_API_17($domain, $username, $password)
{
    return curl_get2($domain . '/api/CategoryList.php?username=' . $username . '&password=' . $password);
}
function balance_API_17($domain, $username, $password)
{
    return curl_get("{$domain}api/GetBalance.php?username=$username&password=$password");
}
function buy_API_21($domain, $token, $product_id, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api/buy-products',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'quantity' => $amount,
            'token' => $token,
            'product_id' => $product_id
        ),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_21($domain, $token)
{
    return curl_get($domain . 'api/quantity?token=' . $token);
}
function buy_API_9($domain, $password, $dataPost)
{
    $data = json_encode($dataPost);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'v1/api/buy?api_key=' . $password,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_9($domain, $password)
{
    return curl_get($domain . 'v1/api/categories?api_key=' . $password);
}
function balance_API_9($domain, $password)
{
    return curl_get($domain . 'v1/api/me?api_key=' . $password);
}

function buy_API_4($domain, $token, $id_product, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'v1/user/partnerbuy',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('amount' => $amount, 'categoryId' => $id_product),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'authorization: ' . $token
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function balance_API_4($domain, $username, $password)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'v1/user/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'username' => $username,
            'password'  => $password
        ),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_4($domain)
{
    return curl_get2($domain . "v1/public/category/list");
}
function buy_API_19($domain, $api_key, $id_api, $amount)
{
    return curl_get2($domain . "user/buy?apikey=$api_key&account_type=$id_api&quality=$amount&type=null");
}
function listProduct_API_19($domain, $api_key)
{
    return curl_get2($domain . "user/account_type?apikey=$api_key");
}
function balance_API_19($domain, $api_key)
{
    return curl_get2($domain . "user/balance?apikey=$api_key");
}
function buy_API_18($domain, $api_key, $id_api, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'mail/buy?mailcode=' . $id_api . '&quantity=' . $amount,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $api_key
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_18($domain)
{
    return curl_get($domain . "mail/currentstock");
}
function balance_API_18($domain, $apikey)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'auth/me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apikey
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function buy_API_SHOPCLONE7($domain, $coupon, $api_key, $id_api, $amount, $proxy = '')
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}/api/buy_product",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('action' => 'buyProduct', 'id' => $id_api, 'amount' => $amount, 'coupon' => $coupon, 'api_key' => $api_key),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);

    // Lấy HTTP status code
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    // Xử lý response JSON và thêm HTTP code vào
    if ($response) {
        $result = json_decode($response, true);
        if (is_array($result)) {
            $result['http_code'] = $http_code;
            return json_encode($result);
        }
    }

    // Nếu response không phải là JSON hợp lệ hoặc rỗng, trả về cấu trúc mới
    return json_encode([
        'status' => 'error2',
        'msg' => __('Mất kết nối đến kho hàng'),
        'http_code' => $http_code
    ]);
}

function listProduct_API_SHOPCLONE7($domain, $api_key, $proxy = '', $use_child = false)
{
    $ch = curl_init();
    // Sử dụng API products_child.php nếu use_child = true, ngược lại dùng products.php
    $api_endpoint = $use_child ? "products_child.php" : "products.php";
    curl_setopt($ch, CURLOPT_URL, "{$domain}/api/{$api_endpoint}?api_key={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function balance_API_SHOPCLONE7($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}/api/profile.php?api_key={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * SHOPKEY API Functions
 * API SHOPKEY sử dụng header-based authentication với X-API-Key và X-API-Secret
 */

/**
 * Lấy số dư tài khoản SHOPKEY
 * @param string $domain Domain của API (VD: https://shopkey.io/)
 * @param string $api_key API Key
 * @param string $secret_key Secret Key
 * @param string $proxy Proxy nếu có (format: ip:port hoặc ip:port:user:pass)
 * @return string JSON response từ API
 */
function balance_API_SHOPKEY($domain, $api_key, $secret_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . "/api/v1/account/balance");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Tự động giải nén response gzip/deflate/brotli để tránh lỗi garbled data
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'X-API-Secret: ' . $secret_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm SHOPKEY
 * @param string $domain Domain của API
 * @param string $api_key API Key
 * @param string $secret_key Secret Key
 * @param string $proxy Proxy nếu có
 * @param int $page Số trang (mặc định = 1)
 * @param int $per_page Số sản phẩm mỗi trang (mặc định = 100)
 * @return string JSON response từ API
 */
function listProduct_API_SHOPKEY($domain, $api_key, $secret_key, $proxy = '', $page = 1, $per_page = 100)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . "/api/v1/products/list?page=" . intval($page) . "&per_page=" . intval($per_page));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Tự động giải nén response gzip/deflate/brotli để tránh lỗi garbled description
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'X-API-Secret: ' . $secret_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm từ SHOPKEY
 * @param string $domain Domain của API
 * @param string $coupon Mã giảm giá
 * @param string $api_key API Key
 * @param string $secret_key Secret Key
 * @param int $plan_id ID của plan cần mua
 * @param int $quantity Số lượng mua
 * @param string $proxy Proxy nếu có
 * @return string JSON response từ API
 */
function buy_API_SHOPKEY($domain, $coupon, $api_key, $secret_key, $plan_id, $quantity, $proxy = '')
{
    // Chuẩn bị dữ liệu POST
    $postData = json_encode([
        'items' => [
            [
                'plan_id' => intval($plan_id),
                'quantity' => intval($quantity)
            ]
        ],
        'coupon_code' => !empty($coupon) ? $coupon : ''
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => rtrim($domain, '/') . "/api/v1/orders/create",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'X-API-Key: ' . $api_key,
            'X-API-Secret: ' . $secret_key,
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Xử lý response JSON và thêm HTTP code
    if ($response) {
        $result = json_decode($response, true);
        if (is_array($result)) {
            $result['http_code'] = $http_code;
            return json_encode($result);
        }
    }

    return json_encode([
        'success' => false,
        'message' => __('Mất kết nối đến kho hàng'),
        'http_code' => $http_code
    ]);
}

/**
 * Lấy trạng thái đơn hàng SHOPKEY
 * @param string $domain Domain của API
 * @param string $api_key API Key
 * @param string $secret_key Secret Key
 * @param string $trans_id Mã giao dịch
 * @param string $proxy Proxy nếu có
 * @return string JSON response từ API
 */
function getOrder_API_SHOPKEY($domain, $api_key, $secret_key, $trans_id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . "/api/v1/orders/status?trans_id=" . urlencode($trans_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Tự động giải nén response gzip/deflate/brotli để tránh lỗi garbled data
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'X-API-Secret: ' . $secret_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_SHOPCLONE6($domain, $username, $password, $api_id, $amount, $proxy = '')
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "{$domain}/api/BResource.php?username={$username}&password={$password}&id={$api_id}&amount={$amount}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $response = curl_exec($curl);

    // Lấy HTTP status code
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    // Xử lý response JSON và thêm HTTP code vào
    if ($response) {
        $result = json_decode($response, true);
        if (is_array($result)) {
            $result['http_code'] = $http_code;
            return json_encode($result);
        }
    }

    // Nếu response không phải là JSON hợp lệ hoặc rỗng, trả về cấu trúc mới
    return json_encode([
        'status' => 'error2',
        'msg' => __('Mất kết nối đến kho hàng'),
        'http_code' => $http_code
    ]);
}
function listProduct_API_SHOPCLONE6($domain, $username, $password, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}/api/ListResource.php?username={$username}&password={$password}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function balance_API_SHOPCLONE6($domain, $username, $password, $proxy = '')
{
    $url = "{$domain}/api/GetBalance.php?username={$username}&password={$password}";

    $opts = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
        "http" => array(
            "header" => array(
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
            )
        )
    );

    // Nếu có proxy, thêm cấu hình proxy
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4) {
            $proxy = "tcp://{$proxy_parts[0]}:{$proxy_parts[1]}";
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
            $opts['http']['header'][] = 'Proxy-Authorization: Basic ' . base64_encode($proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2) {
            $proxy = "tcp://{$proxy_parts[0]}:{$proxy_parts[1]}";
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
        }
    }

    return file_get_contents($url, false, stream_context_create($opts));
}
function getOrder_API_14($domain, $token, $order_id)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
        CURLOPT_POSTFIELDS => '{
            "act": "Get-Order",
            "data": {
                "order_id": ' . $order_id . '
            }
        }',
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function buy_API_14($domain, $token, $id_api, $amount)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
        CURLOPT_POSTFIELDS => '{
        "act": "Create-Order",
        "data": {
            "service_id": ' . $id_api . ',
            "quantity": ' . $amount . '
        }
    }',
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_14($domain, $token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('act' => 'Get-Products'),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function balance_API_14($domain, $token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('act' => 'Me'),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function balance_API_6($domain, $api_key)
{
    return curl_get("$domain/api.php?apikey=$api_key&action=get-balance");
}

// API_35 Functions - Xác thực bằng Email + Token
function balance_API_35($domain, $email, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}?action=balance&email={$email}&token={$token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function listProduct_API_35($domain, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}?action=list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_35($domain, $email, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    // buy=13 là tham số cố định theo tài liệu API
    curl_setopt($ch, CURLOPT_URL, "{$domain}?email={$email}&token={$token}&buy=13&action={$product_id}&quantity={$quantity}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// API_34 Functions
function balance_API_34($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/users/balance?apikey={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function listProduct_API_34($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/products/get-all?apikey={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_34($domain, $api_key, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/orders/buy?productId={$product_id}&quantity={$quantity}&apikey={$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function balance_API_1($domain, $token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api/v1/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('api_key' => $token),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function listProduct_API_1($domain)
{
    return curl_get2($domain . 'api/v1/categories');
}
function buy_API_1($domain, $dataPost)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . "api/v1/buy",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $dataPost,
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
function order_API_1($domain, $api_key, $order_id)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . 'api/v1/order',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('api_key' => $api_key, 'order_id' => $order_id),
        // Tắt xác minh SSL để tương thích với các web sử dụng SSL phiên bản khác nhau
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

// API_36 Functions - humkt.com
function balance_API_36($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/balance?token={$token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function listProduct_API_36($domain, $token, $proxy = '', $page = 1)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/products?token={$token}&page={$page}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_36($domain, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/orders");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'token' => $token,
        'id' => intval($product_id),
        'qty' => intval($quantity)
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function getOrder_API_36($domain, $token, $order_id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/v1/orders/{$order_id}?token={$token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// ==================== API_37 - sieuthikey.io.vn ====================

function balance_API_37($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/get_balance.php?token={$token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function listProduct_API_37($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/get_products.php?token={$token}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_37($domain, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}api/buy_product.php?token={$token}&product_id={$product_id}&soluong={$quantity}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// ==================== API_38 - API Shared (Partner API with MD5 Signature) ====================

/**
 * Tạo chữ ký MD5 cho API Shared
 * Quy tắc: Sắp xếp params theo key A-Z -> Ghép query string -> Thêm &key=app_key -> MD5
 */
function build_sign_API_38($data, $app_key)
{
    unset($data['sign']);
    ksort($data);
    foreach ($data as $k => $v) {
        if ($v === '') unset($data[$k]);
    }
    $raw = http_build_query($data) . "&key=" . $app_key;
    return md5(urldecode($raw));
}

/**
 * Gọi API Shared với chữ ký MD5
 */
function call_API_38($domain, $endpoint, $data, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$domain}index.php?s={$endpoint}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Content-Type: application/x-www-form-urlencoded'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * Kiểm tra kết nối và lấy số dư - /shared/authentication/connect
 */
function balance_API_38($domain, $app_id, $app_key, $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key
    ];
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/authentication/connect', $data, $proxy);
}

/**
 * Lấy danh sách sản phẩm - /shared/commodity/items
 */
function listProduct_API_38($domain, $app_id, $app_key, $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key
    ];
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/commodity/items', $data, $proxy);
}

/**
 * Lấy thông tin tồn kho sản phẩm - /shared/commodity/inventory
 * @param string $domain Domain API
 * @param int $app_id App ID 
 * @param string $app_key App Key
 * @param string $sharedCode Mã sản phẩm
 * @param string $race Loại sản phẩm (nếu có)
 * @param string $proxy Proxy (nếu có)
 * @return string JSON response với count là số lượng tồn kho
 */
function inventory_API_38($domain, $app_id, $app_key, $sharedCode, $race = '', $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key,
        'sharedCode' => $sharedCode
    ];
    if (!empty($race)) {
        $data['race'] = $race;
    }
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/commodity/inventory', $data, $proxy);
}

/**
 * Kiểm tra tồn kho - /shared/commodity/inventoryState
 */
function inventoryState_API_38($domain, $app_id, $app_key, $shared_code, $num, $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key,
        'shared_code' => $shared_code,
        'num' => $num
    ];
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/commodity/inventoryState', $data, $proxy);
}

/**
 * Tạo đơn hàng - /shared/commodity/trade
 */
function buy_API_38($domain, $app_id, $app_key, $shared_code, $num, $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key,
        'shared_code' => $shared_code,
        'num' => $num
    ];
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/commodity/trade', $data, $proxy);
}

/**
 * Tra cứu đơn hàng - /shared/commodity/query
 */
function getOrder_API_38($domain, $app_id, $app_key, $tradeNo, $proxy = '')
{
    $data = [
        'app_id' => $app_id,
        'app_key' => $app_key,
        'tradeNo' => $tradeNo
    ];
    $data['sign'] = build_sign_API_38($data, $app_key);
    return call_API_38($domain, '/shared/commodity/query', $data, $proxy);
}

// ==================== API_39 ====================

/**
 * Lấy số dư tài khoản - GET /api/v1/users/profile
 * @param string $domain Domain API
 * @param string $token API Token (x-api-token header)
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function balance_API_39($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/users/profile');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-token: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy thông tin sản phẩm - GET /api/v1/products/info/:id
 * @param string $domain Domain API
 * @param string $token API Token
 * @param string $product_id ID sản phẩm
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function productInfo_API_39($domain, $token, $product_id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/products/info/' . urlencode($product_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-token: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy thông tin phân loại - GET /api/v1/products/variant/:id
 * @param string $domain Domain API
 * @param string $token Token Header
 * @param string $variant_id Variant ID
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function variantInfo_API_39($domain, $token, $variant_id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/products/variant/' . $variant_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-token: ' . $token
    ]);
    if ($proxy != '') {
        $proxy = explode(':', $proxy);
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
        if (isset($proxy[2]) && isset($proxy[3])) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy[2] . ':' . $proxy[3]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm - POST /api/v1/orders
 * @param string $domain Domain API
 * @param string $token API Token
 * @param string $product_id ID sản phẩm
 * @param int $quantity Số lượng mua
 * @param string $variant_id ID phân loại (tùy chọn)
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function buy_API_39($domain, $token, $product_id, $quantity, $variant_id = '', $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    $postData = [
        'productId' => $product_id,
        'quantity' => intval($quantity)
    ];
    if (!empty($variant_id)) {
        $postData['variantId'] = $variant_id;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-token: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy chi tiết đơn hàng - GET /api/v1/orders/:id
 * @param string $domain Domain API
 * @param string $token API Token
 * @param string $order_id ID đơn hàng
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function getOrder_API_39($domain, $token, $order_id, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/orders/' . urlencode($order_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-token: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// ==================== API_40 - Telegram Shop API ====================

/**
 * Lấy số dư tài khoản - POST /api/balance
 * API 40 xác thực bằng api_key gửi qua JSON body
 * @param string $api_key API Key xác thực
 * @param string $proxy Proxy nếu có (format: ip:port:user:pass)
 * @return string JSON response {"success": true, "balance": 200000}
 */
function balance_API_40($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    // POST /api/balance với JSON body chứa api_key
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    // Gửi api_key dạng JSON body theo chuẩn OpenAPI schema
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'api_key' => $api_key
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm (services) - GET /api/services
 * Response có cấu trúc phân cấp: category → position
 * Mỗi position là 1 sản phẩm có thể mua, dùng position_id làm product_id khi mua
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param string $proxy Proxy nếu có
 * @return string JSON response {"success": true, "category": [...]}
 */
function listProduct_API_40($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    // GET /api/services với api_key qua query parameter
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/services?api_key=' . urlencode($api_key));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm - POST /api/buy
 * Gửi JSON body với api_key, product_id (position_id), quantity
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param int $product_id ID sản phẩm (position_id từ /api/services)
 * @param int $quantity Số lượng mua
 * @param string $proxy Proxy nếu có
 * @return string JSON response
 */
function buy_API_40($domain, $api_key, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    // POST /api/buy với JSON body chứa thông tin mua hàng
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/buy');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    // Payload theo OpenAPI BuyRequest schema: api_key, product_id (integer), quantity (integer)
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'api_key' => $api_key,
        'product_id' => intval($product_id),
        'quantity' => intval($quantity)
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (giúp debug production issues)
    if ($data === false) {
        error_log("[API_40] cURL error in buy_API_40: " . curl_error($ch) . " | URL: " . rtrim($domain, '/') . '/api/buy');
    }
    curl_close($ch);
    return $data;
}

/**
 * ==========================================
 * Xác thực bằng header X-API-Key
 * Base URL: /api/v1
 * ==========================================
 */

/**
 * Lấy số dư tài khoản — GET /v1/account/balance
 * API 41 xác thực bằng header X-API-Key (khác API 40 gửi qua body)
 * @param string $api_key API Key dạng sk_live_xxx
 * @param string $proxy Proxy nếu có (format: ip:port:user:pass)
 * @return string JSON response {"success": true, "data": {"balance": 500000}}
 */
function balance_API_41($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    // GET /v1/account/balance — endpoint lấy số dư dùng header auth
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/account/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // API 41 xác thực qua header X-API-Key (không gửi qua body như API 40)
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm — GET /v1/products
 * API trả flat list (không phân cấp category→position như API 40)
 * Hỗ trợ pagination: page & limit (tối đa 100/trang)
 * Mỗi sản phẩm có: id, name, price, stockCount, category{name}
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param string $proxy Proxy nếu có
 * @param int $page Trang cần lấy (mặc định 1)
 * @param int $limit Số sản phẩm/trang (mặc định 100, tối đa 100)
 * @return string JSON response {"success": true, "data": [...]}
 */
function listProduct_API_41($domain, $api_key, $proxy = '', $page = 1, $limit = 100)
{
    $ch = curl_init();
    // GET /v1/products với pagination — tối đa 100 sản phẩm/trang
    $url = rtrim($domain, '/') . '/api/v1/products?page=' . intval($page) . '&limit=' . intval($limit);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // Xác thực qua header X-API-Key
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm — POST /v1/purchase
 * Gửi JSON body với productId (string), quantity (number), và variantId nếu có
 * Response trả deliveredData[] trực tiếp (khác API 40 trả download URLs)
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param string $product_id ID sản phẩm (string, từ /v1/products)
 * @param int $quantity Số lượng mua
 * @param string $proxy Proxy nếu có
 * @param string|null $variant_id ID variant (nếu sản phẩm có variants)
 * @return string JSON response {"success":true,"data":{"orderId":"...","deliveredData":[...]}}
 */
function buy_API_41($domain, $api_key, $product_id, $quantity, $proxy = '', $variant_id = null)
{
    $ch = curl_init();
    // POST /v1/purchase — endpoint mua hàng
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/v1/purchase');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    // Payload: productId là string, quantity là integer, variantId là string (nếu có)
    $payload = [
        'productId' => strval($product_id),
        'quantity' => intval($quantity)
    ];
    // Chỉ gửi variantId khi sản phẩm có variants (api_id chứa dấu |)
    if (!empty($variant_id)) {
        $payload['variantId'] = strval($variant_id);
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // Xác thực qua header X-API-Key (khác API 40 gửi api_key trong body)
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (giúp debug production issues)
    if ($data === false) {
        error_log("[API_41] cURL error in buy_API_41: " . curl_error($ch) . " | URL: " . rtrim($domain, '/') . '/api/v1/purchase');
    }
    curl_close($ch);
    return $data;
}

/**
 * ==========================================
 * Xác thực bằng header x-api-key (chữ thường, khác API_41)
 * Base URL: /api
 * Đặc điểm:
 *   - KHÔNG có endpoint balance → dùng GET /api/categories để health-check
 *   - GET /api/categories trả flat list (id, name, price, availableCount)
 *   - Đơn hàng POST /api/orders trả status="pending" → cần poll download
 *   - GET /api/orders/{orderId}/download trả TEXT thuần (email|pass|recovery)
 * ==========================================
 */

/**
 * Dùng GET /api/categories để test kết nối + xác thực api_key
 * Nếu API trả success=true nghĩa là domain+api_key hợp lệ
 * @param string $api_key API Key dạng hex 64 ký tự
 * @param string $proxy Proxy nếu có (format: ip:port:user:pass)
 * @return string JSON response từ GET /api/categories
 */
function balance_API_42($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    // GET /api/categories — làm health check thay cho endpoint balance (API không hỗ trợ balance)
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // Gửi x-api-key dù endpoint public — để server log được api_key và ta phát hiện key sai sớm
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . $api_key,
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm (categories) — GET /api/categories
 * Response: {"success":true,"data":[{id,name,slug,description,price,availableCount,...}]}
 * Endpoint này public (không bắt buộc api_key) nhưng vẫn gửi để đồng bộ với các call khác
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param string $proxy Proxy nếu có
 * @return string JSON response chứa danh sách categories
 */
function listProduct_API_42($domain, $api_key, $proxy = '')
{
    $ch = curl_init();
    // GET /api/categories — API flat list, không phân trang nên lấy 1 lần
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/categories');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . $api_key,
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Tạo đơn hàng — POST /api/orders
 * Body JSON: {"categoryId": "uuid", "quantity": N}
 * Response: {"success":true,"data":{"orderId":"...","status":"pending",...}}
 * LƯU Ý: Đơn trả status=pending → cần gọi getOrder_API_42 để download accounts
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực (truyền qua x-api-key header)
 * @param string $category_id ID category (UUID, chính là api_id đã lưu)
 * @param int $quantity Số lượng mua
 * @param string $proxy Proxy nếu có
 * @return string JSON response {"success":true,"data":{"orderId":"..."}}
 */
function buy_API_42($domain, $api_key, $category_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    // POST /api/orders — endpoint tạo đơn hàng
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    // Payload: categoryId là string UUID, quantity là integer
    $payload = [
        'categoryId' => strval($category_id),
        'quantity'   => intval($quantity)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // Xác thực qua header x-api-key (chữ thường theo tài liệu)
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . $api_key,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (giúp debug production issues)
    if ($data === false) {
        error_log("[API_42] cURL error in buy_API_42: " . curl_error($ch) . " | URL: " . rtrim($domain, '/') . '/api/orders');
    }
    curl_close($ch);
    return $data;
}

/**
 * Download accounts từ đơn hàng — GET /api/orders/{orderId}/download
 * Response là TEXT THUẦN (không phải JSON), mỗi dòng một tài khoản: email|password|recovery
 * Trả rỗng / 404 / JSON error nếu đơn chưa sẵn sàng (status=pending chưa xử lý xong)
 * @param string $domain Domain API
 * @param string $api_key API Key xác thực
 * @param string $order_id ID đơn hàng (UUID từ buy_API_42)
 * @param string $proxy Proxy nếu có
 * @return array ['body' => string, 'http_code' => int] — cần phân biệt text thật vs lỗi pending
 */
function getOrder_API_42($domain, $api_key, $order_id, $proxy = '')
{
    $ch = curl_init();
    // GET /api/orders/{orderId}/download — endpoint tải danh sách accounts
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/orders/' . rawurlencode($order_id) . '/download');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . $api_key,
        'Accept: text/plain, application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Log lỗi cURL nếu request thất bại
    if ($body === false) {
        error_log("[API_42] cURL error in getOrder_API_42: " . curl_error($ch) . " | URL: " . rtrim($domain, '/') . '/api/orders/' . $order_id . '/download');
    }
    curl_close($ch);
    // Trả cả body và http_code để caller quyết định retry hay không (endpoint có thể trả JSON lỗi khi pending)
    return [
        'body'      => $body,
        'http_code' => $http_code
    ];
}

function balance_API_43($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/users/profile');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: OAuth ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function listProduct_API_43($domain, $token, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/products/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: OAuth ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function buy_API_43($domain, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/orders/?product_id=' . $product_id . '&quantity=' . $quantity);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: OAuth ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// ==================== API_44 - Buyer API (X-Buyer-Key) ====================
// Auth: Header X-Buyer-Key (lưu trong trường token của bảng suppliers)
// Endpoints:
//   GET /api/buyer/balance  → {balance, user_id, name}
//   GET /api/buyer/products → {products: [{product_id, name, price, stock}]}
//   POST /api/buyer/order   → {order_id, items: [{product: "..."}], total, new_balance}
// Đặc điểm: giá tính bằng USDT, product_id là integer, items trả về ngay (không cần getOrder)

/**
 * Lấy số dư tài khoản - GET /api/buyer/balance
 * Xác thực bằng header X-Buyer-Key (hoặc X-API-Key đều được)
 * @param string $domain  Domain API dạng http://IP:PORT
 * @param string $token   Buyer API Key (lấy từ bot Telegram của shop)
 * @param string $proxy   Proxy nếu có (format: ip:port:user:pass)
 * @return string JSON response {"balance": float, "user_id": int, "name": string}
 */
function balance_API_44($domain, $token, $proxy = '')
{
    $ch = curl_init();
    // GET /api/buyer/balance — endpoint lấy số dư, xác thực qua X-Buyer-Key header
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/buyer/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // X-Buyer-Key là header chính, gửi thêm X-API-Key để tương thích các server cũ hơn
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Buyer-Key: ' . $token,
        'X-API-Key: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ (format: ip:port hoặc ip:port:user:pass)
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm - GET /api/buyer/products
 * Trả về flat list, chỉ hiển thị sản phẩm đang active, stock > 0 là còn hàng
 * product_id là integer (dùng khi đặt hàng), price tính bằng USDT
 * @param string $domain  Domain API dạng http://IP:PORT
 * @param string $token   Buyer API Key
 * @param string $proxy   Proxy nếu có
 * @return string JSON response {"products": [{product_id, name, price, stock, description}]}
 */
function listProduct_API_44($domain, $token, $proxy = '')
{
    $ch = curl_init();
    // GET /api/buyer/products — lấy toàn bộ sản phẩm active (không phân trang)
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/buyer/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Buyer-Key: ' . $token,
        'X-API-Key: ' . $token,
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm - POST /api/buyer/order
 * Body JSON: {"product_id": int, "quantity": int}
 * Header: X-Idempotency-Key được gửi tự động để chống đặt trùng khi retry
 * Response: {"order_id": int, "items": [{"product": "email|pass|..."}], "total": float, "new_balance": float}
 * LƯU Ý: Dữ liệu giao hàng trả về ngay (không cần gọi getOrder), mỗi item có trường "product"
 * @param string $domain     Domain API dạng http://IP:PORT
 * @param string $token      Buyer API Key
 * @param int    $product_id ID sản phẩm (integer, lấy từ listProduct)
 * @param int    $quantity   Số lượng mua
 * @param string $proxy      Proxy nếu có
 * @return string JSON response
 */
function buy_API_44($domain, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    // POST /api/buyer/order — endpoint mua hàng, trả về items ngay lập tức
    curl_setopt($ch, CURLOPT_URL, rtrim($domain, '/') . '/api/buyer/order');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout cao hơn vì cần chờ API xử lý đơn hàng
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    // Payload: product_id phải là integer theo spec API
    $payload = [
        'product_id' => intval($product_id),
        'quantity'   => intval($quantity)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // Gửi X-Idempotency-Key để chống tạo đơn hàng trùng khi retry (theo hướng dẫn API)
    // Dùng uniqid để mỗi lần gọi có key khác nhau (không retry nên không cần lưu lại)
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Buyer-Key: ' . $token,
        'X-API-Key: ' . $token,
        'X-Idempotency-Key: ' . uniqid('sc7_', true),
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (hỗ trợ debug production)
    if ($data === false) {
        error_log('[API_44] cURL error in buy_API_44: ' . curl_error($ch) . ' | URL: ' . rtrim($domain, '/') . '/api/buyer/order');
    }
    curl_close($ch);
    return $data;
}

// ==================== API_45 - Telegram Buyer API (Dạng tgb_xxx) ====================
// Auth: Query param ?key=tgb_xxx (key gửi trong URL, không phải header)
// Domain cố định (được mã hóa trong hàm, không cần nhập)
// Endpoints:
//   GET  /api/telegram-buyer/balance?key=xxx  → {success, balance, balanceText, walletCurrency}
//   GET  /api/telegram-buyer/products?key=xxx → {success, products: [{_id, product_name, pricing, stats}]}
//   POST /api/telegram-buyer/purchase          → {success, orderCode, deliveredAccounts: [{user, password, verifyEmail}]}
// Đặc điểm:
//   - product_id là string MongoDB ObjectId (VD: "64f0c0f2b90c2b4c5a123456")
//   - deliveredAccounts trả object riêng (user + password + verifyEmail), không phải string gộp
//   - walletCurrency có thể là VND hoặc USD tùy loại bot

/**
 * Lấy số dư tài khoản — GET /api/telegram-buyer/balance?key=xxx
 * Domain được mã hóa sẵn trong hàm, key truyền qua query param
 * @param string $domain  Không dùng (bỏ qua) — domain được hardcode trong hàm
 * @param string $token   Buyer API Key dạng tgb_xxxxx
 * @param string $proxy   Proxy nếu có (format: ip:port hoặc ip:port:user:pass)
 * @return string JSON response {success, balance, balanceText, walletCurrency, ...}
 */
function balance_API_45($domain, $token, $proxy = '')
{
    $ch = curl_init();
    // Domain được mã hóa sẵn — key gửi qua query param (không phải header)
    curl_setopt($ch, CURLOPT_URL, 'https://' . 'canboso' . '.com/api/telegram-buyer/balance?key=' . urlencode($token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm — GET /api/telegram-buyer/products?key=xxx
 * Response: {success, products: [{_id, product_name, pricing, walletPricing, stats: {available}}]}
 * product_id là _id (MongoDB ObjectId string) — dùng khi mua hàng
 * pricing là giá theo walletCurrency (VND hoặc USD tùy key)
 * @param string $domain  Không dùng — domain được hardcode trong hàm
 * @param string $token   Buyer API Key dạng tgb_xxxxx
 * @param string $proxy   Proxy nếu có
 * @return string JSON response
 */
function listProduct_API_45($domain, $token, $proxy = '')
{
    $ch = curl_init();
    // Domain được mã hóa sẵn — key gửi qua query param
    curl_setopt($ch, CURLOPT_URL, 'https://' . 'canboso' . '.com/api/telegram-buyer/products?key=' . urlencode($token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm — POST /api/telegram-buyer/purchase
 * Body JSON: {"key": "tgb_xxx", "product_id": "64f0c0f2...", "quantity": N}
 * Response thành công: {success, orderCode, deliveredAccounts: [{user, password, verifyEmail}]}
 * Response lỗi: {success: false, message: "Wallet balance is not enough"}
 * LƯU Ý: deliveredAccounts là mảng object (không phải string) → cần ghép user|password|verifyEmail
 * @param string $domain      Không dùng — domain được hardcode trong hàm
 * @param string $token       Buyer API Key dạng tgb_xxxxx
 * @param string $product_id  MongoDB ObjectId string của sản phẩm
 * @param int    $quantity     Số lượng mua
 * @param string $proxy        Proxy nếu có
 * @return string JSON response
 */
function buy_API_45($domain, $token, $product_id, $quantity, $proxy = '')
{
    $ch = curl_init();
    // POST endpoint mua hàng — key nằm trong body JSON
    curl_setopt($ch, CURLOPT_URL, 'https://' . 'canboso' . '.com/api/telegram-buyer/purchase');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout cao vì API cần thời gian xử lý đơn hàng
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    // Payload: key trong body, product_id là string ObjectId, quantity là integer
    $payload = [
        'key'        => $token,
        'product_id' => strval($product_id),
        'quantity'   => intval($quantity)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (hỗ trợ debug production)
    if ($data === false) {
        error_log('[API_45] cURL error in buy_API_45: ' . curl_error($ch) . ' | product_id: ' . $product_id);
    }
    curl_close($ch);
    return $data;
}

// ==================== API_46 - Shop Bot API (Dạng sk_xxx) ====================
// Auth: Header X-API-Key: sk_xxxx
// Domain: Điền động (ví dụ: http://api-domain.com:20291)
// Endpoints:
//   GET  /api/balance                    → {"success": true, "user_id": 1961783225, "balance": 0}
//   GET  /api/products                   → {"success": true, "products": [{"id": "str", "name": "str", "price": 10000, "stock": 10, "description": "str"}]}
//   POST /api/buy                        → {"success": true, "order": {"order_code": "str", "accounts": ["user|pass"]}}

/**
 * Lấy số dư tài khoản — GET /api/balance
 * @param string $domain  Domain API nhà cung cấp
 * @param string $token   API Key dạng sk_xxxxx
 * @param string $proxy   Proxy nếu có (format: ip:port hoặc ip:port:user:pass)
 * @return string JSON response {success, balance, user_id}
 */
function balance_API_46($domain, $token, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'X-API-Key: ' . $token,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    if ($data === false) {
        error_log('[API_46] cURL error in balance_API_46: ' . curl_error($ch));
    }
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm — GET /api/products
 * @param string $domain  Domain API nhà cung cấp
 * @param string $token   API Key dạng sk_xxxxx
 * @param string $proxy   Proxy nếu có
 * @return string JSON response
 */
function listProduct_API_46($domain, $token, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'X-API-Key: ' . $token,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    if ($data === false) {
        error_log('[API_46] cURL error in listProduct_API_46: ' . curl_error($ch));
    }
    curl_close($ch);
    return $data;
}

/**
 * Mua hàng — POST /api/buy
 * @param string $domain      Domain API nhà cung cấp
 * @param string $token       API Key dạng sk_xxxxx
 * @param string $product_id  ID sản phẩm từ API
 * @param int    $quantity     Số lượng mua
 * @param string $proxy       Proxy nếu có
 * @return string JSON response
 */
function buy_API_46($domain, $token, $product_id, $quantity, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/buy');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    $payload = [
        'product_id' => strval($product_id),
        'quantity'   => intval($quantity)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'X-API-Key: ' . $token,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    // Thêm proxy nếu có và hợp lệ
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    $data = curl_exec($ch);
    if ($data === false) {
        error_log('[API_46] cURL error in buy_API_46: ' . curl_error($ch) . ' | product_id: ' . $product_id);
    }
    curl_close($ch);
    return $data;
}


// ==================== API_47 - API_47 API ====================

/**
 * Thực hiện gọi cURL tới đối tác API_47
 */
function call_API_47($domain, $endpoint, $token, $method = 'GET', $data = [], $proxy = '')
{
    // Đảm bảo domain kết thúc bằng dấu gạch chéo
    $domain = rtrim($domain, '/') . '/';
    $url = $domain . ltrim($endpoint, '/');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $headers = [
        "X-API-KEY: " . $token,
        "Accept: application/json",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
    ];
    
    if (strtoupper($method) == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = "Content-Type: application/json";
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Thêm proxy nếu có cấu hình
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * Lấy thông tin số dư tài khoản của đối tác API_47
 */
function balance_API_47($domain, $token, $proxy = '')
{
    return call_API_47($domain, 'api/dealer/stock', $token, 'GET', [], $proxy);
}

/**
 * Lấy danh sách sản phẩm hiện có từ đối tác API_47
 */
function listProduct_API_47($domain, $token, $proxy = '')
{
    return call_API_47($domain, 'api/dealer/stock', $token, 'GET', [], $proxy);
}

/**
 * Thực hiện mua sản phẩm từ đối tác API_47
 */
function buy_API_47($domain, $token, $product_key, $qty, $proxy = '')
{
    $data = [
        'product_key' => $product_key,
        'qty' => intval($qty)
    ];
    return call_API_47($domain, 'api/dealer/buy', $token, 'POST', $data, $proxy);
}


// ==================== API_48 - APIv7 Compatibility ====================
// Lớp tương thích APIv7: giữ nguyên kiểu yêu cầu cũ với api_key, các endpoint dạng .php
// và các trường response cũ như status, msg, trans_id và data (giống SHOPCLONE7 nhưng có
// tiền tố /api/v7/ trong URL).

/**
 * Áp dụng proxy cho cURL handle nếu cấu hình hợp lệ.
 * Hỗ trợ cả hai định dạng: ip:port và ip:port:user:pass.
 *
 * @param resource $ch    cURL handle đang khởi tạo
 * @param string   $proxy Chuỗi proxy theo định dạng phía trên
 * @return void
 */
function _apply_proxy_API_48($ch, $proxy)
{
    if (empty($proxy)) {
        return;
    }
    $proxy_parts = explode(':', $proxy);
    if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
    } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
    }
}

/**
 * Lấy số dư & thông tin tài khoản từ APIv7 (profile.php).
 *
 * @param string $domain  Domain nhà cung cấp (VD: https://api.example.com/)
 * @param string $api_key API Key của tài khoản trên nhà cung cấp
 * @param string $proxy   Proxy nếu có
 * @return string JSON response thô từ API (để cron/admin tự decode)
 */
function balance_API_48($domain, $api_key, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/v7/profile.php?api_key=' . urlencode($api_key));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    _apply_proxy_API_48($ch, $proxy);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm theo chuyên mục từ APIv7 (products.php).
 * Response trả về cấu trúc categories[] -> products[] (flat, không có chuyên mục cha).
 *
 * @param string $domain  Domain nhà cung cấp
 * @param string $api_key API Key
 * @param string $proxy   Proxy nếu có
 * @return string JSON response thô
 */
function listProduct_API_48($domain, $api_key, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/v7/products.php?api_key=' . urlencode($api_key));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    _apply_proxy_API_48($ch, $proxy);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm qua APIv7 (buy_product) - dùng POST với action=buyProduct.
 * Lưu ý: endpoint buy_product KHÔNG có đuôi .php (theo tài liệu API).
 *
 * @param string $domain   Domain nhà cung cấp
 * @param string $api_key  API Key
 * @param string $id_api   ID sản phẩm trên hệ thống đối tác
 * @param int    $amount   Số lượng cần mua
 * @param string $proxy    Proxy nếu có
 * @return string JSON response thô, có gắn thêm http_code để xử lý lỗi 402 (hết tiền)
 */
function buy_API_48($domain, $api_key, $id_api, $amount, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $domain . '/api/v7/buy_product',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'action'  => 'buyProduct',
            'id'      => $id_api,
            'amount'  => $amount,
            'api_key' => $api_key
        ),
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ),
    ));
    _apply_proxy_API_48($curl, $proxy);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Gắn http_code vào response JSON để phía gọi xử lý các trường hợp như 402 (hết tiền)
    if ($response) {
        $result = json_decode($response, true);
        if (is_array($result)) {
            $result['http_code'] = $http_code;
            return json_encode($result);
        }
    }

    // Fallback khi response rỗng hoặc không phải JSON hợp lệ
    return json_encode([
        'status'    => 'error2',
        'msg'       => __('Mất kết nối đến kho hàng'),
        'http_code' => $http_code
    ]);
}

/**
 * Lấy chi tiết đơn hàng đã mua trên APIv7 (order.php) theo trans_id.
 * Dùng để retry/khôi phục dữ liệu account khi buy bị mất kết nối giữa chừng.
 *
 * @param string $domain    Domain nhà cung cấp
 * @param string $api_key   API Key
 * @param string $trans_id  Mã đơn hàng (public_id) do API trả về khi mua
 * @param string $proxy     Proxy nếu có
 * @return string JSON response thô
 */
function getOrder_API_48($domain, $api_key, $trans_id, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/v7/order.php?api_key=' . urlencode($api_key) . '&order=' . urlencode($trans_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    _apply_proxy_API_48($ch, $proxy);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// ==================== API_49 - Proxy API ====================

/**
 * Hàm áp dụng cấu hình proxy cho cURL của API_49
 *
 * @param resource $ch     Handle cURL
 * @param string   $proxy  Chuỗi proxy dạng ip:port hoặc ip:port:user:pass
 * @return void
 */
function _apply_proxy_API_49($ch, $proxy)
{
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }
}

/**
 * Lấy số dư tài khoản của API 49.
 * Do API này không hỗ trợ API lấy số dư nên trả về thông báo không hỗ trợ.
 *
 * @param string $domain  Domain của nhà cung cấp
 * @param string $key     API Key / Token
 * @param string $proxy   Proxy nếu có
 * @return string JSON response
 */
function balance_API_49($domain, $key, $proxy = '')
{
    return json_encode([
        'status'  => 1,
        'balance' => 'Không hỗ trợ'
    ]);
}

/**
 * Lấy danh sách sản phẩm của API 49.
 * Do API này không có API lấy danh sách sản phẩm nên trả về danh sách sản phẩm cố định.
 *
 * @param string $domain  Domain của nhà cung cấp
 * @param string $key     API Key / Token
 * @param string $proxy   Proxy nếu có
 * @return string JSON response
 */
function listProduct_API_49($domain, $key, $proxy = '')
{
    // Danh sách loại proxy được định nghĩa cố định theo tài liệu đối tác
    $proxy_types = [
        'Viettel'       => 'Proxy Viettel',
        'FPT'           => 'Proxy FPT',
        'VNPT'          => 'Proxy VNPT',
        'US'            => 'Proxy US',
        'DatacenterA'   => 'Proxy DatacenterA',
        'DatacenterB'   => 'Proxy DatacenterB',
        'DatacenterC'   => 'Proxy DatacenterC',
        'GoiViettel'    => 'Proxy GoiViettel',
        'GoiVNPT'       => 'Proxy GoiVNPT',
        'GoiFPT'        => 'Proxy GoiFPT',
        'GoiDATACENTER' => 'Proxy GoiDATACENTER',
        '4Gvinaphone'   => 'Proxy 4Gvinaphone'
    ];

    // Các gói ngày sử dụng khác nhau để tạo ra nhiều sản phẩm cho mỗi loại proxy
    $day_options = [1, 3, 7, 14, 30, 90];

    $products = [];
    foreach ($proxy_types as $type_id => $type_name) {
        foreach ($day_options as $days) {
            // api_id dạng: loaiproxy|ngay — hàm buy_API_49 sẽ tự phân tách để gọi API đúng
            $products[] = [
                'id'       => $type_id . '|' . $days,
                'name'     => $type_name . ' - ' . $days . ' ngày',
                'price'    => 99999,
                'in_stock' => 9999
            ];
        }
    }

    return json_encode([
        'status' => 1,
        'data'   => $products
    ]);
}

/**
 * Gọi API mua proxy từ nhà cung cấp.
 *
 * @param string $domain      Domain nhà cung cấp
 * @param string $key         API Key
 * @param string $loaiproxy   Loại proxy cần mua (api_id)
 * @param int    $quantity    Số lượng proxy cần mua
 * @param string $proxy       Proxy cấu hình cho request nếu có
 * @return string JSON response từ đối tác
 */
function buy_API_49($domain, $key, $loaiproxy, $quantity, $proxy = '')
{
    $domain = rtrim($domain, '/');
    if (empty($domain) || strpos($domain, 'http') === false) {
        $domain = 'https://topproxy.vn';
    }
    
    // Tách cấu hình tùy chọn từ api_id (dạng: loaiproxy|ngay|type|user|password)
    $parts = explode('|', $loaiproxy);
    $real_loaiproxy = isset($parts[0]) ? trim($parts[0]) : '';
    $ngay = (isset($parts[1]) && intval($parts[1]) > 0) ? intval($parts[1]) : 1;
    $type = (isset($parts[2]) && in_array(strtoupper(trim($parts[2])), ['HTTP', 'SOCKS5'])) ? strtoupper(trim($parts[2])) : 'HTTP';
    $user = (isset($parts[3]) && trim($parts[3]) !== '') ? trim($parts[3]) : 'random';
    $pass = (isset($parts[4]) && trim($parts[4]) !== '') ? trim($parts[4]) : 'random';

    $url = $domain . "/apiv2/muaproxy.php";
    
    // Tạo tham số query
    $params = [
        'key' => $key,
        'loaiproxy' => $real_loaiproxy,
        'soluong' => intval($quantity),
        'ngay' => $ngay,
        'type' => $type,
        'user' => $user,
        'password' => $pass
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));
    
    _apply_proxy_API_49($ch, $proxy);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Nếu lỗi kết nối HTTP
    if ($http_code != 200) {
        return json_encode([
            'status' => 'error2',
            'msg' => 'Mất kết nối đến kho hàng đối tác',
            'http_code' => $http_code
        ]);
    }
    
    return $response;
}


// ==================== API_50 - Buyer API (Authorization Bearer, walletCurrency, deliveredAccounts) ====================
// Auth: Header Authorization: Bearer tgb_xxx (lưu trong token của bảng suppliers)
// Endpoints:
//   GET  /api/telegram-buyer/balance?key=xxx  → {success, balance, balanceText, walletCurrency}
//   GET  /api/telegram-buyer/products         → {success, products: [{_id, product_name, pricing, stats}]}
//   POST /api/telegram-buyer/purchase         → {success, orderCode, deliveredAccounts: [{user, password, raw}]}

/**
 * Lấy số dư tài khoản từ API_50
 *
 * @param string $domain  Domain của nhà cung cấp
 * @param string $token   API Key / Token dạng tgb_xxx
 * @param string $proxy   Proxy nếu có (format: ip:port hoặc ip:port:user:pass)
 * @return string JSON response
 */
function balance_API_50($domain, $token, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $domain = preg_replace('/\/api-guide$/i', '', $domain);
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    // Query param cho API check balance theo tài liệu
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/telegram-buyer/balance?key=' . urlencode($token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có cấu hình
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    if ($data === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode([
            'success' => false,
            'message' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm từ API_50
 *
 * @param string $domain  Domain của nhà cung cấp
 * @param string $token   API Key / Token dạng tgb_xxx
 * @param string $proxy   Proxy nếu có
 * @return string JSON response
 */
function listProduct_API_50($domain, $token, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $domain = preg_replace('/\/api-guide$/i', '', $domain);
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/telegram-buyer/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có cấu hình
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    if ($data === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode([
            'success' => false,
            'message' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm từ API_50
 *
 * @param string $domain      Domain của nhà cung cấp
 * @param string $token       API Key / Token dạng tgb_xxx
 * @param string $product_id  ID sản phẩm từ API_50 (_id dạng MongoDB ObjectId)
 * @param int    $quantity    Số lượng mua
 * @param string $proxy       Proxy nếu có
 * @return string JSON response
 */
function buy_API_50($domain, $token, $product_id, $quantity, $proxy = '')
{
    $domain = rtrim($domain, '/');
    $domain = preg_replace('/\/api-guide$/i', '', $domain);
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/api/telegram-buyer/purchase');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $payload = [
        'product_id' => strval($product_id),
        'quantity'   => intval($quantity)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Idempotency-Key: ' . uniqid('tgb_', true),
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    // Thêm proxy nếu có cấu hình
    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    // Log lỗi cURL nếu request thất bại (hỗ trợ debug production)
    if ($data === false) {
        $error = curl_error($ch);
        error_log('[API_50] cURL error in buy_API_50: ' . $error . ' | product_id: ' . $product_id);
        curl_close($ch);
        return json_encode([
            'success' => false,
            'message' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}

// ==================== API_51 - Nas Nabi API (X-API-Key header, ok status, balance & orders endpoints) ====================
// Auth: Header X-API-Key: psk_xxx (lưu trong api_key của bảng suppliers)
// Endpoints:
//   GET  /balance         → {ok, shopId, balance, balanceText}
//   GET  /products        → {ok, products: [{productId, code, title, price, stock}]}
//   POST /orders          → {ok, order: {orderCode, shopOrderId, product, qty, price, total, status, balanceAfter, accounts: [...]}}

/**
 * Lấy số dư tài khoản từ API_51 (Nas Nabi API)
 *
 * @param string $domain  Domain nhà cung cấp cấu hình
 * @param string $api_key API Key kết nối
 * @param string $proxy   Proxy cấu hình nếu có
 * @return string JSON response
 */
function balance_API_51($domain, $api_key, $proxy = '')
{
    // Đảm bảo không có dấu gạch chéo dư thừa ở cuối
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    if ($data === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode([
            'ok' => false,
            'error' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}

/**
 * Lấy danh sách sản phẩm từ API_51 (Nas Nabi API)
 *
 * @param string $domain  Domain nhà cung cấp cấu hình
 * @param string $api_key API Key kết nối
 * @param string $proxy   Proxy cấu hình nếu có
 * @return string JSON response
 */
function listProduct_API_51($domain, $api_key, $proxy = '')
{
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    if ($data === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode([
            'ok' => false,
            'error' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}

/**
 * Mua sản phẩm từ API_51 (Nas Nabi API)
 *
 * @param string $domain       Domain nhà cung cấp cấu hình
 * @param string $api_key      API Key kết nối
 * @param mixed  $product_id   ID sản phẩm (hoặc code sản phẩm)
 * @param int    $quantity     Số lượng mua
 * @param string $shop_order_id Mã đơn hàng chống trùng
 * @param string $proxy        Proxy cấu hình nếu có
 * @return string JSON response
 */
function buy_API_51($domain, $api_key, $product_id, $quantity, $shop_order_id = '', $proxy = '')
{
    $domain = rtrim($domain, '/');
    if (strpos($domain, 'http://') === 0) {
        $domain = str_replace('http://', 'https://', $domain);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $domain . '/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $payload = [
        'qty' => intval($quantity)
    ];
    if (is_numeric($product_id)) {
        $payload['productId'] = intval($product_id);
    } else {
        $payload['code'] = strval($product_id);
    }

    if (!empty($shop_order_id)) {
        $payload['shopOrderId'] = strval($shop_order_id);
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ));

    if (!empty($proxy)) {
        $proxy_parts = explode(':', $proxy);
        if (count($proxy_parts) == 4 && !empty($proxy_parts[0]) && !empty($proxy_parts[1]) && !empty($proxy_parts[2]) && !empty($proxy_parts[3])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_parts[2] . ':' . $proxy_parts[3]);
        } elseif (count($proxy_parts) == 2 && !empty($proxy_parts[0]) && !empty($proxy_parts[1])) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_parts[0] . ':' . $proxy_parts[1]);
        }
    }

    $data = curl_exec($ch);
    if ($data === false) {
        $error = curl_error($ch);
        error_log('[API_51] cURL error in buy_API_51: ' . $error . ' | product_id: ' . $product_id);
        curl_close($ch);
        return json_encode([
            'ok' => false,
            'error' => 'Lỗi kết nối cURL: ' . $error
        ]);
    }
    curl_close($ch);
    return $data;
}


