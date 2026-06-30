<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Toàn bộ tài khoản đã bán | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_sold_product') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if(isset($_POST['RemoveAccounts'])){
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    $value_remove = 0;
    if(empty($_POST['list_account_remove'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập tài khoản cần thêm.")){window.history.back().location.reload();}</script>');
    }
    $list_account_remove = check_string($_POST['list_account_remove']);
    $list = explode(PHP_EOL, $list_account_remove);
    foreach ($list as $account){
        $uid_remove = explode('|', $account)[0];
        $isRemove = $CMSNT->remove("product_sold", " `uid` = '$uid_remove' ");
        if ($isRemove) {
            $value_remove++;
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "Xóa tài khoản ($uid_remove) khỏi đơn hàng đã bán"
            ]);
        }
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', "Xóa $value_remove tài khoản khỏi đơn hàng đã bán", $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("Xóa thành công ['.$value_remove.'] tài khoản.")){window.history.back().location.reload();}</script>');

}


if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$create_gettime = '';
$uid = '';
$shortByDate  = '';
$buyer = '';
$username = '';
$account = '';
$trans_id = '';
$product_code = '';

if(!empty($_GET['product_code'])){
    $product_code = check_string($_GET['product_code']);
    $where .= ' AND `product_code` = "'.$product_code.'" ';
}
if(!empty($_GET['trans_id'])){
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` = "'.$trans_id.'" ';
}
if(!empty($_GET['account'])){
    $account = check_string($_GET['account']);
    $where .= ' AND `account` LIKE "%'.$account.'%" ';
}
if(!empty($_GET['uid'])){
    $uid = check_string($_GET['uid']);
    // Kiểm tra xem có phải nhiều UID không (phân tách bằng dấu phẩy)
    if(strpos($uid, ',') !== false) {
        // Tách các UID bằng dấu phẩy và loại bỏ khoảng trắng
        $uids = array_map('trim', explode(',', $uid));
        $uids = array_filter($uids); // Loại bỏ phần tử rỗng
        if(!empty($uids)) {
            $uids_str = implode('","', $uids);
            $where .= ' AND `uid` IN ("'.$uids_str.'") ';
        }
    } else {
        // Tìm kiếm một UID như trước
        $where .= ' AND `uid` LIKE "%'.$uid.'%" ';
    }
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `buyer` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `buyer` =  "" ';
    }
}
if(!empty($_GET['buyer'])){
    $buyer = check_string($_GET['buyer']);
    $where .= ' AND `buyer` = "'.$buyer.'" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $createdate = $create_gettime;
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);

    if($create_gettime_1[0] != $create_gettime_1[1]){
        $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_gettime_1[0]."' AND `create_gettime` <= '".$create_gettime_1[1]."' ";
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `create_gettime` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `product_sold` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `product_sold` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("product-sold&limit=$limit&shortByDate=$shortByDate&uid=$uid&create_gettime=$create_gettime&buyer=$buyer&username=$username&account=$account&trans_id=$trans_id&product_code=$product_code&"), $from, $totalDatatable, $limit);




?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-cart-arrow-down"></i> Tài khoản đã bán</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">

                <button type="button" onclick="copyAccountSold()" id="btn_copyAccountSold"
                                class="btn btn-primary btn-sm mb-3"><i class="fa-solid fa-list"></i> DANH SÁCH TÀI KHOẢN ĐÃ BÁN</button>
                <button type="button" data-bs-toggle="modal" data-bs-target="#xoa_nhieu_tai_khoan"
                        class="btn btn-danger btn-sm mb-3"><i class="fa-solid fa-trash-can"></i>
                        XÓA NHIỀU TÀI KHOẢN ĐÃ BÁN</button>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="product-sold">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$uid;?>" name="uid"
                                        placeholder="UID (có thể nhập nhiều UID, phân tách bằng dấu phẩy)">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$account;?>" name="account"
                                        placeholder="Tài khoản">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$trans_id;?>" name="trans_id"
                                        placeholder="Mã đơn hàng">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$product_code;?>" name="product_code"
                                        placeholder="Mã kho hàng">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$buyer;?>" name="buyer"
                                        placeholder="ID Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="Username Seller">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('product-sold');?>"><i
                                            class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1.000</option>
                                        <option <?=$limit == 2000 ? 'selected' : '';?> value="2000">2.000</option>
                                        <option <?=$limit == 5000 ? 'selected' : '';?> value="5000">5.000</option>
                                        <option <?=$limit == 10000 ? 'selected' : '';?> value="10000">10.000</option>
                                        <option <?=$limit == 20000 ? 'selected' : '';?> value="20000">20.000</option>
                                        <option <?=$limit == 50000 ? 'selected' : '';?> value="50000">50.000</option>
                                        <option <?=$limit == 100000 ? 'selected' : '';?> value="100000">100.000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?>
                                        </option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
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
                                                    id="check_all_checkbox_product_sold" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center">UID</th>
                                        <th class="text-center">Tài khoản</th>
                                        <th class="text-center">Mã đơn hàng</th>
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
                                                <input type="checkbox" class="form-check-input checkbox_product_sold"
                                                    data-id="<?=$row['id'];?>" data-checkbox="<?=$row['account'];?>" data-uid="<?=$row['uid'];?>"
                                                    name="checkbox_product_sold" value="<?=$row['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <strong><?=$row['uid'];?></strong>
                                        </td>
                                        <td class="text-center">
                                            <textarea rows="1" class="form-control"><?=$row['account'];?></textarea>
                                        </td>
                                        <td class="text-center">
                                            <strong><?=$row['trans_id'];?></strong>
                                        </td>
                                        <td class="text-center">
                                            <strong><?=$row['product_code'];?></strong>
                                        </td>
                                        <td class="text-center"><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['seller']);?>"><?=getRowRealtime("users", $row['seller'], "username");?>
                                                [ID <?=$row['seller'];?>]</a>
                                        </td>
                                        <td class="text-center">
                                            <small data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['create_gettime']));?>"><?=$row['create_gettime'];?></small>
                                        </td>
                                        <td class="text-center"><span
                                                class="badge rounded-pill bg-dark text-white" data-toggle="tooltip" data-placement="bottom"
                                                title="<?=date("H:i:s d-m-Y", $row['time_check_live']);?>"><?=timeAgo($row['time_check_live']);?></span>
                                        </td> 
                                        <td class="text-center"><small><?=$row['type'];?></small></td>
                                        <td class="text-center">
                                            <a type="button" onclick="removeAccount('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Xóa');?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="9">
                                        <div class="btn-list">
                                            <button type="button" onclick="exportDataTXT()" id="exportDataTXT"
                                                class="btn btn-outline-primary shadow-primary btn-wave btn-sm"><i
                                                    class="fa-solid fa-file-export"></i> XUẤT TỆP .TXT</button>
                                            <button type="button" onclick="exportDataClipboard()"
                                                id="exportDataClipboard"
                                                class="btn btn-outline-success shadow-success btn-wave btn-sm"><i
                                                    class="fa-solid fa-copy"></i> COPY</button>
                                            <button type="button" onclick="exportUIDClipboard()" id="exportUIDClipboard"
                                                class="btn btn-outline-info shadow-info btn-wave btn-sm"><i
                                                    class="fa-regular fa-copy"></i> COPY UID</button>
                                            <button type="button" onclick="confirmDeleteAccount()"
                                                id="confirmDeleteAccount"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> DELETE</button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=format_cash($limit);?> of
                                    <?=format_cash($totalDatatable);?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
require_once(__DIR__.'/footer.php');
?>

 


<div class="modal fade" id="xoa_nhieu_tai_khoan" tabindex="-1" aria-labelledby="h6_xoa_nhieu_tai_khoan"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="h6_xoa_nhieu_tai_khoan">XÓA NHIỀU TÀI KHOẢN ĐÃ BÁN
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



<div class="modal fade" id="viewListSold" tabindex="-1" aria-labelledby="viewListSold" data-bs-keyboard="false"
    aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewListSold">DANH SÁCH TÀI KHOẢN ĐÃ BÁN
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="coypyBox_viewListSold" readonly rows="10"></textarea>
                <div class="mt-2 text-end">
                    <span class="badge bg-primary">Tổng số tài khoản: <span id="accountCount">0</span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="copy()" data-clipboard-target="#coypyBox_viewListSold"
                    class="btn btn-info copy">Copy</button>
                <button type="button" onclick="deleteAllAccountSold()" 
                    class="btn btn-danger">Xóa toàn bộ tài khoản đã bán</button>
                <button type="button" class="btn btn-light shadow-light btn-wave" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
function copyAccountSold() {
    var originalButtonContent = $('#btn_copyAccountSold').html();
    $('#btn_copyAccountSold').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    $.ajax({
        url: "<?=base_url('ajaxs/admin/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'view_product_sold',
            token: '<?=$getUser['token'];?>'
        },
        success: function(result) {
            $('#viewListSold').modal('show');
            $('#coypyBox_viewListSold').val(result.accounts);
            $('#btn_copyAccountSold').html(originalButtonContent).prop('disabled', false);
        },
        error: function() {
            alert(html(result));
            location.reload();
        }
    });
}
</script>


<script>
function deleteAllAccountSold() {
    // Hiển thị SweetAlert xác nhận
    Swal.fire({
        title: 'Xác nhận xóa toàn bộ tài khoản đã bán',
        text: 'Hành động này sẽ xóa toàn bộ tài khoản đã bán và không thể hoàn tác!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa toàn bộ',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            // Hiển thị loading
            Swal.fire({
                title: 'Đang xử lý...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Gửi AJAX request
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'removeAllAccountsSold',
                    id: 0,
                    token: '<?=$getUser['token'];?>'
                },
                success: function(result) {
                    if (result.status == 'success') {
                        Swal.fire({
                            title: 'Thành công!',
                            text: result.msg,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Lỗi!',
                            text: result.msg,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra trong quá trình xóa tài khoản',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}
</script>

<script>
function postRemoveAccount(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeAccountSold',
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
    var checkbox = document.getElementsByName('checkbox_product_sold');
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
        $('#confirmDeleteAccount').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
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
    $('#check_all_checkbox_product_sold').on('click', function() {
        $('.checkbox_product_sold').prop('checked', this.checked);
    });
    $('.checkbox_product_sold').on('click', function() {
        $('#check_all_checkbox_product_sold').prop('checked', $('.checkbox_product_sold:checked')
            .length === $('.checkbox_product_sold').length);
    });
});
</script>

<script>
function exportDataTXT() {
    // Lấy tất cả các phần tử input có type là checkbox và được chọn
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

    // Kiểm tra nếu không có checkbox nào được chọn
    if (checkboxes.length === 0) {
        showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
        return;
    }
    $('#exportDataTXT').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
    var selectedData = [];

    // Duyệt qua mỗi checkbox được chọn và thêm giá trị vào mảng
    checkboxes.forEach(function(checkbox) {
        var accountValue = checkbox.getAttribute('data-checkbox');
        if (accountValue) {
            // Trim giá trị để loại bỏ khoảng trắng thừa
            accountValue = accountValue.trim();
            // Nếu tài khoản có nhiều dòng, tách ra thành các dòng riêng
            var lines = accountValue.split(/\r?\n/);
            lines.forEach(function(line) {
                line = line.trim();
                // Chỉ thêm dòng không rỗng
                if (line.length > 0) {
                    selectedData.push(line);
                }
            });
        }
    });

    // Lấy số lượng dữ liệu được xuất
    var numberOfData = selectedData.length;

    // Chuyển đổi mảng thành chuỗi với mỗi giá trị trên một dòng
    var dataString = selectedData.join('\n');

    // Tạo timestamp cho tên file
    var now = new Date();
    var year = now.getFullYear();
    var month = String(now.getMonth() + 1).padStart(2, '0');
    var day = String(now.getDate()).padStart(2, '0');
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    var timestamp = year + '-' + month + '-' + day + '_' + hours + '-' + minutes + '-' + seconds;

    // Tạo một đối tượng Blob chứa dữ liệu
    var blob = new Blob([dataString], {
        type: 'text/plain'
    });

    // Tạo một đường link để tải xuống tệp tin TXT
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = numberOfData + '_' + timestamp + '.txt';

    // Thêm đường link vào trang và kích hoạt sự kiện click để tải xuống
    document.body.appendChild(link);
    link.click();

    // Xóa đường link sau khi đã tải xuống
    document.body.removeChild(link);
    $('#exportDataTXT').html(
        '<i class="fa-solid fa-file-export"></i> XUẤT TỆP .TXT'
    ).prop('disabled',
        false);


}
</script>

<script>
function exportDataClipboard() {
    // Lấy tất cả các phần tử input có type là checkbox và được chọn
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

    // Kiểm tra nếu không có checkbox nào được chọn
    if (checkboxes.length === 0) {
        showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
        return;
    }
    $('#exportDataClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
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
        showMessage("Nội dung đã được sao chép vào clipboard!", 'success');
        $('#exportDataClipboard').html(
            '<i class="fa-solid fa-copy"></i> COPY'
        ).prop('disabled',
            false);
    }).catch(function(error) {
        $('#exportDataClipboard').html(
            '<i class="fa-solid fa-copy"></i> COPY'
        ).prop('disabled',
            false);
        showMessage('Có lỗi xảy ra trong quá trình sao chép: ' + error, 'error');
    });
}
</script>

<script>
function exportUIDClipboard() {
    var checkboxes = document.querySelectorAll('input[name="checkbox_product_sold"]:checked');

    if (checkboxes.length === 0) {
        showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
        return;
    }

    $('#exportUIDClipboard').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__("Processing...");?></span>')
        .prop('disabled', true);

    // Tạo một mảng để lưu trữ giá trị của các checkbox được chọn
    var selectedData = [];

    checkboxes.forEach(function(checkbox) {
        var uid = checkbox.getAttribute('data-uid').trim();
        selectedData.push(uid);
    });

    // Chuyển đổi mảng thành chuỗi, mỗi UID trên một dòng
    var dataString = selectedData.join('\n');

    // Sao chép chuỗi vào clipboard
    navigator.clipboard.writeText(dataString).then(function() {
        showMessage("Nội dung đã được sao chép vào clipboard!", 'success');
        $('#exportUIDClipboard').html('<i class="fa-regular fa-copy"></i> COPY UID')
            .prop('disabled', false);
    }).catch(function(error) {
        showMessage('Có lỗi xảy ra trong quá trình sao chép: ' + error, 'error');
    });
}
</script>


<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>

<script>
// Hàm cập nhật số tài khoản
function updateAccountCount() {
    const textarea = document.getElementById('coypyBox_viewListSold');
    const content = textarea.value;
    
    // Đếm số dòng không trống
    const lines = content.split('\n').filter(line => line.trim() !== '');
    const count = lines.length;
    
    // Cập nhật số tài khoản
    document.getElementById('accountCount').textContent = count;
}

// Thêm sự kiện khi modal được mở
document.addEventListener('DOMContentLoaded', function() {
    const viewListSoldModal = document.getElementById('viewListSold');
    if (viewListSoldModal) {
        viewListSoldModal.addEventListener('shown.bs.modal', function() {
            updateAccountCount();
        });
    }
    
    // Cập nhật số tài khoản khi nội dung textarea thay đổi
    const textarea = document.getElementById('coypyBox_viewListSold');
    if (textarea) {
        textarea.addEventListener('input', updateAccountCount);
    }
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
                showMessage('<?=__('Đã chuyển đổi');?> ' + lines.length +
                    ' <?=__('UID thành định dạng phân tách bằng dấu phẩy');?>', 'success');
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
        '<?=__('Có thể paste nhiều UID (mỗi UID một dòng), hệ thống sẽ tự động thêm vào danh sách hiện có');?>'
    );
});
</script>

