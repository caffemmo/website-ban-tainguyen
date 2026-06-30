<?php

/**
 * Modal hiển thị chi tiết sản phẩm và form mua hàng
 * File: ajaxs/client/modal/view-product.php
 * Chức năng: Hiển thị thông tin sản phẩm, tính toán giá, áp dụng giảm giá và thuế VAT
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../../../config.php");
require_once(__DIR__ . "/../../../libs/db.php");
require_once(__DIR__ . "/../../../libs/lang.php");
require_once(__DIR__ . "/../../../libs/helper.php");

// Kiểm tra đăng nhập user
if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../../models/is_user.php');
}

// Lấy thông tin sản phẩm từ database
if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? AND `status` = 1 ", [validate_int($_GET['id'])])) {
    die('<script type="text/javascript">if(!alert("' . __('Sản phảm không tồn tại') . '")){location.reload();}</script>');
}

// Tính số lượng tồn kho: nếu có supplier thì lấy từ API, không thì lấy từ hàm getStock()
$stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);
?>
<!-- Nút đóng modal gốc: CSS tự ẩn trên mobile (≤1100px) -->
<button class="modal-close icofont-close" data-bs-dismiss="modal"></button>
<div class="product-view" style="position:relative;">
    <!-- Nút đóng modal dành riêng cho mobile (CSS hiện khi ≤1100px) -->
    <button class="modal-close-mobile" data-bs-dismiss="modal" aria-label="Close">
        <i class="fa-solid fa-xmark"></i> <?= __('Đóng'); ?>
    </button>
    <div class="row">
        <div class="col-md-6 col-lg-6">
            <div class="view-details">
                <h3 class="view-name"><a
                        href="<?= base_url('product/' . $product['slug']); ?>"><?= __($product['name']); ?></a></h3>
                <div class="view-meta">
                    <p><label class="label-text feat"><?= __('Kho hàng:'); ?>
                            <strong><?= format_cash($stock); ?></strong></label>
                        <?php if ($CMSNT->site('product_sold_display') == 1): ?>
                            <label class="label-text order"><?= __('Đã bán:'); ?>
                                <strong><?= format_cash($product['sold']); ?></strong></label>
                        <?php endif ?>
                    </p>
                </div>
                <?php if ($CMSNT->site('product_rating_display') == 1): ?>
                    <div class="view-rating">
                        <i class="active icofont-star"></i>
                        <i class="active icofont-star"></i>
                        <i class="active icofont-star"></i>
                        <i class="active icofont-star"></i><i class="icofont-star"></i>
                        <a href="product-video.html">(3 reviews)</a>
                    </div>
                <?php endif ?>

                <h3 class="view-price">
                    <?= $product['discount'] > 0 ? '<del>' . format_currency($product['price']) . '</del>' : ''; ?><span><?= format_currency($product['price'] - $product['price'] * $product['discount'] / 100); ?></span>
                </h3>
                <?php // KO ÁP DỤNG CHO USER ĐÃ ĐƯỢC CHIẾT KHẤU RIÊNG
                if (!isset($getUser) || $getUser['discount'] == 0): ?>
                    <?php if ($CMSNT->num_rows_safe(" SELECT * FROM product_discount WHERE product_id = ? ", [$product['id']]) > 0): ?>
                        <div class="mb-3 card-hot-deal">
                            <span><i class="fa-solid fa-fire-flame-simple" style="color:red;"></i> Hot Deal: </span><br>
                            <?php foreach ($CMSNT->get_list_safe(" SELECT * FROM product_discount WHERE product_id = ? ", [$product['id']]) as $product_discount): ?>
                                <span> * <?= __('Mua'); ?> >= <b style="color:blue;"><?= format_cash($product_discount['min']); ?></b>
                                    <?= __('tài khoản giảm'); ?> <b style="color:red;"><?= $product_discount['discount']; ?>%</b></span>
                                <br>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                <?php endif ?>
                <p class="view-desc"><?= str_replace(PHP_EOL, '<br>', htmlspecialchars($product['short_desc'], ENT_QUOTES, 'UTF-8')); ?></p>
                <div class="view-list-group">
                    <label class="view-list-title"><?= __('Chia sẻ:'); ?></label>
                    <ul class="view-share-list">
                        <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?= base_url('product/' . $product['slug']); ?>"
                                title="Facebook"><i class="fa-brands fa-facebook"></i></a></li>
                        <li><a href="https://twitter.com/intent/tweet?url=<?= base_url('product/' . $product['slug']); ?>"
                                title="Twitter"><i class="fa-brands fa-square-x-twitter"></i></a></li>
                        <li><a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= base_url('product/' . $product['slug']); ?>"
                                title="Linkedin"><i class="fa-brands fa-linkedin"></i></a></li>
                        <li><a href="https://www.instagram.com/?url=<?= base_url('product/' . $product['slug']); ?>"
                                title="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6">
            <div class="view-details">
                <table class="table fs-sm mb-0">
                    <tbody>
                        <tr>
                            <td colspan="2" align="center"><strong><?= __('THÔNG TIN MUA HÀNG'); ?></strong></td>
                        </tr>
                        <tr>
                        <tr>
                            <td><?= __('Số dư của tôi:'); ?></td>
                            <td class="text-right"><strong
                                    class="text-wallet"><?= isset($getUser) ? format_currency($getUser['money']) : 0; ?></strong>
                            </td>
                        </tr>
                        <td><?= __('Số lượng cần mua:'); ?> (<span class="text-danger">*</span>)</td>
                        <td>
                            <div class="product-action" style="display: flex;">
                                <input type="hidden" id="product_id" value="<?= $product['id']; ?>">
                                <input type="hidden" id="token" value="<?= isset($getUser) ? $getUser['token'] : '' ?>">
                                <button class="action-minus1" title="Quantity Minus"><i
                                        class="fa-solid fa-minus"></i></button>
                                <input class="action-input" onkeyup="totalPayment()" title="Quantity Number"
                                    type="number" id="amount" value="1">
                                <button class="action-plus1" title="Quantity Plus"><i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                        </td>
                        </tr>
                        <tr>
                            <td><?= __('Mã giảm giá:'); ?></td>
                            <td><input class="form-control-view-product" onchange="totalPayment()" type="text"
                                    id="coupon" placeholder="<?= __('Nhập mã giảm giá nếu có'); ?>"></td>
                        </tr>
                        <tr>
                            <td><?= __('Thành tiền:'); ?></td>
                            <td class="text-right"><strong id="into_money">0</strong></td>
                        </tr>
                        <!-- Dòng số tiền giảm: chỉ hiển thị khi có giảm giá (discount_number > 0) -->
                        <tr style="display: none;" id="into_discount_row">
                            <td><?= __('Số tiền giảm:'); ?></td>
                            <td class="text-right"><del style="color: red;" id="into_discount">0</del></td>
                        </tr>
                        <!-- Dòng thuế VAT: chỉ hiển thị khi có cài đặt thuế VAT (tax_vat > 0) -->
                        <tr style="display: none;" id="into_vat_row">
                            <td><?= __('Thuế VAT'); ?> (<?= $CMSNT->site('tax_vat'); ?>%)</td>
                            <td class="text-right"><strong id="into_price_vat">0</strong></td>
                        </tr>
                        <tr>
                            <td><?= __('Tổng tiền thanh toán:'); ?></td>
                            <td class="text-right"><strong style="color: blue;" id="into_pay">0</strong></td>
                        </tr>
                    </tbody>
                </table>
                <div class="view-add-group">
                    <?php if (isset($getUser)): ?>
                        <button class="btn-buy" id="btnBuy" onclick="buyProduct()">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span><?= __('THANH TOÁN'); ?></span>
                        </button>
                    <?php else: ?>
                        <button class="btn-buy" type="button"
                            onclick="window.location.href='<?= base_url('client/login'); ?>';">
                            <i class="fa-solid fa-sign-in"></i>
                            <span><?= __('ĐĂNG NHẬP'); ?></span>
                        </button>
                    <?php endif ?>
                </div>
                <div class="view-action-group">
                    <?php
                    $isButtonFavorite = false;
                    if (isset($getUser['id'])) {
                        $isButtonFavorite = $CMSNT->get_row_safe(" SELECT * FROM `favorites` WHERE `user_id` = ? AND `product_id` = ? ", [$getUser['id'], $product['id']]);
                    }
                    ?>
                    <input type="checkbox" <?= $isButtonFavorite == true ? 'checked="checked"' : ''; ?>
                        onclick="addFavorite()" id="favorite" class="input_favorite" name="favorite-checkbox"
                        value="favorite-button">
                    <label for="favorite" class="label_favorite">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="feather feather-heart">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                            </path>
                        </svg>
                        <div class="action">
                            <span class="option-1"><?= __('Thêm vào mục yêu thích'); ?></span>
                            <span class="option-2"><?= __('Đã thêm vào mục yêu thích'); ?></span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    /**
     * Hàm xử lý mua sản phẩm
     * Gửi AJAX request đến ajaxs/client/product.php với action 'buyProduct'
     */
    function buyProduct() {
        // Lưu UI gốc trước khi đổi trạng thái nút (để khi "Mua thêm" khôi phục đúng)
        if (!window.__originalProductViewHTML) {
            var pv0 = document.querySelector('.product-view');
            if (pv0) window.__originalProductViewHTML = pv0.innerHTML;
        }
        // Thay đổi text button và disable trong quá trình xử lý
        $('#btnBuy').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>').prop(
            'disabled',
            true);

        // Gửi AJAX request mua sản phẩm
        $.ajax({
            url: "<?= BASE_URL("ajaxs/client/product.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'buyProduct',
                id: $("#product_id").val(), // ID sản phẩm
                amount: $("#amount").val(), // Số lượng mua
                coupon: $("#coupon").val(), // Mã giảm giá (nếu có)
                token: $("#token").val() // Token của User
            },
            complete: function(xhr, status) {
                // Xử lý response bất kể HTTP code (200, 400, 500, etc.)
                var result;

                // Parse response JSON
                try {
                    if (xhr.responseJSON) {
                        result = xhr.responseJSON;
                    } else if (xhr.responseText) {
                        result = JSON.parse(xhr.responseText);
                    } else {
                        result = {
                            status: 'error',
                            msg: '<?= __('Không nhận được phản hồi từ server'); ?>'
                        };
                    }
                } catch (e) {
                    result = {
                        status: 'error',
                        msg: '<?= __('Lỗi phân tích dữ liệu từ server'); ?>'
                    };
                }

                // Xử lý theo status trong response
                if (result.status == 'success') {

                    // Ghép danh sách tài khoản đã mua
                    var accounts = Array.isArray(result.data) ? result.data : [];
                    var accountsText = accounts.join('\n');
                    if (!accountsText) {
                        accountsText = '<?= __('Chưa nhận được dữ liệu tài khoản. Vui lòng xem trong phần Lịch sử đơn hàng.'); ?>';
                    }

                    // HTML chi tiết đơn hàng hiển thị ngay trong modal hiện tại (UI đẹp, dễ nhìn)
                    // Nút đóng modal dạng pill - chỉ dành cho mobile (≤1100px); desktop đã có .modal-close ở góc nên bỏ inline display để CSS gốc tự ẩn trên desktop
                    var successCloseBtn = '<button class="modal-close-mobile" data-bs-dismiss="modal" aria-label="Close">' +
                        '<i class="fa-solid fa-xmark"></i> <?= __('Đóng'); ?></button>';

                    var successHTML = '' +
                        '<style>\n' +
                        '.vp-wrap{padding:12px 12px 18px;}\n' +
                        '.vp-card{padding:18px;}\n' +
                        '.vp-header{display:flex;align-items:center;gap:12px;margin-bottom:12px;justify-content:center;}\n' +
                        '.vp-check{width:54px;height:54px;border-radius:50%;background:#e8f7ee;border:2px solid #c6f1d9;display:flex;align-items:center;justify-content:center;color:#22c55e;font-size:28px;}\n' +
                        '.vp-title{font-size:20px;font-weight:700;margin:0;text-align:center;}\n' +
                        '.vp-badge{display:inline-block;background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;border-radius:999px;padding:4px 10px;font-size:12px;margin:6px auto 14px;}' + '\n' +
                        '.vp-acc{background:#0f172a0a;border:1px solid #e5e7eb;border-radius:10px;padding:12px;height:230px;overflow:auto;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;white-space:pre-wrap;line-height:1.5;color:#111827;}\n' +
                        '.vp-actions{display:flex;flex-direction:column;gap:10px;margin-top:14px;}\n' +
                        '.vp-btn{display:flex;align-items:center;justify-content:center;gap:8px;border:none;border-radius:10px;height:42px;font-weight:600;cursor:pointer;}\n' +
                        '.vp-btn-primary{background:#2563eb;color:#fff;}\n' +
                        '.vp-btn-info{background:#0ea5e9;color:#fff;}\n' +
                        '.vp-btn-ghost{background:#f3f4f6;color:#111827;}\n' +
                        '@media(min-width:768px){.vp-actions{flex-direction:row;}.vp-btn{min-width:33%;}}\n' +
                        '</style>' +
                        successCloseBtn +
                        '<div class="vp-wrap">' +
                        '<div class="vp-card">' +
                        '<div class="vp-header">' +
                        '<div class="vp-check"><i class="fa-solid fa-check"></i></div>' +
                        '</div>' +
                        '<h3 class="vp-title"><?= __('Thanh toán thành công !'); ?></h3>' +
                        '<div style="text-align:center">' +
                        '<span class="vp-badge"><?= __('Chi tiết đơn hàng'); ?> #' + (result.trans_id || '') + '</span>' +
                        '</div>' +
                        '<div id="purchasedAccounts" class="vp-acc">' + accountsText + '</div>' +
                        '<div class="vp-actions">' +
                        '<button id="btnCopyAccounts" class="vp-btn vp-btn-primary"><i class="fa-regular fa-copy"></i><span><?= __('Sao chép'); ?></span></button>' +
                        '<button id="btnViewOrderDetail" class="vp-btn vp-btn-info"><i class="fa-solid fa-file-invoice"></i><span><?= __('Xem chi tiết đơn hàng'); ?></span></button>' +
                        '<button id="btnBuyMoreInline" class="vp-btn vp-btn-ghost"><i class="fa-solid fa-cart-plus"></i><span><?= __('Mua thêm'); ?></span></button>' +
                        '</div>' +
                        '</div>' +
                        '</div>';

                    var container = document.querySelector('.product-view');
                    if (container) {
                        container.innerHTML = successHTML;

                        // Bind nút copy
                        var textarea = document.getElementById('purchasedAccounts');
                        var btnCopy = document.getElementById('btnCopyAccounts');
                        if (btnCopy && textarea) {
                            btnCopy.addEventListener('click', function() {
                                try {
                                    var text = textarea.innerText || textarea.textContent || '';
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(text).then(function() {
                                            showMessage('<?= __('Đã sao chép vào clipboard'); ?>', 'success');
                                            btnCopy.classList.add('copied');
                                            btnCopy.querySelector('span').innerText = '<?= __('Đã sao chép'); ?>';
                                        });
                                    } else {
                                        var r = document.createRange();
                                        r.selectNode(textarea);
                                        var s = window.getSelection();
                                        s.removeAllRanges();
                                        s.addRange(r);
                                        document.execCommand('copy');
                                        s.removeAllRanges();
                                        showMessage('<?= __('Đã sao chép vào clipboard'); ?>', 'success');
                                        btnCopy.classList.add('copied');
                                        btnCopy.querySelector('span').innerText = '<?= __('Đã sao chép'); ?>';
                                    }
                                } catch (e) {
                                    showMessage('<?= __('Không thể sao chép, vui lòng chọn thủ công'); ?>', 'error');
                                }
                            });
                        }

                        // Bind nút xem chi tiết đơn
                        var btnView = document.getElementById('btnViewOrderDetail');
                        if (btnView) {
                            btnView.addEventListener('click', function() {
                                window.location.href = '<?= base_url('product-order/'); ?>' + (result.trans_id || '');
                            });
                        }

                        // Bind nút mua thêm -> khôi phục UI cũ và gắn lại sự kiện
                        var btnMore = document.getElementById('btnBuyMoreInline');
                        if (btnMore) {
                            btnMore.addEventListener('click', function() {
                                if (window.__originalProductViewHTML) {
                                    container.innerHTML = window.__originalProductViewHTML;
                                    // Reset lại nút mua về trạng thái mặc định
                                    var btn = document.getElementById('btnBuy');
                                    if (btn) {
                                        btn.innerHTML = '<i class="fa-solid fa-cart-shopping"></i> <span><?= __('THANH TOÁN'); ?></span>';
                                        btn.disabled = false;
                                    }
                                    // Gắn lại sự kiện tăng/giảm và tính tiền
                                    bindBuyQtyEvents();
                                    totalPayment();
                                } else {
                                    location.reload();
                                }
                            });
                        }
                    }
                } else {
                    // Hiển thị thông báo lỗi (bất kể HTTP code)
                    var errorMsg = result.msg || '<?= __('Đã xảy ra lỗi, vui lòng thử lại'); ?>';
                    var httpCode = xhr.status || 'Unknown';

                    // Có thể hiển thị thêm HTTP code để debug
                    Swal.fire({
                        title: '<?= __('Thất bại!'); ?>',
                        html: errorMsg,
                        icon: 'error'
                    });
                }

                // Khôi phục lại button về trạng thái ban đầu (luôn chạy)
                $('#btnBuy').html(
                    '<i class="fa-solid fa-cart-shopping"></i> <span><?= __('THANH TOÁN'); ?></span>').prop(
                    'disabled',
                    false);
            }
        });
    }
</script>

<script>
    /**
     * Hàm tính toán tổng tiền thanh toán
     * Gọi AJAX để tính toán giá, giảm giá, thuế VAT dựa trên số lượng và mã giảm giá
     * Tự động cập nhật giao diện hiển thị các dòng thông tin
     */
    function totalPayment() {
        // Lấy các giá trị từ form
        const product_id = $("#product_id").val();
        const amount = $("#amount").val();
        const coupon = $("#coupon").val();
        const token = $("#token").val();

        // Gửi AJAX request tính toán tổng tiền
        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/product.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'total_payment',
                id: product_id, // ID sản phẩm
                amount: amount, // Số lượng
                coupon: coupon, // Mã giảm giá
                token: token // Token user để áp dụng chiết khấu
            },
            success: function(data) {
                if (data.status == 'success') {
                    // Lấy các element để cập nhật giá trị
                    const into_money = $("#into_money");
                    const into_discount = $("#into_discount");
                    const into_discount_row = $("#into_discount_row");
                    const into_pay = $("#into_pay");
                    const into_vat_row = $("#into_vat_row");
                    const into_price_vat = $("#into_price_vat");

                    // Cập nhật các giá trị hiển thị
                    into_money.html(data.money); // Thành tiền (chưa giảm giá)
                    into_discount.html(data.discount); // Số tiền được giảm
                    into_pay.html(data.pay); // Tổng tiền thanh toán cuối cùng

                    // Logic hiển thị dòng số tiền giảm
                    // Chỉ hiển thị khi có giảm giá thực sự (discount_number > 0)
                    if (data.discount_number > 0) {
                        into_discount_row.show();
                    } else {
                        into_discount_row.hide();
                    }

                    // Logic hiển thị dòng thuế VAT
                    // Chỉ hiển thị khi có cài đặt thuế VAT (tax_vat > 0)
                    if (data.tax_vat > 0) {
                        into_price_vat.html(data.price_vat);
                        into_vat_row.show();
                    } else {
                        into_vat_row.hide();
                    }

                    // Hiển thị thông báo khi áp dụng giảm giá thành công
                    if (data.discount_number != 0) {
                        showMessage('<?= __('Áp dụng giảm giá thành công!'); ?>', 'success');
                    }
                } else {
                    // Hiển thị lỗi nếu có
                    showMessage(data.msg, data.status);
                }
            },
            error: function() {
                showMessage('<?= __('Vui lòng liên hệ Developer'); ?>', 'error');
            }
        });
    }

    // Gọi hàm tính toán ngay khi load modal để hiển thị giá ban đầu
    totalPayment();
</script>

<script>
    /**
     * Xử lý sự kiện click nút tăng/giảm số lượng
     * Tự động tính lại tổng tiền khi thay đổi số lượng
     */
    function bindBuyQtyEvents() {
        const inputElement = document.querySelector('#amount');
        const plusButton = document.querySelector('.action-plus1');
        const minusButton = document.querySelector('.action-minus1');
        if (!inputElement || !plusButton || !minusButton) return;

        plusButton.addEventListener('click', function() {
            let currentValue = parseInt(inputElement.value);
            currentValue++;
            inputElement.value = currentValue;
            totalPayment();
        });

        minusButton.addEventListener('click', function() {
            let currentValue = parseInt(inputElement.value);
            currentValue = Math.max(1, currentValue - 1);
            inputElement.value = currentValue;
            totalPayment();
        });
    }

    // Gắn sự kiện lần đầu khi modal được load
    bindBuyQtyEvents();
</script>

<script>
    /**
     * Hàm thêm/xóa sản phẩm khỏi danh sách yêu thích
     * Toggle trạng thái yêu thích của sản phẩm
     */
    function addFavorite() {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/client/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'toggleFavorite',
                id: $("#product_id").val(), // ID sản phẩm
                token: $("#token").val() // Token user
            },
            success: function(data) {
                if (data.status == 'success') {
                    if (data.button == true) {
                        // Đã thêm vào yêu thích
                        $("#btnAddFavorite").hide();
                        $("#btnRemoveFavorite").show();

                        // Cập nhật số lượng yêu thích trên header (tăng 1)
                        var numFavoritesElement = document.getElementById("numFavorites");
                        var currentValue = parseInt(numFavoritesElement.textContent);
                        var newValue = currentValue + 1;
                        numFavoritesElement.textContent = newValue;
                    } else {
                        // Đã xóa khỏi yêu thích
                        $("#btnAddFavorite").show();
                        $("#btnRemoveFavorite").hide();

                        // Cập nhật số lượng yêu thích trên header (giảm 1)
                        var numFavoritesElement = document.getElementById("numFavorites");
                        var currentValue = parseInt(numFavoritesElement.textContent);
                        var newValue = currentValue - 1;
                        numFavoritesElement.textContent = newValue;
                    }
                } else {
                    // Hiển thị lỗi nếu có
                    showMessage(data.msg, 'error');
                }
            },
            error: function() {
                showMessage('<?= __('Vui lòng liên hệ Developer'); ?>', 'error');
            }
        });
    }
</script>