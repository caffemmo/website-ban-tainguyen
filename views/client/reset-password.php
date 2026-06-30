<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Thay đổi mật khẩu').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '

';
$body['footer'] = '

';

if(empty($_GET['token'])){
    redirect(base_url());
}
$token = validate_alphanumeric($_GET['token'], 255);
if($token === false || !$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_forgot_password` = ? AND `token_forgot_password` IS NOT NULL", [$token])) {
    if(empty($getUser['token_forgot_password'])){
        checkBlockIP('RESET_PASSWORD', 15);
        redirect(base_url());
    }
    checkBlockIP('RESET_PASSWORD', 15);
    redirect(base_url());
}
require_once(__DIR__.'/header.php');
?>

<body>
    <section class="user-form-part">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="user-form-logo"><a href="<?=base_url();?>"><img
                                src="<?=BASE_URL($CMSNT->site('logo_light'));?>" alt="logo"></a></div>
                    <div class="user-form-card">
                        <div class="user-form-title">
                            <h2><?=__('Thay đổi mật khẩu');?></h2>
                        </div>
                        <form class="user-form">
                        <input type="hidden" id="csrf_token" value="<?=generate_csrf_token();?>">
                         <input type="hidden" id="ChangePassword_token" value="<?=$getUser['token_forgot_password'];?>">
                            <div class="form-group">
                                <input type="password" id="ChangePassword_password" class="form-control"
                                    placeholder="<?=__('Vui lòng nhập mật khẩu mới');?>" minlength="6" maxlength="50" required>
                            </div>
                            <div class="form-group">
                                <input type="password" id="ChangePassword_repassword" class="form-control"
                                    placeholder="<?=__('Nhập lại mật khẩu mới');?>" minlength="6" maxlength="50" required>
                            </div>
                            <div class="form-button"><button type="button"
                                    id="btnChangePassword"><?=__('Thay đổi mật khẩu');?></button></div>
                        </form>
                    </div>
                    <div class="user-form-remind">
                        <p><?=__('Bạn đã có tài khoản?');?> <a href="<?=BASE_URL();?>"><?=__('Đăng Nhập');?></a></p>
                    </div>
                    <div class="user-form-footer">
                        <p>&COPY; Copyright by <a href="<?=BASE_URL();?>"><?=$CMSNT->site('title');?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="<?=BASE_URL('public/client/');?>vendor/bootstrap/jquery-1.12.4.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/bootstrap/popper.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/bootstrap/bootstrap.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/countdown/countdown.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/niceselect/nice-select.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/slickslider/slick.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>vendor/venobox/venobox.min.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/nice-select.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/countdown.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/accordion.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/venobox.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/slick.js"></script>
    <script src="<?=BASE_URL('public/client/');?>js/main.js"></script>
</body>

</html>

 

<script type="text/javascript">
$("#btnChangePassword").on("click", function() {
    // Validate password inputs
    var newPassword = $("#ChangePassword_password").val();
    var confirmPassword = $("#ChangePassword_repassword").val();
    
    if (!newPassword || newPassword.length < 6) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mật khẩu mới phải có ít nhất 6 ký tự');?>', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        Swal.fire('<?=__('Error');?>', '<?=__('Mật khẩu xác nhận không khớp');?>', 'error');
        return;
    }
    
    $('#btnChangePassword').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?>').prop(
        'disabled',
        true);
    
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'ChangePassword',
            csrf_token: $("#csrf_token").val(),
            token: $("#ChangePassword_token").val(),
            newpassword: $("#ChangePassword_password").val(),
            renewpassword: $("#ChangePassword_repassword").val()
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire({
                    title: '<?=__('Successful !');?>',
                    text: respone.msg,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: '<?=__('Sign In');?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.href = '<?=BASE_URL();?>';
                    }
                });
                location.href = '<?=BASE_URL();?>';
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnChangePassword').html(
                '<?=__('Thay đổi mật khẩu');?>'
                ).prop('disabled', false);
        },
        error: function() {
            showMessage('<?=__('Không thể xử lý');?>', 'error');
            $('#btnChangePassword').html(
                '<?=__('Thay đổi mật khẩu');?>'
                ).prop('disabled', false);
        }

    });
});
</script>