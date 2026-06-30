<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Xác minh 2FA').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
' . renderCaptchaScripts('verify_2fa') . '
';
$body['footer'] = '

';

if (isset($_GET['token'])) {
    $token = validate_alphanumeric($_GET['token'], 255);
    if($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_2fa` = ? AND `token_2fa` IS NOT NULL", [$token])) {
        redirect(base_url('client/login'));
    }
    if(empty($getUser['token_2fa'])){
        redirect(base_url('client/login'));
    }
} else {
    redirect(base_url('client/login'));
}


require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


  
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">

                <div class="user-form-card">
                    <div class="user-form-title">
                        <h2><?=__('Xác Minh 2FA');?></h2>
                        <p><?=__('Nhập mã xác minh mà bạn dùng để bật 2FA vào ô dưới đây để xác minh đăng nhập hợp lệ');?></p>
                    </div>
                    <div class="user-form-group">
                        
                        <form class="user-form">
                            <div class="form-group">
                                <input type="hidden" id="token_2fa" value="<?=$getUser['token_2fa'];?>">
                                <input type="text" id="code" class="form-control"
                                    placeholder="<?=__('Vui lòng nhập mã xác minh');?>" maxlength="6" pattern="[0-9]{6}" required>
                            </div>
                            <?php if(isCaptchaEnabledForModule('verify_2fa')): ?>
                            <center class="mb-3" id="captcha-container">
                                <?=renderCaptchaWidget('captcha-container', 'verify_2fa');?>
                            </center>
                            <?php endif; ?>
                            <div class="form-button">
                            <button type="button" id="btnsubmit"><?=__('Submit');?></button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="user-form-remind">
                <p><?=__('Bạn chưa có tài khoản?');?> <a href="<?=base_url('client/register');?>"><?=__('Đăng Ký Ngay');?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>


<?php
require_once(__DIR__.'/footer.php');
?>

<script type="text/javascript">
$("#btnsubmit").on("click", function() {
    // Validate 2FA code
    var code = $("#code").val();
    if (!code || code.length !== 6 || !/^[0-9]{6}$/.test(code)) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mã xác minh phải có đúng 6 chữ số');?>', 'error');
        return;
    }
    
    $('#btnsubmit').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Đang xử lý...');?>').prop('disabled',
        true);
    <?php if(isCaptchaEnabledForModule('verify_2fa')): ?>
    var __captchaVal = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
    if(!__captchaVal){
        Swal.fire('<?=__('Failure!');?>', '<?=__('Vui lòng xác nhận Captcha');?>', 'error');
        $('#btnsubmit').html('<?=__('Submit');?>').prop('disabled', false);
        return;
    }
    <?php endif; ?>
    var ajaxData = {
        action: 'Verify2FA',
        token_2fa: $("#token_2fa").val(),
        code: code
    };
    <?php if(isCaptchaEnabledForModule('verify_2fa')): ?>
    ajaxData.captcha_response = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
    ajaxData.recaptcha = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
    ajaxData['cf-turnstile-response'] = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : '';
    <?php endif; ?>
    
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: ajaxData,
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire({
                    title: '<?=__('Successful!');?>',
                    text: respone.msg,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.href = '<?=BASE_URL('');?>';
                    }
                });
                location.href = '<?=BASE_URL('');?>';
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnsubmit').html('<?=__('Submit');?>').prop('disabled', false);
        },
        error: function() {
            showMessage('<?=__('Vui lòng liên hệ Developer');?>', 'error');
            $('#btnsubmit').html('<?=__('Submit');?>').prop('disabled', false);
        }

    });
});
</script>
 