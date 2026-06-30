<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Add Campaign'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_email_campaigns') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_email_campaigns') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $isInsert = $CMSNT->insert('email_campaigns', [
        'name'              => check_string($_POST['name']),
        'subject'           => $_POST['subject'],
        'cc'                => !empty($_POST['cc']) ? check_string($_POST['cc']) : NULL,
        'bcc'               => !empty($_POST['bcc']) ? check_string($_POST['bcc']) : NULL,
        'content'           => $_POST['content'],
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime(),
        'status'            => 0
    ]);
    if (empty($_POST['listUser'])) {
        foreach ($CMSNT->get_list("SELECT * FROM `users` WHERE `banned` = 0 AND `email` IS NOT NULL ") as $user) {
            $CMSNT->insert('email_sending', [
                'camp_id'           => $CMSNT->get_row(" SELECT `id` FROM `email_campaigns` ORDER BY id DESC LIMIT 1 ")['id'],
                'user_id'           => $user['id'],
                'status'            => 0,
                'create_gettime'    => gettime(),
                'update_gettime'    => gettime()
            ]);
        }
    } else {
        foreach ($_POST['listUser'] as $user) {
            $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$user' ");
            $CMSNT->insert('email_sending', [
                'camp_id'           => $CMSNT->get_row(" SELECT `id` FROM `email_campaigns` ORDER BY id DESC LIMIT 1 ")['id'],
                'user_id'           => $user['id'],
                'status'            => 0,
                'create_gettime'    => gettime(),
                'update_gettime'    => gettime()
            ]);
        }
    }
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo chiến dịch Email Makreting')." (".check_string($_POST['name']).")"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Tạo chiến dịch Email Makreting')." (".check_string($_POST['name']).")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Successful !")){location.href = "'.base_url_admin('email-campaigns').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Failure !")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-inbox"></i> Tạo chiến dịch Email
                Marketing</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            TẠO CHIẾN DỊCH EMAIL MARKETING
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tên chiến dịch');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" placeholder="Nhập tên cho chiến dịch"
                                        name="name" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Người nhận');?></label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="listUser[]" id="listUser" multiple>
                                        <option value="">Mặc định sẽ áp dụng cho toàn bộ sản phẩm nếu không chọn
                                        </option>
                                        <?php foreach ($CMSNT->get_list("SELECT * FROM `users` ") as $user) {?>
                                        <option value="<?=$user['id'];?>">ID: <?=$user['id'];?> | Username:
                                            <?=$user['username'];?> | Email: <?=$user['email'];?></option>
                                        <?php }?>
                                    </select>
                                    <i><?=__('Mặc định sẽ gửi toàn bộ thành viên nếu không chọn');?></i>
                                </div>
                                <script>
                                const multipleCancelButton = new Choices(
                                    '#listUser', {
                                        allowHTML: true,
                                        removeItemButton: true,
                                    }
                                );
                                </script>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tiêu đề Mail');?> <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" name="subject" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('CC');?></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" name="cc">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('BCC');?></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" name="bcc">
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Nội dung Email');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-12">
                                    <textarea class="content" id="content" name="content" required></textarea>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href="<?=base_url_admin('email-campaigns');?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?=__('Back');?></a>
                            <button type="submit" name="submit" class="btn btn-primary"><i
                                    class="fa fa-fw fa-plus me-1"></i>
                                <?=__('Submit');?></button>
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