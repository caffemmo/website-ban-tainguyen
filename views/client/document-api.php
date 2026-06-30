<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}


$body = [
    'title' => __('Tài liệu tích hợp API').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
 
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('api_status') != 1){
    redirect(base_url('client/home'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-regular fa-file-code m-2"></i>
                        <?=mb_strtoupper(__('Tài liệu tích hợp API'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('API Key:');?></label>
                        <div class="col-lg-8 fv-row d-flex align-items-center">
                            <strong class="copy me-2" style="color: blue; cursor: pointer;" id="api_key"
                                onclick="copy()" data-toggle="tooltip" data-placement="bottom"
                                title="Nhấn vào để Copy"></strong>
                            <button type="button" class="btn btn-sm btn-info me-2" id="toggleApiKey" data-toggle="tooltip" 
                                data-placement="bottom" title="Hiển thị/Ẩn API Key">
                                <i class="fa-solid fa-eye-slash" id="apiKeyIcon"></i>
                            </button>
                            <button onclick="changeAPIKey(`<?=$getUser['token'];?>`)" data-toggle="tooltip"
                                data-placement="bottom" title="Thay đổi API KEY khác nếu API KEY cũ của bạn bị lộ ra ngoài"
                                class="btn btn-sm btn-danger"><i class="fa-solid fa-rotate"></i></button>
                            <input type="hidden" id="actual_api_key" value="<?=$getUser['api_key'];?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Danh sách IP Whitelist:');?></label>
                        <div class="col-lg-8 fv-row">
                            <textarea class="form-control" id="ip_whitelist" placeholder="Nhập danh sách IP được phép truy cập API (mỗi dòng 1 IP)" rows="3"><?=htmlspecialchars($getUser['ip_whitelist_api'] ?? '');?></textarea>
                            <div class="mt-2">
                                <button type="button" class="btn btn-primary btn-sm" onclick="saveIPWhitelist('<?=$getUser['token'];?>')">
                                    <i class="fa-solid fa-save"></i> <?=__('Lưu danh sách IP');?>
                                </button>
                                <small class="text-muted ms-3">
                                    <i class="fa-solid fa-info-circle"></i> <?=__('Để trống để cho phép tất cả IP truy cập API');?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label
                            class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Lấy thông tin tài khoản');?></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-3">
                                <span class="input-group-text">GET</span>
                                <input type="text" class="form-control" id="api_profile" value="<?=base_url('api/profile.php?api_key='.$getUser['api_key']);?>">
                                <button class="btn btn-primary btn-sm" onclick="copyText('api_profile')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label
                            class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Lấy danh sách chuyên mục và sản phẩm');?></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-3">
                                <span class="input-group-text">GET</span>
                                <input type="text" class="form-control" id="api_products" value="<?=base_url('api/products.php?api_key='.$getUser['api_key']);?>">
                                <button class="btn btn-primary btn-sm" onclick="copyText('api_products')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label
                            class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Lấy chi tiết sản phẩm');?></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-3">
                                <span class="input-group-text">GET</span>
                                <input type="text" class="form-control" id="api_product" value="<?=base_url('api/product.php?api_key='.$getUser['api_key'].'&product=3');?>">
                                <button class="btn btn-primary btn-sm" onclick="copyText('api_product')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label
                            class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Lấy chi tiết đơn hàng');?></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-3">
                                <span class="input-group-text">GET</span>
                                <input type="text" class="form-control" id="api_order" value="<?=base_url('api/order.php?api_key='.$getUser['api_key'].'&order=7NVD67d9a4bf5406b');?>">
                                <button class="btn btn-primary btn-sm" onclick="copyText('api_order')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label
                            class="col-lg-4 col-form-label required fw-bold fs-6"><?=__('Mua hàng');?></label>
                        <div class="col-lg-8 fv-row">
                            <div class="input-group mb-3">
                                <span class="input-group-text">POST or GET</span>
                                <input type="text" class="form-control" id="api_buy" value="<?=base_url('api/buy_product');?>">
                                <button class="btn btn-primary btn-sm copy" data-clipboard-target="#api_buy" onclick="copy()"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <p>form-data:</p>
                            <ul class="mb-1">
                                <li><strong>action</strong>: buyProduct</li>
                                <li><strong>id</strong>: <?=__('ID sản phẩm cần mua');?></li>
                                <li><strong>amount</strong>: <?=__('Số lượng cần mua');?></li>
                                <li><strong>coupon</strong>: <?=__('Mã giảm giá nếu có');?></li>
                                <li><strong>api_key</strong>: <span style="color:blue;" id="api_key_form"></span></li>
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
                </div>
            </div>
        </div>
    </div>
</section>



<?php
require_once(__DIR__.'/footer.php');
?>
 


<script type="text/javascript">
function copy() {
    const actualApiKey = $("#actual_api_key").val();
    // Tạo một phần tử tạm thời để sao chép nội dung
    const tempElement = document.createElement("textarea");
    tempElement.value = actualApiKey;
    document.body.appendChild(tempElement);
    tempElement.select();
    document.execCommand('copy');
    document.body.removeChild(tempElement);
    
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}

function copyText(elementId) {
    const text = $('#' + elementId).val();
    const tempElement = document.createElement("textarea");
    tempElement.value = text;
    document.body.appendChild(tempElement);
    tempElement.select();
    document.execCommand('copy');
    document.body.removeChild(tempElement);
    
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

// Xử lý ẩn/hiện API Key
$(document).ready(function() {
    let apiKeyVisible = false;
    const actualApiKey = $("#actual_api_key").val();
    // Hiển thị một phần của API key
    const maskedKey = maskApiKey(actualApiKey);
    $("#api_key").text(maskedKey);
    $("#api_key_form").text(maskedKey);
    
    $("#toggleApiKey").on("click", function() {
        if (apiKeyVisible) {
            // Ẩn API Key (chỉ hiện một phần)
            $("#api_key").text(maskedKey);
            $("#api_key_form").text(maskedKey);
            $("#apiKeyIcon").removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            // Hiện toàn bộ API Key
            $("#api_key").text(actualApiKey);
            $("#api_key_form").text(actualApiKey);
            $("#apiKeyIcon").removeClass("fa-eye-slash").addClass("fa-eye");
        }
        apiKeyVisible = !apiKeyVisible;
    });
    
    // Hàm để hiển thị một phần của API key
    function maskApiKey(key) {
        if (key.length <= 8) {
            return key;
        }
        // Hiển thị 4 ký tự đầu và 4 ký tự cuối
        const hiddenLength = key.length - 8; // Số ký tự bị ẩn
        const stars = "*".repeat(hiddenLength); // Tạo chuỗi * với độ dài chính xác
        return key.substring(0, 4) + stars + key.substring(key.length - 4);
    }
});

// Function để lưu IP Whitelist
function saveIPWhitelist(token) {
    const ipList = $("#ip_whitelist").val().trim();
    
    // Validate IP format
    if (ipList !== '') {
        const ips = ipList.split('\n').filter(ip => ip.trim() !== '');
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        
        for (let i = 0; i < ips.length; i++) {
            const ip = ips[i].trim();
            if (!ipRegex.test(ip)) {
                Swal.fire({
                    title: "<?=__('Lỗi định dạng IP!');?>",
                    text: "<?=__('IP không hợp lệ:');?> " + ip + ". <?=__('Vui lòng kiểm tra lại định dạng IP.');?>",
                    icon: "error"
                });
                return;
            }
        }
    }
    
    Swal.fire({
        title: "<?=__('Xác nhận lưu danh sách IP');?>",
        text: "<?=__('Bạn có chắc chắn muốn cập nhật danh sách IP Whitelist không?');?>",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Hủy');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/client/update.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    token: token,
                    action: 'updateIPWhitelist',
                    ip_whitelist: ipList
                },
                beforeSend: function() {
                    $("button").prop("disabled", true);
                },
                success: function(response) {
                    $("button").prop("disabled", false);
                    if (response.status == 'success') {
                        Swal.fire({
                            title: "<?=__('Thành công!');?>",
                            text: response.msg,
                            icon: "success"
                        });
                    } else {
                        Swal.fire({
                            title: "<?=__('Thất bại!');?>",
                            text: response.msg,
                            icon: "error"
                        });
                    }
                },
                error: function() {
                    $("button").prop("disabled", false);
                    Swal.fire({
                        title: "<?=__('Lỗi!');?>",
                        text: "<?=__('Có lỗi xảy ra, vui lòng thử lại sau.');?>",
                        icon: "error"
                    });
                }
            });
        }
    });
}
</script>