<?php

define("IN_SITE", true);
require_once(__DIR__."/../../../config.php");
require_once(__DIR__."/../../../libs/db.php");
require_once(__DIR__."/../../../libs/lang.php");
require_once(__DIR__."/../../../libs/helper.php");
require_once(__DIR__.'/../../../libs/database/users.php');
require_once(__DIR__.'/../../../models/is_admin.php');

if (empty($_GET['token'])) {
    die('<script type="text/javascript">if(!alert("'.__('Please log in').'")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}
if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '".check_string($_GET['token'])."' AND `banned` = 0 AND `admin` != 0 ")) {
    die('<script type="text/javascript">if(!alert("'.__('Please log in').'")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}
if(checkPermission($getUser['admin'], 'view_coupon') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}
if(!$row = $CMSNT->get_row(" SELECT * FROM `coupons` WHERE `id` = '".check_string($_GET['id'])."'  ")){
    die('<script type="text/javascript">if(!alert("'.__('Item does not exist').'")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}
$id = check_string($_GET['id']);

if (isset($_POST['btnSubmit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
    }
    if($row['status'] == 'cancel'){
        die('<script type="text/javascript">if(!alert("Đơn rút này đã được hoàn tiền rồi, không thể thay đổi trạng thái")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
    }
    if($_POST['status'] == 'cancel'){
        $User = new users;
        $User->RefundCommission($row['user_id'], $row['amount'],  __('Cancellation of withdrawal request').' #'.$row['trans_id']);
    } 
    $isUpdate = $CMSNT->update("aff_withdraw", [
        'status'            => check_string($_POST['status']),
        'reason'            => check_string($_POST['reason']),
        'update_gettime'    => gettime()
    ], " `id` = '" . $row['id'] . "' ");
    if ($isUpdate) {
        die('<script type="text/javascript">if(!alert("Lưu thành công!")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
    }
    die('<script type="text/javascript">if(!alert("Lưu thất bại!")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}


?>
 

<form action="<?=BASE_URL('ajaxs/admin/modal/coupon-view.php?id='.$row['id'].'&token='.$getUser['token']);?>"
    method="POST">

    <div class="modal-header">
        <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-clock-rotate-left"></i> Nhật ký sử dụng mã giảm giá <span
                class="text-primary"><?=$row['code'];?></span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <table id="datatable-basic" class="table text-nowrap table-striped table-hover table-bordered"
            style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Đơn hàng</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=0; foreach ($CMSNT->get_list("SELECT * FROM `coupon_used` WHERE `coupon_id` = '$id' ORDER BY `id` DESC ") as $row) {?>
                <tr>
                    <td><?=$i++;?></td>
                    <td><a class="text-primary"
                            href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                            [ID <?=$row['user_id'];?>]</a></td>
                    <td><?=$row['trans_id'];?></td>
                    <td><?=$row['create_gettime'];?></td>
                </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"><i
                class="fa fa-fw fa-times me-1"></i>
            Close</button>
    </div>
</form>


<script>
$('#datatable-basic').DataTable({
    language: {
        searchPlaceholder: 'Search...',
        sSearch: '',
    },
    "pageLength": 10
});
</script>
 