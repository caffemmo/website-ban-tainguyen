<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Logs',
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
if(checkPermission($getUser['admin'], 'view_logs') != true){
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
$content = '';
$createdate = '';
$ip = '';
$device  = '';
$username = '';
$shortByDate  = '';

if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `user_id` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `user_id` =  "" ';
    }
}
if(!empty($_GET['ip'])){
    $ip = check_string($_GET['ip']);
    $where .= ' AND `ip` LIKE "%'.$ip.'%" ';
}
if(!empty($_GET['device'])){
    $device = check_string($_GET['device']);
    $where .= ' AND `device` LIKE "%'.$device.'%" ';
}
if(isset($_GET['user_id']) && $_GET['user_id'] !== ''){
    $user_id = check_string(intval($_GET['user_id']));
    if($user_id == 0) {
        $where .= ' AND `user_id` = 0 ';
    } else {
        $where .= ' AND `user_id` = '.$user_id.' ';
    }
}
if(!empty($_GET['content'])){
    $content = check_string($_GET['content']);
    $where .= ' AND `action` LIKE "%'.$content.'%" ';
}
if(!empty($_GET['createdate'])){
    $create_date = check_string($_GET['createdate']);
    $createdate = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);
    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `createdate` >= '".$create_date_1[0]."' AND `createdate` <= '".$create_date_1[1]."' ";
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
        $where .= " AND `createdate` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(createdate) = $currentYear AND WEEK(createdate, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(createdate) = '$currentMonth' AND YEAR(createdate) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `logs` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `logs` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("logs&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&content=$content&createdate=$createdate&ip=$ip&device=$device&username=$username&"), $from, $totalDatatable, $limit);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-clock-rotate-left"></i> Nhật ký hoạt động</h1>
            <?php if(checkPermission($getUser['admin'], 'delete_logs') == true): ?>
            <div>
                <button class="btn btn-danger btn-sm" onclick="cleanupLogs()">
                    <i class="fa-solid fa-broom"></i> <?=__('Dọn dẹp nhật ký hoạt động');?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            NHẬT KÝ HOẠT ĐỘNG
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="logs">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$content;?>" name="content"
                                        placeholder="<?=__('Hành động');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$ip;?>" name="ip"
                                        placeholder="<?=__('Địa chỉ IP');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$device;?>" name="device"
                                        placeholder="<?=__('Thiết bị');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="createdate" class="form-control form-control-sm" id="daterange"
                                        value="<?=$createdate;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('logs');?>"><i
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
                                        <th><?=__('Hành động');?></th>
                                        <th><?=__('Thời gian');?></th>
                                        <th><?=__('Địa chỉ IP');?></th>
                                        <th><?=__('Thiết bị');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['id'];?></td>
                                        <td>
                                            <?php if($row['user_id'] == 0): ?>
                                                <span class="badge bg-info text-white"><i class="fas fa-server me-1"></i> Hệ thống</span>
                                            <?php else: ?>
                                                <a class="text-primary" href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?=$row['action'];?></td>
                                        <td><span class="badge bg-light text-dark" data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['createdate']));?>"><?=$row['createdate'];?></span></td>
                                        <td><span class="badge bg-danger-transparent"><?=$row['ip'];?></span></td>
                                        <td><small><?=$row['device'];?></small></td>
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
// Hàm dọn dẹp nhật ký hoạt động
function cleanupLogs() {
    Swal.fire({
        title: '<?=__('Dọn dẹp nhật ký hoạt động');?>',
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
                text: '<?=__('Bạn có chắc chắn muốn xóa tất cả nhật ký từ');?> ' + daysToKeep + ' <?=__('ngày trở lên? Hành động này không thể hoàn tác!');?>',
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
                            action: 'cleanupLogs',
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
                                text: '<?=__('Có lỗi xảy ra khi xóa nhật ký');?>',
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