
<?php
define("IN_SITE", true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . '/../../models/is_ctv.php');
$CMSNT = new DB();

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
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}

if ($CMSNT->site('ctv_status') != 1) {
    die(json_encode(['status' => 'error', 'msg' => __('CTV Panel đang bị tắt trong cài đặt hệ thống.')]));
}

// Cập nhật trạng thái sản phẩm (hiển thị/ẩn)
if ($_POST['action'] == 'update_status_product') {
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không tồn tại')]));
    }

    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }

    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 0;
    if ($status === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Trạng thái không hợp lệ')]));
    }

    // Kiểm tra sản phẩm tồn tại và thuộc về CTV
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `user_id` = ?", [$id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm này không thuộc về bạn')]));
    }

    // Không cho phép cập nhật nếu sản phẩm đang chờ duyệt
    if ($product['pending'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm đang chờ duyệt, không thể thay đổi trạng thái')]));
    }

    // Cập nhật trạng thái
    $isUpdate = $CMSNT->update("products", [
        'status' => $status,
        'update_gettime' => gettime()
    ], " `id` = ?", [$id]);

    if ($isUpdate) {
        // Ghi log
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('CTV cập nhật trạng thái sản phẩm:') . ' ' . ($status == 1 ? __('Hiển thị') : __('Ẩn')) . ' (ID: ' . $id . ', ' . $product['name'] . ')'
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái sản phẩm thành công')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể cập nhật trạng thái sản phẩm')]));
    }
}

if ($_POST['action'] == 'refundOrder') {
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không tồn tại')]));
    }

    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }

    if (!$product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? AND `seller` = ?", [$id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    if ($product_order['refund'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng này đã được hoàn tiền rồi')]));
    }
    if ($product_order['amount'] == 0 || $product_order['pay'] == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng này đã được hoàn tiền rồi')]));
    }
    $reason = isset($_POST['reason']) ? validate_string($_POST['reason'], 500) : '';
    if ($reason === false && !empty($_POST['reason'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Lý do hoàn tiền quá dài')]));
    }

    // Bắt đầu transaction tổng thể để đảm bảo tính nhất quán
    $CMSNT->query("START TRANSACTION");

    try {
        // Lấy kiểu hoàn tiền (full/partial)
        $refundType = validate_string($_POST['refundType'], 20);
        if ($refundType === false || !in_array($refundType, ['full', 'partial'])) {
            throw new Exception(__('Kiểu hoàn tiền không hợp lệ'));
        }
        $User = new users();
        $success = false;

        if ($refundType == 'partial') {
            $reason = 'Hoàn tiền một phần đơn hàng #' . $product_order['trans_id'] . ' (' . $reason . ')';
            // Lấy số lượng cần hoàn (nếu có)
            $partialQuantity = isset($_POST['partialQuantity']) ? validate_int($_POST['partialQuantity'], 1) : 0;
            if ($partialQuantity === false) {
                throw new Exception(__('Số lượng tài khoản cần hoàn không hợp lệ.'));
            }
            if ($partialQuantity > $product_order['amount']) {
                throw new Exception(__('Số lượng tài khoản cần hoàn vượt quá số lượng tài khoản của đơn hàng này.'));
            }
            if ($partialQuantity <= 0) {
                throw new Exception(__('Vui lòng nhập số lượng tài khoản cần hoàn.'));
            }
            $rate = $product_order['pay'] / $product_order['amount'];
            // Tổng số tiền Refund
            $amountRefund = $partialQuantity * $rate;
            if (getRowRealtime('users', $getUser['id'], 'money') < $amountRefund) {
                throw new Exception(__('Số dư khả dụng của bạn không đủ để hoàn lại cho người mua'));
            }

            $leftover = $product_order['amount'] - $partialQuantity;
            $commission_fee = 0;

            // 1. Thu hồi hoa hồng TRƯỚC (nếu có)
            $user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$product_order['buyer']]);
            if ($user['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $user['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $user['ref_id'], 'ref_ck');
                }
                $commission_fee = $amountRefund * $ck / 100;
                $removeCommission = $User->RemoveCommission($user['ref_id'], $commission_fee, __('[CTV] Thu hồi đơn hoàn tiền' . ' ' . $user['username']));
                if (!$removeCommission) {
                    throw new Exception(__('Không thể thu hồi hoa hồng'));
                }
            }

            // 2. Thu hồi tiền người bán TRƯỚC (nếu được cấu hình)
            if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                $removeSeller = $User->RemoveCredits($product_order['seller'], $amountRefund, __('[CTV] Thu hồi hoàn tiền một phần đơn hàng') . ' #' . $product_order['trans_id'], 'TAKE_REFUND_partial_ORDER_' . $product_order['trans_id'] . '_' . time());
                if (!$removeSeller) {
                    throw new Exception(__('Không thể thu hồi tiền người bán'));
                }
            }

            // 3. Hoàn tiền cho người mua SAU
            $isRefund = $User->RefundCredits($product_order['buyer'], $amountRefund, "[CTV] $reason", 'REFUND_partial_ORDER_' . $product_order['trans_id'] . '_' . time());
            if (!$isRefund) {
                throw new Exception(__('Không thể hoàn tiền cho người mua'));
            }

            // 4. Cập nhật đơn hàng
            $updateOrder = $CMSNT->update('product_order', [
                'pay'    => $product_order['pay'] - $amountRefund,
                'cost'   => ($product_order['cost'] / $product_order['amount']) * $leftover,
                'money'  => ($product_order['money'] / $product_order['amount']) * $leftover,
                'amount' => $leftover
            ], " `id` = ?", [$id]);
            if (!$updateOrder) {
                throw new Exception(__('Không thể cập nhật đơn hàng'));
            }

            // 5. Ghi log
            $insertLog = $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('[CTV] Hoàn tiền một phần đơn hàng') . ' (' . $product_order['trans_id'] . ')'
            ]);
            if (!$insertLog) {
                throw new Exception(__('Không thể ghi log'));
            }

            $success = true;
        } else {
            // HOÀN TIỀN TOÀN BỘ
            $reason = 'Hoàn tiền đơn hàng #' . $product_order['trans_id'] . ' (' . $reason . ')';

            // Kiểm tra số dư người bán trước khi hoàn tiền
            if (getRowRealtime('users', $getUser['id'], 'money') < $product_order['pay']) {
                throw new Exception(__('Số dư khả dụng của bạn không đủ để hoàn lại cho người mua'));
            }

            $commission_fee = 0;

            // 1. Thu hồi hoa hồng TRƯỚC (nếu có)
            $user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$product_order['buyer']]);
            if ($user['ref_id'] != 0) {
                $ck = $CMSNT->site('affiliate_ck');
                if (getRowRealtime('users', $user['ref_id'], 'ref_ck') != 0) {
                    $ck = getRowRealtime('users', $user['ref_id'], 'ref_ck');
                }
                $commission_fee = $product_order['pay'] * $ck / 100;
                $removeCommission = $User->RemoveCommission($user['ref_id'], $commission_fee, __('[CTV] Thu hồi đơn hoàn tiền' . ' ' . $user['username']));
                if (!$removeCommission) {
                    throw new Exception(__('Không thể thu hồi hoa hồng'));
                }
            }

            // 2. Thu hồi tiền người bán TRƯỚC (nếu được cấu hình)
            if ($CMSNT->site('cong_tien_nguoi_ban') == 1) {
                $removeSeller = $User->RemoveCredits($product_order['seller'], $product_order['pay'], __('[CTV] Thu hồi hoàn tiền đơn hàng') . ' #' . $product_order['trans_id'], 'TAKE_REFUND_ORDER_' . $product_order['trans_id']);
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
            ], " `id` = ?", [$id]);
            if (!$updateOrder) {
                throw new Exception(__('Không thể cập nhật đơn hàng'));
            }

            // 5. Ghi log
            $insertLog = $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => __('[CTV] Hoàn tiền đơn hàng') . ' (' . $product_order['trans_id'] . ')'
            ]);
            if (!$insertLog) {
                throw new Exception(__('Không thể ghi log'));
            }

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

if ($_POST['action'] == 'update_settings') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die(json_encode(['status' => 'error', 'msg' => __('Token bảo mật không hợp lệ, vui lòng tải lại trang')]));
    }

    $telegram_chat_id = isset($_POST['telegram_chat_id']) ? trim($_POST['telegram_chat_id']) : '';
    if ($telegram_chat_id !== '') {
        $telegram_chat_id = validate_string($telegram_chat_id, 50);
        if ($telegram_chat_id === false) {
            die(json_encode(['status' => 'error', 'msg' => __('Chat ID Telegram không hợp lệ')]));
        }
    }
    $chat_id_value = ($telegram_chat_id !== '') ? $telegram_chat_id : null;

    $isUpdate = $CMSNT->update('users', [
        'telegram_chat_id' => $chat_id_value
    ], "`id` = ?", [$getUser['id']]);

    if ($isUpdate) {
        // Ghi log
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => __('CTV cập nhật cấu hình Telegram')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Lưu cấu hình thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không có dữ liệu cần thay đổi')]));
    }
}
