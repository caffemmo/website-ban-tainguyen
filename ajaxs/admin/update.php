<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . '/../../models/is_admin.php');


if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);
}
if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}

// Xử lý duyệt sản phẩm CTV
if ($_POST['action'] == 'approveCtvProduct') {
    if (checkPermission($getUser['admin'], 'edit_product_ctv') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $product_id = validate_int($_POST['product_id'], 1);
    if ($product_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }
    if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? AND `pending` = 1 ", [$product_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại hoặc đã được duyệt')]));
    }

    // Kiểm tra xem sản phẩm có thuộc về CTV không
    $ctv = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? AND `ctv` = 1 ", [$product['user_id']]);
    if (!$ctv) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không thuộc về CTV')]));
    }

    // Duyệt sản phẩm: set pending = 0, status = 1
    $isUpdate = $CMSNT->update("products", [
        'pending' => 0,
        'status' => 1,
        'update_gettime' => gettime()
    ], " `id` = ? ", [$product_id]);

    if ($isUpdate) {
        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin duyệt sản phẩm CTV') . ': ' . $product['name'] . ' (ID: ' . $product_id . ')'
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Duyệt sản phẩm thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi duyệt sản phẩm')]));
    }
}

// Xử lý duyệt nhiều sản phẩm CTV cùng lúc
if ($_POST['action'] == 'bulkApproveCtvProducts') {
    if (checkPermission($getUser['admin'], 'edit_product_ctv') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['product_ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một sản phẩm')]));
    }

    $product_ids_json = $_POST['product_ids'];
    $product_ids = json_decode($product_ids_json, true);

    if (!is_array($product_ids) || empty($product_ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $product_ids = array_map('intval', $product_ids);
    $approved_count = 0;
    $error_count = 0;
    $product_names = [];

    foreach ($product_ids as $product_id) {
        if ($product_id <= 0) continue;

        // Kiểm tra sản phẩm có tồn tại và đang chờ duyệt không
        if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? AND `pending` = 1 ", [$product_id])) {
            $error_count++;
            continue;
        }

        // Kiểm tra xem sản phẩm có thuộc về CTV không
        $ctv = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? AND `ctv` = 1 ", [$product['user_id']]);
        if (!$ctv) {
            $error_count++;
            continue;
        }

        // Duyệt sản phẩm: set pending = 0, status = 1
        $isUpdate = $CMSNT->update("products", [
            'pending' => 0,
            'status' => 1,
            'update_gettime' => gettime()
        ], " `id` = ? ", [$product_id]);

        if ($isUpdate) {
            $product_names[] = $product['name'];
            $approved_count++;
        } else {
            $error_count++;
        }
    }

    if ($approved_count > 0) {
        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin duyệt hàng loạt sản phẩm CTV') . ': ' . $approved_count . ' sản phẩm (' .
                implode(', ', array_slice($product_names, 0, 5)) .
                (count($product_names) > 5 ? '...' : '') . ')'
        ]);

        $success_msg = __('Đã duyệt thành công') . ' ' . $approved_count . ' ' . __('sản phẩm');
        if ($error_count > 0) {
            $success_msg .= ' (' . $error_count . ' ' . __('sản phẩm không thể duyệt') . ')';
        }

        die(json_encode(['status' => 'success', 'msg' => $success_msg]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được duyệt')]));
    }
}



if ($_POST['action'] == 'cap_nhat_san_pham_nhanh') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }
    if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? ", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Sản phẩm không tồn tại trong hệ thống']));
    }
    // Cập nhật chuyên mục nhanh
    if (!empty($_POST['category_id'])) {
        $category_id = validate_int($_POST['category_id'], 0);
        if ($category_id !== false) {
            $isUpdate = $CMSNT->update("products", [
                'category_id'    => $category_id
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật trạng thái nhanh
    if (isset($_POST['status']) && $_POST['status'] != '' && $_POST['status'] != 'keep') {
        $status = ($_POST['status'] == 'ON') ? 1 : 0;
        $isUpdate = $CMSNT->update("products", [
            'status'    => $status
        ], " `id` = ? ", [$id]);
    }
    // Cập nhật cho phép kết nối API nhanh
    if (isset($_POST['allow_api']) && $_POST['allow_api'] !== '') {
        $allow_api = validate_int($_POST['allow_api'], 0, 1);
        if ($allow_api !== false) {
            $isUpdate = $CMSNT->update("products", [
                'allow_api'    => $allow_api
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật discount nhanh
    if (!empty($_POST['discount'])) {
        $discount = validate_float($_POST['discount'], 0);
        if ($discount !== false) {
            $isUpdate = $CMSNT->update("products", [
                'discount'    => $discount
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật mô tả ngắn nhanh (TEXT/MEDIUMTEXT trong DB; trước đây giới hạn 500 ký tự khiến nội dung dài không lưu được)
    if (!empty($_POST['short_desc'])) {
        $short_desc = validate_string($_POST['short_desc'], 10000);
        if ($short_desc !== false) {
            $isUpdate = $CMSNT->update("products", [
                'short_desc'    => $short_desc
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật giá bán lẻ
    if (!empty($_POST['price'])) {
        $price = validate_float($_POST['price'], 0);
        if ($price !== false) {
            $isUpdate = $CMSNT->update("products", [
                'price'    => $price
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật giá bán lẻ theo phần trăm
    if (!empty($_POST['pricePercent'])) {
        if (!$row = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? ", [$id])) {
            die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
        }
        $pricePercent = validate_float($_POST['pricePercent'], 0);
        $priceAction = isset($_POST['priceAction']) && in_array($_POST['priceAction'], ['increase', 'decrease']) ? $_POST['priceAction'] : 'increase';
        $cost = floatval($row['cost']);

        if ($pricePercent !== false && $cost > 0) {
            if ($priceAction == 'increase') {
                $newPrice = $cost + ($cost * $pricePercent / 100);
            } else {
                $newPrice = $cost - ($cost * $pricePercent / 100);
                if ($newPrice < 0) {
                    $newPrice = 0;
                }
            }

            $isUpdate = $CMSNT->update("products", [
                'price' => $newPrice
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật số lượng đã bán - đặt số lượng cụ thể
    if (isset($_POST['sold_set']) && $_POST['sold_set'] !== '') {
        $sold_set = validate_int($_POST['sold_set'], 0);
        if ($sold_set !== false) {
            $isUpdate = $CMSNT->update("products", [
                'sold' => $sold_set
            ], " `id` = ? ", [$id]);
        }
    }
    // Cập nhật số lượng đã bán - điều chỉnh (cộng/trừ)
    if (isset($_POST['soldAdjust']) && $_POST['soldAdjust'] !== '') {
        if (!$row = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? ", [$id])) {
            die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
        }
        $soldAdjust = validate_int($_POST['soldAdjust'], 0);
        $soldAction = isset($_POST['soldAction']) && in_array($_POST['soldAction'], ['add', 'subtract']) ? $_POST['soldAction'] : 'add';
        if ($soldAdjust !== false) {
            $currentSold = intval($row['sold']);

            if ($soldAction == 'add') {
                $newSold = $currentSold + $soldAdjust;
            } else {
                $newSold = $currentSold - $soldAdjust;
                if ($newSold < 0) {
                    $newSold = 0;
                }
            }

            $isUpdate = $CMSNT->update("products", [
                'sold' => $newSold
            ], " `id` = ? ", [$id]);
        }
    }
    if (isset($isUpdate)) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Cập nhật nhanh sản phẩm (ID $id)"
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được thay đổi')]));
}

if ($_POST['action'] == 'cap_nhat_chuyen_muc_nhanh') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID chuyên mục không hợp lệ')]));
    }
    if (!$category = $CMSNT->get_row_safe(" SELECT * FROM `categories` WHERE `id` = ? ", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Chuyên mục không tồn tại trong hệ thống']));
    }

    $updateData = [];

    // Cập nhật chuyên mục cha nhanh
    if (!empty($_POST['category_id']) && $_POST['category_id'] != 'keep') {
        $parent_id = validate_int($_POST['category_id'], 0);
        if ($parent_id !== false) {
            $updateData['parent_id'] = $parent_id;
        }
    }

    // Cập nhật trạng thái nhanh
    if (isset($_POST['status']) && $_POST['status'] != '' && $_POST['status'] != 'keep') {
        $status = ($_POST['status'] == 'ON') ? 1 : 0;
        $updateData['status'] = $status;
    }

    if (!empty($updateData)) {
        $isUpdate = $CMSNT->update("categories", $updateData, " `id` = ? ", [$id]);
        if ($isUpdate) {

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "Cập nhật nhanh chuyên mục (ID $id)"
            ]);
            die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
        }
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có gì được thay đổi')]));
}


if ($_POST['action'] == 'reset_total_money_users') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $isUpdate = $CMSNT->update('users', [
        'total_money'  => 0
    ], " `total_money` > 0 ");
    if (isset($isUpdate)) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Reset tổng nạp toàn bộ thành viên'
        ]);
        die(json_encode(['status' => 'success', 'msg' => 'Reset tổng nạp toàn bộ user thành công!']));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Reset thất bại')]));
}

// Reset tổng nạp (total_money) cho 1 user cụ thể — dùng ở trang user-edit
if ($_POST['action'] == 'reset_total_money_user') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status' => 'error',
            'msg'    => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $user_id = validate_int($_POST['id'], 1);
    if ($user_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID thành viên không hợp lệ')]));
    }

    // Kiểm tra user tồn tại
    $user = $CMSNT->get_row_safe("SELECT `id`, `username`, `total_money` FROM `users` WHERE `id` = ?", [$user_id]);
    if (!$user) {
        die(json_encode(['status' => 'error', 'msg' => __('Thành viên không tồn tại trong hệ thống')]));
    }

    // Lưu lại giá trị cũ để ghi log
    $old_total_money = $user['total_money'];

    // Reset total_money về 0
    $isUpdate = $CMSNT->update('users', [
        'total_money' => 0
    ], "`id` = ?", [$user_id]);

    if ($isUpdate) {
        // Ghi log chi tiết hành động
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => sprintf(__('[Admin] Reset tổng nạp thành viên %s[%s] từ %s về 0.'), $user['username'], $user['id'], format_currency($old_total_money))
        ]);
        $CMSNT->insert("logs", [
            'user_id'    => $user['id'],
            'createdate' => gettime(),
            'action'     => sprintf(__('Admin %s đã reset tổng nạp của bạn về 0.'), $getUser['username'])
        ]);

        /** NOTE ACTION — thông báo cho admin qua Telegram */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Reset tổng nạp thành viên %s[%s] từ %s về 0.'), $user['username'], $user['id'], format_currency($old_total_money)), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg'    => sprintf(__('Reset tổng nạp thành viên %s thành công!'), $user['username'])
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Reset thất bại')]));
}

if ($_POST['action'] == 'update_status_user') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $user_id = validate_int($_POST['id'], 1);
    if ($user_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID thành viên không hợp lệ')]));
    }
    if (!$user = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$user_id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Thành viên không tồn tại trong hệ thống']));
    }
    $banned_status = !empty($_POST['status']) ? (in_array($_POST['status'], ['0', '1']) ? intval($_POST['status']) : 0) : 0;
    $isUpdate = $CMSNT->update("users", [
        'banned'    => $banned_status
    ], " `id` = ? ", [$user_id]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Status User (' . $user['username'] . ' - ' . $user['id'] . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}


if ($_POST['action'] == 'update_product_code_product_stock') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $stock_id = validate_int($_POST['id'], 1);
    if ($stock_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe(" SELECT * FROM `product_stock` WHERE `id` = ? ", [$stock_id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Tài khoản không tồn tại trong hệ thống']));
    }
    $product_code = !empty($_POST['product_code']) ? validate_string($_POST['product_code'], 255) : false;
    $isUpdate = $CMSNT->update("product_stock", [
        'product_code'    => ($product_code !== false) ? $product_code : $row['product_code']
    ], " `id` = ? ", [$stock_id]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Cập nhật mã kho hàng cho tài khoản (' . $row['uid'] . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => 'Cập nhật kho hàng tài khoản ' . $row['uid'] . ' thành công!']));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật kho hàng tài khoản ' . $row['uid'] . ' thất bại')]));
}

if ($_POST['action'] == 'refundOrder') {
    if (checkPermission($getUser['admin'], 'refund_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => 'ID đơn hàng không tồn tại']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }
    if (!$product_order = $CMSNT->get_row_safe(" SELECT * FROM `product_order` WHERE `id` = ? ", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Đơn hàng không tồn tại trong hệ thống']));
    }
    if ($product_order['refund'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng này đã được hoàn tiền rồi')]));
    }
    if ($product_order['amount'] == 0 || $product_order['pay'] == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng này đã được hoàn tiền rồi')]));
    }
    if (empty($_POST['reason'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng nhập lý do hoàn tiền']));
    }
    $reason = validate_string($_POST['reason'], 500);
    if ($reason === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Lý do hoàn tiền không hợp lệ')]));
    }
    // Bắt đầu transaction tổng thể để đảm bảo tính nhất quán
    $CMSNT->query("START TRANSACTION");

    try {
        // Lấy kiểu hoàn tiền (full/partial/percent) - whitelist để tránh giá trị bất hợp lệ
        $refundType = isset($_POST['refundType']) && in_array($_POST['refundType'], ['full', 'partial', 'percent']) ? $_POST['refundType'] : 'full';
        $User = new users();
        $success = false;

        if ($refundType == 'partial') {
            $reason = 'Hoàn tiền một phần đơn hàng #' . $product_order['trans_id'] . ' (' . $reason . ')';
            // Lấy số lượng cần hoàn (nếu có)
            $partialQuantity = isset($_POST['partialQuantity']) ? intval($_POST['partialQuantity']) : 0;
            if ($partialQuantity > $product_order['amount']) {
                throw new Exception(__('Số lượng tài khoản cần hoàn vượt quá số lượng tài khoản của đơn hàng này.'));
            }
            if ($partialQuantity <= 0) {
                throw new Exception(__('Vui lòng nhập số lượng tài khoản cần hoàn.'));
            }
            $rate = $product_order['pay'] / $product_order['amount'];
            // Tổng số tiền Refund
            $amountRefund = $partialQuantity * $rate;

            $leftover = $product_order['amount'] - $partialQuantity;
            $commission_fee = 0;

            // 1. Thu hồi hoa hồng TRƯỚC (nếu có)
            $user = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$product_order['buyer']]);
            if ($user['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $user['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $user['ref_id'], 'ref_ck');
                }
                $commission_fee = $amountRefund * $ck / 100;
                $removeCommission = $User->RemoveCommission($user['ref_id'], $commission_fee, __('[Admin] Thu hồi đơn hoàn tiền' . ' ' . $user['username']));
                if (!$removeCommission) {
                    throw new Exception(__('Không thể thu hồi hoa hồng'));
                }
            }

            // 2. Thu hồi tiền người bán TRƯỚC (nếu được cấu hình)
            if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                if (getRowRealtime('users', $product_order['seller'], 'money') < $amountRefund) {
                    throw new Exception(__('Số dư khả dụng của Seller không đủ để hoàn lại cho người mua'));
                }
                $removeSeller = $User->RemoveCredits($product_order['seller'], $amountRefund, __('[Admin] Thu hồi hoàn tiền một phần đơn hàng') . ' #' . $product_order['trans_id'], 'TAKE_REFUND_partial_ORDER_' . $product_order['trans_id'] . '_' . time());
                if (!$removeSeller) {
                    throw new Exception(__('Không thể thu hồi tiền người bán'));
                }
            }

            // 3. Hoàn tiền cho người mua SAU
            $isRefund = $User->RefundCredits($product_order['buyer'], $amountRefund, "[Admin] $reason", 'REFUND_partial_ORDER_' . $product_order['trans_id'] . '_' . time());
            if (!$isRefund) {
                throw new Exception(__('Không thể hoàn tiền cho người mua'));
            }

            // 4. Cập nhật đơn hàng
            $updateOrder = $CMSNT->update('product_order', [
                'pay'    => $product_order['pay'] - $amountRefund,
                'cost'   => ($product_order['cost'] / $product_order['amount']) * $leftover,
                'money'  => ($product_order['money'] / $product_order['amount']) * $leftover,
                'amount' => $leftover
            ], " `id` = ? ", [$id]);
            if (!$updateOrder) {
                throw new Exception(__('Không thể cập nhật đơn hàng'));
            }

            // 5. Ghi log
            $insertLog = $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('[Admin] Hoàn tiền một phần đơn hàng') . ' (' . $product_order['trans_id'] . ')'
            ]);
            if (!$insertLog) {
                throw new Exception(__('Không thể ghi log'));
            }
            /** NOTE ACTION */
            $my_text = $CMSNT->site('noti_refund_orders');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{action}', __('Hoàn tiền một phần đơn hàng') . ' (' . $product_order['trans_id'] . ') Lý do: ' . $reason, $my_text);
            $my_text = str_replace('{ip}', myip(), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            $success = true;
        } else if ($refundType == 'percent') {
            // HOÀN TIỀN THEO PHẦN TRĂM
            // Áp dụng cho đơn có 1 tài khoản: chỉ giảm số tiền theo %, không trả lại tài khoản,
            // đồng thời thu hồi hoa hồng và tiền seller theo cùng tỷ lệ %.

            // Chỉ cho phép khi đơn còn đúng 1 tài khoản (theo nghiệp vụ chỉ định)
            if ($product_order['amount'] != 1) {
                throw new Exception(__('Hoàn theo % chỉ áp dụng cho đơn hàng có 1 tài khoản.'));
            }

            // Validate phần trăm trong khoảng (0, 100], cho phép số thập phân
            $percentValue = isset($_POST['percentValue']) ? validate_float($_POST['percentValue'], 0.01, 100) : false;
            if ($percentValue === false) {
                throw new Exception(__('Phần trăm hoàn không hợp lệ (phải > 0 và ≤ 100)'));
            }

            // Tính số tiền hoàn dựa trên pay còn lại của đơn (trường hợp đơn đã hoàn % vài lần trước đó)
            $amountRefund = $product_order['pay'] * $percentValue / 100;
            // Làm tròn để tránh sai số float khi so sánh hoàn 100%
            $amountRefund = round($amountRefund, 2);

            $reason = 'Hoàn ' . $percentValue . '% đơn hàng #' . $product_order['trans_id'] . ' (' . $reason . ')';

            $commission_fee = 0;

            // 1. Thu hồi hoa hồng TRƯỚC (nếu đơn này có người giới thiệu)
            $user = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$product_order['buyer']]);
            if ($user['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $user['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $user['ref_id'], 'ref_ck');
                }
                // Hoa hồng thu hồi tính theo số tiền thực hoàn (đã chứa tỷ lệ %)
                $commission_fee = $amountRefund * $ck / 100;
                $removeCommission = $User->RemoveCommission($user['ref_id'], $commission_fee, __('[Admin] Thu hồi đơn hoàn tiền' . ' ' . $user['username']));
                if (!$removeCommission) {
                    throw new Exception(__('Không thể thu hồi hoa hồng'));
                }
            }

            // 2. Thu hồi tiền người bán TRƯỚC (nếu hệ thống bật cộng tiền seller)
            if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                if (getRowRealtime('users', $product_order['seller'], 'money') < $amountRefund) {
                    throw new Exception(__('Số dư khả dụng của Seller không đủ để hoàn lại cho người mua'));
                }
                $removeSeller = $User->RemoveCredits($product_order['seller'], $amountRefund, __('[Admin] Thu hồi hoàn tiền theo % đơn hàng') . ' #' . $product_order['trans_id'], 'TAKE_REFUND_percent_ORDER_' . $product_order['trans_id'] . '_' . time());
                if (!$removeSeller) {
                    throw new Exception(__('Không thể thu hồi tiền người bán'));
                }
            }

            // 3. Hoàn tiền cho người mua SAU
            $isRefund = $User->RefundCredits($product_order['buyer'], $amountRefund, "[Admin] $reason", 'REFUND_percent_ORDER_' . $product_order['trans_id'] . '_' . time());
            if (!$isRefund) {
                throw new Exception(__('Không thể hoàn tiền cho người mua'));
            }

            // 4. Cập nhật đơn hàng
            // - pay giảm đúng số tiền vừa hoàn
            // - cost giữ nguyên (vì người mua vẫn giữ tài khoản, giá vốn không đổi)
            // - money (lợi nhuận) giảm đúng số tiền hoàn để báo cáo lãi/lỗ chính xác
            // - amount giữ = 1 vì không trả lại tài khoản
            $newPay = $product_order['pay'] - $amountRefund;
            $newMoney = $product_order['money'] - $amountRefund;

            // Edge case: nếu hoàn đủ 100% (pay về 0) -> đánh dấu đơn refund toàn bộ giống chế độ Hoàn toàn bộ
            if ($newPay <= 0.009) {
                $updateOrder = $CMSNT->update('product_order', [
                    'refund'    => 1,
                    'trash'     => 1,
                    'pay'       => 0,
                    'cost'      => 0,
                    'amount'    => 0,
                    'money'     => 0
                ], " `id` = ? ", [$id]);
            } else {
                $updateOrder = $CMSNT->update('product_order', [
                    'pay'    => $newPay,
                    'money'  => $newMoney
                ], " `id` = ? ", [$id]);
            }
            if (!$updateOrder) {
                throw new Exception(__('Không thể cập nhật đơn hàng'));
            }

            // 5. Ghi log thao tác admin để audit
            $insertLog = $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('[Admin] Hoàn tiền theo %') . ' ' . $percentValue . '% ' . __('đơn hàng') . ' (' . $product_order['trans_id'] . ')'
            ]);
            if (!$insertLog) {
                throw new Exception(__('Không thể ghi log'));
            }
            /** NOTE ACTION - thông báo telegram/discord cho admin */
            $my_text = $CMSNT->site('noti_refund_orders');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{action}', __('Hoàn tiền theo %') . ' ' . $percentValue . '% ' . __('đơn hàng') . ' (' . $product_order['trans_id'] . ') Lý do: ' . $reason, $my_text);
            $my_text = str_replace('{ip}', myip(), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            $success = true;
        } else {
            // HOÀN TIỀN TOÀN BỘ
            $reason = 'Hoàn tiền đơn hàng #' . $product_order['trans_id'] . ' (' . $reason . ')';


            $commission_fee = 0;

            // 1. Thu hồi hoa hồng TRƯỚC (nếu có)
            $user = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$product_order['buyer']]);
            if ($user['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $user['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $user['ref_id'], 'ref_ck');
                }
                $commission_fee = $product_order['pay'] * $ck / 100;
                $removeCommission = $User->RemoveCommission($user['ref_id'], $commission_fee, __('[Admin] Thu hồi đơn hoàn tiền' . ' ' . $user['username']));
                if (!$removeCommission) {
                    throw new Exception(__('Không thể thu hồi hoa hồng'));
                }
            }

            // 2. Thu hồi tiền người bán TRƯỚC (nếu được cấu hình)
            if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                // Kiểm tra số dư người bán trước khi hoàn tiền
                if (getRowRealtime('users', $product_order['seller'], 'money') < $product_order['pay']) {
                    throw new Exception(__('Số dư khả dụng của Seller không đủ để hoàn lại cho người mua'));
                }
                $removeSeller = $User->RemoveCredits($product_order['seller'], $product_order['pay'], __('[Admin] Thu hồi hoàn tiền đơn hàng') . ' #' . $product_order['trans_id'], 'TAKE_REFUND_ORDER_' . $product_order['trans_id']);
                if (!$removeSeller) {
                    throw new Exception(__('Không thể thu hồi tiền người bán'));
                }
            }

            // 3. Hoàn tiền cho người mua SAU
            $isRefund = $User->RefundCredits($product_order['buyer'], $product_order['pay'], "[CTV] $reason", 'REFUND_ORDER_' . $product_order['trans_id']);
            if (!$isRefund) {
                throw new Exception(__('Không thể hoàn tiền cho người mua'));
            }

            // 4. Cập nhật đơn hàng
            $updateOrder = $CMSNT->update('product_order', [
                'refund'    => 1,
                'trash'     => 1,
                'pay'       => 0,
                'cost'      => 0,
                'amount'    => 0,
                'money'     => 0
            ], " `id` = ? ", [$id]);
            if (!$updateOrder) {
                throw new Exception(__('Không thể cập nhật đơn hàng'));
            }

            // 5. Ghi log
            $insertLog = $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('[Admin] Hoàn tiền đơn hàng') . ' (' . $product_order['trans_id'] . ')'
            ]);
            if (!$insertLog) {
                throw new Exception(__('Không thể ghi log'));
            }
            /** NOTE ACTION */
            $my_text = $CMSNT->site('noti_refund_orders');
            $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{action}', __('Hoàn tiền đơn hàng') . ' (' . $product_order['trans_id'] . ') Lý do: ' . $reason, $my_text);
            $my_text = str_replace('{ip}', myip(), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);

            $success = true;
        }

        if ($success) {
            // Commit transaction nếu mọi thứ thành công
            $CMSNT->query("COMMIT");
            die(json_encode(['status' => 'success', 'msg' => __('Hoàn tiền đơn hàng thành công!')]));
        }
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $CMSNT->query("ROLLBACK");
        die(json_encode(['status' => 'error', 'msg' => $e->getMessage()]));
    }
}

if ($_POST['action'] == 'update_stt_table_product') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Bạn không có quyền sử dụng tính năng này'
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $stt = !empty($_POST['stt']) ? (validate_int($_POST['stt'], 0) ?: 0) : 0;
    $isUpdate = $CMSNT->update("products", [
        'stt'       => $stt
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Cập nhật ưu tiên sản phẩm (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => 'Cập nhật ưu tiên sản phẩm ID ' . $id . ' thành công!']));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'update_category_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $parent_id = !empty($_POST['category_id']) ? (validate_int($_POST['category_id'], 0) ?: 0) : 0;
    $isUpdate = $CMSNT->update("categories", [
        'parent_id'    => $parent_id
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Cập nhật chuyên mục cha cho chuyên mục (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật chuyên mục cha thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'update_category_product') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $category_id = !empty($_POST['category_id']) ? (validate_int($_POST['category_id'], 0) ?: 0) : 0;
    $isUpdate = $CMSNT->update("products", [
        'category_id'    => $category_id
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Cập nhật chuyên mục cho sản phẩm (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật chuyên mục thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}
if ($_POST['action'] == 'updateTableProductAPI') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $status = !empty($_POST['status']) ? (validate_int($_POST['status'], 0, 1) ?: 0) : 0;
    $isUpdate = $CMSNT->update("suppliers", [
        'status'    => $status
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Supplier (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'updateTableCategory') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Bạn không có quyền sử dụng tính năng này'
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $status = !empty($_POST['status']) ? (validate_int($_POST['status'], 0, 1) ?: 0) : 0;
    $isUpdate = $CMSNT->update("categories", [
        'status'    => $status
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Table Category (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}



if ($_POST['action'] == 'update_status_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $status = !empty($_POST['status']) ? (validate_int($_POST['status'], 0, 1) ?: 0) : 0;
    $isUpdate = $CMSNT->update("categories", [
        'status'    => $status
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Status Category (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}


if ($_POST['action'] == 'update_status_product') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $status = !empty($_POST['status']) ? (validate_int($_POST['status'], 0, 1) ?: 0) : 0;
    $isUpdate = $CMSNT->update("products", [
        'status'    => $status
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Status Product (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}



if ($_POST['action'] == 'cancel_email_campaigns') {
    if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $isUpdate = $CMSNT->update("email_campaigns", [
        'status'  => 2
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

// Cập nhật trạng thái ngôn ngữ (toggle on/off)
if ($_POST['action'] == 'updateLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$language = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại trong hệ thống')]));
    }

    $stt = isset($_POST['stt']) ? intval($_POST['stt']) : $language['stt'];
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

    $isUpdate = $CMSNT->update("languages", [
        'stt' => $stt,
        'status' => $status
    ], " `id` = ? ", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật ngôn ngữ %s (ID %s)'), $language['lang'], $id)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

// Cập nhật thứ tự sắp xếp ngôn ngữ (drag-drop)
if ($_POST['action'] == 'updateLanguageOrder') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['order'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        $id = intval($item['id']);
        $position = intval($item['position']);
        if ($id > 0) {
            $isUpdate = $CMSNT->update("languages", [
                'stt' => $position
            ], " `id` = ? ", [$id]);
            if ($isUpdate) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật thứ tự sắp xếp %d ngôn ngữ'), $updateCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thứ tự thất bại')]));
}

if ($_POST['action'] == 'setDefaultLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('Data does not exist')
        ]);
        die($data);
    }
    $CMSNT->update("languages", [
        'lang_default' => 0
    ], " `id` > 0 ");
    $isUpdate = $CMSNT->update("languages", [
        'lang_default' => 1
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Set default language (' . $row['lang'] . ' ID ' . $row['id'] . ')'
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Language status change successful')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'changeTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $value = isset($_POST['value']) ? validate_string($_POST['value'], 1000) : false;
    if ($value === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Giá trị không hợp lệ')]));
    }
    $isUpdate = $CMSNT->update("translate", [
        'value'  => $value
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Update successful!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Update failed!')]));
}

if ($_POST['action'] == 'setDefaultCurrency') {
    if (checkPermission($getUser['admin'], 'edit_currency') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => 'ID tiền tệ không tồn tại trong hệ thống'
        ]);
        die($data);
    }
    $CMSNT->update("currencies", [
        'default_currency' => 0
    ], " `id` > 0 ");
    $isUpdate = $CMSNT->update("currencies", [
        'default_currency' => 1
    ], " `id` = ? ", [$id]);
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Set mặc định tiền tệ (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Thay đổi trạng thái tiền tệ thành công'
        ]);
        die($data);
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'Cập nhật thất bại']));
    }
}

if ($_POST['action'] == 'logoutALL') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    foreach ($CMSNT->get_list(" SELECT * FROM `users` WHERE `id` > 0 ") as $row) {
        $CMSNT->update('users', [
            'token'     => generateUltraSecureToken(32)
        ], " `id` = ? ", [$row['id']]);
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Log out all members on the system')
    ]);
    $data = json_encode([
        'status'    => 'success',
        'msg'       => __('Đăng xuất tất cả tài khoản thành công!')
    ]);
    die($data);
}

if ($_POST['action'] == 'changeAPIKey') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    foreach ($CMSNT->get_list(" SELECT * FROM `users`  ") as $row) {
        $CMSNT->update('users', [
            'api_key'     => generateUltraSecureToken(16)
        ], " `id` = ? ", [$row['id']]);
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Change API Key for all members')
    ]);

    $data = json_encode([
        'status'    => 'success',
        'msg'       => __('Thay đổi API KEY thành công!')
    ]);
    die($data);
}


// Thêm hàm xử lý cập nhật thứ tự chuyên mục khi kéo thả
if ($_POST['action'] == 'updateCategorySTT') {
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $order = $_POST['order'];

        foreach ($order as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $position = isset($item['position']) ? intval($item['position']) : 0;

            if ($id > 0) {
                $CMSNT->update("categories", [
                    'stt' => $position
                ], " `id` = ? ", [$id]);
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => 'Cập nhật thứ tự thành công!'
        ]));
    }
}

if ($_POST['action'] == 'updateChildCategorySTT') {
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $order = $_POST['order'];

        foreach ($order as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $position = isset($item['position']) ? intval($item['position']) : 0;

            if ($id > 0) {
                // Cập nhật thứ tự cho danh mục con
                $check = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id]);
                if ($check) {
                    $CMSNT->update("categories", [
                        'stt' => $position
                    ], " `id` = ? ", [$id]);
                }
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => 'Cập nhật thứ tự chuyên mục con thành công!'
        ]));
    }
}

// Cập nhật trạng thái nhiều chuyên mục con cùng lúc
if ($_POST['action'] == 'bulk_update_category_status') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    if (empty($_POST['category_ids']) || !is_array($_POST['category_ids'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng chọn ít nhất một chuyên mục']));
    }

    if (!isset($_POST['new_status']) || $_POST['new_status'] === '') {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng chọn trạng thái mới']));
    }

    $category_ids = array_map('intval', $_POST['category_ids']);
    $new_status = intval($_POST['new_status']);
    $updated_count = 0;

    foreach ($category_ids as $id) {
        if ($id > 0) {
            // Kiểm tra chuyên mục có tồn tại không
            if ($CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id])) {
                $isUpdate = $CMSNT->update("categories", [
                    'status' => $new_status
                ], " `id` = ? ", [$id]);
                if ($isUpdate) {
                    $updated_count++;
                }
            }
        }
    }

    if ($updated_count > 0) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Cập nhật trạng thái hàng loạt cho $updated_count chuyên mục"
        ]);
        die(json_encode(['status' => 'success', 'msg' => "Đã cập nhật trạng thái cho $updated_count chuyên mục thành công!"]));
    }

    die(json_encode(['status' => 'error', 'msg' => 'Không có chuyên mục nào được cập nhật']));
}

// Cập nhật parent_id cho nhiều chuyên mục cùng lúc
if ($_POST['action'] == 'bulk_update_parent_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    if (empty($_POST['category_ids']) || !is_array($_POST['category_ids'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng chọn ít nhất một chuyên mục']));
    }

    if (!isset($_POST['new_parent_id']) || $_POST['new_parent_id'] === '') {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng chọn chuyên mục cha mới']));
    }

    $category_ids = array_map('intval', $_POST['category_ids']);
    $new_parent_id = validate_int($_POST['new_parent_id'], 0);
    if ($new_parent_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID chuyên mục cha không hợp lệ')]));
    }
    $updated_count = 0;
    $error_count = 0;
    $category_names = [];

    // Kiểm tra parent_id hợp lệ (nếu không phải 0)
    if ($new_parent_id > 0) {
        if (!$CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `parent_id` = 0", [$new_parent_id])) {
            die(json_encode(['status' => 'error', 'msg' => 'Chuyên mục cha được chọn không tồn tại hoặc không phải là chuyên mục gốc']));
        }
    }

    foreach ($category_ids as $id) {
        if ($id > 0) {
            // Kiểm tra chuyên mục có tồn tại không
            if (!$category = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id])) {
                $error_count++;
                continue;
            }

            // Không cho phép đặt chuyên mục làm con của chính nó
            if ($id == $new_parent_id) {
                $error_count++;
                continue;
            }

            // Không cho phép đặt chuyên mục cha làm con của chuyên mục con của nó
            if ($new_parent_id > 0) {
                $checkChild = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `parent_id` = ?", [$new_parent_id, $id]);
                if ($checkChild) {
                    $error_count++;
                    continue;
                }
            }

            $isUpdate = $CMSNT->update("categories", [
                'parent_id' => $new_parent_id
            ], " `id` = ? ", [$id]);

            if ($isUpdate) {
                $category_names[] = $category['name'];
                $updated_count++;
            } else {
                $error_count++;
            }
        }
    }

    if ($updated_count > 0) {
        $parent_name = $new_parent_id == 0 ? 'Chuyên mục gốc' : getRowRealtime('categories', $new_parent_id, 'name');


        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Cập nhật parent_id hàng loạt cho $updated_count chuyên mục về '$parent_name'"
        ]);

        $success_msg = "Đã cập nhật parent cho $updated_count chuyên mục về '$parent_name' thành công!";
        if ($error_count > 0) {
            $success_msg .= " ($error_count chuyên mục không thể cập nhật)";
        }

        die(json_encode(['status' => 'success', 'msg' => $success_msg]));
    }

    die(json_encode(['status' => 'error', 'msg' => 'Không có chuyên mục nào được cập nhật']));
}

if ($_POST['action'] == 'single_auto_translate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $target_lang = isset($_POST['target_lang']) ? trim($_POST['target_lang']) : '';

    if (empty($text) || empty($target_lang)) {
        die(json_encode(['status' => 'error', 'msg' => 'Thiếu nội dung cần dịch hoặc mã ngôn ngữ']));
    }

    if ($CMSNT->site('chatgpt_api_key') == '') {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng cấu hình API Key ChatGPT tại Admin -> Cài Đặt -> Kết nối']));
    }

    $api_key = $CMSNT->site('chatgpt_api_key');
    $model = $CMSNT->site('chatgpt_model') ?: 'gpt-4o-mini';

    $prompt = "Translate the following text to language code \"$target_lang\". " .
        "Return ONLY the translated text, no explanations, no quotes, no extra formatting.\n\n" .
        "Text to translate: $text";

    $url = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 500,
        'temperature' => 0.3
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        die(json_encode(['status' => 'error', 'msg' => 'Lỗi kết nối: ' . curl_error($ch)]));
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        die(json_encode(['status' => 'error', 'msg' => "Lỗi API (HTTP $http_code): $response"]));
    }

    $response_data = json_decode($response, true);
    if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Không nhận được kết quả dịch từ AI']));
    }

    $translated_text = trim($response_data['choices'][0]['message']['content']);

    // Nếu dịch thành công và có id, cập nhật trực tiếp vào database
    if ($id > 0) {
        $CMSNT->update('translate', ['value' => $translated_text], " `id` = ? ", [$id]);
    }

    die(json_encode([
        'status' => 'success',
        'translated_text' => $translated_text
    ]));
}

if ($_POST['action'] == 'log_bulk_translate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $completed = isset($_POST['completed']) ? intval($_POST['completed']) : 0;
    $failed = isset($_POST['failed']) ? intval($_POST['failed']) : 0;

    if ($completed > 0 || $failed > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Dịch tự động hàng loạt: $completed thành công, $failed thất bại"
        ]);
    }

    die(json_encode([
        'status' => 'success',
        'msg' => 'Logged successfully'
    ]));
}

if ($_POST['action'] == 'syncTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $lang_id = validate_int($_POST['lang_id'], 1);
    if ($lang_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$lang_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại')]));
    }

    // Đọc file lang.php để lấy dữ liệu mặc định
    $langDefault = [];
    if (file_exists(__DIR__ . '/../../lang.php')) {
        include(__DIR__ . '/../../lang.php');
    }

    if (!empty($langDefault)) {
        $insertCount = 0;
        foreach ($langDefault as $key => $value) {
            $isExist = $CMSNT->get_row_safe("SELECT * FROM `translate` WHERE `lang_id` = ? AND `name` = ?", [$lang_id, $key]);
            if ($isExist) {
                continue;
            }
            $isInsert = $CMSNT->insert("translate", [
                'lang_id'   => $lang_id,
                'value'     => $value,
                'name'      => $key
            ]);
            if ($isInsert) {
                $insertCount++;
            }
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Đồng bộ bản dịch từ lang.php cho ngôn ngữ %s (%d items)'), $row['lang'], $insertCount)
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đồng bộ bản dịch thành công! %d nội dung đã được đồng bộ.'), $insertCount),
            'count' => $insertCount
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy dữ liệu trong file lang.php')]));
    }
}

if ($_POST['action'] == 'updateTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $lang_id = validate_int($_POST['lang_id'], 1);
    if ($lang_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$lang_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại')]));
    }
    if (!$default = $CMSNT->get_row("SELECT * FROM `languages` WHERE `lang_default` = 1 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy ngôn ngữ mặc định hệ thống')]));
    }

    $CMSNT->remove("translate", " `lang_id` = ? ", [$lang_id]);

    $translates = $CMSNT->get_list_safe("SELECT * FROM `translate` WHERE `lang_id` = ? ", [$default['id']]);
    $copyCount = 0;
    foreach ($translates as $trans) {
        $CMSNT->insert("translate", [
            'value' => $trans['name'],
            'name'  => $trans['name'],
            'lang_id'   => $lang_id
        ]);
        $copyCount++;
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => "Tạo lại $copyCount lời dịch từ ngôn ngữ gốc cho " . $row['lang']
    ]);

    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Tạo lại thành công') . ' ' . $copyCount . ' ' . __('bản dịch từ ngôn ngữ mặc định')
    ]));
}

// Reset Secret Key Google 2FA
if ($_POST['action'] == 'reset_2fa_key') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $user_id = validate_int($_POST['id'], 1);
    if ($user_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID thành viên không hợp lệ')]));
    }
    if (!$user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$user_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Thành viên không tồn tại trong hệ thống')]));
    }
    if ($getUser['admin'] != 99999 && $user['admin'] == 99999) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    require_once(__DIR__ . '/../../libs/Google2FA.php');
    $newSecretKey = generateSecretKey_Google2FA();

    $isUpdate = $CMSNT->update('users', [
        'SecretKey_2fa' => $newSecretKey
    ], "`id` = ?", [$user_id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Reset Secret Key Google 2FA cho thành viên %s[%s].'), $user['username'], $user['id'])
        ]);
        $CMSNT->insert("logs", [
            'user_id'       => $user['id'],
            'createdate'    => gettime(),
            'action'        => __('Admin đã reset Secret Key Google 2FA của bạn.')
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', htmlspecialchars($_SERVER['SERVER_NAME'], ENT_QUOTES, 'UTF-8'), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Reset Secret Key Google 2FA cho thành viên %s[%s].'), $user['username'], $user['id']), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Reset Secret Key Google 2FA cho thành viên %s thành công!'), $user['username'])]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Reset thất bại')]));
}

// Xử lý Test SMTP
if ($_POST['action'] == 'test_smtp') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $smtp_host = validate_string($_POST['smtp_host'], 255);
    $smtp_port = validate_int($_POST['smtp_port'], 1, 65535);
    $smtp_encryption = isset($_POST['smtp_encryption']) && in_array($_POST['smtp_encryption'], ['ssl', 'tls', '']) ? $_POST['smtp_encryption'] : '';
    $smtp_email = validate_string($_POST['smtp_email']);
    $smtp_from_email = !empty($_POST['smtp_from_email']) ? validate_email($_POST['smtp_from_email']) : false;
    $smtp_password = $_POST['smtp_password'];
    $test_email = validate_email($_POST['test_email']);

    if ($smtp_host === false || $smtp_port === false || $smtp_email === false || empty($smtp_password)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng điền đầy đủ thông tin SMTP')]));
    }

    if ($test_email === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Email nhận test không hợp lệ')]));
    }

    $from_email = ($smtp_from_email !== false) ? $smtp_from_email : $smtp_email;

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_email;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = (int) $smtp_port;
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $siteName = $CMSNT->site('title') ? $CMSNT->site('title') : 'ShopClone7';
        $mail->setFrom($from_email, $siteName);
        $mail->addAddress($test_email);
        $mail->addReplyTo($from_email, $siteName);
        $mail->isHTML(true);
        $mail->Subject = __('Email test SMTP') . ' - ' . $siteName;
        $mail->Body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">'
            . '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">'
            . '<h2 style="color: #fff; margin: 0;">✅ ' . __('SMTP hoạt động tốt!') . '</h2>'
            . '</div>'
            . '<div style="background: #f8f9fa; padding: 25px; border-radius: 0 0 10px 10px; border: 1px solid #e9ecef; border-top: none;">'
            . '<p style="color: #333; font-size: 15px; line-height: 1.6;">' . __('Chúc mừng! Cấu hình SMTP của bạn đã hoạt động chính xác.') . '</p>'
            . '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">'
            . '<tr><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff; font-weight: 600; width: 140px;">SMTP Host</td><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff;">' . htmlspecialchars($smtp_host, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff; font-weight: 600;">' . __('Cổng') . '</td><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff;">' . $smtp_port . '</td></tr>'
            . '<tr><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff; font-weight: 600;">Email</td><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff;">' . htmlspecialchars($from_email, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff; font-weight: 600;">' . __('Thời gian') . '</td><td style="padding: 8px 12px; border: 1px solid #dee2e6; background: #fff;">' . date('d/m/Y H:i:s') . '</td></tr>'
            . '</table>'
            . '<p style="color: #6c757d; font-size: 13px; margin-top: 15px; text-align: center;">' . __('Email này được gửi tự động từ hệ thống') . ' ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div></div>';

        if ($mail->send()) {
            die(json_encode([
                'status' => 'success',
                'msg' => sprintf(__('Gửi email test thành công đến %s! Vui lòng kiểm tra hộp thư.'), htmlspecialchars($test_email, ENT_QUOTES, 'UTF-8'))
            ]));
        } else {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Không thể gửi email test')
            ]));
        }
    } catch (PHPMailer\PHPMailer\Exception $e) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Lỗi SMTP') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        ]));
    } catch (Exception $e) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Lỗi hệ thống') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        ]));
    }
}

// Xử lý Test Telegram
if ($_POST['action'] == 'test_telegram') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $telegram_token = validate_string($_POST['telegram_token'], 255);
    $telegram_chat_id = validate_string($_POST['telegram_chat_id'], 255);
    $telegram_url = !empty($_POST['telegram_url']) ? validate_url($_POST['telegram_url']) : false;
    $message = isset($_POST['message']) ? validate_string($_POST['message'], 2000) : false;

    if ($telegram_token === false || $telegram_chat_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng điền Bot Token và Chat ID')]));
    }

    if ($message === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Nội dung tin nhắn không được để trống')]));
    }

    if ($telegram_url === false || empty($telegram_url)) {
        $telegram_url = 'https://api.telegram.org/';
    }

    try {
        $api_url = $telegram_url . 'bot' . $telegram_token . '/sendMessage';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'chat_id' => $telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!empty($curl_error)) {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Không thể kết nối Telegram API') . ': ' . htmlspecialchars($curl_error, ENT_QUOTES, 'UTF-8')
            ]));
        }

        $result = json_decode($response, true);

        if (isset($result['ok']) && $result['ok'] === true) {
            die(json_encode([
                'status' => 'success',
                'msg' => __('Gửi tin nhắn test Telegram thành công! Vui lòng kiểm tra chat.')
            ]));
        } else {
            $errorDesc = isset($result['description']) ? $result['description'] : __('Lỗi không xác định');
            die(json_encode([
                'status' => 'error',
                'msg' => __('Telegram API lỗi') . ': ' . htmlspecialchars($errorDesc, ENT_QUOTES, 'UTF-8')
            ]));
        }
    } catch (Exception $e) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Lỗi hệ thống') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        ]));
    }
}

// Cập nhật setting từ trang Addons
if ($_POST['action'] == 'updateSetting') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['name'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Tham số không hợp lệ')]));
    }

    $allowedKeys = ['preview_uid_license'];
    $name = validate_alphanumeric($_POST['name'], 50);
    if ($name === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên tham số không hợp lệ')]));
    }

    if (!in_array($name, $allowedKeys)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không được phép cập nhật cài đặt này')]));
    }

    $value = isset($_POST['value']) ? validate_string($_POST['value'], 500) : '';
    if ($value === false) $value = '';
    $isUpdate = $CMSNT->update("settings", [
        'value' => $value
    ], " `name` = ? ", [$name]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật cài đặt Addon') . ': ' . $name
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Lưu thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Lưu thất bại')]));
}

// Gửi thông báo broadcast đến tất cả user đã kết nối Telegram Shopping
// Chỉ tác động user có telegram_chat_id — không đụng tới user web-only
if ($_POST['action'] == 'telegram_shop_broadcast') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng chức năng này')]));
    }

    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('This function cannot be used because this is a demo site')]));
    }

    // Validate nội dung: raw để giữ HTML tags được Telegram hỗ trợ (b, i, code, a, ...)
    // Không dùng check_string vì nó htmlspecialchars → mất format HTML.
    $message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';

    // Độ dài: min 5 để tránh spam rỗng, max 4000 (Telegram sendMessage max 4096 — chừa header cho parse)
    $len = mb_strlen($message, 'UTF-8');
    if ($len < 5) {
        die(json_encode(['status' => 'error', 'msg' => __('Nội dung thông báo quá ngắn (tối thiểu 5 ký tự)')]));
    }
    if ($len > 4000) {
        die(json_encode(['status' => 'error', 'msg' => __('Nội dung thông báo quá dài (tối đa 4000 ký tự)')]));
    }

    // Kiểm tra bot token đã cấu hình chưa
    if (empty($CMSNT->site('telegram_shop_bot_token'))) {
        die(json_encode(['status' => 'error', 'msg' => __('Chưa cấu hình Token Bot Telegram Shop')]));
    }

    // Nếu bot đang tắt → chặn luôn, tránh gửi nhầm khi admin đang bảo trì
    if ($CMSNT->site('telegram_shop_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Telegram Shop đang TẮT, vui lòng bật trước khi gửi thông báo')]));
    }

    // Nâng thời gian chạy script vì broadcast nhiều user có thể tốn thời gian (~35ms/user + xử lý lỗi)
    @set_time_limit(600);
    @ignore_user_abort(true);

    require_once(__DIR__ . '/../../libs/telegram-shop/TelegramShopBot.php');
    $stat = TelegramShopBot::broadcast($message);

    // Ghi log hành vi admin để truy vết.
    // Lưu preview nội dung đã gửi (cắt ngắn) để tiện audit, tránh log quá dài.
    $message_preview = preg_replace('/\s+/', ' ', strip_tags($message));
    if (mb_strlen($message_preview, 'UTF-8') > 180) {
        $message_preview = mb_substr($message_preview, 0, 180, 'UTF-8') . '...';
    }
    $CMSNT->insert('logs', [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => sprintf(
            __('Gửi thông báo broadcast Telegram Shop: %s user nhận / %s lỗi | Nội dung: %s'),
            $stat['sent'],
            $stat['failed'],
            $message_preview
        )
    ]);

    if ($stat['total'] === 0) {
        die(json_encode([
            'status' => 'error',
            'msg'    => __('Chưa có user nào kết nối Telegram Shopping')
        ]));
    }

    die(json_encode([
        'status' => 'success',
        'msg'    => sprintf(
            __('Đã gửi cho %s/%s user (lỗi: %s)'),
            $stat['sent'],
            $stat['total'],
            $stat['failed']
        ),
        'data'   => $stat
    ]));
}

if ($_POST['action'] == 'set_telegram_shop_webhook') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng chức năng này']));
    }

    $bot_token = $CMSNT->site('telegram_shop_bot_token');
    $webhook_code = $CMSNT->site('telegram_shop_webhook_code');

    if (empty($bot_token) || empty($webhook_code)) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng cấu hình đầy đủ Token Bot và lưu cài đặt trước khi set Webhook!']));
    }

    // Gọi getMe để lấy username của bot và lưu vào database
    $get_me_url = "https://api.telegram.org/bot" . $bot_token . "/getMe";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $get_me_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $me_result = curl_exec($ch);
    curl_close($ch);

    $bot_username = '';
    if ($me_result) {
        $me_response = json_decode($me_result, true);
        if (isset($me_response['ok']) && $me_response['ok'] == true && isset($me_response['result']['username'])) {
            $bot_username = $me_response['result']['username'];
            $CMSNT->update("settings", ['value' => $bot_username], " `name` = 'telegram_shop_bot_username' ");
        }
    }

    $webhook_url = base_url('telegram-shop.php?code=' . $webhook_code);
    $telegram_api_url = "https://api.telegram.org/bot" . $bot_token . "/setWebhook?url=" . urlencode($webhook_url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegram_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result) {
        $response = json_decode($result, true);
        if (isset($response['ok']) && $response['ok'] == true) {
            $success_msg = 'Cập nhật Webhook lên Telegram thành công!';
            if (!empty($bot_username)) {
                $success_msg .= ' (Bot Username: @' . $bot_username . ')';
            }
            die(json_encode(['status' => 'success', 'msg' => $success_msg]));
        } else {
            $error_msg = isset($response['description']) ? $response['description'] : 'Lỗi không xác định từ Telegram';
            die(json_encode(['status' => 'error', 'msg' => 'Lỗi từ Telegram: ' . $error_msg]));
        }
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'Lỗi kết nối đến Telegram API. Vui lòng thử lại sau.']));
    }
}

// -----------------------------------------------------------------------------
// [ADDON BOT QUẢN LÝ TELEGRAM] - Lưu cấu hình (status/license/token/whitelist)
// Lý do gộp nhiều trường vào 1 action: admin thường chỉnh nhiều trường cùng lúc,
// 1 lần click = 1 round-trip thay vì 4 lần gọi updateSetting rời rạc.
// -----------------------------------------------------------------------------
if ($_POST['action'] == 'update_telegram_assistant_settings') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Whitelist các key được phép cập nhật + quy tắc validate cho từng key.
    // Chỉ cập nhật key nào client gửi lên để admin có thể lưu riêng license mà không reset field khác.
    $allowed = [
        'telegram_assistant_LicenseKey'    => ['max' => 255],
        'telegram_assistant_status'        => ['enum' => ['0', '1']],
        'telegram_assistant_token'         => ['max' => 255],
        'telegram_assistant_list_username' => ['max' => 1000],
    ];

    $updated = [];
    foreach ($allowed as $key => $rule) {
        if (!isset($_POST[$key])) continue;
        $raw = (string) $_POST[$key];

        // Field status chỉ nhận enum 0/1 để tránh lưu giá trị rác làm webhook hiểu sai trạng thái
        if (isset($rule['enum'])) {
            if (!in_array($raw, $rule['enum'], true)) {
                die(json_encode(['status' => 'error', 'msg' => __('Giá trị không hợp lệ cho ') . $key]));
            }
            $value = $raw;
        } else {
            // Các field chuỗi: trim + check độ dài tối đa để tránh DoS DB
            $value = trim($raw);
            if (mb_strlen($value) > $rule['max']) {
                die(json_encode(['status' => 'error', 'msg' => $key . ' ' . __('quá dài')]));
            }
            // Dùng check_string để escape XSS nhất quán với cách telegram-shop.php lưu
            $value = check_string($value);
        }

        $CMSNT->update("settings", ['value' => $value], " `name` = ? ", [$key]);
        $updated[] = $key;
    }

    if (empty($updated)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có trường nào được gửi lên')]));
    }

    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => __('Cập nhật cấu hình Addon Bot Quản Lý Telegram') . ': ' . implode(', ', $updated)
    ]);

    die(json_encode(['status' => 'success', 'msg' => __('Lưu thành công!')]));
}

// -----------------------------------------------------------------------------
// [ADDON BOT QUẢN LÝ TELEGRAM] - Tạo lại Secret Token
// Dùng khi admin nghi ngờ secret bị lộ. Sau khi đổi BẮT BUỘC phải set lại webhook,
// nếu không Telegram vẫn gửi secret cũ và webhook_bot_telegram.php sẽ chặn hết.
// -----------------------------------------------------------------------------
if ($_POST['action'] == 'regenerate_telegram_assistant_secret') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $newSecret = generateUltraSecureToken(32);
    $CMSNT->update("settings", ['value' => $newSecret], " `name` = ? ", ['telegram_assistant_secret_token']);

    $CMSNT->insert("logs", [
        'user_id'    => $getUser['id'],
        'ip'         => myip(),
        'device'     => getUserAgent(),
        'createdate' => gettime(),
        'action'     => __('Tạo lại Secret Token Bot Quản Lý Telegram')
    ]);

    die(json_encode(['status' => 'success', 'msg' => __('Đã tạo Secret Token mới. Vui lòng nhấn "Cập nhật Webhook" để đồng bộ lên Telegram!')]));
}

// -----------------------------------------------------------------------------
// [ADDON BOT QUẢN LÝ TELEGRAM] - Đăng ký webhook lên Telegram
// Truyền kèm secret_token để Telegram gửi header X-Telegram-Bot-Api-Secret-Token
// (khớp với đoạn xác thực tại api/webhook_bot_telegram.php dòng 112-116).
// -----------------------------------------------------------------------------
if ($_POST['action'] == 'set_telegram_assistant_webhook') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Bắt buộc license phải active trước khi cho set webhook, tránh admin kích hoạt bot chui
    $checkKey = checkAddonLicense($CMSNT->site('telegram_assistant_LicenseKey'), 'SHOPCLONE7_BOTQUANLY');
    if ($checkKey['status'] != true) {
        die(json_encode(['status' => 'error', 'msg' => __('License Addon chưa kích hoạt hoặc không hợp lệ')]));
    }

    $bot_token    = trim((string) $CMSNT->site('telegram_assistant_token'));
    $secret_token = trim((string) $CMSNT->site('telegram_assistant_secret_token'));

    if (empty($bot_token)) {
        die(json_encode(['status' => 'error', 'msg' => __('Chưa cấu hình Bot Token. Vui lòng nhập và lưu Bot Token trước.')]));
    }
    if (empty($secret_token)) {
        die(json_encode(['status' => 'error', 'msg' => __('Chưa có Secret Token. Vui lòng tạo lại Secret Token trước.')]));
    }

    // URL webhook cố định, không cho admin sửa để tránh trỏ sai endpoint
    $webhook_url = base_url('api/webhook_bot_telegram.php');

    $telegram_api_url = "https://api.telegram.org/bot" . $bot_token . "/setWebhook";
    $postFields = [
        'url'           => $webhook_url,
        'secret_token'  => $secret_token,
        // Chỉ nhận 2 loại update cần thiết để giảm tải: tin nhắn thường + callback khi bấm nút
        'allowed_updates' => json_encode(['message', 'callback_query']),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegram_api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($result === false || $result === '') {
        die(json_encode(['status' => 'error', 'msg' => __('Lỗi kết nối đến Telegram API') . ($curlErr ? ': ' . $curlErr : '')]));
    }

    $response = json_decode($result, true);
    if (isset($response['ok']) && $response['ok'] == true) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Cập nhật Webhook Bot Quản Lý Telegram'),
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật Webhook lên Telegram thành công!')]));
    }

    $error_msg = isset($response['description']) ? $response['description'] : __('Lỗi không xác định từ Telegram');
    die(json_encode(['status' => 'error', 'msg' => __('Lỗi từ Telegram') . ': ' . $error_msg]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
