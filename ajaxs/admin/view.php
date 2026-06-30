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
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}


// Lấy lịch sử hoàn tiền của đơn hàng từ bảng dongtien, tìm theo transid có prefix REFUND hoặc TAKE_REFUND
if ($_POST['action'] == 'get_refund_history') {
    if (checkPermission($getUser['admin'], 'refund_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : false;
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }
    $product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ?", [$id]);
    if (!$product_order) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại')]));
    }
    $trans_id = $product_order['trans_id'];

    // Tìm tất cả bản ghi dongtien liên quan đến hoàn tiền hoặc thu hồi của đơn hàng này
    // Các prefix: REFUND_ORDER_, REFUND_partial_ORDER_, REFUND_percent_ORDER_, TAKE_REFUND_*ORDER_
    // Escape các ký tự đặc biệt LIKE (%, _) trong trans_id để tránh false match
    $safe_trans_id = str_replace(['%', '_'], ['\\%', '\\_'], $trans_id);
    $pattern = '%REFUND%ORDER\\_' . $safe_trans_id . '%';
    $rows = $CMSNT->get_list_safe(
        "SELECT `id`, `user_id`, `sotientruoc`, `sotienthaydoi`, `sotiensau`, `noidung`, `transid`, `thoigian` FROM `dongtien` WHERE `transid` LIKE ? ORDER BY `id` DESC",
        [$pattern]
    );

    $history = [];
    foreach ($rows as $row) {
        // Xác định loại giao dịch dựa vào prefix transid
        $type = 'refund'; // Hoàn tiền cho người mua (mặc định)
        if (strpos($row['transid'], 'TAKE_REFUND') === 0) {
            $type = 'take_seller'; // Thu hồi tiền seller
        }

        // Lấy username để hiển thị
        $userRow = $CMSNT->get_row_safe("SELECT `username` FROM `users` WHERE `id` = ?", [$row['user_id']]);
        $username = $userRow ? $userRow['username'] : __('Không xác định');

        // Xác định đây là cộng (+) hay trừ (-) dựa vào số dư trước/sau
        $is_increase = ($row['sotiensau'] - $row['sotientruoc']) > 0;

        $history[] = [
            'id'            => $row['id'],
            'type'          => $type,
            'user_id'       => $row['user_id'],
            'username'      => $username,
            'amount'        => $row['sotienthaydoi'],
            'amount_fmt'    => format_currency($row['sotienthaydoi']),
            'before_fmt'    => format_currency($row['sotientruoc']),
            'after_fmt'     => format_currency($row['sotiensau']),
            'is_increase'   => $is_increase,
            'content'       => htmlspecialchars($row['noidung'], ENT_QUOTES, 'UTF-8'),
            'transid'       => htmlspecialchars($row['transid'], ENT_QUOTES, 'UTF-8'),
            'time'          => $row['thoigian']
        ];
    }

    die(json_encode([
        'status'  => 'success',
        'history' => $history,
        'total'   => count($history)
    ]));
}

// Lấy toàn bộ biến động số dư liên quan đến đơn hàng (mua, doanh thu, hoàn tiền, thu hồi, hoa hồng)
if ($_POST['action'] == 'get_order_balance_history') {
    if (checkPermission($getUser['admin'], 'orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : false;
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }
    $product_order = $CMSNT->get_row_safe("SELECT `id`, `trans_id` FROM `product_order` WHERE `id` = ?", [$id]);
    if (!$product_order) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại')]));
    }
    $trans_id = $product_order['trans_id'];

    // Escape ký tự đặc biệt LIKE trong trans_id để tránh false match
    $safe_trans_id = str_replace(['%', '_'], ['\\%', '\\_'], $trans_id);

    // Tìm tất cả giao dịch dongtien có transid chứa trans_id của đơn hàng
    // Bao gồm: ORDER_, DOANH_THU_, REFUND_, REFUND_ORDER_, REFUND_partial_ORDER_,
    //           REFUND_percent_ORDER_, TAKE_REFUND_*, COMMISSION_
    $exact_transids = [
        'ORDER_' . $trans_id,
        'DOANH_THU_' . $trans_id,
        'REFUND_' . $trans_id,
        'REFUND_ORDER_' . $trans_id,
        'COMMISSION_' . $trans_id,
    ];
    $placeholders = str_repeat('?,', count($exact_transids) - 1) . '?';

    // LIKE patterns cho các giao dịch có timestamp suffix
    $like_refund_partial = 'REFUND\\_partial\\_ORDER\\_' . $safe_trans_id . '%';
    $like_refund_percent = 'REFUND\\_percent\\_ORDER\\_' . $safe_trans_id . '%';
    $like_take_refund = 'TAKE\\_REFUND%ORDER\\_' . $safe_trans_id . '%';

    $sql = "SELECT `id`, `user_id`, `sotientruoc`, `sotienthaydoi`, `sotiensau`, `noidung`, `transid`, `thoigian`
            FROM `dongtien`
            WHERE `transid` IN ($placeholders)
               OR `transid` LIKE ?
               OR `transid` LIKE ?
               OR `transid` LIKE ?
            ORDER BY `id` DESC";

    $params = array_merge($exact_transids, [$like_refund_partial, $like_refund_percent, $like_take_refund]);
    $rows = $CMSNT->get_list_safe($sql, $params);

    // Cache username để tránh query lặp lại cho cùng user
    $user_cache = [];
    $history = [];
    foreach ($rows as $row) {
        // Phân loại giao dịch dựa vào prefix transid
        $transid = $row['transid'];
        if (strpos($transid, 'TAKE_REFUND') === 0) {
            $type = 'take_refund';
        } elseif (strpos($transid, 'REFUND') === 0) {
            $type = 'refund';
        } elseif (strpos($transid, 'DOANH_THU_') === 0) {
            $type = 'revenue';
        } elseif (strpos($transid, 'COMMISSION_') === 0) {
            $type = 'commission';
        } elseif (strpos($transid, 'ORDER_') === 0) {
            $type = 'purchase';
        } else {
            $type = 'other';
        }

        // Lấy username, dùng cache để giảm query
        $uid = $row['user_id'];
        if (!isset($user_cache[$uid])) {
            $userRow = $CMSNT->get_row_safe("SELECT `username` FROM `users` WHERE `id` = ?", [$uid]);
            $user_cache[$uid] = $userRow ? $userRow['username'] : __('Không xác định');
        }

        $is_increase = ($row['sotiensau'] - $row['sotientruoc']) > 0;

        $history[] = [
            'id'         => $row['id'],
            'type'       => $type,
            'user_id'    => $uid,
            'username'   => $user_cache[$uid],
            'amount'     => $row['sotienthaydoi'],
            'amount_fmt' => format_currency($row['sotienthaydoi']),
            'before_fmt' => format_currency($row['sotientruoc']),
            'after_fmt'  => format_currency($row['sotiensau']),
            'is_increase' => $is_increase,
            'content'    => htmlspecialchars($row['noidung'], ENT_QUOTES, 'UTF-8'),
            'transid'    => htmlspecialchars($transid, ENT_QUOTES, 'UTF-8'),
            'time'       => $row['thoigian']
        ];
    }

    die(json_encode([
        'status'  => 'success',
        'history' => $history,
        'total'   => count($history)
    ]));
}

if ($_POST['action'] == 'tinh_tien_refund') {
    if (checkPermission($getUser['admin'], 'refund_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => 'ID đơn hàng không tồn tại']));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => 'ID đơn hàng không hợp lệ']));
    }
    if (!$product_order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ?", [$id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Đơn hàng không tồn tại trong hệ thống']));
    }
    // Lấy kiểu hoàn tiền (full/partial/percent) - whitelist để tránh giá trị bất hợp lệ
    $refundType = isset($_POST['refundType']) && in_array($_POST['refundType'], ['full', 'partial', 'percent']) ? $_POST['refundType'] : 'full';
    if ($refundType == 'partial') {
        // Lấy số lượng cần hoàn (nếu có)
        $partialQuantity = isset($_POST['partialQuantity']) ? intval($_POST['partialQuantity']) : 0;
        if ($partialQuantity > $product_order['amount']) {
            die(json_encode(['status' => 'error', 'msg' => __('Số lượng tài khoản cần hoàn vượt quá số lượng tài khoản của đơn hàng này.')]));
        }
        $rate = $product_order['pay'] / $product_order['amount'];
        // Tổng số tiền Refund tính theo số tài khoản nhập vào
        $amountRefund = $partialQuantity * $rate;
        die(json_encode(['status' => 'success', 'totalRefund' => format_currency($amountRefund)]));
    } else if ($refundType == 'percent') {
        // Hoàn theo % chỉ áp dụng cho đơn có 1 tài khoản (theo nghiệp vụ chỉ định)
        if ($product_order['amount'] != 1) {
            die(json_encode(['status' => 'error', 'msg' => __('Hoàn theo % chỉ áp dụng cho đơn hàng có 1 tài khoản.')]));
        }
        // Validate phần trăm trong khoảng (0, 100], cho phép số thập phân
        $percentValue = isset($_POST['percentValue']) ? validate_float($_POST['percentValue'], 0.01, 100) : false;
        if ($percentValue === false) {
            die(json_encode(['status' => 'error', 'msg' => __('Phần trăm hoàn không hợp lệ (phải > 0 và ≤ 100)')]));
        }
        // Tính số tiền hoàn theo % của số tiền pay còn lại của đơn hàng
        $amountRefund = $product_order['pay'] * $percentValue / 100;
        die(json_encode(['status' => 'success', 'totalRefund' => format_currency($amountRefund)]));
    } else {
        die(json_encode(['status' => 'success', 'totalRefund' => format_currency($product_order['pay'])]));
    }
}
if ($_POST['action'] == 'download_product_die') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $accounts = '';
    $current_product_code = "";
    foreach ($CMSNT->get_list("SELECT * FROM `product_die` ORDER BY product_code") as $row) {

        if ($row['product_code'] != $current_product_code) {
            if ($current_product_code != "") {
                $accounts .= PHP_EOL;
            }
            $current_product_code = $row['product_code'];
            $productRow = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ?", [$current_product_code]);
            $productName = $productRow ? $productRow['name'] : $current_product_code;
            $accounts .= PHP_EOL . PHP_EOL . PHP_EOL . "============== " . $productName . " | Kho Hàng: " . $current_product_code . " ==============" . PHP_EOL;
        }
        $accounts .= $row['account'] . PHP_EOL;
    }
    $data = json_encode([
        'status'    => 'success',
        'filename'  => 'all_list_die_' . gettime(),
        'accounts'  => $accounts,
        'msg'       => __('Xuất dữ liệu thành công')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Tải toàn bộ tài khoản DIE về máy')
    ]);
    die($data);
}

// Export đơn hàng sản phẩm
if ($_POST['action'] == 'exportProductOrders') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một đơn hàng')]));
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Sanitize IDs
    $ids = array_filter(array_map('intval', $_POST['ids']));
    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }

    // Allowed columns mapping - adapted for SHOPCLONE7 product_order table
    $allowed_columns = [
        'trans_id' => ['field' => 'po.trans_id', 'label' => __('Mã đơn hàng')],
        'api_transid' => ['field' => 'po.api_transid', 'label' => __('Mã đơn API')],
        'username' => ['field' => 'u.username', 'label' => __('Username')],
        'product_name' => ['field' => 'po.product_name', 'label' => __('Sản phẩm')],
        'amount' => ['field' => 'po.amount', 'label' => __('Số lượng')],
        'pay' => ['field' => 'po.pay', 'label' => __('Thanh toán')],
        'cost' => ['field' => 'po.cost', 'label' => __('Giá vốn')],
        'create_gettime' => ['field' => 'po.create_gettime', 'label' => __('Ngày tạo')],
        'delivery_content' => ['field' => '', 'label' => __('Nội dung giao')] // Lấy từ product_sold
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause (exclude delivery_content as it's from another table)
    $select_fields = [];
    foreach ($selected_columns as $key => $col) {
        if ($key !== 'delivery_content' && !empty($col['field'])) {
            $select_fields[] = $col['field'] . ' AS `' . $key . '`';
        }
    }

    // Always get trans_id for joining with product_sold if delivery_content is selected
    $need_delivery = isset($selected_columns['delivery_content']);
    if ($need_delivery && !isset($selected_columns['trans_id'])) {
        array_unshift($select_fields, 'po.trans_id AS `_trans_id`');
    }

    // Build query with placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT " . implode(', ', $select_fields) . "
              FROM `product_order` po
              LEFT JOIN `users` u ON po.buyer = u.id
              WHERE po.id IN ($placeholders)
              ORDER BY po.id DESC";

    $orders = $CMSNT->get_list_safe($query, $ids);

    if (empty($orders)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy đơn hàng')]));
    }

    // Get delivery content from product_sold if needed
    $delivery_contents = [];
    if ($need_delivery) {
        foreach ($orders as $order) {
            $trans_id = $order['trans_id'] ?? $order['_trans_id'] ?? '';
            if ($trans_id) {
                $sold_items = $CMSNT->get_list_safe(
                    "SELECT `uid`, `account` FROM `product_sold` WHERE `trans_id` = ?",
                    [$trans_id]
                );
                $content_parts = [];
                foreach ($sold_items as $item) {
                    $content_parts[] = ($item['uid'] ? $item['uid'] . '|' : '') . $item['account'];
                }
                $delivery_contents[$trans_id] = implode(' || ', $content_parts);
            }
        }
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Data rows
    foreach ($orders as $order) {
        $row = [];
        foreach ($selected_columns as $key => $col) {
            if ($key === 'delivery_content') {
                $trans_id = $order['trans_id'] ?? $order['_trans_id'] ?? '';
                $value = $delivery_contents[$trans_id] ?? '';
            } else {
                $value = $order[$key] ?? '';
            }

            // Format specific fields
            if (in_array($key, ['pay', 'cost'])) {
                $value = number_format((float)$value, 0, ',', '.');
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $row[] = $value;
        }
        $lines[] = implode($separator, $row);
    }

    $content = implode("\n", $lines);
    $filename = 'orders_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất đơn hàng
    $log_content = sprintf(
        'Xuất %d đơn hàng (IDs: %s) - File: %s - Các cột: %s',
        count($orders),
        implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : ''),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d đơn hàng'), count($orders)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}


// ======== Export Products ========
if ($_POST['action'] == 'exportProducts') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một sản phẩm')]));
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Sanitize IDs
    $ids = array_filter(array_map('intval', $_POST['ids']));
    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }

    // Allowed columns mapping for products table
    $allowed_columns = [
        'name'           => ['field' => 'p.name', 'label' => __('Tên sản phẩm')],
        'code'           => ['field' => 'p.code', 'label' => __('Mã kho hàng')],
        'price'          => ['field' => 'p.price', 'label' => __('Giá bán')],
        'cost'           => ['field' => 'p.cost', 'label' => __('Giá vốn')],
        'category'       => ['field' => 'c.name', 'label' => __('Chuyên mục')],
        'stock_live'     => ['field' => '', 'label' => __('Tồn kho Live')], // Computed
        'sold'           => ['field' => 'p.sold', 'label' => __('Đã bán')],
        'status'         => ['field' => 'p.status', 'label' => __('Trạng thái')],
        'seller'         => ['field' => 'u.username', 'label' => __('Seller')],
        'create_gettime' => ['field' => 'p.create_gettime', 'label' => __('Ngày tạo')],
        'stock_data'     => ['field' => '', 'label' => __('Dữ liệu kho')], // Computed from product_stock - account only, 1 per line
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause
    $select_fields = ['p.id', 'p.supplier_id', 'p.code AS _code', 'p.api_stock'];
    foreach ($selected_columns as $key => $col) {
        if ($key === 'stock_live') continue; // Computed later
        if (!empty($col['field'])) {
            $alias = $key;
            if ($key === 'category') $alias = 'category_name';
            if ($key === 'seller') $alias = 'seller_name';
            $select_fields[] = $col['field'] . ' AS `' . $alias . '`';
        }
    }

    // Build query with placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT " . implode(', ', $select_fields) . "
              FROM `products` p
              LEFT JOIN `categories` c ON p.category_id = c.id
              LEFT JOIN `users` u ON p.user_id = u.id
              WHERE p.id IN ($placeholders)
              ORDER BY p.id DESC";

    $products = $CMSNT->get_list_safe($query, $ids);

    if (empty($products)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy sản phẩm')]));
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Check if stock_data column is selected
    $has_stock_data = isset($selected_columns['stock_data']);
    $exported_count = 0;

    // Tính memory limit để kiểm tra khi xuất dữ liệu lớn
    $memory_limit_str = ini_get('memory_limit');
    $memory_limit_bytes = -1;
    if ($memory_limit_str !== '-1' && $memory_limit_str !== '0') {
        $val = (int) $memory_limit_str;
        $unit = strtolower(substr(trim($memory_limit_str), -1));
        switch ($unit) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        $memory_limit_bytes = $val;
    }
    // Ngưỡng cảnh báo: 80% memory limit
    $memory_threshold = ($memory_limit_bytes > 0) ? (int)($memory_limit_bytes * 0.8) : 0;

    // Data rows
    foreach ($products as $product) {
        // Kiểm tra bộ nhớ trước mỗi sản phẩm
        if ($memory_threshold > 0 && memory_get_usage() > $memory_threshold) {
            die(json_encode([
                'status' => 'error',
                'msg' => sprintf(
                    __('Dữ liệu quá lớn, bộ nhớ sắp đầy (đã xuất %d dòng). Vui lòng chọn ít sản phẩm hơn hoặc bỏ cột "Dữ liệu kho" để xuất.'),
                    $exported_count
                )
            ]));
        }
        // Nếu có cột stock_data, lấy dữ liệu kho trước
        $stock_accounts = [];
        if ($has_stock_data) {
            $stock_items = $CMSNT->get_list_safe("SELECT `account` FROM `product_stock` WHERE `product_code` = ? ORDER BY `id` DESC", [$product['_code']]);
            if ($stock_items) {
                foreach ($stock_items as $item) {
                    if (!empty(trim($item['account']))) {
                        $stock_accounts[] = trim($item['account']);
                    }
                }
            }
            // Bỏ qua sản phẩm không có kho hàng
            if (empty($stock_accounts)) {
                continue;
            }
        }

        // Build base row values (non-stock columns)
        $base_row = [];
        foreach ($selected_columns as $key => $col) {
            if ($key === 'stock_data') {
                $base_row[$key] = ''; // placeholder, will be filled per stock entry
                continue;
            }
            $value = '';
            switch ($key) {
                case 'stock_live':
                    if ($product['supplier_id'] == 0) {
                        $value = getStock($product['_code']);
                    } else {
                        $value = $product['api_stock'] ?? 0;
                    }
                    break;
                case 'category':
                    $value = $product['category_name'] ?? '';
                    break;
                case 'seller':
                    $value = $product['seller_name'] ?? '';
                    break;
                case 'status':
                    $value = $product['status'] == 1 ? __('Hiển thị') : __('Ẩn');
                    break;
                default:
                    $value = $product[$key] ?? '';
                    break;
            }

            // Format specific fields
            if (in_array($key, ['price', 'cost'])) {
                $value = number_format((float)$value, 0, ',', '.');
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', (string)$value) . '"';
            }
            $base_row[$key] = $value;
        }

        if ($has_stock_data) {
            // Mỗi account 1 dòng
            foreach ($stock_accounts as $account) {
                $row = [];
                foreach ($selected_columns as $key => $col) {
                    if ($key === 'stock_data') {
                        $val = $account;
                        if ($file_type === 'csv') {
                            $val = '"' . str_replace('"', '""', $val) . '"';
                        }
                        $row[] = $val;
                    } else {
                        $row[] = $base_row[$key];
                    }
                }
                $lines[] = implode($separator, $row);
                $exported_count++;
            }
        } else {
            // Không có cột stock_data - xuất 1 dòng bình thường
            $row = array_values($base_row);
            $lines[] = implode($separator, $row);
            $exported_count++;
        }
    }

    $content = implode("\n", $lines);
    $filename = 'products_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất sản phẩm
    $log_content = sprintf(
        'Xuất %d sản phẩm (IDs: %s) - File: %s - Các cột: %s',
        count($products),
        implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : ''),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d sản phẩm'), count($products)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}


// ======== Export Product Stock ========
if ($_POST['action'] == 'exportProductStock') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    $is_export_all = isset($_POST['export_all']) && $_POST['export_all'] == 1;

    if (!$is_export_all && (empty($_POST['ids']) || !is_array($_POST['ids']))) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một bản ghi')]));
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Sanitize IDs
    $ids = [];
    if (!$is_export_all) {
        $ids = array_filter(array_map('intval', $_POST['ids']));
        if (empty($ids)) {
            die(json_encode(['status' => 'error', 'msg' => __('ID bản ghi không hợp lệ')]));
        }
    }

    // Allowed columns mapping for product_stock table
    $allowed_columns = [
        'uid'             => ['field' => 'ps.uid', 'label' => __('UID')],
        'account'         => ['field' => 'ps.account', 'label' => __('Tài khoản')],
        'product_code'    => ['field' => 'ps.product_code', 'label' => __('Mã kho hàng')],
        'seller'          => ['field' => 'u.username', 'label' => __('Seller')],
        'type'            => ['field' => 'ps.type', 'label' => __('Type')],
        'create_gettime'  => ['field' => 'ps.create_gettime', 'label' => __('Ngày thêm')],
        'time_check_live' => ['field' => 'ps.time_check_live', 'label' => __('Check live gần nhất')]
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause
    $select_fields = ['ps.id'];
    foreach ($selected_columns as $key => $col) {
        if (!empty($col['field'])) {
            $alias = $key;
            if ($key === 'seller') $alias = 'seller_name';
            $select_fields[] = $col['field'] . ' AS `' . $alias . '`';
        }
    }

    if ($is_export_all) {
        $query = "SELECT " . implode(', ', $select_fields) . "
                  FROM `product_stock` ps
                  LEFT JOIN `users` u ON ps.seller = u.id
                  ORDER BY ps.id DESC";
        $stocks = $CMSNT->get_list($query);
    } else {
        // Build query with placeholders
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT " . implode(', ', $select_fields) . "
                  FROM `product_stock` ps
                  LEFT JOIN `users` u ON ps.seller = u.id
                  WHERE ps.id IN ($placeholders)
                  ORDER BY ps.id DESC";

        $stocks = $CMSNT->get_list_safe($query, $ids);
    }

    if (empty($stocks)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy bản ghi nào')]));
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Data rows
    foreach ($stocks as $stock) {
        $row = [];
        foreach ($selected_columns as $key => $col) {
            $value = '';
            switch ($key) {
                case 'seller':
                    $value = $stock['seller_name'] ?? '';
                    break;
                case 'time_check_live':
                    $value = $stock['time_check_live'] > 0 ? date("H:i:s d-m-Y", $stock['time_check_live']) : '';
                    break;
                default:
                    $value = $stock[$key] ?? '';
                    break;
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', (string)$value) . '"';
            }
            $row[] = $value;
        }
        $lines[] = implode($separator, $row);
    }

    $content = implode("\n", $lines);
    $filename = 'product_stock_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất
    $log_content = sprintf(
        'Xuất %d tài khoản kho hàng (IDs: %s) - File: %s - Các cột: %s',
        count($stocks),
        implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : ''),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d tài khoản'), count($stocks)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}


if ($_POST['action'] == 'view_chart_thong_ke_don_hang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = isset($_POST['time_range']) && in_array($_POST['time_range'], ['today', 'week', 'month', 'last_month', 'year']) ? $_POST['time_range'] : 'today';
    $labels = [];
    $revenues = [];
    $profits = [];

    if ($time_range == 'today') {
        // Thống kê theo giờ trong ngày hôm nay
        $today = date("Y-m-d");
        for ($hour = 0; $hour < 24; $hour++) {
            $hour_start = sprintf("%02d:00:00", $hour);
            $hour_end = sprintf("%02d:59:59", $hour);
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order 
                      WHERE `refund` = 0 AND DATE(create_gettime) = '$today' 
                      AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'";
            $result = $CMSNT->get_row($query);

            $labels[] = sprintf("%02d:00", $hour);
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND DATE(create_gettime) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND DATE(create_gettime) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = "$day/$month";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'last_month') {
        // Thống kê theo tháng trước
        $lastMonth = date('m', strtotime('-1 month'));
        $lastMonthYear = date('Y', strtotime('-1 month'));
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $lastMonth, $lastMonthYear);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = sprintf("%s-%02d-%02d", $lastMonthYear, $lastMonth, $day);
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND DATE(create_gettime) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = "$day/$lastMonth";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order 
                      WHERE `refund` = 0 AND MONTH(create_gettime) = '$month' AND YEAR(create_gettime) = '$year'";
            $result = $CMSNT->get_row($query);

            $labels[] = "Tháng $month_name";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    }

    die(json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}


if ($_POST['action'] == 'view_chart_thong_ke_nap_tien') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = isset($_POST['time_range']) && in_array($_POST['time_range'], ['today', 'week', 'month', 'last_month', 'year']) ? $_POST['time_range'] : 'today';
    $labels = [];
    $amount = [];

    if ($time_range == 'today') {
        // Thống kê theo giờ trong ngày hôm nay
        $today = date("Y-m-d");
        for ($hour = 0; $hour < 24; $hour++) {
            $hour_start = sprintf("%02d:00:00", $hour);
            $hour_end = sprintf("%02d:59:59", $hour);

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$today' AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$today' AND TIME(create_date) >= '$hour_start' AND TIME(create_date) <= '$hour_end'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$today' AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'")['total'] ?? 0;
            $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) = '$today' AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$today' AND TIME(create_date) >= '$hour_start' AND TIME(create_date) <= '$hour_end'")['total'] ?? 0;
            $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) = '$today' AND TIME(create_date) >= '$hour_start' AND TIME(create_date) <= '$hour_end'")['total'] ?? 0;
            $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) = '$today' AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'")['total'] ?? 0;
            $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) = '$today' AND TIME(create_gettime) >= '$hour_start' AND TIME(create_gettime) <= '$hour_end'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$today' AND TIME(created_at) >= '$hour_start' AND TIME(created_at) <= '$hour_end'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$today' AND TIME(created_at) >= '$hour_start' AND TIME(created_at) <= '$hour_end'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$today' AND TIME(created_at) >= '$hour_start' AND TIME(created_at) <= '$hour_end'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$today' AND TIME(created_at) >= '$hour_start' AND TIME(created_at) <= '$hour_end'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix;

            $labels[] = sprintf("%02d:00", $hour);
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi;

            $labels[] = date("d/m", strtotime("-$i days"));
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix;

            $labels[] = "$day/$month";
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'last_month') {
        // Thống kê theo tháng trước
        $lastMonth = date('m', strtotime('-1 month'));
        $lastMonthYear = date('Y', strtotime('-1 month'));
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $lastMonth, $lastMonthYear);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = sprintf("%s-%02d-%02d", $lastMonthYear, $lastMonth, $day);

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix;

            $labels[] = "$day/$lastMonth";
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));

            $start_date = "$year-$month-01";
            $last_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $end_date = "$year-$month-$last_day";

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix;

            $labels[] = "Tháng $month_name";
            $amount[] = $total_topup;
        }
    }

    die(json_encode([
        'labels' => $labels,
        'amount' => $amount
    ]));
}

if ($_POST['action'] == 'view_chart_thong_ke_nap_tien_thang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $month = date('m');
    $year = date('Y');
    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $labels = [];
    $data = [];

    for ($day = 1; $day <= $numOfDays; $day++) {
        $date = "$year-$month-$day";

        $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
        $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_momo = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_momo WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;
        $total_topup_pm = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_pm WHERE `status` = 1 AND DATE(create_date) = '$date'")['total'] ?? 0;
        $total_topup_squadco = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_squadco WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_toyyibpay = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_toyyibpay WHERE `status` = 1 AND DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_momo + $total_topup_paypal + $total_topup_pm + $total_topup_squadco + $total_topup_toyyibpay + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix;

        $labels[] = "$day/$month/$year";
        $data[] = $total_topup;
    }

    die(json_encode([
        'labels' => $labels,
        'data' => $data
    ]));
}

if ($_POST['action'] == 'view_chart_thong_ke_don_hang_thang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $month = date('m');
    $year = date('Y');
    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $labels = [];
    $revenues = [];
    $profits = [];

    for ($day = 1; $day <= $numOfDays; $day++) {
        $date = "$year-$month-$day";
        $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND DATE(create_gettime) = '$date'";
        $result = $CMSNT->get_row($query);

        $labels[] = "$day/$month/$year";
        $revenues[] = $result['total_pay'] ?? 0;
        $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
    }

    die(json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}


if ($_POST['action'] == 'show_thong_ke_dashboard') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $currentDate = date("Y-m-d");
    $currentYear = date('Y');
    $currentMonth = date('m');

    // Xác định ngày bắt đầu và kết thúc của tuần hiện tại (Thứ Hai đến Chủ Nhật)
    $startOfWeek = date("Y-m-d", strtotime("last Monday", strtotime($currentDate)));
    // Nếu hôm nay là Thứ Hai, không cần lùi lại
    if (date('N', strtotime($currentDate)) == 1) {
        $startOfWeek = $currentDate;
    }
    $endOfWeek = date("Y-m-d", strtotime("next Sunday", strtotime($currentDate)));
    // Nếu hôm nay là Chủ Nhật, không cần tiến lên
    if (date('N', strtotime($currentDate)) == 7) {
        $endOfWeek = $currentDate;
    }

    // Dữ liệu hôm nay
    $query1 = "SELECT 
                COUNT(id) AS total_orders_today, 
                SUM(pay) AS total_pay_today, 
                SUM(cost) AS total_cost_today 
              FROM `product_order` 
              WHERE `refund` = 0 
              AND `create_gettime` LIKE '%$currentDate%'";
    $result1 = $CMSNT->get_row($query1);

    $total_orders_today = $result1['total_orders_today'];
    $total_pay_today = $result1['total_pay_today'];
    $total_cost_today = $result1['total_cost_today'];
    $profit_today = $total_pay_today - $total_cost_today;

    $new_users_today = $CMSNT->get_row("SELECT COUNT(id) AS total_users_today FROM `users` WHERE `create_date` LIKE '%$currentDate%'")['total_users_today'];

    // Dữ liệu tuần này
    $query_week = "SELECT 
                    COUNT(id) AS total_orders_week, 
                    SUM(pay) AS total_pay_week, 
                    SUM(cost) AS total_cost_week 
                  FROM `product_order` 
                  WHERE `refund` = 0 
                  AND DATE(`create_gettime`) BETWEEN '$startOfWeek' AND '$endOfWeek'";
    $result_week = $CMSNT->get_row($query_week);

    $total_orders_week = $result_week['total_orders_week'];
    $total_pay_week = $result_week['total_pay_week'];
    $total_cost_week = $result_week['total_cost_week'];
    $profit_week = $total_pay_week - $total_cost_week;

    $new_users_week = $CMSNT->get_row("SELECT COUNT(id) AS total_users_week FROM `users` WHERE DATE(`create_date`) BETWEEN '$startOfWeek' AND '$endOfWeek'")['total_users_week'];

    // Dữ liệu tháng này
    $query2 = "SELECT 
                COUNT(id) AS total_orders_month, 
                SUM(pay) AS total_pay_month, 
                SUM(cost) AS total_cost_month 
              FROM `product_order` 
              WHERE `refund` = 0 
              AND YEAR(create_gettime) = $currentYear 
              AND MONTH(create_gettime) = $currentMonth";
    $result2 = $CMSNT->get_row($query2);

    $total_orders_month = $result2['total_orders_month'];
    $total_pay_month = $result2['total_pay_month'];
    $total_cost_month = $result2['total_cost_month'];
    $profit_month = $total_pay_month - $total_cost_month;

    $new_users_month = $CMSNT->get_row("SELECT COUNT(id) AS total_users_month FROM `users` WHERE YEAR(create_date) = $currentYear AND MONTH(create_date) = $currentMonth")['total_users_month'];

    // Dữ liệu toàn thời gian
    $query3 = "SELECT 
                COUNT(id) AS total_orders_all, 
                SUM(pay) AS total_pay_all, 
                SUM(cost) AS total_cost_all 
              FROM `product_order` 
              WHERE `refund` = 0";
    $result3 = $CMSNT->get_row($query3);

    $total_orders_all = $result3['total_orders_all'];
    $total_pay_all = $result3['total_pay_all'];
    $total_cost_all = $result3['total_cost_all'];
    $profit_all = $total_pay_all - $total_cost_all;

    $total_users_all = $CMSNT->get_row("SELECT COUNT(id) AS total_users_all FROM `users`")['total_users_all'];

    $data = array(
        "total_orders_today" => format_cash($total_orders_today),
        "total_pay_today" => format_currency($total_pay_today),
        "total_cost_today" => format_currency($total_cost_today),
        "profit_today" => format_currency($profit_today),
        "new_users_today" => format_cash($new_users_today),

        // Thêm dữ liệu tuần này
        "total_orders_week" => format_cash($total_orders_week),
        "total_pay_week" => format_currency($total_pay_week),
        "total_cost_week" => format_currency($total_cost_week),
        "profit_week" => format_currency($profit_week),
        "new_users_week" => format_cash($new_users_week),

        "total_orders_month" => format_cash($total_orders_month),
        "total_pay_month" => format_currency($total_pay_month),
        "total_cost_month" => format_currency($total_cost_month),
        "profit_month" => format_currency($profit_month),
        "new_users_month" => format_cash($new_users_month),
        "total_orders_all" => format_cash($total_orders_all),
        "total_pay_all" => format_currency($total_pay_all),
        "total_cost_all" => format_currency($total_cost_all),
        "profit_all" => format_currency($profit_all),
        "total_users_all" => format_cash($total_users_all)
    );

    die(json_encode($data));
}



if ($_POST['action'] == 'phan_tich_utm_source_users') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    // Tạo HTML cho tab
    $html = '<ul class="nav nav-tabs mb-5 nav-justified nav-style-1 d-sm-flex d-block" id="myTab" role="tablist">';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link active" id="table-tab" data-toggle="tab" href="#table-content" role="tab" aria-controls="table-content" aria-selected="true">Table</a>';
    $html .= '</li>';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link" id="chart-tab" data-toggle="tab" href="#chart-content" role="tab" aria-controls="chart-content" aria-selected="false">Pie Chart</a>';
    $html .= '</li>';
    $html .= '</ul>';

    // Tạo HTML cho nội dung của tab
    $html .= '<div class="tab-content" id="myTabContent">';
    $html .= '<div class="tab-pane fade show active" id="table-content" role="tabpanel" aria-labelledby="table-tab">';
    $html .= '<div class="table-responsive table-wrapper" style="max-height: 500px;overflow-y: auto;">';
    $html .= '<table class="table text-nowrap table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Xếp hạng</th>
                    <th class="text-center">utm_source</th>
                    <th class="text-center">Số thành viên đăng ký</th>
                </tr>
            </thead>
            <tbody>';
    $i = 1;
    $data_labels = [];
    $data_user_counts = [];
    foreach (
        $CMSNT->get_list("SELECT 
    utm_source, 
    COUNT(*) AS total_users
FROM users 
GROUP BY utm_source 
ORDER BY total_users DESC ") as $row
    ) {
        $data_labels[] = $row['utm_source'];
        $data_user_counts[] = $row['total_users'];
        $html .= "<tr>
    <td class='text-center' style='font-size:15px;'>" . $i++ . "</td>
    <td class='text-center'>" . $row['utm_source'] . "</td>
    <td class='text-center'><b>" . format_cash($row['total_users']) . "</b></td>
  </tr>";
    }
    $html .= "</tbody>
        </table>";
    $html .= "</div>";
    $html .= '</div>';

    $html .= '<div class="tab-pane fade" id="chart-content" role="tabpanel" aria-labelledby="chart-tab">';
    $html .= '<canvas id="myChart" width="500" height="300"></canvas>';
    $html .= '</div>';

    $html .= '</div>';

    // Thêm kịch bản JavaScript để chuyển đổi tab
    $html .= '<script>
            $(document).ready(function(){
                $("#table-tab").click(function(){
                    $("#chart-content").removeClass("show active");
                    $("#chart-tab").removeClass("active");
                    $("#table-content").addClass("show active");
                    $("#table-tab").addClass("active");
                });
                $("#chart-tab").click(function(){
                    $("#table-content").removeClass("show active");
                    $("#table-tab").removeClass("active");
                    $("#chart-content").addClass("show active");
                    $("#chart-tab").addClass("active");
                    // Thêm kịch bản JavaScript để vẽ biểu đồ Pie Chart
                    var ctx = document.getElementById("myChart").getContext("2d");
                    var myChart = new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels: ' . json_encode($data_labels) . ',
                            datasets: [{
                                label: "Số lượng người dùng",
                                data: ' . json_encode($data_user_counts) . ',
                                backgroundColor: [
                                    "rgba(255, 99, 132, 0.6)",
                                    "rgba(54, 162, 235, 0.6)",
                                    "rgba(255, 206, 86, 0.6)",
                                    "rgba(75, 192, 192, 0.6)",
                                    "rgba(153, 102, 255, 0.6)",
                                    "rgba(255, 159, 64, 0.6)"
                                ],
                                borderColor: [
                                    "rgba(255, 99, 132, 1)",
                                    "rgba(54, 162, 235, 1)",
                                    "rgba(255, 206, 86, 1)",
                                    "rgba(75, 192, 192, 1)",
                                    "rgba(153, 102, 255, 1)",
                                    "rgba(255, 159, 64, 1)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: "right",
                                labels: {
                                    fontColor: "black",
                                    fontSize: 12
                                }
                            }
                        }
                    });
                });
            });
        </script>';







    die($html);
}


if ($_POST['action'] == 'view_nap_tien_gan_day') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_recent_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $deposits = $CMSNT->get_list("SELECT * FROM `deposit_log` WHERE `is_virtual` = 0 ORDER BY id DESC limit 100");
    $html = '';
    foreach ($deposits as $deposit) {
        $html .= '<li>
        <div class="timeline-time text-end">
            <span class="date">' . timeAgo($deposit['create_time']) . '</span>
        </div>
        <div class="timeline-icon">
            <a href="javascript:void(0);"></a>
        </div>
        <div class="timeline-body">
            <div class="d-flex align-items-top timeline-main-content flex-wrap mt-0">
                <div class="flex-fill">
                    <div class="d-flex align-items-center">
                        <div class="mt-sm-0 mt-2">
                            <p class="mb-0 text-muted"><a class="fw-bold" href="' . base_url_admin('user-edit&id=' . $deposit['user_id']) . '" style="color: green;">' . getRowRealtime('users', $deposit['user_id'], 'username') . '</a>
                                thực hiện nạp <b style="color: blue;">' . format_currency($deposit['amount']) . '</b>
                                bằng <b style="color:red">' . $deposit['method'] . '</b> thực nhận <b style="color:blue;">' . format_currency($deposit['received']) . '</b>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>';
    }
    die($html);
}
if ($_POST['action'] == 'view_don_hang_gan_day') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_recent_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $orders = $CMSNT->get_list("SELECT * FROM `order_log` WHERE `is_virtual` = 0 ORDER BY id DESC limit 100");
    $html = '';
    foreach ($orders as $order) {
        $html .= '<li>
            <div class="timeline-time text-end">
                <span class="date">' . timeAgo($order['create_time']) . '</span>
            </div>
            <div class="timeline-icon">
                <a href="javascript:void(0);"></a>
            </div>
            <div class="timeline-body">
                <div class="d-flex align-items-top timeline-main-content flex-wrap mt-0">
                    <div class="flex-fill">
                        <div class="d-flex align-items-center">
                            <div class="mt-sm-0 mt-2">
                                <p class="mb-0 text-muted"><a class="fw-bold" href="' . base_url_admin('user-edit&id=' . $order['buyer']) . '" style="color: green;">' . getRowRealtime('users', $order['buyer'], 'username') . '</a>
                                    mua <b style="color: red;">' . format_cash($order['amount']) . '</b>
                                    <b>' . $order['product_name'] . '</b> với giá <b style="color:blue;">' . format_currency($order['pay']) . '</b>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>';
    }
    die($html);
}

if ($_POST['action'] == 'top_san_pham_ban_chay') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_order_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    // Tạo HTML cho tab
    $html = '<ul class="nav nav-tabs mb-5 nav-justified nav-style-1 d-sm-flex d-block" id="myTab" role="tablist">';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link active" id="table-tab" data-toggle="tab" href="#table-content" role="tab" aria-controls="table-content" aria-selected="true">Table</a>';
    $html .= '</li>';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link" id="chart-tab" data-toggle="tab" href="#chart-content" role="tab" aria-controls="chart-content" aria-selected="false">Pie Chart</a>';
    $html .= '</li>';
    $html .= '</ul>';

    // Tạo HTML cho nội dung của tab
    $html .= '<div class="tab-content" id="myTabContent">';
    $html .= '<div class="tab-pane fade show active" id="table-content" role="tabpanel" aria-labelledby="table-tab">';
    $html .= '<div class="table-responsive table-wrapper" style="max-height: 500px;overflow-y: auto;">';
    $html .= '<table class="table text-nowrap table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">Xếp hạng</th>
                    <th scope="col">Sản phẩm</th>
                    <th scope="col">Đơn hàng đã bán</th>
                    <th scope="col">Tài khoản đã bán</th>
                    <th scope="col">Doanh thu</th>
                    <th scope="col">Lợi nhuận</th>
                </tr>
            </thead>
            <tbody>';
    $i = 1;
    $data_labels = [];
    $data_revenue = [];
    foreach (
        $CMSNT->get_list("SELECT 
    product_id, 
    product_name, 
    COUNT(*) AS total_orders, 
    SUM(amount) AS total_quantity, 
    SUM(pay) AS total_revenue,
    SUM(cost) AS total_cost
FROM product_order 
WHERE refund != 1 
GROUP BY product_id, product_name 
ORDER BY total_quantity DESC, total_orders DESC ") as $row
    ) {
        $data_labels[] = $row['product_name'];
        $data_revenue[] = $row['total_revenue'];
        $profit = $row['total_revenue'] - $row['total_cost']; // Lợi nhuận = Tổng doanh thu - Tổng chi phí
        $html .= "<tr>
    <td class='text-center' style='font-size:15px;'>" . $i++ . "</td>
    <td><a class='text-primary' href='" . base_url_admin('product-edit&id=' . $row['product_id']) . "'>" . $row['product_name'] . "</a></td>
    <td class='text-right'><b>" . format_cash($row['total_orders']) . "</b></td>
    <td class='text-right'><b style='color:blue;'>" . format_cash($row['total_quantity']) . "</b></td>
    <td class='text-right'><b style='color:red;'>" . format_currency($row['total_revenue']) . "</b></td>
    <td class='text-right'><b style='color:green;'>" . format_currency($profit) . "</b></td>
  </tr>";
    }
    $html .= "</tbody>
        </table>";
    $html .= "</div>";
    $html .= '</div>';

    $html .= '<div class="tab-pane fade" id="chart-content" role="tabpanel" aria-labelledby="chart-tab">';
    $html .= '<canvas id="myChart" width="500" height="300"></canvas>';
    $html .= '</div>';

    $html .= '</div>';

    // Thêm kịch bản JavaScript để chuyển đổi tab
    $html .= '<script>
            $(document).ready(function(){
                $("#table-tab").click(function(){
                    $("#chart-content").removeClass("show active");
                    $("#chart-tab").removeClass("active");
                    $("#table-content").addClass("show active");
                    $("#table-tab").addClass("active");
                });
                $("#chart-tab").click(function(){
                    $("#table-content").removeClass("show active");
                    $("#table-tab").removeClass("active");
                    $("#chart-content").addClass("show active");
                    $("#chart-tab").addClass("active");
                    // Thêm kịch bản JavaScript để vẽ biểu đồ Pie Chart
                    var ctx = document.getElementById("myChart").getContext("2d");
                    var myChart = new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels: ' . json_encode($data_labels) . ',
                            datasets: [{
                                label: "Doanh Thu",
                                data: ' . json_encode($data_revenue) . ',
                                backgroundColor: [
                                    "rgba(255, 99, 132, 0.6)",
                                    "rgba(54, 162, 235, 0.6)",
                                    "rgba(255, 206, 86, 0.6)",
                                    "rgba(75, 192, 192, 0.6)",
                                    "rgba(153, 102, 255, 0.6)",
                                    "rgba(255, 159, 64, 0.6)"
                                ],
                                borderColor: [
                                    "rgba(255, 99, 132, 1)",
                                    "rgba(54, 162, 235, 1)",
                                    "rgba(255, 206, 86, 1)",
                                    "rgba(75, 192, 192, 1)",
                                    "rgba(153, 102, 255, 1)",
                                    "rgba(255, 159, 64, 1)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: "right",
                                labels: {
                                    fontColor: "black",
                                    fontSize: 12
                                }
                            }
                        }
                    });
                });
            });
        </script>';






    die($html);
}

if ($_POST['action'] == 'view_product_sold') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_sold_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $accounts = '';
    foreach ($CMSNT->get_list(" SELECT * FROM `product_sold` ORDER BY id DESC ") as $account) {
        $accounts .= htmlspecialchars_decode($account['account']) . PHP_EOL;
    }
    $data = json_encode([
        'status'    => 'success',
        'accounts'  => $accounts,
        'msg'       => __('Xuất dữ liệu thành công')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Copy toàn bộ danh sách tài khoản đã bán')
    ]);
    die($data);
}

if ($_POST['action'] == 'view_product_live') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['code'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    $code = validate_string($_POST['code'], 255);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (!$product_die = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `product_code` = ?", [$code])) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không tồn tại trong hệ thống')]));
    }
    $accounts = '';
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE `product_code` = ? ORDER BY id DESC", [$code]) as $account) {
        $accounts .= htmlspecialchars_decode($account['account']) . PHP_EOL;
    }
    $data = json_encode([
        'status'    => 'success',
        'accounts'  => $accounts,
        'msg'       => __('Xuất dữ liệu thành công')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xem danh sách tài khoản LIVE của kho hàng') . ' (' . $code . ')'
    ]);
    die($data);
}
if ($_POST['action'] == 'view_product_die') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['code'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    $code = validate_string($_POST['code'], 255);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ')]));
    }
    if (!$product_die = $CMSNT->get_row_safe("SELECT * FROM `product_die` WHERE `product_code` = ?", [$code])) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không tồn tại trong hệ thống')]));
    }
    $accounts = '';
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_die` WHERE `product_code` = ? ORDER BY id DESC", [$code]) as $account) {
        $accounts .= htmlspecialchars_decode($account['account']) . PHP_EOL;
    }
    $data = json_encode([
        'status'    => 'success',
        'accounts'  => $accounts,
        'msg'       => __('Xuất dữ liệu thành công')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xem danh sách tài khoản DIE của kho hàng') . ' (' . $code . ')'
    ]);
    die($data);
}
if ($_POST['action'] == 'view_order') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_order_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['trans_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    $trans_id = validate_string($_POST['trans_id'], 100);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ?", [$trans_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }

    // Lấy thông tin người mua
    $buyer_info = null;
    if ($order['buyer'] > 0) {
        $buyer = $CMSNT->get_row("SELECT `id`, `username`, `email`, `phone`, `money`, `total_money`, `create_date` FROM `users` WHERE `id` = '" . intval($order['buyer']) . "'");
        if ($buyer) {
            $buyer_info = [
                'id' => $buyer['id'],
                'username' => $buyer['username'],
                'email' => $buyer['email'] ? $buyer['email'] : '',
                'phone' => $buyer['phone'] ? $buyer['phone'] : '',
                'money' => format_currency($buyer['money']),
                'total_money' => format_currency($buyer['total_money']),
                'create_date' => $buyer['create_date']
            ];
        }
    }

    // Lấy thông tin người bán (CTV)
    $seller_info = null;
    if ($order['seller'] > 0) {
        $seller = $CMSNT->get_row("SELECT `id`, `username`, `money`, `total_money` FROM `users` WHERE `id` = '" . intval($order['seller']) . "'");
        if ($seller) {
            $seller_info = [
                'id' => $seller['id'],
                'username' => $seller['username'],
                'money' => format_currency($seller['money']),
                'total_money' => format_currency($seller['total_money'])
            ];
        }
    }

    // Lấy thông tin supplier
    $supplier_info = null;
    if ($order['supplier_id'] > 0) {
        $supplier = $CMSNT->get_row("SELECT `id`, `domain`, `type` FROM `suppliers` WHERE `id` = '" . intval($order['supplier_id']) . "'");
        if ($supplier) {
            $supplier_info = [
                'id' => $supplier['id'],
                'domain' => $supplier['domain'],
                'type' => $supplier['type']
            ];
        }
    }

    // Lấy danh sách tài khoản đã bán
    $accounts = '';
    $accounts_list = [];
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ? ORDER BY id DESC", [$trans_id]) as $account) {
        $accounts .= htmlspecialchars_decode($account['account']) . PHP_EOL;
        $accounts_list[] = [
            'uid' => isset($account['uid']) ? $account['uid'] : '',
            'account' => htmlspecialchars_decode($account['account']),
            'type' => isset($account['type']) ? $account['type'] : 'WEB'
        ];
    }

    // Tính lợi nhuận
    $profit = $order['pay'] - $order['cost'];
    $commission_fee = isset($order['commission_fee']) ? $order['commission_fee'] : 0;

    $data = json_encode([
        'status'    => 'success',
        'accounts'  => $accounts,
        'order' => [
            'id' => $order['id'],
            'trans_id' => $order['trans_id'],
            'api_transid' => $order['api_transid'] ? $order['api_transid'] : '',
            'product_id' => $order['product_id'],
            'product_name' => $order['product_name'],
            'amount' => $order['amount'],
            'pay' => format_currency($order['pay']),
            'pay_raw' => $order['pay'],
            'cost' => format_currency($order['cost']),
            'cost_raw' => $order['cost'],
            'profit' => format_currency($profit),
            'commission_fee' => format_currency($commission_fee),
            'ip' => isset($order['ip']) ? $order['ip'] : '',
            'device' => isset($order['device']) ? $order['device'] : '',
            'note' => isset($order['note']) ? $order['note'] : '',
            'refund' => $order['refund'],
            'supplier_id' => $order['supplier_id'],
            'create_gettime' => $order['create_gettime'],
            'time_ago' => timeAgo(strtotime($order['create_gettime']))
        ],
        'buyer' => $buyer_info,
        'seller' => $seller_info,
        'supplier' => $supplier_info,
        'accounts_list' => $accounts_list,
        'accounts_count' => count($accounts_list),
        'msg'       => __('Lấy thành công chi tiết đơn hàng')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('View order') . ' (' . $order['trans_id'] . ')'
    ]);

    die($data);
}
if ($_POST['action'] == 'download_order') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_order_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['trans_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    $trans_id = validate_string($_POST['trans_id'], 100);
    if ($trans_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không hợp lệ')]));
    }
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ?", [$trans_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại trong hệ thống')]));
    }
    $accounts = '';
    foreach ($CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ? ORDER BY id DESC", [$trans_id]) as $account) {
        $accounts .= preg_replace('/\s+/', '', $account['account']) . PHP_EOL;
    }
    $file = $trans_id . ".txt";
    $data = json_encode([
        'status'    => 'success',
        'filename'  => $file,
        'accounts'  => $accounts,
        'msg'       => __('Đang tải xuống đơn hàng...')
    ]);

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Download order') . ' (' . $order['trans_id'] . ')'
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}',  __('Download order') . ' (' . $order['trans_id'] . ')', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die($data);
}

if ($_POST['action'] == 'view_chart_thong_ke_don_hang_api' || $_POST['action'] == 'view_chart_thong_ke_don_hang_supplier') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = isset($_POST['time_range']) && in_array($_POST['time_range'], ['week', 'month', 'year']) ? $_POST['time_range'] : 'week';
    $labels = [];
    $revenues = [];
    $profits = [];

    // Xác định điều kiện lọc supplier_id
    $supplierCondition = "";
    if ($_POST['action'] == 'view_chart_thong_ke_don_hang_api') {
        // Nếu là thống kê tất cả API
        $supplierCondition = "`supplier_id` != 0";
    } else {
        // Nếu là thống kê theo supplier cụ thể
        $supplier_id = validate_int($_POST['supplier_id'], 1);
        if ($supplier_id === false) {
            die(json_encode(['status' => 'error', 'msg' => __('Nhà cung cấp không hợp lệ')]));
        }
        // Kiểm tra tồn tại supplier_id
        if (!$CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ?", [$supplier_id])) {
            die(json_encode(['status' => 'error', 'msg' => __('Nhà cung cấp không tồn tại')]));
        }
        $supplierCondition = "`supplier_id` = ?";
        $supplierParams = [$supplier_id];
    }

    if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND $supplierCondition AND DATE(create_gettime) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND $supplierCondition AND DATE(create_gettime) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = "$day/$month";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));
            $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order 
                      WHERE `refund` = 0 AND $supplierCondition AND MONTH(create_gettime) = '$month' AND YEAR(create_gettime) = '$year'";
            $result = $CMSNT->get_row($query);

            $labels[] = "Tháng $month_name";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    }

    die(json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}

// Duy trì API cũ để tương thích với code cũ
if ($_POST['action'] == 'view_chart_thong_ke_don_hang_api_thang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $month = date('m');
    $year = date('Y');
    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $labels = [];
    $revenues = [];
    $profits = [];

    for ($day = 1; $day <= $numOfDays; $day++) {
        $date = "$year-$month-$day";
        $query = "SELECT SUM(pay) AS total_pay, SUM(cost) AS total_cost FROM product_order WHERE `refund` = 0 AND `supplier_id` != 0 AND DATE(create_gettime) = '$date'";
        $result = $CMSNT->get_row($query);

        $labels[] = "$day/$month/$year";
        $revenues[] = $result['total_pay'] ?? 0;
        $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
    }

    die(json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}

if ($_POST['action'] == 'view_chart_doanh_thu_api_suppliers') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = isset($_POST['time_range']) && in_array($_POST['time_range'], ['week', 'month', 'year']) ? $_POST['time_range'] : 'week';
    $labels = [];
    $suppliers = [];

    // Lấy danh sách tất cả nhà cung cấp API
    $suppliersList = $CMSNT->get_list("SELECT `id`, `domain` FROM `suppliers` WHERE `status` = 1 ORDER BY `id` ASC");

    if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $labels[] = date("d/m", strtotime("-$i days"));

            // Lấy dữ liệu cho mỗi nhà cung cấp
            foreach ($suppliersList as $supplier) {
                $supplier_id = $supplier['id'];

                // Tìm supplier trong mảng hoặc tạo mới
                $found = false;
                foreach ($suppliers as &$sup) {
                    if ($sup['id'] == $supplier_id) {
                        $found = true;
                        $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                                  WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                                  AND DATE(create_gettime) = '$date'";
                        $result = $CMSNT->get_row($query);
                        $sup['revenues'][] = $result['total_pay'] ?? 0;
                        break;
                    }
                }

                if (!$found) {
                    $supplierData = [
                        'id' => $supplier_id,
                        'domain' => $supplier['domain'],
                        'name' => preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($supplier['domain'], '/')),
                        'revenues' => []
                    ];

                    // Fill với 0 cho các ngày trước
                    for ($j = 0; $j < 6 - $i; $j++) {
                        $supplierData['revenues'][] = 0;
                    }

                    $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                              WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                              AND DATE(create_gettime) = '$date'";
                    $result = $CMSNT->get_row($query);
                    $supplierData['revenues'][] = $result['total_pay'] ?? 0;

                    $suppliers[] = $supplierData;
                }
            }
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";
            $labels[] = "$day/$month";

            // Lấy dữ liệu cho mỗi nhà cung cấp
            foreach ($suppliersList as $supplier) {
                $supplier_id = $supplier['id'];

                // Tìm supplier trong mảng hoặc tạo mới
                $found = false;
                foreach ($suppliers as &$sup) {
                    if ($sup['id'] == $supplier_id) {
                        $found = true;
                        $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                                  WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                                  AND DATE(create_gettime) = '$date'";
                        $result = $CMSNT->get_row($query);
                        $sup['revenues'][] = $result['total_pay'] ?? 0;
                        break;
                    }
                }

                if (!$found) {
                    $supplierData = [
                        'id' => $supplier_id,
                        'domain' => $supplier['domain'],
                        'name' => preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($supplier['domain'], '/')),
                        'revenues' => []
                    ];

                    // Fill với 0 cho các ngày trước
                    for ($j = 1; $j < $day; $j++) {
                        $supplierData['revenues'][] = 0;
                    }

                    $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                              WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                              AND DATE(create_gettime) = '$date'";
                    $result = $CMSNT->get_row($query);
                    $supplierData['revenues'][] = $result['total_pay'] ?? 0;

                    $suppliers[] = $supplierData;
                }
            }
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));
            $labels[] = "Tháng $month_name";

            // Lấy dữ liệu cho mỗi nhà cung cấp
            foreach ($suppliersList as $supplier) {
                $supplier_id = $supplier['id'];

                // Tìm supplier trong mảng hoặc tạo mới
                $found = false;
                foreach ($suppliers as &$sup) {
                    if ($sup['id'] == $supplier_id) {
                        $found = true;
                        $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                                  WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                                  AND MONTH(create_gettime) = '$month' AND YEAR(create_gettime) = '$year'";
                        $result = $CMSNT->get_row($query);
                        $sup['revenues'][] = $result['total_pay'] ?? 0;
                        break;
                    }
                }

                if (!$found) {
                    $supplierData = [
                        'id' => $supplier_id,
                        'domain' => $supplier['domain'],
                        'name' => preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($supplier['domain'], '/')),
                        'revenues' => []
                    ];

                    // Fill với 0 cho các tháng trước
                    for ($j = 1; $j < $month; $j++) {
                        $supplierData['revenues'][] = 0;
                    }

                    $query = "SELECT SUM(pay) AS total_pay FROM product_order 
                              WHERE `refund` = 0 AND `supplier_id` = '$supplier_id' 
                              AND MONTH(create_gettime) = '$month' AND YEAR(create_gettime) = '$year'";
                    $result = $CMSNT->get_row($query);
                    $supplierData['revenues'][] = $result['total_pay'] ?? 0;

                    $suppliers[] = $supplierData;
                }
            }
        }
    }

    // Loại bỏ các nhà cung cấp không có doanh thu
    $filteredSuppliers = [];
    foreach ($suppliers as $supplier) {
        $totalRevenue = array_sum($supplier['revenues']);
        if ($totalRevenue > 0) {
            $filteredSuppliers[] = $supplier;
        }
    }

    die(json_encode([
        'labels' => $labels,
        'suppliers' => $filteredSuppliers
    ]));
}

// Export thành viên đã chọn/tất cả dạng TXT/CSV
if ($_POST['action'] == 'exportSelectedUsers') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input - hỗ trợ cả export theo IDs và export tất cả
    $export_all = !empty($_POST['export_all']);
    $ids = [];

    if (!$export_all) {
        if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một thành viên')]));
        }
        // Sanitize IDs
        $ids = array_filter(array_map('intval', $_POST['ids']));
        if (empty($ids)) {
            die(json_encode(['status' => 'error', 'msg' => __('ID thành viên không hợp lệ')]));
        }
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Allowed columns mapping
    $allowed_columns = [
        'id' => ['field' => 'id', 'label' => __('ID')],
        'username' => ['field' => 'username', 'label' => __('Username')],
        'email' => ['field' => 'email', 'label' => __('Email')],
        'fullname' => ['field' => 'fullname', 'label' => __('Họ tên')],
        'phone' => ['field' => 'phone', 'label' => __('Số điện thoại')],
        'money' => ['field' => 'money', 'label' => __('Số dư')],
        'total_money' => ['field' => 'total_money', 'label' => __('Tổng nạp')],
        'discount' => ['field' => 'discount', 'label' => __('Chiết khấu')],
        'admin' => ['field' => 'admin', 'label' => __('Admin')],
        'banned' => ['field' => 'banned', 'label' => __('Trạng thái')],
        'utm_source' => ['field' => 'utm_source', 'label' => __('utm_source')],
        'create_date' => ['field' => 'create_date', 'label' => __('Ngày tạo')],
        'ip' => ['field' => 'ip', 'label' => __('Địa chỉ IP')]
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause
    $select_fields = [];
    foreach ($selected_columns as $key => $col) {
        $select_fields[] = '`' . $col['field'] . '` AS `' . $key . '`';
    }

    // Build query - hỗ trợ cả export theo IDs và export tất cả
    if ($export_all) {
        $query = "SELECT " . implode(', ', $select_fields) . " FROM `users` ORDER BY id DESC";
        $users = $CMSNT->get_list($query);
    } else {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT " . implode(', ', $select_fields) . "
                  FROM `users`
                  WHERE id IN ($placeholders)
                  ORDER BY id DESC";
        $users = $CMSNT->get_list_safe($query, $ids);
    }

    if (empty($users)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy thành viên')]));
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Status mapping
    $status_labels = [
        '0' => __('Active'),
        '1' => __('Banned')
    ];

    // Data rows
    foreach ($users as $user) {
        $row = [];
        foreach ($selected_columns as $key => $col) {
            $value = $user[$key] ?? '';

            // Format specific fields
            if ($key === 'banned') {
                $value = $status_labels[$value] ?? $value;
            } elseif ($key === 'admin') {
                $value = ($value != '0') ? __('Có') : __('Không');
            } elseif (in_array($key, ['money', 'total_money'])) {
                $value = number_format((float)$value, 0, ',', '.');
            } elseif ($key === 'discount') {
                $value = number_format((float)$value, 0) . '%';
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $row[] = $value;
        }
        $lines[] = implode($separator, $row);
    }

    $content = implode("\n", $lines);
    $filename = 'users_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất thành viên
    $log_content = sprintf(
        'Xuất %d thành viên (%s) - File: %s - Các cột: %s',
        count($users),
        $export_all ? 'Tất cả' : 'IDs: ' . implode(', ', $ids),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d thành viên'), count($users)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}


// Lấy bảng xếp hạng user theo giá trị đơn hàng trong ngày
if ($_POST['action'] == 'get_daily_leaderboard') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");

    // Lấy top 50 user có tổng giá trị đơn hàng cao nhất trong ngày
    $query = "SELECT 
                u.id,
                u.username,
                u.fullname,
                u.email,
                SUM(po.pay) as total_spent,
                COUNT(po.id) as total_orders
              FROM `users` u
              INNER JOIN `product_order` po ON u.id = po.buyer
              WHERE po.refund = 0
              AND DATE(po.create_gettime) = '$currentDate'
              GROUP BY u.id, u.username, u.fullname, u.email
              ORDER BY total_spent DESC
              LIMIT 50";

    $leaderboard = $CMSNT->get_list($query);

    $data = [];
    $rank = 1;

    foreach ($leaderboard as $user) {
        $data[] = [
            'rank'  => $rank,
            'id'    => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'] ? $user['fullname'] : $user['username'],
            'email' => $user['email'],
            'total_spent' => format_currency($user['total_spent']),
            'total_orders' => format_cash($user['total_orders'])
        ];
        $rank++;
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'date' => date('d/m/Y')
    ]));
}

// Lấy BXH admin đa kỳ - hỗ trợ nhiều khoảng thời gian cho trang settings BXH
if ($_POST['action'] == 'get_admin_leaderboard') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    // Chỉ admin có quyền xem thống kê mới được xem BXH
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Whitelist các period hợp lệ - bao gồm cả kỳ quá khứ để admin so sánh
    $allowed_periods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'all_time'];
    $period = isset($_POST['period']) ? $_POST['period'] : 'today';
    if (!in_array($period, $allowed_periods)) {
        $period = 'today';
    }

    // Xác định khoảng thời gian dựa trên period
    $time_condition = '';
    $time_params = [];
    $period_label = '';

    switch ($period) {
        case 'today':
            // Hôm nay: từ 00:00 đến hiện tại
            $time_condition = " AND po.`create_gettime` >= ?";
            $time_params[] = date('Y-m-d') . ' 00:00:00';
            $period_label = date('d/m/Y');
            break;
        case 'yesterday':
            // Hôm qua: từ 00:00 đến 23:59:59 của ngày hôm qua
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $time_condition = " AND po.`create_gettime` >= ? AND po.`create_gettime` <= ?";
            $time_params[] = $yesterday . ' 00:00:00';
            $time_params[] = $yesterday . ' 23:59:59';
            $period_label = date('d/m/Y', strtotime('-1 day'));
            break;
        case 'this_week':
            // Tuần này: từ thứ 2 đầu tuần đến hiện tại
            $monday = date('Y-m-d', strtotime('monday this week'));
            $time_condition = " AND po.`create_gettime` >= ?";
            $time_params[] = $monday . ' 00:00:00';
            $period_label = date('d/m', strtotime('monday this week')) . ' - ' . date('d/m/Y');
            break;
        case 'last_week':
            // Tuần trước: từ thứ 2 đến Chủ nhật tuần trước
            $last_monday = date('Y-m-d', strtotime('monday last week'));
            $last_sunday = date('Y-m-d', strtotime('sunday last week'));
            $time_condition = " AND po.`create_gettime` >= ? AND po.`create_gettime` <= ?";
            $time_params[] = $last_monday . ' 00:00:00';
            $time_params[] = $last_sunday . ' 23:59:59';
            $period_label = date('d/m', strtotime('monday last week')) . ' - ' . date('d/m/Y', strtotime('sunday last week'));
            break;
        case 'this_month':
            // Tháng này: từ ngày 1 đến hiện tại
            $time_condition = " AND po.`create_gettime` >= ?";
            $time_params[] = date('Y-m-01') . ' 00:00:00';
            $period_label = '01/' . date('m/Y') . ' - ' . date('d/m/Y');
            break;
        case 'last_month':
            // Tháng trước: từ ngày 1 đến ngày cuối tháng trước
            $first_last_month = date('Y-m-01', strtotime('first day of last month'));
            $last_last_month = date('Y-m-t', strtotime('last month'));
            $time_condition = " AND po.`create_gettime` >= ? AND po.`create_gettime` <= ?";
            $time_params[] = $first_last_month . ' 00:00:00';
            $time_params[] = $last_last_month . ' 23:59:59';
            $period_label = date('d/m', strtotime('first day of last month')) . ' - ' . date('d/m/Y', strtotime('last day of last month'));
            break;
        case 'all_time':
            // Toàn thời gian - không giới hạn
            $time_condition = '';
            $time_params = [];
            $period_label = __('Toàn thời gian');
            break;
    }

    // Truy vấn BXH: top 50 user chi tiêu cao nhất - dùng prepared statements
    $sql = "SELECT 
                u.`id`,
                u.`username`,
                u.`fullname`,
                u.`email`,
                COUNT(po.`id`) AS total_orders,
                SUM(po.`pay`) AS total_spent
            FROM `product_order` po
            INNER JOIN `users` u ON po.`buyer` = u.`id`
            WHERE po.`refund` = 0 
              AND po.`trash` = 0
              {$time_condition}
            GROUP BY u.`id`, u.`username`, u.`fullname`, u.`email`
            ORDER BY total_spent DESC
            LIMIT ?";

    $params = array_merge($time_params, [50]);
    $leaderboard = $CMSNT->get_list_safe($sql, $params);

    // Format dữ liệu - admin được xem đầy đủ thông tin (không che username)
    $data = [];
    $rank = 1;
    foreach ($leaderboard as $user) {
        $data[] = [
            'rank'         => $rank,
            'id'           => $user['id'],
            'username'     => $user['username'],
            'fullname'     => $user['fullname'] ? $user['fullname'] : $user['username'],
            'email'        => $user['email'],
            'total_orders' => format_cash($user['total_orders']),
            'total_spent'  => format_currency($user['total_spent']),
        ];
        $rank++;
    }

    die(json_encode([
        'status'       => 'success',
        'period'       => $period,
        'period_label' => $period_label,
        'data'         => $data
    ]));
}

// Lấy top 50 services bán chạy nhất trong ngày
if ($_POST['action'] == 'get_daily_top_services') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");

    // Lấy top 50 products có tổng doanh thu cao nhất trong ngày
    $query = "SELECT 
                p.id as product_id,
                p.name as product_name,
                SUM(po.pay) as total_revenue,
                SUM(po.cost) as total_cost,
                COUNT(po.id) as total_orders,
                AVG(po.pay) as avg_price
              FROM `products` p
              INNER JOIN `product_order` po ON p.id = po.product_id
              WHERE po.refund = 0
              AND DATE(po.create_gettime) = '$currentDate'
              AND p.name IS NOT NULL
              AND p.name != ''
              GROUP BY p.id, p.name
              ORDER BY total_revenue DESC
              LIMIT 50";

    $products = $CMSNT->get_list($query);

    $data = [];
    $rank = 1;

    foreach ($products as $product) {
        $profit = $product['total_revenue'] - $product['total_cost'];
        $data[] = [
            'rank' => $rank,
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'total_revenue' => format_currency($product['total_revenue']),
            'total_cost' => format_currency($product['total_cost']),
            'profit' => format_currency($profit),
            'total_orders' => format_cash($product['total_orders']),
            'avg_price' => format_currency($product['avg_price'])
        ];
        $rank++;
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'date' => date('d/m/Y')
    ]));
}







if ($_POST['action'] == 'get_affiliate_members') {
    if (checkPermission($getUser['admin'], 'view_affiliate') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    if (empty($_POST['ref_id'])) {
        die(json_encode(['status' => 'error', 'msg' => 'ID không hợp lệ']));
    }
    $ref_id = validate_int($_POST['ref_id'], 1);
    if ($ref_id === false) {
        die(json_encode(['status' => 'error', 'msg' => 'ID không hợp lệ']));
    }

    // Lấy thông tin user ref
    if (!$refUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$ref_id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Người dùng không tồn tại']));
    }

    // Lấy danh sách thành viên đăng ký qua link affiliate
    $members = $CMSNT->get_list_safe("
        SELECT `id`, `username`, `email`, `money`, `total_money`, `create_date` 
        FROM `users` 
        WHERE `ref_id` = ? 
        ORDER BY `create_date` DESC 
    ", [$ref_id]);

    $data = [];
    if (!empty($members)) {
        foreach ($members as $member) {
            $data[] = [
                'id' => $member['id'],
                'username' => $member['username'],
                'email' => $member['email'],
                'money' => $member['money'],
                'money_formatted' => format_currency($member['money']),
                'total_money' => $member['total_money'],
                'total_money_formatted' => format_currency($member['total_money']),
                'create_date' => $member['create_date'],
                'edit_url' => base_url_admin('user-edit&id=' . $member['id'])
            ];
        }
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($members),
        'ref_username' => $refUser['username']
    ]));
}

if ($_POST['action'] == 'load_categories_api_datatable') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    if (empty($_POST['supplier_id'])) {
        die(json_encode(['status' => 'error', 'msg' => 'ID nhà cung cấp không hợp lệ']));
    }

    $supplier_id = validate_int($_POST['supplier_id'], 1);
    if ($supplier_id === false) {
        die(json_encode(['status' => 'error', 'msg' => 'ID nhà cung cấp không hợp lệ']));
    }

    // Kiểm tra supplier tồn tại
    if (!$supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ?", [$supplier_id])) {
        die(json_encode(['status' => 'error', 'msg' => 'Nhà cung cấp không tồn tại']));
    }

    // Lấy danh sách categories
    $categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `supplier_id` = ? ORDER BY `id` DESC", [$supplier_id]);

    $data = [];
    foreach ($categories as $cate) {
        // Lấy tên chuyên mục cha
        $parent_name = '';
        $parent_url = '';
        if ($cate['parent_id'] > 1) {
            $parent = $CMSNT->get_row_safe("SELECT `name` FROM `categories` WHERE `id` = ?", [$cate['parent_id']]);
            $parent_name = $parent ? $parent['name'] : '';
            $parent_url = base_url_admin('category-edit&id=' . $cate['parent_id']);
        }

        // Đếm số sản phẩm
        $total_products = $CMSNT->num_rows_safe("SELECT * FROM `products` WHERE `category_id` = ?", [$cate['id']]);

        $data[] = [
            'id' => $cate['id'],
            'name' => $cate['name'],
            'parent_id' => $cate['parent_id'],
            'parent_name' => $parent_name,
            'parent_url' => $parent_url,
            'total_products' => $total_products,
            'icon' => base_url($cate['icon']),
            'status' => $cate['status'],
            'edit_url' => base_url_admin('category-edit&id=' . $cate['id'])
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data
    ]));
}

// Xem trước số đơn hàng sẽ bị ảnh hưởng khi dọn dẹp
if ($_POST['action'] == 'previewCleanupOrders') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 AND `admin` != 0", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'delete_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $days_to_keep = intval($_POST['days_to_keep']);
    $cleanup_type = isset($_POST['cleanup_type']) && in_array($_POST['cleanup_type'], ['delete_order_revenue', 'delete_order_only', 'delete_order_not_uid', 'delete_order']) ? $_POST['cleanup_type'] : '';

    if ($days_to_keep < 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Số ngày giữ lại phải lớn hơn 0')]));
    }

    // Tính số giây tương ứng với số ngày
    $schedule = $days_to_keep * 24 * 60 * 60;

    // Đếm số đơn hàng sẽ bị ảnh hưởng
    $orders_count = $CMSNT->num_rows(" SELECT * FROM `product_order` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");

    // Chỉ đếm số tài khoản khi loại dọn dẹp có ảnh hưởng đến tài khoản
    $response = [
        'status' => 'success',
        'count' => format_cash($orders_count)
    ];

    // Nếu không phải loại "Xóa đơn hàng, không xóa tài khoản" thì mới hiển thị số tài khoản
    if ($cleanup_type != 'delete_order_only') {
        $accounts_count = $CMSNT->num_rows(" SELECT * FROM `product_sold` WHERE " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $schedule . " ");
        $response['accounts_count'] = format_cash($accounts_count);
    }

    die(json_encode($response));
}

// Preview cleanup Email Queue
if ($_POST['action'] == 'previewCleanupEmailQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = intval($_POST['days']);
    if ($days == 0) {
        // Đếm toàn bộ
        $count = $CMSNT->num_rows(" SELECT id FROM `email_queue` WHERE 1 ");
    } else {
        if ($days < 1) {
            die(json_encode(['status' => 'error', 'msg' => __('Số ngày không hợp lệ')]));
        }
        $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
        $count = $CMSNT->num_rows_safe(" SELECT id FROM `email_queue` WHERE `created_at` < ? ", [$cutoff]);
    }
    die(json_encode([
        'status' => 'success',
        'count' => format_cash($count)
    ]));
}

// Preview cleanup Telegram Queue
if ($_POST['action'] == 'previewCleanupTelegramQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = intval($_POST['days']);
    if ($days == 0) {
        // Đếm toàn bộ
        $count = $CMSNT->num_rows(" SELECT id FROM `telegram_queue` WHERE 1 ");
    } else {
        if ($days < 1) {
            die(json_encode(['status' => 'error', 'msg' => __('Số ngày không hợp lệ')]));
        }
        $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
        $count = $CMSNT->num_rows_safe(" SELECT id FROM `telegram_queue` WHERE `created_at` < ? ", [$cutoff]);
    }
    die(json_encode([
        'status' => 'success',
        'count' => format_cash($count)
    ]));
}
// Preview cleanup Categories
if ($_POST['action'] == 'previewCleanupCategories') {
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

    $orphan_count = 0;
    $empty_count = 0;
    $all_count = 0;

    if ($cleanup_all) {
        $all_count = $CMSNT->num_rows("SELECT `id` FROM `categories`");
    } else {
        if ($cleanup_orphan) {
            // Chuyên mục mồ côi: Có parent_id nhưng parent_id không tồn tại trong db categories
            $orphan_count = $CMSNT->num_rows("SELECT `id` FROM `categories` WHERE `parent_id` > 0 AND `parent_id` NOT IN (SELECT `id` FROM `categories`)");
        }

        if ($cleanup_empty) {
            // Chuyên mục rỗng:
            // 1. Không có chuyên mục con (id not in parent_id of any other category)
            // 2. Không chứa sản phẩm (id not in category_id of products)
            // Lưu ý: Chúng ta lấy danh sách loại trừ thay vì query lồng sâu có thể bị chậm.
            // Chỉ những chuyên mục KHÔNG VƯỚNG bất kỳ cái gì mới được xóa.
            $empty_count = $CMSNT->num_rows("
                SELECT `id` FROM `categories` c 
                WHERE NOT EXISTS (SELECT 1 FROM `products` p WHERE p.category_id = c.id) 
                AND NOT EXISTS (SELECT 1 FROM `categories` sub WHERE sub.parent_id = c.id)
            ");
        }
    }

    die(json_encode([
        'status' => 'success',
        'orphan_count' => format_cash($orphan_count),
        'empty_count' => format_cash($empty_count),
        'all_count' => format_cash($all_count)
    ]));
}


// Load translate data with pagination and search
if ($_POST['action'] == 'load_translate_data') {
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

    $lang_id = intval($_POST['lang_id']);
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $search = validate_string($_POST['search']['value'] ?? '', 2550) ?: '';
    $order_column = intval($_POST['order'][0]['column']);
    $order_dir = isset($_POST['order'][0]['dir']) && in_array($_POST['order'][0]['dir'], ['asc', 'desc']) ? $_POST['order'][0]['dir'] : 'asc';
    $filter = isset($_POST['filter']) && in_array($_POST['filter'], ['all', 'untranslated']) ? $_POST['filter'] : 'all';

    // Kiểm tra ngôn ngữ tồn tại
    if (!$lang_row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ?", [$lang_id])) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại')]));
    }

    // Cột để sắp xếp
    $columns = array('id', 'name', 'value', 'id');
    $order_column_name = isset($columns[$order_column]) ? $columns[$order_column] : 'id';

    // Xây dựng câu truy vấn
    $where_conditions = ["`lang_id` = ?"];
    $where_params = [$lang_id];

    // Thêm filter cho nội dung chưa dịch
    if ($filter === 'untranslated') {
        $where_conditions[] = "(`name` = `value` OR `value` = '' OR `value` IS NULL)";
    }

    if (!empty($search)) {
        $where_conditions[] = "(`name` LIKE ? OR `value` LIKE ?)";
        $where_params[] = '%' . $search . '%';
        $where_params[] = '%' . $search . '%';
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Tổng số bản ghi
    $total_records = $CMSNT->num_rows_safe("SELECT * FROM `translate` WHERE `lang_id` = ?", [$lang_id]);

    // Tổng số bản ghi sau khi lọc
    $total_filtered = $CMSNT->num_rows_safe("SELECT * FROM `translate` WHERE $where_clause", $where_params);

    // Lấy dữ liệu với phân trang và sắp xếp
    $sql = "SELECT * FROM `translate` WHERE $where_clause ORDER BY $order_column_name $order_dir LIMIT ?, ?";
    $translates = $CMSNT->get_list_safe($sql, array_merge($where_params, [$start, $length]));

    $data = array();

    foreach ($translates as $trans) {
        $row = array();
        $row[] = '<input type="checkbox" class="form-check-input row-checkbox" value="' . $trans['id'] . '" data-name="' . htmlspecialchars($trans['name']) . '" data-code="' . $lang_row['code'] . '">';
        $row[] = '<textarea class="form-control" disabled>' . htmlspecialchars($trans['name']) . '</textarea>';
        $row[] = '<textarea class="form-control" id="value' . $trans['id'] . '" onchange="updateForm(\'' . $trans['id'] . '\')">' . htmlspecialchars($trans['value']) . '</textarea>';
        $row[] = '<div class="btn-list">
                    <button type="button" class="btn btn-primary-gradient btn-wave btn-sm" onclick="autoTranslate(\'' . $trans['id'] . '\', \'' . addslashes($trans['name']) . '\', \'' . $lang_row['code'] . '\', this)">
                        <i class="ri-translate"></i> ' . __('Dịch tự động') . '
                    </button>
                    <button type="button" class="btn btn-danger-gradient btn-wave btn-sm" onclick="RemoveRow(\'' . $trans['id'] . '\', \'' . addslashes($trans['name']) . '\')">
                        <i class="ri-delete-bin-line"></i> ' . __('Delete') . '
                    </button>
                  </div>';
        $data[] = $row;
    }

    $response = array(
        "draw" => $draw,
        "recordsTotal" => $total_records,
        "recordsFiltered" => $total_filtered,
        "data" => $data
    );

    die(json_encode($response));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
