<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Profile').' | '.$CMSNT->site('title'),
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
                        <h4><?=__('Thay đổi mật khẩu');?></h4>
                    </div>
                    <div class="account-content">
                        <p class="mb-3 text-muted">
                            <?=__('Thay đổi mật khẩu đăng nhập của bạn là một cách dễ dàng để giữ an toàn cho tài khoản của bạn.');?>
                        </p>
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="form-label"><?=__('Mật khẩu hiện tại');?></label>
                                    <input type="hidden" class="form-control" id="token" value="<?=$getUser['token'];?>">
                                    <input type="password" class="form-control" id="dm-profile-edit-password"
                                        name="dm-profile-edit-password" minlength="6" maxlength="50" required>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="form-label"><?=__('Mật khẩu mới');?></label>
                                    <input type="password" class="form-control" id="dm-profile-edit-password-new"
                                        name="dm-profile-edit-password-new" minlength="6" maxlength="50" required>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group"><label
                                        class="form-label"><?=__('Nhập lại mật khẩu mới');?></label>
                                    <input type="password" class="form-control"
                                        id="dm-profile-edit-password-new-confirm"
                                        name="dm-profile-edit-password-new-confirm" minlength="6" maxlength="50" required>
                                </div>
                            </div>
                            <center>
                                <button class="form-btn" id="btnChangePasswordProfile"
                                    type="button"><?=__('Cập Nhật');?></button>
                            </center>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
 

<script type="text/javascript">
$("#btnChangePasswordProfile").on("click", function() {
    // Validate password inputs
    var currentPassword = $("#dm-profile-edit-password").val();
    var newPassword = $("#dm-profile-edit-password-new").val();
    var confirmPassword = $("#dm-profile-edit-password-new-confirm").val();
    
    if (!currentPassword || currentPassword.length < 6) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mật khẩu hiện tại phải có ít nhất 6 ký tự');?>', 'error');
        return;
    }
    
    if (!newPassword || newPassword.length < 6) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mật khẩu mới phải có ít nhất 6 ký tự');?>', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mật khẩu xác nhận không khớp');?>', 'error');
        return;
    }
    
    $('#btnChangePasswordProfile').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?>')
        .prop('disabled', true);
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'ChangePasswordProfile',
            token: $("#token").val(),
            password: currentPassword,
            newpassword: newPassword,
            renewpassword: confirmPassword
        },
        success: function(result) {
            if (result.status == 'success') {
                Swal.fire('<?=__('Successful!');?>', result.msg, 'success');
                setTimeout("location.href = '<?=BASE_URL('client/login');?>';", 1000);
            } else {
                Swal.fire('<?=__('Failure!');?>', result.msg, 'error');
            }
            $('#btnChangePasswordProfile').html(
                '<?=__('Cập Nhật');?>'
            ).prop('disabled',
                false);
        },
        error: function() {
            showMessage('Không thể xử lý', 'error');
            $('#btnChangePasswordProfile').html(
                '<?=__('Cập Nhật');?>'
            ).prop('disabled',
                false);
        }

    });
});

 
</script>


<?php
require_once(__DIR__.'/footer.php');
?>