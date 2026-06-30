<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Chỉnh sửa mã giảm giá',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="'.BASE_URL('public/AdminLTE3/').'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `coupons` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url('admin/coupons'));
    }
} else {
    redirect(base_url('admin/coupons'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_coupon') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveCoupon'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
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
    $product_id = json_encode($_POST['product_id']);
    if(empty($_POST['product_id'])){
        $product_id = NULL;
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
    
    $isUpdate = $CMSNT->update("coupons", [
        'product_id'    => $product_id,
        'amount'        => $amount,
        'discount'      => $discount,
        'update_gettime'    => gettime(),
        'min'           => $min,
        'max'           => $max
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Coupon (".$row['code'].")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Coupon (".$row['code'].").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công !")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tags"></i> Chỉnh sửa mã giảm giá '<b
                    style="color:red;"><?=$row['code'];?></b>'</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('coupons');?>">Coupons</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Coupon</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA MÃ GIẢM GIÁ
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Số lượng mã giảm giá
                                    (<span class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <button class="btn btn-primary shadow-primary" type="button"
                                            id="button-minus-amount"><i class="fa-solid fa-minus"></i></button>
                                        <input type="number" class="form-control text-center" placeholder=""
                                            value="<?=$row['amount'];?>" name="amount" required>
                                        <button class="btn btn-primary shadow-primary" type="button"
                                            id="button-plus-amount"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                    <script>
                                    document.getElementById('button-plus-amount').addEventListener('click', function() {
                                        incrementValue();
                                    });
                                    document.getElementById('button-minus-amount').addEventListener('click',
                                        function() {
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
                                    <small>Nếu bạn chọn 10, sẽ có 10 lượt sử dụng mã giảm giá cho 10 user khác
                                        nhau.</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Chiết khấu giảm (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="discount"
                                            value="<?=$row['discount'];?>" required>
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
                                        <option value="">Mặc định sẽ áp dụng cho toàn bộ sản phẩm nếu không chọn
                                        </option>
                                        <?php foreach($CMSNT->get_list(" SELECT * FROM `categories` ") as $category):?>
                                        <optgroup label="__<?=$category['name'];?>__">
                                            <?php foreach($CMSNT->get_list(" SELECT * FROM `products` WHERE `category_id` = '".$category['id']."' ") as $product):?>
                                            <option
                                                <?= in_array($product['id'], json_decode($row['product_id'] ?? '[]', true) ?? [], true) ? 'selected' : ''; ?>
                                                value="<?= $product['id']; ?>"><?= $product['name']; ?></option>

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
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Giá trị đơn hàng tối thiểu
                                    (<span class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?=$row['min'];?>" name="min"
                                            required>
                                        <span class="input-group-text">
                                            <?=currencyDefault();?>
                                        </span>
                                    </div>
                                    <small>Giá trị đơn hàng tối thiểu để áp dụng mã giảm giá</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Giá trị đơn hàng tối đa
                                    (<span class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?=$row['max'];?>" name="max"
                                            required>
                                        <span class="input-group-text">
                                            <?=currencyDefault();?>
                                        </span>
                                    </div>
                                    <small>Giá trị đơn hàng tối đa để áp dụng mã giảm giá</small>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?=base_url_admin('coupons');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="SaveCoupon" class="btn btn-primary shadow-primary btn-wave"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Save');?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<?php
require_once(__DIR__.'/footer.php');
?>