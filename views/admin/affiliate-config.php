<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Cấu hình Tiếp Thị Liên Kết').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>


';
$body['footer'] = '
<!-- ckeditor -->
<script src="'.BASE_URL('public/ckeditor/ckeditor.js').'"></script>
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_affiliate') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Crypto Deposit Configuration'
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình Affiliate Program'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("'.__('Save successfully!').'")){window.history.back().location.reload();}</script>');
} 
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Cấu hình Affiliate</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Affiliate Program</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cấu hình Affiliate</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">

                <div class="alert alert-warning alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-warning" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                    </svg>
                    Từ phiên bản <strong>2.6.1</strong> trở đi, hoa hồng sẽ được tính khi khách hàng mua hàng thay vì
                    khi nạp tiền.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
                <div class="alert alert-primary alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                    <svg class="svg-primary" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                    </svg>
                    Liên kết AFF sẽ lưu Cookie trong 30 ngày.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CẤU HÌNH AFFILIATE
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Trạng thái');?></label>
                                        <div class="col-sm-6">
                                            <select class="form-control" name="affiliate_status" required>
                                                <option <?=$CMSNT->site('affiliate_status') == 1 ? 'selected' : '';?>
                                                    value="1">
                                                    <?=__('ON');?></option>
                                                <option <?=$CMSNT->site('affiliate_status') == 0 ? 'selected' : '';?>
                                                    value="0">
                                                    <?=__('OFF');?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Hoa hồng nạp tiền');?></label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="<?=$CMSNT->site('affiliate_ck');?>" name="affiliate_ck"
                                                    placeholder="VD 10 = 10%">
                                                <span class="input-group-text">
                                                    %
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Số tiền rút tối thiểu');?></label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="<?=$CMSNT->site('affiliate_min');?>" name="affiliate_min"
                                                    placeholder="VD 100000 = 100.000đ">
                                                <span class="input-group-text">
                                                    <?=__('VNĐ');?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Phương thức rút tiền');?></label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <textarea class="form-control" rows="4"
                                                    placeholder="Mỗi dòng 1 ngân hàng"
                                                    name="affiliate_banks"><?=$CMSNT->site('affiliate_banks');?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Chat ID Telegram nhận thông báo rút tiền');?></label>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="<?=$CMSNT->site('affiliate_chat_id_telegram');?>"
                                                    name="affiliate_chat_id_telegram" placeholder="">
                                                <span class="input-group-text">
                                                    <?=__('BOT Telegram');?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-12">
                                    <div class="row mb-3">
                                        <label class="col-sm-6 col-form-label"
                                            for="example-hf-email"><?=__('Lưu ý');?></label>
                                        <div class="col-sm-12">
                                            <textarea id="affiliate_note"
                                                name="affiliate_note"><?=$CMSNT->site('affiliate_note');?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">

                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href=""><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Reload');?></a>
                            <button type="submit" name="SaveSettings" class="btn btn-primary">
                                <i class="fa fa-fw fa-save me-1"></i> <?=__('Lưu Ngay');?>
                            </button>
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

<script>
CKEDITOR.replace("affiliate_note");
</script>