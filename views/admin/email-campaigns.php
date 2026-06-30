<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Email Campaigns').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '

';
$body['footer'] = '



';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_email_campaigns') != true){
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


if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$create_gettime = '';
$subject = '';
$shortByDate  = '';
$name = '';
$status = '';



if(!empty($_GET['status'])){
    $status = check_string($_GET['status']);
    $stt22 = $status - 1;
    $where .= ' AND `status` = "'.$stt22.'" ';
}
if(!empty($_GET['subject'])){
    $subject = check_string($_GET['subject']);
    $where .= ' AND `subject` LIKE "%'.$subject.'%" ';
}
if(!empty($_GET['name'])){
    $name = check_string($_GET['name']);
    $where .= ' AND `name` LIKE "%'.$name.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_date = check_string($_GET['create_date']);
    $create_gettime = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);

    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `thoigian` >= '".$create_date_1[0]."' AND `thoigian` <= '".$create_date_1[1]."' ";
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `thoigian` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(thoigian) = $currentYear AND WEEK(thoigian, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(thoigian) = '$currentMonth' AND YEAR(thoigian) = '$currentYear' ";
    }
}



$listDatatable = $CMSNT->get_list(" SELECT * FROM `email_campaigns` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `email_campaigns` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("email-campaigns&limit=$limit&shortByDate=$shortByDate&subject=$subject&create_gettime=$create_gettime&name=$name&status=$status&"), $from, $totalDatatable, $limit);



?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-envelope"></i> Email Campaigns</h1>
        </div>
        <?php if(time() - $CMSNT->site('check_time_cron_sending_email') >= 120):?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            Vui lòng thực hiện <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> liên kết: <a class="text-primary"
                href="<?=base_url('cron/sending_email.php?key='.$CMSNT->site('key_cron_job'));?>"
                target="_blank"><?=base_url('cron/sending_email.php?key='.$CMSNT->site('key_cron_job'));?></a> 1 phút 1 lần để sử dụng chức năng Email
            Campaigns.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <?php if($CMSNT->site('smtp_status') != 1):?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            Vui lòng cấu hình <b>SMTP</b> để sử dụng chức năng Email Campaigns
            <a class="text-primary"
                href="https://help.cmsnt.co/huong-dan/huong-dan-cau-hinh-smtp-vao-website-shopclone7/"
                target="_blank">Xem Hướng Dẫn</a>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH CHIẾN DỊCH EMAIL MARKETING
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?=base_url_admin('email-campaign-add');?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Tạo chiến dịch mới</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="email-campaigns">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$name;?>" name="name"
                                        placeholder="Tên chiến dịch">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$subject;?>" name="subject"
                                        placeholder="Tiêu đề mail">
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control form-control-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 1 ? 'selected' : '';?> value="1">Processing</option>
                                        <option <?=$status == 3 ? 'selected' : '';?> value="3">Cancel</option>
                                        <option <?=$status == 2 ? 'selected' : '';?> value="2">Completed</option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('email-campaigns');?>"><i class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
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
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tên chiến dịch</th>
                                        <th>Tiêu đề mail</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th class="text-center">Tiến trình</th>
                                        <th class="text-center">Thời gian</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><b><?=$row['name'];?></b></td>
                                        <td><small><?=$row['subject'];?></small></td>
                                        <td class="text-center"><?=display_camp($row['status']);?></td>
                                        <td>
                                            <?php
                                                $total_success = $CMSNT->get_row(" SELECT COUNT(id) FROM `email_sending` WHERE `camp_id` = '".$row['id']."' AND `status` = 1 ")['COUNT(id)'];
                                                $total = $CMSNT->get_row(" SELECT COUNT(id) FROM `email_sending` WHERE `camp_id` = '".$row['id']."' ")['COUNT(id)'];
                                                $phantram = 0;
                                                if($total != 0){
                                                    $phantram = $total_success / $total * 100;
                                                }
                                                ?>

                                            <div class="progress progress-xl  progress-animate custom-progress-4 info"
                                                role="progressbar" aria-valuenow="<?=$total_success;?>"
                                                aria-valuemin="0" aria-valuemax="<?=$total;?>">
                                                <div class="progress-bar bg-info-gradient"
                                                    style="width: <?=$phantram;?>%"></div>
                                                <div class="progress-bar-label">
                                                    <?=format_cash($total_success);?>/<?=format_cash($total);?>
                                                    (<?=format_cash($phantram);?>%)</div>
                                            </div>
                                            <div class="text-center"><a class="text-primary"
                                                    href="<?=base_url_admin('email-sending-view&id='.$row['id']);?>">View
                                                    Sending Report</a></div>
                                        </td>
                                        <td class="text-center"><span
                                                class="badge bg-light text-dark"><?=$row['create_gettime'];?></span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-dark btn-sm dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    <?=__('Manage');?>
                                                </button>
                                                <div class="dropdown-menu" style="">
                                                    <a class="dropdown-item"
                                                        href="<?=base_url_admin('email-sending-view&id='.$row['id']);?>"><i
                                                            class="fa-solid fa-eye"></i> View</a>
                                                    <a class="dropdown-item"
                                                        href="<?=base_url_admin('email-campaign-edit&id='.$row['id']);?>"><i
                                                            class="fa-solid fa-pen-to-square"></i> Edit</a>
                                                    <button class="dropdown-item" onclick="CancelRow(<?=$row['id'];?>)"
                                                        <?=$row['status'] == 2 ? 'disabled' : '';?>><i
                                                            class="fa-solid fa-ban"></i> <?=__('Cancel');?></button>

                                                    <button class="dropdown-item"
                                                        onclick="RemoveRow(<?=$row['id'];?>)"><i
                                                            class="fa-solid fa-trash"></i> <?=__('Delete');?></button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
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

const multipleCancelButton = new Choices(
    '#listUser', {
        allowHTML: true,
        removeItemButton: true,
    }
);
</script>

<script type="text/javascript">
function CancelRow(id) {
    cuteAlert({
        type: "question",
        title: "Campaign Cancel Confirmation",
        message: "Are you sure you want to cancel this campaign?",
        confirmText: "Ok",
        cancelText: "Cancel"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/update.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'cancel_email_campaigns',
                    id: id
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        location.reload();
                    } else {
                        showMessage(result.msg, result.status);
                    }
                }
            });
        }
    })
}


function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "Campaign deletion confirmation",
        message: "Are you sure you want to delete this campaign?",
        confirmText: "Ok",
        cancelText: "Cancel"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'email_campaigns',
                    id: id
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        location.reload();
                    } else {
                        showMessage(result.msg, result.status);
                    }
                }
            });
        }
    })
}
</script>