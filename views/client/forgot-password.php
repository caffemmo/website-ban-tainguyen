<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quên mật khẩu').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
' . renderCaptchaScripts('forgot_password') . '
';
$body['footer'] = '

';
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                
                <div class="user-form-card">
                    <div class="user-form-title">
                        <h2><?=__('Bạn quên mật khẩu?');?></h2>
                        <p><?=__('Vui lòng nhập thông tin vào ô dưới đây để xác minh');?></p>
                    </div>
                    <form class="user-form">
                        <div class="form-group">
                            <input type="hidden" id="csrf_token" value="<?=generate_csrf_token();?>">
                            <input type="email" id="email" class="form-control"
                                placeholder="<?=__('Vui lòng nhập địa chỉ Email');?>"></div>
                        <?php if(isCaptchaEnabledForModule('forgot_password')): ?>
                        <center class="mb-3" id="captcha-container">
                            <?=renderCaptchaWidget('captcha-container', 'forgot_password');?>
                        </center>
                        <?php endif; ?>
                        <div class="form-button"><button type="button"
                                id="btnForgotPassword"><?=__('Xác minh');?></button></div>
                    </form>
                </div>
                <div class="user-form-remind">
                    <p><?=__('Bạn đã có tài khoản?');?> <a href="<?=BASE_URL('client/login');?>"><?=__('Đăng Nhập');?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
require_once(__DIR__.'/footer.php');
?>

<script type="text/javascript">
$("#btnForgotPassword").on("click", function() {
    $('#btnForgotPassword').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?>').prop(
        'disabled',
        true);
    <?php if(isCaptchaEnabledForModule('forgot_password')): ?>
    var __captchaVal = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
    if(!__captchaVal){
        Swal.fire('<?=__('Failure!');?>', '<?=__('Vui lòng xác nhận Captcha');?>', 'error');
        $('#btnForgotPassword').html('<?=__('Xác minh');?>').prop('disabled', false);
        return;
    }
    <?php endif; ?>
    var ajaxData = {
        action: 'ForgotPassword',
        csrf_token: $("#csrf_token").val(),
        email: $("#email").val()
    };
    <?php if(isCaptchaEnabledForModule('forgot_password')): ?>
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
                    title: '<?=__('Successful !');?>',
                    text: respone.msg,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        
                    }
                });
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnForgotPassword').html(
                    '<?=__('Xác minh');?>')
                .prop('disabled', false);
        },
        error: function() {
            showMessage('<?=__('Không thể xử lý');?>', 'error');
            $('#btnForgotPassword').html(
                    '<?=__('Xác minh');?>')
                .prop('disabled', false);
        }

    });
});
</script>