<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Khuyến mãi Nạp Tiền',
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
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_promotion') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddPromotion'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used as this is a demo site.').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_promotion') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if(empty($_POST['min'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập số tiền tối thiểu")){window.history.back().location.reload();}</script>');
    }
    $min = check_string($_POST['min']);
    if($min <= 0){
        die('<script type="text/javascript">if(!alert("Số tiền nạp tối thiểu không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    if(empty($_POST['discount'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập phần trăm khuyến mãi")){window.history.back().location.reload();}</script>');
    }
    $discount = check_string($_POST['discount']);
    
    $isInsert = $CMSNT->insert("promotions", [
        'discount'          => $discount,
        'create_gettime'    => gettime(),
        'min'               => $min
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Promotion (".format_currency($min).")"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Promotion (".format_currency($min).")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công!")){location.href = "'.base_url_admin('promotions').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại!")){window.history.back().location.reload();}</script>');
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
$shortByDate  = '';

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
$listDatatable = $CMSNT->get_list(" SELECT * FROM `promotions` WHERE $where ORDER BY `min` ASC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `promotions` WHERE $where ORDER BY `min` ASC ");
$urlDatatable = pagination(base_url_admin("promotions&limit=$limit&shortByDate=$shortByDate&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tags"></i> Khuyến mãi Nạp Tiền</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH MỐC NẠP TIỀN
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> Tạo mốc nạp mới</button>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="promotions">

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
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center">Số tiền nạp tổi thiểu</th>
                                        <th class="text-center">Khuyến mãi thêm</th>
                                        <th class="text-center">Thời gian thêm</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $promotion): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox"
                                                    data-id="<?=$promotion['id'];?>" name="checkbox"
                                                    value="<?=$promotion['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center"><b style="font-size:15px;">>=
                                                <?=format_currency($promotion['min']);?></b></td>
                                        <td class="text-center"><span style="font-size: 15px;"
                                                class="badge bg-primary"><?=$promotion['discount'];?>%</span></td>
                                        <td class="text-center"><?=$promotion['create_gettime'];?></td>
                                        <td class="text-center">
                                            <a type="button" onclick="remove('<?=$promotion['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="6">
                                        <div class="btn-list">
                                            <button type="button" id="btn_delete_product"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> XÓA DỮ LIỆU ĐÃ CHỌN</button>
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
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> Tạo mốc nạp mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Nạp tối thiểu (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" name="min" required>
                                <span class="input-group-text">
                                    <?=currencyDefault();?>
                                </span>
                            </div>
                            <small>Số tiền nạp tối thiểu để được nhận khuyến mãi</small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Khuyến mãi (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" name="discount" required>
                                <span class="input-group-text">
                                    <i class="fa-solid fa-percent"></i>
                                </span>
                            </div>
                            <small>Nhập chiết khấu khuyến mãi VD: 10 (tức khuyến mãi 10% khi nhập nạp tiền đủ
                                mốc)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddPromotion" class="btn btn-primary shadow-primary btn-wave"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?=__('Submit');?></button>
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
$("#btn_delete_product").click(function() {
    var checkboxes = document.querySelectorAll('input[name="checkbox"]:checked');
    if (checkboxes.length === 0) {
        showMessage('Vui lòng chọn ít nhất một bản ghi', 'error');
        return;
    }
    Swal.fire({
        title: "Bạn có chắc không?",
        text: "Hệ thống sẽ xóa " + checkboxes.length +
            " dữ liệu bạn đã chọn khi nhấn Đồng Ý",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Đồng ý",
        cancelButtonText: "Đóng"
    }).then((result) => {
        if (result.isConfirmed) {
            delete_records();
        }
    });
});

function delete_records() {
    var checkbox = document.getElementsByName('checkbox');

    function postUpdatesSequentially(index) {
        if (index < checkbox.length) {
            if (checkbox[index].checked === true) {
                postRemove(checkbox[index].value);
            }
            setTimeout(function() {
                postUpdatesSequentially(index + 1);
            }, 100);
        } else {
            Swal.fire({
                title: "Thành công!",
                text: "Xóa dữ liệu thành công",
                icon: "success"
            });
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    }
    postUpdatesSequentially(0);
}
</script>

<script>
function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removePromotion',
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

function remove(id) {
    cuteAlert({
        type: "question",
        title: "Xác nhận xóa Promotion",
        message: "Bạn có chắc chắn muốn xóa Promotion này không ?",
        confirmText: "Đồng ý",
        cancelText: "Không"
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