<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Toàn bộ tài khoản trong kho hàng | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
if (checkPermission($getUser['admin'], 'edit_stock_product') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if (isset($_POST['RemoveAccounts'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    $value_remove = 0;
    if (empty($_POST['list_account_remove'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập tài khoản cần thêm.")){window.history.back().location.reload();}</script>');
    }
    $list_account_remove = trim($_POST['list_account_remove']);
    // Tách dòng an toàn cho Windows/Unix/Mac
    $list = preg_split('/\r\n|\r|\n/', $list_account_remove);
    foreach ($list as $account) {
        $account = trim($account);
        if ($account === '') {
            continue;
        }
        // Lấy UID: hỗ trợ định dạng UID|..., UID:..., hoặc chỉ UID
        if (strpos($account, '|') !== false) {
            $uid_remove = trim(explode('|', $account)[0]);
        } elseif (strpos($account, ':') !== false) {
            $uid_remove = trim(explode(':', $account)[0]);
        } else {
            $uid_remove = trim(explode(' ', $account)[0]);
        }
        // Validate UID (độ dài tối đa 255)
        $uid_remove = validate_string($uid_remove, 5000, 1);
        if ($uid_remove === false) {
            continue;
        }
        // Xóa bằng Prepared Statements
        $isRemove = $CMSNT->remove("product_stock", " `uid` = ? ", [$uid_remove]);
        if ($isRemove) {
            $value_remove++;
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "Xóa tài khoản ($uid_remove) khỏi kho hàng đang bán"
            ]);
        }
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', "Xóa $value_remove tài khoản khỏi kho hàng", $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("Xóa thành công [' . $value_remove . '] tài khoản.")){window.history.back().location.reload();}</script>');
}


if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = check_string(intval($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$create_gettime = '';
$uid = '';
$shortByDate  = '';
$user_id = '';
$username = '';
$account = '';
$product_code = '';

if (!empty($_GET['product_code'])) {
    $product_code = check_string($_GET['product_code']);
    $where .= ' AND `product_code` = "' . $product_code . '" ';
}
if (!empty($_GET['account'])) {
    $account = check_string($_GET['account']);
    $where .= ' AND `account` LIKE "%' . $account . '%" ';
}
if (!empty($_GET['uid'])) {
    $uid = check_string($_GET['uid']);
    // Kiểm tra xem có phải nhiều UID không (phân tách bằng dấu phẩy)
    if (strpos($uid, ',') !== false) {
        // Tách các UID bằng dấu phẩy và loại bỏ khoảng trắng
        $uids = array_map('trim', explode(',', $uid));
        $uids = array_filter($uids); // Loại bỏ phần tử rỗng
        if (!empty($uids)) {
            $uids_str = implode('","', $uids);
            $where .= ' AND `uid` IN ("' . $uids_str . '") ';
        }
    } else {
        // Tìm kiếm một UID như trước
        $where .= ' AND `uid` LIKE "%' . $uid . '%" ';
    }
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if ($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")) {
        $where .= ' AND `user_id` =  "' . $idUser['id'] . '" ';
    } else {
        $where .= ' AND `user_id` =  "" ';
    }
}
if (!empty($_GET['user_id'])) {
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = "' . $user_id . '" ';
}
if (!empty($_GET['create_gettime'])) {
    $create_gettime = check_string($_GET['create_gettime']);
    $createdate = $create_gettime;
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);

    if ($create_gettime_1[0] != $create_gettime_1[1]) {
        $create_gettime_1 = [$create_gettime_1[0] . ' 00:00:00', $create_gettime_1[1] . ' 23:59:59'];
        $where .= " AND `create_gettime` >= '" . $create_gettime_1[0] . "' AND `create_gettime` <= '" . $create_gettime_1[1] . "' ";
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if ($shortByDate == 1) {
        $where .= " AND `create_gettime` LIKE '%" . $currentDate . "%' ";
    }
    if ($shortByDate == 2) {
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if ($shortByDate == 3) {
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `product_stock` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `product_stock` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("product-warehouse&limit=$limit&shortByDate=$shortByDate&uid=$uid&create_gettime=$create_gettime&user_id=$user_id&username=$username&account=$account&product_code=$product_code&"), $from, $totalDatatable, $limit);




?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-cubes"></i> Tài khoản đang bán</h1>
        </div>
        <div class="row">
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-top justify-content-between">
                            <div class="flex-fill">
                                <p class="mb-0 text-muted">Tài khoản <strong style="color:green;">LIVE</strong></p>
                                <div class="d-flex align-items-center">
                                    <span class="fs-5 fw-semibold"><?= format_cash($totalDatatable); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-md avatar-rounded bg-success-transparent text-success fs-18">
                                    <i class="fa-brands fa-facebook fs-16"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-top justify-content-between">
                            <div class="flex-fill">
                                <p class="mb-0 text-muted">Tài khoản <strong style="color:red;">DIE</strong></p>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="fs-5 fw-semibold"><?= format_cash($CMSNT->get_row(" SELECT COUNT(id) FROM `product_die` ")['COUNT(id)']); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-md avatar-rounded bg-danger-transparent text-danger fs-18">
                                    <i class="fa-brands fa-facebook fs-16"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="text-right">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_nhieu_tai_khoan"
                        class="btn btn-danger btn-sm mb-3"><i class="fa-solid fa-trash-can"></i>
                        XÓA NHIỀU TÀI KHOẢN</button>
                    <button type="button" onclick="showExportProductStockModal('all')" class="btn btn-success btn-sm mb-3">
                        <i class="fa-solid fa-file-export"></i> XUẤT TOÀN BỘ TÀI KHOẢN
                    </button>
                    <button type="button" onclick="downloadListDIE()" class="btn btn-primary btn-sm mb-3">
                        <i class="fa-solid fa-cloud-arrow-down"></i> TẢI VỀ TOÀN BỘ TÀI KHOẢN DIE
                    </button>
                    <button type="button" id="btn_format_list_die" class="btn btn-danger btn-sm mb-3">
                        <i class="fa-solid fa-trash"></i> XÓA TOÀN BỘ TÀI KHOẢN DIE
                    </button>

                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            TÀI KHOẢN <span style="color:green;">LIVE</span> ĐANG BÁN
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="product-warehouse">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $uid; ?>" name="uid"
                                        placeholder="UID (có thể nhập nhiều UID, phân tách bằng dấu phẩy)">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $account; ?>" name="account"
                                        placeholder="Tài khoản">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $product_code; ?>"
                                        name="product_code" placeholder="Mã kho hàng">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $user_id; ?>" name="user_id"
                                        placeholder="ID Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $username; ?>" name="username"
                                        placeholder="Username Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?= $create_gettime; ?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?= __('Search'); ?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?= base_url_admin('product-warehouse'); ?>"><i class="fa fa-trash"></i>
                                        <?= __('Clear filter'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1.000</option>
                                        <option <?= $limit == 2000 ? 'selected' : ''; ?> value="2000">2.000</option>
                                        <option <?= $limit == 5000 ? 'selected' : ''; ?> value="5000">5.000</option>
                                        <option <?= $limit == 10000 ? 'selected' : ''; ?> value="10000">10.000</option>
                                        <option <?= $limit == 20000 ? 'selected' : ''; ?> value="20000">20.000</option>
                                        <option <?= $limit == 50000 ? 'selected' : ''; ?> value="50000">50.000</option>
                                        <option <?= $limit == 100000 ? 'selected' : ''; ?> value="100000">100.000</option>
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
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3">
                                            <?= __('Tháng này'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox_product_stock" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center">UID</th>
                                        <th class="text-center">Tài khoản</th>
                                        <th class="text-center">Kho hàng</th>
                                        <th class="text-center">Seller</th>
                                        <th class="text-center">Thời gian</th>
                                        <th class="text-center">Check live gần nhất</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="form-check form-check-md d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input checkbox_product_stock"
                                                        data-id="<?= $row['id']; ?>" data-checkbox="<?= htmlspecialchars($row['account'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        name="checkbox_product_stock" value="<?= $row['id']; ?>" />
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= $row['uid']; ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <textarea rows="1" class="form-control"><?= $row['account']; ?></textarea>
                                            </td>
                                            <td class="text-center">
                                                <strong
                                                    id="ma_kho_hang_mac_dinh_<?= $row['id']; ?>"><?= $row['product_code']; ?></strong>
                                            </td>
                                            <td class="text-center"><a class="text-primary"
                                                    href="<?= base_url_admin('user-edit&id=' . $row['seller']); ?>"><?= getRowRealtime("users", $row['seller'], "username"); ?>
                                                    [ID <?= $row['seller']; ?>]</a>
                                            </td>
                                            <td class="text-center">
                                                <small data-toggle="tooltip" data-placement="bottom"
                                                    title="<?= timeAgo(strtotime($row['create_gettime'])); ?>"><?= $row['create_gettime']; ?></small>
                                            </td>
                                            <td class="text-center"><span class="badge rounded-pill bg-dark text-white"
                                                    data-toggle="tooltip" data-placement="bottom"
                                                    title="<?= date("H:i:s d-m-Y", $row['time_check_live']); ?>"><?= timeAgo($row['time_check_live']); ?></span>
                                            </td>
                                            <td class="text-center"><small><?= $row['type']; ?></small></td>
                                            <td class="text-center">
                                                <a type="button" onclick="removeAccount('<?= $row['id']; ?>')"
                                                    class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                    title="<?= __('Xóa'); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                                <tfoot>
                                    <td colspan="9">
                                        <div class="btn-list">
                                            <button type="button" onclick="showExportProductStockModal()"
                                                class="btn btn-outline-primary shadow-primary btn-wave btn-sm"><i
                                                    class="fa-solid fa-file-export"></i> TÙY CHỈNH XUẤT DỮ LIỆU</button>
                                            <button type="button" onclick="exportDataClipboard()"
                                                id="exportDataClipboard"
                                                class="btn btn-outline-success shadow-success btn-wave btn-sm"><i
                                                    class="fa-solid fa-copy"></i> COPY</button>
                                            <button type="button" onclick="exportUIDClipboard()" id="exportUIDClipboard"
                                                class="btn btn-outline-info shadow-info btn-wave btn-sm"><i
                                                    class="fa-regular fa-copy"></i> COPY UID</button>
                                            <button type="button" id="btn_edit_ma_kho_hang_product_stock"
                                                class="btn btn-success shadow-success btn-wave btn-sm"><i
                                                    class="fa-solid fa-pen-to-square"></i> THAY ĐỔI KHO HÀNG</button>
                                            <button type="button" onclick="confirmDeleteAccount()"
                                                id="confirmDeleteAccount"
                                                class="btn btn-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> DELETE</button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= format_cash($limit); ?> of
                                    <?= format_cash($totalDatatable); ?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>


<div class="modal fade" id="xoa_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="h6_xoa_nhieu_tai_khoan">XÓA NHIỀU TÀI KHOẢN <strong style="color:green;">LIVE</strong> ĐANG BÁN
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="text-area" class="form-label">Tài khoản cần xóa: (1 dòng 1 tài khoản)</label>
                        <textarea class="form-control" name="list_account_remove" id="accounts_remove" placeholder="UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID
UID|... HOẶC MỖI UID" rows="5" required></textarea>
                        <small>Nhấn Submit để xóa <strong style="color: red;" id="countRemove">0</strong> tài
                            khoản</small>
                        <input type="hidden" name="type" value="multi" readonly>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                var textarea = document.getElementById('accounts_remove');
                                var countAdd = document.getElementById("countRemove");

                                if (textarea && countAdd) {
                                    textarea.addEventListener("input", function() {
                                        var lines = textarea.value.split('\n');
                                        var nonEmptyLinesCount = lines.filter(function(line) {
                                            return line.trim().length >
                                                0; // Lọc ra những dòng không rỗng
                                        }).length;
                                        countAdd.innerText =
                                            nonEmptyLinesCount; // Cập nhật số dòng không rỗng vào countAdd
                                    });
                                }
                            });
                        </script>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="RemoveAccounts" class="btn btn-primary btn-sm"><i
                            class="fa fa-fw fa-trash me-1"></i> Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>




<div class="modal fade" id="modal_edit_ma_kho_hang_product_stock" tabindex="-1"
    aria-labelledby="modal_edit_category_product" data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">Thay đổi kho hàng <mark
                        class="checkboxeslength"></mark> tài khoản đã chọn</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Mã kho hàng:'); ?> <span
                            class="text-danger">*</span></label>
                    <div class="col-sm-8">
                        <input class="form-control" type="text" id="kho_hang_moi"
                            placeholder="Nhập mã kho hàng cần thay đổi">
                    </div>
                </div>
                <p>Khi bạn nhấn vào nút UPDATE đồng nghĩa các tài khoản mà bạn đã chọn sẽ được cập nhật thành mã kho
                    hàng trên.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="update_ma_kho_hang_product_stock()" id="update_ma_kho_hang_product_stock"
                    class="btn btn-primary"><i class="fa fa-solid fa-save"></i> <?= __('Update'); ?></button>
            </div>
        </div>
    </div>
</div>




<script>
    function downloadListDIE(trans_id) {
        Swal.fire({
            title: "<?= __('Xác nhận tải về tài khoản DIE'); ?>",
            text: "<?= __('Hệ thống sẽ tải về toàn bộ tài khoản DIE khi bạn nhấn đồng ý'); ?>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Đóng'); ?>",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/view.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'download_product_die',
                        token: '<?= $getUser['token']; ?>',
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            downloadTXT(result.filename, result.accounts);
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });
    }

    function downloadTXT(filename, text) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }
</script>

<script>
    $(document).ready(function() {
        $("#btn_edit_ma_kho_hang_product_stock").click(function() {
            var checkboxes = $('input[name="checkbox_product_stock"]:checked');
            if (checkboxes.length === 0) {
                showMessage('Vui lòng chọn ít nhất một sản phẩm.', 'error');
                return;
            }
            $(".checkboxeslength").html(checkboxes.length);
            $("#modal_edit_ma_kho_hang_product_stock").modal('show');
        });

        $('#update_ma_kho_hang_product_stock').click(function() {
            update_ma_kho_hang_product_stock();
        });

        function update_ma_kho_hang_product_stock() {
            var originalButtonContent = $('#update_ma_kho_hang_product_stock').html();
            $('#update_ma_kho_hang_product_stock').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop(
                'disabled', true);
            var kho_hang_moi = $('#kho_hang_moi').val();
            var checkboxes = $('input[name="checkbox_product_stock"]:checked');

            function postUpdatesSequentially(index) {
                if (index < checkboxes.length) {
                    var checkbox = checkboxes.eq(index);
                    post_update_ma_kho_hang_product_stock(checkbox.val(), kho_hang_moi);
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 100);
                } else {
                    Swal.fire({
                        title: "Thành công!",
                        text: "Cập nhật kho hàng mới thành công",
                        icon: "success"
                    });
                    $('#update_ma_kho_hang_product_stock').html(originalButtonContent).prop('disabled', false);
                }
            }

            postUpdatesSequentially(0);
        }

        function post_update_ma_kho_hang_product_stock(id, kho_hang_moi) {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'update_product_code_product_stock',
                    id: id,
                    product_code: kho_hang_moi
                },
                success: function(result) {
                    if (result.status == 'success') {
                        $('#ma_kho_hang_mac_dinh_' + id).html(kho_hang_moi);
                        showMessage(result.msg, result.status);
                    } else {
                        showMessage(result.msg, result.status);
                    }
                },
                error: function() {
                    alert(html(result));
                    location.reload();
                }
            });
        }
    });
</script>



<script>
    function postRemoveAccount(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'removeAccountStock',
                id: id
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                }
            }
        });
    }

    function removeAccount(id) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa tài khoản",
            message: "Bạn có chắc chắn muốn xóa tài khoản này không ?",
            confirmText: "Đồng ý",
            cancelText: "Không"
        }).then((e) => {
            if (e) {
                postRemoveAccount(id);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        })
    }
</script>
<script>
    function confirmDeleteAccount() {
        var checkbox = document.getElementsByName('checkbox_product_stock');
        var isAnyCheckboxChecked = false;
        for (var i = 0; i < checkbox.length; i++) {
            if (checkbox[i].checked === true) {
                isAnyCheckboxChecked = true;
                break;
            }
        }
        if (!isAnyCheckboxChecked) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        var result = confirm('Bạn có đồng ý xóa các bản ghi đã chọn không?');
        if (result) {
            $('#confirmDeleteAccount').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
                .prop('disabled',
                    true);

            function postUpdatesSequentially(index) {
                if (index < checkbox.length) {
                    if (checkbox[index].checked === true) {
                        postRemoveAccount(checkbox[index].value);
                    }
                    setTimeout(function() {
                        postUpdatesSequentially(index + 1);
                    }, 100);
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            }
            postUpdatesSequentially(0);
        }
    }

    $(function() {
        $('#check_all_checkbox_product_stock').on('click', function() {
            $('.checkbox_product_stock').prop('checked', this.checked);
        });
        $('.checkbox_product_stock').on('click', function() {
            $('#check_all_checkbox_product_stock').prop('checked', $('.checkbox_product_stock:checked')
                .length === $('.checkbox_product_stock').length);
        });
    });
</script>

<!-- Modal Tùy chỉnh xuất dữ liệu tài khoản -->
<div class="modal fade" id="exportProductStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-file-export me-2 text-primary"></i><?= __('Tùy chỉnh dữ liệu xuất'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-primary d-flex align-items-center mb-4 border-0" role="alert">
                    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
                    <div>
                        <strong><?= __('Hướng dẫn:'); ?></strong> <?= __('Hãy chọn định dạng file và kéo thả để sắp xếp các cột dữ liệu cần thiết. Hệ thống sẽ trích xuất dựa trên lựa chọn của bạn.'); ?>
                    </div>
                </div>
                
                <div class="row g-4">
                    <!-- Phân vùng: Định dạng file -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold mb-3">
                            <i class="fa-solid fa-file-invoice text-muted me-1"></i><?= __('1. Định dạng file'); ?>
                        </label>
                        <div class="card shadow-none border rounded-3 bg-white">
                            <div class="card-body p-3">
                                <div class="form-check custom-radio-box-stock mb-3 p-3 border rounded border-primary bg-primary-transparent">
                                    <input class="form-check-input mt-2" type="radio" name="exportStockFileType" id="stockFileTypeTXT" value="txt" checked style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="stockFileTypeTXT">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-lines fa-2x text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">TXT File</div>
                                                <div class="fs-12 text-muted"><?= __('Văn bản thuần (Tab-separated)'); ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-check custom-radio-box-stock mb-0 p-3 border rounded">
                                    <input class="form-check-input mt-2" type="radio" name="exportStockFileType" id="stockFileTypeCSV" value="csv" style="transform: scale(1.3); cursor:pointer;">
                                    <label class="form-check-label w-100 ms-2 cursor-pointer" for="stockFileTypeCSV">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-file-csv fa-2x text-success me-3"></i>
                                            <div>
                                                <div class="fw-bold fs-15">CSV File</div>
                                                <div class="fs-12 text-muted"><?= __('Dữ liệu bảng (Comma-separated)'); ?></div>
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
                            <label class="form-label fw-bold mb-0">
                                <i class="fa-solid fa-table-columns text-muted me-1"></i><?= __('2. Cột dữ liệu xuất'); ?>
                            </label>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-grip-vertical me-1"></i><?= __('Kéo thả để sắp xếp'); ?>
                            </span>
                        </div>
                        
                        <div class="card shadow-none border bg-light mb-0">
                            <div class="card-body p-2" style="max-height: 380px; overflow-y: auto;">
                                <ul class="list-group list-group-flush" id="exportStockColumnsList">
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="uid">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_uid" value="uid" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_uid"><?= __('UID'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="account">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_account" value="account" checked style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_account"><?= __('Tài khoản'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="product_code">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_product_code" value="product_code" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_product_code"><?= __('Mã kho hàng'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="seller">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_seller" value="seller" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_seller"><?= __('Seller'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="type">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_type" value="type" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_type"><?= __('Loại (Type)'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-2 p-2 d-flex align-items-center" data-column="create_gettime">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_create_gettime" value="create_gettime" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_create_gettime"><?= __('Ngày thêm'); ?></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-white border rounded shadow-sm mb-0 p-2 d-flex align-items-center" data-column="time_check_live">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move px-2"></i>
                                        <div class="form-check mb-0 form-switch w-100 d-flex align-items-center">
                                            <input class="form-check-input ms-0 me-3 export-stock-col-checkbox" type="checkbox" role="switch" id="scol_time_check_live" value="time_check_live" style="transform: scale(1.3); cursor:pointer;">
                                            <label class="form-check-label fw-medium w-100 cursor-pointer text-dark" for="scol_time_check_live"><?= __('Thời gian Check Live'); ?></label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="toggleAllExportStockColumns(true)">
                                    <i class="fa-solid fa-check-double me-1"></i><?= __('Chọn xuất tất cả'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="toggleAllExportStockColumns(false)">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Bỏ chọn'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-secondary px-4 btn-wave" data-bs-dismiss="modal"><?= __('Hủy bỏ'); ?></button>
                <button type="button" class="btn btn-success px-4 btn-wave fw-bold" id="confirmExportStockBtn" onclick="confirmExportStock()">
                    <i class="fa-solid fa-download me-2"></i><?= __('Bắt đầu trích xuất'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .custom-radio-box-stock {
        transition: all 0.2s;
    }
    #exportStockColumnsList .cursor-move {
        cursor: grab;
    }
    #exportStockColumnsList .cursor-move:active {
        cursor: grabbing;
    }
    #exportStockColumnsList .list-group-item {
        user-select: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    #exportStockColumnsList .list-group-item:hover {
        border-color: rgba(var(--primary-rgb), 0.5) !important;
        background-color: rgba(var(--primary-rgb), 0.03) !important;
    }
    #exportStockColumnsList .list-group-item.sortable-ghost {
        opacity: 0.5;
        background-color: rgba(var(--primary-rgb), 0.1) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<script>
    // JS trang trí khi select file type radio cho export stock
    $(document).ready(function() {
        $('input[name="exportStockFileType"]').on('change', function() {
            $('.custom-radio-box-stock').removeClass('border-primary bg-primary-transparent');
            $(this).closest('.custom-radio-box-stock').addClass('border-primary bg-primary-transparent');
        });
    });

    // Khởi tạo Sortable
    if (typeof Sortable !== 'undefined' && document.getElementById('exportStockColumnsList')) {
        new Sortable(document.getElementById('exportStockColumnsList'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.fa-grip-vertical'
        });
    }

    var currentExportType = 'selected';

    // Hiển thị modal
    function showExportProductStockModal(type = 'selected') {
        currentExportType = type;
        if (type === 'selected') {
            var selectedIds = getSelectedStockIds();
            if (selectedIds.length === 0) {
                showMessage('<?= __("Vui lòng chọn ít nhất một bản ghi"); ?>', 'error');
                return;
            }
        }
        var modalEl = document.getElementById('exportProductStockModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).addClass('show').css('display', 'block');
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
        }
    }

    function toggleAllExportStockColumns(checked) {
        $('.export-stock-col-checkbox').prop('checked', checked);
    }

    function getSelectedStockIds() {
        var selectedIds = [];
        $('.checkbox_product_stock:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    // Gửi yêu cầu export
    function confirmExportStock() {
        var selectedIds = [];
        if (currentExportType === 'selected') {
            selectedIds = getSelectedStockIds();
            if (selectedIds.length === 0) {
                showMessage('<?= __("Vui lòng chọn ít nhất một bản ghi"); ?>', 'error');
                return;
            }
        }

        var fileType = $('input[name="exportStockFileType"]:checked').val() || 'txt';
        var columns = [];
        $('#exportStockColumnsList li').each(function() {
            var $checkbox = $(this).find('.export-stock-col-checkbox');
            if ($checkbox.prop('checked')) {
                columns.push($checkbox.val());
            }
        });

        if (columns.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một cột để xuất"); ?>', 'error');
            return;
        }

        $('#confirmExportStockBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang tải..."); ?>');

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'exportProductStock',
                token: '<?= $getUser['token']; ?>',
                ids: selectedIds,
                export_all: currentExportType === 'all' ? 1 : 0,
                file_type: fileType,
                columns: columns
            },
            success: function(result) {
                $('#confirmExportStockBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Bắt đầu trích xuất"); ?>');

                if (result.status == 'success') {
                    // Tạo file và download
                    var content = result.data.content;
                    var filename = result.data.filename;
                    var mimeType = fileType === 'csv' ? 'text/csv;charset=utf-8;' : 'text/plain;charset=utf-8;';

                    // Thêm BOM cho UTF-8
                    var bom = '\uFEFF';
                    var blob = new Blob([bom + content], {
                        type: mimeType
                    });
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);

                    showMessage(result.msg, 'success');
                    // Hide modal
                    var modalEl = document.getElementById('exportProductStockModal');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getInstance(modalEl).hide();
                    } else {
                        $(modalEl).removeClass('show').css('display', 'none');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    }
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#confirmExportStockBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Bắt đầu trích xuất"); ?>');
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }
</script>

<script>
    function exportDataClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        $('#exportDataClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
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
            showMessage('Nội dung đã được sao chép vào clipboard!', 'success');
            $('#exportDataClipboard').html(
                '<i class="fa-solid fa-copy"></i> COPY'
            ).prop('disabled',
                false);
        }).catch(function(error) {
            $('#exportDataClipboard').html(
                '<i class="fa-solid fa-copy"></i> COPY'
            ).prop('disabled',
                false);
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>

<script>
    function exportUIDClipboard() {
        // Lấy tất cả các phần tử input có type là checkbox và được chọn
        var checkboxes = document.querySelectorAll('input[name="checkbox_product_stock"]:checked');

        // Kiểm tra nếu không có checkbox nào được chọn
        if (checkboxes.length === 0) {
            showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
            return;
        }
        $('#exportUIDClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
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
            showMessage('Nội dung đã được sao chép vào clipboard!', 'success');
            $('#exportUIDClipboard').html(
                '<i class="fa-regular fa-copy"></i> COPY UID'
            ).prop('disabled',
                false);
        }).catch(function(error) {
            alert('Có lỗi xảy ra trong quá trình sao chép: ' + error);
        });
    }
</script>

<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage('<?= __('Đã sao chép vào bộ nhớ tạm'); ?>', 'success');
    }
</script>


<script>
    $("#btn_format_list_die").click(function() {
        Swal.fire({
            title: "Bạn có chắc không?",
            text: "Hệ thống sẽ xóa vĩnh viễn toàn bộ dữ liệu tài khoản DIE khi bạn nhấn Đồng Ý",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Đồng ý",
            cancelButtonText: "Đóng"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/admin/remove.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'empty_all_list_die',
                        id: 0,
                        token: '<?= $getUser['token']; ?>'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout("location.href = '';", 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });
    });
</script>

<script>
    // Xử lý tự động chuyển đổi định dạng UID khi paste
    $(document).ready(function() {
        // Xử lý sự kiện paste cho input uid
        $('input[name="uid"]').on('paste', function(e) {
            const input = this;

            // Ngăn chặn hành vi paste mặc định
            e.preventDefault();

            // Lấy dữ liệu từ clipboard
            let clipboardData = e.originalEvent.clipboardData || window.clipboardData;
            let pastedData = clipboardData.getData('text').trim();

            // Lấy nội dung hiện tại của input
            let currentValue = $(input).val().trim();

            // Xử lý dữ liệu paste
            let newData = '';

            // Kiểm tra xem có phải dữ liệu nhiều dòng không
            if (pastedData.includes('\n') || pastedData.includes('\r')) {
                // Tách các dòng và loại bỏ khoảng trắng
                let lines = pastedData.split(/[\r\n]+/)
                    .map(line => line.trim())
                    .filter(line => line.length > 0);

                if (lines.length > 1) {
                    // Nối các dòng bằng dấu phẩy
                    newData = lines.join(',');

                    // Hiển thị thông báo cho việc chuyển đổi
                    showMessage('<?= __('Đã chuyển đổi'); ?> ' + lines.length +
                        ' <?= __('UID thành định dạng phân tách bằng dấu phẩy'); ?>', 'success');
                } else {
                    // Nếu chỉ có 1 dòng
                    newData = lines[0] || '';
                }
            } else {
                // Nếu không có xuống dòng
                newData = pastedData;
            }

            // Kết hợp với nội dung hiện có
            let finalValue = '';
            if (currentValue && newData) {
                // Nếu cả hai đều có nội dung
                // Kiểm tra xem currentValue có kết thúc bằng dấu phẩy không
                if (currentValue.endsWith(',')) {
                    finalValue = currentValue + newData;
                } else {
                    finalValue = currentValue + ',' + newData;
                }
            } else if (newData) {
                // Chỉ có dữ liệu mới
                finalValue = newData;
            } else {
                // Không có dữ liệu mới, giữ nguyên
                finalValue = currentValue;
            }

            // Loại bỏ dấu phẩy trùng lặp
            finalValue = finalValue.replace(/,+/g, ',').replace(/^,|,$/g, '');

            // Cập nhật giá trị input
            $(input).val(finalValue);
        });

        // Thêm tooltip hướng dẫn
        $('input[name="uid"]').attr('title',
            '<?= __('Có thể paste nhiều UID (mỗi UID một dòng), hệ thống sẽ tự động thêm vào danh sách hiện có'); ?>'
        );
    });
</script>