<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Tmweasyapi Thailand').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_user.php');
if($CMSNT->site('tmweasyapi_status') != 1){
    redirect(base_url());
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;
$where_conditions = ["`user_id` = ?", "`status` = ?"];
$where_params = [$getUser['id'], 1];
$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';

if(!empty($_GET['trans_id'])){
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if($trans_id !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $trans_id;
    }
}
if(!empty($_GET['amount'])){
    $amount = validate_float($_GET['amount'], 0, 999999999);
    if($amount !== false) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}

if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_gettime_1 = str_replace('-', '/', $time);
        $create_gettime_1 = explode(' to ', $create_gettime_1);
        if($create_gettime_1[0] != $create_gettime_1[1]){
            $date_start = validate_date($create_gettime_1[0].' 00:00:00');
            $date_end = validate_date($create_gettime_1[1].' 23:59:59');
            if($date_start !== false && $date_end !== false) {
                $where_conditions[] = "`created_at` >= ? AND `created_at` <= ?";
                $where_params[] = $date_start;
                $where_params[] = $date_end;
            }
        }
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");
        if($shortByDate == 1){
            $where_conditions[] = "`created_at` LIKE ?";
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = "YEAR(created_at) = ? AND WEEK(created_at, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = "MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_tmweasyapi` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_tmweasyapi` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);

$urlDatatable = pagination(base_url("?action=recharge-tmweasyapi&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&"), $from, $totalDatatable, $limit);

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Tmweasyapi Thailand');?></h4>
                    <div class="text-center mb-4">
                        <img width="300px" src="<?=base_url('mod/img/logo-tmweasyapi.webp');?>" />
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Enter the deposit amount: (฿)');?></label>
                        <div class="col-sm-8">
                            <input type="hidden" class="form-control" id="token" value="<?=$getUser['token'];?>">
                            <input type="text" class="form-control" id="amount"
                                placeholder="<?=__('Please enter the amount to deposit');?>" required>
                        </div>
                    </div>
                    <center>
                        <div class="wallet-form">
                            <button type="button" id="btnSubmit"><?=__('Submit');?></button>
                        </div>
                    </center>
                </div>
            </div>
            <div class="col-md-5">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lưu ý');?></h4>
                    <?=$CMSNT->site('tmweasyapi_notice');?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="account-card">
                    <h4 class="account-title"><?=__('Lịch sử nạp Tmweasyapi Thailand');?></h4>
                    <form action="<?=base_url();?>" method="GET">
                        <input type="hidden" name="action" value="recharge-tmweasyapi">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$trans_id;?>" name="trans_id"
                                    placeholder="<?=__('Search transaction id');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control col-sm-2 mb-1" value="<?=$amount;?>" name="amount"
                                    placeholder="<?=__('Search amount');?>">
                            </div>
                            <div class="col-lg col-md-6 col-6">
                                <input type="text" class="js-flatpickr form-control mb-1" id="example-flatpickr-range"
                                    name="time" placeholder="<?=__('Chọn thời gian cần tìm');?>" value="<?=$time;?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?=__('Tìm kiếm');?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?=base_url('?action=recharge-tmweasyapi');?>" class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?=__('Bỏ lọc');?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show"><label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
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
                                    <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll">
                        <table class="table fs-sm mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center"><?=__('TransID');?></th>
                                    <th class="text-center"><?=__('Amount');?></th>
                                    <th class="text-center"><?=__('Price');?></th>
                                    <th class="text-center"><?=__('Status');?></th>
                                    <th class="text-center"><?=__('Create date');?></th>
                                    <th class="text-center"><?=__('Action');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $row) {?>
                                <tr>
                                    <td class="text-center"><b><?=$row['trans_id'];?></b></td>
                                    <td class="text-center"><b><?=$row['amount'];?></b></td>
                                    <td class="text-center"><b
                                            style="color: red;"><?=format_currency($row['price']);?></b></td>
                                    <td class="text-center"><?=display_invoice($row['status']);?></td>
                                    <td class="text-center"><i class="far fa-calendar-alt mr-2 text-secondary"></i>
                                        <?=$row['created_at'];?></td>
                                    <td class="text-center">
                                        <a class="btn btn-primary btn-sm" target="_blank"
                                            href="<?=$row['checkout_url'];?>">
                                            <i class="fas fa-credit-card mr-2"></i>
                                            <?=__('Pay Now');?>
                                        </a>
                                    </td>
                                </tr>
                                <?php }?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <div class="float-right">
                                            <?=__('Paid:');?>
                                            <strong
                                                style="color:red;"><?=format_currency($CMSNT->get_row_safe("SELECT SUM(`price`) FROM `payment_tmweasyapi` WHERE $where_clause", $where_params)['SUM(`price`)']);?></strong>

                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bottom-paginate">
                        <p class="page-info">Showing <?=$limit;?> of <?=$totalDatatable;?> Results</p>
                        <div class="pagination">
                            <?=$totalDatatable > $limit ? $urlDatatable : '';?>
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


<!-- Modal Payment Info -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" >
                <h5 class="modal-title" id="paymentModalLabel" style="color:white;">
                    <i class="fas fa-qrcode me-2"></i><?=__('Top up via PromptPay (QR CODE)');?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="payment-status mb-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <h4 class="text-primary mb-2"><?=__('Waiting for Payment');?></h4>
                    <p class="text-muted"><?=__('Please scan PromptPay QR code to complete payment');?></p>
                </div>
                
                <div class="qr-container mb-4">
                    <div class="qr-wrapper p-3 bg-light rounded">
                        <img id="qrImage" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyQR()">
                            <i class="fas fa-copy"></i> <?=__('Copy QR Code');?>
                        </button>
                    </div>
                </div>

                <div class="payment-details bg-light p-3 rounded mb-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="payment-info-item">
                                <small class="text-muted d-block"><?=__('Amount');?></small>
                                <h4 class="text-danger mb-0" id="paymentAmount"></h4>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="payment-info-item">
                                <small class="text-muted d-block"><?=__('Time Remaining');?></small>
                                <h4 class="text-warning mb-0" id="timeRemaining"></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-actions">
                    <button type="button" class="btn btn-danger btn-block" id="btnCancelPayment" data-dismiss="modal">
                        <i class="fas fa-times"></i> <?=__('Cancel Payment');?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.modal-header {
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem;
}

.qr-wrapper {
    border: 2px dashed #dee2e6;
    transition: all 0.3s ease;
}

.qr-wrapper:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.payment-info-item {
    padding: 0.5rem;
}

.payment-info-item small {
    font-size: 0.8rem;
}

.payment-info-item h4 {
    font-size: 1.2rem;
    font-weight: 600;
}

.btn-lg {
    padding: 0.8rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>

<script type="text/javascript">
 
// Xử lý nút Cancel Payment
$('#btnCancelPayment').on('click', function() {
    $('#paymentModal').modal('hide');
});

function copyQR() {
    const qrImage = document.getElementById('qrImage');
    const src = qrImage.getAttribute('src');
    const img = new Image();
    img.onload = function () {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        // Sử dụng kích thước gốc của ảnh để tránh bị cắt
        const w = img.naturalWidth || img.width;
        const h = img.naturalHeight || img.height;
        canvas.width = w;
        canvas.height = h;
        context.imageSmoothingEnabled = false;
        context.drawImage(img, 0, 0, w, h);

        // Tương thích Safari: chuyển dataURL -> blob nếu toBlob không hỗ trợ
        if (canvas.toBlob) {
            canvas.toBlob(function (blob) {
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function () {
                    Swal.fire({
                        icon: 'success',
                        title: '<?=__('Copied!');?>',
                        text: '<?=__('QR code has been copied to clipboard');?>',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            });
        } else {
            const dataUrl = canvas.toDataURL('image/png');
            fetch(dataUrl).then(res => res.blob()).then(blob => {
                const item = new ClipboardItem({ 'image/png': blob });
                return navigator.clipboard.write([item]);
            }).then(function(){
                Swal.fire({
                    icon: 'success',
                    title: '<?=__('Copied!');?>',
                    text: '<?=__('QR code has been copied to clipboard');?>',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
    };
    img.src = src;
}

// Tạo ảnh QR có đóng watermark để hạn chế việc sử dụng sai mục đích
function generateWatermarkedQR(base64Img, watermarkText, color, opacity, fontRatio) {
    return new Promise(function(resolve) {
        var img = new Image();
        img.onload = function() {
            var canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            var ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = false;

            // Vẽ QR gốc
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            // Vẽ watermark lặp chéo, mờ để vẫn quét được (tỉ lệ giống mẫu)
            var baseSize = Math.min(canvas.width, canvas.height);
            var ratio = (typeof fontRatio === 'number' && !isNaN(fontRatio) && fontRatio > 0) ? fontRatio : 0.08;
            var fontSize = Math.max(16, Math.floor(baseSize * ratio));
            ctx.save();
            ctx.globalAlpha = (typeof opacity === 'number' && opacity >= 0 && opacity <= 1) ? opacity : 0.28; // độ mờ watermark
            ctx.fillStyle = color || '#ff0000';
            ctx.strokeStyle = color || '#ff0000';
            ctx.font = fontSize + 'px Arial, Helvetica, sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.translate(canvas.width / 2, canvas.height / 2);
            ctx.rotate(-Math.PI / 4);

            var metrics = ctx.measureText(watermarkText);
            // Khoảng cách đều tay hơn, gần giống mẫu
            var step = Math.floor(baseSize * 0.18);
            var stepX = Math.max(step, metrics.width + fontSize);
            var stepY = Math.max(step, Math.floor(fontSize * 2.2));
            var strokeWidth = Math.max(1, Math.floor(fontSize * 0.06));
            ctx.lineWidth = strokeWidth;

            for (var y = -canvas.height; y <= canvas.height; y += stepY) {
                for (var x = -canvas.width; x <= canvas.width; x += stepX) {
                    ctx.fillText(watermarkText, x, y);
                    ctx.strokeText(watermarkText, x, y);
                }
            }
            ctx.restore();

            resolve(canvas.toDataURL('image/png'));
        };
        img.src = 'data:image/png;base64,' + base64Img;
    });
}

$("#btnSubmit").on("click", function() {
    $('#btnSubmit').html('<?=__("Please wait...");?>').prop('disabled', true);
    $.ajax({
        url: "<?=BASE_URL('ajaxs/client/create.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'RechargeTmweasyapi',
            token: $("#token").val(),
            amount: $("#amount").val()
        },
        success: function(response) {
            if (response.status == 'success') {
                // Hiển thị thông tin trong modal với watermark cấu hình từ admin
                var WM_TEXT = '<?=$CMSNT->site('tmweasyapi_watermark_text');?>';
                var WM_COLOR = '<?=($CMSNT->site('tmweasyapi_watermark_color') != '' ? $CMSNT->site('tmweasyapi_watermark_color') : '#ff0000');?>';
                var WM_OPACITY = parseFloat('<?=($CMSNT->site('tmweasyapi_watermark_opacity') != '' ? $CMSNT->site('tmweasyapi_watermark_opacity') : 0.28);?>');
                var WM_FONT_RATIO = parseFloat('<?=($CMSNT->site('tmweasyapi_watermark_font_size') != '' ? $CMSNT->site('tmweasyapi_watermark_font_size') : 0.08);?>');
                if (WM_TEXT && WM_TEXT.trim() !== '') {
                    generateWatermarkedQR(response.qr, WM_TEXT, WM_COLOR, WM_OPACITY, WM_FONT_RATIO).then(function(dataUrl){
                        $('#qrImage').attr('src', dataUrl);
                    });
                } else {
                    $('#qrImage').attr('src', 'data:image/png;base64,' + response.qr);
                }
                $('#paymentAmount').text(response.amount + ' THB');
                $('#paymentUrl').attr('href', response.invoice_url);
                
                // Xử lý đếm ngược thời gian
                let timeLeft = parseInt(response.time_out);
                const timerDisplay = $('#timeRemaining');
                
                function updateTimer() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerDisplay.text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        timerDisplay.text('<?=__("Time expired");?>');
                        timerDisplay.addClass('text-danger');
                        $('#paymentModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: '<?=__("Time expired");?>',
                            text: '<?=__("Payment time has expired. Please create a new payment.");?>',
                            confirmButtonText: '<?=__("OK");?>'
                        });
                    }
                    timeLeft--;
                }
                
                // Cập nhật ngay lập tức
                updateTimer();
                
                // Cập nhật mỗi giây
                const timer = setInterval(updateTimer, 1000);
                
                // Hiển thị modal
                $('#paymentModal').modal('show');
                
                // Xử lý khi modal đóng
                $('#paymentModal').on('hidden.bs.modal', function () {
                    clearInterval(timer);
                });
            } else {
                Swal.fire(
                    '<?=__('Error');?>',
                    response.msg,
                    'error'
                );
            }
            $('#btnSubmit').html('<?=__('Submit');?>').prop('disabled', false);
        }
    })
});
</script>


<script>
function loadData() {
    $.ajax({
        url: "<?=base_url('ajaxs/client/view.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'notication_topup_tmweasyapi',
            token: '<?=$getUser['token'];?>'
        },
        success: function(respone) {
            // Nếu thành công
            if (respone.status == 'success') {
                // Tắt modal thanh toán
                $('#paymentModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: '<?=__('Thành công !');?>',
                    text: respone.msg,
                    showDenyButton: true,
                    confirmButtonText: '<?=__('Nạp Thêm');?>',
                    denyButtonText: `<?=__('Mua Ngay');?>`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Người dùng bấm "Nạp Thêm" => reload trang
                        location.reload();
                    } else if (result.isDenied) {
                        // Người dùng bấm "Mua Ngay" => chuyển hướng
                        window.location.href = '<?=base_url();?>';
                    } else {
                        // Nếu họ đóng Swal mà không chọn gì (hoặc 'dismiss'),
                        // thì 5 giây sau gọi lại loadData.
                        setTimeout(loadData, 5000);
                    }
                });
            } else {
                // Nếu status != 'success' => không hiển thị Swal
                // hoặc bạn có thể hiện thông báo lỗi
                // Sau đó 5 giây mới load lại
                setTimeout(loadData, 5000);
            }
        },
        error: function() {
            // Nếu Ajax lỗi => 5 giây sau gọi lại loadData
            setTimeout(loadData, 5000);
        }
    });
}

// Lần đầu gọi hàm
loadData();
</script>



<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>