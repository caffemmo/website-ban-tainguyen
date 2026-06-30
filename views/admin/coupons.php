<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Mã giảm giá',
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
if(checkPermission($getUser['admin'], 'view_coupon') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddCoupon'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used as this is a demo site.').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_coupon') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if(empty($_POST['code'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập mã giảm giá cần tạo")){window.history.back().location.reload();}</script>');
    }
    $code = check_string($_POST['code']);
    if($CMSNT->get_row("SELECT * FROM `coupons` WHERE `code` = '$code' ")){
        die('<script type="text/javascript">if(!alert("Mã giảm giá này đã tồn tại trong hệ thống")){window.history.back().location.reload();}</script>');
    }
    //
    if(empty($_POST['amount'])){
        die('<script type="text/javascript">if(!alert("Số lượng không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    $amount = check_string($_POST['amount']);
    if($amount <= 0){
        die('<script type="text/javascript">if(!alert("Số lượng không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    //
    if(empty($_POST['discount'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập chiết khấu giảm giá")){window.history.back().location.reload();}</script>');
    }
    $discount = check_string($_POST['discount']);
    //
    $product_id = '';
    if (!empty($_POST['product_id']) && is_array($_POST['product_id'])) {
        $product_id = json_encode($_POST['product_id']);
    }
    //
    if(empty($_POST['min'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập giá trị đơn hàng tối thiểu")){window.history.back().location.reload();}</script>');
    }
    $min = check_string($_POST['min']);
    if($min <= 0){
        die('<script type="text/javascript">if(!alert("Giá trị đơn hàng tối thiểu không hợp lệ")){window.history.back().location.reload();}</script>');
    }
    //
    if(empty($_POST['max'])){
        die('<script type="text/javascript">if(!alert("Vui lòng nhập giá trị đơn hàng tối đa")){window.history.back().location.reload();}</script>');
    }
    $max = check_string($_POST['max']);
    if($max <= 0){
        die('<script type="text/javascript">if(!alert("Giá trị đơn hàng tối đa không hợp lệ")){window.history.back().location.reload();}</script>');
    }

    $isInsert = $CMSNT->insert("coupons", [
        'code'          => $code,
        'product_id'    => $product_id,
        'amount'        => $amount,
        'used'          => 0,
        'discount'      => $discount,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime(),
        'min'           => $min,
        'max'           => $max
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Coupon ($code)"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Coupon ($code)", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công!")){location.href = "'.base_url_admin('coupons').'";}</script>');
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
$create_gettime = '';
$code = '';
$shortByDate  = '';

if(!empty($_GET['code'])){
    $code = check_string($_GET['code']);
    $where .= ' AND `code` LIKE "%'.$code.'%" ';
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
$listDatatable = $CMSNT->get_list(" SELECT * FROM `coupons` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `coupons` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("coupons&limit=$limit&shortByDate=$shortByDate&code=$code&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tags"></i> Mã giảm giá</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page">Coupons</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH MÃ GIẢM GIÁ
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> Tạo mã giảm giá mới</button>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="coupons">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$code;?>" name="code"
                                        placeholder="Mã giảm giá">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('coupons');?>"><i
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
                                        <th>Mã giảm giá</th>
                                        <th>Sản phẩm áp dụng</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-center">Đã sử dụng</th>
                                        <th>Giảm</th>
                                        <th>Thời gian</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><b><?=$row['code'];?></b><br>
                                            (<?=$row['used'] >= $row['amount'] ? '<span style="color:red">Đã sử dụng hết</span>' : '<span style="color:green">Còn '.$AWWW2=$row['amount'] - $row['used'].' lượt sử dụng</span>';?>)
                                        </td>
                                        <td>
                                            <?php 
                                            if(empty($row['product_id'])){
                                                echo '<span class="badge bg-info-transparent">Áp dụng cho toàn bộ sản phẩm</span>';
                                            }else{
                                                foreach(json_decode($row['product_id']) as $product_id){
                                                    echo '<span class="badge bg-primary-transparent">'.getRowRealtime('products', $product_id, 'name').'</span><br>';
                                                }
                                            }?>
                                        </td>
                                        <td class="text-center"><span style="font-size: 15px;"
                                                class="badge bg-info"><?=format_cash($row['amount']);?></span>
                                        </td>
                                        <td class="text-center"><span style="font-size: 15px;"
                                                class="badge bg-danger"><?=format_cash($CMSNT->num_rows(" SELECT * FROM coupon_used WHERE `coupon_id` = '".$row['id']."' "));?></span>
                                        </td>
                                        <td><span style="font-size: 15px;"
                                                class="badge bg-primary"><?=$row['discount'];?>%</span></td>
                                        <td><?=$row['create_gettime'];?></td>
                                        <td class="text-center">
                                            <buton type="button"
                                                onclick="modalViewCoupon(`<?=$getUser['token'];?>`, `<?=$row['id'];?>`)"
                                                class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                title="Nhật ký sử dụng">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                            </buton>
                                            <a type="button" href="<?=base_url_admin('coupon-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                title="<?=__('Edit');?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a type="button" onclick="remove('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
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





<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> Tạo mã giảm giá mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Mã giảm giá (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="code" name="code"
                                    placeholder="Nhập mã giảm giá cần tạo" required>
                                <button class="btn btn-danger" type="button" onclick="randomCode()"><i
                                        class="fa-solid fa-shuffle"></i> Tạo mã ngẫu
                                    nhiên</button>
                            </div>

                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Số lượng mã giảm giá (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <button class="btn btn-primary shadow-primary" type="button" id="button-minus-amount"><i
                                        class="fa-solid fa-minus"></i></button>
                                <input type="number" class="form-control text-center" placeholder="" value="1"
                                    name="amount" required>
                                <button class="btn btn-primary shadow-primary" type="button" id="button-plus-amount"><i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                            <script>
                            document.getElementById('button-plus-amount').addEventListener('click', function() {
                                incrementValue();
                            });
                            document.getElementById('button-minus-amount').addEventListener('click', function() {
                                decrementValue();
                            });

                            function incrementValue() {
                                var inputElement = document.getElementsByName('amount')[0];
                                var currentValue = parseInt(inputElement.value, 10);
                                inputElement.value = currentValue + 1;
                            }

                            function decrementValue() {
                                var inputElement = document.getElementsByName('amount')[0];
                                var currentValue = parseInt(inputElement.value, 10);
                                if (currentValue > 1) {
                                    inputElement.value = currentValue - 1;
                                }
                            }
                            </script>
                            <small>Nếu bạn chọn 10, sẽ có 10 lượt sử dụng mã giảm giá cho 10 user khác nhau.</small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Chiết khấu giảm (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" name="discount" required>
                                <span class="input-group-text">
                                    <i class="fa-solid fa-percent"></i>
                                </span>
                            </div>
                            <small>Nhập 10 tức giảm 10% cho đơn hàng áp dụng mã giảm giá này.</small><br>
                            <small>Nếu User đã có chiết khấu sẽ không dùng được mã giảm giá này.</small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Sản phẩm áp dụng (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="product_id[]" id="listProduct" multiple>
                                <option value="">Mặc định sẽ áp dụng cho toàn bộ sản phẩm nếu không chọn</option>
                                <?php foreach($CMSNT->get_list(" SELECT * FROM `categories` ") as $category):?>
                                <optgroup label="__<?=$category['name'];?>__">
                                    <?php foreach($CMSNT->get_list(" SELECT * FROM `products` WHERE `category_id` = '".$category['id']."' ") as $product):?>
                                    <option value="<?=$product['id'];?>"><?=$product['name'];?></option>
                                    <?php endforeach?>
                                </optgroup>
                                <?php endforeach?>
                            </select>
                        </div>
                        <script>
                        const multipleCancelButton = new Choices(
                            '#listProduct', {
                                allowHTML: true,
                                removeItemButton: true,
                            }
                        );
                        </script>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Giá trị đơn hàng tối thiểu (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" value="100000" name="min" required>
                                <span class="input-group-text">
                                    <?=currencyDefault();?>
                                </span>
                            </div>
                            <small>Giá trị đơn hàng tối thiểu để áp dụng mã giảm giá</small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Giá trị đơn hàng tối đa (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" class="form-control" value="100000000" name="max" required>
                                <span class="input-group-text">
                                    <?=currencyDefault();?>
                                </span>
                            </div>
                            <small>Giá trị đơn hàng tối đa để áp dụng mã giảm giá</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddCoupon" class="btn btn-primary shadow-primary btn-wave"><i
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
function random(length) {
    var result = '';
    var characters = 'QWERTYUPASDFGHJKZXCVBNM123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() *
            charactersLength));
    }
    return result;
}

function randomCode() {
    document.getElementById('code').value = random(8);
}
</script>


<script>
function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeCounpon',
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
        title: "Xác nhận xóa Counpon",
        message: "Bạn có chắc chắn muốn xóa Coupon này không ?",
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


<div class="modal fade" id="ModalDialogViewCoupon" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl dialog-scrollable">
        <div class="modal-content">
            <div id="modalViewCoupon"></div>
        </div>
    </div>
</div>
<script>
function modalViewCoupon(token, id) {
    $("#modalViewCoupon").html('');
    $.get("<?=BASE_URL('ajaxs/admin/modal/coupon-view.php?id=');?>" + id + '&token=' + token, function(data) {
        $("#modalViewCoupon").html(data);
    });
    $('#ModalDialogViewCoupon').modal('show')
}
</script>