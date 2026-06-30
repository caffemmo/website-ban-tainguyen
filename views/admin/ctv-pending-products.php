<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Sản phẩm chờ duyệt CTV').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
    .status-badge {
        font-size: 12px;
        padding: 4px 8px;
    }
</style>
';
$body['footer'] = '
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="'.base_url('public/theme/').'assets/js/select2.js"></script>
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
// Pagination logic
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

// Build WHERE clause for pending CTV products
$where = " `pending` = 1 AND `user_id` IN (SELECT `id` FROM `users` WHERE `ctv` = 1) ";
$name = '';
$create_gettime = '';
$category_id = '';
$status = '';
$code = '';
$ctv_id = '';

if(!empty($_GET['code'])){
    $code = check_string($_GET['code']);
    $where .= ' AND `code` = "'.$code.'" ';
}
if(!empty($_GET['ctv_id'])){
    $ctv_id = check_string($_GET['ctv_id']);
    $where .= ' AND `user_id` = "'.$ctv_id.'" ';
}
if(!empty($_GET['category_id'])){
    $category_id = check_string($_GET['category_id']);
    $where .= ' AND `category_id` = "'.$category_id.'" ';
}
if(!empty($_GET['name'])){
    $name = check_string($_GET['name']);
    $where .= ' AND (`name` LIKE "%'.$name.'%" OR `code` LIKE "%'.$name.'%") ';
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `products` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `products` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("ctv-pending-products&limit=$limit&name=$name&create_gettime=$create_gettime&category_id=$category_id&code=$code&ctv_id=$ctv_id&"), $from, $totalDatatable, $limit);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-clock"></i> <?=__('Sản phẩm chờ duyệt CTV');?>
            </h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="<?=base_url();?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row g-2 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="ctv-pending-products">
                                <input type="hidden" value="<?=$getUser['token'];?>" id="token">
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$name;?>" name="name" placeholder="<?=__('Tên sản phẩm');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control" value="<?=$code;?>" name="code" placeholder="<?=__('Mã sản phẩm');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control js-example-basic-single" name="ctv_id">
                                        <option value=""><?=__('-- Chọn CTV --');?></option>
                                        <?php foreach($CMSNT->get_list("SELECT * FROM `users` WHERE `ctv` = 1 ORDER BY `id` DESC") as $ctv):?>
                                        <option <?=$ctv_id == $ctv['id'] ? 'selected' : '';?> value="<?=$ctv['id'];?>">
                                            <?=$ctv['email'];?> (ID: <?=$ctv['id'];?>)
                                        </option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control js-example-basic-single" name="category_id">
                                        <option value=""><?=__('-- Chọn chuyên mục --');?></option>
                                        <?php foreach($CMSNT->get_list("SELECT * FROM `categories` ORDER BY `id` DESC") as $category):?>
                                        <option <?=$category_id == $category['id'] ? 'selected' : '';?> value="<?=$category['id'];?>">
                                            <?=$category['name'];?>
                                        </option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-6">
                                    <input type="text" name="create_gettime" class="form-control" id="daterange"
                                        value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <button class="btn btn-hero btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-danger" href="<?=base_url_admin('ctv-pending-products');?>"><i
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

                        <div class="mb-3" id="bulkActionsContainer" style="display: none;">
                            <div class="btn-list">
                                <button class="btn btn-success btn-sm" onclick="bulkApproveProducts()" id="btnBulkApprove">
                                    <i class="fa-solid fa-check-double"></i> <?=__('Duyệt đã chọn');?>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="bulkDeleteProducts()" id="btnBulkDelete">
                                    <i class="fa-solid fa-trash"></i> <?=__('Xóa đã chọn');?>
                                </button>
                                <span class="text-muted ms-2" id="selectedCount">0 <?=__('sản phẩm được chọn');?></span>
                            </div>
                        </div>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th class="text-center"><?=__('Thông tin sản phẩm');?></th>
                                        <th class="text-center"><?=__('CTV');?></th>
                                        <th class="text-center"><?=__('Giá bán');?></th>
                                        <th class="text-center"><?=__('Chuyên mục');?></th>
                                        <th class="text-center"><?=__('Thời gian tạo');?></th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listDatatable)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="text-center py-4">
                                                <i class="fa-solid fa-box-open fs-48 text-muted"></i>
                                                <p class="text-muted mt-2"><?=__('Không có sản phẩm chờ duyệt nào');?></p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($listDatatable as $product): ?>
                                    <?php 
                                        $ctv = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '".$product['user_id']."'");
                                        $category = $CMSNT->get_row("SELECT * FROM `categories` WHERE `id` = '".$product['category_id']."'");
                                        $images = !empty($product['images']) ? explode(',', $product['images']) : [];
                                        $firstImage = !empty($images) ? $images[0] : '';
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="product-checkbox" value="<?=$product['id'];?>" onchange="updateSelectedCount()">
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-dark"><?=$product['name'];?></strong>
                                                <small class="text-muted"><?=__('Mã');?>: <?=$product['code'];?></small>
                                                <?php if(!empty($product['short_desc'])): ?>
                                                <small class="text-muted mt-1"><?=substr($product['short_desc'], 0, 100);?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-primary"><?=$ctv['email'];?></strong>
                                                <small class="text-muted">ID: <?=$ctv['id'];?></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <strong class="text-success"><?=format_currency($product['price']);?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if($category): ?>
                                            <span class="badge bg-info-transparent text-info"><?=$category['name'];?></span>
                                            <?php else: ?>
                                            <span class="text-muted"><?=__('Không có');?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <strong data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($product['create_gettime']));?>"><?=$product['create_gettime'];?></strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-list">
                                                <button class="btn btn-success btn-sm shadow-success btn-wave" 
                                                    onclick="approveProduct(<?=$product['id'];?>)" 
                                                    data-toggle="tooltip" title="<?=__('Duyệt sản phẩm');?>">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <a href="<?=base_url_admin('product-edit&id='.$product['id']);?>" 
                                                    class="btn btn-warning btn-sm shadow-warning btn-wave" 
                                                    data-toggle="tooltip" title="<?=__('Chỉnh sửa');?>">
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm shadow-danger btn-wave" 
                                                    onclick="deleteProduct(<?=$product['id'];?>)" 
                                                    data-toggle="tooltip" title="<?=__('Xóa sản phẩm');?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="text-right">
                                                <?=__('Tổng sản phẩm chờ duyệt');?>: <strong style="color: orange;"><?=format_cash($totalDatatable);?></strong>
                                            </div>
                                        </td>
                                    </tr>
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
// Hàm duyệt sản phẩm
function approveProduct(productId) {
    Swal.fire({
        title: '<?=__('Xác nhận duyệt');?>',
        text: '<?=__('Bạn có chắc chắn muốn duyệt sản phẩm này?');?>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?=__('Duyệt');?>',
        cancelButtonText: '<?=__('Hủy');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?=base_url('ajaxs/admin/update.php');?>',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'approveCtvProduct',
                    token: '<?=$getUser['token'];?>',
                    product_id: productId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage(response.msg, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?=__('Có lỗi xảy ra');?>', 'error');
                }
            });
        }
    });
}

// Hàm xóa sản phẩm
function deleteProduct(productId) {
    Swal.fire({
        title: '<?=__('Xóa sản phẩm');?>',
        text: '<?=__('Bạn có chắc chắn muốn xóa sản phẩm này? Hành động này không thể hoàn tác!');?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?=__('Xóa');?>',
        cancelButtonText: '<?=__('Hủy');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?=base_url('ajaxs/admin/remove.php');?>',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'removeCtvProduct',
                    token: '<?=$getUser['token'];?>',
                    product_id: productId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage(response.msg, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?=__('Có lỗi xảy ra');?>', 'error');
                }
            });
        }
    });
}

// Hàm chọn/bỏ chọn tất cả
function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.product-checkbox');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

// Hàm cập nhật số lượng sản phẩm được chọn
function updateSelectedCount() {
    var checkboxes = document.querySelectorAll('.product-checkbox:checked');
    var count = checkboxes.length;
    var selectedCountEl = document.getElementById('selectedCount');
    var bulkActionsContainer = document.getElementById('bulkActionsContainer');
    var selectAll = document.getElementById('selectAll');
    
    selectedCountEl.textContent = count + ' <?=__('sản phẩm được chọn');?>';
    
    // Hiển thị/ẩn container các nút hành động
    if (count > 0) {
        bulkActionsContainer.style.display = 'block';
    } else {
        bulkActionsContainer.style.display = 'none';
    }
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    var allCheckboxes = document.querySelectorAll('.product-checkbox');
    if (allCheckboxes.length > 0) {
        selectAll.checked = (count === allCheckboxes.length);
    } else {
        selectAll.checked = false;
    }
}

// Hàm lấy danh sách ID sản phẩm được chọn
function getSelectedProductIds() {
    var checkboxes = document.querySelectorAll('.product-checkbox:checked');
    var ids = [];
    checkboxes.forEach(function(checkbox) {
        ids.push(parseInt(checkbox.value));
    });
    return ids;
}

// Hàm duyệt nhiều sản phẩm
function bulkApproveProducts() {
    var productIds = getSelectedProductIds();
    
    if (productIds.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một sản phẩm');?>', 'warning');
        return;
    }
    
    Swal.fire({
        title: '<?=__('Xác nhận duyệt');?>',
        text: '<?=__('Bạn có chắc chắn muốn duyệt');?> ' + productIds.length + ' <?=__('sản phẩm đã chọn?');?>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?=__('Duyệt');?>',
        cancelButtonText: '<?=__('Hủy');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?=base_url('ajaxs/admin/update.php');?>',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'bulkApproveCtvProducts',
                    token: '<?=$getUser['token'];?>',
                    product_ids: JSON.stringify(productIds)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage(response.msg, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?=__('Có lỗi xảy ra');?>', 'error');
                }
            });
        }
    });
}

// Hàm xóa nhiều sản phẩm
function bulkDeleteProducts() {
    var productIds = getSelectedProductIds();
    
    if (productIds.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một sản phẩm');?>', 'warning');
        return;
    }
    
    Swal.fire({
        title: '<?=__('Xóa sản phẩm');?>',
        text: '<?=__('Bạn có chắc chắn muốn xóa');?> ' + productIds.length + ' <?=__('sản phẩm đã chọn? Hành động này không thể hoàn tác!');?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<?=__('Xóa');?>',
        cancelButtonText: '<?=__('Hủy');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?=base_url('ajaxs/admin/remove.php');?>',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'bulkRemoveCtvProducts',
                    token: '<?=$getUser['token'];?>',
                    product_ids: JSON.stringify(productIds)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage(response.msg, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?=__('Có lỗi xảy ra');?>', 'error');
                }
            });
        }
    });
}

</script>
