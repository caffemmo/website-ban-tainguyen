<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Block IP').' | '.$CMSNT->site('title'),
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
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_block_ip') != true){
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Không được dùng chức năng này vì đây là trang web demo.').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_block_ip') != true){
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng edit_block_ip') . '")){window.history.back();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình block ip')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình block ip'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("'.__('Save successfully!').'")){window.history.back().location.reload();}</script>');
} 


if (isset($_POST['AddIPBlock'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này vì đây là trang web demo').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_block_ip') != true){
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }
    if(empty($_POST['ip'])){
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng nhập địa chỉ IP cần chặn') . '")){window.history.back().location.reload();}</script>');
    }
    $ip = check_string($_POST['ip']);


    $isInsert = $CMSNT->insert("block_ip", [
        'ip'                => $ip,
        'attempts'          => 0,
        'banned'            => 1,
        'reason'            => !empty($_POST['reason']) ? check_string($_POST['reason']) : NULL,
        'create_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Block IP ($ip)"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Block IP ($ip)", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm thành công!') . '")){location.href = "'.base_url_admin('block-ip').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
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
$ip = '';
$shortByDate  = '';

if(!empty($_GET['ip'])){
    $ip = check_string($_GET['ip']);
    $where .= ' AND `ip` LIKE "%'.$ip.'%" ';
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
$listDatatable = $CMSNT->get_list(" SELECT * FROM `block_ip` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `block_ip` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("block-ip&limit=$limit&shortByDate=$shortByDate&ip=$ip&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-ban"></i> <?=__('Block IP');?></h1>
        </div>
        <div class="row">
            
             
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('DANH SÁCH IP BỊ CHẶN');?>
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> <?=__('Thêm IP cần Block');?></button>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url(); ?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="block-ip">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$ip;?>" name="ip"
                                        placeholder="<?=__('Tìm IP');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Tìm kiếm');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('block-ip');?>"><i
                                            class="fa fa-trash"></i>
                                        <?=__('Xóa bộ lọc');?>
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
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center"><?=__('Địa chỉ IP');?></th>
                                        <th class="text-center"><?=__('Attempts');?></th>
                                        <th class="text-center"><?=__('Banned');?></th>
                                        <th class="text-center"><?=__('Lý do');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox"
                                                    data-id="<?=$row['id'];?>" name="checkbox"
                                                    value="<?=$row['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center"><?=$row['ip'];?></td>
                                        <td class="text-center"><span style="font-size: 15px;"
                                                class="badge bg-info"><?=format_cash($row['attempts']);?></span>
                                        </td>
                                        <td class="text-center">
                                            <?=$row['banned'] == 1 ? '<span class="badge bg-danger">Banned</span>' : '<span class="badge bg-success">Live</span>';?>
                                        </td>
                                        <td><?=$row['reason'];?></td>
                                        <td class="text-center"><?=$row['create_gettime'];?></td>
                                        <td class="text-center">
                                            <a type="button" onclick="remove('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="8">
                                        <div class="btn-list">
                                            <button type="button" id="btn_delete_row"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> <?=__('Xóa IP Đã Chọn');?></button>
                                        </div>
                                    </td>
                                </tfoot>
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





<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> <?=__('Thêm IP cần Block');?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Địa chỉ IP cần Block');?> (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="ip" name="ip"
                                    placeholder="<?=__('Nhập địa chỉ IP cần Block');?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Lý do chặn (nếu có)');?></label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <textarea class="form-control" name="reason"
                                    placeholder="<?=__('Nhập lý do block ip nếu có');?>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal"><?=__('Đóng');?></button>
                    <button type="submit" name="AddIPBlock" class="btn btn-primary shadow-primary btn-wave"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?=__('Xác nhận');?></button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php
require_once(__DIR__.'/footer.php');
?>


<script>
$(function() {
    $('#check_all_checkbox').on('click', function() {
        $('.checkbox').prop('checked', this.checked);
    });
    $('.checkbox').on('click', function() {
        $('#check_all_checkbox').prop('checked', $('.checkbox:checked')
            .length === $('.checkbox').length);
    });
});
</script>


<script>
$("#btn_delete_row").click(function() {
    var checkboxes = document.querySelectorAll('input[name="checkbox"]:checked');
    if (checkboxes.length === 0) {
        return showMessage('<?=__('Vui lòng chọn ít nhất một IP.');?>', 'error');
    }
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa');?> " + checkboxes.length +
            " <?=__('IP bạn đã chọn khi nhấn Đồng ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            delete_records();
        }
    });
});

function delete_records() {
    var checkbox = document.getElementsByName('checkbox');
    var selectedIds = [];
    
    // Thu thập tất cả ID được chọn
    for (var i = 0; i < checkbox.length; i++) {
        if (checkbox[i].checked === true) {
            selectedIds.push(checkbox[i].value);
        }
    }
    
    // Gọi ajax một lần với tất cả ID
    if (selectedIds.length > 0) {
        postRemoveMultiple(selectedIds);
    }
}
</script>

<script>
function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeBlockIP',
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

function postRemoveMultiple(ids) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeMultipleBlockIP',
            ids: ids
        },
        success: function(result) {
            if (result.status == 'success') {
                Swal.fire({
                    title: "<?=__('Thành công!');?>",
                    text: result.msg,
                    icon: "success"
                });
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                Swal.fire({
                    title: "<?=__('Lỗi!');?>",
                    text: result.msg,
                    icon: "error"
                });
            }
        },
        error: function() {
            Swal.fire({
                title: "<?=__('Lỗi!');?>",
                text: "<?=__('Có lỗi xảy ra khi xóa IP');?>",
                icon: "error"
            });
        }
    });
}

function remove(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận xóa địa chỉ IP');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa địa chỉ IP này không ?');?>",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Không');?>"
    }).then((e) => {
        if (e) {
            postRemove(id);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    })
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var button = document.getElementById('open-card-config');
    var card = document.getElementById('card-config');

    // Thêm sự kiện click cho nút button
    button.addEventListener('click', function() {
        // Kiểm tra nếu card đang hiển thị thì ẩn đi, ngược lại hiển thị
        if (card.style.display === 'none' || card.style.display === '') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>