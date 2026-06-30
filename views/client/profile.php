<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Profile').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
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
                <div class="row">

                    <div class="col-lg-12">
                        <div class="account-card">
                            <h4 class="account-title"><?=__('Ví của tôi');?></h4>
                            <div class="my-wallet">
                                <p><?=__('Số dư hiện tại');?></p>
                                <h3><?=format_currency($getUser['money']);?></h3>
                            </div>
                            <div class="wallet-card-group">
                                <div class="wallet-card">
                                    <p><?=__('Tổng tiền nạp');?></p>
                                    <h3><?=format_currency($getUser['total_money']);?></h3>
                                </div>
                                <div class="wallet-card">
                                    <p><?=__('Số dư đã sử dụng');?></p>
                                    <h3><?=format_currency($getUser['total_money'] - $getUser['money']);?></h3>
                                </div>
                                <div class="wallet-card">
                                    <p><?=__('Giảm giá');?></p>
                                    <h3><?=$getUser['discount'];?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                    <div class="account-card">
                        <div class="account-title">
                            <h4><?=__('Hồ sơ của bạn');?></h4>
                            <button data-bs-toggle="modal"
                                data-bs-target="#profile-edit"><?=__('Chỉnh sửa thông tin');?></button>
                        </div>
                        <div class="account-content">
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label"><?=__('Tên đăng nhập');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['username'];?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label"><?=__('Địa chỉ Email');?></label>
                                        <input type="email" class="form-control" placeholder="Enter your email.."
                                            value="<?=$getUser['email'];?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label class="form-label"><?=__('Số điện thoại');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['phone'];?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label class="form-label"><?=__('Họ và Tên');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['fullname'];?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label
                                            class="form-label"><?=__('Telegram Chat ID');?></label>
                                        <input type="text" class="form-control"
                                            value="<?=$getUser['telegram_chat_id'];?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label class="form-label"><?=__('Thiết bị');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['device'];?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label
                                            class="form-label"><?=__('Đăng ký vào lúc');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['create_date'];?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group"><label
                                            class="form-label"><?=__('Đăng nhập gần nhất');?></label>
                                        <input type="text" class="form-control" value="<?=$getUser['update_date'];?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="profile-edit">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"><button class="modal-close" data-bs-dismiss="modal"><i
                    class="icofont-close"></i></button>
            <form class="modal-form">
                <div class="form-title">
                    <h3><?=__('Chỉnh sửa thông tin');?></h3>
                </div>
                <div class="form-group"><label class="form-label"><?=__('Số điện thoại');?></label>
                    <input type="hidden" class="form-control" value="<?=$getUser['token'];?>" id="token">
                    <input type="text" class="form-control" value="<?=$getUser['phone'];?>" id="phone" maxlength="20" pattern="[0-9+\-\s()]+">
                </div>
                <div class="form-group"><label class="form-label"><?=__('Họ và Tên');?></label>
                    <input type="text" class="form-control" value="<?=$getUser['fullname'];?>" id="fullname" maxlength="100">
                </div>
                <div class="form-group"><label class="form-label"><?=__('Telegram Chat ID');?></label>
                    <input type="text" class="form-control" value="<?=$getUser['telegram_chat_id'];?>"
                        id="telegram_chat_id" maxlength="20" pattern="[0-9\-]+">
                </div>
                <button class="form-btn" id="btnSaveProfile" type="button"><?=__('Lưu');?></button>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
$("#btnSaveProfile").on("click", function() {
    // Validate profile inputs
    var phone = $("#phone").val();
    var fullname = $("#fullname").val();
    var telegram_chat_id = $("#telegram_chat_id").val();
    
    if (phone && !/^[0-9+\-\s()]+$/.test(phone)) {
        Swal.fire('<?=__('Error');?>', '<?=__('Số điện thoại không hợp lệ');?>', 'error');
        return;
    }
    
    if (fullname && fullname.length > 100) {
        Swal.fire('<?=__('Error');?>', '<?=__('Họ và tên không được vượt quá 100 ký tự');?>', 'error');
        return;
    }
    
    if (telegram_chat_id && !/^[0-9\-]+$/.test(telegram_chat_id)) {
        Swal.fire('<?=__('Error');?>', '<?=__('Telegram Chat ID không hợp lệ');?>', 'error');
        return;
    }
    
    $('#btnSaveProfile').html('<i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?>')
        .prop('disabled',
            true);
    $.ajax({
        url: "<?=base_url('ajaxs/client/auth.php');?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'ChangeProfile',
            token: $("#token").val(),
            phone: phone,
            fullname: fullname,
            telegram_chat_id: telegram_chat_id
        },
        success: function(respone) {
            if (respone.status == 'success') {
                Swal.fire('<?=__('Successful!');?>', respone.msg, 'success');
            } else {
                Swal.fire('<?=__('Failure!');?>', respone.msg, 'error');
            }
            $('#btnSaveProfile').html(
                '<?=__('Lưu');?>'
            ).prop('disabled',
                false);
        },
        error: function() {
            showMessage('<?=__('Không thể xử lý');?>', 'error');
            $('#btnSaveProfile').html(
                '<?=__('Lưu');?>'
            ).prop('disabled',
                false);
        }

    });
});
</script>




<?php
require_once(__DIR__.'/footer.php');
?>