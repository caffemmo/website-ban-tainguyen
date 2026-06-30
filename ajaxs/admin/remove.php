<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
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


// Xóa file installer.php
if ($_POST['action'] == 'deleteInstallerFile') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Đường dẫn tới file installer.php
    $installer_path = __DIR__ . '/../../installer.php';

    // Kiểm tra file có tồn tại không
    if (!file_exists($installer_path)) {
        die(json_encode(['status' => 'error', 'msg' => __('File installer.php không tồn tại')]));
    }

    // Thử xóa file
    if (unlink($installer_path)) {
        // Ghi log hoạt động
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa file installer.php khỏi hệ thống')
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa file installer.php thành công! Bảo mật website đã được tăng cường.')
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Không thể xóa file installer.php. Vui lòng kiểm tra quyền ghi file hoặc xóa thủ công.')
        ]));
    }
}

if ($_POST['action'] == 'removeAllAccountsSold') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này') . ' Role: delete_order_product'
        ]));
    }
    $isRemove = $CMSNT->remove("product_sold", " `id` > 0 ");
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa toàn bộ tài khoản đã bán')
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  __('Xóa toàn bộ tài khoản đã bán'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'remove_payment_manual') {
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `payment_manual` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("payment_manual", " `id` = ? ", [$id]);
    if ($isRemove) {
        // XÓA LOGO BANK
        unlink("../../" . $row['icon']);
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá trang nạp tiền thủ công') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá trang nạp tiền thủ công') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa thành công'
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'empty_all_list_die') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $isRemove = $CMSNT->remove("product_die", " `id` > 0 ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa toàn bộ tài khoản DIE')
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  __('Xóa toàn bộ tài khoản DIE'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'removeTaskAutomation') {
    if (checkPermission($getUser['admin'], 'edit_automations') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `automations` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("automations", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Task (' . $row['name'] . ')'
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'removeAccountSold') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_sold` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Tài khoản không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("product_sold", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Xóa tài khoản (" . $row['uid'] . ") khỏi đơn hàng đã bán"
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa tài khoản ' . $row['uid'] . ' thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'empty_list_account_stock') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['confirm_empty_list_account'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập nội dung xác minh')]));
    }
    $confirm_empty_list_account = validate_string($_POST['confirm_empty_list_account'], 50);
    if ($confirm_empty_list_account === false || $confirm_empty_list_account != 'toi dong y') {
        die(json_encode(['status' => 'error', 'msg' => __('Nội dung xác minh không chính xác')]));
    }
    $product_code = validate_string($_POST['id'], 255);
    if ($product_code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `product_code` = ? ", [$product_code])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Kho hàng đang trống')
        ]));
    }
    $isRemove = $CMSNT->remove("product_stock", " `product_code` = ? ", [$product_code]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa toàn bộ tài khoản đang bán của kho hàng') . ' (' . $product_code . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  __('Xóa toàn bộ tài khoản đang bán của kho hàng') . ' (' . $product_code . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'empty_list_die') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $product_code = validate_string($_POST['id'], 255);
    if ($product_code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_die` WHERE `product_code` = ? ", [$product_code])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Không có dữ liệu cần xóa'
        ]));
    }
    $isRemove = $CMSNT->remove("product_die", " `product_code` = ? ", [$product_code]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa toàn bộ tài khoản DIE của kho hàng') . ' (' . $product_code . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  __('Xóa toàn bộ tài khoản DIE của kho hàng') . ' (' . $product_code . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeProductDiscount') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_discount` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("product_discount", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Xóa điều kiện giảm giá sản phẩm (' . getRowRealtime('products', $row['product_id'], 'name') . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Xóa điều kiện giảm giá sản phẩm (' . getRowRealtime('products', $row['product_id'], 'name') . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}



if ($_POST['action'] == 'removeMultipleBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có IP nào được chọn')]));
    }

    $ids = $_POST['ids'];
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $removeCount = 0;
    $errorCount = 0;
    $ipDetails = [];

    foreach ($ids as $id) {
        $id = validate_int($id, 1);
        if ($id === false) continue;

        // Kiểm tra xem IP có tồn tại không
        if (!$row = $CMSNT->get_row_safe("SELECT * FROM `block_ip` WHERE `id` = ?", [$id])) {
            $errorCount++;
            continue;
        }

        // Lưu thông tin IP để ghi log
        $ipDetails[] = $row['ip'];

        // Tiến hành xóa
        if ($CMSNT->remove("block_ip", " `id` = ?", [$id])) {
            $removeCount++;
        } else {
            $errorCount++;
        }
    }

    if ($removeCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa hàng loạt') . ' ' . $removeCount . ' Block IP (' .
                implode(', ', array_slice($ipDetails, 0, 5)) .
                (count($ipDetails) > 5 ? '...' : '') . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', htmlspecialchars($_SERVER['SERVER_NAME'], ENT_QUOTES, 'UTF-8'), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa hàng loạt') . ' ' . $removeCount . ' Block IP', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $removeCount . ' IP' .
                ($errorCount > 0 ? ', ' . $errorCount . ' IP bị lỗi' : '')
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Không có IP nào được xóa')]));
}
if ($_POST['action'] == 'removeBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `block_ip` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("block_ip", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Block IP (' . $row['ip'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Remove Block IP (' . $row['ip'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa IP thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removePromotion') {
    if (checkPermission($getUser['admin'], 'edit_promotion') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `promotions` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("promotions", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Promotion (' . format_currency($row['min']) . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Promotion (' . format_currency($row['min']) . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa promotion thành công')
        ]);
        die($data);
    }
}
if ($_POST['action'] == 'removeCategoriesOnly') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID API không tồn tại trong hệ thống'
        ]));
    }

    $categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `supplier_id` = ? ", [$id]);
    if (!is_array($categories)) {
        $categories = [];
    }
    foreach ($categories as $category) {
        if (!empty($category['icon'])) {
            $imagePath = "../../" . $category['icon'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $CMSNT->remove("categories", " `id` = ? ", [$category['id']]);
    }

    $categoriesCount = count($categories);

    // Đặt lại chuyên mục của sản phẩm thuộc nhà cung cấp này
    $CMSNT->update("products", [
        'category_id' => 0
    ], " `supplier_id` = ? ", [$id]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xóa chuyên mục API') . ' (' . $supplier['domain'] . ' - ' . $categoriesCount . ' ' . __('chuyên mục') . ')'
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Xóa chuyên mục API') . ' (' . $supplier['domain'] . ' - ' . $categoriesCount . ' ' . __('chuyên mục') . ')', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Đã xóa thành công') . ' ' . $categoriesCount . ' ' . __('chuyên mục của nhà cung cấp') . ' ' . $supplier['domain']
    ]));
}
if ($_POST['action'] == 'removeProductsOnly') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID API không tồn tại trong hệ thống'
        ]));
    }

    $productsCountRow = $CMSNT->get_row_safe("SELECT COUNT(`id`) AS `count` FROM `products` WHERE `supplier_id` = ? ", [$id]);
    $productsCount = isset($productsCountRow['count']) ? intval($productsCountRow['count']) : 0;

    $CMSNT->remove("products", " `supplier_id` = ? ", [$id]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xóa sản phẩm API') . ' (' . $supplier['domain'] . ' - ' . $productsCount . ' ' . __('sản phẩm') . ')'
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Xóa sản phẩm API') . ' (' . $supplier['domain'] . ' - ' . $productsCount . ' ' . __('sản phẩm') . ')', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Đã xóa thành công') . ' ' . $productsCount . ' ' . __('sản phẩm của nhà cung cấp') . ' ' . $supplier['domain']
    ]));
}
if ($_POST['action'] == 'removeCategoriesAndProducts') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID API không tồn tại trong hệ thống'
        ]));
    }

    // Đếm số lượng chuyên mục và sản phẩm trước khi xóa
    $categoriesCount = $CMSNT->get_row_safe("SELECT COUNT(id) as count FROM `categories` WHERE `supplier_id` = ? ", [$id])['count'];
    $productsCount = $CMSNT->get_row_safe("SELECT COUNT(id) as count FROM `products` WHERE `supplier_id` = ? ", [$id])['count'];

    // Xóa sản phẩm API
    $CMSNT->remove("products", " `supplier_id` = ? ", [$id]);

    // Xóa chuyên mục API và icon
    foreach ($CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `supplier_id` = ? ", [$id]) as $category) {
        $imagePath = "../../" . $category['icon'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Xóa icon chuyên mục nếu có
        }
        $CMSNT->remove("categories", " `id` = ? ", [$category['id']]);
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xóa chuyên mục và sản phẩm API') . ' (' . $supplier['domain'] . ' - ' . $categoriesCount . ' chuyên mục, ' . $productsCount . ' sản phẩm)'
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Xóa chuyên mục và sản phẩm API') . ' (' . $supplier['domain'] . ' - ' . $categoriesCount . ' chuyên mục, ' . $productsCount . ' sản phẩm)', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Đã xóa thành công') . ' ' . $categoriesCount . ' ' . __('chuyên mục và') . ' ' . $productsCount . ' ' . __('sản phẩm của nhà cung cấp') . ' ' . $supplier['domain']
    ]));
}

if ($_POST['action'] == 'removeSupplier') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID API không tồn tại trong hệ thống'
        ]));
    }
    if ($supplier['status'] == 1) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Vui lòng tắt trạng thái API trước khi xóa'
        ]));
    }

    // Lấy danh sách chuyên mục và icon trước khi bắt đầu transaction
    $categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `supplier_id` = ? ", [$id]);
    $categoryIcons = [];
    foreach ($categories as $category) {
        if (!empty($category['icon'])) {
            $categoryIcons[] = "../../" . $category['icon'];
        }
    }

    // Đếm số lượng sản phẩm và chuyên mục để kiểm tra sau khi xóa
    $productsCount = $CMSNT->num_rows_safe("SELECT * FROM `products` WHERE `supplier_id` = ? ", [$id]);
    $categoriesCount = count($categories);

    // Bắt đầu transaction
    $CMSNT->query("START TRANSACTION");

    try {
        // Bước 1: Xóa sản phẩm trước
        $CMSNT->remove("products", " `supplier_id` = ? ", [$supplier['id']]);

        // Bước 2: Xóa chuyên mục
        foreach ($categories as $category) {
            $CMSNT->remove("categories", " `id` = ? ", [$category['id']]);
        }

        // Bước 3: Xóa supplier
        $isRemove = $CMSNT->remove("suppliers", " `id` = ? ", [$id]);

        if (!$isRemove) {
            throw new Exception('Không thể xóa API Supplier');
        }

        // Kiểm tra xem đã xóa hết chưa
        $remainingProducts = $CMSNT->num_rows_safe("SELECT * FROM `products` WHERE `supplier_id` = ? ", [$id]);
        $remainingCategories = $CMSNT->num_rows_safe("SELECT * FROM `categories` WHERE `supplier_id` = ? ", [$id]);
        $remainingSupplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ? ", [$id]);

        if ($remainingProducts > 0 || $remainingCategories > 0 || $remainingSupplier) {
            throw new Exception('Xóa không đầy đủ: còn ' . $remainingProducts . ' sản phẩm, ' . $remainingCategories . ' chuyên mục');
        }

        // Commit transaction nếu tất cả thành công
        $CMSNT->query("COMMIT");

        // Xóa file icon chuyên mục sau khi transaction thành công (không rollback được file)
        foreach ($categoryIcons as $imagePath) {
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        // Ghi log
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Remove API Supplier') . ' (' . $supplier['domain'] . ' ID ' . $supplier['id'] . ' - ' . $productsCount . ' SP, ' . $categoriesCount . ' DM)'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Remove API Supplier') . ' (' . $supplier['domain'] . ' ID ' . $supplier['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa API thành công!') . ' (' . $productsCount . ' ' . __('sản phẩm') . ', ' . $categoriesCount . ' ' . __('chuyên mục') . ')'
        ]));
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $CMSNT->query("ROLLBACK");

        // Ghi log lỗi
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa API Supplier THẤT BẠI - ROLLBACK') . ' (' . $supplier['domain'] . ' ID ' . $supplier['id'] . ') - Error: ' . $e->getMessage()
        ]);

        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Xóa API thất bại! Đã rollback dữ liệu.') . ' Error: ' . $e->getMessage()
        ]));
    }
}


if ($_POST['action'] == 'removeOrder') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Đơn hàng không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("product_order", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->remove('product_sold', " `trans_id` = ? ", [$product_order['trans_id']]);
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Order (' . $product_order['trans_id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Order (' . $product_order['trans_id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa đơn hàng thành công!')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'bulkRemoveOrders') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được chọn')]));
    }

    $ids = json_decode($_POST['ids'], true);
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $removeCount = 0;
    $errorCount = 0;
    $orderDetails = [];

    foreach ($ids as $id) {
        $id = validate_int($id, 1);
        if ($id === false) continue;

        // Kiểm tra xem đơn hàng có tồn tại không
        if (!$product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ?", [$id])) {
            $errorCount++;
            continue;
        }

        // Lưu thông tin đơn hàng để ghi log
        $orderDetails[] = $product_order['trans_id'];

        // Tiến hành xóa
        if ($CMSNT->remove("product_order", " `id` = ?", [$id])) {
            $CMSNT->remove('product_sold', " `trans_id` = ? ", [$product_order['trans_id']]);
            $removeCount++;
        } else {
            $errorCount++;
        }
    }

    if ($removeCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa hàng loạt') . ' ' . $removeCount . ' đơn hàng (' .
                implode(', ', array_slice($orderDetails, 0, 5)) .
                (count($orderDetails) > 5 ? '...' : '') . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', htmlspecialchars($_SERVER['SERVER_NAME'], ENT_QUOTES, 'UTF-8'), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa hàng loạt') . ' ' . $removeCount . ' đơn hàng', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $removeCount . ' đơn hàng' .
                ($errorCount > 0 ? ', ' . $errorCount . ' đơn hàng bị lỗi' : '')
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được xóa')]));
}
if ($_POST['action'] == 'removeMenu') {
    if (checkPermission($getUser['admin'], 'edit_menu') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `menu` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("menu", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Menu (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Menu (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa menu thành công !'
        ]);
        die($data);
    }
}
if ($_POST['action'] == 'removeRole') {
    if (checkPermission($getUser['admin'], 'edit_role') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `admin_role` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("admin_role", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Role (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Role (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa Role thành công !'
        ]);
        die($data);
    }
}
if ($_POST['action'] == 'removeCounpon') {
    if (checkPermission($getUser['admin'], 'edit_coupon') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `coupons` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("coupons", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Coupon (' . $row['code'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Coupon (' . $row['code'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa Counpon thành công')
        ]);
        die($data);
    }
}
if ($_POST['action'] == 'removeAccountStock') {
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Tài khoản không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("product_stock", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Xóa tài khoản (" . $row['uid'] . ") khỏi kho hàng đang bán"
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa tài khoản ' . $row['uid'] . ' thành công'
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'removeImageProduct') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID sản phẩm không tồn tại trong hệ thống'
        ]));
    }
    $image = validate_string($_POST['image'], 500);
    if ($image === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu ảnh không hợp lệ')]));
    }
    unlink("../../" . dirImageProduct($image));
    // Xóa giá trị cụ thể khỏi biến $images
    $images = str_replace($image, '', $row['images']);
    // Loại bỏ dấu xuống dòng trống nếu có
    $images = preg_replace('/^\h*\v+/m', '', $images);
    $CMSNT->update('products', [
        'images'    => $images
    ], " `id` = ? ", [$row['id']]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Delete Image Product (' . $row['name'] . ' ID ' . $row['id'] . ')'
    ]);
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', 'Delete Image Product (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Xóa sản phẩm thành công')
    ]));
}


if ($_POST['action'] == 'removeProduct') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID sản phẩm không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("products", " `id` = ? ", [$id]);
    if ($isRemove) {

        // Delete product images from server
        if (!empty($product['images'])) {
            $images = explode(PHP_EOL, trim($product['images']));
            foreach ($images as $filename) {
                $filename = trim($filename);
                if (!empty($filename)) {
                    $file_path = "../../" . dirImageProduct($filename);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá sản phẩm') . ' (' . $product['name'] . ' ID ' . $product['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá sản phẩm') . ' (' . $product['name'] . ' ID ' . $product['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa sản phẩm thành công')
        ]));
    }
}


if ($_POST['action'] == 'removeCategory') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID chuyên mục không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("categories", " `id` = ? ", [$id]);
    if ($isRemove) {
        unlink("../../" . $row['icon']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chuyên mục') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chuyên mục') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa chuyên mục thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'cleanupCategories') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $cleanup_orphan = isset($_POST['cleanup_orphan']) && $_POST['cleanup_orphan'] == 1;
    $cleanup_empty = isset($_POST['cleanup_empty']) && $_POST['cleanup_empty'] == 1;
    $cleanup_all = isset($_POST['cleanup_all']) && $_POST['cleanup_all'] == 1;

    if (!$cleanup_orphan && !$cleanup_empty && !$cleanup_all) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một tùy chọn dọn dẹp')]));
    }

    $total_deleted = 0;
    $log_messages = [];

    if ($cleanup_all) {
        $all_list = $CMSNT->get_list("SELECT `id`, `name`, `icon` FROM `categories`");
        $count = count($all_list);
        if ($count > 0) {
            foreach ($all_list as $row) {
                // Xóa icon nếu có
                if (!empty($row['icon']) && file_exists("../../" . $row['icon'])) {
                    unlink("../../" . $row['icon']);
                }
                $CMSNT->remove("categories", " `id` = ? ", [$row['id']]);
            }
            $total_deleted += $count;
            $log_messages[] = $count . " tất cả chuyên mục";
        }
    } else {
        // 1. Xóa chuyên mục mồ côi
        if ($cleanup_orphan) {
            $orphan_list = $CMSNT->get_list("SELECT `id`, `name`, `icon` FROM `categories` WHERE `parent_id` > 0 AND `parent_id` NOT IN (SELECT `id` FROM `categories`)");
            $count = count($orphan_list);
            if ($count > 0) {
                foreach ($orphan_list as $row) {
                    // Xóa icon nếu có
                    if (!empty($row['icon']) && file_exists("../../" . $row['icon'])) {
                        unlink("../../" . $row['icon']);
                    }
                    $CMSNT->remove("categories", " `id` = ? ", [$row['id']]);
                }
                $total_deleted += $count;
                $log_messages[] = $count . " mục mồ côi";
            }
        }

        // 2. Xóa chuyên mục rỗng
        if ($cleanup_empty) {
            // Lấy danh sách các id chuyên mục rỗng
            $empty_list = $CMSNT->get_list("
                SELECT `id`, `name`, `icon` FROM `categories` c 
                WHERE NOT EXISTS (SELECT 1 FROM `products` p WHERE p.category_id = c.id) 
                AND NOT EXISTS (SELECT 1 FROM `categories` sub WHERE sub.parent_id = c.id)
            ");
            $count = count($empty_list);
            if ($count > 0) {
                foreach ($empty_list as $row) {
                    // Xóa icon nếu có
                    if (!empty($row['icon']) && file_exists("../../" . $row['icon'])) {
                        unlink("../../" . $row['icon']);
                    }
                    $CMSNT->remove("categories", " `id` = ? ", [$row['id']]);
                }
                $total_deleted += $count;
                $log_messages[] = $count . " mục rỗng";
            }
        }
    }

    if ($total_deleted > 0) {
        // Ghi log
        $action_msg = __('Dọn dẹp chuyên mục') . ': Đã xóa ' . implode(' và ', $log_messages);
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $action_msg
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', $action_msg, $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Dọn dẹp thành công! Đã xóa tổng %d chuyên mục.'), $total_deleted)
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Không có chuyên mục nào cần xóa!')
        ]));
    }
}


if ($_POST['action'] == 'removeCategoryBlog') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `post_category` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID chuyên mục không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("post_category", " `id` = ? ", [$id]);
    if ($isRemove) {
        unlink("../../" . $row['icon']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chuyên mục bài viết') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chuyên mục bài viết') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa chuyên mục thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removePost') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `posts` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bài viết không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("posts", " `id` = ? ", [$id]);
    if ($isRemove) {
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá bài viết') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá bài viết') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa bài viết thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeBank') {
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `banks` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("banks", " `id` = ? ", [$id]);
    if ($isRemove) {
        // XÓA LOGO BANK
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá ngân hàng') . ' (' . $row['short_name'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá ngân hàng') . ' (' . $row['short_name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa ngân hàng thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    if ($row['lang_default'] == 1) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('You cannot delete the system default language')
        ]);
        die($data);
    }
    $CMSNT->remove("translate", " `lang_id` = ? ", [$row['id']]);
    $isRemove = $CMSNT->remove("languages", " `id` = ? ", [$id]);
    if ($isRemove) {
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá ngôn ngữ') . ' (' . $row['lang'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá ngôn ngữ') . ' (' . $row['lang'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Successful language removal')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeCurrency') {
    if (checkPermission($getUser['admin'], 'edit_currency') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("currencies", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá tiền tệ') . ' (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá tiền tệ') . ' (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa item thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeWithdraw') {
    if (checkPermission($getUser['admin'], 'edit_withdraw_affiliate') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không được để trống')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `aff_withdraw` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('ID item không tồn tại trong hệ thống')
        ]);
        die($data);
    }
    $isRemove = $CMSNT->remove("aff_withdraw", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá yêu cầu rút tiền hoa hồng') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status']
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá yêu cầu rút tiền hoa hồng') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status'], $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xoá thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeCtvWithdraw') {
    if (checkPermission($getUser['admin'], 'edit_withdraw_ctv') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không được để trống')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `ctv_withdraw` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('ID item không tồn tại trong hệ thống')
        ]);
        die($data);
    }
    $isRemove = $CMSNT->remove("ctv_withdraw", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá yêu cầu rút tiền CTV') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status']
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá yêu cầu rút tiền CTV') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status'], $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xoá thành công')
        ]);
        die($data);
    } else {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('Xoá thất bại')
        ]);
        die($data);
    }
}

// Xử lý xóa sản phẩm CTV
if ($_POST['action'] == 'removeCtvProduct') {
    if (checkPermission($getUser['admin'], 'delete_product_ctv') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $product_id = validate_int($_POST['product_id'], 1);
    if ($product_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$product_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại trong hệ thống')]));
    }

    // Kiểm tra xem sản phẩm có thuộc về CTV không
    $ctv = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ? AND `ctv` = 1", [$product['user_id']]);
    if (!$ctv) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không thuộc về CTV')]));
    }

    // Kiểm tra xem sản phẩm có đơn hàng nào không
    $orderCount = $CMSNT->num_rows_safe("SELECT * FROM `product_order` WHERE `product_id` = ?", [$product_id]);
    if ($orderCount > 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa sản phẩm đã có đơn hàng. Vui lòng từ chối thay vì xóa.')]));
    }

    // Xóa sản phẩm khỏi database
    $isDelete = $CMSNT->remove("products", " `id` = ?", [$product_id]);

    if ($isDelete) {
        // Xóa các dữ liệu liên quan
        $CMSNT->remove("product_stock", " `product_code` = ? ", [$product['code']]);
        $CMSNT->remove("product_die", " `product_code` = ? ", [$product['code']]);
        // $CMSNT->remove("product_sold", " `product_code` = '".$product['code']."' "); // Xóa tài khoản đã bán

        // Delete product images from server
        if (!empty($product['images'])) {
            $images = explode(PHP_EOL, trim($product['images']));
            foreach ($images as $filename) {
                $filename = trim($filename);
                if (!empty($filename)) {
                    $file_path = "../../" . dirImageProduct($filename);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
        }

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin xóa sản phẩm CTV') . ': ' . $product['name'] . ' (ID: ' . $product_id . ')'
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Xóa sản phẩm thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa sản phẩm')]));
    }
}

// Xử lý xóa nhiều sản phẩm CTV cùng lúc
if ($_POST['action'] == 'bulkRemoveCtvProducts') {
    if (checkPermission($getUser['admin'], 'delete_product_ctv') != true) {
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
    $removed_count = 0;
    $error_count = 0;
    $product_names = [];

    foreach ($product_ids as $product_id) {
        if ($product_id <= 0) continue;

        // Kiểm tra sản phẩm có tồn tại không
        if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$product_id])) {
            $error_count++;
            continue;
        }

        // Kiểm tra xem sản phẩm có thuộc về CTV không
        $ctv = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ? AND `ctv` = 1", [$product['user_id']]);
        if (!$ctv) {
            $error_count++;
            continue;
        }

        // Kiểm tra xem sản phẩm có đơn hàng nào không
        $orderCount = $CMSNT->num_rows_safe("SELECT * FROM `product_order` WHERE `product_id` = ?", [$product_id]);
        if ($orderCount > 0) {
            $error_count++;
            continue; // Bỏ qua sản phẩm có đơn hàng
        }

        // Xóa sản phẩm khỏi database
        $isDelete = $CMSNT->remove("products", " `id` = ?", [$product_id]);

        if ($isDelete) {

            // Delete product images from server
            if (!empty($product['images'])) {
                $images = explode(PHP_EOL, trim($product['images']));
                foreach ($images as $filename) {
                    $filename = trim($filename);
                    if (!empty($filename)) {
                        $file_path = "../../" . dirImageProduct($filename);
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                }
            }

            $product_names[] = $product['name'];
            $removed_count++;
        } else {
            $error_count++;
        }
    }

    if ($removed_count > 0) {
        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin xóa hàng loạt sản phẩm CTV') . ': ' . $removed_count . ' sản phẩm (' .
                implode(', ', array_slice($product_names, 0, 5)) .
                (count($product_names) > 5 ? '...' : '') . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin xóa hàng loạt sản phẩm CTV') . ': ' . $removed_count . ' sản phẩm', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        $success_msg = __('Đã xóa thành công') . ' ' . $removed_count . ' ' . __('sản phẩm');
        if ($error_count > 0) {
            $success_msg .= ' (' . $error_count . ' ' . __('sản phẩm không thể xóa') . ')';
        }

        die(json_encode(['status' => 'success', 'msg' => $success_msg]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được xóa')]));
    }
}

if ($_POST['action'] == 'removeUser') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không được để trống')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => 'ID user không tồn tại trong hệ thống'
        ]);
        die($data);
    }
    if ($getUser['admin'] != 99999 && $row['admin'] == 99999) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isRemove = $CMSNT->remove("users", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá tài khoản') . ' (' . $row['username'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá tài khoản') . ' (' . $row['username'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa người dùng thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `translate` WHERE `id` = ? ", [$id]);
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    // $isRemove = $CMSNT->remove("translate", " `value` = '".$row['value']."' ");
    $isRemove = $CMSNT->remove("translate", " `id` = ? ", [$id]);
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá nội dung ngôn ngữ') . ' (' . $row['value'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá nội dung ngôn ngữ') . ' (' . $row['value'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Language removal successful')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'email_campaigns') {
    if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `email_campaigns` WHERE `id` = ? ", [$id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("email_campaigns", " `id` = ? ", [$id]);
    if ($isRemove) {
        $CMSNT->remove('email_sending', " `camp_id` = ? ", [$row['id']]);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chiến dịch Email Marketing') . ' (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chiến dịch Email Marketing') . ' (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa item thành công')
        ]);
        die($data);
    }
}


// Xóa nhiều chuyên mục con cùng lúc
if ($_POST['action'] == 'bulk_remove_categories') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    if (empty($_POST['category_ids']) || !is_array($_POST['category_ids'])) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng chọn ít nhất một chuyên mục']));
    }

    $category_ids = array_map('intval', $_POST['category_ids']);
    $removed_count = 0;
    $error_count = 0;
    $category_names = [];

    foreach ($category_ids as $id) {
        if ($id > 0) {
            if (!$row = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id])) {
                $error_count++;
                continue;
            }

            // Kiểm tra nếu là chuyên mục cha có chuyên mục con
            if ($row['parent_id'] == 0) {
                if ($CMSNT->num_rows_safe("SELECT * FROM `categories` WHERE `parent_id` = ?", [$id]) != 0) {
                    $error_count++;
                    continue; // Bỏ qua chuyên mục cha có con
                }
            }

            $isRemove = $CMSNT->remove("categories", " `id` = ? ", [$id]);
            if ($isRemove) {
                // Xóa icon file nếu có
                if (!empty($row['icon'])) {
                    $iconPath = "../../" . $row['icon'];
                    if (file_exists($iconPath)) {
                        unlink($iconPath);
                    }
                }
                $category_names[] = $row['name'];
                $removed_count++;
            } else {
                $error_count++;
            }
        }
    }

    if ($removed_count > 0) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Xóa hàng loạt $removed_count chuyên mục: " . implode(', ', array_slice($category_names, 0, 3)) . (count($category_names) > 3 ? '...' : '')
        ]);

        $success_msg = "Đã xóa thành công $removed_count chuyên mục";
        if ($error_count > 0) {
            $success_msg .= " ($error_count chuyên mục không thể xóa)";
        }

        die(json_encode(['status' => 'success', 'msg' => $success_msg]));
    }

    die(json_encode(['status' => 'error', 'msg' => 'Không có chuyên mục nào được xóa']));
}

// Xử lý dọn dẹp nhật ký Telegram
if ($_POST['action'] == 'cleanupTelegramLogs') {
    if (checkPermission($getUser['admin'], 'delete_telegram_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn (giữ lại logs từ X ngày trở lại)
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng log sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `telegram_logs` WHERE `time` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có nhật ký Telegram nào cần xóa')]));
    }

    // Xóa các log cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("telegram_logs", " `time` < ? ", [$cutoff_date]);

    if ($isRemove) {
        // Đếm lại số lượng log sau khi xóa
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `telegram_logs` WHERE `time` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp nhật ký Telegram') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên (trước ' . $cutoff_date . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp nhật ký Telegram') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('nhật ký Telegram từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa nhật ký Telegram')]));
    }
}

// Xử lý dọn dẹp nhật ký ngân hàng
if ($_POST['action'] == 'cleanupBankLogs') {
    if (checkPermission($getUser['admin'], 'delete_bank_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn (giữ lại logs từ X ngày trở lại)
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng log sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `log_bank_auto` WHERE `create_gettime` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có nhật ký ngân hàng nào cần xóa')]));
    }

    // Xóa các log cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("log_bank_auto", " `create_gettime` < ? ", [$cutoff_date]);

    if ($isRemove) {
        // Đếm lại số lượng log sau khi xóa
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `log_bank_auto` WHERE `create_gettime` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp nhật ký ngân hàng') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên (trước ' . $cutoff_date . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp nhật ký ngân hàng') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('nhật ký ngân hàng từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa nhật ký ngân hàng')]));
    }
}

// Xử lý dọn dẹp lịch sử nạp tiền Bank
if ($_POST['action'] == 'cleanupRechargeBank') {
    if (checkPermission($getUser['admin'], 'delete_recharge') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `payment_bank` WHERE `create_gettime` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền Bank nào cần xóa')]));
    }

    // Xóa các bản ghi cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("payment_bank", " `create_gettime` < ? ", [$cutoff_date]);

    if ($isRemove) {
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `payment_bank` WHERE `create_gettime` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp lịch sử nạp tiền Bank') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp lịch sử nạp tiền Bank') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('lịch sử nạp tiền Bank từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa lịch sử nạp tiền Bank')]));
    }
}

// Xử lý dọn dẹp lịch sử nạp tiền Card
if ($_POST['action'] == 'cleanupRechargeCard') {
    if (checkPermission($getUser['admin'], 'delete_recharge') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `cards` WHERE `create_date` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền Card nào cần xóa')]));
    }

    // Xóa các bản ghi cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("cards", " `create_date` < ? ", [$cutoff_date]);

    if ($isRemove) {
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `cards` WHERE `create_date` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp lịch sử nạp tiền Card') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp lịch sử nạp tiền Card') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('lịch sử nạp tiền Card từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa lịch sử nạp tiền Card')]));
    }
}

// Xử lý dọn dẹp lịch sử nạp tiền Crypto
if ($_POST['action'] == 'cleanupRechargeCrypto') {
    if (checkPermission($getUser['admin'], 'delete_recharge') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `payment_crypto` WHERE `create_gettime` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có lịch sử nạp tiền Crypto nào cần xóa')]));
    }

    // Xóa các bản ghi cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("payment_crypto", " `create_gettime` < ? ", [$cutoff_date]);

    if ($isRemove) {
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `payment_crypto` WHERE `create_gettime` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp lịch sử nạp tiền Crypto') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp lịch sử nạp tiền Crypto') . ': Xóa ' . $deleted_count . ' bản ghi từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('lịch sử nạp tiền Crypto từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa lịch sử nạp tiền Crypto')]));
    }
}

// Xử lý dọn dẹp nhật ký số dư
if ($_POST['action'] == 'cleanupTransactions') {
    if (checkPermission($getUser['admin'], 'delete_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1);

    if ($days_to_keep === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn (giữ lại logs từ X ngày trở lại)
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng log sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `dongtien` WHERE `thoigian` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có nhật ký số dư nào cần xóa')]));
    }

    // Xóa các log cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("dongtien", " `thoigian` < ? ", [$cutoff_date]);

    if ($isRemove) {
        // Đếm lại số lượng log sau khi xóa
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `dongtien` WHERE `thoigian` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp nhật ký số dư') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên (trước ' . $cutoff_date . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp nhật ký số dư') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('nhật ký số dư từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa nhật ký số dư')]));
    }
}

// Xử lý dọn dẹp nhật ký hoạt động
if ($_POST['action'] == 'cleanupLogs') {
    if (checkPermission($getUser['admin'], 'delete_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['days_to_keep'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số ngày cần giữ lại')]));
    }

    $days_to_keep = validate_int($_POST['days_to_keep'], 1) ?: intval($_POST['days_to_keep']);

    if ($days_to_keep < 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày phải lớn hơn 0')]));
    }

    // Tính toán ngày giới hạn (giữ lại logs từ X ngày trở lại)
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

    // Đếm số lượng log sẽ bị xóa
    $count_before = $CMSNT->num_rows_safe("SELECT * FROM `logs` WHERE `createdate` < ? ", [$cutoff_date]);

    if ($count_before == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có nhật ký nào cần xóa')]));
    }

    // Xóa các log cũ hơn số ngày được chỉ định
    $isRemove = $CMSNT->remove("logs", " `createdate` < ? ", [$cutoff_date]);

    if ($isRemove) {
        // Đếm lại số lượng log sau khi xóa
        $count_after = $CMSNT->num_rows_safe("SELECT * FROM `logs` WHERE `createdate` < ? ", [$cutoff_date]);
        $deleted_count = $count_before - $count_after;

        // Log hành động
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('Admin dọn dẹp nhật ký hoạt động') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên (trước ' . $cutoff_date . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Admin dọn dẹp nhật ký hoạt động') . ': Xóa ' . $deleted_count . ' nhật ký từ ' . $days_to_keep . ' ngày trở lên', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('nhật ký từ') . ' ' . $days_to_keep . ' ' . __('ngày trở lên')
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa nhật ký')]));
    }
}

// Dọn dẹp đơn hàng theo thời gian
if ($_POST['action'] == 'cleanupOrders') {
    if (checkPermission($getUser['admin'], 'delete_orders_product') != true) {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $days_to_keep = intval($_POST['days_to_keep']);
    $cleanup_type = isset($_POST['cleanup_type']) && in_array($_POST['cleanup_type'], ['delete_order_revenue', 'delete_order_only', 'delete_order_not_uid', 'delete_order']) ? $_POST['cleanup_type'] : '';

    if ($days_to_keep < 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày giữ lại phải lớn hơn 0')]));
    }

    // Tính số giây tương ứng với số ngày
    $schedule = $days_to_keep * 24 * 60 * 60;
    $orders_deleted = 0;
    $accounts_deleted = 0;

    switch ($cleanup_type) {
        case 'delete_order_revenue':
            // Xóa toàn bộ đơn hàng và tài khoản (xóa hoàn toàn)
            $orders_count = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");
            $accounts_count = $CMSNT->num_rows(" SELECT * FROM `product_sold` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $CMSNT->remove('product_order', " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");
            $CMSNT->remove('product_sold', " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $orders_deleted = $orders_count;
            $accounts_deleted = $accounts_count;
            $action_text = 'Xóa toàn bộ đơn hàng và tài khoản';
            break;

        case 'delete_order_only':
            // Ẩn đơn hàng, không xóa tài khoản
            $orders_count = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " AND `trash` = 0 ");

            $CMSNT->update('product_order', [
                'trash' => 1
            ], " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $orders_deleted = $orders_count;
            $action_text = 'Ẩn đơn hàng, giữ nguyên tài khoản';
            break;

        case 'delete_order_not_uid':
            // Ẩn đơn hàng, xóa thông tin tài khoản nhưng giữ lại UID
            $orders_count = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " AND `trash` = 0 ");
            $accounts_count = $CMSNT->num_rows(" SELECT * FROM `product_sold` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $CMSNT->update('product_order', [
                'trash' => 1
            ], " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $CMSNT->update('product_sold', [
                'account' => __('Tài khoản đã được xóa tự động')
            ], " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $orders_deleted = $orders_count;
            $accounts_deleted = $accounts_count;
            $action_text = 'Ẩn đơn hàng, xóa tài khoản giữ lại UID';
            break;

        case 'delete_order':
            // Ẩn đơn hàng, xóa toàn bộ tài khoản
            $orders_count = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " AND `trash` = 0 ");
            $accounts_count = $CMSNT->num_rows(" SELECT * FROM `product_sold` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $CMSNT->update('product_order', [
                'trash' => 1
            ], " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $CMSNT->remove('product_sold', " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

            $orders_deleted = $orders_count;
            $accounts_deleted = $accounts_count;
            $action_text = 'Ẩn đơn hàng, xóa toàn bộ tài khoản';
            break;

        default:
            die(json_encode(['status' => 'error', 'msg' => __('Loại dọn dẹp không hợp lệ')]));
    }

    // Ghi log
    $CMSNT->insert("logs", [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Dọn dẹp đơn hàng') . ': ' . $action_text . ' - ' . $orders_deleted . ' đơn hàng, ' . $accounts_deleted . ' tài khoản (từ ' . $days_to_keep . ' ngày trở lên)'
    ]);

    // Gửi thông báo Telegram
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Dọn dẹp đơn hàng') . ': ' . $action_text . ' - ' . $orders_deleted . ' đơn hàng, ' . $accounts_deleted . ' tài khoản', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Đã dọn dẹp thành công') . ': ' . format_cash($orders_deleted) . ' ' . __('đơn hàng') . ', ' . format_cash($accounts_deleted) . ' ' . __('tài khoản')
    ]));
}

// Dọn dẹp Email Queue
if ($_POST['action'] == 'cleanupEmailQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = intval($_POST['days']);

    if ($days == 0) {
        // Xóa toàn bộ
        $count = $CMSNT->num_rows(" SELECT id FROM `email_queue` WHERE 1 ");
        if ($count == 0) {
            die(json_encode(['status' => 'error', 'msg' => __('Không có bản ghi nào để xóa')]));
        }
        $CMSNT->remove("email_queue", " 1 ");

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Dọn dẹp Email Queue') . ' (' . __('Xóa toàn bộ') . ' ' . $count . ' ' . __('bản ghi') . ')'
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . format_cash($count) . ' ' . __('bản ghi')
        ]));
    }

    if ($days < 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày không hợp lệ')]));
    }
    $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
    $count = $CMSNT->num_rows_safe(" SELECT id FROM `email_queue` WHERE `created_at` < ? ", [$cutoff]);
    $CMSNT->remove("email_queue", " `created_at` < ? ", [$cutoff]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Dọn dẹp Email Queue') . ' (' . $count . ' ' . __('bản ghi cũ hơn') . ' ' . $days . ' ' . __('ngày') . ')'
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Đã xóa thành công') . ' ' . format_cash($count) . ' ' . __('bản ghi')
    ]));
}

// Dọn dẹp Telegram Queue
if ($_POST['action'] == 'cleanupTelegramQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = intval($_POST['days']);

    if ($days == 0) {
        // Xóa toàn bộ
        $count = $CMSNT->num_rows(" SELECT id FROM `telegram_queue` WHERE 1 ");
        if ($count == 0) {
            die(json_encode(['status' => 'error', 'msg' => __('Không có bản ghi nào để xóa')]));
        }
        $CMSNT->remove("telegram_queue", " 1 ");

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Dọn dẹp Telegram Queue') . ' (' . __('Xóa toàn bộ') . ' ' . $count . ' ' . __('bản ghi') . ')'
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . format_cash($count) . ' ' . __('bản ghi')
        ]));
    }

    if ($days < 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày không hợp lệ')]));
    }
    $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
    $count = $CMSNT->num_rows_safe(" SELECT id FROM `telegram_queue` WHERE `created_at` < ? ", [$cutoff]);
    $CMSNT->remove("telegram_queue", " `created_at` < ? ", [$cutoff]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Dọn dẹp Telegram Queue') . ' (' . $count . ' ' . __('bản ghi cũ hơn') . ' ' . $days . ' ' . __('ngày') . ')'
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Đã xóa thành công') . ' ' . format_cash($count) . ' ' . __('bản ghi')
    ]));
}

if ($_POST['action'] == 'bulk_delete_translates') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'] ?? '', 255);
    if (!$token || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $deletedCount = 0;
    foreach ($_POST['ids'] as $id) {
        $id = intval($id);
        if ($id > 0) {
            $isRemove = $CMSNT->remove("translate", " `id` = ? ", [$id]);
            if ($isRemove) {
                $deletedCount++;
            }
        }
    }

    if ($deletedCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Xóa hàng loạt " . $deletedCount . " bản dịch"
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Đã xóa thành công') . ' ' . $deletedCount . ' ' . __('bản dịch đã chọn')
        ]));
    }

    die(json_encode([
        'status'    => 'error',
        'msg'       => __('Không có dữ liệu nào được xóa')
    ]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => 'Dữ liệu không hợp lệ'
]));
