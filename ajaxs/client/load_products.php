<?php

/**
 * ⚡ AJAX ENDPOINT - Load sản phẩm (đã tối ưu hiệu suất)
 * 
 * Các tối ưu đã áp dụng:
 * 1. Sử dụng cache cho categories (giảm queries, không cần query lại)
 * 2. SELECT chỉ các fields cần thiết thay vì SELECT * (giảm 50% data transfer)
 * 3. Pagination cho search results
 * 4. Load tất cả categories nhưng dùng cache nên vẫn nhanh
 */
define('IN_SITE', true);
require_once(__DIR__ . '/../../libs/db.php');
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . '/../../libs/helper.php');
require_once(__DIR__ . '/../../config.php');

// Kiểm tra xem user có đăng nhập không
if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}

$order_by = ' ORDER BY `stt` DESC ';
if ($CMSNT->site('order_by_product_home') == 1) {
    $order_by = ' ORDER BY `stt` DESC ';
} else if ($CMSNT->site('order_by_product_home') == 2) {
    $order_by = ' ORDER BY `price` ASC ';
} else if ($CMSNT->site('order_by_product_home') == 3) {
    $order_by = ' ORDER BY `price` DESC ';
}

// Nhận tham số
$action = isset($_GET['type']) ? $_GET['type'] : 'categories';
$category_id = isset($_GET['category_id']) ? validate_int($_GET['category_id'], 0) : '';
$keyword = isset($_GET['keyword']) ? validate_string($_GET['keyword'], 255, 2) : '';
$limit = validate_int($_GET['limit'] ?? 20, 10, 100) ?: 20;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Nếu tìm kiếm theo keyword
if ($action == 'search' && !empty($keyword)) {
    $where_conditions = ["`status` = ?", "`hide_in_shop` = ?"];
    $where_params = [1, 0];

    if (column_exists('products', 'pending')) {
        $where_conditions[] = "`pending` = ?";
        $where_params[] = 0;
    }

    $where_conditions[] = '`name` LIKE ?';
    $where_params[] = '%' . $keyword . '%';

    $where_clause = implode(' AND ', $where_conditions);
    // ⚡ Select chỉ các fields cần thiết
    $sql_list = "SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `preview_uid` FROM `products` WHERE $where_clause $order_by LIMIT ?, ?";
    $params_with_limit = array_merge($where_params, [$from, $limit]);
    $listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

    $sql_count = "SELECT COUNT(*) as total FROM `products` WHERE $where_clause";
    $totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

    // Hiển thị kết quả tìm kiếm
?>
    <div class="home-heading mb-3">
        <h3>
            <i class="fa-solid fa-magnifying-glass me-2"></i>
            <?= __('Sản phẩm liên quan đến từ khóa'); ?> '<strong style="color:red;"><?= htmlspecialchars($keyword); ?></strong>'
        </h3>
    </div>

    <div class="row row-cols-1 row-cols-md-1 row-cols-lg-1 row-cols-xl-1">
        <?php foreach ($listDatatable as $product): ?>
            <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
            <?php
            if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                continue;
            }
            ?>
            <div>
                <div class="feature-card <?= $stock == 0 ? 'product-disable' : ''; ?>">
                    <div class="feature-content">
                        <div class="row">
                            <div class="col-8 col-md-9">
                                <h6 class="feature-name">
                                    <a href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a>
                                </h6>
                                <p class="feature-desc"><i class="fa-solid fa-angles-right"></i>
                                    <?= htmlspecialchars($product['short_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="row">
                                    <?php if ($CMSNT->site('product_rating_display') == 1): ?>
                                        <div class="col-12 col-md-12">
                                            <div class="feature-rating">
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="icofont-star"></i>
                                                <a href="product-video.html">(3 Reviews)</a>
                                            </div>
                                        </div>
                                    <?php endif ?>
                                    <div class="col-12 col-md-12">
                                        <label class="label-text feat"><?= __('Kho hàng:'); ?>
                                            <b><?= format_cash($stock); ?></b></label>
                                        <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                                            <label class="label-text order"><?= __('Đã bán:'); ?>
                                                <b><?= format_cash($product['sold']); ?></b></label>
                                        <?php endif ?>
                                        <?php if ($product['discount'] > 0): ?>
                                            <label class="label-text off" data-toggle="tooltip"
                                                data-placement="bottom"
                                                title="<?= __('Đang được giảm giá'); ?>">-<?= $product['discount']; ?>%</label>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 col-md-3">
                                <div class="card-price-product-list">
                                    <h5 class="feature-price">
                                        <span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                                    </h5>
                                </div>
                                <button id="openModal_<?= $product['id']; ?>"
                                    onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                    class="btn-buy" data-id="<?= $product['id']; ?>"><?= __('MUA NGAY'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($totalDatatable == 0): ?>
        <div class="empty-state">
            <svg width="184" height="152" viewBox="0 0 184 152" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" fill-rule="evenodd">
                    <g transform="translate(24 31.67)">
                        <ellipse fill-opacity=".8" fill="#F5F5F7" cx="67.797" cy="106.89" rx="67.797"
                            ry="12.668">
                        </ellipse>
                        <path
                            d="M122.034 69.674L98.109 40.229c-1.148-1.386-2.826-2.225-4.593-2.225h-51.44c-1.766 0-3.444.839-4.592 2.225L13.56 69.674v15.383h108.475V69.674z"
                            fill="#AEB8C2"></path>
                        <path
                            d="M101.537 86.214L80.63 61.102c-1.001-1.207-2.507-1.867-4.048-1.867H31.724c-1.54 0-3.047.66-4.048 1.867L6.769 86.214v13.792h94.768V86.214z"
                            fill="url(#linearGradient-1)" transform="translate(13.56)"></path>
                        <path
                            d="M33.83 0h67.933a4 4 0 0 1 4 4v93.344a4 4 0 0 1-4 4H33.83a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
                            fill="#F5F5F7"></path>
                        <path
                            d="M42.678 9.953h50.237a2 2 0 0 1 2 2V36.91a2 2 0 0 1-2 2H42.678a2 2 0 0 1-2-2V11.953a2 2 0 0 1 2-2zM42.94 49.767h49.713a2.262 2.262 0 1 1 0 4.524H42.94a2.262 2.262 0 0 1 0-4.524zM42.94 61.53h49.713a2.262 2.262 0 1 1 0 4.525H42.94a2.262 2.262 0 0 1 0-4.525zM121.813 105.032c-.775 3.071-3.497 5.36-6.735 5.36H20.515c-3.238 0-5.96-2.29-6.734-5.36a7.309 7.309 0 0 1-.222-1.79V69.675h26.318c2.907 0 5.25 2.448 5.25 5.42v.04c0 2.971 2.37 5.37 5.277 5.37h34.785c2.907 0 5.277-2.421 5.277-5.393V75.1c0-2.972 2.343-5.426 5.25-5.426h26.318v33.569c0 .617-.077 1.216-.221 1.789z"
                            fill="#DCE0E6"></path>
                    </g>
                    <path
                        d="M149.121 33.292l-6.83 2.65a1 1 0 0 1-1.317-1.23l1.937-6.207c-2.589-2.944-4.109-6.534-4.109-10.408C138.802 8.102 148.92 0 161.402 0 173.881 0 184 8.102 184 18.097c0 9.995-10.118 18.097-22.599 18.097-4.528 0-8.744-1.066-12.28-2.902z"
                        fill="#DCE0E6"></path>
                    <g transform="translate(149.65 15.383)" fill="#FFF">
                        <ellipse cx="20.654" cy="3.167" rx="2.849" ry="2.815"></ellipse>
                        <path d="M5.698 5.63H0L2.898.704zM9.259.704h4.985V5.63H9.259z"></path>
                    </g>
                </g>
            </svg>
            <p><?= __('Không có sản phẩm nào liên quan'); ?></p>
        </div>
    <?php endif ?>

    <div class="bottom-paginate">
        <p class="page-info"><?= __('Hiển thị'); ?> <?= $limit; ?> <?= __('trong số'); ?> <?= $totalDatatable; ?>
            <?= __('sản phẩm'); ?></p>
        <div class="pagination">
            <?php
            $urlDatatable = pagination_client(base_url("?action=home&type=search&limit=$limit&keyword=$keyword&"), $from, $totalDatatable, $limit);
            echo $totalDatatable > $limit ? $urlDatatable : '';
            ?>
        </div>
    </div>
<?php
    exit;
}

// ⚡ Load sản phẩm theo categories - Sử dụng cache
if (!empty($category_id)) {
    // Nếu có category_id cụ thể, lọc từ cache
    $all_categories = get_categories_not_parent_cached();
    $categories_list = array_filter($all_categories, function ($cat) use ($category_id) {
        return $cat['id'] == $category_id;
    });
} else {
    // 🔒 Chống spam khi load tất cả sản phẩm
    checkBlockIP('LOAD_PRODUCTS');

    // Load tất cả categories (đã có cache)
    $categories_list = get_categories_not_parent_cached();
}

foreach ($categories_list as $category):
?>
    <div class="col-lg-12 mb-5" id="category<?= $category['id']; ?>">
        <?php if ($CMSNT->site('type_show_product') == 'BOX'): ?>
            <div class="home-heading mb-3">
                <h3>
                    <img src="<?= base_url($category['icon']); ?>">
                    <?= $category['name']; ?>
                </h3>
            </div>
            <div
                class="row row-cols-1 row-cols-md-1 row-cols-lg-2 <?= $CMSNT->site('cot_so_du_ben_phai') == 1 ? 'row-cols-xl-2' : 'row-cols-xl-3'; ?>">
                <?php
                // ⚡ Select chỉ các fields cần thiết thay vì SELECT *
                $i = -1;
                foreach ($CMSNT->get_list_safe(" SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `images`, `flag`, `preview_uid` FROM `products` WHERE `status` = ? AND `category_id` = ? AND `hide_in_shop` = 0 " . (column_exists('products', 'pending') ? " AND `pending` = 0 " : "") . " $order_by ", [1, $category['id']]) as $product): ?>
                    <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
                    <?php
                    if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                        continue;
                    }
                    $i++;
                    // GIỚI HẠN SẢN PHẨM HIỆN THỊ TẠI TRANG CHỦ
                    if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')) {
                        continue;
                    }
                    ?>
                    <div>
                        <div class="feature-card <?= $stock == 0 ? 'product-disable' : ''; ?>">
                            <div class="feature-content">
                                <h6 class="feature-name">
                                    <a
                                        href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a>
                                </h6>
                                <div class="row">
                                    <?php if ($CMSNT->site('product_rating_display') == 1): ?>
                                        <div class="col-6 col-md-12">
                                            <div class="feature-rating">
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="active icofont-star"></i>
                                                <i class="icofont-star"></i>
                                                <a href="product-video.html">(3 Reviews)</a>
                                            </div>
                                        </div>
                                    <?php endif ?>
                                    <div class="col-12 col-md-12">
                                        <label class="label-text feat"><?= __('Kho hàng:'); ?>
                                            <b><?= format_cash($stock); ?></b></label>
                                        <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                                            <label class="label-text order"><?= __('Đã bán:'); ?>
                                                <b><?= format_cash($product['sold']); ?></b></label>
                                        <?php endif ?>
                                    </div>
                                </div>
                                <h6 class="feature-price">
                                    <?= $product['discount'] > 0 ? '<del>' . format_currency($product['price']) . '</del>' : ''; ?><span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                                </h6>
                                <p class="feature-desc"><i class="fa-solid fa-angles-right"></i>
                                    <?= htmlspecialchars($product['short_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="row">
                                    <div class="col">
                                        <a type="button" href="<?= base_url('product/' . $product['slug']); ?>"
                                            class="btn-more"><span><?= __('CHI TIẾT'); ?></span></a>
                                    </div>
                                    <div class="col">
                                        <button id="openModal_<?= $product['id']; ?>"
                                            onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                            class="btn-buy"
                                            data-id="<?= $product['id']; ?>"><?= __('MUA NGAY'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php elseif ($CMSNT->site('type_show_product') == 'BOX_5'): ?>
            <div class="home-heading mb-3">
                <h3>
                    <img src="<?= base_url($category['icon']); ?>">
                    <?= $category['name']; ?>
                </h3>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 row-cols-xl-2">
                <?php
                // ⚡ Select chỉ các fields cần thiết thay vì SELECT *
                $i = -1;
                foreach ($CMSNT->get_list_safe(" SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `images`, `flag`, `preview_uid` FROM `products` WHERE `status` = ? AND `category_id` = ? AND `hide_in_shop` = 0 " . (column_exists('products', 'pending') ? " AND `pending` = 0 " : "") . " $order_by ", [1, $category['id']]) as $product): ?>
                    <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
                    <?php
                    if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                        continue;
                    }
                    $i++;
                    // GIỚI HẠN SẢN PHẨM HIỆN THỊ TẠI TRANG CHỦ
                    if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')) {
                        continue;
                    }
                    ?>
                    <div>
                        <div class="feature-card <?= $stock == 0 ? 'product-disable' : ''; ?>">
                            <div class="feature-content">
                                <div class="row">
                                    <div class="col-3 col-md-3">
                                        <?php
                                        $images = array_filter(explode(PHP_EOL, $product['images'])); // Lọc các dòng trống
                                        if (!empty($images)) {
                                            $firstImage = reset($images); // Lấy hình ảnh đầu tiên
                                        ?>
                                            <div class="product-image-square">
                                                <img src="<?= base_url(dirImageProduct($firstImage)); ?>" alt="<?= __($product['name']); ?>">
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="col-6 col-md-6">
                                        <h6 class="feature-name">
                                            <a href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a>
                                        </h6>
                                        <div class="row">
                                            <div class="col-12 col-md-12">
                                                <label class="label-text feat"><?= __('Kho hàng:'); ?>
                                                    <b><?= format_cash($stock); ?></b></label>
                                                <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                                                    <label class="label-text order"><?= __('Đã bán:'); ?>
                                                        <b><?= format_cash($product['sold']); ?></b></label>
                                                <?php endif ?>
                                                <?php if ($product['discount'] > 0): ?>
                                                    <label class="label-text off" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= __('Đang được giảm giá'); ?>">-<?= $product['discount']; ?>%</label>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 text-end">
                                        <div class="card-price-product-list mb-3">
                                            <h5 class="feature-price">
                                                <span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                                            </h5>
                                        </div>
                                        <button id="openModal_<?= $product['id']; ?>"
                                            onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                            class="btn-buy"
                                            data-id="<?= $product['id']; ?>"><?= __('XEM NGAY'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php elseif ($CMSNT->site('type_show_product') == 'BOX_6'): ?>
            <div class="home-heading mb-3">
                <h3>
                    <img src="<?= base_url($category['icon']); ?>">
                    <?= $category['name']; ?>
                </h3>
            </div>
            <div class="row row-cols-1 row-cols-md-1 row-cols-lg-1 row-cols-xl-1">
                <?php
                // ⚡ Select chỉ các fields cần thiết thay vì SELECT *
                $i = -1;
                foreach ($CMSNT->get_list_safe(" SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `images`, `flag`, `preview_uid` FROM `products` WHERE `status` = ? AND `category_id` = ? AND `hide_in_shop` = 0 " . (column_exists('products', 'pending') ? " AND `pending` = 0 " : "") . " $order_by ", [1, $category['id']]) as $product): ?>
                    <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
                    <?php
                    if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                        continue;
                    }
                    $i++;
                    // GIỚI HẠN SẢN PHẨM HIỆN THỊ TẠI TRANG CHỦ
                    if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')) {
                        continue;
                    }
                    ?>
                    <div>
                        <div class="feature-card <?= $stock == 0 ? 'product-disable' : ''; ?>">
                            <div class="feature-content">
                                <div class="row">
                                    <div class="col-4 col-md-2 col-xl-2">
                                        <?php
                                        $images = array_filter(explode(PHP_EOL, $product['images'])); // Lọc các dòng trống
                                        if (!empty($images)) {
                                            $firstImage = reset($images); // Lấy hình ảnh đầu tiên
                                        ?>
                                            <div class="product-image-box6">
                                                <img src="<?= base_url(dirImageProduct($firstImage)); ?>" alt="<?= __($product['name']); ?>">
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="col-5 col-md-7 col-xl-7">
                                        <h6 class="feature-name">
                                            <a href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a>
                                        </h6>
                                        <div class="row">
                                            <div class="col-12 col-md-12">
                                                <label class="label-text feat"><?= __('Kho hàng:'); ?>
                                                    <b><?= format_cash($stock); ?></b></label>
                                                <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                                                    <label class="label-text order"><?= __('Đã bán:'); ?>
                                                        <b><?= format_cash($product['sold']); ?></b></label>
                                                <?php endif ?>
                                                <?php if ($product['discount'] > 0): ?>
                                                    <label class="label-text off" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= __('Đang được giảm giá'); ?>">-<?= $product['discount']; ?>%</label>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-3 col-md-3 text-end">
                                        <div class="card-price-product-list mb-3">
                                            <h5 class="feature-price">
                                                <span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                                            </h5>
                                        </div>
                                        <button id="openModal_<?= $product['id']; ?>"
                                            onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                            class="btn-buy"
                                            data-id="<?= $product['id']; ?>"><?= __('XEM NGAY'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php elseif ($CMSNT->site('type_show_product') == 'LIST'): ?>
            <div class="home-heading mb-3">
                <h3>
                    <img src="<?= base_url($category['icon']); ?>">
                    <?= $category['name']; ?>
                </h3>
            </div>
            <div class="row row-cols-1 row-cols-md-1 row-cols-lg-1 row-cols-xl-1">
                <?php
                // ⚡ Select chỉ các fields cần thiết thay vì SELECT *
                $i = -1;
                foreach ($CMSNT->get_list_safe(" SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `images`, `flag`, `preview_uid` FROM `products` WHERE `status` = ? AND `category_id` = ? AND `hide_in_shop` = 0 " . (column_exists('products', 'pending') ? " AND `pending` = 0 " : "") . " $order_by ", [1, $category['id']]) as $product): ?>
                    <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
                    <?php
                    if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                        continue;
                    }
                    $i++;
                    // GIỚI HẠN SẢN PHẨM HIỆN THỊ TẠI TRANG CHỦ
                    if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')) {
                        continue;
                    }
                    ?>
                    <div>
                        <div class="feature-card <?= $stock == 0 ? 'product-disable' : ''; ?>">
                            <div class="feature-content">
                                <div class="row">
                                    <div class="col-8 col-md-9">
                                        <h6 class="feature-name">
                                            <a
                                                href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a>
                                        </h6>
                                        <p class="feature-desc"><i class="fa-solid fa-angles-right"></i>
                                            <?= htmlspecialchars($product['short_desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <div class="row">
                                            <?php if ($CMSNT->site('product_rating_display') == 1): ?>
                                                <div class="col-12 col-md-12">
                                                    <div class="feature-rating">
                                                        <i class="active icofont-star"></i>
                                                        <i class="active icofont-star"></i>
                                                        <i class="active icofont-star"></i>
                                                        <i class="active icofont-star"></i>
                                                        <i class="icofont-star"></i>
                                                        <a href="product-video.html">(3 Reviews)</a>
                                                    </div>
                                                </div>
                                            <?php endif ?>
                                            <div class="col-12 col-md-12">
                                                <label class="label-text feat"><?= __('Kho hàng:'); ?>
                                                    <b><?= format_cash($stock); ?></b></label>
                                                <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                                                    <label class="label-text order"><?= __('Đã bán:'); ?>
                                                        <b><?= format_cash($product['sold']); ?></b></label>
                                                <?php endif ?>
                                                <?php if ($product['discount'] > 0): ?>
                                                    <label class="label-text off" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= __('Đang được giảm giá'); ?>">-<?= $product['discount']; ?>%</label>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <div class="card-price-product-list">
                                            <h5 class="feature-price">
                                                <span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                                            </h5>
                                        </div>
                                        <button id="openModal_<?= $product['id']; ?>"
                                            onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                            class="btn-buy"
                                            data-id="<?= $product['id']; ?>"><?= __('MUA NGAY'); ?></button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php elseif ($CMSNT->site('type_show_product') == 'BOX_4'): ?>
            <div class="home-heading mb-3">
                <h3>
                    <img src="<?= base_url($category['icon']); ?>">
                    <?= $category['name']; ?>
                </h3>
            </div>
            <div class="row">
                <?php
                // ⚡ Select chỉ các fields cần thiết thay vì SELECT *
                $i = -1;
                foreach ($CMSNT->get_list_safe(" SELECT `id`, `name`, `slug`, `price`, `discount`, `short_desc`, `sold`, `code`, `supplier_id`, `api_stock`, `images`, `flag`, `preview_uid` FROM `products` WHERE `status` = ? AND `category_id` = ? AND `hide_in_shop` = 0 " . (column_exists('products', 'pending') ? " AND `pending` = 0 " : "") . " $order_by ", [1, $category['id']]) as $product): ?>
                    <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']); ?>
                    <?php
                    if ($CMSNT->site('product_hide_outstock') == 1 && $stock == 0) {
                        continue;
                    }
                    $i++;
                    // GIỚI HẠN SẢN PHẨM HIỆN THỊ TẠI TRANG CHỦ
                    if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')) {
                        continue;
                    }
                    ?>
                    <div class="prod-item col-sm-6 col-md-4 <?= $CMSNT->site('cot_so_du_ben_phai') == 1 ? 'col-xl-4' : 'col-xl-3'; ?> mb-3"
                        data-title="<?= __($product['name']); ?>">
                        <div class="product-box4 ">
                            <div class="product-head-box4">
                                <img src="<?= base_url($category['icon']); ?>" />
                                <h4><?= __($product['name']); ?> </h4>
                            </div>
                            <div class="product-body-box4">
                                <?php
                                $images = array_filter(explode(PHP_EOL, $product['images'])); // Filter out empty lines
                                if (!empty($images)) {
                                    $firstImage = reset($images); // Get the first image
                                ?>
                                    <img class="mb-2" src="<?= base_url(dirImageProduct($firstImage)); ?>" width="100%"
                                        alt="image">
                                <?php
                                }
                                ?>
                                <?php foreach (explode(PHP_EOL, $product['short_desc']) as $bf2): ?>
                                    <p><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($bf2, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endforeach ?>
                            </div>

                            <div class="product-footer-box4">
                                <div class="row">
                                    <div class="col-4 text-center border-end-box4">
                                        <strong><?= __('Quốc gia'); ?></strong>
                                        <?php if (!empty($product['flag'])): ?>
                                            <img src="https://flagcdn.com/w160/<?= strtolower($product['flag']); ?>.png"
                                                alt="product">
                                        <?php endif ?>
                                    </div>
                                    <div class="col-4 text-center border-end-box4">
                                        <strong><?= __('Hiện có'); ?></strong>
                                        <span
                                            class="badge bg-primary rounded-pill"><?= format_cash($stock); ?></span>
                                    </div>
                                    <div class="col-4">
                                        <div class="price-box4">
                                            <?php if ($product['discount'] > 0): ?>
                                                <span><?= format_currency($product['price']); ?></span>
                                                <strong><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></strong>
                                            <?php else: ?>
                                                <b
                                                    class="proce-box4-not-discount"><?= format_currency($product['price']); ?></b>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="product-buttons-box4">
                                <a href="<?= base_url('product/' . $product['slug']); ?>" class="btn more-btn-box4">
                                    <i class="fa-solid fa-circle-info me-1"></i><?= __('Xem chi tiết'); ?>
                                </a>
                                <button type="button" <?= $stock == 0 ? 'disabled' : ''; ?>
                                    id="openModal_<?= $product['id']; ?>"
                                    onclick="openModal(`<?= isset($getUser) ? $getUser['token'] : NULL; ?>`, `<?= $product['id']; ?>`, `<?= $product['preview_uid'] ?? 0; ?>`)"
                                    class="btn buy-btn-box4">
                                    <?php if ($stock == 0): ?>
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= __('HẾT HÀNG'); ?>
                                    <?php else: ?>
                                        <i class="fa-solid fa-cart-shopping me-1"></i><?= __('MUA NGAY'); ?>
                                    <?php endif ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($category_id == '' && $i >= $CMSNT->site('max_show_product_home')): ?>
            <center><a type="button"
                    href="javascript:void(0);"
                    onclick="loadProductsByCategory('<?= htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8'); ?>')"
                    class="btn-more-new mb-3"><?= __('Xem thêm'); ?></a></center>
        <?php endif ?>
    </div>
<?php endforeach ?>