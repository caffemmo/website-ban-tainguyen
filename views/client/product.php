<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (isset($_GET['slug'])) {
    $slug = validate_slug($_GET['slug'], 255);
    if ($slug === false) {
        redirect(base_url());
    }
    if (!$product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `slug` = ? AND `status` = 1", [$slug])) {
        redirect(base_url());
    }
} else {
    redirect(base_url());
}

$body = [
    'title' => __($product['name']).' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/product-details.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
 
';
$body['footer'] = '
 

 
';

if($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
}else{
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>
<div style="margin-bottom:40px;"></div>


<section class="inner-section" style="margin-bottom:40px;">
    <div class="container">
        <div class="row">
            <?php if($CMSNT->site('product_photo_display') == 1 && $product['images'] != ''):?>
            <div class="col-lg-4" id="col1" style="margin-bottom:20px;">
                <div class="details-gallery mb-1">
                    <div class="details-label-group">
                        <?php if($product['discount'] > 0):?>
                        <label class="details-label off">Sale <?=$product['discount'];?>%</label>
                        <?php endif?>
                    </div>
                    <ul class="details-preview">
                        <?php foreach(explode(PHP_EOL, $product['images']) as $image):?>
                        <li><img src="<?=base_url(dirImageProduct($image));?>" alt="product"></li>
                        <?php endforeach?>
                    </ul>
                    <ul class="details-thumb">
                        <?php foreach(explode(PHP_EOL, $product['images']) as $image):?>
                        <li><img src="<?=base_url(dirImageProduct($image));?>" alt="product"></li>
                        <?php endforeach?>
                    </ul>
                </div>
            </div>
            <?php endif?>

            <div class="col-lg-8" id="col2">
                <div class="details-content">
                    <h3 class="details-name"><img
                            src="<?=base_url(getRowRealtime('categories', $product['category_id'],'icon'));?>"
                            width="25px"> <?=__($product['name']);?></h3>
                    <div class="details-meta">
                        <?php $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);?>
                        <p><label class="label-text feat"><?=__('Kho hàng:');?>
                                <strong><?=format_cash($stock);?></strong></label>
                            <?php if($CMSNT->site('product_sold_display') == 1):?>
                            <label class="label-text order"><?=__('Đã bán:');?> <strong>
                                    <?=format_cash($product['sold']);?></strong></label>
                            <?php endif?>
                        </p>
                    </div>
                    <?php if($CMSNT->site('product_rating_display') == 1):?>
                    <div class="details-rating"><i class="active icofont-star"></i><i class="active icofont-star"></i><i
                            class="active icofont-star"></i><i class="active icofont-star"></i><i
                            class="icofont-star"></i><a href="#">(3 reviews)</a>
                    </div>
                    <?php endif?>
                    <h3 class="details-price">
                        <?=$product['discount'] > 0 ? '<del>'.format_currency($product['price']).'</del>' : '';?><span><?=format_currency($product['price']-$product['price']*$product['discount']/100);?></span>
                    </h3>
                    <p class="details-desc"><?=str_replace(PHP_EOL, '<br>', $product['short_desc']);?></p>
                    <div class="details-list-group"><label class="details-list-title">Share:</label>
                        <ul class="details-share-list">
                            <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?=base_url('product/'.$product['slug']);?>"
                                    title="Facebook"><i class="fa-brands fa-facebook"></i></a></li>
                            <li><a href="https://twitter.com/intent/tweet?url=<?=base_url('product/'.$product['slug']);?>"
                                    title="Twitter"><i class="fa-brands fa-square-x-twitter"></i></a></li>
                            <li><a href="https://www.linkedin.com/sharing/share-offsite/?url=<?=base_url('product/'.$product['slug']);?>"
                                    title="Linkedin"><i class="fa-brands fa-linkedin"></i></a></li>
                            <li><a href="https://www.instagram.com/?url=<?=base_url('product/'.$product['slug']);?>"
                                    title="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <input type="hidden" id="product_id" value="<?=$product['id'];?>">
                            <input type="hidden" id="token" value="<?=isset($getUser) ? $getUser['token'] : ''?>">
                            <button id="openModal_<?=$product['id'];?>"
                                onclick="openModal(`<?=isset($getUser) ? $getUser['token'] : NULL;?>`, `<?=$product['id'];?>`, `<?=$product['preview_uid'] ?? 0;?>`)"
                                class="btn-buy mb-2"><i class="fa-solid fa-cart-shopping"></i>
                                <?=mb_strtoupper(__('MUA NGAY'));?></button>
                        </div>
                        <div class="col-lg-6">
                            <a class="btn-more" href="<?=base_url();?>" type="button">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span><?=mb_strtoupper(__('Quay lại'));?></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="inner-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <ul class="nav nav-tabs">
                    <li><a href="#tab-desc" class="tab-link active" data-bs-toggle="tab"><?=__('Chi tiết');?></a></li>
                    <?php if($CMSNT->site('api_status') == 1):?>
                    <li><a href="#tab-api" class="tab-link" data-bs-toggle="tab"><?=__('Tích hợp API');?></a></li>
                    <?php endif?>
                    <?php if($CMSNT->site('affiliate_status') == 1):?>
                    <li><a href="#tab-aff" class="tab-link" data-bs-toggle="tab"><?=__('Tiếp thị liên kết');?></a></li>
                    <?php endif?>
                </ul>
            </div>
        </div>
        <div class="tab-pane fade show active" id="tab-desc">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-details-frame">
                        <?=base64_decode($product['description']);?>
                    </div>
                </div>
            </div>
        </div>
        <?php if($CMSNT->site('api_status') == 1):?>
        <div class="tab-pane fade" id="tab-api">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-details-frame">
                        <?php if(isset($getUser)):?>
                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('API Key:');?></label>
                            <div class="col-lg-8 fv-row">
                                <strong class="copy" style="color: blue;" id="input_api_key"
                                    data-clipboard-target="#input_api_key" onclick="copy()" data-toggle="tooltip"
                                    data-placement="bottom" title="Nhấn vào để Copy"><?=$getUser['api_key'];?></strong>
                                <button onclick="changeAPIKey(`<?=$getUser['token'];?>`)" data-toggle="tooltip"
                                    data-placement="bottom"
                                    title="Thay đổi API KEY khác nếu API KEY cũ của bạn bị lộ ra ngoài"
                                    class="btn btn-danger btn-sm"><i class="fa-solid fa-rotate"></i></button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label
                                class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Lấy chi tiết sản phẩm');?></label>
                            <div class="col-lg-8 fv-row">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">GET</span>
                                    <input type="text" class="form-control" id="api_product"
                                        value="<?=base_url('api/product.php?api_key='.$getUser['api_key'].'&product='.$product['id']);?>">
                                    <button class="btn btn-primary btn-sm copy" data-clipboard-target="#api_product"
                                        onclick="copy()"><i class="fa-solid fa-copy"></i> Copy</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Mua hàng');?></label>
                            <div class="col-lg-8 fv-row">
                                <div class="input-group mb-3">
                                    <span class="input-group-text">POST</span>
                                    <input type="text" class="form-control" id="api_buy"
                                        value="<?=base_url('api/buy_product');?>">
                                    <button class="btn btn-primary btn-sm copy" data-clipboard-target="#api_buy"
                                        onclick="copy()"><i class="fa-solid fa-copy"></i> Copy</button>
                                </div>
                                <p>form-data:</p>
                                <ul class="mb-1">
                                    <li><strong>action</strong>: buyProduct</li>
                                    <li><strong>id</strong>: <?=$product['id'];?></li>
                                    <li><strong>amount</strong>: <?=__('Số lượng cần mua');?></li>
                                    <li><strong>coupon</strong>: <?=__('Mã giảm giá nếu có');?></li>
                                    <li><strong>api_key</strong>: <span
                                            style="color:blue;"><?=$getUser['api_key'];?></span></li>
                                </ul>
                                <p>Response:</p>
                                <textarea class="form-control">
{
    "status": "success",
    "msg": "Tạo đơn hàng thành công!",
    "trans_id": "JF465f728224ce11",
    "data": [
        "1000040304952|GUTJXYIFPWLHCNDOMBRKVAQESZ",
        "1000087676467|IVMRLABECWTQYUXHPOFNJDSZGK",
        "1000073612513|ERKPFTVCAJDLINWMXSUOGBQZHY",
        "1000011975745|JXEZTVLYOFBQNRHGDKMIPUCAWS"
    ]
}
                            </textarea>
                            </div>
                        </div>
                        <script type="text/javascript">
                        new ClipboardJS(".copy");

                        function copy() {
                            showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
                        }
                        </script>
                        <script>
                        function changeAPIKey(token) {
                            Swal.fire({
                                title: "<?=__('Bạn có chắc không?');?>",
                                text: "<?=__('Hệ thống sẽ thay đổi API KEY nếu bạn Đồng Ý');?>",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "<?=__('Tôi đồng ý');?>",
                                cancelButtonText: "<?=__('Đóng');?>"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: "<?=BASE_URL("ajaxs/client/auth.php");?>",
                                        method: "POST",
                                        dataType: "JSON",
                                        data: {
                                            token: token,
                                            action: 'changeAPIKey'
                                        },
                                        success: function(respone) {
                                            if (respone.status == 'success') {
                                                showMessage(result.msg, result.status);
                                                location.reload();
                                            } else {
                                                Swal.fire({
                                                    title: "<?=__('Thất bại!');?>",
                                                    text: respone.msg,
                                                    icon: "error"
                                                });
                                            }
                                        },
                                        error: function() {
                                            alert(html(response));
                                            location.reload();
                                        }
                                    });
                                }
                            });
                        }
                        </script>
                        <?php else:?>
                        <p><?=__('Vui lòng đăng nhập để sử dụng chức năng này');?></p>

                        <?php endif?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif?>
        <?php if($CMSNT->site('affiliate_status') == 1):?>
        <div class="tab-pane fade" id="tab-aff">
            <?php if(isset($getUser)):?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-details-frame">
                        <p class="mb-3"><?=__('Chia sẻ liên kết sản phẩm dưới đây cho bạn bè của bạn, bạn sẽ nhận được hoa hồng khi bạn bè của bạn mua hàng thông qua liên kết phía dưới.');?></p>
                        <div class="form-group row">
                            <label
                                class="col-lg-3 col-form-label required fw-bold fs-6"><?=__('Liên kết sản phẩm');?></label>
                            <div class="col-lg-9 fv-row">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="lien_ket_gioi_thieu"
                                        value="<?=base_url('product/'.$product['slug'].'&aff='.$getUser['id']);?>">
                                    <button class="btn btn-primary btn-sm copy"
                                        data-clipboard-target="#lien_ket_gioi_thieu" onclick="copy()"><i
                                            class="fa-solid fa-copy"></i> Copy</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label required fw-bold fs-6"><?=__('Tỷ lệ hoa hồng');?></label>
                            <div class="col-lg-9 fv-row">
                                <strong  style="color: blue;"><?php
                              $ck = $CMSNT->site('affiliate_ck');
                              if(getRowRealtime('users', $getUser['id'], 'ref_ck') != 0){
                                  $ck = getRowRealtime('users', $getUser['id'], 'ref_ck');
                              }
                              ?><?=$ck;?>%</strong>
                                 
                            </div>
                        </div>
                        <center><a type="button" href="<?=base_url('?action=affiliates');?>"
                                class="btn-more-new mb-3"><?=__('Xem thêm');?></a></center>
                    </div>
                </div>
            </div>
            <?php else:?>
            <p><?=__('Vui lòng đăng nhập để sử dụng chức năng này');?></p>
            <?php endif?>
        </div>
        <?php endif?>




    </div>
</section>



<div class="modal fade" id="openModal" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content">
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
function openModal(token, id, previewUid) {
    $("#modalContent").html('');
    var originalButtonContent = $('#openModal_' + id).html();
    $('#openModal_' + id).html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    // Điều chỉnh kích thước modal
    var modalDialog = $('#openModal .modal-dialog');
    if (previewUid == 1) {
        modalDialog.removeClass('modal-lg').addClass('modal-xl');
    } else {
        modalDialog.removeClass('modal-xl').addClass('modal-lg');
    }
    var modalUrl = previewUid == 1 
        ? "<?=BASE_URL('ajaxs/client/modal/view-product-preview.php');?>" 
        : "<?=BASE_URL('ajaxs/client/modal/view-product.php');?>";
    $.ajax({
        url: modalUrl,
        method: "GET",
        data: {
            id: id,
            token: token
        },
        success: function(data) {
            $("#modalContent").html(data);
            $('#openModal').modal('show');
            $('#openModal_' + id).html(originalButtonContent).prop('disabled', false);
        },
        error: function() {
            Swal.fire('<?=__('Thất bại!');?>', data, 'error');
        }
    });
}
</script>
<script>
function addFavorite2() {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/client/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'toggleFavorite',
            id: $("#product_id").val(),
            token: $("#token").val()
        },
        success: function(data) {
            if (data.status == 'success') {
                if (data.button == true) {
                    $("#btnAddFavorite2").hide();
                    $("#btnRemoveFavorite2").show();
                    // Lấy thẻ sup theo id
                    var numFavoritesElement = document.getElementById("numFavorites");
                    // Giá trị hiện tại trong thẻ sup
                    var currentValue = parseInt(numFavoritesElement.textContent);
                    // Giả sử bạn muốn cộng thêm 1 vào giá trị hiện tại
                    var newValue = currentValue + 1;
                    // Cập nhật giá trị trong thẻ sup
                    numFavoritesElement.textContent = newValue;
                } else {
                    $("#btnAddFavorite2").show();
                    $("#btnRemoveFavorite2").hide();
                    // Lấy thẻ sup theo id
                    var numFavoritesElement = document.getElementById("numFavorites");
                    // Giá trị hiện tại trong thẻ sup
                    var currentValue = parseInt(numFavoritesElement.textContent);
                    // Giả sử bạn muốn cộng thêm 1 vào giá trị hiện tại
                    var newValue = currentValue - 1;
                    // Cập nhật giá trị trong thẻ sup
                    numFavoritesElement.textContent = newValue;
                }
            }
            showMessage(data.msg, data.status);
        },
        error: function() {
            showMessage('<?=__('Vui lòng liên hệ Developer');?>', 'error');
        }
    });
}
</script>

<?php
require_once(__DIR__.'/footer.php');
?>