<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý sản phẩm').' | CTV Panel | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
';
$body['footer'] = '
<!-- Select2 Cdn -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Internal Select-2.js -->
<script src="'.base_url('public/theme/').'assets/js/select2.js"></script>
';
require_once(__DIR__.'/../../models/is_ctv.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

// Pagination logic giống Admin
if(isset($_GET['limit'])){
    $limit = validate_int($_GET['limit'], 1, 1000);
    if ($limit === false) {
        $limit = 10;
    }
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = validate_int($_GET['page'], 1);
    if ($page === false) {
        $page = 1;
    }
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;

// Build WHERE clause giống Admin
$where = " `user_id` = ? ";
$where_params = [$getUser['id']];
$name = '';
$create_gettime = '';
$category_id = '';
$status = '';
$code = '';

if(!empty($_GET['code'])){
    $code = validate_alphanumeric($_GET['code'], 50);
    if ($code !== false) {
        $where .= ' AND `code` = ? ';
        $where_params[] = $code;
    }
}
if(!empty($_GET['status'])){
    $status = validate_int($_GET['status'], 1, 2);
    if ($status !== false) {
        if($status == 2){
            $where .= ' AND `status` = 0 ';
        }else if($status == 1){
            $where .= ' AND `status` = 1 ';
        }
    }
}
if(!empty($_GET['category_id'])){
    $category_id = validate_int($_GET['category_id'], 1);
    if ($category_id !== false) {
        $where .= ' AND `category_id` = ? ';
        $where_params[] = $category_id;
    }
}
if(!empty($_GET['name'])){
    $name = validate_string($_GET['name'], 255);
    if ($name !== false) {
        $where .= ' AND (`name` LIKE ? OR `code` LIKE ?) ';
        $where_params[] = "%$name%";
        $where_params[] = "%$name%";
    }
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = validate_string($_GET['create_gettime'], 50);
    if ($create_gettime !== false) {
        $createdate = $create_gettime;
        $create_gettime_1 = str_replace('-', '/', $create_gettime);
        $create_gettime_1 = explode(' to ', $create_gettime_1);

        if($create_gettime_1[0] != $create_gettime_1[1]){
            $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
            $where .= " AND `create_gettime` >= ? AND `create_gettime` <= ? ";
            $where_params[] = $create_gettime_1[0];
            $where_params[] = $create_gettime_1[1];
        }
    }
}

$listDatatable = $CMSNT->get_list_safe("SELECT * FROM `products` WHERE $where ORDER BY `id` DESC LIMIT ?, ?", array_merge($where_params, [$from, $limit]));
$totalDatatable = $CMSNT->num_rows_safe("SELECT * FROM `products` WHERE $where ORDER BY id DESC", $where_params);
$urlDatatable = pagination(base_url_ctv("products&limit=$limit&name=$name&create_gettime=$create_gettime&category_id=$category_id&status=$status&code=$code&"), $from, $totalDatatable, $limit);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-cart-shopping"></i> <?=__('Sản phẩm CTV');?></h1>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('DANH SÁCH SẢN PHẨM CTV');?>
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?=base_url_ctv('product-add');?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> <?=__('Thêm sản phẩm mới');?></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="<?=base_url_ctv();?>" class="align-items-center mb-3" name="formSearch"
                            method="GET">
                            <div class="row g-2 mb-3">
                                <input type="hidden" name="module" value="ctv">
                                <input type="hidden" name="action" value="products">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$name;?>" name="name"
                                        placeholder="<?=__('Tên sản phẩm');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control" value="<?=$code;?>" name="code"
                                        placeholder="<?=__('Mã kho hàng');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control" name="status">
                                        <option value=""><?=__('-- Trạng thái --');?></option>
                                        <option <?=$status == 1 ? 'selected' : '';?> value="1"><?=__('Hiển Thị');?></option>
                                        <option <?=$status == 2 ? 'selected' : '';?> value="2"><?=__('Ẩn');?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control js-example-basic-single" name="category_id">
                                        <option value=""><?=__('-- Chuyên mục --');?></option>
                                        <?php foreach($CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = 0", []) as $option):?>
                                        <option disabled value="<?=$option['id'];?>"><?=$option['name'];?></option>
                                        <?php foreach($CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = ?", [$option['id']]) as $option1):?>
                                        <option <?=$category_id == $option1['id'] ? 'selected' : '';?>
                                            value="<?=$option1['id'];?>">__<?=$option1['name'];?></option>
                                        <?php endforeach?>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control" id="daterange"
                                        value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-wave btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-wave btn-outline-danger btn-sm" href="<?=base_url_ctv('products');?>"><i
                                            class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?=__('Show');?> :</label>
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
                            </div>
                        </form>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox_product" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                        <th><?=__('Sản phẩm');?></th>
                                        <th class="text-center"><?=__('Chuyên mục');?></th>
                                        <th class="text-center"><?=__('Phê duyệt');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th class="text-center"><?=__('Giá bán');?></th>
                                        <th class="text-center"><?=__('Chi tiết');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $product): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox_product"
                                                    data-id="<?=$product['id'];?>" name="checkbox_product"
                                                    value="<?=$product['id'];?>" />
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($product['pending'] == 0): ?>
                                            <a type="button"
                                                href="<?=base_url_ctv('product-stock&code='.$product['code']);?>"
                                                class="btn btn-warning-gradient btn-wave btn-sm"
                                                data-bs-toggle="tooltip" title="<?=__('Kho hàng');?>">
                                                <i class="fa-solid fa-cart-shopping"></i> <?=__('Kho hàng');?>
                                            </a>
                                            <?php endif?>
                                            <a type="button"
                                                href="<?=base_url_ctv('product-edit&id='.$product['id']);?>"
                                                class="btn btn-sm btn-secondary shadow-secondary btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Chỉnh sửa');?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a type="button" onclick="remove('<?=$product['id'];?>')"
                                                class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Xóa');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <small><a
                                                    href="<?=base_url_ctv('product-edit&id='.$product['id']);?>"><?=$product['name'];?></a>
                                        </td>
                                        <td class="text-center"><span
                                                class="badge bg-primary"><?=$product['category_id'] != 0 ? getRowRealtime('categories', $product['category_id'], 'name') : '';?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($product['pending'] == 1): ?>
                                                <span class="badge bg-warning"><?=__('Chờ duyệt');?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?=__('Đã duyệt');?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch form-check-lg" 
                                                onchange="update_status_product(`<?=$product['id'];?>`)">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="status<?=$product['id'];?>" value="1" 
                                                    <?=$product['status'] == 1 ? 'checked=""' : '';?> 
                                                    <?=$product['pending'] == 1 ? 'disabled' : '';?>>
                                            </div>
                                        </td>
                                        <td>
                                            <?=__('Giá bán');?>: <b
                                                style="color:red;"><?=format_currency($product['price']);?></b><br>
                                            <?php if($product['discount'] > 0): ?>
                                            <?=__('Giảm giá');?>: <b style="color:blue;"><?=$product['discount'];?>%</b>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color:green;"><?=__('Live');?></span>:
                                            <b><?=format_cash($product['supplier_id'] == 0 ? getStock($product['code']) : $product['api_stock']);?></b><br>
                                            <span style="color:red;"><?=__('Die');?></span>:
                                            <b><?=format_cash($CMSNT->get_row_safe("SELECT COUNT(id) as total FROM product_die WHERE `product_code` = ?", [$product['code']])['total']);?></b><br>
                                            <?=__('Đã bán');?>: <b><?=format_cash($product['sold']);?></b>
                                        </td>
                                        <td><small><?=$product['create_gettime'];?></small></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="9">
                                        <div class="btn-list">
                                            <button type="button" id="btn_delete_product"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> <?=__('XÓA SẢN PHẨM');?></button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?=__('Showing');?> <?=$limit;?> <?=__('of');?> <?=format_cash($totalDatatable);?>
                                    <?=__('Results');?></p>
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
<script>
$(function() {
    $('#check_all_checkbox_product').on('click', function() {
        $('.checkbox_product').prop('checked', this.checked);
    });
    $('.checkbox_product').on('click', function() {
        $('#check_all_checkbox_product').prop('checked', $('.checkbox_product:checked')
            .length === $('.checkbox_product').length);
    });
});
</script>

<script>
function post_remove_product(id) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/ctv/remove.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            id: id,
            action: 'deleteProduct'
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
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

function remove(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác Nhận Xóa sản phẩm');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa sản phẩm ID');?> " + id + " <?=__('không');?> ?",
        confirmText: "<?=__('Đồng Ý');?>",
        cancelText: "<?=__('Hủy');?>"
    }).then((e) => {
        if (e) {
            post_remove_product(id);
            setTimeout(function() {
                location.reload();
            }, 500);
        }
    })
}
</script>

<script>
$(document).ready(function() {
    $("#btn_delete_product").click(function() {
        var checkboxes = document.querySelectorAll('input[name="checkbox_product"]:checked');
        if (checkboxes.length === 0) {
            showMessage('<?=__('Vui lòng chọn ít nhất một sản phẩm.');?>', 'error');
            return;
        }
        Swal.fire({
            title: "<?=__('Bạn có chắc không?');?>",
            text: "<?=__('Hệ thống sẽ xóa');?> " + checkboxes.length +
                " <?=__('sản phẩm bạn chọn khi nhấn Đồng Ý');?>",
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
});
</script>

<script>
function update_status_product(id) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/ctv/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'update_status_product',
            id: id,
            status: $('#status' + id + ':checked').val() ? 1 : 0
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            alert('<?=__('Có lỗi xảy ra');?>');
            location.reload();
        }
    });
}

function delete_records() {
    var checkbox = document.getElementsByName('checkbox_product');

    function postUpdatesSequentially(index) {
        if (index < checkbox.length) {
            if (checkbox[index].checked === true) {
                post_remove_product(checkbox[index].value);
            }
            setTimeout(function() {
                postUpdatesSequentially(index + 1);
            }, 100);
        } else {
            Swal.fire({
                title: "<?=__('Thành công!');?>",
                text: "<?=__('Xóa sản phẩm thành công');?>",
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