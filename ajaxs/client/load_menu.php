<?php

define("IN_SITE", true);
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");

// Lấy categories từ cache
$all_categories = get_categories_not_parent_cached();

// Tạo HTML cho menu dropdown (nav.php)
$menu_html = '';
$parent_categories = get_categories_parent_cached();

if (!empty($parent_categories)) {
    $menu_html .= '<div class="row row-cols-5">';
    
    foreach($parent_categories as $category) {
        $menu_html .= '<div class="col-4">';
        $menu_html .= '<div class="megamenu-wrap">';
        $menu_html .= '<h5 class="megamenu-title"> ' . __($category['name']) . '</h5>';
        $menu_html .= '<ul class="megamenu-list">';
        
        $child_categories = get_categories_by_parent_cached($category['id']);
        foreach($child_categories as $category1) {
            $menu_html .= '<li><a href="' . base_url('category/'.$category1['slug']) . '">';
            $menu_html .= '<img width="25px" src="' . base_url($category1['icon']) . '"> ';
            $menu_html .= __($category1['name']) . '</a></li>';
        }
        
        $menu_html .= '</ul>';
        $menu_html .= '</div>';
        $menu_html .= '</div>';
    }
    
    $menu_html .= '</div>';
}

// Tạo HTML cho category buttons (home.php)
$home_buttons_html = '';
foreach($all_categories as $category) {
    $home_buttons_html .= '<li><a class="btn-category-home"';
    $home_buttons_html .= ' href="javascript:void(0);"';
    $home_buttons_html .= ' onclick="loadProductsByCategory(\'' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') . '\')"';
    $home_buttons_html .= ' data-category-id="' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '"';
    $home_buttons_html .= ' data-category-slug="' . htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') . '">';
    $home_buttons_html .= '<img src="' . base_url($category['icon']) . '" width="25px" class="me-2">';
    $home_buttons_html .= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');
    $home_buttons_html .= '</a></li>';
}

// Trả về JSON với cả 2 loại HTML
header('Content-Type: application/json');
echo json_encode([
    'menu_html' => $menu_html,
    'home_buttons_html' => $home_buttons_html
]);
?>
