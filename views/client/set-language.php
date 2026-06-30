<?php
define("IN_SITE", true);
require_once(__DIR__.'/../../libs/db.php');
require_once(__DIR__.'/../../libs/lang.php');
require_once(__DIR__.'/../../libs/helper.php');
require_once(__DIR__.'/../../config.php');

$CMSNT = new DB();

// Lấy language code từ URL parameter
$lang_code = !empty($_GET['lang']) ? validate_string($_GET['lang'], 10) : '';

if(!empty($lang_code) && $lang_code !== false) {
    // Kiểm tra xem ngôn ngữ có tồn tại trong database không
    $language = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `code` = ? AND `status` = ?", [$lang_code, 1]);
    
    if($language) {
        // Sử dụng hàm setLanguageByCode để set ngôn ngữ
        if(setLanguageByCode($lang_code)) {
            // Thành công - redirect về trang chủ
            redirect(base_url());
        } else {
            // Không thể set cookie - redirect về trang chủ
            redirect(base_url());
        }
    } else {
        // Ngôn ngữ không tồn tại - redirect về trang chủ
        redirect(base_url());
    }
} else {
    // Không có lang code - redirect về trang chủ
    redirect(base_url());
}
?> 