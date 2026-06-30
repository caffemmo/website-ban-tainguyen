<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}


$body = [
    'title' => __('Nạp tiền') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');



if (isset($_GET['limit'])) {
    $limit = validate_int($_GET['limit'], 5, 1000) ?: 10;
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = validate_int($_GET['page'], 1, 1000) ?: 1;
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];
$shortByDate = '';
$description = '';
$tid = '';
$time = '';

if (!empty($_GET['tid'])) {
    $tid = validate_alphanumeric($_GET['tid'], 50);
    if ($tid !== false) {
        $where_conditions[] = '`tid` = ?';
        $where_params[] = $tid;
    }
}
if (!empty($_GET['description'])) {
    $description = validate_string($_GET['description'], 255);
    if ($description !== false) {
        $where_conditions[] = '`description` LIKE ?';
        $where_params[] = '%' . $description . '%';
    }
}
if (!empty($_GET['time'])) {
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if (count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]) {
            $start_date = $create_date_1[0] . ' 00:00:00';
            $end_date = $create_date_1[1] . ' 23:59:59';
            if (validate_date($create_date_1[0], 'Y/m/d') && validate_date($create_date_1[1], 'Y/m/d')) {
                $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if ($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if ($shortByDate == 1) {
            $where_conditions[] = "`create_gettime` LIKE ?";
            $where_params[] = "%$currentDate%";
        }
        if ($shortByDate == 2) {
            $where_conditions[] = "YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if ($shortByDate == 3) {
            $where_conditions[] = "MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_bank` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT COUNT(*) AS total FROM `payment_bank` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($count_sql, $where_params)['total'] ?? 0;
$urlDatatable = pagination_client(base_url("?action=recharge-bank&limit=$limit&shortByDate=$shortByDate&time=$time&tid=$tid&description=$description&"), $from, $totalDatatable, $limit);
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <?php if ($CMSNT->num_rows_safe(" SELECT * FROM `promotions` ", []) != 0): ?>
                <div class="col-lg-6 mb-3">
                    <div class="account-card p-0 h-100" style="overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#065f46,#059669);padding:16px 20px;display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-gift" style="color:#fff;font-size:18px;"></i>
                            </div>
                            <div>
                                <p style="margin:0;font-weight:700;color:#fff;font-size:15px;"><?= __('Khuyến mãi nạp tiền'); ?></p>
                                <p style="margin:0;color:rgba(255,255,255,.75);font-size:12px;"><?= __('Nạp càng nhiều, thưởng càng lớn'); ?></p>
                            </div>
                        </div>
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr style="background:#f0fdf4;">
                                    <th style="padding:10px 20px;border:none;color:#065f46;font-weight:600;font-size:13px;"><?= __('Nạp từ'); ?></th>
                                    <th style="padding:10px 20px;border:none;color:#065f46;font-weight:600;font-size:13px;text-align:right;"><?= __('Thưởng thêm'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($CMSNT->get_list_safe(" SELECT * FROM `promotions` ORDER BY `min` DESC ", []) as $promotion): ?>
                                    <tr style="border-bottom:1px solid #f1f3f5;">
                                        <td style="padding:10px 20px;border:none;font-weight:700;color:#1e3a5f;">≥ <?= format_currency($promotion['min']); ?></td>
                                        <td style="padding:10px 20px;border:none;text-align:right;">
                                            <span style="display:inline-block;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;">+<?= $promotion['discount']; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif ?>
            <div class="col-lg-6 mb-3">
                <div class="account-card p-0" style="overflow:hidden;">
                    <div style="background:linear-gradient(135deg,#92400e,#d97706);padding:16px 20px;display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:18px;"></i>
                        </div>
                        <div>
                            <p style="margin:0;font-weight:700;color:#fff;font-size:15px;"><?= __('Lưu ý quan trọng'); ?></p>
                            <p style="margin:0;color:rgba(255,255,255,.75);font-size:12px;"><?= __('Vui lòng đọc kỹ trước khi nạp'); ?></p>
                        </div>
                    </div>
                    <div style="padding:20px;font-size:14px;line-height:1.8;color:#374151;">
                        <?= $CMSNT->site('bank_notice'); ?>
                    </div>
                </div>
            </div>

            <?php
            $bankRechargeType = $CMSNT->site('bank_recharge_type');
            $userPrefixFullname = $getUser['prefix_fullname'] ?? '';
            $needsFullname = ($bankRechargeType == 'fullname_transfer' && empty($userPrefixFullname));
            if ($bankRechargeType == 'fullname_transfer' && !empty($userPrefixFullname)) {
                $transferContent = $userPrefixFullname . ' ' . $CMSNT->site('prefix_autobank');
            } else {
                $transferContent = $CMSNT->site('prefix_autobank') . $getUser['id'];
            }
            ?>

            <style>
                #bank_cards_container {
                    display: contents;
                }

                .bank-card {
                    border: none;
                    border-radius: 16px;
                    overflow: hidden;
                    background: #fff;
                    box-shadow: 2px 6px 10px rgba(0, 0, 0, 0.2);
                    transition: transform .2s, box-shadow .2s
                }

                .bank-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, .12)
                }

                .bank-card-header {
                    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8e 100%);
                    padding: 16px 20px;
                    display: flex;
                    align-items: center;
                    gap: 12px
                }

                .bank-card-header .bank-icon {
                    width: 44px;
                    height: 44px;
                    background: rgba(255, 255, 255, .15);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 20px;
                    color: #fff;
                    backdrop-filter: blur(4px)
                }

                .bank-card-header .bank-name {
                    color: #fff;
                    font-weight: 700;
                    font-size: 16px;
                    margin: 0
                }

                .bank-card-header .bank-owner {
                    color: rgba(255, 255, 255, .7);
                    font-size: 13px;
                    margin: 0
                }

                .bank-qr-wrap {
                    padding: 20px;
                    background: #fff;
                    text-align: center
                }

                .bank-qr-wrap img {
                    max-width: 260px;
                    width: 100%;
                    border-radius: 12px;
                    border: 2px solid #e2e8f0
                }

                .bank-info {
                    padding: 0
                }

                .bank-info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 14px 20px;
                    border-bottom: 1px solid #f1f3f5;
                    gap: 8px
                }

                .bank-info-row:last-child {
                    border-bottom: none
                }

                .bank-info-row .info-label {
                    color: #6b7280;
                    font-size: 13px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    white-space: nowrap
                }

                .bank-info-row .info-label i {
                    font-size: 14px;
                    color: #9ca3af;
                    width: 16px;
                    text-align: center
                }

                .bank-info-row .info-value {
                    font-weight: 700;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    text-align: right;
                    word-break: break-all
                }

                .bank-info-row .info-value .copy-btn {
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                    background: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all .15s;
                    flex-shrink: 0;
                    color: #6b7280
                }

                .bank-info-row .info-value .copy-btn:hover {
                    background: #f0f7ff;
                    border-color: #3b82f6;
                    color: #3b82f6
                }

                .transfer-content-box {
                    background: linear-gradient(135deg, #fef3f2 0%, #fff1f0 100%);
                    border: 2px dashed #f87171;
                    border-radius: 12px;
                    padding: 14px 20px;
                    margin: 0 20px 16px;
                    text-align: center;
                    animation: pulse-border 2s infinite
                }

                .transfer-content-box .tc-label {
                    font-size: 12px;
                    color: #b91c1c;
                    text-transform: uppercase;
                    letter-spacing: .06em;
                    font-weight: 600;
                    margin-bottom: 4px
                }

                .transfer-content-box .tc-value {
                    font-size: 20px;
                    font-weight: 800;
                    color: #dc2626;
                    letter-spacing: .04em;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px
                }

                .transfer-content-box .tc-value .copy-btn {
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    border: 1px solid #fca5a5;
                    background: rgba(255, 255, 255, .6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    color: #dc2626;
                    transition: all .15s;
                    flex-shrink: 0
                }

                .transfer-content-box .tc-value .copy-btn:hover {
                    background: #dc2626;
                    color: #fff
                }

                .bank-card-footer {
                    padding: 12px 20px;
                    background: #fff;
                    border-top: 1px solid #f1f3f5
                }

                .bank-card-footer small {
                    color: #9ca3af;
                    font-size: 12px
                }

                @keyframes pulse-border {

                    0%,
                    100% {
                        border-color: #f87171
                    }

                    50% {
                        border-color: #fca5a5
                    }
                }

                .modal-backdrop.show {
                    background: rgba(0, 0, 0, .65) !important;
                    backdrop-filter: blur(5px) !important;
                    -webkit-backdrop-filter: blur(5px) !important
                }
            </style>

            <?php if ($needsFullname): ?>
                <div class="modal fade" id="fullnameModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="fullnameModalLabel">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden;">
                            <div class="modal-body text-center" style="padding: 32px 28px 12px;">
                                <div style="width:64px; height:64px; background:linear-gradient(135deg,#dbeafe,#e0f2fe); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                                    <i class="fa-solid fa-user-edit" style="font-size:24px; color:#2563eb;"></i>
                                </div>
                                <h5 style="font-weight:700; margin-bottom:8px;"><?= __('Cập nhật Họ và Tên để tiếp tục nạp tiền'); ?></h5>
                                <p class="text-muted" style="font-size:14px; margin-bottom:0;"><?= __('Họ và Tên sẽ được sử dụng làm nội dung chuyển khoản khi nạp tiền.'); ?></p>
                            </div>
                            <div class="modal-body" style="padding: 16px 28px 28px;">
                                <label class="form-label fw-bold"><?= __('Họ và Tên'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="prefix_fullname_input" placeholder="<?= __('VD: Nguyen Van A'); ?>" maxlength="50" style="font-size:15px; border-radius:10px;">
                                <div class="form-text"><?= __('Hệ thống sẽ tự động bỏ dấu và ghi hoa.'); ?></div>
                                <div id="fullname_preview" class="mt-3" style="display:none;">
                                    <div style="background:linear-gradient(135deg,#eff6ff,#f0f9ff); border:1px solid #bfdbfe; border-radius:10px; padding:12px 16px; font-size:14px;">
                                        <i class="fas fa-arrow-right me-1" style="color:#2563eb;"></i>
                                        <?= __('Nội dung CK:'); ?> <strong id="fullname_preview_text" style="color:#2563eb;"></strong>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" type="button" id="btn_save_fullname" onclick="savePrefixFullname()" style="padding:12px; font-size:15px; font-weight:600; border-radius:10px; background:linear-gradient(135deg,#2563eb,#1d4ed8); border:none;">
                                    <i class="fas fa-save me-1"></i> <?= __('Lưu và tiếp tục'); ?>
                                </button>
                                <p class="text-muted text-center mt-3 mb-0" style="font-size:12px;">
                                    <i class="fas fa-lock me-1"></i><?= __('Sau khi lưu, bạn sẽ không thể thay đổi họ và tên.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div id="bank_cards_container" <?php if ($needsFullname) echo 'style="display:none;"'; ?>>
                <?php foreach ($CMSNT->get_list_safe("SELECT * FROM `banks` WHERE `status` = ? ", [1]) as $bank): ?>
                    <div class="col-lg-6 col-md-6 col-12 mb-4">
                        <div class="bank-card h-100">
                            <div class="bank-card-header">
                                <div class="bank-icon"><i class="fas fa-university"></i></div>
                                <div>
                                    <p class="bank-name"><?= $bank['short_name']; ?></p>
                                    <p class="bank-owner"><?= $bank['accountName']; ?></p>
                                </div>
                            </div>
                            <div class="bank-qr-wrap">
                                <?php if ($bank['short_name'] == 'MOMO'): ?>
                                    <img src="https://api.web2m.com/api/qrmomo.php?amount=0&phone=<?= $bank['accountNumber']; ?>&noidung=<?= rawurlencode($transferContent); ?>&size=300" onerror="this.style.display='none'; document.getElementById('default-<?= $bank['id']; ?>').style.display='block';" />
                                <?php else: ?>
                                    <?php $qrUrl = "https://api.vietqr.io/" . $bank['short_name'] . "/" . $bank['accountNumber'] . "/0/" . rawurlencode($transferContent) . "/vietqr_net_2.jpg?accountName=" . $bank['accountName']; ?>
                                    <img src="<?= $qrUrl; ?>" id="vietqr-<?= $bank['id']; ?>" onerror="this.style.display='none'; document.getElementById('default-<?= $bank['id']; ?>').style.display='block';" />
                                    <img src="<?= base_url($bank['image']); ?>" style="display:none;" id="default-<?= $bank['id']; ?>">
                                <?php endif ?>
                            </div>
                            <div class="transfer-content-box">
                                <div class="tc-label"><?= __('Nội dung chuyển khoản'); ?></div>
                                <div class="tc-value">
                                    <span id="copyNoiDung<?= $bank['id']; ?>"><?= $transferContent; ?></span>
                                    <button onclick="copy()" class="copy copy-btn" data-clipboard-target="#copyNoiDung<?= $bank['id']; ?>" title="<?= __('Sao chép'); ?>">
                                        <i class="fas fa-copy" style="font-size:14px;"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="bank-info">
                                <div class="bank-info-row">
                                    <span class="info-label"><i class="fas fa-credit-card"></i> <?= __('Số tài khoản'); ?></span>
                                    <span class="info-value" style="color:#059669;">
                                        <span id="copySTK<?= $bank['id']; ?>"><?= $bank['accountNumber']; ?></span>
                                        <button onclick="copy()" class="copy copy-btn" data-clipboard-target="#copySTK<?= $bank['id']; ?>" title="<?= __('Sao chép'); ?>">
                                            <i class="fas fa-copy" style="font-size:12px;"></i>
                                        </button>
                                    </span>
                                </div>
                                <div class="bank-info-row">
                                    <span class="info-label"><i class="fas fa-user"></i> <?= __('Chủ tài khoản'); ?></span>
                                    <span class="info-value" style="color:#1e3a5f;"><?= $bank['accountName']; ?></span>
                                </div>
                            </div>
                            <div class="bank-card-footer text-center">
                                <small><i class="fas fa-info-circle me-1"></i><?= __('Nhập đúng nội dung chuyển tiền để hệ thống cộng tiền tự động'); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-clock-rotate-left m-2"></i> <?= mb_strtoupper(__('Lịch sử nạp tiền')); ?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="" method="GET" class="mb-3">
                        <input type="hidden" name="action" value="recharge-bank">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" value="<?= $tid; ?>" name="tid"
                                    placeholder="<?= __('Mã giao dịch'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" value="<?= $description; ?>" name="description"
                                    placeholder="<?= __('Nội dung chuyển khoản'); ?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input type="text" class="js-flatpickr form-control mb-2" id="example-flatpickr-range"
                                    name="time" placeholder="<?= __('Chọn thời gian cần tìm'); ?>" value="<?= $time; ?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?= base_url('?action=recharge-bank'); ?>" class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show"><label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                    <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                    <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                    <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                    <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                    <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                    <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
                                </select>
                            </div>
                            <div class="filter-short">
                                <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                <select name="shortByDate" onchange="this.form.submit()"
                                    class="form-select filter-select">
                                    <option value=""><?= __('Tất cả'); ?></option>
                                    <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?>
                                    </option>
                                    <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?>
                                    </option>
                                    <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll">
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="15%"><?= __('Thời gian'); ?></th>
                                    <th class="text-center"><?= __('Ngân hàng'); ?></th>
                                    <th><?= __('Nội dung chuyển khoản'); ?></th>
                                    <th class="text-right"><?= __('Số tiền nạp'); ?></th>
                                    <th class="text-right"><?= __('Thực nhận'); ?></th>
                                    <th class="text-center"><?= __('Trạng thái'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><b><?= $row['create_gettime']; ?></b></td>
                                        <td class="text-center"><b><?= $row['method']; ?></b></td>
                                        <td>
                                            <small
                                                id="RB<?= $row['id']; ?>"><?= substr($row['description'], 0, 30); ?>...</small>
                                            <small class="hidden"
                                                id="hidden<?= $row['id']; ?>"><?= $row['description']; ?></small>
                                            <a href="javascript:void(0)" class="hidden"
                                                id="read-hide<?= $row['id']; ?>"><?= __('Ẩn bớt'); ?></a>
                                            <a href="javascript:void(0)"
                                                id="read-more<?= $row['id']; ?>"><?= __('Hiển thị thêm'); ?></a>
                                        </td>
                                        <td class="text-right"><b
                                                style="color: green;"><?= format_currency($row['amount']); ?></b></td>
                                        <td class="text-right"><b
                                                style="color: red;"><?= format_currency($row['received']); ?></b></td>
                                        <td class="fw-bold text-success text-center"><b><?= __('Đã thanh toán'); ?></b></td>
                                    </tr>

                                    <script>
                                        $("#read-more<?= $row['id']; ?>").click(function() {
                                            $("#hidden<?= $row['id']; ?>").show(); // hiển thị nội dung đầy đủ
                                            $(this).hide(); // Ẩn nút hiển thị thêm
                                            $("#RB<?= $row['id']; ?>").hide(); // Ẩn nội dung rút ngắn
                                            $("#read-hide<?= $row['id']; ?>").show(); // hiển thị nút ẩn bớt
                                        });
                                        $("#read-hide<?= $row['id']; ?>").click(function() {
                                            $("#hidden<?= $row['id']; ?>").hide(); // ẩn nội dung
                                            $(this).hide(); // ẩn nút ẩn bớt
                                            $("#RB<?= $row['id']; ?>").show(); // hiển thị nội dung rút ngắn
                                            $("#read-more<?= $row['id']; ?>").show(); // hiện nút hiển thị thêm
                                        });
                                    </script>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?= __('Đã thanh toán:'); ?>
                                            <strong style="color:red;"><?php
                                                                        $sum_sql = "SELECT SUM(`amount`) AS total FROM `payment_bank` WHERE $where_clause";
                                                                        $sum = $CMSNT->get_row_safe($sum_sql, $where_params)['total'] ?? 0;
                                                                        echo format_currency($sum);
                                                                        ?></strong>
                                            |

                                            <?= __('Thực nhận:'); ?>
                                            <strong style="color:blue;"><?php
                                                                        $sum_received_sql = "SELECT SUM(`received`) AS total FROM `payment_bank` WHERE $where_clause";
                                                                        $sum_received = $CMSNT->get_row_safe($sum_received_sql, $where_params)['total'] ?? 0;
                                                                        echo format_currency($sum_received);
                                                                        ?></strong>
                                    </td>
                                </tr>
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


<?php
require_once(__DIR__ . '/footer.php');
?>

<script type="text/javascript">
    function removeVietnameseTones(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    // Live preview khi nhập
    var fnInput = document.getElementById('prefix_fullname_input');
    if (fnInput) {
        fnInput.addEventListener('input', function() {
            var val = removeVietnameseTones(this.value).toUpperCase().replace(/\s+/g, ' ').trim();
            var preview = document.getElementById('fullname_preview');
            var previewText = document.getElementById('fullname_preview_text');
            if (val.length > 0) {
                preview.style.display = '';
                previewText.textContent = val + ' <?= $CMSNT->site('prefix_autobank'); ?>';
            } else {
                preview.style.display = 'none';
            }
        });
    }

    function savePrefixFullname() {
        var input = document.getElementById('prefix_fullname_input');
        var fullname = input.value.trim();
        if (!fullname) {
            showMessage("<?= __('Vui lòng nhập họ và tên'); ?>", 'error');
            return;
        }
        // Client-side validation
        var processed = removeVietnameseTones(fullname).toUpperCase().replace(/\s+/g, ' ').trim();
        var words = processed.split(' ');
        if (words.length < 2) {
            showMessage("<?= __('Họ và tên phải có ít nhất 2 từ (VD: NGUYEN VAN A)'); ?>", 'error');
            return;
        }
        if (!/^[A-Z]+(\s[A-Z]+)+$/.test(processed)) {
            showMessage("<?= __('Họ và tên chỉ được chứa chữ cái và khoảng trắng'); ?>", 'error');
            return;
        }

        var btn = document.getElementById('btn_save_fullname');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?= __('Đang lưu...'); ?>';

        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'SavePrefixFullname',
                token: '<?= $getUser['token']; ?>',
                fullname: fullname
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(result.msg, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-1"></i> <?= __("Lưu và tiếp tục"); ?>';
                }
            },
            error: function() {
                showMessage("<?= __('Lỗi kết nối, vui lòng thử lại'); ?>", 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> <?= __("Lưu và tiếp tục"); ?>';
            }
        });
    }

    // Auto-open modal nếu cần nhập họ tên
    <?php if ($needsFullname): ?>
        $(document).ready(function() {
            var el = document.getElementById('fullnameModal');
            var fullnameModal = new bootstrap.Modal(el);
            fullnameModal.show();
            // Chặn hoàn toàn việc đóng modal
            el.addEventListener('hide.bs.modal', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            });
        });
    <?php endif; ?>
</script>

<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }

    function imageLoaded(bankId) {
        // Ẩn loading spinner
        document.getElementById('loading-' + bankId).style.display = 'none';

        // Hiển thị ảnh QR
        if (document.getElementById('vietqr-' + bankId)) {
            document.getElementById('vietqr-' + bankId).style.display = 'block';
        } else {
            // Nếu là ảnh MOMO
            event.target.style.display = 'block';
        }
    }

    function imageError(bankId) {
        // Ẩn loading spinner
        document.getElementById('loading-' + bankId).style.display = 'none';

        // Ẩn ảnh QR lỗi
        if (document.getElementById('vietqr-' + bankId)) {
            document.getElementById('vietqr-' + bankId).style.display = 'none';
        } else {
            // Nếu là ảnh MOMO bị lỗi
            event.target.style.display = 'none';
        }

        // Hiển thị ảnh mặc định 
        if (document.getElementById('default-' + bankId)) {
            document.getElementById('default-' + bankId).style.display = 'block';
        }
    }
</script>

<script>
    function loadData() {
        $.ajax({
            url: "<?= base_url('ajaxs/client/view.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'notication_topup',
                token: '<?= $getUser['token']; ?>'
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '<?= __('Thành công !'); ?>',
                        text: respone.msg,
                        showDenyButton: true,
                        confirmButtonText: '<?= __('Nạp Thêm'); ?>',
                        denyButtonText: `<?= __('Mua Ngay'); ?>`,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        } else if (result.isDenied) {
                            window.location.href = '<?= base_url(); ?>';
                        }
                    });
                }
                setTimeout(loadData, 5000);
            },
            error: function() {
                setTimeout(loadData, 5000);
            }
        });
    }
    loadData();
</script>

<script>
    Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
        'jq-masked-inputs', 'jq-pw-strength'
    ]);
</script>