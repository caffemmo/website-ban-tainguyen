<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Transactions',
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
if(checkPermission($getUser['admin'], 'view_transactions') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 20;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$user_id = '';
$noidung = '';
$create_date = '';
$username = '';
$shortByDate  = '';
$transaction_type = '';



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
if(!empty($_GET['noidung'])){
    $noidung = check_string($_GET['noidung']);
    $where .= ' AND `noidung` LIKE "%'.$noidung.'%" ';
}
if(!empty($_GET['create_date'])){
    $create_date = check_string($_GET['create_date']);
    $createdate = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);

    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `thoigian` >= '".$create_date_1[0]."' AND `thoigian` <= '".$create_date_1[1]."' ";
    }
}
if(!empty($_GET['transaction_type'])){
    $transaction_type = check_string($_GET['transaction_type']);
    if($transaction_type == 'plus'){
        $where .= " AND (`sotiensau` - `sotientruoc`) > 0 ";
    }
    if($transaction_type == 'minus'){
        $where .= " AND (`sotiensau` - `sotientruoc`) < 0 ";
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
        $where .= " AND `thoigian` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(thoigian) = $currentYear AND WEEK(thoigian, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(thoigian) = '$currentMonth' AND YEAR(thoigian) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `dongtien` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `dongtien` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("transactions&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&noidung=$noidung&create_date=$create_date&username=$username&transaction_type=$transaction_type&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-money-bill-transfer"></i> Biến động số dư</h1>
            <?php if(checkPermission($getUser['admin'], 'delete_transactions') == true): ?>
            <div>
                <button class="btn btn-danger btn-sm" onclick="cleanupTransactions()">
                    <i class="fa-solid fa-broom"></i> <?=__('Dọn dẹp nhật ký số dư');?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            NHẬT KÝ THAY ĐỔI SỐ DƯ
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="transactions">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$noidung;?>" name="noidung"
                                        placeholder="<?=__('Lý do');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_date" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_date;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control form-control-sm" name="transaction_type">
                                        <option value=""><?=__('Tất cả giao dịch');?></option>
                                        <option value="plus" <?=isset($_GET['transaction_type']) && $_GET['transaction_type'] == 'plus' ? 'selected' : '';?>><?=__('Cộng tiền');?></option>
                                        <option value="minus" <?=isset($_GET['transaction_type']) && $_GET['transaction_type'] == 'minus' ? 'selected' : '';?>><?=__('Trừ tiền');?></option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('transactions');?>"><i class="fa fa-trash"></i>
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
                                        <option <?=$limit == 5000 ? 'selected' : '';?> value="5000">5.000</option>
                                        <option <?=$limit == 10000 ? 'selected' : '';?> value="10000">10.000</option>
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
                                        <th>#</th>
                                        <th><?=__('Username');?></th>
                                        <th>Số dư trước</th>
                                        <th>Số dư thay đổi</th>
                                        <th>Số dư hiện tại</th>
                                        <th>Thời gian</th>
                                        <th>Lý do</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['id'];?></td>
                                        <td><a class="text-primary" href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                        </td>
                                        <td class="text-right">
                                            <span><?=format_currency($row['sotientruoc']);?></span>
                                        </td>
                                        <?php if(($row['sotiensau'] - $row['sotientruoc']) > 0):?>
                                        <td class="text-right"><b
                                                style="color:green;">+<?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php elseif(($row['sotientruoc'] - $row['sotiensau']) > 0):?>
                                        <td class="text-right"><b
                                                style="color:red;">-<?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php else:?>
                                        <td class="text-right"><b><?=format_currency($row['sotienthaydoi']);?></b>
                                        </td>
                                        <?php endif?>
                                        <td class="text-right"><b style="color:blue;"><?=format_currency($row['sotiensau']);?></b>
                                        </td>
                                        <td><span class="badge bg-light text-dark" data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['thoigian']));?>"><?=$row['thoigian'];?></span></td>
                                        <td><i><?=$row['noidung'];?></i></td>
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

<?php if(checkPermission($getUser['admin'], 'delete_logs') == true): ?>
<script>
// Hàm dọn dẹp nhật ký số dư
function cleanupTransactions() {
    Swal.fire({
        title: '<?=__('Dọn dẹp nhật ký số dư');?>',
        html: '<div class="text-start">' +
              '<p class="mb-3"><?=__('Nhập số ngày cần giữ lại nhật ký. Tất cả nhật ký từ số ngày trở lên sẽ bị xóa.');?></p>' +
              '<p class="text-muted small mb-3"><?=__('Ví dụ: Nhập 30 sẽ xóa tất cả nhật ký từ 30 ngày trở lên');?></p>' +
              '<input type="number" id="daysToKeep" class="form-control" min="1" placeholder="<?=__('Số ngày cần giữ lại');?>" value="30">' +
              '</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?=__('Xóa');?>',
        cancelButtonText: '<?=__('Hủy');?>',
        inputValidator: (value) => {
            if (!value || value < 1) {
                return '<?=__('Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)');?>'
            }
        },
        preConfirm: () => {
            const days = document.getElementById('daysToKeep').value;
            if (!days || days < 1) {
                Swal.showValidationMessage('<?=__('Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)');?>');
                return false;
            }
            return days;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const daysToKeep = result.value;
            
            // Xác nhận lần nữa
            Swal.fire({
                title: '<?=__('Xác nhận xóa');?>',
                text: '<?=__('Bạn có chắc chắn muốn xóa tất cả nhật ký số dư từ');?> ' + daysToKeep + ' <?=__('ngày trở lên? Hành động này không thể hoàn tác!');?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?=__('Xác nhận xóa');?>',
                cancelButtonText: '<?=__('Hủy');?>'
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    // Hiển thị loading
                    Swal.fire({
                        title: '<?=__('Đang xử lý');?>...',
                        text: '<?=__('Vui lòng đợi');?>...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: '<?=base_url('ajaxs/admin/remove.php');?>',
                        method: 'POST',
                        dataType: 'JSON',
                        data: {
                            action: 'cleanupTransactions',
                            token: '<?=$getUser['token'];?>',
                            days_to_keep: daysToKeep
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: '<?=__('Thành công');?>',
                                    text: response.msg,
                                    icon: 'success',
                                    confirmButtonText: '<?=__('OK');?>'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: '<?=__('Lỗi');?>',
                                    text: response.msg,
                                    icon: 'error',
                                    confirmButtonText: '<?=__('OK');?>'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: '<?=__('Lỗi');?>',
                                text: '<?=__('Có lỗi xảy ra khi xóa nhật ký số dư');?>',
                                icon: 'error',
                                confirmButtonText: '<?=__('OK');?>'
                            });
                        }
                    });
                }
            });
        }
    });
}
</script>
<?php endif; ?>