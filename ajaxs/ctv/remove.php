<?php
define("IN_SITE", true);
require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/../../libs/db.php');
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__.'/../../libs/helper.php');
require_once(__DIR__.'/../../models/is_ctv.php');
$CMSNT = new DB();

if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);   
}
if($CMSNT->site('ctv_status') != 1){
    die(json_encode(['status' => 'error', 'msg' => __('CTV Panel đang bị tắt trong cài đặt hệ thống.')]));
}

// Handle delete product
if ($_POST['action'] == 'deleteProduct') {
    $product_id = validate_int($_POST['id'], 1);
    if ($product_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ!')]));
    }
    
    // Check if product exists and belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `user_id` = ?", [$product_id, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại hoặc bạn không có quyền xóa!')]));
    }
    
    // Check if product has been sold
    if ($product['sold'] > 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa sản phẩm đã có giao dịch!')]));
    }
    
    // Check if product has stock
    $stock_count = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_stock` WHERE `product_id` = ?", [$product_id])['total'];
    if ($stock_count > 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng xóa hết tài khoản trong kho trước khi xóa sản phẩm!')]));
    }
    
    // Delete product images from server
    if (!empty($product['images'])) {
        $images = explode(PHP_EOL, trim($product['images']));
        foreach ($images as $filename) {
            $filename = trim($filename);
            if (!empty($filename)) {
                $file_path = __DIR__ . '/../../' . dirImageProduct($filename);
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }
    
    // Delete product
    $isDelete = $CMSNT->remove('products', "`id` = ?", [$product_id]);
    
    if ($isDelete) {
        // Log activity
        $CMSNT->insert('logs', [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => '[CTV] CTV xóa sản phẩm: ' . $product['name']
        ]);
        
        die(json_encode(['status' => 'success', 'msg' => __('Xóa sản phẩm thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa sản phẩm!')]));
    }
}

// Handle remove product image
if ($_POST['action'] == 'removeImageProduct') {
    $product_id = validate_int($_POST['id'], 1);
    if ($product_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ!')]));
    }
    
    $image_url = validate_string($_POST['image'], 255);
    if ($image_url === false) {
        die(json_encode(['status' => 'error', 'msg' => __('URL ảnh không hợp lệ!')]));
    }
    
    // Check if product exists and belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `user_id` = ?", [$product_id, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại hoặc bạn không có quyền chỉnh sửa!')]));
    }
    
    // Get current images
    $current_images = explode(PHP_EOL, trim($product['images']));
    $updated_images = [];
    $image_found = false;
    
    foreach ($current_images as $img) {
        $img = trim($img);
        if (!empty($img)) {
            if ($img === $image_url) {
                $image_found = true;
                // Delete image file from server - Giờ img chỉ chứa tên file
                $file_path = __DIR__ . '/../../' . dirImageProduct($img);
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            } else {
                $updated_images[] = $img;
            }
        }
    }
    
    if (!$image_found) {
        die(json_encode(['status' => 'error', 'msg' => __('Ảnh không tồn tại!')]));
    }
    
    // Update product images
    $new_images = implode(PHP_EOL, $updated_images);
    $isUpdate = $CMSNT->update('products', [
        'images' => $new_images,
        'update_gettime' => gettime()
    ], "`id` = ?", [$product_id]);
    
    if ($isUpdate) {
        // Log activity
        $CMSNT->insert('logs', [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => 'CTV xóa ảnh sản phẩm: ' . $product['name']
        ]);
        
        die(json_encode(['status' => 'success', 'msg' => __('Xóa ảnh thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa ảnh!')]));
    }
}

// Handle remove account from stock
if ($_POST['action'] == 'removeAccountStock') {
    $account_id = validate_int($_POST['id'], 1);
    if ($account_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID tài khoản không hợp lệ!')]));
    }
    
    // Check if account exists and belongs to this CTV
    $account = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `id` = ? AND `seller` = ?", [$account_id, $getUser['id']]);
    if (!$account) {
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản không tồn tại hoặc bạn không có quyền xóa!')]));
    }
    
    // Remove account from stock
    $isRemove = $CMSNT->remove('product_stock', "`id` = ?", [$account_id]);
    
    if ($isRemove) {
        // Log activity
        $CMSNT->insert('logs', [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => 'CTV xóa tài khoản khỏi kho hàng: ' . $account['uid']
        ]);
        
        die(json_encode(['status' => 'success', 'msg' => __('Xóa tài khoản thành công!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa tài khoản!')]));
    }
}

// Handle empty list die accounts
if ($_POST['action'] == 'empty_list_die') {
    $code = validate_alphanumeric($_POST['id'], 50);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ!')]));
    }
    
    // Check if product belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ? AND `user_id` = ?", [$code, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền truy cập kho hàng này!')]));
    }
    
    // Count die accounts before deletion
    $die_count = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_die` WHERE `product_code` = ?", [$code])['total'];
    
    // Remove all die accounts for this product
    $isRemove = $CMSNT->remove('product_die', "`product_code` = ?", [$code]);
    
    if ($isRemove) {
        // Log activity
        $CMSNT->insert('logs', [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => 'CTV xóa toàn bộ ' . $die_count . ' tài khoản DIE khỏi kho hàng: ' . $code
        ]);
        
        die(json_encode(['status' => 'success', 'msg' => __('Xóa thành công ' . $die_count . ' tài khoản DIE!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa tài khoản DIE!')]));
    }
}

if ($_POST['action'] == 'empty_list_account_stock') {
    $code = validate_alphanumeric($_POST['id'], 50);
    if ($code === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Mã kho hàng không hợp lệ!')]));
    }
    
    $confirm_empty_list_account = validate_string($_POST['confirm_empty_list_account'], 50);
    if ($confirm_empty_list_account === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Cụm từ xác nhận không hợp lệ!')]));
    }
    
    if ($confirm_empty_list_account != 'toi dong y') {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn cần nhập đúng cụm từ xác nhận "toi dong y" để xóa toàn bộ tài khoản!')]));
    }
    
    // Check if product belongs to this CTV
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `code` = ? AND `user_id` = ?", [$code, $getUser['id']]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền truy cập kho hàng này!')]));
    }
    
    // Count live accounts before deletion
    $live_count = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_stock` WHERE `product_code` = ?", [$code])['total'];
    
    // Remove all live accounts for this product
    $isRemove = $CMSNT->remove('product_stock', "`product_code` = ?", [$code]);
    
    if ($isRemove) {
        // Log activity
        $CMSNT->insert('logs', [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => 'CTV xóa toàn bộ ' . $live_count . ' tài khoản LIVE khỏi kho hàng: ' . $code
        ]);
        
        die(json_encode(['status' => 'success', 'msg' => __('Xóa thành công ' . $live_count . ' tài khoản đang bán!')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa tài khoản đang bán!')]));
    }
}


die(json_encode(['status' => 'error', 'msg' => __('Action không hợp lệ!')]));
