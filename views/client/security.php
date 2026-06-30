<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Bảo mật tài khoản').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/profile.css">
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3">
                <?php require_once(__DIR__.'/sidebar.php');?>
            </div>
            <div class="col-lg-9">
                <div class="account-card">
                    <div class="account-title">
                        <h4><?=__('Bảo mật tài khoản');?></h4>
                    </div>
                    <div class="account-content">
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group row">
                                    <div class="col-xl-6">
                                        <label class="form-label"><?=__('Xác minh đăng nhập bằng');?>
                                            <b>OTP Mail:</b></label>
                                    </div>
                                    <div class="col-xl-6">
                                        <input type="hidden" id="token" value="<?=$getUser['token'];?>">
                                        <label class="switch">
                                            <input type="checkbox" value="1" id="status_otp_mail"
                                                <?=$getUser['status_otp_mail'] == 1 ? 'checked' : '';?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-12">
                                <div class="form-group row">
                                    <div class="col-xl-6">
                                        <label
                                            class="form-label"><?=__('Gửi thông báo về mail khi đăng nhập thành công:');?></label>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="switch">
                                            <input type="checkbox" value="1" id="status_noti_login_to_mail"
                                                <?=$getUser['status_noti_login_to_mail'] == 1 ? 'checked' : '';?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-12">
                                <div class="form-group row">
                                    <div class="col-xl-6">
                                        <label
                                            class="form-label"><?=__('Đúng Trình Duyệt và IP mua hàng mới có thể xem đơn hàng:');?></label>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="switch">
                                            <input type="checkbox" value="1" id="status_view_order"
                                                <?=$getUser['status_view_order'] == 1 ? 'checked' : '';?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <center>
                                <button class="form-btn" id="btnChangeSecurity"
                                    type="button"><?=__('Cập Nhật');?></button>
                            </center>
                        </div>
                    </div>
                </div>
                <div class="account-card py-4">

                    <div class="account-content">
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group row">
                                    <div class="col-xl-6">
                                        <label class="form-label"><?=__('Xác minh đăng nhập bằng');?> <b>Google
                                                Authenticator</b>:</label>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="switch">
                                            <input type="checkbox" value="1" id="status_2fa"
                                                <?=$getUser['status_2fa'] == 1 ? 'checked' : '';?>>
                                            <span class="slider"></span>
                                        </label>
                                        <div id="qr_2fa" style="display:none;">
                                            <?php
                                    use PragmaRX\Google2FAQRCode\Google2FA;

                                    $google2fa = new Google2FA();
                                    $qrCodeUrl = $google2fa->getQRCodeInline($CMSNT->site('title'), $getUser['email'], $getUser['SecretKey_2fa']);
                                    
                                   ?>
                                            <img src="<?=$qrCodeUrl;?>" alt="QR Code 2FA"><br>
                                            
                                            <input placeholder="<?=__('Nhập mã xác minh để lưu');?>" class="input-style"
                                                id="secret" type="text" maxlength="6" pattern="[0-9]{6}" required>
                                            <button class="btn-save" id="btnSave2FA">
                                                <span><i class="fa-solid fa-floppy-disk"></i>
                                                    <?=__('Lưu');?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small><?=__('- Sử dụng điện thoại tải App Google Authenticator sau đó quét mã QR để nhận mã xác minh.');?><br>
                            <?=__('- Mã QR sẽ được thay đổi khi bạn tắt xác minh.');?><br>
                        <?=__('- Nếu bật Xác minh đăng nhập bằng OTP Mail thì không bật Google Authenticator và ngược lại.');?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $("#status_2fa").change(function() {
        var qrElement = $("#qr_2fa");
        var toggled = qrElement.data('toggled');
        
        if (!toggled) {
            qrElement.show();
            qrElement.data('toggled', true);
        } else {
            qrElement.hide();
            qrElement.data('toggled', false);
        }
    });
});

</script>
<script type="text/javascript">
$("#btnSave2FA").on("click", function() {
    // Validate 2FA secret code
    var secret = $("#secret").val();
    if (!secret || secret.length !== 6 || !/^[0-9]{6}$/.test(secret)) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mã xác minh phải có đúng 6 chữ số');?>', 'error');
        return;
    }
    
    $('#btnSave2FA').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'Save2FA',
            token: $("#token").val(),
            status_2fa: $("#status_2fa").is(":checked") ? 1 : 0,
            secret: secret
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire('<?=__('Successful!');?>', respone.msg, 'success');
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnSave2FA').html(
                '<span><i class="fa-solid fa-floppy-disk"></i> <?=__('Lưu');?></span>'
            ).prop('disabled',
                false);
        },
        error: function() {
            showMessage('<?=__('Không thể xử lý');?>', 'error');
            $('#btnSave2FA').html(
                '<span><i class="fa-solid fa-floppy-disk"></i> <?=__('Lưu');?></span>'
            ).prop('disabled',
                false);
        }

    });
});
</script>

<script type="text/javascript">
$("#btnChangeSecurity").on("click", function() {
    $('#btnChangeSecurity').html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'changeSecurity',
            token: $("#token").val(),
            status_noti_login_to_mail: $("#status_noti_login_to_mail").is(":checked") ? 1 : 0,
            status_otp_mail: $("#status_otp_mail").is(":checked") ? 1 : 0,
            status_view_order: $("#status_view_order").is(":checked") ? 1 : 0
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire('<?=__('Successful!');?>', respone.msg, 'success');
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnChangeSecurity').html(
                '<?=__('Cập nhật');?>'
            ).prop('disabled',
                false);
        },
        error: function() {
            showMessage('<?=__('Không thể xử lý');?>', 'error');
            $('#btnChangeSecurity').html(
                '<?=__('Cập nhật');?>'
            ).prop('disabled',
                false);
        }

    });
});
</script>


<?php
require_once(__DIR__.'/footer.php');
?>