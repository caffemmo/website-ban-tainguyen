<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Edit Campaign'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="'.BASE_URL('public/AdminLTE3/').'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `email_campaigns` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('email-campaigns'));
    }
} else {
    redirect(base_url_admin('email-campaigns'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
?>
<?php
if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_email_campaigns') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $isInsert = $CMSNT->update('email_campaigns', [
        'name'              => check_string($_POST['name']),
        'subject'           => $_POST['subject'],
        'cc'                => !empty($_POST['cc']) ? check_string($_POST['cc']) : NULL,
        'bcc'               => !empty($_POST['bcc']) ? check_string($_POST['bcc']) : NULL,
        'content'           => $_POST['content'],
        'update_gettime'    => gettime()
    ], " `id` = '".$row['id']."' ");
    
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Chỉnh sửa chiến dịch Email Marketing')." (".check_string($_POST['name']).")"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Chỉnh sửa chiến dịch Email Marketing')." (".check_string($_POST['name']).")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Successful !")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Failure !")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Chỉnh sửa chiến dịch <?=__($row['name']);?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('email-campaigns');?>">Email
                                Campaigns</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa chiến dịch
                            <?=__($row['name']);?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA CHIẾN DỊCH
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tên chiến dịch');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" value="<?=$row['name'];?>" type="text"
                                        placeholder="Nhập tên cho chiến dịch" name="name" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Subject');?> <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" value="<?=$row['subject'];?>" type="text" name="subject"
                                        required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('CC');?></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" value="<?=$row['cc'];?>" name="cc">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('BCC');?></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" value="<?=$row['bcc'];?>" name="bcc">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Nội dung Email');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-12">
                                    <textarea class="content" id="content" name="content"
                                        required><?=$row['content'];?></textarea>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href="<?=base_url_admin('email-campaigns');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?=__('Back');?></a>
                            <button type="submit" name="submit" class="btn btn-primary"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Save');?></button>
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
CKEDITOR.replace("content");
</script>