<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Chỉnh sửa API nhà cung cấp',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
 
';
require_once(__DIR__ . '/../../libs/suppliers.php');
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    if (!$supplier = $CMSNT->get_row("SELECT * FROM `suppliers` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('product-api'));
    }
} else {
    redirect(base_url_admin('product-api'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['save'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if (empty($_POST['type'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng chọn loại API cần kết nối")){window.history.back().location.reload();}</script>');
    }
    $type = check_string($_POST['type']);
    if (empty($_POST['domain'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập domain cần kết nối")){window.history.back().location.reload();}</script>');
    }
    $domain = check_string($_POST['domain']);
    if (in_array($domain, $domain_blacklist)) {
        die('<script type="text/javascript">if(!alert("' . $domain . ' nằm trong danh sách đen, không thể kết nối")){window.history.back().location.reload();}</script>');
    }

    $price = '';
    $token = !empty($_POST['token']) ? check_string($_POST['token']) : NULL;
    if ($type == 'SHOPCLONE6') {
        $checkdomain = checkDomainAPI(check_string($_POST['domain']), check_string($_POST['proxy']));
        if ($checkdomain['status'] == false) {
            die('<script type="text/javascript">if(!alert("' . $checkdomain['msg'] . '")){window.history.back().location.reload();}</script>');
        }

        $data = balance_API_SHOPCLONE6(check_string($_POST['domain']), check_string($_POST['username']), check_string($_POST['password']), check_string($_POST['proxy']));
        $price = $data;
        $data = json_decode($data, true);
        if (isset($data['status']) && $data['status'] == 'error') {
            die('<script type="text/javascript">if(!alert("' . $data['msg'] . '")){window.history.back().location.reload();}</script>');
        }
    } else if ($type == 'SHOPKEY') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Secret Key")){window.history.back().location.reload();}</script>');
        }
        $checkdomain = checkDomainAPI(check_string($_POST['domain']), check_string($_POST['proxy']));
        if ($checkdomain['status'] == false) {
            die('<script type="text/javascript">if(!alert("' . $checkdomain['msg'] . '")){window.history.back().location.reload();}</script>');
        }

        // Gọi API với api_key và secret_key riêng biệt
        $response = balance_API_SHOPKEY(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($response, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['data']['balance']['current']);
        // Lưu token riêng (không ghép vào api_key)
        $token = check_string($_POST['token']);
    } else if ($type == 'SHOPCLONE7') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $checkdomain = checkDomainAPI(check_string($_POST['domain']), check_string($_POST['proxy']));
        if ($checkdomain['status'] == false) {
            die('<script type="text/javascript">if(!alert("' . $checkdomain['msg'] . '")){window.history.back().location.reload();}</script>');
        }

        // Kiểm tra nếu chọn child = 1 thì phải có API products_child.php
        if (isset($_POST['child']) && intval($_POST['child']) == 1) {
            $check_child_api = listProduct_API_SHOPCLONE7(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']), true);
            $check_result = json_decode($check_child_api, true);
            if (!isset($check_result['status']) || $check_result['status'] == 'error') {
                die('<script type="text/javascript">if(!alert("Website mà bạn kết nối chưa cập nhật phiên bản mới nhất nên không thể đồng bộ chuyên mục Cha → Con → Sản phẩm. Vui lòng chọn OFF hoặc liên hệ với chủ website để cập nhật.")){window.history.back().location.reload();}</script>');
            }
        }

        $response = balance_API_SHOPCLONE7(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($response, true);
        if ($result['status'] == 'error') {
            $price = $result['msg'];
            die('<script type="text/javascript">if(!alert("' . $result['msg'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['data']['money']);
    } else if ($type == 'API_1') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $response = balance_API_1(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($response, true);
        if ($result['status'] != true) {
            $price = $result['msg'];
            die('<script type="text/javascript">if(!alert("' . $result['msg'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['balance']);
    } else if ($type == 'API_4') {
        if (empty($_POST['username'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập username")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['password'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập password")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_4(check_string($_POST['domain']), check_string($_POST['username']), check_string($_POST['password']));
        $result = json_decode($result, true);
        if (!isset($result['data']['Data']['userDetail']['coin'])) {
            die('<script type="text/javascript">if(!alert("Thông tin kết nối không chính xác")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency(check_string($result['data']['Data']['userDetail']['coin']));
        $token = check_string($result['data']['Data']['accessToken']);
    } else if ($type == 'API_6') {
        $result = balance_API_6(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (!isset($result['balance'])) {
            die('<script type="text/javascript">if(!alert("' . $result['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['balance']);
    } else if ($type == 'API_9') {
        $result = balance_API_9(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if ($result['error'] != 0) {
            die('<script type="text/javascript">if(!alert("' . $result['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['data']['balance']);
    } else if ($type == 'API_14') {
        $result = balance_API_14(check_string($_POST['domain']), check_string($_POST['token']));
        $result = json_decode($result, true);
        if (!isset($result['user'])) {
            die('<script type="text/javascript">if(!alert("' . $result['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['user']['balance']);
    } else if ($type == 'API_17') {
        if (empty($_POST['username'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập username")){window.history.back().location.reload();}</script>');
        }
        $username = check_string($_POST['username']);
        if (empty($_POST['password'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập password")){window.history.back().location.reload();}</script>');
        }
        $password = check_string($_POST['password']);
        $data = balance_API_17(check_string($_POST['domain']), $username, $password);
        $price = $data;
        $data = json_decode($data, true);
        if (isset($data['status']) && $data['status'] == 'error') {
            die('<script type="text/javascript">if(!alert("' . $data['msg'] . '")){window.history.back().location.reload();}</script>');
        }
    } else if ($type == 'API_18') {
        $result = balance_API_18(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (isset($result['error'])) {
            die('<script type="text/javascript">if(!alert("' . $result['error'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = '$' . $result['balance'];
    } else if ($type == 'API_19') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_19(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (!isset($result['balance'])) {
            die('<script type="text/javascript">if(!alert("' . $result['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['balance']);
    } else if ($type == 'API_20') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập kioskToken")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập userToken")){window.history.back().location.reload();}</script>');
        }
        $result = curl_get(check_string($_POST['domain']) . 'api/getStock?kioskToken=' . check_string($_POST['api_key']) . '&userToken=' . check_string($_POST['token']));
        $result = json_decode($result, true);
        if ($result['success'] != true) {
            die('<script type="text/javascript">if(!alert("' . $result['description'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = check_string($result['name']);
    } else if ($type == 'API_21') {
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập token")){window.history.back().location.reload();}</script>');
        }
        $price = 'Không có API lấy số dư';
    } else if ($type == 'API_22') {
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập token")){window.history.back().location.reload();}</script>');
        }
        $price = 'Không có API lấy số dư';
    } else if ($type == 'API_23') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập kioskToken")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_23(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if ($result['Code'] != 0) {
            die('<script type="text/javascript">if(!alert("' . $result['Message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = check_string('$' . $result['Balance']);
    } else if ($type == 'API_24') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_24(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (!isset($result['data'][0]['money_available'])) {
            die('<script type="text/javascript">if(!alert("[SYSTEM] Thông tin kết nối không chính xác")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency(check_string($result['data'][0]['money_available']));
    } else if ($type == 'API_25') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_25(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (isset($result) && $result['Code'] == 1) {
            die('<script type="text/javascript">if(!alert("' . $result['Message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = '$' . $result['Balance'];
    } else if ($type == 'API_26') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_26(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['token']));
        $result = json_decode($result, true);
        if (!isset($result['status']) && $result['status'] != 'ok') {
            die('<script type="text/javascript">if(!alert("Thông tin kết nối không chính xác")){window.history.back().location.reload();}</script>');
        }
        $price = check_string($result['balance']);
    } else if ($type == 'API_27') {
        $price = __('Không có API lấy số dư');
    } else if ($type == 'API_28') {
        if (empty($_POST['username'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập username")){window.history.back().location.reload();}</script>');
        }
        $username = check_string($_POST['username']);
        if (empty($_POST['password'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập password")){window.history.back().location.reload();}</script>');
        }
        $password = check_string($_POST['password']);
        $data = balance_API_28(check_string($_POST['domain']), $username, $password);
        $data = json_decode($data, true);
        if (isset($data['status']) && $data['status'] == 'error') {
            die('<script type="text/javascript">if(!alert("' . $data['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = check_string($data['amount']);
        $token = check_string($data['user_token']);
    } else if ($type == 'API_29') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_29(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (!isset($result['data'])) {
            die('<script type="text/javascript">if(!alert("Kết nối đến API không thành công!")){window.history.back().location.reload();}</script>');
        }
        $price = '$' . $result['data'];
    } else if ($type == 'API_30') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_30(check_string($_POST['domain']), check_string($_POST['api_key']));
        $result = json_decode($result, true);
        if (!isset($result['num'])) {
            die('<script type="text/javascript">if(!alert("Kết nối đến API không thành công!")){window.history.back().location.reload();}</script>');
        }
        $price = '¥' . $result['num'];
    } else if ($type == 'API_31') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_31(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if ($result['status'] == 'error') {
            $price = $result['msg'];
            die('<script type="text/javascript">if(!alert("' . $result['msg'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['data']['money']);
    } else if ($type == 'API_32') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $response = balance_API_32(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($response, true);
        if ($result['success'] != true) {
            die('<script type="text/javascript">if(!alert("' . $result['message'] . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['data']['balance']);
    } else if ($type == 'API_33') {
        if (empty($_POST['username'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập username")){window.history.back().location.reload();}</script>');
        }
        $username = check_string($_POST['username']);
        if (empty($_POST['password'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập password")){window.history.back().location.reload();}</script>');
        }
        $password = check_string($_POST['password']);
        $response = getToken_API_33(check_string($_POST['domain']), $username, $password, check_string($_POST['proxy']));
        $data = json_decode($response, true);
        if (!isset($data) || $data['code'] != '200000') {
            $errorMsg = isset($data['message']) ? $data['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // Lưu partner token
        $token = check_string($data['data']);
        // Lưu số dư
        $price = isset($data['balance']) ? format_currency($data['balance']) : '0';
    } else if ($type == 'API_34') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập api_key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_34(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = check_string($result['data']['balance']);
    } else if ($type == 'API_35') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Email")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_35(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // Hiển thị số dư WMR và USD
        $balance_wmr = isset($result['data']['balance_wmr']) ? $result['data']['balance_wmr'] : 0;
        $balance_usd = isset($result['data']['balance_usd']) ? $result['data']['balance_usd'] : 0;
        $price = $balance_wmr . ' WMR / $' . $balance_usd . ' USD';
    } else if ($type == 'API_36') {
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_36(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['code']) || $result['code'] != 1) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = '$' . check_string($result['data']['balance']);
    } else if ($type == 'API_37') {
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_37(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['status']) || $result['status'] != 1) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency(check_string($result['balance']));
    } else if ($type == 'API_38') {
        // API_38 sử dụng api_key là app_id và token là app_key
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập App ID")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập App Key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_38(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['code']) || $result['code'] != 200) {
            $errorMsg = isset($result['msg']) ? $result['msg'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $balance = isset($result['data']['balance']) ? $result['data']['balance'] : 0;
        $price = format_currency(check_string($balance));
    } else if ($type == 'API_39') {
        // API_39
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_39(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $balance = isset($result['data']['walletBalance']) ? $result['data']['walletBalance'] : 0;
        $price = format_currency(check_string($balance));
    } else if ($type == 'API_40') {
        // API_40 - Telegram Shop API, xác thực bằng api_key
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_40(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency(check_string($result['balance']));
    } else if ($type == 'API_41') {
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_41(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['error']) ? $result['error'] : (isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!');
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // Số dư nằm trong data.balance (khác API 40 nằm ở balance trực tiếp)
        $balance = isset($result['data']['balance']) ? $result['data']['balance'] : 0;
        $price = format_currency(check_string($balance));
    } else if ($type == 'API_42') {
        // API_42 — mail555.com không có endpoint balance, xác thực qua GET /api/categories
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_42(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['error']) ? $result['error'] : (isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công!');
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // API không có endpoint balance → hiển thị chuỗi placeholder thay vì số dư
        $price = 'Không có API lấy số dư';
    } else if ($type == 'API_43') { // API 43 BuyFB
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Token")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_43(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);

        // Cấu trúc trả về {"profile": {"balance": 199950.0}, "wallet": {"balance": 199950.0}}
        if (!isset($result['profile']['balance']) && !isset($result['wallet']['balance'])) {
            die('<script type="text/javascript">if(!alert("Kết nối đến API không thành công!")){window.history.back().location.reload();}</script>');
        }
        $balance = isset($result['profile']['balance']) ? $result['profile']['balance'] : $result['wallet']['balance'];
        $price = format_currency(check_string($balance));
    } else if ($type == 'API_44') {
        // API_44 - Buyer API, xác thực bằng X-Buyer-Key header (lưu trong trường token)
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Buyer API Key")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_44(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        // Response hợp lệ khi có trường balance (số dư tính bằng USDT)
        if (!isset($result['balance'])) {
            $errorMsg = isset($result['error']) ? $result['error'] : 'Kết nối đến API không thành công! Kiểm tra lại domain (IP:PORT) và Buyer API Key.';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // Hiển thị số dư USDT
        $price = '$' . check_string($result['balance']) . ' USDT';
    } else if ($type == 'API_45') {
        // API_45 - Telegram Buyer API
        // Key gửi qua query param (dạng tgb_xxx), domain được hardcode trong hàm
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Telegram Buyer Key (dạng tgb_xxx)")){window.history.back().location.reload();}</script>');
        }
        // Domain không cần nhập — được hardcode trong hàm
        $result = balance_API_45('', check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        // Response hợp lệ khi success = true và có trường balance
        if (!isset($result['success']) || $result['success'] != true || !isset($result['balance'])) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công! Kiểm tra lại Telegram Buyer Key.';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        // Hiển thị số dư kèm đơn vị tiền tệ (VND hoặc USD tùy loại bot)
        $currency = isset($result['walletCurrency']) ? $result['walletCurrency'] : '';
        $price = isset($result['balanceText']) ? check_string($result['balanceText']) : check_string($result['balance']) . ' ' . $currency;
    } else if ($type == 'API_46') {
        // API_46 - Shop Bot API
        if (empty($_POST['domain'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Domain API nhà cung cấp")){window.history.back().location.reload();}</script>');
        }
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key (dạng sk_xxx)")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_46(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true || !isset($result['balance'])) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công! Kiểm tra lại API Key hoặc Domain.';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency($result['balance']);
    } else if ($type == 'API_47') {
        // API_47 sử dụng token làm API Key
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Token (API Key)")){window.history.back().location.reload();}</script>');
        }
        $result = balance_API_47(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result, true);
        if (!isset($result['success']) || $result['success'] != true) {
            $errorMsg = isset($result['error']) ? $result['error'] : 'Kết nối đến API không thành công!';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $balance = isset($result['balance']) ? $result['balance'] : 0;
        $price = format_currency(check_string($balance));
    } else if ($type == 'API_48') {
        // API_48 - APIv7 Compatibility: sử dụng api_key qua query param
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        $response = balance_API_48(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($response, true);
        // APIv7 trả về status = "success" khi thành công, số dư nằm tại data.money
        if (!isset($result['status']) || $result['status'] != 'success' || !isset($result['data']['money'])) {
            $errorMsg = isset($result['msg']) ? $result['msg'] : 'Kết nối đến API không thành công! Kiểm tra lại Domain hoặc API Key.';
            die('<script type="text/javascript">if(!alert("' . $errorMsg . '")){window.history.back().location.reload();}</script>');
        }
        $price = format_currency(check_string($result['data']['money']));
    } else if ($type == 'API_49') {
        // API_49 - mua proxy tự động: kiểm tra api_key có được nhập không
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        $price = 'Không có API lấy số dư';
    } else if ($type == 'API_50') {
        // API_50 - Buyer API: yêu cầu token (API Key) dạng tgb_xxx
        if (empty($_POST['token'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập Buyer API Key (tgb_xxx)")){window.history.back().location.reload();}</script>');
        }
        // Gọi hàm check số dư API_50 để xác thực thông tin kết nối
        $result_raw = balance_API_50(check_string($_POST['domain']), check_string($_POST['token']), check_string($_POST['proxy']));
        $result = json_decode($result_raw, true);
        
        // Kiểm tra kết quả trả về từ API
        if (!isset($result['success']) || $result['success'] != true || !isset($result['balance'])) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Kết nối đến API không thành công! Vui lòng kiểm tra lại Domain và Token.';
            if ($result_raw !== false && !empty($result_raw)) {
                $errorMsg .= ' Chi tiết phản hồi: ' . strip_tags(substr($result_raw, 0, 300));
            }
            die('<script type="text/javascript">if(!alert("' . addslashes($errorMsg) . '")){window.history.back().location.reload();}</script>');
        }
        
        // Lưu trữ số dư định dạng text hiển thị
        $currency = isset($result['walletCurrency']) ? $result['walletCurrency'] : '';
        $price = isset($result['balanceText']) ? check_string($result['balanceText']) : check_string($result['balance']) . ' ' . $currency;
    } else if ($type == 'API_51') {
        // API_51 - Nas Nabi API: yêu cầu API Key
        if (empty($_POST['api_key'])) {
            die('<script type="text/javascript">if(!alert("Vui lòng nhập API Key")){window.history.back().location.reload();}</script>');
        }
        // Gọi hàm check số dư balance_API_51 để xác thực thông tin kết nối
        $result_raw = balance_API_51(check_string($_POST['domain']), check_string($_POST['api_key']), check_string($_POST['proxy']));
        $result = json_decode($result_raw, true);
        
        // Kiểm tra kết quả trả về từ API
        if (!isset($result['ok']) || $result['ok'] != true || !isset($result['balance'])) {
            $errorMsg = isset($result['error']) ? $result['error'] : 'Kết nối đến API không thành công! Vui lòng kiểm tra lại Domain và API Key.';
            if ($result_raw !== false && !empty($result_raw)) {
                $errorMsg .= ' Chi tiết phản hồi: ' . strip_tags(substr($result_raw, 0, 300));
            }
            die('<script type="text/javascript">if(!alert("' . addslashes($errorMsg) . '")){window.history.back().location.reload();}</script>');
        }
        
        // Lưu trữ số dư định dạng text hiển thị
        $price = isset($result['balanceText']) ? check_string($result['balanceText']) : format_currency(check_string($result['balance']));
    }



    $isUpdate = $CMSNT->update("suppliers", [
        'type'      => $type,
        'domain'    => $domain,
        'username'  => !empty($_POST['username']) ? check_string($_POST['username']) : NULL,
        'password'  => !empty($_POST['password']) ? check_string($_POST['password']) : NULL,
        'api_key'   => !empty($_POST['api_key']) ? check_string($_POST['api_key']) : NULL,
        'token'     => $token,
        'coupon'     => !empty($_POST['coupon']) ? check_string($_POST['coupon']) : NULL,
        'price'     => check_string($price),
        'check_string_api'      => check_string($_POST['check_string_api']),
        'discount'          => check_string($_POST['discount']),
        'update_name'       => check_string($_POST['update_name']),
        'proxy'       => check_string($_POST['proxy']),
        'sync_category'     => !empty($_POST['sync_category']) ? check_string($_POST['sync_category']) : 'OFF',
        'sync_category_image' => !empty($_POST['sync_category_image']) ? check_string($_POST['sync_category_image']) : 'ON',
        'child'             => isset($_POST['child']) ? intval($_POST['child']) : 0,
        'isAutoShow'        => isset($_POST['isAutoShow']) ? intval($_POST['isAutoShow']) : 0,
        'rate'              => !empty($_POST['rate']) ? check_string($_POST['rate']) : 1,
        'list_api_id'       => !empty($_POST['list_api_id']) ? check_string($_POST['list_api_id']) : NULL,
        'update_price'      => check_string($_POST['update_price']),
        'roundMoney'      => check_string($_POST['roundMoney']),
        'update_gettime'    => gettime()
    ], " `id` = '" . $supplier['id'] . "' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit API Supplier (" . $supplier['domain'] . " ID " . $supplier['id'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit API Supplier (" . $supplier['domain'] . " ID " . $supplier['id'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công!")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại!")){window.history.back().location.reload();}</script>');
    }
}
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button"
                    class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('product-api'); ?>"><i class="fa-solid fa-arrow-left"></i></a> Chỉnh sửa API
                nhà cung cấp <?= $supplier['domain']; ?>
            </h1>
        </div>

        <?php
        // Lặp qua danh sách nhà cung cấp
        foreach ($cron_suppliers as $type => $key) {
            if ($supplier['type'] == $type && time() - $CMSNT->site("time_cron_suppliers_$key") >= 120) {
        ?>
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Vui lòng thực hiện <b><a target="_blank" class="text-primary"
                            href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                    liên kết:
                    <a class="text-primary" href="<?= base_url('cron/suppliers/' . $key . '.php?key=' . $CMSNT->site('key_cron_job')); ?>" target="_blank">
                        <?= base_url('cron/suppliers/' . $key . '.php?key=' . $CMSNT->site('key_cron_job')); ?>
                    </a> 1 phút 1 lần để hệ thống tự động cập nhật dữ liệu từ API.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
        <?php
            }
        }
        ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                CHỈNH SỬA KẾT NỐI API
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5 gy-3">
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-plug-circle-plus text-primary"></i> Thông tin kết nối API</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="api-select">
                                                    <i class="fa-solid fa-server text-info"></i> <?= __('Loại API:'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="hidden" name="type" value="<?= $supplier['type']; ?>">
                                                <select class="form-select form-select-lg shadow-sm" id="api-select" disabled required>
                                                    <option value="">-- Chọn loại API --</option>
                                                    <option <?= $supplier['type'] == 'SHOPCLONE7' ? 'selected' : ''; ?> value="SHOPCLONE7" class="bg-success-subtle">
                                                        SHOPCLONE7 CMSNT (Miễn phí)</option>
                                                    <option <?= $supplier['type'] == 'SHOPCLONE6' ? 'selected' : ''; ?> value="SHOPCLONE6" class="bg-success-subtle">
                                                        SHOPCLONE5 & SHOPCLONE6 CMSNT (Miễn phí)</option>
                                                    <option <?= $supplier['type'] == 'SHOPKEY' ? 'selected' : ''; ?> value="SHOPKEY" class="bg-success-subtle">
                                                        SHOPKEY CMSNT (Miễn phí)</option>
                                                    <option <?= $supplier['type'] == 'API_1' ? 'selected' : ''; ?> value="API_1">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_4' ? 'selected' : ''; ?> value="API_4">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_6' ? 'selected' : ''; ?> value="API_6">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_9' ? 'selected' : ''; ?> value="API_9">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_14' ? 'selected' : ''; ?> value="API_14">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_17' ? 'selected' : ''; ?> value="API_17">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_18' ? 'selected' : ''; ?> value="API_18">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_19' ? 'selected' : ''; ?> value="API_19">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_20' ? 'selected' : ''; ?> value="API_20" class="bg-warning-subtle">API (Không còn hỗ trợ)</option>
                                                    <option <?= $supplier['type'] == 'API_21' ? 'selected' : ''; ?> value="API_21">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_22' ? 'selected' : ''; ?> value="API_22">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_23' ? 'selected' : ''; ?> value="API_23">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_24' ? 'selected' : ''; ?> value="API_24">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_25' ? 'selected' : ''; ?> value="API_25">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_26' ? 'selected' : ''; ?> value="API_26">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_27' ? 'selected' : ''; ?> value="API_27">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_28' ? 'selected' : ''; ?> value="API_28">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_29' ? 'selected' : ''; ?> value="API_29">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_30' ? 'selected' : ''; ?> value="API_30">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_31' ? 'selected' : ''; ?> value="API_31">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_32' ? 'selected' : ''; ?> value="API_32">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_33' ? 'selected' : ''; ?> value="API_33">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_34' ? 'selected' : ''; ?> value="API_34">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_35' ? 'selected' : ''; ?> value="API_35">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_36' ? 'selected' : ''; ?> value="API_36">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_37' ? 'selected' : ''; ?> value="API_37">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_38' ? 'selected' : ''; ?> value="API_38">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_39' ? 'selected' : ''; ?> value="API_39">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_40' ? 'selected' : ''; ?> value="API_40">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_41' ? 'selected' : ''; ?> value="API_41">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_42' ? 'selected' : ''; ?> value="API_42">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_43' ? 'selected' : ''; ?> value="API_43">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_44' ? 'selected' : ''; ?> value="API_44">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_45' ? 'selected' : ''; ?> value="API_45">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_46' ? 'selected' : ''; ?> value="API_46">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_47' ? 'selected' : ''; ?> value="API_47">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_48' ? 'selected' : ''; ?> value="API_48">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_49' ? 'selected' : ''; ?> value="API_49">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_50' ? 'selected' : ''; ?> value="API_50">API (Ngoài hệ sinh thái)</option>
                                                    <option <?= $supplier['type'] == 'API_51' ? 'selected' : ''; ?> value="API_51">API (Ngoài hệ sinh thái)</option>
                                                </select>
                                                <div class="form-text"><i class="fas fa-info-circle"></i> API CMSNT được hỗ trợ miễn phí, API ngoại hệ sinh thái tính phí phát sinh.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="domain">
                                                    <i class="fa-solid fa-globe text-primary"></i> <?= __('Domain'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text bg-light"><i class="fas fa-link"></i></span>
                                                    <input type="text" class="form-control shadow-sm" id="domain" value="<?= $supplier['domain']; ?>"
                                                        placeholder="VD: https://domain.com/" name="domain" autocomplete="off"
                                                        data-lpignore="true" required>
                                                </div>
                                                <div class="form-text"><i class="fas fa-info-circle"></i> Nhập đầy đủ URL kèm https:// hoặc http://</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="balance">
                                                    <i class="fa-solid fa-wallet text-success"></i> <?= __('Số dư:'); ?>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-coins"></i></span>
                                                    <textarea class="form-control shadow-sm" id="balance" readonly><?= $supplier['price']; ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Thông tin đăng nhập -->
                                        <div class="credentials-container mt-3">
                                            <div class="row">
                                                <div class="col-md-6 mb-3" id="username" style="display: none;">
                                                    <label class="form-label fw-bold" for="username-input">
                                                        <i class="fa-solid fa-user text-warning"></i> <?= __('Username:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="username-input" name="username"
                                                            value="<?= $supplier['username']; ?>" autocomplete="new-password"
                                                            placeholder="<?= __('Nhập tên đăng nhập website API'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="password" style="display: none;">
                                                    <label class="form-label fw-bold" for="password-input">
                                                        <i class="fa-solid fa-key text-warning"></i> <?= __('Password:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                                                        <input type="password" class="form-control shadow-sm" id="password-input" name="password"
                                                            value="<?= $supplier['password']; ?>" autocomplete="new-password"
                                                            placeholder="<?= __('Nhập mật khẩu đăng nhập website API'); ?>">
                                                        <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="api_key" style="display: none;">
                                                    <label class="form-label fw-bold" for="api-key-input">
                                                        <i class="fa-solid fa-key text-danger"></i> <?= __('API Key:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="api-key-input" name="api_key"
                                                            value="<?= $supplier['api_key']; ?>" autocomplete="new-password"
                                                            placeholder="<?= __('Nhập Api Key trong website API'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="token" style="display: none;">
                                                    <label class="form-label fw-bold" for="token-input">
                                                        <i class="fa-solid fa-shield-halved text-success"></i> <?= __('Token:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-shield-alt"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="token-input" name="token"
                                                            value="<?= $supplier['token']; ?>" autocomplete="new-password"
                                                            placeholder="<?= __('Nhập Token trong website API'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="coupon" style="display: none;">
                                                    <label class="form-label fw-bold" for="coupon-input">
                                                        <i class="fa-solid fa-tag text-info"></i> <?= __('Coupon:'); ?>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-percentage"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="coupon-input" name="coupon"
                                                            value="<?= $supplier['coupon']; ?>" autocomplete="new-password"
                                                            placeholder="<?= __('Nhập mã giảm giá nếu có'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-12 mb-3" id="list_api_id_div" style="display: <?= $supplier['type'] == 'API_39' ? 'block' : 'none'; ?>;">
                                                    <label class="form-label fw-bold" for="list-api-id-input">
                                                        <i class="fa-solid fa-list-ol text-primary"></i> <?= __('Danh sách Product ID cần đồng bộ:'); ?>
                                                    </label>
                                                    <textarea class="form-control shadow-sm" id="list-api-id-input" name="list_api_id" rows="5" placeholder="<?= __('Nhập mỗi sản phẩm trên 1 dòng theo format: productId|variantId' . PHP_EOL . 'VD:' . PHP_EOL . 'prod_123|var_456' . PHP_EOL . 'prod_xyz'); ?>"><?= $supplier['list_api_id']; ?></textarea>
                                                    <div class="form-text"><i class="fas fa-info-circle text-info"></i> <?= __('Mỗi dòng 1 ID dạng productId|variantId. Cron sẽ tự động lấy thông tin cho các ID này.'); ?></div>
                                                </div>
                                            </div>
                                            <!-- SHOPKEY Warning -->
                                            <div class="alert alert-info mt-3" id="shopkey_warning" style="display: <?= $supplier['type'] == 'SHOPKEY' ? 'block' : 'none'; ?>;">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                <strong>Lưu ý:</strong> SHOPKEY API chỉ đồng bộ <b>sản phẩm giao ngay</b>, không đồng bộ sản phẩm Order (đặt hàng).
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cài đặt đồng bộ -->
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-sliders text-success"></i> Cài đặt đồng bộ dữ liệu</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3" id="sync_category" style="display: none;">
                                                <label class="form-label fw-bold" for="sync-category-select">
                                                    <i class="fa-solid fa-folder-tree text-primary"></i> Đồng bộ chuyên mục từ API
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="sync-category-select" name="sync_category" required>
                                                    <option <?= $supplier['sync_category'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Không đồng bộ</option>
                                                    <option <?= $supplier['sync_category'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Đồng bộ tự động</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Hệ thống sẽ tự động đồng bộ và thêm chuyên mục từ API.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="sync_category_image" style="display: none;">
                                                <label class="form-label fw-bold" for="sync-category-image-select">
                                                    <i class="fa-solid fa-image text-info"></i> Đồng bộ ảnh chuyên mục
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="sync-category-image-select" name="sync_category_image" required>
                                                    <option <?= $supplier['sync_category_image'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Đồng bộ (Tải từ API)</option>
                                                    <option <?= $supplier['sync_category_image'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Không đồng bộ (Dùng ảnh hệ thống)</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Lấy ảnh từ API nếu bật. Nếu tắt, sẽ lấy ảnh mặc định của Web.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="child_sync" style="display: none;">
                                                <label class="form-label fw-bold" for="child-select">
                                                    <i class="fa-solid fa-sitemap text-warning"></i> Đồng bộ cấu trúc như web con
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="child-select" name="child" required>
                                                    <option <?= (!isset($supplier['child']) || $supplier['child'] == 0) ? 'selected' : ''; ?> value="0">OFF - Cấu trúc thông thường (Con → Sản phẩm)</option>
                                                    <option <?= (isset($supplier['child']) && $supplier['child'] == 1) ? 'selected' : ''; ?> value="1">ON - Cấu trúc web con (Cha → Con → Sản phẩm)</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Chọn ON nếu API có cấu trúc Chuyên mục cha → Chuyên mục con → Sản phẩm.
                                                </div>
                                                <div class="alert alert-warning mt-2" id="child-warning" style="<?= (isset($supplier['child']) && $supplier['child'] == 1) ? 'display: block;' : 'display: none;'; ?>">
                                                    <i class="fas fa-exclamation-triangle"></i> <strong>Lưu ý:</strong> Khi bật chế độ này, hệ thống sẽ tự động đồng bộ theo cấu trúc 3 cấp (Cha → Con → Sản phẩm) và tự động cập nhật chuyên mục, mô tả ngắn, mô tả, ưu tiên khi API thay đổi.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="auto_show" style="display: none;">
                                                <label class="form-label fw-bold" for="auto-show-select">
                                                    <i class="fa-solid fa-toggle-on text-success"></i> Tự động bật trạng thái sản phẩm
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="auto-show-select" name="isAutoShow" required>
                                                    <option <?= (!isset($supplier['isAutoShow']) || $supplier['isAutoShow'] == 0) ? 'selected' : ''; ?> value="0">OFF - Giữ trạng thái ẩn khi đồng bộ</option>
                                                    <option <?= (isset($supplier['isAutoShow']) && $supplier['isAutoShow'] == 1) ? 'selected' : ''; ?> value="1">ON - Tự động hiển thị sản phẩm</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Khi ON, sản phẩm sẽ tự động được bật trạng thái hiển thị sau khi đồng bộ.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="update-price-select">
                                                    <i class="fa-solid fa-sack-dollar text-success"></i> Cập nhật giá bán tự động
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="update-price-select" name="update_price" required>
                                                    <option <?= $supplier['update_price'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Cập nhật tự động</option>
                                                    <option <?= $supplier['update_price'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Giữ nguyên giá</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Khi giá sản phẩm thay đổi ở API, hệ thống sẽ tự động cập nhật.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="round-money-select">
                                                    <i class="fa-solid fa-circle-dollar-to-slot text-primary"></i> Làm tròn giá bán
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="round-money-select" name="roundMoney" required>
                                                    <option <?= $supplier['roundMoney'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Giữ nguyên số</option>
                                                    <option <?= $supplier['roundMoney'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Làm tròn số</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> VD: <?= format_currency(10550); ?> sẽ làm tròn thành <?= format_currency(10600); ?> hoặc <?= format_currency(10530); ?> sẽ làm tròn thành <?= format_currency(10500); ?>.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="discount-input">
                                                    <i class="fa-solid fa-percent text-danger"></i> Tăng giá so với giá gốc
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control shadow-sm" id="discount-input" value="<?= $supplier['discount']; ?>" min="0"
                                                        placeholder="Nhập % tăng giá" name="discount" required>
                                                    <span class="input-group-text bg-light">%</span>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Nhập 10 để tăng giá bán thêm 10% so với giá gốc, nhập 0 để giữ nguyên.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="rate_field" style="display: none;">
                                                <label class="form-label fw-bold" for="rate-input">
                                                    <i class="fa-solid fa-percent text-danger"></i> <?= __('Tỷ giá tiền tệ quốc tế (nếu có)'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control shadow-sm"
                                                        id="rate-input" value="<?= !empty($supplier['rate']) ? $supplier['rate'] : '1'; ?>" min="0"
                                                        placeholder="Nhập tỷ giá" name="rate" required>
                                                    <span class="input-group-text bg-light"><?= currencyDefault(); ?></span>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Nếu giá dịch vụ của API giống giá tiền tệ của bạn, hãy nhập 1. Nếu website bạn sử dụng USD nhưng giá dịch vụ API là VND, hãy nhập tỷ giá của 1 VND ~0,000038. Nếu giá của bạn là VND nhưng giá của API là USD, hãy nhập tỷ giá của 1 USD ~26.000'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="update-name-select">
                                                    <i class="fa-solid fa-font text-info"></i> Cập nhật tên & mô tả tự động
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="update-name-select" name="update_name" required>
                                                    <option <?= $supplier['update_name'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Cập nhật tự động</option>
                                                    <option <?= $supplier['update_name'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Giữ nguyên nội dung</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Tự động cập nhật tên và mô tả sản phẩm từ API.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="check-string-api-select">
                                                    <i class="fa-solid fa-code text-warning"></i> Lọc HTML trong nội dung API
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="check-string-api-select" name="check_string_api" required>
                                                    <option <?= $supplier['check_string_api'] == 'ON' ? 'selected' : ''; ?> value="ON">ON - Kích hoạt bảo vệ</option>
                                                    <option <?= $supplier['check_string_api'] == 'OFF' ? 'selected' : ''; ?> value="OFF">OFF - Tắt bảo vệ</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-shield-alt text-danger"></i> Bảo vệ website bằng cách lọc mã HTML độc hại từ API.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-sliders text-success"></i> Cài đặt khác</h5>
                                        <div class="row">

                                            <div class="col-md-6 mb-3" id="proxy" style="display: none;">
                                                <label class="form-label fw-bold" for="proxy-input">
                                                    <i class="fa-solid fa-globe text-danger"></i> Proxy v4 hoặc v6 (nếu có):
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control shadow-sm" id="proxy-input" value="<?= $supplier['proxy']; ?>"
                                                        placeholder="ip:port:username:password" name="proxy" autocomplete="off">
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Chỉ dùng Proxy nếu quý khách đã nhờ phía API whitelist IP nhưng vẫn không hiện số dư sau khi kết nối.
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" name="save" class="btn btn-primary btn-lg shadow-lg btn-wave">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> <?= __('Lưu thay đổi'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card position-sticky" style="top: 85px;">
                        <div class="card-header bg-primary">
                            <div class="card-title">
                                <i class="fa-solid fa-circle-info me-1"></i> LƯU Ý
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-primary" role="alert">
                                <i class="fa-solid fa-lightbulb me-1"></i> <strong>Mục đích:</strong> Chức năng này cho phép quý khách bán lại sản phẩm của website khác trên chính website của quý khách.
                            </div>

                            <div class="alert alert-warning mb-3" role="alert">
                                <h6 class="alert-heading"><i class="fa-solid fa-triangle-exclamation me-1"></i> Lưu ý quan trọng!</h6>
                                <p>Trường hợp quý khách cấu hình đúng nhưng không hiện số dư API có thể do máy chủ không thể kết nối với API đích.</p>
                                <a href="https://help.cmsnt.co/huong-dan/ket-noi-api-nhap-dung-thong-tin-nhung-khong-ra-so-du-thi-lam-sao/" class="btn btn-sm btn-warning mt-2" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i> Xem hướng dẫn xử lý
                                </a>
                            </div>

                            <div class="d-flex align-items-center p-3 rounded bg-light mb-3">
                                <div class="me-3 text-primary fs-3"><i class="fa-solid fa-handshake"></i></div>
                                <div>
                                    <h6 class="mb-1">API cùng hệ sinh thái CMSNT</h6>
                                    <p class="mb-0 text-success fw-bold">Miễn phí</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center p-3 rounded bg-light mb-3">
                                <div class="me-3 text-warning fs-3"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
                                <div>
                                    <h6 class="mb-1">API ngoài hệ sinh thái</h6>
                                    <p class="mb-0">Phí tích hợp: <span class="text-danger fw-bold">liên hệ báo giá chi tiết</span></p>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="https://www.cmsnt.co/p/contact.html" class="btn btn-outline-primary" target="_blank">
                                    <i class="fa-solid fa-headset me-1"></i> Liên hệ hỗ trợ kết nối API
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
</div>





<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    var lightboxVideo = GLightbox({
        selector: '.glightbox'
    });

    CKEDITOR.replace("description");
    CKEDITOR.replace("note");

    function removeImageProduct(id, image) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa ảnh",
            message: "Bạn có chắc chắn muốn xóa ảnh " + id + " không ?",
            confirmText: "Đồng Ý",
            cancelText: "Hủy"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: id,
                        image: image,
                        action: 'removeImageProduct'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        })
    }
</script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ngăn chặn autofill bằng cách thêm một trường ẩn và đảm bảo không tự động điền
        const form = document.querySelector('form');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'text';
        hiddenInput.style.display = 'none';
        hiddenInput.name = 'prevent_autofill';
        hiddenInput.setAttribute('autocomplete', 'off');
        form.prepend(hiddenInput);

        // Thêm thuộc tính autocomplete="new-password" vào tất cả các trường input
        const allInputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        allInputs.forEach(input => {
            input.setAttribute('autocomplete', 'new-password');
        });

        // Đoạn code xử lý toggle fields
        const typeSelect = document.querySelector("input[name='type']");
        const usernameField = document.getElementById("username");
        const passwordField = document.getElementById("password");
        const apiKeyField = document.getElementById("api_key");
        const tokenField = document.getElementById("token");
        const couponField = document.getElementById("coupon");
        const sync_category = document.getElementById("sync_category");
        const sync_category_image = document.getElementById("sync_category_image");
        const child_sync = document.getElementById("child_sync");
        const auto_show = document.getElementById("auto_show");
        const proxyField = document.getElementById("proxy");
        const rateField = document.getElementById("rate_field");
        const list_api_id_div = document.getElementById("list_api_id_div");

        // Thêm xử lý hiển thị/ẩn mật khẩu
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password-input');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        function toggleFields() {
            const selectedType = typeSelect.value;
            const shopkeyWarning = document.getElementById("shopkey_warning");
            usernameField.style.display = "none";
            passwordField.style.display = "none";
            apiKeyField.style.display = "none";
            tokenField.style.display = "none";
            couponField.style.display = "none";
            sync_category.style.display = "none";
            if (sync_category_image) sync_category_image.style.display = "none";
            child_sync.style.display = "none";
            auto_show.style.display = "none";
            proxyField.style.display = "none";
            rateField.style.display = "none";
            if (list_api_id_div) list_api_id_div.style.display = "none";
            if (shopkeyWarning) shopkeyWarning.style.display = "none";

            // Hiển thị ô Username và Password
            if (selectedType === "SHOPCLONE6") {
                sync_category.style.display = "block";
                usernameField.style.display = "block";
                passwordField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
            }
            // Hiển thị ô API Key và Secret Key cho SHOPKEY
            else if (selectedType === "SHOPKEY") {
                sync_category.style.display = "block";
                apiKeyField.style.display = "block";
                tokenField.style.display = "block";
                couponField.style.display = "block";
                proxyField.style.display = "block";
                child_sync.style.display = "block";
                auto_show.style.display = "block";
                rateField.style.display = "block";
                if (shopkeyWarning) shopkeyWarning.style.display = "block";
                // Đổi label cho SHOPKEY
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-key text-danger"></i> <?= __("API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập API Key từ SHOPKEY"); ?>';
                document.querySelector('#token label').innerHTML = '<i class="fa-solid fa-shield-halved text-success"></i> <?= __("Secret Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Nhập Secret Key từ SHOPKEY"); ?>';
                // Kiểm tra sync_category để quyết định disabled child_sync
                const syncCategorySelect = document.getElementById('sync-category-select');
                const childSelect = document.getElementById('child-select');
                if (syncCategorySelect && childSelect) {
                    if (syncCategorySelect.value === "OFF") {
                        childSelect.disabled = true;
                        childSelect.value = "0";
                    } else {
                        childSelect.disabled = false;
                    }
                }
            }
            // Hiển thị ô API Key
            else if (selectedType === "SHOPCLONE7" || selectedType === "API_31") {
                sync_category.style.display = "block";
                apiKeyField.style.display = "block";
                couponField.style.display = "block";
                proxyField.style.display = "block";
                if (selectedType === "SHOPCLONE7") {
                    child_sync.style.display = "block";
                    auto_show.style.display = "block";
                    rateField.style.display = "block";
                    // Kiểm tra sync_category để quyết định disabled child_sync
                    const syncCategorySelect = document.getElementById('sync-category-select');
                    const childSelect = document.getElementById('child-select');
                    if (syncCategorySelect && childSelect) {
                        if (syncCategorySelect.value === "OFF") {
                            childSelect.disabled = true;
                            childSelect.value = "0";
                        } else {
                            childSelect.disabled = false;
                        }
                    }
                }
            }
            // Hiển thị ô Username và Password
            else if (selectedType === "API_4" || selectedType === "API_17" || selectedType === "API_28" || selectedType === "API_33") {
                usernameField.style.display = "block";
                passwordField.style.display = "block";
            }
            // Hiển thị ô API Key
            else if (selectedType === "API_1" || selectedType === "API_6" || selectedType === "API_18" ||
                selectedType === "API_19" || selectedType === "API_9" || selectedType === "API_23" || selectedType === "API_24" || selectedType === "API_25" || selectedType === "API_27" || selectedType === "API_29" || selectedType === "API_30" || selectedType === "API_32") {
                apiKeyField.style.display = "block";
            }
            // Hiển thị ô API Key cho API_34
            else if (selectedType === "API_34") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô Email (api_key) và Token cho API_35
            else if (selectedType === "API_35") {
                apiKeyField.style.display = "block";
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label cho API_35
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-envelope text-danger"></i> <?= __("Email:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập Email đăng ký trên website API"); ?>';
            }
            // Hiển thị ô Token cho API_36, API_37 và API_39
            else if (selectedType === "API_36" || selectedType === "API_37" || selectedType === "API_39") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                if (selectedType === "API_39" && list_api_id_div) {
                    list_api_id_div.style.display = "block";
                }
            }
            // Hiển thị ô API Key cho API_40 (Telegram Shop API)
            else if (selectedType === "API_40") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô API Key cho API_41 (xscr.us)
            else if (selectedType === "API_41") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô API Key cho API_42 (mail555.com)
            else if (selectedType === "API_42") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô Token cho API_43 (BuyFB)
            else if (selectedType === "API_43") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô Token cho API_44 (Buyer API, xác thực bằng X-Buyer-Key)
            else if (selectedType === "API_44") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
            }
            // Hiển thị ô Token cho API_45 (Telegram Buyer API, key dạng tgb_xxx)
            // Domain không cần nhập — được hardcode trong hàm
            else if (selectedType === "API_45") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label token thành Telegram Buyer Key để admin hiểu rõ
                document.querySelector('#token label').innerHTML = '<i class="fa-brands fa-telegram text-primary"></i> <?= __("Telegram Buyer Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Dạng tgb_xxx... (lấy từ bot Telegram của shop)"); ?>';
            }
            // Hiển thị ô Token cho API_46 (Shop Bot API, key dạng sk_xxx)
            else if (selectedType === "API_46") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label token thành API Key
                document.querySelector('#token label').innerHTML = '<i class="fa-solid fa-key text-warning"></i> <?= __("API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Dạng sk_xxx... (lấy từ website API)"); ?>';
            }
            // Hiển thị ô API Key (app_id) và Token (app_key) cho API_38 (API Shared)
            else if (selectedType === "API_38") {
                apiKeyField.style.display = "block";
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                if (sync_category) sync_category.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label cho API_38
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-id-card text-danger"></i> <?= __("App ID:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập App ID từ nhà cung cấp API"); ?>';
                document.querySelector('#token label').innerHTML = '<i class="fa-solid fa-key text-success"></i> <?= __("App Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Nhập App Key từ nhà cung cấp API"); ?>';
            }
            // Hiển thị ô Token cho API_47
            else if (selectedType === "API_47") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label cho API_47
                document.querySelector('#token label').innerHTML = '<i class="fa-solid fa-key text-success"></i> <?= __("Token (API Key):"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Nhập API Key từ nhà cung cấp API 47"); ?>';
            }
            // Hiển thị ô API Key cho API_48 (APIv7 Compatibility - tương thích SHOPCLONE7 nhưng dùng /api/v7/)
            else if (selectedType === "API_48") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                sync_category.style.display = "block";
                // Đổi label cho API_48
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-key text-danger"></i> <?= __("API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập API Key từ nhà cung cấp APIv7 (VD: btu_xxxxx)"); ?>';
            }
            // Hiển thị ô API Key cho API_49 (mua proxy tự động)
            else if (selectedType === "API_49") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label cho API_49
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-key text-danger"></i> <?= __("API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập API Key từ nhà cung cấp"); ?>';
            }
            // Hiển thị ô Token cho API_50 (Telegram Buyer API)
            else if (selectedType === "API_50") {
                tokenField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label token thành Buyer API Key để rõ nghĩa hơn
                document.querySelector('#token label').innerHTML = '<i class="fa-solid fa-key text-warning"></i> <?= __("Buyer API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('token-input').placeholder = '<?= __("Nhập API Key / Token dạng tgb_xxx từ nhà cung cấp"); ?>';
            }
            // Hiển thị ô API Key cho API_51 (Nas Nabi API)
            else if (selectedType === "API_51") {
                apiKeyField.style.display = "block";
                proxyField.style.display = "block";
                rateField.style.display = "block";
                auto_show.style.display = "block";
                // Đổi label cho API_51
                document.querySelector('#api_key label').innerHTML = '<i class="fa-solid fa-key text-danger"></i> <?= __("API Key:"); ?> <span class="text-danger">*</span>';
                document.getElementById('api-key-input').placeholder = '<?= __("Nhập API Key từ Nas Nabi (VD: psk_xxxxx)"); ?>';
            }
            // Hiển thị ô Token
            else if (selectedType === "API_14" || selectedType === "API_21" || selectedType === "API_22") {
                tokenField.style.display = "block";
            }
            // Hiển thị ô API Key và Token
            else if (selectedType === "API_20" || selectedType === "API_26") {
                apiKeyField.style.display = "block";
                tokenField.style.display = "block";
            }

            // Hiển thị Auto Show cho tất cả các loại API
            if (selectedType !== "") {
                auto_show.style.display = "block";
            }

            // Xử lý tự động hiện/ẩn hộp thoại sync_category_image
            const selectSyncCategory = document.getElementById('sync-category-select');
            if (sync_category.style.display === "block" && selectSyncCategory && selectSyncCategory.value === "ON" && sync_category_image) {
                sync_category_image.style.display = "block";
            }
        }
        toggleFields();
        typeSelect.addEventListener("change", toggleFields);

        // Xử lý disabled/enabled child_sync khi sync_category thay đổi
        const syncCategorySelect = document.getElementById('sync-category-select');
        if (syncCategorySelect) {
            syncCategorySelect.addEventListener('change', function() {
                const selectedType = typeSelect.value;
                const childSelect = document.getElementById('child-select');
                const childWarning = document.getElementById('child-warning');

                if (sync_category_image) {
                    if (this.value === "ON") {
                        sync_category_image.style.display = "block";
                    } else {
                        sync_category_image.style.display = "none";
                    }
                }

                if ((selectedType === "SHOPCLONE7" || selectedType === "SHOPKEY") && childSelect) {
                    if (this.value === "ON") {
                        childSelect.disabled = false;
                        childSelect.parentElement.classList.remove('opacity-50');
                    } else {
                        childSelect.disabled = true;
                        childSelect.value = "0";
                        childSelect.parentElement.classList.add('opacity-50');
                        if (childWarning) {
                            childWarning.style.display = "none";
                        }
                    }
                }
            });
        }

        // Xử lý hiển thị cảnh báo khi chọn child = ON
        const childSelect = document.getElementById('child-select');
        const childWarning = document.getElementById('child-warning');

        if (childSelect) {
            childSelect.addEventListener('change', function() {
                if (this.value == '1') {
                    childWarning.style.display = 'block';
                    childWarning.classList.add('animate__animated', 'animate__fadeIn');
                    setTimeout(() => {
                        childWarning.classList.remove('animate__animated', 'animate__fadeIn');
                    }, 1000);
                } else {
                    childWarning.style.display = 'none';
                }
            });
        }

        // Cải thiện UX với hiệu ứng làm nổi bật section
        const apiSelect = document.getElementById('api-select');
        apiSelect.addEventListener('change', function() {
            if (this.value) {
                document.querySelector('.credentials-container').classList.add('animate__animated', 'animate__fadeIn');
                setTimeout(() => {
                    document.querySelector('.credentials-container').classList.remove('animate__animated', 'animate__fadeIn');
                }, 1000);
            }
        });
    });
</script>

<style>
    .api-section {
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
    }

    .api-section:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Lấy select box
        const selectBox = document.getElementById('api-select');

        // Lấy tất cả các option trừ 4 option đầu tiên (placeholder, SHOPCLONE7, SHOPCLONE6, SHOPKEY)
        const options = Array.from(selectBox.options).slice(4);

        // Xáo trộn mảng options
        for (let i = options.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [options[i], options[j]] = [options[j], options[i]];
        }

        // Xóa tất cả các option hiện tại (trừ 4 option đầu tiên)
        while (selectBox.options.length > 4) {
            selectBox.remove(4);
        }

        // Thêm lại các option đã xáo trộn
        options.forEach(option => {
            selectBox.add(option);
        });
    });
</script>