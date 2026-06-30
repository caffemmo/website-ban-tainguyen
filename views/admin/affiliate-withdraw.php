<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Affiliate Withdraw',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>

';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_withdraw_affiliate') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
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
$shortByDate  = '';
$user_id = '';
$reason = '';
$create_gettime = '';
$username = '';
$status = '';
$stk = '';
$trans_id = '';


if(!empty($_GET['trans_id'])){
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` = "'.$trans_id.'" ';
}
if(!empty($_GET['stk'])){
    $stk = check_string($_GET['stk']);
    $where .= ' AND `stk` = "'.$stk.'" ';
}
if(!empty($_GET['status'])){
    $status = check_string($_GET['status']);
    $where .= ' AND `status` = "'.$status.'" ';
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `user_id` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `user_id` =  "" ';
    }
}
if(!empty($_GET['user_id'])){
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = "'.$user_id.'" ';
}
if(!empty($_GET['reason'])){
    $reason = check_string($_GET['reason']);
    $where .= ' AND `reason` LIKE "%'.$reason.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `aff_withdraw` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `aff_withdraw` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("affiliate-withdraw&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&reason=$reason&create_gettime=$create_gettime&username=$username&stk=$stk&status=$status&trans_id=$trans_id&"), $from, $totalDatatable, $limit);

$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Affiliate Withdraw</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Affiliate Program</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Affiliate Withdraw</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default">
                                    <?=format_currency($CMSNT->get_row(" SELECT SUM(amount) FROM `aff_withdraw` WHERE `status` = 'completed' ")['SUM(amount)']);?>
                                </p>
                                <p class="mb-0 text-muted">Tổng số tiền đã rút</p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-danger rounded-circle fs-20"><i
                                        class='bx bxs-wallet-alt'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default">
                                    <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM `aff_withdraw` WHERE `status` = 'completed' AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ")['SUM(amount)']);?>
                                </p>
                                <p class="mb-0 text-muted">Tiền rút trong tháng <?=date('m');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-info rounded-circle fs-20"><i
                                        class='bx bxs-wallet-alt'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default">
                                    <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM aff_withdraw WHERE  `status` = 'completed' AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ")['SUM(amount)']);?>
                                </p>
                                <p class="mb-0 text-muted">Tiền rút trong tuần</p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-warning rounded-circle fs-20"><i
                                        class='bx bxs-wallet-alt'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default">
                                    <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM aff_withdraw WHERE  `status` = 'completed' AND `create_gettime` LIKE '%".$currentDate."%' ")['SUM(amount)']);?>
                                </p>
                                <p class="mb-0 text-muted">Tiền rút hôm nay</p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-primary rounded-circle fs-20"><i
                                        class='bx bxs-wallet-alt'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            ĐƠN RÚT TIỀN HOA HỒNG
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="affiliate-withdraw">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$trans_id;?>" name="trans_id"
                                        placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$stk;?>" name="stk"
                                        placeholder="<?=__('Số tài khoản');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$reason;?>" name="reason"
                                        placeholder="<?=__('Lý do');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control form-control-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 'pending' ? 'selected' : '';?> value="pending">
                                            <?=__('Pending');?></option>
                                        <option <?=$status == 'cancel' ? 'selected' : '';?> value="cancel">
                                            <?=__('Cancel');?></option>
                                        <option <?=$status == 'completed' ? 'selected' : '';?> value="completed">
                                            <?=__('Completed');?></option>
                                    </select>
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
                                        href="<?=base_url_admin('affiliate-withdraw');?>"><i class="fa fa-trash"></i>
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
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
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
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead class="table">
                                    <tr>
                                        <th scope="col"></th>
                                        <th><?=__('Mã giao dịch');?></th>
                                        <th><?=__('Thành viên');?></th>
                                        <th><?=__('Số tiền rút');?></th>
                                        <th><?=__('Tài khoản nhận tiền');?></th>
                                        <th><?=__('Trạng thái');?></th>
                                        <th><?=__('Thời gian');?></th>
                                        <th><?=__('Lý do');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td>
                                            <button type="button"
                                                onclick="modalEdit(`<?=$getUser['token'];?>`, `<?=$row['id'];?>`)"
                                                class="btn btn-icon btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Edit');?>">
                                                <i class="fa fa-fw fa-edit"></i>
                                            </button>
                                        </td>
                                        <td><?=$row['trans_id'];?></td>
                                        <td><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                        </td>
                                        <td class="text-right">
                                            <span
                                                class="badge bg-primary-gradient"><?=format_currency($row['amount']);?></span>
                                        </td>
                                        <td><?=$row['bank'];?> - <?=$row['stk'];?> - <?=$row['name'];?></td>
                                        <td class="text-center"><?=display_withdraw($row['status']);?></td>
                                        <td><span class="badge bg-light text-dark"><?=$row['create_gettime'];?></span>
                                        </td>
                                        <td><?=$row['reason'];?></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                            <div class="col-sm-12 col-md-12 mb-3">
                                <!--<button class="btn btn-danger btn-sm me-1" type="button" onclick="deleteConfirm()"-->
                                <!--    name="btn_delete"><i class="fas fa-trash mr-1"></i> Xóa bản ghi đã chọn</button>-->
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

<div class="modal fade" id="ModalDialog" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl dialog-scrollable">
        <div class="modal-content">
            <div id="modalEdit"></div>
        </div>
    </div>
</div>
<script>
function modalEdit(token, id) {
    $("#modalEdit").html('');
    $.get("<?=BASE_URL('ajaxs/admin/modal/withdraw-edit.php?id=');?>" + id + '&token=' + token, function(data) {
        $("#modalEdit").html(data);
    });
    $('#ModalDialog').modal('show')
}

function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeWithdraw',
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

function deleteConfirm() {
    var result = confirm("<?=__('Bạn có thực sự muốn xóa các bản ghi đã chọn không?');?>");
    if (result) {
        var checkbox = document.getElementsByName('checkbox');
        for (var i = 0; i < checkbox.length; i++) {
            if (checkbox[i].checked === true) {
                postRemove(checkbox[i].value);
            }
        }
    }
    setTimeout(function() {
        location.reload();
    }, 1000);
}
$(document).ready(function() {
    $('#check_all').on('click', function() {
        if (this.checked) {
            $('.checkbox').each(function() {
                this.checked = true;
            });
        } else {
            $('.checkbox').each(function() {
                this.checked = false;
            });
        }
    });
    $('.checkbox').on('click', function() {
        if ($('.checkbox:checked').length == $('.checkbox').length) {
            $('#check_all').prop('checked', true);
        } else {
            $('#check_all').prop('checked', false);
        }
    });
});
</script>
<script type="text/javascript">
function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Warning');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa item id ');?> " + id + " không ?",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Huỷ');?>"
    }).then((e) => {
        if (e) {
            postRemove(id);
            location.reload();
        }
    })
}
</script>