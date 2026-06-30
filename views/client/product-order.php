<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once(__DIR__ . '/../../models/is_user.php');

if (isset($_GET['trans_id'])) {
    $trans_id = validate_alphanumeric($_GET['trans_id'], 50);
    if ($trans_id === false) {
        redirect(base_url('product-orders/'));
    }
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `trans_id` = ? AND `buyer` = ? AND `trash` = 0", [$trans_id, $getUser['id']])) {
        redirect(base_url('product-orders/'));
    }
} else {
    redirect(base_url('product-orders/'));
}
$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$order['product_id']]);


$body = [
    'title' => __('Chi tiết đơn hàng') . ' #' . $order['trans_id'] . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/product-details.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

';
$body['footer'] = '
 
';


if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseIpVerified') == 1) {
    if ($order['ip'] != myip()) {
        die(__('Địa chỉ IP của bạn không khớp với địa chỉ IP bạn dùng để mua hàng'));
    }
}
if ($order['status_view_order'] == 1 || $CMSNT->site('isPurchaseDeviceVerified') == 1) {
    if ($order['device'] != getUserAgent()) {
        die(__('Trình duyệt của bạn không khớp với trình duyệt lúc bạn mua hàng'));
    }
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');



if (isset($_GET['limit'])) {
    $limit = validate_int($_GET['limit'], 5, 20000) ?: 10;
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = validate_int($_GET['page'], 1, 10000) ?: 1;
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where_conditions = ["`trans_id` = ?"];
$where_params = [$order['trans_id']];
$shortByDate = '';
$account = '';


if (!empty($_GET['account'])) {
    $account = validate_string($_GET['account'], 100);
    if ($account !== false) {
        $where_conditions[] = "`account` LIKE ?";
        $where_params[] = '%' . $account . '%';
    }
}


$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `product_sold` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT COUNT(*) AS total FROM `product_sold` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($count_sql, $where_params)['total'] ?? 0;
$urlDatatable = pagination_client(base_url("?action=product-order&limit=$limit&account=$account&trans_id=$trans_id&"), $from, $totalDatatable, $limit);

?>
<div style="margin-bottom:40px;"></div>
<section class="inner-section" style="margin-bottom:40px;">
    <div class="container">
        <div class="row">
            <div class="col-6 mb-3">
                <a class="btn btn-danger btn-sm mb-2" href="<?= base_url('product-orders'); ?>" type="button">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span><?= __('Quay lại'); ?></span></a><br>
                <?= __('Mã đơn hàng:'); ?>
                <strong><?= $order['trans_id']; ?></strong><br>
            </div>
            <div class="col-6 mb-3">
                <div class="text-right">
                    <button type="button" class="btn btn-info btn-sm mb-1" onclick="copyText()">
                        <i class="fa-solid fa-copy"></i> <?= __('Copy'); ?>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm mb-1" onclick="showExportAccountsModal()">
                        <i class="fa-solid fa-cloud-arrow-down"></i> <?= __('Tải về đơn hàng'); ?>
                    </button>
                    <button type="button" onclick="deleteOrder()" class="btn btn-danger btn-sm mb-1">
                        <i class="fa-solid fa-trash"></i> <?= __('Xóa đơn hàng'); ?>
                    </button>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="account-card pt-3">
                    <h3 class="details-name"><?= __('Sản phẩm:'); ?>
                        <?php if ($product): ?>
                            <a href="<?= base_url('product/' . $product['slug']); ?>"><?= __($order['product_name']); ?></a>
                        <?php else: ?>
                            <?= __($order['product_name']); ?>
                        <?php endif; ?>
                    </h3>

                    <div class="details-meta">
                        <p>
                            <label class="label-text feat"><?= __('Số lượng mua:'); ?>
                                <strong><?= format_cash($order['amount']); ?></strong></label>
                            <label class="label-text order"><?= __('Thanh toán:'); ?>
                                <strong><?= format_currency($order['pay']); ?></strong></label>
                        </p>
                    </div>

                    <p class="details-desc"><?= base64_decode($product ? $product['note'] : ''); ?></p>
                </div>
            </div>

        </div>
    </div>
</section>
<section class="inner-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-circle-info m-2"></i> <?= mb_strtoupper(__('Chi tiết đơn hàng')); ?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="<?= base_url(); ?>" method="GET">
                        <input type="hidden" name="action" value="product-order">
                        <input type="hidden" name="trans_id" value="<?= $order['trans_id']; ?>">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" type="text" value="<?= $account; ?>" name="account"
                                    placeholder="<?= __('Tài khoản'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?= base_url('product-order/' . $order['trans_id']); ?>"
                                    class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-short">
                                <label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                    <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                    <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                    <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                    <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                    <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                    <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1.000</option>
                                    <option <?= $limit == 10000 ? 'selected' : ''; ?> value="10000">10.000</option>
                                    <option <?= $limit == 20000 ? 'selected' : ''; ?> value="20000">20.000</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll table-wrapper">
                        <table class="table fs-sm text-nowrap table-hover mb-0">
                            <thead>
                                <th class="text-center">
                                    <input type="checkbox" class="form-check-input" name="check_all"
                                        id="check_all_checkbox_product_sold" value="option1">
                                </th>
                                <?php if ($CMSNT->site('is_uid_visible') == 1): ?>
                                    <th class="text-center">UID</th>
                                <?php endif ?>
                                <th class="text-center"><?= __('Tài khoản'); ?></th>
                                <th class="text-center"><?= __('Thao tác'); ?></th>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) { ?>
                                    <tr style="vertical-align: middle;">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input checkbox_product_sold"
                                                data-id="<?= $row['id']; ?>" data-checkbox="<?= htmlspecialchars($row['account'], ENT_QUOTES, 'UTF-8'); ?>"
                                                name="checkbox_product_sold" value="<?= $row['id']; ?>" />
                                        </td>
                                        <?php if ($CMSNT->site('is_uid_visible') == 1): ?>
                                            <td class="text-center">
                                                <strong><?= $row['uid']; ?></strong>
                                            </td>
                                        <?php endif ?>
                                        <td class="text-center">
                                            <textarea class="form-control" id="copy<?= $row['id']; ?>" rows="1"
                                                readonly><?= $row['account']; ?></textarea>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm copy" onclick="copy()"
                                                data-clipboard-target="#copy<?= $row['id']; ?>">
                                                <i class="fa-solid fa-copy"></i> <?= __('Copy'); ?></button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <td colspan="5">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-dark dropdown-toggle"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-screwdriver-wrench"></i>
                                        </button>
                                        <ul class="dropdown-menu ano20">
                                            <li><a class="dropdown-item" href="javascript:void(0);" type="button"
                                                    onclick="exportDataTXT()"><i class="fa-solid fa-file-export"></i>
                                                    <?= __('Lưu các tài khoản đã chọn vào tệp .txt'); ?></a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" type="button"
                                                    onclick="exportDataClipboard()"><i class="fa-solid fa-copy"></i>
                                                    <?= __('Sao chép các tài khoản đã chọn'); ?></a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" type="button"
                                                    onclick="exportUIDClipboard()"><i class="fa-regular fa-copy"></i>
                                                    <?= __('Chỉ sao chép UID các tài khoản đã chọn'); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bottom-paginate">
                        <p class="page-info">Showing <?= $limit; ?> of <?= $totalDatatable; ?> Results</p>
                        <div class="pagination">
                            <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<textarea class="form-control" id="listAccount"
    hidden><?php
            foreach ($CMSNT->get_list_safe("SELECT * FROM `product_sold` WHERE `trans_id` = ?", [$order['trans_id']]) as $acc) {
                echo $acc['account'] . PHP_EOL;
            }
            ?></textarea>

<!-- Modal Export tài khoản -->
<div class="modal fade" id="exportAccountsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 20px 24px;">
                <h5 class="modal-title fw-bold text-white" style="font-size: 1.1rem;">
                    <i class="fa-solid fa-file-export me-2"></i><?= __('Xuất dữ liệu tài khoản'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert d-flex align-items-center mb-4 border-0" role="alert" style="background: rgba(102, 126, 234, 0.08); border-radius: 12px; color: #667eea;">
                    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
                    <div style="font-size: 0.9rem;">
                        <strong><?= __('Hướng dẫn:'); ?></strong> <?= __('Chọn định dạng file và kéo thả để sắp xếp cột dữ liệu. Hệ thống sẽ xuất toàn bộ tài khoản trong đơn hàng này.'); ?>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Phân vùng: Định dạng file -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-3" style="font-size: 0.95rem;">
                            <i class="fa-solid fa-file-invoice me-1" style="color: #667eea;"></i><?= __('1. Định dạng file'); ?>
                        </label>
                        <div class="card shadow-none border" style="border-radius: 12px;">
                            <div class="card-body p-3">
                                <div class="form-check export-radio-box mb-3 p-3 border rounded active-radio" style="border-radius: 10px !important; cursor: pointer; transition: all 0.2s;">
                                    <input class="form-check-input mt-2" type="radio" name="exportAccountFileType" id="accFileTypeTXT" value="txt" checked style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2" for="accFileTypeTXT" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-lines fa-2x me-3" style="color: #667eea;"></i>
                                            <div>
                                                <div class="fw-bold" style="font-size: 0.95rem;">TXT File</div>
                                                <div style="font-size: 0.78rem; color: #888;"><?= __('Văn bản thuần (Tab-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check export-radio-box mb-0 p-3 border rounded" style="border-radius: 10px !important; cursor: pointer; transition: all 0.2s;">
                                    <input class="form-check-input mt-2" type="radio" name="exportAccountFileType" id="accFileTypeCSV" value="csv" style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2" for="accFileTypeCSV" style="cursor: pointer;">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-csv fa-2x me-3" style="color: #22c55e;"></i>
                                            <div>
                                                <div class="fw-bold" style="font-size: 0.95rem;">CSV File</div>
                                                <div style="font-size: 0.78rem; color: #888;"><?= __('Dữ liệu bảng (Comma-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phân vùng: Chọn và sắp xếp cột -->
                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-bold mb-0" style="font-size: 0.95rem;">
                                <i class="fa-solid fa-table-columns me-1" style="color: #667eea;"></i><?= __('2. Cột dữ liệu xuất'); ?>
                            </label>
                            <span class="badge" style="background: rgba(102, 126, 234, 0.1); color: #667eea; font-weight: 500; padding: 6px 12px; border-radius: 8px;">
                                <i class="fa-solid fa-grip-vertical me-1"></i><?= __('Kéo thả để sắp xếp'); ?>
                            </span>
                        </div>

                        <div class="card shadow-none border" style="border-radius: 12px;">
                            <div class="card-body p-2" style="max-height: 320px; overflow-y: auto;">
                                <ul class="list-group list-group-flush" id="exportAccountColumnsList">
                                    <?php if ($CMSNT->site('is_uid_visible') == 1): ?>
                                    <li class="list-group-item border shadow-sm mb-2 p-2 d-flex align-items-center" style="border-radius: 10px !important;" data-column="uid">
                                        <i class="fa-solid fa-grip-vertical px-2 export-col-handle" style="color: #aaa; cursor: grab;"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-acc-col-checkbox" type="checkbox" role="switch" id="acc_col_uid" value="uid" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 text-dark" for="acc_col_uid" style="cursor: pointer;">UID</label>
                                        </div>
                                    </li>
                                    <?php endif ?>
                                    <li class="list-group-item border shadow-sm mb-2 p-2 d-flex align-items-center" style="border-radius: 10px !important;" data-column="account">
                                        <i class="fa-solid fa-grip-vertical px-2 export-col-handle" style="color: #aaa; cursor: grab;"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-acc-col-checkbox" type="checkbox" role="switch" id="acc_col_account" value="account" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 text-dark" for="acc_col_account" style="cursor: pointer;"><?= __('Tài khoản'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item border shadow-sm mb-2 p-2 d-flex align-items-center" style="border-radius: 10px !important;" data-column="create_gettime">
                                        <i class="fa-solid fa-grip-vertical px-2 export-col-handle" style="color: #aaa; cursor: grab;"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-acc-col-checkbox" type="checkbox" role="switch" id="acc_col_time" value="create_gettime" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 text-dark" for="acc_col_time" style="cursor: pointer;"><?= __('Thời gian'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item border shadow-sm mb-0 p-2 d-flex align-items-center" style="border-radius: 10px !important;" data-column="product_name">
                                        <i class="fa-solid fa-grip-vertical px-2 export-col-handle" style="color: #aaa; cursor: grab;"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-acc-col-checkbox" type="checkbox" role="switch" id="acc_col_product" value="product_name" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 text-dark" for="acc_col_product" style="cursor: pointer;"><?= __('Tên sản phẩm'); ?></label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer border-top d-flex gap-2" style="background: #fafafa;">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="toggleAllExportAccColumns(true)" style="border-radius: 8px;">
                                    <i class="fa-solid fa-check-double me-1"></i><?= __('Chọn tất cả'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="toggleAllExportAccColumns(false)" style="border-radius: 8px;">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Bỏ chọn'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 10px;"><?= __('Hủy bỏ'); ?></button>
                <button type="button" class="btn px-4 fw-bold text-white" id="confirmExportAccBtn" onclick="confirmExportAccounts()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px;">
                    <i class="fa-solid fa-download me-2"></i><?= __('Bắt đầu trích xuất'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.export-radio-box.active-radio {
    border-color: #667eea !important;
    background: rgba(102, 126, 234, 0.06) !important;
}
#exportAccountColumnsList .export-col-handle:active {
    cursor: grabbing;
}
#exportAccountColumnsList .list-group-item {
    user-select: none;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    background: #fff;
}
#exportAccountColumnsList .list-group-item:hover {
    border-color: rgba(102, 126, 234, 0.5) !important;
    background-color: rgba(102, 126, 234, 0.03) !important;
}
#exportAccountColumnsList .list-group-item.sortable-ghost {
    opacity: 0.5;
    background-color: rgba(102, 126, 234, 0.1) !important;
    border-color: #667eea !important;
}
</style>

<script>
    // === Export Accounts Modal Logic ===
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo SortableJS
        if (typeof Sortable !== 'undefined' && document.getElementById('exportAccountColumnsList')) {
            new Sortable(document.getElementById('exportAccountColumnsList'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.export-col-handle'
            });
        }

        // Highlight radio khi chọn
        $('input[name="exportAccountFileType"]').on('change', function() {
            $('.export-radio-box').removeClass('active-radio');
            $(this).closest('.export-radio-box').addClass('active-radio');
        });
    });

    function showExportAccountsModal() {
        var modalEl = document.getElementById('exportAccountsModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    }

    function toggleAllExportAccColumns(checked) {
        $('.export-acc-col-checkbox').prop('checked', checked);
    }

    function confirmExportAccounts() {
        // Lấy loại file
        var fileType = $('input[name="exportAccountFileType"]:checked').val() || 'txt';

        // Lấy danh sách cột được chọn theo thứ tự
        var columns = [];
        $('#exportAccountColumnsList li').each(function() {
            var $checkbox = $(this).find('.export-acc-col-checkbox');
            if ($checkbox.prop('checked')) {
                columns.push($checkbox.val());
            }
        });

        if (columns.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một cột để xuất"); ?>', 'error');
            return;
        }

        // Loading state
        var $btn = $('#confirmExportAccBtn');
        var originalHTML = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang tải..."); ?>');

        $.ajax({
            url: "<?= BASE_URL('ajaxs/client/view.php'); ?>",
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'exportOrderAccounts',
                trans_id: '<?= $trans_id; ?>',
                token: '<?= $getUser['token']; ?>',
                file_type: fileType,
                columns: columns
            },
            success: function(result) {
                $btn.prop('disabled', false).html(originalHTML);

                if (result.status == 'success') {
                    var content = result.data.content;
                    var filename = result.data.filename;
                    var mimeType = fileType === 'csv' ? 'text/csv;charset=utf-8;' : 'text/plain;charset=utf-8;';

                    // UTF-8 BOM
                    var bom = '\uFEFF';
                    var blob = new Blob([bom + content], { type: mimeType });
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);

                    showMessage(result.msg, 'success');
                    // Đóng modal
                    var modalEl = document.getElementById('exportAccountsModal');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getInstance(modalEl).hide();
                    } else {
                        $(modalEl).modal('hide');
                    }
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(originalHTML);
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }
</script>
<script>
    function deleteOrder() {
        Swal.fire({
            title: "<?= __('Bạn có chắc không?'); ?>",
            text: "<?= __('Hệ thống sẽ xóa đơn hàng khỏi lịch sử của bạn khi bạn nhấn đồng ý'); ?>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Đóng'); ?>",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/client/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: '<?= $order['id']; ?>',
                        token: '<?= $getUser['token']; ?>',
                        action: 'removeOrder'
                    },
                    success: function(respone) {
                        if (respone.status == 'success') {
                            location.reload();
                        } else {
                            Swal.fire({
                                title: "<?= __('Thất bại!'); ?>",
                                text: respone.msg,
                                icon: "error"
                            });
                        }
                    },
                    error: function() {
                        alert(html(response));
                        location.reload();
                    }
                });
            }
        });
    }
</script>

<script>
    function copyText() {
        var textarea = document.getElementById("listAccount");
        textarea.removeAttribute("hidden"); // Hiển thị textarea trước khi sao chép
        textarea.select(); // Chọn nội dung trong textarea
        document.execCommand("copy"); // Sao chép nội dung vào clipboard
        textarea.setAttribute("hidden", true); // Ẩn lại textarea sau khi sao chép
        showMessage('<?= __('Đã sao chép toàn bộ tài khoản vào bộ nhớ tạm'); ?>', 'success');
    }
</script>
<script>
    $(function() {
        $('#check_all_checkbox_product_sold').on('click', function() {
            $('.checkbox_product_sold').prop('checked', this.checked);
        });
        $('.checkbox_product_sold').on('click', function() {
            $('#check_all_checkbox_product_sold').prop('checked', $('.checkbox_product_sold:checked')
                .length === $('.checkbox_product_sold').length);
        });
    });
</script>
<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }
</script>

<script>
    function exportDataTXT() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            return showMessage('<?= __('Vui lòng chọn ít nhất một tài khoản'); ?>', 'error');
        }

        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            selectedData.push(checkbox.getAttribute('data-checkbox') + ''); // Thêm dòng mới sau mỗi giá trị
        });

        // Lấy số lượng dữ liệu được xuất
        var numberOfData = checkboxes.length;

        // Chuyển đổi mảng thành chuỗi với mỗi giá trị trên một dòng
        var dataString = selectedData.join('');

        // Tạo một đối tượng Blob chứa dữ liệu
        var blob = new Blob([dataString], {
            type: 'text/plain'
        });

        // Tạo một đường link để tải xuống tệp tin TXT
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = '<?= $trans_id; ?>_' + numberOfData + '.txt';

        // Thêm đường link vào trang và kích hoạt sự kiện click để tải xuống
        document.body.appendChild(link);
        link.click();

        // Xóa đường link sau khi đã tải xuống
        document.body.removeChild(link);
    }
</script>

<script>
    function exportDataClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            return showMessage('<?= __('Vui lòng chọn ít nhất một tài khoản'); ?>', 'error');
        }

        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            // Đảm bảo rằng có một dòng mới sau mỗi giá trị
            selectedData.push(checkbox.getAttribute('data-checkbox').trim());
        });

        // Chuyển đổi mảng thành chuỗi, với mỗi giá trị trên một dòng
        var dataString = selectedData.join('\n');

        // Sao chép chuỗi vào clipboard
        navigator.clipboard.writeText(dataString).then(function() {
            showMessage('<?= __('Nội dung đã được sao chép vào clipboard!'); ?>', 'success');
        }).catch(function(error) {
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>

<script>
    function exportUIDClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            return showMessage('<?= __('Vui lòng chọn ít nhất một tài khoản'); ?>', 'error');
        }

        // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
        var selectedData = [];

        // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
        checkboxes.forEach(function(checkbox) {
            // Lấy dữ liệu và chia nó dựa trên dấu '|'
            var fullData = checkbox.getAttribute('data-checkbox').trim();
            var splitData = fullData.split('|');
            // Kiểm tra để chắc chắn rằng dữ liệu tồn tại trước khi thêm vào mảng
            if (splitData.length > 0) {
                selectedData.push(splitData[0]); // Chỉ lấy phần trước dấu '|'
            }
        });

        // Chuyển đổi mảng thành chuỗi, với mỗi giá trị trên một dòng
        var dataString = selectedData.join('\n');

        // Sao chép chuỗi vào clipboard
        navigator.clipboard.writeText(dataString).then(function() {
            showMessage('<?= __('Nội dung đã được sao chép vào clipboard!'); ?>', 'success');
        }).catch(function(error) {
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>
<?php
require_once(__DIR__ . '/footer.php');
?>