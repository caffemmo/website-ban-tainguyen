<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Lịch sử ngân hàng'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

 
';
$body['footer'] = '
 
  
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
if(checkPermission($getUser['admin'], 'view_recharge') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 20;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$shortByDate = '';
$description = '';
$tid = '';
$create_gettime = '';
$type = '';
$method = '';


if(!empty($_GET['method'])){
    $method = check_string($_GET['method']);
    $where .= ' AND `method` LIKE "%'.$method.'%" ';
}
if(!empty($_GET['tid'])){
    $tid = check_string($_GET['tid']);
    $where .= ' AND `tid` = "'.$tid.'" ';
}
if(!empty($_GET['type'])){
    $type = check_string($_GET['type']);
    $where .= ' AND `type` = "'.$type.'" ';
}
if(!empty($_GET['description'])){
    $description = check_string($_GET['description']);
    $description    = str_replace(' ', '', $description);
    $where .= ' AND `description` LIKE "%'.$description.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $create_date_1 = str_replace('-', '/', $create_gettime);
    $create_date_1 = explode(' to ', $create_date_1);
    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_date_1[0]."' AND `create_gettime` <= '".$create_date_1[1]."' ";
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `log_bank_auto` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `log_bank_auto` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("log-auto-bank&limit=$limit&shortByDate=$shortByDate&time=$create_gettime&tid=$tid&description=$description&method=$method&type=$type&"), $from, $totalDatatable, $limit);


 
$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");


$total_yesterday = intval($CMSNT->get_row("SELECT SUM(amount) FROM log_bank_auto WHERE  `create_gettime` LIKE '%".$yesterday."%' ")['SUM(amount)']);
$total_today = $CMSNT->get_row("SELECT SUM(amount) FROM log_bank_auto WHERE  `create_gettime` LIKE '%".$currentDate."%' ")['SUM(amount)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(amount) FROM log_bank_auto ")['SUM(amount)'];

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-building-columns"></i> Lịch sử giao dịch Ngân Hàng</h1>
            <?php if(checkPermission($getUser['admin'], 'delete_bank_logs') == true): ?>
            <div>
                <button class="btn btn-danger btn-sm" onclick="cleanupBankLogs()">
                    <i class="fa-solid fa-broom"></i> <?=__('Dọn dẹp nhật ký ngân hàng');?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="log-auto-bank">
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$tid;?>" name="tid"
                                        placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$description;?>"
                                        name="description" placeholder="<?=__('Nội dung chuyển khoản');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$method;?>" name="method"
                                        placeholder="<?=__('Ngân hàng');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$type;?>" name="type"
                                        placeholder="<?=__('Type');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-sm btn-danger" href="<?=base_url_admin('log-auto-bank');?>"><i
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
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1">
                                            <?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2">
                                            <?=__('Tuần này');?>
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
                                <thead class="table">
                                    <tr>
                                        <th><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Ngân hàng');?></th>
                                        <th class="text-right"><?=__('Số tiền nạp');?></th>
                                        <th class="text-center"><?=__('Mã giao dịch');?></th>
                                        <th><?=__('Nội dung chuyển khoản');?></th>
                                        <th class="text-center">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><strong><?=$row['create_gettime'];?></strong> (<i><?= timeAgo(strtotime($row['create_gettime'])); ?></i>)</td>
                                        <td class="text-center"><b><?=$row['method'];?></b></td>
                                        <td class="text-right"><b
                                                style="color: green;"><?=format_currency($row['amount']);?></b>
                                        </td>
                                        <td class="text-center"><b style="color:red;"><?=$row['tid'];?></b></td>
                                        <td><?=$row['description'];?></td>
                                        <td class="text-center"><b><?=$row['type'];?></b></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of
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

<?php if(checkPermission($getUser['admin'], 'delete_logs') == true): ?>
<script>
// Hàm dọn dẹp nhật ký ngân hàng
function cleanupBankLogs() {
    Swal.fire({
        title: '<?=__('Dọn dẹp nhật ký ngân hàng');?>',
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
                text: '<?=__('Bạn có chắc chắn muốn xóa tất cả nhật ký ngân hàng từ');?> ' + daysToKeep + ' <?=__('ngày trở lên? Hành động này không thể hoàn tác!');?>',
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
                            action: 'cleanupBankLogs',
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
                                text: '<?=__('Có lỗi xảy ra khi xóa nhật ký ngân hàng');?>',
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