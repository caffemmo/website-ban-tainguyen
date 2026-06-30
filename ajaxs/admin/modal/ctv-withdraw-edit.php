<?php

define("IN_SITE", true);
require_once(__DIR__."/../../../config.php");
require_once(__DIR__."/../../../libs/db.php");
require_once(__DIR__."/../../../libs/lang.php");
require_once(__DIR__."/../../../libs/helper.php");
require_once(__DIR__.'/../../../libs/database/users.php');
require_once(__DIR__.'/../../../models/is_admin.php');

if (empty($_GET['token'])) {
    die('<script type="text/javascript">if(!alert("'.__('Please log in').'")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
}
if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '".check_string($_GET['token'])."' AND `banned` = 0 AND `admin` != 0 ")) {
    die('<script type="text/javascript">if(!alert("'.__('Please log in').'")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
}
if(checkPermission($getUser['admin'], 'edit_withdraw_ctv') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
}
if(!$row = $CMSNT->get_row(" SELECT * FROM `ctv_withdraw` WHERE `id` = '".check_string($_GET['id'])."'  ")){
    die('<script type="text/javascript">if(!alert("'.__('Item does not exist').'")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
}

if (isset($_POST['btnSubmit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
    }
    
    // Kiểm tra nếu đã completed thì không cho thay đổi
    if($row['status'] == 'completed'){
        die('<script type="text/javascript">if(!alert("Đơn rút này đã hoàn thành, không thể thay đổi trạng thái")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
    }
    $status = check_string($_POST['status']);
    // Nếu chuyển từ completed sang cancel (trường hợp hiếm)
    if($status == 'cancel'){
        // Hoàn tiền lại cho CTV khi lệnh rút hủy
        $User = new users;
        $User->RefundCredits($row['user_id'], $row['amount'], __('[CTV] Hủy đơn rút tiền của CTV').' #'.$row['trans_id'], 'CTV_REFUND_WITHDRAW_'.$row['trans_id']);
    }
    
    $isUpdate = $CMSNT->update("ctv_withdraw", [
        'status'            => $status,
        'reason'            => check_string($_POST['reason']),
        'updated_at'        => gettime()
    ], " `id` = '" . $row['id'] . "' ");
    
    if ($isUpdate) {
        die('<script type="text/javascript">if(!alert("Lưu thành công!")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
    }
    die('<script type="text/javascript">if(!alert("Lưu thất bại!")){location.href=`' . base_url_admin('ctv-withdraw') . '`;}</script>');
}

?>

<form action="<?=BASE_URL('ajaxs/admin/modal/ctv-withdraw-edit.php?id='.$row['id'].'&token='.$getUser['token']);?>"
    method="POST">

    <div class="modal-header">
        <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa fa-edit"></i> <?=__('Chỉnh sửa yêu cầu rút tiền CTV');?>
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
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Số tiền yêu cầu:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=format_currency($row['amount']);?>" disabled>
                    </div>
                </div>
            </div>
            <?php if($CMSNT->site('ctv_fee_withdraw') > 0): ?>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Phí rút tiền:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=format_currency($row['fee'] ?? 0);?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Số tiền thực nhận:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control fw-bold text-success" value="<?=format_currency($row['receive'] ?? $row['amount']);?>" disabled>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">CTV:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=getRowRealtime("users", $row['user_id'], "username");?> [ID: <?=$row['user_id'];?>]" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Số dư hiện tại:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" value="<?=format_currency(getRowRealtime("users", $row['user_id'], "money"));?>" disabled>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Trạng thái:</label>
                    <div class="col-sm-7">
                        <select class="form-control mb-1" name="status">
                            <option <?=$row['status'] == 'pending' ? 'selected' : '';?> value="pending">
                                <?=__('Chờ xử lý');?></option>
                            <option <?=$row['status'] == 'completed' ? 'selected' : '';?> value="completed">
                                <?=__('Hoàn thành');?></option>
                            <option <?=$row['status'] == 'cancel' ? 'selected' : '';?> value="cancel">
                                <?=__('Hủy bỏ');?></option>
                        </select>
                        <ul>
                            <li><strong>Chờ xử lý:</strong> CTV đã gửi yêu cầu, chờ admin xử lý.</li>
                            <li><strong>Hoàn thành:</strong> Sau khi đã chuyển tiền cho CTV thì hoàn thành lệnh.</li>
                            <li><strong>Hủy bỏ:</strong> Hủy yêu cầu rút tiền, tiền sẽ được hoàn về tài khoản của CTV tự động.</li>
                        </ul>
                        </br>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="row mb-3">
                    <label class="col-sm-5 col-form-label" for="example-hf-email">Lý do:</label>
                    <div class="col-sm-7">
                        <textarea class="form-control" rows="4" name="reason" placeholder="Nhập lý do (nếu có)"><?=$row['reason'];?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hiển thị QR Code nếu cần -->
        <center class="py-3">
            <?php 
            // Sử dụng số tiền thực nhận cho QR code
            $actualAmount = $row['receive'] ?? $row['amount'];
            ?>
            <?php if($row['bank'] == 'Ví MOMO'): ?>
                <?=file_get_contents("https://api.web2m.com/api/qrmomo.php?amount=".$actualAmount."&phone=".$row['stk']."&noidung=".$row['trans_id']."&size=300");?>
            <?php else:?>
                <?php
                $img1 = "https://api.vietqr.io/".$row['bank']."/".$row['stk']."/".$actualAmount."/".$row['trans_id']."/vietqr_net_2.jpg?accountName=".$row['name'];
                $img = $img1;
                $is_img = curl_get($img1);
                ?>
                <?php if($is_img != 'invalid acqId'):?>
                    <img src="<?=$img;?>" width="300px" />
                <?php else:?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> Không thể tạo QR Code cho ngân hàng này.
                    </div>
                <?php endif?>
            <?php endif?>
        </center>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i
                class="fa fa-fw fa-times me-1"></i> Close</button>
        <button type="submit" name="btnSubmit" class="btn btn-primary"><i class="fa fa-fw fa-save me-1"></i>
            Save</button>
    </div>
</form>
