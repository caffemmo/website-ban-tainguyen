<?php

/**
 * Modal hiển thị danh sách UID để khách chọn trước khi mua
 * Layout: Tên SP trên cùng | UID list bên trái | Thanh toán bên phải
 * CSS tự viết hoàn toàn, tránh conflict theme gốc
 */

define("IN_SITE", true);
require_once(__DIR__ . "/../../../config.php");
require_once(__DIR__ . "/../../../libs/db.php");
require_once(__DIR__ . "/../../../libs/lang.php");
require_once(__DIR__ . "/../../../libs/helper.php");

if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../../models/is_user.php');
}

if (!$product = $CMSNT->get_row_safe(" SELECT * FROM `products` WHERE `id` = ? AND `status` = 1 ", [validate_int($_GET['id'])])) {
    die('<script type="text/javascript">if(!alert("' . __('Sản phảm không tồn tại') . '")){location.reload();}</script>');
}

if (($product['preview_uid'] ?? 0) != 1 || $product['supplier_id'] != 0) {
    die('<script type="text/javascript">if(!alert("' . __('Sản phẩm không hỗ trợ xem trước UID') . '")){location.reload();}</script>');
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$total_stock = $CMSNT->get_row_safe("SELECT COUNT(id) as total FROM `product_stock` WHERE `product_code` = ?", [$product['code']])['total'];
$stock_list = $CMSNT->get_list_safe("SELECT `id`, `uid` FROM `product_stock` WHERE `product_code` = ? ORDER BY `id` ASC LIMIT ?, ?", [$product['code'], $offset, $per_page]);
$total_pages = ceil($total_stock / $per_page);
$unit_price = $product['price'] - $product['price'] * $product['discount'] / 100;
?>

<div class="pv-container" id="previewUidContainer">
    <!-- Nút đóng -->
    <button class="pv-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>

    <!-- Header -->
    <div class="pv-header">
        <div class="pv-title"><?= __($product['name']); ?></div>
        <div>
            <span class="pv-badge pv-badge-green"><?= __('Kho hàng:'); ?> <?= format_cash($total_stock); ?></span>
            <span class="pv-badge pv-badge-blue"><?= __('Giá:'); ?> <?= format_currency($unit_price); ?>/UID</span>
            <?php if ($product['discount'] > 0): ?>
                <span class="pv-badge pv-badge-red">-<?= $product['discount']; ?>%</span>
            <?php endif; ?>
        </div>
        <?php if (!isset($getUser) || $getUser['discount'] == 0): ?>
            <?php if ($CMSNT->num_rows_safe(" SELECT * FROM product_discount WHERE product_id = ? ", [$product['id']]) > 0): ?>
                <div style="margin-top:10px;padding:8px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:13px;">
                    <span><i class="fa-solid fa-fire-flame-simple" style="color:red;"></i> <b>Hot Deal:</b></span><br>
                    <?php foreach ($CMSNT->get_list_safe(" SELECT * FROM product_discount WHERE product_id = ? ", [$product['id']]) as $product_discount): ?>
                        <span> * <?= __('Mua'); ?> >= <b style="color:#2563eb;"><?= format_cash($product_discount['min']); ?></b> <?= __('tài khoản giảm'); ?> <b style="color:#dc2626;"><?= $product_discount['discount']; ?>%</b></span><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <input type="hidden" id="preview_product_id" value="<?= $product['id']; ?>">
    <input type="hidden" id="preview_token" value="<?= isset($getUser) ? $getUser['token'] : '' ?>">

    <?php if ($total_stock > 0): ?>
        <div class="pv-body">
            <!-- CỘT TRÁI: Danh sách UID -->
            <div class="pv-left">
                <div class="pv-toolbar">
                    <label>
                        <i class="fa-solid fa-hand-pointer" style="color:#2563eb;margin-right:4px;"></i><?= __('Vui lòng chọn tài khoản bạn muốn mua:'); ?>
                    </label>
                    <span class="pv-page-info"><?= __('Trang'); ?> <?= $page; ?>/<?= $total_pages; ?></span>
                </div>

                <div class="pv-uid-list" id="uidListContainer">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;text-align:center;"><input type="checkbox" id="selectAllUid"></th>
                                <th><?= __('Tài khoản'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock_list as $stock): ?>
                                <tr class="uid-row" data-stock-id="<?= $stock['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="uid-checkbox" value="<?= $stock['id']; ?>">
                                    </td>
                                    <td>
                                        <span class="pv-uid-text"><?= htmlspecialchars($stock['uid'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pv-pagination">
                        <?php
                        $start_page = max(1, $page - 4);
                        $end_page = min($total_pages, $start_page + 9);
                        if ($end_page - $start_page < 9) $start_page = max(1, $end_page - 9);
                        ?>
                        <?php if ($start_page > 1): ?>
                            <button onclick="loadPreviewPage(1)">1</button>
                            <?php if ($start_page > 2): ?><span class="pv-dots">...</span><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <button class="<?= $p == $page ? 'pv-page-active' : ''; ?>" onclick="loadPreviewPage(<?= $p; ?>)"><?= $p; ?></button>
                        <?php endfor; ?>
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?><span class="pv-dots">...</span><?php endif; ?>
                            <button onclick="loadPreviewPage(<?= $total_pages; ?>)"><?= $total_pages; ?></button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CỘT PHẢI: Thanh toán -->
            <div class="pv-right">
                <div class="pv-payment">
                    <div class="pv-payment-title">
                        <i class="fa-solid fa-receipt"></i> <?= __('Thanh toán'); ?>
                    </div>

                    <div class="pv-payment-row">
                        <span class="pv-label"><?= __('Số dư:'); ?></span>
                        <span class="pv-value-green"><?= isset($getUser) ? format_currency($getUser['money']) : 0; ?></span>
                    </div>

                    <div class="pv-payment-row">
                        <span class="pv-label"><?= __('Đã chọn:'); ?></span>
                        <span class="pv-selected-badge"><span id="selectedCount">0</span> <?= __('tài khoản'); ?></span>
                    </div>

                    <div style="margin-bottom:14px;">
                        <input class="pv-coupon-input" type="text" id="preview_coupon" placeholder="<?= __('Nhập mã giảm giá'); ?>">
                    </div>

                    <hr class="pv-divider">

                    <div class="pv-payment-row">
                        <span class="pv-label"><?= __('Thành tiền:'); ?></span>
                        <span class="pv-value" id="preview_money">0</span>
                    </div>

                    <div class="pv-payment-row" id="preview_discount_row" style="display:none;">
                        <span class="pv-label"><?= __('Giảm giá:'); ?></span>
                        <span class="pv-value-red" id="preview_discount">-0</span>
                    </div>

                    <?php if ($CMSNT->site('tax_vat') > 0): ?>
                        <div class="pv-payment-row" id="preview_vat_row" style="display:none;">
                            <span class="pv-label">VAT (<?= $CMSNT->site('tax_vat'); ?>%)</span>
                            <span class="pv-value" id="preview_vat">0</span>
                        </div>
                    <?php endif; ?>

                    <hr class="pv-divider-bold">

                    <div class="pv-payment-row">
                        <span style="font-weight:700;font-size:14px;color:#111827;"><?= __('Tổng cộng:'); ?></span>
                        <span class="pv-value-blue" id="preview_pay">0</span>
                    </div>

                    <?php if (isset($getUser)): ?>
                        <button class="pv-btn-pay" id="btnBuyPreview" onclick="buyPreviewUid()" disabled>
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span><?= __('THANH TOÁN'); ?></span>
                        </button>
                    <?php else: ?>
                        <button class="pv-btn-login" onclick="window.location.href='<?= base_url('client/login'); ?>';">
                            <i class="fa-solid fa-sign-in"></i>
                            <span><?= __('ĐĂNG NHẬP'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="pv-empty">
            <i class="fa-solid fa-box-open"></i>
            <p style="font-size:16px;font-weight:600;"><?= __('Sản phẩm hiện đã hết hàng'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
    if (!window._previewSelectedIds) {
        window._previewSelectedIds = new Set();
    }

    $(document).ready(function() {
        $('.uid-checkbox').each(function() {
            if (window._previewSelectedIds.has($(this).val())) {
                $(this).prop('checked', true);
                $(this).closest('tr').addClass('pv-selected');
            }
        });
        updateSelectedCount();
        recalcPreviewPayment();
    });

    $('.uid-row').on('click', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) return;
        var cb = $(this).find('.uid-checkbox');
        cb.prop('checked', !cb.prop('checked')).trigger('change');
    });

    $('.uid-checkbox').on('change', function() {
        var id = $(this).val();
        if ($(this).is(':checked')) {
            window._previewSelectedIds.add(id);
            $(this).closest('tr').addClass('pv-selected');
        } else {
            window._previewSelectedIds.delete(id);
            $(this).closest('tr').removeClass('pv-selected');
        }
        updateSelectAll();
        updateSelectedCount();
        recalcPreviewPayment();
    });

    $('#selectAllUid').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.uid-checkbox').each(function() {
            $(this).prop('checked', isChecked).trigger('change');
        });
    });

    function updateSelectAll() {
        var total = $('.uid-checkbox').length;
        var checked = $('.uid-checkbox:checked').length;
        $('#selectAllUid').prop('checked', total > 0 && total === checked);
    }

    function updateSelectedCount() {
        var count = window._previewSelectedIds.size;
        $('#selectedCount').text(count);
        $('#btnBuyPreview').prop('disabled', count === 0);
    }

    var _recalcTimer = null;

    function recalcPreviewPayment() {
        clearTimeout(_recalcTimer);
        _recalcTimer = setTimeout(function() {
            _doRecalcPreviewPayment();
        }, 300);
    }

    function _doRecalcPreviewPayment() {
        var amount = window._previewSelectedIds.size;
        if (amount === 0) {
            $('#preview_money').text('0');
            $('#preview_discount').text('-0');
            $('#preview_pay').text('0');
            $('#preview_discount_row').css('display', 'none');
            if ($('#preview_vat_row').length) $('#preview_vat_row').css('display', 'none');
            return;
        }
        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/product.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'total_payment',
                id: $('#preview_product_id').val(),
                amount: amount,
                coupon: $('#preview_coupon').val(),
                token: $('#preview_token').val()
            },
            success: function(data) {
                if (data.status == 'success') {
                    $('#preview_money').html(data.money);
                    $('#preview_pay').html(data.pay);
                    if (data.discount_number > 0) {
                        $('#preview_discount').html('-' + data.discount);
                        $('#preview_discount_row').css('display', 'flex');
                    } else {
                        $('#preview_discount_row').css('display', 'none');
                    }
                    if (data.tax_vat > 0 && $('#preview_vat_row').length) {
                        $('#preview_vat').html(data.price_vat);
                        $('#preview_vat_row').css('display', 'flex');
                    } else if ($('#preview_vat_row').length) {
                        $('#preview_vat_row').css('display', 'none');
                    }
                }
            }
        });
    }

    $('#preview_coupon').on('change', function() {
        recalcPreviewPayment();
    });

    function loadPreviewPage(page) {
        var pid = window._previewProductId || $('#preview_product_id').val();
        var tok = window._previewToken || $('#preview_token').val();
        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/modal/view-product-preview.php'); ?>",
            method: "GET",
            data: {
                id: pid,
                token: tok,
                page: page
            },
            success: function(data) {
                $("#modalContent").html(data);
            }
        });
    }

    function buyPreviewUid() {
        var selectedIds = Array.from(window._previewSelectedIds);
        if (selectedIds.length === 0) return;

        // Lưu lại productId và token trước khi thay HTML
        window._previewProductId = $('#preview_product_id').val();
        window._previewToken = $('#preview_token').val();

        $('#btnBuyPreview').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>').prop('disabled', true);

        $.ajax({
            url: "<?= BASE_URL("ajaxs/client/product.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'buyProductByUid',
                id: $('#preview_product_id').val(),
                stock_ids: selectedIds,
                coupon: $('#preview_coupon').val(),
                token: $('#preview_token').val()
            },
            complete: function(xhr) {
                var result;
                try {
                    result = xhr.responseJSON || JSON.parse(xhr.responseText);
                } catch (e) {
                    result = null;
                }
                if (!result) result = {
                    status: 'error',
                    msg: '<?= __('Không nhận được phản hồi'); ?>'
                };

                if (result.status == 'success') {
                    window._previewSelectedIds = new Set();
                    var accounts = Array.isArray(result.data) ? result.data : [];
                    var accountsText = accounts.join('\n') || '<?= __('Vui lòng xem trong Lịch sử đơn hàng.'); ?>';

                    // Nút đóng modal dạng pill trong màn hình thanh toán thành công - chỉ hiện ở mobile (≤1100px), desktop đã có nút đóng riêng nên bỏ inline display để CSS gốc tự ẩn
                    var html = '<div class="pv-container" style="position:relative;">' +
                        '<button class="modal-close-mobile" data-bs-dismiss="modal" aria-label="Close">' +
                        '<i class="fa-solid fa-xmark"></i> <?= __('Đóng'); ?></button>' +
                        '<div style="text-align:center;padding:20px 0 10px;">' +
                        '<div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#d1fae5,#a7f3d0);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;"><i class="fa-solid fa-check" style="font-size:28px;color:#059669;"></i></div>' +
                        '<h3 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 8px;"><?= __('Thanh toán thành công!'); ?></h3>' +
                        '<span style="display:inline-block;background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;">#' + (result.trans_id || '') + '</span>' +
                        '</div>' +
                        '<div id="purchasedAccounts" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px;max-height:250px;overflow:auto;font-family:monospace;white-space:pre-wrap;line-height:1.6;font-size:13px;color:#1f2937;margin:16px 0;">' + accountsText + '</div>' +
                        '<div style="display:flex;gap:10px;flex-wrap:wrap;">' +
                        '<button id="btnCopyAccounts" style="flex:1;min-width:100px;height:42px;border:none;border-radius:10px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:14px;"><i class="fa-regular fa-copy"></i><?= __('Sao chép'); ?></button>' +
                        '<button id="btnViewOrderDetail" style="flex:1;min-width:100px;height:42px;border:none;border-radius:10px;background:#0ea5e9;color:#fff;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:14px;"><i class="fa-solid fa-file-invoice"></i><?= __('Chi tiết'); ?></button>' +
                        '<button id="btnBuyMorePreview" style="flex:1;min-width:100px;height:42px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:14px;"><i class="fa-solid fa-cart-plus"></i><?= __('Mua thêm'); ?></button>' +
                        '</div>' +
                        '</div>';

                    document.getElementById('previewUidContainer').innerHTML = html;

                    document.getElementById('btnCopyAccounts').addEventListener('click', function() {
                        var text = document.getElementById('purchasedAccounts').innerText;
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(function() {
                                showMessage('<?= __('Đã sao chép'); ?>', 'success');
                            });
                        } else {
                            var r = document.createRange();
                            r.selectNode(document.getElementById('purchasedAccounts'));
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(r);
                            document.execCommand('copy');
                            window.getSelection().removeAllRanges();
                            showMessage('<?= __('Đã sao chép'); ?>', 'success');
                        }
                    });
                    document.getElementById('btnViewOrderDetail').addEventListener('click', function() {
                        window.location.href = '<?= base_url('product-order/'); ?>' + (result.trans_id || '');
                    });
                    document.getElementById('btnBuyMorePreview').addEventListener('click', function() {
                        loadPreviewPage(1);
                    });
                } else {
                    Swal.fire({
                        title: '<?= __('Thất bại!'); ?>',
                        html: result.msg || '<?= __('Đã xảy ra lỗi'); ?>',
                        icon: 'error'
                    });
                }
                $('#btnBuyPreview').html('<i class="fa-solid fa-cart-shopping"></i> <span><?= __('THANH TOÁN'); ?></span>').prop('disabled', false);
            }
        });
    }
</script>