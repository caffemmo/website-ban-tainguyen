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
if(checkPermission($getUser['admin'], 'edit_withdraw_affiliate') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}
if(!$row = $CMSNT->get_row(" SELECT * FROM `aff_withdraw` WHERE `id` = '".check_string($_GET['id'])."'  ")){
    die('<script type="text/javascript">if(!alert("'.__('Item does not exist').'")){location.href=`' . base_url_admin('affiliate-withdraw') . '`;}</script>');
}

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


<form action="<?=BASE_URL('ajaxs/admin/modal/withdraw-edit.php?id='.$row['id'].'&token='.$getUser['token']);?>"
    method="POST">

    <div class="modal-header">
        <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa fa-edit"></i> <?=__('Chỉnh sửa yêu cầu');?>
            #<span class="text-primary"><?=$row['trans_id'];?></span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body">
        <div class="row">
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Ngân hàng:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=$row['bank'];?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Số tài khoản:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=$row['stk'];?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Chủ tài khoản:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=$row['name'];?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Số tiền cần rút:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=format_currency($row['amount']);?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Trạng thái:</label>
                    <div class="col-sm-7">
                        <select class="form-control mb-1" name="status">
                        <option <?=$row['status'] == 'pending' ? 'selected' : '';?> value="pending">
                                <?=__('Pending');?></option>
                            <option <?=$row['status'] == 'completed' ? 'selected' : '';?> value="completed">
                                <?=__('Completed');?></option>
                            <option <?=$row['status'] == 'cancel' ? 'selected' : '';?> value="cancel">
                                <?=__('Cancel');?></option>
                        </select>
                        <ul>
                            <li>Pending: đang chờ xử lý.</li>
                            <li>Cancel: huỷ và hoàn tiền.</li>
                            <li>Completed: hoàn thành yêu cầu rút tiền.</li>
                        </ul>
                        </br>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Lý do huỷ đơn nếu có:</label>
                    <div class="col-sm-7">
                        <textarea class="form-control" rows="4" name="reason"><?=$row['reason'];?></textarea>
                    </div>
                </div>
            </div>
        </div>
                        <center class="py-3">

                        <?php if($row['bank'] == 'Ví MOMO'): ?>
                        <?=file_get_contents("https://api.web2m.com/api/qrmomo.php?amount=".$row['amount']."&phone=".$row['stk']."&noidung=".$row['trans_id']."&size=300");?>
                        <?php else:?>
                        <?php
                        $img1 = "https://api.vietqr.io/".$row['bank']."/".$row['stk']."/".$row['amount']."/".$row['trans_id']."/vietqr_net_2.jpg?accountName=".$row['name'];
                        $img = $img1;
                        $is_img = curl_get($img1);
                        ?>
                        <?php if($is_img != 'invalid acqId'):?>
                        <img src="<?=$img;?>" width="300px" />
                        <?php else:?>
        
                        <?php endif?>
                        <?php endif?>
                    </center>
    </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i
                class="fa fa-fw fa-times me-1"></i> Close</button>
        <button type="submit" name="btnSubmit" class="btn btn-primary"><i class="fa fa-fw fa-save me-1"></i>
            Save</button>
    </div>
</form>