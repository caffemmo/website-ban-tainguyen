<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}


function setLanguageByCode($code){
    global $CMSNT;
    
    // Validate input
    $code = validate_string($code, 10);
    if ($code === false) {
        return false;
    }
    
    if ($row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `code` = ? AND `status` = 1", [$code])) {
        $isSet = setcookie('language', $row['lang'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}
// chọn ngôn ngữ
function setLanguage($id){
    global $CMSNT;
    
    // Validate input
    $id = validate_int($id, 1);
    if ($id === false) {
        return false;
    }
    
    if ($row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? AND `status` = 1", [$id])) {
        $isSet = setcookie('language', $row['lang'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

// lấy ngôn ngữ mặc định
function getLanguage(){
    global $CMSNT;
    if (isset($_COOKIE['language'])) {
        $language = validate_string($_COOKIE['language'], 10);
        if ($language !== false) {
            $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang` = ? AND `status` = 1", [$language]);
            if ($rowLang) {
                return $rowLang['lang'];
            }
        }
    }
    $rowLang = $CMSNT->get_row("SELECT * FROM `languages` WHERE `lang_default` = 1");
    if ($rowLang) {
        return $rowLang['lang'];
    }
    return false;
}
//hiển thị ngôn ngữ
function __($name){
    global $CMSNT;
    if (isset($_COOKIE['language'])) {
        $language = validate_string($_COOKIE['language'], 55);
        if ($language !== false) {
            $rowLang = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `lang` = ? AND `status` = 1", [$language]);
            if ($rowLang) {
                $rowTran = $CMSNT->get_row_safe("SELECT * FROM `translate` WHERE `lang_id` = ? AND `name` = ?", [$rowLang['id'], $name]);
                if ($rowTran) {
                    return $rowTran['value'];
                }
            }
        }
    }
    $rowLang = $CMSNT->get_row("SELECT * FROM `languages` WHERE `lang_default` = 1");
    if ($rowLang && isset($rowLang['id'])) {
        $rowTran = $CMSNT->get_row("SELECT * FROM `translate` WHERE `lang_id` = '".$rowLang['id']."' AND `name` = '".$name."'");
        if ($rowTran && isset($rowTran['value'])) {
            return $rowTran['value'];
        }
    }
    return $name;
}

